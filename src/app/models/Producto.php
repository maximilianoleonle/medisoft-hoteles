<?php
/**
 * Modelo Producto
 * Los Cedros
 */

class Producto extends Model {
    protected $table = 'productos';
    
    /**
     * Obtener todos los productos
     */
    public function getAll($activos = true) {
        $sql = "SELECT p.*, cp.nombre as categoria_nombre 
                FROM productos p
                LEFT JOIN categorias_producto cp ON p.categoria_id = cp.id";
        if ($activos) {
            $sql .= " WHERE p.activo = 1";
        }
        $sql .= " ORDER BY cp.nombre, p.nombre";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Obtener productos con descuento automático
     */
    public function getProductosAutomaticos() {
        $sql = "SELECT * FROM productos 
                WHERE descuento_automatico = 1 AND activo = 1 
                ORDER BY nombre";
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Actualizar stock
     */
    public function actualizarStock($producto_id, $cantidad, $tipo = 'salida') {
        $operador = $tipo === 'salida' ? '-' : '+';
        $sql = "UPDATE productos 
                SET stock_actual = stock_actual $operador :cantidad 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'cantidad' => $cantidad,
            'id' => $producto_id
        ]);
    }
}