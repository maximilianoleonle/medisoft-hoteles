<?php
/**
 * Funciones Helper de Autenticación - MEJORADAS
 * Los Cedros
 */

/**
 * Verificar si el usuario está autenticado
 */
function is_authenticated() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    $lifetimeMinutes = defined('APP_SESSION_LIFETIME') ? (int) APP_SESSION_LIFETIME : 120;
    $lastActivity = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time();

    if ($lifetimeMinutes > 0 && (time() - (int) $lastActivity) > ($lifetimeMinutes * 60)) {
        logout();
        return false;
    }

    $_SESSION['last_activity'] = time();
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
        } else {
            // Usuario no válido, cerrar sesión
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
    if (!is_authenticated()) {
        // Si es una petición AJAX, retornar JSON
        if (is_ajax()) {
            json_response([
                'success' => false,
                'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.',
                'redirect' => url('login')
            ], 401);
        }
        
        // Para peticiones normales, redirigir al login
        set_mensaje('Debe iniciar sesión para acceder a esta página', 'error');
        redirect('login');
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
function login($user_id, $remember = false) {
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
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
