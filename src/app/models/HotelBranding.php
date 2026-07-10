<?php
/**
 * Modelo para branding basico controlado por hotel.
 */

class HotelBranding extends Model {
    protected $table = 'hotel_branding';
    protected $fillable = [
        'hotel_id',
        'nombre_visual',
        'logo_url',
        'favicon_url',
        'login_background_url',
        'pwa_icon_192_url',
        'pwa_icon_512_url',
        'color_primary',
        'color_secondary',
        'color_accent',
        'sidebar_style',
        'login_style',
        'tema',
        'activo'
    ];

    /**
     * Temas visuales disponibles (slug => nombre comercial).
     * El slug se refleja en data-tema del <html> y en css/temas/<slug>.css.
     */
    const TEMAS = [
        'deleite' => 'Deleite Sereno',
        'cupertino' => 'Cupertino',
    ];

    const TEMA_DEFAULT = 'deleite';

    private $fallback = [
        'nombre_visual' => 'Medisoft Hoteles',
        'logo_url' => 'img/logo.png',
        'favicon_url' => null,
        'login_background_url' => null,
        'pwa_icon_192_url' => null,
        'pwa_icon_512_url' => null,
        'color_primary' => '#9CA777',
        'color_secondary' => '#7A8B5C',
        'color_accent' => '#D4AF37',
        'sidebar_style' => 'default',
        'login_style' => 'default',
        'tema' => self::TEMA_DEFAULT,
        'activo' => 1
    ];

    public static function temasDisponibles() {
        return self::TEMAS;
    }

    public static function temaSlugs() {
        return array_keys(self::TEMAS);
    }

    public function obtenerPorHotel($hotelId) {
        try {
            $resultado = $this->query(
                "SELECT *
                 FROM {$this->table}
                 WHERE hotel_id = ?
                 LIMIT 1",
                [(int) $hotelId]
            );

            return $resultado[0] ?? null;
        } catch (Throwable $e) {
            error_log('Error al obtener branding de hotel: ' . $e->getMessage());
            return null;
        }
    }

    public function resolverParaHotel($hotelId = null, array $hotel = null) {
        $branding = $this->fallbackParaHotel($hotel);

        if (!$hotelId) {
            return $branding;
        }

        $registro = $this->obtenerPorHotel((int) $hotelId);

        if (!$registro || empty($registro['activo'])) {
            return $branding;
        }

        foreach ($branding as $campo => $fallback) {
            if (array_key_exists($campo, $registro) && $registro[$campo] !== null && $registro[$campo] !== '') {
                $branding[$campo] = $registro[$campo];
            }
        }

        $branding['id'] = $registro['id'] ?? null;
        $branding['hotel_id'] = (int) $hotelId;
        $branding['activo'] = (int) ($registro['activo'] ?? 1);
        $normalizado = $this->normalizarDatos($branding);
        $branding = array_merge($branding, $normalizado);

        return $branding;
    }

    public function guardarParaHotel($hotelId, array $data) {
        $hotelId = (int) $hotelId;
        if ($hotelId <= 0) {
            return false;
        }

        $normalizado = $this->normalizarDatos($data);

        try {
            $stmt = $this->db->query(
                "INSERT INTO {$this->table}
                    (hotel_id, nombre_visual, logo_url, favicon_url, login_background_url,
                     pwa_icon_192_url, pwa_icon_512_url,
                     color_primary, color_secondary, color_accent, sidebar_style, login_style,
                     tema, activo, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, 'deleite'), ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    nombre_visual = VALUES(nombre_visual),
                    logo_url = VALUES(logo_url),
                    favicon_url = VALUES(favicon_url),
                    login_background_url = VALUES(login_background_url),
                    pwa_icon_192_url = VALUES(pwa_icon_192_url),
                    pwa_icon_512_url = VALUES(pwa_icon_512_url),
                    color_primary = VALUES(color_primary),
                    color_secondary = VALUES(color_secondary),
                    color_accent = VALUES(color_accent),
                    sidebar_style = VALUES(sidebar_style),
                    login_style = VALUES(login_style),
                    tema = COALESCE(?, tema),
                    activo = VALUES(activo),
                    updated_at = NOW()",
                [
                    $hotelId,
                    $normalizado['nombre_visual'],
                    $normalizado['logo_url'],
                    $normalizado['favicon_url'],
                    $normalizado['login_background_url'],
                    $normalizado['pwa_icon_192_url'],
                    $normalizado['pwa_icon_512_url'],
                    $normalizado['color_primary'],
                    $normalizado['color_secondary'],
                    $normalizado['color_accent'],
                    $normalizado['sidebar_style'],
                    $normalizado['login_style'],
                    $normalizado['tema'],
                    $normalizado['activo'],
                    $normalizado['tema']
                ]
            );

            return $stmt !== false;
        } catch (Throwable $e) {
            error_log('Error al guardar branding de hotel: ' . $e->getMessage());
            return false;
        }
    }

    public function validarDatos(array $data) {
        $errores = [];

        $nombre = trim((string) ($data['nombre_visual'] ?? ''));
        if ($nombre !== '' && strlen($nombre) > 150) {
            $errores[] = 'El nombre visual no puede exceder 150 caracteres.';
        }

        foreach (['color_primary', 'color_secondary', 'color_accent'] as $campo) {
            $color = trim((string) ($data[$campo] ?? ''));
            if ($color !== '' && !$this->esHexSeguro($color)) {
                $errores[] = 'El color ' . str_replace('color_', '', $campo) . ' debe usar formato HEX, por ejemplo #0F766E.';
            }
        }

        $urls = [
            'logo_url' => 'logo',
            'favicon_url' => 'favicon',
            'login_background_url' => 'fondo de login'
        ];

        foreach ($urls as $campo => $label) {
            $url = trim((string) ($data[$campo] ?? ''));
            if ($url !== '' && !$this->esUrlAssetSegura($url, $campo === 'favicon_url')) {
                $errores[] = 'La URL de ' . $label . ' debe ser una imagen segura png, jpg, jpeg, webp' . ($campo === 'favicon_url' ? ' o ico.' : '.');
            }
        }

        $pwaIcons = [
            'pwa_icon_192_url' => 'icono PWA 192x192',
            'pwa_icon_512_url' => 'icono PWA 512x512'
        ];

        foreach ($pwaIcons as $campo => $label) {
            $url = trim((string) ($data[$campo] ?? ''));
            if ($url !== '' && !$this->esUrlPwaIconSegura($url)) {
                $errores[] = 'La URL de ' . $label . ' debe ser una ruta local segura png o webp.';
            }
        }

        $sidebarStyle = trim((string) ($data['sidebar_style'] ?? 'default'));
        if (!in_array($sidebarStyle, ['default', 'solid', 'dark'], true)) {
            $errores[] = 'El estilo de sidebar seleccionado no es valido.';
        }

        $loginStyle = trim((string) ($data['login_style'] ?? 'default'));
        if (!in_array($loginStyle, ['default', 'soft', 'image'], true)) {
            $errores[] = 'El estilo de login seleccionado no es valido.';
        }

        $tema = trim((string) ($data['tema'] ?? ''));
        if ($tema !== '' && !in_array($tema, self::temaSlugs(), true)) {
            $errores[] = 'El tema de diseno seleccionado no es valido.';
        }

        return $errores;
    }

    public function normalizarDatos(array $data) {
        return [
            'nombre_visual' => $this->valorNullable($data['nombre_visual'] ?? null),
            'logo_url' => $this->normalizarUrlAsset($data['logo_url'] ?? null, false),
            'favicon_url' => $this->normalizarUrlAsset($data['favicon_url'] ?? null, true),
            'login_background_url' => $this->normalizarUrlAsset($data['login_background_url'] ?? null, false),
            'pwa_icon_192_url' => $this->normalizarUrlPwaIcon($data['pwa_icon_192_url'] ?? null),
            'pwa_icon_512_url' => $this->normalizarUrlPwaIcon($data['pwa_icon_512_url'] ?? null),
            'color_primary' => $this->normalizarHex($data['color_primary'] ?? null),
            'color_secondary' => $this->normalizarHex($data['color_secondary'] ?? null),
            'color_accent' => $this->normalizarHex($data['color_accent'] ?? null),
            'sidebar_style' => $this->normalizarOpcion($data['sidebar_style'] ?? 'default', ['default', 'solid', 'dark'], 'default'),
            'login_style' => $this->normalizarOpcion($data['login_style'] ?? 'default', ['default', 'soft', 'image'], 'default'),
            'tema' => $this->normalizarTema($data['tema'] ?? null),
            'activo' => !empty($data['activo']) ? 1 : 0
        ];
    }

    /**
     * NULL significa "no especificado": guardarParaHotel lo preserva via COALESCE,
     * asi los formularios que aun no envian tema no resetean la eleccion del hotel.
     */
    private function normalizarTema($tema) {
        $tema = trim((string) $tema);
        if ($tema === '') {
            return null;
        }

        return in_array($tema, self::temaSlugs(), true) ? $tema : self::TEMA_DEFAULT;
    }

    private function fallbackParaHotel(array $hotel = null) {
        $fallback = $this->fallback;

        if (!empty($hotel['nombre_visual'])) {
            $fallback['nombre_visual'] = $hotel['nombre_visual'];
        } elseif (!empty($hotel['nombre_comercial'])) {
            $fallback['nombre_visual'] = $hotel['nombre_comercial'];
        } elseif (!empty($hotel['nombre'])) {
            $fallback['nombre_visual'] = $hotel['nombre'];
        }

        if (!empty($hotel['hotel_id'])) {
            $fallback['hotel_id'] = (int) $hotel['hotel_id'];
        } elseif (!empty($hotel['id'])) {
            $fallback['hotel_id'] = (int) $hotel['id'];
        }

        return $fallback;
    }

    private function valorNullable($valor) {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }

    private function normalizarHex($color) {
        $color = trim((string) $color);
        return $this->esHexSeguro($color) ? strtoupper($color) : null;
    }

    private function esHexSeguro($color) {
        return (bool) preg_match('/^#[0-9A-Fa-f]{6}$/', trim((string) $color));
    }

    private function normalizarUrlAsset($url, $permitirIco = false) {
        $url = trim((string) $url);
        return $url !== '' && $this->esUrlAssetSegura($url, $permitirIco) ? $url : null;
    }

    private function normalizarUrlPwaIcon($url) {
        $url = trim((string) $url);
        return $url !== '' && $this->esUrlPwaIconSegura($url) ? $url : null;
    }

    private function esUrlAssetSegura($url, $permitirIco = false) {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F<>"\']/', $url)) {
            return false;
        }

        $lower = strtolower($url);
        foreach (['javascript:', 'data:', 'vbscript:', 'file:'] as $scheme) {
            if (strpos($lower, $scheme) === 0) {
                return false;
            }
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $permitidas = $permitirIco ? ['png', 'jpg', 'jpeg', 'webp', 'ico'] : ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array($extension, $permitidas, true)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== null && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            return false;
        }

        if ($scheme === null) {
            $cleanPath = ltrim($path, '/');
            $prefijosPermitidos = ['uploads/branding/', 'uploads/', 'img/'];
            foreach ($prefijosPermitidos as $prefijo) {
                if (strpos($cleanPath, $prefijo) === 0) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    private function esUrlPwaIconSegura($url) {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F<>"\']/', $url)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme !== null) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['png', 'webp'], true)) {
            return false;
        }

        $cleanPath = ltrim($path, '/');
        return strpos($cleanPath, 'uploads/branding/') === 0 || strpos($cleanPath, 'img/icons/') === 0;
    }

    private function normalizarOpcion($valor, array $permitidos, $fallback) {
        $valor = trim((string) $valor);
        return in_array($valor, $permitidos, true) ? $valor : $fallback;
    }
}
