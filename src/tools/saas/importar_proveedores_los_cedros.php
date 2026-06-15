<?php
/**
 * Importacion idempotente de proveedores historicos de Los Cedros.
 *
 * Por defecto solo simula. Para escribir requiere --apply y --backup-file
 * apuntando a un respaldo existente.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo "[ERROR] APP_ENV debe ser local. Valor actual: " . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    exit(1);
}

$options = getopt('', [
    'apply',
    'backup-file::',
    'source-db::',
    'target-db::',
    'target-hotel-slug::',
    'review-file::',
    'format::',
    'help',
]);

if (isset($options['help'])) {
    echo "Uso:\n";
    echo "  php tools/saas/importar_proveedores_los_cedros.php [--format=text|json]\n";
    echo "  php tools/saas/importar_proveedores_los_cedros.php --apply --backup-file=/ruta/backup.sql\n";
    echo "\n";
    echo "Por defecto solo simula. No toca movimientos_caja ni crea compras/CxP/documentos.\n";
    exit(0);
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
if (!is_file($configPath)) {
    echo "[ERROR] No se encontro config/database.php.\n";
    exit(1);
}

$dbConfig = require $configPath;

$sourceDb = (string)($options['source-db'] ?? 'medisoft_hoteles');
$targetDb = (string)($options['target-db'] ?? ($dbConfig['database'] ?? 'medisoft_hoteles_import'));
$targetHotelSlug = (string)($options['target-hotel-slug'] ?? 'los-cedros');
$reviewFile = (string)($options['review-file'] ?? (__DIR__ . '/proveedores_los_cedros_reconciliation.json'));
$format = strtolower((string)($options['format'] ?? 'text'));
$apply = array_key_exists('apply', $options);
$backupFile = (string)($options['backup-file'] ?? '');

if (!in_array($format, ['text', 'json'], true)) {
    echo "[ERROR] Formato no soportado: {$format}. Usa text o json.\n";
    exit(1);
}

try {
    assertSafeIdentifier($sourceDb, 'source-db');
    assertSafeIdentifier($targetDb, 'target-db');
} catch (InvalidArgumentException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

if (!is_file($reviewFile)) {
    echo "[ERROR] No existe el archivo de revision: {$reviewFile}\n";
    exit(1);
}

if ($apply) {
    if ($backupFile === '') {
        echo "[ERROR] --apply requiere --backup-file=/ruta/backup.sql\n";
        exit(1);
    }

    if (!is_file($backupFile) || filesize($backupFile) <= 0) {
        echo "[ERROR] El backup indicado no existe o esta vacio: {$backupFile}\n";
        exit(1);
    }
}

try {
    $review = loadReviewFile($reviewFile);
    $pdo = openPdo($dbConfig);
    $report = executeImportPlan($pdo, $review, [
        'source_db' => $sourceDb,
        'target_db' => $targetDb,
        'target_hotel_slug' => $targetHotelSlug,
        'apply' => $apply,
        'backup_file' => $backupFile,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit($report['errors'] > 0 ? 1 : 0);
}

renderTextReport($report);
exit($report['errors'] > 0 ? 1 : 0);

function openPdo(array $dbConfig): PDO
{
    $host = $dbConfig['host'] ?? 'db';
    $port = getenv('DB_PORT') ?: '3306';
    $charset = $dbConfig['charset'] ?? 'utf8mb4';
    $dsn = "mysql:host={$host};port={$port};charset={$charset}";
    $options = $dbConfig['options'] ?? [];

    return new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', $options);
}

function assertSafeIdentifier(string $identifier, string $label): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException("Identificador invalido para {$label}: {$identifier}");
    }
}

function qi(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function loadReviewFile(string $reviewFile): array
{
    $raw = file_get_contents($reviewFile);
    if ($raw === false) {
        throw new RuntimeException("No se pudo leer {$reviewFile}");
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('JSON de revision invalido: ' . json_last_error_msg());
    }

    if (!isset($decoded['candidates']) || !is_array($decoded['candidates'])) {
        throw new RuntimeException('El archivo de revision no contiene candidates.');
    }

    return $decoded;
}

function executeImportPlan(PDO $pdo, array $review, array $runtime): array
{
    $sourceDb = (string)$runtime['source_db'];
    $targetDb = (string)$runtime['target_db'];
    $targetHotelSlug = (string)$runtime['target_hotel_slug'];
    $apply = (bool)$runtime['apply'];

    validateReviewMatchesRuntime($review, $runtime);

    if (!$apply) {
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->exec('START TRANSACTION READ ONLY');
    } else {
        $pdo->beginTransaction();
    }

    validateDatabaseState($pdo, $sourceDb, $targetDb);
    $hotel = fetchTargetHotel($pdo, $targetDb, $targetHotelSlug);
    if (!$hotel) {
        throw new RuntimeException("No se encontro hotel destino {$targetHotelSlug}");
    }

    $activeProviders = fetchActiveProviders($pdo, $targetDb, (int)$hotel['id']);
    $activeByKey = [];
    foreach ($activeProviders as $provider) {
        $activeByKey[normalizeName((string)$provider['nombre'])] = $provider;
    }

    $sourceEvidence = fetchSourceEvidence($pdo, $sourceDb);
    $reviewCandidates = normalizeReviewCandidates($review['candidates']);

    $summary = [
        'approved_in_review' => 0,
        'excluded_in_review' => 0,
        'would_insert' => 0,
        'inserted' => 0,
        'already_exists' => 0,
        'missing_source_evidence' => 0,
        'skipped_excluded' => 0,
        'duplicates_in_review' => 0,
    ];

    $items = [];
    $seenApprovedKeys = [];

    foreach ($reviewCandidates as $candidate) {
        $key = $candidate['nombre_key'];
        $approved = $candidate['approved'];
        $evidence = $sourceEvidence[$key] ?? null;

        if (!$approved) {
            $summary['excluded_in_review']++;
            $summary['skipped_excluded']++;
            $items[] = buildItem('skipped_excluded', $candidate, $evidence, 'Candidato excluido por archivo de revision.');
            continue;
        }

        $summary['approved_in_review']++;

        if (isset($seenApprovedKeys[$key])) {
            $summary['duplicates_in_review']++;
            $items[] = buildItem('duplicate_in_review', $candidate, $evidence, 'Nombre normalizado repetido en candidatos aprobados.');
            continue;
        }
        $seenApprovedKeys[$key] = true;

        if (!$evidence) {
            $summary['missing_source_evidence']++;
            $items[] = buildItem('missing_source_evidence', $candidate, null, 'No hay evidencia en movimientos_caja.proveedor; no se importa.');
            continue;
        }

        if (isset($activeByKey[$key])) {
            $summary['already_exists']++;
            $items[] = buildItem(
                'already_exists',
                $candidate,
                $evidence,
                'Ya existe proveedor activo con el mismo nombre normalizado.',
                (int)$activeByKey[$key]['id']
            );
            continue;
        }

        if ($apply) {
            $providerId = insertProvider($pdo, $targetDb, (int)$hotel['id'], $candidate, $evidence);
            $summary['inserted']++;
            $activeByKey[$key] = ['id' => $providerId, 'nombre' => $candidate['nombre']];
            $items[] = buildItem('inserted', $candidate, $evidence, 'Proveedor insertado.', $providerId);
        } else {
            $summary['would_insert']++;
            $items[] = buildItem('would_insert', $candidate, $evidence, 'Proveedor listo para insertar en modo apply.');
        }
    }

    $errors = $summary['missing_source_evidence'] + $summary['duplicates_in_review'];
    if ($apply && $errors > 0) {
        throw new RuntimeException('No se puede aplicar con candidatos inconsistentes.');
    }

    if ($apply) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }

    return [
        'phase' => '2L',
        'mode' => $apply ? 'apply' : 'dry-run',
        'writes_db' => $apply,
        'source_db' => $sourceDb,
        'target_db' => $targetDb,
        'target_hotel' => [
            'id' => (int)$hotel['id'],
            'nombre' => $hotel['nombre'],
            'slug' => $hotel['slug'],
        ],
        'backup_file' => $runtime['backup_file'] ?: null,
        'review_file' => realpath((string)($GLOBALS['reviewFile'] ?? '')) ?: (string)($GLOBALS['reviewFile'] ?? ''),
        'summary' => $summary,
        'errors' => $errors,
        'items' => $items,
    ];
}

function validateReviewMatchesRuntime(array $review, array $runtime): void
{
    $checks = [
        'source_db' => 'source_db',
        'target_db' => 'target_db',
        'target_hotel_slug' => 'target_hotel_slug',
    ];

    foreach ($checks as $reviewKey => $runtimeKey) {
        if (isset($review[$reviewKey]) && (string)$review[$reviewKey] !== (string)$runtime[$runtimeKey]) {
            throw new RuntimeException(
                "El archivo de revision declara {$reviewKey}={$review[$reviewKey]}, pero runtime usa {$runtime[$runtimeKey]}"
            );
        }
    }
}

function validateDatabaseState(PDO $pdo, string $sourceDb, string $targetDb): void
{
    foreach ([$sourceDb, $targetDb] as $database) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db');
        $stmt->execute(['db' => $database]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException("No existe la base {$database}");
        }
    }

    $required = [
        [$sourceDb, 'movimientos_caja'],
        [$targetDb, 'hoteles'],
        [$targetDb, 'proveedores'],
    ];

    foreach ($required as [$database, $table]) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
        );
        $stmt->execute(['db' => $database, 'table' => $table]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException("No existe {$database}.{$table}");
        }
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :db
           AND TABLE_NAME = "proveedores"
           AND INDEX_NAME IN ("uk_proveedores_hotel_rfc", "uk_proveedores_hotel_nombre_activo")'
    );
    $stmt->execute(['db' => $targetDb]);
    if ((int)$stmt->fetchColumn() < 2) {
        throw new RuntimeException('Faltan indices unicos Fase 2J en proveedores.');
    }
}

function fetchTargetHotel(PDO $pdo, string $targetDb, string $slug): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, slug
         FROM ' . qi($targetDb) . '.hoteles
         WHERE slug = :slug
         LIMIT 1'
    );
    $stmt->execute(['slug' => $slug]);
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    return $hotel ?: null;
}

function fetchActiveProviders(PDO $pdo, string $targetDb, int $hotelId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre
         FROM ' . qi($targetDb) . '.proveedores
         WHERE hotel_id = :hotel_id
           AND activo = 1
         ORDER BY nombre'
    );
    $stmt->execute(['hotel_id' => $hotelId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchSourceEvidence(PDO $pdo, string $sourceDb): array
{
    $stmt = $pdo->query(
        'SELECT TRIM(proveedor) AS nombre,
                COUNT(*) AS usos,
                MIN(created_at) AS primera_fecha,
                MAX(created_at) AS ultima_fecha,
                SUM(monto) AS total_monto,
                GROUP_CONCAT(DISTINCT tipo ORDER BY tipo SEPARATOR ", ") AS tipos,
                GROUP_CONCAT(DISTINCT categoria ORDER BY categoria SEPARATOR ", ") AS categorias
         FROM ' . qi($sourceDb) . '.movimientos_caja
         WHERE proveedor IS NOT NULL
           AND TRIM(proveedor) <> ""
         GROUP BY TRIM(proveedor)
         ORDER BY usos DESC, nombre ASC'
    );

    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $byKey = [];
    foreach ($rows ?: [] as $row) {
        $key = normalizeName((string)$row['nombre']);
        $byKey[$key] = [
            'nombre_fuente' => (string)$row['nombre'],
            'usos' => (int)$row['usos'],
            'primera_fecha' => $row['primera_fecha'],
            'ultima_fecha' => $row['ultima_fecha'],
            'total_monto' => (string)$row['total_monto'],
            'tipos' => (string)($row['tipos'] ?? ''),
            'categorias' => (string)($row['categorias'] ?? ''),
        ];
    }

    return $byKey;
}

function normalizeReviewCandidates(array $candidates): array
{
    $normalized = [];
    foreach ($candidates as $candidate) {
        if (!is_array($candidate) || trim((string)($candidate['nombre'] ?? '')) === '') {
            throw new RuntimeException('Candidato invalido en archivo de revision.');
        }

        $name = trim((string)$candidate['nombre']);
        $normalized[] = [
            'nombre' => $name,
            'nombre_key' => normalizeName($name),
            'approved' => (bool)($candidate['approved'] ?? false),
            'reason' => (string)($candidate['reason'] ?? ''),
        ];
    }

    return $normalized;
}

function insertProvider(PDO $pdo, string $targetDb, int $hotelId, array $candidate, array $evidence): int
{
    $notes = sprintf(
        'Importado desde movimientos_caja.proveedor en Fase 2L. Usos historicos: %d. Fechas: %s -> %s. Categorias: %s.',
        (int)$evidence['usos'],
        (string)$evidence['primera_fecha'],
        (string)$evidence['ultima_fecha'],
        (string)($evidence['categorias'] ?: 'N/A')
    );

    $stmt = $pdo->prepare(
        'INSERT INTO ' . qi($targetDb) . '.proveedores
            (hotel_id, nombre, razon_social, rfc, telefono, email, direccion, notas, activo, created_by, updated_by)
         VALUES
            (:hotel_id, :nombre, NULL, NULL, NULL, NULL, NULL, :notas, 1, NULL, NULL)'
    );
    $stmt->execute([
        'hotel_id' => $hotelId,
        'nombre' => $candidate['nombre'],
        'notas' => $notes,
    ]);

    return (int)$pdo->lastInsertId();
}

function buildItem(string $status, array $candidate, ?array $evidence, string $message, ?int $providerId = null): array
{
    return [
        'status' => $status,
        'nombre' => $candidate['nombre'],
        'nombre_key' => $candidate['nombre_key'],
        'approved' => $candidate['approved'],
        'message' => $message,
        'provider_id' => $providerId,
        'source' => $evidence,
    ];
}

function normalizeName(string $name): string
{
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    if (is_string($ascii) && $ascii !== '') {
        $name = $ascii;
    }

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($name, 'UTF-8');
    }

    return strtolower($name);
}

function renderTextReport(array $report): void
{
    echo "Importacion proveedores Los Cedros - Fase 2L\n";
    echo "============================================\n";
    echo "[OK] Modo: {$report['mode']}\n";
    echo "[OK] Fuente: {$report['source_db']}\n";
    echo "[OK] Destino: {$report['target_db']}\n";
    echo "[OK] Hotel: {$report['target_hotel']['nombre']} ({$report['target_hotel']['slug']}, id {$report['target_hotel']['id']})\n";
    echo '[OK] Escribe DB: ' . ($report['writes_db'] ? 'si' : 'no') . "\n";
    if ($report['backup_file']) {
        echo "[OK] Backup confirmado: {$report['backup_file']}\n";
    }

    echo "\nResumen\n";
    echo "-------\n";
    foreach ($report['summary'] as $key => $value) {
        echo str_pad($key, 30) . $value . "\n";
    }
    echo str_pad('errors', 30) . $report['errors'] . "\n";

    echo "\nDetalle\n";
    echo "-------\n";
    foreach ($report['items'] as $item) {
        echo '[' . strtoupper($item['status']) . '] ' . $item['nombre'] . "\n";
        echo '  ' . $item['message'] . "\n";
        if ($item['provider_id']) {
            echo '  proveedor_id=' . $item['provider_id'] . "\n";
        }
        if ($item['source']) {
            echo '  usos=' . $item['source']['usos']
                . ' fechas=' . $item['source']['primera_fecha'] . ' -> ' . $item['source']['ultima_fecha']
                . ' total=' . $item['source']['total_monto'] . "\n";
            echo '  categorias=' . ($item['source']['categorias'] ?: 'N/A') . "\n";
        }
    }

    if (!$report['writes_db']) {
        echo "\nPara aplicar despues de backup:\n";
        echo "php tools/saas/importar_proveedores_los_cedros.php --apply --backup-file=/ruta/backup.sql\n";
    }
}
