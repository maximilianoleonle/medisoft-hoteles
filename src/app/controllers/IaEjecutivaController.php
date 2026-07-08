<?php
/**
 * Asesor inteligente (bloque ia_ejecutiva): resumen gerencial diario narrado.
 */

require_once __DIR__ . '/../services/IaEjecutivaService.php';

class IaEjecutivaController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('ia_ejecutiva');
        }

        return true;
    }

    public function resumenDiarioAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $fecha = trim((string) $this->getQuery('fecha', date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || strtotime($fecha) > time()) {
            $fecha = date('Y-m-d');
        }

        $servicio = new IaEjecutivaService();
        $resultado = $servicio->resumenGerencialDiario($hotelId, $fecha, false, user_id());

        View::renderTemplate('ia/resumen_diario', [
            'title' => 'Asesor inteligente - ' . current_hotel_display_name(),
            'fecha' => $fecha,
            'resultado' => $resultado,
            'configurado' => $servicio->configurado(),
        ]);
    }

    public function regenerarResumenAction() {
        if (!$this->isPost()) {
            $this->redirect('ia/resumen-diario');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $fecha = trim((string) $this->getPost('fecha', date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $servicio = new IaEjecutivaService();
        $resultado = $servicio->resumenGerencialDiario($hotelId, $fecha, true, user_id());

        if (empty($resultado['success'])) {
            set_mensaje($resultado['message'] ?? 'No se pudo regenerar el resumen.', 'error');
        } else {
            set_mensaje('Resumen regenerado con los datos mas recientes del dia.', 'success');
        }

        $this->redirect('ia/resumen-diario?fecha=' . urlencode($fecha));
    }
}
