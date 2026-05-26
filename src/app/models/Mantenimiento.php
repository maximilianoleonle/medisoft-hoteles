<?php
/**
 * Modelo de Mantenimiento
 * Los Cedros
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';

class Mantenimiento extends Model {
    protected $table = 'mantenimientos_habitaciones';
    protected $fillable = [
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
                WHERE habitacion_id = ? AND estado = 'en_proceso' 
                ORDER BY fecha_inicio DESC LIMIT 1";
        
        $stmt = $this->db->query($sql, [$habitacion_id]);
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
        $data['estado'] = 'en_proceso';
        $data['fecha_inicio'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
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
        $where = "";
        $params = [];
        
        if ($fecha_inicio && $fecha_fin) {
            $where = "WHERE fecha_inicio BETWEEN ? AND ?";
            $params = [$fecha_inicio, $fecha_fin];
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
                WHERE m.habitacion_id = ?
                ORDER BY m.fecha_inicio DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar mantenimientos
     */
    public function buscar($filtros = []) {
        $conditions = [];
        $params = [];
        
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
                AND programado = 1 
                AND estado = 'programado'
                ORDER BY fecha_programada ASC";
        
        $stmt = $this->db->query($sql, [$habitacion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener todos los mantenimientos programados (activos)
     */
    public function todosProgramados() {
        $sql = "SELECT m.*, h.numero as habitacion_numero, h.tipo as habitacion_tipo,
                       h.piso as habitacion_piso
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.programado = 1 
                AND m.estado = 'programado'
                ORDER BY m.fecha_programada ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Verificar si una habitación tiene mantenimiento programado que conflicte con fechas
     * Retorna true si HAY conflicto
     */
    public function tieneConflictoConFechas($habitacion_id, $fecha_entrada, $fecha_salida) {
        $sql = "SELECT COUNT(*) as conflictos FROM {$this->table} 
                WHERE habitacion_id = ? 
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
        $sql = "SELECT DISTINCT m.habitacion_id, m.fecha_programada, m.fecha_programada_fin,
                       m.tipo_mantenimiento, m.motivo, h.numero as habitacion_numero
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.estado IN ('programado', 'en_proceso')
                AND (
                    (m.programado = 1 AND m.fecha_programada IS NOT NULL AND (
                        (m.fecha_programada < ? AND (m.fecha_programada_fin IS NULL OR m.fecha_programada_fin > ?))
                        OR (m.fecha_programada >= ? AND m.fecha_programada < ?)
                        OR (m.fecha_programada_fin IS NOT NULL AND m.fecha_programada_fin > ? AND m.fecha_programada < ?)
                    ))
                    OR (m.estado = 'en_proceso' AND m.programado = 0)
                )";
        
        $params = [
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
        
        $sql = "SELECT m.*, h.numero as habitacion_numero 
                FROM {$this->table} m
                INNER JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.programado = 1 
                AND m.estado = 'programado' 
                AND m.fecha_programada <= ?";
        
        $stmt = $this->db->query($sql, [$hoy]);
        $pendientes = $stmt->fetchAll();
        
        $activados = 0;
        foreach ($pendientes as $mant) {
            // Cambiar estado del mantenimiento a en_proceso
            $this->update($mant['id'], [
                'estado' => 'en_proceso',
                'fecha_inicio' => date('Y-m-d H:i:s')
            ]);
            
            // Cambiar estado de la habitación a mantenimiento
            $sql_hab = "UPDATE habitaciones SET estado = 'mantenimiento' WHERE id = ? AND estado = 'disponible'";
            $this->db->query($sql_hab, [$mant['habitacion_id']]);
            
            $activados++;
        }
        
        return $activados;
    }

}