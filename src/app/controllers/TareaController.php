<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../services/AuditService.php';

class TareaController extends Controller
{
    private $tareaModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->tareaModel = new TareaOperativa();
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

            set_mensaje('Tarea operativa creada correctamente.', 'success');
            $this->redirect('tareas/' . $tareaId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear la tarea: ' . $e->getMessage(), 'error');
            $this->redirect('tareas/crear');
        }
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

    private function auditar(string $accion, int $tareaId, ?array $despues): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'tarea_operativa',
                'entidad_id' => (string)$tareaId,
                'descripcion' => 'Tarea operativa creada manualmente',
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar tarea operativa: ' . $e->getMessage());
        }
    }
}
