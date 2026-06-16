<?php
/**
 * Health check tecnico Fase 1A/1B/1C/2A/2B/2C/2D/2E/2F/2G/2H/2I/2J/2K/2L/2M/2N/2O/2P/2Q/2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3A/3B/3C-C.
 *
 * Solo lectura. No ejecuta migraciones ni modifica datos.
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

function hcLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function hcOk(string $message): void
{
    global $ok;
    $ok++;
    hcLine('OK', $message);
}

function hcWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    hcLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function hcError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    hcLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function hcQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function hcFindFirstExistingPath(array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if ($candidate && file_exists($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    return null;
}

function hcParseEnvFile(string $path): array
{
    $values = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

function hcTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcCountRows(PDO $pdo, string $table): ?int
{
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table));
        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcReportZeroCount(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        hcWarning(
            'No se pudo validar consistencia CxP: ' . $label . '.',
            'Revisar manualmente el esquema antes de avanzar con CxP operativa.'
        );
        return;
    }

    if ($count === 0) {
        hcOk('Consistencia CxP OK: ' . $label . ' = 0.');
        return;
    }

    hcError('Consistencia CxP fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function hcCxpCajaTextPredicate(PDO $pdo, string $database): ?string
{
    $columns = [];
    foreach (['descripcion', 'referencia', 'proveedor', 'categoria', 'comprobante', 'motivo_edicion'] as $column) {
        if (hcColumnExists($pdo, $database, 'movimientos_caja', $column)) {
            $quoted = hcQuoteIdentifier($column);
            $columns[] = 'COALESCE(' . $quoted . ", '')";
        }
    }

    if (empty($columns)) {
        return null;
    }

    return "LOWER(CONCAT_WS(' ', " . implode(', ', $columns) . ")) REGEXP 'cxp|cuenta por pagar|cuentas por pagar'";
}

function hcReportCxpConsistency(PDO $pdo, string $database): void
{
    hcReportZeroCount(
        'movimientos CxP/pagos/abonos accidentales',
        hcCountRows($pdo, 'cuentas_por_pagar_movimientos'),
        'Revisar cuentas_por_pagar_movimientos; Fase 3C-C no debe tener pagos, abonos ni movimientos CxP.'
    );

    $checks = [
        [
            'label' => 'CxP duplicada por compra y hotel',
            'sql' => "SELECT COUNT(*) FROM (
                SELECT hotel_id, compra_id
                FROM cuentas_por_pagar
                WHERE compra_id IS NOT NULL
                GROUP BY hotel_id, compra_id
                HAVING COUNT(*) > 1
            ) duplicadas",
            'recommendation' => 'No generar nuevas CxP hasta dejar una sola cuenta por compra/hotel.',
        ],
        [
            'label' => 'CxP con compra inexistente',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                LEFT JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.id IS NULL",
            'recommendation' => 'Reconciliar compra_id antes de exponer generacion o reportes CxP.',
        ],
        [
            'label' => 'CxP con proveedor inexistente',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                LEFT JOIN proveedores p ON p.id = cxp.proveedor_id
                WHERE p.id IS NULL",
            'recommendation' => 'Reconciliar proveedor_id antes de permitir CxP operativa.',
        ],
        [
            'label' => 'CxP con hotel_id nulo',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE hotel_id IS NULL',
            'recommendation' => 'Asignar hotel_id por respaldo verificado antes de usar CxP.',
        ],
        [
            'label' => 'CxP con compra de otro hotel',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.hotel_id <> cxp.hotel_id",
            'recommendation' => 'Bloquear la cuenta y reconciliar hotel_id de compra/CxP.',
        ],
        [
            'label' => 'CxP con proveedor de otro hotel',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN proveedores p ON p.id = cxp.proveedor_id
                WHERE p.hotel_id <> cxp.hotel_id",
            'recommendation' => 'Bloquear la cuenta y reconciliar hotel_id de proveedor/CxP.',
        ],
        [
            'label' => 'CxP generada desde compra no recibida',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.estado <> 'recibida'",
            'recommendation' => 'Anular avance CxP sobre compras no recibidas y revisar el origen manual.',
        ],
        [
            'label' => 'CxP con saldo mayor al total',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE saldo > total',
            'recommendation' => 'Corregir saldo/total antes de habilitar pagos o reportes financieros.',
        ],
        [
            'label' => 'CxP con total invalido o saldo negativo',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE total <= 0 OR saldo < 0',
            'recommendation' => 'Revisar totales de origen antes de permitir nuevas generaciones.',
        ],
        [
            'label' => 'CxP sin fecha de emision',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE fecha_emision IS NULL',
            'recommendation' => 'Completar fecha_emision desde la compra validada antes de operar CxP.',
        ],
    ];

    foreach ($checks as $check) {
        hcReportZeroCount($check['label'], hcCountScalar($pdo, $check['sql']), $check['recommendation']);
    }

    if (hcTableExists($pdo, $database, 'movimientos_caja')) {
        $predicate = hcCxpCajaTextPredicate($pdo, $database);
        if ($predicate === null) {
            hcWarning(
                'No se pudo validar movimientos de Caja relacionados con CxP: faltan columnas textuales conocidas.',
                'Confirmar manualmente que Fase 3C no haya escrito movimientos_caja.'
            );
        } else {
            hcReportZeroCount(
                'movimientos de Caja con referencia textual a CxP',
                hcCountScalar($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE ' . $predicate),
                'Revisar movimientos_caja; Fase 3C no debe crear pagos, abonos ni movimientos de Caja.'
            );
        }
    } else {
        hcWarning(
            'No se pudo validar movimientos de Caja relacionados con CxP porque falta movimientos_caja.',
            'Revisar esquema de Caja antes de habilitar pagos.'
        );
    }
}

function hcNullHotelRows(PDO $pdo, string $table): ?int
{
    try {
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table) . ' WHERE hotel_id IS NULL'
        );
        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcRouteRegex(string $route): string
{
    $quoted = preg_quote(trim($route, '/'), '#');
    $quoted = preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', $quoted);
    return '#^' . $quoted . '$#';
}

function hcParseRoutes(string $routesPath): array
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

function hcRouteExists(array $routes, string $path, ?string $method = null): bool
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

function hcRoutePatternExists(array $routes, string $samplePath, ?string $method = null): bool
{
    $sample = trim($samplePath, '/');
    foreach ($routes as $route) {
        if ($method !== null && strtolower($route['method']) !== strtolower($method)) {
            continue;
        }
        if (preg_match(hcRouteRegex($route['path']), $sample)) {
            return true;
        }
    }

    return false;
}

function hcExtractMethodCode(string $code, string $method): string
{
    $pos = strpos($code, 'function ' . $method . '(');
    if ($pos === false) {
        return '';
    }

    $brace = strpos($code, '{', $pos);
    if ($brace === false) {
        return '';
    }

    $depth = 0;
    $length = strlen($code);
    for ($i = $brace; $i < $length; $i++) {
        if ($code[$i] === '{') {
            $depth++;
        } elseif ($code[$i] === '}') {
            $depth--;
            if ($depth === 0) {
                return substr($code, $pos, $i - $pos + 1);
            }
        }
    }

    return substr($code, $pos);
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllersDir = $appRoot . '/app/controllers';
$sidebarPath = $appRoot . '/app/views/layout/sidebar.php';
$providerReconciliationTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/reconciliar_proveedores_dry_run.php',
    $projectRoot . '/src/tools/saas/reconciliar_proveedores_dry_run.php',
    getcwd() . '/tools/saas/reconciliar_proveedores_dry_run.php',
    dirname(getcwd()) . '/src/tools/saas/reconciliar_proveedores_dry_run.php',
    '/workspace/src/tools/saas/reconciliar_proveedores_dry_run.php',
]);
$providerImportTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/importar_proveedores_los_cedros.php',
    $projectRoot . '/src/tools/saas/importar_proveedores_los_cedros.php',
    getcwd() . '/tools/saas/importar_proveedores_los_cedros.php',
    dirname(getcwd()) . '/src/tools/saas/importar_proveedores_los_cedros.php',
    '/workspace/src/tools/saas/importar_proveedores_los_cedros.php',
]);
$providerImportReviewFile = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/proveedores_los_cedros_reconciliation.json',
    $projectRoot . '/src/tools/saas/proveedores_los_cedros_reconciliation.json',
    getcwd() . '/tools/saas/proveedores_los_cedros_reconciliation.json',
    dirname(getcwd()) . '/src/tools/saas/proveedores_los_cedros_reconciliation.json',
    '/workspace/src/tools/saas/proveedores_los_cedros_reconciliation.json',
]);
$minimalPurchasingPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_compras_minimas.php',
    $projectRoot . '/src/tools/saas/preflight_compras_minimas.php',
    getcwd() . '/tools/saas/preflight_compras_minimas.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_compras_minimas.php',
    '/workspace/src/tools/saas/preflight_compras_minimas.php',
]);
$purchaseReceptionPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_recepcion_compras.php',
    $projectRoot . '/src/tools/saas/preflight_recepcion_compras.php',
    getcwd() . '/tools/saas/preflight_recepcion_compras.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_recepcion_compras.php',
    '/workspace/src/tools/saas/preflight_recepcion_compras.php',
]);
$purchaseServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/CompraService.php',
    $projectRoot . '/src/app/services/CompraService.php',
    getcwd() . '/app/services/CompraService.php',
    dirname(getcwd()) . '/src/app/services/CompraService.php',
    '/workspace/src/app/services/CompraService.php',
]);
$purchaseControllerFile = hcFindFirstExistingPath([
    $appRoot . '/app/controllers/CompraController.php',
    $projectRoot . '/src/app/controllers/CompraController.php',
    getcwd() . '/app/controllers/CompraController.php',
    dirname(getcwd()) . '/src/app/controllers/CompraController.php',
    '/workspace/src/app/controllers/CompraController.php',
]);
$purchaseIndexViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/index.php',
    $projectRoot . '/src/app/views/compras/index.php',
    getcwd() . '/app/views/compras/index.php',
    dirname(getcwd()) . '/src/app/views/compras/index.php',
    '/workspace/src/app/views/compras/index.php',
]);
$purchaseFormViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/form.php',
    $projectRoot . '/src/app/views/compras/form.php',
    getcwd() . '/app/views/compras/form.php',
    dirname(getcwd()) . '/src/app/views/compras/form.php',
    '/workspace/src/app/views/compras/form.php',
]);
$purchaseDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/ver.php',
    $projectRoot . '/src/app/views/compras/ver.php',
    getcwd() . '/app/views/compras/ver.php',
    dirname(getcwd()) . '/src/app/views/compras/ver.php',
    '/workspace/src/app/views/compras/ver.php',
]);
$purchaseReportViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/reporte_recibidas.php',
    $projectRoot . '/src/app/views/compras/reporte_recibidas.php',
    getcwd() . '/app/views/compras/reporte_recibidas.php',
    dirname(getcwd()) . '/src/app/views/compras/reporte_recibidas.php',
    '/workspace/src/app/views/compras/reporte_recibidas.php',
]);
$providerDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/proveedores/ver.php',
    $projectRoot . '/src/app/views/proveedores/ver.php',
    getcwd() . '/app/views/proveedores/ver.php',
    dirname(getcwd()) . '/src/app/views/proveedores/ver.php',
    '/workspace/src/app/views/proveedores/ver.php',
]);
$cxpControllerFile = hcFindFirstExistingPath([
    $appRoot . '/app/controllers/CuentaPorPagarController.php',
    $projectRoot . '/src/app/controllers/CuentaPorPagarController.php',
    getcwd() . '/app/controllers/CuentaPorPagarController.php',
    dirname(getcwd()) . '/src/app/controllers/CuentaPorPagarController.php',
    '/workspace/src/app/controllers/CuentaPorPagarController.php',
]);
$cxpModelFile = hcFindFirstExistingPath([
    $appRoot . '/app/models/CuentaPorPagar.php',
    $projectRoot . '/src/app/models/CuentaPorPagar.php',
    getcwd() . '/app/models/CuentaPorPagar.php',
    dirname(getcwd()) . '/src/app/models/CuentaPorPagar.php',
    '/workspace/src/app/models/CuentaPorPagar.php',
]);
$cxpIndexViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/index.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/index.php',
    getcwd() . '/app/views/cuentas_por_pagar/index.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/index.php',
    '/workspace/src/app/views/cuentas_por_pagar/index.php',
]);
$cxpDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/ver.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/ver.php',
    getcwd() . '/app/views/cuentas_por_pagar/ver.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/ver.php',
    '/workspace/src/app/views/cuentas_por_pagar/ver.php',
]);
$cxpPreviewViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/generacion_preview.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/generacion_preview.php',
    getcwd() . '/app/views/cuentas_por_pagar/generacion_preview.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/generacion_preview.php',
    '/workspace/src/app/views/cuentas_por_pagar/generacion_preview.php',
]);
$purchaseTestTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_compra_service.php',
    $projectRoot . '/src/tools/saas/probar_compra_service.php',
    getcwd() . '/tools/saas/probar_compra_service.php',
    dirname(getcwd()) . '/src/tools/saas/probar_compra_service.php',
    '/workspace/src/tools/saas/probar_compra_service.php',
]);
$minimalPurchasingDraft = hcFindFirstExistingPath([
    $projectRoot . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    getcwd() . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    dirname(getcwd()) . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    dirname(dirname(getcwd())) . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    '/workspace/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
]);
$cxpBaseDraft = hcFindFirstExistingPath([
    $projectRoot . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    getcwd() . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    dirname(getcwd()) . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    dirname(dirname(getcwd())) . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    '/workspace/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
]);
$minimalPurchasingOfficialMigration = hcFindFirstExistingPath([
    $projectRoot . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    getcwd() . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    dirname(getcwd()) . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    dirname(dirname(getcwd())) . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    '/workspace/migrations/20260615_002_fase_2n_compras_minimas.sql',
]);
$cxpOfficialMigration = hcFindFirstExistingPath([
    $projectRoot . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    getcwd() . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    dirname(getcwd()) . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    dirname(dirname(getcwd())) . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    '/workspace/migrations/20260615_003_fase_3b_cxp_base.sql',
]);
$docsTechnicalDir = hcFindFirstExistingPath([
    getenv('DOCS_TECHNICAL_DIR') ?: '',
    getenv('PROJECT_ROOT') ? rtrim((string) getenv('PROJECT_ROOT'), "/\\") . '/docs/technical' : '',
    $projectRoot . '/docs/technical',
    dirname($projectRoot) . '/docs/technical',
    getcwd() . '/docs/technical',
    dirname(getcwd()) . '/docs/technical',
    dirname(dirname(getcwd())) . '/docs/technical',
    '/workspace/docs/technical',
]);
$inventoryDoc = $docsTechnicalDir ? $docsTechnicalDir . '/inventory_reconciliation.md' : null;
$duplicatedTablesDoc = $docsTechnicalDir ? $docsTechnicalDir . '/duplicated_tables.md' : null;
$purchasingInventoryDoc = $docsTechnicalDir ? $docsTechnicalDir . '/purchasing_inventory_contract.md' : null;

echo "Health check Fase 1A/1B/1C/2A/2B/2C/2D/2E/2F/2G/2H/2I/2J/2K/2L/2M/2N/2O/2P/2Q/2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3A/3B/3C-C/4C-A - Medisoft Hoteles\n";
echo "============================================================\n";

if (!is_file($configPath)) {
    hcError('No se encontro config/database.php.', 'Verificar montaje de src antes de ejecutar el checker.');
    exit(1);
}

if ($docsTechnicalDir) {
    hcOk('Directorio docs/technical detectado: ' . $docsTechnicalDir);
} else {
    hcWarning(
        'Directorio docs/technical no esta accesible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar docs/technical para validar documentacion tecnica.'
    );
}

if ($providerReconciliationTool && is_file($providerReconciliationTool)) {
    $providerReconciliationCode = (string) file_get_contents($providerReconciliationTool);
    if (
        strpos($providerReconciliationCode, 'Solo lectura') !== false
        && strpos($providerReconciliationCode, 'movimientos_caja.proveedor') !== false
        && strpos($providerReconciliationCode, 'writes_db') !== false
    ) {
        hcOk('Herramienta dry-run de reconciliacion de proveedores existe y esta marcada como solo lectura.');
    } else {
        hcWarning(
            'Herramienta dry-run de proveedores existe pero no declara claramente su alcance.',
            'Asegurar que src/tools/saas/reconciliar_proveedores_dry_run.php documente solo lectura y no escritura.'
        );
    }
} else {
    hcWarning(
        'No existe herramienta dry-run de reconciliacion de proveedores.',
        'Crear src/tools/saas/reconciliar_proveedores_dry_run.php antes de preparar una importacion real.'
    );
}

if ($providerImportTool && is_file($providerImportTool) && $providerImportReviewFile && is_file($providerImportReviewFile)) {
    $providerImportCode = (string) file_get_contents($providerImportTool);
    $providerReviewCode = (string) file_get_contents($providerImportReviewFile);
    if (
        strpos($providerImportCode, '--apply') !== false
        && strpos($providerImportCode, '--backup-file') !== false
        && strpos($providerImportCode, 'beginTransaction') !== false
        && strpos($providerReviewCode, '"phase": "2L"') !== false
        && strpos($providerReviewCode, '"approved": false') !== false
    ) {
        hcOk('Importador revisable Fase 2L de proveedores existe y exige backup para aplicar.');
    } else {
        hcWarning(
            'Importador Fase 2L de proveedores existe pero no declara todas las guardas esperadas.',
            'Verificar --apply, --backup-file, transaccion y lista de revision con exclusiones.'
        );
    }
} else {
    hcWarning(
        'No existe importador revisable Fase 2L completo para proveedores de Los Cedros.',
        'Crear importar_proveedores_los_cedros.php y proveedores_los_cedros_reconciliation.json antes de importar.'
    );
}

if ($minimalPurchasingPreflight && is_file($minimalPurchasingPreflight)) {
    $minimalPurchasingPreflightCode = (string) file_get_contents($minimalPurchasingPreflight);
    if (
        strpos($minimalPurchasingPreflightCode, 'Solo lectura') !== false
        && strpos($minimalPurchasingPreflightCode, 'compras') !== false
        && strpos($minimalPurchasingPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($minimalPurchasingPreflightCode, 'proveedores') !== false
        && strpos($minimalPurchasingPreflightCode, 'CompraController') !== false
        && strpos($minimalPurchasingPreflightCode, 'movimientos_caja') !== false
        && strpos($minimalPurchasingPreflightCode, 'cuentas-por-pagar/generacion-preview') !== false
        && strpos($minimalPurchasingPreflightCode, 'function generacionPreviewAction') !== false
        && strpos($minimalPurchasingPreflightCode, 'function previewGeneracionDesdeCompras') !== false
        && strpos($minimalPurchasingPreflightCode, '3C-C') !== false
    ) {
        hcOk('Preflight de compras minimas existe, es solo lectura y conoce Fase 2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3B/3C-C.');
    } else {
        hcWarning(
            'Preflight de compras minimas existe pero no declara guardas completas.',
            'Verificar que preflight_compras_minimas.php valide solo lectura, UI de borradores, proveedores, inventario moderno y no Caja.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 2M de compras minimas.',
        'Crear src/tools/saas/preflight_compras_minimas.php antes de preparar migraciones de compras.'
    );
}

if ($purchaseReceptionPreflight && is_file($purchaseReceptionPreflight)) {
    $purchaseReceptionPreflightCode = (string) file_get_contents($purchaseReceptionPreflight);
    if (
        strpos($purchaseReceptionPreflightCode, 'Preflight Fase 2T') !== false
        && strpos($purchaseReceptionPreflightCode, '3C-C') !== false
        && strpos($purchaseReceptionPreflightCode, 'Solo lectura') !== false
        && strpos($purchaseReceptionPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($purchaseReceptionPreflightCode, 'detalles_con_movimiento') !== false
        && strpos($purchaseReceptionPreflightCode, 'productos_repetidos') !== false
        && strpos($purchaseReceptionPreflightCode, 'un movimiento por linea') !== false
        && strpos($purchaseReceptionPreflightCode, 'cuentas-por-pagar/generacion-preview') !== false
        && strpos($purchaseReceptionPreflightCode, 'generacionpreview') !== false
        && strpos($purchaseReceptionPreflightCode, 'movimientos_caja') !== false
    ) {
        hcOk('Preflight Fase 2T/2U/2V/2W/2X/2Y/2Z/3B/3C-C de recepcion de compras existe y es solo lectura.');
    } else {
        hcWarning(
            'Preflight Fase 2T de recepcion existe pero no declara todas las guardas esperadas.',
            'Verificar solo lectura, productos repetidos, detalles con movimiento y ausencia de recepcion ejecutable.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 2T de recepcion de compras.',
        'Crear src/tools/saas/preflight_recepcion_compras.php antes de exponer recepcion.'
    );
}

if ($minimalPurchasingOfficialMigration && is_file($minimalPurchasingOfficialMigration)) {
    hcOk('Migracion oficial Fase 2O de compras minimas existe en migrations/.');
} else {
    hcWarning(
        'Migracion oficial Fase 2O de compras minimas no existe en migrations/.',
        'Promover el borrador solo con backup completo y autorizacion explicita.'
    );
}

if ($cxpBaseDraft && is_file($cxpBaseDraft)) {
    $cxpDraftCode = (string) file_get_contents($cxpBaseDraft);
    if (
        strpos($cxpDraftCode, 'NO EJECUTAR EN ESTA SUBFASE') !== false
        && strpos($cxpDraftCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar') !== false
        && strpos($cxpDraftCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar_movimientos') !== false
        && strpos($cxpDraftCode, 'uk_cxp_hotel_compra') !== false
        && strpos($cxpDraftCode, 'fk_cxp_compra') !== false
        && strpos($cxpDraftCode, 'Rollback manual documentado') !== false
        && strpos($cxpDraftCode, 'movimientos_caja') === false
    ) {
        hcOk('Borrador tecnico Fase 3B de CxP base existe para trazabilidad.');
    } else {
        hcWarning(
            'Borrador Fase 3B de CxP base incompleto o riesgoso.',
            'Mantenerlo no ejecutado, no destructivo, sin Caja y con rollback manual documentado.'
        );
    }
} else {
    hcWarning(
        'Borrador Fase 3B de CxP base no visible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar docs/technical para validar el borrador historico.'
    );
}

if ($cxpOfficialMigration && is_file($cxpOfficialMigration)) {
    $cxpMigrationCode = (string) file_get_contents($cxpOfficialMigration);
    if (
        strpos($cxpMigrationCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar') !== false
        && strpos($cxpMigrationCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar_movimientos') !== false
        && strpos($cxpMigrationCode, '20260615_003_fase_3b_cxp_base.sql') !== false
        && strpos($cxpMigrationCode, 'phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql') !== false
        && strpos($cxpMigrationCode, 'movimientos_caja') === false
    ) {
        hcOk('Migracion oficial Fase 3B de CxP base existe y documenta backup previo.');
    } else {
        hcWarning(
            'Migracion oficial Fase 3B de CxP base incompleta.',
            'Validar tablas, backup documentado, ausencia de Caja y registro en migrations.'
        );
    }
} else {
    hcWarning(
        'Migracion oficial Fase 3B de CxP base no visible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar migrations/ para validar el archivo oficial.'
    );
}

if ($purchaseServiceFile && is_file($purchaseServiceFile)) {
    $purchaseServiceCode = (string) file_get_contents($purchaseServiceFile);
    if (
        strpos($purchaseServiceCode, 'class CompraService') !== false
        && strpos($purchaseServiceCode, 'function crearBorrador') !== false
        && strpos($purchaseServiceCode, 'function obtenerCompra') !== false
        && strpos($purchaseServiceCode, 'function recibirCompra') !== false
        && strpos($purchaseServiceCode, 'function listarCompras') !== false
        && strpos($purchaseServiceCode, 'function catalogosBorrador') !== false
        && strpos($purchaseServiceCode, 'function catalogosReporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function reporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function filtrosReporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function assertCompraPuedeRecibirse') !== false
        && strpos($purchaseServiceCode, 'function assertDetallesPuedenRecibirse') !== false
        && strpos($purchaseServiceCode, 'ya fue recibida') !== false
        && strpos($purchaseServiceCode, 'esta cancelada') !== false
        && strpos($purchaseServiceCode, 'otra sesion') !== false
        && strpos($purchaseServiceCode, 'COUNT(DISTINCT c.id)') !== false
        && strpos($purchaseServiceCode, 'GROUP BY p.id, p.nombre') !== false
        && strpos($purchaseServiceCode, 'GROUP BY ip.id, ip.codigo, ip.nombre, ip.unidad_medida') !== false
        && strpos($purchaseServiceCode, 'LEFT JOIN movimientos_inventario') !== false
        && strpos($purchaseServiceCode, 'movimiento_stock_posterior') !== false
        && strpos($purchaseServiceCode, 'beginTransaction') !== false
        && strpos($purchaseServiceCode, 'movimientos_inventario') !== false
        && strpos($purchaseServiceCode, "'ENTRADA'") !== false
        && strpos($purchaseServiceCode, 'AuditService::record') !== false
        && strpos($purchaseServiceCode, 'movimientos_caja') === false
        && strpos($purchaseServiceCode, 'cuentas_por_pagar') === false
    ) {
        hcOk('CompraService existe con contrato transaccional, reportes y guardas Fase 2Z.');
    } else {
        hcWarning(
            'CompraService Fase 2Z existe pero no declara todas las guardas esperadas.',
            'Revisar crearBorrador, recibirCompra, reporteRecibidas, assertCompraPuedeRecibirse, assertDetallesPuedenRecibirse y ausencia de Caja/CxP.'
        );
    }
} else {
    hcWarning(
        'CompraService Fase 2P no existe.',
        'Crear capa interna antes de exponer rutas o UI de Compras.'
    );
}

if ($purchaseTestTool && is_file($purchaseTestTool)) {
    $purchaseTestCode = (string) file_get_contents($purchaseTestTool);
    if (
        strpos($purchaseTestCode, 'ROLLBACK_TEST') !== false
        && strpos($purchaseTestCode, '--backup-file') !== false
        && strpos($purchaseTestCode, '--duplicate-line') !== false
        && strpos($purchaseTestCode, 'duplicate_product_rule') !== false
        && strpos($purchaseTestCode, 'runRollbackExercise') !== false
        && strpos($purchaseTestCode, "['manage_transaction' => false]") !== false
        && strpos($purchaseTestCode, 'rollBack') !== false
        && strpos($purchaseTestCode, 'commit()') === false
    ) {
        hcOk('Herramienta Fase 2Q/2U de prueba CompraService existe y fuerza rollback.');
    } else {
        hcWarning(
            'Herramienta Fase 2Q/2U de prueba CompraService existe pero no declara todas las guardas esperadas.',
            'Revisar backup obligatorio, confirmacion ROLLBACK_TEST, duplicate-line, transaccion externa y ausencia de commit.'
        );
    }
} else {
    hcWarning(
        'Herramienta Fase 2Q de prueba CompraService no existe.',
        'Crear script CLI de prueba con rollback antes de exponer UI o rutas.'
    );
}

if ($minimalPurchasingDraft && is_file($minimalPurchasingDraft)) {
    $minimalPurchasingDraftCode = (string) file_get_contents($minimalPurchasingDraft);
    if (
        strpos($minimalPurchasingDraftCode, 'BORRADOR NO EJECUTADO') !== false
        && strpos($minimalPurchasingDraftCode, 'CREATE TABLE IF NOT EXISTS compras') !== false
        && strpos($minimalPurchasingDraftCode, 'CREATE TABLE IF NOT EXISTS compra_detalles') !== false
        && strpos($minimalPurchasingDraftCode, 'fk_compras_proveedor') !== false
        && strpos($minimalPurchasingDraftCode, 'fk_compra_detalles_producto') !== false
        && strpos($minimalPurchasingDraftCode, 'No toca movimientos_caja') !== false
    ) {
        hcOk('Borrador Fase 2N de compras minimas existe con tablas y guardas principales.');
    } else {
        hcWarning(
            'Borrador Fase 2N de compras minimas existe pero no declara todas las guardas esperadas.',
            'Revisar compras, compra_detalles, FKs y exclusiones de Caja/CxP antes de promover.'
        );
    }
} else {
    hcWarning(
        'No existe borrador Fase 2N de compras minimas.',
        'Crear docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql antes de promover migracion.'
    );
}

$envPath = hcFindFirstExistingPath([
    getenv('ENV_FILE') ?: '',
    $projectRoot . '/.env',
    $appRoot . '/.env',
    getcwd() . '/.env',
    dirname(getcwd()) . '/.env',
]);

$envValues = [];
if ($envPath) {
    $envValues = hcParseEnvFile($envPath);
    hcOk('Archivo .env detectado: ' . $envPath);
} else {
    hcWarning('No se encontro archivo .env accesible desde este contexto.', 'Ejecutar el checker desde el host o montar el proyecto completo si se quiere validar .env por archivo.');
}

$dbConfig = require $configPath;
$database = (string) ($dbConfig['database'] ?? '');
if ($database === '') {
    hcError('La configuracion de DB no define database.', 'Revisar DB_NAME en .env/configuracion.');
} else {
    hcOk('Base configurada por la app: ' . $database);
}

if (isset($envValues['DB_NAME']) && $database !== '' && $envValues['DB_NAME'] !== $database) {
    hcWarning(
        'DB_NAME en .env (' . $envValues['DB_NAME'] . ') difiere de config/database.php (' . $database . ').',
        'Unificar la base activa antes de ejecutar nuevas fases.'
    );
} elseif (isset($envValues['DB_NAME'])) {
    hcOk('DB_NAME de .env coincide con la configuracion cargada.');
}

$pdo = null;
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'] ?? 'db',
        $dbConfig['port'] ?? 3306,
        $database,
        $dbConfig['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    hcOk('Conexion PDO de solo lectura inicializada.');
} catch (Throwable $e) {
    hcError('No se pudo conectar a la base configurada: ' . $e->getMessage(), 'Revisar contenedores y credenciales antes de continuar.');
}

if ($pdo) {
    if (hcTableExists($pdo, $database, 'migrations')) {
        hcOk('Tabla migrations existe.');
    } else {
        hcError('Tabla migrations no existe.', 'No aplicar nuevas fases hasta crear/controlar la tabla migrations.');
    }

    $migrationsDir = hcFindFirstExistingPath([
        getenv('MIGRATIONS_DIR') ?: '',
        $projectRoot . '/migrations',
        dirname($projectRoot) . '/migrations',
        getcwd() . '/migrations',
        dirname(getcwd()) . '/migrations',
    ]);

    if ($migrationsDir && is_dir($migrationsDir)) {
        $migrationFiles = array_map('basename', glob($migrationsDir . '/*.sql') ?: []);
        sort($migrationFiles);
        hcOk('Directorio migrations detectado: ' . $migrationsDir . ' (' . count($migrationFiles) . ' archivos SQL).');

        if (hcTableExists($pdo, $database, 'migrations')) {
            $registered = $pdo->query('SELECT nombre FROM migrations ORDER BY nombre')->fetchAll(PDO::FETCH_COLUMN);
            $registered = array_map('strval', $registered);

            $missingRegistered = array_values(array_diff($migrationFiles, $registered));
            $registeredWithoutFile = array_values(array_diff($registered, $migrationFiles));

            if (empty($missingRegistered)) {
                hcOk('Todas las migraciones de migrations/ estan registradas.');
            } else {
                hcError(
                    'Migraciones de migrations/ no registradas: ' . implode(', ', $missingRegistered),
                    'No ejecutar codigo que dependa de esas migraciones sin reconciliar el historial.'
                );
            }

            if (empty($registeredWithoutFile)) {
                hcOk('Todas las migraciones registradas tienen archivo en migrations/.');
            } else {
                hcWarning(
                    'Migraciones registradas sin archivo local: ' . implode(', ', $registeredWithoutFile),
                    'Verificar si fueron aplicadas manualmente o si falta versionar archivos.'
                );
            }
        }
    } else {
        hcWarning(
            'No se encontro directorio migrations/ accesible.',
            'Ejecutar con el proyecto raiz montado o definir MIGRATIONS_DIR para comparar archivos vs DB.'
        );
    }

    $extraMigrationDir = $appRoot . '/database/migrations';
    if (is_dir($extraMigrationDir)) {
        $extraFiles = array_map('basename', glob($extraMigrationDir . '/*.sql') ?: []);
        foreach ($extraFiles as $file) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE nombre = :name');
            $stmt->execute(['name' => $file]);
            if ((int) $stmt->fetchColumn() === 0) {
                hcWarning(
                    'Migracion fuera de migrations/ no registrada: database/migrations/' . $file,
                    'Decidir si esta migracion pertenece al flujo oficial antes de aplicarla.'
                );
            }
        }
    }

    $requiredSaasTables = [
        'hoteles',
        'hotel_configuracion',
        'hotel_usuarios',
        'hotel_modulos',
        'hotel_branding',
        'logs_auditoria',
        'saas_admins',
        'modulos',
        'planes',
        'plan_modulos',
        'reporte_links',
        'reporte_link_envios',
        'pwa_push_subscriptions',
    ];

    foreach ($requiredSaasTables as $table) {
        if (hcTableExists($pdo, $database, $table)) {
            hcOk('Tabla SaaS requerida existe: ' . $table);
        } else {
            hcError('Tabla SaaS requerida faltante: ' . $table, 'No avanzar con modulos SaaS hasta reconciliar migraciones.');
        }
    }

    if (hcTableExists($pdo, $database, 'hoteles')) {
        $activeHotels = $pdo->query(
            'SELECT h.id, h.slug, h.nombre, h.plan_id, p.clave AS plan_clave
             FROM hoteles h
             LEFT JOIN planes p ON p.id = h.plan_id
             WHERE h.activo = 1
             ORDER BY h.id'
        )->fetchAll();

        if (empty($activeHotels)) {
            hcError('No hay hoteles activos.', 'Activar o crear al menos un hotel antes de operar multihotel.');
        } else {
            hcOk('Hoteles activos detectados: ' . count($activeHotels));
        }

        if (hcTableExists($pdo, $database, 'modulos')) {
            $activeModuleCount = (int) $pdo->query(
                'SELECT COUNT(*) FROM modulos WHERE activo_global = 1'
            )->fetchColumn();
            hcOk('Modulos globales activos: ' . $activeModuleCount);
        }

        if (hcTableExists($pdo, $database, 'hotel_modulos') && hcTableExists($pdo, $database, 'modulos')) {
            $coreModuleKeys = ['dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'reportes'];
            $losCedrosExpected = [
                'dashboard',
                'habitaciones',
                'reservaciones',
                'huespedes',
                'caja',
                'facturacion',
                'inventario',
                'reportes',
                'configuracion',
                'usuarios',
                'tarifas_dinamicas',
                'limpieza',
                'mantenimiento',
                'auditoria',
            ];

            $moduleStmt = $pdo->prepare(
                'SELECT m.clave
                 FROM hotel_modulos hm
                 INNER JOIN modulos m ON m.id = hm.modulo_id
                 WHERE hm.hotel_id = :hotel_id
                   AND hm.activo = 1
                   AND m.activo_global = 1
                 ORDER BY m.orden, m.clave'
            );

            $planStmt = $pdo->prepare(
                'SELECT m.clave
                 FROM plan_modulos pm
                 INNER JOIN modulos m ON m.id = pm.modulo_id
                 WHERE pm.plan_id = :plan_id
                   AND pm.incluido = 1
                   AND m.activo_global = 1
                 ORDER BY m.orden, m.clave'
            );

            foreach ($activeHotels as $hotel) {
                $moduleStmt->execute(['hotel_id' => (int) $hotel['id']]);
                $activeKeys = array_values(array_map('strval', $moduleStmt->fetchAll(PDO::FETCH_COLUMN)));

                if (empty($activeKeys)) {
                    hcError(
                        'Hotel activo sin modulos activos: ' . $hotel['slug'],
                        'Asignar modulos base antes de liberar flujos SaaS/multihotel para este hotel.'
                    );
                    continue;
                }

                hcOk('Hotel ' . $hotel['slug'] . ' tiene modulos activos: ' . count($activeKeys));

                $missingCore = array_values(array_diff($coreModuleKeys, $activeKeys));
                if (!empty($missingCore)) {
                    hcWarning(
                        'Hotel ' . $hotel['slug'] . ' no tiene modulos core activos: ' . implode(', ', $missingCore),
                        'Revisar hotel_modulos antes de operar ese hotel.'
                    );
                }

                if (($hotel['slug'] ?? '') === 'los-cedros') {
                    $missingLosCedros = array_values(array_diff($losCedrosExpected, $activeKeys));
                    if (empty($missingLosCedros)) {
                        hcOk('Los Cedros tiene los modulos Fase 1B minimos activos.');
                    } else {
                        hcError(
                            'Los Cedros no tiene modulos Fase 1B minimos: ' . implode(', ', $missingLosCedros),
                            'Ejecutar la migracion 20260614_002_fase_1b_modulos_los_cedros_auditoria.sql tras backup.'
                        );
                    }
                }

                $planId = (int) ($hotel['plan_id'] ?? 0);
                $planClave = (string) ($hotel['plan_clave'] ?? '');
                if ($planId > 0 && $planClave !== '' && $planClave !== 'personalizado' && hcTableExists($pdo, $database, 'plan_modulos')) {
                    $planStmt->execute(['plan_id' => $planId]);
                    $planKeys = array_values(array_map('strval', $planStmt->fetchAll(PDO::FETCH_COLUMN)));
                    $missingFromPlan = array_values(array_diff($planKeys, $activeKeys));
                    $outsidePlan = array_values(array_diff($activeKeys, $planKeys));

                    if (empty($missingFromPlan) && empty($outsidePlan)) {
                        hcOk('Hotel ' . $hotel['slug'] . ' coincide con su plan ' . $planClave . '.');
                    } else {
                        hcWarning(
                            'Hotel ' . $hotel['slug'] . ' difiere de su plan ' . $planClave . '. Faltan: ' . (empty($missingFromPlan) ? 'ninguno' : implode(', ', $missingFromPlan)) . '. Fuera de plan: ' . (empty($outsidePlan) ? 'ninguno' : implode(', ', $outsidePlan)) . '.',
                            'Mantener como advertencia si el hotel usa excepciones manuales; no corregir automaticamente.'
                        );
                    }
                }
            }
        }
    }

    if (hcTableExists($pdo, $database, 'logs_auditoria')) {
        $auditCount = hcCountRows($pdo, 'logs_auditoria');
        if ($auditCount === 0) {
            hcWarning(
                'logs_auditoria existe pero aun no tiene registros.',
                'Probar login/logout despues de desplegar AuditService para confirmar escritura gradual.'
            );
        } else {
            hcOk('logs_auditoria tiene registros: ' . $auditCount);

            $lastAudit = $pdo->query(
                'SELECT accion, created_at FROM logs_auditoria ORDER BY created_at DESC, id DESC LIMIT 1'
            )->fetch();
            if ($lastAudit) {
                hcOk('Ultima auditoria: ' . $lastAudit['accion'] . ' en ' . $lastAudit['created_at']);
            }
        }
    }

    $hotelIdTables = [
        'habitaciones',
        'tipos_habitacion',
        'habitacion_imagenes',
        'mantenimientos_habitaciones',
        'inventario_categorias',
        'inventario_productos',
        'inventario_config_habitacion',
        'movimientos_inventario',
        'reservaciones',
        'reservacion_habitaciones',
        'reservacion_pagos',
        'reservacion_abonos',
        'reservacion_notas',
        'solicitudes_factura',
        'cajas',
        'cortes_caja',
        'movimientos_caja',
        'notificaciones',
        'pwa_push_subscriptions',
        'reporte_links',
        'reporte_link_envios',
        'proveedores',
    ];

    foreach ($hotelIdTables as $table) {
        if (!hcTableExists($pdo, $database, $table)) {
            hcWarning('Tabla esperada para hotel_id no existe: ' . $table, 'Verificar si la migracion correspondiente debe permanecer pendiente.');
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            hcError('Tabla sin hotel_id esperado: ' . $table, 'No activar multihotel completo para esta tabla hasta migrarla.');
            continue;
        }

        $nullRows = hcNullHotelRows($pdo, $table);
        if ($nullRows === 0) {
            hcOk('Tabla con hotel_id completo: ' . $table);
        } else {
            hcError('Tabla ' . $table . ' tiene hotel_id NULL: ' . (string) $nullRows, 'Corregir backfill antes de usar aislamiento multihotel.');
        }
    }

    if (hcTableExists($pdo, $database, 'proveedores')) {
        $providerCount = hcCountRows($pdo, 'proveedores');
        hcOk('Catalogo proveedores Fase 2F existe. Registros: ' . (string) $providerCount);

        if (hcColumnExists($pdo, $database, 'proveedores', 'hotel_id')) {
            $providerNullHotels = hcNullHotelRows($pdo, 'proveedores');
            if ($providerNullHotels === 0) {
                hcOk('proveedores tiene hotel_id completo.');
            } else {
                hcError('proveedores tiene hotel_id NULL: ' . (string) $providerNullHotels, 'Corregir aislamiento antes de usar catalogo multihotel.');
            }
        } else {
            hcError('proveedores no tiene hotel_id.', 'No usar catalogo de proveedores sin alcance por hotel.');
        }

        if (hcTableExists($pdo, $database, 'migrations')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE nombre = :name');
            $stmt->execute(['name' => '20260614_004_fase_2f_catalogo_proveedores.sql']);
            if ((int) $stmt->fetchColumn() > 0) {
                hcOk('Migracion Fase 2F de proveedores registrada.');
            } else {
                hcError(
                    'proveedores existe pero la migracion Fase 2F no esta registrada.',
                    'Registrar/reconciliar 20260614_004_fase_2f_catalogo_proveedores.sql antes de depender del catalogo.'
                );
            }

            $stmt->execute(['name' => '20260615_001_fase_2j_unique_proveedores.sql']);
            if ((int) $stmt->fetchColumn() > 0) {
                hcOk('Migracion Fase 2J de constraints proveedores registrada.');
            } else {
                hcError(
                    'Migracion Fase 2J de constraints proveedores no esta registrada.',
                    'Aplicar 20260615_001_fase_2j_unique_proveedores.sql tras backup o retirar dependencias de indices unicos.'
                );
            }
        }

        $stmt = $pdo->prepare(
            'SELECT COLUMN_NAME, EXTRA, GENERATION_EXPRESSION
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $stmt->execute(['db' => $database, 'table' => 'proveedores', 'column' => 'nombre_activo_key']);
        $generatedColumn = $stmt->fetch();
        if (
            $generatedColumn
            && strpos((string) ($generatedColumn['EXTRA'] ?? ''), 'GENERATED') !== false
            && strpos((string) ($generatedColumn['GENERATION_EXPRESSION'] ?? ''), 'activo') !== false
        ) {
            hcOk('proveedores.nombre_activo_key existe como columna generada para nombres activos.');
        } else {
            hcError(
                'No existe columna generada proveedores.nombre_activo_key.',
                'Agregar la columna generada antes de depender del indice unico de nombre activo.'
            );
        }

        $expectedProviderUniqueIndexes = [
            'uk_proveedores_hotel_rfc' => ['hotel_id', 'rfc'],
            'uk_proveedores_hotel_nombre_activo' => ['hotel_id', 'nombre_activo_key'],
        ];
        $stmt = $pdo->prepare(
            'SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db
               AND TABLE_NAME = :table
               AND INDEX_NAME IN ("uk_proveedores_hotel_rfc", "uk_proveedores_hotel_nombre_activo")
             ORDER BY INDEX_NAME, SEQ_IN_INDEX'
        );
        $stmt->execute(['db' => $database, 'table' => 'proveedores']);
        $providerIndexes = [];
        foreach ($stmt->fetchAll() as $row) {
            $providerIndexes[$row['INDEX_NAME']]['non_unique'] = (int) $row['NON_UNIQUE'];
            $providerIndexes[$row['INDEX_NAME']]['columns'][] = $row['COLUMN_NAME'];
        }

        foreach ($expectedProviderUniqueIndexes as $indexName => $columns) {
            if (
                isset($providerIndexes[$indexName])
                && ($providerIndexes[$indexName]['non_unique'] ?? 1) === 0
                && ($providerIndexes[$indexName]['columns'] ?? []) === $columns
            ) {
                hcOk('Indice unico proveedores OK: ' . $indexName . ' (' . implode(', ', $columns) . ').');
            } else {
                hcError(
                    'Indice unico proveedores faltante o incorrecto: ' . $indexName,
                    'Revisar migracion Fase 2J antes de confiar en constraints SQL de proveedores.'
                );
            }
        }

        if (hcTableExists($pdo, $database, 'hotel_modulos') && hcTableExists($pdo, $database, 'modulos')) {
            $stmt = $pdo->query(
                "SELECT COUNT(*)
                 FROM proveedores p
                 WHERE NOT EXISTS (
                     SELECT 1
                     FROM hotel_modulos hm
                     INNER JOIN modulos m ON m.id = hm.modulo_id
                     WHERE hm.hotel_id = p.hotel_id
                       AND hm.activo = 1
                       AND m.activo_global = 1
                       AND m.clave = 'inventario'
                 )"
            );
            $providersWithoutInventory = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($providersWithoutInventory === 0) {
                hcOk('No hay proveedores en hoteles sin modulo inventario activo.');
            } else {
                hcWarning(
                    'Hay proveedores en hoteles sin modulo inventario activo: ' . $providersWithoutInventory,
                    'Revisar hotel_modulos antes de ocultar o bloquear el catalogo para esos hoteles.'
                );
            }
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT hotel_id, rfc
                 FROM proveedores
                 WHERE rfc IS NOT NULL AND rfc <> ''
                 GROUP BY hotel_id, rfc
                 HAVING COUNT(*) > 1
             ) duplicados_rfc"
        );
        $duplicateRfcGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateRfcGroups === 0) {
            hcOk('No hay RFC duplicados por hotel en proveedores.');
        } else {
            hcWarning(
                'Hay grupos de RFC duplicado por hotel en proveedores: ' . $duplicateRfcGroups,
                'Revisar catalogo manualmente antes de usar proveedores como base de compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT hotel_id, nombre
                 FROM proveedores
                 WHERE activo = 1
                 GROUP BY hotel_id, nombre
                 HAVING COUNT(*) > 1
             ) duplicados_nombre"
        );
        $duplicateNameGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateNameGroups === 0) {
            hcOk('No hay nombres activos duplicados por hotel en proveedores.');
        } else {
            hcWarning(
                'Hay grupos de nombre activo duplicado por hotel en proveedores: ' . $duplicateNameGroups,
                'Fusionar o desactivar duplicados manualmente antes de construir compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND notas LIKE '%Importado desde movimientos_caja.proveedor en Fase 2L%'"
        );
        $phase2LProviders = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($phase2LProviders === 29) {
            hcOk('Importacion Fase 2L de proveedores Los Cedros aplicada: 29 proveedores.');
        } elseif ($phase2LProviders === 0) {
            hcWarning(
                'Importacion Fase 2L de proveedores Los Cedros aun no aplicada.',
                'Aplicar importar_proveedores_los_cedros.php solo despues de backup si el catalogo historico ya fue aprobado.'
            );
        } else {
            hcWarning(
                'Importacion Fase 2L de proveedores Los Cedros tiene conteo inesperado: ' . $phase2LProviders,
                'Revisar lista de revision y proveedores con notas Fase 2L antes de continuar.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND nombre IN ('huésped', 'Huesped', 'no se', 'sin definir')"
        );
        $phase2LExcludedInserted = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($phase2LExcludedInserted === 0) {
            hcOk('Candidatos excluidos Fase 2L no fueron insertados como proveedores.');
        } else {
            hcWarning(
                'Candidatos excluidos Fase 2L aparecen insertados: ' . $phase2LExcludedInserted,
                'Revisar manualmente huésped/no se/sin definir antes de construir compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND activo = 1"
        );
        $losCedrosActiveProviders = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($losCedrosActiveProviders >= 29) {
            hcOk('Los Cedros tiene proveedores activos disponibles para fases futuras: ' . $losCedrosActiveProviders . '.');
        } else {
            hcWarning(
                'Los Cedros tiene pocos proveedores activos: ' . $losCedrosActiveProviders,
                'Validar catalogo antes de habilitar Compras o recepciones.'
            );
        }
    } else {
        hcWarning(
            'Catalogo proveedores Fase 2F aun no existe.',
            'Ejecutar 20260614_004_fase_2f_catalogo_proveedores.sql tras backup antes de usar /proveedores.'
        );
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
        if (!hcTableExists($pdo, $database, $table)) {
            hcError(
                'Tabla minima Fase 2O faltante: ' . $table,
                'Aplicar 20260615_002_fase_2n_compras_minimas.sql solo despues de backup confirmado.'
            );
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            hcError(
                'Tabla minima Fase 2O incompleta: ' . $table . '. Faltan: ' . implode(', ', $missingColumns),
                'Restaurar backup o reconciliar estructura antes de construir flujo de Compras.'
            );
        } else {
            hcOk('Tabla minima Fase 2O disponible: ' . $table);
        }

        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table) . ' WHERE hotel_id IS NULL');
        $nullHotelRows = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($nullHotelRows === 0) {
            hcOk($table . ' tiene hotel_id completo.');
        } else {
            hcError($table . ' tiene hotel_id NULL: ' . $nullHotelRows, 'Corregir aislamiento por hotel antes de exponer Compras.');
        }

        $rows = hcCountRows($pdo, $table);
        if ($rows === 0) {
            hcOk($table . ' esta vacia; no hay escrituras operativas de Compras.');
        } elseif ($table === 'compras') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM compras WHERE estado <> 'borrador'");
            $nonDraftRows = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($nonDraftRows === 0) {
                hcOk('compras contiene solo borradores Fase 2R: ' . $rows . '.');
            } else {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM compras
                     WHERE estado <> 'borrador'
                       AND NOT (estado = 'recibida' AND fecha_recepcion IS NOT NULL)"
                );
                $unexpectedNonDraftRows = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($unexpectedNonDraftRows === 0) {
                    hcOk('compras no borrador consistentes con recepcion Fase 2W: ' . $nonDraftRows . '.');
                } else {
                    hcWarning(
                        'compras no borrador inconsistentes: ' . $unexpectedNonDraftRows . ' de ' . $nonDraftRows,
                        'Auditar origen antes de habilitar pagos, CxP o cancelaciones.'
                    );
                }
            }
        } elseif ($table === 'compra_detalles') {
            $stmt = $pdo->query('SELECT COUNT(*) FROM compra_detalles WHERE movimiento_inventario_id IS NOT NULL');
            $linkedMovements = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($linkedMovements === 0) {
                hcOk('compra_detalles no tiene movimientos de inventario vinculados.');
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
                    hcOk('compra_detalles tiene movimientos vinculados consistentes con Fase 2W: ' . $linkedMovements . '.');
                } else {
                    hcWarning(
                        'compra_detalles tiene movimientos vinculados inconsistentes: ' . $inconsistentLinkedMovements . ' de ' . $linkedMovements,
                        'Auditar detalles antes de habilitar pagos, CxP o devoluciones.'
                    );
                }
            }
        }
    }

    if (hcTableExists($pdo, $database, 'compras') && hcTableExists($pdo, $database, 'compra_detalles')) {
        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT d.compra_id, d.producto_id
                 FROM compra_detalles d
                 INNER JOIN compras c ON c.id = d.compra_id
                 WHERE c.estado = 'borrador'
                 GROUP BY d.compra_id, d.producto_id
                 HAVING COUNT(*) > 1
             ) productos_repetidos"
        );
        $duplicateProductGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateProductGroups === 0) {
            hcOk('Fase 2U: borradores de compras sin productos repetidos.');
        } else {
            hcOk(
                'Fase 2U: productos repetidos permitidos como un movimiento por linea. Grupos detectados: ' . $duplicateProductGroups . '.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM compra_detalles d
             INNER JOIN compras c ON c.id = d.compra_id
             LEFT JOIN proveedores p
                    ON p.id = c.proveedor_id
                   AND p.hotel_id = c.hotel_id
                   AND p.activo = 1
             LEFT JOIN inventario_productos ip
                    ON ip.id = d.producto_id
                   AND ip.hotel_id = d.hotel_id
                   AND ip.activo = 1
             WHERE c.estado = 'borrador'
               AND (p.id IS NULL OR ip.id IS NULL OR d.hotel_id <> c.hotel_id)"
        );
        $invalidDraftLines = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($invalidDraftLines === 0) {
            hcOk('Fase 2U: borradores tienen proveedor/productos activos y scoped por hotel.');
        } else {
            hcError(
                'Fase 2U: borradores con proveedor/producto invalido o hotel_id cruzado: ' . $invalidDraftLines,
                'Corregir borradores antes de habilitar recepcion.'
            );
        }
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM migrations
         WHERE nombre = '20260615_002_fase_2n_compras_minimas.sql'
           AND estado = 'ejecutada'"
    );
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 1) {
        hcOk('Migracion Fase 2O de compras minimas registrada como ejecutada.');
    } else {
        hcError(
            'Migracion Fase 2O de compras minimas no esta registrada como ejecutada.',
            'Registrar la migracion solo si las tablas fueron creadas correctamente.'
        );
    }

    $futurePurchasingTables = [
        'proveedor_contactos',
        'compra_pagos',
        'documentos_proveedor',
    ];
    foreach ($futurePurchasingTables as $table) {
        if (hcTableExists($pdo, $database, $table)) {
            hcWarning(
                'Tabla futura de compras/CxP detectada fuera del alcance Fase 2O: ' . $table,
                'Validar origen antes de usarla; Fase 2O solo habilita compras minimas sin pagos, CxP ni documentos.'
            );
        } else {
            hcOk('Tabla futura no creada en Fase 2O: ' . $table);
        }
    }

    if (hcTableExists($pdo, $database, 'cuentas_por_pagar') && hcTableExists($pdo, $database, 'cuentas_por_pagar_movimientos')) {
        $cxpRequiredColumns = [
            'id', 'hotel_id', 'proveedor_id', 'compra_id', 'folio',
            'descripcion', 'fecha_emision', 'fecha_vencimiento', 'estado',
            'moneda', 'subtotal', 'impuestos', 'total', 'saldo', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ];
        $cxpMissingColumns = [];
        foreach ($cxpRequiredColumns as $column) {
            if (!hcColumnExists($pdo, $database, 'cuentas_por_pagar', $column)) {
                $cxpMissingColumns[] = $column;
            }
        }

        $cxpMovRequiredColumns = [
            'id', 'cuenta_por_pagar_id', 'hotel_id', 'tipo_movimiento',
            'monto', 'saldo_anterior', 'saldo_posterior', 'referencia',
            'notas', 'usuario_id', 'created_at',
        ];
        $cxpMovMissingColumns = [];
        foreach ($cxpMovRequiredColumns as $column) {
            if (!hcColumnExists($pdo, $database, 'cuentas_por_pagar_movimientos', $column)) {
                $cxpMovMissingColumns[] = $column;
            }
        }

        if (empty($cxpMissingColumns) && empty($cxpMovMissingColumns)) {
            hcOk('Tablas Fase 3B de CxP base existen con columnas requeridas.');
        } else {
            hcError(
                'Tablas Fase 3B de CxP incompletas. Faltan CxP: ' . implode(', ', $cxpMissingColumns) . '. Movimientos: ' . implode(', ', $cxpMovMissingColumns),
                'No exponer CxP hasta reconciliar la migracion 20260615_003_fase_3b_cxp_base.sql.'
            );
        }

        $cxpCount = hcCountRows($pdo, 'cuentas_por_pagar');
        $cxpMovCount = hcCountRows($pdo, 'cuentas_por_pagar_movimientos');
        hcOk('CxP Fase 3B/3C-C disponible. Registros: cuentas=' . (string)$cxpCount . ', movimientos=' . (string)$cxpMovCount . '.');
        hcReportCxpConsistency($pdo, $database);

        if (hcTableExists($pdo, $database, 'migrations')) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM migrations
                 WHERE nombre = '20260615_003_fase_3b_cxp_base.sql'
                   AND estado = 'ejecutada'"
            );
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 1) {
                hcOk('Migracion Fase 3B de CxP base registrada como ejecutada.');
            } else {
                hcError(
                    'Migracion Fase 3B de CxP base no esta registrada como ejecutada.',
                    'Registrar solo si las tablas fueron creadas correctamente tras backup.'
                );
            }
        }
    } else {
        hcWarning(
            'Tablas Fase 3B de CxP base aun no existen.',
            'Aplicar la migracion 20260615_003_fase_3b_cxp_base.sql solo despues de backup.'
        );
    }

    $npTables = [
        'trabajadores' => [
            'id', 'hotel_id', 'usuario_id', 'nombre_completo', 'identificacion',
            'rol_laboral', 'telefono', 'email', 'estado', 'fecha_alta',
            'fecha_baja', 'salario_base', 'periodicidad_pago', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_pagos' => [
            'id', 'hotel_id', 'trabajador_id', 'tipo', 'efecto', 'monto',
            'concepto', 'periodo_inicio', 'periodo_fin', 'fecha', 'referencia',
            'notas', 'estado', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_anticipos' => [
            'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
            'fecha', 'motivo', 'estado', 'referencia', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_prestamos' => [
            'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
            'fecha', 'plazo_meses', 'abono_periodico', 'motivo', 'estado',
            'referencia', 'notas', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_asistencias' => [
            'id', 'hotel_id', 'trabajador_id', 'fecha', 'tipo',
            'hora_entrada', 'hora_salida', 'horas', 'horas_extra',
            'observaciones', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_documentos' => [
            'id', 'hotel_id', 'trabajador_id', 'tipo', 'nombre_original',
            'ruta_archivo', 'mime', 'tamano', 'notas', 'estado',
            'subido_por', 'created_at', 'updated_at',
        ],
    ];

    $npMissingTables = [];
    $npMissingColumns = [];
    $npRows = 0;
    foreach ($npTables as $table => $columns) {
        if (!hcTableExists($pdo, $database, $table)) {
            $npMissingTables[] = $table;
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            $npMissingColumns[] = $table . '.hotel_id';
        }

        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $npMissingColumns[] = $table . '.' . $column;
            }
        }

        $npRows += (int)(hcCountRows($pdo, $table) ?? 0);
    }

    if (empty($npMissingTables) && empty($npMissingColumns)) {
        hcOk('Personal base NP-A tiene las seis tablas aditivas con hotel_id y columnas requeridas. Registros totales=' . (string)$npRows . '.');
    } else {
        hcWarning(
            'Personal base NP-A incompleto. Tablas faltantes: ' . implode(', ', $npMissingTables) . '. Columnas faltantes: ' . implode(', ', $npMissingColumns) . '.',
            'No exponer Personal hasta aplicar o reconciliar migrations/20260616_001_fase_np_a_personal_base.sql.'
        );
    }

    if (hcTableExists($pdo, $database, 'migrations')) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM migrations
             WHERE nombre = '20260616_001_fase_np_a_personal_base.sql'
               AND estado = 'ejecutada'"
        );
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 1) {
            hcOk('Migracion Personal base NP-A registrada como ejecutada.');
        } elseif (empty($npMissingTables)) {
            hcWarning(
                'Tablas Personal base NP-A existen pero la migracion no esta registrada.',
                'Registrar solo si la migracion fue aplicada tras backup verificado.'
            );
        }
    }

    $cajaNomina = hcTableExists($pdo, $database, 'movimientos_caja')
        ? hcCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE LOWER(COALESCE(categoria, '')) LIKE '%nomina%'")
        : null;
    $categoriasNomina = hcTableExists($pdo, $database, 'categorias_movimientos')
        ? hcCountScalar($pdo, "SELECT COUNT(*) FROM categorias_movimientos WHERE LOWER(COALESCE(nombre, '')) LIKE '%nomina%'")
        : null;
    if ($cajaNomina === 0 && $categoriasNomina === 0) {
        hcOk('Personal base NP-A no genero movimientos ni categorias de Caja/Nomina.');
    } else {
        hcError(
            'Personal base NP-A detecta referencias de Caja/Nomina. movimientos=' . (string)$cajaNomina . ', categorias=' . (string)$categoriasNomina . '.',
            'Revisar antes de continuar; NP-A no debe tocar Caja ni crear categoria Nomina.'
        );
    }

    $inventoryModernTables = [
        'inventario_productos' => 'productos operativos',
        'movimientos_inventario' => 'movimientos operativos',
        'inventario_config_habitacion' => 'configuracion por tipo de habitacion',
    ];

    foreach ($inventoryModernTables as $table => $purpose) {
        if (!hcTableExists($pdo, $database, $table)) {
            hcError('Contrato moderno de inventario incompleto: falta ' . $table . '.', 'No construir inventario avanzado hasta restaurar la tabla moderna.');
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            hcError('Contrato moderno de inventario sin hotel_id: ' . $table . '.', 'Agregar aislamiento por hotel antes de usar este flujo.');
            continue;
        }

        $nullRows = hcNullHotelRows($pdo, $table);
        if ($nullRows === 0) {
            hcOk('Contrato moderno de inventario OK: ' . $table . ' (' . $purpose . ') tiene hotel_id completo.');
        } else {
            hcError('Contrato moderno de inventario con hotel_id NULL en ' . $table . ': ' . (string) $nullRows, 'Corregir backfill antes de operar inventario multihotel.');
        }
    }

    $inventoryLegacyPairs = [
        ['legacy' => 'productos', 'modern' => 'inventario_productos', 'note' => 'catalogo legacy con datos semilla/antiguos'],
        ['legacy' => 'inventario_movimientos', 'modern' => 'movimientos_inventario', 'note' => 'movimientos legacy sin registros'],
        ['legacy' => 'inventario_habitacion_config', 'modern' => 'inventario_config_habitacion', 'note' => 'configuracion legacy por habitacion sin registros'],
    ];

    foreach ($inventoryLegacyPairs as $pair) {
        $legacyExists = hcTableExists($pdo, $database, $pair['legacy']);
        $modernExists = hcTableExists($pdo, $database, $pair['modern']);
        if ($legacyExists && $modernExists) {
            $legacyCount = hcCountRows($pdo, $pair['legacy']);
            $modernCount = hcCountRows($pdo, $pair['modern']);
            hcWarning(
                'Inventario legacy congelado: ' . $pair['legacy'] . ' (' . $legacyCount . ') convive con ' . $pair['modern'] . ' (' . $modernCount . ') como ' . $pair['note'] . '.',
                $inventoryDoc && is_file($inventoryDoc)
                    ? 'Mantener congelado; seguir docs/technical/inventory_reconciliation.md antes de cualquier fusion.'
                    : 'Montar o crear docs/technical/inventory_reconciliation.md antes de continuar con Inventario avanzado.'
            );
        } elseif ($legacyExists || $modernExists) {
            hcWarning(
                'Inventario tiene solo una tabla del par legacy/moderno: ' . $pair['legacy'] . ' / ' . $pair['modern'] . '.',
                'Validar dependencias antes de limpiar nombres historicos.'
            );
        }
    }

    $duplicatePairs = [
        ['legacy' => 'push_subscriptions', 'modern' => 'pwa_push_subscriptions'],
        ['legacy' => 'huespedes_vehiculos', 'modern' => 'huesped_vehiculos'],
    ];

    foreach ($duplicatePairs as $pair) {
        $legacyExists = hcTableExists($pdo, $database, $pair['legacy']);
        $modernExists = hcTableExists($pdo, $database, $pair['modern']);
        if ($legacyExists && $modernExists) {
            $legacyCount = hcCountRows($pdo, $pair['legacy']);
            $modernCount = hcCountRows($pdo, $pair['modern']);
            hcWarning(
                'Tablas duplicadas/solapadas detectadas: ' . $pair['legacy'] . ' (' . $legacyCount . ') vs ' . $pair['modern'] . ' (' . $modernCount . ').',
                'No borrar ni fusionar; seguir docs/technical/duplicated_tables.md.'
            );
        } elseif ($legacyExists || $modernExists) {
            hcWarning(
                'Solo existe una tabla del par duplicado: ' . $pair['legacy'] . ' / ' . $pair['modern'] . '.',
                'Validar dependencias antes de limpiar nombres historicos.'
            );
        }
    }

    $viewStmt = $pdo->prepare(
        'SELECT DEFINER, SECURITY_TYPE
         FROM information_schema.VIEWS
         WHERE TABLE_SCHEMA = :db
           AND TABLE_NAME = :view'
    );
    $viewStmt->execute(['db' => $database, 'view' => 'vista_caja_actual']);
    $viewInfo = $viewStmt->fetch();

    if (!$viewInfo) {
        hcWarning(
            'vista_caja_actual no existe.',
            'Confirmar si consumidores legacy la necesitan antes de limpiar referencias.'
        );
    } else {
        try {
            $pdo->query('SELECT * FROM vista_caja_actual LIMIT 1')->fetch();
            hcOk('vista_caja_actual responde SELECT simple.');
        } catch (Throwable $e) {
            hcError(
                'vista_caja_actual no responde SELECT simple: ' . $e->getMessage(),
                'Recrear la vista con SQL SECURITY INVOKER para evitar definer invalido.'
            );
        }

        if (($viewInfo['SECURITY_TYPE'] ?? '') === 'INVOKER') {
            hcOk('vista_caja_actual usa SQL SECURITY INVOKER.');
        } else {
            hcWarning(
                'vista_caja_actual usa SECURITY_TYPE=' . ($viewInfo['SECURITY_TYPE'] ?? 'desconocido') . ' y DEFINER=' . ($viewInfo['DEFINER'] ?? 'desconocido') . '.',
                'Preferir SQL SECURITY INVOKER en local para que mysqldump no dependa de un definer heredado.'
            );
        }
    }
}

if (!is_file($routesPath)) {
    hcError('No se encontro config/routes.php.', 'No se puede validar rutas sin archivo de rutas.');
    $routes = [];
} else {
    $routes = hcParseRoutes($routesPath);
    hcOk('Rutas activas parseadas: ' . count($routes));

    $missingRouteMethods = [];
    foreach ($routes as $route) {
        $controllerFile = $controllersDir . '/' . $route['controller'] . 'Controller.php';
        if (!is_file($controllerFile)) {
            $missingRouteMethods[] = $route['method'] . ' ' . $route['path'] . ' -> controller missing ' . $route['controller'];
            continue;
        }

        $controllerCode = file_get_contents($controllerFile) ?: '';
        $action = $route['action'];
        $hasActionSuffix = preg_match('/function\s+' . preg_quote($action . 'Action', '/') . '\s*\(/', $controllerCode);
        $hasDirectMethod = preg_match('/function\s+' . preg_quote($action, '/') . '\s*\(/', $controllerCode);
        if (!$hasActionSuffix && !$hasDirectMethod) {
            $missingRouteMethods[] = $route['method'] . ' ' . $route['path'] . ' -> ' . $route['controller'] . '::' . $action;
        }
    }

    if (empty($missingRouteMethods)) {
        hcOk('No hay rutas activas apuntando a metodos inexistentes.');
    } else {
        hcError(
            'Rutas activas con controlador/metodo inexistente: ' . implode(' | ', $missingRouteMethods),
            'Corregir aliases o desactivar rutas no implementadas antes de abrir esos flujos.'
        );
    }

    $inventarioControllerPath = $controllersDir . '/InventarioController.php';
    $inventarioControllerCode = is_file($inventarioControllerPath) ? (string) file_get_contents($inventarioControllerPath) : '';
    if ($inventarioControllerCode !== '') {
        if (
            strpos($inventarioControllerCode, 'protected $table = \'productos\'') === false
            && strpos($inventarioControllerCode, 'FROM productos') === false
            && strpos($inventarioControllerCode, 'JOIN productos') === false
        ) {
            hcOk('InventarioController no usa tabla legacy productos en SQL activo.');
        } else {
            hcWarning(
                'InventarioController contiene referencias SQL a productos legacy.',
                'Mover cualquier lectura activa al contrato moderno inventario_productos antes de Inventario avanzado.'
            );
        }

        if (
            hcRouteExists($routes, 'inventario/generarPdfMovimientos', 'post')
            && strpos($inventarioControllerCode, 'FROM movimientos_inventario m') !== false
            && strpos($inventarioControllerCode, 'WHERE m.hotel_id = ?') !== false
            && strpos($inventarioControllerCode, 'p.hotel_id = m.hotel_id') !== false
        ) {
            hcOk('PDF de movimientos de inventario filtra movimientos/productos por hotel_id.');
        } elseif (hcRouteExists($routes, 'inventario/generarPdfMovimientos', 'post')) {
            hcError(
                'PDF de movimientos de inventario no muestra aislamiento hotel_id completo.',
                'Filtrar movimientos_inventario por hotel_id y unir productos/habitaciones por el mismo hotel.'
            );
        }

        $guardarConfigCode = hcExtractMethodCode($inventarioControllerCode, 'guardarConfiguracionAction');
        if (
            $guardarConfigCode !== ''
            && strpos($guardarConfigCode, 'actualizarConfiguracion') !== false
            && strpos($guardarConfigCode, 'is_array($productos)') !== false
            && strpos($guardarConfigCode, 'intval($cantidad)') === false
        ) {
            hcOk('InventarioController delega guardado de configuracion al modelo sin silenciar validaciones.');
        } else {
            hcError(
                'InventarioController puede estar casteando o ignorando validaciones de configuracion.',
                'Delegar cantidades crudas a Inventario::actualizarConfiguracion y dejar que el modelo valide.'
            );
        }

        if (
            $guardarConfigCode !== ''
            && strpos($guardarConfigCode, 'registrarAuditoriaConfiguracionInventario') !== false
            && strpos($guardarConfigCode, '$cambiosAuditables') !== false
            && strpos($inventarioControllerCode, 'inventario.configuracion_actualizada') !== false
            && strpos($inventarioControllerCode, 'datos_antes') !== false
            && strpos($inventarioControllerCode, 'datos_despues') !== false
        ) {
            hcOk('Configuracion de inventario registra auditoria minima Fase 2E cuando hay cambios reales.');
        } else {
            hcWarning(
                'Auditoria de configuracion de inventario no esta implementada o no es detectable.',
                'Mantener documentado como pendiente o registrar inventario.configuracion_actualizada sin romper el guardado.'
            );
        }

        $officialInventoryRoutes = [
            ['get', 'inventario'],
            ['get', 'inventario/configuracion'],
            ['post', 'inventario/guardarConfiguracion'],
            ['get', 'inventario/movimientos'],
            ['get', 'inventario/exportar'],
            ['post', 'inventario/generarPdfMovimientos'],
            ['get', 'inventario/ajuste/1', true],
            ['post', 'inventario/ajuste/1', true],
            ['get', 'api/inventario/alertas'],
            ['get', 'api/inventario/verificar-stock/1', true],
            ['get', 'api/inventario/preview-checkin/1', true],
        ];

        foreach ($officialInventoryRoutes as $routeCheck) {
            [$method, $path] = $routeCheck;
            $isPattern = (bool)($routeCheck[2] ?? false);
            $exists = $isPattern
                ? hcRoutePatternExists($routes, $path, $method)
                : hcRouteExists($routes, $path, $method);

            if ($exists) {
                hcOk('Ruta oficial de inventario registrada: ' . strtoupper($method) . ' /' . $path);
            } else {
                hcError(
                    'Falta ruta oficial de inventario: ' . strtoupper($method) . ' /' . $path,
                    'Restaurar solo la ruta oficial existente del contrato moderno; no reactivar rutas legacy.'
                );
            }
        }
    }

    $inventoryModelPath = $appRoot . '/app/models/Inventario.php';
    $inventoryModelCode = is_file($inventoryModelPath) ? (string) file_get_contents($inventoryModelPath) : '';
    if ($inventoryModelCode !== '') {
        if (
            strpos($inventoryModelCode, "protected \$table = 'inventario_productos'") !== false
            && strpos($inventoryModelCode, 'FROM inventario_config_habitacion ich') !== false
            && strpos($inventoryModelCode, 'JOIN inventario_productos p') !== false
            && strpos($inventoryModelCode, 'p.hotel_id = ich.hotel_id') !== false
            && strpos($inventoryModelCode, 'AND ich.hotel_id = ?') !== false
        ) {
            hcOk('Inventario::getConfiguracionCompleta usa contrato moderno scoped por hotel.');
        } else {
            hcError(
                'Inventario::getConfiguracionCompleta no muestra contrato moderno completo.',
                'Debe leer inventario_config_habitacion + inventario_productos y unir por hotel_id.'
            );
        }

        if (
            strpos($inventoryModelCode, 'WHERE id = ? AND hotel_id = ?') !== false
            && strpos($inventoryModelCode, 'WHERE tipo_habitacion = ? AND producto_id = ? AND hotel_id = ?') !== false
            && strpos($inventoryModelCode, '(tipo_habitacion, producto_id, cantidad_descontar, activo, hotel_id, created_at)') !== false
        ) {
            hcOk('Inventario::actualizarConfiguracion valida producto y configuracion por hotel_id.');
        } else {
            hcError(
                'Inventario::actualizarConfiguracion no muestra aislamiento hotel_id completo.',
                'No guardar configuracion por habitacion sin validar producto y registro por hotel.'
            );
        }

        if (
            strpos($inventoryModelCode, 'tipoHabitacionPerteneceAlHotel') !== false
            && strpos($inventoryModelCode, 'FROM tipos_habitacion') !== false
            && strpos($inventoryModelCode, 'codigo = ? AND hotel_id = ? AND activo = 1') !== false
            && strpos($inventoryModelCode, 'descuento_automatico = 1') !== false
            && strpos($inventoryModelCode, 'La cantidad de configuracion no puede ser negativa') !== false
            && strpos($inventoryModelCode, 'throw $e') !== false
        ) {
            hcOk('Configuracion oficial Fase 2D valida tipo/producto/cantidad por hotel antes de guardar.');
        } else {
            hcError(
                'Configuracion oficial Fase 2D no muestra validaciones completas.',
                'Validar tipo_habitacion del hotel, producto activo/automatico del hotel y cantidad numerica no negativa.'
            );
        }

        if (
            strpos($inventoryModelCode, "'changed' =>") !== false
            && strpos($inventoryModelCode, "'cantidad_anterior' =>") !== false
            && strpos($inventoryModelCode, "'cantidad_nueva' =>") !== false
            && strpos($inventoryModelCode, "'descuento_automatico_cambio' => false") !== false
        ) {
            hcOk('Inventario::actualizarConfiguracion devuelve metadatos auditables Fase 2E.');
        } else {
            hcWarning(
                'Inventario::actualizarConfiguracion no expone metadatos auditables claros.',
                'Si se audita configuracion, capturar cantidad anterior/nueva y producto/tipo sin nueva migracion.'
            );
        }
    } else {
        hcError('No se encontro app/models/Inventario.php.', 'No se puede validar contrato moderno de inventario.');
    }

    $inventoryServicePath = $appRoot . '/app/services/InventarioService.php';
    $inventoryServiceCode = is_file($inventoryServicePath) ? (string) file_get_contents($inventoryServicePath) : '';
    if ($inventoryServiceCode !== '') {
        if (
            strpos($inventoryServiceCode, 'JOIN inventario_productos ip') !== false
            && strpos($inventoryServiceCode, 'ip.hotel_id = ich.hotel_id') !== false
            && strpos($inventoryServiceCode, 'AND ich.hotel_id = ?') !== false
            && strpos($inventoryServiceCode, 'INSERT INTO movimientos_inventario') !== false
            && strpos($inventoryServiceCode, 'WHERE id = ? AND hotel_id = ?') !== false
        ) {
            hcOk('InventarioService usa inventario_config_habitacion/inventario_productos/movimientos_inventario con hotel_id.');
        } else {
            hcError(
                'InventarioService no muestra contrato moderno scoped completo.',
                'No ejecutar descuento/preview de inventario si mezcla productos legacy o ignora hotel_id.'
            );
        }
    } else {
        hcError('No se encontro app/services/InventarioService.php.', 'No se puede validar consumo automatico de inventario.');
    }

    $movementModelPath = $appRoot . '/app/models/MovimientoInventario.php';
    $movementModelCode = is_file($movementModelPath) ? (string) file_get_contents($movementModelPath) : '';
    if ($movementModelCode !== '') {
        if (
            strpos($movementModelCode, "protected \$table = 'movimientos_inventario'") !== false
            && strpos($movementModelCode, 'productoPerteneceAlHotel') !== false
            && strpos($movementModelCode, 'habitacionPerteneceAlHotel') !== false
            && strpos($movementModelCode, 'p.hotel_id = m.hotel_id') !== false
        ) {
            hcOk('MovimientoInventario mantiene movimientos en tabla moderna y valida hotel.');
        } else {
            hcError(
                'MovimientoInventario no muestra validacion moderna de hotel completa.',
                'Los movimientos manuales deben validar producto/habitacion y usar movimientos_inventario.'
            );
        }
    } else {
        hcError('No se encontro app/models/MovimientoInventario.php.', 'No se puede validar trazabilidad moderna de inventario.');
    }

    $apiControllerPath = $controllersDir . '/ApiController.php';
    $apiCode = is_file($apiControllerPath) ? (string) file_get_contents($apiControllerPath) : '';
    if ($apiCode !== '') {
        $alertasCode = hcExtractMethodCode($apiCode, 'alertasInventarioAction');
        $stockCode = hcExtractMethodCode($apiCode, 'verificarStockHabitacionAction');
        $previewCode = hcExtractMethodCode($apiCode, 'previewCheckinInventarioAction');

        if (
            $alertasCode !== ''
            && strpos($alertasCode, 'FROM inventario_productos') !== false
            && strpos($alertasCode, 'WHERE hotel_id = ?') !== false
            && strpos($alertasCode, 'FROM productos') === false
        ) {
            hcOk('/api/inventario/alertas usa inventario_productos scoped y no productos legacy.');
        } else {
            hcError(
                '/api/inventario/alertas no muestra fuente moderna scoped.',
                'Debe derivar alertas desde inventario_productos.hotel_id, no desde productos legacy.'
            );
        }

        if ($stockCode !== '' && strpos($stockCode, 'new InventarioService') !== false) {
            hcOk('/api/inventario/verificar-stock delega en InventarioService moderno.');
        } else {
            hcWarning(
                '/api/inventario/verificar-stock no muestra delegacion clara a InventarioService.',
                'Mantener la verificacion de stock en el servicio moderno scoped.'
            );
        }

        if ($previewCode !== '' && strpos($previewCode, 'Preview de inventario aun no disponible con fuente scoped') !== false) {
            hcOk('/api/inventario/preview-checkin mantiene respuesta controlada sin mezclar fuentes legacy.');
        } else {
            hcWarning(
                '/api/inventario/preview-checkin no muestra respuesta controlada documentada.',
                'No habilitar preview real hasta definir fuente scoped completa.'
            );
        }

        if (
            $previewCode !== ''
            && stripos($previewCode, 'UPDATE ') === false
            && stripos($previewCode, 'INSERT ') === false
            && stripos($previewCode, 'DELETE ') === false
        ) {
            hcOk('/api/inventario/preview-checkin es lectura pura en Fase 2E.');
        } elseif ($previewCode !== '') {
            hcError(
                '/api/inventario/preview-checkin contiene escritura potencial.',
                'El preview por reservacion debe ser lectura pura y no modificar stock.'
            );
        }

        $legacyApiMethod = hcExtractMethodCode($apiCode, 'getProductosDescuentoReservacion');
        $legacyApiRoutes = array_filter($routes, static function (array $route): bool {
            return strtolower((string) $route['controller']) === 'api'
                && (string) $route['action'] === 'getProductosDescuentoReservacion';
        });

        if (!empty($legacyApiRoutes)) {
            hcError(
                'ApiController::getProductosDescuentoReservacion esta ruteado aunque es legacy.',
                'Retirar la ruta y usar /api/inventario/verificar-stock o InventarioService scoped.'
            );
        } elseif ($legacyApiMethod !== '' && strpos($legacyApiMethod, 'LEGACY INVENTARIO FASE 2D') !== false) {
            hcOk('ApiController::getProductosDescuentoReservacion queda congelado y sin ruta activa.');
        } elseif ($legacyApiMethod !== '') {
            hcWarning(
                'ApiController::getProductosDescuentoReservacion existe sin marca Fase 2D.',
                'Congelarlo o modernizarlo antes de exponer cualquier preview por reservacion.'
            );
        }

        if (
            $legacyApiMethod !== ''
            && (strpos($legacyApiMethod, 'ConfiguracionHabitacionService') !== false || strpos($legacyApiMethod, 'services/ConfiguracionHabitacionService.php') !== false)
        ) {
            hcWarning(
                'ApiController contiene metodo legacy no ruteado getProductosDescuentoReservacion con dependencia antigua.',
                'No registrar esta ruta; reemplazarla por InventarioService scoped si una fase futura necesita ese preview.'
            );
        }
    }

    $legacyInventoryFiles = [
        $appRoot . '/app/models/ConfiguracionInventario.php',
        $appRoot . '/app/services/ConfiguracionInventarioService.php',
        $appRoot . '/app/models/InventarioReportes.php',
        $appRoot . '/app/helpers/integracion_inventario.php',
        $appRoot . '/app/views/inventario/ConfiguracionHabitacionService.php',
    ];

    foreach ($legacyInventoryFiles as $legacyPath) {
        if (!is_file($legacyPath)) {
            continue;
        }

        $legacyCode = (string) file_get_contents($legacyPath);
        if (strpos($legacyCode, 'LEGACY INVENTARIO FASE 2C') !== false) {
            hcWarning(
                'Archivo legacy de inventario congelado: ' . str_replace($appRoot . '/', '', $legacyPath),
                'Mantener sin rutas nuevas y sin ejecucion automatica hasta una fase de retiro/migracion.'
            );
        } else {
            hcWarning(
                'Archivo legacy de inventario sin marca Fase 2C: ' . str_replace($appRoot . '/', '', $legacyPath),
                'Marcarlo como congelado o migrarlo explicitamente antes de conectarlo a rutas activas.'
            );
        }
    }

    $legacyViewConfigPath = $appRoot . '/app/views/inventario/configuracion-habitacion.php';
    if (is_file($legacyViewConfigPath) && !hcRouteExists($routes, 'inventario/configuracion-habitacion', 'get')) {
        hcWarning(
            'Vista legacy inventario/configuracion-habitacion.php existe sin ruta oficial.',
            'Mantenerla desconectada; usar /inventario/configuracion como flujo oficial.'
        );
    }

    $integracionInventarioPath = $appRoot . '/app/helpers/integracion_inventario.php';
    $integracionInventarioCode = is_file($integracionInventarioPath) ? (string) file_get_contents($integracionInventarioPath) : '';
    if ($integracionInventarioCode !== '') {
        $hasDestructiveSql = strpos($integracionInventarioCode, 'DELETE FROM movimientos_inventario') !== false
            || strpos($integracionInventarioCode, 'UPDATE productos') !== false;
        $hasLegacyWriteGuard = strpos($integracionInventarioCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
            && strpos($integracionInventarioCode, 'inventario_legacy_fase2d_escritura_permitida(__FUNCTION__)') !== false;

        if ($hasDestructiveSql && $hasLegacyWriteGuard) {
            hcOk('integracion_inventario.php conserva SQL legacy peligroso detras de guarda Fase 2D.');
        } elseif ($hasDestructiveSql) {
            hcError(
                'integracion_inventario.php contiene SQL destructivo sin guarda Fase 2D.',
                'Bloquear funciones legacy de escritura o mover esos ejemplos a documentacion no ejecutable.'
            );
        } elseif (strpos($integracionInventarioCode, 'LEGACY INVENTARIO FASE 2D') !== false) {
            hcOk('integracion_inventario.php esta marcado como legacy Fase 2D.');
        }
    }

    if ($duplicatedTablesDoc && is_file($duplicatedTablesDoc)) {
        $duplicatedDocCode = (string) file_get_contents($duplicatedTablesDoc);
        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2C') !== false
            && strpos($duplicatedDocCode, 'ConfiguracionInventario') !== false
            && strpos($duplicatedDocCode, 'ConfiguracionHabitacionService') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra congelamiento Fase 2C.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2C.',
                'Actualizar docs/technical/duplicated_tables.md con referencias legacy congeladas.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2D') !== false
            && strpos($duplicatedDocCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
            && strpos($duplicatedDocCode, 'getProductosDescuentoReservacion') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra neutralizacion Fase 2D.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2D.',
                'Actualizar docs/technical/duplicated_tables.md con decision de preview legacy y helper peligroso.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2E') !== false
            && strpos($duplicatedDocCode, 'compras') !== false
            && strpos($duplicatedDocCode, 'movimientos_inventario') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra decision Fase 2E para compras futuras.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2E.',
                'Actualizar docs/technical/duplicated_tables.md con el contrato de compras y tablas legacy que no deben usarse.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2F') !== false
            && strpos($duplicatedDocCode, 'movimientos_caja.proveedor') !== false
            && strpos($duplicatedDocCode, 'proveedores') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra alcance Fase 2F de proveedores.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2F.',
                'Actualizar docs/technical/duplicated_tables.md aclarando que proveedores no reconcilia movimientos_caja.proveedor.'
            );
        }
    } elseif ($docsTechnicalDir) {
        hcWarning(
            'No se encontro docs/technical/duplicated_tables.md.',
            'Documentar tablas duplicadas antes de limpiar productos/inventario_movimientos.'
        );
    }

    $productoRoutes = array_filter($routes, static function (array $route): bool {
        return strtolower((string) $route['controller']) === 'producto';
    });
    if (empty($productoRoutes)) {
        hcOk('No hay rutas activas hacia ProductoController legacy.');
    } else {
        hcError(
            'Hay rutas activas hacia ProductoController legacy.',
            'Retirar esas rutas o migrarlas al contrato inventario_productos antes de operar multihotel.'
        );
    }

    $providerExpectedRoutes = [
        ['method' => 'get', 'path' => 'proveedores'],
        ['method' => 'get', 'path' => 'proveedores/crear'],
        ['method' => 'post', 'path' => 'proveedores'],
        ['method' => 'get', 'path' => 'proveedores/1', 'pattern' => true],
        ['method' => 'get', 'path' => 'proveedores/1/editar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/actualizar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/desactivar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/reactivar', 'pattern' => true],
    ];

    $missingProviderRoutes = [];
    foreach ($providerExpectedRoutes as $expectedRoute) {
        $exists = !empty($expectedRoute['pattern'])
            ? hcRoutePatternExists($routes, $expectedRoute['path'], $expectedRoute['method'])
            : hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method']);
        if (!$exists) {
            $missingProviderRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingProviderRoutes)) {
        hcOk('Rutas minimas Fase 3A de proveedores, incluida ficha read-only, estan registradas.');
    } else {
        hcError(
            'Rutas Fase 3A de proveedores faltantes: ' . implode(', ', $missingProviderRoutes),
            'Registrar CRUD minimo y GET /proveedores/{id} read-only antes de probar Proveedores v2.'
        );
    }

    $purchaseExpectedRoutes = [
        ['method' => 'get', 'path' => 'compras'],
        ['method' => 'get', 'path' => 'compras/crear'],
        ['method' => 'get', 'path' => 'compras/reportes/recibidas'],
        ['method' => 'get', 'path' => 'compras/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'compras'],
        ['method' => 'post', 'path' => 'compras/{id:[0-9]+}/recibir'],
    ];
    $missingPurchaseRoutes = [];
    foreach ($purchaseExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingPurchaseRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingPurchaseRoutes)) {
        hcOk('Rutas minimas Fase 2Y de Compras, reporte, detalle y recepcion estan registradas.');
    } else {
        hcError(
            'Rutas Fase 2Y de Compras faltantes: ' . implode(', ', $missingPurchaseRoutes),
            'Registrar solo GET /compras, GET /compras/reportes/recibidas, GET /compras/{id}, GET /compras/crear, POST /compras y POST /compras/{id}/recibir.'
        );
    }

    $cxpExpectedRoutes = [
        ['method' => 'get', 'path' => 'cuentas-por-pagar'],
        ['method' => 'get', 'path' => 'cuentas-por-pagar/generacion-preview'],
        ['method' => 'post', 'path' => 'cuentas-por-pagar/generar-desde-compra/{id:[0-9]+}'],
        ['method' => 'get', 'path' => 'cuentas-por-pagar/{id:[0-9]+}'],
    ];
    $missingCxpRoutes = [];
    foreach ($cxpExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingCxpRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingCxpRoutes)) {
        hcOk('Rutas Fase 3B/3C-C de CxP, preview GET y generacion manual POST estan registradas.');
    } else {
        hcWarning(
            'Rutas Fase 3B/3C-C de CxP faltantes: ' . implode(', ', $missingCxpRoutes),
            'Registrar GET listado/preview/detalle y POST manual /cuentas-por-pagar/generar-desde-compra/{id}; sin pagos ni Caja.'
        );
    }

    $forbiddenPurchaseRoutes = [];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
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
            $forbiddenPurchaseRoutes[] = $signature;
        }
    }

    if (empty($forbiddenPurchaseRoutes)) {
        hcOk('Solo hay compras minimas y CxP/preview/generacion manual Fase 3B/3C-C; no hay rutas de pagos, contactos ni documentos de compras.');
    } else {
        hcError(
            'Rutas fuera del alcance Fase 3C-C detectadas: ' . implode(' | ', $forbiddenPurchaseRoutes),
            'Retirar rutas que no sean Compras basicas, CxP listado/preview/detalle, POST generar CxP desde compra y POST /compras/{id}/recibir.'
        );
    }

    $providerModelPath = $appRoot . '/app/models/Proveedor.php';
    $providerControllerPath = $controllersDir . '/ProveedorController.php';
    if (is_file($providerModelPath) && is_file($providerControllerPath)) {
        $providerModelCode = (string) file_get_contents($providerModelPath);
        $providerControllerCode = (string) file_get_contents($providerControllerPath);

        if (
            strpos($providerModelCode, "protected \$table = 'proveedores'") !== false
            && strpos($providerModelCode, 'hotel_id') !== false
            && preg_match('/WHERE\s+id\s*=\s*\?\s+AND\s+hotel_id\s*=\s*\?/i', $providerModelCode)
            && preg_match('/UPDATE\s+proveedores.*WHERE\s+id\s*=\s*\?\s+AND\s+hotel_id\s*=\s*\?/is', $providerModelCode)
        ) {
            hcOk('Proveedor model usa proveedores con aislamiento hotel_id.');
        } else {
            hcError(
                'Proveedor model no muestra aislamiento hotel_id completo.',
                'No usar catalogo proveedores hasta filtrar lecturas/escrituras por hotel_id.'
            );
        }

        if (
            strpos($providerModelCode, 'validarDuplicadoPorHotel') !== false
            && strpos($providerModelCode, 'buscarDuplicado') !== false
            && strpos($providerModelCode, 'rfc') !== false
            && strpos($providerModelCode, 'nombre') !== false
            && strpos($providerModelCode, 'activo = 1') !== false
        ) {
            hcOk('Proveedor model valida duplicados por RFC y nombre activo en Fase 2I.');
        } else {
            hcWarning(
                'Proveedor model no muestra validacion de duplicados Fase 2I.',
                'Validar RFC por hotel y nombre activo por hotel antes de construir compras.'
            );
        }

        $providerForbiddenCode = $providerModelCode . "\n" . $providerControllerCode;
        $hasForbiddenProviderScope = strpos($providerForbiddenCode, 'movimientos_caja') !== false
            || strpos($providerForbiddenCode, 'cuentas_por_pagar') !== false
            || strpos($providerForbiddenCode, 'compra_pagos') !== false
            || strpos($providerForbiddenCode, 'documentos_proveedor') !== false
            || preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(compras|compra_detalles|inventario_productos|movimientos_inventario|movimientos_caja|cuentas_por_pagar)\b/i', $providerForbiddenCode);

        if (!$hasForbiddenProviderScope) {
            hcOk('Proveedor Fase 3A no toca Caja, CxP, pagos ni escrituras de compras/inventario.');
        } else {
            hcError(
                'Proveedor Fase 3A contiene referencias fuera de alcance o escrituras prohibidas.',
                'Mantener Proveedores v2 en lectura de compras; sin Caja, CxP, pagos ni escrituras de compras/inventario.'
            );
        }

        if (
            strpos($providerControllerCode, 'function verAction') !== false
            && strpos($providerControllerCode, 'proveedores/ver') !== false
            && strpos($providerControllerCode, 'comprasDisponibles') !== false
            && strpos($providerControllerCode, 'resumenComprasPorProveedor') !== false
            && strpos($providerControllerCode, 'comprasRecientesPorProveedor') !== false
            && strpos($providerModelCode, 'function comprasDisponibles') !== false
            && strpos($providerModelCode, 'function resumenComprasPorProveedor') !== false
            && strpos($providerModelCode, 'function comprasRecientesPorProveedor') !== false
            && strpos($providerModelCode, 'FROM compras') !== false
            && strpos($providerModelCode, 'JOIN compra_detalles') !== false
        ) {
            hcOk('Proveedor Fase 3A expone ficha read-only e historial de compras por hotel.');
        } else {
            hcWarning(
                'Proveedor Fase 3A no muestra ficha read-only completa.',
                'Agregar verAction, proveedores/ver.php y metodos read-only resumenComprasPorProveedor/comprasRecientesPorProveedor.'
            );
        }

        if (
            strpos($providerControllerCode, 'AuditService::record') !== false
            && strpos($providerControllerCode, 'proveedores.creado') !== false
            && strpos($providerControllerCode, 'proveedores.actualizado') !== false
            && strpos($providerControllerCode, 'proveedores.desactivado') !== false
            && strpos($providerControllerCode, 'proveedores.reactivado') !== false
        ) {
            hcOk('ProveedorController integra auditoria minima Fase 2F.');
        } else {
            hcWarning(
                'ProveedorController no muestra auditoria minima completa Fase 2F.',
                'Registrar altas, cambios, desactivaciones y reactivaciones en logs_auditoria si la tabla existe.'
            );
        }

        if (
            preg_match('/function\s+before\s*\([^)]*\).*?require_hotel_module\s*\(\s*[\'"]inventario[\'"]\s*\)/s', $providerControllerCode)
        ) {
            hcOk('ProveedorController requiere modulo inventario para acceso directo Fase 2H.');
        } else {
            hcError(
                'ProveedorController no valida modulo inventario.',
                'Proteger /proveedores con require_hotel_module("inventario") para alinear acceso directo con el sidebar.'
            );
        }

        $providerIndexViewPath = $appRoot . '/app/views/proveedores/index.php';
        if (is_file($providerIndexViewPath) && $providerDetailViewFile && is_file($providerDetailViewFile)) {
            $providerIndexViewCode = (string) file_get_contents($providerIndexViewPath);
            $providerDetailViewCode = (string) file_get_contents($providerDetailViewFile);
            if (
                strpos($providerIndexViewCode, "url('proveedores/' . (int)\$proveedor['id'])") !== false
                && strpos($providerDetailViewCode, 'Compras recientes') !== false
                && strpos($providerDetailViewCode, 'Solo lectura') !== false
                && strpos($providerDetailViewCode, "url('compras/' . (int)") !== false
                && strpos($providerDetailViewCode, "url('compras/reportes/recibidas?proveedor_id='") !== false
                && strpos($providerDetailViewCode, '<form') === false
                && strpos($providerDetailViewCode, 'csrf_field()') === false
            ) {
                hcOk('Vistas de Proveedores Fase 3A enlazan ficha read-only e historial sin formularios nuevos.');
            } else {
                hcWarning(
                    'Vistas de Proveedores Fase 3A incompletas.',
                    'Asegurar enlace GET /proveedores/{id}, vista read-only sin forms y links a compras/reporte.'
                );
            }
        } else {
            hcWarning(
                'No se encontro vista de detalle de Proveedores Fase 3A.',
                'Crear app/views/proveedores/ver.php para la ficha read-only.'
            );
        }
    } else {
        hcError(
            'Faltan archivos de Proveedor Fase 2F.',
            'Crear src/app/models/Proveedor.php y src/app/controllers/ProveedorController.php para las rutas registradas.'
        );
    }

    if ($purchaseControllerFile && is_file($purchaseControllerFile)) {
        $purchaseControllerCode = (string) file_get_contents($purchaseControllerFile);
        $forbiddenPurchaseControllerTokens = [
            'function pagarAction',
            'function cancelarAction',
            'movimientos_caja',
            'cuentas_por_pagar',
            'compra_pagos',
            'documentos_proveedor',
        ];
        $controllerForbidden = [];
        foreach ($forbiddenPurchaseControllerTokens as $token) {
            if (strpos($purchaseControllerCode, $token) !== false) {
                $controllerForbidden[] = $token;
            }
        }

        if (
            strpos($purchaseControllerCode, 'class CompraController') !== false
            && strpos($purchaseControllerCode, 'function indexAction') !== false
            && strpos($purchaseControllerCode, 'function crearAction') !== false
            && strpos($purchaseControllerCode, 'function verAction') !== false
            && strpos($purchaseControllerCode, 'function reporteRecibidasAction') !== false
            && strpos($purchaseControllerCode, 'function guardarAction') !== false
            && strpos($purchaseControllerCode, 'function recibirAction') !== false
            && strpos($purchaseControllerCode, "require_hotel_module('inventario')") !== false
            && strpos($purchaseControllerCode, 'compras/ver') !== false
            && strpos($purchaseControllerCode, 'compras/reporte_recibidas') !== false
            && strpos($purchaseControllerCode, 'obtenerCompra') !== false
            && strpos($purchaseControllerCode, 'reporteRecibidas') !== false
            && strpos($purchaseControllerCode, 'catalogosReporteRecibidas') !== false
            && strpos($purchaseControllerCode, 'crearBorrador') !== false
            && strpos($purchaseControllerCode, 'recibirCompra') !== false
            && strpos($purchaseControllerCode, 'validateCSRF') !== false
            && empty($controllerForbidden)
        ) {
            hcOk('CompraController Fase 2Y expone listado, detalle, reporte read-only, borrador y recepcion minima bajo modulo inventario.');
        } else {
            hcError(
                'CompraController Fase 2Y no cumple el alcance minimo o contiene tokens prohibidos: ' . (empty($controllerForbidden) ? 'sin detalle' : implode(', ', $controllerForbidden)),
                'Mantener solo indexAction, crearAction, verAction, reporteRecibidasAction, guardarAction y recibirAction; sin pagos, CxP, documentos ni caja.'
            );
        }
    } else {
        hcError(
            'No existe CompraController Fase 2R.',
            'Crear controlador minimo para borradores o retirar rutas /compras.'
        );
    }

    if ($purchaseIndexViewFile && is_file($purchaseIndexViewFile) && $purchaseFormViewFile && is_file($purchaseFormViewFile) && $purchaseDetailViewFile && is_file($purchaseDetailViewFile) && $purchaseReportViewFile && is_file($purchaseReportViewFile)) {
        $purchaseIndexViewCode = (string) file_get_contents($purchaseIndexViewFile);
        $purchaseFormViewCode = (string) file_get_contents($purchaseFormViewFile);
        $purchaseDetailViewCode = (string) file_get_contents($purchaseDetailViewFile);
        $purchaseReportViewCode = (string) file_get_contents($purchaseReportViewFile);
        $purchaseViewsCode = $purchaseIndexViewCode . "\n" . $purchaseFormViewCode . "\n" . $purchaseDetailViewCode . "\n" . $purchaseReportViewCode;

        if (
            strpos($purchaseIndexViewCode, "url('compras/crear')") !== false
            && strpos($purchaseIndexViewCode, "url('compras/reportes/recibidas')") !== false
            && strpos($purchaseIndexViewCode, "url('compras/' . (int)") !== false
            && strpos($purchaseFormViewCode, "action=\"<?= url('compras') ?>\"") !== false
            && strpos($purchaseFormViewCode, 'csrf_field()') !== false
            && strpos($purchaseFormViewCode, 'name="producto_id[]"') !== false
            && strpos($purchaseFormViewCode, 'name="cantidad[]"') !== false
            && strpos($purchaseFormViewCode, 'name="costo_unitario[]"') !== false
            && strpos($purchaseDetailViewCode, 'movimiento_inventario_id') !== false
            && strpos($purchaseDetailViewCode, 'movimiento_stock_posterior') !== false
            && strpos($purchaseDetailViewCode, "url('compras?estado=") !== false
            && strpos($purchaseDetailViewCode, "url('compras/reportes/recibidas')") !== false
            && strpos($purchaseReportViewCode, "action=\"<?= url('compras/reportes/recibidas') ?>\"") !== false
            && strpos($purchaseReportViewCode, 'por_proveedor') !== false
            && strpos($purchaseReportViewCode, 'por_producto') !== false
            && strpos($purchaseReportViewCode, "url('compras/' . (int)") !== false
            && strpos($purchaseViewsCode, '/recibir') !== false
            && strpos($purchaseViewsCode, 'purchase-btn-receive') !== false
            && strpos($purchaseViewsCode, "url('compras/pagar") === false
            && strpos($purchaseViewsCode, 'cuentas-por-pagar') === false
        ) {
            hcOk('Vistas de Compras Fase 2Y permiten reporte read-only, detalle, borrador y recepcion minima con CSRF.');
        } else {
            hcError(
                'Vistas de Compras Fase 2Y incompletas o con enlaces fuera de alcance.',
                'Revisar reporte read-only, detalle, CSRF, action POST /compras, recepcion minima y ausencia de pagos/CxP.'
            );
        }
    } else {
        hcError(
            'Faltan vistas de Compras Fase 2Y.',
            'Crear app/views/compras/index.php, app/views/compras/form.php, app/views/compras/ver.php y app/views/compras/reporte_recibidas.php o retirar rutas /compras.'
        );
    }

    if ($cxpControllerFile && is_file($cxpControllerFile) && $cxpModelFile && is_file($cxpModelFile)) {
        $cxpControllerCode = (string) file_get_contents($cxpControllerFile);
        $cxpModelCode = (string) file_get_contents($cxpModelFile);
        $cxpCode = $cxpControllerCode . "\n" . $cxpModelCode;
        $cxpForbidden = preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(cuentas_por_pagar_movimientos|movimientos_caja|cortes_caja|cajas)\b/i', $cxpCode)
            || strpos($cxpCode, 'function pagarAction') !== false
            || strpos($cxpCode, 'function abonarAction') !== false
            || strpos($cxpCode, 'PAGO') !== false
            || strpos($cxpCode, 'ABONO') !== false
            || strpos($cxpCode, 'movimientos_caja') !== false;

        if (
            !$cxpForbidden
            && strpos($cxpControllerCode, 'class CuentaPorPagarController') !== false
            && strpos($cxpControllerCode, 'function indexAction') !== false
            && strpos($cxpControllerCode, 'function generacionPreviewAction') !== false
            && strpos($cxpControllerCode, 'function generarDesdeCompraAction') !== false
            && strpos($cxpControllerCode, 'validateCSRF') !== false
            && strpos($cxpControllerCode, 'function verAction') !== false
            && strpos($cxpControllerCode, "require_hotel_module('inventario')") !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/index') !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/generacion_preview') !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/ver') !== false
            && strpos($cxpModelCode, 'class CuentaPorPagar') !== false
            && strpos($cxpModelCode, "protected \$table = 'cuentas_por_pagar'") !== false
            && strpos($cxpModelCode, 'function resumenPorHotel') !== false
            && strpos($cxpModelCode, 'function listarPorHotel') !== false
            && strpos($cxpModelCode, 'function previewGeneracionDesdeCompras') !== false
            && strpos($cxpModelCode, 'function generarDesdeCompraRecibida') !== false
            && strpos($cxpModelCode, 'FOR UPDATE') !== false
            && strpos($cxpModelCode, 'INSERT INTO cuentas_por_pagar') !== false
            && strpos($cxpModelCode, 'AuditService::record') !== false
            && strpos($cxpModelCode, 'function buscarPorIdHotel') !== false
            && strpos($cxpModelCode, 'function movimientosPorCuenta') !== false
            && strpos($cxpModelCode, 'FROM cuentas_por_pagar') !== false
            && strpos($cxpModelCode, 'FROM cuentas_por_pagar_movimientos') !== false
        ) {
            hcOk('CxP Fase 3B/3C-C expone lectura, preview y generacion manual auditada sin pagos ni Caja.');
        } else {
            hcError(
                'CxP Fase 3C-C no cumple contrato o contiene tokens prohibidos.',
                'Mantener GET index/preview/ver y POST generar desde compra recibida; sin pagos, abonos ni movimientos_caja.'
            );
        }
    } else {
        hcWarning(
            'Faltan modelo/controlador de CxP Fase 3B.',
            'Crear CuentaPorPagar y CuentaPorPagarController solo para vistas read-only.'
        );
    }

    if (
        $cxpIndexViewFile && is_file($cxpIndexViewFile)
        && $cxpDetailViewFile && is_file($cxpDetailViewFile)
        && $cxpPreviewViewFile && is_file($cxpPreviewViewFile)
    ) {
        $cxpIndexViewCode = (string) file_get_contents($cxpIndexViewFile);
        $cxpDetailViewCode = (string) file_get_contents($cxpDetailViewFile);
        $cxpPreviewViewCode = (string) file_get_contents($cxpPreviewViewFile);
        $cxpViewsCode = $cxpIndexViewCode . "\n" . $cxpDetailViewCode . "\n" . $cxpPreviewViewCode;
        if (
            strpos($cxpIndexViewCode, "action=\"<?= url('cuentas-por-pagar') ?>\"") !== false
            && strpos($cxpIndexViewCode, "url('cuentas-por-pagar/generacion-preview')") !== false
            && strpos($cxpIndexViewCode, "url('cuentas-por-pagar/' . (int)") !== false
            && strpos($cxpDetailViewCode, "url('cuentas-por-pagar')") !== false
            && strpos($cxpDetailViewCode, "url('compras/' . (int)") !== false
            && strpos($cxpDetailViewCode, "url('proveedores/' . (int)") !== false
            && strpos($cxpPreviewViewCode, "action=\"<?= url('cuentas-por-pagar/generacion-preview') ?>\"") !== false
            && strpos($cxpPreviewViewCode, "action=\"<?= url('cuentas-por-pagar/generar-desde-compra/' . (int)") !== false
            && strpos($cxpPreviewViewCode, 'csrf_field()') !== false
            && strpos($cxpPreviewViewCode, "url('compras/' . (int)") !== false
            && strpos($cxpPreviewViewCode, "url('proveedores/' . (int)") !== false
            && strpos($cxpViewsCode, 'Solo lectura') !== false
            && strpos($cxpViewsCode, 'Sin pagos') !== false
            && strpos($cxpIndexViewCode . "\n" . $cxpDetailViewCode, 'csrf_field()') === false
            && strpos($cxpViewsCode, 'movimientos_caja') === false
        ) {
            hcOk('Vistas CxP Fase 3B/3C-C incluyen lectura, preview y POST manual con CSRF sin pagos ni Caja.');
        } else {
            hcError(
                'Vistas CxP Fase 3C-C incompletas o con acciones fuera de alcance.',
                'Mantener listado/detalle read-only y solo POST manual desde preview con CSRF; sin enlaces de pago/caja.'
            );
        }
    } else {
        hcWarning(
            'Faltan vistas CxP Fase 3B/3C-A.',
            'Crear app/views/cuentas_por_pagar/index.php, generacion_preview.php y ver.php en modo solo lectura.'
        );
    }

    if (is_file($sidebarPath)) {
        $sidebarCode = (string) file_get_contents($sidebarPath);
        if (
            strpos($sidebarCode, '$mostrarProveedores = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('proveedores')") !== false
            && strpos($sidebarCode, 'Proveedores') !== false
        ) {
            hcOk('Sidebar expone Proveedores bajo el gate visual de Inventario en Fase 2G.');
        } else {
            hcWarning(
                'Sidebar no expone Proveedores bajo Inventario.',
                'Si el catalogo ya fue validado manualmente, enlazar /proveedores solo cuando inventario este visible.'
            );
        }

        if (
            strpos($sidebarCode, '$mostrarCompras = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('compras')") !== false
            && strpos($sidebarCode, 'Compras') !== false
        ) {
            hcOk('Sidebar expone Compras Fase 2R bajo el gate visual de Inventario.');
        } else {
            hcError(
                'Sidebar no expone Compras Fase 2R bajo el gate de Inventario.',
                'Enlazar /compras solo mediante $mostrarCompras = $mostrarInventario.'
            );
        }

        if (
            strpos($sidebarCode, '$mostrarCuentasPorPagar = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('cuentas-por-pagar')") !== false
            && strpos($sidebarCode, 'Cuentas por pagar') !== false
        ) {
            hcOk('Sidebar expone CxP read-only Fase 3B bajo el gate visual de Inventario.');
        } else {
            hcError(
                'Sidebar no expone CxP read-only bajo el gate esperado.',
                'Enlazar /cuentas-por-pagar solo mediante $mostrarCuentasPorPagar = $mostrarInventario.'
            );
        }
    } else {
        hcWarning(
            'No se encontro sidebar.php para validar navegacion Fase 2G.',
            'Validar manualmente que Proveedores no se exponga como modulo independiente sin decision SaaS.'
        );
    }

    $auditServicePath = $appRoot . '/app/services/AuditService.php';
    $authControllerPath = $controllersDir . '/AuthController.php';
    if (is_file($auditServicePath)) {
        hcOk('AuditService existe para escritura gradual en logs_auditoria.');
    } else {
        hcError('AuditService no existe.', 'Crear una base reusable de auditoria antes de integrar acciones sensibles.');
    }

    if (is_file($authControllerPath)) {
        $authCode = (string) file_get_contents($authControllerPath);
        if (
            strpos($authCode, 'AuditService::loginSuccess') !== false
            && strpos($authCode, 'AuditService::loginFailed') !== false
            && strpos($authCode, 'AuditService::logout') !== false
        ) {
            hcOk('AuthController integra auditoria minima de login/logout.');
        } else {
            hcWarning(
                'AuthController no muestra integracion completa con AuditService para login/logout.',
                'Integrar gradualmente sin cambiar la logica de autenticacion.'
            );
        }
    }

    $habitacionView = $appRoot . '/app/views/habitaciones/ver.php';
    if (is_file($habitacionView) && strpos((string) file_get_contents($habitacionView), 'habitaciones/limpieza/') !== false) {
        hcError('habitaciones/ver.php sigue apuntando a habitaciones/limpieza/{id}.', 'Cambiar el form al endpoint seguro habitaciones/{id}/liberar.');
    } elseif (is_file($habitacionView) && hcRoutePatternExists($routes, 'habitaciones/1/liberar', 'post')) {
        hcOk('habitaciones/ver.php no usa habitaciones/limpieza/{id} y existe POST habitaciones/{id}/liberar.');
    }

    $backupView = $appRoot . '/app/views/configuracion/backup.php';
    if (is_file($backupView) && strpos((string) file_get_contents($backupView), 'configuracion/backup/descargar') !== false) {
        if (hcRouteExists($routes, 'configuracion/backup/descargar', 'get')) {
            hcOk('configuracion/backup.php apunta a una ruta de descarga registrada.');
        } else {
            hcError('configuracion/backup.php apunta a configuracion/backup/descargar sin ruta GET.', 'Registrar Configuracion::descargarBackup.');
        }
    }

    $inventarioAjusteView = $appRoot . '/app/views/inventario/ajuste.php';
    if (is_file($inventarioAjusteView) && strpos((string) file_get_contents($inventarioAjusteView), 'inventario/ajuste/') !== false) {
        $ajusteGet = hcRoutePatternExists($routes, 'inventario/ajuste/1', 'get');
        $ajustePost = hcRoutePatternExists($routes, 'inventario/ajuste/1', 'post');
        if (!$ajusteGet && !$ajustePost) {
            hcWarning(
                'inventario/ajuste/{id} queda aislado como vista legacy no registrada.',
                $inventoryDoc && is_file($inventoryDoc)
                    ? 'Mantener sin ruta hasta Fase 2B; entrada/salida actuales cubren movimientos manuales seguros.'
                    : 'Documentar decision antes de activar ajuste de stock por id.'
            );
        } elseif ($ajusteGet && $ajustePost) {
            hcOk('inventario/ajuste/{id} tiene rutas GET y POST registradas.');

            $ajusteViewCode = (string) file_get_contents($inventarioAjusteView);
            $ajusteActionCode = hcExtractMethodCode($inventarioControllerCode, 'ajusteAction');
            $procesarAjusteCode = hcExtractMethodCode($inventarioControllerCode, 'procesarAjusteAction');
            $movimientoModelPath = $appRoot . '/app/models/MovimientoInventario.php';
            $movimientoModelCode = is_file($movimientoModelPath) ? (string) file_get_contents($movimientoModelPath) : '';
            $inventarioDocCode = $inventoryDoc && is_file($inventoryDoc) ? (string) file_get_contents($inventoryDoc) : '';

            if ($ajusteActionCode !== '' && $procesarAjusteCode !== '') {
                hcOk('InventarioController expone ajusteAction y procesarAjusteAction.');
            } else {
                hcError(
                    'inventario/ajuste/{id} esta registrado pero faltan metodos de controller.',
                    'No exponer la ruta hasta que ambos metodos existan.'
                );
            }

            if (
                strpos($ajusteViewCode, '$producto') !== false
                && strpos($ajusteViewCode, '$productos') === false
                && strpos($ajusteActionCode, "'producto' => \$producto") !== false
            ) {
                hcOk('Vista de ajuste usa contrato singular $producto alineado con el controller.');
            } else {
                hcError(
                    'Vista/controller de ajuste no estan alineados en $producto.',
                    'El GET debe entregar producto singular y la vista no debe esperar $productos.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'getByIdWithCategory') !== false
                && strpos($procesarAjusteCode, 'actualizarProductoBase') !== false
            ) {
                hcOk('Ajuste valida producto y actualiza stock mediante el modelo moderno Inventario.');
            } else {
                hcError(
                    'Ajuste no muestra validacion/actualizacion por modelo moderno.',
                    'Usar Inventario::getByIdWithCategory y actualizarProductoBase para respetar hotel_id.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'crearMovimientoManual') !== false
                && strpos($movimientoModelCode, "protected \$table = 'movimientos_inventario'") !== false
                && strpos($movimientoModelCode, 'productoPerteneceAlHotel') !== false
            ) {
                hcOk('Ajuste registra movimiento en movimientos_inventario con validacion de hotel.');
            } else {
                hcError(
                    'Ajuste puede modificar stock sin movimiento moderno validado.',
                    'Registrar siempre en MovimientoInventario::crearMovimientoManual antes de confirmar.'
                );
            }

            if (strpos($procesarAjusteCode, 'AuditService::record') !== false && strpos($procesarAjusteCode, 'inventario.ajuste_stock') !== false) {
                hcOk('Ajuste registra auditoria minima inventario.ajuste_stock.');
            } else {
                hcError(
                    'Ajuste no registra auditoria minima.',
                    'Llamar AuditService::record con stock anterior/nuevo y motivo.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'FROM productos') === false
                && strpos($procesarAjusteCode, 'JOIN productos') === false
                && strpos($procesarAjusteCode, 'inventario_movimientos') === false
            ) {
                hcOk('Ajuste moderno no usa tablas legacy productos ni inventario_movimientos.');
            } else {
                hcError(
                    'Ajuste moderno contiene referencias a tablas legacy.',
                    'No usar productos ni inventario_movimientos en Fase 2B.'
                );
            }

            if ($inventoryDoc === null || !is_file($inventoryDoc)) {
                hcWarning(
                    'No se pudo validar documentacion de inventario porque docs/technical no esta montado.',
                    'Ejecutar el checker desde la raiz del proyecto para validar docs/technical/inventory_reconciliation.md.'
                );
            } elseif (strpos($inventarioDocCode, 'Fase 2B') !== false && strpos($inventarioDocCode, 'inventario.ajuste_stock') !== false) {
                hcOk('Documentacion de inventario registra contrato Fase 2B.');
            } else {
                hcWarning(
                    'Documentacion de inventario no registra aun Fase 2B.',
                    'Actualizar docs/technical/inventory_reconciliation.md con el contrato final del ajuste.'
                );
            }

            if ($inventoryDoc && is_file($inventoryDoc)) {
                if (
                    strpos($inventarioDocCode, 'Fase 2C') !== false
                    && strpos($inventarioDocCode, 'legacy congelado') !== false
                    && strpos($inventarioDocCode, 'inventario_config_habitacion') !== false
                    && strpos($inventarioDocCode, 'inventario_productos') !== false
                ) {
                    hcOk('Documentacion de inventario registra contrato Fase 2C.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2C.',
                        'Actualizar docs/technical/inventory_reconciliation.md con congelamiento legacy y contrato moderno.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2D') !== false
                    && strpos($inventarioDocCode, 'rollback') !== false
                    && strpos($inventarioDocCode, 'getProductosDescuentoReservacion') !== false
                    && strpos($inventarioDocCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2D.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2D.',
                        'Actualizar docs/technical/inventory_reconciliation.md con prueba rollback, preview y guardas legacy.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2E') !== false
                    && strpos($inventarioDocCode, 'inventario.configuracion_actualizada') !== false
                    && strpos($inventarioDocCode, 'purchasing_inventory_contract.md') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2E.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2E.',
                        'Actualizar docs/technical/inventory_reconciliation.md con auditoria de configuracion y contrato de compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2F') !== false
                    && strpos($inventarioDocCode, 'proveedores') !== false
                    && strpos($inventarioDocCode, 'sin compras') !== false
                ) {
                    hcOk('Documentacion de inventario registra separacion Fase 2F de proveedores vs compras.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2F.',
                        'Actualizar docs/technical/inventory_reconciliation.md con alcance de proveedores sin compras ni caja.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2G') !== false
                    && strpos($inventarioDocCode, 'sidebar') !== false
                    && strpos($inventarioDocCode, 'gate visual de `inventario`') !== false
                ) {
                    hcOk('Documentacion de inventario registra exposicion controlada Fase 2G.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2G.',
                        'Actualizar docs/technical/inventory_reconciliation.md con enlace de sidebar bajo Inventario y sin compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2H') !== false
                    && strpos($inventarioDocCode, "require_hotel_module('inventario')") !== false
                    && strpos($inventarioDocCode, 'No se crea modulo `proveedores`') !== false
                ) {
                    hcOk('Documentacion de inventario registra gate Fase 2H de proveedores.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2H.',
                        'Actualizar docs/technical/inventory_reconciliation.md con gate de modulo inventario para Proveedores.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2I') !== false
                    && strpos($inventarioDocCode, 'RFC no debe repetirse') !== false
                    && strpos($inventarioDocCode, 'No se agrega indice unico') !== false
                ) {
                    hcOk('Documentacion de inventario registra validacion Fase 2I de duplicados.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2I.',
                        'Actualizar docs/technical/inventory_reconciliation.md con validacion de duplicados sin migracion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2J') !== false
                    && strpos($inventarioDocCode, 'uk_proveedores_hotel_rfc') !== false
                    && strpos($inventarioDocCode, 'nombre_activo_key') !== false
                ) {
                    hcOk('Documentacion de inventario registra constraints SQL Fase 2J.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2J.',
                        'Actualizar docs/technical/inventory_reconciliation.md con indices unicos SQL de proveedores.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2K') !== false
                    && strpos($inventarioDocCode, 'reconciliar_proveedores_dry_run.php') !== false
                    && strpos($inventarioDocCode, 'No inserta proveedores') !== false
                ) {
                    hcOk('Documentacion de inventario registra dry-run Fase 2K de proveedores historicos.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2K.',
                        'Actualizar docs/technical/inventory_reconciliation.md con dry-run de proveedores historicos sin importacion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2L') !== false
                    && strpos($inventarioDocCode, 'importar_proveedores_los_cedros.php') !== false
                    && strpos($inventarioDocCode, '--backup-file') !== false
                ) {
                    hcOk('Documentacion de inventario registra importador revisable Fase 2L.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2L.',
                        'Actualizar docs/technical/inventory_reconciliation.md con importador revisable y backup obligatorio.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2M') !== false
                    && strpos($inventarioDocCode, 'preflight_compras_minimas.php') !== false
                    && strpos($inventarioDocCode, 'No crea tablas `compras`') !== false
                ) {
                    hcOk('Documentacion de inventario registra preflight Fase 2M de compras minimas.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2M.',
                        'Actualizar docs/technical/inventory_reconciliation.md con preflight de compras minimas sin rutas ni tablas nuevas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2N') !== false
                    && strpos($inventarioDocCode, '20260615_002_fase_2n_compras_minimas_draft.sql') !== false
                    && strpos($inventarioDocCode, 'No se ejecuta contra ninguna base') !== false
                ) {
                    hcOk('Documentacion de inventario registra borrador SQL Fase 2N.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2N.',
                        'Actualizar docs/technical/inventory_reconciliation.md con borrador SQL no ejecutado de compras minimas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2O') !== false
                    && strpos($inventarioDocCode, '20260615_002_fase_2n_compras_minimas.sql') !== false
                    && strpos($inventarioDocCode, 'No se genero ningun `movimientos_inventario`') !== false
                ) {
                    hcOk('Documentacion de inventario registra migracion aplicada Fase 2O.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2O.',
                        'Actualizar docs/technical/inventory_reconciliation.md con migracion aplicada, tablas vacias y sin recepcion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2P') !== false
                    && strpos($inventarioDocCode, 'CompraService.php') !== false
                    && strpos($inventarioDocCode, 'Sin rutas') !== false
                    && strpos($inventarioDocCode, 'No se genera ningun movimiento de inventario') !== false
                ) {
                    hcOk('Documentacion de inventario registra servicio interno Fase 2P.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2P.',
                        'Actualizar docs/technical/inventory_reconciliation.md con servicio interno sin rutas ni movimientos ejecutados.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Q') !== false
                    && strpos($inventarioDocCode, 'probar_compra_service.php') !== false
                    && strpos($inventarioDocCode, 'ROLLBACK_TEST') !== false
                    && strpos($inventarioDocCode, 'rollback obligatorio') !== false
                ) {
                    hcOk('Documentacion de inventario registra prueba CLI Fase 2Q.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Q.',
                        'Actualizar docs/technical/inventory_reconciliation.md con prueba CLI, rollback y restauracion de stock/conteos.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2R') !== false
                    && strpos($inventarioDocCode, 'CompraController.php') !== false
                    && strpos($inventarioDocCode, 'POST /compras') !== false
                    && strpos($inventarioDocCode, 'No modifica stock') !== false
                ) {
                    hcOk('Documentacion de inventario registra UI minima Fase 2R.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2R.',
                        'Actualizar docs/technical/inventory_reconciliation.md con UI de borradores sin recepcion ni stock.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2S') !== false
                    && strpos($inventarioDocCode, 'compras_no_borrador = 0') !== false
                    && strpos($inventarioDocCode, 'detalles_con_movimiento = 0') !== false
                    && strpos($inventarioDocCode, 'movimientos_recepcion_compra = 0') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2S.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2S.',
                        'Actualizar docs/technical/inventory_reconciliation.md con cierre de borrador real sin stock ni movimientos.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2T') !== false
                    && strpos($inventarioDocCode, 'preflight_recepcion_compras.php') !== false
                    && strpos($inventarioDocCode, 'productos repetidos') !== false
                    && strpos($inventarioDocCode, 'sin recibir compras') !== false
                ) {
                    hcOk('Documentacion de inventario registra preflight Fase 2T.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2T.',
                        'Actualizar docs/technical/inventory_reconciliation.md con preflight de recepcion sin recibir compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2U') !== false
                    && strpos($inventarioDocCode, '--duplicate-line') !== false
                    && strpos($inventarioDocCode, 'un movimiento por linea') !== false
                    && strpos($inventarioDocCode, 'rollback') !== false
                ) {
                    hcOk('Documentacion de inventario registra regla Fase 2U para productos repetidos.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2U.',
                        'Actualizar docs/technical/inventory_reconciliation.md con regla de productos repetidos y prueba con rollback.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2V') !== false
                    && strpos($inventarioDocCode, 'POST /compras/{id}/recibir') !== false
                    && strpos($inventarioDocCode, 'CompraController::recibirAction()') !== false
                    && strpos($inventarioDocCode, 'CompraService::recibirCompra()') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra recepcion minima Fase 2V.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2V.',
                        'Actualizar docs/technical/inventory_reconciliation.md con recepcion minima sin caja, pagos ni CxP.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2W') !== false
                    && strpos($inventarioDocCode, 'compra #2') !== false
                    && strpos($inventarioDocCode, 'movimiento_inventario_id') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra recepcion real controlada Fase 2W.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2W.',
                        'Actualizar docs/technical/inventory_reconciliation.md con compra #2 recibida, movimientos vinculados y exclusiones.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2X') !== false
                    && strpos($inventarioDocCode, 'GET /compras/{id}') !== false
                    && strpos($inventarioDocCode, 'CompraController::verAction()') !== false
                    && strpos($inventarioDocCode, 'movimiento_inventario_id') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra detalle read-only Fase 2X.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2X.',
                        'Actualizar docs/technical/inventory_reconciliation.md con detalle read-only de compra y movimientos vinculados.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Y') !== false
                    && strpos($inventarioDocCode, 'GET /compras/reportes/recibidas') !== false
                    && strpos($inventarioDocCode, 'CompraController::reporteRecibidasAction()') !== false
                    && strpos($inventarioDocCode, 'CompraService::reporteRecibidas()') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra reportes read-only Fase 2Y.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Y.',
                        'Actualizar docs/technical/inventory_reconciliation.md con reporte read-only de compras recibidas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Z') !== false
                    && strpos($inventarioDocCode, 'assertCompraPuedeRecibirse()') !== false
                    && strpos($inventarioDocCode, 'assertDetallesPuedenRecibirse()') !== false
                    && strpos($inventarioDocCode, 'un movimiento por linea') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra endurecimiento Fase 2Z.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Z.',
                        'Actualizar docs/technical/inventory_reconciliation.md con guardas de recepcion y navegacion read-only.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3A') !== false
                    && strpos($inventarioDocCode, 'ProveedorController::verAction()') !== false
                    && strpos($inventarioDocCode, 'Proveedor::resumenComprasPorProveedor()') !== false
                    && strpos($inventarioDocCode, 'Proveedor::comprasRecientesPorProveedor()') !== false
                    && strpos($inventarioDocCode, 'GET /proveedores/{id}') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra proveedores v2 read-only Fase 3A.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 3A.',
                        'Actualizar docs/technical/inventory_reconciliation.md con ficha read-only de proveedor e historial.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3B draft') !== false
                    && strpos($inventarioDocCode, '20260615_003_fase_3b_cxp_base_draft.sql') !== false
                    && strpos($inventarioDocCode, 'No se ejecuta SQL contra la base') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                    && strpos($inventarioDocCode, 'Sin rutas activas de CxP') !== false
                ) {
                    hcOk('Documentacion de inventario registra draft no ejecutado Fase 3B de CxP.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun draft Fase 3B.',
                        'Actualizar docs/technical/inventory_reconciliation.md con CxP base no ejecutada y exclusiones.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3B aplicada') !== false
                    && strpos($inventarioDocCode, '20260615_003_fase_3b_cxp_base.sql') !== false
                    && strpos($inventarioDocCode, 'CuentaPorPagarController') !== false
                    && strpos($inventarioDocCode, 'GET /cuentas-por-pagar') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                    && strpos($inventarioDocCode, 'Sin formularios POST') !== false
                ) {
                    hcOk('Documentacion de inventario registra CxP read-only Fase 3B aplicada.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 3B aplicada.',
                        'Actualizar docs/technical/inventory_reconciliation.md con migracion, rutas read-only y exclusiones.'
                    );
                }
            }

            if ($purchasingInventoryDoc === null || !is_file($purchasingInventoryDoc)) {
                hcWarning(
                    'No se pudo validar contrato de compras porque docs/technical no esta montado o falta el documento.',
                    'Crear docs/technical/purchasing_inventory_contract.md antes de implementar Proveedores/Compras.'
                );
            } else {
                $purchasingDocCode = (string) file_get_contents($purchasingInventoryDoc);
                if (
                    strpos($purchasingDocCode, 'Contrato de recepcion de compras') !== false
                    && strpos($purchasingDocCode, 'inventario_productos') !== false
                    && strpos($purchasingDocCode, 'movimientos_inventario') !== false
                    && strpos($purchasingDocCode, 'ENTRADA') !== false
                    && strpos($purchasingDocCode, 'No implementar todavia') !== false
                ) {
                    hcOk('Contrato Fase 2E de Compras/Inventario documentado.');
                } else {
                    hcWarning(
                        'Contrato de compras hacia inventario esta incompleto.',
                        'Documentar recepcion, tablas futuras, estados, relacion con caja/CxP/documentos y auditoria.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2F') !== false
                    && strpos($purchasingDocCode, '20260614_004_fase_2f_catalogo_proveedores.sql') !== false
                    && strpos($purchasingDocCode, 'Sin compras') !== false
                ) {
                    hcOk('Contrato de compras documenta implementacion minima Fase 2F de proveedores.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra implementacion Fase 2F.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con tabla proveedores, auditoria y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2G') !== false
                    && strpos($purchasingDocCode, 'sidebar') !== false
                    && strpos($purchasingDocCode, 'Sin compras') !== false
                ) {
                    hcOk('Contrato de compras documenta exposicion minima Fase 2G.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra exposicion Fase 2G.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con enlace de Proveedores y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2H') !== false
                    && strpos($purchasingDocCode, "require_hotel_module('inventario')") !== false
                    && strpos($purchasingDocCode, 'Sin modulo nuevo `proveedores`') !== false
                ) {
                    hcOk('Contrato de compras documenta gate minimo Fase 2H.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra gate Fase 2H.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con proteccion de Proveedores por modulo inventario.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2I') !== false
                    && strpos($purchasingDocCode, 'RFC repetido por hotel') !== false
                    && strpos($purchasingDocCode, 'Sin indice unico nuevo') !== false
                ) {
                    hcOk('Contrato de compras documenta validacion minima Fase 2I.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra validacion Fase 2I.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con reglas de duplicados en Proveedores.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2J') !== false
                    && strpos($purchasingDocCode, '20260615_001_fase_2j_unique_proveedores.sql') !== false
                    && strpos($purchasingDocCode, 'uk_proveedores_hotel_nombre_activo') !== false
                ) {
                    hcOk('Contrato de compras documenta constraints SQL Fase 2J.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra constraints Fase 2J.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con migracion e indices unicos de Proveedores.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2K') !== false
                    && strpos($purchasingDocCode, 'reconciliar_proveedores_dry_run.php') !== false
                    && strpos($purchasingDocCode, 'Sin inserts en `proveedores`') !== false
                ) {
                    hcOk('Contrato de compras documenta dry-run Fase 2K de proveedores.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra dry-run Fase 2K.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con preparacion de reconciliacion sin inserts.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2L') !== false
                    && strpos($purchasingDocCode, 'importar_proveedores_los_cedros.php') !== false
                    && strpos($purchasingDocCode, 'Sin actualizacion de `movimientos_caja`') !== false
                ) {
                    hcOk('Contrato de compras documenta importador revisable Fase 2L.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra importador Fase 2L.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con importador revisable sin Caja ni CxP.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2M') !== false
                    && strpos($purchasingDocCode, 'preflight_compras_minimas.php') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                    && strpos($purchasingDocCode, 'Sin cuentas por pagar') !== false
                ) {
                    hcOk('Contrato de compras documenta preflight Fase 2M.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra preflight Fase 2M.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con preparacion de compras minimas sin Caja, CxP ni documentos.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2N') !== false
                    && strpos($purchasingDocCode, '20260615_002_fase_2n_compras_minimas_draft.sql') !== false
                    && strpos($purchasingDocCode, 'No ejecutar desde `docs/technical/sql_drafts`') !== false
                ) {
                    hcOk('Contrato de compras documenta borrador SQL Fase 2N.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra borrador SQL Fase 2N.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con migracion borrador no ejecutada y rollback futuro.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2O') !== false
                    && strpos($purchasingDocCode, 'phase2o_20260615_013321_medisoft_hoteles_import.sql') !== false
                    && strpos($purchasingDocCode, 'Sin rutas nuevas') !== false
                ) {
                    hcOk('Contrato de compras documenta migracion aplicada Fase 2O.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra migracion aplicada Fase 2O.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con backup, migracion, tablas vacias y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2P') !== false
                    && strpos($purchasingDocCode, 'CompraService.php') !== false
                    && strpos($purchasingDocCode, 'Sin rutas publicas') !== false
                ) {
                    hcOk('Contrato de compras documenta servicio interno Fase 2P.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra servicio interno Fase 2P.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con CompraService y exclusiones de rutas/UI/Caja/CxP.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Q') !== false
                    && strpos($purchasingDocCode, 'probar_compra_service.php') !== false
                    && strpos($purchasingDocCode, 'ROLLBACK_TEST') !== false
                    && strpos($purchasingDocCode, 'no contiene `commit()`') !== false
                ) {
                    hcOk('Contrato de compras documenta prueba CLI Fase 2Q.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra prueba CLI Fase 2Q.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con script, backup obligatorio y rollback test.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2R') !== false
                    && strpos($purchasingDocCode, 'CompraController.php') !== false
                    && strpos($purchasingDocCode, 'POST /compras') !== false
                    && strpos($purchasingDocCode, 'Sin recepcion') !== false
                ) {
                    hcOk('Contrato de compras documenta UI minima Fase 2R.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra UI minima Fase 2R.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con rutas/vistas de borrador y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2S') !== false
                    && strpos($purchasingDocCode, 'compras_no_borrador = 0') !== false
                    && strpos($purchasingDocCode, 'detalles_con_movimiento = 0') !== false
                    && strpos($purchasingDocCode, 'movimientos_recepcion_compra = 0') !== false
                ) {
                    hcOk('Contrato de compras documenta cierre Fase 2S.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra cierre Fase 2S.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con la validacion del borrador real.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2T') !== false
                    && strpos($purchasingDocCode, 'preflight_recepcion_compras.php') !== false
                    && strpos($purchasingDocCode, 'productos repetidos') !== false
                    && strpos($purchasingDocCode, 'Sin recepcion') !== false
                ) {
                    hcOk('Contrato de compras documenta preflight Fase 2T.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra preflight Fase 2T.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con validacion previa a recepcion.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2U') !== false
                    && strpos($purchasingDocCode, '--duplicate-line') !== false
                    && strpos($purchasingDocCode, 'un movimiento por linea') !== false
                    && strpos($purchasingDocCode, 'ROLLBACK_TEST') !== false
                ) {
                    hcOk('Contrato de compras documenta regla Fase 2U para productos repetidos.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra regla Fase 2U.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con regla de productos repetidos y prueba rollback.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2V') !== false
                    && strpos($purchasingDocCode, 'POST /compras/{id}/recibir') !== false
                    && strpos($purchasingDocCode, 'CompraController::recibirAction()') !== false
                    && strpos($purchasingDocCode, 'CompraService::recibirCompra()') !== false
                    && strpos($purchasingDocCode, 'No registra pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta recepcion minima Fase 2V.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra recepcion minima Fase 2V.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta/action/servicio y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2W') !== false
                    && strpos($purchasingDocCode, 'compra #2') !== false
                    && strpos($purchasingDocCode, 'movimiento_inventario_id') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                    && strpos($purchasingDocCode, 'Sin cuentas por pagar') !== false
                ) {
                    hcOk('Contrato de compras documenta recepcion real controlada Fase 2W.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra recepcion real controlada Fase 2W.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con compra #2, movimientos, auditoria y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2X') !== false
                    && strpos($purchasingDocCode, 'GET /compras/{id}') !== false
                    && strpos($purchasingDocCode, 'CompraController::verAction()') !== false
                    && strpos($purchasingDocCode, 'compras/ver.php') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta detalle read-only Fase 2X.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra detalle read-only Fase 2X.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta, vista, movimientos mostrados y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Y') !== false
                    && strpos($purchasingDocCode, 'GET /compras/reportes/recibidas') !== false
                    && strpos($purchasingDocCode, 'CompraController::reporteRecibidasAction()') !== false
                    && strpos($purchasingDocCode, 'CompraService::reporteRecibidas()') !== false
                    && strpos($purchasingDocCode, 'compras/reporte_recibidas.php') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta reporte read-only Fase 2Y.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra reporte read-only Fase 2Y.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta, vista, filtros, agregados y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Z') !== false
                    && strpos($purchasingDocCode, 'assertCompraPuedeRecibirse()') !== false
                    && strpos($purchasingDocCode, 'assertDetallesPuedenRecibirse()') !== false
                    && strpos($purchasingDocCode, 'recepcion doble') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta endurecimiento Fase 2Z.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra endurecimiento Fase 2Z.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con guardas de recepcion doble, estados y detalles invalidos.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3A') !== false
                    && strpos($purchasingDocCode, 'GET /proveedores/{id}') !== false
                    && strpos($purchasingDocCode, 'ProveedorController::verAction()') !== false
                    && strpos($purchasingDocCode, 'Proveedor::resumenComprasPorProveedor()') !== false
                    && strpos($purchasingDocCode, 'Proveedor::comprasRecientesPorProveedor()') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta proveedores v2 read-only Fase 3A.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra proveedores v2 Fase 3A.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ficha read-only, ruta y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3B draft') !== false
                    && strpos($purchasingDocCode, '20260615_003_fase_3b_cxp_base_draft.sql') !== false
                    && strpos($purchasingDocCode, 'No se ejecuta contra ninguna base de datos') !== false
                    && strpos($purchasingDocCode, 'uk_cxp_hotel_compra') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Contrato de compras documenta draft no ejecutado Fase 3B de CxP.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra draft Fase 3B.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con draft CxP, no ejecucion y promocion futura.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3B aplicada') !== false
                    && strpos($purchasingDocCode, 'phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql') !== false
                    && strpos($purchasingDocCode, '20260615_003_fase_3b_cxp_base.sql') !== false
                    && strpos($purchasingDocCode, 'CuentaPorPagarController') !== false
                    && strpos($purchasingDocCode, 'GET /cuentas-por-pagar') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Contrato de compras documenta CxP read-only Fase 3B aplicada.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra Fase 3B aplicada.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con backup, migracion, rutas read-only y exclusiones.'
                    );
                }
            }
        } else {
            hcError(
                'inventario/ajuste/{id} esta registrado parcialmente. GET=' . ($ajusteGet ? 'si' : 'no') . ', POST=' . ($ajustePost ? 'si' : 'no') . '.',
                'Registrar GET y POST juntos o mantener el flujo sin ruta.'
            );
        }
    }

    $documentoControllerPath = $controllersDir . '/DocumentoController.php';
    $documentoControllerCode = is_file($documentoControllerPath)
        ? (string) file_get_contents($documentoControllerPath)
        : '';

    if (hcRoutePatternExists($routes, 'documentos/1/descargar', 'get')) {
        hcOk('Ruta documental segura registrada: GET /documentos/{id}/descargar.');
    } else {
        hcWarning(
            'Ruta documental segura GET /documentos/{id}/descargar no esta registrada.',
            'Restaurar la ruta solo si Fase 4B-A sigue vigente.'
        );
    }

    if (
        $documentoControllerCode !== ''
        && strpos($documentoControllerCode, 'documentos.descargado') !== false
        && strpos($documentoControllerCode, 'documentos.descarga_bloqueada') !== false
        && strpos($documentoControllerCode, 'AuditService::record') !== false
    ) {
        hcOk('Centro Documental Fase 4B-B audita descargas exitosas y bloqueadas con AuditService.');
    } elseif ($documentoControllerCode !== '') {
        hcWarning(
            'Centro Documental no muestra auditoria completa Fase 4B-B.',
            'Registrar documentos.descargado y documentos.descarga_bloqueada con AuditService sin exponer storage_path.'
        );
    }

    $documentoModelPath = $appRoot . '/app/models/Documento.php';
    $documentoModelCode = is_file($documentoModelPath)
        ? (string) file_get_contents($documentoModelPath)
        : '';
    $documentoEditViewPath = $appRoot . '/app/views/documentos/editar.php';
    $documentoEditViewCode = is_file($documentoEditViewPath)
        ? (string) file_get_contents($documentoEditViewPath)
        : '';

    if (
        hcRoutePatternExists($routes, 'documentos/1/editar', 'get')
        && hcRoutePatternExists($routes, 'documentos/1/actualizar', 'post')
    ) {
        hcOk('Rutas Fase 4B-C de metadata documental registradas: GET editar y POST actualizar.');
    } else {
        hcWarning(
            'Rutas Fase 4B-C de metadata documental incompletas.',
            'Registrar GET /documentos/{id}/editar y POST /documentos/{id}/actualizar solo si la fase esta autorizada.'
        );
    }

    if (
        $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, 'actualizarMetadata') !== false
        && strpos($documentoModelCode, 'documentos.metadata_actualizada') !== false
        && $documentoEditViewCode !== ''
        && strpos($documentoEditViewCode, 'storage_path') === false
        && strpos($documentoEditViewCode, 'nombre_archivo') === false
    ) {
        hcOk('Centro Documental Fase 4B-C actualiza metadata con CSRF y auditoria diferencial sin editar archivo/storage.');
    } elseif ($documentoModelCode !== '') {
        hcWarning(
            'Centro Documental no muestra contrato completo Fase 4B-C.',
            'Validar metadata en modelo, usar CSRF y auditar documentos.metadata_actualizada sin exponer storage_path.'
        );
    }

    $documentoDetailViewPath = $appRoot . '/app/views/documentos/ver.php';
    $documentoDetailViewCode = is_file($documentoDetailViewPath)
        ? (string) file_get_contents($documentoDetailViewPath)
        : '';

    if (
        hcRoutePatternExists($routes, 'documentos/1/archivar', 'post')
        && hcRoutePatternExists($routes, 'documentos/1/restaurar', 'post')
        && $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && $documentoDetailViewCode !== ''
        && strpos($documentoControllerCode, 'archivarAction') !== false
        && strpos($documentoControllerCode, 'restaurarAction') !== false
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, 'actualizarEstado') !== false
        && strpos($documentoModelCode, 'documentos.estado_actualizado') !== false
        && strpos($documentoModelCode, "'activo' => ['archivado") !== false
        && strpos($documentoModelCode, "'archivado' => ['activo") !== false
        && strpos($documentoDetailViewCode, "method=\"POST\"") !== false
        && strpos($documentoDetailViewCode, "csrf_field()") !== false
        && strpos($documentoDetailViewCode, "/archivar") !== false
        && strpos($documentoDetailViewCode, "/restaurar") !== false
        && strpos($documentoModelCode, 'DELETE FROM documentos') === false
    ) {
        hcOk('Centro Documental Fase 4D-A archiva/restaura con POST, CSRF, transiciones centrales y auditoria sin borrar archivos.');
    } else {
        hcWarning(
            'Centro Documental Fase 4D-A no muestra contrato completo de archivado reversible.',
            'Validar POST /documentos/{id}/archivar y /restaurar, CSRF, Documento::actualizarEstado(), auditoria documentos.estado_actualizado y ausencia de borrado fisico.'
        );
    }

    if (
        hcRoutePatternExists($routes, 'documentos/1/eliminar', 'post')
        && $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && $documentoDetailViewCode !== ''
        && strpos($documentoControllerCode, 'eliminarAction') !== false
        && strpos($documentoControllerCode, "cambiarEstadoAction('eliminado'") !== false
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, "'activo' => ['archivado', 'eliminado']") !== false
        && strpos($documentoModelCode, "'archivado' => ['activo', 'eliminado']") !== false
        && strpos($documentoModelCode, "'activo', 'archivado', 'eliminado'") !== false
        && strpos($documentoDetailViewCode, "/eliminar") !== false
        && strpos($documentoDetailViewCode, 'Baja logica') !== false
        && strpos($documentoDetailViewCode, "csrf_field()") !== false
        && strpos($documentoModelCode, 'DELETE FROM documentos') === false
    ) {
        hcOk('Centro Documental Fase 4D-B-A baja logicamente documentos con POST, CSRF, transiciones centrales y sin borrar archivos.');
    } elseif ($documentoModelCode !== '') {
        hcWarning(
            'Centro Documental Fase 4D-B-A no muestra contrato completo de baja logica.',
            'Validar POST /documentos/{id}/eliminar, CSRF, transiciones hacia eliminado, auditoria y ausencia de DELETE/borrado fisico.'
        );
    }

    $documentosEntidadPartialPath = $appRoot . '/app/views/partials/documentos_entidad.php';
    $documentosEntidadPartialCode = is_file($documentosEntidadPartialPath)
        ? (string) file_get_contents($documentosEntidadPartialPath)
        : '';

    if (
        $documentosEntidadPartialCode !== ''
        && strpos($documentosEntidadPartialCode, 'Lista segura') !== false
        && strpos($documentosEntidadPartialCode, 'Vincular documento') !== false
        && strpos($documentosEntidadPartialCode, "documentos/subir' . \$entityQuery") !== false
        && strpos($documentosEntidadPartialCode, 'storage_path') === false
        && strpos($documentosEntidadPartialCode, 'nombre_archivo') === false
        && strpos($documentosEntidadPartialCode, 'documentos/\' . $documentoId') !== false
        && strpos($documentosEntidadPartialCode, '<form') === false
        && strpos($documentosEntidadPartialCode, 'method=') === false
        && strpos($documentosEntidadPartialCode, '/editar') === false
        && strpos($documentosEntidadPartialCode, '/actualizar') === false
    ) {
        hcOk('Centro Documental Fase 4C-A tiene partial contextual por entidad sin storage_path, formularios, edicion ni POST nuevo.');
    } else {
        hcWarning(
            'Centro Documental Fase 4C-A no muestra partial contextual completo por entidad.',
            'Validar src/app/views/partials/documentos_entidad.php: sin storage_path/nombre_archivo, sin formularios, sin editar/actualizar y solo enlaces GET seguros.'
        );
    }

    $documentosEntidadViews = [
        'proveedor' => $appRoot . '/app/views/proveedores/ver.php',
        'compra' => $appRoot . '/app/views/compras/ver.php',
        'cuenta_por_pagar' => $appRoot . '/app/views/cuentas_por_pagar/ver.php',
        'huesped' => $appRoot . '/app/views/huespedes/ver.php',
        'reservacion' => $appRoot . '/app/views/reservaciones/ver.php',
    ];
    $viewsConPartial = [];
    foreach ($documentosEntidadViews as $entidadTipo => $viewPath) {
        $viewCode = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
        if (strpos($viewCode, "View::partial('documentos_entidad'") !== false) {
            $viewsConPartial[] = $entidadTipo;
        }
    }

    if (count($viewsConPartial) === count($documentosEntidadViews)) {
        hcOk('Fichas de proveedor, compra, CxP, huesped y reservacion incluyen seccion documental contextual.');
    } else {
        $faltantes = array_diff(array_keys($documentosEntidadViews), $viewsConPartial);
        hcWarning(
            'Faltan fichas con seccion documental contextual 4C-A: ' . implode(', ', $faltantes),
            'Agregar View::partial("documentos_entidad") solo en fichas autorizadas y sin formularios nuevos.'
        );
    }

    $documentosEntidadControllers = [
        'proveedor' => $controllersDir . '/ProveedorController.php',
        'compra' => $controllersDir . '/CompraController.php',
        'cuenta_por_pagar' => $controllersDir . '/CuentaPorPagarController.php',
        'huesped' => $controllersDir . '/HuespedController.php',
        'reservacion' => $controllersDir . '/ReservacionController.php',
    ];
    $controllersConConsulta = [];
    foreach ($documentosEntidadControllers as $entidadTipo => $controllerPath) {
        $controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
        if (
            strpos($controllerCode, 'Documento') !== false
            && strpos($controllerCode, 'documentosPorEntidad') !== false
            && strpos($controllerCode, "'" . $entidadTipo . "'") !== false
        ) {
            $controllersConConsulta[] = $entidadTipo;
        }
    }

    if (count($controllersConConsulta) === count($documentosEntidadControllers)) {
        hcOk('Controladores 4C-A consultan documentos por entidad con Documento::documentosPorEntidad().');
    } else {
        $faltantes = array_diff(array_keys($documentosEntidadControllers), $controllersConConsulta);
        hcWarning(
            'Faltan consultas documentales por entidad en controladores: ' . implode(', ', $faltantes),
            'Usar Documento::documentosPorEntidad($hotelId, $entidadTipo, $id) y pasar solo metadata segura a la vista.'
        );
    }

    if (hcRouteExists($routes, 'api/sync', 'post')) {
        $apiController = $controllersDir . '/ApiController.php';
        $apiCode = is_file($apiController) ? (string) file_get_contents($apiController) : '';
        if (
            strpos($apiCode, 'sync_temporarily_disabled') !== false
            && preg_match('/function\s+syncAction\s*\([^)]*\).*?renderJSON\s*\(.*?423/s', $apiCode)
        ) {
            hcOk('/api/sync sigue registrado y bloqueado con sync_temporarily_disabled + HTTP 423 en codigo.');
        } else {
            hcError('/api/sync no muestra bloqueo estatico con HTTP 423.', 'No continuar hasta restaurar bloqueo temporal de sync.');
        }
    } else {
        hcError('No existe ruta POST /api/sync.', 'Restaurar ruta bloqueada antes de probar PWA/offline.');
    }
}

$recommendations = array_values(array_unique(array_filter($recommendations)));

echo "============================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if (!empty($recommendations)) {
    echo "Recomendaciones concretas:\n";
    foreach ($recommendations as $recommendation) {
        echo "- {$recommendation}\n";
    }
}

echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
exit($errors > 0 ? 1 : 0);
