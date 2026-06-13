<?php
/**
 * Configuración PWA
 * Los Cedros
 */

return [
    'app_name' => 'Hotel Los Cedros',
    'short_name' => 'Los Cedros',
    'theme_color' => '#5D3A1A',
    'background_color' => '#7A8B5C',
    'display' => 'standalone',
    'orientation' => 'any',
    'version' => '1.0.0',
    'cache_version' => 'v1',
    
    // Notificaciones push. En produccion define estas variables en el entorno.
    'push' => [
        'enabled' => true,
        'vapid_public_key' => getenv('PWA_VAPID_PUBLIC_KEY') ?: '',
        'vapid_private_key' => getenv('PWA_VAPID_PRIVATE_KEY') ?: '',
        'vapid_subject' => getenv('PWA_VAPID_SUBJECT') ?: 'mailto:soporte@medisoft.mx',
        'ttl' => 3600,
    ],
    
    // Sincronización en background
    'background_sync' => [
        'enabled' => true,
        'intervals' => [
            'reservations' => 300, // 5 minutos
            'availability' => 600  // 10 minutos
        ]
    ]
];
