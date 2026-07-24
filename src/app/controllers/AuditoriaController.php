<?php
/**
 * Bitacora de auditoria (bloque auditoria): quien hizo que y cuando.
 * Solo lectura para gerente/administrador; la tabla es append-only
 * (los eventos los escribe el hook central de Controller::runAction).
 */

class AuditoriaController extends Controller {

    private const PERMISOS = [
        'index' => 'auditoria.view',
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('auditoria');
        }

        // Bitacora: quien la lee vigila a los demas. Antes se gateaba por el
        // rol-string ('gerente'/'administrador'), que NO distingue los roles
        // personalizados: todos se guardan como 'recepcionista' (ver
        // UsuarioController::resolverAsignacionRol), asi que un rol a la medida
        // no podia entrar aunque el propietario se lo concediera. Ahora manda
        // el permiso, que si es configurable desde Roles y permisos.
        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $db = Database::getInstance();

        $filtroUsuario = max(0, (int) $this->getQuery('usuario', 0));
        $filtroModulo = trim((string) $this->getQuery('modulo', ''));
        $filtroDesde = trim((string) $this->getQuery('desde', ''));
        $filtroHasta = trim((string) $this->getQuery('hasta', ''));

        $where = ['hotel_id = ?'];
        $params = [$hotelId];

        if ($filtroUsuario > 0) {
            $where[] = 'usuario_id = ?';
            $params[] = $filtroUsuario;
        }
        if ($filtroModulo !== '' && preg_match('/^[A-Za-z]{2,60}$/', $filtroModulo)) {
            $where[] = 'modulo = ?';
            $params[] = $filtroModulo;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroDesde)) {
            $where[] = 'created_at >= ?';
            $params[] = $filtroDesde . ' 00:00:00';
        } else {
            $filtroDesde = '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroHasta)) {
            $where[] = 'created_at <= ?';
            $params[] = $filtroHasta . ' 23:59:59';
        } else {
            $filtroHasta = '';
        }

        $eventos = [];
        $usuarios = [];
        $modulos = [];

        try {
            $stmt = $db->query(
                "SELECT * FROM auditoria_eventos
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY id DESC
                 LIMIT 300",
                $params
            );
            $eventos = $stmt ? $stmt->fetchAll() : [];

            $stmt = $db->query(
                "SELECT DISTINCT usuario_id, usuario_nombre FROM auditoria_eventos
                 WHERE hotel_id = ? AND usuario_id IS NOT NULL
                 ORDER BY usuario_nombre LIMIT 100",
                [$hotelId]
            );
            $usuarios = $stmt ? $stmt->fetchAll() : [];

            $stmt = $db->query(
                "SELECT DISTINCT modulo FROM auditoria_eventos WHERE hotel_id = ? ORDER BY modulo LIMIT 60",
                [$hotelId]
            );
            $modulos = $stmt ? array_column($stmt->fetchAll(), 'modulo') : [];
        } catch (Throwable $e) {
            error_log('Auditoria: error al listar eventos: ' . $e->getMessage());
        }

        View::renderTemplate('auditoria/index', [
            'title' => 'Historial de actividad - ' . current_hotel_display_name(),
            'eventos' => $eventos,
            'usuarios' => $usuarios,
            'modulos' => $modulos,
            'filtros' => [
                'usuario' => $filtroUsuario,
                'modulo' => $filtroModulo,
                'desde' => $filtroDesde,
                'hasta' => $filtroHasta,
            ],
        ]);
    }
}
