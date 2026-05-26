<?php
/**
 * Script de Corrección de Precios de Reservaciones
 * Los Cedros
 * 
 * Este script corrige las reservaciones que fueron editadas con el bug
 * de duplicación de precios.
 * 
 * USO: Ejecutar desde el navegador o línea de comandos
 * URL: /admin/corregir_precios_reservaciones.php
 */

// Configuración
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

// Variables de control
$modo_prueba = true; // Cambiar a false para ejecutar realmente
$mostrar_detalles = true;

// Obtener conexión a la base de datos
$db = Database::getInstance();

echo "<h1>🔧 Script de Corrección de Precios de Reservaciones</h1>";
echo "<p><strong>Modo:</strong> " . ($modo_prueba ? "PRUEBA (no se guardarán cambios)" : "PRODUCCIÓN (se actualizarán precios)") . "</p>";
echo "<hr>";

// Obtener todas las reservaciones confirmadas o checked_in
$sql = "SELECT r.id, r.precio_total, r.fecha_entrada, r.fecha_salida,
               h.nombre_completo as huesped,
               DATEDIFF(r.fecha_salida, r.fecha_entrada) as noches
        FROM reservaciones r
        INNER JOIN huespedes h ON r.huesped_id = h.id
        WHERE r.estado IN ('confirmada', 'checked_in')
        ORDER BY r.id DESC";

$stmt = $db->query($sql);
$reservaciones = $stmt->fetchAll();

echo "<p>📊 <strong>Total de reservaciones activas:</strong> " . count($reservaciones) . "</p>";
echo "<hr>";

$total_corregidas = 0;
$total_correctas = 0;
$total_diferencia = 0;

foreach ($reservaciones as $reservacion) {
    // Obtener habitaciones de esta reservación
    $sql_habs = "SELECT rh.habitacion_id, rh.es_cortesia, 
                        hab.numero, hab.precio_base
                 FROM reservacion_habitaciones rh
                 INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                 WHERE rh.reservacion_id = ?";
    
    $stmt_habs = $db->query($sql_habs, [$reservacion['id']]);
    $habitaciones = $stmt_habs->fetchAll();
    
    // Calcular el precio CORRECTO
    $precio_correcto = 0;
    $noches = $reservacion['noches'];
    
    foreach ($habitaciones as $hab) {
        // Solo sumar si NO es cortesía
        if ($hab['es_cortesia'] != 1) {
            $precio_correcto += $hab['precio_base'] * $noches;
        }
    }
    
    // Comparar con el precio guardado
    $precio_guardado = $reservacion['precio_total'];
    $diferencia = $precio_guardado - $precio_correcto;
    
    // Si hay diferencia, necesita corrección
    if (abs($diferencia) > 0.01) { // Tolerancia de 1 centavo por redondeo
        $total_corregidas++;
        $total_diferencia += abs($diferencia);
        
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107; border-radius: 4px;'>";
        echo "<h3 style='margin: 0 0 10px 0; color: #856404;'>⚠️ Reservación #{$reservacion['id']} - {$reservacion['huesped']}</h3>";
        
        if ($mostrar_detalles) {
            echo "<div style='font-size: 14px; color: #856404;'>";
            echo "<strong>Fechas:</strong> " . date('d/m/Y', strtotime($reservacion['fecha_entrada'])) . " → " . date('d/m/Y', strtotime($reservacion['fecha_salida'])) . " ({$noches} noches)<br>";
            echo "<strong>Habitaciones:</strong><br>";
            
            foreach ($habitaciones as $hab) {
                $es_cortesia = $hab['es_cortesia'] == 1 ? " <span style='color: #28a745;'>(CORTESÍA)</span>" : "";
                $precio_hab = $hab['precio_base'] * $noches;
                echo "  • Hab. {$hab['numero']}: \${$hab['precio_base']} × {$noches} = \$" . number_format($precio_hab, 2) . "{$es_cortesia}<br>";
            }
            
            echo "</div>";
        }
        
        echo "<div style='margin-top: 10px; font-weight: bold;'>";
        echo "<span style='color: #dc3545;'>❌ Precio guardado: \$" . number_format($precio_guardado, 2) . "</span><br>";
        echo "<span style='color: #28a745;'>✅ Precio correcto: \$" . number_format($precio_correcto, 2) . "</span><br>";
        echo "<span style='color: #ff6b6b;'>📉 Diferencia: -\$" . number_format($diferencia, 2) . "</span>";
        echo "</div>";
        
        if (!$modo_prueba) {
            // Actualizar en la base de datos
            $sql_update = "UPDATE reservaciones SET precio_total = ? WHERE id = ?";
            $db->query($sql_update, [$precio_correcto, $reservacion['id']]);
            echo "<div style='color: #28a745; margin-top: 10px;'>✓ Actualizado en la base de datos</div>";
        } else {
            echo "<div style='color: #007bff; margin-top: 10px;'>ℹ️ No actualizado (modo prueba)</div>";
        }
        
        echo "</div>";
        
    } else {
        $total_correctas++;
    }
}

// Resumen final
echo "<hr>";
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
echo "<h2 style='margin: 0 0 15px 0; color: #155724;'>📊 Resumen de Corrección</h2>";
echo "<div style='font-size: 16px; color: #155724;'>";
echo "<strong>Total reservaciones revisadas:</strong> " . count($reservaciones) . "<br>";
echo "<strong>Reservaciones correctas:</strong> {$total_correctas}<br>";
echo "<strong>Reservaciones corregidas:</strong> <span style='color: #ffc107;'>{$total_corregidas}</span><br>";

if ($total_corregidas > 0) {
    echo "<strong>Total diferencia corregida:</strong> <span style='color: #dc3545;'>-\$" . number_format($total_diferencia, 2) . "</span><br>";
}

echo "</div>";
echo "</div>";

if ($modo_prueba && $total_corregidas > 0) {
    echo "<hr>";
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin-top: 20px; border: 2px solid #ffc107;'>";
    echo "<h3 style='margin: 0 0 10px 0; color: #856404;'>⚠️ IMPORTANTE</h3>";
    echo "<p style='font-size: 16px; color: #856404; margin: 0;'>";
    echo "Esto es una <strong>PRUEBA</strong>. Para aplicar los cambios realmente:<br>";
    echo "1. Revisa que las correcciones sean correctas<br>";
    echo "2. Cambia <code>\$modo_prueba = false;</code> en la línea 14 del script<br>";
    echo "3. Ejecuta de nuevo este script<br>";
    echo "4. Haz un respaldo de la base de datos antes (por seguridad)";
    echo "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #6c757d; font-size: 12px;'>Script de Corrección de Precios - Los Cedros - " . date('d/m/Y H:i:s') . "</p>";
