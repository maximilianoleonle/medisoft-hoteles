<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class CuentaPorCobrar extends Model
{
    protected $table = 'reservaciones';

    public function tablasDisponibles(): bool
    {
        foreach (['reservaciones', 'huespedes', 'reservacion_pagos', 'reservacion_abonos', 'solicitudes_factura'] as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                return false;
            }
        }

        return true;
    }

    public function listarDerivadasPorHotel(int $hotelId, array $filtros = [], int $limite = 200): array
    {
        if ($hotelId <= 0 || !$this->tablasDisponibles()) {
            return [];
        }

        $limite = max(1, min(500, $limite));
        $where = ['r.hotel_id = ?'];
        $params = [$hotelId, $hotelId, $hotelId, $hotelId];

        $estadoReservacion = $this->normalizarEstadoReservacion($filtros['estado_reservacion'] ?? 'todas');
        if ($estadoReservacion !== 'todas') {
            $where[] = 'r.estado = ?';
            $params[] = $estadoReservacion;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(CAST(r.id AS CHAR) LIKE ? OR h.nombre_completo LIKE ? OR h.telefono LIKE ? OR h.email LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT r.id AS reservacion_id,
                    r.hotel_id,
                    r.huesped_id,
                    h.nombre_completo AS huesped_nombre,
                    h.telefono AS huesped_telefono,
                    h.email AS huesped_email,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.estado AS reservacion_estado,
                    r.precio_total,
                    COALESCE(p.pagos_total, 0) AS pagos_total,
                    COALESCE(p.pagos_count, 0) AS pagos_count,
                    COALESCE(a.abonos_total, 0) AS abonos_total,
                    COALESCE(a.abonos_count, 0) AS abonos_count,
                    sf.solicitud_factura_id,
                    COALESCE(sf.solicitud_factura_count, 0) AS solicitud_factura_count,
                    sf.factura_estatus,
                    sf.requiere_factura,
                    sf.numero_factura,
                    (r.precio_total - COALESCE(p.pagos_total, 0) - COALESCE(a.abonos_total, 0)) AS saldo_estimado_raw
             FROM reservaciones r
             INNER JOIN huespedes h
                ON h.id = r.huesped_id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS pagos_count,
                        SUM(monto) AS pagos_total
                 FROM reservacion_pagos
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) p
                ON p.hotel_id = r.hotel_id
               AND p.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        COUNT(*) AS abonos_count,
                        SUM(monto) AS abonos_total
                 FROM reservacion_abonos
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) a
                ON a.hotel_id = r.hotel_id
               AND a.reservacion_id = r.id
             LEFT JOIN (
                 SELECT hotel_id,
                        reservacion_id,
                        MAX(id) AS solicitud_factura_id,
                        COUNT(*) AS solicitud_factura_count,
                        MAX(estatus) AS factura_estatus,
                        MAX(requiere_factura) AS requiere_factura,
                        MAX(numero_factura) AS numero_factura
                 FROM solicitudes_factura
                 WHERE hotel_id = ?
                 GROUP BY hotel_id, reservacion_id
             ) sf
                ON sf.hotel_id = r.hotel_id
               AND sf.reservacion_id = r.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE WHEN (r.precio_total - COALESCE(p.pagos_total, 0) - COALESCE(a.abonos_total, 0)) > 0 THEN 0 ELSE 1 END,
                r.fecha_entrada DESC,
                r.id DESC
             LIMIT {$limite}",
            $params
        );

        $filas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        $cuentas = array_map([$this, 'normalizarCuentaDerivada'], $filas);
        $estadoSaldo = $this->normalizarEstadoSaldo($filtros['estado_saldo'] ?? 'todas');

        if ($estadoSaldo !== 'todas') {
            $cuentas = array_values(array_filter($cuentas, static function (array $cuenta) use ($estadoSaldo): bool {
                return (string)($cuenta['estado_saldo'] ?? '') === $estadoSaldo;
            }));
        }

        return array_slice($cuentas, 0, $limite);
    }

    public function resumenPorCuentas(array $cuentas): array
    {
        $resumen = [
            'total' => count($cuentas),
            'pendientes' => 0,
            'liquidadas' => 0,
            'excedentes' => 0,
            'total_reservado' => '0.00',
            'total_cubierto' => '0.00',
            'saldo_estimado' => '0.00',
        ];

        foreach ($cuentas as $cuenta) {
            $estado = (string)($cuenta['estado_saldo'] ?? '');
            if ($estado === 'pendiente') {
                $resumen['pendientes']++;
            } elseif ($estado === 'liquidada') {
                $resumen['liquidadas']++;
            } elseif ($estado === 'excedente') {
                $resumen['excedentes']++;
            }

            $resumen['total_reservado'] = $this->decimal((float)$resumen['total_reservado'] + (float)($cuenta['precio_total'] ?? 0));
            $resumen['total_cubierto'] = $this->decimal((float)$resumen['total_cubierto'] + (float)($cuenta['monto_cubierto'] ?? 0));
            $resumen['saldo_estimado'] = $this->decimal((float)$resumen['saldo_estimado'] + (float)($cuenta['saldo_estimado'] ?? 0));
        }

        return $resumen;
    }

    private function normalizarCuentaDerivada(array $cuenta): array
    {
        $total = (float)($cuenta['precio_total'] ?? 0);
        $pagos = (float)($cuenta['pagos_total'] ?? 0);
        $abonos = (float)($cuenta['abonos_total'] ?? 0);
        $cubierto = $pagos + $abonos;
        $saldoRaw = $total - $cubierto;
        $saldo = max(0, $saldoRaw);

        if ($saldoRaw < -0.009) {
            $estadoSaldo = 'excedente';
        } elseif ($saldo <= 0.009) {
            $estadoSaldo = 'liquidada';
        } else {
            $estadoSaldo = 'pendiente';
        }

        $cuenta['precio_total'] = $this->decimal($total);
        $cuenta['pagos_total'] = $this->decimal($pagos);
        $cuenta['abonos_total'] = $this->decimal($abonos);
        $cuenta['monto_cubierto'] = $this->decimal($cubierto);
        $cuenta['saldo_estimado'] = $this->decimal($saldo);
        $cuenta['saldo_estimado_raw'] = $this->decimal($saldoRaw);
        $cuenta['estado_saldo'] = $estadoSaldo;

        return $cuenta;
    }

    private function normalizarEstadoReservacion($value): string
    {
        $estado = (string)($value ?? 'todas');
        return in_array($estado, ['confirmada', 'checked_in', 'checked_out', 'cancelada', 'todas'], true)
            ? $estado
            : 'todas';
    }

    private function normalizarEstadoSaldo($value): string
    {
        $estado = (string)($value ?? 'todas');
        return in_array($estado, ['pendiente', 'liquidada', 'excedente', 'todas'], true)
            ? $estado
            : 'todas';
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
