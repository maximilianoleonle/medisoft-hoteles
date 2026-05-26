<?php
/**
 * Modelo de Movimiento de Inventario - VERSIÓN CORREGIDA
 * Los Cedros
 * 
 * Corrige el problema de ordenamiento de movimientos recientes
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class MovimientoInventario extends Model {
    protected $table = 'movimientos_inventario';
    protected $timestamps = true;
    protected $fillable = [
    'producto_id',
    'tipo_movimiento',
    'cantidad',
    'stock_anterior',
    'stock_posterior',
    'motivo',
    'habitacion_id',
    'reservacion_id',
    'usuario_id',
    'hotel_id',
    'created_at'  // ASEGÚRATE DE QUE ESTA LÍNEA ESTÉ AQUÍ
];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function productoPerteneceAlHotel($productoId, $hotelId): bool {
        $result = $this->query(
            "SELECT COUNT(*) AS total
             FROM inventario_productos
             WHERE id = ? AND hotel_id = ?",
            [$productoId, $hotelId]
        );

        return (int)($result[0]['total'] ?? 0) > 0;
    }

    private function habitacionPerteneceAlHotel($habitacionId, $hotelId): bool {
        $result = $this->query(
            "SELECT COUNT(*) AS total
             FROM habitaciones
             WHERE id = ? AND hotel_id = ?",
            [$habitacionId, $hotelId]
        );

        return (int)($result[0]['total'] ?? 0) > 0;
    }

    public function crearMovimientoManual(array $data) {
        $hotelId = $this->hotelIdActual();
        $productoId = (int)($data['producto_id'] ?? 0);
        $habitacionId = $data['habitacion_id'] ?? null;

        if ($productoId <= 0 || !$this->productoPerteneceAlHotel($productoId, $hotelId)) {
            throw new Exception('Producto no encontrado para el hotel actual');
        }

        if ($habitacionId !== null && $habitacionId !== '') {
            $habitacionId = (int)$habitacionId;
            if ($habitacionId <= 0 || !$this->habitacionPerteneceAlHotel($habitacionId, $hotelId)) {
                throw new Exception('Habitacion no encontrada para el hotel actual');
            }
            $data['habitacion_id'] = $habitacionId;
        } else {
            $data['habitacion_id'] = null;
        }

        $data['producto_id'] = $productoId;
        $data['hotel_id'] = $hotelId;

        return parent::create($data);
    }
    
    /**
     * Obtener movimientos con detalles - VERSIÓN CORREGIDA CON ORDENAMIENTO
     */
    public function getMovimientosDetallados($limit = 100) {
    try {
        // Obtener conexión directa a la base de datos
        $db = Database::getInstance();
        $hotelId = $this->hotelIdActual();
        
        // Consulta simplificada que sabemos que funciona
        $sql = "SELECT m.*,
                COALESCE(p.nombre, CONCAT('Producto #', m.producto_id)) as producto_nombre,
                COALESCE(p.codigo, 'N/A') as producto_codigo,
                h.numero as habitacion_numero
                FROM movimientos_inventario m
                LEFT JOIN inventario_productos p
                    ON m.producto_id = p.id AND p.hotel_id = m.hotel_id
                LEFT JOIN habitaciones h
                    ON m.habitacion_id = h.id AND h.hotel_id = m.hotel_id
                WHERE m.hotel_id = ?
                ORDER BY m.id DESC
                LIMIT " . intval($limit);
        
        // Ejecutar directamente con la conexión de base de datos
        $stmt = $db->query($sql, [$hotelId]);
        
        if ($stmt === false) {
            return [];
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar valores por defecto para campos faltantes
        foreach ($results as &$row) {
            $row['usuario_nombre'] = 'Usuario #' . ($row['usuario_id'] ?? 'Sistema');
            $row['habitacion_numero'] = $row['habitacion_numero'] ?: ($row['habitacion_id'] ? 'Hab. ' . $row['habitacion_id'] : null);
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error en getMovimientosDetallados: " . $e->getMessage());
        return [];
    }
}
    
    /**
     * Obtener movimientos por producto
     */
    public function getMovimientosPorProducto($producto_id, $limit = 50) {
        try {
            $hotelId = $this->hotelIdActual();
            $productoId = (int)$producto_id;

            if ($productoId <= 0 || !$this->productoPerteneceAlHotel($productoId, $hotelId)) {
                return [];
            }

            $sql = "SELECT m.*,
                    COALESCE(p.nombre, CONCAT('Producto #', m.producto_id)) as producto_nombre,
                    COALESCE(p.codigo, 'N/A') as producto_codigo,
                    COALESCE(h.numero, m.habitacion_id) as habitacion_numero
                    FROM movimientos_inventario m
                    LEFT JOIN inventario_productos p
                        ON m.producto_id = p.id AND p.hotel_id = m.hotel_id
                    LEFT JOIN habitaciones h
                        ON m.habitacion_id = h.id AND h.hotel_id = m.hotel_id
                    WHERE m.producto_id = ? AND m.hotel_id = ?
                    ORDER BY IFNULL(m.created_at, NOW()) DESC, m.id DESC 
                    LIMIT " . intval($limit);
            
            $results = $this->query($sql, [$productoId, $hotelId]);
            
            // Agregar usuario_nombre si falta
            foreach ($results as &$row) {
                $row['usuario_nombre'] = $row['usuario_id'] ? 'Usuario #' . $row['usuario_id'] : 'Sistema';
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Error en getMovimientosPorProducto: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Método alternativo para obtener movimientos recientes
     * Usa el ID como criterio principal de ordenamiento
     */
    public function getMovimientosRecientes($limit = 20) {
        try {
            $hotelId = $this->hotelIdActual();
            // Ordenar por ID descendente garantiza los más recientes
            $sql = "SELECT m.*, 
                    p.nombre as producto_nombre,
                    p.codigo as producto_codigo,
                    h.numero as habitacion_numero
                    FROM movimientos_inventario m
                    LEFT JOIN inventario_productos p
                        ON m.producto_id = p.id AND p.hotel_id = m.hotel_id
                    LEFT JOIN habitaciones h
                        ON m.habitacion_id = h.id AND h.hotel_id = m.hotel_id
                    WHERE m.hotel_id = ?
                    ORDER BY m.id DESC
                    LIMIT " . intval($limit);
            
            $results = $this->query($sql, [$hotelId]);
            
            if (!empty($results)) {
                foreach ($results as &$row) {
                    $row['usuario_nombre'] = $row['usuario_id'] ? 'Usuario #' . $row['usuario_id'] : 'Sistema';
                    
                    // Validar datos
                    if (empty($row['producto_nombre'])) {
                        $row['producto_nombre'] = 'Producto #' . $row['producto_id'];
                    }
                    if (empty($row['producto_codigo'])) {
                        $row['producto_codigo'] = 'N/A';
                    }
                    if (empty($row['created_at']) || $row['created_at'] == '0000-00-00 00:00:00') {
                        $row['created_at'] = date('Y-m-d H:i:s');
                    }
                }
            }
            
            return $results ?? [];
            
        } catch (Exception $e) {
            error_log("Error en getMovimientosRecientes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Debug: Ver últimos movimientos con información completa
     */
    public function debugMovimientos($limit = 10) {
        try {
            $sql = "SELECT * FROM movimientos_inventario ORDER BY id DESC LIMIT " . intval($limit);
            $results = $this->query($sql);
            
            error_log("=== DEBUG MOVIMIENTOS (últimos $limit) ===");
            foreach ($results as $mov) {
                error_log(sprintf(
                    "ID: %d | Tipo: %s | Producto: %d | Cantidad: %d | Fecha: %s | Motivo: %s",
                    $mov['id'],
                    $mov['tipo_movimiento'],
                    $mov['producto_id'],
                    $mov['cantidad'],
                    $mov['created_at'] ?? 'SIN FECHA',
                    substr($mov['motivo'] ?? 'Sin motivo', 0, 50)
                ));
            }
            error_log("=== FIN DEBUG ===");
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Error en debug: " . $e->getMessage());
            return [];
        }
    }
}
