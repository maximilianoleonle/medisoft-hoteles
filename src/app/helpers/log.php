<?php
/**
 * Logger estructurado de la aplicacion.
 *
 * Escribe una linea JSON por evento en storage/logs/app-YYYY-MM-DD.log con
 * contexto de tenant (hotel, usuario, ruta, request_id) para poder rastrear
 * cualquier fallo en produccion multi-hotel.
 *
 * Regla de oro: este logger NUNCA lanza excepciones ni rompe la peticion.
 * Si no puede escribir al archivo, cae al error_log() de PHP.
 */

/**
 * Identificador corto y unico de la peticion actual.
 * Se muestra al usuario en la pagina de error ("Codigo #a3f9c1d2") para
 * correlacionar su reporte con la linea exacta del log.
 */
function ms_request_id() {
    static $id = null;

    if ($id === null) {
        try {
            $id = bin2hex(random_bytes(4));
        } catch (Throwable $e) {
            $id = substr(str_pad(dechex(mt_rand()), 8, '0', STR_PAD_LEFT), 0, 8);
        }
    }

    return $id;
}

/**
 * Registrar un evento. Niveles sugeridos: debug, info, warning, error, critical.
 * $contexto es un array libre; no incluir contrasenas ni datos de tarjetas.
 */
function ms_log($nivel, $mensaje, array $contexto = []) {
    try {
        $registro = [
            'ts' => date('Y-m-d H:i:s'),
            'nivel' => (string) $nivel,
            'request_id' => ms_request_id(),
            'hotel_id' => isset($_SESSION['hotel_id']) && $_SESSION['hotel_id'] !== ''
                ? (int) $_SESSION['hotel_id'] : null,
            'usuario_id' => isset($_SESSION['user_id']) && $_SESSION['user_id'] !== ''
                ? (int) $_SESSION['user_id'] : null,
            'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'cli',
            'ruta' => isset($_SERVER['REQUEST_URI'])
                ? strtok((string) $_SERVER['REQUEST_URI'], '?') : null,
            'mensaje' => (string) $mensaje,
        ];

        if (!empty($contexto)) {
            $registro['contexto'] = $contexto;
        }

        $linea = json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($linea === false) {
            error_log('[ms_log] ' . $nivel . ': ' . $mensaje);
            return;
        }

        $dir = defined('STORAGE_PATH')
            ? STORAGE_PATH . '/logs'
            : dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $archivo = $dir . '/app-' . date('Y-m-d') . '.log';
        $archivoNuevo = !file_exists($archivo);

        if (@file_put_contents($archivo, $linea . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('[ms_log fallback] ' . $linea);
            return;
        }

        if ($archivoNuevo) {
            // El log lo comparten la web (www-data) y los scripts CLI (root en
            // Docker): sin esto, quien no creo el archivo cae al fallback.
            @chmod($archivo, 0666);
        }
    } catch (Throwable $e) {
        error_log('[ms_log error] ' . $e->getMessage() . ' | ' . $nivel . ': ' . $mensaje);
    }
}

/**
 * Registrar una excepcion/error con su ubicacion exacta.
 */
function ms_log_excepcion($e, $nivel = 'error', array $contexto = []) {
    if (!$e instanceof Throwable) {
        ms_log($nivel, 'ms_log_excepcion recibio un valor que no es Throwable');
        return;
    }

    $contexto += [
        'exception' => get_class($e),
        'origen' => $e->getFile() . ':' . $e->getLine(),
    ];

    ms_log($nivel, $e->getMessage(), $contexto);
}
