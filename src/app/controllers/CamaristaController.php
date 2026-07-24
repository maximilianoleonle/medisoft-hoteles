<?php
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../models/Area.php';

/**
 * App de camarista (bloque camarista): tablero movil de limpieza.
 * Cualquier usuario del hotel puede usarlo (la camarista entra con su usuario);
 * solo alterna limpieza <-> disponible, nunca toca ocupada ni mantenimiento.
 * Ademas registra quien hizo cada limpieza y permite programar limpiezas
 * (fecha + personal) apoyandose en tareas operativas de categoria limpieza.
 * Muestra tambien las AREAS del hotel (alberca, lobby...) con el mismo flujo:
 * mandar a limpieza / marcar limpia con personal, reusando los motores
 * *AreaParaHotel de TareaOperativa (bloque habitaciones y areas).
 */

class CamaristaController extends Controller {

    /**
     * Permiso por accion (auditoria de accesos, 23 jul 2026). Marcar y
     * programar ES el trabajo del tablero, no un extra: por eso todas las
     * acciones piden lo mismo que entrar. Se acepta cualquiera de los dos
     * permisos (any-of), igual que el menu: la pantalla de Limpieza la abre
     * tanto una camarista como quien gestiona tareas (ver config/navegacion.php).
     */
    private const PERMISOS = [
        'index'      => ['camarista.view', 'tareas.view'],
        'marcar'     => ['camarista.view', 'tareas.view'],
        'programar'  => ['camarista.view', 'tareas.view'],
        'marcarArea' => ['camarista.view', 'tareas.view'],
    ];

    /** @var TareaOperativa */
    private $tareaModel;
    /** @var Area */
    private $areaModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->tareaModel = new TareaOperativa();
        $this->areaModel = new Area();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('camarista');
        }

        $this->requirePermissionForAction(self::PERMISOS);

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

        // Ocupación vigente por cuarto (huésped + fechas): da contexto en el
        // tablero — quién está hospedado y qué día sale cada habitación ocupada.
        $stmt = $db->query(
            "SELECT rh.habitacion_id, r.fecha_entrada, r.fecha_salida, h.nombre_completo
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             INNER JOIN huespedes h ON h.id = r.huesped_id
             WHERE rh.hotel_id = ?
               AND r.estado = 'checked_in'
               AND DATE(r.fecha_entrada) <= CURDATE()
               AND DATE(r.fecha_salida) >= CURDATE()
             ORDER BY r.fecha_salida",
            [$hotelId]
        );
        $ocupacion = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $habId = (int) $fila['habitacion_id'];
            if (isset($ocupacion[$habId])) {
                continue;
            }
            $ocupacion[$habId] = [
                'huesped' => (string) $fila['nombre_completo'],
                'fecha_entrada' => (string) $fila['fecha_entrada'],
                'fecha_salida' => (string) $fila['fecha_salida'],
            ];
        }

        // Hora de salida estándar del hotel: anticipa a qué hora se liberan los
        // cuartos que salen hoy/mañana. (La hora por reserva no es confiable.)
        $horaSalida = function_exists('hotel_config_get')
            ? (string) hotel_config_get('operacion.checkout_hora', '12:00', $hotelId)
            : '12:00';

        // Personal activo + limpieza activa por cuarto o area (asignados / programadas).
        // La misma tarea que alimenta Habitaciones debe alimentar tambien las
        // tarjetas de Areas para que el equipo vea a quien le corresponde.
        $personal = $this->personalActivo($hotelId);
        $tareasPorHabitacion = [];
        $tareasPorArea = [];
        if ($this->tareasDisponibles()) {
            foreach ($this->tareaModel->listarPorHotel($hotelId, ['categoria' => 'limpieza'], 300) as $tarea) {
                $habId = (int) ($tarea['habitacion_id'] ?? 0);
                $areaId = (int) ($tarea['area_id'] ?? 0);
                $estado = (string) ($tarea['estado'] ?? '');
                if (!in_array($estado, ['pendiente', 'asignada', 'en_proceso'], true)) {
                    continue;
                }

                $resumenTarea = [
                    'id' => (int) $tarea['id'],
                    'estado' => $estado,
                    'fecha_programada' => (string) ($tarea['fecha_programada'] ?? ''),
                    'trabajador_ids' => array_values(array_filter(array_map('intval', explode(',', (string) ($tarea['trabajadores_ids'] ?? ''))))),
                    'trabajador_nombres' => (string) ($tarea['trabajadores_nombres'] ?? ''),
                ];

                if ($habId > 0 && !isset($tareasPorHabitacion[$habId])) {
                    $tareasPorHabitacion[$habId] = $resumenTarea;
                }
                if ($areaId > 0 && !isset($tareasPorArea[$areaId])) {
                    $tareasPorArea[$areaId] = $resumenTarea;
                }
            }
        }

        // Areas del hotel (bloque habitaciones y areas): solo las que le tocan a
        // limpieza (disponible/limpieza); mantenimiento/cerrada son de recepcion.
        $areas = [];
        try {
            foreach ($this->areaModel->listar($hotelId, true) as $a) {
                if (in_array((string) ($a['estado'] ?? ''), ['disponible', 'limpieza'], true)) {
                    $areas[] = $a;
                }
            }
        } catch (Throwable $e) {
            error_log('camarista: no se pudieron cargar areas: ' . $e->getMessage());
        }

        View::renderTemplate('camarista/index', [
            'title' => 'Limpieza - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'salidasHoy' => $salidasHoy,
            'ocupacion' => $ocupacion,
            'horaSalida' => $horaSalida,
            'personal' => $personal,
            'tareasLimpieza' => $tareasPorHabitacion,
            'tareasLimpiezaAreas' => $tareasPorArea,
            'areas' => $areas,
            'tiposArea' => Area::catalogoTipos(),
        ]);
    }

    /**
     * Alterna el estado de un AREA desde el tablero: limpieza <-> disponible,
     * reusando los motores del bloque areas (misma regla de personal
     * obligatorio al marcar limpia). POST /camarista/area/marcar/{id}
     */
    public function marcarAreaAction($areaId = null) {
        if (!$this->isPost()) {
            $this->redirect('camarista');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $areaId = (int) $areaId;
        $nuevoEstado = (string) $this->getPost('estado', '');

        if (!in_array($nuevoEstado, ['limpieza', 'disponible'], true)) {
            set_mensaje('Esa acción no se hace desde este tablero; pídela en recepción.', 'error');
            $this->redirect('camarista');
        }

        $area = $this->areaModel->obtenerPorId($areaId, $hotelId);
        if (!$area || (int) ($area['activa'] ?? 1) !== 1) {
            set_mensaje('Área no encontrada.', 'error');
            $this->redirect('camarista');
        }

        $estadoActual = (string) ($area['estado'] ?? '');
        if (!in_array($estadoActual, ['limpieza', 'disponible'], true)) {
            set_mensaje('Esa área está en ' . $estadoActual . '. Pídele a recepción que la libere y vuelve a intentarlo.', 'error');
            $this->redirect('camarista');
        }

        $usuarioId = function_exists('user_id') ? user_id() : null;

        // Mandar a limpieza: reusa el motor que ademas crea la tarea.
        if ($nuevoEstado === 'limpieza') {
            try {
                $this->tareaModel->iniciarLimpiezaAreaParaHotel($hotelId, $areaId, $usuarioId);
                set_mensaje('Área marcada por limpiar.', 'success');
            } catch (Throwable $e) {
                set_mensaje($e->getMessage() ?: 'No se pudo marcar el área por limpiar.', 'error');
            }
            $this->redirect('camarista');
        }

        // Marcar limpia: quien limpio es obligatorio (mismo contrato que cuartos).
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

        if (!$sinPersonal && empty($trabajadorIds) && !empty($this->personalActivo($hotelId))) {
            set_mensaje('Indica quién hizo la limpieza o marca "Sin registrar personal".', 'error');
            $this->redirect('camarista');
        }

        if ($estadoActual !== 'limpieza') {
            set_mensaje('El área no está en limpieza.', 'error');
            $this->redirect('camarista');
        }

        try {
            $this->tareaModel->completarLimpiezaConPersonalAreaParaHotel(
                $hotelId,
                $areaId,
                $sinPersonal ? [] : $trabajadorIds,
                $usuarioId
            );
            set_mensaje('Área marcada como limpia. ✨', 'success');
        } catch (Throwable $e) {
            error_log('camarista marcar limpia area #' . $areaId . ': ' . $e->getMessage());
            // Fail-open como los cuartos: al menos liberar el area.
            $this->areaModel->cambiarEstado($areaId, 'disponible', $hotelId);
            set_mensaje('Área marcada como limpia. ✨', 'success');
        }

        $this->redirect('camarista');
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
            set_mensaje('Esa acción no se hace desde este tablero; pídela en recepción.', 'error');
            $this->redirect('camarista');
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT estado FROM habitaciones WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1",
            [$habitacionId, $hotelId]
        );
        $habitacion = $stmt ? $stmt->fetch() : null;

        if (!$habitacion) {
            set_mensaje('Habitación no encontrada.', 'error');
            $this->redirect('camarista');
        }

        $estadoActual = (string) $habitacion['estado'];

        // Nunca pisar ocupada ni mantenimiento desde aqui.
        if (!in_array($estadoActual, ['limpieza', 'disponible'], true)) {
            set_mensaje('Esa habitación está en ' . ($estadoActual === 'ocupada' ? 'uso' : $estadoActual) . '. Pídele a recepción que la libere y vuelve a intentarlo.', 'error');
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
                ? ($nuevoEstado === 'disponible' ? 'Habitación marcada como limpia. ✨' : 'Habitación marcada en limpieza.')
                : 'No se pudo actualizar la habitación.',
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
            set_mensaje('Por ahora no se pueden programar limpiezas; avísale al administrador.', 'error');
            $this->redirect('camarista');
        }

        // Normalizar igual que el modelo (fecha invalida = hoy) para que el
        // mensaje de exito diga la fecha que realmente quedo guardada.
        $fechaTs = strtotime(trim((string) $this->getPost('fecha', '')));
        $fecha = date('Y-m-d', $fechaTs ?: time());
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
