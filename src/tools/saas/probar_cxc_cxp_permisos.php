<?php
/**
 * Prueba de regresion del re-mapeo de permisos de CxC/CxP (auditoria Fable
 * jul-2026). Verifica:
 *
 *  A) Catalogo (config/permisos.php): expone cuentas_por_cobrar.cobrar y
 *     cuentas_por_pagar.pagar (toggles del editor de roles).
 *  B) Presets: administrador conserva escritura (.cobrar/.pagar), gerente por
 *     .all; recepcionista sin CxC/CxP (configurable, no rompe: hoy no tiene).
 *  C) Resolver permission_in_list: .all concede la accion; .view sola NO;
 *     '*' concede todo.
 *  D) Backfill idempotente (migracion 20260710_001): re-aplicar las UPDATE
 *     dentro de una transaccion NO crece los arreglos permisos_json.
 *
 * La parte D corre dentro de una transaccion que SE REVIERTE.
 * Uso: php src/tools/saas/probar_cxc_cxp_permisos.php
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

echo "== Prueba re-mapeo de permisos CxC / CxP ==\n\n";

/* -------- A) Catalogo -------- */
$cfg = require CONFIG_PATH . '/permisos.php';
$catCxc = $cfg['catalogo']['cuentas_por_cobrar']['permisos'] ?? [];
$catCxp = $cfg['catalogo']['cuentas_por_pagar']['permisos'] ?? [];
check('A catalogo expone cuentas_por_cobrar.cobrar', isset($catCxc['cuentas_por_cobrar.cobrar']));
check('A catalogo expone cuentas_por_pagar.pagar', isset($catCxp['cuentas_por_pagar.pagar']));

/* -------- B) Presets -------- */
$admin = $cfg['presets']['administrador']['permisos'] ?? [];
check('B administrador conserva escritura CxC (.cobrar)', in_array('cuentas_por_cobrar.cobrar', $admin, true));
check('B administrador conserva escritura CxP (.pagar)', in_array('cuentas_por_pagar.pagar', $admin, true));

$gerente = $cfg['presets']['gerente']['permisos'] ?? [];
check('B gerente tiene control total CxC (.all)', in_array('cuentas_por_cobrar.all', $gerente, true));

$recep = $cfg['presets']['recepcionista']['permisos'] ?? [];
$recepSinCuentas = !array_filter($recep, static function ($p) {
    return strpos($p, 'cuentas_por_cobrar.') === 0 || strpos($p, 'cuentas_por_pagar.') === 0;
});
check('B recepcionista sin CxC/CxP (configurable, no rompe: hoy no tiene)', $recepSinCuentas);

/* -------- C) Resolver permission_in_list -------- */
check('C cuentas_por_cobrar.all concede .cobrar (wildcard)', permission_in_list('cuentas_por_cobrar.cobrar', ['cuentas_por_cobrar.all']));
check('C cuentas_por_pagar.all concede .pagar (wildcard)', permission_in_list('cuentas_por_pagar.pagar', ['cuentas_por_pagar.all']));
check('C solo .view NO concede .cobrar (lectura no cobra)', !permission_in_list('cuentas_por_cobrar.cobrar', ['cuentas_por_cobrar.view']));
check('C comodin * concede .pagar', permission_in_list('cuentas_por_pagar.pagar', ['*']));

/* -------- D) Idempotencia del backfill (rollback) -------- */
$db = Database::getInstance();
$pdo = $db->getConnection();

$sqlFirma = "SELECT COALESCE(SUM(
        JSON_CONTAINS(permisos_json,'\"cuentas_por_cobrar.cobrar\"')
      + JSON_CONTAINS(permisos_json,'\"cuentas_por_pagar.pagar\"')
    ), 0) AS total,
    COALESCE(SUM(JSON_LENGTH(permisos_json)), 0) AS long_total
    FROM roles";
$antes = $pdo->query($sqlFirma)->fetch(PDO::FETCH_ASSOC);

$updates = [
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','cuentas_por_cobrar.cobrar')
       WHERE JSON_CONTAINS(permisos_json,'\"cuentas_por_cobrar.view\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"cuentas_por_cobrar.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"cuentas_por_cobrar.cobrar\"')",
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','cuentas_por_pagar.pagar')
       WHERE JSON_CONTAINS(permisos_json,'\"cuentas_por_pagar.view\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"cuentas_por_pagar.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"cuentas_por_pagar.pagar\"')",
];

$db->safeBeginTransaction();
try {
    $filasAfectadas = 0;
    foreach ($updates as $sql) {
        $filasAfectadas += $pdo->exec($sql);
    }
    $despues = $pdo->query($sqlFirma)->fetch(PDO::FETCH_ASSOC);

    check('D re-aplicar el backfill NO afecta filas (idempotente)', (int) $filasAfectadas === 0);
    check('D conteo de acciones CxC/CxP estable', (int) $antes['total'] === (int) $despues['total']);
    check('D longitud total de permisos_json estable', (int) $antes['long_total'] === (int) $despues['long_total']);

    throw new Exception('__ROLLBACK_OK__');
} catch (Throwable $e) {
    $db->safeRollBack();
    if ($e->getMessage() !== '__ROLLBACK_OK__') {
        echo "\n  ERROR de la prueba: " . $e->getMessage() . "\n";
        $fallos++;
    }
}

echo "\n== Resumen == PASS: {$ok}  FAIL: {$fallos}\n";
exit($fallos > 0 ? 1 : 0);
