<?php
/**
 * Modelo de Mantenimiento
 * Los Cedros
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class Mantenimiento extends Model {
    protected $table = 'mantenimientos_habitaciones';
    protected $fillable = [
        'hotel_id',
        'habitacion_id',
        'tipo_mantenimiento',
        'prioridad',
        'motivo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'realizado_por',
        'costo',
        'observaciones',
        'usuario_registro_id',
        'fecha_programada',
        'fecha_programada_fin',
        'programado'
    ];

    protected function hotelIdActual()
    {
        return obtenerHotelIdActualCompat();
    }

    public function find($id, $columns = ['*']) {
        $columns = implode(', ', $columns);
        $sql = "SELECT {$columns} FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $this->hotelIdActual()]);
        return $stmt ? $stmt->fetch() : false;
    }

    public function where($conditions, $columns = ['*']) {
        $conditions['hotel_id'] = $this->hotelIdActual();
        return parent::where($conditions, $columns);
    }

    public function create($data) {
        $data['hotel_id'] = $data['hotel_id'] ?? $this->hotelIdActual();
        return parent::create($data);
    }

    public function update($id, $data) {
        $data = $this->filterFillable($data);
        unset($data['hotel_id']);

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $values[] = $this->hotelIdActual();

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) .
               " WHERE {$this->primaryKey} = ? AND hotel_id = ?";

        $stmt = $this->db->query($sql, $values);
        return $stmt ? $this->find($id) : false;
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $this->hotelIdActual()]);
        return $stmt !== false;
    }
    
    /**
     * Tipos de mantenimiento disponibles
     */
    public static function getTipos() {
        return [
            'preventivo' => 'Preventivo',
            'correctivo' => 'Correctivo',
            'emergencia' => 'Emergencia',
            'limpieza_profunda' => 'Limpieza Profunda'
        ];
    }
    
    /**
     * Prioridades disponibles
     */
    public static function getPrioridades() {
        return [
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            'urgente' => 'Urgente'
        ];
    }
    
    /**
     * Estados posibles del mantenimiento
     */
    public static function getEstados() {
        return [
            'programado' => 'Programado',
            'en_proceso' => 'En Proceso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado'
        ];
    }
    
    /**
     * Obtener mantenimientos activos
     */
    public function activos() {
        return $this->where(['estado' => 'en_proceso']);
    }
    
    /**
     * Obtener mantenimientos por habitación
     */
    public function porHabitacion($habitacion_id) {
        return $this->where(['habitacion_id' => $habitacion_id]);
    }
    
    /**
     * Obtener mantenimiento actual de una habitación
     */
    public function mantenimientoActual($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'
                ORDER BY fecha_inicio DESC LIMIT 1";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetch();
    }
    
    /**
     * Iniciar mantenimiento
     */
    public function iniciar($habitacion_id, $data) {
        // Verificar si ya hay un mantenimiento en proceso
        $actual = $this->mantenimientoActual($habitacion_id);
        if ($actual) {
            return false;
        }
        
        $data['habitacion_id'] = $habitacion_id;
        $data['hotel_id'] = $this->hotelIdActual();
        $data['estado'] = 'en_proceso';
        $data['fecha_inicio'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }

    /**
     * Verificar si ya existe mantenimiento programado que se solape con el rango.
     */
    public function tieneProgramadoSolapado($habitacion_id, $fecha_inicio, $fecha_fin = null, $excluir_id = null) {
        $fecha_fin_check = $fecha_fin ?: $fecha_inicio;
        $params = [
            $habitacion_id,
            $this->hotelIdActual(),
            $fecha_fin_check,
            $fecha_inicio
        ];

        $whereExclusion = '';
        if ($excluir_id !== null) {
            $whereExclusion = " AND {$this->primaryKey} <> ?";
            $params[] = $excluir_id;
        }

        $sql = "SELECT COUNT(*) as total
                FROM {$this->table}
                WHERE habitacion_id = ?
                AND hotel_id = ?
                AND programado = 1
                AND estado = 'programado'
                AND fecha_programada IS NOT NULL
                AND fecha_programada <= ?
                AND COALESCE(fecha_programada_fin, fecha_programada) >= ?
                {$whereExclusion}";

        $stmt = $this->db->query($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return ((int)($result['total'] ?? 0)) > 0;
    }

    /**
     * Preview read-only de mantenimientos programados vencidos o proximos.
     */
    public function previewProgramados($dias = 30) {
        $dias = max(0, min(90, (int)$dias));
        $hotelId = $this->hotelIdActual();
        $hoy = date('Y-m-d');
        $hasta = date('Y-m-d', strtotime('+' . $dias . ' days'));

        $sql = "SELECT
                    m.id,
                    m.habitacion_id,
                    m.tipo_mantenimiento,
                    m.prioridad,
                    m.motivo,
                    m.descripcion,
                    m.fecha_programada,
                    m.fecha_programada_fin,
                    m.created_at,
                    h.numero AS habitacion_numero,
                    h.tipo AS habitacion_tipo,
                    h.piso AS habitacion_piso,
                    h.estado AS habitacion_estado,
                    (
                        SELECT COUNT(*)
                        FROM reservaciones r
                        INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id
                        INNER JOIN habitaciones hr ON hr.id = rh.habitacion_id
                        WHERE rh.habitacion_id = m.habitacion_id
                          AND hr.hotel_id = m.hotel_id
                          AND r.estado IN ('confirmada', 'checked_in')
                          AND r.fecha_entrada < DATE_ADD(COALESCE(m.fecha_programada_fin, m.fecha_programada), INTERVAL 1 DAY)
                          AND r.fecha_salida > m.fecha_programada
                    ) AS reservaciones_conflicto
                FROM {$this->table} m
                INNER JOIN habitaciones h
                    ON h.id = m.habitacion_id
                   AND h.hotel_id = m.hotel_id
                WHERE m.hotel_id = ?
                  AND m.programado = 1
                  AND m.estado = 'programado'
                  AND m.fecha_programada IS NOT NULL
                  AND m.fecha_programada <= ?
                ORDER BY
                    m.fecha_programada ASC,
                    FIELD(m.prioridad, 'urgente', 'alta', 'media', 'baja'),
                    m.id ASC";

        $stmt = $this->db->query($sql, [$hotelId, $hasta]);
        $rows = $stmt ? $stmt->fetchAll() : [];
        $resumen = [
            'total' => 0,
            'vencidos' => 0,
            'hoy' => 0,
            'proximos' => 0,
            'con_conflictos' => 0,
            'habitacion_no_disponible' => 0,
            'candidatos' => 0,
        ];

        foreach ($rows as &$row) {
            $fecha = (string)($row['fecha_programada'] ?? '');
            $conflictos = (int)($row['reservaciones_conflicto'] ?? 0);
            $estadoHabitacion = (string)($row['habitacion_estado'] ?? '');
            $advertencias = [];

            if ($fecha < $hoy) {
                $row['categoria_preview'] = 'vencido';
                $advertencias[] = 'Fecha programada vencida';
                $resumen['vencidos']++;
            } elseif ($fecha === $hoy) {
                $row['categoria_preview'] = 'hoy';
                $advertencias[] = 'Programado para hoy';
                $resumen['hoy']++;
            } else {
                $row['categoria_preview'] = 'proximo';
                $advertencias[] = 'Programado proximamente';
                $resumen['proximos']++;
            }

            if ($estadoHabitacion !== 'disponible') {
                $advertencias[] = 'Habitacion en estado ' . ($estadoHabitacion ?: 'desconocido');
                $resumen['habitacion_no_disponible']++;
            }

            if ($conflictos > 0) {
                $advertencias[] = 'Reservaciones conflictivas: ' . $conflictos;
                $resumen['con_conflictos']++;
            }

            $row['preview_candidato'] = ($fecha <= $hoy && $estadoHabitacion === 'disponible' && $conflictos === 0);
            if ($row['preview_candidato']) {
                $advertencias[] = 'Candidato revisable; no se activa automaticamente';
                $resumen['candidatos']++;
            } else {
                $advertencias[] = 'Solo lectura; requiere revision manual';
            }

            $row['preview_advertencias'] = $advertencias;
            $resumen['total']++;
        }
        unset($row);

        return [
            'dias' => $dias,
            'desde' => $hoy,
            'hasta' => $hasta,
            'resumen' => $resumen,
            'registros' => $rows,
        ];
    }
    
    /**
     * Finalizar mantenimiento
     */
    public function finalizar($habitacion_id, $observaciones = null, $costo = null) {
        $actual = $this->mantenimientoActual($habitacion_id);
        if (!$actual) {
            return false;
        }
        
        $data = [
            'estado' => 'completado',
            'fecha_fin' => date('Y-m-d H:i:s')
        ];
        
        if ($observaciones !== null) {
            $data['observaciones'] = $observaciones;
        }
        
        if ($costo !== null) {
            $data['costo'] = $costo;
        }
        
        return $this->update($actual['id'], $data);
    }
    
    /**
     * Cancelar mantenimiento
     */
    public function cancelar($habitacion_id, $motivo = null) {
        $actual = $this->mantenimientoActual($habitacion_id);
        if (!$actual) {
            return false;
        }
        
        $data = [
            'estado' => 'cancelado',
            'fecha_fin' => date('Y-m-d H:i:s')
        ];
        
        if ($motivo !== null) {
            $data['observaciones'] = 'Cancelado: ' . $motivo;
        }
        
        return $this->update($actual['id'], $data);
    }
    
    /**
     * Validar datos de mantenimiento
     */
    public function validar($data) {
        $errores = [];
        
        // Validar tipo
        $tipos = array_keys(self::getTipos());
        if (empty($data['tipo_mantenimiento']) || !in_array($data['tipo_mantenimiento'], $tipos)) {
            $errores[] = 'Debe seleccionar un tipo de mantenimiento válido';
        }
        
        // Validar prioridad
        $prioridades = array_keys(self::getPrioridades());
        if (empty($data['prioridad']) || !in_array($data['prioridad'], $prioridades)) {
            $errores[] = 'Debe seleccionar una prioridad válida';
        }
        
        // Validar motivo
        if (empty($data['motivo'])) {
            $errores[] = 'El motivo es obligatorio';
        }
        
        // Validar costo si se proporciona
        if (isset($data['costo']) && $data['costo'] !== null && $data['costo'] !== '') {
            if (!is_numeric($data['costo']) || $data['costo'] < 0) {
                $errores[] = 'El costo debe ser un número positivo';
            }
        }
        
        return $errores;
    }
    
    /**
     * Obtener estadísticas de mantenimientos
     */
    public function estadisticas($fecha_inicio = null, $fecha_fin = null) {
        $where = "WHERE hotel_id = ?";
        $params = [$this->hotelIdActual()];
        
        if ($fecha_inicio && $fecha_fin) {
            $where .= " AND fecha_inicio BETWEEN ? AND ?";
            $params[] = $fecha_inicio;
            $params[] = $fecha_fin;
        }
        
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(CASE WHEN tipo_mantenimiento = 'preventivo' THEN 1 ELSE 0 END) as preventivos,
                SUM(CASE WHEN tipo_mantenimiento = 'correctivo' THEN 1 ELSE 0 END) as correctivos,
                SUM(CASE WHEN tipo_mantenimiento = 'emergencia' THEN 1 ELSE 0 END) as emergencias,
                SUM(CASE WHEN tipo_mantenimiento = 'limpieza_profunda' THEN 1 ELSE 0 END) as limpiezas,
                SUM(COALESCE(costo, 0)) as costo_total,
                AVG(CASE WHEN costo IS NOT NULL THEN costo END) as costo_promedio
                FROM {$this->table}
                $where";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Obtener historial de mantenimientos de una habitación
     */
    public function historial($habitacion_id, $limite = 10) {
        $sql = "SELECT m.*, u.nombre as usuario_nombre
                FROM {$this->table} m
                LEFT JOIN usuarios u ON m.usuario_registro_id = u.id
                WHERE m.habitacion_id = ? AND m.hotel_id = ?
                ORDER BY m.fecha_inicio DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual(), $limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar mantenimientos
     */
    public function buscar($filtros = []) {
        $hotelId = $this->hotelIdActual();
        $conditions = ["m.hotel_id = ?", "h.hotel_id = ?"];
        $params = [$hotelId, $hotelId];
        
        if (!empty($filtros['habitacion_id'])) {
            $conditions[] = "m.habitacion_id = ?";
            $params[] = $filtros['habitacion_id'];
        }
        
        if (!empty($filtros['tipo_mantenimiento'])) {
            $conditions[] = "m.tipo_mantenimiento = ?";
            $params[] = $filtros['tipo_mantenimiento'];
        }
        
        if (!empty($filtros['prioridad'])) {
            $conditions[] = "m.prioridad = ?";
            $params[] = $filtros['prioridad'];
        }
        
        if (!empty($filtros['estado'])) {
            $conditions[] = "m.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
            $conditions[] = "m.fecha_inicio BETWEEN ? AND ?";
            $params[] = $filtros['fecha_inicio'];
            $params[] = $filtros['fecha_fin'];
        }
        
        $where = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "SELECT m.*, h.numero as habitacion_numero, u.nombre as usuario_nombre
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                LEFT JOIN usuarios u ON m.usuario_registro_id = u.id
                $where
                ORDER BY m.fecha_inicio DESC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Programar un mantenimiento futuro
     */
    public function programar($habitacion_id, $data) {
        $data['habitacion_id'] = $habitacion_id;
        $data['hotel_id'] = $this->hotelIdActual();
        $data['estado'] = 'programado';
        $data['programado'] = 1;
        $data['fecha_inicio'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Obtener mantenimientos programados de una habitación
     */
    public function programadosPorHabitacion($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? 
                AND hotel_id = ?
                AND programado = 1 
                AND estado = 'programado'
                ORDER BY fecha_programada ASC";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener todos los mantenimientos programados (activos)
     */
    public function todosProgramados() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT m.*, h.numero as habitacion_numero, h.tipo as habitacion_tipo,
                       h.piso as habitacion_piso
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.programado = 1 
                AND m.hotel_id = ?
                AND h.hotel_id = ?
                AND m.estado = 'programado'
                ORDER BY m.fecha_programada ASC";
        
        $stmt = $this->db->query($sql, [$hotelId, $hotelId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si una habitación tiene mantenimiento programado que conflicte con fechas
     * Retorna true si HAY conflicto
     */
    public function tieneConflictoConFechas($habitacion_id, $fecha_entrada, $fecha_salida) {
        $sql = "SELECT COUNT(*) as conflictos FROM {$this->table} 
                WHERE habitacion_id = ? 
                AND hotel_id = ?
                AND estado IN ('programado', 'en_proceso')
                AND (
                    -- Mantenimiento programado que se solapa con las fechas
                    (programado = 1 AND fecha_programada IS NOT NULL AND (
                        (fecha_programada <= ? AND (fecha_programada_fin IS NULL OR fecha_programada_fin >= ?))
                        OR (fecha_programada >= ? AND fecha_programada <= ?)
                        OR (fecha_programada_fin IS NOT NULL AND fecha_programada_fin >= ? AND fecha_programada <= ?)
                    ))
                    -- Mantenimiento en proceso actual
                    OR (estado = 'en_proceso' AND programado = 0)
                )";
        
        $params = [
            $habitacion_id,
            $this->hotelIdActual(),
            $fecha_salida, $fecha_entrada,  // caso 1: mantenimiento empieza antes y termina después
            $fecha_entrada, $fecha_salida,  // caso 2: mantenimiento empieza dentro del rango
            $fecha_entrada, $fecha_salida   // caso 3: mantenimiento termina dentro del rango
        ];
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['conflictos'] > 0;
    }
    
    /**
     * Obtener habitaciones con mantenimiento programado que conflicte con fechas
     * Retorna array de habitacion_ids con conflicto
     */
    public function habitacionesConConflicto($fecha_entrada, $fecha_salida) {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT DISTINCT m.habitacion_id, m.fecha_programada, m.fecha_programada_fin,
                       m.tipo_mantenimiento, m.motivo, h.numero as habitacion_numero
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.estado IN ('programado', 'en_proceso')
                AND m.hotel_id = ?
                AND h.hotel_id = ?
                AND (
                    (m.programado = 1 AND m.fecha_programada IS NOT NULL AND (
                        (m.fecha_programada < ? AND (m.fecha_programada_fin IS NULL OR m.fecha_programada_fin > ?))
                        OR (m.fecha_programada >= ? AND m.fecha_programada < ?)
                        OR (m.fecha_programada_fin IS NOT NULL AND m.fecha_programada_fin > ? AND m.fecha_programada < ?)
                    ))
                    OR (m.estado = 'en_proceso' AND m.programado = 0)
                )";
        
        $params = [
            $hotelId, $hotelId,
            $fecha_salida, $fecha_entrada,
            $fecha_entrada, $fecha_salida,
            $fecha_entrada, $fecha_salida
        ];
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Iniciar un mantenimiento programado (cambiar estado a en_proceso)
     */
    public function iniciarProgramado($mantenimiento_id) {
        $mantenimiento = $this->find($mantenimiento_id);
        if (!$mantenimiento || $mantenimiento['estado'] !== 'programado') {
            return false;
        }
        
        return $this->update($mantenimiento_id, [
            'estado' => 'en_proceso',
            'fecha_inicio' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Activar manualmente un mantenimiento programado vencido o de hoy.
     */
    public function activarProgramadoManual($mantenimiento_id, $usuario_id = null) {
        $mantenimiento_id = (int)$mantenimiento_id;
        $usuario_id = $usuario_id ? (int)$usuario_id : null;
        $hotelId = $this->hotelIdActual();
        $hoy = date('Y-m-d');
        $pdo = $this->db->getConnection();

        if ($mantenimiento_id <= 0) {
            return [
                'success' => false,
                'message' => 'Mantenimiento no valido',
            ];
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT
                    m.*,
                    h.numero AS habitacion_numero,
                    h.estado AS habitacion_estado
                 FROM {$this->table} m
                 INNER JOIN habitaciones h
                    ON h.id = m.habitacion_id
                   AND h.hotel_id = m.hotel_id
                 WHERE m.id = ?
                   AND m.hotel_id = ?
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([$mantenimiento_id, $hotelId]);
            $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$mantenimiento) {
                throw new RuntimeException('Mantenimiento no encontrado para el hotel actual');
            }

            if ((int)($mantenimiento['programado'] ?? 0) !== 1 || (string)($mantenimiento['estado'] ?? '') !== 'programado') {
                throw new RuntimeException('Solo se puede activar un mantenimiento programado pendiente');
            }

            $fechaProgramada = substr((string)($mantenimiento['fecha_programada'] ?? ''), 0, 10);
            if ($fechaProgramada === '') {
                throw new RuntimeException('El mantenimiento no tiene fecha programada');
            }

            if ($fechaProgramada > $hoy) {
                throw new RuntimeException('Solo se puede activar mantenimiento vencido o programado para hoy');
            }

            if ((string)($mantenimiento['habitacion_estado'] ?? '') !== 'disponible') {
                throw new RuntimeException('La habitacion no esta disponible para iniciar mantenimiento');
            }

            $stmtActivo = $pdo->prepare(
                "SELECT id
                 FROM {$this->table}
                 WHERE habitacion_id = ?
                   AND hotel_id = ?
                   AND estado = 'en_proceso'
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtActivo->execute([(int)$mantenimiento['habitacion_id'], $hotelId]);
            if ($stmtActivo->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('La habitacion ya tiene un mantenimiento en proceso');
            }

            $fechaFin = substr((string)($mantenimiento['fecha_programada_fin'] ?: $fechaProgramada), 0, 10);
            $fechaFinExclusiva = date('Y-m-d', strtotime($fechaFin . ' +1 day'));
            $stmtReservas = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh
                    ON rh.reservacion_id = r.id
                   AND rh.hotel_id = r.hotel_id
                 INNER JOIN habitaciones h
                    ON h.id = rh.habitacion_id
                   AND h.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ?
                   AND rh.habitacion_id = ?
                   AND r.estado IN ('confirmada', 'checked_in')
                   AND r.fecha_entrada < ?
                   AND r.fecha_salida > ?"
            );
            $stmtReservas->execute([
                $hotelId,
                (int)$mantenimiento['habitacion_id'],
                $fechaFinExclusiva,
                $fechaProgramada,
            ]);

            if ((int)$stmtReservas->fetchColumn() > 0) {
                throw new RuntimeException('Hay reservaciones conflictivas para la fecha programada');
            }

            $stmtUpdateMantenimiento = $pdo->prepare(
                "UPDATE {$this->table}
                 SET estado = 'en_proceso',
                     fecha_inicio = NOW(),
                     realizado_por = ?,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado = 'programado'
                   AND programado = 1"
            );
            $stmtUpdateMantenimiento->execute([
                $usuario_id,
                $mantenimiento_id,
                $hotelId,
            ]);

            if ($stmtUpdateMantenimiento->rowCount() !== 1) {
                throw new RuntimeException('No se pudo actualizar el mantenimiento programado');
            }

            $stmtUpdateHabitacion = $pdo->prepare(
                "UPDATE habitaciones
                 SET estado = 'mantenimiento',
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado = 'disponible'"
            );
            $stmtUpdateHabitacion->execute([
                (int)$mantenimiento['habitacion_id'],
                $hotelId,
            ]);

            if ($stmtUpdateHabitacion->rowCount() !== 1) {
                throw new RuntimeException('No se pudo marcar la habitacion en mantenimiento');
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Mantenimiento programado activado correctamente',
                'mantenimiento' => $mantenimiento,
                'habitacion_id' => (int)$mantenimiento['habitacion_id'],
                'habitacion_numero' => $mantenimiento['habitacion_numero'] ?? null,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'mantenimiento_id' => $mantenimiento_id,
            ];
        }
    }
    
    /**
     * Cancelar un mantenimiento programado
     */
    public function cancelarProgramado($mantenimiento_id, $motivo = null) {
        $mantenimiento = $this->find($mantenimiento_id);
        if (!$mantenimiento || $mantenimiento['estado'] !== 'programado') {
            return false;
        }
        
        $data = [
            'estado' => 'cancelado',
            'fecha_fin' => date('Y-m-d H:i:s')
        ];
        
        if ($motivo) {
            $data['observaciones'] = 'Cancelado: ' . $motivo;
        }
        
        return $this->update($mantenimiento_id, $data);
    }
    
    /**
     * Verificar y activar mantenimientos programados que ya deben iniciar
     * (para ejecutar con cron o al cargar el dashboard)
     */
    public function activarMantenimientosPendientes() {
        $hoy = date('Y-m-d');
        $hotelId = $this->hotelIdActual();
        
        $sql = "SELECT m.*, h.numero as habitacion_numero 
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.programado = 1 
                AND m.hotel_id = ?
                AND h.hotel_id = ?
                AND m.estado = 'programado' 
                AND m.fecha_programada <= ?";
        
        $stmt = $this->db->query($sql, [$hotelId, $hotelId, $hoy]);
        $pendientes = $stmt->fetchAll();
        
        $activados = 0;
        foreach ($pendientes as $mant) {
            // Cambiar estado del mantenimiento a en_proceso
            $this->update($mant['id'], [
                'estado' => 'en_proceso',
                'fecha_inicio' => date('Y-m-d H:i:s')
            ]);
            
            // Cambiar estado de la habitación a mantenimiento
            $sql_hab = "UPDATE habitaciones SET estado = 'mantenimiento' WHERE id = ? AND hotel_id = ? AND estado = 'disponible'";
            $this->db->query($sql_hab, [$mant['habitacion_id'], $hotelId]);
            
            $activados++;
        }
        
        return $activados;
    }

}
