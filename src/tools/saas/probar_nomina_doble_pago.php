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
 *  #3 Sub-ventanas de periodo: el filtro de solape del saldo no puede
 *     puentear el pool global. Con el saldo ya consumido, un pago con
 *     ventana parcial (que excluye los pagos previos pero ve el credito
 *     NOMV2 completo) debe quedar BLOQUEADO por el tope global.
 *  G1/G2 Guardas del ledger v1: referencia NOMV2- reservada y rechazo de
 *     conceptos con fecha dentro de un periodo v2 congelado (huerfanos).
 *
 * Todo corre dentro de una transaccion que SE REVIERTE: no persiste nada.
 * NOTA de orden: G2 y #3 corren ANTES del intento de anular de #2, porque
 * ese anular fallido deja el periodo en 'anulado' dentro de la transaccion
 * (los servicios no hacen rollback propio cuando la transaccion es externa).
 * Uso: php src/tools/saas/probar_nomina_doble_pago.php
 */

if (PHP_SAPI !== 'cli') { http_response_code(403); echo "Solo CLI.\n"; exit(1); }

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Database.php';
require_once APP_PATH . '/models/ConfiguracionHotelRegistry.php';
require_once APP_PATH . '/models/Trabajador.php';
require_once APP_PATH . '/services/NominaCalculoService.php';
require_once APP_PATH . '/services/NominaCierreService.php';
require_once APP_PATH . '/services/TrabajadorPagoCajaService.php';

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

    // --- GUARDAS del ledger v1 (con el periodo aun aprobado) ---
    $modelo = new Trabajador();

    // G1: el prefijo NOMV2- esta reservado al motor (el throw ocurre en la
    // validacion, antes de que el modelo abra/commitee transaccion).
    $prefijoBloqueado = false;
    try {
        $modelo->registrarConceptoLaboralParaHotel($trabId, $HOTEL, [
            'tipo' => 'bono', 'efecto' => 'a_favor', 'monto' => '100',
            'concepto' => 'Test prefijo reservado', 'fecha' => '2035-04-01',
            'referencia' => 'NOMV2-999-1',
        ], null);
    } catch (Throwable $e) {
        $prefijoBloqueado = (stripos($e->getMessage(), 'reservada') !== false);
    }
    check('G1 referencia NOMV2- manual RECHAZADA (prefijo reservado del motor)', $prefijoBloqueado);

    // G2: concepto con fecha dentro del periodo v2 congelado = huerfano.
    $huerfanoBloqueado = false;
    try {
        $modelo->registrarConceptoLaboralParaHotel($trabId, $HOTEL, [
            'tipo' => 'bono', 'efecto' => 'a_favor', 'monto' => '100',
            'concepto' => 'Test concepto tardio', 'fecha' => '2035-03-12',
        ], null);
    } catch (Throwable $e) {
        $huerfanoBloqueado = (stripos($e->getMessage(), 'ya cerrado') !== false);
    }
    check('G2 concepto v1 con fecha dentro del periodo v2 cerrado RECHAZADO (huerfano)', $huerfanoBloqueado);

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

    // --- CRITICO #3: sub-ventanas de periodo no puentean el pool global ---
    // El pago libre de arriba (sin periodo_inicio/fin, fecha_pago 01-mar) ya
    // consumio TODO el saldo. Una ventana parcial 12..14-mar lo excluye del
    // filtro de solape pero sigue viendo el credito NOMV2 (01..15-mar)
    // completo: sin el tope global, este pago volveria a sacar el dinero.
    $pagoSvc = new TrabajadorPagoCajaService($db, ['manage_transaction' => false]);

    $evalSub = $pagoSvc->evaluarPago($HOTEL, $trabId, [
        'periodo_inicio' => '2035-03-12',
        'periodo_fin' => '2035-03-14',
    ]);
    check('#3 evaluarPago con sub-ventana reporta tope 0.00 (pool global agotado)', abs((float) $evalSub['monto_maximo']) < 0.01);

    // Corte abierto para intentar el pago real (si no hay, se abre uno; todo
    // se revierte con el rollback final).
    $corteAbierto = $pdo->query(
        "SELECT cc.id FROM cortes_caja cc
         INNER JOIN cajas c ON c.id = cc.caja_id AND c.hotel_id = cc.hotel_id
         WHERE cc.hotel_id = {$HOTEL} AND cc.estado = 'abierto' AND COALESCE(c.activa, 1) = 1
         ORDER BY cc.id DESC LIMIT 1"
    )->fetchColumn();
    if (!$corteAbierto) {
        $cajaId = $pdo->query("SELECT id FROM cajas WHERE hotel_id = {$HOTEL} AND COALESCE(activa, 1) = 1 ORDER BY id LIMIT 1")->fetchColumn();
        if ($cajaId) {
            $pdo->prepare(
                "INSERT INTO cortes_caja (hotel_id, caja_id, fecha_apertura, monto_inicial, estado, usuario_apertura_id)
                 VALUES (?, ?, NOW(), 0.00, 'abierto', ?)"
            )->execute([$HOTEL, (int) $cajaId, $usuarioTest]);
            $corteAbierto = (int) $pdo->lastInsertId();
        }
    }

    if ($corteAbierto) {
        $subVentanaBloqueado = false;
        try {
            $pagoSvc->registrarPago($HOTEL, $trabId, [
                'monto' => '100.00',
                'metodo_pago' => 'efectivo',
                'referencia' => 'TEST-SUBVENTANA-' . $trabId,
                'periodo_inicio' => '2035-03-12',
                'periodo_fin' => '2035-03-14',
            ], $usuarioTest);
        } catch (Throwable $e) {
            $subVentanaBloqueado = (stripos($e->getMessage(), 'saldo laboral disponible') !== false);
        }
        check('#3 pago con sub-ventana BLOQUEADO: no ve de nuevo el credito ya pagado', $subVentanaBloqueado);
    } else {
        echo "  INFO  #3 (pago real) omitido: no hay caja activa para abrir corte en hotel {$HOTEL}.\n";
    }

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
