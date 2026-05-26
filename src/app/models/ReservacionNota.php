<?php
/**
 * Modelo de Notas de Reservación
 * Los Cedros
 * Archivo: /models/ReservacionNota.php
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReservacionNota extends Model {
    protected $table = 'reservacion_notas';
    protected $fillable = ['hotel_id', 'reservacion_id', 'usuario_id', 'nota'];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function reservacionPerteneceHotel($reservacion_id, $hotel_id = null) {
        $hotel_id = $hotel_id ?: $this->hotelIdActual();
        $sql = "SELECT id FROM reservaciones WHERE id = ? AND hotel_id = ? LIMIT 1";
        $stmt = $this->db->query($sql, [$reservacion_id, $hotel_id]);

        return $stmt && $stmt->fetch();
    }
    
    /**
     * Obtener todas las notas de una reservación
     */
    public function obtenerPorReservacion($reservacion_id) {
        $sql = "SELECT rn.*, u.nombre_completo as usuario_nombre
                FROM {$this->table} rn
                INNER JOIN usuarios u ON rn.usuario_id = u.id
                INNER JOIN reservaciones r ON rn.reservacion_id = r.id AND rn.hotel_id = r.hotel_id
                WHERE rn.reservacion_id = ?
                AND rn.hotel_id = ?
                ORDER BY rn.created_at DESC";
        
        $stmt = $this->db->query($sql, [$reservacion_id, $this->hotelIdActual()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Agregar una nueva nota
     */
    public function agregarNota($reservacion_id, $usuario_id, $nota) {
        try {
            $hotel_id = $this->hotelIdActual();

            if (!$this->reservacionPerteneceHotel($reservacion_id, $hotel_id)) {
                return ['success' => false, 'error' => 'Reservacion no encontrada en el hotel actual'];
            }

            $sql = "INSERT INTO {$this->table} (hotel_id, reservacion_id, usuario_id, nota, created_at)
                    VALUES (?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->query($sql, [$hotel_id, $reservacion_id, $usuario_id, $nota]);
            
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
        $sql = "SELECT COUNT(*) as total
                FROM {$this->table} rn
                INNER JOIN reservaciones r ON rn.reservacion_id = r.id AND rn.hotel_id = r.hotel_id
                WHERE rn.reservacion_id = ?
                AND rn.hotel_id = ?";
        $stmt = $this->db->query($sql, [$reservacion_id, $this->hotelIdActual()]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
