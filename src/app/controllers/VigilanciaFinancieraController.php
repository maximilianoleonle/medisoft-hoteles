<?php
/**
 * Vigilancia financiera (IA): informe forense sobre la conciliacion
 * determinista, con escalera de costo (plantilla $0 / Opus 4.8 / Fable 5 v2).
 *
 * Gating temporal en el bloque ia_ejecutiva mientras no exista el modulo
 * propio; al monetizar como bloque 'vigilancia_financiera' cambiar la clave
 * aqui y en config/navegacion.php.
 */

require_once __DIR__ . '/../services/VigilanciaFinancieraService.php';

class VigilanciaFinancieraController extends Controller {

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

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new VigilanciaFinancieraService();
        // regenerar=false: usa el informe del dia si ya existe; si no, lo genera
        // (nivel 1 = plantilla sin costo; nivel 2 = una llamada a Opus, cacheada).
        $resultado = $servicio->analizar($hotelId, [], false, user_id());

        // Conteos frescos de la conciliacion para las tarjetas (solo SQL, $0).
        $totales = ['error' => 0, 'warning' => 0, 'hallazgos' => 0];
        try {
            $reporte = (new ConciliacionFinanciera())->reporteReadOnlyPorHotel($hotelId, ['page' => 1, 'limit' => 50]);
            if (!empty($reporte['schema_ok'])) {
                $totales = array_merge($totales, (array) ($reporte['totales_alertas'] ?? []));
            }
        } catch (Throwable $e) {
            error_log('Vigilancia financiera (vista): error al leer conciliacion: ' . $e->getMessage());
        }

        View::renderTemplate('ia/vigilancia_financiera', [
            'title' => 'Vigilancia financiera - ' . current_hotel_display_name(),
            'resultado' => $resultado,
            'totales' => $totales,
            'configurado' => $servicio->configurado(),
        ]);
    }

    public function regenerarAction() {
        if (!$this->isPost()) {
            $this->redirect('ia/vigilancia-financiera');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new VigilanciaFinancieraService();
        $resultado = $servicio->analizar($hotelId, [], true, user_id());

        if (empty($resultado['success'])) {
            set_mensaje($resultado['message'] ?? 'No se pudo regenerar el informe.', 'error');
        } else {
            set_mensaje('Informe de vigilancia regenerado con los datos mas recientes.', 'success');
        }

        $this->redirect('ia/vigilancia-financiera');
    }
}
