<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>404 - Página No Encontrada | Medisoft Hoteles</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'hotel-brown': '#5D3A1A',
                        'hotel-brown-light': '#7B4F2F',
                        'hotel-brown-dark': '#3E2612',
                        'hotel-gold': '#D4AF37',
                        'hotel-cream': '#FFF8E7',
                        'hotel-beige': '#F5E6D3'
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
            <i class="fas fa-exclamation-triangle text-hotel-gold text-8xl mb-4"></i>
            <h1 class="text-6xl font-bold text-hotel-brown font-playfair mb-2">404</h1>
            <p class="text-2xl text-hotel-brown-light mb-4">Página No Encontrada</p>
            <p class="text-gray-600 mb-8">Lo sentimos, la página que busca no existe o ha sido movida.</p>
        </div>
        
        <div class="space-x-4">
            <a href="<?= url('/') ?>" class="inline-block bg-hotel-brown text-white px-6 py-3 rounded-lg hover:bg-hotel-brown-dark transition duration-300">
                <i class="fas fa-home mr-2"></i>Ir al Inicio
            </a>
            <a href="<?= back_url('/') ?>" class="inline-block bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i>Regresar
            </a>
        </div>
    </div>
</body>
</html>
