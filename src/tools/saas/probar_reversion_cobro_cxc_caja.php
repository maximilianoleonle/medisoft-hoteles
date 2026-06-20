<?php
/**
 * Prueba rollback Fase 7B-D-D-A para reversion de cobro CxC con Caja.
 *
 * Ejecuta un cobro temporal, lo revierte dentro de una transaccion externa
 * y revierte al final. No deja cobros, cancelaciones, movimientos de Caja,
 * auditoria ni cambios de saldo persistentes.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

if (getenv('APP_ENV') !== 'local') {
    echo "[ERROR] APP_ENV debe ser local para esta prueba rollback.\n";
    exit(1);
}

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/app/services/CuentaPorCobrarCobroService.php';
require_once dirname(__DIR__, 2) . '/app/services/CuentaPorCobrarReversionCobroService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function cxcReversalTestLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cxcReversalCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    return (int)$stmt->fetchColumn();
}

function cxcReversalScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $stmt = $pdo->query(
        "SELECT cxc.id AS cuenta_id,
                cxc.hotel_id,
                cxc.saldo,
                cxc.estado,
                cxc.total,
                cc.id AS corte_id
         FROM cuentas_por_cobrar cxc
         INNER JOIN cortes_caja cc
            ON cc.hotel_id = cxc.hotel_id
           AND cc.estado = 'abierto'
         INNER JOIN cajas c
            ON c.id = cc.caja_id
           AND c.hotel_id = cc.hotel_id
           AND COALESCE(c.activa, 1) = 1
         LEFT JOIN reservaciones r
            ON r.id = cxc.reservacion_id
           AND r.hotel_id = cxc.hotel_id
         LEFT JOIN solicitudes_factura sf
            ON sf.id = cxc.solicitud_factura_id
           AND sf.hotel_id = cxc.hotel_id
         WHERE cxc.estado IN ('pendiente', 'parcial', 'vencida')
           AND cxc.saldo >= 1
           AND cxc.total > 0
           AND cxc.saldo <= cxc.total
           AND (cxc.reservacion_id IS NULL OR (r.id IS NOT NULL AND r.estado <> 'cancelada'))
           AND (cxc.solicitud_factura_id IS NULL OR sf.id IS NOT NULL)
         ORDER BY cxc.id ASC
         LIMIT 1"
    );
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        throw new RuntimeException('No hay CxC elegible con corte abierto para prueba rollback de reversion.');
    }

    $cuentaId = (int)$cuenta['cuenta_id'];
    $hotelId = (int)$cuenta['hotel_id'];
    $saldoAntes = (string)$cuenta['saldo'];
    $estadoAntes = (string)$cuenta['estado'];

    $cxcMovsAntes = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos');
    $cobrosAntes = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
    $cancelacionesAntes = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'CANCELACION'");
    $cajaMovsAntes = cxcReversalCountRows($pdo, 'movimientos_caja');
    $cajaCobrosAntes = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Cobro CxC'");
    $cajaReversionesAntes = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Reversion Cobro CxC'");
    $logsAntes = cxcReversalCountRows($pdo, 'logs_auditoria');

    cxcReversalTestLine('OK', 'Cuenta elegida para rollback: CxC #' . $cuentaId . ', hotel ' . $hotelId . ', saldo ' . $saldoAntes . '.');
    cxcReversalTestLine('OK', 'Corte abierto detectado: #' . (int)$cuenta['corte_id'] . '.');

    $pdo->beginTransaction();
    try {
        $cobroService = new CuentaPorCobrarCobroService($db, ['manage_transaction' => false]);
        $referenciaPrueba = 'TEST-ROLLBACK-CXC-REV-' . date('YmdHis');
        $cobro = $cobroService->registrarCobro($hotelId, $cuentaId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'notas' => 'Cobro temporal para probar reversion rollback 7B-D-D-A. No debe persistir.',
        ], 1);

        $movimientoCobroId = (int)$cobro['cuenta_por_cobrar_movimiento_id'];
        $reversionService = new CuentaPorCobrarReversionCobroService($db, ['manage_transaction' => false]);
        $resultado = $reversionService->revertirCobro($hotelId, $cuentaId, $movimientoCobroId, [
            'motivo' => 'Prueba rollback automatica Fase 7B-D-D-A. No debe persistir.',
        ], 1);

        $cxcMovsDurante = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos');
        $cobrosDurante = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
        $cancelacionesDurante = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'CANCELACION'");
        $cajaMovsDurante = cxcReversalCountRows($pdo, 'movimientos_caja');
        $cajaCobrosDurante = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Cobro CxC'");
        $cajaReversionesDurante = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Reversion Cobro CxC'");
        $logsDurante = cxcReversalCountRows($pdo, 'logs_auditoria');
        $saldoDurante = cxcReversalScalar($pdo, 'SELECT saldo FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
        $estadoDurante = cxcReversalScalar($pdo, 'SELECT estado FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
        $tipoCobroDurante = cxcReversalScalar($pdo, 'SELECT tipo_movimiento FROM cuentas_por_cobrar_movimientos WHERE id = ?', [$movimientoCobroId]);
        $tipoCancelacionDurante = cxcReversalScalar($pdo, 'SELECT tipo_movimiento FROM cuentas_por_cobrar_movimientos WHERE id = ?', [(int)$resultado['movimiento_cancelacion_id']]);
        $tipoCajaCobroDurante = cxcReversalScalar($pdo, 'SELECT tipo FROM movimientos_caja WHERE id = ?', [(int)$cobro['movimiento_caja_id']]);
        $tipoCajaReversionDurante = cxcReversalScalar($pdo, 'SELECT tipo FROM movimientos_caja WHERE id = ?', [(int)$resultado['movimiento_caja_reversion_id']]);

        if ($cxcMovsDurante !== $cxcMovsAntes + 2 || $cobrosDurante !== $cobrosAntes + 1 || $cancelacionesDurante !== $cancelacionesAntes + 1) {
            throw new RuntimeException('No se insertaron exactamente un COBRO y una CANCELACION CxC dentro de la transaccion.');
        }

        if (
            $cajaMovsDurante !== $cajaMovsAntes + 2
            || $cajaCobrosDurante !== $cajaCobrosAntes + 1
            || $cajaReversionesDurante !== $cajaReversionesAntes + 1
            || $tipoCajaCobroDurante !== 'ingreso'
            || $tipoCajaReversionDurante !== 'gasto'
        ) {
            throw new RuntimeException('No se insertaron exactamente un ingreso y un gasto de Caja dentro de la transaccion.');
        }

        if ($logsDurante < $logsAntes + 2) {
            throw new RuntimeException('No se registro auditoria dentro de la transaccion.');
        }

        if ((float)$saldoDurante !== (float)$saldoAntes || (string)$estadoDurante !== $estadoAntes) {
            throw new RuntimeException('El saldo/estado dentro de la transaccion no regreso al valor inicial tras cobrar y revertir.');
        }

        if ($tipoCobroDurante !== 'COBRO') {
            throw new RuntimeException('El movimiento CxC temporal no quedo como COBRO.');
        }

        if ($tipoCancelacionDurante !== 'CANCELACION') {
            throw new RuntimeException('El movimiento CxC de reversion no quedo como CANCELACION.');
        }

        cxcReversalTestLine('OK', 'Movimiento CxC COBRO temporal #' . $movimientoCobroId . ' creado.');
        cxcReversalTestLine('OK', 'Movimiento CxC CANCELACION temporal #' . (int)$resultado['movimiento_cancelacion_id'] . ' creado.');
        cxcReversalTestLine('OK', 'Movimiento Caja ingreso temporal #' . (int)$cobro['movimiento_caja_id'] . ' creado.');
        cxcReversalTestLine('OK', 'Movimiento Caja gasto temporal #' . (int)$resultado['movimiento_caja_reversion_id'] . ' creado.');
        cxcReversalTestLine('OK', 'Saldo temporal: ' . number_format((float)$saldoDurante, 2, '.', '') . '.');

        try {
            $reversionService->revertirCobro($hotelId, $cuentaId, $movimientoCobroId, [
                'motivo' => 'Intento duplicado esperado dentro de rollback.',
            ], 1);
            throw new RuntimeException('La doble reversion fue aceptada indebidamente.');
        } catch (Throwable $duplicado) {
            if (strpos($duplicado->getMessage(), 'reversion') === false) {
                throw $duplicado;
            }
            cxcReversalTestLine('OK', 'Doble reversion bloqueada limpiamente dentro de la transaccion.');
        }

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $cxcMovsDespues = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos');
    $cobrosDespues = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
    $cancelacionesDespues = cxcReversalCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'CANCELACION'");
    $cajaMovsDespues = cxcReversalCountRows($pdo, 'movimientos_caja');
    $cajaCobrosDespues = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Cobro CxC'");
    $cajaReversionesDespues = cxcReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Reversion Cobro CxC'");
    $logsDespues = cxcReversalCountRows($pdo, 'logs_auditoria');
    $saldoDespues = cxcReversalScalar($pdo, 'SELECT saldo FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
    $estadoDespues = cxcReversalScalar($pdo, 'SELECT estado FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);

    if (
        $cxcMovsDespues !== $cxcMovsAntes
        || $cobrosDespues !== $cobrosAntes
        || $cancelacionesDespues !== $cancelacionesAntes
        || $cajaMovsDespues !== $cajaMovsAntes
        || $cajaCobrosDespues !== $cajaCobrosAntes
        || $cajaReversionesDespues !== $cajaReversionesAntes
        || $logsDespues !== $logsAntes
    ) {
        throw new RuntimeException('El rollback no dejo los conteos iguales a los iniciales.');
    }

    if ((string)$saldoDespues !== $saldoAntes || (string)$estadoDespues !== $estadoAntes) {
        throw new RuntimeException('El rollback no dejo saldo/estado iguales a los iniciales.');
    }

    cxcReversalTestLine('OK', 'Rollback confirmado: COBRO, CANCELACION, Caja, auditoria, saldo y estado quedaron iguales.');
    cxcReversalTestLine('OK', 'Prueba de reversion CxC-Caja completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    cxcReversalTestLine('ERROR', $e->getMessage());
    exit(1);
}
