<?php
/**
 * Proactividad del Guardian (vigilancia financiera) — CLI operacional.
 *
 * Dispara las dos alertas del Guardian por la cadena push existente, SOLO a
 * usuarios con el permiso guardian.view:
 *
 *   --scan    Alerta inmediata: hallazgos de severidad ALTA nuevos y sin
 *             notificar. Pensado para cron frecuente (p.ej. cada hora).
 *   --digest  Resumen semanal (lunes): la semana pasada completa. El verde
 *             tambien se comunica ("Semana limpia...").
 *   --dry-run No envia push ni marca notificado; solo muestra que haria.
 *
 * Uso:
 *   php src/tools/saas/guardian_notificar.php --scan            (todos los hoteles con el modulo)
 *   php src/tools/saas/guardian_notificar.php 3 --scan          (solo hotel 3)
 *   php src/tools/saas/guardian_notificar.php --digest
 *   php src/tools/saas/guardian_notificar.php 3 --digest --dry-run
 *
 * Cron sugerido (contenedor):
 *   0 * * * *  php /var/www/html/tools/saas/guardian_notificar.php --scan
 *   0 8 * * 1  php /var/www/html/tools/saas/guardian_notificar.php --digest
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

// Cargar .env de la raiz del repo (mismo patron que probar_vigilancia_financiera).
$envPath = dirname(__DIR__, 3) . '/.env';
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

$hotelId = 0;
$modo = '';
$dryRun = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--scan' || $arg === '--digest') {
        $modo = substr($arg, 2);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (ctype_digit((string) $arg)) {
        $hotelId = (int) $arg;
    }
}

if ($modo === '') {
    echo "Falta el modo: --scan (alerta inmediata) o --digest (resumen semanal).\n";
    exit(1);
}

if (!defined('APP_PATH')) {
    define('APP_PATH', dirname(__DIR__, 2) . '/app');
}

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Model.php';
require_once dirname(__DIR__, 2) . '/app/helpers/hotel_config.php';
require_once dirname(__DIR__, 2) . '/app/services/GuardianAlertaService.php';

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Hoteles destino: el indicado o todos los activos con el modulo del bloque
// (gating temporal en ia_ejecutiva, igual que la vista).
if ($hotelId > 0) {
    $hoteles = [$hotelId];
} else {
    $stmt = $pdo->prepare(
        "SELECT DISTINCT h.id
         FROM hoteles h
         INNER JOIN hotel_modulos hm ON hm.hotel_id = h.id AND hm.activo = 1
         INNER JOIN modulos m ON m.id = hm.modulo_id AND m.clave = 'ia_ejecutiva'
         WHERE h.activo = 1
         ORDER BY h.id"
    );
    $stmt->execute();
    $hoteles = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

if (empty($hoteles)) {
    echo "Sin hoteles con el modulo activo. Nada que hacer.\n";
    exit(0);
}

echo '== Guardian ' . ($modo === 'scan' ? 'alerta inmediata' : 'digest semanal')
    . ($dryRun ? ' (dry-run)' : '')
    . ' — ' . count($hoteles) . " hotel(es) ==\n";

$servicio = new GuardianAlertaService($db);
$fallas = 0;

foreach ($hoteles as $hid) {
    try {
        $r = $modo === 'scan'
            ? $servicio->evaluarYNotificar($hid, $dryRun)
            : $servicio->digestSemanal($hid, $dryRun);

        if (empty($r['success'])) {
            $fallas++;
            echo "[FALLA] hotel {$hid}: " . ($r['mensaje'] ?? 'sin mensaje') . "\n";
            continue;
        }

        echo "[OK]   hotel {$hid}: " . ($r['mensaje'] ?? '')
            . ' (push: ' . (int) ($r['enviados'] ?? 0) . ' enviados, '
            . (int) ($r['fallidos'] ?? 0) . ' fallidos, '
            . (int) ($r['destinatarios'] ?? 0) . " destinatarios)\n";
    } catch (Throwable $e) {
        $fallas++;
        echo "[FALLA] hotel {$hid}: " . $e->getMessage() . "\n";
    }
}

echo $fallas === 0 ? "== Todo OK ==\n" : "== {$fallas} falla(s) ==\n";
exit($fallas > 0 ? 1 : 0);
