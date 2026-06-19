<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';

class CuentaPorCobrarController extends Controller
{
    private $cuentaModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->cuentaModel = new CuentaPorCobrar();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('reservaciones');
        }

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado_reservacion' => $this->getQuery('estado_reservacion', 'todas'),
            'estado_saldo' => $this->getQuery('estado_saldo', 'pendiente'),
        ];

        $tablaDisponible = $this->cuentaModel->tablasDisponibles();
        $cuentas = $tablaDisponible ? $this->cuentaModel->listarDerivadasPorHotel($hotelId, $filtros, 300) : [];

        View::renderTemplate('cuentas_por_cobrar/index', [
            'title' => 'Cuentas por cobrar - ' . current_hotel_display_name(),
            'cuentas' => $cuentas,
            'resumen' => $this->cuentaModel->resumenPorCuentas($cuentas),
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
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
            'title' => 'CxC operativa read-only - ' . current_hotel_display_name(),
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
        $cuenta = $this->cuentaModel->buscarOperativaPorIdHotel($id, $hotelId);

        if (!$cuenta) {
            set_mensaje('Cuenta por cobrar operativa no encontrada para el hotel actual.', 'error');
            $this->redirect('cuentas-por-cobrar/operativas');
            return;
        }

        View::renderTemplate('cuentas_por_cobrar/ver_operativa', [
            'title' => 'CxC operativa #' . $id . ' - ' . current_hotel_display_name(),
            'cuenta' => $cuenta,
            'movimientos' => $this->cuentaModel->movimientosOperativosPorCuenta($id, $hotelId, 100),
            'movimientosDisponibles' => $this->cuentaModel->movimientosOperativosDisponibles(),
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

    public function generarDesdeReservacionAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('cuentas-por-cobrar');
            return;
        }

        $this->validateCSRF();

        $reservacionId = (int)($this->route_params['id'] ?? 0);
        try {
            $resultado = $this->cuentaModel->generarDesdeReservacionElegible(
                $this->hotelIdActual(),
                $reservacionId,
                $this->usuarioIdActual()
            );

            $cxcId = (int)($resultado['cxc_id'] ?? 0);
            set_mensaje(
                'Cuenta por cobrar #' . $cxcId . ' generada desde reservacion #' . $reservacionId . '.',
                'success'
            );
            $this->redirect('cuentas-por-cobrar/operativas/' . $cxcId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo generar la cuenta por cobrar: ' . $e->getMessage(), 'error');
            $this->redirect('cuentas-por-cobrar');
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
