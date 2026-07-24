<?php
/**
 * Check-in digital interno (bloque checkin_digital): tablero de llegadas
 * proximas, generacion de links y descarga segura de identificaciones.
 */

require_once __DIR__ . '/../services/CheckinDigitalService.php';

class CheckinDigitalController extends Controller {

    /**
     * Permiso por accion (auditoria de accesos, 23 jul 2026). Descargar la
     * identificacion del huesped (INE / pasaporte) es dato personal sensible:
     * lleva permiso propio, separado de ver el tablero, para poder darle a
     * recepcion el tablero sin darle las identificaciones si asi se decide.
     */
    private const PERMISOS = [
        'index'       => 'checkin_digital.view',
        'generar'     => 'checkin_digital.generar',
        'descargarId' => 'checkin_digital.identificacion',
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('checkin_digital');
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new CheckinDigitalService();

        View::renderTemplate('checkin_digital/index', [
            'title' => 'Check-in digital - ' . current_hotel_display_name(),
            'filas' => $servicio->tablero($hotelId),
            'slugHotel' => (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : ''),
        ]);
    }

    public function generarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('checkin-digital');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new CheckinDigitalService();
        $token = $servicio->generarLink($hotelId, (int) $id, user_id());

        set_mensaje(
            $token
                ? 'Link de pre-registro listo. Cópialo y mándaselo al huésped.'
                : 'No se pudo generar el link (verifica que la reservación siga activa).',
            $token ? 'success' : 'error'
        );
        $this->redirect('checkin-digital');
    }

    /** Descarga interna de la identificacion (storage privado, solo con sesion). */
    public function descargarIdAction($id) {
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new CheckinDigitalService();
        $ruta = $servicio->rutaIdentificacion($hotelId, (int) $id);

        if (!$ruta) {
            set_mensaje('La identificación aún no está disponible. Pídele al huésped que complete su pre-registro.', 'error');
            $this->redirect('checkin-digital');
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($ruta) : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="identificacion_reservacion_' . (int) $id . '.' . pathinfo($ruta, PATHINFO_EXTENSION) . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: private, no-store');
        readfile($ruta);
        exit;
    }
}
