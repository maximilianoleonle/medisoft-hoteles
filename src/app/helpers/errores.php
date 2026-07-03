<?php
/**
 * Manejadores globales de errores.
 *
 * Garantiza que cualquier Throwable o error fatal de PHP:
 *  1. Quede registrado en el log estructurado con contexto (ms_log).
 *  2. Muestre al usuario la pagina de error amigable con un codigo de
 *     referencia (request_id), nunca una pantalla blanca ni un stack trace
 *     en produccion.
 */

/**
 * Renderizar la pagina 500 de forma defensiva.
 * Si la vista o sus helpers no estan disponibles (fallo muy temprano en el
 * bootstrap), cae a un HTML minimo. Nunca lanza.
 */
function ms_render_error_500() {
    if (headers_sent()) {
        // Ya se envio parte de la respuesta: no podemos rearmar la pagina.
        return;
    }

    // Descartar cualquier salida parcial para no mezclar HTML roto.
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    http_response_code(500);

    try {
        if (class_exists('View') && function_exists('url')) {
            View::render('errors/500', [
                'title' => 'Error del servidor',
                'request_id' => ms_request_id(),
            ]);
            return;
        }
    } catch (Throwable $e) {
        ms_log('critical', 'Fallo al renderizar la pagina 500: ' . $e->getMessage());
    }

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error del servidor</title></head>'
        . '<body style="font-family:sans-serif;text-align:center;padding:3rem;">'
        . '<h1>Error del servidor</h1>'
        . '<p>Ocurrio un problema inesperado. Intente nuevamente en unos minutos.</p>'
        . '<p style="color:#888;">Codigo de referencia: #' . htmlspecialchars(ms_request_id(), ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
}

/**
 * Manejador central: registra el Throwable y responde segun el tipo de
 * peticion (JSON para AJAX, pagina 500 para navegacion normal).
 */
function ms_manejar_throwable($e) {
    if (!$e instanceof Throwable) {
        return;
    }

    ms_log_excepcion($e, 'critical');

    $esAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    $debug = defined('APP_DEBUG') && APP_DEBUG;

    if ($esAjax && !headers_sent()) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'success' => false,
            'message' => 'Ocurrio un error inesperado. Codigo de referencia: #' . ms_request_id(),
            'request_id' => ms_request_id(),
        ];
        if ($debug) {
            $payload['debug'] = $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        return;
    }

    if ($debug && !headers_sent()) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        http_response_code(500);
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><em>' . htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8') . '</em></p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p>Codigo de referencia: #' . htmlspecialchars(ms_request_id(), ENT_QUOTES, 'UTF-8') . '</p>';
        return;
    }

    ms_render_error_500();
}

/**
 * Registrar los manejadores globales. Llamar una sola vez, lo antes posible
 * en el bootstrap (index.php).
 */
function ms_registrar_manejadores_errores() {
    set_exception_handler('ms_manejar_throwable');

    // Los errores fatales (E_ERROR, agotamiento de memoria, etc.) no pasan por
    // set_exception_handler: se capturan en el shutdown.
    register_shutdown_function(function () {
        $err = error_get_last();

        if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        ms_log('critical', 'Error fatal de PHP: ' . $err['message'], [
            'origen' => $err['file'] . ':' . $err['line'],
            'tipo' => $err['type'],
        ]);

        ms_render_error_500();
    });
}
