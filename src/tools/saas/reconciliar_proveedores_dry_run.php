<?php
/**
 * Reconciliacion dry-run de proveedores historicos.
 *
 * Solo lectura. No inserta, actualiza, borra ni altera datos.
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
    'source-db::',
    'target-db::',
    'target-hotel-slug::',
    'format::',
    'min-usos::',
    'help',
]);

if (isset($options['help'])) {
    echo "Uso:\n";
    echo "  php tools/saas/reconciliar_proveedores_dry_run.php [--source-db=medisoft_hoteles] [--target-db=medisoft_hoteles_import] [--target-hotel-slug=los-cedros] [--format=text|json] [--min-usos=1]\n";
    echo "\n";
    echo "La herramienta solo lee movimientos_caja.proveedor y proveedores; no modifica DB.\n";
    exit(0);
}

$configPath = dirname(__DIR__, 2) . '/config/database.php';
if (!is_file($configPath)) {
    echo "[ERROR] No se encontro config/database.php.\n";
    exit(1);
}

$dbConfig = require $configPath;

$sourceDb = (string)($options['source-db'] ?? 'medisoft_hoteles');
$targetDb = (string)($options['target-db'] ?? ($dbConfig['database'] ?? 'medisoft_hoteles_import'));
$targetHotelSlug = (string)($options['target-hotel-slug'] ?? 'los-cedros');
$format = strtolower((string)($options['format'] ?? 'text'));
$minUsos = max(1, (int)($options['min-usos'] ?? 1));

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

$host = $dbConfig['host'] ?? 'db';
$port = getenv('DB_PORT') ?: '3306';
$charset = $dbConfig['charset'] ?? 'utf8mb4';
$username = $dbConfig['username'] ?? 'medisoft_user';
$password = $dbConfig['password'] ?? '';
$optionsPdo = $dbConfig['options'] ?? [];

$dsn = "mysql:host={$host};port={$port};charset={$charset}";

try {
    $pdo = new PDO($dsn, $username, $password, $optionsPdo);
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    $pdo->exec('START TRANSACTION READ ONLY');

    $result = buildReport($pdo, $sourceDb, $targetDb, $targetHotelSlug, $minUsos);

    $pdo->rollBack();
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

if ($format === 'json') {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

renderTextReport($result);
exit(0);

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

function tableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function buildReport(PDO $pdo, string $sourceDb, string $targetDb, string $targetHotelSlug, int $minUsos): array
{
    foreach ([$sourceDb, $targetDb] as $database) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db');
        $stmt->execute(['db' => $database]);
        if ((int)$stmt->fetchColumn() === 0) {
            throw new RuntimeException("No existe la base {$database}");
        }
    }

    $sourceHasProviders = tableExists($pdo, $sourceDb, 'proveedores');
    $sourceHasMovements = tableExists($pdo, $sourceDb, 'movimientos_caja');
    $targetHasProviders = tableExists($pdo, $targetDb, 'proveedores');
    $targetHasMovements = tableExists($pdo, $targetDb, 'movimientos_caja');

    if (!$sourceHasMovements) {
        throw new RuntimeException("La base fuente {$sourceDb} no tiene movimientos_caja");
    }
    if (!$targetHasProviders) {
        throw new RuntimeException("La base destino {$targetDb} no tiene proveedores");
    }
    if (!tableExists($pdo, $targetDb, 'hoteles')) {
        throw new RuntimeException("La base destino {$targetDb} no tiene hoteles");
    }

    $hotel = fetchTargetHotel($pdo, $targetDb, $targetHotelSlug);
    if (!$hotel) {
        throw new RuntimeException("No se encontro hotel destino con slug {$targetHotelSlug}");
    }

    $catalog = fetchCatalogProviders($pdo, $targetDb, (int)$hotel['id']);
    $catalogByKey = [];
    foreach ($catalog as $provider) {
        $key = normalizeName((string)$provider['nombre']);
        if ((int)$provider['activo'] === 1) {
            $catalogByKey[$key] = $provider;
        }
    }

    $sourceCandidates = fetchMovementProviderCandidates($pdo, $sourceDb, null, $minUsos);
    $targetMovementCandidates = $targetHasMovements
        ? fetchMovementProviderCandidates($pdo, $targetDb, (int)$hotel['id'], 1)
        : [];

    $targetMovementByKey = [];
    foreach ($targetMovementCandidates as $candidate) {
        $targetMovementByKey[$candidate['nombre_key']] = $candidate;
    }

    $sourceKeyCounts = [];
    foreach ($sourceCandidates as $candidate) {
        $sourceKeyCounts[$candidate['nombre_key']] = ($sourceKeyCounts[$candidate['nombre_key']] ?? 0) + 1;
    }

    $classified = [];
    $summary = [
        'source_rows_with_provider' => countRowsWithProvider($pdo, $sourceDb, 'movimientos_caja', null),
        'source_distinct_provider_names' => count($sourceCandidates),
        'target_catalog_providers' => count($catalog),
        'target_movements_with_provider' => $targetHasMovements ? countRowsWithProvider($pdo, $targetDb, 'movimientos_caja', (int)$hotel['id']) : 0,
        'candidato_catalogo' => 0,
        'requiere_revision' => 0,
        'ya_existe_catalogo' => 0,
        'conflicto_nombre_normalizado' => 0,
    ];

    foreach ($sourceCandidates as $candidate) {
        $key = $candidate['nombre_key'];
        $status = 'candidato_catalogo';
        $reason = 'Nombre historico no existe como proveedor activo del hotel destino.';

        if (isset($catalogByKey[$key])) {
            $status = 'ya_existe_catalogo';
            $reason = 'Ya existe un proveedor activo con el mismo nombre normalizado.';
        } elseif (($sourceKeyCounts[$key] ?? 0) > 1) {
            $status = 'conflicto_nombre_normalizado';
            $reason = 'Hay mas de un texto historico con el mismo nombre normalizado.';
        } elseif (isAmbiguousProviderName((string)$candidate['nombre'])) {
            $status = 'requiere_revision';
            $reason = 'Texto historico ambiguo o generico; no conviene importarlo automaticamente.';
        }

        $summary[$status]++;

        $targetMovement = $targetMovementByKey[$key] ?? null;
        $classified[] = [
            'status' => $status,
            'reason' => $reason,
            'nombre' => $candidate['nombre'],
            'nombre_key' => $key,
            'usos_fuente' => $candidate['usos'],
            'primera_fecha' => $candidate['primera_fecha'],
            'ultima_fecha' => $candidate['ultima_fecha'],
            'total_monto' => $candidate['total_monto'],
            'tipos' => $candidate['tipos'],
            'categorias' => $candidate['categorias'],
            'usos_movimientos_destino' => $targetMovement['usos'] ?? 0,
            'proveedor_catalogo_id' => $catalogByKey[$key]['id'] ?? null,
        ];
    }

    return [
        'dry_run' => true,
        'writes_db' => false,
        'source_db' => $sourceDb,
        'target_db' => $targetDb,
        'target_hotel' => [
            'id' => (int)$hotel['id'],
            'nombre' => $hotel['nombre'],
            'slug' => $hotel['slug'],
        ],
        'source_has_proveedores_table' => $sourceHasProviders,
        'source_uses_movimientos_caja_proveedor' => true,
        'min_usos' => $minUsos,
        'summary' => $summary,
        'candidates' => $classified,
        'recommendation' => buildRecommendation($summary, $sourceHasProviders),
    ];
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

function fetchCatalogProviders(PDO $pdo, string $targetDb, int $hotelId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, hotel_id, nombre, rfc, activo, created_at, updated_at
         FROM ' . qi($targetDb) . '.proveedores
         WHERE hotel_id = :hotel_id
         ORDER BY nombre'
    );
    $stmt->execute(['hotel_id' => $hotelId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fetchMovementProviderCandidates(PDO $pdo, string $database, ?int $hotelId, int $minUsos): array
{
    if (!columnExists($pdo, $database, 'movimientos_caja', 'proveedor')) {
        return [];
    }

    $hasHotelId = columnExists($pdo, $database, 'movimientos_caja', 'hotel_id');
    $where = "proveedor IS NOT NULL AND TRIM(proveedor) <> ''";
    $params = ['min_usos' => $minUsos];

    if ($hotelId !== null && $hasHotelId) {
        $where .= ' AND hotel_id = :hotel_id';
        $params['hotel_id'] = $hotelId;
    }

    $stmt = $pdo->prepare(
        'SELECT TRIM(proveedor) AS nombre,
                COUNT(*) AS usos,
                MIN(created_at) AS primera_fecha,
                MAX(created_at) AS ultima_fecha,
                SUM(monto) AS total_monto,
                GROUP_CONCAT(DISTINCT tipo ORDER BY tipo SEPARATOR ", ") AS tipos,
                GROUP_CONCAT(DISTINCT categoria ORDER BY categoria SEPARATOR ", ") AS categorias
         FROM ' . qi($database) . '.movimientos_caja
         WHERE ' . $where . '
         GROUP BY TRIM(proveedor)
         HAVING COUNT(*) >= :min_usos
         ORDER BY usos DESC, nombre ASC'
    );
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) {
        $row['nombre_key'] = normalizeName((string)$row['nombre']);
        $row['usos'] = (int)$row['usos'];
        $row['tipos'] = (string)($row['tipos'] ?? '');
        $row['total_monto'] = (string)$row['total_monto'];
    }
    unset($row);

    return $rows;
}

function countRowsWithProvider(PDO $pdo, string $database, string $table, ?int $hotelId): int
{
    $hasHotelId = columnExists($pdo, $database, $table, 'hotel_id');
    $where = "proveedor IS NOT NULL AND TRIM(proveedor) <> ''";
    $params = [];

    if ($hotelId !== null && $hasHotelId) {
        $where .= ' AND hotel_id = :hotel_id';
        $params['hotel_id'] = $hotelId;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ' . qi($database) . '.' . qi($table) . '
         WHERE ' . $where
    );
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
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

function isAmbiguousProviderName(string $name): bool
{
    $key = normalizeName($name);
    $patterns = [
        '/^no se$/',
        '/^sin definir$/',
        '/^huesped$/',
        '/^huespedes$/',
        '/^na$/',
        '/^n\/a$/',
        '/^ninguno$/',
        '/^s\/n$/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $key)) {
            return true;
        }
    }

    return false;
}

function buildRecommendation(array $summary, bool $sourceHasProviders): string
{
    if (!$sourceHasProviders) {
        return 'La fuente no tiene tabla proveedores; tratar movimientos_caja.proveedor como candidatos, no como catalogo confiable.';
    }

    if (($summary['candidato_catalogo'] ?? 0) > 0) {
        return 'Revisar candidatos antes de crear un script de importacion idempotente.';
    }

    return 'No hay candidatos importables automaticos; mantener solo como evidencia historica.';
}

function renderTextReport(array $report): void
{
    echo "Reconciliacion proveedores dry-run\n";
    echo "===================================\n";
    echo "[OK] Modo: solo lectura; no escribe DB.\n";
    echo "[OK] Fuente: {$report['source_db']}\n";
    echo "[OK] Destino: {$report['target_db']}\n";
    echo "[OK] Hotel destino: {$report['target_hotel']['nombre']} ({$report['target_hotel']['slug']}, id {$report['target_hotel']['id']})\n";

    if ($report['source_has_proveedores_table']) {
        echo "[OK] La fuente tiene tabla proveedores.\n";
    } else {
        echo "[WARNING] La fuente no tiene tabla proveedores; se usan candidatos desde movimientos_caja.proveedor.\n";
    }

    echo "\nResumen\n";
    echo "-------\n";
    foreach ($report['summary'] as $key => $value) {
        echo str_pad($key, 36) . $value . "\n";
    }

    echo "\nCandidatos\n";
    echo "----------\n";
    if (!$report['candidates']) {
        echo "Sin candidatos.\n";
    }

    foreach ($report['candidates'] as $candidate) {
        echo '[' . strtoupper($candidate['status']) . '] ' . $candidate['nombre'] . "\n";
        echo '  usos_fuente=' . $candidate['usos_fuente']
            . ' destino_movimientos=' . $candidate['usos_movimientos_destino']
            . ' total=' . $candidate['total_monto'] . "\n";
        echo '  tipos=' . ($candidate['tipos'] ?: 'N/A') . "\n";
        echo '  fechas=' . ($candidate['primera_fecha'] ?? 'N/A') . ' -> ' . ($candidate['ultima_fecha'] ?? 'N/A') . "\n";
        echo '  categorias=' . ($candidate['categorias'] ?: 'N/A') . "\n";
        echo '  motivo=' . $candidate['reason'] . "\n";
    }

    echo "\nRecomendacion\n";
    echo "-------------\n";
    echo $report['recommendation'] . "\n";
}
