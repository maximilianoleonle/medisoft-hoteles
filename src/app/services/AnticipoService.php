<?php

require_once __DIR__ . '/../../core/Database.php';

/**
 * AnticipoService
 *
 * Registra (y revierte) anticipos/abonos de una reservación como dinero REAL:
 *  - Inserta un movimiento de Caja (ingreso) dentro del corte abierto.
 *  - Inserta una fila en reservacion_abonos ligada a ese movimiento de Caja.
 * Con eso el saldo de la reservación baja y Cuentas por Cobrar lo refleja
 * automáticamente (ya lee reservacion_abonos).
 *
 * Regla de operación (consistente con CuentaPorCobrarCobroService): requiere
 * un corte de Caja abierto. Todo es transaccional: si algo falla, no queda
 * registro a medias.
 */
class AnticipoService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Tablas necesarias para registrar un anticipo con Caja. */
    public function tablasDisponibles(): bool
    {
        foreach (['reservaciones', 'reservacion_abonos', 'cajas', 'cortes_caja', 'movimientos_caja'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Evalúa si se puede registrar un anticipo ahora (corte abierto, saldo, etc.).
     * No escribe nada. Útil para pintar la UI.
     */
    public function evaluar(int $hotelId, int $reservacionId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $reservacionId = $this->validarId($reservacionId, 'Reservacion invalida');

        if (!$this->tablasDisponibles()) {
            return ['elegible' => false, 'motivo' => 'Faltan tablas requeridas (caja/abonos).', 'corte' => null, 'saldo' => 0.0, 'metodos_pago' => []];
        }

        $reservacion = $this->obtenerReservacion($hotelId, $reservacionId, false);
        $corte = $this->obtenerCorteAbierto($hotelId, false);
        $saldo = $reservacion ? $this->saldoPendiente($hotelId, $reservacionId, (float)$reservacion['precio_total']) : 0.0;

        $motivos = [];
        if (!$reservacion) {
            $motivos[] = 'Reservacion no encontrada para el hotel actual.';
        } elseif ((string)($reservacion['estado'] ?? '') === 'cancelada') {
            $motivos[] = 'La reservacion esta cancelada.';
        }
        if (!$corte) {
            $motivos[] = 'No hay corte de Caja abierto. Abre la caja para registrar el anticipo.';
        }
        if ($reservacion && $saldo <= 0.004) {
            $motivos[] = 'La reservacion no tiene saldo pendiente.';
        }

        return [
            'elegible' => empty($motivos),
            'motivo' => implode(' ', $motivos),
            'corte' => $corte,
            'saldo' => round($saldo, 2),
            'metodos_pago' => $this->metodosPago(),
        ];
    }

    /**
     * Registra el anticipo. $datos: monto, metodo_pago, referencia (opcional), notas (opcional), concepto (opcional).
     * Devuelve el detalle (abono_id, movimiento_caja_id, corte_id, saldo_anterior/posterior).
     */
    public function registrar(int $hotelId, int $reservacionId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $reservacionId = $this->validarId($reservacionId, 'Reservacion invalida');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para registrar el anticipo en Caja');

        if (!$this->tablasDisponibles()) {
            throw new Exception('No se puede registrar el anticipo: faltan tablas de Caja/abonos.');
        }

        $monto = $this->normalizarMonto($datos['monto'] ?? null);
        $metodoPago = $this->normalizarMetodoPago($datos['metodo_pago'] ?? null);
        $referencia = $this->normalizarTextoNullable($datos['referencia'] ?? null, 100);
        $concepto = $this->normalizarTextoNullable($datos['concepto'] ?? null, 255) ?? 'Anticipo de reservacion';

        if ($this->pdo->inTransaction()) {
            throw new Exception('El anticipo debe controlar su propia transaccion.');
        }

        $this->pdo->beginTransaction();
        try {
            $reservacion = $this->obtenerReservacion($hotelId, $reservacionId, true);
            if (!$reservacion) {
                throw new Exception('Reservacion no encontrada para el hotel actual.');
            }
            if ((string)($reservacion['estado'] ?? '') === 'cancelada') {
                throw new Exception('No se puede registrar un anticipo en una reservacion cancelada.');
            }

            $corte = $this->obtenerCorteAbierto($hotelId, true);
            if (!$corte) {
                throw new Exception('No hay corte de Caja abierto. Abre la caja para registrar el anticipo.');
            }

            $total = (float)$reservacion['precio_total'];
            $saldoAnterior = $this->saldoPendiente($hotelId, $reservacionId, $total);
            if ($saldoAnterior <= 0.004) {
                throw new Exception('La reservacion no tiene saldo pendiente.');
            }
            if ($monto > $saldoAnterior + 0.00001) {
                throw new Exception('El anticipo no puede ser mayor al saldo pendiente ($' . number_format($saldoAnterior, 2) . ').');
            }

            $descripcionCaja = $this->limitar(
                $concepto . ' - Reserva #' . $reservacionId,
                255
            );
            $referenciaCaja = $referencia ?: ('ANTICIPO-' . $reservacionId);

            // 1) Movimiento de Caja (ingreso) dentro del corte abierto
            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'ingreso', 'Anticipo reservacion', NULL, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $this->decimal($monto),
                $metodoPago,
                $this->normalizarTextoNullable($referenciaCaja, 100),
                $reservacionId,
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaId = (int)$this->pdo->lastInsertId();

            // 2) Abono de la reservación ligado al movimiento de Caja
            $stmt = $this->pdo->prepare(
                "INSERT INTO reservacion_abonos
                    (hotel_id, reservacion_id, monto, metodo_pago, noches_cubiertas,
                     concepto, referencia, usuario_id, corte_id, movimiento_caja_id,
                     created_at, requiere_factura, tipo_tarjeta)
                 VALUES
                    (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, NOW(), 'no', '')"
            );
            $stmt->execute([
                $hotelId,
                $reservacionId,
                $this->decimal($monto),
                $metodoPago,
                $this->limitar($concepto, 255),
                $referencia,
                $usuarioId,
                (int)$corte['id'],
                $movimientoCajaId,
            ]);
            $abonoId = (int)$this->pdo->lastInsertId();

            $saldoPosterior = max(0, round($saldoAnterior - $monto, 2));

            $this->pdo->commit();

            return [
                'abono_id' => $abonoId,
                'movimiento_caja_id' => $movimientoCajaId,
                'corte_id' => (int)$corte['id'],
                'monto' => round($monto, 2),
                'saldo_anterior' => round($saldoAnterior, 2),
                'saldo_posterior' => $saldoPosterior,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Revierte un anticipo: genera un contramovimiento de Caja (gasto) en el corte
     * abierto y elimina el abono. Solo posible si el corte del anticipo sigue abierto.
     */
    public function reversar(int $hotelId, int $reservacionId, int $abonoId, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $reservacionId = $this->validarId($reservacionId, 'Reservacion invalida');
        $abonoId = $this->validarId($abonoId, 'Anticipo invalido');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para revertir el anticipo');

        if (!$this->tablasDisponibles()) {
            throw new Exception('No se puede revertir el anticipo: faltan tablas de Caja/abonos.');
        }

        if ($this->pdo->inTransaction()) {
            throw new Exception('La reversion debe controlar su propia transaccion.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM reservacion_abonos
                 WHERE id = ? AND reservacion_id = ? AND hotel_id = ? LIMIT 1 FOR UPDATE"
            );
            $stmt->execute([$abonoId, $reservacionId, $hotelId]);
            $abono = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$abono) {
                throw new Exception('Anticipo no encontrado para esta reservacion.');
            }

            $corte = $this->obtenerCorteAbierto($hotelId, true);
            if (!$corte) {
                throw new Exception('No hay corte de Caja abierto para revertir el anticipo.');
            }
            if ((int)($abono['corte_id'] ?? 0) !== (int)$corte['id']) {
                throw new Exception('No se puede revertir: el anticipo pertenece a un corte de Caja ya cerrado.');
            }

            $monto = (float)$abono['monto'];

            // Contramovimiento de Caja (gasto) que cancela el ingreso del anticipo
            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'gasto', 'Reverso anticipo', NULL, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $this->limitar('Reverso de anticipo - Reserva #' . $reservacionId, 255),
                $this->decimal($monto),
                (string)($abono['metodo_pago'] ?? 'efectivo'),
                $this->normalizarTextoNullable('REV-ANTICIPO-' . $abonoId, 100),
                $reservacionId,
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoReversoId = (int)$this->pdo->lastInsertId();

            // Eliminar el abono (el saldo sube automáticamente)
            $stmt = $this->pdo->prepare(
                "DELETE FROM reservacion_abonos WHERE id = ? AND reservacion_id = ? AND hotel_id = ?"
            );
            $stmt->execute([$abonoId, $reservacionId, $hotelId]);

            $this->pdo->commit();

            return [
                'abono_id' => $abonoId,
                'movimiento_reverso_id' => $movimientoReversoId,
                'monto' => round($monto, 2),
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function obtenerReservacion(int $hotelId, int $reservacionId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT id, hotel_id, precio_total, estado
             FROM reservaciones
             WHERE id = ? AND hotel_id = ? LIMIT 1" . $lock
        );
        $stmt->execute([$reservacionId, $hotelId]);
        $reservacion = $stmt->fetch(PDO::FETCH_ASSOC);
        return $reservacion ?: null;
    }

    /** Saldo pendiente = precio_total - (reservacion_pagos + reservacion_abonos). */
    private function saldoPendiente(int $hotelId, int $reservacionId, float $total): float
    {
        $sumar = function ($tabla) use ($hotelId, $reservacionId) {
            if (!$this->tablaExiste($tabla)) {
                return 0.0;
            }
            $stmt = $this->pdo->prepare(
                "SELECT COALESCE(SUM(monto), 0) AS t FROM {$tabla} WHERE reservacion_id = ? AND hotel_id = ?"
            );
            $stmt->execute([$reservacionId, $hotelId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($row['t'] ?? 0);
        };

        $pagado = $sumar('reservacion_pagos') + $sumar('reservacion_abonos');
        return max(0, round($total - $pagado, 2));
    }

    private function obtenerCorteAbierto(int $hotelId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT cc.id, cc.hotel_id, cc.caja_id, cc.estado
             FROM cortes_caja cc
             INNER JOIN cajas c ON c.id = cc.caja_id AND c.hotel_id = cc.hotel_id
             WHERE cc.hotel_id = ? AND cc.estado = 'abierto' AND COALESCE(c.activa, 1) = 1
             ORDER BY cc.fecha_apertura DESC, cc.id DESC
             LIMIT 1" . $lock
        );
        $stmt->execute([$hotelId]);
        $corte = $stmt->fetch(PDO::FETCH_ASSOC);
        return $corte ?: null;
    }

    private function metodosPago(): array
    {
        return ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
    }

    private function normalizarMetodoPago($value): string
    {
        $metodo = trim((string)($value ?? ''));
        if (!array_key_exists($metodo, $this->metodosPago())) {
            throw new Exception('Metodo de pago invalido.');
        }
        return $metodo;
    }

    private function normalizarMonto($value): float
    {
        $monto = round((float)str_replace(',', '', (string)$value), 2);
        if ($monto <= 0) {
            throw new Exception('El monto del anticipo debe ser mayor a 0.');
        }
        return $monto;
    }

    private function normalizarTextoNullable($value, int $maxLength): ?string
    {
        $texto = trim((string)($value ?? ''));
        return $texto === '' ? null : $this->limitar($texto, $maxLength);
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
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1"
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
        return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength, 'UTF-8') : substr($value, 0, $maxLength);
    }
}
