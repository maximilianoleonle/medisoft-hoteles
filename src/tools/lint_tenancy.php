<?php
/**
 * Linter multi-tenant — Medisoft Hoteles
 *
 * Detecta SQL que toca tablas CON columna hotel_id sin mencionar hotel_id en
 * la misma query (ventana de texto alrededor de la referencia a la tabla).
 * Una query asi es candidata a fuga entre hoteles.
 *
 * Estrategia "ratchet": la deuda existente vive en lint_tenancy_baseline.json
 * (archivo:tabla -> cantidad). El linter FALLA solo si aparece una referencia
 * nueva (par archivo:tabla nuevo o mas ocurrencias que la linea base). Asi el
 * codigo nuevo nace blindado y la deuda vieja solo puede bajar.
 *
 * Supresion puntual: incluir 'tenant-ok' en un comentario del SQL o cerca de
 * la referencia (ej. "-- tenant-ok: catalogo global compartido a proposito").
 *
 * Uso (vive en src/tools para correr igual en contenedor y en CI):
 *   php src/tools/lint_tenancy.php               # verifica contra la linea base
 *   php src/tools/lint_tenancy.php --baseline    # regenera la linea base
 *   php src/tools/lint_tenancy.php --detalle     # lista cada hallazgo
 */

$src = dirname(__DIR__); // .../src
$schema = $src . '/database/schema.sql';
$baselinePath = __DIR__ . '/lint_tenancy_baseline.json';

$modoBaseline = in_array('--baseline', $argv, true);
$modoDetalle = in_array('--detalle', $argv, true);

// ── 1. Clasificar tablas desde el schema (fuente de verdad) ──────────────
if (!is_readable($schema)) {
    fwrite(STDERR, "No se encontro database/schema.sql\n");
    exit(2);
}
$sqlSchema = file_get_contents($schema);
preg_match_all('/CREATE TABLE `([a-z0-9_]+)`\s*\((.*?)\)\s*ENGINE/is', $sqlSchema, $m, PREG_SET_ORDER);

$tablasTenant = [];
foreach ($m as $t) {
    if (stripos($t[2], '`hotel_id`') !== false) {
        $tablasTenant[strtolower($t[1])] = true;
    }
}
if (count($tablasTenant) < 10) {
    fwrite(STDERR, 'Schema sospechosamente vacio (' . count($tablasTenant) . " tablas tenant). Regenerar schema.sql.\n");
    exit(2);
}

// ── 2. Escanear codigo propio ────────────────────────────────────────────
$dirs = ['app/models', 'app/services', 'app/controllers', 'app/helpers', 'core', 'api'];
$archivos = [];
foreach ($dirs as $d) {
    $ruta = $src . '/' . $d;
    if (!is_dir($ruta)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() === 'php' && strpos($f->getPathname(), 'tcpdf') === false) {
            $archivos[] = $f->getPathname();
        }
    }
}

$hallazgos = []; // "archivo_rel|tabla" => cantidad
$detalles = [];

foreach ($archivos as $archivo) {
    $contenido = file_get_contents($archivo);
    $rel = str_replace('\\', '/', substr($archivo, strlen($src) + 1));

    if (!preg_match_all('/\b(FROM|JOIN|UPDATE|INTO)\s+`?([a-z0-9_]+)`?/i', $contenido, $refs, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
        continue;
    }

    foreach ($refs as $ref) {
        $tabla = strtolower($ref[2][0]);
        if (!isset($tablasTenant[$tabla])) {
            continue;
        }

        $pos = $ref[0][1];
        // Ventana: la mayoria de las queries del repo caben en ~1200 chars.
        $ventana = substr($contenido, max(0, $pos - 400), 400 + strlen($ref[0][0]) + 800);

        if (stripos($ventana, 'hotel_id') !== false || stripos($ventana, 'tenant-ok') !== false) {
            continue;
        }

        $linea = substr_count(substr($contenido, 0, $pos), "\n") + 1;
        $clave = $rel . '|' . $tabla;
        $hallazgos[$clave] = ($hallazgos[$clave] ?? 0) + 1;
        $detalles[] = "$rel:$linea  tabla=$tabla";
    }
}

ksort($hallazgos);

// ── 3. Modo baseline: congelar la deuda actual ───────────────────────────
if ($modoBaseline) {
    file_put_contents($baselinePath, json_encode($hallazgos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo 'Linea base regenerada: ' . count($hallazgos) . ' pares archivo|tabla, ' . array_sum($hallazgos) . " referencias.\n";
    exit(0);
}

// ── 4. Comparar contra la linea base (ratchet) ───────────────────────────
$baseline = is_readable($baselinePath)
    ? (json_decode(file_get_contents($baselinePath), true) ?: [])
    : [];

$nuevos = [];
foreach ($hallazgos as $clave => $cant) {
    $permitido = $baseline[$clave] ?? 0;
    if ($cant > $permitido) {
        $nuevos[] = "$clave (ahora $cant, linea base $permitido)";
    }
}

if ($modoDetalle) {
    echo implode("\n", $detalles) . "\n\n";
}

$totalRefs = array_sum($hallazgos);
$totalBase = array_sum($baseline);
echo "Referencias sin hotel_id: $totalRefs (linea base: $totalBase)\n";

if ($nuevos) {
    echo "\nNUEVAS queries sin scoping de hotel (agregar hotel_id o marcar 'tenant-ok' con justificacion):\n";
    echo '  ' . implode("\n  ", $nuevos) . "\n";
    exit(1);
}

if ($totalRefs < $totalBase) {
    echo "La deuda bajo ($totalBase -> $totalRefs): regenerar linea base con --baseline y commitearla.\n";
}

echo "OK: sin queries nuevas sin scoping.\n";
exit(0);
