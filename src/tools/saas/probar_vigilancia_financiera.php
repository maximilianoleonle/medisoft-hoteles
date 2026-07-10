<?php
/**
 * Prueba del bloque premium vigilancia_financiera (CLI, solo local).
 *
 * Verifica la ESCALERA DE COSTO del servicio sin tocar rutas ni monetizacion:
 *   - Nivel 1 (plantilla, $0): hotel con conciliacion limpia -> informe verde
 *     SIN llamar al API (se prueba con la key suprimida para demostrarlo).
 *   - Nivel 2 (claude-opus-4-8): hotel con hallazgos -> gating sin key y, si
 *     hay ANTHROPIC_API_KEY, UNA generacion real (costo: centavos).
 *   - Cache: la segunda llamada debe venir desde cache en ambos niveles.
 *
 * Uso:
 *   php src/tools/saas/probar_vigilancia_financiera.php              (hotel 1)
 *   php src/tools/saas/probar_vigilancia_financiera.php 3            (hotel 3)
 *   php src/tools/saas/probar_vigilancia_financiera.php 3 --anomalia
 *     Inyecta 2 movimientos Caja QA huerfanos (referencia QA-VIGIA-TEST-1)
 *     para forzar el nivel 2, corre el analisis real y SIEMPRE limpia al
 *     final (filas QA + cache del dia), aunque la prueba falle.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

// Cargar .env de la raiz del repo (mismo patron que probar_copiloto_ia).
$envPath = dirname(__DIR__, 3) . '/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        if (trim($k) !== '' && getenv(trim($k)) === false) {
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

if (getenv('APP_ENV') !== 'local') {
    echo "[ERROR] APP_ENV debe ser local para esta prueba.\n";
    exit(1);
}

$hotelId = 1;
$conAnomalia = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--anomalia') {
        $conAnomalia = true;
    } elseif (ctype_digit((string) $arg)) {
        $hotelId = (int) $arg;
    }
}

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/app/services/VigilanciaFinancieraService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fallas = 0;
$ok = static function (bool $cond, string $msg) use (&$fallas) {
    echo ($cond ? '[OK]   ' : '[FALLA]') . ' ' . $msg . "\n";
    if (!$cond) {
        $fallas++;
    }
};
$info = static function (string $msg) {
    echo '[INFO] ' . $msg . "\n";
};

echo "== Prueba vigilancia_financiera (hotel {$hotelId}" . ($conAnomalia ? ', con anomalia QA' : '') . ") ==\n";

$servicio = new VigilanciaFinancieraService($db);

// ── 0) Anomalia QA opcional (limpieza garantizada al salir) ─────────────
if ($conAnomalia) {
    $uid = (int) $pdo->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn();
    $ins = $pdo->prepare(
        "INSERT INTO movimientos_caja
            (hotel_id, tipo, categoria, descripcion, monto, metodo_pago, referencia, usuario_id, corte_id)
         VALUES (?, 'ingreso', 'Cobro CxC', 'QA vigilancia financiera (temporal)', 500.00, 'efectivo', 'QA-VIGIA-TEST-1', ?, NULL)"
    );
    $ins->execute([$hotelId, $uid]);
    $ins->execute([$hotelId, $uid]);
    $info('Anomalia QA inyectada: 2 ingresos Cobro CxC huerfanos (ref QA-VIGIA-TEST-1).');

    // Limpieza pase lo que pase (exito, falla o excepcion).
    register_shutdown_function(static function () use ($pdo, $hotelId) {
        try {
            $del = $pdo->prepare(
                "DELETE FROM movimientos_caja WHERE hotel_id = ? AND referencia LIKE 'QA-VIGIA-TEST-%'"
            );
            $del->execute([$hotelId]);
            $filas = $del->rowCount();
            $cache = $pdo->prepare(
                "DELETE FROM ia_resumenes WHERE hotel_id = ? AND tipo = 'vigilancia_financiera' AND fecha = CURDATE()"
            );
            $cache->execute([$hotelId]);
            echo "[INFO] Limpieza QA: {$filas} movimiento(s) y el cache del dia eliminados.\n";
        } catch (Throwable $e) {
            echo '[FALLA] Limpieza QA fallo: ' . $e->getMessage() . " — borra a mano los movimientos con referencia QA-VIGIA-TEST-1 del hotel {$hotelId}.\n";
        }
    });
}

// ── 1) Conciliacion determinista disponible ─────────────────────────────
require_once dirname(__DIR__, 2) . '/app/models/ConciliacionFinanciera.php';
$reporte = (new ConciliacionFinanciera())->reporteReadOnlyPorHotel($hotelId, ['page' => 1, 'limit' => 50]);
$ok(is_array($reporte) && array_key_exists('schema_ok', $reporte), 'La conciliacion determinista respondio.');
if (empty($reporte['schema_ok'])) {
    $info('Esquema financiero incompleto en esta base: faltan tablas de CxC/CxP/Caja. Fin.');
    exit($fallas > 0 ? 1 : 0);
}
$totales = $reporte['totales_alertas'] ?? [];
$errores = (int) ($totales['error'] ?? 0);
$warnings = (int) ($totales['warning'] ?? 0);
$limpio = ($errores === 0 && $warnings === 0);
$info(sprintf(
    'Conciliacion: %d errores, %d warnings, %d hallazgos -> nivel esperado: %s.',
    $errores,
    $warnings,
    (int) ($totales['hallazgos'] ?? 0),
    $limpio ? '1 (plantilla, $0)' : '2 (Opus 4.8)'
));

// Guardamos la key real y la suprimimos para probar los caminos sin costo.
$keyReal = trim((string) getenv('ANTHROPIC_API_KEY'));
putenv('ANTHROPIC_API_KEY=');
$ok(!$servicio->configurado(), 'configurado() = false sin API key.');

if ($limpio) {
    // ── Nivel 1: informe verde de plantilla, sin key y sin costo ─────────
    $res = $servicio->analizar($hotelId, [], true, null);
    $ok(!empty($res['success']), 'Nivel 1: analizar() funciono SIN API key (plantilla local).');
    $ok(($res['modelo'] ?? '') === 'plantilla', "Nivel 1: modelo = 'plantilla' (no se llamo al API).");
    $ok(strpos((string) ($res['informe'] ?? ''), '🟢') !== false, 'Nivel 1: el informe trae el semaforo verde.');
    echo "\n----- INFORME (plantilla, costo \$0) -----\n" . rtrim((string) ($res['informe'] ?? '')) . "\n----- FIN INFORME -----\n\n";

    $cache = $servicio->analizar($hotelId, [], false);
    $ok(!empty($cache['desde_cache']), 'Segunda llamada vino desde cache.');

    if ($keyReal !== '') {
        putenv('ANTHROPIC_API_KEY=' . $keyReal);
    }
    echo ($fallas === 0 ? "\n== Todo OK (nivel 1, \$0 gastados) ==\n" : "\n== {$fallas} falla(s) ==\n");
    exit($fallas > 0 ? 1 : 0);
}

// ── Nivel 2: hay hallazgos ───────────────────────────────────────────────
$sinKey = $servicio->analizar($hotelId, [], true);
$ok(empty($sinKey['success']) && !empty($sinKey['message']), 'Nivel 2 sin key: mensaje amable (no revienta).');

if ($keyReal === '') {
    $info('Sin ANTHROPIC_API_KEY: se omite la llamada real a Opus 4.8.');
    echo ($fallas === 0 ? "\n== Todo OK (sin llamada real) ==\n" : "\n== {$fallas} falla(s) ==\n");
    exit($fallas > 0 ? 1 : 0);
}

putenv('ANTHROPIC_API_KEY=' . $keyReal);
$info('Llamando a Opus 4.8 (una vez, costo centavos)...');
$t0 = microtime(true);
$res = $servicio->analizar($hotelId, [], true, null);
$ms = (int) round((microtime(true) - $t0) * 1000);

if (empty($res['success'])) {
    $ok(false, 'Generacion real: ' . ($res['message'] ?? 'sin mensaje'));
    exit(1);
}
$ok(true, sprintf('Generacion real OK en %d ms (modelo: %s).', $ms, $res['modelo'] ?? '?'));
$info('Hallazgos deterministas de base: ' . (int) ($res['hallazgos_deterministas'] ?? 0));
echo "\n----- INFORME -----\n" . rtrim((string) $res['informe']) . "\n----- FIN INFORME -----\n\n";

$cache = $servicio->analizar($hotelId, [], false);
$ok(!empty($cache['desde_cache']), 'Segunda llamada vino desde cache (no re-pago tokens).');

echo ($fallas === 0 ? "\n== Todo OK ==\n" : "\n== {$fallas} falla(s) ==\n");
exit($fallas > 0 ? 1 : 0);
