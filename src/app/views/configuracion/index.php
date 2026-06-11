<?php
/**
 * Vista de configuración - Versión compacta
 * Vista hotelera
 */
$configHotel = is_array($config['hotel'] ?? null) ? $config['hotel'] : [];
$configBranding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
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
$configHotelColors = [
    ['Principal', function_exists('hotel_branding_hex') ? hotel_branding_hex($configBranding['color_primary'] ?? null, '#1B2746') : ($configBranding['color_primary'] ?? '#1B2746')],
    ['Secundario', function_exists('hotel_branding_hex') ? hotel_branding_hex($configBranding['color_secondary'] ?? null, '#0F172A') : ($configBranding['color_secondary'] ?? '#0F172A')],
    ['Acento', function_exists('hotel_branding_hex') ? hotel_branding_hex($configBranding['color_accent'] ?? null, '#BD9441') : ($configBranding['color_accent'] ?? '#BD9441')],
];
$configReadHotelSetting = function ($key, $default) {
    return function_exists('hotel_setting') ? hotel_setting($key, $default) : $default;
};
$configDisplayValue = function ($value) {
    $value = trim((string) $value);
    return $value !== '' ? $value : 'No configurado';
};
$configOperativaHotel = [
    [
        'label' => 'Hora de check-in',
        'icon' => 'fas fa-sign-in-alt',
        'value' => $configDisplayValue($configReadHotelSetting('operacion.checkin_hora', '15:00')),
    ],
    [
        'label' => 'Hora de check-out',
        'icon' => 'fas fa-sign-out-alt',
        'value' => $configDisplayValue($configReadHotelSetting('operacion.checkout_hora', '12:00')),
    ],
    [
        'label' => 'Moneda',
        'icon' => 'fas fa-coins',
        'value' => $configDisplayValue($configReadHotelSetting('operacion.moneda', 'MXN')),
    ],
    [
        'label' => 'Telefono',
        'icon' => 'fas fa-phone-alt',
        'value' => $configDisplayValue($configReadHotelSetting('contacto.telefono', '')),
    ],
    [
        'label' => 'Direccion',
        'icon' => 'fas fa-map-marker-alt',
        'value' => $configDisplayValue($configReadHotelSetting('contacto.direccion', '')),
    ],
    [
        'label' => 'Nombre app/PWA',
        'icon' => 'fas fa-mobile-alt',
        'value' => $configDisplayValue($configReadHotelSetting('pwa.nombre_app', 'Medisoft Hoteles')),
    ],
];
?>

<!-- Estilos críticos inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.config-view { opacity: 0; transition: opacity 0.3s ease; }
.config-view.loaded { opacity: 1; }
.form-input {
    transition: all 0.3s ease;
    border: 1px solid #d1d5db;
}
.form-input:focus {
    border-color: var(--hotel-brown);
    box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
    outline: none;
}
.stat-card {
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    border: 1px solid #e5e7eb;
}
.config-section {
    border-left: 3px solid var(--hotel-gold);
}

/* Color balance: use hotel brand as accent, keep settings readable in neutral ink. */
.config-view {
    --cfg-brand: var(--brand-primary, #1B2746);
    --cfg-brand-2: var(--brand-secondary, #0F172A);
    --cfg-accent: var(--brand-accent, #BD9441);
    --cfg-heading: #111827;
    --cfg-text: #1F2937;
    --cfg-muted: #667085;
    --cfg-bg: #F8F5ED;
    --cfg-bg-2: #FBFAF6;
    --cfg-surface: #FFFFFF;
    --cfg-surface-warm: color-mix(in srgb, var(--cfg-accent) 3%, #FFFFFF);
    --cfg-line: color-mix(in srgb, var(--cfg-brand) 6%, #E7E1D4);
    --cfg-line-soft: color-mix(in srgb, var(--cfg-brand) 4%, #F0ECE2);
    --cfg-shadow: 0 1px 2px rgba(17,24,39,.035), 0 14px 30px -24px rgba(17,24,39,.34);
    background:
        radial-gradient(circle at 10% 0%, color-mix(in srgb, var(--cfg-accent) 6%, transparent) 0, transparent 26%),
        radial-gradient(circle at 92% 2%, color-mix(in srgb, var(--cfg-brand) 4%, transparent) 0, transparent 24%),
        linear-gradient(180deg, var(--cfg-bg-2), #FFFFFF 46%, var(--cfg-bg)) !important;
    color: var(--cfg-text);
}

.config-view > div:first-child {
    background: transparent !important;
    color: var(--cfg-heading) !important;
    border-bottom: 1px solid var(--cfg-line);
    box-shadow: none !important;
}

.config-view > div:first-child h1,
.config-view h2,
.config-view h3,
.config-view h4,
.config-view .text-gray-900,
.config-view .text-gray-800,
.config-view .text-gray-700 {
    color: var(--cfg-heading) !important;
}

.config-view > div:first-child h1 i {
    color: color-mix(in srgb, var(--cfg-brand) 70%, var(--cfg-heading)) !important;
}

.config-view > div:first-child h1 span,
.config-view .text-gray-600,
.config-view .text-gray-500,
.config-view .text-gray-400 {
    color: var(--cfg-muted) !important;
}

.config-view > div:first-child a[href*="configuracion/backup"] {
    background: var(--cfg-surface) !important;
    border: 1px solid var(--cfg-line) !important;
    color: var(--cfg-heading) !important;
    box-shadow: 0 10px 22px -20px rgba(17,24,39,.45);
}

.config-view > div:first-child a[href*="configuracion/backup"]:hover {
    background: color-mix(in srgb, var(--cfg-accent) 6%, #FFFFFF) !important;
    border-color: color-mix(in srgb, var(--cfg-accent) 18%, var(--cfg-line)) !important;
}

.config-view .stat-card,
.config-view .bg-white.rounded-lg.shadow-sm,
.config-view .bg-gray-100.rounded-lg,
.config-view .bg-gray-50.rounded-md {
    background: var(--cfg-surface) !important;
    border: 1px solid var(--cfg-line) !important;
    box-shadow: var(--cfg-shadow) !important;
}

.config-view .stat-card {
    border-radius: 14px !important;
}

.config-view .stat-card:nth-child(1) > div > div:last-child,
.config-view .stat-card:nth-child(2) > div > div:last-child,
.config-view .stat-card:nth-child(3) > div > div:last-child,
.config-view .stat-card:nth-child(4) > div > div:last-child {
    background: color-mix(in srgb, var(--cfg-brand) 5%, #FFFFFF) !important;
    border: 1px solid var(--cfg-line);
    color: var(--cfg-heading) !important;
}

.config-view .stat-card:nth-child(2) > div > div:last-child,
.config-view .stat-card:nth-child(4) > div > div:last-child {
    background: #E8F4ED !important;
}

.config-view .stat-card:nth-child(3) > div > div:last-child {
    background: #F3F0FA !important;
}

.config-view .stat-card i,
.config-view .text-hotel-brown,
.config-view .text-blue-600,
.config-view .text-purple-600 {
    color: var(--cfg-heading) !important;
}

.config-view .text-emerald-600,
.config-view .stat-card:nth-child(2) i,
.config-view .stat-card:nth-child(4) i {
    color: #276749 !important;
}

.config-view .bg-gray-50,
.config-view .bg-gray-100,
.config-view .bg-gray-200 {
    background-color: color-mix(in srgb, var(--cfg-brand) 3%, #FFFFFF) !important;
}

.config-view .border-gray-200,
.config-view .border-gray-300 {
    border-color: var(--cfg-line) !important;
}

.config-view .config-section {
    border-left-color: color-mix(in srgb, var(--cfg-accent) 55%, var(--cfg-line)) !important;
}

.config-view label,
.config-view .text-xs.font-semibold {
    color: var(--cfg-text) !important;
}

.config-view .form-input {
    background: var(--cfg-surface) !important;
    border-color: var(--cfg-line) !important;
    color: var(--cfg-heading) !important;
    box-shadow: inset 0 1px 0 rgba(17,24,39,.02);
}

.config-view .form-input:focus {
    border-color: color-mix(in srgb, var(--cfg-accent) 44%, var(--cfg-line)) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--cfg-accent) 18%, transparent) !important;
}

.config-view input[type="checkbox"] {
    accent-color: var(--cfg-heading);
}

.config-view .rounded-lg.border.bg-gray-50,
.config-view .inline-flex.rounded-md {
    background: var(--cfg-surface-warm) !important;
    border-color: var(--cfg-line) !important;
    color: var(--cfg-heading) !important;
}

.config-view a[href*="dashboard"] {
    background: #F7F7F8 !important;
    border: 1px solid #E4E7EC !important;
    color: var(--cfg-text) !important;
}

.config-view a[href*="dashboard"]:hover {
    background: #FFFFFF !important;
    border-color: var(--cfg-line) !important;
}

.config-view button[type="submit"] {
    background: var(--cfg-heading) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 22px -18px rgba(17,24,39,.7) !important;
}

.config-view button[type="submit"]:hover {
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--cfg-accent) 48%, #111827) !important;
}

.config-view a:focus-visible,
.config-view button:focus-visible,
.config-view input:focus-visible,
.config-view select:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--cfg-accent) 22%, transparent) !important;
    outline-offset: 2px;
}
</style>

<div class="config-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-cog text-lg opacity-80"></i>
                        Configuración del Sistema
                        <span class="text-hotel-gold text-sm font-normal ml-2"><?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></span>
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= url('configuracion/backup') ?>"
                       class="bg-white/10 backdrop-blur text-white px-3 py-1.5 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-1.5 border border-white/20 text-sm">
                        <i class="fas fa-database text-xs"></i>
                        <span>Respaldos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <!-- Tarjetas de Estado del Sistema -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <!-- Último Respaldo -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Último Respaldo</p>
                        <?php if ($ultimo_backup): ?>
                            <p class="text-sm font-semibold text-gray-900">
                                <?= format_datetime($ultimo_backup['fecha']) ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <?= $ultimo_backup['tamano'] ?>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Sin respaldos</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-blue-100 p-2.5 rounded-lg">
                        <i class="fas fa-database text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Espacio en Disco -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Espacio Usado</p>
                        <p class="text-sm font-semibold text-gray-900">
                            <?= format_file_size($espacio['usado']) ?>
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                            <div class="bg-emerald-600 h-1 rounded-full" style="width: <?= round(($espacio['usado'] / $espacio['total']) * 100) ?>%"></div>
                        </div>
                    </div>
                    <div class="bg-emerald-100 p-2.5 rounded-lg">
                        <i class="fas fa-hdd text-emerald-600"></i>
                    </div>
                </div>
            </div>

            <!-- Usuarios Activos -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Usuarios Activos</p>
                        <p class="text-sm font-semibold text-gray-900">
                            <?= $usuarios_activos ?? 0 ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">En línea ahora</p>
                    </div>
                    <div class="bg-purple-100 p-2.5 rounded-lg">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                </div>
            </div>

            <!-- Estado del Sistema -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Estado del Sistema</p>
                        <p class="text-sm font-semibold text-emerald-600">
                            Operativo
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">Todos los servicios OK</p>
                    </div>
                    <div class="bg-emerald-100 p-2.5 rounded-lg">
                        <i class="fas fa-check-circle text-emerald-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Configuración -->
        <!-- Configuracion basica del hotel cliente -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex flex-col lg:flex-row gap-4 lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="w-16 h-16 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
                        <?php if ($configHotelLogo): ?>
                            <img src="<?= htmlspecialchars($configHotelLogo, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="<?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>"
                                 class="w-full h-full object-contain p-2">
                        <?php else: ?>
                            <i class="fas fa-hotel text-2xl text-hotel-brown"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Configuracion de su hotel</p>
                        <h2 class="text-lg font-bold text-gray-900 mt-0.5">
                            <?= htmlspecialchars($configHotelNombre, ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Datos basicos que alimentan encabezados, tickets, PDFs y la identidad visual del sistema.
                        </p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <?php if ($configHotelId): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                    <i class="fas fa-hashtag text-gray-400"></i>
                                    Hotel <?= (int) $configHotelId ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($configHotelSlug): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                    <i class="fas fa-link text-gray-400"></i>
                                    <?= htmlspecialchars($configHotelSlug, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 lg:min-w-[420px]">
                    <?php foreach ($configHotelColors as [$label, $color]): ?>
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-md border border-black/10 shadow-sm"
                                      style="background: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>;"></span>
                                <div>
                                    <p class="text-[11px] text-gray-500"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="text-xs font-mono text-gray-800"><?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-4">
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs text-gray-500 mb-1">Contacto</p>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($configHotel['telefono'] ?? 'Sin telefono', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs text-gray-500 mb-1">Correo</p>
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($configHotel['email'] ?? 'Sin correo', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs text-gray-500 mb-1">Check-in</p>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($configHotel['check_in_time'] ?? '15:00', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                    <p class="text-xs text-gray-500 mb-1">Check-out</p>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($configHotel['check_out_time'] ?? '12:00', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </div>

        <!-- Configuracion operativa tenant-safe de solo lectura -->
        <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Solo lectura</p>
                    <h3 class="text-base font-semibold text-gray-900 mt-0.5 flex items-center gap-2 config-section pl-3">
                        <i class="fas fa-sliders-h text-hotel-brown text-sm"></i>
                        Configuracion operativa del hotel
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Valores leidos desde la capa tenant-safe. Todavia no se editan desde esta pantalla.
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium self-start">
                    <i class="fas fa-lock text-gray-400"></i>
                    Registry hotel_configuracion
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($configOperativaHotel as $item): ?>
                    <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center shrink-0">
                                <i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?> text-hotel-brown text-sm"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-500 mb-1">
                                    <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="text-sm font-semibold text-gray-800 break-words">
                                    <?= htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" action="<?= url('configuracion/actualizar') ?>" id="configForm">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Columna Izquierda -->
                <div class="space-y-4">
                    <!-- Información del Hotel -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-building text-hotel-brown text-sm"></i>
                            Información del Hotel
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Nombre del Hotel
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-hotel"></i>
                                    </span>
                                    <input type="text"
                                           name="hotel_nombre"
                                           value="<?= $config['hotel']['nombre'] ?? (function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles') ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Teléfono
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel"
                                           name="hotel_telefono"
                                           value="<?= $config['hotel']['telefono'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="(555) 123-4567">
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Dirección
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <input type="text"
                                           name="hotel_direccion"
                                           value="<?= $config['hotel']['direccion'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="Calle, Número, Colonia">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Email
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email"
                                           name="hotel_email"
                                           value="<?= $config['hotel']['email'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="hotel@ejemplo.com">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Horas por Estancia
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                    <input type="number"
                                           name="hotel_horas_estancia"
                                           value="<?= $config['hotel']['horas_estancia'] ?? 12 ?>"
                                           min="1" max="48"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Horarios -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Horarios</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">
                                        Check-in
                                    </label>
                                    <input type="time"
                                           name="hotel_check_in_time"
                                           value="<?= $config['hotel']['check_in_time'] ?? '15:00' ?>"
                                           class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">
                                        Check-out
                                    </label>
                                    <input type="time"
                                           name="hotel_check_out_time"
                                           value="<?= $config['hotel']['check_out_time'] ?? '12:00' ?>"
                                           class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Tarifas -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-dollar-sign text-hotel-brown text-sm"></i>
                            Configuración de Tarifas
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Incremento Fin de Semana (%)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-percentage"></i>
                                    </span>
                                    <input type="number"
                                           name="tarifas_incremento_fin_semana"
                                           value="<?= $config['tarifas']['incremento_fin_semana'] ?? 0 ?>"
                                           min="0" max="100" step="0.1"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Aplicado viernes, sábado y domingo
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Grupo Mínimo
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <input type="number"
                                               name="tarifas_descuento_grupo_minimo"
                                               value="<?= $config['tarifas']['descuento_grupo_minimo'] ?? 5 ?>"
                                               min="2" max="20"
                                               class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Habitación Gratis
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                            <i class="fas fa-gift"></i>
                                        </span>
                                        <input type="number"
                                               name="tarifas_descuento_grupo_gratis"
                                               value="<?= $config['tarifas']['descuento_grupo_gratis'] ?? 20 ?>"
                                               min="5" max="50"
                                               class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">
                                Una habitación gratis cada X habitaciones reservadas
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div class="space-y-4">
                    <!-- Configuración del Sistema -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-server text-hotel-brown text-sm"></i>
                            Configuración del Sistema
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Duración de Sesión (minutos)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-hourglass-half"></i>
                                    </span>
                                    <input type="number"
                                           name="sistema_session_lifetime"
                                           value="<?= $config['sistema']['session_lifetime'] ?? 120 ?>"
                                           min="15" max="480" step="15"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>

                            <!-- Respaldos Automáticos -->
                            <div class="bg-gray-50 rounded-md p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                               name="sistema_backup_enabled"
                                               value="1"
                                               <?= ($config['sistema']['backup_enabled'] ?? 0) ? 'checked' : '' ?>
                                               class="rounded text-hotel-brown focus:ring-hotel-brown">
                                        <span class="text-xs font-semibold text-gray-700">
                                            Respaldos Automáticos
                                        </span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mt-2">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">
                                            Frecuencia
                                        </label>
                                        <select name="sistema_backup_frequency"
                                                class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                            <option value="daily" <?= ($config['sistema']['backup_frequency'] ?? '') == 'daily' ? 'selected' : '' ?>>Diario</option>
                                            <option value="weekly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                            <option value="monthly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">
                                            Mantener últimos
                                        </label>
                                        <input type="number"
                                               name="sistema_backup_keep_last"
                                               value="<?= $config['sistema']['backup_keep_last'] ?? 4 ?>"
                                               min="1" max="30"
                                               class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventario Automático -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-boxes text-hotel-brown text-sm"></i>
                            Inventario Automático
                        </h3>

                        <div class="space-y-3">
                            <p class="text-xs text-gray-600 mb-2">
                                Cantidad de productos a asignar automáticamente por habitación
                            </p>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Papel Higiénico (rollos)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-toilet-paper"></i>
                                    </span>
                                    <input type="number"
                                           name="inventario_auto_papel_higienico"
                                           value="<?= $config['inventario']['auto_papel_higienico'] ?? 2 ?>"
                                           min="0" max="10"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Jabón (unidades)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-soap"></i>
                                    </span>
                                    <input type="number"
                                           name="inventario_auto_jabon"
                                           value="<?= $config['inventario']['auto_jabon'] ?? 2 ?>"
                                           min="0" max="10"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Sistema -->
                    <div class="bg-gray-100 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle text-gray-600"></i>
                            Información del Sistema
                        </h4>
                        <div class="space-y-1 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Versión del Sistema:</span>
                                <span class="font-mono">v1.0.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>PHP:</span>
                                <span class="font-mono"><?= phpversion() ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Base de Datos:</span>
                                <span class="font-mono">MySQL</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Servidor:</span>
                                <span class="font-mono"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="bg-white rounded-lg shadow-sm p-3 mt-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i>
                        Los cambios se aplicarán inmediatamente
                    </div>

                    <div class="flex gap-2">
                        <a href="<?= url('dashboard') ?>"
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-all duration-300 flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-times text-xs"></i>
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-hotel-brown text-white rounded-md hover:bg-hotel-brown-dark transition-all duration-300 flex items-center gap-2 text-sm font-medium shadow hover:shadow-md">
                            <i class="fas fa-save text-xs"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Formateo de teléfono
document.querySelector('input[name="hotel_telefono"]').addEventListener('input', function(e) {
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
});

// Validación del formulario
document.getElementById('configForm').addEventListener('submit', function(e) {
    e.preventDefault();

    Swal.fire({
        title: '¿Guardar configuración?',
        text: 'Los cambios se aplicarán inmediatamente',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B4423',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Sí, guardar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Toggle de respaldos automáticos
document.querySelector('input[name="sistema_backup_enabled"]').addEventListener('change', function() {
    const backupOptions = this.closest('.bg-gray-50').querySelectorAll('select, input[type="number"]');
    backupOptions.forEach(input => {
        input.disabled = !this.checked;
        input.style.opacity = this.checked ? '1' : '0.5';
    });
});

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.config-view');
    if (view) view.classList.add('loaded');

    // Trigger inicial del toggle
    const backupCheckbox = document.querySelector('input[name="sistema_backup_enabled"]');
    if (backupCheckbox) {
        backupCheckbox.dispatchEvent(new Event('change'));
    }
});
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
