<?php
/**
 * Cron de cobros SaaS: ciclo automatico de facturacion mensual a hoteles.
 * Idempotente (correr diario es seguro):
 *  1. Genera los cobros del mes en curso que falten (monto congelado).
 *  2. Manda el correo de cobro inicial con link de pago Stripe.
 *  3. Manda UN recordatorio a cobros por vencer (<= 3 dias) o vencidos.
 *  4. Marca como 'vencido' lo pendiente con fecha limite pasada.
 *
 * Programar en el host (recomendado diario, ej. 8:00):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_saas_cobros.php
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
require_once __DIR__ . '/../app/services/SaasCobroService.php';

$periodo = $argv[1] ?? date('Y-m');

echo '[' . date('Y-m-d H:i:s') . "] Cobros SaaS: ciclo del periodo {$periodo}\n";

$servicio = new SaasCobroService();
$resultado = $servicio->cicloAutomatico($periodo);

foreach (($resultado['log'] ?? []) as $linea) {
    echo '  - ' . $linea . "\n";
}

echo '[' . date('Y-m-d H:i:s') . "] Listo.\n";
