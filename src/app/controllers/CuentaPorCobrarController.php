<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';

class CuentaPorCobrarController extends Controller
{
    private $cuentaModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorCobrar();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('reservaciones');
        }

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado_reservacion' => $this->getQuery('estado_reservacion', 'todas'),
            'estado_saldo' => $this->getQuery('estado_saldo', 'pendiente'),
        ];

        $tablaDisponible = $this->cuentaModel->tablasDisponibles();
        $cuentas = $tablaDisponible ? $this->cuentaModel->listarDerivadasPorHotel($hotelId, $filtros, 300) : [];

        View::renderTemplate('cuentas_por_cobrar/index', [
            'title' => 'Cuentas por cobrar - ' . current_hotel_display_name(),
            'cuentas' => $cuentas,
            'resumen' => $this->cuentaModel->resumenPorCuentas($cuentas),
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }
}
