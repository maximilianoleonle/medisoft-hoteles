<?php
/**
 * WhatsApp del hotel (bloque whatsapp): conexion Green API y toggles
 * de mensajes automaticos del motor de reservas.
 */

require_once __DIR__ . '/../services/WhatsAppHotelService.php';

class WhatsAppController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('whatsapp');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $db = Database::getInstance();

        $cred = null;
        try {
            $stmt = $db->query(
                "SELECT id_instance, api_host, numero_avisos, activo,
                        (api_token_encrypted IS NOT NULL AND api_token_encrypted <> '') AS token_configurado
                 FROM hotel_whatsapp_credenciales WHERE hotel_id = ? LIMIT 1",
                [$hotelId]
            );
            $cred = $stmt ? ($stmt->fetch() ?: null) : null;
        } catch (Throwable $e) {
            error_log('WhatsApp: error al leer credenciales para vista: ' . $e->getMessage());
        }

        View::renderTemplate('whatsapp/index', [
            'title' => 'WhatsApp - ' . current_hotel_display_name(),
            'cred' => $cred,
            'config' => [
                'confirmacion_huesped' => ConfiguracionHotelRegistry::getBool('whatsapp.confirmacion_huesped_activa', true, $hotelId),
                'aviso_dueno' => ConfiguracionHotelRegistry::getBool('whatsapp.aviso_dueno_activo', true, $hotelId),
            ],
        ]);
    }

    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('whatsapp');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new WhatsAppHotelService();
        $ok = $servicio->guardarCredenciales(
            $hotelId,
            (string) $this->getPost('id_instance', ''),
            (string) $this->getPost('api_token', ''),
            (string) $this->getPost('numero_avisos', ''),
            (int) $this->getPost('activo', 1) === 1
        );

        // Toggles en hotel_configuracion
        try {
            $db = Database::getInstance();
            $claves = [
                'whatsapp.confirmacion_huesped_activa' => (int) $this->getPost('confirmacion_huesped', 0) === 1 ? '1' : '0',
                'whatsapp.aviso_dueno_activo' => (int) $this->getPost('aviso_dueno', 0) === 1 ? '1' : '0',
            ];
            foreach ($claves as $clave => $valor) {
                $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, 'boolean', 'whatsapp', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()",
                    [$hotelId, $clave, $valor]
                );
            }
            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            error_log('WhatsApp: error al guardar toggles: ' . $e->getMessage());
            $ok = false;
        }

        set_mensaje($ok ? 'Configuracion de WhatsApp guardada.' : 'No se pudo guardar la configuracion de WhatsApp.', $ok ? 'success' : 'error');
        $this->redirect('whatsapp');
    }

    public function probarAction() {
        if (!$this->isPost()) {
            $this->redirect('whatsapp');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new WhatsAppHotelService();
        $ok = $servicio->enviarPrueba($hotelId, current_hotel_display_name());

        set_mensaje(
            $ok
                ? 'Mensaje de prueba enviado. Revisa el WhatsApp del numero de avisos.'
                : 'No se pudo enviar la prueba. Verifica instancia, token y numero (y que la instancia este autorizada en Green API).',
            $ok ? 'success' : 'error'
        );
        $this->redirect('whatsapp');
    }
}
