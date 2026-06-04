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
            'logo_url' => hotel_branding_default_logo_path(),
            'favicon_url' => null,
            'login_background_url' => null,
            'pwa_icon_192_url' => null,
            'pwa_icon_512_url' => null,
            'color_primary' => '#9CA777',
            'color_secondary' => '#7A8B5C',
            'color_accent' => '#D4AF37',
            'sidebar_style' => 'default',
            'login_style' => 'default',
            'activo' => 1
        ];
    }
}

function hotel_branding_default_logo_path() {
    return 'img/logo.png';
}

function hotel_branding_default_logo_url() {
    $path = hotel_branding_default_logo_path();
    return function_exists('asset') ? asset($path) : '/' . $path;
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

function hotel_branding_pwa_icon_asset_url($path, $size) {
    $size = (int) $size;
    if (!in_array($size, [192, 512], true)) {
        return null;
    }

    $path = trim((string) $path);
    if ($path === '' || preg_match('/[\x00-\x1F<>"\']/', $path)) {
        return null;
    }

    if (parse_url($path, PHP_URL_SCHEME) !== null) {
        return null;
    }

    $urlPath = parse_url($path, PHP_URL_PATH);
    if (!$urlPath) {
        return null;
    }

    $extension = strtolower(pathinfo($urlPath, PATHINFO_EXTENSION));
    if (!in_array($extension, ['png', 'webp'], true)) {
        return null;
    }

    $cleanPath = ltrim($urlPath, '/');
    if (strpos($cleanPath, 'uploads/branding/') !== 0 && strpos($cleanPath, 'img/icons/') !== 0) {
        return null;
    }

    $absolutePath = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/' . $cleanPath : null;
    if (!$absolutePath || !is_file($absolutePath)) {
        return null;
    }

    $imageInfo = @getimagesize($absolutePath);
    if (!$imageInfo || (int) $imageInfo[0] !== $size || (int) $imageInfo[1] !== $size) {
        return null;
    }

    return function_exists('asset') ? asset($cleanPath) : '/' . $cleanPath;
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

function current_hotel_display_name($fallback = 'Medisoft Hoteles') {
    $fallback = trim((string) $fallback) ?: 'Medisoft Hoteles';

    if (!function_exists('current_hotel_id') || !current_hotel_id()) {
        return $fallback;
    }

    $nombreSesion = function_exists('current_hotel_nombre') ? trim((string) current_hotel_nombre()) : '';
    $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : null;

    return hotel_branding_public_name(is_array($branding) ? $branding : null, $nombreSesion !== '' ? $nombreSesion : $fallback);
}

function hotel_branding_upload_asset(array $file, $hotelSlug, $tipo) {
    $config = hotel_branding_upload_config($tipo);
    if (!$config) {
        return ['success' => false, 'error' => 'Tipo de asset de branding no valido.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'uploaded' => false];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No se pudo recibir el archivo de ' . $config['label'] . '.'];
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!$tmpName || !is_uploaded_file($tmpName)) {
        return ['success' => false, 'error' => 'El archivo de ' . $config['label'] . ' no es una subida valida.'];
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $config['max_size']) {
        return ['success' => false, 'error' => 'El archivo de ' . $config['label'] . ' excede el tamano maximo de ' . hotel_branding_format_bytes($config['max_size']) . '.'];
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === 'svg' || !in_array($extension, $config['extensions'], true)) {
        return ['success' => false, 'error' => 'Extension no permitida para ' . $config['label'] . '.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpName) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mime || !in_array($mime, $config['mimes'], true)) {
        return ['success' => false, 'error' => 'MIME no permitido para ' . $config['label'] . '.'];
    }

    $expectedExtensions = hotel_branding_extensions_for_mime($mime);
    if (!in_array($extension, $expectedExtensions, true)) {
        return ['success' => false, 'error' => 'La extension no coincide con el tipo real del archivo de ' . $config['label'] . '.'];
    }

    $imageInfo = @getimagesize($tmpName);
    if (!$imageInfo || empty($imageInfo[0]) || empty($imageInfo[1])) {
        return ['success' => false, 'error' => 'El archivo de ' . $config['label'] . ' no es una imagen valida.'];
    }

    [$width, $height] = $imageInfo;
    if (!empty($config['exact_width']) && !empty($config['exact_height'])
        && ((int) $width !== (int) $config['exact_width'] || (int) $height !== (int) $config['exact_height'])) {
        return ['success' => false, 'error' => 'La imagen de ' . $config['label'] . ' debe medir exactamente ' . $config['exact_width'] . 'x' . $config['exact_height'] . ' px.'];
    }

    if ($width < $config['min_width'] || $height < $config['min_height']) {
        return ['success' => false, 'error' => 'La imagen de ' . $config['label'] . ' debe medir al menos ' . $config['min_width'] . 'x' . $config['min_height'] . ' px.'];
    }

    if ($width > $config['max_width'] || $height > $config['max_height']) {
        return ['success' => false, 'error' => 'La imagen de ' . $config['label'] . ' excede el tamano maximo de ' . $config['max_width'] . 'x' . $config['max_height'] . ' px.'];
    }

    $slug = hotel_branding_safe_slug($hotelSlug);
    if ($slug === '') {
        return ['success' => false, 'error' => 'Slug de hotel no valido para guardar assets.'];
    }

    $relativeDir = 'uploads/branding/' . $slug . '/' . $config['dir'];
    $absoluteDir = PUBLIC_PATH . '/' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        return ['success' => false, 'error' => 'No se pudo crear la carpeta de branding.'];
    }

    hotel_branding_write_upload_guards(PUBLIC_PATH . '/uploads/branding');

    $filename = $tipo . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $absolutePath = $absoluteDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        return ['success' => false, 'error' => 'No se pudo guardar el archivo de ' . $config['label'] . '.'];
    }

    @chmod($absolutePath, 0644);

    return [
        'success' => true,
        'path' => $relativeDir . '/' . $filename,
        'uploaded' => true,
        'mime' => $mime,
        'size' => $size,
        'width' => (int) $width,
        'height' => (int) $height
    ];
}

function hotel_branding_upload_config($tipo) {
    $configs = [
        'logo' => [
            'label' => 'logo',
            'dir' => 'logo',
            'max_size' => 2 * 1024 * 1024,
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
            'mimes' => ['image/png', 'image/jpeg', 'image/webp'],
            'min_width' => 64,
            'min_height' => 64,
            'max_width' => 4096,
            'max_height' => 4096
        ],
        'favicon' => [
            'label' => 'favicon',
            'dir' => 'favicon',
            'max_size' => 512 * 1024,
            'extensions' => ['ico', 'png'],
            'mimes' => ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'],
            'min_width' => 16,
            'min_height' => 16,
            'max_width' => 1024,
            'max_height' => 1024
        ],
        'login_bg' => [
            'label' => 'fondo de login',
            'dir' => 'login-bg',
            'max_size' => 4 * 1024 * 1024,
            'extensions' => ['png', 'jpg', 'jpeg', 'webp'],
            'mimes' => ['image/png', 'image/jpeg', 'image/webp'],
            'min_width' => 800,
            'min_height' => 400,
            'max_width' => 6000,
            'max_height' => 6000
        ],
        'pwa_icon_192' => [
            'label' => 'icono PWA 192x192',
            'dir' => 'pwa-icons',
            'max_size' => 1024 * 1024,
            'extensions' => ['png', 'webp'],
            'mimes' => ['image/png', 'image/webp'],
            'min_width' => 192,
            'min_height' => 192,
            'max_width' => 192,
            'max_height' => 192,
            'exact_width' => 192,
            'exact_height' => 192
        ],
        'pwa_icon_512' => [
            'label' => 'icono PWA 512x512',
            'dir' => 'pwa-icons',
            'max_size' => 1024 * 1024,
            'extensions' => ['png', 'webp'],
            'mimes' => ['image/png', 'image/webp'],
            'min_width' => 512,
            'min_height' => 512,
            'max_width' => 512,
            'max_height' => 512,
            'exact_width' => 512,
            'exact_height' => 512
        ],
    ];

    return $configs[$tipo] ?? null;
}

function hotel_branding_extensions_for_mime($mime) {
    $map = [
        'image/png' => ['png'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/webp' => ['webp'],
        'image/x-icon' => ['ico'],
        'image/vnd.microsoft.icon' => ['ico'],
    ];

    return $map[$mime] ?? [];
}

function hotel_branding_safe_slug($slug) {
    $slug = strtolower(trim((string) $slug));
    return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug) ? $slug : '';
}

function hotel_branding_format_bytes($bytes) {
    if ($bytes >= 1024 * 1024) {
        return (int) ($bytes / 1024 / 1024) . ' MB';
    }

    return (int) ceil($bytes / 1024) . ' KB';
}

function hotel_branding_write_upload_guards($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $htaccess = rtrim($dir, '/\\') . '/.htaccess';
    if (file_exists($htaccess)) {
        return;
    }

    $content = "Options -Indexes\n"
        . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar|pl|py|jsp|asp|aspx|sh|cgi|html|htm|js|css|svg)$\">\n"
        . "    Require all denied\n"
        . "</FilesMatch>\n"
        . "<FilesMatch \"\\.(jpg|jpeg|png|webp|ico)$\">\n"
        . "    Require all granted\n"
        . "</FilesMatch>\n";

    @file_put_contents($htaccess, $content);
}
