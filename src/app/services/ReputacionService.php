<?php

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/ConfiguracionHotelRegistry.php';

/**
 * ReputacionService (bloque reputacion, $149)
 *
 * Encuestas post-estancia: recepcion genera un link con token por reservacion
 * con checkout; el huesped califica su estancia (1-5), NPS opcional y comentario.
 * Calificacion alta -> se le invita a dejar resena en Google (link por hotel);
 * calificacion baja -> alerta interna via NotificacionService (best-effort,
 * gateada por el bloque notificaciones). No toca el flujo de check-out.
 */
class ReputacionService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Genera (o reusa) la encuesta de una reservacion con checkout. Devuelve el token o null. */
    public function generarLink(int $hotelId, int $reservacionId, ?int $usuarioId = null): ?string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, fecha_salida, estado FROM reservaciones WHERE id = ? AND hotel_id = ? LIMIT 1"
            );
            $stmt->execute([$reservacionId, $hotelId]);
            $reservacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservacion || !in_array((string) $reservacion['estado'], ['checked_out', 'completada'], true)) {
                return null;
            }

            // Reusar la encuesta existente si ya hay una (idempotente por reservacion).
            $stmt = $this->pdo->prepare(
                "SELECT token, estado FROM reputacion_encuestas
                 WHERE hotel_id = ? AND reservacion_id = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $reservacionId]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            $dias = max(1, ConfiguracionHotelRegistry::getInt('reputacion.dias_vigencia', 30, $hotelId));
            $expira = date('Y-m-d 23:59:59', strtotime((string) $reservacion['fecha_salida'] . ' +' . $dias . ' days'));

            if ($existente) {
                // Refrescar vigencia y reactivar si estaba expirada (no si ya respondio).
                if ($existente['estado'] !== 'respondida') {
                    $this->pdo->prepare(
                        "UPDATE reputacion_encuestas
                         SET estado = IF(estado = 'expirada', 'pendiente', estado), expires_at = ?, updated_at = NOW()
                         WHERE hotel_id = ? AND reservacion_id = ?"
                    )->execute([$expira, $hotelId, $reservacionId]);
                }
                return (string) $existente['token'];
            }

            $token = bin2hex(random_bytes(16));
            $this->pdo->prepare(
                "INSERT INTO reputacion_encuestas
                    (hotel_id, reservacion_id, token, estado, expires_at, creado_por, created_at, updated_at)
                 VALUES (?, ?, ?, 'pendiente', ?, ?, NOW(), NOW())"
            )->execute([$hotelId, $reservacionId, $token, $expira, $usuarioId]);

            return $token;
        } catch (Throwable $e) {
            error_log('Reputacion: error al generar link (reservacion ' . $reservacionId . '): ' . $e->getMessage());
            return null;
        }
    }

    /** Encuesta + datos de la reservacion/huesped para la pagina publica, o null. */
    public function porToken(int $hotelId, string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT e.*, r.fecha_entrada, r.fecha_salida,
                        h.nombre_completo, h.telefono, h.email
                 FROM reputacion_encuestas e
                 INNER JOIN reservaciones r ON r.id = e.reservacion_id AND r.hotel_id = e.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE e.hotel_id = ? AND e.token = ?
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $token]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                return null;
            }

            if (in_array($fila['estado'], ['pendiente', 'enviada'], true) && strtotime((string) $fila['expires_at']) < time()) {
                $this->pdo->prepare("UPDATE reputacion_encuestas SET estado = 'expirada' WHERE id = ?")
                    ->execute([(int) $fila['id']]);
                $fila['estado'] = 'expirada';
            }

            return $fila;
        } catch (Throwable $e) {
            error_log('Reputacion: error al leer token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Guarda la respuesta del huesped. Devuelve ['success', 'message', 'mostrar_google'].
     */
    public function responder(int $hotelId, string $token, array $datos): array
    {
        $encuesta = $this->porToken($hotelId, $token);
        if (!$encuesta) {
            return ['success' => false, 'message' => 'Link no valido.', 'mostrar_google' => false];
        }
        if ($encuesta['estado'] === 'respondida') {
            return ['success' => true, 'message' => 'Ya habiamos recibido tu opinion. ¡Gracias!', 'mostrar_google' => $this->calificaBien((int) $encuesta['calificacion'], $hotelId)];
        }
        if ($encuesta['estado'] === 'expirada') {
            return ['success' => false, 'message' => 'Esta encuesta ya expiro. ¡Gracias de todos modos!', 'mostrar_google' => false];
        }

        $calificacion = (int) ($datos['calificacion'] ?? 0);
        $nps = trim((string) ($datos['nps'] ?? ''));
        $comentario = trim((string) ($datos['comentario'] ?? ''));

        if ($calificacion < 1 || $calificacion > 5) {
            return ['success' => false, 'message' => 'Selecciona una calificacion de 1 a 5 estrellas.', 'mostrar_google' => false];
        }
        $npsValor = ($nps !== '' && ctype_digit($nps) && (int) $nps <= 10) ? (int) $nps : null;

        try {
            $this->pdo->prepare(
                "UPDATE reputacion_encuestas
                 SET estado = 'respondida', calificacion = ?, nps = ?, comentario = ?, respondida_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([
                $calificacion,
                $npsValor,
                $comentario !== '' ? mb_substr($comentario, 0, 1000) : null,
                (int) $encuesta['id'],
            ]);
        } catch (Throwable $e) {
            error_log('Reputacion: error al guardar respuesta (encuesta ' . $encuesta['id'] . '): ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo guardar tu opinion. Intenta de nuevo.', 'mostrar_google' => false];
        }

        $umbral = ConfiguracionHotelRegistry::getInt('reputacion.umbral_alerta', 3, $hotelId);
        if ($calificacion <= $umbral) {
            $this->alertarCalificacionBaja($hotelId, $encuesta, $calificacion, $comentario);
        }

        return [
            'success' => true,
            'message' => '¡Gracias por tu opinion!',
            'mostrar_google' => $this->calificaBien($calificacion, $hotelId),
        ];
    }

    /** Checkouts recientes del hotel con el estado de su encuesta. */
    public function tablero(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id AS reservacion_id, r.fecha_salida,
                        h.nombre_completo, h.telefono, h.email,
                        e.id AS encuesta_id, e.token, e.estado AS encuesta_estado,
                        e.calificacion, e.nps, e.comentario, e.canal_envio, e.respondida_at
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 LEFT JOIN reputacion_encuestas e ON e.reservacion_id = r.id AND e.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ?
                   AND r.estado IN ('checked_out', 'completada')
                   AND r.fecha_salida >= CURDATE() - INTERVAL 30 DAY
                 ORDER BY r.fecha_salida DESC, r.id DESC
                 LIMIT 100"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Reputacion: error al listar tablero: ' . $e->getMessage());
            return [];
        }
    }

    /** KPIs de los ultimos 90 dias: promedio, respondidas, tasa de respuesta y NPS. */
    public function kpis(int $hotelId): array
    {
        $vacio = ['promedio' => null, 'respondidas' => 0, 'generadas' => 0, 'tasa_respuesta' => null, 'nps' => null];

        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) AS generadas,
                        SUM(estado = 'respondida') AS respondidas,
                        AVG(CASE WHEN estado = 'respondida' THEN calificacion END) AS promedio,
                        SUM(CASE WHEN estado = 'respondida' AND nps >= 9 THEN 1 ELSE 0 END) AS promotores,
                        SUM(CASE WHEN estado = 'respondida' AND nps <= 6 THEN 1 ELSE 0 END) AS detractores,
                        SUM(CASE WHEN estado = 'respondida' AND nps IS NOT NULL THEN 1 ELSE 0 END) AS con_nps
                 FROM reputacion_encuestas
                 WHERE hotel_id = ? AND created_at >= CURDATE() - INTERVAL 90 DAY"
            );
            $stmt->execute([$hotelId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return $vacio;
            }

            $generadas = (int) $row['generadas'];
            $respondidas = (int) $row['respondidas'];
            $conNps = (int) $row['con_nps'];

            return [
                'promedio' => $row['promedio'] !== null ? round((float) $row['promedio'], 1) : null,
                'respondidas' => $respondidas,
                'generadas' => $generadas,
                'tasa_respuesta' => $generadas > 0 ? (int) round($respondidas * 100 / $generadas) : null,
                'nps' => $conNps > 0 ? (int) round(((int) $row['promotores'] - (int) $row['detractores']) * 100 / $conNps) : null,
            ];
        } catch (Throwable $e) {
            error_log('Reputacion: error al calcular KPIs: ' . $e->getMessage());
            return $vacio;
        }
    }

    /**
     * Envia la encuesta por correo al huesped. Devuelve ['ok', 'error'].
     * Remitente: reportes.email_remitente o contacto.email del hotel.
     */
    public function enviarPorCorreo(int $hotelId, int $encuestaId, string $linkPublico, string $nombreHotel): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT e.id, e.estado, h.email, h.nombre_completo
                 FROM reputacion_encuestas e
                 INNER JOIN reservaciones r ON r.id = e.reservacion_id AND r.hotel_id = e.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE e.hotel_id = ? AND e.id = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $encuestaId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Reputacion: error al leer encuesta para correo: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo leer la encuesta.'];
        }

        if (!$fila || $fila['estado'] === 'respondida' || $fila['estado'] === 'expirada') {
            return ['ok' => false, 'error' => 'La encuesta no esta disponible para envio.'];
        }

        $email = trim((string) ($fila['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'El huesped no tiene un correo valido registrado.'];
        }

        $remitente = trim((string) hotel_config_get('reportes.email_remitente', '', $hotelId));
        if ($remitente === '') {
            $remitente = trim((string) hotel_config_get('contacto.email', '', $hotelId));
        }
        if ($remitente === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Configura el correo remitente del hotel (Configuracion > Contacto).'];
        }
        if (!function_exists('mail')) {
            return ['ok' => false, 'error' => 'La funcion mail() no esta disponible en este servidor.'];
        }

        $nombreHuesped = trim((string) ($fila['nombre_completo'] ?? ''));
        $asunto = $this->mimeHeader('¿Como fue tu estancia en ' . $nombreHotel . '?');
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->mimeHeader($nombreHotel) . ' <' . $remitente . '>',
            'Reply-To: ' . $remitente,
            'X-Mailer: Medisoft Hoteles',
        ];

        $ok = @mail($email, $asunto, $this->htmlCorreo($nombreHotel, $nombreHuesped, $linkPublico), implode("\r\n", $headers));
        if (!$ok) {
            return ['ok' => false, 'error' => 'El servidor no acepto el envio del correo.'];
        }

        try {
            $this->pdo->prepare(
                "UPDATE reputacion_encuestas
                 SET estado = 'enviada', canal_envio = 'correo', enviada_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$encuestaId]);
        } catch (Throwable $e) {
            error_log('Reputacion: correo enviado pero no se pudo marcar (encuesta ' . $encuestaId . '): ' . $e->getMessage());
        }

        return ['ok' => true, 'error' => null];
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function calificaBien(int $calificacion, int $hotelId): bool
    {
        $url = trim((string) ConfiguracionHotelRegistry::get('reputacion.google_review_url', '', $hotelId));
        return $calificacion >= 4 && $url !== '';
    }

    /** Alerta interna de calificacion baja. Best-effort: jamas rompe la respuesta del huesped. */
    private function alertarCalificacionBaja(int $hotelId, array $encuesta, int $calificacion, string $comentario): void
    {
        try {
            require_once __DIR__ . '/NotificacionService.php';

            $nombre = trim((string) ($encuesta['nombre_completo'] ?? 'Huesped'));
            $mensaje = $nombre . ' califico su estancia con ' . $calificacion . '/5 (reservacion #' . (int) $encuesta['reservacion_id'] . ').';
            if ($comentario !== '') {
                $mensaje .= ' Comentario: "' . mb_substr($comentario, 0, 180) . '"';
            }

            NotificacionService::crear([
                'hotel_id' => $hotelId,
                'rol_destino' => 'gerente',
                'modulo' => 'reputacion',
                'tipo' => 'calificacion_baja',
                'severidad' => 'alta',
                'titulo' => 'Calificacion baja de un huesped',
                'mensaje' => $mensaje,
                'entidad_tipo' => 'reputacion_encuesta',
                'entidad_id' => (int) $encuesta['id'],
                'url' => 'reputacion',
                'dedupe_key' => 'reputacion_baja_' . (int) $encuesta['id'],
            ]);
        } catch (Throwable $e) {
            error_log('Reputacion: no se pudo crear alerta de calificacion baja: ' . $e->getMessage());
        }
    }

    private function htmlCorreo(string $nombreHotel, string $nombreHuesped, string $linkPublico): string
    {
        $hotelSeguro = htmlspecialchars($nombreHotel, ENT_QUOTES, 'UTF-8');
        $saludo = $nombreHuesped !== ''
            ? 'Hola ' . htmlspecialchars($nombreHuesped, ENT_QUOTES, 'UTF-8') . ','
            : 'Hola,';
        $linkSeguro = htmlspecialchars($linkPublico, ENT_QUOTES, 'UTF-8');

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">'
            . $saludo . '<br><br>Gracias por hospedarte en <strong>' . $hotelSeguro . '</strong>. '
            . 'Nos encantaria saber como fue tu estancia; contestar toma menos de un minuto.'
            . '<div style="margin-top:22px;"><a href="' . $linkSeguro . '" style="display:inline-block;background:#1B2746;color:#ffffff;text-decoration:none;border-radius:8px;padding:12px 18px;font-weight:bold;">Calificar mi estancia</a></div>'
            . '<p style="margin-top:22px;color:#667085;font-size:13px;">Si el boton no abre, copia y pega este link en tu navegador:<br><a href="' . $linkSeguro . '" style="color:#1B2746;">' . $linkSeguro . '</a></p>'
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
