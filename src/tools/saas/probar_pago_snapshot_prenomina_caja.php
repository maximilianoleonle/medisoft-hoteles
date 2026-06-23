<?php
/**
 * Prueba rollback Fase 5E-N-A/5E-P-A para pago individual trazado desde
 * snapshot aprobado de pre-nomina.
 *
 * Crea trabajador, concepto laboral y snapshot aprobado temporales dentro de
 * una transaccion externa, registra un pago con Caja y revierte todo al final.
 * No deja pagos laborales, movimientos de Caja, auditoria ni snapshot persistidos.
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
require_once dirname(__DIR__, 2) . '/app/services/TrabajadorNominaSnapshotPagoService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function payrollSnapshotPayLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function payrollSnapshotPayCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $safeTable = str_replace('`', '``', $table);
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . $safeTable . '` WHERE ' . $where);

    return (int)$stmt->fetchColumn();
}

function payrollSnapshotPayScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function payrollSnapshotPayFetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function payrollSnapshotPayInsert(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$pdo->lastInsertId();
}

function payrollSnapshotPayOpenCut(PDO $pdo): ?array
{
    return payrollSnapshotPayFetchOne(
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

function payrollSnapshotPayUserForHotel(PDO $pdo, int $hotelId): int
{
    $usuarioId = payrollSnapshotPayScalar(
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
        $usuarioId = payrollSnapshotPayScalar(
            $pdo,
            "SELECT id
             FROM usuarios
             WHERE activo = 1
             ORDER BY id ASC
             LIMIT 1"
        );
    }

    if (!$usuarioId) {
        throw new RuntimeException('No hay usuario valido para created_by/updated_by en prueba rollback.');
    }

    return (int)$usuarioId;
}

function payrollSnapshotPayCreateWorker(PDO $pdo, int $hotelId, int $usuarioId, string $stamp, string $fechaAlta): int
{
    return payrollSnapshotPayInsert(
        $pdo,
        "INSERT INTO trabajadores
            (hotel_id, usuario_id, nombre_completo, identificacion, rol_laboral,
             telefono, email, estado, fecha_alta, fecha_baja, salario_base,
             periodicidad_pago, notas, created_by, updated_by, created_at, updated_at)
         VALUES
            (?, NULL, ?, ?, 'QA rollback', NULL, NULL, 'activo', ?, NULL, 0.00,
             'por_evento', ?, ?, ?, NOW(), NOW())",
        [
            $hotelId,
            'QA rollback pago snapshot pre-nomina',
            'QA-ROLLBACK-5E-N-A-' . $stamp,
            $fechaAlta,
            'Trabajador temporal rollback 5E-N-A. No debe persistir.',
            $usuarioId,
            $usuarioId,
        ]
    );
}

function payrollSnapshotPayCreateConcept(PDO $pdo, int $hotelId, int $trabajadorId, int $usuarioId, string $stamp, string $inicio, string $fin): int
{
    return payrollSnapshotPayInsert(
        $pdo,
        "INSERT INTO trabajador_pagos
            (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
             periodo_inicio, periodo_fin, fecha, referencia, notas, estado,
             created_by, updated_by, created_at, updated_at)
         VALUES
            (?, ?, 'bono', 'a_favor', '10.00', 'Saldo temporal pago snapshot',
             ?, ?, ?, ?, 'Concepto temporal rollback 5E-N-A. No debe persistir.',
             'activo', ?, ?, NOW(), NOW())",
        [
            $hotelId,
            $trabajadorId,
            $inicio,
            $fin,
            $inicio,
            'QA-ROLLBACK-5E-N-A-LEDGER-' . $stamp,
            $usuarioId,
            $usuarioId,
        ]
    );
}

function payrollSnapshotPayCreateApprovedPeriod(PDO $pdo, int $hotelId, int $usuarioId, string $stamp, string $inicio, string $fin): int
{
    return payrollSnapshotPayInsert(
        $pdo,
        "INSERT INTO trabajador_nomina_periodos
            (hotel_id, tipo_periodo, etiqueta, fecha_inicio, fecha_fin, estado,
             filtros_json, resumen_json, trabajadores_total, bruto_total, deducciones_total,
             pagos_caja_aplicados_total, reversiones_detectadas_total, neto_sugerido_total,
             pendiente_pago_total, cerrado_por, cerrado_at, aprobado_por, aprobado_at,
             created_at, updated_at)
         VALUES
            (?, 'manual', ?, ?, ?, 'aprobado',
             JSON_OBJECT('qa', '5E-N-A rollback'), JSON_OBJECT('qa', '5E-N-A rollback'),
             1, '10.00', '0.00', '0.00', '0.00', '10.00', '10.00',
             ?, NOW(), ?, NOW(), NOW(), NOW())",
        [
            $hotelId,
            'QA rollback pago snapshot ' . $stamp,
            $inicio,
            $fin,
            $usuarioId,
            $usuarioId,
        ]
    );
}

function payrollSnapshotPayCreateDetail(PDO $pdo, int $periodoId, int $hotelId, int $trabajadorId, string $stamp): int
{
    return payrollSnapshotPayInsert(
        $pdo,
        "INSERT INTO trabajador_nomina_periodo_detalles
            (periodo_id, hotel_id, trabajador_id, trabajador_nombre, trabajador_identificacion,
             trabajador_rol, trabajador_estado, estado_preview_nomina, motivo_bloqueo_nomina,
             conceptos_count, conceptos_a_favor, conceptos_en_contra, bruto_periodo,
             anticipos_count, anticipos_saldo, prestamos_count, prestamos_saldo,
             deducciones_informativas, pagos_caja_count, pagos_caja_pagados,
             pagos_caja_revertidos, pagos_caja_aplicados, pagos_caja_revertidos_total,
             reversiones_detectadas, ultimo_pago_caja, neto_sugerido,
             pendiente_pago_sugerido, snapshot_json, created_at)
         VALUES
            (?, ?, ?, 'QA rollback pago snapshot pre-nomina', ?, 'QA rollback',
             'activo', 'por_pagar', NULL, 1, '10.00', '0.00', '10.00',
             0, '0.00', 0, '0.00', '0.00', 0, 0, 0, '0.00', '0.00',
             '0.00', NULL, '10.00', '10.00', JSON_OBJECT('qa', '5E-N-A rollback'), NOW())",
        [
            $periodoId,
            $hotelId,
            $trabajadorId,
            'QA-ROLLBACK-5E-N-A-' . $stamp,
        ]
    );
}

try {
    $openCut = payrollSnapshotPayOpenCut($pdo);
    if (!$openCut) {
        throw new RuntimeException('No hay corte de Caja abierto para probar pago desde snapshot.');
    }

    $stamp = date('YmdHis');
    $hotelId = (int)$openCut['hotel_id'];
    $usuarioId = payrollSnapshotPayUserForHotel($pdo, $hotelId);
    $inicio = (new DateTimeImmutable('+25 years +' . (int)date('s') . ' days'))->format('Y-m-d');
    $fin = (new DateTimeImmutable($inicio . ' +6 days'))->format('Y-m-d');

    $countsBefore = [
        'trabajadores' => payrollSnapshotPayCountRows($pdo, 'trabajadores'),
        'trabajador_pagos' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos'),
        'trabajador_nomina_periodos' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodos'),
        'trabajador_nomina_periodo_detalles' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodo_detalles'),
        'trabajador_pagos_caja' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos_caja'),
        'movimientos_caja' => payrollSnapshotPayCountRows($pdo, 'movimientos_caja'),
        'logs_auditoria' => payrollSnapshotPayCountRows($pdo, 'logs_auditoria'),
    ];

    payrollSnapshotPayLine('OK', 'Corte abierto #' . (int)$openCut['corte_id'] . ', hotel #' . $hotelId . ', usuario #' . $usuarioId . '.');

    $pdo->beginTransaction();
    try {
        $trabajadorId = payrollSnapshotPayCreateWorker($pdo, $hotelId, $usuarioId, $stamp, $inicio);
        $conceptoId = payrollSnapshotPayCreateConcept($pdo, $hotelId, $trabajadorId, $usuarioId, $stamp, $inicio, $fin);
        $periodoId = payrollSnapshotPayCreateApprovedPeriod($pdo, $hotelId, $usuarioId, $stamp, $inicio, $fin);
        $detalleId = payrollSnapshotPayCreateDetail($pdo, $periodoId, $hotelId, $trabajadorId, $stamp);

        payrollSnapshotPayLine('OK', 'Datos temporales: trabajador #' . $trabajadorId . ', concepto #' . $conceptoId . ', snapshot #' . $periodoId . ', detalle #' . $detalleId . '.');

        $service = new TrabajadorNominaSnapshotPagoService($db, null, ['manage_transaction' => false]);
        $evaluacion = $service->evaluarPagoDesdeSnapshot($hotelId, $periodoId, $detalleId);
        if (empty($evaluacion['elegible'])) {
            throw new RuntimeException('La evaluacion temporal no fue elegible: ' . (string)($evaluacion['motivo_bloqueo'] ?? 'sin detalle'));
        }

        if ((float)($evaluacion['monto_maximo'] ?? 0) < 1.0) {
            throw new RuntimeException('El monto maximo temporal no permite pagar 1.00.');
        }

        $referenciaPrueba = 'TEST-ROLLBACK-5E-N-A-' . $stamp;
        $resultado = $service->registrarPagoDesdeSnapshot($hotelId, $periodoId, $detalleId, [
            'monto' => '1.00',
            'metodo_pago' => 'transferencia',
            'referencia' => $referenciaPrueba,
            'notas' => 'Prueba rollback automatica Fase 5E-N-A. No debe persistir.',
        ], $usuarioId);

        $countsDuring = [
            'trabajadores' => payrollSnapshotPayCountRows($pdo, 'trabajadores'),
            'trabajador_pagos' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos'),
            'trabajador_nomina_periodos' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodos'),
            'trabajador_nomina_periodo_detalles' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodo_detalles'),
            'trabajador_pagos_caja' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos_caja'),
            'movimientos_caja' => payrollSnapshotPayCountRows($pdo, 'movimientos_caja'),
            'logs_auditoria' => payrollSnapshotPayCountRows($pdo, 'logs_auditoria'),
        ];

        foreach ([
            'trabajadores',
            'trabajador_pagos',
            'trabajador_nomina_periodos',
            'trabajador_nomina_periodo_detalles',
            'trabajador_pagos_caja',
            'movimientos_caja',
        ] as $table) {
            if (($countsDuring[$table] ?? 0) !== ($countsBefore[$table] ?? 0) + 1) {
                throw new RuntimeException('No se inserto exactamente un registro temporal en ' . $table . '.');
            }
        }

        if (($countsDuring['logs_auditoria'] ?? 0) < ($countsBefore['logs_auditoria'] ?? 0) + 1) {
            throw new RuntimeException('No se registro auditoria para el pago desde snapshot.');
        }

        payrollSnapshotPayLine('OK', 'Pago snapshot temporal #' . (int)($resultado['trabajador_pago_caja_id'] ?? 0) . ' creado.');
        payrollSnapshotPayLine('OK', 'Movimiento Caja temporal #' . (int)($resultado['movimiento_caja_id'] ?? 0) . ' creado.');

        $pagoTrazado = payrollSnapshotPayFetchOne(
            $pdo,
            "SELECT nomina_periodo_id,
                    nomina_periodo_detalle_id,
                    trabajador_id,
                    hotel_id
             FROM trabajador_pagos_caja
             WHERE id = ?",
            [(int)($resultado['trabajador_pago_caja_id'] ?? 0)]
        );
        if (!$pagoTrazado) {
            throw new RuntimeException('No se pudo releer el pago temporal para validar trazabilidad 5E-P-A.');
        }
        if ((int)($pagoTrazado['nomina_periodo_id'] ?? 0) !== $periodoId) {
            throw new RuntimeException('El pago temporal no quedo ligado al periodo de snapshot correcto.');
        }
        if ((int)($pagoTrazado['nomina_periodo_detalle_id'] ?? 0) !== $detalleId) {
            throw new RuntimeException('El pago temporal no quedo ligado al detalle de snapshot correcto.');
        }
        if ((int)($pagoTrazado['trabajador_id'] ?? 0) !== $trabajadorId || (int)($pagoTrazado['hotel_id'] ?? 0) !== $hotelId) {
            throw new RuntimeException('La trazabilidad temporal no coincide con hotel/trabajador.');
        }
        payrollSnapshotPayLine('OK', 'Trazabilidad 5E-P-A temporal ligada a periodo #' . $periodoId . ' y detalle #' . $detalleId . '.');

        $snapshotPendienteDespues = payrollSnapshotPayScalar(
            $pdo,
            "SELECT pendiente_pago_sugerido
             FROM trabajador_nomina_periodo_detalles
             WHERE id = ?",
            [$detalleId]
        );
        if (number_format((float)$snapshotPendienteDespues, 2, '.', '') !== '10.00') {
            throw new RuntimeException('El snapshot fue mutado indebidamente durante el pago.');
        }
        payrollSnapshotPayLine('OK', 'Snapshot temporal permanecio inmutable tras registrar pago.');

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $countsAfter = [
        'trabajadores' => payrollSnapshotPayCountRows($pdo, 'trabajadores'),
        'trabajador_pagos' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos'),
        'trabajador_nomina_periodos' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodos'),
        'trabajador_nomina_periodo_detalles' => payrollSnapshotPayCountRows($pdo, 'trabajador_nomina_periodo_detalles'),
        'trabajador_pagos_caja' => payrollSnapshotPayCountRows($pdo, 'trabajador_pagos_caja'),
        'movimientos_caja' => payrollSnapshotPayCountRows($pdo, 'movimientos_caja'),
        'logs_auditoria' => payrollSnapshotPayCountRows($pdo, 'logs_auditoria'),
    ];

    foreach ($countsBefore as $table => $before) {
        if (($countsAfter[$table] ?? null) !== $before) {
            throw new RuntimeException('Rollback inconsistente en ' . $table . ': antes=' . $before . ', despues=' . ($countsAfter[$table] ?? 'N/D') . '.');
        }
    }

    $persistidos = payrollSnapshotPayCountRows(
        $pdo,
        'trabajador_pagos_caja',
        "referencia LIKE 'TEST-ROLLBACK-5E-N-A-%'"
    ) + payrollSnapshotPayCountRows(
        $pdo,
        'trabajador_pagos',
        "referencia LIKE 'QA-ROLLBACK-5E-N-A-%'"
    );
    if ($persistidos !== 0) {
        throw new RuntimeException('Quedaron referencias TEST/QA-ROLLBACK-5E-N-A persistidas.');
    }

    payrollSnapshotPayLine('OK', 'Rollback confirmado: pago, Caja, auditoria y snapshot quedaron iguales.');
    payrollSnapshotPayLine('OK', 'Prueba 5E-N-A/5E-P-A reversible completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    payrollSnapshotPayLine('ERROR', $e->getMessage());
    exit(1);
}
