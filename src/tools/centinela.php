<?php
/**
 * Centinela de errores — Medisoft Hoteles
 *
 * Vigila los logs estructurados (storage/logs/app-*.log) y reporta errores
 * REALES con precisión de firma: agrupa las repeticiones de un mismo error
 * en un solo cluster con todo el contexto para corregirlo (mensaje, SQL,
 * origen archivo:línea, rutas afectadas, conteo, primera/última vez).
 *
 * Mantiene estado en centinela_estado.json para ser certero:
 *   - NUEVA:     firma nunca vista → esto es lo que hay que atender.
 *   - REGRESIÓN: firma marcada resuelta que volvió a aparecer DESPUÉS de
 *                la fecha de resolución → prioridad máxima.
 *   - conocida:  ya reportada antes, sigue ahí (se lista compacta).
 *   - ignorada:  ruido aceptado a propósito (no se lista).
 *
 * Uso (en contenedor, como todas las tools):
 *   MSYS_NO_PATHCONV=1 docker exec medisoft_hoteles_app php /var/www/html/tools/centinela.php
 *   ... centinela.php --dias=30          # ventana de análisis (default 7)
 *   ... centinela.php --json             # salida máquina (contrato de automatización)
 *   ... centinela.php resolver <firma> "nota de qué se arregló"
 *   ... centinela.php ignorar  <firma> "por qué es ruido aceptado"
 *
 * Exit code: 0 = sin nuevas ni regresiones · 1 = hay que actuar.
 *
 * Pipeline futuro (autocorrección con Claude): un cron corre
 * `centinela.php --json`; si exit=1, manda cada cluster nuevo/regresión a
 * Claude (API o `claude -p`) con el JSON como contexto; Claude propone el
 * fix como rama/PR; humano aprueba; al mergear se marca `resolver <firma>`.
 * El JSON de cada cluster es autosuficiente para ese fin: no agregar campos
 * al reporte humano sin agregarlos también aquí.
 */

$src = dirname(__DIR__);
$rutaEstado = __DIR__ . '/centinela_estado.json';

// ── Argumentos ────────────────────────────────────────────────────────────
$dias = 7;
$modoJson = false;
$accion = null;
$accionFirma = null;
$accionNota = '';
foreach (array_slice($argv, 1) as $i => $arg) {
    if (preg_match('/^--dias=(\d+)$/', $arg, $m)) {
        $dias = (int) $m[1];
    } elseif ($arg === '--json') {
        $modoJson = true;
    } elseif (in_array($arg, ['resolver', 'ignorar'], true)) {
        $accion = $arg;
        $accionFirma = $argv[$i + 2] ?? null; // argv[0]=script, slice desde 1
        $accionNota = $argv[$i + 3] ?? '';
    }
}

$estado = is_readable($rutaEstado)
    ? (json_decode(file_get_contents($rutaEstado), true) ?: [])
    : [];

// ── Acciones de estado ────────────────────────────────────────────────────
if ($accion !== null) {
    if (!$accionFirma) {
        fwrite(STDERR, "Falta la firma: centinela.php $accion <firma> [nota]\n");
        exit(2);
    }
    $estado[$accionFirma] = [
        'estado' => $accion === 'resolver' ? 'resuelta' : 'ignorada',
        'nota' => $accionNota,
        'fecha' => date('Y-m-d H:i:s'),
    ];
    file_put_contents($rutaEstado, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "Firma $accionFirma marcada como {$estado[$accionFirma]['estado']}.\n";
    exit(0);
}

// ── Recolectar errores de la ventana ──────────────────────────────────────
$desde = date('Y-m-d 00:00:00', strtotime("-$dias days"));
$clusters = [];

for ($d = 0; $d <= $dias; $d++) {
    $archivo = $src . '/storage/logs/app-' . date('Y-m-d', strtotime("-$d days")) . '.log';
    if (!is_readable($archivo)) {
        continue;
    }
    $fh = fopen($archivo, 'r');
    while (($linea = fgets($fh)) !== false) {
        if (strpos($linea, '"nivel":"error"') === false && strpos($linea, '"nivel":"critical"') === false) {
            continue;
        }
        $e = json_decode($linea, true);
        if (!$e || ($e['ts'] ?? '') < $desde) {
            continue;
        }

        // Firma: mensaje normalizado (números y literales fuera) + método normalizado.
        // Un mismo bug con IDs distintos debe caer en el MISMO cluster.
        $mensajeNorm = preg_replace(
            ["/'[^']*'/", '/"[^"]*"/', '/\b\d+\b/'],
            ["'…'", '"…"', 'N'],
            (string) ($e['mensaje'] ?? '')
        );
        $canal = ($e['metodo'] ?? '') === 'cli' ? 'cli' : 'web';
        $firma = substr(md5($canal . '|' . ($e['nivel'] ?? '') . '|' . $mensajeNorm), 0, 10);

        if (!isset($clusters[$firma])) {
            $clusters[$firma] = [
                'firma' => $firma,
                'nivel' => $e['nivel'],
                'canal' => $canal,
                'mensaje_normalizado' => $mensajeNorm,
                'mensaje_ejemplo' => $e['mensaje'] ?? '',
                'conteo' => 0,
                'primera_vez' => $e['ts'],
                'ultima_vez' => $e['ts'],
                'rutas' => [],
                'origen' => null,
                'sql_ejemplo' => null,
                'codigo_sql' => null,
                'hoteles' => [],
                'request_id_ejemplo' => $e['request_id'] ?? null,
            ];
        }
        $c = &$clusters[$firma];
        $c['conteo']++;
        if ($e['ts'] < $c['primera_vez']) { $c['primera_vez'] = $e['ts']; }
        if ($e['ts'] > $c['ultima_vez']) {
            $c['ultima_vez'] = $e['ts'];
            $c['mensaje_ejemplo'] = $e['mensaje'] ?? $c['mensaje_ejemplo'];
            $c['request_id_ejemplo'] = $e['request_id'] ?? $c['request_id_ejemplo'];
        }
        $ruta = $e['ruta'] ?? '(sin ruta)';
        $c['rutas'][$ruta] = ($c['rutas'][$ruta] ?? 0) + 1;
        if (isset($e['hotel_id'])) { $c['hoteles'][$e['hotel_id']] = true; }
        $ctx = $e['contexto'] ?? [];
        if (!empty($ctx['origen'])) { $c['origen'] = $ctx['origen']; }
        if (!empty($ctx['sql'])) { $c['sql_ejemplo'] = $ctx['sql']; }
        if (!empty($ctx['codigo'])) { $c['codigo_sql'] = $ctx['codigo']; }
        unset($c);
    }
    fclose($fh);
}

// ── Clasificar contra el estado ───────────────────────────────────────────
$nuevas = $regresiones = $conocidas = [];
foreach ($clusters as $firma => $c) {
    $c['hoteles'] = array_keys($c['hoteles']);
    arsort($c['rutas']);
    $reg = $estado[$firma] ?? null;
    if ($reg === null) {
        $c['clasificacion'] = 'NUEVA';
        $nuevas[] = $c;
    } elseif ($reg['estado'] === 'ignorada') {
        continue;
    } elseif ($reg['estado'] === 'resuelta' && $c['ultima_vez'] > $reg['fecha']) {
        $c['clasificacion'] = 'REGRESION';
        $c['resuelta_en'] = $reg['fecha'];
        $c['nota_resolucion'] = $reg['nota'] ?? '';
        $regresiones[] = $c;
    } elseif ($reg['estado'] === 'resuelta') {
        continue; // resuelta y sin apariciones posteriores: silencio
    } else {
        $c['clasificacion'] = 'conocida';
        $conocidas[] = $c;
    }
}
$ordenar = fn ($a, $b) => $b['conteo'] <=> $a['conteo'];
usort($nuevas, $ordenar);
usort($regresiones, $ordenar);
usort($conocidas, $ordenar);

// Toda firma vista queda registrada como conocida (para que "nueva" sea de una sola vez)
foreach ($clusters as $firma => $c) {
    if (!isset($estado[$firma])) {
        $estado[$firma] = ['estado' => 'conocida', 'nota' => '', 'fecha' => date('Y-m-d H:i:s')];
    }
}
file_put_contents($rutaEstado, json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$hayAccion = $nuevas || $regresiones;

// ── Salida ────────────────────────────────────────────────────────────────
if ($modoJson) {
    echo json_encode([
        'generado' => date('Y-m-d H:i:s'),
        'ventana_dias' => $dias,
        'accion_requerida' => $hayAccion,
        'regresiones' => $regresiones,
        'nuevas' => $nuevas,
        'conocidas' => $conocidas,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($hayAccion ? 1 : 0);
}

echo '== CENTINELA ' . date('Y-m-d H:i') . " (últimos $dias días) ==\n";
$pintar = function (array $c) {
    $rutas = implode(', ', array_slice(array_keys($c['rutas']), 0, 3));
    echo "[{$c['clasificacion']}] firma {$c['firma']} · {$c['conteo']}x · {$c['nivel']} · canal {$c['canal']}\n";
    echo "  {$c['mensaje_ejemplo']}\n";
    if ($c['origen']) { echo "  origen: {$c['origen']}\n"; }
    if ($c['sql_ejemplo']) { echo '  sql: ' . mb_substr($c['sql_ejemplo'], 0, 160) . "\n"; }
    echo "  rutas: $rutas | {$c['primera_vez']} → {$c['ultima_vez']}\n";
    if ($c['canal'] === 'cli') {
        echo "  ⚠ canal cli: probablemente tests contra medisoft_test (ver CEMENTERIO antes de culpar a la BD real)\n";
    }
    if (!empty($c['resuelta_en'])) {
        echo "  resuelta el {$c['resuelta_en']} ({$c['nota_resolucion']}) y VOLVIÓ\n";
    }
};

if ($regresiones) {
    echo "\n-- REGRESIONES (máxima prioridad) --\n";
    array_map($pintar, $regresiones);
}
if ($nuevas) {
    echo "\n-- NUEVAS --\n";
    array_map($pintar, $nuevas);
}
if ($conocidas) {
    echo "\n-- Conocidas sin resolver (compacto) --\n";
    foreach ($conocidas as $c) {
        echo "  {$c['firma']} · {$c['conteo']}x · " . mb_substr($c['mensaje_ejemplo'], 0, 90) . "\n";
    }
}
if (!$clusters) {
    echo "Sin errores en la ventana. Sistema limpio.\n";
} elseif (!$hayAccion) {
    echo "\nSin firmas nuevas ni regresiones. Nada que atender.\n";
}
echo "\nMarcar arreglado: php tools/centinela.php resolver <firma> \"nota\" · silenciar ruido: ignorar <firma>\n";
exit($hayAccion ? 1 : 0);
