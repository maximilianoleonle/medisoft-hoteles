<?php
/**
 * Controlador de Usuarios
 * Sistema hotelero
 */

class UsuarioController extends Controller {
    
    private $usuarioModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->usuarioModel = new Usuario();
    }
    
    /**
     * Verificar permisos antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        
        // Solo el gerente puede gestionar usuarios
        if (!is_gerente()) {
            set_mensaje('No tiene permisos para acceder a esta sección', 'error');
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Listado de usuarios
     */
    public function indexAction() {
        $usuarios = $this->usuarioModel->orderBy('rol', 'ASC');
        
        View::renderTemplate('usuarios/index', [
            'title' => 'Gestión de Usuarios',
            'usuarios' => $usuarios
        ]);
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function crearAction() {
        View::renderTemplate('usuarios/crear', [
            'title' => 'Nuevo Usuario'
        ]);
    }
    
    /**
     * Guardar nuevo usuario
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('usuarios');
        }
        
        // Validar CSRF
        $this->validateCSRF();
        
        // Obtener datos
        $data = [
            'nombre_usuario' => $this->getPost('nombre_usuario'),
            'password' => $this->getPost('password'),
            'nombre_completo' => $this->getPost('nombre_completo'),
            'email' => $this->getPost('email'),
            'telefono' => $this->getPost('telefono'),
            'rol' => $this->getPost('rol'),
            'activo' => 1
        ];
        
        // Validaciones
        $errores = $this->validarDatosUsuario($data, true);
        
        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('usuarios/create');
        }
        
        try {
            // Verificar si el username ya existe
            if ($this->usuarioModel->usernameExiste($data['nombre_usuario'])) {
                save_old_input($data);
                set_mensaje('El nombre de usuario ya está en uso', 'error');
                $this->redirect('usuarios/create');
            }
            
            // Crear usuario
            $usuario = $this->usuarioModel->crearUsuario($data);
            
            if ($usuario) {
                // Registrar en log
                $this->registrarAccion('crear_usuario', "Usuario creado: {$data['nombre_completo']} ({$data['rol']})");
                
                set_mensaje('Usuario creado exitosamente', 'success');
                $this->redirect('usuarios');
            } else {
                throw new Exception('Error al crear el usuario');
            }
            
        } catch (Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            save_old_input($data);
            set_mensaje('Error al crear el usuario. Por favor intente nuevamente.', 'error');
            $this->redirect('usuarios/create');
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function editarAction($id) {
        $usuario = $this->usuarioModel->find($id);
        
        if (!$usuario) {
            set_mensaje('Usuario no encontrado', 'error');
            $this->redirect('usuarios');
        }
        
        // No permitir editar al usuario actual si es el único gerente activo
        if ($usuario['id'] == user_id() && $usuario['rol'] == 'gerente') {
            $gerentesActivos = $this->usuarioModel->query(
                "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'gerente' AND activo = 1 AND id != ?",
                [$usuario['id']]
            );
            
            if ($gerentesActivos[0]['total'] == 0) {
                set_mensaje('No puede editar este usuario porque es el único gerente activo', 'warning');
                $this->redirect('usuarios');
            }
        }
        
        View::renderTemplate('usuarios/editar', [
            'title' => 'Editar Usuario',
            'usuario' => $usuario
        ]);
    }
    
    /**
     * Actualizar usuario
     */
    public function actualizarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('usuarios');
        }
        
        // Validar CSRF
        $this->validateCSRF();
        
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            set_mensaje('Usuario no encontrado', 'error');
            $this->redirect('usuarios');
        }
        
        // Obtener datos
        $data = [
            'nombre_usuario' => $this->getPost('nombre_usuario'),
            'nombre_completo' => $this->getPost('nombre_completo'),
            'email' => $this->getPost('email'),
            'telefono' => $this->getPost('telefono'),
            'rol' => $this->getPost('rol')
        ];
        
        // Solo incluir password si se proporcionó
        $password = $this->getPost('password');
        if (!empty($password)) {
            $data['password'] = $password;
        }
        
        // Validaciones
        $errores = $this->validarDatosUsuario($data, false);
        
        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect("usuarios/{$id}/edit");
        }
        
        try {
            // Verificar si el username ya existe (excepto para el usuario actual)
            if ($this->usuarioModel->usernameExiste($data['nombre_usuario'], $id)) {
                save_old_input($data);
                set_mensaje('El nombre de usuario ya está en uso', 'error');
                $this->redirect("usuarios/{$id}/edit");
            }
            
            // Actualizar usuario
            $actualizado = $this->usuarioModel->actualizarUsuario($id, $data);
            
            if ($actualizado) {
                // Registrar en log
                $this->registrarAccion('actualizar_usuario', "Usuario actualizado: {$data['nombre_completo']}");
                
                // Si el usuario se editó a sí mismo, actualizar la sesión
                if ($id == user_id()) {
                    $_SESSION['user'] = array_merge($_SESSION['user'], [
                        'nombre_completo' => $data['nombre_completo'],
                        'email' => $data['email']
                    ]);
                }
                
                set_mensaje('Usuario actualizado exitosamente', 'success');
                $this->redirect('usuarios');
            } else {
                throw new Exception('Error al actualizar el usuario');
            }
            
        } catch (Exception $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            save_old_input($data);
            set_mensaje('Error al actualizar el usuario. Por favor intente nuevamente.', 'error');
            $this->redirect("usuarios/{$id}/edit");
        }
    }
    
    /**
     * Cambiar estado activo/inactivo
     */
    public function cambiarEstadoAction($id) {
        if (!$this->isPost()) {
            $this->redirect('usuarios');
        }
        
        // Validar CSRF
        $this->validateCSRF();
        
        try {
            $usuario = $this->usuarioModel->find($id);
            
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // No permitir desactivar al usuario actual
            if ($usuario['id'] == user_id()) {
                set_mensaje('No puede desactivar su propia cuenta', 'error');
                $this->redirect('usuarios');
            }
            
            // No permitir desactivar al último gerente activo
            if ($usuario['rol'] == 'gerente' && $usuario['activo'] == 1) {
                $gerentesActivos = $this->usuarioModel->query(
                    "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'gerente' AND activo = 1"
                );
                
                if ($gerentesActivos[0]['total'] <= 1) {
                    set_mensaje('No puede desactivar al último gerente activo', 'error');
                    $this->redirect('usuarios');
                }
            }
            
            // Cambiar estado
            $this->usuarioModel->toggleActivo($id);
            
            // Registrar en log
            $nuevoEstado = $usuario['activo'] ? 'desactivado' : 'activado';
            $this->registrarAccion('toggle_usuario', "Usuario {$nuevoEstado}: {$usuario['nombre_completo']}");
            
            set_mensaje('Estado del usuario actualizado', 'success');
            
        } catch (Exception $e) {
            error_log("Error al cambiar estado de usuario: " . $e->getMessage());
            set_mensaje('Error al cambiar el estado del usuario', 'error');
        }
        
        $this->redirect('usuarios');
    }
    
    /**
     * Validar datos del usuario
     */
    private function validarDatosUsuario($data, $esNuevo = true) {
        $errores = [];
        
        // Nombre de usuario
        if (empty($data['nombre_usuario'])) {
            $errores[] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($data['nombre_usuario']) < 4) {
            $errores[] = 'El nombre de usuario debe tener al menos 4 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['nombre_usuario'])) {
            $errores[] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
        }
        
        // Password (obligatorio solo en creación)
        if ($esNuevo || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errores[] = 'La contraseña es obligatoria';
            } elseif (strlen($data['password']) < 10) {
                $errores[] = 'La contraseña debe tener al menos 10 caracteres';
            }
        }
        
        // Nombre completo
        if (empty($data['nombre_completo'])) {
            $errores[] = 'El nombre completo es obligatorio';
        }
        
        // Email (opcional pero si se proporciona debe ser válido)
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no es válido';
        }
        
        // Rol
        $rolesValidos = ['gerente', 'administrador', 'recepcionista'];
        if (empty($data['rol']) || !in_array($data['rol'], $rolesValidos)) {
            $errores[] = 'El rol seleccionado no es válido';
        }
        
        return $errores;
    }
    
    /**
     * Registrar acción en log
     */
    private function registrarAccion($tipo, $descripcion) {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at) 
             VALUES (?, ?, 1, ?, ?, ?, NOW())",
            [$tipo, user_id(), get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $descripcion]
        );
    }
}
