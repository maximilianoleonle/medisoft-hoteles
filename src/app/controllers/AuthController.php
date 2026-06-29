<?php
require_once __DIR__ . '/../services/AuditService.php';

/**
 * Controlador de Autenticación
 * Los Cedros
 */

class AuthController extends Controller {
    
    /**
     * Mostrar formulario de login
     */
    public function loginAction() {
        // Si ya está autenticado, redirigir al dashboard
        if (is_authenticated()) {
            $this->redirect('dashboard');
        }
        
        // Renderizar vista de login
        $branding = function_exists('hotel_branding') ? hotel_branding() : null;
        View::render('auth/login', [
            'title' => 'Iniciar Sesión - ' . hotel_branding_public_name($branding, 'Medisoft Hoteles'),
            'branding' => $branding
        ]);
    }
    
    /**
     * Procesar autenticación
     */
    /**
     * Mostrar login scoped por hotel usando slug.
     */
    public function hotelLoginAction($slug) {
        $slug = $this->normalizarSlugHotel($slug);

        if ($slug === '') {
            set_mensaje('Liga de hotel invalida.', 'error');
            $this->redirect('login');
        }

        $hotel = $this->resolverHotelLoginPorSlug($slug);

        if (!$hotel) {
            set_mensaje('Hotel no encontrado o inactivo. Verifique la liga de acceso.', 'error');
            View::render('auth/login', [
                'title' => 'Hotel no encontrado - Medisoft',
                'login_disabled' => true,
                'login_action' => url('login/authenticate'),
                'branding' => function_exists('hotel_branding') ? hotel_branding() : null
            ]);
            return;
        }

        if (is_authenticated()) {
            $sessionSlug = $_SESSION['hotel_slug'] ?? null;

            if ($sessionSlug === $hotel['slug']) {
                $this->redirect('dashboard');
            }

            set_mensaje('Ya hay una sesion activa. Cierre sesion antes de ingresar a otro hotel.', 'error');
            $this->redirect('dashboard');
        }

        $branding = function_exists('hotel_branding')
            ? hotel_branding((int) $hotel['hotel_id'], $hotel)
            : null;

        View::render('auth/login', [
            'title' => 'Iniciar Sesion - ' . hotel_branding_public_name($branding, $hotel['nombre_comercial']),
            'hotel' => $hotel,
            'login_action' => url('h/' . $hotel['slug'] . '/login/authenticate'),
            'branding' => $branding
        ]);
    }

    /**
     * Procesar autenticacion scoped por hotel.
     */
    public function hotelAuthenticateAction($slug) {
        $slug = $this->normalizarSlugHotel($slug);

        if ($slug === '') {
            set_mensaje('Liga de hotel invalida.', 'error');
            $this->redirect('login');
        }

        $hotel = $this->resolverHotelLoginPorSlug($slug);
        $loginPath = 'h/' . $slug . '/login';

        if (!$hotel) {
            set_mensaje('Hotel no encontrado o inactivo. Verifique la liga de acceso.', 'error');
            $this->redirect($loginPath);
        }

        if (is_authenticated()) {
            if (($_SESSION['hotel_slug'] ?? null) === $hotel['slug']) {
                $this->redirect('dashboard');
            }

            set_mensaje('Ya hay una sesion activa. Cierre sesion antes de ingresar a otro hotel.', 'error');
            $this->redirect('dashboard');
        }

        if (!$this->isPost()) {
            $this->redirect($loginPath);
        }

        $this->validateCSRF();

        $ip = get_client_ip();
        $intentos_key = 'login_intentos_' . md5($ip . '|' . $hotel['slug']);
        $bloqueo_key  = 'login_bloqueado_' . md5($ip . '|' . $hotel['slug']);

        if (isset($_SESSION[$bloqueo_key]) && $_SESSION[$bloqueo_key] > time()) {
            $segundos = $_SESSION[$bloqueo_key] - time();
            set_mensaje('Demasiados intentos fallidos. Espera ' . ceil($segundos / 60) . ' minuto(s) antes de intentar de nuevo.', 'error');
            $this->redirect($loginPath);
        }

        $nombre_usuario = trim($this->getPost('nombre_usuario', ''));
        $password = $this->getPost('password', '');
        $remember = $this->getPost('remember') ? true : false;

        if (empty($nombre_usuario) || empty($password)) {
            set_mensaje('Por favor complete todos los campos', 'error');
            $this->redirect($loginPath);
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT * FROM usuarios WHERE nombre_usuario = ? AND activo = 1",
            [$nombre_usuario]
        );

        $usuario = $stmt->fetch();
        $credencialesValidas = $usuario && password_verify($password, $usuario['password']);
        $hotelUsuario = $credencialesValidas
            ? $this->resolverHotelUsuarioActivo((int) $hotel['hotel_id'], (int) $usuario['id'])
            : null;

        if ($credencialesValidas && $hotelUsuario) {
            unset($_SESSION[$intentos_key], $_SESSION[$bloqueo_key]);

            login($usuario['id'], $remember, [
                'id' => (int) $hotel['hotel_id'],
                'slug' => $hotel['slug'],
                'nombre' => $hotel['nombre_comercial'],
                'rol_hotel' => $hotelUsuario['rol'] ?? null,
                'hotel_usuario_id' => $hotelUsuario['id'] ?? null
            ]);

            $this->logLogin($usuario['id'], true);
            $this->auditLogin((int) $usuario['id'], true, $nombre_usuario, (int) $hotel['hotel_id'], 'hotel_login');
            set_mensaje('Bienvenido ' . $usuario['nombre_completo'], 'success');
            $this->redirect('dashboard');
        }

        $_SESSION[$intentos_key] = ($_SESSION[$intentos_key] ?? 0) + 1;

        if ($_SESSION[$intentos_key] >= 5) {
            $_SESSION[$bloqueo_key] = time() + 900;
            unset($_SESSION[$intentos_key]);
            $this->logLogin(null, false, $nombre_usuario);
            $this->auditLogin(null, false, $nombre_usuario, (int) $hotel['hotel_id'], 'hotel_login');
            set_mensaje('Cuenta bloqueada temporalmente por multiples intentos fallidos. Intenta en 15 minutos.', 'error');
            $this->redirect($loginPath);
        }

        $this->logLogin(null, false, $nombre_usuario);
        $this->auditLogin(null, false, $nombre_usuario, (int) $hotel['hotel_id'], 'hotel_login');
        $restantes = 5 - $_SESSION[$intentos_key];
        set_mensaje('Usuario o contrasena no validos para este hotel. Te quedan ' . $restantes . ' intento(s).', 'error');
        $this->redirect($loginPath);
    }

    public function authenticateAction() {
        // Verificar que sea POST
        if (!$this->isPost()) {
            $this->redirect('login');
        }

        // Verificar token CSRF
        $this->validateCSRF();

        // --- Protección brute force por IP (máx. 5 intentos en 15 min) ---
        $ip = get_client_ip();
        $intentos_key = 'login_intentos_' . md5($ip);
        $bloqueo_key  = 'login_bloqueado_' . md5($ip);

        if (isset($_SESSION[$bloqueo_key]) && $_SESSION[$bloqueo_key] > time()) {
            $segundos = $_SESSION[$bloqueo_key] - time();
            set_mensaje('Demasiados intentos fallidos. Espera ' . ceil($segundos / 60) . ' minuto(s) antes de intentar de nuevo.', 'error');
            $this->redirect('login');
        }

        // Obtener datos del formulario
        $nombre_usuario = trim($this->getPost('nombre_usuario', ''));
        $password = $this->getPost('password', '');
        $remember = $this->getPost('remember') ? true : false;

        // Validar campos requeridos
        if (empty($nombre_usuario) || empty($password)) {
            set_mensaje('Por favor complete todos los campos', 'error');
            $this->redirect('login');
        }

        // Buscar usuario en base de datos
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT * FROM usuarios WHERE nombre_usuario = ? AND activo = 1",
            [$nombre_usuario]
        );

        $usuario = $stmt->fetch();

        // Verificar si existe el usuario y la contraseña es correcta
        if ($usuario && password_verify($password, $usuario['password'])) {
            // Limpiar contador de intentos al login exitoso
            unset($_SESSION[$intentos_key], $_SESSION[$bloqueo_key]);

            $preferredSlug = defined('MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE')
                ? normalize_hotel_login_slug($_COOKIE[MEDISOFT_HOTEL_LOGIN_CONTEXT_COOKIE] ?? null)
                : null;
            $hotelContext = function_exists('resolve_default_hotel_context_for_user')
                ? resolve_default_hotel_context_for_user((int) $usuario['id'], $preferredSlug)
                : null;

            if (!$hotelContext) {
                // El usuario no tiene ningun hotel activo asignado. No se permite
                // entrar sin contexto hotelero porque toda la UI quedaría sin branding
                // y los datos cruzarían entre hoteles. Se indica al usuario que use
                // la liga directa de su hotel (BUG-001).
                set_mensaje('Su usuario no tiene acceso a ning&uacute;n hotel activo. Ingrese desde la liga directa de su hotel (ej. /h/nombre-hotel/login).', 'warning');
                $this->redirect('login');
            }

            login($usuario['id'], $remember, $hotelContext);
            $this->logLogin($usuario['id'], true);
            $this->auditLogin((int) $usuario['id'], true, $nombre_usuario, $hotelContext['id'] ?? null, 'login');
            set_mensaje('Bienvenido ' . $usuario['nombre_completo'], 'success');
            $this->redirect('dashboard');

        } else {
            // Incrementar contador de intentos
            $_SESSION[$intentos_key] = ($_SESSION[$intentos_key] ?? 0) + 1;

            if ($_SESSION[$intentos_key] >= 5) {
                $_SESSION[$bloqueo_key] = time() + 900; // 15 minutos
                unset($_SESSION[$intentos_key]);
                $this->logLogin(null, false, $nombre_usuario);
                $this->auditLogin(null, false, $nombre_usuario, null, 'login');
                set_mensaje('Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta en 15 minutos.', 'error');
                $this->redirect('login');
            }

            $this->logLogin(null, false, $nombre_usuario);
            $this->auditLogin(null, false, $nombre_usuario, null, 'login');
            $restantes = 5 - $_SESSION[$intentos_key];
            set_mensaje('Usuario o contraseña incorrectos. Te quedan ' . $restantes . ' intento(s).', 'error');
            $this->redirect('login');
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logoutAction() {
        if (!$this->isPost()) {
            $this->redirect('dashboard');
        }

        $this->validateCSRF();
        $loginPath = 'login';

        // Verificar que esté autenticado
        if (is_authenticated()) {
            $user_id = user_id();
            $hotel_id = function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null);
            $hotel_slug = $hotel_id && function_exists('hotel_login_slug_for_hotel_id')
                ? hotel_login_slug_for_hotel_id((int) $hotel_id)
                : null;

            if (!$hotel_slug && function_exists('current_hotel_slug')) {
                $hotel_slug = normalize_hotel_login_slug(current_hotel_slug());
            }

            if ($hotel_slug) {
                $loginPath = 'h/' . $hotel_slug . '/login';
            }
            
            // Cerrar sesión
            logout();
            
            // Registrar en log
            $this->logLogout($user_id);
            $this->auditLogout((int) $user_id, $hotel_id ? (int) $hotel_id : null);
            
            // Mensaje
            set_mensaje('Sesión cerrada correctamente', 'info');
        }
        
        // Redirigir al login
        $this->redirect($loginPath);
    }
    
    /**
     * Registrar intento de login en log
     */
    private function normalizarSlugHotel($slug) {
        $slug = strtolower(trim((string) $slug));

        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return '';
        }

        return $slug;
    }

    private function resolverHotelLoginPorSlug($slug) {
        if ($slug === '') {
            return null;
        }

        $db = Database::getInstance();
        // El esquema actual no tiene bandera "demo"; esta microfase solo resuelve hoteles activos.
        $stmt = $db->query(
            "SELECT id AS hotel_id, nombre AS nombre_comercial, slug
             FROM hoteles
             WHERE slug = ? AND activo = 1
             LIMIT 1",
            [$slug]
        );

        if (!$stmt) {
            return null;
        }

        $hotel = $stmt->fetch();

        return $hotel ?: null;
    }

    private function resolverHotelUsuarioActivo($hotelId, $usuarioId) {
        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, hotel_id, usuario_id, rol, activo
             FROM hotel_usuarios
             WHERE hotel_id = ? AND usuario_id = ? AND activo = 1
             LIMIT 1",
            [$hotelId, $usuarioId]
        );

        if (!$stmt) {
            return null;
        }

        $hotelUsuario = $stmt->fetch();

        return $hotelUsuario ?: null;
    }

    private function logLogin($user_id, $success, $username = null) {
        try {
            $db = Database::getInstance();
            
            // Preparar datos del log
            $log_data = [
                'tipo' => 'login',
                'usuario_id' => $user_id,
                'exitoso' => $success ? 1 : 0,
                'ip' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'detalles' => $success ? 'Login exitoso' : 'Login fallido para usuario: ' . $username
            ];
            
            // Insertar en tabla de logs (si existe)
            $sql = "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at) 
                    VALUES (:tipo, :usuario_id, :exitoso, :ip, :user_agent, :detalles, NOW())";
            
            $db->query($sql, $log_data);
            
        } catch (Exception $e) {
            // Si falla el log, no interrumpir el proceso
            error_log("Error al registrar log de login: " . $e->getMessage());
        }
    }

    private function auditLogin($user_id, $success, $username = null, $hotel_id = null, $contexto = 'login') {
        try {
            if ($success && $user_id) {
                AuditService::loginSuccess((int) $user_id, $hotel_id ? (int) $hotel_id : null, $contexto);
                return;
            }

            AuditService::loginFailed($username, $hotel_id ? (int) $hotel_id : null, $contexto);
        } catch (Throwable $e) {
            error_log("Error al registrar auditoria de login: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar logout en log
     */
    private function logLogout($user_id) {
        try {
            $db = Database::getInstance();
            
            $log_data = [
                'tipo' => 'logout',
                'usuario_id' => $user_id,
                'exitoso' => 1,
                'ip' => get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'detalles' => 'Logout exitoso'
            ];
            
            $sql = "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at) 
                    VALUES (:tipo, :usuario_id, :exitoso, :ip, :user_agent, :detalles, NOW())";
            
            $db->query($sql, $log_data);
            
        } catch (Exception $e) {
            error_log("Error al registrar log de logout: " . $e->getMessage());
        }
    }

    private function auditLogout($user_id, $hotel_id = null) {
        try {
            if ($user_id) {
                AuditService::logout((int) $user_id, $hotel_id ? (int) $hotel_id : null);
            }
        } catch (Throwable $e) {
            error_log("Error al registrar auditoria de logout: " . $e->getMessage());
        }
    }
}
