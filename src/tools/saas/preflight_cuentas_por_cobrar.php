<?php
/**
 * Preflight Fase 7A-A para Cuentas por Cobrar read-only.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida que /cuentas-por-cobrar sea GET/read-only, derivado de reservaciones
 * y sin escrituras en Caja, pagos, abonos o facturacion.
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

function cxcPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cxcPfOk(string $message): void
{
    global $ok;
    $ok++;
    cxcPfLine('OK', $message);
}

function cxcPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    cxcPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cxcPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    cxcPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cxcPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function cxcPfCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int)$stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function cxcPfReportZero(string $label, ?int $count, string $recommendation, bool $warningOnly = false): void
{
    if ($count === null) {
        cxcPfWarning('No se pudo validar CxC: ' . $label . '.', 'Revisar manualmente antes de operar CxC.');
        return;
    }

    if ($count === 0) {
        cxcPfOk('Consistencia CxC OK: ' . $label . ' = 0.');
        return;
    }

    if ($warningOnly) {
        cxcPfWarning('Consistencia CxC pendiente de revisar: ' . $label . ' = ' . (string)$count . '.', $recommendation);
        return;
    }

    cxcPfError('Consistencia CxC fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function cxcPfParseRoutes(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = (string)file_get_contents($path);
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

function cxcPfRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && (string)$route['path'] === $path) {
            return true;
        }
    }

    return false;
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/CuentaPorCobrarController.php';
$modelPath = $appRoot . '/app/models/CuentaPorCobrar.php';
$viewPath = $appRoot . '/app/views/cuentas_por_cobrar/index.php';
$sidebarPath = $appRoot . '/app/views/layout/sidebar.php';

echo "Preflight Fase 7A-A - Cuentas por cobrar read-only\n";
echo "=====================================================\n";

if (is_file($configPath)) {
    cxcPfOk('Configuracion de base detectada.');
} else {
    cxcPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
        cxcPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        cxcPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    foreach (['reservaciones', 'huespedes', 'reservacion_pagos', 'reservacion_abonos', 'solicitudes_factura'] as $table) {
        if (cxcPfTableExists($pdo, $database, $table)) {
            cxcPfOk('Tabla fuente disponible: ' . $table . '.');
        } else {
            cxcPfError('Falta tabla fuente CxC: ' . $table . '.', 'No habilitar reporte CxC hasta reconciliar fuentes.');
        }
    }

    cxcPfReportZero(
        'reservaciones con hotel_id nulo',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservaciones WHERE hotel_id IS NULL'),
        'Completar hotel_id antes de exponer CxC.'
    );
    cxcPfReportZero(
        'pagos con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservacion_pagos p LEFT JOIN reservaciones r ON r.id = p.reservacion_id AND r.hotel_id = p.hotel_id WHERE p.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar pagos historicos antes de convertir CxC en operativa; el reporte read-only los excluye por hotel/reservacion.',
        true
    );
    cxcPfReportZero(
        'abonos con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservacion_abonos a LEFT JOIN reservaciones r ON r.id = a.reservacion_id AND r.hotel_id = a.hotel_id WHERE a.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar abonos antes de usar CxC.'
    );
    cxcPfReportZero(
        'solicitudes_factura con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM solicitudes_factura sf LEFT JOIN reservaciones r ON r.id = sf.reservacion_id AND r.hotel_id = sf.hotel_id WHERE sf.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar facturacion historica antes de convertir CxC en operativa; el reporte read-only solo enlaza solicitudes scoped por hotel/reservacion.',
        true
    );
    cxcPfReportZero(
        'reservaciones con saldo estimado negativo',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM (SELECT r.id, r.precio_total - COALESCE(p.total, 0) - COALESCE(a.total, 0) AS saldo FROM reservaciones r LEFT JOIN (SELECT hotel_id, reservacion_id, SUM(monto) AS total FROM reservacion_pagos GROUP BY hotel_id, reservacion_id) p ON p.hotel_id = r.hotel_id AND p.reservacion_id = r.id LEFT JOIN (SELECT hotel_id, reservacion_id, SUM(monto) AS total FROM reservacion_abonos GROUP BY hotel_id, reservacion_id) a ON a.hotel_id = r.hotel_id AND a.reservacion_id = r.id) x WHERE x.saldo < -0.009'),
        'Revisar excedentes antes de operar CxC real.',
        true
    );
}

$routes = cxcPfParseRoutes($routesPath);
if (cxcPfRouteExists($routes, 'cuentas-por-cobrar', 'get')) {
    cxcPfOk('Ruta CxC registrada: GET /cuentas-por-cobrar.');
} else {
    cxcPfError('No esta registrada GET /cuentas-por-cobrar.', 'Registrar solo ruta GET para 7A-A.');
}

foreach ($routes as $route) {
    if (strpos((string)$route['path'], 'cuentas-por-cobrar') === 0 && strtolower((string)$route['method']) !== 'get') {
        cxcPfError('Ruta CxC no permitida: ' . strtoupper((string)$route['method']) . ' /' . $route['path'] . '.', '7A-A solo permite GET/read-only.');
    }
}

$modelCode = is_file($modelPath) ? (string)file_get_contents($modelPath) : '';
$controllerCode = is_file($controllerPath) ? (string)file_get_contents($controllerPath) : '';
$viewCode = is_file($viewPath) ? (string)file_get_contents($viewPath) : '';
$sidebarCode = is_file($sidebarPath) ? (string)file_get_contents($sidebarPath) : '';

if (
    $modelCode !== ''
    && strpos($modelCode, 'class CuentaPorCobrar') !== false
    && strpos($modelCode, 'listarDerivadasPorHotel') !== false
    && strpos($modelCode, 'r.hotel_id = ?') !== false
    && strpos($modelCode, 'reservacion_pagos') !== false
    && strpos($modelCode, 'reservacion_abonos') !== false
    && strpos($modelCode, 'solicitudes_factura') !== false
    && !preg_match('/\b(INSERT|UPDATE|DELETE|ALTER|DROP)\b/i', $modelCode)
) {
    cxcPfOk('Modelo CxC es read-only y derivado por hotel.');
} else {
    cxcPfError('Modelo CxC no cumple contrato read-only.', 'Revisar CuentaPorCobrar.php.');
}

if (
    $controllerCode !== ''
    && strpos($controllerCode, 'require_hotel_context') !== false
    && strpos($controllerCode, "require_hotel_module('reservaciones')") !== false
    && strpos($controllerCode, 'indexAction') !== false
    && !preg_match('/function\s+\w+Action[^}]*validateCSRF/s', $controllerCode)
) {
    cxcPfOk('Controller CxC expone solo index GET protegido.');
} else {
    cxcPfWarning('Controller CxC requiere revision manual.', 'Asegurar auth, hotel, modulo reservaciones y ausencia de POST.');
}

if (
    $viewCode !== ''
    && strpos($viewCode, 'method="GET"') !== false
    && stripos($viewCode, 'saldo estimado') !== false
    && stripos($viewCode, 'no crea cobros') !== false
    && strpos($viewCode, 'movimientos_caja') === false
) {
    cxcPfOk('Vista CxC es GET/read-only y comunica saldo estimado.');
} else {
    cxcPfWarning('Vista CxC no muestra contrato read-only completo.', 'Revisar texto y formularios.');
}

if ($sidebarCode !== '' && strpos($sidebarCode, 'cuentas-por-cobrar') !== false) {
    cxcPfOk('Sidebar enlaza CxC read-only.');
} else {
    cxcPfWarning('Sidebar no enlaza CxC.', 'Agregar navegacion solo si es segura para el hotel.');
}

if ($pdo instanceof PDO) {
    try {
        $pdo->rollBack();
    } catch (Throwable $e) {
    }
}

echo "=====================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if (!empty($recommendations)) {
    echo "Recomendaciones concretas:\n";
    foreach (array_values(array_unique($recommendations)) as $recommendation) {
        echo "- {$recommendation}\n";
    }
}

echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
exit($errors > 0 ? 1 : 0);
