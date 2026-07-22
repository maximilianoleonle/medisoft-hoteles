<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/MantenimientoFoto.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../models/ActivoHotel.php';
require_once __DIR__ . '/../services/NotificacionService.php';
require_once __DIR__ . '/../services/MantenimientoPreventivoService.php';

/**
 * Detalle de mantenimiento (bloque mantenimiento_plus).
 *
 * El flujo correctivo basico (iniciar/finalizar desde habitaciones) NO vive
 * aqui y sigue libre; este controlador agrega la capa premium: evidencia
 * fotografica antes/despues, costo real hacia gastos y activos/preventivo.
 */
class MantenimientoController extends Controller
{
    private $mantenimientoModel;
    private $fotoModel;
    private $tareaModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->mantenimientoModel = new Mantenimiento();
        $this->fotoModel = new MantenimientoFoto();
        $this->tareaModel = new TareaOperativa();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        require_hotel_module('mantenimiento_plus');

        if (function_exists('require_permission')) {
            require_permission('habitaciones.view');
        }

        return true;
    }

    /**
     * GET /mantenimientos/{id}
     */
    public function verAction()
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();

        $mantenimiento = $this->obtenerDetalle($id, $hotelId);
        if (!$mantenimiento) {
            set_mensaje('Mantenimiento no encontrado', 'error');
            $this->redirect('reportes/mantenimiento');
            return;
        }

        $fotos = $this->fotoModel->porMantenimiento($id, $hotelId);
        $tareaActiva = $this->tareaModel->buscarTareaActivaPorMantenimientoHotel($hotelId, $id);

        View::renderTemplate('mantenimientos/ver', [
            'title' => 'Mantenimiento #' . $id . ' - ' . current_hotel_display_name(),
            'mantenimiento' => $mantenimiento,
            'fotos' => $fotos,
            'tarea_activa' => $tareaActiva,
            'max_fotos' => MantenimientoFoto::MAX_FOTOS_POR_MOMENTO,
            'puede_gestionar' => function_exists('can') ? can('habitaciones.mantenimiento') : true,
            'puede_caja' => function_exists('can') ? can('caja.movimientos') : true,
            'metodos_pago' => MovimientoCaja::getMetodosPago(),
        ]);
    }

    /**
     * POST /mantenimientos/{id}/fotos
     * Sube evidencia (momento reporte|resuelto) desde el celular.
     */
    public function subirFotosAction()
    {
        if (!$this->isPost()) {
            $this->redirect('reportes/mantenimiento');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();
        $momento = trim((string)$this->getPost('momento', 'reporte'));

        $mantenimiento = $this->obtenerDetalle($id, $hotelId);
        if (!$mantenimiento) {
            set_mensaje('Mantenimiento no encontrado', 'error');
            $this->redirect('reportes/mantenimiento');
            return;
        }

        if (!in_array($momento, MantenimientoFoto::MOMENTOS, true)) {
            set_mensaje('Momento de evidencia no valido', 'error');
            $this->redirect('mantenimientos/' . $id);
            return;
        }

        if (empty($_FILES['fotos']['name'][0] ?? '')) {
            set_mensaje('Selecciona al menos una foto', 'error');
            $this->redirect('mantenimientos/' . $id);
            return;
        }

        $resultado = $this->fotoModel->guardarLoteDesdeUpload(
            $hotelId,
            $id,
            $momento,
            $_FILES['fotos'],
            function_exists('user_id') ? user_id() : null
        );

        $subidas = count($resultado['fotos']);
        if ($subidas > 0) {
            $mensaje = $subidas === 1 ? 'Foto de evidencia guardada' : $subidas . ' fotos de evidencia guardadas';
            if (!empty($resultado['errores'])) {
                $mensaje .= '. Avisos: ' . implode('; ', $resultado['errores']);
            }
            set_mensaje($mensaje, 'success');
        } else {
            set_mensaje(!empty($resultado['errores'])
                ? implode('; ', $resultado['errores'])
                : 'No se pudo guardar la evidencia', 'error');
        }

        $this->redirect('mantenimientos/' . $id);
    }

    /**
     * POST /mantenimientos/{id}/cerrar
     * Cierra el mantenimiento con costo real, proveedor y evidencia del
     * arreglo. Si se pide, registra el egreso por el flujo existente de caja
     * (con caja cerrada queda "por registrar" y se avisa; jamas se inserta
     * un movimiento directo).
     */
    public function cerrarAction()
    {
        if (!$this->isPost()) {
            $this->redirect('reportes/mantenimiento');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();

        $costo = $this->parseMonto($this->getPost('costo', ''));
        $costoEstimado = $this->parseMonto($this->getPost('costo_estimado', ''));
        $proveedor = trim((string)$this->getPost('proveedor', ''));
        $notaCosto = trim((string)$this->getPost('nota', $this->getPost('nota_costo', '')));
        $observaciones = trim((string)$this->getPost('observaciones', ''));
        $registrarGasto = trim((string)$this->getPost('registrar_gasto', '')) === '1';
        $metodoPago = trim((string)$this->getPost('metodo_pago', 'efectivo'));

        if ($costo !== null && $costo < 0) {
            set_mensaje('El costo real no puede ser negativo', 'error');
            $this->redirect('mantenimientos/' . $id);
            return;
        }

        if (!array_key_exists($metodoPago, MovimientoCaja::getMetodosPago())) {
            $metodoPago = 'efectivo';
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();

            $stmt = $db->query(
                "SELECT m.*, h.estado AS habitacion_estado
                 FROM mantenimientos_habitaciones m
                 LEFT JOIN habitaciones h
                    ON h.id = m.habitacion_id
                   AND h.hotel_id = m.hotel_id
                 WHERE m.id = ? AND m.hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$id, $hotelId]
            );
            $mant = $stmt ? $stmt->fetch() : null;

            if (!$mant) {
                throw new RuntimeException('Mantenimiento no encontrado para el hotel actual');
            }

            if (!in_array((string)$mant['estado'], ['en_proceso', 'programado'], true)) {
                throw new RuntimeException('Este mantenimiento ya esta cerrado');
            }

            $db->query(
                "UPDATE mantenimientos_habitaciones
                 SET estado = 'completado',
                     fecha_fin = NOW(),
                     costo = ?,
                     costo_estimado = COALESCE(?, costo_estimado),
                     proveedor = ?,
                     nota_costo = ?,
                     observaciones = CASE WHEN ? <> '' THEN ? ELSE observaciones END,
                     updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?",
                [
                    $costo,
                    $costoEstimado,
                    $proveedor !== '' ? $proveedor : null,
                    $notaCosto !== '' ? $notaCosto : null,
                    $observaciones,
                    $observaciones,
                    $id,
                    $hotelId,
                ]
            );

            // Liberar la habitacion solo si estaba en mantenimiento (mismo
            // comportamiento que el finalizar basico de /habitaciones).
            if (!empty($mant['habitacion_id']) && (string)($mant['habitacion_estado'] ?? '') === 'mantenimiento') {
                $db->query(
                    "UPDATE habitaciones SET estado = 'disponible', updated_at = NOW()
                     WHERE id = ? AND hotel_id = ? AND estado = 'mantenimiento'",
                    [(int)$mant['habitacion_id'], $hotelId]
                );
            }

            $db->safeCommit();
        } catch (Throwable $e) {
            if ($db->enTransaccion()) {
                $db->safeRollBack();
            }
            set_mensaje_error_op($e, 'cerrar el mantenimiento');
            $this->redirect('mantenimientos/' . $id);
            return;
        }

        $mensajes = ['Mantenimiento cerrado correctamente.'];

        // Preventivo por activo: el cierre marca el servicio realizado y
        // recalcula proximo_servicio (hoy + periodicidad).
        if (!empty($mant['activo_id'])) {
            $activoModel = new ActivoHotel();
            if ($activoModel->registrarServicioCompletado($hotelId, (int)$mant['activo_id'])) {
                $activoInfo = $activoModel->obtenerPorId((int)$mant['activo_id'], $hotelId);
                if ($activoInfo && !empty($activoInfo['proximo_servicio'])) {
                    $mensajes[] = 'Proximo servicio de "' . (string)$activoInfo['nombre'] . '": '
                        . date('d/m/Y', strtotime((string)$activoInfo['proximo_servicio'])) . '.';
                }
            }
        }

        // Evidencia del arreglo (post-commit: las fotos no son transaccionales).
        if (!empty($_FILES['fotos_resuelto']['name'][0] ?? '')) {
            $resultadoFotos = $this->fotoModel->guardarLoteDesdeUpload(
                $hotelId,
                $id,
                'resuelto',
                $_FILES['fotos_resuelto'],
                function_exists('user_id') ? user_id() : null
            );
            $subidas = count($resultadoFotos['fotos']);
            if ($subidas > 0) {
                $mensajes[] = 'Evidencia del arreglo guardada (' . $subidas . ' foto' . ($subidas === 1 ? '' : 's') . ').';
            }
            if (!empty($resultadoFotos['errores'])) {
                $mensajes[] = 'Fotos con aviso: ' . implode('; ', $resultadoFotos['errores']) . '.';
            }
        }

        $tono = 'success';
        if ($registrarGasto && $costo !== null && $costo > 0) {
            $gasto = $this->registrarGastoDeMantenimiento($id, $hotelId, $metodoPago);
            $mensajes[] = $gasto['message'];
            if (!$gasto['success']) {
                $tono = 'warning';
            }
        } elseif ($costo !== null && $costo > 0) {
            $mensajes[] = 'El costo quedo POR REGISTRAR en gastos; puedes registrarlo desde este detalle o desde Caja.';
            $tono = 'warning';
        }

        $this->notificarCierre($mant ?? [], $id);

        set_mensaje(implode(' ', $mensajes), $tono);
        $this->redirect('mantenimientos/' . $id);
    }

    /**
     * POST /mantenimientos/{id}/registrar-gasto
     * Registra en caja el costo de un mantenimiento ya cerrado (cola de
     * "por registrar", p. ej. porque la caja estaba cerrada al cierre).
     */
    public function registrarGastoAction()
    {
        if (!$this->isPost()) {
            $this->redirect('reportes/mantenimiento');
            return;
        }

        $this->validateCSRF();

        if (function_exists('require_permission')) {
            require_permission('caja.movimientos');
        }

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();
        $metodoPago = trim((string)$this->getPost('metodo_pago', 'efectivo'));
        if (!array_key_exists($metodoPago, MovimientoCaja::getMetodosPago())) {
            $metodoPago = 'efectivo';
        }

        $resultado = $this->registrarGastoDeMantenimiento($id, $hotelId, $metodoPago);
        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'warning');

        $volverA = trim((string)$this->getPost('volver_a', ''));
        $this->redirect($volverA === 'caja' ? 'caja' : 'mantenimientos/' . $id);
    }

    /**
     * Crea el egreso por el FLUJO EXISTENTE de caja y lo liga al
     * mantenimiento. Candados:
     *  - FOR UPDATE sobre el mantenimiento + gasto_movimiento_id IS NULL:
     *    un doble clic no duplica el egreso.
     *  - MovimientoCaja::registrarMovimiento aporta los candados de caja
     *    (corte abierto FOR UPDATE, nunca cae en corte cerrado).
     */
    private function registrarGastoDeMantenimiento(int $id, int $hotelId, string $metodoPago): array
    {
        if ($id <= 0 || $hotelId <= 0) {
            return ['success' => false, 'message' => 'Mantenimiento no valido.'];
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();

            $stmt = $db->query(
                "SELECT m.*, h.numero AS habitacion_numero
                 FROM mantenimientos_habitaciones m
                 LEFT JOIN habitaciones h
                    ON h.id = m.habitacion_id
                   AND h.hotel_id = m.hotel_id
                 WHERE m.id = ? AND m.hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$id, $hotelId]
            );
            $mant = $stmt ? $stmt->fetch() : null;

            if (!$mant) {
                throw new RuntimeException('Mantenimiento no encontrado para el hotel actual.');
            }

            if (!empty($mant['gasto_movimiento_id'])) {
                $db->safeRollBack();
                return ['success' => true, 'message' => 'El gasto de este mantenimiento ya estaba registrado en caja.'];
            }

            $costo = (float)($mant['costo'] ?? 0);
            if ($costo <= 0) {
                throw new RuntimeException('Este mantenimiento no tiene costo real mayor a cero.');
            }

            if ((string)$mant['estado'] !== 'completado') {
                throw new RuntimeException('Solo se registra el gasto de un mantenimiento completado.');
            }

            $categoriaId = $this->asegurarCategoriaGastoMantenimiento($hotelId);
            $ubicacion = trim((string)($mant['habitacion_numero'] ?? '')) !== ''
                ? 'hab. ' . $mant['habitacion_numero']
                : 'instalaciones';

            $movimientoModel = new MovimientoCaja();
            $resultado = $movimientoModel->registrarMovimiento([
                'tipo' => 'gasto',
                'categoria_id' => $categoriaId,
                'descripcion' => 'Mantenimiento #' . $id . ' (' . $ubicacion . '): ' . mb_substr((string)($mant['motivo'] ?? ''), 0, 150),
                'monto' => $costo,
                'metodo_pago' => $metodoPago,
                'referencia' => 'MANT-' . $id,
                'proveedor' => (string)($mant['proveedor'] ?? '') ?: null,
            ]);

            if (empty($resultado['success'])) {
                // Caja cerrada u otro candado: el costo queda "por registrar".
                if ($db->enTransaccion()) {
                    $db->safeRollBack();
                }
                $motivo = (string)($resultado['message'] ?? 'No se pudo registrar el movimiento');
                return [
                    'success' => false,
                    'message' => 'El gasto quedo POR REGISTRAR: ' . $motivo . '. Aparecera en la cola al abrir caja.',
                ];
            }

            $db->query(
                "UPDATE mantenimientos_habitaciones
                 SET gasto_movimiento_id = ?, gasto_registrado_en = NOW(), updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND gasto_movimiento_id IS NULL",
                [(int)$resultado['movimiento_id'], $id, $hotelId]
            );

            $db->safeCommit();

            return [
                'success' => true,
                'message' => 'Gasto de $' . number_format($costo, 2) . ' registrado en caja (referencia MANT-' . $id . ').',
            ];
        } catch (Throwable $e) {
            if ($db->enTransaccion()) {
                $db->safeRollBack();
            }
            return ['success' => false, 'message' => 'No se registro el gasto: ' . $e->getMessage()];
        }
    }

    /**
     * Categoria de gasto "Mantenimiento" del hotel (se crea si no existe,
     * respetando el catalogo por hotel de categorias_movimientos).
     */
    private function asegurarCategoriaGastoMantenimiento(int $hotelId): ?int
    {
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT id FROM categorias_movimientos
             WHERE hotel_id = ? AND tipo = 'gasto' AND nombre = 'Mantenimiento' AND activa = 1
             ORDER BY id ASC LIMIT 1",
            [$hotelId]
        );
        $row = $stmt ? $stmt->fetch() : null;
        if ($row) {
            return (int)$row['id'];
        }

        $db->query(
            "INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, descripcion, icono, color, activa, orden)
             VALUES (?, 'Mantenimiento', 'gasto', 'Costos de mantenimiento de habitaciones e instalaciones', 'fas fa-tools', '#C2841C', 1, 0)",
            [$hotelId]
        );

        $nuevoId = (int)$db->lastInsertId();
        return $nuevoId > 0 ? $nuevoId : null;
    }

    /**
     * Acepta montos con formato de MedisoftMoneyInput ("1,250.50").
     */
    private function parseMonto($valor): ?float
    {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return null;
        }

        $valor = str_replace([',', '$', ' '], '', $valor);
        return is_numeric($valor) ? (float)$valor : null;
    }

    private function notificarCierre(array $mant, int $id): void
    {
        try {
            $habitacionNumero = trim((string)($mant['habitacion_numero'] ?? ''));
            NotificacionService::crear([
                'hotel_id' => (int)$this->hotelIdActual(),
                'modulo' => 'habitaciones',
                'tipo' => 'mantenimiento_finalizado',
                'severidad' => 'info',
                'titulo' => 'Mantenimiento cerrado' . ($habitacionNumero !== '' ? ' en habitacion ' . $habitacionNumero : ''),
                'mensaje' => 'Se cerro el mantenimiento #' . $id . ' con evidencia y costo.',
                'entidad_tipo' => 'habitacion',
                'entidad_id' => (int)($mant['habitacion_id'] ?? 0) ?: null,
                'url' => 'mantenimientos/' . $id,
                'dedupe_key' => 'mantenimientos.cierre.' . $id,
                'creada_por' => function_exists('user_id') ? user_id() : null,
            ]);
        } catch (Throwable $e) {
            // La notificacion nunca frena el cierre.
        }
    }

    /**
     * GET /mantenimientos/activos
     * Catalogo de activos con preventivo (boiler, bomba, aires...).
     */
    public function activosAction()
    {
        $hotelId = (int)$this->hotelIdActual();
        $activoModel = new ActivoHotel();

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT id, numero FROM habitaciones
             WHERE hotel_id = ? AND COALESCE(activa, 1) = 1
             ORDER BY CAST(numero AS UNSIGNED), numero",
            [$hotelId]
        );

        View::renderTemplate('mantenimientos/activos', [
            'title' => 'Activos y preventivo - ' . current_hotel_display_name(),
            'activos' => $activoModel->listar($hotelId),
            'habitaciones' => $stmt ? ($stmt->fetchAll() ?: []) : [],
            'puede_gestionar' => function_exists('can') ? can('habitaciones.mantenimiento') : true,
        ]);
    }

    /**
     * GET /mantenimientos/activos/{id}
     * Historial de servicios del activo con costo acumulado.
     */
    public function activoAction()
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();

        $activoModel = new ActivoHotel();
        $activo = $activoModel->obtenerPorId($id, $hotelId);

        if (!$activo) {
            set_mensaje('Activo no encontrado', 'error');
            $this->redirect('mantenimientos/activos');
            return;
        }

        View::renderTemplate('mantenimientos/activo', [
            'title' => (string)$activo['nombre'] . ' - ' . current_hotel_display_name(),
            'activo' => $activo,
            'historial' => $activoModel->historialServicios($id, $hotelId),
            'puede_gestionar' => function_exists('can') ? can('habitaciones.mantenimiento') : true,
        ]);
    }

    /**
     * POST /mantenimientos/activos/guardar (alta o edicion segun id).
     */
    public function guardarActivoAction()
    {
        if (!$this->isPost()) {
            $this->redirect('mantenimientos/activos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $hotelId = (int)$this->hotelIdActual();
        $id = (int)$this->getPost('id', 0);

        $nombre = trim((string)$this->getPost('nombre', ''));
        $ubicacion = trim((string)$this->getPost('ubicacion', ''));
        $habitacionId = (int)$this->getPost('habitacion_id', 0);
        $periodicidad = (int)$this->getPost('periodicidad_dias', 0);
        $ultimoServicio = trim((string)$this->getPost('ultimo_servicio', ''));
        $proximoServicio = trim((string)$this->getPost('proximo_servicio', ''));
        $notas = trim((string)$this->getPost('notas', ''));

        if ($nombre === '') {
            set_mensaje('El nombre del activo es obligatorio', 'error');
            $this->redirect('mantenimientos/activos');
            return;
        }

        if ($periodicidad < 1 || $periodicidad > 3650) {
            set_mensaje('La periodicidad debe ser entre 1 y 3650 dias', 'error');
            $this->redirect('mantenimientos/activos');
            return;
        }

        // Habitacion opcional: si viene, debe ser del hotel.
        if ($habitacionId > 0) {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
                [$habitacionId, $hotelId]
            );
            if (!$stmt || !$stmt->fetch()) {
                set_mensaje('La habitacion elegida no pertenece a este hotel', 'error');
                $this->redirect('mantenimientos/activos');
                return;
            }
        }

        $ultimoServicio = $this->parseFecha($ultimoServicio);
        $proximoServicio = $this->parseFecha($proximoServicio);

        // proximo_servicio calculado si no se capturo: desde el ultimo
        // servicio (o desde hoy) + periodicidad.
        if ($proximoServicio === null) {
            $base = $ultimoServicio ?: date('Y-m-d');
            $proximoServicio = date('Y-m-d', strtotime($base . ' +' . $periodicidad . ' days'));
        }

        $activoModel = new ActivoHotel();

        if ($id > 0 && !$activoModel->obtenerPorId($id, $hotelId)) {
            set_mensaje('Activo no encontrado', 'error');
            $this->redirect('mantenimientos/activos');
            return;
        }

        $resultado = $activoModel->guardar([
            'nombre' => mb_substr($nombre, 0, 160),
            'ubicacion' => $ubicacion !== '' ? mb_substr($ubicacion, 0, 160) : null,
            'habitacion_id' => $habitacionId > 0 ? $habitacionId : null,
            'periodicidad_dias' => $periodicidad,
            'ultimo_servicio' => $ultimoServicio,
            'proximo_servicio' => $proximoServicio,
            'notas' => $notas !== '' ? $notas : null,
            'activo' => 1,
        ], $id > 0 ? $id : null);

        if ($resultado) {
            set_mensaje($id > 0 ? 'Activo actualizado' : 'Activo registrado; su preventivo vence el ' . date('d/m/Y', strtotime($proximoServicio)), 'success');
        } else {
            set_mensaje('No se pudo guardar el activo', 'error');
        }

        $this->redirect('mantenimientos/activos');
    }

    /**
     * POST /mantenimientos/activos/{id}/toggle (pausar/reactivar preventivo).
     */
    public function toggleActivoAction()
    {
        if (!$this->isPost()) {
            $this->redirect('mantenimientos/activos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = (int)$this->hotelIdActual();

        $activoModel = new ActivoHotel();
        $activo = $activoModel->obtenerPorId($id, $hotelId);

        if (!$activo) {
            set_mensaje('Activo no encontrado', 'error');
        } else {
            $nuevo = (int)($activo['activo'] ?? 1) === 1 ? 0 : 1;
            $activoModel->guardar(['activo' => $nuevo], $id);
            set_mensaje($nuevo ? 'Preventivo reactivado' : 'Preventivo pausado (no generara mantenimientos)', 'success');
        }

        $this->redirect('mantenimientos/activos');
    }

    /**
     * POST /mantenimientos/activos/generar
     * Genera ahora los preventivos vencidos del hotel (mismo motor del cron).
     */
    public function generarPreventivosAction()
    {
        if (!$this->isPost()) {
            $this->redirect('mantenimientos/activos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('habitaciones.mantenimiento');

        $servicio = new MantenimientoPreventivoService();
        $resumen = $servicio->generarParaHotel((int)$this->hotelIdActual());

        $partes = [];
        if ($resumen['generados'] > 0) {
            $partes[] = $resumen['generados'] . ' preventivo' . ($resumen['generados'] === 1 ? '' : 's') . ' generado' . ($resumen['generados'] === 1 ? '' : 's') . ' con su tarea';
        }
        if ($resumen['omitidos'] > 0) {
            $partes[] = $resumen['omitidos'] . ' ya tenian mantenimiento abierto';
        }
        if (empty($partes)) {
            $partes[] = 'No hay activos vencidos por generar';
        }

        $tono = empty($resumen['errores']) ? 'success' : 'warning';
        if (!empty($resumen['errores'])) {
            $partes[] = 'Errores: ' . implode('; ', $resumen['errores']);
        }

        set_mensaje(implode('. ', $partes) . '.', $tono);
        $this->redirect('mantenimientos/activos');
    }

    private function parseFecha(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        $ts = strtotime($valor);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    /**
     * Detalle tenancy-safe con habitacion y usuario que registro.
     */
    private function obtenerDetalle(int $id, int $hotelId): ?array
    {
        if ($id <= 0 || $hotelId <= 0) {
            return null;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT m.*,
                    h.numero AS habitacion_numero,
                    h.tipo AS habitacion_tipo,
                    h.estado AS habitacion_estado,
                    u.nombre_completo AS usuario_registro_nombre,
                    a.nombre AS activo_nombre,
                    a.ubicacion AS activo_ubicacion
             FROM mantenimientos_habitaciones m
             LEFT JOIN habitaciones h
                ON h.id = m.habitacion_id
               AND h.hotel_id = m.hotel_id
             LEFT JOIN usuarios u
                ON u.id = m.usuario_registro_id
             LEFT JOIN activos_hotel a
                ON a.id = m.activo_id
               AND a.hotel_id = m.hotel_id
             WHERE m.id = ?
               AND m.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }
}
