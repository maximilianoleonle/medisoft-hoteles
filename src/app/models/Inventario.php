<?php
/**
 * Modelo de Inventario
 * Los Cedros
 */

class Inventario extends Model {
    protected $table = 'inventario_productos'; // CORREGIDO
    protected $fillable = [
        'codigo',
        'nombre', 
        'categoria_id',
        'stock_actual',
        'stock_minimo',
        'descuento_automatico',
        'costo_unitario', // Cambiado de precio_unitario
        'unidad_medida',
        'activo'
    ];
    
    /**
     * Obtener todos los productos con su categoría
     */
    public function getAllWithCategory() {
        $sql = "SELECT p.*, 
                COALESCE(c.nombre, 'Sin categoría') as categoria_nombre 
                FROM inventario_productos p 
                LEFT JOIN inventario_categorias c ON p.categoria_id = c.id 
                WHERE p.activo = 1 
                ORDER BY p.nombre";
        return $this->query($sql);
    }
    
    /**
     * Obtener un producto con su categoría
     */
    public function getByIdWithCategory($id) {
        $sql = "SELECT p.*, c.nombre as categoria_nombre 
                FROM inventario_productos p 
                LEFT JOIN inventario_categorias c ON p.categoria_id = c.id 
                WHERE p.id = ?";
        $results = $this->query($sql, [$id]);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Actualizar stock
     */
    public function actualizarStock($producto_id, $cantidad, $tipo = 'SALIDA') {
        $db = Database::getInstance();
        
        if ($tipo == 'SALIDA') {
            $cantidad = -abs($cantidad);
        } else {
            $cantidad = abs($cantidad);
        }
        
        $sql = "UPDATE inventario_productos SET stock_actual = stock_actual + ? WHERE id = ?";
        return $db->query($sql, [$cantidad, $producto_id]);
    }
    
    /**
     * Obtener stock actual
     */
    public function getStock($producto_id) {
        $producto = $this->find($producto_id);
        return $producto ? $producto['stock_actual'] : 0;
    }
    
    /**
     * Obtener productos para descuento automático
     */
    public function getProductosDescuentoAutomatico() {
        return $this->where(['descuento_automatico' => 1, 'activo' => 1]);
    }
    
    /**
     * Obtener categorías
     */
    public function getCategorias() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM inventario_categorias WHERE activo = 1 ORDER BY orden";
        $stmt = $db->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Obtener habitaciones activas
     */
    public function getHabitacionesActivas() {
        $db = Database::getInstance();
        $sql = "SELECT id, numero FROM habitaciones WHERE activa = 1 ORDER BY numero";
        $stmt = $db->query($sql);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    /**
     * Obtener configuración completa
     */
    /**
 * Obtener configuración completa
 */
public function getConfiguracionCompleta() {
    $db = Database::getInstance();
    
    try {
        // Usar directamente inventario_config_habitacion
        $sql = "SELECT 
                    ich.tipo_habitacion,
                    ich.producto_id,
                    ich.cantidad_descontar,
                    ich.activo,
                    p.nombre as producto_nombre,
                    p.codigo
                FROM inventario_config_habitacion ich
                JOIN inventario_productos p ON ich.producto_id = p.id
                WHERE ich.activo = 1 AND p.activo = 1
                ORDER BY ich.tipo_habitacion, p.nombre";
        
        $stmt = $db->query($sql);
        $results = $stmt ? $stmt->fetchAll() : [];
        
        // Organizar por tipo de habitación
        $configuracion = [];
        foreach ($results as $row) {
            if (!isset($configuracion[$row['tipo_habitacion']])) {
                $configuracion[$row['tipo_habitacion']] = [];
            }
            $configuracion[$row['tipo_habitacion']][] = $row;
        }
        
        return $configuracion;
        
    } catch (Exception $e) {
        error_log("Error en getConfiguracionCompleta: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Actualizar configuración de habitación
     */
    /**
 * Actualizar configuración de habitación
 */
public function actualizarConfiguracion($tipo_habitacion, $producto_id, $cantidad) {
    $db = Database::getInstance();
    
    try {
        // Convertir cantidad a entero para evitar decimales
        $cantidad = intval($cantidad);
        
        // Primero verificar si existe el registro
        $sql = "SELECT id FROM inventario_config_habitacion 
                WHERE tipo_habitacion = ? AND producto_id = ?";
        $stmt = $db->query($sql, [$tipo_habitacion, $producto_id]);
        $existe = $stmt ? $stmt->fetch() : null;
        
        if ($existe) {
            // Si existe, actualizar
            $sql = "UPDATE inventario_config_habitacion 
                    SET cantidad_descontar = ?, 
                        activo = ?,
                        created_at = NOW()
                    WHERE tipo_habitacion = ? AND producto_id = ?";
            $result = $db->query($sql, [
                $cantidad, 
                $cantidad > 0 ? 1 : 0, 
                $tipo_habitacion, 
                $producto_id
            ]);
        } else {
            // Si no existe y la cantidad es mayor a 0, insertar
            if ($cantidad > 0) {
                $sql = "INSERT INTO inventario_config_habitacion 
                        (tipo_habitacion, producto_id, cantidad_descontar, activo, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $result = $db->query($sql, [
                    $tipo_habitacion, 
                    $producto_id, 
                    $cantidad, 
                    1
                ]);
            } else {
                // Si la cantidad es 0 y no existe, no hacer nada
                return true;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error en actualizarConfiguracion: " . $e->getMessage());
        return false;
    }
}}