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
    || in_array($vtbRoot, ['dashboard', 'inicio', 'admin', 'api', 'login', 'logout'], true)
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
    'lealtad'        => 'Huésped frecuente',
    'forecast'       => 'Forecast',
    'night-audit'    => 'Night Audit',
    'auditoria'      => 'Bitácora',
    'correccion'     => 'Corrección',
    'operacion'      => 'Operación',
    'offline'        => 'Offline',
    'navegacion'     => 'Navegación',
    'ia'             => 'IA ejecutiva',
    'reputacion'     => 'Reputación',
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
        <div class="ms-vtb-hotel"><?= htmlspecialchars($vtbHotel, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</nav>
<style>
/* ── Barra de navegación global (partials/view_topbar.php) ──
   Transparente y SIN línea: se funde con el fondo de la vista. Un script
   (abajo) la anida dentro del contenedor raíz de cada vista para que
   herede su fondo exacto (decisión del owner: "parejo, sin separación"). */
.ms-vtb {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 14px 24px 10px;
    padding: 0;
    background: transparent;
    border: 0;
}
/* Anidada en un contenedor con padding propio: sin doble sangría */
.ms-vtb--nested { margin: 0 0 12px; }
/* Anidada en un contenedor SIN padding: conserva su aire */
.ms-vtb--nested.ms-vtb--pad { margin: 14px 24px 10px; }
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
.ms-vtb-back:hover { background: var(--brand-soft, #F6F2EA); }
.ms-vtb-back:active { transform: scale(.92); }
.ms-vtb-back:focus-visible {
    outline: 2px solid var(--brand-primary, #1B2746);
    outline-offset: 2px;
}
.ms-vtb-copy { min-width: 0; display: grid; gap: 3px; }
.ms-vtb-crumbs {
    font-size: .78rem;
    font-weight: 700;
    color: var(--brand-muted, #8A93A7);
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ms-vtb-crumbs a { color: inherit; text-decoration: none; transition: color .15s; }
.ms-vtb-crumbs a:hover { color: var(--brand-text, #1B2746); }
.ms-vtb-sep { margin: 0 4px; opacity: .6; }
.ms-vtb-crumbs strong { color: var(--brand-primary, #1B2746); font-weight: 800; }
.ms-vtb-hotel {
    font-size: .72rem;
    font-weight: 800;
    color: var(--brand-muted, #8A93A7);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .ms-vtb { margin: 12px 14px 8px; }
    .ms-vtb--nested { margin: 0 0 10px; }
    .ms-vtb--nested.ms-vtb--pad { margin: 12px 14px 8px; }
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
   franja ni línea. Corre en DOMContentLoaded (la vista ya está en el DOM,
   el skeleton #psk sigue cubriendo hasta 'load', así que no hay salto).
   Guardas: solo contenedores visibles de bloque o flex-columna (DIV/SECTION/
   MAIN/ARTICLE/FORM); si no hay candidato seguro, la barra se queda donde
   está (transparente sobre el lienzo). */
(function() {
    var anidar = function() {
        var bar = document.querySelector('.ms-vtb');
        if (!bar || bar.classList.contains('ms-vtb--nested')) { return; }

        var omitir = { STYLE: 1, SCRIPT: 1, LINK: 1, TEMPLATE: 1, NOSCRIPT: 1 };
        var admitidos = { DIV: 1, SECTION: 1, MAIN: 1, ARTICLE: 1, FORM: 1 };
        var n = bar.nextElementSibling;

        while (n && (omitir[n.tagName] || n.id === 'psk' || getComputedStyle(n).display === 'none')) {
            n = n.nextElementSibling;
        }
        if (!n || !admitidos[n.tagName]) { return; }

        var cs = getComputedStyle(n);
        var display = cs.display;
        var esBloque = display === 'block' || display === 'flow-root';
        var esFlexColumna = display === 'flex' && cs.flexDirection.indexOf('column') === 0;
        if (!esBloque && !esFlexColumna) { return; }
        if (cs.position === 'fixed' || cs.position === 'absolute') { return; }

        n.insertBefore(bar, n.firstChild);
        bar.classList.add('ms-vtb--nested');
        if (parseFloat(cs.paddingLeft) < 10) {
            bar.classList.add('ms-vtb--pad');
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', anidar);
    } else {
        anidar();
    }
})();
</script>
