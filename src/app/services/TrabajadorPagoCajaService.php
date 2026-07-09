<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class TrabajadorPagoCajaService
{
    private $db;
    private $pdo;
    private $manageTransaction;
    private $snapshotTraceColumnsAvailable = null;

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
        $trazabilidadSnapshot = $this->normalizarTrazabilidadSnapshot($datos);
        if ($periodoInicio !== null && $periodoFin !== null && $periodoFin < $periodoInicio) {
            throw new Exception('El periodo del pago laboral no es valido');
        }

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $trabajador = $this->obtenerTrabajador($hotelId, $trabajadorId, true);
            $corte = $this->obtenerCorteAbierto($hotelId, true);
            $this->bloquearFilasLaborales($hotelId, $trabajadorId);
            $trazabilidadDisponible = $this->trazabilidadSnapshotDisponible();
            if (!$trazabilidadSnapshot['requiere_trazabilidad'] && $trazabilidadDisponible) {
                $trazabilidadSnapshot = $this->inferirTrazabilidadSnapshotNominaV2(
                    $hotelId,
                    $trabajadorId,
                    $monto,
                    $periodoInicio,
                    $periodoFin
                );

                if ($trazabilidadSnapshot['requiere_trazabilidad']) {
                    $periodoInicio = $periodoInicio ?: ($trazabilidadSnapshot['periodo_inicio'] ?? null);
                    $periodoFin = $periodoFin ?: ($trazabilidadSnapshot['periodo_fin'] ?? null);
                    if ($concepto === null && !empty($trazabilidadSnapshot['concepto_sugerido'])) {
                        $concepto = $this->normalizarTextoNullable($trazabilidadSnapshot['concepto_sugerido'], 160);
                    }
                    if (!empty($trazabilidadSnapshot['nota_sistema'])) {
                        $notas = $this->anexarNotaSistema($notas, (string)$trazabilidadSnapshot['nota_sistema']);
                    }
                }
            }
            if ($trazabilidadSnapshot['requiere_trazabilidad'] && !$trazabilidadDisponible) {
                throw new Exception('La trazabilidad de snapshot aun no esta disponible en trabajador_pagos_caja');
            }
            if ($trazabilidadSnapshot['requiere_trazabilidad']) {
                $this->validarTrazabilidadSnapshot(
                    $hotelId,
                    $trabajadorId,
                    (int)$trazabilidadSnapshot['nomina_periodo_id'],
                    (int)$trazabilidadSnapshot['nomina_periodo_detalle_id'],
                    true
                );
            }
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

            if ($trazabilidadDisponible) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO trabajador_pagos_caja
                        (hotel_id, trabajador_id, movimiento_caja_id, corte_id,
                         nomina_periodo_id, nomina_periodo_detalle_id, monto,
                         metodo_pago, referencia, periodo_inicio, periodo_fin, concepto,
                         fecha_pago, estado, notas, created_by, updated_by, created_at, updated_at)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pagado', ?, ?, ?, NOW(), NOW())"
                );
                $stmt->execute([
                    $hotelId,
                    $trabajadorId,
                    $movimientoCajaId,
                    (int)$corte['id'],
                    $trazabilidadSnapshot['nomina_periodo_id'],
                    $trazabilidadSnapshot['nomina_periodo_detalle_id'],
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
            } else {
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
            }
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
                    'nomina_periodo_id' => $trazabilidadSnapshot['nomina_periodo_id'],
                    'nomina_periodo_detalle_id' => $trazabilidadSnapshot['nomina_periodo_detalle_id'],
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
                'nomina_periodo_id' => $trazabilidadSnapshot['nomina_periodo_id'],
                'nomina_periodo_detalle_id' => $trazabilidadSnapshot['nomina_periodo_detalle_id'],
            ];
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function evaluarReversion(int $hotelId, int $trabajadorId, int $pagoCajaId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $trabajadorId = $this->validarId($trabajadorId, 'Trabajador invalido');
        $pagoCajaId = $this->validarId($pagoCajaId, 'Pago laboral con Caja invalido');

        if (!$this->tablasDisponibles()) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'Faltan tablas requeridas para reversion de pago laboral con Caja.',
                'corte' => null,
                'monto' => '0.00',
                'referencia_reversion' => null,
            ];
        }

        $trabajador = $this->obtenerTrabajador($hotelId, $trabajadorId, false);
        $pago = $this->obtenerPagoCaja($hotelId, $trabajadorId, $pagoCajaId, false);
        $corte = $this->obtenerCorteAbierto($hotelId, false);
        $referenciaReversion = $this->referenciaReversion($trabajadorId, $pagoCajaId);
        $bloqueos = $this->bloqueosReversion($trabajador, $pago, $corte, $referenciaReversion, false);

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Pago laboral elegible para reversion controlada con Caja.'
                : '',
            'motivo_bloqueo' => implode(' ', $bloqueos),
            'corte' => $corte,
            'monto' => $pago ? $this->decimal($pago['monto'] ?? 0) : '0.00',
            'referencia_reversion' => $referenciaReversion,
        ];
    }

    public function revertirPago(int $hotelId, int $trabajadorId, int $pagoCajaId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $trabajadorId = $this->validarId($trabajadorId, 'Trabajador invalido');
        $pagoCajaId = $this->validarId($pagoCajaId, 'Pago laboral con Caja invalido');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para revertir pago laboral con Caja');
        $motivo = $this->normalizarMotivoReversion($datos['motivo'] ?? null);

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas requeridas para reversion de pago laboral con Caja no disponibles');
        }

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $trabajador = $this->obtenerTrabajador($hotelId, $trabajadorId, true);
            $corte = $this->obtenerCorteAbierto($hotelId, true);
            $this->bloquearFilasLaborales($hotelId, $trabajadorId);
            $pago = $this->obtenerPagoCaja($hotelId, $trabajadorId, $pagoCajaId, true);
            $referenciaReversion = $this->referenciaReversion($trabajadorId, $pagoCajaId);
            $bloqueos = $this->bloqueosReversion($trabajador, $pago, $corte, $referenciaReversion, true);
            if ($bloqueos) {
                throw new Exception(implode(' ', $bloqueos));
            }

            $saldo = $this->saldoLaboralDisponible($hotelId, $trabajadorId, null, null);
            $monto = (float)$pago['monto'];
            $montoDecimal = $this->decimal($monto);
            $saldoAnteriorDecimal = $this->decimal($saldo['saldo_disponible'] ?? 0);
            $saldoPosteriorDecimal = $this->decimal((float)($saldo['saldo_disponible'] ?? 0) + $monto);
            $metodoPago = $this->normalizarMetodoPago($pago['metodo_pago'] ?? $pago['movimiento_metodo_pago'] ?? null);
            $descripcionCaja = $this->normalizarTextoObligatorio(
                'Reversion pago laboral trabajador #' . $trabajadorId . ' pago #' . $pagoCajaId,
                255
            );

            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'ingreso', 'Reversion Pago laboral', NULL, ?, ?, ?, ?, NULL, ?, NULL, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $montoDecimal,
                $metodoPago,
                $referenciaReversion,
                $this->normalizarTextoNullable('Trabajador: ' . (string)($trabajador['nombre_completo'] ?? ''), 200),
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaReversionId = (int)$this->pdo->lastInsertId();

            $notaReversion = $this->limitar(
                'Reversion controlada ' . date('Y-m-d H:i:s') . '. Motivo: ' . $motivo . '. Movimiento Caja reversion #' . $movimientoCajaReversionId . '.',
                1000
            );
            $notasPrevias = trim((string)($pago['notas'] ?? ''));
            $espacioPrevio = max(0, 1000 - strlen($notaReversion) - 2);
            $notasPrevias = $espacioPrevio > 0 ? $this->limitar($notasPrevias, $espacioPrevio) : '';
            $notasActualizadas = trim($notasPrevias !== '' ? $notasPrevias . "\n\n" . $notaReversion : $notaReversion);

            $stmt = $this->pdo->prepare(
                "UPDATE trabajador_pagos_caja
                 SET estado = 'revertido',
                     notas = ?,
                     updated_by = ?,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND trabajador_id = ?
                   AND estado = 'pagado'"
            );
            $stmt->execute([
                $notasActualizadas,
                $usuarioId,
                $pagoCajaId,
                $hotelId,
                $trabajadorId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('No se pudo marcar el pago laboral como revertido');
            }

            AuditService::record('trabajadores.pago_laboral_caja_revertido', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_pago_caja',
                'entidad_id' => (string)$pagoCajaId,
                'descripcion' => 'Pago laboral revertido con ingreso de Caja controlado',
                'datos_antes' => [
                    'trabajador_id' => $trabajadorId,
                    'estado' => $pago['estado'] ?? null,
                    'monto' => $montoDecimal,
                    'saldo_disponible' => $saldoAnteriorDecimal,
                    'movimiento_caja_original_id' => (int)($pago['movimiento_caja_id'] ?? 0),
                ],
                'datos_despues' => [
                    'trabajador_id' => $trabajadorId,
                    'estado' => 'revertido',
                    'monto' => $montoDecimal,
                    'movimiento_caja_reversion_id' => $movimientoCajaReversionId,
                    'corte_id' => (int)$corte['id'],
                    'referencia' => $referenciaReversion,
                    'saldo_posterior_estimado' => $saldoPosteriorDecimal,
                    'motivo' => $motivo,
                ],
            ]);

            $this->commitIfManaged();

            return [
                'trabajador_id' => $trabajadorId,
                'trabajador_pago_caja_id' => $pagoCajaId,
                'movimiento_caja_original_id' => (int)($pago['movimiento_caja_id'] ?? 0),
                'movimiento_caja_reversion_id' => $movimientoCajaReversionId,
                'corte_id' => (int)$corte['id'],
                'monto' => $montoDecimal,
                'saldo_anterior' => $saldoAnteriorDecimal,
                'saldo_posterior_estimado' => $saldoPosteriorDecimal,
                'referencia' => $referenciaReversion,
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

    private function obtenerPagoCaja(int $hotelId, int $trabajadorId, int $pagoCajaId, bool $forUpdate): ?array
    {
        $sql = "SELECT pc.*,
                       mc.tipo AS movimiento_tipo,
                       mc.categoria AS movimiento_categoria,
                       mc.monto AS movimiento_monto,
                       mc.metodo_pago AS movimiento_metodo_pago,
                       mc.referencia AS movimiento_referencia,
                       mc.descripcion AS movimiento_descripcion,
                       mc.corte_id AS movimiento_corte_id,
                       mc.created_at AS movimiento_created_at
                FROM trabajador_pagos_caja pc
                INNER JOIN movimientos_caja mc
                   ON mc.id = pc.movimiento_caja_id
                  AND mc.hotel_id = pc.hotel_id
                WHERE pc.hotel_id = ?
                  AND pc.trabajador_id = ?
                  AND pc.id = ?
                LIMIT 1";

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId, $trabajadorId, $pagoCajaId]);
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

    private function bloqueosReversion(?array $trabajador, ?array $pago, ?array $corte, string $referenciaReversion, bool $forUpdate): array
    {
        if (!$trabajador) {
            return ['Trabajador no encontrado para el hotel actual.'];
        }

        $bloqueos = [];
        if (!$pago) {
            $bloqueos[] = 'Pago laboral con Caja no encontrado para este trabajador.';
        }

        if (!$corte) {
            $bloqueos[] = 'No hay corte de Caja abierto para registrar la reversion.';
        }

        if ($pago && (string)($pago['estado'] ?? '') !== 'pagado') {
            $bloqueos[] = 'El pago laboral no esta en estado pagado o ya fue revertido.';
        }

        if ($pago && (float)($pago['monto'] ?? 0) <= 0) {
            $bloqueos[] = 'El pago laboral no tiene monto valido.';
        }

        if ($pago && (string)($pago['movimiento_tipo'] ?? '') !== 'gasto') {
            $bloqueos[] = 'El movimiento original de Caja no es un egreso.';
        }

        if ($pago && (string)($pago['movimiento_categoria'] ?? '') !== 'Pago laboral') {
            $bloqueos[] = 'El movimiento original de Caja no corresponde a Pago laboral.';
        }

        if ($pago && abs((float)($pago['movimiento_monto'] ?? 0) - (float)($pago['monto'] ?? 0)) > 0.004) {
            $bloqueos[] = 'El monto del pago laboral no coincide con el movimiento de Caja original.';
        }

        if ($pago && (int)($pago['movimiento_corte_id'] ?? 0) !== (int)($pago['corte_id'] ?? 0)) {
            $bloqueos[] = 'El corte del pago laboral no coincide con el corte del movimiento original.';
        }

        if ($this->existeReversionPrevia($trabajador ? (int)$trabajador['hotel_id'] : 0, $referenciaReversion, $forUpdate)) {
            $bloqueos[] = 'Este pago laboral ya tiene una reversion registrada.';
        }

        return $bloqueos;
    }

    private function existeReversionPrevia(int $hotelId, string $referenciaReversion, bool $forUpdate): bool
    {
        if ($hotelId <= 0 || trim($referenciaReversion) === '') {
            return false;
        }

        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND tipo = 'ingreso'
               AND categoria = 'Reversion Pago laboral'
               AND referencia = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$hotelId, $referenciaReversion]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
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

    private function normalizarTrazabilidadSnapshot(array $datos): array
    {
        $periodoRaw = $datos['nomina_periodo_id'] ?? null;
        $detalleRaw = $datos['nomina_periodo_detalle_id'] ?? null;
        $tienePeriodo = trim((string)($periodoRaw ?? '')) !== '';
        $tieneDetalle = trim((string)($detalleRaw ?? '')) !== '';

        if (!$tienePeriodo && !$tieneDetalle) {
            return $this->trazabilidadSnapshotVacia();
        }

        if ($tienePeriodo !== $tieneDetalle) {
            throw new Exception('La trazabilidad de snapshot requiere periodo y detalle juntos');
        }

        return [
            'requiere_trazabilidad' => true,
            'nomina_periodo_id' => $this->validarId($periodoRaw, 'Periodo de snapshot invalido para pago laboral'),
            'nomina_periodo_detalle_id' => $this->validarId($detalleRaw, 'Detalle de snapshot invalido para pago laboral'),
        ];
    }

    private function trazabilidadSnapshotVacia(): array
    {
        return [
            'requiere_trazabilidad' => false,
            'nomina_periodo_id' => null,
            'nomina_periodo_detalle_id' => null,
        ];
    }

    private function trazabilidadSnapshotDisponible(): bool
    {
        if ($this->snapshotTraceColumnsAvailable !== null) {
            return (bool)$this->snapshotTraceColumnsAvailable;
        }

        $this->snapshotTraceColumnsAvailable = $this->columnaExiste('trabajador_pagos_caja', 'nomina_periodo_id')
            && $this->columnaExiste('trabajador_pagos_caja', 'nomina_periodo_detalle_id');

        return (bool)$this->snapshotTraceColumnsAvailable;
    }

    private function inferirTrazabilidadSnapshotNominaV2(
        int $hotelId,
        int $trabajadorId,
        float $monto,
        ?string $periodoInicio,
        ?string $periodoFin
    ): array {
        if (!$this->tablasNominaSnapshotDisponibles()) {
            return $this->trazabilidadSnapshotVacia();
        }

        $where = [
            "p.hotel_id = ?",
            "p.estado = 'aprobado'",
            "p.motor = 'v2'",
            "d.trabajador_id = ?",
            "d.estado_preview_nomina = 'por_pagar'",
            "tp.estado = 'activo'",
            "tp.efecto = 'a_favor'",
        ];
        $params = [$hotelId, $trabajadorId];

        if ($periodoInicio !== null && $periodoFin !== null) {
            $where[] = 'p.fecha_inicio = ?';
            $where[] = 'p.fecha_fin = ?';
            $params[] = $periodoInicio;
            $params[] = $periodoFin;
        } elseif ($periodoInicio !== null) {
            $where[] = 'p.fecha_inicio = ?';
            $params[] = $periodoInicio;
        } elseif ($periodoFin !== null) {
            $where[] = 'p.fecha_fin = ?';
            $params[] = $periodoFin;
        }

        $stmt = $this->pdo->prepare(
            "SELECT p.id AS periodo_id,
                    p.fecha_inicio,
                    p.fecha_fin,
                    p.etiqueta,
                    d.id AS detalle_id,
                    d.neto_sugerido,
                    COALESCE((
                        SELECT SUM(pc.monto)
                        FROM trabajador_pagos_caja pc
                        WHERE pc.hotel_id = p.hotel_id
                          AND pc.trabajador_id = d.trabajador_id
                          AND pc.nomina_periodo_id = p.id
                          AND pc.nomina_periodo_detalle_id = d.id
                          AND pc.estado = 'pagado'
                    ), 0) AS pagos_trazados
             FROM trabajador_nomina_periodos p
             INNER JOIN trabajador_nomina_periodo_detalles d
                ON d.periodo_id = p.id
               AND d.hotel_id = p.hotel_id
             INNER JOIN trabajador_pagos tp
                ON tp.hotel_id = p.hotel_id
               AND tp.trabajador_id = d.trabajador_id
               AND tp.referencia = CONCAT('NOMV2-', p.id, '-', d.trabajador_id)
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.fecha_fin DESC, p.id DESC
             LIMIT 5"
        );
        $stmt->execute($params);

        $candidatos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pendiente = max(0.0, (float)($row['neto_sugerido'] ?? 0) - (float)($row['pagos_trazados'] ?? 0));
            if ($pendiente <= 0.004) {
                continue;
            }

            $row['pendiente_vivo'] = $pendiente;
            $candidatos[] = $row;
            if (count($candidatos) > 1) {
                break;
            }
        }

        if (count($candidatos) > 1) {
            throw new Exception('Hay mas de un periodo de nomina aprobado con saldo pendiente. Registra el pago desde el periodo correcto para no cruzar saldos.');
        }

        if (!$candidatos) {
            return $this->trazabilidadSnapshotVacia();
        }

        $candidato = $candidatos[0];
        $pendiente = (float)($candidato['pendiente_vivo'] ?? 0);
        if ($monto > $pendiente + 0.00001) {
            throw new Exception(
                'El monto excede el pendiente del periodo de nomina aprobado ($'
                . number_format($pendiente, 2)
                . '). Registra el pago desde el periodo o divide el pago.'
            );
        }

        $periodoId = (int)$candidato['periodo_id'];
        $detalleId = (int)$candidato['detalle_id'];
        $fechaInicio = (string)$candidato['fecha_inicio'];
        $fechaFin = (string)$candidato['fecha_fin'];

        return [
            'requiere_trazabilidad' => true,
            'nomina_periodo_id' => $periodoId,
            'nomina_periodo_detalle_id' => $detalleId,
            'periodo_inicio' => $fechaInicio,
            'periodo_fin' => $fechaFin,
            'concepto_sugerido' => 'Pago desde snapshot pre-nomina #' . $periodoId . ' detalle #' . $detalleId,
            'nota_sistema' => $this->limitar(
                'Trazabilidad automatica: pago ligado al periodo de nomina #'
                . $periodoId
                . ', detalle #'
                . $detalleId
                . ', periodo '
                . $fechaInicio
                . ' a '
                . $fechaFin
                . '.',
                1000
            ),
        ];
    }

    private function tablasNominaSnapshotDisponibles(): bool
    {
        foreach (['trabajador_nomina_periodos', 'trabajador_nomina_periodo_detalles', 'trabajador_pagos'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    private function validarTrazabilidadSnapshot(
        int $hotelId,
        int $trabajadorId,
        int $periodoId,
        int $detalleId,
        bool $forUpdate
    ): void {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT p.id AS periodo_id,
                    p.hotel_id AS periodo_hotel_id,
                    p.estado AS periodo_estado,
                    d.id AS detalle_id,
                    d.periodo_id AS detalle_periodo_id,
                    d.hotel_id AS detalle_hotel_id,
                    d.trabajador_id AS detalle_trabajador_id,
                    d.estado_preview_nomina AS detalle_estado
             FROM trabajador_nomina_periodos p
             INNER JOIN trabajador_nomina_periodo_detalles d
                ON d.periodo_id = p.id
               AND d.hotel_id = p.hotel_id
               AND d.id = ?
             WHERE p.id = ?
               AND p.hotel_id = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$detalleId, $periodoId, $hotelId]);
        $snapshot = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$snapshot) {
            throw new Exception('La trazabilidad de snapshot no pertenece al hotel actual');
        }

        if ((int)($snapshot['detalle_trabajador_id'] ?? 0) !== $trabajadorId) {
            throw new Exception('El detalle del snapshot no corresponde al trabajador pagado');
        }

        if ((string)($snapshot['periodo_estado'] ?? '') !== 'aprobado') {
            throw new Exception('Solo se puede trazar pago contra snapshot aprobado');
        }

        if ((string)($snapshot['detalle_estado'] ?? '') !== 'por_pagar') {
            throw new Exception('Solo se puede trazar pago contra detalle de snapshot por pagar');
        }
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

    private function referenciaReversion(int $trabajadorId, int $pagoCajaId): string
    {
        return 'REV-NOM-TRAB-' . $trabajadorId . '-PAGO-' . $pagoCajaId;
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

    private function normalizarMotivoReversion($value): string
    {
        $motivo = trim((string)($value ?? ''));
        if ($motivo === '') {
            throw new Exception('El motivo de reversion es obligatorio');
        }

        return $this->limitar($motivo, 1000);
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

    private function anexarNotaSistema(?string $notas, string $notaSistema): ?string
    {
        $notaSistema = trim($notaSistema);
        if ($notaSistema === '') {
            return $notas;
        }

        $notas = trim((string)($notas ?? ''));
        $texto = $notas !== '' ? $notas . "\n\n" . $notaSistema : $notaSistema;

        return $this->limitar($texto, 1000);
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

    private function columnaExiste(string $tabla, string $columna): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
             LIMIT 1"
        );
        $stmt->execute([$tabla, $columna]);

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
