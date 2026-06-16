<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class TareaOperativa extends Model
{
    protected $table = 'tareas_operativas';

    public function tablaDisponible(): bool
    {
        return $this->tablaExiste('tareas_operativas');
    }

    public function eventosDisponibles(): bool
    {
        return $this->tablaExiste('tarea_eventos');
    }

    public function listarPorHotel(int $hotelId, array $filtros = [], int $limite = 100): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $limite = max(1, min(300, $limite));
        $where = ['t.hotel_id = ?'];
        $params = [$hotelId];

        foreach (['categoria', 'estado', 'prioridad'] as $filtro) {
            $valor = trim((string)($filtros[$filtro] ?? ''));
            if ($valor !== '' && $valor !== 'todos') {
                $where[] = 't.' . $filtro . ' = ?';
                $params[] = $valor;
            }
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $where[] = '(t.titulo LIKE ? OR t.descripcion LIKE ? OR h.numero LIKE ? OR tr.nombre_completo LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.categoria,
                    t.titulo,
                    t.descripcion,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.reservacion_id,
                    t.huesped_id,
                    t.trabajador_id,
                    t.mantenimiento_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.fecha_inicio,
                    t.fecha_cierre,
                    t.origen,
                    t.created_at,
                    t.updated_at,
                    h.numero AS habitacion_numero,
                    tr.nombre_completo AS trabajador_nombre,
                    m.motivo AS mantenimiento_motivo,
                    hu.nombre_completo AS huesped_nombre
             FROM tareas_operativas t
             LEFT JOIN habitaciones h
                ON h.id = t.habitacion_id
               AND h.hotel_id = t.hotel_id
             LEFT JOIN trabajadores tr
                ON tr.id = t.trabajador_id
               AND tr.hotel_id = t.hotel_id
             LEFT JOIN mantenimientos_habitaciones m
                ON m.id = t.mantenimiento_id
               AND m.hotel_id = t.hotel_id
             LEFT JOIN huespedes hu
                ON hu.id = t.huesped_id
               AND hu.hotel_id = t.hotel_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY FIELD(t.estado, 'pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada'),
                      FIELD(t.prioridad, 'urgente', 'alta', 'media', 'baja'),
                      COALESCE(t.fecha_limite, t.fecha_programada, t.created_at) ASC
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
            "SELECT t.*,
                    h.numero AS habitacion_numero,
                    h.estado AS habitacion_estado,
                    tr.nombre_completo AS trabajador_nombre,
                    tr.estado AS trabajador_estado,
                    m.tipo_mantenimiento,
                    m.motivo AS mantenimiento_motivo,
                    m.estado AS mantenimiento_estado,
                    hu.nombre_completo AS huesped_nombre,
                    uc.nombre_completo AS creada_por_nombre,
                    ua.nombre_completo AS asignada_por_nombre,
                    ucl.nombre_completo AS cerrada_por_nombre
             FROM tareas_operativas t
             LEFT JOIN habitaciones h
                ON h.id = t.habitacion_id
               AND h.hotel_id = t.hotel_id
             LEFT JOIN trabajadores tr
                ON tr.id = t.trabajador_id
               AND tr.hotel_id = t.hotel_id
             LEFT JOIN mantenimientos_habitaciones m
                ON m.id = t.mantenimiento_id
               AND m.hotel_id = t.hotel_id
             LEFT JOIN huespedes hu
                ON hu.id = t.huesped_id
               AND hu.hotel_id = t.hotel_id
             LEFT JOIN usuarios uc
                ON uc.id = t.creada_por_usuario_id
             LEFT JOIN usuarios ua
                ON ua.id = t.asignada_por_usuario_id
             LEFT JOIN usuarios ucl
                ON ucl.id = t.cerrada_por_usuario_id
             WHERE t.id = ?
               AND t.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function eventosPorTarea(int $tareaId, int $hotelId, int $limite = 50): array
    {
        if ($tareaId <= 0 || $hotelId <= 0 || !$this->eventosDisponibles()) {
            return [];
        }

        $limite = max(1, min(100, $limite));
        $stmt = $this->db->query(
            "SELECT e.id,
                    e.tipo_evento,
                    e.estado_anterior,
                    e.estado_nuevo,
                    e.comentario,
                    e.created_at,
                    u.nombre_completo AS usuario_nombre
             FROM tarea_eventos e
             LEFT JOIN usuarios u
                ON u.id = e.usuario_id
             WHERE e.tarea_id = ?
               AND e.hotel_id = ?
             ORDER BY e.created_at DESC, e.id DESC
             LIMIT {$limite}",
            [$tareaId, $hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function resumenPorHotel(int $hotelId): array
    {
        $base = [
            'total' => 0,
            'pendiente' => 0,
            'asignada' => 0,
            'en_proceso' => 0,
            'completada' => 0,
            'cancelada' => 0,
            'limpieza' => 0,
            'mantenimiento' => 0,
            'general' => 0,
        ];

        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $base;
        }

        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                    SUM(CASE WHEN estado = 'asignada' THEN 1 ELSE 0 END) AS asignada,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
                    SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) AS completada,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS cancelada,
                    SUM(CASE WHEN categoria = 'limpieza' THEN 1 ELSE 0 END) AS limpieza,
                    SUM(CASE WHEN categoria = 'mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento,
                    SUM(CASE WHEN categoria = 'general' THEN 1 ELSE 0 END) AS general
             FROM tareas_operativas
             WHERE hotel_id = ?",
            [$hotelId]
        );

        $row = $stmt ? ($stmt->fetch() ?: []) : [];
        foreach ($base as $key => $default) {
            $base[$key] = (int)($row[$key] ?? $default);
        }

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
}
