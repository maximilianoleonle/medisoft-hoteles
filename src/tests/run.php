<?php
/**
 * Runner de pruebas — Medisoft Hoteles
 *
 * 1. Recrea la BD de prueba desde database/schema.sql ANTES de correr: los
 *    casos asumen esquema al dia y BD limpia (sin residuos de corridas
 *    anteriores). Se salta con --keep-db o MS_TEST_KEEP_DB=1 (iteracion
 *    rapida cuando el esquema no cambio; los casos hacen t_reset_db()).
 * 2. Ejecuta cada tests/casos/*Test.php en un PROCESO PHP INDEPENDIENTE
 *    (los caches estaticos por-request del app exigen proceso limpio por caso)
 *    y resume resultados. Exit != 0 si algo fallo.
 *
 * Uso (desde el host):
 *   docker exec medisoft_hoteles_app php /var/www/html/tests/run.php
 *   docker exec medisoft_hoteles_app php /var/www/html/tests/run.php CanalWhatsApp
 *     (corre solo los casos cuyo nombre de archivo contenga el argumento)
 * En CI se ejecuta directo: php src/tests/run.php (con DB_HOST=127.0.0.1).
 *
 * Requisito unico por maquina: que la BD de prueba exista y el usuario de la
 * app tenga permisos sobre ella (una sola vez):
 *   CREATE DATABASE medisoft_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 *   GRANT ALL ON medisoft_test.* TO 'medisoft_user'@'%';
 *
 * TODO pendiente: NominaCreditoTest (invariante NOMV2: credito = bruto -
 * ledger absorbido + candado anti doble pago en anular/reabrir). Requiere
 * seed profundo de grupos/trabajadores/periodos.
 */

// ── Argumentos: flags (--*) y filtros de casos ───────────────────────────
$keepDb = getenv('MS_TEST_KEEP_DB') === '1';
$filtros = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--keep-db') {
        $keepDb = true;
    } elseif (strpos($arg, '--') === 0) {
        fwrite(STDERR, "Opcion desconocida: $arg (solo se acepta --keep-db).\n");
        exit(2);
    } else {
        $filtros[] = $arg;
    }
}

// ── BD de prueba: mismo candado que bootstrap.php ────────────────────────
$dbPrueba = getenv('MS_TEST_DB') ?: 'medisoft_test';
if (stripos($dbPrueba, 'test') === false) {
    fwrite(STDERR, "[tests] MS_TEST_DB debe contener 'test' (recibido: $dbPrueba). Abortando para proteger datos reales.\n");
    exit(2);
}

if (!$keepDb) {
    recrearBdPrueba($dbPrueba);
} else {
    echo "[tests] --keep-db: se conserva el esquema actual de $dbPrueba.\n";
}

// ── Casos a correr ───────────────────────────────────────────────────────
$casos = glob(__DIR__ . '/casos/*Test.php');
sort($casos);

if ($filtros) {
    $casos = array_values(array_filter($casos, function ($caso) use ($filtros) {
        foreach ($filtros as $f) {
            if (stripos(basename($caso), $f) !== false) {
                return true;
            }
        }
        return false;
    }));
}

if (!$casos) {
    if ($filtros) {
        fwrite(STDERR, 'Ningun caso casa con: ' . implode(', ', $filtros) . "\nDisponibles:\n");
        foreach (glob(__DIR__ . '/casos/*Test.php') as $c) {
            fwrite(STDERR, '  ' . basename($c) . "\n");
        }
    } else {
        fwrite(STDERR, "No hay casos de prueba en tests/casos/.\n");
    }
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

// ─────────────────────────────────────────────────────────────────────────

/**
 * Recrea la BD de prueba completa (DROP/CREATE DATABASE) e importa
 * database/schema.sql. Se recrea la base entera y no tabla por tabla porque
 * un DROP VIEW/TABLE de un objeto creado por OTRO usuario (p.ej. un import
 * manual como root) exige SYSTEM_USER en MySQL 8; DROP DATABASE no. El dump
 * es estructura plana (sin DELIMITER), por eso basta cortar por lineas que
 * terminan en ';'.
 */
function recrearBdPrueba(string $dbPrueba): void
{
    $schema = dirname(__DIR__) . '/database/schema.sql';
    if (!is_readable($schema)) {
        fwrite(STDERR, "[tests] No se encontro database/schema.sql ($schema).\n");
        exit(2);
    }

    $config = require dirname(__DIR__) . '/config/database.php';
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";

    try {
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("DROP DATABASE IF EXISTS `$dbPrueba`");
        $pdo->exec("CREATE DATABASE `$dbPrueba` CHARACTER SET utf8mb4 COLLATE {$config['collation']}");
        $pdo->exec("USE `$dbPrueba`");
    } catch (PDOException $e) {
        fwrite(STDERR, "[tests] No pude recrear la BD de prueba '$dbPrueba': " . $e->getMessage() . "\n");
        fwrite(STDERR, "Otorga permisos una sola vez (ver docblock de este archivo) y reintenta.\n");
        exit(2);
    }

    $inicio = microtime(true);
    $sentencia = '';
    $total = 0;

    foreach (preg_split('/\r?\n/', (string) file_get_contents($schema)) as $linea) {
        $trim = trim($linea);
        if ($sentencia === '' && ($trim === '' || strpos($trim, '--') === 0)) {
            continue;
        }

        $sentencia .= $linea . "\n";
        if (substr(rtrim($linea), -1) !== ';') {
            continue;
        }

        try {
            $pdo->exec($sentencia);
        } catch (PDOException $e) {
            fwrite(STDERR, "[tests] Fallo al importar schema.sql: " . $e->getMessage() . "\n");
            fwrite(STDERR, 'Sentencia: ' . mb_substr(trim($sentencia), 0, 200) . "\n");
            exit(2);
        }
        $sentencia = '';
        $total++;
    }

    $seg = round(microtime(true) - $inicio, 1);
    echo "[tests] BD $dbPrueba recreada desde schema.sql ($total sentencias, {$seg}s).\n";
}
