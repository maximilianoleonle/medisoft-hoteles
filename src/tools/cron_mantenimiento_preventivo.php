<?php
/**
 * Cron de mantenimiento preventivo (bloque mantenimiento_plus): recorre los
 * hoteles con el bloque contratado y genera los mantenimientos preventivos
 * de activos vencidos (proximo_servicio <= hoy), con su tarea vinculada y
 * aviso push. Idempotente: un activo con mantenimiento abierto no genera otro.
 *
 * Tambien manda el recordatorio anticipado "vence en N dias" (N configurable
 * por hotel via hotel_configuracion clave mantenimiento.aviso_anticipacion_dias,
 * default 7) con dedupe diario por activo.
 *
 * Programar en el host (recomendado 1 vez al dia por la manana):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_mantenimiento_preventivo.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Cargar .env como index.php (el env del contenedor puede estar desactualizado).
$envPath = '/var/www/.env';
if (is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linea) {
        $linea = trim($linea);
        if ($linea === '' || strpos($linea, '#') === 0 || strpos($linea, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        if (trim($k) !== '' && getenv(trim($k)) === false) {
            putenv(trim($k) . '=' . trim($v));
        }
    }
}

// En CLI no hay request: helpers como base_url() esperan HTTP_HOST.
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

$src = dirname(__DIR__);

if (!defined('APP_PATH')) {
    define('APP_PATH', $src . '/app');
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', $src . '/core');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', $src . '/public_html');
}

require_once $src . '/core/Database.php';
require_once $src . '/core/Model.php';
require_once $src . '/app/helpers/cache.php';
require_once $src . '/app/helpers/functions.php';
require_once $src . '/app/helpers/hotel_config.php';
require_once $src . '/app/helpers/modulos.php';
require_once $src . '/app/models/Modulo.php';
require_once $src . '/app/models/Notificacion.php';
require_once $src . '/app/services/MantenimientoPreventivoService.php';

echo '[' . date('Y-m-d H:i:s') . "] Mantenimiento preventivo: generacion y recordatorios\n";

$db = Database::getInstance();

// Hoteles activos con el bloque contratado (en CLI no hay contexto de hotel).
$stmt = $db->query(
    "SELECT h.id, h.nombre
     FROM hoteles h
     INNER JOIN hotel_modulos hm ON hm.hotel_id = h.id AND hm.activo = 1
     INNER JOIN modulos m ON m.id = hm.modulo_id AND m.clave = 'mantenimiento_plus'
     WHERE h.activo = 1
     ORDER BY h.id"
);
$hoteles = $stmt ? $stmt->fetchAll() : [];

if (empty($hoteles)) {
    echo "  Sin hoteles con el bloque mantenimiento_plus activo.\n";
    exit;
}

$servicio = new MantenimientoPreventivoService($db);
$totalGenerados = 0;
$totalRecordatorios = 0;

foreach ($hoteles as $hotel) {
    $hotelId = (int)$hotel['id'];
    echo "  Hotel #{$hotelId} {$hotel['nombre']}: ";

    // 1) Generar preventivos vencidos.
    $resumen = $servicio->generarParaHotel($hotelId);
    $totalGenerados += $resumen['generados'];
    echo $resumen['generados'] . ' generados, ' . $resumen['omitidos'] . ' omitidos';

    foreach ($resumen['errores'] as $error) {
        echo "\n    ERROR: {$error}";
    }

    // 2) Recordatorio anticipado "vence en N dias" (default 7, configurable).
    $anticipacion = 7;
    if (function_exists('hotel_config_get')) {
        $anticipacion = max(1, min(60, (int)hotel_config_get('mantenimiento.aviso_anticipacion_dias', 7, $hotelId)));
    }

    $stmtProximos = $db->query(
        "SELECT a.id, a.nombre, a.ubicacion, a.proximo_servicio,
                h.numero AS habitacion_numero,
                DATEDIFF(a.proximo_servicio, CURDATE()) AS dias_restantes
         FROM activos_hotel a
         LEFT JOIN habitaciones h
            ON h.id = a.habitacion_id
           AND h.hotel_id = a.hotel_id
         WHERE a.hotel_id = ?
           AND a.activo = 1
           AND a.proximo_servicio IS NOT NULL
           AND a.proximo_servicio > CURDATE()
           AND a.proximo_servicio <= DATE_ADD(CURDATE(), INTERVAL ? DAY)",
        [$hotelId, $anticipacion]
    );

    $recordatoriosHotel = 0;
    foreach (($stmtProximos ? $stmtProximos->fetchAll() : []) as $activo) {
        $dias = (int)($activo['dias_restantes'] ?? 0);
        $ubicacion = trim((string)($activo['habitacion_numero'] ?? '')) !== ''
            ? 'Hab. ' . $activo['habitacion_numero']
            : (trim((string)($activo['ubicacion'] ?? '')) ?: 'Instalaciones generales');

        $id = NotificacionService::crear([
            'hotel_id' => $hotelId,
            'modulo' => 'habitaciones',
            'tipo' => 'mantenimiento_preventivo_proximo',
            'severidad' => 'info',
            'titulo' => mb_substr((string)$activo['nombre'] . ' vence su servicio en ' . $dias . ' dia' . ($dias === 1 ? '' : 's'), 0, 150),
            'mensaje' => mb_substr($ubicacion . '. Programa el servicio preventivo antes del ' . substr((string)$activo['proximo_servicio'], 0, 10) . '.', 0, 500),
            'url' => 'mantenimientos/activos',
            'rol_destino' => 'gerente,mantenimiento',
            'dedupe_key' => 'mantenimiento_plus.recordatorio.' . (int)$activo['id'] . '.' . date('Ymd'),
            'creada_por' => null,
        ]);

        if ($id) {
            $recordatoriosHotel++;
        }
    }

    $totalRecordatorios += $recordatoriosHotel;
    echo ", {$recordatoriosHotel} recordatorios\n";
}

echo '[' . date('Y-m-d H:i:s') . "] Listo: {$totalGenerados} preventivos generados, {$totalRecordatorios} recordatorios.\n";
exit(0);
