<?php
/**
 * Helpers de branding basico controlado por hotel.
 */

function hotel_branding($hotelId = null, array $hotel = null) {
    try {
        $model = new HotelBranding();
        return $model->resolverParaHotel($hotelId ? (int) $hotelId : null, $hotel);
    } catch (Throwable $e) {
        error_log('Error en helper hotel_branding: ' . $e->getMessage());
        return [
            'nombre_visual' => $hotel['nombre_comercial'] ?? $hotel['nombre'] ?? 'Medisoft Hoteles',
            'logo_url' => 'img/logo-hotel-san-nicolas2.png',
            'favicon_url' => null,
            'login_background_url' => null,
            'color_primary' => '#9CA777',
            'color_secondary' => '#7A8B5C',
            'color_accent' => '#D4AF37',
            'sidebar_style' => 'default',
            'login_style' => 'default',
            'activo' => 1
        ];
    }
}

function current_hotel_branding() {
    $hotelId = function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null);
    $hotel = [
        'hotel_id' => $hotelId,
        'nombre_comercial' => $_SESSION['hotel_nombre'] ?? null,
        'slug' => $_SESSION['hotel_slug'] ?? null,
    ];

    return hotel_branding($hotelId, $hotel);
}

function hotel_branding_asset_url($path) {
    $path = trim((string) $path);
    if ($path === '' || preg_match('/[\x00-\x1F<>"\']/', $path)) {
        return null;
    }

    $lower = strtolower($path);
    foreach (['javascript:', 'data:', 'vbscript:', 'file:'] as $scheme) {
        if (strpos($lower, $scheme) === 0) {
            return null;
        }
    }

    $scheme = parse_url($path, PHP_URL_SCHEME);
    $urlPath = parse_url($path, PHP_URL_PATH);
    $extension = strtolower(pathinfo((string) $urlPath, PATHINFO_EXTENSION));
    if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'ico'], true)) {
        return null;
    }

    if ($scheme !== null) {
        return in_array(strtolower($scheme), ['http', 'https'], true) ? $path : null;
    }

    $cleanPath = ltrim($path, '/');
    foreach (['uploads/branding/', 'uploads/', 'img/'] as $prefijo) {
        if (strpos($cleanPath, $prefijo) === 0) {
            return function_exists('asset') ? asset($cleanPath) : '/' . $cleanPath;
        }
    }

    return null;
}

function hotel_branding_css_vars(array $branding = null) {
    $branding = $branding ?: hotel_branding();
    $primary = hotel_branding_hex($branding['color_primary'] ?? null, '#9CA777');
    $secondary = hotel_branding_hex($branding['color_secondary'] ?? null, '#7A8B5C');
    $accent = hotel_branding_hex($branding['color_accent'] ?? null, '#D4AF37');

    return '<style id="hotel-branding-vars">:root{'
        . '--brand-primary:' . $primary . ';'
        . '--brand-secondary:' . $secondary . ';'
        . '--brand-accent:' . $accent . ';'
        . '--ms-primary:' . $primary . ';'
        . '--ms-secondary:' . $secondary . ';'
        . '--ms-accent:' . $accent . ';'
        . '}</style>';
}

function hotel_branding_hex($color, $fallback) {
    $color = trim((string) $color);
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : $fallback;
}

function hotel_branding_public_name(array $branding = null, $fallback = 'Medisoft Hoteles') {
    $nombre = trim((string) ($branding['nombre_visual'] ?? ''));
    return $nombre !== '' ? $nombre : $fallback;
}
