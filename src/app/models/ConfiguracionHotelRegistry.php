<?php
/**
 * Registry read-only de configuracion simple por hotel.
 *
 * Microfase 2B: solo lectura segura desde hotel_configuracion.
 */

class ConfiguracionHotelRegistry
{
    private static $definitions = [
        'operacion.checkin_hora' => [
            'type' => 'string',
            'default' => '15:00',
            'description' => 'Hora base de check-in.',
        ],
        'operacion.checkout_hora' => [
            'type' => 'string',
            'default' => '12:00',
            'description' => 'Hora base de check-out.',
        ],
        'operacion.moneda' => [
            'type' => 'string',
            'default' => 'MXN',
            'description' => 'Codigo de moneda operativa.',
        ],
        'contacto.telefono' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Telefono principal de contacto.',
        ],
        'contacto.direccion' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Direccion publica del hotel.',
        ],
        'documentos.mostrar_logo' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Indica si los documentos pueden mostrar logo.',
        ],
        'pwa.nombre_app' => [
            'type' => 'string',
            'default' => 'Medisoft Hoteles',
            'description' => 'Nombre visible sugerido para la app.',
        ],
    ];

    private static $legacyFallbacks = [
        'contacto.direccion' => 'hotel.direccion',
        'operacion.moneda' => 'hotel.moneda_codigo',
    ];

    private static $cache = [];

    public static function get(string $key, $default = null, ?int $hotelId = null)
    {
        if (!self::hasKey($key)) {
            return $default;
        }

        $definition = self::$definitions[$key];
        $resolvedDefault = $default !== null ? $default : $definition['default'];
        $resolvedDefault = self::castValue($resolvedDefault, $definition['type'], $definition['default']);
        $resolvedHotelId = self::resolveHotelId($hotelId);

        if (!$resolvedHotelId) {
            return $resolvedDefault;
        }

        $row = self::rowForKey($resolvedHotelId, $key);

        if (!$row) {
            $row = self::legacyFallbackRow($resolvedHotelId, $key);
        }

        if (!$row) {
            return $resolvedDefault;
        }

        return self::castValue($row['valor'] ?? null, $definition['type'], $resolvedDefault);
    }

    public static function getInt(string $key, int $default = 0, ?int $hotelId = null): int
    {
        if (func_num_args() < 2 && self::hasKey($key)) {
            $default = (int) self::$definitions[$key]['default'];
        }

        $value = self::get($key, $default, $hotelId);

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public static function getBool(string $key, bool $default = false, ?int $hotelId = null): bool
    {
        if (func_num_args() < 2 && self::hasKey($key)) {
            $default = (bool) self::$definitions[$key]['default'];
        }

        $value = self::get($key, $default, $hotelId);
        return self::castValue($value, 'boolean', $default);
    }

    public static function getJson(string $key, array $default = [], ?int $hotelId = null): array
    {
        if (!self::hasKey($key)) {
            return $default;
        }

        if (func_num_args() < 2 && is_array(self::$definitions[$key]['default'])) {
            $default = self::$definitions[$key]['default'];
        }

        $value = self::get($key, $default, $hotelId);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $default;
        }

        return $default;
    }

    public static function getMany(array $keys, ?int $hotelId = null): array
    {
        $result = [];

        foreach ($keys as $key) {
            $key = (string) $key;

            if (!self::hasKey($key)) {
                continue;
            }

            $result[$key] = self::get($key, self::$definitions[$key]['default'], $hotelId);
        }

        return $result;
    }

    public static function allForHotel(?int $hotelId = null): array
    {
        return self::getMany(array_keys(self::$definitions), $hotelId);
    }

    public static function hasKey(string $key): bool
    {
        return array_key_exists($key, self::$definitions);
    }

    public static function definition(string $key): ?array
    {
        if (!self::hasKey($key)) {
            return null;
        }

        return self::$definitions[$key];
    }

    private static function resolveHotelId(?int $hotelId = null): ?int
    {
        if ($hotelId && $hotelId > 0) {
            return $hotelId;
        }

        try {
            if (function_exists('hotel_config_resolve_hotel_id')) {
                $resolved = hotel_config_resolve_hotel_id($hotelId);

                if ($resolved) {
                    return (int) $resolved;
                }
            }

            if (class_exists('TenantContext') && method_exists('TenantContext', 'hotelId')) {
                $tenantHotelId = TenantContext::hotelId();

                if ($tenantHotelId) {
                    return (int) $tenantHotelId;
                }
            }

            if (function_exists('obtenerHotelIdActualCompat')) {
                $compatHotelId = obtenerHotelIdActualCompat();

                if ($compatHotelId) {
                    return (int) $compatHotelId;
                }
            }
        } catch (Throwable $e) {
            error_log('ConfiguracionHotelRegistry no pudo resolver hotel_id: ' . $e->getMessage());
        }

        return null;
    }

    private static function rowForKey(int $hotelId, string $key): ?array
    {
        $rows = self::rowsForHotel($hotelId);
        return $rows[$key] ?? null;
    }

    private static function legacyFallbackRow(int $hotelId, string $key): ?array
    {
        if (empty(self::$legacyFallbacks[$key])) {
            return null;
        }

        return self::rowForKey($hotelId, self::$legacyFallbacks[$key]);
    }

    private static function rowsForHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !class_exists('Database')) {
            return [];
        }

        if (array_key_exists($hotelId, self::$cache)) {
            return self::$cache[$hotelId];
        }

        $keys = array_values(array_unique(array_merge(
            array_keys(self::$definitions),
            array_values(self::$legacyFallbacks)
        )));

        if (empty($keys)) {
            self::$cache[$hotelId] = [];
            return self::$cache[$hotelId];
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($keys), '?'));
            $params = array_merge([$hotelId], $keys);
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT clave, valor, tipo
                 FROM hotel_configuracion
                 WHERE hotel_id = ?
                   AND activo = 1
                   AND clave IN ({$placeholders})",
                $params
            );

            if (!$stmt) {
                self::$cache[$hotelId] = [];
                return self::$cache[$hotelId];
            }

            $rows = [];

            foreach ($stmt->fetchAll() as $row) {
                if (!empty($row['clave'])) {
                    $rows[(string) $row['clave']] = $row;
                }
            }

            self::$cache[$hotelId] = $rows;
            return self::$cache[$hotelId];
        } catch (Throwable $e) {
            error_log('ConfiguracionHotelRegistry no pudo leer hotel_configuracion: ' . $e->getMessage());
            self::$cache[$hotelId] = [];
            return self::$cache[$hotelId];
        }
    }

    private static function castValue($value, string $type, $default)
    {
        switch ($type) {
            case 'integer':
                return is_numeric($value) ? (int) $value : (int) $default;

            case 'float':
                return is_numeric($value) ? (float) $value : (float) $default;

            case 'boolean':
                if (is_bool($value)) {
                    return $value;
                }

                if (is_int($value)) {
                    return $value === 1;
                }

                $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return $filtered === null ? (bool) $default : $filtered;

            case 'json':
                if (is_array($value)) {
                    return $value;
                }

                $decoded = json_decode((string) $value, true);
                return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : $default;

            case 'string':
            default:
                return is_scalar($value) || $value === null ? (string) $value : (string) $default;
        }
    }
}
