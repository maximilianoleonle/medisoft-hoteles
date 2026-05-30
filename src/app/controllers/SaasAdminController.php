<?php
/**
 * Controlador minimo para el Panel Medisoft interno SaaS.
 */

class SaasAdminController extends Controller {
    private $hotelModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->hotelModel = new Hotel();
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

        View::renderTemplate('admin/saas/hotel_detalle', [
            'title' => 'Panel Medisoft interno - Detalle de hotel',
            'hotel' => $hotel
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

    private function normalizarSlug($slug) {
        return strtolower(trim((string) $slug));
    }

    private function valorNullable($valor) {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}