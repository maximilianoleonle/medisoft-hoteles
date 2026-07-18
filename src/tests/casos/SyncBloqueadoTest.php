<?php

require_once __DIR__ . '/../lib.php';

/**
 * Extrae el cuerpo de un metodo con tokens para respetar llaves anidadas.
 */
function sync_bloqueado_method_body(string $source, string $method): ?string
{
    $tokens = token_get_all($source);
    $total = count($tokens);

    for ($i = 0; $i < $total; $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) {
            continue;
        }

        $name = null;
        for ($j = $i + 1; $j < $total; $j++) {
            $token = $tokens[$j];
            if (is_array($token) && $token[0] === T_STRING) {
                $name = $token[1];
                break;
            }
            if ($token === '{' || $token === ';') {
                break;
            }
        }

        if ($name !== $method) {
            continue;
        }

        while ($j < $total && $tokens[$j] !== '{') {
            $j++;
        }
        if ($j >= $total) {
            return null;
        }

        $depth = 0;
        $body = '';
        for (; $j < $total; $j++) {
            $token = $tokens[$j];
            $text = is_array($token) ? $token[1] : $token;
            $body .= $text;

            if ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
                if ($depth === 0) {
                    return $body;
                }
            }
        }

        return null;
    }

    return null;
}

$controllerPath = __DIR__ . '/../../app/controllers/ApiController.php';
$source = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$body = sync_bloqueado_method_body($source, 'syncAction');

t_ok($source !== '', 'ApiController.php existe y se puede leer');
t_ok($body !== null, 'syncAction existe y su cuerpo se puede extraer');

$syncBody = $body ?? '';
t_ok(strpos($syncBody, '423') !== false, 'syncAction responde HTTP 423');
t_ok(
    preg_match("/'error'\\s*=>\\s*'sync_temporarily_disabled'/", $syncBody) === 1,
    'syncAction responde error sync_temporarily_disabled'
);
t_ok(
    preg_match("/'success'\\s*=>\\s*false/", $syncBody) === 1,
    'syncAction responde success false'
);
t_ok(strpos($syncBody, 'new Sync') === false, 'syncAction no instancia Sync');
t_ok(strpos($syncBody, 'procesarLote') === false, 'syncAction no procesa lotes');
t_ok(strpos($syncBody, 'php://input') === false, 'syncAction no lee el body');

t_fin();
