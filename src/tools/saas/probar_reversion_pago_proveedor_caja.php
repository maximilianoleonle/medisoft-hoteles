<?php
/**
 * Prueba rollback Fase 3D-D-A para reversion de pago proveedor con Caja.
 *
 * Ejecuta un pago temporal, lo revierte dentro de una transaccion externa
 * y revierte al final. No deja pagos, cancelaciones, movimientos de Caja,
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
require_once dirname(__DIR__, 2) . '/app/services/CuentaPorPagarPagoService.php';
require_once dirname(__DIR__, 2) . '/app/services/CuentaPorPagarReversionPagoService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function cxpReversalTestLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cxpReversalCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    return (int)$stmt->fetchColumn();
}

function cxpReversalScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $stmt = $pdo->query(
        "SELECT cxp.id AS cuenta_id,
                cxp.hotel_id,
                cxp.saldo,
                cxp.estado,
                cxp.total,
                cc.id AS corte_id
         FROM cuentas_por_pagar cxp
         INNER JOIN proveedores p
            ON p.id = cxp.proveedor_id
           AND p.hotel_id = cxp.hotel_id
           AND COALESCE(p.activo, 1) = 1
         INNER JOIN cortes_caja cc
            ON cc.hotel_id = cxp.hotel_id
           AND cc.estado = 'abierto'
         INNER JOIN cajas c
            ON c.id = cc.caja_id
           AND c.hotel_id = cc.hotel_id
           AND COALESCE(c.activa, 1) = 1
         LEFT JOIN compras co
            ON co.id = cxp.compra_id
           AND co.hotel_id = cxp.hotel_id
         WHERE cxp.estado IN ('pendiente', 'parcial', 'vencida')
           AND cxp.saldo >= 1
           AND cxp.total > 0
           AND cxp.saldo <= cxp.total
           AND (cxp.compra_id IS NULL OR (co.id IS NOT NULL AND co.estado = 'recibida'))
         ORDER BY cxp.id ASC
         LIMIT 1"
    );
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        throw new RuntimeException('No hay CxP elegible con corte abierto para prueba rollback de reversion.');
    }

    $cuentaId = (int)$cuenta['cuenta_id'];
    $hotelId = (int)$cuenta['hotel_id'];
    $saldoAntes = (string)$cuenta['saldo'];
    $estadoAntes = (string)$cuenta['estado'];

    $cxpMovsAntes = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos');
    $pagosAntes = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'PAGO_REFERENCIAL'");
    $cancelacionesAntes = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'CANCELACION'");
    $cajaMovsAntes = cxpReversalCountRows($pdo, 'movimientos_caja');
    $cajaPagosAntes = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Pago proveedor'");
    $cajaReversionesAntes = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Reversion Pago proveedor'");
    $logsAntes = cxpReversalCountRows($pdo, 'logs_auditoria');

    cxpReversalTestLine('OK', 'Cuenta elegida para rollback: CxP #' . $cuentaId . ', hotel ' . $hotelId . ', saldo ' . $saldoAntes . '.');
    cxpReversalTestLine('OK', 'Corte abierto detectado: #' . (int)$cuenta['corte_id'] . '.');

    $pdo->beginTransaction();
    try {
        $pagoService = new CuentaPorPagarPagoService($db, ['manage_transaction' => false]);
        $referenciaPrueba = 'TEST-ROLLBACK-CXP-REV-' . date('YmdHis');
        $pago = $pagoService->registrarPago($hotelId, $cuentaId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'notas' => 'Pago temporal para probar reversion rollback 3D-D-A. No debe persistir.',
        ], 1);

        $movimientoPagoId = (int)$pago['cuenta_por_pagar_movimiento_id'];
        $reversionService = new CuentaPorPagarReversionPagoService($db, ['manage_transaction' => false]);
        $resultado = $reversionService->revertirPago($hotelId, $cuentaId, $movimientoPagoId, [
            'motivo' => 'Prueba rollback automatica Fase 3D-D-A. No debe persistir.',
        ], 1);

        $cxpMovsDurante = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos');
        $pagosDurante = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'PAGO_REFERENCIAL'");
        $cancelacionesDurante = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'CANCELACION'");
        $cajaMovsDurante = cxpReversalCountRows($pdo, 'movimientos_caja');
        $cajaPagosDurante = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Pago proveedor'");
        $cajaReversionesDurante = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Reversion Pago proveedor'");
        $logsDurante = cxpReversalCountRows($pdo, 'logs_auditoria');
        $saldoDurante = cxpReversalScalar($pdo, 'SELECT saldo FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
        $estadoDurante = cxpReversalScalar($pdo, 'SELECT estado FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
        $tipoPagoDurante = cxpReversalScalar($pdo, 'SELECT tipo_movimiento FROM cuentas_por_pagar_movimientos WHERE id = ?', [$movimientoPagoId]);
        $tipoCancelacionDurante = cxpReversalScalar($pdo, 'SELECT tipo_movimiento FROM cuentas_por_pagar_movimientos WHERE id = ?', [(int)$resultado['movimiento_cancelacion_id']]);
        $tipoCajaPagoDurante = cxpReversalScalar($pdo, 'SELECT tipo FROM movimientos_caja WHERE id = ?', [(int)$pago['movimiento_caja_id']]);
        $tipoCajaReversionDurante = cxpReversalScalar($pdo, 'SELECT tipo FROM movimientos_caja WHERE id = ?', [(int)$resultado['movimiento_caja_reversion_id']]);

        if ($cxpMovsDurante !== $cxpMovsAntes + 2 || $pagosDurante !== $pagosAntes + 1 || $cancelacionesDurante !== $cancelacionesAntes + 1) {
            throw new RuntimeException('No se insertaron exactamente un PAGO_REFERENCIAL y una CANCELACION CxP dentro de la transaccion.');
        }

        if (
            $cajaMovsDurante !== $cajaMovsAntes + 2
            || $cajaPagosDurante !== $cajaPagosAntes + 1
            || $cajaReversionesDurante !== $cajaReversionesAntes + 1
            || $tipoCajaPagoDurante !== 'gasto'
            || $tipoCajaReversionDurante !== 'ingreso'
        ) {
            throw new RuntimeException('No se insertaron exactamente un gasto y un ingreso de Caja dentro de la transaccion.');
        }

        if ($logsDurante < $logsAntes + 2) {
            throw new RuntimeException('No se registro auditoria dentro de la transaccion.');
        }

        if ((float)$saldoDurante !== (float)$saldoAntes || (string)$estadoDurante !== $estadoAntes) {
            throw new RuntimeException('El saldo/estado dentro de la transaccion no regreso al valor inicial tras pagar y revertir.');
        }

        if ($tipoPagoDurante !== 'PAGO_REFERENCIAL') {
            throw new RuntimeException('El movimiento CxP temporal no quedo como PAGO_REFERENCIAL.');
        }

        if ($tipoCancelacionDurante !== 'CANCELACION') {
            throw new RuntimeException('El movimiento CxP de reversion no quedo como CANCELACION.');
        }

        cxpReversalTestLine('OK', 'Movimiento CxP PAGO temporal #' . $movimientoPagoId . ' creado.');
        cxpReversalTestLine('OK', 'Movimiento CxP CANCELACION temporal #' . (int)$resultado['movimiento_cancelacion_id'] . ' creado.');
        cxpReversalTestLine('OK', 'Movimiento Caja gasto temporal #' . (int)$pago['movimiento_caja_id'] . ' creado.');
        cxpReversalTestLine('OK', 'Movimiento Caja ingreso temporal #' . (int)$resultado['movimiento_caja_reversion_id'] . ' creado.');
        cxpReversalTestLine('OK', 'Saldo temporal: ' . number_format((float)$saldoDurante, 2, '.', '') . '.');

        try {
            $reversionService->revertirPago($hotelId, $cuentaId, $movimientoPagoId, [
                'motivo' => 'Intento duplicado esperado dentro de rollback.',
            ], 1);
            throw new RuntimeException('La doble reversion fue aceptada indebidamente.');
        } catch (Throwable $duplicado) {
            if (strpos($duplicado->getMessage(), 'reversion') === false) {
                throw $duplicado;
            }
            cxpReversalTestLine('OK', 'Doble reversion bloqueada limpiamente dentro de la transaccion.');
        }

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $cxpMovsDespues = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos');
    $pagosDespues = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'PAGO_REFERENCIAL'");
    $cancelacionesDespues = cxpReversalCountRows($pdo, 'cuentas_por_pagar_movimientos', "tipo_movimiento = 'CANCELACION'");
    $cajaMovsDespues = cxpReversalCountRows($pdo, 'movimientos_caja');
    $cajaPagosDespues = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'gasto' AND categoria = 'Pago proveedor'");
    $cajaReversionesDespues = cxpReversalCountRows($pdo, 'movimientos_caja', "tipo = 'ingreso' AND categoria = 'Reversion Pago proveedor'");
    $logsDespues = cxpReversalCountRows($pdo, 'logs_auditoria');
    $saldoDespues = cxpReversalScalar($pdo, 'SELECT saldo FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
    $estadoDespues = cxpReversalScalar($pdo, 'SELECT estado FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);

    if (
        $cxpMovsDespues !== $cxpMovsAntes
        || $pagosDespues !== $pagosAntes
        || $cancelacionesDespues !== $cancelacionesAntes
        || $cajaMovsDespues !== $cajaMovsAntes
        || $cajaPagosDespues !== $cajaPagosAntes
        || $cajaReversionesDespues !== $cajaReversionesAntes
        || $logsDespues !== $logsAntes
    ) {
        throw new RuntimeException('El rollback no dejo los conteos iguales a los iniciales.');
    }

    if ((string)$saldoDespues !== $saldoAntes || (string)$estadoDespues !== $estadoAntes) {
        throw new RuntimeException('El rollback no dejo saldo/estado iguales a los iniciales.');
    }

    cxpReversalTestLine('OK', 'Rollback confirmado: PAGO, CANCELACION, Caja, auditoria, saldo y estado quedaron iguales.');
    cxpReversalTestLine('OK', 'Prueba de reversion CxP-Caja completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    cxpReversalTestLine('ERROR', $e->getMessage());
    exit(1);
}
