<?php
/**
 * DIAGNÓSTICO SIMPLE: Detectar reservaciones problemáticas
 * Subir a: public_html/diagnostico_simple.php
 */

// Cargar solo lo necesario
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/core/Database.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Reservaciones - Los Cedros</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #6B4423; }
        h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #6B4423; padding-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #6B4423; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .error { background-color: #fee !important; }
        .warning { background-color: #ffa !important; }
        .ok { background-color: #efe !important; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-error { background: #f44; color: white; }
        .badge-warning { background: #fa0; color: black; }
        .badge-ok { background: #4a4; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico de Reservaciones</h1>
    <p><strong>Fecha:</strong> <?= date('d/m/Y H:i:s') ?></p>

<?php
try {
    $db = Database::getInstance();
    
    // ==========================================
    // 1. Habitaciones con múltiples reservaciones ACTIVAS
    // ==========================================
    echo "<h2>1️⃣ Habitaciones con Múltiples Reservaciones Activas</h2>";
    
    $sql = "SELECT 
            h.numero,
            h.estado as estado_bd,
            COUNT(DISTINCT r.id) as total_reservaciones,
            GROUP_CONCAT(
                CONCAT('R', r.id, ': ', hu.nombre_completo, ' (', r.estado, ')')
                SEPARATOR '<br>'
            ) as detalles
        FROM habitaciones h
        INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
        INNER JOIN reservaciones r ON rh.reservacion_id = r.id
        INNER JOIN huespedes hu ON r.huesped_id = hu.id
        WHERE r.estado IN ('confirmada', 'checked_in')
        AND CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida
        GROUP BY h.id
        HAVING COUNT(DISTINCT r.id) > 1";
    
    $stmt = $db->query($sql);
    $duplicadas = $stmt->fetchAll();
    
    if (count($duplicadas) > 0) {
        echo "<p class='error'>⚠️ <strong>" . count($duplicadas) . " habitaciones con problema</strong></p>";
        echo "<table>";
        echo "<tr><th>Habitación</th><th>Estado en BD</th><th>Total Reservaciones</th><th>Detalles</th></tr>";
        foreach ($duplicadas as $d) {
            echo "<tr class='error'>";
            echo "<td><strong>" . $d['numero'] . "</strong></td>";
            echo "<td>" . $d['estado_bd'] . "</td>";
            echo "<td><span class='badge badge-error'>" . $d['total_reservaciones'] . "</span></td>";
            echo "<td>" . $d['detalles'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='ok'>✅ No hay habitaciones con reservaciones duplicadas</p>";
    }
    
    // ==========================================
    // 2. Reservaciones confirmadas antiguas
    // ==========================================
    echo "<h2>2️⃣ Reservaciones Sin Check-in (Pendientes)</h2>";
    
    $sql = "SELECT 
            r.id,
            h.numero,
            hu.nombre_completo,
            r.fecha_entrada,
            DATEDIFF(CURDATE(), r.fecha_entrada) as dias_atraso
        FROM reservaciones r
        INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
        INNER JOIN habitaciones h ON rh.habitacion_id = h.id
        INNER JOIN huespedes hu ON r.huesped_id = hu.id
        WHERE r.estado = 'confirmada'
        AND r.fecha_entrada < CURDATE()
        ORDER BY dias_atraso DESC
        LIMIT 20";
    
    $stmt = $db->query($sql);
    $pendientes = $stmt->fetchAll();
    
    if (count($pendientes) > 0) {
        echo "<p class='warning'>⚠️ <strong>" . count($pendientes) . " reservaciones sin check-in</strong></p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Habitación</th><th>Huésped</th><th>Fecha Entrada</th><th>Días de Atraso</th></tr>";
        foreach ($pendientes as $p) {
            $class = $p['dias_atraso'] > 3 ? 'error' : 'warning';
            echo "<tr class='$class'>";
            echo "<td>R" . $p['id'] . "</td>";
            echo "<td>" . $p['numero'] . "</td>";
            echo "<td>" . $p['nombre_completo'] . "</td>";
            echo "<td>" . $p['fecha_entrada'] . "</td>";
            echo "<td><span class='badge badge-warning'>" . $p['dias_atraso'] . " días</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='ok'>✅ No hay reservaciones pendientes de check-in</p>";
    }
    
    // ==========================================
    // 3. Estados inconsistentes
    // ==========================================
    echo "<h2>3️⃣ Inconsistencias de Estado</h2>";
    
    $sql = "SELECT 
            h.numero,
            h.estado as estado_habitacion,
            r.id as reservacion_id,
            r.estado as estado_reservacion,
            hu.nombre_completo
        FROM habitaciones h
        INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
        INNER JOIN reservaciones r ON rh.reservacion_id = r.id
        INNER JOIN huespedes hu ON r.huesped_id = hu.id
        WHERE h.estado != 'ocupada'
        AND r.estado = 'checked_in'
        AND CURDATE() BETWEEN r.fecha_entrada AND r.fecha_salida";
    
    $stmt = $db->query($sql);
    $inconsistentes = $stmt->fetchAll();
    
    if (count($inconsistentes) > 0) {
        echo "<p class='error'>❌ <strong>" . count($inconsistentes) . " habitaciones con estado incorrecto</strong></p>";
        echo "<table>";
        echo "<tr><th>Habitación</th><th>Estado BD</th><th>Reservación</th><th>Estado Reserv.</th><th>Huésped</th></tr>";
        foreach ($inconsistentes as $i) {
            echo "<tr class='error'>";
            echo "<td><strong>" . $i['numero'] . "</strong></td>";
            echo "<td>" . $i['estado_habitacion'] . "</td>";
            echo "<td>R" . $i['reservacion_id'] . "</td>";
            echo "<td>checked_in</td>";
            echo "<td>" . $i['nombre_completo'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Solución:</strong> Estas habitaciones deberían estar en estado 'ocupada'</p>";
    } else {
        echo "<p class='ok'>✅ No hay inconsistencias de estado</p>";
    }
    
    // ==========================================
    // RESUMEN
    // ==========================================
    $total_problemas = count($duplicadas) + count($inconsistentes);
    
    echo "<h2>📊 Resumen</h2>";
    echo "<div style='background: #f9f9f9; padding: 20px; border-radius: 8px; border-left: 4px solid #6B4423;'>";
    
    if ($total_problemas == 0) {
        echo "<p style='font-size: 18px; color: #4a4;'><strong>✅ Sistema Saludable</strong></p>";
        echo "<p>No se detectaron problemas críticos en las reservaciones.</p>";
    } else {
        echo "<p style='font-size: 18px; color: #f44;'><strong>⚠️ Se Detectaron Problemas</strong></p>";
        echo "<ul style='font-size: 16px;'>";
        if (count($duplicadas) > 0) {
            echo "<li>❌ " . count($duplicadas) . " habitaciones con reservaciones duplicadas</li>";
        }
        if (count($inconsistentes) > 0) {
            echo "<li>❌ " . count($inconsistentes) . " habitaciones con estado incorrecto</li>";
        }
        if (count($pendientes) > 0) {
            echo "<li>⚠️ " . count($pendientes) . " reservaciones pendientes de check-in</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error' style='padding: 20px; border-radius: 5px;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

</div>
</body>
</html>