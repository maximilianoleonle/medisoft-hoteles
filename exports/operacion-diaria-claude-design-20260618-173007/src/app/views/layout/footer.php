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

    <!-- Scripts adicionales para vistas específicas -->
    <?php if (isset($title) && strpos($title, 'Dashboard') !== false): ?>
    <script src="<?= asset('js/dashboard.js') ?>"></script>
    <?php endif; ?>

    <!-- Buscador global (todas las páginas autenticadas) -->
    <script src="<?= asset('js/buscador-global.js') ?>" defer></script>

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
