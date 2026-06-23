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
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'categoria' => $this->getQuery('categoria', 'todos'),
            'estado' => $this->getQuery('estado', 'todos'),
            'prioridad' => $this->getQuery('prioridad', 'todos'),
        ];

        $tablaDisponible = $this->tareaModel->tablaDisponible();

        View::renderTemplate('tareas/index', [
            'title' => 'Tareas operativas - ' . current_hotel_display_name(),
            'tareas' => $tablaDisponible ? $this->tareaModel->listarPorHotel($hotelId, $filtros, 200) : [],
            'resumen' => $tablaDisponible ? $this->tareaModel->resumenPorHotel($hotelId) : $this->resumenVacio(),
            'filtros' => $filtros,
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
        $filtros = [
            'desde' => $this->getQuery('desde', date('Y-m-d')),
            'hasta' => $this->getQuery('hasta', date('Y-m-d')),
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
            'puedeAsignar' => $this->puedeAsignar($tarea),
            'puedeCambiarEstado' => $this->puedeCambiarEstado($tarea),
            'documentosEntidad' => $this->documentosDeTarea($id, $hotelId),
        ]);
    }

    public function crearAction(): void
    {
        $this->requireWritePermission();
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('tareas/form', [
            'title' => 'Nueva tarea operativa - ' . current_hotel_display_name(),
            'habitaciones' => $this->tareaModel->habitacionesOpciones($hotelId),
            'valores' => [
                'categoria' => 'general',
                'prioridad' => 'media',
            ],
        ]);
    }

    public function guardarAction(): void
    {
        $this->requireWritePermission();

        if (!$this->isPost()) {
            $this->redirect('tareas');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $tareaId = $this->tareaModel->crearParaHotel(
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );
            $tarea = $this->tareaModel->buscarPorIdHotel($tareaId, $hotelId);
            $this->auditar('tareas.creada', $tareaId, $tarea);

            clear_old_input();
            set_mensaje('Tarea operativa creada correctamente.', 'success');
            $this->redirect('tareas/' . $tareaId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear la tarea: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTarea([$e->getMessage()]));
            $this->redirect('tareas/crear');
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

        try {
            $antes = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new RuntimeException('Tarea no encontrada para el hotel actual.');
            }

            $trabajadorId = (int)$this->getPost('trabajador_id', 0);
            $this->tareaModel->asignarTrabajadorParaHotel($id, $hotelId, $trabajadorId, $this->usuarioIdActual());
            $despues = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('tareas.asignada', $id, $despues, $antes);

            set_mensaje('Tarea asignada correctamente.', 'success');
        } catch (Throwable $e) {
            set_mensaje('No se pudo asignar la tarea: ' . $e->getMessage(), 'error');
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

            $lower = strtolower($mensaje);
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
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function datosFormulario(): array
    {
        return [
            'titulo' => $this->getPost('titulo', ''),
            'descripcion' => $this->getPost('descripcion', ''),
            'categoria' => $this->getPost('categoria', 'general'),
            'prioridad' => $this->getPost('prioridad', 'media'),
            'habitacion_id' => $this->getPost('habitacion_id', null),
            'fecha_programada' => $this->getPost('fecha_programada', ''),
            'fecha_limite' => $this->getPost('fecha_limite', ''),
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

        try {
            $antes = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new RuntimeException('Tarea no encontrada para el hotel actual.');
            }

            $comentario = $this->getPost('comentario', '');
            $this->tareaModel->cambiarEstadoManualParaHotel(
                $id,
                $hotelId,
                $accion,
                $this->usuarioIdActual(),
                $comentario
            );
            $despues = $this->tareaModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar($accionAuditoria, $id, $despues, $antes);

            set_mensaje($mensajeExito, 'success');
        } catch (Throwable $e) {
            set_mensaje('No se pudo actualizar la tarea: ' . $e->getMessage(), 'error');
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
