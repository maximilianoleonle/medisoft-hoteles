// app/models/ConfiguracionInventario.php
<?php
namespace App\Models;

use App\Core\Model;

class ConfiguracionInventario extends Model {
    protected $table = 'inventario_config_habitacion';
    
    public function getConfiguracionPorTipo($tipo_habitacion) {
        $sql = "SELECT ich.*, p.nombre as producto_nombre, p.stock_actual
                FROM inventario_config_habitacion ich
                JOIN productos p ON ich.producto_id = p.id
                WHERE ich.tipo_habitacion = :tipo 
                AND ich.activo = 1
                AND p.descuento_automatico = 1";
        
        return $this->db->query($sql, ['tipo' => $tipo_habitacion])->fetchAll();
    }
    
    public function guardarConfiguracion($tipo_habitacion, $producto_id, $cantidad) {
        $sql = "INSERT INTO inventario_config_habitacion 
                (tipo_habitacion, producto_id, cantidad_descontar) 
                VALUES (:tipo, :producto_id, :cantidad)
                ON DUPLICATE KEY UPDATE 
                cantidad_descontar = :cantidad,
                activo = 1";
        
        return $this->db->query($sql, [
            'tipo' => $tipo_habitacion,
            'producto_id' => $producto_id,
            'cantidad' => $cantidad
        ]);
    }
}