<?php
/**
 * Funciones Helper de Autenticación - MEJORADAS
 * Los Cedros
 */

/**
 * Verificar si el usuario está autenticado
 */
if (!defined('MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE')) {
    define('MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE', 'medisoft_last_hotel_slug');
}

function bootstrap_tenant_context_from_session() {
    if (!class_exists('TenantContext')) {
        return;
    }

    if (empty($_SESSION['hotel_id'])) {
        TenantContext::reset();
        return;
    }

    $roles = [];

    if (!empty($_SESSION['hotel_usuario']['rol'])) {
        $roles[] = $_SESSION['hotel_usuario']['rol'];
    }

    if (!empty($_SESSION['user']['rol'])) {
        $roles[] = $_SESSION['user']['rol'];
    }

    TenantContext::boot([
        'hotel' => [
            'id' => (int) $_SESSION['hotel_id'],
            'slug' => $_SESSION['hotel_slug'] ?? null,
            'nombre' => $_SESSION['hotel_nombre'] ?? null,
        ],
        'usuario_id' => $_SESSION['user_id'] ?? null,
        'roles' => array_values(array_unique(array_filter($roles))),
        'permisos' => [],
        'hoteles_disponibles' => [[
            'id' => (int) $_SESSION['hotel_id'],
            'slug' => $_SESSION['hotel_slug'] ?? null,
            'nombre' => $_SESSION['hotel_nombre'] ?? null,
        ]],
        'superadmin' => in_array('superadmin', $roles, true),
    ]);

    remember_hotel_login_context($_SESSION['hotel_slug'] ?? null);
}

function normalize_hotel_login_slug($slug) {
    $slug = strtolower(trim((string) $slug));

    return preg_match('/^[a-z0-9-]+$/', $slug) ? $slug : null;
}

function hotel_login_slug_for_hotel_id($hotelId) {
    static $cache = [];

    $hotelId = (int) $hotelId;
    if ($hotelId <= 0) {
        return null;
    }

    if (array_key_exists($hotelId, $cache)) {
        return $cache[$hotelId];
    }

    if (!class_exists('Database')) {
        $cache[$hotelId] = null;
        return null;
    }

    try {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT slug
             FROM hoteles
             WHERE id = ? AND activo = 1
             LIMIT 1",
            [$hotelId]
        );

        $hotel = $stmt ? $stmt->fetch() : null;
        $cache[$hotelId] = normalize_hotel_login_slug($hotel['slug'] ?? null);
    } catch (Throwable $e) {
        error_log('No se pudo resolver slug de hotel para login: ' . $e->getMessage());
        $cache[$hotelId] = null;
    }

    return $cache[$hotelId];
}

function remember_hotel_login_context($slug = null) {
    $slugFromHotelId = !empty($_SESSION['hotel_id'])
        ? hotel_login_slug_for_hotel_id($_SESSION['hotel_id'])
        : null;

    $slug = $slugFromHotelId ?: normalize_hotel_login_slug($slug ?: ($_SESSION['hotel_slug'] ?? null));

    if ($slugFromHotelId) {
        $_SESSION['hotel_slug'] = $slugFromHotelId;
    }

    if (!$slug) {
        return;
    }

    if (!headers_sent()) {
        setcookie(MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE, $slug, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => function_exists('is_https_request') ? is_https_request() : true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    $_COOKIE[MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE] = $slug;
}

function hotel_login_cookie_allowed_for_request($requestUri = null) {
    $path = parse_url($requestUri ?? ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
    $path = strtolower(trim($path, '/'));

    if ($path === '') {
        return false;
    }

    if (preg_match('#^(login|logout|h|admin|saas|panel|medisoft|hoteles)(/|$)#', $path)) {
        return false;
    }

    $firstSegment = explode('/', $path)[0] ?? '';
    $hotelSegments = [
        'api',
        'auditoria',
        'caja',
        'configuracion',
        'dashboard',
        'facturacion',
        'habitaciones',
        'huespedes',
        'inventario',
        'llaves',
        'mantenimiento',
        'notificaciones',
        'perfil',
        'reportes',
        'reservaciones',
        'usuarios',
    ];

    return in_array($firstSegment, $hotelSegments, true);
}

function remembered_hotel_login_slug($requestUri = null) {
    if (!hotel_login_cookie_allowed_for_request($requestUri)) {
        return null;
    }

    return normalize_hotel_login_slug($_COOKIE[MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE] ?? null);
}

function is_authenticated() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    $lifetimeMinutes = defined('APP_SESSION_LIFETIME') ? (int) APP_SESSION_LIFETIME : 120;
    $lastActivity = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time();

    if ($lifetimeMinutes > 0 && (time() - (int) $lastActivity) > ($lifetimeMinutes * 60)) {
        remember_hotel_login_context($_SESSION['hotel_slug'] ?? null);
        logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
    bootstrap_tenant_context_from_session();
    return true;
}

/**
 * Obtener nombre del usuario actual - MEJORADO
 */
function user_name() {
    $user = current_user();
    return $user ? $user['nombre_completo'] : 'Usuario';
}

/**
 * Obtener el usuario actual
 */
function current_user() {
    if (!is_authenticated()) {
        return null;
    }
    
    // Si no está en sesión, buscarlo en BD
    if (!isset($_SESSION['user'])) {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT * FROM usuarios WHERE id = ? AND activo = 1",
            [$_SESSION['user_id']]
        );
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password']); // No guardar password en sesión
            $_SESSION['user'] = $user;
            bootstrap_tenant_context_from_session();
        } else {
            // Usuario no válido, cerrar sesión
            remember_hotel_login_context($_SESSION['hotel_slug'] ?? null);
            logout();
            return null;
        }
    }
    
    return $_SESSION['user'];
}

/**
 * Obtener ID del usuario actual
 */
function user_id() {
    return is_authenticated() ? $_SESSION['user_id'] : null;
}

function current_hotel_id() {
    return !empty($_SESSION['hotel_id']) ? (int) $_SESSION['hotel_id'] : null;
}

function current_hotel_slug() {
    return $_SESSION['hotel_slug'] ?? null;
}

function current_hotel_nombre() {
    return $_SESSION['hotel_nombre'] ?? null;
}

function current_hotel_user_id() {
    return $_SESSION['hotel_usuario']['id'] ?? null;
}

function current_hotel_user_role() {
    return $_SESSION['hotel_usuario']['rol'] ?? null;
}

function has_hotel_context() {
    return current_hotel_id() !== null && current_hotel_slug() !== null;
}

function login_path_for_current_context($requestUri = null) {
    $hotelId = current_hotel_id();
    $slug = $hotelId ? hotel_login_slug_for_hotel_id($hotelId) : null;

    if ($slug) {
        $_SESSION['hotel_slug'] = $slug;
    }

    if (!$slug) {
        $slug = normalize_hotel_login_slug(current_hotel_slug());
    }

    if (!$slug && $requestUri) {
        $path = parse_url($requestUri, PHP_URL_PATH) ?: '';

        if (preg_match('#^/h/([a-z0-9-]+)(?:/|$)#i', $path, $matches)) {
            $slug = normalize_hotel_login_slug($matches[1]);
        }
    }

    if (!$slug) {
        $slug = remembered_hotel_login_slug($requestUri);
    }

    return $slug ? 'h/' . $slug . '/login' : 'login';
}

function require_hotel_context() {
    if (has_hotel_context()) {
        bootstrap_tenant_context_from_session();
        return current_hotel_id();
    }

    if (is_ajax()) {
        json_response([
            'success' => false,
            'message' => 'No hay contexto de hotel activo.',
            'redirect' => url(login_path_for_current_context($_SERVER['REQUEST_URI'] ?? null))
        ], 403);
    }

    set_mensaje('No hay contexto de hotel activo. Inicie sesion desde el acceso de su hotel.', 'error');
    redirect(login_path_for_current_context($_SERVER['REQUEST_URI'] ?? null));
}

/**
 * Obtener rol del usuario actual
 */
function user_role() {
    $user = current_user();
    return $user ? $user['rol'] : null;
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function has_role($role) {
    return user_role() === $role;
}

/**
 * Verificar si el usuario es Gerente
 */
function is_gerente() {
    return has_role('gerente');
}

/**
 * Verificar si el usuario es Administrador
 */
function is_admin() {
    return has_role('administrador');
}

/**
 * Verificar si el usuario es Recepcionista
 */
function is_recepcionista() {
    return has_role('recepcionista');
}

/**
 * Verificar acceso al Panel Medisoft interno SaaS.
 */
function current_saas_admin() {
    if (!is_authenticated()) {
        return null;
    }

    $usuarioId = $_SESSION['user_id'] ?? null;
    if (!$usuarioId) {
        return null;
    }

    try {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, usuario_id, rol, activo, permisos_json
             FROM saas_admins
             WHERE usuario_id = ?
               AND activo = 1
               AND rol IN ('owner', 'admin', 'soporte')
             LIMIT 1",
            [(int) $usuarioId]
        );

        if (!$stmt) {
            return null;
        }

        $saasAdmin = $stmt->fetch();
        return $saasAdmin ?: null;
    } catch (Throwable $e) {
        error_log('Error al validar saas_admins: ' . $e->getMessage());
        return null;
    }
}

function isSaasAdmin() {
    return current_saas_admin() !== null;
}

/**
 * Verificar si el usuario puede acceder a una funcionalidad
 */
function can($permission) {
    $role = user_role();
    
    // Definir permisos por rol
    $permissions = [
        'gerente' => [
            'usuarios.view', 'usuarios.create', 'usuarios.edit', 'usuarios.delete',
            'configuracion.view', 'configuracion.edit',
            'reportes.all', 'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte', 'caja.ajustes',
            'habitaciones.all', 'huespedes.all', 'inventarios.all'
        ],
        'administrador' => [
            'usuarios.view',
            'reportes.view', 'reportes.export',
            'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte',
            'habitaciones.all', 'huespedes.all', 'inventarios.all'
        ],
        'recepcionista' => [
            'habitaciones.view', 'habitaciones.checkin', 'habitaciones.checkout',
            'habitaciones.mantenimiento',
            'huespedes.view', 'huespedes.create', 'huespedes.edit',
            'caja.view', 'caja.cobros',
            'llaves.control'
        ]
    ];
    
    // Verificar si el rol tiene el permiso
    if (isset($permissions[$role]) && in_array($permission, $permissions[$role])) {
        return true;
    }
    
    // Verificar permisos con wildcards (ej: habitaciones.all)
    if (isset($permissions[$role])) {
        foreach ($permissions[$role] as $perm) {
            if (strpos($perm, '.all') !== false) {
                $module = str_replace('.all', '', $perm);
                if (strpos($permission, $module . '.') === 0) {
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Requerir autenticación - MEJORADO
 */
function require_auth() {
    $loginPath = login_path_for_current_context($_SERVER['REQUEST_URI'] ?? null);

    if (!is_authenticated()) {

        // Si es una petición AJAX, retornar JSON
        if (is_ajax()) {
            json_response([
                'success' => false,
                'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
                'redirect' => url($loginPath)
            ], 401);
        }
        
        // Para peticiones normales, redirigir al login
        set_mensaje('Debe iniciar sesión para acceder a esta página', 'error');
        redirect($loginPath);
    }
}

/**
 * Requerir rol específico
 */
function require_role($role) {
    require_auth();
    
    if (!has_role($role)) {
        if (is_ajax()) {
            json_response([
                'success' => false,
                'message' => 'No tiene permisos para acceder a esta funcionalidad'
            ], 403);
        }
        
        set_mensaje('No tiene permisos para acceder a esta página', 'error');
        redirect('dashboard');
    }
}

/**
 * Requerir acceso al Panel Medisoft interno SaaS.
 */
function requireSaasAdmin() {
    require_auth();

    if (!isSaasAdmin()) {
        if (is_ajax()) {
            json_response([
                'success' => false,
                'message' => 'No tiene permisos para acceder al Panel Medisoft interno'
            ], 403);
        }

        set_mensaje('No tiene permisos para acceder al Panel Medisoft interno', 'error');
        redirect('dashboard');
    }
}

/**
 * Requerir permiso específico
 */
function require_permission($permission) {
    require_auth();
    
    if (!can($permission)) {
        if (is_ajax()) {
            json_response([
                'success' => false,
                'message' => 'No tiene permisos para realizar esta acción'
            ], 403);
        }
        
        set_mensaje('No tiene permisos para realizar esta acción', 'error');
        redirect('dashboard');
    }
}

/**
 * Login del usuario
 */
function login($user_id, $remember = false, $hotel = null) {
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    if (is_array($hotel) && !empty($hotel['id'])) {
        $_SESSION['hotel_id'] = (int) $hotel['id'];
        $_SESSION['hotel_slug'] = $hotel['slug'] ?? null;
        $_SESSION['hotel_nombre'] = $hotel['nombre'] ?? null;
        $_SESSION['hotel_usuario'] = [
            'id' => $hotel['hotel_usuario_id'] ?? null,
            'rol' => $hotel['rol_hotel'] ?? null,
        ];

        remember_hotel_login_context($_SESSION['hotel_slug'] ?? null);
        bootstrap_tenant_context_from_session();
    }
    
    // Registrar login en base de datos
    $db = Database::getInstance();
    $db->query(
        "UPDATE usuarios SET ultimo_login = NOW(), ip_ultimo_login = ? WHERE id = ?",
        [get_client_ip(), $user_id]
    );
    
    // Si marcó "recordarme", crear cookie
    if ($remember) {
        // Implementar token de remember me
        $token = generate_random_string(64);
        $expiry = time() + (30 * 24 * 60 * 60); // 30 días
        
        // Guardar en BD
        $db->query(
            "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)",
            [$user_id, hash('sha256', $token), date('Y-m-d H:i:s', $expiry)]
        );
        
        // Crear cookie
        setcookie('remember_token', $token, [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '',
            'secure' => function_exists('is_https_request') ? is_https_request() : true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Logout del usuario
 */
function logout() {
    if (class_exists('TenantContext')) {
        TenantContext::reset();
    }

    // Eliminar remember token si existe
    if (isset($_COOKIE['remember_token'])) {
        $db = Database::getInstance();
        $db->query(
            "DELETE FROM remember_tokens WHERE user_id = ?",
            [$_SESSION['user_id'] ?? 0]
        );
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => function_exists('is_https_request') ? is_https_request() : true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    
    // Destruir sesión
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'] ?? (function_exists('is_https_request') ? is_https_request() : true),
            'httponly' => $params['httponly'] ?? true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    
    session_destroy();
}
