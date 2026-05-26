<?php
require_once 'path/to/Database.php';
require_once 'services/InventarioService.php'; // Ajusta la ruta

$db = Database::getInstance();
$service = new InventarioService($db);

// Prueba con una habitación real
$habitacion_id = 1; // Cambia por un ID real
$resultado = $service->verificarDisponibilidad($habitacion_id);

echo "Resultado verificación:\n";
print_r($resultado);