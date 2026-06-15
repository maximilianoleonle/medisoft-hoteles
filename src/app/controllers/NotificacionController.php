<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Notificacion.php';

class NotificacionController extends Controller {
    private $notificacionModel;

    public function __construct($route_params = []) {
        parent::__construct($route_params);
        $this->notificacionModel = new Notificacion();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $estadoFiltro = (string)$this->getQuery('estado', 'nueva');
        if ($estadoFiltro === 'activas') {
            $estadoFiltro = 'nueva';
        }

        $filtros = [
            'estado' => $estadoFiltro,
            'modulo' => $this->getQuery('modulo', ''),
            'severidad' => $this->getQuery('severidad', ''),
            'rol_usuario' => function_exists('current_hotel_user_role') ? current_hotel_user_role() : null,
            'usuario_id' => function_exists('user_id') ? user_id() : null,
        ];

        $tablaDisponible = $this->notificacionModel->tablaDisponible();
        $notificaciones = $tablaDisponible
            ? $this->notificacionModel->listarPorHotel($hotelId, $filtros, 120)
            : [];
        $resumen = $tablaDisponible
            ? $this->notificacionModel->resumenPorHotel($hotelId, $filtros['rol_usuario'], $filtros['usuario_id'])
            : [];
        $modulos = $tablaDisponible
            ? $this->notificacionModel->modulosPorHotel($hotelId, $filtros['rol_usuario'], $filtros['usuario_id'])
            : [];

        View::renderTemplate('notificaciones/index', [
            'title' => 'Notificaciones - ' . current_hotel_display_name(),
            'notificaciones' => $notificaciones,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'modulos' => $modulos,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function marcarLeidaAction() {
        $this->accionEstado('leida', 'Notificacion marcada como leida.');
    }

    public function abrirAction() {
        if (!$this->isGet()) {
            $this->redirect('notificaciones');
            return;
        }

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $notificacion = $id > 0 ? $this->notificacionModel->buscarPorIdHotel($id, $hotelId) : null;

        $rolUsuario = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
        $usuarioId = function_exists('user_id') ? user_id() : null;

        if (!$notificacion || !$this->notificacionModel->visibleParaUsuario($notificacion, $rolUsuario, $usuarioId)) {
            set_mensaje('Notificacion no encontrada.', 'error');
            $this->redirect('notificaciones');
            return;
        }

        $estado = (string)($notificacion['estado'] ?? '');
        if (in_array($estado, ['nueva', 'leida'], true) && $this->seArchivaAlAbrir($notificacion)) {
            $this->notificacionModel->cambiarEstado($id, $hotelId, 'descartada');
        } elseif ($estado === 'nueva') {
            $this->notificacionModel->marcarLeidaAlAbrir($id, $hotelId);
        }

        $destino = trim((string)($notificacion['url'] ?? ''));
        $this->redirect($destino !== '' ? $destino : 'notificaciones');
    }

    public function resolverAction() {
        $this->accionEstado('resuelta', 'Notificacion resuelta.');
    }

    public function descartarAction() {
        $this->accionEstado('descartada', 'Notificacion descartada.');
    }

    public function marcarTodasLeidasAction() {
        if (!$this->isPost()) {
            $this->redirect('notificaciones');
            return;
        }

        $this->validateCSRF();
        $ok = $this->notificacionModel->marcarTodasLeidas($this->hotelIdActual());
        set_mensaje($ok ? 'Todas las notificaciones nuevas fueron marcadas como leidas.' : 'No se pudieron actualizar las notificaciones.', $ok ? 'success' : 'error');
        $this->redirect('notificaciones');
    }

    private function accionEstado(string $estado, string $mensajeExito): void {
        if (!$this->isPost()) {
            $this->redirect('notificaciones');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        if ($id <= 0 || !$this->notificacionModel->buscarPorIdHotel($id, $hotelId)) {
            set_mensaje('Notificacion no encontrada.', 'error');
            $this->redirect('notificaciones');
            return;
        }

        $ok = $this->notificacionModel->cambiarEstado($id, $hotelId, $estado);
        set_mensaje($ok ? $mensajeExito : 'No se pudo actualizar la notificacion.', $ok ? 'success' : 'error');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? 'notificaciones');
    }

    private function seArchivaAlAbrir(array $notificacion): bool {
        $modulo = strtolower(trim((string)($notificacion['modulo'] ?? '')));
        $tipo = strtolower(trim((string)($notificacion['tipo'] ?? '')));

        if ($tipo === 'regla_reporte_gerencial_diario') {
            return true;
        }

        return $modulo === 'reportes' && strpos($tipo, 'regla_reporte_') === 0;
    }

    private function hotelIdActual(): int {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int) obtenerHotelIdActualCompat()
            : (int) ($_SESSION['hotel_id'] ?? 0);
    }
}
