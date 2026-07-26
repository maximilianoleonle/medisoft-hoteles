<?php
/**
 * Preflight Fase MANT-A para reporte de mantenimiento read-only.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida que /reportes/mantenimiento sea GET/read-only y scoped por hotel.
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

function mantPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function mantPfOk(string $message): void
{
    global $ok;
    $ok++;
    mantPfLine('OK', $message);
}

function mantPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    mantPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function mantPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    mantPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function mantPfQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function mantPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function mantPfColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function mantPfCountRows(PDO $pdo, string $table): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . mantPfQuote($table));

    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

function mantPfCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function mantPfParseRoutes(string $path): array
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

function mantPfRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && (string)$route['path'] === $path) {
            return true;
        }
    }

    return false;
}

function mantPfMethodBody(string $code, string $method): string
{
    $needle = 'function ' . $method . '(';
    $start = strpos($code, $needle);
    if ($start === false) {
        return '';
    }

    $next = strpos($code, "\n    public function ", $start + strlen($needle));
    if ($next === false) {
        $next = strpos($code, "\n}", $start + strlen($needle));
    }

    if ($next === false) {
        return substr($code, $start);
    }

    return substr($code, $start, $next - $start);
}

function mantPfBodyIsReadOnly(string $body): bool
{
    return !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\b/i', $body);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    mantPfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/ReportesController.php';
$modelPath = $appRoot . '/app/models/Reporte.php';
$viewPath = $appRoot . '/app/views/reportes/mantenimiento.php';

echo "Preflight Fase MANT-A - Reporte mantenimiento read-only\n";
echo "=====================================================\n";

if (is_file($configPath)) {
    mantPfOk('Configuracion de base detectada.');
} else {
    mantPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
            getenv('DB_PORT') ?: ($dbConfig['port'] ?? '3306'),
            $database,
            $dbConfig['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->exec('START TRANSACTION READ ONLY');
        mantPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        mantPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    foreach (['mantenimientos_habitaciones', 'habitaciones'] as $table) {
        if (mantPfTableExists($pdo, $database, $table)) {
            mantPfOk('Fuente MANT disponible: ' . $table . ' (' . mantPfCountRows($pdo, $table) . ' registros).');
        } else {
            mantPfError('Fuente MANT faltante: ' . $table . '.', 'No exponer el reporte hasta reconciliar la tabla fuente.');
        }
    }

    foreach (['hotel_id', 'habitacion_id', 'estado', 'tipo_mantenimiento', 'prioridad', 'fecha_inicio', 'fecha_fin', 'created_at'] as $column) {
        if (mantPfTableExists($pdo, $database, 'mantenimientos_habitaciones') && mantPfColumnExists($pdo, $database, 'mantenimientos_habitaciones', $column)) {
            mantPfOk('mantenimientos_habitaciones contiene columna requerida: ' . $column . '.');
        } else {
            mantPfError('mantenimientos_habitaciones no contiene columna requerida: ' . $column . '.', 'No confiar en el reporte hasta reconciliar esquema de mantenimiento.');
        }
    }

    if (mantPfTableExists($pdo, $database, 'mantenimientos_habitaciones') && mantPfColumnExists($pdo, $database, 'mantenimientos_habitaciones', 'hotel_id')) {
        $nullHotel = mantPfCountScalar($pdo, 'SELECT COUNT(*) FROM mantenimientos_habitaciones WHERE hotel_id IS NULL');
        if ($nullHotel === 0) {
            mantPfOk('Mantenimientos sin hotel_id = 0.');
        } else {
            mantPfError('Mantenimientos sin hotel_id = ' . (string)$nullHotel . '.', 'Reconciliar hotel_id antes de confiar en reportes multihotel.');
        }
    }

    if (mantPfTableExists($pdo, $database, 'mantenimientos_habitaciones') && mantPfTableExists($pdo, $database, 'habitaciones')) {
        $orphanRooms = mantPfCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM mantenimientos_habitaciones m
             LEFT JOIN habitaciones h ON h.id = m.habitacion_id
             WHERE m.habitacion_id IS NOT NULL
               AND h.id IS NULL"
        );
        if ($orphanRooms === 0) {
            mantPfOk('Mantenimientos con habitacion inexistente = 0.');
        } else {
            mantPfWarning('Mantenimientos con habitacion inexistente = ' . (string)$orphanRooms . '.', 'Revisar datos historicos antes de usar mantenimiento como fuente operativa.');
        }

        $crossHotelRooms = mantPfCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM mantenimientos_habitaciones m
             JOIN habitaciones h ON h.id = m.habitacion_id
             WHERE m.hotel_id <> h.hotel_id"
        );
        if ($crossHotelRooms === 0) {
            mantPfOk('Mantenimientos vinculados a habitacion de otro hotel = 0.');
        } else {
            mantPfError('Mantenimientos vinculados a habitacion de otro hotel = ' . (string)$crossHotelRooms . '.', 'Bloquear automatizaciones hasta reconciliar habitacion_id/hotel_id.');
        }
    }

    if (mantPfTableExists($pdo, $database, 'mantenimientos_habitaciones')) {
        $invalidDates = mantPfCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM mantenimientos_habitaciones
             WHERE fecha_inicio IS NOT NULL
               AND fecha_fin IS NOT NULL
               AND fecha_fin < fecha_inicio"
        );
        if ($invalidDates === 0) {
            mantPfOk('Mantenimientos con fecha_fin menor a fecha_inicio = 0.');
        } else {
            mantPfWarning('Mantenimientos con fecha_fin menor a fecha_inicio = ' . (string)$invalidDates . '.', 'Revisar historico antes de calcular duraciones automaticas.');
        }
    }
}

$routes = mantPfParseRoutes($routesPath);
if (mantPfRouteExists($routes, 'reportes/mantenimiento', 'get')) {
    mantPfOk('Ruta MANT registrada: GET /reportes/mantenimiento.');
} else {
    mantPfError('Ruta MANT faltante: GET /reportes/mantenimiento.', 'No publicar navegacion sin ruta GET protegida.');
}

if (!mantPfRouteExists($routes, 'reportes/mantenimiento', 'post')) {
    mantPfOk('No existe POST /reportes/mantenimiento.');
} else {
    mantPfError('Existe POST /reportes/mantenimiento fuera de contrato.', 'Retirar POST; MANT-A debe ser solo lectura.');
}

$controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$modelCode = is_file($modelPath) ? (string) file_get_contents($modelPath) : '';
$viewCode = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';

if (
    $controllerCode !== ''
    && strpos($controllerCode, 'class ReportesController') !== false
    && strpos($controllerCode, 'function mantenimientoAction') !== false
    && strpos($controllerCode, 'is_authenticated()') !== false
    && strpos($controllerCode, "require_hotel_module('reportes')") !== false
    && strpos($controllerCode, '../views/reportes/mantenimiento.php') !== false
) {
    mantPfOk('ReportesController protege el reporte basico con sesion, permisos y modulo Reportes.');
} else {
    mantPfError('ReportesController no muestra guardas completas para mantenimiento.', 'Validar sesion, permisos, modulo reportes y vista de mantenimiento.');
}

$activeMethods = [
    'obtenerMantenimientosPorTipo' => 'hotel_id = ?',
    'obtenerMantenimientosPorPrioridad' => 'hotel_id = ?',
    'obtenerHabitacionesConMasMantenimientos' => 'h.hotel_id = ?',
    'obtenerRegistroMantenimientos' => 'm.hotel_id = ?',
    'obtenerTopResponsablesMantenimiento' => 'hotel_id = ?',
    'obtenerTendenciaMensualMantenimiento' => 'hotel_id = ?',
    'obtenerEstadisticasMantenimientoCompletas' => 'hotel_id = ?',
];

foreach ($activeMethods as $method => $scopeNeedle) {
    $body = mantPfMethodBody($modelCode, $method);
    if ($body === '') {
        mantPfError('Metodo activo faltante en Reporte: ' . $method . '.', 'No usar el reporte hasta restaurar el metodo.');
        continue;
    }

    if (strpos($body, $scopeNeedle) !== false && mantPfBodyIsReadOnly($body)) {
        mantPfOk('Metodo activo scoped/read-only: ' . $method . '.');
    } else {
        mantPfError('Metodo activo sin contrato multihotel/read-only: ' . $method . '.', 'Agregar filtro hotel_id y retirar escrituras en el metodo activo.');
    }
}

if (
    $viewCode !== ''
    && strpos($viewCode, 'Reporte de mantenimiento') !== false
    && strpos($viewCode, 'method="GET"') !== false
    && strpos($viewCode, 'method="POST"') === false
    && strpos($viewCode, 'csrf_field()') === false
    && strpos($viewCode, 'storage_path') === false
    && strpos($viewCode, "url('reportes/mantenimiento')") !== false
) {
    mantPfOk('Vista MANT-A es GET/read-only y no expone storage interno.');
} else {
    mantPfError('Vista MANT-A no cumple contrato read-only.', 'Revisar formulario GET unico, ausencia de POST/storage y ruta correcta.');
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
