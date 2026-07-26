<?php
/**
 * Cron matutino: genera el resumen inteligente del dia anterior y envia el push a
 * direccion, para TODOS los hoteles con los bloques ia_ejecutiva y
 * notificaciones contratados. Idempotente (cache + dedupe por dia): puede
 * correr varias veces sin duplicar ni re-pagar tokens.
 *
 * Programar en el host (ej. 7:00 AM):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_resumen_ia.php
 *
 * Nota: aunque el cron no corra, la regla del dashboard genera el briefing
 * al primer inicio de sesion del dia (este cron solo lo adelanta al push).
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
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../app/services/IaEjecutivaService.php';
require_once __DIR__ . '/../app/services/NotificacionService.php';

$db = Database::getInstance();

// Hoteles activos con el bloque ia_ejecutiva contratado. En CLI hotel_has_module
// no existe, asi que el filtro va aqui en SQL.
// GOTCHA (2026-07-25): 'notificaciones' paso al paquete base (es_core=1) y ya no
// necesita fila en hotel_modulos, asi que el EXISTS que lo exigia dejaba fuera a
// TODOS los hoteles. Un modulo base se comprueba con es_core, no con hotel_modulos.
$stmt = $db->query(
    "SELECT h.id, h.nombre
     FROM hoteles h
     WHERE h.activo = 1
       AND EXISTS (SELECT 1 FROM hotel_modulos hm INNER JOIN modulos m ON m.id = hm.modulo_id
                   WHERE hm.hotel_id = h.id AND hm.activo = 1 AND m.clave = 'ia_ejecutiva' AND m.activo_global = 1)
       AND EXISTS (SELECT 1 FROM modulos m2
                   WHERE m2.clave = 'notificaciones' AND m2.activo_global = 1
                     AND (m2.es_core = 1
                          OR EXISTS (SELECT 1 FROM hotel_modulos hm2
                                     WHERE hm2.hotel_id = h.id AND hm2.modulo_id = m2.id AND hm2.activo = 1)))
     ORDER BY h.id"
);
$hoteles = $stmt ? $stmt->fetchAll() : [];

$fecha = date('Y-m-d', strtotime('-1 day'));
echo 'Cron resumen inteligente — briefing del ' . $fecha . ' — ' . count($hoteles) . " hotel(es) elegible(s)\n";

$servicio = new IaEjecutivaService($db);
if (!$servicio->configurado()) {
    echo "ABORT: ANTHROPIC_API_KEY no configurada.\n";
    exit(1);
}

$ok = 0;
$fallas = 0;

foreach ($hoteles as $hotel) {
    try {
        $notifId = $servicio->notificarResumenMatutino((int) $hotel['id'], $fecha);
        if ($notifId !== null) {
            echo "  OK    {$hotel['nombre']} (hotel {$hotel['id']}) -> notificacion #{$notifId}\n";
            $ok++;
        } else {
            echo "  SKIP  {$hotel['nombre']} (hotel {$hotel['id']}) -> sin notificacion (ver log)\n";
            $fallas++;
        }
    } catch (Throwable $e) {
        echo "  FAIL  {$hotel['nombre']} (hotel {$hotel['id']}) -> " . $e->getMessage() . "\n";
        error_log('Cron resumen inteligente: fallo en hotel ' . $hotel['id'] . ': ' . $e->getMessage());
        $fallas++;
    }
}

echo "Terminado: {$ok} enviado(s), {$fallas} con problema.\n";
exit($fallas > 0 && $ok === 0 ? 1 : 0);
