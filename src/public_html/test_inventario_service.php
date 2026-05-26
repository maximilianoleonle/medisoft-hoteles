<?php
// test_inventario_service.php
// Ejecuta este archivo para verificar que el servicio funcione correctamente

require_once 'path/to/Database.php'; // Ajusta la ruta
require_once 'path/to/InventarioService.php'; // Ajusta la ruta

$db = Database::getInstance();
$inventarioService = new InventarioService($db);

// 1. Probar configuración para habitación sencilla
echo "=== TEST 1: Verificar configuración ===\n";
$sql = "SELECT DISTINCT tipo FROM habitaciones WHERE activa = 1";
$stmt = $db->query($sql);
$tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tipos as $tipo) {
    echo "\nTipo de habitación: $tipo\n";
    
    $sql = "SELECT 
                ich.producto_id,
                ich.cantidad_descontar,
                ip.nombre,
                ip.stock_actual,
                ip.descuento_automatico
            FROM inventario_config_habitacion ich
            JOIN inventario_productos ip ON ich.producto_id = ip.id
            WHERE ich.tipo_habitacion = ?
            AND ich.activo = 1";
    
    $stmt = $db->query($sql, [$tipo]);
    $configs = $stmt->fetchAll();
    
    if (empty($configs)) {
        echo "  ⚠️ NO HAY CONFIGURACIÓN para este tipo\n";
    } else {
        foreach ($configs as $config) {
            echo "  - {$config['nombre']}: {$config['cantidad_descontar']} unidades";
            echo " (Stock actual: {$config['stock_actual']})\n";
        }
    }
}

// 2. Probar descuento para una habitación específica
echo "\n=== TEST 2: Simular descuento ===\n";
$habitacion_id = 1; // Cambia esto por un ID real de habitación
$reservacion_id = 999999; // ID de prueba

$resultado = $inventarioService->descontarInventarioCheckIn($habitacion_id, $reservacion_id);

echo "Resultado: ";
print_r($resultado);

// 3. Verificar movimientos registrados
if ($resultado['success'] && !empty($resultado['productos_descontados'])) {
    echo "\n=== TEST 3: Verificar movimientos ===\n";
    $sql = "SELECT * FROM inventario_movimientos 
            WHERE reservacion_id = ? 
            ORDER BY created_at DESC";
    $stmt = $db->query($sql, [$reservacion_id]);
    $movimientos = $stmt->fetchAll();
    
    echo "Movimientos registrados: " . count($movimientos) . "\n";
    foreach ($movimientos as $mov) {
        echo "- Producto ID: {$mov['producto_id']}, ";
        echo "Cantidad: {$mov['cantidad']}, ";
        echo "Stock anterior: {$mov['stock_anterior']} → {$mov['stock_posterior']}\n";
    }
    
    // Limpiar datos de prueba
    echo "\nLimpiando datos de prueba...\n";
    $db->query("DELETE FROM inventario_movimientos WHERE reservacion_id = ?", [$reservacion_id]);
    
    // Restaurar stock
    foreach ($resultado['productos_descontados'] as $prod) {
        $sql = "UPDATE inventario_productos 
                SET stock_actual = stock_actual + ? 
                WHERE id = ?";
        $db->query($sql, [$prod['cantidad'], $prod['producto_id']]);
    }
}