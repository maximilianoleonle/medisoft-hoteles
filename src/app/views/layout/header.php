<?php
$layoutRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$layoutEsPanelSaas = strpos($layoutRequestPath, '/admin/saas') === 0;
$layoutBranding = (!$layoutEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_branding'))
    ? current_hotel_branding()
    : null;
$layoutNombreVisual = $layoutBranding
    ? hotel_branding_public_name($layoutBranding, current_hotel_nombre() ?: 'Los Cedros')
    : ($layoutEsPanelSaas ? 'Medisoft' : 'Los Cedros');
$layoutLogoUrl = ($layoutBranding && function_exists('hotel_branding_asset_url'))
    ? (hotel_branding_asset_url($layoutBranding['logo_url'] ?? null) ?: asset('img/logo-hotel-san-nicolas2.png'))
    : asset('img/logo-hotel-san-nicolas2.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Sistema de Gestión Hotelera - Los Cedros, Santa Catarina Juquila, Oaxaca">
    <title><?= $title ?? 'Los Cedros' ?></title>
    
    <!-- PWA Meta Tags -->
    <!-- ACTUALIZADO: Color verde olivo para la barra de estado -->
    <meta name="theme-color" content="#9CA777">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Los Cedros">
    <meta name="application-name" content="Los Cedros">
    <meta name="msapplication-TileColor" content="#9CA777">
    <meta name="msapplication-TileImage" content="<?= asset('img/icons/icon-144x144.png') ?>">
    <meta name="msapplication-config" content="<?= asset('browserconfig.xml') ?>">
    <meta name="format-detection" content="telephone=no">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    
    <!-- Manifest PWA -->
    <link rel="manifest" href="<?= asset('manifest.json') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('img/favicon-16x16.png') ?>">
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    
    <!-- iOS Icons -->
    <link rel="apple-touch-icon" href="<?= asset('img/icons/icon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="<?= asset('img/icons/icon-72x72.png') ?>">
    <link rel="apple-touch-icon" sizes="96x96" href="<?= asset('img/icons/icon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="128x128" href="<?= asset('img/icons/icon-128x128.png') ?>">
    <link rel="apple-touch-icon" sizes="144x144" href="<?= asset('img/icons/icon-144x144.png') ?>">
    <link rel="apple-touch-icon" sizes="152x152" href="<?= asset('img/icons/icon-152x152.png') ?>">
    <link rel="apple-touch-icon" sizes="192x192" href="<?= asset('img/icons/icon-192x192.png') ?>">
    <link rel="apple-touch-icon" sizes="384x384" href="<?= asset('img/icons/icon-384x384.png') ?>">
    <link rel="apple-touch-icon" sizes="512x512" href="<?= asset('img/icons/icon-512x512.png') ?>">
    
    <!-- Preconnect para optimización -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fix para layout del dashboard - Cargar al final -->
    <link rel="stylesheet" href="<?= asset('css/dashboard-layout-fix.css') ?>">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS del sidebar -->
    <link rel="stylesheet" href="<?= asset('css/sidebar-styles.css') ?>">
    <!-- NUEVO: Tamaño grande del sidebar -->
    <link rel="stylesheet" href="<?= asset('css/sidebar-size-override.css') ?>">

    <link rel="stylesheet" href="css/performance-optimization.css"> <!-- NUEVO -->
    <!-- Chart.js para gráficas -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom Configuration -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'hotel-brown': '#5D3A1A',
                        'hotel-brown-light': '#7B4F2F',
                        'hotel-brown-dark': '#3E2612',
                        'hotel-gold': '#6B4423',
                        'hotel-cream': '#FFF8E7',
                        'hotel-beige': '#F5E6D3',
                        'hotel-olive': '#9CA777',
                        'hotel-olive-light': '#B8C49A',
                        'hotel-olive-dark': '#7A8B5C'
                    },
                    fontFamily: {
                        'playfair': ['Playfair Display', 'serif'],
                        'inter': ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <?php if ($layoutBranding && function_exists('hotel_branding_css_vars')): ?>
        <?= hotel_branding_css_vars($layoutBranding) ?>
    <?php endif; ?>
    
    <?php
        if (!function_exists('obtenerHotelIdActualCompat') && defined('APP_PATH')) {
            require_once APP_PATH . '/helpers/hotel_config.php';
        }

        $medisoftHotelId = obtenerHotelIdActualCompat();
        $medisoftUsuarioId = user_id();
        $medisoftContext = [
            'hotel_id' => (int) $medisoftHotelId,
            'usuario_id' => $medisoftUsuarioId ? (int) $medisoftUsuarioId : null,
            'hotel_scope' => 'hotel-' . (int) $medisoftHotelId,
            'storage_scope' => 'hotel-' . (int) $medisoftHotelId . '-user-' . ($medisoftUsuarioId ? (int) $medisoftUsuarioId : 'anon'),
            'storage_version' => 'v1',
            'generated_at' => date('c'),
        ];
    ?>
    <script>
        window.BASE_URL = '<?= rtrim(url(''), '/') ?>';
        window.API_URL = '<?= url('api') ?>';
        window.MEDISOFT_CONTEXT = <?= json_encode($medisoftContext, JSON_UNESCAPED_SLASHES) ?>;
        window.USUARIO_ID = window.MEDISOFT_CONTEXT.usuario_id;
    </script>

    <!-- O usando un meta tag -->
    <meta name="base-url" content="<?= rtrim(url(''), '/') ?>">
    <meta name="api-url" content="<?= url('api') ?>">
    
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">

    <!-- CSS de la pantalla de carga -->
    <link rel="stylesheet" href="<?= asset('css/loading-screen.css') ?>">

    <!-- CSS PWA (offline banner, toasts, install btn) -->
    <link rel="stylesheet" href="<?= asset('css/pwa.css') ?>">
    
    <!-- Solo en el dashboard -->
    <?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
    <link rel="stylesheet" href="<?= asset('css/dashboard.css') ?>">
    <?php endif; ?>
    
    <!-- Script de pantalla de carga -->
    <script src="<?= asset('js/loading-screen.js') ?>"></script>

    <!-- JavaScript Global -->
    <script src="<?= asset('js/app.js') ?>" defer></script>

    <!-- PWA: registro de SW + lógica offline (reemplaza el script inline de SW) -->
    <script src="<?= asset('js/pwa.js') ?>" defer></script>

    <!-- Offline Data: snapshots de habitaciones/reservaciones + cola tipada + sync -->
    <script src="<?= asset('js/offline-data.js') ?>" defer></script>

    <style>
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
                background: linear-gradient(135deg, var(--brand-primary, #9CA777) 0%, var(--brand-secondary, #7A8B5C) 100%);
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
                height: 40px;
                /* IMPORTANTE: Ancho fijo para evitar que se redimensione */
                width: auto;
                min-width: 120px; /* Ancho mínimo */
                display: flex;
                align-items: center;
                justify-content: center;
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
            
            /* Barra de progreso */
            .scroll-progress {
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                height: 2px;
            background: var(--brand-accent, #D4AF37);
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
        }
    </style>
</head>
<body class="bg-gray-100 font-inter loading">

  <!-- ── Banner Offline ─────────────────────────────────────────────────── -->
  <div id="pwa-offline-banner" role="alert" aria-live="assertive" hidden>
    <span class="pwa-banner-icon"><i class="fas fa-wifi"></i></span>
    <div class="pwa-banner-text">
      <strong>Sin conexión a internet</strong>
      <span>Modo lectura activo. Los cambios se sincronizarán cuando vuelva internet.</span>
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
    <!-- Incluir pantalla de carga -->
    <?php include APP_PATH . '/views/components/loading-screen.php'; ?>
    
    <!-- HEADER MÓVIL MODERNO CON VERDE OLIVO (Solo aparece en móvil) -->
    <div class="mobile-header-modern" id="mobileHeaderModern">
        <!-- Botón de menú -->
        <button id="mobile-menu-toggle" class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Logo centrado con efecto verde olivo -->
        <div class="mobile-header-logo">
            <img src="<?= htmlspecialchars($layoutLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($layoutNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        
        <!-- Acciones: sync + install -->
        <div class="mobile-header-actions" style="display:flex;align-items:center;gap:6px;">
            <!-- Indicador de sincronización -->
            <div id="pwa-sync-indicator" title="Estado de conexión">
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
    
    <!-- Barra de progreso de scroll con verde olivo -->
    <div class="scroll-progress" id="scrollProgress"></div>
    
    <!-- Contenedor Principal -->
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <?php require_once APP_PATH . '/views/layout/sidebar.php'; ?>
        
        <!-- Contenido Principal -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header Desktop (solo visible en desktop) -->
            <header class="bg-white shadow-sm border-b border-gray-200 relative">
                <div class="px-4 sm:px-6 lg:px-8 py-4">
                    <!-- Tu contenido del header desktop aquí -->
                </div>
            </header>
            
            <!-- Contenido de la página -->
            <main class="main-content">
                <!-- Mensajes Flash -->
                <?php if ($mensaje = get_mensaje()): ?>
                    <?php 
                    // Asegurar que el mensaje tenga la estructura correcta
                    if (is_string($mensaje)) {
                        $mensaje = ['texto' => $mensaje, 'tipo' => 'info'];
                    }
                    
                    // Obtener valores con defaults seguros
                    $texto = $mensaje['texto'] ?? 'Operación realizada';
                    $tipo = $mensaje['tipo'] ?? 'info';
                    ?>
                    <div class="mx-6 mt-4 fade-in">
                        <div class="p-4 rounded-lg flex items-center justify-between <?php 
                            echo $tipo === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 
                                ($tipo === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 
                                'bg-blue-50 text-blue-700 border border-blue-200'); 
                        ?>">
                            <div class="flex items-center">
                                <i class="fas <?php 
                                    echo $tipo === 'error' ? 'fa-exclamation-circle' : 
                                        ($tipo === 'success' ? 'fa-check-circle' : 'fa-info-circle'); 
                                ?> mr-3"></i>
                                <span><?= htmlspecialchars($texto) ?></span>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

    <!-- SCRIPT PARA AUTO-HIDE -->
    <script>
        // Ejecutar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Iniciando script de auto-hide - Los Cedros');
            
            // Variables para auto-hide
            let lastScrollTop = 0;
            let ticking = false;
            const mobileHeader = document.getElementById('mobileHeaderModern');
            const scrollProgress = document.getElementById('scrollProgress');
            
            // Verificar que el header existe
            if (mobileHeader) {
                console.log('Header móvil Los Cedros encontrado');
                
                // Función para manejar el scroll
                function handleScroll() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                    
                    // Solo en móvil
                    if (window.innerWidth <= 1024) {
                        // Actualizar barra de progreso
                        if (scrollProgress && scrollHeight > 0) {
                            const percentage = scrollTop / scrollHeight;
                            scrollProgress.style.transform = `scaleX(${percentage})`;
                        }
                        
                        // Auto-hide del header con umbral mínimo
                        if (scrollTop > lastScrollTop && scrollTop > 10) {
                            // Scrolling hacia abajo - ocultar y pantalla completa
                            mobileHeader.classList.add('hidden');
                            document.body.classList.add('header-hidden');
                            console.log('Ocultando header - Pantalla completa activada');
                        } else if (scrollTop < lastScrollTop) {
                            // Scrolling hacia arriba - mostrar
                            mobileHeader.classList.remove('hidden');
                            document.body.classList.remove('header-hidden');
                            console.log('Mostrando header - Pantalla normal');
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
                
                // Escuchar evento de scroll
                window.addEventListener('scroll', requestTick, { passive: true });
                
                // PRUEBA MANUAL mejorada
                window.toggleHeader = function() {
                    if (mobileHeader.classList.contains('hidden')) {
                        mobileHeader.classList.remove('hidden');
                        document.body.classList.remove('header-hidden');
                        console.log('Header Los Cedros mostrado - Pantalla normal');
                    } else {
                        mobileHeader.classList.add('hidden');
                        document.body.classList.add('header-hidden');
                        console.log('Header Los Cedros oculto - Pantalla completa');
                    }
                };
                
                console.log('TIP: Escribe toggleHeader() en la consola para probar el auto-hide con pantalla completa');
                
            } else {
                console.error('Header móvil no encontrado');
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
                console.log('Ocultando pantalla de carga por timeout');
                loadingScreen.style.display = 'none';
                document.body.classList.remove('loading');
            }
        }, 2000);
    </script>
</body>
</html>
