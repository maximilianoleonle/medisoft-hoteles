<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Trabajador.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/TrabajadorPagoCajaService.php';
require_once __DIR__ . '/../services/TrabajadorNominaPeriodoService.php';
require_once __DIR__ . '/../services/TrabajadorNominaSnapshotPagoService.php';
require_once __DIR__ . '/../services/TrabajadorReciboLaboralPdfService.php';

class TrabajadorController extends Controller
{
    private $trabajadorModel;
    private $tareaModel;
    private $pagoCajaService;
    private $nominaPeriodoService;
    private $snapshotPagoService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->trabajadorModel = new Trabajador();
        $this->tareaModel = new TareaOperativa();
        $this->pagoCajaService = new TrabajadorPagoCajaService();
        $this->nominaPeriodoService = new TrabajadorNominaPeriodoService($this->trabajadorModel);
        $this->snapshotPagoService = new TrabajadorNominaSnapshotPagoService();
    }

    /**
     * Lista ligera (id, nombre, estado) para poblar los selectores de trabajador
     * de las barras de filtro. Incluye inactivos/bajas para poder consultar su historial.
     */
    private function trabajadoresParaFiltro(int $hotelId): array
    {
        if (!$this->trabajadorModel->tablaDisponible()) {
            return [];
        }
        return $this->trabajadorModel->listarPorHotel($hotelId, ['estado' => 'todos'], 300);
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('personal');
        }

        if (function_exists('require_permission')) {
            require_permission('personal.view');
        }

        $this->gateNominaLegacy();

        return true;
    }

    /**
     * Personal = registro de gente + tareas. Todo lo demas que vive en este
     * controlador (nomina por periodos, ledger laboral, pagos por Caja, recibos)
     * se mudo al modulo Nomina y se apaga con personal_nomina_legacy_visible().
     *
     * Son DOS listas, no una, y la diferencia importa:
     *
     *  (1) $registroPersonal = la superficie de Personal que se queda. ALLOWLIST a
     *      proposito, igual que requirePermissionForAction: lo que no este aqui
     *      queda apagado. Si agregas una accion de REGISTRO nueva, ponla aqui o el
     *      gate la mandara a /trabajadores.
     *
     *  (2) $motorNomina = acciones que NO son pantalla de Personal sino el motor
     *      que el modulo Nomina consume por debajo. Los periodos del motor viejo
     *      (v1) se siguen viendo, aprobando, anulando y PAGANDO desde /nomina, que
     *      postea a estas rutas: el unico boton "Registrar pago" del producto vive
     *      en views/nomina/periodo_ver.php y apunta aqui. Apagarlas con el
     *      interruptor dejaba al hotel sin ninguna forma de pagar nomina, que es lo
     *      contrario de lo que se pidio. Se gatean por el MODULO destino: si el
     *      hotel contrato Nomina siguen vivas (sin menu, alcanzables solo desde
     *      /nomina); si no lo contrato, no hay de donde llegar y caen al aviso.
     */
    /** Acciones que SON la superficie de Personal (registro de gente). */
    public const ACCIONES_REGISTRO_PERSONAL = [
        'index', 'reporte', 'informes', 'ver',
        'crear', 'guardar', 'editar', 'actualizar',
        'bajaLogica', 'reactivar',
    ];

    /** Acciones que NO son pantalla de Personal sino motor que /nomina consume. */
    public const ACCIONES_MOTOR_NOMINA = [
        'nominaPeriodoDetalle',
        'aprobarNominaPeriodo',
        'anularNominaPeriodo',
        'registrarPagoSnapshotNomina',
        'reporteNominaPagosSnapshot',
        'exportarNominaPagosSnapshot',
    ];

    /**
     * Decision PURA del gate, sin sesion ni BD, para poder probarla sola:
     *   'registro'      -> se queda en Personal, siempre visible
     *   'motor_nomina'  -> vive solo si el hotel contrato 'nomina_avanzada'
     *   'nomina_legacy' -> apagado mientras la nomina no viva en Personal
     */
    public static function clasificarAccionPersonal(string $accion): string
    {
        if (in_array($accion, self::ACCIONES_REGISTRO_PERSONAL, true)) {
            return 'registro';
        }

        if (in_array($accion, self::ACCIONES_MOTOR_NOMINA, true)) {
            return 'motor_nomina';
        }

        return 'nomina_legacy';
    }

    private function gateNominaLegacy(): void
    {
        if (!function_exists('personal_nomina_legacy_visible') || personal_nomina_legacy_visible()) {
            return;
        }

        $clase = self::clasificarAccionPersonal((string) ($this->route_params['action'] ?? ''));

        if ($clase === 'registro') {
            return;
        }

        if ($clase === 'motor_nomina') {
            if (function_exists('current_hotel_has_module') && !current_hotel_has_module('nomina_avanzada')) {
                require_personal_nomina_legacy();
            }
            return;
        }

        require_personal_nomina_legacy();
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'activos'),
        ];

        $tablaDisponible = $this->trabajadorModel->tablaDisponible();

        View::renderTemplate('trabajadores/index', [
            'title' => 'Personal - ' . current_hotel_display_name(),
            'trabajadores' => $tablaDisponible ? $this->trabajadorModel->listarPorHotel($hotelId, $filtros, 200) : [],
            'resumen' => $tablaDisponible ? $this->trabajadorModel->resumenPorHotel($hotelId) : $this->resumenVacio(),
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function reporteAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->trabajadorModel->tablaDisponible();

        View::renderTemplate('trabajadores/reporte', [
            'title' => 'Reporte de Personal - ' . current_hotel_display_name(),
            'reporte' => $tablaDisponible ? $this->trabajadorModel->reporteReadOnlyPorHotel($hotelId) : $this->reporteVacio(),
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function informesAction(): void
    {
        View::renderTemplate('trabajadores/informes', [
            'title' => 'Informes de Personal - ' . current_hotel_display_name(),
        ]);
    }

    public function nominaPeriodosAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->trabajadorModel->tablasNominaPreviewDisponibles();
        $tablaPersistenteDisponible = $this->trabajadorModel->tablasNominaPeriodoPersistenteDisponibles();
        $periodos = $this->trabajadorModel->nominaPeriodosReadOnlyPorHotel(
            $hotelId,
            $this->filtrosNominaPeriodosDesdeQuery(),
            6
        );
        $periodos = $this->prepararNominaPeriodosPersistentesVista($periodos, $hotelId, $tablaPersistenteDisponible);

        View::renderTemplate('trabajadores/nomina_periodos', [
            'title' => 'Periodos pre-nomina - ' . current_hotel_display_name(),
            'periodosNomina' => $periodos,
            'tablaDisponible' => $tablaDisponible,
            'tablaPersistenteDisponible' => $tablaPersistenteDisponible,
            'periodosPersistentes' => $tablaPersistenteDisponible ? $this->trabajadorModel->nominaPeriodosPersistentesPorHotel($hotelId, 10) : [],
            'cierreTokens' => $periodos['cierre_tokens'] ?? [],
            'modo' => 'lista',
        ]);
    }

    public function nominaPeriodoPreviewAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->trabajadorModel->tablasNominaPreviewDisponibles();
        $tablaPersistenteDisponible = $this->trabajadorModel->tablasNominaPeriodoPersistenteDisponibles();
        $periodos = $this->trabajadorModel->nominaPeriodosReadOnlyPorHotel(
            $hotelId,
            $this->filtrosNominaPeriodosDesdeQuery(),
            6
        );
        $periodos = $this->prepararNominaPeriodosPersistentesVista($periodos, $hotelId, $tablaPersistenteDisponible);

        View::renderTemplate('trabajadores/nomina_periodos', [
            'title' => 'Detalle periodo pre-nomina - ' . current_hotel_display_name(),
            'periodosNomina' => $periodos,
            'tablaDisponible' => $tablaDisponible,
            'tablaPersistenteDisponible' => $tablaPersistenteDisponible,
            'periodosPersistentes' => $tablaPersistenteDisponible ? $this->trabajadorModel->nominaPeriodosPersistentesPorHotel($hotelId, 10) : [],
            'cierreTokens' => $periodos['cierre_tokens'] ?? [],
            'modo' => 'detalle',
        ]);
    }

    public function nominaPeriodoDetalleAction(): void
    {
        $periodoId = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $tablaPersistenteDisponible = $this->trabajadorModel->tablasNominaPeriodoPersistenteDisponibles();
        $periodo = $tablaPersistenteDisponible
            ? $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $hotelId)
            : null;

        if (!$periodo) {
            set_mensaje('Periodo de pre-nomina no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores/nomina/periodos');
            return;
        }

        $pagosSnapshot = [];
        $pagoSnapshotTokens = [];
        if ((string)($periodo['estado'] ?? '') === 'aprobado') {
            foreach (is_array($periodo['detalles'] ?? null) ? $periodo['detalles'] : [] as $detalle) {
                $detalleId = (int)($detalle['id'] ?? 0);
                if ($detalleId <= 0) {
                    continue;
                }

                try {
                    $evaluacion = $this->snapshotPagoService->evaluarPagoDesdeSnapshot($hotelId, $periodoId, $detalleId);
                } catch (Throwable $e) {
                    $evaluacion = [
                        'elegible' => false,
                        'motivo_bloqueo' => $e->getMessage(),
                        'monto_maximo' => '0.00',
                        'snapshot_pendiente' => number_format((float)($detalle['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                        'saldo_vivo' => '0.00',
                        'metodos_pago' => [
                            'efectivo' => 'Efectivo',
                            'tarjeta' => 'Tarjeta',
                            'transferencia' => 'Transferencia',
                        ],
                    ];
                }

                $pagosSnapshot[$detalleId] = $evaluacion;
                if (!empty($evaluacion['elegible'])) {
                    $pagoSnapshotTokens[$detalleId] = $this->generarNominaPeriodoToken('pago_snapshot_caja', $periodoId . ':' . $detalleId);
                }
            }
        }

        View::renderTemplate('trabajadores/nomina_periodo_detalle', [
            'title' => 'Periodo pre-nomina #' . $periodoId . ' - ' . current_hotel_display_name(),
            'periodoNomina' => $periodo,
            'tablaPersistenteDisponible' => $tablaPersistenteDisponible,
            'pagosSnapshot' => $pagosSnapshot,
            'pagoSnapshotTokens' => $pagoSnapshotTokens,
            'aprobarToken' => (string)($periodo['estado'] ?? '') === 'cerrado'
                ? $this->generarNominaPeriodoToken('aprobar', (string)$periodoId)
                : null,
            'anularToken' => (string)($periodo['estado'] ?? '') !== 'anulado'
                ? $this->generarNominaPeriodoToken('anular', (string)$periodoId)
                : null,
        ]);
    }

    public function registrarPagoSnapshotNominaAction(): void
    {
        // Este es el endpoint al que postea el UNICO boton "Registrar pago" del
        // producto (views/nomina/periodo_ver.php). Mismo any-of que usa esa vista
        // para decidir si lo pinta: un boton jamas debe exigir mas que su pantalla.
        $this->requirePagoNominaPermission();

        if (!$this->isPost()) {
            $this->redirect('trabajadores/nomina/periodos');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $periodoId = (int)($this->route_params['periodo'] ?? 0);
        $detalleId = (int)($this->route_params['detalle'] ?? 0);
        $tokenKey = $periodoId . ':' . $detalleId;

        try {
            if (!$this->consumirNominaPeriodoToken('pago_snapshot_caja', $tokenKey, (string)$this->getPost('pago_token', ''))) {
                throw new Exception('El permiso de pago expiró o ya se usó; recarga la página antes de reintentar');
            }

            $resultado = $this->snapshotPagoService->registrarPagoDesdeSnapshot(
                $this->hotelIdActual(),
                $periodoId,
                $detalleId,
                $this->datosPagoSnapshotNomina(),
                $this->usuarioIdActual()
            );

            set_mensaje(
                'Pago registrado y descontado de Caja. Las cifras guardadas del periodo no cambian.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el pago desde snapshot');
        }

        // El pago se puede lanzar desde la pantalla heredada (Personal) o desde el
        // modulo Nomina; volvemos a la superficie de origen. Sin 'origen' se
        // conserva el comportamiento historico (Personal).
        if ((string)$this->getPost('origen', '') === 'nomina') {
            $this->redirect($periodoId > 0 ? 'nomina/periodos/' . $periodoId : 'nomina/periodos');
            return;
        }

        $this->redirect($periodoId > 0 ? 'trabajadores/nomina/periodos/' . $periodoId : 'trabajadores/nomina/periodos');
    }

    public function cerrarNominaPeriodoAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores/nomina/periodos');
            return;
        }

        $this->validateCSRF();

        $datos = $this->datosCierreNominaPeriodo();
        $tokenKey = $this->nominaPeriodoTokenKey(
            (string)($datos['fecha_inicio'] ?? ''),
            (string)($datos['fecha_fin'] ?? '')
        );

        try {
            if (!$this->consumirNominaPeriodoToken('cerrar', $tokenKey, (string)$this->getPost('periodo_token', ''))) {
                throw new Exception('Token de cierre invalido o ya utilizado; recarga la pantalla antes de reintentar');
            }

            $periodoId = $this->nominaPeriodoService->cerrarPeriodo(
                $this->hotelIdActual(),
                $datos,
                $this->usuarioIdActual()
            );
            $periodo = $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $this->hotelIdActual());
            $this->auditarNominaPeriodo('trabajadores.nomina_periodo_cerrado', $periodoId, null, $periodo);

            set_mensaje('Periodo de pre-nomina cerrado como snapshot persistente. No se genero pago ni movimiento de Caja.', 'success');
            $this->redirect('trabajadores/nomina/periodos/' . $periodoId);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'cerrar el periodo de pre-nomina');
            $this->redirect('trabajadores/nomina/periodos' . $this->queryNominaPeriodoDesdeDatos($datos));
        }
    }

    public function aprobarNominaPeriodoAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores/nomina/periodos');
            return;
        }

        $this->validateCSRF();

        $periodoId = (int)($this->route_params['id'] ?? 0);
        try {
            if (!$this->consumirNominaPeriodoToken('aprobar', (string)$periodoId, (string)$this->getPost('periodo_token', ''))) {
                throw new Exception('Token de aprobacion invalido o ya utilizado; recarga el periodo antes de reintentar');
            }

            $antes = $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $this->hotelIdActual());
            $this->nominaPeriodoService->aprobarPeriodo($this->hotelIdActual(), $periodoId, $this->usuarioIdActual());
            $despues = $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $this->hotelIdActual());
            $this->auditarNominaPeriodo('trabajadores.nomina_periodo_aprobado', $periodoId, $antes, $despues);

            set_mensaje('Periodo de pre-nomina aprobado administrativamente. No se genero pago ni movimiento de Caja.', 'success');
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'aprobar el periodo de pre-nomina');
        }

        $this->redirect($periodoId > 0 ? 'trabajadores/nomina/periodos/' . $periodoId : 'trabajadores/nomina/periodos');
    }

    public function anularNominaPeriodoAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores/nomina/periodos');
            return;
        }

        $this->validateCSRF();

        $periodoId = (int)($this->route_params['id'] ?? 0);
        try {
            if (!$this->consumirNominaPeriodoToken('anular', (string)$periodoId, (string)$this->getPost('periodo_token', ''))) {
                throw new Exception('Token de anulacion invalido o ya utilizado; recarga el periodo antes de reintentar');
            }

            $antes = $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $this->hotelIdActual());
            $this->nominaPeriodoService->anularPeriodo(
                $this->hotelIdActual(),
                $periodoId,
                (string)$this->getPost('motivo', ''),
                $this->usuarioIdActual()
            );
            $despues = $this->trabajadorModel->nominaPeriodoPersistentePorHotel($periodoId, $this->hotelIdActual());
            $this->auditarNominaPeriodo('trabajadores.nomina_periodo_anulado', $periodoId, $antes, $despues);

            set_mensaje('Periodo de pre-nomina anulado sin borrar snapshot. No se genero movimiento de Caja.', 'success');
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'anular el periodo de pre-nomina');
        }

        $this->redirect($periodoId > 0 ? 'trabajadores/nomina/periodos/' . $periodoId : 'trabajadores/nomina/periodos');
    }

    public function reporteNominaPeriodosAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReporteNominaPeriodosDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReporteNominaPeriodosDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reporteNominaPeriodosPersistentesPorHotel($hotelId, $filtros, 300)
            : $this->reporteNominaPeriodosVacio($filtros);

        View::renderTemplate('trabajadores/nomina_periodos_reporte', [
            'title' => 'Reporte snapshots pre-nomina - ' . current_hotel_display_name(),
            'reporte' => $reporte,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function exportarNominaPeriodosAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReporteNominaPeriodosDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReporteNominaPeriodosDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reporteNominaPeriodosPersistentesPorHotel($hotelId, $filtros, 1000)
            : $this->reporteNominaPeriodosVacio($filtros);

        $this->descargarNominaPeriodosCsv($reporte);
    }

    public function reporteNominaPagosSnapshotAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReporteNominaPagosSnapshotDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReporteNominaPagosSnapshotDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reporteNominaPagosSnapshotPorHotel($hotelId, $filtros, 300)
            : $this->reporteNominaPagosSnapshotVacio($filtros);

        View::renderTemplate('trabajadores/nomina_pagos_snapshot_reporte', [
            'title' => 'Conciliacion pagos snapshot - ' . current_hotel_display_name(),
            'reporte' => $reporte,
            'tablaDisponible' => $tablaDisponible,
            'trabajadoresFiltro' => $this->trabajadoresParaFiltro($hotelId),
        ]);
    }

    public function exportarNominaPagosSnapshotAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReporteNominaPagosSnapshotDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReporteNominaPagosSnapshotDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reporteNominaPagosSnapshotPorHotel($hotelId, $filtros, 1000)
            : $this->reporteNominaPagosSnapshotVacio($filtros);

        $this->descargarNominaPagosSnapshotCsv($reporte);
    }

    public function auditoriaNominaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosAuditoriaNominaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasAuditoriaNominaConsolidadaDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->auditoriaNominaConsolidadaPorHotel($hotelId, $filtros, 500)
            : $this->auditoriaNominaConsolidadaVacio($filtros);

        View::renderTemplate('trabajadores/nomina_auditoria_consolidada', [
            'title' => 'Auditoria nomina consolidada - ' . current_hotel_display_name(),
            'reporte' => $reporte,
            'tablaDisponible' => $tablaDisponible,
            'trabajadoresFiltro' => $this->trabajadoresParaFiltro($hotelId),
        ]);
    }

    public function exportarAuditoriaNominaAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosAuditoriaNominaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasAuditoriaNominaConsolidadaDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->auditoriaNominaConsolidadaPorHotel($hotelId, $filtros, 1000)
            : $this->auditoriaNominaConsolidadaVacio($filtros);

        $this->descargarAuditoriaNominaCsv($reporte);
    }

    public function expedienteNominaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosExpedienteNominaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasExpedienteNominaAdministrativoDisponibles();
        $expediente = $tablaDisponible
            ? $this->trabajadorModel->expedienteNominaAdministrativoPorHotel($hotelId, $filtros, 500)
            : $this->expedienteNominaAdministrativoVacio($filtros);

        View::renderTemplate('trabajadores/nomina_expediente_administrativo', [
            'title' => 'Expediente administrativo nomina - ' . current_hotel_display_name(),
            'expediente' => $expediente,
            'tablaDisponible' => $tablaDisponible,
            'trabajadoresFiltro' => $this->trabajadoresParaFiltro($hotelId),
        ]);
    }

    public function exportarExpedienteNominaAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosExpedienteNominaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasExpedienteNominaAdministrativoDisponibles();
        $expediente = $tablaDisponible
            ? $this->trabajadorModel->expedienteNominaAdministrativoPorHotel($hotelId, $filtros, 1000)
            : $this->expedienteNominaAdministrativoVacio($filtros);

        $this->descargarExpedienteNominaCsv($expediente);
    }

    public function nominaPreviewAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosNominaPreviewDesdeQuery();
        $tablaDisponible = $this->trabajadorModel->tablasNominaPreviewDisponibles();
        $preview = $this->trabajadorModel->nominaPreviewPorHotel($hotelId, $filtros, 250);

        View::renderTemplate('trabajadores/nomina_preview', [
            'title' => 'Preview nomina laboral - ' . current_hotel_display_name(),
            'preview' => $preview,
            'tablaDisponible' => $tablaDisponible,
            'trabajadoresFiltro' => $this->trabajadoresParaFiltro($hotelId),
        ]);
    }

    public function exportarNominaPreviewAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosNominaPreviewDesdeQuery();
        $preview = $this->trabajadorModel->nominaPreviewPorHotel($hotelId, $filtros, 500);

        if (!empty($preview['bloqueos'])) {
            set_mensaje('No se pudo exportar el preview de pre-nomina: ' . implode(' ', $preview['bloqueos']), 'error');
            $query = http_build_query(array_filter($filtros, static function ($value) {
                return $value !== null && $value !== '';
            }));
            $this->redirect('trabajadores/nomina/preview' . ($query !== '' ? '?' . $query : ''));
            return;
        }

        $this->descargarNominaPreviewCsv($preview);
    }

    public function reciboLaboralAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $tablaDisponible = $this->trabajadorModel->tablasNominaPreviewDisponibles();
        $recibo = $this->trabajadorModel->reciboLaboralInformativoPorHotel(
            $hotelId,
            $id,
            $this->filtrosReciboLaboralDesdeQuery()
        );

        if (empty($recibo['trabajador'])) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        View::renderTemplate('trabajadores/recibo_laboral_informativo', [
            'title' => 'Recibo laboral informativo - ' . current_hotel_display_name(),
            'recibo' => $recibo,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function reciboLaboralPdfAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReciboLaboralDesdeQuery();
        $recibo = $this->trabajadorModel->reciboLaboralInformativoPorHotel($hotelId, $id, $filtros);

        if (empty($recibo['trabajador'])) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        if (!empty($recibo['bloqueos']) || empty($recibo['calculo'])) {
            set_mensaje('No se pudo generar el PDF informativo: revisa el periodo del recibo.', 'error');
            $query = http_build_query(array_filter($filtros, static function ($value) {
                return $value !== null && $value !== '';
            }));
            $this->redirect('trabajadores/' . $id . '/recibo-laboral' . ($query !== '' ? '?' . $query : ''));
            return;
        }

        $pdf = new TrabajadorReciboLaboralPdfService();
        $pdf->descargar($recibo);
    }

    public function reportePagosCajaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReportePagosCajaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReportePagosCajaDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reportePagosCajaPorHotel($hotelId, $filtros, 300)
            : $this->reportePagosCajaVacio($filtros);

        View::renderTemplate('trabajadores/reporte_pagos_caja', [
            'title' => 'Reporte pagos laborales Caja - ' . current_hotel_display_name(),
            'reporte' => $reporte,
            'tablaDisponible' => $tablaDisponible,
            'trabajadoresFiltro' => $this->trabajadoresParaFiltro($hotelId),
        ]);
    }

    public function exportarReportePagosCajaAction(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }

        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtrosReportePagosCajaDesdeQuery();

        $tablaDisponible = $this->trabajadorModel->tablasReportePagosCajaDisponibles();
        $reporte = $tablaDisponible
            ? $this->trabajadorModel->reportePagosCajaPorHotel($hotelId, $filtros, 500)
            : $this->reportePagosCajaVacio($filtros);

        $this->descargarReportePagosCajaCsv($reporte);
    }

    public function simuladorPagoCajaAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'periodo_inicio' => $this->getQuery('periodo_inicio', ''),
            'periodo_fin' => $this->getQuery('periodo_fin', ''),
            'metodo_pago' => $this->getQuery('metodo_pago', 'efectivo'),
            'monto' => $this->getQuery('monto', ''),
            'referencia' => $this->getQuery('referencia', ''),
        ];

        $tablaDisponible = $this->trabajadorModel->tablasSimuladorPagoCajaDisponibles();
        $datos = $tablaDisponible
            ? $this->trabajadorModel->simuladorPagoCajaPorHotel($hotelId, $filtros, 200)
            : [
                'trabajadores' => [],
                'resumen' => [],
                'corte' => null,
                'filtros_normalizados' => $filtros,
            ];

        View::renderTemplate('trabajadores/simulador_pago_caja', [
            'title' => 'Simulador pagos laborales Caja - ' . current_hotel_display_name(),
            'trabajadores' => $datos['trabajadores'] ?? [],
            'resumen' => $datos['resumen'] ?? [],
            'corte' => $datos['corte'] ?? null,
            'filtros' => $datos['filtros_normalizados'] ?? $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function verAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);

        if (!$trabajador) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        $documentosEntidad = [];
        try {
            if (puede_ver_documentos_vinculados()) {
                $documentoModel = new Documento();
                $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'trabajador', $id, 10);
            }
        } catch (Throwable $e) {
            $documentosEntidad = [];
        }

        // Con la nomina fuera de Personal, la ficha es registro + tareas: ni siquiera
        // se consulta el ledger ni Caja (son ~8 queries y una evaluacion de corte por
        // cada pago). La vista ya normaliza estos vacios a [] en su cabecera.
        $nominaLegacy = personal_nomina_legacy_visible();

        $pagoCaja = [
            'elegible' => false,
            'motivo_bloqueo' => 'No se pudo evaluar el pago laboral con Caja.',
            'corte' => null,
            'metodos_pago' => [],
            'monto_maximo' => '0.00',
            'saldo' => [],
        ];
        $pagoCajaToken = null;
        $pagosCajaLaborales = [];
        $reversionesPagoCaja = [];
        $reversionPagoCajaTokens = [];
        $avisoNominaPendiente = null;
        $datosNomina = [
            'resumenLedger' => [],
            'conceptosLaborales' => [],
            'anticiposRecientes' => [],
            'prestamosRecientes' => [],
            'asistenciasRecientes' => [],
            'ledgerDisponible' => [],
        ];

        if ($nominaLegacy) {
            try {
                $pagoCaja = $this->pagoCajaService->evaluarPago($hotelId, $id);
                if (!empty($pagoCaja['elegible'])) {
                    $pagoCajaToken = $this->generarPagoCajaToken($id);
                }
            } catch (Throwable $e) {
                $pagoCaja['motivo_bloqueo'] = $e->getMessage();
            }

            $pagosCajaLaborales = $this->trabajadorModel->pagosCajaPorTrabajador($id, $hotelId, 20);
            foreach ($pagosCajaLaborales as $pagoLaboral) {
                $pagoLaboralId = (int)($pagoLaboral['id'] ?? 0);
                if ($pagoLaboralId <= 0 || (string)($pagoLaboral['estado'] ?? '') !== 'pagado') {
                    continue;
                }

                try {
                    $reversion = $this->pagoCajaService->evaluarReversion($hotelId, $id, $pagoLaboralId);
                    $reversionesPagoCaja[$pagoLaboralId] = $reversion;
                    if (!empty($reversion['elegible'])) {
                        $reversionPagoCajaTokens[$pagoLaboralId] = $this->generarReversionPagoCajaToken($id, $pagoLaboralId);
                    }
                } catch (Throwable $e) {
                    $reversionesPagoCaja[$pagoLaboralId] = [
                        'elegible' => false,
                        'motivo_bloqueo' => $e->getMessage(),
                    ];
                }
            }

            $avisoNominaPendiente = (function_exists('current_hotel_has_module')
                && current_hotel_has_module('nomina_avanzada'))
                ? $this->trabajadorModel->pendienteConfiguracionNomina($id, $hotelId)
                : null;

            $datosNomina = [
                'resumenLedger' => $this->trabajadorModel->resumenLedgerPorTrabajador($id, $hotelId),
                'conceptosLaborales' => $this->trabajadorModel->conceptosLaboralesPorTrabajador($id, $hotelId, 12),
                'anticiposRecientes' => $this->trabajadorModel->anticiposPorTrabajador($id, $hotelId, 12),
                'prestamosRecientes' => $this->trabajadorModel->prestamosPorTrabajador($id, $hotelId, 12),
                'asistenciasRecientes' => $this->trabajadorModel->ultimosMovimientosPorTrabajador($id, $hotelId, 20),
                'ledgerDisponible' => $this->trabajadorModel->tablasLedgerDisponibles(),
            ];
        }

        View::renderTemplate('trabajadores/ver', array_merge($datosNomina, [
            'title' => 'Trabajador #' . $id . ' - ' . current_hotel_display_name(),
            'trabajador' => $trabajador,
            'nominaLegacyVisible' => $nominaLegacy,
            'avisoNominaPendiente' => $avisoNominaPendiente,
            'pagosCajaLaborales' => $pagosCajaLaborales,
            'reversionesPagoCaja' => $reversionesPagoCaja,
            'reversionPagoCajaTokens' => $reversionPagoCajaTokens,
            // Tareas pasa de dato secundario a la segunda pestana de la ficha.
            'tareasContextuales' => $this->tareaModel->listarPorEntidadHotel($hotelId, 'trabajador', $id, $nominaLegacy ? 8 : 12),
            'pagoCaja' => $pagoCaja,
            'pagoCajaToken' => $pagoCajaToken,
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'trabajador',
                'id' => $id,
                'label' => 'Trabajador',
            ],
        ]));
    }

    public function crearAction(): void
    {
        $this->requireWritePermission('personal.gestionar');
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('trabajadores/form', [
            'title' => 'Nuevo trabajador - ' . current_hotel_display_name(),
            'modo' => 'crear',
            'trabajador' => [],
            'usuariosVinculables' => $this->trabajadorModel->usuariosVinculablesPorHotel($hotelId),
        ]);
    }

    public function guardarAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $trabajadorId = $this->trabajadorModel->crearParaHotel(
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );
            $despues = $this->trabajadorModel->buscarPorIdHotel($trabajadorId, $hotelId);
            $this->auditar('trabajadores.creado', null, $despues, $trabajadorId);

            clear_old_input();
            set_mensaje('Trabajador creado correctamente.', 'success');
            $this->redirect('trabajadores/' . $trabajadorId);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'crear el trabajador');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTrabajador([$e->getMessage()]));
            $this->redirect('trabajadores/crear');
        }
    }

    public function editarAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        $hotelId = $this->hotelIdActual();
        $trabajador = $this->trabajadorActual($hotelId);
        if (!$trabajador) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        $salarioEnNomina = function_exists('current_hotel_has_module')
            && current_hotel_has_module('nomina_avanzada')
            && $this->trabajadorModel->salarioAdministradoEnNomina((int) $trabajador['id'], $hotelId);

        View::renderTemplate('trabajadores/form', [
            'title' => 'Editar trabajador - ' . current_hotel_display_name(),
            'modo' => 'editar',
            'trabajador' => $trabajador,
            'usuariosVinculables' => $this->trabajadorModel->usuariosVinculablesPorHotel($hotelId),
            'salarioEnNomina' => $salarioEnNomina,
        ]);
    }

    public function actualizarAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $antes = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $this->trabajadorModel->actualizarParaHotel(
                $id,
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );

            $despues = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('trabajadores.actualizado', $antes, $despues, $id);

            clear_old_input();
            set_mensaje('Trabajador actualizado correctamente.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'actualizar el trabajador');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTrabajador([$e->getMessage()]));
            $this->redirect($id > 0 ? 'trabajadores/' . $id . '/editar' : 'trabajadores');
        }
    }

    public function bajaLogicaAction(): void
    {
        $this->cambiarEstado('baja', 'Trabajador dado de baja correctamente.', 'trabajadores.baja_logica');
    }

    public function reactivarAction(): void
    {
        $this->cambiarEstado('activo', 'Trabajador reactivado correctamente.', 'trabajadores.reactivado');
    }

    public function registrarConceptoLaboralAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$trabajador) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $conceptoId = $this->trabajadorModel->registrarConceptoLaboralParaHotel(
                $id,
                $hotelId,
                $this->datosConceptoLaboral(),
                $this->usuarioIdActual()
            );

            $concepto = $this->trabajadorModel->conceptoLaboralPorIdHotel($conceptoId, $id, $hotelId);
            $this->auditarConceptoLaboral($trabajador, $concepto ?: [], $conceptoId);

            set_mensaje('Concepto laboral registrado correctamente. No se genero movimiento de Caja.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el concepto laboral');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarAnticipoLaboralAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$trabajador) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $anticipoId = $this->trabajadorModel->registrarAnticipoLaboralParaHotel(
                $id,
                $hotelId,
                $this->datosAnticipoLaboral(),
                $this->usuarioIdActual()
            );

            $anticipo = $this->trabajadorModel->anticipoLaboralPorIdHotel($anticipoId, $id, $hotelId);
            $this->auditarMovimientoLaboral('trabajadores.anticipo_laboral_registrado', 'trabajador_anticipo', $trabajador, $anticipo ?: [], $anticipoId);

            set_mensaje('Anticipo laboral registrado correctamente. No se genero movimiento de Caja.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el anticipo laboral');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarPrestamoLaboralAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$trabajador) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $prestamoId = $this->trabajadorModel->registrarPrestamoLaboralParaHotel(
                $id,
                $hotelId,
                $this->datosPrestamoLaboral(),
                $this->usuarioIdActual()
            );

            $prestamo = $this->trabajadorModel->prestamoLaboralPorIdHotel($prestamoId, $id, $hotelId);
            $this->auditarMovimientoLaboral('trabajadores.prestamo_laboral_registrado', 'trabajador_prestamo', $trabajador, $prestamo ?: [], $prestamoId);

            set_mensaje('Prestamo laboral registrado correctamente. No se genero movimiento de Caja.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el prestamo laboral');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarAsistenciaLaboralAction(): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$trabajador) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $asistenciaId = $this->trabajadorModel->registrarAsistenciaLaboralParaHotel(
                $id,
                $hotelId,
                $this->datosAsistenciaLaboral(),
                $this->usuarioIdActual()
            );

            $asistencia = $this->trabajadorModel->asistenciaLaboralPorIdHotel($asistenciaId, $id, $hotelId);
            $this->auditarMovimientoLaboral('trabajadores.asistencia_laboral_registrada', 'trabajador_asistencia', $trabajador, $asistencia ?: [], $asistenciaId);

            set_mensaje('Asistencia laboral registrada correctamente. No se genero nomina ni movimiento de Caja.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar la asistencia laboral');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarPagoCajaAction(): void
    {
        $this->requireWritePermission('personal.pagar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $id = (int)($this->route_params['id'] ?? 0);
        try {
            if (!$this->consumirPagoCajaToken($id, (string)$this->getPost('pago_token', ''))) {
                throw new Exception('Token de pago invalido o ya utilizado; recarga el trabajador antes de reintentar');
            }

            $resultado = $this->pagoCajaService->registrarPago(
                $this->hotelIdActual(),
                $id,
                $this->datosPagoCajaLaboral(),
                $this->usuarioIdActual()
            );

            set_mensaje(
                'Pago registrado y descontado de Caja. Le quedan pendientes $'
                . number_format((float)$resultado['saldo_posterior_estimado'], 2) . ' a este trabajador.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el pago laboral con Caja');
        }

        $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
    }

    public function revertirPagoCajaAction(): void
    {
        $this->requireWritePermission('personal.pagar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_hotel_module')) {
            require_hotel_module('caja');
        }

        $id = (int)($this->route_params['id'] ?? 0);
        $pagoCajaId = (int)($this->route_params['pagoid'] ?? $this->route_params['pagoId'] ?? 0);
        try {
            if (!$this->consumirReversionPagoCajaToken($id, $pagoCajaId, (string)$this->getPost('reversion_token', ''))) {
                throw new Exception('Token de reversion invalido o ya utilizado; recarga el trabajador antes de reintentar');
            }

            $resultado = $this->pagoCajaService->revertirPago(
                $this->hotelIdActual(),
                $id,
                $pagoCajaId,
                $this->datosReversionPagoCajaLaboral(),
                $this->usuarioIdActual()
            );

            set_mensaje(
                'Pago laboral revertido. Ingreso Caja #' . (int)$resultado['movimiento_caja_reversion_id']
                . ', saldo disponible estimado ' . number_format((float)$resultado['saldo_posterior_estimado'], 2) . '.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'revertir el pago laboral con Caja');
        }

        $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
    }

    private function cambiarEstado(string $estado, string $mensaje, string $accion): void
    {
        $this->requireWritePermission('personal.gestionar');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $antes = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $this->trabajadorModel->cambiarEstadoParaHotel($id, $hotelId, $estado, $this->usuarioIdActual());
            $despues = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar($accion, $antes, $despues, $id);

            set_mensaje($mensaje, 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'cambiar el estado del trabajador');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'baja' => 0,
        ];
    }

    private function reporteVacio(): array
    {
        return [
            'trabajadores' => $this->resumenVacio(),
            'ledger' => [
                'conceptos_count' => 0,
                'conceptos_a_favor' => '0.00',
                'conceptos_en_contra' => '0.00',
                'conceptos_neutros' => '0.00',
                'anticipos_count' => 0,
                'anticipos_saldo' => '0.00',
                'prestamos_count' => 0,
                'prestamos_saldo' => '0.00',
                'saldo_informativo' => '0.00',
            ],
            'asistencias' => [
                'total' => 0,
                'asistencia' => 0,
                'falta' => 0,
                'retardo' => 0,
                'permiso' => 0,
                'incapacidad' => 0,
                'descanso' => 0,
                'horas_extra' => 0,
                'primera_fecha' => null,
                'ultima_fecha' => null,
            ],
            'documentos' => [
                'total' => 0,
                'trabajadores_con_documentos' => 0,
            ],
            'tareas' => [
                'total' => 0,
                'pendiente' => 0,
                'asignada' => 0,
                'en_proceso' => 0,
                'completada' => 0,
                'cancelada' => 0,
            ],
            'trabajadores_relevantes' => [],
        ];
    }

    private function prepararNominaPeriodosPersistentesVista(array $periodosNomina, int $hotelId, bool $tablaPersistenteDisponible): array
    {
        $periodos = is_array($periodosNomina['periodos'] ?? null) ? $periodosNomina['periodos'] : [];
        $periodosNomina['cierre_tokens'] = [];

        if (!$tablaPersistenteDisponible || empty($periodos)) {
            return $periodosNomina;
        }

        $persistentes = $this->trabajadorModel->mapaNominaPeriodosPersistentesPorRango($hotelId, $periodos);
        $filtros = is_array($periodosNomina['filtros_normalizados'] ?? null) ? $periodosNomina['filtros_normalizados'] : [];
        $estado = (string)($filtros['estado'] ?? 'activos');
        $rolLaboral = trim((string)($filtros['rol_laboral'] ?? ''));

        foreach ($periodos as $idx => $periodo) {
            $key = $this->nominaPeriodoTokenKey(
                (string)($periodo['fecha_inicio'] ?? ''),
                (string)($periodo['fecha_fin'] ?? '')
            );
            $snapshot = $persistentes[$key] ?? null;
            if (is_array($snapshot)) {
                $periodos[$idx]['snapshot_id'] = (int)($snapshot['id'] ?? 0);
                $periodos[$idx]['snapshot_estado'] = (string)($snapshot['estado'] ?? '');
                $periodos[$idx]['snapshot_cerrado_at'] = $snapshot['cerrado_at'] ?? null;
                $periodos[$idx]['snapshot_aprobado_at'] = $snapshot['aprobado_at'] ?? null;
                $periodos[$idx]['snapshot_anulado_at'] = $snapshot['anulado_at'] ?? null;
                continue;
            }

            $puedeCerrar = !empty($periodo['cerrable_readonly'])
                && $estado === 'activos'
                && $rolLaboral === ''
                && trim((string)($periodo['fecha_inicio'] ?? '')) !== ''
                && trim((string)($periodo['fecha_fin'] ?? '')) !== '';

            if ($puedeCerrar) {
                $periodosNomina['cierre_tokens'][$key] = $this->generarNominaPeriodoToken('cerrar', $key);
                $periodos[$idx]['puede_cerrar_persistente'] = true;
            } else {
                $periodos[$idx]['puede_cerrar_persistente'] = false;
            }
        }

        $periodosNomina['periodos'] = $periodos;
        $detalle = is_array($periodosNomina['periodo_detalle'] ?? null) ? $periodosNomina['periodo_detalle'] : null;
        if ($detalle) {
            foreach ($periodos as $periodo) {
                if (
                    (string)($periodo['fecha_inicio'] ?? '') === (string)($detalle['fecha_inicio'] ?? '')
                    && (string)($periodo['fecha_fin'] ?? '') === (string)($detalle['fecha_fin'] ?? '')
                ) {
                    $periodosNomina['periodo_detalle'] = $periodo;
                    break;
                }
            }
        }

        return $periodosNomina;
    }

    private function datosCierreNominaPeriodo(): array
    {
        return [
            'tipo_periodo' => $this->getPost('tipo_periodo', 'manual'),
            'etiqueta' => $this->getPost('etiqueta', ''),
            'fecha_inicio' => $this->getPost('fecha_inicio', ''),
            'fecha_fin' => $this->getPost('fecha_fin', ''),
            'estado' => $this->getPost('estado', 'activos'),
            'rol_laboral' => $this->getPost('rol_laboral', ''),
            'incluir_pagos_caja' => $this->getPost('incluir_pagos_caja', '1'),
        ];
    }

    private function queryNominaPeriodoDesdeDatos(array $datos): string
    {
        $query = http_build_query(array_filter([
            'tipo_periodo' => $datos['tipo_periodo'] ?? 'manual',
            'fecha_inicio' => $datos['fecha_inicio'] ?? '',
            'fecha_fin' => $datos['fecha_fin'] ?? '',
            'estado' => $datos['estado'] ?? 'activos',
            'rol_laboral' => $datos['rol_laboral'] ?? '',
            'incluir_pagos_caja' => $datos['incluir_pagos_caja'] ?? '1',
        ], static function ($value) {
            return $value !== null && $value !== '';
        }));

        return $query !== '' ? '?' . $query : '';
    }

    private function filtrosNominaPeriodosDesdeQuery(): array
    {
        return [
            'tipo_periodo' => $this->getQuery('tipo_periodo', 'semanal'),
            'fecha_base' => $this->getQuery('fecha_base', date('Y-m-d')),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
            'estado' => $this->getQuery('estado', 'activos'),
            'rol_laboral' => $this->getQuery('rol_laboral', ''),
            'incluir_pagos_caja' => $this->getQuery('incluir_pagos_caja', '1'),
        ];
    }

    private function filtrosNominaPreviewDesdeQuery(): array
    {
        return [
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'rol_laboral' => $this->getQuery('rol_laboral', ''),
            'estado' => $this->getQuery('estado', 'activos'),
            'solo_con_saldo' => $this->getQuery('solo_con_saldo', '0'),
            'incluir_pagos_caja' => $this->getQuery('incluir_pagos_caja', '1'),
        ];
    }

    private function filtrosReciboLaboralDesdeQuery(): array
    {
        return [
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
            'incluir_pagos_caja' => $this->getQuery('incluir_pagos_caja', '1'),
        ];
    }

    private function filtrosReportePagosCajaDesdeQuery(): array
    {
        return [
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
            'metodo_pago' => $this->getQuery('metodo_pago', 'todos'),
            'corte_id' => $this->getQuery('corte_id', ''),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
        ];
    }

    private function filtrosReporteNominaPeriodosDesdeQuery(): array
    {
        return [
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
            'estado' => $this->getQuery('estado', 'todos'),
            'tipo_periodo' => $this->getQuery('tipo_periodo', 'todos'),
            'buscar' => $this->getQuery('buscar', ''),
        ];
    }

    private function filtrosReporteNominaPagosSnapshotDesdeQuery(): array
    {
        return [
            'periodo_id' => $this->getQuery('periodo_id', ''),
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
            'conciliacion' => $this->getQuery('conciliacion', 'todos'),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
        ];
    }

    private function filtrosAuditoriaNominaDesdeQuery(): array
    {
        return [
            'periodo_id' => $this->getQuery('periodo_id', ''),
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'estado_snapshot' => $this->getQuery('estado_snapshot', 'todos'),
            'estado_auditoria' => $this->getQuery('estado_auditoria', 'todos'),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
        ];
    }

    private function filtrosExpedienteNominaDesdeQuery(): array
    {
        return [
            'periodo_id' => $this->getQuery('periodo_id', ''),
            'trabajador_id' => $this->getQuery('trabajador_id', ''),
            'buscar' => $this->getQuery('buscar', ''),
            'estado_snapshot' => $this->getQuery('estado_snapshot', 'todos'),
            'estado_expediente' => $this->getQuery('estado_expediente', 'todos'),
            'fecha_inicio' => $this->getQuery('fecha_inicio', ''),
            'fecha_fin' => $this->getQuery('fecha_fin', ''),
        ];
    }

    private function descargarReportePagosCajaCsv(array $reporte): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'pagos_laborales_caja_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'registro_id',
            'fecha_pago',
            'trabajador_id',
            'trabajador',
            'identificacion',
            'rol',
            'monto',
            'metodo_pago',
            'estado',
            'referencia',
            'concepto',
            'periodo_inicio',
            'periodo_fin',
            'caja',
            'corte_id',
            'corte_estado',
            'movimiento_caja_id',
            'movimiento_reversion_id',
            'corte_reversion_id',
            'monto_reversion_caja',
            'fecha_reversion_caja',
            'creado_por',
            'actualizado_por',
            'notas',
        ]);

        $registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
        foreach ($registros as $registro) {
            fputcsv($out, [
                (int)($registro['id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['fecha_pago'] ?? ''),
                (int)($registro['trabajador_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['trabajador_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_identificacion'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_rol'] ?? ''),
                number_format((float)($registro['monto'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaValor($registro['metodo_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['referencia'] ?? ''),
                $this->csvReportePagosCajaValor($registro['concepto'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_inicio'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fin'] ?? ''),
                $this->csvReportePagosCajaValor($registro['caja_nombre'] ?? ''),
                (int)($registro['corte_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['corte_estado'] ?? ''),
                (int)($registro['movimiento_caja_id'] ?? 0),
                (int)($registro['movimiento_reversion_id'] ?? 0),
                (int)($registro['corte_reversion_id'] ?? 0),
                number_format((float)($registro['movimiento_reversion_monto'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaValor($registro['movimiento_reversion_created_at'] ?? ''),
                $this->csvReportePagosCajaUsuario($registro, 'creado_por'),
                $this->csvReportePagosCajaUsuario($registro, 'actualizado_por'),
                $this->csvReportePagosCajaValor($registro['notas'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }

    private function descargarNominaPreviewCsv(array $preview): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filtros = is_array($preview['filtros_normalizados'] ?? null) ? $preview['filtros_normalizados'] : [];
        $fechaInicio = $this->csvReportePagosCajaValor($filtros['fecha_inicio'] ?? '');
        $fechaFin = $this->csvReportePagosCajaValor($filtros['fecha_fin'] ?? '');
        $filename = 'pre_nomina_preview_' . ($fechaInicio !== '' ? str_replace('-', '', $fechaInicio) : date('Ymd')) . '_' . date('His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'periodo_inicio',
            'periodo_fin',
            'trabajador_id',
            'trabajador',
            'identificacion',
            'rol',
            'estado_trabajador',
            'estado_preview',
            'bruto_periodo',
            'conceptos_a_favor',
            'conceptos_en_contra',
            'anticipos_saldo',
            'prestamos_saldo',
            'deducciones_informativas',
            'pagos_caja_aplicados',
            'pagos_caja_pagados',
            'pagos_caja_revertidos',
            'reversiones_detectadas',
            'neto_sugerido',
            'pendiente_pago_sugerido',
            'ultimo_pago_caja',
        ]);

        $trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
        foreach ($trabajadores as $trabajador) {
            fputcsv($out, [
                $fechaInicio,
                $fechaFin,
                (int)($trabajador['id'] ?? 0),
                $this->csvReportePagosCajaValor($trabajador['nombre_completo'] ?? ''),
                $this->csvReportePagosCajaValor($trabajador['identificacion'] ?? ''),
                $this->csvReportePagosCajaValor($trabajador['rol_laboral'] ?? ''),
                $this->csvReportePagosCajaValor($trabajador['estado'] ?? ''),
                $this->csvReportePagosCajaValor($trabajador['estado_preview_nomina'] ?? ''),
                number_format((float)($trabajador['bruto_periodo'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['conceptos_a_favor'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['conceptos_en_contra'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['anticipos_saldo'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['prestamos_saldo'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['deducciones_informativas'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['pagos_caja_aplicados'] ?? 0), 2, '.', ''),
                (int)($trabajador['pagos_caja_pagados'] ?? 0),
                (int)($trabajador['pagos_caja_revertidos'] ?? 0),
                number_format((float)($trabajador['reversiones_detectadas'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['neto_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($trabajador['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaValor($trabajador['ultimo_pago_caja'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }

    private function descargarNominaPeriodosCsv(array $reporte): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];
        $fechaInicio = $this->csvReportePagosCajaValor($filtros['fecha_inicio'] ?? '');
        $filename = 'snapshots_pre_nomina_' . ($fechaInicio !== '' ? str_replace('-', '', $fechaInicio) : date('Ymd')) . '_' . date('His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'snapshot_id',
            'tipo_periodo',
            'etiqueta',
            'fecha_inicio',
            'fecha_fin',
            'estado',
            'trabajadores_total',
            'bruto_total',
            'deducciones_total',
            'pagos_caja_aplicados_total',
            'reversiones_detectadas_total',
            'neto_sugerido_total',
            'pendiente_pago_total',
            'cerrado_por',
            'cerrado_at',
            'aprobado_por',
            'aprobado_at',
            'anulado_por',
            'anulado_at',
            'motivo_anulacion',
        ]);

        $registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
        foreach ($registros as $registro) {
            fputcsv($out, [
                (int)($registro['id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['tipo_periodo'] ?? ''),
                $this->csvReportePagosCajaValor($registro['etiqueta'] ?? ''),
                $this->csvReportePagosCajaValor($registro['fecha_inicio'] ?? ''),
                $this->csvReportePagosCajaValor($registro['fecha_fin'] ?? ''),
                $this->csvReportePagosCajaValor($registro['estado'] ?? ''),
                (int)($registro['trabajadores_total'] ?? 0),
                number_format((float)($registro['bruto_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['deducciones_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pagos_caja_aplicados_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['reversiones_detectadas_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['neto_sugerido_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pendiente_pago_total'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaUsuario($registro, 'cerrado_por'),
                $this->csvReportePagosCajaValor($registro['cerrado_at'] ?? ''),
                $this->csvReportePagosCajaUsuario($registro, 'aprobado_por'),
                $this->csvReportePagosCajaValor($registro['aprobado_at'] ?? ''),
                $this->csvReportePagosCajaUsuario($registro, 'anulado_por'),
                $this->csvReportePagosCajaValor($registro['anulado_at'] ?? ''),
                $this->csvReportePagosCajaValor($registro['motivo_anulacion'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }

    private function descargarNominaPagosSnapshotCsv(array $reporte): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'conciliacion_pagos_snapshot_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'pago_id',
            'fecha_pago',
            'estado_pago',
            'conciliacion',
            'monto',
            'metodo_pago',
            'referencia_pago',
            'movimiento_caja_id',
            'movimiento_referencia',
            'corte_id',
            'caja',
            'snapshot_id',
            'snapshot_estado',
            'snapshot_etiqueta',
            'snapshot_inicio',
            'snapshot_fin',
            'detalle_id',
            'trabajador_id',
            'trabajador_actual',
            'trabajador_snapshot',
            'bruto_snapshot',
            'deducciones_snapshot',
            'neto_snapshot',
            'pendiente_snapshot',
            'movimiento_reversion_id',
            'monto_reversion_caja',
            'creado_por',
            'notas',
        ]);

        $registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
        foreach ($registros as $registro) {
            fputcsv($out, [
                (int)($registro['id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['fecha_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['conciliacion_estado'] ?? ''),
                number_format((float)($registro['monto'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaValor($registro['metodo_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['referencia'] ?? ''),
                (int)($registro['movimiento_caja_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['movimiento_referencia'] ?? ''),
                (int)($registro['corte_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['caja_nombre'] ?? ''),
                (int)($registro['nomina_periodo_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['periodo_estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_etiqueta'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_inicio'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_fin'] ?? ''),
                (int)($registro['nomina_periodo_detalle_id'] ?? 0),
                (int)($registro['trabajador_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['trabajador_actual_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['detalle_trabajador_nombre'] ?? ''),
                number_format((float)($registro['bruto_periodo'] ?? 0), 2, '.', ''),
                number_format((float)($registro['deducciones_informativas'] ?? 0), 2, '.', ''),
                number_format((float)($registro['neto_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                (int)($registro['movimiento_reversion_id'] ?? 0),
                number_format((float)($registro['movimiento_reversion_monto'] ?? 0), 2, '.', ''),
                $this->csvReportePagosCajaUsuario($registro, 'creado_por'),
                $this->csvReportePagosCajaValor($registro['notas'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }

    private function descargarAuditoriaNominaCsv(array $reporte): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'auditoria_nomina_consolidada_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'periodo_id',
            'periodo_estado',
            'periodo_etiqueta',
            'periodo_inicio',
            'periodo_fin',
            'detalle_id',
            'trabajador_id',
            'trabajador_snapshot',
            'trabajador_actual',
            'rol_snapshot',
            'estado_auditoria',
            'bruto_snapshot',
            'deducciones_snapshot',
            'neto_snapshot',
            'pendiente_snapshot',
            'pagos_caja_total',
            'reversiones_total',
            'saldo_auditoria',
            'pagos_count',
            'pagos_pagados_count',
            'pagos_revertidos_count',
            'ultimo_pago_id',
            'ultimo_pago',
            'referencias_pago',
            'cajas',
            'inconsistencias_count',
        ]);

        $registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
        foreach ($registros as $registro) {
            fputcsv($out, [
                (int)($registro['periodo_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['periodo_estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_etiqueta'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_inicio'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_fin'] ?? ''),
                (int)($registro['detalle_id'] ?? 0),
                (int)($registro['trabajador_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['trabajador_snapshot_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_actual_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_snapshot_rol'] ?? ''),
                $this->csvReportePagosCajaValor($registro['auditoria_estado'] ?? ''),
                number_format((float)($registro['bruto_periodo'] ?? 0), 2, '.', ''),
                number_format((float)($registro['deducciones_informativas'] ?? 0), 2, '.', ''),
                number_format((float)($registro['neto_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pagos_caja_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['reversiones_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['saldo_auditoria'] ?? 0), 2, '.', ''),
                (int)($registro['pagos_count'] ?? 0),
                (int)($registro['pagos_pagados_count'] ?? 0),
                (int)($registro['pagos_revertidos_count'] ?? 0),
                (int)($registro['ultimo_pago_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['ultimo_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['referencias_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['cajas'] ?? ''),
                (int)($registro['inconsistencias_count'] ?? 0),
            ]);
        }

        fclose($out);
        exit;
    }

    private function descargarExpedienteNominaCsv(array $expediente): void
    {
        if (function_exists('session_write_close')) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename = 'expediente_administrativo_nomina_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'periodo_id',
            'periodo_estado',
            'periodo_etiqueta',
            'periodo_inicio',
            'periodo_fin',
            'detalle_id',
            'trabajador_id',
            'trabajador_snapshot',
            'trabajador_actual',
            'rol_snapshot',
            'estado_auditoria',
            'estado_expediente',
            'bloqueos',
            'bruto_snapshot',
            'deducciones_snapshot',
            'neto_snapshot',
            'pendiente_snapshot',
            'pagos_caja_total',
            'reversiones_total',
            'saldo_auditoria',
            'pagos_count',
            'ultimo_pago_id',
            'ultimo_pago',
            'referencias_pago',
            'cajas',
        ]);

        $registros = is_array($expediente['registros'] ?? null) ? $expediente['registros'] : [];
        foreach ($registros as $registro) {
            $bloqueos = is_array($registro['bloqueos'] ?? null)
                ? implode(' | ', $registro['bloqueos'])
                : (string)($registro['bloqueos'] ?? '');

            fputcsv($out, [
                (int)($registro['periodo_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['periodo_estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_etiqueta'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_inicio'] ?? ''),
                $this->csvReportePagosCajaValor($registro['periodo_fecha_fin'] ?? ''),
                (int)($registro['detalle_id'] ?? 0),
                (int)($registro['trabajador_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['trabajador_snapshot_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_actual_nombre'] ?? ''),
                $this->csvReportePagosCajaValor($registro['trabajador_snapshot_rol'] ?? ''),
                $this->csvReportePagosCajaValor($registro['auditoria_estado'] ?? ''),
                $this->csvReportePagosCajaValor($registro['estado_expediente'] ?? ''),
                $this->csvReportePagosCajaValor($bloqueos),
                number_format((float)($registro['bruto_periodo'] ?? 0), 2, '.', ''),
                number_format((float)($registro['deducciones_informativas'] ?? 0), 2, '.', ''),
                number_format((float)($registro['neto_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                number_format((float)($registro['pagos_caja_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['reversiones_total'] ?? 0), 2, '.', ''),
                number_format((float)($registro['saldo_auditoria'] ?? 0), 2, '.', ''),
                (int)($registro['pagos_count'] ?? 0),
                (int)($registro['ultimo_pago_id'] ?? 0),
                $this->csvReportePagosCajaValor($registro['ultimo_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['referencias_pago'] ?? ''),
                $this->csvReportePagosCajaValor($registro['cajas'] ?? ''),
            ]);
        }

        fclose($out);
        exit;
    }

    private function csvReportePagosCajaValor($value): string
    {
        if ($value === null) {
            return '';
        }

        $texto = trim(str_replace(["\r\n", "\r", "\n"], ' ', (string)$value));

        // Una celda que empieza con = + - @ o tab se ejecuta como formula al
        // abrir el CSV en Excel/Sheets (CSV injection): se neutraliza con
        // apostrofe, salvo valores numericos legitimos (p.ej. -100.50).
        if ($texto !== '' && strpos("=+-@\t", $texto[0]) !== false && !is_numeric($texto)) {
            $texto = "'" . $texto;
        }

        return $texto;
    }

    private function csvReportePagosCajaUsuario(array $registro, string $prefijo): string
    {
        $nombre = $this->csvReportePagosCajaValor($registro[$prefijo . '_nombre'] ?? '');
        if ($nombre !== '') {
            return $nombre;
        }

        return $this->csvReportePagosCajaValor($registro[$prefijo . '_login'] ?? '');
    }

    private function reportePagosCajaVacio(array $filtros = []): array
    {
        return [
            'registros' => [],
            'resumen' => [
                'total_registros' => 0,
                'pagados_count' => 0,
                'revertidos_count' => 0,
                'egreso_original_total' => '0.00',
                'pagado_vigente_total' => '0.00',
                'revertido_total' => '0.00',
                'reversion_caja_total' => '0.00',
                'impacto_caja_neto' => '0.00',
            ],
            'por_estado' => [],
            'por_metodo' => [],
            'por_corte' => [],
            'filtros_normalizados' => [
                'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
                'buscar' => trim((string)($filtros['buscar'] ?? '')),
                'estado' => trim((string)($filtros['estado'] ?? 'todos')),
                'metodo_pago' => trim((string)($filtros['metodo_pago'] ?? 'todos')),
                'corte_id' => max(0, (int)($filtros['corte_id'] ?? 0)),
                'fecha_inicio' => trim((string)($filtros['fecha_inicio'] ?? '')),
                'fecha_fin' => trim((string)($filtros['fecha_fin'] ?? '')),
            ],
        ];
    }

    private function reporteNominaPeriodosVacio(array $filtros = []): array
    {
        $estado = trim((string)($filtros['estado'] ?? 'todos'));
        if (!in_array($estado, ['todos', 'cerrado', 'aprobado', 'anulado'], true)) {
            $estado = 'todos';
        }

        $tipoPeriodo = trim((string)($filtros['tipo_periodo'] ?? 'todos'));
        if (!in_array($tipoPeriodo, ['todos', 'semanal', 'quincenal', 'mensual', 'manual'], true)) {
            $tipoPeriodo = 'todos';
        }

        return [
            'registros' => [],
            'resumen' => [
                'total_registros' => 0,
                'cerrados_count' => 0,
                'aprobados_count' => 0,
                'anulados_count' => 0,
                'trabajadores_total' => 0,
                'bruto_total' => '0.00',
                'deducciones_total' => '0.00',
                'pagos_caja_aplicados_total' => '0.00',
                'reversiones_detectadas_total' => '0.00',
                'neto_sugerido_total' => '0.00',
                'pendiente_pago_total' => '0.00',
            ],
            'por_estado' => [],
            'filtros_normalizados' => [
                'fecha_inicio' => trim((string)($filtros['fecha_inicio'] ?? '')),
                'fecha_fin' => trim((string)($filtros['fecha_fin'] ?? '')),
                'estado' => $estado,
                'tipo_periodo' => $tipoPeriodo,
                'buscar' => trim((string)($filtros['buscar'] ?? '')),
            ],
        ];
    }

    private function reporteNominaPagosSnapshotVacio(array $filtros = []): array
    {
        $estado = trim((string)($filtros['estado'] ?? 'todos'));
        if (!in_array($estado, ['todos', 'pagado', 'revertido'], true)) {
            $estado = 'todos';
        }

        $conciliacion = trim((string)($filtros['conciliacion'] ?? 'todos'));
        if (!in_array($conciliacion, ['todos', 'ok', 'revisar'], true)) {
            $conciliacion = 'todos';
        }

        return [
            'registros' => [],
            'resumen' => [
                'total_registros' => 0,
                'ok_count' => 0,
                'revisar_count' => 0,
                'pagados_count' => 0,
                'revertidos_count' => 0,
                'monto_total' => '0.00',
                'monto_pagado_total' => '0.00',
                'monto_revertido_total' => '0.00',
                'reversion_caja_total' => '0.00',
            ],
            'por_estado' => [],
            'por_periodo' => [],
            'filtros_normalizados' => [
                'periodo_id' => max(0, (int)($filtros['periodo_id'] ?? 0)),
                'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
                'buscar' => trim((string)($filtros['buscar'] ?? '')),
                'estado' => $estado,
                'conciliacion' => $conciliacion,
                'fecha_inicio' => trim((string)($filtros['fecha_inicio'] ?? '')),
                'fecha_fin' => trim((string)($filtros['fecha_fin'] ?? '')),
            ],
        ];
    }

    private function auditoriaNominaConsolidadaVacio(array $filtros = []): array
    {
        $estadoSnapshot = trim((string)($filtros['estado_snapshot'] ?? 'todos'));
        if (!in_array($estadoSnapshot, ['todos', 'cerrado', 'aprobado', 'anulado'], true)) {
            $estadoSnapshot = 'todos';
        }

        $estadoAuditoria = trim((string)($filtros['estado_auditoria'] ?? 'todos'));
        if (!in_array($estadoAuditoria, ['todos', 'liquidado', 'parcial', 'sin_pago', 'revisar'], true)) {
            $estadoAuditoria = 'todos';
        }

        return [
            'registros' => [],
            'resumen' => [
                'total_registros' => 0,
                'trabajadores_total' => 0,
                'liquidado_count' => 0,
                'parcial_count' => 0,
                'sin_pago_count' => 0,
                'revisar_count' => 0,
                'bruto_total' => '0.00',
                'pendiente_snapshot_total' => '0.00',
                'pagos_caja_total' => '0.00',
                'reversiones_total' => '0.00',
                'saldo_auditoria_total' => '0.00',
            ],
            'por_estado' => [],
            'por_periodo' => [],
            'filtros_normalizados' => [
                'periodo_id' => max(0, (int)($filtros['periodo_id'] ?? 0)),
                'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
                'buscar' => trim((string)($filtros['buscar'] ?? '')),
                'estado_snapshot' => $estadoSnapshot,
                'estado_auditoria' => $estadoAuditoria,
                'fecha_inicio' => trim((string)($filtros['fecha_inicio'] ?? '')),
                'fecha_fin' => trim((string)($filtros['fecha_fin'] ?? '')),
            ],
        ];
    }

    private function expedienteNominaAdministrativoVacio(array $filtros = []): array
    {
        $estadoSnapshot = trim((string)($filtros['estado_snapshot'] ?? 'todos'));
        if (!in_array($estadoSnapshot, ['todos', 'cerrado', 'aprobado', 'anulado'], true)) {
            $estadoSnapshot = 'todos';
        }

        $estadoExpediente = trim((string)($filtros['estado_expediente'] ?? 'todos'));
        if (!in_array($estadoExpediente, ['todos', 'listo_revision', 'con_pendientes', 'requiere_correccion', 'bloqueado', 'anulado'], true)) {
            $estadoExpediente = 'todos';
        }

        return [
            'registros' => [],
            'resumen' => [
                'total_registros' => 0,
                'trabajadores_total' => 0,
                'listo_revision_count' => 0,
                'con_pendientes_count' => 0,
                'requiere_correccion_count' => 0,
                'bloqueado_count' => 0,
                'anulado_count' => 0,
                'bloqueos_total' => 0,
                'bruto_total' => '0.00',
                'pendiente_snapshot_total' => '0.00',
                'pagos_caja_total' => '0.00',
                'reversiones_total' => '0.00',
                'saldo_auditoria_total' => '0.00',
            ],
            'por_estado' => [],
            'por_periodo' => [],
            'bloqueos' => [],
            'filtros_normalizados' => [
                'periodo_id' => max(0, (int)($filtros['periodo_id'] ?? 0)),
                'trabajador_id' => max(0, (int)($filtros['trabajador_id'] ?? 0)),
                'buscar' => trim((string)($filtros['buscar'] ?? '')),
                'estado_snapshot' => $estadoSnapshot,
                'estado_expediente' => $estadoExpediente,
                'fecha_inicio' => trim((string)($filtros['fecha_inicio'] ?? '')),
                'fecha_fin' => trim((string)($filtros['fecha_fin'] ?? '')),
            ],
        ];
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function trabajadorActual(int $hotelId): ?array
    {
        $id = (int)($this->route_params['id'] ?? 0);
        return $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
    }

    private function erroresCamposTrabajador(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = function_exists('mb_strtolower') ? mb_strtolower($mensaje, 'UTF-8') : strtolower($mensaje);
            $lower = strtr($lower, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ñ' => 'n',
                'Á' => 'a',
                'É' => 'e',
                'Í' => 'i',
                'Ó' => 'o',
                'Ú' => 'u',
                'Ñ' => 'n',
            ]);
            $campo = null;

            if (strpos($lower, 'nombre') !== false) {
                $campo = 'nombre_completo';
            } elseif (strpos($lower, 'correo') !== false || strpos($lower, 'email') !== false) {
                $campo = 'email';
            } elseif (strpos($lower, 'salario') !== false) {
                $campo = 'salario_base';
            } elseif (strpos($lower, 'usuario') !== false) {
                $campo = 'usuario_id';
            } elseif (strpos($lower, 'identificacion') !== false) {
                $campo = 'identificacion';
            } elseif (strpos($lower, 'rol') !== false) {
                $campo = 'rol_laboral';
            } elseif (strpos($lower, 'telefono') !== false) {
                $campo = 'telefono';
            } elseif (strpos($lower, 'fecha') !== false) {
                $campo = 'fecha_alta';
            } elseif (strpos($lower, 'periodicidad') !== false) {
                $campo = 'periodicidad_pago';
            } elseif (strpos($lower, 'nota') !== false) {
                $campo = 'notas';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            } else {
                $fieldErrors['_global'][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function datosFormulario(): array
    {
        return [
            'usuario_id' => $this->getPost('usuario_id', null),
            'nombre_completo' => $this->getPost('nombre_completo', ''),
            'identificacion' => $this->getPost('identificacion', ''),
            'rol_laboral' => $this->getPost('rol_laboral', ''),
            'telefono' => $this->getPost('telefono', ''),
            'email' => $this->getPost('email', ''),
            'fecha_alta' => $this->getPost('fecha_alta', ''),
            'salario_base' => $this->getPost('salario_base', ''),
            'periodicidad_pago' => $this->getPost('periodicidad_pago', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosConceptoLaboral(): array
    {
        return [
            'tipo' => $this->getPost('tipo', ''),
            'efecto' => $this->getPost('efecto', ''),
            'monto' => $this->getPost('monto', ''),
            'concepto' => $this->getPost('concepto', ''),
            'periodo_inicio' => $this->getPost('periodo_inicio', ''),
            'periodo_fin' => $this->getPost('periodo_fin', ''),
            'fecha' => $this->getPost('fecha', ''),
            'referencia' => $this->getPost('referencia', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosAnticipoLaboral(): array
    {
        return [
            'monto' => $this->getPost('monto', ''),
            'fecha' => $this->getPost('fecha', ''),
            'motivo' => $this->getPost('motivo', ''),
            'referencia' => $this->getPost('referencia', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosPrestamoLaboral(): array
    {
        return [
            'monto' => $this->getPost('monto', ''),
            'fecha' => $this->getPost('fecha', ''),
            'plazo_meses' => $this->getPost('plazo_meses', ''),
            'abono_periodico' => $this->getPost('abono_periodico', ''),
            'motivo' => $this->getPost('motivo', ''),
            'referencia' => $this->getPost('referencia', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosAsistenciaLaboral(): array
    {
        return [
            'fecha' => $this->getPost('fecha', ''),
            'tipo' => $this->getPost('tipo', ''),
            'hora_entrada' => $this->getPost('hora_entrada', ''),
            'hora_salida' => $this->getPost('hora_salida', ''),
            'horas' => $this->getPost('horas', ''),
            'horas_extra' => $this->getPost('horas_extra', ''),
            'observaciones' => $this->getPost('observaciones', ''),
        ];
    }

    private function datosPagoCajaLaboral(): array
    {
        return [
            'monto' => $this->getPost('monto', ''),
            'metodo_pago' => $this->getPost('metodo_pago', ''),
            'referencia' => $this->getPost('referencia', ''),
            'periodo_inicio' => $this->getPost('periodo_inicio', ''),
            'periodo_fin' => $this->getPost('periodo_fin', ''),
            'concepto' => $this->getPost('concepto', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosPagoSnapshotNomina(): array
    {
        return [
            'monto' => $this->getPost('monto', ''),
            'metodo_pago' => $this->getPost('metodo_pago', ''),
            'referencia' => $this->getPost('referencia', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function datosReversionPagoCajaLaboral(): array
    {
        return [
            'motivo' => $this->getPost('motivo', ''),
        ];
    }

    private function usuarioIdActual(): ?int
    {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }

    private function nominaPeriodoTokenKey(string $fechaInicio, string $fechaFin): string
    {
        return trim($fechaInicio) . ':' . trim($fechaFin);
    }

    private function generarNominaPeriodoToken(string $accion, string $key): string
    {
        if (!isset($_SESSION['trabajador_nomina_periodo_tokens']) || !is_array($_SESSION['trabajador_nomina_periodo_tokens'])) {
            $_SESSION['trabajador_nomina_periodo_tokens'] = [];
        }

        if (!isset($_SESSION['trabajador_nomina_periodo_tokens'][$accion]) || !is_array($_SESSION['trabajador_nomina_periodo_tokens'][$accion])) {
            $_SESSION['trabajador_nomina_periodo_tokens'][$accion] = [];
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['trabajador_nomina_periodo_tokens'][$accion][$key] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirNominaPeriodoToken(string $accion, string $key, string $token): bool
    {
        $token = trim($token);
        $registro = $_SESSION['trabajador_nomina_periodo_tokens'][$accion][$key] ?? null;
        unset($_SESSION['trabajador_nomina_periodo_tokens'][$accion][$key]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }

    private function generarPagoCajaToken(int $trabajadorId): string
    {
        if (!isset($_SESSION['trabajador_pago_caja_tokens']) || !is_array($_SESSION['trabajador_pago_caja_tokens'])) {
            $_SESSION['trabajador_pago_caja_tokens'] = [];
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['trabajador_pago_caja_tokens'][$trabajadorId] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirPagoCajaToken(int $trabajadorId, string $token): bool
    {
        $token = trim($token);
        $registro = $_SESSION['trabajador_pago_caja_tokens'][$trabajadorId] ?? null;
        unset($_SESSION['trabajador_pago_caja_tokens'][$trabajadorId]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }

    private function generarReversionPagoCajaToken(int $trabajadorId, int $pagoCajaId): string
    {
        if (!isset($_SESSION['trabajador_reversion_pago_caja_tokens']) || !is_array($_SESSION['trabajador_reversion_pago_caja_tokens'])) {
            $_SESSION['trabajador_reversion_pago_caja_tokens'] = [];
        }

        $key = $trabajadorId . ':' . $pagoCajaId;
        $token = bin2hex(random_bytes(16));
        $_SESSION['trabajador_reversion_pago_caja_tokens'][$key] = [
            'token' => $token,
            'created_at' => time(),
        ];

        return $token;
    }

    private function consumirReversionPagoCajaToken(int $trabajadorId, int $pagoCajaId, string $token): bool
    {
        $token = trim($token);
        $key = $trabajadorId . ':' . $pagoCajaId;
        $registro = $_SESSION['trabajador_reversion_pago_caja_tokens'][$key] ?? null;
        unset($_SESSION['trabajador_reversion_pago_caja_tokens'][$key]);

        if (!is_array($registro) || $token === '') {
            return false;
        }

        if ((int)($registro['created_at'] ?? 0) < time() - 3600) {
            return false;
        }

        return hash_equals((string)($registro['token'] ?? ''), $token);
    }

    private function requireWritePermission(string $permission): void
    {
        if (function_exists('require_permission')) {
            require_permission($permission);
        }
    }

    /**
     * Pagar nomina: 'nomina.pagar' (la casilla que se ofrece hoy en la matriz) o
     * 'personal.pagar' (la que traen los roles sembrados de antes, incluido el
     * preset 'administrador'). Espejo exacto del gate de NominaController, que es
     * quien decide si se pinta el boton.
     */
    private function requirePagoNominaPermission(): void
    {
        if (!function_exists('can_any')) {
            $this->requireWritePermission('personal.pagar');
            return;
        }

        if (!can_any(['nomina.pagar', 'personal.pagar'])) {
            deny_access_403('No tienes permiso para registrar pagos de nómina.');
        }
    }

    private function auditar(string $accion, ?array $antes, ?array $despues, int $trabajadorId): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'trabajador',
                'entidad_id' => (string)$trabajadorId,
                'descripcion' => 'Cambio en catalogo de trabajadores',
                'datos_antes' => $antes,
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar trabajador: ' . $e->getMessage());
        }
    }

    private function auditarConceptoLaboral(array $trabajador, array $concepto, int $conceptoId): void
    {
        try {
            AuditService::record('trabajadores.concepto_laboral_registrado', [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'trabajador_concepto_laboral',
                'entidad_id' => (string)$conceptoId,
                'descripcion' => 'Concepto laboral manual registrado sin Caja',
                'datos_despues' => [
                    'trabajador_id' => (int)($trabajador['id'] ?? 0),
                    'trabajador_nombre' => $trabajador['nombre_completo'] ?? null,
                    'concepto' => $concepto,
                    'sin_caja' => true,
                    'sin_pago_real' => true,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar concepto laboral: ' . $e->getMessage());
        }
    }

    private function auditarMovimientoLaboral(string $accion, string $entidadTipo, array $trabajador, array $movimiento, int $movimientoId): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => (string)$movimientoId,
                'descripcion' => 'Movimiento laboral manual registrado sin Caja',
                'datos_despues' => [
                    'trabajador_id' => (int)($trabajador['id'] ?? 0),
                    'trabajador_nombre' => $trabajador['nombre_completo'] ?? null,
                    'movimiento' => $movimiento,
                    'sin_caja' => true,
                    'sin_pago_real' => true,
                    'sin_abono' => true,
                ],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar movimiento laboral: ' . $e->getMessage());
        }
    }

    private function auditarNominaPeriodo(string $accion, int $periodoId, ?array $antes, ?array $despues): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'trabajador_nomina_periodo',
                'entidad_id' => (string)$periodoId,
                'descripcion' => 'Cambio administrativo de periodo de pre-nomina sin Caja',
                'datos_antes' => $antes,
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar periodo de pre-nomina: ' . $e->getMessage());
        }
    }
}
