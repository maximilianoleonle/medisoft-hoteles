<?php

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';
require_once __DIR__ . '/MotorCuponService.php';

/**
 * LealtadService (bloque lealtad, $179)
 *
 * Programa de huesped frecuente: ranking por estancias completadas y cupon
 * personal de agradecimiento. El cupon se crea en motor_cupones (1 solo uso,
 * vigencia limitada) y viaja por el flujo de dinero YA blindado del bloque
 * promociones: aqui no se toca ningun calculo de pago.
 */
class LealtadService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Huespedes con al menos $minEstancias estancias completadas, con su
     * cupon de lealtad vigente (si existe). Orden: mas estancias primero.
     */
    public function frecuentes(int $hotelId, int $minEstancias): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.id, h.nombre_completo, h.telefono, h.email,
                        COUNT(r.id) AS estancias,
                        MAX(r.fecha_salida) AS ultima_salida,
                        lc.id AS lealtad_id, lc.correo_enviado_at,
                        c.codigo AS cupon_codigo, c.valor AS cupon_valor,
                        c.vigente_hasta AS cupon_vigencia, c.usos AS cupon_usos,
                        c.limite_usos AS cupon_limite, c.activo AS cupon_activo
                 FROM huespedes h
                 INNER JOIN reservaciones r
                     ON r.huesped_id = h.id AND r.hotel_id = h.hotel_id
                    AND r.estado IN ('checked_out', 'completada')
                 LEFT JOIN lealtad_cupones lc ON lc.id = (
                     SELECT lc2.id FROM lealtad_cupones lc2
                     INNER JOIN motor_cupones c2 ON c2.id = lc2.cupon_id
                     WHERE lc2.hotel_id = h.hotel_id AND lc2.huesped_id = h.id
                       AND c2.activo = 1
                       AND (c2.limite_usos IS NULL OR c2.usos < c2.limite_usos)
                       AND (c2.vigente_hasta IS NULL OR c2.vigente_hasta >= CURDATE())
                     ORDER BY lc2.id DESC LIMIT 1
                 )
                 LEFT JOIN motor_cupones c ON c.id = lc.cupon_id
                 WHERE h.hotel_id = ?
                 GROUP BY h.id, h.nombre_completo, h.telefono, h.email,
                          lc.id, lc.correo_enviado_at, c.codigo, c.valor,
                          c.vigente_hasta, c.usos, c.limite_usos, c.activo
                 HAVING estancias >= ?
                 ORDER BY estancias DESC, ultima_salida DESC
                 LIMIT 200"
            );
            $stmt->execute([$hotelId, max(1, $minEstancias)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Lealtad: error al listar frecuentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera el cupon personal de un huesped frecuente. Devuelve
     * ['success', 'message', 'codigo' => ?string].
     */
    public function generarCupon(int $hotelId, int $huespedId, ?int $usuarioId = null): array
    {
        $minEstancias = max(1, ConfiguracionHotelRegistry::getInt('lealtad.min_estancias', 3, $hotelId));

        try {
            $stmt = $this->pdo->prepare(
                "SELECT h.id, h.nombre_completo, COUNT(r.id) AS estancias
                 FROM huespedes h
                 INNER JOIN reservaciones r
                     ON r.huesped_id = h.id AND r.hotel_id = h.hotel_id
                    AND r.estado IN ('checked_out', 'completada')
                 WHERE h.hotel_id = ? AND h.id = ?
                 GROUP BY h.id, h.nombre_completo"
            );
            $stmt->execute([$hotelId, $huespedId]);
            $huesped = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Lealtad: error al leer huesped: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo leer al huesped.', 'codigo' => null];
        }

        if (!$huesped || (int) $huesped['estancias'] < $minEstancias) {
            return ['success' => false, 'message' => 'El huesped aun no cumple las estancias minimas del programa.', 'codigo' => null];
        }

        if ($this->cuponVigente($hotelId, $huespedId)) {
            return ['success' => false, 'message' => 'Este huesped ya tiene un cupon de lealtad vigente.', 'codigo' => null];
        }

        $descuento = max(1, min(100, ConfiguracionHotelRegistry::getInt('lealtad.descuento_pct', 10, $hotelId)));
        $vigenciaDias = max(7, ConfiguracionHotelRegistry::getInt('lealtad.vigencia_dias', 90, $hotelId));
        $cuponService = new MotorCuponService($this->db);

        // Codigo personal unico; reintenta ante la (rarisima) colision.
        $codigo = null;
        for ($i = 0; $i < 4; $i++) {
            $candidato = 'GRACIAS-' . strtoupper(bin2hex(random_bytes(3)));
            $resultado = $cuponService->crear($hotelId, [
                'codigo' => $candidato,
                'tipo' => 'porcentaje',
                'valor' => (string) $descuento,
                'vigente_desde' => date('Y-m-d'),
                'vigente_hasta' => date('Y-m-d', strtotime('+' . $vigenciaDias . ' days')),
                'limite_usos' => '1',
            ], $usuarioId);

            if (!empty($resultado['success'])) {
                $codigo = $candidato;
                break;
            }
        }

        if ($codigo === null) {
            return ['success' => false, 'message' => 'No se pudo generar el cupon. Intenta de nuevo.', 'codigo' => null];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM motor_cupones WHERE hotel_id = ? AND codigo = ? LIMIT 1");
            $stmt->execute([$hotelId, $codigo]);
            $cuponId = (int) $stmt->fetchColumn();

            $this->pdo->prepare(
                "INSERT INTO lealtad_cupones
                    (hotel_id, huesped_id, cupon_id, estancias_al_generar, creado_por, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            )->execute([$hotelId, $huespedId, $cuponId, (int) $huesped['estancias'], $usuarioId]);
        } catch (Throwable $e) {
            error_log('Lealtad: cupon creado pero no se pudo ligar al huesped: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Cupon ' . $codigo . ' (' . $descuento . '%) generado para ' . $huesped['nombre_completo'] . '.',
            'codigo' => $codigo,
        ];
    }

    /** Correo con el cupon al huesped. Devuelve ['ok', 'error']. */
    public function enviarCorreo(int $hotelId, int $lealtadId, string $urlMotor, string $nombreHotel): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT lc.id, lc.correo_enviado_at, h.email, h.nombre_completo,
                        c.codigo, c.valor, c.vigente_hasta
                 FROM lealtad_cupones lc
                 INNER JOIN huespedes h ON h.id = lc.huesped_id
                 INNER JOIN motor_cupones c ON c.id = lc.cupon_id
                 WHERE lc.hotel_id = ? AND lc.id = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $lealtadId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'No se pudo leer el cupon.'];
        }

        if (!$fila) {
            return ['ok' => false, 'error' => 'Cupon de lealtad no encontrado.'];
        }

        $email = trim((string) ($fila['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'El huesped no tiene un correo valido registrado.'];
        }

        $remitente = trim((string) hotel_config_get('reportes.email_remitente', '', $hotelId));
        if ($remitente === '') {
            $remitente = trim((string) hotel_config_get('contacto.email', '', $hotelId));
        }
        if ($remitente === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL) || !function_exists('mail')) {
            return ['ok' => false, 'error' => 'Configura el correo remitente del hotel.'];
        }

        $e = static function ($v) {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $nombre = trim((string) $fila['nombre_completo']);
        $vigencia = $fila['vigente_hasta'] ? date('d/m/Y', strtotime((string) $fila['vigente_hasta'])) : null;

        $body = '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">'
            . ($nombre !== '' ? 'Hola ' . $e($nombre) . ',' : 'Hola,')
            . '<br><br>Gracias por elegirnos una y otra vez. Eres de nuestros huespedes favoritos, y queremos '
            . 'agradecertelo con un <strong>' . $e(rtrim(rtrim(number_format((float) $fila['valor'], 2), '0'), '.')) . '% de descuento</strong> en tu proxima estancia en <strong>' . $e($nombreHotel) . '</strong>.'
            . '<div style="margin:22px 0;text-align:center;"><div style="display:inline-block;border:2px dashed #BD9441;border-radius:10px;padding:14px 26px;font-family:ui-monospace,monospace;font-size:1.4rem;font-weight:bold;letter-spacing:.12em;color:#1B2746;">' . $e($fila['codigo']) . '</div></div>'
            . '<p style="margin:0;color:#475467;">Usalo al reservar en linea:</p>'
            . '<div style="margin-top:12px;"><a href="' . $e($urlMotor) . '" style="display:inline-block;background:#1B2746;color:#ffffff;text-decoration:none;border-radius:8px;padding:12px 18px;font-weight:bold;">Reservar mi proxima estancia</a></div>'
            . ($vigencia ? '<p style="margin-top:18px;color:#667085;font-size:13px;">Valido hasta el ' . $e($vigencia) . ' · un solo uso · aplica al hospedaje.</p>' : '')
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</body></html>';

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $nombreHotel . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'X-Mailer: Medisoft Hoteles',
        ];

        $ok = @mail($email, 'Un regalo por tu lealtad - ' . $nombreHotel, $body, implode("\r\n", $headers));
        if (!$ok) {
            return ['ok' => false, 'error' => 'El servidor no acepto el envio del correo.'];
        }

        try {
            $this->pdo->prepare("UPDATE lealtad_cupones SET correo_enviado_at = NOW() WHERE id = ?")
                ->execute([$lealtadId]);
        } catch (Throwable $e2) {
            error_log('Lealtad: correo enviado pero no se pudo marcar: ' . $e2->getMessage());
        }

        return ['ok' => true, 'error' => null];
    }

    /** True si el huesped ya tiene un cupon de lealtad activo, con usos y vigente. */
    private function cuponVigente(int $hotelId, int $huespedId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM lealtad_cupones lc
                 INNER JOIN motor_cupones c ON c.id = lc.cupon_id
                 WHERE lc.hotel_id = ? AND lc.huesped_id = ?
                   AND c.activo = 1
                   AND (c.limite_usos IS NULL OR c.usos < c.limite_usos)
                   AND (c.vigente_hasta IS NULL OR c.vigente_hasta >= CURDATE())
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $huespedId]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}
