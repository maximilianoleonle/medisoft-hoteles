<?php
/**
 * Helpers base para configuracion por hotel.
 *
 * Fase 1: no se cargan automaticamente ni reemplazan la configuracion actual.
 * Estan listos para integrarse gradualmente cuando exista TenantContext activo.
 */

if (!function_exists('hotel_config_get')) {
    function hotel_config_get($clave, $default = null, $hotelId = null)
    {
        $hotelId = hotel_config_resolve_hotel_id($hotelId);

        if (!$hotelId || !class_exists('Database')) {
            return $default;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT valor, tipo FROM hotel_configuracion WHERE hotel_id = ? AND clave = ? AND activo = 1 LIMIT 1",
            [$hotelId, $clave]
        );

        if (!$stmt) {
            return $default;
        }

        $row = $stmt->fetch();

        if (!$row) {
            return $default;
        }

        return hotel_config_cast_value($row['valor'], $row['tipo'], $default);
    }
}

if (!function_exists('hotel_feature_enabled')) {
    function hotel_feature_enabled($clave, $hotelId = null, $default = false)
    {
        $clave = strpos($clave, 'feature.') === 0 ? $clave : 'feature.' . $clave;

        return (bool) hotel_config_get($clave, $default, $hotelId);
    }
}

if (!function_exists('hotel_config_resolve_hotel_id')) {
    function hotel_config_resolve_hotel_id($hotelId = null)
    {
        if ($hotelId) {
            return (int) $hotelId;
        }

        if (class_exists('TenantContext') && TenantContext::hotelId()) {
            return (int) TenantContext::hotelId();
        }

        return null;
    }
}

if (!function_exists('obtenerHotelIdActualCompat')) {
    function obtenerHotelIdActualCompat()
    {
        static $hotelId = null;

        if ($hotelId !== null) {
            return $hotelId;
        }

        if (class_exists('TenantContext') && method_exists('TenantContext', 'hotelId')) {
            $tenantHotelId = TenantContext::hotelId();

            if ($tenantHotelId) {
                $hotelId = (int) $tenantHotelId;
                return $hotelId;
            }
        }

        if (!class_exists('Database')) {
            $databasePath = dirname(__DIR__, 2) . '/core/Database.php';

            if (file_exists($databasePath)) {
                require_once $databasePath;
            }
        }

        if (!class_exists('Database')) {
            throw new RuntimeException('No se pudo resolver la base de datos para obtener el hotel actual.');
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id FROM hoteles WHERE slug = ? AND activo = 1 LIMIT 1",
            ['los-cedros']
        );

        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar el hotel actual de compatibilidad.');
        }

        $row = $stmt->fetch();

        if (!$row || empty($row['id'])) {
            throw new RuntimeException('No se encontro el hotel de compatibilidad los-cedros.');
        }

        $hotelId = (int) $row['id'];
        return $hotelId;
    }
}

if (!function_exists('hotel_config_cast_value')) {
    function hotel_config_cast_value($valor, $tipo, $default = null)
    {
        switch ($tipo) {
            case 'integer':
                return (int) $valor;

            case 'float':
                return (float) $valor;

            case 'boolean':
                return filter_var($valor, FILTER_VALIDATE_BOOLEAN);

            case 'json':
                $decoded = json_decode((string) $valor, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;

            case 'string':
            default:
                return $valor;
        }
    }
}

if (!function_exists('hotel_config_feature_defaults')) {
    function hotel_config_feature_defaults()
    {
        return [
            'feature.multi_hotel' => false,
            'feature.selector_hotel' => false,
            'feature.audit_logs' => false,
            'feature.hotel_configuracion' => false,
            'feature.saas_billing' => false,
        ];
    }
}
