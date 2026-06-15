<?php

require_once __DIR__ . '/../../core/Database.php';

class AuditService
{
    private static $tableChecked = false;
    private static $tableAvailable = false;

    public static function record(string $accion, array $context = []): bool
    {
        try {
            $db = Database::getInstance();

            if (!self::isAvailable($db)) {
                return false;
            }

            $hotelId = self::resolveHotelId($context);
            $usuarioId = self::resolveUsuarioId($context);

            $datosAntes = self::jsonOrNull($context['datos_antes'] ?? null);
            $datosDespues = self::jsonOrNull($context['datos_despues'] ?? null);

            $stmt = $db->query(
                "INSERT INTO logs_auditoria
                    (hotel_id, usuario_id, accion, entidad_tipo, entidad_id, descripcion,
                     datos_antes, datos_despues, ip, user_agent, created_at)
                 VALUES
                    (:hotel_id, :usuario_id, :accion, :entidad_tipo, :entidad_id, :descripcion,
                     :datos_antes, :datos_despues, :ip, :user_agent, NOW())",
                [
                    'hotel_id' => $hotelId,
                    'usuario_id' => $usuarioId,
                    'accion' => self::limit($accion, 80),
                    'entidad_tipo' => self::nullableLimit($context['entidad_tipo'] ?? null, 80),
                    'entidad_id' => self::nullableLimit($context['entidad_id'] ?? null, 80),
                    'descripcion' => self::nullableString($context['descripcion'] ?? null),
                    'datos_antes' => $datosAntes,
                    'datos_despues' => $datosDespues,
                    'ip' => self::nullableLimit($context['ip'] ?? self::clientIp(), 45),
                    'user_agent' => self::nullableLimit($context['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null), 255),
                ]
            );

            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('AuditService no pudo registrar auditoria: ' . $e->getMessage());
            return false;
        }
    }

    public static function loginSuccess(int $usuarioId, ?int $hotelId = null, string $contexto = 'login'): bool
    {
        return self::record('auth.login_success', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'auth',
            'entidad_id' => (string) $usuarioId,
            'descripcion' => 'Login exitoso',
            'datos_despues' => [
                'contexto' => $contexto,
                'success' => true,
            ],
        ]);
    }

    public static function loginFailed(?string $username = null, ?int $hotelId = null, string $contexto = 'login'): bool
    {
        return self::record('auth.login_failed', [
            'hotel_id' => $hotelId,
            'usuario_id' => null,
            'entidad_tipo' => 'auth',
            'entidad_id' => null,
            'descripcion' => 'Login fallido',
            'datos_despues' => [
                'contexto' => $contexto,
                'username' => $username ? self::limit($username, 120) : null,
                'success' => false,
            ],
        ]);
    }

    public static function logout(int $usuarioId, ?int $hotelId = null, string $contexto = 'logout'): bool
    {
        return self::record('auth.logout', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'auth',
            'entidad_id' => (string) $usuarioId,
            'descripcion' => 'Logout exitoso',
            'datos_despues' => [
                'contexto' => $contexto,
                'success' => true,
            ],
        ]);
    }

    private static function isAvailable(Database $db): bool
    {
        if (self::$tableChecked) {
            return self::$tableAvailable;
        }

        self::$tableChecked = true;

        try {
            $stmt = $db->query(
                "SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'logs_auditoria'"
            );

            self::$tableAvailable = $stmt !== false && (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            self::$tableAvailable = false;
            error_log('AuditService no pudo validar logs_auditoria: ' . $e->getMessage());
        }

        return self::$tableAvailable;
    }

    private static function resolveHotelId(array $context): ?int
    {
        if (array_key_exists('hotel_id', $context)) {
            $hotelId = $context['hotel_id'];
            return $hotelId ? (int) $hotelId : null;
        }

        if (function_exists('current_hotel_id')) {
            $hotelId = current_hotel_id();
            return $hotelId ? (int) $hotelId : null;
        }

        if (class_exists('TenantContext')) {
            $hotelId = TenantContext::hotelId();
            return $hotelId ? (int) $hotelId : null;
        }

        return !empty($_SESSION['hotel_id']) ? (int) $_SESSION['hotel_id'] : null;
    }

    private static function resolveUsuarioId(array $context): ?int
    {
        if (array_key_exists('usuario_id', $context)) {
            $usuarioId = $context['usuario_id'];
            return $usuarioId ? (int) $usuarioId : null;
        }

        if (function_exists('user_id')) {
            $usuarioId = user_id();
            return $usuarioId ? (int) $usuarioId : null;
        }

        return !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private static function jsonOrNull($value): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? null : $json;
    }

    private static function clientIp(): ?string
    {
        if (function_exists('get_client_ip')) {
            return get_client_ip();
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }

    private static function nullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function nullableLimit($value, int $limit): ?string
    {
        $value = self::nullableString($value);

        return $value === null ? null : self::limit($value, $limit);
    }

    private static function limit(string $value, int $limit): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit, 'UTF-8');
        }

        return substr($value, 0, $limit);
    }
}
