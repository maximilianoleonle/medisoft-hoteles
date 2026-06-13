<?php

require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/PwaPushService.php';

class NotificacionService {
    public static function crear(array $datos): ?int {
        try {
            $hotelId = (int)($datos['hotel_id'] ?? 0);
            if ($hotelId <= 0 && function_exists('obtenerHotelIdActualCompat')) {
                $hotelId = (int) obtenerHotelIdActualCompat();
            }

            if ($hotelId <= 0) {
                return null;
            }

            $datos['hotel_id'] = $hotelId;

            $modelo = new Notificacion();
            $id = $modelo->crearEvento($datos);

            if ($id && $modelo->ultimoEventoFueCreado()) {
                try {
                    (new PwaPushService())->enviarNotificacion($datos, (int)$id);
                } catch (Throwable $e) {
                    error_log('No se pudo enviar push PWA de notificacion: ' . $e->getMessage());
                }
            }

            return $id;
        } catch (Throwable $e) {
            error_log('No se pudo crear notificacion: ' . $e->getMessage());
            return null;
        }
    }
}
