<?php
/**
 * QR en la habitacion — pagina publica "Tu estancia" (sin login).
 *
 * El token del QR ES la credencial de la habitacion (32 hex, rotable desde
 * /peticiones/qr). Igual que el check-in digital y la encuesta, el POST no
 * usa CSRF de sesion (la ruta es publica); lo protegen el token, la regla de
 * "solo con huesped en check-in" y los topes (por IP aqui, por habitacion en
 * el servicio).
 *
 * Nunca muestra datos del huesped: el QR esta pegado en el cuarto y lo puede
 * escanear cualquiera que entre.
 */

require_once __DIR__ . '/../services/EstanciaService.php';

class EstanciaPublicoController extends Controller {

    /** Peticiones por IP y minuto antes de frenar (ademas del tope por habitacion). */
    private const MAX_POR_MINUTO_IP = 12;

    public function portalAction($slug, $token) {
        [$hotel, $habitacion] = $this->resolver($slug, $token);
        $hotelId = (int) $hotel['id'];
        $servicio = new EstanciaService();

        $config = $servicio->configuracion($hotelId);
        $estancia = $servicio->estanciaActiva($hotelId, (int) $habitacion['habitacion_id']);
        $peticiones = $estancia
            ? $servicio->peticionesDeEstancia($hotelId, (int) $habitacion['habitacion_id'], (int) $estancia['reservacion_id'])
            : [];

        $resultado = (string) ($_GET['r'] ?? '');
        $resultadosValidos = ['ok', 'ok_sin_foto', 'duplicada', 'detalle', 'tipo', 'limite', 'desactivadas', 'sin_estancia', 'error'];

        $this->cabecerasPrivadas();

        View::renderTemplate('estancia/portal', [
            'title' => 'Tu estancia - ' . ($hotel['nombre'] ?? 'Hotel'),
            'hotel' => $hotel,
            'branding' => $this->brandingPublico($hotel),
            'habitacion' => $habitacion,
            'token' => (string) $token,
            'config' => $config,
            'contacto' => $servicio->contactoRecepcion($hotelId),
            'aceptaPeticiones' => $estancia !== null && !empty($config['peticiones_activas']),
            'hayEstancia' => $estancia !== null,
            'peticiones' => $peticiones,
            'tipos' => EstanciaService::TIPOS,
            'resultado' => in_array($resultado, $resultadosValidos, true) ? $resultado : '',
        ]);
    }

    public function pedirAction($slug, $token) {
        $ruta = 'h/' . strtolower(trim((string) $slug)) . '/estancia/' . $token;

        if (!$this->isPost()) {
            $this->redirect($ruta);
        }

        [$hotel, $habitacion] = $this->resolver($slug, $token);

        if (!$this->permitirSolicitud((string) $hotel['slug'])) {
            $codigo = 'limite';
        } else {
            // POST crudo a proposito: el servicio normaliza y la vista escapa
            // al pintar (post() aplicaria htmlspecialchars y guardaria &quot;).
            $resultado = (new EstanciaService())->crearPeticion(
                (int) $hotel['id'],
                (int) $habitacion['habitacion_id'],
                (string) ($_POST['tipo'] ?? ''),
                is_string($_POST['detalle'] ?? null) ? (string) $_POST['detalle'] : '',
                isset($_FILES['foto']) && is_array($_FILES['foto']) && !is_array($_FILES['foto']['name'] ?? null) ? $_FILES['foto'] : null,
                (string) ($_SERVER['REMOTE_ADDR'] ?? '')
            );
            $codigo = (string) ($resultado['codigo'] ?? 'error');
        }

        // Post/Redirect/Get: recargar la pagina no reenvia la peticion.
        header('Location: ' . url($ruta) . '?r=' . rawurlencode($codigo) . '#peticiones', true, 303);
        exit;
    }

    // ───────────────────────── Helpers ─────────────────────────

    /** Resuelve hotel + habitacion del QR o corta con pagina amable. */
    private function resolver($slug, $token) {
        $slug = strtolower(trim((string) $slug));
        $hotel = null;

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug)) {
            try {
                $stmt = Database::getInstance()->query(
                    "SELECT id, nombre, slug, activo FROM hoteles WHERE slug = ? AND activo = 1 LIMIT 1",
                    [$slug]
                );
                $hotel = $stmt ? ($stmt->fetch() ?: null) : null;
            } catch (Throwable $e) {
                error_log('Estancia publica: error al resolver hotel: ' . $e->getMessage());
            }
        }

        if (!$hotel) {
            $this->paginaPublica(404, 'Hotel no encontrado', 'El código que escaneaste no es válido.');
        }

        if (!EstanciaService::disponible((int) $hotel['id'])) {
            $this->paginaPublica(404, 'No disponible', 'Este hotel no tiene activo el servicio del código QR. Para cualquier cosa, comunícate con recepción.', $hotel);
        }

        TenantContext::setHotel($hotel);

        $habitacion = (new EstanciaService())->porToken((int) $hotel['id'], (string) $token);

        if (!$habitacion) {
            $this->paginaPublica(404, 'Código no válido', 'Este código ya no funciona. Pide ayuda en recepción.', $hotel);
        }

        return [$hotel, $habitacion];
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
     * La pagina cambia con cada peticion (su estado) y la URL lleva el token:
     * nada de cache compartido ni de mandarla como referer a otros sitios.
     */
    private function cabecerasPrivadas() {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: private, no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }

    private function permitirSolicitud(string $slug) {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
        $clave = hash('sha256', 'estancia|' . $ip . '|' . $slug);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'estancia_publica', ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    intentos = IF(ultimo_intento < NOW() - INTERVAL 60 SECOND, 1, intentos + 1),
                    ultimo_intento = NOW()",
                [$clave, substr($ip, 0, 45), substr($slug, 0, 120)]
            );

            $stmt = $db->query("SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1", [$clave]);
            $row = $stmt ? $stmt->fetch() : null;

            return (int) ($row['intentos'] ?? 0) <= self::MAX_POR_MINUTO_IP;
        } catch (Throwable $e) {
            // Fail-CLOSED, mismo criterio que el check-in publico: sin poder
            // medir el abuso, no se abre la puerta (el aviso dice que llamen).
            error_log('estancia_publica permitirSolicitud fail-closed: ' . $e->getMessage());
            return false;
        }
    }

    private function paginaPublica(int $statusCode, string $titulo, string $mensaje, ?array $hotel = null) {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        $this->cabecerasPrivadas();
        $nombre = htmlspecialchars((string) ($hotel['nombre'] ?? 'Medisoft Hoteles'), ENT_QUOTES, 'UTF-8');
        $titulo = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $mensaje = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');
        echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"utf-8\">"
            . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">"
            . "<meta name=\"robots\" content=\"noindex\"><meta name=\"referrer\" content=\"no-referrer\">"
            . "<title>{$titulo} - {$nombre}</title>"
            . "<style>body{font-family:system-ui,sans-serif;background:#F5F5F7;color:#2A3242;display:grid;place-items:center;min-height:100vh;margin:0;padding:24px;text-align:center}main{max-width:420px}h1{font-size:1.3rem}p{line-height:1.5;color:#5B6475}</style>"
            . "</head><body><main><h1>{$titulo}</h1><p>{$mensaje}</p><p><small>{$nombre}</small></p></main></body></html>";
        exit;
    }
}
