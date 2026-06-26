<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Proveedor.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/AuditService.php';

class ProveedorController extends Controller {
    private $proveedorModel;

    public function __construct($route_params = []) {
        parent::__construct($route_params);
        $this->proveedorModel = new Proveedor();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('inventario');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'activos'),
        ];

        $tablaDisponible = $this->proveedorModel->tablaDisponible();
        $proveedores = $tablaDisponible ? $this->proveedorModel->listarPorHotel($hotelId, $filtros, 200) : [];
        $resumen = $tablaDisponible ? $this->proveedorModel->resumenPorHotel($hotelId) : [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
        ];

        View::renderTemplate('proveedores/index', [
            'title' => 'Proveedores - ' . current_hotel_display_name(),
            'proveedores' => $proveedores,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function crearAction() {
        View::renderTemplate('proveedores/form', [
            'title' => 'Nuevo proveedor - ' . current_hotel_display_name(),
            'modo' => 'crear',
            'proveedor' => [],
        ]);
    }

    public function verAction() {
        $hotelId = $this->hotelIdActual();
        $proveedor = $this->proveedorActual();
        if (!$proveedor) {
            set_mensaje('Proveedor no encontrado para el hotel actual.', 'error');
            $this->redirect('proveedores');
            return;
        }

        $documentosEntidad = [];
        try {
            $documentoModel = new Documento();
            $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'proveedor', (int)$proveedor['id'], 10);
        } catch (Throwable $e) {
            $documentosEntidad = [];
        }

        View::renderTemplate('proveedores/ver', [
            'title' => 'Proveedor - ' . current_hotel_display_name(),
            'proveedor' => $proveedor,
            'historialDisponible' => $this->proveedorModel->comprasDisponibles(),
            'resumenCompras' => $this->proveedorModel->resumenComprasPorProveedor((int)$proveedor['id'], $hotelId),
            'comprasRecientes' => $this->proveedorModel->comprasRecientesPorProveedor((int)$proveedor['id'], $hotelId, 50),
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'proveedor',
                'id' => (int)$proveedor['id'],
                'label' => 'Proveedor',
            ],
        ]);
    }

    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('proveedores');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $datos = $this->datosFormulario();
            $proveedorId = $this->proveedorModel->crearParaHotel($hotelId, $datos, $this->usuarioIdActual());
            $proveedor = $this->proveedorModel->buscarPorIdHotel($proveedorId, $hotelId);

            $this->auditar('proveedores.creado', null, $proveedor, $proveedorId);
            clear_old_input();
            set_mensaje('Proveedor creado correctamente.', 'success');
            $this->redirect('proveedores');
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposProveedor([$e->getMessage()]));
            $this->redirect('proveedores/crear');
        }
    }

    public function editarAction() {
        $proveedor = $this->proveedorActual();
        if (!$proveedor) {
            set_mensaje('Proveedor no encontrado para el hotel actual.', 'error');
            $this->redirect('proveedores');
            return;
        }

        View::renderTemplate('proveedores/form', [
            'title' => 'Editar proveedor - ' . current_hotel_display_name(),
            'modo' => 'editar',
            'proveedor' => $proveedor,
        ]);
    }

    public function actualizarAction() {
        if (!$this->isPost()) {
            $this->redirect('proveedores');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $id = (int)($this->route_params['id'] ?? 0);
            $antes = $this->proveedorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Proveedor no encontrado para el hotel actual');
            }

            $ok = $this->proveedorModel->actualizarParaHotel($id, $hotelId, $this->datosFormulario(), $this->usuarioIdActual());
            if (!$ok) {
                throw new Exception('No se pudo actualizar el proveedor');
            }

            $despues = $this->proveedorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('proveedores.actualizado', $antes, $despues, $id);
            clear_old_input();
            set_mensaje('Proveedor actualizado correctamente.', 'success');
            $this->redirect('proveedores');
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposProveedor([$e->getMessage()]));
            $id = (int)($this->route_params['id'] ?? 0);
            $this->redirect($id > 0 ? 'proveedores/' . $id . '/editar' : 'proveedores');
        }
    }

    public function desactivarAction() {
        $this->cambiarEstado(false, 'Proveedor desactivado correctamente.', 'proveedores.desactivado');
    }

    public function reactivarAction() {
        $this->cambiarEstado(true, 'Proveedor reactivado correctamente.', 'proveedores.reactivado');
    }

    private function cambiarEstado(bool $activo, string $mensaje, string $accion): void {
        if (!$this->isPost()) {
            $this->redirect('proveedores');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $id = (int)($this->route_params['id'] ?? 0);
            $antes = $this->proveedorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Proveedor no encontrado para el hotel actual');
            }

            $ok = $this->proveedorModel->cambiarActivo($id, $hotelId, $activo, $this->usuarioIdActual());
            if (!$ok) {
                throw new Exception('No se pudo actualizar el estado del proveedor');
            }

            $despues = $this->proveedorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar($accion, $antes, $despues, $id);
            set_mensaje($mensaje, 'success');
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }

        $this->redirect('proveedores');
    }

    private function proveedorActual(): ?array {
        $id = (int)($this->route_params['id'] ?? 0);
        return $this->proveedorModel->buscarPorIdHotel($id, $this->hotelIdActual());
    }

    private function erroresCamposProveedor(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'nombre') !== false) {
                $campo = 'nombre';
            } elseif (strpos($lower, 'razon') !== false || strpos($lower, 'social') !== false) {
                $campo = 'razon_social';
            } elseif (strpos($lower, 'rfc') !== false) {
                $campo = 'rfc';
            } elseif (strpos($lower, 'correo') !== false || strpos($lower, 'email') !== false) {
                $campo = 'email';
            } elseif (strpos($lower, 'telefono') !== false || strpos($lower, 'tel') !== false) {
                $campo = 'telefono';
            } elseif (strpos($lower, 'direccion') !== false) {
                $campo = 'direccion';
            } elseif (strpos($lower, 'nota') !== false) {
                $campo = 'notas';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function datosFormulario(): array {
        return [
            'nombre' => $this->getPost('nombre', ''),
            'razon_social' => $this->getPost('razon_social', ''),
            'rfc' => $this->getPost('rfc', ''),
            'telefono' => $this->getPost('telefono', ''),
            'email' => $this->getPost('email', ''),
            'direccion' => $this->getPost('direccion', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function auditar(string $accion, ?array $antes, ?array $despues, int $proveedorId): void {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'proveedor',
                'entidad_id' => (string)$proveedorId,
                'descripcion' => 'Cambio en catalogo de proveedores',
                'datos_antes' => $antes,
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar proveedor: ' . $e->getMessage());
        }
    }

    private function hotelIdActual(): int {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function usuarioIdActual(): ?int {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }
}
