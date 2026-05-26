<?php
/**
 * Modelo HabitacionImagen
 * app/models/HabitacionImagen.php
 */

class HabitacionImagen extends Model {
    protected $table = 'habitacion_imagenes';
    protected $fillable = [
        'habitacion_id',
        'url',
        'descripcion',
        'es_principal',
        'orden'
    ];
    
    /**
     * Obtener todas las imágenes de una habitación
     */
    public function porHabitacion($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? 
                ORDER BY es_principal DESC, orden ASC, id ASC";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener imagen principal de una habitación
     */
    public function obtenerPrincipal($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? AND es_principal = 1 
                LIMIT 1";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        return $stmt->fetch();
    }
    
    /**
     * Establecer imagen como principal
     */
    public function establecerPrincipal($imagen_id, $habitacion_id) {
        // Primero quitar principal a todas las demás
        $sql1 = "UPDATE {$this->table} SET es_principal = 0 WHERE habitacion_id = ?";
        $this->db->query($sql1, [$habitacion_id]);
        
        // Establecer la nueva principal
        $sql2 = "UPDATE {$this->table} SET es_principal = 1 WHERE id = ? AND habitacion_id = ?";
        return $this->db->query($sql2, [$imagen_id, $habitacion_id]);
    }
    
    /**
     * Contar imágenes de una habitación
     */
    public function contarPorHabitacion($habitacion_id) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE habitacion_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obtener siguiente orden para nueva imagen
     */
    public function obtenerSiguienteOrden($habitacion_id) {
        $sql = "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente 
                FROM {$this->table} WHERE habitacion_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id]);
        $result = $stmt->fetch();
        return $result['siguiente'];
    }
    
    /**
     * Reordenar imágenes
     */
    public function reordenar($habitacion_id, $orden_imagenes) {
        foreach ($orden_imagenes as $orden => $imagen_id) {
            $sql = "UPDATE {$this->table} SET orden = ? 
                    WHERE id = ? AND habitacion_id = ?";
            $this->db->query($sql, [$orden, $imagen_id, $habitacion_id]);
        }
        return true;
    }
}