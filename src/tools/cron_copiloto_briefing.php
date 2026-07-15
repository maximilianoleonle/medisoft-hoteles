<?php
/**
 * Cron del briefing matutino del Copiloto (bloque copiloto_briefing).
 *
 * Recorre los hoteles activos con el bloque contratado y envia el briefing
 * del dia por push cuando ya paso la hora configurada del hotel
 * (copiloto.briefing_hora). Es idempotente por dia: puede programarse cada
 * 10-15 minutos y cada hotel recibe UN briefing diario.
 *
 * Programar en el host (ej. cada 15 min entre 5:00 y 12:00):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/cron_copiloto_briefing.php
 *
 * Uso manual / pruebas:
 *   php tools/cron_copiloto_briefing.php                  # todos, respeta hora
 *   php tools/cron_copiloto_briefing.php --hotel=1        # solo ese hotel
 *   php tools/cron_copiloto_briefing.php --hotel=1 --force  # ignora la hora (no el resto de candados)
 *   php tools/cron_copiloto_briefing.php --hotel=1 --preview # imprime el briefing sin enviar nada
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Cargar .env como index.php (produccion: /var/www/.env; local: raiz del repo).
foreach (['/var/www/.env', dirname(__DIR__, 2) . '/.env'] as $envPath) {
    if (!is_readable($envPath)) {
        continue;
    }
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
    break;
}

// Constantes de ruta como public_html/index.php: la cadena push las necesita
// (PwaPushService carga APP_PATH/config/pwa.php al construirse).
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Model.php';
require_once APP_PATH . '/helpers/cache.php';
require_once APP_PATH . '/helpers/modulos.php';
// hotel_has_module consulta via el modelo Modulo; en CLI hay que cargarlo.
require_once APP_PATH . '/models/Modulo.php';
require_once APP_PATH . '/services/CopilotoBriefingService.php';

$opciones = getopt('', ['hotel::', 'force', 'preview']);
$soloHotel = isset($opciones['hotel']) ? (int) $opciones['hotel'] : 0;
$forzarHora = array_key_exists('force', $opciones);
$soloPreview = array_key_exists('preview', $opciones);

$db = Database::getInstance();
$servicio = new CopilotoBriefingService($db);

$hoteles = $servicio->hotelesConBloque();
if ($soloHotel > 0) {
    $hoteles = array_values(array_filter($hoteles, static function ($h) use ($soloHotel) {
        return (int) $h['id'] === $soloHotel;
    }));
}

echo 'Cron briefing del Copiloto — ' . date('Y-m-d H:i') . ' — ' . count($hoteles) . " hotel(es) con el bloque\n";
if ($soloHotel > 0 && empty($hoteles)) {
    echo "  (el hotel {$soloHotel} no tiene el bloque copiloto_briefing activo)\n";
}

$enviados = 0;
$saltados = 0;
$fallas = 0;

foreach ($hoteles as $hotel) {
    $hotelId = (int) $hotel['id'];
    try {
        if ($soloPreview) {
            $briefing = $servicio->componer($hotelId);
            echo "  PREVIEW {$hotel['nombre']} (hotel {$hotelId}) — hora config " . $servicio->horaConfigurada($hotelId)
                . ", hora hotel " . $servicio->horaLocalHotel($hotelId) . "\n";
            echo "    {$briefing['titulo']}\n";
            foreach (explode("\n", $briefing['mensaje']) as $linea) {
                echo "    {$linea}\n";
            }
            continue;
        }

        $r = $servicio->enviarSiCorresponde($hotelId, $forzarHora);
        if ($r['enviado']) {
            echo "  OK    {$hotel['nombre']} (hotel {$hotelId}) -> {$r['motivo']}\n";
            $enviados++;
        } else {
            echo "  SKIP  {$hotel['nombre']} (hotel {$hotelId}) -> {$r['motivo']}\n";
            $saltados++;
        }
    } catch (Throwable $e) {
        echo "  FAIL  {$hotel['nombre']} (hotel {$hotelId}) -> " . $e->getMessage() . "\n";
        error_log('Cron briefing copiloto: fallo en hotel ' . $hotelId . ': ' . $e->getMessage());
        $fallas++;
    }
}

if (!$soloPreview) {
    echo "Terminado: {$enviados} enviado(s), {$saltados} saltado(s), {$fallas} con problema.\n";
}
exit($fallas > 0 && $enviados === 0 && $saltados === 0 ? 1 : 0);
