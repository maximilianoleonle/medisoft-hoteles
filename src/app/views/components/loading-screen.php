<!-- 
===================================
COMPONENTE DE PANTALLA DE CARGA
Archivo: app/views/components/loading-screen.php
===================================
-->

<?php
$loadingNombreVisual = $layoutNombreVisual ?? 'Medisoft';
$loadingLogoUrl = $layoutLogoUrl ?? (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : (function_exists('asset') ? asset('img/logo.png') : '/img/logo.png'));
?>

<!-- Pantalla de Carga Principal -->
<div id="loadingScreen" class="loading-screen" style="display: none;">
    <div class="loading-content">
        <div class="logo-container">
            <div class="logo-glow"></div>
            <img src="<?= htmlspecialchars($loadingLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($loadingNombreVisual, ENT_QUOTES, 'UTF-8') ?>"
                 class="logo-image">
        </div>
        
        
        <div class="loading-text"></div>
    </div>
</div>

<!-- Contenedor para transiciones entre páginas -->
<div class="page-transition" style="display: none;">
    <img src="<?= htmlspecialchars($loadingLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
         alt="<?= htmlspecialchars($loadingNombreVisual, ENT_QUOTES, 'UTF-8') ?>"
         class="logo-mini">
</div>

<script>
    // Configuración específica para la pantalla de carga
    window.LoadingConfig = {
        minLoadingTime: 1000,
        showOnNavigation: false, // Desactivar por ahora
        debugMode: false
    };
    
    // Registrar tiempo de inicio
    window.loadStartTime = Date.now();
    
    // Forzar ocultación si algo falla
    window.addEventListener('error', function() {
        setTimeout(function() {
            var loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.display = 'none';
                document.body.classList.remove('loading');
            }
        }, 100);
    });
</script>
