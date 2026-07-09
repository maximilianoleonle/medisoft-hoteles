<?php
/**
 * Copiloto IA (bloque copiloto_ia): endpoints JSON de las funciones de IA
 * sobre datos existentes. Interno (requiere sesion). Solo lectura.
 *
 * El gate del bloque copiloto_ia NO va aqui: lo resuelve el servicio, porque
 * los hoteles SIN el bloque tienen derecho a la prueba gratis (teaser). Lo que
 * SI se gatea por accion es el bloque fuente de los datos (reputacion/forecast).
 */

require_once __DIR__ . '/../services/CopilotoIaService.php';

class CopilotoIaController extends Controller {

    private const THROTTLE_MAX = 10;      // generaciones
    private const THROTTLE_VENTANA = 60;  // segundos

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        return true;
    }

    /** Borrador de respuesta a una encuesta/resena (requiere bloque reputacion). */
    public function resenaAction() {
        $this->prepararJson('reputacion');

        $encuestaId = (int) $this->getPost('encuesta_id', 0);

        $this->responderCon(static function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) use ($encuestaId) {
            return $servicio->borradorResena($hotelId, $encuestaId, $regenerar, user_id());
        });
    }

    /** Analisis mensual de encuestas (requiere bloque reputacion). */
    public function analisisAction() {
        $this->prepararJson('reputacion');

        $mes = (string) $this->getPost('mes', '');

        $this->responderCon(static function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) use ($mes) {
            return $servicio->analisisEncuestas($hotelId, $mes !== '' ? $mes : null, $regenerar, user_id());
        });
    }

    /** Consejo de tarifa del dia (requiere bloque forecast). */
    public function tarifaAction() {
        $this->prepararJson('forecast');

        $this->responderCon(static function (CopilotoIaService $servicio, int $hotelId, bool $regenerar) {
            return $servicio->consejoTarifa($hotelId, $regenerar, user_id());
        });
    }

    // ───────────────────── Internos ─────────────────────

    /** Valida metodo, CSRF, bloque fuente y throttle. Corta con JSON si algo falla. */
    private function prepararJson(string $moduloFuente) {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido.'], 405);
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module($moduloFuente);
        }

        $hotelId = (int) obtenerHotelIdActualCompat();
        if (!$this->permitirSolicitud($hotelId)) {
            View::renderJSON(['success' => false, 'message' => 'Vas muy rapido. Espera un momento e intenta de nuevo.'], 429);
        }
    }

    private function responderCon(callable $fn) {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $regenerar = (string) $this->getPost('regenerar', '0') === '1';

        try {
            $servicio = new CopilotoIaService();
            $resultado = $fn($servicio, $hotelId, $regenerar);
        } catch (Throwable $e) {
            error_log('CopilotoIA: error en endpoint: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Ocurrio un error. Intenta de nuevo.'], 500);
            return;
        }

        View::renderJSON($resultado, 200);
    }

    /** Rate-limit por usuario reutilizando login_intentos (prefijo copiloto_ia|). */
    private function permitirSolicitud(int $hotelId) {
        $uid = (int) user_id();
        $clave = hash('sha256', 'copiloto_ia|' . $uid . '|' . $hotelId);

        try {
            $db = Database::getInstance();
            $db->query(
                "INSERT INTO login_intentos (clave, ip, nombre_usuario, hotel_slug, intentos, ultimo_intento, created_at)
                 VALUES (?, ?, 'copiloto_ia', ?, 1, NOW(), NOW())
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
