<?php
/**
 * Canales iCal (bloque canales_ical): tablero interno de sincronizacion de
 * calendarios con OTAs — export .ics por habitacion + feeds importados.
 */

require_once __DIR__ . '/../services/IcalCanalesService.php';

class CanalesController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('canales_ical');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $db = Database::getInstance();
        $servicio = new IcalCanalesService($db);

        $token = $servicio->tokenExportacion($hotelId);

        $stmt = $db->query(
            "SELECT id, numero, tipo FROM habitaciones
             WHERE hotel_id = ? AND activa = 1
             ORDER BY piso, CAST(numero AS UNSIGNED)",
            [$hotelId]
        );
        $habitaciones = $stmt ? $stmt->fetchAll() : [];

        $hotel = class_exists('TenantContext') ? (TenantContext::hotel() ?: []) : [];
        $slug = (string) ($hotel['slug'] ?? '');

        View::renderTemplate('canales/index', [
            'title' => 'Airbnb y Booking - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'feeds' => $servicio->feedsDelHotel($hotelId),
            'token' => $token,
            'slug' => $slug,
        ]);
    }

    public function guardarFeedAction() {
        if (!$this->isPost()) {
            $this->redirect('canales');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new IcalCanalesService();
        $resultado = $servicio->guardarFeed(
            $hotelId,
            (int) $this->getPost('habitacion_id', 0),
            (string) $this->getPost('nombre', ''),
            (string) $this->getPost('url', '')
        );

        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'error');
        $this->redirect('canales');
    }

    public function eliminarFeedAction($feedId = null) {
        if (!$this->isPost()) {
            $this->redirect('canales');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new IcalCanalesService();
        $ok = $servicio->eliminarFeed($hotelId, (int) $feedId);

        set_mensaje(
            $ok ? 'Calendario eliminado; sus bloqueos se liberaron.' : 'No se pudo eliminar el calendario.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('canales');
    }

    public function sincronizarAction() {
        if (!$this->isPost()) {
            $this->redirect('canales');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new IcalCanalesService();
        $resultados = $servicio->sincronizarHotel($hotelId);

        $total = count($resultados);
        $fallidos = count(array_filter($resultados, function ($r) {
            return empty($r['success']);
        }));

        if ($total === 0) {
            set_mensaje('No hay calendarios que sincronizar. Agrega primero el link iCal de tu plataforma.', 'info');
        } elseif ($fallidos === 0) {
            set_mensaje("Sincronizacion completa: {$total} calendario(s) al dia.", 'success');
        } else {
            set_mensaje("Sincronizacion con problemas: {$fallidos} de {$total} calendario(s) fallaron. Revisa el detalle en la tabla.", 'warning');
        }

        $this->redirect('canales');
    }
}
