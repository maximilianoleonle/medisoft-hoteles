<?php
/**
 * Cron de canales iCal: sincroniza los calendarios importados (Airbnb/Booking)
 * de TODOS los hoteles con el bloque canales_ical contratado. Idempotente:
 * upsert por (feed, uid) y libera eventos cancelados.
 *
 * Programar en el host (recomendado cada 30-60 min):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_ical_sync.php
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
require_once __DIR__ . '/../app/services/IcalCanalesService.php';

$db = Database::getInstance();

// Hoteles activos con el bloque contratado (en CLI no existe hotel_has_module).
$stmt = $db->query(
    "SELECT h.id, h.nombre
     FROM hoteles h
     WHERE h.activo = 1
       AND EXISTS (SELECT 1 FROM hotel_modulos hm INNER JOIN modulos m ON m.id = hm.modulo_id
                   WHERE hm.hotel_id = h.id AND hm.activo = 1 AND m.clave = 'canales_ical' AND m.activo_global = 1)
     ORDER BY h.id"
);
$hoteles = $stmt ? $stmt->fetchAll() : [];

echo 'Cron iCal — ' . count($hoteles) . " hotel(es) con canales_ical\n";

$servicio = new IcalCanalesService($db);
$ok = 0;
$fallas = 0;

foreach ($hoteles as $hotel) {
    $resultados = $servicio->sincronizarHotel((int) $hotel['id']);

    foreach ($resultados as $r) {
        if (!empty($r['success'])) {
            echo "  OK    {$hotel['nombre']} feed #{$r['feed_id']} -> {$r['eventos']} bloqueo(s) activo(s)\n";
            $ok++;
        } else {
            echo "  FAIL  {$hotel['nombre']} feed #{$r['feed_id']} -> {$r['error']}\n";
            $fallas++;
        }
    }
}

echo "Terminado: {$ok} feed(s) OK, {$fallas} con problema.\n";
exit($fallas > 0 && $ok === 0 ? 1 : 0);
