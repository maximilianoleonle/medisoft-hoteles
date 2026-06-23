<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/TrabajadorPagoCajaService.php';

class TrabajadorNominaSnapshotPagoService
{
    private $db;
    private $pdo;
    private $pagoCajaService;
    private $manageTransaction;

    public function __construct(?Database $db = null, ?TrabajadorPagoCajaService $pagoCajaService = null, array $options = [])
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->manageTransaction = (bool)($options['manage_transaction'] ?? true);
        $this->pagoCajaService = $pagoCajaService ?: new TrabajadorPagoCajaService($this->db, [
            'manage_transaction' => false,
        ]);
    }

    public function tablasDisponibles(): bool
    {
        foreach ([
            'trabajador_nomina_periodos',
            'trabajador_nomina_periodo_detalles',
            'trabajador_pagos_caja',
            'movimientos_caja',
            'cortes_caja',
            'cajas',
            'trabajadores',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return $this->pagoCajaService->tablasDisponibles();
    }

    public function evaluarPagoDesdeSnapshot(int $hotelId, int $periodoId, int $detalleId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $periodoId = $this->validarId($periodoId, 'Periodo de pre-nomina invalido');
        $detalleId = $this->validarId($detalleId, 'Detalle de pre-nomina invalido');

        if (!$this->tablasDisponibles()) {
            return $this->evaluacionBloqueada('Faltan tablas requeridas para pagar desde snapshot de pre-nomina.');
        }

        $contexto = $this->obtenerContextoSnapshot($hotelId, $periodoId, $detalleId, false);

        return $this->evaluarContexto($contexto);
    }

    public function registrarPagoDesdeSnapshot(int $hotelId, int $periodoId, int $detalleId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $periodoId = $this->validarId($periodoId, 'Periodo de pre-nomina invalido');
        $detalleId = $this->validarId($detalleId, 'Detalle de pre-nomina invalido');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para pago desde snapshot de pre-nomina');

        if (!$this->tablasDisponibles()) {
            throw new Exception('Faltan tablas requeridas para pagar desde snapshot de pre-nomina');
        }

        $monto = $this->normalizarMonto($datos['monto'] ?? null);
        $metodoPago = $this->normalizarMetodoPago($datos['metodo_pago'] ?? null);
        $referencia = $this->normalizarTextoObligatorio((string)($datos['referencia'] ?? ''), 100);
        $notas = $this->normalizarTextoNullable($datos['notas'] ?? null, 700);

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $contexto = $this->obtenerContextoSnapshot($hotelId, $periodoId, $detalleId, true);
            $evaluacion = $this->evaluarContexto($contexto);

            if (empty($evaluacion['elegible'])) {
                throw new Exception($evaluacion['motivo_bloqueo'] ?: 'El detalle del snapshot no es elegible para pago con Caja');
            }

            $montoMaximo = (float)($evaluacion['monto_maximo'] ?? 0);
            if ($monto > $montoMaximo + 0.00001) {
                throw new Exception('El monto excede el maximo permitido entre snapshot aprobado y saldo vivo de Caja');
            }

            $periodo = $contexto['periodo'];
            $detalle = $contexto['detalle'];
            $trabajadorId = (int)($detalle['trabajador_id'] ?? 0);
            $concepto = $this->limitar(
                'Pago desde snapshot pre-nomina #' . $periodoId . ' detalle #' . $detalleId,
                160
            );
            $notaContexto = $this->limitar(
                'Origen: snapshot pre-nomina #' . $periodoId
                . ', detalle #' . $detalleId
                . ', periodo ' . (string)($periodo['fecha_inicio'] ?? '')
                . ' a ' . (string)($periodo['fecha_fin'] ?? '')
                . '. Snapshot pendiente: ' . $this->decimal($evaluacion['snapshot_pendiente'] ?? 0)
                . '. Saldo vivo: ' . $this->decimal($evaluacion['saldo_vivo'] ?? 0)
                . ($notas !== null ? '. Nota usuario: ' . $notas : ''),
                1000
            );

            $resultado = $this->pagoCajaService->registrarPago($hotelId, $trabajadorId, [
                'monto' => $this->decimal($monto),
                'metodo_pago' => $metodoPago,
                'referencia' => $referencia,
                'periodo_inicio' => $periodo['fecha_inicio'] ?? null,
                'periodo_fin' => $periodo['fecha_fin'] ?? null,
                'concepto' => $concepto,
                'notas' => $notaContexto,
                'nomina_periodo_id' => $periodoId,
                'nomina_periodo_detalle_id' => $detalleId,
            ], $usuarioId);

            AuditService::record('trabajadores.nomina_snapshot_pago_caja_registrado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_nomina_periodo_detalle',
                'entidad_id' => (string)$detalleId,
                'descripcion' => 'Pago laboral individual registrado desde snapshot aprobado de pre-nomina',
                'datos_antes' => [
                    'periodo_id' => $periodoId,
                    'detalle_id' => $detalleId,
                    'trabajador_id' => $trabajadorId,
                    'estado_snapshot' => $periodo['estado'] ?? null,
                    'snapshot_pendiente' => $this->decimal($evaluacion['snapshot_pendiente'] ?? 0),
                    'saldo_vivo' => $this->decimal($evaluacion['saldo_vivo'] ?? 0),
                    'monto_maximo' => $this->decimal($montoMaximo),
                ],
                'datos_despues' => [
                    'periodo_id' => $periodoId,
                    'detalle_id' => $detalleId,
                    'trabajador_id' => $trabajadorId,
                    'trabajador_pago_caja_id' => (int)($resultado['trabajador_pago_caja_id'] ?? 0),
                    'movimiento_caja_id' => (int)($resultado['movimiento_caja_id'] ?? 0),
                    'corte_id' => (int)($resultado['corte_id'] ?? 0),
                    'monto' => $this->decimal($monto),
                    'referencia' => $referencia,
                    'snapshot_inmutable' => true,
                    'sin_nomina_oficial' => true,
                    'sin_pago_masivo' => true,
                ],
            ]);

            $this->commitIfManaged();

            $resultado['periodo_id'] = $periodoId;
            $resultado['detalle_id'] = $detalleId;
            $resultado['monto_maximo_snapshot'] = $this->decimal($montoMaximo);

            return $resultado;
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function evaluarContexto(array $contexto): array
    {
        $periodo = $contexto['periodo'] ?? null;
        $detalle = $contexto['detalle'] ?? null;
        $bloqueos = [];

        if (!$periodo) {
            $bloqueos[] = 'Snapshot de pre-nomina no encontrado para el hotel actual.';
        }

        if (!$detalle) {
            $bloqueos[] = 'Detalle del snapshot no encontrado para el periodo actual.';
        }

        if ($periodo && (string)($periodo['estado'] ?? '') !== 'aprobado') {
            $bloqueos[] = 'Solo se puede pagar desde snapshots aprobados.';
        }

        if ($detalle && (string)($detalle['trabajador_estado'] ?? '') !== 'activo') {
            $bloqueos[] = 'El trabajador del snapshot no esta activo.';
        }

        if ($detalle && (string)($detalle['estado_preview_nomina'] ?? '') !== 'por_pagar') {
            $bloqueos[] = 'El detalle del snapshot no esta en estado por pagar.';
        }

        $snapshotPendiente = $detalle ? (float)($detalle['pendiente_pago_sugerido'] ?? 0) : 0.0;
        if ($snapshotPendiente <= 0.004) {
            $bloqueos[] = 'El pendiente del snapshot no es positivo.';
        }

        $evaluacionCaja = null;
        if ($periodo && $detalle) {
            $evaluacionCaja = $this->pagoCajaService->evaluarPago(
                (int)($periodo['hotel_id'] ?? 0),
                (int)($detalle['trabajador_id'] ?? 0),
                [
                    'periodo_inicio' => $periodo['fecha_inicio'] ?? null,
                    'periodo_fin' => $periodo['fecha_fin'] ?? null,
                ]
            );

            if (empty($evaluacionCaja['elegible'])) {
                $bloqueos[] = $evaluacionCaja['motivo_bloqueo'] ?? 'El saldo vivo de Caja no es elegible.';
            }
        }

        $saldoVivo = $evaluacionCaja ? (float)($evaluacionCaja['monto_maximo'] ?? 0) : 0.0;
        $montoMaximo = min($snapshotPendiente, $saldoVivo);
        if ($periodo && $detalle && $montoMaximo <= 0.004) {
            $bloqueos[] = 'No hay monto disponible entre snapshot aprobado y saldo vivo.';
        }

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Snapshot aprobado, detalle por pagar, saldo vivo positivo y corte de Caja disponible.'
                : '',
            'motivo_bloqueo' => implode(' ', array_values(array_filter($bloqueos))),
            'monto_maximo' => $this->decimal(max(0, $montoMaximo)),
            'snapshot_pendiente' => $this->decimal(max(0, $snapshotPendiente)),
            'saldo_vivo' => $this->decimal(max(0, $saldoVivo)),
            'metodos_pago' => $evaluacionCaja['metodos_pago'] ?? [
                'efectivo' => 'Efectivo',
                'tarjeta' => 'Tarjeta',
                'transferencia' => 'Transferencia',
            ],
            'corte' => $evaluacionCaja['corte'] ?? null,
            'periodo' => $periodo,
            'detalle' => $detalle,
        ];
    }

    private function evaluacionBloqueada(string $motivo): array
    {
        return [
            'elegible' => false,
            'motivo_elegibilidad' => '',
            'motivo_bloqueo' => $motivo,
            'monto_maximo' => '0.00',
            'snapshot_pendiente' => '0.00',
            'saldo_vivo' => '0.00',
            'metodos_pago' => [
                'efectivo' => 'Efectivo',
                'tarjeta' => 'Tarjeta',
                'transferencia' => 'Transferencia',
            ],
            'corte' => null,
        ];
    }

    private function obtenerContextoSnapshot(int $hotelId, int $periodoId, int $detalleId, bool $forUpdate): array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT id,
                    hotel_id,
                    tipo_periodo,
                    etiqueta,
                    fecha_inicio,
                    fecha_fin,
                    estado,
                    cerrado_at,
                    aprobado_at,
                    anulado_at
             FROM trabajador_nomina_periodos
             WHERE id = ?
               AND hotel_id = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$periodoId, $hotelId]);
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $detalle = null;
        if ($periodo) {
            $stmt = $this->pdo->prepare(
                "SELECT id,
                        periodo_id,
                        hotel_id,
                        trabajador_id,
                        trabajador_nombre,
                        trabajador_estado,
                        estado_preview_nomina,
                        motivo_bloqueo_nomina,
                        pendiente_pago_sugerido,
                        neto_sugerido,
                        pagos_caja_aplicados,
                        snapshot_json
                 FROM trabajador_nomina_periodo_detalles
                 WHERE id = ?
                   AND periodo_id = ?
                   AND hotel_id = ?
                 LIMIT 1" . $lock
            );
            $stmt->execute([$detalleId, $periodoId, $hotelId]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return [
            'periodo' => $periodo,
            'detalle' => $detalle,
        ];
    }

    private function validarId($value, string $message): int
    {
        $id = (int)$value;
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function normalizarMonto($value): float
    {
        $normalized = str_replace([',', ' '], ['', ''], trim((string)($value ?? '')));
        if ($normalized === '' || !is_numeric($normalized)) {
            throw new Exception('Monto invalido');
        }

        $monto = round((float)$normalized, 2);
        if ($monto <= 0) {
            throw new Exception('El monto debe ser mayor a cero');
        }

        return $monto;
    }

    private function normalizarMetodoPago($value): string
    {
        $metodo = trim((string)($value ?? ''));
        if (!in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'], true)) {
            throw new Exception('Metodo de pago invalido');
        }

        return $metodo;
    }

    private function normalizarTextoObligatorio(string $value, int $max): string
    {
        $value = $this->limpiarTexto($value);
        if ($value === '') {
            throw new Exception('Referencia obligatoria');
        }

        return $this->limitar($value, $max);
    }

    private function normalizarTextoNullable($value, int $max): ?string
    {
        $value = $this->limpiarTexto((string)($value ?? ''));
        if ($value === '') {
            return null;
        }

        return $this->limitar($value, $max);
    }

    private function limpiarTexto(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
    }

    private function limitar(string $value, int $max): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max, 'UTF-8');
        }

        return substr($value, 0, $max);
    }

    private function decimal($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$tabla]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('El pago desde snapshot debe controlar su propia transaccion');
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
}
