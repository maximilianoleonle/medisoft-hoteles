<?php
/**
 * Barra inferior de accesos rápidos (solo móvil, PWA del hotel).
 *
 * Los atajos se configuran por hotel en Configuración → Barra inferior.
 * El botón "Menú" es fijo y abre la navegación completa.
 */

$footerNavRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

$footerNavEsPanelSaas = strpos($footerNavRequestPath, '/admin/saas') === 0;
$footerNavAutenticado = function_exists('is_authenticated') && is_authenticated();
$footerNavConContexto = function_exists('has_hotel_context') && has_hotel_context();

if ($footerNavEsPanelSaas || !$footerNavAutenticado || !$footerNavConContexto || !function_exists('hotel_footer_nav_items')) {
    return;
}

$footerNavItems = hotel_footer_nav_items();

if (empty($footerNavItems)) {
    return;
}

$footerNavNormalizedPath = '/' . trim($footerNavRequestPath, '/');
if ($footerNavNormalizedPath === '/') {
    $footerNavNormalizedPath = '/dashboard';
}

// Activo = el atajo cuyo path coincide con el prefijo más largo de la URL actual
// (así "Calendario" gana sobre "Reservas" en /reservaciones/calendario).
$footerNavActiveKey = null;
$footerNavBestLength = 0;

foreach ($footerNavItems as $footerNavKey => $footerNavItem) {
    $footerNavPrefix = '/' . trim((string) $footerNavItem['path'], '/');
    $footerNavMatches = $footerNavNormalizedPath === $footerNavPrefix
        || strpos($footerNavNormalizedPath, $footerNavPrefix . '/') === 0;

    if ($footerNavMatches && strlen($footerNavPrefix) > $footerNavBestLength) {
        $footerNavActiveKey = $footerNavKey;
        $footerNavBestLength = strlen($footerNavPrefix);
    }
}
?>

<nav class="hotel-bottom-nav" id="hotel-bottom-nav" aria-label="Accesos rápidos">
    <?php foreach ($footerNavItems as $footerNavKey => $footerNavItem): ?>
    <?php $footerNavEsActivo = $footerNavKey === $footerNavActiveKey; ?>
    <a href="<?= url($footerNavItem['path']) ?>"
       class="hbn-item<?= $footerNavEsActivo ? ' is-active' : '' ?>"
       <?= $footerNavEsActivo ? 'aria-current="page"' : '' ?>>
        <span class="hbn-pill" aria-hidden="true">
            <i class="fas <?= htmlspecialchars($footerNavItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
        </span>
        <span class="hbn-label"><?= htmlspecialchars($footerNavItem['short'], ENT_QUOTES, 'UTF-8') ?></span>
    </a>
    <?php endforeach; ?>

    <button type="button" class="hbn-item hbn-menu" id="hotel-bottom-nav-menu" aria-label="Abrir menú completo">
        <span class="hbn-pill" aria-hidden="true">
            <i class="fas fa-grip"></i>
        </span>
        <span class="hbn-label">Menú</span>
    </button>
</nav>

<style>
.hotel-bottom-nav {
    display: none;
}

@media (max-width: 1024px) {
    .hotel-bottom-nav {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 980; /* debajo del overlay del sidebar (999) y del header móvil */
        transform: translateY(0);
        transition: transform .22s ease, opacity .22s ease;
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: 1fr;
        align-items: stretch;
        padding: 6px 4px calc(6px + env(safe-area-inset-bottom, 0px));
        background: color-mix(in srgb, var(--hotel-panel, #FFFEFB) 94%, transparent);
        -webkit-backdrop-filter: blur(14px) saturate(1.3);
        backdrop-filter: blur(14px) saturate(1.3);
        border-top: 1px solid var(--hotel-border, #E7DEC9);
        box-shadow: 0 -12px 32px rgba(15, 23, 42, 0.08);
    }

    .hotel-bottom-nav .hbn-item {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        min-height: 52px;
        padding: 4px 2px;
        border: 0;
        background: none;
        border-radius: 12px;
        color: var(--hotel-muted, #6B7280);
        text-decoration: none;
        font-family: inherit;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: color .18s ease;
    }

    .hotel-bottom-nav .hbn-pill {
        display: grid;
        place-items: center;
        width: 46px;
        height: 26px;
        border-radius: 999px;
        font-size: 1rem;
        line-height: 1;
        background: transparent;
        transition: background-color .18s ease, transform .18s ease;
    }

    .hotel-bottom-nav .hbn-label {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: .6rem;
        font-weight: 650;
        letter-spacing: .01em;
        line-height: 1;
    }

    .hotel-bottom-nav .hbn-item:active .hbn-pill {
        transform: scale(.92);
    }

    .hotel-bottom-nav .hbn-item.is-active {
        color: var(--hotel-brand-dark, #0F172A);
    }

    .hotel-bottom-nav .hbn-item.is-active .hbn-pill {
        background: color-mix(in srgb, var(--hotel-brand, #1B2746) 14%, #FFFFFF);
        color: var(--hotel-brand-dark, #0F172A);
    }

    .hotel-bottom-nav .hbn-item.is-active .hbn-label {
        font-weight: 700;
    }

    .hotel-bottom-nav .hbn-item.is-active::before {
        content: '';
        position: absolute;
        top: -6px;
        left: 50%;
        transform: translateX(-50%);
        width: 22px;
        height: 3px;
        border-radius: 0 0 999px 999px;
        background: var(--hotel-accent, #BD9441);
    }

    .hotel-bottom-nav .hbn-item:focus-visible {
        outline: 2px solid var(--hotel-accent, #BD9441);
        outline-offset: -2px;
    }

    /* Con un modal u overlay abierto, la barra se retira para no interferir */
    body.hbn-overlay-open .hotel-bottom-nav,
    body.hb-mobile-sheet-open .hotel-bottom-nav,
    body.hb-modal-open .hotel-bottom-nav,
    body.swal2-shown .hotel-bottom-nav,
    body.overflow-hidden .hotel-bottom-nav {
        transform: translate3d(0, calc(100% + 16px), 0);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    @media (prefers-reduced-motion: reduce) {
        .hotel-bottom-nav,
        .hotel-bottom-nav .hbn-item,
        .hotel-bottom-nav .hbn-pill {
            transition: none;
        }
    }

    /* ── La vista completa se recorre hacia arriba: nada queda bajo la barra ──
     * Alto real de la barra: 6px + 52px + 6px = 64px, más safe-area. */
    body.has-hotel-bottom-nav {
        --hbn-offset: calc(64px + env(safe-area-inset-bottom, 0px));
    }

    /* Vistas con scroll interno (todas menos dashboard): el shell termina
     * exactamente donde empieza la barra (60px header + 4px + barra). */
    body.hotel-layout-scope.has-hotel-bottom-nav:not(.page-dashboard) > .flex.h-screen.overflow-hidden {
        height: calc(100dvh - 64px - var(--hbn-offset)) !important;
        min-height: calc(100dvh - 64px - var(--hbn-offset)) !important;
        max-height: calc(100dvh - 64px - var(--hbn-offset)) !important;
    }

    @supports not (height: 100dvh) {
        body.hotel-layout-scope.has-hotel-bottom-nav:not(.page-dashboard) > .flex.h-screen.overflow-hidden {
            height: calc(100vh - 64px - var(--hbn-offset)) !important;
            min-height: calc(100vh - 64px - var(--hbn-offset)) !important;
            max-height: calc(100vh - 64px - var(--hbn-offset)) !important;
        }
    }

    /* Dashboard: el documento hace scroll, se compensa con espacio al final */
    body.hotel-layout-scope.has-hotel-bottom-nav.page-dashboard .main-content {
        padding-bottom: calc(var(--hbn-offset) + 24px) !important;
    }

    /* ── Header auto-oculto: el contenido ocupa su espacio ──
     * Al scrollear hacia abajo el shell agrega body.header-hidden; sin esto
     * quedaba una franja en blanco de 60px arriba. */
    body.hotel-layout-scope.has-hotel-bottom-nav {
        transition: none;
    }

    body.hotel-layout-scope.has-hotel-bottom-nav.header-hidden {
        padding-top: 64px !important;
    }

    body.hotel-layout-scope.has-hotel-bottom-nav:not(.page-dashboard) > .flex.h-screen.overflow-hidden {
        transition: none;
    }

    body.hotel-layout-scope.has-hotel-bottom-nav.header-hidden:not(.page-dashboard) > .flex.h-screen.overflow-hidden {
        height: calc(100dvh - 64px - var(--hbn-offset)) !important;
        min-height: calc(100dvh - 64px - var(--hbn-offset)) !important;
        max-height: calc(100dvh - 64px - var(--hbn-offset)) !important;
    }

    @supports not (height: 100dvh) {
        body.hotel-layout-scope.has-hotel-bottom-nav.header-hidden:not(.page-dashboard) > .flex.h-screen.overflow-hidden {
            height: calc(100vh - 64px - var(--hbn-offset)) !important;
            min-height: calc(100vh - 64px - var(--hbn-offset)) !important;
            max-height: calc(100vh - 64px - var(--hbn-offset)) !important;
        }
    }

    /* La barrita de progreso de scroll sube junto con el header */
    body.has-hotel-bottom-nav.header-hidden .scroll-progress {
        top: 64px;
    }

    /* Flotantes anclados al fondo suben el alto de la barra */
    body.has-hotel-bottom-nav #ms-toast-stack {
        bottom: calc(var(--hbn-offset) + 12px);
    }

    body.has-hotel-bottom-nav #pwa-update-banner {
        bottom: calc(var(--hbn-offset) + 12px);
    }

    /* Barra de resumen/checkout al crear reservación */
    body.has-hotel-bottom-nav .resumen-flotante {
        bottom: var(--hbn-offset);
    }

    /* Barra de acciones (check-in/check-out/cancelar) en ver reservación */
    body.has-hotel-bottom-nav .rdv3-mobile-bottom {
        bottom: var(--hbn-offset);
    }

    /* Toasts inline de las vistas */
    body.has-hotel-bottom-nav .res-inline-toast,
    body.has-hotel-bottom-nav .rdv3-inline-toast,
    body.has-hotel-bottom-nav .purchase-toast,
    body.has-hotel-bottom-nav .doc-action-toast,
    body.has-hotel-bottom-nav .worker-action-toast,
    body.has-hotel-bottom-nav .lim-rep-toast {
        bottom: calc(var(--hbn-offset) + 14px);
    }
}
</style>

<script>
(function () {
    document.body.classList.add('has-hotel-bottom-nav');

    var menuBtn = document.getElementById('hotel-bottom-nav-menu');

    if (menuBtn) {
        menuBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var hamburger = document.getElementById('mobile-menu-toggle');
            if (hamburger) {
                hamburger.click();
                return;
            }

            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    }

    /* ── Ocultar la barra mientras haya un modal/overlay visible ──
     * Las vistas usan modales muy distintos (Tailwind z-50, SweetAlert2,
     * bottom-sheets propios), así que en lugar de pelear con z-index se
     * detecta genéricamente cualquier capa fija que cubra la pantalla. */
    var OVERLAY_SELECTOR = '.swal2-container, .fixed.inset-0, [id*="modal" i], [class*="modal" i], [id*="sheet" i], [class*="sheet" i], .xpm-ov, .sidebar-overlay';
    var OVERLAY_BODY_CLASSES = ['swal2-shown', 'hb-mobile-sheet-open', 'hb-modal-open', 'overflow-hidden'];
    var overlayCheckQueued = false;

    function isOverlayVisible(el) {
        if (!el || el.closest('.hotel-bottom-nav')) {
            return false;
        }

        var cs = window.getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden' || parseFloat(cs.opacity) === 0) {
            return false;
        }

        if (cs.position !== 'fixed') {
            return false;
        }

        var rect = el.getBoundingClientRect();
        // Debe cubrir buena parte de la pantalla (backdrop o bottom-sheet).
        return rect.width >= window.innerWidth * 0.55
            && rect.height >= window.innerHeight * 0.35;
    }

    function anyOverlayOpen() {
        for (var c = 0; c < OVERLAY_BODY_CLASSES.length; c++) {
            if (document.body.classList.contains(OVERLAY_BODY_CLASSES[c])) {
                return true;
            }
        }

        var candidates = document.querySelectorAll(OVERLAY_SELECTOR);
        for (var i = 0; i < candidates.length; i++) {
            if (isOverlayVisible(candidates[i])) {
                return true;
            }
        }

        return false;
    }

    function syncOverlayState() {
        overlayCheckQueued = false;
        document.body.classList.toggle('hbn-overlay-open', anyOverlayOpen());
    }

    var lateOverlayCheck = null;

    function queueOverlayCheck() {
        if (!overlayCheckQueued) {
            overlayCheckQueued = true;
            window.requestAnimationFrame(syncOverlayState);
        }

        // Los modales suelen cerrarse con fade: al terminar la transición ya no
        // hay mutación DOM, así que se re-verifica un poco después.
        window.clearTimeout(lateOverlayCheck);
        lateOverlayCheck = window.setTimeout(syncOverlayState, 380);
    }

    var observer = null;

    function bindOverlayObserver() {
        if (observer) {
            observer.disconnect();
        }

        observer = new MutationObserver(queueOverlayCheck);
        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'hidden']
        });

        syncOverlayState();
    }

    bindOverlayObserver();

    // Algunas vistas pesadas dejan huérfano al observer inicial; se re-registra
    // con la página ya cargada y se usa el click como señal de respaldo
    // (todo modal se abre o cierra a partir de un tap).
    window.addEventListener('load', bindOverlayObserver);
    document.addEventListener('DOMContentLoaded', bindOverlayObserver);
    document.addEventListener('click', queueOverlayCheck, true);
    document.addEventListener('transitionend', queueOverlayCheck, true);
    document.addEventListener('animationend', queueOverlayCheck, true);

    // Red de seguridad: en algunas vistas los listeners/observers registrados
    // durante el parseo dejan de disparar; los timers no. El toggle es
    // idempotente, así que este pulso solo corrige estados desincronizados.
    window.setInterval(syncOverlayState, 600);
})();
</script>
