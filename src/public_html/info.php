<?php
// Mostrar ubicación del error log
echo "<h2>Ubicación del error_log:</h2>";
echo ini_get('error_log') . "<br><br>";

// Crear un error de prueba
error_log("=== PRUEBA ERROR LOG - " . date('Y-m-d H:i:s') . " ===");

// Mostrar información PHP
phpinfo();
?>