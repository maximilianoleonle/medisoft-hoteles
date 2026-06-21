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

class TrabajadorController extends Controller
{
    private $trabajadorModel;
    private $tareaModel;
    private $pagoCajaService;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->trabajadorModel = new Trabajador();
        $this->tareaModel = new TareaOperativa();
        $this->pagoCajaService = new TrabajadorPagoCajaService();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('usuarios');
        }

        if (function_exists('require_permission')) {
            require_permission('usuarios.view');
        }

        return true;
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
        ]);
    }

    public function exportarNominaPreviewAction(): void
    {
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
        ]);
    }

    public function exportarReportePagosCajaAction(): void
    {
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
            $documentoModel = new Documento();
            $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'trabajador', $id, 10);
        } catch (Throwable $e) {
            $documentosEntidad = [];
        }

        $pagoCaja = [
            'elegible' => false,
            'motivo_bloqueo' => 'No se pudo evaluar el pago laboral con Caja.',
            'corte' => null,
            'metodos_pago' => [],
            'monto_maximo' => '0.00',
            'saldo' => [],
        ];
        $pagoCajaToken = null;
        try {
            $pagoCaja = $this->pagoCajaService->evaluarPago($hotelId, $id);
            if (!empty($pagoCaja['elegible'])) {
                $pagoCajaToken = $this->generarPagoCajaToken($id);
            }
        } catch (Throwable $e) {
            $pagoCaja['motivo_bloqueo'] = $e->getMessage();
        }

        $pagosCajaLaborales = $this->trabajadorModel->pagosCajaPorTrabajador($id, $hotelId, 20);
        $reversionesPagoCaja = [];
        $reversionPagoCajaTokens = [];
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

        View::renderTemplate('trabajadores/ver', [
            'title' => 'Trabajador #' . $id . ' - ' . current_hotel_display_name(),
            'trabajador' => $trabajador,
            'resumenLedger' => $this->trabajadorModel->resumenLedgerPorTrabajador($id, $hotelId),
            'conceptosLaborales' => $this->trabajadorModel->conceptosLaboralesPorTrabajador($id, $hotelId, 12),
            'pagosCajaLaborales' => $pagosCajaLaborales,
            'reversionesPagoCaja' => $reversionesPagoCaja,
            'reversionPagoCajaTokens' => $reversionPagoCajaTokens,
            'anticiposRecientes' => $this->trabajadorModel->anticiposPorTrabajador($id, $hotelId, 12),
            'prestamosRecientes' => $this->trabajadorModel->prestamosPorTrabajador($id, $hotelId, 12),
            'asistenciasRecientes' => $this->trabajadorModel->ultimosMovimientosPorTrabajador($id, $hotelId, 20),
            'ledgerDisponible' => $this->trabajadorModel->tablasLedgerDisponibles(),
            'tareasContextuales' => $this->tareaModel->listarPorEntidadHotel($hotelId, 'trabajador', $id, 8),
            'pagoCaja' => $pagoCaja,
            'pagoCajaToken' => $pagoCajaToken,
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'trabajador',
                'id' => $id,
                'label' => 'Trabajador',
            ],
        ]);
    }

    public function crearAction(): void
    {
        $this->requireWritePermission('usuarios.create');
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
        $this->requireWritePermission('usuarios.create');

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

            set_mensaje('Trabajador creado correctamente.', 'success');
            $this->redirect('trabajadores/' . $trabajadorId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear el trabajador: ' . $e->getMessage(), 'error');
            $this->redirect('trabajadores/crear');
        }
    }

    public function editarAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

        $hotelId = $this->hotelIdActual();
        $trabajador = $this->trabajadorActual($hotelId);
        if (!$trabajador) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        View::renderTemplate('trabajadores/form', [
            'title' => 'Editar trabajador - ' . current_hotel_display_name(),
            'modo' => 'editar',
            'trabajador' => $trabajador,
            'usuariosVinculables' => $this->trabajadorModel->usuariosVinculablesPorHotel($hotelId),
        ]);
    }

    public function actualizarAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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

            set_mensaje('Trabajador actualizado correctamente.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje('No se pudo actualizar el trabajador: ' . $e->getMessage(), 'error');
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
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo registrar el concepto laboral: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarAnticipoLaboralAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo registrar el anticipo laboral: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarPrestamoLaboralAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo registrar el prestamo laboral: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarAsistenciaLaboralAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo registrar la asistencia laboral: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    public function registrarPagoCajaAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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
                'Pago laboral registrado. Movimiento Caja #' . (int)$resultado['movimiento_caja_id']
                . ', saldo estimado nuevo ' . number_format((float)$resultado['saldo_posterior_estimado'], 2) . '.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje('No se pudo registrar el pago laboral con Caja: ' . $e->getMessage(), 'error');
        }

        $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
    }

    public function revertirPagoCajaAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo revertir el pago laboral con Caja: ' . $e->getMessage(), 'error');
        }

        $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
    }

    private function cambiarEstado(string $estado, string $mensaje, string $accion): void
    {
        $this->requireWritePermission('usuarios.edit');

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
            set_mensaje('No se pudo cambiar el estado del trabajador: ' . $e->getMessage(), 'error');
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

    private function csvReportePagosCajaValor($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(str_replace(["\r\n", "\r", "\n"], ' ', (string)$value));
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
}
