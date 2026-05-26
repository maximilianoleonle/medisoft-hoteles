<?php
/**
 * Método de debug con logging detallado para detectar problemas en el PDF
 * Agregar este método a tu InventarioController.php
 */
public function debugPdfAction() {
    echo "<h1>Debug Sistema de Inventario - PDF</h1>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #333; color: white; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    </style>";
    
    $db = Database::getInstance();
    
    // 1. Verificar conexión a BD
    echo "<div class='debug-section'>";
    echo "<h2>1. Conexión a Base de Datos</h2>";
    try {
        $test = $db->query("SELECT 1");
        echo "<p class='success'>✓ Conexión exitosa</p>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
        return;
    }
    echo "</div>";
    
    // 2. Verificar tablas necesarias
    echo "<div class='debug-section'>";
    echo "<h2>2. Verificación de Tablas</h2>";
    $tablas = [
        'movimientos_inventario',
        'inventario_productos',
        'inventario_categorias',
        'habitaciones',
        'usuarios'
    ];
    
    foreach ($tablas as $tabla) {
        try {
            $sql = "SELECT COUNT(*) as total FROM $tabla";
            $stmt = $db->query($sql);
            $result = $stmt->fetch();
            echo "<p class='success'>✓ Tabla '$tabla': {$result['total']} registros</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error en tabla '$tabla': " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // 3. Analizar estructura de movimientos_inventario
    echo "<div class='debug-section'>";
    echo "<h2>3. Estructura de movimientos_inventario</h2>";
    try {
        $sql = "DESCRIBE movimientos_inventario";
        $stmt = $db->query($sql);
        $columns = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 4. Analizar fechas en movimientos
    echo "<div class='debug-section'>";
    echo "<h2>4. Análisis de Fechas en Movimientos</h2>";
    try {
        // Estadísticas de fechas
        $sql = "SELECT 
                MIN(created_at) as fecha_min,
                MAX(created_at) as fecha_max,
                COUNT(*) as total,
                COUNT(DISTINCT DATE(created_at)) as dias_distintos,
                COUNT(CASE WHEN created_at IS NULL THEN 1 END) as fechas_nulas
                FROM movimientos_inventario";
        $stmt = $db->query($sql);
        $stats = $stmt->fetch();
        
        echo "<p><strong>Fecha mínima:</strong> " . ($stats['fecha_min'] ?? 'N/A') . "</p>";
        echo "<p><strong>Fecha máxima:</strong> " . ($stats['fecha_max'] ?? 'N/A') . "</p>";
        echo "<p><strong>Total movimientos:</strong> " . $stats['total'] . "</p>";
        echo "<p><strong>Días distintos:</strong> " . $stats['dias_distintos'] . "</p>";
        echo "<p><strong>Fechas nulas:</strong> " . $stats['fechas_nulas'] . "</p>";
        
        // Movimientos por día
        $sql = "SELECT DATE(created_at) as fecha, COUNT(*) as total 
                FROM movimientos_inventario 
                GROUP BY DATE(created_at) 
                ORDER BY fecha DESC 
                LIMIT 10";
        $stmt = $db->query($sql);
        $por_dia = $stmt->fetchAll();
        
        echo "<h3>Movimientos por día (últimos 10 días con movimientos):</h3>";
        echo "<table>";
        echo "<tr><th>Fecha</th><th>Total Movimientos</th></tr>";
        foreach ($por_dia as $dia) {
            echo "<tr><td>" . ($dia['fecha'] ?? 'NULL') . "</td><td>{$dia['total']}</td></tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 5. Probar consulta del PDF
    echo "<div class='debug-section'>";
    echo "<h2>5. Prueba de Consulta del PDF</h2>";
    
    // Usar fechas que sabemos que tienen datos
    $fecha_desde = '2025-09-01';
    $fecha_hasta = '2025-09-30';
    
    echo "<p><strong>Probando con fechas:</strong> $fecha_desde a $fecha_hasta</p>";
    
    try {
        $sql = "SELECT m.*, 
                       p.nombre as producto_nombre, 
                       p.codigo as producto_codigo,
                       COALESCE(u.nombre, 'Sistema') as usuario_nombre,
                       h.numero as habitacion_numero
                FROM movimientos_inventario m
                LEFT JOIN inventario_productos p ON m.producto_id = p.id
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE DATE(m.created_at) BETWEEN ? AND ?
                ORDER BY m.created_at DESC
                LIMIT 5";
        
        $stmt = $db->query($sql, [$fecha_desde, $fecha_hasta]);
        $movimientos = $stmt->fetchAll();
        
        echo "<p class='success'>✓ Consulta ejecutada exitosamente</p>";
        echo "<p><strong>Movimientos encontrados:</strong> " . count($movimientos) . "</p>";
        
        if (!empty($movimientos)) {
            echo "<h3>Primeros 5 movimientos:</h3>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th></tr>";
            foreach ($movimientos as $mov) {
                echo "<tr>";
                echo "<td>{$mov['id']}</td>";
                echo "<td>{$mov['created_at']}</td>";
                echo "<td>" . ($mov['producto_nombre'] ?? 'N/A') . "</td>";
                echo "<td>{$mov['tipo_movimiento']}</td>";
                echo "<td>{$mov['cantidad']}</td>";
                echo "<td>" . substr($mov['motivo'] ?? '', 0, 50) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Mostrar SQL completo para debug
        echo "<h3>SQL Ejecutado:</h3>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        echo "<p>Parámetros: [$fecha_desde, $fecha_hasta]</p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error en consulta: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    echo "</div>";
    
    // 6. Verificar productos
    echo "<div class='debug-section'>";
    echo "<h2>6. Verificación de Productos</h2>";
    try {
        $productos = $this->inventarioModel->getAllWithCategory();
        echo "<p class='success'>✓ Productos cargados: " . count($productos) . "</p>";
        
        if (empty($productos)) {
            echo "<p class='warning'>⚠ No hay productos en el sistema</p>";
        } else {
            echo "<table>";
            echo "<tr><th>ID</th><th>Código</th><th>Nombre</th><th>Stock</th><th>Categoría</th></tr>";
            $count = 0;
            foreach ($productos as $prod) {
                if ($count++ >= 5) break;
                echo "<tr>";
                echo "<td>{$prod['id']}</td>";
                echo "<td>{$prod['codigo']}</td>";
                echo "<td>{$prod['nombre']}</td>";
                echo "<td>{$prod['stock_actual']}</td>";
                echo "<td>" . ($prod['categoria_nombre'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<p>... mostrando solo los primeros 5 productos</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 7. Simular generación de PDF
    echo "<div class='debug-section'>";
    echo "<h2>7. Simulación de Generación de PDF</h2>";
    echo "<p>Para generar un PDF de prueba con las fechas correctas:</p>";
    echo "<form method='POST' action='" . url('inventario/generarPdfMovimientos') . "'>";
    echo csrf_field();
    echo "<input type='hidden' name='fecha_desde' value='2025-09-01'>";
    echo "<input type='hidden' name='fecha_hasta' value='2025-09-30'>";
    echo "<button type='submit' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "Generar PDF de Prueba (Septiembre 2025)";
    echo "</button>";
    echo "</form>";
    echo "</div>";
    
    // 8. Logs del sistema
    echo "<div class='debug-section'>";
    echo "<h2>8. Información del Sistema</h2>";
    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
    echo "<p><strong>Fecha/Hora del servidor:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "<p><strong>Usuario actual:</strong> " . ($_SESSION['usuario_nombre'] ?? 'No identificado') . "</p>";
    echo "</div>";
    
    exit();
}

// También agrega este método para generar logs en archivo
public function logInventarioAction() {
    $logFile = APP_PATH . '/logs/inventario_debug_' . date('Y-m-d_H-i-s') . '.log';
    $log = "=== LOG DE DEBUG INVENTARIO ===\n";
    $log .= "Fecha: " . date('Y-m-d H:i:s') . "\n";
    $log .= "Usuario: " . ($_SESSION['usuario_nombre'] ?? 'Sistema') . "\n\n";
    
    $db = Database::getInstance();
    
    // Log de movimientos
    $sql = "SELECT * FROM movimientos_inventario ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->query($sql);
    $movimientos = $stmt->fetchAll();
    
    $log .= "ÚLTIMOS 20 MOVIMIENTOS:\n";
    $log .= str_repeat("-", 80) . "\n";
    foreach ($movimientos as $mov) {
        $log .= sprintf("ID: %d | Fecha: %s | Tipo: %s | Producto: %d | Cantidad: %d\n",
            $mov['id'],
            $mov['created_at'],
            $mov['tipo_movimiento'],
            $mov['producto_id'],
            $mov['cantidad']
        );
    }
    
    // Guardar log
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $log);
    
    echo "Log guardado en: " . $logFile;
    exit();
}
?>