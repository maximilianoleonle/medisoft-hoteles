// app.js - Inicialización de PWA
(function() {
    'use strict';

    const medisoftOfflineEnabled = window.MEDISOFT_OFFLINE_ENABLED === true;

    // Verificar soporte de Service Worker
    if (medisoftOfflineEnabled && 'serviceWorker' in navigator && !window.BASE_URL) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register((window.BASE_URL || '') + '/service-worker.js', {
                scope: (window.BASE_URL || '') + '/'
            })
                .then(registration => {
                    console.log('Service Worker registrado:', registration);
                    
                    // Verificar actualizaciones
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'activated') {
                                // Mostrar notificación de actualización
                                if (window.confirm('Nueva versión disponible. ¿Desea actualizar?')) {
                                    window.location.reload();
                                }
                            }
                        });
                    });
                })
                .catch(error => {
                    console.error('Error al registrar Service Worker:', error);
                });
        });
    }

    // Detectar si la app está instalada
    let deferredPrompt;
    const installButton = document.getElementById('install-button');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Mostrar botón de instalación
        if (installButton) {
            installButton.style.display = 'block';
            
            installButton.addEventListener('click', () => {
                deferredPrompt.prompt();
                
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuario aceptó instalar la PWA');
                    }
                    deferredPrompt = null;
                });
            });
        }
    });

    // Detectar modo standalone
    if (window.matchMedia('(display-mode: standalone)').matches) {
        console.log('App ejecutándose en modo standalone');
        document.body.classList.add('pwa-standalone');
    }

    // Manejo offline/online
    function updateOnlineStatus() {
        const statusElement = document.getElementById('connection-status');
        if (statusElement) {
            if (navigator.onLine) {
                statusElement.textContent = 'En línea';
                statusElement.className = 'text-green-600';
            } else {
                statusElement.textContent = 'Sin conexión';
                statusElement.className = 'text-red-600';
            }
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // API de Vibración (para notificaciones táctiles)
    window.vibrar = function(duration = 200) {
        if ('vibrate' in navigator) {
            navigator.vibrate(duration);
        }
    };

    // Compartir nativo
    window.compartir = function(titulo, texto, url) {
        if (navigator.share) {
            navigator.share({
                title: titulo,
                text: texto,
                url: url || window.location.href
            }).catch(console.error);
        } else {
            // Fallback: copiar al portapapeles
            const shareText = `${titulo}\n${texto}\n${url || window.location.href}`;
            navigator.clipboard.writeText(shareText).then(() => {
                alert('Enlace copiado al portapapeles');
            });
        }
    };

})();
