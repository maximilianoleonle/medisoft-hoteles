<?php
/**
 * Modelo HabitacionImagen
 * app/models/HabitacionImagen.php
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class HabitacionImagen extends Model {
    protected $table = 'habitacion_imagenes';
    protected $fillable = [
        'hotel_id',
        'habitacion_id',
        'url',
        'descripcion',
        'es_principal',
        'orden'
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
     * Obtener todas las imágenes de una habitación
     */
    public function porHabitacion($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? AND hotel_id = ?
                ORDER BY es_principal DESC, orden ASC, id ASC";
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener imagen principal de una habitación
     */
    public function obtenerPrincipal($habitacion_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE habitacion_id = ? AND hotel_id = ? AND es_principal = 1
                LIMIT 1";
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetch();
    }
    
    /**
     * Establecer imagen como principal
     */
    public function establecerPrincipal($imagen_id, $habitacion_id) {
        $hotelId = $this->hotelIdActual();
        // Primero quitar principal a todas las demás
        $sql1 = "UPDATE {$this->table} SET es_principal = 0 WHERE habitacion_id = ? AND hotel_id = ?";
        $this->db->query($sql1, [$habitacion_id, $hotelId]);
        
        // Establecer la nueva principal
        $sql2 = "UPDATE {$this->table} SET es_principal = 1 WHERE id = ? AND habitacion_id = ? AND hotel_id = ?";
        return $this->db->query($sql2, [$imagen_id, $habitacion_id, $hotelId]);
    }
    
    /**
     * Contar imágenes de una habitación
     */
    public function contarPorHabitacion($habitacion_id) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE habitacion_id = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Obtener siguiente orden para nueva imagen
     */
    public function obtenerSiguienteOrden($habitacion_id) {
        $sql = "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente 
                FROM {$this->table} WHERE habitacion_id = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        $result = $stmt->fetch();
        return $result['siguiente'];
    }
    
    /**
     * Reordenar imágenes
     */
    public function reordenar($habitacion_id, $orden_imagenes) {
        $hotelId = $this->hotelIdActual();
        foreach ($orden_imagenes as $orden => $imagen_id) {
            $sql = "UPDATE {$this->table} SET orden = ? 
                    WHERE id = ? AND habitacion_id = ? AND hotel_id = ?";
            $this->db->query($sql, [$orden, $imagen_id, $habitacion_id, $hotelId]);
        }
        return true;
    }
}
