<?php
require_once __DIR__ . '/../models/Area.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

/**
 * Controlador de Areas del hotel (bloque habitaciones y areas).
 *
 * Zonas sin numero de habitacion (alberca, lobby, restaurante...) que se
 * limpian, se mantienen y se cierran igual que un cuarto. Vive dentro del
 * modulo 'habitaciones' a proposito: mismos gates, misma seccion del sidebar.
 */
class AreaController extends Controller {
    private $areaModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->areaModel = new Area();
    }

    protected function before() {
        $this->requireAuth();
        require_hotel_module('habitaciones');
        return true;
    }

    private function hotelIdActual() {
        return (int)obtenerHotelIdActualCompat();
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('areas/index', [
            'title' => 'Áreas del hotel - ' . current_hotel_display_name(),
            'areas' => $this->areaModel->listar($hotelId),
            'conteo_estados' => $this->areaModel->contarPorEstado($hotelId),
            'tipos' => Area::catalogoTipos(),
            'puede_gestionar' => function_exists('can') ? can('habitaciones.edit') : true,
        ]);
    }

    /**
     * POST /areas/guardar (alta o edicion segun id, patron de activos).
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');

        $hotelId = $this->hotelIdActual();
        $id = (int)$this->getPost('id', 0);

        $nombre = trim((string)$this->getPost('nombre', ''));
        $tipo = trim((string)$this->getPost('tipo', 'otra'));
        $piso = trim((string)$this->getPost('piso', ''));
        $descripcion = trim((string)$this->getPost('descripcion', ''));

        if ($nombre === '' || mb_strlen($nombre) > 120) {
            set_mensaje('El nombre del área es obligatorio (máximo 120 caracteres)', 'error');
            $this->redirect('areas');
            return;
        }

        if (!Area::tipoValido($tipo)) {
            set_mensaje('El tipo de área elegido no es válido', 'error');
            $this->redirect('areas');
            return;
        }

        // Nombre unico por hotel (UNIQUE en BD; validamos antes para dar
        // mensaje digno en lugar de SQLSTATE).
        $duplicada = $this->areaModel->obtenerPorNombre($nombre, $hotelId);
        if ($duplicada && (int)$duplicada['id'] !== $id) {
            set_mensaje('Ya existe un área con ese nombre en este hotel', 'error');
            $this->redirect('areas');
            return;
        }

        $datos = [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'piso' => ($piso === '' ? null : (int)$piso),
            'descripcion' => ($descripcion === '' ? null : mb_substr($descripcion, 0, 500)),
        ];

        if ($id > 0) {
            $area = $this->areaModel->obtenerPorId($id, $hotelId);
            if (!$area) {
                set_mensaje('Área no encontrada', 'error');
                $this->redirect('areas');
                return;
            }
            $resultado = $this->areaModel->guardar($datos, $id);
            set_mensaje($resultado ? 'Área actualizada' : 'No se pudo actualizar el área', $resultado ? 'success' : 'error');
        } else {
            $datos['estado'] = 'disponible';
            $datos['activa'] = 1;
            $resultado = $this->areaModel->guardar($datos);
            set_mensaje($resultado ? 'Área creada' : 'No se pudo crear el área', $resultado ? 'success' : 'error');
        }

        $this->redirect('areas');
    }

    /**
     * POST /areas/{id}/toggle — pausa/reactiva el area (baja logica, el
     * UNIQUE de nombre se conserva para poder reactivar sin duplicar).
     */
    public function toggleAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $area = $this->areaModel->obtenerPorId($id, $hotelId);

        if (!$area) {
            set_mensaje('Área no encontrada', 'error');
            $this->redirect('areas');
            return;
        }

        $nuevaActiva = ((int)($area['activa'] ?? 1) === 1) ? 0 : 1;
        $this->areaModel->guardar(['activa' => $nuevaActiva], $id);
        set_mensaje($nuevaActiva ? 'Área reactivada' : 'Área pausada: ya no aparecerá en el mapa', 'success');
        $this->redirect('areas');
    }
}
