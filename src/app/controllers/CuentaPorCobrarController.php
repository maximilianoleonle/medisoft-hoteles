<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';
require_once __DIR__ . '/../models/Reservacion.php';
require_once __DIR__ . '/../services/CuentaPorCobrarCobroService.php';
require_once __DIR__ . '/../services/CuentaPorCobrarReversionCobroService.php';

class CuentaPorCobrarController extends Controller
{
    private $cuentaModel;
    private $cobroService;
    private $reversionCobroService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorCobrar();
        $this->cobroService = new CuentaPorCobrarCobroService();
        $this->reversionCobroService = new CuentaPorCobrarReversionCobroService();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        // CxC se retiro de la oferta comercial. Este controlador permanece
        // accesible por permiso para consultar y liquidar cuentas historicas.
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_cobrar.view');
        }

        return true;
    }

    public function indexAction(): void
    {
        // Compatibilidad con favoritos antiguos: la portada retirada lleva al
        // historial operativo, nunca vuelve a ofrecer saldos derivados nuevos.
        $this->redirect('cuentas-por-cobrar/operativas');
    }

    public function operativasAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
        ];

        $tablaDisponible = $this->cuentaModel->tablaOperativaDisponible();
        $cuentas = $tablaDisponible ? $this->cuentaModel->listarOperativasPorHotel($hotelId, $filtros, 100) : [];
        $resumen = $tablaDisponible
            ? $this->cuentaModel->resumenOperativasPorHotel($hotelId)
            : [
                'total' => 0,
                'pendientes' => 0,
                'parciales' => 0,
                'liquidadas' => 0,
                'vencidas' => 0,
                'canceladas' => 0,
                'incobrables' => 0,
                'total_importe' => '0.00',
                'saldo_total' => '0.00',
                'saldo_vencido' => '0.00',
            ];

        View::renderTemplate('cuentas_por_cobrar/operativas', [
            'title' => 'Cuentas operativas - ' . current_hotel_display_name(),
            'cuentas' => $cuentas,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function verOperativaAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $puedeGestionarCobros = function_exists('can') && can('cuentas_por_cobrar.cobrar');
        $cuenta = $this->cuentaModel->buscarOperativaPorIdHotel($id, $hotelId);

        if (!$cuenta) {
            set_mensaje('Cuenta por cobrar operativa no encontrada para el hotel actual.', 'error');
            $this->redirect('cuentas-por-cobrar/operativas');
            return;
        }

        $cobroCaja = [
            'elegible' => false,
            'motivo_bloqueo' => 'No se pudo evaluar el cobro con Caja.',
            'corte' => null,
            'metodos_pago' => [],
            'monto_maximo' => '0.00',
        ];
        $cobroToken = null;
        if ($puedeGestionarCobros) {
            try {
                $cobroCaja = $this->cobroService->evaluarCobro($hotelId, $id);
                if (!empty($cobroCaja['elegible'])) {
                    $cobroToken = $this->generarCobroToken($id);
                }
            } catch (Throwable $e) {
                $cobroCaja['motivo_bloqueo'] = $e->getMessage();
            }
        }

        $movimientos = $this->cuentaModel->movimientosOperativosPorCuenta($id, $hotelId, 100);
        $reversionesCobro = [];
        $reversionTokens = [];
        foreach ($puedeGestionarCobros ? $movimientos : [] as $movimiento) {
            if ((string)($movimiento['tipo_movimiento'] ?? '') !== 'COBRO') {
                continue;
            }

            $movimientoId = (int)($movimiento['id'] ?? 0);
            if ($movimientoId <= 0) {
                continue;
            }

            try {
                $reversionesCobro[$movimientoId] = $this->reversionCobroService->evaluarReversion($hotelId, $id, $movimientoId);
                if (!empty($reversionesCobro[$movimientoId]['elegible'])) {
                    $reversionTokens[$movimientoId] = $this->generarReversionCobroToken($id, $movimientoId);
                }
            } catch (Throwable $e) {
                $reversionesCobro[$movimientoId] = [
                    'elegible' => false,
                    'motivo_bloqueo' => $e->getMessage(),
                    'monto' => $movimiento['monto'] ?? '0.00',
                ];
            }
        }

        View::renderTemplate('cuentas_por_cobrar/ver_operativa', [
            'title' => 'CxC operativa #' . $id . ' - ' . current_hotel_display_name(),
            'cuenta' => $cuenta,
            'movimientos' => $movimientos,
            'movimientosDisponibles' => $this->cuentaModel->movimientosOperativosDisponibles(),
            'cobroCaja' => $cobroCaja,
            'cobroToken' => $cobroToken,
            'reversionesCobro' => $reversionesCobro,
            'reversionTokens' => $reversionTokens,
            'puedeGestionarCobros' => $puedeGestionarCobros,
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
            ? $this->cuentaModel->simuladorCajaCliente($hotelId, $filtros, 200)
            : [
                'corte' => null,
                'cuentas' => [],
                'resumen' => [],
                'tipo_cobro_disponible' => false,
            ];

        View::renderTemplate('cuentas_por_cobrar/simulador_caja', [
            'title' => 'Simulador Caja CxC - ' . current_hotel_display_name(),
            'cuentas' => $datos['cuentas'] ?? [],
            'resumen' => $datos['resumen'] ?? [],
            'corte' => $datos['corte'] ?? null,
            'tipoCobroDisponible' => !empty($datos['tipo_cobro_disponible']),
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function registrarCobroCajaAction(): void
    {
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_cobrar.cobrar');
        }
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-cobrar/operativas');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $cuentaId = (int)($this->route_params['id'] ?? 0);
        try {
            if (!$this->consumirCobroToken($cuentaId, (string)$this->getPost('cobro_token', ''))) {
                throw new Exception('Token de cobro invalido o ya utilizado; recarga la cuenta antes de reintentar');
            }

            $resultado = $this->cobroService->registrarCobro(
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

            $advertenciaFactura = $this->sincronizarFacturaPorCobroCxc($cuentaId, $resultado);

            set_mensaje(
                'Cobro registrado en Caja. Saldo pendiente del cliente: $'
                . number_format((float)$resultado['saldo_posterior'], 2) . '.'
                . ($advertenciaFactura ?? ''),
                'success'
            );
            clear_old_input();
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el cobro CxC');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposCobro([$e->getMessage()]));
        }

        $this->redirect($cuentaId > 0 ? 'cuentas-por-cobrar/operativas/' . $cuentaId : 'cuentas-por-cobrar/operativas');
    }

    public function revertirCobroCajaAction(): void
    {
        if (function_exists('require_permission')) {
            require_permission('cuentas_por_cobrar.cobrar');
        }
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-cobrar/operativas');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $cuentaId = (int)($this->route_params['id'] ?? 0);
        $movimientoId = (int)($this->route_params['movimientoid'] ?? $this->route_params['movimientoId'] ?? 0);

        try {
            if (!$this->consumirReversionCobroToken($cuentaId, $movimientoId, (string)$this->getPost('reversion_token', ''))) {
                throw new Exception('Token de reversion invalido o ya utilizado; recarga la cuenta antes de reintentar');
            }

            $resultado = $this->reversionCobroService->revertirCobro(
                $this->hotelIdActual(),
                $cuentaId,
                $movimientoId,
                [
                    'motivo' => $this->getPost('motivo'),
                ],
                $this->usuarioIdActual()
            );

            $advertenciaFactura = $this->sincronizarFacturaPorReversionCxc($cuentaId, $resultado);

            set_mensaje(
                'Cobro revertido: el dinero salió de Caja. Saldo pendiente del cliente: $'
                . number_format((float)$resultado['saldo_posterior'], 2) . '.'
                . ($advertenciaFactura ?? ''),
                'success'
            );
            clear_old_input();
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'revertir el cobro CxC');
            $oldInput = $_POST;
            $oldInput['reversion_movimiento_id'] = $movimientoId;
            save_old_input($oldInput);
            save_form_errors($this->erroresCamposReversionCobro([$e->getMessage()]));
        }

        $this->redirect($cuentaId > 0 ? 'cuentas-por-cobrar/operativas/' . $cuentaId : 'cuentas-por-cobrar/operativas');
    }

    private function sincronizarFacturaPorCobroCxc(int $cuentaId, array $resultado): ?string
    {
        try {
            $hotelId = $this->hotelIdActual();
            $cuenta = $this->cuentaModel->buscarOperativaPorIdHotel($cuentaId, $hotelId);

            if (
                !$cuenta
                || empty($cuenta['reservacion_id'])
                || empty($cuenta['solicitud_factura_id'])
                || (string)($cuenta['factura_requiere_factura'] ?? '') !== 'si'
                || (string)($cuenta['factura_tipo'] ?? '') !== 'cliente'
            ) {
                return null;
            }

            $monto = round((float)($resultado['monto'] ?? 0), 2);
            if ($monto <= 0.004) {
                return null;
            }

            $metodoPago = strtolower(trim((string)$this->getPost('metodo_pago', 'efectivo')));
            if (!in_array($metodoPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
                $metodoPago = 'efectivo';
            }

            $referencia = trim((string)$this->getPost('referencia', ''));
            $movimientoCxcId = (int)($resultado['cuenta_por_cobrar_movimiento_id'] ?? 0);
            $nota = 'Cobro CxC #' . $cuentaId
                . ' aplicado a factura - Movimiento CxC #'
                . $movimientoCxcId;
            if ($referencia !== '') {
                $nota .= ' | Ref: ' . $referencia;
            }

            $reservacionModel = new Reservacion();
            $resultadoFactura = $reservacionModel->crearSolicitudFactura([
                'reservacion_id' => (int)$cuenta['reservacion_id'],
                'hotel_id' => $hotelId,
                'requiere_factura' => 'si',
                'tipo' => 'cliente',
                'estatus' => 'pendiente',
                'metodo_pago_principal' => $metodoPago,
                'monto_total' => $monto,
                'usuario_registro_id' => $this->usuarioIdActual(),
                'notas' => $nota,
                'modo_monto' => 'acumular',
                'modo_solicitud' => $this->modoSolicitudFactura($this->getPost('factura_modo_cxc', 'acumular')),
            ]);

            if (!$resultadoFactura) {
                return ' La factura vinculada no se actualizo automaticamente; revisa Facturacion.';
            }
        } catch (Throwable $e) {
            error_log('No se pudo sincronizar factura desde cobro CxC #' . $cuentaId . ': ' . $e->getMessage());
            return ' La factura vinculada no se actualizo automaticamente; revisa Facturacion.';
        }

        return null;
    }

    private function sincronizarFacturaPorReversionCxc(int $cuentaId, array $resultado): ?string
    {
        try {
            $hotelId = $this->hotelIdActual();
            $cuenta = $this->cuentaModel->buscarOperativaPorIdHotel($cuentaId, $hotelId);

            if (
                !$cuenta
                || empty($cuenta['reservacion_id'])
                || empty($cuenta['solicitud_factura_id'])
                || (string)($cuenta['factura_requiere_factura'] ?? '') !== 'si'
                || (string)($cuenta['factura_tipo'] ?? '') !== 'cliente'
            ) {
                return null;
            }

            $monto = round((float)($resultado['monto'] ?? 0), 2);
            if ($monto <= 0.004) {
                return null;
            }

            $nota = 'Reversion de cobro CxC #' . $cuentaId
                . ' - Movimiento CxC #'
                . (int)($resultado['movimiento_cobro_id'] ?? 0);

            $reservacionModel = new Reservacion();
            $resultadoFactura = $reservacionModel->crearSolicitudFactura([
                'reservacion_id' => (int)$cuenta['reservacion_id'],
                'hotel_id' => $hotelId,
                'requiere_factura' => 'si',
                'tipo' => 'cliente',
                'estatus' => 'pendiente',
                'metodo_pago_principal' => 'efectivo',
                'monto_total' => -1 * $monto,
                'usuario_registro_id' => $this->usuarioIdActual(),
                'notas' => $nota,
                'modo_monto' => 'acumular',
                'referencia_nota' => 'Movimiento CxC #' . (int)($resultado['movimiento_cobro_id'] ?? 0),
            ]);

            if (!$resultadoFactura) {
                return ' La factura vinculada no se ajusto automaticamente (puede estar ya facturada); revisala en Facturacion.';
            }
        } catch (Throwable $e) {
            error_log('No se pudo ajustar factura desde reversion CxC #' . $cuentaId . ': ' . $e->getMessage());
            return ' La factura vinculada no se ajusto automaticamente; revisa Facturacion.';
        }

        return null;
    }

    private function erroresCamposCobro(array $errores): array
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

    private function erroresCamposReversionCobro(array $errores): array
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

    private function modoSolicitudFactura($valor): string
    {
        return trim((string)$valor) === 'separada' ? 'separada' : 'actualizar';
    }

    private function generarCobroToken(int $cuentaId): string
    {
        if (!isset($_SESSION['cxc_cobro_tokens']) || !is_array($_SESSION['cxc_cobro_tokens'])) {
            $_SESSION['cxc_cobro_tokens'] = [];
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['cxc_cobro_tokens'][$cuentaId] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirCobroToken(int $cuentaId, string $token): bool
    {
        $token = trim($token);
        $registro = $_SESSION['cxc_cobro_tokens'][$cuentaId] ?? null;
        unset($_SESSION['cxc_cobro_tokens'][$cuentaId]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }

    private function generarReversionCobroToken(int $cuentaId, int $movimientoId): string
    {
        if (!isset($_SESSION['cxc_reversion_cobro_tokens']) || !is_array($_SESSION['cxc_reversion_cobro_tokens'])) {
            $_SESSION['cxc_reversion_cobro_tokens'] = [];
        }

        $key = $cuentaId . ':' . $movimientoId;
        $token = bin2hex(random_bytes(16));
        $_SESSION['cxc_reversion_cobro_tokens'][$key] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirReversionCobroToken(int $cuentaId, int $movimientoId, string $token): bool
    {
        $token = trim($token);
        $key = $cuentaId . ':' . $movimientoId;
        $registro = $_SESSION['cxc_reversion_cobro_tokens'][$key] ?? null;
        unset($_SESSION['cxc_reversion_cobro_tokens'][$key]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }
}
