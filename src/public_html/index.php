<?php
/**
 * Punto de entrada principal
 * Los Cedros
 */

// Definir la ruta base del proyecto
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', __DIR__); // Ajustado para Hostinger
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Reporte de errores: solo al log, nunca al navegador
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('session.use_strict_mode', 1);
error_reporting(E_ALL);

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesion con cookies endurecidas
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();
// Agregar estas rutas en tu archivo public/index.php después de las rutas existentes

// API Routes

// Autoloader simple
spl_autoload_register(function ($class) {
    // Buscar en core/
    $file = CORE_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Buscar en app/controllers/
    $file = APP_PATH . '/controllers/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
    
    // Buscar en app/models/
    $file = APP_PATH . '/models/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

// Cargar helpers
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/hotel_config.php';
require_once APP_PATH . '/helpers/modulos.php';

// Cargar helper de habitaciones si existe
if (file_exists(APP_PATH . '/helpers/habitaciones.php')) {
    require_once APP_PATH . '/helpers/habitaciones.php';
}

// Cargar configuración de la aplicación
$config = require_once CONFIG_PATH . '/app.php';

// Definir constantes de configuración
foreach ($config as $key => $value) {
    define('APP_' . strtoupper($key), $value);
}

// Verificar token remember me si existe
if (!is_authenticated() && isset($_COOKIE['remember_token'])) {
    $db = Database::getInstance();
    $token_hash = hash('sha256', $_COOKIE['remember_token']);
    
    $stmt = $db->query(
        "SELECT user_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()",
        [$token_hash]
    );
    
    if ($user = $stmt->fetch()) {
        login($user['user_id'], false);
    } else {
        // Token inválido, eliminar cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

// Crear instancia del router
$router = new Router();

// Cargar rutas
require_once CONFIG_PATH . '/routes.php';

// Obtener la URL
$url = $_SERVER['REQUEST_URI'];

// Eliminar parámetros GET de la URL
$url = strtok($url, '?');

// Eliminar el directorio base si existe
$script_name = $_SERVER['SCRIPT_NAME'];
$base_path = dirname($script_name);

// Si el base_path es solo '/', no hacer nada
if ($base_path !== '/' && $base_path !== '\\') {
    $url = str_replace($base_path, '', $url);
}

// Asegurar que la URL empiece con /
if (empty($url)) {
    $url = '/';
} elseif ($url[0] !== '/') {
    $url = '/' . $url;
}


// Despachar la ruta
try {
    $router->dispatch($url);
} catch (Exception $e) {
    // Manejar errores
    error_log($e->getMessage());
    
    // Mostrar página de error
    http_response_code(500);
    
    if (APP_DEBUG) {
        echo '<h1>Error</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        View::render('errors/500', [
            'title' => 'Error del servidor'
        ]);
    }
}
