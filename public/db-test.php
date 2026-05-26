<?php
date_default_timezone_set('America/Mexico_City');

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'medisoft_hoteles';
$user = getenv('DB_USER') ?: 'medisoft_user';
$pass = getenv('DB_PASS') ?: 'medisoft_pass';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "<h1>Conexión PHP + MySQL correcta</h1>";
    echo "<p>PHP pudo conectarse a la base de datos <strong>$db</strong> usando el host <strong>$host</strong>.</p>";
    echo "<p>Fecha y hora: " . date('Y-m-d H:i:s') . "</p>";

} catch (PDOException $e) {
    echo "<h1>Error de conexión</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}