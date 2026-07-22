<?php
/**
 * Prueba de regresion de la division Compras/Proveedores vs Inventario
 * (decision jul-2026: division ratificada + permiso compras.recibir separado).
 * Verifica:
 *
 *  A) Catalogo (config/permisos.php): expone compras.recibir (accion) y las
 *     secciones compras/proveedores/cuentas_por_pagar cuelgan del modulo
 *     contratable 'compras' (ya no 'inventario'/null); cuentas_por_cobrar
 *     cuelga de 'cuentas_cobrar'. La pantalla de Roles bloquea secciones por
 *     este campo: si deriva, un hotel con el modulo activo no podria asignar
 *     permisos.
 *  B) Presets: gerente y administrador conservan compras.all (que concede
 *     recibir via wildcard); recepcionista sin permisos compras.*.
 *  C) Resolver permission_in_list: compras.all concede compras.recibir;
 *     compras.view sola NO recibe; '*' concede todo.
 *  D) Gates de codigo: CompraController exige modulo 'compras' +
 *     compras.view de base, compras.recibir en recibirAction y compras.all
 *     en editar/actualizar/cancelar; la vista de listado solo pinta el boton
 *     Recibir con can('compras.recibir').
 *  E) Alineacion catalogo <-> navegacion: el modulo de cada seccion del
 *     catalogo coincide con el declarado en config/navegacion.php.
 *
 * Solo lectura. Uso: php src/tools/saas/probar_compras_permisos.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); echo "Solo CLI.\n"; exit(1); }

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Database.php';
require_once APP_PATH . '/helpers/auth.php';

$fallos = 0;
$ok = 0;
function check($nombre, $cond) {
    global $ok, $fallos;
    if ($cond) { $ok++; echo "  PASS  $nombre\n"; }
    else { $fallos++; echo "  FAIL  $nombre\n"; }
}

echo "== Prueba division Compras vs Inventario (permisos y modulos) ==\n\n";

/* -------- A) Catalogo -------- */
$cfg = require CONFIG_PATH . '/permisos.php';
$catalogo = $cfg['catalogo'] ?? [];

$catCompras = $catalogo['compras']['permisos'] ?? [];
check('A catalogo expone compras.recibir', isset($catCompras['compras.recibir']));
check('A compras.recibir es tipo accion', ($catCompras['compras.recibir']['tipo'] ?? '') === 'accion');
check('A seccion compras cuelga del modulo compras', ($catalogo['compras']['modulo'] ?? null) === 'compras');
check('A seccion proveedores cuelga del modulo compras', ($catalogo['proveedores']['modulo'] ?? null) === 'compras');
check('A seccion cuentas_por_pagar cuelga del modulo compras', ($catalogo['cuentas_por_pagar']['modulo'] ?? null) === 'compras');
check('A seccion cuentas_por_cobrar cuelga del modulo cuentas_cobrar', ($catalogo['cuentas_por_cobrar']['modulo'] ?? null) === 'cuentas_cobrar');
check('A seccion inventario sigue en modulo inventario', ($catalogo['inventario']['modulo'] ?? null) === 'inventario');

/* -------- B) Presets -------- */
$gerente = $cfg['presets']['gerente']['permisos'] ?? [];
$admin = $cfg['presets']['administrador']['permisos'] ?? [];
check('B gerente conserva compras.all', in_array('compras.all', $gerente, true));
check('B administrador conserva compras.all', in_array('compras.all', $admin, true));

$recep = $cfg['presets']['recepcionista']['permisos'] ?? [];
$recepSinCompras = !array_filter($recep, static function ($p) {
    return strpos($p, 'compras.') === 0;
});
check('B recepcionista sin permisos de compras', $recepSinCompras);

/* -------- C) Resolver permission_in_list -------- */
check('C compras.all concede compras.recibir (wildcard)', permission_in_list('compras.recibir', ['compras.all']));
check('C solo compras.view NO concede recibir (ver no mueve stock)', !permission_in_list('compras.recibir', ['compras.view']));
check('C compras.recibir explicito concede recibir', permission_in_list('compras.recibir', ['compras.view', 'compras.recibir']));
check('C comodin * concede compras.recibir', permission_in_list('compras.recibir', ['*']));

/* -------- D) Gates de codigo -------- */
$controllerCode = (string) @file_get_contents(APP_PATH . '/controllers/CompraController.php');
check('D CompraController exige modulo compras', strpos($controllerCode, "require_hotel_module('compras')") !== false);
check('D CompraController exige compras.view de base', strpos($controllerCode, "require_permission('compras.view')") !== false);
check('D recibirAction exige compras.recibir', strpos($controllerCode, "require_permission('compras.recibir')") !== false);
check('D editar/cancelar siguen exigiendo compras.all', substr_count($controllerCode, "require_permission('compras.all')") >= 3);

$indexView = (string) @file_get_contents(APP_PATH . '/views/compras/index.php');
check('D listado condiciona boton Recibir con can(compras.recibir)', strpos($indexView, "can('compras.recibir')") !== false);

$verView = (string) @file_get_contents(APP_PATH . '/views/compras/ver.php');
check('D detalle condiciona editar/cancelar con can(compras.all)', strpos($verView, "can('compras.all')") !== false);

/* -------- E) Alineacion catalogo <-> navegacion -------- */
$nav = require CONFIG_PATH . '/navegacion.php';
$modulosNav = [];
foreach ($nav as $item) {
    if (isset($item['ruta'], $item['modulo'])) {
        $modulosNav[$item['ruta']] = $item['modulo'];
    }
}
check('E navegacion: /compras bajo modulo compras', ($modulosNav['compras'] ?? null) === 'compras');
check('E navegacion: /proveedores bajo modulo compras', ($modulosNav['proveedores'] ?? null) === 'compras');
check('E navegacion: /cuentas-por-pagar bajo modulo compras', ($modulosNav['cuentas-por-pagar'] ?? null) === 'compras');
check('E navegacion: /cuentas-por-cobrar bajo modulo cuentas_cobrar', ($modulosNav['cuentas-por-cobrar'] ?? null) === 'cuentas_cobrar');
check('E catalogo y navegacion alineados para compras', ($catalogo['compras']['modulo'] ?? null) === ($modulosNav['compras'] ?? null));
check('E catalogo y navegacion alineados para proveedores', ($catalogo['proveedores']['modulo'] ?? null) === ($modulosNav['proveedores'] ?? null));
check('E catalogo y navegacion alineados para CxP', ($catalogo['cuentas_por_pagar']['modulo'] ?? null) === ($modulosNav['cuentas-por-pagar'] ?? null));
check('E catalogo y navegacion alineados para CxC', ($catalogo['cuentas_por_cobrar']['modulo'] ?? null) === ($modulosNav['cuentas-por-cobrar'] ?? null));

echo "\n== Resultado: {$ok} PASS, {$fallos} FAIL ==\n";
exit($fallos > 0 ? 1 : 0);
