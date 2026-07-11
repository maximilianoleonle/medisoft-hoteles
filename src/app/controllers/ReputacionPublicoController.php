<?php
/**
 * Encuesta post-estancia publica (bloque reputacion, sin login).
 * El token del link ES la credencial (32 hex aleatorios por reservacion);
 * por eso el POST no usa CSRF de sesion, igual que los endpoints publicos
 * del motor y del check-in digital. Rate limit reutiliza login_intentos.
 */

require_once __DIR__ . '/../services/ReputacionService.php';

class ReputacionPublicoController extends Controller {

    public function formularioAction($slug, $token) {
        [$hotel, $encuesta] = $this->resolver($slug, $token);

        View::renderTemplate('encuesta/formulario', [
            'title' => 'Tu opinion - ' . ($hotel['nombre'] ?? 'Hotel'),
            'hotel' => $hotel,
            'branding' => $this->brandingPublico($hotel),
            'encuesta' => $encuesta,
            'token' => $token,
            'resultado' => null,
            'googleUrl' => $this->googleUrl((int) $hotel['id']),
        ]);
    }

    public function responderAction($slug, $token) {
        if (!$this->isPost()) {
            $this->redirect('h/' . $slug . '/encuesta/' . $token);
        }

        [$hotel, $encuesta] = $this->resolver($slug, $token);

        if (!$this->permitirSolicitud((string) $hotel['slug'])) {
            $resultado = ['success' => false, 'message' => 'Demasiados intentos. Espera un minuto e intenta de nuevo.', 'mostrar_google' => false];
        } else {
            $servicio = new ReputacionService();
            $resultado = $servicio->responder((int) $hotel['id'], (string) $token, $_POST);
        }

        // Releer la encuesta para reflejar el estado final en la vista.
        $servicio = $servicio ?? new ReputacionService();
        $encuesta = $servicio->porToken((int) $hotel['id'], (string) $token);

        View::renderTemplate('encuesta/formulario', [
            'title' => 'Tu opinion - ' . ($hotel['nombre'] ?? 'Hotel'),
            'hotel' => $hotel,
            'branding' => $this->brandingPublico($hotel),
            'encuesta' => $encuesta,
            'token' => $token,
            'resultado' => $resultado,
            'googleUrl' => $this->googleUrl((int) $hotel['id']),
        ]);
    }

    // ───────────────────────── Helpers ─────────────────────────

    /** Resuelve hotel + encuesta o corta con pagina amable. */
    private function resolver($slug, $token) {
        $slug = strtolower(trim((string) $slug));
        $hotel = null;

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug)) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query(
                    "SELECT id, nombre, slug, activo FROM hoteles WHERE slug = ? AND activo = 1 LIMIT 1",
                    [$slug]
                );
                $hotel = $stmt ? ($stmt->fetch() ?: null) : null;
            } catch (Throwable $e) {
                error_log('Encuesta publica: error al resolver hotel: ' . $e->getMessage());
            }
        }

        if (!$hotel) {
            $this->paginaPublica(404, 'Hotel no encontrado', 'La liga que abriste no es valida.');
        }

        if (!function_exists('hotel_has_module') || !hotel_has_module('reputacion', (int) $hotel['id'])) {
            $this->paginaPublica(404, 'Encuesta no disponible', 'Este hotel no tiene habilitadas las encuestas en linea.', $hotel);
        }

        TenantContext::setHotel($hotel);

        $servicio = new ReputacionService();
        $encuesta = $servicio->porToken((int) $hotel['id'], (string) $token);

        if (!$encuesta) {
            $this->paginaPublica(404, 'Liga no valida', 'Esta liga de encuesta no existe. Pide una nueva al hotel.', $hotel);
        }

        return [$hotel, $encuesta];
    }

    private function googleUrl(int $hotelId): string {
        return trim((string) ConfiguracionHotelRegistry::get('reputacion.google_review_url', '', $hotelId));
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

    private function permitirSolicitud(string $slug) {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconocida');
        $clave = hash('sha256', 'encuesta|' . $ip . '|' . $slug);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'encuesta_publica', ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    intentos = IF(ultimo_intento < NOW() - INTERVAL 60 SECOND, 1, intentos + 1),
                    ultimo_intento = NOW()",
                [$clave, substr($ip, 0, 45), substr($slug, 0, 120)]
            );

            $stmt = $db->query("SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1", [$clave]);
            $row = $stmt ? $stmt->fetch() : null;

            return (int) ($row['intentos'] ?? 0) <= 15;
        } catch (Throwable $e) {
            // Fail-CLOSED: si no se puede evaluar el throttle de este POST público
            // (encuesta), rechazar en vez de permitir abuso ilimitado.
            error_log('encuesta_publica permitirSolicitud fail-closed: ' . $e->getMessage());
            return false;
        }
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
