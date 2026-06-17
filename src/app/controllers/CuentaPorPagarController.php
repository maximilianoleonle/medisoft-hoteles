<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorPagar.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/CuentaPorPagarPagoService.php';

class CuentaPorPagarController extends Controller
{
    private $cuentaModel;
    private $pagoService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorPagar();
        $this->pagoService = new CuentaPorPagarPagoService();
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

        $pagoCaja = [
            'elegible' => false,
            'motivo_bloqueo' => 'No se pudo evaluar el pago con Caja.',
            'corte' => null,
            'metodos_pago' => [],
            'monto_maximo' => '0.00',
        ];
        $pagoToken = null;
        try {
            $pagoCaja = $this->pagoService->evaluarPago($hotelId, $id);
            if (!empty($pagoCaja['elegible'])) {
                $pagoToken = $this->generarPagoToken($id);
            }
        } catch (Throwable $e) {
            $pagoCaja['motivo_bloqueo'] = $e->getMessage();
        }

        View::renderTemplate('cuentas_por_pagar/ver', [
            'title' => 'Cuenta por pagar #' . $id . ' - ' . current_hotel_display_name(),
            'cuenta' => $cuenta,
            'movimientos' => $this->cuentaModel->movimientosPorCuenta($id, $hotelId, 100),
            'movimientosDisponibles' => $this->cuentaModel->movimientosDisponibles(),
            'pagoCaja' => $pagoCaja,
            'pagoToken' => $pagoToken,
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

    public function registrarPagoCajaAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-pagar');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $cuentaId = (int)($this->route_params['id'] ?? 0);
        try {
            if (!$this->consumirPagoToken($cuentaId, (string)$this->getPost('pago_token', ''))) {
                throw new Exception('Token de pago invalido o ya utilizado; recarga la cuenta antes de reintentar');
            }

            $resultado = $this->pagoService->registrarPago(
                $this->hotelIdActual(),
                $cuentaId,
                [
                    'monto' => $this->getPost('monto'),
                    'metodo_pago' => $this->getPost('metodo_pago'),
                    'referencia' => $this->getPost('referencia'),
                    'notas' => $this->getPost('notas'),
                ],
                $this->usuarioIdActual()
            );

            set_mensaje(
                'Pago de proveedor registrado. Movimiento Caja #' . (int)$resultado['movimiento_caja_id']
                . ', saldo nuevo ' . number_format((float)$resultado['saldo_posterior'], 2) . '.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje('No se pudo registrar el pago proveedor: ' . $e->getMessage(), 'error');
        }

        $this->redirect($cuentaId > 0 ? 'cuentas-por-pagar/' . $cuentaId : 'cuentas-por-pagar');
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

    private function generarPagoToken(int $cuentaId): string
    {
        if (!isset($_SESSION['cxp_pago_tokens']) || !is_array($_SESSION['cxp_pago_tokens'])) {
            $_SESSION['cxp_pago_tokens'] = [];
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['cxp_pago_tokens'][$cuentaId] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirPagoToken(int $cuentaId, string $token): bool
    {
        $token = trim($token);
        $registro = $_SESSION['cxp_pago_tokens'][$cuentaId] ?? null;
        unset($_SESSION['cxp_pago_tokens'][$cuentaId]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }
}
