<?php
/**
 * Prueba rollback Fase 5E-D-A para pago laboral con Caja.
 *
 * Ejecuta escrituras dentro de una transaccion externa y revierte al final.
 * Si no hay trabajador elegible, crea trabajador/concepto temporal dentro de
 * la misma transaccion. No deja pagos laborales, movimientos de Caja,
 * auditoria, trabajadores ni conceptos persistentes.
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
require_once dirname(__DIR__, 2) . '/app/services/TrabajadorPagoCajaService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function workerPayLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function workerPayCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    return (int)$stmt->fetchColumn();
}

function workerPayScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function workerPayFetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function workerPayEligibleWorker(PDO $pdo): ?array
{
    $stmt = $pdo->query(
        "SELECT t.id,
                t.hotel_id,
                t.nombre_completo,
                cc.id AS corte_id,
                (
                    COALESCE(lp.a_favor, 0)
                    - COALESCE(lp.en_contra, 0)
                    - COALESCE(la.saldo, 0)
                    - COALESCE(lpr.saldo, 0)
                    - COALESCE(pc.monto, 0)
                ) AS saldo_disponible
         FROM trabajadores t
         INNER JOIN cortes_caja cc
            ON cc.hotel_id = t.hotel_id
           AND cc.estado = 'abierto'
         INNER JOIN cajas c
            ON c.id = cc.caja_id
           AND c.hotel_id = cc.hotel_id
           AND COALESCE(c.activa, 1) = 1
         LEFT JOIN (
            SELECT hotel_id,
                   trabajador_id,
                   SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END) AS a_favor,
                   SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END) AS en_contra
            FROM trabajador_pagos
            GROUP BY hotel_id, trabajador_id
         ) lp ON lp.hotel_id = t.hotel_id AND lp.trabajador_id = t.id
         LEFT JOIN (
            SELECT hotel_id,
                   trabajador_id,
                   SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END) AS saldo
            FROM trabajador_anticipos
            GROUP BY hotel_id, trabajador_id
         ) la ON la.hotel_id = t.hotel_id AND la.trabajador_id = t.id
         LEFT JOIN (
            SELECT hotel_id,
                   trabajador_id,
                   SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END) AS saldo
            FROM trabajador_prestamos
            GROUP BY hotel_id, trabajador_id
         ) lpr ON lpr.hotel_id = t.hotel_id AND lpr.trabajador_id = t.id
         LEFT JOIN (
            SELECT hotel_id,
                   trabajador_id,
                   SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) AS monto
            FROM trabajador_pagos_caja
            GROUP BY hotel_id, trabajador_id
         ) pc ON pc.hotel_id = t.hotel_id AND pc.trabajador_id = t.id
         WHERE t.estado = 'activo'
         HAVING saldo_disponible >= 1
         ORDER BY t.id ASC
         LIMIT 1"
    );
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function workerPayOpenCut(PDO $pdo): ?array
{
    return workerPayFetchOne(
        $pdo,
        "SELECT cc.id AS corte_id,
                cc.hotel_id
         FROM cortes_caja cc
         INNER JOIN cajas c
            ON c.id = cc.caja_id
           AND c.hotel_id = cc.hotel_id
           AND COALESCE(c.activa, 1) = 1
         WHERE cc.estado = 'abierto'
         ORDER BY cc.fecha_apertura DESC, cc.id DESC
         LIMIT 1"
    );
}

function workerPayUserForHotel(PDO $pdo, int $hotelId): int
{
    $usuarioId = workerPayScalar(
        $pdo,
        "SELECT usuario_id
         FROM hotel_usuarios
         WHERE hotel_id = ?
           AND activo = 1
           AND usuario_id IS NOT NULL
         ORDER BY usuario_id ASC
         LIMIT 1",
        [$hotelId]
    );
    if (!$usuarioId) {
        $usuarioId = workerPayScalar($pdo, 'SELECT id FROM usuarios ORDER BY id ASC LIMIT 1');
    }
    if (!$usuarioId) {
        throw new RuntimeException('No hay usuario valido para created_by/updated_by en prueba rollback.');
    }

    return (int)$usuarioId;
}

function workerPayCreateTemporaryWorker(PDO $pdo, int $hotelId, int $usuarioId, string $stamp): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO trabajadores
            (hotel_id, usuario_id, nombre_completo, identificacion, rol_laboral,
             telefono, email, estado, fecha_alta, fecha_baja, salario_base,
             periodicidad_pago, notas, created_by, updated_by, created_at, updated_at)
         VALUES
            (?, NULL, ?, ?, 'QA rollback', NULL, NULL, 'activo', CURDATE(), NULL, 0.00,
             'por_evento', ?, ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $hotelId,
        'QA rollback pago laboral Caja',
        'QA-ROLLBACK-5E-D-A-' . $stamp,
        'Trabajador temporal creado dentro de rollback 5E-D-A. No debe persistir.',
        $usuarioId,
        $usuarioId,
    ]);

    return (int)$pdo->lastInsertId();
}

function workerPayCreateTemporaryConcept(PDO $pdo, int $hotelId, int $trabajadorId, int $usuarioId, string $stamp): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO trabajador_pagos
            (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
             periodo_inicio, periodo_fin, fecha, referencia, notas, estado,
             created_by, updated_by, created_at, updated_at)
         VALUES
            (?, ?, 'bono', 'a_favor', '10.00', 'Saldo temporal rollback Caja',
             NULL, NULL, CURDATE(), ?, 'Concepto temporal rollback 5E-D-A. No debe persistir.', 'activo',
             ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $hotelId,
        $trabajadorId,
        'QA-ROLLBACK-5E-D-A-LEDGER-' . $stamp,
        $usuarioId,
        $usuarioId,
    ]);

    return (int)$pdo->lastInsertId();
}

try {
    $trabajador = workerPayEligibleWorker($pdo);
    $openCut = $trabajador ? null : workerPayOpenCut($pdo);
    if (!$trabajador && !$openCut) {
        throw new RuntimeException('No hay trabajador elegible ni corte abierto para crear dato temporal rollback.');
    }

    $stamp = date('YmdHis');
    $hotelId = $trabajador ? (int)$trabajador['hotel_id'] : (int)$openCut['hotel_id'];
    $usuarioId = workerPayUserForHotel($pdo, $hotelId);
    $crearTemporal = !$trabajador;

    $pagosAntes = workerPayCountRows($pdo, 'trabajador_pagos_caja');
    $cajaAntes = workerPayCountRows($pdo, 'movimientos_caja');
    $logsAntes = workerPayCountRows($pdo, 'logs_auditoria');
    $trabajadoresAntes = workerPayCountRows($pdo, 'trabajadores');
    $conceptosAntes = workerPayCountRows($pdo, 'trabajador_pagos');

    if ($trabajador) {
        $trabajadorId = (int)$trabajador['id'];
        workerPayLine('OK', 'Trabajador elegido: #' . $trabajadorId . ', hotel ' . $hotelId . ', saldo disponible ' . number_format((float)$trabajador['saldo_disponible'], 2, '.', '') . '.');
        workerPayLine('OK', 'Corte abierto detectado: #' . (int)$trabajador['corte_id'] . '. Usuario prueba #' . $usuarioId . '.');
    } else {
        $trabajadorId = 0;
        workerPayLine('OK', 'No habia trabajador elegible; se creara dato laboral temporal dentro del rollback.');
        workerPayLine('OK', 'Corte abierto detectado: #' . (int)$openCut['corte_id'] . '. Hotel ' . $hotelId . '. Usuario prueba #' . $usuarioId . '.');
    }

    $pdo->beginTransaction();
    try {
        if ($crearTemporal) {
            $trabajadorId = workerPayCreateTemporaryWorker($pdo, $hotelId, $usuarioId, $stamp);
            $conceptoId = workerPayCreateTemporaryConcept($pdo, $hotelId, $trabajadorId, $usuarioId, $stamp);
            workerPayLine('OK', 'Trabajador temporal #' . $trabajadorId . ' y concepto laboral #' . $conceptoId . ' creados dentro de la transaccion.');
        }

        $service = new TrabajadorPagoCajaService($db, ['manage_transaction' => false]);
        $referenciaPrueba = 'TEST-ROLLBACK-5E-D-A-' . $stamp;
        $resultado = $service->registrarPago($hotelId, $trabajadorId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'concepto' => 'Prueba rollback pago laboral Caja',
            'notas' => 'Prueba rollback automatica Fase 5E-D-A. No debe persistir.',
        ], $usuarioId);

        $pagosDurante = workerPayCountRows($pdo, 'trabajador_pagos_caja');
        $cajaDurante = workerPayCountRows($pdo, 'movimientos_caja');
        $logsDurante = workerPayCountRows($pdo, 'logs_auditoria');

        if ($pagosDurante !== $pagosAntes + 1) {
            throw new RuntimeException('No se inserto exactamente un pago laboral Caja dentro de la transaccion.');
        }

        if ($cajaDurante !== $cajaAntes + 1) {
            throw new RuntimeException('No se inserto exactamente un movimiento Caja dentro de la transaccion.');
        }

        if ($logsDurante < $logsAntes + 1) {
            throw new RuntimeException('No se registro auditoria dentro de la transaccion.');
        }

        workerPayLine('OK', 'Pago laboral temporal #' . (int)$resultado['trabajador_pago_caja_id'] . ' creado.');
        workerPayLine('OK', 'Movimiento Caja temporal #' . (int)$resultado['movimiento_caja_id'] . ' creado.');
        workerPayLine('OK', 'Saldo temporal posterior estimado: ' . number_format((float)$resultado['saldo_posterior_estimado'], 2, '.', '') . '.');

        try {
            $service->registrarPago($hotelId, $trabajadorId, [
                'monto' => '1.00',
                'metodo_pago' => 'transferencia',
                'referencia' => $referenciaPrueba,
                'concepto' => 'Intento duplicado esperado',
                'notas' => 'Intento duplicado esperado dentro de rollback.',
            ], $usuarioId);
            throw new RuntimeException('La referencia duplicada fue aceptada indebidamente.');
        } catch (Throwable $duplicado) {
            if (strpos($duplicado->getMessage(), 'misma referencia') === false) {
                throw $duplicado;
            }
            workerPayLine('OK', 'Referencia duplicada bloqueada limpiamente dentro de la transaccion.');
        }

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $pagosDespues = workerPayCountRows($pdo, 'trabajador_pagos_caja');
    $cajaDespues = workerPayCountRows($pdo, 'movimientos_caja');
    $logsDespues = workerPayCountRows($pdo, 'logs_auditoria');
    $trabajadoresDespues = workerPayCountRows($pdo, 'trabajadores');
    $conceptosDespues = workerPayCountRows($pdo, 'trabajador_pagos');
    $referenciaDespues = workerPayCountRows(
        $pdo,
        'trabajador_pagos_caja',
        "referencia LIKE 'TEST-ROLLBACK-5E-D-A-%'"
    );

    if ($pagosDespues !== $pagosAntes || $cajaDespues !== $cajaAntes || $logsDespues !== $logsAntes) {
        throw new RuntimeException('El rollback no dejo pagos Caja, movimientos Caja y auditoria iguales a los iniciales.');
    }

    if ($trabajadoresDespues !== $trabajadoresAntes || $conceptosDespues !== $conceptosAntes) {
        throw new RuntimeException('El rollback no dejo trabajadores/conceptos laborales iguales a los iniciales.');
    }

    if ($referenciaDespues !== 0) {
        throw new RuntimeException('Quedaron referencias TEST-ROLLBACK-5E-D-A persistidas.');
    }

    workerPayLine('OK', 'Rollback confirmado: pagos laborales Caja, movimientos Caja, auditoria, trabajadores y conceptos quedaron iguales.');
    workerPayLine('OK', 'Prueba pago laboral-Caja reversible completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    workerPayLine('ERROR', $e->getMessage());
    exit(1);
}