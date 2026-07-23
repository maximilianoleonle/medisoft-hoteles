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

// Cap deslizante: total de columnas (atajos + Menú) e índice del activo.
$footerNavTotal = count($footerNavItems) + 1;
$footerNavActiveIndex = -1;
$footerNavIdx = 0;
foreach ($footerNavItems as $footerNavKey => $footerNavItem) {
    if ($footerNavKey === $footerNavActiveKey) {
        $footerNavActiveIndex = $footerNavIdx;
        break;
    }
    $footerNavIdx++;
}
?>

<nav class="hotel-bottom-nav" id="hotel-bottom-nav" aria-label="Accesos rápidos"
     style="--hbn-count:<?= (int) $footerNavTotal ?>;--hbn-active:<?= (int) $footerNavActiveIndex ?>;"
     <?= $footerNavActiveIndex < 0 ? 'data-no-active' : '' ?>>
    <span class="hbn-cap" id="hbn-cap" aria-hidden="true"></span>
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

    <button type="button" class="hbn-item hbn-menu" id="hotel-bottom-nav-menu" aria-label="Abrir menú completo" aria-controls="sidebar" aria-expanded="false">
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
    /* ── V12: barra flotante de vidrio con cap deslizante ── */
    .hotel-bottom-nav {
        position: fixed;
        left: 14px;
        right: 14px;
        bottom: calc(6px + env(safe-area-inset-bottom, 0px));
        z-index: 980; /* debajo del menú lateral (cajón 10020 / fondo 10010) y del header móvil */
        height: 64px;
        padding: 7px;
        transform: translateY(0);
        transition: transform .22s ease, opacity .22s ease;
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: 1fr;
        align-items: stretch;
        background: rgba(255, 255, 255, .72);
        /* blur 10 (era saturate(180%)+blur 24): en iOS el backdrop se
           re-desenfoca en CADA frame de scroll y esta barra vive fija en
           todas las vistas — mismo vidrio a ~1/4 del costo de GPU. El fondo
           sube de .6 a .72 para compensar la legibilidad del cristal. */
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        border: .5px solid rgba(255, 255, 255, .8);
        border-radius: 24px;
        box-shadow: 0 14px 34px rgba(27, 39, 70, .18);
    }

    /* Cap deslizante: píldora del color primario del hotel bajo el ítem activo */
    .hotel-bottom-nav .hbn-cap {
        position: absolute;
        top: 7px;
        left: 7px;
        width: calc((100% - 14px) / var(--hbn-count, 5));
        height: calc(100% - 14px);
        background: var(--hotel-brand, var(--brand-primary, #1B2746));
        border-radius: 18px;
        box-shadow: 0 5px 14px color-mix(in srgb, var(--hotel-brand, #1B2746) 30%, transparent);
        transform: translateX(calc(var(--hbn-active, 0) * 100%));
        transition: transform .34s cubic-bezier(.22, 1, .36, 1);
        pointer-events: none;
    }

    .hotel-bottom-nav[data-no-active] .hbn-cap {
        opacity: 0;
        transform: translateX(0) scale(.6);
    }

    .hotel-bottom-nav .hbn-item {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        min-height: 0;
        padding: 2px 4px;
        border: 0;
        background: none;
        border-radius: 18px;
        color: var(--hotel-muted, #939BAD);
        text-decoration: none;
        font-family: inherit;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        transition: color .2s ease;
    }

    .hotel-bottom-nav .hbn-pill {
        display: grid;
        place-items: center;
        width: 24px;
        height: 24px;
        border-radius: 0;
        font-size: 1.05rem;
        line-height: 1;
        background: transparent;
        transition: transform .3s cubic-bezier(.34, 1.56, .64, 1);
    }

    .hotel-bottom-nav .hbn-label {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        /* Responsivo: baja en pantallas angostas para que etiquetas largas
           (ej. "Habitaciones") no rocen el borde de su columna. */
        font-size: clamp(.5rem, 2.4vw, .56rem);
        font-weight: 700;
        letter-spacing: 0;
        line-height: 1;
    }

    .hotel-bottom-nav .hbn-item:active .hbn-pill {
        transform: scale(.9);
    }

    /* Activo: texto en el color "sobre primario"; el icono usa el acento
     * aclarado con blanco para garantizar contraste sobre el cap en
     * cualquier paleta de hotel. */
    .hotel-bottom-nav .hbn-item.is-active {
        color: var(--hotel-on-brand, #FFFFFF);
    }

    .hotel-bottom-nav .hbn-item.is-active .hbn-pill {
        color: color-mix(in srgb, var(--hotel-accent, #D8BC83) 40%, #FFFFFF);
        transform: translateY(-1px) scale(1.06);
    }

    .hotel-bottom-nav .hbn-item:focus-visible {
        outline: 2px solid var(--hotel-accent, #BD9441);
        outline-offset: -2px;
    }

    /* Con un modal u overlay abierto, la barra se retira para no interferir */
    .hotel-bottom-nav[hidden],
    body.hbn-overlay-open .hotel-bottom-nav,
    /* ── Y con el menú lateral abierto ──
     * El cajón mide 86vw y va a z-index 10020, así que tapaba la barra: se
     * veía asomada por debajo pero los toques se los quedaba él (desde caja,
     * abrir el menú y tocar "Caja" en la barra no hacía nada). Subirle el
     * z-index a la barra NO sirvió — algún ancestro le atrapa el apilamiento —
     * así que se esconde, que además es lo honesto: no mostrar algo que no se
     * puede tocar. Para salir del menú está la hamburguesa del header, que lo
     * alterna abrir/cerrar y queda por encima del cajón.
     * Van las dos formas de detectarlo a propósito: la clase la pone
     * syncOverlayState() y el :has() no depende de JS. Cualquiera basta. */
    body.hbn-menu-abierto .hotel-bottom-nav,
    body:has(#sidebar.active) .hotel-bottom-nav,
    body:has(#sidebar.open) .hotel-bottom-nav,
    body.hb-mobile-sheet-open .hotel-bottom-nav,
    body.hb-modal-open .hotel-bottom-nav,
    body.swal2-shown .hotel-bottom-nav,
    body.overflow-hidden .hotel-bottom-nav,
    body:has(#hbMobileRoomSheet.is-open) .hotel-bottom-nav,
    body:has(#modalLimpieza:not(.hidden)) .hotel-bottom-nav,
    body:has(#vistaRapidaModal:not(.hidden)) .hotel-bottom-nav,
    body:has(.tc-modal:not(.hidden)) .hotel-bottom-nav,
    body:has(.swal2-container.swal2-backdrop-show) .hotel-bottom-nav,
    body:has(.fixed.inset-0:not(.hidden)) .hotel-bottom-nav {
        display: none !important;
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
     * Barra flotante: 64px de alto + 6px de aire abajo + 8px de holgura. */
    body.has-hotel-bottom-nav {
        --hbn-offset: calc(78px + env(safe-area-inset-bottom, 0px));
    }

    /* La barra flota: el shell llega hasta abajo y el CONTENIDO pasa por
     * debajo del vidrio; solo se compensa con padding al final del scroll
     * para que el último elemento no quede tapado. */
    body.hotel-layout-scope.has-hotel-bottom-nav .main-content {
        padding-bottom: calc(var(--hbn-offset) + 24px) !important;
        scroll-padding-bottom: calc(var(--hbn-offset) + 24px);
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

    /* La barra se renderiza en el header (antes que el sidebar y el resto del
     * documento), así que los elementos externos se resuelven al momento de
     * usarlos, no al cargar este script. */
    var menuBtn = document.getElementById('hotel-bottom-nav-menu');

    /* ── Cap deslizante: acompaña el tap antes de navegar ──
     * El slide y el cambio de color (is-active) son inmediatos al tocar,
     * para que no quede el ítem viejo "encendido" mientras carga la vista. */
    var hbnNav = document.getElementById('hotel-bottom-nav');
    var hbnInitialActive = hbnNav ? parseInt(hbnNav.style.getPropertyValue('--hbn-active') || '-1', 10) : -1;
    var hbnInitialEl = hbnNav ? hbnNav.querySelector('.hbn-item.is-active') : null;

    function hbnMarkActive(target) {
        if (!hbnNav) return;
        var items = hbnNav.querySelectorAll('.hbn-item');
        for (var j = 0; j < items.length; j++) {
            items[j].classList.toggle('is-active', items[j] === target);
        }
    }

    function hbnSlideCap(idx, target) {
        if (!hbnNav) return;
        if (idx < 0) {
            hbnNav.setAttribute('data-no-active', '');
            hbnMarkActive(null);
            return;
        }
        hbnNav.removeAttribute('data-no-active');
        hbnNav.style.setProperty('--hbn-active', idx);
        if (target !== undefined) hbnMarkActive(target);
    }

    if (hbnNav) {
        var hbnItems = hbnNav.querySelectorAll('.hbn-item');
        for (var hi = 0; hi < hbnItems.length; hi++) {
            (function (item, idx) {
                item.addEventListener('click', function () {
                    hbnSlideCap(idx, item);
                });
            })(hbnItems[hi], hi);
        }
    }

    // El botón Menú ALTERNA el menú móvil (header y footer siguen visibles;
    // no hay overlay ni X). Al cerrar, el cap regresa al atajo de la vista.
    if (menuBtn) {
        menuBtn.addEventListener('click', function (e) {
            e.preventDefault();

            var sidebar = document.getElementById('sidebar');
            if (!sidebar) return;

            var abierto = sidebar.classList.toggle('active');
            document.body.style.overflow = abierto ? 'hidden' : '';
            menuBtn.setAttribute('aria-expanded', abierto ? 'true' : 'false');

            if (!abierto) {
                hbnSlideCap(hbnInitialActive, hbnInitialEl);
            }
        });
    }

    // La hamburguesa del header también alterna el menú: el cap la acompaña.
    function hbnBindHeaderToggle() {
        var headerToggle = document.getElementById('mobile-menu-toggle');
        if (!headerToggle || headerToggle.dataset.hbnCapBound) return;
        headerToggle.dataset.hbnCapBound = 'true';
        headerToggle.addEventListener('click', function () {
            var sidebar = document.getElementById('sidebar');
            var abierto = sidebar && sidebar.classList.contains('active');
            if (menuBtn) menuBtn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
            if (abierto && menuBtn) {
                var items = hbnNav ? hbnNav.querySelectorAll('.hbn-item') : [];
                for (var k = 0; k < items.length; k++) {
                    if (items[k] === menuBtn) { hbnSlideCap(k, menuBtn); break; }
                }
            } else {
                hbnSlideCap(hbnInitialActive, hbnInitialEl);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hbnBindHeaderToggle);
    } else {
        hbnBindHeaderToggle();
    }

    /* ── Ocultar la barra mientras haya un modal/overlay visible ──
     * Las vistas usan modales muy distintos (Tailwind z-50, SweetAlert2,
     * bottom-sheets propios), así que en lugar de pelear con z-index se
     * detecta genéricamente cualquier capa fija que cubra la pantalla. */
    /* El menú móvil (#sidebar / .sidebar-overlay) NO cuenta como modal:
     * el footer permanece visible mientras el menú está abierto. */
    var OVERLAY_SELECTOR = '.swal2-container, .fixed.inset-0, [id*="modal" i], [class*="modal" i], [id*="sheet" i], [class*="sheet" i], .xpm-ov';
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
        var isOpen = anyOverlayOpen();
        var nav = document.getElementById('hotel-bottom-nav');

        document.body.classList.toggle('hbn-overlay-open', isOpen);

        // Con el menú lateral abierto la barra se esconde: el cajón la tapaba
        // y se quedaba con los toques (ver el bloque de CSS que la retira).
        // Se mira #sidebar directamente en vez de engancharse a quien lo abre,
        // porque hay dos botones (el "Menú" de aquí y la hamburguesa del
        // header, en otro archivo) y el observer de arriba ya nos trae hasta
        // aquí en cuanto cambia cualquier clase del documento.
        var sidebarEl = document.getElementById('sidebar');
        document.body.classList.toggle('hbn-menu-abierto', !!(sidebarEl
            && (sidebarEl.classList.contains('active') || sidebarEl.classList.contains('open'))));

        // Bloqueo de scroll del fondo con un modal abierto. El scroll real de la
        // app NO vive en body/html (el shell es h-screen overflow-hidden) sino en
        // .main-content, asi que congelar el body no basta: sin esto el fondo se
        // desliza por detras del modal. Se fija overflow:hidden inline (gana a
        // cualquier regla) y se restaura al cerrarse; la posicion no se pierde.
        var mainScroll = document.querySelector('.main-content');
        if (mainScroll) {
            if (isOpen) {
                mainScroll.style.setProperty('overflow', 'hidden', 'important');
            } else {
                mainScroll.style.removeProperty('overflow');
            }
        }

        if (nav) {
            if (isOpen) {
                nav.setAttribute('hidden', 'hidden');
                nav.setAttribute('aria-hidden', 'true');
            } else {
                nav.removeAttribute('hidden');
                nav.removeAttribute('aria-hidden');
            }
        }
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
