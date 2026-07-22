<?php
/**
 * Barra de navegación global de vistas — flecha de regreso + migas + hotel.
 *
 * Réplica del lenguaje de la topbar de Caja / detalle de reservación
 * (aprobada por el owner): [← chip] "Sección / Página actual" + nombre del
 * hotel debajo. Se incluye UNA sola vez desde layout/header.php, por lo que
 * queda como primer elemento dentro de <main class="main-content"> en TODAS
 * las vistas con layout.
 *
 * Exclusiones (no se pinta):
 *   - Dashboard (raíz de la app) y panel SaaS (/admin/saas).
 *   - Reportes: la sección /reportes completa y cualquier ruta con pinta de
 *     reporte/documento imprimible (contrato: los reportes quedan intactos).
 *   - Vistas que YA tienen esta barra como parte de su diseño aprobado:
 *     /caja (cj-topbar) y /reservaciones/ver/N (rdv3-topbar).
 *
 * Reemplazo de migas locales: al renderizarse, la barra oculta las migas y
 * flechas de regreso viejas de las vistas (kill-list al final del CSS). Las
 * reglas solo existen en páginas donde la barra se pinta, así que las vistas
 * excluidas conservan su diseño intacto.
 */

if (!function_exists('url') || !function_exists('has_hotel_context') || !has_hotel_context()) {
    return;
}
if (!empty($layoutEsPanelSaas)) {
    return;
}

// ── Ruta actual relativa a la app (sin base ni query) ──
$vtbBase = rtrim((string) (parse_url(url(''), PHP_URL_PATH) ?: ''), '/');
$vtbPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
if ($vtbBase !== '' && strpos($vtbPath, $vtbBase) === 0) {
    $vtbPath = substr($vtbPath, strlen($vtbBase));
}
$vtbRuta = trim($vtbPath, '/');
$vtbSegs = $vtbRuta === '' ? [] : explode('/', strtolower($vtbRuta));
$vtbRoot = $vtbSegs[0] ?? '';

// ── Exclusiones ──
$vtbSkip = $vtbRuta === ''
    || in_array($vtbRoot, ['dashboard', 'inicio', 'admin', 'api', 'login', 'logout', 'dueno'], true)
    || $vtbRoot === 'reportes'                                   // hermanitos de reportes
    || strpos($vtbRuta, 'reporte') !== false                     // reportes de otros módulos (contrato)
    || strpos($vtbRuta, 'recibo-laboral') !== false              // documentos imprimibles
    || strpos($vtbRuta, 'expediente') !== false
    || strpos($vtbRuta, 'pagos-snapshot') !== false
    || strpos($vtbRuta, 'auditoria-consolidada') !== false
    || $vtbRuta === 'caja'                                       // topbar propia (modelo aprobado)
    || ($vtbRoot === 'reservaciones' && ($vtbSegs[1] ?? '') === 'ver'); // rdv3-topbar propia

if ($vtbSkip) {
    return;
}

// ── Catálogo de pantallas (etiquetas canónicas por ruta) ──
if (!function_exists('nav_pantallas_catalogo')) {
    $vtbHelper = APP_PATH . '/helpers/navegacion.php';
    if (is_readable($vtbHelper)) {
        require_once $vtbHelper;
    }
}
$vtbCatalogo = [];
if (function_exists('nav_pantallas_catalogo')) {
    try {
        foreach (nav_pantallas_catalogo() as $vtbEntrada) {
            if (!empty($vtbEntrada['ruta']) && !empty($vtbEntrada['etiqueta'])) {
                $vtbCatalogo[$vtbEntrada['ruta']] = $vtbEntrada['etiqueta'];
            }
        }
    } catch (Throwable $e) {
        $vtbCatalogo = [];
    }
}
// Secciones sin entrada propia en el catálogo
$vtbExtras = [
    'roles'          => 'Roles y permisos',
    'lavanderia'     => 'Lavandería',
    'lealtad'        => 'Huésped frecuente',
    'forecast'       => 'Pronóstico de ocupación',
    'night-audit'    => 'Cierre del día',
    'auditoria'      => 'Historial de actividad',
    'correccion'     => 'Corrección',
    'operacion'      => 'Operación',
    'offline'        => 'Offline',
    'navegacion'     => 'Navegación',
    'ia'             => 'Asistente IA',
    'copiloto'       => 'Uso del asistente',
    'reputacion'     => 'Opiniones y encuestas',
];

$vtbHotel = function_exists('current_hotel_display_name') ? current_hotel_display_name() : 'Medisoft Hoteles';

// Etiqueta de la página actual: título del controlador sin el sufijo del hotel
$vtbPagina = trim((string) ($title ?? ''));
if ($vtbPagina !== '' && $vtbHotel !== '') {
    $vtbPagina = trim((string) preg_replace('/\s*[-–—|·:]\s*' . preg_quote($vtbHotel, '/') . '\s*$/u', '', $vtbPagina));
}

$vtbSeccionLabel = $vtbCatalogo[$vtbRoot] ?? ($vtbExtras[$vtbRoot] ?? ucfirst(str_replace('-', ' ', $vtbRoot)));
$vtbExacta = $vtbCatalogo[$vtbRuta] ?? null;
// "Nómina · Empleados" bajo la sección "Nómina" → miga "Empleados" (sin eco)
if ($vtbExacta !== null && strpos($vtbExacta, $vtbSeccionLabel . ' · ') === 0) {
    $vtbExacta = substr($vtbExacta, strlen($vtbSeccionLabel . ' · '));
}

if ($vtbExacta !== null && strpos($vtbRuta, '/') === false) {
    // Índice de sección: una sola miga
    $vtbCrumbs = [['label' => $vtbExacta]];
    $vtbBack = back_url('dashboard');
} elseif ($vtbExacta !== null) {
    // Subpantalla catalogada: Sección / Etiqueta canónica
    $vtbCrumbs = isset($vtbCatalogo[$vtbRoot])
        ? [['label' => $vtbSeccionLabel, 'href' => url($vtbRoot)], ['label' => $vtbExacta]]
        : [['label' => $vtbExacta]];
    $vtbBack = isset($vtbCatalogo[$vtbRoot]) ? back_url($vtbRoot) : back_url('dashboard');
} elseif (strpos($vtbRuta, '/') !== false && isset($vtbCatalogo[$vtbRoot])) {
    // Subpantalla libre: Sección / título de la página
    $vtbActual = $vtbPagina !== '' ? $vtbPagina : ucfirst(str_replace(['-', '_'], ' ', end($vtbSegs)));
    if (mb_strtolower($vtbActual, 'UTF-8') === mb_strtolower($vtbSeccionLabel, 'UTF-8')) {
        $vtbCrumbs = [['label' => $vtbSeccionLabel]];
    } else {
        $vtbCrumbs = [['label' => $vtbSeccionLabel, 'href' => url($vtbRoot)], ['label' => $vtbActual]];
    }
    $vtbBack = back_url($vtbRoot);
} else {
    // Sección no catalogada (raíz o profunda): una sola miga con el mejor nombre
    $vtbActual = $vtbPagina !== '' ? $vtbPagina : $vtbSeccionLabel;
    $vtbCrumbs = [['label' => $vtbActual]];
    $vtbBack = strpos($vtbRuta, '/') !== false ? back_url($vtbRoot) : back_url('dashboard');
}
?>
<nav class="ms-vtb" aria-label="Ruta de navegación">
    <a href="<?= htmlspecialchars($vtbBack, ENT_QUOTES, 'UTF-8') ?>" class="ms-vtb-back" aria-label="Regresar" title="Regresar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="ms-vtb-copy">
        <div class="ms-vtb-crumbs">
            <?php foreach ($vtbCrumbs as $vtbIdx => $vtbCrumb): ?>
                <?php if ($vtbIdx > 0): ?><span class="ms-vtb-sep">/</span><?php endif; ?>
                <?php if ($vtbIdx === count($vtbCrumbs) - 1): ?>
                    <strong><?= htmlspecialchars($vtbCrumb['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                <?php elseif (!empty($vtbCrumb['href'])): ?>
                    <a href="<?= htmlspecialchars($vtbCrumb['href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($vtbCrumb['label'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <span><?= htmlspecialchars($vtbCrumb['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
<style>
/* ── Barra de navegación global (partials/view_topbar.php) ──
   Transparente y SIN línea EN REPOSO: se funde con el fondo de la vista. Un
   script (abajo) la anida dentro del contenedor raíz de cada vista para que
   herede su fondo exacto (decisión del owner: "parejo, sin separación").
   Al hacer scroll se queda FIJA arriba (position:sticky) y solo entonces
   toma el vidrio esmerilado + línea fina, igual que la topbar de Caja, para
   que el contenido no se transparente por debajo (pedido del owner: "solo la
   línea de la flechita se queda arriba con el scroll"). */
.ms-vtb {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 14px 24px 10px;
    /* Un poco de aire vertical: la flecha respira y, al fijarse, la barra de
       vidrio no queda tan chata. Se aplica en reposo Y pegada (is-stuck solo
       pisa el padding horizontal) → misma altura en ambos estados = sin salto
       al pegarse. align-items:center mantiene la flecha centrada. */
    padding: 5px 0;
    background: transparent;
    border: 0;
    position: sticky;
    top: 0;
    z-index: 30;
    transition: background .2s ease, box-shadow .2s ease, border-color .2s ease;
}
/* Anidada en un contenedor con padding propio: sin doble sangría */
.ms-vtb--nested { margin: 0 0 12px; }
/* Anidada en un contenedor SIN padding: conserva su aire */
.ms-vtb--nested.ms-vtb--pad { margin: 14px 24px 10px; }
/* Fijada arriba: vidrio esmerilado + línea fina a ancho completo del
   contenedor (el margen negativo lleva el vidrio al borde; el script pone
   --vtb-mar-* / --vtb-pad-*). La sangría de la flechita no cambia: el padding
   reañade exactamente el hueco de reposo. */
.ms-vtb.is-stuck {
    margin-top: 0;
    margin-left: calc(var(--vtb-mar-l, 24px) * -1);
    margin-right: calc(var(--vtb-mar-r, 24px) * -1);
    /* Sin padding vertical: la barra conserva su alto exacto al fijarse → cero
       salto. Solo el vidrio/borde delatan que se pegó. */
    padding-left: var(--vtb-pad-l, 24px);
    padding-right: var(--vtb-pad-r, 24px);
    background: rgba(255, 255, 255, .82);
    -webkit-backdrop-filter: saturate(180%) blur(14px);
    backdrop-filter: saturate(180%) blur(14px);
    border-bottom: 1px solid var(--brand-border, #E4DDCE);
    box-shadow: 0 6px 18px rgba(27, 39, 70, .06);
}
html[data-theme="dark"] .ms-vtb.is-stuck {
    background: rgba(22, 24, 30, .78);
    border-bottom-color: rgba(255, 255, 255, .10);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .28);
}
/* Cupertino (solo claro): la barra de Caja usa este mismo lenguaje de vidrio.
   En oscuro gana la regla de arriba (borde tinta clara). */
html[data-tema="cupertino"]:not([data-theme="dark"]) .ms-vtb.is-stuck {
    border-bottom-color: rgba(0, 0, 0, .08);
}
.ms-vtb-back {
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    border: 1px solid var(--brand-border, #E4DDCE);
    background: var(--brand-surface, #FFFFFF);
    color: var(--brand-primary, #1B2746);
    box-shadow: 0 1px 2px rgba(36, 48, 74, .06);
    text-decoration: none;
    -webkit-tap-highlight-color: transparent;
    transition: transform .16s ease, background .16s ease;
}
.ms-vtb-back svg { width: 16px; height: 16px; }
.ms-vtb-back:hover { background: var(--brand-soft, #F5F5F7); }
.ms-vtb-back:active { transform: scale(.92); }
.ms-vtb-back:focus-visible {
    outline: 2px solid var(--brand-primary, #1B2746);
    outline-offset: 2px;
}
.ms-vtb-copy { min-width: 0; display: grid; gap: 3px; }
.ms-vtb-crumbs {
    font-size: .78rem;
    font-weight: 600;
    color: var(--brand-muted, #8A93A7);
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ms-vtb-crumbs a { color: inherit; text-decoration: none; transition: color .15s; }
.ms-vtb-crumbs a:hover { color: var(--brand-text, #1B2746); }
.ms-vtb-sep { margin: 0 4px; opacity: .6; }
.ms-vtb-crumbs strong { color: var(--brand-primary, #1B2746); font-weight: 700; }

@media (max-width: 768px) {
    .ms-vtb { margin: 6px 12px 4px; gap: 8px; }
    .ms-vtb--nested { margin: 0 0 6px; }
    .ms-vtb--nested.ms-vtb--pad { margin: 6px 12px 4px; }

    /* Icono puro (sin chip) para una barra más limpia. La caja conserva ~36px
       de área de toque, pero transparente: se ve solo el icono. */
    .ms-vtb-back,
    html[data-tema="cupertino"] .ms-vtb-back {
        width: 36px;
        height: 36px;
        margin-left: -7px;          /* alinea el icono al borde óptico del texto */
        border: 0;
        background: transparent;
        box-shadow: none;
        border-radius: 10px;        /* solo para el halo de foco/tap */
        color: var(--brand-primary, #1B2746);
    }
    .ms-vtb-back svg { width: 20px; height: 20px; }
    .ms-vtb-back:hover { background: transparent; }
    .ms-vtb-back:active { background: color-mix(in srgb, var(--brand-primary, #1B2746) 9%, transparent); }
    .ms-vtb-crumbs { font-size: .82rem; }

    /* Pegada al hacer scroll: cristal limpio, hairline sutil, sin sombra dura */
    .ms-vtb.is-stuck {
        margin-top: 0;
        background: rgba(255, 255, 255, .70);
        -webkit-backdrop-filter: saturate(180%) blur(16px);
        backdrop-filter: saturate(180%) blur(16px);
        box-shadow: none;
        border-bottom: 1px solid color-mix(in srgb, var(--brand-border, #E4DDCE) 55%, transparent);
    }
    html[data-theme="dark"] .ms-vtb.is-stuck {
        background: rgba(22, 24, 30, .66);
        box-shadow: none;
        border-bottom-color: rgba(255, 255, 255, .08);
    }
}

/* Modo oscuro: los tokens neutrales ya voltean; el énfasis usa tinta clara
   (el primario del hotel puede perder contraste sobre lienzo oscuro). */
html[data-theme="dark"] .ms-vtb-back { box-shadow: none; } /* chip delineado: mismo surface que la barra + borde */
html[data-theme="dark"] .ms-vtb-crumbs strong { color: var(--brand-text, #EFE9DC); }

/* Cupertino: chip circular de vidrio, misma receta que la flecha Apple. */
html[data-tema="cupertino"] .ms-vtb-back {
    border-radius: 999px;
    border-color: rgba(0, 0, 0, .06);
    box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
}
html[data-theme="dark"][data-tema="cupertino"] .ms-vtb-back {
    border-color: rgba(255, 255, 255, .12);
}

@media print {
    .ms-vtb { display: none !important; }
}

/* ── Reemplazo: con la barra global presente, las migas y flechas de
   regreso locales de las vistas se retiran (la barra las sustituye).
   Estas reglas solo existen en páginas donde la barra se pinta. ── */
.hdv-crumb,
.hcal-crumb,
.guest-breadcrumb,
.s-crumb,
.ge-breadcrumb,
.gc-breadcrumb,
.create-room-breadcrumb,
.cut-backbar,
nav[aria-label="breadcrumb"],
.ms-vtb-legacy,
.ms-back,
.ms-back-legacy {
    display: none !important;
}
</style>
<script>
/* Anida la barra dentro del contenedor raíz de la vista para que herede su
   fondo exacto (lienzo, gradiente o color propio) y se vea "pareja" sin
   franja ni línea EN REPOSO. Corre en DOMContentLoaded (la vista ya está en el
   DOM, el skeleton #psk sigue cubriendo hasta 'load', así que no hay salto).
   Además deja la barra FIJA arriba (position:sticky) y activa el vidrio
   esmerilado solo cuando se pega, vía la clase .is-stuck. */
(function() {
    var bar = null;

    /* ¿Anidarse en `n` rompería el sticky? El elemento pegajoso se posiciona
       respecto a su bloque contenedor; si un ancestro (hasta main-content)
       recorta el overflow o crea un nuevo contexto (transform/filter/
       perspective/contain/will-change) y NO es el que scrollea, el sticky se
       cancela. En ese caso la barra se queda a nivel de main-content, donde el
       sticky siempre funciona. */
    var stickySeguro = function(nodo) {
        var el = nodo;
        while (el && el !== document.body && !el.classList.contains('main-content')) {
            var s = getComputedStyle(el);
            if (s.overflowX !== 'visible' || s.overflowY !== 'visible') { return false; }
            if (s.transform !== 'none' || s.perspective !== 'none' || s.filter !== 'none') { return false; }
            if (/transform|perspective|filter/.test(s.willChange || '')) { return false; }
            if (/paint|layout|strict|content/.test(s.contain || '')) { return false; }
            el = el.parentElement;
        }
        return true;
    };

    /* Sangría para que la línea de vidrio, al fijarse, llegue de borde a borde
       del contenedor SIN mover la flecha. Dos piezas:
         · margen negativo = padding del contenedor (lleva el vidrio al borde).
         · padding = padding del contenedor + margen de reposo de la barra
           (reañade exactamente el hueco que tenía la flecha).
       Solo se mide en reposo: estando pegada, los márgenes de la barra ya
       están sobrescritos y mentirían. */
    var recalcularBleed = function() {
        if (!bar || bar.classList.contains('is-stuck')) { return; }
        var p = getComputedStyle(bar.parentElement);
        var b = getComputedStyle(bar);
        bar.style.setProperty('--vtb-mar-l', p.paddingLeft);
        bar.style.setProperty('--vtb-mar-r', p.paddingRight);
        bar.style.setProperty('--vtb-pad-l', 'calc(' + p.paddingLeft + ' + ' + b.marginLeft + ')');
        bar.style.setProperty('--vtb-pad-r', 'calc(' + p.paddingRight + ' + ' + b.marginRight + ')');
    };

    var anidar = function() {
        bar = document.querySelector('.ms-vtb');
        if (!bar || bar.classList.contains('ms-vtb--nested')) { return; }

        /* Tipografía blindada: la barra usa la fuente del body (tema activo),
           nunca la del contenedor de la vista en el que se anide. Inline con
           !important porque algunas vistas imponen su fuente con reglas
           universales !important (p. ej. .habitaciones-view *). */
        var fuente = getComputedStyle(document.body).fontFamily;
        bar.style.setProperty('font-family', fuente, 'important');
        bar.querySelectorAll('.ms-vtb-crumbs, .ms-vtb-crumbs *').forEach(function(el) {
            el.style.setProperty('font-family', fuente, 'important');
        });

        var omitir = { STYLE: 1, SCRIPT: 1, LINK: 1, TEMPLATE: 1, NOSCRIPT: 1 };
        var admitidos = { DIV: 1, SECTION: 1, MAIN: 1, ARTICLE: 1, FORM: 1 };
        var n = bar.nextElementSibling;

        while (n && (omitir[n.tagName] || n.id === 'psk' || getComputedStyle(n).display === 'none')) {
            n = n.nextElementSibling;
        }

        var puedeAnidar = !!n && admitidos[n.tagName];
        if (puedeAnidar) {
            var cs = getComputedStyle(n);
            var display = cs.display;
            var esBloque = display === 'block' || display === 'flow-root';
            var esFlexColumna = display === 'flex' && cs.flexDirection.indexOf('column') === 0;
            if (!esBloque && !esFlexColumna) { puedeAnidar = false; }
            else if (cs.position === 'fixed' || cs.position === 'absolute') { puedeAnidar = false; }
            /* Contenedores centrados (max-width + margin auto): anidarse ahí
               arrastraría la flecha al centro en pantallas anchas. */
            else if (n.getBoundingClientRect().left - bar.parentElement.getBoundingClientRect().left > 40) { puedeAnidar = false; }
            /* Contenedores que romperían el sticky: mejor a nivel de main. */
            else if (!stickySeguro(n)) { puedeAnidar = false; }

            if (puedeAnidar) {
                n.insertBefore(bar, n.firstChild);
                bar.classList.add('ms-vtb--nested');
                if (parseFloat(cs.paddingLeft) < 10) {
                    bar.classList.add('ms-vtb--pad');
                }
            }
        }

        recalcularBleed();
        montarDeteccion();
    };

    /* ── Detector de "pegada arriba" ──
       La barra es sticky top:0, así que se pega cuando el scroll supera su
       posición natural de reposo (restOffset). Medimos ese offset una vez (y
       en resize, solo si NO está pegada, porque estando pegada su rect miente).
       Listener de scroll con rAF: determinista y sin depender de IO. */
    var scroller = (function() {
        var mc = document.querySelector('.main-content');
        if (mc && /auto|scroll|overlay/i.test(getComputedStyle(mc).overflowY)) { return mc; }
        return null; // scroll del documento
    })();
    var esDoc = !scroller;
    var restOffset = 0;
    var ticking = false;

    var refTop = function() {
        return esDoc ? 0 : scroller.getBoundingClientRect().top;
    };
    var scrollTop = function() {
        return esDoc ? (window.pageYOffset || document.documentElement.scrollTop || 0) : scroller.scrollTop;
    };
    var medirRest = function() {
        /* El rect solo es fiable con el scroll en el tope: position:sticky clava
           la barra arriba aunque la clase is-stuck no esté puesta, así que medir
           con scroll > 0 daría un offset falso. */
        if (!bar || scrollTop() > 1) { return; }
        restOffset = bar.getBoundingClientRect().top - refTop() + scrollTop();
    };
    var actualizar = function() {
        ticking = false;
        if (!bar) { return; }
        bar.classList.toggle('is-stuck', scrollTop() > restOffset + 0.5);
    };
    var onScroll = function() {
        if (!ticking) { ticking = true; window.requestAnimationFrame(actualizar); }
    };

    var montarDeteccion = function() {
        if (!bar || bar.__vtbObs) { return; }
        bar.__vtbObs = true;
        medirRest();
        (esDoc ? window : scroller).addEventListener('scroll', onScroll, { passive: true });
        actualizar();
    };

    window.addEventListener('resize', function() {
        recalcularBleed();
        medirRest();
        actualizar();
    }, { passive: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', anidar);
    } else {
        anidar();
    }
})();
</script>
