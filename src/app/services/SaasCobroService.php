<?php

require_once __DIR__ . '/MotorPasarelaService.php'; // reutiliza verificarWebhookStripe()

/**
 * SaasCobroService: facturacion mensual de Medisoft a los hoteles.
 *
 * Genera un cobro por hotel activo y periodo (YYYY-MM) congelando el desglose
 * de resumenCobroMensual. El pago es via Stripe Checkout con la cuenta de
 * MEDISOFT (llaves globales SAAS_STRIPE_SECRET_KEY / SAAS_STRIPE_WEBHOOK_SECRET
 * en .env — recrear el contenedor al cambiarlas), o marcado manual.
 *
 * Este dinero es de la plataforma: JAMAS toca la Caja de ningun hotel.
 */
class SaasCobroService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    // ───────────────────────── Generacion ─────────────────────────

    /** Genera los cobros del periodo para hoteles activos que aun no lo tengan. */
    public function generarPeriodo(string $periodo): array
    {
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            return ['success' => false, 'message' => 'Periodo invalido; usa el formato AAAA-MM.'];
        }

        $stmt = $this->db->query("SELECT id, nombre FROM hoteles WHERE activo = 1 ORDER BY id");
        $hoteles = $stmt ? $stmt->fetchAll() : [];

        if (!class_exists('Modulo')) {
            require_once __DIR__ . '/../models/Modulo.php';
        }
        $moduloModel = new Modulo();

        $creados = 0;
        $omitidos = 0;

        foreach ($hoteles as $hotel) {
            $hotelId = (int) $hotel['id'];

            $existe = $this->db->query(
                "SELECT id FROM saas_cobros WHERE hotel_id = ? AND periodo = ? LIMIT 1",
                [$hotelId, $periodo]
            );
            if ($existe && $existe->fetch()) {
                $omitidos++;
                continue;
            }

            $resumen = $moduloModel->resumenCobroMensual($hotelId);
            if (!$resumen) {
                error_log('Cobros SaaS: sin resumen de cobro para hotel ' . $hotelId . '; omitido.');
                $omitidos++;
                continue;
            }

            $this->db->query(
                "INSERT INTO saas_cobros (hotel_id, periodo, monto, desglose_json, estado, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'pendiente', NOW(), NOW())",
                [
                    $hotelId,
                    $periodo,
                    round((float) $resumen['total'], 2),
                    json_encode([
                        'precio_base' => $resumen['precio_base'],
                        'total_modulos' => $resumen['total_modulos'],
                        'modulos' => array_map(static function ($m) {
                            return [
                                'clave' => $m['clave'],
                                'nombre' => $m['nombre'],
                                'precio' => (float) $m['precio_aplicado'],
                            ];
                        }, $resumen['modulos']),
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );
            $creados++;
        }

        return [
            'success' => true,
            'message' => "Cobros de {$periodo}: {$creados} generado(s), {$omitidos} ya existian u omitidos.",
            'creados' => $creados,
        ];
    }

    public function cobrosDelPeriodo(string $periodo): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, h.nombre AS hotel_nombre, h.slug AS hotel_slug
             FROM saas_cobros c
             INNER JOIN hoteles h ON h.id = c.hotel_id
             WHERE c.periodo = ?
             ORDER BY h.nombre",
            [$periodo]
        );

        return $stmt ? $stmt->fetchAll() : [];
    }

    public function porId(int $cobroId): ?array
    {
        $stmt = $this->db->query(
            "SELECT c.*, h.nombre AS hotel_nombre FROM saas_cobros c
             INNER JOIN hoteles h ON h.id = c.hotel_id
             WHERE c.id = ? LIMIT 1",
            [$cobroId]
        );

        return $stmt ? ($stmt->fetch() ?: null) : null;
    }

    // ───────────────────────── Pago ─────────────────────────

    public function configurado(): bool
    {
        return trim((string) getenv('SAAS_STRIPE_SECRET_KEY')) !== '';
    }

    /** Crea (o reutiliza) el link de pago Stripe del cobro. */
    public function crearLinkPago(int $cobroId, string $urlRetorno): array
    {
        $cobro = $this->porId($cobroId);

        if (!$cobro) {
            return ['success' => false, 'message' => 'Cobro no encontrado.'];
        }
        if ($cobro['estado'] !== 'pendiente') {
            return ['success' => false, 'message' => 'Este cobro ya esta ' . $cobro['estado'] . '.'];
        }
        if (!empty($cobro['checkout_url'])) {
            return ['success' => true, 'message' => 'Este cobro ya tiene link de pago.', 'checkout_url' => $cobro['checkout_url']];
        }

        $secretKey = trim((string) getenv('SAAS_STRIPE_SECRET_KEY'));
        if ($secretKey === '') {
            return ['success' => false, 'message' => 'Falta SAAS_STRIPE_SECRET_KEY en el .env (y recrear el contenedor).'];
        }

        $params = [
            'mode' => 'payment',
            'client_reference_id' => 'saas-cobro-' . (int) $cobro['id'],
            'success_url' => $urlRetorno,
            'cancel_url' => $urlRetorno,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => 'mxn',
            'line_items[0][price_data][unit_amount]' => (int) round(((float) $cobro['monto']) * 100),
            'line_items[0][price_data][product_data][name]' => 'Medisoft Hoteles · ' . $cobro['hotel_nombre'] . ' · ' . $cobro['periodo'],
            'metadata[saas_cobro_id]' => (string) $cobro['id'],
            'payment_intent_data[metadata][saas_cobro_id]' => (string) $cobro['id'],
        ];

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $respuesta = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        $json = json_decode((string) $respuesta, true) ?: [];

        if ($status !== 200 || empty($json['id']) || empty($json['url'])) {
            $detalle = $json['error']['message'] ?? ('HTTP ' . $status);
            error_log('Cobros SaaS: Stripe checkout fallo (cobro ' . $cobroId . '): ' . $detalle);
            return ['success' => false, 'message' => 'Stripe rechazo el link de pago: ' . $detalle];
        }

        $this->db->query(
            "UPDATE saas_cobros
             SET checkout_url = ?, proveedor_pago_id = ?, metodo = 'stripe', updated_at = NOW()
             WHERE id = ?",
            [(string) $json['url'], (string) $json['id'], $cobroId]
        );

        return ['success' => true, 'message' => 'Link de pago creado. Copialo y mandaselo al hotel.', 'checkout_url' => (string) $json['url']];
    }

    /** Marca pagado (webhook o manual). Idempotente. */
    public function marcarPagado(int $cobroId, string $metodo, ?string $referencia = null): bool
    {
        $stmt = $this->db->query(
            "UPDATE saas_cobros
             SET estado = 'pagado', metodo = ?, pagado_at = NOW(),
                 proveedor_pago_id = COALESCE(?, proveedor_pago_id), updated_at = NOW()
             WHERE id = ? AND estado = 'pendiente'",
            [$metodo, $referencia, $cobroId]
        );

        return $stmt !== false && $stmt->rowCount() > 0;
    }

    public function cancelar(int $cobroId): bool
    {
        $stmt = $this->db->query(
            "UPDATE saas_cobros SET estado = 'cancelado', updated_at = NOW()
             WHERE id = ? AND estado = 'pendiente'",
            [$cobroId]
        );

        return $stmt !== false && $stmt->rowCount() > 0;
    }

    // ───────────────────────── Webhook ─────────────────────────

    /**
     * Procesa el webhook Stripe de la cuenta Medisoft. Devuelve HTTP status
     * a responder. Firma obligatoria con SAAS_STRIPE_WEBHOOK_SECRET.
     */
    public function procesarWebhookStripe(string $payload, string $sigHeader): int
    {
        $whsec = trim((string) getenv('SAAS_STRIPE_WEBHOOK_SECRET'));

        $pasarela = new MotorPasarelaService($this->db);
        $evento = $pasarela->verificarWebhookStripe($payload, $sigHeader, $whsec);

        if ($evento === null) {
            return 400;
        }

        $tipo = (string) ($evento['type'] ?? '');
        if ($tipo !== 'checkout.session.completed') {
            return 200; // evento que no nos interesa; confirmar recepcion
        }

        $sesion = $evento['data']['object'] ?? [];
        $cobroId = (int) ($sesion['metadata']['saas_cobro_id'] ?? 0);

        if ($cobroId <= 0 || (($sesion['payment_status'] ?? '') !== 'paid')) {
            return 200;
        }

        $this->marcarPagado($cobroId, 'stripe', (string) ($sesion['id'] ?? ''));
        return 200;
    }
}
