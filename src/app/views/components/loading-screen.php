<!-- 
===================================
COMPONENTE DE PANTALLA DE CARGA
Archivo: app/views/components/loading-screen.php
===================================
-->

<!-- Pantalla de Carga Principal -->
<div id="loadingScreen" class="loading-screen">
    <div class="loading-content">
        <div class="logo-container">
            <div class="logo-glow"></div>
            <img src="<?= asset('img/logo-hotel-san-nicolas2.png') ?>" 
                 alt="Los Cedros" 
                 class="logo-image">
        </div>
        
        
        <div class="loading-text"></div>
    </div>
</div>

<!-- Contenedor para transiciones entre páginas -->
<div class="page-transition" style="display: none;">
    <img src="<?= asset('img/logo-hotel-san-nicolas2.png') ?>" 
         alt="Los Cedros" 
         class="logo-mini">
</div>

<script>
    // Configuración específica para la pantalla de carga
    window.LoadingConfig = {
        minLoadingTime: 1000,
        showOnNavigation: false, // Desactivar por ahora
        debugMode: true // Activar para ver logs
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