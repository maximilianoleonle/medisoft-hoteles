<?php
/**
 * Motor de reservas publico por hotel (sin login).
 *
 * Resolucion de tenant copiada del patron de PwaController::manifestAction:
 * slug -> hotel activo -> TenantContext::setHotel ANTES de tocar modelos
 * (los modelos se scopean por el hotel del contexto).
 *
 * Guards en cadena: hotel activo -> bloque motor_reservas -> config motor.publico_activo.
 * Endpoints JSON con rate-limit por IP reutilizando la tabla login_intentos.
 */

require_once __DIR__ . '/../services/MotorDisponibilidadService.php';

class MotorReservasPublicoController extends Controller {

    private const THROTTLE_MAX = 30;      // solicitudes
    private const THROTTLE_VENTANA = 60;  // segundos

    public function reservarAction($slug) {
        $hotel = $this->resolverHotel($slug);
        if (!$hotel) {
            $this->paginaPublica(404, 'Hotel no encontrado', 'La pagina que buscas no existe o el hotel no esta activo.');
        }

        if (!$this->motorHabilitado($hotel)) {
            $this->paginaPublica(
                404,
                'Reservas en linea no disponibles',
                'Este hotel aun no tiene habilitadas las reservas en linea. Contactalo directamente para reservar.',
                $hotel
            );
        }

        TenantContext::setHotel($hotel);
        $hotelId = (int) $hotel['id'];

        View::renderTemplate('motor/reservar', [
            'title' => 'Reservar - ' . ($hotel['nombre'] ?? 'Hotel'),
            'hotel' => $hotel,
            'branding' => $this->brandingPublico($hotel),
            'politica' => (string) ConfiguracionHotelRegistry::get('motor.politica_texto', '', $hotelId),
            'minNoches' => ConfiguracionHotelRegistry::getInt('motor.min_noches', 1, $hotelId),
            'maxNoches' => ConfiguracionHotelRegistry::getInt('motor.max_noches', 30, $hotelId),
            'anticipacionMaxDias' => ConfiguracionHotelRegistry::getInt('motor.anticipacion_max_dias', 180, $hotelId),
        ]);
    }

    public function disponibilidadAction($slug) {
        $hotel = $this->resolverHotel($slug);
        if (!$hotel) {
            $this->jsonPublico(['success' => false, 'message' => 'Hotel no encontrado.'], 404);
        }

        if (!$this->motorHabilitado($hotel)) {
            $this->jsonPublico(['success' => false, 'message' => 'Reservas en linea no disponibles para este hotel.'], 404);
        }

        if (!$this->permitirSolicitud((string) $hotel['slug'])) {
            $this->jsonPublico(['success' => false, 'message' => 'Demasiadas consultas. Intenta de nuevo en un minuto.'], 429);
        }

        TenantContext::setHotel($hotel);
        $hotelId = (int) $hotel['id'];

        $entrada = trim((string) $this->getQuery('entrada', ''));
        $salida = trim((string) $this->getQuery('salida', ''));
        $personas = max(0, (int) $this->getQuery('personas', 0));

        $service = new MotorDisponibilidadService();
        $rango = $service->validarRango($hotelId, $entrada, $salida);
        if (!$rango['ok']) {
            $this->jsonPublico(['success' => false, 'message' => $rango['error']], 422);
        }

        try {
            $resultado = $service->consultar($hotelId, $entrada, $salida, $personas);
        } catch (Throwable $e) {
            error_log('Motor publico: error al consultar disponibilidad del hotel ' . $hotelId . ': ' . $e->getMessage());
            $this->jsonPublico(['success' => false, 'message' => 'No se pudo consultar la disponibilidad. Intenta de nuevo.'], 500);
            return;
        }

        $resultado['moneda'] = (string) ($hotel['moneda_codigo'] ?? 'MXN');
        $resultado['moneda_simbolo'] = (string) ($hotel['moneda_simbolo'] ?? '$');
        $this->jsonPublico($resultado, 200);
    }

    // ───────────────────────── Guards y helpers ─────────────────────────

    private function resolverHotel($slug) {
        $slug = strtolower(trim((string) $slug));
        if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug)) {
            return null;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, nombre, slug, activo, moneda_codigo, moneda_simbolo
                 FROM hoteles
                 WHERE slug = ? AND activo = 1
                 LIMIT 1",
                [$slug]
            );

            return $stmt ? ($stmt->fetch() ?: null) : null;
        } catch (Throwable $e) {
            error_log('Motor publico: error al resolver hotel por slug: ' . $e->getMessage());
            return null;
        }
    }

    private function motorHabilitado(array $hotel) {
        $hotelId = (int) ($hotel['id'] ?? 0);

        if (!function_exists('hotel_has_module') || !hotel_has_module('motor_reservas', $hotelId)) {
            return false;
        }

        return ConfiguracionHotelRegistry::getBool('motor.publico_activo', false, $hotelId);
    }

    private function brandingPublico(array $hotel) {
        if (!function_exists('hotel_branding')) {
            return [];
        }

        return hotel_branding((int) $hotel['id'], [
            'id' => (int) $hotel['id'],
            'hotel_id' => (int) $hotel['id'],
            'nombre_comercial' => $hotel['nombre'] ?? null,
            'slug' => $hotel['slug'] ?? null,
        ]);
    }

    /**
     * Rate-limit por IP+slug sobre la tabla login_intentos (clave con prefijo motor|).
     * Ventana fija de 60s; falla abierto si la tabla no existe.
     */
    private function permitirSolicitud(string $slug) {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
        $clave = hash('sha256', 'motor|' . $ip . '|' . $slug);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'motor_publico', ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    intentos = IF(ultimo_intento < NOW() - INTERVAL " . self::THROTTLE_VENTANA . " SECOND, 1, intentos + 1),
                    ultimo_intento = NOW()",
                [$clave, substr($ip, 0, 45), substr($slug, 0, 120)]
            );

            $stmt = $db->query("SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1", [$clave]);
            $row = $stmt ? $stmt->fetch() : null;

            return (int) ($row['intentos'] ?? 0) <= self::THROTTLE_MAX;
        } catch (Throwable $e) {
            error_log('Motor publico: rate limit no disponible: ' . $e->getMessage());
            return true;
        }
    }

    private function jsonPublico(array $payload, int $statusCode) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function paginaPublica(int $statusCode, string $titulo, string $mensaje, ?array $hotel = null) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        $nombre = htmlspecialchars((string) ($hotel['nombre'] ?? 'Medisoft Hoteles'), ENT_QUOTES, 'UTF-8');
        $titulo = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
        echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"utf-8\">"
            . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
            . "<meta name=\"robots\" content=\"noindex\">"
            . "<title>{$titulo} - {$nombre}</title>"
            . "<style>body{font-family:system-ui,sans-serif;background:#f6f4ef;color:#334;display:grid;place-items:center;min-height:100vh;margin:0;padding:24px;text-align:center}main{max-width:420px}h1{font-size:1.3rem}p{line-height:1.5;color:#667}</style>"
            . "</head><body><main><h1>{$titulo}</h1><p>{$mensaje}</p></main></body></html>";
        exit;
    }
}
