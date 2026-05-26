// ===================================
// PANTALLA DE CARGA - Los Cedros
// Archivo: public_html/js/loading-screen.js
// ===================================

(function() {
    'use strict';

    // Configuración
    const config = {
        minLoadingTime: 1000, // Tiempo mínimo de carga en ms
        fadeOutDuration: 500, // Duración del fade out
        showOnNavigation: true, // Mostrar en navegación entre páginas
        debugMode: false // Modo debug
    };

    // Estado de la aplicación
    let isFirstLoad = true;
    let loadingPromises = [];

    // Función principal para mostrar la pantalla de carga
    function showLoadingScreen(state = 'initial') {
        const loadingScreen = document.getElementById('loadingScreen');
        if (!loadingScreen) {
            console.warn('Pantalla de carga no encontrada');
            return;
        }

        // Añadir clase al body para prevenir scroll
        document.body.classList.add('loading');
        
        // Establecer estado de carga
        loadingScreen.setAttribute('data-loading-state', state);
        
        // Mostrar pantalla
        loadingScreen.classList.remove('fade-out');
        loadingScreen.style.display = 'flex';

        if (config.debugMode) {
            console.log(`Pantalla de carga mostrada: ${state}`);
        }
    }

    // Función para ocultar la pantalla de carga
    // Función para ocultar la pantalla de carga
function hideLoadingScreen() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (!loadingScreen) return;

    // Forzar ocultación inmediata si hay problemas
    try {
        // Actualizar estado a 'ready'
        loadingScreen.setAttribute('data-loading-state', 'ready');

        // Tiempo mínimo de visualización
        const timeElapsed = Date.now() - (window.loadStartTime || 0);
        const remainingTime = Math.max(0, config.minLoadingTime - timeElapsed);

        setTimeout(() => {
            loadingScreen.classList.add('fade-out');
            document.body.classList.remove('loading');

            setTimeout(() => {
                loadingScreen.style.display = 'none';
                isFirstLoad = false;
                
                if (config.debugMode) {
                    console.log('Pantalla de carga oculta');
                }
            }, config.fadeOutDuration);
        }, remainingTime);
    } catch (error) {
        // Si hay cualquier error, ocultar inmediatamente
        console.error('Error en pantalla de carga:', error);
        loadingScreen.style.display = 'none';
        document.body.classList.remove('loading');
    }
}

    // Función para transiciones entre páginas
    function showPageTransition() {
        let transition = document.querySelector('.page-transition');
        
        if (!transition) {
            transition = document.createElement('div');
            transition.className = 'page-transition';
            transition.innerHTML = `
                <img src="/img/logo-hotel-san-nicolas.png" 
                     alt="Los Cedros" 
                     class="logo-mini">
            `;
            document.body.appendChild(transition);
        }

        requestAnimationFrame(() => {
            transition.classList.add('active');
        });

        return transition;
    }

    // Función para ocultar transición
    function hidePageTransition(transition) {
        if (transition) {
            transition.classList.remove('active');
            setTimeout(() => {
                if (transition.parentNode) {
                    transition.parentNode.removeChild(transition);
                }
            }, 300);
        }
    }

    // Interceptar navegación con AJAX
    function interceptNavigation() {
        if (!config.showOnNavigation) return;

        // Interceptar clicks en enlaces internos
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('mailto:')) {
                return;
            }

            // Prevenir navegación por defecto
            e.preventDefault();

            // Mostrar transición
            const transition = showPageTransition();

            // Simular navegación con pequeño retraso
            setTimeout(() => {
                window.location.href = href;
            }, 300);
        });

        // Interceptar envío de formularios
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.getAttribute('data-no-loading') === 'true') return;

            // Mostrar transición para formularios que no sean AJAX
            if (!form.classList.contains('ajax-form')) {
                showPageTransition();
            }
        });
    }

    // Función para registrar promesas de carga
    window.registerLoadingPromise = function(promise) {
        loadingPromises.push(promise);
        return promise;
    };

    // Funciones públicas
    window.LoadingScreen = {
        show: showLoadingScreen,
        hide: hideLoadingScreen,
        showTransition: showPageTransition,
        hideTransition: hidePageTransition
    };

    // Inicialización cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
    // Guardar tiempo de inicio
    window.loadStartTime = Date.now();

    // ✅ Mostrar siempre al abrir la app
    showLoadingScreen('initial');

    // Ocultar hasta que todo cargue + mínimo 2s
    if (document.readyState === 'complete') {
        hideLoadingScreen();
    } else {
        window.addEventListener('load', hideLoadingScreen);
    }
});


    // Manejar errores de carga
    window.addEventListener('error', function(e) {
        console.error('Error de carga:', e);
        // Ocultar pantalla de carga en caso de error
        setTimeout(hideLoadingScreen, 1000);
    });

    // Integración con SPA (Single Page Application) si usas AJAX
    if (window.jQuery || window.$) {
        $(document).ajaxStart(function() {
            if (config.showOnNavigation) {
                showPageTransition();
            }
        }).ajaxStop(function() {
            const transition = document.querySelector('.page-transition');
            hidePageTransition(transition);
        });
    }

    // Integración con Fetch API
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const promise = originalFetch.apply(this, args);
        
        if (config.showOnNavigation && args[0] && args[0].includes('api/')) {
            // Registrar promesa para tracking
            registerLoadingPromise(promise);
        }
        
        return promise;
    };
// Timeout de seguridad - ocultar después de 3 segundos máximo
setTimeout(function() {
    const loadingScreen = document.getElementById('loadingScreen');
    if (loadingScreen && loadingScreen.style.display !== 'none') {
        console.warn('Timeout de pantalla de carga - ocultando forzadamente');
        loadingScreen.style.display = 'none';
        document.body.classList.remove('loading');
    }
}, 3000);
})();