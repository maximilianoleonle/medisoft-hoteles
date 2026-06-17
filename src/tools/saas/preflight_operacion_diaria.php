<?php
/**
 * Preflight Fase OP-A/8A-A para tablero operativo diario read-only.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida que /operacion/diaria sea GET/read-only, scoped por hotel y sin Caja.
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

function opPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function opPfOk(string $message): void
{
    global $ok;
    $ok++;
    opPfLine('OK', $message);
}

function opPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    opPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function opPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    opPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function opPfQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function opPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function opPfColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function opPfCountRows(PDO $pdo, string $table): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . opPfQuote($table));

    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

function opPfParseRoutes(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = (string) file_get_contents($path);
    $raw = preg_replace('/^\s*\/\/.*$/m', '', $raw);
    preg_match_all(
        "/\\\$router->(get|post)\\('([^']+)'\\s*,\\s*\\[\\s*'controller'\\s*=>\\s*'([^']+)'\\s*,\\s*'action'\\s*=>\\s*'([^']+)'/s",
        $raw,
        $matches,
        PREG_SET_ORDER
    );

    $routes = [];
    foreach ($matches as $match) {
        $routes[] = [
            'method' => $match[1],
            'path' => trim($match[2], '/'),
            'controller' => $match[3],
            'action' => $match[4],
        ];
    }

    return $routes;
}

function opPfRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && (string)$route['path'] === $path) {
            return true;
        }
    }

    return false;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    opPfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/OperacionController.php';
$modelPath = $appRoot . '/app/models/OperacionDiaria.php';
$viewPath = $appRoot . '/app/views/operacion/diaria.php';
$sidebarPath = $appRoot . '/app/views/layout/sidebar.php';

echo "Preflight Fase OP-A/8A-A - Tablero operativo diario\n";
echo "=====================================================\n";

if (is_file($configPath)) {
    opPfOk('Configuracion de base detectada.');
} else {
    opPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
}

$pdo = null;
$database = '';
if (is_file($configPath)) {
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
        opPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        opPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $sourceTables = [
        'reservaciones',
        'reservacion_habitaciones',
        'habitaciones',
        'huespedes',
        'tareas_operativas',
        'tarea_eventos',
        'mantenimientos_habitaciones',
        'trabajadores',
        'documentos',
        'documento_entidades',
        'reservacion_pagos',
        'reservacion_abonos',
    ];

    foreach ($sourceTables as $table) {
        if (opPfTableExists($pdo, $database, $table)) {
            $count = opPfCountRows($pdo, $table);
            opPfOk('Fuente OP-A disponible: ' . $table . ' (' . (string)$count . ' registros).');
        } else {
            opPfWarning('Fuente OP-A no disponible: ' . $table . '.', 'El tablero debe degradar con estado vacio si falta ' . $table . '.');
        }
    }

    foreach ($sourceTables as $table) {
        if (opPfTableExists($pdo, $database, $table) && opPfColumnExists($pdo, $database, $table, 'hotel_id')) {
            opPfOk('Fuente OP-A con hotel_id: ' . $table . '.');
        } elseif ($table === 'huespedes' && opPfTableExists($pdo, $database, $table)) {
            opPfOk('Fuente OP-A sin hotel_id directo pero scoped por reservaciones: huespedes.');
        } elseif (opPfTableExists($pdo, $database, $table)) {
            opPfWarning('Fuente OP-A sin hotel_id: ' . $table . '.', 'No usar ' . $table . ' en OP-A sin scope alterno seguro.');
        }
    }
}

$routes = opPfParseRoutes($routesPath);
if (opPfRouteExists($routes, 'operacion/diaria', 'get')) {
    opPfOk('Ruta OP-A registrada: GET /operacion/diaria.');
} else {
    opPfError('Ruta OP-A faltante: GET /operacion/diaria.', 'Registrar solo GET /operacion/diaria para el tablero read-only.');
}

$forbiddenRoutes = [];
foreach ($routes as $route) {
    $method = strtoupper((string)$route['method']);
    $path = (string)$route['path'];
    $controller = strtolower((string)$route['controller']);
    $action = strtolower((string)$route['action']);
    $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;

    if (($path === 'operacion/diaria' || strpos($path, 'operacion/') === 0 || $controller === 'operacion')
        && $signature !== 'GET /operacion/diaria -> operacion::diaria') {
        $forbiddenRoutes[] = $signature;
    }
}

if ($forbiddenRoutes === []) {
    opPfOk('No hay rutas OP-A fuera del contrato read-only.');
} else {
    opPfError('Rutas OP-A fuera de alcance: ' . implode(' | ', $forbiddenRoutes), 'Retirar POST o rutas adicionales bajo /operacion.');
}

$controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$modelCode = is_file($modelPath) ? (string) file_get_contents($modelPath) : '';
$viewCode = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
$sidebarCode = is_file($sidebarPath) ? (string) file_get_contents($sidebarPath) : '';

if (
    $controllerCode !== ''
    && strpos($controllerCode, 'class OperacionController') !== false
    && strpos($controllerCode, 'function diariaAction') !== false
    && strpos($controllerCode, 'require_hotel_context') !== false
    && strpos($controllerCode, "require_hotel_module('dashboard')") !== false
    && strpos($controllerCode, 'operacion/diaria') !== false
) {
    opPfOk('OperacionController OP-A usa guardas de sesion, hotel y modulo dashboard.');
} else {
    opPfError('OperacionController OP-A no muestra guardas esperadas.', 'Validar requireAuth, hotel context, modulo dashboard y vista operacion/diaria.');
}

if (
    $modelCode !== ''
    && strpos($modelCode, 'class OperacionDiaria') !== false
    && strpos($modelCode, 'function reporteReadOnlyPorHotel') !== false
    && strpos($modelCode, 'hotel_id = ?') !== false
    && strpos($modelCode, 'cuentas_por_cobrar') !== false
    && strpos($modelCode, 'reservacion_pagos') !== false
    && strpos($modelCode, 'reservacion_abonos') !== false
    && strpos($modelCode, 'storage_path') === false
    && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\b/i', $modelCode)
) {
    opPfOk('OperacionDiaria OP-A/8A-A es read-only, filtra por hotel_id y expone KPIs CxC estimados sin storage_path.');
} else {
    opPfError('OperacionDiaria OP-A/8A-A no cumple contrato read-only.', 'Retirar escrituras, storage_path o consultas sin hotel_id.');
}

if (
    $viewCode !== ''
    && strpos($viewCode, 'Tablero operativo diario') !== false
    && strpos($viewCode, 'KPIs financieros estimados') !== false
    && strpos($viewCode, "url('cuentas-por-cobrar')") !== false
    && strpos($viewCode, 'method="POST"') === false
    && strpos($viewCode, '<form') === false
    && strpos($viewCode, 'csrf_field()') === false
    && strpos($viewCode, 'storage_path') === false
    && strpos($viewCode, 'movimientos_caja') === false
    && strpos($viewCode, "url('reservaciones/ver/'") !== false
    && strpos($viewCode, "url('tareas/'") !== false
) {
    opPfOk('Vista OP-A/8A-A es read-only, sin formularios ni rutas internas.');
} else {
    opPfError('Vista OP-A/8A-A no muestra contrato visual seguro.', 'Asegurar vista sin POST/form/storage/Caja y con enlaces GET seguros.');
}

if ($sidebarCode !== '' && strpos($sidebarCode, "url('operacion/diaria')") !== false && strpos($sidebarCode, '$mostrarOperacionDiaria') !== false) {
    opPfOk('Sidebar enlaza OP-A bajo guardas visuales de dashboard.');
} else {
    opPfWarning('Sidebar no enlaza OP-A.', 'Agregar navegacion solo si el modulo dashboard esta activo.');
}

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
exit($errors > 0 ? 1 : 0);
