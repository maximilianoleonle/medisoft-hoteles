<?php
/**
 * Preflight Fase 5E-T-A - Frontera de nomina oficial.
 *
 * Herramienta solo lectura. Verifica que el bloque actual de Personal/Nomina
 * siga siendo administrativo y no exponga nomina oficial, CFDI, timbrado,
 * dispersion bancaria ni pago masivo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$ok = 0;
$warnings = 0;
$errors = 0;
$recommendations = [];

function nomOfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function nomOfOk(string $message): void
{
    global $ok;
    $ok++;
    nomOfLine('OK', $message);
}

function nomOfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    nomOfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function nomOfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    nomOfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function nomOfReadFile(string $path, string $label): ?string
{
    if (!is_file($path)) {
        nomOfError('No se encontro ' . $label . '.', 'Ejecutar desde el arbol src del proyecto.');
        return null;
    }

    $code = file_get_contents($path);
    if ($code === false) {
        nomOfError('No se pudo leer ' . $label . '.', 'Revisar permisos del archivo.');
        return null;
    }

    nomOfOk($label . ' disponible para validacion estatica.');
    return (string)$code;
}

function nomOfParseRoutes(string $code): array
{
    preg_match_all(
        "/\\\$router->(get|post)\\('([^']+)'\\s*,\\s*\\[\\s*'controller'\\s*=>\\s*'([^']+)'\\s*,\\s*'action'\\s*=>\\s*'([^']+)'/s",
        $code,
        $matches,
        PREG_SET_ORDER
    );

    $routes = [];
    foreach ($matches as $match) {
        $routes[] = [
            'method' => strtolower((string)$match[1]),
            'path' => (string)$match[2],
            'controller' => (string)$match[3],
            'action' => (string)$match[4],
        ];
    }

    return $routes;
}

function nomOfContainsForbiddenToken(string $value): ?string
{
    $patterns = [
        'cfdi' => '/cfdi/i',
        'timbrado' => '/timbr/i',
        'dispersion' => '/dispers/i',
        'pago masivo' => '/pago[-_]?masivo/i',
        'nomina oficial' => '/nomina[-_]?oficial/i',
        'recibo fiscal' => '/recibo[-_]?fiscal/i',
        'uuid fiscal' => '/uuid[-_]?fiscal/i',
    ];

    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return $label;
        }
    }

    return null;
}

function nomOfRouteSignature(array $route): string
{
    return strtoupper((string)$route['method']) . ' ' . (string)$route['path']
        . ' -> ' . (string)$route['controller'] . '::' . (string)$route['action'];
}

function nomOfAssertNoForbiddenRoutes(array $routes): void
{
    $matches = [];
    foreach ($routes as $route) {
        $surface = implode(' ', [
            (string)($route['path'] ?? ''),
            (string)($route['controller'] ?? ''),
            (string)($route['action'] ?? ''),
        ]);
        $token = nomOfContainsForbiddenToken($surface);
        if ($token !== null) {
            $matches[] = nomOfRouteSignature($route) . ' [' . $token . ']';
        }
    }

    if ($matches === []) {
        nomOfOk('No hay rutas activas de nomina oficial, CFDI, timbrado, dispersion ni pago masivo.');
        return;
    }

    nomOfError(
        'Rutas prohibidas detectadas en la frontera de nomina oficial: ' . implode('; ', $matches) . '.',
        'Retirar esas rutas o abrir contrato formal de nomina oficial con autorizacion explicita, migraciones, permisos y QA independiente.'
    );
}

function nomOfListFiles(string $root, array $extensions): array
{
    if (!is_dir($root)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (in_array($extension, $extensions, true)) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);
    return $files;
}

function nomOfScanCodeSymbols(array $files): void
{
    $matches = [];
    foreach ($files as $file) {
        $code = (string)file_get_contents($file);
        preg_match_all('/\b(class|function)\s+([A-Za-z_][A-Za-z0-9_]*)/i', $code, $symbols, PREG_SET_ORDER);
        foreach ($symbols as $symbol) {
            $token = nomOfContainsForbiddenToken((string)$symbol[2]);
            if ($token !== null) {
                $matches[] = $file . '::' . (string)$symbol[2] . ' [' . $token . ']';
            }
        }
    }

    if ($matches === []) {
        nomOfOk('No hay clases ni metodos activos con nombres de nomina oficial, CFDI, timbrado, dispersion o pago masivo.');
        return;
    }

    nomOfError(
        'Simbolos prohibidos detectados: ' . implode('; ', $matches) . '.',
        'Mover cualquier implementacion oficial a una fase separada con contrato, permisos, migraciones y QA propios.'
    );
}

function nomOfScanMigrations(string $migrationsRoot): void
{
    if (!is_dir($migrationsRoot)) {
        nomOfOk('Directorio migrations no esta montado; validacion de archivos SQL omitida sin bloquear frontera oficial.');
        return;
    }

    $matches = [];
    foreach (nomOfListFiles($migrationsRoot, ['sql']) as $file) {
        $filenameToken = nomOfContainsForbiddenToken(basename($file));
        if ($filenameToken !== null) {
            $matches[] = basename($file) . ' [' . $filenameToken . ']';
            continue;
        }

        $sql = (string)file_get_contents($file);
        if (preg_match('/\b(CREATE|ALTER)\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $sql, $tableMatch) === 1) {
            $tableName = (string)$tableMatch[2];
            $tableToken = nomOfContainsForbiddenToken($tableName);
            if ($tableToken !== null) {
                $matches[] = basename($file) . ' tabla ' . $tableName . ' [' . $tableToken . ']';
            }
        }
    }

    if ($matches === []) {
        nomOfOk('Migraciones locales no exponen tablas de nomina oficial, CFDI, timbrado, dispersion ni pago masivo.');
        return;
    }

    nomOfError(
        'Migraciones prohibidas detectadas para frontera oficial: ' . implode('; ', $matches) . '.',
        'No promover migraciones oficiales de nomina sin contrato, backup, autorizacion explicita y plan de rollback SQL.'
    );
}

function nomOfOpenReadOnlyPdo(string $configPath): ?array
{
    if (!is_file($configPath)) {
        nomOfWarning('No se encontro config/database.php para validacion DB.', 'Ejecutar desde el arbol src del proyecto si se desea validar DB.');
        return null;
    }

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

        nomOfOk('Conexion DB de solo lectura inicializada para ' . $database . '.');
        return [$pdo, $database];
    } catch (Throwable $e) {
        nomOfWarning('No se pudo abrir conexion DB read-only: ' . $e->getMessage(), 'Revisar contenedores si se necesita validacion DB.');
        return null;
    }
}

function nomOfScanDatabaseObjects(?array $db): void
{
    if ($db === null) {
        return;
    }

    [$pdo, $database] = $db;
    if (!$pdo instanceof PDO || $database === '') {
        return;
    }

    $patterns = [
        '%nomina%cfdi%',
        '%nomina%timbr%',
        '%nomina%dispers%',
        '%nomina%oficial%',
        '%trabajador%cfdi%',
        '%trabajador%timbr%',
        '%trabajador%dispers%',
        '%pago%masivo%',
        '%recibo%fiscal%',
        '%uuid%fiscal%',
    ];

    $where = implode(' OR ', array_fill(0, count($patterns), 'LOWER(TABLE_NAME) LIKE ?'));
    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND (' . $where . ') ORDER BY TABLE_NAME'
    );
    $stmt->execute(array_merge([$database], $patterns));
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $where = implode(' OR ', array_fill(0, count($patterns), 'LOWER(COLUMN_NAME) LIKE ?'));
    $stmt = $pdo->prepare(
        'SELECT CONCAT(TABLE_NAME, ".", COLUMN_NAME) AS object_name
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND (' . $where . ')
         ORDER BY TABLE_NAME, COLUMN_NAME'
    );
    $stmt->execute(array_merge([$database], $patterns));
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($tables === [] && $columns === []) {
        nomOfOk('DB local no tiene objetos de nomina oficial, CFDI laboral, timbrado, dispersion ni pago masivo.');
    } else {
        nomOfError(
            'DB contiene objetos que parecen nomina oficial: tablas=' . implode(', ', $tables ?: ['ninguna'])
            . '; columnas=' . implode(', ', $columns ?: ['ninguna']) . '.',
            'Auditar esos objetos antes de avanzar; no usarlos sin contrato formal de nomina oficial.'
        );
    }

    try {
        $pdo->rollBack();
    } catch (Throwable $e) {
        // Read-only guard: no action needed if the connection already ended.
    }
}

function nomOfValidateApiSyncBlocked(string $apiControllerPath): void
{
    if (!is_file($apiControllerPath)) {
        nomOfWarning('ApiController no disponible para validar /api/sync.', 'Validar manualmente que /api/sync siga bloqueado.');
        return;
    }

    // Ola 1 (30-jul): el candado dejo de ser "423 a todo" y paso a ser una lista
    // blanca. Lo que importa para nomina es que NINGUNA operacion de dinero se
    // sincronice sin conexion; eso lo garantiza Sync::OPERACIONES_HABILITADAS.
    $code = (string)file_get_contents($apiControllerPath);
    require_once __DIR__ . '/../../app/models/Sync.php';
    $conDinero = array_intersect(Sync::OPERACIONES_HABILITADAS, ['pago_caja', 'gasto_caja', 'pre_corte_caja']);

    if (strpos($code, 'Sync::OPERACIONES_HABILITADAS') !== false && empty($conDinero)) {
        nomOfOk('/api/sync filtra por lista blanca y ninguna operacion de dinero esta habilitada offline.');
        return;
    }

    nomOfError(
        '/api/sync no muestra el candado esperado.',
        'syncAction debe filtrar contra Sync::OPERACIONES_HABILITADAS, y esa lista NO puede incluir operaciones de caja.'
    );
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    nomOfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$routesPath = $appRoot . '/config/routes.php';
$configPath = $appRoot . '/config/database.php';
$apiControllerPath = $appRoot . '/app/controllers/ApiController.php';
$docsRoot = dirname($appRoot) . '/docs';
$migrationsRoot = dirname($appRoot) . '/migrations';

echo "Preflight Fase 5E-T-A - Frontera nomina oficial\n";
echo "=====================================================\n";

$routesCode = nomOfReadFile($routesPath, 'config/routes.php');
if ($routesCode !== null) {
    $routes = nomOfParseRoutes($routesCode);
    nomOfOk('Rutas activas parseadas: ' . count($routes) . '.');
    nomOfAssertNoForbiddenRoutes($routes);
}

$codeFiles = [];
foreach ([
    $appRoot . '/app/controllers/TrabajadorController.php',
    $appRoot . '/app/models/Trabajador.php',
] as $payrollFile) {
    if (is_file($payrollFile)) {
        $codeFiles[] = $payrollFile;
    }
}

foreach (nomOfListFiles($appRoot . '/app/services', ['php']) as $serviceFile) {
    if (stripos(basename($serviceFile), 'Trabajador') !== false || stripos(basename($serviceFile), 'Nomina') !== false) {
        $codeFiles[] = $serviceFile;
    }
}

nomOfOk('Archivos PHP de superficie activa revisables: ' . count($codeFiles) . '.');
nomOfScanCodeSymbols($codeFiles);

nomOfScanMigrations($migrationsRoot);
nomOfScanDatabaseObjects(nomOfOpenReadOnlyPdo($configPath));
nomOfValidateApiSyncBlocked($apiControllerPath);

$contractPath = $docsRoot . '/fase_5E_T_0_contrato_frontera_nomina_oficial.md';
if (is_file($contractPath)) {
    nomOfOk('Contrato 5E-T-0 detectado: docs/fase_5E_T_0_contrato_frontera_nomina_oficial.md.');
} elseif (!is_dir($docsRoot)) {
    nomOfOk('Directorio docs no esta montado; validacion documental 5E-T-0 omitida sin bloquear preflight.');
} else {
    nomOfWarning(
        'Contrato 5E-T-0 aun no existe en docs.',
        'Crear contrato documental de frontera antes de avanzar hacia cualquier nomina oficial.'
    );
}

echo "=====================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if ($recommendations !== []) {
    echo "Recomendaciones concretas:\n";
    foreach (array_values(array_unique($recommendations)) as $recommendation) {
        echo '- ' . $recommendation . "\n";
    }
}

if ($errors > 0) {
    echo "Resultado general: FAIL\n";
    exit(1);
}

echo "Resultado general: PASS_WITH_WARNINGS_ALLOWED\n";
exit(0);
