<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/OperacionDiaria.php';
require_once __DIR__ . '/../models/ConciliacionFinanciera.php';

class OperacionController extends Controller
{
    /**
     * Permiso por accion (auditoria de accesos, 23 jul 2026). La conciliacion
     * cruza cobros contra caja del hotel completo: va separada del tablero.
     */
    private const PERMISOS = [
        'diaria'                 => 'operacion.view',
        'conciliacionFinanciera' => 'operacion.conciliacion',
    ];

    private $operacionModel;
    private $conciliacionModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->operacionModel = new OperacionDiaria();
        $this->conciliacionModel = new ConciliacionFinanciera();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('tablero_ejecutivo');
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function diariaAction(): void
    {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('operacion/diaria', [
            'title' => 'El hotel hoy - ' . current_hotel_display_name(),
            'reporte' => $this->operacionModel->reporteReadOnlyPorHotel($hotelId),
            'tablasDisponibles' => $this->operacionModel->tablasDisponibles(),
        ]);
    }

    public function conciliacionFinancieraAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosConciliacion();

        View::renderTemplate('operacion/conciliacion_financiera', [
            'title' => 'Conciliacion financiera - ' . current_hotel_display_name(),
            'reporte' => $this->conciliacionModel->reporteReadOnlyPorHotel($hotelId, $filtros),
        ]);
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int) obtenerHotelIdActualCompat()
            : (int) ($_SESSION['hotel_id'] ?? 0);
    }

    private function filtrosConciliacion(): array
    {
        return [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'tipo' => $_GET['tipo'] ?? 'todos',
            'severidad' => $_GET['severidad'] ?? 'todos',
            'corte_id' => $_GET['corte_id'] ?? 0,
            'referencia' => $_GET['referencia'] ?? '',
            'page' => $_GET['page'] ?? 1,
            'limit' => $_GET['limit'] ?? 25,
        ];
    }
}
