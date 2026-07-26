<?php

require_once __DIR__ . '/../bootstrap.php';

echo "ReportesIndividualesGatesTest\n";

$root = dirname(__DIR__, 2);
$controller = (string) file_get_contents($root . '/app/controllers/ReportesController.php');
$helpers = (string) file_get_contents($root . '/app/helpers/modulos.php');
$sidebar = (string) file_get_contents($root . '/app/views/layout/sidebar.php');
$navegacion = (string) file_get_contents($root . '/config/navegacion.php');
$helperNav = (string) file_get_contents($root . '/app/helpers/navegacion.php');
$footerNav = (string) file_get_contents($root . '/app/helpers/footer_nav.php');
$vistaIndex = (string) file_get_contents($root . '/app/views/reportes/index.php');
$hotelDetalle = (string) file_get_contents($root . '/app/views/admin/saas/hotel_detalle.php');
$migracion = (string) file_get_contents(dirname($root) . '/migrations/20260724_001_clasificacion_comercial_modulos.sql');

// ── Mapa central pantalla -> modulo ──
t_ok(strpos($helpers, "function hotel_report_screen_modules") !== false, 'mapa central de reportes existe');
t_ok(strpos($helpers, "'ranking-estados'          => ['reporte_procedencia']") !== false,
    'ranking y comparativa pertenecen a reporte_procedencia (sin producto propio)');
t_ok(strpos($helpers, "'mantenimiento'            => ['mantenimiento']") !== false,
    'reporte de mantenimiento incluido con el modulo mantenimiento');
t_ok(strpos($helpers, "'ejecutivo'                => ['tablero_ejecutivo']") !== false,
    'tablero ejecutivo pertenece a tablero_ejecutivo');

// ── Gates de servidor por accion ──
t_ok(strpos($controller, 'require_hotel_reports_center();') !== false,
    'el centro exige >=1 reporte permitido');
t_ok(strpos($controller, "require_hotel_module('reportes')") === false,
    'el contenedor interno reportes ya no gatea el centro');
foreach ([
    "require_hotel_report('ingresos-gastos')",
    "require_hotel_report('procedencia')",
    "require_hotel_report('habitaciones-rentables')",
    "require_hotel_report('ocupacion')",
    "require_hotel_report('estancia')",
    "require_hotel_report('ranking-estados')",
    "require_hotel_report('limpieza')",
] as $gate) {
    t_ok(strpos($controller, $gate) !== false, 'gate presente: ' . $gate);
}
t_ok(strpos($controller, "require_hotel_module('exportaciones')") !== false,
    'exportar sigue exigiendo el bloque exportaciones');
t_ok(substr_count($controller, '$pantallaPorTipo') >= 4,
    'exportarPdf y datosGrafica mapean tipo -> reporte contratado');
t_ok(strpos($controller, "'ingresos-gastos-usuario' => 'ingresos-gastos'") !== false,
    'variantes de export de ingresos exigen el reporte de ingresos');

// ── Visibilidad de menu ──
t_ok(strpos($sidebar, 'hotel_reports_center_available') !== false,
    'sidebar muestra Reportes solo con >=1 reporte permitido');
t_ok(strpos($navegacion, "'modulo' => ['reporte_ingresos_egresos'") !== false,
    'catalogo de navegacion usa any-of de modulos de reporte');
t_ok(strpos($footerNav, "'reporte_ingresos_egresos'") !== false,
    'atajos moviles usan any-of de modulos de reporte');
t_ok(strpos($vistaIndex, '$repTieneIngresos') !== false
    && strpos($vistaIndex, '$repTieneProcedencia') !== false
    && strpos($vistaIndex, '$repTieneOcupacion') !== false
    && strpos($vistaIndex, '$repTieneEstancia') !== false
    && strpos($vistaIndex, '$repTieneRentables') !== false,
    'cada tarjeta del centro se muestra solo si el reporte esta contratado');

// ── Configuracion solo equipo Medisoft ──
t_ok(strpos($navegacion, "'solo_medisoft' => true") !== false,
    'entrada Configuracion marcada solo_medisoft en el catalogo de navegacion');
t_ok(strpos($helperNav, "solo_medisoft") !== false && strpos($helperNav, 'isSaasAdmin') !== false,
    'nav_pantalla_visible oculta pantallas solo_medisoft a usuarios de hotel');
t_ok(strpos($sidebar, 'isSaasAdmin()') !== false,
    'sidebar oculta Configuracion a usuarios que no son del equipo Medisoft');

// ── Panel SaaS (fase minima) ──
t_ok(strpos($hotelDetalle, "=== 'interno'") !== false,
    'panel separa internos de la seleccion de contratacion');
t_ok(strpos($hotelDetalle, 'motivo_bloqueo') !== false,
    'panel muestra el motivo del bloqueo');
t_ok(strpos($hotelDetalle, 'No disponible aún') !== false,
    'panel conserva el estado No disponible aun');
// Fase visual (2026-07-24): los bloqueados ya no llevan checkbox; el estimado
// recorre solo toggles habilitados y suma los marcados (misma proteccion).
t_ok(strpos($hotelDetalle, ".modulo-toggle:not(:disabled)") !== false
    && strpos($hotelDetalle, 'toggle.checked') !== false,
    'el estimado JS del cobro solo suma bloques habilitados y marcados');

// ── Migracion correctiva y de clasificacion ──
t_ok(strpos($migracion, "'tareas'") !== false && strpos($migracion, "'reportes_distribucion'") !== false,
    'migracion re-registra tareas y reportes_distribucion como internos trazables');
t_ok(strpos($migracion, 'mantenimiento_plus') === false,
    'migracion NO recrea mantenimiento_plus (ya fusionado)');
t_ok(strpos($migracion, 'ON DUPLICATE KEY UPDATE') !== false
    && strpos($migracion, 'information_schema.COLUMNS') !== false,
    'migracion idempotente (columnas y filas re-ejecutables)');
// Evaluar solo el SQL EFECTIVO: la reversion documentada en comentarios `--`
// menciona DROP/DELETE a proposito y no debe contar como sentencia.
$migracionEfectiva = implode("\n", array_filter(
    array_map('trim', explode("\n", $migracion)),
    static function ($linea) {
        return $linea !== '' && strpos($linea, '--') !== 0;
    }
));
t_ok(strpos($migracionEfectiva, 'DELETE FROM modulos') === false
    && strpos($migracionEfectiva, 'DELETE FROM hotel_modulos') === false
    && strpos($migracionEfectiva, 'DELETE FROM plan_modulos') === false
    && strpos($migracionEfectiva, 'DROP ') === false,
    'migracion no borra modulos, contrataciones ni presets');
t_ok(strpos($migracion, "SET pm.incluido = 0") !== false,
    'el modulo general reportes sale del Basico por incluido=0 (sin DELETE)');

t_fin();
