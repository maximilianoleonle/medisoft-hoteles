<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorPagar.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/CuentaPorPagarPagoService.php';
require_once __DIR__ . '/../services/CuentaPorPagarReversionPagoService.php';

class CuentaPorPagarController extends Controller
{
    private $cuentaModel;
    private $pagoService;
    private $reversionPagoService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorPagar();
        $this->pagoService = new CuentaPorPagarPagoService();
        $this->reversionPagoService = new CuentaPorPagarReversionPagoService();
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

        // Acceso base de lectura. Las acciones de dinero (registrar/revertir
        // pago, generar CxP) piden cuentas_por_pagar.pagar, configurable por
        // rol desde el editor de roles del hotel.
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_pagar.view');
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
            if (puede_ver_documentos_vinculados()) {
                $documentoModel = new Documento();
                $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'cuenta_por_pagar', $id, 10);
            }
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

        $movimientos = $this->cuentaModel->movimientosPorCuenta($id, $hotelId, 100);
        $reversionesPago = [];
        $reversionTokens = [];
        $movimientosPago = [];
        foreach ($movimientos as $movimiento) {
            if ((string)($movimiento['tipo_movimiento'] ?? '') !== 'PAGO_REFERENCIAL') {
                continue;
            }

            $movimientoId = (int)($movimiento['id'] ?? 0);
            if ($movimientoId <= 0) {
                continue;
            }

            $movimientosPago[] = $movimiento;
            try {
                $reversionesPago[$movimientoId] = $this->reversionPagoService->evaluarReversion($hotelId, $id, $movimientoId);
                if (!empty($reversionesPago[$movimientoId]['elegible'])) {
                    $reversionTokens[$movimientoId] = $this->generarReversionPagoToken($id, $movimientoId);
                }
            } catch (Throwable $e) {
                $reversionesPago[$movimientoId] = [
                    'elegible' => false,
                    'motivo_bloqueo' => $e->getMessage(),
                ];
            }
        }

        View::renderTemplate('cuentas_por_pagar/ver', [
            'title' => 'Cuenta por pagar #' . $id . ' - ' . current_hotel_display_name(),
            'cuenta' => $cuenta,
            'movimientos' => $movimientos,
            'movimientosDisponibles' => $this->cuentaModel->movimientosDisponibles(),
            'pagoCaja' => $pagoCaja,
            'pagoToken' => $pagoToken,
            'reversionesPago' => $reversionesPago,
            'reversionTokens' => $reversionTokens,
            'movimientosPago' => $movimientosPago,
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
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_pagar.pagar');
        }
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
            set_mensaje_error_op($e, 'generar la cuenta por pagar');
            $this->redirect('cuentas-por-pagar/generacion-preview');
        }
    }

    public function registrarPagoCajaAction(): void
    {
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_pagar.pagar');
        }
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
                'Pago al proveedor registrado: el dinero salió de Caja. Queda por pagar: $'
                . number_format((float)$resultado['saldo_posterior'], 2) . '.',
                'success'
            );
            clear_old_input();
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el pago proveedor');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposPago([$e->getMessage()]));
        }

        $this->redirect($cuentaId > 0 ? 'cuentas-por-pagar/' . $cuentaId : 'cuentas-por-pagar');
    }

    public function revertirPagoCajaAction(): void
    {
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_pagar.pagar');
        }
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-pagar');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $cuentaId = (int)($this->route_params['id'] ?? 0);
        $movimientoId = (int)($this->route_params['movimientoid'] ?? $this->route_params['movimientoId'] ?? 0);
        try {
            if (!$this->consumirReversionPagoToken($cuentaId, $movimientoId, (string)$this->getPost('reversion_token', ''))) {
                throw new Exception('Token de reversion invalido o ya utilizado; recarga la cuenta antes de reintentar');
            }

            $resultado = $this->reversionPagoService->revertirPago(
                $this->hotelIdActual(),
                $cuentaId,
                $movimientoId,
                [
                    'motivo' => $this->getPost('motivo'),
                ],
                $this->usuarioIdActual()
            );

            set_mensaje(
                'Pago revertido: el dinero regresó a Caja. Queda por pagar: $'
                . number_format((float)$resultado['saldo_posterior'], 2) . '.',
                'success'
            );
            clear_old_input();
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'revertir el pago proveedor');
            $oldInput = $_POST;
            $oldInput['reversion_movimiento_id'] = $movimientoId;
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposReversionPago([$e->getMessage()]));
        }

        $this->redirect($cuentaId > 0 ? 'cuentas-por-pagar/' . $cuentaId : 'cuentas-por-pagar');
    }

    private function erroresCamposPago(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'monto') !== false || strpos($lower, 'saldo') !== false || strpos($lower, 'importe') !== false) {
                $campo = 'monto';
            } elseif (strpos($lower, 'metodo') !== false) {
                $campo = 'metodo_pago';
            } elseif (strpos($lower, 'referencia') !== false || strpos($lower, 'folio') !== false) {
                $campo = 'referencia';
            } elseif (strpos($lower, 'nota') !== false || strpos($lower, 'observacion') !== false) {
                $campo = 'notas';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function erroresCamposReversionPago(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            if (
                strpos($lower, 'motivo') !== false
                || strpos($lower, 'razon') !== false
                || strpos($lower, 'explica') !== false
            ) {
                $fieldErrors['motivo'][] = $mensaje;
            }
        }

        return $fieldErrors;
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

    private function generarReversionPagoToken(int $cuentaId, int $movimientoId): string
    {
        if (!isset($_SESSION['cxp_reversion_pago_tokens']) || !is_array($_SESSION['cxp_reversion_pago_tokens'])) {
            $_SESSION['cxp_reversion_pago_tokens'] = [];
        }

        $key = $cuentaId . ':' . $movimientoId;
        $token = bin2hex(random_bytes(16));
        $_SESSION['cxp_reversion_pago_tokens'][$key] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirReversionPagoToken(int $cuentaId, int $movimientoId, string $token): bool
    {
        $token = trim($token);
        $key = $cuentaId . ':' . $movimientoId;
        $registro = $_SESSION['cxp_reversion_pago_tokens'][$key] ?? null;
        unset($_SESSION['cxp_reversion_pago_tokens'][$key]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }
}
