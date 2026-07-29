<?php
/**
 * Guardado de la configuracion de un hotel (pantalla de ajustes), con el hotel
 * SIEMPRE explicito.
 *
 * Existe porque la misma pantalla se usa desde dos lugares:
 *   - /configuracion (contexto del hotel de la sesion)
 *   - /admin/saas/hoteles/{id}/configuracion (Medisoft configurando a un cliente)
 *
 * Todo lo que se guarda aqui vive en hotel_configuracion (por hotel). La tabla
 * legacy `configuracion` NO se toca: no tiene hotel_id (UNIQUE por clave a
 * secas), asi que escribirla desde un hotel pisaria a todos los demas.
 */
class HotelConfiguracionService
{
    /**
     * Normaliza el POST completo de la pantalla.
     *
     * @return array{values: array, errors: string[]}
     */
    public static function normalizarPayload(array $post): array
    {
        $values = [];
        $errors = [];

        $ajustes = $post['hotel_config'] ?? null;
        if (is_array($ajustes) && function_exists('hotel_config_normalize_editable_payload')) {
            $resultado = hotel_config_normalize_editable_payload($ajustes);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['ajustes'] = $resultado['values'];
            }
        }

        $catalogoHabitaciones = $post['room_catalog'] ?? null;
        if (is_array($catalogoHabitaciones) && function_exists('hotel_room_catalog_normalize_payload')) {
            $resultado = hotel_room_catalog_normalize_payload($catalogoHabitaciones);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['room_catalog'] = $resultado['values'];
            }
        }

        $catalogoGeneral = $post['general_catalog'] ?? null;
        if (is_array($catalogoGeneral) && function_exists('hotel_general_catalog_normalize_payload')) {
            $resultado = hotel_general_catalog_normalize_payload($catalogoGeneral);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['general_catalog'] = $resultado['values'];
            }
        }

        $camposHuesped = $post['guest_fields'] ?? null;
        if (is_array($camposHuesped) && function_exists('hotel_guest_field_policy_normalize_payload')) {
            $values['guest_fields'] = hotel_guest_field_policy_normalize_payload($camposHuesped);
        }

        $propietarios = $post['owner_config'] ?? null;
        if (is_array($propietarios) && function_exists('hotel_owner_distribution_normalize_payload')) {
            $resultado = hotel_owner_distribution_normalize_payload($propietarios);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['owner_config'] = $resultado['values'];
            }
        }

        if (isset($post['footer_nav_submitted']) && function_exists('hotel_footer_nav_normalize_payload')) {
            $resultado = hotel_footer_nav_normalize_payload($post['footer_nav'] ?? []);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['footer_nav'] = $resultado['values'];
            }
        }

        $apariencia = $post['hotel_appearance'] ?? null;
        if (is_array($apariencia)) {
            $resultado = self::normalizarApariencia($apariencia);
            if (!empty($resultado['errors'])) {
                $errors = array_merge($errors, $resultado['errors']);
            } else {
                $values['appearance'] = $resultado['values'];
            }
        }

        return ['values' => $values, 'errors' => $errors];
    }

    /**
     * Fondo del sistema: solo dos modos y un hex de 6 digitos.
     *
     * @return array{values: array, errors: string[]}
     */
    public static function normalizarApariencia(array $payload): array
    {
        $modo = trim((string) ($payload['background_mode'] ?? 'default'));
        $color = strtoupper(trim((string) ($payload['background_color'] ?? '')));

        if (!in_array($modo, ['default', 'custom'], true)) {
            $modo = 'default';
        }

        if ($modo === 'custom' && !preg_match('/^#[0-9A-F]{6}$/', $color)) {
            return [
                'values' => [],
                'errors' => ['Elige el color con el selector; el valor no es válido.'],
            ];
        }

        return [
            'values' => [
                'background_mode' => $modo,
                'background_color' => $modo === 'custom' ? $color : '',
            ],
            'errors' => [],
        ];
    }

    /**
     * Escribe los bloques normalizados en el hotel indicado.
     * Lanza RuntimeException si el hotel no es valido; el llamador maneja la transaccion.
     */
    public static function guardar(array $normalizado, $hotelId): void
    {
        $hotelId = (int) $hotelId;

        if ($hotelId <= 0) {
            throw new RuntimeException('No se pudo resolver el hotel para guardar la configuración.');
        }

        if (!empty($normalizado['ajustes']) && function_exists('hotel_config_save_editable_values')) {
            hotel_config_save_editable_values($normalizado['ajustes'], $hotelId);
        }

        if (isset($normalizado['room_catalog']) && is_array($normalizado['room_catalog'])
            && function_exists('hotel_room_catalog_save_values')) {
            hotel_room_catalog_save_values($normalizado['room_catalog'], $hotelId);
        }

        if (isset($normalizado['general_catalog']) && is_array($normalizado['general_catalog'])
            && function_exists('hotel_general_catalog_save_values')) {
            hotel_general_catalog_save_values($normalizado['general_catalog'], $hotelId);
        }

        if (isset($normalizado['guest_fields']) && is_array($normalizado['guest_fields'])
            && function_exists('hotel_guest_field_policy_save')) {
            hotel_guest_field_policy_save($normalizado['guest_fields'], $hotelId);
        }

        if (isset($normalizado['owner_config']) && is_array($normalizado['owner_config'])
            && function_exists('hotel_owner_distribution_save')) {
            hotel_owner_distribution_save($normalizado['owner_config'], $hotelId);
        }

        if (isset($normalizado['footer_nav']) && is_array($normalizado['footer_nav'])
            && function_exists('hotel_footer_nav_save')) {
            hotel_footer_nav_save($normalizado['footer_nav'], $hotelId);
        }

        if (isset($normalizado['appearance']) && is_array($normalizado['appearance'])
            && function_exists('hotel_config_save_value')) {
            hotel_config_save_value(
                'apariencia.fondo_sistema',
                $normalizado['appearance']['background_color'],
                'string',
                'apariencia',
                'Color de fondo global de las vistas operativas del hotel en modo claro.',
                $hotelId
            );
        }
    }

    /**
     * Datos que la vista configuracion/index necesita, para cualquier hotel.
     * Sin fallback a la tabla legacy `configuracion`: es global y contaminaria
     * al hotel objetivo con datos de otro.
     */
    public static function datosDeVista($hotelId): array
    {
        $hotelId = (int) $hotelId;

        return [
            'hotelSettingDefinitions' => function_exists('hotel_config_editable_definitions') ? hotel_config_editable_definitions() : [],
            'hotelSettings' => function_exists('hotel_config_editable_values') ? hotel_config_editable_values($hotelId) : [],
            'roomTypeCatalog' => function_exists('hotel_room_catalog_type_rows') ? hotel_room_catalog_type_rows($hotelId, true) : [],
            'roomFloorCatalog' => function_exists('hotel_room_catalog_floor_rows') ? hotel_room_catalog_floor_rows($hotelId, true) : [],
            'roomAmenityCatalog' => function_exists('hotel_room_catalog_amenity_rows') ? hotel_room_catalog_amenity_rows($hotelId, true) : [],
            'roomIncludedCatalog' => function_exists('hotel_room_catalog_included_rows') ? hotel_room_catalog_included_rows($hotelId, true) : [],
            'generalZoneCatalog' => function_exists('hotel_general_catalog_zone_rows') ? hotel_general_catalog_zone_rows($hotelId, true) : [],
            'generalParkingCatalog' => function_exists('hotel_general_catalog_parking_rows') ? hotel_general_catalog_parking_rows($hotelId, true) : [],
            'generalUnitCatalog' => function_exists('hotel_general_catalog_unit_rows') ? hotel_general_catalog_unit_rows($hotelId, true) : [],
            'guestFieldCatalog' => function_exists('hotel_guest_field_catalog') ? hotel_guest_field_catalog() : [],
            'guestFieldPolicy' => function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy($hotelId) : [],
            'ownerDistributionConfig' => function_exists('hotel_owner_distribution_config') ? hotel_owner_distribution_config($hotelId) : [],
            'footerNavCatalog' => function_exists('hotel_footer_nav_available_catalog') ? hotel_footer_nav_available_catalog() : [],
            'footerNavSelected' => function_exists('hotel_footer_nav_items') ? array_keys(hotel_footer_nav_items($hotelId)) : [],
            'footerNavMax' => function_exists('hotel_footer_nav_max') ? hotel_footer_nav_max() : 4,
            'footerNavMin' => function_exists('hotel_footer_nav_min') ? hotel_footer_nav_min() : 2,
        ];
    }

    /**
     * Color de fondo guardado por el hotel ('' = usa el de la marca).
     */
    public static function fondoGuardado($hotelId): string
    {
        if (!function_exists('hotel_config_get')) {
            return '';
        }

        $valor = trim((string) hotel_config_get('apariencia.fondo_sistema', '', (int) $hotelId));

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) ? strtoupper($valor) : '';
    }
}
