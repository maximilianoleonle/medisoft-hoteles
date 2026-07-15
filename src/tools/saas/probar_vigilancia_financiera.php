<?php
/**
 * Prueba del bloque premium vigilancia_financiera (CLI, solo local).
 *
 * Verifica la ESCALERA DE COSTO del servicio sin tocar rutas ni monetizacion:
 *   - Nivel 1 (plantilla, $0): integridad Y patrones limpios -> informe verde
 *     SIN llamar al API (se prueba con la key suprimida para demostrarlo).
 *   - Nivel 2 (claude-opus-4-8): hallazgos de integridad (sin patrones) ->
 *     gating sin key y, si hay ANTHROPIC_API_KEY, UNA generacion real.
 *   - Nivel 3 (claude-fable-5, forense): patrones de comportamiento del
 *     Guardian -> prompt con movimientos crudos, fallback a Opus 4.8.
 *   - Cache: la segunda llamada debe venir desde cache en todos los niveles.
 *
 * Uso:
 *   php src/tools/saas/probar_vigilancia_financiera.php              (hotel 1)
 *   php src/tools/saas/probar_vigilancia_financiera.php 3            (hotel 3)
 *   php src/tools/saas/probar_vigilancia_financiera.php 3 --anomalia
 *     Inyecta 2 movimientos Caja QA huerfanos (referencia QA-VIGIA-TEST-1)
 *     para forzar hallazgos de integridad (nivel 2+).
 *   php src/tools/saas/probar_vigilancia_financiera.php 3 --patron
 *     Inyecta 3 movimientos QA a las 03:00 (referencia QA-VIGIA-PATRON-*)
 *     para forzar un patron del Guardian (nivel 3 forense).
 *   Ambos flags SIEMPRE limpian al final (filas QA + cache del dia).
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
$conPatron = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--anomalia') {
        $conAnomalia = true;
    } elseif ($arg === '--patron') {
        $conPatron = true;
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

echo "== Prueba vigilancia_financiera (hotel {$hotelId}"
    . ($conAnomalia ? ', con anomalia QA' : '')
    . ($conPatron ? ', con patron QA' : '') . ") ==\n";

$servicio = new VigilanciaFinancieraService($db);

// ── 0) Inyecciones QA opcionales (limpieza garantizada al salir) ─────────
if ($conAnomalia || $conPatron) {
    $uid = (int) $pdo->query('SELECT id FROM usuarios LIMIT 1')->fetchColumn();

    if ($conAnomalia) {
        $ins = $pdo->prepare(
            "INSERT INTO movimientos_caja
                (hotel_id, tipo, categoria, descripcion, monto, metodo_pago, referencia, usuario_id, corte_id)
             VALUES (?, 'ingreso', 'Cobro CxC', 'QA vigilancia financiera (temporal)', 500.00, 'efectivo', 'QA-VIGIA-TEST-1', ?, NULL)"
        );
        $ins->execute([$hotelId, $uid]);
        $ins->execute([$hotelId, $uid]);
        $info('Anomalia QA inyectada: 2 ingresos Cobro CxC huerfanos (ref QA-VIGIA-TEST-1).');
    }

    if ($conPatron) {
        // 3 movimientos a las 03:00 en dias recientes: dispara GD_FUERA_HORARIO
        // (regla por eventos, no exige volumen minimo del hotel).
        $ins = $pdo->prepare(
            "INSERT INTO movimientos_caja
                (hotel_id, tipo, categoria, descripcion, monto, metodo_pago, referencia, usuario_id, corte_id, created_at)
             VALUES (?, 'ingreso', 'Hospedaje', 'QA patron guardian (temporal)', 350.00, 'efectivo', ?, ?, NULL, ?)"
        );
        for ($i = 1; $i <= 3; $i++) {
            $ins->execute([$hotelId, 'QA-VIGIA-PATRON-' . $i, $uid, date('Y-m-d', strtotime("-{$i} days")) . ' 03:00:00']);
        }
        $info('Patron QA inyectado: 3 movimientos a las 03:00 (ref QA-VIGIA-PATRON-*).');
    }

    // Limpieza pase lo que pase (exito, falla o excepcion).
    register_shutdown_function(static function () use ($pdo, $hotelId) {
        try {
            $del = $pdo->prepare(
                "DELETE FROM movimientos_caja WHERE hotel_id = ? AND (referencia LIKE 'QA-VIGIA-TEST-%' OR referencia LIKE 'QA-VIGIA-PATRON-%')"
            );
            $del->execute([$hotelId]);
            $filas = $del->rowCount();
            $cache = $pdo->prepare(
                "DELETE FROM ia_resumenes WHERE hotel_id = ? AND tipo = 'vigilancia_financiera' AND fecha = CURDATE()"
            );
            $cache->execute([$hotelId]);
            echo "[INFO] Limpieza QA: {$filas} movimiento(s) y el cache del dia eliminados.\n";
        } catch (Throwable $e) {
            echo '[FALLA] Limpieza QA fallo: ' . $e->getMessage() . " — borra a mano los movimientos QA-VIGIA-* del hotel {$hotelId}.\n";
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

// Patrones del Guardian (misma fuente que el servicio).
require_once dirname(__DIR__, 2) . '/app/models/GuardianPatrones.php';
$patrones = (new GuardianPatrones())->reporteReadOnlyPorHotel($hotelId);
$pTot = $patrones['totales'] ?? [];
$hayPatrones = ((int) ($pTot['alta'] ?? 0) + (int) ($pTot['media'] ?? 0)) > 0;

$limpio = ($errores === 0 && $warnings === 0 && !$hayPatrones);
$nivelEsperado = $limpio ? 1 : ($hayPatrones ? 3 : 2);
$info(sprintf(
    'Conciliacion: %d errores, %d warnings. Guardian: %d hallazgos de patron -> nivel esperado: %s.',
    $errores,
    $warnings,
    (int) ($pTot['hallazgos'] ?? 0),
    [1 => '1 (plantilla, $0)', 2 => '2 (Opus 4.8)', 3 => '3 (Fable 5 forense)'][$nivelEsperado]
));
if ($conPatron) {
    $ok($hayPatrones, 'El patron QA fue detectado por GuardianPatrones (nivel 3 esperado).');
}

// Guardamos la key real y la suprimimos para probar los caminos sin costo.
$keyReal = trim((string) getenv('ANTHROPIC_API_KEY'));
putenv('ANTHROPIC_API_KEY=');
$ok(!$servicio->configurado(), 'configurado() = false sin API key.');

if ($limpio) {
    // ── Nivel 1: informe verde de plantilla, sin key y sin costo ─────────
    $res = $servicio->analizar($hotelId, [], true, null);
    $ok(!empty($res['success']), 'Nivel 1: analizar() funciono SIN API key (plantilla local).');
    $ok(($res['modelo'] ?? '') === 'plantilla', "Nivel 1: modelo = 'plantilla' (no se llamo al API).");
    $ok((int) ($res['nivel'] ?? 0) === 1, 'Nivel 1: el servicio reporta nivel 1.');
    $ok(strpos((string) ($res['informe'] ?? ''), 'Guardian') !== false, 'Nivel 1: la plantilla menciona los patrones vigilados.');
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

// ── Nivel 2/3: hay hallazgos ─────────────────────────────────────────────
$sinKey = $servicio->analizar($hotelId, [], true);
$ok(empty($sinKey['success']) && !empty($sinKey['message']), "Nivel {$nivelEsperado} sin key: mensaje amable (no revienta).");

if ($keyReal === '') {
    $info('Sin ANTHROPIC_API_KEY: se omite la llamada real al API.');
    echo ($fallas === 0 ? "\n== Todo OK (sin llamada real) ==\n" : "\n== {$fallas} falla(s) ==\n");
    exit($fallas > 0 ? 1 : 0);
}

putenv('ANTHROPIC_API_KEY=' . $keyReal);
$info($nivelEsperado === 3
    ? 'Llamando a Fable 5 forense (una vez; puede tomar minutos)...'
    : 'Llamando a Opus 4.8 (una vez, costo centavos)...');
$t0 = microtime(true);
$res = $servicio->analizar($hotelId, [], true, null);
$ms = (int) round((microtime(true) - $t0) * 1000);

if (empty($res['success'])) {
    $ok(false, 'Generacion real: ' . ($res['message'] ?? 'sin mensaje'));
    exit(1);
}
$ok(true, sprintf('Generacion real OK en %d ms (modelo: %s, nivel: %d).', $ms, $res['modelo'] ?? '?', (int) ($res['nivel'] ?? 0)));
$ok((int) ($res['nivel'] ?? 0) === $nivelEsperado, "La escalera eligio el nivel esperado ({$nivelEsperado}).");
$info('Hallazgos deterministas: ' . (int) ($res['hallazgos_deterministas'] ?? 0)
    . ' | patrones: ' . (int) ($res['hallazgos_patrones'] ?? 0));
echo "\n----- INFORME -----\n" . rtrim((string) $res['informe']) . "\n----- FIN INFORME -----\n\n";

$cache = $servicio->analizar($hotelId, [], false);
$ok(!empty($cache['desde_cache']), 'Segunda llamada vino desde cache (no re-pago tokens).');

echo ($fallas === 0 ? "\n== Todo OK ==\n" : "\n== {$fallas} falla(s) ==\n");
exit($fallas > 0 ? 1 : 0);
