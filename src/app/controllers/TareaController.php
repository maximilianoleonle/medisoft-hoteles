<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/AuditService.php';

class TareaController extends Controller
{
    private $tareaModel;
    private $documentoModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->tareaModel = new TareaOperativa();
        $this->documentoModel = new Documento();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('habitaciones');
        }

        if (function_exists('require_permission')) {
            require_permission('habitaciones.view');
        }

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->tareaModel->tablaDisponible();
        $trabajadorActual = $tablaDisponible ? $this->trabajadorActualParaHotel($hotelId) : null;
        $filtroPersonalAuto = false;
        $filtros = $this->filtrosIndexTareas($trabajadorActual, $filtroPersonalAuto);

        View::renderTemplate('tareas/index', [
            'title' => 'Tareas operativas - ' . current_hotel_display_name(),
            'tareas' => $tablaDisponible ? $this->tareaModel->listarPorHotel($hotelId, $filtros, 200) : [],
            'resumen' => $tablaDisponible ? $this->tareaModel->resumenPorHotel($hotelId) : $this->resumenVacio(),
            'filtros' => $filtros,
            'trabajadores' => $tablaDisponible ? $this->tareaModel->trabajadoresActivosOpciones($hotelId) : [],
            'trabajadorActual' => $trabajadorActual,
            'filtroPersonalAuto' => $filtroPersonalAuto,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function reporteAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->tareaModel->tablaDisponible();

        View::renderTemplate('tareas/reporte', [
            'title' => 'Reporte operativo de tareas - ' . current_hotel_display_name(),
            'reporte' => $tablaDisponible ? $this->tareaModel->reporteReadOnlyPorHotel($hotelId) : $this->reporteVacio(),
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function agendaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->tareaModel->tablaDisponible();
        $mesActualDesde = date('Y-m-01');
        $mesActualHasta = date('Y-m-t');
        $filtros = [
            'desde' => $this->getQuery('desde', $mesActualDesde),
            'hasta' => $this->getQuery('hasta', $mesActualHasta),
            'trabajador_id' => $this->getQuery('trabajador_id', 'todos'),
            'categoria' => $this->getQuery('categoria', 'todos'),
            'estado' => $this->getQuery('estado', 'activos'),
        ];

        View::renderTemplate('tareas/agenda', [
            'title' => 'Agenda de tareas - ' . current_hotel_display_name(),
            'agenda' => $tablaDisponible ? $this->tareaModel->agendaReadOnlyPorHotel($hotelId, $filtros) : $this->agendaVacia($filtros),
            'trabajadores' => $tablaDisponible ? $this->tareaModel->trabajadoresActivosOpciones($hotelId) : [],
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function verAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $tarea = $this->tareaModel->buscarPorIdHotel($id, $hotelId);

        if (!$tarea) {
            set_mensaje('Tarea no encontrada para el hotel actual.', 'error');
            $this->redirect('tareas');
            return;
        }

        View::renderTemplate('tareas/ver', [
            'title' => 'Tarea #' . $id . ' - ' . current_hotel_display_name(),
            'tarea' => $tarea,
            'eventos' => $this->tareaModel->eventosPorTarea($id, $hotelId, 50),
            'eventosDisponibles' => $this->tareaModel->eventosDisponibles(),
            'trabajadoresActivos' => $this->puedeAsignar($tarea)
                ? $this->tareaModel->trabajadoresActivosOpciones($hotelId)
                : [],
            'trabajadoresAsignados' => $this->tareaModel->trabajadoresAsignados($id, $hotelId),
            'puedeAsignar' => $this->puedeAsignar($tarea),
            'puedeCambiarEstado' => $this->puedeCambiarEstado($tarea),
            'documentosEntidad' => $this->documentosDeTarea($id, $hotelId),
        ]);
    }

    public function crearAction(): void
    {
        $this->requireWritePermission();
        $hotelId = $this->hotelIdActual();

        $habitaciones = $this->tareaModel->habitacionesOpciones($hotelId);

        $valores = [
            'categoria' => 'general',
            'prioridad' => 'media',
        ];

        // Prefill opcional desde la tarjeta de habitacion (index): ?habitacion_id=&categoria=
        $habitacionIdQuery = (int)$this->getQuery('habitacion_id', 0);
        if ($habitacionIdQuery > 0) {
            foreach ($habitaciones as $hab) {
                if ((int)($hab['id'] ?? 0) === $habitacionIdQuery) {
                    $valores['habitacion_id'] = $habitacionIdQuery;
                    break;
                }
            }
        }

        $categoriaQuery = trim((string)$this->getQuery('categoria', ''));
        if (in_array($categoriaQuery, ['limpieza', 'mantenimiento', 'general'], true)) {
            $valores['categoria'] = $categoriaQuery;
        }

        View::renderTemplate('tareas/form', [
            'title' => 'Nueva tarea operativa - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'trabajadores' => $this->tareaModel->trabajadoresActivosOpciones($hotelId),
            'valores' => $valores,
        ]);
    }

    /**
     * Endpoint read-only (JSON) para el panel "Tareas del cuarto" del index de
     * Habitaciones. Reutiliza listarPorEntidadHotel y respeta el hotel activo
     * (la verificacion de permiso/ hotel ya ocurre en before()).
     */
    public function porHabitacionAction(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        if ($id <= 0 || $hotelId <= 0) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        if (!$this->tareaModel->tablaDisponible()) {
            echo json_encode(['ok' => true, 'tareas' => []]);
            return;
        }

        $tareas = $this->tareaModel->listarPorEntidadHotel($hotelId, 'habitacion', $id, 12);

        $items = [];
        foreach ($tareas as $t) {
            $trabajadoresTexto = trim((string)($t['trabajadores_nombres'] ?? ($t['trabajadores_asignados'] ?? '')));
            if ($trabajadoresTexto === '') {
                $trabajadoresTexto = trim((string)($t['trabajador_nombre'] ?? ''));
            }

            $items[] = [
                'id' => (int)($t['id'] ?? 0),
                'titulo' => (string)($t['titulo'] ?? ''),
                'categoria' => (string)($t['categoria'] ?? 'general'),
                'estado' => (string)($t['estado'] ?? 'pendiente'),
                'prioridad' => (string)($t['prioridad'] ?? 'media'),
                'trabajador_nombre' => $trabajadoresTexto !== '' ? $trabajadoresTexto : null,
                'trabajadores_nombres' => $trabajadoresTexto !== '' ? $trabajadoresTexto : null,
                'fecha_limite' => $t['fecha_limite'] ?? null,
                'habitacion_numero' => $t['habitacion_numero'] ?? null,
            ];
        }

        echo json_encode(['ok' => true, 'tareas' => $items]);
    }

    public function guardarAction(): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('tareas');
            return;
        }

        $this->validateCSRF();

        $formularios = [];
        try {
            $hotelId = $this->hotelIdActual();
            $formularios = $this->datosFormularioCreacionMultiple();
            $tareaIds = $this->tareaModel->crearVariasParaHotel(
                $hotelId,
                $formularios,
                $this->usuarioIdActual()
            );

            foreach ($tareaIds as $tareaId) {
                $tarea = $this->tareaModel->buscarPorIdHotel((int)$tareaId, $hotelId);
                $this->auditar('tareas.creada', (int)$tareaId, $tarea);
            }

            clear_old_input();
            if (count($tareaIds) === 1) {
                set_mensaje('Tarea operativa creada correctamente.', 'success');
                $this->redirect('tareas/' . (int)$tareaIds[0]);
            } else {
                set_mensaje('Se crearon ' . count($tareaIds) . ' tareas correctamente.', 'success');
                $this->redirect('tareas');
            }
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear la tarea: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors(count($formularios) > 1
                ? ['_global' => [$e->getMessage()]]
                : $this->erroresCamposTarea([$e->getMessage()])
            );
            $this->redirect('tareas/crear');
        }
    }

    public function editarAction(): void
    {
        $this->requireWritePermission();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $tarea = $this->tareaModel->buscarPorIdHotel($id, $hotelId);

        if (!$tarea) {
            set_mensaje('Tarea no encontrada para el hotel actual.', 'error');
            $this->redirect('tareas');
            return;
        }

        $trabajadoresAsignados = $this->tareaModel->trabajadoresAsignados($id, $hotelId);

        View::renderTemplate('tareas/form', [
            'title' => 'Editar tarea #' . $id . ' - ' . current_hotel_display_name(),
            'modo' => 'editar',
            'tarea' => $tarea,
            'habitaciones' => $this->tareaModel->habitacionesOpciones($hotelId),
            'trabajadores' => $this->tareaModel->trabajadoresActivosOpciones($hotelId),
            'valores' => $this->valoresFormularioDesdeTarea($tarea, $trabajadoresAsignados),
        ]);
    }

    public function actualizarAction(): void
    {
        $this->requireWritePermission();

        $id = (int)($this->route_params['id'] ?? 0);
        if (!$this->isPost()) {
            $this->redirect($id > 0 ? 'tareas/' . $id . '/editar' : 'tareas');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $antes = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new RuntimeException('Tarea no encontrada para el hotel actual.');
            }

            $this->tareaModel->actualizarParaHotel(
                $id,
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );

            $despues = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('tareas.actualizada', $id, $despues, $antes);

            clear_old_input();
            set_mensaje('Tarea actualizada correctamente.', 'success');
            $this->redirect('tareas/' . $id);
        } catch (Throwable $e) {
            set_mensaje('No se pudo actualizar la tarea: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTarea([$e->getMessage()]));
            $this->redirect($id > 0 ? 'tareas/' . $id . '/editar' : 'tareas');
        }
    }

    public function crearDesdeMantenimientoAction(): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('reportes/mantenimiento-programado');
            return;
        }

        $this->validateCSRF();

        $mantenimientoId = (int)($this->route_params['id'] ?? 0);
        $dias = (int)$this->getPost('dias', 30);
        $dias = max(0, min(90, $dias));

        try {
            $hotelId = $this->hotelIdActual();
            $tareaId = $this->tareaModel->crearDesdeMantenimientoParaHotel(
                $hotelId,
                $mantenimientoId,
                [],
                $this->usuarioIdActual()
            );
            $tarea = $this->tareaModel->buscarPorIdHotel($tareaId, $hotelId);
            $this->auditar('tareas.creada_desde_mantenimiento', $tareaId, $tarea);

            set_mensaje('Tarea vinculada al mantenimiento creada correctamente.', 'success');
            $this->redirect('tareas/' . $tareaId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear la tarea desde mantenimiento: ' . $e->getMessage(), 'error');
            $this->redirect('reportes/mantenimiento-programado?dias=' . $dias);
        }
    }

    public function crearDesdeLimpiezaAction(): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('reportes/limpieza');
            return;
        }

        $this->validateCSRF();

        $habitacionId = (int)($this->route_params['id'] ?? 0);

        try {
            $hotelId = $this->hotelIdActual();
            $tareaId = $this->tareaModel->crearDesdeLimpiezaHabitacionParaHotel(
                $hotelId,
                $habitacionId,
                [],
                $this->usuarioIdActual()
            );
            $tarea = $this->tareaModel->buscarPorIdHotel($tareaId, $hotelId);
            $this->auditar('tareas.creada_desde_limpieza', $tareaId, $tarea);

            set_mensaje('Tarea de limpieza creada correctamente.', 'success');
            $this->redirect('tareas/' . $tareaId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear la tarea de limpieza: ' . $e->getMessage(), 'error');
            $this->redirect($habitacionId > 0 ? 'habitaciones/' . $habitacionId : 'reportes/limpieza');
        }
    }

    public function asignarAction(): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('tareas');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        $trabajadorIds = $this->getPost('trabajador_ids', []);
        if (!is_array($trabajadorIds)) {
            $trabajadorIds = $trabajadorIds !== '' && $trabajadorIds !== null ? [$trabajadorIds] : [];
        }
        // Compatibilidad con el campo simple anterior (trabajador_id).
        $trabajadorIdSimple = (int)$this->getPost('trabajador_id', 0);
        if ($trabajadorIdSimple > 0) {
            $trabajadorIds[] = $trabajadorIdSimple;
        }

        try {
            $antes = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new RuntimeException('Tarea no encontrada para el hotel actual.');
            }

            $this->tareaModel->asignarTrabajadoresParaHotel($id, $hotelId, $trabajadorIds, $this->usuarioIdActual());
            $despues = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('tareas.asignada', $id, $despues, $antes);

            clear_old_input();
            set_mensaje('Tarea asignada correctamente.', 'success');
        } catch (Throwable $e) {
            set_mensaje('No se pudo asignar la tarea: ' . $e->getMessage(), 'error');
            save_old_input([
                'trabajador_ids' => array_values(array_map('strval', $trabajadorIds)),
                'tarea_form_action' => 'asignar',
            ]);
            save_form_errors($this->erroresCamposTarea([$e->getMessage()]));
        }

        $this->redirect($id > 0 ? 'tareas/' . $id : 'tareas');
    }

    public function iniciarAction(): void
    {
        $this->cambiarEstadoManual('iniciar', 'Tarea iniciada correctamente.', 'tareas.iniciada');
    }

    public function completarAction(): void
    {
        $this->cambiarEstadoManual('completar', 'Tarea completada correctamente.', 'tareas.completada');
    }

    public function cancelarAction(): void
    {
        $this->cambiarEstadoManual('cancelar', 'Tarea cancelada correctamente.', 'tareas.cancelada');
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'pendiente' => 0,
            'asignada' => 0,
            'en_proceso' => 0,
            'completada' => 0,
            'cancelada' => 0,
            'limpieza' => 0,
            'mantenimiento' => 0,
            'general' => 0,
        ];
    }

    private function reporteVacio(): array
    {
        return [
            'resumen' => $this->resumenVacio(),
            'prioridades' => [
                'baja' => 0,
                'media' => 0,
                'alta' => 0,
                'urgente' => 0,
            ],
            'riesgos' => [
                'vencidas' => 0,
                'proximas_24h' => 0,
                'sin_asignar_activas' => 0,
            ],
            'por_trabajador' => [],
            'por_habitacion' => [],
            'recientes' => [],
            'eventos_recientes' => [],
        ];
    }

    private function agendaVacia(array $filtros): array
    {
        return [
            'filtros' => $filtros,
            'resumen' => [
                'total' => 0,
                'activas' => 0,
                'sin_asignar' => 0,
                'por_estado' => [],
                'por_categoria' => [],
                'por_trabajador' => [],
            ],
            'tareas' => [],
        ];
    }

    private function documentosDeTarea(int $tareaId, int $hotelId): array
    {
        if ($tareaId <= 0 || $hotelId <= 0) {
            return [];
        }

        return $this->documentoModel->documentosPorTareaHotel($hotelId, $tareaId, 50);
    }

    private function erroresCamposTarea(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = function_exists('mb_strtolower') ? mb_strtolower($mensaje, 'UTF-8') : strtolower($mensaje);
            $lower = strtr($lower, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'Á' => 'a',
                'É' => 'e',
                'Í' => 'i',
                'Ó' => 'o',
                'Ú' => 'u',
            ]);
            $campo = null;

            if (strpos($lower, 'titulo') !== false) {
                $campo = 'titulo';
            } elseif (strpos($lower, 'descripcion') !== false) {
                $campo = 'descripcion';
            } elseif (strpos($lower, 'categoria') !== false) {
                $campo = 'categoria';
            } elseif (strpos($lower, 'prioridad') !== false) {
                $campo = 'prioridad';
            } elseif (strpos($lower, 'habitacion') !== false) {
                $campo = 'habitacion_id';
            } elseif (strpos($lower, 'fecha limite') !== false || strpos($lower, 'limite') !== false || strpos($lower, 'anterior') !== false) {
                $campo = 'fecha_limite';
            } elseif (strpos($lower, 'fecha programada') !== false || strpos($lower, 'programada') !== false || strpos($lower, 'fecha') !== false) {
                $campo = 'fecha_programada';
            } elseif (strpos($lower, 'trabajador') !== false || strpos($lower, 'asignacion') !== false || strpos($lower, 'asignar') !== false) {
                $campo = 'trabajador_id';
            } elseif (strpos($lower, 'comentario') !== false || strpos($lower, 'motivo') !== false || strpos($lower, 'nota') !== false || strpos($lower, 'cierre') !== false) {
                $campo = 'comentario';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            } else {
                $fieldErrors['_global'][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function datosFormulario(): array
    {
        $trabajadorIds = $this->getPost('trabajador_ids', []);
        if (!is_array($trabajadorIds)) {
            $trabajadorIds = $trabajadorIds !== '' && $trabajadorIds !== null ? [$trabajadorIds] : [];
        }

        return [
            'titulo' => $this->getPost('titulo', ''),
            'descripcion' => $this->getPost('descripcion', ''),
            'categoria' => $this->getPost('categoria', 'general'),
            'prioridad' => $this->getPost('prioridad', 'media'),
            'habitacion_id' => $this->getPost('habitacion_id', null),
            'fecha_programada' => $this->getPost('fecha_programada', ''),
            'fecha_limite' => $this->getPost('fecha_limite', ''),
            'trabajador_ids' => $trabajadorIds,
        ];
    }

    private function datosFormularioCreacionMultiple(): array
    {
        $formularios = [$this->datosFormulario()];
        $extras = $this->getPost('tareas_extra', []);

        if (!is_array($extras)) {
            return $formularios;
        }

        foreach ($extras as $extra) {
            if (!is_array($extra) || !$this->tareaExtraTieneContenido($extra)) {
                continue;
            }

            $formularios[] = $this->datosTareaDesdeArray($extra);
        }

        return $formularios;
    }

    private function datosTareaDesdeArray(array $datos): array
    {
        $trabajadorIds = $datos['trabajador_ids'] ?? [];
        if (!is_array($trabajadorIds)) {
            $trabajadorIds = $trabajadorIds !== '' && $trabajadorIds !== null ? [$trabajadorIds] : [];
        }

        return [
            'titulo' => $datos['titulo'] ?? '',
            'descripcion' => $datos['descripcion'] ?? '',
            'categoria' => $datos['categoria'] ?? 'general',
            'prioridad' => $datos['prioridad'] ?? 'media',
            'habitacion_id' => $datos['habitacion_id'] ?? null,
            'fecha_programada' => $datos['fecha_programada'] ?? '',
            'fecha_limite' => $datos['fecha_limite'] ?? '',
            'trabajador_ids' => $trabajadorIds,
        ];
    }

    private function tareaExtraTieneContenido(array $datos): bool
    {
        foreach (['titulo', 'descripcion', 'habitacion_id', 'fecha_programada', 'fecha_limite'] as $campo) {
            if (trim((string)($datos[$campo] ?? '')) !== '') {
                return true;
            }
        }

        $trabajadorIds = $datos['trabajador_ids'] ?? [];
        if (!is_array($trabajadorIds)) {
            $trabajadorIds = $trabajadorIds !== '' && $trabajadorIds !== null ? [$trabajadorIds] : [];
        }

        foreach ($trabajadorIds as $trabajadorId) {
            if ((int)$trabajadorId > 0) {
                return true;
            }
        }

        return false;
    }

    private function valoresFormularioDesdeTarea(array $tarea, array $trabajadoresAsignados): array
    {
        $trabajadorIds = [];
        foreach ($trabajadoresAsignados as $asignado) {
            $trabajadorId = (int)($asignado['trabajador_id'] ?? 0);
            if ($trabajadorId > 0) {
                $trabajadorIds[] = $trabajadorId;
            }
        }

        if (empty($trabajadorIds) && !empty($tarea['trabajador_id'])) {
            $trabajadorIds[] = (int)$tarea['trabajador_id'];
        }

        return [
            'id' => (int)($tarea['id'] ?? 0),
            'titulo' => (string)($tarea['titulo'] ?? ''),
            'descripcion' => (string)($tarea['descripcion'] ?? ''),
            'categoria' => (string)($tarea['categoria'] ?? 'general'),
            'prioridad' => (string)($tarea['prioridad'] ?? 'media'),
            'habitacion_id' => $tarea['habitacion_id'] ?? '',
            'fecha_programada' => $tarea['fecha_programada'] ?? '',
            'fecha_limite' => $tarea['fecha_limite'] ?? '',
            'trabajador_ids' => $trabajadorIds,
        ];
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function usuarioIdActual(): ?int
    {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }

    private function filtrosIndexTareas(?array $trabajadorActual, bool &$filtroPersonalAuto): array
    {
        $filtroPersonalAuto = false;
        $hayFiltroManual = false;
        foreach (['buscar', 'categoria', 'estado', 'prioridad', 'trabajador_id', 'fecha_desde', 'fecha_hasta', 'filtros'] as $campo) {
            if (array_key_exists($campo, $_GET)) {
                $hayFiltroManual = true;
                break;
            }
        }

        if (!$hayFiltroManual && !empty($trabajadorActual['id'])) {
            $hoy = date('Y-m-d');
            $filtroPersonalAuto = true;

            return [
                'buscar' => '',
                'categoria' => 'todos',
                'estado' => 'todos',
                'prioridad' => 'todos',
                'trabajador_id' => (string)(int)$trabajadorActual['id'],
                'fecha_desde' => $hoy,
                'fecha_hasta' => $hoy,
            ];
        }

        return [
            'buscar' => $this->getQuery('buscar', ''),
            'categoria' => $this->getQuery('categoria', 'todos'),
            'estado' => $this->getQuery('estado', 'todos'),
            'prioridad' => $this->getQuery('prioridad', 'todos'),
            'trabajador_id' => $this->getQuery('trabajador_id', 'todos'),
            'fecha_desde' => $this->getQuery('fecha_desde', ''),
            'fecha_hasta' => $this->getQuery('fecha_hasta', ''),
        ];
    }

    private function trabajadorActualParaHotel(int $hotelId): ?array
    {
        $usuarioId = $this->usuarioIdActual();
        if ($hotelId <= 0 || !$usuarioId) {
            return null;
        }

        try {
            $stmt = Database::getInstance()->query(
                "SELECT id, nombre_completo, rol_laboral
                 FROM trabajadores
                 WHERE hotel_id = ?
                   AND usuario_id = ?
                   AND estado = 'activo'
                 ORDER BY id ASC
                 LIMIT 1",
                [$hotelId, $usuarioId]
            );

            $trabajador = $stmt ? $stmt->fetch() : null;
            return $trabajador ?: null;
        } catch (Throwable $e) {
            error_log('No se pudo resolver trabajador del usuario para tareas: ' . $e->getMessage());
            return null;
        }
    }

    private function requireWritePermission(): void
    {
        if (function_exists('require_permission')) {
            require_permission('habitaciones.mantenimiento');
        }
    }

    private function cambiarEstadoManual(string $accion, string $mensajeExito, string $accionAuditoria): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('tareas');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $comentario = $this->getPost('comentario', '');

        try {
            $antes = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new RuntimeException('Tarea no encontrada para el hotel actual.');
            }

            $this->tareaModel->cambiarEstadoManualParaHotel(
                $id,
                $hotelId,
                $accion,
                $this->usuarioIdActual(),
                $comentario
            );
            $despues = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar($accionAuditoria, $id, $despues, $antes);

            clear_old_input();
            set_mensaje($mensajeExito, 'success');
        } catch (Throwable $e) {
            set_mensaje('No se pudo actualizar la tarea: ' . $e->getMessage(), 'error');
            save_old_input([
                'comentario' => $comentario,
                'tarea_form_action' => $accion,
            ]);
            save_form_errors($this->erroresCamposTarea([$e->getMessage()]));
        }

        $this->redirect($id > 0 ? 'tareas/' . $id : 'tareas');
    }

    private function puedeAsignar(array $tarea): bool
    {
        $estado = (string)($tarea['estado'] ?? '');
        return function_exists('can')
            && can('habitaciones.mantenimiento')
            && in_array($estado, ['pendiente', 'asignada'], true);
    }

    private function puedeCambiarEstado(array $tarea): bool
    {
        $estado = (string)($tarea['estado'] ?? '');
        return function_exists('can')
            && can('habitaciones.mantenimiento')
            && in_array($estado, ['pendiente', 'asignada', 'en_proceso'], true);
    }

    private function auditar(string $accion, int $tareaId, ?array $despues, ?array $antes = null): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'tarea_operativa',
                'entidad_id' => (string)$tareaId,
                'descripcion' => 'Cambio en tarea operativa',
                'datos_antes' => $antes,
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar tarea operativa: ' . $e->getMessage());
        }
    }
}
