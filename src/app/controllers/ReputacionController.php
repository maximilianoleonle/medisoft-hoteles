<?php
/**
 * Reputacion interna (bloque reputacion): tablero de checkouts recientes,
 * generacion/envio de encuestas y configuracion del link de Google.
 */

require_once __DIR__ . '/../services/ReputacionService.php';

class ReputacionController extends Controller {

    /**
     * Permiso por accion (auditoria de accesos, 23 jul 2026). Generar y enviar
     * encuestas sale hacia el huesped: no lo autoriza el permiso de solo ver.
     */
    private const PERMISOS = [
        'index'   => 'reputacion.view',
        'generar' => 'reputacion.encuestas',
        'enviar'  => 'reputacion.encuestas',
        'config'  => 'reputacion.configurar',
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('reputacion');
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new ReputacionService();

        View::renderTemplate('reputacion/index', [
            'title' => 'Opiniones y encuestas - ' . current_hotel_display_name(),
            'kpis' => $servicio->kpis($hotelId),
            'filas' => $servicio->tablero($hotelId),
            'slugHotel' => (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : ''),
            'config' => [
                'google_review_url' => (string) ConfiguracionHotelRegistry::get('reputacion.google_review_url', '', $hotelId),
                'umbral_alerta' => ConfiguracionHotelRegistry::getInt('reputacion.umbral_alerta', 3, $hotelId),
            ],
        ]);
    }

    public function generarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('reputacion');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new ReputacionService();
        $token = $servicio->generarLink($hotelId, (int) $id, user_id());

        set_mensaje(
            $token
                ? 'Encuesta lista. Copiala o mandala al huesped por correo o WhatsApp.'
                : 'No se pudo generar la encuesta (solo aplica a reservaciones con checkout).',
            $token ? 'success' : 'error'
        );
        $this->redirect('reputacion');
    }

    public function enviarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('reputacion');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $slug = (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : '');

        $servicio = new ReputacionService();
        $encuesta = null;

        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, token FROM reputacion_encuestas WHERE hotel_id = ? AND id = ? LIMIT 1",
                [$hotelId, (int) $id]
            );
            $encuesta = $stmt ? ($stmt->fetch() ?: null) : null;
        } catch (Throwable $e) {
            error_log('Reputacion: error al leer encuesta para envio: ' . $e->getMessage());
        }

        if (!$encuesta || $slug === '') {
            set_mensaje('La encuesta no existe.', 'error');
            $this->redirect('reputacion');
        }

        $linkPublico = url('h/' . $slug . '/encuesta/' . $encuesta['token']);
        $resultado = $servicio->enviarPorCorreo($hotelId, (int) $encuesta['id'], $linkPublico, current_hotel_display_name());

        set_mensaje(
            !empty($resultado['ok'])
                ? 'Encuesta enviada por correo al huesped.'
                : ('No se pudo enviar: ' . ($resultado['error'] ?? 'error desconocido')),
            !empty($resultado['ok']) ? 'success' : 'error'
        );
        $this->redirect('reputacion');
    }

    public function configAction() {
        if (!$this->isPost()) {
            $this->redirect('reputacion');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $googleUrl = trim((string) $this->getPost('google_review_url', ''));
        if ($googleUrl !== '' && !filter_var($googleUrl, FILTER_VALIDATE_URL)) {
            set_mensaje('El link de Google no es una URL valida.', 'error');
            $this->redirect('reputacion');
        }

        $umbral = (int) $this->getPost('umbral_alerta', 3);
        $umbral = max(1, min(4, $umbral));

        $ok = true;
        try {
            $db = Database::getInstance();
            $claves = [
                'reputacion.google_review_url' => ['string', $googleUrl],
                'reputacion.umbral_alerta' => ['integer', (string) $umbral],
            ];
            foreach ($claves as $clave => [$tipo, $valor]) {
                $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'reputacion', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
                    [$hotelId, $clave, $valor, $tipo]
                );
            }
            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            error_log('Reputacion: error al guardar configuracion: ' . $e->getMessage());
            $ok = false;
        }

        set_mensaje($ok ? 'Configuracion de reputacion guardada.' : 'No se pudo guardar la configuracion.', $ok ? 'success' : 'error');
        $this->redirect('reputacion');
    }
}
