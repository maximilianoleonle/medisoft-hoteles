<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/OperacionDiaria.php';

class OperacionController extends Controller
{
    private $operacionModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->operacionModel = new OperacionDiaria();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('dashboard');
        }

        return true;
    }

    public function diariaAction(): void
    {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('operacion/diaria', [
            'title' => 'Operacion diaria - ' . current_hotel_display_name(),
            'reporte' => $this->operacionModel->reporteReadOnlyPorHotel($hotelId),
            'tablasDisponibles' => $this->operacionModel->tablasDisponibles(),
        ]);
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int) obtenerHotelIdActualCompat()
            : (int) ($_SESSION['hotel_id'] ?? 0);
    }
}

