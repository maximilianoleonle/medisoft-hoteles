<?php
/**
 * Preflight Fase 2M/2N/2O/2P/2Q/2R/2S para Compras minimas.
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
$purchaseTestToolPath = $appRoot . '/tools/saas/probar_compra_service.php';
$draftMigrationPath = $projectRoot . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql';
$officialMigrationPath = $projectRoot . '/migrations/20260615_002_fase_2n_compras_minimas.sql';

echo "Preflight Fase 2M/2N/2O/2P/2Q/2R/2S - Compras minimas\n";
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
                pfWarning(
                    'compras contiene registros no borrador: ' . $nonDraftRows,
                    'Auditar origen antes de habilitar recepcion, pagos o CxP.'
                );
            }
        } elseif ($table === 'compra_detalles') {
            $linkedMovements = pfCountRows($pdo, 'compra_detalles', 'movimiento_inventario_id IS NOT NULL');
            if ($linkedMovements === 0) {
                pfOk('compra_detalles no tiene movimientos de inventario vinculados.');
            } else {
                pfWarning(
                    'compra_detalles ya tiene movimientos de inventario vinculados: ' . $linkedMovements,
                    'Auditar origen antes de habilitar UI de recepcion.'
                );
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
        'cuentas_por_pagar',
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

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if (is_file($routesPath)) {
    $routes = pfParseRoutes($routesPath);
    $expectedPurchaseRoutes = [
        ['method' => 'get', 'path' => 'compras'],
        ['method' => 'get', 'path' => 'compras/crear'],
        ['method' => 'post', 'path' => 'compras'],
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
            'Registrar solo listado, formulario y guardado de borrador.'
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
            'POST /compras',
        ], true) && $controller === 'compra' && in_array($action, ['index', 'crear', 'guardar'], true);

        if ($isAllowedPurchaseRoute) {
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
        pfOk('No hay rutas de recepcion, pagos, CxP, contactos ni documentos de compras.');
    } else {
        pfError(
            'Rutas fuera del alcance Fase 2R detectadas: ' . implode(' | ', $forbiddenRoutes),
            'Retirar rutas que no sean GET /compras, GET /compras/crear y POST /compras.'
        );
    }
} else {
    pfWarning('No se pudo leer config/routes.php.', 'Ejecutar desde el proyecto montado para validar rutas.');
}

$blockedFiles = [
    $appRoot . '/app/controllers/ComprasController.php',
    $appRoot . '/app/controllers/CuentaPorPagarController.php',
    $appRoot . '/app/models/Compra.php',
    $appRoot . '/app/models/CuentaPorPagar.php',
    $appRoot . '/app/views/cuentas_por_pagar',
];
$existingBlockedFiles = [];
foreach ($blockedFiles as $path) {
    if (file_exists($path)) {
        $existingBlockedFiles[] = str_replace($appRoot . '/', '', $path);
    }
}

if (!$existingBlockedFiles) {
    pfOk('No hay controladores/modelos/vistas de CxP o compras avanzadas activos.');
} else {
    pfWarning(
        'Archivos de CxP o compras avanzadas detectados: ' . implode(', ', $existingBlockedFiles),
        'Auditar antes de registrar rutas o depender de ellos.'
    );
}

if (is_file($purchaseServicePath)) {
    $service = (string) file_get_contents($purchaseServicePath);
    if (
        strpos($service, 'class CompraService') !== false
        && strpos($service, 'function crearBorrador') !== false
        && strpos($service, 'function recibirCompra') !== false
        && strpos($service, 'function listarCompras') !== false
        && strpos($service, 'function catalogosBorrador') !== false
        && strpos($service, 'beginTransaction') !== false
        && strpos($service, 'movimientos_inventario') !== false
        && strpos($service, "tipo_movimiento, cantidad") !== false
        && strpos($service, "'ENTRADA'") !== false
        && strpos($service, 'AuditService::record') !== false
        && strpos($service, 'movimientos_caja') === false
        && strpos($service, 'cuentas_por_pagar') === false
    ) {
        pfOk('CompraService Fase 2P existe con contrato transaccional minimo.');
    } else {
        pfWarning(
            'CompraService Fase 2P existe pero no declara todas las guardas esperadas.',
            'Revisar crearBorrador, recibirCompra, transaccion, auditoria, movimientos_inventario y ausencia de Caja/CxP.'
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
        'function recibirAction',
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
        && strpos($controller, 'function guardarAction') !== false
        && strpos($controller, "require_hotel_module('inventario')") !== false
        && strpos($controller, 'crearBorrador') !== false
        && empty($controllerForbidden)
    ) {
        pfOk('CompraController Fase 2R expone solo listado, formulario y guardado de borrador bajo modulo inventario.');
    } else {
        pfError(
            'CompraController Fase 2R no cumple el alcance minimo o contiene tokens prohibidos: ' . (empty($controllerForbidden) ? 'sin detalle' : implode(', ', $controllerForbidden)),
            'Mantener solo indexAction, crearAction y guardarAction; sin recepcion, pagos, CxP, documentos ni caja.'
        );
    }
} else {
    pfError(
        'CompraController Fase 2R no existe.',
        'Crear solo controlador minimo para borradores antes de exponer rutas /compras.'
    );
}

if (is_file($purchaseIndexViewPath) && is_file($purchaseFormViewPath)) {
    $indexView = (string) file_get_contents($purchaseIndexViewPath);
    $formView = (string) file_get_contents($purchaseFormViewPath);
    $viewsCode = $indexView . "\n" . $formView;

    if (
        strpos($indexView, "url('compras/crear')") !== false
        && strpos($formView, "action=\"<?= url('compras') ?>\"") !== false
        && strpos($formView, 'csrf_field()') !== false
        && strpos($formView, 'name="producto_id[]"') !== false
        && strpos($formView, 'name="cantidad[]"') !== false
        && strpos($formView, 'name="costo_unitario[]"') !== false
        && strpos($viewsCode, "url('compras/recibir") === false
        && strpos($viewsCode, "url('compras/pagar") === false
        && strpos($viewsCode, 'cuentas-por-pagar') === false
    ) {
        pfOk('Vistas Fase 2R de Compras solo permiten crear borradores.');
    } else {
        pfError(
            'Vistas Fase 2R de Compras incompletas o con enlaces fuera de alcance.',
            'Revisar form CSRF, action POST /compras y ausencia de recepcion/pagos/CxP.'
        );
    }
} else {
    pfError(
        'Faltan vistas Fase 2R de Compras.',
        'Crear app/views/compras/index.php y app/views/compras/form.php.'
    );
}

if (is_file($purchaseTestToolPath)) {
    $tool = (string) file_get_contents($purchaseTestToolPath);
    if (
        strpos($tool, 'ROLLBACK_TEST') !== false
        && strpos($tool, '--backup-file') !== false
        && strpos($tool, 'runRollbackExercise') !== false
        && strpos($tool, "['manage_transaction' => false]") !== false
        && strpos($tool, 'rollBack') !== false
        && strpos($tool, 'commit()') === false
    ) {
        pfOk('Herramienta Fase 2Q de prueba CompraService existe y fuerza rollback.');
    } else {
        pfWarning(
            'Herramienta Fase 2Q de prueba CompraService existe pero no declara guardas completas.',
            'Revisar backup obligatorio, confirmacion ROLLBACK_TEST, transaccion externa y ausencia de commit.'
        );
    }
} else {
    pfWarning(
        'Herramienta Fase 2Q de prueba CompraService no existe.',
        'Crear script CLI de prueba antes de exponer rutas o UI.'
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
