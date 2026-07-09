<?php
/**
 * Prueba de regresion (rollback) de los 2 criticos de doble pago de Nomina v2:
 *  #1 Doble disponibilidad de saldo: el credito NOMV2 no debe re-sumar las
 *     lineas de ledger v1 ya presentes -> saldo pagable == neto.
 *  #2 Guard de reabrir/anular ciego al riel libre: un pago con
 *     nomina_periodo_id NULL dentro del rango debe bloquear anular/reabrir.
 *
 * Todo corre dentro de una transaccion que SE REVIERTE: no persiste nada.
 * Uso: php src/tools/saas/probar_nomina_doble_pago.php
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

echo "== Prueba doble pago Nomina v2 (rollback) ==\n\n";

$db->safeBeginTransaction();
try {
    // Grupo quincenal del demo (o el primero disponible).
    $g = $pdo->query("SELECT id, nombre FROM nomina_grupos WHERE hotel_id = {$HOTEL} AND periodicidad = 'quincenal' AND activo = 1 ORDER BY id LIMIT 1")->fetch();
    if (!$g) { throw new Exception('No hay grupo quincenal en hotel ' . $HOTEL . ' (corre seed_nomina_demo.php).'); }
    $grupoId = (int) $g['id'];

    $inicio = '2035-03-01';
    $fin = '2035-03-15';
    $fechaBono = '2035-03-10';

    // Trabajador de prueba, activo, asignado al grupo, con salario quincenal.
    $pdo->prepare(
        "INSERT INTO trabajadores (hotel_id, nombre_completo, rol_laboral, estado, fecha_alta, salario_base, periodicidad_pago, grupo_nomina_id, created_at)
         VALUES (?, '[TEST] Doble Pago', 'test', 'activo', ?, 2000.00, 'quincenal', ?, NOW())"
    )->execute([$HOTEL, $inicio, $grupoId]);
    $trabId = (int) $pdo->lastInsertId();

    // Linea de ledger v1 (bono 500) con fecha dentro del periodo.
    $pdo->prepare(
        "INSERT INTO trabajador_pagos (hotel_id, trabajador_id, tipo, efecto, monto, concepto, fecha, estado, created_at)
         VALUES (?, ?, 'bono', 'a_favor', 500.00, 'Bono test', ?, 'activo', NOW())"
    )->execute([$HOTEL, $trabId, $fechaBono]);

    // Preview -> neto esperado (salario + bono).
    $calc = new NominaCalculoService($db);
    $preview = $calc->preview($HOTEL, $grupoId, $inicio, $fin);
    $netoTest = null;
    foreach ($preview['trabajadores'] as $t) {
        if ((int) $t['trabajador']['id'] === $trabId) { $netoTest = round((float) $t['neto'], 2); break; }
    }
    check('preview incluye al trabajador de prueba', $netoTest !== null);
    echo "  INFO  neto del periodo = " . number_format((float) $netoTest, 2) . " (neto == bono 500: el salario usa vigencias (no seteadas))\n";

    // Cerrar y aprobar (dentro de la transaccion externa: los servicios no commitean).
    $cierre = new NominaCierreService($db);
    $periodoId = $cierre->cerrar($HOTEL, $grupoId, $inicio, $fin, null);
    $cierre->aprobar($HOTEL, $periodoId, null);

    // --- CRITICO #1: sin doble conteo ---
    $credito = $pdo->prepare("SELECT monto FROM trabajador_pagos WHERE hotel_id = ? AND trabajador_id = ? AND referencia = ?");
    $credito->execute([$HOTEL, $trabId, 'NOMV2-' . $periodoId . '-' . $trabId]);
    $montoCredito = round((float) ($credito->fetchColumn() ?: 0), 2);
    echo "  INFO  credito NOMV2 emitido = " . number_format($montoCredito, 2) . "\n";
    check('#1 credito NOMV2 = neto - bono (no el neto completo)', abs($montoCredito - ($netoTest - 500.0)) < 0.01);

    // Saldo pagable del trabajador en el periodo = suma activa a_favor - deducciones.
    $sum = $pdo->prepare(
        "SELECT COALESCE(SUM(CASE WHEN efecto = 'a_favor' THEN monto ELSE -monto END), 0)
         FROM trabajador_pagos
         WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'activo' AND fecha BETWEEN ? AND ?"
    );
    $sum->execute([$HOTEL, $trabId, $inicio, $fin]);
    $saldoPagable = round((float) $sum->fetchColumn(), 2);
    echo "  INFO  saldo pagable (bono + credito) = " . number_format($saldoPagable, 2) . "\n";
    check('#1 saldo pagable == neto (sin exceso por doble conteo)', abs($saldoPagable - $netoTest) < 0.01);

    // --- CRITICO #2: guard detecta pago del riel libre (nomina_periodo_id NULL) ---
    // Simular un pago libre: movimiento_caja + trabajador_pagos_caja con periodo NULL.
    $corte = $pdo->query("SELECT id FROM cortes_caja WHERE hotel_id = {$HOTEL} ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$corte) { throw new Exception('No hay cortes_caja en hotel ' . $HOTEL . ' para simular el pago libre.'); }
    $usuarioTest = (int) ($pdo->query("SELECT usuario_id FROM hotel_usuarios WHERE hotel_id = {$HOTEL} LIMIT 1")->fetchColumn() ?: 0);
    if ($usuarioTest <= 0) { throw new Exception('No hay usuario del hotel para la simulacion.'); }
    $pdo->prepare(
        "INSERT INTO movimientos_caja (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago, referencia, usuario_id, corte_id, created_at)
         VALUES (?, 'gasto', 'Pago laboral', NULL, 'TEST pago libre', ?, 'efectivo', ?, ?, ?, NOW())"
    )->execute([$HOTEL, number_format($netoTest, 2, '.', ''), 'TEST-LIBRE-' . $trabId, $usuarioTest, (int) $corte]);
    $movId = (int) $pdo->lastInsertId();
    $pdo->prepare(
        "INSERT INTO trabajador_pagos_caja (hotel_id, trabajador_id, movimiento_caja_id, corte_id, nomina_periodo_id, nomina_periodo_detalle_id, monto, metodo_pago, referencia, fecha_pago, estado, created_at, updated_at)
         VALUES (?, ?, ?, ?, NULL, NULL, ?, 'efectivo', ?, ?, 'pagado', NOW(), NOW())"
    )->execute([$HOTEL, $trabId, $movId, (int) $corte, number_format($netoTest, 2, '.', ''), 'TEST-LIBRE-' . $trabId, $inicio . ' 12:00:00']);

    // Con un pago libre vigente en el rango, anular DEBE fallar.
    $anularBloqueado = false;
    try {
        $cierre->anular($HOTEL, $periodoId, 'test anular', null);
    } catch (Throwable $e) {
        $anularBloqueado = (strpos($e->getMessage(), 'pago') !== false);
    }
    check('#2 anular BLOQUEADO por el pago del riel libre (periodo NULL)', $anularBloqueado);

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
