<?php
class ConfiguracionInventarioService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Obtener configuración por tipo de habitación
    public function getConfiguracionPorTipo($tipo_habitacion) {
        $sql = "SELECT ich.*, p.nombre as producto_nombre, p.codigo
                FROM inventario_config_habitacion ich
                JOIN productos p ON ich.producto_id = p.id
                WHERE ich.tipo_habitacion = ? AND ich.activo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tipo_habitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener toda la configuración
    public function getConfiguracionCompleta() {
        $sql = "SELECT ich.*, p.nombre as producto_nombre, p.codigo
                FROM inventario_config_habitacion ich
                JOIN productos p ON ich.producto_id = p.id
                WHERE ich.activo = 1
                ORDER BY ich.tipo_habitacion, p.nombre";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar por tipo de habitación
        $configuracion = [];
        foreach ($results as $row) {
            if (!isset($configuracion[$row['tipo_habitacion']])) {
                $configuracion[$row['tipo_habitacion']] = [];
            }
            $configuracion[$row['tipo_habitacion']][] = $row;
        }
        
        return $configuracion;
    }
    
    // Actualizar configuración
    public function actualizarConfiguracion($tipo_habitacion, $producto_id, $cantidad) {
        // Verificar si existe
        $sql = "SELECT id FROM inventario_config_habitacion 
                WHERE tipo_habitacion = ? AND producto_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tipo_habitacion, $producto_id]);
        $existe = $stmt->fetch();
        
        if ($existe) {
            // Actualizar
            $sql = "UPDATE inventario_config_habitacion 
                    SET cantidad_descontar = ?, activo = ? 
                    WHERE tipo_habitacion = ? AND producto_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$cantidad, $cantidad > 0 ? 1 : 0, 
                                 $tipo_habitacion, $producto_id]);
        } else {
            // Insertar
            $sql = "INSERT INTO inventario_config_habitacion 
                    (tipo_habitacion, producto_id, cantidad_descontar, activo) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$tipo_habitacion, $producto_id, 
                                 $cantidad, $cantidad > 0 ? 1 : 0]);
        }
    }
}