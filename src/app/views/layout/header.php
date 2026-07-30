<?php
$layoutRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$layoutEsPanelSaas = strpos($layoutRequestPath, '/admin/saas') === 0;
$layoutTieneContextoHotel = function_exists('has_hotel_context') && has_hotel_context();
$layoutOfflineHoteleroActivo = !$layoutEsPanelSaas
    && $layoutTieneContextoHotel
    && function_exists('is_authenticated')
    && is_authenticated();
$layoutBranding = (!$layoutEsPanelSaas && $layoutTieneContextoHotel && function_exists('current_hotel_branding'))
    ? current_hotel_branding()
    : null;
$layoutSystemBackgroundColor = (!$layoutEsPanelSaas && $layoutBranding && function_exists('hotel_branding_system_background'))
    ? hotel_branding_system_background(
        $layoutBranding,
        function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null)
    )
    : null;
/* Tema visual del negocio (multi-diseño). 'deleite' es el diseño fundador y no
   emite atributo ni CSS extra; cualquier otro tema agrega data-tema + su hoja. */
$layoutTema = 'deleite';
if ($layoutBranding && !empty($layoutBranding['tema']) && preg_match('/^[a-z0-9-]{1,30}$/', (string) $layoutBranding['tema'])) {
    $layoutTema = (string) $layoutBranding['tema'];
}
$layoutTemaCssHref = null;
if ($layoutTema !== 'deleite' && defined('PUBLIC_PATH') && is_file(PUBLIC_PATH . '/css/temas/' . $layoutTema . '.css')) {
    $layoutTemaCssHref = 'css/temas/' . $layoutTema . '.css';
}
$layoutNombreVisual = $layoutBranding
    ? hotel_branding_public_name($layoutBranding, current_hotel_nombre() ?: 'Medisoft Hoteles')
    : 'Medisoft Hoteles';
$layoutLogoUrl = ($layoutBranding && function_exists('hotel_branding_asset_url'))
    ? (hotel_branding_asset_url($layoutBranding['logo_url'] ?? null) ?: hotel_branding_default_logo_url())
    : (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : asset('img/logo.png'));
$layoutFaviconUrl = ($layoutBranding && function_exists('hotel_branding_asset_url'))
    ? hotel_branding_asset_url($layoutBranding['favicon_url'] ?? null)
    : null;
$layoutPwaIcon192Url = ($layoutBranding && function_exists('hotel_branding_pwa_icon_asset_url'))
    ? hotel_branding_pwa_icon_asset_url($layoutBranding['pwa_icon_192_url'] ?? null, 192)
    : null;
$layoutPwaIcon512Url = ($layoutBranding && function_exists('hotel_branding_pwa_icon_asset_url'))
    ? hotel_branding_pwa_icon_asset_url($layoutBranding['pwa_icon_512_url'] ?? null, 512)
    : null;
$layoutAppleTouchIconUrl = $layoutPwaIcon192Url ?: $layoutLogoUrl;
$layoutThemeColor = '#1B2746';
if ($layoutEsPanelSaas) {
    $layoutThemeColor = '#0B1220';
} elseif ($layoutBranding && function_exists('hotel_branding_hex')) {
    $layoutThemeColor = hotel_branding_hex($layoutBranding['color_primary'] ?? null, '#1B2746');
}
$layoutOfflineBrandingPayload = null;
if ($layoutOfflineHoteleroActivo && $layoutBranding && function_exists('hotel_branding_hex')) {
    $layoutOfflineBrandingPayload = [
        'name' => $layoutNombreVisual,
        'logo' => $layoutLogoUrl,
        'favicon' => $layoutFaviconUrl,
        'pwaIcon192' => $layoutPwaIcon192Url,
        'pwaIcon512' => $layoutPwaIcon512Url,
        'primary' => hotel_branding_hex($layoutBranding['color_primary'] ?? null, '#1B2746'),
        'secondary' => hotel_branding_hex($layoutBranding['color_secondary'] ?? null, '#0F172A'),
        'accent' => hotel_branding_hex($layoutBranding['color_accent'] ?? null, '#BD9441'),
        'hotelId' => function_exists('current_hotel_id') ? (int) current_hotel_id() : null,
        'slug' => function_exists('current_hotel_slug') ? (string) current_hotel_slug() : null,
        'updatedAt' => date('c'),
    ];
}
$layoutManifestHref = asset('manifest.json');
$layoutHotelSlug = function_exists('current_hotel_slug') ? current_hotel_slug() : null;
if (!$layoutEsPanelSaas && $layoutHotelSlug && preg_match('/^[a-z0-9-]+$/', $layoutHotelSlug)) {
    $layoutManifestHref = url('h/' . $layoutHotelSlug . '/manifest.webmanifest');
}
$layoutPathSegment = trim((string)$layoutRequestPath, '/');
$layoutPathSegment = $layoutPathSegment === '' ? 'dashboard' : strtok($layoutPathSegment, '/');
$layoutPageClass = preg_match('/^[a-z0-9_-]+$/i', (string)$layoutPathSegment)
    ? ' page-' . str_replace('_', '-', strtolower((string)$layoutPathSegment))
    : '';
?>
<!DOCTYPE html>
<html lang="es"<?= $layoutTema !== 'deleite' ? ' data-tema="' . htmlspecialchars($layoutTema, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="description" content="Sistema de Gestión Hotelera - <?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($title ?? $layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- PWA Meta Tags -->
    <!-- Color base para la barra de estado -->
    <meta name="theme-color" content="<?= htmlspecialchars($layoutThemeColor, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="application-name" content="<?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="msapplication-TileColor" content="<?= htmlspecialchars($layoutThemeColor, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="msapplication-TileImage" content="<?= htmlspecialchars($layoutPwaIcon192Url ?: asset('img/icons/icon-144x144.png'), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="msapplication-config" content="<?= asset('browserconfig.xml') ?>">
    <meta name="format-detection" content="telephone=no">

    <!-- Transiciones de vista entre páginas (MPA View Transitions): cross-fade
         suave al navegar entre pantallas del mismo origen, en vez del corte a
         blanco. Mejora progresiva: navegadores sin soporte navegan como siempre;
         el bfcache restaura sin transición (instantáneo, como debe ser). -->
    <style>
        @view-transition { navigation: auto; }
        ::view-transition-old(root) { animation-duration: .16s; }
        ::view-transition-new(root) { animation-duration: .2s; }
        @media (prefers-reduced-motion: reduce) {
            ::view-transition-group(*),
            ::view-transition-old(*),
            ::view-transition-new(*) { animation: none !important; }
        }
    </style>
    <script>
        /* Tema de color (light | auto | dark). Corre antes de cargar CSS para evitar flash. */
        (function() {
            var KEY = 'medisoft:theme';
            var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

            function readMode() {
                var mode = null;
                try { mode = window.localStorage.getItem(KEY); } catch (error) {}
                return (mode === 'dark' || mode === 'auto' || mode === 'light') ? mode : 'light';
            }

            function apply() {
                var mode = readMode();
                var dark = mode === 'dark' || (mode === 'auto' && media && media.matches);
                if (dark) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            }

            window.MedisoftTheme = {
                get: readMode,
                set: function(mode) {
                    if (mode !== 'dark' && mode !== 'auto' && mode !== 'light') {
                        mode = 'light';
                    }
                    try { window.localStorage.setItem(KEY, mode); } catch (error) {}
                    apply();
                    document.dispatchEvent(new CustomEvent('medisoft:theme-change', { detail: { mode: mode } }));
                },
                apply: apply
            };

            if (media) {
                var onSchemeChange = function() {
                    if (readMode() === 'auto') {
                        apply();
                    }
                };
                if (media.addEventListener) {
                    media.addEventListener('change', onSchemeChange);
                } else if (media.addListener) {
                    media.addListener(onSchemeChange);
                }
            }

            apply();
        })();
    </script>
    <script>
        /* Hápticos (vibración). Preferencia por dispositivo; ON por defecto donde el hardware lo soporta.
           Android/Chrome vibra; iOS/Safari no expone la Vibration API y degrada en silencio (sin error). */
        (function() {
            var KEY = 'medisoft:haptics';
            var supported = typeof navigator !== 'undefined' && typeof navigator.vibrate === 'function';
            var reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;

            // Patrones (ms). Sutiles a propósito: un aviso, no un timbre.
            var PATTERNS = {
                tap:     10,
                success: 18,
                warning: [22, 45, 22],
                error:   [28, 55, 28]
            };

            function enabled() {
                var v = null;
                try { v = window.localStorage.getItem(KEY); } catch (error) {}
                return v !== 'off'; // ausente => activado
            }

            function fire(kind) {
                if (!supported || !enabled()) { return false; }
                if (reduceMotion && reduceMotion.matches) { return false; }
                var pattern = PATTERNS[kind];
                if (pattern == null) { return false; } // tipo desconocido (p.ej. 'info') => sin vibración
                try { return navigator.vibrate(pattern); } catch (error) { return false; }
            }

            window.MedisoftHaptics = {
                supported: supported,
                fire: fire,
                get: function() { return enabled() ? 'on' : 'off'; },
                set: function(mode) {
                    var on = mode !== 'off';
                    try { window.localStorage.setItem(KEY, on ? 'on' : 'off'); } catch (error) {}
                    document.dispatchEvent(new CustomEvent('medisoft:haptics-change', { detail: { enabled: on } }));
                }
            };
        })();
    </script>
    <script>
        (function() {
            try {
                var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                    || window.navigator.standalone === true;
                var alreadySeen = window.sessionStorage.getItem('medisoft_pwa_launch_seen') === '1';

                if (standalone && !alreadySeen) {
                    window.PwaLaunchSplashStartedAt = Date.now();
                    document.documentElement.classList.add('pwa-launch-pending');
                }
            } catch (error) {}
        })();
    </script>
    <script>
        (function() {
            function blockZoom(event) {
                if (event.cancelable) {
                    event.preventDefault();
                }
            }

            document.addEventListener('gesturestart', blockZoom, { passive: false });
            document.addEventListener('gesturechange', blockZoom, { passive: false });
            document.addEventListener('gestureend', blockZoom, { passive: false });
            document.addEventListener('touchmove', function(event) {
                if (event.touches && event.touches.length > 1) {
                    blockZoom(event);
                }
            }, { passive: false });
            // El bloqueo de doble-tap-zoom por touchend se quitó a propósito:
            // html{touch-action:pan-x pan-y} ya lo desactiva por CSS, y el
            // preventDefault en touchend CANCELABA el click sintético del
            // segundo tap rápido — steppers, flechas de calendario y botones
            // repetidos "no agarraban" al tocarlos rápido.
        })();
    </script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <!-- Escudo CSRF: parcha XHR/fetch/forms ANTES de cualquier otro JS (sin defer) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/csrf-shield.js') : asset('js/csrf-shield.js') ?>"></script>

    <!-- Manifest PWA -->
    <link rel="manifest" href="<?= htmlspecialchars($layoutManifestHref, ENT_QUOTES, 'UTF-8') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <?php if ($layoutFaviconUrl): ?>
    <link rel="icon" href="<?= htmlspecialchars($layoutFaviconUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    
    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="<?= htmlspecialchars($layoutAppleTouchIconUrl, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?= asset('img/icons/icon-72x72.png') ?>">
    <link rel="apple-touch-icon" sizes="96x96" href="<?= asset('img/icons/icon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="128x128" href="<?= asset('img/icons/icon-128x128.png') ?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?= asset('img/icons/icon-144x144.png') ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= asset('img/icons/icon-152x152.png') ?>">
    <link rel="apple-touch-icon" sizes="192x192" href="<?= htmlspecialchars($layoutPwaIcon192Url ?: asset('img/icons/icon-192x192.png'), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="apple-touch-icon" sizes="384x384" href="<?= asset('img/icons/icon-384x384.png') ?>">
    <link rel="apple-touch-icon" sizes="512x512" href="<?= htmlspecialchars($layoutPwaIcon512Url ?: asset('img/icons/icon-512x512.png'), ENT_QUOTES, 'UTF-8') ?>">
    
    <!-- Librerías self-hosted (fase 6 offline): sin dependencia de CDNs externos -->
    <!-- Tailwind CSS PRECOMPILADO (npm run build:css). Antes era el Play CDN
         compilando en el navegador en cada carga; la config inline de colores
         vive ahora en tailwind.config.js (raíz del repo). -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/tailwind.css') : asset('css/tailwind.css') ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">

    <!-- Fix para layout del dashboard - Cargar al final -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/dashboard-layout-fix.css') : asset('css/dashboard-layout-fix.css') ?>">

    <!-- Fuentes (Playfair Display + Inter) self-hosted -->
    <link href="<?= asset('vendor/fonts/fonts.css') ?>" rel="stylesheet">
    
    <!-- CSS del sidebar -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/sidebar-styles.css') : asset('css/sidebar-styles.css') ?>">
    <!-- NUEVO: Tamaño grande del sidebar -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/sidebar-size-override.css') : asset('css/sidebar-size-override.css') ?>">

    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/performance-optimization.css') : asset('css/performance-optimization.css') ?>"> <!-- NUEVO -->

    <!-- Selector con buscador (cualquier <select data-ms-combo>) -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/ms-combo.css') : asset('css/ms-combo.css') ?>">
    <!-- Chart.js ya NO se carga aquí: eran 204 KB bloqueando el primer
         pintado de ~200 vistas en cada navegación (Safari no precarga MPA)
         y las 6 vistas que grafican lo cargan por su cuenta (reportes/*,
         caja/historial, inventario/reportes). -->
    
    <!-- SweetAlert2 -->
    <script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
    
    <!-- La config de colores/fuentes de Tailwind se compila en build
         (tailwind.config.js); ya no hay config runtime aquí. -->

    <?php if ($layoutBranding && function_exists('hotel_branding_css_vars')): ?>
        <?= hotel_branding_css_vars($layoutBranding) ?>
    <?php endif; ?>

    <?php if ($layoutEsPanelSaas): ?>
    <style>
        /* Override: header móvil usa identidad Medisoft, no hotel */
        .ms-admin-scope .mobile-header-modern {
            background: var(--ms-sidebar, #0B1220) !important;
            border-bottom: 1px solid rgba(148,163,184,0.16) !important;
            box-shadow: 0 1px 12px rgba(15,23,42,0.22) !important;
        }
        .ms-admin-scope .scroll-progress {
            background: var(--ms-accent, #06B6D4) !important;
        }
        .ms-admin-scope .mobile-menu-toggle {
            background: rgba(255,255,255,0.08) !important;
            border-color: rgba(148,163,184,0.22) !important;
        }
        .ms-admin-scope .mobile-header-logo img {
            filter: brightness(0) invert(1) !important;
        }
        .ms-admin-scope {
            --ms-bg: #F6F8FB;
            --ms-surface: #FFFFFF;
            --ms-sidebar: #0B1220;
            --ms-primary: #2563EB;
            --ms-primary-hover: #1D4ED8;
            --ms-accent: #06B6D4;
            --ms-text: #0F172A;
            --ms-muted: #64748B;
            --ms-border: #E2E8F0;
            --ms-success: #16A34A;
            --ms-warning: #F59E0B;
            --ms-danger: #DC2626;
            background: var(--ms-bg);
            color: var(--ms-text);
        }

        .ms-admin-scope .main-content {
            background: var(--ms-bg);
            color: var(--ms-text);
        }

        .ms-admin-scope header.bg-white,
        .ms-admin-scope .bg-white {
            background-color: var(--ms-surface);
        }

        .ms-admin-scope .text-gray-500,
        .ms-admin-scope .text-gray-600,
        .ms-admin-scope .text-gray-700 {
            color: var(--ms-muted);
        }

        .ms-admin-scope .text-gray-900 {
            color: var(--ms-text);
        }

        .ms-admin-scope .border-gray-100,
        .ms-admin-scope .border-gray-200,
        .ms-admin-scope .border-gray-300,
        .ms-admin-scope .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        .ms-admin-scope .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: var(--ms-border);
        }
    </style>
    <?php endif; ?>
    
    <?php
        $medisoftContext = null;
        if ($layoutOfflineHoteleroActivo) {
            $medisoftHotelId = current_hotel_id();
            $medisoftUsuarioId = user_id();
            $medisoftContext = [
                'hotel_id' => (int) $medisoftHotelId,
                'usuario_id' => $medisoftUsuarioId ? (int) $medisoftUsuarioId : null,
                'hotel_scope' => 'hotel-' . (int) $medisoftHotelId,
                'storage_scope' => 'hotel-' . (int) $medisoftHotelId . '-user-' . ($medisoftUsuarioId ? (int) $medisoftUsuarioId : 'anon'),
                'storage_version' => 'v1',
                'generated_at' => date('c'),
            ];
        }
    ?>
    <?php $layoutFieldErrors = function_exists('get_form_errors') ? get_form_errors(true) : []; ?>
    <script>
        window.BASE_URL = '<?= rtrim(url(''), '/') ?>';
        window.API_URL = '<?= url('api') ?>';
        window.MEDISOFT_FIELD_ERRORS = <?= json_encode($layoutFieldErrors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.MEDISOFT_OFFLINE_ENABLED = <?= $layoutOfflineHoteleroActivo ? 'true' : 'false' ?>;
        /* Captura de ESCRITURAS sin conexion (cobros, check-in/out, altas).
           APAGADA desde jul-26 por decision del owner: /api/sync responde HTTP
           423 desde hace meses, asi que todo lo que se encolaba sin internet
           jamas llegaba al servidor — la app decia "se enviara al recuperar la
           conexion" y el cobro se perdia. Mientras el endpoint siga cerrado, es
           preferible avisar que no se puede guardar. El cache de LECTURA y la
           app instalable siguen activos (eso si funciona). Para reactivarlo:
           reabrir /api/sync (auditando idempotencia y cortes de caja) y poner
           esto en true. Ver ApiController::syncAction. */
        <?php
            // Operaciones cuya captura SIN CONEXION esta habilitada. Sale de la
            // MISMA constante que valida el servidor, para que cliente y backend
            // no puedan desincronizarse: si aqui aparece algo que Sync rechaza,
            // el hotelero captura y luego lo pierde.
            require_once APP_PATH . '/models/Sync.php';
        ?>
        window.MEDISOFT_OFFLINE_OPERACIONES = <?= json_encode(Sync::OPERACIONES_HABILITADAS) ?>;
        // Compatibilidad: paginas viejas en cache que aun consultan el booleano.
        // Se deja en false a proposito — el permiso real es por operacion.
        window.MEDISOFT_OFFLINE_ESCRITURAS = false;
        <?php if ($medisoftContext): ?>
        window.MEDISOFT_CONTEXT = <?= json_encode($medisoftContext, JSON_UNESCAPED_SLASHES) ?>;
        window.USUARIO_ID = window.MEDISOFT_CONTEXT.usuario_id;
        <?php endif; ?>
        <?php if ($layoutOfflineBrandingPayload): ?>
        window.MEDISOFT_OFFLINE_BRANDING = <?= json_encode($layoutOfflineBrandingPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        try {
            window.localStorage.setItem('medisoft:offline-branding', JSON.stringify(window.MEDISOFT_OFFLINE_BRANDING));
            if (window.MEDISOFT_OFFLINE_BRANDING.slug) {
                window.localStorage.setItem('medisoft:offline-branding:' + window.MEDISOFT_OFFLINE_BRANDING.slug, JSON.stringify(window.MEDISOFT_OFFLINE_BRANDING));
            }
            if (window.MEDISOFT_OFFLINE_BRANDING.hotelId) {
                window.localStorage.setItem('medisoft:offline-branding:hotel-' + window.MEDISOFT_OFFLINE_BRANDING.hotelId, JSON.stringify(window.MEDISOFT_OFFLINE_BRANDING));
            }
        } catch (error) {}
        <?php endif; ?>
    </script>

    <!-- O usando un meta tag -->
    <meta name="base-url" content="<?= rtrim(url(''), '/') ?>">
    <meta name="api-url" content="<?= url('api') ?>">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/custom.css') : asset('css/custom.css') ?>">

    <!-- CSS de la pantalla de carga -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/loading-screen.css') : asset('css/loading-screen.css') ?>">
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/pwa-launch-splash.css') : asset('css/pwa-launch-splash.css') ?>">

    <!-- CSS PWA (offline banner, toasts, install btn) -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/pwa.css') : asset('css/pwa.css') ?>">
    
    <!-- Solo en el dashboard (titulo "Inicio - ..."; se conserva "Dashboard" por compatibilidad) -->
    <?php if (isset($title) && (strpos($title, 'Dashboard') !== false || strpos($title, 'Inicio - ') === 0)): ?>
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/dashboard.css') : asset('css/dashboard.css') ?>">
    <?php endif; ?>
    
    <!-- Script de pantalla de carga -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/loading-screen.js') : asset('js/loading-screen.js') ?>"></script>
    <script src="<?= function_exists('asset_version') ? asset_version('js/pwa-launch-splash.js') : asset('js/pwa-launch-splash.js') ?>" defer></script>

    <!-- JavaScript Global -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/app.js') : asset('js/app.js') ?>" defer></script>

    <!-- PWA: registro de SW + lógica offline (reemplaza el script inline de SW) -->
    <?php if ($layoutOfflineHoteleroActivo): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/pwa.js') : asset('js/pwa.js') ?>" defer></script>

    <!-- Offline Data: snapshots de habitaciones/reservaciones + cola tipada + sync -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/offline-data.js') : asset('js/offline-data.js') ?>" defer></script>
    <?php endif; ?>

    <style>
        html {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        @media (max-width: 1024px) {
            input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
            select,
            textarea {
                font-size: 16px !important;
            }
        }

        /* PWA critical UI: evita banners planos si el CSS externo aun no carga */
        #pwa-offline-banner[hidden],
        #pwa-update-banner[hidden] {
            display: none !important;
        }

        #pwa-offline-banner,
        #pwa-update-banner {
            box-sizing: border-box;
            font-family: inherit;
        }

        #pwa-offline-banner {
            position: fixed;
            top: 14px;
            right: 18px;
            left: auto;
            width: min(460px, calc(100vw - 32px));
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 15px;
            border: 1px solid rgba(197, 136, 38, 0.28);
            border-radius: 12px;
            background: #fff9e9;
            color: #533916;
            box-shadow: 0 18px 48px rgba(42, 51, 36, 0.18);
            transform: translateY(-12px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.22s ease, transform 0.22s ease;
        }

        #pwa-offline-banner.visible {
            transform: translateY(0);
            opacity: 1;
            pointer-events: auto;
        }

        #pwa-offline-banner .pwa-banner-icon,
        #pwa-update-banner .pwa-update-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        #pwa-offline-banner .pwa-banner-icon {
            background: #f5dfab;
            color: #9a5f06;
        }

        #pwa-offline-banner .pwa-banner-text strong,
        #pwa-update-banner .pwa-update-copy strong {
            display: block;
            font-size: 0.84rem;
            line-height: 1.15;
            letter-spacing: 0;
            color: inherit;
        }

        #pwa-offline-banner .pwa-banner-text span,
        #pwa-update-banner .pwa-update-copy span {
            display: block;
            margin-top: 2px;
            font-size: 0.74rem;
            line-height: 1.25;
            opacity: 0.78;
        }

        #pwa-update-banner {
            position: fixed;
            right: 20px;
            bottom: 22px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            width: min(440px, calc(100vw - 32px));
            padding: 12px 12px 12px 14px;
            border: 1px solid rgba(124, 143, 86, 0.34);
            border-radius: 14px;
            background: #f9fbf4;
            color: #33422b;
            box-shadow: 0 20px 54px rgba(45, 59, 38, 0.2);
            transform: translateY(16px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.24s ease, transform 0.24s ease;
        }

        #pwa-update-banner.visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        #pwa-update-banner .pwa-update-icon {
            background: #e8efd9;
            color: #5c7442;
        }

        #pwa-update-banner .pwa-update-copy {
            flex: 1;
            min-width: 0;
        }

        #pwa-update-banner button[data-action="update"] {
            border: 0;
            border-radius: 10px;
            background: #5c7a4e;
            color: #f8fbf2;
            padding: 9px 12px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(92, 122, 78, 0.22);
        }

        #pwa-update-banner button[data-action="update"]:hover {
            background: #4f6b43;
        }

        @media (max-width: 640px) {
            #pwa-offline-banner,
            #pwa-update-banner {
                left: 12px;
                right: 12px;
                width: auto;
            }

            #pwa-update-banner {
                bottom: 14px;
            }
        }
        /* HEADER MÓVIL MODERNO CON AUTO-HIDE */
        .pwa-portrait-guard {
            position: fixed;
            inset: 0;
            z-index: 12000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: max(24px, env(safe-area-inset-top)) max(24px, env(safe-area-inset-right)) max(24px, env(safe-area-inset-bottom)) max(24px, env(safe-area-inset-left));
            background:
                radial-gradient(circle at 18% 18%, color-mix(in srgb, var(--brand-accent, #BD9441) 18%, transparent), transparent 18rem),
                linear-gradient(135deg, color-mix(in srgb, var(--brand-primary, #1B2746) 92%, #000000), var(--brand-secondary, #0F172A));
            color: var(--brand-action-text, #FFFEFB);
            text-align: center;
        }

        .pwa-portrait-guard-card {
            width: min(360px, 82vw);
            display: grid;
            justify-items: center;
            gap: 12px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 22px;
            background: rgba(255,255,255,.08);
            box-shadow: 0 24px 60px rgba(0,0,0,.32);
            padding: 24px 22px;
            backdrop-filter: blur(14px);
        }

        .pwa-portrait-guard-icon {
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: rgba(255,255,255,.12);
            color: var(--brand-accent, #BD9441);
            font-size: 1.55rem;
        }

        .pwa-portrait-guard-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 850;
            line-height: 1.2;
        }

        .pwa-portrait-guard-copy {
            margin: 0;
            color: rgba(255,255,255,.78);
            font-size: .86rem;
            font-weight: 650;
            line-height: 1.45;
        }

        @media (max-width: 1024px) and (orientation: landscape) and (pointer: coarse) {
            body.hotel-layout-scope {
                overflow: hidden !important;
            }

            body.hotel-layout-scope .pwa-portrait-guard {
                display: flex;
            }
        }

        @media (max-width: 1024px) {
            body {
                padding-top: 60px;
            }
            
            .mobile-header-modern {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 60px;
                background: linear-gradient(135deg, var(--brand-primary, #1B2746) 0%, var(--brand-secondary, #0F172A) 100%);
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 15px;
                transition: transform 0.3s ease;
            }
            
            .mobile-header-modern.hidden {
                transform: translateY(-100%);
            }

            /* Con CUALQUIER modal/overlay abierto en movil, el header se retira
               para no interferir ni asomarse por encima. Se apoya en los mismos
               indicadores que usa la barra inferior (detector generico
               body.hbn-overlay-open + clases directas + :has()), asi cubre
               todos los modales sin tener que tocarlos uno por uno. */
            body.hbn-overlay-open .mobile-header-modern,
            body.overflow-hidden .mobile-header-modern,
            body.swal2-shown .mobile-header-modern,
            body.hb-modal-open .mobile-header-modern,
            body.hb-mobile-sheet-open .mobile-header-modern,
            body:has(.swal2-container.swal2-backdrop-show) .mobile-header-modern,
            body:has(.fixed.inset-0:not(.hidden)) .mobile-header-modern {
                transform: translateY(-100%);
                pointer-events: none;
            }

            /* La barrita de progreso de scroll tambien se oculta con el modal. */
            body.hbn-overlay-open .scroll-progress,
            body.overflow-hidden .scroll-progress,
            body.swal2-shown .scroll-progress,
            body.hb-modal-open .scroll-progress,
            body.hb-mobile-sheet-open .scroll-progress,
            body:has(.swal2-container.swal2-backdrop-show) .scroll-progress,
            body:has(.fixed.inset-0:not(.hidden)) .scroll-progress {
                opacity: 0;
            }

            /* Botón de hamburguesa móvil */
            .mobile-menu-toggle {
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 8px;
                padding: 0.5rem;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s;
                margin-left: 5px;
            }

            .mobile-menu-toggle:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .mobile-menu-toggle:active {
                transform: scale(0.95);
            }

            .mobile-menu-toggle i {
                font-size: 1.25rem;
                color: white;
            }
            
            /* LOGO CENTRADO - TAMAÑO FIJO */
            .mobile-header-logo {
                position: absolute;
                left: 50%;
                transform: translateX(-50%);
                height: 44px;
                /* IMPORTANTE: Ancho fijo para evitar que se redimensione */
                width: auto;
                min-width: 120px; /* Ancho mínimo */
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2px 8px;
                border-radius: 12px;
                color: #fff;
                text-decoration: none;
                cursor: pointer;
            }

            .mobile-header-logo:focus-visible {
                outline: 2px solid rgba(255, 255, 255, 0.95);
                outline-offset: 3px;
                background: rgba(255, 255, 255, 0.12);
            }
            
          .mobile-header-logo img {
    height: 100%;
    width: auto;
    min-height: 40px;
    max-height: 40px;
    object-fit: contain;
    filter: brightness(0) invert(1)
            drop-shadow(0 0 6px rgba(92, 64, 51, 0.9))
            drop-shadow(0 0 14px rgba(92, 64, 51, 0.7))
            drop-shadow(0 0 24px rgba(92, 64, 51, 0.5));
}


            
            /* Prevenir que el logo se redimensione en diferentes vistas */
            .mobile-header-logo img,
            .mobile-header-logo {
                transition: none !important;
                transform-origin: center !important;
            }
            
            /* Asegurar tamaño consistente en todas las vistas */
            .main-content .mobile-header-logo img,
            .dashboard .mobile-header-logo img,
            .habitaciones .mobile-header-logo img,
            .reservaciones .mobile-header-logo img,
            .huespedes .mobile-header-logo img,
            .reportes .mobile-header-logo img,
            .configuracion .mobile-header-logo img,
            .inventario .mobile-header-logo img,
            .caja .mobile-header-logo img,
            .usuarios .mobile-header-logo img {
                height: 40px !important;
                min-height: 40px !important;
                max-height: 40px !important;
            }
            
            /* Botones de acción */
            .mobile-header-actions {
                display: flex;
                gap: 8px;
            }
            
            .mobile-header-actions button {
                width: 36px;
                height: 36px;
                background: rgba(255, 255, 255, 0.1);
                border: none;
                border-radius: 50%;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }

            /* Campana de notificaciones del header */
            .mobile-header-bell {
                position: relative;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.22);
                border: 1px solid rgba(255, 255, 255, 0.5);
                color: #fff;
                font-size: 1.15rem;
                text-decoration: none;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
                -webkit-tap-highlight-color: transparent;
                transition: background 0.2s ease, transform 0.15s ease;
            }

            .mobile-header-bell:hover {
                background: rgba(255, 255, 255, 0.3);
            }

            .mobile-header-bell:active {
                transform: scale(0.94);
            }

            .mobile-header-bell:focus-visible {
                outline: 2px solid rgba(255, 255, 255, 0.95);
                outline-offset: 2px;
            }

            /* Mismo rojo semántico que la burbuja de notificaciones del sidebar */
            .mobile-header-bell-badge {
                position: absolute;
                top: -3px;
                right: -3px;
                min-width: 17px;
                height: 17px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 4px;
                border-radius: 999px;
                background: #dc2626;
                color: #FFFEFB;
                font-size: 0.62rem;
                font-weight: 700;
                line-height: 1;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.35);
            }
            
            /* Barra de progreso */
            .scroll-progress {
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                height: 2px;
            background: var(--brand-accent, #BD9441);
                transform-origin: left;
                transform: scaleX(0);
                z-index: 9998;
            }
            
            /* Ocultar header desktop */
            header.bg-white {
                display: none !important;
            }
        }
        
        @media (min-width: 1025px) {
            .mobile-header-modern {
                display: none !important;
            }
            .scroll-progress {
                display: none !important;
            }
            /* Header desktop sin contenido: se oculta para no mostrar una barra blanca vacía.
               El page-header con título y acciones se integrará en la microfase 10.4. */
            header.bg-white {
                display: none;
            }
        }
    </style>
    <?php if (!$layoutEsPanelSaas): ?>
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/hotel-layout-shell.css') : asset('css/hotel-layout-shell.css') ?>">
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/dark-theme.css') : asset('css/dark-theme.css') ?>">
    <?php if ($layoutTemaCssHref): ?>
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version($layoutTemaCssHref) : asset($layoutTemaCssHref) ?>">
    <?php endif; ?>
    <?php if ($layoutSystemBackgroundColor): ?>
    <style id="hotel-system-background">
        :root {
            --brand-system-background: <?= htmlspecialchars($layoutSystemBackgroundColor, ENT_QUOTES, 'UTF-8') ?>;
        }

        html:not([data-theme="dark"]) body.hotel-layout-scope {
            /* El tema puede definir --hotel-bg con mayor especificidad. */
            --hotel-bg: var(--brand-system-background) !important;
            background: var(--brand-system-background) !important;
        }

        html:not([data-theme="dark"]) body.hotel-layout-scope .main-content {
            background: var(--brand-system-background) !important;
        }

        /*
         * Algunas vistas historicas pintan un lienzo de pagina propio encima
         * de .main-content. Solo alcanzamos el contenedor raiz de la vista:
         * las tarjetas, tablas, modales y superficies internas conservan sus
         * colores semanticos.
         */
        html:not([data-theme="dark"]) body.hotel-layout-scope .main-content > :is(
            [class*="-page"],
            [class*="-view"],
            [class~="min-h-screen"],
            .dashboard-boutique,
            .res-bookings,
            .vista-reservacion,
            .reservation-detail-v2,
            .rdv3,
            .habitaciones-view,
            .hdv,
            .vista-historial,
            .page-container,
            .op-daily,
            .cj-page,
            .usr-bg,
            .inv-page,
            .hc-page,
            .invoice-desk,
            .payroll-preview,
            .workers-report,
            .labor-cash-report,
            .nomina-detalle,
            .nomina-periodos,
            .nomina-audit,
            .nomina-exp,
            .snapshot-pay-report,
            .payroll-snapshot-report,
            .mant-prog,
            .mact,
            .cpv,
            .du,
            .fc,
            .cam,
            .cnl,
            .lea,
            .rep,
            .wav,
            .mrv,
            .iav,
            .vgf,
            .msj,
            .na,
            .hcal,
            .arx
        ) {
            background: var(--brand-system-background) !important;
        }

        /* Las texturas del contenedor raiz tampoco deben cubrir el lienzo. */
        html:not([data-theme="dark"]) body.hotel-layout-scope .main-content > :is(
            [class*="-page"],
            [class*="-view"],
            [class~="min-h-screen"],
            .dashboard-boutique,
            .res-bookings,
            .vista-reservacion,
            .reservation-detail-v2,
            .rdv3,
            .habitaciones-view,
            .hdv,
            .vista-historial,
            .page-container,
            .op-daily,
            .cj-page,
            .usr-bg,
            .inv-page,
            .hc-page,
            .invoice-desk,
            .payroll-preview,
            .workers-report,
            .labor-cash-report,
            .nomina-detalle,
            .nomina-periodos,
            .nomina-audit,
            .nomina-exp,
            .snapshot-pay-report,
            .payroll-snapshot-report,
            .mant-prog,
            .mact,
            .cpv,
            .du,
            .fc,
            .cam,
            .cnl,
            .lea,
            .rep,
            .wav,
            .mrv,
            .iav,
            .vgf,
            .msj,
            .na,
            .hcal,
            .arx
        )::before,
        html:not([data-theme="dark"]) body.hotel-layout-scope .main-content > :is(
            [class*="-page"],
            [class*="-view"],
            [class~="min-h-screen"],
            .dashboard-boutique,
            .res-bookings,
            .vista-reservacion,
            .reservation-detail-v2,
            .rdv3,
            .habitaciones-view,
            .hdv,
            .vista-historial,
            .page-container,
            .op-daily,
            .cj-page,
            .usr-bg,
            .inv-page,
            .hc-page,
            .invoice-desk,
            .payroll-preview,
            .workers-report,
            .labor-cash-report,
            .nomina-detalle,
            .nomina-periodos,
            .nomina-audit,
            .nomina-exp,
            .snapshot-pay-report,
            .payroll-snapshot-report,
            .mant-prog,
            .mact,
            .cpv,
            .du,
            .fc,
            .cam,
            .cnl,
            .lea,
            .rep,
            .wav,
            .mrv,
            .iav,
            .vgf,
            .msj,
            .na,
            .hcal,
            .arx
        )::after {
            background-color: transparent !important;
            background-image: none !important;
        }

        /* Configuracion usa cada seccion como un segundo lienzo de pagina. */
        html:not([data-theme="dark"]) body.hotel-layout-scope .hc-panel[data-hc-section] {
            background: var(--brand-system-background) !important;
        }

        html:not([data-theme="dark"]) body.hotel-layout-scope #psk {
            background: var(--brand-system-background) !important;
        }
    </style>
    <?php endif; ?>
    <?php endif; ?>
</head>
<body class="bg-gray-100 font-inter<?= $layoutEsPanelSaas ? ' ms-admin-scope' : ' hotel-layout-scope' ?><?= htmlspecialchars($layoutPageClass, ENT_QUOTES, 'UTF-8') ?>">
  <?php include APP_PATH . '/views/components/pwa-launch-splash.php'; ?>

  <div class="pwa-portrait-guard" role="alert" aria-live="assertive" aria-label="Modo vertical requerido">
    <div class="pwa-portrait-guard-card">
      <span class="pwa-portrait-guard-icon"><i class="fas fa-mobile-screen-button" aria-hidden="true"></i></span>
      <p class="pwa-portrait-guard-title">Usa la app en vertical</p>
      <p class="pwa-portrait-guard-copy">Gira tu teléfono para seguir trabajando.</p>
    </div>
  </div>

  <!-- ── Banner Offline ─────────────────────────────────────────────────── -->
  <div id="pwa-offline-banner" role="alert" aria-live="assertive" hidden>
    <span class="pwa-banner-icon"><i class="fas fa-wifi"></i></span>
    <div class="pwa-banner-text">
      <strong>Sin conexión a internet</strong>
      <!-- El texto lo escribe pwa.js segun si la captura offline esta encendida:
           hoy esta apagada y prometer "se enviaran solos" seria falso. -->
      <span data-pwa-banner-copy>Puedes consultar la información ya cargada. Para guardar cambios hace falta conexión.</span>
    </div>
  </div>

  <!-- ── Banner de nueva versión disponible ────────────────────────────── -->
  <div id="pwa-update-banner" role="status" aria-live="polite" hidden>
    <span class="pwa-update-icon"><i class="fas fa-rotate"></i></span>
    <span class="pwa-update-copy">
      <strong>Nueva versión lista</strong>
      <span>Actualiza para cargar las mejoras recientes.</span>
    </span>
    <button data-action="update"><i class="fas fa-bolt"></i><span>Actualizar</span></button>
  </div>

  <!-- ── Toast / alertas flotantes (siempre visibles, no se pierden con el scroll) ── -->
  <?php include APP_PATH . '/views/partials/toast.php'; ?>

  <!-- ── Modal de confirmación global + estados de página (msConfirm / msPageState) ── -->
  <?php include APP_PATH . '/views/partials/confirm.php'; ?>

  <!-- ── Micro-interacciones globales: count-up, entrada escalonada, press-state (MedisoftMotion) ── -->
  <?php include APP_PATH . '/views/partials/motion.php'; ?>

  <!-- ── Barra inferior de atajos: se renderiza temprano para estar visible
       desde el primer paint, igual que el header móvil ── -->
  <?php include APP_PATH . '/views/layout/footer-nav.php'; ?>

    <!-- Incluir pantalla de carga -->
    <?php include APP_PATH . '/views/components/loading-screen.php'; ?>
    
    <!-- HEADER MÓVIL MODERNO (Solo aparece en móvil) -->
    <div class="mobile-header-modern" id="mobileHeaderModern">
        <a href="<?= url('dashboard') ?>"
           class="mobile-header-logo"
           aria-label="Ir a Inicio"
           title="Ir a Inicio">
            <img src="<?= htmlspecialchars($layoutLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!$layoutEsPanelSaas): ?>
            <span class="mobile-header-name"><?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </a>
        
        <!-- Acciones: notificaciones + sync + install -->
        <div class="mobile-header-actions" style="display:flex;align-items:center;gap:6px;">
            <?php
            // Campana de notificaciones: mismo gating por módulo Y permiso que la
            // sidebar y misma fuente del conteo (sidebar_novedades, cacheado en
            // sesión). El permiso es obligatorio desde que 'notificaciones' es
            // paquete base: el módulo ya no esconde la campana de ningún rol.
            $headerBellVisible = $layoutOfflineHoteleroActivo
                && (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('notificaciones'))
                && (!function_exists('can') || can('notificaciones.view'));
            $headerBellCount = 0;
            if ($headerBellVisible && $layoutPathSegment !== 'notificaciones') {
                require_once APP_PATH . '/helpers/sidebar_novedades.php';
                $headerBellCount = (int) (sidebar_novedades()['notificaciones']['count'] ?? 0);
            }
            ?>
            <?php if ($headerBellVisible): ?>
            <a href="<?= url('notificaciones') ?>"
               class="mobile-header-bell"
               aria-label="<?= $headerBellCount > 0 ? 'Notificaciones: ' . $headerBellCount . ' sin leer' : 'Notificaciones' ?>"
               title="Notificaciones">
                <i class="fas fa-bell" aria-hidden="true"></i>
                <?php if ($headerBellCount > 0): ?>
                <span class="mobile-header-bell-badge"><?= $headerBellCount > 99 ? '99+' : $headerBellCount ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <!-- Indicador de sincronización (toca para ver operaciones offline) -->
            <div id="pwa-sync-indicator" title="Cambios sin enviar"
                 style="cursor:pointer;"
                 onclick="window.location.href='<?= url('offline/pendientes') ?>'">
                <i class="fas fa-sync-alt"></i>
                <span id="pwa-queue-badge" class="hidden">0</span>
            </div>
            <!-- Botón instalar PWA (oculto hasta que el navegador lo permita) -->
            <button id="pwa-install-btn" class="hidden" onclick="window.triggerInstall()" title="Instalar app">
                <i class="fas fa-download"></i>
                <span>Instalar</span>
            </button>
        </div>
    </div>
    
    <!-- Barra de progreso de scroll -->
    <div class="scroll-progress" id="scrollProgress"></div>
    
    <!-- Contenedor Principal -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php require_once APP_PATH . '/views/layout/sidebar.php'; ?>
        
        <!-- Contenido Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Desktop (solo visible en desktop) -->
            <header class="bg-white shadow-sm border-b border-gray-200 relative hotel-header">
                <div class="hotel-header-inner">
                    <div class="hotel-header-brand">
                        <img src="<?= htmlspecialchars($layoutLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
                        <div>
                            <p class="hotel-header-kicker">Hotel activo</p>
                            <p class="hotel-header-name"><?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="hotel-header-page">
                        <p class="hotel-header-kicker">Recepción</p>
                        <h1><?= htmlspecialchars($title ?? 'Panel hotelero', ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>

                    <div class="hotel-header-actions">
                        <span class="hotel-header-status">
                            <span class="hotel-session-dot"></span>
                            <span>Sesión activa</span>
                        </span>
                        <span class="hotel-header-user">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars(user_name(), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            </header>
            
            <!-- Contenido de la página -->
            <main class="main-content">
                <!-- Mensajes Flash → ahora se renderizan como toast flotante (siempre visible) en partials/toast.php -->
<?php
/* ── Skeleton de carga estilo FB ─────────────────────────────────
 *  Tipo A → dashboard / calendario / vistas con columnas
 *  Tipo B → listas (reservaciones, habitaciones, huespedes, etc.)
 *  Tipo C → detalle de un registro (ver/N)
 * ──────────────────────────────────────────────────────────────── */
$_skUri  = $_SERVER['REQUEST_URI'] ?? '';
$_skType = 'b'; // default: lista
if (preg_match('#/(dashboard|inicio|calendario|reportes)#i', $_skUri)) {
    $_skType = 'a';                                              // panel / vistas con columnas
} elseif (preg_match('#/(crear|editar|nuevo|agregar|form)(/\d+)?/?(\?.*)?$#i', $_skUri)) {
    $_skType = 'd';                                              // formulario (alta / edición)
} elseif (preg_match('#/(ver|detalle)(/\d+)?/?(\?.*)?$#i', $_skUri)) {
    $_skType = 'c';                                              // detalle de un registro
}
// Skeleton propio por vista (solo la ruta base). Si existe el archivo, gana al genérico.
$_skView = '';
if (preg_match('#/(dashboard|inicio|habitaciones|reservaciones|caja|huespedes|inventario|reportes|cuentas-por-cobrar|cuentas-por-pagar|compras|proveedores|documentos)/?($|\?)#i', $_skUri, $_skM)) {
    $_skView = strtolower($_skM[1]) === 'inicio' ? 'dashboard' : strtolower($_skM[1]);
    if (!is_file(APP_PATH . "/views/skeletons/{$_skView}.php")) {
        $_skView = '';                                          // sin archivo → cae al genérico A/B/C/D
    }
}
// Panel SaaS admin → no skeleton
$_skShow = !($layoutEsPanelSaas ?? false);
if ($_skShow):
?>
<style>
/* ── Skeleton global ───────────────────────────────────────────── */
#psk{
    position:fixed;
    inset:0;
    z-index:500;
    background:#FCFBF7; /* blanco cálido: mezcla con el lienzo boutique, no un flash frío */
    overflow:hidden;
    pointer-events:none;
    transition:opacity .3s ease;
}
/* En desktop queda a la derecha del sidebar */
@media(min-width:1025px){
    #psk{ left:268px; }
    .ms-admin-scope #psk{ left:0; }
}
@media(max-width:1024px){
    #psk{ top:60px; } /* debajo del header mobile */
}
#psk.psk-out{ opacity:0; }

/* ── Shimmer ── */
@keyframes psk-shimmer{
    0%  { background-position:-600px 0 }
    100%{ background-position: 600px 0 }
}
.psk-bone{
    border-radius:8px;
    background:linear-gradient(90deg,#EBEBEB 25%,#F5F5F5 50%,#EBEBEB 75%);
    background-size:1200px 100%;
    animation:psk-shimmer 1.5s infinite ease-in-out;
}
/* Variantes de grosor */
.psk-circle { border-radius:50% !important; }
.psk-r4     { border-radius:4px !important; }
.psk-r12    { border-radius:12px !important; }
.psk-r16    { border-radius:16px !important; }

/* ── Contenedor del skeleton ── */
.psk-wrap{
    padding:22px 22px 0;
    display:flex;
    flex-direction:column;
    gap:18px;
    height:100%;
    box-sizing:border-box;
}
/* Cabecera */
.psk-hd{ display:flex; align-items:center; gap:12px; }
.psk-hd-circle{ width:44px; height:44px; flex-shrink:0; }
.psk-hd-lines{ flex:1; display:flex; flex-direction:column; gap:7px; }
/* Fila de stats */
.psk-stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
@media(max-width:640px){ .psk-stats{ grid-template-columns:repeat(2,1fr); } }
.psk-stat-card{ height:78px; }
/* Layout 2 columnas */
.psk-cols{ display:grid; grid-template-columns:1fr 280px; gap:14px; flex:1; min-height:0; }
@media(max-width:900px){ .psk-cols{ grid-template-columns:1fr; } }
.psk-main{ display:flex; flex-direction:column; gap:10px; }
.psk-side{ display:flex; flex-direction:column; gap:10px; }
/* Card grande */
.psk-card{ border-radius:16px; overflow:hidden; }
/* Lista rows */
.psk-rows{ display:flex; flex-direction:column; gap:8px; }
.psk-row{ display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #F5F5F5; }
.psk-row:last-child{ border-bottom:none; }
/* Toolbar */
.psk-toolbar{ display:flex; gap:10px; align-items:center; }
/* Detail hero */
.psk-hero{ height:130px; }
.psk-detail-cols{ display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:14px; flex:1; min-height:0; }
@media(max-width:800px){ .psk-detail-cols{ grid-template-columns:1fr; } }
/* ── Formulario (tipo D) ── */
.psk-form{ display:flex; flex-direction:column; gap:16px; }
.psk-fieldset{ display:flex; flex-direction:column; gap:12px; padding:16px; border:1px solid #F1EEE8; border-radius:16px; }
html[data-theme="dark"] .psk-fieldset{ border-color:#38352C; }
.psk-grid2{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:640px){ .psk-grid2{ grid-template-columns:1fr; } }
.psk-field{ display:flex; flex-direction:column; gap:8px; }
/* ── Rejillas responsivas compartidas por los skeletons de vista ── */
.psk-g6{ display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
.psk-g5{ display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
.psk-g3{ display:grid; grid-template-columns:1fr; gap:12px; }
.psk-split{ display:grid; grid-template-columns:1fr; gap:14px; flex:1; min-height:0; }
.psk-hdr-col{ display:flex; flex-direction:column; gap:9px; }
@media(min-width:640px){
    .psk-g6{ grid-template-columns:repeat(3,1fr); }
    .psk-g3{ grid-template-columns:repeat(3,1fr); }
}
@media(min-width:1025px){
    .psk-g6{ grid-template-columns:repeat(6,1fr); }
    .psk-g5{ grid-template-columns:repeat(5,1fr); }
    .psk-split{ grid-template-columns:1.6fr 1fr; }
}
/* ── Respeta reduced-motion: sin shimmer, tono estable ── */
@media (prefers-reduced-motion: reduce){
    .psk-bone{ animation:none; background:#EEECE6; }
    html[data-theme="dark"] .psk-bone{ animation:none; background:#26241E; }
}
</style>

<div id="psk" role="status" aria-label="Cargando…">
<?php if ($_skView): /* ── Skeleton propio de la vista ── */ ?>
<?php include APP_PATH . "/views/skeletons/{$_skView}.php"; ?>
<?php elseif ($_skType === 'a'): /* ── TIPO A: Dashboard / Calendario ── */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-circle psk-hd-circle"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:38%"></div>
            <div class="psk-bone" style="height:20px;width:55%"></div>
        </div>
        <div class="psk-bone psk-r12" style="height:36px;width:120px;margin-left:auto"></div>
    </div>
    <div class="psk-stats">
        <div class="psk-bone psk-stat-card psk-r16"></div>
        <div class="psk-bone psk-stat-card psk-r16"></div>
        <div class="psk-bone psk-stat-card psk-r16"></div>
        <div class="psk-bone psk-stat-card psk-r16"></div>
    </div>
    <div class="psk-cols">
        <div class="psk-main">
            <div class="psk-bone psk-r16" style="height:44px"></div>
            <div class="psk-bone psk-r16" style="height:200px"></div>
            <div class="psk-rows">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="psk-row">
                    <div class="psk-bone psk-circle" style="width:36px;height:36px;flex-shrink:0"></div>
                    <div style="flex:1;display:flex;flex-direction:column;gap:6px">
                        <div class="psk-bone" style="height:10px;width:<?= [60,75,50,68][$i] ?>%"></div>
                        <div class="psk-bone" style="height:10px;width:<?= [40,55,35,45][$i] ?>%"></div>
                    </div>
                    <div class="psk-bone psk-r12" style="height:24px;width:64px"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
        <div class="psk-side">
            <div class="psk-bone psk-r16" style="height:160px"></div>
            <div class="psk-bone psk-r16" style="height:110px"></div>
            <div class="psk-bone psk-r16" style="height:90px"></div>
        </div>
    </div>
</div>

<?php elseif ($_skType === 'b'): /* ── TIPO B: Lista ── */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:28%"></div>
            <div class="psk-bone" style="height:24px;width:42%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:36px;width:90px"></div>
            <div class="psk-bone psk-r12" style="height:36px;width:120px"></div>
        </div>
    </div>
    <div class="psk-toolbar">
        <div class="psk-bone psk-r12" style="height:40px;flex:1"></div>
        <div class="psk-bone psk-r12" style="height:40px;width:100px"></div>
        <div class="psk-bone psk-r12" style="height:40px;width:80px"></div>
    </div>
    <div class="psk-bone psk-r16" style="height:48px"></div>
    <div class="psk-rows">
        <?php
        $pskW1 = [80,65,72,58,70,62,75];
        $pskW2 = [55,42,50,38,48,40,52];
        for ($i = 0; $i < 7; $i++):
        ?>
        <div class="psk-row">
            <div class="psk-bone psk-circle" style="width:40px;height:40px;flex-shrink:0"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:7px">
                <div class="psk-bone" style="height:11px;width:<?= $pskW1[$i] ?>%"></div>
                <div class="psk-bone" style="height:10px;width:<?= $pskW2[$i] ?>%"></div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
                <div class="psk-bone psk-r12" style="height:22px;width:72px"></div>
                <div class="psk-bone" style="height:9px;width:50px"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="psk-bone psk-r12" style="height:38px;width:220px;margin:0 auto"></div>
</div>

<?php elseif ($_skType === 'd'): /* ── TIPO D: Formulario (alta / edición) ── */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r12" style="height:36px;width:36px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:24%"></div>
            <div class="psk-bone" style="height:22px;width:48%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:38px;width:96px"></div>
            <div class="psk-bone psk-r12" style="height:38px;width:130px"></div>
        </div>
    </div>
    <div class="psk-form">
        <div class="psk-fieldset">
            <div class="psk-bone" style="height:13px;width:34%"></div>
            <div class="psk-grid2">
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="psk-field">
                    <div class="psk-bone psk-r4" style="height:9px;width:<?= [42,54,38,60][$i] ?>%"></div>
                    <div class="psk-bone psk-r12" style="height:44px"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="psk-field">
                <div class="psk-bone psk-r4" style="height:9px;width:28%"></div>
                <div class="psk-bone psk-r12" style="height:92px"></div>
            </div>
        </div>
        <div class="psk-fieldset">
            <div class="psk-bone" style="height:13px;width:40%"></div>
            <div class="psk-grid2">
                <?php for ($i = 0; $i < 2; $i++): ?>
                <div class="psk-field">
                    <div class="psk-bone psk-r4" style="height:9px;width:<?= [50,44][$i] ?>%"></div>
                    <div class="psk-bone psk-r12" style="height:44px"></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;padding-bottom:22px">
        <div class="psk-bone psk-r12" style="height:44px;width:120px"></div>
        <div class="psk-bone psk-r12" style="height:44px;width:170px"></div>
    </div>
</div>

<?php else: /* ── TIPO C: Detalle ── */ ?>
<div class="psk-wrap">
    <div class="psk-hd">
        <div class="psk-bone psk-r12" style="height:36px;width:36px;flex-shrink:0"></div>
        <div class="psk-hd-lines">
            <div class="psk-bone" style="height:11px;width:25%"></div>
            <div class="psk-bone" style="height:22px;width:45%"></div>
        </div>
        <div style="display:flex;gap:8px;margin-left:auto">
            <div class="psk-bone psk-r12" style="height:36px;width:80px"></div>
            <div class="psk-bone psk-r12" style="height:36px;width:100px"></div>
        </div>
    </div>
    <div class="psk-bone psk-hero psk-r16"></div>
    <div class="psk-detail-cols">
        <div class="psk-main">
            <div class="psk-bone psk-r16" style="height:130px"></div>
            <div class="psk-rows">
                <?php
                $pskDW = [70,55,80,45,65];
                for ($i = 0; $i < 5; $i++):
                ?>
                <div class="psk-row">
                    <div class="psk-bone psk-r4" style="height:10px;width:20%"></div>
                    <div class="psk-bone" style="height:10px;width:<?= $pskDW[$i] ?>%;margin-left:16px"></div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="psk-bone psk-r16" style="height:100px"></div>
        </div>
        <div class="psk-side">
            <div class="psk-bone psk-r16" style="height:200px"></div>
            <div class="psk-bone psk-r16" style="height:140px"></div>
        </div>
    </div>
</div>
<?php endif; ?>
</div><!-- /#psk -->

<script>
(function(){
    var sk = document.getElementById('psk');
    if (!sk) return;
    var showTimer = 0, safety = 0;

    function hide(){
        clearTimeout(safety);
        sk.classList.add('psk-out');
        setTimeout(function(){ sk.style.display = 'none'; }, 340);
    }
    function show(){
        clearTimeout(safety);
        sk.style.transition = 'none';       // aparece al instante (sin fundido) para cubrir ya
        sk.style.display = '';
        sk.classList.remove('psk-out');
        void sk.offsetWidth;
        sk.style.transition = '';
        safety = setTimeout(hide, 8000);    // nunca dejarlo pegado si la navegación no ocurre
    }

    // Descarte inicial (tras el pintado de la página). No se remueve del DOM: se reutiliza.
    if (document.readyState === 'complete') {
        setTimeout(hide, 60);
    } else {
        window.addEventListener('load', function(){ setTimeout(hide, 80); }, { once: true });
        setTimeout(hide, 3500); // respaldo si 'load' tarda demasiado
    }

    // ── Reaparece al navegar internamente: cubre el "think-time" del servidor,
    //    el instante en que la página vieja se queda congelada tras el clic. ──
    function ignore(a, e){
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return true;
        if (a.target && a.target !== '_self') return true;
        if (a.hasAttribute('download') || a.hasAttribute('data-no-skeleton')) return true;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) return true;
        var url; try { url = new URL(a.href, location.href); } catch (_) { return true; }
        if (url.origin !== location.origin) return true;
        // Descargas / exports / impresión: navegan a un archivo, no cambian de pantalla
        if (/\.(pdf|xlsx?|csv|zip|docx?|png|jpe?g)(\?|$)/i.test(url.pathname)) return true;
        if (/\/(export|descargar|download|print|pdf)(\/|\?|$)/i.test(url.pathname)) return true;
        // Ancla dentro de la misma página → no es navegación
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return true;
        return false;
    }
    // Burbuja (no captura): si otro handler cancela la navegación (links AJAX), no mostramos nada.
    document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
        if (!a || ignore(a, e)) return;
        clearTimeout(showTimer);
        showTimer = setTimeout(show, 120); // sin parpadeo en paginas instantaneas
    });

    // Al volver con "atras" (bfcache) no dejar el skeleton puesto.
    window.addEventListener('pageshow', function(ev){ clearTimeout(showTimer); if (ev.persisted) hide(); });
    window.addEventListener('pagehide', function(){ clearTimeout(showTimer); });
})();
</script>
<?php endif; // $_skShow ?>


    <!-- SCRIPT PARA AUTO-HIDE -->
    <script>
        (function () {
            async function lockPwaPortrait() {
                const isHotelMobile = document.body.classList.contains('hotel-layout-scope')
                    && window.matchMedia('(max-width: 1024px) and (pointer: coarse)').matches;

                if (!isHotelMobile || !window.screen || !window.screen.orientation || typeof window.screen.orientation.lock !== 'function') {
                    return;
                }

                try {
                    await window.screen.orientation.lock('portrait-primary');
                } catch (error) {
                    // iOS y algunos navegadores rechazan este bloqueo en PWAs web.
                    // El overlay CSS de paisaje queda como respaldo visual.
                }
            }

            window.lockPwaPortrait = lockPwaPortrait;

            document.addEventListener('DOMContentLoaded', lockPwaPortrait);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    lockPwaPortrait();
                }
            });
            window.addEventListener('resize', lockPwaPortrait, { passive: true });
            window.addEventListener('orientationchange', lockPwaPortrait, { passive: true });
            document.addEventListener('pointerdown', lockPwaPortrait, { passive: true });
        })();

        // Ejecutar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {

            // Variables para auto-hide
            let lastScrollTop = 0;
            let ticking = false;
            const mobileHeader = document.getElementById('mobileHeaderModern');
            const scrollProgress = document.getElementById('scrollProgress');
            const mainScrollCandidate = document.querySelector('.main-content');
            const scrollContainer = (function () {
                if (mainScrollCandidate) {
                    const overflowY = window.getComputedStyle(mainScrollCandidate).overflowY;
                    if (/auto|scroll|overlay/i.test(overflowY)) {
                        return mainScrollCandidate;
                    }
                }

                return document.scrollingElement || document.documentElement;
            })();
            const isDocumentScroll = scrollContainer === document.documentElement || scrollContainer === document.body;
            const isHotelLayout = document.body.classList.contains('hotel-layout-scope');
            
            // Verificar que el header existe
            if (mobileHeader) {

                // Función para manejar el scroll
                function handleScroll() {
                    const scrollTop = isDocumentScroll
                        ? (window.pageYOffset || document.documentElement.scrollTop)
                        : scrollContainer.scrollTop;
                    const scrollHeight = Math.max(
                        0,
                        isDocumentScroll
                            ? document.documentElement.scrollHeight - document.documentElement.clientHeight
                            : scrollContainer.scrollHeight - scrollContainer.clientHeight
                    );
                    
                    // Solo en móvil
                    if (window.innerWidth <= 1024) {
                        // Actualizar barra de progreso
                        if (scrollProgress && scrollHeight > 0) {
                            const percentage = scrollTop / scrollHeight;
                            scrollProgress.style.transform = `scaleX(${percentage})`;
                        }

                        if (isHotelLayout) {
                            mobileHeader.classList.remove('hidden');
                            document.body.classList.remove('header-hidden');
                            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                            ticking = false;
                            return;
                        }
                        
                        // Auto-hide del header con umbral mínimo
                        if (scrollTop > lastScrollTop && scrollTop > 10) {
                            // Scrolling hacia abajo - ocultar y pantalla completa
                            mobileHeader.classList.add('hidden');
                            document.body.classList.add('header-hidden');
                        } else if (scrollTop < lastScrollTop) {
                            // Scrolling hacia arriba - mostrar
                            mobileHeader.classList.remove('hidden');
                            document.body.classList.remove('header-hidden');
                        }
                        
                        // Si estamos en el top, siempre mostrar
                        if (scrollTop <= 0) {
                            mobileHeader.classList.remove('hidden');
                            document.body.classList.remove('header-hidden');
                        }
                    }
                    
                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                    ticking = false;
                }
                
                // Optimizar con requestAnimationFrame
                function requestTick() {
                    if (!ticking) {
                        window.requestAnimationFrame(handleScroll);
                        ticking = true;
                    }
                }
                
                // Escuchar el scroll real del layout. En movil/PWA vive dentro de main-content.
                (isDocumentScroll ? window : scrollContainer).addEventListener('scroll', requestTick, { passive: true });
                window.addEventListener('resize', requestTick, { passive: true });
                
                // PRUEBA MANUAL mejorada
                window.toggleHeader = function() {
                    if (mobileHeader.classList.contains('hidden')) {
                        mobileHeader.classList.remove('hidden');
                        document.body.classList.remove('header-hidden');
                    } else {
                        mobileHeader.classList.add('hidden');
                        document.body.classList.add('header-hidden');
                    }
                };
            }
            
            // Asegurar que el header sea visible al cargar
            if (window.innerWidth <= 1024 && mobileHeader) {
                mobileHeader.classList.remove('hidden');
                document.body.classList.remove('header-hidden');
            }
        });
    </script>
    
    <!-- Script para forzar ocultación de pantalla de carga -->
    <script>
        // Forzar ocultación después de 2 segundos máximo
        setTimeout(function() {
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen && loadingScreen.style.display !== 'none') {
                loadingScreen.style.display = 'none';
                document.body.classList.remove('loading');
            }
        }, 2000);
    </script>

    <!-- ── Barra de navegación global (flecha + migas + hotel): se pinta antes
         del contenido de cada vista; excluye Dashboard, Reportes y vistas con
         barra propia (ver partials/view_topbar.php) ── -->
    <?php include APP_PATH . '/views/partials/view_topbar.php'; ?>

    <!-- ── Flechas de regreso: retroceso REAL de historial ──
         back_url() (PHP) apunta la flecha a la página del Referer, pero navegar
         "hacia adelante" a ese destino crea OTRA entrada de historial: entre dos
         pantallas las flechas terminan en ping-pong A↔B y nunca salen del par.
         Arreglo: si el destino de la flecha ES la página de la que venimos,
         retrocedemos de verdad (history.back()), igual que la flecha del
         navegador — restaura scroll/estado y los clics sucesivos siguen
         retrocediendo. Si PHP eligió el fallback (venías de un formulario, de la
         misma página, de fuera de la app) o no hay historial (pestaña nueva),
         la navegación normal al href se respeta con todas sus protecciones.
         Cubre TODAS las flechas del sistema: .ms-vtb-back (barra global),
         .ms-back (partial móvil) y .ms-back-legacy (botones de cada vista). -->
    <script>
    (function() {
        var normalizar = function(u) {
            try {
                var url = new URL(u, window.location.href);
                var path = url.pathname.length > 1 ? url.pathname.replace(/\/+$/, '') : url.pathname;
                return url.origin + path + url.search;
            } catch (e) { return null; }
        };
        document.addEventListener('click', function(ev) {
            if (ev.defaultPrevented || ev.button !== 0) { return; }
            if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.altKey) { return; } // pestaña/ventana nueva: nativo
            var flecha = ev.target && ev.target.closest
                ? ev.target.closest('a.ms-vtb-back, a.ms-back, a.ms-back-legacy')
                : null;
            if (!flecha || !flecha.getAttribute('href') || flecha.target === '_blank') { return; }
            if (window.history.length <= 1 || !document.referrer) { return; } // sin historial: href normal
            var destino = normalizar(flecha.href);
            if (destino && destino === normalizar(document.referrer)) {
                ev.preventDefault();
                window.history.back();
            }
        });
    })();
    </script>
</body>
</html>
