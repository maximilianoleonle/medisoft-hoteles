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
