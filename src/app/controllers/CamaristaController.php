<?php
require_once __DIR__ . '/../models/TareaOperativa.php';

/**
 * App de camarista (bloque camarista): tablero movil de limpieza.
 * Cualquier usuario del hotel puede usarlo (la camarista entra con su usuario);
 * solo alterna limpieza <-> disponible, nunca toca ocupada ni mantenimiento.
 * Ademas registra quien hizo cada limpieza y permite programar limpiezas
 * (fecha + personal) apoyandose en tareas operativas de categoria limpieza.
 */

class CamaristaController extends Controller {

    /** @var TareaOperativa */
    private $tareaModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->tareaModel = new TareaOperativa();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('camarista');
        }

        return true;
    }

    private function tareasDisponibles(): bool {
        try {
            return $this->tareaModel->tablaDisponible() && $this->tareaModel->eventosDisponibles();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Trabajadores activos del hotel (roles de limpieza primero).
     */
    private function personalActivo(int $hotelId): array {
        if (!$this->tareasDisponibles()) {
            return [];
        }

        $personal = [];
        foreach ($this->tareaModel->trabajadoresActivosOpciones($hotelId) as $t) {
            $personal[] = [
                'id' => (int) $t['id'],
                'nombre' => (string) $t['nombre_completo'],
                'rol' => (string) ($t['rol_laboral'] ?? ''),
            ];
        }

        usort($personal, static function (array $a, array $b): int {
            $esLimpieza = static function (array $p): int {
                $rol = strtolower($p['rol']);
                return (strpos($rol, 'camarist') !== false || strpos($rol, 'limpiez') !== false) ? 0 : 1;
            };
            $orden = $esLimpieza($a) <=> $esLimpieza($b);
            return $orden !== 0 ? $orden : strcasecmp($a['nombre'], $b['nombre']);
        });

        return $personal;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT id, numero, tipo, piso, estado FROM habitaciones
             WHERE hotel_id = ? AND activa = 1
             ORDER BY piso, CAST(numero AS UNSIGNED)",
            [$hotelId]
        );
        $habitaciones = $stmt ? $stmt->fetchAll() : [];

        // Salidas de hoy: al hacer checkout esas habitaciones necesitaran limpieza.
        $stmt = $db->query(
            "SELECT rh.habitacion_id, r.estado AS reservacion_estado
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             WHERE rh.hotel_id = ?
               AND r.fecha_salida = CURDATE()
               AND r.estado IN ('checked_in', 'checked_out')",
            [$hotelId]
        );
        $salidasHoy = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $salidasHoy[(int) $fila['habitacion_id']] = (string) $fila['reservacion_estado'];
        }

        // Personal activo + limpieza activa por cuarto (asignados / programadas).
        $personal = $this->personalActivo($hotelId);
        $tareasPorHabitacion = [];
        if (!empty($personal)) {
            foreach ($this->tareaModel->listarPorHotel($hotelId, ['categoria' => 'limpieza'], 300) as $tarea) {
                $habId = (int) ($tarea['habitacion_id'] ?? 0);
                $estado = (string) ($tarea['estado'] ?? '');
                if ($habId <= 0 || !in_array($estado, ['pendiente', 'asignada', 'en_proceso'], true) || isset($tareasPorHabitacion[$habId])) {
                    continue;
                }

                $tareasPorHabitacion[$habId] = [
                    'id' => (int) $tarea['id'],
                    'estado' => $estado,
                    'fecha_programada' => (string) ($tarea['fecha_programada'] ?? ''),
                    'trabajador_ids' => array_values(array_filter(array_map('intval', explode(',', (string) ($tarea['trabajadores_ids'] ?? ''))))),
                    'trabajador_nombres' => (string) ($tarea['trabajadores_nombres'] ?? ''),
                ];
            }
        }

        View::renderTemplate('camarista/index', [
            'title' => 'Limpieza - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'salidasHoy' => $salidasHoy,
            'personal' => $personal,
            'tareasLimpieza' => $tareasPorHabitacion,
        ]);
    }

    public function marcarAction($habitacionId = null) {
        if (!$this->isPost()) {
            $this->redirect('camarista');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $habitacionId = (int) $habitacionId;
        $nuevoEstado = (string) $this->getPost('estado', '');

        // Solo el par limpieza <-> disponible; el resto es de recepcion/mantenimiento.
        if (!in_array($nuevoEstado, ['limpieza', 'disponible'], true)) {
            set_mensaje('Accion no permitida desde el tablero de limpieza.', 'error');
            $this->redirect('camarista');
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT estado FROM habitaciones WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1",
            [$habitacionId, $hotelId]
        );
        $habitacion = $stmt ? $stmt->fetch() : null;

        if (!$habitacion) {
            set_mensaje('Habitacion no encontrada.', 'error');
            $this->redirect('camarista');
        }

        $estadoActual = (string) $habitacion['estado'];

        // Nunca pisar ocupada ni mantenimiento desde aqui.
        if (!in_array($estadoActual, ['limpieza', 'disponible'], true)) {
            set_mensaje('Esa habitacion esta ' . $estadoActual . '; recepcion debe liberarla primero.', 'error');
            $this->redirect('camarista');
        }

        // Al marcar como limpia: quien la limpio es obligatorio (uno o mas
        // trabajadores, o la eleccion explicita "sin registrar personal").
        $sinPersonal = (string) $this->getPost('sin_personal', '') === '1';
        $trabajadorIds = [];
        $raw = $this->getPost('trabajador_ids', []);
        if (is_array($raw)) {
            foreach ($raw as $valor) {
                $valor = (int) $valor;
                if ($valor > 0) {
                    $trabajadorIds[$valor] = $valor;
                }
            }
        }
        $trabajadorIds = array_values($trabajadorIds);

        if ($nuevoEstado === 'disponible' && !$sinPersonal && empty($trabajadorIds) && !empty($this->personalActivo($hotelId))) {
            set_mensaje('Indica quién hizo la limpieza o marca "Sin registrar personal".', 'error');
            $this->redirect('camarista');
        }

        $ok = $db->query(
            "UPDATE habitaciones SET estado = ?, updated_at = NOW() WHERE id = ? AND hotel_id = ?",
            [$nuevoEstado, $habitacionId, $hotelId]
        ) !== false;

        // Registrar quien limpio (fail-open: nunca rompe el marcado).
        if ($ok && $nuevoEstado === 'disponible' && $this->tareasDisponibles()) {
            try {
                $usuarioId = function_exists('user_id') ? user_id() : null;
                $this->tareaModel->completarLimpiezaConPersonalParaHotel(
                    $hotelId,
                    $habitacionId,
                    $sinPersonal ? [] : $trabajadorIds,
                    $usuarioId
                );
            } catch (Throwable $e) {
                error_log('camarista marcar limpia hab #' . $habitacionId . ': ' . $e->getMessage());
            }
        }

        set_mensaje(
            $ok
                ? ($nuevoEstado === 'disponible' ? 'Habitacion marcada como limpia. ✨' : 'Habitacion marcada en limpieza.')
                : 'No se pudo actualizar la habitacion.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('camarista');
    }

    /**
     * Programa (o reprograma) la limpieza de una habitacion: fecha + personal
     * que se encargara. Funciona aunque la habitacion este ocupada hoy.
     * POST /camarista/programar/{id}
     */
    public function programarAction($habitacionId = null) {
        if (!$this->isPost()) {
            $this->redirect('camarista');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $habitacionId = (int) $habitacionId;

        if (!$this->tareasDisponibles()) {
            set_mensaje('El modulo de tareas no esta disponible para programar limpiezas.', 'error');
            $this->redirect('camarista');
        }

        $fecha = (string) $this->getPost('fecha', date('Y-m-d'));
        $trabajadorIds = [];
        $raw = $this->getPost('trabajador_ids', []);
        if (is_array($raw)) {
            foreach ($raw as $valor) {
                $valor = (int) $valor;
                if ($valor > 0) {
                    $trabajadorIds[$valor] = $valor;
                }
            }
        }
        $trabajadorIds = array_values($trabajadorIds);

        if (empty($trabajadorIds)) {
            set_mensaje('Selecciona al menos una persona para programar la limpieza.', 'error');
            $this->redirect('camarista');
        }

        try {
            $usuarioId = function_exists('user_id') ? user_id() : null;
            $this->tareaModel->programarLimpiezaParaHotel($hotelId, $habitacionId, $fecha, $trabajadorIds, $usuarioId);
            set_mensaje('Limpieza programada para el ' . date('d/m/Y', strtotime($fecha)) . '. 🗓️', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage() ?: 'No se pudo programar la limpieza.', 'error');
        }

        $this->redirect('camarista');
    }
}
