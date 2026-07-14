<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>500 - Error del Servidor | Medisoft Hoteles</title>
    
    <!-- Tailwind CSS precompilado (config en tailwind.config.js de la raíz).
         Nota: los alias hotel-* ahora se resuelven contra las variables de
         marca --brand-* con sus fallbacks; esta página pierde sus hex legacy
         y adopta la paleta de marca (deseable en white-label). -->
    <link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/tailwind.css') : asset('css/tailwind.css') ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        html {
            touch-action: pan-x pan-y;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
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
<body class="min-h-screen bg-hotel-cream flex items-center justify-center p-4 font-inter">
    <div class="text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-circle text-red-500 text-8xl mb-4"></i>
            <h1 class="text-6xl font-bold text-hotel-brown font-playfair mb-2">500</h1>
            <p class="text-2xl text-hotel-brown-light mb-4">Error del Servidor</p>
            <p class="text-gray-600 mb-8">Lo sentimos, algo salió mal en nuestro servidor.<br>Por favor, intente nuevamente más tarde.</p>
        </div>
        
        <div class="space-x-4">
            <a href="<?= url('/') ?>" class="inline-block bg-hotel-brown text-white px-6 py-3 rounded-lg hover:bg-hotel-brown-dark transition duration-300">
                <i class="fas fa-home mr-2"></i>Ir al Inicio
            </a>
            <button onclick="location.reload()" class="inline-block bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition duration-300">
                <i class="fas fa-sync-alt mr-2"></i>Reintentar
            </button>
        </div>
        
        <div class="mt-8 text-sm text-gray-500">
            <p>Si el problema persiste, contacte al administrador del sistema.</p>
            <?php if (!empty($request_id)): ?>
                <p class="mt-2 font-mono text-gray-400">Código de referencia: #<?= htmlspecialchars($request_id, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
