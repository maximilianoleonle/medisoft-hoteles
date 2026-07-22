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
        ]);
    }

    public function crearAction() {
        $this->formView(null);
    }

    public function editarAction($id) {
        $rol = $this->rolModel->obtenerPorId($id, current_hotel_id());

        if (!$rol) {
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

        if (!$rol) {
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
            set_mensaje('No se puede eliminar: hay ' . $usuarios . ' usuario(s) con este rol. Reasignelos primero.', 'error');
            $this->redirect('configuracion/roles');
        }

        if (!$this->rolModel->eliminar((int) $rol['id'], $hotelId)) {
            set_mensaje('No se pudo eliminar el rol.', 'error');
            $this->redirect('configuracion/roles');
        }

        set_mensaje('Rol eliminado correctamente.', 'success');
        $this->redirect('configuracion/roles');
    }

    /* ------------------------------------------------------------------ */

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
