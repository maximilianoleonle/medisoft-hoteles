<?php
/**
 * Controlador de Roles configurables por hotel (autoservicio del dueno).
 *
 * Protegido con can('roles.manage'). Vive bajo /configuracion/roles.
 */

class RolController extends Controller {

    private $rolModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->rolModel = new Rol();
    }

    protected function before() {
        require_auth();
        require_hotel_context();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('roles_avanzados');
        }

        if (!can('roles.manage')) {
            set_mensaje('No tiene permisos para gestionar roles.', 'error');
            $this->redirect('dashboard');
            return false;
        }

        return true;
    }

    public function indexAction() {
        $hotelId = current_hotel_id();

        View::renderTemplate('roles/index', [
            'title' => 'Roles y permisos',
            'roles' => $this->rolModel->listarPorHotel($hotelId),
            'catalogo' => $this->rolModel->catalogo(),
            // Cada tarjeta de rol muestra a su gente: es lo que evita confundir
            // "quien tiene este rol" con "a quien le toca este permiso".
            'usuariosPorRol' => $this->rolModel->usuariosPorRolDeHotel($hotelId),
            'usuariosSinRol' => $this->rolModel->usuariosSinRol($hotelId),
        ]);
    }

    public function crearAction() {
        $this->formView(null);
    }

    public function editarAction($id) {
        $rol = $this->rolModel->obtenerPorId($id, current_hotel_id());

        if (!$rol || $this->rolModel->estaOculto($rol['clave'] ?? '')) {
            set_mensaje('Rol no encontrado.', 'error');
            $this->redirect('configuracion/roles');
        }

        $this->formView($rol);
    }

    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('configuracion/roles');
        }

        $this->validateCSRF();
        $hotelId = current_hotel_id();
        $data = $this->datosDesdePost();
        $errores = $this->validar($data);

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('configuracion/roles/crear');
        }

        $id = $this->rolModel->crearParaHotel($hotelId, $data);

        if (!$id) {
            save_old_input($data);
            set_mensaje('No se pudo crear el rol. Intenta de nuevo; si sigue fallando, contacta a soporte.', 'error');
            $this->redirect('configuracion/roles/crear');
        }

        clear_old_input();
        set_mensaje('Rol creado correctamente.', 'success');
        $this->redirect('configuracion/roles');
    }

    public function actualizarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('configuracion/roles');
        }

        $this->validateCSRF();
        $hotelId = current_hotel_id();
        $rol = $this->rolModel->obtenerPorId($id, $hotelId);

        if (!$rol || $this->rolModel->estaOculto($rol['clave'] ?? '')) {
            set_mensaje('Rol no encontrado.', 'error');
            $this->redirect('configuracion/roles');
        }

        $data = $this->datosDesdePost();
        $esAccesoTotal = in_array($rol['clave'], ['propietario', 'superadmin'], true);

        // Nombre/descripcion siempre editables.
        $payload = [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'],
        ];

        $errores = $this->validar($data, $rol);

        // Los permisos de los roles de acceso total no se editan (anti-lockout).
        if (!$esAccesoTotal) {
            $permisos = $this->rolModel->sanitizarPermisos($data['permisos']);

            // Se conservan los permisos VALIDOS que el rol ya tenia y que la
            // matriz no dibuja (hoy 'llaves.control', fuera del catalogo): sin
            // casilla que marcar jamas vuelven en el POST, y guardar el rol se
            // los borraba en silencio — recepcion perdia el control de llaves.
            $fueraDeLaMatriz = array_values(array_diff(
                is_array($actuales = json_decode($rol['permisos_json'] ?? '[]', true)) ? $actuales : [],
                $this->clavesDelCatalogo()
            ));

            if ($fueraDeLaMatriz && !in_array('*', $permisos, true)) {
                $permisos = $this->rolModel->sanitizarPermisos(array_merge($permisos, $fueraDeLaMatriz));
            }

            // Anti-lockout: el usuario no puede quitarse a si mismo la gestion de roles.
            if ((int) current_hotel_role_id() === (int) $rol['id']
                && !in_array('*', $permisos, true)
                && !in_array('roles.manage', $permisos, true)) {
                $errores[] = 'No puede quitarse a usted mismo el permiso de gestionar roles.';
            }

            $payload['permisos'] = $permisos;
        }

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('configuracion/roles/' . (int) $rol['id'] . '/editar');
        }

        if (!$this->rolModel->actualizarRol((int) $rol['id'], $hotelId, $payload)) {
            save_old_input($data);
            set_mensaje('No se pudo actualizar el rol.', 'error');
            $this->redirect('configuracion/roles/' . (int) $rol['id'] . '/editar');
        }

        clear_old_input();
        set_mensaje('Rol actualizado correctamente.', 'success');
        $this->redirect('configuracion/roles');
    }

    public function eliminarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('configuracion/roles');
        }

        $this->validateCSRF();
        $hotelId = current_hotel_id();
        $rol = $this->rolModel->obtenerPorId($id, $hotelId);

        if (!$rol) {
            set_mensaje('Rol no encontrado.', 'error');
            $this->redirect('configuracion/roles');
        }

        if (!empty($rol['es_sistema'])) {
            set_mensaje('Los roles base del sistema no se pueden eliminar (solo editar sus permisos).', 'error');
            $this->redirect('configuracion/roles');
        }

        $usuarios = $this->rolModel->contarUsuarios((int) $rol['id']);
        if ($usuarios > 0) {
            set_mensaje('No se puede eliminar: hay ' . $usuarios . ($usuarios == 1 ? ' usuario' : ' usuarios') . ' con este rol. Reasígnalos primero.', 'error');
            $this->redirect('configuracion/roles');
        }

        if (!$this->rolModel->eliminar((int) $rol['id'], $hotelId)) {
            set_mensaje('No se pudo eliminar el rol.', 'error');
            $this->redirect('configuracion/roles');
        }

        set_mensaje('Rol eliminado correctamente.', 'success');
        $this->redirect('configuracion/roles');
    }

    /* ---------------------------------------------------------------------
     * Permisos de UNA persona (ajustes sobre lo que ya trae su rol)
     * ------------------------------------------------------------------- */

    /**
     * Matriz de permisos de una persona concreta dentro de su rol.
     * Ruta: /configuracion/roles/{id}/persona/{usuarioId}
     */
    public function permisosUsuarioAction($id, $usuarioId) {
        $ctx = $this->contextoPersona($id, $usuarioId);

        $permisosRol = json_decode($ctx['rol']['permisos_json'] ?? '[]', true);
        $permisosRol = is_array($permisosRol) ? $permisosRol : [];
        $ajustes = $ctx['persona']['ajustes'];

        // Lo que venia marcado tras un error de validacion gana, pero SOLO si era
        // de esta misma persona: sin el sello, abrir a otra despues de un error
        // le pintaria las casillas de la anterior. Se lee crudo de la sesion a
        // proposito: old() pasa el valor por htmlspecialchars() y revienta con
        // arreglos en PHP 8.
        $marcados = null;
        if ((int) ($_SESSION['old_input']['permisos_persona'] ?? 0) === (int) $ctx['persona']['usuario_id']) {
            $marcados = $_SESSION['old_input']['permisos'] ?? null;
        }
        clear_old_input();

        if (!is_array($marcados)) {
            $marcados = [];
            foreach ($this->clavesDelCatalogo() as $permiso) {
                if (permisos_con_ajustes_personales($permiso, $permisosRol, $ajustes)) {
                    $marcados[] = $permiso;
                }
            }
        }

        $hotelId = current_hotel_id();

        View::renderTemplate('roles/permisos_persona', [
            'title' => 'Permisos de ' . $this->nombreVisible($ctx['persona']),
            'rol' => $ctx['rol'],
            'persona' => $ctx['persona'],
            'catalogo' => $this->rolModel->catalogo(),
            'permisosRol' => $permisosRol,
            'ajustes' => $ajustes,
            'marcados' => $marcados,
            'modulosActivos' => function_exists('hotel_active_module_keys')
                ? (hotel_active_module_keys($hotelId) ?: [])
                : [],
            'esUnoMismo' => (int) $ctx['persona']['usuario_id'] === (int) ($_SESSION['user_id'] ?? 0),
            'action' => url('configuracion/roles/' . (int) $ctx['rol']['id'] . '/persona/' . (int) $ctx['persona']['usuario_id']),
            'actionRestablecer' => url('configuracion/roles/' . (int) $ctx['rol']['id'] . '/persona/' . (int) $ctx['persona']['usuario_id'] . '/restablecer'),
        ]);
    }

    public function guardarPermisosUsuarioAction($id, $usuarioId) {
        if (!$this->isPost()) {
            $this->redirect('configuracion/roles');
        }

        $this->validateCSRF();
        $ctx = $this->contextoPersona($id, $usuarioId);
        $hotelId = current_hotel_id();
        $volver = 'configuracion/roles/' . (int) $ctx['rol']['id'] . '/persona/' . (int) $ctx['persona']['usuario_id'];

        $permisosRol = json_decode($ctx['rol']['permisos_json'] ?? '[]', true);
        $permisosRol = is_array($permisosRol) ? $permisosRol : [];

        $marcados = array_values(array_filter((array) $this->getPost('permisos', []), 'is_string'));
        $ambito = $this->clavesDelCatalogo();
        $ajustes = Rol::derivarAjustes($permisosRol, $marcados, $ambito);

        $errores = [];
        $esUnoMismo = (int) $ctx['persona']['usuario_id'] === (int) ($_SESSION['user_id'] ?? 0);

        // Conceder a OTROS no se restringe: quien llega aquí ya podía dar esos
        // mismos permisos editando el rol completo, y bloquearlo solo aquí
        // volvería la pantalla incoherente. Lo que sí se cierra es la puerta de
        // atrás silenciosa: darse a UNO MISMO lo que su rol no le da. Subirlo al
        // rol sigue siendo posible, pero eso queda a la vista de todos.
        if ($esUnoMismo && !$this->actorTienePoderTotal()) {
            $prohibidos = array_values(array_filter($ajustes['extra'], static function ($permiso) use ($permisosRol) {
                return !permission_in_list($permiso, $permisosRol);
            }));

            if ($prohibidos) {
                $errores[] = 'No puedes darte a ti mismo permisos que tu rol no tiene. '
                    . 'Si de verdad hacen falta, agrégalos al rol o pídele a otra persona que te los dé.';
            }
        }

        // Anti-bloqueo: nadie se cierra a sí mismo la puerta de esta pantalla.
        if ($esUnoMismo && !permisos_con_ajustes_personales('roles.manage', $permisosRol, $ajustes)) {
            $errores[] = 'No puedes quitarte a ti mismo el permiso de gestionar roles.';
        }

        if ($errores) {
            save_old_input([
                'permisos' => $marcados,
                'permisos_persona' => (int) $ctx['persona']['usuario_id'],
            ]);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect($volver);
        }

        if (!$this->rolModel->guardarAjustesUsuario($hotelId, (int) $ctx['persona']['usuario_id'], $ajustes)) {
            save_old_input([
                'permisos' => $marcados,
                'permisos_persona' => (int) $ctx['persona']['usuario_id'],
            ]);
            set_mensaje('No se pudieron guardar los permisos de esta persona.', 'error');
            $this->redirect($volver);
        }

        clear_old_input();
        $this->registrarCambioPermisos($ctx, $ajustes);

        $cambios = count($ajustes['extra']) + count($ajustes['quitados']);
        set_mensaje(
            $cambios === 0
                ? $this->nombreVisible($ctx['persona']) . ' vuelve a tener exactamente los permisos de su rol.'
                : 'Permisos de ' . $this->nombreVisible($ctx['persona']) . ' actualizados.',
            'success'
        );
        $this->redirect('configuracion/roles');
    }

    /** Borra los ajustes personales: la persona vuelve a heredar su rol tal cual. */
    public function restablecerPermisosUsuarioAction($id, $usuarioId) {
        if (!$this->isPost()) {
            $this->redirect('configuracion/roles');
        }

        $this->validateCSRF();
        $ctx = $this->contextoPersona($id, $usuarioId);
        $hotelId = current_hotel_id();

        if (!$this->rolModel->guardarAjustesUsuario($hotelId, (int) $ctx['persona']['usuario_id'], null)) {
            set_mensaje('No se pudieron restablecer los permisos de esta persona.', 'error');
            $this->redirect('configuracion/roles/' . (int) $ctx['rol']['id'] . '/persona/' . (int) $ctx['persona']['usuario_id']);
        }

        $this->registrarCambioPermisos($ctx, ['extra' => [], 'quitados' => []]);
        set_mensaje($this->nombreVisible($ctx['persona']) . ' vuelve a tener exactamente los permisos de su rol.', 'success');
        $this->redirect('configuracion/roles');
    }

    /* ------------------------------------------------------------------ */

    /**
     * Resuelve y valida el par (rol, persona) de las rutas por persona.
     * Redirige (no regresa) ante cualquier inconsistencia: rol o persona ajenos
     * al hotel, persona que ya no tiene ese rol, o rol de acceso total.
     */
    private function contextoPersona($rolId, $usuarioId) {
        $hotelId = current_hotel_id();
        $rol = $this->rolModel->obtenerPorId($rolId, $hotelId);

        if (!$rol) {
            set_mensaje('Rol no encontrado.', 'error');
            $this->redirect('configuracion/roles');
        }

        $persona = $this->rolModel->usuarioDelHotel($hotelId, $usuarioId);

        if (!$persona || (int) $persona['role_id'] !== (int) $rol['id']) {
            set_mensaje('Esa persona ya no tiene este rol.', 'error');
            $this->redirect('configuracion/roles');
        }

        // Los roles de acceso total no se recortan por persona (misma razon que
        // en el form del rol: evitar bloqueos accidentales del dueño).
        if ($this->rolConcedePoderTotal($rol)) {
            set_mensaje('Este rol tiene acceso total: sus permisos no se ajustan por persona.', 'warning');
            $this->redirect('configuracion/roles');
        }

        return ['rol' => $rol, 'persona' => $persona];
    }

    /** Claves de permiso que la matriz muestra (unico ambito comparable). */
    private function clavesDelCatalogo() {
        $claves = [];

        foreach ($this->rolModel->catalogo() as $grupo) {
            foreach (array_keys($grupo['permisos'] ?? []) as $permiso) {
                $claves[] = $permiso;
            }
        }

        return array_values(array_unique($claves));
    }

    private function nombreVisible(array $persona) {
        $nombre = trim((string) ($persona['nombre_completo'] ?? ''));
        return $nombre !== '' ? $nombre : (string) ($persona['nombre_usuario'] ?? 'esta persona');
    }

    /** ¿El actor posee el comodin '*' en este hotel? (calco de UsuarioController) */
    private function actorTienePoderTotal(): bool {
        $rolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;

        if (in_array($rolHotel, ['propietario', 'superadmin'], true)) {
            return true;
        }

        $roleId = function_exists('current_hotel_role_id') ? current_hotel_role_id() : null;

        if ($roleId && function_exists('hotel_role_permissions')) {
            $permisos = hotel_role_permissions($roleId);
            if (is_array($permisos) && in_array('*', $permisos, true)) {
                return true;
            }
        }

        return false;
    }

    private function rolConcedePoderTotal(array $rol): bool {
        if (in_array($rol['clave'] ?? null, ['propietario', 'superadmin'], true)) {
            return true;
        }

        $permisos = json_decode($rol['permisos_json'] ?? '[]', true);

        return is_array($permisos) && in_array('*', $permisos, true);
    }

    /** Bitacora: quien toco los permisos de quien y como quedaron. */
    private function registrarCambioPermisos(array $ctx, array $ajustes) {
        try {
            Database::getInstance()->query(
                "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at)
                 VALUES ('permisos_persona', ?, 1, ?, ?, ?, NOW())",
                [
                    $_SESSION['user_id'] ?? null,
                    function_exists('get_client_ip') ? get_client_ip() : null,
                    $_SERVER['HTTP_USER_AGENT'] ?? '',
                    sprintf(
                        'Permisos de %s (rol %s): +[%s] -[%s]',
                        $this->nombreVisible($ctx['persona']),
                        $ctx['rol']['nombre'] ?? '?',
                        implode(', ', $ajustes['extra'] ?? []),
                        implode(', ', $ajustes['quitados'] ?? [])
                    ),
                ]
            );
        } catch (Throwable $e) {
            error_log('registrarCambioPermisos: ' . $e->getMessage());
        }
    }

    private function formView($rol) {
        $hotelId = current_hotel_id();
        $modulosActivos = function_exists('hotel_active_module_keys')
            ? (hotel_active_module_keys($hotelId) ?: [])
            : [];

        $permisosActuales = $rol
            ? (json_decode($rol['permisos_json'] ?? '[]', true) ?: [])
            : [];

        View::renderTemplate('roles/form', [
            'title' => $rol ? 'Editar rol' : 'Nuevo rol',
            'rol' => $rol,
            'catalogo' => $this->rolModel->catalogo(),
            'permisosActuales' => $permisosActuales,
            'modulosActivos' => $modulosActivos,
            'esAccesoTotal' => $rol ? in_array($rol['clave'], ['propietario', 'superadmin'], true) : false,
            'action' => $rol
                ? url('configuracion/roles/' . (int) $rol['id'])
                : url('configuracion/roles'),
        ]);
    }

    private function datosDesdePost() {
        return [
            'nombre' => trim((string) $this->getPost('nombre', '')),
            'descripcion' => trim((string) $this->getPost('descripcion', '')),
            'permisos' => array_values(array_filter((array) $this->getPost('permisos', []), 'is_string')),
        ];
    }

    private function validar(array $data, $rolExistente = null) {
        $errores = [];

        if ($data['nombre'] === '') {
            $errores[] = 'El nombre del rol es obligatorio.';
        } elseif (mb_strlen($data['nombre']) > 120) {
            $errores[] = 'El nombre del rol es demasiado largo.';
        }

        return $errores;
    }
}
