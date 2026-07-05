<?php
/**
 * Modelo de Huésped
 * Los Cedros
 */

class Huesped extends Model {
    protected $table = 'huespedes';
    protected $fillable = [
        'nombre_completo',
        'telefono',
        'email',
        'procedencia_estado',
        'procedencia_ciudad',
        'vehiculo_marca',
        'vehiculo_placas',
        'notas'
    ];
    
    /**
     * Obtener lista de estados de México
     */
    public static function getEstados() {
        return [
            'Aguascalientes' => 'Aguascalientes',
            'Baja California' => 'Baja California',
            'Baja California Sur' => 'Baja California Sur',
            'Campeche' => 'Campeche',
            'Chiapas' => 'Chiapas',
            'Chihuahua' => 'Chihuahua',
            'Ciudad de México' => 'Ciudad de México',
            'Coahuila' => 'Coahuila',
            'Colima' => 'Colima',
            'Durango' => 'Durango',
            'Estado de México' => 'Estado de México',
            'Guanajuato' => 'Guanajuato',
            'Guerrero' => 'Guerrero',
            'Hidalgo' => 'Hidalgo',
            'Jalisco' => 'Jalisco',
            'Michoacán' => 'Michoacán',
            'Morelos' => 'Morelos',
            'Nayarit' => 'Nayarit',
            'Nuevo León' => 'Nuevo León',
            'Oaxaca' => 'Oaxaca',
            'Puebla' => 'Puebla',
            'Querétaro' => 'Querétaro',
            'Quintana Roo' => 'Quintana Roo',
            'San Luis Potosí' => 'San Luis Potosí',
            'Sinaloa' => 'Sinaloa',
            'Sonora' => 'Sonora',
            'Tabasco' => 'Tabasco',
            'Tamaulipas' => 'Tamaulipas',
            'Tlaxcala' => 'Tlaxcala',
            'Veracruz' => 'Veracruz',
            'Yucatán' => 'Yucatán',
            'Zacatecas' => 'Zacatecas'
        ];
    }
    
    /**
     * Buscar huéspedes por término
     */
    public function buscar($termino) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE nombre_completo LIKE ? 
                OR telefono LIKE ?
                OR email LIKE ?
                OR vehiculo_placas LIKE ?
                ORDER BY nombre_completo";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$termino, $termino, $termino, $termino]);
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar por teléfono exacto
     */
    public function buscarPorTelefono($telefono) {
        return $this->first(['telefono' => $telefono]);
    }
    
    /**
     * Obtener historial de reservaciones del huésped (ACTUALIZADO)
     */
    public function getReservaciones($huesped_id) {
        $sql = "SELECT r.*, 
                GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') as habitaciones_numeros,
                GROUP_CONCAT(DISTINCT h.tipo) as tipos_habitacion,
                COUNT(DISTINCT rh.habitacion_id) as total_habitaciones,
                SUM(CASE WHEN rh.es_cortesia = 1 THEN 1 ELSE 0 END) as habitaciones_cortesia
                FROM reservaciones r
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
                WHERE r.huesped_id = ?
                GROUP BY r.id
                ORDER BY r.fecha_entrada DESC";
        
        $stmt = $this->db->query($sql, [$huesped_id]);
        return $stmt->fetchAll();
    }
    
    // Agregar estos métodos a la clase Huesped existente:

/**
 * Obtener vehículos del huésped
 */
public function getVehiculos($huesped_id) {
    $vehiculoModel = new HuespedVehiculo();
    return $vehiculoModel->porHuesped($huesped_id);
}

/**
 * Contar vehículos activos del huésped
 */
public function contarVehiculos($huesped_id) {
    $sql = "SELECT COUNT(*) as total FROM huesped_vehiculos 
            WHERE huesped_id = ? AND activo = 1";
    $stmt = $this->db->query($sql, [$huesped_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Obtener huéspedes con sus vehículos
 */
public function buscarConVehiculos($termino = null) {
    $sql = "SELECT h.*, 
            COUNT(DISTINCT v.id) as total_vehiculos,
            GROUP_CONCAT(DISTINCT CONCAT(v.marca, ' - ', v.placas) SEPARATOR ', ') as vehiculos_info
            FROM {$this->table} h
            LEFT JOIN huesped_vehiculos v ON h.id = v.huesped_id AND v.activo = 1";
    
    $params = [];
    
    if ($termino) {
        $sql .= " WHERE h.nombre_completo LIKE ? 
                  OR h.telefono LIKE ?
                  OR h.email LIKE ?
                  OR v.placas LIKE ?";
        $termino_like = '%' . $termino . '%';
        $params = [$termino_like, $termino_like, $termino_like, $termino_like];
    }
    
    $sql .= " GROUP BY h.id ORDER BY h.nombre_completo";
    
    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
}
    /**
     * Contar reservaciones del huésped
     */
    public function contarReservaciones($huesped_id) {
        $sql = "SELECT COUNT(*) as total FROM reservaciones WHERE huesped_id = ?";
        $stmt = $this->db->query($sql, [$huesped_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtener estadísticas de huéspedes por estado
     */
    public function estadisticasPorEstado() {
        $sql = "SELECT procedencia_estado, COUNT(*) as total 
                FROM {$this->table} 
                WHERE procedencia_estado IS NOT NULL 
                GROUP BY procedencia_estado 
                ORDER BY total DESC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar datos del huésped
     */
    public function validar($data, $id = null) {
        $errores = [];
        
        // Validar nombre completo
        if (empty($data['nombre_completo'])) {
            $errores[] = 'El nombre completo es obligatorio';
        } elseif (strlen($data['nombre_completo']) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres';
        } elseif (strlen($data['nombre_completo']) > 200) {
            $errores[] = 'El nombre no puede exceder 200 caracteres';
        }
        
        // Validar teléfono (opcional pero si se proporciona debe ser válido)
        if (!empty($data['telefono'])) {
            // Eliminar espacios y guiones
            $telefono_limpio = preg_replace('/[\s\-\(\)]/', '', $data['telefono']);
            if (!preg_match('/^[0-9]{10,15}$/', $telefono_limpio)) {
                $errores[] = 'El teléfono debe contener entre 10 y 15 dígitos';
            }
        }
        
        // Validar email (opcional)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        // Validar placas (opcional)
        if (!empty($data['vehiculo_placas'])) {
            if (strlen($data['vehiculo_placas']) > 20) {
                $errores[] = 'Las placas no pueden exceder 20 caracteres';
            }
        }
        
        return $errores;
    }
    
    /**
     * Verificar si existe un huésped con el mismo teléfono
     */
    public function existeTelefono($telefono, $excluir_id = null) {
        if (empty($telefono)) return false;
        
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE telefono = ?";
        $params = [$telefono];
        
        if ($excluir_id) {
            $sql .= " AND id != ?";
            $params[] = $excluir_id;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['total'] > 0;
    }
    
    /**
     * Obtener huéspedes frecuentes (más de 3 visitas) - ACTUALIZADO
     */
    public function getHuespedesFrecuentes($limite = 10) {
        $sql = "SELECT h.*, COUNT(DISTINCT r.id) as total_visitas,
                MAX(r.fecha_entrada) as ultima_visita,
                SUM(r.precio_total) as total_gastado
                FROM {$this->table} h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                WHERE r.estado IN ('checked_out', 'checked_in')
                GROUP BY h.id
                HAVING total_visitas >= 3
                ORDER BY total_visitas DESC, ultima_visita DESC
                LIMIT ?";
        
        $stmt = $this->db->query($sql, [$limite]);
        return $stmt->fetchAll();
    }
    
    /**
     * Contar huéspedes con vehículo
     */
    public function contarConVehiculo() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE vehiculo_marca IS NOT NULL 
                AND vehiculo_marca != ''";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Obtener estadísticas generales
     */
    public function obtenerEstadisticas() {
        $sql = "SELECT 
                COUNT(*) as total_huespedes,
                COUNT(CASE WHEN vehiculo_marca IS NOT NULL AND vehiculo_marca != '' THEN 1 END) as con_vehiculo,
                COUNT(DISTINCT procedencia_estado) as estados_diferentes,
                COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as con_email,
                COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as con_telefono
                FROM {$this->table}";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result ?: [];
    }
    
    /**
     * Buscar huéspedes para autocompletado
     */
    public function buscarAutocompletado($termino, $limite = 10) {
        $sql = "SELECT id, nombre_completo, telefono, procedencia_estado
                FROM {$this->table}
                WHERE nombre_completo LIKE ? OR telefono LIKE ?
                ORDER BY nombre_completo
                LIMIT ?";
        
        $termino = '%' . $termino . '%';
        $stmt = $this->db->query($sql, [$termino, $termino, $limite]);
        return $stmt->fetchAll();
    }
}