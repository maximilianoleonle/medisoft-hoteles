<?php
/**
 * Configuración General de la Aplicación
 * Los Cedros
 */

return [
    // Nombre de la aplicación
    'name' => 'Los Cedros',
    
    // Versión
    'version' => '1.0.0',
    
    // Modo debug (cambiar a false en producción)
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    
    // URL base (dejar vacío para detección automática)
    'url' => '',
    
    // Zona horaria
    'timezone' => 'America/Mexico_City',
    
    // Configuración de sesión
    'session_lifetime' => 120, // minutos
    
    // Datos del hotel
    'hotel' => [
        'nombre' => 'Los Cedros',
        'direccion' => 'Santa Catarina Juquila, Oaxaca',
        'telefono' => '',
        'email' => '',
        'habitaciones' => 66,
        'check_in_time' => '15:00',
        'check_out_time' => '12:00',
        'horas_estancia' => 24,
    ],
    
    // Configuración de uploads
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif'],
        'allowed_documents' => ['pdf', 'doc', 'docx'],
    ],
    
    // Configuración de inventario
    'inventario' => [
        'auto_descuento' => [
            'papel_higienico' => 1, // Cantidad por habitación
            'jabon' => 1, // Cantidad por habitación
        ]
    ],
    
    // Configuración de tarifas
    'tarifas' => [
        'incremento_fin_semana' => 0, // Porcentaje de incremento
        'descuento_grupo' => [
            'habitaciones_minimas' => 10,
            'habitaciones_gratis' => 1
        ]
    ],
    
    // Configuración de moneda
    'moneda' => [
        'simbolo' => '$',
        'codigo' => 'MXN',
        'decimales' => 2
    ],
    
    // Configuración de logs
    'log' => [
        'enabled' => true,
        'path' => STORAGE_PATH . '/logs',
        'level' => 'debug', // debug, info, warning, error
        'days_to_keep' => 30
    ],
    
    // Configuración de respaldos
    'backup' => [
        'enabled' => true,
        'path' => STORAGE_PATH . '/backups',
        'frequency' => 'weekly', // daily, weekly, monthly
        'keep_last' => 4 // Número de respaldos a mantener
    ]
];
