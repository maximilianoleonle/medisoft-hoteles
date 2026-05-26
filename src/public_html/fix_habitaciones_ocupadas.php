<?php
/**
 * Script para arreglar y sincronizar estados de habitaciones
 * Los Cedros
 * 
 * INSTRUCCIONES:
 * 1. Sube este archivo a tu servidor en la carpeta raíz de tu proyecto
 * 2. Ejecuta desde el navegador: http://tudominio.com/fix_habitaciones_ocupadas.php
 * 3. Verifica los resultados
 * 4. ELIMINA este archivo después de ejecutarlo
 */

// Configuración de la base de datos
// IMPORTANTE: Ajusta estos valores según tu configuración
require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

echo "<h1>Fix de Estados de Habitaciones</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; margin: 10px 0; border-radius: 5px; }
    table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background: #6B4423; color: white; }
    tr:nth-child(even) { background: #f9f9f9; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    .badge-error { background: #dc3545; color: white; }
    .badge-success { background: #28a745; color: white; }
</style>";

try {
    $pdo->beginTransaction();
    
    // ============================================================
    // PASO 1: Identificar habitaciones con estado incorrecto
    // ============================================================
    echo "<div class='info'>";
    echo "<h2>🔍 Paso 1: Identificando habitaciones con estado incorrecto...</h2>";
    
    $sql = "SELECT 
                h.id,
                h.numero,
                h.estado as estado_habitacion,
                r.id as reservacion_id,
                r.estado as estado_reservacion,
                r.fecha_entrada,
                r.fecha_salida,
                CONCAT(COALESCE(hu.nombre, ''), ' ', COALESCE(hu.apellidos, '')) as nombre_completo
            FROM habitaciones h
            INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id
            LEFT JOIN huespedes hu ON r.huesped_id = hu.id
            WHERE r.estado = 'checked_in'
            AND h.estado != 'ocupada'
            ORDER BY h.numero";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $habitaciones_incorrectas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($habitaciones_incorrectas)) {
        echo "<p>✅ <strong>¡Excelente!</strong> No se encontraron habitaciones con estado incorrecto.</p>";
        echo "</div>";
    } else {
        $count = count($habitaciones_incorrectas);
        echo "<p>⚠️ Se encontraron <strong>$count habitación(es)</strong> con estado incorrecto:</p>";
        echo "</div>";
        
        echo "<table>";
        echo "<tr>
                <th>Habitación</th>
                <th>Estado Actual</th>
                <th>Debería ser</th>
                <th>Reservación ID</th>
                <th>Huésped</th>
                <th>Check-in</th>
                <th>Check-out</th>
              </tr>";
        
        foreach ($habitaciones_incorrectas as $hab) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($hab['numero']) . "</strong></td>";
            echo "<td><span class='badge badge-error'>" . htmlspecialchars($hab['estado_habitacion']) . "</span></td>";
            echo "<td><span class='badge badge-success'>ocupada</span></td>";
            echo "<td>#" . $hab['reservacion_id'] . "</td>";
            echo "<td>" . htmlspecialchars($hab['nombre_completo']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($hab['fecha_entrada'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($hab['fecha_salida'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // ============================================================
        // PASO 2: Corregir los estados
        // ============================================================
        echo "<div class='info'>";
        echo "<h2>🔧 Paso 2: Corrigiendo estados de habitaciones...</h2>";
        
        $sql_update = "UPDATE habitaciones h
                      INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                      INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                      SET h.estado = 'ocupada'
                      WHERE r.estado = 'checked_in'
                      AND h.estado != 'ocupada'";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute();
        $actualizadas = $stmt_update->rowCount();
        
        echo "<p>✅ Se actualizaron <strong>$actualizadas habitación(es)</strong> correctamente.</p>";
        echo "</div>";
    }
    
    // ============================================================
    // PASO 3: Verificar que todo quedó bien
    // ============================================================
    echo "<div class='info'>";
    echo "<h2>✓ Paso 3: Verificación final...</h2>";
    
    // Verificar habitaciones con check-in
    $sql_verify = "SELECT COUNT(*) as total
                   FROM habitaciones h
                   INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                   INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                   WHERE r.estado = 'checked_in'
                   AND h.estado != 'ocupada'";
    
    $stmt_verify = $pdo->prepare($sql_verify);
    $stmt_verify->execute();
    $result = $stmt_verify->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] == 0) {
        echo "<p>✅ <strong>¡Perfecto!</strong> Todas las habitaciones con check-in están marcadas como ocupadas.</p>";
        echo "</div>";
        
        // ============================================================
        // PASO 4: Resumen general
        // ============================================================
        echo "<div class='success'>";
        echo "<h2>📊 Resumen de Estados Actuales</h2>";
        
        $sql_resumen = "SELECT 
                          estado,
                          COUNT(*) as cantidad,
                          GROUP_CONCAT(numero ORDER BY numero SEPARATOR ', ') as habitaciones
                        FROM habitaciones
                        GROUP BY estado
                        ORDER BY estado";
        
        $stmt_resumen = $pdo->prepare($sql_resumen);
        $stmt_resumen->execute();
        $resumen = $stmt_resumen->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Estado</th><th>Cantidad</th><th>Habitaciones</th></tr>";
        
        foreach ($resumen as $row) {
            $color_map = [
                'disponible' => '#28a745',
                'ocupada' => '#dc3545',
                'limpieza' => '#007bff',
                'mantenimiento' => '#ffc107',
                'por_llegar' => '#6f42c1'
            ];
            
            $color = $color_map[$row['estado']] ?? '#6c757d';
            
            echo "<tr>";
            echo "<td><span class='badge' style='background: $color; color: white;'>" . ucfirst($row['estado']) . "</span></td>";
            echo "<td><strong>" . $row['cantidad'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['habitaciones']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Confirmar transacción
        $pdo->commit();
        
        echo "<div class='success'>";
        echo "<h2>🎉 ¡Proceso Completado Exitosamente!</h2>";
        echo "<p><strong>Próximos pasos:</strong></p>";
        echo "<ol>";
        echo "<li>Verifica que las habitaciones 04 y 08 ahora aparezcan como ocupadas en tu sistema</li>";
        echo "<li>Elimina este archivo (fix_habitaciones_ocupadas.php) por seguridad</li>";
        echo "<li>Si el problema vuelve a ocurrir, revisa los logs del servidor para identificar errores</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        throw new Exception("ERROR: Aún quedan " . $result['total'] . " habitación(es) con estado incorrecto después del fix.");
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<div class='error'>";
    echo "<h2>❌ Error durante el proceso</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>No se realizaron cambios en la base de datos.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<div class='info'>";
echo "<p><strong>Fecha de ejecución:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Elimina este archivo después de ejecutarlo.</p>";
echo "</div>";
?>
