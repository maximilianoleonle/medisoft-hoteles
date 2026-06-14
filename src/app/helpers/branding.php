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
            'color_primary' => '#1B2746',
            'color_secondary' => '#0F172A',
            'color_accent' => '#BD9441',
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
    $primaryRaw = hotel_branding_hex($branding['color_primary'] ?? null, '#1B2746');
    $secondaryRaw = hotel_branding_hex($branding['color_secondary'] ?? null, '#0F172A');
    $accentRaw = hotel_branding_hex($branding['color_accent'] ?? null, '#BD9441');
    $safePalette = hotel_branding_safe_palette($primaryRaw, $secondaryRaw, $accentRaw);

    $vars = array_merge([
        '--brand-primary-raw' => $primaryRaw,
        '--brand-secondary-raw' => $secondaryRaw,
        '--brand-accent-raw' => $accentRaw,
    ], $safePalette);

    $css = '';
    foreach ($vars as $name => $value) {
        $css .= $name . ':' . $value . ';';
    }

    return '<style id="hotel-branding-vars">:root{' . $css . '}</style>';
}

function hotel_branding_hex($color, $fallback) {
    $color = trim((string) $color);
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : $fallback;
}

function hotel_branding_safe_palette($primaryRaw, $secondaryRaw, $accentRaw) {
    $surface = '#FFFEFB';
    $surfaceSoft = hotel_branding_mix($accentRaw, '#F8F5ED', 8);
    $primary = hotel_branding_solid_color_for_light_text($primaryRaw);
    $secondary = hotel_branding_adjust_for_contrast($secondaryRaw, $surface, 7);
    $accent = hotel_branding_solid_color_for_light_text($accentRaw);
    $text = hotel_branding_adjust_for_contrast($secondaryRaw, $surface, 7);
    $muted = hotel_branding_mix($text, $surface, 66);

    if (hotel_branding_contrast_ratio($muted, $surface) < 4.5) {
        $muted = hotel_branding_adjust_for_contrast($muted, $surface, 4.5);
    }

    $actionBg = $primary;
    $actionText = '#FFFEFB';
    $actionBgHover = hotel_branding_luminance($actionBg) < 0.18
        ? hotel_branding_mix($surface, $actionBg, 10)
        : hotel_branding_mix('#111827', $actionBg, 14);

    if (hotel_branding_contrast_ratio($actionBgHover, $actionText) < 4.5) {
        $actionBgHover = hotel_branding_solid_color_for_light_text($actionBgHover);
    }

    return [
        '--brand-primary' => $primary,
        '--brand-secondary' => $secondary,
        '--brand-accent' => $accent,
        '--brand-text' => $text,
        '--brand-muted' => $muted,
        '--brand-surface' => $surface,
        '--brand-surface-soft' => $surfaceSoft,
        '--brand-soft' => hotel_branding_mix($primaryRaw, $surface, 9),
        '--brand-border' => hotel_branding_mix($primary, '#DED7CA', 18),
        '--brand-line' => hotel_branding_mix($primary, '#E7E1D4', 10),
        '--brand-focus' => hotel_branding_mix($primary, $surface, 34),
        '--brand-primary-contrast' => '#FFFEFB',
        '--brand-secondary-contrast' => '#FFFEFB',
        '--brand-accent-contrast' => '#FFFEFB',
        '--brand-action-bg' => $actionBg,
        '--brand-action-bg-hover' => $actionBgHover,
        '--brand-action-text' => $actionText,
    ];
}

function hotel_branding_solid_color_for_light_text($color, $minimumContrast = 4.5) {
    $color = strtoupper($color);
    $lightText = '#FFFEFB';

    if ($color === '#000000') {
        return '#111827';
    }

    if (hotel_branding_contrast_ratio($color, $lightText) >= $minimumContrast) {
        return $color;
    }

    for ($weight = 8; $weight <= 88; $weight += 4) {
        $candidate = hotel_branding_mix('#111827', $color, $weight);
        if (hotel_branding_contrast_ratio($candidate, $lightText) >= $minimumContrast) {
            return $candidate;
        }
    }

    return '#1B2746';
}

function hotel_branding_adjust_for_contrast($color, $background, $minimumContrast = 4.5) {
    $color = strtoupper($color);
    if ($color === '#000000') {
        $color = '#111827';
    }

    if (hotel_branding_contrast_ratio($color, $background) >= $minimumContrast) {
        return $color;
    }

    $target = hotel_branding_luminance($background) > 0.45 ? '#111827' : '#FFFEFB';

    for ($weight = 8; $weight <= 92; $weight += 4) {
        $candidate = hotel_branding_mix($target, $color, $weight);
        if (hotel_branding_contrast_ratio($candidate, $background) >= $minimumContrast) {
            return $candidate;
        }
    }

    return $target;
}

function hotel_branding_mix($firstHex, $secondHex, $firstWeight) {
    $first = hotel_branding_hex_to_rgb($firstHex);
    $second = hotel_branding_hex_to_rgb($secondHex);
    $firstWeight = max(0, min(100, (float) $firstWeight)) / 100;
    $secondWeight = 1 - $firstWeight;

    return hotel_branding_rgb_to_hex([
        (int) round(($first[0] * $firstWeight) + ($second[0] * $secondWeight)),
        (int) round(($first[1] * $firstWeight) + ($second[1] * $secondWeight)),
        (int) round(($first[2] * $firstWeight) + ($second[2] * $secondWeight)),
    ]);
}

function hotel_branding_contrast_ratio($firstHex, $secondHex) {
    $first = hotel_branding_luminance($firstHex);
    $second = hotel_branding_luminance($secondHex);
    $lighter = max($first, $second);
    $darker = min($first, $second);

    return ($lighter + 0.05) / ($darker + 0.05);
}

function hotel_branding_luminance($hex) {
    $rgb = hotel_branding_hex_to_rgb($hex);
    $channels = array_map('hotel_branding_luminance_channel', $rgb);

    return ($channels[0] * 0.2126) + ($channels[1] * 0.7152) + ($channels[2] * 0.0722);
}

function hotel_branding_luminance_channel($channel) {
    $channel = max(0, min(255, (int) $channel)) / 255;

    return $channel <= 0.03928
        ? $channel / 12.92
        : pow(($channel + 0.055) / 1.055, 2.4);
}

function hotel_branding_hex_to_rgb($hex) {
    $hex = ltrim(hotel_branding_hex($hex, '#111827'), '#');

    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function hotel_branding_rgb_to_hex(array $rgb) {
    return sprintf(
        '#%02X%02X%02X',
        max(0, min(255, (int) $rgb[0])),
        max(0, min(255, (int) $rgb[1])),
        max(0, min(255, (int) $rgb[2]))
    );
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
