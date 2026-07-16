<?php
/**
 * Diagnostico de la llave de IA (ANTHROPIC_API_KEY) para todos los bloques
 * que dependen de ella: copiloto (fallback IA), copiloto_ia (resenas/
 * analisis/tarifa), ia_ejecutiva y vigilancia_financiera nivel 2.
 *
 * Revisa la CADENA completa sin exponer el secreto:
 *  1. ¿La variable existe en el entorno del proceso (como la ven los servicios)?
 *  2. ¿El valor esta limpio? (comillas, espacios, retornos de carro de un
 *     .env editado en Windows, prefijo del tipo de llave correcto)
 *  3. ¿Anthropic la acepta? Ping minimo a /v1/messages con claude-haiku-4-5
 *     (el modelo mas barato: si la llave es valida el costo es despreciable).
 *
 * Uso (dentro del contenedor, mismo entorno que la app):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/diagnosticar_ia.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit;
}

// Cargar .env como los demas crons (produccion: /var/www/.env; local: raiz del repo).
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

echo "Diagnostico de la llave de IA — " . date('Y-m-d H:i') . "\n\n";

// 1) Presencia.
$cruda = getenv('ANTHROPIC_API_KEY');
if ($cruda === false || trim((string) $cruda) === '') {
    echo "[FALLA] ANTHROPIC_API_KEY no esta definida en el entorno.\n";
    echo "        Agregala al .env (raiz del repo en local, /var/www/.env en produccion)\n";
    echo "        y reinicia el contenedor: docker compose restart app\n";
    exit(1);
}
$cruda = (string) $cruda;
$llave = trim($cruda);

// 2) Higiene del valor (sin imprimir el secreto).
$avisos = [];
if ($cruda !== $llave) {
    $avisos[] = 'el valor trae espacios o saltos alrededor (los servicios hacen trim(), pero conviene limpiarlo)';
}
if (strpos($llave, "\r") !== false) {
    $avisos[] = 'el valor contiene retorno de carro (\\r): el .env se edito con finales de linea Windows';
}
if (strpbrk($llave, "\"'") !== false) {
    $avisos[] = 'el valor trae comillas: en .env va SIN comillas (CLAVE=valor)';
}
if (strpos($llave, 'sk-ant-admin') === 0) {
    $avisos[] = 'es una llave ADMIN (sk-ant-admin...): esas no sirven para /v1/messages; se necesita una API key normal';
} elseif (strpos($llave, 'sk-ant-') !== 0) {
    $avisos[] = 'no tiene el prefijo esperado sk-ant-...: puede ser de otro proveedor o estar truncada';
}
echo 'Llave presente: ' . substr($llave, 0, 12) . '*** (' . strlen($llave) . " caracteres)\n";
foreach ($avisos as $a) {
    echo "[AVISO] {$a}\n";
}

// 3) Ping real al API (modelo mas barato; un 401 no consume tokens).
$payload = json_encode([
    'model' => 'claude-haiku-4-5-20251001',
    'max_tokens' => 8,
    'messages' => [['role' => 'user', 'content' => 'ping']],
]);
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . $llave,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$respuesta = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
$errorCurl = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    echo "\n[FALLA] No hubo conexion con api.anthropic.com: {$errorCurl}\n";
    echo "        Revisa la salida a internet del contenedor (DNS/proxy/firewall).\n";
    exit(1);
}

$json = json_decode((string) $respuesta, true);
$tipoError = (string) ($json['error']['type'] ?? '');
$msgError = (string) ($json['error']['message'] ?? '');

echo "\nPing al API: HTTP {$status}" . ($tipoError !== '' ? " ({$tipoError})" : '') . "\n";

if ($status === 200) {
    echo "[OK] La llave es valida. Copiloto IA, consejo de tarifa, resumen ejecutivo\n";
    echo "     y vigilancia financiera nivel 2 quedan operativos.\n";
    exit(0);
}
if ($status === 401) {
    echo "[FALLA] Anthropic rechaza la llave: {$msgError}\n";
    echo "        La llave fue revocada o nunca fue valida. Para arreglarlo:\n";
    echo "        1. Entra a https://console.anthropic.com/settings/keys con la cuenta de Medisoft.\n";
    echo "        2. Crea una API key nueva (workspace de produccion) y copiala completa.\n";
    echo "        3. Pegala en el .env como ANTHROPIC_API_KEY=sk-ant-... (sin comillas).\n";
    echo "        4. Reinicia: docker compose restart app  (en el VPS igual, tras editar /var/www/.env).\n";
    echo "        5. Vuelve a correr este diagnostico hasta ver [OK].\n";
    exit(1);
}
if ($status === 403) {
    echo "[FALLA] La llave existe pero no tiene permiso ({$msgError}). Revisa el workspace\n";
    echo "        y los limites de la cuenta en console.anthropic.com.\n";
    exit(1);
}
if ($status === 404 && $tipoError === 'not_found_error') {
    echo "[CASI] La llave AUTENTICA bien, pero el modelo de prueba no esta disponible\n";
    echo "       para esta cuenta ({$msgError}). Revisa el acceso a modelos del workspace.\n";
    exit(1);
}
if ($status === 429) {
    echo "[CASI] La llave autentica pero esta rate-limiteada ahora mismo. Reintenta en un minuto.\n";
    exit(1);
}
echo "[FALLA] Respuesta inesperada del API: {$msgError}\n";
exit(1);
