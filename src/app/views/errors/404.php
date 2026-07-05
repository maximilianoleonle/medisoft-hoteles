<?php
// Branding del hotel (paleta white-label). Sin contexto, hotel_branding_css_vars()
// cae a la paleta por defecto de Medisoft (navy/oro).
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>404 - Página no encontrada | <?= htmlspecialchars($errorHotelNombre, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="theme-color" content="<?= htmlspecialchars($errorThemeColor, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Paleta del hotel (white-label) -->
    <?= function_exists('hotel_branding_css_vars') ? hotel_branding_css_vars($errorBranding) : '' ?>

    <script>
        // Mismos alias de color que el layout del app: se resuelven contra las
        // variables de marca inyectadas arriba (o sus fallbacks por defecto).
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'hotel-brown': 'var(--brand-secondary, #0F172A)',
                        'hotel-brown-light': 'color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #FFFFFF)',
                        'hotel-brown-dark': 'color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #000000)',
                        'hotel-gold': 'var(--brand-accent, #BD9441)',
                        'hotel-cream': 'color-mix(in srgb, var(--brand-accent, #BD9441) 9%, #F8F5ED)',
                        'hotel-beige': 'color-mix(in srgb, var(--brand-accent, #BD9441) 18%, #F8F5ED)',
                        'hotel-ink': 'var(--brand-text, #172033)',
                        'hotel-muted': 'var(--brand-muted, #6B7280)'
                    },
                    fontFamily: {
                        'playfair': ['Playfair Display', 'serif'],
                        'inter': ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
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
        .e404-code {
            background: linear-gradient(135deg, var(--brand-secondary, #0F172A), var(--brand-accent, #BD9441));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .e404-mark {
            background: color-mix(in srgb, var(--brand-accent, #BD9441) 14%, #fff);
            border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 30%, #fff);
        }
    </style>
    <script>
        (function() {
            var lastTouchEnd = 0;
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
            document.addEventListener('touchend', function(event) {
                var now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    blockZoom(event);
                }
                lastTouchEnd = now;
            }, { passive: false });
        })();
    </script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 font-inter">
    <div class="w-full max-w-md text-center">
        <?php if ($errorLogoUrl): ?>
        <img src="<?= htmlspecialchars($errorLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
             alt="<?= htmlspecialchars($errorHotelNombre, ENT_QUOTES, 'UTF-8') ?>"
             class="mx-auto mb-6 h-14 w-14 rounded-2xl object-contain shadow-sm">
        <?php endif; ?>

        <div class="e404-mark mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-2xl">
            <i class="fas fa-compass text-hotel-gold text-3xl"></i>
        </div>

        <h1 class="e404-code font-playfair text-7xl font-bold leading-none mb-3">404</h1>
        <p class="text-xl font-semibold text-hotel-brown mb-2">Página no encontrada</p>
        <p class="text-hotel-muted mb-8 leading-relaxed">
            Lo sentimos, la página que buscas no existe o fue movida.
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
