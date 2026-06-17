<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class OperacionDiaria extends Model
{
    public function reporteReadOnlyPorHotel(int $hotelId): array
    {
        return [
            'fecha' => date('Y-m-d'),
            'habitaciones' => $this->resumenHabitaciones($hotelId),
            'reservaciones' => $this->resumenReservaciones($hotelId),
            'tareas' => $this->resumenTareas($hotelId),
            'mantenimiento' => $this->resumenMantenimiento($hotelId),
            'trabajadores' => $this->resumenTrabajadores($hotelId),
            'documentos' => $this->documentosRecientes($hotelId),
            'cuentas_por_cobrar' => $this->resumenCuentasPorCobrar($hotelId),
        ];
    }

    public function tablasDisponibles(): array
    {
        $tablas = [
            'habitaciones',
            'tipos_habitacion',
            'reservaciones',
            'reservacion_habitaciones',
            'huespedes',
            'tareas_operativas',
            'tarea_eventos',
            'mantenimientos_habitaciones',
            'trabajadores',
            'documentos',
            'documento_entidades',
            'reservacion_pagos',
            'reservacion_abonos',
        ];

        $disponibles = [];
        foreach ($tablas as $tabla) {
            $disponibles[$tabla] = $this->tablaExiste($tabla);
        }

        return $disponibles;
    }

    private function resumenHabitaciones(int $hotelId): array
    {
        $base = [
            'total' => 0,
            'disponible' => 0,
            'ocupada' => 0,
            'limpieza' => 0,
            'mantenimiento' => 0,
        ];

        if ($hotelId <= 0 || !$this->tablaExiste('habitaciones')) {
            return $base;
        }

        $stmt = $this->db->query(
            "SELECT estado, COUNT(*) AS total
             FROM habitaciones
             WHERE hotel_id = ?
               AND COALESCE(activa, 1) = 1
             GROUP BY estado",
            [$hotelId]
        );

        foreach ($stmt ? ($stmt->fetchAll() ?: []) : [] as $row) {
            $estado = (string)($row['estado'] ?? '');
            if (array_key_exists($estado, $base)) {
                $base[$estado] = (int)($row['total'] ?? 0);
            }
            $base['total'] += (int)($row['total'] ?? 0);
        }

        return $base;
    }

    private function resumenReservaciones(int $hotelId): array
    {
        $base = [
            'llegadas_hoy' => 0,
            'salidas_hoy' => 0,
            'activas' => 0,
            'checkins_vencidos' => 0,
            'checkouts_vencidos' => 0,
            'hoy' => [],
        ];

        if (
            $hotelId <= 0
            || !$this->tablaExiste('reservaciones')
            || !$this->tablaExiste('reservacion_habitaciones')
            || !$this->tablaExiste('habitaciones')
            || !$this->tablaExiste('huespedes')
        ) {
            return $base;
        }

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN fecha_entrada = CURDATE() AND estado = 'confirmada' THEN 1 ELSE 0 END) AS llegadas_hoy,
                SUM(CASE WHEN fecha_salida = CURDATE() AND estado IN ('checked_in', 'checked_out') THEN 1 ELSE 0 END) AS salidas_hoy,
                SUM(CASE WHEN estado = 'checked_in' THEN 1 ELSE 0 END) AS activas,
                SUM(CASE WHEN fecha_entrada < CURDATE() AND estado = 'confirmada' THEN 1 ELSE 0 END) AS checkins_vencidos,
                SUM(CASE WHEN fecha_salida < CURDATE() AND estado = 'checked_in' THEN 1 ELSE 0 END) AS checkouts_vencidos
             FROM reservaciones
             WHERE hotel_id = ?",
            [$hotelId]
        );

        foreach (['llegadas_hoy', 'salidas_hoy', 'activas', 'checkins_vencidos', 'checkouts_vencidos'] as $key) {
            $base[$key] = (int)($row[$key] ?? 0);
        }

        $stmt = $this->db->query(
            "SELECT r.id,
                    r.estado,
                    r.fecha_entrada,
                    r.hora_llegada_estimada,
                    r.fecha_salida,
                    r.hora_entrada,
                    r.hora_salida,
                    h.nombre_completo AS huesped_nombre,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
             FROM reservaciones r
             INNER JOIN huespedes h
                ON h.id = r.huesped_id
             INNER JOIN reservacion_habitaciones rh
                ON rh.reservacion_id = r.id
               AND rh.hotel_id = r.hotel_id
             INNER JOIN habitaciones hab
                ON hab.id = rh.habitacion_id
               AND hab.hotel_id = r.hotel_id
             WHERE r.hotel_id = ?
               AND (r.fecha_entrada = CURDATE() OR r.fecha_salida = CURDATE())
               AND r.estado IN ('confirmada', 'checked_in', 'checked_out')
             GROUP BY r.id
             ORDER BY r.fecha_entrada ASC, r.hora_llegada_estimada ASC, r.id ASC
             LIMIT 12",
            [$hotelId]
        );
        $base['hoy'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        return $base;
    }

    private function resumenTareas(int $hotelId): array
    {
        $base = [
            'total' => 0,
            'activas' => 0,
            'urgentes' => 0,
            'vencidas' => 0,
            'recientes' => [],
        ];

        if ($hotelId <= 0 || !$this->tablaExiste('tareas_operativas')) {
            return $base;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS activas,
                    SUM(CASE WHEN prioridad = 'urgente' AND estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS urgentes,
                    SUM(CASE WHEN fecha_limite IS NOT NULL AND fecha_limite < NOW() AND estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS vencidas
             FROM tareas_operativas
             WHERE hotel_id = ?",
            [$hotelId]
        );

        foreach (['total', 'activas', 'urgentes', 'vencidas'] as $key) {
            $base[$key] = (int)($row[$key] ?? 0);
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.titulo,
                    t.categoria,
                    t.prioridad,
                    t.estado,
                    t.fecha_limite,
                    h.numero AS habitacion_numero,
                    tr.nombre_completo AS trabajador_nombre
             FROM tareas_operativas t
             LEFT JOIN habitaciones h
                ON h.id = t.habitacion_id
               AND h.hotel_id = t.hotel_id
             LEFT JOIN trabajadores tr
                ON tr.id = t.trabajador_id
               AND tr.hotel_id = t.hotel_id
             WHERE t.hotel_id = ?
             ORDER BY FIELD(t.estado, 'en_proceso', 'asignada', 'pendiente', 'completada', 'cancelada'),
                      FIELD(t.prioridad, 'urgente', 'alta', 'media', 'baja'),
                      COALESCE(t.fecha_limite, t.updated_at, t.created_at) ASC
             LIMIT 10",
            [$hotelId]
        );
        $base['recientes'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        return $base;
    }

    private function resumenMantenimiento(int $hotelId): array
    {
        $base = [
            'en_proceso' => 0,
            'programado' => 0,
            'urgente' => 0,
            'recientes' => [],
        ];

        if ($hotelId <= 0 || !$this->tablaExiste('mantenimientos_habitaciones')) {
            return $base;
        }

        $row = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
                SUM(CASE WHEN estado = 'programado' THEN 1 ELSE 0 END) AS programado,
                SUM(CASE WHEN prioridad = 'urgente' AND estado IN ('en_proceso', 'programado') THEN 1 ELSE 0 END) AS urgente
             FROM mantenimientos_habitaciones
             WHERE hotel_id = ?",
            [$hotelId]
        );

        foreach (['en_proceso', 'programado', 'urgente'] as $key) {
            $base[$key] = (int)($row[$key] ?? 0);
        }

        $stmt = $this->db->query(
            "SELECT m.id,
                    m.tipo_mantenimiento,
                    m.motivo,
                    m.prioridad,
                    m.estado,
                    m.fecha_inicio,
                    m.fecha_programada,
                    h.numero AS habitacion_numero
             FROM mantenimientos_habitaciones m
             INNER JOIN habitaciones h
                ON h.id = m.habitacion_id
               AND h.hotel_id = m.hotel_id
             WHERE m.hotel_id = ?
             ORDER BY FIELD(m.estado, 'en_proceso', 'programado', 'completado', 'cancelado'),
                      FIELD(m.prioridad, 'urgente', 'alta', 'media', 'baja'),
                      COALESCE(m.fecha_programada, DATE(m.fecha_inicio), DATE(m.created_at)) DESC
             LIMIT 8",
            [$hotelId]
        );
        $base['recientes'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        return $base;
    }

    private function resumenTrabajadores(int $hotelId): array
    {
        $base = [
            'total' => 0,
            'activos' => 0,
            'con_tareas_activas' => 0,
        ];

        if ($hotelId <= 0 || !$this->tablaExiste('trabajadores')) {
            return $base;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos
             FROM trabajadores
             WHERE hotel_id = ?",
            [$hotelId]
        );
        $base['total'] = (int)($row['total'] ?? 0);
        $base['activos'] = (int)($row['activos'] ?? 0);

        if ($this->tablaExiste('tareas_operativas')) {
            $row = $this->fetchOne(
                "SELECT COUNT(DISTINCT trabajador_id) AS total
                 FROM tareas_operativas
                 WHERE hotel_id = ?
                   AND trabajador_id IS NOT NULL
                   AND estado IN ('pendiente', 'asignada', 'en_proceso')",
                [$hotelId]
            );
            $base['con_tareas_activas'] = (int)($row['total'] ?? 0);
        }

        return $base;
    }

    private function documentosRecientes(int $hotelId): array
    {
        if (
            $hotelId <= 0
            || !$this->tablaExiste('documentos')
            || !$this->tablaExiste('documento_entidades')
        ) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT d.id,
                    d.titulo,
                    d.nombre_original,
                    d.mime_type,
                    d.estado,
                    d.created_at,
                    de.entidad_tipo,
                    de.entidad_id,
                    de.relacion
             FROM documentos d
             LEFT JOIN documento_entidades de
                ON de.documento_id = d.id
               AND de.hotel_id = d.hotel_id
             WHERE d.hotel_id = ?
               AND d.estado = 'activo'
             ORDER BY d.created_at DESC, d.id DESC
             LIMIT 8",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    private function resumenCuentasPorCobrar(int $hotelId): array
    {
        $base = [
            'total_reservaciones' => 0,
            'pendientes' => 0,
            'liquidadas' => 0,
            'excedentes' => 0,
            'monto_cubierto' => '0.00',
            'saldo_estimado' => '0.00',
        ];

        if (
            $hotelId <= 0
            || !$this->tablaExiste('reservaciones')
            || !$this->tablaExiste('reservacion_pagos')
            || !$this->tablaExiste('reservacion_abonos')
        ) {
            return $base;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total_reservaciones,
                    SUM(CASE WHEN saldo > 0.009 THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN saldo BETWEEN -0.009 AND 0.009 THEN 1 ELSE 0 END) AS liquidadas,
                    SUM(CASE WHEN saldo < -0.009 THEN 1 ELSE 0 END) AS excedentes,
                    SUM(monto_cubierto) AS monto_cubierto,
                    SUM(CASE WHEN saldo > 0 THEN saldo ELSE 0 END) AS saldo_estimado
             FROM (
                SELECT r.id,
                       COALESCE(p.total, 0) + COALESCE(a.total, 0) AS monto_cubierto,
                       r.precio_total - COALESCE(p.total, 0) - COALESCE(a.total, 0) AS saldo
                FROM reservaciones r
                LEFT JOIN (
                    SELECT hotel_id,
                           reservacion_id,
                           SUM(monto) AS total
                    FROM reservacion_pagos
                    WHERE hotel_id = ?
                    GROUP BY hotel_id, reservacion_id
                ) p
                   ON p.hotel_id = r.hotel_id
                  AND p.reservacion_id = r.id
                LEFT JOIN (
                    SELECT hotel_id,
                           reservacion_id,
                           SUM(monto) AS total
                    FROM reservacion_abonos
                    WHERE hotel_id = ?
                    GROUP BY hotel_id, reservacion_id
                ) a
                   ON a.hotel_id = r.hotel_id
                  AND a.reservacion_id = r.id
                WHERE r.hotel_id = ?
             ) cxc",
            [$hotelId, $hotelId, $hotelId]
        );

        foreach (['total_reservaciones', 'pendientes', 'liquidadas', 'excedentes'] as $key) {
            $base[$key] = (int)($row[$key] ?? 0);
        }
        $base['monto_cubierto'] = $this->decimal($row['monto_cubierto'] ?? 0);
        $base['saldo_estimado'] = $this->decimal($row['saldo_estimado'] ?? 0);

        return $base;
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?",
            [$tabla]
        );
        $row = $stmt ? $stmt->fetch() : null;

        return (int)($row['total'] ?? 0) > 0;
    }

    private function fetchOne(string $sql, array $params = []): array
    {
        $stmt = $this->db->query($sql, $params);
        return $stmt ? ($stmt->fetch() ?: []) : [];
    }

    private function decimal($value): string
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}
