<?php
/**
 * Modelo de Control de Llaves
 * Los Cedros
 */

class ControlLlave extends Model {
    protected $table = 'control_llaves';
    protected $fillable = [
        'habitacion_id',
        'reservacion_id',
        'estado',
        'tiene_llave',
        'fecha_prestamo',
        'fecha_devolucion',
        'usuario_presta_id',
        'usuario_recibe_id',
        'ultima_entrega_at',
        'ultima_recogida_at',
        'entregada_por_id',
        'recibida_por_id',
        'recibida_por_manual',
        'entregada_por_manual',
        'notas'
    ];
    
    /**
     * Verificar si el hotel tiene la llave
     */
    public function hotelTieneLlave($habitacion_id) {
        $sql = "SELECT tiene_llave FROM {$this->table} WHERE habitacion_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['tiene_llave'] : true;
    }
    
    /**
     * Entregar llave al huésped
     */
    public function entregarLlave($habitacion_id, $reservacion_id, $entregada_por_id, $entregada_por_manual = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Actualizar control_llaves
            $sql = "UPDATE {$this->table} 
                    SET tiene_llave = 0,
                        ultima_entrega_at = NOW(),
                        entregada_por_id = ?,
                        entregada_por_manual = ?,
                        reservacion_id = ?
                    WHERE habitacion_id = ?";
            
            $db->query($sql, [$entregada_por_id, $entregada_por_manual, $reservacion_id, $habitacion_id]);
            
            // Registrar en historial
            $sql = "INSERT INTO historial_llaves 
                    (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual) 
                    VALUES (?, ?, 'entrega', NOW(), ?, ?)";
            
            $db->query($sql, [$habitacion_id, $reservacion_id, $entregada_por_id, $entregada_por_manual]);
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al entregar llave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recibir llave del huésped
     */
    public function recibirLlave($habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual = null, $notas = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Actualizar control_llaves
            $sql = "UPDATE {$this->table} 
                    SET tiene_llave = 1,
                        ultima_recogida_at = NOW(),
                        recibida_por_id = ?,
                        recibida_por_manual = ?,
                        notas = ?
                    WHERE habitacion_id = ?";
            
            $db->query($sql, [$recibida_por_id, $recibida_por_manual, $notas, $habitacion_id]);
            
            // Registrar en historial
            $sql = "INSERT INTO historial_llaves 
                    (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, notas) 
                    VALUES (?, ?, 'recogida', NOW(), ?, ?, ?)";
            
            $db->query($sql, [$habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual, $notas]);
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al recibir llave: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener historial de llaves de una habitación
     */
    public function obtenerHistorial($habitacion_id, $limite = 10) {
        $sql = "SELECT hl.*, u.nombre_completo as usuario_nombre,
                h.numero as habitacion_numero
                FROM historial_llaves hl
                LEFT JOIN usuarios u ON hl.usuario_id = u.id
                LEFT JOIN habitaciones h ON hl.habitacion_id = h.id
                WHERE hl.habitacion_id = ?
                ORDER BY hl.fecha_hora DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial de llaves de una reservación
     */
    public function obtenerHistorialPorReservacion($reservacion_id) {
        $sql = "SELECT hl.*, u.nombre_completo as usuario_nombre,
                h.numero as habitacion_numero
                FROM historial_llaves hl
                LEFT JOIN usuarios u ON hl.usuario_id = u.id
                LEFT JOIN habitaciones h ON hl.habitacion_id = h.id
                WHERE hl.reservacion_id = ?
                ORDER BY hl.fecha_hora DESC";
        
        $stmt = $this->db->query($sql, [$reservacion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener información de control de llaves por habitación
     */
    public function obtenerPorHabitacion($habitacion_id) {
        $sql = "SELECT cl.*, 
                ue.nombre_completo as entregada_por_nombre,
                ur.nombre_completo as recibida_por_nombre
                FROM {$this->table} cl
                LEFT JOIN usuarios ue ON cl.entregada_por_id = ue.id
                LEFT JOIN usuarios ur ON cl.recibida_por_id = ur.id
                WHERE cl.habitacion_id = ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener todas las llaves en poder del hotel
     */
    public function llavesEnHotel() {
        $sql = "SELECT cl.*, h.numero, h.estado as habitacion_estado,
                r.id as reservacion_id, hu.nombre_completo as huesped_nombre
                FROM {$this->table} cl
                INNER JOIN habitaciones h ON cl.habitacion_id = h.id
                LEFT JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                LEFT JOIN reservaciones r ON rh.reservacion_id = r.id AND r.estado = 'checked_in'
                LEFT JOIN huespedes hu ON r.huesped_id = hu.id
                WHERE cl.tiene_llave = 1
                AND h.estado = 'ocupada'
                ORDER BY cl.ultima_recogida_at DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}