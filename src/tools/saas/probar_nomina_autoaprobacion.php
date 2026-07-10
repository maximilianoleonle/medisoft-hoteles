<?php
/**
 * Prueba de regresion (rollback) de la auto-aprobacion de cierre:
 * con nomina.requiere_aprobacion_cierre = 0, cerrar() debe dejar el periodo
 * en estado 'aprobado' EN LA MISMA transaccion, con los creditos NOMV2
 * emitidos y el evento de aprobacion registrado.
 *
 * El caso contrario (config = 1, cierre queda en 'cerrado' y exige segundo
 * paso) lo cubre probar_nomina_doble_pago.php: su aprobar() explicito
 * fallaria con "Solo un periodo cerrado puede aprobarse" si el cierre
 * auto-aprobara con la config default.
 *
 * NOTA: la config se escribe ANTES de la primera lectura del registry en el
 * proceso (ConfiguracionHotelRegistry cachea por hotel y no expone
 * invalidacion); no cambiar el orden.
 *
 * Todo corre dentro de una transaccion que SE REVIERTE: no persiste nada.
 * Uso: php src/tools/saas/probar_nomina_autoaprobacion.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); echo "Solo CLI.\n"; exit(1); }

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Database.php';
require_once APP_PATH . '/models/ConfiguracionHotelRegistry.php';
require_once APP_PATH . '/services/NominaCalculoService.php';
require_once APP_PATH . '/services/NominaCierreService.php';

$HOTEL = 2;
$fallos = 0;
$ok = 0;
function check($nombre, $cond) {
    global $ok, $fallos;
    if ($cond) { $ok++; echo "  PASS  $nombre\n"; }
    else { $fallos++; echo "  FAIL  $nombre\n"; }
}

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "== Prueba auto-aprobacion de cierre (rollback) ==\n\n";

$db->safeBeginTransaction();
try {
    // Config SIN segundo paso, escrita antes de que el registry cachee.
    $pdo->prepare(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, 'nomina.requiere_aprobacion_cierre', '0', 'boolean', 'nomina', 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE valor = '0', updated_at = NOW()"
    )->execute([$HOTEL]);

    $g = $pdo->query("SELECT id, nombre FROM nomina_grupos WHERE hotel_id = {$HOTEL} AND periodicidad = 'quincenal' AND activo = 1 ORDER BY id LIMIT 1")->fetch();
    if (!$g) { throw new Exception('No hay grupo quincenal en hotel ' . $HOTEL . ' (corre seed_nomina_demo.php).'); }
    $grupoId = (int) $g['id'];

    $inicio = '2036-03-01';
    $fin = '2036-03-15';

    $pdo->prepare(
        "INSERT INTO trabajadores (hotel_id, nombre_completo, rol_laboral, estado, fecha_alta, salario_base, periodicidad_pago, grupo_nomina_id, created_at)
         VALUES (?, '[TEST] Auto Aprobacion', 'test', 'activo', ?, 3000.00, 'quincenal', ?, NOW())"
    )->execute([$HOTEL, $inicio, $grupoId]);
    $trabId = (int) $pdo->lastInsertId();

    $pdo->prepare(
        "INSERT INTO trabajador_salarios (hotel_id, trabajador_id, salario, esquema, vigente_desde, created_at)
         VALUES (?, ?, 3000.00, 'quincenal', ?, NOW())"
    )->execute([$HOTEL, $trabId, $inicio]);

    // Cerrar: con la config en 0 debe cerrar Y aprobar en un solo paso.
    $cierre = new NominaCierreService($db);
    $periodoId = $cierre->cerrar($HOTEL, $grupoId, $inicio, $fin, null);

    $st = $pdo->prepare("SELECT estado, aprobado_at FROM trabajador_nomina_periodos WHERE id = ? AND hotel_id = ?");
    $st->execute([$periodoId, $HOTEL]);
    $periodo = $st->fetch();
    check('cerrar() dejo el periodo en estado aprobado', ($periodo['estado'] ?? '') === 'aprobado');
    check('cerrar() sello aprobado_at', !empty($periodo['aprobado_at']));

    // Credito NOMV2 emitido = bruto del snapshot (sin ledger v1 absorbido).
    $st = $pdo->prepare("SELECT bruto_periodo FROM trabajador_nomina_periodo_detalles WHERE periodo_id = ? AND hotel_id = ? AND trabajador_id = ?");
    $st->execute([$periodoId, $HOTEL, $trabId]);
    $bruto = round((float) $st->fetchColumn(), 2);

    $st = $pdo->prepare("SELECT monto FROM trabajador_pagos WHERE hotel_id = ? AND trabajador_id = ? AND referencia = ? AND estado = 'activo'");
    $st->execute([$HOTEL, $trabId, 'NOMV2-' . $periodoId . '-' . $trabId]);
    $credito = round((float) ($st->fetchColumn() ?: 0), 2);
    echo "  INFO  bruto = " . number_format($bruto, 2) . ", credito NOMV2 = " . number_format($credito, 2) . "\n";
    check('credito NOMV2 emitido en el mismo cierre (= bruto)', $credito > 0 && abs($credito - $bruto) < 0.01);

    // Trazabilidad: el periodo debe tener evento de cierre Y de aprobacion.
    $st = $pdo->prepare("SELECT COUNT(*) FROM trabajador_nomina_periodo_eventos WHERE periodo_id = ? AND hotel_id = ? AND tipo = 'aprobacion'");
    $st->execute([$periodoId, $HOTEL]);
    check('evento de aprobacion registrado en la bitacora del periodo', (int) $st->fetchColumn() === 1);

    // Un segundo aprobar() manual debe rechazarse (ya no esta 'cerrado').
    $reaprobarBloqueado = false;
    try {
        $cierre->aprobar($HOTEL, $periodoId, null);
    } catch (Throwable $e) {
        $reaprobarBloqueado = (strpos($e->getMessage(), 'cerrado') !== false);
    }
    check('aprobar() manual posterior RECHAZADO (no emite creditos dobles)', $reaprobarBloqueado);

    throw new Exception('__ROLLBACK_OK__');
} catch (Throwable $e) {
    $db->safeRollBack();
    if ($e->getMessage() !== '__ROLLBACK_OK__') {
        echo "\n  ERROR de la prueba: " . $e->getMessage() . "\n";
        $fallos++;
    }
}

echo "\n== Resumen == PASS: {$ok}  FAIL: {$fallos}\n";
echo "  (rollback ejecutado: no persistio nada)\n";
exit($fallos > 0 ? 1 : 0);
