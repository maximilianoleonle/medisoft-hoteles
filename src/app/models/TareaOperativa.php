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
