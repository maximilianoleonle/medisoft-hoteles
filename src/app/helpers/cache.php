<?php
/**
 * Cache de datos en memoria compartida (APCu) entre requests.
 *
 * Complementa (no reemplaza) los caches estaticos por request que ya tienen
 * los lookups calientes: el estatico evita consultas repetidas DENTRO de una
 * pagina; APCu evita repetirlas ENTRE paginas y ENTRE usuarios.
 *
 * Degradacion: si APCu no esta disponible (CLI con apc.enable_cli=0, o un
 * hosting sin la extension), todo cae a la consulta directa — el sistema
 * funciona igual, solo sin el ahorro.
 *
 * Uso:
 *   $valor = ms_cache_remember('modulos_hotel_5', 60, function () { ...query... });
 *   ms_cache_forget('modulos_hotel_5');   // al escribir el dato cacheado
 *
 * Reglas:
 *  - TTLs cortos (30-120s): la invalidacion explicita es cortesia, el TTL es
 *    la garantia. Nunca cachear con TTL largo datos que un admin edita.
 *  - NO cachear datos por usuario/sesion (solo por hotel/rol/config).
 *  - null nunca se guarda: un fallo de BD no debe "pegarse" 60 segundos.
 */

if (!defined('MS_CACHE_PREFIX')) {
    // Subir la version (ms1 -> ms2) invalida todo el cache tras un deploy
    // que cambie la forma de los datos cacheados.
    define('MS_CACHE_PREFIX', 'ms1:');
}

if (!function_exists('ms_cache_disponible')) {
    function ms_cache_disponible(): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            $disponible = function_exists('apcu_fetch')
                && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)
                && (PHP_SAPI !== 'cli' || filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN));
        }
        return $disponible;
    }
}

if (!function_exists('ms_cache_remember')) {
    /**
     * Devuelve el valor cacheado o ejecuta $fn, guarda su resultado y lo
     * devuelve. Un resultado null NO se guarda (los fallos no se pegan).
     */
    function ms_cache_remember(string $clave, int $ttl, callable $fn)
    {
        if (!ms_cache_disponible()) {
            return $fn();
        }

        $claveCompleta = MS_CACHE_PREFIX . $clave;
        $ok = false;
        $valor = apcu_fetch($claveCompleta, $ok);
        if ($ok) {
            return $valor;
        }

        $valor = $fn();
        if ($valor !== null) {
            apcu_store($claveCompleta, $valor, max(1, $ttl));
        }

        return $valor;
    }
}

if (!function_exists('ms_cache_forget')) {
    function ms_cache_forget(string $clave): void
    {
        if (ms_cache_disponible()) {
            apcu_delete(MS_CACHE_PREFIX . $clave);
        }
    }
}
