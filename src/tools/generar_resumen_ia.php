<?php
/**
 * Herramienta CLI: generar el resumen gerencial IA de un hotel.
 * Sirve para pruebas y como base del envio matutino programado (V2).
 *
 * Uso (dentro del contenedor app):
 *   php tools/generar_resumen_ia.php <hotel_slug> [fecha Y-m-d] [--regenerar]
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Cargar .env como index.php (por si el env del contenedor esta desactualizado).
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
require_once __DIR__ . '/../app/services/IaEjecutivaService.php';

$slug = $argv[1] ?? '';
$fecha = $argv[2] ?? date('Y-m-d');
$regenerar = in_array('--regenerar', $argv, true);

if ($slug === '') {
    echo "Uso: php tools/generar_resumen_ia.php <hotel_slug> [fecha Y-m-d] [--regenerar]\n";
    exit(1);
}

$db = Database::getInstance();
$stmt = $db->query("SELECT id, nombre FROM hoteles WHERE slug = ? LIMIT 1", [$slug]);
$hotel = $stmt ? $stmt->fetch() : null;
if (!$hotel) {
    echo "ERROR: hotel '{$slug}' no encontrado\n";
    exit(1);
}

echo "Generando resumen IA para {$hotel['nombre']} — {$fecha}" . ($regenerar ? ' (regenerando)' : '') . "...\n\n";

$inicio = microtime(true);
$servicio = new IaEjecutivaService($db);
$resultado = $servicio->resumenGerencialDiario((int) $hotel['id'], $fecha, $regenerar);
$segundos = round(microtime(true) - $inicio, 1);

if (empty($resultado['success'])) {
    echo "ERROR: " . ($resultado['message'] ?? 'desconocido') . "\n";
    exit(1);
}

echo str_repeat('=', 64) . "\n";
echo $resultado['resumen'] . "\n";
echo str_repeat('=', 64) . "\n";
echo ($resultado['desde_cache'] ?? false ? "(desde cache)" : "(generado nuevo en {$segundos}s)") . "\n";
exit(0);
