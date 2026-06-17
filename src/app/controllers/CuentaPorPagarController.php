<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorPagar.php';
require_once __DIR__ . '/../models/Documento.php';

class CuentaPorPagarController extends Controller
{
    private $cuentaModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorPagar();
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
            'estado' => $this->getQuery('estado', 'todos'),
        ];

        $tablaDisponible = $this->cuentaModel->tablaDisponible();
        $cuentas = $tablaDisponible ? $this->cuentaModel->listarPorHotel($hotelId, $filtros, 100) : [];
        $resumen = $tablaDisponible ? $this->cuentaModel->resumenPorHotel($hotelId) : [
            'total' => 0,
            'pendientes' => 0,
            'parciales' => 0,
            'pagadas' => 0,
            'vencidas' => 0,
            'canceladas' => 0,
            'total_importe' => '0.00',
            'saldo_total' => '0.00',
            'saldo_vencido' => '0.00',
        ];

        View::renderTemplate('cuentas_por_pagar/index', [
            'title' => 'Cuentas por pagar - ' . current_hotel_display_name(),
            'cuentas' => $cuentas,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function verAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $cuenta = $this->cuentaModel->buscarPorIdHotel($id, $hotelId);

        if (!$cuenta) {
            set_mensaje('Cuenta por pagar no encontrada para el hotel actual.', 'error');
            $this->redirect('cuentas-por-pagar');
            return;
        }

        $documentosEntidad = [];
        try {
            $documentoModel = new Documento();
            $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'cuenta_por_pagar', $id, 10);
        } catch (Throwable $e) {
            $documentosEntidad = [];
        }

        View::renderTemplate('cuentas_por_pagar/ver', [
            'title' => 'Cuenta por pagar #' . $id . ' - ' . current_hotel_display_name(),
            'cuenta' => $cuenta,
            'movimientos' => $this->cuentaModel->movimientosPorCuenta($id, $hotelId, 100),
            'movimientosDisponibles' => $this->cuentaModel->movimientosDisponibles(),
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'cuenta_por_pagar',
                'id' => $id,
                'label' => 'Cuenta por pagar',
            ],
        ]);
    }

    public function generacionPreviewAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'recibida'),
        ];

        $tablaDisponible = $this->cuentaModel->tablasPreviewGeneracionDisponibles();
        $compras = $tablaDisponible
            ? $this->cuentaModel->previewGeneracionDesdeCompras($hotelId, $filtros, 200)
            : [];
        $resumen = $this->cuentaModel->resumenPreviewGeneracion($compras);

        View::renderTemplate('cuentas_por_pagar/generacion_preview', [
            'title' => 'Preview generacion CxP - ' . current_hotel_display_name(),
            'compras' => $compras,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function simuladorCajaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
        ];

        $tablaDisponible = $this->cuentaModel->tablasSimuladorCajaDisponibles();
        $datos = $tablaDisponible
            ? $this->cuentaModel->simuladorCajaProveedor($hotelId, $filtros, 200)
            : ['cuentas' => [], 'resumen' => [], 'corte' => null];

        View::renderTemplate('cuentas_por_pagar/simulador_caja', [
            'title' => 'Simulador Caja CxP - ' . current_hotel_display_name(),
            'cuentas' => $datos['cuentas'] ?? [],
            'resumen' => $datos['resumen'] ?? [],
            'corte' => $datos['corte'] ?? null,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function generarDesdeCompraAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-pagar/generacion-preview');
            return;
        }

        $this->validateCSRF();

        $compraId = (int)($this->route_params['id'] ?? 0);
        try {
            $resultado = $this->cuentaModel->generarDesdeCompraRecibida(
                $this->hotelIdActual(),
                $compraId,
                $this->usuarioIdActual()
            );

            $cxpId = (int)($resultado['cxp_id'] ?? 0);
            set_mensaje('Cuenta por pagar #' . $cxpId . ' generada desde compra #' . $compraId . '.', 'success');
            $this->redirect('cuentas-por-pagar/' . $cxpId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo generar la cuenta por pagar: ' . $e->getMessage(), 'error');
            $this->redirect('cuentas-por-pagar/generacion-preview');
        }
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
