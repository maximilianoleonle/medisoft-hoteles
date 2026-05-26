<?php
/**
 * Helper Functions para PWA
 * Los Cedros
 */

/**
 * Verificar si la app está en modo standalone
 */
function is_pwa_standalone() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           $_SERVER['HTTP_X_REQUESTED_WITH'] == 'com.hotelsan nicolas.app';
}

/**
 * Generar meta tags PWA
 */
function pwa_meta_tags() {
    $config = require CONFIG_PATH . '/pwa.php';
    
    $html = '';
    $html .= '<meta name="theme-color" content="' . $config['theme_color'] . '">' . PHP_EOL;
    $html .= '<meta name="mobile-web-app-capable" content="yes">' . PHP_EOL;
    $html .= '<meta name="apple-mobile-web-app-capable" content="yes">' . PHP_EOL;
    $html .= '<link rel="manifest" href="/manifest.json">' . PHP_EOL;
    
    return $html;
}

/**
 * Verificar si el navegador soporta PWA
 */
function browser_supports_pwa() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Verificar navegadores compatibles
    $compatible_browsers = [
        'Chrome' => 40,
        'Firefox' => 44,
        'Safari' => 11.3,
        'Edge' => 17
    ];
    
    foreach ($compatible_browsers as $browser => $min_version) {
        if (strpos($user_agent, $browser) !== false) {
            // Aquí podrías verificar la versión específica
            return true;
        }
    }
    
    return false;
}

/**
 * Registrar dispositivo para notificaciones push
 */
function register_push_subscription($subscription_data) {
    // Guardar en base de datos
    $db = Database::getInstance();
    
    $sql = "INSERT INTO push_subscriptions 
            (user_id, endpoint, p256dh, auth, created_at) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            p256dh = VALUES(p256dh), 
            auth = VALUES(auth),
            updated_at = NOW()";
    
    return $db->query($sql, [
        user_id(),
        $subscription_data['endpoint'],
        $subscription_data['keys']['p256dh'],
        $subscription_data['keys']['auth']
    ]);
}