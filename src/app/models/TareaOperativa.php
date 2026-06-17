<?php

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class TareaOperativa extends Model
{
    protected $table = 'tareas_operativas';

    private const CATEGORIAS = ['limpieza', 'mantenimiento', 'general'];
    private const PRIORIDADES = ['baja', 'media', 'alta', 'urgente'];

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
                    NULL AS huesped_nombre
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
                    NULL AS huesped_nombre,
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

    public function listarPorEntidadHotel(int $hotelId, string $entidad, int $entidadId, int $limite = 10): array
    {
        if ($hotelId <= 0 || $entidadId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $columnas = [
            'habitacion' => 't.habitacion_id',
            'trabajador' => 't.trabajador_id',
            'mantenimiento' => 't.mantenimiento_id',
        ];

        if (!isset($columnas[$entidad])) {
            return [];
        }

        $limite = max(1, min(30, $limite));
        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.categoria,
                    t.titulo,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.trabajador_id,
                    t.mantenimiento_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.fecha_inicio,
                    t.fecha_cierre,
                    t.origen,
                    t.created_at,
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
               AND {$columnas[$entidad]} = ?
             ORDER BY FIELD(t.estado, 'en_proceso', 'asignada', 'pendiente', 'completada', 'cancelada'),
                      COALESCE(t.fecha_limite, t.fecha_programada, t.created_at) DESC
             LIMIT {$limite}",
            [$hotelId, $entidadId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function buscarTareaActivaPorMantenimientoHotel(int $hotelId, int $mantenimientoId): ?array
    {
        if ($hotelId <= 0 || $mantenimientoId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.categoria,
                    t.titulo,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.trabajador_id,
                    t.mantenimiento_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.origen,
                    t.created_at,
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
               AND t.mantenimiento_id = ?
               AND t.estado IN ('pendiente', 'asignada', 'en_proceso')
             ORDER BY FIELD(t.estado, 'en_proceso', 'asignada', 'pendiente'),
                      COALESCE(t.fecha_limite, t.fecha_programada, t.created_at) ASC,
                      t.id ASC
             LIMIT 1",
            [$hotelId, $mantenimientoId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    public function buscarTareaActivaLimpiezaPorHabitacionHotel(int $hotelId, int $habitacionId): ?array
    {
        if ($hotelId <= 0 || $habitacionId <= 0 || !$this->tablaDisponible()) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.categoria,
                    t.titulo,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.trabajador_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.origen,
                    t.created_at,
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
               AND t.habitacion_id = ?
               AND t.categoria = 'limpieza'
               AND t.estado IN ('pendiente', 'asignada', 'en_proceso')
             ORDER BY FIELD(t.estado, 'en_proceso', 'asignada', 'pendiente'),
                      COALESCE(t.fecha_limite, t.fecha_programada, t.created_at) ASC,
                      t.id ASC
             LIMIT 1",
            [$hotelId, $habitacionId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
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

    public function resumenActivoPorHabitacionesHotel(int $hotelId, array $habitacionIds): array
    {
        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return [];
        }

        $ids = [];
        foreach ($habitacionIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$hotelId], array_values($ids));

        $stmt = $this->db->query(
            "SELECT t.habitacion_id,
                    COUNT(*) AS total_activas,
                    SUM(CASE WHEN t.categoria = 'limpieza' THEN 1 ELSE 0 END) AS limpieza,
                    SUM(CASE WHEN t.categoria = 'mantenimiento' THEN 1 ELSE 0 END) AS mantenimiento,
                    SUM(CASE WHEN t.categoria = 'general' THEN 1 ELSE 0 END) AS general,
                    SUM(CASE WHEN t.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendiente,
                    SUM(CASE WHEN t.estado = 'asignada' THEN 1 ELSE 0 END) AS asignada,
                    SUM(CASE WHEN t.estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
                    MIN(t.fecha_limite) AS proxima_fecha_limite
             FROM tareas_operativas t
             INNER JOIN habitaciones h
                ON h.id = t.habitacion_id
               AND h.hotel_id = t.hotel_id
             WHERE t.hotel_id = ?
               AND t.habitacion_id IN ({$placeholders})
               AND t.estado IN ('pendiente', 'asignada', 'en_proceso')
             GROUP BY t.habitacion_id",
            $params
        );

        $resumen = [];
        foreach ($stmt ? ($stmt->fetchAll() ?: []) : [] as $row) {
            $habitacionId = (int)($row['habitacion_id'] ?? 0);
            if ($habitacionId <= 0) {
                continue;
            }

            foreach ([
                'total_activas',
                'limpieza',
                'mantenimiento',
                'general',
                'pendiente',
                'asignada',
                'en_proceso',
            ] as $campo) {
                $row[$campo] = (int)($row[$campo] ?? 0);
            }

            $resumen[$habitacionId] = $row;
        }

        return $resumen;
    }

    public function reporteReadOnlyPorHotel(int $hotelId): array
    {
        $reporte = [
            'resumen' => $this->resumenPorHotel($hotelId),
            'prioridades' => [
                'baja' => 0,
                'media' => 0,
                'alta' => 0,
                'urgente' => 0,
            ],
            'riesgos' => [
                'vencidas' => 0,
                'proximas_24h' => 0,
                'sin_asignar_activas' => 0,
            ],
            'por_trabajador' => [],
            'por_habitacion' => [],
            'recientes' => [],
            'eventos_recientes' => [],
        ];

        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $reporte;
        }

        $stmt = $this->db->query(
            "SELECT prioridad, COUNT(*) AS total
             FROM tareas_operativas
             WHERE hotel_id = ?
             GROUP BY prioridad",
            [$hotelId]
        );
        foreach ($stmt ? ($stmt->fetchAll() ?: []) : [] as $row) {
            $prioridad = (string)($row['prioridad'] ?? '');
            if (array_key_exists($prioridad, $reporte['prioridades'])) {
                $reporte['prioridades'][$prioridad] = (int)($row['total'] ?? 0);
            }
        }

        $riesgos = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') AND fecha_limite IS NOT NULL AND fecha_limite < NOW() THEN 1 ELSE 0 END) AS vencidas,
                SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') AND fecha_limite IS NOT NULL AND fecha_limite >= NOW() AND fecha_limite <= DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS proximas_24h,
                SUM(CASE WHEN estado IN ('pendiente', 'asignada', 'en_proceso') AND trabajador_id IS NULL THEN 1 ELSE 0 END) AS sin_asignar_activas
             FROM tareas_operativas
             WHERE hotel_id = ?",
            [$hotelId]
        );
        foreach (['vencidas', 'proximas_24h', 'sin_asignar_activas'] as $key) {
            $reporte['riesgos'][$key] = (int)($riesgos[$key] ?? 0);
        }

        $stmt = $this->db->query(
            "SELECT t.trabajador_id,
                    tr.nombre_completo AS trabajador_nombre,
                    COUNT(*) AS total,
                    SUM(CASE WHEN t.estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS activas,
                    SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) AS completadas
             FROM tareas_operativas t
             INNER JOIN trabajadores tr
                ON tr.id = t.trabajador_id
               AND tr.hotel_id = t.hotel_id
             WHERE t.hotel_id = ?
               AND t.trabajador_id IS NOT NULL
             GROUP BY t.trabajador_id, tr.nombre_completo
             ORDER BY activas DESC, total DESC, tr.nombre_completo ASC
             LIMIT 12",
            [$hotelId]
        );
        $reporte['por_trabajador'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT t.habitacion_id,
                    h.numero AS habitacion_numero,
                    COUNT(*) AS total,
                    SUM(CASE WHEN t.estado IN ('pendiente', 'asignada', 'en_proceso') THEN 1 ELSE 0 END) AS activas,
                    SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) AS completadas
             FROM tareas_operativas t
             INNER JOIN habitaciones h
                ON h.id = t.habitacion_id
               AND h.hotel_id = t.hotel_id
             WHERE t.hotel_id = ?
               AND t.habitacion_id IS NOT NULL
             GROUP BY t.habitacion_id, h.numero
             ORDER BY activas DESC, total DESC, CAST(h.numero AS UNSIGNED), h.numero
             LIMIT 12",
            [$hotelId]
        );
        $reporte['por_habitacion'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.categoria,
                    t.titulo,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.trabajador_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.updated_at,
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
             ORDER BY t.updated_at DESC, t.id DESC
             LIMIT 15",
            [$hotelId]
        );
        $reporte['recientes'] = $stmt ? ($stmt->fetchAll() ?: []) : [];

        if ($this->eventosDisponibles()) {
            $stmt = $this->db->query(
                "SELECT e.id,
                        e.tarea_id,
                        e.tipo_evento,
                        e.estado_anterior,
                        e.estado_nuevo,
                        e.comentario,
                        e.created_at,
                        t.titulo AS tarea_titulo,
                        u.nombre_completo AS usuario_nombre
                 FROM tarea_eventos e
                 INNER JOIN tareas_operativas t
                    ON t.id = e.tarea_id
                   AND t.hotel_id = e.hotel_id
                 LEFT JOIN usuarios u
                    ON u.id = e.usuario_id
                 WHERE e.hotel_id = ?
                 ORDER BY e.created_at DESC, e.id DESC
                 LIMIT 15",
                [$hotelId]
            );
            $reporte['eventos_recientes'] = $stmt ? ($stmt->fetchAll() ?: []) : [];
        }

        return $reporte;
    }

    public function agendaReadOnlyPorHotel(int $hotelId, array $filtros = []): array
    {
        $desde = $this->normalizarFechaSimple($filtros['desde'] ?? null, date('Y-m-d'));
        $hasta = $this->normalizarFechaSimple($filtros['hasta'] ?? null, $desde);

        $desdeTs = strtotime($desde);
        $hastaTs = strtotime($hasta);
        if ($hastaTs < $desdeTs) {
            [$desde, $hasta] = [$hasta, $desde];
            [$desdeTs, $hastaTs] = [$hastaTs, $desdeTs];
        }

        $maxHasta = strtotime('+31 days', $desdeTs);
        if ($hastaTs > $maxHasta) {
            $hasta = date('Y-m-d', $maxHasta);
        }

        $estado = strtolower(trim((string)($filtros['estado'] ?? 'activos')));
        $estadosPermitidos = ['todos', 'activos', 'pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            $estado = 'activos';
        }

        $categoria = strtolower(trim((string)($filtros['categoria'] ?? 'todos')));
        if ($categoria !== 'todos' && !in_array($categoria, self::CATEGORIAS, true)) {
            $categoria = 'todos';
        }

        $trabajadorId = $this->normalizarEnteroNullable($filtros['trabajador_id'] ?? null);

        $agenda = [
            'filtros' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'trabajador_id' => $trabajadorId ?: 'todos',
                'categoria' => $categoria,
                'estado' => $estado,
            ],
            'resumen' => [
                'total' => 0,
                'activas' => 0,
                'sin_asignar' => 0,
                'por_estado' => [],
                'por_categoria' => [],
                'por_trabajador' => [],
            ],
            'tareas' => [],
        ];

        if ($hotelId <= 0 || !$this->tablaDisponible()) {
            return $agenda;
        }

        $where = [
            't.hotel_id = ?',
            'DATE(COALESCE(t.fecha_programada, t.fecha_limite, t.created_at)) BETWEEN ? AND ?',
        ];
        $params = [$hotelId, $desde, $hasta];

        if ($estado === 'activos') {
            $where[] = "t.estado IN ('pendiente', 'asignada', 'en_proceso')";
        } elseif ($estado !== 'todos') {
            $where[] = 't.estado = ?';
            $params[] = $estado;
        }

        if ($categoria !== 'todos') {
            $where[] = 't.categoria = ?';
            $params[] = $categoria;
        }

        if ($trabajadorId !== null) {
            $where[] = 't.trabajador_id = ?';
            $params[] = $trabajadorId;
        }

        $stmt = $this->db->query(
            "SELECT t.id,
                    t.hotel_id,
                    t.categoria,
                    t.titulo,
                    t.prioridad,
                    t.estado,
                    t.habitacion_id,
                    t.trabajador_id,
                    t.mantenimiento_id,
                    t.fecha_programada,
                    t.fecha_limite,
                    t.fecha_inicio,
                    t.fecha_cierre,
                    t.origen,
                    t.created_at,
                    DATE(COALESCE(t.fecha_programada, t.fecha_limite, t.created_at)) AS fecha_agenda,
                    h.numero AS habitacion_numero,
                    h.estado AS habitacion_estado,
                    tr.nombre_completo AS trabajador_nombre,
                    tr.rol_laboral AS trabajador_rol,
                    m.tipo_mantenimiento,
                    m.motivo AS mantenimiento_motivo
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
             WHERE " . implode(' AND ', $where) . "
             ORDER BY fecha_agenda ASC,
                      CASE WHEN tr.nombre_completo IS NULL THEN 1 ELSE 0 END,
                      tr.nombre_completo ASC,
                      FIELD(t.estado, 'en_proceso', 'asignada', 'pendiente', 'completada', 'cancelada'),
                      FIELD(t.prioridad, 'urgente', 'alta', 'media', 'baja'),
                      t.id ASC
             LIMIT 300",
            $params
        );

        $tareas = $stmt ? ($stmt->fetchAll() ?: []) : [];
        $agenda['tareas'] = $tareas;
        $agenda['resumen']['total'] = count($tareas);

        foreach ($tareas as $tarea) {
            $estadoTarea = (string)($tarea['estado'] ?? 'pendiente');
            $categoriaTarea = (string)($tarea['categoria'] ?? 'general');
            $trabajadorNombre = trim((string)($tarea['trabajador_nombre'] ?? ''));
            if ($trabajadorNombre === '') {
                $trabajadorNombre = 'Sin asignar';
            }

            if (in_array($estadoTarea, ['pendiente', 'asignada', 'en_proceso'], true)) {
                $agenda['resumen']['activas']++;
                if (empty($tarea['trabajador_id'])) {
                    $agenda['resumen']['sin_asignar']++;
                }
            }

            $agenda['resumen']['por_estado'][$estadoTarea] = ($agenda['resumen']['por_estado'][$estadoTarea] ?? 0) + 1;
            $agenda['resumen']['por_categoria'][$categoriaTarea] = ($agenda['resumen']['por_categoria'][$categoriaTarea] ?? 0) + 1;
            $agenda['resumen']['por_trabajador'][$trabajadorNombre] = ($agenda['resumen']['por_trabajador'][$trabajadorNombre] ?? 0) + 1;
        }

        arsort($agenda['resumen']['por_trabajador']);

        return $agenda;
    }

    public function habitacionesOpciones(int $hotelId): array
    {
        if ($hotelId <= 0) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id, numero, estado
             FROM habitaciones
             WHERE hotel_id = ?
               AND COALESCE(activa, 1) = 1
             ORDER BY CAST(numero AS UNSIGNED), numero",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function trabajadoresActivosOpciones(int $hotelId): array
    {
        if ($hotelId <= 0 || !$this->tablaExiste('trabajadores')) {
            return [];
        }

        $stmt = $this->db->query(
            "SELECT id, nombre_completo, rol_laboral
             FROM trabajadores
             WHERE hotel_id = ?
               AND estado = 'activo'
             ORDER BY nombre_completo ASC, id ASC",
            [$hotelId]
        );

        return $stmt ? ($stmt->fetchAll() ?: []) : [];
    }

    public function crearParaHotel(int $hotelId, array $datos, ?int $usuarioId = null): int
    {
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido para crear la tarea.');
        }

        if (!$this->tablaDisponible() || !$this->eventosDisponibles()) {
            throw new RuntimeException('La base de tareas operativas no esta disponible.');
        }

        $datos = $this->normalizarDatos($datos, $hotelId);
        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "INSERT INTO tareas_operativas
                    (hotel_id, categoria, titulo, descripcion, prioridad, estado,
                     habitacion_id, fecha_programada, fecha_limite, creada_por_usuario_id,
                     origen, created_at)
                 VALUES
                    (?, ?, ?, ?, ?, 'pendiente',
                     ?, ?, ?, ?,
                     'manual', NOW())",
                [
                    $hotelId,
                    $datos['categoria'],
                    $datos['titulo'],
                    $datos['descripcion'],
                    $datos['prioridad'],
                    $datos['habitacion_id'],
                    $datos['fecha_programada'],
                    $datos['fecha_limite'],
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new RuntimeException('No se pudo insertar la tarea operativa.');
            }

            $tareaId = (int)$this->db->lastInsertId();
            $this->registrarEvento(
                $hotelId,
                $tareaId,
                'creada',
                null,
                'pendiente',
                'Tarea creada manualmente.',
                $usuarioId
            );

            $this->db->safeCommit();
            return $tareaId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function crearDesdeMantenimientoParaHotel(int $hotelId, int $mantenimientoId, array $datos = [], ?int $usuarioId = null): int
    {
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido para crear la tarea.');
        }

        if ($mantenimientoId <= 0) {
            throw new InvalidArgumentException('Mantenimiento no valido para crear la tarea.');
        }

        if (!$this->tablaDisponible() || !$this->eventosDisponibles()) {
            throw new RuntimeException('La base de tareas operativas no esta disponible.');
        }

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "SELECT m.id,
                        m.hotel_id,
                        m.habitacion_id,
                        m.tipo_mantenimiento,
                        m.prioridad,
                        m.motivo,
                        m.descripcion,
                        m.fecha_programada,
                        m.fecha_programada_fin,
                        m.fecha_inicio,
                        m.estado,
                        h.numero AS habitacion_numero
                 FROM mantenimientos_habitaciones m
                 INNER JOIN habitaciones h
                    ON h.id = m.habitacion_id
                   AND h.hotel_id = m.hotel_id
                 WHERE m.id = ?
                   AND m.hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$mantenimientoId, $hotelId]
            );
            $mantenimiento = $stmt ? $stmt->fetch() : null;
            if (!$mantenimiento) {
                throw new RuntimeException('Mantenimiento no encontrado para el hotel actual.');
            }

            $duplicado = $this->db->query(
                "SELECT id
                 FROM tareas_operativas
                 WHERE hotel_id = ?
                   AND mantenimiento_id = ?
                   AND estado IN ('pendiente', 'asignada', 'en_proceso')
                 ORDER BY id ASC
                 LIMIT 1
                 FOR UPDATE",
                [$hotelId, $mantenimientoId]
            );
            $duplicadoRow = $duplicado ? $duplicado->fetch() : null;
            if ($duplicadoRow) {
                throw new RuntimeException('Ya existe una tarea activa vinculada a este mantenimiento.');
            }

            $titulo = $this->limitarTexto(
                $datos['titulo'] ?? '',
                160,
                'Seguimiento mantenimiento hab. ' . (string)($mantenimiento['habitacion_numero'] ?? $mantenimiento['habitacion_id'])
            );
            $descripcion = $this->limitarTexto(
                $datos['descripcion'] ?? '',
                2000,
                $this->descripcionMantenimientoParaTarea($mantenimiento)
            );
            $prioridad = (string)($mantenimiento['prioridad'] ?? 'media');
            if (!in_array($prioridad, self::PRIORIDADES, true)) {
                $prioridad = 'media';
            }

            $fechaProgramada = $mantenimiento['fecha_programada'] ?? ($mantenimiento['fecha_inicio'] ?? null);
            $fechaLimite = $mantenimiento['fecha_programada_fin'] ?? null;

            $stmt = $this->db->query(
                "INSERT INTO tareas_operativas
                    (hotel_id, categoria, titulo, descripcion, prioridad, estado,
                     habitacion_id, mantenimiento_id, fecha_programada, fecha_limite,
                     creada_por_usuario_id, origen, created_at)
                 VALUES
                    (?, 'mantenimiento', ?, ?, ?, 'pendiente',
                     ?, ?, ?, ?,
                     ?, 'mantenimiento', NOW())",
                [
                    $hotelId,
                    $titulo,
                    $descripcion,
                    $prioridad,
                    (int)$mantenimiento['habitacion_id'],
                    $mantenimientoId,
                    $fechaProgramada,
                    $fechaLimite,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new RuntimeException('No se pudo insertar la tarea vinculada al mantenimiento.');
            }

            $tareaId = (int)$this->db->lastInsertId();
            $this->registrarEvento(
                $hotelId,
                $tareaId,
                'creada',
                null,
                'pendiente',
                'Tarea creada manualmente desde mantenimiento #' . (string)$mantenimientoId . '.',
                $usuarioId
            );

            $this->db->safeCommit();
            return $tareaId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function crearDesdeLimpiezaHabitacionParaHotel(int $hotelId, int $habitacionId, array $datos = [], ?int $usuarioId = null): int
    {
        if ($hotelId <= 0) {
            throw new InvalidArgumentException('Hotel no valido para crear la tarea.');
        }

        if ($habitacionId <= 0) {
            throw new InvalidArgumentException('Habitacion no valida para crear la tarea de limpieza.');
        }

        if (!$this->tablaDisponible() || !$this->eventosDisponibles()) {
            throw new RuntimeException('La base de tareas operativas no esta disponible.');
        }

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "SELECT id,
                        hotel_id,
                        numero,
                        tipo,
                        piso,
                        estado
                 FROM habitaciones
                 WHERE id = ?
                   AND hotel_id = ?
                   AND COALESCE(activa, 1) = 1
                 LIMIT 1
                 FOR UPDATE",
                [$habitacionId, $hotelId]
            );
            $habitacion = $stmt ? $stmt->fetch() : null;
            if (!$habitacion) {
                throw new RuntimeException('Habitacion no encontrada para el hotel actual.');
            }

            if ((string)($habitacion['estado'] ?? '') !== 'limpieza') {
                throw new RuntimeException('Solo se pueden crear tareas de limpieza desde habitaciones en estado limpieza.');
            }

            $duplicado = $this->db->query(
                "SELECT id
                 FROM tareas_operativas
                 WHERE hotel_id = ?
                   AND habitacion_id = ?
                   AND categoria = 'limpieza'
                   AND estado IN ('pendiente', 'asignada', 'en_proceso')
                 ORDER BY id ASC
                 LIMIT 1
                 FOR UPDATE",
                [$hotelId, $habitacionId]
            );
            $duplicadoRow = $duplicado ? $duplicado->fetch() : null;
            if ($duplicadoRow) {
                throw new RuntimeException('Ya existe una tarea activa de limpieza para esta habitacion.');
            }

            $titulo = $this->limitarTexto(
                $datos['titulo'] ?? '',
                160,
                'Limpieza habitacion ' . (string)($habitacion['numero'] ?? $habitacionId)
            );
            $descripcion = $this->limitarTexto(
                $datos['descripcion'] ?? '',
                2000,
                $this->descripcionLimpiezaParaTarea($habitacion)
            );

            $stmt = $this->db->query(
                "INSERT INTO tareas_operativas
                    (hotel_id, categoria, titulo, descripcion, prioridad, estado,
                     habitacion_id, fecha_programada, creada_por_usuario_id,
                     origen, created_at)
                 VALUES
                    (?, 'limpieza', ?, ?, 'media', 'pendiente',
                     ?, NOW(), ?,
                     'habitacion', NOW())",
                [
                    $hotelId,
                    $titulo,
                    $descripcion,
                    $habitacionId,
                    $usuarioId,
                ]
            );

            if (!$stmt) {
                throw new RuntimeException('No se pudo insertar la tarea de limpieza.');
            }

            $tareaId = (int)$this->db->lastInsertId();
            $this->registrarEvento(
                $hotelId,
                $tareaId,
                'creada',
                null,
                'pendiente',
                'Tarea creada manualmente desde limpieza de habitacion #' . (string)$habitacionId . '.',
                $usuarioId
            );

            $this->db->safeCommit();
            return $tareaId;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function asignarTrabajadorParaHotel(int $id, int $hotelId, int $trabajadorId, ?int $usuarioId = null): bool
    {
        if ($id <= 0 || $hotelId <= 0) {
            throw new InvalidArgumentException('Tarea no valida para asignacion.');
        }

        if ($trabajadorId <= 0) {
            throw new InvalidArgumentException('Seleccione un trabajador activo.');
        }

        $tarea = $this->buscarPorIdHotel($id, $hotelId);
        if (!$tarea) {
            throw new RuntimeException('Tarea no encontrada para el hotel actual.');
        }

        $estadoAnterior = (string)($tarea['estado'] ?? 'pendiente');
        if (!in_array($estadoAnterior, ['pendiente', 'asignada'], true)) {
            throw new RuntimeException('Solo se pueden asignar tareas pendientes o ya asignadas.');
        }

        $trabajador = $this->trabajadorActivoEnHotel($trabajadorId, $hotelId);
        if (!$trabajador) {
            throw new RuntimeException('El trabajador seleccionado no esta activo en el hotel actual.');
        }

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "UPDATE tareas_operativas
                 SET trabajador_id = ?,
                     asignada_por_usuario_id = ?,
                     estado = 'asignada',
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado IN ('pendiente', 'asignada')",
                [$trabajadorId, $usuarioId, $id, $hotelId]
            );

            if (!$stmt || $stmt->rowCount() !== 1) {
                throw new RuntimeException('No se pudo asignar la tarea.');
            }

            $this->registrarEvento(
                $hotelId,
                $id,
                'asignada',
                $estadoAnterior,
                'asignada',
                'Tarea asignada a ' . (string)($trabajador['nombre_completo'] ?? ('trabajador #' . $trabajadorId)) . '.',
                $usuarioId
            );

            $this->db->safeCommit();
            return true;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    public function cambiarEstadoManualParaHotel(
        int $id,
        int $hotelId,
        string $accion,
        ?int $usuarioId = null,
        ?string $comentario = null
    ): bool {
        if ($id <= 0 || $hotelId <= 0) {
            throw new InvalidArgumentException('Tarea no valida para cambio de estado.');
        }

        $tarea = $this->buscarPorIdHotel($id, $hotelId);
        if (!$tarea) {
            throw new RuntimeException('Tarea no encontrada para el hotel actual.');
        }

        $estadoAnterior = (string)($tarea['estado'] ?? 'pendiente');
        $accion = strtolower(trim($accion));

        $transicion = $this->transicionManual($accion, $estadoAnterior);
        $comentario = $this->nullableTexto($comentario, 800);

        $this->db->safeBeginTransaction();

        try {
            $stmt = $this->db->query(
                "UPDATE tareas_operativas
                 SET estado = ?,
                     fecha_inicio = CASE WHEN ? = 'en_proceso' THEN COALESCE(fecha_inicio, NOW()) ELSE fecha_inicio END,
                     fecha_cierre = CASE WHEN ? IN ('completada', 'cancelada') THEN NOW() ELSE fecha_cierre END,
                     cerrada_por_usuario_id = CASE WHEN ? IN ('completada', 'cancelada') THEN ? ELSE cerrada_por_usuario_id END,
                     cancelada_por_usuario_id = CASE WHEN ? = 'cancelada' THEN ? ELSE cancelada_por_usuario_id END,
                     notas_cierre = CASE WHEN ? IN ('completada', 'cancelada') THEN ? ELSE notas_cierre END,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado = ?",
                [
                    $transicion['estado_nuevo'],
                    $transicion['estado_nuevo'],
                    $transicion['estado_nuevo'],
                    $transicion['estado_nuevo'],
                    $usuarioId,
                    $transicion['estado_nuevo'],
                    $usuarioId,
                    $transicion['estado_nuevo'],
                    $comentario,
                    $id,
                    $hotelId,
                    $estadoAnterior,
                ]
            );

            if (!$stmt || $stmt->rowCount() !== 1) {
                throw new RuntimeException('No se pudo actualizar el estado de la tarea.');
            }

            $this->registrarEvento(
                $hotelId,
                $id,
                $transicion['evento'],
                $estadoAnterior,
                $transicion['estado_nuevo'],
                $comentario ?: $transicion['comentario'],
                $usuarioId
            );

            $this->db->safeCommit();
            return true;
        } catch (Throwable $e) {
            $this->db->safeRollBack();
            throw $e;
        }
    }

    private function normalizarDatos(array $datos, int $hotelId): array
    {
        $titulo = trim((string)($datos['titulo'] ?? ''));
        if ($titulo === '') {
            throw new InvalidArgumentException('El titulo de la tarea es obligatorio.');
        }

        if (function_exists('mb_substr')) {
            $titulo = mb_substr($titulo, 0, 160, 'UTF-8');
        } else {
            $titulo = substr($titulo, 0, 160);
        }

        $categoria = trim((string)($datos['categoria'] ?? 'general'));
        if (!in_array($categoria, self::CATEGORIAS, true)) {
            $categoria = 'general';
        }

        $prioridad = trim((string)($datos['prioridad'] ?? 'media'));
        if (!in_array($prioridad, self::PRIORIDADES, true)) {
            $prioridad = 'media';
        }

        $habitacionId = $this->normalizarEnteroNullable($datos['habitacion_id'] ?? null);
        if ($habitacionId !== null) {
            $this->validarHabitacionHotel($habitacionId, $hotelId);
        }

        $fechaProgramada = $this->normalizarFechaHora($datos['fecha_programada'] ?? null);
        $fechaLimite = $this->normalizarFechaHora($datos['fecha_limite'] ?? null);
        if ($fechaProgramada !== null && $fechaLimite !== null && strtotime($fechaLimite) < strtotime($fechaProgramada)) {
            throw new InvalidArgumentException('La fecha limite no puede ser anterior a la fecha programada.');
        }

        $descripcion = trim((string)($datos['descripcion'] ?? ''));

        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'categoria' => $categoria,
            'prioridad' => $prioridad,
            'habitacion_id' => $habitacionId,
            'fecha_programada' => $fechaProgramada,
            'fecha_limite' => $fechaLimite,
        ];
    }

    private function limitarTexto($value, int $limite, string $fallback): string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            $texto = $fallback;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
    }

    private function descripcionMantenimientoParaTarea(array $mantenimiento): string
    {
        $lineas = [
            'Tarea vinculada al mantenimiento #' . (int)($mantenimiento['id'] ?? 0) . '.',
            'Habitacion: ' . (string)($mantenimiento['habitacion_numero'] ?? $mantenimiento['habitacion_id'] ?? '-'),
            'Tipo: ' . (string)($mantenimiento['tipo_mantenimiento'] ?? '-'),
            'Estado mantenimiento: ' . (string)($mantenimiento['estado'] ?? '-'),
        ];

        $motivo = trim((string)($mantenimiento['motivo'] ?? ''));
        if ($motivo !== '') {
            $lineas[] = 'Motivo: ' . $motivo;
        }

        $descripcion = trim((string)($mantenimiento['descripcion'] ?? ''));
        if ($descripcion !== '') {
            $lineas[] = 'Descripcion: ' . $descripcion;
        }

        return implode("\n", $lineas);
    }

    private function descripcionLimpiezaParaTarea(array $habitacion): string
    {
        return implode("\n", [
            'Tarea manual de limpieza vinculada a habitacion.',
            'Habitacion: ' . (string)($habitacion['numero'] ?? $habitacion['id'] ?? '-'),
            'Tipo: ' . (string)($habitacion['tipo'] ?? '-'),
            'Piso: ' . (string)($habitacion['piso'] ?? '-'),
            'Estado habitacion al crear tarea: ' . (string)($habitacion['estado'] ?? '-'),
        ]);
    }

    private function validarHabitacionHotel(int $habitacionId, int $hotelId): void
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS total
             FROM habitaciones
             WHERE id = ?
               AND hotel_id = ?
               AND COALESCE(activa, 1) = 1",
            [$habitacionId, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;

        if ((int)($row['total'] ?? 0) !== 1) {
            throw new InvalidArgumentException('La habitacion seleccionada no pertenece al hotel actual.');
        }
    }

    private function trabajadorActivoEnHotel(int $trabajadorId, int $hotelId): ?array
    {
        if (!$this->tablaExiste('trabajadores')) {
            return null;
        }

        $stmt = $this->db->query(
            "SELECT id, nombre_completo
             FROM trabajadores
             WHERE id = ?
               AND hotel_id = ?
               AND estado = 'activo'
             LIMIT 1",
            [$trabajadorId, $hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;

        return $row ?: null;
    }

    private function transicionManual(string $accion, string $estadoActual): array
    {
        $activos = ['pendiente', 'asignada', 'en_proceso'];

        if ($accion === 'iniciar') {
            if (!in_array($estadoActual, ['pendiente', 'asignada'], true)) {
                throw new RuntimeException('Solo se pueden iniciar tareas pendientes o asignadas.');
            }

            return [
                'estado_nuevo' => 'en_proceso',
                'evento' => 'iniciada',
                'comentario' => 'Tarea iniciada manualmente.',
            ];
        }

        if ($accion === 'completar') {
            if (!in_array($estadoActual, $activos, true)) {
                throw new RuntimeException('Solo se pueden completar tareas activas.');
            }

            return [
                'estado_nuevo' => 'completada',
                'evento' => 'completada',
                'comentario' => 'Tarea completada manualmente.',
            ];
        }

        if ($accion === 'cancelar') {
            if (!in_array($estadoActual, $activos, true)) {
                throw new RuntimeException('Solo se pueden cancelar tareas activas.');
            }

            return [
                'estado_nuevo' => 'cancelada',
                'evento' => 'cancelada',
                'comentario' => 'Tarea cancelada manualmente.',
            ];
        }

        throw new InvalidArgumentException('Accion de tarea no permitida.');
    }

    private function registrarEvento(
        int $hotelId,
        int $tareaId,
        string $tipoEvento,
        ?string $estadoAnterior,
        ?string $estadoNuevo,
        ?string $comentario,
        ?int $usuarioId
    ): void {
        $stmt = $this->db->query(
            "INSERT INTO tarea_eventos
                (hotel_id, tarea_id, tipo_evento, estado_anterior, estado_nuevo, comentario, usuario_id, created_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$hotelId, $tareaId, $tipoEvento, $estadoAnterior, $estadoNuevo, $comentario, $usuarioId]
        );

        if (!$stmt) {
            throw new RuntimeException('No se pudo registrar el evento inicial de la tarea.');
        }
    }

    private function normalizarEnteroNullable($value): ?int
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $id = (int)$value;
        return $id > 0 ? $id : null;
    }

    private function normalizarFechaHora($value): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        $timestamp = strtotime($value);
        if (!$timestamp) {
            throw new InvalidArgumentException('Formato de fecha no valido.');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizarFechaSimple($value, string $fallback): string
    {
        $value = trim((string)($value ?? ''));
        $timestamp = $value !== '' ? strtotime($value) : false;

        if (!$timestamp) {
            $timestamp = strtotime($fallback);
        }

        return date('Y-m-d', $timestamp ?: time());
    }

    private function nullableTexto($value, int $limite): ?string
    {
        $texto = trim((string)($value ?? ''));
        if ($texto === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, $limite, 'UTF-8');
        }

        return substr($texto, 0, $limite);
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
}
