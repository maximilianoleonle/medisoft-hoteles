<?php
/**
 * Cron de purga del centro documental: un documento "dado de baja" solo
 * cambiaba estado en BD, el archivo se quedaba en el servidor para siempre.
 * Ahora eliminado_en abre una ventana de retencion (Documento::RETENCION_
 * ELIMINADOS_DIAS, 30 dias) durante la cual el archivo sigue en disco y es
 * recuperable; pasada la ventana este cron lo purga de verdad (unlink fisico
 * + purgado_en), documento por documento en su propia transaccion.
 *
 * Programar en el host (recomendado 1 vez al dia):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_purga_documentos.php
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
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', $src . '/storage');
}

require_once $src . '/core/Database.php';
require_once $src . '/core/Model.php';
require_once $src . '/app/services/AuditService.php';
require_once $src . '/app/models/Documento.php';

echo '[' . date('Y-m-d H:i:s') . "] Purga de documentos dados de baja\n";

$documentoModel = new Documento();

if (!$documentoModel->tablasDisponibles()) {
    echo "  Tablas documentales no disponibles.\n";
    exit;
}

$resultado = $documentoModel->purgarElegibles();

$mb = round($resultado['bytes_liberados'] / 1048576, 2);
echo "  Candidatos: {$resultado['candidatos']}, purgados: {$resultado['purgados']}, espacio liberado: {$mb} MB\n";

foreach ($resultado['errores'] as $error) {
    echo "  ERROR: {$error}\n";
}

echo '[' . date('Y-m-d H:i:s') . "] Listo.\n";
exit(0);
