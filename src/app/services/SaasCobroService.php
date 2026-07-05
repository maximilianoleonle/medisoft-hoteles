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
                "INSERT INTO saas_cobros (hotel_id, periodo, monto, desglose_json, estado, vence_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'pendiente', ?, NOW(), NOW())",
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
                    $this->fechaVencimiento($periodo),
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
        if (!in_array($cobro['estado'], ['pendiente', 'vencido'], true)) {
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
             WHERE id = ? AND estado IN ('pendiente', 'vencido')",
            [$metodo, $referencia, $cobroId]
        );

        return $stmt !== false && $stmt->rowCount() > 0;
    }

    public function cancelar(int $cobroId): bool
    {
        $stmt = $this->db->query(
            "UPDATE saas_cobros SET estado = 'cancelado', updated_at = NOW()
             WHERE id = ? AND estado IN ('pendiente', 'vencido')",
            [$cobroId]
        );

        return $stmt !== false && $stmt->rowCount() > 0;
    }

    // ───────────────────────── Ciclo automatico ─────────────────────────

    /**
     * Ciclo completo del periodo, idempotente (pensado para el cron diario o
     * el boton del panel): genera cobros, manda el correo inicial con link de
     * pago, manda recordatorio a los que estan por vencer o vencidos y marca
     * como 'vencido' lo que paso su fecha limite. Devuelve un log legible.
     */
    public function cicloAutomatico(string $periodo): array
    {
        $log = [];

        $generacion = $this->generarPeriodo($periodo);
        $log[] = $generacion['message'];

        // Correo inicial a pendientes sin correo (con link de pago si hay Stripe).
        $correosOk = 0;
        $correosError = 0;
        $stmt = $this->db->query(
            "SELECT id FROM saas_cobros
             WHERE periodo = ? AND estado = 'pendiente' AND correo_enviado_at IS NULL
             ORDER BY id",
            [$periodo]
        );
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $resultado = $this->enviarCorreoCobro((int) $fila['id'], false);
            if (!empty($resultado['success'])) {
                $correosOk++;
            } else {
                $correosError++;
                error_log('Cobros SaaS: correo inicial fallo (cobro ' . $fila['id'] . '): ' . ($resultado['message'] ?? ''));
            }
        }
        $log[] = "Correos de cobro: {$correosOk} enviado(s), {$correosError} con error.";

        // Recordatorio unico: por vencer en <= 3 dias o ya vencido, sin recordatorio previo.
        $recordatoriosOk = 0;
        $recordatoriosError = 0;
        $stmt = $this->db->query(
            "SELECT id FROM saas_cobros
             WHERE estado IN ('pendiente', 'vencido')
               AND correo_enviado_at IS NOT NULL
               AND recordatorio_enviado_at IS NULL
               AND vence_at IS NOT NULL
               AND vence_at <= CURDATE() + INTERVAL 3 DAY
             ORDER BY id"
        );
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $resultado = $this->enviarCorreoCobro((int) $fila['id'], true);
            if (!empty($resultado['success'])) {
                $recordatoriosOk++;
            } else {
                $recordatoriosError++;
                error_log('Cobros SaaS: recordatorio fallo (cobro ' . $fila['id'] . '): ' . ($resultado['message'] ?? ''));
            }
        }
        $log[] = "Recordatorios: {$recordatoriosOk} enviado(s), {$recordatoriosError} con error.";

        $vencidos = $this->marcarVencidos();
        $log[] = "Cobros marcados como vencidos: {$vencidos}.";

        return ['success' => true, 'log' => $log];
    }

    /** Pendientes con fecha limite pasada -> 'vencido'. Devuelve cuantos cambio. */
    public function marcarVencidos(): int
    {
        $stmt = $this->db->query(
            "UPDATE saas_cobros SET estado = 'vencido', updated_at = NOW()
             WHERE estado = 'pendiente' AND vence_at IS NOT NULL AND vence_at < CURDATE()"
        );

        return $stmt !== false ? $stmt->rowCount() : 0;
    }

    /**
     * Correo de cobro (o recordatorio) al email del hotel, con desglose y link
     * de pago (lo crea si hay Stripe configurado). Marca el timestamp de envio.
     */
    public function enviarCorreoCobro(int $cobroId, bool $esRecordatorio = false): array
    {
        $cobro = $this->porId($cobroId);
        if (!$cobro) {
            return ['success' => false, 'message' => 'Cobro no encontrado.'];
        }
        if (!in_array($cobro['estado'], ['pendiente', 'vencido'], true)) {
            return ['success' => false, 'message' => 'Este cobro ya esta ' . $cobro['estado'] . '.'];
        }

        $remitente = trim((string) getenv('SAAS_EMAIL_REMITENTE'));
        if ($remitente === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Falta SAAS_EMAIL_REMITENTE en el .env (correo de tu plataforma).'];
        }
        if (!function_exists('mail')) {
            return ['success' => false, 'message' => 'La funcion mail() no esta disponible en este servidor.'];
        }

        $stmt = $this->db->query("SELECT email FROM hoteles WHERE id = ? LIMIT 1", [(int) $cobro['hotel_id']]);
        $hotelEmail = trim((string) (($stmt ? $stmt->fetch() : null)['email'] ?? ''));
        if ($hotelEmail === '' || !filter_var($hotelEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El hotel no tiene un email valido registrado en el panel.'];
        }

        // Asegurar link de pago si hay Stripe; sin Stripe el correo va sin boton.
        if (empty($cobro['checkout_url']) && $this->configurado()) {
            $base = rtrim((string) getenv('APP_URL'), '/') ?: 'http://localhost';
            $this->crearLinkPago($cobroId, $base . '/login');
            $cobro = $this->porId($cobroId) ?: $cobro;
        }

        $nombrePlataforma = trim((string) getenv('SAAS_EMAIL_NOMBRE')) ?: 'Medisoft Hoteles';
        $asunto = $esRecordatorio
            ? 'Recordatorio de pago ' . $cobro['periodo'] . ' - ' . $nombrePlataforma
            : 'Tu mensualidad ' . $cobro['periodo'] . ' - ' . $nombrePlataforma;

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->mimeHeader($nombrePlataforma) . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'X-Mailer: Medisoft Hoteles',
        ];

        $ok = @mail(
            $hotelEmail,
            $this->mimeHeader($asunto),
            $this->htmlCorreoCobro($cobro, $nombrePlataforma, $esRecordatorio),
            implode("\r\n", $headers)
        );
        if (!$ok) {
            return ['success' => false, 'message' => 'El servidor no acepto el envio del correo.'];
        }

        $campo = $esRecordatorio ? 'recordatorio_enviado_at' : 'correo_enviado_at';
        $this->db->query(
            "UPDATE saas_cobros SET {$campo} = NOW(), updated_at = NOW() WHERE id = ?",
            [$cobroId]
        );

        return ['success' => true, 'message' => ($esRecordatorio ? 'Recordatorio' : 'Correo de cobro') . ' enviado a ' . $hotelEmail . '.'];
    }

    /** Estado de cuenta del hotel: sus cobros mas recientes. */
    public function cobrosPorHotel(int $hotelId, int $limite = 24): array
    {
        $limite = max(1, min(60, $limite));
        $stmt = $this->db->query(
            "SELECT * FROM saas_cobros WHERE hotel_id = ? ORDER BY periodo DESC LIMIT {$limite}",
            [$hotelId]
        );

        return $stmt ? $stmt->fetchAll() : [];
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

    // ───────────────────────── Helpers ─────────────────────────

    /** Fecha limite de pago del periodo: dia SAAS_COBRO_DIA_VENCIMIENTO (1-28, default 10). */
    private function fechaVencimiento(string $periodo): string
    {
        $dia = (int) getenv('SAAS_COBRO_DIA_VENCIMIENTO');
        if ($dia < 1 || $dia > 28) {
            $dia = 10;
        }

        return $periodo . '-' . str_pad((string) $dia, 2, '0', STR_PAD_LEFT);
    }

    private function htmlCorreoCobro(array $cobro, string $nombrePlataforma, bool $esRecordatorio): string
    {
        $e = static function ($v) {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $money = static function ($n) {
            return '$' . number_format((float) $n, 2) . ' MXN';
        };

        $desglose = json_decode((string) ($cobro['desglose_json'] ?? ''), true) ?: [];
        $filas = '<tr><td style="padding:6px 0;color:#475467;">Paquete basico</td>'
            . '<td style="padding:6px 0;text-align:right;color:#172033;">' . $money($desglose['precio_base'] ?? 0) . '</td></tr>';
        foreach (($desglose['modulos'] ?? []) as $m) {
            $filas .= '<tr><td style="padding:6px 0;color:#475467;">' . $e($m['nombre'] ?? $m['clave'] ?? 'Bloque') . '</td>'
                . '<td style="padding:6px 0;text-align:right;color:#172033;">' . $money($m['precio'] ?? 0) . '</td></tr>';
        }

        $intro = $esRecordatorio
            ? 'Te recordamos que la mensualidad de <strong>' . $e($cobro['periodo']) . '</strong> sigue pendiente de pago.'
            : 'Este es el detalle de tu mensualidad de <strong>' . $e($cobro['periodo']) . '</strong>.';
        $vence = !empty($cobro['vence_at'])
            ? '<p style="margin:14px 0 0;color:#667085;font-size:13px;">Fecha limite de pago: <strong>'
                . $e(date('d/m/Y', strtotime((string) $cobro['vence_at']))) . '</strong></p>'
            : '';
        $boton = !empty($cobro['checkout_url'])
            ? '<div style="margin-top:22px;"><a href="' . $e($cobro['checkout_url']) . '" style="display:inline-block;background:#1B2746;color:#ffffff;text-decoration:none;border-radius:8px;padding:12px 18px;font-weight:bold;">Pagar en linea</a></div>'
            : '<p style="margin-top:22px;color:#667085;font-size:13px;">Responde este correo para coordinar tu pago.</p>';

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">'
            . 'Hola, equipo de <strong>' . $e($cobro['hotel_nombre'] ?? 'tu hotel') . '</strong>:<br><br>' . $intro
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;border-top:1px solid #EDEFF3;border-bottom:1px solid #EDEFF3;font-size:14px;">'
            . $filas
            . '<tr><td style="padding:10px 0;font-weight:bold;color:#172033;">Total del mes</td>'
            . '<td style="padding:10px 0;text-align:right;font-weight:bold;color:#172033;">' . $money($cobro['monto']) . '</td></tr>'
            . '</table>'
            . $vence
            . $boton
            . '<p style="margin-top:22px;color:#98A2B3;font-size:12px;">' . $e($nombrePlataforma) . ' · Si ya pagaste, ignora este mensaje.</p>'
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    private function mimeHeader(string $value): string
    {
        $value = trim((string) preg_replace('/[\r\n\x00-\x1F\x7F]+/u', ' ', $value));
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
