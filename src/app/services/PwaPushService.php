<?php

require_once __DIR__ . '/../models/PwaPushSubscription.php';
require_once __DIR__ . '/../models/HotelBranding.php';
require_once __DIR__ . '/../helpers/branding.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class PwaPushService {
    private $subscriptionModel;
    private $config;

    public function __construct() {
        $this->subscriptionModel = new PwaPushSubscription();
        $configPath = APP_PATH . '/config/pwa.php';
        $this->config = is_readable($configPath)
            ? (require $configPath)
            : [];
    }

    public function estadoCliente(int $hotelId): array {
        $enabled = $this->estaActivoParaHotel($hotelId);
        $publicKey = $this->vapidPublicKey();
        $configured = $this->estaConfigurado();

        return [
            'success' => true,
            'enabled' => $enabled && $configured,
            'configured' => $configured,
            'public_key' => $configured ? $publicKey : '',
            'message' => $this->mensajeEstado($enabled, $configured),
        ];
    }

    public function guardarSuscripcion(int $hotelId, ?int $usuarioId, array $subscription, string $userAgent = ''): bool {
        if (!$this->estaActivoParaHotel($hotelId)) {
            return false;
        }

        return $this->subscriptionModel->guardarSuscripcion($hotelId, $usuarioId, $subscription, $userAgent);
    }

    public function desactivarSuscripcion(int $hotelId, string $endpoint): bool {
        return $this->subscriptionModel->desactivarSuscripcion($hotelId, $endpoint);
    }

    public function enviarPrueba(int $hotelId): array {
        $configurado = $this->estaConfigurado();
        $activo = $this->estaActivoParaHotel($hotelId);
        $dispositivos = ($configurado && $activo)
            ? count($this->subscriptionModel->listarActivasPorHotel($hotelId))
            : 0;

        $resultado = $this->enviarPayloadHotel($hotelId, [
            'title' => 'Notificaciones activas',
            'body' => 'Este dispositivo ya puede recibir avisos de Medisoft Hoteles.',
            'url' => 'notificaciones',
            'tag' => 'pwa-push-test-' . $hotelId,
            'severity' => 'info',
        ]);

        $resultado['diagnostico'] = [
            'configurado' => $configurado,
            'activo_en_hotel' => $activo,
            'dispositivos_activos' => $dispositivos,
            'enviados' => (int)($resultado['sent'] ?? 0),
            'fallidos' => (int)($resultado['failed'] ?? 0),
        ];

        return $resultado;
    }

    public function enviarNotificacion(array $notificacion, ?int $notificacionId = null): array {
        $hotelId = (int)($notificacion['hotel_id'] ?? 0);
        if ($hotelId <= 0 || !$this->debeEnviarNotificacion($notificacion, $hotelId)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $rolesDestino = $this->rolesDestinoParaNotificacion($notificacion);
        $urlPush = $notificacionId
            ? 'notificaciones/' . (int)$notificacionId . '/abrir'
            : $this->normalizarUrlPush((string)($notificacion['url'] ?? 'notificaciones'));

        return $this->enviarPayloadHotel($hotelId, [
            'title' => $this->limitarTexto((string)($notificacion['titulo'] ?? 'Notificacion'), 90),
            'body' => $this->limitarTexto((string)($notificacion['mensaje'] ?? 'Nuevo aviso operativo.'), 180),
            'url' => $urlPush,
            'tag' => 'notificacion-' . ($notificacionId ?: hash('crc32b', serialize($notificacion))),
            'severity' => (string)($notificacion['severidad'] ?? 'info'),
            'module' => (string)($notificacion['modulo'] ?? 'sistema'),
            'notification_id' => $notificacionId,
            'roles' => $rolesDestino,
        ], $rolesDestino);
    }

    /**
     * Envio dirigido a usuarios concretos (por id), sin ruteo por rol.
     * Lo usa el Guardian: sus alertas van SOLO a quienes tienen el permiso
     * guardian.view (resuelto contra roles.permisos_json), no a un rol-string.
     */
    public function enviarDirectoAUsuarios(int $hotelId, array $payload, array $usuarioIds): array {
        $usuarioIds = array_values(array_unique(array_filter(array_map('intval', $usuarioIds))));
        if (empty($usuarioIds) || !$this->estaActivoParaHotel($hotelId) || !$this->estaConfigurado()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $payload['title'] = $this->limitarTexto((string)($payload['title'] ?? 'Notificacion'), 90);
        $payload['body'] = $this->limitarTexto((string)($payload['body'] ?? ''), 180);
        $payload['url'] = $this->normalizarUrlPush((string)($payload['url'] ?? 'notificaciones'));
        $payload = $this->agregarBrandingAlPayload($hotelId, $payload);

        $suscripciones = $this->subscriptionModel->listarActivasPorHotelUsuarios($hotelId, $usuarioIds);
        $resultado = ['sent' => 0, 'failed' => 0, 'skipped' => false];

        foreach ($suscripciones as $suscripcion) {
            $envio = $this->enviarWebPush($suscripcion, $payload);

            if (!empty($envio['ok'])) {
                $resultado['sent']++;
                continue;
            }

            $resultado['failed']++;
            $status = (int)($envio['status'] ?? 0);
            if (in_array($status, [404, 410], true)) {
                $this->subscriptionModel->desactivarPorId((int)($suscripcion['id'] ?? 0));
            }
        }

        return $resultado;
    }

    private function enviarPayloadHotel(int $hotelId, array $payload, array $rolesDestino = []): array {
        if (!$this->estaActivoParaHotel($hotelId) || !$this->estaConfigurado()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $payload = $this->agregarBrandingAlPayload($hotelId, $payload);

        $suscripciones = !empty($rolesDestino)
            ? $this->subscriptionModel->listarActivasPorHotelRoles($hotelId, $rolesDestino)
            : $this->subscriptionModel->listarActivasPorHotel($hotelId);
        $resultado = ['sent' => 0, 'failed' => 0, 'skipped' => false];

        foreach ($suscripciones as $suscripcion) {
            $envio = $this->enviarWebPush($suscripcion, $payload);

            if (!empty($envio['ok'])) {
                $resultado['sent']++;
                continue;
            }

            $resultado['failed']++;
            $status = (int)($envio['status'] ?? 0);
            if (in_array($status, [404, 410], true)) {
                $this->subscriptionModel->desactivarPorId((int)($suscripcion['id'] ?? 0));
            }
        }

        return $resultado;
    }

    private function agregarBrandingAlPayload(int $hotelId, array $payload): array {
        $branding = function_exists('hotel_branding') ? hotel_branding($hotelId) : [];

        $icon = function_exists('hotel_branding_pwa_icon_asset_url')
            ? (
                hotel_branding_pwa_icon_asset_url($branding['pwa_icon_192_url'] ?? null, 192)
                ?: hotel_branding_pwa_icon_asset_url($branding['pwa_icon_512_url'] ?? null, 512)
            )
            : null;

        if (!$icon && function_exists('hotel_branding_asset_url')) {
            $icon = hotel_branding_asset_url($branding['logo_url'] ?? null);
        }

        $payload['icon'] = $this->normalizarAssetPush($payload['icon'] ?? $icon ?? 'img/icons/icon-192x192.png');
        $payload['badge'] = $this->normalizarAssetPush($payload['badge'] ?? $payload['icon'] ?? 'img/icons/icon-72x72.png');

        return $payload;
    }

    private function normalizarAssetPush($url): string {
        $url = trim((string)$url);
        if ($url === '') {
            return 'img/icons/icon-192x192.png';
        }

        $localAsset = $this->normalizarAssetPushLocal($url);
        if ($localAsset !== null) {
            return $localAsset;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        return ltrim($url, '/');
    }

    private function normalizarAssetPushLocal(string $url): ?string {
        $partes = parse_url($url);
        if ($partes === false) {
            return null;
        }

        $path = (string)($partes['path'] ?? $url);
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (!$this->esRutaAssetPushLocalPermitida($path)) {
            return null;
        }

        if (defined('PUBLIC_PATH') && !is_file(PUBLIC_PATH . '/' . $path)) {
            return null;
        }

        $query = isset($partes['query']) && $partes['query'] !== ''
            ? '?' . $partes['query']
            : '';

        return $path . $query;
    }

    private function esRutaAssetPushLocalPermitida(string $path): bool {
        if ($path === '' || preg_match('/[\x00-\x1F<>"\']/', $path)) {
            return false;
        }

        $extension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?: $path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'ico'], true)) {
            return false;
        }

        foreach (['uploads/branding/', 'uploads/', 'img/icons/', 'img/'] as $prefijo) {
            if (strpos($path, $prefijo) === 0) {
                return true;
            }
        }

        return false;
    }

    private function enviarWebPush(array $suscripcion, array $payload): array {
        try {
            $endpoint = (string)($suscripcion['endpoint'] ?? '');
            $endpointParts = parse_url($endpoint);
            if (empty($endpointParts['scheme']) || empty($endpointParts['host'])) {
                return ['ok' => false, 'status' => 0, 'error' => 'endpoint_invalido'];
            }

            $body = $this->construirPayloadCifrado($suscripcion, $payload);
            if ($body === null) {
                return ['ok' => false, 'status' => 0, 'error' => 'payload_no_cifrado'];
            }

            $headers = [
                'TTL: ' . (int)($this->config['push']['ttl'] ?? 3600),
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Authorization: ' . $this->crearVapidAuthorization($endpoint),
            ];

            return $this->postBinario($endpoint, $body, $headers);
        } catch (Throwable $e) {
            error_log('No se pudo enviar PWA push: ' . $e->getMessage());
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    private function construirPayloadCifrado(array $suscripcion, array $payload): ?string {
        $receiverPublicKey = $this->base64UrlDecode((string)($suscripcion['p256dh'] ?? ''));
        $authSecret = $this->base64UrlDecode((string)($suscripcion['auth'] ?? ''));

        if (strlen($receiverPublicKey) !== 65 || strlen($authSecret) < 16) {
            return null;
        }

        $localKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if (!$localKey) {
            return null;
        }

        $localDetails = openssl_pkey_get_details($localKey);
        $senderPublicKey = $this->publicPointFromDetails($localDetails);
        $receiverPem = $this->ecPublicKeyPemFromPoint($receiverPublicKey);
        $receiverKey = openssl_pkey_get_public($receiverPem);

        if (!$receiverKey || strlen($senderPublicKey) !== 65) {
            return null;
        }

        $sharedSecret = openssl_pkey_derive($receiverKey, $localKey, 32);
        if ($sharedSecret === false || strlen($sharedSecret) === 0) {
            return null;
        }

        $ikm = $this->hkdf(
            hash_hmac('sha256', $sharedSecret, $authSecret, true),
            "WebPush: info\0" . $receiverPublicKey . $senderPublicKey,
            32,
            ''
        );

        $salt = random_bytes(16);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = $this->hkdf($prk, "Content-Encoding: aes128gcm\0", 16, '');
        $nonce = $this->hkdf($prk, "Content-Encoding: nonce\0", 12, '');

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            return null;
        }

        $plaintext = $payloadJson . "\x02";
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        if ($ciphertext === false || strlen($tag) !== 16) {
            return null;
        }

        return $salt . pack('N', 4096) . chr(strlen($senderPublicKey)) . $senderPublicKey . $ciphertext . $tag;
    }

    private function crearVapidAuthorization(string $endpoint): string {
        $publicKey = $this->vapidPublicKey();
        $privateScalar = $this->base64UrlDecode($this->vapidPrivateKey());
        $publicPoint = $this->base64UrlDecode($publicKey);
        $privatePem = $this->ecPrivateKeyPemFromScalar($privateScalar, $publicPoint);
        $audience = $this->endpointAudience($endpoint);

        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => (string)($this->config['push']['vapid_subject'] ?? 'mailto:soporte@medisoft.mx'),
        ]));

        $unsigned = $header . '.' . $claims;
        $signatureDer = '';
        if (!openssl_sign($unsigned, $signatureDer, $privatePem, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar VAPID.');
        }

        $signature = $this->ecdsaDerToRaw($signatureDer);
        if ($signature === null) {
            throw new RuntimeException('Firma VAPID invalida.');
        }

        return 'vapid t=' . $unsigned . '.' . $this->base64UrlEncode($signature) . ', k=' . $publicKey;
    }

    private function postBinario(string $url, string $body, array $headers): array {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 10,
            ]);

            curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'ok' => $status >= 200 && $status < 300,
                'status' => $status,
                'error' => $error,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $status = 0;
        foreach (($http_response_header ?? []) as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) {
                $status = (int)$m[1];
                break;
            }
        }

        return [
            'ok' => $response !== false && $status >= 200 && $status < 300,
            'status' => $status,
            'error' => $response === false ? 'http_request_failed' : '',
        ];
    }

    private function estaActivoParaHotel(int $hotelId): bool {
        if (empty($this->config['push']['enabled'])) {
            return false;
        }

        return function_exists('hotel_config_get')
            ? (bool)hotel_config_get('notificaciones.pwa_push_activo', true, $hotelId)
            : true;
    }

    private function estaConfigurado(): bool {
        $public = $this->base64UrlDecode($this->vapidPublicKey());
        $private = $this->base64UrlDecode($this->vapidPrivateKey());

        return strlen($public) === 65 && strlen($private) === 32;
    }

    private function mensajeEstado(bool $enabled, bool $configured): string {
        if (!$enabled) {
            return 'Las notificaciones PWA estan desactivadas para este hotel.';
        }

        if (!$configured) {
            return 'Faltan las claves VAPID para enviar notificaciones PWA.';
        }

        return 'Listo para activar notificaciones en este dispositivo.';
    }

    private function debeEnviarNotificacion(array $notificacion, int $hotelId): bool {
        $estado = (string)($notificacion['estado'] ?? 'nueva');
        if ($estado !== '' && $estado !== 'nueva') {
            return false;
        }

        $modulo = strtolower((string)($notificacion['modulo'] ?? ''));
        $tipo = strtolower((string)($notificacion['tipo'] ?? ''));
        $severidad = strtolower((string)($notificacion['severidad'] ?? 'info'));
        $esAutomatica = strpos($tipo, 'regla_') === 0;

        foreach (['link_seguro', 'reporte_link', 'email_fallido', 'correo_fallido'] as $omitido) {
            if (strpos($tipo, $omitido) !== false) {
                return false;
            }
        }

        if (function_exists('hotel_config_get')) {
            if ((bool)hotel_config_get('notificaciones.pwa_push_solo_prioritarias', false, $hotelId)
                && !in_array($severidad, ['alta', 'critica'], true)) {
                return false;
            }

            if ($esAutomatica && !(bool)hotel_config_get('notificaciones.pwa_push_automaticas', true, $hotelId)) {
                return false;
            }

            if (!$esAutomatica && !(bool)hotel_config_get('notificaciones.pwa_push_eventos', true, $hotelId)) {
                return false;
            }
        }

        return true;
    }

    private function rolesDestinoParaNotificacion(array $notificacion): array {
        $rolesConfigurados = $this->parseRolesDestino($notificacion['rol_destino'] ?? '');
        if (!empty($rolesConfigurados)) {
            return $this->expandirRolesDestino($rolesConfigurados);
        }

        $modulo = strtolower((string)($notificacion['modulo'] ?? 'sistema'));
        $tipo = strtolower((string)($notificacion['tipo'] ?? ''));
        $severidad = strtolower((string)($notificacion['severidad'] ?? 'info'));
        $texto = strtolower(trim($tipo . ' ' . (string)($notificacion['titulo'] ?? '') . ' ' . (string)($notificacion['mensaje'] ?? '')));

        if ($severidad === 'critica') {
            return $this->expandirRolesDestino(['gerente']);
        }

        if (in_array($modulo, ['caja', 'facturacion', 'reportes'], true)) {
            return $this->expandirRolesDestino(['gerente']);
        }

        if ($modulo === 'habitaciones') {
            if (strpos($texto, 'mantenimiento') !== false) {
                return $this->expandirRolesDestino(['mantenimiento']);
            }

            if (strpos($texto, 'limpieza') !== false) {
                return $this->expandirRolesDestino(['limpieza']);
            }

            return $this->expandirRolesDestino(['recepcionista']);
        }

        if ($modulo === 'reservaciones'
            || strpos($texto, 'check-in') !== false
            || strpos($texto, 'check_in') !== false
            || strpos($texto, 'check-out') !== false
            || strpos($texto, 'check_out') !== false) {
            return $this->expandirRolesDestino(['recepcionista']);
        }

        return $this->expandirRolesDestino(['gerente']);
    }

    private function parseRolesDestino($valor): array {
        $roles = is_array($valor)
            ? $valor
            : preg_split('/[,;\s]+/', (string)$valor, -1, PREG_SPLIT_NO_EMPTY);

        $aliases = [
            'admin' => 'administrador',
            'administracion' => 'administrador',
            'recepcion' => 'recepcionista',
            'gerencia' => 'gerente',
            'mantenimiento_habitaciones' => 'mantenimiento',
            'housekeeping' => 'limpieza',
        ];

        $normalizados = [];
        foreach ($roles ?: [] as $rol) {
            $rol = strtolower(trim((string)$rol));
            if (function_exists('iconv')) {
                $rolAscii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $rol);
                if ($rolAscii !== false && $rolAscii !== '') {
                    $rol = strtolower(trim($rolAscii));
                }
            }
            $rol = str_replace([' ', '-'], '_', $rol);
            $rol = $aliases[$rol] ?? $rol;

            if (preg_match('/^[a-z_]+$/', $rol)) {
                $normalizados[] = $rol;
            }
        }

        return array_values(array_unique($normalizados));
    }

    private function expandirRolesDestino(array $roles): array {
        // Los roles de gestion (dueno/gerente/admin/superadmin) reciben TODAS las
        // notificaciones, ademas del personal del rol especifico. Asi el push
        // operativo (reservas, limpieza, mantenimiento) tambien llega a gerencia
        // con la app cerrada, no solo a recepcion/administrador.
        $gestion = ['superadmin', 'propietario', 'gerente', 'administrador'];

        $expandir = [
            'gerente' => $gestion,
            'administrador' => $gestion,
            'propietario' => $gestion,
            'superadmin' => $gestion,
            'recepcionista' => array_merge(['recepcionista'], $gestion),
            'limpieza' => array_merge(['limpieza', 'recepcionista'], $gestion),
            'mantenimiento' => array_merge(['mantenimiento', 'recepcionista'], $gestion),
        ];

        // Siempre se incluye la gestion como base (incluso para roles desconocidos).
        $resultado = $gestion;
        foreach ($roles as $rol) {
            $rol = strtolower(trim((string)$rol));
            foreach (($expandir[$rol] ?? [$rol]) as $rolExpandido) {
                if (preg_match('/^[a-z_]+$/', $rolExpandido)) {
                    $resultado[] = $rolExpandido;
                }
            }
        }

        return array_values(array_unique($resultado));
    }

    private function vapidPublicKey(): string {
        return trim((string)($this->config['push']['vapid_public_key'] ?? ''));
    }

    private function vapidPrivateKey(): string {
        return trim((string)($this->config['push']['vapid_private_key'] ?? ''));
    }

    private function endpointAudience(string $endpoint): string {
        $parts = parse_url($endpoint);
        $audience = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

        if (!empty($parts['port'])) {
            $audience .= ':' . (int)$parts['port'];
        }

        return $audience;
    }

    private function normalizarUrlPush(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return 'notificaciones';
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        return ltrim($url, '/');
    }

    private function limitarTexto(string $texto, int $max): string {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)));
        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($texto, 'UTF-8') > $max
                ? rtrim(mb_substr($texto, 0, $max, 'UTF-8'))
                : $texto;
        }

        return strlen($texto) > $max ? rtrim(substr($texto, 0, $max)) : $texto;
    }

    private function publicPointFromDetails($details): string {
        $ec = $details['ec'] ?? [];
        $x = isset($ec['x']) ? str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) : '';
        $y = isset($ec['y']) ? str_pad($ec['y'], 32, "\0", STR_PAD_LEFT) : '';

        return strlen($x) === 32 && strlen($y) === 32 ? "\x04" . $x . $y : '';
    }

    private function hkdf(string $prk, string $info, int $length, string $salt = ''): string {
        if ($salt !== '') {
            $prk = hash_hmac('sha256', $prk, $salt, true);
        }

        $okm = '';
        $previous = '';
        $counter = 1;

        while (strlen($okm) < $length) {
            $previous = hash_hmac('sha256', $previous . $info . chr($counter), $prk, true);
            $okm .= $previous;
            $counter++;
        }

        return substr($okm, 0, $length);
    }

    private function base64UrlEncode($value): string {
        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? '' : $decoded;
    }

    private function ecPublicKeyPemFromPoint(string $point): string {
        $algorithm = $this->derSequence(
            $this->derOid('1.2.840.10045.2.1') .
            $this->derOid('1.2.840.10045.3.1.7')
        );
        $subjectPublicKey = $this->derBitString($point);

        return $this->pem('PUBLIC KEY', $this->derSequence($algorithm . $subjectPublicKey));
    }

    private function ecPrivateKeyPemFromScalar(string $scalar, string $publicPoint): string {
        $scalar = str_pad($scalar, 32, "\0", STR_PAD_LEFT);
        $curve = $this->derOid('1.2.840.10045.3.1.7');
        $body = $this->derIntegerInt(1) .
            $this->derOctetString($scalar) .
            "\xA0" . $this->derLength(strlen($curve)) . $curve;

        if (strlen($publicPoint) === 65) {
            $publicBitString = $this->derBitString($publicPoint);
            $body .= "\xA1" . $this->derLength(strlen($publicBitString)) . $publicBitString;
        }

        return $this->pem('EC PRIVATE KEY', $this->derSequence($body));
    }

    private function ecdsaDerToRaw(string $der): ?string {
        $offset = 0;
        if ($this->readByte($der, $offset) !== 0x30) {
            return null;
        }

        $this->readDerLength($der, $offset);

        if ($this->readByte($der, $offset) !== 0x02) {
            return null;
        }
        $rLen = $this->readDerLength($der, $offset);
        $r = substr($der, $offset, $rLen);
        $offset += $rLen;

        if ($this->readByte($der, $offset) !== 0x02) {
            return null;
        }
        $sLen = $this->readDerLength($der, $offset);
        $s = substr($der, $offset, $sLen);

        $r = str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);

        if (strlen($r) !== 32 || strlen($s) !== 32) {
            return null;
        }

        return $r . $s;
    }

    private function readByte(string $data, int &$offset): ?int {
        if ($offset >= strlen($data)) {
            return null;
        }

        return ord($data[$offset++]);
    }

    private function readDerLength(string $data, int &$offset): int {
        $first = $this->readByte($data, $offset);
        if ($first === null) {
            return 0;
        }

        if ($first < 0x80) {
            return $first;
        }

        $bytes = $first & 0x7F;
        $length = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $length = ($length << 8) | (int)$this->readByte($data, $offset);
        }

        return $length;
    }

    private function derSequence(string $value): string {
        return "\x30" . $this->derLength(strlen($value)) . $value;
    }

    private function derIntegerInt(int $value): string {
        return "\x02\x01" . chr($value);
    }

    private function derOctetString(string $value): string {
        return "\x04" . $this->derLength(strlen($value)) . $value;
    }

    private function derBitString(string $value): string {
        return "\x03" . $this->derLength(strlen($value) + 1) . "\x00" . $value;
    }

    private function derOid(string $oid): string {
        $parts = array_map('intval', explode('.', $oid));
        $first = (40 * $parts[0]) + $parts[1];
        $body = chr($first);

        for ($i = 2; $i < count($parts); $i++) {
            $body .= $this->base128((int)$parts[$i]);
        }

        return "\x06" . $this->derLength(strlen($body)) . $body;
    }

    private function base128(int $value): string {
        $bytes = [chr($value & 0x7F)];
        $value >>= 7;

        while ($value > 0) {
            array_unshift($bytes, chr(($value & 0x7F) | 0x80));
            $value >>= 7;
        }

        return implode('', $bytes);
    }

    private function derLength(int $length): string {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function pem(string $label, string $der): string {
        return "-----BEGIN {$label}-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END {$label}-----\n";
    }
}
