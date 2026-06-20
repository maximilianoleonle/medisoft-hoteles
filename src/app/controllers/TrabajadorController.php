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

        View::renderTemplate('trabajadores/ver', [
            'title' => 'Trabajador #' . $id . ' - ' . current_hotel_display_name(),
            'trabajador' => $trabajador,
            'resumenLedger' => $this->trabajadorModel->resumenLedgerPorTrabajador($id, $hotelId),
            'conceptosLaborales' => $this->trabajadorModel->conceptosLaboralesPorTrabajador($id, $hotelId, 12),
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
