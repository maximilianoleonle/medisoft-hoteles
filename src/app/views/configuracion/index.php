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
$configGeneralUnitRows = is_array($generalUnitCatalog ?? null)
    ? array_values($generalUnitCatalog)
    : (function_exists('hotel_general_catalog_unit_rows') ? hotel_general_catalog_unit_rows(null, true) : []);
$configPwaPushDevices = is_array($pwaPushDevices ?? null) ? array_values($pwaPushDevices) : [];

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

?>

<style>
.hc-page {
    --hc-brand: var(--brand-primary, #223b36);
    --hc-brand-strong: var(--brand-secondary, #17241f);
    --hc-accent: var(--brand-accent, #b9873f);
    --hc-accent-soft: color-mix(in srgb, var(--hc-accent) 14%, #fffffb);
    --hc-bg: #f4f1ea;
    --hc-bg-deep: #ebe5d9;
    --hc-surface: #fffffd;
    --hc-surface-muted: color-mix(in srgb, var(--hc-brand) 4%, #fffffd);
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
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
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
    background: color-mix(in srgb, var(--hc-brand) 5%, #fffffd);
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
    background: color-mix(in srgb, var(--hc-brand) 8%, #fffffd);
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
    background: color-mix(in srgb, var(--hc-brand) 4%, #fffffd);
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
    background: color-mix(in srgb, var(--hc-brand) 5%, #fffffd);
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
    background: #fffffd;
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
    background: color-mix(in srgb, var(--hc-brand) 6%, #fffffd);
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
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-brand) 9%, #fffffd), var(--hc-accent-soft));
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
    background: #fffffd;
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
    background: #fffffd;
    cursor: pointer;
    isolation: isolate;
    text-transform: none;
    letter-spacing: 0;
    transition: background .18s ease-out, border-color .18s ease-out, box-shadow .18s ease-out;
}

.hc-switch:has(input:checked) {
    border-color: color-mix(in srgb, var(--hc-success) 38%, var(--hc-line));
    background: color-mix(in srgb, var(--hc-success) 7%, #fffffd);
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
    background: color-mix(in srgb, var(--hc-brand) 6%, #fffffd);
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
    background: #fffffd;
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
    background: color-mix(in srgb, var(--hc-brand) 3%, #fffffd);
}

.hc-catalog-row.is-type {
    grid-template-columns: minmax(120px, 1fr) minmax(140px, 1.15fr) minmax(92px, .7fr) minmax(120px, .8fr) minmax(160px, 1.35fr) minmax(92px, .6fr);
}

.hc-catalog-row.is-floor,
.hc-catalog-row.is-amenity,
.hc-catalog-row.is-simple {
    grid-template-columns: minmax(110px, .8fr) minmax(180px, 1.5fr) minmax(92px, .6fr);
}

.hc-catalog-row.is-unit {
    grid-template-columns: minmax(110px, .9fr) minmax(180px, 1.4fr) minmax(92px, .7fr) minmax(92px, .6fr);
}

.hc-catalog-cell {
    min-width: 0;
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
        linear-gradient(135deg, color-mix(in srgb, var(--hc-brand) 11%, #fffffd), #fffffd 58%),
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
    background: #fffffd;
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
    background: color-mix(in srgb, var(--hc-danger) 7%, #fffffd);
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
    .hc-catalog-row.is-unit {
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
    .hc-catalog-row.is-unit {
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
    --hc-accent-soft: color-mix(in srgb, var(--hc-accent) 7%, #fff);
    --hc-bg: #fcfbf8;
    --hc-bg-deep: color-mix(in srgb, var(--hc-accent) 4%, #f4f1ea);
    --hc-surface: rgba(255,255,255,.86);
    --hc-surface-muted: color-mix(in srgb, var(--hc-accent) 3%, rgba(255,255,255,.82));
    --hc-brand-wash: color-mix(in srgb, var(--hc-brand) 6%, #fff);
    --hc-accent-wash: color-mix(in srgb, var(--hc-accent) 10%, #fff);
    --hc-blush-wash: color-mix(in srgb, var(--hc-accent) 5%, color-mix(in srgb, var(--hc-brand) 3%, #fff));
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
    --hc-section-wash: color-mix(in srgb, var(--hc-section-accent) 7%, #fff);
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
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
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

.hc-eyebrow {
    display: inline-flex;
    min-height: 30px;
    margin: 0;
    padding: 0 10px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-brand) 58%, #667085);
    background: linear-gradient(135deg, rgba(255,255,255,.78), var(--hc-accent-wash));
    border: 1px solid color-mix(in srgb, var(--hc-accent) 26%, #ded6c8);
    font-size: .78rem;
    font-weight: 640;
    letter-spacing: 0;
    text-transform: none;
}

.hc-eyebrow i {
    color: color-mix(in srgb, var(--hc-accent) 76%, #795a16);
}

.hc-title {
    max-width: 760px;
    margin-top: 11px;
    color: var(--hc-brand-strong);
    font-size: clamp(1.7rem, 3vw, 2.85rem);
    line-height: 1.08;
    font-weight: 540;
    letter-spacing: 0;
}

.hc-subtitle {
    max-width: 680px;
    margin-top: 10px;
    color: var(--hc-ink-soft);
    font-size: .98rem;
    line-height: 1.55;
    font-weight: 430;
}

.hc-top-meta {
    gap: 10px;
    margin-top: 16px;
}

.hc-chip {
    min-height: 32px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--hc-brand) 58%, #667085);
    background: color-mix(in srgb, var(--hc-brand) 4%, rgba(255,255,255,.82));
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
        linear-gradient(135deg, rgba(255,255,255,.9), var(--hc-accent-wash) 58%, color-mix(in srgb, var(--hc-brand) 5%, #fff));
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
    background: linear-gradient(135deg, var(--hc-accent-wash), color-mix(in srgb, var(--hc-brand) 4%, #fff));
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
    background: linear-gradient(135deg, rgba(255,255,255,.9), color-mix(in srgb, var(--hc-accent) 4%, rgba(255,255,255,.88)));
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
.hc-nav-link[href="#hc-rooms"] { --hc-nav-accent: var(--hc-tone-indigo); }
.hc-nav-link[href="#hc-catalogs"] { --hc-nav-accent: var(--hc-tone-olive); }
.hc-nav-link[href="#hc-brand"] { --hc-nav-accent: var(--hc-accent); }
.hc-nav-link[href="#hc-system"] { --hc-nav-accent: var(--hc-tone-coral); }
.hc-nav-link[href="#hc-devices"] { --hc-nav-accent: var(--hc-tone-slate); }

.hc-nav-link i {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    color: color-mix(in srgb, var(--hc-nav-accent) 68%, #667085);
    background: color-mix(in srgb, var(--hc-nav-accent) 10%, #fff);
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
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-nav-accent) 13%, #fff), color-mix(in srgb, var(--hc-brand) 4%, #fff));
    border-color: color-mix(in srgb, var(--hc-nav-accent) 34%, var(--hc-line));
    box-shadow: none;
}

.hc-nav-link.is-active i {
    color: color-mix(in srgb, var(--hc-nav-accent) 78%, #334155);
    background: color-mix(in srgb, var(--hc-nav-accent) 18%, #fff);
    border-color: color-mix(in srgb, var(--hc-nav-accent) 28%, var(--hc-line));
}

.hc-panel,
.hc-save-dock,
.hc-bottom-actions {
    border-radius: 16px;
    border-color: var(--hc-line);
    background: linear-gradient(180deg, rgba(255,255,255,.92), color-mix(in srgb, var(--hc-accent) 2%, rgba(255,255,255,.88)));
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
        linear-gradient(180deg, rgba(255,255,255,.94), var(--hc-section-wash));
}

.hc-panel[data-hc-section]::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--hc-section-accent) 70%, var(--hc-line)), color-mix(in srgb, var(--hc-section-accent) 12%, transparent));
}

#hc-readonly { --hc-section-accent: var(--hc-tone-blue); --hc-section-wash: color-mix(in srgb, var(--hc-tone-blue) 6%, #fff); }
#hc-settings { --hc-section-accent: var(--hc-tone-sage); --hc-section-wash: color-mix(in srgb, var(--hc-tone-sage) 6%, #fff); }
#hc-notifications { --hc-section-accent: var(--hc-tone-amber); --hc-section-wash: color-mix(in srgb, var(--hc-tone-amber) 7%, #fff); }
#hc-rooms { --hc-section-accent: var(--hc-tone-indigo); --hc-section-wash: color-mix(in srgb, var(--hc-tone-indigo) 6%, #fff); }
#hc-catalogs { --hc-section-accent: var(--hc-tone-olive); --hc-section-wash: color-mix(in srgb, var(--hc-tone-olive) 6%, #fff); }
#hc-brand { --hc-section-accent: var(--hc-accent); --hc-section-wash: color-mix(in srgb, var(--hc-accent) 7%, #fff); }
#hc-system { --hc-section-accent: var(--hc-tone-coral); --hc-section-wash: color-mix(in srgb, var(--hc-tone-coral) 6%, #fff); }
#hc-devices { --hc-section-accent: var(--hc-tone-slate); --hc-section-wash: color-mix(in srgb, var(--hc-tone-slate) 6%, #fff); }

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
    color: color-mix(in srgb, var(--hc-section-accent) 78%, #334155);
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-section-accent) 16%, #fff), color-mix(in srgb, var(--hc-brand) 4%, #fff));
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
    background: color-mix(in srgb, var(--hc-section-accent) 9%, #fff);
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
        linear-gradient(135deg, color-mix(in srgb, var(--hc-card-accent) 10%, rgba(255,255,255,.94)), rgba(255,255,255,.88));
    border-color: color-mix(in srgb, var(--hc-card-accent) 18%, var(--hc-line));
    box-shadow: 0 12px 26px -24px color-mix(in srgb, var(--hc-card-accent) 44%, transparent);
}

.hc-stat.is-featured {
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-card-accent) 13%, #fff), color-mix(in srgb, var(--hc-accent) 6%, #fff));
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
    color: color-mix(in srgb, var(--hc-card-accent) 78%, #334155);
    background: color-mix(in srgb, var(--hc-card-accent) 14%, #fff);
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
        linear-gradient(135deg, rgba(255,255,255,.94), color-mix(in srgb, var(--hc-accent) 4%, rgba(255,255,255,.9)));
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
    background: rgba(255,255,255,.86);
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
        linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent) 6%, rgba(255,255,255,.92)), rgba(255,255,255,.86));
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
#hc-rooms .hc-catalog-box:nth-child(3n+1) { --hc-block-accent: var(--hc-tone-indigo); }
#hc-rooms .hc-catalog-box:nth-child(3n+2) { --hc-block-accent: var(--hc-tone-sage); }
#hc-rooms .hc-catalog-box:nth-child(3n+3) { --hc-block-accent: var(--hc-tone-amber); }
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
    color: color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 76%, #334155);
}

.hc-page .form-input,
.hc-color-code,
.hc-color-input input[type="color"] {
    border-radius: 12px;
    border-color: color-mix(in srgb, var(--hc-accent) 18%, #ddd5c8);
    background: #fff;
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
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-block-accent, var(--hc-section-accent)) 5%, rgba(255,255,255,.92)), color-mix(in srgb, var(--hc-accent) 3%, rgba(255,255,255,.88)));
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

.hc-catalog-row.is-unit {
    grid-template-columns: minmax(140px, .78fr) minmax(220px, 1.18fr) minmax(110px, .52fr) minmax(118px, 148px);
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
    background: linear-gradient(135deg, color-mix(in srgb, var(--hc-success) 8%, #fff), color-mix(in srgb, var(--hc-block-accent, var(--hc-success)) 4%, #fff));
}

.hc-brand-preview {
    --hc-block-accent: var(--hc-accent);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--hc-accent) 18%, transparent), transparent 11rem),
        linear-gradient(135deg, color-mix(in srgb, var(--hc-accent) 10%, rgba(255,255,255,.92)), color-mix(in srgb, var(--hc-tone-indigo) 5%, rgba(255,255,255,.88)));
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

@media (max-width: 1240px) {
    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-unit {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 860px) {
    .hc-catalog-row,
    .hc-catalog-row.is-type,
    .hc-catalog-row.is-floor,
    .hc-catalog-row.is-amenity,
    .hc-catalog-row.is-simple,
    .hc-catalog-row.is-unit {
        grid-template-columns: 1fr;
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

    .hc-title {
        font-size: clamp(1.75rem, 9vw, 2.6rem);
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
</style>

<div class="hc-page" data-active-section="hc-readonly">
    <main class="hc-shell">
        <header class="hc-top" aria-labelledby="config-page-title">
            <section class="hc-top-main">
                <div>
                    <p class="hc-eyebrow">
                        <i class="fas fa-sliders-h"></i>
                        Centro de ajustes
                    </p>
                    <h1 id="config-page-title" class="hc-title">Configuracion del hotel</h1>
                    <p class="hc-subtitle">
                        Administra operacion, catalogos, marca y dispositivos desde una vista clara para este hotel.
                    </p>
                </div>

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
                    <a href="#hc-rooms" class="hc-nav-link">
                        <i class="fas fa-bed"></i>
                        <strong>Habitaciones <span>Tipos y amenidades</span></strong>
                    </a>
                    <a href="#hc-catalogs" class="hc-nav-link">
                        <i class="fas fa-layer-group"></i>
                        <strong>Catalogos <span>Listas auxiliares</span></strong>
                    </a>
                    <a href="#hc-brand" class="hc-nav-link">
                        <i class="fas fa-palette"></i>
                        <strong>Marca <span>Identidad visual</span></strong>
                    </a>
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

                <form method="POST" action="<?= url('configuracion/update') ?>" id="configForm" enctype="multipart/form-data">
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
                                        <p class="hc-field-hint">Ejemplo de codigo: sencilla_lujo. Usa letras, numeros y guion bajo.</p>
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
                                        <p class="hc-field-hint">Catalogo preparado para la fase de huespedes y vehiculos.</p>
                                    </div>
                                    <button type="button" class="hc-add-btn" data-catalog-add="parkings">
                                        <i class="fas fa-plus"></i>
                                        Agregar estacionamiento
                                    </button>
                                </div>

                                <div class="hc-catalog-list" data-catalog-list="parkings" data-next-index="<?= count($parkingRowsForForm) ?>">
                                    <?php foreach ($parkingRowsForForm as $index => $row): ?>
                                        <?php
                                        $parkingCode = (string) ($row['codigo'] ?? '');
                                        $parkingLabel = (string) ($row['label'] ?? '');
                                        $parkingActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                        ?>
                                        <div class="hc-catalog-row is-simple" data-catalog-row="parkings">
                                            <div class="hc-catalog-cell">
                                                <label>Codigo</label>
                                                <input type="text"
                                                       name="general_catalog[parkings][<?= $index ?>][codigo]"
                                                       value="<?= htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8') ?>"
                                                       maxlength="30"
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
                                                       maxlength="30"
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
                            <aside class="hc-brand-preview" aria-label="Vista previa de marca">
                                <div>
                                    <div class="hc-brand-logo">
                                        <?php if ($brandingLogoPreview): ?>
                                            <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"
                                                 alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <i class="fas fa-hotel"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p class="hc-brand-name"><?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="hc-brand-note">
                                        Estos recursos se aplican al entorno operativo del hotel y no al panel SaaS.
                                    </p>
                                </div>
                                <div class="hc-top-meta">
                                    <span class="hc-chip">Primary <?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="hc-chip">Accent <?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></span>
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
                                           class="form-input">
                                    <p class="hc-field-hint">Nombre mostrado en login, encabezado y documentos compatibles.</p>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_primary">Color principal</label>
                                    <div class="hc-color-input">
                                        <input type="color"
                                               id="branding_color_primary"
                                               name="hotel_branding[color_primary]"
                                               value="<?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code"><?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_secondary">Color secundario</label>
                                    <div class="hc-color-input">
                                        <input type="color"
                                               id="branding_color_secondary"
                                               name="hotel_branding[color_secondary]"
                                               value="<?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code"><?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_color_accent">Color acento</label>
                                    <div class="hc-color-input">
                                        <input type="color"
                                               id="branding_color_accent"
                                               name="hotel_branding[color_accent]"
                                               value="<?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="hc-color-code"><?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="branding_sidebar_style">Estilo de menu</label>
                                    <select id="branding_sidebar_style"
                                            name="hotel_branding[sidebar_style]"
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
                                            <?php if ($brandingLogoPreview): ?>
                                                <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
                                            <?php else: ?>
                                                <i class="fas fa-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input">
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="favicon_file">Favicon</label>
                                    <div class="hc-upload-row">
                                        <div class="hc-upload-thumb">
                                            <?php if ($brandingFaviconPreview): ?>
                                                <img src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Favicon">
                                            <?php else: ?>
                                                <i class="fas fa-star"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,image/x-icon,image/png" class="form-input">
                                    </div>
                                </div>

                                <div class="hc-field is-wide">
                                    <label for="login_background_file">Fondo de login</label>
                                    <div class="hc-upload-row">
                                        <div class="hc-upload-thumb">
                                            <?php if ($brandingLoginPreview): ?>
                                                <img src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Fondo login">
                                            <?php else: ?>
                                                <i class="fas fa-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="login_background_file" name="login_background_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input">
                                    </div>
                                </div>

                                <div class="hc-field">
                                    <label for="pwa_icon_192_file">Icono PWA 192</label>
                                    <div class="hc-upload-row">
                                        <div class="hc-upload-thumb">
                                            <?php if ($brandingPwa192Preview): ?>
                                                <img src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>" alt="PWA 192">
                                            <?php else: ?>
                                                <i class="fas fa-mobile-alt"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="pwa_icon_192_file" name="pwa_icon_192_file" accept=".png,.webp,image/png,image/webp" class="form-input">
                                    </div>
                                    <p class="hc-field-hint">Debe medir exactamente 192x192 px.</p>
                                </div>

                                <div class="hc-field">
                                    <label for="pwa_icon_512_file">Icono PWA 512</label>
                                    <div class="hc-upload-row">
                                        <div class="hc-upload-thumb">
                                            <?php if ($brandingPwa512Preview): ?>
                                                <img src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>" alt="PWA 512">
                                            <?php else: ?>
                                                <i class="fas fa-mobile-alt"></i>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" id="pwa_icon_512_file" name="pwa_icon_512_file" accept=".png,.webp,image/png,image/webp" class="form-input">
                                    </div>
                                    <p class="hc-field-hint">Debe medir exactamente 512x512 px.</p>
                                </div>
                            </div>
                        </div>
                    </section>

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    });
});

document.querySelectorAll('.hc-color-input input[type="color"]').forEach(input => {
    const chip = input.closest('.hc-color-input')?.querySelector('.hc-color-code');

    if (!chip) {
        return;
    }

    input.addEventListener('input', () => {
        chip.textContent = input.value.toUpperCase();
    });
});

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
    configForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitForm = () => this.submit();

        if (!window.Swal) {
            if (window.confirm('Guardar configuracion? Los cambios se aplicaran inmediatamente.')) {
                submitForm();
            }
            return;
        }

        Swal.fire({
            title: 'Guardar configuracion',
            text: 'Los cambios se aplicaran inmediatamente',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17241f',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-save mr-2"></i>Si, guardar',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                submitForm();
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

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.hc-page');
    if (view) {
        view.classList.add('is-ready');
    }
});
</script>
