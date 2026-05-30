<?php
/**
 * Controlador minimo para el Panel Medisoft interno SaaS.
 */

class SaasAdminController extends Controller {
    private $hotelModel;
    private $usuarioModel;
    private $moduloModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->hotelModel = new Hotel();
        $this->usuarioModel = new Usuario();
        $this->moduloModel = new Modulo();
    }

    protected function before() {
        requireSaasAdmin();
        return true;
    }

    public function hotelesAction() {
        $hoteles = $this->hotelModel->listarParaSaasAdmin();

        View::renderTemplate('admin/saas/hoteles', [
            'title' => 'Panel Medisoft interno - Hoteles',
            'hoteles' => $hoteles
        ]);
    }

    public function crearHotelAction() {
        View::renderTemplate('admin/saas/hotel_form', [
            'title' => 'Panel Medisoft interno - Crear hotel',
            'hotel' => null,
            'action' => url('admin/saas/hoteles'),
            'modo' => 'crear'
        ]);
    }

    public function guardarHotelAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();

        $data = $this->datosHotelDesdePost();
        $data['activo'] = 0;
        $errores = $this->validarDatosHotel($data);

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/crear');
        }

        $hotelId = $this->hotelModel->crearParaSaasAdmin($data);

        if (!$hotelId) {
            save_old_input($data);
            set_mensaje('No se pudo crear el hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/crear');
        }

        set_mensaje('Hotel creado en estado inactivo. Active el hotel cuando complete su configuracion.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotelId);
    }

    public function verHotelAction($id) {
        $hotel = $this->obtenerHotelORedirigir($id);
        $usuariosHotel = $this->hotelModel->listarUsuariosParaSaasAdmin((int) $hotel['id']);
        $modulosHotel = $this->moduloModel->listarParaHotelSaasAdmin((int) $hotel['id']);

        View::renderTemplate('admin/saas/hotel_detalle', [
            'title' => 'Panel Medisoft interno - Detalle de hotel',
            'hotel' => $hotel,
            'usuariosHotel' => $usuariosHotel,
            'modulosHotel' => $modulosHotel
        ]);
    }

    public function editarHotelAction($id) {
        $hotel = $this->obtenerHotelORedirigir($id);

        View::renderTemplate('admin/saas/hotel_form', [
            'title' => 'Panel Medisoft interno - Editar hotel',
            'hotel' => $hotel,
            'action' => url('admin/saas/hoteles/' . (int) $hotel['id'] . '/actualizar'),
            'modo' => 'editar'
        ]);
    }

    public function actualizarHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);

        $data = $this->datosHotelDesdePost();
        $errores = $this->validarDatosHotel($data, (int) $hotel['id']);

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar');
        }

        $actualizado = $this->hotelModel->actualizarParaSaasAdmin((int) $hotel['id'], $data);

        if (!$actualizado) {
            save_old_input($data);
            set_mensaje('No se pudo actualizar el hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar');
        }

        set_mensaje('Hotel actualizado correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    public function estadoHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $activo = (int) $this->getPost('activo', 0) === 1;

        $actualizado = $this->hotelModel->actualizarEstadoParaSaasAdmin((int) $hotel['id'], $activo);

        if (!$actualizado) {
            set_mensaje('No se pudo cambiar el estado del hotel.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        set_mensaje($activo ? 'Hotel activado correctamente.' : 'Hotel suspendido correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    public function guardarAdminHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $data = $this->datosAdminHotelDesdePost();
        $usuarioExistente = $this->usuarioModel->buscarPorNombreUsuario($data['nombre_usuario']);
        $errores = $this->validarDatosAdminHotel($data, $usuarioExistente);

        if (!empty($errores)) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        if ($usuarioExistente && $this->hotelModel->usuarioVinculado((int) $hotel['id'], (int) $usuarioExistente['id'])) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('El usuario ya esta vinculado a este hotel.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        if ($usuarioExistente && empty($usuarioExistente['activo'])) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('El usuario existe, pero esta inactivo. Active la cuenta antes de vincularla.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();
            $usuarioId = $usuarioExistente['id'] ?? null;
            $usuarioCreado = false;

            if (!$usuarioId) {
                $usuarioId = $this->usuarioModel->crearUsuario([
                    'nombre_usuario' => $data['nombre_usuario'],
                    'password' => $data['password'],
                    'nombre_completo' => $data['nombre_completo'],
                    'email' => $data['email'],
                    'telefono' => null,
                    'rol' => $data['rol_hotel'],
                    'activo' => 1
                ]);
                $usuarioCreado = true;

                if (!$usuarioId) {
                    throw new Exception('No se pudo crear el usuario.');
                }
            }

            $vinculado = $this->hotelModel->vincularUsuarioParaSaasAdmin(
                (int) $hotel['id'],
                (int) $usuarioId,
                $data['rol_hotel'],
                !empty($data['es_principal']),
                !empty($data['activo'])
            );

            if (!$vinculado) {
                throw new Exception('No se pudo vincular el usuario al hotel.');
            }

            $db->safeCommit();
            clear_old_input();

            set_mensaje(
                $usuarioCreado
                    ? 'Usuario administrador creado y vinculado al hotel correctamente.'
                    : 'Usuario existente vinculado al hotel correctamente.',
                'success'
            );
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        } catch (Exception $e) {
            $db->safeRollBack();
            error_log('Error al crear administrador hotelero: ' . $e->getMessage());
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('No se pudo guardar el administrador del hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }
    }

    public function actualizarModulosHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $modulosActivos = $this->normalizarModuloIds($this->getPost('modulos', []));
        $actualizado = $this->moduloModel->actualizarModulosHotel(
            (int) $hotel['id'],
            $modulosActivos,
            user_id()
        );

        if (!$actualizado) {
            set_mensaje('No se pudieron actualizar los modulos del hotel. Verifique que la migracion de modulos este aplicada.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        set_mensaje('Modulos del hotel actualizados correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    private function obtenerHotelORedirigir($id) {
        $hotel = $this->hotelModel->obtenerParaSaasAdmin((int) $id);

        if (!$hotel) {
            set_mensaje('Hotel no encontrado.', 'error');
            $this->redirect('admin/saas/hoteles');
        }

        return $hotel;
    }

    private function datosHotelDesdePost() {
        return [
            'nombre' => trim($this->getPost('nombre', '')),
            'slug' => $this->normalizarSlug($this->getPost('slug', '')),
            'codigo' => $this->valorNullable($this->getPost('codigo', '')),
            'razon_social' => $this->valorNullable($this->getPost('razon_social', '')),
            'rfc' => $this->valorNullable(strtoupper($this->getPost('rfc', ''))),
            'telefono' => $this->valorNullable($this->getPost('telefono', '')),
            'email' => $this->valorNullable(strtolower($this->getPost('email', ''))),
            'direccion' => $this->valorNullable($this->getPost('direccion', '')),
            'ciudad' => $this->valorNullable($this->getPost('ciudad', '')),
            'estado' => $this->valorNullable($this->getPost('estado', '')),
            'pais' => $this->valorNullable($this->getPost('pais', '')) ?: 'Mexico',
            'zona_horaria' => $this->valorNullable($this->getPost('zona_horaria', '')) ?: 'America/Mexico_City',
            'moneda_codigo' => $this->valorNullable(strtoupper($this->getPost('moneda_codigo', ''))) ?: 'MXN',
            'moneda_simbolo' => $this->valorNullable($this->getPost('moneda_simbolo', '')) ?: '$',
        ];
    }

    private function validarDatosHotel(array $data, $hotelId = null) {
        $errores = [];

        if ($data['nombre'] === '') {
            $errores[] = 'El nombre comercial es obligatorio.';
        }

        if ($data['slug'] === '') {
            $errores[] = 'El slug es obligatorio.';
        } elseif (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $data['slug'])) {
            $errores[] = 'El slug solo puede usar minusculas, numeros y guiones, sin iniciar ni terminar con guion.';
        } elseif ($this->hotelModel->slugExiste($data['slug'], $hotelId)) {
            $errores[] = 'El slug ya esta en uso por otro hotel.';
        }

        if ($this->hotelModel->codigoExiste($data['codigo'], $hotelId)) {
            $errores[] = 'El codigo ya esta en uso por otro hotel.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato valido.';
        }

        if (!preg_match('/^[A-Z]{3}$/', $data['moneda_codigo'])) {
            $errores[] = 'La moneda debe usar un codigo ISO de 3 letras, por ejemplo MXN.';
        }

        return $errores;
    }

    private function datosAdminHotelDesdePost() {
        return [
            'nombre_completo' => trim($this->getPost('nombre_completo', '')),
            'nombre_usuario' => strtolower(trim($this->getPost('nombre_usuario', ''))),
            'email' => $this->valorNullable(strtolower($this->getPost('email', ''))),
            'password' => (string) $this->getPost('password', ''),
            'rol_hotel' => trim($this->getPost('rol_hotel', 'administrador')),
            'es_principal' => (int) $this->getPost('es_principal', 0) === 1 ? 1 : 0,
            'activo' => (int) $this->getPost('activo', 1) === 1 ? 1 : 0,
        ];
    }

    private function validarDatosAdminHotel(array $data, $usuarioExistente = null) {
        $errores = [];

        if ($data['nombre_usuario'] === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (strlen($data['nombre_usuario']) < 4) {
            $errores[] = 'El nombre de usuario debe tener al menos 4 caracteres.';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['nombre_usuario'])) {
            $errores[] = 'El nombre de usuario solo puede contener minusculas, numeros y guiones bajos.';
        }

        if (!$usuarioExistente) {
            if ($data['nombre_completo'] === '') {
                $errores[] = 'El nombre completo es obligatorio para crear un usuario nuevo.';
            }

            if ($data['password'] === '') {
                $errores[] = 'La contrasena temporal es obligatoria para crear un usuario nuevo.';
            } elseif (strlen($data['password']) < 10) {
                $errores[] = 'La contrasena temporal debe tener al menos 10 caracteres.';
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato valido.';
        }

        $rolesValidos = ['administrador', 'gerente'];
        if (!in_array($data['rol_hotel'], $rolesValidos, true)) {
            $errores[] = 'El rol hotelero seleccionado no es valido.';
        }

        return $errores;
    }

    private function guardarOldInputAdminHotel(array $data) {
        unset($data['password']);
        save_old_input($data);
    }

    private function normalizarModuloIds($modulos) {
        if (!is_array($modulos)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $modulos))));
    }

    private function normalizarSlug($slug) {
        return strtolower(trim((string) $slug));
    }

    private function valorNullable($valor) {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
