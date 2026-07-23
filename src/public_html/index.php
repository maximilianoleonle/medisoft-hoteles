<?php
/**
 * Punto de entrada principal
 * Los Cedros
 */

// Definir la ruta base del proyecto
$publicParent = dirname(__DIR__);
$hostingerRoot = $publicParent . '/private/staging';
$rootPath = (
    is_dir($hostingerRoot . '/app')
    && is_dir($hostingerRoot . '/config')
    && is_dir($hostingerRoot . '/core')
) ? $hostingerRoot : $publicParent;

define('ROOT_PATH', $rootPath);
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', __DIR__); // Ajustado para Hostinger
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Logger estructurado + manejadores globales de errores.
// Se cargan ANTES que todo lo demas para que cualquier fallo del bootstrap
// quede en el log con request_id y muestre la pagina de error amigable.
require_once APP_PATH . '/helpers/log.php';
require_once APP_PATH . '/helpers/errores.php';
ms_registrar_manejadores_errores();

// Cargar variables de entorno desde ROOT_PATH/.env si el hosting no las inyecta.
$envPath = ROOT_PATH . '/.env';
if (is_readable($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($envLines ?: [] as $envLine) {
        $envLine = trim($envLine);

        if ($envLine === '' || strpos($envLine, '#') === 0 || strpos($envLine, '=') === false) {
            continue;
        }

        [$envKey, $envValue] = explode('=', $envLine, 2);
        $envKey = trim($envKey);

        if ($envKey === '' || getenv($envKey) !== false) {
            continue;
        }

        $envValue = trim($envValue);
        $quote = $envValue[0] ?? '';
        if (($quote === '"' || $quote === "'") && substr($envValue, -1) === $quote) {
            $envValue = substr($envValue, 1, -1);
        }

        putenv($envKey . '=' . $envValue);
        $_ENV[$envKey] = $envValue;
        $_SERVER[$envKey] = $envValue;
    }
}

// Reporte de errores: solo al log, nunca al navegador
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('session.use_strict_mode', 1);
error_reporting(E_ALL);

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

// Detrás de un proxy confiable (Caddy), resolver IP real y esquema ANTES de
// decidir cookies Secure. Sin TRUSTED_PROXIES en .env no cambia nada.
require_once APP_PATH . '/helpers/proxy_confiable.php';
ms_proxy_aplicar();

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

// bfcache: evita que la sesion de PHP emita "Cache-Control: no-store" (su
// default), que impide al navegador restaurar la pantalla al instante con el
// boton "atras". Emitimos nuestro propio header privado revalidable mas abajo.
session_cache_limiter('');

session_start();

// ── Headers de seguridad globales ────────────────────────────────────────
// Se emiten desde PHP (no solo el proxy) para que apliquen igual en dev,
// staging y producción. CSP completa (script-src) queda pendiente: las vistas
// aún dependen de JS inline; frame-ancestors sí es seguro de activar ya.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self'");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=()');

// Cache privado revalidable en vez de "no-store": habilita el back-forward
// cache (regresar instantaneo) y permite reutilizar la precarga en la
// navegacion, SIN exponer HTML en caches compartidos (private) y forzando
// revalidacion contra el servidor (no-cache) — tras cerrar sesion, cualquier
// navegacion normal revalida y termina en el login. Solo en GET; las descargas
// (PDF) y las APIs con no-store propio emiten su header despues y lo reemplazan.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    header('Cache-Control: private, no-cache, must-revalidate');
}
if ($secureCookie) {
    // 180 días, sin includeSubDomains: cada subdominio de hotel decide el suyo.
    header('Strict-Transport-Security: max-age=15552000');
}

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
require_once APP_PATH . '/helpers/cache.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/hotel_config.php';
require_once APP_PATH . '/helpers/modulos.php';
require_once APP_PATH . '/helpers/navegacion.php';
require_once APP_PATH . '/helpers/footer_nav.php';
require_once APP_PATH . '/helpers/branding.php';
require_once APP_PATH . '/helpers/paises.php';

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
        "SELECT id, user_id, hotel_id FROM remember_tokens WHERE token = ? AND expires_at > NOW()",
        [$token_hash]
    );

    if ($rememberRow = $stmt->fetch()) {
        // Restaurar el MISMO hotel con el que se creó el token (si sigue activo y
        // el usuario es miembro). Si no, se reconstruye sin hotel (irá a elegir hotel).
        $rememberHotel = !empty($rememberRow['hotel_id'])
            ? remember_resolve_hotel_context((int) $rememberRow['user_id'], (int) $rememberRow['hotel_id'])
            : null;

        // Reconstruir la sesión + contexto del hotel SIN emitir un token nuevo
        // (remember=false) para no provocar carreras entre peticiones concurrentes.
        login((int) $rememberRow['user_id'], false, $rememberHotel);

        // Expiración deslizante: renovamos el mismo token y su cookie a 30 días.
        $rememberSlideExpiry = time() + (30 * 24 * 60 * 60);
        $db->query(
            "UPDATE remember_tokens SET expires_at = ? WHERE id = ?",
            [date('Y-m-d H:i:s', $rememberSlideExpiry), (int) $rememberRow['id']]
        );
        setcookie('remember_token', $_COOKIE['remember_token'], [
            'expires' => $rememberSlideExpiry,
            'path' => '/',
            'domain' => '',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        // Token inválido o expirado, eliminar cookie
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
} catch (Throwable $e) {
    // Registra en el log estructurado (hotel, usuario, ruta, request_id) y
    // responde con la pagina 500 amigable o JSON si es AJAX.
    ms_manejar_throwable($e);
}
