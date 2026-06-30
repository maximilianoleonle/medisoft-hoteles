(function() {
    'use strict';

    var KEY = 'medisoft_pwa_launch_seen';
    var MIN_VISIBLE_MS = 650;
    var MAX_VISIBLE_MS = 2600;
    var FADE_MS = 240;

    function isStandalone() {
        try {
            return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;
        } catch (error) {
            return false;
        }
    }

    function hasSeenSplash() {
        try {
            return window.sessionStorage.getItem(KEY) === '1';
        } catch (error) {
            return false;
        }
    }

    function markSeenSplash() {
        try {
            window.sessionStorage.setItem(KEY, '1');
        } catch (error) {}
    }

    function forceHide(splash) {
        if (!splash) {
            return;
        }

        splash.style.display = 'none';
        splash.classList.remove('is-visible', 'is-hiding');
        splash.setAttribute('aria-busy', 'false');
        document.documentElement.classList.remove('pwa-launch-pending');
        document.body.classList.remove('pwa-launch-lock');
    }

    function hideSplash(splash) {
        if (!splash) {
            return;
        }

        var startedAt = window.PwaLaunchSplashStartedAt || Date.now();
        var elapsed = Date.now() - startedAt;
        var remaining = Math.max(0, MIN_VISIBLE_MS - elapsed);

        setTimeout(function() {
            splash.classList.add('is-hiding');
            document.documentElement.classList.remove('pwa-launch-pending');
            document.body.classList.remove('pwa-launch-lock');

            setTimeout(function() {
                forceHide(splash);
            }, FADE_MS);
        }, remaining);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var splash = document.getElementById('pwaLaunchSplash');
        if (!splash) {
            return;
        }

        if (!isStandalone() || hasSeenSplash()) {
            forceHide(splash);
            return;
        }

        markSeenSplash();
        window.PwaLaunchSplashStartedAt = window.PwaLaunchSplashStartedAt || Date.now();
        splash.classList.add('is-visible');
        splash.setAttribute('aria-busy', 'true');
        document.body.classList.add('pwa-launch-lock');

        if (document.readyState === 'complete') {
            hideSplash(splash);
        } else {
            window.addEventListener('load', function() {
                hideSplash(splash);
            }, { once: true });
        }

        setTimeout(function() {
            forceHide(splash);
        }, MAX_VISIBLE_MS);
    });
})();
