<?php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class CompraService
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

    public function crearBorrador(int $hotelId, array $datos, array $detalles, ?int $usuarioId = null): int
    {
        $this->assertTablasDisponibles();
        $this->assertTransaccionPropia();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $usuarioId = $this->normalizarUsuarioId($usuarioId);
        $proveedorId = $this->validarId($datos['proveedor_id'] ?? 0, 'Proveedor invalido');
        $folio = $this->normalizarTextoNullable($datos['folio'] ?? null, 60);
        $fechaCompra = $this->normalizarFecha($datos['fecha_compra'] ?? date('Y-m-d'));
        $notas = $this->normalizarTextoNullable($datos['notas'] ?? null, 1000);

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $proveedor = $this->obtenerProveedorActivo($hotelId, $proveedorId);
            $this->validarFolioDisponible($hotelId, $folio);
            $detallesNormalizados = $this->normalizarDetalles($hotelId, $detalles, false);
            $totales = $this->calcularTotales($detallesNormalizados);

            $stmt = $this->pdo->prepare(
                "INSERT INTO compras
                    (hotel_id, proveedor_id, folio, fecha_compra, estado,
                     subtotal, impuestos, total, notas, created_by, updated_by,
                     created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, 'borrador', ?, 0.00, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $proveedorId,
                $folio,
                $fechaCompra,
                $totales['subtotal'],
                $totales['total'],
                $notas,
                $usuarioId,
                $usuarioId,
            ]);

            $compraId = (int)$this->pdo->lastInsertId();
            $this->insertarDetalles($compraId, $hotelId, $detallesNormalizados);

            AuditService::record('compras.creada', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'compras',
                'entidad_id' => (string)$compraId,
                'descripcion' => 'Compra minima creada en borrador',
                'datos_despues' => [
                    'compra_id' => $compraId,
                    'proveedor_id' => (int)$proveedor['id'],
                    'proveedor_nombre' => $proveedor['nombre'],
                    'estado' => 'borrador',
                    'detalle_count' => count($detallesNormalizados),
                    'total' => $totales['total'],
                ],
            ]);

            $this->commitIfManaged();
            return $compraId;
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function recibirCompra(int $hotelId, int $compraId, ?int $usuarioId = null): array
    {
        $this->assertTablasDisponibles();
        $this->assertTransaccionPropia();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $compraId = $this->validarId($compraId, 'Compra invalida');
        $usuarioId = $this->normalizarUsuarioId($usuarioId);

        $this->assertTransactionPolicy();
        $this->beginTransactionIfManaged();

        try {
            $compra = $this->obtenerCompraBloqueada($hotelId, $compraId);

            if ($compra['estado'] !== 'borrador') {
                throw new Exception('Solo se pueden recibir compras en estado borrador');
            }

            $this->obtenerProveedorActivo($hotelId, (int)$compra['proveedor_id']);
            $detalles = $this->obtenerDetallesBloqueados($hotelId, $compraId);

            if (!$detalles) {
                throw new Exception('La compra no tiene detalles para recibir');
            }

            $movimientos = [];
            foreach ($detalles as $detalle) {
                if (!empty($detalle['movimiento_inventario_id'])) {
                    throw new Exception('La compra ya tiene movimientos de inventario vinculados');
                }

                $producto = $this->obtenerProductoActivo($hotelId, (int)$detalle['producto_id'], true);
                $stockAnterior = (float)$producto['stock_actual'];
                $cantidad = (float)$detalle['cantidad'];
                $stockPosterior = $stockAnterior + $cantidad;

                $stmt = $this->pdo->prepare(
                    "UPDATE inventario_productos
                     SET stock_actual = ?, updated_at = NOW()
                     WHERE id = ? AND hotel_id = ?"
                );
                $stmt->execute([
                    $this->decimal($stockPosterior),
                    (int)$producto['id'],
                    $hotelId,
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception('No se pudo actualizar stock de producto en el hotel actual');
                }

                $stmt = $this->pdo->prepare(
                    "INSERT INTO movimientos_inventario
                        (hotel_id, producto_id, tipo_movimiento, cantidad,
                         stock_anterior, stock_posterior, motivo, usuario_id, created_at)
                     VALUES
                        (?, ?, 'ENTRADA', ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    $hotelId,
                    (int)$producto['id'],
                    $this->decimal($cantidad),
                    $this->decimal($stockAnterior),
                    $this->decimal($stockPosterior),
                    'Recepcion compra #' . $compraId,
                    $usuarioId,
                ]);

                $movimientoId = (int)$this->pdo->lastInsertId();

                $stmt = $this->pdo->prepare(
                    "UPDATE compra_detalles
                     SET movimiento_inventario_id = ?, updated_at = NOW()
                     WHERE id = ?
                       AND compra_id = ?
                       AND hotel_id = ?
                       AND movimiento_inventario_id IS NULL"
                );
                $stmt->execute([
                    $movimientoId,
                    (int)$detalle['id'],
                    $compraId,
                    $hotelId,
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new Exception('No se pudo vincular movimiento de inventario al detalle de compra');
                }

                $movimientos[] = [
                    'detalle_id' => (int)$detalle['id'],
                    'producto_id' => (int)$producto['id'],
                    'movimiento_inventario_id' => $movimientoId,
                    'cantidad' => $this->decimal($cantidad),
                    'stock_anterior' => $this->decimal($stockAnterior),
                    'stock_posterior' => $this->decimal($stockPosterior),
                ];
            }

            $stmt = $this->pdo->prepare(
                "UPDATE compras
                 SET estado = 'recibida',
                     fecha_recepcion = NOW(),
                     recibida_por = ?,
                     updated_by = ?,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado = 'borrador'"
            );
            $stmt->execute([
                $usuarioId,
                $usuarioId,
                $compraId,
                $hotelId,
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception('No se pudo marcar la compra como recibida');
            }

            AuditService::record('compras.recibida', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'compras',
                'entidad_id' => (string)$compraId,
                'descripcion' => 'Compra minima recibida y entrada de inventario registrada',
                'datos_antes' => [
                    'estado' => $compra['estado'],
                ],
                'datos_despues' => [
                    'estado' => 'recibida',
                    'movimientos' => $movimientos,
                ],
            ]);

            $this->commitIfManaged();

            return [
                'success' => true,
                'compra_id' => $compraId,
                'estado' => 'recibida',
                'movimientos' => $movimientos,
            ];
        } catch (Throwable $e) {
            if ($this->manageTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function obtenerCompra(int $hotelId, int $compraId): ?array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $compraId = $this->validarId($compraId, 'Compra invalida');

        $stmt = $this->pdo->prepare(
            "SELECT c.*, p.nombre AS proveedor_nombre
             FROM compras c
             INNER JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             WHERE c.id = ?
               AND c.hotel_id = ?
             LIMIT 1"
        );
        $stmt->execute([$compraId, $hotelId]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compra) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT d.*,
                    ip.nombre AS producto_nombre,
                    ip.codigo AS producto_codigo,
                    mi.tipo_movimiento AS movimiento_tipo,
                    mi.cantidad AS movimiento_cantidad,
                    mi.stock_anterior AS movimiento_stock_anterior,
                    mi.stock_posterior AS movimiento_stock_posterior,
                    mi.motivo AS movimiento_motivo,
                    mi.usuario_id AS movimiento_usuario_id,
                    mi.created_at AS movimiento_created_at
             FROM compra_detalles d
             INNER JOIN inventario_productos ip
                ON ip.id = d.producto_id
               AND ip.hotel_id = d.hotel_id
             LEFT JOIN movimientos_inventario mi
                ON mi.id = d.movimiento_inventario_id
               AND mi.hotel_id = d.hotel_id
             WHERE d.compra_id = ?
               AND d.hotel_id = ?
             ORDER BY d.id ASC"
        );
        $stmt->execute([$compraId, $hotelId]);
        $compra['detalles'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $compra;
    }

    public function listarCompras(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $limite = max(1, min(200, (int)$limite));
        $estado = (string)($filtros['estado'] ?? 'borrador');
        $buscar = trim((string)($filtros['buscar'] ?? ''));

        $where = ['c.hotel_id = ?'];
        $params = [$hotelId];

        if (in_array($estado, ['borrador', 'recibida', 'cancelada'], true)) {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }

        if ($buscar !== '') {
            $where[] = '(c.folio LIKE ? OR p.nombre LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT c.*,
                       p.nombre AS proveedor_nombre,
                       (
                           SELECT COUNT(*)
                           FROM compra_detalles d
                           WHERE d.compra_id = c.id
                             AND d.hotel_id = c.hotel_id
                       ) AS detalle_count
                FROM compras c
                INNER JOIN proveedores p
                   ON p.id = c.proveedor_id
                  AND p.hotel_id = c.hotel_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.id DESC
                LIMIT " . $limite;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'borrador' THEN 1 ELSE 0 END) AS borradores,
                    SUM(CASE WHEN estado = 'recibida' THEN 1 ELSE 0 END) AS recibidas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
                    COALESCE(SUM(CASE WHEN estado = 'borrador' THEN total ELSE 0 END), 0) AS total_borrador
             FROM compras
             WHERE hotel_id = ?"
        );
        $stmt->execute([$hotelId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int)($row['total'] ?? 0),
            'borradores' => (int)($row['borradores'] ?? 0),
            'recibidas' => (int)($row['recibidas'] ?? 0),
            'canceladas' => (int)($row['canceladas'] ?? 0),
            'total_borrador' => $this->decimal($row['total_borrador'] ?? 0),
        ];
    }

    public function catalogosBorrador(int $hotelId): array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');

        $stmt = $this->pdo->prepare(
            "SELECT id, nombre, rfc
             FROM proveedores
             WHERE hotel_id = ?
               AND activo = 1
             ORDER BY nombre ASC
             LIMIT 300"
        );
        $stmt->execute([$hotelId]);
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT id, codigo, nombre, stock_actual, costo_unitario, unidad_medida
             FROM inventario_productos
             WHERE hotel_id = ?
               AND activo = 1
             ORDER BY nombre ASC
             LIMIT 500"
        );
        $stmt->execute([$hotelId]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'proveedores' => $proveedores,
            'productos' => $productos,
        ];
    }

    public function catalogosReporteRecibidas(int $hotelId): array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');

        $stmt = $this->pdo->prepare(
            "SELECT p.id, p.nombre, p.rfc
             FROM proveedores p
             WHERE p.hotel_id = ?
               AND (
                   p.activo = 1
                   OR EXISTS (
                       SELECT 1
                       FROM compras c
                       WHERE c.hotel_id = p.hotel_id
                         AND c.proveedor_id = p.id
                   )
               )
             ORDER BY p.nombre ASC
             LIMIT 500"
        );
        $stmt->execute([$hotelId]);
        $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT ip.id, ip.codigo, ip.nombre, ip.unidad_medida
             FROM inventario_productos ip
             WHERE ip.hotel_id = ?
               AND (
                   ip.activo = 1
                   OR EXISTS (
                       SELECT 1
                       FROM compra_detalles d
                       WHERE d.hotel_id = ip.hotel_id
                         AND d.producto_id = ip.id
                   )
               )
             ORDER BY ip.nombre ASC
             LIMIT 800"
        );
        $stmt->execute([$hotelId]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'proveedores' => $proveedores,
            'productos' => $productos,
        ];
    }

    public function reporteRecibidas(int $hotelId, array $filtros = [], int $limite = 300): array
    {
        $this->assertTablasDisponibles();

        $hotelId = $this->validarId($hotelId, 'Hotel invalido');
        $limite = max(1, min(500, (int)$limite));
        [$filtrosNormalizados, $where, $params] = $this->filtrosReporteRecibidas($hotelId, $filtros);
        $whereSql = implode(' AND ', $where);

        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT c.id) AS compras,
                    COUNT(DISTINCT c.proveedor_id) AS proveedores,
                    COUNT(DISTINCT d.producto_id) AS productos,
                    COALESCE(SUM(d.cantidad), 0) AS cantidad_total,
                    COALESCE(SUM(d.subtotal), 0) AS total_lineas
             FROM compras c
             INNER JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             INNER JOIN compra_detalles d
                ON d.compra_id = c.id
               AND d.hotel_id = c.hotel_id
             INNER JOIN inventario_productos ip
                ON ip.id = d.producto_id
               AND ip.hotel_id = d.hotel_id
             WHERE {$whereSql}"
        );
        $stmt->execute($params);
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT p.id AS proveedor_id,
                    p.nombre AS proveedor_nombre,
                    COUNT(DISTINCT c.id) AS compras,
                    COALESCE(SUM(d.cantidad), 0) AS cantidad_total,
                    COALESCE(SUM(d.subtotal), 0) AS total_lineas
             FROM compras c
             INNER JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             INNER JOIN compra_detalles d
                ON d.compra_id = c.id
               AND d.hotel_id = c.hotel_id
             INNER JOIN inventario_productos ip
                ON ip.id = d.producto_id
               AND ip.hotel_id = d.hotel_id
             WHERE {$whereSql}
             GROUP BY p.id, p.nombre
             ORDER BY total_lineas DESC, p.nombre ASC
             LIMIT 200"
        );
        $stmt->execute($params);
        $porProveedor = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT ip.id AS producto_id,
                    ip.codigo AS producto_codigo,
                    ip.nombre AS producto_nombre,
                    ip.unidad_medida,
                    COUNT(DISTINCT c.id) AS compras,
                    COALESCE(SUM(d.cantidad), 0) AS cantidad_total,
                    COALESCE(SUM(d.subtotal), 0) AS total_lineas
             FROM compras c
             INNER JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             INNER JOIN compra_detalles d
                ON d.compra_id = c.id
               AND d.hotel_id = c.hotel_id
             INNER JOIN inventario_productos ip
                ON ip.id = d.producto_id
               AND ip.hotel_id = d.hotel_id
             WHERE {$whereSql}
             GROUP BY ip.id, ip.codigo, ip.nombre, ip.unidad_medida
             ORDER BY total_lineas DESC, ip.nombre ASC
             LIMIT 300"
        );
        $stmt->execute($params);
        $porProducto = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $this->pdo->prepare(
            "SELECT c.id AS compra_id,
                    c.folio,
                    c.estado,
                    c.fecha_compra,
                    c.fecha_recepcion,
                    COALESCE(DATE(c.fecha_recepcion), c.fecha_compra) AS fecha_reporte,
                    p.id AS proveedor_id,
                    p.nombre AS proveedor_nombre,
                    d.id AS detalle_id,
                    d.producto_id,
                    ip.codigo AS producto_codigo,
                    ip.nombre AS producto_nombre,
                    ip.unidad_medida,
                    d.cantidad,
                    d.costo_unitario,
                    d.subtotal,
                    d.movimiento_inventario_id,
                    mi.tipo_movimiento AS movimiento_tipo,
                    mi.created_at AS movimiento_created_at
             FROM compras c
             INNER JOIN proveedores p
                ON p.id = c.proveedor_id
               AND p.hotel_id = c.hotel_id
             INNER JOIN compra_detalles d
                ON d.compra_id = c.id
               AND d.hotel_id = c.hotel_id
             INNER JOIN inventario_productos ip
                ON ip.id = d.producto_id
               AND ip.hotel_id = d.hotel_id
             LEFT JOIN movimientos_inventario mi
                ON mi.id = d.movimiento_inventario_id
               AND mi.hotel_id = d.hotel_id
             WHERE {$whereSql}
             ORDER BY COALESCE(c.fecha_recepcion, c.fecha_compra) DESC, c.id DESC, d.id ASC
             LIMIT {$limite}"
        );
        $stmt->execute($params);
        $lineas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'filtros' => $filtrosNormalizados,
            'resumen' => [
                'compras' => (int)($resumen['compras'] ?? 0),
                'proveedores' => (int)($resumen['proveedores'] ?? 0),
                'productos' => (int)($resumen['productos'] ?? 0),
                'cantidad_total' => $this->decimal($resumen['cantidad_total'] ?? 0),
                'total_lineas' => $this->decimal($resumen['total_lineas'] ?? 0),
            ],
            'lineas' => $lineas,
            'por_proveedor' => $porProveedor,
            'por_producto' => $porProducto,
        ];
    }

    private function filtrosReporteRecibidas(int $hotelId, array $filtros): array
    {
        $estado = (string)($filtros['estado'] ?? 'recibida');
        $estado = $estado === '' ? 'recibida' : $estado;
        if (!in_array($estado, ['recibida', 'borrador', 'cancelada', 'todos'], true)) {
            throw new Exception('Estado de reporte invalido');
        }

        $proveedorId = $this->normalizarIdOpcional($filtros['proveedor_id'] ?? null, 'Proveedor invalido');
        $productoId = $this->normalizarIdOpcional($filtros['producto_id'] ?? null, 'Producto invalido');
        $fechaInicio = $this->normalizarFechaOpcional($filtros['fecha_inicio'] ?? null, 'Fecha inicial invalida');
        $fechaFin = $this->normalizarFechaOpcional($filtros['fecha_fin'] ?? null, 'Fecha final invalida');

        if ($fechaInicio !== null && $fechaFin !== null && $fechaFin < $fechaInicio) {
            throw new Exception('El rango de fechas del reporte es invalido');
        }

        $where = ['c.hotel_id = ?'];
        $params = [$hotelId];

        if ($estado !== 'todos') {
            $where[] = 'c.estado = ?';
            $params[] = $estado;
        }

        if ($proveedorId !== null) {
            $where[] = 'c.proveedor_id = ?';
            $params[] = $proveedorId;
        }

        if ($productoId !== null) {
            $where[] = 'd.producto_id = ?';
            $params[] = $productoId;
        }

        if ($fechaInicio !== null) {
            $where[] = 'COALESCE(DATE(c.fecha_recepcion), c.fecha_compra) >= ?';
            $params[] = $fechaInicio;
        }

        if ($fechaFin !== null) {
            $where[] = 'COALESCE(DATE(c.fecha_recepcion), c.fecha_compra) <= ?';
            $params[] = $fechaFin;
        }

        return [
            [
                'estado' => $estado,
                'proveedor_id' => $proveedorId,
                'producto_id' => $productoId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
            $where,
            $params,
        ];
    }

    private function insertarDetalles(int $compraId, int $hotelId, array $detalles): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO compra_detalles
                (compra_id, hotel_id, producto_id, cantidad, costo_unitario,
                 subtotal, created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );

        foreach ($detalles as $detalle) {
            $stmt->execute([
                $compraId,
                $hotelId,
                $detalle['producto_id'],
                $detalle['cantidad'],
                $detalle['costo_unitario'],
                $detalle['subtotal'],
            ]);
        }
    }

    private function normalizarDetalles(int $hotelId, array $detalles, bool $lockProductos): array
    {
        if (!$detalles) {
            throw new Exception('La compra requiere al menos un producto');
        }

        $normalizados = [];
        foreach ($detalles as $detalle) {
            $productoId = $this->validarId($detalle['producto_id'] ?? 0, 'Producto invalido');
            $producto = $this->obtenerProductoActivo($hotelId, $productoId, $lockProductos);
            $cantidad = $this->normalizarDecimalPositivo($detalle['cantidad'] ?? 0, 'Cantidad invalida');
            $costo = array_key_exists('costo_unitario', $detalle)
                ? $this->normalizarDecimalNoNegativo($detalle['costo_unitario'], 'Costo unitario invalido')
                : $this->normalizarDecimalNoNegativo($producto['costo_unitario'] ?? 0, 'Costo unitario invalido');

            $normalizados[] = [
                'producto_id' => (int)$producto['id'],
                'cantidad' => $this->decimal($cantidad),
                'costo_unitario' => $this->decimal($costo),
                'subtotal' => $this->decimal($cantidad * $costo),
            ];
        }

        return $normalizados;
    }

    private function calcularTotales(array $detalles): array
    {
        $subtotal = 0.0;
        foreach ($detalles as $detalle) {
            $subtotal += (float)$detalle['subtotal'];
        }

        return [
            'subtotal' => $this->decimal($subtotal),
            'total' => $this->decimal($subtotal),
        ];
    }

    private function obtenerCompraBloqueada(int $hotelId, int $compraId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM compras
             WHERE id = ? AND hotel_id = ?
             LIMIT 1
             FOR UPDATE"
        );
        $stmt->execute([$compraId, $hotelId]);
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compra) {
            throw new Exception('Compra no encontrada para el hotel actual');
        }

        return $compra;
    }

    private function obtenerDetallesBloqueados(int $hotelId, int $compraId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM compra_detalles
             WHERE compra_id = ?
               AND hotel_id = ?
             ORDER BY id ASC
             FOR UPDATE"
        );
        $stmt->execute([$compraId, $hotelId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function obtenerProveedorActivo(int $hotelId, int $proveedorId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, nombre
             FROM proveedores
             WHERE id = ?
               AND hotel_id = ?
               AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([$proveedorId, $hotelId]);
        $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$proveedor) {
            throw new Exception('Proveedor no encontrado para el hotel actual');
        }

        return $proveedor;
    }

    private function obtenerProductoActivo(int $hotelId, int $productoId, bool $lock): array
    {
        $sql = "SELECT id, nombre, stock_actual, costo_unitario
                FROM inventario_productos
                WHERE id = ?
                  AND hotel_id = ?
                  AND activo = 1
                LIMIT 1";

        if ($lock) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productoId, $hotelId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception('Producto no encontrado para el hotel actual');
        }

        return $producto;
    }

    private function validarFolioDisponible(int $hotelId, ?string $folio): void
    {
        if ($folio === null) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM compras
             WHERE hotel_id = ?
               AND folio = ?
             LIMIT 1"
        );
        $stmt->execute([$hotelId, $folio]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception('Ya existe una compra con ese folio en el hotel actual');
        }
    }

    private function assertTablasDisponibles(): void
    {
        foreach (['compras', 'compra_detalles', 'proveedores', 'inventario_productos', 'movimientos_inventario'] as $table) {
            if (!$this->tablaExiste($table)) {
                throw new Exception('Tabla requerida no disponible: ' . $table);
            }
        }
    }

    private function assertTransaccionPropia(): void
    {
        $this->assertTransactionPolicy();
    }

    private function assertTransactionPolicy(): void
    {
        if ($this->manageTransaction && $this->pdo->inTransaction()) {
            throw new Exception('CompraService debe controlar su propia transaccion');
        }

        if (!$this->manageTransaction && !$this->pdo->inTransaction()) {
            throw new Exception('CompraService requiere una transaccion externa activa');
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
        if ($this->manageTransaction) {
            $this->pdo->commit();
        }
    }

    private function tablaExiste(string $table): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?"
        );
        $stmt->execute([$table]);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function validarId($value, string $message): int
    {
        $id = (int)$value;
        if ($id <= 0) {
            throw new Exception($message);
        }

        return $id;
    }

    private function normalizarIdOpcional($value, string $message): ?int
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return null;
        }

        if (!ctype_digit($text)) {
            throw new Exception($message);
        }

        return $this->validarId($text, $message);
    }

    private function normalizarUsuarioId(?int $usuarioId): ?int
    {
        return $usuarioId !== null && $usuarioId > 0 ? $usuarioId : null;
    }

    private function normalizarFechaOpcional($value, string $message): ?string
    {
        $fecha = trim((string)($value ?? ''));
        if ($fecha === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            !$date
            || ($errors !== false && ((int)$errors['warning_count'] > 0 || (int)$errors['error_count'] > 0))
        ) {
            throw new Exception($message);
        }

        return $date->format('Y-m-d');
    }

    private function normalizarFecha($value): string
    {
        $fecha = trim((string)$value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            !$date
            || ($errors !== false && ((int)$errors['warning_count'] > 0 || (int)$errors['error_count'] > 0))
        ) {
            throw new Exception('Fecha de compra invalida');
        }

        return $date->format('Y-m-d');
    }

    private function normalizarTextoNullable($value, int $limit): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit, 'UTF-8');
        }

        return substr($value, 0, $limit);
    }

    private function normalizarDecimalPositivo($value, string $message): float
    {
        $number = $this->normalizarNumero($value, $message);
        if ($number <= 0) {
            throw new Exception($message);
        }

        return $number;
    }

    private function normalizarDecimalNoNegativo($value, string $message): float
    {
        $number = $this->normalizarNumero($value, $message);
        if ($number < 0) {
            throw new Exception($message);
        }

        return $number;
    }

    private function normalizarNumero($value, string $message): float
    {
        if (!is_numeric($value)) {
            throw new Exception($message);
        }

        return round((float)$value, 2);
    }

    private function decimal($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }
}
