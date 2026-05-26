<?php
// diagnostico_inventario.php
// Ejecuta este script para diagnosticar problemas con el descuento de inventario

require_once 'path/to/Database.php'; // Ajusta la ruta

$db = Database::getInstance();

echo "=== DIAGNÓSTICO DEL SISTEMA DE INVENTARIO ===\n\n";

// 1. Verificar tablas
echo "1. VERIFICANDO TABLAS:\n";
$tablas = ['inventario_productos', 'inventario_config_habitacion', 'inventario_movimientos'];
foreach ($tablas as $tabla) {
    $sql = "SHOW TABLES LIKE '$tabla'";
    $result = $db->query($sql);
    $existe = $result->fetch() ? '✓' : '✗';
    echo "   $existe $tabla\n";
}

// 2. Verificar productos con descuento automático
echo "\n2. PRODUCTOS CON DESCUENTO AUTOMÁTICO:\n";
$sql = "SELECT id, nombre, stock_actual, descuento_automatico 
        FROM inventario_productos 
        WHERE descuento_automatico = 1 AND activo = 1";
$stmt = $db->query($sql);
$productos = $stmt->fetchAll();

if (empty($productos)) {
    echo "   ⚠️ NO HAY PRODUCTOS marcados para descuento automático\n";
} else {
    foreach ($productos as $p) {
        echo "   - {$p['nombre']} (Stock: {$p['stock_actual']})\n";
    }
}

// 3. Verificar configuración por tipo de habitación
echo "\n3. CONFIGURACIÓN POR TIPO DE HABITACIÓN:\n";
$sql = "SELECT 
            ich.tipo_habitacion,
            COUNT(*) as productos_configurados,
            GROUP_CONCAT(
                CONCAT(p.nombre, ' (', ich.cantidad_descontar, ')') 
                SEPARATOR ', '
            ) as detalles
        FROM inventario_config_habitacion ich
        JOIN inventario_productos p ON ich.producto_id = p.id
        WHERE ich.activo = 1
        GROUP BY ich.tipo_habitacion";
$stmt = $db->query($sql);
$configs = $stmt->fetchAll();

if (empty($configs)) {
    echo "   ⚠️ NO HAY CONFIGURACIÓN activa\n";
    echo "   Debes ir a /inventario/configuracion y configurar las cantidades\n";
} else {
    foreach ($configs as $c) {
        echo "   {$c['tipo_habitacion']}: {$c['detalles']}\n";
    }
}

// 4. Verificar tipos de habitación en el sistema
echo "\n4. TIPOS DE HABITACIÓN EN EL SISTEMA:\n";
$sql = "SELECT DISTINCT tipo, COUNT(*) as cantidad 
        FROM habitaciones 
        WHERE activa = 1 
        GROUP BY tipo";
$stmt = $db->query($sql);
$tipos = $stmt->fetchAll();

foreach ($tipos as $t) {
    echo "   - {$t['tipo']}: {$t['cantidad']} habitaciones\n";
}

// 5. Verificar última reservación con check-in
echo "\n5. ÚLTIMA RESERVACIÓN CON CHECK-IN:\n";
$sql = "SELECT r.id, r.created_at, r.updated_at, r.estado,
               COUNT(DISTINCT im.id) as movimientos_inventario
        FROM reservaciones r
        LEFT JOIN inventario_movimientos im ON im.reservacion_id = r.id
        WHERE r.estado = 'checked_in'
        GROUP BY r.id
        ORDER BY r.updated_at DESC
        LIMIT 5";
$stmt = $db->query($sql);
$reservaciones = $stmt->fetchAll();

if (empty($reservaciones)) {
    echo "   No hay reservaciones con check-in reciente\n";
} else {
    foreach ($reservaciones as $r) {
        echo "   Reservación #{$r['id']} - Check-in: {$r['updated_at']}\n";
        echo "   Movimientos de inventario: {$r['movimientos_inventario']}\n\n";
    }
}

// 6. Verificar archivos del sistema
echo "\n6. VERIFICANDO ARCHIVOS:\n";
$archivos = [
    'services/InventarioService.php',
    'models/Inventario.php',
    'models/MovimientoInventario.php',
    'controllers/InventarioController.php',
    'controllers/ReservacionController.php'
];

foreach ($archivos as $archivo) {
    $rutas = [
        __DIR__ . '/' . $archivo,
        __DIR__ . '/../' . $archivo,
        __DIR__ . '/../../' . $archivo,
        $_SERVER['DOCUMENT_ROOT'] . '/' . $archivo
    ];
    
    $encontrado = false;
    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            echo "   ✓ $archivo encontrado\n";
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado) {
        echo "   ✗ $archivo NO ENCONTRADO\n";
    }
}

// 7. Recomendaciones
echo "\n7. RECOMENDACIONES:\n";
if (empty($productos)) {
    echo "   1. Marca algunos productos con descuento_automatico = 1\n";
}
if (empty($configs)) {
    echo "   2. Ve a /inventario/configuracion y configura las cantidades\n";
}
echo "   3. Verifica los logs del servidor para más detalles\n";
echo "   4. Asegúrate de que InventarioService.php esté en la carpeta services/\n";

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
