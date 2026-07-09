<?php
/**
 * Prueba de regresion (rollback) de los 2 criticos de doble pago de Nomina v2,
 * segun la implementacion consolidada (invariante de credito):
 *  #1 Doble disponibilidad de saldo: el credito NOMV2 = bruto del snapshot
 *     MENOS el neto de las lineas ledger v1 absorbidas (que siguen activas)
 *     -> el saldo pagable del trabajador queda en el bruto exacto, sin exceso.
 *  #2 Candado anti doble pago en anular/reabrir: un pago del riel LIBRE
 *     (nomina_periodo_id NULL) que ya consumio el credito aprobado debe
 *     bloquear anular/reabrir (verificarCreditosNoConsumidos).
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

    // Salario vigente (el motor lo toma de trabajador_salarios, no del campo
    // salario_base): con sueldo el bruto del periodo supera al ledger absorbido
    // y el credito NOMV2 emitido es > 0 (necesario para ejercitar el candado #2).
    $pdo->prepare(
        "INSERT INTO trabajador_salarios (hotel_id, trabajador_id, salario, esquema, vigente_desde, created_at)
         VALUES (?, ?, 2000.00, 'quincenal', ?, NOW())"
    )->execute([$HOTEL, $trabId, $inicio]);

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
    echo "  INFO  neto del periodo = " . number_format((float) $netoTest, 2) . " (sueldo 2000 + bono 500 - deducciones)\n";

    // Cerrar y aprobar (dentro de la transaccion externa: los servicios no commitean).
    $cierre = new NominaCierreService($db);
    $periodoId = $cierre->cerrar($HOTEL, $grupoId, $inicio, $fin, null);
    $cierre->aprobar($HOTEL, $periodoId, null);

    // Bruto del snapshot: la referencia del invariante de credito.
    $st = $pdo->prepare("SELECT bruto_periodo FROM trabajador_nomina_periodo_detalles WHERE periodo_id = ? AND hotel_id = ? AND trabajador_id = ?");
    $st->execute([$periodoId, $HOTEL, $trabId]);
    $brutoTest = round((float) $st->fetchColumn(), 2);
    echo "  INFO  bruto del snapshot = " . number_format($brutoTest, 2) . "\n";

    // --- CRITICO #1: sin doble conteo (credito = bruto - ledger absorbido) ---
    $credito = $pdo->prepare("SELECT monto FROM trabajador_pagos WHERE hotel_id = ? AND trabajador_id = ? AND referencia = ? AND estado = 'activo'");
    $credito->execute([$HOTEL, $trabId, 'NOMV2-' . $periodoId . '-' . $trabId]);
    $montoCredito = round((float) ($credito->fetchColumn() ?: 0), 2);
    echo "  INFO  credito NOMV2 emitido = " . number_format($montoCredito, 2) . "\n";
    check('#1 credito NOMV2 = bruto - bono absorbido (no re-suma el ledger v1)', abs($montoCredito - ($brutoTest - 500.0)) < 0.01);

    // Saldo pagable del trabajador = suma activa a_favor - en_contra. El bono
    // sigue activo + credito (bruto - bono) => debe quedar en el bruto exacto
    // (sin doble conteo seria bruto + 500).
    $sum = $pdo->prepare(
        "SELECT COALESCE(SUM(CASE WHEN efecto = 'a_favor' THEN monto ELSE -monto END), 0)
         FROM trabajador_pagos
         WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'activo' AND fecha BETWEEN ? AND ?"
    );
    $sum->execute([$HOTEL, $trabId, $inicio, $fin]);
    $saldoPagable = round((float) $sum->fetchColumn(), 2);
    echo "  INFO  saldo pagable (bono + credito) = " . number_format($saldoPagable, 2) . "\n";
    check('#1 saldo pagable == bruto (sin exceso por doble conteo)', abs($saldoPagable - $brutoTest) < 0.01);

    // --- CRITICO #2: candado detecta credito consumido por el riel libre ---
    // Simular un pago libre que consume todo el saldo (bono + credito NOMV2):
    // movimiento_caja + trabajador_pagos_caja con nomina_periodo_id NULL.
    $corte = $pdo->query("SELECT id FROM cortes_caja WHERE hotel_id = {$HOTEL} ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$corte) { throw new Exception('No hay cortes_caja en hotel ' . $HOTEL . ' para simular el pago libre.'); }
    $usuarioTest = (int) ($pdo->query("SELECT usuario_id FROM hotel_usuarios WHERE hotel_id = {$HOTEL} LIMIT 1")->fetchColumn() ?: 0);
    if ($usuarioTest <= 0) { throw new Exception('No hay usuario del hotel para la simulacion.'); }
    $pdo->prepare(
        "INSERT INTO movimientos_caja (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago, referencia, usuario_id, corte_id, created_at)
         VALUES (?, 'gasto', 'Pago laboral', NULL, 'TEST pago libre', ?, 'efectivo', ?, ?, ?, NOW())"
    )->execute([$HOTEL, number_format($brutoTest, 2, '.', ''), 'TEST-LIBRE-' . $trabId, $usuarioTest, (int) $corte]);
    $movId = (int) $pdo->lastInsertId();
    $pdo->prepare(
        "INSERT INTO trabajador_pagos_caja (hotel_id, trabajador_id, movimiento_caja_id, corte_id, nomina_periodo_id, nomina_periodo_detalle_id, monto, metodo_pago, referencia, fecha_pago, estado, created_at, updated_at)
         VALUES (?, ?, ?, ?, NULL, NULL, ?, 'efectivo', ?, ?, 'pagado', NOW(), NOW())"
    )->execute([$HOTEL, $trabId, $movId, (int) $corte, number_format($brutoTest, 2, '.', ''), 'TEST-LIBRE-' . $trabId, $inicio . ' 12:00:00']);

    // Con el credito ya consumido por el pago libre, anular DEBE fallar
    // (verificarCreditosNoConsumidos: conceptos - pagos < credito a retirar).
    $anularBloqueado = false;
    try {
        $cierre->anular($HOTEL, $periodoId, 'test anular', null);
    } catch (Throwable $e) {
        $anularBloqueado = (strpos($e->getMessage(), 'pago') !== false);
    }
    check('#2 anular BLOQUEADO: el pago del riel libre (periodo NULL) ya consumio el credito', $anularBloqueado);

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
