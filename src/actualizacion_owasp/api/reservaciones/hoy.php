<?php
/**
 * API: Reservaciones del día (snapshot para modo offline)
 * Ruta: /api/reservaciones/hoy
 * Método: GET
 * Los Cedros
 *
 * Devuelve las reservaciones activas de hoy + las de mañana que
 * ya tienen check-in pendiente. Diseñado para ser cacheado en
 * IndexedDB y servir en modo offline.
 */

define('ROOT_PATH',    dirname(dirname(__DIR__)));
define('APP_PATH',     ROOT_PATH . '/app');
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('CORE_PATH',    ROOT_PATH . '/core');
define('PUBLIC_PATH',  ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('session.use_strict_mode', 1);
error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {
    foreach ([CORE_PATH, APP_PATH . '/models', APP_PATH . '/controllers'] as $dir) {
        $file = "$dir/$class.php";
        if (file_exists($file)) { require_once $file; return; }
    }
});

require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/functions.php';

$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $db  = Database::getInstance();
    $hoy = date('Y-m-d');

    // Reservaciones que cubren hoy (ya checkeadas o por chekear)
    // + las de mañana ya confirmadas (para anticipar check-in)
    $sql = "SELECT
                r.id,
                r.estado,
                r.fecha_entrada,
                r.fecha_salida,
                r.hora_llegada_estimada,
                r.hora_entrada,
                r.hora_salida,
                r.precio_total,
                r.metodo_pago,
                r.notas,
                r.total_habitaciones,
                h.id            AS huesped_id,
                h.nombre_completo AS huesped_nombre,
                h.telefono      AS huesped_telefono,
                h.procedencia_estado,
                GROUP_CONCAT(
                    DISTINCT hab.numero
                    ORDER BY CAST(hab.numero AS UNSIGNED), hab.numero
                    SEPARATOR ', '
                )               AS habitaciones_numeros,
                GROUP_CONCAT(
                    DISTINCT hab.id
                    SEPARATOR ','
                )               AS habitaciones_ids,
                GROUP_CONCAT(
                    DISTINCT hab.tipo
                    SEPARATOR '||'
                )               AS habitaciones_tipos
            FROM reservaciones r
            INNER JOIN huespedes h   ON r.huesped_id = h.id
            LEFT  JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            LEFT  JOIN habitaciones hab ON rh.habitacion_id = hab.id
            WHERE r.estado IN ('confirmada', 'checked_in')
              AND r.fecha_entrada <= DATE_ADD(?, INTERVAL 1 DAY)
              AND r.fecha_salida  >= ?
            GROUP BY r.id
            ORDER BY r.fecha_entrada ASC, r.hora_llegada_estimada ASC";

    $stmt = $db->query($sql, [$hoy, $hoy]);
    $filas = $stmt ? $stmt->fetchAll() : [];

    // Normalizar para JSON
    $reservaciones = array_map(function (array $r): array {
        return [
            'id'                   => (int)  $r['id'],
            'estado'               => $r['estado'],
            'fecha_entrada'        => $r['fecha_entrada'],
            'fecha_salida'         => $r['fecha_salida'],
            'hora_llegada_estimada'=> $r['hora_llegada_estimada'],
            'hora_entrada'         => $r['hora_entrada'],
            'hora_salida'          => $r['hora_salida'],
            'precio_total'         => (float) $r['precio_total'],
            'metodo_pago'          => $r['metodo_pago'],
            'notas'                => $r['notas'],
            'total_habitaciones'   => (int) $r['total_habitaciones'],
            'huesped_id'           => (int) $r['huesped_id'],
            'huesped_nombre'       => $r['huesped_nombre'],
            'huesped_telefono'     => $r['huesped_telefono'],
            'procedencia_estado'   => $r['procedencia_estado'],
            'habitaciones_numeros' => $r['habitaciones_numeros'],
            'habitaciones_ids'     => $r['habitaciones_ids'] ? array_map('intval', explode(',', $r['habitaciones_ids'])) : [],
            'habitaciones_tipos'   => $r['habitaciones_tipos'],
        ];
    }, $filas);

    echo json_encode([
        'success'        => true,
        'fecha'          => $hoy,
        'total'          => count($reservaciones),
        'reservaciones'  => $reservaciones,
        'generado_at'    => date('Y-m-d H:i:s'),
    ]);

} catch (Throwable $e) {
    error_log('[API reservaciones/hoy] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
