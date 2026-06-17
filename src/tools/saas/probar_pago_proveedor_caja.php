<?php
/**
 * Prueba rollback Fase 3D para pago proveedor con Caja.
 *
 * Ejecuta escrituras dentro de una transaccion externa y revierte al final.
 * No deja pagos, movimientos ni cambios de saldo persistentes.
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

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function testLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function countRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    return (int)$stmt->fetchColumn();
}

function scalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

try {
    $stmt = $pdo->query(
        "SELECT cxp.id,
                cxp.hotel_id,
                cxp.saldo,
                cxp.estado,
                p.nombre AS proveedor_nombre,
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
           AND (cxp.compra_id IS NULL OR co.estado = 'recibida')
         ORDER BY cxp.id ASC
         LIMIT 1"
    );
    $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cuenta) {
        throw new RuntimeException('No hay CxP elegible con corte abierto para prueba rollback.');
    }

    $cuentaId = (int)$cuenta['id'];
    $hotelId = (int)$cuenta['hotel_id'];
    $saldoAntes = (string)$cuenta['saldo'];
    $estadoAntes = (string)$cuenta['estado'];
    $cxpMovsAntes = countRows($pdo, 'cuentas_por_pagar_movimientos');
    $cajaMovsAntes = countRows($pdo, 'movimientos_caja');
    $logsAntes = countRows($pdo, 'logs_auditoria');

    testLine('OK', 'Cuenta elegida para rollback: CxP #' . $cuentaId . ', hotel ' . $hotelId . ', saldo ' . $saldoAntes . '.');
    testLine('OK', 'Corte abierto detectado: #' . (int)$cuenta['corte_id'] . '.');

    $pdo->beginTransaction();
    try {
        $service = new CuentaPorPagarPagoService($db, ['manage_transaction' => false]);
        $referenciaPrueba = 'TEST-ROLLBACK-3D-' . date('YmdHis');
        $resultado = $service->registrarPago($hotelId, $cuentaId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'notas' => 'Prueba rollback automatica Fase 3D. No debe persistir.',
        ], 1);

        $cxpMovsDurante = countRows($pdo, 'cuentas_por_pagar_movimientos');
        $cajaMovsDurante = countRows($pdo, 'movimientos_caja');
        $logsDurante = countRows($pdo, 'logs_auditoria');
        $saldoDurante = scalar($pdo, 'SELECT saldo FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);

        if ($cxpMovsDurante !== $cxpMovsAntes + 1) {
            throw new RuntimeException('No se inserto exactamente un movimiento CxP dentro de la transaccion.');
        }

        if ($cajaMovsDurante !== $cajaMovsAntes + 1) {
            throw new RuntimeException('No se inserto exactamente un movimiento Caja dentro de la transaccion.');
        }

        if ($logsDurante < $logsAntes + 1) {
            throw new RuntimeException('No se registro auditoria dentro de la transaccion.');
        }

        if ((float)$saldoDurante !== (float)$saldoAntes - 1.00) {
            throw new RuntimeException('El saldo dentro de la transaccion no coincide con el pago de prueba.');
        }

        testLine('OK', 'Movimiento CxP temporal #' . (int)$resultado['cuenta_por_pagar_movimiento_id'] . ' creado.');
        testLine('OK', 'Movimiento Caja temporal #' . (int)$resultado['movimiento_caja_id'] . ' creado.');
        testLine('OK', 'Saldo temporal: ' . number_format((float)$saldoDurante, 2, '.', '') . '.');

        try {
            $service->registrarPago($hotelId, $cuentaId, [
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
            testLine('OK', 'Referencia duplicada bloqueada limpiamente dentro de la transaccion.');
        }

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $cxpMovsDespues = countRows($pdo, 'cuentas_por_pagar_movimientos');
    $cajaMovsDespues = countRows($pdo, 'movimientos_caja');
    $logsDespues = countRows($pdo, 'logs_auditoria');
    $saldoDespues = scalar($pdo, 'SELECT saldo FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);
    $estadoDespues = scalar($pdo, 'SELECT estado FROM cuentas_por_pagar WHERE id = ? AND hotel_id = ?', [$cuentaId, $hotelId]);

    if ($cxpMovsDespues !== $cxpMovsAntes || $cajaMovsDespues !== $cajaMovsAntes || $logsDespues !== $logsAntes) {
        throw new RuntimeException('El rollback no dejo los conteos iguales a los iniciales.');
    }

    if ((string)$saldoDespues !== $saldoAntes || (string)$estadoDespues !== $estadoAntes) {
        throw new RuntimeException('El rollback no dejo saldo/estado iguales a los iniciales.');
    }

    testLine('OK', 'Rollback confirmado: CxP movimientos, Caja, auditoria, saldo y estado quedaron iguales.');
    testLine('OK', 'Prueba proveedor-Caja reversible completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    testLine('ERROR', $e->getMessage());
    exit(1);
}
