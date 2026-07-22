<?php
/**
 * Huesped frecuente (bloque lealtad): ranking de huespedes que regresan y
 * cupon personal de agradecimiento (vive en motor_cupones, bloque promociones).
 */

require_once __DIR__ . '/../services/LealtadService.php';

class LealtadController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('lealtad');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new LealtadService();
        $minEstancias = max(1, ConfiguracionHotelRegistry::getInt('lealtad.min_estancias', 3, $hotelId));
        $slug = (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : '');

        View::renderTemplate('lealtad/index', [
            'title' => 'Huésped frecuente - ' . current_hotel_display_name(),
            'frecuentes' => $servicio->frecuentes($hotelId, $minEstancias),
            'config' => [
                'min_estancias' => $minEstancias,
                'descuento_pct' => ConfiguracionHotelRegistry::getInt('lealtad.descuento_pct', 10, $hotelId),
                'vigencia_dias' => ConfiguracionHotelRegistry::getInt('lealtad.vigencia_dias', 90, $hotelId),
            ],
            'urlMotor' => $slug !== '' ? url('h/' . $slug . '/reservar') : '',
            'promocionesActivo' => function_exists('hotel_menu_module_enabled') && hotel_menu_module_enabled('promociones'),
        ]);
    }

    public function generarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('lealtad');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $resultado = (new LealtadService())->generarCupon($hotelId, (int) $id, user_id());
        set_mensaje($resultado['message'], !empty($resultado['success']) ? 'success' : 'error');
        $this->redirect('lealtad');
    }

    public function enviarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('lealtad');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $slug = (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : '');
        $urlMotor = $slug !== '' ? url('h/' . $slug . '/reservar') : url('');

        $resultado = (new LealtadService())->enviarCorreo($hotelId, (int) $id, $urlMotor, current_hotel_display_name());
        set_mensaje(
            !empty($resultado['ok'])
                ? 'Cupon enviado por correo al huesped.'
                : ('No se pudo enviar: ' . ($resultado['error'] ?? 'error desconocido')),
            !empty($resultado['ok']) ? 'success' : 'error'
        );
        $this->redirect('lealtad');
    }

    public function configAction() {
        if (!$this->isPost()) {
            $this->redirect('lealtad');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $claves = [
            'lealtad.min_estancias' => (string) max(1, min(50, (int) $this->getPost('min_estancias', 3))),
            'lealtad.descuento_pct' => (string) max(1, min(100, (int) $this->getPost('descuento_pct', 10))),
            'lealtad.vigencia_dias' => (string) max(7, min(365, (int) $this->getPost('vigencia_dias', 90))),
        ];

        $ok = true;
        try {
            $db = Database::getInstance();
            foreach ($claves as $clave => $valor) {
                $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, 'integer', 'lealtad', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()",
                    [$hotelId, $clave, $valor]
                );
            }
            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            error_log('Lealtad: error al guardar configuracion: ' . $e->getMessage());
            $ok = false;
        }

        set_mensaje($ok ? 'Configuracion del programa guardada.' : 'No se pudo guardar la configuracion.', $ok ? 'success' : 'error');
        $this->redirect('lealtad');
    }
}
