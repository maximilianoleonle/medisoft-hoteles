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

?>

<style>
.config-view {
    --cfg-brand: var(--brand-primary, #1B2746);
    --cfg-brand-2: var(--brand-secondary, #0F172A);
    --cfg-accent: var(--brand-accent, #BD9441);
    --cfg-accent-dark: color-mix(in srgb, var(--cfg-accent) 72%, #31230D);
    --cfg-bg: #F6F2EA;
    --cfg-bg-2: #FBF8F2;
    --cfg-surface: rgba(255, 255, 255, .96);
    --cfg-surface-soft: color-mix(in srgb, var(--cfg-brand) 4%, #FFFFFF);
    --cfg-surface-warm: color-mix(in srgb, var(--cfg-accent) 6%, #FFFFFF);
    --cfg-line: color-mix(in srgb, var(--cfg-brand) 11%, #E7E1D4);
    --cfg-line-strong: color-mix(in srgb, var(--cfg-brand) 20%, #D8CCBA);
    --cfg-text: #17233E;
    --cfg-muted: #667085;
    --cfg-good: #16824E;
    --cfg-shadow: 0 1px 2px rgba(23, 35, 62, .04), 0 18px 42px -34px rgba(23, 35, 62, .42);
    min-height: 100vh;
    background:
        linear-gradient(135deg, rgba(255,255,255,.42) 0 25%, transparent 25% 50%) 0 0 / 24px 24px,
        linear-gradient(180deg, var(--cfg-bg-2), var(--cfg-bg));
    color: var(--cfg-text);
    font-family: Manrope, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    opacity: 0;
    transition: opacity .22s ease;
}

.config-view.loaded {
    opacity: 1;
}

.config-shell {
    width: min(1540px, calc(100% - 28px));
    margin: 0 auto;
    padding: 28px 0 46px;
}

.config-hero,
.config-panel,
.config-status-card,
.config-actions {
    background: var(--cfg-surface);
    border: 1px solid var(--cfg-line);
    box-shadow: var(--cfg-shadow);
}

.config-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
    gap: 14px;
    border-radius: 18px;
    padding: 14px;
}

.config-hero-main {
    min-height: 230px;
    border-radius: 14px;
    padding: 28px;
    background: linear-gradient(135deg, var(--cfg-brand), var(--cfg-brand-2));
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.config-kicker,
.config-label,
.config-section-kicker,
.config-meta-label {
    margin: 0;
    color: var(--cfg-muted);
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: 0;
    text-transform: uppercase;
}

.config-hero-main .config-kicker {
    color: color-mix(in srgb, var(--cfg-accent) 78%, #FFFFFF);
}

.config-title {
    margin: 10px 0 0;
    max-width: 760px;
    color: #FFFFFF;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 3.6rem;
    line-height: .95;
    font-weight: 700;
    letter-spacing: 0;
}

.config-subtitle {
    max-width: 760px;
    margin: 14px 0 0;
    color: rgba(255, 255, 255, .78);
    font-size: .96rem;
    line-height: 1.55;
    font-weight: 650;
}

.config-hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 22px;
}

.config-tag,
.config-readonly-badge,
.config-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    border-radius: 999px;
    padding: 0 11px;
    font-size: .76rem;
    font-weight: 800;
}

.config-tag {
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .16);
    color: #FFFFFF;
}

.config-hero-card {
    border-radius: 14px;
    padding: 18px;
    background: var(--cfg-surface-warm);
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 20%, var(--cfg-line));
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 20px;
}

.config-hotel-top {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.config-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    border: 1px solid var(--cfg-line);
    background: #FFFFFF;
    display: grid;
    place-items: center;
    overflow: hidden;
    flex: 0 0 auto;
}

.config-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

.config-hotel-name {
    margin: 3px 0 0;
    color: var(--cfg-text);
    font-size: 1.35rem;
    font-weight: 900;
    line-height: 1.12;
}

.config-hotel-note {
    margin: 7px 0 0;
    color: var(--cfg-muted);
    font-size: .86rem;
    line-height: 1.45;
    font-weight: 650;
}

.config-backup-link,
.config-cancel-link,
.config-submit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    border-radius: 12px;
    padding: 0 14px;
    font-size: .84rem;
    font-weight: 850;
    text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease;
}

.config-backup-link {
    width: 100%;
    background: var(--cfg-brand);
    color: #FFFFFF;
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--cfg-brand) 70%, transparent);
}

.config-backup-link:hover,
.config-submit-btn:hover,
.config-cancel-link:hover {
    transform: translateY(-1px);
}

.config-section {
    margin-top: 16px;
}

.config-status-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.config-status-card {
    border-radius: 14px;
    padding: 15px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 42px;
    gap: 12px;
    align-items: center;
}

.config-status-icon,
.config-section-icon,
.config-oper-icon,
.config-field-icon {
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.config-status-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--cfg-surface-warm);
    color: var(--cfg-accent-dark);
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 18%, var(--cfg-line));
}

.config-status-value {
    margin: 3px 0 0;
    color: var(--cfg-text);
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.18;
}

.config-status-detail {
    margin: 4px 0 0;
    color: var(--cfg-muted);
    font-size: .78rem;
    font-weight: 650;
}

.config-panel {
    border-radius: 16px;
    padding: 18px;
}

.config-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.config-panel-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: var(--cfg-text);
    font-size: 1.12rem;
    line-height: 1.2;
    font-weight: 900;
}

.config-panel-copy {
    max-width: 760px;
    margin: 5px 0 0;
    color: var(--cfg-muted);
    font-size: .86rem;
    line-height: 1.45;
    font-weight: 650;
}

.config-section-icon {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    background: var(--cfg-surface-warm);
    color: var(--cfg-accent-dark);
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 20%, var(--cfg-line));
}

.config-readonly-badge {
    border: 1px solid color-mix(in srgb, var(--cfg-good) 20%, #D9E8DF);
    background: color-mix(in srgb, var(--cfg-good) 10%, #FFFFFF);
    color: #14633E;
    white-space: nowrap;
}

.config-oper-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.config-oper-item {
    min-height: 118px;
    border-radius: 14px;
    border: 1px solid var(--cfg-line);
    background: var(--cfg-surface-soft);
    padding: 14px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.config-oper-item.is-featured {
    background: linear-gradient(180deg, #FFFFFF, var(--cfg-surface-warm));
    border-color: color-mix(in srgb, var(--cfg-accent) 24%, var(--cfg-line));
}

.config-oper-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: #FFFFFF;
    color: var(--cfg-brand);
    border: 1px solid var(--cfg-line);
}

.config-oper-value {
    margin: 4px 0 0;
    color: var(--cfg-text);
    font-size: 1.06rem;
    font-weight: 900;
    line-height: 1.22;
    overflow-wrap: anywhere;
}

.config-oper-detail {
    margin: 3px 0 0;
    color: var(--cfg-muted);
    font-size: .78rem;
    font-weight: 650;
}

.config-hotel-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) minmax(320px, .9fr);
    gap: 14px;
}

.config-info-list {
    display: grid;
    gap: 9px;
}

.config-info-row,
.config-color-row,
.config-system-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    border-bottom: 1px solid var(--cfg-line);
    padding: 10px 0;
}

.config-info-row:last-child,
.config-color-row:last-child,
.config-system-row:last-child {
    border-bottom: 0;
}

.config-info-value,
.config-color-value,
.config-system-value {
    color: var(--cfg-text);
    font-weight: 850;
    text-align: right;
    overflow-wrap: anywhere;
}

.config-swatch {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, .12);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .3);
}

.config-chip {
    background: #FFFFFF;
    border: 1px solid var(--cfg-line);
    color: var(--cfg-text);
}

.config-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(340px, .78fr);
    gap: 14px;
}

.config-column {
    display: grid;
    gap: 14px;
}

.config-field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.config-field {
    display: grid;
    gap: 6px;
}

.config-field.is-wide {
    grid-column: 1 / -1;
}

.config-field label,
.config-field-label {
    color: var(--cfg-text);
    font-size: .78rem;
    font-weight: 850;
}

.config-control-wrap {
    position: relative;
}

.config-field-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    width: 18px;
    height: 18px;
    transform: translateY(-50%);
    color: color-mix(in srgb, var(--cfg-brand) 54%, var(--cfg-muted));
    pointer-events: none;
}

.form-input {
    width: 100%;
    min-height: 40px;
    border-radius: 11px;
    border: 1px solid var(--cfg-line);
    background: #FFFFFF;
    color: var(--cfg-text);
    font-size: .88rem;
    font-weight: 650;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.form-input:focus {
    border-color: color-mix(in srgb, var(--cfg-accent) 45%, var(--cfg-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--cfg-accent) 18%, transparent);
    outline: none;
}

textarea.form-input {
    min-height: 104px;
    resize: vertical;
}

.config-control-wrap .form-input {
    padding-left: 38px;
}

.config-field-hint {
    margin: 0;
    color: var(--cfg-muted);
    font-size: .76rem;
    font-weight: 650;
    line-height: 1.35;
}

.config-brand-grid {
    display: grid;
    grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
    gap: 14px;
}

.config-brand-preview {
    border-radius: 14px;
    border: 1px solid var(--cfg-line);
    background: linear-gradient(135deg, var(--cfg-brand), var(--cfg-brand-2));
    min-height: 230px;
    padding: 18px;
    color: #FFFFFF;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.config-brand-logo {
    width: 86px;
    height: 86px;
    border-radius: 18px;
    background: rgba(255, 255, 255, .94);
    display: grid;
    place-items: center;
    overflow: hidden;
}

.config-brand-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
}

.config-brand-name {
    margin: 18px 0 0;
    font-size: 1.4rem;
    line-height: 1.12;
    font-weight: 900;
}

.config-brand-note {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, .78);
    font-size: .82rem;
    line-height: 1.45;
    font-weight: 700;
}

.config-color-input {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    gap: 8px;
    align-items: center;
}

.config-color-input input[type="color"] {
    width: 48px;
    height: 40px;
    border: 1px solid var(--cfg-line);
    border-radius: 11px;
    background: #FFFFFF;
    padding: 4px;
}

.config-upload-row {
    display: grid;
    grid-template-columns: 54px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}

.config-upload-thumb {
    width: 54px;
    height: 54px;
    border-radius: 13px;
    border: 1px solid var(--cfg-line);
    background: #FFFFFF;
    display: grid;
    place-items: center;
    overflow: hidden;
    color: var(--cfg-muted);
}

.config-upload-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
}

.config-catalog-stack {
    display: grid;
    gap: 16px;
}

.config-catalog-box {
    border-radius: 14px;
    border: 1px solid var(--cfg-line);
    background: var(--cfg-surface-soft);
    padding: 14px;
}

.config-catalog-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}

.config-catalog-title {
    margin: 0;
    color: var(--cfg-text);
    font-size: .95rem;
    font-weight: 900;
}

.config-catalog-list {
    display: grid;
    gap: 8px;
}

.config-catalog-row {
    display: grid;
    gap: 8px;
    align-items: end;
    padding: 10px;
    border-radius: 12px;
    background: #FFFFFF;
    border: 1px solid var(--cfg-line);
}

.config-catalog-row.is-type {
    grid-template-columns: minmax(110px, .8fr) minmax(150px, 1fr) 88px 116px minmax(160px, 1.2fr) 76px;
}

.config-catalog-row.is-floor {
    grid-template-columns: 96px minmax(180px, 1fr) 76px;
}

.config-catalog-row.is-amenity {
    grid-template-columns: minmax(120px, .9fr) minmax(180px, 1fr) 76px;
}

.config-catalog-row.is-simple {
    grid-template-columns: minmax(120px, .9fr) minmax(180px, 1fr) 76px;
}

.config-catalog-row.is-unit {
    grid-template-columns: minmax(120px, .9fr) minmax(180px, 1fr) 110px 76px;
}

.config-catalog-cell {
    display: grid;
    gap: 5px;
}

.config-catalog-cell label {
    color: var(--cfg-muted);
    font-size: .68rem;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: 0;
}

.config-catalog-add {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    border: 1px solid var(--cfg-line);
    border-radius: 10px;
    padding: 0 11px;
    background: #FFFFFF;
    color: var(--cfg-text);
    font-size: .78rem;
    font-weight: 850;
    cursor: pointer;
}

.config-legacy-panel {
    display: none;
}

.config-divider {
    margin: 14px 0;
    border: 0;
    border-top: 1px solid var(--cfg-line);
}

.config-backup-box {
    border-radius: 14px;
    border: 1px solid var(--cfg-line);
    background: var(--cfg-surface-soft);
    padding: 13px;
}

.config-toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    color: var(--cfg-text);
    font-size: .84rem;
    font-weight: 850;
}

.config-toggle-label input {
    accent-color: var(--cfg-brand);
}

.config-actions {
    position: sticky;
    bottom: 12px;
    z-index: 5;
    margin-top: 14px;
    border-radius: 16px;
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.config-actions-note {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--cfg-muted);
    font-size: .82rem;
    font-weight: 700;
}

.config-actions-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.config-cancel-link {
    border: 1px solid var(--cfg-line);
    background: #FFFFFF;
    color: var(--cfg-text);
}

.config-submit-btn {
    border: 0;
    background: linear-gradient(135deg, var(--cfg-brand), var(--cfg-brand-2));
    color: #FFFFFF;
    cursor: pointer;
    box-shadow: 0 14px 28px -20px color-mix(in srgb, var(--cfg-brand) 80%, transparent);
}

.config-backup-link:focus-visible,
.config-cancel-link:focus-visible,
.config-submit-btn:focus-visible,
.config-view input:focus-visible,
.config-view select:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--cfg-accent) 24%, transparent);
    outline-offset: 3px;
}

@media (max-width: 1100px) {
    .config-hero,
    .config-hotel-grid,
    .config-form-grid,
    .config-brand-grid {
        grid-template-columns: 1fr;
    }

    .config-status-grid,
    .config-oper-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .config-catalog-row.is-type,
    .config-catalog-row.is-floor,
    .config-catalog-row.is-amenity,
    .config-catalog-row.is-simple,
    .config-catalog-row.is-unit {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .config-shell {
        width: min(100% - 20px, 1540px);
        padding-top: 18px;
    }

    .config-hero-main {
        min-height: 0;
        padding: 22px;
    }

    .config-title {
        font-size: 2.35rem;
    }

    .config-status-grid,
    .config-oper-grid,
    .config-field-grid {
        grid-template-columns: 1fr;
    }

    .config-panel-header,
    .config-actions,
    .config-info-row,
    .config-color-row,
    .config-system-row {
        align-items: flex-start;
        flex-direction: column;
    }

    .config-info-value,
    .config-color-value,
    .config-system-value {
        text-align: left;
    }

    .config-actions {
        position: static;
    }

    .config-actions-buttons,
    .config-cancel-link,
    .config-submit-btn {
        width: 100%;
    }
}
/* Redisenio integral de Configuracion: visual nuevo sin alterar contratos del formulario. */
.config-view {
    --cfg-ink: #111827;
    --cfg-ink-2: #273244;
    --cfg-ink-3: #475569;
    --cfg-paper-2: #F8F2E8;
    --cfg-paper-3: #EFE7DA;
    --cfg-brand-readable: color-mix(in srgb, var(--cfg-brand) 78%, #111827);
    --cfg-accent-readable: color-mix(in srgb, var(--cfg-accent) 66%, #3B2D12);
    background:
        radial-gradient(circle at 7% 8%, color-mix(in srgb, var(--cfg-accent) 18%, transparent), transparent 26rem),
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--cfg-brand) 10%, transparent), transparent 30rem),
        linear-gradient(135deg, rgba(255, 255, 255, .32) 0 25%, transparent 25% 50%) 0 0 / 30px 30px,
        linear-gradient(180deg, #FFFCF6 0%, var(--cfg-paper-2) 56%, var(--cfg-paper-3) 100%);
    color: var(--cfg-ink);
}

.config-shell {
    width: min(1620px, calc(100% - 32px));
    padding: 30px 0 54px;
}

.config-hero {
    position: relative;
    overflow: hidden;
    grid-template-columns: minmax(0, 1fr) minmax(330px, 430px);
    gap: 18px;
    border: 0;
    background: transparent;
    box-shadow: none;
    padding: 0;
}

.config-hero-main,
.config-hero-card,
.config-panel,
.config-actions {
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 18%, #DFD4C3);
    box-shadow: 0 1px 0 rgba(255,255,255,.8), 0 28px 72px -58px rgba(17,24,39,.72);
}

.config-hero-main {
    position: relative;
    overflow: hidden;
    min-height: 282px;
    border-radius: 28px;
    padding: clamp(24px, 3vw, 38px);
    background:
        radial-gradient(circle at 86% 4%, color-mix(in srgb, var(--cfg-accent) 24%, transparent), transparent 18rem),
        linear-gradient(135deg, rgba(255,253,248,.98), rgba(246,239,226,.92));
    color: var(--cfg-ink);
}

.config-hero-main::before {
    content: "";
    position: absolute;
    inset: 18px auto 18px 0;
    width: 5px;
    border-radius: 0 999px 999px 0;
    background: linear-gradient(180deg, var(--cfg-accent), color-mix(in srgb, var(--cfg-brand) 70%, var(--cfg-accent)));
}

.config-hero-main .config-kicker,
.config-section-kicker {
    color: var(--cfg-accent-readable);
}

.config-title {
    max-width: 780px;
    color: var(--cfg-ink);
    font-size: clamp(2.7rem, 5.2vw, 5.7rem);
    line-height: .88;
    text-wrap: balance;
}

.config-subtitle {
    max-width: 68ch;
    color: var(--cfg-ink-3);
    font-size: 1rem;
}

.config-tag {
    min-height: 34px;
    border-radius: 12px;
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 24%, #E5DAC9);
    background: rgba(255,255,255,.72);
    color: var(--cfg-ink-2);
}

.config-hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 22px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cfg-accent) 24%, transparent), transparent 16rem),
        linear-gradient(145deg, #FFFFFF, color-mix(in srgb, var(--cfg-accent) 7%, #FFFFFF));
}

.config-hero-card::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 0;
    height: 4px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, var(--cfg-accent), color-mix(in srgb, var(--cfg-brand) 68%, var(--cfg-accent)));
}

.config-logo {
    width: 74px;
    height: 74px;
    border-radius: 20px;
    border-color: color-mix(in srgb, var(--cfg-accent) 22%, var(--cfg-line));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
}

.config-hotel-name,
.config-panel-title,
.config-settings-group-title,
.config-catalog-title {
    color: var(--cfg-ink);
}

.config-hotel-note,
.config-panel-copy,
.config-settings-group-copy,
.config-field-hint,
.config-status-detail,
.config-oper-detail {
    color: var(--cfg-ink-3);
}

.config-backup-link {
    background: #111827;
    box-shadow: none;
}

.config-nav-strip {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    margin: 16px 0;
}

.config-nav-link {
    min-height: 58px;
    display: flex;
    align-items: center;
    gap: 11px;
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 18%, #DFD4C3);
    border-radius: 18px;
    background: rgba(255,255,255,.82);
    color: var(--cfg-ink-2);
    padding: 10px 12px;
    font-weight: 900;
    text-decoration: none;
    box-shadow: 0 16px 42px -36px rgba(17,24,39,.45);
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.config-nav-link i {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: color-mix(in srgb, var(--cfg-accent) 11%, #FFFFFF);
    color: var(--cfg-accent-readable);
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 18%, #E6DAC9);
    flex: 0 0 auto;
}

.config-nav-link span {
    display: block;
    color: var(--cfg-muted);
    font-size: .72rem;
    font-weight: 800;
    margin-top: 2px;
}

.config-nav-link:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--cfg-accent) 42%, #DFD4C3);
    background: #FFFFFF;
}

.config-panel {
    border-radius: 24px;
    padding: clamp(18px, 2vw, 24px);
    background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(255,253,248,.9));
}

.config-intro-panel {
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cfg-accent) 12%, transparent), transparent 12rem),
        rgba(255,255,255,.86);
}

.config-panel-header {
    align-items: center;
    padding-bottom: 14px;
    border-bottom: 1px solid color-mix(in srgb, var(--cfg-accent) 16%, #E6DAC9);
    margin-bottom: 18px;
}

.config-panel-title {
    font-size: clamp(1.05rem, 1.35vw, 1.35rem);
}

.config-section-icon,
.config-settings-group-icon,
.config-oper-icon,
.config-status-icon {
    background: color-mix(in srgb, var(--cfg-accent) 10%, #FFFFFF);
    color: var(--cfg-accent-readable);
    border-color: color-mix(in srgb, var(--cfg-accent) 22%, var(--cfg-line));
}

.config-readonly-badge {
    border-radius: 12px;
    background: color-mix(in srgb, var(--cfg-good) 9%, #FFFFFF);
}

.config-oper-grid {
    grid-template-columns: repeat(6, minmax(0, 1fr));
}

.config-oper-item {
    grid-column: span 2;
    min-height: 146px;
    border-radius: 20px;
    background: #FFFFFF;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.7);
}

.config-oper-item.is-featured {
    grid-column: span 3;
    background:
        radial-gradient(circle at 95% 0%, color-mix(in srgb, var(--cfg-accent) 18%, transparent), transparent 12rem),
        #FFFFFF;
}

.config-oper-value,
.config-status-value,
.config-info-value,
.config-color-value,
.config-system-value {
    color: var(--cfg-ink);
}

.config-field-grid {
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr));
    gap: 14px;
}

.config-field label,
.config-field-label,
.config-catalog-cell label,
.config-toggle-label {
    color: var(--cfg-ink-2);
}

.config-field-icon {
    color: color-mix(in srgb, var(--cfg-accent) 50%, var(--cfg-muted));
}

.form-input {
    min-height: 46px;
    border-radius: 14px;
    border-color: color-mix(in srgb, var(--cfg-accent) 18%, #E1D7C8);
    background: rgba(255,255,255,.94);
    color: var(--cfg-ink);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
}

.form-input:hover {
    border-color: color-mix(in srgb, var(--cfg-accent) 34%, #D7C9B8);
}

.form-input:focus {
    border-color: color-mix(in srgb, var(--cfg-accent) 62%, var(--cfg-line));
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--cfg-accent) 16%, transparent);
}

.config-catalog-stack {
    gap: 18px;
}

.config-catalog-box {
    border-radius: 22px;
    background: linear-gradient(180deg, rgba(255,255,255,.88), rgba(255,253,248,.76));
    padding: 16px;
}

.config-catalog-head {
    padding-bottom: 12px;
    border-bottom: 1px solid color-mix(in srgb, var(--cfg-accent) 14%, #E7DCCB);
}

.config-catalog-row {
    border-radius: 16px;
    padding: 12px;
    background: #FFFFFF;
    border-color: color-mix(in srgb, var(--cfg-accent) 15%, var(--cfg-line));
}

.config-catalog-row.is-type {
    grid-template-columns: minmax(110px, .72fr) minmax(145px, 1fr) 88px 120px minmax(160px, 1.2fr) 86px;
}

.config-catalog-add {
    min-height: 38px;
    border-radius: 13px;
    background: #111827;
    border-color: #111827;
    color: #FFFFFF;
}

.config-catalog-add:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px -22px rgba(17,24,39,.7);
}

.config-brand-grid {
    grid-template-columns: minmax(320px, .78fr) minmax(0, 1.22fr);
    gap: 18px;
}

.config-brand-preview {
    min-height: 310px;
    border-radius: 24px;
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--cfg-accent) 26%, transparent), transparent 16rem),
        linear-gradient(145deg, #111827, color-mix(in srgb, var(--cfg-brand) 70%, #111827));
}

.config-brand-logo {
    border-radius: 22px;
}

.config-chip {
    min-height: 36px;
    border-radius: 12px;
    color: var(--cfg-ink-2);
}

.config-upload-row {
    grid-template-columns: 62px minmax(0, 1fr);
    gap: 12px;
}

.config-upload-thumb {
    width: 62px;
    height: 62px;
    border-radius: 17px;
    background: #FFFFFF;
}

.config-actions {
    bottom: 16px;
    border-radius: 22px;
    padding: 14px;
    background: rgba(255,253,248,.88);
    backdrop-filter: blur(16px) saturate(1.05);
}

.config-actions-note {
    color: var(--cfg-ink-3);
}

.config-cancel-link,
.config-submit-btn {
    min-height: 46px;
    border-radius: 14px;
}

.config-submit-btn {
    background: linear-gradient(135deg, var(--cfg-brand-readable), #111827);
}

.config-backup-link:hover,
.config-submit-btn:hover,
.config-cancel-link:hover,
.config-catalog-add:hover {
    transform: translateY(-1px);
}

@media (max-width: 1180px) {
    .config-nav-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .config-oper-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .config-oper-item,
    .config-oper-item.is-featured {
        grid-column: auto;
    }
}

@media (max-width: 760px) {
    .config-shell {
        width: min(100% - 20px, 1620px);
        padding: 18px 0 34px;
    }

    .config-hero-main,
    .config-hero-card,
    .config-panel {
        border-radius: 20px;
    }

    .config-title {
        font-size: clamp(2.25rem, 12vw, 3.25rem);
    }

    .config-nav-strip,
    .config-oper-grid {
        grid-template-columns: 1fr;
    }

    .config-nav-link {
        min-height: 52px;
    }

    .config-actions {
        position: static;
    }
}

/* Propuesta 2: workspace real para configuracion, mas visible y facil de navegar. */
.config-workspace {
    display: grid;
    grid-template-columns: minmax(260px, 310px) minmax(0, 1fr);
    gap: 22px;
    align-items: start;
    margin-top: 18px;
}

.config-command-panel {
    position: sticky;
    top: 18px;
    display: grid;
    gap: 12px;
    min-width: 0;
}

.config-command-card,
.config-command-help {
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 18%, #DFD4C3);
    border-radius: 24px;
    box-shadow: 0 22px 60px -48px rgba(17,24,39,.72);
}

.config-command-card {
    overflow: hidden;
    padding: 18px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cfg-accent) 28%, transparent), transparent 12rem),
        linear-gradient(145deg, #111827, color-mix(in srgb, var(--cfg-brand) 58%, #111827));
    color: #FFFFFF;
}

.config-command-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.config-command-logo {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 18px;
    background: rgba(255,255,255,.94);
    color: var(--cfg-brand-readable);
    overflow: hidden;
}

.config-command-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

.config-command-brand strong {
    display: block;
    margin-top: 2px;
    color: #FFFFFF;
    font-size: 1rem;
    line-height: 1.15;
    font-weight: 950;
}

.config-command-card .config-section-kicker {
    color: color-mix(in srgb, var(--cfg-accent) 72%, #FFFFFF);
}

.config-command-card p {
    margin: 16px 0 0;
    color: rgba(255,255,255,.72);
    font-size: .84rem;
    line-height: 1.45;
    font-weight: 700;
}

.config-command-panel .config-nav-strip {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
    margin: 0;
}

.config-command-panel .config-nav-link {
    min-height: 62px;
    align-items: center;
    border-radius: 18px;
    background: rgba(255,255,255,.92);
    box-shadow: none;
}

.config-command-panel .config-nav-link strong {
    color: var(--cfg-ink);
    line-height: 1.12;
}

.config-command-panel .config-nav-link:hover {
    transform: translateX(4px);
}

.config-command-help {
    display: grid;
    gap: 9px;
    padding: 14px;
    background: rgba(255,253,248,.82);
}

.config-command-help span {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    color: var(--cfg-ink-3);
    font-size: .78rem;
    line-height: 1.35;
    font-weight: 750;
}

.config-command-help i {
    color: var(--cfg-accent-readable);
    margin-top: 2px;
}

.config-content-flow {
    display: grid;
    gap: 18px;
    min-width: 0;
}

.config-content-flow > .config-section,
.config-content-flow > form.config-section {
    margin-top: 0;
}

.config-content-flow > form.config-section {
    display: grid;
    gap: 18px;
}

.config-content-flow > form.config-section > .config-section,
.config-content-flow > form.config-section > .config-panel {
    margin-top: 0;
}

.config-content-flow .config-panel {
    position: relative;
    overflow: hidden;
}

.config-device-list {
    display: grid;
    gap: 10px;
}

.config-device-row {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 16%, #E7DCCB);
    border-radius: 18px;
    background: rgba(255,255,255,.9);
}

.config-device-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    color: var(--cfg-accent-readable);
    background: color-mix(in srgb, var(--cfg-accent) 10%, #FFFFFF);
}

.config-device-title {
    display: block;
    color: var(--cfg-ink);
    font-weight: 950;
    line-height: 1.2;
}

.config-device-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-top: 5px;
    color: var(--cfg-ink-3);
    font-size: .78rem;
    font-weight: 750;
}

.config-device-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 9px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 950;
}

.config-device-status.is-active {
    background: #DCFCE7;
    color: #166534;
}

.config-device-status.is-inactive {
    background: #F3F4F6;
    color: #4B5563;
}

.config-device-actions {
    display: flex;
    justify-content: flex-end;
}

.config-device-revoke {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 12px;
    border: 1px solid #FECACA;
    border-radius: 12px;
    background: #FEF2F2;
    color: #991B1B;
    font-size: .78rem;
    font-weight: 900;
    cursor: pointer;
}

.config-device-empty {
    padding: 24px;
    border: 1px dashed color-mix(in srgb, var(--cfg-accent) 26%, #D9CDBB);
    border-radius: 18px;
    background: rgba(255,255,255,.72);
    color: var(--cfg-ink-3);
    font-weight: 750;
    text-align: center;
}

@media (max-width: 720px) {
    .config-device-row {
        grid-template-columns: 42px minmax(0, 1fr);
    }

    .config-device-actions {
        grid-column: 1 / -1;
        justify-content: stretch;
    }

    .config-device-form,
    .config-device-revoke {
        width: 100%;
    }
}

.config-content-flow .config-panel::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient(180deg, var(--cfg-accent), color-mix(in srgb, var(--cfg-brand) 62%, var(--cfg-accent)));
    opacity: .82;
}

.config-content-flow .config-panel-header,
.config-content-flow .config-panel > :not(.config-panel-header) {
    position: relative;
    z-index: 1;
}

.config-intro-panel {
    background:
        radial-gradient(circle at 94% 8%, color-mix(in srgb, var(--cfg-accent) 18%, transparent), transparent 14rem),
        linear-gradient(135deg, #FFFFFF, color-mix(in srgb, var(--cfg-accent) 5%, #FFFFFF));
}

.config-start-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.config-start-card {
    min-height: 150px;
    display: grid;
    align-content: start;
    gap: 9px;
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 16%, #E4D8C8);
    border-radius: 18px;
    background: rgba(255,255,255,.82);
    padding: 15px;
}

.config-start-card.is-primary {
    background:
        radial-gradient(circle at 96% 0%, color-mix(in srgb, var(--cfg-accent) 20%, transparent), transparent 10rem),
        #111827;
    color: #FFFFFF;
}

.config-start-card span {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--cfg-accent) 12%, #FFFFFF);
    color: var(--cfg-accent-readable);
}

.config-start-card.is-primary span {
    background: rgba(255,255,255,.12);
    color: color-mix(in srgb, var(--cfg-accent) 72%, #FFFFFF);
    border: 1px solid rgba(255,255,255,.15);
}

.config-start-card strong {
    color: var(--cfg-ink);
    font-size: .94rem;
    font-weight: 950;
    line-height: 1.2;
}

.config-start-card.is-primary strong {
    color: #FFFFFF;
}

.config-start-card p {
    margin: 0;
    color: var(--cfg-ink-3);
    font-size: .8rem;
    line-height: 1.45;
    font-weight: 700;
}

.config-start-card.is-primary p {
    color: rgba(255,255,255,.72);
}

.config-settings-stack {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 380px), 1fr));
    gap: 14px;
}

.config-settings-group {
    border: 1px solid color-mix(in srgb, var(--cfg-accent) 15%, var(--cfg-line));
    border-radius: 20px;
    background: rgba(255,255,255,.78);
    padding: 18px;
}

.config-settings-group:last-child {
    padding-bottom: 18px;
    border-bottom: 1px solid color-mix(in srgb, var(--cfg-accent) 15%, var(--cfg-line));
}

@media (max-width: 1280px) {
    .config-workspace {
        grid-template-columns: 1fr;
    }

    .config-command-panel {
        position: static;
    }

    .config-command-panel .config-nav-strip {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 940px) {
    .config-start-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .config-command-panel .config-nav-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .config-start-grid,
    .config-command-panel .config-nav-strip {
        grid-template-columns: 1fr;
    }

    .config-workspace {
        gap: 14px;
    }
}
</style>

<div class="config-view">
    <main class="config-shell">
        <section class="config-hero" aria-labelledby="config-page-title">
            <div class="config-hero-main">
                <div>
                    <p class="config-kicker">Panel de configuracion</p>
                    <h1 id="config-page-title" class="config-title">Ajustes del hotel</h1>
                    <p class="config-subtitle">
                        Edita datos generales, horarios, contacto y marca visual del hotel desde un lugar seguro por cliente.
                    </p>
                </div>
                <div class="config-hero-tags">
                    <span class="config-tag">
                        <i class="fas fa-hotel"></i>
                        <?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php if ($configHotelId): ?>
                        <span class="config-tag">
                            <i class="fas fa-hashtag"></i>
                            Hotel <?= (int) $configHotelId ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($configHotelSlug): ?>
                        <span class="config-tag">
                            <i class="fas fa-link"></i>
                            <?= htmlspecialchars($configHotelSlug, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="config-hero-card" aria-label="Resumen del hotel">
                <div class="config-hotel-top">
                    <div class="config-logo">
                        <?php if ($configHotelLogo): ?>
                            <img src="<?= htmlspecialchars($configHotelLogo, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <i class="fas fa-hotel text-2xl"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="config-label">Hotel activo</p>
                        <h2 class="config-hotel-name"><?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="config-hotel-note">
                            Identidad, horarios y datos visibles para documentos y operacion diaria.
                        </p>
                    </div>
                </div>
                <span class="config-backup-link">
                    <i class="fas fa-lock"></i>
                    Configuracion por hotel
                </span>
            </aside>
        </section>

        <div class="config-workspace">
            <aside class="config-command-panel" aria-label="Indice de configuracion">
                <section class="config-command-card">
                    <div class="config-command-brand">
                        <span class="config-command-logo">
                            <?php if ($configHotelLogo): ?>
                                <img src="<?= htmlspecialchars($configHotelLogo, ENT_QUOTES, 'UTF-8') ?>"
                                     alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <i class="fas fa-hotel"></i>
                            <?php endif; ?>
                        </span>
                        <div>
                            <p class="config-section-kicker">Centro de control</p>
                            <strong><?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                    <p>Usa este panel para moverte entre ajustes sin perder el boton de guardado.</p>
                </section>

        <nav class="config-nav-strip" aria-label="Secciones de configuracion">
            <a href="#config-operational-readonly-title" class="config-nav-link">
                <i class="fas fa-clipboard-check"></i>
                <strong>Operacion <span>Horarios y contacto</span></strong>
            </a>
            <a href="#config-hotel-settings-title" class="config-nav-link">
                <i class="fas fa-sliders-h"></i>
                <strong>Ajustes <span>Valores por hotel</span></strong>
            </a>
            <?php if (!empty($configNotificationSettingDefinitions)): ?>
                <a href="#config-notifications-title" class="config-nav-link">
                    <i class="fas fa-bell"></i>
                    <strong>Notificaciones <span>Reglas y umbrales</span></strong>
                </a>
                <a href="#config-pwa-devices-title" class="config-nav-link">
                    <i class="fas fa-mobile-screen-button"></i>
                    <strong>Dispositivos <span>Push PWA activos</span></strong>
                </a>
            <?php endif; ?>
            <a href="#config-room-catalog-title" class="config-nav-link">
                <i class="fas fa-bed"></i>
                <strong>Habitaciones <span>Tipos y amenidades</span></strong>
            </a>
            <a href="#config-general-catalog-title" class="config-nav-link">
                <i class="fas fa-layer-group"></i>
                <strong>Catalogos <span>Listas auxiliares</span></strong>
            </a>
            <a href="#config-branding-title" class="config-nav-link">
                <i class="fas fa-palette"></i>
                <strong>Marca <span>Identidad visual</span></strong>
            </a>
        </nav>

                <section class="config-command-help">
                    <span><i class="fas fa-lock"></i> Los valores de solo lectura no modifican el guardado.</span>
                    <span><i class="fas fa-save"></i> Los cambios editables se aplican hasta confirmar.</span>
                </section>
            </aside>

            <div class="config-content-flow">
        <section class="config-panel config-section" aria-labelledby="config-operational-readonly-title">
            <div class="config-panel-header">
                <div>
                    <p class="config-section-kicker">Solo lectura</p>
                    <h2 id="config-operational-readonly-title" class="config-panel-title">
                        <span class="config-section-icon"><i class="fas fa-clipboard-check"></i></span>
                        Configuracion operativa del hotel
                    </h2>
                    <p class="config-panel-copy">
                        Valores leidos desde el registry tenant-safe. Este bloque no permite edicion ni guarda cambios.
                    </p>
                </div>
                <span class="config-readonly-badge">
                    <i class="fas fa-lock"></i>
                    Solo lectura
                </span>
            </div>

            <div class="config-oper-grid">
                <?php foreach ($configOperationalReadOnly as $operationalItem): ?>
                    <article class="config-oper-item<?= !empty($operationalItem['featured']) ? ' is-featured' : '' ?>">
                        <span class="config-oper-icon">
                            <i class="fas <?= htmlspecialchars($operationalItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        </span>
                        <div>
                            <p class="config-meta-label">
                                <?= htmlspecialchars($operationalItem['label'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="config-oper-value">
                                <?= $configOperationalDisplay($operationalItem['value']) ?>
                            </p>
                            <p class="config-oper-detail">
                                <?= htmlspecialchars($operationalItem['detail'], ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <form method="POST" action="<?= url('configuracion/update') ?>" id="configForm" class="config-section" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <section class="config-panel config-intro-panel">
                <div class="config-panel-header">
                    <div>
                        <p class="config-section-kicker">Editable</p>
                        <h2 class="config-panel-title">
                            <span class="config-section-icon"><i class="fas fa-edit"></i></span>
                            Centro de configuracion editable
                        </h2>
                        <p class="config-panel-copy">
                            Ajusta la operacion visible, catalogos y marca del hotel desde una sola pantalla.
                        </p>
                    </div>
                </div>

                <div class="config-start-grid">
                    <article class="config-start-card is-primary">
                        <span><i class="fas fa-sliders-h"></i></span>
                        <strong>Ajustes del hotel</strong>
                        <p>Horarios, contacto, moneda y textos que alimentan pantallas y documentos compatibles.</p>
                    </article>
                    <article class="config-start-card">
                        <span><i class="fas fa-bed"></i></span>
                        <strong>Catalogos operativos</strong>
                        <p>Tipos de habitaciones, pisos, amenidades, zonas, estacionamientos y unidades.</p>
                    </article>
                    <article class="config-start-card">
                        <span><i class="fas fa-palette"></i></span>
                        <strong>Marca del hotel</strong>
                        <p>Logo, colores, estilo de menu, login e iconos de app sin mezclar identidad Medisoft.</p>
                    </article>
                    <article class="config-start-card">
                        <span><i class="fas fa-check-double"></i></span>
                        <strong>Guardado controlado</strong>
                        <p>El formulario conserva sus campos originales y confirma antes de aplicar cambios.</p>
                    </article>
                </div>
            </section>

            <?php if (!empty($configHotelSettingDefinitions)): ?>
                <section class="config-panel config-section" aria-labelledby="config-hotel-settings-title">
                    <div class="config-panel-header">
                        <div>
                            <p class="config-section-kicker">Por hotel</p>
                            <h3 id="config-hotel-settings-title" class="config-panel-title">
                                <span class="config-section-icon"><i class="fas fa-sliders-h"></i></span>
                                Configuracion operativa y textos
                            </h3>
                            <p class="config-panel-copy">
                                Estos valores se guardan por hotel. Los textos de reservacion y documentos son informativos y no cambian calculos ni estados.
                            </p>
                        </div>
                    </div>

                    <div class="config-field-grid">
                        <?php foreach ($configHotelSettingDefinitions as $settingKey => $settingDefinition): ?>
                            <?php
                            $settingInput = $settingDefinition['input'] ?? 'text';
                            $settingType = $settingDefinition['type'] ?? 'string';
                            $settingValue = $configHotelSettings[$settingKey] ?? ($settingDefinition['default'] ?? '');
                            $settingId = 'hotel_config_' . preg_replace('/[^a-z0-9_]+/i', '_', $settingKey);
                            $settingIsWide = $settingInput === 'textarea' || in_array($settingKey, ['contacto.direccion'], true);
                            $settingHtmlType = in_array($settingInput, ['time', 'tel', 'email', 'number'], true) ? $settingInput : 'text';
                            $settingMax = (int) ($settingDefinition['max'] ?? 0);
                            $settingRows = max(2, (int) ($settingDefinition['rows'] ?? 3));
                            ?>
                            <div class="config-field<?= $settingIsWide ? ' is-wide' : '' ?>">
                                <?php if ($settingInput === 'checkbox'): ?>
                                    <input type="hidden"
                                           name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                           value="0">
                                    <label class="config-toggle-label" for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="checkbox"
                                               id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                                               name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                               value="1"
                                               <?= (bool) $settingValue ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($settingDefinition['label'] ?? $settingKey, ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                <?php elseif ($settingInput === 'textarea'): ?>
                                    <label for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($settingDefinition['label'] ?? $settingKey, ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <textarea id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                                              name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                              rows="<?= (int) $settingRows ?>"
                                              class="form-input"
                                              <?= $settingMax > 0 ? 'maxlength="' . (int) $settingMax . '"' : '' ?>><?= htmlspecialchars((string) $settingValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <?php else: ?>
                                    <label for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($settingDefinition['label'] ?? $settingKey, ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                    <input type="<?= htmlspecialchars($settingHtmlType, ENT_QUOTES, 'UTF-8') ?>"
                                           id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                                           name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                           value="<?= htmlspecialchars((string) $settingValue, ENT_QUOTES, 'UTF-8') ?>"
                                            class="form-input"
                                            <?= $settingInput === 'currency' ? 'maxlength="3" style="text-transform:uppercase;"' : '' ?>
                                            <?= $settingInput === 'timezone' ? 'maxlength="80" placeholder="America/Mexico_City"' : '' ?>
                                            <?= $settingMax > 0 && !in_array($settingInput, ['currency', 'timezone'], true) ? 'maxlength="' . (int) $settingMax . '"' : '' ?>>
                                <?php endif; ?>

                                <?php if (!empty($settingDefinition['descripcion'])): ?>
                                    <p class="config-field-hint">
                                        <?= htmlspecialchars($settingDefinition['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($configNotificationSettingDefinitions)): ?>
                <section class="config-panel config-section" aria-labelledby="config-notifications-title">
                    <div class="config-panel-header">
                        <div>
                            <p class="config-section-kicker">Centro de notificaciones</p>
                            <h3 id="config-notifications-title" class="config-panel-title">
                                <span class="config-section-icon"><i class="fas fa-bell"></i></span>
                                Reglas automaticas
                            </h3>
                            <p class="config-panel-copy">
                                Preferencias por hotel para los avisos operativos que aparecen en el dashboard y en notificaciones.
                            </p>
                        </div>
                    </div>

                    <?php
                    $configNotificationGroups = [
                        [
                            'title' => 'Activacion general',
                            'hint' => 'Control maestro de avisos automaticos.',
                            'keys' => $configNotificationGlobalKeys,
                        ],
                        [
                            'title' => 'Reglas operativas',
                            'hint' => 'Tipos de aviso que el dashboard puede crear con datos reales del hotel.',
                            'keys' => $configNotificationRuleKeys,
                        ],
                        [
                            'title' => 'Umbrales',
                            'hint' => 'Valores que cambian cuando un aviso sube de prioridad.',
                            'keys' => $configNotificationThresholdKeys,
                        ],
                    ];
                    ?>

                    <div class="config-catalog-stack">
                        <?php foreach ($configNotificationGroups as $notificationGroup): ?>
                            <div class="config-catalog-box">
                                <div class="config-catalog-head">
                                    <div>
                                        <h4 class="config-catalog-title">
                                            <?= htmlspecialchars($notificationGroup['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </h4>
                                        <p class="config-field-hint">
                                            <?= htmlspecialchars($notificationGroup['hint'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="config-field-grid">
                                    <?php foreach ($notificationGroup['keys'] as $settingKey): ?>
                                        <?php
                                        if (empty($configNotificationSettingDefinitions[$settingKey])) {
                                            continue;
                                        }

                                        $settingDefinition = $configNotificationSettingDefinitions[$settingKey];
                                        $settingInput = $settingDefinition['input'] ?? 'text';
                                        $settingValue = $configHotelSettings[$settingKey] ?? ($settingDefinition['default'] ?? '');
                                        $settingId = 'hotel_config_' . preg_replace('/[^a-z0-9_]+/i', '_', $settingKey);
                                        $settingHtmlType = $settingInput === 'number' ? 'number' : 'text';
                                        $settingMin = $settingDefinition['min_value'] ?? null;
                                        $settingMaxValue = $settingDefinition['max_value'] ?? null;
                                        $settingStep = $settingDefinition['step'] ?? null;
                                        ?>
                                        <div class="config-field">
                                            <?php if ($settingInput === 'checkbox'): ?>
                                                <input type="hidden"
                                                       name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                                       value="0">
                                                <label class="config-toggle-label" for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="checkbox"
                                                           id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                                                           name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                                           value="1"
                                                           <?= (bool) $settingValue ? 'checked' : '' ?>>
                                                    <?= htmlspecialchars($settingDefinition['label'] ?? $settingKey, ENT_QUOTES, 'UTF-8') ?>
                                                </label>
                                            <?php else: ?>
                                                <label for="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($settingDefinition['label'] ?? $settingKey, ENT_QUOTES, 'UTF-8') ?>
                                                </label>
                                                <input type="<?= htmlspecialchars($settingHtmlType, ENT_QUOTES, 'UTF-8') ?>"
                                                       id="<?= htmlspecialchars($settingId, ENT_QUOTES, 'UTF-8') ?>"
                                                       name="hotel_config[<?= htmlspecialchars($settingKey, ENT_QUOTES, 'UTF-8') ?>]"
                                                       value="<?= htmlspecialchars((string) $settingValue, ENT_QUOTES, 'UTF-8') ?>"
                                                       class="form-input"
                                                       <?= $settingMin !== null ? 'min="' . (int) $settingMin . '"' : '' ?>
                                                       <?= $settingMaxValue !== null ? 'max="' . (int) $settingMaxValue . '"' : '' ?>
                                                       <?= $settingStep !== null ? 'step="' . htmlspecialchars((string) $settingStep, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                            <?php endif; ?>

                                            <?php if (!empty($settingDefinition['descripcion'])): ?>
                                                <p class="config-field-hint">
                                                    <?= htmlspecialchars($settingDefinition['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="config-panel config-section" aria-labelledby="config-room-catalog-title">
                <div class="config-panel-header">
                    <div>
                        <p class="config-section-kicker">Habitaciones</p>
                        <h3 id="config-room-catalog-title" class="config-panel-title">
                            <span class="config-section-icon"><i class="fas fa-bed"></i></span>
                            Catalogos de habitaciones
                        </h3>
                        <p class="config-panel-copy">
                            Opciones que vera el hotel al crear o editar habitaciones. Los codigos se guardan como claves tecnicas.
                        </p>
                    </div>
                </div>

                <div class="config-catalog-stack">
                    <?php $typeRowsForForm = $configAppendBlankRows($configRoomTypeRows, 2); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Tipos o categorias</h4>
                                <p class="config-field-hint">Ejemplo de codigo: sencilla_lujo. Usa letras, numeros y guion bajo.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="types">
                                <i class="fas fa-plus"></i>
                                Agregar tipo
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="types" data-next-index="<?= count($typeRowsForForm) ?>">
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
                                <div class="config-catalog-row is-type" data-catalog-row="types">
                                    <div class="config-catalog-cell">
                                        <label>Codigo</label>
                                        <input type="text"
                                               name="room_catalog[types][<?= $index ?>][codigo]"
                                               value="<?= htmlspecialchars($typeCode, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="20"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Nombre</label>
                                        <input type="text"
                                               name="room_catalog[types][<?= $index ?>][nombre]"
                                               value="<?= htmlspecialchars($typeName, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="50"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Capacidad</label>
                                        <input type="number"
                                               name="room_catalog[types][<?= $index ?>][capacidad_default]"
                                               value="<?= htmlspecialchars($typeCapacity, ENT_QUOTES, 'UTF-8') ?>"
                                               min="1"
                                               max="50"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Precio base</label>
                                        <input type="number"
                                               name="room_catalog[types][<?= $index ?>][precio_base_default]"
                                               value="<?= htmlspecialchars($typePrice, ENT_QUOTES, 'UTF-8') ?>"
                                               min="0"
                                               step="0.01"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Descripcion</label>
                                        <input type="text"
                                               name="room_catalog[types][<?= $index ?>][descripcion]"
                                               value="<?= htmlspecialchars($typeDescription, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="255"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="room_catalog[types][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="room_catalog[types][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $typeActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php $floorRowsForForm = $configAppendBlankRows($configRoomFloorRows, 1); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Pisos o niveles</h4>
                                <p class="config-field-hint">El numero se guarda en la habitacion. Usa negativos para sotanos y evita 0.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="floors">
                                <i class="fas fa-plus"></i>
                                Agregar piso
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="floors" data-next-index="<?= count($floorRowsForForm) ?>">
                            <?php foreach ($floorRowsForForm as $index => $row): ?>
                                <?php
                                $floorValue = (string) ($row['valor'] ?? '');
                                $floorLabel = (string) ($row['label'] ?? '');
                                $floorActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                ?>
                                <div class="config-catalog-row is-floor" data-catalog-row="floors">
                                    <div class="config-catalog-cell">
                                        <label>Numero</label>
                                        <input type="number"
                                               name="room_catalog[floors][<?= $index ?>][valor]"
                                               value="<?= htmlspecialchars($floorValue, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Etiqueta</label>
                                        <input type="text"
                                               name="room_catalog[floors][<?= $index ?>][label]"
                                               value="<?= htmlspecialchars($floorLabel, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="80"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="room_catalog[floors][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="room_catalog[floors][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $floorActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php $amenityRowsForForm = $configAppendBlankRows($configRoomAmenityRows, 1); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Amenidades especiales</h4>
                                <p class="config-field-hint">Estas opciones aparecen como checkboxes en crear y editar habitacion.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="amenities">
                                <i class="fas fa-plus"></i>
                                Agregar amenidad
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="amenities" data-next-index="<?= count($amenityRowsForForm) ?>">
                            <?php foreach ($amenityRowsForForm as $index => $row): ?>
                                <?php
                                $amenityCode = (string) ($row['codigo'] ?? '');
                                $amenityLabel = (string) ($row['label'] ?? '');
                                $amenityActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                ?>
                                <div class="config-catalog-row is-amenity" data-catalog-row="amenities">
                                    <div class="config-catalog-cell">
                                        <label>Codigo</label>
                                        <input type="text"
                                               name="room_catalog[amenities][<?= $index ?>][codigo]"
                                               value="<?= htmlspecialchars($amenityCode, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="30"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Etiqueta</label>
                                        <input type="text"
                                               name="room_catalog[amenities][<?= $index ?>][label]"
                                               value="<?= htmlspecialchars($amenityLabel, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="70"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="room_catalog[amenities][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="room_catalog[amenities][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $amenityActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="config-panel config-section" aria-labelledby="config-general-catalog-title">
                <div class="config-panel-header">
                    <div>
                        <p class="config-section-kicker">Bajo riesgo</p>
                        <h3 id="config-general-catalog-title" class="config-panel-title">
                            <span class="config-section-icon"><i class="fas fa-layer-group"></i></span>
                            Catalogos generales
                        </h3>
                        <p class="config-panel-copy">
                            Listas auxiliares del hotel. En esta fase no modifican caja, reservas ni huespedes.
                        </p>
                    </div>
                </div>

                <div class="config-catalog-stack">
                    <?php $zoneRowsForForm = $configAppendBlankRows($configGeneralZoneRows, 1); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Zonas o areas</h4>
                                <p class="config-field-hint">Areas internas para clasificar ubicaciones futuras sin afectar operacion actual.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="zones">
                                <i class="fas fa-plus"></i>
                                Agregar zona
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="zones" data-next-index="<?= count($zoneRowsForForm) ?>">
                            <?php foreach ($zoneRowsForForm as $index => $row): ?>
                                <?php
                                $zoneCode = (string) ($row['codigo'] ?? '');
                                $zoneLabel = (string) ($row['label'] ?? '');
                                $zoneActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                ?>
                                <div class="config-catalog-row is-simple" data-catalog-row="zones">
                                    <div class="config-catalog-cell">
                                        <label>Codigo</label>
                                        <input type="text"
                                               name="general_catalog[zones][<?= $index ?>][codigo]"
                                               value="<?= htmlspecialchars($zoneCode, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="30"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Etiqueta</label>
                                        <input type="text"
                                               name="general_catalog[zones][<?= $index ?>][label]"
                                               value="<?= htmlspecialchars($zoneLabel, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="80"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="general_catalog[zones][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="general_catalog[zones][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $zoneActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php $parkingRowsForForm = $configAppendBlankRows($configGeneralParkingRows, 1); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Estacionamientos</h4>
                                <p class="config-field-hint">Catalogo preparado para la fase de huespedes y vehiculos.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="parkings">
                                <i class="fas fa-plus"></i>
                                Agregar estacionamiento
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="parkings" data-next-index="<?= count($parkingRowsForForm) ?>">
                            <?php foreach ($parkingRowsForForm as $index => $row): ?>
                                <?php
                                $parkingCode = (string) ($row['codigo'] ?? '');
                                $parkingLabel = (string) ($row['label'] ?? '');
                                $parkingActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                ?>
                                <div class="config-catalog-row is-simple" data-catalog-row="parkings">
                                    <div class="config-catalog-cell">
                                        <label>Codigo</label>
                                        <input type="text"
                                               name="general_catalog[parkings][<?= $index ?>][codigo]"
                                               value="<?= htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="30"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Etiqueta</label>
                                        <input type="text"
                                               name="general_catalog[parkings][<?= $index ?>][label]"
                                               value="<?= htmlspecialchars($parkingLabel, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="80"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="general_catalog[parkings][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="general_catalog[parkings][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $parkingActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php $unitRowsForForm = $configAppendBlankRows($configGeneralUnitRows, 1); ?>
                    <div class="config-catalog-box">
                        <div class="config-catalog-head">
                            <div>
                                <h4 class="config-catalog-title">Unidades de medida</h4>
                                <p class="config-field-hint">Estas opciones alimentan el selector de unidad al crear o editar productos.</p>
                            </div>
                            <button type="button" class="config-catalog-add" data-catalog-add="units">
                                <i class="fas fa-plus"></i>
                                Agregar unidad
                            </button>
                        </div>

                        <div class="config-catalog-list" data-catalog-list="units" data-next-index="<?= count($unitRowsForForm) ?>">
                            <?php foreach ($unitRowsForForm as $index => $row): ?>
                                <?php
                                $unitCode = (string) ($row['codigo'] ?? '');
                                $unitLabel = (string) ($row['label'] ?? '');
                                $unitAbbreviation = (string) ($row['abreviatura'] ?? '');
                                $unitActive = !array_key_exists('activo', $row) || !empty($row['activo']);
                                ?>
                                <div class="config-catalog-row is-unit" data-catalog-row="units">
                                    <div class="config-catalog-cell">
                                        <label>Codigo</label>
                                        <input type="text"
                                               name="general_catalog[units][<?= $index ?>][codigo]"
                                               value="<?= htmlspecialchars($unitCode, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="30"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Etiqueta</label>
                                        <input type="text"
                                               name="general_catalog[units][<?= $index ?>][label]"
                                               value="<?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="80"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Abrev.</label>
                                        <input type="text"
                                               name="general_catalog[units][<?= $index ?>][abreviatura]"
                                               value="<?= htmlspecialchars($unitAbbreviation, ENT_QUOTES, 'UTF-8') ?>"
                                               maxlength="12"
                                               class="form-input">
                                    </div>
                                    <div class="config-catalog-cell">
                                        <label>Activo</label>
                                        <input type="hidden" name="general_catalog[units][<?= $index ?>][activo]" value="0">
                                        <label class="config-toggle-label">
                                            <input type="checkbox"
                                                   name="general_catalog[units][<?= $index ?>][activo]"
                                                   value="1"
                                                   <?= $unitActive ? 'checked' : '' ?>>
                                            Si
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="config-panel config-section" aria-labelledby="config-branding-title">
                <div class="config-panel-header">
                    <div>
                        <p class="config-section-kicker">Marca del hotel</p>
                        <h3 id="config-branding-title" class="config-panel-title">
                            <span class="config-section-icon"><i class="fas fa-palette"></i></span>
                            Marca y apariencia
                        </h3>
                        <p class="config-panel-copy">
                            Esta identidad usa tokens del hotel y se mantiene separada del Panel Medisoft.
                        </p>
                    </div>
                    <span class="config-readonly-badge">
                        <i class="fas fa-shield-alt"></i>
                        --brand-*
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

                <div class="config-brand-grid">
                    <div class="config-brand-preview">
                        <div>
                            <div class="config-brand-logo">
                                <?php if ($brandingLogoPreview): ?>
                                    <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>"
                                         alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>">
                                <?php else: ?>
                                    <i class="fas fa-hotel text-2xl"></i>
                                <?php endif; ?>
                            </div>
                            <p class="config-brand-name">
                                <?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                            <p class="config-brand-note">
                                El logo, colores y estilos se aplican al entorno operativo del hotel.
                            </p>
                        </div>
                        <div class="config-hero-tags">
                            <span class="config-tag">Primary <?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="config-tag">Accent <?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>

                    <div class="config-field-grid">
                        <div class="config-field is-wide">
                            <label for="branding_nombre_visual">Nombre visual</label>
                            <input type="text"
                                   id="branding_nombre_visual"
                                   name="hotel_branding[nombre_visual]"
                                   value="<?= $configBrandingField('nombre_visual', $configHotelNombre) ?>"
                                   maxlength="150"
                                   class="form-input">
                            <p class="config-field-hint">Nombre mostrado en login, encabezado y documentos compatibles.</p>
                        </div>

                        <div class="config-field">
                            <label for="branding_color_primary">Color principal</label>
                            <div class="config-color-input">
                                <input type="color"
                                       id="branding_color_primary"
                                       name="hotel_branding[color_primary]"
                                       value="<?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="config-chip"><?= htmlspecialchars((string) $brandingPrimary, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="config-field">
                            <label for="branding_color_secondary">Color secundario</label>
                            <div class="config-color-input">
                                <input type="color"
                                       id="branding_color_secondary"
                                       name="hotel_branding[color_secondary]"
                                       value="<?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="config-chip"><?= htmlspecialchars((string) $brandingSecondary, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="config-field">
                            <label for="branding_color_accent">Color acento</label>
                            <div class="config-color-input">
                                <input type="color"
                                       id="branding_color_accent"
                                       name="hotel_branding[color_accent]"
                                       value="<?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?>">
                                <span class="config-chip"><?= htmlspecialchars((string) $brandingAccent, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="config-field">
                            <label for="branding_sidebar_style">Estilo de menu</label>
                            <select id="branding_sidebar_style"
                                    name="hotel_branding[sidebar_style]"
                                    class="form-input">
                                <option value="default" <?= ($configBranding['sidebar_style'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                <option value="solid" <?= ($configBranding['sidebar_style'] ?? 'default') === 'solid' ? 'selected' : '' ?>>Solido</option>
                                <option value="dark" <?= ($configBranding['sidebar_style'] ?? 'default') === 'dark' ? 'selected' : '' ?>>Oscuro</option>
                            </select>
                        </div>

                        <div class="config-field">
                            <label for="branding_login_style">Estilo de login</label>
                            <select id="branding_login_style"
                                    name="hotel_branding[login_style]"
                                    class="form-input">
                                <option value="default" <?= ($configBranding['login_style'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                                <option value="soft" <?= ($configBranding['login_style'] ?? 'default') === 'soft' ? 'selected' : '' ?>>Suave</option>
                                <option value="image" <?= ($configBranding['login_style'] ?? 'default') === 'image' ? 'selected' : '' ?>>Imagen</option>
                            </select>
                        </div>

                        <div class="config-field">
                            <label for="logo_file">Logo</label>
                            <div class="config-upload-row">
                                <div class="config-upload-thumb">
                                    <?php if ($brandingLogoPreview): ?>
                                        <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Logo">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input">
                            </div>
                        </div>

                        <div class="config-field">
                            <label for="favicon_file">Favicon</label>
                            <div class="config-upload-row">
                                <div class="config-upload-thumb">
                                    <?php if ($brandingFaviconPreview): ?>
                                        <img src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Favicon">
                                    <?php else: ?>
                                        <i class="fas fa-star"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,image/x-icon,image/png" class="form-input">
                            </div>
                        </div>

                        <div class="config-field is-wide">
                            <label for="login_background_file">Fondo de login</label>
                            <div class="config-upload-row">
                                <div class="config-upload-thumb">
                                    <?php if ($brandingLoginPreview): ?>
                                        <img src="<?= htmlspecialchars($brandingLoginPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Fondo login">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="login_background_file" name="login_background_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="form-input">
                            </div>
                        </div>

                        <div class="config-field">
                            <label for="pwa_icon_192_file">Icono PWA 192</label>
                            <div class="config-upload-row">
                                <div class="config-upload-thumb">
                                    <?php if ($brandingPwa192Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwa192Preview, ENT_QUOTES, 'UTF-8') ?>" alt="PWA 192">
                                    <?php else: ?>
                                        <i class="fas fa-mobile-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="pwa_icon_192_file" name="pwa_icon_192_file" accept=".png,.webp,image/png,image/webp" class="form-input">
                            </div>
                            <p class="config-field-hint">Debe medir exactamente 192x192 px.</p>
                        </div>

                        <div class="config-field">
                            <label for="pwa_icon_512_file">Icono PWA 512</label>
                            <div class="config-upload-row">
                                <div class="config-upload-thumb">
                                    <?php if ($brandingPwa512Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwa512Preview, ENT_QUOTES, 'UTF-8') ?>" alt="PWA 512">
                                    <?php else: ?>
                                        <i class="fas fa-mobile-alt"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="pwa_icon_512_file" name="pwa_icon_512_file" accept=".png,.webp,image/png,image/webp" class="form-input">
                            </div>
                            <p class="config-field-hint">Debe medir exactamente 512x512 px.</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="config-form-grid config-section config-legacy-panel" aria-hidden="true">
                    <div class="config-column">
                        <section class="config-panel" aria-labelledby="config-hotel-form-title">
                            <div class="config-panel-header">
                                <div>
                                    <p class="config-section-kicker">Hotel</p>
                                    <h3 id="config-hotel-form-title" class="config-panel-title">
                                        <span class="config-section-icon"><i class="fas fa-building"></i></span>
                                        Informacion del hotel
                                    </h3>
                                </div>
                            </div>

                            <div class="config-field-grid">
                                <div class="config-field">
                                    <label for="hotel_nombre">Nombre del hotel</label>
                                    <div class="config-control-wrap">
                                        <span class="config-field-icon"><i class="fas fa-hotel"></i></span>
                                        <input type="text"
                                               id="hotel_nombre"
                                               name="hotel_nombre"
                                               value="<?= htmlspecialchars((string) $configLegacyNombre, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-input">
                                    </div>
                                </div>

                                <div class="config-field">
                                    <label for="hotel_telefono">Telefono</label>
                                    <div class="config-control-wrap">
                                        <span class="config-field-icon"><i class="fas fa-phone"></i></span>
                                        <input type="tel"
                                               id="hotel_telefono"
                                               name="hotel_telefono"
                                               value="<?= htmlspecialchars((string) $configLegacyTelefono, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-input"
                                               placeholder="(555) 123-4567">
                                    </div>
                                </div>

                                <div class="config-field is-wide">
                                    <label for="hotel_direccion">Direccion</label>
                                    <div class="config-control-wrap">
                                        <span class="config-field-icon"><i class="fas fa-map-marker-alt"></i></span>
                                        <input type="text"
                                               id="hotel_direccion"
                                               name="hotel_direccion"
                                               value="<?= htmlspecialchars((string) $configLegacyDireccion, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-input"
                                               placeholder="Calle, numero, colonia">
                                    </div>
                                </div>

                                <div class="config-field">
                                    <label for="hotel_email">Email</label>
                                    <div class="config-control-wrap">
                                        <span class="config-field-icon"><i class="fas fa-envelope"></i></span>
                                        <input type="email"
                                               id="hotel_email"
                                               name="hotel_email"
                                               value="<?= htmlspecialchars((string) $configLegacyEmail, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-input"
                                               placeholder="hotel@ejemplo.com">
                                    </div>
                                </div>

                                <div class="config-field">
                                    <label for="hotel_horas_estancia">Horas por estancia</label>
                                    <div class="config-control-wrap">
                                        <span class="config-field-icon"><i class="fas fa-clock"></i></span>
                                        <input type="number"
                                               id="hotel_horas_estancia"
                                               name="hotel_horas_estancia"
                                               value="<?= $config['hotel']['horas_estancia'] ?? 12 ?>"
                                               min="1" max="48"
                                               class="form-input">
                                    </div>
                                </div>
                            </div>

                            <hr class="config-divider">

                            <div class="config-field-grid">
                                <div class="config-field">
                                    <label for="hotel_check_in_time">Check-in</label>
                                    <input type="time"
                                           id="hotel_check_in_time"
                                           name="hotel_check_in_time"
                                           value="<?= htmlspecialchars((string) $configLegacyCheckIn, ENT_QUOTES, 'UTF-8') ?>"
                                           class="form-input">
                                </div>
                                <div class="config-field">
                                    <label for="hotel_check_out_time">Check-out</label>
                                    <input type="time"
                                           id="hotel_check_out_time"
                                           name="hotel_check_out_time"
                                           value="<?= htmlspecialchars((string) $configLegacyCheckOut, ENT_QUOTES, 'UTF-8') ?>"
                                           class="form-input">
                                </div>
                            </div>
                        </section>

                        <input type="hidden"
                               name="tarifas_incremento_fin_semana"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['incremento_fin_semana'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="tarifas_descuento_grupo_minimo"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['descuento_grupo_minimo'] ?? 5), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="tarifas_descuento_grupo_gratis"
                               value="<?= htmlspecialchars((string) ($config['tarifas']['descuento_grupo_gratis'] ?? 20), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="config-column">
                        <section class="config-panel" aria-labelledby="config-sistema-title">
                            <div class="config-panel-header">
                                <div>
                                    <p class="config-section-kicker">Sistema</p>
                                    <h3 id="config-sistema-title" class="config-panel-title">
                                        <span class="config-section-icon"><i class="fas fa-server"></i></span>
                                        Seguridad y respaldos
                                    </h3>
                                </div>
                            </div>

                            <div class="config-field">
                                <label for="sistema_session_lifetime">Duracion de sesion (minutos)</label>
                                <div class="config-control-wrap">
                                    <span class="config-field-icon"><i class="fas fa-hourglass-half"></i></span>
                                    <input type="number"
                                           id="sistema_session_lifetime"
                                           name="sistema_session_lifetime"
                                           value="<?= $config['sistema']['session_lifetime'] ?? 120 ?>"
                                           min="15" max="480" step="15"
                                           class="form-input">
                                </div>
                            </div>

                            <div class="config-backup-box mt-4" data-backup-options>
                                <label class="config-toggle-label">
                                    <input type="checkbox"
                                           name="sistema_backup_enabled"
                                           value="1"
                                           <?= ($config['sistema']['backup_enabled'] ?? 0) ? 'checked' : '' ?>
                                           class="rounded text-hotel-brown focus:ring-hotel-brown">
                                    Respaldos automaticos
                                </label>

                                <div class="config-field-grid mt-3">
                                    <div class="config-field">
                                        <label for="sistema_backup_frequency">Frecuencia</label>
                                        <select id="sistema_backup_frequency"
                                                name="sistema_backup_frequency"
                                                class="form-input">
                                            <option value="daily" <?= ($config['sistema']['backup_frequency'] ?? '') == 'daily' ? 'selected' : '' ?>>Diario</option>
                                            <option value="weekly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                            <option value="monthly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                        </select>
                                    </div>

                                    <div class="config-field">
                                        <label for="sistema_backup_keep_last">Mantener ultimos</label>
                                        <input type="number"
                                               id="sistema_backup_keep_last"
                                               name="sistema_backup_keep_last"
                                               value="<?= $config['sistema']['backup_keep_last'] ?? 4 ?>"
                                               min="1" max="30"
                                               class="form-input">
                                    </div>
                                </div>
                            </div>
                        </section>

                        <input type="hidden"
                               name="inventario_auto_papel_higienico"
                               value="<?= htmlspecialchars((string) ($config['inventario']['auto_papel_higienico'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden"
                               name="inventario_auto_jabon"
                               value="<?= htmlspecialchars((string) ($config['inventario']['auto_jabon'] ?? 2), ENT_QUOTES, 'UTF-8') ?>">

                    </div>
            </div>

            <div class="config-actions">
                <div class="config-actions-note">
                    <i class="fas fa-info-circle"></i>
                    Los cambios editables se aplicaran cuando confirmes el guardado.
                </div>

                <div class="config-actions-buttons">
                    <a href="<?= url('dashboard') ?>" class="config-cancel-link">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="config-submit-btn">
                        <i class="fas fa-save"></i>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>

        <section class="config-panel config-section" aria-labelledby="config-pwa-devices-title">
            <div class="config-panel-header">
                <div>
                    <p class="config-section-kicker">PWA Push</p>
                    <h3 id="config-pwa-devices-title" class="config-panel-title">
                        <span class="config-section-icon"><i class="fas fa-mobile-screen-button"></i></span>
                        Dispositivos con avisos activos
                    </h3>
                    <p class="config-panel-copy">
                        Equipos que aceptaron recibir notificaciones PWA de este hotel. Revocar un dispositivo impide nuevos avisos hasta que se active de nuevo.
                    </p>
                </div>
            </div>

            <?php if (empty($configPwaPushDevices)): ?>
                <div class="config-device-empty">
                    Todavia no hay dispositivos PWA registrados para este hotel.
                </div>
            <?php else: ?>
                <div class="config-device-list">
                    <?php foreach ($configPwaPushDevices as $device): ?>
                        <?php
                        $deviceId = (int)($device['id'] ?? 0);
                        $deviceActive = !empty($device['activo']);
                        $deviceName = $configPwaDeviceName($device);
                        $deviceSeen = $configPwaDate($device['last_seen_at'] ?? null);
                        $deviceCreated = $configPwaDate($device['created_at'] ?? null);
                        ?>
                        <article class="config-device-row">
                            <span class="config-device-icon">
                                <i class="fas fa-mobile-screen-button"></i>
                            </span>
                            <div>
                                <strong class="config-device-title">
                                    <?= htmlspecialchars($deviceName, ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                                <div class="config-device-meta">
                                    <span>
                                        <i class="fas fa-clock"></i>
                                        Ultima actividad: <?= htmlspecialchars($deviceSeen, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar-plus"></i>
                                        Alta: <?= htmlspecialchars($deviceCreated, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="config-device-status <?= $deviceActive ? 'is-active' : 'is-inactive' ?>">
                                        <i class="fas <?= $deviceActive ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                                        <?= $deviceActive ? 'Activo' : 'Revocado' ?>
                                    </span>
                                </div>
                            </div>
                            <div class="config-device-actions">
                                <?php if ($deviceActive && $deviceId > 0): ?>
                                    <form method="POST"
                                          action="<?= url('configuracion/pwa-push/' . $deviceId . '/revocar') ?>"
                                          class="config-device-form">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="config-device-revoke">
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
syncLegacyField('input[name="hotel_config[contacto.direccion]"]', 'input[name="hotel_direccion"]');
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
    });
});

document.querySelectorAll('.config-color-input input[type="color"]').forEach(input => {
    const chip = input.closest('.config-color-input')?.querySelector('.config-chip');

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

        Swal.fire({
            title: 'Guardar configuracion',
            text: 'Los cambios se aplicaran inmediatamente',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17233E',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-save mr-2"></i>Si, guardar',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
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

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.config-view');
    if (view) {
        view.classList.add('loaded');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
