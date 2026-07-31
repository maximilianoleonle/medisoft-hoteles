<?php
/**
 * Subnav fija de sección: la misma barra, con las mismas pestañas y el mismo
 * orden, en todas las vistas de Personal y de Nómina.
 *
 * Uso (antes del include):
 *   $subnav_section = 'personal' | 'nomina' | 'habitaciones';
 *   $subnav_active  = 'equipo'|'pagos'|'informes'   (personal)
 *                     ('prenomina' se acepta pero ya no pinta pestana: superficie movida a Nomina)
 *                     'inicio'|'incidencias'|'periodos'|'empleados'|'catalogos'|'ajustes' (nomina)
 *                     'habitaciones'|'areas' (habitaciones y areas)
 *   include APP_PATH . '/views/partials/section_subnav.php';
 *
 * Solo navegación de sección: las acciones contextuales (exportar, filtros,
 * atajos con parámetros) se quedan en cada vista.
 */
$subnavSection = $subnav_section ?? 'personal';
$subnavActive = $subnav_active ?? '';

if ($subnavSection === 'nomina') {
    $subnavPuedeConfigurar = function_exists('can') ? can('nomina.configurar') : true;
    $subnavTabs = [
        'inicio'      => ['url' => url('nomina'),             'icono' => 'fa-gauge-high',     'label' => 'Inicio'],
        'incidencias' => ['url' => url('nomina/incidencias'), 'icono' => 'fa-clipboard-list', 'label' => 'Incidencias'],
        'periodos'    => ['url' => url('nomina/periodos'),    'icono' => 'fa-calendar-week',  'label' => 'Periodos'],
        'empleados'   => ['url' => url('nomina/empleados'),   'icono' => 'fa-address-book',   'label' => 'Empleados'],
        'catalogos'   => ['url' => url('nomina/catalogos'),   'icono' => 'fa-layer-group',    'label' => 'Catálogos'],
    ];
    if ($subnavPuedeConfigurar) {
        $subnavTabs['ajustes'] = ['url' => url('nomina/configuracion'), 'icono' => 'fa-sliders', 'label' => 'Ajustes'];
    }
    $subnavAria = 'Secciones de Nómina';
} elseif ($subnavSection === 'habitaciones') {
    $subnavTabs = [
        'mapa'         => ['url' => url('mapa'),         'icono' => 'fa-map',              'label' => 'Mapa'],
        'habitaciones' => ['url' => url('habitaciones'), 'icono' => 'fa-bed',              'label' => 'Habitaciones'],
        'areas'        => ['url' => url('areas'),        'icono' => 'fa-map-location-dot', 'label' => 'Áreas'],
    ];
    // Activos con preventivo (boiler, minisplit, bomba): cuelgan de una habitacion
    // o de un area, asi que su puerta natural es esta seccion. Gate EXACTO al de la
    // pantalla destino (MantenimientoController::before = modulo 'mantenimiento' +
    // 'habitaciones.view'): ni mas estricto (esconderlo a quien si puede entrar) ni
    // mas laxo (enlace a 403). Gestionar activos ya pide su propio permiso dentro.
    // OJO con la clave: el catalogo `modulos` la llama 'mantenimiento' a secas
    // (verificado en local Y en prod). 'mantenimiento_plus' NO existe en el
    // catalogo y con estos helpers -in_array estricto- nunca haria match.
    $subnavVeActivos = (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('mantenimiento'))
        && (!function_exists('can') || can('habitaciones.view'));
    if ($subnavVeActivos) {
        $subnavTabs['activos'] = ['url' => url('mantenimientos/activos'), 'icono' => 'fa-screwdriver-wrench', 'label' => 'Activos'];
    }
    $subnavAria = 'Secciones de Habitaciones y áreas';
} elseif ($subnavSection === 'lavanderia') {
    $subnavTabs = [
        'panel'   => ['url' => url('lavanderia'),           'icono' => 'fa-shirt',         'label' => 'Blancos'],
        'lotes'   => ['url' => url('lavanderia/lotes'),     'icono' => 'fa-arrows-spin',   'label' => 'Ciclos de lavado'],
        'pedidos' => ['url' => url('lavanderia/pedidos'),   'icono' => 'fa-basket-shopping', 'label' => 'Pedidos'],
    ];
    $subnavAria = 'Secciones de Lavandería';
} else {
    // Personal quedo en registro de gente + tareas: la nomina COMPLETA (calculo,
    // periodos, recibos y pagos) vive en el modulo Nomina (sidebar > Nomina).
    // Por eso ya no hay pestana "Pre-nomina" ni "Pagos" aqui. Los keys viejos
    // ('prenomina', 'pagos') se toleran abajo para no romper vistas apagadas.
    $subnavTabs = [
        'equipo'    => ['url' => url('trabajadores'),                   'icono' => 'fa-users',          'label' => 'Equipo'],
        'informes'  => ['url' => url('trabajadores/informes'),          'icono' => 'fa-chart-pie',      'label' => 'Informes'],
    ];
    if (function_exists('personal_nomina_legacy_visible') && personal_nomina_legacy_visible()) {
        $subnavTabs = [
            'equipo'   => $subnavTabs['equipo'],
            'pagos'    => ['url' => url('trabajadores/pagos-caja/reporte'), 'icono' => 'fa-cash-register', 'label' => 'Pagos'],
            'informes' => $subnavTabs['informes'],
        ];
    }
    $subnavAria = 'Secciones de Personal';
}
?>
<?php if (!defined('MS_SECTION_SUBNAV_CSS')): define('MS_SECTION_SUBNAV_CSS', true); ?>
<style>
.ms-subnav {
    --msnav-brand: var(--brand-primary, #1B2746);
    --msnav-gold: var(--brand-accent, #BD9441);
    --msnav-text: var(--brand-text, #232323);
    --msnav-muted: var(--brand-muted, #6d675e);
    --msnav-border: var(--brand-border, #e3dccd);
    --msnav-card: color-mix(in srgb, var(--brand-surface, #F5F5F7) 55%, #ffffff);
    display: flex; flex-wrap: wrap; gap: 8px;
    margin: 2px 0 18px;
}
.ms-subnav .ms-subnav-tab {
    display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid var(--msnav-border); border-radius: 12px;
    padding: 9px 14px; font-size: 13px; font-weight: 600; text-decoration: none;
    color: var(--msnav-text); background: var(--msnav-card);
    transition: border-color .15s ease, transform .12s ease;
    white-space: nowrap;
}
.ms-subnav .ms-subnav-tab i { font-size: 12px; color: var(--msnav-muted); }
.ms-subnav .ms-subnav-tab:hover { border-color: var(--msnav-gold); transform: translateY(-1px); color: var(--msnav-text); }
.ms-subnav .ms-subnav-tab.is-active { background: var(--msnav-brand); border-color: var(--msnav-brand); color: #fff; }
.ms-subnav .ms-subnav-tab.is-active i { color: rgba(255,255,255,.82); }
/* Subnav embebida en un header de marca (Habitaciones/Áreas/Mapa): con el
   activo de relleno de marca se fundía con el header y no parecía un botón.
   Aquí el seleccionado es una píldora blanca elevada, con texto e ícono de la
   marca y una barra de acento inferior: se lee claramente como botón activo
   y resalta sobre cualquier color de marca del header. */
.ms-subnav--onbrand .ms-subnav-tab { box-shadow: 0 1px 2px rgba(0,0,0,.10); }
.ms-subnav--onbrand .ms-subnav-tab.is-active {
    background: #fff; color: var(--msnav-brand); border-color: #fff; font-weight: 700;
    box-shadow: 0 8px 20px -8px rgba(0,0,0,.42), inset 0 -3px 0 var(--msnav-brand);
}
.ms-subnav--onbrand .ms-subnav-tab.is-active i { color: var(--msnav-brand); }
.ms-subnav--onbrand .ms-subnav-tab.is-active:hover { border-color: #fff; transform: none; }
@media (max-width: 768px) {
    .ms-subnav { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding-bottom: 4px; }
    .ms-subnav::-webkit-scrollbar { display: none; }
}
</style>
<?php endif; ?>
<nav class="ms-subnav<?= $subnavSection === 'habitaciones' ? ' ms-subnav--onbrand' : '' ?>" aria-label="<?= htmlspecialchars($subnavAria) ?>">
    <?php foreach ($subnavTabs as $subnavKey => $subnavTab): $subnavEsActiva = ($subnavKey === $subnavActive); ?>
    <a href="<?= $subnavTab['url'] ?>"
       class="ms-subnav-tab ms-pressable <?= $subnavEsActiva ? 'is-active' : '' ?>"
       data-prefetch
       <?= $subnavEsActiva ? 'aria-current="page"' : '' ?>>
        <i class="fas <?= $subnavTab['icono'] ?>"></i> <?= $subnavTab['label'] ?>
    </a>
    <?php endforeach; ?>
</nav>
<?php unset($subnav_section, $subnav_active, $subnavSection, $subnavActive, $subnavTabs, $subnavAria, $subnavPuedeConfigurar, $subnavVeActivos, $subnavKey, $subnavTab, $subnavEsActiva); ?>
