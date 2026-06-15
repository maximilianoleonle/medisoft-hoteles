<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

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
