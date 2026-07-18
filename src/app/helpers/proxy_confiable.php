<?php
/**
 * Proxy confiable — resolución de IP real y esquema HTTPS (Fase 2, 2026-07-17).
 *
 * Detrás de un reverse proxy (Caddy en docker-compose.prod.yml) PHP ve la IP
 * del proxy en REMOTE_ADDR y HTTPS apagado, porque el proxy habla HTTP plano
 * con app:80. Consecuencias sin este helper: cookies sin Secure, sin HSTS
 * desde PHP y rate-limit/auditoría registrando la IP del proxy para todos.
 *
 * Regla de seguridad: los encabezados X-Forwarded-* SOLO se honran cuando la
 * conexión entra desde una IP listada en TRUSTED_PROXIES (.env, IPs o CIDRs
 * separados por coma). Con TRUSTED_PROXIES vacío (default) no se confía en
 * ningún encabezado y el comportamiento es idéntico al histórico.
 *
 * La IP real se toma del extremo DERECHO de X-Forwarded-For saltando los
 * saltos confiables (un cliente puede mandar X-Forwarded-For falso, pero el
 * proxy confiable siempre APPENDEA la IP real al final, así que lo falsificado
 * queda a la izquierda y se ignora).
 */

/**
 * Parsear TRUSTED_PROXIES (o el valor dado) a lista de IP/CIDR. PURO.
 */
function ms_proxy_lista_confiable(?string $valor = null): array
{
    $crudo = $valor ?? (getenv('TRUSTED_PROXIES') ?: '');
    $lista = [];
    foreach (explode(',', $crudo) as $item) {
        $item = trim($item);
        if ($item !== '') {
            $lista[] = $item;
        }
    }
    return $lista;
}

/**
 * ¿La IP cae dentro de la entrada (IP exacta o CIDR v4/v6)? PURO.
 */
function ms_ip_en_cidr(string $ip, string $entrada): bool
{
    $ipBin = @inet_pton($ip);
    if ($ipBin === false) {
        return false;
    }

    if (strpos($entrada, '/') === false) {
        $entradaBin = @inet_pton($entrada);
        return $entradaBin !== false && $entradaBin === $ipBin;
    }

    [$red, $bits] = explode('/', $entrada, 2);
    $redBin = @inet_pton($red);
    if ($redBin === false || !is_numeric($bits)) {
        return false;
    }
    $bits = (int) $bits;
    if (strlen($redBin) !== strlen($ipBin)) {
        return false; // familias distintas (v4 vs v6)
    }
    $maxBits = strlen($redBin) * 8;
    if ($bits < 0 || $bits > $maxBits) {
        return false;
    }

    $bytesCompletos = intdiv($bits, 8);
    $bitsRestantes = $bits % 8;
    if ($bytesCompletos > 0 && strncmp($ipBin, $redBin, $bytesCompletos) !== 0) {
        return false;
    }
    if ($bitsRestantes === 0) {
        return true;
    }
    $mascara = ~(0xFF >> $bitsRestantes) & 0xFF;
    return ((ord($ipBin[$bytesCompletos]) ^ ord($redBin[$bytesCompletos])) & $mascara) === 0;
}

/**
 * ¿La IP está en la lista de proxies confiables? PURO.
 */
function ms_proxy_es_confiable(string $ip, array $confiables): bool
{
    foreach ($confiables as $entrada) {
        if (ms_ip_en_cidr($ip, $entrada)) {
            return true;
        }
    }
    return false;
}

/**
 * Resolver IP real y esquema a partir de $_SERVER. PURO (no muta nada).
 *
 * Devuelve ['ip' => ?string, 'https' => ?bool]:
 *  - ip    = IP real del cliente, o null si no hay nada que cambiar.
 *  - https = true si el proxy confiable declara X-Forwarded-Proto https,
 *            null si no aplica (no pisar lo que ya viera PHP).
 */
function ms_proxy_resolver(array $server, array $confiables): array
{
    $sinCambio = ['ip' => null, 'https' => null];

    $remote = (string) ($server['REMOTE_ADDR'] ?? '');
    if ($remote === '' || $confiables === [] || !ms_proxy_es_confiable($remote, $confiables)) {
        return $sinCambio; // conexión directa o proxy no confiable: ignorar headers
    }

    $resultado = $sinCambio;

    // IP real: extremo derecho de X-Forwarded-For saltando saltos confiables.
    // Si TODA la cadena es confiable (ej. cliente dentro de la misma red que
    // el proxy), el cliente es el salto valido mas LEJANO (izquierdo).
    $xff = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($xff !== '') {
        $saltos = array_map('trim', explode(',', $xff));
        $ultimaConfiable = null;
        for ($i = count($saltos) - 1; $i >= 0; $i--) {
            $candidata = $saltos[$i];
            if (filter_var($candidata, FILTER_VALIDATE_IP) === false) {
                // Basura en la cadena: no confiar en lo que quede a la
                // izquierda, pero conservar el ultimo salto confiable valido.
                break;
            }
            if (!ms_proxy_es_confiable($candidata, $confiables)) {
                $resultado['ip'] = $candidata;
                break;
            }
            $ultimaConfiable = $candidata;
        }
        if ($resultado['ip'] === null && $ultimaConfiable !== null) {
            $resultado['ip'] = $ultimaConfiable;
        }
    }

    // Esquema: el proxy confiable declara cómo llegó el cliente.
    $proto = strtolower(trim((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($proto === 'https') {
        $resultado['https'] = true;
    }

    return $resultado;
}

/**
 * Aplicar la resolución sobre $_SERVER (se llama UNA vez en index.php, antes
 * de configurar cookies de sesión). Con TRUSTED_PROXIES vacío no hace nada.
 */
function ms_proxy_aplicar(): void
{
    $res = ms_proxy_resolver($_SERVER, ms_proxy_lista_confiable());
    if ($res['ip'] !== null) {
        $_SERVER['MS_PROXY_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SERVER['REMOTE_ADDR'] = $res['ip'];
    }
    if ($res['https'] === true) {
        $_SERVER['HTTPS'] = 'on';
    }
}
