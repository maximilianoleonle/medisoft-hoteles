<?php
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
        View::render('auth/login', [
            'title' => 'Iniciar Sesión - Los Cedros'
        ]);
    }
    
    /**
     * Procesar autenticación
     */
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

            login($usuario['id'], $remember);
            $this->logLogin($usuario['id'], true);
            set_mensaje('Bienvenido ' . $usuario['nombre_completo'], 'success');
            $this->redirect('dashboard');

        } else {
            // Incrementar contador de intentos
            $_SESSION[$intentos_key] = ($_SESSION[$intentos_key] ?? 0) + 1;

            if ($_SESSION[$intentos_key] >= 5) {
                $_SESSION[$bloqueo_key] = time() + 900; // 15 minutos
                unset($_SESSION[$intentos_key]);
                $this->logLogin(null, false, $nombre_usuario);
                set_mensaje('Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta en 15 minutos.', 'error');
                $this->redirect('login');
            }

            $this->logLogin(null, false, $nombre_usuario);
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

        // Verificar que esté autenticado
        if (is_authenticated()) {
            $user_id = user_id();
            
            // Cerrar sesión
            logout();
            
            // Registrar en log
            $this->logLogout($user_id);
            
            // Mensaje
            set_mensaje('Sesión cerrada correctamente', 'info');
        }
        
        // Redirigir al login
        $this->redirect('login');
    }
    
    /**
     * Registrar intento de login en log
     */
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
}
