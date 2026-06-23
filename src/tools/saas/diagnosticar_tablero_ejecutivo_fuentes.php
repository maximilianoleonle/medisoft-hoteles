<?php
/**
 * Diagnostico read-only de fuentes del tablero ejecutivo.
 *
 * No crea migraciones, no corrige auditoria, no modifica fuentes y no escribe
 * datos. Solo explica warnings funcionales de ledger_laboral y logs_auditoria.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo '[ERROR] APP_ENV debe ser local. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    echo "Resultado general: FAIL\n";
    exit(1);
}

$limit = 20;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string)$arg, $m) === 1) {
        $limit = max(1, min(200, (int)$m[1]));
    }
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$modelPath = $appRoot . '/app/models/TableroEjecutivo.php';

$ok = 0;
$warnings = 0;
$errors = 0;
$recommendations = [];

function dteLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function dteOk(string $message): void
{
    global $ok;
    $ok++;
    dteLine('OK', $message);
}

function dteWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    dteLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function dteError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    dteLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function dteSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function dteQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function dteFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function dteScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function dteTableExists(PDO $pdo, string $database, string $table): bool
{
    return (int)dteScalar(
        $pdo,
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table',
        ['db' => $database, 'table' => $table]
    ) > 0;
}

function dteTableColumns(PDO $pdo, string $database, string $table): array
{
    $rows = dteFetchAll(
        $pdo,
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
         ORDER BY ORDINAL_POSITION',
        ['db' => $database, 'table' => $table]
    );

    return array_map(static fn(array $row): string => (string)$row['COLUMN_NAME'], $rows);
}

function dteHasColumn(array $columns, string $column): bool
{
    return in_array($column, $columns, true);
}

function dteTableCount(PDO $pdo, string $table): int
{
    return (int)dteScalar($pdo, 'SELECT COUNT(*) FROM ' . dteQuoteIdentifier($table));
}

function dteNullHotelCount(PDO $pdo, string $table, array $columns): ?int
{
    if (!dteHasColumn($columns, 'hotel_id')) {
        return null;
    }

    return (int)dteScalar($pdo, 'SELECT COUNT(*) FROM ' . dteQuoteIdentifier($table) . ' WHERE hotel_id IS NULL');
}

function dtePrintRows(array $rows, array $columns): void
{
    foreach ($rows as $row) {
        $parts = [];
        foreach ($columns as $column) {
            $parts[] = $column . '=' . (string)($row[$column] ?? '-');
        }
        echo '- ' . implode(' | ', $parts) . "\n";
    }
}

function dtePrintSummary(): void
{
    global $ok, $warnings, $errors, $recommendations;

    $recommendations = array_values(array_unique(array_filter($recommendations)));

    echo "=====================================================\n";
    echo "Resumen\n";
    echo "OK: {$ok}\n";
    echo "WARNING: {$warnings}\n";
    echo "ERROR: {$errors}\n";

    if ($recommendations) {
        echo "Recomendaciones concretas:\n";
        foreach ($recommendations as $recommendation) {
            echo "- {$recommendation}\n";
        }
    }

    echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
}

echo "Diagnostico read-only de fuentes del tablero ejecutivo\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    dteError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
    dtePrintSummary();
    exit(1);
}

$pdo = null;
$database = '';

try {
    $dbConfig = require $configPath;
    $database = (string)($dbConfig['database'] ?? '');
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'] ?? 'db',
        getenv('DB_PORT') ?: '3306',
        $database,
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    $pdo->exec('START TRANSACTION READ ONLY');
    dteOk('Conexion read-only inicializada para ' . $database . '.');
} catch (Throwable $e) {
    dteError('No se pudo conectar en modo read-only: ' . $e->getMessage(), 'Revisar contenedores y credenciales locales.');
    dtePrintSummary();
    exit(1);
}

dteSection('Modelo tablero ejecutivo');
if (!is_file($modelPath)) {
    dteError('No se encontro TableroEjecutivo.php.', 'Revisar estructura del proyecto antes de validar el warning.');
} else {
    $modelCode = file_get_contents($modelPath);
    if ($modelCode === false) {
        dteError('No se pudo leer TableroEjecutivo.php.', 'Revisar permisos locales del archivo.');
    } else {
        if (
            strpos($modelCode, 'START TRANSACTION READ ONLY') !== false
            && strpos($modelCode, 'rollBack') !== false
            && strpos($modelCode, 'ledger_laboral') !== false
            && strpos($modelCode, 'auditoria_sin_hotel') !== false
        ) {
            dteOk('TableroEjecutivo conserva lectura read-only, rollback, ledger opcional y auditoria sin hotel como alerta informativa.');
        } else {
            dteWarning(
                'No se pudo confirmar todo el contrato estatico del modelo TableroEjecutivo.',
                'Revisar manualmente el modelo antes de cerrar el warning del tablero.'
            );
        }
    }
}

dteSection('Fuente laboral opcional');
$laborTables = [
    'ledger_laboral',
    'trabajadores',
    'trabajador_pagos',
    'trabajador_pagos_caja',
    'trabajador_conceptos',
    'trabajador_anticipos',
    'trabajador_prestamos',
    'trabajador_asistencias',
    'trabajador_nomina_periodos',
    'trabajador_nomina_periodo_detalles',
    'trabajador_nomina_periodo_eventos',
];

$availableLaborSources = 0;
foreach ($laborTables as $table) {
    if (!dteTableExists($pdo, $database, $table)) {
        if ($table === 'ledger_laboral') {
            dteWarning(
                'ledger_laboral no existe; es una fuente opcional del tablero.',
                'Mantener el KPI laboral pendiente degradado hasta autorizar una fuente dedicada.'
            );
        } else {
            dteOk('Fuente laboral alternativa no instalada: ' . $table . ' (opcional para este diagnostico).');
        }
        continue;
    }

    $availableLaborSources++;
    $columns = dteTableColumns($pdo, $database, $table);
    $count = dteTableCount($pdo, $table);
    $nullHotelCount = dteNullHotelCount($pdo, $table, $columns);

    $message = 'Fuente laboral disponible: ' . $table . ' con ' . (string)$count . ' fila(s).';
    if ($nullHotelCount === null) {
        $message .= ' Sin hotel_id directo.';
        dteOk($message);
    } elseif ($nullHotelCount === 0) {
        $message .= ' Scope hotel_id sin nulos.';
        dteOk($message);
    } else {
        dteWarning(
            $message . ' hotel_id nulo en ' . (string)$nullHotelCount . ' fila(s).',
            'No usar ' . $table . ' como fuente gerencial directa sin resolver scope multihotel.'
        );
    }
}

if ($availableLaborSources > 0) {
    dteOk('El modulo de Personal/Nomina tiene fuentes alternativas disponibles; la ausencia de ledger_laboral no bloquea la pantalla.');
} else {
    dteWarning(
        'No se encontraron fuentes laborales alternativas instaladas.',
        'Mantener la seccion Personal del tablero en modo degradado hasta completar el modulo laboral.'
    );
}

dteSection('Auditoria sin hotel_id');
if (!dteTableExists($pdo, $database, 'logs_auditoria')) {
    dteError('No existe logs_auditoria.', 'El tablero ejecutivo requiere degradar o completar esta fuente.');
} else {
    $columns = dteTableColumns($pdo, $database, 'logs_auditoria');
    $missing = array_values(array_diff(['id', 'hotel_id', 'accion', 'created_at'], $columns));
    if ($missing) {
        dteError(
            'logs_auditoria existe pero faltan columnas esperadas: ' . implode(', ', $missing) . '.',
            'Revisar schema antes de usar auditoria en el tablero ejecutivo.'
        );
    } else {
        $total = dteTableCount($pdo, 'logs_auditoria');
        $withoutHotel = (int)dteScalar($pdo, 'SELECT COUNT(*) FROM logs_auditoria WHERE hotel_id IS NULL');
        $withHotel = (int)dteScalar($pdo, 'SELECT COUNT(*) FROM logs_auditoria WHERE hotel_id IS NOT NULL');
        $recentWithoutHotel = (int)dteScalar(
            $pdo,
            'SELECT COUNT(*) FROM logs_auditoria WHERE hotel_id IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
        );
        $nonAuthWithoutHotel = null;
        if (dteHasColumn($columns, 'entidad_tipo')) {
            $nonAuthWithoutHotel = (int)dteScalar(
                $pdo,
                "SELECT COUNT(*)
                 FROM logs_auditoria
                 WHERE hotel_id IS NULL
                   AND NOT (entidad_tipo = 'auth' AND accion LIKE 'auth.%')"
            );
        }

        dteOk('logs_auditoria total: ' . (string)$total . ' fila(s).');
        dteOk('logs_auditoria con hotel_id: ' . (string)$withHotel . ' fila(s).');

        if ($withoutHotel === 0) {
            dteOk('logs_auditoria no tiene filas sin hotel_id.');
        } else {
            dteWarning(
                'logs_auditoria tiene ' . (string)$withoutHotel . ' fila(s) sin hotel_id.',
                'Tratarlas como auditoria historica/global hasta autorizar limpieza o backfill.'
            );
        }

        if ($nonAuthWithoutHotel === 0) {
            dteOk('Todos los logs sin hotel_id pertenecen a auth.* global; no son auditoria operativa de hotel.');
        } elseif ($nonAuthWithoutHotel !== null) {
            dteWarning(
                'Hay ' . (string)$nonAuthWithoutHotel . ' log(s) sin hotel_id que no pertenecen a auth.* global.',
                'Revisar esos eventos antes de clasificar el warning como global/historico.'
            );
        }

        if ($recentWithoutHotel === 0) {
            dteOk('No hay logs sin hotel_id creados en los ultimos 7 dias.');
        } elseif ($nonAuthWithoutHotel === 0) {
            dteOk('Los logs recientes sin hotel_id son auth.* globales; el tablero los mantiene fuera de metricas por hotel.');
        } else {
            dteWarning(
                'Hay ' . (string)$recentWithoutHotel . ' log(s) sin hotel_id creados en los ultimos 7 dias.',
                'Revisar origen reciente antes de clasificar el warning como historico.'
            );
        }

        $range = dteFetchAll(
            $pdo,
            'SELECT MIN(created_at) AS primer_log, MAX(created_at) AS ultimo_log
             FROM logs_auditoria
             WHERE hotel_id IS NULL'
        );
        if (!empty($range)) {
            dteOk(
                'Rango logs sin hotel_id: '
                . (string)($range[0]['primer_log'] ?? '-')
                . ' a '
                . (string)($range[0]['ultimo_log'] ?? '-')
                . '.'
            );
        }

        dteSection('Acciones sin hotel_id');
        $actions = dteFetchAll(
            $pdo,
            'SELECT COALESCE(NULLIF(accion, \'\'), \'(sin accion)\') AS accion,
                    COUNT(*) AS total,
                    MIN(created_at) AS primero,
                    MAX(created_at) AS ultimo
             FROM logs_auditoria
             WHERE hotel_id IS NULL
             GROUP BY COALESCE(NULLIF(accion, \'\'), \'(sin accion)\')
             ORDER BY total DESC, ultimo DESC
             LIMIT ' . $limit
        );
        if ($actions) {
            dtePrintRows($actions, ['accion', 'total', 'primero', 'ultimo']);
        } else {
            dteOk('No hay acciones sin hotel_id para listar.');
        }

        if (dteHasColumn($columns, 'entidad_tipo')) {
            dteSection('Entidades sin hotel_id');
            $entities = dteFetchAll(
                $pdo,
                'SELECT COALESCE(NULLIF(entidad_tipo, \'\'), \'(sin entidad)\') AS entidad_tipo,
                        COUNT(*) AS total,
                        MIN(created_at) AS primero,
                        MAX(created_at) AS ultimo
                 FROM logs_auditoria
                 WHERE hotel_id IS NULL
                 GROUP BY COALESCE(NULLIF(entidad_tipo, \'\'), \'(sin entidad)\')
                 ORDER BY total DESC, ultimo DESC
                 LIMIT ' . $limit
            );
            if ($entities) {
                dtePrintRows($entities, ['entidad_tipo', 'total', 'primero', 'ultimo']);
            } else {
                dteOk('No hay entidades sin hotel_id para listar.');
            }
        }

        dteSection('Muestras sin hotel_id');
        $sampleColumns = array_values(array_intersect(
            ['id', 'created_at', 'accion', 'entidad_tipo', 'entidad_id', 'usuario_id', 'user_id', 'ip_address'],
            $columns
        ));
        $samples = dteFetchAll(
            $pdo,
            'SELECT ' . implode(', ', array_map('dteQuoteIdentifier', $sampleColumns)) . '
             FROM logs_auditoria
             WHERE hotel_id IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        );
        if ($samples) {
            dtePrintRows($samples, $sampleColumns);
        } else {
            dteOk('No hay muestras de logs sin hotel_id.');
        }
    }
}

if ($pdo->inTransaction()) {
    $pdo->rollBack();
    dteOk('Transaccion read-only cerrada con rollback.');
}

dtePrintSummary();
