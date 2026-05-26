<?php
namespace App\Services;

use App\Models\Inventario;
use PDO;

class ConfiguracionInventarioService {
    private $db;
    private $inventario;
    
    public function __construct($db) {
        $this->db = $db;
        $this->inventario = new Inventario();
    }
    
    /**
     * Obtener configuración de inventario por tipo de habitación
     */
    public function getConfiguracionPorTipo($tipo_habitacion) {
        $sql = "SELECT 
                    ich.*,
                    p.codigo,
                    p.nombre as producto_nombre,
                    c.nombre as categoria,
                    u.abreviatura as unidad
                FROM inventario_config_habitacion ich
                JOIN productos p ON ich.producto_id = p.id
                JOIN categorias_producto c ON p.categoria_id = c.id
                JOIN unidades_medida u ON p.unidad_medida_id = u.id
                WHERE ich.tipo_habitacion = :tipo
                AND ich.activo = TRUE
                ORDER BY c.orden, p.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tipo' => $tipo_habitacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Guardar configuración de inventario
     */
    public function guardarConfiguracion($tipo_habitacion, $configuraciones) {
        try {
            $this->db->beginTransaction();
            
            // Desactivar configuraciones anteriores
            $sql = "UPDATE inventario_config_habitacion 
                    SET activo = FALSE 
                    WHERE tipo_habitacion = :tipo";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':tipo' => $tipo_habitacion]);
            
            // Insertar nuevas configuraciones
            foreach ($configuraciones as $config) {
                $sql = "INSERT INTO inventario_config_habitacion 
                        (tipo_habitacion, tipo_habitacion_id, producto_id, 
                         cantidad_checkin, cantidad_limpieza, activo)
                        VALUES (:tipo, :tipo_id, :producto_id, 
                                :cant_checkin, :cant_limpieza, TRUE)
                        ON DUPLICATE KEY UPDATE
                            cantidad_checkin = VALUES(cantidad_checkin),
                            cantidad_limpieza = VALUES(cantidad_limpieza),
                            activo = TRUE";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':tipo' => $tipo_habitacion,
                    ':tipo_id' => $this->getTipoHabitacionId($tipo_habitacion),
                    ':producto_id' => $config['producto_id'],
                    ':cant_checkin' => $config['cantidad_checkin'] ?? 0,
                    ':cant_limpieza' => $config['cantidad_limpieza'] ?? 0
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Obtener ID del tipo de habitación
     */
    private function getTipoHabitacionId($tipo) {
        $tipos = [
            'sencilla' => 1,
            'doble' => 2,
            'triple' => 3,
            'cuadruple' => 4,
            'doble_jacuzzi' => 5,
            'sencilla_jacuzzi' => 6
        ];
        
        return $tipos[$tipo] ?? 1;
    }
    
    /**
     * Calcular consumo estimado mensual
     */
    public function calcularConsumoEstimado($tipo_habitacion, $ocupacion_promedio = 0.7) {
        $config = $this->getConfiguracionPorTipo($tipo_habitacion);
        
        $consumo_estimado = [];
        foreach ($config as $item) {
            $consumo_diario = $item['cantidad_checkin'] * $ocupacion_promedio;
            $consumo_mensual = $consumo_diario * 30;
            
            $consumo_estimado[] = [
                'producto' => $item['producto_nombre'],
                'consumo_diario' => round($consumo_diario, 2),
                'consumo_mensual' => round($consumo_mensual, 2),
                'unidad' => $item['unidad']
            ];
        }
        
        return $consumo_estimado;
    }
}