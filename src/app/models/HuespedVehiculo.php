<?php
/**
 * Modelo de Vehículos de Huéspedes
 * Los Cedros
 */

class HuespedVehiculo extends Model {
    protected $table = 'huesped_vehiculos';
    protected $fillable = [
        'huesped_id',
        'marca',
        'modelo',
        'placas',
        'color',
        'estacionamiento',
        'activo'
    ];
    
    /**
     * Obtener opciones de estacionamiento
     */
    public static function getEstacionamientos() {
        return [
            'coches' => 'Coches',
            'camionetas' => 'Camionetas',
            'discos' => 'Discos',
            'nikkos' => 'Nikkos'
        ];
    }
    
    /**
     * Obtener vehículos por huésped
     */
    public function porHuesped($huesped_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE huesped_id = ? AND activo = 1
                ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql, [$huesped_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar vehículo por placas
     */
    public function buscarPorPlacas($placas) {
        return $this->first(['placas' => $placas, 'activo' => 1]);
    }
    
    /**
     * Verificar si las placas ya existen (excluyendo un ID específico)
     */
    public function existenPlacas($placas, $excluir_id = null) {
        if (empty($placas)) return false;
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE placas = ? AND activo = 1";
        $params = [$placas];
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Contar vehículos activos por estacionamiento
     */
    public function contarPorEstacionamiento() {
        $sql = "SELECT estacionamiento, COUNT(*) as total 
                FROM {$this->table} 
                WHERE activo = 1 
                GROUP BY estacionamiento";
        
        $stmt = $this->db->query($sql);
        $resultados = $stmt->fetchAll();
        
        // Formatear resultados con los nuevos estacionamientos
        $conteo = [
            'coches' => 0,
            'camionetas' => 0,
            'discos' => 0,
            'nikkos' => 0
        ];
        
        foreach ($resultados as $resultado) {
            if (isset($conteo[$resultado['estacionamiento']])) {
                $conteo[$resultado['estacionamiento']] = $resultado['total'];
            }
        }
        
        return $conteo;
    }
    
    /**
     * Desactivar vehículo (soft delete)
     */
    public function desactivar($id) {
        return $this->update($id, ['activo' => 0]);
    }
    
    /**
     * Obtener información completa del vehículo con huésped
     */
    public function obtenerConHuesped($id) {
        $sql = "SELECT v.*, h.nombre_completo, h.telefono 
                FROM {$this->table} v
                INNER JOIN huespedes h ON v.huesped_id = h.id
                WHERE v.id = ? AND v.activo = 1";
        
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->fetch();
    }
    
    /**
     * Buscar vehículos con término de búsqueda
     */
    public function buscar($termino) {
        $sql = "SELECT v.*, h.nombre_completo 
                FROM {$this->table} v
                INNER JOIN huespedes h ON v.huesped_id = h.id
                WHERE v.activo = 1 
                AND (v.placas LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?)
                ORDER BY v.created_at DESC";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$termino, $termino, $termino]);
        return $stmt->fetchAll();
    }
    
    /**
     * Migrar vehículos existentes de la tabla huespedes
     * (Función de utilidad para migración inicial)
     */
    public function migrarVehiculosExistentes() {
        // Primero migrar los datos existentes con valores temporales
        $sql = "INSERT INTO {$this->table} (huesped_id, marca, placas, estacionamiento, created_at)
                SELECT id, vehiculo_marca, vehiculo_placas, 
                       CASE 
                           WHEN vehiculo_marca LIKE '%camioneta%' OR vehiculo_marca LIKE '%pickup%' THEN 'camionetas'
                           ELSE 'coches'
                       END as estacionamiento,
                       created_at
                FROM huespedes
                WHERE vehiculo_marca IS NOT NULL 
                AND vehiculo_marca != ''
                AND NOT EXISTS (
                    SELECT 1 FROM {$this->table} 
                    WHERE huesped_id = huespedes.id 
                    AND placas = huespedes.vehiculo_placas
                )";
        
        return $this->db->query($sql);
    }
}