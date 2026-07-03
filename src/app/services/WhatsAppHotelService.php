<?php

require_once __DIR__ . '/MotorPasarelaService.php'; // reutiliza cifrar()/descifrar()

/**
 * WhatsAppHotelService (bloque whatsapp, $299)
 *
 * Envio de WhatsApp multi-tenant via Green API: cada hotel conecta su propio
 * numero (instancia + token, cifrado igual que la pasarela). V1:
 *  - Confirmacion automatica al huesped cuando paga su reserva online (motor).
 *  - Aviso al dueno de cada reserva online.
 * Todo best-effort: un fallo de WhatsApp JAMAS rompe el flujo que lo dispara.
 *
 * API Green: POST {host}/waInstance{id}/sendMessage/{token}
 *            body {"chatId":"521XXXXXXXXXX@c.us","message":"..."}
 */
class WhatsAppHotelService
{
    private $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
    }

    // ───────────────────────── Credenciales ─────────────────────────

    public function credenciales(int $hotelId): ?array
    {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM hotel_whatsapp_credenciales WHERE hotel_id = ? AND activo = 1 LIMIT 1",
                [$hotelId]
            );
            $row = $stmt ? $stmt->fetch() : null;
            if (!$row) {
                return null;
            }

            $row['api_token'] = MotorPasarelaService::descifrar((string) ($row['api_token_encrypted'] ?? ''));
            unset($row['api_token_encrypted']);

            if (empty($row['id_instance']) || $row['api_token'] === null || $row['api_token'] === '') {
                return null;
            }

            return $row;
        } catch (Throwable $e) {
            error_log('WhatsApp hotel: error al leer credenciales del hotel ' . $hotelId . ': ' . $e->getMessage());
            return null;
        }
    }

    public function guardarCredenciales(int $hotelId, string $idInstance, ?string $apiToken, string $numeroAvisos, bool $activo): bool
    {
        try {
            $actual = $this->db->query(
                "SELECT api_token_encrypted FROM hotel_whatsapp_credenciales WHERE hotel_id = ? LIMIT 1",
                [$hotelId]
            );
            $fila = $actual ? $actual->fetch() : null;

            // Token vacio en el form = conservar el guardado (nunca se re-muestra).
            $tokenEnc = ($apiToken !== null && trim($apiToken) !== '')
                ? MotorPasarelaService::cifrar(trim($apiToken))
                : ($fila['api_token_encrypted'] ?? null);

            $stmt = $this->db->query(
                "INSERT INTO hotel_whatsapp_credenciales
                    (hotel_id, id_instance, api_token_encrypted, numero_avisos, activo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    id_instance = VALUES(id_instance),
                    api_token_encrypted = VALUES(api_token_encrypted),
                    numero_avisos = VALUES(numero_avisos),
                    activo = VALUES(activo),
                    updated_at = NOW()",
                [
                    $hotelId,
                    trim($idInstance),
                    $tokenEnc,
                    $this->normalizarTelefono($numeroAvisos) ?: null,
                    $activo ? 1 : 0,
                ]
            );

            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('WhatsApp hotel: error al guardar credenciales del hotel ' . $hotelId . ': ' . $e->getMessage());
            return false;
        }
    }

    public function configurado(int $hotelId): bool
    {
        return $this->credenciales($hotelId) !== null;
    }

    // ───────────────────────── Envio base ─────────────────────────

    /**
     * Envia un mensaje de texto. Telefono en cualquier formato mexicano
     * razonable (10 digitos, 52..., +52...). Best-effort: devuelve bool.
     */
    public function enviarMensaje(int $hotelId, string $telefono, string $mensaje): bool
    {
        $cred = $this->credenciales($hotelId);
        if (!$cred) {
            return false;
        }

        $numero = $this->normalizarTelefono($telefono);
        if ($numero === '') {
            error_log('WhatsApp hotel: telefono invalido para hotel ' . $hotelId . ': ' . $telefono);
            return false;
        }

        $url = sprintf(
            '%s/waInstance%s/sendMessage/%s',
            rtrim((string) $cred['api_host'], '/'),
            $cred['id_instance'],
            $cred['api_token']
        );

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'chatId' => $numero . '@c.us',
                    'message' => $mensaje,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_TIMEOUT => 15,
                CURLOPT_CONNECTTIMEOUT => 8,
            ]);
            $respuesta = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            $json = json_decode((string) $respuesta, true);
            $enviado = $status === 200 && !empty($json['idMessage']);

            if (!$enviado) {
                error_log('WhatsApp hotel: envio fallido (hotel ' . $hotelId . ', HTTP ' . $status . '): ' . substr((string) $respuesta, 0, 200));
            }

            return $enviado;
        } catch (Throwable $e) {
            error_log('WhatsApp hotel: excepcion al enviar (hotel ' . $hotelId . '): ' . $e->getMessage());
            return false;
        }
    }

    public function enviarPrueba(int $hotelId, string $nombreHotel): bool
    {
        $cred = $this->credenciales($hotelId);
        if (!$cred || empty($cred['numero_avisos'])) {
            return false;
        }

        return $this->enviarMensaje(
            $hotelId,
            (string) $cred['numero_avisos'],
            "✅ *{$nombreHotel}*\nWhatsApp conectado correctamente a Medisoft Hoteles.\nRecibiras aqui los avisos de reservas online."
        );
    }

    // ───────────────────────── Casos de negocio (motor) ─────────────────────────

    /**
     * Mensajes de una reserva online pagada: confirmacion al huesped + aviso
     * al dueno. Best-effort, respeta el bloque y los toggles del hotel.
     * $r: nombre, telefono, folio, entrada, salida, tipo, anticipo, saldo, hotel_nombre.
     */
    public function notificarReservaOnline(int $hotelId, array $r): void
    {
        try {
            // Bloque comercial: sin 'whatsapp' contratado no se envia nada.
            if (function_exists('hotel_has_module') && !hotel_has_module('whatsapp', $hotelId)) {
                return;
            }

            $cred = $this->credenciales($hotelId);
            if (!$cred) {
                return;
            }

            $fmt = static function ($n) {
                return '$' . number_format((float) $n, 2);
            };
            $fecha = static function ($iso) {
                $ts = strtotime((string) $iso . ' 12:00:00');
                return $ts ? date('d/m/Y', $ts) : (string) $iso;
            };

            if (ConfiguracionHotelRegistry::getBool('whatsapp.confirmacion_huesped_activa', true, $hotelId)
                && !empty($r['telefono'])) {
                $msj = "✅ *Reservacion confirmada — {$r['hotel_nombre']}*\n\n"
                    . "Hola {$r['nombre']}, recibimos tu anticipo y tu habitacion esta apartada.\n\n"
                    . "🧾 Folio: *#{$r['folio']}*\n"
                    . "📅 Llegada: {$fecha($r['entrada'])}\n"
                    . "📅 Salida: {$fecha($r['salida'])}\n"
                    . "🛏 Habitacion: {$r['tipo']}\n"
                    . "💳 Anticipo pagado: {$fmt($r['anticipo'])}\n"
                    . "💰 Pagas al llegar: {$fmt($r['saldo'])}\n\n"
                    . (!empty($r['checkin_url'])
                        ? "⚡ Adelanta tu llegada: completa tu pre-registro aqui (1 minuto):\n{$r['checkin_url']}\n\n"
                        : '')
                    . "Presenta tu folio al llegar. ¡Te esperamos!";
                $this->enviarMensaje($hotelId, (string) $r['telefono'], $msj);
            }

            if (ConfiguracionHotelRegistry::getBool('whatsapp.aviso_dueno_activo', true, $hotelId)
                && !empty($cred['numero_avisos'])) {
                $msj = "💵 *Nueva reserva online — {$r['hotel_nombre']}*\n\n"
                    . "Huesped: {$r['nombre']}\n"
                    . "Folio: #{$r['folio']} · {$r['tipo']}\n"
                    . "{$fecha($r['entrada'])} → {$fecha($r['salida'])}\n"
                    . "Anticipo cobrado: {$fmt($r['anticipo'])} (por conciliar a Caja)\n"
                    . "Saldo al llegar: {$fmt($r['saldo'])}";
                $this->enviarMensaje($hotelId, (string) $cred['numero_avisos'], $msj);
            }
        } catch (Throwable $e) {
            // Best-effort: jamas romper el flujo que nos llamo (webhook de pago).
            error_log('WhatsApp hotel: notificarReservaOnline fallo (hotel ' . $hotelId . '): ' . $e->getMessage());
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    /** Normaliza a formato Green API para Mexico: 521 + 10 digitos. */
    private function normalizarTelefono(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono);

        if (strlen($digitos) === 10) {
            return '521' . $digitos;
        }
        if (strlen($digitos) === 12 && strpos($digitos, '52') === 0) {
            return '521' . substr($digitos, 2);
        }
        if (strlen($digitos) === 13 && strpos($digitos, '521') === 0) {
            return $digitos;
        }

        // Otros paises: aceptar 11-15 digitos tal cual.
        return (strlen($digitos) >= 11 && strlen($digitos) <= 15) ? $digitos : '';
    }
}
