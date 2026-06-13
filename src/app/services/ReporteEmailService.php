<?php

require_once __DIR__ . '/../helpers/hotel_config.php';

class ReporteEmailService {
    public function enviarLink(array $registro, string $linkPublico, array $opciones = []): array {
        $hotelId = (int)($registro['hotel_id'] ?? 0);
        $config = $this->configuracion($hotelId);
        $titulo = trim((string)($registro['titulo'] ?? 'Reporte PDF'));
        $hotel = $this->nombreHotel();
        $expira = $this->formatearFecha((string)($registro['expira_en'] ?? ''));

        $destinatarios = $opciones['destinatarios'] ?? $config['destinatarios'];
        $destinatarios = $this->normalizarEmails($destinatarios);
        $asunto = $this->renderTemplate($config['asunto'], [
            'hotel' => $hotel,
            'titulo' => $titulo,
            'link' => $linkPublico,
            'expira' => $expira,
        ]);
        $mensaje = $this->renderTemplate($config['mensaje'], [
            'hotel' => $hotel,
            'titulo' => $titulo,
            'link' => $linkPublico,
            'expira' => $expira,
        ]);

        $resultadoBase = [
            'ok' => false,
            'estado' => 'fallido',
            'destinatarios' => $destinatarios,
            'asunto' => $asunto,
            'error' => null,
        ];

        if (!$config['activo']) {
            $resultadoBase['error'] = 'El envio por correo no esta activo en configuracion.';
            return $resultadoBase;
        }

        if (empty($destinatarios)) {
            $resultadoBase['error'] = 'No hay destinatarios de correo configurados.';
            return $resultadoBase;
        }

        if ($config['remitente'] === '') {
            $resultadoBase['error'] = 'No hay correo remitente configurado.';
            return $resultadoBase;
        }

        if (!function_exists('mail')) {
            $resultadoBase['error'] = 'La funcion mail() no esta disponible en este servidor.';
            return $resultadoBase;
        }

        $headers = $this->headers($config['remitente'], $config['nombre_remitente']);
        $body = $this->htmlBody($mensaje, $linkPublico);
        $to = implode(', ', $destinatarios);
        $ok = @mail($to, $this->mimeHeader($asunto), $body, implode("\r\n", $headers));

        if (!$ok) {
            $resultadoBase['error'] = 'El servidor no acepto el envio del correo.';
            return $resultadoBase;
        }

        $resultadoBase['ok'] = true;
        $resultadoBase['estado'] = 'enviado';
        $resultadoBase['error'] = null;

        return $resultadoBase;
    }

    private function configuracion(int $hotelId): array {
        $contactEmail = (string) hotel_config_get('contacto.email', '', $hotelId ?: null);
        $remitente = trim((string) hotel_config_get('reportes.email_remitente', '', $hotelId ?: null));
        $nombreRemitente = trim((string) hotel_config_get('reportes.email_nombre_remitente', '', $hotelId ?: null));
        $asunto = trim((string) hotel_config_get('reportes.email_asunto', 'Reporte disponible - {hotel}', $hotelId ?: null));
        $mensaje = trim((string) hotel_config_get(
            'reportes.email_mensaje',
            "Hola,\n\nEl reporte {titulo} de {hotel} ya esta disponible.\n\nLink seguro: {link}\nVigencia: {expira}",
            $hotelId ?: null
        ));

        if ($remitente === '') {
            $remitente = trim($contactEmail);
        }

        if ($nombreRemitente === '') {
            $nombreRemitente = $this->nombreHotel();
        }

        if ($asunto === '') {
            $asunto = 'Reporte disponible - {hotel}';
        }

        if ($mensaje === '') {
            $mensaje = "Hola,\n\nEl reporte {titulo} de {hotel} ya esta disponible.\n\nLink seguro: {link}\nVigencia: {expira}";
        }

        return [
            'activo' => function_exists('hotel_report_email_enabled')
                ? hotel_report_email_enabled($hotelId ?: null)
                : (bool) hotel_config_get('reportes.email_envio_activo', false, $hotelId ?: null),
            'destinatarios' => function_exists('hotel_report_email_recipients')
                ? hotel_report_email_recipients($hotelId ?: null)
                : hotel_config_get('reportes.email_destinatarios', '', $hotelId ?: null),
            'remitente' => filter_var($remitente, FILTER_VALIDATE_EMAIL) ? $remitente : '',
            'nombre_remitente' => $this->limpiarHeader($nombreRemitente),
            'asunto' => $this->limpiarHeader($asunto),
            'mensaje' => $mensaje,
        ];
    }

    private function normalizarEmails($value): array {
        if (is_array($value)) {
            $emails = $value;
        } else {
            $emails = preg_split('/[,;\r\n]+/', (string)$value);
        }

        $emails = array_values(array_unique(array_filter(array_map('trim', $emails), static function ($email) {
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        })));

        return $emails;
    }

    private function headers(string $remitente, string $nombreRemitente): array {
        $nombre = $this->mimeHeader($nombreRemitente);
        $from = $nombre !== '' ? $nombre . ' <' . $remitente . '>' : $remitente;

        return [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from,
            'Reply-To: ' . $remitente,
            'X-Mailer: Medisoft Hoteles',
        ];
    }

    private function htmlBody(string $mensaje, string $linkPublico): string {
        $mensajeSeguro = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));
        $linkSeguro = htmlspecialchars($linkPublico, ENT_QUOTES, 'UTF-8');

        return '<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#172033;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7fb;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="padding:24px;font-size:15px;line-height:1.6;">' . $mensajeSeguro
            . '<div style="margin-top:22px;"><a href="' . $linkSeguro . '" style="display:inline-block;background:#1B2746;color:#ffffff;text-decoration:none;border-radius:8px;padding:12px 18px;font-weight:bold;">Abrir reporte PDF</a></div>'
            . '<p style="margin-top:22px;color:#667085;font-size:13px;">Si el boton no abre, copia y pega este link en tu navegador:<br><a href="' . $linkSeguro . '" style="color:#1B2746;">' . $linkSeguro . '</a></p>'
            . '</td></tr></table>'
            . '</td></tr></table>'
            . '</body></html>';
    }

    private function renderTemplate(string $template, array $vars): string {
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{' . $key . '}'] = (string)$value;
        }

        return strtr($template, $replace);
    }

    private function limpiarHeader(string $value): string {
        $value = preg_replace('/[\r\n]+/', ' ', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return trim((string)$value);
    }

    private function mimeHeader(string $value): string {
        $value = $this->limpiarHeader($value);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n");
        }

        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function nombreHotel(): string {
        if (function_exists('current_hotel_display_name')) {
            $nombre = trim((string) current_hotel_display_name());
            if ($nombre !== '') {
                return $nombre;
            }
        }

        return 'Hotel';
    }

    private function formatearFecha(string $value): string {
        $timestamp = strtotime($value);

        return $timestamp ? date('d/m/Y H:i', $timestamp) : 'sin fecha definida';
    }
}
