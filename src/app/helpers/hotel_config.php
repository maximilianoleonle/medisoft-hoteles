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

        if ($input === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return [
                'ok' => false,
                'error' => "{$label} no tiene un formato valido.",
            ];
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

if (!function_exists('hotel_room_catalog_type_rows')) {
    function hotel_room_catalog_type_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.habitacion_tipos', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_room_catalog_sanitize_type_rows($storedRows)
            : hotel_room_catalog_default_type_rows($hotelId);

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
            ? hotel_general_catalog_sanitize_rows($storedRows, false)
            : hotel_general_catalog_default_parking_rows();

        return $includeInactive ? $rows : hotel_general_catalog_active_rows($rows);
    }
}

if (!function_exists('hotel_general_catalog_unit_rows')) {
    function hotel_general_catalog_unit_rows($hotelId = null, $includeInactive = true)
    {
        $storedRows = hotel_config_get('catalogos.unidades_medida', null, $hotelId);
        $rows = is_array($storedRows) && !empty($storedRows)
            ? hotel_general_catalog_sanitize_rows($storedRows, true)
            : hotel_general_catalog_default_unit_rows();

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
    function hotel_general_catalog_sanitize_rows(array $rows, $withAbbreviation = false)
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
        $parkingResult = hotel_general_catalog_normalize_rows($payload['parkings'] ?? [], 'estacionamiento', false, false);
        $unitResult = hotel_general_catalog_normalize_rows($payload['units'] ?? [], 'unidad de medida', true, true);

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
    function hotel_general_catalog_normalize_rows($rows, $labelSingular, $withAbbreviation = false, $requireActive = true)
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
            $abbreviation = trim((string) ($row['abreviatura'] ?? ''));
            $hasAny = $codigo !== '' || $label !== '' || $abbreviation !== '';

            if (!$hasAny) {
                continue;
            }

            if ($codigo === '' || !preg_match('/^[a-z0-9_]{2,30}$/', $codigo)) {
                $errors[] = 'Cada ' . $labelSingular . ' debe tener un codigo de 2 a 30 caracteres usando letras, numeros o guion bajo.';
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
