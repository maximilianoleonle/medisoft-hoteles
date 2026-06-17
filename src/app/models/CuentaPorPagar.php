<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/AuditService.php';

class CuentaPorPagar extends Model
{
    protected $table = 'cuentas_por_pagar';

    public function tablaDisponible(): bool
    {
        return $this->tablaExiste('cuentas_por_pagar');
    }

    public function movimientosDisponibles(): bool
    {
        return $this->tablaExiste('cuentas_por_pagar_movimientos');
    }

    public function tablasPreviewGeneracionDisponibles(): bool
    {
        foreach (['compras', 'compra_detalles', 'proveedores', 'hoteles', 'cuentas_por_pagar'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function tablasSimuladorCajaDisponibles(): bool
    {
        foreach (['cuentas_por_pagar', 'proveedores', 'compras', 'hoteles', 'cajas', 'cortes_caja'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function resumenPorHotel(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $this->resumenVacio();
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'parcial' THEN 1 ELSE 0 END) AS parciales,
                    SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) AS pagadas,
                    SUM(CASE WHEN estado = 'vencida' THEN 1 ELSE 0 END) AS vencidas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    COALESCE(SUM(total), 0) AS total_importe,
                    COALESCE(SUM(saldo), 0) AS saldo_total,
                    COALESCE(SUM(CASE WHEN fecha_vencimiento < CURDATE()
                                      AND estado IN ('pendiente', 'parcial', 'vencida')
                                      THEN saldo ELSE 0 END), 0) AS saldo_vencido
             FROM cuentas_por_pagar
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        return [
            'total' => (int)($row['total'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'parciales' => (int)($row['parciales'] ?? 0),
            'pagadas' => (int)($row['pagadas'] ?? 0),
            'vencidas' => (int)($row['vencidas'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
            'total_importe' => $this->decimal($row['total_importe'] ?? 0),
            'saldo_total' => $this->decimal($row['saldo_total'] ?? 0),
            'saldo_vencido' => $this->decimal($row['saldo_vencido'] ?? 0),
        ];
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $where = ['cxp.hotel_id = ?'];
        $params = [$hotelId];

        $estado = $this->normalizarEstado($filtros['estado'] ?? 'todos');
        if ($estado !== 'todos') {
            $where[] = 'cxp.estado = ?';
            $params[] = $estado;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(cxp.folio LIKE ? OR cxp.descripcion LIKE ? OR p.nombre LIKE ? OR c.folio LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT cxp.*,
                    p.nombre AS proveedor_nombre,
                    c.folio AS compra_folio
             FROM cuentas_por_pagar cxp
             INNER JOIN proveedores p
                ON p.id = cxp.proveedor_id
               AND p.hotel_id = cxp.hotel_id
             LEFT JOIN compras c
                ON c.id = cxp.compra_id
               AND c.hotel_id = cxp.hotel_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE
                    WHEN cxp.estado IN ('pendiente', 'parcial', 'vencida') THEN 0
                    ELSE 1
                END,
                cxp.fecha_vencimiento IS NULL ASC,
                cxp.fecha_vencimiento ASC,
                cxp.id DESC
             LIMIT {$limite}",
            $params
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarPorIdHotel(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT cxp.*,
                    p.nombre AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    c.folio AS compra_folio,
                    c.fecha_compra,
                    c.fecha_recepcion
             FROM cuentas_por_pagar cxp
             INNER JOIN proveedores p
                ON p.id = cxp.proveedor_id
               AND p.hotel_id = cxp.hotel_id
             LEFT JOIN compras c
                ON c.id = cxp.compra_id
               AND c.hotel_id = cxp.hotel_id
             WHERE cxp.id = ?
               AND cxp.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function movimientosPorCuenta(int $id, int $hotelId, int $limite = 100): array
    {
        if ($id <= 0 || $hotelId <= 0 || !$this->movimientosDisponibles()) {
            return [];
        }

        $limite = max(1, min(200, $limite));
        $stmt = $this->db->query(
            "SELECT *
             FROM cuentas_por_pagar_movimientos
             WHERE cuenta_por_pagar_id = ?
               AND hotel_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT {$limite}",
            [$id, $hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function previewGeneracionDesdeCompras(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        if ($hotelId <= 0 || !$this->tablasPreviewGeneracionDisponibles()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['c.hotel_id = ?'];
        $params = [$hotelId, $hotelId, $hotelId];

        $estado = $this->normalizarEstadoCompra($filtros['estado'] ?? 'recibida');
        if ($estado !== 'todos') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(c.folio LIKE ? OR p.nombre LIKE ? OR CAST(c.id AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT c.id AS compra_id,
                    c.hotel_id,
                    h.nombre AS hotel_nombre,
                    c.proveedor_id,
                    p.nombre AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    c.folio AS compra_folio,
                    c.fecha_compra,
                    c.fecha_recepcion,
                    c.estado AS compra_estado,
                    c.subtotal,
                    c.impuestos,
                    c.total,
                    COALESCE(det.detalle_count, 0) AS detalle_count,
                    cxp.cxp_id,
                    COALESCE(cxp.cxp_count, 0) AS cxp_count,
                    cxp.cxp_estado,
                    cxp.cxp_folio,
                    cxp.cxp_saldo
             FROM compras c
             INNER JOIN hoteles h
                ON h.id = c.hotel_id
             LEFT JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             LEFT JOIN (
                 SELECT hotel_id,
                        compra_id,
                        COUNT(*) AS detalle_count
                 FROM compra_detalles
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, compra_id
             ) det
                ON det.hotel_id = c.hotel_id
               AND det.compra_id = c.id
             LEFT JOIN (
                 SELECT hotel_id,
                        compra_id,
                        MIN(id) AS cxp_id,
                        COUNT(*) AS cxp_count,
                        MIN(estado) AS cxp_estado,
                        MIN(folio) AS cxp_folio,
                        SUM(saldo) AS cxp_saldo
                 FROM cuentas_por_pagar
                 WHERE hotel_id = ?
                   AND compra_id IS NOT NULL
                 GROUP BY hotel_id, compra_id
             ) cxp
                ON cxp.hotel_id = c.hotel_id
               AND cxp.compra_id = c.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE
                    WHEN c.estado = 'recibida'
                     AND COALESCE(cxp.cxp_count, 0) = 0
                     AND p.id IS NOT NULL
                     AND c.total > 0
                     AND COALESCE(det.detalle_count, 0) > 0
                    THEN 0
                    ELSE 1
                END,
                c.fecha_recepcion DESC,
                c.id DESC
             LIMIT {$limite}",
            $params
        );

        $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        return array_map([$this, 'evaluarPreviewGeneracion'], $filas);
    }

    public function resumenPreviewGeneracion(array $compras): array
    {
        $resumen = [
            'total' => count($compras),
            'elegibles' => 0,
            'bloqueadas' => 0,
            'con_cxp' => 0,
            'recibidas' => 0,
            'total_elegible' => '0.00',
        ];

        foreach ($compras as $compra) {
            if (($compra['compra_estado'] ?? '') === 'recibida') {
                $resumen['recibidas']++;
            }

            if ((int)($compra['cxp_count'] ?? 0) > 0) {
                $resumen['con_cxp']++;
            }

            if (!empty($compra['es_elegible'])) {
                $resumen['elegibles']++;
                $resumen['total_elegible'] = $this->decimal(
                    (float)$resumen['total_elegible'] + (float)($compra['total'] ?? 0)
                );
            } else {
                $resumen['bloqueadas']++;
            }
        }

        return $resumen;
    }

    public function simuladorCajaProveedor(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        $corte = $this->corteAbiertoSimuladorCaja($hotelId);
        $cuentas = [];

        if ($hotelId > 0 && $this->tablasSimuladorCajaDisponibles()) {
            $limite = max(1, min(300, $limite));
            $where = ['cxp.hotel_id = ?'];
            $params = [$hotelId];

            $estado = $this->normalizarEstado($filtros['estado'] ?? 'todos');
            if ($estado !== 'todos') {
                $where[] = 'cxp.estado = ?';
                $params[] = $estado;
            }

            $buscar = trim((string)($filtros['buscar'] ?? ''));
            if ($buscar !== '') {
                $like = '%' . $buscar . '%';
                $where[] = '(cxp.folio LIKE ? OR cxp.descripcion LIKE ? OR p.nombre LIKE ? OR c.folio LIKE ? OR CAST(cxp.id AS CHAR) LIKE ?)';
                array_push($params, $like, $like, $like, $like, $like);
            }

            $stmt = $this->db->query(
                "SELECT cxp.*,
                        h.nombre AS hotel_nombre,
                        p.id AS proveedor_hotel_id,
                        p.nombre AS proveedor_nombre,
                        p.rfc AS proveedor_rfc,
                        c.id AS compra_hotel_id,
                        c.folio AS compra_folio,
                        c.estado AS compra_estado,
                        c.fecha_compra,
                        c.fecha_recepcion
                 FROM cuentas_por_pagar cxp
                 INNER JOIN hoteles h
                    ON h.id = cxp.hotel_id
                 LEFT JOIN proveedores p
                    ON p.id = cxp.proveedor_id
                   AND p.hotel_id = cxp.hotel_id
                 LEFT JOIN compras c
                    ON c.id = cxp.compra_id
                   AND c.hotel_id = cxp.hotel_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY
                    CASE
                        WHEN cxp.estado IN ('pendiente', 'parcial', 'vencida')
                         AND cxp.saldo > 0
                         AND p.id IS NOT NULL
                         AND (cxp.compra_id IS NULL OR c.id IS NOT NULL)
                        THEN 0
                        ELSE 1
                    END,
                    cxp.fecha_vencimiento IS NULL ASC,
                    cxp.fecha_vencimiento ASC,
                    cxp.id DESC
                 LIMIT {$limite}",
                $params
            );

            $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
            foreach ($filas as $fila) {
                $cuentas[] = $this->evaluarSimuladorCaja($fila, $corte);
            }
        }

        return [
            'corte' => $corte,
            'cuentas' => $cuentas,
            'resumen' => $this->resumenSimuladorCaja($cuentas, $corte),
        ];
    }

    public function generarDesdeCompraRecibida(int $hotelId, int $compraId, ?int $usuarioId = null): array
    {
        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $compraId = $this->validarId($compraId, 'Compra invalida');
        $usuarioId = $this->normalizarUsuarioId($usuarioId);
        $pdo = $this->db->getConnection();

        if (!$this->tablasPreviewGeneracionDisponibles()) {
            throw new Exception('Tablas requeridas de compras y CxP no disponibles');
        }

        if ($pdo->inTransaction()) {
            throw new Exception('La generacion de CxP debe controlar su propia transaccion');
        }

        $pdo->beginTransaction();

        try {
            $compra = $this->obtenerCompraParaGeneracion($hotelId, $compraId);
            $this->assertCompraGenerable($compra);
            $this->assertSinCxpParaCompra($hotelId, $compraId);

            $fechaEmision = $this->normalizarFechaEmision($compra['fecha_recepcion'] ?? null, $compra['fecha_compra'] ?? null);
            $folio = 'CXP-COMPRA-' . $compraId;
            $descripcion = 'Cuenta por pagar generada desde compra #' . $compraId;
            $total = $this->decimal($compra['total'] ?? 0);

            $stmt = $pdo->prepare(
                "INSERT INTO cuentas_por_pagar
                    (hotel_id, proveedor_id, compra_id, folio, descripcion,
                     fecha_emision, fecha_vencimiento, estado, moneda,
                     subtotal, impuestos, total, saldo, notas,
                     created_by, updated_by, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, NULL, 'pendiente', 'MXN',
                     ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                (int)$compra['proveedor_id'],
                $compraId,
                $folio,
                $descripcion,
                $fechaEmision,
                $this->decimal($compra['subtotal'] ?? 0),
                $this->decimal($compra['impuestos'] ?? 0),
                $total,
                $total,
                'Generada manualmente desde compra recibida #' . $compraId . '. Sin pagos ni Caja.',
                $usuarioId,
                $usuarioId,
            ]);

            $cxpId = (int)$pdo->lastInsertId();

            AuditService::record('cuentas_por_pagar.generada_desde_compra', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'cuentas_por_pagar',
                'entidad_id' => (string)$cxpId,
                'descripcion' => 'CxP generada manualmente desde compra recibida',
                'datos_despues' => [
                    'cxp_id' => $cxpId,
                    'compra_id' => $compraId,
                    'proveedor_id' => (int)$compra['proveedor_id'],
                    'proveedor_nombre' => $compra['proveedor_nombre'] ?? null,
                    'estado' => 'pendiente',
                    'total' => $total,
                    'saldo' => $total,
                    'sin_caja' => true,
                    'sin_pagos' => true,
                ],
            ]);

            $pdo->commit();

            return [
                'cxp_id' => $cxpId,
                'compra_id' => $compraId,
                'hotel_id' => $hotelId,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'pendientes' => 0,
            'parciales' => 0,
            'pagadas' => 0,
            'vencidas' => 0,
            'canceladas' => 0,
            'total_importe' => '0.00',
            'saldo_total' => '0.00',
            'saldo_vencido' => '0.00',
        ];
    }

    private function normalizarEstado($value): string
    {
        $estado = (string)($value ?? 'todos');
        return in_array($estado, ['pendiente', 'parcial', 'pagada', 'vencida', 'cancelada', 'todos'], true)
            ? $estado
            : 'todos';
    }

    private function normalizarEstadoCompra($value): string
    {
        $estado = (string)($value ?? 'recibida');
        return in_array($estado, ['borrador', 'recibida', 'cancelada', 'todos'], true)
            ? $estado
            : 'recibida';
    }

    private function obtenerCompraParaGeneracion(int $hotelId, int $compraId): array
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "SELECT c.*,
                    p.id AS proveedor_hotel_id,
                    p.nombre AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    (
                        SELECT COUNT(*)
                        FROM compra_detalles d
                        WHERE d.hotel_id = c.hotel_id
                          AND d.compra_id = c.id
                    ) AS detalle_count
             FROM compras c
             LEFT JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             WHERE c.id = ?
               AND c.hotel_id = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$compraId, $hotelId]);
        $compra = $stmt->fetch();

        if (!$compra) {
            throw new Exception('Compra no encontrada para el hotel actual');
        }

        return $compra;
    }

    private function assertCompraGenerable(array $compra): void
    {
        if (($compra['estado'] ?? '') !== 'recibida') {
            throw new Exception('Solo se puede generar CxP desde compras recibidas');
        }

        if (empty($compra['proveedor_id']) || empty($compra['proveedor_hotel_id'])) {
            throw new Exception('El proveedor no pertenece al hotel actual');
        }

        if ((float)($compra['total'] ?? 0) <= 0) {
            throw new Exception('El total de la compra no es valido para CxP');
        }

        if ((int)($compra['detalle_count'] ?? 0) <= 0) {
            throw new Exception('La compra no tiene detalles para respaldar la CxP');
        }
    }

    private function assertSinCxpParaCompra(int $hotelId, int $compraId): void
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            "SELECT id
             FROM cuentas_por_pagar
             WHERE hotel_id = ?
               AND compra_id = ?
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$hotelId, $compraId]);
        $existente = $stmt->fetch();

        if ($existente) {
            throw new Exception('Ya existe una cuenta por pagar para esta compra: #' . (int)$existente['id']);
        }
    }

    private function corteAbiertoSimuladorCaja(int $hotelId): ?array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('cajas') || !$this->tablaExiste('cortes_caja')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT cc.id,
                    cc.hotel_id,
                    cc.caja_id,
                    cc.fecha_apertura,
                    cc.monto_inicial,
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
             LIMIT 1",
            [$hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    private function resumenSimuladorCaja(array $cuentas, ?array $corte): array
    {
        $resumen = [
            'total' => count($cuentas),
            'elegibles' => 0,
            'bloqueadas' => 0,
            'sin_corte' => empty($corte) ? 1 : 0,
            'saldo_elegible' => '0.00',
            'saldo_revisado' => '0.00',
        ];

        foreach ($cuentas as $cuenta) {
            $saldo = (float)($cuenta['saldo'] ?? 0);
            $resumen['saldo_revisado'] = $this->decimal((float)$resumen['saldo_revisado'] + $saldo);

            if (!empty($cuenta['es_elegible_caja'])) {
                $resumen['elegibles']++;
                $resumen['saldo_elegible'] = $this->decimal((float)$resumen['saldo_elegible'] + $saldo);
            } else {
                $resumen['bloqueadas']++;
            }
        }

        return $resumen;
    }

    private function evaluarSimuladorCaja(array $cuenta, ?array $corte): array
    {
        $bloqueos = [];

        if (empty($corte)) {
            $bloqueos[] = 'No hay corte de Caja abierto para el hotel actual.';
        }

        if (empty($cuenta['hotel_id']) || (int)$cuenta['hotel_id'] <= 0) {
            $bloqueos[] = 'La cuenta no tiene hotel_id valido.';
        }

        if (!in_array((string)($cuenta['estado'] ?? ''), ['pendiente', 'parcial', 'vencida'], true)) {
            $bloqueos[] = 'El estado de la cuenta no permite egreso.';
        }

        if (empty($cuenta['proveedor_id']) || empty($cuenta['proveedor_hotel_id'])) {
            $bloqueos[] = 'El proveedor no pertenece al hotel actual.';
        }

        if ((float)($cuenta['total'] ?? 0) <= 0) {
            $bloqueos[] = 'El total de la cuenta no es valido.';
        }

        if ((float)($cuenta['saldo'] ?? 0) <= 0) {
            $bloqueos[] = 'La cuenta no tiene saldo pendiente.';
        }

        if (!empty($cuenta['compra_id']) && empty($cuenta['compra_hotel_id'])) {
            $bloqueos[] = 'La compra vinculada no pertenece al hotel actual o no existe.';
        }

        if (!empty($cuenta['compra_id']) && !empty($cuenta['compra_estado']) && (string)$cuenta['compra_estado'] !== 'recibida') {
            $bloqueos[] = 'La compra vinculada no esta recibida.';
        }

        $cuenta['es_elegible_caja'] = empty($bloqueos);
        $cuenta['motivo_elegibilidad_caja'] = empty($bloqueos)
            ? 'Cuenta con saldo, proveedor del hotel y corte de Caja abierto. Esta pantalla no registra movimientos.'
            : '';
        $cuenta['motivo_bloqueo_caja'] = implode(' ', $bloqueos);

        return $cuenta;
    }

    private function evaluarPreviewGeneracion(array $compra): array
    {
        $bloqueo = '';

        if (($compra['compra_estado'] ?? '') !== 'recibida') {
            $bloqueo = 'La compra no esta recibida.';
        } elseif (empty($compra['proveedor_id']) || empty($compra['proveedor_nombre'])) {
            $bloqueo = 'El proveedor no existe para el hotel actual.';
        } elseif ((float)($compra['total'] ?? 0) <= 0) {
            $bloqueo = 'El total de la compra no es valido.';
        } elseif ((int)($compra['detalle_count'] ?? 0) <= 0) {
            $bloqueo = 'La compra no tiene detalles vinculados.';
        } elseif ((int)($compra['cxp_count'] ?? 0) > 0) {
            $bloqueo = 'Ya existe una cuenta por pagar vinculada.';
        }

        $compra['es_elegible'] = $bloqueo === '';
        $compra['motivo_elegibilidad'] = $bloqueo === ''
            ? 'Compra recibida, con proveedor del hotel, total valido y sin CxP vinculada.'
            : '';
        $compra['motivo_bloqueo'] = $bloqueo;

        return $compra;
    }

    private function validarId($value, string $message): int
    {
        $id = (int)$value;
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function normalizarUsuarioId($value): ?int
    {
        $id = (int)($value ?? 0);
        return $id > 0 ? $id : null;
    }

    private function normalizarFechaEmision($fechaRecepcion, $fechaCompra): string
    {
        $fecha = trim((string)($fechaRecepcion ?: $fechaCompra ?: date('Y-m-d')));
        return substr($fecha, 0, 10);
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT 1
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1",
            [$tabla]
        );

        return $stmt !== false && (bool)$stmt->fetch();
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}
