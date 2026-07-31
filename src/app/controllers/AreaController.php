<?php
require_once __DIR__ . '/../models/Area.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

/**
 * Controlador de Areas del hotel (bloque habitaciones y areas).
 *
 * Zonas sin numero de habitacion (alberca, lobby, restaurante...) que se
 * limpian, se mantienen y se cierran igual que un cuarto. Vive dentro del
 * modulo 'habitaciones' a proposito: mismos gates, misma seccion del sidebar.
 */
class AreaController extends Controller {
    private $areaModel;
    private $tareaModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->areaModel = new Area();
        $this->tareaModel = new TareaOperativa();
    }

    protected function before() {
        $this->requireAuth();
        require_hotel_module('habitaciones');

        // Las areas viven DENTRO del modulo habitaciones (mismos gates, sin
        // claves areas.* por el gotcha can_legacy): permiso base del menu
        // (config/navegacion.php -> 'habitaciones.view'). Las escrituras ya
        // exigen 'habitaciones.edit' accion por accion.
        require_permission_or_403('habitaciones.view');

        return true;
    }

    private function hotelIdActual() {
        return (int)obtenerHotelIdActualCompat();
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('areas/index', [
            'title' => 'Áreas del hotel - ' . current_hotel_display_name(),
            'areas' => $this->areaModel->listar($hotelId),
            'conteo_estados' => $this->areaModel->contarPorEstado($hotelId),
            'tipos' => Area::catalogoTipos(),
            'puede_gestionar' => function_exists('can') ? can('habitaciones.edit') : true,
        ]);
    }

    /**
     * POST /areas/guardar (alta o edicion segun id, patron de activos).
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');

        $hotelId = $this->hotelIdActual();
        $id = (int)$this->getPost('id', 0);

        $nombre = trim((string)$this->getPost('nombre', ''));
        $tipo = trim((string)$this->getPost('tipo', 'otra'));
        $piso = trim((string)$this->getPost('piso', ''));
        $descripcion = trim((string)$this->getPost('descripcion', ''));

        if ($nombre === '' || mb_strlen($nombre) > 120) {
            set_mensaje('El nombre del área es obligatorio (máximo 120 caracteres)', 'error');
            $this->redirect('areas');
            return;
        }

        if (!Area::tipoValido($tipo)) {
            set_mensaje('El tipo de área elegido no es válido', 'error');
            $this->redirect('areas');
            return;
        }

        // Nombre unico por hotel (UNIQUE en BD; validamos antes para dar
        // mensaje digno en lugar de SQLSTATE).
        $duplicada = $this->areaModel->obtenerPorNombre($nombre, $hotelId);
        if ($duplicada && (int)$duplicada['id'] !== $id) {
            set_mensaje('Ya existe un área con ese nombre en este hotel', 'error');
            $this->redirect('areas');
            return;
        }

        $datos = [
            'nombre' => $nombre,
            'tipo' => $tipo,
            'piso' => ($piso === '' ? null : (int)$piso),
            'descripcion' => ($descripcion === '' ? null : mb_substr($descripcion, 0, 500)),
        ];

        if ($id > 0) {
            $area = $this->areaModel->obtenerPorId($id, $hotelId);
            if (!$area) {
                set_mensaje('Área no encontrada', 'error');
                $this->redirect('areas');
                return;
            }
            $resultado = $this->areaModel->guardar($datos, $id);
            set_mensaje($resultado ? 'Área actualizada' : 'No se pudo actualizar el área', $resultado ? 'success' : 'error');
        } else {
            $datos['estado'] = 'disponible';
            $datos['activa'] = 1;
            $resultado = $this->areaModel->guardar($datos);
            set_mensaje($resultado ? 'Área creada' : 'No se pudo crear el área', $resultado ? 'success' : 'error');
        }

        $this->redirect('areas');
    }

    /**
     * GET /mapa — mapa digital del hotel: habitaciones + areas por piso con
     * su estado en vivo. Solo lectura; cada ficha enlaza a su detalle.
     */
    public function mapaAction() {
        $hotelId = $this->hotelIdActual();

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, numero, tipo, piso, estado
             FROM habitaciones
             WHERE hotel_id = ? AND COALESCE(activa, 1) = 1
             ORDER BY piso ASC, CAST(numero AS UNSIGNED), numero",
            [$hotelId]
        );
        $habitaciones = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $areas = $this->areaModel->listar($hotelId, true);

        $conteo = ['disponible' => 0, 'ocupada' => 0, 'limpieza' => 0, 'mantenimiento' => 0, 'cerrada' => 0];
        foreach ($habitaciones as $h) {
            $e = (string)($h['estado'] ?? '');
            if (isset($conteo[$e])) {
                $conteo[$e]++;
            }
        }
        foreach ($areas as $a) {
            $e = (string)($a['estado'] ?? '');
            if (isset($conteo[$e])) {
                $conteo[$e]++;
            }
        }

        View::renderTemplate('areas/mapa', [
            'title' => 'Mapa del hotel - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'areas' => $areas,
            'conteo' => $conteo,
            'tipos_area' => Area::catalogoTipos(),
        ]);
    }

    /**
     * GET /areas/{id} — detalle del area: acciones operativas + historial.
     */
    public function verAction() {
        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $area = $this->areaModel->obtenerPorId($id, $hotelId);

        if (!$area) {
            set_mensaje('Área no encontrada', 'error');
            $this->redirect('areas');
            return;
        }

        $trabajadores = [];
        try {
            if ($this->tareaModel->tablaDisponible()) {
                $trabajadores = $this->tareaModel->trabajadoresActivosOpciones($hotelId);
            }
        } catch (Throwable $e) {
            error_log('Areas: no se pudo cargar personal de limpieza: ' . $e->getMessage());
        }

        // Activos del area (boiler de la alberca, minisplit del lobby) con su
        // preventivo. Alimenta el bloque "Activos" y el selector opcional del
        // mantenimiento. Tolerante: sin Mantenimiento Plus la lista va vacia.
        $activos = [];
        try {
            require_once __DIR__ . '/../models/ActivoHotel.php';
            $activos = (new ActivoHotel())->listarPorUnidad($hotelId, 'area', $id);
        } catch (Throwable $e) {
            error_log('Areas: no se pudieron cargar los activos del area: ' . $e->getMessage());
        }

        View::renderTemplate('areas/ver', [
            'title' => (string)$area['nombre'] . ' - ' . current_hotel_display_name(),
            'area' => $area,
            'tipos' => Area::catalogoTipos(),
            'historial' => $this->areaModel->historial($id, $hotelId),
            'activos' => $activos,
            'personal_limpieza' => $trabajadores,
            'tipos_mantenimiento' => class_exists('Mantenimiento') ? Mantenimiento::getTipos() : [],
            'prioridades_mantenimiento' => class_exists('Mantenimiento') ? Mantenimiento::getPrioridades() : [],
            'puede_mantenimiento' => function_exists('can') ? can('habitaciones.mantenimiento') : true,
            'puede_gestionar' => function_exists('can') ? can('habitaciones.edit') : true,
        ]);
    }

    /**
     * POST /areas/{id}/limpieza — accion 'iniciar' (area -> limpieza + tarea)
     * o 'completar' (contrato de personal de habitaciones: personal_confirmado
     * / trabajador_ids[] / sin_personal). Sin gate extra: paridad con el
     * cambiar-estado de habitaciones (auth + modulo).
     */
    public function limpiezaAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $accion = trim((string)$this->getPost('accion'));
        $area = $this->areaModel->obtenerPorId($id, $hotelId);

        if (!$area) {
            set_mensaje('Área no encontrada', 'error');
            $this->redirect('areas');
            return;
        }

        try {
            if ($accion === 'iniciar') {
                $this->tareaModel->iniciarLimpiezaAreaParaHotel($hotelId, $id, user_id());
                set_mensaje('Área enviada a limpieza', 'success');
            } elseif ($accion === 'completar') {
                if ((string)($area['estado'] ?? '') !== 'limpieza') {
                    set_mensaje('El área no está en limpieza', 'error');
                    $this->redirect('areas/' . $id);
                    return;
                }

                $personal = $this->personalLimpiezaPost();
                $errorPersonal = $this->validarPersonalLimpiezaPost($personal);
                if ($errorPersonal !== null) {
                    set_mensaje($errorPersonal, 'error');
                    $this->redirect('areas/' . $id);
                    return;
                }

                $completada = false;
                try {
                    $completada = $this->tareaModel->completarLimpiezaConPersonalAreaParaHotel($hotelId, $id, $personal['ids'], user_id());
                } catch (Throwable $e) {
                    error_log('Areas: completar limpieza area #' . $id . ': ' . $e->getMessage());
                }
                if (!$completada) {
                    // Fail-open como habitaciones: sin base de tareas, al menos
                    // se libera el area.
                    $this->areaModel->cambiarEstado($id, 'disponible', $hotelId);
                }
                set_mensaje('Área marcada como limpia', 'success');
            } else {
                set_mensaje('Acción de limpieza no válida', 'error');
            }
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'completar la operación');
        }

        $this->redirect('areas/' . $id);
    }

    /**
     * POST /areas/{id}/mantenimiento — accion 'iniciar' | 'finalizar' via
     * MantenimientoService (mismo motor que habitaciones, con area_id).
     */
    public function mantenimientoAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $accion = trim((string)$this->getPost('accion'));

        if (!in_array($accion, ['iniciar', 'finalizar'], true)) {
            set_mensaje('Acción de mantenimiento no válida', 'error');
            $this->redirect('areas/' . $id);
            return;
        }

        require_once __DIR__ . '/../services/MantenimientoService.php';
        $servicio = new MantenimientoService();

        try {
            if ($accion === 'iniciar') {
                $servicio->iniciarParaAreaHotel(
                    $hotelId,
                    $id,
                    trim((string)$this->getPost('tipo_mantenimiento')),
                    trim((string)$this->getPost('prioridad', 'media')),
                    trim((string)$this->getPost('motivo')),
                    user_id(),
                    // Opcional: a que activo del area se le esta dando servicio.
                    ((int)$this->getPost('activo_id', 0)) ?: null
                );
                set_mensaje('Mantenimiento del área iniciado correctamente', 'success');
            } else {
                $servicio->finalizarParaAreaHotel($hotelId, $id, user_id());
                set_mensaje('Mantenimiento del área finalizado correctamente', 'success');
            }
        } catch (Exception $e) {
            set_mensaje_error_op($e, 'procesar mantenimiento');
        }

        $this->redirect('areas/' . $id);
    }

    /**
     * POST /areas/{id}/cerrar — cierra un area disponible o reabre una
     * cerrada. En limpieza/mantenimiento no se puede cerrar.
     */
    public function cerrarAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $area = $this->areaModel->obtenerPorId($id, $hotelId);

        if (!$area) {
            set_mensaje('Área no encontrada', 'error');
            $this->redirect('areas');
            return;
        }

        $estado = (string)($area['estado'] ?? '');
        if ($estado === 'disponible') {
            $this->areaModel->cambiarEstado($id, 'cerrada', $hotelId);
            set_mensaje('Área cerrada al público', 'success');
        } elseif ($estado === 'cerrada') {
            $this->areaModel->cambiarEstado($id, 'disponible', $hotelId);
            set_mensaje('Área reabierta', 'success');
        } else {
            set_mensaje('No se puede cerrar un área en limpieza o mantenimiento', 'error');
        }

        $this->redirect('areas/' . $id);
    }

    /**
     * Contrato de personal de limpieza (mismo del selector de habitaciones):
     * personal_confirmado=1 activa la validacion, trabajador_ids[] o
     * sin_personal=1 como eleccion explicita.
     */
    private function personalLimpiezaPost(): array {
        $ids = [];
        $raw = $_POST['trabajador_ids'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $valor) {
                $valor = (int)$valor;
                if ($valor > 0) {
                    $ids[$valor] = $valor;
                }
            }
        }

        return [
            'confirmado' => (string)($_POST['personal_confirmado'] ?? '') === '1',
            'sin_personal' => (string)($_POST['sin_personal'] ?? '') === '1',
            'ids' => array_values($ids),
        ];
    }

    private function validarPersonalLimpiezaPost(array $personalPost): ?string {
        if (!$personalPost['confirmado']) {
            return null;
        }

        if (!$personalPost['sin_personal'] && empty($personalPost['ids'])) {
            return 'Indica quién hizo la limpieza o marca "Sin registrar personal".';
        }

        if (!empty($personalPost['ids']) && $this->tareaModel && $this->tareaModel->tablaDisponible()) {
            $activos = [];
            foreach ($this->tareaModel->trabajadoresActivosOpciones($this->hotelIdActual()) as $t) {
                $activos[(int)$t['id']] = true;
            }
            foreach ($personalPost['ids'] as $tid) {
                if (!isset($activos[$tid])) {
                    return 'Uno de los trabajadores seleccionados ya no está activo en el hotel.';
                }
            }
        }

        return null;
    }

    /**
     * POST /areas/{id}/toggle — pausa/reactiva el area (baja logica, el
     * UNIQUE de nombre se conserva para poder reactivar sin duplicar).
     */
    public function toggleAction() {
        if (!$this->isPost()) {
            $this->redirect('areas');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $area = $this->areaModel->obtenerPorId($id, $hotelId);

        if (!$area) {
            set_mensaje('Área no encontrada', 'error');
            $this->redirect('areas');
            return;
        }

        $nuevaActiva = ((int)($area['activa'] ?? 1) === 1) ? 0 : 1;
        $this->areaModel->guardar(['activa' => $nuevaActiva], $id);
        set_mensaje($nuevaActiva ? 'Área reactivada' : 'Área pausada: ya no aparecerá en el mapa', 'success');
        $this->redirect('areas');
    }
}
