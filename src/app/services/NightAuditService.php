<?php

require_once __DIR__ . '/../helpers/hotel_config.php';

/**
 * NightAuditService (bloque night_audit, $199)
 *
 * Cierre nocturno del dia operativo: detecta pendientes y avisa. REGLA DURA:
 * este servicio SOLO LEE reservaciones, cortes_caja y motor_pagos_online;
 * lo unico que escribe es su propia tabla night_audit_cierres, notificaciones
 * (best-effort, bloque notificaciones) y el correo al gerente. Jamas corrige
 * un no-show, cierra un corte ni toca Caja: eso es decision humana.
 */
class NightAuditService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Ejecuta (o devuelve, si ya existe) el cierre de una fecha operativa.
     * Idempotente por (hotel_id, fecha). Devuelve ['success','cierre','creado'].
     */
    public function ejecutarCierre(int $hotelId, string $fecha, ?int $usuarioId = null): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha > date('Y-m-d')) {
            return ['success' => false, 'cierre' => null, 'creado' => false, 'message' => 'Fecha de cierre invalida.'];
        }

        $existente = $this->porFecha($hotelId, $fecha);
        if ($existente) {
            return ['success' => true, 'cierre' => $existente, 'creado' => false, 'message' => 'El cierre de esa fecha ya existia.'];
        }

        $hallazgos = [
            'no_shows' => $this->noShows($hotelId),
            'checkouts_vencidos' => $this->checkoutsVencidos($hotelId),
            'cortes_abiertos' => $this->cortesAbiertos($hotelId),
        ];
        $conteos = $this->conteosDelDia($hotelId, $fecha);

        try {
            $this->pdo->prepare(
                "INSERT INTO night_audit_cierres
                    (hotel_id, fecha, hallazgos_json, no_shows, checkouts_vencidos, cortes_abiertos,
                     llegadas, salidas, ocupadas_noche, pagos_online, monto_online, ejecutado_por, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            )->execute([
                $hotelId,
                $fecha,
                json_encode($hallazgos, JSON_UNESCAPED_UNICODE),
                count($hallazgos['no_shows']),
                count($hallazgos['checkouts_vencidos']),
                count($hallazgos['cortes_abiertos']),
                $conteos['llegadas'],
                $conteos['salidas'],
                $conteos['ocupadas_noche'],
                $conteos['pagos_online'],
                number_format($conteos['monto_online'], 2, '.', ''),
                $usuarioId,
            ]);
        } catch (Throwable $e) {
            // Carrera cron vs boton manual: si otro proceso lo creo, devolverlo.
            $existente = $this->porFecha($hotelId, $fecha);
            if ($existente) {
                return ['success' => true, 'cierre' => $existente, 'creado' => false, 'message' => 'El cierre de esa fecha ya existia.'];
            }
            error_log('Night audit: error al guardar cierre (hotel ' . $hotelId . '): ' . $e->getMessage());
            return ['success' => false, 'cierre' => null, 'creado' => false, 'message' => 'No se pudo guardar el cierre.'];
        }

        $cierre = $this->porFecha($hotelId, $fecha);
        $this->notificarPendientes($hotelId, $cierre, $hallazgos);

        return ['success' => true, 'cierre' => $cierre, 'creado' => true, 'message' => 'Cierre del ' . $fecha . ' generado.'];
    }

    public function porFecha(int $hotelId, string $fecha): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM night_audit_cierres WHERE hotel_id = ? AND fecha = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $fecha]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function historial(int $hotelId, int $limite = 30): array
    {
        try {
            $limite = max(1, min(90, $limite));
            $stmt = $this->pdo->prepare(
                "SELECT * FROM night_audit_cierres WHERE hotel_id = ? ORDER BY fecha DESC LIMIT {$limite}"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Night audit: error al listar historial: ' . $e->getMessage());
            return [];
        }
    }

    /** Correo del cierre al gerente (remitente/destinatarios de la config del hotel). */
    public function enviarCorreo(int $hotelId, array $cierre, string $nombreHotel): array
    {
        if (!empty($cierre['correo_enviado_at'])) {
            return ['ok' => true, 'error' => null];
        }

        $remitente = trim((string) hotel_config_get('reportes.email_remitente', '', $hotelId));
        if ($remitente === '') {
            $remitente = trim((string) hotel_config_get('contacto.email', '', $hotelId));
        }
        if ($remitente === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Sin correo remitente configurado.'];
        }

        $destinatarios = function_exists('hotel_report_email_recipients')
            ? hotel_report_email_recipients($hotelId)
            : (string) hotel_config_get('reportes.email_destinatarios', '', $hotelId);
        if (is_string($destinatarios)) {
            $destinatarios = preg_split('/[,;\r\n]+/', $destinatarios) ?: [];
        }
        $destinatarios = array_values(array_filter(array_map('trim', (array) $destinatarios), static function ($e) {
            return $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL);
        }));
        if (empty($destinatarios)) {
            $contacto = trim((string) hotel_config_get('contacto.email', '', $hotelId));
            if ($contacto !== '' && filter_var($contacto, FILTER_VALIDATE_EMAIL)) {
                $destinatarios = [$contacto];
            }
        }
        if (empty($destinatarios) || !function_exists('mail')) {
            return ['ok' => false, 'error' => 'Sin destinatarios de correo configurados.'];
        }

        $asunto = 'Cierre del día ' . $cierre['fecha'] . ' - ' . $nombreHotel;
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $nombreHotel . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'X-Mailer: Medisoft Hoteles',
        ];

        $ok = @mail(implode(', ', $destinatarios), $asunto, $this->htmlCorreo($cierre, $nombreHotel), implode("\r\n", $headers));
        if (!$ok) {
            return ['ok' => false, 'error' => 'El servidor no acepto el envio del correo.'];
        }

        try {
            $this->pdo->prepare(
                "UPDATE night_audit_cierres SET correo_enviado_at = NOW(), updated_at = NOW() WHERE id = ?"
            )->execute([(int) $cierre['id']]);
        } catch (Throwable $e) {
            error_log('Night audit: correo enviado pero no se pudo marcar: ' . $e->getMessage());
        }

        return ['ok' => true, 'error' => null];
    }

    // ───────────────────────── Detecciones (solo lectura) ─────────────────────────

    /** Confirmadas cuya llegada ya paso y nunca hicieron check-in. */
    private function noShows(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id, r.fecha_entrada, r.fecha_salida, h.nombre_completo, h.telefono
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND r.estado = 'confirmada' AND r.fecha_entrada < CURDATE()
                 ORDER BY r.fecha_entrada ASC
                 LIMIT 50"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Night audit: error al detectar no-shows: ' . $e->getMessage());
            return [];
        }
    }

    /** Con check-in cuya salida ya paso y siguen sin checkout. */
    private function checkoutsVencidos(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id, r.fecha_entrada, r.fecha_salida, h.nombre_completo, h.telefono
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE r.hotel_id = ? AND r.estado = 'checked_in' AND r.fecha_salida < CURDATE()
                 ORDER BY r.fecha_salida ASC
                 LIMIT 50"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Night audit: error al detectar checkouts vencidos: ' . $e->getMessage());
            return [];
        }
    }

    /** Cortes de caja de dias anteriores que siguen abiertos. */
    private function cortesAbiertos(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, caja_id, fecha_apertura
                 FROM cortes_caja
                 WHERE hotel_id = ? AND fecha_cierre IS NULL AND fecha_apertura < CURDATE()
                 ORDER BY fecha_apertura ASC
                 LIMIT 20"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Night audit: error al detectar cortes abiertos: ' . $e->getMessage());
            return [];
        }
    }

    /** Conteos operativos del dia: llegadas, salidas, ocupadas y pagos del motor. */
    private function conteosDelDia(int $hotelId, string $fecha): array
    {
        $conteos = ['llegadas' => 0, 'salidas' => 0, 'ocupadas_noche' => 0, 'pagos_online' => 0, 'monto_online' => 0.0];

        try {
            $stmt = $this->pdo->prepare(
                "SELECT
                    SUM(fecha_entrada = ? AND estado IN ('checked_in', 'checked_out')) AS llegadas,
                    SUM(fecha_salida = ? AND estado = 'checked_out') AS salidas
                 FROM reservaciones
                 WHERE hotel_id = ? AND estado <> 'cancelada'
                   AND (fecha_entrada = ? OR fecha_salida = ?)"
            );
            $stmt->execute([$fecha, $fecha, $hotelId, $fecha, $fecha]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $conteos['llegadas'] = (int) ($row['llegadas'] ?? 0);
            $conteos['salidas'] = (int) ($row['salidas'] ?? 0);

            // Habitaciones ocupadas la noche de la fecha (entrada <= fecha < salida).
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(rh.id)
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh
                     ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ? AND r.estado NOT IN ('cancelada', 'pendiente')
                   AND r.fecha_entrada <= ? AND r.fecha_salida > ?"
            );
            $stmt->execute([$hotelId, $fecha, $fecha]);
            $conteos['ocupadas_noche'] = (int) $stmt->fetchColumn();

            // Pagos online del motor de ese dia (ledger propio, no Caja).
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) AS n, COALESCE(SUM(monto), 0) AS monto
                 FROM motor_pagos_online
                 WHERE hotel_id = ? AND estado IN ('pagado', 'conciliado') AND DATE(created_at) = ?"
            );
            $stmt->execute([$hotelId, $fecha]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $conteos['pagos_online'] = (int) ($row['n'] ?? 0);
            $conteos['monto_online'] = (float) ($row['monto'] ?? 0);
        } catch (Throwable $e) {
            error_log('Night audit: error en conteos del dia: ' . $e->getMessage());
        }

        return $conteos;
    }

    /** Notificaciones internas por hallazgos pendientes. Best-effort. */
    private function notificarPendientes(int $hotelId, ?array $cierre, array $hallazgos): void
    {
        if (!$cierre) {
            return;
        }

        $pendientes = [];
        if (!empty($hallazgos['no_shows'])) {
            $pendientes[] = count($hallazgos['no_shows']) . ' no-show(s) sin resolver';
        }
        if (!empty($hallazgos['checkouts_vencidos'])) {
            $pendientes[] = count($hallazgos['checkouts_vencidos']) . ' checkout(s) vencido(s)';
        }
        if (!empty($hallazgos['cortes_abiertos'])) {
            $pendientes[] = count($hallazgos['cortes_abiertos']) . ' corte(s) de caja abierto(s) de dias anteriores';
        }

        if (empty($pendientes)) {
            return;
        }

        try {
            require_once __DIR__ . '/NotificacionService.php';

            NotificacionService::crear([
                'hotel_id' => $hotelId,
                'rol_destino' => 'gerente',
                'modulo' => 'night_audit',
                'tipo' => 'cierre_con_pendientes',
                'severidad' => 'alta',
                'titulo' => 'Cierre del día con pendientes',
                'mensaje' => 'Cierre del ' . $cierre['fecha'] . ': ' . implode(', ', $pendientes) . '.',
                'entidad_tipo' => 'night_audit_cierre',
                'entidad_id' => (int) $cierre['id'],
                'url' => 'night-audit',
                'dedupe_key' => 'night_audit_' . (int) $cierre['id'],
            ]);
        } catch (Throwable $e) {
            error_log('Night audit: no se pudo crear notificacion: ' . $e->getMessage());
        }
    }

    private function htmlCorreo(array $cierre, string $nombreHotel): string
    {
        $e = static function ($v) {
            return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        };
        $hallazgos = json_decode((string) ($cierre['hallazgos_json'] ?? ''), true) ?: [];

        $filaResumen = static function ($nombre, $valor, $alerta = false) use ($e) {
            $color = $alerta && (int) $valor > 0 ? '#B91C1C' : '#172033';
            return '<tr><td style="padding:6px 0;color:#475467;">' . $e($nombre) . '</td>'
                . '<td style="padding:6px 0;text-align:right;font-weight:bold;color:' . $color . ';">' . $e($valor) . '</td></tr>';
        };

        $detalle = '';
        foreach (($hallazgos['no_shows'] ?? []) as $r) {
            $detalle .= '<li>No-show: ' . $e($r['nombre_completo']) . ' (reservacion #' . (int) $r['id'] . ', llegada ' . $e($r['fecha_entrada']) . ')</li>';
        }
        foreach (($hallazgos['checkouts_vencidos'] ?? []) as $r) {
            $detalle .= '<li>Checkout vencido: ' . $e($r['nombre_completo']) . ' (reservacion #' . (int) $r['id'] . ', salida ' . $e($r['fecha_salida']) . ')</li>';
        }
        foreach (($hallazgos['cortes_abiertos'] ?? []) as $c) {
            $detalle .= '<li>Corte de caja #' . (int) $c['id'] . ' abierto desde ' . $e($c['fecha_apertura']) . '</li>';
        }
        $bloqueDetalle = $detalle !== ''
            ? '<p style="margin:18px 0 6px;font-weight:bold;color:#B91C1C;">Pendientes que requieren accion:</p><ul style="margin:0;padding-left:18px;color:#475467;font-size:14px;line-height:1.7;">' . $detalle . '</ul>'
            : '<p style="margin:18px 0 0;color:#15803D;font-weight:bold;">Sin pendientes: dia cerrado limpio. ✔</p>';

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">'
            . '<strong>' . $e($nombreHotel) . '</strong> · Cierre del d&iacute;a <strong>' . $e($cierre['fecha']) . '</strong>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;border-top:1px solid #EDEFF3;border-bottom:1px solid #EDEFF3;font-size:14px;">'
            . $filaResumen('Llegadas del dia', $cierre['llegadas'])
            . $filaResumen('Salidas del dia', $cierre['salidas'])
            . $filaResumen('Habitaciones ocupadas esa noche', $cierre['ocupadas_noche'])
            . $filaResumen('Pagos online del motor', $cierre['pagos_online'] . ' ($' . number_format((float) $cierre['monto_online'], 2) . ')')
            . $filaResumen('No-shows', $cierre['no_shows'], true)
            . $filaResumen('Checkouts vencidos', $cierre['checkouts_vencidos'], true)
            . $filaResumen('Cortes de caja abiertos', $cierre['cortes_abiertos'], true)
            . '</table>'
            . $bloqueDetalle
            . '<p style="margin-top:22px;color:#98A2B3;font-size:12px;">Este cierre solo detecta y avisa; ninguna reservacion ni corte fue modificado.</p>'
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</body></html>';
    }
}
