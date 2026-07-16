<?php
/**
 * Radiografía del sistema — Medisoft Hoteles
 *
 * Un solo comando que imprime el estado VIVO del entorno de desarrollo para
 * que una sesión de IA (o un humano tras un merge) arranque sin explorar:
 * migraciones pendientes, linter de tenancy, frescura del CSS compilado,
 * errores recientes en logs y tamaño de la suite de tests.
 *
 * Uso (desde el host, PHP vive en el contenedor):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/radiografia.php
 *
 * Regla de diseño: cada sección es independiente y NUNCA tumba el reporte
 * completo (try/catch por bloque). Salida pensada para leerse en ~20 líneas.
 */

$src = dirname(__DIR__); // /var/www/html en contenedor, src/ en repo

echo "== RADIOGRAFÍA " . date('Y-m-d H:i') . " ==\n";

// ── 1. BD y migraciones ──────────────────────────────────────────────────
try {
    $config = require $src . '/config/database.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );

    $dirMigraciones = null;
    foreach ([dirname($src) . '/migrations', $src . '/../migrations'] as $cand) {
        if (is_dir($cand)) { $dirMigraciones = realpath($cand); break; }
    }
    $archivos = $dirMigraciones ? glob($dirMigraciones . '/*.sql') : [];
    $aplicadas = [];
    try {
        $aplicadas = $pdo->query('SELECT archivo FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        // tabla inexistente = BD sin baseline
    }
    $pendientes = array_diff(array_map('basename', $archivos), $aplicadas);

    echo "BD: {$config['database']} @ {$config['host']} | migraciones: " . count($archivos)
        . " | PENDIENTES: " . count($pendientes) . "\n";
    foreach ($pendientes as $p) {
        echo "  PENDIENTE  $p\n";
    }
    if ($pendientes) {
        echo "  -> correr: docker exec medisoft_hoteles_app php /var/www/html/tools/migrate.php up\n";
    }
} catch (Throwable $e) {
    echo "BD: SIN CONEXIÓN (" . $e->getMessage() . ")\n";
}

// ── 2. Linter de tenancy (ratchet vs baseline) ───────────────────────────
try {
    $salida = [];
    $code = 1;
    exec('php ' . escapeshellarg(__DIR__ . '/lint_tenancy.php') . ' 2>&1', $salida, $code);
    $resumen = trim((string) end($salida));
    echo 'Tenancy: ' . ($code === 0 ? 'OK' : 'FALLA') . " — $resumen\n";
    if ($code !== 0) {
        echo "  -> detalle: php tools/lint_tenancy.php --detalle\n";
    }
} catch (Throwable $e) {
    echo "Tenancy: no se pudo evaluar\n";
}

// ── 3. Frescura del CSS compilado (heurística de build:css pendiente) ────
try {
    $css = $src . '/public_html/css/tailwind.css';
    $mtimeCss = is_file($css) ? filemtime($css) : 0;
    $masNuevo = 0;
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $src . '/app/views', FilesystemIterator::SKIP_DOTS
    ));
    foreach ($rii as $f) {
        if ($f->getExtension() === 'php' && $f->getMTime() > $masNuevo) {
            $masNuevo = $f->getMTime();
        }
    }
    foreach (glob($src . '/public_html/js/*.js') ?: [] as $js) {
        if (filemtime($js) > $masNuevo) {
            $masNuevo = filemtime($js);
        }
    }
    if (!$mtimeCss) {
        echo "CSS: tailwind.css NO EXISTE -> npm run build:css\n";
    } elseif ($masNuevo > $mtimeCss) {
        echo 'CSS: vistas/js más nuevos que tailwind.css ('
            . date('m-d H:i', $masNuevo) . ' > ' . date('m-d H:i', $mtimeCss)
            . ") -> posible build:css pendiente (solo si se tocaron clases Tailwind)\n";
    } else {
        echo 'CSS: al día (' . date('m-d H:i', $mtimeCss) . ")\n";
    }
} catch (Throwable $e) {
    echo "CSS: no se pudo evaluar\n";
}

// ── 4. Errores en logs (hoy y ayer) ──────────────────────────────────────
try {
    $dirLogs = $src . '/storage/logs';
    $ultimoError = null;
    foreach ([date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))] as $dia) {
        $archivo = "$dirLogs/app-$dia.log";
        if (!is_readable($archivo)) {
            echo "Logs $dia: sin archivo\n";
            continue;
        }
        $n = 0;
        $ultimoDelDia = null;
        $fh = fopen($archivo, 'r');
        while (($linea = fgets($fh)) !== false) {
            if (strpos($linea, '"nivel":"error"') !== false) {
                $n++;
                $ultimoDelDia = $linea;
            }
        }
        fclose($fh);
        // hoy tiene prioridad: ayer solo aporta el "último" si hoy no tuvo errores
        if ($ultimoDelDia !== null && $ultimoError === null) {
            $ultimoError = $ultimoDelDia;
        }
        echo "Logs $dia: $n errores\n";
    }
    if ($ultimoError && ($j = json_decode($ultimoError, true))) {
        echo '  último: [' . ($j['ts'] ?? '?') . '] '
            . mb_substr((string) ($j['mensaje'] ?? ''), 0, 140) . "\n";
    }
} catch (Throwable $e) {
    echo "Logs: no se pudieron leer\n";
}

// ── 5. Suite de tests (conteo; correrla recrea la BD de prueba) ──────────
$casos = glob($src . '/tests/casos/*Test.php') ?: [];
$conc = glob($src . '/tests/concurrencia/*.php') ?: [];
echo 'Tests: ' . count($casos) . ' casos + ' . count($conc) . " de concurrencia"
    . " (correr: docker exec medisoft_hoteles_app php /var/www/html/tests/run.php)\n";

echo "== FIN ==\n";
