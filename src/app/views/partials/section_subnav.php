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
        'habitaciones' => ['url' => url('habitaciones'), 'icono' => 'fa-bed',              'label' => 'Habitaciones'],
        'areas'        => ['url' => url('areas'),        'icono' => 'fa-map-location-dot', 'label' => 'Áreas'],
    ];
    $subnavAria = 'Secciones de Habitaciones y áreas';
} else {
    // Nota: la pestaña "Pre-nómina" se retiró de aquí a proposito. El calculo y
    // cierre de periodos vive ahora en el modulo Nomina (sidebar > Nomina). La
    // superficie vieja (trabajadores/nomina/*) sigue existiendo como pantalla de
    // pago a la que el modulo Nomina enlaza, pero ya no se anuncia como puerta
    // duplicada en Personal. El key 'prenomina' se conserva tolerado abajo.
    $subnavTabs = [
        'equipo'    => ['url' => url('trabajadores'),                   'icono' => 'fa-users',          'label' => 'Equipo'],
        'pagos'     => ['url' => url('trabajadores/pagos-caja/reporte'),'icono' => 'fa-cash-register',  'label' => 'Pagos'],
        'informes'  => ['url' => url('trabajadores/informes'),          'icono' => 'fa-chart-pie',      'label' => 'Informes'],
    ];
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
    --msnav-card: color-mix(in srgb, var(--brand-surface, #F6F2EA) 55%, #ffffff);
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
@media (max-width: 768px) {
    .ms-subnav { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding-bottom: 4px; }
    .ms-subnav::-webkit-scrollbar { display: none; }
}
</style>
<?php endif; ?>
<nav class="ms-subnav" aria-label="<?= htmlspecialchars($subnavAria) ?>">
    <?php foreach ($subnavTabs as $subnavKey => $subnavTab): $subnavEsActiva = ($subnavKey === $subnavActive); ?>
    <a href="<?= $subnavTab['url'] ?>"
       class="ms-subnav-tab ms-pressable <?= $subnavEsActiva ? 'is-active' : '' ?>"
       <?= $subnavEsActiva ? 'aria-current="page"' : '' ?>>
        <i class="fas <?= $subnavTab['icono'] ?>"></i> <?= $subnavTab['label'] ?>
    </a>
    <?php endforeach; ?>
</nav>
<?php unset($subnav_section, $subnav_active, $subnavSection, $subnavActive, $subnavTabs, $subnavAria, $subnavPuedeConfigurar, $subnavKey, $subnavTab, $subnavEsActiva); ?>
