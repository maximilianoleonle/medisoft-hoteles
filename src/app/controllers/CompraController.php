<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
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
