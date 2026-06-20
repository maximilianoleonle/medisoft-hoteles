<?php
/**
 * Prueba rollback Fase 7B-D-C-A para cobro CxC con Caja.
 *
 * Ejecuta escrituras dentro de una transaccion externa y revierte al final.
 * No deja cobros, movimientos ni cambios de saldo persistentes.
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

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function cxcCashTestLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cxcCashCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    return (int)$stmt->fetchColumn();
}

function cxcCashScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $stmt = $pdo->query(
        "SELECT cxc.id,
                cxc.hotel_id,
                cxc.saldo,
                cxc.estado,
                h.nombre_completo AS huesped_nombre,
                cc.id AS corte_id
         FROM cuentas_por_cobrar cxc
         LEFT JOIN huespedes h
            ON h.id = cxc.huesped_id
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
        throw new RuntimeException('No hay CxC elegible con corte abierto para prueba rollback.');
    }

    $cuentaId = (int)$cuenta['id'];
    $hotelId = (int)$cuenta['hotel_id'];
    $saldoAntes = (string)$cuenta['saldo'];
    $estadoAntes = (string)$cuenta['estado'];
    $cxcMovsAntes = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos');
    $cobrosAntes = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
    $cajaMovsAntes = cxcCashCountRows($pdo, 'movimientos_caja');
    $logsAntes = cxcCashCountRows($pdo, 'logs_auditoria');

    cxcCashTestLine('OK', 'Cuenta elegida para rollback: CxC #' . $cuentaId . ', hotel ' . $hotelId . ', saldo ' . $saldoAntes . '.');
    cxcCashTestLine('OK', 'Corte abierto detectado: #' . (int)$cuenta['corte_id'] . '.');

    $pdo->beginTransaction();
    try {
        $service = new CuentaPorCobrarCobroService($db, ['manage_transaction' => false]);
        $referenciaPrueba = 'TEST-ROLLBACK-CXC-' . date('YmdHis');
        $resultado = $service->registrarCobro($hotelId, $cuentaId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'notas' => 'Prueba rollback automatica Fase 7B-D-C-A. No debe persistir.',
        ], 1);

        $cxcMovsDurante = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos');
        $cobrosDurante = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
        $cajaMovsDurante = cxcCashCountRows($pdo, 'movimientos_caja');
        $logsDurante = cxcCashCountRows($pdo, 'logs_auditoria');
        $saldoDurante = cxcCashScalar($pdo, 'SELECT saldo FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
        $tipoCajaDurante = cxcCashScalar($pdo, 'SELECT tipo FROM movimientos_caja WHERE id = ?', [(int)$resultado['movimiento_caja_id']]);
        $tipoMovDurante = cxcCashScalar($pdo, 'SELECT tipo_movimiento FROM cuentas_por_cobrar_movimientos WHERE id = ?', [(int)$resultado['cuenta_por_cobrar_movimiento_id']]);

        if ($cxcMovsDurante !== $cxcMovsAntes + 1 || $cobrosDurante !== $cobrosAntes + 1) {
            throw new RuntimeException('No se inserto exactamente un movimiento COBRO dentro de la transaccion.');
        }

        if ($cajaMovsDurante !== $cajaMovsAntes + 1 || $tipoCajaDurante !== 'ingreso') {
            throw new RuntimeException('No se inserto exactamente un ingreso de Caja dentro de la transaccion.');
        }

        if ($logsDurante < $logsAntes + 1) {
            throw new RuntimeException('No se registro auditoria dentro de la transaccion.');
        }

        if ((float)$saldoDurante !== (float)$saldoAntes - 1.00) {
            throw new RuntimeException('El saldo dentro de la transaccion no coincide con el cobro de prueba.');
        }

        if ($tipoMovDurante !== 'COBRO') {
            throw new RuntimeException('El movimiento CxC temporal no quedo como COBRO.');
        }

        cxcCashTestLine('OK', 'Movimiento CxC temporal #' . (int)$resultado['cuenta_por_cobrar_movimiento_id'] . ' creado.');
        cxcCashTestLine('OK', 'Movimiento Caja temporal #' . (int)$resultado['movimiento_caja_id'] . ' creado.');
        cxcCashTestLine('OK', 'Saldo temporal: ' . number_format((float)$saldoDurante, 2, '.', '') . '.');

        try {
            $service->registrarCobro($hotelId, $cuentaId, [
                'monto' => '1.00',
                'metodo_pago' => 'transferencia',
                'referencia' => $referenciaPrueba,
                'notas' => 'Intento duplicado esperado dentro de rollback.',
            ], 1);
            throw new RuntimeException('La referencia duplicada fue aceptada indebidamente.');
        } catch (Throwable $duplicado) {
            if (strpos($duplicado->getMessage(), 'misma referencia') === false) {
                throw $duplicado;
            }
            cxcCashTestLine('OK', 'Referencia duplicada bloqueada limpiamente dentro de la transaccion.');
        }

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $cxcMovsDespues = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos');
    $cobrosDespues = cxcCashCountRows($pdo, 'cuentas_por_cobrar_movimientos', "tipo_movimiento = 'COBRO'");
    $cajaMovsDespues = cxcCashCountRows($pdo, 'movimientos_caja');
    $logsDespues = cxcCashCountRows($pdo, 'logs_auditoria');
    $saldoDespues = cxcCashScalar($pdo, 'SELECT saldo FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
    $estadoDespues = cxcCashScalar($pdo, 'SELECT estado FROM cuentas_por_cobrar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);

    if ($cxcMovsDespues !== $cxcMovsAntes || $cobrosDespues !== $cobrosAntes || $cajaMovsDespues !== $cajaMovsAntes || $logsDespues !== $logsAntes) {
        throw new RuntimeException('El rollback no dejo los conteos iguales a los iniciales.');
    }

    if ((string)$saldoDespues !== $saldoAntes || (string)$estadoDespues !== $estadoAntes) {
        throw new RuntimeException('El rollback no dejo saldo/estado iguales a los iniciales.');
    }

    cxcCashTestLine('OK', 'Rollback confirmado: CxC movimientos, cobros, Caja, auditoria, saldo y estado quedaron iguales.');
    cxcCashTestLine('OK', 'Prueba CxC-Caja reversible completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    cxcCashTestLine('ERROR', $e->getMessage());
    exit(1);
}
