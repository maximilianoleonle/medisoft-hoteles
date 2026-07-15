<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Mantenimiento.php';
require_once __DIR__ . '/../models/MantenimientoFoto.php';
require_once __DIR__ . '/../models/TareaOperativa.php';
require_once __DIR__ . '/../services/NotificacionService.php';

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
                    u.nombre_completo AS usuario_registro_nombre
             FROM mantenimientos_habitaciones m
             LEFT JOIN habitaciones h
                ON h.id = m.habitacion_id
               AND h.hotel_id = m.hotel_id
             LEFT JOIN usuarios u
                ON u.id = m.usuario_registro_id
             WHERE m.id = ?
               AND m.hotel_id = ?
             LIMIT 1",
            [$id, $hotelId]
        );

        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }
}
