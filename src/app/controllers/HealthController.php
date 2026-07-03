<?php
/**
 * Health check HTTP para monitoreo de produccion.
 *
 * GET /health?token=XXXX  (o header X-Health-Token: XXXX)
 *
 * Protegido por el token HEALTH_TOKEN del entorno. Si el token no esta
 * configurado o no coincide, responde 404 para no revelar que el endpoint
 * existe. Nunca expone datos de hoteles, usuarios ni configuracion.
 *
 * Respuestas: 200 (status ok) / 503 (status fail) con detalle por check.
 */

class HealthController extends Controller {

    // Tablas sin las cuales la operacion multi-hotel no funciona.
    private $tablasCriticas = [
        'usuarios',
        'hoteles',
        'hotel_usuarios',
        'hotel_modulos',
        'hotel_configuracion',
        'modulos',
        'roles',
        'reservaciones',
        'reservacion_habitaciones',
        'habitaciones',
        'movimientos_caja',
        'cortes_caja',
        'huespedes',
        'notificaciones',
        'remember_tokens',
    ];

    public function indexAction() {
        $tokenEsperado = getenv('HEALTH_TOKEN') ?: '';
        $tokenRecibido = $_GET['token'] ?? ($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '');

        // Sin token configurado el endpoint queda cerrado (fail closed).
        if ($tokenEsperado === '' || !hash_equals($tokenEsperado, (string) $tokenRecibido)) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Página no encontrada']);
            exit;
        }

        $checks = [];
        $ok = true;

        // 1. Base de datos viva
        $inicio = microtime(true);
        try {
            $db = Database::getInstance();
            $stmt = $db->query('SELECT 1');
            $dbOk = $stmt !== false && $stmt->fetchColumn() == 1;
        } catch (Throwable $e) {
            $dbOk = false;
            ms_log('critical', 'Health check: BD inaccesible: ' . $e->getMessage());
        }
        $checks['db'] = [
            'ok' => $dbOk,
            'ms' => round((microtime(true) - $inicio) * 1000, 1),
        ];
        $ok = $ok && $dbOk;

        // 2. Tablas criticas presentes
        $checks['tablas'] = ['ok' => false, 'faltantes' => $this->tablasCriticas];
        if ($dbOk) {
            try {
                $placeholders = implode(',', array_fill(0, count($this->tablasCriticas), '?'));
                $stmt = $db->query(
                    "SELECT TABLE_NAME FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($placeholders)",
                    $this->tablasCriticas
                );
                $presentes = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
                $faltantes = array_values(array_diff($this->tablasCriticas, $presentes));
                $checks['tablas'] = ['ok' => empty($faltantes), 'faltantes' => $faltantes];

                if (!empty($faltantes)) {
                    ms_log('critical', 'Health check: tablas criticas faltantes', ['faltantes' => $faltantes]);
                }
            } catch (Throwable $e) {
                ms_log('error', 'Health check: no se pudieron verificar tablas: ' . $e->getMessage());
            }
        }
        $ok = $ok && $checks['tablas']['ok'];

        // 3. Storage de logs escribible
        $logsDir = STORAGE_PATH . '/logs';
        $storageOk = is_dir($logsDir) && is_writable($logsDir);
        $checks['storage_logs'] = ['ok' => $storageOk];
        $ok = $ok && $storageOk;

        // 4. Senal de negocio minima (solo un numero, sin datos de tenants)
        $checks['hoteles_activos'] = null;
        if ($dbOk && $checks['tablas']['ok']) {
            try {
                $stmt = $db->query('SELECT COUNT(*) FROM hoteles WHERE activo = 1');
                if ($stmt !== false) {
                    $checks['hoteles_activos'] = (int) $stmt->fetchColumn();
                }
            } catch (Throwable $e) {
                // No es bloqueante: la columna 'activo' podria variar por entorno.
                $checks['hoteles_activos'] = null;
            }
        }

        View::renderJSON([
            'status' => $ok ? 'ok' : 'fail',
            'ts' => date('Y-m-d H:i:s'),
            'version' => defined('APP_VERSION') ? APP_VERSION : null,
            'php' => PHP_VERSION,
            'request_id' => ms_request_id(),
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }
}
