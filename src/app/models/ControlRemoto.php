<?php
/**
 * Modelo de Control de Remotos - ACTUALIZADO
 * Los Cedros
 * 
 * Cambios v2.0:
 * - Soporte para entrega/recepción múltiple de remotos
 * - Campo nombre_propietario_ine
 */

class ControlRemoto extends Model {
    protected $table = 'control_remotos';
    protected $fillable = [
        'habitacion_id',
        'reservacion_id',
        'tiene_remoto',
        'ultima_entrega_at',
        'ultima_recogida_at',
        'entregada_por_id',
        'recibida_por_id',
        'recibida_por_manual',
        'entregada_por_manual',
        'tipo_identificacion',
        'nombre_propietario_ine',
        'notas'
    ];
    
    /**
     * Verificar si el hotel tiene el control remoto
     */
    public function hotelTieneRemoto($habitacion_id) {
        $sql = "SELECT tiene_remoto FROM {$this->table} WHERE habitacion_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['tiene_remoto'] : true;
    }
    
    /**
     * Entregar control remoto al huésped (INDIVIDUAL)
     */
    public function entregarRemoto($habitacion_id, $reservacion_id, $entregada_por_id, $entregada_por_manual = null, $tipo_identificacion = null, $nombre_propietario_ine = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Actualizar control_remotos
            $sql = "UPDATE {$this->table} 
                    SET tiene_remoto = 0,
                        ultima_entrega_at = NOW(),
                        entregada_por_id = ?,
                        entregada_por_manual = ?,
                        reservacion_id = ?,
                        tipo_identificacion = ?,
                        nombre_propietario_ine = ?
                    WHERE habitacion_id = ?";
            
            $db->query($sql, [
                $entregada_por_id, 
                $entregada_por_manual, 
                $reservacion_id, 
                $tipo_identificacion,
                $nombre_propietario_ine,
                $habitacion_id
            ]);
            
            // Registrar en historial
            $sql = "INSERT INTO historial_remotos 
                    (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, tipo_identificacion, nombre_propietario_ine) 
                    VALUES (?, ?, 'entrega', NOW(), ?, ?, ?, ?)";
            
            $db->query($sql, [
                $habitacion_id, 
                $reservacion_id, 
                $entregada_por_id, 
                $entregada_por_manual, 
                $tipo_identificacion,
                $nombre_propietario_ine
            ]);
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al entregar control remoto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Entregar control remoto a MÚLTIPLES habitaciones con la misma INE
     * 
     * @param array $habitaciones_ids Array de IDs de habitaciones
     * @param int $reservacion_id ID de la reservación
     * @param int $entregada_por_id ID del usuario que entrega
     * @param string $entregada_por_manual Nombre manual de quien entrega
     * @param string $tipo_identificacion Tipo de identificación (ine, licencia, otro)
     * @param string $nombre_propietario_ine Nombre del propietario de la identificación
     * @return array Resultado con éxito y detalles
     */
    public function entregarRemotosMultiples($habitaciones_ids, $reservacion_id, $entregada_por_id, $entregada_por_manual = null, $tipo_identificacion = null, $nombre_propietario_ine = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $exitosas = 0;
            $fallidas = 0;
            $detalles = [];
            
            foreach ($habitaciones_ids as $habitacion_id) {
                try {
                    // Actualizar control_remotos
                    $sql = "UPDATE {$this->table} 
                            SET tiene_remoto = 0,
                                ultima_entrega_at = NOW(),
                                entregada_por_id = ?,
                                entregada_por_manual = ?,
                                reservacion_id = ?,
                                tipo_identificacion = ?,
                                nombre_propietario_ine = ?
                            WHERE habitacion_id = ?";
                    
                    $db->query($sql, [
                        $entregada_por_id, 
                        $entregada_por_manual, 
                        $reservacion_id, 
                        $tipo_identificacion,
                        $nombre_propietario_ine,
                        $habitacion_id
                    ]);
                    
                    // Registrar en historial
                    $sql = "INSERT INTO historial_remotos 
                            (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, tipo_identificacion, nombre_propietario_ine) 
                            VALUES (?, ?, 'entrega', NOW(), ?, ?, ?, ?)";
                    
                    $db->query($sql, [
                        $habitacion_id, 
                        $reservacion_id, 
                        $entregada_por_id, 
                        $entregada_por_manual, 
                        $tipo_identificacion,
                        $nombre_propietario_ine
                    ]);
                    
                    $exitosas++;
                    $detalles[] = "Habitación ID $habitacion_id: OK";
                    
                } catch (Exception $e) {
                    $fallidas++;
                    $detalles[] = "Habitación ID $habitacion_id: Error - " . $e->getMessage();
                    error_log("Error al entregar remoto de habitación $habitacion_id: " . $e->getMessage());
                }
            }
            
            $db->commit();
            
            return [
                'success' => $exitosas > 0,
                'exitosas' => $exitosas,
                'fallidas' => $fallidas,
                'total' => count($habitaciones_ids),
                'detalles' => $detalles
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al entregar controles remotos múltiples: " . $e->getMessage());
            return [
                'success' => false,
                'exitosas' => 0,
                'fallidas' => count($habitaciones_ids),
                'total' => count($habitaciones_ids),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Recibir control remoto del huésped (INDIVIDUAL)
     */
    public function recibirRemoto($habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual = null, $notas = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Actualizar control_remotos
            $sql = "UPDATE {$this->table} 
                    SET tiene_remoto = 1,
                        ultima_recogida_at = NOW(),
                        recibida_por_id = ?,
                        recibida_por_manual = ?,
                        tipo_identificacion = NULL,
                        nombre_propietario_ine = NULL,
                        notas = ?
                    WHERE habitacion_id = ?";
            
            $db->query($sql, [$recibida_por_id, $recibida_por_manual, $notas, $habitacion_id]);
            
            // Registrar en historial
            $sql = "INSERT INTO historial_remotos 
                    (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, notas) 
                    VALUES (?, ?, 'recogida', NOW(), ?, ?, ?)";
            
            $db->query($sql, [$habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual, $notas]);
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al recibir control remoto: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recibir control remoto de MÚLTIPLES habitaciones
     * 
     * @param array $habitaciones_ids Array de IDs de habitaciones
     * @param int $reservacion_id ID de la reservación
     * @param int $recibida_por_id ID del usuario que recibe
     * @param string $recibida_por_manual Nombre manual de quien recibe
     * @param string $notas Notas opcionales
     * @return array Resultado con éxito y detalles
     */
    public function recibirRemotosMultiples($habitaciones_ids, $reservacion_id, $recibida_por_id, $recibida_por_manual = null, $notas = null) {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $exitosas = 0;
            $fallidas = 0;
            $detalles = [];
            
            foreach ($habitaciones_ids as $habitacion_id) {
                try {
                    // Actualizar control_remotos
                    $sql = "UPDATE {$this->table} 
                            SET tiene_remoto = 1,
                                ultima_recogida_at = NOW(),
                                recibida_por_id = ?,
                                recibida_por_manual = ?,
                                tipo_identificacion = NULL,
                                nombre_propietario_ine = NULL,
                                notas = ?
                            WHERE habitacion_id = ?";
                    
                    $db->query($sql, [$recibida_por_id, $recibida_por_manual, $notas, $habitacion_id]);
                    
                    // Registrar en historial
                    $sql = "INSERT INTO historial_remotos 
                            (habitacion_id, reservacion_id, tipo_movimiento, fecha_hora, usuario_id, usuario_manual, notas) 
                            VALUES (?, ?, 'recogida', NOW(), ?, ?, ?)";
                    
                    $db->query($sql, [$habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual, $notas]);
                    
                    $exitosas++;
                    $detalles[] = "Habitación ID $habitacion_id: OK";
                    
                } catch (Exception $e) {
                    $fallidas++;
                    $detalles[] = "Habitación ID $habitacion_id: Error - " . $e->getMessage();
                    error_log("Error al recibir remoto de habitación $habitacion_id: " . $e->getMessage());
                }
            }
            
            $db->commit();
            
            return [
                'success' => $exitosas > 0,
                'exitosas' => $exitosas,
                'fallidas' => $fallidas,
                'total' => count($habitaciones_ids),
                'detalles' => $detalles
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error al recibir controles remotos múltiples: " . $e->getMessage());
            return [
                'success' => false,
                'exitosas' => 0,
                'fallidas' => count($habitaciones_ids),
                'total' => count($habitaciones_ids),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener historial de control remoto de una habitación
     */
    public function obtenerHistorial($habitacion_id, $limite = 10) {
        $sql = "SELECT hr.*, u.nombre_completo as usuario_nombre,
                h.numero as habitacion_numero
                FROM historial_remotos hr
                LEFT JOIN usuarios u ON hr.usuario_id = u.id
                LEFT JOIN habitaciones h ON hr.habitacion_id = h.id
                WHERE hr.habitacion_id = ?
                ORDER BY hr.fecha_hora DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id, $limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial de control remoto de una reservación
     */
    public function obtenerHistorialPorReservacion($reservacion_id) {
        $sql = "SELECT hr.*, u.nombre_completo as usuario_nombre,
                h.numero as habitacion_numero
                FROM historial_remotos hr
                LEFT JOIN usuarios u ON hr.usuario_id = u.id
                LEFT JOIN habitaciones h ON hr.habitacion_id = h.id
                WHERE hr.reservacion_id = ?
                ORDER BY hr.fecha_hora DESC";
        
        $stmt = $this->db->query($sql, [$reservacion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener información de control remoto por habitación
     */
    public function obtenerPorHabitacion($habitacion_id) {
        $sql = "SELECT cr.*, 
                ue.nombre_completo as entregada_por_nombre,
                ur.nombre_completo as recibida_por_nombre
                FROM {$this->table} cr
                LEFT JOIN usuarios ue ON cr.entregada_por_id = ue.id
                LEFT JOIN usuarios ur ON cr.recibida_por_id = ur.id
                WHERE cr.habitacion_id = ?";
        
        $stmt = $this->db->query($sql, [$habitacion_id]);
        return $stmt->fetch();
    }
    
    /**
     * Contar cuántos remotos están con el huésped en una reservación
     */
    public function contarRemotosConHuesped($reservacion_id) {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} cr
                INNER JOIN habitaciones h ON cr.habitacion_id = h.id
                INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                WHERE rh.reservacion_id = ? AND cr.tiene_remoto = 0";
        
        $stmt = $this->db->query($sql, [$reservacion_id]);
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Contar cuántos remotos están en el hotel en una reservación
     */
    public function contarRemotosEnHotel($reservacion_id) {
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} cr
                INNER JOIN habitaciones h ON cr.habitacion_id = h.id
                INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                WHERE rh.reservacion_id = ? AND cr.tiene_remoto = 1";
        
        $stmt = $this->db->query($sql, [$reservacion_id]);
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }
}