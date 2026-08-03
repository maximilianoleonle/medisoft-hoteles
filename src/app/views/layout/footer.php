<?php
// Candado anti doble render: View::renderTemplate() ya incluye este footer.
// Si una vista ademas lo incluye a mano (paso en reportes/index, procedencia
// y habitaciones-rentables, 2026-07-22), todo el footer salia DOS veces y el
// copiloto quedaba duplicado: el FAB de arriba sin listeners y el de abajo
// con toggle doble — el panel no abria. La segunda inclusion no pinta nada.
if (!empty($GLOBALS['msFooterRenderizado'])) { return; }
$GLOBALS['msFooterRenderizado'] = true;
?>
<body><?php if (isset($_SESSION['user_id']) && user_role() == 'gerente'): ?>
    <div style="position: fixed; bottom: 10px; right: 10px; z-index: 9999;">
       
    </div>
<?php endif; ?></body>

</main>
        </div>
    </div>

    <!-- La barra inferior de accesos rápidos ahora se incluye desde el header
         (visible desde el primer paint, como el header móvil) -->

    <!-- Scripts adicionales para vistas específicas (titulo "Inicio - ..."; se conserva "Dashboard" por compatibilidad) -->
    <?php if (isset($title) && (strpos($title, 'Dashboard') !== false || strpos($title, 'Inicio - ') === 0)): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/dashboard.js') : asset('js/dashboard.js') ?>"></script>
    <?php endif; ?>

    <!-- Oculta la sidebar mientras haya cualquier modal abierto (todas las páginas) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/modal-sidebar-fix.js') : asset('js/modal-sidebar-fix.js') ?>" defer></script>

    <!-- Selector de responsable de limpieza al hacer check-out (todas las páginas) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/checkout-limpieza.js') : asset('js/checkout-limpieza.js') ?>" defer></script>

    <!-- Buscador global (todas las páginas autenticadas) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/buscador-global.js') : asset('js/buscador-global.js') ?>" defer></script>
    <script src="<?= function_exists('asset_version') ? asset_version('js/mobile-file-return.js') : asset('js/mobile-file-return.js') ?>" defer></script>

    <!-- Selects con buscador: cualquier <select data-ms-combo> se vuelve filtrable -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/ms-combo.js') : asset('js/ms-combo.js') ?>" defer></script>

    <!-- Navegación fluida: regresar instantáneo (bfcache) + precarga en intención (todas las páginas) -->
    <script src="<?= function_exists('asset_version') ? asset_version('js/instant-nav.js') : asset('js/instant-nav.js') ?>" defer></script>

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
    <?php // Arma el reporte del día en el navegador cuando no hay red. Va con el
          // mismo gate del módulo que los botones de exportar: sin 'exportaciones'
          // no hay nada que generar y el archivo no tiene por qué viajar. ?>
    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/reservaciones-reporte-offline.js') : asset('js/reservaciones-reporte-offline.js') ?>" defer></script>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Offline: interceptores para habitaciones (check-in, check-out, estados) -->
    <?php if (isset($title) && stripos($title, 'Habitaciones') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/habitaciones-offline.js') : asset('js/habitaciones-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: interceptores para caja (ingresos y gastos) -->
    <?php if (isset($title) && stripos($title, 'Caja') !== false): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/caja-offline.js') : asset('js/caja-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Offline: registro de huéspedes sin conexión (id temporal + cola de sync) -->
    <?php if (isset($title) && (stripos($title, 'Huésped') !== false || stripos($title, 'Huesped') !== false)): ?>
    <script src="<?= function_exists('asset_version') ? asset_version('js/huespedes-offline.js') : asset('js/huespedes-offline.js') ?>" defer></script>
    <?php endif; ?>

    <!-- Copiloto Medisoft (bloque copiloto): widget flotante, solo si esta activo -->
    <?php include __DIR__ . '/copiloto_widget.php'; ?>

    <!-- Scroll a la seccion indicada por #ancla (usado por los enlaces del Copiloto).
         Generico y seguro: si el elemento no existe, no hace nada. -->
    <style>
        @keyframes cop-flash-kf { 0% { box-shadow: 0 0 0 0 rgba(189,148,65,.55); } 100% { box-shadow: 0 0 0 12px rgba(189,148,65,0); } }
        .cop-flash { animation: cop-flash-kf 1.1s ease-out 2; border-radius: 10px; }
    </style>
    <script>
    (function () {
        function irAAncla() {
            var id = (location.hash || '').replace('#', '');
            if (!id) { return; }
            var el = document.getElementById(id);
            if (!el) { return; }
            try { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) { el.scrollIntoView(); }
            el.classList.remove('cop-flash');
            void el.offsetWidth; // reinicia la animacion si se repite
            el.classList.add('cop-flash');
            setTimeout(function () { el.classList.remove('cop-flash'); }, 2500);
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { setTimeout(irAAncla, 120); });
        } else {
            setTimeout(irAAncla, 120);
        }
        window.addEventListener('hashchange', irAAncla);
    })();
    </script>

    <!-- ═══════════════════════════════════════════════════════════════════
         ms-modal-motion · capa de movimiento para modales (global, opt-in)
         Deleite Sereno: entrada coreografiada + skeleton reutilizable.
         Uso: overlay con clase .ms-anim y diálogo con .ms-anim-panel
              (+ .ms-anim-sheet para hoja inferior en móvil).
              Abrir/cerrar con window.msModal.open(el, {skeleton}) / .close(el)
         ═══════════════════════════════════════════════════════════════════ -->
    <style>
        :root{
            --ms-ease:cubic-bezier(.22,1,.36,1);
            --ms-sk-bg:var(--brand-surface, #FFFFFF);
            --ms-sk-bar:color-mix(in srgb, var(--brand-muted, #6B7280) 18%, var(--brand-surface-soft, #F4F1EA));
        }
        /* Fondo oscuro: se desvanece al entrar/salir */
        .ms-anim{ opacity:0; transition:opacity .26s var(--ms-ease); }
        .ms-anim.ms-anim-in{ opacity:1; }
        /* Diálogo: "pop" (leve subida + escala) en escritorio */
        .ms-anim .ms-anim-panel{
            opacity:0; transform:translateY(18px) scale(.965); transform-origin:center bottom;
            will-change:transform, opacity;
            transition:opacity .3s ease, transform .42s var(--ms-ease);
        }
        .ms-anim.ms-anim-in .ms-anim-panel{ opacity:1; transform:none; }
        /* El panel es contexto de posicionamiento sólo si lleva skeleton */
        .ms-anim-panel:has(.ms-sk-layer){ position:relative; }

        /* Skeleton reutilizable (se inyecta dentro del panel) */
        .ms-anim-panel .ms-sk-layer{
            position:absolute; inset:0; z-index:5; display:flex; flex-direction:column; gap:14px;
            padding:18px 20px 20px; background:var(--ms-sk-bg); border-radius:inherit;
            opacity:1; transition:opacity .34s ease;
        }
        .ms-anim-panel:not(.is-loading) .ms-sk-layer{ opacity:0; pointer-events:none; }
        .ms-sk{ position:relative; overflow:hidden; border-radius:9px; background:var(--ms-sk-bar); }
        .ms-sk::after{
            content:''; position:absolute; inset:0; transform:translateX(-100%);
            background:linear-gradient(90deg, transparent, color-mix(in srgb, #fff 78%, transparent), transparent);
            animation:msSkShimmer 1.25s ease-in-out infinite;
        }
        .ms-sk.sk-label{ height:11px; width:38%; } .ms-sk.sk-label.sk-sm{ width:26%; }
        .ms-sk.sk-input{ height:46px; } .ms-sk.sk-area{ height:78px; }
        .ms-sk.sk-title{ height:15px; width:60%; } .ms-sk.sk-kicker{ height:9px; width:44%; }
        .ms-sk.sk-chip{ width:42px; height:42px; flex:0 0 42px; border-radius:12px; }
        .ms-sk.sk-btn{ height:42px; width:104px; border-radius:11px; }
        .ms-sk-head{ display:flex; align-items:center; gap:12px; margin-bottom:4px; }
        .ms-sk-heading{ flex:1; min-width:0; display:grid; gap:7px; }
        .ms-sk-field{ display:grid; gap:8px; }
        .ms-sk-row{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
        .ms-sk-actions{ display:flex; justify-content:flex-end; gap:10px; margin-top:auto; padding-top:8px; }
        @keyframes msSkShimmer{ 100%{ transform:translateX(100%); } }

        /* Móvil: hoja inferior + agarradera (opt-in con .ms-anim-sheet) */
        @media (max-width:640px){
            .ms-anim .ms-anim-panel.ms-anim-sheet{ opacity:1; transform:translateY(100%); transition:transform .4s var(--ms-ease); }
            .ms-anim.ms-anim-in .ms-anim-panel.ms-anim-sheet{ transform:translateY(0); }
            .ms-anim .ms-anim-panel.ms-anim-sheet::before{
                content:''; position:absolute; top:7px; left:50%; transform:translateX(-50%);
                width:42px; height:4px; border-radius:999px; z-index:6;
                background:color-mix(in srgb, var(--brand-muted, #6B7280) 42%, transparent);
            }
        }

        /* Respeta a quien prefiere menos movimiento */
        @media (prefers-reduced-motion: reduce){
            .ms-anim, .ms-anim .ms-anim-panel, .ms-anim-panel .ms-sk-layer{ transition-duration:.01ms !important; }
            .ms-anim .ms-anim-panel{ transform:none !important; }
            .ms-sk::after{ animation:none !important; }
        }
    </style>
    <script>
    (function(){
        if (window.__msModalMotionReady) { return; }
        window.__msModalMotionReady = true;

        var EXIT_FALLBACK = 520;

        function prefersReduced(){
            return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        }
        function panelOf(overlay){
            return overlay.querySelector('.ms-anim-panel') || null;
        }
        function buildSkeleton(panel){
            if (!panel || panel.querySelector('.ms-sk-layer')) { return; }
            var sk = document.createElement('div');
            sk.className = 'ms-sk-layer';
            sk.setAttribute('aria-hidden', 'true');
            sk.innerHTML =
                '<div class="ms-sk-head"><div class="ms-sk sk-chip"></div><div class="ms-sk-heading"><div class="ms-sk sk-kicker"></div><div class="ms-sk sk-title"></div></div></div>' +
                '<div class="ms-sk-field"><div class="ms-sk sk-label"></div><div class="ms-sk sk-input"></div></div>' +
                '<div class="ms-sk-field"><div class="ms-sk sk-label sk-sm"></div><div class="ms-sk sk-area"></div></div>' +
                '<div class="ms-sk-row"><div class="ms-sk-field"><div class="ms-sk sk-label"></div><div class="ms-sk sk-input"></div></div><div class="ms-sk-field"><div class="ms-sk sk-label"></div><div class="ms-sk sk-input"></div></div></div>' +
                '<div class="ms-sk-actions"><div class="ms-sk sk-btn"></div><div class="ms-sk sk-btn"></div></div>';
            panel.appendChild(sk);
        }
        function resolve(overlay){
            return (typeof overlay === 'string') ? document.getElementById(overlay) : overlay;
        }
        function open(overlay, opts){
            overlay = resolve(overlay);
            if (!overlay) { return; }
            opts = opts || {};

            overlay.classList.add('ms-anim');
            overlay.classList.remove('hidden');
            // Convención alterna: modales .modal que se muestran con display inline
            if (opts.display) { overlay.style.display = opts.display; }

            var panel = panelOf(overlay);
            if (panel) {
                var sk = opts.skeleton;
                if (sk && !prefersReduced()) {
                    buildSkeleton(panel);
                    panel.classList.add('is-loading');
                    if (panel._msSkTimer) { clearTimeout(panel._msSkTimer); }
                    panel._msSkTimer = setTimeout(function(){ panel.classList.remove('is-loading'); }, (typeof sk === 'number' ? sk : 520));
                } else {
                    panel.classList.remove('is-loading');
                }
            }

            if (opts.lockScroll !== false) { document.body.classList.add('overflow-hidden'); }

            // Fuerza el estado inicial (oculto) y dispara la transición de entrada.
            void overlay.offsetWidth;
            overlay.classList.add('ms-anim-in');

            if (typeof opts.onOpen === 'function') { opts.onOpen(overlay, panel); }
        }
        function close(overlay, opts){
            overlay = resolve(overlay);
            if (!overlay) { return; }
            opts = opts || {};

            var panel = panelOf(overlay);
            var finish = function(){
                overlay.classList.add('hidden');
                if (opts.display) { overlay.style.display = 'none'; }
                if (opts.lockScroll !== false) { document.body.classList.remove('overflow-hidden'); }
                if (panel) { if (panel._msSkTimer) { clearTimeout(panel._msSkTimer); } panel.classList.remove('is-loading'); }
                if (typeof opts.onClose === 'function') { opts.onClose(overlay, panel); }
            };

            overlay.classList.remove('ms-anim-in');

            if (!panel || prefersReduced()) { finish(); return; }

            var done = false;
            var onEnd = function(e){
                if (e && (e.target !== panel || (e.propertyName && e.propertyName !== 'transform'))) { return; }
                if (done) { return; }
                done = true;
                panel.removeEventListener('transitionend', onEnd);
                finish();
            };
            panel.addEventListener('transitionend', onEnd);
            setTimeout(onEnd, EXIT_FALLBACK);
        }

        // Cierre al hacer clic en el fondo (opt-in con data-ms-overlay-close en el overlay)
        document.addEventListener('mousedown', function(e){
            var overlay = e.target;
            if (!(overlay instanceof Element)) { return; }
            overlay = overlay.closest('.ms-anim[data-ms-overlay-close]');
            if (!overlay) { return; }
            var panel = panelOf(overlay);
            if (panel && !panel.contains(e.target)) { close(overlay); }
        });

        window.msModal = { open: open, close: close, buildSkeleton: buildSkeleton, panelOf: panelOf };
    })();
    </script>

</body>
</html>
