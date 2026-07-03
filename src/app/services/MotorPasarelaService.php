<?php

/**
 * MotorPasarelaService
 *
 * Integracion directa (REST + curl, sin SDK) con Stripe y MercadoPago para
 * el anticipo online del motor de reservas. Credenciales por hotel en
 * hotel_pasarela_credenciales con secrets cifrados AES-256-GCM usando la
 * llave de entorno MOTOR_PASARELA_KEY.
 *
 * Este servicio NO toca Caja ni reservaciones: solo habla con la pasarela.
 */
class MotorPasarelaService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    // ───────────────────────── Credenciales ─────────────────────────

    /** Credenciales activas del hotel con secrets ya descifrados, o null. */
    public function credenciales(int $hotelId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM hotel_pasarela_credenciales WHERE hotel_id = ? AND activo = 1 LIMIT 1",
                [$hotelId]
            );
            $row = $stmt ? $stmt->fetch() : null;
            if (!$row) {
                error_log('DEBUG pasarela: sin fila para hotel ' . $hotelId); // TEMP
                return null;
            }

            $row['secret_key'] = self::descifrar((string) ($row['secret_key_encrypted'] ?? ''));
            $row['webhook_secret'] = self::descifrar((string) ($row['webhook_secret_encrypted'] ?? ''));
            unset($row['secret_key_encrypted'], $row['webhook_secret_encrypted']);

            if ($row['secret_key'] === null || $row['secret_key'] === '') {
                error_log('DEBUG pasarela: descifrado nulo; key_env=' . var_export(getenv('MOTOR_PASARELA_KEY'), true)); // TEMP
                return null;
            }

            return $row;
        } catch (Throwable $e) {
            error_log('Motor pasarela: error al leer credenciales del hotel ' . $hotelId . ': ' . $e->getMessage());
            return null;
        }
    }

    public function guardarCredenciales(int $hotelId, string $proveedor, string $publicKey, ?string $secretKey, ?string $webhookSecret, string $modo, bool $activo): bool
    {
        $proveedor = in_array($proveedor, ['stripe', 'mercadopago'], true) ? $proveedor : 'stripe';
        $modo = $modo === 'live' ? 'live' : 'test';

        try {
            $actual = $this->db->query(
                "SELECT secret_key_encrypted, webhook_secret_encrypted FROM hotel_pasarela_credenciales WHERE hotel_id = ? LIMIT 1",
                [$hotelId]
            );
            $fila = $actual ? $actual->fetch() : null;

            // Secret vacio en el form = conservar el guardado (nunca se re-muestra).
            $secretEnc = ($secretKey !== null && trim($secretKey) !== '')
                ? self::cifrar(trim($secretKey))
                : ($fila['secret_key_encrypted'] ?? null);
            $webhookEnc = ($webhookSecret !== null && trim($webhookSecret) !== '')
                ? self::cifrar(trim($webhookSecret))
                : ($fila['webhook_secret_encrypted'] ?? null);

            $stmt = $this->db->query(
                "INSERT INTO hotel_pasarela_credenciales
                    (hotel_id, proveedor, public_key, secret_key_encrypted, webhook_secret_encrypted, modo, activo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    proveedor = VALUES(proveedor),
                    public_key = VALUES(public_key),
                    secret_key_encrypted = VALUES(secret_key_encrypted),
                    webhook_secret_encrypted = VALUES(webhook_secret_encrypted),
                    modo = VALUES(modo),
                    activo = VALUES(activo),
                    updated_at = NOW()",
                [$hotelId, $proveedor, trim($publicKey), $secretEnc, $webhookEnc, $modo, $activo ? 1 : 0]
            );

            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('Motor pasarela: error al guardar credenciales del hotel ' . $hotelId . ': ' . $e->getMessage());
            return false;
        }
    }

    // ───────────────────────── Checkout ─────────────────────────

    /**
     * Crea la sesion de pago del anticipo.
     * $pago: monto (float), moneda, concepto, hold_token, email.
     * $urls: success, cancel.
     * Devuelve ['ok', 'checkout_url', 'proveedor', 'proveedor_pago_id', 'error'].
     */
    public function crearCheckout(int $hotelId, array $pago, array $urls): array
    {
        $cred = $this->credenciales($hotelId);
        if (!$cred) {
            return ['ok' => false, 'error' => 'El hotel no tiene pasarela de pago configurada.'];
        }

        if (($cred['proveedor'] ?? '') === 'mercadopago') {
            return $this->checkoutMercadoPago($cred, $pago, $urls);
        }

        return $this->checkoutStripe($cred, $pago, $urls);
    }

    private function checkoutStripe(array $cred, array $pago, array $urls): array
    {
        $params = [
            'mode' => 'payment',
            'client_reference_id' => (string) $pago['hold_token'],
            'customer_email' => (string) ($pago['email'] ?? ''),
            'success_url' => (string) $urls['success'],
            'cancel_url' => (string) $urls['cancel'],
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => strtolower((string) ($pago['moneda'] ?? 'mxn')),
            'line_items[0][price_data][unit_amount]' => (int) round(((float) $pago['monto']) * 100),
            'line_items[0][price_data][product_data][name]' => (string) $pago['concepto'],
            'metadata[hold_token]' => (string) $pago['hold_token'],
            'payment_intent_data[metadata][hold_token]' => (string) $pago['hold_token'],
        ];

        $res = $this->http('POST', 'https://api.stripe.com/v1/checkout/sessions', [
            'Authorization: Bearer ' . $cred['secret_key'],
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query($params));

        if (!$res['ok'] || empty($res['json']['id']) || empty($res['json']['url'])) {
            $detalle = $res['json']['error']['message'] ?? ('HTTP ' . $res['status']);
            error_log('Motor pasarela: Stripe checkout fallo: ' . $detalle);
            return ['ok' => false, 'error' => 'No se pudo iniciar el pago con la pasarela.'];
        }

        return [
            'ok' => true,
            'checkout_url' => (string) $res['json']['url'],
            'proveedor' => 'stripe',
            'proveedor_pago_id' => (string) $res['json']['id'],
        ];
    }

    private function checkoutMercadoPago(array $cred, array $pago, array $urls): array
    {
        $body = [
            'items' => [[
                'title' => (string) $pago['concepto'],
                'quantity' => 1,
                'currency_id' => strtoupper((string) ($pago['moneda'] ?? 'MXN')),
                'unit_price' => round((float) $pago['monto'], 2),
            ]],
            'external_reference' => (string) $pago['hold_token'],
            'payer' => ['email' => (string) ($pago['email'] ?? '')],
            'back_urls' => [
                'success' => (string) $urls['success'],
                'failure' => (string) $urls['cancel'],
                'pending' => (string) $urls['success'],
            ],
            'auto_return' => 'approved',
        ];

        $res = $this->http('POST', 'https://api.mercadopago.com/checkout/preferences', [
            'Authorization: Bearer ' . $cred['secret_key'],
            'Content-Type: application/json',
        ], json_encode($body, JSON_UNESCAPED_UNICODE));

        if (!$res['ok'] || empty($res['json']['id'])) {
            $detalle = $res['json']['message'] ?? ('HTTP ' . $res['status']);
            error_log('Motor pasarela: MercadoPago checkout fallo: ' . $detalle);
            return ['ok' => false, 'error' => 'No se pudo iniciar el pago con la pasarela.'];
        }

        $url = ($cred['modo'] ?? 'test') === 'live'
            ? ($res['json']['init_point'] ?? '')
            : ($res['json']['sandbox_init_point'] ?? $res['json']['init_point'] ?? '');

        return [
            'ok' => true,
            'checkout_url' => (string) $url,
            'proveedor' => 'mercadopago',
            'proveedor_pago_id' => (string) $res['json']['id'],
        ];
    }

    // ───────────────────────── Webhooks ─────────────────────────

    /**
     * Verifica firma Stripe (header Stripe-Signature, esquema t=..,v1=..) y devuelve
     * el evento decodificado, o null si la firma es invalida.
     */
    public function verificarWebhookStripe(string $payload, string $sigHeader, string $webhookSecret): ?array
    {
        $partes = [];
        foreach (explode(',', $sigHeader) as $par) {
            $kv = explode('=', trim($par), 2);
            if (count($kv) === 2) {
                $partes[$kv[0]][] = $kv[1];
            }
        }

        $timestamp = (int) ($partes['t'][0] ?? 0);
        if ($timestamp <= 0 || abs(time() - $timestamp) > 300) {
            return null;
        }

        $esperada = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);
        $valida = false;
        foreach ($partes['v1'] ?? [] as $firma) {
            if (hash_equals($esperada, $firma)) {
                $valida = true;
                break;
            }
        }
        if (!$valida) {
            return null;
        }

        $evento = json_decode($payload, true);
        return is_array($evento) ? $evento : null;
    }

    /**
     * MercadoPago no firma con secreto compartido simple: la verificacion es
     * consultar el pago directamente a su API con el access token del hotel.
     */
    public function consultarPagoMercadoPago(string $paymentId, string $accessToken): ?array
    {
        $res = $this->http('GET', 'https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId), [
            'Authorization: Bearer ' . $accessToken,
        ]);

        return ($res['ok'] && is_array($res['json'])) ? $res['json'] : null;
    }

    /** Reembolso best-effort cuando la habitacion ya no esta disponible. */
    public function reembolsar(array $cred, string $referenciaPago): bool
    {
        try {
            if (($cred['proveedor'] ?? '') === 'mercadopago') {
                $res = $this->http('POST', 'https://api.mercadopago.com/v1/payments/' . rawurlencode($referenciaPago) . '/refunds', [
                    'Authorization: Bearer ' . $cred['secret_key'],
                    'Content-Type: application/json',
                    'X-Idempotency-Key: refund-' . $referenciaPago,
                ], '{}');
                return $res['ok'];
            }

            // Stripe: referencia = payment_intent
            $res = $this->http('POST', 'https://api.stripe.com/v1/refunds', [
                'Authorization: Bearer ' . $cred['secret_key'],
                'Content-Type: application/x-www-form-urlencoded',
            ], http_build_query(['payment_intent' => $referenciaPago]));
            return $res['ok'];
        } catch (Throwable $e) {
            error_log('Motor pasarela: reembolso fallo (' . $referenciaPago . '): ' . $e->getMessage());
            return false;
        }
    }

    // ───────────────────────── Cifrado y HTTP ─────────────────────────

    public static function cifrar(string $texto): ?string
    {
        $llave = self::llave();
        if ($llave === null || $texto === '') {
            return null;
        }

        $iv = random_bytes(12);
        $tag = '';
        $cifrado = openssl_encrypt($texto, 'aes-256-gcm', $llave, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cifrado === false) {
            return null;
        }

        return base64_encode($iv . $tag . $cifrado);
    }

    public static function descifrar(string $blob): ?string
    {
        $llave = self::llave();
        if ($llave === null || $blob === '') {
            return null;
        }

        $raw = base64_decode($blob, true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cifrado = substr($raw, 28);
        $texto = openssl_decrypt($cifrado, 'aes-256-gcm', $llave, OPENSSL_RAW_DATA, $iv, $tag);

        return $texto === false ? null : $texto;
    }

    private static function llave(): ?string
    {
        $b64 = (string) (getenv('MOTOR_PASARELA_KEY') ?: '');
        if ($b64 === '') {
            error_log('Motor pasarela: MOTOR_PASARELA_KEY no esta configurada en el entorno.');
            return null;
        }

        $llave = base64_decode($b64, true);
        return ($llave !== false && strlen($llave) === 32) ? $llave : hash('sha256', $b64, true);
    }

    private function http(string $metodo, string $url, array $headers, ?string $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($ch);
        curl_close($ch);

        if ($respuesta === false) {
            error_log('Motor pasarela: error curl contra ' . parse_url($url, PHP_URL_HOST) . ': ' . $errorCurl);
            return ['ok' => false, 'status' => 0, 'json' => null];
        }

        $json = json_decode((string) $respuesta, true);
        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'json' => is_array($json) ? $json : null,
        ];
    }
}
