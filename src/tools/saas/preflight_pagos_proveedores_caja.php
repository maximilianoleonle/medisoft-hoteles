<?php
/**
 * Preflight Fase 3D para egresos a proveedores con Caja.
 *
 * Valida simulador read-only y pago proveedor controlado. No modifica datos.
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

function ppcLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function ppcOk(string $message): void
{
    global $ok;
    $ok++;
    ppcLine('OK', $message);
}

function ppcWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    ppcLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function ppcError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    ppcLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function ppcQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function ppcTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function ppcColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function ppcCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . ppcQuote($table) . ' WHERE ' . $where);

    return $stmt ? (int)$stmt->fetchColumn() : 0;
}

function ppcParseRoutes(string $routesPath): array
{
    $raw = file_get_contents($routesPath);
    if ($raw === false) {
        return [];
    }

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
            'path' => $match[2],
            'controller' => $match[3],
            'action' => $match[4],
        ];
    }

    return $routes;
}

function ppcRouteExists(array $routes, string $path, ?string $method = null): bool
{
    $normalized = trim($path, '/');
    foreach ($routes as $route) {
        if ($method !== null && strtolower($route['method']) !== strtolower($method)) {
            continue;
        }
        if (trim($route['path'], '/') === $normalized) {
            return true;
        }
    }

    return false;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    ppcError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/CuentaPorPagarController.php';
$modelPath = $appRoot . '/app/models/CuentaPorPagar.php';
$viewPath = $appRoot . '/app/views/cuentas_por_pagar/simulador_caja.php';
$detailViewPath = $appRoot . '/app/views/cuentas_por_pagar/ver.php';
$paymentServicePath = $appRoot . '/app/services/CuentaPorPagarPagoService.php';
$rollbackTestPath = $appRoot . '/tools/saas/probar_pago_proveedor_caja.php';

echo "Preflight Fase 3D - Pago proveedor controlado con Caja\n";
echo "=====================================================\n";

$pdo = null;
$database = '';
if (!is_file($configPath)) {
    ppcError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
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
        ppcOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        ppcError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo) {
    $schema = [
        'cuentas_por_pagar' => ['id', 'hotel_id', 'proveedor_id', 'compra_id', 'estado', 'total', 'saldo'],
        'proveedores' => ['id', 'hotel_id', 'nombre', 'activo'],
        'compras' => ['id', 'hotel_id', 'proveedor_id', 'estado', 'total'],
        'cajas' => ['id', 'hotel_id', 'nombre', 'activa'],
        'cortes_caja' => ['id', 'hotel_id', 'caja_id', 'estado', 'fecha_apertura'],
        'cuentas_por_pagar_movimientos' => ['id', 'cuenta_por_pagar_id', 'hotel_id', 'tipo_movimiento', 'monto'],
        'movimientos_caja' => ['id', 'hotel_id', 'tipo', 'monto', 'corte_id'],
    ];

    foreach ($schema as $table => $columns) {
        if (!ppcTableExists($pdo, $database, $table)) {
            ppcError('Tabla requerida faltante: ' . $table, 'No avanzar a 3D-B hasta restaurar esta tabla.');
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!ppcColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            ppcError(
                'Tabla ' . $table . ' incompleta. Faltan columnas: ' . implode(', ', $missingColumns),
                'Reconciliar esquema antes de preparar egresos a proveedores.'
            );
        } else {
            ppcOk('Tabla ' . $table . ' disponible con columnas requeridas.');
        }
    }

    $cxp = ppcCountRows($pdo, 'cuentas_por_pagar');
    $cxpMovs = ppcCountRows($pdo, 'cuentas_por_pagar_movimientos');
    $cashMoves = ppcCountRows($pdo, 'movimientos_caja');
    $openCuts = ppcCountRows($pdo, 'cortes_caja', "estado = 'abierto'");
    $eligibleStmt = $pdo->query(
        "SELECT COUNT(*)
         FROM cuentas_por_pagar cxp
         INNER JOIN proveedores p
            ON p.id = cxp.proveedor_id
           AND p.hotel_id = cxp.hotel_id
         WHERE cxp.estado IN ('pendiente', 'parcial', 'vencida')
           AND cxp.saldo > 0"
    );
    $eligible = $eligibleStmt ? (int)$eligibleStmt->fetchColumn() : 0;

    ppcOk('CxP registradas: ' . $cxp . '.');
    ppcOk('Movimientos CxP actuales: ' . $cxpMovs . '.');
    ppcOk('Movimientos de Caja actuales: ' . $cashMoves . '.');
    ppcOk('Cortes abiertos detectados: ' . $openCuts . '.');
    ppcOk('CxP potencialmente elegibles por estado/saldo/proveedor: ' . $eligible . '.');

    $badHotel = ppcCountRows($pdo, 'cuentas_por_pagar', 'hotel_id IS NULL');
    if ($badHotel === 0) {
        ppcOk('CxP sin hotel_id = 0.');
    } else {
        ppcError('CxP sin hotel_id = ' . $badHotel . '.', 'Corregir aislamiento antes de cualquier egreso.');
    }

    $crossProvider = $pdo->query(
        "SELECT COUNT(*)
         FROM cuentas_por_pagar cxp
         JOIN proveedores p ON p.id = cxp.proveedor_id
         WHERE p.hotel_id <> cxp.hotel_id"
    );
    $crossProviderCount = $crossProvider ? (int)$crossProvider->fetchColumn() : 0;
    if ($crossProviderCount === 0) {
        ppcOk('CxP con proveedor de otro hotel = 0.');
    } else {
        ppcError('CxP con proveedor de otro hotel = ' . $crossProviderCount . '.', 'Bloquear 3D-B hasta reconciliar proveedores.');
    }

    $crossPurchase = $pdo->query(
        "SELECT COUNT(*)
         FROM cuentas_por_pagar cxp
         JOIN compras c ON c.id = cxp.compra_id
         WHERE cxp.compra_id IS NOT NULL
           AND c.hotel_id <> cxp.hotel_id"
    );
    $crossPurchaseCount = $crossPurchase ? (int)$crossPurchase->fetchColumn() : 0;
    if ($crossPurchaseCount === 0) {
        ppcOk('CxP con compra de otro hotel = 0.');
    } else {
        ppcError('CxP con compra de otro hotel = ' . $crossPurchaseCount . '.', 'Bloquear 3D-B hasta reconciliar compras.');
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if (is_file($routesPath)) {
    $routes = ppcParseRoutes($routesPath);
    if (ppcRouteExists($routes, 'cuentas-por-pagar/simulador-caja', 'get')) {
        ppcOk('Ruta GET /cuentas-por-pagar/simulador-caja registrada.');
    } else {
        ppcError('Falta GET /cuentas-por-pagar/simulador-caja.', 'Registrar solo ruta GET read-only para 3D-A.');
    }

    if (ppcRouteExists($routes, 'cuentas-por-pagar/{id:[0-9]+}/registrar-pago-caja', 'post')) {
        ppcOk('Ruta POST /cuentas-por-pagar/{id}/registrar-pago-caja registrada para 3D-B.');
    } else {
        ppcError('Falta POST controlado para registrar pago proveedor con Caja.', 'Registrar solo la ruta POST autorizada de Fase 3D-B.');
    }

    foreach ($routes as $route) {
        $method = strtoupper((string)$route['method']);
        $path = strtolower((string)$route['path']);
        $action = strtolower((string)$route['action']);
        $allowedPost = in_array($action, ['generardesdecompra', 'registrarpagocaja'], true);
        if ($method === 'POST' && strpos($path, 'cuentas-por-pagar') !== false && !$allowedPost) {
            ppcError(
                'POST CxP fuera del contrato 3D: /' . trim($path, '/') . ' -> ' . $route['action'],
                'Mantener solo generarDesdeCompra y registrarPagoCaja.'
            );
        }
    }
}

if (is_file($controllerPath) && is_file($modelPath) && is_file($viewPath)) {
    $controller = (string)file_get_contents($controllerPath);
    $model = (string)file_get_contents($modelPath);
    $view = (string)file_get_contents($viewPath);
    $code = $controller . "\n" . $model . "\n" . $view;

    if (
        strpos($controller, 'function simuladorCajaAction') !== false
        && strpos($model, 'function simuladorCajaProveedor') !== false
        && strpos($model, 'function corteAbiertoSimuladorCaja') !== false
        && strpos($view, 'method="GET"') !== false
        && strpos($view, 'method="POST"') === false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(cuentas_por_pagar_movimientos|movimientos_caja|cortes_caja|cajas)\b/i', $code)
        && strpos($controller, 'function pagarAction') === false
        && strpos($controller, 'function abonarAction') === false
    ) {
        ppcOk('Simulador 3D-A es GET/read-only y no contiene escrituras sobre CxP o Caja.');
    } else {
        ppcError(
            'Simulador 3D-A incompleto o contiene tokens de escritura.',
            'Mantener solo GET, SELECT y diagnostico; sin POST ni escrituras.'
        );
    }
} else {
    ppcError('Faltan archivos del simulador 3D-A.', 'Crear controlador/modelo/vista read-only antes de cerrar 3D-A.');
}

if (is_file($controllerPath) && is_file($detailViewPath) && is_file($paymentServicePath) && is_file($rollbackTestPath)) {
    $controller = (string)file_get_contents($controllerPath);
    $detailView = (string)file_get_contents($detailViewPath);
    $service = (string)file_get_contents($paymentServicePath);
    $rollbackTest = (string)file_get_contents($rollbackTestPath);

    if (
        strpos($controller, 'function registrarPagoCajaAction') !== false
        && strpos($controller, 'validateCSRF()') !== false
        && strpos($controller, "require_hotel_module('caja')") !== false
        && strpos($controller, 'consumirPagoToken') !== false
        && strpos($detailView, 'registrar-pago-caja') !== false
        && strpos($detailView, 'csrf_field()') !== false
        && strpos($detailView, 'name="pago_token"') !== false
    ) {
        ppcOk('Controlador y vista tienen POST pago Caja con CSRF, modulo Caja y token anti doble envio.');
    } else {
        ppcError(
            'Controlador/vista de pago Caja incompletos.',
            'Revisar CSRF, modulo Caja, token pago y accion POST del detalle de CxP.'
        );
    }

    if (
        strpos($service, 'class CuentaPorPagarPagoService') !== false
        && strpos($service, 'manage_transaction') !== false
        && strpos($service, 'FOR UPDATE') !== false
        && strpos($service, 'INSERT INTO cuentas_por_pagar_movimientos') !== false
        && strpos($service, 'INSERT INTO movimientos_caja') !== false
        && strpos($service, 'UPDATE cuentas_por_pagar') !== false
        && strpos($service, 'AuditService::record') !== false
        && strpos($service, "'pendiente', 'parcial', 'vencida'") !== false
        && strpos($service, 'La compra vinculada no esta recibida') !== false
    ) {
        ppcOk('Servicio de pago proveedor usa transaccion, locks, CxP, Caja, auditoria y validaciones de estado.');
    } else {
        ppcError(
            'Servicio de pago proveedor no cumple contrato transaccional minimo.',
            'Validar locks FOR UPDATE, escrituras CxP/Caja, auditoria y validaciones de compra recibida.'
        );
    }

    if (
        strpos($rollbackTest, "['manage_transaction' => false]") !== false
        && strpos($rollbackTest, 'rollBack()') !== false
        && strpos($rollbackTest, 'cuentas_por_pagar_movimientos') !== false
        && strpos($rollbackTest, 'movimientos_caja') !== false
    ) {
        ppcOk('Prueba rollback de pago proveedor disponible y no persistente.');
    } else {
        ppcError(
            'Falta prueba rollback segura para pago proveedor.',
            'Mantener una prueba CLI que inserte dentro de transaccion externa y haga rollback.'
        );
    }
} else {
    ppcError('Faltan archivos del pago proveedor 3D-B.', 'Crear servicio, detalle CxP y prueba rollback antes de QA.');
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
