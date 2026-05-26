<?php
/**
 * Servicio de Inventario para Check-in/Check-out
 * Los Cedros
 * VERSIÓN CORREGIDA
 */
require_once __DIR__ . '/../helpers/hotel_config.php';

class InventarioService {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }
    
    /**
     * Procesar descuento automático de inventario al hacer check-in
     */
    public function descontarInventarioCheckIn($habitacion_id, $reservacion_id) {
        try {
            // Obtener el tipo de habitación
            $hotel_id = $this->hotelIdActual();

            $sql = "SELECT tipo, hotel_id FROM habitaciones WHERE id = ? AND hotel_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$habitacion_id, $hotel_id]);
            $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$habitacion) {
                return [
                    'success' => false,
                    'error' => 'Habitación no encontrada'
                ];
            }
            
            $tipo_habitacion = $habitacion['tipo'];
            
            // Obtener configuración de productos para este tipo de habitación
            $sql = "SELECT 
                        ich.producto_id,
                        ich.cantidad_descontar,
                        ip.nombre as producto_nombre,
                        ip.codigo,
                        ip.stock_actual,
                        ip.unidad_medida,
                        ip.hotel_id as producto_hotel_id,
                        ich.hotel_id as configuracion_hotel_id
                    FROM inventario_config_habitacion ich
                    JOIN inventario_productos ip
                        ON ich.producto_id = ip.id
                        AND ip.hotel_id = ich.hotel_id
                    WHERE ich.tipo_habitacion = ?
                    AND ich.hotel_id = ?
                    AND ich.activo = 1
                    AND ich.cantidad_descontar > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipo_habitacion, $hotel_id]);
            $productos_config = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($productos_config)) {
                return [
                    'success' => true,
                    'productos_descontados' => [],
                    'mensaje' => 'No hay productos configurados para descuento automático'
                ];
            }
            
            $productos_descontados = [];
            $productos_sin_stock = [];
            
            // Procesar cada producto
            foreach ($productos_config as $config) {
                $cantidad_descontar = intval($config['cantidad_descontar']);
                $stock_actual = intval($config['stock_actual']);

                if ((int)$config['producto_hotel_id'] !== $hotel_id || (int)$config['configuracion_hotel_id'] !== $hotel_id) {
                    throw new Exception('Producto o configuracion de inventario fuera del hotel actual');
                }
                
                if ($stock_actual >= $cantidad_descontar) {
                    // Actualizar stock
                    $nuevo_stock = $stock_actual - $cantidad_descontar;
                    
                    $sql = "UPDATE inventario_productos 
                            SET stock_actual = ? 
                            WHERE id = ? AND hotel_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$nuevo_stock, $config['producto_id'], $hotel_id]);

                    if ($stmt->rowCount() === 0) {
                        throw new Exception('No se pudo actualizar el stock del producto en el hotel actual');
                    }
                    
                    // CORREGIDO: Usar el nombre correcto de la tabla
                    $sql = "INSERT INTO movimientos_inventario 
                            (hotel_id, producto_id, tipo_movimiento, cantidad, stock_anterior,
                             stock_posterior, motivo, habitacion_id, reservacion_id, 
                             usuario_id, created_at)
                            VALUES (?, ?, 'SALIDA', ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
                    $motivo = "Check-in automático - Habitación {$habitacion_id}";
                    
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([
                        $hotel_id,
                        $config['producto_id'],
                        $cantidad_descontar,
                        $stock_actual,
                        $nuevo_stock,
                        $motivo,
                        $habitacion_id,
                        $reservacion_id,
                        $usuario_id
                    ]);
                    
                    // Agregar log para debug
                    error_log("Movimiento registrado - Producto: {$config['producto_nombre']}, Cantidad: {$cantidad_descontar}");
                    
                    $productos_descontados[] = [
                        'producto_id' => $config['producto_id'],
                        'nombre' => $config['producto_nombre'],
                        'cantidad' => $cantidad_descontar,
                        'unidad' => $config['unidad_medida'],
                        'stock_anterior' => $stock_actual,
                        'stock_nuevo' => $nuevo_stock
                    ];
                } else {
                    // No hay stock suficiente
                    $productos_sin_stock[] = [
                        'producto_id' => $config['producto_id'],
                        'nombre' => $config['producto_nombre'],
                        'cantidad_requerida' => $cantidad_descontar,
                        'stock_actual' => $stock_actual
                    ];
                }
            }
            
            return [
                'success' => true,
                'productos_descontados' => $productos_descontados,
                'productos_sin_stock' => $productos_sin_stock,
                'total_descontado' => count($productos_descontados)
            ];
            
        } catch (Exception $e) {
            error_log("Error en descontarInventarioCheckIn: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar disponibilidad de inventario antes del check-in
     */
    public function verificarDisponibilidad($habitacion_id) {
        try {
            // Obtener el tipo de habitación
            $hotel_id = $this->hotelIdActual();

            $sql = "SELECT tipo, hotel_id FROM habitaciones WHERE id = ? AND hotel_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$habitacion_id, $hotel_id]);
            $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$habitacion) {
                return [
                    'disponible' => false,
                    'mensaje' => 'Habitación no encontrada'
                ];
            }
            
            // Verificar productos configurados
            $sql = "SELECT 
                        ich.producto_id,
                        ich.cantidad_descontar,
                        ip.nombre,
                        ip.stock_actual,
                        ip.hotel_id as producto_hotel_id,
                        ich.hotel_id as configuracion_hotel_id
                    FROM inventario_config_habitacion ich
                    JOIN inventario_productos ip
                        ON ich.producto_id = ip.id
                        AND ip.hotel_id = ich.hotel_id
                    WHERE ich.tipo_habitacion = ?
                    AND ich.hotel_id = ?
                    AND ich.activo = 1
                    AND ich.cantidad_descontar > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$habitacion['tipo'], $hotel_id]);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $productos_insuficientes = [];
            
            foreach ($productos as $producto) {
                if ((int)$producto['producto_hotel_id'] !== $hotel_id || (int)$producto['configuracion_hotel_id'] !== $hotel_id) {
                    return [
                        'disponible' => false,
                        'mensaje' => 'Configuracion de inventario fuera del hotel actual'
                    ];
                }

                if ($producto['stock_actual'] < $producto['cantidad_descontar']) {
                    $productos_insuficientes[] = [
                        'nombre' => $producto['nombre'],
                        'requerido' => $producto['cantidad_descontar'],
                        'disponible' => $producto['stock_actual']
                    ];
                }
            }
            
            if (!empty($productos_insuficientes)) {
                return [
                    'disponible' => false,
                    'productos_insuficientes' => $productos_insuficientes,
                    'mensaje' => 'Stock insuficiente para algunos productos'
                ];
            }
            
            return [
                'disponible' => true,
                'mensaje' => 'Stock disponible'
            ];
            
        } catch (Exception $e) {
            error_log("Error en verificarDisponibilidad: " . $e->getMessage());
            return [
                'disponible' => false,
                'mensaje' => 'Error al verificar disponibilidad'
            ];
        }
    }
    
    /**
     * Método de debug para verificar que se están creando movimientos
     */
    public function debugUltimosMovimientos() {
        try {
            $sql = "SELECT COUNT(*) as total FROM movimientos_inventario";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("=== DEBUG INVENTARIO SERVICE ===");
            error_log("Total movimientos en BD: " . $count['total']);
            
            $sql = "SELECT * FROM movimientos_inventario ORDER BY id DESC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($movimientos as $mov) {
                error_log("Movimiento ID: {$mov['id']}, Tipo: {$mov['tipo_movimiento']}, Producto: {$mov['producto_id']}, Fecha: {$mov['created_at']}");
            }
            error_log("=== FIN DEBUG ===");
            
        } catch (Exception $e) {
            error_log("Error en debug: " . $e->getMessage());
        }
    }
}
