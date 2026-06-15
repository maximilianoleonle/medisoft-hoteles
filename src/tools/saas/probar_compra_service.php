<?php
/**
 * Prueba controlada de CompraService.
 *
 * Por defecto es solo lectura. El modo --apply ejecuta una compra de prueba
 * dentro de una transaccion externa y SIEMPRE hace rollback.
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
    'confirm::',
    'target-hotel-slug::',
    'provider-id::',
    'product-id::',
    'cantidad::',
    'costo-unitario::',
    'format::',
    'help',
]);

if (isset($options['help'])) {
    echo "Uso:\n";
    echo "  php tools/saas/probar_compra_service.php [--format=text|json]\n";
    echo "  php tools/saas/probar_compra_service.php --apply --backup-file=/ruta/backup.sql --confirm=ROLLBACK_TEST\n";
    echo "\n";
    echo "El modo --apply ejecuta crearBorrador + recibirCompra y luego hace rollback.\n";
    echo "No crea rutas, UI, pagos, CxP, documentos ni movimientos de caja.\n";
    exit(0);
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$servicePath = $appRoot . '/app/services/CompraService.php';
$configPath = $appRoot . '/config/database.php';

if (!is_file($servicePath)) {
    echo "[ERROR] No existe CompraService.php.\n";
    exit(1);
}

if (!is_file($configPath)) {
    echo "[ERROR] No se encontro config/database.php.\n";
    exit(1);
}

require_once $servicePath;

$apply = array_key_exists('apply', $options);
$format = strtolower((string)($options['format'] ?? 'text'));
$backupFile = (string)($options['backup-file'] ?? '');
$confirm = (string)($options['confirm'] ?? '');
$targetHotelSlug = (string)($options['target-hotel-slug'] ?? 'los-cedros');
$providerId = isset($options['provider-id']) ? (int)$options['provider-id'] : null;
$productId = isset($options['product-id']) ? (int)$options['product-id'] : null;
$cantidad = (string)($options['cantidad'] ?? '1.00');
$costoUnitario = array_key_exists('costo-unitario', $options) ? (string)$options['costo-unitario'] : null;

if (!in_array($format, ['text', 'json'], true)) {
    echo "[ERROR] Formato no soportado: {$format}. Usa text o json.\n";
    exit(1);
}

if (!preg_match('/^[a-z0-9-]+$/', $targetHotelSlug)) {
    echo "[ERROR] Slug de hotel invalido.\n";
    exit(1);
}

if ($apply) {
    if ($backupFile === '' || !is_file($backupFile) || filesize($backupFile) <= 0) {
        echo "[ERROR] --apply requiere --backup-file existente y no vacio.\n";
        exit(1);
    }

    if ($confirm !== 'ROLLBACK_TEST') {
        echo "[ERROR] --apply requiere --confirm=ROLLBACK_TEST.\n";
        exit(1);
    }
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $report = $apply
        ? runRollbackExercise($db, $pdo, [
            'backup_file' => $backupFile,
            'target_hotel_slug' => $targetHotelSlug,
            'provider_id' => $providerId,
            'product_id' => $productId,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
        ])
        : runDryRun($pdo, [
            'target_hotel_slug' => $targetHotelSlug,
            'provider_id' => $providerId,
            'product_id' => $productId,
            'cantidad' => $cantidad,
            'costo_unitario' => $costoUnitario,
        ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit(!empty($report['errors']) ? 1 : 0);
}

renderTextReport($report);
exit(!empty($report['errors']) ? 1 : 0);

function runDryRun(PDO $pdo, array $runtime): array
{
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    $pdo->exec('START TRANSACTION READ ONLY');

    try {
        $fixture = buildFixture($pdo, $runtime);
        $snapshot = fetchSnapshot($pdo, $fixture);
        $warnings = validateStaticContract($pdo);

        $pdo->rollBack();

        return [
            'phase' => '2Q',
            'mode' => 'dry-run',
            'writes_db' => false,
            'rollback_test_executed' => false,
            'fixture' => publicFixture($fixture),
            'snapshot' => $snapshot,
            'warnings' => $warnings,
            'errors' => 0,
            'recommendations' => [
                'Para ejecutar prueba transaccional con rollback: --apply --backup-file=/ruta/backup.sql --confirm=ROLLBACK_TEST',
                'No exponer rutas ni UI hasta que esta prueba pase en verde.',
            ],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function runRollbackExercise(Database $db, PDO $pdo, array $runtime): array
{
    $fixture = buildFixture($pdo, $runtime);
    $before = fetchSnapshot($pdo, $fixture);
    $warnings = validateStaticContract($pdo);
    $folio = 'F2Q-' . date('YmdHis') . '-' . random_int(1000, 9999);

    $pdo->beginTransaction();

    try {
        $service = new CompraService($db, ['manage_transaction' => false]);
        $compraId = $service->crearBorrador(
            (int)$fixture['hotel']['id'],
            [
                'proveedor_id' => (int)$fixture['provider']['id'],
                'folio' => $folio,
                'fecha_compra' => date('Y-m-d'),
                'notas' => 'Prueba Fase 2Q con rollback automatico',
            ],
            [[
                'producto_id' => (int)$fixture['product']['id'],
                'cantidad' => $fixture['cantidad'],
                'costo_unitario' => $fixture['costo_unitario'],
            ]],
            null
        );

        $received = $service->recibirCompra((int)$fixture['hotel']['id'], $compraId, null);
        $insideCompra = $service->obtenerCompra((int)$fixture['hotel']['id'], $compraId);
        $inside = fetchSnapshot($pdo, $fixture);

        $pdo->rollBack();

        $after = fetchSnapshot($pdo, $fixture);
        $persisted = findPurchaseByFolio($pdo, (int)$fixture['hotel']['id'], $folio);
        $rollbackOk = snapshotsMatch($before, $after) && !$persisted;

        return [
            'phase' => '2Q',
            'mode' => 'rollback-test',
            'writes_db' => true,
            'rolled_back' => true,
            'rollback_ok' => $rollbackOk,
            'backup_file' => $runtime['backup_file'],
            'fixture' => publicFixture($fixture),
            'compra_id_temporal' => $compraId,
            'folio_temporal' => $folio,
            'received' => $received,
            'inside_transaction' => [
                'compra_estado' => $insideCompra['estado'] ?? null,
                'detalle_count' => isset($insideCompra['detalles']) ? count($insideCompra['detalles']) : 0,
                'snapshot' => $inside,
            ],
            'before' => $before,
            'after' => $after,
            'warnings' => $warnings,
            'errors' => $rollbackOk ? 0 : 1,
            'recommendations' => $rollbackOk
                ? ['Rollback verificado. La siguiente fase puede preparar UI interna controlada.']
                : ['Revisar inmediatamente: la prueba no dejo el estado inicial intacto. Restaurar backup si hay duda.'],
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function buildFixture(PDO $pdo, array $runtime): array
{
    $hotel = fetchHotel($pdo, (string)$runtime['target_hotel_slug']);
    if (!$hotel) {
        throw new RuntimeException('No se encontro hotel activo: ' . $runtime['target_hotel_slug']);
    }

    $provider = fetchProvider($pdo, (int)$hotel['id'], $runtime['provider_id']);
    if (!$provider) {
        throw new RuntimeException('No se encontro proveedor activo para el hotel.');
    }

    $product = fetchProduct($pdo, (int)$hotel['id'], $runtime['product_id']);
    if (!$product) {
        throw new RuntimeException('No se encontro producto activo para el hotel.');
    }

    $cantidad = normalizePositiveDecimal($runtime['cantidad'], 'Cantidad invalida');
    $costo = $runtime['costo_unitario'] === null
        ? normalizeNonNegativeDecimal($product['costo_unitario'] ?? 0, 'Costo invalido')
        : normalizeNonNegativeDecimal($runtime['costo_unitario'], 'Costo invalido');

    return [
        'hotel' => $hotel,
        'provider' => $provider,
        'product' => $product,
        'cantidad' => decimal($cantidad),
        'costo_unitario' => decimal($costo),
    ];
}

function fetchHotel(PDO $pdo, string $slug): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, nombre, slug
         FROM hoteles
         WHERE slug = ?
           AND activo = 1
         LIMIT 1"
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function fetchProvider(PDO $pdo, int $hotelId, ?int $providerId): ?array
{
    if ($providerId !== null && $providerId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, nombre
             FROM proveedores
             WHERE id = ?
               AND hotel_id = ?
               AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([$providerId, $hotelId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, nombre
             FROM proveedores
             WHERE hotel_id = ?
               AND activo = 1
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([$hotelId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetchProduct(PDO $pdo, int $hotelId, ?int $productId): ?array
{
    if ($productId !== null && $productId > 0) {
        $stmt = $pdo->prepare(
            "SELECT id, nombre, codigo, stock_actual, costo_unitario
             FROM inventario_productos
             WHERE id = ?
               AND hotel_id = ?
               AND activo = 1
             LIMIT 1"
        );
        $stmt->execute([$productId, $hotelId]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, nombre, codigo, stock_actual, costo_unitario
             FROM inventario_productos
             WHERE hotel_id = ?
               AND activo = 1
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([$hotelId]);
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function fetchSnapshot(PDO $pdo, array $fixture): array
{
    $hotelId = (int)$fixture['hotel']['id'];
    $productId = (int)$fixture['product']['id'];

    return [
        'compras' => countRows($pdo, 'compras', 'hotel_id = ?', [$hotelId]),
        'compra_detalles' => countRows($pdo, 'compra_detalles', 'hotel_id = ?', [$hotelId]),
        'movimientos_inventario' => countRows($pdo, 'movimientos_inventario', 'hotel_id = ?', [$hotelId]),
        'logs_auditoria' => countRows($pdo, 'logs_auditoria', 'hotel_id = ?', [$hotelId]),
        'producto_stock' => fetchProductStock($pdo, $hotelId, $productId),
    ];
}

function countRows(PDO $pdo, string $table, string $where, array $params): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '` WHERE ' . $where);
    $stmt->execute($params);

    return (int)$stmt->fetchColumn();
}

function fetchProductStock(PDO $pdo, int $hotelId, int $productId): string
{
    $stmt = $pdo->prepare(
        "SELECT stock_actual
         FROM inventario_productos
         WHERE id = ?
           AND hotel_id = ?
         LIMIT 1"
    );
    $stmt->execute([$productId, $hotelId]);

    return decimal($stmt->fetchColumn());
}

function findPurchaseByFolio(PDO $pdo, int $hotelId, string $folio): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM compras
         WHERE hotel_id = ?
           AND folio = ?"
    );
    $stmt->execute([$hotelId, $folio]);

    return (int)$stmt->fetchColumn() > 0;
}

function validateStaticContract(PDO $pdo): array
{
    $warnings = [];

    foreach (['compra_pagos', 'cuentas_por_pagar', 'documentos_proveedor'] as $table) {
        if (tableExists($pdo, $table)) {
            $warnings[] = "Tabla fuera de alcance detectada: {$table}";
        }
    }

    $routesPath = dirname(__DIR__, 2) . '/config/routes.php';
    if (is_file($routesPath)) {
        $routes = (string)file_get_contents($routesPath);
        foreach (['/compras', '/cuentas-por-pagar', '/documentos-proveedor'] as $token) {
            if (strpos($routes, $token) !== false) {
                $warnings[] = "Ruta fuera de alcance detectada: {$token}";
            }
        }
    }

    return $warnings;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);

    return (int)$stmt->fetchColumn() > 0;
}

function snapshotsMatch(array $before, array $after): bool
{
    return $before === $after;
}

function publicFixture(array $fixture): array
{
    return [
        'hotel' => [
            'id' => (int)$fixture['hotel']['id'],
            'nombre' => $fixture['hotel']['nombre'],
            'slug' => $fixture['hotel']['slug'],
        ],
        'provider' => [
            'id' => (int)$fixture['provider']['id'],
            'nombre' => $fixture['provider']['nombre'],
        ],
        'product' => [
            'id' => (int)$fixture['product']['id'],
            'nombre' => $fixture['product']['nombre'],
            'codigo' => $fixture['product']['codigo'],
            'stock_actual' => decimal($fixture['product']['stock_actual']),
        ],
        'cantidad' => $fixture['cantidad'],
        'costo_unitario' => $fixture['costo_unitario'],
    ];
}

function normalizePositiveDecimal($value, string $message): float
{
    $number = normalizeNumber($value, $message);
    if ($number <= 0) {
        throw new InvalidArgumentException($message);
    }

    return $number;
}

function normalizeNonNegativeDecimal($value, string $message): float
{
    $number = normalizeNumber($value, $message);
    if ($number < 0) {
        throw new InvalidArgumentException($message);
    }

    return $number;
}

function normalizeNumber($value, string $message): float
{
    if (!is_numeric($value)) {
        throw new InvalidArgumentException($message);
    }

    return round((float)$value, 2);
}

function decimal($value): string
{
    return number_format((float)$value, 2, '.', '');
}

function renderTextReport(array $report): void
{
    echo "Prueba CompraService Fase 2Q\n";
    echo "============================\n";
    echo "Modo: " . $report['mode'] . "\n";
    echo "Escribe DB: " . (!empty($report['writes_db']) ? 'si, con rollback' : 'no') . "\n";

    if (!empty($report['backup_file'])) {
        echo "Backup: " . $report['backup_file'] . "\n";
    }

    echo "Hotel: " . $report['fixture']['hotel']['nombre'] . " (#" . $report['fixture']['hotel']['id'] . ")\n";
    echo "Proveedor: " . $report['fixture']['provider']['nombre'] . " (#" . $report['fixture']['provider']['id'] . ")\n";
    echo "Producto: " . $report['fixture']['product']['nombre'] . " (#" . $report['fixture']['product']['id'] . ")\n";
    echo "Cantidad: " . $report['fixture']['cantidad'] . "\n";

    if (isset($report['rollback_ok'])) {
        echo "Rollback OK: " . ($report['rollback_ok'] ? 'si' : 'no') . "\n";
        echo "Compra temporal: " . ($report['compra_id_temporal'] ?? 'n/a') . "\n";
    }

    if (!empty($report['snapshot'])) {
        renderSnapshot('Snapshot', $report['snapshot']);
    }
    if (!empty($report['before'])) {
        renderSnapshot('Antes', $report['before']);
    }
    if (!empty($report['inside_transaction']['snapshot'])) {
        renderSnapshot('Dentro de transaccion', $report['inside_transaction']['snapshot']);
    }
    if (!empty($report['after'])) {
        renderSnapshot('Despues de rollback', $report['after']);
    }

    if (!empty($report['warnings'])) {
        echo "Warnings:\n";
        foreach ($report['warnings'] as $warning) {
            echo "- {$warning}\n";
        }
    }

    echo "Errores: " . ($report['errors'] ?? 0) . "\n";

    if (!empty($report['recommendations'])) {
        echo "Recomendaciones:\n";
        foreach ($report['recommendations'] as $recommendation) {
            echo "- {$recommendation}\n";
        }
    }
}

function renderSnapshot(string $label, array $snapshot): void
{
    echo "{$label}: ";
    echo 'compras=' . $snapshot['compras'];
    echo ', detalles=' . $snapshot['compra_detalles'];
    echo ', movimientos=' . $snapshot['movimientos_inventario'];
    echo ', auditoria=' . $snapshot['logs_auditoria'];
    echo ', stock=' . $snapshot['producto_stock'];
    echo "\n";
}
