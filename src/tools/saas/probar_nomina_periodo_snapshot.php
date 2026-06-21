<?php
/**
 * Prueba rollback Fase 5E-L-A para snapshots persistentes de pre-nomina.
 *
 * Crea hotel/trabajador/concepto temporal dentro de una transaccion externa,
 * ejecuta cierre, aprobacion y anulacion de snapshot, y revierte todo al final.
 * No deja hoteles, trabajadores, conceptos, snapshots, detalles ni eventos.
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
require_once dirname(__DIR__, 2) . '/app/models/Trabajador.php';
require_once dirname(__DIR__, 2) . '/app/services/TrabajadorNominaPeriodoService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function payrollSnapshotLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function payrollSnapshotCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $safeTable = str_replace('`', '``', $table);
    $stmt = $pdo->query('SELECT COUNT(*) FROM `' . $safeTable . '` WHERE ' . $where);

    return (int)$stmt->fetchColumn();
}

function payrollSnapshotScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function payrollSnapshotInsert(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int)$pdo->lastInsertId();
}

function payrollSnapshotUserId(PDO $pdo): int
{
    $usuarioId = payrollSnapshotScalar(
        $pdo,
        "SELECT id
         FROM usuarios
         WHERE activo = 1
         ORDER BY id ASC
         LIMIT 1"
    );

    if (!$usuarioId) {
        throw new RuntimeException('No hay usuario activo para created_by/cerrado_por en prueba rollback.');
    }

    return (int)$usuarioId;
}

function payrollSnapshotCreateHotel(PDO $pdo, string $stamp): int
{
    return payrollSnapshotInsert(
        $pdo,
        "INSERT INTO hoteles
            (nombre, slug, codigo, pais, zona_horaria, moneda_codigo, moneda_simbolo,
             activo, metadata, created_at, updated_at)
         VALUES
            (?, ?, ?, 'Mexico', 'America/Mexico_City', 'MXN', '$',
             1, JSON_OBJECT('qa', '5E-L-A rollback'), NOW(), NOW())",
        [
            'QA rollback pre-nomina ' . $stamp,
            'qa-rollback-5e-l-a-' . strtolower($stamp),
            'QA5ELA' . substr($stamp, -10),
        ]
    );
}

function payrollSnapshotLinkUser(PDO $pdo, int $hotelId, int $usuarioId): int
{
    return payrollSnapshotInsert(
        $pdo,
        "INSERT INTO hotel_usuarios
            (hotel_id, usuario_id, rol, es_principal, activo, permisos_json, created_at, updated_at)
         VALUES
            (?, ?, 'administrador', 1, 1, JSON_OBJECT('qa', '5E-L-A rollback'), NOW(), NOW())",
        [$hotelId, $usuarioId]
    );
}

function payrollSnapshotCreateWorker(PDO $pdo, int $hotelId, int $usuarioId, string $stamp, string $fechaAlta): int
{
    return payrollSnapshotInsert(
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
            'QA rollback snapshot pre-nomina',
            'QA-ROLLBACK-5E-L-A-' . $stamp,
            $fechaAlta,
            'Trabajador temporal creado dentro de rollback 5E-L-A. No debe persistir.',
            $usuarioId,
            $usuarioId,
        ]
    );
}

function payrollSnapshotCreateConcept(PDO $pdo, int $hotelId, int $trabajadorId, int $usuarioId, string $stamp, string $inicio, string $fin): int
{
    return payrollSnapshotInsert(
        $pdo,
        "INSERT INTO trabajador_pagos
            (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
             periodo_inicio, periodo_fin, fecha, referencia, notas, estado,
             created_by, updated_by, created_at, updated_at)
         VALUES
            (?, ?, 'bono', 'a_favor', '123.45', 'Saldo temporal snapshot',
             ?, ?, ?, ?, 'Concepto temporal rollback 5E-L-A. No debe persistir.',
             'activo', ?, ?, NOW(), NOW())",
        [
            $hotelId,
            $trabajadorId,
            $inicio,
            $fin,
            $inicio,
            'QA-ROLLBACK-5E-L-A-LEDGER-' . $stamp,
            $usuarioId,
            $usuarioId,
        ]
    );
}

try {
    $stamp = date('YmdHis');
    $usuarioId = payrollSnapshotUserId($pdo);
    $inicio = (new DateTimeImmutable('+20 years'))->format('Y-m-d');
    $fin = (new DateTimeImmutable('+20 years +6 days'))->format('Y-m-d');

    $countsBefore = [
        'hoteles' => payrollSnapshotCountRows($pdo, 'hoteles'),
        'hotel_usuarios' => payrollSnapshotCountRows($pdo, 'hotel_usuarios'),
        'trabajadores' => payrollSnapshotCountRows($pdo, 'trabajadores'),
        'trabajador_pagos' => payrollSnapshotCountRows($pdo, 'trabajador_pagos'),
        'trabajador_nomina_periodos' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodos'),
        'trabajador_nomina_periodo_detalles' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodo_detalles'),
        'trabajador_nomina_periodo_eventos' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodo_eventos'),
    ];

    $pdo->beginTransaction();
    try {
        $hotelId = payrollSnapshotCreateHotel($pdo, $stamp);
        payrollSnapshotLinkUser($pdo, $hotelId, $usuarioId);
        $trabajadorId = payrollSnapshotCreateWorker($pdo, $hotelId, $usuarioId, $stamp, $inicio);
        $conceptoId = payrollSnapshotCreateConcept($pdo, $hotelId, $trabajadorId, $usuarioId, $stamp, $inicio, $fin);

        payrollSnapshotLine('OK', 'Hotel temporal #' . $hotelId . ', trabajador #' . $trabajadorId . ' y concepto #' . $conceptoId . ' creados dentro de transaccion.');

        $trabajadorModel = new Trabajador();
        $service = new TrabajadorNominaPeriodoService($trabajadorModel, ['manage_transaction' => false]);
        $periodoId = $service->cerrarPeriodo($hotelId, [
            'tipo_periodo' => 'manual',
            'etiqueta' => 'QA rollback snapshot ' . $stamp,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'estado' => 'activos',
            'rol_laboral' => '',
            'incluir_pagos_caja' => '0',
        ], $usuarioId);

        $snapshot = $trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $hotelId);
        if (!$snapshot || (string)($snapshot['estado'] ?? '') !== 'cerrado') {
            throw new RuntimeException('El snapshot temporal no quedo en estado cerrado.');
        }

        if ((int)($snapshot['trabajadores_total'] ?? 0) !== 1 || count($snapshot['detalles'] ?? []) !== 1) {
            throw new RuntimeException('El snapshot temporal no guardo exactamente un trabajador/detalle.');
        }

        payrollSnapshotLine('OK', 'Snapshot temporal #' . $periodoId . ' cerrado con un detalle.');

        try {
            $service->cerrarPeriodo($hotelId, [
                'tipo_periodo' => 'manual',
                'etiqueta' => 'Duplicado esperado ' . $stamp,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'estado' => 'activos',
                'rol_laboral' => '',
                'incluir_pagos_caja' => '0',
            ], $usuarioId);
            throw new RuntimeException('El cierre duplicado fue aceptado indebidamente.');
        } catch (Throwable $duplicado) {
            if (strpos($duplicado->getMessage(), 'ya tiene un cierre') === false) {
                throw $duplicado;
            }
            payrollSnapshotLine('OK', 'Cierre duplicado bloqueado dentro de la transaccion.');
        }

        $service->aprobarPeriodo($hotelId, $periodoId, $usuarioId);
        $snapshot = $trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $hotelId);
        if (!$snapshot || (string)($snapshot['estado'] ?? '') !== 'aprobado') {
            throw new RuntimeException('El snapshot temporal no quedo en estado aprobado.');
        }
        payrollSnapshotLine('OK', 'Snapshot temporal aprobado administrativamente.');

        try {
            $service->anularPeriodo($hotelId, $periodoId, '', $usuarioId);
            throw new RuntimeException('La anulacion sin motivo fue aceptada indebidamente.');
        } catch (Throwable $sinMotivo) {
            if (strpos($sinMotivo->getMessage(), 'motivo') === false) {
                throw $sinMotivo;
            }
            payrollSnapshotLine('OK', 'Anulacion sin motivo bloqueada.');
        }

        $service->anularPeriodo($hotelId, $periodoId, 'Prueba rollback 5E-L-A', $usuarioId);
        $snapshot = $trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $hotelId);
        if (!$snapshot || (string)($snapshot['estado'] ?? '') !== 'anulado') {
            throw new RuntimeException('El snapshot temporal no quedo en estado anulado.');
        }

        if (count($snapshot['eventos'] ?? []) !== 3) {
            throw new RuntimeException('El snapshot temporal no registro los tres eventos esperados.');
        }
        payrollSnapshotLine('OK', 'Snapshot temporal anulado con evento y motivo.');

        $pdo->rollBack();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $countsAfter = [
        'hoteles' => payrollSnapshotCountRows($pdo, 'hoteles'),
        'hotel_usuarios' => payrollSnapshotCountRows($pdo, 'hotel_usuarios'),
        'trabajadores' => payrollSnapshotCountRows($pdo, 'trabajadores'),
        'trabajador_pagos' => payrollSnapshotCountRows($pdo, 'trabajador_pagos'),
        'trabajador_nomina_periodos' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodos'),
        'trabajador_nomina_periodo_detalles' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodo_detalles'),
        'trabajador_nomina_periodo_eventos' => payrollSnapshotCountRows($pdo, 'trabajador_nomina_periodo_eventos'),
    ];

    foreach ($countsBefore as $table => $before) {
        if (($countsAfter[$table] ?? null) !== $before) {
            throw new RuntimeException('Rollback inconsistente en ' . $table . ': antes=' . $before . ', despues=' . ($countsAfter[$table] ?? 'N/D') . '.');
        }
    }

    $persistidos = payrollSnapshotCountRows(
        $pdo,
        'hoteles',
        "slug LIKE 'qa-rollback-5e-l-a-%'"
    ) + payrollSnapshotCountRows(
        $pdo,
        'trabajador_pagos',
        "referencia LIKE 'QA-ROLLBACK-5E-L-A-%'"
    );
    if ($persistidos !== 0) {
        throw new RuntimeException('Quedaron datos temporales QA-ROLLBACK-5E-L-A persistidos.');
    }

    payrollSnapshotLine('OK', 'Rollback confirmado: hotel, trabajador, concepto, snapshot, detalles y eventos quedaron iguales.');
    payrollSnapshotLine('OK', 'Prueba 5E-L-A reversible completada sin cambios persistentes.');
    exit(0);
} catch (Throwable $e) {
    payrollSnapshotLine('ERROR', $e->getMessage());
    exit(1);
}
