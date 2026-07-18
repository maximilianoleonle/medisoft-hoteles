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
        // Expiró la sesión por inactividad: limpiamos la sesión PERO conservamos el
        // token "recordarme" para que el auto-login reconstruya la sesión en la
        // siguiente petición (sesión siempre activa cuando se marcó recordarme).
        session_idle_expire();
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

/**
 * ID del rol configurable que el usuario tiene EN EL HOTEL actual
 * (hotel_usuarios.role_id). Lo consume can() para resolver permisos.
 *
 * Resolucion perezosa y defensiva: usa el valor cacheado en sesion si existe;
 * si no, lo consulta de hotel_usuarios y lo cachea. Devuelve null cuando no hay
 * contexto de hotel, el usuario no tiene role_id (datos legacy) o la columna aun
 * no existe (migracion pendiente). En esos casos can() usa el esquema legacy.
 */
function current_hotel_role_id() {
    static $cache = [];

    $hotelId = current_hotel_id();
    $userId = $_SESSION['user_id'] ?? null;

    if (!$hotelId || !$userId) {
        return null;
    }

    $key = $hotelId . ':' . $userId;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    // Preferir el valor ya cacheado en sesion.
    if (isset($_SESSION['hotel_usuario']['role_id']) && $_SESSION['hotel_usuario']['role_id'] !== null) {
        return $cache[$key] = (int) $_SESSION['hotel_usuario']['role_id'];
    }

    $roleId = null;

    if (class_exists('Database')) {
        try {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT role_id FROM hotel_usuarios
                 WHERE hotel_id = ? AND usuario_id = ? AND activo = 1
                 LIMIT 1",
                [(int) $hotelId, (int) $userId]
            );
            $row = $stmt ? $stmt->fetch() : null;

            if ($row && $row['role_id'] !== null) {
                $roleId = (int) $row['role_id'];
            }
        } catch (Throwable $e) {
            error_log('current_hotel_role_id: ' . $e->getMessage());
        }
    }

    // Cachear el int resuelto en sesion; el null se reintenta en cada request
    // hasta que exista role_id (p. ej. tras aplicar la migracion + backfill).
    if ($roleId !== null && isset($_SESSION['hotel_usuario']) && is_array($_SESSION['hotel_usuario'])) {
        $_SESSION['hotel_usuario']['role_id'] = $roleId;
    }

    return $cache[$key] = $roleId;
}

function has_hotel_context() {
    return current_hotel_id() !== null && current_hotel_slug() !== null;
}

/**
 * Clave del rol configurable del usuario en el hotel actual (roles.clave),
 * o null si no hay rol resoluble. La consume el aterrizaje por rol.
 */
function current_hotel_role_clave() {
    static $cache = [];

    $roleId = current_hotel_role_id();

    if (!$roleId || !class_exists('Database')) {
        return null;
    }

    if (array_key_exists($roleId, $cache)) {
        return $cache[$roleId];
    }

    try {
        $db = Database::getInstance();
        // role_id ya viene de hotel_usuarios del hotel actual; el hotel_id
        // extra es cinturon de seguridad multi-tenant.
        $stmt = $db->query(
            "SELECT clave FROM roles WHERE id = ? AND hotel_id = ? LIMIT 1",
            [(int) $roleId, (int) current_hotel_id()]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return $cache[$roleId] = $row ? (string) $row['clave'] : null;
    } catch (Throwable $e) {
        error_log('current_hotel_role_clave: ' . $e->getMessage());
        return $cache[$roleId] = null;
    }
}

/**
 * Ruta "hogar" del usuario actual. Los usuarios con rol Dueno (remoto)
 * aterrizan en el Modo Dueno (/dueno) y nunca ven el dashboard completo;
 * el resto conserva el dashboard. Exige modulo activo + permiso para no
 * mandar a nadie a una pantalla que lo rebotaria (evita bucles de redirect).
 */
function home_route_for_current_user() {
    if (has_hotel_context()
        && current_hotel_role_clave() === 'dueno_remoto'
        && function_exists('hotel_has_module') && hotel_has_module('modo_dueno')
        && can('dueno.view')) {
        return 'dueno';
    }

    return 'dashboard';
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
 * Verificar si el usuario puede acceder a una funcionalidad.
 *
 * Sistema de roles configurables: resuelve los permisos del rol que el usuario
 * tiene EN EL HOTEL actual (hotel_usuarios.role_id -> roles.permisos_json). Si
 * no hay un rol configurable resoluble (sesion legacy, migracion pendiente o
 * usuario sin role_id), cae al esquema legacy por rol-string, preservando el
 * comportamiento previo (paridad).
 */
function can($permission) {
    $roleId = current_hotel_role_id();

    if ($roleId) {
        $permisos = hotel_role_permissions($roleId);

        // null  => no se pudo resolver (tabla ausente/error) -> usar fallback.
        // array => el rol manda (un rol sin permisos no accede a nada).
        if (is_array($permisos)) {
            return permission_in_list($permission, $permisos);
        }
    }

    return can_legacy($permission);
}

/**
 * Evalua si un permiso esta concedido dentro de una lista. Soporta el comodin
 * '*' (todo) y wildcards por modulo '<modulo>.all' / '<modulo>.*'.
 */
function permission_in_list($permission, array $permisos) {
    if (in_array('*', $permisos, true)) {
        return true;
    }

    if (in_array($permission, $permisos, true)) {
        return true;
    }

    $dot = strpos($permission, '.');
    $modulo = $dot !== false ? substr($permission, 0, $dot) : $permission;

    if (in_array($modulo . '.all', $permisos, true)) {
        return true;
    }

    if (in_array($modulo . '.*', $permisos, true)) {
        return true;
    }

    return false;
}

/**
 * Permisos efectivos (array) del rol indicado, o null si no se pueden resolver.
 * Cachea la instancia del modelo; Rol::permisosDeRol cachea por rol en el request.
 */
function hotel_role_permissions($roleId) {
    $roleId = (int) $roleId;

    if ($roleId <= 0 || !class_exists('Rol')) {
        return null;
    }

    static $model = null;

    try {
        if ($model === null) {
            $model = new Rol();
        }

        return $model->permisosDeRol($roleId);
    } catch (Throwable $e) {
        error_log('hotel_role_permissions: ' . $e->getMessage());
        return null;
    }
}

/**
 * Permisos del PRESET homonimo de config/permisos.php para un rol-string
 * legacy, o null si el config/preset no existe. PURO respecto a BD (solo lee
 * el archivo de configuracion, con cache por request).
 *
 * Compat RBAC (Fase 3, 2026-07-17): los presets son la fuente de paridad de
 * can() e incluyen modulos que el arreglo historico de can_legacy no conocia
 * (reservaciones.*, documentos.*, tareas.*). Resolver el rol legacy contra su
 * preset permite gatear esas acciones sin bloquear a los hotel_usuarios sin
 * role_id (2 administradores y 1 gerente detectados en la auditoria).
 */
function legacy_preset_permissions($role) {
    static $cache = [];

    $role = (string) $role;
    if (array_key_exists($role, $cache)) {
        return $cache[$role];
    }

    $base = defined('CONFIG_PATH') ? CONFIG_PATH : __DIR__ . '/../../config';
    $path = $base . '/permisos.php';
    if (!is_readable($path)) {
        return $cache[$role] = null;
    }

    $cfg = require $path;
    $permisos = $cfg['presets'][$role]['permisos'] ?? null;

    return $cache[$role] = (is_array($permisos) ? $permisos : null);
}

/**
 * Esquema de permisos legacy por rol-string. Fallback para sesiones/usuarios sin
 * rol configurable. Resuelve contra el preset homonimo (paridad con roles
 * configurables); si el config no esta disponible, cae al arreglo historico.
 */
function can_legacy($permission) {
    $role = user_role();

    $preset = legacy_preset_permissions($role);
    if ($preset !== null) {
        return permission_in_list($permission, $preset);
    }

    // Arreglo historico (solo si config/permisos.php no se pudo leer)
    $permissions = [
        'gerente' => [
            'usuarios.view', 'usuarios.create', 'usuarios.edit', 'usuarios.delete',
            'configuracion.view', 'configuracion.edit',
            'reportes.all', 'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte', 'caja.ajustes',
            'habitaciones.all', 'huespedes.all', 'inventarios.all',
            'facturacion.all', 'compras.all',
            'personal.view', 'personal.gestionar', 'personal.pagar',
            'cuentas_por_cobrar.all', 'cuentas_por_pagar.all',
            'guardian.view'
        ],
        'administrador' => [
            'usuarios.view', 'usuarios.create', 'usuarios.edit',
            'reportes.view', 'reportes.export',
            'caja.view', 'caja.movimientos', 'caja.cobros', 'caja.corte',
            'habitaciones.all', 'huespedes.all', 'inventarios.all',
            'facturacion.all', 'compras.all',
            'personal.view', 'personal.gestionar', 'personal.pagar',
            'cuentas_por_cobrar.view', 'cuentas_por_cobrar.cobrar',
            'cuentas_por_pagar.view', 'cuentas_por_pagar.pagar'
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
        redirect(home_route_for_current_user());
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
        redirect(home_route_for_current_user());
    }
}

/**
 * Denegar acceso con 403 REAL (Fase RBAC no monetaria, 2026-07-18).
 *
 * A diferencia del patron historico de require_permission() — que en HTML
 * REDIRIGE al home con un flash — esta funcion responde un 403 explicito:
 *  - Peticion AJAX/JSON  -> HTTP 403 con cuerpo JSON.
 *  - Navegacion HTML     -> HTTP 403 + pagina errors/403 (white-label).
 * Nunca es un exito silencioso ni un redirect que se confunda con "ok".
 */
function deny_access_403($mensaje = 'No tiene permiso para acceder a esta sección') {
    if (function_exists('is_ajax') && is_ajax()) {
        json_response(['success' => false, 'message' => $mensaje], 403);
    }

    http_response_code(403);

    if (class_exists('View')) {
        View::render('errors/403', ['title' => 'Acceso denegado', 'mensaje' => $mensaje]);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $mensaje;
    }

    exit;
}

/**
 * Requerir un permiso devolviendo 403 REAL (HTML y JSON), cerrado por defecto.
 *
 * Igual que require_permission() en la resolucion (can() -> rol configurable o
 * fallback legacy por preset), pero deniega con 403 explicito en vez de
 * redirigir. Usada por los gates de LECTURA no monetaria de reservaciones
 * (index/ver/calendario/notas/disponibilidad). No sustituye a
 * require_permission() en los 53 call-sites existentes: es opt-in.
 */
function require_permission_or_403($permission, $mensaje = null) {
    require_auth();

    if (!can($permission)) {
        deny_access_403($mensaje ?? 'No tiene permiso para ver esta sección');
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
    } else {
        unset($_SESSION['hotel_id'], $_SESSION['hotel_slug'], $_SESSION['hotel_nombre'], $_SESSION['hotel_usuario']);
        if (class_exists('TenantContext')) {
            TenantContext::reset();
        }
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
        $expiry = time() + (30 * 24 * 60 * 60); // 30 días (deslizante: se renueva en cada auto-login)

        // El token recuerda el hotel con el que se inició sesión para restaurar
        // ese MISMO contexto al auto-loguear (evita el cruce a otro hotel).
        $rememberHotelId = (is_array($hotel) && !empty($hotel['id']))
            ? (int) $hotel['id']
            : (!empty($_SESSION['hotel_id']) ? (int) $_SESSION['hotel_id'] : null);

        // Guardar en BD
        $db->query(
            "INSERT INTO remember_tokens (user_id, hotel_id, token, expires_at) VALUES (?, ?, ?, ?)",
            [$user_id, $rememberHotelId, hash('sha256', $token), date('Y-m-d H:i:s', $expiry)]
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

function resolve_default_hotel_context_for_user($userId, $preferredSlug = null) {
    $userId = (int) $userId;
    if ($userId <= 0 || !class_exists('Database')) {
        return null;
    }

    $preferredSlug = normalize_hotel_login_slug($preferredSlug);
    $db = Database::getInstance();

    try {
        if ($preferredSlug) {
            $stmt = $db->query(
                "SELECT h.id, h.slug, h.nombre, hu.id AS hotel_usuario_id, hu.rol
                 FROM hotel_usuarios hu
                 INNER JOIN hoteles h ON h.id = hu.hotel_id
                 WHERE hu.usuario_id = ?
                   AND hu.activo = 1
                   AND h.activo = 1
                   AND h.slug = ?
                 LIMIT 1",
                [$userId, $preferredSlug]
            );
            $hotel = $stmt ? $stmt->fetch() : null;
            if ($hotel) {
                return [
                    'id' => (int) $hotel['id'],
                    'slug' => $hotel['slug'] ?? null,
                    'nombre' => $hotel['nombre'] ?? null,
                    'rol_hotel' => $hotel['rol'] ?? null,
                    'hotel_usuario_id' => $hotel['hotel_usuario_id'] ?? null,
                ];
            }
        }

        $stmt = $db->query(
            "SELECT h.id, h.slug, h.nombre, hu.id AS hotel_usuario_id, hu.rol
             FROM hotel_usuarios hu
             INNER JOIN hoteles h ON h.id = hu.hotel_id
             WHERE hu.usuario_id = ?
               AND hu.activo = 1
               AND h.activo = 1
             ORDER BY hu.es_principal DESC, h.id ASC
             LIMIT 1",
            [$userId]
        );
        $hotel = $stmt ? $stmt->fetch() : null;

        return $hotel ? [
            'id' => (int) $hotel['id'],
            'slug' => $hotel['slug'] ?? null,
            'nombre' => $hotel['nombre'] ?? null,
            'rol_hotel' => $hotel['rol'] ?? null,
            'hotel_usuario_id' => $hotel['hotel_usuario_id'] ?? null,
        ] : null;
    } catch (Throwable $e) {
        error_log('resolve_default_hotel_context_for_user: ' . $e->getMessage());
        return null;
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

/**
 * Expira la sesión por inactividad SIN destruir el token "recordarme".
 * Permite que el auto-login (front controller) reconstruya la sesión y el
 * contexto del hotel en la siguiente petición. A diferencia de logout(), no
 * borra la fila ni la cookie de remember_token.
 */
function session_idle_expire() {
    if (class_exists('TenantContext')) {
        TenantContext::reset();
    }

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

/**
 * Resuelve el array de hotel (formato que espera login()) a partir del hotel_id
 * guardado en un token "recordarme", validando que el usuario siga siendo
 * miembro ACTIVO de ese hotel. Devuelve null si el hotel no está activo o el
 * usuario ya no pertenece a él (así el auto-login no entra a un hotel indebido).
 */
function remember_resolve_hotel_context($userId, $hotelId) {
    $userId = (int) $userId;
    $hotelId = (int) $hotelId;

    if ($userId <= 0 || $hotelId <= 0 || !class_exists('Database')) {
        return null;
    }

    try {
        $db = Database::getInstance();

        $hotelStmt = $db->query(
            "SELECT id, slug, nombre FROM hoteles WHERE id = ? AND activo = 1 LIMIT 1",
            [$hotelId]
        );
        $hotel = $hotelStmt ? $hotelStmt->fetch() : null;

        if (!$hotel) {
            return null;
        }

        $miembroStmt = $db->query(
            "SELECT id, rol FROM hotel_usuarios WHERE hotel_id = ? AND usuario_id = ? AND activo = 1 LIMIT 1",
            [$hotelId, $userId]
        );
        $miembro = $miembroStmt ? $miembroStmt->fetch() : null;

        if (!$miembro) {
            return null;
        }

        return [
            'id' => (int) $hotel['id'],
            'slug' => $hotel['slug'] ?? null,
            'nombre' => $hotel['nombre'] ?? null,
            'rol_hotel' => $miembro['rol'] ?? null,
            'hotel_usuario_id' => $miembro['id'] ?? null,
        ];
    } catch (Throwable $e) {
        error_log('remember_resolve_hotel_context: ' . $e->getMessage());
        return null;
    }
}
