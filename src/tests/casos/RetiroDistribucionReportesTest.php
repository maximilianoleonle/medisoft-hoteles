<?php
/**
 * Distribucion de reportes queda congelada para una futura version
 * empresarial. Exportaciones permanece como bloque comercial independiente.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "RetiroDistribucionReportesTest\n";

$centro = (string)file_get_contents(APP_PATH . '/views/reportes/index.php');
$controller = (string)file_get_contents(APP_PATH . '/controllers/ReporteLinkController.php');
$servicio = (string)file_get_contents(APP_PATH . '/services/ReporteEntregaService.php');
$modelo = (string)file_get_contents(APP_PATH . '/models/ReporteLink.php');
$routes = (string)file_get_contents(ROOT_PATH . '/config/routes.php');
$reporteController = (string)file_get_contents(APP_PATH . '/controllers/ReportesController.php');
$migracion = (string)file_get_contents(dirname(ROOT_PATH) . '/migrations/20260723_001_retirar_distribucion_reportes_comercial.sql');

t_ok(strpos($centro, '$repTieneDistribucion = false;') !== false, 'Centro oculta Distribucion de reportes');
t_ok(strpos($controller, "require_hotel_module('reportes_distribucion')") !== false, 'controlador historico permanece congelado por gate');
t_ok(strpos($servicio, "hotel_has_module('reportes_distribucion'") !== false, 'servicio deja de generar links sin el bloque');
t_ok(strpos($modelo, 'class ReporteLink') !== false, 'modelo historico se conserva');
t_ok(strpos($routes, "/reportes/link/{token:") !== false, 'ruta historica se conserva para futura reactivacion');

t_ok(strpos($reporteController, "require_hotel_module('exportaciones')") !== false, 'Exportaciones conserva su gate independiente');
t_ok(strpos($migracion, "WHERE clave = 'reportes_distribucion'") !== false, 'migracion retira solo el bloque de distribucion');
t_ok(strpos($migracion, "WHERE clave = 'exportaciones'") === false, 'migracion no retira Exportaciones');
t_ok(strpos($migracion, 'DELETE FROM reporte_links') === false, 'migracion conserva links historicos');
t_ok(strpos($migracion, 'DROP TABLE') === false, 'migracion conserva tablas y archivos');

t_fin();
