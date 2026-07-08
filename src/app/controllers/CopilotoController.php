<?php
/**
 * Copiloto Medisoft (bloque copiloto): endpoint JSON del asistente hibrido.
 * Interno (requiere sesion). Solo lectura; nunca ejecuta cambios.
 */

require_once __DIR__ . '/../services/CopilotoService.php';

class CopilotoController extends Controller {

    private const THROTTLE_MAX = 20;      // preguntas
    private const THROTTLE_VENTANA = 60;  // segundos

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('copiloto');
        }

        return true;
    }

    public function preguntarAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'texto' => 'Metodo no permitido.'], 405);
        }

        $this->validateCSRF();

        $hotelId = (int) obtenerHotelIdActualCompat();

        if (!$this->permitirSolicitud($hotelId)) {
            View::renderJSON(['success' => false, 'texto' => 'Vas muy rapido. Espera un momento e intenta de nuevo.', 'fuente' => 'fallback'], 429);
        }

        $pregunta = (string) $this->getPost('pregunta', '');

        try {
            $servicio = new CopilotoService();
            $resultado = $servicio->responder($hotelId, $pregunta, user_id());
        } catch (Throwable $e) {
            error_log('Copiloto: error al responder: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'texto' => 'Ocurrio un error. Intenta de nuevo.', 'fuente' => 'fallback'], 500);
            return;
        }

        View::renderJSON($resultado, 200);
    }

    /** Rate-limit por usuario reutilizando login_intentos (prefijo copiloto|). */
    private function permitirSolicitud(int $hotelId) {
        $uid = (int) user_id();
        $clave = hash('sha256', 'copiloto|' . $uid . '|' . $hotelId);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'copiloto', ?, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    intentos = IF(ultimo_intento < NOW() - INTERVAL " . self::THROTTLE_VENTANA . " SECOND, 1, intentos + 1),
                    ultimo_intento = NOW()",
                [$clave, substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45), 'hotel_' . $hotelId]
            );

            $stmt = $db->query("SELECT intentos FROM login_intentos WHERE clave = ? LIMIT 1", [$clave]);
            $row = $stmt ? $stmt->fetch() : null;

            return (int) ($row['intentos'] ?? 0) <= self::THROTTLE_MAX;
        } catch (Throwable $e) {
            return true;
        }
    }
}
