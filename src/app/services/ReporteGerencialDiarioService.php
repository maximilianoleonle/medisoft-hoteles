<?php

require_once __DIR__ . '/../../core/Database.php';

class ReporteGerencialDiarioService {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function generar(int $hotelId, ?string $fecha = null): array {
        $fecha = $this->normalizarFecha($fecha);

        $finanzas = $this->finanzasDelDia($hotelId, $fecha);
        $habitaciones = $this->habitaciones($hotelId, $fecha);
        $agenda = $this->agenda($hotelId, $fecha);
        $caja = $this->caja($hotelId, $fecha);
        $facturacion = $this->facturacion($hotelId);
        $inventario = $this->inventario($hotelId);

        $riesgos = [
            'checkins_vencidos' => $this->conteoReservacionesVencidas($hotelId, $fecha, 'entrada'),
            'checkouts_vencidos' => $this->conteoReservacionesVencidas($hotelId, $fecha, 'salida'),
            'facturas_pendientes' => (int)($facturacion['pendientes'] ?? 0),
            'habitaciones_limpieza' => (int)($habitaciones['limpieza'] ?? 0),
            'habitaciones_mantenimiento' => (int)($habitaciones['mantenimiento'] ?? 0),
            'inventario_bajo' => (int)($inventario['bajo_minimo'] ?? 0),
        ];

        $riesgos['total'] = array_sum($riesgos);

        return [
            'fecha' => $fecha,
            'generado_en' => date('Y-m-d H:i:s'),
            'finanzas' => $finanzas,
            'habitaciones' => $habitaciones,
            'agenda' => $agenda,
            'caja' => $caja,
            'facturacion' => $facturacion,
            'inventario' => $inventario,
            'riesgos' => $riesgos,
            'severidad' => $this->severidad($riesgos),
        ];
    }

    public function mensajeNotificacion(array $reporte): string {
        $finanzas = $reporte['finanzas'] ?? [];
        $habitaciones = $reporte['habitaciones'] ?? [];
        $agenda = $reporte['agenda'] ?? [];
        $riesgos = $reporte['riesgos'] ?? [];

        $ingresos = (float)($finanzas['ingresos'] ?? 0);
        $balance = (float)($finanzas['balance'] ?? 0);
        $ocupacion = (float)($habitaciones['ocupacion_pct'] ?? 0);
        $entradas = (int)($agenda['entradas']['total'] ?? 0);
        $salidas = (int)($agenda['salidas']['total'] ?? 0);
        $riesgosTotal = (int)($riesgos['total'] ?? 0);

        return sprintf(
            'Resumen diario: ingresos $%s, balance $%s, ocupacion %.1f%%, %d entrada(s), %d salida(s), %d pendiente(s) gerenciales.',
            number_format($ingresos, 2),
            number_format($balance, 2),
            $ocupacion,
            $entradas,
            $salidas,
            $riesgosTotal
        );
    }

    private function finanzasDelDia(int $hotelId, string $fecha): array {
        $row = $this->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) AS gastos,
                COUNT(*) AS movimientos
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND DATE(created_at) = ?",
            [$hotelId, $fecha]
        );

        $porMetodo = $this->fetchAll(
            "SELECT metodo_pago,
                    tipo,
                    COUNT(*) AS cantidad,
                    COALESCE(SUM(monto), 0) AS total
             FROM movimientos_caja
             WHERE hotel_id = ?
               AND DATE(created_at) = ?
             GROUP BY metodo_pago, tipo
             ORDER BY metodo_pago, tipo",
            [$hotelId, $fecha]
        );

        $metodos = [
            'efectivo' => ['ingresos' => 0.0, 'gastos' => 0.0, 'movimientos' => 0],
            'tarjeta' => ['ingresos' => 0.0, 'gastos' => 0.0, 'movimientos' => 0],
            'transferencia' => ['ingresos' => 0.0, 'gastos' => 0.0, 'movimientos' => 0],
        ];

        foreach ($porMetodo as $item) {
            $metodo = (string)($item['metodo_pago'] ?? '');
            $tipo = (string)($item['tipo'] ?? '');
            if (!isset($metodos[$metodo])) {
                continue;
            }

            if ($tipo === 'ingreso') {
                $metodos[$metodo]['ingresos'] = (float)($item['total'] ?? 0);
            } elseif ($tipo === 'gasto') {
                $metodos[$metodo]['gastos'] = (float)($item['total'] ?? 0);
            }

            $metodos[$metodo]['movimientos'] += (int)($item['cantidad'] ?? 0);
        }

        $ingresos = (float)($row['ingresos'] ?? 0);
        $gastos = (float)($row['gastos'] ?? 0);

        return [
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'balance' => $ingresos - $gastos,
            'movimientos' => (int)($row['movimientos'] ?? 0),
            'metodos' => $metodos,
        ];
    }

    private function habitaciones(int $hotelId, string $fecha): array {
        $row = $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) AS ocupadas,
                SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) AS disponibles,
                SUM(CASE WHEN estado = 'limpieza' THEN 1 ELSE 0 END) AS limpieza,
                SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento
             FROM habitaciones
             WHERE hotel_id = ?
               AND activa = 1",
            [$hotelId]
        );

        $porLlegar = $this->fetchOne(
            "SELECT COUNT(DISTINCT rh.habitacion_id) AS total
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r
                ON r.id = rh.reservacion_id
               AND r.hotel_id = rh.hotel_id
             INNER JOIN habitaciones h
                ON h.id = rh.habitacion_id
               AND h.hotel_id = rh.hotel_id
             WHERE rh.hotel_id = ?
               AND r.fecha_entrada = ?
               AND r.estado = 'confirmada'
               AND h.estado = 'disponible'
               AND h.activa = 1",
            [$hotelId, $fecha]
        );

        $total = (int)($row['total'] ?? 0);
        $ocupadas = (int)($row['ocupadas'] ?? 0);
        $disponibles = (int)($row['disponibles'] ?? 0);
        $porLlegarTotal = (int)($porLlegar['total'] ?? 0);

        return [
            'total' => $total,
            'ocupadas' => $ocupadas,
            'disponibles' => $disponibles,
            'disponibles_reales' => max(0, $disponibles - $porLlegarTotal),
            'por_llegar' => $porLlegarTotal,
            'limpieza' => (int)($row['limpieza'] ?? 0),
            'mantenimiento' => (int)($row['mantenimiento'] ?? 0),
            'ocupacion_pct' => $total > 0 ? round(($ocupadas / $total) * 100, 1) : 0.0,
        ];
    }

    private function agenda(int $hotelId, string $fecha): array {
        $entradas = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hora_entrada IS NULL THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) AS completadas
             FROM reservaciones
             WHERE hotel_id = ?
               AND fecha_entrada = ?
               AND estado IN ('confirmada', 'checked_in')",
            [$hotelId, $fecha]
        );

        $salidas = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hora_salida IS NULL THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) AS completadas
             FROM reservaciones
             WHERE hotel_id = ?
               AND fecha_salida = ?
               AND estado IN ('checked_in', 'checked_out')",
            [$hotelId, $fecha]
        );

        $huespedes = $this->fetchOne(
            "SELECT COUNT(DISTINCT r.huesped_id) AS huespedes,
                    COUNT(DISTINCT rh.habitacion_id) AS habitaciones_ocupadas
             FROM reservaciones r
             INNER JOIN reservacion_habitaciones rh
                ON r.id = rh.reservacion_id
               AND rh.hotel_id = r.hotel_id
             WHERE r.hotel_id = ?
               AND r.estado = 'checked_in'
               AND r.fecha_entrada <= ?
               AND r.fecha_salida >= ?",
            [$hotelId, $fecha, $fecha]
        );

        return [
            'entradas' => $this->agendaRow($entradas),
            'salidas' => $this->agendaRow($salidas),
            'huespedes_actuales' => (int)($huespedes['huespedes'] ?? 0),
            'habitaciones_ocupadas' => (int)($huespedes['habitaciones_ocupadas'] ?? 0),
        ];
    }

    private function caja(int $hotelId, string $fecha): array {
        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado = 'abierto' THEN 1 ELSE 0 END) AS abiertas,
                SUM(CASE WHEN estado = 'cerrado' AND DATE(fecha_cierre) = ? THEN 1 ELSE 0 END) AS cerradas
             FROM cortes_caja
             WHERE hotel_id = ?",
            [$fecha, $hotelId]
        );

        return [
            'abiertas' => (int)($row['abiertas'] ?? 0),
            'cerradas' => (int)($row['cerradas'] ?? 0),
        ];
    }

    private function facturacion(int $hotelId): array {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS pendientes
             FROM solicitudes_factura
             WHERE hotel_id = ?
               AND estatus = 'pendiente'",
            [$hotelId]
        );

        return [
            'pendientes' => (int)($row['pendientes'] ?? 0),
        ];
    }

    private function inventario(int $hotelId): array {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS bajo_minimo,
                    SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) AS sin_stock
             FROM inventario_productos
             WHERE hotel_id = ?
               AND activo = 1
               AND stock_actual <= stock_minimo",
            [$hotelId]
        );

        return [
            'bajo_minimo' => (int)($row['bajo_minimo'] ?? 0),
            'sin_stock' => (int)($row['sin_stock'] ?? 0),
        ];
    }

    private function conteoReservacionesVencidas(int $hotelId, string $fecha, string $tipo): int {
        $campo = $tipo === 'salida' ? 'fecha_salida' : 'fecha_entrada';
        $estado = $tipo === 'salida' ? 'checked_in' : 'confirmada';

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM reservaciones
             WHERE hotel_id = ?
               AND {$campo} < ?
               AND estado = ?",
            [$hotelId, $fecha, $estado]
        );

        return (int)($row['total'] ?? 0);
    }

    private function agendaRow(array $row): array {
        return [
            'total' => (int)($row['total'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'completadas' => (int)($row['completadas'] ?? 0),
        ];
    }

    private function severidad(array $riesgos): string {
        if ((int)($riesgos['inventario_bajo'] ?? 0) > 0
            || (int)($riesgos['checkouts_vencidos'] ?? 0) > 0
            || (int)($riesgos['checkins_vencidos'] ?? 0) > 0) {
            return 'media';
        }

        return ((int)($riesgos['total'] ?? 0) > 0) ? 'media' : 'info';
    }

    private function normalizarFecha(?string $fecha): string {
        $fecha = trim((string)($fecha ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return date('Y-m-d');
        }

        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        return $dt ? $dt->format('Y-m-d') : date('Y-m-d');
    }

    private function fetchOne(string $sql, array $params = []): array {
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) {
            return [];
        }

        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    private function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) {
            return [];
        }

        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}
