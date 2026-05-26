<?php
/**
 * API: Buscador Global
 * Ruta: GET /api/buscar?q=texto
 * Busca en huéspedes, reservaciones y habitaciones.
 * Los Cedros
 */

define('ROOT_PATH',    dirname(__DIR__));
define('APP_PATH',     ROOT_PATH . '/app');
define('CONFIG_PATH',  ROOT_PATH . '/config');
define('CORE_PATH',    ROOT_PATH . '/core');
define('PUBLIC_PATH',  ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('session.use_strict_mode', 1);

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

$q = trim($_GET['q'] ?? '');

if ($q === '__offline_cache__') {
    try {
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT id, nombre_completo, telefono, procedencia_estado
             FROM huespedes
             ORDER BY nombre_completo ASC
             LIMIT 1000"
        );
        $huespedes = $stmt ? $stmt->fetchAll() : [];

        $stmt = $db->query(
            "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida,
                    r.hora_llegada_estimada, r.hora_entrada, r.hora_salida,
                    r.precio_total, r.metodo_pago, r.notas, r.total_habitaciones,
                    h.id AS huesped_id,
                    h.nombre_completo AS huesped_nombre,
                    h.telefono AS huesped_telefono,
                    h.procedencia_estado,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones,
                    GROUP_CONCAT(DISTINCT hab.id SEPARATOR ',') AS habitaciones_ids,
                    GROUP_CONCAT(DISTINCT hab.tipo SEPARATOR '||') AS habitaciones_tipos
             FROM reservaciones r
             INNER JOIN huespedes h ON r.huesped_id = h.id
             LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
             LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
             GROUP BY r.id
             ORDER BY r.updated_at DESC, r.id DESC
             LIMIT 3000"
        );
        $reservaciones = $stmt ? $stmt->fetchAll() : [];

        $stmt = $db->query(
            "SELECT id, numero, tipo, estado, precio_base
             FROM habitaciones
             WHERE activa = 1
             ORDER BY numero ASC"
        );
        $habitaciones = $stmt ? $stmt->fetchAll() : [];

        $resultados = [];

        foreach ($huespedes as $h) {
            $resultados[] = [
                'tipo'      => 'huesped',
                'icono'     => 'fa-user',
                'color'     => '#5C7A4E',
                'titulo'    => $h['nombre_completo'],
                'subtitulo' => $h['telefono'] ? 'Tel. ' . $h['telefono'] : ($h['procedencia_estado'] ?? ''),
                'url'       => 'huespedes/' . $h['id'],
            ];
        }

        $estados_label = [
            'confirmada'  => 'Confirmada',
            'checked_in'  => 'Check-in',
            'completada'  => 'Check-out',
            'cancelada'   => 'Cancelada',
        ];

        foreach ($reservaciones as $r) {
            $fecha = !empty($r['fecha_entrada']) ? date('d/m/Y', strtotime($r['fecha_entrada'])) : '-';
            $resultados[] = [
                'tipo'      => 'reservacion',
                'icono'     => 'fa-calendar-check',
                'color'     => '#2563EB',
                'titulo'    => '#' . $r['id'] . ' - ' . $r['huesped_nombre'],
                'subtitulo' => ($r['habitaciones'] ?? '-') . ' | ' . $fecha . ' | ' . ($estados_label[$r['estado']] ?? $r['estado']),
                'url'       => 'reservaciones/ver/' . $r['id'],
            ];
        }

        $estado_es = [
            'disponible'   => 'Disponible',
            'ocupada'      => 'Ocupada',
            'mantenimiento'=> 'Mantenimiento',
            'limpieza'     => 'Limpieza',
        ];

        foreach ($habitaciones as $hab) {
            $resultados[] = [
                'tipo'      => 'habitacion',
                'icono'     => 'fa-bed',
                'color'     => '#7C3AED',
                'titulo'    => 'Habitacion ' . $hab['numero'],
                'subtitulo' => ucfirst(str_replace('_', ' ', $hab['tipo'])) . ' | ' . ($estado_es[$hab['estado']] ?? $hab['estado']),
                'url'       => 'habitaciones',
            ];
        }

        echo json_encode([
            'success' => true,
            'query' => $q,
            'resultados' => $resultados,
            'huespedes' => $huespedes,
            'reservaciones' => array_map(function (array $r): array {
                return [
                    'id' => (int) $r['id'],
                    'estado' => $r['estado'],
                    'fecha_entrada' => $r['fecha_entrada'],
                    'fecha_salida' => $r['fecha_salida'],
                    'hora_llegada_estimada' => $r['hora_llegada_estimada'],
                    'hora_entrada' => $r['hora_entrada'],
                    'hora_salida' => $r['hora_salida'],
                    'precio_total' => (float) $r['precio_total'],
                    'metodo_pago' => $r['metodo_pago'],
                    'notas' => $r['notas'],
                    'total_habitaciones' => (int) ($r['total_habitaciones'] ?: 1),
                    'huesped_id' => (int) $r['huesped_id'],
                    'huesped_nombre' => $r['huesped_nombre'],
                    'huesped_telefono' => $r['huesped_telefono'],
                    'procedencia_estado' => $r['procedencia_estado'],
                    'habitaciones_numeros' => $r['habitaciones'],
                    'habitaciones_ids' => $r['habitaciones_ids'],
                    'habitaciones_tipos' => $r['habitaciones_tipos'],
                ];
            }, $reservaciones),
            'habitaciones' => $habitaciones,
            'total' => count($resultados),
        ]);
    } catch (Throwable $e) {
        error_log('[API buscar offline cache] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
    exit;
}

$q_id_rapido = preg_replace('/\D+/', '', $q);
if (strlen($q) < 2 && $q_id_rapido === '') {
    echo json_encode(['success' => true, 'resultados' => []]);
    exit;
}

try {
    $db     = Database::getInstance();
    $buscar = '%' . $q . '%';
    $q_id = preg_replace('/\D+/', '', $q);
    $buscar_id = $q_id !== '' ? '%' . $q_id . '%' : $buscar;
    $id_exacto = $q_id !== '' ? $q_id : '__sin_id__';
    $limite = 5; // máximo por categoría

    // ── Huéspedes ────────────────────────────────────────────────────────────
    $stmt = $db->query(
        "SELECT id, nombre_completo, telefono, procedencia_estado
         FROM huespedes
         WHERE nombre_completo LIKE ? OR telefono LIKE ?
         ORDER BY nombre_completo ASC
         LIMIT ?",
        [$buscar, $buscar, $limite]
    );
    $huespedes = $stmt ? $stmt->fetchAll() : [];

    // ── Reservaciones ─────────────────────────────────────────────────────────
    $stmt = $db->query(
        "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida, r.precio_total,
                h.nombre_completo AS huesped_nombre,
                GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
         FROM reservaciones r
         INNER JOIN huespedes h   ON r.huesped_id = h.id
         LEFT  JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
         LEFT  JOIN habitaciones hab ON rh.habitacion_id = hab.id
         WHERE CAST(r.id AS CHAR) LIKE ?
            OR h.nombre_completo LIKE ?
            OR hab.numero LIKE ?
         GROUP BY r.id
         ORDER BY
            CASE
                WHEN CAST(r.id AS CHAR) = ? THEN 0
                WHEN CAST(r.id AS CHAR) LIKE ? THEN 1
                ELSE 2
            END,
            r.fecha_entrada DESC
         LIMIT ?",
        [$buscar_id, $buscar, $buscar, $id_exacto, $buscar_id, $limite]
    );
    $reservaciones = $stmt ? $stmt->fetchAll() : [];

    // ── Habitaciones ──────────────────────────────────────────────────────────
    $stmt = $db->query(
        "SELECT id, numero, tipo, estado, precio_base
         FROM habitaciones
         WHERE (numero LIKE ? OR tipo LIKE ?)
           AND activa = 1
         ORDER BY numero ASC
         LIMIT ?",
        [$buscar, $buscar, $limite]
    );
    $habitaciones = $stmt ? $stmt->fetchAll() : [];

    // ── Normalizar resultados ─────────────────────────────────────────────────
    $resultados = [];

    foreach ($huespedes as $h) {
        $resultados[] = [
            'tipo'      => 'huesped',
            'icono'     => 'fa-user',
            'color'     => '#5C7A4E',
            'titulo'    => $h['nombre_completo'],
            'subtitulo' => $h['telefono'] ? '📞 ' . $h['telefono'] : ($h['procedencia_estado'] ?? ''),
            'url'       => 'huespedes/' . $h['id'],
        ];
    }

    $estados_label = [
        'confirmada'  => 'Confirmada',
        'checked_in'  => 'Check-in',
        'completada'  => 'Check-out',
        'cancelada'   => 'Cancelada',
    ];
    foreach ($reservaciones as $r) {
        $fecha = !empty($r['fecha_entrada']) ? date('d/m/Y', strtotime($r['fecha_entrada'])) : '-';
        $resultados[] = [
            'tipo'      => 'reservacion',
            'icono'     => 'fa-calendar-check',
            'color'     => '#2563EB',
            'titulo'    => '#' . $r['id'] . ' — ' . $r['huesped_nombre'],
            'subtitulo' => ($r['habitaciones'] ?? '-') . ' · ' . $fecha . ' · ' . ($estados_label[$r['estado']] ?? $r['estado']),
            'url'       => 'reservaciones/ver/' . $r['id'],
        ];
    }

    foreach ($habitaciones as $hab) {
        $estado_es = [
            'disponible'   => 'Disponible',
            'ocupada'      => 'Ocupada',
            'mantenimiento'=> 'Mantenimiento',
            'limpieza'     => 'Limpieza',
        ];
        $resultados[] = [
            'tipo'      => 'habitacion',
            'icono'     => 'fa-bed',
            'color'     => '#7C3AED',
            'titulo'    => 'Habitación ' . $hab['numero'],
            'subtitulo' => ucfirst(str_replace('_', ' ', $hab['tipo'])) . ' · ' . ($estado_es[$hab['estado']] ?? $hab['estado']),
            'url'       => 'habitaciones',
        ];
    }

    echo json_encode([
        'success'     => true,
        'query'       => $q,
        'resultados'  => $resultados,
        'total'       => count($resultados),
    ]);

} catch (Throwable $e) {
    error_log('[API buscar] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
