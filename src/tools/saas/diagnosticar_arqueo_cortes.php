<?php
/**
 * Diagnostico read-only de cortes con diferencias de arqueo.
 *
 * No abre, cierra, recalcula ni corrige cortes. Solo lista diferencias entre
 * totales guardados en cortes_caja y movimientos_caja asociados.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo "[ERROR] APP_ENV debe ser local. Valor actual: " . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    echo "Resultado general: FAIL\n";
    exit(1);
}

$limit = 50;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string)$arg, $m) === 1) {
        $limit = max(1, min(500, (int)$m[1]));
    }
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';

$ok = 0;
$warnings = 0;
$errors = 0;

function dacLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function dacOk(string $message): void
{
    global $ok;
    $ok++;
    dacLine('OK', $message);
}

function dacWarning(string $message): void
{
    global $warnings;
    $warnings++;
    dacLine('WARNING', $message);
}

function dacError(string $message): void
{
    global $errors;
    $errors++;
    dacLine('ERROR', $message);
}

function dacMoney($value): string
{
    return '$' . number_format((float)$value, 2, '.', ',');
}

function dacSignedMoney($value): string
{
    $amount = (float)$value;
    return ($amount >= 0 ? '+' : '-') . dacMoney(abs($amount));
}

function dacSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function dacFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function dacScalar(PDO $pdo, string $sql, array $params = [])
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchColumn();
}

function dacTableExists(PDO $pdo, string $database, string $table): bool
{
    return (int)dacScalar(
        $pdo,
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table',
        ['db' => $database, 'table' => $table]
    ) > 0;
}

function dacColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    return (int)dacScalar(
        $pdo,
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column',
        ['db' => $database, 'table' => $table, 'column' => $column]
    ) > 0;
}

function dacPrintCut(array $row): void
{
    echo '#'
        . (int)($row['corte_id'] ?? 0)
        . ' | hotel ' . (int)($row['hotel_id'] ?? 0)
        . ' | ' . (string)($row['caja_nombre'] ?? 'Caja sin nombre')
        . ' | cierre ' . (string)($row['fecha_cierre'] ?? '-')
        . ' | movimientos ' . (int)($row['movimientos_count'] ?? 0)
        . "\n";
}

function dacPrintMethodDelta(array $row, string $label, string $savedKey, string $movKey, string $deltaKey): void
{
    $delta = (float)($row[$deltaKey] ?? 0);
    if (abs($delta) <= 0.01) {
        return;
    }

    echo '  - ' . $label
        . ': guardado ' . dacMoney($row[$savedKey] ?? 0)
        . ' vs movimientos ' . dacMoney($row[$movKey] ?? 0)
        . ' => delta ' . dacSignedMoney($delta)
        . "\n";
}

echo "Diagnostico read-only de arqueo de cortes\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    dacError('No se encontro config/database.php.');
    echo "Resultado general: FAIL\n";
    exit(1);
}

$pdo = null;
$database = '';

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
    dacOk('Conexion read-only inicializada para ' . $database . '.');
} catch (Throwable $e) {
    dacError('No se pudo conectar en modo read-only: ' . $e->getMessage());
    echo "Resultado general: FAIL\n";
    exit(1);
}

$required = [
    'cortes_caja' => [
        'id',
        'hotel_id',
        'caja_id',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'monto_inicial',
        'total_ingresos_efectivo',
        'total_ingresos_tarjeta',
        'total_ingresos_transferencia',
        'total_gastos_efectivo',
        'total_gastos_tarjeta',
        'total_gastos_transferencia',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia',
    ],
    'movimientos_caja' => ['id', 'hotel_id', 'corte_id', 'tipo', 'metodo_pago', 'monto', 'categoria', 'created_at'],
    'cajas' => ['id', 'hotel_id', 'nombre'],
];

$schemaReady = true;
foreach ($required as $table => $columns) {
    if (!dacTableExists($pdo, $database, $table)) {
        dacError('Falta tabla requerida: ' . $table . '.');
        $schemaReady = false;
        continue;
    }

    $missing = [];
    foreach ($columns as $column) {
        if (!dacColumnExists($pdo, $database, $table, $column)) {
            $missing[] = $column;
        }
    }

    if ($missing) {
        dacError('Tabla ' . $table . ' incompleta. Faltan columnas: ' . implode(', ', $missing) . '.');
        $schemaReady = false;
    } else {
        dacOk('Tabla ' . $table . ' disponible con columnas requeridas.');
    }
}

if (!$schemaReady) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Resultado general: FAIL\n";
    exit(1);
}

$summary = dacFetchAll(
    $pdo,
    "SELECT cc.estado,
            COUNT(DISTINCT cc.id) AS cortes,
            COALESCE(SUM(CASE WHEN mc.id IS NULL THEN 0 ELSE 1 END), 0) AS movimientos
     FROM cortes_caja cc
     LEFT JOIN movimientos_caja mc
       ON mc.corte_id = cc.id
      AND mc.hotel_id = cc.hotel_id
     GROUP BY cc.estado
     ORDER BY cc.estado"
);

dacSection('Resumen general');
foreach ($summary as $row) {
    dacOk(
        'Estado ' . (string)($row['estado'] ?? '(sin estado)')
        . ': cortes ' . (int)$row['cortes']
        . ', movimientos vinculados ' . (int)$row['movimientos'] . '.'
    );
}

$baseTotalsSql = "
    SELECT cc.id AS corte_id,
           cc.hotel_id,
           cc.caja_id,
           COALESCE(c.nombre, CONCAT('Caja #', cc.caja_id)) AS caja_nombre,
           cc.estado,
           cc.fecha_apertura,
           cc.fecha_cierre,
           ROUND(COALESCE(cc.monto_inicial, 0), 2) AS monto_inicial,
           ROUND(COALESCE(cc.total_ingresos_efectivo, 0), 2) AS corte_ing_efectivo,
           ROUND(COALESCE(cc.total_ingresos_tarjeta, 0), 2) AS corte_ing_tarjeta,
           ROUND(COALESCE(cc.total_ingresos_transferencia, 0), 2) AS corte_ing_transferencia,
           ROUND(COALESCE(cc.total_gastos_efectivo, 0), 2) AS corte_gasto_efectivo,
           ROUND(COALESCE(cc.total_gastos_tarjeta, 0), 2) AS corte_gasto_tarjeta,
           ROUND(COALESCE(cc.total_gastos_transferencia, 0), 2) AS corte_gasto_transferencia,
           ROUND(COALESCE(cc.efectivo_esperado, 0), 2) AS efectivo_esperado,
           ROUND(COALESCE(cc.efectivo_contado, 0), 2) AS efectivo_contado,
           ROUND(COALESCE(cc.diferencia, 0), 2) AS diferencia_guardada,
           COUNT(mc.id) AS movimientos_count,
           MIN(mc.created_at) AS primer_movimiento,
           MAX(mc.created_at) AS ultimo_movimiento,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_efectivo,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_tarjeta,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_transferencia,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_efectivo,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_tarjeta,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_transferencia,
           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE -mc.monto END), 0), 2) AS mov_neto_total
    FROM cortes_caja cc
    LEFT JOIN cajas c
      ON c.id = cc.caja_id
     AND (c.hotel_id = cc.hotel_id OR c.hotel_id IS NULL OR cc.hotel_id IS NULL)
    LEFT JOIN movimientos_caja mc
      ON mc.corte_id = cc.id
     AND mc.hotel_id = cc.hotel_id
    WHERE cc.estado = 'cerrado'
    GROUP BY cc.id,
             cc.hotel_id,
             cc.caja_id,
             c.nombre,
             cc.estado,
             cc.fecha_apertura,
             cc.fecha_cierre,
             cc.monto_inicial,
             cc.total_ingresos_efectivo,
             cc.total_ingresos_tarjeta,
             cc.total_ingresos_transferencia,
             cc.total_gastos_efectivo,
             cc.total_gastos_tarjeta,
             cc.total_gastos_transferencia,
             cc.efectivo_esperado,
             cc.efectivo_contado,
             cc.diferencia
";

$totalsMismatchSql = "
    SELECT x.*,
           ROUND(x.mov_ing_efectivo - x.corte_ing_efectivo, 2) AS delta_ing_efectivo,
           ROUND(x.mov_ing_tarjeta - x.corte_ing_tarjeta, 2) AS delta_ing_tarjeta,
           ROUND(x.mov_ing_transferencia - x.corte_ing_transferencia, 2) AS delta_ing_transferencia,
           ROUND(x.mov_gasto_efectivo - x.corte_gasto_efectivo, 2) AS delta_gasto_efectivo,
           ROUND(x.mov_gasto_tarjeta - x.corte_gasto_tarjeta, 2) AS delta_gasto_tarjeta,
           ROUND(x.mov_gasto_transferencia - x.corte_gasto_transferencia, 2) AS delta_gasto_transferencia,
           ROUND(
                ABS(x.mov_ing_efectivo - x.corte_ing_efectivo)
              + ABS(x.mov_ing_tarjeta - x.corte_ing_tarjeta)
              + ABS(x.mov_ing_transferencia - x.corte_ing_transferencia)
              + ABS(x.mov_gasto_efectivo - x.corte_gasto_efectivo)
              + ABS(x.mov_gasto_tarjeta - x.corte_gasto_tarjeta)
              + ABS(x.mov_gasto_transferencia - x.corte_gasto_transferencia),
              2
           ) AS delta_abs_total
    FROM ({$baseTotalsSql}) x
    HAVING ABS(delta_ing_efectivo) > 0.01
        OR ABS(delta_ing_tarjeta) > 0.01
        OR ABS(delta_ing_transferencia) > 0.01
        OR ABS(delta_gasto_efectivo) > 0.01
        OR ABS(delta_gasto_tarjeta) > 0.01
        OR ABS(delta_gasto_transferencia) > 0.01
    ORDER BY delta_abs_total DESC, corte_id DESC
    LIMIT :limit
";

$stmt = $pdo->prepare($totalsMismatchSql);
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$totalsMismatch = $stmt->fetchAll();

dacSection('Cortes cerrados con totales guardados distintos a movimientos');
if (!$totalsMismatch) {
    dacOk('No hay cortes cerrados con diferencias entre totales guardados y movimientos.');
} else {
    dacWarning('Se encontraron ' . count($totalsMismatch) . ' corte(s) en el listado limitado a ' . $limit . '.');
    foreach ($totalsMismatch as $row) {
        dacPrintCut($row);
        echo '  Primer movimiento: ' . (string)($row['primer_movimiento'] ?? '-') . ' | ultimo: ' . (string)($row['ultimo_movimiento'] ?? '-') . "\n";
        echo '  Delta absoluto total: ' . dacMoney($row['delta_abs_total'] ?? 0) . "\n";
        dacPrintMethodDelta($row, 'ingresos efectivo', 'corte_ing_efectivo', 'mov_ing_efectivo', 'delta_ing_efectivo');
        dacPrintMethodDelta($row, 'ingresos tarjeta', 'corte_ing_tarjeta', 'mov_ing_tarjeta', 'delta_ing_tarjeta');
        dacPrintMethodDelta($row, 'ingresos transferencia', 'corte_ing_transferencia', 'mov_ing_transferencia', 'delta_ing_transferencia');
        dacPrintMethodDelta($row, 'gastos efectivo', 'corte_gasto_efectivo', 'mov_gasto_efectivo', 'delta_gasto_efectivo');
        dacPrintMethodDelta($row, 'gastos tarjeta', 'corte_gasto_tarjeta', 'mov_gasto_tarjeta', 'delta_gasto_tarjeta');
        dacPrintMethodDelta($row, 'gastos transferencia', 'corte_gasto_transferencia', 'mov_gasto_transferencia', 'delta_gasto_transferencia');
    }
}

$cashExpectedStoredSql = "
    SELECT x.*,
           ROUND(x.monto_inicial + x.corte_ing_efectivo - x.corte_gasto_efectivo, 2) AS efectivo_formula_guardada,
           ROUND(x.efectivo_esperado - (x.monto_inicial + x.corte_ing_efectivo - x.corte_gasto_efectivo), 2) AS delta_esperado_vs_guardado
    FROM ({$baseTotalsSql}) x
    HAVING ABS(delta_esperado_vs_guardado) > 0.01
    ORDER BY ABS(delta_esperado_vs_guardado) DESC, corte_id DESC
    LIMIT :limit
";

$stmt = $pdo->prepare($cashExpectedStoredSql);
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$cashExpectedStored = $stmt->fetchAll();

dacSection('Cortes cerrados con efectivo esperado distinto a formula guardada');
if (!$cashExpectedStored) {
    dacOk('No hay cortes cerrados con efectivo esperado distinto a monto inicial + ingresos efectivo guardados - gastos efectivo guardados.');
} else {
    dacWarning('Se encontraron ' . count($cashExpectedStored) . ' corte(s) en el listado limitado a ' . $limit . '.');
    foreach ($cashExpectedStored as $row) {
        dacPrintCut($row);
        echo '  Efectivo esperado guardado: ' . dacMoney($row['efectivo_esperado'] ?? 0) . "\n";
        echo '  Formula con totales guardados: ' . dacMoney($row['efectivo_formula_guardada'] ?? 0)
            . ' | delta ' . dacSignedMoney($row['delta_esperado_vs_guardado'] ?? 0) . "\n";
    }
}

$cashExpectedMovementSql = "
    SELECT x.*,
           ROUND(x.monto_inicial + x.mov_ing_efectivo - x.mov_gasto_efectivo, 2) AS efectivo_formula_movimientos,
           ROUND(x.efectivo_esperado - (x.monto_inicial + x.mov_ing_efectivo - x.mov_gasto_efectivo), 2) AS delta_esperado_vs_movimientos
    FROM ({$baseTotalsSql}) x
    HAVING ABS(delta_esperado_vs_movimientos) > 0.01
    ORDER BY ABS(delta_esperado_vs_movimientos) DESC, corte_id DESC
    LIMIT :limit
";

$stmt = $pdo->prepare($cashExpectedMovementSql);
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$cashExpectedMovement = $stmt->fetchAll();

dacSection('Cortes cerrados con efectivo esperado distinto a formula por movimientos');
if (!$cashExpectedMovement) {
    dacOk('No hay cortes cerrados con efectivo esperado distinto a monto inicial + ingresos efectivo por movimientos - gastos efectivo por movimientos.');
} else {
    dacWarning('Se encontraron ' . count($cashExpectedMovement) . ' corte(s) en el listado limitado a ' . $limit . '.');
    foreach ($cashExpectedMovement as $row) {
        dacPrintCut($row);
        echo '  Efectivo esperado guardado: ' . dacMoney($row['efectivo_esperado'] ?? 0) . "\n";
        echo '  Formula con movimientos: ' . dacMoney($row['efectivo_formula_movimientos'] ?? 0)
            . ' | delta ' . dacSignedMoney($row['delta_esperado_vs_movimientos'] ?? 0) . "\n";
    }
}

$diffSql = "
    SELECT x.*,
           ROUND(x.efectivo_contado - x.efectivo_esperado, 2) AS diferencia_formula,
           ROUND(x.diferencia_guardada - (x.efectivo_contado - x.efectivo_esperado), 2) AS delta_diferencia
    FROM ({$baseTotalsSql}) x
    HAVING ABS(delta_diferencia) > 0.01
    ORDER BY ABS(delta_diferencia) DESC, corte_id DESC
    LIMIT :limit
";

$stmt = $pdo->prepare($diffSql);
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$diffRows = $stmt->fetchAll();

dacSection('Cortes cerrados con diferencia de arqueo inconsistente');
if (!$diffRows) {
    dacOk('No hay cortes cerrados con diferencia guardada distinta a contado menos esperado.');
} else {
    dacWarning('Se encontraron ' . count($diffRows) . ' corte(s) en el listado limitado a ' . $limit . '.');
    foreach ($diffRows as $row) {
        dacPrintCut($row);
        echo '  Diferencia guardada: ' . dacMoney($row['diferencia_guardada'] ?? 0) . "\n";
        echo '  Diferencia calculada: ' . dacMoney($row['diferencia_formula'] ?? 0)
            . ' | delta ' . dacSignedMoney($row['delta_diferencia'] ?? 0) . "\n";
    }
}

$problemCutIds = [];
foreach ([$totalsMismatch, $cashExpectedStored, $cashExpectedMovement, $diffRows] as $group) {
    foreach ($group as $row) {
        $problemCutIds[(int)$row['corte_id']] = true;
    }
}

dacSection('Movimientos posteriores al cierre en cortes con diferencias');
if (!$problemCutIds) {
    dacOk('Sin cortes con diferencias para revisar movimientos posteriores al cierre.');
} else {
    $ids = array_keys($problemCutIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $lateRows = dacFetchAll(
        $pdo,
        "SELECT cc.id AS corte_id,
                cc.fecha_cierre,
                mc.tipo,
                mc.metodo_pago,
                COALESCE(NULLIF(mc.categoria, ''), '(sin categoria)') AS categoria,
                COUNT(*) AS movimientos,
                ROUND(COALESCE(SUM(mc.monto), 0), 2) AS total,
                MIN(mc.created_at) AS primer_movimiento,
                MAX(mc.created_at) AS ultimo_movimiento
         FROM cortes_caja cc
         INNER JOIN movimientos_caja mc
           ON mc.corte_id = cc.id
          AND mc.hotel_id = cc.hotel_id
         WHERE cc.estado = 'cerrado'
           AND cc.id IN ({$placeholders})
           AND cc.fecha_cierre IS NOT NULL
           AND mc.created_at > cc.fecha_cierre
         GROUP BY cc.id,
                  cc.fecha_cierre,
                  mc.tipo,
                  mc.metodo_pago,
                  COALESCE(NULLIF(mc.categoria, ''), '(sin categoria)')
         ORDER BY cc.id DESC, mc.tipo, mc.metodo_pago, total DESC",
        $ids
    );

    if (!$lateRows) {
        dacOk('No hay movimientos posteriores al cierre en los cortes con diferencias.');
    } else {
        dacWarning('Se encontraron movimientos creados despues de fecha_cierre en cortes con diferencias.');
        foreach ($lateRows as $row) {
            dacOk(
                'Corte #' . (int)$row['corte_id']
                . ' | cierre ' . (string)$row['fecha_cierre']
                . ' | ' . (string)$row['tipo']
                . ' | ' . (string)$row['metodo_pago']
                . ' | ' . (string)$row['categoria']
                . ': ' . (int)$row['movimientos']
                . ' mov, ' . dacMoney($row['total'])
                . ' | primero ' . (string)$row['primer_movimiento']
                . ' | ultimo ' . (string)$row['ultimo_movimiento']
            );
        }
    }
}

dacSection('Categorias en cortes con diferencias');
if (!$problemCutIds) {
    dacOk('Sin cortes con diferencias para agrupar categorias.');
} else {
    $ids = array_keys($problemCutIds);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $categoryRows = dacFetchAll(
        $pdo,
        "SELECT mc.corte_id,
                mc.tipo,
                mc.metodo_pago,
                COALESCE(NULLIF(mc.categoria, ''), '(sin categoria)') AS categoria,
                COUNT(*) AS movimientos,
                ROUND(COALESCE(SUM(mc.monto), 0), 2) AS total
         FROM movimientos_caja mc
         WHERE mc.corte_id IN ({$placeholders})
         GROUP BY mc.corte_id, mc.tipo, mc.metodo_pago, COALESCE(NULLIF(mc.categoria, ''), '(sin categoria)')
         ORDER BY mc.corte_id DESC, mc.tipo, mc.metodo_pago, total DESC",
        $ids
    );

    if (!$categoryRows) {
        dacWarning('Los cortes con diferencias no tienen movimientos vinculados.');
    } else {
        foreach ($categoryRows as $row) {
            dacOk(
                'Corte #' . (int)$row['corte_id']
                . ' | ' . (string)$row['tipo']
                . ' | ' . (string)$row['metodo_pago']
                . ' | ' . (string)$row['categoria']
                . ': ' . (int)$row['movimientos']
                . ' mov, ' . dacMoney($row['total'])
            );
        }
    }
}

if ($pdo->inTransaction()) {
    $pdo->rollBack();
    dacOk('Transaccion read-only cerrada con rollback.');
}

echo "=====================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";
echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";

exit($errors > 0 ? 1 : 0);
