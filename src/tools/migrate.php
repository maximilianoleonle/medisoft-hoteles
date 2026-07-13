<?php
/**
 * Runner de migraciones — Medisoft Hoteles
 *
 * Lleva registro de que migracion se aplico en que base (tabla
 * schema_migrations) y aplica las pendientes EN ORDEN. Resuelve el dolor
 * multi-PC/produccion de "aplicar migraciones a mano tras cada merge".
 *
 * Comandos:
 *   php tools/migrate.php status     # que hay aplicado y que falta (default)
 *   php tools/migrate.php up         # aplica TODAS las pendientes en orden
 *   php tools/migrate.php up --una   # aplica solo la siguiente
 *   php tools/migrate.php baseline   # marca todo como aplicado SIN ejecutar
 *                                    # (para BDs existentes que ya van al dia)
 *
 * Uso tipico tras un merge (dentro del contenedor):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/migrate.php up
 *
 * Soporta bloques DELIMITER (procedures) parseandolos como el cliente mysql.
 * Cada migracion es responsable de su propia idempotencia (los ALTER hacen
 * commit implicito en MySQL; no hay rollback automatico).
 */

$src = dirname(__DIR__);

// migrations/ vive junto a src (repo) o junto a /var/www/html (contenedor)
$dirMigraciones = null;
foreach ([dirname($src) . '/migrations', $src . '/../migrations'] as $cand) {
    if (is_dir($cand)) { $dirMigraciones = realpath($cand); break; }
}
if (!$dirMigraciones) {
    fwrite(STDERR, "No se encontro el directorio migrations/.\n");
    exit(2);
}

$config = require $src . '/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    archivo VARCHAR(255) NOT NULL PRIMARY KEY,
    aplicada_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$aplicadas = $pdo->query('SELECT archivo FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$aplicadas = array_flip($aplicadas);

$archivos = glob($dirMigraciones . '/*.sql');
sort($archivos);
$pendientes = array_values(array_filter($archivos, fn ($a) => !isset($aplicadas[basename($a)])));

$comando = $argv[1] ?? 'status';

echo 'BD: ' . $config['database'] . ' @ ' . $config['host']
    . ' | migraciones: ' . count($archivos)
    . ' | aplicadas: ' . count($aplicadas)
    . ' | pendientes: ' . count($pendientes) . "\n";

if ($comando === 'status') {
    foreach ($pendientes as $p) {
        echo '  PENDIENTE  ' . basename($p) . "\n";
    }
    if (!$pendientes) {
        echo "Al dia.\n";
    }
    exit(0);
}

if ($comando === 'baseline') {
    $st = $pdo->prepare('INSERT IGNORE INTO schema_migrations (archivo) VALUES (?)');
    foreach ($archivos as $a) {
        $st->execute([basename($a)]);
    }
    echo 'Linea base registrada: ' . count($archivos) . " migraciones marcadas como aplicadas (sin ejecutar).\n";
    echo "Usar SOLO en una BD que ya estaba al dia.\n";
    exit(0);
}

if ($comando !== 'up') {
    fwrite(STDERR, "Comando desconocido: $comando (usar status | up | baseline)\n");
    exit(2);
}

$soloUna = in_array('--una', $argv, true);

/**
 * Divide un script SQL respetando DELIMITER (construccion del CLIENTE mysql,
 * PDO no la entiende) y comentarios de linea.
 */
function ms_dividir_sql(string $sql): array
{
    $delimitador = ';';
    $sentencias = [];
    $actual = '';

    foreach (preg_split('/\r\n|\r|\n/', $sql) as $linea) {
        $recortada = trim($linea);

        // Comentarios completos y lineas vacias fuera de una sentencia en curso
        if ($actual === '' && ($recortada === '' || strpos($recortada, '--') === 0 || strpos($recortada, '#') === 0)) {
            continue;
        }

        if (preg_match('/^DELIMITER\s+(\S+)/i', $recortada, $m)) {
            $delimitador = $m[1];
            continue;
        }

        $actual .= $linea . "\n";

        $fin = rtrim($actual);
        if (substr($fin, -strlen($delimitador)) === $delimitador) {
            $sentencia = trim(substr($fin, 0, -strlen($delimitador)));
            if ($sentencia !== '') {
                $sentencias[] = $sentencia;
            }
            $actual = '';
        }
    }

    $resto = trim($actual);
    if ($resto !== '') {
        $sentencias[] = $resto;
    }

    return $sentencias;
}

foreach ($pendientes as $ruta) {
    $nombre = basename($ruta);
    echo "Aplicando $nombre ... ";

    $sentencias = ms_dividir_sql((string) file_get_contents($ruta));

    try {
        foreach ($sentencias as $s) {
            $pdo->exec($s);
        }
    } catch (Throwable $e) {
        echo "ERROR\n";
        fwrite(STDERR, "  " . $e->getMessage() . "\n");
        fwrite(STDERR, "  La migracion NO se registro. Corregir y reintentar (deben ser idempotentes).\n");
        exit(1);
    }

    $pdo->prepare('INSERT INTO schema_migrations (archivo) VALUES (?)')->execute([$nombre]);
    echo "OK\n";

    if ($soloUna) {
        break;
    }
}

echo "Listo.\n";
exit(0);
