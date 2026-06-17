<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class CuentaPorPagarPagoService
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
            'cuentas_por_pagar',
            'cuentas_por_pagar_movimientos',
            'proveedores',
            'compras',
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

    public function evaluarPago(int $hotelId, int $cuentaId): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por pagar invalida');

        if (!$this->tablasDisponibles()) {
            return [
                'elegible' => false,
                'motivo_bloqueo' => 'Faltan tablas requeridas para pago proveedor con Caja.',
                'corte' => null,
            ];
        }

        $cuenta = $this->obtenerCuentaParaPago($hotelId, $cuentaId, false);
        $corte = $this->obtenerCorteAbierto($hotelId, false);

        $bloqueos = $this->bloqueosCuenta($cuenta, $corte);

        return [
            'elegible' => empty($bloqueos),
            'motivo_elegibilidad' => empty($bloqueos)
                ? 'Cuenta con saldo, proveedor del hotel, compra recibida y corte de Caja abierto.'
                : '',
            'motivo_bloqueo' => implode(' ', $bloqueos),
            'corte' => $corte,
            'metodos_pago' => $this->metodosPago(),
            'monto_maximo' => $cuenta ? $this->decimal($cuenta['saldo'] ?? 0) : '0.00',
        ];
    }

    public function registrarPago(int $hotelId, int $cuentaId, array $datos, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $cuentaId = $this->validarId($cuentaId, 'Cuenta por pagar invalida');
        $usuarioId = $this->validarId($usuarioId, 'Usuario invalido para registrar egreso de Caja');

        if (!$this->tablasDisponibles()) {
            throw new Exception('Tablas requeridas para pago proveedor con Caja no disponibles');
        }

        $monto = $this->normalizarMonto($datos['monto'] ?? null);
        $metodoPago = $this->normalizarMetodoPago($datos['metodo_pago'] ?? null);
        $referencia = $this->normalizarTextoNullable($datos['referencia'] ?? null, 100);
        $notas = $this->normalizarTextoNullable($datos['notas'] ?? null, 1000);

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $cuenta = $this->obtenerCuentaParaPago($hotelId, $cuentaId, true);
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
            $estadoPosterior = $saldoPosterior <= 0.004 ? 'pagada' : 'parcial';
            $montoDecimal = $this->decimal($monto);
            $saldoAnteriorDecimal = $this->decimal($saldoAnterior);
            $saldoPosteriorDecimal = $this->decimal($saldoPosterior);

            $stmt = $this->pdo->prepare(
                "INSERT INTO cuentas_por_pagar_movimientos
                    (cuenta_por_pagar_id, hotel_id, tipo_movimiento, monto,
                     saldo_anterior, saldo_posterior, referencia, notas,
                     usuario_id, created_at)
                 VALUES
                    (?, ?, 'PAGO_REFERENCIAL', ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $cuentaId,
                $hotelId,
                $montoDecimal,
                $saldoAnteriorDecimal,
                $saldoPosteriorDecimal,
                $referencia,
                $notas,
                $usuarioId,
            ]);
            $movimientoCxpId = (int)$this->pdo->lastInsertId();

            $descripcionCaja = $this->normalizarTextoObligatorio(
                'Pago CxP #' . $cuentaId . ' - ' . (string)($cuenta['proveedor_nombre'] ?? 'Proveedor'),
                255
            );
            $referenciaCaja = $referencia ?: ('CXP-' . $cuentaId . '-MOV-' . $movimientoCxpId);

            $stmt = $this->pdo->prepare(
                "INSERT INTO movimientos_caja
                    (hotel_id, tipo, categoria, categoria_id, descripcion, monto,
                     metodo_pago, referencia, comprobante, proveedor, reservacion_id,
                     usuario_id, corte_id, created_at)
                 VALUES
                    (?, 'gasto', 'Pago proveedor', NULL, ?, ?, ?, ?, NULL, ?, NULL, ?, ?, NOW())"
            );
            $stmt->execute([
                $hotelId,
                $descripcionCaja,
                $montoDecimal,
                $metodoPago,
                $this->normalizarTextoNullable($referenciaCaja, 100),
                $this->normalizarTextoNullable($cuenta['proveedor_nombre'] ?? null, 200),
                $usuarioId,
                (int)$corte['id'],
            ]);
            $movimientoCajaId = (int)$this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                "UPDATE cuentas_por_pagar
                 SET saldo = ?,
                     estado = ?,
                     updated_by = ?,
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
                throw new Exception('No se pudo actualizar el saldo de la cuenta por pagar');
            }

            AuditService::record('cuentas_por_pagar.pago_caja_registrado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'cuentas_por_pagar',
                'entidad_id' => (string)$cuentaId,
                'descripcion' => 'Pago proveedor registrado con movimiento de Caja controlado',
                'datos_antes' => [
                    'estado' => $cuenta['estado'] ?? null,
                    'saldo' => $saldoAnteriorDecimal,
                ],
                'datos_despues' => [
                    'estado' => $estadoPosterior,
                    'saldo' => $saldoPosteriorDecimal,
                    'monto' => $montoDecimal,
                    'metodo_pago' => $metodoPago,
                    'cuenta_por_pagar_movimiento_id' => $movimientoCxpId,
                    'movimiento_caja_id' => $movimientoCajaId,
                    'corte_id' => (int)$corte['id'],
                ],
            ]);

            $this->commitIfManaged();

            return [
                'cuenta_por_pagar_id' => $cuentaId,
                'cuenta_por_pagar_movimiento_id' => $movimientoCxpId,
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

    private function obtenerCuentaParaPago(int $hotelId, int $cuentaId, bool $forUpdate): ?array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $this->pdo->prepare(
            "SELECT cxp.*,
                    p.id AS proveedor_hotel_id,
                    p.nombre AS proveedor_nombre,
                    p.activo AS proveedor_activo,
                    c.id AS compra_hotel_id,
                    c.estado AS compra_estado,
                    c.folio AS compra_folio
             FROM cuentas_por_pagar cxp
             LEFT JOIN proveedores p
                ON p.id = cxp.proveedor_id
               AND p.hotel_id = cxp.hotel_id
             LEFT JOIN compras c
                ON c.id = cxp.compra_id
               AND c.hotel_id = cxp.hotel_id
             WHERE cxp.id = ?
               AND cxp.hotel_id = ?
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
            return ['Cuenta por pagar no encontrada para el hotel actual.'];
        }

        $bloqueos = [];
        if (!$corte) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }

        if (empty($cuenta['hotel_id']) || (int)$cuenta['hotel_id'] <= 0) {
            $bloqueos[] = 'La cuenta no tiene hotel_id valido.';
        }

        if (!in_array((string)($cuenta['estado'] ?? ''), ['pendiente', 'parcial', 'vencida'], true)) {
            $bloqueos[] = 'El estado de la cuenta no permite registrar pago.';
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

        if (empty($cuenta['proveedor_id']) || empty($cuenta['proveedor_hotel_id'])) {
            $bloqueos[] = 'El proveedor no pertenece al hotel actual.';
        }

        if (isset($cuenta['proveedor_activo']) && (int)$cuenta['proveedor_activo'] !== 1) {
            $bloqueos[] = 'El proveedor esta inactivo.';
        }

        if (!empty($cuenta['compra_id']) && empty($cuenta['compra_hotel_id'])) {
            $bloqueos[] = 'La compra vinculada no pertenece al hotel actual o no existe.';
        }

        if (!empty($cuenta['compra_id']) && (string)($cuenta['compra_estado'] ?? '') !== 'recibida') {
            $bloqueos[] = 'La compra vinculada no esta recibida.';
        }

        return $bloqueos;
    }

    private function assertReferenciaNoDuplicada(int $hotelId, int $cuentaId, string $referencia): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM cuentas_por_pagar_movimientos
             WHERE hotel_id = ?
               AND cuenta_por_pagar_id = ?
               AND tipo_movimiento = 'PAGO_REFERENCIAL'
               AND referencia = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $cuentaId, $referencia]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Ya existe un pago referencial con la misma referencia para esta cuenta');
        }
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('El pago proveedor debe controlar su propia transaccion');
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
            throw new Exception('El monto del pago debe ser mayor a 0');
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
