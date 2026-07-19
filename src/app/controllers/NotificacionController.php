<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../services/NotificacionService.php';
require_once __DIR__ . '/../services/NotificacionReglasService.php';

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

        if (function_exists('require_hotel_module')) {
            require_hotel_module('notificaciones');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $estadoFiltro = (string)$this->getQuery('estado', 'activas');
        if ($estadoFiltro === 'nueva') {
            $estadoFiltro = 'activas';
        }
        if (!in_array($estadoFiltro, ['activas', 'resuelta', 'descartada'], true)) {
            $estadoFiltro = 'activas';
        }

        $filtros = [
            'estado' => $estadoFiltro,
            'modulo' => $this->getQuery('modulo', ''),
            'severidad' => '',
            'rol_usuario' => function_exists('current_hotel_user_role') ? current_hotel_user_role() : null,
            'usuario_id' => function_exists('user_id') ? user_id() : null,
        ];

        $tablaDisponible = $this->notificacionModel->tablaDisponible();
        if ($tablaDisponible) {
            NotificacionReglasService::evaluarDashboard($hotelId);
            NotificacionService::sincronizarBandeja($hotelId);
        }
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

        // Abrir tiene efectos (marcar leida / archivar): una precarga
        // especulativa del navegador NUNCA debe dispararlos. 503 hace que el
        // navegador descarte la especulacion y repita la peticion real al clic.
        if (function_exists('is_speculative_request') && is_speculative_request()) {
            http_response_code(503);
            header('Cache-Control: no-store');
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

        if ((string)$this->getPost('accion', '') === 'archivar_pendientes') {
            $totalArchivadas = $this->archivarPendientesVisibles();
            set_mensaje(
                $totalArchivadas > 0
                    ? $totalArchivadas . ' notificacion(es) pendiente(s) fueron archivadas.'
                    : 'No habia notificaciones pendientes por archivar.',
                'success'
            );
            $this->redirect('notificaciones');
            return;
        }

        $ok = $this->notificacionModel->marcarTodasLeidas($this->hotelIdActual());
        set_mensaje($ok ? 'Todas las notificaciones nuevas fueron marcadas como leidas.' : 'No se pudieron actualizar las notificaciones.', $ok ? 'success' : 'error');
        $this->redirect('notificaciones');
    }

    private function archivarPendientesVisibles(): int {
        $hotelId = $this->hotelIdActual();

        if ($hotelId <= 0 || !$this->notificacionModel->tablaDisponible() || !class_exists('Database')) {
            return 0;
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT *
             FROM notificaciones
             WHERE hotel_id = ?
               AND estado IN ('nueva', 'leida')",
            [$hotelId]
        );

        $notificaciones = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        if (empty($notificaciones)) {
            return 0;
        }

        $rolUsuario = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
        $usuarioId = function_exists('user_id') ? user_id() : null;
        $archivadas = 0;

        foreach ($notificaciones as $notificacion) {
            $id = (int)($notificacion['id'] ?? 0);
            if ($id <= 0 || !$this->notificacionModel->visibleParaUsuario($notificacion, $rolUsuario, $usuarioId)) {
                continue;
            }

            if ($this->notificacionModel->cambiarEstado($id, $hotelId, 'descartada')) {
                $archivadas++;
            }
        }

        return $archivadas;
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
            if ($this->isAjax()) {
                View::renderJSON(['success' => false, 'message' => 'Notificacion no encontrada.'], 404);
                return;
            }
            set_mensaje('Notificacion no encontrada.', 'error');
            $this->redirect('notificaciones');
            return;
        }

        $ok = $this->notificacionModel->cambiarEstado($id, $hotelId, $estado);

        if ($this->isAjax()) {
            View::renderJSON([
                'success' => $ok,
                'message' => $ok ? $mensajeExito : 'No se pudo actualizar la notificacion.',
            ], $ok ? 200 : 500);
            return;
        }

        set_mensaje($ok ? $mensajeExito : 'No se pudo actualizar la notificacion.', $ok ? 'success' : 'error');
        // Solo se regresa al Referer si es de este mismo host (anti open redirect).
        $this->redirectBackSeguro('notificaciones');
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
