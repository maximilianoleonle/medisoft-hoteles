<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../services/PwaPushService.php';
require_once __DIR__ . '/../models/PwaPushSubscription.php';

class PwaPushController extends Controller {
    private $pushService;

    public function __construct($route_params = []) {
        parent::__construct($route_params);
        $this->pushService = new PwaPushService();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        return true;
    }

    public function publicKeyAction(): void {
        View::renderJSON($this->pushService->estadoCliente($this->hotelIdActual()));
    }

    public function subscribeAction(): void {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido.'], 405);
            return;
        }

        $this->validateCSRF();
        $payload = $this->jsonBody();
        $subscription = $payload['subscription'] ?? null;

        if (!is_array($subscription)) {
            View::renderJSON(['success' => false, 'message' => 'Suscripcion invalida.'], 422);
            return;
        }

        $ok = $this->pushService->guardarSuscripcion(
            $this->hotelIdActual(),
            function_exists('user_id') ? (int)user_id() : null,
            $subscription,
            (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        View::renderJSON([
            'success' => $ok,
            'message' => $ok
                ? 'Notificaciones activadas en este dispositivo.'
                : 'No se pudo guardar la suscripcion push.',
        ], $ok ? 200 : 500);
    }

    public function unsubscribeAction(): void {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido.'], 405);
            return;
        }

        $this->validateCSRF();
        $payload = $this->jsonBody();
        $endpoint = trim((string)($payload['endpoint'] ?? ''));

        if ($endpoint === '') {
            View::renderJSON(['success' => false, 'message' => 'Endpoint requerido.'], 422);
            return;
        }

        $ok = $this->pushService->desactivarSuscripcion($this->hotelIdActual(), $endpoint);
        View::renderJSON([
            'success' => $ok,
            'message' => $ok
                ? 'Notificaciones desactivadas en este dispositivo.'
                : 'No se pudo desactivar la suscripcion.',
        ], $ok ? 200 : 500);
    }

    public function testAction(): void {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido.'], 405);
            return;
        }

        $this->validateCSRF();
        $resultado = $this->pushService->enviarPrueba($this->hotelIdActual());

        View::renderJSON([
            'success' => empty($resultado['skipped']) && (int)($resultado['sent'] ?? 0) > 0,
            'message' => empty($resultado['skipped'])
                ? 'Prueba enviada a dispositivos activos.'
                : 'Las notificaciones PWA no estan listas o no hay dispositivos activos.',
            'result' => $resultado,
        ]);
    }

    public function revocarDispositivoAction($id): void {
        if (!$this->isPost()) {
            $this->redirect('configuracion');
            return;
        }

        $this->validateCSRF();
        if (!$this->puedeGestionarDispositivos()) {
            set_mensaje('No tiene permisos para revocar dispositivos PWA.', 'error');
            $this->redirect('configuracion');
            return;
        }

        $model = new PwaPushSubscription();
        $ok = $model->desactivarPorIdHotel((int)$id, $this->hotelIdActual());

        set_mensaje(
            $ok ? 'Dispositivo PWA revocado correctamente.' : 'No se pudo revocar el dispositivo PWA.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('configuracion');
    }

    private function jsonBody(): array {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function hotelIdActual(): int {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function puedeGestionarDispositivos(): bool {
        $rolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;

        return (function_exists('is_gerente') && is_gerente())
            || in_array($rolHotel, ['gerente', 'administrador'], true);
    }
}
