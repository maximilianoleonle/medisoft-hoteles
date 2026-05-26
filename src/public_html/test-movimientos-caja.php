<?php
// Script de prueba para verificar datos de movimientos_caja
require_once '../../../config/database.php';

// Información del test
echo "<h2>Prueba de Datos - Movimientos de Caja</h2>";

try {
    $db = Database::getInstance();
    
    // 1. Verificar conexión
    echo "<h3>1. Conexión a BD:</h3>";
    echo "✓ Conectado exitosamente<br><br>";
    
    // 2. Verificar estructura de la tabla
    echo "<h3>2. Estructura de la tabla movimientos_caja:</h3>";
    $sql = "DESCRIBE movimientos_caja";
    $stmt = $db->query($sql);
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 3. Verificar datos generales
    echo "<h3>3. Resumen de datos:</h3>";
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN tipo = 'ingreso' THEN 1 ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN 1 ELSE 0 END) as total_gastos,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as suma_ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as suma_gastos
            FROM movimientos_caja";
    
    $stmt = $db->query($sql);
    $resumen = $stmt->fetch();
    
    echo "Total de movimientos: " . $resumen['total'] . "<br>";
    echo "Total ingresos: " . $resumen['total_ingresos'] . " (Suma: $" . number_format($resumen['suma_ingresos'], 2) . ")<br>";
    echo "Total gastos: " . $resumen['total_gastos'] . " (Suma: $" . number_format($resumen['suma_gastos'], 2) . ")<br><br>";
    
    // 4. Verificar usuarios con movimientos
    echo "<h3>4. Usuarios con movimientos:</h3>";
    $sql = "SELECT 
                u.id,
                u.nombre_completo,
                COUNT(mc.id) as total_movimientos,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN mc.tipo = 'gasto' THEN mc.monto ELSE 0 END) as total_gastos
            FROM usuarios u
            LEFT JOIN movimientos_caja mc ON u.id = mc.usuario_id
            WHERE u.activo = 1
            GROUP BY u.id, u.nombre_completo
            ORDER BY total_movimientos DESC";
    
    $stmt = $db->query($sql);
    $usuarios = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Movimientos</th><th>Ingresos</th><th>Gastos</th></tr>";
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nombre_completo']}</td>";
        echo "<td>{$usuario['total_movimientos']}</td>";
        echo "<td>$" . number_format($usuario['total_ingresos'], 2) . "</td>";
        echo "<td>$" . number_format($usuario['total_gastos'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 5. Últimos 10 movimientos
    echo "<h3>5. Últimos 10 movimientos:</h3>";
    $sql = "SELECT 
                mc.*,
                u.nombre_completo as usuario
            FROM movimientos_caja mc
            LEFT JOIN usuarios u ON mc.usuario_id = u.id
            ORDER BY mc.created_at DESC
            LIMIT 10";
    
    $stmt = $db->query($sql);
    $movimientos = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='font-size: 12px;'>";
    echo "<tr><th>ID</th><th>Fecha</th><th>Usuario</th><th>Tipo</th><th>Categoría</th><th>Descripción</th><th>Método</th><th>Monto</th></tr>";
    foreach ($movimientos as $mov) {
        $color = $mov['tipo'] == 'ingreso' ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$mov['id']}</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($mov['created_at'])) . "</td>";
        echo "<td>{$mov['usuario']}</td>";
        echo "<td style='color: $color;'>{$mov['tipo']}</td>";
        echo "<td>{$mov['categoria']}</td>";
        echo "<td>" . substr($mov['descripcion'], 0, 30) . "...</td>";
        echo "<td>{$mov['metodo_pago']}</td>";
        echo "<td style='color: $color;'>$" . number_format($mov['monto'], 2) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 6. Verificar datos del mes actual
    echo "<h3>6. Datos del mes actual:</h3>";
    $fecha_inicio = date('Y-m-01');
    $fecha_fin = date('Y-m-d');
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos
            FROM movimientos_caja 
            WHERE DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
    $mes_actual = $stmt->fetch();
    
    echo "Período: $fecha_inicio al $fecha_fin<br>";
    echo "Total movimientos: " . $mes_actual['total'] . "<br>";
    echo "Ingresos: $" . number_format($mes_actual['ingresos'], 2) . "<br>";
    echo "Gastos: $" . number_format($mes_actual['gastos'], 2) . "<br><br>";
    
    // 7. Prueba de consulta específica por usuario
    echo "<h3>7. Prueba de consulta por usuario (ID=1):</h3>";
    $usuario_id = 1;
    
    $sql = "SELECT 
                mc.*,
                COALESCE(cm.nombre, mc.categoria) as categoria_nombre
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.usuario_id = ? 
            AND DATE(mc.created_at) BETWEEN ? AND ?
            ORDER BY mc.created_at DESC
            LIMIT 5";
    
    $stmt = $db->query($sql, [$usuario_id, $fecha_inicio, $fecha_fin]);
    $movimientos_usuario = $stmt->fetchAll();
    
    echo "Movimientos encontrados: " . count($movimientos_usuario) . "<br>";
    if (!empty($movimientos_usuario)) {
        echo "<pre>";
        print_r($movimientos_usuario[0]); // Mostrar estructura del primer registro
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error:</h3>";
    echo $e->getMessage();
}
