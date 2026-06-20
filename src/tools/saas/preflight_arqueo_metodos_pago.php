<?php
/**
 * Preflight Fase 9C-A - Arqueo por corte y metodo read-only.
 *
 * Diagnostica cortes y movimientos de Caja sin modificar datos.
 * No abre, cierra, recalcula ni corrige cortes.
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

function ampLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function ampOk(string $message): void
{
    global $ok;
    $ok++;
    ampLine('OK', $message);
}

function ampWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    ampLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function ampError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    ampLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function ampSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function ampTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function ampColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function ampScalar(PDO $pdo, string $sql)
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function ampCount(PDO $pdo, string $sql): ?int
{
    $value = ampScalar($pdo, $sql);

    return $value === null ? null : (int)$value;
}

function ampMoney($value): string
{
    return '$' . number_format((float)$value, 2, '.', ',');
}

function ampMetric(string $label, $value): void
{
    if ($value === null) {
        ampWarning('No se pudo calcular metrica: ' . $label . '.', 'Revisar manualmente la consulta read-only 9C-A.');
        return;
    }

    ampOk($label . ': ' . (string)$value . '.');
}

function ampMoneyMetric(string $label, $value): void
{
    if ($value === null) {
        ampWarning('No se pudo calcular importe: ' . $label . '.', 'Revisar manualmente la consulta read-only 9C-A.');
        return;
    }

    ampOk($label . ': ' . ampMoney($value) . '.');
}

function ampReportZero(string $label, ?int $count, string $recommendation, bool $warningOnly = false): void
{
    if ($count === null) {
        ampWarning('No se pudo validar arqueo: ' . $label . '.', 'Revisar manualmente antes de exponer arqueo por metodo.');
        return;
    }

    if ($count === 0) {
        ampOk('Arqueo OK: ' . $label . ' = 0.');
        return;
    }

    if ($warningOnly) {
        ampWarning('Arqueo pendiente de revisar: ' . $label . ' = ' . (string)$count . '.', $recommendation);
        return;
    }

    ampError('Arqueo fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function ampPrintSummary(): void
{
    global $ok, $warnings, $errors, $recommendations;

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
}

$appEnv = getenv('APP_ENV');
if ($appEnv === 'local') {
    ampOk('APP_ENV local confirmado para preflight read-only.');
} else {
    ampError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : (string)$appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';

echo "Preflight Fase 9C-A/9C-B-A - Arqueo por corte y metodo read-only\n";
echo "=====================================================\n";

$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/CajaController.php';
$modelPath = $appRoot . '/app/models/ArqueoMetodosPago.php';
$viewPath = $appRoot . '/app/views/caja/arqueo_metodos.php';

ampSection('Pantalla 9C-B-A GET/read-only');

$routesCode = is_file($routesPath) ? (string)file_get_contents($routesPath) : '';
$controllerCode = is_file($controllerPath) ? (string)file_get_contents($controllerPath) : '';
$modelCode = is_file($modelPath) ? (string)file_get_contents($modelPath) : '';
$viewCode = is_file($viewPath) ? (string)file_get_contents($viewPath) : '';

if ($routesCode !== '' && strpos($routesCode, "get('/caja/arqueo-metodos'") !== false) {
    ampOk('Ruta 9C-B-A registrada como GET /caja/arqueo-metodos.');
} else {
    ampError('No se encontro GET /caja/arqueo-metodos.', 'Registrar solo ruta GET para la pantalla read-only 9C-B-A.');
}

if ($routesCode !== '' && preg_match("/post\\(['\"]\\/caja\\/arqueo-metodos['\"]/i", $routesCode) !== 1) {
    ampOk('No existe POST /caja/arqueo-metodos.');
} else {
    ampError('Se encontro POST /caja/arqueo-metodos fuera de contrato.', 'Retirar cualquier ruta POST de arqueo por metodo.');
}

if (
    $controllerCode !== ''
    && strpos($controllerCode, 'function arqueoMetodosAction') !== false
    && strpos($controllerCode, 'ArqueoMetodosPago') !== false
    && strpos($controllerCode, 'reporteReadOnlyPorHotel') !== false
    && strpos($controllerCode, "View::renderTemplate('caja/arqueo_metodos'") !== false
    && strpos($controllerCode, 'getQuery') !== false
) {
    ampOk('CajaController expone arqueoMetodosAction con lector read-only y filtros GET.');
} else {
    ampError(
        'CajaController no muestra contrato completo para 9C-B-A.',
        'Usar ArqueoMetodosPago::reporteReadOnlyPorHotel(), filtros getQuery y vista caja/arqueo_metodos.'
    );
}

if (
    $modelCode !== ''
    && strpos($modelCode, 'class ArqueoMetodosPago') !== false
    && strpos($modelCode, 'function reporteReadOnlyPorHotel') !== false
    && strpos($modelCode, 'START TRANSACTION READ ONLY') !== false
    && strpos($modelCode, 'rollBack') !== false
    && strpos($modelCode, 'hotel_id') !== false
    && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $modelCode)
) {
    ampOk('ArqueoMetodosPago es lector read-only con hotel_id, transaccion y rollback.');
} else {
    ampError(
        'ArqueoMetodosPago no cumple contrato read-only.',
        'Mantener modelo sin escrituras SQL, con START TRANSACTION READ ONLY, rollback y filtro por hotel actual.'
    );
}

if (
    $viewCode !== ''
    && strpos($viewCode, 'Solo lectura') !== false
    && strpos($viewCode, 'method="get"') !== false
    && preg_match('/method\s*=\s*[\'"]post[\'"]/i', $viewCode) !== 1
    && strpos($viewCode, 'csrf_field()') === false
    && strpos($viewCode, 'name="hotel_id"') === false
    && strpos($viewCode, '--brand-') !== false
    && strpos($viewCode, '--ms-') === false
) {
    ampOk('Vista caja/arqueo_metodos usa filtros GET, solo lectura y branding hotelero.');
} else {
    ampError(
        'Vista caja/arqueo_metodos no cumple contrato visual/read-only.',
        'Usar filtros GET, etiqueta Solo lectura, tokens --brand-*, sin CSRF/POST y sin hotel_id editable.'
    );
}

$pdo = null;
$database = '';
$schemaReady = false;

if (!is_file($configPath)) {
    ampError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
        ampOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        ampError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $schemaReady = true;
    $requiredSchema = [
        'movimientos_caja' => [
            'id',
            'hotel_id',
            'tipo',
            'categoria',
            'monto',
            'metodo_pago',
            'referencia',
            'corte_id',
            'created_at',
        ],
        'cortes_caja' => [
            'id',
            'hotel_id',
            'caja_id',
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
            'estado',
        ],
        'cajas' => ['id', 'hotel_id', 'nombre', 'activa'],
        'categorias_movimientos' => ['id', 'nombre', 'tipo', 'activa'],
    ];

    ampSection('Esquema requerido');
    foreach ($requiredSchema as $table => $columns) {
        if (!ampTableExists($pdo, $database, $table)) {
            $schemaReady = false;
            ampError('Tabla requerida faltante: ' . $table . '.', 'No avanzar con 9C-A hasta restaurar el esquema de Caja.');
            continue;
        }

        $missing = [];
        foreach ($columns as $column) {
            if (!ampColumnExists($pdo, $database, $table, $column)) {
                $missing[] = $column;
            }
        }

        if ($missing) {
            $schemaReady = false;
            ampError(
                'Tabla ' . $table . ' incompleta. Faltan columnas: ' . implode(', ', $missing) . '.',
                'Reconciliar esquema antes de diagnosticar arqueo por metodo.'
            );
        } else {
            ampOk('Tabla ' . $table . ' disponible con columnas requeridas.');
        }
    }

    if ($schemaReady) {
        ampSection('Resumen cortes y movimientos');
        ampMetric('Cortes totales', ampCount($pdo, 'SELECT COUNT(*) FROM cortes_caja'));
        ampMetric('Cortes abiertos', ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'abierto'"));
        ampMetric('Cortes cerrados', ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'cerrado'"));
        ampMetric('Cortes cancelados', ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'cancelado'"));
        ampMetric('Movimientos Caja totales', ampCount($pdo, 'SELECT COUNT(*) FROM movimientos_caja'));
        ampMetric('Movimientos ingreso', ampCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'ingreso'"));
        ampMetric('Movimientos gasto', ampCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'gasto'"));
        ampMetric('Metodos distintos en movimientos', ampCount($pdo, "SELECT COUNT(DISTINCT COALESCE(NULLIF(metodo_pago, ''), '(vacio)')) FROM movimientos_caja"));
        ampMoneyMetric('Importe ingresos Caja', ampScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE tipo = 'ingreso'"));
        ampMoneyMetric('Importe gastos Caja', ampScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja WHERE tipo = 'gasto'"));
        ampMoneyMetric('Neto Caja por movimientos', ampScalar($pdo, "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) FROM movimientos_caja"));

        ampSection('Resumen por metodo');
        $methodStmt = $pdo->query(
            "SELECT metodo_pago,
                    SUM(CASE WHEN tipo = 'ingreso' THEN 1 ELSE 0 END) AS ingresos_count,
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS ingresos_total,
                    SUM(CASE WHEN tipo = 'gasto' THEN 1 ELSE 0 END) AS gastos_count,
                    COALESCE(SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END), 0) AS gastos_total
             FROM movimientos_caja
             GROUP BY metodo_pago
             ORDER BY metodo_pago"
        );
        $methods = $methodStmt ? $methodStmt->fetchAll() : [];
        if ($methods) {
            foreach ($methods as $row) {
                $method = (string)($row['metodo_pago'] ?? '(sin metodo)');
                ampOk(
                    'Metodo ' . $method . ': ingresos ' . (int)$row['ingresos_count'] .
                    ' / ' . ampMoney($row['ingresos_total']) .
                    ', gastos ' . (int)$row['gastos_count'] .
                    ' / ' . ampMoney($row['gastos_total']) . '.'
                );
            }
        } else {
            ampWarning('No se encontraron movimientos para agrupar por metodo.', 'Confirmar si la base local tiene datos de Caja.');
        }

        ampSection('Alertas estructurales');
        ampReportZero(
            'movimientos sin metodo_pago',
            ampCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE metodo_pago IS NULL OR metodo_pago = ''"),
            'Todo movimiento de Caja debe conservar metodo de pago.'
        );
        ampReportZero(
            'movimientos sin corte_id',
            ampCount($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE corte_id IS NULL'),
            'Todo movimiento de Caja debe conservar corte.'
        );
        ampReportZero(
            'movimientos con corte inexistente o de otro hotel',
            ampCount(
                $pdo,
                'SELECT COUNT(*)
                 FROM movimientos_caja mc
                 LEFT JOIN cortes_caja cc
                   ON cc.id = mc.corte_id
                  AND cc.hotel_id = mc.hotel_id
                 WHERE mc.corte_id IS NULL
                    OR cc.id IS NULL'
            ),
            'Revisar movimientos cuyo corte no pertenece al mismo hotel.'
        );
        ampReportZero(
            'cortes sin caja valida del mismo hotel',
            ampCount(
                $pdo,
                'SELECT COUNT(*)
                 FROM cortes_caja cc
                 LEFT JOIN cajas c
                   ON c.id = cc.caja_id
                 WHERE c.id IS NULL
                    OR (c.hotel_id IS NOT NULL AND cc.hotel_id IS NOT NULL AND c.hotel_id <> cc.hotel_id)'
            ),
            'Revisar cortes con caja faltante o cruzada.'
        );
        ampReportZero(
            'movimientos con tipo invalido',
            ampCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo NOT IN ('ingreso', 'gasto') OR tipo IS NULL"),
            'Solo se admiten tipos ingreso/gasto en movimientos de Caja.'
        );
        ampReportZero(
            'movimientos con monto no positivo',
            ampCount($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE monto <= 0 OR monto IS NULL'),
            'Revisar movimientos con importes nulos, cero o negativos.'
        );
        ampReportZero(
            'movimientos con metodo fuera de enum esperado',
            ampCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE metodo_pago NOT IN ('efectivo', 'tarjeta', 'transferencia') OR metodo_pago IS NULL"),
            'Normalizar metodos antes de un reporte operativo por metodo.'
        );
        ampReportZero(
            'cortes con estado invalido',
            ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado NOT IN ('abierto', 'cerrado', 'cancelado') OR estado IS NULL"),
            'Revisar estados de corte antes de diagnosticar arqueo.'
        );
        ampReportZero(
            'cortes cerrados sin fecha_cierre',
            ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'cerrado' AND fecha_cierre IS NULL"),
            'Un corte cerrado debe conservar fecha de cierre.'
        );
        ampReportZero(
            'cortes abiertos con fecha_cierre',
            ampCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'abierto' AND fecha_cierre IS NOT NULL"),
            'Un corte abierto no debe tener fecha de cierre.'
        );

        ampSection('Alertas de arqueo cerrado');
        ampReportZero(
            'cortes cerrados con totales guardados distintos a movimientos',
            ampCount(
                $pdo,
                "SELECT COUNT(*)
                 FROM (
                    SELECT cc.id,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_efectivo,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_tarjeta,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_ing_transferencia,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_efectivo,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'tarjeta' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_tarjeta,
                           ROUND(COALESCE(SUM(CASE WHEN mc.tipo = 'gasto' AND mc.metodo_pago = 'transferencia' THEN mc.monto ELSE 0 END), 0), 2) AS mov_gasto_transferencia,
                           COALESCE(cc.total_ingresos_efectivo, 0) AS corte_ing_efectivo,
                           COALESCE(cc.total_ingresos_tarjeta, 0) AS corte_ing_tarjeta,
                           COALESCE(cc.total_ingresos_transferencia, 0) AS corte_ing_transferencia,
                           COALESCE(cc.total_gastos_efectivo, 0) AS corte_gasto_efectivo,
                           COALESCE(cc.total_gastos_tarjeta, 0) AS corte_gasto_tarjeta,
                           COALESCE(cc.total_gastos_transferencia, 0) AS corte_gasto_transferencia
                    FROM cortes_caja cc
                    LEFT JOIN movimientos_caja mc
                      ON mc.corte_id = cc.id
                     AND mc.hotel_id = cc.hotel_id
                    WHERE cc.estado = 'cerrado'
                    GROUP BY cc.id,
                             cc.total_ingresos_efectivo,
                             cc.total_ingresos_tarjeta,
                             cc.total_ingresos_transferencia,
                             cc.total_gastos_efectivo,
                             cc.total_gastos_tarjeta,
                             cc.total_gastos_transferencia
                    HAVING ABS(mov_ing_efectivo - corte_ing_efectivo) > 0.01
                        OR ABS(mov_ing_tarjeta - corte_ing_tarjeta) > 0.01
                        OR ABS(mov_ing_transferencia - corte_ing_transferencia) > 0.01
                        OR ABS(mov_gasto_efectivo - corte_gasto_efectivo) > 0.01
                        OR ABS(mov_gasto_tarjeta - corte_gasto_tarjeta) > 0.01
                        OR ABS(mov_gasto_transferencia - corte_gasto_transferencia) > 0.01
                 ) x"
            ),
            'Revisar diferencias historicas antes de usar arqueo operativo como fuente de decision.',
            true
        );
        ampReportZero(
            'cortes cerrados con efectivo_esperado distinto a formula guardada',
            ampCount(
                $pdo,
                "SELECT COUNT(*)
                 FROM (
                    SELECT cc.id,
                           ROUND(COALESCE(cc.monto_inicial, 0) + COALESCE(cc.total_ingresos_efectivo, 0) - COALESCE(cc.total_gastos_efectivo, 0), 2) AS efectivo_calculado,
                           ROUND(COALESCE(cc.efectivo_esperado, 0), 2) AS efectivo_guardado
                    FROM cortes_caja cc
                    WHERE cc.estado = 'cerrado'
                    HAVING ABS(efectivo_calculado - efectivo_guardado) > 0.01
                 ) x"
            ),
            'Revisar efectivo esperado historico; no recalcular automaticamente.',
            true
        );
        ampReportZero(
            'cortes cerrados con diferencia distinta a efectivo_contado menos esperado',
            ampCount(
                $pdo,
                "SELECT COUNT(*)
                 FROM (
                    SELECT cc.id,
                           ROUND(COALESCE(cc.efectivo_contado, 0) - COALESCE(cc.efectivo_esperado, 0), 2) AS diferencia_calculada,
                           ROUND(COALESCE(cc.diferencia, 0), 2) AS diferencia_guardada
                    FROM cortes_caja cc
                    WHERE cc.estado = 'cerrado'
                    HAVING ABS(diferencia_calculada - diferencia_guardada) > 0.01
                 ) x"
            ),
            'Revisar diferencias guardadas en cortes cerrados.',
            true
        );

        ampSection('Cortes abiertos');
        ampMetric(
            'Cortes abiertos con movimientos',
            ampCount(
                $pdo,
                "SELECT COUNT(DISTINCT cc.id)
                 FROM cortes_caja cc
                 INNER JOIN movimientos_caja mc
                   ON mc.corte_id = cc.id
                  AND mc.hotel_id = cc.hotel_id
                 WHERE cc.estado = 'abierto'"
            )
        );
        ampMoneyMetric(
            'Neto actual en cortes abiertos',
            ampScalar(
                $pdo,
                "SELECT COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE -mc.monto END), 0)
                 FROM movimientos_caja mc
                 INNER JOIN cortes_caja cc
                   ON cc.id = mc.corte_id
                  AND cc.hotel_id = mc.hotel_id
                 WHERE cc.estado = 'abierto'"
            )
        );
    } else {
        ampWarning('Se omite arqueo porque el esquema requerido no esta completo.', 'Completar esquema antes de interpretar resultados 9C-A.');
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        ampOk('Transaccion read-only cerrada con rollback sin escrituras.');
    }
}

ampPrintSummary();
exit($errors > 0 ? 1 : 0);
