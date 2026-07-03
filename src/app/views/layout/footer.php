<body><?php if (isset($_SESSION['user_id']) && user_role() == 'gerente'): ?>
    <div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999;">
       
    </div>
<?php endif; ?></body>

<!-- Solo en el dashboard -->
<!-- Solo cargar en el dashboard -->
<?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<?php endif; ?>

</main>
        </div>
    </div>

    <!-- Barra inferior de accesos rápidos (solo móvil, configurable por hotel) -->
    <?php include APP_PATH . '/views/layout/footer-nav.php'; ?>

    <!-- Scripts adicionales para vistas específicas -->
    <?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
    <script src="<?= asset('js/dashboard.js') ?>"></script>
    <?php endif; ?>

    <!-- Buscador global (todas las páginas autenticadas) -->
    <script src="<?= asset('js/buscador-global.js') ?>" defer></script>
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
    </style>

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
    <script src="<?= asset('js/reservaciones-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: interceptores para habitaciones (check-in, check-out, estados) -->
    <?php if (isset($title) && stripos($title, 'Habitaciones') !== false): ?>
    <script src="<?= asset('js/habitaciones-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: interceptores para caja (ingresos y gastos) -->
    <?php if (isset($title) && stripos($title, 'Caja') !== false): ?>
    <script src="<?= asset('js/caja-offline.js') ?>" defer></script>
    <?php endif; ?>

</body>
</html>
