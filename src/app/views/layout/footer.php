<body><?php if (isset($_SESSION['user_id']) && user_role() == 'gerente'): ?>
    <div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999;">
       
    </div>
<?php endif; ?></body>

<!-- Solo en el dashboard -->
<!-- Solo cargar en el dashboard -->
<?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= function_exists('asset_version') ? asset_version('js/dashboard.js') : asset('js/dashboard.js') ?>"></script>
<?php endif; ?>

</main>
        </div>
    </div>

    <!-- La barra inferior de accesos rápidos ahora se incluye desde el header
         (visible desde el primer paint, como el header móvil) -->

    <!-- Scripts adicionales para vistas específicas -->
    <?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/dashboard.js') : asset('js/dashboard.js') ?>"></script>
    <?php endif; ?>

    <!-- Buscador global (todas las páginas autenticadas) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/buscador-global.js') : asset('js/buscador-global.js') ?>" defer></script>
    <script src="<?= function_exists('asset_version') ? asset_version('js/mobile-file-return.js') : asset('js/mobile-file-return.js') ?>" defer></script>

    <script>
        document.addEventListener('change', function(event) {
            if (!(event.target instanceof Element)) {
                return;
            }

            const control = event.target.closest('select, input[type="date"], input[type="month"], input[type="checkbox"], input[type="radio"]');
            if (!control || control.matches('[data-auto-filter-ignore]')) {
                return;
            }

            const form = control.closest('form[data-auto-filter-form]');
            if (!form || form.dataset.autoFilterSubmitting === '1') {
                return;
            }

            form.dataset.autoFilterSubmitting = '1';

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        });
    </script>

    <style>
        [data-easy-href] {
            cursor: pointer;
        }

        [data-easy-href]:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--brand-accent, #BD9441) 52%, transparent);
            outline-offset: -2px;
        }

        a[data-self-nav-current="true"] {
            cursor: default;
        }

        .ms-self-nav-live {
            position: fixed;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        a.ms-self-nav-pulse {
            animation: msSelfNavPulse .42s ease;
        }

        #sidebar .nav-item.ms-self-nav-pulse .nav-icon,
        .hotel-bottom-nav .hbn-item.ms-self-nav-pulse .hbn-pill {
            animation: msSelfNavIconPulse .42s ease;
        }

        @keyframes msSelfNavPulse {
            0% {
                outline: 0 solid color-mix(in srgb, var(--brand-accent, var(--hotel-accent, #BD9441)) 38%, transparent);
                outline-offset: 0;
            }
            45% {
                outline: 2px solid color-mix(in srgb, var(--brand-accent, var(--hotel-accent, #BD9441)) 42%, transparent);
                outline-offset: 3px;
            }
            100% {
                outline: 0 solid transparent;
                outline-offset: 7px;
            }
        }

        @keyframes msSelfNavIconPulse {
            0%, 100% { filter: brightness(1); }
            45% { filter: brightness(.96) saturate(1.15); }
        }

        @media (prefers-reduced-motion: reduce) {
            a.ms-self-nav-pulse,
            #sidebar .nav-item.ms-self-nav-pulse .nav-icon,
            .hotel-bottom-nav .hbn-item.ms-self-nav-pulse .hbn-pill {
                animation: none;
            }
        }
    </style>

    <script>
        (function() {
            if (window.__hotelSelfNavigationGuardReady) {
                return;
            }

            window.__hotelSelfNavigationGuardReady = true;

            function canonicalPath(pathname) {
                var clean = (pathname || '/').replace(/\/+$/, '');
                if (clean === '' || clean === '/') {
                    return '/dashboard';
                }
                return clean;
            }

            function isShellNavigationLink(link) {
                return !!(link && link.closest('#sidebar, #hotel-bottom-nav'));
            }

            function linkTargetUrl(link, event) {
                if (!isShellNavigationLink(link) || link.hasAttribute('download') || link.hasAttribute('onclick')) {
                    return null;
                }

                if (link.matches('[data-self-nav-ignore], [data-no-self-nav-guard], [role="button"]')) {
                    return null;
                }

                if (event && (
                    event.defaultPrevented ||
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey
                )) {
                    return null;
                }

                var target = (link.getAttribute('target') || '').toLowerCase();
                if (target && target !== '_self') {
                    return null;
                }

                var rawHref = (link.getAttribute('href') || '').trim();
                if (!rawHref || rawHref === '#' || rawHref.charAt(0) === '#') {
                    return null;
                }

                if (/^(javascript:|mailto:|tel:|sms:|blob:|data:)/i.test(rawHref)) {
                    return null;
                }

                try {
                    var targetUrl = new URL(rawHref, window.location.href);
                    if (targetUrl.origin !== window.location.origin) {
                        return null;
                    }
                    if (targetUrl.protocol !== 'http:' && targetUrl.protocol !== 'https:') {
                        return null;
                    }
                    return targetUrl;
                } catch (error) {
                    return null;
                }
            }

            function isSameCurrentUrl(targetUrl) {
                var currentUrl = new URL(window.location.href);
                var samePath = canonicalPath(targetUrl.pathname) === canonicalPath(currentUrl.pathname);
                var sameQuery = targetUrl.search === currentUrl.search;
                var sameHashIntent = targetUrl.hash === '' || targetUrl.hash === currentUrl.hash;

                return samePath && sameQuery && sameHashIntent;
            }

            function navLabel(link) {
                var labelNode = link.querySelector('.nav-text, .hbn-label');
                var text = (labelNode ? labelNode.textContent : link.textContent || '').replace(/\s+/g, ' ').trim();
                return text || 'esta vista';
            }

            function announce(link) {
                var live = document.getElementById('ms-self-nav-live');
                if (!live) {
                    live = document.createElement('div');
                    live.id = 'ms-self-nav-live';
                    live.className = 'ms-self-nav-live';
                    live.setAttribute('aria-live', 'polite');
                    live.setAttribute('aria-atomic', 'true');
                    (document.body || document.documentElement).appendChild(live);
                }

                var label = navLabel(link);
                live.textContent = 'Ya estas en ' + label + '. No se recargo la pagina.';
            }

            function pulse(link) {
                link.classList.remove('ms-self-nav-pulse');
                void link.offsetWidth;
                link.classList.add('ms-self-nav-pulse');

                window.setTimeout(function() {
                    link.classList.remove('ms-self-nav-pulse');
                }, 520);
            }

            function closeMobileSidebarIfNeeded(link) {
                if (!link.closest('#sidebar')) {
                    return;
                }

                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('sidebar-overlay');
                var footerMenu = document.getElementById('hotel-bottom-nav-menu');
                var legacyToggle = document.getElementById('mobile-menu-toggle');

                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (overlay) {
                        overlay.classList.remove('active');
                    }
                    document.body.style.overflow = '';
                    if (footerMenu) {
                        footerMenu.setAttribute('aria-expanded', 'false');
                    }
                    if (legacyToggle) {
                        legacyToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            }

            function markCurrentLinks() {
                document.querySelectorAll('#sidebar a[href], #hotel-bottom-nav a[href]').forEach(function(link) {
                    var targetUrl = linkTargetUrl(link, null);
                    if (targetUrl && isSameCurrentUrl(targetUrl)) {
                        link.setAttribute('data-self-nav-current', 'true');
                        if (!link.hasAttribute('aria-current')) {
                            link.setAttribute('aria-current', 'page');
                        }
                    } else {
                        link.removeAttribute('data-self-nav-current');
                    }
                });
            }

            document.addEventListener('click', function(event) {
                var link = event.target instanceof Element ? event.target.closest('a[href]') : null;
                var targetUrl = linkTargetUrl(link, event);

                if (!targetUrl || !isSameCurrentUrl(targetUrl)) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                pulse(link);
                closeMobileSidebarIfNeeded(link);
                announce(link);
            }, true);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', markCurrentLinks);
            } else {
                markCurrentLinks();
            }
        })();
    </script>

    <script>
        (function() {
            if (window.__hotelEasyHrefReady) {
                return;
            }

            window.__hotelEasyHrefReady = true;

            var interactiveSelector = [
                'a',
                'button',
                'input',
                'select',
                'textarea',
                'form',
                'label',
                'summary',
                'iframe',
                'audio',
                'video',
                '[role="button"]',
                '[role="menuitem"]',
                '[role="link"]:not([data-easy-href])',
                '[contenteditable="true"]',
                '[data-easy-href-ignore]',
                '[data-no-row-click]'
            ].join(',');

            function closestEasyHref(target) {
                return target instanceof Element ? target.closest('[data-easy-href]') : null;
            }

            function isInsideInteractive(target, row) {
                if (!(target instanceof Element)) {
                    return false;
                }

                var interactive = target.closest(interactiveSelector);
                return !!interactive && row.contains(interactive);
            }

            function openEasyHref(row, event) {
                var href = row.getAttribute('data-easy-href');
                if (!href) {
                    return;
                }

                if (event && (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1)) {
                    window.open(href, '_blank', 'noopener');
                    return;
                }

                window.location.href = href;
            }

            document.addEventListener('click', function(event) {
                if (event.defaultPrevented || event.button !== 0) {
                    return;
                }

                var row = closestEasyHref(event.target);
                if (!row || isInsideInteractive(event.target, row)) {
                    return;
                }

                openEasyHref(row, event);
            });

            document.addEventListener('auxclick', function(event) {
                if (event.defaultPrevented || event.button !== 1) {
                    return;
                }

                var row = closestEasyHref(event.target);
                if (!row || isInsideInteractive(event.target, row)) {
                    return;
                }

                event.preventDefault();
                openEasyHref(row, event);
            });

            document.addEventListener('keydown', function(event) {
                if (event.defaultPrevented || (event.key !== 'Enter' && event.key !== ' ')) {
                    return;
                }

                var row = closestEasyHref(event.target);
                if (!row || isInsideInteractive(event.target, row)) {
                    return;
                }

                event.preventDefault();
                openEasyHref(row, event);
            });
        })();
    </script>

    <!-- Offline: caché de lectura para reservaciones del día -->
    <?php if (isset($title) && stripos($title, 'Reservaciones') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/reservaciones-offline.js') : asset('js/reservaciones-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: interceptores para habitaciones (check-in, check-out, estados) -->
    <?php if (isset($title) && stripos($title, 'Habitaciones') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/habitaciones-offline.js') : asset('js/habitaciones-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: interceptores para caja (ingresos y gastos) -->
    <?php if (isset($title) && stripos($title, 'Caja') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/caja-offline.js') : asset('js/caja-offline.js') ?>" defer></script>
    <?php endif; ?>

</body>
</html>
