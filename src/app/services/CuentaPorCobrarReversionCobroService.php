<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class CuentaPorCobrarReversionCobroService
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
            'cuentas_por_cobrar',
            'cuentas_por_cobrar_movimientos',
            'cajas',
            'cortes_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return $this->tipoMovimientoDisponible('CANCELACION');
    }

    public function evaluarReversion(int $hotelId, int $cuentaId, int $movimientoCobroId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por cobrar invalida');
        $movimientoCobroId = $this->validarId($movimientoCobroId, 'Movimiento de cobro invalido');

        if (!$this->tablasDisponibles()) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'Faltan tablas requeridas o tipo CANCELACION para reversion CxC.',
                'corte' => null,
                'monto' => '0.00',
                'referencia_reversion' => null,
            ];
        }

        $cuenta = $this->obtenerCuenta($hotelId, $cuentaId, false);
        $movimientoCobro = $this->obtenerMovimientoCobro($hotelId, $cuentaId, $movimientoCobroId, false);
        $movimientoCaja = $movimientoCobro
            ? $this->obtenerMovimientoCajaOriginal($hotelId, $cuentaId, $movimientoCobro, false)
            : null;
        $corte = $this->obtenerCorteAbierto($hotelId, false);
        $referenciaReversion = $this->referenciaReversion($cuentaId, $movimientoCobroId);

        $bloqueos = $this->bloqueosReversion($cuenta, $movimientoCobro, $movimientoCaja, $corte, $referenciaReversion, false);

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Cobro con Caja elegible para reversion controlada.'
                : '',
            'motivo_bloqueo' => implode(' ', $bloqueos),
            'corte' => $corte,
            'monto' => $movimientoCobro ? $this->decimal($movimientoCobro['monto'] ?? 0) : '0.00',
            'referencia_reversion' => $referenciaReversion,
            'movimiento_caja_original' => $movimientoCaja,
        ];
    }

    public function revertirCobro(int $hotelId, int $cuentaId, int $movimientoCobroId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por cobrar invalida');
        $movimientoCobroId = $this->validarId($movimientoCobroId, 'Movimiento de cobro invalido');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para revertir cobro CxC');
        $motivo = $this->normalizarMotivo($datos['motivo'] ?? null);

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas requeridas o tipo CANCELACION no disponibles para reversion CxC con Caja');
        }

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $cuenta = $this->obtenerCuenta($hotelId, $cuentaId, true);
            $movimientoCobro = $this->obtenerMovimientoCobro($hotelId, $cuentaId, $movimientoCobroId, true);
            $movimientoCaja = $movimientoCobro
                ? $this->obtenerMovimientoCajaOriginal($hotelId, $cuentaId, $movimientoCobro, true)
                : null;
            $corte = $this->obtenerCorteAbierto($hotelId, true);
            $referenciaReversion = $this->referenciaReversion($cuentaId, $movimientoCobroId);
            $bloqueos = $this->bloqueosReversion($cuenta, $movimientoCobro, $movimientoCaja, $corte, $referenciaReversion, true);
            if ($bloqueos) {
                throw new Exception(implode(' ', $bloqueos));
            }

            $monto = (float)$movimientoCobro['monto'];
            $saldoAnterior = (float)$cuenta['saldo'];
            $saldoPosterior = round($saldoAnterior + $monto, 2);
            $total = (float)$cuenta['total'];
            if ($saldoPosterior > $total + 0.004) {
                throw new Exception('La reversion dejaria saldo mayor al total de la CxC');
            }

            $estadoPosterior = $this->estadoDesdeSaldo($cuenta, $saldoPosterior);
            $montoDecimal = $this->decimal($monto);
            $saldoAnteriorDecimal = $this->decimal($saldoAnterior);
            $saldoPosteriorDecimal = $this->decimal($saldoPosterior);
            $notas = $this->limitar(
                'Reversion del cobro CxC movimiento #' . $movimientoCobroId . '. Motivo: ' . $motivo,
                1000
            );

            $stmt = $this->pdo->prepare(
                "INSERT INTO cuentas_por_cobrar_movimientos
                    (hotel_id, cuenta_por_cobrar_id, tipo_movimiento, monto,
                     saldo_anterior, saldo_posterior, referencia, notas, usuario_id, created_at)
                 VALUES
                    (?, ?, 'CANCELACION', ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $cuentaId,
                $montoDecimal,
                $saldoAnteriorDecimal,
                $saldoPosteriorDecimal,
                $referenciaReversion,
                $notas,
                $usuarioId,
            ]);
            $movimientoCancelacionId = (int)$this->pdo->lastInsertId();

            $descripcionCaja = $this->normalizarTextoObligatorio(
                'Reversion Cobro CxC #' . $cuentaId . ' mov #' . $movimientoCobroId,
                255
            );
            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'gasto', 'Reversion Cobro CxC', NULL, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $montoDecimal,
                (string)$movimientoCaja['metodo_pago'],
                $referenciaReversion,
                !empty($cuenta['reservacion_id']) ? (int)$cuenta['reservacion_id'] : null,
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaReversionId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                "UPDATE cuentas_por_cobrar
                 SET saldo = ?,
                     estado = ?,
                     actualizado_por_usuario_id = ?,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?"
            );
            $stmt->execute([
                $saldoPosteriorDecimal,
                $estadoPosterior,
                $usuarioId,
                $cuentaId,
                $hotelId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('No se pudo actualizar el saldo de la cuenta por cobrar');
            }

            AuditService::record('cuentas_por_cobrar.cobro_caja_revertido', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'cuentas_por_cobrar',
                'entidad_id' => (string)$cuentaId,
                'descripcion' => 'Cobro CxC revertido con gasto de Caja controlado',
                'datos_antes' => [
                    'estado' => $cuenta['estado'] ?? null,
                    'saldo' => $saldoAnteriorDecimal,
                    'movimiento_cobro_id' => $movimientoCobroId,
                    'movimiento_caja_original_id' => (int)$movimientoCaja['id'],
                ],
                'datos_despues' => [
                    'estado' => $estadoPosterior,
                    'saldo' => $saldoPosteriorDecimal,
                    'monto' => $montoDecimal,
                    'movimiento_cancelacion_id' => $movimientoCancelacionId,
                    'movimiento_caja_reversion_id' => $movimientoCajaReversionId,
                    'corte_id' => (int)$corte['id'],
                    'referencia' => $referenciaReversion,
                    'motivo' => $motivo,
                ],
            ]);

            $this->commitIfManaged();

            return [
                'cuenta_por_cobrar_id' => $cuentaId,
                'movimiento_cobro_id' => $movimientoCobroId,
                'movimiento_cancelacion_id' => $movimientoCancelacionId,
                'movimiento_caja_original_id' => (int)$movimientoCaja['id'],
                'movimiento_caja_reversion_id' => $movimientoCajaReversionId,
                'corte_id' => (int)$corte['id'],
                'estado' => $estadoPosterior,
                'saldo_anterior' => $saldoAnteriorDecimal,
                'saldo_posterior' => $saldoPosteriorDecimal,
                'monto' => $montoDecimal,
                'referencia' => $referenciaReversion,
            ];
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function obtenerCuenta(int $hotelId, int $cuentaId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT cxc.*,
                    h.nombre_completo AS huesped_nombre
             FROM cuentas_por_cobrar cxc
             LEFT JOIN huespedes h
                ON h.id = cxc.huesped_id
             WHERE cxc.id = ?
               AND cxc.hotel_id = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$cuentaId, $hotelId]);
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cuenta ?: null;
    }

    private function obtenerMovimientoCobro(int $hotelId, int $cuentaId, int $movimientoCobroId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM cuentas_por_cobrar_movimientos
             WHERE id = ?
               AND hotel_id = ?
               AND cuenta_por_cobrar_id = ?
               AND tipo_movimiento = 'COBRO'
             LIMIT 1" . $lock
        );
        $stmt->execute([$movimientoCobroId, $hotelId, $cuentaId]);
        $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $movimiento ?: null;
    }

    private function obtenerMovimientoCajaOriginal(int $hotelId, int $cuentaId, array $movimientoCobro, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $referenciaManual = trim((string)($movimientoCobro['referencia'] ?? ''));
        $referenciaManual = $referenciaManual !== '' ? $referenciaManual : '__SIN_REFERENCIA_MANUAL__';
        $referenciaAutomatica = 'CXC-' . $cuentaId . '-MOV-' . (int)$movimientoCobro['id'];
        $descripcionLike = 'Cobro CxC #' . $cuentaId . '%';
        $createdAt = (string)($movimientoCobro['created_at'] ?? date('Y-m-d H:i:s'));

        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND tipo = 'ingreso'
               AND categoria = 'Cobro CxC'
               AND monto = ?
               AND (referencia = ? OR referencia = ?)
               AND descripcion LIKE ?
             ORDER BY ABS(TIMESTAMPDIFF(SECOND, created_at, ?)) ASC, id DESC
             LIMIT 1" . $lock
        );
        $stmt->execute([
            $hotelId,
            $this->decimal($movimientoCobro['monto'] ?? 0),
            $referenciaManual,
            $referenciaAutomatica,
            $descripcionLike,
            $createdAt,
        ]);
        $movimientoCaja = $stmt->fetch(PDO::FETCH_ASSOC);

        return $movimientoCaja ?: null;
    }

    private function obtenerCorteAbierto(int $hotelId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT cc.id,
                    cc.hotel_id,
                    cc.caja_id,
                    cc.fecha_apertura,
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
             LIMIT 1" . $lock
        );
        $stmt->execute([$hotelId]);
        $corte = $stmt->fetch(PDO::FETCH_ASSOC);

        return $corte ?: null;
    }

    private function bloqueosReversion(?array $cuenta, ?array $movimientoCobro, ?array $movimientoCaja, ?array $corte, string $referenciaReversion, bool $forUpdate): array
    {
        $bloqueos = [];

        if (!$cuenta) {
            return ['Cuenta por cobrar no encontrada para el hotel actual.'];
        }

        if (!$movimientoCobro) {
            $bloqueos[] = 'Movimiento COBRO no encontrado para esta cuenta.';
        }

        if (!$movimientoCaja) {
            $bloqueos[] = 'No se encontro el ingreso de Caja asociado al cobro.';
        }

        if (!$corte) {
            $bloqueos[] = 'No hay corte de Caja abierto para registrar la reversion.';
        }

        if (!$this->tipoMovimientoDisponible('CANCELACION')) {
            $bloqueos[] = 'El tipo de movimiento CANCELACION no esta disponible.';
        }

        if (!in_array((string)($cuenta['estado'] ?? ''), ['pendiente', 'parcial', 'liquidada', 'vencida'], true)) {
            $bloqueos[] = 'El estado de la cuenta no permite revertir cobros.';
        }

        if ((float)($cuenta['total'] ?? 0) <= 0) {
            $bloqueos[] = 'El total de la cuenta no es valido.';
        }

        if ((float)($cuenta['saldo'] ?? 0) < 0) {
            $bloqueos[] = 'La cuenta tiene saldo negativo.';
        }

        if ($movimientoCobro && (float)($movimientoCobro['monto'] ?? 0) <= 0) {
            $bloqueos[] = 'El movimiento COBRO no tiene monto valido.';
        }

        if ($movimientoCobro && (float)($cuenta['saldo'] ?? 0) + (float)($movimientoCobro['monto'] ?? 0) > (float)($cuenta['total'] ?? 0) + 0.004) {
            $bloqueos[] = 'La reversion dejaria saldo mayor al total de la cuenta.';
        }

        if ($this->existeReversionPrevia($cuenta ? (int)$cuenta['hotel_id'] : 0, $cuenta ? (int)$cuenta['id'] : 0, $referenciaReversion, $forUpdate)) {
            $bloqueos[] = 'Este cobro ya tiene reversion registrada.';
        }

        return $bloqueos;
    }

    private function existeReversionPrevia(int $hotelId, int $cuentaId, string $referenciaReversion, bool $forUpdate): bool
    {
        if ($hotelId <= 0 || $cuentaId <= 0) {
            return false;
        }

        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM cuentas_por_cobrar_movimientos
             WHERE hotel_id = ?
               AND cuenta_por_cobrar_id = ?
               AND tipo_movimiento = 'CANCELACION'
               AND referencia = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$hotelId, $cuentaId, $referenciaReversion]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return true;
        }

        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND tipo = 'gasto'
               AND categoria = 'Reversion Cobro CxC'
               AND referencia = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$hotelId, $referenciaReversion]);

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function estadoDesdeSaldo(array $cuenta, float $saldo): string
    {
        if ($saldo <= 0.004) {
            return 'liquidada';
        }

        $fechaVencimiento = trim((string)($cuenta['fecha_vencimiento'] ?? ''));
        if ($fechaVencimiento !== '' && $fechaVencimiento < date('Y-m-d')) {
            return 'vencida';
        }

        $total = (float)($cuenta['total'] ?? 0);
        if ($saldo >= $total - 0.004) {
            return 'pendiente';
        }

        return 'parcial';
    }

    private function referenciaReversion(int $cuentaId, int $movimientoCobroId): string
    {
        return 'REV-CXC-' . $cuentaId . '-MOV-' . $movimientoCobroId;
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('La reversion CxC debe controlar su propia transaccion');
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

    private function normalizarMotivo($value): string
    {
        $motivo = trim((string)($value ?? ''));
        if ($motivo === '') {
            throw new Exception('El motivo de reversion es obligatorio');
        }

        return $this->limitar($motivo, 1000);
    }

    private function normalizarTextoObligatorio(string $value, int $maxLength): string
    {
        $texto = trim($value);
        if ($texto === '') {
            throw new Exception('Texto requerido vacio');
        }

        return $this->limitar($texto, $maxLength);
    }

    private function validarId($value, string $message): int
    {
        $id = (int)($value ?? 0);
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function tipoMovimientoDisponible(string $tipo): bool
    {
        $stmt = $this->pdo->query(
            "SELECT COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
               AND COLUMN_NAME = 'tipo_movimiento'
             LIMIT 1"
        );

        $columnType = $stmt ? (string)($stmt->fetchColumn() ?: '') : '';
        return strpos($columnType, "'" . $tipo . "'") !== false;
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
