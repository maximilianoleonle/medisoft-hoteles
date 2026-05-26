<?php
/**
 * API: Obtener todas las habitaciones con información de ocupación Y mantenimiento
 * Ruta: api/habitaciones/todas-con-ocupacion
 * 
 * ARCHIVO ACTUALIZADO - Reemplazar el existente
 * Cambio: Ahora incluye información de mantenimientos programados
 * 
 * Los Cedros
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
        if (file_exists($file)) {
            require_once $file;
            return;
        }
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
    $fecha_entrada = $_GET['fecha_entrada'] ?? date('Y-m-d');
    $fecha_salida = $_GET['fecha_salida'] ?? date('Y-m-d', strtotime('+1 day'));
    
    $db = Database::getInstance();
    
    // 1. Obtener TODAS las habitaciones activas
    $sql = "SELECT h.* FROM habitaciones h WHERE h.activa = 1 ORDER BY h.piso, CAST(h.numero AS UNSIGNED)";
    $stmt = $db->query($sql);
    $habitaciones = $stmt->fetchAll();
    
    // 2. Obtener habitaciones ocupadas en esas fechas
    $sql_ocupadas = "SELECT DISTINCT rh.habitacion_id,
                     r.id as reservacion_id,
                     r.fecha_entrada,
                     r.fecha_salida,
                     r.estado as estado_reservacion,
                     h.nombre_completo as huesped_nombre
                     FROM reservacion_habitaciones rh
                     INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                     INNER JOIN huespedes h ON r.huesped_id = h.id
                     WHERE r.estado IN ('confirmada', 'checked_in')
                     AND ? < r.fecha_salida
                     AND ? > r.fecha_entrada";
    
    $stmt = $db->query($sql_ocupadas, [$fecha_entrada, $fecha_salida]);
    $ocupadas = [];
    while ($row = $stmt->fetch()) {
        $ocupadas[$row['habitacion_id']] = $row;
    }
    
    // 3. Obtener información detallada de ocupación por noche
    $sql_noches = "SELECT rh.habitacion_id,
                   r.fecha_entrada, r.fecha_salida, r.estado as estado_reservacion,
                   h.nombre_completo as huesped_nombre
                   FROM reservacion_habitaciones rh
                   INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                   INNER JOIN huespedes h ON r.huesped_id = h.id
                   WHERE r.estado IN ('confirmada', 'checked_in')
                   AND ? < r.fecha_salida
                   AND ? > r.fecha_entrada
                   ORDER BY r.fecha_entrada";
    
    $stmt = $db->query($sql_noches, [$fecha_entrada, $fecha_salida]);
    $detalle_noches = [];
    while ($row = $stmt->fetch()) {
        if (!isset($detalle_noches[$row['habitacion_id']])) {
            $detalle_noches[$row['habitacion_id']] = [];
        }
        
        $inicio = max(strtotime($fecha_entrada), strtotime($row['fecha_entrada']));
        $fin = min(strtotime($fecha_salida), strtotime($row['fecha_salida']));
        
        $noche = 1;
        for ($d = strtotime($fecha_entrada); $d < strtotime($fecha_salida); $d += 86400) {
            if ($d >= $inicio && $d < $fin) {
                $detalle_noches[$row['habitacion_id']][] = [
                    'noche' => $noche,
                    'fecha' => date('Y-m-d', $d),
                    'fecha_formateada' => date('d/m/Y', $d)
                ];
            }
            $noche++;
        }
    }
    
    // 4. NUEVO: Obtener mantenimientos que conflicten con las fechas
    $sql_mant = "SELECT DISTINCT m.habitacion_id, m.tipo_mantenimiento, m.motivo, 
                        m.fecha_programada, m.fecha_programada_fin, m.estado as estado_mantenimiento,
                        m.programado, m.prioridad
                 FROM mantenimientos_habitaciones m
                 WHERE m.estado IN ('programado', 'en_proceso')
                 AND (
                     (m.programado = 1 AND m.fecha_programada IS NOT NULL AND (
                         (m.fecha_programada < ? AND (m.fecha_programada_fin IS NULL OR m.fecha_programada_fin > ?))
                         OR (m.fecha_programada >= ? AND m.fecha_programada < ?)
                         OR (m.fecha_programada_fin IS NOT NULL AND m.fecha_programada_fin > ? AND m.fecha_programada < ?)
                     ))
                     OR (m.estado = 'en_proceso' AND m.programado = 0)
                 )";
    
    $params_mant = [
        $fecha_salida, $fecha_entrada,
        $fecha_entrada, $fecha_salida,
        $fecha_entrada, $fecha_salida
    ];
    
    $stmt_mant = $db->query($sql_mant, $params_mant);
    $mantenimientos = [];
    while ($row = $stmt_mant->fetch()) {
        $mantenimientos[$row['habitacion_id']] = $row;
    }
    
    // 5. También marcar habitaciones que están en mantenimiento por estado actual
    // (mantenimiento inmediato, no programado)
    
    // 6. Construir respuesta
    $resultado = [];
    foreach ($habitaciones as $hab) {
        $hab_id = $hab['id'];
        $esta_ocupada = isset($ocupadas[$hab_id]);
        $esta_en_mantenimiento = ($hab['estado'] === 'mantenimiento') || isset($mantenimientos[$hab_id]);
        
        $hab_data = [
            'id' => $hab['id'],
            'numero' => $hab['numero'],
            'tipo' => $hab['tipo'],
            'piso' => $hab['piso'],
            'precio_base' => $hab['precio_base'],
            'caracteristicas' => $hab['caracteristicas'],
            'estado' => $hab['estado'],
            'ocupada' => $esta_ocupada,
            'en_mantenimiento' => $esta_en_mantenimiento
        ];
        
        // Agregar info de ocupación si aplica
        if ($esta_ocupada && isset($ocupadas[$hab_id])) {
            $info = $ocupadas[$hab_id];
            $hab_data['info_ocupacion'] = [
                'huesped_nombre' => $info['huesped_nombre'],
                'estado' => $info['estado_reservacion'],
                'fecha_entrada' => $info['fecha_entrada'],
                'fecha_salida' => $info['fecha_salida'],
                'noches_ocupadas' => isset($detalle_noches[$hab_id]) ? count($detalle_noches[$hab_id]) : 0,
                'fechas_ocupadas' => $detalle_noches[$hab_id] ?? []
            ];
        }
        
        // Agregar info de mantenimiento si aplica
        if ($esta_en_mantenimiento) {
            if (isset($mantenimientos[$hab_id])) {
                $mant = $mantenimientos[$hab_id];
                $hab_data['info_mantenimiento'] = [
                    'tipo' => $mant['tipo_mantenimiento'],
                    'motivo' => $mant['motivo'],
                    'programado' => (bool)$mant['programado'],
                    'fecha_programada' => $mant['fecha_programada'] ? date('d/m/Y', strtotime($mant['fecha_programada'])) : null,
                    'fecha_programada_fin' => $mant['fecha_programada_fin'] ? date('d/m/Y', strtotime($mant['fecha_programada_fin'])) : null,
                    'prioridad' => $mant['prioridad'],
                    'estado' => $mant['estado_mantenimiento']
                ];
            } else {
                // Mantenimiento actual (estado de habitación = mantenimiento)
                $hab_data['info_mantenimiento'] = [
                    'tipo' => 'En proceso',
                    'motivo' => 'Habitación en mantenimiento',
                    'programado' => false,
                    'fecha_programada' => null,
                    'fecha_programada_fin' => null,
                    'prioridad' => null,
                    'estado' => 'en_proceso'
                ];
            }
        }
        
        $resultado[] = $hab_data;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $resultado,
        'meta' => [
            'total' => count($resultado),
            'ocupadas' => count($ocupadas),
            'en_mantenimiento' => count(array_filter($resultado, fn($h) => $h['en_mantenimiento'])),
            'disponibles' => count(array_filter($resultado, fn($h) => !$h['ocupada'] && !$h['en_mantenimiento'])),
            'fecha_entrada' => $fecha_entrada,
            'fecha_salida' => $fecha_salida
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en API todas-con-ocupacion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener habitaciones: ' . $e->getMessage()
    ]);
}
