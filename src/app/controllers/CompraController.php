<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/CompraService.php';

class CompraController extends Controller
{
    private $compraService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->compraService = new CompraService();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('compras');
        }

        // Ademas del modulo contratado, exigir permiso del usuario: 'recibir' una
        // compra ejecuta entradas de inventario y habilita la CxP, no debe quedar
        // abierto a cualquier autenticado (p. ej. recepcionista).
        require_permission('compras.view');

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
        ];

        $compras = [];
        $resumen = [
            'total' => 0,
            'borradores' => 0,
            'recibidas' => 0,
            'canceladas' => 0,
            'total_borrador' => '0.00',
        ];
        $tablaDisponible = true;
        $errorTecnico = null;

        try {
            $compras = $this->compraService->listarCompras($hotelId, $filtros, 100);
            $resumen = $this->compraService->resumenPorHotel($hotelId);
        } catch (Throwable $e) {
            $tablaDisponible = false;
            $errorTecnico = $e->getMessage();
        }

        View::renderTemplate('compras/index', [
            'title' => 'Compras - ' . current_hotel_display_name(),
            'compras' => $compras,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
            'errorTecnico' => $errorTecnico,
        ]);
    }

    public function crearAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $catalogos = [
            'proveedores' => [],
            'productos' => [],
        ];
        $tablaDisponible = true;
        $errorTecnico = null;

        try {
            $catalogos = $this->compraService->catalogosBorrador($hotelId);
        } catch (Throwable $e) {
            $tablaDisponible = false;
            $errorTecnico = $e->getMessage();
        }

        View::renderTemplate('compras/form', [
            'title' => 'Nuevo borrador de compra - ' . current_hotel_display_name(),
            'catalogos' => $catalogos,
            'tablaDisponible' => $tablaDisponible,
            'errorTecnico' => $errorTecnico,
        ]);
    }

    public function verAction(): void
    {
        try {
            $compraId = (int)($this->route_params['id'] ?? 0);
            if ($compraId <= 0) {
                throw new Exception('Compra invalida');
            }

            $hotelId = $this->hotelIdActual();
            $compra = $this->compraService->obtenerCompra($hotelId, $compraId);
            if (!$compra) {
                set_mensaje('Compra no encontrada para el hotel actual.', 'error');
                $this->redirect('compras');
                return;
            }

            $documentosEntidad = [];
            try {
                $documentoModel = new Documento();
                $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'compra', $compraId, 10);
            } catch (Throwable $e) {
                $documentosEntidad = [];
            }

            View::renderTemplate('compras/ver', [
                'title' => 'Compra #' . $compraId . ' - ' . current_hotel_display_name(),
                'compra' => $compra,
                'documentosEntidad' => $documentosEntidad,
                'documentosEntidadContexto' => [
                    'tipo' => 'compra',
                    'id' => $compraId,
                    'label' => 'Compra',
                ],
            ]);
        } catch (Throwable $e) {
            set_mensaje('No se pudo cargar el detalle de la compra: ' . $e->getMessage(), 'error');
            $this->redirect('compras');
        }
    }

    public function editarAction(): void
    {
        require_permission('compras.all');

        try {
            $compraId = (int)($this->route_params['id'] ?? 0);
            if ($compraId <= 0) {
                throw new Exception('Compra invalida');
            }

            $hotelId = $this->hotelIdActual();
            $compra = $this->compraService->obtenerCompra($hotelId, $compraId);
            if (!$compra) {
                throw new Exception('Compra no encontrada para el hotel actual');
            }
            if ((string)($compra['estado'] ?? '') !== 'borrador') {
                throw new Exception('Solo se pueden editar compras en borrador');
            }

            View::renderTemplate('compras/form', [
                'title' => 'Editar compra #' . $compraId . ' - ' . current_hotel_display_name(),
                'catalogos' => $this->compraService->catalogosBorrador($hotelId),
                'tablaDisponible' => true,
                'errorTecnico' => null,
                'compra' => $compra,
                'modoEdicion' => true,
            ]);
        } catch (Throwable $e) {
            set_mensaje('No se puede editar la compra: ' . $e->getMessage(), 'error');
            $this->redirect('compras');
        }
    }

    public function reporteRecibidasAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'proveedor_id' => $this->getQuery('proveedor_id', ''),
            'producto_id' => $this->getQuery('producto_id', ''),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
            'estado' => $this->getQuery('estado', 'recibida'),
        ];

        $catalogos = [
            'proveedores' => [],
            'productos' => [],
        ];
        $reporte = [
            'filtros' => $filtros,
            'resumen' => [
                'compras' => 0,
                'proveedores' => 0,
                'productos' => 0,
                'cantidad_total' => '0.00',
                'total_lineas' => '0.00',
            ],
            'lineas' => [],
            'por_proveedor' => [],
            'por_producto' => [],
        ];
        $tablaDisponible = true;
        $errorTecnico = null;

        try {
            $catalogos = $this->compraService->catalogosReporteRecibidas($hotelId);
            $reporte = $this->compraService->reporteRecibidas($hotelId, $filtros, 300);
        } catch (Throwable $e) {
            $tablaDisponible = false;
            $errorTecnico = $e->getMessage();
        }

        View::renderTemplate('compras/reporte_recibidas', [
            'title' => 'Reporte de compras recibidas - ' . current_hotel_display_name(),
            'catalogos' => $catalogos,
            'reporte' => $reporte,
            'tablaDisponible' => $tablaDisponible,
            'errorTecnico' => $errorTecnico,
        ]);
    }

    public function guardarAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('compras');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $datos = [
                'proveedor_id' => $_POST['proveedor_id'] ?? null,
                'folio' => $_POST['folio'] ?? null,
                'fecha_compra' => $_POST['fecha_compra'] ?? date('Y-m-d'),
                'notas' => $_POST['notas'] ?? null,
            ];

            $detalles = $this->detallesFormulario();
            if (!$detalles) {
                throw new Exception('La compra requiere al menos un producto');
            }

            $detallesPorProveedor = $this->agruparDetallesPorProveedor($detalles, $datos['proveedor_id']);
            $comprasCreadas = $this->compraService->crearBorradoresPorProveedor(
                $hotelId,
                $datos,
                $detallesPorProveedor,
                $this->usuarioIdActual()
            );

            clear_old_input();
            if (count($comprasCreadas) === 1) {
                set_mensaje('Borrador de compra #' . (int)($comprasCreadas[0]['compra_id'] ?? 0) . ' creado correctamente.', 'success');
            } else {
                $compraIds = array_map(static function ($compra) {
                    return '#' . (int)($compra['compra_id'] ?? 0);
                }, $comprasCreadas);
                set_mensaje(
                    'Se crearon ' . count($comprasCreadas) . ' borradores de compra por proveedor: ' . implode(', ', $compraIds) . '.',
                    'success'
                );
            }
            $this->redirect('compras');
        } catch (Throwable $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposCompra([$e->getMessage()]));
            $this->redirect('compras/crear');
        }
    }

    public function actualizarAction(): void
    {
        require_permission('compras.all');
        $compraId = (int)($this->route_params['id'] ?? 0);
        if (!$this->isPost() || $compraId <= 0) {
            $this->redirect('compras');
            return;
        }

        $this->validateCSRF();

        try {
            $detalles = $this->detallesFormulario();
            if (!$detalles) {
                throw new Exception('La compra requiere al menos un producto');
            }

            $this->compraService->actualizarBorrador(
                $this->hotelIdActual(),
                $compraId,
                [
                    'proveedor_id' => $_POST['proveedor_id'] ?? null,
                    'folio' => $_POST['folio'] ?? null,
                    'fecha_compra' => $_POST['fecha_compra'] ?? date('Y-m-d'),
                    'notas' => $_POST['notas'] ?? null,
                ],
                $detalles,
                $this->usuarioIdActual()
            );

            clear_old_input();
            set_mensaje('Compra #' . $compraId . ' actualizada correctamente.', 'success');
            $this->redirect('compras/' . $compraId);
        } catch (Throwable $e) {
            set_mensaje('Error al actualizar la compra: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposCompra([$e->getMessage()]));
            $this->redirect('compras/' . $compraId . '/editar');
        }
    }

    public function cancelarAction(): void
    {
        require_permission('compras.all');
        $compraId = (int)($this->route_params['id'] ?? 0);
        if (!$this->isPost() || $compraId <= 0) {
            $this->redirect('compras');
            return;
        }

        $this->validateCSRF();

        try {
            $this->compraService->cancelarBorrador(
                $this->hotelIdActual(),
                $compraId,
                $this->usuarioIdActual()
            );
            set_mensaje('Compra #' . $compraId . ' cancelada. No se modifico el inventario.', 'success');
        } catch (Throwable $e) {
            set_mensaje('Error al cancelar la compra: ' . $e->getMessage(), 'error');
        }

        $this->redirect('compras/' . $compraId);
    }

    public function recibirAction(): void
    {
        // Accion separada de compras.all: recibir suma stock al inventario y
        // habilita la CxP; puede otorgarse sola (compras.recibir) a un rol
        // operativo de almacen sin darle editar/cancelar borradores.
        require_permission('compras.recibir');
        if (!$this->isPost()) {
            $this->redirect('compras');
            return;
        }

        $this->validateCSRF();

        try {
            $compraId = (int)($this->route_params['id'] ?? 0);
            if ($compraId <= 0) {
                throw new Exception('Compra invalida');
            }

            $resultado = $this->compraService->recibirCompra(
                $this->hotelIdActual(),
                $compraId,
                $this->usuarioIdActual()
            );
            $movimientos = isset($resultado['movimientos']) && is_array($resultado['movimientos'])
                ? count($resultado['movimientos'])
                : 0;

            set_mensaje(
                'Compra #' . $compraId . ' recibida correctamente. Movimientos de inventario: ' . $movimientos . '.',
                'success'
            );
            $this->redirect('compras?estado=recibida');
        } catch (Throwable $e) {
            set_mensaje('Error al recibir compra: ' . $e->getMessage(), 'error');
            $this->redirect('compras');
        }
    }

    private function erroresCamposCompra(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'proveedor') !== false) {
                $campo = 'proveedor_id';
            } elseif (strpos($lower, 'folio') !== false) {
                $campo = 'folio';
            } elseif (strpos($lower, 'fecha') !== false) {
                $campo = 'fecha_compra';
            } elseif (strpos($lower, 'producto') !== false || strpos($lower, 'detalle') !== false || strpos($lower, 'linea') !== false) {
                $campo = 'producto_id_0';
            } elseif (strpos($lower, 'cantidad') !== false) {
                $campo = 'cantidad_0';
            } elseif (strpos($lower, 'costo') !== false || strpos($lower, 'importe') !== false) {
                $campo = 'costo_unitario_0';
            } elseif (strpos($lower, 'nota') !== false) {
                $campo = 'notas';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function detallesFormulario(): array
    {
        $productoIds = $this->normalizarArregloPost('producto_id');
        $cantidades = $this->normalizarArregloPost('cantidad');
        $costos = $this->normalizarArregloPost('costo_unitario');
        $proveedoresLinea = $this->normalizarArregloPost('detalle_proveedor_id');

        $detalles = [];
        $maxRows = max(count($productoIds), count($cantidades), count($costos), count($proveedoresLinea));
        for ($i = 0; $i < $maxRows; $i++) {
            $productoId = trim((string)($productoIds[$i] ?? ''));
            $cantidad = trim((string)($cantidades[$i] ?? ''));
            $costo = trim((string)($costos[$i] ?? ''));
            $proveedorLinea = trim((string)($proveedoresLinea[$i] ?? ''));

            if ($productoId === '' && $cantidad === '' && $costo === '') {
                continue;
            }

            $detalle = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
            ];

            if ($costo !== '') {
                $detalle['costo_unitario'] = $costo;
            }

            if ($proveedorLinea !== '') {
                $detalle['proveedor_id'] = $proveedorLinea;
            }

            $detalles[] = $detalle;
        }

        return $detalles;
    }

    private function agruparDetallesPorProveedor(array $detalles, $proveedorGeneral): array
    {
        $necesitaProveedorGeneral = false;
        foreach ($detalles as $detalle) {
            if (trim((string)($detalle['proveedor_id'] ?? '')) === '') {
                $necesitaProveedorGeneral = true;
                break;
            }
        }

        $proveedorGeneralId = null;
        if ($necesitaProveedorGeneral) {
            $proveedorGeneralId = $this->validarProveedorFormulario(
                $proveedorGeneral,
                'Elige un proveedor general o selecciona proveedor en cada producto'
            );
        }

        $grupos = [];
        foreach ($detalles as $detalle) {
            $proveedorLinea = trim((string)($detalle['proveedor_id'] ?? ''));
            $proveedorId = $proveedorLinea !== ''
                ? $this->validarProveedorFormulario($proveedorLinea)
                : $proveedorGeneralId;

            $detalleNormal = $detalle;
            unset($detalleNormal['proveedor_id']);

            if (!isset($grupos[$proveedorId])) {
                $grupos[$proveedorId] = [];
            }
            $grupos[$proveedorId][] = $detalleNormal;
        }

        return $grupos;
    }

    private function validarProveedorFormulario($value, string $message = 'Proveedor invalido'): int
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || !ctype_digit($text) || (int)$text <= 0) {
            throw new Exception($message);
        }

        return (int)$text;
    }

    private function normalizarArregloPost(string $key): array
    {
        $value = $_POST[$key] ?? [];
        if (!is_array($value)) {
            return [$value];
        }

        return array_values($value);
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function usuarioIdActual(): ?int
    {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }
}
