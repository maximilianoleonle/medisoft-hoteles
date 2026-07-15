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
        'huespedes.campos_registro' => [
            'type' => 'json',
            'default' => [],
            'description' => 'Politica de campos visibles y obligatorios para registro de huespedes y vehiculos.',
        ],
        'motor.publico_activo' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Pagina publica de reservas del hotel encendida (requiere bloque motor_reservas).',
        ],
        'motor.anticipo_tipo' => [
            'type' => 'string',
            'default' => 'porcentaje',
            'description' => 'Como se calcula el anticipo online: porcentaje, primera_noche o monto_fijo.',
        ],
        'motor.anticipo_valor' => [
            'type' => 'float',
            'default' => 30.0,
            'description' => 'Valor del anticipo online: % del total o monto fijo, segun anticipo_tipo.',
        ],
        'motor.min_noches' => [
            'type' => 'integer',
            'default' => 1,
            'description' => 'Minimo de noches reservables desde la pagina publica.',
        ],
        'motor.max_noches' => [
            'type' => 'integer',
            'default' => 30,
            'description' => 'Maximo de noches reservables desde la pagina publica.',
        ],
        'motor.anticipacion_max_dias' => [
            'type' => 'integer',
            'default' => 180,
            'description' => 'Cuantos dias hacia adelante se puede reservar online.',
        ],
        'motor.politica_texto' => [
            'type' => 'string',
            'default' => 'El anticipo confirma tu reservacion. El saldo restante se liquida al llegar al hotel.',
            'description' => 'Politica de reservacion visible en la pagina publica.',
        ],
        'motor.email_confirmacion_activo' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enviar correo de confirmacion al huesped al completar su pago online.',
        ],
        'whatsapp.confirmacion_huesped_activa' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Enviar confirmacion por WhatsApp al huesped cuando paga su reserva online.',
        ],
        'whatsapp.aviso_dueno_activo' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Avisar por WhatsApp al numero del hotel cada reserva online pagada.',
        ],
        'ical.token_exportacion' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Token secreto de los feeds iCal exportados del hotel (bloque canales_ical).',
        ],
        'reputacion.google_review_url' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Link para dejar resena en Google (se muestra al huesped que califica bien).',
        ],
        'reputacion.umbral_alerta' => [
            'type' => 'integer',
            'default' => 3,
            'description' => 'Calificacion (1-5) igual o menor a esta genera alerta interna (bloque reputacion).',
        ],
        'reputacion.dias_vigencia' => [
            'type' => 'integer',
            'default' => 30,
            'description' => 'Dias que sigue valido el link de encuesta despues del checkout.',
        ],
        'lealtad.min_estancias' => [
            'type' => 'integer',
            'default' => 3,
            'description' => 'Estancias completadas para considerar frecuente a un huesped (bloque lealtad).',
        ],
        'lealtad.descuento_pct' => [
            'type' => 'integer',
            'default' => 10,
            'description' => 'Porcentaje de descuento del cupon personal de huesped frecuente.',
        ],
        'lealtad.vigencia_dias' => [
            'type' => 'integer',
            'default' => 90,
            'description' => 'Dias de vigencia del cupon personal desde que se genera.',
        ],
        'copiloto.ia_activa' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Permitir que el copiloto use IA (Claude) para preguntas abiertas. Apagado = solo reglas, sin costo de API.',
        ],
        'nomina.modo' => [
            'type' => 'string',
            'default' => 'simplificada',
            'description' => 'Modo de nomina del negocio: simplificada, hibrida o legal (bloque nomina_avanzada).',
        ],
        'nomina.pais' => [
            'type' => 'string',
            'default' => 'MX',
            'description' => 'Pais cuyas reglas legales de nomina aplican al negocio.',
        ],
        'nomina.redondeo' => [
            'type' => 'string',
            'default' => 'centavos',
            'description' => 'Redondeo de montos de nomina: centavos (2 decimales) o pesos (enteros).',
        ],
        'nomina.permitir_horas_extra' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Permite registrar horas extra como incidencia de nomina.',
        ],
        'nomina.permitir_descuentos_manuales' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Permite descuentos manuales autorizados en la nomina.',
        ],
        'nomina.requiere_aprobacion_cierre' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Un periodo cerrado requiere aprobacion de un segundo paso antes de pagarse.',
        ],
        'nomina.permitir_reapertura' => [
            'type' => 'boolean',
            'default' => false,
            'description' => 'Permite reabrir periodos de nomina cerrados (siempre con motivo y auditoria).',
        ],
        'negocio.giro' => [
            'type' => 'string',
            'default' => 'hotel',
            'description' => 'Giro del negocio (hotel, restaurante, academia...); selecciona el adaptador de nomina.',
        ],
        'canal_whatsapp.confirmacion_activa' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Ofrecer el mensaje de confirmacion de reserva en la cola de Mensajes (bloque canal_whatsapp).',
        ],
        'canal_whatsapp.recordatorio_activo' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Ofrecer el recordatorio "te esperamos manana" para las llegadas del dia siguiente.',
        ],
        'canal_whatsapp.anticipo_activo' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Ofrecer el mensaje informativo de anticipo (instrucciones de deposito; no registra pagos).',
        ],
        'canal_whatsapp.encuesta_activa' => [
            'type' => 'boolean',
            'default' => true,
            'description' => 'Ofrecer la encuesta post-estancia por WhatsApp a las salidas del dia (requiere bloque reputacion).',
        ],
        'canal_whatsapp.datos_deposito' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Cuenta/CLABE y banco que el mensaje de anticipo indica al huesped para depositar.',
        ],
        'canal_whatsapp.link_maps' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Link de Google Maps del hotel que acompana al recordatorio de llegada.',
        ],
        'canal_whatsapp.plantilla_confirmacion' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Plantilla propia del hotel para la confirmacion; vacio = usar la sugerida.',
        ],
        'canal_whatsapp.plantilla_recordatorio' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Plantilla propia del hotel para el recordatorio; vacio = usar la sugerida.',
        ],
        'canal_whatsapp.plantilla_anticipo' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Plantilla propia del hotel para el aviso de anticipo; vacio = usar la sugerida.',
        ],
        'canal_whatsapp.plantilla_encuesta' => [
            'type' => 'string',
            'default' => '',
            'description' => 'Plantilla propia del hotel para la invitacion a la encuesta; vacio = usar la sugerida.',
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
