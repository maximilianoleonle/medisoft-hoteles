<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class TrabajadorPagoCajaService
{
    private $db;
    private $pdo;
    private $manageTransaction;

    public function __construct(?Database $db = null, array $options = [])
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->manageTransaction = (bool)($options['manage_transaction'] ?? true);
    }

    public function tablasDisponibles(): bool
    {
        foreach ([
            'trabajadores',
            'trabajador_pagos',
            'trabajador_anticipos',
            'trabajador_prestamos',
            'trabajador_pagos_caja',
            'cajas',
            'cortes_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function evaluarPago(int $hotelId, int $trabajadorId, array $filtros = []): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $trabajadorId = $this->validarId($trabajadorId, 'Trabajador invalido');
        $periodoInicio = $this->normalizarFechaNullable($filtros['periodo_inicio'] ?? null, 'Periodo inicial invalido');
        $periodoFin = $this->normalizarFechaNullable($filtros['periodo_fin'] ?? null, 'Periodo final invalido');
        if ($periodoInicio !== null && $periodoFin !== null && $periodoFin < $periodoInicio) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'El periodo evaluado no es valido.',
                'corte' => null,
                'metodos_pago' => $this->metodosPago(),
                'monto_maximo' => '0.00',
            ];
        }

        if (!$this->tablasDisponibles()) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'Faltan tablas requeridas para pago laboral con Caja.',
                'corte' => null,
                'metodos_pago' => $this->metodosPago(),
                'monto_maximo' => '0.00',
            ];
        }

        $trabajador = $this->obtenerTrabajador($hotelId, $trabajadorId, false);
        $corte = $this->obtenerCorteAbierto($hotelId, false);
        $saldo = $trabajador ? $this->saldoLaboralDisponible($hotelId, $trabajadorId, $periodoInicio, $periodoFin) : $this->saldoVacio();
        $bloqueos = $this->bloqueosPago($trabajador, $corte, (float)$saldo['saldo_disponible']);

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Trabajador activo, saldo laboral positivo, corte de Caja abierto y servicio controlado.'
                : '',
            'motivo_bloqueo' => implode(' ', $bloqueos),
            'corte' => $corte,
            'metodos_pago' => $this->metodosPago(),
            'monto_maximo' => $this->decimal($saldo['saldo_disponible']),
            'saldo' => $saldo,
            'periodo_inicio' => $periodoInicio,
            'periodo_fin' => $periodoFin,
        ];
    }

    public function registrarPago(int $hotelId, int $trabajadorId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $trabajadorId = $this->validarId($trabajadorId, 'Trabajador invalido');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para registrar pago laboral con Caja');

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas requeridas para pago laboral con Caja no disponibles');
        }

        $monto = $this->normalizarMonto($datos['monto'] ?? null);
        $metodoPago = $this->normalizarMetodoPago($datos['metodo_pago'] ?? null);
        $referencia = $this->normalizarTextoObligatorio((string)($datos['referencia'] ?? ''), 100);
        $concepto = $this->normalizarTextoNullable($datos['concepto'] ?? null, 160);
        $notas = $this->normalizarTextoNullable($datos['notas'] ?? null, 1000);
        $periodoInicio = $this->normalizarFechaNullable($datos['periodo_inicio'] ?? null, 'Periodo inicial invalido');
        $periodoFin = $this->normalizarFechaNullable($datos['periodo_fin'] ?? null, 'Periodo final invalido');
        if ($periodoInicio !== null && $periodoFin !== null && $periodoFin < $periodoInicio) {
            throw new Exception('El periodo del pago laboral no es valido');
        }

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $trabajador = $this->obtenerTrabajador($hotelId, $trabajadorId, true);
            $corte = $this->obtenerCorteAbierto($hotelId, true);
            $this->bloquearFilasLaborales($hotelId, $trabajadorId);
            $saldo = $trabajador ? $this->saldoLaboralDisponible($hotelId, $trabajadorId, $periodoInicio, $periodoFin) : $this->saldoVacio();

            $bloqueos = $this->bloqueosPago($trabajador, $corte, (float)$saldo['saldo_disponible']);
            if ($bloqueos) {
                throw new Exception(implode(' ', $bloqueos));
            }

            $saldoDisponible = (float)$saldo['saldo_disponible'];
            if ($monto > $saldoDisponible + 0.00001) {
                throw new Exception('El monto no puede ser mayor al saldo laboral disponible');
            }

            $this->assertReferenciaNoDuplicada($hotelId, $referencia);

            $montoDecimal = $this->decimal($monto);
            $saldoAnteriorDecimal = $this->decimal($saldoDisponible);
            $saldoPosteriorDecimal = $this->decimal(max(0, $saldoDisponible - $monto));
            $conceptoFinal = $concepto ?: $this->normalizarTextoObligatorio('Pago laboral trabajador #' . $trabajadorId, 160);
            $descripcionCaja = $this->normalizarTextoObligatorio(
                'Pago laboral trabajador #' . $trabajadorId . ' - ' . (string)($trabajador['nombre_completo'] ?? 'Trabajador'),
                255
            );

            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'gasto', 'Pago laboral', NULL, ?, ?, ?, ?, NULL, ?, NULL, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $montoDecimal,
                $metodoPago,
                $referencia,
                $this->normalizarTextoNullable('Trabajador: ' . (string)($trabajador['nombre_completo'] ?? ''), 200),
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                "INSERT INTO trabajador_pagos_caja
                    (hotel_id, trabajador_id, movimiento_caja_id, corte_id, monto,
                     metodo_pago, referencia, periodo_inicio, periodo_fin, concepto,
                     fecha_pago, estado, notas, created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pagado', ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $trabajadorId,
                $movimientoCajaId,
                (int)$corte['id'],
                $montoDecimal,
                $metodoPago,
                $referencia,
                $periodoInicio,
                $periodoFin,
                $conceptoFinal,
                $notas,
                $usuarioId,
                $usuarioId,
            ]);
            $pagoCajaId = (int)$this->pdo->lastInsertId();

            AuditService::record('trabajadores.pago_laboral_caja_registrado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_pago_caja',
                'entidad_id' => (string)$pagoCajaId,
                'descripcion' => 'Pago laboral registrado con movimiento de Caja controlado',
                'datos_antes' => [
                    'trabajador_id' => $trabajadorId,
                    'saldo_disponible' => $saldoAnteriorDecimal,
                    'pagos_caja_total' => $this->decimal($saldo['pagos_caja_total'] ?? 0),
                ],
                'datos_despues' => [
                    'trabajador_id' => $trabajadorId,
                    'pago_caja_id' => $pagoCajaId,
                    'movimiento_caja_id' => $movimientoCajaId,
                    'corte_id' => (int)$corte['id'],
                    'monto' => $montoDecimal,
                    'saldo_posterior_estimado' => $saldoPosteriorDecimal,
                    'metodo_pago' => $metodoPago,
                    'referencia' => $referencia,
                    'periodo_inicio' => $periodoInicio,
                    'periodo_fin' => $periodoFin,
                ],
            ]);

            $this->commitIfManaged();

            return [
                'trabajador_id' => $trabajadorId,
                'trabajador_pago_caja_id' => $pagoCajaId,
                'movimiento_caja_id' => $movimientoCajaId,
                'corte_id' => (int)$corte['id'],
                'monto' => $montoDecimal,
                'saldo_anterior' => $saldoAnteriorDecimal,
                'saldo_posterior_estimado' => $saldoPosteriorDecimal,
                'referencia' => $referencia,
            ];
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function obtenerTrabajador(int $hotelId, int $trabajadorId, bool $forUpdate): ?array
    {
        $sql = "SELECT id,
                       hotel_id,
                       nombre_completo,
                       identificacion,
                       rol_laboral,
                       estado
                FROM trabajadores
                WHERE hotel_id = ?
                  AND id = ?
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId, $trabajadorId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function obtenerCorteAbierto(int $hotelId, bool $forUpdate): ?array
    {
        $sql = "SELECT cc.id,
                       cc.hotel_id,
                       cc.caja_id,
                       cc.fecha_apertura,
                       cc.monto_inicial,
                       cc.estado,
                       c.nombre AS caja_nombre
                FROM cortes_caja cc
                INNER JOIN cajas c
                   ON c.id = cc.caja_id
                  AND c.hotel_id = cc.hotel_id
                WHERE cc.hotel_id = ?
                  AND cc.estado = 'abierto'
                  AND COALESCE(c.activa, 1) = 1
                ORDER BY cc.fecha_apertura DESC, cc.id DESC
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function bloquearFilasLaborales(int $hotelId, int $trabajadorId): void
    {
        foreach (['trabajador_pagos', 'trabajador_anticipos', 'trabajador_prestamos', 'trabajador_pagos_caja'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                continue;
            }

            $stmt = $this->pdo->prepare(
                'SELECT id FROM `' . str_replace('`', '``', $tabla) . '` WHERE hotel_id = ? AND trabajador_id = ? FOR UPDATE'
            );
            $stmt->execute([$hotelId, $trabajadorId]);
            $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    private function saldoLaboralDisponible(int $hotelId, int $trabajadorId, ?string $periodoInicio, ?string $periodoFin): array
    {
        $resumen = $this->saldoVacio();

        $where = ['hotel_id = ?', 'trabajador_id = ?'];
        $params = [$hotelId, $trabajadorId];
        if ($periodoInicio !== null) {
            $where[] = 'COALESCE(periodo_fin, fecha) >= ?';
            $params[] = $periodoInicio;
        }
        if ($periodoFin !== null) {
            $where[] = 'COALESCE(periodo_inicio, fecha) <= ?';
            $params[] = $periodoFin;
        }

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'a_favor' THEN monto ELSE 0 END), 0) AS a_favor,
                    COALESCE(SUM(CASE WHEN estado = 'activo' AND efecto = 'en_contra' THEN monto ELSE 0 END), 0) AS en_contra
             FROM trabajador_pagos
             WHERE " . implode(' AND ', $where)
        );
        $stmt->execute($params);
        $conceptos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
             FROM trabajador_anticipos
             WHERE hotel_id = ?
               AND trabajador_id = ?"
        );
        $stmt->execute([$hotelId, $trabajadorId]);
        $anticipos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN estado = 'vigente' THEN saldo_pendiente ELSE 0 END), 0) AS saldo
             FROM trabajador_prestamos
             WHERE hotel_id = ?
               AND trabajador_id = ?"
        );
        $stmt->execute([$hotelId, $trabajadorId]);
        $prestamos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $wherePagos = ['hotel_id = ?', 'trabajador_id = ?'];
        $paramsPagos = [$hotelId, $trabajadorId];
        if ($periodoInicio !== null) {
            $wherePagos[] = 'COALESCE(periodo_fin, DATE(fecha_pago)) >= ?';
            $paramsPagos[] = $periodoInicio;
        }
        if ($periodoFin !== null) {
            $wherePagos[] = 'COALESCE(periodo_inicio, DATE(fecha_pago)) <= ?';
            $paramsPagos[] = $periodoFin;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END), 0) AS monto
             FROM trabajador_pagos_caja
             WHERE " . implode(' AND ', $wherePagos)
        );
        $stmt->execute($paramsPagos);
        $pagosCaja = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $aFavor = (float)($conceptos['a_favor'] ?? 0);
        $enContra = (float)($conceptos['en_contra'] ?? 0);
        $anticiposSaldo = (float)($anticipos['saldo'] ?? 0);
        $prestamosSaldo = (float)($prestamos['saldo'] ?? 0);
        $pagosCajaTotal = (float)($pagosCaja['monto'] ?? 0);
        $base = $aFavor - $enContra - $anticiposSaldo - $prestamosSaldo;

        $resumen['conceptos_count'] = (int)($conceptos['total'] ?? 0);
        $resumen['conceptos_a_favor'] = $this->decimal($aFavor);
        $resumen['conceptos_en_contra'] = $this->decimal($enContra);
        $resumen['anticipos_count'] = (int)($anticipos['total'] ?? 0);
        $resumen['anticipos_saldo'] = $this->decimal($anticiposSaldo);
        $resumen['prestamos_count'] = (int)($prestamos['total'] ?? 0);
        $resumen['prestamos_saldo'] = $this->decimal($prestamosSaldo);
        $resumen['pagos_caja_count'] = (int)($pagosCaja['total'] ?? 0);
        $resumen['pagos_caja_total'] = $this->decimal($pagosCajaTotal);
        $resumen['saldo_base'] = $this->decimal($base);
        $resumen['saldo_disponible'] = $this->decimal($base - $pagosCajaTotal);

        return $resumen;
    }

    private function saldoVacio(): array
    {
        return [
            'conceptos_count' => 0,
            'conceptos_a_favor' => '0.00',
            'conceptos_en_contra' => '0.00',
            'anticipos_count' => 0,
            'anticipos_saldo' => '0.00',
            'prestamos_count' => 0,
            'prestamos_saldo' => '0.00',
            'pagos_caja_count' => 0,
            'pagos_caja_total' => '0.00',
            'saldo_base' => '0.00',
            'saldo_disponible' => '0.00',
        ];
    }

    private function bloqueosPago(?array $trabajador, ?array $corte, float $saldoDisponible): array
    {
        $bloqueos = [];
        if (!$trabajador) {
            $bloqueos[] = 'Trabajador no encontrado para el hotel actual.';
        } elseif (($trabajador['estado'] ?? '') !== 'activo') {
            $bloqueos[] = 'El trabajador no esta activo.';
        }

        if (!$corte) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }

        if ($saldoDisponible <= 0.004) {
            $bloqueos[] = 'El saldo laboral disponible no es positivo.';
        }

        return $bloqueos;
    }

    private function assertReferenciaNoDuplicada(int $hotelId, string $referencia): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM trabajador_pagos_caja
             WHERE hotel_id = ?
               AND referencia = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $referencia]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Ya existe un pago laboral con la misma referencia');
        }

        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND referencia = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $referencia]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Ya existe un movimiento de Caja con la misma referencia');
        }
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('El pago laboral debe controlar su propia transaccion');
        }

        if (!$this->manageTransaction && !$this->pdo->inTransaction()) {
            throw new Exception('El modo de prueba requiere una transaccion externa activa');
        }
    }

    private function beginTransactionIfManaged(): void
    {
        if ($this->manageTransaction) {
            $this->pdo->beginTransaction();
        }
    }

    private function commitIfManaged(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    private function metodosPago(): array
    {
        return [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
        ];
    }

    private function normalizarMetodoPago($value): string
    {
        $metodo = trim((string)($value ?? ''));
        if (!array_key_exists($metodo, $this->metodosPago())) {
            throw new Exception('Metodo de pago invalido');
        }

        return $metodo;
    }

    private function normalizarMonto($value): float
    {
        $monto = round((float)$value, 2);
        if ($monto <= 0) {
            throw new Exception('El monto del pago laboral debe ser mayor a 0');
        }

        return $monto;
    }

    private function normalizarTextoNullable($value, int $maxLength): ?string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        return $this->limitar($texto, $maxLength);
    }

    private function normalizarTextoObligatorio(string $value, int $maxLength): string
    {
        $texto = trim($value);
        if ($texto === '') {
            throw new Exception('Referencia requerida para pago laboral con Caja');
        }

        return $this->limitar($texto, $maxLength);
    }

    private function normalizarFechaNullable($value, string $message): ?string
    {
        $fecha = trim((string)($value ?? ''));
        if ($fecha === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$date || $date->format('Y-m-d') !== $fecha) {
            throw new Exception($message);
        }

        return $fecha;
    }

    private function validarId($value, string $message): int
    {
        $id = (int)($value ?? 0);
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1"
        );
        $stmt->execute([$tabla]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }

    private function limitar(string $value, int $maxLength): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }
}
