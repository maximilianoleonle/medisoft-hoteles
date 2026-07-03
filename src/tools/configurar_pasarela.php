<?php
/**
 * Herramienta CLI: configurar credenciales de pasarela de un hotel.
 * Guarda los secrets CIFRADOS via MotorPasarelaService (AES-256-GCM).
 *
 * Uso (dentro del contenedor app):
 *   php tools/configurar_pasarela.php <hotel_slug> <proveedor> <public_key> <secret_key|-> <webhook_secret|-> <test|live>
 *   ('-' conserva el valor ya guardado)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Cargar .env (mismo mecanismo que index.php; el env del contenedor puede estar viejo).
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
require_once __DIR__ . '/../app/services/MotorPasarelaService.php';

[$script, $slug, $proveedor, $publicKey, $secretKey, $webhookSecret, $modo] = array_pad($argv, 7, '');

if ($slug === '' || $proveedor === '') {
    echo "Uso: php tools/configurar_pasarela.php <hotel_slug> <stripe|mercadopago> <pk> <sk|-> <whsec|-> <test|live>\n";
    exit(1);
}

$db = Database::getInstance();
$stmt = $db->query("SELECT id, nombre FROM hoteles WHERE slug = ? LIMIT 1", [$slug]);
$hotel = $stmt ? $stmt->fetch() : null;
if (!$hotel) {
    echo "ERROR: hotel '{$slug}' no encontrado\n";
    exit(1);
}

$servicio = new MotorPasarelaService($db);
$ok = $servicio->guardarCredenciales(
    (int) $hotel['id'],
    $proveedor,
    $publicKey === '-' ? '' : $publicKey,
    $secretKey === '-' ? null : $secretKey,
    $webhookSecret === '-' ? null : $webhookSecret,
    $modo ?: 'test',
    true
);

if (!$ok) {
    echo "ERROR: no se pudieron guardar las credenciales\n";
    exit(1);
}

// Verificacion: descifrar lo guardado sin imprimir el secreto completo.
$cred = $servicio->credenciales((int) $hotel['id']);
$mask = function ($v) {
    $v = (string) $v;
    return $v === '' ? '(vacio)' : substr($v, 0, 10) . '...' . substr($v, -4) . ' (' . strlen($v) . ' chars)';
};

echo "OK: credenciales guardadas para {$hotel['nombre']} (id {$hotel['id']})\n";
echo "  proveedor:      " . ($cred['proveedor'] ?? '?') . " / modo " . ($cred['modo'] ?? '?') . "\n";
echo "  public_key:     " . $mask($cred['public_key'] ?? '') . "\n";
echo "  secret_key:     " . $mask($cred['secret_key'] ?? '') . " [descifrada OK]\n";
echo "  webhook_secret: " . $mask($cred['webhook_secret'] ?? '') . "\n";
exit(0);
