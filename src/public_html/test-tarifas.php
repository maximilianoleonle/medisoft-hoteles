<?php
/**
 * Script de prueba para verificar tarifas dinámicas
 * Coloca este archivo en la raíz de tu proyecto y accede a él desde el navegador
 */

// Incluir archivos necesarios
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/IncrementoTarifa.php';
require_once __DIR__ . '/app/models/Habitacion.php';

// Verificar autenticación (opcional, comenta si quieres probar sin login)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Debes iniciar sesión para ejecutar esta prueba");
}

// Inicializar modelos
$tarifaModel = new IncrementoTarifa();
$habitacionModel = new Habitacion();

// Fecha de prueba
$fecha_prueba = $_GET['fecha'] ?? date('Y-m-d');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Prueba de Tarifas Dinámicas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .incremento { color: #d9534f; font-weight: bold; }
        .form-group { margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1>Prueba de Tarifas Dinámicas - Los Cedros</h1>
    
    <form method='get'>
        <div class='form-group'>
            <label>Fecha de prueba:</label>
            <input type='date' name='fecha' value='$fecha_prueba'>
            <button type='submit'>Probar</button>
        </div>
    </form>";

// Obtener incrementos activos para la fecha
$incrementos_activos = $tarifaModel->getActivosParaFecha($fecha_prueba);

echo "<h2>Incrementos activos para: " . date('d/m/Y', strtotime($fecha_prueba)) . "</h2>";

if (empty($incrementos_activos)) {
    echo "<p>No hay incrementos activos para esta fecha.</p>";
} else {
    echo "<table>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Valor</th>
            <th>Alcance</th>
            <th>Aplica a</th>
            <th>Prioridad</th>
        </tr>";
    
    foreach ($incrementos_activos as $inc) {
        $tipo_incremento = $inc['tipo_incremento'] == 'porcentaje' ? '%' : '$';
        $aplica_a = '';
        
        if ($inc['alcance'] == 'global') {
            $aplica_a = 'Todas las habitaciones';
        } elseif ($inc['alcance'] == 'tipo_habitacion') {
            $tipos = json_decode($inc['tipos_habitacion'], true);
            $aplica_a = 'Tipos: ' . implode(', ', $tipos);
        } elseif ($inc['alcance'] == 'habitacion') {
            $habs = json_decode($inc['habitaciones'], true);
            $aplica_a = 'Habitaciones: ' . implode(', ', $habs);
        }
        
        echo "<tr>
            <td>{$inc['nombre']}</td>
            <td>{$inc['tipo_incremento']}</td>
            <td>{$inc['valor_incremento']}$tipo_incremento</td>
            <td>{$inc['alcance']}</td>
            <td>$aplica_a</td>
            <td>{$inc['prioridad']}</td>
        </tr>";
    }
    echo "</table>";
}

// Probar precios de habitaciones
$habitaciones = $habitacionModel->where(['activa' => 1]);

echo "<h2>Precios de habitaciones con incrementos aplicados</h2>";
echo "<table>
    <tr>
        <th>Número</th>
        <th>Tipo</th>
        <th>Precio Base</th>
        <th>Incrementos</th>
        <th>Precio Final</th>
        <th>Diferencia</th>
    </tr>";

foreach ($habitaciones as $hab) {
    $calculo = $tarifaModel->calcularPrecioConIncremento(
        $hab['id'],
        $hab['tipo'],
        $hab['precio_base'],
        $fecha_prueba
    );
    
    $incrementos_detalle = [];
    foreach ($calculo['incrementos_aplicados'] as $inc) {
        $incrementos_detalle[] = $inc['nombre'] . ' (+$' . number_format($inc['aumento'], 2) . ')';
    }
    
    $diferencia = $calculo['precio_final'] - $calculo['precio_base'];
    $clase_diferencia = $diferencia > 0 ? 'incremento' : '';
    
    echo "<tr>
        <td>{$hab['numero']}</td>
        <td>{$hab['tipo']}</td>
        <td>$" . number_format($calculo['precio_base'], 2) . "</td>
        <td>" . (empty($incrementos_detalle) ? 'Ninguno' : implode('<br>', $incrementos_detalle)) . "</td>
        <td>$" . number_format($calculo['precio_final'], 2) . "</td>
        <td class='$clase_diferencia'>$" . number_format($diferencia, 2) . "</td>
    </tr>";
}

echo "</table>

<h3>Notas:</h3>
<ul>
    <li>Los incrementos se aplican según su prioridad (mayor prioridad primero)</li>
    <li>Los incrementos de porcentaje se calculan sobre el precio base</li>
    <li>Los incrementos fijos se suman directamente al precio</li>
    <li>Si hay múltiples incrementos aplicables, todos se suman</li>
</ul>

</body>
</html>";