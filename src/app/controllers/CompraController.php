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
            require_hotel_module('inventario');
        }

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'borrador'),
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
            $compraId = $this->compraService->crearBorrador(
                $hotelId,
                $datos,
                $detalles,
                $this->usuarioIdActual()
            );

            set_mensaje('Borrador de compra #' . $compraId . ' creado correctamente.', 'success');
            $this->redirect('compras');
        } catch (Throwable $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            $this->redirect('compras/crear');
        }
    }

    public function recibirAction(): void
    {
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

    private function detallesFormulario(): array
    {
        $productoIds = $this->normalizarArregloPost('producto_id');
        $cantidades = $this->normalizarArregloPost('cantidad');
        $costos = $this->normalizarArregloPost('costo_unitario');

        $detalles = [];
        $maxRows = max(count($productoIds), count($cantidades), count($costos));
        for ($i = 0; $i < $maxRows; $i++) {
            $productoId = trim((string)($productoIds[$i] ?? ''));
            $cantidad = trim((string)($cantidades[$i] ?? ''));
            $costo = trim((string)($costos[$i] ?? ''));

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

            $detalles[] = $detalle;
        }

        return $detalles;
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
