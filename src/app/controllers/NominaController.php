<?php
/**
 * Nomina core (bloque nomina_avanzada) - Fase 1: base tecnica.
 *
 * Dashboard informativo y configuracion de nomina por negocio (tenant).
 * El motor de calculo, catalogos e incidencias llegan en fases posteriores.
 * Este controlador NO toca Caja, snapshots de pre-nomina ni pagos: la
 * operacion laboral vigente sigue viviendo en /trabajadores (bloque personal).
 */

require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/NominaCatalogoService.php';
require_once __DIR__ . '/../services/NominaSalarioService.php';
require_once __DIR__ . '/../services/NominaIncidenciaService.php';
require_once __DIR__ . '/../services/NominaCalculoService.php';
require_once __DIR__ . '/../services/NominaCierreService.php';
require_once __DIR__ . '/../models/Trabajador.php';

class NominaController extends Controller {

    /** Valores permitidos de la configuracion (whitelist de servidor). */
    private $modosValidos = ['simplificada', 'hibrida', 'legal'];
    private $paisesValidos = ['MX'];
    private $redondeosValidos = ['centavos', 'pesos'];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('nomina_avanzada');
        }

        if (function_exists('require_permission')) {
            require_permission('nomina.view');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $config = $this->configuracionActual($hotelId);

        $personalActivo = function_exists('hotel_has_module') && hotel_has_module('personal', $hotelId);
        $stats = $this->estadisticasBase($hotelId, $personalActivo);

        View::renderTemplate('nomina/index', [
            'title' => 'Nomina - ' . current_hotel_display_name(),
            'config' => $config,
            'personalActivo' => $personalActivo,
            'stats' => $stats,
            'puedeConfigurar' => can('nomina.configurar'),
            'ahoraTeToca' => $this->ahoraTeToca($hotelId),
        ]);
    }

    /**
     * "Ahora te toca": acciones pendientes REALES del ciclo de nomina para el
     * centro de mando del inicio. Solo lectura y tolerante a errores.
     */
    private function ahoraTeToca($hotelId): array {
        $pendientes = [];
        try {
            $db = Database::getInstance();

            // Periodos cerrados esperando aprobacion (paso 3).
            $st = $db->query(
                "SELECT id, etiqueta FROM trabajador_nomina_periodos
                 WHERE hotel_id = ? AND motor = 'v2' AND estado = 'cerrado'
                 ORDER BY fecha_fin DESC LIMIT 5",
                [$hotelId]
            );
            foreach (($st !== false ? $st->fetchAll() : []) as $p) {
                $pendientes[] = [
                    'icono' => 'fa-check-double',
                    'texto' => 'El periodo "' . (string) $p['etiqueta'] . '" está cerrado y espera tu aprobación.',
                    'accion' => 'Revisar y aprobar',
                    'url' => url('nomina/periodos/' . (int) $p['id']),
                ];
            }

            // Periodos aprobados con dinero sin pagar (paso 4). El pendiente
            // del encabezado es del momento del cierre: el VIVO se concilia
            // contra los pagos de Caja, igual que en la pantalla del periodo.
            $st = $db->query(
                "SELECT p.* FROM trabajador_nomina_periodos p
                 WHERE p.hotel_id = ? AND p.motor = 'v2' AND p.estado = 'aprobado'
                 ORDER BY p.fecha_fin DESC LIMIT 5",
                [$hotelId]
            );
            $modeloTrabajador = new Trabajador();
            foreach (($st !== false ? $st->fetchAll() : []) as $p) {
                $std = $db->query(
                    "SELECT * FROM trabajador_nomina_periodo_detalles WHERE periodo_id = ? AND hotel_id = ?",
                    [(int) $p['id'], $hotelId]
                );
                $conciliado = $modeloTrabajador->conciliarNominaPeriodoSnapshotConPagosCaja(
                    $p,
                    $std !== false ? $std->fetchAll() : [],
                    $hotelId
                );
                $pendienteVivo = (float) ($conciliado['periodo']['pendiente_pago_total'] ?? 0);
                if ($pendienteVivo > 0.009) {
                    $pendientes[] = [
                        'icono' => 'fa-hand-holding-dollar',
                        'texto' => 'Hay $' . number_format($pendienteVivo, 2) . ' aprobados sin pagar del periodo "' . (string) $p['etiqueta'] . '".',
                        'accion' => 'Ir a pagar',
                        'url' => url('nomina/periodos/' . (int) $p['id']),
                    ];
                }
            }

            // Incidencias propuestas esperando visto bueno.
            $st = $db->query(
                "SELECT COUNT(*) AS total, MIN(fecha) AS desde, MAX(fecha) AS hasta
                 FROM nomina_incidencias WHERE hotel_id = ? AND estado = 'pendiente'",
                [$hotelId]
            );
            $inc = $st !== false ? $st->fetch() : null;
            if ($inc && (int) $inc['total'] > 0) {
                $pendientes[] = [
                    'icono' => 'fa-clipboard-check',
                    'texto' => (int) $inc['total'] . ' incidencia(s) propuestas esperan tu visto bueno (no entran a la nómina hasta aprobarlas).',
                    'accion' => 'Revisarlas',
                    'url' => url('nomina/incidencias?desde=' . (string) $inc['desde'] . '&hasta=' . (string) $inc['hasta'] . '&estado=pendiente'),
                ];
            }
        } catch (Throwable $e) {
            return [];
        }

        return $pendientes;
    }

    public function configuracionAction() {
        if (function_exists('require_permission')) {
            require_permission('nomina.configurar');
        }

        $hotelId = $this->hotelIdActual();

        View::renderTemplate('nomina/configuracion', [
            'title' => 'Configuracion de nomina - ' . current_hotel_display_name(),
            'config' => $this->configuracionActual($hotelId),
            'modos' => $this->modosValidos,
            'redondeos' => $this->redondeosValidos,
        ]);
    }

    public function guardarConfiguracionAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/configuracion');
        }

        $this->validateCSRF();

        if (function_exists('require_permission')) {
            require_permission('nomina.configurar');
        }

        $hotelId = $this->hotelIdActual();
        $antes = $this->configuracionActual($hotelId);

        $modo = (string) $this->getPost('modo', $antes['modo']);
        $pais = strtoupper(trim((string) $this->getPost('pais', $antes['pais'])));
        $redondeo = (string) $this->getPost('redondeo', $antes['redondeo']);

        require_once __DIR__ . '/../services/NominaAdaptadorRegistry.php';
        $giro = (string) $this->getPost('giro', $antes['giro']);

        $nuevos = [
            'giro' => in_array($giro, NominaAdaptadorRegistry::GIROS_VALIDOS, true) ? $giro : 'hotel',
            'modo' => in_array($modo, $this->modosValidos, true) ? $modo : 'simplificada',
            'pais' => in_array($pais, $this->paisesValidos, true) ? $pais : 'MX',
            'redondeo' => in_array($redondeo, $this->redondeosValidos, true) ? $redondeo : 'centavos',
            'permitir_horas_extra' => $this->getPost('permitir_horas_extra') ? true : false,
            'permitir_descuentos_manuales' => $this->getPost('permitir_descuentos_manuales') ? true : false,
            'requiere_aprobacion_cierre' => $this->getPost('requiere_aprobacion_cierre') ? true : false,
            'permitir_reapertura' => $this->getPost('permitir_reapertura') ? true : false,
        ];

        $claves = [
            'negocio.giro' => ['valor' => $nuevos['giro'], 'tipo' => 'string'],
            'nomina.modo' => ['valor' => $nuevos['modo'], 'tipo' => 'string'],
            'nomina.pais' => ['valor' => $nuevos['pais'], 'tipo' => 'string'],
            'nomina.redondeo' => ['valor' => $nuevos['redondeo'], 'tipo' => 'string'],
            'nomina.permitir_horas_extra' => ['valor' => $nuevos['permitir_horas_extra'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.permitir_descuentos_manuales' => ['valor' => $nuevos['permitir_descuentos_manuales'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.requiere_aprobacion_cierre' => ['valor' => $nuevos['requiere_aprobacion_cierre'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.permitir_reapertura' => ['valor' => $nuevos['permitir_reapertura'] ? '1' : '0', 'tipo' => 'boolean'],
        ];

        $ok = true;

        try {
            $db = Database::getInstance();

            foreach ($claves as $clave => $dato) {
                $resultado = $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'nomina', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
                    [$hotelId, $clave, $dato['valor'], $dato['tipo']]
                );

                if ($resultado === false) {
                    throw new Exception('No se pudo guardar la clave ' . $clave);
                }
            }

            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('error', 'Nomina: error al guardar configuracion', ['error' => $e->getMessage()]);
            }
            $ok = false;
        }

        if ($ok && $antes !== $nuevos) {
            AuditService::record('nomina.configuracion_actualizada', [
                'hotel_id' => $hotelId,
                'usuario_id' => user_id(),
                'entidad_tipo' => 'nomina_configuracion',
                'entidad_id' => (string) $hotelId,
                'descripcion' => 'Actualizo la configuracion de nomina del negocio',
                'datos_antes' => $antes,
                'datos_despues' => $nuevos,
            ]);
        }

        set_mensaje(
            $ok ? 'Configuracion de nomina guardada.' : 'No se pudo guardar la configuracion de nomina.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('nomina/configuracion');
    }

    /* ------------------------------------------------------------------ */
    /* Fase 2: catalogos internos                                          */
    /* ------------------------------------------------------------------ */

    public function catalogosAction() {
        $hotelId = $this->hotelIdActual();
        $servicio = new NominaCatalogoService();

        $tipo = (string) $this->getQuery('tipo', 'departamentos');
        if (!$servicio->esTipoValido($tipo)) {
            $tipo = 'departamentos';
        }

        $puedeConfigurar = can('nomina.configurar');

        // Primera visita de un negocio sin conceptos: sembrar los base.
        if ($puedeConfigurar) {
            try {
                $servicio->sembrarConceptosBase($hotelId, user_id());
            } catch (Throwable $e) {
                // Siembra best-effort: la pantalla funciona sin conceptos.
            }
        }

        View::renderTemplate('nomina/catalogos', [
            'title' => 'Catalogos de nomina - ' . current_hotel_display_name(),
            'tipo' => $tipo,
            'registros' => $servicio->listar($hotelId, $tipo),
            'conteos' => $servicio->conteos($hotelId),
            'departamentosActivos' => $servicio->listar($hotelId, 'departamentos', true),
            'puedeConfigurar' => $puedeConfigurar,
        ]);
    }

    public function catalogoGuardarAction($tipo) {
        $this->requiereEscrituraCatalogo($tipo);
        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCatalogoService())->crear($hotelId, (string) $tipo, $_POST, user_id());
            set_mensaje('Registro creado en el catalogo.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/catalogos?tipo=' . urlencode((string) $tipo));
    }

    public function catalogoActualizarAction($tipo, $id) {
        $this->requiereEscrituraCatalogo($tipo);
        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCatalogoService())->actualizar($hotelId, (string) $tipo, (int) $id, $_POST, user_id());
            set_mensaje('Registro actualizado.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/catalogos?tipo=' . urlencode((string) $tipo));
    }

    public function catalogoAlternarAction($tipo, $id) {
        $this->requiereEscrituraCatalogo($tipo);
        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCatalogoService())->alternar($hotelId, (string) $tipo, (int) $id, user_id());
            set_mensaje('Estado del registro actualizado.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/catalogos?tipo=' . urlencode((string) $tipo));
    }

    /* ------------------------------------------------------------------ */
    /* Fase 2: empleados (ficha de nomina sobre trabajadores existentes)   */
    /* ------------------------------------------------------------------ */

    public function empleadosAction() {
        $hotelId = $this->hotelIdActual();

        $buscar = trim((string) $this->getQuery('buscar', ''));
        $estado = (string) $this->getQuery('estado', 'activo');
        if (!in_array($estado, ['activo', 'inactivo', 'baja', 'todos'], true)) {
            $estado = 'activo';
        }

        $empleados = [];
        try {
            $db = Database::getInstance();
            $sql = "SELECT t.id, t.nombre_completo, t.rol_laboral, t.estado, t.periodicidad_pago,
                           p.nombre AS puesto, d.nombre AS departamento,
                           g.nombre AS grupo_nomina, c.nombre AS tipo_contrato,
                           EXISTS(SELECT 1 FROM trabajador_salarios s
                                  WHERE s.hotel_id = t.hotel_id AND s.trabajador_id = t.id) AS tiene_salario
                    FROM trabajadores t
                    LEFT JOIN nomina_puestos p ON p.id = t.puesto_id
                    LEFT JOIN nomina_departamentos d ON d.id = t.departamento_id
                    LEFT JOIN nomina_grupos g ON g.id = t.grupo_nomina_id
                    LEFT JOIN nomina_tipos_contrato c ON c.id = t.tipo_contrato_id
                    WHERE t.hotel_id = ?";
            $params = [$hotelId];

            if ($estado !== 'todos') {
                $sql .= " AND t.estado = ?";
                $params[] = $estado;
            }
            if ($buscar !== '') {
                $sql .= " AND t.nombre_completo LIKE ?";
                $params[] = '%' . $buscar . '%';
            }
            $sql .= " ORDER BY t.nombre_completo ASC LIMIT 300";

            $st = $db->query($sql, $params);
            $empleados = $st !== false ? $st->fetchAll() : [];
        } catch (Throwable $e) {
            $empleados = [];
        }

        View::renderTemplate('nomina/empleados', [
            'title' => 'Empleados de nomina - ' . current_hotel_display_name(),
            'empleados' => $empleados,
            'buscar' => $buscar,
            'estado' => $estado,
            'personalActivo' => function_exists('hotel_has_module') && hotel_has_module('personal', $hotelId),
        ]);
    }

    public function empleadoFichaAction($id) {
        $hotelId = $this->hotelIdActual();
        $trabajadorId = (int) $id;

        $db = Database::getInstance();
        $st = $db->query(
            "SELECT t.*, p.nombre AS puesto_nombre, d.nombre AS departamento_nombre,
                    g.nombre AS grupo_nombre, c.nombre AS contrato_nombre
             FROM trabajadores t
             LEFT JOIN nomina_puestos p ON p.id = t.puesto_id
             LEFT JOIN nomina_departamentos d ON d.id = t.departamento_id
             LEFT JOIN nomina_grupos g ON g.id = t.grupo_nomina_id
             LEFT JOIN nomina_tipos_contrato c ON c.id = t.tipo_contrato_id
             WHERE t.id = ? AND t.hotel_id = ?",
            [$trabajadorId, $hotelId]
        );
        $trabajador = $st !== false ? $st->fetch() : null;

        if (!$trabajador) {
            set_mensaje('El empleado no existe en este negocio.', 'error');
            $this->redirect('nomina/empleados');
        }

        $catalogo = new NominaCatalogoService();
        $puedeSalarios = can('nomina.salarios');

        $salarios = new NominaSalarioService();

        View::renderTemplate('nomina/empleado_ficha', [
            'title' => 'Ficha de nomina - ' . current_hotel_display_name(),
            'trabajador' => $trabajador,
            'puestos' => $catalogo->listar($hotelId, 'puestos', true),
            'departamentos' => $catalogo->listar($hotelId, 'departamentos', true),
            'contratos' => $catalogo->listar($hotelId, 'tipos_contrato', true),
            'grupos' => $catalogo->listar($hotelId, 'grupos', true),
            'puedeEmpleados' => can('nomina.empleados'),
            'puedeSalarios' => $puedeSalarios,
            'salarioVigente' => $puedeSalarios ? $salarios->vigente($hotelId, $trabajadorId) : null,
            'historialSalarios' => $puedeSalarios ? $salarios->historial($hotelId, $trabajadorId) : [],
        ]);
    }

    /**
     * Alta de empleado como seccion espejo de /trabajadores/crear, sin salir del
     * flujo de Nomina. Crea el MISMO registro (tabla trabajadores) con la MISMA
     * fuente de verdad (Trabajador::crearParaHotel): solo cambia el contexto de
     * navegacion. Exige el modulo Personal y el permiso personal.gestionar porque
     * el registro pertenece a ese bloque (misma autoridad que el alta original).
     */
    public function empleadoCrearAction() {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('personal');
        }
        if (function_exists('require_permission')) {
            require_permission('personal.gestionar');
        }

        $hotelId = $this->hotelIdActual();

        View::renderTemplate('trabajadores/form', [
            'title' => 'Nuevo empleado - ' . current_hotel_display_name(),
            'modo' => 'crear',
            'contexto' => 'nomina',
            'trabajador' => [],
            'usuariosVinculables' => (new Trabajador())->usuariosVinculablesPorHotel($hotelId),
        ]);
    }

    public function empleadoGuardarAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/empleados');
            return;
        }
        $this->validateCSRF();
        if (function_exists('require_hotel_module')) {
            require_hotel_module('personal');
        }
        if (function_exists('require_permission')) {
            require_permission('personal.gestionar');
        }

        $hotelId = $this->hotelIdActual();
        $modelo = new Trabajador();

        try {
            $trabajadorId = $modelo->crearParaHotel($hotelId, $this->datosFormularioEmpleado(), user_id());

            try {
                AuditService::record('trabajadores.creado', [
                    'hotel_id' => $hotelId,
                    'usuario_id' => user_id(),
                    'entidad_tipo' => 'trabajador',
                    'entidad_id' => (string) $trabajadorId,
                    'descripcion' => 'Alta de empleado desde Nomina',
                    'datos_antes' => null,
                    'datos_despues' => $modelo->buscarPorIdHotel($trabajadorId, $hotelId),
                ]);
            } catch (Throwable $e) {
                error_log('No se pudo auditar alta de empleado desde nomina: ' . $e->getMessage());
            }

            clear_old_input();
            set_mensaje('Empleado creado correctamente. Ahora asignale puesto, grupo y salario.', 'success');
            $this->redirect('nomina/empleados/' . $trabajadorId);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'crear el empleado');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposEmpleado([$e->getMessage()]));
            $this->redirect('nomina/empleados/crear');
        }
    }

    /** Datos del formulario de alta de empleado (espejo de TrabajadorController). */
    private function datosFormularioEmpleado(): array {
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

    /** Mapea el mensaje de validacion al campo para que el form lo resalte inline. */
    private function erroresCamposEmpleado(array $errores): array {
        $mapa = [
            'nombre' => 'nombre_completo',
            'correo' => 'email',
            'email' => 'email',
            'salario' => 'salario_base',
            'usuario' => 'usuario_id',
            'identificacion' => 'identificacion',
            'telefono' => 'telefono',
            'periodicidad' => 'periodicidad_pago',
        ];

        $fieldErrors = [];
        foreach ($errores as $mensaje) {
            $mensaje = trim((string) $mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = function_exists('mb_strtolower') ? mb_strtolower($mensaje, 'UTF-8') : strtolower($mensaje);
            $lower = strtr($lower, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);

            $campo = '_global';
            foreach ($mapa as $needle => $target) {
                if (strpos($lower, $needle) !== false) {
                    $campo = $target;
                    break;
                }
            }
            $fieldErrors[$campo][] = $mensaje;
        }

        return $fieldErrors;
    }

    public function empleadoAsignacionesAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/empleados');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.empleados');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCatalogoService())->asignarATrabajador($hotelId, (int) $id, [
                'puesto_id' => $this->getPost('puesto_id'),
                'departamento_id' => $this->getPost('departamento_id'),
                'tipo_contrato_id' => $this->getPost('tipo_contrato_id'),
                'grupo_nomina_id' => $this->getPost('grupo_nomina_id'),
            ], user_id());
            set_mensaje('Asignaciones de nomina guardadas.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/empleados/' . (int) $id);
    }

    public function empleadoSalarioAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/empleados');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.salarios');
        }

        $hotelId = $this->hotelIdActual();

        // Candado de flujo (espeja el bloqueo de la ficha): el salario se aplica
        // sobre un calendario de pago; sin grupo de pago asignado (Paso 1) no hay
        // a que aplicarlo. Se valida en servidor para que no se pueda registrar
        // por POST directo saltandose las asignaciones.
        $st = Database::getInstance()->query(
            "SELECT grupo_nomina_id FROM trabajadores WHERE id = ? AND hotel_id = ?",
            [(int) $id, $hotelId]
        );
        $trabActual = $st !== false ? $st->fetch() : null;
        if (!$trabActual) {
            set_mensaje('El empleado no existe en este negocio.', 'error');
            $this->redirect('nomina/empleados');
        }
        if (empty($trabActual['grupo_nomina_id'])) {
            set_mensaje('Primero asígnale un grupo de pago (Paso 1) para poder registrar su salario.', 'error');
            $this->redirect('nomina/empleados/' . (int) $id);
        }

        try {
            $resultado = (new NominaSalarioService())->registrarCambio($hotelId, (int) $id, [
                'salario' => $this->getPost('salario'),
                'esquema' => $this->getPost('esquema'),
                'vigente_desde' => $this->getPost('vigente_desde'),
                'motivo' => $this->getPost('motivo'),
            ], user_id());
            set_mensaje($resultado['message'] ?? 'Cambio salarial registrado.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/empleados/' . (int) $id);
    }

    /* ------------------------------------------------------------------ */
    /* Fase 3: incidencias                                                 */
    /* ------------------------------------------------------------------ */

    public function incidenciasAction() {
        $hotelId = $this->hotelIdActual();
        $servicio = new NominaIncidenciaService();
        $catalogo = new NominaCatalogoService();

        $filtros = [
            'desde' => (string) $this->getQuery('desde', date('Y-m-01')),
            'hasta' => (string) $this->getQuery('hasta', date('Y-m-d')),
            'estado' => (string) $this->getQuery('estado', ''),
            'trabajador_id' => (int) $this->getQuery('trabajador_id', 0),
        ];

        $trabajadores = [];
        try {
            $db = Database::getInstance();
            $st = $db->query(
                "SELECT id, nombre_completo FROM trabajadores WHERE hotel_id = ? AND estado = 'activo' ORDER BY nombre_completo ASC LIMIT 300",
                [$hotelId]
            );
            $trabajadores = $st !== false ? $st->fetchAll() : [];
        } catch (Throwable $e) {
            $trabajadores = [];
        }

        View::renderTemplate('nomina/incidencias', [
            'title' => 'Incidencias de nomina - ' . current_hotel_display_name(),
            'incidencias' => $servicio->listar($hotelId, $filtros),
            'filtros' => $filtros,
            'conceptos' => $catalogo->listar($hotelId, 'conceptos', true),
            'trabajadores' => $trabajadores,
            'puedeCapturar' => can('nomina.incidencias'),
        ]);
    }

    public function incidenciaCrearAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/incidencias');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.incidencias');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaIncidenciaService())->registrar($hotelId, [
                'trabajador_id' => $this->getPost('trabajador_id'),
                'concepto_id' => $this->getPost('concepto_id'),
                'fecha' => $this->getPost('fecha'),
                'cantidad' => $this->getPost('cantidad'),
                'monto' => $this->getPost('monto'),
                'descripcion' => $this->getPost('descripcion'),
            ], user_id());
            $fechaLegible = date('d/m/Y', strtotime((string) $this->getPost('fecha'))) ?: (string) $this->getPost('fecha');
            set_mensaje('Incidencia registrada. Entrará a la nómina cuando calcules el periodo que incluya el ' . $fechaLegible . ' (pestaña Periodos).', 'success');

            // Volver con el filtro abarcando la fecha capturada: sin esto, una
            // incidencia futura (o de un mes previo) quedaria fuera del rango
            // por defecto y pareceria que no se guardo.
            $fecha = (string) $this->getPost('fecha');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $desde = min(date('Y-m-01'), $fecha);
                $hasta = max(date('Y-m-d'), $fecha);
                $this->redirect('nomina/incidencias?desde=' . $desde . '&hasta=' . $hasta);
                return;
            }
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/incidencias');
    }

    public function incidenciaEstadoAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/incidencias');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.incidencias');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaIncidenciaService())->cambiarEstado($hotelId, (int) $id, (string) $this->getPost('estado'), user_id());
            set_mensaje('Incidencia actualizada.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/incidencias');
    }

    /* ------------------------------------------------------------------ */
    /* Fase 3: periodos v2 (preview, cierre, aprobacion, anulacion)        */
    /* ------------------------------------------------------------------ */

    public function periodosAction() {
        $hotelId = $this->hotelIdActual();
        $catalogo = new NominaCatalogoService();
        $grupos = $catalogo->listar($hotelId, 'grupos', true);

        $sugerencias = [];
        $periodos = [];
        try {
            $calculo = new NominaCalculoService();
            foreach ($grupos as $g) {
                $sugerencias[(int) $g['id']] = $calculo->sugerirRango($hotelId, $g);
            }

            $db = Database::getInstance();
            $st = $db->query(
                "SELECT p.id, p.etiqueta, p.tipo_periodo, p.motor, p.estado, p.fecha_inicio, p.fecha_fin,
                        p.trabajadores_total, p.bruto_total, p.neto_sugerido_total, g.nombre AS grupo_nombre
                 FROM trabajador_nomina_periodos p
                 LEFT JOIN nomina_grupos g ON g.id = p.grupo_nomina_id
                 WHERE p.hotel_id = ?
                 ORDER BY p.fecha_fin DESC, p.id DESC
                 LIMIT 60",
                [$hotelId]
            );
            $periodos = $st !== false ? $st->fetchAll() : [];
        } catch (Throwable $e) {
            // Vista funciona con listas vacias.
        }

        View::renderTemplate('nomina/periodos', [
            'title' => 'Periodos de nomina - ' . current_hotel_display_name(),
            'grupos' => $grupos,
            'sugerencias' => $sugerencias,
            'periodos' => $periodos,
            'puedeCalcular' => can('nomina.calcular'),
        ]);
    }

    public function periodoPreviewAction() {
        if (function_exists('require_permission')) {
            require_permission('nomina.calcular');
        }

        $hotelId = $this->hotelIdActual();
        $grupoId = (int) $this->getQuery('grupo_id', 0);
        $inicio = (string) $this->getQuery('inicio', '');
        $fin = (string) $this->getQuery('fin', '');

        try {
            $preview = (new NominaCalculoService())->preview($hotelId, $grupoId, $inicio, $fin);
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
            $this->redirect('nomina/periodos');
            return;
        }

        View::renderTemplate('nomina/periodo_preview', [
            'title' => 'Previsualizacion de nomina - ' . current_hotel_display_name(),
            'preview' => $preview,
            'puedeCerrar' => can('nomina.cerrar'),
        ]);
    }

    public function periodoCerrarAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.cerrar');
        }

        $hotelId = $this->hotelIdActual();

        try {
            $periodoId = (new NominaCierreService())->cerrar(
                $hotelId,
                (int) $this->getPost('grupo_id'),
                (string) $this->getPost('inicio'),
                (string) $this->getPost('fin'),
                user_id()
            );
            $requiereAprobacion = ConfiguracionHotelRegistry::getBool('nomina.requiere_aprobacion_cierre', true, $hotelId);
            set_mensaje(
                $requiereAprobacion
                    ? 'Periodo cerrado. Revisa el snapshot y apruebalo para habilitar pagos.'
                    : 'Periodo cerrado y aprobado en un solo paso (configuracion del negocio): los pagos por Caja quedan habilitados.',
                'success'
            );
            $this->redirect('nomina/periodos/' . $periodoId);
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
            $this->redirect('nomina/periodos');
        }
    }

    public function periodoVerAction($id) {
        $hotelId = $this->hotelIdActual();
        $periodoId = (int) $id;

        $db = Database::getInstance();
        $st = $db->query(
            "SELECT p.*, g.nombre AS grupo_nombre
             FROM trabajador_nomina_periodos p
             LEFT JOIN nomina_grupos g ON g.id = p.grupo_nomina_id
             WHERE p.id = ? AND p.hotel_id = ?",
            [$periodoId, $hotelId]
        );
        $periodo = $st !== false ? $st->fetch() : null;

        if (!$periodo) {
            set_mensaje('El periodo no existe en este negocio.', 'error');
            $this->redirect('nomina/periodos');
        }

        $detalles = [];
        $lineasPorDetalle = [];
        $st = $db->query(
            "SELECT * FROM trabajador_nomina_periodo_detalles WHERE periodo_id = ? AND hotel_id = ? ORDER BY trabajador_nombre ASC",
            [$periodoId, $hotelId]
        );
        $detalles = $st !== false ? $st->fetchAll() : [];
        $conciliado = (new Trabajador())->conciliarNominaPeriodoSnapshotConPagosCaja($periodo, $detalles, $hotelId);
        $periodo = $conciliado['periodo'];
        $detalles = $conciliado['detalles'];

        $st = $db->query(
            "SELECT * FROM nomina_periodo_conceptos WHERE periodo_id = ? AND hotel_id = ? ORDER BY detalle_id ASC, tipo ASC, id ASC",
            [$periodoId, $hotelId]
        );
        foreach (($st !== false ? $st->fetchAll() : []) as $linea) {
            $lineasPorDetalle[(int) $linea['detalle_id']][] = $linea;
        }

        $recibosPorDetalle = [];
        try {
            require_once __DIR__ . '/../services/NominaReciboService.php';
            foreach ((new NominaReciboService())->listarPorPeriodo($hotelId, $periodoId) as $recibo) {
                if ($recibo['estado'] === 'emitido') {
                    $recibosPorDetalle[(int) $recibo['detalle_id']] = $recibo;
                }
            }
        } catch (Throwable $e) {
            $recibosPorDetalle = [];
        }

        // Pago por Caja migrado al modulo Nomina: preparamos la evaluacion y los
        // tokens por detalle igual que la pantalla heredada de Personal. El form
        // de la vista postea al MISMO endpoint que ejecuta el pago
        // (TrabajadorController::registrarPagoSnapshotNomina): no se duplica ni
        // se reescribe nada de la logica de dinero.
        $pagosSnapshot = [];
        $pagoSnapshotTokens = [];
        $puedePagar = function_exists('can') ? can('personal.pagar') : false;
        if ((string) ($periodo['estado'] ?? '') === 'aprobado' && $puedePagar) {
            require_once __DIR__ . '/../services/TrabajadorNominaSnapshotPagoService.php';
            $snapshotPagoService = new TrabajadorNominaSnapshotPagoService();
            foreach ($detalles as $detallePago) {
                $detallePagoId = (int) ($detallePago['id'] ?? 0);
                if ($detallePagoId <= 0) {
                    continue;
                }
                try {
                    $evaluacion = $snapshotPagoService->evaluarPagoDesdeSnapshot($hotelId, $periodoId, $detallePagoId);
                } catch (Throwable $e) {
                    $evaluacion = [
                        'elegible' => false,
                        'motivo_bloqueo' => $e->getMessage(),
                        'monto_maximo' => '0.00',
                        'snapshot_pendiente' => number_format((float) ($detallePago['pendiente_pago_sugerido'] ?? 0), 2, '.', ''),
                        'saldo_vivo' => '0.00',
                        'metodos_pago' => ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'],
                    ];
                }
                $pagosSnapshot[$detallePagoId] = $evaluacion;
                if (!empty($evaluacion['elegible'])) {
                    $pagoSnapshotTokens[$detallePagoId] = $this->generarPagoSnapshotCajaToken($periodoId, $detallePagoId);
                }
            }
        }

        View::renderTemplate('nomina/periodo_ver', [
            'title' => 'Periodo de nomina - ' . current_hotel_display_name(),
            'periodo' => $periodo,
            'detalles' => $detalles,
            'lineasPorDetalle' => $lineasPorDetalle,
            'recibosPorDetalle' => $recibosPorDetalle,
            'puedeAprobar' => can('nomina.aprobar'),
            'puedeAnular' => can('nomina.reabrir'),
            'puedeVerRecibos' => can('nomina.salarios'),
            'permitirReapertura' => ConfiguracionHotelRegistry::getBool('nomina.permitir_reapertura', false, $hotelId),
            'pagosSnapshot' => $pagosSnapshot,
            'pagoSnapshotTokens' => $pagoSnapshotTokens,
            'puedePagar' => $puedePagar,
        ]);
    }

    /**
     * Token de un solo uso para pagar un detalle desde su snapshot. Escribe en la
     * MISMA estructura de sesion que consume TrabajadorController al ejecutar el
     * pago (accion 'pago_snapshot_caja', key "periodo:detalle"), para que el
     * endpoint heredado lo valide sin cambios. Mantener en sincronia con
     * TrabajadorController::generarNominaPeriodoToken / consumirNominaPeriodoToken.
     */
    private function generarPagoSnapshotCajaToken(int $periodoId, int $detalleId): string {
        if (!isset($_SESSION['trabajador_nomina_periodo_tokens']) || !is_array($_SESSION['trabajador_nomina_periodo_tokens'])) {
            $_SESSION['trabajador_nomina_periodo_tokens'] = [];
        }
        if (!isset($_SESSION['trabajador_nomina_periodo_tokens']['pago_snapshot_caja']) || !is_array($_SESSION['trabajador_nomina_periodo_tokens']['pago_snapshot_caja'])) {
            $_SESSION['trabajador_nomina_periodo_tokens']['pago_snapshot_caja'] = [];
        }
        $token = bin2hex(random_bytes(16));
        $_SESSION['trabajador_nomina_periodo_tokens']['pago_snapshot_caja'][$periodoId . ':' . $detalleId] = [
            'token' => $token,
            'created_at' => time(),
        ];
        return $token;
    }

    public function periodoAprobarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.aprobar');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCierreService())->aprobar($hotelId, (int) $id, user_id());
            set_mensaje('Periodo aprobado: los pagos por Caja quedan habilitados.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/periodos/' . (int) $id);
    }

    public function periodoRecibosEmitirAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.aprobar');
        }

        $hotelId = $this->hotelIdActual();

        try {
            require_once __DIR__ . '/../services/NominaReciboService.php';
            $emitidos = (new NominaReciboService())->emitirPorPeriodo($hotelId, (int) $id, user_id());
            set_mensaje($emitidos > 0 ? $emitidos . ' recibo(s) emitido(s).' : 'Todos los detalles ya tienen recibo vigente.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/periodos/' . (int) $id);
    }

    public function reciboPdfAction($id) {
        if (function_exists('require_permission')) {
            require_permission('nomina.salarios');
        }

        $hotelId = $this->hotelIdActual();

        try {
            require_once __DIR__ . '/../services/NominaReciboService.php';
            (new NominaReciboService())->descargarPdf($hotelId, (int) $id);
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
            $this->redirect('nomina/periodos');
        }
    }

    public function reciboCancelarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.reabrir');
        }

        $hotelId = $this->hotelIdActual();
        $volverA = (int) $this->getPost('periodo_id', 0);

        try {
            require_once __DIR__ . '/../services/NominaReciboService.php';
            (new NominaReciboService())->cancelar($hotelId, (int) $id, (string) $this->getPost('motivo', ''), user_id());
            set_mensaje('Recibo cancelado.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect($volverA > 0 ? 'nomina/periodos/' . $volverA : 'nomina/periodos');
    }

    public function periodoReabrirAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.reabrir');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCierreService())->reabrir($hotelId, (int) $id, (string) $this->getPost('motivo', ''), user_id());
            set_mensaje('Periodo reabierto (cerrado sin aprobacion). Los recibos emitidos fueron cancelados.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/periodos/' . (int) $id);
    }

    /* ------------------------------------------------------------------ */
    /* Fase 7: exportacion para contador (modo hibrido y general)          */
    /* ------------------------------------------------------------------ */

    public function periodoExportarAction($id) {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('exportaciones');
        }
        if (function_exists('require_permission')) {
            require_permission('nomina.exportar');
        }

        $hotelId = $this->hotelIdActual();
        $periodoId = (int) $id;

        $db = Database::getInstance();
        $st = $db->query(
            "SELECT * FROM trabajador_nomina_periodos WHERE id = ? AND hotel_id = ?",
            [$periodoId, $hotelId]
        );
        $periodo = $st !== false ? $st->fetch() : null;
        if (!$periodo) {
            set_mensaje('El periodo no existe en este negocio.', 'error');
            $this->redirect('nomina/periodos');
        }

        $st = $db->query(
            "SELECT d.trabajador_nombre, d.trabajador_identificacion, d.trabajador_rol,
                    l.concepto_nombre, l.tipo, l.clasificacion, l.origen, l.cantidad, l.base, l.monto, l.referencia
             FROM trabajador_nomina_periodo_detalles d
             LEFT JOIN nomina_periodo_conceptos l ON l.detalle_id = d.id
             WHERE d.periodo_id = ? AND d.hotel_id = ?
             ORDER BY d.trabajador_nombre ASC, l.tipo ASC, l.id ASC",
            [$periodoId, $hotelId]
        );
        $filas = $st !== false ? $st->fetchAll() : [];

        session_write_close();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombre = 'nomina-periodo-' . $periodoId . '-' . $periodo['fecha_inicio'] . '-' . $periodo['fecha_fin'] . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('X-Content-Type-Options: nosniff');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, ['Periodo', $this->csvValor($periodo['etiqueta']), $periodo['fecha_inicio'], $periodo['fecha_fin'], 'estado', $periodo['estado'], 'motor', $periodo['motor'] ?? 'v1']);
        fputcsv($salida, ['Empleado', 'Identificacion', 'Puesto', 'Concepto', 'Tipo', 'Clasificacion', 'Origen', 'Cantidad', 'Base', 'Monto', 'Referencia']);
        foreach ($filas as $f) {
            fputcsv($salida, [
                $this->csvValor($f['trabajador_nombre']),
                $this->csvValor($f['trabajador_identificacion']),
                $this->csvValor($f['trabajador_rol']),
                $this->csvValor($f['concepto_nombre']),
                $f['tipo'],
                $f['clasificacion'],
                $f['origen'],
                $f['cantidad'] !== null ? number_format((float) $f['cantidad'], 2, '.', '') : '',
                $f['base'] !== null ? number_format((float) $f['base'], 2, '.', '') : '',
                $f['monto'] !== null ? number_format((float) $f['monto'], 2, '.', '') : '',
                $this->csvValor($f['referencia']),
            ]);
        }
        fclose($salida);
        exit;
    }

    /**
     * Neutraliza celdas que Excel/Sheets interpretarian como formula
     * (CSV injection): nombres, conceptos y referencias son texto capturado
     * por usuarios del hotel.
     */
    private function csvValor($value): string {
        $texto = trim(str_replace(["\r\n", "\r", "\n"], ' ', (string) ($value ?? '')));
        if ($texto !== '' && strpos("=+-@\t", $texto[0]) !== false && !is_numeric($texto)) {
            $texto = "'" . $texto;
        }
        return $texto;
    }

    /* ------------------------------------------------------------------ */
    /* Fases 8/9: adaptador por giro (propone incidencias)                 */
    /* ------------------------------------------------------------------ */

    public function incidenciasProponerAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/incidencias');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.incidencias');
        }

        $hotelId = $this->hotelIdActual();

        try {
            require_once __DIR__ . '/../services/NominaAdaptadorRegistry.php';
            $giro = NominaAdaptadorRegistry::giroDelNegocio($hotelId);
            $adaptador = NominaAdaptadorRegistry::paraGiro($giro);

            if ($adaptador === null) {
                set_mensaje('El giro "' . $giro . '" aun no tiene adaptador de nomina; captura las incidencias manualmente.', 'warning');
            } else {
                $desde = (string) $this->getPost('desde', date('Y-m-01'));
                $hasta = (string) $this->getPost('hasta', date('Y-m-d'));
                $resultado = $adaptador->proponerIncidencias($hotelId, $desde, $hasta, user_id());

                $mensaje = $resultado['propuestas'] . ' propuesta(s) del adaptador ' . $giro . ' (quedan pendientes de aprobar).';
                if (!empty($resultado['avisos'])) {
                    $mensaje .= ' ' . implode(' ', $resultado['avisos']);
                }
                set_mensaje($mensaje, $resultado['propuestas'] > 0 ? 'success' : 'info');
            }
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/incidencias?estado=pendiente');
    }

    /* ------------------------------------------------------------------ */
    /* Fase 10: API interna JSON (reutilizable por integraciones futuras)  */
    /* ------------------------------------------------------------------ */

    public function apiPeriodosAction() {
        $hotelId = $this->hotelIdActual();

        $db = Database::getInstance();
        $st = $db->query(
            "SELECT p.id, p.etiqueta, p.tipo_periodo, p.motor, p.estado, p.fecha_inicio, p.fecha_fin,
                    p.trabajadores_total, p.bruto_total, p.neto_sugerido_total, g.nombre AS grupo
             FROM trabajador_nomina_periodos p
             LEFT JOIN nomina_grupos g ON g.id = p.grupo_nomina_id
             WHERE p.hotel_id = ?
             ORDER BY p.fecha_fin DESC, p.id DESC
             LIMIT 100",
            [$hotelId]
        );

        View::renderJSON(['success' => true, 'periodos' => $st !== false ? $st->fetchAll() : []]);
    }

    public function apiPeriodoVerAction($id) {
        $hotelId = $this->hotelIdActual();
        $periodoId = (int) $id;

        $db = Database::getInstance();
        $st = $db->query(
            "SELECT * FROM trabajador_nomina_periodos WHERE id = ? AND hotel_id = ?",
            [$periodoId, $hotelId]
        );
        $periodo = $st !== false ? $st->fetch() : null;

        if (!$periodo) {
            View::renderJSON(['success' => false, 'error' => 'periodo_no_encontrado'], 404);
        }

        $st = $db->query(
            "SELECT id, trabajador_id, trabajador_nombre, conceptos_a_favor, conceptos_en_contra,
                    deducciones_informativas, neto_sugerido, pendiente_pago_sugerido
             FROM trabajador_nomina_periodo_detalles WHERE periodo_id = ? AND hotel_id = ?",
            [$periodoId, $hotelId]
        );
        $detalles = $st !== false ? $st->fetchAll() : [];

        $st = $db->query(
            "SELECT detalle_id, concepto_nombre, tipo, clasificacion, origen, cantidad, base, monto, referencia
             FROM nomina_periodo_conceptos WHERE periodo_id = ? AND hotel_id = ?",
            [$periodoId, $hotelId]
        );
        $lineas = $st !== false ? $st->fetchAll() : [];

        View::renderJSON(['success' => true, 'periodo' => $periodo, 'detalles' => $detalles, 'lineas' => $lineas]);
    }

    public function apiIncidenciaCrearAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'error' => 'metodo_no_permitido'], 405);
        }
        $this->validateCSRF();
        if (!can('nomina.incidencias')) {
            View::renderJSON(['success' => false, 'error' => 'permiso_denegado'], 403);
        }

        $hotelId = $this->hotelIdActual();

        try {
            $incidenciaId = (new NominaIncidenciaService())->registrar($hotelId, [
                'trabajador_id' => $this->getPost('trabajador_id'),
                'concepto_id' => $this->getPost('concepto_id'),
                'fecha' => $this->getPost('fecha'),
                'cantidad' => $this->getPost('cantidad'),
                'monto' => $this->getPost('monto'),
                'descripcion' => $this->getPost('descripcion'),
            ], user_id());
            View::renderJSON(['success' => true, 'incidencia_id' => $incidenciaId]);
        } catch (Throwable $e) {
            View::renderJSON(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function periodoAnularAction($id) {
        if (!$this->isPost()) {
            $this->redirect('nomina/periodos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.reabrir');
        }

        $hotelId = $this->hotelIdActual();

        try {
            (new NominaCierreService())->anular($hotelId, (int) $id, (string) $this->getPost('motivo', ''), user_id());
            set_mensaje('Periodo anulado. Sus montos pendientes ya no cuentan para pagos.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('nomina/periodos/' . (int) $id);
    }

    /* ------------------------------------------------------------------ */

    private function requiereEscrituraCatalogo($tipo) {
        if (!$this->isPost()) {
            $this->redirect('nomina/catalogos');
        }
        $this->validateCSRF();
        if (function_exists('require_permission')) {
            require_permission('nomina.configurar');
        }
        if (!(new NominaCatalogoService())->esTipoValido((string) $tipo)) {
            set_mensaje('Tipo de catalogo no valido.', 'error');
            $this->redirect('nomina/catalogos');
        }
    }

    private function hotelIdActual() {
        return (int) obtenerHotelIdActualCompat();
    }

    /**
     * Lee la configuracion vigente de nomina del tenant (con defaults del
     * registry). Siempre con hotelId explicito: nunca confiar en el request.
     */
    private function configuracionActual($hotelId) {
        return [
            'giro' => (string) ConfiguracionHotelRegistry::get('negocio.giro', 'hotel', $hotelId),
            'modo' => (string) ConfiguracionHotelRegistry::get('nomina.modo', 'simplificada', $hotelId),
            'pais' => (string) ConfiguracionHotelRegistry::get('nomina.pais', 'MX', $hotelId),
            'redondeo' => (string) ConfiguracionHotelRegistry::get('nomina.redondeo', 'centavos', $hotelId),
            'permitir_horas_extra' => ConfiguracionHotelRegistry::getBool('nomina.permitir_horas_extra', true, $hotelId),
            'permitir_descuentos_manuales' => ConfiguracionHotelRegistry::getBool('nomina.permitir_descuentos_manuales', true, $hotelId),
            'requiere_aprobacion_cierre' => ConfiguracionHotelRegistry::getBool('nomina.requiere_aprobacion_cierre', true, $hotelId),
            'permitir_reapertura' => ConfiguracionHotelRegistry::getBool('nomina.permitir_reapertura', false, $hotelId),
        ];
    }

    /**
     * Metricas informativas del dashboard leidas del subsistema laboral
     * existente (bloque personal). Defensivo: si las tablas no estan o la
     * consulta falla, el dashboard muestra estado vacio sin romper.
     */
    private function estadisticasBase($hotelId, $personalActivo) {
        $stats = [
            'trabajadores_activos' => null,
            'periodos_registrados' => null,
            'ultimo_periodo' => null,
        ];

        if (!$personalActivo) {
            return $stats;
        }

        try {
            $db = Database::getInstance();

            $st = $db->query(
                "SELECT COUNT(*) AS total FROM trabajadores WHERE hotel_id = ? AND estado = 'activo'",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['trabajadores_activos'] = (int) ($fila['total'] ?? 0);
            }

            $st = $db->query(
                "SELECT COUNT(*) AS total FROM trabajador_nomina_periodos WHERE hotel_id = ?",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['periodos_registrados'] = (int) ($fila['total'] ?? 0);
            }

            $st = $db->query(
                "SELECT etiqueta, tipo_periodo, fecha_inicio, fecha_fin, estado
                 FROM trabajador_nomina_periodos
                 WHERE hotel_id = ?
                 ORDER BY fecha_fin DESC, id DESC
                 LIMIT 1",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['ultimo_periodo'] = $fila ?: null;
            }
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('warning', 'Nomina: no se pudieron leer estadisticas base', ['error' => $e->getMessage()]);
            }
        }

        return $stats;
    }
}
