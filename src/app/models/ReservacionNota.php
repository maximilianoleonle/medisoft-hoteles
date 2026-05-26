<?php
/**
 * Modelo de Notas de Reservación
 * Los Cedros
 * Archivo: /models/ReservacionNota.php
 */

require_once __DIR__ . '/../../core/Model.php';

class ReservacionNota extends Model {
    protected $table = 'reservacion_notas';
    protected $fillable = ['reservacion_id', 'usuario_id', 'nota'];
    
    /**
     * Obtener todas las notas de una reservación
     */
    public function obtenerPorReservacion($reservacion_id) {
        $sql = "SELECT rn.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} rn
                INNER JOIN usuarios u ON rn.usuario_id = u.id
                WHERE rn.reservacion_id = ?
                ORDER BY rn.created_at DESC";
        
        $stmt = $this->db->query($sql, [$reservacion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Agregar una nueva nota
     */
    public function agregarNota($reservacion_id, $usuario_id, $nota) {
        try {
            $sql = "INSERT INTO {$this->table} (reservacion_id, usuario_id, nota, created_at)
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->query($sql, [$reservacion_id, $usuario_id, $nota]);
            
            if ($stmt) {
                return [
                    'success' => true,
                    'id' => $this->db->lastInsertId()
                ];
            }
            
            return ['success' => false, 'error' => 'No se pudo guardar la nota'];
            
        } catch (Exception $e) {
            error_log("Error al agregar nota: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Contar notas de una reservación
     */
    public function contarNotas($reservacion_id) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE reservacion_id = ?";
        $stmt = $this->db->query($sql, [$reservacion_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}