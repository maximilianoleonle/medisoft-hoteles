<?php
/**
 * Helpers base para configuracion por hotel.
 *
 * Fase 1: no se cargan automaticamente ni reemplazan la configuracion actual.
 * Estan listos para integrarse gradualmente cuando exista TenantContext activo.
 */

if (!function_exists('hotel_setting')) {
    function hotel_setting($key, $default = null)
    {
        try {
            if (class_exists('ConfiguracionHotelRegistry')) {
                if (func_num_args() < 2) {
                    return ConfiguracionHotelRegistry::get((string) $key);
                }

                return ConfiguracionHotelRegistry::get((string) $key, $default);
            }
        } catch (Throwable $e) {
            error_log('Error en helper hotel_setting: ' . $e->getMessage());
        }

        return $default;
    }
}

if (!function_exists('hotel_setting_bool')) {
    function hotel_setting_bool($key, $default = false)
    {
        try {
            if (class_exists('ConfiguracionHotelRegistry')) {
                if (func_num_args() < 2) {
                    return ConfiguracionHotelRegistry::getBool((string) $key);
                }

                return ConfiguracionHotelRegistry::getBool((string) $key, (bool) $default);
            }
        } catch (Throwable $e) {
            error_log('Error en helper hotel_setting_bool: ' . $e->getMessage());
        }

        return (bool) $default;
    }
}

if (!function_exists('hotel_setting_int')) {
    function hotel_setting_int($key, $default = 0)
    {
        try {
            if (class_exists('ConfiguracionHotelRegistry')) {
                if (func_num_args() < 2) {
                    return ConfiguracionHotelRegistry::getInt((string) $key);
                }

                return ConfiguracionHotelRegistry::getInt((string) $key, (int) $default);
            }
        } catch (Throwable $e) {
            error_log('Error en helper hotel_setting_int: ' . $e->getMessage());
        }

        return (int) $default;
    }
}

if (!function_exists('hotel_setting_json')) {
    function hotel_setting_json($key, $default = [])
    {
        try {
            if (class_exists('ConfiguracionHotelRegistry')) {
                if (func_num_args() < 2) {
                    return ConfiguracionHotelRegistry::getJson((string) $key);
                }

                return ConfiguracionHotelRegistry::getJson((string) $key, is_array($default) ? $default : []);
            }
        } catch (Throwable $e) {
            error_log('Error en helper hotel_setting_json: ' . $e->getMessage());
        }

        return is_array($default) ? $default : [];
    }
}

if (!function_exists('hotel_config_editable_definitions')) {
    function hotel_config_editable_definitions()
    {
        return [
            'operacion.checkin_hora' => [
                'label' => 'Hora de check-in',
                'type' => 'string',
                'input' => 'time',
                'default' => '15:00',
                'grupo' => 'operacion',
                'descripcion' => 'Hora base de check-in.',
                'required' => true,
            ],
            'operacion.checkout_hora' => [
                'label' => 'Hora de check-out',
                'type' => 'string',
                'input' => 'time',
                'default' => '12:00',
                'grupo' => 'operacion',
                'descripcion' => 'Hora base de check-out.',
                'required' => true,
            ],
            'operacion.moneda' => [
                'label' => 'Moneda',
                'type' => 'string',
                'input' => 'currency',
                'default' => 'MXN',
                'grupo' => 'operacion',
                'descripcion' => 'Codigo de moneda operativa.',
                'required' => true,
            ],
            'operacion.zona_horaria' => [
                'label' => 'Zona horaria',
                'type' => 'string',
                'input' => 'timezone',
                'default' => 'America/Mexico_City',
                'grupo' => 'operacion',
                'descripcion' => 'Zona horaria usada para horarios visibles del hotel.',
                'required' => true,
                'max' => 80,
            ],
            'contacto.telefono' => [
                'label' => 'Telefono de contacto',
                'type' => 'string',
                'input' => 'tel',
                'default' => '',
                'grupo' => 'contacto',
                'descripcion' => 'Telefono principal de contacto.',
                'required' => false,
                'max' => 40,
            ],
            'contacto.email' => [
                'label' => 'Email de contacto',
                'type' => 'string',
                'input' => 'email',
                'default' => '',
                'grupo' => 'contacto',
                'descripcion' => 'Correo publico de contacto para el hotel.',
                'required' => false,
                'max' => 160,
            ],
            'contacto.direccion' => [
                'label' => 'Direccion de contacto',
                'type' => 'string',
                'input' => 'text',
                'default' => '',
                'grupo' => 'contacto',
                'descripcion' => 'Direccion publica del hotel.',
                'required' => false,
                'max' => 255,
            ],
            'documentos.mostrar_logo' => [
                'label' => 'Mostrar logo en documentos',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'documentos',
                'descripcion' => 'Indica si los documentos pueden mostrar logo.',
                'required' => false,
            ],
            'reservaciones.politica_reserva' => [
                'label' => 'Politica de reservacion',
                'type' => 'string',
                'input' => 'textarea',
                'default' => '',
                'grupo' => 'reservaciones',
                'descripcion' => 'Texto informativo para confirmar condiciones de reserva sin alterar reglas operativas.',
                'required' => false,
                'max' => 1200,
                'rows' => 4,
            ],
            'reservaciones.politica_cancelacion' => [
                'label' => 'Politica de cancelacion',
                'type' => 'string',
                'input' => 'textarea',
                'default' => '',
                'grupo' => 'reservaciones',
                'descripcion' => 'Texto visible para explicar condiciones de cancelacion; no modifica penalizaciones ni caja.',
                'required' => false,
                'max' => 1200,
                'rows' => 4,
            ],
            'reservaciones.terminos_cotizacion' => [
                'label' => 'Terminos de cotizacion',
                'type' => 'string',
                'input' => 'textarea',
                'default' => '',
                'grupo' => 'reservaciones',
                'descripcion' => 'Texto que se imprime en el PDF de cotizacion. Variables: {hotel}, {fecha_entrada}, {checkin}, {checkout}. Si queda vacio se usan los terminos predeterminados.',
                'required' => false,
                'max' => 2000,
                'rows' => 6,
            ],
            'reservaciones.mensaje_confirmacion_whatsapp' => [
                'label' => 'Mensaje de confirmacion WhatsApp',
                'type' => 'string',
                'input' => 'textarea',
                'default' => 'Hola {huesped}, tu reservacion #{folio} en {hotel} esta confirmada del {fecha_entrada} al {fecha_salida}. Total: {total}.',
                'grupo' => 'reservaciones',
                'descripcion' => 'Plantilla de texto. Variables sugeridas: {hotel}, {huesped}, {folio}, {fecha_entrada}, {fecha_salida}, {total}.',
                'required' => false,
                'max' => 800,
                'rows' => 4,
            ],
            'documentos.texto_cotizacion' => [
                'label' => 'Nota para cotizaciones',
                'type' => 'string',
                'input' => 'textarea',
                'default' => 'Cotizacion sujeta a disponibilidad al momento de confirmar la reservacion.',
                'grupo' => 'documentos',
                'descripcion' => 'Nota de presentacion para futuras cotizaciones; no altera importes ni disponibilidad.',
                'required' => false,
                'max' => 800,
                'rows' => 3,
            ],
            'documentos.texto_ticket' => [
                'label' => 'Nota para tickets',
                'type' => 'string',
                'input' => 'textarea',
                'default' => 'Este documento no es un comprobante fiscal.',
                'grupo' => 'documentos',
                'descripcion' => 'Texto auxiliar para futuros tickets o comprobantes visuales.',
                'required' => false,
                'max' => 500,
                'rows' => 3,
            ],
            'documentos.texto_footer' => [
                'label' => 'Pie de documentos',
                'type' => 'string',
                'input' => 'textarea',
                'default' => '',
                'grupo' => 'documentos',
                'descripcion' => 'Texto institucional para documentos futuros, sin impacto en calculos.',
                'required' => false,
                'max' => 800,
                'rows' => 3,
            ],
            'reportes.links_publicos_activos' => [
                'label' => 'Permitir links seguros publicos',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'reportes',
                'descripcion' => 'Si se apaga, los links de reportes ya generados dejan de abrir publicamente para este hotel.',
                'required' => false,
            ],
            'reportes.link_expiracion_dias' => [
                'label' => 'Dias de vigencia del link',
                'type' => 'integer',
                'input' => 'number',
                'default' => 7,
                'grupo' => 'reportes',
                'descripcion' => 'Vigencia predeterminada para nuevos links seguros de reportes PDF.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 90,
                'step' => 1,
            ],
            'reportes.email_envio_activo' => [
                'label' => 'Permitir envio por correo',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => false,
                'grupo' => 'reportes',
                'descripcion' => 'Activa el envio de links seguros de reportes por correo. No adjunta PDFs.',
                'required' => false,
            ],
            'reportes.email_destinatarios' => [
                'label' => 'Destinatarios de reportes',
                'type' => 'string',
                'input' => 'email_list',
                'default' => '',
                'grupo' => 'reportes',
                'descripcion' => 'Correos separados por coma, punto y coma o salto de linea.',
                'required' => false,
                'max' => 500,
                'rows' => 3,
            ],
            'reportes.email_remitente' => [
                'label' => 'Correo remitente',
                'type' => 'string',
                'input' => 'email',
                'default' => '',
                'grupo' => 'reportes',
                'descripcion' => 'Correo que aparecera como remitente. Si queda vacio se usa el email de contacto.',
                'required' => false,
                'max' => 160,
            ],
            'reportes.email_nombre_remitente' => [
                'label' => 'Nombre remitente',
                'type' => 'string',
                'input' => 'text',
                'default' => '',
                'grupo' => 'reportes',
                'descripcion' => 'Nombre visible del remitente. Si queda vacio se usa el nombre del hotel.',
                'required' => false,
                'max' => 120,
            ],
            'reportes.email_asunto' => [
                'label' => 'Asunto del correo',
                'type' => 'string',
                'input' => 'text',
                'default' => 'Reporte disponible - {hotel}',
                'grupo' => 'reportes',
                'descripcion' => 'Puedes usar {hotel}, {titulo} y {expira}.',
                'required' => false,
                'max' => 180,
            ],
            'reportes.email_mensaje' => [
                'label' => 'Mensaje del correo',
                'type' => 'string',
                'input' => 'textarea',
                'default' => "Hola,\n\nEl reporte {titulo} de {hotel} ya esta disponible.\n\nLink seguro: {link}\nVigencia: {expira}",
                'grupo' => 'reportes',
                'descripcion' => 'Plantilla del cuerpo. Variables: {hotel}, {titulo}, {link}, {expira}.',
                'required' => false,
                'max' => 1200,
                'rows' => 5,
            ],
            'notificaciones.automaticas_activas' => [
                'label' => 'Activar reglas automaticas',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Permite que el dashboard genere avisos operativos automaticos para el hotel.',
                'required' => false,
            ],
            'notificaciones.pwa_push_activo' => [
                'label' => 'Permitir notificaciones PWA',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Permite que usuarios del hotel activen avisos push en sus dispositivos instalados o compatibles.',
                'required' => false,
            ],
            'notificaciones.pwa_push_solo_prioritarias' => [
                'label' => 'Push solo para prioridad alta o critica',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => false,
                'grupo' => 'notificaciones',
                'descripcion' => 'Si se activa, el celular solo recibira avisos push de severidad alta o critica.',
                'required' => false,
            ],
            'notificaciones.pwa_push_automaticas' => [
                'label' => 'Enviar push de reglas automaticas',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Permite enviar push cuando el dashboard crea avisos automaticos por reglas operativas.',
                'required' => false,
            ],
            'notificaciones.pwa_push_eventos' => [
                'label' => 'Enviar push de eventos operativos',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Permite enviar push cuando una accion del sistema genera un aviso, por ejemplo caja, habitaciones o facturacion.',
                'required' => false,
            ],
            'notificaciones.regla_checkins_pendientes' => [
                'label' => 'Avisar check-ins pendientes',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando hay reservaciones confirmadas con entrada vencida.',
                'required' => false,
            ],
            'notificaciones.regla_checkouts_pendientes' => [
                'label' => 'Avisar check-outs pendientes',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando hay salidas vencidas en reservaciones activas.',
                'required' => false,
            ],
            'notificaciones.regla_facturas_pendientes' => [
                'label' => 'Avisar facturas pendientes',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando existen solicitudes de factura pendientes.',
                'required' => false,
            ],
            'notificaciones.regla_mantenimiento_activo' => [
                'label' => 'Avisar mantenimiento activo',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando hay habitaciones en mantenimiento.',
                'required' => false,
            ],
            'notificaciones.regla_habitaciones_limpieza' => [
                'label' => 'Avisar habitaciones en limpieza',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando hay habitaciones pendientes de liberar por limpieza.',
                'required' => false,
            ],
            'notificaciones.regla_caja_abierta_prolongada' => [
                'label' => 'Avisar caja abierta prolongada',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando un corte de caja lleva demasiado tiempo abierto.',
                'required' => false,
            ],
            'notificaciones.regla_inventario_bajo' => [
                'label' => 'Avisar inventario bajo',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea avisos cuando productos activos llegan a su stock minimo.',
                'required' => false,
            ],
            'notificaciones.regla_reporte_gerencial_diario' => [
                'label' => 'Crear reporte gerencial diario',
                'type' => 'boolean',
                'input' => 'checkbox',
                'default' => true,
                'grupo' => 'notificaciones',
                'descripcion' => 'Crea una notificacion diaria para gerencia con resumen de ingresos, ocupacion, agenda y pendientes.',
                'required' => false,
            ],
            'notificaciones.umbral_retraso_alta_dias' => [
                'label' => 'Dias para prioridad alta en entradas/salidas',
                'type' => 'integer',
                'input' => 'number',
                'default' => 2,
                'grupo' => 'notificaciones',
                'descripcion' => 'Dias vencidos para subir check-ins o check-outs pendientes a prioridad alta.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 30,
                'step' => 1,
            ],
            'notificaciones.umbral_facturas_alta' => [
                'label' => 'Facturas para prioridad alta',
                'type' => 'integer',
                'input' => 'number',
                'default' => 5,
                'grupo' => 'notificaciones',
                'descripcion' => 'Cantidad de facturas pendientes necesaria para mostrar prioridad alta.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 100,
                'step' => 1,
            ],
            'notificaciones.umbral_limpieza_media' => [
                'label' => 'Habitaciones en limpieza para prioridad media',
                'type' => 'integer',
                'input' => 'number',
                'default' => 5,
                'grupo' => 'notificaciones',
                'descripcion' => 'Cantidad de habitaciones en limpieza necesaria para subir el aviso a prioridad media.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 200,
                'step' => 1,
            ],
            'notificaciones.umbral_caja_horas_media' => [
                'label' => 'Horas de caja abierta para avisar',
                'type' => 'integer',
                'input' => 'number',
                'default' => 12,
                'grupo' => 'notificaciones',
                'descripcion' => 'Horas minimas que debe llevar abierta una caja para crear aviso.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 168,
                'step' => 1,
            ],
            'notificaciones.umbral_caja_horas_alta' => [
                'label' => 'Horas de caja abierta para prioridad alta',
                'type' => 'integer',
                'input' => 'number',
                'default' => 24,
                'grupo' => 'notificaciones',
                'descripcion' => 'Horas necesarias para que el aviso de caja abierta sea prioridad alta.',
                'required' => true,
                'min_value' => 1,
                'max_value' => 336,
                'step' => 1,
            ],
            'pwa.nombre_app' => [
                'label' => 'Nombre de la app',
                'type' => 'string',
                'input' => 'text',
                'default' => '',
                'grupo' => 'pwa',
                'descripcion' => 'Nombre visible sugerido para el manifest de la app del hotel.',
                'required' => false,
                'max' => 80,
            ],
        ];
    }
}

if (!function_exists('hotel_config_editable_values')) {
    function hotel_config_editable_values($hotelId = null)
    {
        $values = [];

        foreach (hotel_config_editable_definitions() as $key => $definition) {
            $default = $definition['default'];

            $values[$key] = hotel_config_get($key, $default, $hotelId ? (int) $hotelId : null);

            if (($definition['type'] ?? 'string') === 'boolean') {
                $values[$key] = (bool) $values[$key];
            }
        }

        return $values;
    }
}

if (!function_exists('hotel_config_normalize_editable_payload')) {
    function hotel_config_normalize_editable_payload(array $payload)
    {
        $definitions = hotel_config_editable_definitions();
        $values = [];
        $errors = [];

        foreach ($definitions as $key => $definition) {
            $rawValue = $payload[$key] ?? null;
            $result = hotel_config_normalize_editable_value($key, $rawValue, $definition);

            if (!$result['ok']) {
                $errors[] = $result['error'];
                continue;
            }

            $values[$key] = $result['value'];
        }

        return [
            'values' => $values,
            'errors' => $errors,
        ];
    }
}

if (!function_exists('hotel_config_normalize_editable_value')) {
    function hotel_config_normalize_editable_value($key, $value, array $definition)
    {
        $label = $definition['label'] ?? $key;
        $type = $definition['type'] ?? 'string';
        $input = $definition['input'] ?? 'text';
        $required = !empty($definition['required']);

        if ($type === 'boolean') {
            return [
                'ok' => true,
                'value' => !empty($value) ? '1' : '0',
            ];
        }

        $value = trim((string) $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        if ($required && $value === '') {
            return [
                'ok' => false,
                'error' => "{$label} es obligatorio.",
            ];
        }

        if ($input === 'time') {
            if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value)) {
                return [
                    'ok' => false,
                    'error' => "{$label} debe tener formato HH:MM.",
                ];
            }
        }

        if ($input === 'currency') {
            $value = strtoupper($value);

            if (!preg_match('/^[A-Z]{3}$/', $value)) {
                return [
                    'ok' => false,
                    'error' => "{$label} debe ser un codigo de 3 letras, por ejemplo MXN.",
                ];
            }
        }

        if ($input === 'timezone' && !in_array($value, timezone_identifiers_list(), true)) {
            return [
                'ok' => false,
                'error' => "{$label} debe ser una zona horaria valida, por ejemplo America/Mexico_City.",
            ];
        }

        if ($type === 'integer') {
            if (!preg_match('/^-?\d+$/', $value)) {
                return [
                    'ok' => false,
                    'error' => "{$label} debe ser un numero entero.",
                ];
            }

            $intValue = (int) $value;
            $minValue = $definition['min_value'] ?? null;
            $maxValue = $definition['max_value'] ?? null;

            if ($minValue !== null && $intValue < (int) $minValue) {
                return [
                    'ok' => false,
                    'error' => "{$label} debe ser mayor o igual a {$minValue}.",
                ];
            }

            if ($maxValue !== null && $intValue > (int) $maxValue) {
                return [
                    'ok' => false,
                    'error' => "{$label} debe ser menor o igual a {$maxValue}.",
                ];
            }

            return [
                'ok' => true,
                'value' => (string) $intValue,
            ];
        }

        if ($input === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'error' => "{$label} no tiene un formato valido.",
            ];
        }

        if ($input === 'email_list' && $value !== '') {
            $emails = preg_split('/[,;\r\n]+/', $value);
            $emails = array_values(array_unique(array_filter(array_map('trim', $emails), static function ($email) {
                return $email !== '';
            })));

            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return [
                        'ok' => false,
                        'error' => "{$label} contiene un correo con formato invalido: {$email}.",
                    ];
                }
            }

            $value = implode(', ', $emails);
        }

        $maxLength = (int) ($definition['max'] ?? 0);
        if ($maxLength > 0 && function_exists('mb_strlen') && mb_strlen($value, 'UTF-8') > $maxLength) {
            return [
                'ok' => false,
                'error' => "{$label} no debe exceder {$maxLength} caracteres.",
            ];
        }

        if ($maxLength > 0 && !function_exists('mb_strlen') && strlen($value) > $maxLength) {
            return [
                'ok' => false,
                'error' => "{$label} no debe exceder {$maxLength} caracteres.",
            ];
        }

        return [
            'ok' => true,
            'value' => $value,
        ];
    }
}

if (!function_exists('hotel_config_save_editable_values')) {
    function hotel_config_save_editable_values(array $values, $hotelId = null)
    {
        $hotelId = hotel_config_resolve_hotel_id($hotelId);

        if (!$hotelId || !class_exists('Database')) {
            throw new RuntimeException('No se pudo resolver el hotel actual para guardar configuracion.');
        }

        $definitions = hotel_config_editable_definitions();
        $db = Database::getInstance();

        foreach ($values as $key => $value) {
            if (!isset($definitions[$key])) {
                continue;
            }

            $definition = $definitions[$key];
            $stmt = $db->query(
                "INSERT INTO hotel_configuracion
                    (hotel_id, clave, valor, tipo, grupo, descripcion, es_feature_flag, activo, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    valor = VALUES(valor),
                    tipo = VALUES(tipo),
                    grupo = VALUES(grupo),
                    descripcion = VALUES(descripcion),
                    es_feature_flag = 0,
                    activo = 1,
                    updated_at = CURRENT_TIMESTAMP",
                [
                    (int) $hotelId,
                    (string) $key,
                    (string) $value,
                    (string) ($definition['type'] ?? 'string'),
                    (string) ($definition['grupo'] ?? ''),
                    (string) ($definition['descripcion'] ?? ''),
                ]
            );

            if (!$stmt) {
                throw new RuntimeException('No se pudo guardar la configuracion ' . $key . '.');
            }
        }

        return true;
    }
}

if (!function_exists('hotel_config_save_value')) {
    function hotel_config_save_value($clave, $valor, $tipo = 'string', $grupo = 'general', $descripcion = '', $hotelId = null)
    {
        $hotelId = hotel_config_resolve_hotel_id($hotelId);

        if (!$hotelId || !class_exists('Database')) {
            throw new RuntimeException('No se pudo resolver el hotel actual para guardar configuracion.');
        }

        if ($tipo === 'json') {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE);
        } elseif ($tipo === 'boolean') {
            $valor = !empty($valor) ? '1' : '0';
        } else {
            $valor = (string) $valor;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "INSERT INTO hotel_configuracion
                (hotel_id, clave, valor, tipo, grupo, descripcion, es_feature_flag, activo, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                valor = VALUES(valor),
                tipo = VALUES(tipo),
                grupo = VALUES(grupo),
                descripcion = VALUES(descripcion),
                es_feature_flag = 0,
                activo = 1,
                updated_at = CURRENT_TIMESTAMP",
            [
                (int) $hotelId,
                (string) $clave,
                $valor,
                (string) $tipo,
                (string) $grupo,
                (string) $descripcion,
            ]
        );

        if (!$stmt) {
            throw new RuntimeException('No se pudo guardar la configuracion ' . $clave . '.');
        }

        return true;
    }
}

if (!function_exists('hotel_room_catalog_default_type_rows')) {
    function hotel_room_catalog_default_type_rows($hotelId = null)
    {
        $hotelId = hotel_config_resolve_hotel_id($hotelId);
        $rowsByCode = [];

        if (class_exists('Database')) {
            try {
                $db = Database::getInstance();
                $stmt = false;

                if ($hotelId) {
                    $stmt = $db->query(
                        "SELECT codigo, nombre, descripcion, capacidad_default, precio_base_default, activo, orden
                         FROM tipos_habitacion
                         WHERE hotel_id = ? OR hotel_id IS NULL
                         ORDER BY COALESCE(orden, 0), id",
                        [(int) $hotelId]
                    );
                }

                if (!$stmt) {
                    $stmt = $db->query(
                        "SELECT codigo, nombre, descripcion, capacidad_default, precio_base_default, activo, orden
                         FROM tipos_habitacion
                         ORDER BY COALESCE(orden, 0), id"
                    );
                }

                if ($stmt) {
                    foreach ($stmt->fetchAll() as $index => $row) {
                        $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
                        if ($codigo === '') {
                            continue;
                        }

                        if (function_exists('hotel_room_catalog_configurable_type_codes') && !isset(hotel_room_catalog_configurable_type_codes()[$codigo])) {
                            continue;
                        }

                        $rowsByCode[$codigo] = [
                            'codigo' => $codigo,
                            'nombre' => trim((string) ($row['nombre'] ?? $codigo)),
                            'descripcion' => trim((string) ($row['descripcion'] ?? '')),
                            'capacidad_default' => max(1, (int) ($row['capacidad_default'] ?? 2)),
                            'precio_base_default' => (float) ($row['precio_base_default'] ?? 0),
                            'activo' => !empty($row['activo']) ? 1 : 0,
                            'orden' => (int) ($row['orden'] ?? $index),
                        ];
                    }
                }
            } catch (Throwable $e) {
                error_log('Error cargando tipos_habitacion base: ' . $e->getMessage());
            }
        }

        $tipos = class_exists('Habitacion') ? Habitacion::getTipos() : [
            'sencilla' => 'Sencilla',
            'doble' => 'Doble',
            'triple' => 'Triple',
            'cuadruple' => 'Cuadruple',
            'doble_jacuzzi' => 'Doble con Jacuzzi',
            'sencilla_jacuzzi' => 'Sencilla con Jacuzzi',
        ];
        $rangos = class_exists('Habitacion') ? Habitacion::getRangoPrecios() : [];

        $orden = count($rowsByCode);
        foreach ($tipos as $codigo => $nombre) {
            $codigo = strtolower(trim((string) $codigo));
            if ($codigo === '' || isset($rowsByCode[$codigo])) {
                continue;
            }

            if (function_exists('hotel_room_catalog_configurable_type_codes') && !isset(hotel_room_catalog_configurable_type_codes()[$codigo])) {
                continue;
            }

            $rowsByCode[$codigo] = [
                'codigo' => $codigo,
                'nombre' => (string) $nombre,
                'descripcion' => '',
                'capacidad_default' => 2,
                'precio_base_default' => (float) ($rangos[$codigo]['min'] ?? 0),
                'activo' => 1,
                'orden' => $orden++,
            ];
        }

        return array_values($rowsByCode);
    }
}

if (!function_exists('hotel_room_catalog_default_floor_rows')) {
    function hotel_room_catalog_default_floor_rows()
    {
        $pisos = class_exists('Habitacion') ? Habitacion::getPisos() : [
            -4 => '4 niveles abajo',
            -2 => '2 niveles abajo',
            -1 => 'Un nivel abajo',
            1 => 'Nivel de piso',
            2 => '2o Nivel',
            3 => '3o Nivel',
        ];

        $rows = [];
        $orden = 0;
        foreach ($pisos as $valor => $label) {
            $rows[] = [
                'valor' => (int) $valor,
                'label' => (string) $label,
                'activo' => 1,
                'orden' => $orden++,
            ];
        }

        return $rows;
    }
}

if (!function_exists('hotel_room_catalog_default_amenity_rows')) {
    function hotel_room_catalog_default_amenity_rows()
    {
        return [
            ['codigo' => 'pantalla', 'label' => 'Pantalla', 'activo' => 1, 'orden' => 0],
            ['codigo' => 'balcon', 'label' => 'Balcon', 'activo' => 1, 'orden' => 1],
            ['codigo' => 'jacuzzi', 'label' => 'Jacuzzi', 'activo' => 1, 'orden' => 2],
            ['codigo' => 'amplia', 'label' => 'Mas amplia', 'activo' => 1, 'orden' => 3],
        ];
    }
}

if (!function_exists('hotel_room_catalog_storage_type_codes')) {
    function hotel_room_catalog_storage_type_codes()
    {
        return [
            'sencilla' => true,
            'doble' => true,
            'triple' => true,
            'cuadruple' => true,
            'sencilla_manolo' => true,
            'doble_manolo' => true,
        ];
    }
}

if (!function_exists('hotel_room_catalog_type_aliases')) {
    function hotel_room_catalog_type_aliases()
    {
        return [
            'sencilla_jacuzzi' => 'sencilla',
            'doble_jacuzzi' => 'doble',
        ];
    }
}

if (!function_exists('hotel_room_catalog_configurable_type_codes')) {
    function hotel_room_catalog_configurable_type_codes()
    {
        return hotel_room_catalog_storage_type_codes() + array_fill_keys(array_keys(hotel_room_catalog_type_aliases()), true);
    }
}

if (!function_exists('hotel_room_catalog_storage_type_for_code')) {
    function hotel_room_catalog_storage_type_for_code($codigo, $hotelId = null)
    {
        $codigo = strtolower(trim((string) $codigo));

        if ($codigo === '') {
            return '';
        }

        $storageCodes = hotel_room_catalog_storage_type_codes();
        if (isset($storageCodes[$codigo])) {
            return $codigo;
        }

        $aliases = hotel_room_catalog_type_aliases();
        if (isset($aliases[$codigo])) {
            return $aliases[$codigo];
        }

        foreach (hotel_room_catalog_type_rows($hotelId, true) as $row) {
            if (strtolower((string) ($row['codigo'] ?? '')) !== $codigo) {
                continue;
            }

            $texto = strtolower(trim($codigo . ' ' . (string) ($row['nombre'] ?? '')));
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
            if ($ascii !== false) {
                $texto = $ascii;
            }

            if (strpos($texto, 'cuadruple') !== false || strpos($texto, 'cua') !== false) {
                return 'cuadruple';
            }
            if (strpos($texto, 'triple') !== false || strpos($texto, 'tri') !== false) {
                return 'triple';
            }
            if (strpos($texto, 'doble') !== false || strpos($texto, 'dob') !== false) {
                return 'doble';
            }
            if (strpos($texto, 'sencilla') !== false || strpos($texto, 'simple') !== false || strpos($texto, 'sen') !== false) {
                return 'sencilla';
            }

            $capacidad = max(1, (int) ($row['capacidad_default'] ?? 2));
            if ($capacidad >= 7) {
                return 'cuadruple';
            }
            if ($capacidad >= 5) {
                return 'triple';
            }
            if ($capacidad >= 3) {
                return 'doble';
            }

            return 'sencilla';
        }

        return '';
    }
}

if (!function_exists('hotel_room_catalog_type_rows')) {
    function hotel_room_catalog_type_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.habitacion_tipos', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_room_catalog_sanitize_type_rows($storedRows)
            : hotel_room_catalog_default_type_rows($hotelId);

        if (empty($rows)) {
            $rows = hotel_room_catalog_default_type_rows($hotelId);
        }

        if ($includeInactive) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) {
            return !empty($row['activo']);
        }));
    }
}

if (!function_exists('hotel_room_catalog_floor_rows')) {
    function hotel_room_catalog_floor_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.habitacion_pisos', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_room_catalog_sanitize_floor_rows($storedRows)
            : hotel_room_catalog_default_floor_rows();

        if ($includeInactive) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) {
            return !empty($row['activo']);
        }));
    }
}

if (!function_exists('hotel_room_catalog_amenity_rows')) {
    function hotel_room_catalog_amenity_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.habitacion_amenidades', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_room_catalog_sanitize_amenity_rows($storedRows)
            : hotel_room_catalog_default_amenity_rows();

        if ($includeInactive) {
            return $rows;
        }

        return array_values(array_filter($rows, function ($row) {
            return !empty($row['activo']);
        }));
    }
}

if (!function_exists('hotel_room_catalog_types')) {
    function hotel_room_catalog_types($hotelId = null)
    {
        $tipos = [];
        foreach (hotel_room_catalog_type_rows($hotelId, false) as $row) {
            $tipos[$row['codigo']] = $row['nombre'];
        }

        return $tipos;
    }
}

if (!function_exists('hotel_room_catalog_legacy_type_labels')) {
    function hotel_room_catalog_legacy_type_labels()
    {
        return [
            'sencilla_manolo' => 'Sencilla Manolo',
            'doble_manolo' => 'Doble Manolo',
        ];
    }
}

if (!function_exists('hotel_room_catalog_legacy_type_codes_in_use')) {
    function hotel_room_catalog_legacy_type_codes_in_use($hotelId = null)
    {
        static $cache = [];

        $hotelId = hotel_config_resolve_hotel_id($hotelId);
        if (!$hotelId || !class_exists('Database')) {
            return [];
        }

        $cacheKey = (string) (int) $hotelId;
        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $codes = [];
        try {
            $legacyCodes = array_keys(hotel_room_catalog_legacy_type_labels());
            $placeholders = implode(',', array_fill(0, count($legacyCodes), '?'));
            $params = array_merge([(int) $hotelId], $legacyCodes);
            $stmt = Database::getInstance()->query(
                "SELECT DISTINCT tipo
                 FROM habitaciones
                 WHERE hotel_id = ?
                   AND activa = 1
                   AND tipo IN ($placeholders)
                 ORDER BY tipo",
                $params
            );

            if ($stmt) {
                foreach ($stmt->fetchAll() as $row) {
                    $code = strtolower(trim((string) ($row['tipo'] ?? '')));
                    if ($code !== '') {
                        $codes[] = $code;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Error cargando tipos legacy en uso: ' . $e->getMessage());
        }

        $cache[$cacheKey] = array_values(array_unique($codes));
        return $cache[$cacheKey];
    }
}

if (!function_exists('hotel_room_catalog_types_for_select')) {
    function hotel_room_catalog_types_for_select($hotelId = null)
    {
        $tipos = hotel_room_catalog_types($hotelId);
        $legacyLabels = hotel_room_catalog_legacy_type_labels();

        foreach ($tipos as $codigo => $nombre) {
            $codigo = strtolower(trim((string) $codigo));
            if ($codigo === '' || !isset($legacyLabels[$codigo])) {
                continue;
            }

            $tipos[$codigo] = trim((string) $nombre) !== ''
                ? (string) $nombre
                : $legacyLabels[$codigo];
        }

        foreach (hotel_room_catalog_legacy_type_codes_in_use($hotelId) as $codigo) {
            if (!isset($tipos[$codigo]) && isset($legacyLabels[$codigo])) {
                $tipos[$codigo] = $legacyLabels[$codigo];
            }
        }

        return $tipos;
    }
}

if (!function_exists('hotel_room_catalog_floors')) {
    function hotel_room_catalog_floors($hotelId = null)
    {
        $pisos = [];
        foreach (hotel_room_catalog_floor_rows($hotelId, false) as $row) {
            $pisos[(int) $row['valor']] = $row['label'];
        }

        return $pisos;
    }
}

if (!function_exists('hotel_room_catalog_amenities')) {
    function hotel_room_catalog_amenities($hotelId = null)
    {
        $amenidades = [];
        foreach (hotel_room_catalog_amenity_rows($hotelId, false) as $row) {
            $amenidades[$row['codigo']] = $row['label'];
        }

        return $amenidades;
    }
}

if (!function_exists('hotel_room_catalog_sanitize_type_rows')) {
    function hotel_room_catalog_sanitize_type_rows(array $rows)
    {
        $clean = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $nombre = trim((string) ($row['nombre'] ?? ''));

            if ($codigo === '' || $nombre === '' || isset($seen[$codigo])) {
                continue;
            }

            if (!isset(hotel_room_catalog_configurable_type_codes()[$codigo])) {
                continue;
            }

            $seen[$codigo] = true;
            $clean[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => trim((string) ($row['descripcion'] ?? '')),
                'capacidad_default' => max(1, (int) ($row['capacidad_default'] ?? 2)),
                'precio_base_default' => max(0, (float) ($row['precio_base_default'] ?? 0)),
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => (int) ($row['orden'] ?? $orden),
            ];
            $orden++;
        }

        usort($clean, function ($a, $b) {
            return ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
        });

        return $clean;
    }
}

if (!function_exists('hotel_room_catalog_sanitize_floor_rows')) {
    function hotel_room_catalog_sanitize_floor_rows(array $rows)
    {
        $clean = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $valor = (int) ($row['valor'] ?? 0);
            $label = trim((string) ($row['label'] ?? ''));

            if ($valor === 0 || $label === '' || isset($seen[$valor])) {
                continue;
            }

            $seen[$valor] = true;
            $clean[] = [
                'valor' => $valor,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => (int) ($row['orden'] ?? $orden),
            ];
            $orden++;
        }

        usort($clean, function ($a, $b) {
            return ((int) ($a['valor'] ?? 0)) <=> ((int) ($b['valor'] ?? 0));
        });

        return $clean;
    }
}

if (!function_exists('hotel_room_catalog_sanitize_amenity_rows')) {
    function hotel_room_catalog_sanitize_amenity_rows(array $rows)
    {
        $clean = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));

            if ($codigo === '' || $label === '' || isset($seen[$codigo])) {
                continue;
            }

            $seen[$codigo] = true;
            $clean[] = [
                'codigo' => $codigo,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => (int) ($row['orden'] ?? $orden),
            ];
            $orden++;
        }

        usort($clean, function ($a, $b) {
            return ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
        });

        return $clean;
    }
}

if (!function_exists('hotel_room_catalog_normalize_payload')) {
    function hotel_room_catalog_normalize_payload(array $payload)
    {
        $typeResult = hotel_room_catalog_normalize_type_payload($payload['types'] ?? []);
        $floorResult = hotel_room_catalog_normalize_floor_payload($payload['floors'] ?? []);
        $amenityResult = hotel_room_catalog_normalize_amenity_payload($payload['amenities'] ?? []);

        return [
            'values' => [
                'types' => $typeResult['values'],
                'floors' => $floorResult['values'],
                'amenities' => $amenityResult['values'],
            ],
            'errors' => array_merge($typeResult['errors'], $floorResult['errors'], $amenityResult['errors']),
        ];
    }
}

if (!function_exists('hotel_room_catalog_normalize_type_payload')) {
    function hotel_room_catalog_normalize_type_payload($rows)
    {
        $rows = is_array($rows) ? $rows : [];
        $values = [];
        $errors = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $descripcion = trim((string) ($row['descripcion'] ?? ''));
            $capacidadRaw = trim((string) ($row['capacidad_default'] ?? ''));
            $precioRaw = trim((string) ($row['precio_base_default'] ?? ''));
            $hasAny = $codigo !== '' || $nombre !== '' || $descripcion !== '' || $capacidadRaw !== '' || $precioRaw !== '';

            if (!$hasAny) {
                continue;
            }

            if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,20}$/', $codigo)) {
                $errors[] = 'Cada tipo de habitacion debe tener un codigo de 2 a 20 caracteres usando letras, numeros o guion bajo.';
                continue;
            }

            if (!isset(hotel_room_catalog_configurable_type_codes()[$codigo])) {
                $errors[] = 'El tipo de habitacion "' . $codigo . '" no es compatible con el catalogo tecnico actual. Usa: ' . implode(', ', array_keys(hotel_room_catalog_configurable_type_codes())) . '.';
                continue;
            }

            if (isset($seen[$codigo])) {
                $errors[] = 'El tipo de habitacion "' . $codigo . '" esta duplicado.';
                continue;
            }

            if ($nombre === '') {
                $errors[] = 'El tipo "' . $codigo . '" debe tener nombre.';
                continue;
            }

            if (hotel_room_catalog_text_length($nombre) > 50) {
                $errors[] = 'El nombre del tipo "' . $codigo . '" no debe exceder 50 caracteres.';
                continue;
            }

            if (hotel_room_catalog_text_length($descripcion) > 255) {
                $errors[] = 'La descripcion del tipo "' . $codigo . '" no debe exceder 255 caracteres.';
                continue;
            }

            $capacidad = $capacidadRaw === '' ? 2 : (int) $capacidadRaw;
            if ($capacidad < 1 || $capacidad > 50) {
                $errors[] = 'La capacidad del tipo "' . $codigo . '" debe estar entre 1 y 50.';
                continue;
            }

            $precio = $precioRaw === '' ? 0 : (float) $precioRaw;
            if ($precio < 0) {
                $errors[] = 'El precio base del tipo "' . $codigo . '" no puede ser negativo.';
                continue;
            }

            $seen[$codigo] = true;
            $values[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'capacidad_default' => $capacidad,
                'precio_base_default' => round($precio, 2),
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => $orden++,
            ];
        }

        if (empty($values)) {
            $errors[] = 'Debes conservar al menos un tipo de habitacion.';
        } elseif (!array_filter($values, function ($row) {
            return !empty($row['activo']);
        })) {
            $errors[] = 'Debes dejar activo al menos un tipo de habitacion.';
        }

        return ['values' => $values, 'errors' => $errors];
    }
}

if (!function_exists('hotel_room_catalog_normalize_floor_payload')) {
    function hotel_room_catalog_normalize_floor_payload($rows)
    {
        $rows = is_array($rows) ? $rows : [];
        $values = [];
        $errors = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $valorRaw = trim((string) ($row['valor'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $hasAny = $valorRaw !== '' || $label !== '';

            if (!$hasAny) {
                continue;
            }

            if (!preg_match('/^-?\d+$/', $valorRaw)) {
                $errors[] = 'Cada piso debe tener un numero entero.';
                continue;
            }

            $valor = (int) $valorRaw;
            if ($valor === 0) {
                $errors[] = 'El piso 0 no esta permitido; usa 1 para nivel de piso.';
                continue;
            }

            if (isset($seen[$valor])) {
                $errors[] = 'El piso "' . $valor . '" esta duplicado.';
                continue;
            }

            if ($label === '') {
                $errors[] = 'El piso "' . $valor . '" debe tener etiqueta.';
                continue;
            }

            if (hotel_room_catalog_text_length($label) > 80) {
                $errors[] = 'La etiqueta del piso "' . $valor . '" no debe exceder 80 caracteres.';
                continue;
            }

            $seen[$valor] = true;
            $values[] = [
                'valor' => $valor,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => $orden++,
            ];
        }

        if (empty($values)) {
            $errors[] = 'Debes conservar al menos un piso.';
        } elseif (!array_filter($values, function ($row) {
            return !empty($row['activo']);
        })) {
            $errors[] = 'Debes dejar activo al menos un piso.';
        }

        return ['values' => $values, 'errors' => $errors];
    }
}

if (!function_exists('hotel_room_catalog_normalize_amenity_payload')) {
    function hotel_room_catalog_normalize_amenity_payload($rows)
    {
        $rows = is_array($rows) ? $rows : [];
        $values = [];
        $errors = [];
        $seen = [];
        $orden = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));
            $hasAny = $codigo !== '' || $label !== '';

            if (!$hasAny) {
                continue;
            }

            if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,30}$/', $codigo)) {
                $errors[] = 'Cada amenidad debe tener un codigo de 2 a 30 caracteres usando letras, numeros o guion bajo.';
                continue;
            }

            if (isset($seen[$codigo])) {
                $errors[] = 'La amenidad "' . $codigo . '" esta duplicada.';
                continue;
            }

            if ($label === '') {
                $errors[] = 'La amenidad "' . $codigo . '" debe tener etiqueta.';
                continue;
            }

            if (hotel_room_catalog_text_length($label) > 70) {
                $errors[] = 'La etiqueta de amenidad "' . $codigo . '" no debe exceder 70 caracteres.';
                continue;
            }

            $seen[$codigo] = true;
            $values[] = [
                'codigo' => $codigo,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => $orden++,
            ];
        }

        return ['values' => $values, 'errors' => $errors];
    }
}

if (!function_exists('hotel_room_catalog_save_values')) {
    function hotel_room_catalog_save_values(array $values, $hotelId = null)
    {
        hotel_config_save_value(
            'catalogos.habitacion_tipos',
            $values['types'] ?? [],
            'json',
            'catalogos',
            'Tipos de habitacion configurables por hotel.',
            $hotelId
        );
        hotel_config_save_value(
            'catalogos.habitacion_pisos',
            $values['floors'] ?? [],
            'json',
            'catalogos',
            'Pisos configurables por hotel.',
            $hotelId
        );
        hotel_config_save_value(
            'catalogos.habitacion_amenidades',
            $values['amenities'] ?? [],
            'json',
            'catalogos',
            'Amenidades configurables por hotel.',
            $hotelId
        );

        return true;
    }
}

if (!function_exists('hotel_room_catalog_text_length')) {
    function hotel_room_catalog_text_length($value)
    {
        $value = (string) $value;
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

if (!function_exists('hotel_general_catalog_default_zone_rows')) {
    function hotel_general_catalog_default_zone_rows()
    {
        return [
            ['codigo' => 'recepcion', 'label' => 'Recepcion', 'activo' => 1, 'orden' => 0],
            ['codigo' => 'habitaciones', 'label' => 'Habitaciones', 'activo' => 1, 'orden' => 1],
            ['codigo' => 'servicio', 'label' => 'Servicio', 'activo' => 1, 'orden' => 2],
        ];
    }
}

if (!function_exists('hotel_general_catalog_default_parking_rows')) {
    function hotel_general_catalog_default_parking_rows()
    {
        return [
            ['codigo' => 'coches', 'label' => 'Coches', 'activo' => 1, 'orden' => 0],
            ['codigo' => 'camionetas', 'label' => 'Camionetas', 'activo' => 1, 'orden' => 1],
            ['codigo' => 'discos', 'label' => 'Discos', 'activo' => 1, 'orden' => 2],
            ['codigo' => 'nikkos', 'label' => 'Nikkos', 'activo' => 1, 'orden' => 3],
        ];
    }
}

if (!function_exists('hotel_general_catalog_parking_code_set')) {
    function hotel_general_catalog_parking_code_set()
    {
        return [
            'coches' => true,
            'camionetas' => true,
            'discos' => true,
            'nikkos' => true,
        ];
    }
}

if (!function_exists('hotel_general_catalog_default_unit_rows')) {
    function hotel_general_catalog_default_unit_rows()
    {
        return [
            ['codigo' => 'pieza', 'label' => 'Pieza', 'abreviatura' => 'pza', 'activo' => 1, 'orden' => 0],
            ['codigo' => 'rollo', 'label' => 'Rollo', 'abreviatura' => 'rollo', 'activo' => 1, 'orden' => 1],
            ['codigo' => 'caja', 'label' => 'Caja', 'abreviatura' => 'caja', 'activo' => 1, 'orden' => 2],
            ['codigo' => 'paquete', 'label' => 'Paquete', 'abreviatura' => 'paq', 'activo' => 1, 'orden' => 3],
            ['codigo' => 'litro', 'label' => 'Litro', 'abreviatura' => 'l', 'activo' => 1, 'orden' => 4],
            ['codigo' => 'kilogramo', 'label' => 'Kilogramo', 'abreviatura' => 'kg', 'activo' => 1, 'orden' => 5],
            ['codigo' => 'unidad', 'label' => 'Unidad', 'abreviatura' => 'u', 'activo' => 1, 'orden' => 6],
        ];
    }
}

if (!function_exists('hotel_general_catalog_zone_rows')) {
    function hotel_general_catalog_zone_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.hotel_zonas', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_general_catalog_sanitize_rows($storedRows, false)
            : hotel_general_catalog_default_zone_rows();

        return $includeInactive ? $rows : hotel_general_catalog_active_rows($rows);
    }
}

if (!function_exists('hotel_general_catalog_parking_rows')) {
    function hotel_general_catalog_parking_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.estacionamientos', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_general_catalog_sanitize_rows($storedRows, false, 30, hotel_general_catalog_parking_code_set())
            : hotel_general_catalog_default_parking_rows();

        if (empty($rows)) {
            $rows = hotel_general_catalog_default_parking_rows();
        }

        return $includeInactive ? $rows : hotel_general_catalog_active_rows($rows);
    }
}

if (!function_exists('hotel_general_catalog_unit_rows')) {
    function hotel_general_catalog_unit_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.unidades_medida', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_general_catalog_sanitize_rows($storedRows, true, 20)
            : hotel_general_catalog_default_unit_rows();

        if (empty($rows)) {
            $rows = hotel_general_catalog_default_unit_rows();
        }

        return $includeInactive ? $rows : hotel_general_catalog_active_rows($rows);
    }
}

if (!function_exists('hotel_general_catalog_units')) {
    function hotel_general_catalog_units($hotelId = null)
    {
        $units = [];
        foreach (hotel_general_catalog_unit_rows($hotelId, false) as $row) {
            $units[$row['codigo']] = $row['label'];
        }

        return $units;
    }
}

if (!function_exists('hotel_general_catalog_active_rows')) {
    function hotel_general_catalog_active_rows(array $rows)
    {
        return array_values(array_filter($rows, function ($row) {
            return !empty($row['activo']);
        }));
    }
}

if (!function_exists('hotel_general_catalog_sanitize_rows')) {
    function hotel_general_catalog_sanitize_rows(array $rows, $withAbbreviation = false, $maxCodeLength = 30, array $allowedCodes = null)
    {
        $clean = [];
        $seen = [];
        $orden = 0;
        $maxCodeLength = max(2, (int) $maxCodeLength);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));

            if ($codigo === '' || $label === '' || isset($seen[$codigo])) {
                continue;
            }

            if (hotel_room_catalog_text_length($codigo) > $maxCodeLength) {
                continue;
            }

            if (is_array($allowedCodes) && !isset($allowedCodes[$codigo])) {
                continue;
            }

            $seen[$codigo] = true;
            $item = [
                'codigo' => $codigo,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => (int) ($row['orden'] ?? $orden),
            ];

            if ($withAbbreviation) {
                $item['abreviatura'] = trim((string) ($row['abreviatura'] ?? $codigo));
            }

            $clean[] = $item;
            $orden++;
        }

        usort($clean, function ($a, $b) {
            return ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
        });

        return $clean;
    }
}

if (!function_exists('hotel_general_catalog_normalize_payload')) {
    function hotel_general_catalog_normalize_payload(array $payload)
    {
        $zoneResult = hotel_general_catalog_normalize_rows($payload['zones'] ?? [], 'zona', false, true);
        $parkingResult = hotel_general_catalog_normalize_rows($payload['parkings'] ?? [], 'estacionamiento', false, true, 30, hotel_general_catalog_parking_code_set());
        $unitResult = hotel_general_catalog_normalize_rows($payload['units'] ?? [], 'unidad de medida', true, true, 20);

        return [
            'values' => [
                'zones' => $zoneResult['values'],
                'parkings' => $parkingResult['values'],
                'units' => $unitResult['values'],
            ],
            'errors' => array_merge($zoneResult['errors'], $parkingResult['errors'], $unitResult['errors']),
        ];
    }
}

if (!function_exists('hotel_general_catalog_normalize_rows')) {
    function hotel_general_catalog_normalize_rows($rows, $labelSingular, $withAbbreviation = false, $requireActive = true, $maxCodeLength = 30, array $allowedCodes = null)
    {
        $rows = is_array($rows) ? $rows : [];
        $values = [];
        $errors = [];
        $seen = [];
        $orden = 0;
        $maxCodeLength = max(2, (int) $maxCodeLength);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $codigo = strtolower(trim((string) ($row['codigo'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));
            $abbreviation = trim((string) ($row['abreviatura'] ?? ''));
            $hasAny = $codigo !== '' || $label !== '' || $abbreviation !== '';

            if (!$hasAny) {
                continue;
            }

            if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,' . $maxCodeLength . '}$/', $codigo)) {
                $errors[] = 'Cada ' . $labelSingular . ' debe tener un codigo de 2 a ' . $maxCodeLength . ' caracteres usando letras, numeros o guion bajo.';
                continue;
            }

            if (is_array($allowedCodes) && !isset($allowedCodes[$codigo])) {
                $errors[] = 'El codigo "' . $codigo . '" no es compatible para ' . $labelSingular . '. Usa: ' . implode(', ', array_keys($allowedCodes)) . '.';
                continue;
            }

            if (isset($seen[$codigo])) {
                $errors[] = 'El codigo "' . $codigo . '" esta duplicado en ' . $labelSingular . '.';
                continue;
            }

            if ($label === '') {
                $errors[] = 'El codigo "' . $codigo . '" debe tener etiqueta.';
                continue;
            }

            if (hotel_room_catalog_text_length($label) > 80) {
                $errors[] = 'La etiqueta "' . $label . '" no debe exceder 80 caracteres.';
                continue;
            }

            $item = [
                'codigo' => $codigo,
                'label' => $label,
                'activo' => !empty($row['activo']) ? 1 : 0,
                'orden' => $orden++,
            ];

            if ($withAbbreviation) {
                $abbreviation = $abbreviation !== '' ? $abbreviation : $codigo;
                if (hotel_room_catalog_text_length($abbreviation) > 12) {
                    $errors[] = 'La abreviatura de "' . $codigo . '" no debe exceder 12 caracteres.';
                    continue;
                }
                $item['abreviatura'] = $abbreviation;
            }

            $seen[$codigo] = true;
            $values[] = $item;
        }

        if (empty($values)) {
            $errors[] = 'Debes conservar al menos un registro en ' . $labelSingular . '.';
        } elseif ($requireActive && !array_filter($values, function ($row) {
            return !empty($row['activo']);
        })) {
            $errors[] = 'Debes dejar activo al menos un registro en ' . $labelSingular . '.';
        }

        return ['values' => $values, 'errors' => $errors];
    }
}

if (!function_exists('hotel_general_catalog_save_values')) {
    function hotel_general_catalog_save_values(array $values, $hotelId = null)
    {
        hotel_config_save_value(
            'catalogos.hotel_zonas',
            $values['zones'] ?? [],
            'json',
            'catalogos',
            'Zonas o areas configurables por hotel.',
            $hotelId
        );
        hotel_config_save_value(
            'catalogos.estacionamientos',
            $values['parkings'] ?? [],
            'json',
            'catalogos',
            'Estacionamientos configurables por hotel.',
            $hotelId
        );
        hotel_config_save_value(
            'catalogos.unidades_medida',
            $values['units'] ?? [],
            'json',
            'catalogos',
            'Unidades de medida configurables por hotel.',
            $hotelId
        );

        return true;
    }
}

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

if (!function_exists('hotel_owner_distribution_default')) {
    function hotel_owner_distribution_default()
    {
        return [
            'version' => 1,
            'propietario_default' => 'elia',
            'propietarios' => [
                'manolo' => [
                    'key' => 'manolo',
                    'nombre' => 'Manolo',
                    'activo' => true,
                    'participacion_pct' => 100.0,
                ],
                'elia' => [
                    'key' => 'elia',
                    'nombre' => 'Elia',
                    'activo' => true,
                    'participacion_pct' => 100.0,
                ],
            ],
            'reglas_tipo_contiene' => [
                'manolo' => 'manolo',
            ],
            'habitaciones' => [],
        ];
    }
}

if (!function_exists('hotel_owner_distribution_text_length')) {
    function hotel_owner_distribution_text_length($value)
    {
        $value = (string) $value;
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}

if (!function_exists('hotel_owner_distribution_key')) {
    function hotel_owner_distribution_key($value)
    {
        $key = strtolower(trim((string) $value));
        $key = preg_replace('/[^a-z0-9_-]+/', '_', $key);
        return trim((string) $key, '_-');
    }
}

if (!function_exists('hotel_owner_distribution_percentage')) {
    function hotel_owner_distribution_percentage($value)
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $percentage = is_numeric($value) ? (float) $value : 100.0;

        return max(0.0, min(100.0, round($percentage, 2)));
    }
}

if (!function_exists('hotel_owner_distribution_normalize')) {
    function hotel_owner_distribution_normalize($config = null)
    {
        $base = hotel_owner_distribution_default();

        if (is_string($config) && trim($config) !== '') {
            $decoded = json_decode($config, true);
            $config = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($config)) {
            $config = [];
        }

        $rawOwners = is_array($config['propietarios'] ?? null) && !empty($config['propietarios'])
            ? $config['propietarios']
            : $base['propietarios'];

        $propietarios = [];
        foreach ($rawOwners as $key => $row) {
            if (!is_array($row)) {
                $row = ['nombre' => (string) $row];
            }

            $ownerKey = hotel_owner_distribution_key($row['key'] ?? $key);
            $ownerName = trim((string) ($row['nombre'] ?? $row['label'] ?? $ownerKey));

            if ($ownerKey === '') {
                continue;
            }

            $propietarios[$ownerKey] = [
                'key' => $ownerKey,
                'nombre' => $ownerName !== '' ? $ownerName : $ownerKey,
                'activo' => array_key_exists('activo', $row) ? !empty($row['activo']) : true,
                'participacion_pct' => hotel_owner_distribution_percentage(
                    $row['participacion_pct'] ?? ($row['porcentaje'] ?? ($row['participacion'] ?? 100))
                ),
            ];
        }

        if (empty($propietarios)) {
            $propietarios = $base['propietarios'];
        }

        $activeOwnerKeys = array_keys(array_filter($propietarios, static function (array $row) {
            return !empty($row['activo']);
        }));

        if (empty($activeOwnerKeys)) {
            $propietarios = $base['propietarios'];
            $activeOwnerKeys = array_keys($propietarios);
        }

        $defaultKey = hotel_owner_distribution_key($config['propietario_default'] ?? $base['propietario_default']);
        if (!isset($propietarios[$defaultKey]) || empty($propietarios[$defaultKey]['activo'])) {
            $defaultKey = isset($propietarios[$base['propietario_default']]) && !empty($propietarios[$base['propietario_default']]['activo'])
                ? $base['propietario_default']
                : (string) $activeOwnerKeys[0];
        }

        $reglas = [];
        $rawRules = is_array($config['reglas_tipo_contiene'] ?? null)
            ? $config['reglas_tipo_contiene']
            : $base['reglas_tipo_contiene'];
        foreach ($rawRules as $needle => $ownerKey) {
            if (is_array($ownerKey)) {
                $needle = $ownerKey['texto'] ?? $needle;
                $ownerKey = $ownerKey['propietario_key'] ?? ($ownerKey['propietario'] ?? '');
            }

            $needle = strtolower(trim((string) $needle));
            $ownerKey = hotel_owner_distribution_key($ownerKey);

            if ($needle !== '' && isset($propietarios[$ownerKey]) && !empty($propietarios[$ownerKey]['activo'])) {
                $reglas[$needle] = $ownerKey;
            }
        }

        $habitaciones = [];
        $rawRooms = is_array($config['habitaciones'] ?? null) ? $config['habitaciones'] : [];
        foreach ($rawRooms as $row) {
            if (!is_array($row)) {
                continue;
            }

            $ownerKey = hotel_owner_distribution_key($row['propietario_key'] ?? ($row['propietario'] ?? ''));
            if (!isset($propietarios[$ownerKey]) || empty($propietarios[$ownerKey]['activo'])) {
                continue;
            }

            $assignment = [
                'propietario_key' => $ownerKey,
            ];

            foreach (['habitacion_id', 'numero', 'tipo'] as $field) {
                if (!array_key_exists($field, $row)) {
                    continue;
                }

                $value = is_string($row[$field]) ? trim($row[$field]) : $row[$field];
                if ($value !== '' && $value !== null) {
                    $assignment[$field] = $value;
                }
            }

            if (count($assignment) > 1) {
                $habitaciones[] = $assignment;
            }
        }

        return [
            'version' => 1,
            'propietario_default' => $defaultKey,
            'propietarios' => $propietarios,
            'reglas_tipo_contiene' => $reglas,
            'habitaciones' => $habitaciones,
        ];
    }
}

if (!function_exists('hotel_owner_distribution_config')) {
    function hotel_owner_distribution_config($hotelId = null)
    {
        $stored = hotel_config_get('propietarios.distribucion', hotel_owner_distribution_default(), $hotelId);
        return hotel_owner_distribution_normalize($stored);
    }
}

if (!function_exists('hotel_owner_distribution_normalize_payload')) {
    function hotel_owner_distribution_normalize_payload(array $payload)
    {
        $errors = [];
        $propietarios = [];
        $seen = [];

        $ownerRows = is_array($payload['propietarios'] ?? null) ? $payload['propietarios'] : [];
        foreach ($ownerRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $keyRaw = trim((string) ($row['key'] ?? ''));
            $name = trim((string) ($row['nombre'] ?? ''));
            $percentageRaw = trim((string) ($row['participacion_pct'] ?? ''));
            $active = !empty($row['activo']);
            $hasAny = $keyRaw !== '' || $name !== '' || $percentageRaw !== '';

            if (!$hasAny) {
                continue;
            }

            $ownerKey = hotel_owner_distribution_key($keyRaw !== '' ? $keyRaw : $name);
            if ($ownerKey === '' || !preg_match('/^[a-z0-9_-]{2,40}$/', $ownerKey)) {
                $errors[] = 'Cada propietario debe tener una clave de 2 a 40 caracteres usando letras, numeros, guion o guion bajo.';
                continue;
            }

            if (isset($seen[$ownerKey])) {
                $errors[] = 'El propietario "' . $ownerKey . '" esta duplicado.';
                continue;
            }

            if ($name === '') {
                $errors[] = 'El propietario "' . $ownerKey . '" debe tener nombre.';
                continue;
            }

            if (hotel_owner_distribution_text_length($name) > 80) {
                $errors[] = 'El nombre del propietario "' . $ownerKey . '" no debe exceder 80 caracteres.';
                continue;
            }

            if ($percentageRaw !== '' && !is_numeric(str_replace(',', '.', $percentageRaw))) {
                $errors[] = 'La participacion del propietario "' . $ownerKey . '" debe ser numerica.';
                continue;
            }

            $percentageNumber = $percentageRaw !== '' ? (float) str_replace(',', '.', $percentageRaw) : 100.0;
            if ($percentageNumber < 0 || $percentageNumber > 100) {
                $errors[] = 'La participacion del propietario "' . $ownerKey . '" debe estar entre 0 y 100.';
                continue;
            }
            $percentage = hotel_owner_distribution_percentage($percentageNumber);

            $seen[$ownerKey] = true;
            $propietarios[$ownerKey] = [
                'key' => $ownerKey,
                'nombre' => $name,
                'activo' => $active,
                'participacion_pct' => $percentage,
            ];
        }

        if (empty($propietarios)) {
            $errors[] = 'Debes configurar al menos un propietario.';
        }

        $activeOwners = array_filter($propietarios, static function (array $row) {
            return !empty($row['activo']);
        });
        $activeOwnerKeys = array_keys($activeOwners);

        if (!empty($propietarios) && empty($activeOwners)) {
            $errors[] = 'Debes dejar activo al menos un propietario.';
        }

        $defaultKey = hotel_owner_distribution_key($payload['propietario_default'] ?? '');
        if ($defaultKey === '') {
            $errors[] = 'Selecciona un propietario predeterminado.';
        } elseif (!isset($activeOwners[$defaultKey])) {
            $errors[] = 'El propietario predeterminado debe existir y estar activo.';
        }

        $reglas = [];
        $ruleRows = is_array($payload['reglas_tipo_contiene'] ?? null) ? $payload['reglas_tipo_contiene'] : [];
        foreach ($ruleRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $needle = strtolower(trim((string) ($row['texto'] ?? '')));
            $ownerKey = hotel_owner_distribution_key($row['propietario_key'] ?? '');
            $hasAny = $needle !== '' || $ownerKey !== '';

            if (!$hasAny) {
                continue;
            }

            if ($needle === '') {
                $errors[] = 'Cada regla por tipo debe tener el texto a buscar.';
                continue;
            }

            if (hotel_owner_distribution_text_length($needle) > 60) {
                $errors[] = 'La regla "' . $needle . '" no debe exceder 60 caracteres.';
                continue;
            }

            if (!isset($activeOwners[$ownerKey])) {
                $errors[] = 'La regla "' . $needle . '" debe apuntar a un propietario activo.';
                continue;
            }

            $reglas[$needle] = $ownerKey;
        }

        $habitaciones = [];
        $assignmentRows = is_array($payload['habitaciones'] ?? null) ? $payload['habitaciones'] : [];
        foreach ($assignmentRows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $roomIdRaw = trim((string) ($row['habitacion_id'] ?? ''));
            $numero = trim((string) ($row['numero'] ?? ''));
            $tipo = strtolower(trim((string) ($row['tipo'] ?? '')));
            $ownerKey = hotel_owner_distribution_key($row['propietario_key'] ?? '');
            $hasScope = $roomIdRaw !== '' || $numero !== '' || $tipo !== '';
            $hasAny = $hasScope || $ownerKey !== '';

            if (!$hasAny) {
                continue;
            }

            if (!$hasScope) {
                $errors[] = 'Cada asignacion debe tener numero, tipo o id de habitacion.';
                continue;
            }

            if (!isset($activeOwners[$ownerKey])) {
                $errors[] = 'Cada asignacion de habitacion debe apuntar a un propietario activo.';
                continue;
            }

            $assignment = [
                'propietario_key' => $ownerKey,
            ];

            if ($roomIdRaw !== '') {
                if (!preg_match('/^\d+$/', $roomIdRaw)) {
                    $errors[] = 'El id de habitacion debe ser numerico.';
                    continue;
                }

                $assignment['habitacion_id'] = (int) $roomIdRaw;
            }

            if ($numero !== '') {
                if (hotel_owner_distribution_text_length($numero) > 30) {
                    $errors[] = 'El numero de habitacion "' . $numero . '" no debe exceder 30 caracteres.';
                    continue;
                }

                $assignment['numero'] = $numero;
            }

            if ($tipo !== '') {
                if (hotel_owner_distribution_text_length($tipo) > 80) {
                    $errors[] = 'El tipo de habitacion "' . $tipo . '" no debe exceder 80 caracteres.';
                    continue;
                }

                $assignment['tipo'] = $tipo;
            }

            $habitaciones[] = $assignment;
        }

        if (!empty($errors)) {
            return [
                'values' => hotel_owner_distribution_normalize([
                    'propietario_default' => $defaultKey ?: ($activeOwnerKeys[0] ?? ''),
                    'propietarios' => $propietarios,
                    'reglas_tipo_contiene' => $reglas,
                    'habitaciones' => $habitaciones,
                ]),
                'errors' => $errors,
            ];
        }

        return [
            'values' => hotel_owner_distribution_normalize([
                'version' => 1,
                'propietario_default' => $defaultKey,
                'propietarios' => $propietarios,
                'reglas_tipo_contiene' => $reglas,
                'habitaciones' => $habitaciones,
            ]),
            'errors' => [],
        ];
    }
}

if (!function_exists('hotel_owner_distribution_save')) {
    function hotel_owner_distribution_save(array $config, $hotelId = null)
    {
        return hotel_config_save_value(
            'propietarios.distribucion',
            hotel_owner_distribution_normalize($config),
            'json',
            'propietarios',
            'Distribucion configurable de ingresos por propietario.',
            $hotelId
        );
    }
}

if (!function_exists('hotel_feature_enabled')) {
    function hotel_feature_enabled($clave, $hotelId = null, $default = false)
    {
        $clave = strpos($clave, 'feature.') === 0 ? $clave : 'feature.' . $clave;

        return (bool) hotel_config_get($clave, $default, $hotelId);
    }
}

if (!function_exists('hotel_report_links_public_enabled')) {
    function hotel_report_links_public_enabled($hotelId = null)
    {
        return (bool) hotel_config_get('reportes.links_publicos_activos', true, $hotelId);
    }
}

if (!function_exists('hotel_report_link_expiration_days')) {
    function hotel_report_link_expiration_days($hotelId = null)
    {
        $days = (int) hotel_config_get('reportes.link_expiracion_dias', 7, $hotelId);

        return max(1, min(90, $days));
    }
}

if (!function_exists('hotel_report_email_enabled')) {
    function hotel_report_email_enabled($hotelId = null)
    {
        return (bool) hotel_config_get('reportes.email_envio_activo', false, $hotelId);
    }
}

if (!function_exists('hotel_report_email_recipients')) {
    function hotel_report_email_recipients($hotelId = null)
    {
        $value = (string) hotel_config_get('reportes.email_destinatarios', '', $hotelId);
        $emails = preg_split('/[,;\r\n]+/', $value);

        return array_values(array_unique(array_filter(array_map('trim', $emails), static function ($email) {
            return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
        })));
    }
}

if (!function_exists('hotel_notifications_automatic_enabled')) {
    function hotel_notifications_automatic_enabled($hotelId = null)
    {
        return (bool) hotel_config_get('notificaciones.automaticas_activas', true, $hotelId);
    }
}

if (!function_exists('hotel_notification_rule_enabled')) {
    function hotel_notification_rule_enabled($regla, $hotelId = null)
    {
        $regla = preg_replace('/[^a-z0-9_]+/i', '', (string) $regla);
        if ($regla === '') {
            return false;
        }

        return (bool) hotel_config_get('notificaciones.regla_' . $regla, true, $hotelId);
    }
}

if (!function_exists('hotel_notification_threshold')) {
    function hotel_notification_threshold($clave, $default, $hotelId = null, $min = 1, $max = 999)
    {
        $clave = preg_replace('/[^a-z0-9_]+/i', '', (string) $clave);
        $value = (int) hotel_config_get('notificaciones.' . $clave, (int) $default, $hotelId);

        return max((int) $min, min((int) $max, $value));
    }
}

if (!function_exists('hotel_guest_field_catalog')) {
    function hotel_guest_field_catalog()
    {
        return [
            'nombre_completo' => [
                'scope' => 'guest',
                'label' => 'Nombre completo',
                'descripcion' => 'Nombre legal o comercial del huesped.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'nombre_completo',
                'default_visible' => true,
                'default_required' => true,
                'locked' => true,
                'wide' => true,
                'max' => 200,
                'placeholder' => 'Nombre completo del huesped',
                'icon' => 'fa-user',
            ],
            'telefono' => [
                'scope' => 'guest',
                'label' => 'Telefono celular',
                'descripcion' => 'Dato base para recuperar clientes, confirmar reservas y contactar por WhatsApp.',
                'input' => 'tel',
                'storage' => 'column',
                'field' => 'telefono',
                'default_visible' => true,
                'default_required' => true,
                'locked' => true,
                'max' => 20,
                'placeholder' => '10 digitos',
                'icon' => 'fa-mobile-screen-button',
            ],
            'email' => [
                'scope' => 'guest',
                'label' => 'Email',
                'descripcion' => 'Correo para confirmaciones, cotizaciones o facturacion.',
                'input' => 'email',
                'storage' => 'column',
                'field' => 'email',
                'default_visible' => true,
                'default_required' => false,
                'max' => 160,
                'placeholder' => 'correo@ejemplo.com',
                'icon' => 'fa-envelope',
            ],
            'procedencia_estado' => [
                'scope' => 'guest',
                'label' => 'Estado de procedencia',
                'descripcion' => 'Estado o region de origen para reportes.',
                'input' => 'state',
                'storage' => 'column',
                'field' => 'procedencia_estado',
                'default_visible' => true,
                'default_required' => false,
                'icon' => 'fa-map-location-dot',
            ],
            'procedencia_ciudad' => [
                'scope' => 'guest',
                'label' => 'Ciudad de procedencia',
                'descripcion' => 'Ciudad de origen del huesped.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'procedencia_ciudad',
                'default_visible' => true,
                'default_required' => false,
                'max' => 120,
                'placeholder' => 'Ciudad de origen',
                'icon' => 'fa-city',
            ],
            'identificacion_tipo' => [
                'scope' => 'guest',
                'label' => 'Tipo de identificacion',
                'descripcion' => 'INE, pasaporte, licencia u otro documento.',
                'input' => 'select',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'options' => [
                    'ine' => 'INE',
                    'pasaporte' => 'Pasaporte',
                    'licencia' => 'Licencia de conducir',
                    'cedula' => 'Cedula profesional',
                    'otro' => 'Otro documento',
                ],
                'icon' => 'fa-id-card',
            ],
            'identificacion_numero' => [
                'scope' => 'guest',
                'label' => 'Numero o folio de identificacion',
                'descripcion' => 'Folio, numero o clave del documento presentado.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 80,
                'placeholder' => 'Folio o numero',
                'icon' => 'fa-fingerprint',
            ],
            'ine_folio' => [
                'scope' => 'guest',
                'label' => 'Clave/Folio INE',
                'descripcion' => 'Campo separado para hoteles que necesitan capturar la INE de forma especifica.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 80,
                'placeholder' => 'Folio INE',
                'uppercase' => true,
                'icon' => 'fa-address-card',
            ],
            'identificacion_archivo' => [
                'scope' => 'guest',
                'label' => 'Archivo de identificacion / INE',
                'descripcion' => 'Activa camara, galeria o selector de archivos para subir imagen o PDF al Centro Documental.',
                'input' => 'file',
                'storage' => 'document',
                'default_visible' => false,
                'default_required' => false,
                'accept' => 'image/*,.pdf,application/pdf',
                'accept_label' => 'JPG, PNG, WEBP o PDF hasta 10 MB',
                'ui_note' => 'Puede quedar visible y opcional, o visible y obligatorio segun la politica del hotel.',
                'wide' => true,
                'icon' => 'fa-camera',
            ],
            'curp' => [
                'scope' => 'guest',
                'label' => 'CURP',
                'descripcion' => 'Clave unica de registro de poblacion.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 18,
                'placeholder' => 'CURP',
                'uppercase' => true,
                'icon' => 'fa-barcode',
            ],
            'rfc' => [
                'scope' => 'guest',
                'label' => 'RFC',
                'descripcion' => 'Dato fiscal para hoteles que preparan facturacion desde recepcion.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 13,
                'placeholder' => 'RFC',
                'uppercase' => true,
                'icon' => 'fa-file-invoice',
            ],
            'fecha_nacimiento' => [
                'scope' => 'guest',
                'label' => 'Fecha de nacimiento',
                'descripcion' => 'Dato opcional para expedientes o politicas internas.',
                'input' => 'date',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'icon' => 'fa-cake-candles',
            ],
            'nacionalidad' => [
                'scope' => 'guest',
                'label' => 'Nacionalidad',
                'descripcion' => 'Pais o nacionalidad declarada por el huesped.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 80,
                'placeholder' => 'Mexicana, estadounidense, etc.',
                'icon' => 'fa-earth-americas',
            ],
            'direccion' => [
                'scope' => 'guest',
                'label' => 'Direccion',
                'descripcion' => 'Domicilio del huesped si el hotel lo requiere.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 220,
                'wide' => true,
                'placeholder' => 'Calle, numero, colonia',
                'icon' => 'fa-house',
            ],
            'codigo_postal' => [
                'scope' => 'guest',
                'label' => 'Codigo postal',
                'descripcion' => 'CP del huesped o de facturacion.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 12,
                'placeholder' => '00000',
                'icon' => 'fa-location-crosshairs',
            ],
            'contacto_emergencia_nombre' => [
                'scope' => 'guest',
                'label' => 'Contacto de emergencia',
                'descripcion' => 'Persona a quien contactar ante una emergencia.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 160,
                'placeholder' => 'Nombre del contacto',
                'icon' => 'fa-user-shield',
            ],
            'contacto_emergencia_telefono' => [
                'scope' => 'guest',
                'label' => 'Telefono de emergencia',
                'descripcion' => 'Celular del contacto de emergencia.',
                'input' => 'tel',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 10,
                'placeholder' => '10 digitos maximo',
                'icon' => 'fa-phone-volume',
            ],
            'empresa' => [
                'scope' => 'guest',
                'label' => 'Empresa',
                'descripcion' => 'Empresa, institucion o razon social relacionada con la estancia.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 160,
                'placeholder' => 'Empresa o institucion',
                'icon' => 'fa-building',
            ],
            'agencia' => [
                'scope' => 'guest',
                'label' => 'Agencia o canal',
                'descripcion' => 'Agencia, convenio o canal por el que llega el huesped.',
                'input' => 'text',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 160,
                'placeholder' => 'Agencia, OTA o convenio',
                'icon' => 'fa-briefcase',
            ],
            'motivo_viaje' => [
                'scope' => 'guest',
                'label' => 'Motivo de viaje',
                'descripcion' => 'Motivo principal de la estancia.',
                'input' => 'select',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'options' => [
                    'turismo' => 'Turismo',
                    'trabajo' => 'Trabajo',
                    'familia' => 'Familia',
                    'religioso' => 'Religioso',
                    'salud' => 'Salud',
                    'evento' => 'Evento',
                    'otro' => 'Otro',
                ],
                'icon' => 'fa-route',
            ],
            'preferencias' => [
                'scope' => 'guest',
                'label' => 'Preferencias del huesped',
                'descripcion' => 'Preferencias de habitacion, alergias, accesibilidad o notas recurrentes.',
                'input' => 'textarea',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 800,
                'rows' => 3,
                'wide' => true,
                'placeholder' => 'Preferencias o requerimientos especiales',
                'icon' => 'fa-star',
            ],
            'requiere_factura' => [
                'scope' => 'guest',
                'label' => 'Requiere factura',
                'descripcion' => 'Marca si el huesped suele requerir factura.',
                'input' => 'checkbox',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'icon' => 'fa-receipt',
            ],
            'notas' => [
                'scope' => 'guest',
                'label' => 'Notas internas',
                'descripcion' => 'Observaciones visibles para recepcion.',
                'input' => 'textarea',
                'storage' => 'column',
                'field' => 'notas',
                'default_visible' => true,
                'default_required' => false,
                'max' => 1200,
                'rows' => 4,
                'wide' => true,
                'placeholder' => 'Cualquier informacion adicional sobre el huesped',
                'icon' => 'fa-note-sticky',
            ],
            'vehiculo_marca' => [
                'scope' => 'vehicle',
                'label' => 'Marca',
                'descripcion' => 'Marca del vehiculo.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'marca',
                'default_visible' => true,
                'default_required' => false,
                'max' => 80,
                'placeholder' => 'Toyota, Nissan, etc.',
                'icon' => 'fa-car-side',
            ],
            'vehiculo_modelo' => [
                'scope' => 'vehicle',
                'label' => 'Modelo',
                'descripcion' => 'Modelo o linea del vehiculo.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'modelo',
                'default_visible' => true,
                'default_required' => false,
                'max' => 80,
                'placeholder' => 'Corolla, Sentra, etc.',
                'icon' => 'fa-car',
            ],
            'vehiculo_placas' => [
                'scope' => 'vehicle',
                'label' => 'Placas',
                'descripcion' => 'Placas para control de estacionamiento.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'placas',
                'default_visible' => true,
                'default_required' => false,
                'max' => 20,
                'placeholder' => 'ABC-123',
                'uppercase' => true,
                'icon' => 'fa-barcode',
            ],
            'vehiculo_color' => [
                'scope' => 'vehicle',
                'label' => 'Color',
                'descripcion' => 'Color del vehiculo.',
                'input' => 'text',
                'storage' => 'column',
                'field' => 'color',
                'default_visible' => true,
                'default_required' => false,
                'max' => 50,
                'placeholder' => 'Rojo, azul, blanco',
                'icon' => 'fa-palette',
            ],
            'vehiculo_tipo' => [
                'scope' => 'vehicle',
                'label' => 'Tipo de vehiculo',
                'descripcion' => 'Auto, camioneta, motocicleta u otro.',
                'input' => 'select',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'options' => [
                    'auto' => 'Auto',
                    'camioneta' => 'Camioneta',
                    'motocicleta' => 'Motocicleta',
                    'van' => 'Van',
                    'otro' => 'Otro',
                ],
                'icon' => 'fa-truck-pickup',
            ],
            'vehiculo_estacionamiento' => [
                'scope' => 'vehicle',
                'label' => 'Estacionamiento',
                'descripcion' => 'Zona o cajon donde se estaciona.',
                'input' => 'parking',
                'storage' => 'column',
                'field' => 'estacionamiento',
                'default_visible' => true,
                'default_required' => false,
                'icon' => 'fa-square-parking',
            ],
            'vehiculo_observaciones' => [
                'scope' => 'vehicle',
                'label' => 'Observaciones del vehiculo',
                'descripcion' => 'Notas internas sobre acceso, cajon o distintivos.',
                'input' => 'textarea',
                'storage' => 'extra',
                'default_visible' => false,
                'default_required' => false,
                'max' => 500,
                'rows' => 2,
                'wide' => true,
                'placeholder' => 'Observaciones del vehiculo',
                'icon' => 'fa-clipboard-list',
            ],
        ];
    }
}

if (!function_exists('hotel_guest_field_policy_defaults')) {
    function hotel_guest_field_policy_defaults()
    {
        $fields = [];

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            $visible = !empty($definition['default_visible']);
            $required = !empty($definition['default_required']);

            if (!empty($definition['locked'])) {
                $visible = true;
                $required = true;
            }

            $fields[$key] = [
                'visible' => $visible,
                'required' => $required,
            ];
        }

        return [
            'version' => 1,
            'fields' => $fields,
        ];
    }
}

if (!function_exists('hotel_guest_field_policy_normalize')) {
    function hotel_guest_field_policy_normalize($policy)
    {
        $defaults = hotel_guest_field_policy_defaults();
        $fields = $defaults['fields'];

        if (is_string($policy) && trim($policy) !== '') {
            $decoded = json_decode($policy, true);
            $policy = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($policy)) {
            $policy = [];
        }

        $storedFields = is_array($policy['fields'] ?? null) ? $policy['fields'] : $policy;

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            $stored = is_array($storedFields[$key] ?? null) ? $storedFields[$key] : [];
            $visible = array_key_exists('visible', $stored) ? !empty($stored['visible']) : $fields[$key]['visible'];
            $required = array_key_exists('required', $stored) ? !empty($stored['required']) : $fields[$key]['required'];

            if (!empty($definition['locked'])) {
                $visible = true;
                $required = true;
            }

            if ($required) {
                $visible = true;
            }

            if (!$visible) {
                $required = false;
            }

            $fields[$key] = [
                'visible' => $visible,
                'required' => $required,
            ];
        }

        return [
            'version' => 1,
            'fields' => $fields,
        ];
    }
}

if (!function_exists('hotel_guest_field_policy')) {
    function hotel_guest_field_policy($hotelId = null)
    {
        $stored = hotel_config_get('huespedes.campos_registro', [], $hotelId);
        return hotel_guest_field_policy_normalize($stored);
    }
}

if (!function_exists('hotel_guest_field_policy_normalize_payload')) {
    function hotel_guest_field_policy_normalize_payload(array $payload)
    {
        $fields = [];

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            $row = is_array($payload[$key] ?? null) ? $payload[$key] : [];
            $visible = !empty($row['visible']);
            $required = !empty($row['required']);

            if (!empty($definition['locked'])) {
                $visible = true;
                $required = true;
            }

            if ($required) {
                $visible = true;
            }

            if (!$visible) {
                $required = false;
            }

            $fields[$key] = [
                'visible' => $visible,
                'required' => $required,
            ];
        }

        return hotel_guest_field_policy_normalize([
            'version' => 1,
            'fields' => $fields,
        ]);
    }
}

if (!function_exists('hotel_guest_field_policy_save')) {
    function hotel_guest_field_policy_save(array $policy, $hotelId = null)
    {
        return hotel_config_save_value(
            'huespedes.campos_registro',
            hotel_guest_field_policy_normalize($policy),
            'json',
            'huespedes',
            'Politica de campos visibles y obligatorios para registro de huespedes y vehiculos.',
            $hotelId
        );
    }
}

if (!function_exists('hotel_guest_field_visible')) {
    function hotel_guest_field_visible($fieldKey, array $policy = null)
    {
        $policy = $policy ?: hotel_guest_field_policy();
        return !empty($policy['fields'][(string) $fieldKey]['visible']);
    }
}

if (!function_exists('hotel_guest_field_required')) {
    function hotel_guest_field_required($fieldKey, array $policy = null)
    {
        $policy = $policy ?: hotel_guest_field_policy();
        return !empty($policy['fields'][(string) $fieldKey]['required']);
    }
}

if (!function_exists('hotel_guest_visible_fields')) {
    function hotel_guest_visible_fields($scope, array $policy = null)
    {
        $scope = (string) $scope;
        $policy = $policy ?: hotel_guest_field_policy();
        $fields = [];

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            if (($definition['scope'] ?? '') !== $scope) {
                continue;
            }

            if (!hotel_guest_field_visible($key, $policy)) {
                continue;
            }

            $fields[$key] = $definition;
        }

        return $fields;
    }
}

if (!function_exists('hotel_guest_decode_extra_json')) {
    function hotel_guest_decode_extra_json($value)
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) ($value ?? ''), true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('hotel_guest_sanitize_field_value')) {
    function hotel_guest_sanitize_field_value($fieldKey, $value, array $definition)
    {
        if (($definition['input'] ?? 'text') === 'checkbox') {
            return !empty($value) ? '1' : '0';
        }

        $value = trim((string) ($value ?? ''));
        $cleanValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if ($cleanValue !== null) {
            $value = $cleanValue;
        }

        if (!empty($definition['uppercase'])) {
            $value = function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
        }

        $max = (int) ($definition['max'] ?? 0);
        if ($max > 0) {
            if (function_exists('mb_substr')) {
                $value = mb_substr($value, 0, $max, 'UTF-8');
            } else {
                $value = substr($value, 0, $max);
            }
        }

        return $value;
    }
}

if (!function_exists('hotel_guest_collect_extra_values')) {
    function hotel_guest_collect_extra_values(array $payload, $scope, array $policy = null, array $existing = [])
    {
        $scope = (string) $scope;
        $policy = $policy ?: hotel_guest_field_policy();
        $values = $existing;

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            if (($definition['scope'] ?? '') !== $scope || ($definition['storage'] ?? '') !== 'extra') {
                continue;
            }

            if (!hotel_guest_field_visible($key, $policy)) {
                continue;
            }

            $values[$key] = hotel_guest_sanitize_field_value($key, $payload[$key] ?? null, $definition);
        }

        return $values;
    }
}

if (!function_exists('hotel_guest_has_vehicle_payload')) {
    function hotel_guest_has_vehicle_payload(array $vehicle, array $policy = null)
    {
        $policy = $policy ?: hotel_guest_field_policy();

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            if (($definition['scope'] ?? '') !== 'vehicle' || !hotel_guest_field_visible($key, $policy)) {
                continue;
            }

            $storage = $definition['storage'] ?? 'column';
            $field = $storage === 'column' ? ($definition['field'] ?? $key) : $key;
            $raw = $storage === 'column' ? ($vehicle[$field] ?? null) : (($vehicle['extras'][$key] ?? null) ?? ($vehicle[$key] ?? null));

            if (($definition['input'] ?? 'text') === 'checkbox') {
                if (!empty($raw)) {
                    return true;
                }
                continue;
            }

            if (trim((string) ($raw ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hotel_guest_field_validation_errors')) {
    function hotel_guest_field_validation_errors(array $columnData, array $extraData = [], $scope = 'guest', array $policy = null, $contextLabel = '')
    {
        $scope = (string) $scope;
        $policy = $policy ?: hotel_guest_field_policy();
        $errors = [];

        foreach (hotel_guest_field_catalog() as $key => $definition) {
            if (($definition['scope'] ?? '') !== $scope || !hotel_guest_field_visible($key, $policy)) {
                continue;
            }

            $storage = $definition['storage'] ?? 'column';
            if ($storage === 'document') {
                continue;
            }

            $field = $storage === 'column' ? ($definition['field'] ?? $key) : $key;
            $value = $storage === 'column' ? ($columnData[$field] ?? null) : ($extraData[$key] ?? null);
            $label = $definition['label'] ?? $key;
            $prefix = trim((string) $contextLabel) !== '' ? trim((string) $contextLabel) . ': ' : '';

            if (hotel_guest_field_required($key, $policy)) {
                $isEmpty = ($definition['input'] ?? 'text') === 'checkbox'
                    ? empty($value)
                    : trim((string) ($value ?? '')) === '';

                if ($isEmpty) {
                    $errors[] = $prefix . $label . ' es obligatorio.';
                    continue;
                }
            }

            $valueText = trim((string) ($value ?? ''));
            if ($valueText === '') {
                continue;
            }

            $input = $definition['input'] ?? 'text';

            if ($input === 'email' && !filter_var($valueText, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $prefix . $label . ' no tiene un formato valido.';
            }

            if ($input === 'tel') {
                $digits = preg_replace('/\D+/', '', $valueText);
                if (strlen($digits) < 7 || strlen($digits) > 15) {
                    $errors[] = $prefix . $label . ' debe contener entre 7 y 15 digitos.';
                }
            }

            if ($input === 'date' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valueText)) {
                $errors[] = $prefix . $label . ' debe tener formato AAAA-MM-DD.';
            }

            if ($input === 'select' && !empty($definition['options']) && !array_key_exists($valueText, $definition['options'])) {
                $errors[] = $prefix . $label . ' contiene una opcion no valida.';
            }
        }

        return $errors;
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

        if (!empty($_SESSION['hotel_id'])) {
            return (int) $_SESSION['hotel_id'];
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

        if (!empty($_SESSION['hotel_id'])) {
            $hotelId = (int) $_SESSION['hotel_id'];
            return $hotelId;
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

        // Fallback de compatibilidad mono-hotel: solo se usa cuando no existe TenantContext
        // ni hotel_id en sesion. Las rutas /h/{slug}/login no deben depender de este fallback.
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
