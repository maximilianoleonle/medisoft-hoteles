<?php
/**
 * Preflight Fase 2M/2N/2O/2P/2Q/2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3B/3C-B para Compras minimas y CxP controlada.
 *
 * Solo lectura. No crea tablas, rutas, migraciones ni datos.
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

function pfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function pfOk(string $message): void
{
    global $ok;
    $ok++;
    pfLine('OK', $message);
}

function pfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    pfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function pfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    pfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function pfQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function pfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function pfColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function pfCountRows(PDO $pdo, string $table, string $where = '1=1'): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . pfQuote($table) . ' WHERE ' . $where);

    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

function pfRouteRegex(string $route): string
{
    $quoted = preg_quote(trim($route, '/'), '#');
    $quoted = preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', $quoted);
    return '#^' . $quoted . '$#';
}

function pfParseRoutes(string $routesPath): array
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

function pfRouteExists(array $routes, string $path, ?string $method = null): bool
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
    pfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o de staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$docsPath = $projectRoot . '/docs/technical/purchasing_inventory_contract.md';
$purchaseServicePath = $appRoot . '/app/services/CompraService.php';
$purchaseControllerPath = $appRoot . '/app/controllers/CompraController.php';
$purchaseIndexViewPath = $appRoot . '/app/views/compras/index.php';
$purchaseFormViewPath = $appRoot . '/app/views/compras/form.php';
$purchaseDetailViewPath = $appRoot . '/app/views/compras/ver.php';
$purchaseReportViewPath = $appRoot . '/app/views/compras/reporte_recibidas.php';
$purchaseTestToolPath = $appRoot . '/tools/saas/probar_compra_service.php';
$purchaseReceptionPreflightPath = $appRoot . '/tools/saas/preflight_recepcion_compras.php';
$draftMigrationPath = $projectRoot . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql';
$officialMigrationPath = $projectRoot . '/migrations/20260615_002_fase_2n_compras_minimas.sql';

echo "Preflight Fase 2M/2N/2O/2P/2Q/2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3B/3C-B - Compras minimas y CxP controlada\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    pfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
    pfOk('Configuracion de base detectada.');
}

$pdo = null;
$database = '';
if (is_file($configPath)) {
    try {
        $dbConfig = require $configPath;
        $database = (string) ($dbConfig['database'] ?? '');
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
        pfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        pfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo) {
    $requiredTables = [
        'hoteles',
        'proveedores',
        'inventario_productos',
        'inventario_categorias',
        'movimientos_inventario',
        'logs_auditoria',
    ];

    foreach ($requiredTables as $table) {
        if (pfTableExists($pdo, $database, $table)) {
            pfOk('Tabla requerida disponible: ' . $table);
        } else {
            pfError('Tabla requerida faltante: ' . $table, 'No preparar compras hasta restaurar tablas base.');
        }
    }

    foreach (['proveedores', 'inventario_productos', 'inventario_categorias', 'movimientos_inventario'] as $table) {
        if (pfTableExists($pdo, $database, $table) && pfColumnExists($pdo, $database, $table, 'hotel_id')) {
            $nullRows = pfCountRows($pdo, $table, 'hotel_id IS NULL');
            if ($nullRows === 0) {
                pfOk($table . ' tiene hotel_id completo.');
            } else {
                pfError($table . ' tiene hotel_id NULL: ' . $nullRows, 'Corregir aislamiento antes de Compras.');
            }
        } else {
            pfError($table . ' no tiene hotel_id disponible.', 'No usar en Compras sin alcance por hotel.');
        }
    }

    if (pfTableExists($pdo, $database, 'proveedores')) {
        $losCedrosProviders = pfCountRows($pdo, 'proveedores', 'hotel_id = 1 AND activo = 1');
        if ($losCedrosProviders >= 29) {
            pfOk('Los Cedros tiene proveedores activos suficientes para diseno futuro: ' . $losCedrosProviders . '.');
        } else {
            pfWarning(
                'Los Cedros tiene pocos proveedores activos: ' . $losCedrosProviders,
                'Revisar catalogo antes de habilitar compras.'
            );
        }

        $duplicates = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT hotel_id, nombre_activo_key
                 FROM proveedores
                 WHERE activo = 1
                 GROUP BY hotel_id, nombre_activo_key
                 HAVING COUNT(*) > 1
             ) d"
        );
        $duplicateGroups = $duplicates ? (int) $duplicates->fetchColumn() : 0;
        if ($duplicateGroups === 0) {
            pfOk('No hay nombres activos duplicados en proveedores.');
        } else {
            pfError('Hay nombres activos duplicados en proveedores: ' . $duplicateGroups, 'Resolver antes de Compras.');
        }
    }

    if (pfTableExists($pdo, $database, 'inventario_productos')) {
        $activeProducts = pfCountRows($pdo, 'inventario_productos', 'hotel_id = 1 AND activo = 1');
        if ($activeProducts > 0) {
            pfOk('Los Cedros tiene productos de inventario activos: ' . $activeProducts . '.');
        } else {
            pfWarning('Los Cedros no tiene productos activos de inventario.', 'Compras no podra recibir productos sin catalogo activo.');
        }
    }

    $minimalPurchasingTables = [
        'compras' => [
            'id', 'hotel_id', 'proveedor_id', 'folio', 'fecha_compra',
            'fecha_recepcion', 'estado', 'subtotal', 'impuestos', 'total',
            'notas', 'recibida_por', 'cancelada_por', 'created_by',
            'updated_by', 'created_at', 'updated_at',
        ],
        'compra_detalles' => [
            'id', 'compra_id', 'hotel_id', 'producto_id', 'cantidad',
            'costo_unitario', 'subtotal', 'movimiento_inventario_id',
            'created_at', 'updated_at',
        ],
    ];
    foreach ($minimalPurchasingTables as $table => $columns) {
        if (!pfTableExists($pdo, $database, $table)) {
            pfWarning(
                'Tabla minima Fase 2O no existe aun: ' . $table,
                'Aplicar migracion oficial solo despues de backup y autorizacion.'
            );
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!pfColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            pfError(
                $table . ' existe pero faltan columnas: ' . implode(', ', $missingColumns),
                'Restaurar backup o reconciliar estructura antes de construir UI o recepcion.'
            );
        } else {
            pfOk($table . ' tiene estructura minima Fase 2O.');
        }

        if (pfColumnExists($pdo, $database, $table, 'hotel_id')) {
            $nullRows = pfCountRows($pdo, $table, 'hotel_id IS NULL');
            if ($nullRows === 0) {
                pfOk($table . ' tiene hotel_id completo.');
            } else {
                pfError($table . ' tiene hotel_id NULL: ' . $nullRows, 'Corregir aislamiento antes de Compras.');
            }
        }

        $rows = pfCountRows($pdo, $table);
        if ($rows === 0) {
            pfOk($table . ' esta vacia o sin borradores operativos.');
        } elseif ($table === 'compras') {
            $nonDraftRows = pfCountRows($pdo, 'compras', "estado <> 'borrador'");
            if ($nonDraftRows === 0) {
                pfOk('compras contiene solo borradores Fase 2R: ' . $rows . '.');
            } else {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM compras
                     WHERE estado <> 'borrador'
                       AND NOT (estado = 'recibida' AND fecha_recepcion IS NOT NULL)"
                );
                $unexpectedNonDraftRows = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($unexpectedNonDraftRows === 0) {
                    pfOk('compras no borrador consistentes con recepcion Fase 2W: ' . $nonDraftRows . '.');
                } else {
                    pfWarning(
                        'compras no borrador inconsistentes: ' . $unexpectedNonDraftRows . ' de ' . $nonDraftRows,
                        'Auditar origen antes de habilitar pagos, CxP o cancelaciones.'
                    );
                }
            }
        } elseif ($table === 'compra_detalles') {
            $linkedMovements = pfCountRows($pdo, 'compra_detalles', 'movimiento_inventario_id IS NOT NULL');
            if ($linkedMovements === 0) {
                pfOk('compra_detalles no tiene movimientos de inventario vinculados.');
            } else {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM compra_detalles d
                     INNER JOIN compras c
                        ON c.id = d.compra_id
                       AND c.hotel_id = d.hotel_id
                     LEFT JOIN movimientos_inventario mi
                        ON mi.id = d.movimiento_inventario_id
                     WHERE d.movimiento_inventario_id IS NOT NULL
                       AND (c.estado <> 'recibida' OR mi.id IS NULL)"
                );
                $inconsistentLinkedMovements = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($inconsistentLinkedMovements === 0) {
                    pfOk('compra_detalles tiene movimientos vinculados consistentes con Fase 2W: ' . $linkedMovements . '.');
                } else {
                    pfWarning(
                        'compra_detalles tiene movimientos vinculados inconsistentes: ' . $inconsistentLinkedMovements . ' de ' . $linkedMovements,
                        'Auditar detalles antes de habilitar pagos, CxP o devoluciones.'
                    );
                }
            }
        }
    }

    if (pfTableExists($pdo, $database, 'migrations')) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM migrations
             WHERE nombre = '20260615_002_fase_2n_compras_minimas.sql'
               AND estado = 'ejecutada'"
        );
        $stmt->execute();
        if ((int) $stmt->fetchColumn() === 1) {
            pfOk('Migracion Fase 2O de compras minimas registrada como ejecutada.');
        } else {
            pfWarning(
                'Migracion Fase 2O de compras minimas no esta registrada como ejecutada.',
                'Registrar solo si la migracion fue aplicada correctamente.'
            );
        }
    }

    $futureTables = [
        'proveedor_contactos',
        'compra_pagos',
        'documentos_proveedor',
    ];
    foreach ($futureTables as $table) {
        if (pfTableExists($pdo, $database, $table)) {
            pfWarning(
                'Tabla futura ya existe fuera del alcance 2O: ' . $table,
                'Auditar origen antes de crear rutas o modelos de compras.'
            );
        } else {
            pfOk('Tabla futura aun no creada: ' . $table);
        }
    }

    if (pfTableExists($pdo, $database, 'cuentas_por_pagar') && pfTableExists($pdo, $database, 'cuentas_por_pagar_movimientos')) {
        pfOk('Tablas CxP Fase 3B existen para modo read-only.');
        pfOk('cuentas_por_pagar registros actuales: ' . (string)pfCountRows($pdo, 'cuentas_por_pagar'));
        pfOk('cuentas_por_pagar_movimientos registros actuales: ' . (string)pfCountRows($pdo, 'cuentas_por_pagar_movimientos'));
    } else {
        pfWarning(
            'Tablas CxP Fase 3B aun no existen.',
            'Aplicar migracion CxP solo despues de backup si se va a exponer la vista read-only.'
        );
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if (is_file($routesPath)) {
    $routes = pfParseRoutes($routesPath);
    $expectedPurchaseRoutes = [
        ['method' => 'get', 'path' => 'compras'],
        ['method' => 'get', 'path' => 'compras/crear'],
        ['method' => 'get', 'path' => 'compras/reportes/recibidas'],
        ['method' => 'get', 'path' => 'compras/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'compras'],
        ['method' => 'post', 'path' => 'compras/{id:[0-9]+}/recibir'],
    ];

    $missingPurchaseRoutes = [];
    foreach ($expectedPurchaseRoutes as $expectedRoute) {
        if (!pfRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingPurchaseRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingPurchaseRoutes)) {
        pfOk('Rutas Fase 2R de compras en borrador registradas.');
    } else {
        pfError(
            'Rutas Fase 2R faltantes: ' . implode(', ', $missingPurchaseRoutes),
            'Registrar solo listado, formulario, guardado de borrador y recepcion minima.'
        );
    }

    $forbiddenRoutes = [];
    foreach ($routes as $route) {
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $method = strtoupper((string) $route['method']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;

        $isAllowedPurchaseRoute = in_array($method . ' /' . $path, [
            'GET /compras',
            'GET /compras/crear',
            'GET /compras/reportes/recibidas',
            'GET /compras/{id:[0-9]+}',
            'POST /compras',
            'POST /compras/{id:[0-9]+}/recibir',
        ], true) && $controller === 'compra' && in_array($action, ['index', 'crear', 'reporterecibidas', 'ver', 'guardar', 'recibir'], true);

        $isAllowedCxpReadOnlyRoute = in_array($method . ' /' . $path, [
            'GET /cuentas-por-pagar',
            'GET /cuentas-por-pagar/generacion-preview',
            'POST /cuentas-por-pagar/generar-desde-compra/{id:[0-9]+}',
            'GET /cuentas-por-pagar/{id:[0-9]+}',
        ], true) && $controller === 'cuentaporpagar' && in_array($action, ['index', 'generacionpreview', 'generardesdecompra', 'ver'], true);

        if ($isAllowedPurchaseRoute || $isAllowedCxpReadOnlyRoute) {
            continue;
        }

        if (
            strpos($path, 'compras') !== false
            || strpos($path, 'cuentas-por-pagar') !== false
            || strpos($path, 'proveedor-contactos') !== false
            || strpos($path, 'documentos-proveedor') !== false
            || strpos($controller, 'compra') !== false
            || strpos($controller, 'cuentaporpagar') !== false
        ) {
            $forbiddenRoutes[] = $signature;
        }
    }

    if (empty($forbiddenRoutes)) {
        pfOk('Solo existen compras minimas y CxP/preview/generacion manual Fase 3B/3C-B; no hay pagos, contactos ni documentos.');
    } else {
        pfError(
            'Rutas fuera del alcance Fase 3C-B detectadas: ' . implode(' | ', $forbiddenRoutes),
            'Retirar rutas que no sean Compras minimas, GET de CxP/preview/detalle o POST generar CxP desde compra.'
        );
    }
} else {
    pfWarning('No se pudo leer config/routes.php.', 'Ejecutar desde el proyecto montado para validar rutas.');
}

$blockedFiles = [
    $appRoot . '/app/controllers/ComprasController.php',
    $appRoot . '/app/models/Compra.php',
];
$existingBlockedFiles = [];
foreach ($blockedFiles as $path) {
    if (file_exists($path)) {
        $existingBlockedFiles[] = str_replace($appRoot . '/', '', $path);
    }
}

if (!$existingBlockedFiles) {
    pfOk('No hay controladores/modelos legacy de compras avanzadas activos.');
} else {
    pfWarning(
        'Archivos de compras avanzadas detectados: ' . implode(', ', $existingBlockedFiles),
        'Auditar antes de registrar rutas o depender de ellos.'
    );
}

$cxpControllerPath = $appRoot . '/app/controllers/CuentaPorPagarController.php';
$cxpModelPath = $appRoot . '/app/models/CuentaPorPagar.php';
$cxpIndexViewPath = $appRoot . '/app/views/cuentas_por_pagar/index.php';
$cxpPreviewViewPath = $appRoot . '/app/views/cuentas_por_pagar/generacion_preview.php';
$cxpDetailViewPath = $appRoot . '/app/views/cuentas_por_pagar/ver.php';
if (
    is_file($cxpControllerPath)
    && is_file($cxpModelPath)
    && is_file($cxpIndexViewPath)
    && is_file($cxpPreviewViewPath)
    && is_file($cxpDetailViewPath)
) {
    $cxpCode = (string)file_get_contents($cxpControllerPath) . "\n"
        . (string)file_get_contents($cxpModelPath) . "\n"
        . (string)file_get_contents($cxpIndexViewPath) . "\n"
        . (string)file_get_contents($cxpPreviewViewPath) . "\n"
        . (string)file_get_contents($cxpDetailViewPath);
    if (
        strpos($cxpCode, 'CuentaPorPagarController') !== false
        && strpos($cxpCode, 'class CuentaPorPagar') !== false
        && strpos($cxpCode, 'cuentas_por_pagar/index') !== false
        && strpos($cxpCode, 'cuentas_por_pagar/generacion_preview') !== false
        && strpos($cxpCode, 'cuentas_por_pagar/ver') !== false
        && strpos($cxpCode, 'function generacionPreviewAction') !== false
        && strpos($cxpCode, 'function generarDesdeCompraAction') !== false
        && strpos($cxpCode, 'function previewGeneracionDesdeCompras') !== false
        && strpos($cxpCode, 'function generarDesdeCompraRecibida') !== false
        && strpos($cxpCode, 'method="POST"') !== false
        && strpos($cxpCode, 'validateCSRF') !== false
        && strpos($cxpCode, 'INSERT INTO cuentas_por_pagar') !== false
        && strpos($cxpCode, 'FOR UPDATE') !== false
        && strpos($cxpCode, 'movimientos_caja') === false
        && strpos($cxpCode, 'function pagarAction') === false
        && strpos($cxpCode, 'function abonarAction') === false
    ) {
        pfOk('CxP Fase 3B/3C-B existe con lectura, preview y generacion manual controlada.');
    } else {
        pfError(
            'CxP Fase 3C-B contiene tokens fuera de alcance o falta validacion central.',
            'Mantener solo POST manual con CSRF desde compra recibida, sin pagos ni movimientos_caja.'
        );
    }
}

if (is_file($purchaseServicePath)) {
    $service = (string) file_get_contents($purchaseServicePath);
    if (
        strpos($service, 'class CompraService') !== false
        && strpos($service, 'function crearBorrador') !== false
        && strpos($service, 'function obtenerCompra') !== false
        && strpos($service, 'function recibirCompra') !== false
        && strpos($service, 'function listarCompras') !== false
        && strpos($service, 'function catalogosBorrador') !== false
        && strpos($service, 'function catalogosReporteRecibidas') !== false
        && strpos($service, 'function reporteRecibidas') !== false
        && strpos($service, 'function filtrosReporteRecibidas') !== false
        && strpos($service, 'function assertCompraPuedeRecibirse') !== false
        && strpos($service, 'function assertDetallesPuedenRecibirse') !== false
        && strpos($service, 'ya fue recibida') !== false
        && strpos($service, 'otra sesion') !== false
        && strpos($service, 'COUNT(DISTINCT c.id)') !== false
        && strpos($service, 'GROUP BY p.id, p.nombre') !== false
        && strpos($service, 'GROUP BY ip.id, ip.codigo, ip.nombre, ip.unidad_medida') !== false
        && strpos($service, 'LEFT JOIN movimientos_inventario') !== false
        && strpos($service, 'movimiento_stock_posterior') !== false
        && strpos($service, 'beginTransaction') !== false
        && strpos($service, 'movimientos_inventario') !== false
        && strpos($service, "tipo_movimiento, cantidad") !== false
        && strpos($service, "'ENTRADA'") !== false
        && strpos($service, 'AuditService::record') !== false
        && strpos($service, 'movimientos_caja') === false
        && strpos($service, 'cuentas_por_pagar') === false
    ) {
        pfOk('CompraService Fase 2Z existe con contrato transaccional minimo, reportes read-only y guardas explicitas de recepcion.');
    } else {
        pfWarning(
            'CompraService Fase 2Z existe pero no declara todas las guardas esperadas.',
            'Revisar crearBorrador, recibirCompra, reporteRecibidas, assertCompraPuedeRecibirse, assertDetallesPuedenRecibirse y ausencia de Caja/CxP.'
        );
    }
} else {
    pfWarning(
        'CompraService Fase 2P no existe.',
        'Crear capa interna antes de exponer rutas o UI de compras.'
    );
}

if (is_file($purchaseControllerPath)) {
    $controller = (string) file_get_contents($purchaseControllerPath);
    $forbiddenControllerTokens = [
        'function pagarAction',
        'function cancelarAction',
        'movimientos_caja',
        'cuentas_por_pagar',
        'compra_pagos',
        'documentos_proveedor',
    ];
    $controllerForbidden = [];
    foreach ($forbiddenControllerTokens as $token) {
        if (strpos($controller, $token) !== false) {
            $controllerForbidden[] = $token;
        }
    }

    if (
        strpos($controller, 'class CompraController') !== false
        && strpos($controller, 'function indexAction') !== false
        && strpos($controller, 'function crearAction') !== false
        && strpos($controller, 'function verAction') !== false
        && strpos($controller, 'function reporteRecibidasAction') !== false
        && strpos($controller, 'function guardarAction') !== false
        && strpos($controller, 'function recibirAction') !== false
        && strpos($controller, "require_hotel_module('inventario')") !== false
        && strpos($controller, 'compras/ver') !== false
        && strpos($controller, 'compras/reporte_recibidas') !== false
        && strpos($controller, 'obtenerCompra') !== false
        && strpos($controller, 'reporteRecibidas') !== false
        && strpos($controller, 'catalogosReporteRecibidas') !== false
        && strpos($controller, 'crearBorrador') !== false
        && strpos($controller, 'recibirCompra') !== false
        && strpos($controller, 'validateCSRF') !== false
        && empty($controllerForbidden)
    ) {
        pfOk('CompraController Fase 2Y expone listado, detalle, reporte read-only, borrador y recepcion minima bajo modulo inventario.');
    } else {
        pfError(
            'CompraController Fase 2Y no cumple el alcance minimo o contiene tokens prohibidos: ' . (empty($controllerForbidden) ? 'sin detalle' : implode(', ', $controllerForbidden)),
            'Mantener solo indexAction, crearAction, verAction, reporteRecibidasAction, guardarAction y recibirAction; sin pagos, CxP, documentos ni caja.'
        );
    }
} else {
    pfError(
        'CompraController Fase 2R no existe.',
        'Crear solo controlador minimo para borradores antes de exponer rutas /compras.'
    );
}

if (is_file($purchaseIndexViewPath) && is_file($purchaseFormViewPath) && is_file($purchaseDetailViewPath) && is_file($purchaseReportViewPath)) {
    $indexView = (string) file_get_contents($purchaseIndexViewPath);
    $formView = (string) file_get_contents($purchaseFormViewPath);
    $detailView = (string) file_get_contents($purchaseDetailViewPath);
    $reportView = (string) file_get_contents($purchaseReportViewPath);
    $viewsCode = $indexView . "\n" . $formView . "\n" . $detailView . "\n" . $reportView;

    if (
        strpos($indexView, "url('compras/crear')") !== false
        && strpos($indexView, "url('compras/reportes/recibidas')") !== false
        && strpos($indexView, "url('compras/' . (int)") !== false
        && strpos($formView, "action=\"<?= url('compras') ?>\"") !== false
        && strpos($formView, 'csrf_field()') !== false
        && strpos($formView, 'name="producto_id[]"') !== false
        && strpos($formView, 'name="cantidad[]"') !== false
        && strpos($formView, 'name="costo_unitario[]"') !== false
        && strpos($detailView, 'movimiento_inventario_id') !== false
        && strpos($detailView, 'movimiento_stock_posterior') !== false
        && strpos($detailView, "url('compras?estado=") !== false
        && strpos($detailView, "url('compras/reportes/recibidas')") !== false
        && strpos($reportView, "action=\"<?= url('compras/reportes/recibidas') ?>\"") !== false
        && strpos($reportView, 'por_proveedor') !== false
        && strpos($reportView, 'por_producto') !== false
        && strpos($reportView, "url('compras/' . (int)") !== false
        && strpos($viewsCode, '/recibir') !== false
        && strpos($viewsCode, 'purchase-btn-receive') !== false
        && strpos($viewsCode, "url('compras/pagar") === false
        && strpos($viewsCode, 'cuentas-por-pagar') === false
    ) {
        pfOk('Vistas Fase 2Y de Compras permiten reporte read-only, detalle, borrador y recepcion minima con CSRF.');
    } else {
        pfError(
            'Vistas Fase 2Y de Compras incompletas o con enlaces fuera de alcance.',
            'Revisar reporte read-only, detalle, form CSRF, action POST /compras, recepcion minima y ausencia de pagos/CxP.'
        );
    }
} else {
    pfError(
        'Faltan vistas Fase 2Y de Compras.',
        'Crear app/views/compras/index.php, app/views/compras/form.php, app/views/compras/ver.php y app/views/compras/reporte_recibidas.php.'
    );
}

if (is_file($purchaseTestToolPath)) {
    $tool = (string) file_get_contents($purchaseTestToolPath);
    if (
        strpos($tool, 'ROLLBACK_TEST') !== false
        && strpos($tool, '--backup-file') !== false
        && strpos($tool, '--duplicate-line') !== false
        && strpos($tool, 'duplicate_product_rule') !== false
        && strpos($tool, 'runRollbackExercise') !== false
        && strpos($tool, "['manage_transaction' => false]") !== false
        && strpos($tool, 'rollBack') !== false
        && strpos($tool, 'commit()') === false
    ) {
        pfOk('Herramienta Fase 2Q/2U de prueba CompraService existe y fuerza rollback.');
    } else {
        pfWarning(
            'Herramienta Fase 2Q/2U de prueba CompraService existe pero no declara guardas completas.',
            'Revisar backup obligatorio, confirmacion ROLLBACK_TEST, duplicate-line, transaccion externa y ausencia de commit.'
        );
    }
} else {
    pfWarning(
        'Herramienta Fase 2Q de prueba CompraService no existe.',
        'Crear script CLI de prueba antes de exponer rutas o UI.'
    );
}

if (is_file($purchaseReceptionPreflightPath)) {
    $tool = (string) file_get_contents($purchaseReceptionPreflightPath);
    if (
        strpos($tool, 'Preflight Fase 2T') !== false
        && strpos($tool, 'Fase 2U') !== false
        && strpos($tool, 'Fase 2W') !== false
        && strpos($tool, 'Solo lectura') !== false
        && strpos($tool, 'START TRANSACTION READ ONLY') !== false
        && strpos($tool, 'productos_repetidos') !== false
        && strpos($tool, 'detalles_con_movimiento') !== false
    ) {
        pfOk('Preflight Fase 2T/2U/2V/2W/2X de recepcion existe y es solo lectura.');
    } else {
        pfWarning(
            'Preflight Fase 2T existe pero no declara guardas completas.',
            'Revisar productos repetidos, detalles con movimiento y transaccion de solo lectura.'
        );
    }
} else {
    pfWarning(
        'Preflight Fase 2T/2U de recepcion futura no existe.',
        'Crear preflight_recepcion_compras.php antes de exponer recepcion.'
    );
}

if (is_file($officialMigrationPath)) {
    pfOk('Migracion oficial Fase 2O de Compras existe en migrations/.');
} else {
    pfWarning(
        'No existe migracion oficial de Compras en migrations/.',
        'Promover el borrador solo despues de backup completo y autorizacion.'
    );
}

if (is_file($draftMigrationPath)) {
    $draft = (string) file_get_contents($draftMigrationPath);
    if (
        strpos($draft, 'BORRADOR NO EJECUTADO') !== false
        && strpos($draft, 'CREATE TABLE IF NOT EXISTS compras') !== false
        && strpos($draft, 'CREATE TABLE IF NOT EXISTS compra_detalles') !== false
        && strpos($draft, 'fk_compras_proveedor') !== false
        && strpos($draft, 'fk_compra_detalles_producto') !== false
        && strpos($draft, 'No toca movimientos_caja') !== false
    ) {
        pfOk('Borrador Fase 2N de compras minimas existe y declara guardas principales.');
    } else {
        pfWarning(
            'Borrador Fase 2N de compras minimas incompleto.',
            'Revisar tablas compras/compra_detalles, FKs y exclusiones de Caja/CxP antes de promover.'
        );
    }
} else {
    pfWarning(
        'No existe borrador Fase 2N de compras minimas.',
        'Crear docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql antes de promover una migracion.'
    );
}

if (is_file($docsPath)) {
    $doc = (string) file_get_contents($docsPath);
    if (
        strpos($doc, 'Actualizacion Fase 2M') !== false
        && strpos($doc, 'Sin movimientos de caja') !== false
        && strpos($doc, 'Sin cuentas por pagar') !== false
    ) {
        pfOk('Contrato Fase 2M documentado.');
    } else {
        pfWarning('Contrato Fase 2M incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2N') !== false
        && strpos($doc, '20260615_002_fase_2n_compras_minimas_draft.sql') !== false
        && strpos($doc, 'No ejecutar desde `docs/technical/sql_drafts`') !== false
    ) {
        pfOk('Contrato Fase 2N documentado.');
    } else {
        pfWarning('Contrato Fase 2N incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2O') !== false
        && strpos($doc, '20260615_002_fase_2n_compras_minimas.sql') !== false
        && strpos($doc, 'Sin rutas nuevas') !== false
    ) {
        pfOk('Contrato Fase 2O documentado.');
    } else {
        pfWarning('Contrato Fase 2O incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2P') !== false
        && strpos($doc, 'CompraService.php') !== false
        && strpos($doc, 'Sin rutas publicas') !== false
    ) {
        pfOk('Contrato Fase 2P documentado.');
    } else {
        pfWarning('Contrato Fase 2P incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2Q') !== false
        && strpos($doc, 'probar_compra_service.php') !== false
        && strpos($doc, 'ROLLBACK_TEST') !== false
    ) {
        pfOk('Contrato Fase 2Q documentado.');
    } else {
        pfWarning('Contrato Fase 2Q incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2R') !== false
        && strpos($doc, 'CompraController.php') !== false
        && strpos($doc, 'POST /compras') !== false
        && strpos($doc, 'Sin recepcion') !== false
    ) {
        pfOk('Contrato Fase 2R documentado.');
    } else {
        pfWarning('Contrato Fase 2R incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2S') !== false
        && strpos($doc, 'compras_no_borrador = 0') !== false
        && strpos($doc, 'detalles_con_movimiento = 0') !== false
        && strpos($doc, 'movimientos_recepcion_compra = 0') !== false
    ) {
        pfOk('Contrato Fase 2S documentado.');
    } else {
        pfWarning('Contrato Fase 2S incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con cierre de borrador real.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2T') !== false
        && strpos($doc, 'preflight_recepcion_compras.php') !== false
        && strpos($doc, 'productos repetidos') !== false
        && strpos($doc, 'Sin recepcion') !== false
    ) {
        pfOk('Contrato Fase 2T documentado.');
    } else {
        pfWarning('Contrato Fase 2T incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con preflight de recepcion.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2U') !== false
        && strpos($doc, '--duplicate-line') !== false
        && strpos($doc, 'un movimiento por linea') !== false
        && strpos($doc, 'ROLLBACK_TEST') !== false
    ) {
        pfOk('Contrato Fase 2U documentado.');
    } else {
        pfWarning('Contrato Fase 2U incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con regla de productos repetidos.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2V') !== false
        && strpos($doc, 'POST /compras/{id}/recibir') !== false
        && strpos($doc, 'CompraController::recibirAction()') !== false
        && strpos($doc, 'CompraService::recibirCompra()') !== false
        && strpos($doc, 'Sin pagos') !== false
    ) {
        pfOk('Contrato Fase 2V documentado.');
    } else {
        pfWarning('Contrato Fase 2V incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con recepcion minima.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2W') !== false
        && strpos($doc, 'compra #2') !== false
        && strpos($doc, 'movimiento_inventario_id') !== false
        && strpos($doc, 'Sin pagos') !== false
        && strpos($doc, 'Sin cuentas por pagar') !== false
    ) {
        pfOk('Contrato Fase 2W documentado.');
    } else {
        pfWarning('Contrato Fase 2W incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con recepcion real controlada.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2X') !== false
        && strpos($doc, 'GET /compras/{id}') !== false
        && strpos($doc, 'CompraController::verAction()') !== false
        && strpos($doc, 'compras/ver.php') !== false
        && strpos($doc, 'Sin pagos') !== false
    ) {
        pfOk('Contrato Fase 2X documentado.');
    } else {
        pfWarning('Contrato Fase 2X incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con detalle read-only de compra.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2Y') !== false
        && strpos($doc, 'GET /compras/reportes/recibidas') !== false
        && strpos($doc, 'CompraController::reporteRecibidasAction()') !== false
        && strpos($doc, 'CompraService::reporteRecibidas()') !== false
        && strpos($doc, 'compras/reporte_recibidas.php') !== false
        && strpos($doc, 'Sin pagos') !== false
    ) {
        pfOk('Contrato Fase 2Y documentado.');
    } else {
        pfWarning('Contrato Fase 2Y incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con reporte read-only de compras recibidas.');
    }

    if (
        strpos($doc, 'Actualizacion Fase 2Z') !== false
        && strpos($doc, 'assertCompraPuedeRecibirse()') !== false
        && strpos($doc, 'assertDetallesPuedenRecibirse()') !== false
        && strpos($doc, 'recepcion doble') !== false
        && strpos($doc, 'Sin pagos') !== false
    ) {
        pfOk('Contrato Fase 2Z documentado.');
    } else {
        pfWarning('Contrato Fase 2Z incompleto o no documentado.', 'Actualizar docs/technical/purchasing_inventory_contract.md con endurecimiento de compras minimas.');
    }
} else {
    pfWarning('No se encontro contrato de compras.', 'Crear docs/technical/purchasing_inventory_contract.md.');
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
