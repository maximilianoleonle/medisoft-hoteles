<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/TareaOperativa.php';

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
}
