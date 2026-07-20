<?php
// Pagina 403 (Acceso denegado) — white-label, mismo contrato visual que 404.php.
// Renderizada por deny_access_403() (helpers/auth.php) cuando un usuario
// autenticado no tiene el permiso requerido para una seccion (cerrado por
// defecto). Standalone: sin layout (View::render).
$errorBranding = function_exists('current_hotel_branding') ? current_hotel_branding() : null;
if (!is_array($errorBranding)) {
    $errorBranding = (isset($branding) && is_array($branding)) ? $branding : null;
}
$errorHotelNombre = function_exists('hotel_branding_public_name')
    ? hotel_branding_public_name($errorBranding, 'Medisoft Hoteles')
    : 'Medisoft Hoteles';
$errorLogoUrl = function_exists('hotel_branding_asset_url')
    ? (hotel_branding_asset_url($errorBranding['logo_url'] ?? null)
        ?: (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : null))
    : null;
$errorThemeColor = function_exists('hotel_branding_hex')
    ? hotel_branding_hex($errorBranding['color_primary'] ?? null, '#1B2746')
    : '#1B2746';
$errorMensaje = isset($mensaje) && is_string($mensaje) && $mensaje !== ''
    ? $mensaje
    : 'No tienes permiso para ver esta sección.';
/* Tema visual del negocio (multi-diseño): mismo contrato que header.php. */
$errorTema = 'deleite';
if (!empty($errorBranding['tema']) && preg_match('/^[a-z0-9-]{1,30}$/', (string) $errorBranding['tema'])) {
    $errorTema = (string) $errorBranding['tema'];
}
$errorTemaCssHref = null;
if ($errorTema !== 'deleite' && defined('PUBLIC_PATH') && is_file(PUBLIC_PATH . '/css/temas/' . $errorTema . '.css')) {
    $errorTemaCssHref = 'css/temas/' . $errorTema . '.css';
}
?>
<!DOCTYPE html>
<html lang="es"<?= $errorTema !== 'deleite' ? ' data-tema="' . htmlspecialchars($errorTema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>403 - Acceso denegado | <?= htmlspecialchars($errorHotelNombre, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="theme-color" content="<?= htmlspecialchars($errorThemeColor, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Tailwind CSS precompilado (config en tailwind.config.js de la raíz) -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/tailwind.css') : asset('css/tailwind.css') ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Paleta del hotel (white-label) -->
    <?= function_exists('hotel_branding_css_vars') ? hotel_branding_css_vars($errorBranding) : '' ?>

    <style>
        html {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }
        body {
            background:
                radial-gradient(circle at 15% 12%, color-mix(in srgb, var(--brand-accent, #BD9441) 14%, transparent), transparent 30rem),
                radial-gradient(circle at 85% 88%, color-mix(in srgb, var(--brand-primary, #1B2746) 10%, transparent), transparent 32rem),
                var(--brand-surface, #FFFEFB);
        }
        .e403-code {
            background: linear-gradient(135deg, var(--brand-secondary, #0F172A), var(--brand-accent, #BD9441));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .e403-mark {
            background: color-mix(in srgb, var(--brand-accent, #BD9441) 14%, #fff);
            border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 30%, #fff);
        }
    </style>
    <?php if ($errorTemaCssHref): ?>
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version($errorTemaCssHref) : asset($errorTemaCssHref) ?>">
    <?php endif; ?>
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-inter" data-ms-error>
    <div class="w-full max-w-md text-center">
        <?php if ($errorLogoUrl): ?>
        <img src="<?= htmlspecialchars($errorLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($errorHotelNombre, ENT_QUOTES, 'UTF-8') ?>"
             class="mx-auto mb-6 h-14 w-14 rounded-2xl object-contain shadow-sm">
        <?php endif; ?>

        <div class="e403-mark mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl">
            <i class="fas fa-lock text-hotel-gold text-3xl"></i>
        </div>

        <h1 class="e403-code font-playfair text-7xl font-bold leading-none mb-3">403</h1>
        <p class="text-xl font-semibold text-hotel-brown mb-2">Acceso denegado</p>
        <p class="text-hotel-muted mb-8 leading-relaxed">
            <?= htmlspecialchars($errorMensaje, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="flex flex-col sm:flex-row items-stretch justify-center gap-3">
            <a href="<?= url('/') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-hotel-brown px-6 py-3 font-semibold text-white transition duration-300 hover:bg-hotel-brown-dark">
                <i class="fas fa-home"></i>Ir al inicio
            </a>
            <a href="<?= back_url('/') ?>"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-hotel-brown-light bg-white px-6 py-3 font-semibold text-hotel-brown transition duration-300 hover:bg-hotel-cream">
                <i class="fas fa-arrow-left"></i>Regresar
            </a>
        </div>
    </div>
</body>
</html>
