<?php
/**
 * Forecast de ocupacion (bloque forecast): proyeccion 30/60/90 dias,
 * pickup report y comparativa contra el anio anterior. Solo lectura.
 */

require_once __DIR__ . '/../services/ForecastService.php';

class ForecastController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('forecast');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new ForecastService();

        $totalHabitaciones = $servicio->habitacionesActivas($hotelId);
        $porDia = $servicio->ocupacionPorDia($hotelId, date('Y-m-d'), 90, true);

        View::renderTemplate('forecast/index', [
            'title' => 'Forecast - ' . current_hotel_display_name(),
            'totalHabitaciones' => $totalHabitaciones,
            'porDia' => $porDia,
            'kpis' => [
                'ocupacion_30' => ForecastService::promedio($porDia, 30, $totalHabitaciones),
                'ocupacion_60' => ForecastService::promedio($porDia, 60, $totalHabitaciones),
                'ocupacion_90' => ForecastService::promedio($porDia, 90, $totalHabitaciones),
            ],
            'pickup' => $servicio->pickup($hotelId),
            'semanas' => $servicio->resumenSemanal($hotelId, 12, $totalHabitaciones),
        ]);
    }
}
