<?php
/**
 * LIMPIEZA: Cancelar reservaciones antiguas sin check-in
 * Subir a: public_html/limpiar_reservaciones_antiguas.php
 * 
 * IMPORTANTE: Este script SOLO muestra qué se haría.
 * Para ejecutar realmente, quita el comentario de las líneas marcadas.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/core/Database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Limpieza de Reservaciones - Los Cedros</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #6B4423; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #6B4423; color: white; }
        .warning { background: #ffa; padding: 15px; border-left: 4px solid #fa0; margin: 20px 0; }
        .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #6B4423; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #8B5A2B; }
        .btn-danger { background: #f44; }
        .btn-danger:hover { background: #c33; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧹 Limpieza de Reservaciones Antiguas</h1>

<?php
try {
    $db = Database::getInstance();
    $modo = $_GET['modo'] ?? 'vista';
    $dias_limite = intval($_GET['dias'] ?? 30);
    
    echo "<div class='info'>";
    echo "<strong>Modo actual:</strong> " . ($modo == 'ejecutar' ? 'EJECUTAR' : 'VISTA PREVIA');
    echo "<br><strong>Criterio:</strong> Reservaciones 'confirmadas' con más de $dias_limite días de atraso";
    echo "</div>";
    
    // Obtener reservaciones candidatas
    $sql = "SELECT 
            r.id,
            h.numero as habitacion,
            hu.nombre_completo,
            r.fecha_entrada,
            r.fecha_salida,
            r.precio_total,
            DATEDIFF(CURDATE(), r.fecha_entrada) as dias_atraso,
            r.created_at
        FROM reservaciones r
        INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
        INNER JOIN habitaciones h ON rh.habitacion_id = h.id
        INNER JOIN huespedes hu ON r.huesped_id = hu.id
        WHERE r.estado = 'confirmada'
        AND DATEDIFF(CURDATE(), r.fecha_entrada) > ?
        ORDER BY r.fecha_entrada ASC";
    
    $stmt = $db->query($sql, [$dias_limite]);
    $reservaciones = $stmt->fetchAll();
    
    if (count($reservaciones) == 0) {
        echo "<p class='info'>✅ No hay reservaciones antiguas para limpiar</p>";
    } else {
        echo "<h2>📋 Reservaciones Encontradas: " . count($reservaciones) . "</h2>";
        
        if ($modo == 'vista') {
            echo "<div class='warning'>";
            echo "<strong>⚠️ VISTA PREVIA</strong><br>";
            echo "Esto es solo una vista previa. No se ha cancelado ninguna reservación.<br>";
            echo "Para ejecutar la limpieza, haz clic en el botón de abajo.";
            echo "</div>";
        }
        
        echo "<table>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Habitación</th>";
        echo "<th>Huésped</th>";
        echo "<th>Fecha Entrada</th>";
        echo "<th>Días Atraso</th>";
        echo "<th>Precio</th>";
        echo "<th>Estado Actual</th>";
        echo "</tr>";
        
        $total_recuperado = 0;
        
        foreach ($reservaciones as $r) {
            echo "<tr>";
            echo "<td>R" . $r['id'] . "</td>";
            echo "<td>" . htmlspecialchars($r['habitacion']) . "</td>";
            echo "<td>" . htmlspecialchars($r['nombre_completo']) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($r['fecha_entrada'])) . "</td>";
            echo "<td><strong>" . $r['dias_atraso'] . " días</strong></td>";
            echo "<td>$" . number_format($r['precio_total'], 2) . "</td>";
            echo "<td>" . ($modo == 'ejecutar' ? '<span style="color: #f44;">Cancelada ✓</span>' : 'confirmada') . "</td>";
            echo "</tr>";
            
            $total_recuperado += $r['precio_total'];
        }
        
        echo "</table>";
        
        echo "<div class='info'>";
        echo "<strong>Total de reservaciones:</strong> " . count($reservaciones) . "<br>";
        echo "<strong>Habitaciones que se liberarán:</strong> " . count($reservaciones) . "<br>";
        echo "<strong>Monto total (no cobrado):</strong> $" . number_format($total_recuperado, 2);
        echo "</div>";
        
        if ($modo == 'ejecutar') {
            // EJECUTAR LA LIMPIEZA
            $canceladas = 0;
            $errores = 0;
            
            foreach ($reservaciones as $r) {
                try {
                    // Cancelar la reservación
                    $sql_update = "UPDATE reservaciones 
                                  SET estado = 'cancelada',
                                      notas = CONCAT(COALESCE(notas, ''), '\n\n[AUTO-CANCELADA el " . date('Y-m-d H:i:s') . "] Reservación antigua sin check-in (+" . $r['dias_atraso'] . " días)')
                                  WHERE id = ?";
                    
                    $db->query($sql_update, [$r['id']]);
                    $canceladas++;
                    
                } catch (Exception $e) {
                    $errores++;
                    error_log("Error al cancelar reservación R" . $r['id'] . ": " . $e->getMessage());
                }
            }
            
            echo "<div style='background: #4a4; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h2>✅ Limpieza Completada</h2>";
            echo "<p><strong>Reservaciones canceladas:</strong> $canceladas</p>";
            if ($errores > 0) {
                echo "<p><strong>Errores:</strong> $errores</p>";
            }
            echo "</div>";
            
            echo "<a href='limpiar_reservaciones_antiguas.php' class='btn'>Volver a Vista Previa</a>";
            
        } else {
            // BOTONES DE ACCIÓN
            echo "<div style='margin: 30px 0; text-align: center;'>";
            echo "<a href='?modo=vista&dias=30' class='btn'>30+ días</a>";
            echo "<a href='?modo=vista&dias=60' class='btn'>60+ días</a>";
            echo "<a href='?modo=vista&dias=90' class='btn'>90+ días</a>";
            echo "<br><br>";
            echo "<a href='?modo=ejecutar&dias=$dias_limite' class='btn btn-danger' onclick='return confirm(\"⚠️ ¿Seguro que quieres cancelar " . count($reservaciones) . " reservaciones?\")'>🗑️ EJECUTAR LIMPIEZA</a>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #fee; padding: 20px; border-radius: 5px;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

    <hr style="margin: 40px 0;">
    <p style="text-align: center; color: #666;">
        <small>
            <strong>Nota:</strong> Las reservaciones canceladas NO se eliminan de la base de datos,
            solo cambian su estado a "cancelada".<br>
            Esto permite mantener el historial completo.
        </small>
    </p>

</div>
</body>
</html>
