<?php
/**
 * Night audit interno (bloque night_audit): historial de cierres nocturnos
 * y ejecucion manual del cierre. Solo detecta y avisa; no modifica nada.
 */

require_once __DIR__ . '/../services/NightAuditService.php';

class NightAuditController extends Controller {

    /** Permiso por accion (auditoria de accesos, 23 jul 2026). */
    private const PERMISOS = [
        'index'    => 'night_audit.view',
        'ejecutar' => 'night_audit.ejecutar',
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('night_audit');
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new NightAuditService();

        $historial = $servicio->historial($hotelId, 30);
        $ultimo = $historial[0] ?? null;

        View::renderTemplate('night_audit/index', [
            'title' => 'Cierre del día - ' . current_hotel_display_name(),
            'historial' => $historial,
            'ultimo' => $ultimo,
            'hallazgosUltimo' => $ultimo ? (json_decode((string) ($ultimo['hallazgos_json'] ?? ''), true) ?: []) : [],
            'fechaSugerida' => date('Y-m-d', strtotime('-1 day')),
        ]);
    }

    public function ejecutarAction() {
        if (!$this->isPost()) {
            $this->redirect('night-audit');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $fecha = trim((string) $this->getPost('fecha', date('Y-m-d', strtotime('-1 day'))));
        $servicio = new NightAuditService();
        $resultado = $servicio->ejecutarCierre($hotelId, $fecha, user_id());

        if (!empty($resultado['success']) && !empty($resultado['creado']) && !empty($resultado['cierre'])) {
            $servicio->enviarCorreo($hotelId, $resultado['cierre'], current_hotel_display_name());
        }

        set_mensaje(
            $resultado['message'] ?? (!empty($resultado['success']) ? 'Cierre ejecutado.' : 'No se pudo ejecutar el cierre.'),
            !empty($resultado['success']) ? 'success' : 'error'
        );
        $this->redirect('night-audit');
    }
}
