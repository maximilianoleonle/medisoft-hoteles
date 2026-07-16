<?php
/**
 * Copiloto Medisoft (bloque copiloto): endpoint JSON del asistente hibrido.
 * Interno (requiere sesion). Las preguntas son solo lectura; las acciones
 * (accionAction) solo ejecutan lo que el usuario ya confirmo en el widget.
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
        // Contexto de pantalla: ruta relativa desde la que pregunta el usuario
        // ("reservaciones/ver/12"). Solo orienta la respuesta; el servicio hace
        // todas sus lecturas con scope de hotel y validando el permiso del rol.
        $ruta = mb_substr((string) $this->getPost('ruta', ''), 0, 200);
        // Memoria de conversacion: el intent que este mismo endpoint devolvio
        // en la pregunta anterior ("¿y manana?" hereda el tema).
        $intentPrevio = mb_substr((string) $this->getPost('intent_previo', ''), 0, 40);

        try {
            $servicio = new CopilotoService();
            $resultado = $servicio->responder($hotelId, $pregunta, user_id(), $ruta, $intentPrevio);
        } catch (Throwable $e) {
            error_log('Copiloto: error al responder: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'texto' => 'Ocurrio un error. Intenta de nuevo.', 'fuente' => 'fallback'], 500);
            return;
        }

        View::renderJSON($resultado, 200);
    }

    /**
     * Ejecuta una accion previamente confirmada por el usuario en el widget
     * (msConfirm): limpieza/tareas, mantenimiento (bloquear/liberar) o gasto
     * de caja en efectivo. El servicio revalida el permiso del rol por tipo;
     * en dinero solo existe el gasto, jamas cobros ni cortes.
     */
    public function accionAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'texto' => 'Metodo no permitido.'], 405);
        }

        $this->validateCSRF();

        $hotelId = (int) obtenerHotelIdActualCompat();

        if (!$this->permitirSolicitud($hotelId)) {
            View::renderJSON(['success' => false, 'texto' => 'Vas muy rapido. Espera un momento e intenta de nuevo.', 'fuente' => 'fallback'], 429);
        }

        $tipo = (string) $this->getPost('tipo', '');
        $params = [
            'habitacion_id' => (int) $this->getPost('habitacion_id', 0),
            'fecha' => mb_substr((string) $this->getPost('fecha', ''), 0, 10),
            'trabajador_id' => (int) $this->getPost('trabajador_id', 0),
            'motivo' => mb_substr(trim((string) $this->getPost('motivo', '')), 0, 300),
            // Solo para registrar_gasto (el metodo es siempre efectivo y lo
            // fija el servidor; el POST no puede elegirlo).
            'monto' => (float) str_replace([',', '$', ' '], '', (string) $this->getPost('monto', '0')),
            'categoria_id' => (int) $this->getPost('categoria_id', 0),
            'descripcion' => mb_substr(trim((string) $this->getPost('descripcion', '')), 0, 200),
            // Solo para crear_cupon (MotorCuponService::crear revalida todo).
            'codigo' => mb_substr(trim((string) $this->getPost('codigo', '')), 0, 30),
            'cupon_tipo' => mb_substr((string) $this->getPost('cupon_tipo', ''), 0, 10),
            'valor' => mb_substr((string) $this->getPost('valor', ''), 0, 12),
            'vigente_desde' => mb_substr((string) $this->getPost('vigente_desde', ''), 0, 10),
            'vigente_hasta' => mb_substr((string) $this->getPost('vigente_hasta', ''), 0, 10),
            'limite_usos' => mb_substr((string) $this->getPost('limite_usos', ''), 0, 6),
        ];

        try {
            $servicio = new CopilotoService();
            $resultado = $servicio->ejecutarAccion($hotelId, $tipo, $params, user_id());
        } catch (Throwable $e) {
            error_log('Copiloto: error al ejecutar accion: ' . $e->getMessage());
            View::renderJSON(['success' => false, 'texto' => 'Ocurrio un error. Intenta de nuevo.', 'fuente' => 'fallback'], 500);
            return;
        }

        View::renderJSON($resultado, 200);
    }

    /**
     * Panel de valor del copiloto (GET /copiloto/valor): uso real del
     * asistente para gerencia. Solo lectura sobre el log existente.
     */
    public function valorAction() {
        // Mismo criterio de acceso que la pantalla de Configuracion: gerencia.
        $rolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
        if (!(is_gerente() || in_array($rolHotel, ['gerente', 'administrador'], true))) {
            set_mensaje('No tiene permisos para acceder a esta sección', 'error');
            $this->redirect('dashboard');
            return;
        }

        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new CopilotoService();

        View::renderTemplate('copiloto/valor', [
            'title' => 'Copiloto - ' . current_hotel_display_name(),
            'uso' => $servicio->resumenUso($hotelId, 30),
            'nombreAsistente' => CopilotoService::nombreAsistente($hotelId),
            'iaDisponible' => $servicio->iaDisponible($hotelId),
        ]);
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
