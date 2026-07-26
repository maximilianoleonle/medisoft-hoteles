<?php
/**
 * Contrato comercial de mantenimiento:
 * - el reporte general pertenece a Reportes;
 * - programados y bloques por activo pertenecen al mismo modulo mantenimiento;
 * - limpieza permanece independiente.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "MantenimientoReporteNivelesTest\n";

$controller = (string)file_get_contents(APP_PATH . '/controllers/ReportesController.php');
$centro = (string)file_get_contents(APP_PATH . '/views/reportes/index.php');
$vista = (string)file_get_contents(APP_PATH . '/views/reportes/mantenimiento.php');
$mantenimientoController = (string)file_get_contents(APP_PATH . '/controllers/MantenimientoController.php');
$navegacion = (string)file_get_contents(ROOT_PATH . '/config/navegacion.php');
$preflightReporte = (string)file_get_contents(ROOT_PATH . '/tools/saas/preflight_reporte_mantenimiento.php');
$preflightOperativo = (string)file_get_contents(ROOT_PATH . '/tools/saas/preflight_mantenimiento_operativo.php');
$migracionUnificacion = (string)file_get_contents(dirname(ROOT_PATH) . '/migrations/20260722_001_unificar_modulo_mantenimiento.sql');

function t_cuerpo_mantenimiento_niveles(string $codigo, string $accion): string
{
    $inicio = strpos($codigo, 'function ' . $accion . '(');
    if ($inicio === false) {
        return '';
    }
    $siguiente = strpos($codigo, 'function ', $inicio + strlen('function ' . $accion . '('));
    return substr($codigo, $inicio, $siguiente === false ? null : $siguiente - $inicio);
}

$mantenimiento = t_cuerpo_mantenimiento_niveles($controller, 'mantenimientoAction');
$programado = t_cuerpo_mantenimiento_niveles($controller, 'mantenimientoProgramadoAction');
$limpieza = t_cuerpo_mantenimiento_niveles($controller, 'limpiezaAction');

t_ok($mantenimiento !== '', 'accion del reporte general existe');
t_ok(strpos($mantenimiento, "require_hotel_module('mantenimiento_plus')") === false, 'reporte general no conserva gate Plus');
t_ok(strpos($mantenimiento, "require_hotel_module('mantenimiento')") !== false, 'reporte general exige el modulo unico Mantenimiento');
// Fase clasificacion comercial (2026-07-24): el modulo `reportes` paso a
// contenedor interno; el centro ahora exige >=1 reporte permitido.
t_ok(strpos($controller, 'require_hotel_reports_center();') !== false, 'ReportesController gatea el centro por >=1 reporte permitido');

t_ok($programado !== '', 'accion de mantenimiento programado existe');
t_ok(strpos($programado, "require_hotel_module('mantenimiento')") !== false, 'mantenimiento programado exige el modulo unico en servidor');
t_ok(strpos($programado, "require_hotel_module('mantenimiento_plus')") === false, 'mantenimiento programado no conserva gate Plus');

t_ok(strpos($centro, "url('reportes/mantenimiento')") !== false, 'Centro conserva el acceso al reporte general');
t_ok(strpos($centro, '<?php if ($repTieneMantenimiento): ?>') !== false, 'acceso a programados esta condicionado por Mantenimiento');
t_ok(strpos($centro, "url('reportes/mantenimiento-programado')") !== false, 'Centro conserva el enlace avanzado dentro de su condicion');
// Fase clasificacion comercial (2026-07-24): el contador de areas suma cada
// reporte individual contratado; Mantenimiento sigue siendo un sumando condicionado.
t_ok(strpos($centro, "(\$repTieneMantenimiento ? 1 : 0)") !== false, 'contador incluye Mantenimiento solo cuando esta contratado');

t_ok(substr_count($vista, "current_hotel_has_module('mantenimiento')") >= 1, 'vista protege activos y costos con el modulo unico');
t_ok(strpos($vista, 'Mantenimiento Plus') === false, 'vista ya no presenta un producto Plus');
t_ok(strpos($vista, 'obtenerCostosMantenimientoPorActivo') === false, 'vista no ejecuta consultas de costos directamente');

t_ok($limpieza !== '', 'accion de limpieza sigue disponible');
t_ok(strpos($limpieza, "require_hotel_module('mantenimiento')") === false, 'limpieza permanece independiente de Mantenimiento');

t_ok(strpos($preflightReporte, 'reporte basico') !== false, 'preflight documenta el nivel basico');
t_ok(strpos($preflightOperativo, "require_hotel_module('mantenimiento')") !== false, 'preflight operativo vigila el gate del modulo unico');

t_ok(strpos($mantenimientoController, "require_hotel_module('mantenimiento')") !== false, 'activos, evidencias y costos exigen Mantenimiento');
t_ok(strpos($mantenimientoController, "require_hotel_module('mantenimiento_plus')") === false, 'controlador avanzado no conserva el gate Plus');
t_ok(strpos($navegacion, "'modulo' => 'mantenimiento'") !== false, 'navegacion publica activos bajo Mantenimiento');
t_ok(strpos($navegacion, "'modulo' => 'mantenimiento_plus'") === false, 'navegacion no publica Mantenimiento Plus');
t_ok(strpos($migracionUnificacion, "DELETE FROM modulos") !== false, 'migracion retira el catalogo duplicado');
t_ok(strpos($migracionUnificacion, "precio_override, enabled_by") !== false && strpos($migracionUnificacion, "       NULL,") !== false, 'migracion no suma ni copia el precio Plus');

t_fin();
