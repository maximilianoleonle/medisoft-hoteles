<?php
/**
 * Controlador de Usuarios
 * Sistema hotelero
 */

class UsuarioController extends Controller {

    private $usuarioModel;
    private $rolModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->usuarioModel = new Usuario();
        $this->rolModel = new Rol();
    }

    /**
     * Verificar permisos antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('usuarios');

        // Acceso administrativo del hotel cuando el modulo esta activo.
        if (!$this->puedeGestionarUsuariosHotel()) {
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
        $hotelId = $this->hotelIdActual();
        $usuarios = $hotelId
            ? $this->usuarioModel->listarTrabajadoresHotel($hotelId)
            : $this->usuarioModel->orderBy('rol', 'ASC');

        View::renderTemplate('usuarios/index', [
            'title' => 'Gestión de Usuarios',
            'usuarios' => $usuarios,
            'esGestionHotel' => (bool) $hotelId,
            'puedeCrearUsuarios' => $this->puedeGestionarUsuariosHotel(),
            'puedeEditarUsuarios' => $this->puedeGestionarUsuariosHotel()
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function crearAction() {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('usuarios/crear', [
            'title' => $hotelId ? 'Nuevo Trabajador' : 'Nuevo Usuario',
            'esGestionHotel' => (bool) $hotelId,
            'rolesHotel' => $this->rolesDelHotel($hotelId)
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

        $hotelId = $this->hotelIdActual();
        $rolesHotel = $this->rolesDelHotel($hotelId);
        $usaRolesConfigurables = !empty($rolesHotel);
        $asignacion = $usaRolesConfigurables
            ? $this->resolverAsignacionRol($hotelId, $this->getPost('role_id'))
            : null;

        // Obtener datos
        $data = [
            'nombre_usuario' => $this->getPost('nombre_usuario'),
            'password' => $this->getPost('password'),
            'nombre_completo' => $this->getPost('nombre_completo'),
            'email' => $this->getPost('email'),
            'telefono' => $this->getPost('telefono'),
            'rol' => $asignacion ? $asignacion['rol'] : $this->getPost('rol'),
            'role_id' => $asignacion['role_id'] ?? null,
            'activo' => 1
        ];

        // Validaciones
        $errores = $this->validarDatosUsuario($data, true);

        if ($usaRolesConfigurables && !$asignacion) {
            $errores[] = 'Selecciona un rol válido para el trabajador.';
        }

        if (!empty($errores)) {
            save_old_input($this->oldInputUsuario($data));
            save_form_errors($this->erroresCamposUsuario($errores));
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('usuarios/create');
        }

        try {
            // Verificar si el username ya existe
            if ($this->usuarioModel->usernameExiste($data['nombre_usuario'])) {
                save_old_input($this->oldInputUsuario($data));
                save_form_errors(['nombre_usuario' => ['El nombre de usuario ya esta en uso']]);
                set_mensaje('El nombre de usuario ya está en uso', 'error');
                $this->redirect('usuarios/create');
            }

            // Crear usuario
            $usuario = $this->usuarioModel->crearUsuario($data);

            if ($usuario) {
                if ($hotelId) {
                    $this->usuarioModel->vincularAHotel($hotelId, $usuario, $data['rol'], true, $data['role_id']);
                }

                // Registrar en log
                $this->registrarAccion('crear_usuario', "Usuario creado: {$data['nombre_completo']} ({$data['rol']})");

                clear_old_input();
                set_mensaje('Usuario creado exitosamente', 'success');
                $this->redirect('usuarios');
            } else {
                throw new Exception('Error al crear el usuario');
            }

        } catch (Exception $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            save_old_input($this->oldInputUsuario($data));
            save_form_errors($this->erroresCamposUsuario([$e->getMessage()]));
            set_mensaje('Error al crear el usuario. Por favor intente nuevamente.', 'error');
            $this->redirect('usuarios/create');
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function editarAction($id) {
        $hotelId = $this->hotelIdActual();
        $usuario = $hotelId
            ? $this->usuarioModel->findTrabajadorHotel($hotelId, $id)
            : $this->usuarioModel->find($id);

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
            'usuario' => $usuario,
            'esGestionHotel' => (bool) $hotelId,
            'rolesHotel' => $this->rolesDelHotel($hotelId),
            'usuarioRoleId' => $this->roleIdActualUsuario($hotelId, $id)
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

        $hotelId = $this->hotelIdActual();
        $usuario = $hotelId
            ? $this->usuarioModel->findTrabajadorHotel($hotelId, $id)
            : $this->usuarioModel->find($id);
        if (!$usuario) {
            set_mensaje('Usuario no encontrado', 'error');
            $this->redirect('usuarios');
        }

        $rolesHotel = $this->rolesDelHotel($hotelId);
        $usaRolesConfigurables = !empty($rolesHotel);
        $roleIdPost = $this->getPost('role_id');
        $rolLegacyPost = $this->getPost('rol'); // campo de solo-lectura (rol no editable)
        $asignacion = ($usaRolesConfigurables && $roleIdPost)
            ? $this->resolverAsignacionRol($hotelId, $roleIdPost)
            : null;

        // Obtener datos
        $data = [
            'nombre_usuario' => $this->getPost('nombre_usuario'),
            'nombre_completo' => $this->getPost('nombre_completo'),
            'email' => $this->getPost('email'),
            'telefono' => $this->getPost('telefono'),
            // Con roles configurables el rol SIEMPRE se deriva de role_id (via
            // $asignacion). Nunca se confia en el 'rol' crudo del POST: era el
            // bypass que permitia escribir usuarios.rol='gerente' omitiendo role_id.
            'rol' => $asignacion ? $asignacion['rol'] : ($usaRolesConfigurables ? null : $rolLegacyPost),
            'role_id' => $asignacion['role_id'] ?? null
        ];

        // Solo incluir password si se proporcionó
        $password = $this->getPost('password');
        if (!empty($password)) {
            $data['password'] = $password;
        }

        // Validaciones
        $errores = $this->validarDatosUsuario($data, false);

        // Con roles configurables, exigir SIEMPRE una asignación válida por
        // role_id (ya no se acepta el 'rol' crudo del POST como sustituto).
        if ($usaRolesConfigurables && !$asignacion) {
            $errores[] = 'Selecciona un rol válido para el trabajador.';
        }

        if (!empty($errores)) {
            save_old_input($this->oldInputUsuario($data));
            save_form_errors($this->erroresCamposUsuario($errores));
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect("usuarios/{$id}/edit");
        }

        try {
            // Verificar si el username ya existe (excepto para el usuario actual)
            if ($this->usuarioModel->usernameExiste($data['nombre_usuario'], $id)) {
                save_old_input($this->oldInputUsuario($data));
                save_form_errors(['nombre_usuario' => ['El nombre de usuario ya esta en uso']]);
                set_mensaje('El nombre de usuario ya está en uso', 'error');
                $this->redirect("usuarios/{$id}/edit");
            }

            // Actualizar usuario
            $actualizado = $this->usuarioModel->actualizarUsuario($id, $data);
            if ($actualizado && $hotelId) {
                $this->usuarioModel->actualizarRolHotel($hotelId, $id, $data['rol'], $data['role_id']);
            }

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

                clear_old_input();
                set_mensaje('Usuario actualizado exitosamente', 'success');
                $this->redirect('usuarios');
            } else {
                throw new Exception('Error al actualizar el usuario');
            }

        } catch (Exception $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            save_old_input($this->oldInputUsuario($data));
            save_form_errors($this->erroresCamposUsuario([$e->getMessage()]));
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
            $hotelId = $this->hotelIdActual();
            $usuario = $hotelId
                ? $this->usuarioModel->findTrabajadorHotel($hotelId, $id)
                : $this->usuarioModel->find($id);

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
            if ($hotelId) {
                $this->usuarioModel->toggleActivoHotel($hotelId, $id);
            } else {
                $this->usuarioModel->toggleActivo($id);
            }

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

        // Rol (valores ENUM de hotel_usuarios; el rol configurable ya se valida al resolverse)
        $rolesValidos = ['superadmin', 'propietario', 'gerente', 'administrador', 'recepcionista'];
        if (empty($data['rol']) || !in_array($data['rol'], $rolesValidos)) {
            $errores[] = 'El rol seleccionado no es válido';
        } elseif (in_array($data['rol'], ['propietario', 'superadmin'], true) && !$this->actorTienePoderTotal()) {
            // Anti-escalada en el camino legacy (hotel sin roles configurables):
            // el ENUM crudo propietario/superadmin solo lo asigna quien ya tiene poder total.
            $errores[] = 'No tiene permisos para asignar un rol de máximo privilegio';
        }

        return $errores;
    }

    private function oldInputUsuario(array $data): array {
        unset($data['password']);
        return $data;
    }

    private function erroresCamposUsuario(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = function_exists('mb_strtolower') ? mb_strtolower($mensaje, 'UTF-8') : strtolower($mensaje);
            $lower = strtr($lower, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ñ' => 'n',
                'Á' => 'a',
                'É' => 'e',
                'Í' => 'i',
                'Ó' => 'o',
                'Ú' => 'u',
                'Ñ' => 'n',
            ]);
            $campo = null;

            if (strpos($lower, 'nombre de usuario') !== false || strpos($lower, 'nombre_usuario') !== false || strpos($lower, 'username') !== false) {
                $campo = 'nombre_usuario';
            } elseif (strpos($lower, 'contrasena') !== false || strpos($lower, 'password') !== false) {
                $campo = 'password';
            } elseif (strpos($lower, 'nombre completo') !== false || strpos($lower, 'nombre') !== false) {
                $campo = 'nombre_completo';
            } elseif (strpos($lower, 'email') !== false || strpos($lower, 'correo') !== false) {
                $campo = 'email';
            } elseif (strpos($lower, 'telefono') !== false) {
                $campo = 'telefono';
            } elseif (strpos($lower, 'rol') !== false) {
                $campo = 'rol';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            } else {
                $fieldErrors['_global'][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function hotelIdActual() {
        return function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_id')
            ? current_hotel_id()
            : null;
    }

    /**
     * Roles configurables (activos) del hotel para poblar el selector.
     * Devuelve [] si no hay hotel o la tabla aun no existe (migracion pendiente).
     */
    private function rolesDelHotel($hotelId) {
        if (!$hotelId) {
            return [];
        }

        return $this->rolModel->listarPorHotel((int) $hotelId, true);
    }

    /**
     * Resuelve un role_id enviado por el form a [role_id, rol-ENUM]. El ENUM se
     * mapea desde la clave del rol; los roles personalizados usan 'recepcionista'
     * como valor de compatibilidad (can() ya resuelve por role_id).
     */
    private function resolverAsignacionRol($hotelId, $roleIdInput) {
        $roleIdInput = (int) $roleIdInput;

        if (!$hotelId || $roleIdInput <= 0) {
            return null;
        }

        $rol = $this->rolModel->obtenerPorId($roleIdInput, (int) $hotelId);

        if (!$rol || empty($rol['activo'])) {
            return null;
        }

        // Anti-escalada de privilegios: nadie puede OTORGAR un rol de maximo
        // privilegio (comodin '*' / propietario / superadmin) si el propio actor
        // no lo posee. Sin esto, un 'administrador' del hotel podia asignarse el
        // rol Propietario y obtener control total del tenant.
        if ($this->rolConcedePoderTotal($rol) && !$this->actorTienePoderTotal()) {
            error_log(sprintf(
                'UsuarioController: bloqueado intento de asignar rol de maximo privilegio (rol_id=%d, hotel=%d) por usuario %s sin poder total',
                (int) $rol['id'],
                (int) $hotelId,
                (string) ($_SESSION['user_id'] ?? '?')
            ));
            return null;
        }

        $enumValidos = ['superadmin', 'propietario', 'gerente', 'administrador', 'recepcionista'];
        $enum = in_array($rol['clave'], $enumValidos, true) ? $rol['clave'] : 'recepcionista';

        return ['role_id' => (int) $rol['id'], 'rol' => $enum];
    }

    /**
     * ¿El actor posee poder total en el hotel actual (comodin '*')? Solo entonces
     * puede otorgar roles de maximo privilegio. Se cumple para los roles de
     * sistema propietario/superadmin y para cualquier rol configurable cuyo
     * permisos_json incluya '*'.
     */
    private function actorTienePoderTotal(): bool {
        $rolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
        if (in_array($rolHotel, ['propietario', 'superadmin'], true)) {
            return true;
        }

        if (function_exists('current_hotel_role_id') && function_exists('hotel_role_permissions')) {
            $roleId = current_hotel_role_id();
            if ($roleId) {
                $permisos = hotel_role_permissions($roleId);
                if (is_array($permisos) && in_array('*', $permisos, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * ¿El rol destino concede poder total? True para las claves de sistema
     * propietario/superadmin o para cualquier rol cuyos permisos incluyan '*'.
     */
    private function rolConcedePoderTotal(array $rol): bool {
        if (in_array($rol['clave'] ?? null, ['propietario', 'superadmin'], true)) {
            return true;
        }

        $permisos = null;
        if (!empty($rol['id']) && function_exists('hotel_role_permissions')) {
            $permisos = hotel_role_permissions((int) $rol['id']);
        }
        if (!is_array($permisos) && isset($rol['permisos_json'])) {
            $decoded = json_decode((string) $rol['permisos_json'], true);
            $permisos = is_array($decoded) ? $decoded : null;
        }

        return is_array($permisos) && in_array('*', $permisos, true);
    }

    /**
     * role_id actual de un usuario en el hotel (para preseleccionar en edicion).
     * Devuelve null si no tiene o la columna no existe.
     */
    private function roleIdActualUsuario($hotelId, $usuarioId) {
        if (!$hotelId) {
            return null;
        }

        $rows = $this->usuarioModel->query(
            "SELECT role_id FROM hotel_usuarios WHERE hotel_id = ? AND usuario_id = ? LIMIT 1",
            [(int) $hotelId, (int) $usuarioId]
        );

        return (!empty($rows) && $rows[0]['role_id'] !== null) ? (int) $rows[0]['role_id'] : null;
    }

    private function puedeGestionarUsuariosHotel() {
        $rolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;

        return is_gerente() || in_array($rolHotel, ['gerente', 'administrador'], true);
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
