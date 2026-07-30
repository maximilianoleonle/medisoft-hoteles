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

/**
 * Este caso NACIÓ para exigir que /api/sync respondiera 423 a TODO, después de
 * que los interceptores encolaran cobros que nunca llegaban al servidor
 * (jul-2026). El 30-jul se reabrió la Ola 1 — SOLO alta de huésped — así que el
 * candado cambió de forma, no desapareció: ahora vigila que la lista siga siendo
 * mínima y que nada de dinero se cuele.
 *
 * Si alguien amplía OPERACIONES_HABILITADAS sin arreglar los defectos que la
 * auditoría documentó (checkout escribe un estado inexistente en el enum; los
 * movimientos de caja no comprueban su INSERT ni bloquean el corte), este caso
 * truena. Es deliberado: que duela aquí y no en un hotel.
 */

require_once __DIR__ . '/../../app/models/Sync.php';

// ── 1. La lista blanca del servidor ──────────────────────────────────────────

$habilitadas = Sync::OPERACIONES_HABILITADAS;
t_ok(is_array($habilitadas), 'Sync declara OPERACIONES_HABILITADAS como lista');

foreach (['pago_caja', 'gasto_caja', 'pre_corte_caja'] as $tipoDinero) {
    t_ok(
        !in_array($tipoDinero, $habilitadas, true),
        "ninguna operación de dinero está habilitada offline ($tipoDinero)"
    );
}

t_ok(
    !in_array('checkout', $habilitadas, true),
    'checkout sigue deshabilitado (escribe estado="completada", ausente del enum)'
);
t_ok(
    $habilitadas === ['crear_huesped'],
    'Ola 1: la única operación habilitada es crear_huesped'
);

// ── 2. El endpoint filtra por lista BLANCA, no por lista negra ───────────────

$controllerPath = __DIR__ . '/../../app/controllers/ApiController.php';
$source = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$body = sync_bloqueado_method_body($source, 'syncAction');

t_ok($source !== '', 'ApiController.php existe y se puede leer');
t_ok($body !== null, 'syncAction existe y su cuerpo se puede extraer');

$syncBody = $body ?? '';
t_ok(
    strpos($syncBody, 'Sync::OPERACIONES_HABILITADAS') !== false,
    'syncAction filtra contra la lista del modelo, no contra una copia local'
);
t_ok(strpos($syncBody, 'in_array') !== false, 'syncAction comprueba pertenencia antes de procesar');
t_ok(
    strpos($syncBody, 'Sync::MAX_OPERACIONES_POR_LOTE') !== false,
    'syncAction respeta el tope de operaciones por lote'
);
t_ok(
    strpos($syncBody, 'array_slice') !== false,
    'un lote grande se recorta y se drena por partes, no se rechaza entero'
);

// ── 3. El modelo repite el candado (defensa en profundidad) ──────────────────

$modelSource = (string) file_get_contents(__DIR__ . '/../../app/models/Sync.php');
$despacharBody = sync_bloqueado_method_body($modelSource, 'despachar');

t_ok($despacharBody !== null, 'Sync::despachar existe');
t_ok(
    strpos((string) $despacharBody, 'OPERACIONES_HABILITADAS') !== false,
    'Sync::despachar rechaza por su cuenta los tipos no habilitados'
);

// ── 4. Cliente y servidor no pueden desincronizarse ──────────────────────────

$headerSource = (string) @file_get_contents(__DIR__ . '/../../app/views/layout/header.php');
t_ok(
    strpos($headerSource, 'Sync::OPERACIONES_HABILITADAS') !== false,
    'el layout publica la MISMA constante del servidor, no una lista a mano'
);

$offlineData = (string) @file_get_contents(__DIR__ . '/../../public_html/js/offline-data.js');
t_ok(
    strpos($offlineData, 'MEDISOFT_OFFLINE_OPERACIONES') !== false,
    'el cliente decide por operación, no por un booleano global'
);
t_ok(
    strpos($offlineData, 'if (!tipo) return false') !== false,
    'sin tipo declarado, el permiso falla CERRADO'
);
t_ok(
    strpos($offlineData, 'res.status === 403') !== false,
    'el cliente maneja el 403 del CSRF (sin esto la cola reintenta para siempre)'
);

t_fin();
