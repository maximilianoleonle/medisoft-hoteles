<?php
/**
 * Mensajes (bloque canal_whatsapp): cola diaria de WhatsApp asistido.
 *
 * Recepcion ve que mensajes tocan hoy (confirmaciones nuevas, llegadas de
 * manana, salidas de hoy), los manda con un toque via wa.me y aqui queda el
 * timeline. Frontera dura: este controlador solo LEE reservaciones; su unica
 * escritura es la tabla mensajes_whatsapp (mas la configuracion del hotel).
 */

require_once __DIR__ . '/../services/CanalWhatsAppService.php';

class MensajesController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('canal_whatsapp');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new CanalWhatsAppService();

        View::renderTemplate('mensajes/index', [
            'title' => 'Mensajes - ' . current_hotel_display_name(),
            'pendientes' => $servicio->pendientesDeHoy($hotelId),
            'historial' => $servicio->historial($hotelId, 60),
            'resumen' => $servicio->resumenHoy($hotelId),
            'tiposActivos' => $servicio->tiposActivos($hotelId),
            'configFaltante' => $servicio->configuracionFaltante($hotelId),
        ]);
    }

    /** POST JSON: registra el envio y devuelve el link wa.me listo para abrir. */
    public function enviarAction() {
        if (!$this->isPost()) {
            $this->redirect('mensajes');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new CanalWhatsAppService();
        $resultado = $servicio->marcarEnviado(
            $hotelId,
            (int) $this->getPost('reservacion_id', 0),
            (string) $this->getPost('tipo', ''),
            user_id()
        );

        if ($this->isAjax()) {
            View::renderJSON($resultado);
            return;
        }

        // Camino sin JS: marca y deja el link a un toque en el aviso.
        if (!empty($resultado['success'])) {
            $link = htmlspecialchars((string) ($resultado['link'] ?? ''), ENT_QUOTES, 'UTF-8');
            set_mensaje('Mensaje marcado como enviado. <a href="' . $link . '" target="_blank" rel="noopener">Abrir WhatsApp</a>', 'success');
        } else {
            set_mensaje((string) ($resultado['motivo'] ?? 'No se pudo preparar el mensaje.'), 'error');
        }

        $this->redirect('mensajes');
    }

    /** POST JSON: descarta un mensaje de la cola (huesped sin WhatsApp, etc.). */
    public function descartarAction() {
        if (!$this->isPost()) {
            $this->redirect('mensajes');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new CanalWhatsAppService();
        $resultado = $servicio->descartar(
            $hotelId,
            (int) $this->getPost('reservacion_id', 0),
            (string) $this->getPost('tipo', ''),
            user_id(),
            (string) $this->getPost('motivo', '')
        );

        if ($this->isAjax()) {
            View::renderJSON($resultado);
            return;
        }

        set_mensaje(
            !empty($resultado['success']) ? 'Mensaje descartado.' : (string) ($resultado['motivo'] ?? 'No se pudo descartar.'),
            !empty($resultado['success']) ? 'success' : 'error'
        );
        $this->redirect('mensajes');
    }

    public function configuracionAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new CanalWhatsAppService();

        $config = [
            'confirmacion_activa' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.confirmacion_activa', true, $hotelId),
            'recordatorio_activo' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.recordatorio_activo', true, $hotelId),
            'anticipo_activo' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.anticipo_activo', true, $hotelId),
            'encuesta_activa' => ConfiguracionHotelRegistry::getBool('canal_whatsapp.encuesta_activa', true, $hotelId),
            'datos_deposito' => (string) ConfiguracionHotelRegistry::get('canal_whatsapp.datos_deposito', '', $hotelId),
            'link_maps' => (string) ConfiguracionHotelRegistry::get('canal_whatsapp.link_maps', '', $hotelId),
        ];

        foreach (CanalWhatsAppService::TIPOS as $tipo) {
            $config['plantilla_' . $tipo] = (string) ConfiguracionHotelRegistry::get('canal_whatsapp.plantilla_' . $tipo, '', $hotelId);
        }

        View::renderTemplate('mensajes/configuracion', [
            'title' => 'Mensajes · Configuración - ' . current_hotel_display_name(),
            'config' => $config,
            'plantillasSugeridas' => CanalWhatsAppService::plantillasSugeridas(),
            'reputacionActiva' => function_exists('hotel_has_module') ? (bool) hotel_has_module('reputacion', $hotelId) : false,
            'configFaltante' => $servicio->configuracionFaltante($hotelId),
        ]);
    }

    public function guardarConfiguracionAction() {
        if (!$this->isPost()) {
            $this->redirect('mensajes/configuracion');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $linkMaps = trim((string) $this->getPost('link_maps', ''));
        if ($linkMaps !== '' && !filter_var($linkMaps, FILTER_VALIDATE_URL)) {
            set_mensaje('El link de Maps no parece una URL válida. Copia el enlace completo (https://...).', 'error');
            $this->redirect('mensajes/configuracion');
        }

        $valores = [
            'canal_whatsapp.confirmacion_activa' => [(int) $this->getPost('confirmacion_activa', 0) === 1 ? '1' : '0', 'boolean'],
            'canal_whatsapp.recordatorio_activo' => [(int) $this->getPost('recordatorio_activo', 0) === 1 ? '1' : '0', 'boolean'],
            'canal_whatsapp.anticipo_activo' => [(int) $this->getPost('anticipo_activo', 0) === 1 ? '1' : '0', 'boolean'],
            'canal_whatsapp.encuesta_activa' => [(int) $this->getPost('encuesta_activa', 0) === 1 ? '1' : '0', 'boolean'],
            'canal_whatsapp.datos_deposito' => [mb_substr(trim((string) $this->getPost('datos_deposito', '')), 0, 500), 'string'],
            'canal_whatsapp.link_maps' => [mb_substr($linkMaps, 0, 300), 'string'],
        ];

        // Plantilla identica a la sugerida (o vacia) se guarda como '' para
        // que el hotel siga recibiendo mejoras de la plantilla de fabrica.
        $sugeridas = CanalWhatsAppService::plantillasSugeridas();
        foreach (CanalWhatsAppService::TIPOS as $tipo) {
            $plantilla = str_replace("\r\n", "\n", trim((string) $this->getPost('plantilla_' . $tipo, '')));
            if ($plantilla === trim($sugeridas[$tipo])) {
                $plantilla = '';
            }
            $valores['canal_whatsapp.plantilla_' . $tipo] = [mb_substr($plantilla, 0, 2000), 'string'];
        }

        $ok = true;

        try {
            $db = Database::getInstance();
            foreach ($valores as $clave => [$valor, $tipoDato]) {
                $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'canal_whatsapp', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
                    [$hotelId, $clave, $valor, $tipoDato]
                );
            }

            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            error_log('Mensajes: error al guardar configuracion: ' . $e->getMessage());
            $ok = false;
        }

        set_mensaje(
            $ok ? 'Configuración de Mensajes guardada.' : 'No se pudo guardar la configuración. Intenta de nuevo.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('mensajes/configuracion');
    }
}
