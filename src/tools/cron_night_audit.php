<?php
/**
 * Cron de night audit: cierre nocturno de TODOS los hoteles con el bloque
 * night_audit contratado. Idempotente por (hotel, fecha): correrlo dos veces
 * no duplica cierres. Solo detecta y avisa; jamas modifica la operacion.
 *
 * Programar en el host (recomendado de madrugada, ej. 4:00; cierra el dia de AYER):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_night_audit.php
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

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/services/NightAuditService.php';

$fecha = $argv[1] ?? date('Y-m-d', strtotime('-1 day'));

echo '[' . date('Y-m-d H:i:s') . "] Night audit: cierre del dia {$fecha}\n";

$db = Database::getInstance();

// Hoteles activos con el bloque contratado (en CLI no existe hotel_has_module).
$stmt = $db->query(
    "SELECT h.id, h.nombre
     FROM hoteles h
     INNER JOIN hotel_modulos hm ON hm.hotel_id = h.id AND hm.activo = 1
     INNER JOIN modulos m ON m.id = hm.modulo_id AND m.clave = 'night_audit'
     WHERE h.activo = 1
     ORDER BY h.id"
);
$hoteles = $stmt ? $stmt->fetchAll() : [];

if (empty($hoteles)) {
    echo "  Sin hoteles con el bloque night_audit activo.\n";
    exit;
}

$servicio = new NightAuditService($db);

foreach ($hoteles as $hotel) {
    $hotelId = (int) $hotel['id'];
    $resultado = $servicio->ejecutarCierre($hotelId, $fecha);

    if (empty($resultado['success'])) {
        echo "  - {$hotel['nombre']}: ERROR ({$resultado['message']})\n";
        continue;
    }

    $cierre = $resultado['cierre'];
    $etiqueta = $resultado['creado'] ? 'cierre generado' : 'ya existia';
    echo "  - {$hotel['nombre']}: {$etiqueta} · no-shows {$cierre['no_shows']} · checkouts vencidos {$cierre['checkouts_vencidos']} · cortes abiertos {$cierre['cortes_abiertos']}\n";

    $correo = $servicio->enviarCorreo($hotelId, $cierre, (string) $hotel['nombre']);
    echo '      correo: ' . (!empty($correo['ok']) ? 'enviado' : 'no enviado (' . ($correo['error'] ?? '') . ')') . "\n";
}

echo '[' . date('Y-m-d H:i:s') . "] Listo.\n";
