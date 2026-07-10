<?php
/**
 * Prueba de regresion del re-mapeo de permisos del bloque Personal
 * (auditoria Fable jul-2026). Verifica tres cosas:
 *
 *  A) Presets (config/permisos.php): gerente y administrador incluyen
 *     personal.view/gestionar/pagar (hoteles NUEVOS heredan el acceso que
 *     hoy tienen via usuarios.edit); recepcionista sigue sin personal.*.
 *  B) Resolver de permisos (permission_in_list de auth.php): el comodin
 *     personal.all concede los tres; un rol con SOLO personal.gestionar NO
 *     obtiene personal.pagar (separacion maker-checker); '*' concede todo.
 *  C) Backfill idempotente (migracion 20260709_001): re-aplicar las UPDATE
 *     dentro de una transaccion NO crece los arreglos permisos_json.
 *
 * La parte C corre dentro de una transaccion que SE REVIERTE.
 * Uso: php src/tools/saas/probar_personal_permisos.php
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

echo "== Prueba re-mapeo de permisos Personal ==\n\n";

/* -------- A) Presets del catalogo -------- */
$cfg = require CONFIG_PATH . '/permisos.php';
$presets = $cfg['presets'] ?? [];

$gerente = $presets['gerente']['permisos'] ?? [];
check('A gerente incluye personal.gestionar', in_array('personal.gestionar', $gerente, true));
check('A gerente incluye personal.pagar', in_array('personal.pagar', $gerente, true));

$admin = $presets['administrador']['permisos'] ?? [];
check('A administrador incluye personal.view', in_array('personal.view', $admin, true));
check('A administrador incluye personal.gestionar', in_array('personal.gestionar', $admin, true));
check('A administrador incluye personal.pagar', in_array('personal.pagar', $admin, true));

$recep = $presets['recepcionista']['permisos'] ?? [];
$recepSinPersonal = !array_filter($recep, static function ($p) { return strpos($p, 'personal.') === 0; });
check('A recepcionista NO recibe permisos personal.* (sin acceso, como hoy)', $recepSinPersonal);

// El catalogo declara los tres permisos finos.
$catPersonal = $cfg['catalogo']['personal']['permisos'] ?? [];
check('A catalogo declara personal.gestionar y personal.pagar',
    isset($catPersonal['personal.gestionar']) && isset($catPersonal['personal.pagar']));

/* -------- B) Resolver permission_in_list -------- */
check('B personal.all concede personal.pagar (wildcard)', permission_in_list('personal.pagar', ['personal.all']));
check('B personal.all concede personal.gestionar (wildcard)', permission_in_list('personal.gestionar', ['personal.all']));
check('B solo personal.gestionar NO concede personal.pagar (maker-checker)',
    !permission_in_list('personal.pagar', ['personal.view', 'personal.gestionar']));
check('B personal.view NO concede personal.gestionar', !permission_in_list('personal.gestionar', ['personal.view']));
check('B comodin * concede personal.pagar', permission_in_list('personal.pagar', ['*']));

/* -------- C) Idempotencia del backfill (rollback) -------- */
$db = Database::getInstance();
$pdo = $db->getConnection();

$sqlFirma = "SELECT COALESCE(SUM(
        JSON_CONTAINS(permisos_json,'\"personal.view\"')
      + JSON_CONTAINS(permisos_json,'\"personal.gestionar\"')
      + JSON_CONTAINS(permisos_json,'\"personal.pagar\"')
    ), 0) AS total,
    COALESCE(SUM(JSON_LENGTH(permisos_json)), 0) AS long_total
    FROM roles";
$antes = $pdo->query($sqlFirma)->fetch(PDO::FETCH_ASSOC);

$updates = [
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','personal.view')
       WHERE JSON_CONTAINS(permisos_json,'\"usuarios.edit\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.view\"')",
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','personal.gestionar')
       WHERE JSON_CONTAINS(permisos_json,'\"usuarios.edit\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.gestionar\"')",
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','personal.pagar')
       WHERE JSON_CONTAINS(permisos_json,'\"usuarios.edit\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.pagar\"')",
    "UPDATE roles SET permisos_json = JSON_ARRAY_APPEND(permisos_json,'$','personal.view')
       WHERE JSON_CONTAINS(permisos_json,'\"usuarios.view\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"usuarios.edit\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"*\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.all\"')
         AND NOT JSON_CONTAINS(permisos_json,'\"personal.view\"')",
];

$db->safeBeginTransaction();
try {
    $filasAfectadas = 0;
    foreach ($updates as $sql) {
        $filasAfectadas += $pdo->exec($sql);
    }
    $despues = $pdo->query($sqlFirma)->fetch(PDO::FETCH_ASSOC);

    check('C re-aplicar el backfill NO afecta filas (idempotente)', (int) $filasAfectadas === 0);
    check('C conteo de permisos personal.* estable', (int) $antes['total'] === (int) $despues['total']);
    check('C longitud total de permisos_json estable', (int) $antes['long_total'] === (int) $despues['long_total']);

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
