<?php
/**
 * Configuracion de Base de Datos
 * Entorno local Docker - Medisoft Hoteles
 */

return [
    'host' => getenv('DB_HOST') ?: 'db',
    'database' => getenv('DB_NAME') ?: 'medisoft_hoteles',
    'username' => getenv('DB_USER') ?: 'medisoft_user',
    'password' => getenv('DB_PASS') ?: 'medisoft_pass',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];