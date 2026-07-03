<?php
/**
 * Webhook publico de cobros SaaS (cuenta Stripe de Medisoft, no de hoteles).
 * Verificado exclusivamente por firma (SAAS_STRIPE_WEBHOOK_SECRET); sin firma
 * valida responde 400 y no toca nada.
 */

require_once __DIR__ . '/../services/SaasCobroService.php';

class SaasWebhookController extends Controller {

    public function stripeAction() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit;
        }

        $payload = (string) file_get_contents('php://input');
        $sigHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

        $servicio = new SaasCobroService();
        $status = $servicio->procesarWebhookStripe($payload, $sigHeader);

        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['received' => $status === 200]);
        exit;
    }
}
