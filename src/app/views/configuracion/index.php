<?php
/**
 * Vista de configuracion del sistema hotelero.
 */
$configHotel = is_array($config['hotel'] ?? null) ? $config['hotel'] : [];
$configBranding = is_array($hotelBranding ?? null)
    ? $hotelBranding
    : (function_exists('current_hotel_branding') ? current_hotel_branding() : []);
if (!is_array($configBranding)) {
    $configBranding = [];
}

$configHotelNombre = function_exists('current_hotel_display_name')
    ? current_hotel_display_name($configHotel['nombre'] ?? 'Medisoft Hoteles')
    : ($configHotel['nombre'] ?? 'Medisoft Hoteles');
$configHotelSlug = function_exists('current_hotel_slug') ? current_hotel_slug() : ($_SESSION['hotel_slug'] ?? '');
$configHotelId = function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null);

$configHotelLogo = '';
if (function_exists('hotel_branding_asset_url')) {
    $configHotelLogo = hotel_branding_asset_url($configBranding['logo_url'] ?? null)
        ?: (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : asset('img/logo.png'));
}

$configHotelSettingDefinitions = is_array($hotelSettingDefinitions ?? null)
    ? $hotelSettingDefinitions
    : (function_exists('hotel_config_editable_definitions') ? hotel_config_editable_definitions() : []);
$configHotelSettings = is_array($hotelSettings ?? null)
    ? $hotelSettings
    : (function_exists('hotel_config_editable_values') ? hotel_config_editable_values() : []);
$configNotificationSettingDefinitions = [];
foreach ($configHotelSettingDefinitions as $settingKey => $settingDefinition) {
    if (($settingDefinition['grupo'] ?? '') === 'notificaciones') {
        $configNotificationSettingDefinitions[$settingKey] = $settingDefinition;
        unset($configHotelSettingDefinitions[$settingKey]);
    }
}
$configNotificationGlobalKeys = [
    'notificaciones.automaticas_activas',
    'notificaciones.pwa_push_activo',
    'notificaciones.pwa_push_solo_prioritarias',
    'notificaciones.pwa_push_automaticas',
    'notificaciones.pwa_push_eventos',
];
$configNotificationRuleKeys = [
    'notificaciones.regla_checkins_pendientes',
    'notificaciones.regla_checkouts_pendientes',
    'notificaciones.regla_facturas_pendientes',
    'notificaciones.regla_mantenimiento_activo',
    'notificaciones.regla_habitaciones_limpieza',
    'notificaciones.regla_caja_abierta_prolongada',
    'notificaciones.regla_inventario_bajo',
    'notificaciones.regla_reporte_gerencial_diario',
];
$configNotificationThresholdKeys = [
    'notificaciones.umbral_retraso_alta_dias',
    'notificaciones.umbral_facturas_alta',
    'notificaciones.umbral_limpieza_media',
    'notificaciones.umbral_caja_horas_media',
    'notificaciones.umbral_caja_horas_alta',
];
$configRoomTypeRows = is_array($roomTypeCatalog ?? null)
    ? array_values($roomTypeCatalog)
    : (function_exists('hotel_room_catalog_type_rows') ? hotel_room_catalog_type_rows(null, true) : []);
$configRoomFloorRows = is_array($roomFloorCatalog ?? null)
    ? array_values($roomFloorCatalog)
    : (function_exists('hotel_room_catalog_floor_rows') ? hotel_room_catalog_floor_rows(null, true) : []);
$configRoomAmenityRows = is_array($roomAmenityCatalog ?? null)
    ? array_values($roomAmenityCatalog)
    : (function_exists('hotel_room_catalog_amenity_rows') ? hotel_room_catalog_amenity_rows(null, true) : []);
$configGeneralZoneRows = is_array($generalZoneCatalog ?? null)
    ? array_values($generalZoneCatalog)
    : (function_exists('hotel_general_catalog_zone_rows') ? hotel_general_catalog_zone_rows(null, true) : []);
$configGeneralParkingRows = is_array($generalParkingCatalog ?? null)
    ? array_values($generalParkingCatalog)
    : (function_exists('hotel_general_catalog_parking_rows') ? hotel_general_catalog_parking_rows(null, true) : []);
$configParkingCapacityTotal = 0;
$configParkingActiveCount = 0;
foreach ($configGeneralParkingRows as $configParkingRow) {
    if (!empty($configParkingRow['activo'])) {
        $configParkingActiveCount++;
        $configParkingCapacityTotal += max(0, min(999, (int) ($configParkingRow['cupo'] ?? 0)));
    }
}
$configGeneralUnitRows = is_array($generalUnitCatalog ?? null)
    ? array_values($generalUnitCatalog)
    : (function_exists('hotel_general_catalog_unit_rows') ? hotel_general_catalog_unit_rows(null, true) : []);
$configPwaPushDevices = is_array($pwaPushDevices ?? null) ? array_values($pwaPushDevices) : [];
$configFooterNavCatalog = is_array($footerNavCatalog ?? null)
    ? $footerNavCatalog
    : (function_exists('hotel_footer_nav_available_catalog') ? hotel_footer_nav_available_catalog() : []);
$configFooterNavSelected = is_array($footerNavSelected ?? null)
    ? array_values(array_filter($footerNavSelected, static function ($navKey) use ($configFooterNavCatalog) {
        return isset($configFooterNavCatalog[$navKey]);
    }))
    : [];
$configFooterNavMax = max(1, (int) ($footerNavMax ?? 4));
$configFooterNavMin = max(1, (int) ($footerNavMin ?? 2));
$configGuestFieldCatalog = is_array($guestFieldCatalog ?? null)
    ? $guestFieldCatalog
    : (function_exists('hotel_guest_field_catalog') ? hotel_guest_field_catalog() : []);
$configGuestFieldPolicy = is_array($guestFieldPolicy ?? null)
    ? $guestFieldPolicy
    : (function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy($configHotelId) : ['fields' => []]);
$configOwnerDistribution = is_array($ownerDistributionConfig ?? null)
    ? $ownerDistributionConfig
    : (function_exists('hotel_owner_distribution_config') ? hotel_owner_distribution_config($configHotelId) : []);
if (function_exists('hotel_owner_distribution_normalize')) {
    $configOwnerDistribution = hotel_owner_distribution_normalize($configOwnerDistribution);
}
$configOwnerRows = is_array($configOwnerDistribution['propietarios'] ?? null)
    ? array_values($configOwnerDistribution['propietarios'])
    : [];
$configOwnerDefault = (string) ($configOwnerDistribution['propietario_default'] ?? '');
$configOwnerRuleRows = [];
if (is_array($configOwnerDistribution['reglas_tipo_contiene'] ?? null)) {
    foreach ($configOwnerDistribution['reglas_tipo_contiene'] as $ruleText => $ruleOwnerKey) {
        $configOwnerRuleRows[] = [
            'texto' => (string) $ruleText,
            'propietario_key' => (string) $ruleOwnerKey,
        ];
    }
}
$configOwnerAssignmentRows = is_array($configOwnerDistribution['habitaciones'] ?? null)
    ? array_values($configOwnerDistribution['habitaciones'])
    : [];
$configOwnerRowsByKey = [];
$configOwnerActiveRows = [];
foreach ($configOwnerRows as $ownerRow) {
    $ownerKey = (string) ($ownerRow['key'] ?? '');
    if ($ownerKey === '') {
        continue;
    }

    $configOwnerRowsByKey[$ownerKey] = $ownerRow;
    if (!empty($ownerRow['activo'])) {
        $configOwnerActiveRows[] = $ownerRow;
    }
}
$configOwnerDefaultRow = is_array($configOwnerRowsByKey[$configOwnerDefault] ?? null)
    ? $configOwnerRowsByKey[$configOwnerDefault]
    : [];
$configOwnerDefaultName = (string) ($configOwnerDefaultRow['nombre'] ?? $configOwnerDefault);
$configOwnerFormatPercentage = function ($value) {
    $value = is_numeric($value) ? (float) $value : 100.0;
    return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
};
$configGuestFieldsByScope = [
    'guest' => [],
    'vehicle' => [],
];
foreach ($configGuestFieldCatalog as $guestFieldKey => $guestFieldDefinition) {
    $guestFieldScope = (string)($guestFieldDefinition['scope'] ?? 'guest');
    if (!isset($configGuestFieldsByScope[$guestFieldScope])) {
        continue;
    }

    $configGuestFieldsByScope[$guestFieldScope][$guestFieldKey] = $guestFieldDefinition;
}
$configIdentityFieldKeys = [
    'identificacion_tipo',
    'identificacion_numero',
    'ine_folio',
    'identificacion_archivo',
];
$configIdentityFieldDefinitions = [];
foreach ($configIdentityFieldKeys as $identityFieldKey) {
    if (isset($configGuestFieldsByScope['guest'][$identityFieldKey])) {
        $configIdentityFieldDefinitions[$identityFieldKey] = $configGuestFieldsByScope['guest'][$identityFieldKey];
    }
}
$configIdentityAnyVisible = false;
$configIdentityAnyRequired = false;
$configIdentityFileVisible = false;
$configIdentityFileRequired = false;
foreach (array_keys($configIdentityFieldDefinitions) as $identityFieldKey) {
    $identityState = is_array($configGuestFieldPolicy['fields'][$identityFieldKey] ?? null)
        ? $configGuestFieldPolicy['fields'][$identityFieldKey]
        : [];
    $identityVisible = !empty($identityState['visible']);
    $identityRequired = !empty($identityState['required']);

    $configIdentityAnyVisible = $configIdentityAnyVisible || $identityVisible;
    $configIdentityAnyRequired = $configIdentityAnyRequired || $identityRequired;

    if ($identityFieldKey === 'identificacion_archivo') {
        $configIdentityFileVisible = $identityVisible;
        $configIdentityFileRequired = $identityRequired;
    }
}

$configPwaDeviceName = function (array $device) {
    $agent = (string)($device['navegador'] ?? '');
    $agentLower = strtolower($agent);
    $browser = 'Navegador';
    $platform = 'Dispositivo';

    if (strpos($agentLower, 'edg/') !== false) {
        $browser = 'Microsoft Edge';
    } elseif (strpos($agentLower, 'chrome/') !== false && strpos($agentLower, 'chromium') === false) {
        $browser = 'Chrome';
    } elseif (strpos($agentLower, 'safari/') !== false && strpos($agentLower, 'chrome/') === false) {
        $browser = 'Safari';
    } elseif (strpos($agentLower, 'firefox/') !== false) {
        $browser = 'Firefox';
    }

    if (strpos($agentLower, 'iphone') !== false) {
        $platform = 'iPhone';
    } elseif (strpos($agentLower, 'ipad') !== false) {
        $platform = 'iPad';
    } elseif (strpos($agentLower, 'android') !== false) {
        $platform = 'Android';
    } elseif (strpos($agentLower, 'windows') !== false) {
        $platform = 'Windows';
    } elseif (strpos($agentLower, 'mac os') !== false || strpos($agentLower, 'macintosh') !== false) {
        $platform = 'Mac';
    }

    return trim($browser . ' - ' . $platform);
};

$configPwaDate = function ($value) {
    if (!$value) {
        return 'Sin fecha';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : 'Sin fecha';
};

$configAppendBlankRows = function (array $rows, $count = 1) {
    for ($i = 0; $i < $count; $i++) {
        $rows[] = [];
    }

    return $rows;
};

$configBrandingField = function ($key, $default = '') use ($configBranding) {
    return htmlspecialchars((string) ($configBranding[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
$configBrandingAsset = function ($key) use ($configBranding) {
    if (!function_exists('hotel_branding_asset_url')) {
        return '';
    }

    return hotel_branding_asset_url($configBranding[$key] ?? null) ?: '';
};
$configBrandingPwaAsset = function ($key, $size) use ($configBranding) {
    if (!function_exists('hotel_branding_pwa_icon_asset_url')) {
        return '';
    }

    return hotel_branding_pwa_icon_asset_url($configBranding[$key] ?? null, $size) ?: '';
};
$configLegacyNombre = trim((string) ($configBranding['nombre_visual'] ?? '')) !== ''
    ? $configBranding['nombre_visual']
    : $configHotelNombre;
$configLegacyTelefono = $configHotelSettings['contacto.telefono'] ?? ($configHotel['telefono'] ?? '');
$configLegacyDireccion = $configHotelSettings['contacto.direccion'] ?? ($configHotel['direccion'] ?? '');
$configLegacyEmail = $configHotelSettings['contacto.email'] ?? ($configHotel['email'] ?? '');
$configLegacyCheckIn = $configHotelSettings['operacion.checkin_hora'] ?? ($configHotel['check_in_time'] ?? '15:00');
$configLegacyCheckOut = $configHotelSettings['operacion.checkout_hora'] ?? ($configHotel['check_out_time'] ?? '12:00');
$configHotelSetting = function ($key, $default = '') {
    return function_exists('hotel_setting') ? hotel_setting($key, $default) : $default;
};
$configOperationalDisplay = function ($value) {
    $text = trim((string) ($value ?? ''));

    return htmlspecialchars($text !== '' ? $text : 'No configurado', ENT_QUOTES, 'UTF-8');
};
$configOperationalReadOnly = [
    [
        'label' => 'Hora de check-in',
        'value' => $configHotelSetting('operacion.checkin_hora', '15:00'),
        'detail' => 'Inicio operativo para llegadas.',
        'icon' => 'fa-door-open',
        'featured' => true,
    ],
    [
        'label' => 'Hora de check-out',
        'value' => $configHotelSetting('operacion.checkout_hora', '12:00'),
        'detail' => 'Hora base de salida.',
        'icon' => 'fa-door-closed',
        'featured' => true,
    ],
    [
        'label' => 'Moneda',
        'value' => $configHotelSetting('operacion.moneda', 'MXN'),
        'detail' => 'Moneda mostrada al hotel.',
        'icon' => 'fa-coins',
        'featured' => false,
    ],
    [
        'label' => 'Telefono',
        'value' => $configHotelSetting('contacto.telefono', ''),
        'detail' => 'Contacto visible del hotel.',
        'icon' => 'fa-phone',
        'featured' => false,
    ],
    [
        'label' => 'Direccion',
        'value' => $configHotelSetting('contacto.direccion', ''),
        'detail' => 'Domicilio operativo registrado.',
        'icon' => 'fa-map-marker-alt',
        'featured' => false,
    ],
    [
        'label' => 'Nombre app/PWA',
        'value' => $configHotelSetting('pwa.nombre_app', 'Medisoft Hoteles'),
        'detail' => 'Nombre visible en la app instalada.',
        'icon' => 'fa-mobile-alt',
        'featured' => false,
    ],
];

$configHotelSettingGroupMeta = [
    'operacion' => [
        'title' => 'Operacion',
        'hint' => 'Horarios, moneda y zona horaria del hotel.',
        'icon' => 'fa-clock',
    ],
    'contacto' => [
        'title' => 'Contacto',
        'hint' => 'Datos visibles para comunicacion y documentos.',
        'icon' => 'fa-address-card',
    ],
    'documentos' => [
        'title' => 'Documentos',
        'hint' => 'Textos y visibilidad de logo en piezas futuras.',
        'icon' => 'fa-file-lines',
    ],
    'reservaciones' => [
        'title' => 'Reservaciones',
        'hint' => 'Mensajes informativos sin cambiar reglas operativas.',
        'icon' => 'fa-calendar-check',
    ],
    'reportes' => [
        'title' => 'Reportes',
        'hint' => 'Links seguros y correo para reportes compartidos.',
        'icon' => 'fa-chart-line',
    ],
    'pwa' => [
        'title' => 'App PWA',
        'hint' => 'Nombre visible sugerido para la app instalable.',
        'icon' => 'fa-mobile-screen-button',
    ],
    'otros' => [
        'title' => 'Otros ajustes',
        'hint' => 'Valores adicionales del hotel.',
        'icon' => 'fa-sliders-h',
    ],
];
$configGroupedHotelSettings = [];
foreach ($configHotelSettingDefinitions as $settingKey => $settingDefinition) {
    $settingGroup = (string) ($settingDefinition['grupo'] ?? 'otros');
    if (!isset($configHotelSettingGroupMeta[$settingGroup])) {
        $settingGroup = 'otros';
    }

    $configGroupedHotelSettings[$settingGroup][$settingKey] = $settingDefinition;
}

$configNotificationGroups = [
    [
        'title' => 'Activacion general',
        'hint' => 'Control maestro de avisos automaticos y envios push.',
        'keys' => $configNotificationGlobalKeys,
        'icon' => 'fa-toggle-on',
    ],
    [
        'title' => 'Reglas operativas',
        'hint' => 'Eventos que el dashboard puede convertir en avisos reales.',
        'keys' => $configNotificationRuleKeys,
        'icon' => 'fa-list-check',
    ],
    [
        'title' => 'Umbrales',
        'hint' => 'Valores que elevan la prioridad de los avisos.',
        'keys' => $configNotificationThresholdKeys,
        'icon' => 'fa-gauge-high',
    ],
];

$configRenderSettingField = function ($settingKey, array $settingDefinition, $settingValue) {
    $settingInput = $settingDefinition['input'] ?? 'text';
    $settingId = 'hotel_config_' . preg_replace('/[^a-z0-9_]+/i', '_', $settingKey);
    $settingMax = (int) ($settingDefinition['max'] ?? 0);
    $settingRows = max(2, (int) ($settingDefinition['rows'] ?? 3));
    $settingMin = $settingDefinition['min_value'] ?? null;
    $settingMaxValue = $settingDefinition['max_value'] ?? null;
    $settingStep = $settingDefinition['step'] ?? null;
    $settingHtmlType = in_array($settingInput, ['time', 'tel', 'email', 'number'], true) ? $settingInput : 'text';
    $settingIsTextarea = $settingInput === 'textarea' || $settingInput === 'email_list';
    $settingIsWide = $settingIsTextarea || in_array($settingKey, ['contacto.direccion'], true);
    $settingLabel = $settingDefinition['label'] ?? $settingKey;
    $settingDescription = $settingDefinition['descripcion'] ?? '';

    ob_start();
    ?>
    <div class="hc-field<?= $settingIsWide ? ' is-wide' : '' ?><?= $settingInput === 'checkbox' ? ' is-switch-field' : '' ?>">
        <?php if ($settingInput === 'checkbox'): ?>
            <input type="hidden"
                   name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                   value="0">
            <label class="hc-switch" for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                <input type="checkbox"
                       id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                       name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                       value="1"
                       <?= (bool) $settingValue ? 'checked' : '' ?>>
                <span class="hc-switch-ui" aria-hidden="true"></span>
                <span class="hc-switch-text">
                    <strong><?= htmlspecialchars($settingLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php if ($settingDescription !== ''): ?>
                        <small><?= htmlspecialchars($settingDescription, ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                </span>
            </label>
        <?php elseif ($settingIsTextarea): ?>
            <label for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($settingLabel, ENT_QUOTES, 'UTF-8') ?>
            </label>
            <textarea id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                      name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                      rows="<?= (int) $settingRows ?>"
                      class="form-input"
                      <?= $settingMax > 0 ? 'maxlength="' . (int) $settingMax . '"' : '' ?>><?= htmlspecialchars((string) $settingValue, ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php if ($settingDescription !== ''): ?>
                <p class="hc-field-hint"><?= htmlspecialchars($settingDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php else: ?>
            <label for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($settingLabel, ENT_QUOTES, 'UTF-8') ?>
            </label>
            <input type="<?= htmlspecialchars($settingHtmlType, ENT_QUOTES, 'UTF-8') ?>"
                   id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                   name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                   value="<?= htmlspecialchars((string) $settingValue, ENT_QUOTES, 'UTF-8') ?>"
                   class="form-input<?= $settingInput === 'currency' ? ' hc-uppercase' : '' ?>"
                   <?= $settingInput === 'currency' ? 'maxlength="3"' : '' ?>
                   <?= $settingInput === 'timezone' ? 'maxlength="80" placeholder="America/Mexico_City"' : '' ?>
                   <?= $settingMax > 0 && !in_array($settingInput, ['currency', 'timezone'], true) ? 'maxlength="' . (int) $settingMax . '"' : '' ?>
                   <?= $settingMin !== null ? 'min="' . (int) $settingMin . '"' : '' ?>
                   <?= $settingMaxValue !== null ? 'max="' . (int) $settingMaxValue . '"' : '' ?>
                   <?= $settingStep !== null ? 'step="' . htmlspecialchars((string) $settingStep, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
            <?php if ($settingDescription !== ''): ?>
                <p class="hc-field-hint"><?= htmlspecialchars($settingDescription, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
};

$configRenderGuestFieldPolicy = function ($fieldKey, array $fieldDefinition) use ($configGuestFieldPolicy) {
    $fieldState = is_array($configGuestFieldPolicy['fields'][$fieldKey] ?? null)
        ? $configGuestFieldPolicy['fields'][$fieldKey]
        : [];
    $label = (string)($fieldDefinition['label'] ?? $fieldKey);
    $description = (string)($fieldDefinition['descripcion'] ?? '');
    $icon = (string)($fieldDefinition['icon'] ?? 'fa-circle-dot');
    $locked = !empty($fieldDefinition['locked']);
    $visible = $locked || !empty($fieldState['visible']);
    $required = $locked || !empty($fieldState['required']);
    $inputType = (string)($fieldDefinition['input'] ?? 'text');
    $fieldId = 'guest_field_' . preg_replace('/[^a-z0-9_]+/i', '_', (string)$fieldKey);

    ob_start();
    ?>
    <article class="hc-policy-row<?= $locked ? ' is-locked' : '' ?>"
             data-guest-policy-row
             data-guest-field="<?= htmlspecialchars((string)$fieldKey, ENT_QUOTES, 'UTF-8') ?>">
        <div class="hc-policy-main">
            <span class="hc-policy-icon"><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i></span>
            <div>
                <h4><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h4>
                <?php if ($description !== ''): ?>
                    <p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <span class="hc-policy-type"><?= htmlspecialchars(strtoupper($inputType), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="hc-policy-controls">
            <?php if ($locked): ?>
                <input type="hidden" name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][visible]" value="1">
                <input type="hidden" name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][required]" value="1">
                <span class="hc-policy-lock"><i class="fas fa-lock"></i> Base</span>
                <span class="hc-policy-lock is-required"><i class="fas fa-check"></i> Obligatorio</span>
            <?php else: ?>
                <input type="hidden" name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][visible]" value="0">
                <label class="hc-policy-toggle" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_visible">
                    <input type="checkbox"
                           id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_visible"
                           name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][visible]"
                           value="1"
                           data-guest-visible
                           <?= $visible ? 'checked' : '' ?>>
                    <span class="hc-switch-ui" aria-hidden="true"></span>
                    <strong>Visible</strong>
                </label>

                <input type="hidden" name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][required]" value="0">
                <label class="hc-policy-toggle" for="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_required">
                    <input type="checkbox"
                           id="<?= htmlspecialchars($fieldId, ENT_QUOTES, 'UTF-8') ?>_required"
                           name="guest_fields[<?= htmlspecialchars($fieldKey, ENT_QUOTES, 'UTF-8') ?>][required]"
                           value="1"
                           data-guest-required
                           <?= $required ? 'checked' : '' ?>>
                    <span class="hc-switch-ui" aria-hidden="true"></span>
                    <strong>Obligatorio</strong>
                </label>
            <?php endif; ?>
        </div>
    </article>
    <?php
    return ob_get_clean();
};

?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.hc-page {
    --hc-brand: var(--brand-primary, #223b36);
    --hc-brand-strong: var(--brand-secondary, #17241f);
    --hc-accent: var(--brand-accent, #b9873f);
    --hc-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --hc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --hc-accent-soft: color-mix(in srgb, var(--hc-accent) 14%, #fffffb);
    --hc-bg: #f4f1ea;
    --hc-bg-deep: #ebe5d9;
    --hc-surface: var(--hc-paper, #FFFFFD);
    --hc-surface-muted: color-mix(in srgb, var(--hc-brand) 4%, var(--hc-paper, #FFFFFD));
    --hc-ink: #18211f;
    --hc-ink-soft: #58625e;
    --hc-ink-faint: #7c8580;
    --hc-line: color-mix(in srgb, var(--hc-brand) 13%, #ded7ca);
    --hc-line-strong: color-mix(in srgb, var(--hc-brand) 24%, #cfc5b5);
    --hc-danger: #a3413c;
    --hc-success: #247857;
    --hc-focus: color-mix(in srgb, var(--hc-accent) 45%, #f7d99a);
    --hc-shadow: 0 18px 48px -36px color-mix(in srgb, var(--hc-brand) 70%, transparent);
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--hc-accent) 16%, transparent), transparent 34rem),
        linear-gradient(180deg, var(--hc-bg), var(--hc-bg-deep));
    color: var(--hc-ink);
    font-family: var(--hc-sans);
    opacity: 0;
    transition: opacity .2s ease-out;
}

.hc-page.is-ready {
    opacity: 1;
}

.hc-page * {
    box-sizing: border-box;
}

.hc-shell {
    width: min(1480px, calc(100% - 32px));
    margin: 0 auto;
    padding: 26px 0 52px;
}

.hc-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, 390px);
    gap: 18px;
    align-items: stretch;
}

.hc-top-main,
.hc-identity,
.hc-nav-card,
.hc-panel,
.hc-save-dock {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    box-shadow: var(--hc-shadow);
}

.hc-top-main {
    min-height: 220px;
    border-radius: 24px;
    padding: 30px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.hc-top-main::after {
    content: "";
    position: absolute;
    inset: auto -12% -58% 48%;
    height: 220px;
    background: color-mix(in srgb, var(--hc-accent) 18%, transparent);
    border-radius: 999px;
    pointer-events: none;
}

.hc-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    margin: 0 0 12px;
    padding: 7px 10px;
    border-radius: 10px;
    color: var(--hc-brand);
    background: var(--hc-surface-muted);
    border: 1px solid var(--hc-line);
    font-size: .72rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.hc-title {
    margin: 0;
    max-width: 760px;
    color: var(--hc-brand-strong);
    font-size: 2.55rem;
    line-height: 1.03;
    font-weight: 780;
    text-wrap: balance;
}

.hc-subtitle {
    max-width: 780px;
    margin: 14px 0 0;
    color: var(--hc-ink-soft);
    font-size: 1rem;
    line-height: 1.6;
}

.hc-top-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 24px;
    position: relative;
    z-index: 1;
}

.hc-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 7px 10px;
    border-radius: 12px;
    color: var(--hc-brand-strong);
    background: color-mix(in srgb, var(--hc-brand) 5%, var(--hc-paper, #FFFFFD));
    border: 1px solid var(--hc-line);
    font-size: .8rem;
    font-weight: 750;
}

.hc-identity {
    border-radius: 24px;
    padding: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 22px;
}

.hc-logo-block {
    display: flex;
    gap: 14px;
    align-items: center;
}

.hc-logo,
.hc-mini-logo,
.hc-brand-logo,
.hc-upload-thumb,
.hc-device-icon,
.hc-stat-icon,
.hc-section-mark {
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    overflow: hidden;
    background: color-mix(in srgb, var(--hc-brand) 8%, var(--hc-paper, #FFFFFD));
    color: var(--hc-brand);
    border: 1px solid var(--hc-line);
}

.hc-logo {
    width: 72px;
    height: 72px;
    border-radius: 20px;
}

.hc-logo img,
.hc-mini-logo img,
.hc-brand-logo img,
.hc-upload-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.hc-identity-label,
.hc-section-kicker,
.hc-field label,
.hc-catalog-cell label {
    margin: 0;
    color: var(--hc-ink-faint);
    font-size: .72rem;
    line-height: 1.25;
    font-weight: 820;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.hc-identity-name {
    display: block;
    margin-top: 5px;
    color: var(--hc-ink);
    font-size: 1rem;
    line-height: 1.25;
}

.hc-identity-note {
    margin: 0;
    color: var(--hc-ink-soft);
    font-size: .88rem;
    line-height: 1.55;
}

.hc-token-note {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    padding: 12px;
    border-radius: 16px;
    color: var(--hc-brand-strong);
    background: var(--hc-accent-soft);
    border: 1px solid color-mix(in srgb, var(--hc-accent) 25%, var(--hc-line));
    font-size: .84rem;
    font-weight: 760;
}

.hc-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 16px;
    margin-top: 18px;
    align-items: start;
}

.hc-nav-card {
    position: sticky;
    top: 12px;
    z-index: 20;
    border-radius: 22px;
    padding: 12px;
    display: grid;
    grid-template-columns: minmax(220px, .32fr) minmax(0, 1fr);
    gap: 12px;
    align-items: center;
}

.hc-nav-head {
    display: flex;
    gap: 12px;
    align-items: center;
    min-width: 0;
    padding: 8px 10px;
    border: 1px solid var(--hc-line);
    border-radius: 17px;
    background: color-mix(in srgb, var(--hc-brand) 4%, var(--hc-paper, #FFFFFD));
}

.hc-mini-logo {
    width: 42px;
    height: 42px;
    border-radius: 13px;
}

.hc-nav-title {
    display: block;
    color: var(--hc-ink);
    font-size: .92rem;
    line-height: 1.2;
}

.hc-nav {
    display: flex;
    gap: 8px;
    min-width: 0;
    margin-top: 0;
    padding: 2px 2px 4px;
    overflow-x: auto;
    overscroll-behavior-x: contain;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--hc-brand) 22%, #d8d0c3) transparent;
}

.hc-nav-link {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    flex: 1 0 174px;
    min-height: 62px;
    padding: 10px;
    border-radius: 16px;
    color: var(--hc-ink-soft);
    text-decoration: none;
    border: 1px solid transparent;
    background: transparent;
    scroll-snap-align: start;
    transition: background .18s ease-out, color .18s ease-out, border-color .18s ease-out, transform .18s ease-out;
}

.hc-nav-link i {
    display: inline-grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--hc-brand) 5%, var(--hc-paper, #FFFFFD));
}

.hc-nav-link strong {
    display: block;
    color: inherit;
    font-size: .86rem;
    line-height: 1.15;
}

.hc-nav-link span {
    display: block;
    margin-top: 2px;
    color: var(--hc-ink-faint);
    font-size: .72rem;
    font-weight: 650;
}

.hc-nav-link:hover,
.hc-nav-link.is-active {
    color: var(--hc-brand-strong);
    background: var(--hc-paper, #FFFFFD);
    border-color: color-mix(in srgb, var(--hc-brand) 18%, var(--hc-line));
    box-shadow: 0 13px 28px -24px color-mix(in srgb, var(--hc-brand) 70%, transparent);
    transform: translateY(-1px);
}

.hc-nav-link.is-active i {
    color: var(--hc-brand-strong);
    background: var(--hc-accent-soft);
    border: 1px solid color-mix(in srgb, var(--hc-accent) 28%, var(--hc-line));
}

.hc-nav-foot {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 0;
    padding: 0 4px 2px;
    border-top: 0;
    color: var(--hc-ink-soft);
    font-size: .76rem;
    line-height: 1.45;
}

.hc-nav-foot span {
    display: flex;
    gap: 8px;
}

.hc-main {
    min-width: 0;
    display: grid;
    gap: 14px;
}

.hc-panel {
    border-radius: 24px;
    padding: 24px;
}

.hc-panel[data-hc-section] {
    display: none;
}

.hc-panel[data-hc-section].is-active {
    display: block;
    animation: hcScreenIn .2s ease-out;
}

.hc-page[data-active-section="hc-readonly"] .hc-save-dock,
.hc-page[data-active-section="hc-devices"] .hc-save-dock,
.hc-page[data-active-section="hc-readonly"] .hc-bottom-actions,
.hc-page[data-active-section="hc-devices"] .hc-bottom-actions {
    display: none;
}

@keyframes hcScreenIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hc-panel-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.hc-panel-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 6px 0 0;
    color: var(--hc-brand-strong);
    font-size: 1.2rem;
    line-height: 1.25;
    font-weight: 780;
}

.hc-section-mark {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    color: var(--hc-brand);
    background: var(--hc-accent-soft);
}

.hc-panel-copy {
    max-width: 72ch;
    margin: 9px 0 0;
    color: var(--hc-ink-soft);
    font-size: .9rem;
    line-height: 1.55;
}

.hc-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 7px 10px;
    border-radius: 11px;
    color: var(--hc-brand);
    background: color-mix(in srgb, var(--hc-brand) 6%, var(--hc-paper, #FFFFFD));
    border: 1px solid var(--hc-line);
    font-size: .76rem;
    font-weight: 820;
    white-space: nowrap;
}

.hc-readonly-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.hc-stat {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 12px;
    min-height: 118px;
    padding: 15px;
    border-radius: 18px;
    background: var(--hc-surface-muted);
    border: 1px solid var(--hc-line);
}

.hc-stat.is-featured {
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-brand) 9%, var(--hc-paper, #FFFFFD)), var(--hc-accent-soft));
}

.hc-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
}

.hc-stat-label {
    margin: 0;
    color: var(--hc-ink-faint);
    font-size: .72rem;
    font-weight: 820;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.hc-stat-value {
    margin: 5px 0 0;
    color: var(--hc-ink);
    font-size: 1.05rem;
    line-height: 1.25;
    font-weight: 780;
    overflow-wrap: anywhere;
}

.hc-stat-detail {
    margin: 6px 0 0;
    color: var(--hc-ink-soft);
    font-size: .78rem;
    line-height: 1.45;
}

.hc-save-dock {
    position: static;
    z-index: 8;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    border-radius: 18px;
    padding: 13px 14px;
}

.hc-save-copy {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--hc-ink-soft);
    font-size: .84rem;
    font-weight: 700;
}

.hc-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 9px;
}

.hc-btn,
.hc-link-btn,
.hc-add-btn,
.hc-device-revoke {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    border-radius: 13px;
    padding: 10px 14px;
    border: 1px solid var(--hc-line);
    font-size: .84rem;
    font-weight: 820;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s ease-out, background .18s ease-out, border-color .18s ease-out, color .18s ease-out;
}

.hc-btn:hover,
.hc-link-btn:hover,
.hc-add-btn:hover,
.hc-device-revoke:hover {
    transform: translateY(-1px);
}

.hc-btn:active,
.hc-link-btn:active,
.hc-add-btn:active,
.hc-device-revoke:active {
    transform: translateY(0);
}

.hc-btn-primary {
    color: #fffffb;
    background: var(--hc-brand-strong);
    border-color: var(--hc-brand-strong);
}

.hc-btn-primary:hover {
    background: color-mix(in srgb, var(--hc-brand-strong) 88%, var(--hc-accent));
}

.hc-btn-primary.is-confirming {
    background: color-mix(in srgb, var(--hc-accent) 46%, var(--hc-brand-strong));
    border-color: color-mix(in srgb, var(--hc-accent) 55%, var(--hc-brand-strong));
}

.hc-link-btn {
    color: var(--hc-brand);
    background: var(--hc-surface);
}

.hc-link-btn:hover,
.hc-add-btn:hover {
    color: var(--hc-brand-strong);
    background: var(--hc-accent-soft);
    border-color: color-mix(in srgb, var(--hc-accent) 28%, var(--hc-line));
}

.hc-group-stack {
    display: grid;
    gap: 20px;
}

.hc-setting-group,
.hc-catalog-box,
.hc-system-block,
.hc-notification-block {
    padding-top: 20px;
    border-top: 1px solid var(--hc-line);
}

.hc-setting-group:first-child,
.hc-catalog-box:first-child,
.hc-system-block:first-child,
.hc-notification-block:first-child {
    padding-top: 0;
    border-top: 0;
}

.hc-group-head,
.hc-catalog-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 14px;
}

.hc-catalog-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.hc-catalog-metric {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 7px 10px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent, var(--hc-brand)) 18%, var(--hc-line));
    border-radius: 12px;
    background: color-mix(in srgb, var(--hc-block-accent, var(--hc-brand)) 6%, var(--hc-paper, #FFFFFD));
    color: var(--hc-brand-strong);
    font-size: .78rem;
    font-weight: 780;
}

.hc-catalog-metric b {
    color: var(--hc-block-accent, var(--hc-brand));
    font-size: 1rem;
    font-variant-numeric: tabular-nums;
}

.hc-group-title,
.hc-catalog-title {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: var(--hc-ink);
    font-size: .98rem;
    line-height: 1.25;
    font-weight: 800;
}

.hc-group-title i,
.hc-catalog-title i {
    color: var(--hc-brand);
}

.hc-group-hint,
.hc-field-hint {
    margin: 5px 0 0;
    color: var(--hc-ink-soft);
    font-size: .78rem;
    line-height: 1.45;
}

.hc-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 13px;
}

.hc-field {
    min-width: 0;
}

.hc-field.is-wide {
    grid-column: 1 / -1;
}

.hc-field label,
.hc-catalog-cell label {
    display: block;
    margin-bottom: 7px;
}

.hc-page .form-input {
    width: 100%;
    min-height: 42px;
    border-radius: 13px;
    border: 1px solid var(--hc-line);
    background: var(--hc-paper, #FFFFFD);
    color: var(--hc-ink);
    font-size: .9rem;
    line-height: 1.4;
    padding: 10px 12px;
    box-shadow: 0 1px 0 color-mix(in srgb, var(--hc-brand) 5%, transparent);
    transition: border-color .16s ease-out, box-shadow .16s ease-out, background .16s ease-out;
}

.hc-page textarea.form-input {
    resize: vertical;
    min-height: 96px;
}

.hc-page .form-input:focus,
.hc-page input[type="color"]:focus,
.hc-btn:focus-visible,
.hc-link-btn:focus-visible,
.hc-add-btn:focus-visible,
.hc-device-revoke:focus-visible,
.hc-nav-link:focus-visible {
    outline: 3px solid var(--hc-focus);
    outline-offset: 2px;
}

.hc-page .form-input:focus {
    border-color: color-mix(in srgb, var(--hc-accent) 48%, var(--hc-line));
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--hc-accent) 16%, transparent);
}

.hc-uppercase {
    text-transform: uppercase;
}

.hc-switch {
    position: relative;
    display: grid;
    grid-template-columns: 26px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    min-height: 58px;
    margin: 0;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid var(--hc-line);
    background: var(--hc-paper, #FFFFFD);
    cursor: pointer;
    isolation: isolate;
    text-transform: none;
    letter-spacing: 0;
    transition: background .18s ease-out, border-color .18s ease-out, box-shadow .18s ease-out;
}

.hc-switch:has(input:checked) {
    border-color: color-mix(in srgb, var(--hc-success) 38%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-success) 7%, var(--hc-paper, #FFFFFD));
    box-shadow: 0 12px 24px -25px color-mix(in srgb, var(--hc-success) 70%, transparent);
}

.hc-field label.hc-switch,
.hc-catalog-cell label.hc-switch {
    margin-bottom: 0;
    color: var(--hc-ink);
    font-size: inherit;
    font-weight: inherit;
    letter-spacing: 0;
    text-transform: none;
}

.hc-switch input {
    position: static;
    grid-column: 1;
    grid-row: 1;
    z-index: 1;
    width: 20px;
    height: 20px;
    margin: 0;
    opacity: 1;
    accent-color: var(--hc-success);
    cursor: pointer;
}

.hc-switch:hover {
    border-color: color-mix(in srgb, var(--hc-brand) 26%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-brand) 6%, var(--hc-paper, #FFFFFD));
    box-shadow: 0 14px 28px -26px color-mix(in srgb, var(--hc-brand) 70%, transparent);
}

.hc-switch-ui {
    display: none;
}

.hc-switch input:focus-visible {
    outline: 3px solid var(--hc-focus);
    outline-offset: 2px;
}

.hc-switch-text {
    min-width: 0;
    position: relative;
    z-index: 1;
    grid-column: 2;
    grid-row: 1;
}

.hc-switch-text strong {
    display: block;
    color: var(--hc-ink);
    font-size: .9rem;
    line-height: 1.3;
    font-weight: 820;
    letter-spacing: 0;
    text-transform: none;
}

.hc-switch-text small {
    display: block;
    margin-top: 5px;
    color: var(--hc-ink-soft);
    font-size: .8rem;
    line-height: 1.45;
    font-weight: 650;
    letter-spacing: 0;
    text-transform: none;
}

.hc-catalog-stack {
    display: grid;
    gap: 22px;
}

.hc-add-btn {
    min-height: 36px;
    padding: 8px 11px;
    color: var(--hc-brand);
    background: var(--hc-paper, #FFFFFD);
}

.hc-catalog-list {
    display: grid;
    gap: 9px;
}

.hc-catalog-row {
    display: grid;
    gap: 10px;
    align-items: end;
    padding: 12px;
    border: 1px solid var(--hc-line);
    border-radius: 17px;
    background: color-mix(in srgb, var(--hc-brand) 3%, var(--hc-paper, #FFFFFD));
}

.hc-catalog-row.is-type {
    grid-template-columns: minmax(120px, 1fr) minmax(140px, 1.15fr) minmax(92px, .7fr) minmax(120px, .8fr) minmax(160px, 1.35fr) minmax(92px, .6fr);
}

.hc-catalog-row.is-floor,
.hc-catalog-row.is-amenity,
.hc-catalog-row.is-simple {
    grid-template-columns: minmax(110px, .8fr) minmax(180px, 1.5fr) minmax(92px, .6fr);
}

.hc-catalog-row.is-parking {
    grid-template-columns: minmax(110px, .72fr) minmax(180px, 1.28fr) minmax(92px, .52fr) minmax(92px, .55fr);
}

.hc-catalog-row.is-unit {
    grid-template-columns: minmax(110px, .9fr) minmax(180px, 1.4fr) minmax(92px, .7fr) minmax(92px, .6fr);
}

.hc-catalog-row.is-owner {
    grid-template-columns: minmax(120px, .78fr) minmax(180px, 1.25fr) minmax(110px, .54fr) minmax(118px, 148px);
}

.hc-catalog-row.is-owner-rule {
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr);
}

.hc-catalog-row.is-owner-room {
    grid-template-columns: minmax(104px, .62fr) minmax(120px, .78fr) minmax(160px, 1fr) minmax(180px, .95fr);
}

.hc-catalog-cell {
    min-width: 0;
}

.hc-owner-preview {
    display: grid;
    gap: 14px;
    margin-bottom: 20px;
}

.hc-owner-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.hc-owner-summary-card,
.hc-owner-flow-card {
    border: 1px solid color-mix(in srgb, var(--hc-section-accent) 18%, var(--hc-line));
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--hc-section-accent) 10%, transparent), transparent 9rem),
        linear-gradient(135deg, color-mix(in srgb, var(--hc-section-accent) 6%, color-mix(in srgb, var(--hc-paper, #FFF) 94%, transparent)), color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent));
    box-shadow: 0 12px 28px -30px color-mix(in srgb, var(--hc-section-accent) 44%, transparent);
}

.hc-owner-summary-card {
    min-height: 104px;
    padding: 14px;
    border-radius: 14px;
}

.hc-owner-summary-card span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: color-mix(in srgb, var(--hc-section-accent) 72%, var(--hc-ink-mix, #334155));
    font-size: .72rem;
    font-weight: 780;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.hc-owner-summary-card strong {
    display: block;
    margin-top: 8px;
    color: var(--hc-brand-strong);
    font-size: 1.08rem;
    line-height: 1.2;
    font-weight: 760;
    overflow-wrap: anywhere;
}

.hc-owner-summary-card small {
    display: block;
    margin-top: 6px;
    color: var(--hc-ink-soft);
    font-size: .76rem;
    line-height: 1.42;
}

.hc-owner-flow-card {
    padding: 16px;
    border-radius: 16px;
}

.hc-owner-flow-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.hc-owner-flow-head h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: var(--hc-brand-strong);
    font-size: .95rem;
    font-weight: 700;
}

.hc-owner-flow-head p {
    margin: 5px 0 0;
    color: var(--hc-ink-soft);
    font-size: .78rem;
    line-height: 1.45;
}

.hc-owner-flow-list {
    display: grid;
    gap: 9px;
}

.hc-owner-flow-row {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    min-height: 64px;
    padding: 11px;
    border: 1px solid color-mix(in srgb, var(--hc-section-accent) 13%, var(--hc-line));
    border-radius: 14px;
    background: color-mix(in srgb, var(--hc-paper, #FFF) 78%, transparent);
}

.hc-owner-flow-icon {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: color-mix(in srgb, var(--hc-section-accent) 78%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-section-accent) 12%, var(--hc-paper, #FFF));
    border: 1px solid color-mix(in srgb, var(--hc-section-accent) 20%, var(--hc-line));
}

.hc-owner-flow-name {
    margin: 0;
    color: var(--hc-brand-strong);
    font-size: .9rem;
    line-height: 1.25;
    font-weight: 720;
    overflow-wrap: anywhere;
}

.hc-owner-flow-note {
    margin: 3px 0 0;
    color: var(--hc-ink-soft);
    font-size: .76rem;
    line-height: 1.42;
}

.hc-owner-flow-pill {
    min-width: 76px;
    padding: 7px 9px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-section-accent) 74%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-section-accent) 10%, var(--hc-paper, #FFF));
    border: 1px solid color-mix(in srgb, var(--hc-section-accent) 18%, var(--hc-line));
    text-align: center;
    font-size: .76rem;
    font-weight: 800;
}

.hc-owner-flow-empty {
    margin: 0;
    padding: 12px;
    border-radius: 13px;
    color: var(--hc-ink-soft);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 76%, transparent);
    border: 1px dashed color-mix(in srgb, var(--hc-section-accent) 24%, var(--hc-line));
    font-size: .82rem;
    line-height: 1.45;
}

@media (max-width: 1240px) {
    .hc-owner-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 860px) {
    .hc-owner-summary-grid,
    .hc-owner-flow-row {
        grid-template-columns: 1fr;
    }

    .hc-owner-flow-pill {
        width: fit-content;
    }
}

.hc-brand-grid {
    display: grid;
    grid-template-columns: minmax(240px, 340px) minmax(0, 1fr);
    gap: 22px;
    align-items: start;
}

.hc-brand-preview {
    position: sticky;
    top: 90px;
    display: grid;
    gap: 18px;
    padding: 20px;
    border-radius: 20px;
    border: 1px solid var(--hc-line);
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-brand) 11%, var(--hc-paper, #FFFFFD)), var(--hc-paper, #FFFFFD) 58%),
        var(--hc-surface);
}

.hc-brand-logo {
    width: 94px;
    height: 94px;
    border-radius: 24px;
    margin-bottom: 14px;
}

.hc-brand-name {
    margin: 0;
    color: var(--hc-brand-strong);
    font-size: 1.1rem;
    font-weight: 820;
    line-height: 1.25;
}

.hc-brand-note {
    margin: 8px 0 0;
    color: var(--hc-ink-soft);
    font-size: .84rem;
    line-height: 1.55;
}

.hc-color-input {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}

.hc-color-input input[type="color"] {
    width: 48px;
    height: 42px;
    padding: 4px;
    border-radius: 13px;
    border: 1px solid var(--hc-line);
    background: var(--hc-paper, #FFFFFD);
}

.hc-color-code {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    padding: 9px 11px;
    border-radius: 13px;
    color: var(--hc-ink);
    background: var(--hc-surface-muted);
    border: 1px solid var(--hc-line);
    font-size: .84rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.hc-upload-row {
    display: grid;
    grid-template-columns: 54px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}

.hc-upload-thumb {
    width: 54px;
    height: 54px;
    border-radius: 16px;
}

.hc-system-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.hc-backup-box {
    padding: 14px;
    border-radius: 18px;
    border: 1px solid var(--hc-line);
    background: var(--hc-surface-muted);
}

.hc-backup-fields {
    margin-top: 13px;
}

.hc-hidden-compat {
    display: none;
}

.hc-bottom-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px;
    border-radius: 20px;
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    box-shadow: var(--hc-shadow);
}

.hc-bottom-note {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--hc-ink-soft);
    font-size: .86rem;
    font-weight: 700;
}

.hc-device-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 108px;
    border: 1px dashed var(--hc-line-strong);
    border-radius: 18px;
    color: var(--hc-ink-soft);
    background: var(--hc-surface-muted);
    text-align: center;
    padding: 18px;
    font-size: .9rem;
}

.hc-device-list {
    display: grid;
    gap: 10px;
}

.hc-device-row {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr) auto;
    gap: 13px;
    align-items: center;
    padding: 13px;
    border: 1px solid var(--hc-line);
    border-radius: 18px;
    background: var(--hc-surface-muted);
}

.hc-device-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
}

.hc-device-title {
    display: block;
    color: var(--hc-ink);
    font-size: .92rem;
    line-height: 1.3;
}

.hc-device-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-top: 6px;
    color: var(--hc-ink-soft);
    font-size: .76rem;
    line-height: 1.35;
}

.hc-device-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.hc-device-status.is-active {
    color: var(--hc-success);
    font-weight: 820;
}

.hc-device-status.is-inactive {
    color: var(--hc-danger);
    font-weight: 820;
}

.hc-device-form {
    margin: 0;
}

.hc-device-revoke {
    color: var(--hc-danger);
    background: color-mix(in srgb, var(--hc-danger) 7%, var(--hc-paper, #FFFFFD));
    border-color: color-mix(in srgb, var(--hc-danger) 30%, var(--hc-line));
}

html {
    scroll-behavior: smooth;
}

@media (max-width: 1240px) {
    .hc-readonly-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-parking,
    .hc-catalog-row.is-unit,
    .hc-catalog-row.is-owner,
    .hc-catalog-row.is-owner-rule,
    .hc-catalog-row.is-owner-room {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 1020px) {
    .hc-top,
    .hc-brand-grid,
    .hc-system-grid {
        grid-template-columns: 1fr;
    }

    .hc-brand-preview {
        position: static;
    }

    .hc-nav-card {
        grid-template-columns: 1fr;
    }

    .hc-nav-link {
        flex-basis: 210px;
    }
}

@media (max-width: 720px) {
    .hc-shell {
        width: min(100% - 20px, 1480px);
        padding-top: 14px;
    }

    .hc-top-main,
    .hc-identity,
    .hc-panel {
        border-radius: 18px;
        padding: 18px;
    }

    .hc-title {
        font-size: 1.85rem;
    }

    .hc-readonly-grid,
    .hc-field-grid,
    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-parking,
    .hc-catalog-row.is-unit,
    .hc-catalog-row.is-owner,
    .hc-catalog-row.is-owner-rule,
    .hc-catalog-row.is-owner-room {
        grid-template-columns: 1fr;
    }

    .hc-nav-card {
        top: 8px;
        border-radius: 18px;
        padding: 10px;
    }

    .hc-nav-head {
        display: none;
    }

    .hc-nav {
        gap: 7px;
        padding-bottom: 5px;
    }

    .hc-nav-link {
        flex: 0 0 168px;
        min-height: 58px;
    }

    .hc-nav-foot {
        display: none;
    }

    .hc-switch {
        grid-template-columns: 26px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
    }

    .hc-switch-text {
        grid-column: 2;
        grid-row: 1;
    }

    .hc-panel-header,
    .hc-group-head,
    .hc-catalog-head,
    .hc-save-dock,
    .hc-bottom-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .hc-actions {
        justify-content: stretch;
    }

    .hc-actions > *,
    .hc-bottom-actions .hc-actions > * {
        flex: 1 1 100%;
    }

    .hc-device-row {
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .hc-device-actions {
        grid-column: 1 / -1;
    }
}

/* Vista inspirada en el index de notificaciones: ligera, horizontal y por hotel. */
.hc-page {
    --hc-brand: var(--brand-primary, #1f3f46);
    --hc-brand-strong: color-mix(in srgb, var(--hc-brand) 58%, #465467);
    --hc-accent: var(--brand-accent, #b58a3c);
    --hc-accent-soft: color-mix(in srgb, var(--hc-accent) 7%, var(--hc-paper, #FFF));
    --hc-bg: #fcfbf8;
    --hc-bg-deep: color-mix(in srgb, var(--hc-accent) 4%, #f4f1ea);
    --hc-surface: color-mix(in srgb, var(--hc-paper, #FFF) 86%, transparent);
    --hc-surface-muted: color-mix(in srgb, var(--hc-accent) 3%, color-mix(in srgb, var(--hc-paper, #FFF) 82%, transparent));
    --hc-brand-wash: color-mix(in srgb, var(--hc-brand) 6%, var(--hc-paper, #FFF));
    --hc-accent-wash: color-mix(in srgb, var(--hc-accent) 10%, var(--hc-paper, #FFF));
    --hc-blush-wash: color-mix(in srgb, var(--hc-accent) 5%, color-mix(in srgb, var(--hc-brand) 3%, var(--hc-paper, #FFF)));
    --hc-ink: #344054;
    --hc-ink-soft: #526176;
    --hc-ink-faint: #748094;
    --hc-line: color-mix(in srgb, var(--hc-brand) 9%, #e9e2d7);
    --hc-line-strong: color-mix(in srgb, var(--hc-brand) 18%, #d8d0c3);
    --hc-danger: #b42318;
    --hc-success: #15803d;
    --hc-tone-blue: #3f7891;
    --hc-tone-sage: #2f8a70;
    --hc-tone-amber: #b98a35;
    --hc-tone-coral: #b66b5f;
    --hc-tone-indigo: #6e6aa9;
    --hc-tone-olive: #6f8547;
    --hc-tone-slate: #58728f;
    --hc-section-accent: var(--hc-accent);
    --hc-section-wash: color-mix(in srgb, var(--hc-section-accent) 7%, var(--hc-paper, #FFF));
    --hc-focus: color-mix(in srgb, var(--hc-accent) 22%, transparent);
    --hc-shadow: 0 16px 38px -34px color-mix(in srgb, var(--hc-brand) 24%, transparent);
    --hc-page-gutter: clamp(34px, 3.8vw, 64px);
    background:
        radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--hc-accent) 15%, transparent), transparent 26rem),
        radial-gradient(circle at 34% 8%, color-mix(in srgb, var(--hc-tone-sage) 8%, transparent), transparent 24rem),
        radial-gradient(circle at 74% 2%, color-mix(in srgb, var(--hc-tone-indigo) 7%, transparent), transparent 25rem),
        radial-gradient(circle at 96% 8%, color-mix(in srgb, var(--hc-brand) 10%, transparent), transparent 32rem),
        linear-gradient(180deg, var(--hc-bg) 0%, var(--hc-bg-deep) 100%);
    color: var(--hc-ink);
    font-family: var(--hc-sans);
    padding-inline: var(--hc-page-gutter);
    overflow-x: hidden;
}

.hc-shell {
    width: min(1440px, 100%);
    margin-inline: auto;
    padding: 30px 0 58px;
}

.hc-top {
    grid-template-columns: minmax(0, 1fr) minmax(240px, 315px);
    gap: 20px;
    align-items: end;
    margin-bottom: 22px;
}

.hc-top-main {
    min-height: auto;
    padding: 0;
    border: 0;
    background: transparent;
    box-shadow: none;
    overflow: visible;
}

.hc-top-main::after {
    display: none;
}

.hc-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(960px, 100%);
}

.hc-hero-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex: 0 0 48px;
    border-radius: 15px;
    color: #fff;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--hc-accent), var(--hc-brand) 54%, var(--hc-tone-sage));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--hc-brand) 72%, transparent);
}

.hc-title-copy {
    min-width: 0;
    padding-top: 1px;
}

.hc-eyebrow {
    display: block;
    min-height: 0;
    margin: 0 0 2px;
    padding: 0;
    border: 0;
    border-radius: 0;
    color: var(--hc-ink-faint);
    background: transparent;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.hc-title {
    max-width: 920px;
    margin: 0;
    color: var(--hc-brand-strong);
    font-family: var(--hc-serif);
    font-size: clamp(2.35rem, 4vw, 3.35rem);
    line-height: .98;
    font-weight: 700;
    letter-spacing: 0;
    text-wrap: balance;
}

.hc-subtitle {
    max-width: 920px;
    margin-top: 9px;
    color: var(--hc-ink-soft);
    font-size: .94rem;
    line-height: 1.55;
    font-weight: 600;
}

.hc-top-meta {
    gap: 10px;
    margin-top: 14px;
}

.hc-chip {
    min-height: 32px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-brand) 58%, #667085);
    background: color-mix(in srgb, var(--hc-brand) 4%, color-mix(in srgb, var(--hc-paper, #FFF) 82%, transparent));
    border-color: var(--hc-line);
    font-size: .78rem;
    font-weight: 560;
}

.hc-identity {
    min-height: 0;
    border-radius: 16px;
    padding: 18px;
    gap: 16px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-paper, #FFF) 90%, transparent), var(--hc-accent-wash) 58%, color-mix(in srgb, var(--hc-brand) 5%, var(--hc-paper, #FFF)));
    border-color: var(--hc-line);
    box-shadow: 0 18px 42px -38px color-mix(in srgb, var(--hc-brand) 34%, transparent);
}

.hc-logo {
    width: 54px;
    height: 54px;
    border-radius: 15px;
}

.hc-identity-label,
.hc-section-kicker,
.hc-field label,
.hc-catalog-cell label {
    color: var(--hc-ink-faint);
    font-size: .72rem;
    font-weight: 620;
    letter-spacing: .04em;
}

.hc-identity-name {
    color: var(--hc-brand-strong);
    font-size: .98rem;
    font-weight: 620;
}

.hc-identity-note {
    color: var(--hc-ink-soft);
    font-size: .84rem;
    font-weight: 430;
}

.hc-token-note {
    min-height: 34px;
    padding: 9px 10px;
    border-radius: 12px;
    color: color-mix(in srgb, var(--hc-brand) 58%, #667085);
    background: linear-gradient(135deg, var(--hc-accent-wash), color-mix(in srgb, var(--hc-brand) 4%, var(--hc-paper, #FFF)));
    border-color: color-mix(in srgb, var(--hc-accent) 26%, var(--hc-line));
    font-size: .8rem;
    font-weight: 620;
}

.hc-layout {
    gap: 20px;
    margin-top: 0;
}

.hc-nav-card {
    top: 10px;
    display: block;
    padding: 12px;
    border-radius: 16px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-paper, #FFF) 90%, transparent), color-mix(in srgb, var(--hc-accent) 4%, color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent)));
    border-color: var(--hc-line);
    box-shadow: 0 16px 38px -34px color-mix(in srgb, var(--hc-brand) 24%, transparent);
    backdrop-filter: blur(12px);
}

.hc-nav-head,
.hc-nav-foot {
    display: none;
}

.hc-nav {
    gap: 10px;
    padding: 2px 2px 4px;
    scrollbar-color: color-mix(in srgb, var(--hc-accent) 28%, #d8d0c3) transparent;
}

.hc-nav-link {
    --hc-nav-accent: var(--hc-accent);
    flex: 0 0 auto;
    min-height: 50px;
    grid-template-columns: 30px minmax(0, 1fr);
    gap: 9px;
    padding: 9px 12px;
    border-radius: 13px;
    color: var(--hc-ink-soft);
}

.hc-nav-link[href="#hc-readonly"] { --hc-nav-accent: var(--hc-tone-blue); }
.hc-nav-link[href="#hc-settings"] { --hc-nav-accent: var(--hc-tone-sage); }
.hc-nav-link[href="#hc-notifications"] { --hc-nav-accent: var(--hc-tone-amber); }
.hc-nav-link[href="#hc-guest-fields"] { --hc-nav-accent: var(--hc-tone-blue); }
.hc-nav-link[href="#hc-rooms"] { --hc-nav-accent: var(--hc-tone-indigo); }
.hc-nav-link[href="#hc-owners"] { --hc-nav-accent: var(--hc-tone-sage); }
.hc-nav-link[href="#hc-catalogs"] { --hc-nav-accent: var(--hc-tone-olive); }
.hc-nav-link[href="#hc-brand"] { --hc-nav-accent: var(--hc-accent); }
.hc-nav-link[href="#hc-footer-nav"] { --hc-nav-accent: var(--hc-tone-indigo); }
.hc-nav-link[href="#hc-system"] { --hc-nav-accent: var(--hc-tone-coral); }
.hc-nav-link[href="#hc-devices"] { --hc-nav-accent: var(--hc-tone-slate); }

.hc-nav-link i {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    color: color-mix(in srgb, var(--hc-nav-accent) 68%, #667085);
    background: color-mix(in srgb, var(--hc-nav-accent) 10%, var(--hc-paper, #FFF));
}

.hc-nav-link strong {
    font-size: .82rem;
    line-height: 1.14;
    font-weight: 620;
}

.hc-nav-link span {
    margin-top: 1px;
    color: var(--hc-ink-faint);
    font-size: .67rem;
    font-weight: 500;
}

.hc-nav-link:hover,
.hc-nav-link.is-active {
    color: var(--hc-brand-strong);
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-nav-accent) 13%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-brand) 4%, var(--hc-paper, #FFF)));
    border-color: color-mix(in srgb, var(--hc-nav-accent) 34%, var(--hc-line));
    box-shadow: none;
}

.hc-nav-link.is-active i {
    color: color-mix(in srgb, var(--hc-nav-accent) 78%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-nav-accent) 18%, var(--hc-paper, #FFF));
    border-color: color-mix(in srgb, var(--hc-nav-accent) 28%, var(--hc-line));
}

.hc-panel,
.hc-save-dock,
.hc-bottom-actions {
    border-radius: 16px;
    border-color: var(--hc-line);
    background: linear-gradient(180deg, color-mix(in srgb, var(--hc-paper, #FFF) 92%, transparent), color-mix(in srgb, var(--hc-accent) 2%, color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent)));
    box-shadow: var(--hc-shadow);
}

#configForm {
    display: grid;
    gap: 26px;
    align-items: start;
}

.hc-panel {
    position: relative;
    padding: clamp(24px, 2.2vw, 32px);
    overflow: hidden;
}

.hc-panel[data-hc-section] {
    border-top: 0;
    border-color: color-mix(in srgb, var(--hc-section-accent) 18%, var(--hc-line));
    background:
        radial-gradient(circle at 98% 0%, color-mix(in srgb, var(--hc-section-accent) 13%, transparent), transparent 13rem),
        linear-gradient(180deg, color-mix(in srgb, var(--hc-paper, #FFF) 94%, transparent), var(--hc-section-wash));
}

.hc-panel[data-hc-section]::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--hc-section-accent) 70%, var(--hc-line)), color-mix(in srgb, var(--hc-section-accent) 12%, transparent));
}

#hc-readonly { --hc-section-accent: var(--hc-tone-blue); --hc-section-wash: color-mix(in srgb, var(--hc-tone-blue) 6%, var(--hc-paper, #FFF)); }
#hc-settings { --hc-section-accent: var(--hc-tone-sage); --hc-section-wash: color-mix(in srgb, var(--hc-tone-sage) 6%, var(--hc-paper, #FFF)); }
#hc-notifications { --hc-section-accent: var(--hc-tone-amber); --hc-section-wash: color-mix(in srgb, var(--hc-tone-amber) 7%, var(--hc-paper, #FFF)); }
#hc-rooms { --hc-section-accent: var(--hc-tone-indigo); --hc-section-wash: color-mix(in srgb, var(--hc-tone-indigo) 6%, var(--hc-paper, #FFF)); }
#hc-owners { --hc-section-accent: var(--hc-tone-sage); --hc-section-wash: color-mix(in srgb, var(--hc-tone-sage) 6%, var(--hc-paper, #FFF)); }
#hc-guest-fields { --hc-section-accent: var(--hc-tone-blue); --hc-section-wash: color-mix(in srgb, var(--hc-tone-blue) 6%, var(--hc-paper, #FFF)); }
#hc-catalogs { --hc-section-accent: var(--hc-tone-olive); --hc-section-wash: color-mix(in srgb, var(--hc-tone-olive) 6%, var(--hc-paper, #FFF)); }
#hc-brand { --hc-section-accent: var(--hc-accent); --hc-section-wash: color-mix(in srgb, var(--hc-accent) 7%, var(--hc-paper, #FFF)); }
#hc-footer-nav { --hc-section-accent: var(--hc-tone-indigo); --hc-section-wash: color-mix(in srgb, var(--hc-tone-indigo) 6%, var(--hc-paper, #FFF)); }
#hc-system { --hc-section-accent: var(--hc-tone-coral); --hc-section-wash: color-mix(in srgb, var(--hc-tone-coral) 6%, var(--hc-paper, #FFF)); }
#hc-devices { --hc-section-accent: var(--hc-tone-slate); --hc-section-wash: color-mix(in srgb, var(--hc-tone-slate) 6%, var(--hc-paper, #FFF)); }

.hc-panel-header {
    gap: 18px;
    margin-bottom: 28px;
}

.hc-panel-title {
    gap: 10px;
    margin-top: 5px;
    color: var(--hc-brand-strong);
    font-size: 1.05rem;
    line-height: 1.24;
    font-weight: 620;
}

.hc-section-mark {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    color: color-mix(in srgb, var(--hc-section-accent) 78%, var(--hc-ink-mix, #334155));
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-section-accent) 16%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-brand) 4%, var(--hc-paper, #FFF)));
    border-color: color-mix(in srgb, var(--hc-section-accent) 24%, var(--hc-line));
}

.hc-panel[data-hc-section] .hc-section-kicker {
    color: color-mix(in srgb, var(--hc-section-accent) 62%, var(--hc-ink-faint));
}

.hc-panel-copy {
    max-width: 68ch;
    color: var(--hc-ink-soft);
    font-size: .86rem;
    font-weight: 430;
}

.hc-badge {
    min-height: 30px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-section-accent) 66%, #667085);
    background: color-mix(in srgb, var(--hc-section-accent) 9%, var(--hc-paper, #FFF));
    border-color: color-mix(in srgb, var(--hc-section-accent) 20%, var(--hc-line));
    font-size: .74rem;
    font-weight: 620;
}

.hc-readonly-grid {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}

.hc-stat {
    --hc-card-accent: var(--hc-tone-blue);
    min-height: 108px;
    padding: 16px;
    border-radius: 14px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-card-accent) 10%, color-mix(in srgb, var(--hc-paper, #FFF) 94%, transparent)), color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent));
    border-color: color-mix(in srgb, var(--hc-card-accent) 18%, var(--hc-line));
    box-shadow: 0 12px 26px -24px color-mix(in srgb, var(--hc-card-accent) 44%, transparent);
}

.hc-stat.is-featured {
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-card-accent) 13%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-accent) 6%, var(--hc-paper, #FFF)));
}

.hc-readonly-grid .hc-stat:nth-child(6n+1) { --hc-card-accent: var(--hc-tone-blue); }
.hc-readonly-grid .hc-stat:nth-child(6n+2) { --hc-card-accent: var(--hc-tone-indigo); }
.hc-readonly-grid .hc-stat:nth-child(6n+3) { --hc-card-accent: var(--hc-tone-amber); }
.hc-readonly-grid .hc-stat:nth-child(6n+4) { --hc-card-accent: var(--hc-tone-sage); }
.hc-readonly-grid .hc-stat:nth-child(6n+5) { --hc-card-accent: var(--hc-tone-coral); }
.hc-readonly-grid .hc-stat:nth-child(6n+6) { --hc-card-accent: var(--hc-tone-olive); }

.hc-stat-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    color: color-mix(in srgb, var(--hc-card-accent) 78%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-card-accent) 14%, var(--hc-paper, #FFF));
    border-color: color-mix(in srgb, var(--hc-card-accent) 22%, var(--hc-line));
}

.hc-stat-label {
    color: var(--hc-ink-faint);
    font-weight: 620;
}

.hc-stat-value {
    color: var(--hc-brand-strong);
    font-size: 1rem;
    font-weight: 560;
}

.hc-stat-detail {
    color: var(--hc-ink-soft);
    font-weight: 430;
}

.hc-save-dock {
    position: relative;
    min-height: 74px;
    padding: 16px 18px;
    border-left: 0;
    border-bottom: 3px solid color-mix(in srgb, var(--hc-accent) 38%, var(--hc-line));
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-paper, #FFF) 94%, transparent), color-mix(in srgb, var(--hc-accent) 4%, color-mix(in srgb, var(--hc-paper, #FFF) 90%, transparent)));
}

.hc-save-copy,
.hc-bottom-note {
    color: var(--hc-ink-soft);
    font-size: .84rem;
    font-weight: 520;
}

.hc-btn,
.hc-link-btn,
.hc-add-btn,
.hc-device-revoke {
    min-height: 40px;
    border-radius: 12px;
    font-size: .84rem;
    font-weight: 620;
}

.hc-btn-primary {
    color: #fff;
    background: color-mix(in srgb, var(--hc-brand) 78%, var(--hc-accent));
    border-color: color-mix(in srgb, var(--hc-brand) 78%, var(--hc-accent));
}

.hc-btn-primary:hover {
    background: color-mix(in srgb, var(--hc-brand) 68%, var(--hc-accent));
}

.hc-link-btn,
.hc-add-btn {
    color: color-mix(in srgb, var(--hc-brand) 62%, #667085);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 86%, transparent);
}

.hc-setting-group,
.hc-catalog-box,
.hc-system-block,
.hc-notification-block {
    --hc-block-accent: var(--hc-section-accent);
    padding: 28px 22px 22px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent) 15%, var(--hc-line));
    border-left: 4px solid color-mix(in srgb, var(--hc-block-accent) 58%, var(--hc-line));
    border-radius: 16px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--hc-block-accent) 10%, transparent), transparent 10rem),
        linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent) 6%, color-mix(in srgb, var(--hc-paper, #FFF) 92%, transparent)), color-mix(in srgb, var(--hc-paper, #FFF) 86%, transparent));
    box-shadow: 0 12px 28px -28px color-mix(in srgb, var(--hc-block-accent) 44%, transparent);
}

.hc-setting-group:first-child,
.hc-catalog-box:first-child,
.hc-system-block:first-child,
.hc-notification-block:first-child {
    padding-top: 28px;
    border-top: 1px solid color-mix(in srgb, var(--hc-block-accent) 15%, var(--hc-line));
}

#hc-settings .hc-setting-group:nth-child(6n+1) { --hc-block-accent: var(--hc-tone-sage); }
#hc-settings .hc-setting-group:nth-child(6n+2) { --hc-block-accent: var(--hc-tone-blue); }
#hc-settings .hc-setting-group:nth-child(6n+3) { --hc-block-accent: var(--hc-tone-amber); }
#hc-settings .hc-setting-group:nth-child(6n+4) { --hc-block-accent: var(--hc-tone-indigo); }
#hc-settings .hc-setting-group:nth-child(6n+5) { --hc-block-accent: var(--hc-tone-coral); }
#hc-settings .hc-setting-group:nth-child(6n+6) { --hc-block-accent: var(--hc-tone-olive); }
#hc-notifications .hc-notification-block:nth-child(3n+1) { --hc-block-accent: var(--hc-tone-amber); }
#hc-notifications .hc-notification-block:nth-child(3n+2) { --hc-block-accent: var(--hc-tone-blue); }
#hc-notifications .hc-notification-block:nth-child(3n+3) { --hc-block-accent: var(--hc-tone-coral); }
#hc-guest-fields .hc-policy-box:nth-child(2n+1) { --hc-block-accent: var(--hc-tone-blue); }
#hc-guest-fields .hc-policy-box:nth-child(2n+2) { --hc-block-accent: var(--hc-tone-sage); }
#hc-rooms .hc-catalog-box:nth-child(3n+1) { --hc-block-accent: var(--hc-tone-indigo); }
#hc-rooms .hc-catalog-box:nth-child(3n+2) { --hc-block-accent: var(--hc-tone-sage); }
#hc-rooms .hc-catalog-box:nth-child(3n+3) { --hc-block-accent: var(--hc-tone-amber); }
#hc-owners .hc-catalog-box:nth-child(3n+1) { --hc-block-accent: var(--hc-tone-sage); }
#hc-owners .hc-catalog-box:nth-child(3n+2) { --hc-block-accent: var(--hc-tone-indigo); }
#hc-owners .hc-catalog-box:nth-child(3n+3) { --hc-block-accent: var(--hc-tone-amber); }
#hc-catalogs .hc-catalog-box:nth-child(3n+1) { --hc-block-accent: var(--hc-tone-olive); }
#hc-catalogs .hc-catalog-box:nth-child(3n+2) { --hc-block-accent: var(--hc-tone-blue); }
#hc-catalogs .hc-catalog-box:nth-child(3n+3) { --hc-block-accent: var(--hc-tone-indigo); }
#hc-system .hc-system-block:nth-child(2n+1) { --hc-block-accent: var(--hc-tone-coral); }
#hc-system .hc-system-block:nth-child(2n+2) { --hc-block-accent: var(--hc-tone-slate); }

.hc-group-title,
.hc-catalog-title {
    color: var(--hc-brand-strong);
    font-size: .96rem;
    font-weight: 620;
}

.hc-group-hint,
.hc-field-hint {
    color: var(--hc-ink-soft);
    font-weight: 430;
}

.hc-group-title i,
.hc-catalog-title i {
    color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 76%, var(--hc-ink-mix, #334155));
}

.hc-page .form-input,
.hc-color-code,
.hc-color-input input[type="color"] {
    border-radius: 12px;
    border-color: color-mix(in srgb, var(--hc-accent) 18%, #ddd5c8);
    background: var(--hc-paper, #FFF);
    color: var(--hc-ink);
    font-size: .88rem;
    font-weight: 430;
    box-shadow: none;
}

.hc-page .form-input:focus,
.hc-color-input input[type="color"]:focus {
    border-color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 42%, var(--hc-line));
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 13%, transparent);
}

.hc-field-grid {
    gap: 18px 20px;
}

.hc-system-grid {
    align-items: start;
}

.hc-system-block {
    align-self: start;
}

.hc-group-stack,
.hc-catalog-stack {
    gap: 26px;
}

.hc-group-head,
.hc-catalog-head {
    margin-bottom: 20px;
    padding-top: 2px;
}

.hc-identity-policy {
    --hc-block-accent: var(--hc-tone-indigo);
    display: grid;
    gap: 18px;
    margin-bottom: 20px;
    padding: 18px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent) 22%, var(--hc-line));
    border-radius: 18px;
    background:
        radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--hc-block-accent) 13%, transparent), transparent 14rem),
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--hc-success) 9%, transparent), transparent 12rem),
        linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent) 7%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-success) 4%, var(--hc-paper, #FFF)));
    box-shadow: 0 16px 34px -30px color-mix(in srgb, var(--hc-block-accent) 48%, transparent);
}

.hc-identity-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: start;
}

.hc-identity-copy {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 13px;
    align-items: start;
    min-width: 0;
}

.hc-identity-emblem {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    border-radius: 15px;
    color: color-mix(in srgb, var(--hc-block-accent) 80%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-block-accent) 13%, var(--hc-paper, #FFF));
    border: 1px solid color-mix(in srgb, var(--hc-block-accent) 22%, var(--hc-line));
    box-shadow: inset 0 1px 0 color-mix(in srgb, var(--hc-paper, #FFF) 78%, transparent);
}

.hc-identity-eyebrow {
    margin: 0 0 3px;
    color: color-mix(in srgb, var(--hc-block-accent) 74%, var(--hc-ink-soft));
    font-size: .7rem;
    font-weight: 850;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.hc-identity-copy h4 {
    margin: 0;
    color: var(--hc-brand-strong);
    font-size: 1.04rem;
    font-weight: 820;
    line-height: 1.2;
}

.hc-identity-copy p:not(.hc-identity-eyebrow) {
    max-width: 68ch;
    margin: 7px 0 0;
    color: var(--hc-ink-soft);
    font-size: .82rem;
    line-height: 1.5;
    font-weight: 470;
}

.hc-identity-status {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.hc-identity-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 32px;
    padding: 7px 10px;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent) 18%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-paper, #FFF) 78%, transparent);
    color: var(--hc-ink-soft);
    font-size: .72rem;
    font-weight: 830;
    white-space: nowrap;
}

.hc-identity-pill.is-on {
    color: color-mix(in srgb, var(--hc-success) 80%, #1f2937);
    background: color-mix(in srgb, var(--hc-success) 11%, var(--hc-paper, #FFF));
    border-color: color-mix(in srgb, var(--hc-success) 22%, var(--hc-line));
}

.hc-identity-pill.is-muted {
    color: color-mix(in srgb, var(--hc-ink-faint) 90%, #64748b);
    background: color-mix(in srgb, var(--hc-tone-slate) 6%, var(--hc-paper, #FFF));
}

.hc-identity-flow {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.hc-identity-step {
    min-width: 0;
    padding: 12px;
    border-radius: 14px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent) 14%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-paper, #FFF) 72%, transparent);
}

.hc-identity-step i {
    display: inline-grid;
    place-items: center;
    width: 28px;
    height: 28px;
    margin-bottom: 8px;
    border-radius: 10px;
    color: color-mix(in srgb, var(--hc-block-accent) 78%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-block-accent) 11%, var(--hc-paper, #FFF));
}

.hc-identity-step strong,
.hc-identity-step span {
    display: block;
}

.hc-identity-step strong {
    color: var(--hc-brand-strong);
    font-size: .78rem;
    font-weight: 820;
    line-height: 1.24;
}

.hc-identity-step span {
    margin-top: 4px;
    color: var(--hc-ink-soft);
    font-size: .72rem;
    font-weight: 500;
    line-height: 1.42;
}

.hc-identity-fields {
    display: grid;
    gap: 10px;
}

.hc-identity-fields .hc-policy-row {
    border-color: color-mix(in srgb, var(--hc-block-accent) 20%, var(--hc-line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-paper, #FFF) 86%, transparent), color-mix(in srgb, var(--hc-block-accent) 5%, var(--hc-paper, #FFF)));
}

.hc-policy-grid {
    display: grid;
    gap: 22px;
}

.hc-policy-box {
    border-top: 1px solid color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 18%, var(--hc-line));
    padding-top: 20px;
}

.hc-policy-box:first-child {
    border-top: 0;
    padding-top: 0;
}

.hc-policy-list {
    display: grid;
    gap: 11px;
}

.hc-policy-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: center;
    min-height: 86px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 13%, var(--hc-line));
    border-radius: 16px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 6%, var(--hc-paper, #FFF)), #fff 62%);
}

.hc-policy-row.is-locked {
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--hc-tone-sage) 10%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-accent) 5%, var(--hc-paper, #FFF)));
}

.hc-policy-main {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
    min-width: 0;
}

.hc-policy-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 82%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 12%, var(--hc-paper, #FFF));
    border: 1px solid color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 20%, var(--hc-line));
}

.hc-policy-main h4 {
    margin: 0;
    color: var(--hc-brand-strong);
    font-size: .94rem;
    font-weight: 760;
    line-height: 1.25;
}

.hc-policy-main p {
    margin: 4px 0 0;
    color: var(--hc-ink-soft);
    font-size: .78rem;
    line-height: 1.45;
    font-weight: 430;
}

.hc-policy-type {
    display: inline-flex;
    width: fit-content;
    margin-top: 7px;
    padding: 4px 7px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 68%, #475569);
    background: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 9%, var(--hc-paper, #FFF));
    font-size: .62rem;
    font-weight: 860;
    letter-spacing: .05em;
}

.hc-policy-controls {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    flex-wrap: wrap;
}

.hc-policy-toggle {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 10px;
    border: 1px solid color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 14%, var(--hc-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--hc-paper, #FFF) 78%, transparent);
    color: var(--hc-ink-soft);
    font-size: .76rem;
    font-weight: 760;
    cursor: pointer;
}

.hc-policy-toggle .hc-switch-ui {
    width: 34px;
    height: 20px;
    margin: 0;
}

.hc-policy-toggle .hc-switch-ui::after {
    width: 14px;
    height: 14px;
}

.hc-policy-lock {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 7px 10px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-brand) 70%, var(--hc-ink-mix, #334155));
    background: color-mix(in srgb, var(--hc-brand) 7%, var(--hc-paper, #FFF));
    border: 1px solid color-mix(in srgb, var(--hc-brand) 16%, var(--hc-line));
    font-size: .74rem;
    font-weight: 820;
}

.hc-policy-lock.is-required {
    color: color-mix(in srgb, var(--hc-success) 80%, #1f2937);
    background: color-mix(in srgb, var(--hc-success) 10%, var(--hc-paper, #FFF));
    border-color: color-mix(in srgb, var(--hc-success) 18%, var(--hc-line));
}

.hc-catalog-list {
    gap: 12px;
}

.hc-switch,
.hc-catalog-row,
.hc-backup-box,
.hc-device-row,
.hc-device-empty,
.hc-brand-preview {
    border-radius: 14px;
    border-color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 13%, var(--hc-line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 5%, color-mix(in srgb, var(--hc-paper, #FFF) 92%, transparent)), color-mix(in srgb, var(--hc-accent) 3%, color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent)));
}

.hc-switch {
    min-height: 64px;
    padding: 14px 16px;
}

.hc-catalog-row,
.hc-device-row {
    padding: 14px;
    gap: 12px;
}

.hc-catalog-row {
    align-items: end;
    gap: 14px;
    padding: 16px;
}

.hc-catalog-row.is-type {
    grid-template-columns:
        minmax(120px, .72fr)
        minmax(150px, 1fr)
        minmax(100px, .52fr)
        minmax(120px, .62fr)
        minmax(170px, 1.08fr)
        minmax(118px, 140px);
}

.hc-catalog-row.is-floor,
.hc-catalog-row.is-amenity,
.hc-catalog-row.is-simple {
    grid-template-columns: minmax(160px, .72fr) minmax(240px, 1.28fr) minmax(118px, 148px);
}

.hc-catalog-row.is-parking {
    grid-template-columns: minmax(140px, .62fr) minmax(220px, 1.08fr) minmax(104px, .42fr) minmax(118px, 148px);
}

.hc-catalog-row.is-unit {
    grid-template-columns: minmax(140px, .78fr) minmax(220px, 1.18fr) minmax(110px, .52fr) minmax(118px, 148px);
}

.hc-catalog-row.is-owner {
    grid-template-columns: minmax(150px, .78fr) minmax(220px, 1.22fr) minmax(118px, .54fr) minmax(118px, 148px);
}

.hc-catalog-row.is-owner-rule {
    grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr);
}

.hc-catalog-row.is-owner-room {
    grid-template-columns: minmax(118px, .62fr) minmax(150px, .78fr) minmax(190px, 1fr) minmax(190px, .95fr);
}

.hc-catalog-row .hc-catalog-cell:last-child .hc-switch {
    min-height: 44px;
    padding: 10px 12px;
}

.hc-switch-text strong {
    color: var(--hc-brand-strong);
    font-weight: 620;
}

.hc-switch-text small {
    color: var(--hc-ink-soft);
    font-weight: 430;
}

.hc-switch:has(input:checked) {
    border-color: color-mix(in srgb, var(--hc-success) 22%, var(--hc-line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-success) 8%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-block-accent, var(--hc-success)) 4%, var(--hc-paper, #FFF)));
}

.hc-brand-preview {
    --hc-block-accent: var(--hc-accent);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--hc-accent) 18%, transparent), transparent 11rem),
        linear-gradient(135deg, color-mix(in srgb, var(--hc-accent) 10%, color-mix(in srgb, var(--hc-paper, #FFF) 92%, transparent)), color-mix(in srgb, var(--hc-tone-indigo) 5%, color-mix(in srgb, var(--hc-paper, #FFF) 88%, transparent)));
}

.hc-brand-name,
.hc-device-title {
    color: var(--hc-brand-strong);
    font-weight: 620;
}

.hc-brand-note,
.hc-device-meta {
    color: var(--hc-ink-soft);
    font-weight: 430;
}

.hc-brand-preview {
    --preview-primary: var(--hc-brand);
    --preview-secondary: var(--hc-brand-strong);
    --preview-accent: var(--hc-accent);
    --preview-primary-soft: color-mix(in srgb, var(--preview-primary) 9%, var(--hc-paper, #FFF));
    --preview-accent-soft: color-mix(in srgb, var(--preview-accent) 12%, var(--hc-paper, #FFF));
    --preview-line: color-mix(in srgb, var(--preview-primary) 16%, #e7ded2);
    --preview-on-primary: var(--hc-paper, #FFFEFB);
    --preview-on-secondary: var(--hc-paper, #FFFEFB);
    position: sticky;
    top: 88px;
    align-self: start;
    display: grid;
    gap: 14px;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid var(--preview-line);
    background:
        radial-gradient(circle at 94% 0%, color-mix(in srgb, var(--preview-accent) 20%, transparent), transparent 10rem),
        linear-gradient(180deg, color-mix(in srgb, var(--hc-paper, #FFF) 96%, transparent), color-mix(in srgb, var(--preview-accent) 4%, color-mix(in srgb, var(--hc-paper, #FFF) 90%, transparent)));
    box-shadow: 0 18px 44px -34px color-mix(in srgb, var(--preview-secondary) 28%, transparent);
    overflow: hidden;
}

.hc-preview-head,
.hc-preview-row,
.hc-preview-chip,
.hc-preview-topbar,
.hc-preview-brandline,
.hc-preview-assets,
.hc-preview-asset,
.hc-preview-palette,
.hc-preview-swatch,
.hc-preview-login-card,
.hc-preview-login-brand,
.hc-preview-pwa {
    display: flex;
    align-items: center;
}

.hc-preview-head {
    justify-content: space-between;
    align-items: start;
    gap: 12px;
}

.hc-preview-kicker {
    display: block;
    margin: 0 0 3px;
    color: var(--hc-ink-faint);
    font-size: .68rem;
    font-weight: 680;
}

.hc-preview-title {
    display: block;
    max-width: 18rem;
    color: var(--hc-brand-strong);
    font-size: .98rem;
    line-height: 1.18;
    font-weight: 720;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hc-preview-live {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 9px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--preview-primary) 72%, var(--hc-ink-mix, #334155));
    background: var(--preview-primary-soft);
    border: 1px solid var(--preview-line);
    font-size: .7rem;
    font-weight: 760;
    transition: background .18s ease, border-color .18s ease, color .18s ease;
}

.hc-preview-live::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: var(--preview-accent);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--preview-accent) 16%, transparent);
}

.hc-brand-preview.is-dirty .hc-preview-live {
    color: color-mix(in srgb, var(--preview-accent) 72%, #3b2f12);
    background: color-mix(in srgb, var(--preview-accent) 15%, var(--hc-paper, #FFFEFB));
    border-color: color-mix(in srgb, var(--preview-accent) 28%, var(--preview-line));
}

.hc-preview-actions {
    flex: 0 0 auto;
    display: grid;
    justify-items: end;
    gap: 7px;
}

.hc-preview-reset {
    min-height: 26px;
    border: 1px solid var(--preview-line);
    border-radius: 8px;
    padding: 0 9px;
    color: color-mix(in srgb, var(--preview-secondary) 62%, #64748b);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 72%, transparent);
    font-size: .68rem;
    line-height: 1;
    font-weight: 720;
    cursor: pointer;
    transition: transform .16s ease, background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease;
}

.hc-preview-reset:hover:not(:disabled) {
    color: var(--preview-secondary);
    background: var(--hc-paper, #FFFEFB);
    border-color: color-mix(in srgb, var(--preview-primary) 25%, var(--preview-line));
    transform: translateY(-1px);
}

.hc-preview-reset:active:not(:disabled) {
    transform: translateY(0);
}

.hc-preview-reset:disabled {
    cursor: default;
    opacity: .46;
}

.hc-preview-reset:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--preview-accent) 48%, transparent);
    outline-offset: 2px;
}

.hc-preview-system {
    display: grid;
    grid-template-columns: 98px minmax(0, 1fr);
    min-height: 232px;
    border: 1px solid var(--preview-line);
    border-radius: 16px;
    overflow: hidden;
    background: var(--hc-paper, #FFFEFB);
}

.hc-preview-sidebar {
    display: grid;
    grid-template-rows: auto 1fr auto;
    gap: 12px;
    padding: 12px 10px;
    background:
        radial-gradient(circle at 50% -20%, color-mix(in srgb, var(--preview-accent) 14%, transparent), transparent 6rem),
        linear-gradient(180deg, color-mix(in srgb, var(--preview-primary) 6%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--preview-accent) 5%, var(--hc-paper, #FFF)));
    border-right: 1px solid var(--preview-line);
}

.hc-brand-preview[data-sidebar-style="solid"] .hc-preview-sidebar {
    background: linear-gradient(180deg, var(--preview-primary), color-mix(in srgb, var(--preview-primary) 78%, var(--preview-secondary)));
    color: var(--preview-on-primary);
    border-right-color: color-mix(in srgb, var(--preview-primary) 60%, var(--preview-secondary));
}

.hc-brand-preview[data-sidebar-style="dark"] .hc-preview-sidebar {
    background:
        radial-gradient(circle at 50% -20%, color-mix(in srgb, var(--preview-accent) 18%, transparent), transparent 6rem),
        linear-gradient(180deg, var(--preview-secondary), color-mix(in srgb, var(--preview-secondary) 78%, #111827));
    color: var(--preview-on-secondary);
    border-right-color: color-mix(in srgb, var(--preview-secondary) 74%, var(--hc-paper, #FFF));
}

.hc-preview-brandline {
    min-width: 0;
    gap: 8px;
}

.hc-preview-mark,
.hc-preview-app-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--preview-primary) 14%, #ded7ca);
    background: var(--hc-paper, #FFFEFB);
    color: var(--preview-primary);
}

.hc-preview-mark {
    width: 38px;
    height: 38px;
    border-radius: 12px;
}

.hc-preview-mark img,
.hc-preview-app-icon img,
.hc-preview-login-mark img,
.hc-preview-upload-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.hc-preview-sidebar-name {
    min-width: 0;
    color: inherit;
    font-size: .68rem;
    line-height: 1.12;
    font-weight: 780;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hc-preview-nav {
    display: grid;
    gap: 7px;
    align-content: start;
}

.hc-preview-nav-item {
    display: grid;
    grid-template-columns: 18px minmax(0, 1fr);
    align-items: center;
    gap: 6px;
    min-width: 0;
    min-height: 29px;
    border-radius: 9px;
    padding: 0 8px;
    color: color-mix(in srgb, var(--preview-secondary) 70%, #64748b);
    background: transparent;
    font-size: .62rem;
    font-weight: 720;
}

.hc-preview-sidebar .hc-preview-nav-item i {
    color: currentColor;
    font-size: .68rem;
}

.hc-preview-nav-item span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hc-preview-nav-item.is-active {
    background: linear-gradient(135deg, var(--preview-primary), color-mix(in srgb, var(--preview-primary) 78%, var(--preview-secondary)));
    color: var(--preview-on-primary);
}

.hc-brand-preview[data-sidebar-style="solid"] .hc-preview-nav-item,
.hc-brand-preview[data-sidebar-style="dark"] .hc-preview-nav-item {
    background: rgba(255,255,255,.08);
}

.hc-brand-preview[data-sidebar-style="solid"] .hc-preview-nav-item {
    color: color-mix(in srgb, var(--preview-on-primary) 72%, transparent);
}

.hc-brand-preview[data-sidebar-style="dark"] .hc-preview-nav-item {
    color: color-mix(in srgb, var(--preview-on-secondary) 72%, transparent);
}

.hc-brand-preview[data-sidebar-style="solid"] .hc-preview-nav-item.is-active,
.hc-brand-preview[data-sidebar-style="dark"] .hc-preview-nav-item.is-active {
    background: rgba(255,255,255,.2);
    color: inherit;
}

.hc-preview-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: inherit;
    font-size: .6rem;
    font-weight: 650;
    opacity: .72;
}

.hc-preview-status span {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #16a34a;
}

.hc-preview-workspace {
    min-width: 0;
    display: grid;
    grid-template-rows: auto 1fr;
    background:
        radial-gradient(circle at 98% 0%, color-mix(in srgb, var(--preview-accent) 14%, transparent), transparent 9rem),
        color-mix(in srgb, var(--preview-accent) 5%, #fbfaf6);
}

.hc-preview-topbar {
    justify-content: space-between;
    gap: 10px;
    min-height: 48px;
    padding: 10px 12px;
    border-bottom: 1px solid var(--preview-line);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 72%, transparent);
}

.hc-preview-screen-title {
    display: block;
    color: var(--preview-secondary);
    font-size: .76rem;
    line-height: 1.1;
    font-weight: 780;
}

.hc-preview-screen-subtitle {
    display: block;
    margin-top: 2px;
    color: color-mix(in srgb, var(--preview-secondary) 52%, #94a3b8);
    font-size: .6rem;
    line-height: 1.1;
    font-weight: 560;
}

.hc-preview-chip {
    flex: 0 0 auto;
    min-height: 25px;
    border-radius: 8px;
    padding: 0 8px;
    color: color-mix(in srgb, var(--preview-primary) 76%, var(--hc-ink-mix, #334155));
    background: var(--hc-paper, #FFFEFB);
    border: 1px solid var(--preview-line);
    font-size: .62rem;
    font-weight: 760;
}

.hc-preview-content {
    display: grid;
    gap: 11px;
    padding: 12px;
}

.hc-preview-metric-row {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
}

.hc-preview-metric {
    min-width: 0;
    min-height: 58px;
    padding: 9px;
    border-radius: 11px;
    background: color-mix(in srgb, var(--hc-paper, #FFF) 82%, transparent);
    border: 1px solid color-mix(in srgb, var(--preview-primary) 10%, #e8ddd0);
}

.hc-preview-metric strong,
.hc-preview-room-title {
    display: block;
    color: var(--preview-secondary);
    font-size: .8rem;
    line-height: 1.1;
    font-weight: 780;
}

.hc-preview-metric span,
.hc-preview-room-meta,
.hc-preview-login-copy,
.hc-preview-pwa span {
    display: block;
    margin-top: 4px;
    color: color-mix(in srgb, var(--preview-secondary) 54%, #94a3b8);
    font-size: .6rem;
    line-height: 1.25;
    font-weight: 560;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

.hc-preview-room {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    min-height: 68px;
    padding: 11px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--preview-accent) 24%, #e8ddd0);
    background: linear-gradient(135deg, var(--hc-paper, #FFF), var(--preview-accent-soft));
}

.hc-preview-room-cta {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    border-radius: 9px;
    padding: 0 10px;
    color: var(--preview-on-primary);
    background: linear-gradient(135deg, var(--preview-primary), color-mix(in srgb, var(--preview-primary) 78%, var(--preview-secondary)));
    font-size: .62rem;
    font-weight: 760;
    white-space: nowrap;
}

.hc-preview-login-card {
    position: relative;
    min-height: 116px;
    justify-content: space-between;
    gap: 12px;
    padding: 14px;
    border-radius: 16px;
    border: 1px solid var(--preview-line);
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--preview-primary) 92%, #111827), color-mix(in srgb, var(--preview-secondary) 84%, #111827));
    color: var(--preview-on-primary);
    overflow: hidden;
}

.hc-preview-login-bg {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity .18s ease;
}

.hc-preview-login-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hc-brand-preview[data-login-style="image"] .hc-preview-login-bg {
    opacity: .32;
}

.hc-brand-preview[data-login-style="soft"] .hc-preview-login-card {
    color: var(--preview-secondary);
    background: linear-gradient(135deg, var(--preview-accent-soft), var(--preview-primary-soft));
}

.hc-preview-login-brand,
.hc-preview-login-panel {
    position: relative;
    z-index: 1;
}

.hc-preview-login-brand {
    min-width: 0;
    gap: 10px;
}

.hc-preview-login-mark,
.hc-preview-app-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    overflow: hidden;
    background: var(--hc-paper, #FFFEFB);
    color: var(--preview-primary);
}

.hc-preview-login-title {
    display: block;
    max-width: 13rem;
    font-size: .9rem;
    line-height: 1.1;
    font-weight: 780;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hc-preview-login-copy {
    color: currentColor;
    opacity: .72;
}

.hc-preview-login-panel {
    min-width: 96px;
    display: grid;
    gap: 7px;
}

.hc-preview-login-line {
    height: 8px;
    border-radius: 999px;
    background: rgba(255,255,255,.42);
}

.hc-brand-preview[data-login-style="soft"] .hc-preview-login-line {
    background: rgba(15,23,42,.13);
}

.hc-preview-login-button {
    height: 24px;
    border-radius: 8px;
    background: var(--preview-accent);
}

.hc-preview-pwa {
    justify-content: space-between;
    gap: 12px;
    padding: 11px;
    border-radius: 14px;
    border: 1px solid var(--preview-line);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 76%, transparent);
}

.hc-preview-app-icons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.hc-preview-app-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
}

.hc-preview-app-icon.is-large {
    width: 44px;
    height: 44px;
    border-radius: 13px;
}

.hc-preview-assets {
    gap: 8px;
    flex-wrap: wrap;
}

.hc-preview-asset {
    gap: 7px;
    min-height: 36px;
    border-radius: 11px;
    padding: 6px 8px;
    border: 1px solid var(--preview-line);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 74%, transparent);
    color: var(--hc-ink-soft);
    font-size: .66rem;
    font-weight: 700;
}

.hc-preview-asset-box {
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
    border-radius: 7px;
    background: var(--preview-primary-soft);
    color: var(--preview-primary);
    overflow: hidden;
}

.hc-preview-palette {
    gap: 8px;
    flex-wrap: wrap;
}

.hc-preview-swatch {
    flex: 1 1 92px;
    min-width: 0;
    gap: 8px;
    min-height: 40px;
    padding: 7px 8px;
    border-radius: 11px;
    border: 1px solid var(--preview-line);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 74%, transparent);
    overflow: hidden;
}

.hc-preview-swatch-dot {
    width: 22px;
    height: 22px;
    border-radius: 8px;
    flex: 0 0 auto;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.34);
}

.hc-preview-swatch strong {
    display: block;
    color: var(--hc-ink);
    font-size: .66rem;
    line-height: 1.05;
    font-weight: 760;
}

.hc-preview-swatch code {
    display: block;
    margin-top: 2px;
    color: var(--hc-ink-soft);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .62rem;
    line-height: 1.05;
}

.hc-preview-contrast {
    display: grid;
    gap: 9px;
    padding: 10px;
    border-radius: 13px;
    border: 1px solid var(--preview-line);
    background: color-mix(in srgb, var(--hc-paper, #FFF) 76%, transparent);
}

.hc-preview-contrast-head,
.hc-preview-contrast-grid,
.hc-preview-contrast-item {
    display: flex;
    align-items: center;
}

.hc-preview-contrast-head {
    justify-content: space-between;
    gap: 10px;
}

.hc-preview-contrast-title {
    display: block;
    color: var(--preview-secondary);
    font-size: .68rem;
    line-height: 1.1;
    font-weight: 780;
}

.hc-preview-contrast-state {
    flex: 0 0 auto;
    color: var(--hc-success);
    font-size: .62rem;
    line-height: 1.1;
    font-weight: 760;
}

.hc-preview-contrast.has-warning .hc-preview-contrast-state {
    color: var(--hc-danger);
}

.hc-preview-contrast-grid {
    gap: 6px;
    flex-wrap: wrap;
}

.hc-preview-contrast-item {
    flex: 1 1 88px;
    min-width: 0;
    justify-content: space-between;
    gap: 8px;
    min-height: 32px;
    padding: 6px 7px;
    border-radius: 9px;
    border: 1px solid color-mix(in srgb, var(--hc-success) 20%, var(--preview-line));
    background: color-mix(in srgb, var(--hc-success) 6%, var(--hc-paper, #FFFEFB));
    overflow: hidden;
}

.hc-preview-contrast-item.is-warn {
    border-color: color-mix(in srgb, var(--hc-danger) 24%, var(--preview-line));
    background: color-mix(in srgb, var(--hc-danger) 6%, var(--hc-paper, #FFFEFB));
}

.hc-preview-contrast-item span,
.hc-preview-contrast-item strong {
    display: block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hc-preview-contrast-item span {
    color: var(--hc-ink-soft);
    font-size: .6rem;
    line-height: 1.1;
    font-weight: 650;
}

.hc-preview-contrast-item strong {
    flex: 0 0 auto;
    color: var(--hc-success);
    font-size: .62rem;
    line-height: 1.1;
    font-weight: 780;
}

.hc-preview-contrast-item.is-warn strong {
    color: var(--hc-danger);
}

.hc-upload-thumb {
    position: relative;
}

.hc-upload-thumb img {
    display: block;
}

.hc-upload-row {
    align-items: start;
}

.hc-upload-control {
    min-width: 0;
    display: grid;
    gap: 6px;
}

.hc-upload-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    margin: 0;
    color: var(--hc-ink-soft);
    font-size: .72rem;
    line-height: 1.3;
    font-weight: 560;
}

.hc-upload-status::before {
    content: "";
    width: 6px;
    height: 6px;
    flex: 0 0 auto;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hc-ink-faint) 72%, var(--hc-paper, #FFF));
}

.hc-upload-row.is-selected .hc-upload-status {
    color: color-mix(in srgb, var(--hc-success) 84%, var(--hc-ink));
}

.hc-upload-row.is-selected .hc-upload-status::before {
    background: var(--hc-success);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--hc-success) 14%, transparent);
}

.hc-upload-row.is-selected .hc-upload-thumb {
    border-color: color-mix(in srgb, var(--hc-success) 28%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-success) 8%, var(--hc-paper, #FFF));
}

.hc-preview-empty-icon[hidden],
.hc-preview-upload-img[hidden],
.hc-preview-image[hidden] {
    display: none !important;
}

@media (max-width: 1180px) {
    .hc-preview-system {
        grid-template-columns: 88px minmax(0, 1fr);
    }

    .hc-preview-metric-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 520px) {
    .hc-preview-system {
        grid-template-columns: 1fr;
    }

    .hc-preview-sidebar {
        grid-template-columns: auto 1fr auto;
        grid-template-rows: auto;
        align-items: center;
        border-right: 0;
        border-bottom: 1px solid var(--preview-line);
    }

    .hc-preview-nav {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .hc-preview-status,
    .hc-preview-sidebar-name,
    .hc-preview-nav-item span {
        display: none;
    }

    .hc-preview-login-card,
    .hc-preview-pwa {
        align-items: stretch;
        flex-direction: column;
    }
}

@media (max-width: 1240px) {
    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-parking,
    .hc-catalog-row.is-unit,
    .hc-catalog-row.is-owner,
    .hc-catalog-row.is-owner-rule,
    .hc-catalog-row.is-owner-room {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 860px) {
    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-parking,
    .hc-catalog-row.is-unit,
    .hc-catalog-row.is-owner,
    .hc-catalog-row.is-owner-rule,
    .hc-catalog-row.is-owner-room {
        grid-template-columns: 1fr;
    }

    .hc-policy-row {
        grid-template-columns: 1fr;
    }

    .hc-policy-controls {
        justify-content: flex-start;
    }

    .hc-identity-top,
    .hc-identity-flow {
        grid-template-columns: 1fr;
    }

    .hc-identity-status {
        justify-content: flex-start;
    }
}

@media (max-width: 1020px) {
    .hc-page {
        --hc-page-gutter: clamp(18px, 4vw, 34px);
    }

    .hc-top {
        grid-template-columns: 1fr;
    }

    .hc-identity {
        max-width: none;
    }
}

@media (max-width: 720px) {
    .hc-page {
        --hc-page-gutter: 10px;
    }

    .hc-shell {
        width: 100%;
        padding-top: 18px;
    }

    .hc-top-main,
    .hc-identity,
    .hc-panel {
        border-radius: 16px;
        padding: 16px;
    }

    .hc-top-main {
        padding: 0;
    }

    .hc-title-lockup {
        grid-template-columns: 44px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }

    .hc-hero-icon {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
        border-radius: 14px;
    }

    .hc-title {
        font-size: 2rem;
    }

    .hc-nav-card {
        border-radius: 16px;
        padding: 8px;
    }

    .hc-nav-link {
        flex-basis: 158px;
    }

    #configForm {
        gap: 18px;
    }

    .hc-save-dock {
        min-height: 0;
        padding: 14px;
    }

    .hc-setting-group,
    .hc-catalog-box,
    .hc-system-block,
    .hc-notification-block {
        padding: 22px 16px 16px;
    }

    .hc-setting-group:first-child,
    .hc-catalog-box:first-child,
    .hc-system-block:first-child,
    .hc-notification-block:first-child {
        padding-top: 22px;
    }
}

/* ── Apariencia: tema de color (preferencia por dispositivo) ── */
.hc-theme-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    padding: 18px 20px;
    border: 1px solid var(--hc-line);
    border-radius: 16px;
    background: var(--hc-surface);
    box-shadow: var(--hc-shadow);
}

.hc-theme-label {
    margin: 0;
    font-size: .92rem;
    font-weight: 700;
    color: var(--hc-ink);
}

.hc-theme-hint {
    margin: 3px 0 0;
    font-size: .76rem;
    color: var(--hc-ink-faint);
}

.hc-theme-seg {
    display: inline-flex;
    align-items: stretch;
    gap: 4px;
    padding: 5px;
    border: 1px solid var(--hc-line);
    border-radius: 999px;
    background: var(--hc-surface-muted);
}

.hc-theme-opt {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    min-width: 74px;
    padding: 9px 16px;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: var(--hc-ink-soft);
    font-family: inherit;
    font-size: .74rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .18s ease, color .18s ease;
    -webkit-tap-highlight-color: transparent;
}

.hc-theme-opt i {
    font-size: .95rem;
}

.hc-theme-opt:hover {
    color: var(--hc-ink);
}

.hc-theme-opt.is-active {
    background: color-mix(in srgb, var(--hc-brand) 14%, var(--hc-surface));
    color: var(--hc-ink);
    box-shadow: 0 1px 4px color-mix(in srgb, var(--hc-brand) 30%, transparent);
}

.hc-theme-opt:focus-visible {
    outline: 2px solid var(--hc-focus);
    outline-offset: 2px;
}

@media (max-width: 640px) {
    .hc-theme-card {
        flex-direction: column;
        align-items: stretch;
    }

    .hc-theme-seg {
        justify-content: center;
    }

    .hc-theme-opt {
        flex: 1;
        min-width: 0;
    }
}

/* ═══════════════════════════════════════════════════════════════════
   Capa boutique (Claude Design configuracion.html · Deleite Sereno).
   Solo re-tematiza vía tokens --hc-*: el re-mapeo dark de dark-theme.css
   (html[data-theme="dark"] .hc-page) tiene mayor especificidad y gana.
   ═══════════════════════════════════════════════════════════════════ */
.hc-page {
    --hc-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --hc-brand-strong: var(--brand-action-bg-hover, color-mix(in srgb, var(--hc-brand) 84%, #0B1220));
    --hc-on-brand: var(--brand-action-text, #FFFEFB);
    --hc-accent: var(--brand-accent, #B0883F);
    --hc-gold-bg: color-mix(in srgb, var(--hc-accent) 16%, var(--hc-paper, #FFFDF6));
    --hc-gold-line: color-mix(in srgb, var(--hc-accent) 34%, var(--hc-line));
    --hc-gold-soft: color-mix(in srgb, var(--hc-accent) 55%, var(--hc-on-brand));
    --hc-bg: #F6F2EA;
    --hc-bg-deep: #F1EBDF;
    --hc-surface: var(--hc-paper, #FFFFFF);
    --hc-surface-muted: color-mix(in srgb, var(--hc-accent) 4%, var(--hc-paper, #FEFCF7));
    --hc-ink: var(--hc-ink-mix, #1B2746);
    --hc-ink-soft: #3E4A66;
    --hc-ink-faint: #6C7689;
    --hc-line: #ECE5D8;
    --hc-line-strong: #E0D7C6;
    --hc-success: #1E9E63;
    --hc-success-bg: #E7F4EC;
    --hc-shadow: 0 2px 8px rgba(27,39,70,.045), 0 12px 28px rgba(27,39,70,.055);
    background: linear-gradient(180deg, color-mix(in srgb, var(--hc-bg) 40%, var(--hc-paper, #FBF8F2)), var(--hc-bg) 60%);
}

html[data-theme="dark"] .hc-page {
    background: linear-gradient(180deg, var(--hc-bg), var(--hc-bg-deep));
}

/* ── Cabecera: tile navy con ícono dorado + serif ── */
.hc-page .hc-hero-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: grid;
    place-items: center;
    background: linear-gradient(150deg, var(--hc-brand), var(--hc-brand-strong));
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--hc-brand) 72%, #111827);
    border: 0;
}

.hc-page .hc-hero-icon i {
    color: var(--hc-gold-soft);
    font-size: 1.35rem;
}

.hc-page .hc-title {
    font-family: var(--hc-serif);
    font-size: clamp(1.85rem, 3vw, 2.25rem);
    font-weight: 600;
    letter-spacing: -.01em;
    color: var(--hc-ink);
    line-height: 1;
}

.hc-page .hc-subtitle { color: var(--hc-ink-faint); font-weight: 500; }
.hc-page .hc-eyebrow { color: var(--hc-ink-faint); }

.hc-page .hc-chip {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    border-radius: 999px;
    color: var(--hc-ink-soft);
    font-weight: 700;
    box-shadow: 0 1px 2px rgba(27,39,70,.05);
}

.hc-page .hc-chip i { color: var(--hc-accent); }

/* El diseño concentra la identidad en el chip del hotel: sin aside duplicado */
.hc-page .hc-identity { display: none; }
.hc-page .hc-top { grid-template-columns: minmax(0, 1fr); }

/* ── Riel de pestañas ── */
.hc-page .hc-nav-card {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    border-radius: 20px;
    box-shadow: 0 1px 2px rgba(27,39,70,.05);
}

.hc-page .hc-nav-head { display: none; }

.hc-page .hc-nav .hc-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 12px;
    color: var(--hc-ink-soft);
    border: 1px solid transparent;
}

.hc-page .hc-nav .hc-nav-link i {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: var(--hc-surface-muted);
    color: var(--hc-ink-faint);
    display: grid;
    place-items: center;
    font-size: .82rem;
    flex: none;
    transition: background .14s ease, color .14s ease;
}

.hc-page .hc-nav .hc-nav-link strong { font-weight: 600; color: inherit; }

.hc-page .hc-nav .hc-nav-link strong span {
    display: block;
    font-size: .69rem;
    font-weight: 500;
    color: var(--hc-ink-faint);
    margin-top: 1px;
}

.hc-page .hc-nav .hc-nav-link:hover { background: var(--hc-surface-muted); color: var(--hc-ink); }

.hc-page .hc-nav .hc-nav-link.is-active {
    background: var(--hc-brand);
    color: var(--hc-on-brand);
    box-shadow: 0 10px 20px -16px color-mix(in srgb, var(--hc-brand) 80%, #111827);
}

.hc-page .hc-nav .hc-nav-link.is-active i {
    background: color-mix(in srgb, var(--hc-on-brand) 16%, transparent);
    color: var(--hc-gold-soft);
}

.hc-page .hc-nav .hc-nav-link.is-active strong span {
    color: color-mix(in srgb, var(--hc-on-brand) 62%, transparent);
}

.hc-page .hc-nav-foot { color: var(--hc-ink-faint); }

/* ── Encabezados de panel: serif + chip dorado ── */
.hc-page .hc-panel.is-active { animation: hcPin .24s ease; }

@keyframes hcPin {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: none; }
}

.hc-page .hc-panel-title {
    font-family: var(--hc-serif);
    font-weight: 600;
    color: var(--hc-ink);
}

.hc-page .hc-section-mark {
    background: var(--hc-gold-bg);
    color: var(--hc-accent);
    border: 0;
    border-radius: 11px;
}

.hc-page .hc-section-kicker { color: var(--hc-ink-faint); }
.hc-page .hc-panel-copy { color: var(--hc-ink-faint); }

.hc-page .hc-badge {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    color: var(--hc-ink-soft);
    border-radius: 999px;
}

/* ── Cajas de contenido = card2 del diseño ── */
.hc-page .hc-notification-block,
.hc-page .hc-policy-box,
.hc-page .hc-catalog-box,
.hc-page .hc-system-block,
.hc-page .hc-owner-flow-card,
.hc-page .hc-theme-card {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    border-radius: 20px;
    box-shadow: 0 1px 2px rgba(27,39,70,.05);
}

.hc-page .hc-group-title {
    font-family: var(--hc-serif);
    font-size: 1.08rem;
    font-weight: 600;
    color: var(--hc-ink);
}

.hc-page .hc-group-title i { color: var(--hc-accent); }
.hc-page .hc-group-hint { color: var(--hc-ink-faint); }

/* ── Resumen solo lectura (rstat) ── */
.hc-page .hc-stat {
    border: 1px solid var(--hc-line);
    border-radius: 14px;
    background: var(--hc-surface-muted);
    box-shadow: none;
}

.hc-page .hc-stat.is-featured {
    background: linear-gradient(135deg, var(--hc-gold-bg), color-mix(in srgb, var(--hc-accent) 24%, var(--hc-paper, #FBF3E1)));
    border-color: var(--hc-gold-line);
}

.hc-page .hc-stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    color: var(--hc-accent);
}

.hc-page .hc-stat-label {
    font-size: .66rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--hc-ink-faint);
}

.hc-page .hc-stat-value { font-weight: 700; color: var(--hc-ink); }
.hc-page .hc-stat-detail { color: var(--hc-ink-faint); }

/* ── Switch rows (swrow): verde semántico al activar ── */
.hc-page .hc-switch {
    border: 1px solid var(--hc-line);
    border-radius: 13px;
    background: var(--hc-surface-muted);
    transition: border-color .14s ease, background .14s ease;
}

.hc-page .hc-switch:hover { border-color: var(--hc-gold-line); }

.hc-page .hc-switch:has(input:checked) {
    background: color-mix(in srgb, var(--hc-success) 7%, var(--hc-surface));
    border-color: color-mix(in srgb, var(--hc-success) 35%, var(--hc-line));
    box-shadow: none;
}

.hc-page .hc-switch .hc-switch-ui {
    width: 46px;
    height: 27px;
    border-radius: 99px;
    background: var(--hc-line-strong);
    position: relative;
    flex: none;
    transition: background .2s ease;
}

.hc-page .hc-switch .hc-switch-ui::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 21px;
    height: 21px;
    border-radius: 50%;
    background: var(--hc-paper, #fff);
    box-shadow: 0 1px 2px rgba(27,39,70,.18);
    transition: transform .2s ease;
}

.hc-page .hc-switch:has(input:checked) .hc-switch-ui { background: var(--hc-success); }
.hc-page .hc-switch:has(input:checked) .hc-switch-ui::after { transform: translateX(19px); }

.hc-page .hc-switch-text strong { font-weight: 700; color: var(--hc-ink); }
.hc-page .hc-switch-text small { color: var(--hc-ink-faint); }

/* ── Filas de catálogo ── */
.hc-page .hc-catalog-row {
    border: 1px solid var(--hc-line);
    border-radius: 12px;
    background: var(--hc-surface-muted);
}

/* ── Dispositivos ── */
.hc-page .hc-device-row {
    border: 1px solid var(--hc-line);
    border-radius: 14px;
    background: var(--hc-surface-muted);
}

.hc-page .hc-device-icon {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    color: var(--hc-ink);
}

/* ── Save dock y botones ── */
.hc-page .hc-save-dock {
    background: var(--hc-surface);
    border: 1px solid var(--hc-line);
    border-radius: 16px;
    box-shadow: var(--hc-shadow);
}

.hc-page .hc-save-copy i { color: var(--hc-accent); }

.hc-page .hc-btn-primary {
    background: var(--hc-brand);
    color: var(--hc-on-brand);
    border: 0;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 10px 20px -14px color-mix(in srgb, var(--hc-brand) 75%, #111827);
}

.hc-page .hc-btn-primary:hover {
    background: var(--hc-brand-strong);
    transform: translateY(-1px);
}

.hc-page .hc-link-btn {
    background: var(--hc-surface);
    color: var(--hc-ink-soft);
    border: 1px solid var(--hc-line);
    border-radius: 12px;
    font-weight: 700;
}

.hc-page .hc-link-btn:hover {
    border-color: var(--hc-gold-line);
    color: var(--hc-ink);
}

/* ── Campos: superficie cálida + foco dorado ── */
.hc-page .hc-field input:not([type="checkbox"]):not([type="radio"]),
.hc-page .hc-field select,
.hc-page .hc-field textarea {
    background: var(--hc-surface-muted);
    border: 1px solid var(--hc-line);
    border-radius: 11px;
    color: var(--hc-ink);
}

.hc-page .hc-field input:not([type="checkbox"]):not([type="radio"]):focus,
.hc-page .hc-field select:focus,
.hc-page .hc-field textarea:focus {
    border-color: color-mix(in srgb, var(--hc-accent) 55%, var(--hc-line));
    box-shadow: 0 0 0 3px var(--hc-gold-bg);
    background: var(--hc-surface);
}

@media (prefers-reduced-motion: reduce) {
    .hc-page .hc-panel.is-active { animation: none; }
    .hc-page .hc-switch .hc-switch-ui,
    .hc-page .hc-switch .hc-switch-ui::after { transition: none; }
}
</style>

<div class="hc-page" data-active-section="hc-readonly">
    <main class="hc-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <header class="hc-top" aria-labelledby="config-page-title">
            <section class="hc-top-main">
                <div class="hc-title-lockup">
                    <div class="hc-hero-icon" aria-hidden="true">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <div class="hc-title-copy">
                        <p class="hc-eyebrow">Operacion hotelera</p>
                        <h1 id="config-page-title" class="hc-title">Configuracion del hotel</h1>
                        <p class="hc-subtitle">
                            Administra operacion, catalogos, marca y dispositivos desde una vista clara para este hotel.
                        </p>

                        <div class="hc-top-meta" aria-label="Contexto del hotel">
                            <span class="hc-chip">
                                <i class="fas fa-hotel"></i>
                                <?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if ($configHotelId): ?>
                                <span class="hc-chip">
                                    <i class="fas fa-hashtag"></i>
                                    Hotel <?= (int) $configHotelId ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($configHotelSlug): ?>
                                <span class="hc-chip">
                                    <i class="fas fa-link"></i>
                                    <?= htmlspecialchars($configHotelSlug, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="hc-identity" aria-label="Identidad activa">
                <div class="hc-logo-block">
                    <span class="hc-logo">
                        <?php if ($configHotelLogo): ?>
                            <img src="<?= htmlspecialchars($configHotelLogo, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <i class="fas fa-hotel"></i>
                        <?php endif; ?>
                    </span>
                    <div>
                        <p class="hc-identity-label">Hotel activo</p>
                        <strong class="hc-identity-name"><?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>
                <p class="hc-identity-note">
                    Esta vista usa la identidad visual activa del hotel.
                </p>
                <p class="hc-token-note">
                    <i class="fas fa-shield-alt"></i>
                    Marca del hotel activa
                </p>
            </aside>
        </header>

        <div class="hc-layout">
            <aside class="hc-nav-card" aria-label="Navegacion de configuracion">
                <div class="hc-nav-head">
                    <span class="hc-mini-logo">
                        <?php if ($configHotelLogo): ?>
                            <img src="<?= htmlspecialchars($configHotelLogo, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <i class="fas fa-hotel"></i>
                        <?php endif; ?>
                    </span>
                    <div>
                        <p class="hc-identity-label">Ajustes del hotel</p>
                        <strong class="hc-nav-title"><?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <nav class="hc-nav" aria-label="Pantallas de configuracion">
                    <a href="#hc-readonly" class="hc-nav-link is-active" aria-current="page">
                        <i class="fas fa-clipboard-check"></i>
                        <strong>Vista general <span>Valores leidos</span></strong>
                    </a>
                    <a href="#hc-appearance" class="hc-nav-link">
                        <i class="fas fa-circle-half-stroke"></i>
                        <strong>Apariencia <span>Tema de color</span></strong>
                    </a>
                    <?php if (!empty($configGroupedHotelSettings)): ?>
                        <a href="#hc-settings" class="hc-nav-link">
                            <i class="fas fa-sliders-h"></i>
                            <strong>Ajustes <span>Operacion y textos</span></strong>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($configNotificationSettingDefinitions)): ?>
                        <a href="#hc-notifications" class="hc-nav-link">
                            <i class="fas fa-bell"></i>
                            <strong>Notificaciones <span>Reglas y umbrales</span></strong>
                        </a>
                    <?php endif; ?>
                    <a href="#hc-guest-fields" class="hc-nav-link">
                        <i class="fas fa-user-check"></i>
                        <strong>Huespedes <span>Campos requeridos</span></strong>
                    </a>
                    <a href="#hc-rooms" class="hc-nav-link">
                        <i class="fas fa-bed"></i>
                        <strong>Habitaciones <span>Tipos y amenidades</span></strong>
                    </a>
                    <a href="#hc-owners" class="hc-nav-link">
                        <i class="fas fa-user-tie"></i>
                        <strong>Propietarios <span>Distribucion</span></strong>
                    </a>
                    <a href="#hc-catalogs" class="hc-nav-link">
                        <i class="fas fa-layer-group"></i>
                        <strong>Catalogos <span>Listas auxiliares</span></strong>
                    </a>
                    <a href="#hc-brand" class="hc-nav-link">
                        <i class="fas fa-palette"></i>
                        <strong>Marca <span>Identidad visual</span></strong>
                    </a>
                    <?php if (!empty($configFooterNavCatalog)): ?>
                    <a href="#hc-footer-nav" class="hc-nav-link">
                        <i class="fas fa-grip"></i>
                        <strong>Barra inferior <span>Atajos de la app</span></strong>
                    </a>
                    <?php endif; ?>
                    <a href="#hc-system" class="hc-nav-link">
                        <i class="fas fa-server"></i>
                        <strong>Sistema <span>Sesion y respaldos</span></strong>
                    </a>
                    <a href="#hc-devices" class="hc-nav-link">
                        <i class="fas fa-mobile-screen-button"></i>
                        <strong>Dispositivos <span>Push PWA</span></strong>
                    </a>
                </nav>

                <div class="hc-nav-foot">
                    <span><i class="fas fa-lock"></i> La vista general es solo lectura.</span>
                    <span><i class="fas fa-save"></i> Los cambios editables se aplican al guardar.</span>
                </div>
            </aside>

            <div class="hc-main">
                <section id="hc-readonly" class="hc-panel is-active" data-hc-section aria-labelledby="hc-readonly-title">
                    <div class="hc-panel-header">
                        <div>
                            <p class="hc-section-kicker">Solo lectura</p>
                            <h2 id="hc-readonly-title" class="hc-panel-title">
                                <span class="hc-section-mark"><i class="fas fa-clipboard-check"></i></span>
                                Resumen operativo del hotel
                            </h2>
                            <p class="hc-panel-copy">
                                Valores actuales del hotel. Este bloque no modifica ni envia datos.
                            </p>
                        </div>
                        <span class="hc-badge">
                            <i class="fas fa-lock"></i>
                            Solo lectura
                        </span>
                    </div>

                    <div class="hc-readonly-grid">
                        <?php foreach ($configOperationalReadOnly as $operationalItem): ?>
                            <article class="hc-stat<?= !empty($operationalItem['featured']) ? ' is-featured' : '' ?>">
                                <span class="hc-stat-icon">
                                    <i class="fas <?= htmlspecialchars($operationalItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </span>
                                <div>
                                    <p class="hc-stat-label"><?= htmlspecialchars($operationalItem['label'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="hc-stat-value"><?= $configOperationalDisplay($operationalItem['value']) ?></p>
                                    <p class="hc-stat-detail"><?= htmlspecialchars($operationalItem['detail'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="hc-appearance" class="hc-panel" data-hc-section aria-labelledby="hc-appearance-title">
                    <div class="hc-panel-header">
                        <div>
                            <p class="hc-section-kicker">Preferencia de este dispositivo</p>
                            <h2 id="hc-appearance-title" class="hc-panel-title">
                                <span class="hc-section-mark"><i class="fas fa-circle-half-stroke"></i></span>
                                Apariencia
                            </h2>
                            <p class="hc-panel-copy">
                                Elige el tema de color y la vibración de la app. Se aplica al instante y se guarda solo en este dispositivo.
                            </p>
                        </div>
                    </div>

                    <div class="hc-theme-card">
                        <div>
                            <p class="hc-theme-label">Tema de color</p>
                            <p class="hc-theme-hint">Con "Auto" la app sigue el modo claro/oscuro del sistema.</p>
                        </div>
                        <div class="hc-theme-seg" role="radiogroup" aria-label="Tema de color">
                            <button type="button" class="hc-theme-opt" data-theme-mode="light" role="radio" aria-checked="false">
                                <i class="fas fa-sun" aria-hidden="true"></i>
                                <span>Claro</span>
                            </button>
                            <button type="button" class="hc-theme-opt" data-theme-mode="auto" role="radio" aria-checked="false">
                                <i class="fas fa-desktop" aria-hidden="true"></i>
                                <span>Auto</span>
                            </button>
                            <button type="button" class="hc-theme-opt" data-theme-mode="dark" role="radio" aria-checked="false">
                                <i class="fas fa-moon" aria-hidden="true"></i>
                                <span>Oscuro</span>
                            </button>
                        </div>
                    </div>

                    <div class="hc-theme-card" id="hc-haptics-card" hidden>
                        <div>
                            <p class="hc-theme-label">Vibración (hápticos)</p>
                            <p class="hc-theme-hint">Pequeñas vibraciones al confirmar acciones y avisos. Disponible solo en dispositivos compatibles.</p>
                        </div>
                        <div class="hc-theme-seg" role="radiogroup" aria-label="Vibración">
                            <button type="button" class="hc-theme-opt" data-haptics-mode="on" role="radio" aria-checked="false">
                                <i class="fas fa-mobile-screen-button" aria-hidden="true"></i>
                                <span>Activada</span>
                            </button>
                            <button type="button" class="hc-theme-opt" data-haptics-mode="off" role="radio" aria-checked="false">
                                <i class="fas fa-ban" aria-hidden="true"></i>
                                <span>Desactivada</span>
                            </button>
                        </div>
                    </div>
                </section>

                <form method="POST" action="<?= url('configuracion/update') ?>" id="configForm" enctype="multipart/form-data" data-form-guard="off">
                    <?= csrf_field() ?>

                    <section class="hc-save-dock" aria-label="Acciones de guardado">
                        <div class="hc-save-copy">
                            <i class="fas fa-circle-info"></i>
                            Revisa las secciones y guarda cuando todo este listo.
                        </div>
                        <div class="hc-actions">
                            <a href="<?= url('dashboard') ?>" class="hc-link-btn">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="hc-btn hc-btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </section>

                    <?php if (!empty($configGroupedHotelSettings)): ?>
                        <section id="hc-settings" class="hc-panel" data-hc-section aria-labelledby="hc-settings-title">
                            <div class="hc-panel-header">
                                <div>
                                    <p class="hc-section-kicker">Editable por hotel</p>
                                    <h2 id="hc-settings-title" class="hc-panel-title">
                                        <span class="hc-section-mark"><i class="fas fa-sliders-h"></i></span>
                                        Ajustes operativos y textos
                                    </h2>
                                    <p class="hc-panel-copy">
                                        Campos para contacto, horarios, documentos, reservaciones, reportes y PWA.
                                    </p>
                                </div>
                            </div>

                            <div class="hc-group-stack">
                                <?php foreach ($configHotelSettingGroupMeta as $groupKey => $groupMeta): ?>
                                    <?php if (empty($configGroupedHotelSettings[$groupKey])): ?>
                                        <?php continue; ?>
                                    <?php endif; ?>
                                    <section class="hc-setting-group" aria-label="<?= htmlspecialchars($groupMeta['title'], ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="hc-group-head">
                                            <div>
                                                <h3 class="hc-group-title">
                                                    <i class="fas <?= htmlspecialchars($groupMeta['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                    <?= htmlspecialchars($groupMeta['title'], ENT_QUOTES, 'UTF-8') ?>
                                                </h3>
                                                <p class="hc-group-hint"><?= htmlspecialchars($groupMeta['hint'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                        </div>
                                        <div class="hc-field-grid">
                                            <?php foreach ($configGroupedHotelSettings[$groupKey] as $settingKey => $settingDefinition): ?>
                                                <?php
                                                $settingValue = $configHotelSettings[$settingKey] ?? ($settingDefinition['default'] ?? '');
                                                echo $configRenderSettingField($settingKey, $settingDefinition, $settingValue);
                                                ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($configNotificationSettingDefinitions)): ?>
                        <section id="hc-notifications" class="hc-panel" data-hc-section aria-labelledby="hc-notifications-title">
                            <div class="hc-panel-header">
                                <div>
                                    <p class="hc-section-kicker">Centro de notificaciones</p>
                                    <h2 id="hc-notifications-title" class="hc-panel-title">
                                        <span class="hc-section-mark"><i class="fas fa-bell"></i></span>
                                        Reglas automaticas y push
                                    </h2>
                                    <p class="hc-panel-copy">
                                        Preferencias por hotel para avisos del dashboard, reglas automaticas y umbrales de prioridad.
                                    </p>
                                </div>
                            </div>

                            <div class="hc-group-stack">
                                <?php foreach ($configNotificationGroups as $notificationGroup): ?>
                                    <section class="hc-notification-block" aria-label="<?= htmlspecialchars($notificationGroup['title'], ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="hc-group-head">
                                            <div>
                                                <h3 class="hc-group-title">
                                                    <i class="fas <?= htmlspecialchars($notificationGroup['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                                    <?= htmlspecialchars($notificationGroup['title'], ENT_QUOTES, 'UTF-8') ?>
                                                </h3>
                                                <p class="hc-group-hint"><?= htmlspecialchars($notificationGroup['hint'], ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                        </div>
                                        <div class="hc-field-grid">
                                            <?php foreach ($notificationGroup['keys'] as $settingKey): ?>
                                                <?php
                                                if (empty($configNotificationSettingDefinitions[$settingKey])) {
                                                    continue;
                                                }

                                                $settingDefinition = $configNotificationSettingDefinitions[$settingKey];
                                                $settingValue = $configHotelSettings[$settingKey] ?? ($settingDefinition['default'] ?? '');
                                                echo $configRenderSettingField($settingKey, $settingDefinition, $settingValue);
                                                ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section id="hc-guest-fields" class="hc-panel" data-hc-section aria-labelledby="hc-guest-fields-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Registro de huespedes</p>
                                <h2 id="hc-guest-fields-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-user-check"></i></span>
                                    Campos solicitados al registrar
                                </h2>
                                <p class="hc-panel-copy">
                                    Define que datos se muestran y cuales son obligatorios para el huesped y sus vehiculos. Nombre y telefono celular quedan como base del sistema.
                                </p>
                            </div>
                            <span class="hc-badge">
                                <i class="fas fa-hotel"></i>
                                Por hotel
                            </span>
                        </div>

                        <div class="hc-policy-grid">
                            <section class="hc-policy-box" aria-label="Datos del huesped">
                                <div class="hc-group-head">
                                    <div>
                                        <h3 class="hc-group-title">
                                            <i class="fas fa-user"></i>
                                            Datos del huesped
                                        </h3>
                                        <p class="hc-group-hint">Contacto, procedencia, documentos, datos fiscales y preferencias del expediente.</p>
                                    </div>
                                </div>
                                <?php if (!empty($configIdentityFieldDefinitions)): ?>
                                    <section class="hc-identity-policy" data-identity-policy-panel aria-label="Configuracion de identificacion">
                                        <div class="hc-identity-top">
                                            <div class="hc-identity-copy">
                                                <span class="hc-identity-emblem"><i class="fas fa-id-card-clip"></i></span>
                                                <div>
                                                    <p class="hc-identity-eyebrow">Documento de identidad</p>
                                                    <h4>INE, pasaporte o licencia</h4>
                                                    <p>
                                                        Decide si recepcion debe capturar el tipo de documento, el folio y una imagen o PDF. El archivo puede venir de camara, galeria o selector de archivos.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="hc-identity-status" aria-label="Estado de identificacion">
                                                <span class="hc-identity-pill<?= $configIdentityAnyVisible ? ' is-on' : ' is-muted' ?>" data-identity-status="record">
                                                    <i class="fas <?= $configIdentityAnyVisible ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                                    <?= $configIdentityAnyVisible ? 'Se pedira' : 'No se pedira' ?>
                                                </span>
                                                <span class="hc-identity-pill<?= $configIdentityAnyRequired ? ' is-on' : ' is-muted' ?>" data-identity-status="required">
                                                    <i class="fas <?= $configIdentityAnyRequired ? 'fa-circle-check' : 'fa-circle' ?>"></i>
                                                    <?= $configIdentityAnyRequired ? 'Con obligatorios' : 'Sin obligatorios' ?>
                                                </span>
                                                <span class="hc-identity-pill<?= $configIdentityFileVisible ? ' is-on' : ' is-muted' ?>" data-identity-status="file">
                                                    <i class="fas <?= $configIdentityFileVisible ? 'fa-file-arrow-up' : 'fa-file-circle-xmark' ?>"></i>
                                                    <?= $configIdentityFileVisible ? ($configIdentityFileRequired ? 'Archivo obligatorio' : 'Archivo opcional') : 'Archivo apagado' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="hc-identity-flow" aria-label="Flujo de captura">
                                            <div class="hc-identity-step">
                                                <i class="fas fa-list-check"></i>
                                                <strong>1. Pedir dato</strong>
                                                <span>Activa los campos que debe ver recepcion.</span>
                                            </div>
                                            <div class="hc-identity-step">
                                                <i class="fas fa-asterisk"></i>
                                                <strong>2. Hacer obligatorio</strong>
                                                <span>Marca lo que no puede faltar al registrar.</span>
                                            </div>
                                            <div class="hc-identity-step">
                                                <i class="fas fa-camera-retro"></i>
                                                <strong>3. Adjuntar archivo</strong>
                                                <span>JPG, PNG, WEBP o PDF hasta 10 MB.</span>
                                            </div>
                                        </div>

                                        <div class="hc-identity-fields">
                                            <?php foreach ($configIdentityFieldDefinitions as $guestFieldKey => $guestFieldDefinition): ?>
                                                <?= $configRenderGuestFieldPolicy($guestFieldKey, $guestFieldDefinition) ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </section>
                                <?php endif; ?>
                                <div class="hc-policy-list">
                                    <?php foreach ($configGuestFieldsByScope['guest'] as $guestFieldKey => $guestFieldDefinition): ?>
                                        <?php if (in_array($guestFieldKey, $configIdentityFieldKeys, true)) { continue; } ?>
                                        <?= $configRenderGuestFieldPolicy($guestFieldKey, $guestFieldDefinition) ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <section class="hc-policy-box" aria-label="Datos de vehiculos">
                                <div class="hc-group-head">
                                    <div>
                                        <h3 class="hc-group-title">
                                            <i class="fas fa-car-side"></i>
                                            Vehiculos registrados
                                        </h3>
                                        <p class="hc-group-hint">El hotel decide si captura marca, modelo, placas, color, tipo, cajon o notas del vehiculo.</p>
                                    </div>
                                </div>
                                <div class="hc-policy-list">
                                    <?php foreach ($configGuestFieldsByScope['vehicle'] as $guestFieldKey => $guestFieldDefinition): ?>
                                        <?= $configRenderGuestFieldPolicy($guestFieldKey, $guestFieldDefinition) ?>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section id="hc-rooms" class="hc-panel" data-hc-section aria-labelledby="hc-rooms-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Habitaciones</p>
                                <h2 id="hc-rooms-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-bed"></i></span>
                                    Catalogos de habitaciones
                                </h2>
                                <p class="hc-panel-copy">
                                    Opciones disponibles al crear o editar habitaciones. Los codigos se conservan como claves tecnicas.
                                </p>
                            </div>
                        </div>

                        <div class="hc-catalog-stack">
                            <?php $typeRowsForForm = $configAppendBlankRows($configRoomTypeRows, 2); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-door-open"></i>
                                            Tipos o categorias
                                        </h3>
                                        <p class="hc-field-hint">Codigos compatibles: sencilla, doble, triple, cuadruple, sencilla_manolo, doble_manolo, sencilla_jacuzzi, doble_jacuzzi.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="types">
                                        <i class="fas fa-plus"></i>
                                        Agregar tipo
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="types" data-next-index="<?= count($typeRowsForForm) ?>">
                                    <?php foreach ($typeRowsForForm as $index => $row): ?>
                                        <?php
                                        $typeCode = (string) ($row['codigo'] ?? '');
                                        $typeName = (string) ($row['nombre'] ?? '');
                                        $typeDescription = (string) ($row['descripcion'] ?? '');
                                        $typeCapacity = (string) ($row['capacidad_default'] ?? '');
                                        $typePrice = isset($row['precio_base_default']) && $row['precio_base_default'] !== ''
                                            ? number_format((float) $row['precio_base_default'], 2, '.', '')
                                            : '';
                                        $typeActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-type" data-catalog-row="types">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="room_catalog[types][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="20"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Nombre</label>
                                                <input type="text"
                                                       name="room_catalog[types][<?= $index ?>][nombre]"
                                                       value="<?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="50"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Capacidad</label>
                                                <input type="number"
                                                       name="room_catalog[types][<?= $index ?>][capacidad_default]"
                                                       value="<?= htmlspecialchars($typeCapacity, ENT_QUOTES, 'UTF-8') ?>"
                                                       min="1"
                                                       max="50"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Precio base</label>
                                                <input type="number"
                                                       name="room_catalog[types][<?= $index ?>][precio_base_default]"
                                                       value="<?= htmlspecialchars($typePrice, ENT_QUOTES, 'UTF-8') ?>"
                                                       min="0"
                                                       step="0.01"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Descripcion</label>
                                                <input type="text"
                                                       name="room_catalog[types][<?= $index ?>][descripcion]"
                                                       value="<?= htmlspecialchars($typeDescription, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="255"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="room_catalog[types][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="room_catalog[types][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $typeActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $floorRowsForForm = $configAppendBlankRows($configRoomFloorRows, 1); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-layer-group"></i>
                                            Pisos o niveles
                                        </h3>
                                        <p class="hc-field-hint">El numero se guarda en la habitacion. Usa negativos para sotanos y evita 0.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="floors">
                                        <i class="fas fa-plus"></i>
                                        Agregar piso
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="floors" data-next-index="<?= count($floorRowsForForm) ?>">
                                    <?php foreach ($floorRowsForForm as $index => $row): ?>
                                        <?php
                                        $floorValue = (string) ($row['valor'] ?? '');
                                        $floorLabel = (string) ($row['label'] ?? '');
                                        $floorActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-floor" data-catalog-row="floors">
                                            <div class="hc-catalog-cell">
                                                <label>Numero</label>
                                                <input type="number"
                                                       name="room_catalog[floors][<?= $index ?>][valor]"
                                                       value="<?= htmlspecialchars($floorValue, ENT_QUOTES, 'UTF-8') ?>"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Etiqueta</label>
                                                <input type="text"
                                                       name="room_catalog[floors][<?= $index ?>][label]"
                                                       value="<?= htmlspecialchars($floorLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="room_catalog[floors][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="room_catalog[floors][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $floorActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $amenityRowsForForm = $configAppendBlankRows($configRoomAmenityRows, 1); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-sparkles"></i>
                                            Amenidades especiales
                                        </h3>
                                        <p class="hc-field-hint">Estas opciones aparecen como checkboxes en crear y editar habitacion.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="amenities">
                                        <i class="fas fa-plus"></i>
                                        Agregar amenidad
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="amenities" data-next-index="<?= count($amenityRowsForForm) ?>">
                                    <?php foreach ($amenityRowsForForm as $index => $row): ?>
                                        <?php
                                        $amenityCode = (string) ($row['codigo'] ?? '');
                                        $amenityLabel = (string) ($row['label'] ?? '');
                                        $amenityActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-amenity" data-catalog-row="amenities">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="room_catalog[amenities][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($amenityCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="30"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Etiqueta</label>
                                                <input type="text"
                                                       name="room_catalog[amenities][<?= $index ?>][label]"
                                                       value="<?= htmlspecialchars($amenityLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="70"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="room_catalog[amenities][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="room_catalog[amenities][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $amenityActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section id="hc-owners" class="hc-panel" data-hc-section aria-labelledby="hc-owners-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Distribucion</p>
                                <h2 id="hc-owners-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-user-tie"></i></span>
                                    Propietarios por habitacion
                                </h2>
                                <p class="hc-panel-copy">
                                    Define dueños, reglas por tipo de habitacion y excepciones por habitacion para reportes y cortes.
                                </p>
                            </div>
                        </div>

                        <datalist id="owner-key-options">
                            <?php foreach ($configOwnerRows as $ownerRow): ?>
                                <?php
                                $ownerOptionKey = (string) ($ownerRow['key'] ?? '');
                                $ownerOptionName = (string) ($ownerRow['nombre'] ?? $ownerOptionKey);
                                ?>
                                <?php if ($ownerOptionKey !== ''): ?>
                                    <option value="<?= htmlspecialchars($ownerOptionKey, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($ownerOptionName, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </datalist>

                        <div class="hc-owner-preview" data-owner-preview>
                            <div class="hc-owner-summary-grid" aria-label="Vista previa de propietarios">
                                <article class="hc-owner-summary-card">
                                    <span><i class="fas fa-users"></i> Duenos activos</span>
                                    <strong data-owner-preview-active-count><?= count($configOwnerActiveRows) ?></strong>
                                    <small>Participan en cortes y reportes.</small>
                                </article>
                                <article class="hc-owner-summary-card">
                                    <span><i class="fas fa-star"></i> Predeterminado</span>
                                    <strong data-owner-preview-default>
                                        <?= htmlspecialchars($configOwnerDefaultName !== '' ? $configOwnerDefaultName : 'Sin definir', ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                    <small>Recibe ingresos sin coincidencia y remanentes.</small>
                                </article>
                                <article class="hc-owner-summary-card">
                                    <span><i class="fas fa-filter"></i> Reglas por tipo</span>
                                    <strong data-owner-preview-rule-count><?= count($configOwnerRuleRows) ?></strong>
                                    <small>Coincidencias por texto en tipo de habitacion.</small>
                                </article>
                                <article class="hc-owner-summary-card">
                                    <span><i class="fas fa-door-open"></i> Asignaciones</span>
                                    <strong data-owner-preview-assignment-count><?= count($configOwnerAssignmentRows) ?></strong>
                                    <small>Excepciones por numero, tipo exacto o ID.</small>
                                </article>
                            </div>

                            <section class="hc-owner-flow-card" aria-labelledby="hc-owner-flow-title">
                                <div class="hc-owner-flow-head">
                                    <div>
                                        <h3 id="hc-owner-flow-title">
                                            <i class="fas fa-route"></i>
                                            Vista previa del reparto
                                        </h3>
                                        <p>El porcentaje menor a 100 envia el remanente al dueno predeterminado.</p>
                                    </div>
                                    <span class="hc-owner-flow-pill" data-owner-preview-default-key>
                                        <?= htmlspecialchars($configOwnerDefault !== '' ? $configOwnerDefault : 'default', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <div class="hc-owner-flow-list" data-owner-preview-list>
                                    <?php if (empty($configOwnerActiveRows)): ?>
                                        <p class="hc-owner-flow-empty">No hay duenos activos configurados.</p>
                                    <?php else: ?>
                                        <?php foreach ($configOwnerActiveRows as $ownerRow): ?>
                                            <?php
                                            $previewOwnerKey = (string) ($ownerRow['key'] ?? '');
                                            $previewOwnerName = (string) ($ownerRow['nombre'] ?? $previewOwnerKey);
                                            $previewOwnerPct = $configOwnerFormatPercentage($ownerRow['participacion_pct'] ?? 100);
                                            $previewIsDefault = $previewOwnerKey !== '' && $previewOwnerKey === $configOwnerDefault;
                                            $previewNote = $previewIsDefault
                                                ? 'Dueno predeterminado: recibe remanentes y lo no clasificado.'
                                                : ((float) ($ownerRow['participacion_pct'] ?? 100) < 100
                                                    ? 'Recibe ' . $previewOwnerPct . '%. El resto va a ' . ($configOwnerDefaultName ?: $configOwnerDefault) . '.'
                                                    : 'Recibe el 100% de sus habitaciones asignadas.');
                                            ?>
                                            <article class="hc-owner-flow-row">
                                                <span class="hc-owner-flow-icon">
                                                    <i class="fas <?= $previewIsDefault ? 'fa-star' : 'fa-user-tie' ?>"></i>
                                                </span>
                                                <div>
                                                    <p class="hc-owner-flow-name">
                                                        <?= htmlspecialchars($previewOwnerName, ENT_QUOTES, 'UTF-8') ?>
                                                    </p>
                                                    <p class="hc-owner-flow-note">
                                                        <?= htmlspecialchars($previewNote, ENT_QUOTES, 'UTF-8') ?>
                                                    </p>
                                                </div>
                                                <span class="hc-owner-flow-pill">
                                                    <?= htmlspecialchars($previewOwnerPct, ENT_QUOTES, 'UTF-8') ?>%
                                                </span>
                                            </article>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>

                        <div class="hc-catalog-stack">
                            <?php $ownerRowsForForm = $configAppendBlankRows($configOwnerRows, 2); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-users-gear"></i>
                                            Dueños configurados
                                        </h3>
                                        <p class="hc-field-hint">La clave se usa en reglas y reportes. Ejemplo: elia, manolo, socio_1.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-owner-add="propietarios">
                                        <i class="fas fa-plus"></i>
                                        Agregar dueño
                                    </button>
                                </div>

                                <div class="hc-field-grid">
                                    <div class="hc-field">
                                        <label>Dueño predeterminado</label>
                                        <input type="text"
                                               name="owner_config[propietario_default]"
                                               value="<?= htmlspecialchars($configOwnerDefault, ENT_QUOTES, 'UTF-8') ?>"
                                               list="owner-key-options"
                                               maxlength="40"
                                               class="form-input"
                                               placeholder="elia">
                                        <p class="hc-field-hint">Se usa cuando ninguna regla o asignacion coincide.</p>
                                    </div>
                                </div>

                                <div class="hc-catalog-list" data-owner-list="propietarios" data-next-index="<?= count($ownerRowsForForm) ?>">
                                    <?php foreach ($ownerRowsForForm as $index => $row): ?>
                                        <?php
                                        $ownerKey = (string) ($row['key'] ?? '');
                                        $ownerName = (string) ($row['nombre'] ?? '');
                                        $ownerParticipation = array_key_exists('participacion_pct', $row)
                                            ? rtrim(rtrim(number_format((float) $row['participacion_pct'], 2, '.', ''), '0'), '.')
                                            : '';
                                        $ownerActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-owner" data-owner-row="propietarios">
                                            <div class="hc-catalog-cell">
                                                <label>Clave</label>
                                                <input type="text"
                                                       name="owner_config[propietarios][<?= $index ?>][key]"
                                                       value="<?= htmlspecialchars($ownerKey, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="40"
                                                       class="form-input"
                                                       placeholder="elia">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Nombre visible</label>
                                                <input type="text"
                                                       name="owner_config[propietarios][<?= $index ?>][nombre]"
                                                       value="<?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input"
                                                       placeholder="Elia">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Participacion %</label>
                                                <input type="number"
                                                       name="owner_config[propietarios][<?= $index ?>][participacion_pct]"
                                                       value="<?= htmlspecialchars($ownerParticipation, ENT_QUOTES, 'UTF-8') ?>"
                                                       min="0"
                                                       max="100"
                                                       step="0.01"
                                                       class="form-input"
                                                       placeholder="100">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="owner_config[propietarios][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="owner_config[propietarios][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $ownerActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $ownerRuleRowsForForm = $configAppendBlankRows($configOwnerRuleRows, 2); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-filter-circle-dollar"></i>
                                            Reglas por tipo
                                        </h3>
                                        <p class="hc-field-hint">Si el tipo de habitacion contiene el texto, el ingreso se asigna al dueño indicado.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-owner-add="reglas_tipo_contiene">
                                        <i class="fas fa-plus"></i>
                                        Agregar regla
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-owner-list="reglas_tipo_contiene" data-next-index="<?= count($ownerRuleRowsForForm) ?>">
                                    <?php foreach ($ownerRuleRowsForForm as $index => $row): ?>
                                        <?php
                                        $ruleText = (string) ($row['texto'] ?? '');
                                        $ruleOwner = (string) ($row['propietario_key'] ?? '');
                                        ?>
                                        <div class="hc-catalog-row is-owner-rule" data-owner-row="reglas_tipo_contiene">
                                            <div class="hc-catalog-cell">
                                                <label>Tipo contiene</label>
                                                <input type="text"
                                                       name="owner_config[reglas_tipo_contiene][<?= $index ?>][texto]"
                                                       value="<?= htmlspecialchars($ruleText, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="60"
                                                       class="form-input"
                                                       placeholder="manolo">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Propietario</label>
                                                <input type="text"
                                                       name="owner_config[reglas_tipo_contiene][<?= $index ?>][propietario_key]"
                                                       value="<?= htmlspecialchars($ruleOwner, ENT_QUOTES, 'UTF-8') ?>"
                                                       list="owner-key-options"
                                                       maxlength="40"
                                                       class="form-input"
                                                       placeholder="manolo">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $ownerAssignmentRowsForForm = $configAppendBlankRows($configOwnerAssignmentRows, 2); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-door-closed"></i>
                                            Asignaciones especificas
                                        </h3>
                                        <p class="hc-field-hint">Usa numero para una habitacion exacta o tipo exacto para una categoria completa.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-owner-add="habitaciones">
                                        <i class="fas fa-plus"></i>
                                        Agregar asignacion
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-owner-list="habitaciones" data-next-index="<?= count($ownerAssignmentRowsForForm) ?>">
                                    <?php foreach ($ownerAssignmentRowsForForm as $index => $row): ?>
                                        <?php
                                        $assignmentNumber = (string) ($row['numero'] ?? '');
                                        $assignmentType = (string) ($row['tipo'] ?? '');
                                        $assignmentId = (string) ($row['habitacion_id'] ?? '');
                                        $assignmentOwner = (string) ($row['propietario_key'] ?? '');
                                        ?>
                                        <div class="hc-catalog-row is-owner-room" data-owner-row="habitaciones">
                                            <div class="hc-catalog-cell">
                                                <label>No. habitacion</label>
                                                <input type="text"
                                                       name="owner_config[habitaciones][<?= $index ?>][numero]"
                                                       value="<?= htmlspecialchars($assignmentNumber, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="30"
                                                       class="form-input"
                                                       placeholder="101">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Tipo exacto</label>
                                                <input type="text"
                                                       name="owner_config[habitaciones][<?= $index ?>][tipo]"
                                                       value="<?= htmlspecialchars($assignmentType, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input"
                                                       placeholder="habitacion manolo">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>ID opcional</label>
                                                <input type="number"
                                                       name="owner_config[habitaciones][<?= $index ?>][habitacion_id]"
                                                       value="<?= htmlspecialchars($assignmentId, ENT_QUOTES, 'UTF-8') ?>"
                                                       min="1"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Propietario</label>
                                                <input type="text"
                                                       name="owner_config[habitaciones][<?= $index ?>][propietario_key]"
                                                       value="<?= htmlspecialchars($assignmentOwner, ENT_QUOTES, 'UTF-8') ?>"
                                                       list="owner-key-options"
                                                       maxlength="40"
                                                       class="form-input"
                                                       placeholder="elia">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section id="hc-catalogs" class="hc-panel" data-hc-section aria-labelledby="hc-catalogs-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Listas auxiliares</p>
                                <h2 id="hc-catalogs-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-layer-group"></i></span>
                                    Catalogos generales
                                </h2>
                                <p class="hc-panel-copy">
                                    Listas auxiliares del hotel. En esta fase no modifican caja, reservas ni huespedes.
                                </p>
                            </div>
                        </div>

                        <div class="hc-catalog-stack">
                            <?php $zoneRowsForForm = $configAppendBlankRows($configGeneralZoneRows, 1); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-location-dot"></i>
                                            Zonas o areas
                                        </h3>
                                        <p class="hc-field-hint">Areas internas para clasificar ubicaciones futuras sin afectar operacion actual.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="zones">
                                        <i class="fas fa-plus"></i>
                                        Agregar zona
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="zones" data-next-index="<?= count($zoneRowsForForm) ?>">
                                    <?php foreach ($zoneRowsForForm as $index => $row): ?>
                                        <?php
                                        $zoneCode = (string) ($row['codigo'] ?? '');
                                        $zoneLabel = (string) ($row['label'] ?? '');
                                        $zoneActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-simple" data-catalog-row="zones">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="general_catalog[zones][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($zoneCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="30"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Etiqueta</label>
                                                <input type="text"
                                                       name="general_catalog[zones][<?= $index ?>][label]"
                                                       value="<?= htmlspecialchars($zoneLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="general_catalog[zones][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="general_catalog[zones][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $zoneActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $parkingRowsForForm = $configAppendBlankRows($configGeneralParkingRows, 1); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-square-parking"></i>
                                            Estacionamientos
                                        </h3>
                                        <p class="hc-field-hint">Define el nombre visible y el cupo que alimentara el indicador de estacionamiento del dashboard.</p>
                                    </div>
                                    <div class="hc-catalog-actions">
                                        <span class="hc-catalog-metric" title="Suma de cupos activos" data-parking-capacity-total>
                                            <i class="fas fa-car-side" aria-hidden="true"></i>
                                            <b data-parking-capacity-value><?= (int) $configParkingCapacityTotal ?></b>
                                            cupos activos
                                        </span>
                                        <button type="button" class="hc-add-btn" data-catalog-add="parkings">
                                            <i class="fas fa-plus"></i>
                                            Agregar estacionamiento
                                        </button>
                                    </div>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="parkings" data-next-index="<?= count($parkingRowsForForm) ?>">
                                    <?php foreach ($parkingRowsForForm as $index => $row): ?>
                                        <?php
                                        $parkingCode = (string) ($row['codigo'] ?? '');
                                        $parkingLabel = (string) ($row['label'] ?? '');
                                        $parkingCapacity = array_key_exists('cupo', $row) ? max(0, min(999, (int) $row['cupo'])) : '';
                                        $parkingActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-parking" data-catalog-row="parkings">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="general_catalog[parkings][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="20"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Etiqueta</label>
                                                <input type="text"
                                                       name="general_catalog[parkings][<?= $index ?>][label]"
                                                       value="<?= htmlspecialchars($parkingLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Cupo</label>
                                                <input type="number"
                                                       name="general_catalog[parkings][<?= $index ?>][cupo]"
                                                       value="<?= $parkingCapacity === '' ? '' : (int) $parkingCapacity ?>"
                                                       min="0"
                                                       max="999"
                                                       step="1"
                                                       inputmode="numeric"
                                                       class="form-input"
                                                       placeholder="0">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="general_catalog[parkings][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="general_catalog[parkings][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $parkingActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>

                            <?php $unitRowsForForm = $configAppendBlankRows($configGeneralUnitRows, 1); ?>
                            <section class="hc-catalog-box">
                                <div class="hc-catalog-head">
                                    <div>
                                        <h3 class="hc-catalog-title">
                                            <i class="fas fa-ruler-combined"></i>
                                            Unidades de medida
                                        </h3>
                                        <p class="hc-field-hint">Estas opciones alimentan el selector de unidad al crear o editar productos.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="units">
                                        <i class="fas fa-plus"></i>
                                        Agregar unidad
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="units" data-next-index="<?= count($unitRowsForForm) ?>">
                                    <?php foreach ($unitRowsForForm as $index => $row): ?>
                                        <?php
                                        $unitCode = (string) ($row['codigo'] ?? '');
                                        $unitLabel = (string) ($row['label'] ?? '');
                                        $unitAbbreviation = (string) ($row['abreviatura'] ?? '');
                                        $unitActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-unit" data-catalog-row="units">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="general_catalog[units][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($unitCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="20"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Etiqueta</label>
                                                <input type="text"
                                                       name="general_catalog[units][<?= $index ?>][label]"
                                                       value="<?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="80"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Abrev.</label>
                                                <input type="text"
                                                       name="general_catalog[units][<?= $index ?>][abreviatura]"
                                                       value="<?= htmlspecialchars($unitAbbreviation, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="12"
                                                       class="form-input">
                                            </div>
                                            <div class="hc-catalog-cell">
                                                <label>Activo</label>
                                                <input type="hidden" name="general_catalog[units][<?= $index ?>][activo]" value="0">
                                                <label class="hc-switch">
                                                    <input type="checkbox"
                                                           name="general_catalog[units][<?= $index ?>][activo]"
                                                           value="1"
                                                           <?= $unitActive ? 'checked' : '' ?>>
                                                    <span class="hc-switch-ui" aria-hidden="true"></span>
                                                    <span class="hc-switch-text"><strong>Si</strong></span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        </div>
                    </section>

                    <section id="hc-brand" class="hc-panel" data-hc-section aria-labelledby="hc-brand-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Marca del hotel</p>
                                <h2 id="hc-brand-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-palette"></i></span>
                                    Identidad visual
                                </h2>
                                <p class="hc-panel-copy">
                                    Logo, colores, estilo de menu, login e iconos PWA del hotel cliente.
                                </p>
                            </div>
                            <span class="hc-badge">
                                <i class="fas fa-shield-alt"></i>
                                Marca activa
                            </span>
                        </div>

                        <?php
                        $brandingLogoPreview = $configBrandingAsset('logo_url') ?: $configHotelLogo;
                        $brandingFaviconPreview = $configBrandingAsset('favicon_url');
                        $brandingLoginPreview = $configBrandingAsset('login_background_url');
                        $brandingPwa192Preview = $configBrandingPwaAsset('pwa_icon_192_url', 192);
                        $brandingPwa512Preview = $configBrandingPwaAsset('pwa_icon_512_url', 512);
                        $brandingPrimary = function_exists('hotel_branding_hex')
                            ? hotel_branding_hex($configBranding['color_primary'] ?? null, '#1B2746')
                            : ($configBranding['color_primary'] ?? '#1B2746');
                        $brandingSecondary = function_exists('hotel_branding_hex')
                            ? hotel_branding_hex($configBranding['color_secondary'] ?? null, '#0F172A')
                            : ($configBranding['color_secondary'] ?? '#0F172A');
                        $brandingAccent = function_exists('hotel_branding_hex')
                            ? hotel_branding_hex($configBranding['color_accent'] ?? null, '#BD9441')
                            : ($configBranding['color_accent'] ?? '#BD9441');
                        ?>

                        <input type="hidden" name="hotel_branding[logo_url]" value="<?= $configBrandingField('logo_url') ?>">
                        <input type="hidden" name="hotel_branding[favicon_url]" value="<?= $configBrandingField('favicon_url') ?>">
                        <input type="hidden" name="hotel_branding[login_background_url]" value="<?= $configBrandingField('login_background_url') ?>">
                        <input type="hidden" name="hotel_branding[pwa_icon_192_url]" value="<?= $configBrandingField('pwa_icon_192_url') ?>">
                        <input type="hidden" name="hotel_branding[pwa_icon_512_url]" value="<?= $configBrandingField('pwa_icon_512_url') ?>">
                        <input type="hidden" name="hotel_branding[activo]" value="1">

                        <div class="hc-brand-grid">
                            <aside class="hc-brand-preview"
                                   aria-label="Vista previa de marca"
                                   data-brand-preview
                                   data-default-name="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>"
                                   data-sidebar-style="<?= htmlspecialchars((string) ($configBranding['sidebar_style'] ?? 'default'), ENT_QUOTES, 'UTF-8') ?>"
                                   data-login-style="<?= htmlspecialchars((string) ($configBranding['login_style'] ?? 'default'), ENT_QUOTES, 'UTF-8') ?>"
                                   style="--preview-primary: <?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?>; --preview-secondary: <?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?>; --preview-accent: <?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?>;">
                                <div class="hc-preview-head">
                                    <div>
                                        <span class="hc-preview-kicker">Vista previa del sistema</span>
                                        <strong class="hc-preview-title" data-brand-preview-text="name">
                                            <?= htmlspecialchars($configLegacyNombre, ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                    </div>
                                    <div class="hc-preview-actions">
                                        <span class="hc-preview-live" data-brand-dirty-label aria-live="polite">Sin cambios</span>
                                        <button type="button" class="hc-preview-reset" data-brand-reset disabled>Restablecer</button>
                                    </div>
                                </div>

                                <div class="hc-preview-system" aria-hidden="true">
                                    <div class="hc-preview-sidebar">
                                        <div class="hc-preview-brandline">
                                            <span class="hc-preview-mark">
                                                <img <?php if ($brandingLogoPreview): ?>src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                     alt=""
                                                     data-brand-image="logo"
                                                     data-original-src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                     <?= $brandingLogoPreview ? '' : 'hidden' ?>>
                                                <i class="fas fa-hotel hc-preview-empty-icon" data-brand-fallback="logo" <?= $brandingLogoPreview ? 'hidden' : '' ?>></i>
                                            </span>
                                            <span class="hc-preview-sidebar-name" data-brand-preview-text="name">
                                                <?= htmlspecialchars($configLegacyNombre, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>

                                        <div class="hc-preview-nav">
                                            <span class="hc-preview-nav-item is-active"><i class="fas fa-compass"></i><span>Dashboard</span></span>
                                            <span class="hc-preview-nav-item"><i class="fas fa-bed"></i><span>Habitaciones</span></span>
                                            <span class="hc-preview-nav-item"><i class="fas fa-wallet"></i><span>Caja</span></span>
                                        </div>

                                        <span class="hc-preview-status"><span></span>Sesion activa</span>
                                    </div>

                                    <div class="hc-preview-workspace">
                                        <div class="hc-preview-topbar">
                                            <div>
                                                <span class="hc-preview-screen-title">Operacion hotelera</span>
                                                <span class="hc-preview-screen-subtitle" data-brand-preview-text="sidebar-style">Menu default</span>
                                            </div>
                                            <span class="hc-preview-chip">Recepcion</span>
                                        </div>

                                        <div class="hc-preview-content">
                                            <div class="hc-preview-metric-row">
                                                <span class="hc-preview-metric"><strong>18</strong><span>Disponibles</span></span>
                                                <span class="hc-preview-metric"><strong>7</strong><span>Ocupadas</span></span>
                                                <span class="hc-preview-metric"><strong>3</strong><span>Llegadas</span></span>
                                            </div>
                                            <div class="hc-preview-room">
                                                <div>
                                                    <span class="hc-preview-room-title">Habitacion 204</span>
                                                    <span class="hc-preview-room-meta">Suite doble, lista para check-in</span>
                                                </div>
                                                <span class="hc-preview-room-cta">Asignar</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="hc-preview-login-card" aria-hidden="true">
                                    <div class="hc-preview-login-bg">
                                        <img <?php if ($brandingLoginPreview): ?>src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                             alt=""
                                             data-brand-image="login"
                                             data-original-src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>"
                                             <?= $brandingLoginPreview ? '' : 'hidden' ?>>
                                    </div>
                                    <div class="hc-preview-login-brand">
                                        <span class="hc-preview-login-mark">
                                            <img <?php if ($brandingLogoPreview): ?>src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt=""
                                                 data-brand-image="logo"
                                                 data-original-src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingLogoPreview ? '' : 'hidden' ?>>
                                            <i class="fas fa-hotel hc-preview-empty-icon" data-brand-fallback="logo" <?= $brandingLogoPreview ? 'hidden' : '' ?>></i>
                                        </span>
                                        <div>
                                            <strong class="hc-preview-login-title" data-brand-preview-text="name">
                                                <?= htmlspecialchars($configLegacyNombre, ENT_QUOTES, 'UTF-8') ?>
                                            </strong>
                                            <span class="hc-preview-login-copy" data-brand-preview-text="login-style">Login default</span>
                                        </div>
                                    </div>
                                    <div class="hc-preview-login-panel">
                                        <span class="hc-preview-login-line"></span>
                                        <span class="hc-preview-login-line"></span>
                                        <span class="hc-preview-login-button"></span>
                                    </div>
                                </div>

                                <div class="hc-preview-pwa" aria-hidden="true">
                                    <div>
                                        <span class="hc-preview-screen-title">Iconos y acceso movil</span>
                                        <span class="hc-preview-screen-subtitle">Favicon, PWA 192 y PWA 512</span>
                                    </div>
                                    <div class="hc-preview-app-icons">
                                        <span class="hc-preview-app-icon">
                                            <img <?php if ($brandingFaviconPreview): ?>src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt=""
                                                 data-brand-image="favicon"
                                                 data-original-src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingFaviconPreview ? '' : 'hidden' ?>>
                                            <i class="fas fa-star hc-preview-empty-icon" data-brand-fallback="favicon" <?= $brandingFaviconPreview ? 'hidden' : '' ?>></i>
                                        </span>
                                        <span class="hc-preview-app-icon">
                                            <img <?php if ($brandingPwa192Preview): ?>src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt=""
                                                 data-brand-image="pwa192"
                                                 data-original-src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingPwa192Preview ? '' : 'hidden' ?>>
                                            <i class="fas fa-mobile-alt hc-preview-empty-icon" data-brand-fallback="pwa192" <?= $brandingPwa192Preview ? 'hidden' : '' ?>></i>
                                        </span>
                                        <span class="hc-preview-app-icon is-large">
                                            <img <?php if ($brandingPwa512Preview): ?>src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt=""
                                                 data-brand-image="pwa512"
                                                 data-original-src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingPwa512Preview ? '' : 'hidden' ?>>
                                            <i class="fas fa-mobile-alt hc-preview-empty-icon" data-brand-fallback="pwa512" <?= $brandingPwa512Preview ? 'hidden' : '' ?>></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="hc-preview-palette" aria-label="Colores de vista previa">
                                    <span class="hc-preview-swatch">
                                        <span class="hc-preview-swatch-dot" data-brand-swatch="primary" style="background: <?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?>"></span>
                                        <span><strong>Principal</strong><code data-brand-preview-code="primary"><?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></code></span>
                                    </span>
                                    <span class="hc-preview-swatch">
                                        <span class="hc-preview-swatch-dot" data-brand-swatch="secondary" style="background: <?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?>"></span>
                                        <span><strong>Secundario</strong><code data-brand-preview-code="secondary"><?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?></code></span>
                                    </span>
                                    <span class="hc-preview-swatch">
                                        <span class="hc-preview-swatch-dot" data-brand-swatch="accent" style="background: <?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?>"></span>
                                        <span><strong>Acento</strong><code data-brand-preview-code="accent"><?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></code></span>
                                    </span>
                                </div>

                                <div class="hc-preview-contrast" data-brand-contrast aria-label="Revision de contraste de la vista previa">
                                    <div class="hc-preview-contrast-head">
                                        <span class="hc-preview-contrast-title">Legibilidad</span>
                                        <span class="hc-preview-contrast-state" data-brand-contrast-summary>AA listo</span>
                                    </div>
                                    <div class="hc-preview-contrast-grid">
                                        <span class="hc-preview-contrast-item" data-brand-contrast-item="primary">
                                            <span>Principal</span>
                                            <strong data-brand-contrast-score="primary">AA</strong>
                                        </span>
                                        <span class="hc-preview-contrast-item" data-brand-contrast-item="secondary">
                                            <span>Secundario</span>
                                            <strong data-brand-contrast-score="secondary">AA</strong>
                                        </span>
                                        <span class="hc-preview-contrast-item" data-brand-contrast-item="accent">
                                            <span>Acento</span>
                                            <strong data-brand-contrast-score="accent">AA</strong>
                                        </span>
                                    </div>
                                </div>
                            </aside>

                            <div class="hc-field-grid">
                                <div class="hc-field is-wide">
                                    <label for="branding_nombre_visual">Nombre visual</label>
                                     <input type="text"
                                           id="branding_nombre_visual"
                                           name="hotel_branding[nombre_visual]"
                                           value="<?= $configBrandingField('nombre_visual', $configHotelNombre) ?>"
                                           maxlength="150"
                                           data-brand-input="name"
                                           class="form-input">
                                    <p class="hc-field-hint">Nombre mostrado en login, encabezado y documentos compatibles.</p>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_primary">Color principal</label>
                                    <div class="hc-color-input">
                                         <input type="color"
                                               id="branding_color_primary"
                                               name="hotel_branding[color_primary]"
                                               data-brand-input="primary"
                                               value="<?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code" data-brand-preview-code="primary"><?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_secondary">Color secundario</label>
                                    <div class="hc-color-input">
                                         <input type="color"
                                               id="branding_color_secondary"
                                               name="hotel_branding[color_secondary]"
                                               data-brand-input="secondary"
                                               value="<?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code" data-brand-preview-code="secondary"><?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_accent">Color acento</label>
                                    <div class="hc-color-input">
                                         <input type="color"
                                               id="branding_color_accent"
                                               name="hotel_branding[color_accent]"
                                               data-brand-input="accent"
                                               value="<?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code" data-brand-preview-code="accent"><?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                 <div class="hc-field">
                                     <label for="branding_sidebar_style">Estilo de menu</label>
                                     <select id="branding_sidebar_style"
                                             name="hotel_branding[sidebar_style]"
                                             data-brand-input="sidebar"
                                             class="form-input">
                                        <option value="default" <?= ($configBranding['sidebar_style'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                        <option value="solid" <?= ($configBranding['sidebar_style'] ?? 'default') === 'solid' ? 'selected' : '' ?>>Solido</option>
                                        <option value="dark" <?= ($configBranding['sidebar_style'] ?? 'default') === 'dark' ? 'selected' : '' ?>>Oscuro</option>
                                    </select>
                                </div>

                                 <div class="hc-field">
                                     <label for="branding_login_style">Estilo de login</label>
                                     <select id="branding_login_style"
                                             name="hotel_branding[login_style]"
                                             data-brand-input="login"
                                             class="form-input">
                                        <option value="default" <?= ($configBranding['login_style'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                        <option value="soft" <?= ($configBranding['login_style'] ?? 'default') === 'soft' ? 'selected' : '' ?>>Suave</option>
                                        <option value="image" <?= ($configBranding['login_style'] ?? 'default') === 'image' ? 'selected' : '' ?>>Imagen</option>
                                    </select>
                                </div>

                                 <div class="hc-field">
                                     <label for="logo_file">Logo</label>
                                     <div class="hc-upload-row">
                                         <div class="hc-upload-thumb">
                                            <img <?php if ($brandingLogoPreview): ?>src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt="Logo"
                                                 class="hc-preview-upload-img"
                                                 data-brand-image="logo"
                                                 data-original-src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingLogoPreview ? '' : 'hidden' ?>>
                                            <i class="fas fa-image hc-preview-empty-icon" data-brand-fallback="logo" <?= $brandingLogoPreview ? 'hidden' : '' ?>></i>
                                         </div>
                                        <div class="hc-upload-control">
                                            <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input" data-brand-file="logo">
                                            <p class="hc-upload-status"
                                               data-brand-file-status="logo"
                                               data-default-status="<?= $brandingLogoPreview ? 'Logo actual cargado' : 'Sin logo cargado' ?>">
                                                <?= $brandingLogoPreview ? 'Logo actual cargado' : 'Sin logo cargado' ?>
                                            </p>
                                        </div>
                                     </div>
                                 </div>

                                 <div class="hc-field">
                                     <label for="favicon_file">Favicon</label>
                                     <div class="hc-upload-row">
                                         <div class="hc-upload-thumb">
                                            <img <?php if ($brandingFaviconPreview): ?>src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt="Favicon"
                                                 class="hc-preview-upload-img"
                                                 data-brand-image="favicon"
                                                 data-original-src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingFaviconPreview ? '' : 'hidden' ?>>
                                            <i class="fas fa-star hc-preview-empty-icon" data-brand-fallback="favicon" <?= $brandingFaviconPreview ? 'hidden' : '' ?>></i>
                                         </div>
                                        <div class="hc-upload-control">
                                            <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,image/x-icon,image/png" class="form-input" data-brand-file="favicon">
                                            <p class="hc-upload-status"
                                               data-brand-file-status="favicon"
                                               data-default-status="<?= $brandingFaviconPreview ? 'Favicon actual cargado' : 'Sin favicon cargado' ?>">
                                                <?= $brandingFaviconPreview ? 'Favicon actual cargado' : 'Sin favicon cargado' ?>
                                            </p>
                                        </div>
                                     </div>
                                 </div>

                                 <div class="hc-field is-wide">
                                     <label for="login_background_file">Fondo de login</label>
                                     <div class="hc-upload-row">
                                         <div class="hc-upload-thumb">
                                            <img <?php if ($brandingLoginPreview): ?>src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt="Fondo login"
                                                 class="hc-preview-upload-img"
                                                 data-brand-image="login"
                                                 data-original-src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingLoginPreview ? '' : 'hidden' ?>>
                                            <i class="fas fa-image hc-preview-empty-icon" data-brand-fallback="login" <?= $brandingLoginPreview ? 'hidden' : '' ?>></i>
                                         </div>
                                        <div class="hc-upload-control">
                                            <input type="file" id="login_background_file" name="login_background_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input" data-brand-file="login">
                                            <p class="hc-upload-status"
                                               data-brand-file-status="login"
                                               data-default-status="<?= $brandingLoginPreview ? 'Fondo actual cargado' : 'Sin fondo cargado' ?>">
                                                <?= $brandingLoginPreview ? 'Fondo actual cargado' : 'Sin fondo cargado' ?>
                                            </p>
                                        </div>
                                     </div>
                                 </div>

                                 <div class="hc-field">
                                     <label for="pwa_icon_192_file">Icono PWA 192</label>
                                     <div class="hc-upload-row">
                                         <div class="hc-upload-thumb">
                                            <img <?php if ($brandingPwa192Preview): ?>src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt="PWA 192"
                                                 class="hc-preview-upload-img"
                                                 data-brand-image="pwa192"
                                                 data-original-src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingPwa192Preview ? '' : 'hidden' ?>>
                                            <i class="fas fa-mobile-alt hc-preview-empty-icon" data-brand-fallback="pwa192" <?= $brandingPwa192Preview ? 'hidden' : '' ?>></i>
                                         </div>
                                        <div class="hc-upload-control">
                                            <input type="file" id="pwa_icon_192_file" name="pwa_icon_192_file" accept=".png,.webp,image/png,image/webp" class="form-input" data-brand-file="pwa192">
                                            <p class="hc-upload-status"
                                               data-brand-file-status="pwa192"
                                               data-default-status="<?= $brandingPwa192Preview ? 'Icono 192 actual cargado' : 'Sin icono 192 cargado' ?>">
                                                <?= $brandingPwa192Preview ? 'Icono 192 actual cargado' : 'Sin icono 192 cargado' ?>
                                            </p>
                                        </div>
                                     </div>
                                     <p class="hc-field-hint">Debe medir exactamente 192x192 px.</p>
                                 </div>

                                 <div class="hc-field">
                                     <label for="pwa_icon_512_file">Icono PWA 512</label>
                                     <div class="hc-upload-row">
                                         <div class="hc-upload-thumb">
                                            <img <?php if ($brandingPwa512Preview): ?>src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
                                                 alt="PWA 512"
                                                 class="hc-preview-upload-img"
                                                 data-brand-image="pwa512"
                                                 data-original-src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>"
                                                 <?= $brandingPwa512Preview ? '' : 'hidden' ?>>
                                            <i class="fas fa-mobile-alt hc-preview-empty-icon" data-brand-fallback="pwa512" <?= $brandingPwa512Preview ? 'hidden' : '' ?>></i>
                                         </div>
                                        <div class="hc-upload-control">
                                            <input type="file" id="pwa_icon_512_file" name="pwa_icon_512_file" accept=".png,.webp,image/png,image/webp" class="form-input" data-brand-file="pwa512">
                                            <p class="hc-upload-status"
                                               data-brand-file-status="pwa512"
                                               data-default-status="<?= $brandingPwa512Preview ? 'Icono 512 actual cargado' : 'Sin icono 512 cargado' ?>">
                                                <?= $brandingPwa512Preview ? 'Icono 512 actual cargado' : 'Sin icono 512 cargado' ?>
                                            </p>
                                        </div>
                                     </div>
                                     <p class="hc-field-hint">Debe medir exactamente 512x512 px.</p>
                                 </div>
                            </div>
                        </div>
                    </section>

                    <?php if (!empty($configFooterNavCatalog)): ?>
                    <section id="hc-footer-nav" class="hc-panel" data-hc-section aria-labelledby="hc-footer-nav-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">App del hotel</p>
                                <h2 id="hc-footer-nav-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-grip"></i></span>
                                    Barra inferior de accesos rápidos
                                </h2>
                                <p class="hc-panel-copy">
                                    En el teléfono, tu equipo verá una barra fija con los atajos que elijas aquí.
                                    Selecciona de <?= (int) $configFooterNavMin ?> a <?= (int) $configFooterNavMax ?> accesos; el botón <strong>Menú</strong> siempre está presente
                                    y el orden en que los actives es el orden en la barra.
                                </p>
                            </div>
                            <span class="hc-badge">
                                <i class="fas fa-mobile-screen-button"></i>
                                Solo móvil
                            </span>
                        </div>

                        <div class="hc-fnav-layout">
                            <div class="hc-fnav-preview-wrap" aria-hidden="true">
                                <p class="hc-fnav-preview-caption">Vista previa</p>
                                <div class="hc-fnav-phone">
                                    <div class="hc-fnav-screen"></div>
                                    <div class="hc-fnav-bar" data-fnav-preview>
                                        <!-- slots generados por JS -->
                                    </div>
                                </div>
                            </div>

                            <div class="hc-fnav-picker">
                                <div class="hc-fnav-counter" role="status">
                                    <strong data-fnav-count><?= count($configFooterNavSelected) ?></strong>
                                    de <?= (int) $configFooterNavMax ?> atajos seleccionados
                                    <span class="hc-fnav-counter-hint" data-fnav-hint></span>
                                </div>

                                <input type="hidden" name="footer_nav_submitted" value="1">
                                <div data-fnav-inputs>
                                    <?php foreach ($configFooterNavSelected as $navKey): ?>
                                    <input type="hidden" name="footer_nav[]" value="<?= htmlspecialchars($navKey, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php endforeach; ?>
                                </div>

                                <div class="hc-fnav-grid">
                                    <?php foreach ($configFooterNavCatalog as $navKey => $navItem): ?>
                                    <?php $navOrder = array_search($navKey, $configFooterNavSelected, true); ?>
                                    <button type="button"
                                            class="hc-fnav-option<?= $navOrder !== false ? ' is-selected' : '' ?>"
                                            data-fnav-option="<?= htmlspecialchars($navKey, ENT_QUOTES, 'UTF-8') ?>"
                                            data-fnav-icon="<?= htmlspecialchars($navItem['icon'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-fnav-short="<?= htmlspecialchars($navItem['short'], ENT_QUOTES, 'UTF-8') ?>"
                                            aria-pressed="<?= $navOrder !== false ? 'true' : 'false' ?>">
                                        <span class="hc-fnav-option-icon"><i class="fas <?= htmlspecialchars($navItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                                        <span class="hc-fnav-option-copy">
                                            <strong><?= htmlspecialchars($navItem['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small><?= htmlspecialchars($navItem['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                                        </span>
                                        <span class="hc-fnav-option-order" data-fnav-order><?= $navOrder !== false ? (int) $navOrder + 1 : '' ?></span>
                                    </button>
                                    <?php endforeach; ?>
                                </div>

                                <p class="hc-field-hint">
                                    Solo se listan los módulos activos de tu hotel. Si un módulo se desactiva después,
                                    su atajo desaparece de la barra automáticamente.
                                </p>
                            </div>
                        </div>
                    </section>

                    <style>
                    .hc-fnav-layout {
                        display: grid;
                        grid-template-columns: 300px minmax(0, 1fr);
                        gap: 26px;
                        align-items: start;
                    }

                    @media (max-width: 900px) {
                        .hc-fnav-layout {
                            grid-template-columns: 1fr;
                        }
                    }

                    .hc-fnav-preview-wrap {
                        position: sticky;
                        top: 18px;
                    }

                    .hc-fnav-preview-caption {
                        margin: 0 0 10px;
                        font-size: .72rem;
                        font-weight: 700;
                        letter-spacing: .08em;
                        text-transform: uppercase;
                        color: var(--hc-ink-faint);
                    }

                    .hc-fnav-phone {
                        border: 1px solid var(--hc-line-strong);
                        border-radius: 26px;
                        overflow: hidden;
                        background: linear-gradient(180deg, color-mix(in srgb, var(--hc-section-accent) 5%, var(--hc-paper, #FFF)), color-mix(in srgb, var(--hc-section-accent) 10%, #f6f4ef));
                        box-shadow: var(--hc-shadow);
                    }

                    .hc-fnav-screen {
                        height: 132px;
                        background:
                            linear-gradient(180deg, color-mix(in srgb, var(--hc-brand) 8%, transparent), transparent 42px),
                            repeating-linear-gradient(180deg, color-mix(in srgb, var(--hc-brand) 6%, transparent) 0 10px, transparent 10px 34px);
                        opacity: .5;
                        margin: 14px 14px 0;
                        border-radius: 12px 12px 0 0;
                    }

                    .hc-fnav-bar {
                        display: grid;
                        grid-auto-flow: column;
                        grid-auto-columns: 1fr;
                        gap: 2px;
                        padding: 8px 6px 12px;
                        background: var(--hc-paper, #FFFEFB);
                        border-top: 1px solid var(--hc-line);
                    }

                    .hc-fnav-slot {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 4px;
                        min-width: 0;
                        color: var(--hc-ink-soft);
                    }

                    .hc-fnav-slot i {
                        display: grid;
                        place-items: center;
                        width: 34px;
                        height: 22px;
                        border-radius: 999px;
                        font-size: .82rem;
                    }

                    .hc-fnav-slot span {
                        max-width: 100%;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        font-size: .54rem;
                        font-weight: 650;
                    }

                    .hc-fnav-slot.is-first {
                        color: var(--hc-brand-strong);
                    }

                    .hc-fnav-slot.is-first i {
                        background: color-mix(in srgb, var(--hc-brand) 12%, var(--hc-paper, #FFF));
                    }

                    .hc-fnav-slot.is-menu {
                        color: var(--hc-ink-faint);
                    }

                    .hc-fnav-slot.is-empty {
                        opacity: .35;
                    }

                    .hc-fnav-slot.is-empty i {
                        border: 1px dashed var(--hc-line-strong);
                    }

                    .hc-fnav-counter {
                        display: flex;
                        align-items: baseline;
                        gap: 6px;
                        flex-wrap: wrap;
                        margin-bottom: 14px;
                        font-size: .84rem;
                        color: var(--hc-ink-soft);
                    }

                    .hc-fnav-counter strong {
                        font-size: 1.05rem;
                        color: var(--hc-brand-strong);
                    }

                    .hc-fnav-counter-hint {
                        font-size: .76rem;
                        color: var(--hc-danger);
                    }

                    .hc-fnav-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                        gap: 10px;
                    }

                    .hc-fnav-option {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        padding: 11px 12px;
                        border: 1px solid var(--hc-line);
                        border-radius: 14px;
                        background: var(--hc-surface);
                        text-align: left;
                        font-family: inherit;
                        cursor: pointer;
                        transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
                    }

                    .hc-fnav-option:hover {
                        border-color: color-mix(in srgb, var(--hc-section-accent) 34%, var(--hc-line));
                    }

                    .hc-fnav-option:focus-visible {
                        outline: 2px solid var(--hc-focus);
                        outline-offset: 2px;
                    }

                    .hc-fnav-option-icon {
                        display: grid;
                        place-items: center;
                        flex: 0 0 auto;
                        width: 38px;
                        height: 38px;
                        border-radius: 11px;
                        font-size: .95rem;
                        color: color-mix(in srgb, var(--hc-section-accent) 70%, var(--hc-ink-mix, #334155));
                        background: color-mix(in srgb, var(--hc-section-accent) 10%, var(--hc-paper, #FFF));
                        border: 1px solid color-mix(in srgb, var(--hc-section-accent) 16%, var(--hc-line));
                    }

                    .hc-fnav-option-copy {
                        min-width: 0;
                        flex: 1;
                    }

                    .hc-fnav-option-copy strong {
                        display: block;
                        font-size: .84rem;
                        font-weight: 700;
                        color: var(--hc-ink);
                    }

                    .hc-fnav-option-copy small {
                        display: block;
                        margin-top: 2px;
                        font-size: .72rem;
                        line-height: 1.3;
                        color: var(--hc-ink-faint);
                    }

                    .hc-fnav-option-order {
                        display: grid;
                        place-items: center;
                        flex: 0 0 auto;
                        width: 24px;
                        height: 24px;
                        border-radius: 999px;
                        border: 1px dashed var(--hc-line-strong);
                        font-size: .72rem;
                        font-weight: 700;
                        color: transparent;
                        transition: all .16s ease;
                    }

                    .hc-fnav-option.is-selected {
                        border-color: color-mix(in srgb, var(--hc-section-accent) 44%, var(--hc-line));
                        background: linear-gradient(135deg, color-mix(in srgb, var(--hc-section-accent) 9%, var(--hc-paper, #FFF)), var(--hc-paper, #FFF));
                        box-shadow: 0 10px 22px -18px color-mix(in srgb, var(--hc-section-accent) 60%, transparent);
                    }

                    .hc-fnav-option.is-selected .hc-fnav-option-order {
                        border-style: solid;
                        border-color: color-mix(in srgb, var(--hc-section-accent) 50%, var(--hc-line));
                        background: color-mix(in srgb, var(--hc-section-accent) 16%, var(--hc-paper, #FFF));
                        color: color-mix(in srgb, var(--hc-section-accent) 78%, #1e293b);
                    }

                    .hc-fnav-option.is-blocked {
                        opacity: .55;
                        cursor: not-allowed;
                    }

                    .hc-fnav-option.is-shake {
                        animation: hc-fnav-shake .3s ease;
                    }

                    @keyframes hc-fnav-shake {
                        0%, 100% { transform: translateX(0); }
                        30% { transform: translateX(-4px); }
                        60% { transform: translateX(4px); }
                    }

                    @media (prefers-reduced-motion: reduce) {
                        .hc-fnav-option,
                        .hc-fnav-option-order {
                            transition: none;
                        }

                        .hc-fnav-option.is-shake {
                            animation: none;
                        }
                    }
                    </style>

                    <script>
                    (function () {
                        var panel = document.getElementById('hc-footer-nav');
                        if (!panel) return;

                        var MAX = <?= (int) $configFooterNavMax ?>;
                        var MIN = <?= (int) $configFooterNavMin ?>;
                        var selected = <?= json_encode(array_values($configFooterNavSelected), JSON_UNESCAPED_UNICODE) ?>;

                        var inputsBox = panel.querySelector('[data-fnav-inputs]');
                        var preview = panel.querySelector('[data-fnav-preview]');
                        var countEl = panel.querySelector('[data-fnav-count]');
                        var hintEl = panel.querySelector('[data-fnav-hint]');
                        var options = Array.prototype.slice.call(panel.querySelectorAll('[data-fnav-option]'));

                        function render() {
                            // Inputs ocultos en el orden elegido
                            inputsBox.innerHTML = '';
                            selected.forEach(function (key) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'footer_nav[]';
                                input.value = key;
                                inputsBox.appendChild(input);
                            });

                            // Tarjetas: orden + bloqueo al llegar al máximo
                            var full = selected.length >= MAX;
                            options.forEach(function (option) {
                                var key = option.getAttribute('data-fnav-option');
                                var index = selected.indexOf(key);
                                var isSelected = index !== -1;
                                option.classList.toggle('is-selected', isSelected);
                                option.classList.toggle('is-blocked', !isSelected && full);
                                option.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                                option.querySelector('[data-fnav-order]').textContent = isSelected ? String(index + 1) : '';
                            });

                            // Vista previa
                            preview.innerHTML = '';
                            for (var i = 0; i < MAX; i++) {
                                var slot = document.createElement('div');
                                slot.className = 'hc-fnav-slot';
                                var key = selected[i];

                                if (key) {
                                    var option = panel.querySelector('[data-fnav-option="' + key + '"]');
                                    slot.innerHTML = '<i class="fas ' + option.getAttribute('data-fnav-icon') + '"></i><span></span>';
                                    slot.querySelector('span').textContent = option.getAttribute('data-fnav-short');
                                    if (i === 0) slot.classList.add('is-first');
                                } else {
                                    slot.classList.add('is-empty');
                                    slot.innerHTML = '<i class="fas fa-plus"></i><span>Libre</span>';
                                }

                                preview.appendChild(slot);
                            }

                            var menuSlot = document.createElement('div');
                            menuSlot.className = 'hc-fnav-slot is-menu';
                            menuSlot.innerHTML = '<i class="fas fa-grip"></i><span>Menú</span>';
                            preview.appendChild(menuSlot);

                            // Contador y avisos
                            countEl.textContent = String(selected.length);
                            if (selected.length < MIN) {
                                hintEl.textContent = 'Selecciona al menos ' + MIN + ' para guardar.';
                            } else {
                                hintEl.textContent = '';
                            }
                        }

                        options.forEach(function (option) {
                            option.addEventListener('click', function () {
                                var key = option.getAttribute('data-fnav-option');
                                var index = selected.indexOf(key);

                                if (index !== -1) {
                                    selected.splice(index, 1);
                                } else if (selected.length < MAX) {
                                    selected.push(key);
                                } else {
                                    option.classList.remove('is-shake');
                                    void option.offsetWidth;
                                    option.classList.add('is-shake');
                                    return;
                                }

                                render();
                            });
                        });

                        render();
                    })();
                    </script>
                    <?php endif; ?>

                    <section id="hc-system" class="hc-panel" data-hc-section aria-labelledby="hc-system-title">
                        <div class="hc-panel-header">
                            <div>
                                <p class="hc-section-kicker">Compatibilidad</p>
                                <h2 id="hc-system-title" class="hc-panel-title">
                                    <span class="hc-section-mark"><i class="fas fa-server"></i></span>
                                    Sistema y respaldos
                                </h2>
                                <p class="hc-panel-copy">
                                    Campos legacy necesarios para mantener integraciones actuales de sesion, estancia y respaldo.
                                </p>
                            </div>
                        </div>

                        <div class="hc-system-grid">
                            <section class="hc-system-block" aria-label="Sesion y estancia">
                                <div class="hc-group-head">
                                    <div>
                                        <h3 class="hc-group-title">
                                            <i class="fas fa-hourglass-half"></i>
                                            Sesion y estancia
                                        </h3>
                                        <p class="hc-group-hint">Valores guardados en configuracion legacy del sistema.</p>
                                    </div>
                                </div>
                                <div class="hc-field-grid">
                                    <div class="hc-field">
                                        <label for="hotel_horas_estancia">Horas por estancia</label>
                                        <input type="number"
                                               id="hotel_horas_estancia"
                                               name="hotel_horas_estancia"
                                               value="<?= $config['hotel']['horas_estancia'] ?? 12 ?>"
                                               min="1"
                                               max="48"
                                               class="form-input">
                                    </div>
                                    <div class="hc-field">
                                        <label for="sistema_session_lifetime">Duracion de sesion (minutos)</label>
                                        <input type="number"
                                               id="sistema_session_lifetime"
                                               name="sistema_session_lifetime"
                                               value="<?= $config['sistema']['session_lifetime'] ?? 120 ?>"
                                               min="15"
                                               max="480"
                                               step="15"
                                               class="form-input">
                                    </div>
                                </div>
                            </section>

                            <section class="hc-system-block" aria-label="Respaldos automaticos">
                                <div class="hc-group-head">
                                    <div>
                                        <h3 class="hc-group-title">
                                            <i class="fas fa-database"></i>
                                            Respaldos automaticos
                                        </h3>
                                        <p class="hc-group-hint">Configura frecuencia y retencion sin cambiar la ruta de backup.</p>
                                    </div>
                                </div>
                                <div class="hc-backup-box" data-backup-options>
                                    <label class="hc-switch">
                                        <input type="checkbox"
                                               name="sistema_backup_enabled"
                                               value="1"
                                               <?= ($config['sistema']['backup_enabled'] ?? 0) ? 'checked' : '' ?>>
                                        <span class="hc-switch-ui" aria-hidden="true"></span>
                                        <span class="hc-switch-text">
                                            <strong>Respaldos automaticos</strong>
                                            <small>Activa o pausa la ejecucion automatica.</small>
                                        </span>
                                    </label>

                                    <div class="hc-field-grid hc-backup-fields">
                                        <div class="hc-field">
                                            <label for="sistema_backup_frequency">Frecuencia</label>
                                            <select id="sistema_backup_frequency"
                                                    name="sistema_backup_frequency"
                                                    class="form-input">
                                                <option value="daily" <?= ($config['sistema']['backup_frequency'] ?? '') == 'daily' ? 'selected' : '' ?>>Diario</option>
                                                <option value="weekly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                                <option value="monthly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                            </select>
                                        </div>

                                        <div class="hc-field">
                                            <label for="sistema_backup_keep_last">Mantener ultimos</label>
                                            <input type="number"
                                                   id="sistema_backup_keep_last"
                                                   name="sistema_backup_keep_last"
                                                   value="<?= $config['sistema']['backup_keep_last'] ?? 4 ?>"
                                                   min="1"
                                                   max="30"
                                                   class="form-input">
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </section>

                    <div class="hc-hidden-compat" aria-hidden="true">
                        <input type="hidden"
                               id="hotel_nombre"
                               name="hotel_nombre"
                               value="<?= htmlspecialchars((string) $configLegacyNombre, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               id="hotel_telefono"
                               name="hotel_telefono"
                               value="<?= htmlspecialchars((string) $configLegacyTelefono, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               id="hotel_direccion"
                               name="hotel_direccion"
                               value="<?= htmlspecialchars((string) $configLegacyDireccion, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               id="hotel_email"
                               name="hotel_email"
                               value="<?= htmlspecialchars((string) $configLegacyEmail, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               id="hotel_check_in_time"
                               name="hotel_check_in_time"
                               value="<?= htmlspecialchars((string) $configLegacyCheckIn, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               id="hotel_check_out_time"
                               name="hotel_check_out_time"
                               value="<?= htmlspecialchars((string) $configLegacyCheckOut, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="tarifas_incremento_fin_semana"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['incremento_fin_semana'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="tarifas_descuento_grupo_minimo"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['descuento_grupo_minimo'] ?? 5), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="tarifas_descuento_grupo_gratis"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['descuento_grupo_gratis'] ?? 20), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="inventario_auto_papel_higienico"
                               value="<?= htmlspecialchars((string) ($config['inventario']['auto_papel_higienico'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="inventario_auto_jabon"
                               value="<?= htmlspecialchars((string) ($config['inventario']['auto_jabon'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <section class="hc-bottom-actions" aria-label="Confirmar cambios">
                        <div class="hc-bottom-note">
                            <i class="fas fa-circle-info"></i>
                            Los cambios editables se aplicaran cuando confirmes el guardado.
                        </div>
                        <div class="hc-actions">
                            <a href="<?= url('dashboard') ?>" class="hc-link-btn">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                            <button type="submit" class="hc-btn hc-btn-primary">
                                <i class="fas fa-save"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </section>
                </form>

                <section id="hc-devices" class="hc-panel" data-hc-section aria-labelledby="hc-devices-title">
                    <div class="hc-panel-header">
                        <div>
                            <p class="hc-section-kicker">PWA Push</p>
                            <h2 id="hc-devices-title" class="hc-panel-title">
                                <span class="hc-section-mark"><i class="fas fa-mobile-screen-button"></i></span>
                                Dispositivos con avisos activos
                            </h2>
                            <p class="hc-panel-copy">
                                Equipos que aceptaron recibir notificaciones PWA de este hotel. Revocar impide nuevos avisos hasta que se active de nuevo.
                            </p>
                        </div>
                    </div>

                    <?php if (empty($configPwaPushDevices)): ?>
                        <div class="hc-device-empty">
                            Todavia no hay dispositivos PWA registrados para este hotel.
                        </div>
                    <?php else: ?>
                        <div class="hc-device-list">
                            <?php foreach ($configPwaPushDevices as $device): ?>
                                <?php
                                $deviceId = (int)($device['id'] ?? 0);
                                $deviceActive = !empty($device['activo']);
                                $deviceName = $configPwaDeviceName($device);
                                $deviceSeen = $configPwaDate($device['last_seen_at'] ?? null);
                                $deviceCreated = $configPwaDate($device['created_at'] ?? null);
                                ?>
                                <article class="hc-device-row">
                                    <span class="hc-device-icon">
                                        <i class="fas fa-mobile-screen-button"></i>
                                    </span>
                                    <div>
                                        <strong class="hc-device-title">
                                            <?= htmlspecialchars($deviceName, ENT_QUOTES, 'UTF-8') ?>
                                        </strong>
                                        <div class="hc-device-meta">
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                Ultima actividad: <?= htmlspecialchars($deviceSeen, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar-plus"></i>
                                                Alta: <?= htmlspecialchars($deviceCreated, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <span class="hc-device-status <?= $deviceActive ? 'is-active' : 'is-inactive' ?>">
                                                <i class="fas <?= $deviceActive ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                                                <?= $deviceActive ? 'Activo' : 'Revocado' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="hc-device-actions">
                                        <?php if ($deviceActive && $deviceId > 0): ?>
                                            <form method="POST"
                                                  action="<?= url('configuracion/pwa-push/' . $deviceId . '/revocar') ?>"
                                                  class="hc-device-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="hc-device-revoke">
                                                    <i class="fas fa-ban"></i>
                                                    Revocar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
</div>

<script>
const syncLegacyField = function(sourceSelector, targetSelector) {
    const source = document.querySelector(sourceSelector);
    const target = document.querySelector(targetSelector);

    if (!source || !target) {
        return;
    }

    const sync = function() {
        target.value = source.value;
    };

    source.addEventListener('input', sync);
    source.addEventListener('change', sync);
    sync();
};

syncLegacyField('input[name="hotel_branding[nombre_visual]"]', 'input[name="hotel_nombre"]');
syncLegacyField('input[name="hotel_config[contacto.telefono]"]', 'input[name="hotel_telefono"]');
syncLegacyField('input[name="hotel_config[contacto.direccion]"], textarea[name="hotel_config[contacto.direccion]"]', 'input[name="hotel_direccion"]');
syncLegacyField('input[name="hotel_config[contacto.email]"]', 'input[name="hotel_email"]');
syncLegacyField('input[name="hotel_config[operacion.checkin_hora]"]', 'input[name="hotel_check_in_time"]');
syncLegacyField('input[name="hotel_config[operacion.checkout_hora]"]', 'input[name="hotel_check_out_time"]');

document.querySelectorAll('[data-catalog-add]').forEach(button => {
    button.addEventListener('click', () => {
        const kind = button.dataset.catalogAdd;
        const list = document.querySelector(`[data-catalog-list="${kind}"]`);

        if (!list) {
            return;
        }

        const sourceRow = list.querySelector(`[data-catalog-row="${kind}"]`);
        if (!sourceRow) {
            return;
        }

        const nextIndex = parseInt(list.dataset.nextIndex || '0', 10);
        const row = sourceRow.cloneNode(true);
        const namePattern = new RegExp('(\\[' + kind + '\\])\\[\\d+\\]');

        row.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.name) {
                input.name = input.name.replace(namePattern, '$1[' + nextIndex + ']');
            }

            if (input.type === 'checkbox') {
                input.checked = true;
                return;
            }

            if (input.type === 'hidden') {
                input.value = '0';
                return;
            }

            input.value = '';
        });

        list.dataset.nextIndex = String(nextIndex + 1);
        list.appendChild(row);
        row.querySelector('input, textarea, select')?.focus();
        document.dispatchEvent(new CustomEvent('catalog-config-changed', { detail: { kind } }));
    });
});

(() => {
    const catalogPanel = document.querySelector('#hc-catalogs');
    const capacityNode = catalogPanel?.querySelector('[data-parking-capacity-value]');

    if (!catalogPanel || !capacityNode) {
        return;
    }

    const syncParkingCapacity = () => {
        let total = 0;
        catalogPanel.querySelectorAll('[data-catalog-row="parkings"]').forEach(row => {
            const activeInput = row.querySelector('input[type="checkbox"][name$="[activo]"]');
            const capacityInput = row.querySelector('input[name$="[cupo]"]');
            if (!activeInput?.checked || !capacityInput) {
                return;
            }

            const value = Number.parseInt(capacityInput.value || '0', 10);
            if (Number.isFinite(value)) {
                total += Math.max(0, Math.min(999, value));
            }
        });

        capacityNode.textContent = String(total);
    };

    catalogPanel.addEventListener('input', syncParkingCapacity);
    catalogPanel.addEventListener('change', syncParkingCapacity);
    document.addEventListener('catalog-config-changed', syncParkingCapacity);
    syncParkingCapacity();
})();

document.querySelectorAll('[data-owner-add]').forEach(button => {
    button.addEventListener('click', () => {
        const kind = button.dataset.ownerAdd;
        const list = document.querySelector(`[data-owner-list="${kind}"]`);

        if (!list) {
            return;
        }

        const sourceRow = list.querySelector(`[data-owner-row="${kind}"]`);
        if (!sourceRow) {
            return;
        }

        const nextIndex = parseInt(list.dataset.nextIndex || '0', 10);
        const row = sourceRow.cloneNode(true);
        const namePattern = new RegExp('(\\[' + kind + '\\])\\[\\d+\\]');

        row.querySelectorAll('input, textarea, select').forEach(input => {
            if (input.name) {
                input.name = input.name.replace(namePattern, '$1[' + nextIndex + ']');
            }

            if (input.type === 'checkbox') {
                input.checked = true;
                return;
            }

            if (input.type === 'hidden') {
                input.value = '0';
                return;
            }

            input.value = '';
        });

        list.dataset.nextIndex = String(nextIndex + 1);
        list.appendChild(row);
        row.querySelector('input, textarea, select')?.focus();
        document.dispatchEvent(new CustomEvent('owner-config-changed'));
    });
});

(() => {
    const ownerPanel = document.querySelector('#hc-owners');
    const preview = ownerPanel?.querySelector('[data-owner-preview]');

    if (!ownerPanel || !preview) {
        return;
    }

    const activeCountNode = preview.querySelector('[data-owner-preview-active-count]');
    const defaultNode = preview.querySelector('[data-owner-preview-default]');
    const defaultKeyNode = preview.querySelector('[data-owner-preview-default-key]');
    const ruleCountNode = preview.querySelector('[data-owner-preview-rule-count]');
    const assignmentCountNode = preview.querySelector('[data-owner-preview-assignment-count]');
    const listNode = preview.querySelector('[data-owner-preview-list]');
    const defaultInput = ownerPanel.querySelector('input[name="owner_config[propietario_default]"]');

    const normalizeKey = function(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, '_')
            .replace(/^[_-]+|[_-]+$/g, '');
    };

    const readRowValue = function(row, suffix) {
        return row.querySelector(`input[name$="${suffix}"]`)?.value?.trim() || '';
    };

    const formatPercentage = function(value) {
        const parsed = Number(String(value || '').replace(',', '.'));
        const safe = Number.isFinite(parsed) ? Math.max(0, Math.min(100, parsed)) : 100;
        return String(Number(safe.toFixed(2)));
    };

    const ownerRows = function() {
        return Array.from(ownerPanel.querySelectorAll('[data-owner-row="propietarios"]'))
            .map(row => {
                const key = normalizeKey(readRowValue(row, '[key]'));
                const name = readRowValue(row, '[nombre]');
                const percentage = formatPercentage(readRowValue(row, '[participacion_pct]') || 100);
                const active = !!row.querySelector('input[type="checkbox"][name$="[activo]"]')?.checked;

                return {
                    key,
                    name: name || key,
                    percentage,
                    active,
                    hasAny: key !== '' || name !== ''
                };
            })
            .filter(row => row.hasAny);
    };

    const countFilledRows = function(kind, fields) {
        return Array.from(ownerPanel.querySelectorAll(`[data-owner-row="${kind}"]`)).filter(row => {
            return fields.some(field => readRowValue(row, field) !== '');
        }).length;
    };

    const makeFlowRow = function(owner, defaultOwner) {
        const article = document.createElement('article');
        article.className = 'hc-owner-flow-row';

        const icon = document.createElement('span');
        icon.className = 'hc-owner-flow-icon';
        icon.innerHTML = `<i class="fas ${owner.key === defaultOwner.key ? 'fa-star' : 'fa-user-tie'}"></i>`;

        const body = document.createElement('div');
        const name = document.createElement('p');
        name.className = 'hc-owner-flow-name';
        name.textContent = owner.name || owner.key || 'Sin nombre';

        const note = document.createElement('p');
        note.className = 'hc-owner-flow-note';
        if (owner.key === defaultOwner.key) {
            note.textContent = 'Dueno predeterminado: recibe remanentes y lo no clasificado.';
        } else if (Number(owner.percentage) < 100) {
            note.textContent = `Recibe ${owner.percentage}%. El resto va a ${defaultOwner.name || defaultOwner.key || 'el default'}.`;
        } else {
            note.textContent = 'Recibe el 100% de sus habitaciones asignadas.';
        }

        body.appendChild(name);
        body.appendChild(note);

        const pill = document.createElement('span');
        pill.className = 'hc-owner-flow-pill';
        pill.textContent = `${owner.percentage}%`;

        article.appendChild(icon);
        article.appendChild(body);
        article.appendChild(pill);

        return article;
    };

    const syncOwnerPreview = function() {
        const owners = ownerRows();
        const activeOwners = owners.filter(owner => owner.active);
        const defaultKey = normalizeKey(defaultInput?.value || '');
        const defaultOwner = activeOwners.find(owner => owner.key === defaultKey)
            || owners.find(owner => owner.key === defaultKey)
            || activeOwners[0]
            || { key: defaultKey, name: defaultKey || 'Sin definir', percentage: '100', active: true };

        if (activeCountNode) {
            activeCountNode.textContent = String(activeOwners.length);
        }
        if (defaultNode) {
            defaultNode.textContent = defaultOwner.name || defaultOwner.key || 'Sin definir';
        }
        if (defaultKeyNode) {
            defaultKeyNode.textContent = defaultOwner.key || 'default';
        }
        if (ruleCountNode) {
            ruleCountNode.textContent = String(countFilledRows('reglas_tipo_contiene', ['[texto]', '[propietario_key]']));
        }
        if (assignmentCountNode) {
            assignmentCountNode.textContent = String(countFilledRows('habitaciones', ['[numero]', '[tipo]', '[habitacion_id]', '[propietario_key]']));
        }
        if (listNode) {
            listNode.innerHTML = '';
            const visibleOwners = activeOwners.length > 0 ? activeOwners : owners;

            if (visibleOwners.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'hc-owner-flow-empty';
                empty.textContent = 'No hay duenos activos configurados.';
                listNode.appendChild(empty);
            } else {
                visibleOwners.forEach(owner => {
                    listNode.appendChild(makeFlowRow(owner, defaultOwner));
                });
            }
        }
    };

    ownerPanel.addEventListener('input', syncOwnerPreview);
    ownerPanel.addEventListener('change', syncOwnerPreview);
    document.addEventListener('owner-config-changed', syncOwnerPreview);
    syncOwnerPreview();
})();

document.querySelectorAll('.hc-color-input input[type="color"]').forEach(input => {
    const chip = input.closest('.hc-color-input')?.querySelector('.hc-color-code');

    if (!chip) {
        return;
    }

    input.addEventListener('input', () => {
        chip.textContent = input.value.toUpperCase();
    });
});

(() => {
    const preview = document.querySelector('[data-brand-preview]');

    if (!preview) {
        return;
    }

    const fields = {
        name: document.querySelector('[data-brand-input="name"]'),
        primary: document.querySelector('[data-brand-input="primary"]'),
        secondary: document.querySelector('[data-brand-input="secondary"]'),
        accent: document.querySelector('[data-brand-input="accent"]'),
        sidebar: document.querySelector('[data-brand-input="sidebar"]'),
        login: document.querySelector('[data-brand-input="login"]')
    };
    const trackedFields = Object.values(fields).filter(Boolean);
    const fileInputs = Array.from(document.querySelectorAll('[data-brand-file]'));
    const resetButton = preview.querySelector('[data-brand-reset]');
    const dirtyLabel = preview.querySelector('[data-brand-dirty-label]');
    const initialValues = new Map(trackedFields.map(field => [field, field.value]));

    const colorLabels = {
        primary: 'principal',
        secondary: 'secundario',
        accent: 'acento'
    };

    const sidebarLabels = {
        default: 'Menu default',
        solid: 'Menu solido',
        dark: 'Menu oscuro'
    };

    const loginLabels = {
        default: 'Login default',
        soft: 'Login suave',
        image: 'Login con imagen'
    };

    const objectUrls = new Map();

    const normalizeHex = function(value, fallback) {
        const candidate = String(value || '').trim();
        return /^#[0-9A-Fa-f]{6}$/.test(candidate) ? candidate.toUpperCase() : fallback;
    };

    const hexToRgb = function(hex) {
        const value = normalizeHex(hex, '#111827').replace('#', '');
        return [
            parseInt(value.slice(0, 2), 16),
            parseInt(value.slice(2, 4), 16),
            parseInt(value.slice(4, 6), 16)
        ];
    };

    const rgbToHex = function(rgb) {
        return '#' + rgb.map(channel => {
            const safe = Math.max(0, Math.min(255, Math.round(channel)));
            return safe.toString(16).padStart(2, '0');
        }).join('').toUpperCase();
    };

    const mixHex = function(firstHex, secondHex, firstWeight) {
        const first = hexToRgb(firstHex);
        const second = hexToRgb(secondHex);
        const weight = Math.max(0, Math.min(100, firstWeight)) / 100;

        return rgbToHex(first.map((channel, index) => {
            return channel * weight + second[index] * (1 - weight);
        }));
    };

    const luminanceChannel = function(channel) {
        const value = channel / 255;
        return value <= 0.03928 ? value / 12.92 : Math.pow((value + 0.055) / 1.055, 2.4);
    };

    const contrastText = function(hex) {
        const rgb = hexToRgb(hex);
        const luminance = 0.2126 * luminanceChannel(rgb[0])
            + 0.7152 * luminanceChannel(rgb[1])
            + 0.0722 * luminanceChannel(rgb[2]);
        const darkRatio = (luminance + 0.05) / 0.05;
        const lightRatio = 1.05 / (luminance + 0.05);

        return darkRatio >= lightRatio ? '#111827' : '#FFFEFB';
    };

    const relativeLuminance = function(hex) {
        const rgb = hexToRgb(hex);
        return 0.2126 * luminanceChannel(rgb[0])
            + 0.7152 * luminanceChannel(rgb[1])
            + 0.0722 * luminanceChannel(rgb[2]);
    };

    const contrastRatio = function(backgroundHex, textHex) {
        const first = relativeLuminance(backgroundHex);
        const second = relativeLuminance(textHex);
        const light = Math.max(first, second);
        const dark = Math.min(first, second);

        return (light + 0.05) / (dark + 0.05);
    };

    const setText = function(selector, value) {
        document.querySelectorAll(selector).forEach(target => {
            target.textContent = value;
        });
    };

    const setImageSource = function(key, source) {
        document.querySelectorAll(`[data-brand-image="${key}"]`).forEach(image => {
            if (source) {
                image.src = source;
                image.hidden = false;
            } else {
                image.removeAttribute('src');
                image.hidden = true;
            }
        });

        document.querySelectorAll(`[data-brand-fallback="${key}"]`).forEach(icon => {
            icon.hidden = Boolean(source);
        });
    };

    const restoreImage = function(key) {
        const original = document.querySelector(`[data-brand-image="${key}"]`)?.dataset.originalSrc || '';
        setImageSource(key, original);
    };

    const setFileStatus = function(input, key, file) {
        const row = input.closest('.hc-upload-row');
        const status = document.querySelector(`[data-brand-file-status="${key}"]`);

        if (row) {
            row.classList.toggle('is-selected', Boolean(file));
        }

        if (!status) {
            return;
        }

        if (!file) {
            status.textContent = status.dataset.defaultStatus || 'Sin archivo seleccionado';
            return;
        }

        const shortName = file.name.length > 34
            ? file.name.slice(0, 18) + '...' + file.name.slice(-12)
            : file.name;
        status.textContent = 'Seleccionado: ' + shortName;
    };

    const updateContrastStatus = function(colors) {
        const contrastPanel = preview.querySelector('[data-brand-contrast]');
        const summary = preview.querySelector('[data-brand-contrast-summary]');
        let warningCount = 0;

        Object.entries(colors).forEach(([key, value]) => {
            const item = preview.querySelector(`[data-brand-contrast-item="${key}"]`);
            const score = preview.querySelector(`[data-brand-contrast-score="${key}"]`);
            const textColor = contrastText(value);
            const ratio = contrastRatio(value, textColor);
            const tone = textColor === '#111827' ? 'Oscuro' : 'Claro';
            const passes = ratio >= 4.5;

            if (!passes) {
                warningCount += 1;
            }

            if (item) {
                item.classList.toggle('is-warn', !passes);
                item.title = `Contraste ${ratio.toFixed(1)}:1`;
            }

            if (score) {
                score.textContent = passes ? `${tone} ${ratio.toFixed(1)}` : `Revisar ${ratio.toFixed(1)}`;
            }
        });

        if (contrastPanel) {
            contrastPanel.classList.toggle('has-warning', warningCount > 0);
        }

        if (summary) {
            summary.textContent = warningCount > 0 ? 'Revisar contraste' : 'AA listo';
        }
    };

    const hasSelectedFiles = function() {
        return fileInputs.some(input => input.files && input.files.length > 0);
    };

    const hasFieldChanges = function() {
        return trackedFields.some(field => field.value !== initialValues.get(field));
    };

    const updateDirtyState = function() {
        const isDirty = hasFieldChanges() || hasSelectedFiles();
        preview.classList.toggle('is-dirty', isDirty);

        if (dirtyLabel) {
            dirtyLabel.textContent = isDirty ? 'Cambios sin guardar' : 'Sin cambios';
        }

        if (resetButton) {
            resetButton.disabled = !isDirty;
        }
    };

    const updatePreview = function() {
        const primary = normalizeHex(fields.primary?.value, '#1B2746');
        const secondary = normalizeHex(fields.secondary?.value, '#0F172A');
        const accent = normalizeHex(fields.accent?.value, '#BD9441');
        const visualName = String(fields.name?.value || preview.dataset.defaultName || 'Hotel').trim()
            || preview.dataset.defaultName
            || 'Hotel';
        const sidebarStyle = fields.sidebar?.value || 'default';
        const loginStyle = fields.login?.value || 'default';

        preview.style.setProperty('--preview-primary', primary);
        preview.style.setProperty('--preview-secondary', secondary);
        preview.style.setProperty('--preview-accent', accent);
        preview.style.setProperty('--preview-primary-soft', mixHex(primary, '#FFFEFB', 10));
        preview.style.setProperty('--preview-accent-soft', mixHex(accent, '#FFFEFB', 13));
        preview.style.setProperty('--preview-line', mixHex(primary, '#E7DED2', 16));
        preview.style.setProperty('--preview-on-primary', contrastText(primary));
        preview.style.setProperty('--preview-on-secondary', contrastText(secondary));
        preview.dataset.sidebarStyle = sidebarStyle;
        preview.dataset.loginStyle = loginStyle;

        setText('[data-brand-preview-text="name"]', visualName);
        setText('[data-brand-preview-text="sidebar-style"]', sidebarLabels[sidebarStyle] || 'Menu default');
        setText('[data-brand-preview-text="login-style"]', loginLabels[loginStyle] || 'Login default');

        Object.entries({ primary, secondary, accent }).forEach(([key, value]) => {
            document.querySelectorAll(`[data-brand-preview-code="${key}"]`).forEach(target => {
                target.textContent = value;
            });
            document.querySelectorAll(`[data-brand-swatch="${key}"]`).forEach(target => {
                target.style.background = value;
                target.title = `${colorLabels[key]} ${value}`;
            });
        });
        updateContrastStatus({ primary, secondary, accent });

        updateDirtyState();
    };

    trackedFields.forEach(field => {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
    });

    fileInputs.forEach(input => {
        input.addEventListener('change', () => {
            const key = input.dataset.brandFile;
            const file = input.files && input.files[0] ? input.files[0] : null;

            if (objectUrls.has(key)) {
                URL.revokeObjectURL(objectUrls.get(key));
                objectUrls.delete(key);
            }

            if (!file) {
                restoreImage(key);
                setFileStatus(input, key, null);
                updateDirtyState();
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            objectUrls.set(key, previewUrl);
            setImageSource(key, previewUrl);
            setFileStatus(input, key, file);
            updateDirtyState();
        });
    });

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            trackedFields.forEach(field => {
                field.value = initialValues.get(field) || '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            });

            fileInputs.forEach(input => {
                const key = input.dataset.brandFile;

                if (objectUrls.has(key)) {
                    URL.revokeObjectURL(objectUrls.get(key));
                    objectUrls.delete(key);
                }

                input.value = '';
                restoreImage(key);
                setFileStatus(input, key, null);
            });

            updatePreview();
            updateDirtyState();
        });
    }

    updatePreview();

    window.addEventListener('beforeunload', () => {
        objectUrls.forEach(url => URL.revokeObjectURL(url));
        objectUrls.clear();
    });
})();

const phoneInput = document.querySelector('input[name="hotel_config[contacto.telefono]"], input[name="hotel_telefono"]');
if (phoneInput) {
    phoneInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';

        if (value.length > 0) {
            if (value.length <= 3) {
                formattedValue = `(${value}`;
            } else if (value.length <= 6) {
                formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
            } else {
                formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
            }
        }

        e.target.value = formattedValue;
        const legacyPhoneInput = document.querySelector('input[name="hotel_telefono"]');
        if (legacyPhoneInput) {
            legacyPhoneInput.value = formattedValue;
        }
    });
}

const configForm = document.getElementById('configForm');
if (configForm) {
    const saveButtons = Array.from(configForm.querySelectorAll('button[type="submit"]'));
    const saveStatusTargets = Array.from(configForm.querySelectorAll('.hc-save-copy, .hc-bottom-note'));
    const originalSaveButtonHtml = new Map(saveButtons.map(button => [button, button.innerHTML]));
    const originalSaveStatusHtml = new Map(saveStatusTargets.map(target => [target, target.innerHTML]));

    const setSaveSubmittingState = (active) => {
        saveButtons.forEach(button => {
            button.disabled = active;
            button.setAttribute('aria-busy', active ? 'true' : 'false');
            button.innerHTML = active
                ? '<i class="fas fa-spinner fa-spin"></i> Guardando cambios'
                : (originalSaveButtonHtml.get(button) || '<i class="fas fa-save"></i> Guardar cambios');
        });

        saveStatusTargets.forEach(target => {
            target.innerHTML = active
                ? '<i class="fas fa-circle-info"></i> Guardando la configuracion del hotel. Espera un momento.'
                : (originalSaveStatusHtml.get(target) || target.innerHTML);
        });
    };

    const submitConfigForm = () => {
        configForm.dataset.confirmedSave = '1';
        setSaveSubmittingState(true);
        HTMLFormElement.prototype.submit.call(configForm);
    };

    configForm.addEventListener('submit', function(e) {
        if (this.dataset.confirmedSave === '1') {
            return;
        }

        e.preventDefault();

        if (this.dataset.awaitingSaveConfirmation === '1') {
            return;
        }

        this.dataset.awaitingSaveConfirmation = '1';

        const clearPendingConfirmation = () => {
            delete this.dataset.awaitingSaveConfirmation;
        };

        if (typeof Swal === 'undefined') {
            const confirmed = window.confirm('¿Guardar los cambios de configuracion ahora?');
            clearPendingConfirmation();
            if (confirmed) {
                submitConfigForm();
            }
            return;
        }

        Swal.fire({
            title: '¿Guardar configuracion?',
            html: '<p style="margin:0;text-align:left;">Se aplicaran los cambios editables de esta pantalla para el hotel. Revisa que la informacion sea correcta antes de continuar.</p>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Si, guardar cambios',
            cancelButtonText: 'Seguir revisando',
            confirmButtonColor: 'var(--brand-primary, #1B2746)',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'hc-save-confirm-modal'
            }
        }).then((result) => {
            clearPendingConfirmation();
            if (result.isConfirmed) {
                submitConfigForm();
            }
        });
    });
}

const backupCheckbox = document.querySelector('input[name="sistema_backup_enabled"]');
if (backupCheckbox) {
    const syncBackupOptions = function() {
        const backupBox = backupCheckbox.closest('[data-backup-options]');
        if (!backupBox) {
            return;
        }

        const backupOptions = backupBox.querySelectorAll('select, input[type="number"]');
        backupOptions.forEach(input => {
            input.disabled = !backupCheckbox.checked;
            input.style.opacity = backupCheckbox.checked ? '1' : '0.55';
        });
    };

    backupCheckbox.addEventListener('change', syncBackupOptions);
    syncBackupOptions();
}

document.querySelectorAll('[data-guest-policy-row]').forEach(row => {
    const visible = row.querySelector('[data-guest-visible]');
    const required = row.querySelector('[data-guest-required]');

    if (!visible || !required) {
        return;
    }

    required.addEventListener('change', () => {
        if (required.checked) {
            visible.checked = true;
        }
    });

    visible.addEventListener('change', () => {
        if (!visible.checked) {
            required.checked = false;
        }
    });
});

const identityPolicyPanel = document.querySelector('[data-identity-policy-panel]');
if (identityPolicyPanel) {
    const identityRows = Array.from(identityPolicyPanel.querySelectorAll('[data-guest-policy-row]'));
    const identityStatusRecord = identityPolicyPanel.querySelector('[data-identity-status="record"]');
    const identityStatusRequired = identityPolicyPanel.querySelector('[data-identity-status="required"]');
    const identityStatusFile = identityPolicyPanel.querySelector('[data-identity-status="file"]');

    const setIdentityStatus = function(node, isOn, text, iconClass) {
        if (!node) {
            return;
        }

        node.classList.toggle('is-on', isOn);
        node.classList.toggle('is-muted', !isOn);
        const icon = node.querySelector('i');
        if (icon) {
            icon.className = 'fas ' + iconClass;
        }
        node.lastChild.textContent = ' ' + text;
    };

    const syncIdentitySummary = function() {
        const anyVisible = identityRows.some(row => row.querySelector('[data-guest-visible]')?.checked);
        const anyRequired = identityRows.some(row => row.querySelector('[data-guest-required]')?.checked);
        const fileRow = identityPolicyPanel.querySelector('[data-guest-field="identificacion_archivo"]');
        const fileVisible = !!fileRow?.querySelector('[data-guest-visible]')?.checked;
        const fileRequired = !!fileRow?.querySelector('[data-guest-required]')?.checked;

        setIdentityStatus(identityStatusRecord, anyVisible, anyVisible ? 'Se pedira' : 'No se pedira', anyVisible ? 'fa-eye' : 'fa-eye-slash');
        setIdentityStatus(identityStatusRequired, anyRequired, anyRequired ? 'Con obligatorios' : 'Sin obligatorios', anyRequired ? 'fa-circle-check' : 'fa-circle');
        setIdentityStatus(
            identityStatusFile,
            fileVisible,
            fileVisible ? (fileRequired ? 'Archivo obligatorio' : 'Archivo opcional') : 'Archivo apagado',
            fileVisible ? 'fa-file-arrow-up' : 'fa-file-circle-xmark'
        );
    };

    identityPolicyPanel.querySelectorAll('[data-guest-visible], [data-guest-required]').forEach(input => {
        input.addEventListener('change', syncIdentitySummary);
    });
    syncIdentitySummary();
}

const navLinks = Array.from(document.querySelectorAll('.hc-nav-link'));
const sectionById = new Map(navLinks.map(link => [link.getAttribute('href')?.replace('#', ''), link]));
const observedSections = Array.from(document.querySelectorAll('[data-hc-section]'));

const activateConfigSection = function(sectionId, options = {}) {
    const targetSection = document.getElementById(sectionId);

    if (!targetSection) {
        return;
    }

    observedSections.forEach(section => {
        const isActive = section === targetSection;
        section.classList.toggle('is-active', isActive);
        section.hidden = !isActive;
    });

    navLinks.forEach(link => {
        const isActive = link.getAttribute('href') === '#' + sectionId;
        link.classList.toggle('is-active', isActive);
        if (isActive) {
            link.setAttribute('aria-current', 'page');
            link.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        } else {
            link.removeAttribute('aria-current');
        }
    });

    const page = document.querySelector('.hc-page');
    if (page) {
        page.dataset.activeSection = sectionId;
    }

    if (options.focus) {
        targetSection.querySelector('input, textarea, select, button, a')?.focus({ preventScroll: true });
    }
};

if (observedSections.length > 0) {
    const initialSection = window.location.hash && sectionById.has(window.location.hash.replace('#', ''))
        ? window.location.hash.replace('#', '')
        : (observedSections[0]?.id || 'hc-readonly');

    activateConfigSection(initialSection);

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href')?.replace('#', '');
            activateConfigSection(sectionId);
        });
    });
}

// ── Tema de color (MedisoftTheme se define en layout/header.php) ──
(function() {
    const themeButtons = Array.from(document.querySelectorAll('.hc-theme-opt[data-theme-mode]'));
    if (themeButtons.length === 0 || !window.MedisoftTheme) {
        return;
    }

    const syncThemeButtons = function() {
        const mode = window.MedisoftTheme.get();
        themeButtons.forEach(button => {
            const isActive = button.dataset.themeMode === mode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    };

    themeButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.MedisoftTheme.set(this.dataset.themeMode);
        });
    });

    document.addEventListener('medisoft:theme-change', syncThemeButtons);
    syncThemeButtons();
})();

// ── Vibración / hápticos (MedisoftHaptics se define en layout/header.php) ──
(function() {
    const card = document.getElementById('hc-haptics-card');
    const buttons = Array.from(document.querySelectorAll('.hc-theme-opt[data-haptics-mode]'));
    if (!card || buttons.length === 0 || !window.MedisoftHaptics) {
        return;
    }

    // Solo mostrar el control donde tiene sentido: soporte real + dispositivo táctil.
    const touch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
    if (!window.MedisoftHaptics.supported || !touch) {
        return; // la card queda oculta (hidden)
    }
    card.hidden = false;

    const syncButtons = function() {
        const mode = window.MedisoftHaptics.get();
        buttons.forEach(button => {
            const isActive = button.dataset.hapticsMode === mode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });
    };

    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const mode = this.dataset.hapticsMode;
            window.MedisoftHaptics.set(mode);
            syncButtons();
            if (mode === 'on') { window.MedisoftHaptics.fire('success'); } // confirmación palpable
        });
    });

    document.addEventListener('medisoft:haptics-change', syncButtons);
    syncButtons();
})();

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.hc-page');
    if (view) {
        view.classList.add('is-ready');
    }
});
</script>
