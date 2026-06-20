<?php
/**
 * Preflight Fase 9A-A - Conciliacion financiera operativa read-only.
 *
 * Cruza CxC, CxP y Caja para detectar diferencias sin modificar datos.
 * No crea rutas, migraciones, pagos, cobros, reversiones ni ajustes.
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

function cfinLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cfinOk(string $message): void
{
    global $ok;
    $ok++;
    cfinLine('OK', $message);
}

function cfinWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    cfinLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cfinError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    cfinLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cfinSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function cfinQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function cfinTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function cfinColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function cfinScalar(PDO $pdo, string $sql)
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function cfinCount(PDO $pdo, string $sql): ?int
{
    $value = cfinScalar($pdo, $sql);

    return $value === null ? null : (int)$value;
}

function cfinMoney($value): string
{
    return '$' . number_format((float)$value, 2, '.', ',');
}

function cfinMetric(string $label, $value, string $prefix = ''): void
{
    if ($value === null) {
        cfinWarning('No se pudo calcular metrica: ' . $label . '.', 'Revisar manualmente la consulta read-only de conciliacion.');
        return;
    }

    cfinOk($label . ': ' . $prefix . (string)$value . '.');
}

function cfinMoneyMetric(string $label, $value): void
{
    if ($value === null) {
        cfinWarning('No se pudo calcular importe: ' . $label . '.', 'Revisar manualmente la consulta read-only de conciliacion.');
        return;
    }

    cfinOk($label . ': ' . cfinMoney($value) . '.');
}

function cfinReportZero(string $label, ?int $count, string $recommendation, bool $warningOnly = false): void
{
    if ($count === null) {
        cfinWarning('No se pudo validar conciliacion: ' . $label . '.', 'Revisar manualmente antes de exponer conciliacion financiera.');
        return;
    }

    if ($count === 0) {
        cfinOk('Conciliacion OK: ' . $label . ' = 0.');
        return;
    }

    if ($warningOnly) {
        cfinWarning('Conciliacion pendiente de revisar: ' . $label . ' = ' . (string)$count . '.', $recommendation);
        return;
    }

    cfinError('Conciliacion fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function cfinReportAtLeast(string $label, ?int $actual, ?int $minimum, string $recommendation, bool $warningOnly = true): void
{
    if ($actual === null || $minimum === null) {
        cfinWarning('No se pudo validar auditoria: ' . $label . '.', 'Revisar logs_auditoria manualmente.');
        return;
    }

    if ($actual >= $minimum) {
        cfinOk('Auditoria OK: ' . $label . ' = ' . (string)$actual . ' de ' . (string)$minimum . ' esperados.');
        return;
    }

    $message = 'Auditoria incompleta: ' . $label . ' = ' . (string)$actual . ' de ' . (string)$minimum . ' esperados.';
    if ($warningOnly) {
        cfinWarning($message, $recommendation);
    } else {
        cfinError($message, $recommendation);
    }
}

function cfinReadSource(string $path, string $label): ?string
{
    if (!is_file($path)) {
        cfinError('No se encontro ' . $label . '.', 'Restaurar el archivo requerido para la pantalla read-only 9A-B-A.');
        return null;
    }

    $code = file_get_contents($path);
    if ($code === false) {
        cfinError('No se pudo leer ' . $label . '.', 'Revisar permisos del archivo.');
        return null;
    }

    cfinOk($label . ' disponible para validacion estatica.');
    return $code;
}

function cfinHasRoute(string $routesCode, string $method, string $uri, string $controller, string $action): bool
{
    $pattern = '/\$router->' . preg_quote(strtolower($method), '/') .
        '\(\s*[\'"]' . preg_quote($uri, '/') . '[\'"]\s*,\s*\[\s*' .
        '[\'"]controller[\'"]\s*=>\s*[\'"]' . preg_quote($controller, '/') . '[\'"]\s*,\s*' .
        '[\'"]action[\'"]\s*=>\s*[\'"]' . preg_quote($action, '/') . '[\'"]\s*' .
        '\]\s*\)/';

    return preg_match($pattern, $routesCode) === 1;
}

function cfinHasRouteMethod(string $routesCode, string $method, string $uri): bool
{
    $pattern = '/\$router->' . preg_quote(strtolower($method), '/') .
        '\(\s*[\'"]' . preg_quote($uri, '/') . '[\'"]/';

    return preg_match($pattern, $routesCode) === 1;
}

function cfinHasSqlWriteToken(string $code): bool
{
    return preg_match('/\b(INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|CREATE|TRUNCATE)\b/i', $code) === 1;
}

function cfinAssertContains(?string $code, string $needle, string $okMessage, string $errorMessage, string $recommendation): void
{
    if ($code === null) {
        return;
    }

    if (strpos($code, $needle) !== false) {
        cfinOk($okMessage);
        return;
    }

    cfinError($errorMessage, $recommendation);
}

function cfinPrintSummary(): void
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
    cfinOk('APP_ENV local confirmado para preflight read-only.');
} else {
    cfinError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : (string)$appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/OperacionController.php';
$modelPath = $appRoot . '/app/models/ConciliacionFinanciera.php';
$viewPath = $appRoot . '/app/views/operacion/conciliacion_financiera.php';
$dailyViewPath = $appRoot . '/app/views/operacion/diaria.php';

echo "Preflight Fase 9A-A/9A-B-A - Conciliacion financiera read-only\n";
echo "=====================================================\n";

cfinSection('Contrato fuente 9A-B-A');

$routesCode = cfinReadSource($routesPath, 'config/routes.php');
if ($routesCode !== null) {
    if (cfinHasRoute($routesCode, 'get', '/operacion/conciliacion-financiera', 'Operacion', 'conciliacionFinanciera')) {
        cfinOk('Ruta GET /operacion/conciliacion-financiera apunta a Operacion::conciliacionFinanciera.');
    } else {
        cfinError(
            'No se encontro la ruta GET esperada para conciliacion financiera.',
            'Restaurar la ruta GET read-only antes de validar 9A-B-A.'
        );
    }

    if (cfinHasRouteMethod($routesCode, 'post', '/operacion/conciliacion-financiera')) {
        cfinError(
            'La pantalla 9A-B-A no debe exponer ruta POST propia.',
            'Eliminar cualquier POST de conciliacion financiera; este paso es solo lectura.'
        );
    } else {
        cfinOk('No existe POST para /operacion/conciliacion-financiera.');
    }
}

$controllerCode = cfinReadSource($controllerPath, 'OperacionController.php');
cfinAssertContains(
    $controllerCode,
    'conciliacionFinancieraAction',
    'Controlador expone conciliacionFinancieraAction.',
    'OperacionController no expone conciliacionFinancieraAction.',
    'Restaurar accion GET read-only de conciliacion financiera.'
);
cfinAssertContains(
    $controllerCode,
    'reporteReadOnlyPorHotel',
    'Controlador consume el lector read-only del modelo.',
    'OperacionController no llama al lector read-only de conciliacion.',
    'Usar ConciliacionFinanciera::reporteReadOnlyPorHotel sin escrituras.'
);
cfinAssertContains(
    $controllerCode,
    "View::renderTemplate('operacion/conciliacion_financiera'",
    'Controlador renderiza la vista read-only esperada.',
    'OperacionController no renderiza operacion/conciliacion_financiera.',
    'Restaurar la vista de matriz de conciliacion 9A-B-A.'
);
if ($controllerCode !== null) {
    if (cfinHasSqlWriteToken($controllerCode)) {
        cfinError('OperacionController contiene tokens SQL de escritura.', 'Mantener la pantalla 9A-B-A sin escrituras SQL.');
    } else {
        cfinOk('OperacionController sin tokens SQL de escritura para 9A-B-A.');
    }
}

$modelCode = cfinReadSource($modelPath, 'ConciliacionFinanciera.php');
cfinAssertContains(
    $modelCode,
    'class ConciliacionFinanciera',
    'Modelo ConciliacionFinanciera declarado.',
    'No se encontro la clase ConciliacionFinanciera.',
    'Restaurar el modelo read-only de conciliacion financiera.'
);
cfinAssertContains(
    $modelCode,
    'reporteReadOnlyPorHotel',
    'Modelo expone reporteReadOnlyPorHotel.',
    'El modelo no expone reporteReadOnlyPorHotel.',
    'Mantener una unica entrada read-only para la pantalla 9A-B-A.'
);
cfinAssertContains(
    $modelCode,
    'START TRANSACTION READ ONLY',
    'Modelo abre transaccion READ ONLY.',
    'El modelo no declara START TRANSACTION READ ONLY.',
    'La lectura debe ejecutarse dentro de transaccion read-only.'
);
cfinAssertContains(
    $modelCode,
    'rollBack',
    'Modelo cierra la transaccion con rollback.',
    'El modelo no evidencia rollback de cierre.',
    'Cerrar la transaccion read-only con rollback.'
);
if ($modelCode !== null) {
    if (cfinHasSqlWriteToken($modelCode)) {
        cfinError('ConciliacionFinanciera contiene tokens SQL de escritura.', 'Eliminar escrituras; 9A-B-A solo puede consultar datos.');
    } else {
        cfinOk('ConciliacionFinanciera sin tokens SQL de escritura.');
    }
}

$viewCode = cfinReadSource($viewPath, 'operacion/conciliacion_financiera.php');
cfinAssertContains(
    $viewCode,
    'method="get"',
    'Vista usa formulario GET para filtros.',
    'La vista de conciliacion no usa formulario GET.',
    'Los filtros de 9A-B-A deben ser querystring y no POST.'
);
if ($viewCode !== null) {
    if (preg_match('/method\s*=\s*[\'"]post[\'"]/i', $viewCode) === 1) {
        cfinError('La vista de conciliacion contiene method POST.', 'Eliminar POST de la pantalla read-only 9A-B-A.');
    } else {
        cfinOk('Vista de conciliacion sin formularios POST.');
    }

    if (strpos($viewCode, '--ms-') !== false) {
        cfinError('La vista mezcla tokens --ms-* en area hotelera.', 'Usar solo tokens --brand-* para el sistema operativo del hotel.');
    } else {
        cfinOk('Vista sin tokens --ms-* en area hotelera.');
    }

    if (strpos($viewCode, '--brand-') !== false) {
        cfinOk('Vista usa tokens --brand-* del hotel.');
    } else {
        cfinWarning('No se detectaron tokens --brand-* en la vista.', 'Revisar branding hotelero antes de QA visual.');
    }
}

$dailyViewCode = cfinReadSource($dailyViewPath, 'operacion/diaria.php');
cfinAssertContains(
    $dailyViewCode,
    "url('operacion/conciliacion-financiera')",
    'Operacion diaria enlaza la pantalla de conciliacion.',
    'Operacion diaria no enlaza conciliacion financiera.',
    'Agregar enlace de navegacion hacia la pantalla 9A-B-A.'
);

$pdo = null;
$database = '';
$schemaReady = false;

if (!is_file($configPath)) {
    cfinError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
        cfinOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        cfinError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $schemaReady = true;
    $requiredSchema = [
        'hoteles' => ['id', 'nombre', 'activo'],
        'cuentas_por_cobrar' => ['id', 'hotel_id', 'estado', 'total', 'saldo', 'reservacion_id'],
        'cuentas_por_cobrar_movimientos' => ['id', 'hotel_id', 'cuenta_por_cobrar_id', 'tipo_movimiento', 'monto', 'saldo_anterior', 'saldo_posterior', 'referencia'],
        'cuentas_por_pagar' => ['id', 'hotel_id', 'proveedor_id', 'compra_id', 'estado', 'total', 'saldo'],
        'cuentas_por_pagar_movimientos' => ['id', 'hotel_id', 'cuenta_por_pagar_id', 'tipo_movimiento', 'monto', 'saldo_anterior', 'saldo_posterior', 'referencia'],
        'movimientos_caja' => ['id', 'hotel_id', 'tipo', 'categoria', 'monto', 'referencia', 'corte_id'],
        'cortes_caja' => ['id', 'hotel_id', 'caja_id', 'estado'],
        'cajas' => ['id', 'hotel_id', 'activa'],
        'logs_auditoria' => ['id', 'hotel_id', 'accion', 'entidad_tipo', 'entidad_id'],
    ];

    cfinSection('Esquema requerido');
    foreach ($requiredSchema as $table => $columns) {
        if (!cfinTableExists($pdo, $database, $table)) {
            $schemaReady = false;
            cfinError('Tabla requerida faltante: ' . $table . '.', 'No avanzar con 9A-A hasta restaurar el esquema financiero.');
            continue;
        }

        $missing = [];
        foreach ($columns as $column) {
            if (!cfinColumnExists($pdo, $database, $table, $column)) {
                $missing[] = $column;
            }
        }

        if ($missing) {
            $schemaReady = false;
            cfinError(
                'Tabla ' . $table . ' incompleta. Faltan columnas: ' . implode(', ', $missing) . '.',
                'Reconciliar esquema antes de ejecutar conciliacion financiera.'
            );
        } else {
            cfinOk('Tabla ' . $table . ' disponible con columnas requeridas.');
        }
    }

    $contextTables = ['reservaciones', 'compras', 'proveedores'];
    foreach ($contextTables as $table) {
        if (cfinTableExists($pdo, $database, $table)) {
            cfinOk('Tabla de contexto disponible: ' . $table . '.');
        } else {
            cfinWarning('Tabla de contexto no disponible: ' . $table . '.', 'La conciliacion puede ejecutarse sin contexto, pero enlaces de origen quedan limitados.');
        }
    }

    if ($schemaReady) {
        cfinSection('Resumen CxC');
        cfinMetric('CxC totales', cfinScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar'));
        cfinMetric(
            'CxC abiertas con saldo',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar WHERE estado IN ('pendiente', 'parcial', 'vencida') AND saldo > 0")
        );
        cfinMetric(
            'CxC liquidadas o saldo cero',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar WHERE estado = 'liquidada' OR saldo = 0")
        );
        cfinMoneyMetric(
            'Saldo CxC pendiente no cancelado',
            cfinScalar($pdo, "SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_cobrar WHERE estado NOT IN ('cancelada', 'incobrable')")
        );
        cfinMetric(
            'Movimientos CxC COBRO',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'COBRO'")
        );
        cfinMoneyMetric(
            'Importe CxC COBRO',
            cfinScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'COBRO'")
        );
        cfinMetric(
            'Movimientos CxC reversion',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%'")
        );
        cfinMoneyMetric(
            'Importe CxC reversion',
            cfinScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%'")
        );

        cfinSection('Resumen CxP');
        cfinMetric('CxP totales', cfinScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_pagar'));
        cfinMetric(
            'CxP abiertas con saldo',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar WHERE estado IN ('pendiente', 'parcial', 'vencida') AND saldo > 0")
        );
        cfinMetric(
            'CxP pagadas o saldo cero',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar WHERE estado = 'pagada' OR saldo = 0")
        );
        cfinMoneyMetric(
            'Saldo CxP pendiente no cancelado',
            cfinScalar($pdo, "SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_pagar WHERE estado <> 'cancelada'")
        );
        cfinMetric(
            'Movimientos CxP PAGO_REFERENCIAL',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'PAGO_REFERENCIAL'")
        );
        cfinMoneyMetric(
            'Importe CxP PAGO_REFERENCIAL',
            cfinScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'PAGO_REFERENCIAL'")
        );
        cfinMetric(
            'Movimientos CxP reversion',
            cfinScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%'")
        );
        cfinMoneyMetric(
            'Importe CxP reversion',
            cfinScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%'")
        );

        cfinSection('Resumen Caja financiera');
        cfinMetric(
            'Movimientos Caja vinculados a CxC',
            cfinScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE categoria IN ('Cobro CxC', 'Reversion Cobro CxC')")
        );
        cfinMoneyMetric(
            'Importe Caja vinculado a CxC',
            cfinScalar($pdo, "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) FROM movimientos_caja WHERE categoria IN ('Cobro CxC', 'Reversion Cobro CxC')")
        );
        cfinMetric(
            'Movimientos Caja vinculados a CxP',
            cfinScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE categoria IN ('Pago proveedor', 'Reversion Pago proveedor')")
        );
        cfinMoneyMetric(
            'Importe Caja vinculado a CxP',
            cfinScalar($pdo, "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END), 0) FROM movimientos_caja WHERE categoria IN ('Pago proveedor', 'Reversion Pago proveedor')")
        );
        cfinMetric(
            'Movimientos financieros en cortes abiertos',
            cfinScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja mc INNER JOIN cortes_caja cc ON cc.id = mc.corte_id AND cc.hotel_id = mc.hotel_id WHERE mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND cc.estado = 'abierto'")
        );
        cfinMetric(
            'Movimientos financieros en cortes cerrados',
            cfinScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja mc INNER JOIN cortes_caja cc ON cc.id = mc.corte_id AND cc.hotel_id = mc.hotel_id WHERE mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND cc.estado = 'cerrado'")
        );

        cfinSection('Alertas CxC vs Caja');
        cfinReportZero(
            'CxC con saldo fuera de rango',
            cfinCount($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar WHERE saldo < 0 OR total < 0 OR saldo > total'),
            'Revisar saldos CxC antes de permitir nuevas operaciones.'
        );
        cfinReportZero(
            'CxC liquidada con saldo positivo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar WHERE estado = 'liquidada' AND saldo > 0"),
            'Revisar estados CxC antes de exponer conciliacion operativa.'
        );
        cfinReportZero(
            'movimientos CxC sin cuenta del mismo hotel',
            cfinCount($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN cuentas_por_cobrar c ON c.id = m.cuenta_por_cobrar_id AND c.hotel_id = m.hotel_id WHERE c.id IS NULL'),
            'Corregir trazabilidad CxC antes de operar Caja.'
        );
        cfinReportZero(
            'movimientos CxC financieros con saldos invalidos',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento IN ('COBRO', 'CANCELACION') AND (monto <= 0 OR saldo_anterior < 0 OR saldo_posterior < 0)"),
            'Revisar movimientos CxC con importes o saldos invalidos.'
        );
        cfinReportZero(
            'movimientos CxC COBRO que incrementan saldo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'COBRO' AND saldo_anterior < saldo_posterior"),
            'Un COBRO CxC debe disminuir o mantener el saldo, nunca incrementarlo.'
        );
        cfinReportZero(
            'reversiones CxC que disminuyen saldo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' AND saldo_anterior > saldo_posterior"),
            'Una reversion de cobro CxC debe restaurar saldo.'
        );
        cfinReportZero(
            'movimientos CxC COBRO sin ingreso Caja asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'ingreso' AND mc.categoria = 'Cobro CxC' AND mc.monto = m.monto AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE m.tipo_movimiento = 'COBRO' AND mc.id IS NULL"),
            'Cada COBRO CxC debe tener ingreso Caja asociado.'
        );
        cfinReportZero(
            'ingresos Caja Cobro CxC sin COBRO asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_cobrar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'COBRO' AND m.monto = mc.monto AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE mc.tipo = 'ingreso' AND mc.categoria = 'Cobro CxC' AND m.id IS NULL"),
            'Todo ingreso Caja Cobro CxC debe apuntar a movimiento CxC COBRO.'
        );
        cfinReportZero(
            'movimientos CxC CANCELACION sin gasto Caja asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'gasto' AND mc.categoria = 'Reversion Cobro CxC' AND mc.monto = m.monto AND mc.referencia = m.referencia WHERE m.tipo_movimiento = 'CANCELACION' AND m.referencia LIKE 'REV-CXC-%' AND mc.id IS NULL"),
            'Cada reversion CxC debe tener gasto Caja asociado.'
        );
        cfinReportZero(
            'gastos Caja Reversion Cobro CxC sin CANCELACION asociada',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_cobrar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'CANCELACION' AND m.monto = mc.monto AND m.referencia = mc.referencia WHERE mc.tipo = 'gasto' AND mc.categoria = 'Reversion Cobro CxC' AND m.id IS NULL"),
            'Todo gasto Caja de reversion CxC debe apuntar a una CANCELACION CxC.'
        );
        cfinReportZero(
            'cobros CxC con doble reversion',
            cfinCount($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, cuenta_por_cobrar_id, referencia, COUNT(*) AS total FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' GROUP BY hotel_id, cuenta_por_cobrar_id, referencia HAVING COUNT(*) > 1) x"),
            'Bloquear nuevas reversiones CxC hasta reconciliar duplicados.'
        );
        cfinReportZero(
            'referencias REV-CXC con formato inesperado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' AND referencia NOT REGEXP '^REV-CXC-[0-9]+-MOV-[0-9]+$'"),
            'Normalizar o revisar referencias de reversion CxC antes de UI.'
        );

        cfinSection('Alertas CxP vs Caja');
        cfinReportZero(
            'CxP con saldo fuera de rango',
            cfinCount($pdo, 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE saldo < 0 OR total < 0 OR saldo > total'),
            'Revisar saldos CxP antes de permitir nuevos pagos.'
        );
        cfinReportZero(
            'CxP pagada con saldo positivo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar WHERE estado = 'pagada' AND saldo > 0"),
            'Revisar estados CxP antes de exponer conciliacion operativa.'
        );
        cfinReportZero(
            'movimientos CxP sin cuenta del mismo hotel',
            cfinCount($pdo, 'SELECT COUNT(*) FROM cuentas_por_pagar_movimientos m LEFT JOIN cuentas_por_pagar c ON c.id = m.cuenta_por_pagar_id AND c.hotel_id = m.hotel_id WHERE c.id IS NULL'),
            'Corregir trazabilidad CxP antes de operar Caja.'
        );
        cfinReportZero(
            'movimientos CxP financieros con saldos invalidos',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento IN ('PAGO_REFERENCIAL', 'CANCELACION') AND (monto <= 0 OR saldo_anterior < 0 OR saldo_posterior < 0)"),
            'Revisar movimientos CxP con importes o saldos invalidos.'
        );
        cfinReportZero(
            'movimientos CxP PAGO_REFERENCIAL que incrementan saldo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'PAGO_REFERENCIAL' AND saldo_anterior < saldo_posterior"),
            'Un pago proveedor debe disminuir o mantener el saldo, nunca incrementarlo.'
        );
        cfinReportZero(
            'reversiones CxP que disminuyen saldo',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' AND saldo_anterior > saldo_posterior"),
            'Una reversion de pago proveedor debe restaurar saldo.'
        );
        cfinReportZero(
            'movimientos CxP PAGO_REFERENCIAL sin gasto Caja asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'gasto' AND mc.categoria = 'Pago proveedor' AND mc.monto = m.monto AND (mc.referencia = CONCAT('CXP-', m.cuenta_por_pagar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE m.tipo_movimiento = 'PAGO_REFERENCIAL' AND mc.id IS NULL"),
            'Cada PAGO_REFERENCIAL debe tener gasto Caja asociado.'
        );
        cfinReportZero(
            'gastos Caja Pago proveedor sin PAGO_REFERENCIAL asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_pagar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'PAGO_REFERENCIAL' AND m.monto = mc.monto AND (mc.referencia = CONCAT('CXP-', m.cuenta_por_pagar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE mc.tipo = 'gasto' AND mc.categoria = 'Pago proveedor' AND m.id IS NULL"),
            'Todo gasto Caja Pago proveedor debe apuntar a movimiento CxP PAGO_REFERENCIAL.'
        );
        cfinReportZero(
            'movimientos CxP CANCELACION sin ingreso Caja asociado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'ingreso' AND mc.categoria = 'Reversion Pago proveedor' AND mc.monto = m.monto AND mc.referencia = m.referencia WHERE m.tipo_movimiento = 'CANCELACION' AND m.referencia LIKE 'REV-CXP-%' AND mc.id IS NULL"),
            'Cada reversion CxP debe tener ingreso Caja asociado.'
        );
        cfinReportZero(
            'ingresos Caja Reversion Pago proveedor sin CANCELACION asociada',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_pagar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'CANCELACION' AND m.monto = mc.monto AND m.referencia = mc.referencia WHERE mc.tipo = 'ingreso' AND mc.categoria = 'Reversion Pago proveedor' AND m.id IS NULL"),
            'Todo ingreso Caja de reversion CxP debe apuntar a una CANCELACION CxP.'
        );
        cfinReportZero(
            'pagos proveedor con doble reversion',
            cfinCount($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, cuenta_por_pagar_id, referencia, COUNT(*) AS total FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' GROUP BY hotel_id, cuenta_por_pagar_id, referencia HAVING COUNT(*) > 1) x"),
            'Bloquear nuevas reversiones CxP hasta reconciliar duplicados.'
        );
        cfinReportZero(
            'referencias REV-CXP con formato inesperado',
            cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXP-%' AND referencia NOT REGEXP '^REV-CXP-[0-9]+-MOV-[0-9]+$'"),
            'Normalizar o revisar referencias de reversion CxP antes de UI.'
        );

        cfinSection('Alertas Caja transversal');
        cfinReportZero(
            'movimientos Caja financieros con tipo/categoria inesperada',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE (categoria = 'Cobro CxC' AND tipo <> 'ingreso') OR (categoria = 'Reversion Cobro CxC' AND tipo <> 'gasto') OR (categoria = 'Pago proveedor' AND tipo <> 'gasto') OR (categoria = 'Reversion Pago proveedor' AND tipo <> 'ingreso')"),
            'Revisar movimientos Caja con categoria financiera y tipo incompatible.'
        );
        cfinReportZero(
            'movimientos Caja financieros sin hotel_id',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND hotel_id IS NULL"),
            'No operar conciliacion financiera con movimientos Caja sin hotel_id.'
        );
        cfinReportZero(
            'movimientos Caja financieros sin corte valido del mismo hotel',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cortes_caja cc ON cc.id = mc.corte_id AND cc.hotel_id = mc.hotel_id WHERE mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND (mc.corte_id IS NULL OR cc.id IS NULL)"),
            'Todo movimiento financiero controlado debe conservar corte de Caja del mismo hotel.'
        );
        cfinReportZero(
            'cortes financieros con caja de otro hotel',
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja mc INNER JOIN cortes_caja cc ON cc.id = mc.corte_id INNER JOIN cajas c ON c.id = cc.caja_id WHERE mc.categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND c.hotel_id IS NOT NULL AND cc.hotel_id IS NOT NULL AND c.hotel_id <> cc.hotel_id"),
            'Revisar cajas/cortes con hotel_id cruzado antes de nuevas operaciones.'
        );
        cfinReportZero(
            'referencias Caja financieras duplicadas',
            cfinCount($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, tipo, categoria, referencia, COUNT(*) AS total FROM movimientos_caja WHERE categoria IN ('Cobro CxC', 'Reversion Cobro CxC', 'Pago proveedor', 'Reversion Pago proveedor') AND referencia IS NOT NULL AND referencia <> '' GROUP BY hotel_id, tipo, categoria, referencia HAVING COUNT(*) > 1) x"),
            'Revisar referencias Caja duplicadas antes de automatizar conciliacion.',
            true
        );
        cfinReportZero(
            'referencias CxC COBRO duplicadas por cuenta',
            cfinCount($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, cuenta_por_cobrar_id, referencia, COUNT(*) AS total FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'COBRO' AND referencia IS NOT NULL AND referencia <> '' GROUP BY hotel_id, cuenta_por_cobrar_id, referencia HAVING COUNT(*) > 1) x"),
            'Revisar referencias duplicadas en cobros CxC.',
            true
        );
        cfinReportZero(
            'referencias CxP PAGO_REFERENCIAL duplicadas por cuenta',
            cfinCount($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, cuenta_por_pagar_id, referencia, COUNT(*) AS total FROM cuentas_por_pagar_movimientos WHERE tipo_movimiento = 'PAGO_REFERENCIAL' AND referencia IS NOT NULL AND referencia <> '' GROUP BY hotel_id, cuenta_por_pagar_id, referencia HAVING COUNT(*) > 1) x"),
            'Revisar referencias duplicadas en pagos proveedor.',
            true
        );

        if (cfinTableExists($pdo, $database, 'reservaciones')) {
            cfinReportZero(
                'CxC con reservacion de otro hotel',
                cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar c INNER JOIN reservaciones r ON r.id = c.reservacion_id WHERE c.reservacion_id IS NOT NULL AND r.hotel_id <> c.hotel_id"),
                'Revisar origen reservacion de CxC antes de mostrar conciliacion.'
            );
        }

        if (cfinTableExists($pdo, $database, 'proveedores')) {
            cfinReportZero(
                'CxP con proveedor de otro hotel',
                cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar c INNER JOIN proveedores p ON p.id = c.proveedor_id WHERE p.hotel_id <> c.hotel_id"),
                'Revisar proveedor asociado a CxP antes de nuevos pagos.'
            );
        }

        if (cfinTableExists($pdo, $database, 'compras')) {
            cfinReportZero(
                'CxP con compra de otro hotel',
                cfinCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar c INNER JOIN compras co ON co.id = c.compra_id WHERE c.compra_id IS NOT NULL AND co.hotel_id <> c.hotel_id"),
                'Revisar compra asociada a CxP antes de nuevos pagos.'
            );
        }

        cfinSection('Auditoria esperada');
        cfinReportAtLeast(
            'logs cobro CxC',
            cfinCount($pdo, "SELECT COUNT(*) FROM logs_auditoria WHERE accion = 'cuentas_por_cobrar.cobro_caja_registrado' AND entidad_tipo = 'cuentas_por_cobrar'"),
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'ingreso' AND categoria = 'Cobro CxC'"),
            'Cada cobro CxC persistente debe tener auditoria asociada.'
        );
        cfinReportAtLeast(
            'logs reversion cobro CxC',
            cfinCount($pdo, "SELECT COUNT(*) FROM logs_auditoria WHERE accion = 'cuentas_por_cobrar.cobro_caja_revertido' AND entidad_tipo = 'cuentas_por_cobrar'"),
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'gasto' AND categoria = 'Reversion Cobro CxC'"),
            'Cada reversion CxC persistente debe tener auditoria asociada.'
        );
        cfinReportAtLeast(
            'logs pago proveedor',
            cfinCount($pdo, "SELECT COUNT(*) FROM logs_auditoria WHERE accion = 'cuentas_por_pagar.pago_caja_registrado' AND entidad_tipo = 'cuentas_por_pagar'"),
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'gasto' AND categoria = 'Pago proveedor'"),
            'Cada pago proveedor persistente debe tener auditoria asociada.'
        );
        cfinReportAtLeast(
            'logs reversion pago proveedor',
            cfinCount($pdo, "SELECT COUNT(*) FROM logs_auditoria WHERE accion = 'cuentas_por_pagar.pago_caja_revertido' AND entidad_tipo = 'cuentas_por_pagar'"),
            cfinCount($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE tipo = 'ingreso' AND categoria = 'Reversion Pago proveedor'"),
            'Cada reversion de pago proveedor persistente debe tener auditoria asociada.'
        );
    } else {
        cfinWarning(
            'Se omiten cruces financieros porque el esquema requerido no esta completo.',
            'Completar esquema antes de interpretar resultados de conciliacion.'
        );
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        cfinOk('Transaccion read-only cerrada con rollback sin escrituras.');
    }
}

cfinPrintSummary();
exit($errors > 0 ? 1 : 0);
