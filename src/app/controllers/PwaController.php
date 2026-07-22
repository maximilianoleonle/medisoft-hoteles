<?php
/**
 * Controlador PWA para manifest dinamico por hotel.
 */

class PwaController extends Controller {

    /**
     * Pantalla "Operaciones offline": lo pendiente, lo sincronizado y lo
     * rechazado por /api/sync. El contenido se renderiza 100% en el cliente
     * desde IndexedDB, así la pantalla también funciona sin internet.
     */
    public function pendientesAction() {
        $this->requireAuth();

        View::renderTemplate('pwa/pendientes', [
            'title' => 'Cambios sin enviar - ' . (function_exists('current_hotel_display_name') ? current_hotel_display_name() : 'Medisoft Hoteles'),
        ]);
    }

    public function manifestAction($slug) {
        $slug = $this->normalizarSlug($slug);
        if ($slug === '') {
            $this->jsonError('Slug de hotel invalido.', 404);
        }

        $hotel = $this->obtenerHotelActivoPorSlug($slug);
        if (!$hotel) {
            $this->jsonError('Hotel no encontrado o inactivo.', 404);
        }

        $branding = function_exists('hotel_branding')
            ? hotel_branding((int) $hotel['id'], [
                'id' => (int) $hotel['id'],
                'hotel_id' => (int) $hotel['id'],
                'nombre_comercial' => $hotel['nombre'] ?? null,
                'slug' => $hotel['slug'] ?? null,
            ])
            : [];

        $nombreConfig = function_exists('hotel_config_get')
            ? hotel_config_get('pwa.nombre_app', '', (int) $hotel['id'])
            : '';
        $nombreFallback = function_exists('hotel_branding_public_name')
            ? hotel_branding_public_name($branding, $hotel['nombre'] ?? 'Medisoft Hoteles')
            : ($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Medisoft Hoteles');

        $nombre = $this->textoSeguro(
            trim((string) $nombreConfig) !== '' ? $nombreConfig : $nombreFallback,
            'Medisoft Hoteles',
            80
        );

        $manifest = [
            'id' => url('h/' . $slug),
            'name' => $nombre,
            'short_name' => $this->shortName($nombre),
            'description' => 'Acceso al sistema de gestion hotelera de ' . $nombre . ' con Medisoft Hoteles.',
            'start_url' => url('h/' . $slug . '/login'),
            'scope' => rtrim(url(''), '/') . '/',
            'display' => 'standalone',
            'background_color' => $this->splashBackgroundColor($branding),
            'theme_color' => $this->colorSeguro($branding['color_primary'] ?? null, '#1B2746'),
            'orientation' => 'portrait-primary',
            'lang' => 'es-MX',
            'dir' => 'ltr',
            'categories' => ['business', 'productivity'],
            'prefer_related_applications' => false,
            'icons' => $this->iconosManifest($branding),
        ];

        $this->renderManifest($manifest);
    }

    private function obtenerHotelActivoPorSlug($slug) {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, nombre, slug, activo
                 FROM hoteles
                 WHERE slug = ? AND activo = 1
                 LIMIT 1",
                [$slug]
            );

            return $stmt ? ($stmt->fetch() ?: null) : null;
        } catch (Throwable $e) {
            error_log('Error al resolver manifest de hotel: ' . $e->getMessage());
            return null;
        }
    }

    private function normalizarSlug($slug) {
        $slug = strtolower(trim((string) $slug));
        return preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug) ? $slug : '';
    }

    private function textoSeguro($valor, $fallback, $maxLength) {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags((string) $valor)));
        if ($texto === '') {
            $texto = $fallback;
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($texto, 'UTF-8') > $maxLength
                ? rtrim(mb_substr($texto, 0, $maxLength, 'UTF-8'))
                : $texto;
        }

        return strlen($texto) > $maxLength ? rtrim(substr($texto, 0, $maxLength)) : $texto;
    }

    private function shortName($nombre) {
        $nombre = $this->textoSeguro($nombre, 'Medisoft', 40);
        $max = 24;

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($nombre, 'UTF-8') > $max
                ? rtrim(mb_substr($nombre, 0, $max, 'UTF-8'))
                : $nombre;
        }

        return strlen($nombre) > $max ? rtrim(substr($nombre, 0, $max)) : $nombre;
    }

    private function colorSeguro($color, $fallback) {
        $color = trim((string) $color);
        if (function_exists('hotel_branding_hex')) {
            return hotel_branding_hex($color, $fallback);
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : $fallback;
    }

    private function splashBackgroundColor(array $branding) {
        $accent = $this->colorSeguro($branding['color_accent'] ?? null, '#BD9441');

        if (function_exists('hotel_branding_mix')) {
            return hotel_branding_mix($accent, '#F8F5ED', 8);
        }

        return '#F8F5ED';
    }

    private function iconosFallback() {
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $icons = [];

        foreach ($sizes as $size) {
            $icons[] = [
                'src' => asset('img/icons/icon-' . $size . 'x' . $size . '.png'),
                'sizes' => $size . 'x' . $size,
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ];
        }

        return $icons;
    }

    private function iconosManifest(array $branding) {
        $icon192 = $this->iconoHotel($branding['pwa_icon_192_url'] ?? null, 192);
        $icon512 = $this->iconoHotel($branding['pwa_icon_512_url'] ?? null, 512);

        if ($icon192 && $icon512) {
            return [$icon192, $icon512];
        }

        return $this->iconosFallback();
    }

    private function iconoHotel($path, $size) {
        if (!function_exists('hotel_branding_pwa_icon_asset_url')) {
            return null;
        }

        $src = hotel_branding_pwa_icon_asset_url($path, $size);
        if (!$src) {
            return null;
        }

        $extension = strtolower(pathinfo((string) parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
        $type = $extension === 'webp' ? 'image/webp' : 'image/png';

        return [
            'src' => $src,
            'sizes' => (int) $size . 'x' . (int) $size,
            'type' => $type,
            'purpose' => 'any maskable',
        ];
    }

    private function renderManifest(array $manifest) {
        http_response_code(200);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError($message, $statusCode) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
