<?php
/**
 * Runner de pruebas — Medisoft Hoteles
 *
 * Ejecuta cada tests/casos/*Test.php en un PROCESO PHP INDEPENDIENTE
 * (los caches estaticos por-request del app exigen proceso limpio por caso)
 * y resume resultados. Exit != 0 si algo fallo.
 *
 * Uso (desde el host):
 *   docker exec medisoft_hoteles_app php /var/www/html/tests/run.php
 * En CI se ejecuta directo: php src/tests/run.php (con DB_HOST=127.0.0.1).
 *
 * TODO pendiente: NominaCreditoTest (invariante NOMV2: credito = bruto -
 * ledger absorbido + candado anti doble pago en anular/reabrir). Requiere
 * seed profundo de grupos/trabajadores/periodos.
 */

$casos = glob(__DIR__ . '/casos/*Test.php');
sort($casos);

if (!$casos) {
    fwrite(STDERR, "No hay casos de prueba en tests/casos/.\n");
    exit(1);
}

$inicio = microtime(true);
$fallas = [];

foreach ($casos as $caso) {
    $nombre = basename($caso);
    echo "== $nombre ==\n";

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($caso) . ' 2>&1';
    $salida = [];
    $codigo = 0;
    exec($cmd, $salida, $codigo);
    echo implode("\n", $salida) . "\n";

    if ($codigo !== 0) {
        $fallas[] = $nombre;
    }
}

$duracion = round(microtime(true) - $inicio, 1);
echo "\n==============================\n";
if ($fallas) {
    echo 'FALLARON: ' . implode(', ', $fallas) . " ({$duracion}s)\n";
    exit(1);
}
echo 'TODOS LOS CASOS PASARON (' . count($casos) . " archivos, {$duracion}s)\n";
exit(0);
