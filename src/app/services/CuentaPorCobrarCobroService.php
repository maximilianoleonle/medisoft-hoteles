<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class CuentaPorCobrarCobroService
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
            'huespedes',
            'reservaciones',
            'solicitudes_factura',
            'cajas',
            'cortes_caja',
            'movimientos_caja',
        ] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return $this->tipoMovimientoCobroDisponible();
    }

    public function evaluarCobro(int $hotelId, int $cuentaId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por cobrar invalida');

        if (!$this->tablasDisponibles()) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'Faltan tablas requeridas o tipo COBRO para cobro CxC con Caja.',
                'corte' => null,
                'metodos_pago' => [],
                'monto_maximo' => '0.00',
            ];
        }

        $cuenta = $this->obtenerCuentaParaCobro($hotelId, $cuentaId, false);
        $corte = $this->obtenerCorteAbierto($hotelId, false);
        $bloqueos = $this->bloqueosCuenta($cuenta, $corte);

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Cuenta con saldo, estado cobrable, corte de Caja abierto y tipo COBRO disponible.'
                : '',
            'motivo_bloqueo' => implode(' ', $bloqueos),
            'corte' => $corte,
            'metodos_pago' => $this->metodosPago(),
            'monto_maximo' => $cuenta ? $this->decimal($cuenta['saldo'] ?? 0) : '0.00',
        ];
    }

    public function registrarCobro(int $hotelId, int $cuentaId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por cobrar invalida');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para registrar ingreso de Caja');

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas requeridas o tipo COBRO no disponibles para cobro CxC con Caja');
        }

        $monto = $this->normalizarMonto($datos['monto'] ?? null);
        $metodoPago = $this->normalizarMetodoPago($datos['metodo_pago'] ?? null);
        $referencia = $this->normalizarTextoNullable($datos['referencia'] ?? null, 100);
        $notas = $this->normalizarTextoNullable($datos['notas'] ?? null, 1000);

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $cuenta = $this->obtenerCuentaParaCobro($hotelId, $cuentaId, true);
            $corte = $this->obtenerCorteAbierto($hotelId, true);
            $bloqueos = $this->bloqueosCuenta($cuenta, $corte);
            if ($bloqueos) {
                throw new Exception(implode(' ', $bloqueos));
            }

            $saldoAnterior = (float)$cuenta['saldo'];
            if ($monto > $saldoAnterior + 0.00001) {
                throw new Exception('El monto no puede ser mayor al saldo pendiente');
            }

            if ($referencia !== null) {
                $this->assertReferenciaNoDuplicada($hotelId, $cuentaId, $referencia);
            }

            $saldoPosterior = max(0, $saldoAnterior - $monto);
            $estadoPosterior = $saldoPosterior <= 0.004 ? 'liquidada' : 'parcial';
            $montoDecimal = $this->decimal($monto);
            $saldoAnteriorDecimal = $this->decimal($saldoAnterior);
            $saldoPosteriorDecimal = $this->decimal($saldoPosterior);

            $stmt = $this->pdo->prepare(
                "INSERT INTO cuentas_por_cobrar_movimientos
                    (hotel_id, cuenta_por_cobrar_id, tipo_movimiento, monto,
                     saldo_anterior, saldo_posterior, referencia, notas, usuario_id, created_at)
                 VALUES
                    (?, ?, 'COBRO', ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $cuentaId,
                $montoDecimal,
                $saldoAnteriorDecimal,
                $saldoPosteriorDecimal,
                $referencia,
                $notas,
                $usuarioId,
            ]);
            $movimientoCxcId = (int)$this->pdo->lastInsertId();

            $descripcionCaja = $this->normalizarTextoObligatorio(
                'Cobro CxC #' . $cuentaId . ' - ' . (string)($cuenta['huesped_nombre'] ?? 'Cliente'),
                255
            );
            $referenciaCaja = $referencia ?: ('CXC-' . $cuentaId . '-MOV-' . $movimientoCxcId);

            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'ingreso', 'Cobro CxC', NULL, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $montoDecimal,
                $metodoPago,
                $this->normalizarTextoNullable($referenciaCaja, 100),
                !empty($cuenta['reservacion_id']) ? (int)$cuenta['reservacion_id'] : null,
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaId = (int)$this->pdo->lastInsertId();

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

            AuditService::record('cuentas_por_cobrar.cobro_caja_registrado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'cuentas_por_cobrar',
                'entidad_id' => (string)$cuentaId,
                'descripcion' => 'Cobro CxC registrado con movimiento de Caja controlado',
                'datos_antes' => [
                    'estado' => $cuenta['estado'] ?? null,
                    'saldo' => $saldoAnteriorDecimal,
                ],
                'datos_despues' => [
                    'estado' => $estadoPosterior,
                    'saldo' => $saldoPosteriorDecimal,
                    'monto' => $montoDecimal,
                    'metodo_pago' => $metodoPago,
                    'cuenta_por_cobrar_movimiento_id' => $movimientoCxcId,
                    'movimiento_caja_id' => $movimientoCajaId,
                    'corte_id' => (int)$corte['id'],
                    'referencia' => $referenciaCaja,
                ],
            ]);

            $this->commitIfManaged();

            return [
                'cuenta_por_cobrar_id' => $cuentaId,
                'cuenta_por_cobrar_movimiento_id' => $movimientoCxcId,
                'movimiento_caja_id' => $movimientoCajaId,
                'corte_id' => (int)$corte['id'],
                'estado' => $estadoPosterior,
                'saldo_anterior' => $saldoAnteriorDecimal,
                'saldo_posterior' => $saldoPosteriorDecimal,
                'monto' => $montoDecimal,
            ];
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    private function obtenerCuentaParaCobro(int $hotelId, int $cuentaId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT cxc.*,
                    h.id AS huesped_hotel_id,
                    h.nombre_completo AS huesped_nombre,
                    r.id AS reservacion_hotel_id,
                    r.estado AS reservacion_estado,
                    sf.id AS factura_hotel_id,
                    sf.numero_factura,
                    sf.estatus AS factura_estatus
             FROM cuentas_por_cobrar cxc
             LEFT JOIN huespedes h
                ON h.id = cxc.huesped_id
             LEFT JOIN reservaciones r
                ON r.id = cxc.reservacion_id
               AND r.hotel_id = cxc.hotel_id
             LEFT JOIN solicitudes_factura sf
                ON sf.id = cxc.solicitud_factura_id
               AND sf.hotel_id = cxc.hotel_id
             WHERE cxc.id = ?
               AND cxc.hotel_id = ?
             LIMIT 1" . $lock
        );
        $stmt->execute([$cuentaId, $hotelId]);
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

        return $cuenta ?: null;
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
                    c.nombre AS caja_nombre,
                    c.ubicacion AS caja_ubicacion
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

    private function bloqueosCuenta(?array $cuenta, ?array $corte): array
    {
        if (!$cuenta) {
            return ['Cuenta por cobrar no encontrada para el hotel actual.'];
        }

        $bloqueos = [];
        if (!$corte) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }

        if (!$this->tipoMovimientoCobroDisponible()) {
            $bloqueos[] = 'El tipo de movimiento COBRO no esta disponible.';
        }

        if (empty($cuenta['hotel_id']) || (int)$cuenta['hotel_id'] <= 0) {
            $bloqueos[] = 'La cuenta no tiene hotel_id valido.';
        }

        if (!in_array((string)($cuenta['estado'] ?? ''), ['pendiente', 'parcial', 'vencida'], true)) {
            $bloqueos[] = 'El estado de la cuenta no permite registrar cobro.';
        }

        if ((float)($cuenta['total'] ?? 0) <= 0) {
            $bloqueos[] = 'El total de la cuenta no es valido.';
        }

        if ((float)($cuenta['saldo'] ?? 0) <= 0) {
            $bloqueos[] = 'La cuenta no tiene saldo pendiente.';
        }

        if ((float)($cuenta['saldo'] ?? 0) > (float)($cuenta['total'] ?? 0)) {
            $bloqueos[] = 'El saldo de la cuenta es mayor al total.';
        }

        if (!empty($cuenta['huesped_id']) && empty($cuenta['huesped_hotel_id'])) {
            $bloqueos[] = 'El huesped vinculado no existe.';
        }

        if (!empty($cuenta['reservacion_id']) && empty($cuenta['reservacion_hotel_id'])) {
            $bloqueos[] = 'La reservacion vinculada no pertenece al hotel actual o no existe.';
        }

        if (!empty($cuenta['reservacion_estado']) && (string)$cuenta['reservacion_estado'] === 'cancelada') {
            $bloqueos[] = 'La reservacion vinculada esta cancelada.';
        }

        if (!empty($cuenta['solicitud_factura_id']) && empty($cuenta['factura_hotel_id'])) {
            $bloqueos[] = 'La factura vinculada no pertenece al hotel actual o no existe.';
        }

        return $bloqueos;
    }

    private function assertReferenciaNoDuplicada(int $hotelId, int $cuentaId, string $referencia): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM cuentas_por_cobrar_movimientos
             WHERE hotel_id = ?
               AND cuenta_por_cobrar_id = ?
               AND tipo_movimiento = 'COBRO'
               AND referencia = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $cuentaId, $referencia]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Ya existe un cobro con la misma referencia para esta cuenta');
        }
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('El cobro CxC debe controlar su propia transaccion');
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
            throw new Exception('El monto del cobro debe ser mayor a 0');
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

    private function tipoMovimientoCobroDisponible(): bool
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
        return strpos($columnType, "'COBRO'") !== false;
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
