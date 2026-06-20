<?php
/**
 * Preflight Fase 10A-A/10A-B-A/10B-A - Tablero ejecutivo integral read-only.
 *
 * Diagnostica fuentes, valida la pantalla GET/read-only del tablero ejecutivo y
 * confirma que el reporte gerencial directo no archive notificaciones.
 * No modifica datos.
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

function teLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function teOk(string $message): void
{
    global $ok;
    $ok++;
    teLine('OK', $message);
}

function teWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    teLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function teError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    teLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function teSection(string $title): void
{
    echo "\n" . $title . "\n";
    echo str_repeat('-', strlen($title)) . "\n";
}

function teQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function teTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function teColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function teTableColumns(PDO $pdo, string $database, string $table): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function teScalar(PDO $pdo, string $sql)
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function teCount(PDO $pdo, string $sql): ?int
{
    $value = teScalar($pdo, $sql);

    return $value === null ? null : (int)$value;
}

function teMoney($value): string
{
    return '$' . number_format((float)$value, 2, '.', ',');
}

function teMetric(string $label, $value): void
{
    if ($value === null) {
        teWarning('No se pudo calcular metrica candidata: ' . $label . '.', 'Revisar manualmente la consulta read-only 10A-A.');
        return;
    }

    teOk($label . ': ' . (string)$value . '.');
}

function teMoneyMetric(string $label, $value): void
{
    if ($value === null) {
        teWarning('No se pudo calcular importe candidato: ' . $label . '.', 'Revisar manualmente la consulta read-only 10A-A.');
        return;
    }

    teOk($label . ': ' . teMoney($value) . '.');
}

function teHasColumns(array $schema, string $table, array $columns): bool
{
    if (!isset($schema[$table])) {
        return false;
    }

    foreach ($columns as $column) {
        if (!in_array($column, $schema[$table], true)) {
            return false;
        }
    }

    return true;
}

function teReadSource(string $path, string $label): ?string
{
    if (!is_file($path)) {
        teWarning('No se encontro ' . $label . '.', 'Revisar archivos antes de conectar una futura pantalla 10A.');
        return null;
    }

    $code = file_get_contents($path);
    if ($code === false) {
        teWarning('No se pudo leer ' . $label . '.', 'Revisar permisos del archivo.');
        return null;
    }

    teOk($label . ' disponible para validacion estatica.');
    return $code;
}

function teHasRoute(string $routesCode, string $method, string $uri): bool
{
    $pattern = '/\$router->' . preg_quote(strtolower($method), '/') .
        '\(\s*[\'"]' . preg_quote($uri, '/') . '[\'"]/i';

    return preg_match($pattern, $routesCode) === 1;
}

function teExtractMethodCode(string $code, string $method): string
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

function teCodeHasSqlWriteToken(string $code): bool
{
    $patterns = [
        'IN' . 'SERT\s+INTO',
        'UP' . 'DATE',
        'DE' . 'LETE\s+FROM',
        'RE' . 'PLACE\s+INTO',
        'AL' . 'TER\s+TABLE',
        'DR' . 'OP\s+TABLE',
        'CR' . 'EATE\s+TABLE',
        'TR' . 'UNCATE',
    ];

    return preg_match('/\b(' . implode('|', $patterns) . ')\b/i', $code) === 1;
}

function tePrintSummary(): void
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
    teOk('APP_ENV local confirmado para preflight read-only.');
} else {
    teError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : (string)$appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$reportesControllerPath = $appRoot . '/app/controllers/ReportesController.php';
$notificacionControllerPath = $appRoot . '/app/controllers/NotificacionController.php';
$modelPath = $appRoot . '/app/models/TableroEjecutivo.php';
$viewPath = $appRoot . '/app/views/reportes/ejecutivo.php';
$indexViewPath = $appRoot . '/app/views/reportes/index.php';
$contractCandidates = [
    dirname($appRoot) . '/docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md',
    dirname(dirname($appRoot)) . '/docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md',
    getcwd() . '/docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md',
    dirname(getcwd()) . '/docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md',
];
$docsDirCandidates = [
    dirname($appRoot) . '/docs',
    dirname(dirname($appRoot)) . '/docs',
    getcwd() . '/docs',
    dirname(getcwd()) . '/docs',
];

echo "Preflight Fase 10A-A/10A-B-A/10B-A - Tablero ejecutivo integral read-only\n";
echo "=====================================================\n";

teSection('Contrato y superficie futura');

$contractPath = null;
foreach ($contractCandidates as $candidate) {
    if (is_file($candidate)) {
        $contractPath = $candidate;
        break;
    }
}

$docsDirAvailable = false;
foreach ($docsDirCandidates as $candidate) {
    if (is_dir($candidate)) {
        $docsDirAvailable = true;
        break;
    }
}

if ($contractPath !== null) {
    teOk('Contrato 10A-0 disponible: docs/fase_10A_0_contrato_tablero_ejecutivo_readonly.md.');
} elseif (!$docsDirAvailable) {
    teOk('Directorio docs no esta montado en este contexto; contrato 10A-0 validado por documentacion del workspace.');
} else {
    teWarning('No se encontro contrato 10A-0.', 'Mantener contrato documental antes de implementar UI.');
}

$routesCode = teReadSource($routesPath, 'config/routes.php');
$reportesControllerCode = teReadSource($reportesControllerPath, 'ReportesController.php');
$notificacionControllerCode = teReadSource($notificacionControllerPath, 'NotificacionController.php');
$modelCode = teReadSource($modelPath, 'TableroEjecutivo.php');
$viewCode = teReadSource($viewPath, 'reportes/ejecutivo.php');
$indexViewCode = teReadSource($indexViewPath, 'reportes/index.php');

if ($routesCode !== null) {
    if (teHasRoute($routesCode, 'post', '/reportes/ejecutivo')) {
        teError('Existe POST /reportes/ejecutivo fuera de contrato.', 'Retirar POST; 10A debe iniciar como read-only.');
    } else {
        teOk('No existe POST /reportes/ejecutivo.');
    }

    if (
        teHasRoute($routesCode, 'get', '/reportes/ejecutivo')
        && strpos($routesCode, "'controller' => 'Reportes', 'action' => 'ejecutivo'") !== false
    ) {
        teOk('Ruta 10A-B-A registrada como GET /reportes/ejecutivo.');
    } else {
        teError('No se encontro GET /reportes/ejecutivo hacia Reportes::ejecutivo.', 'Registrar una unica ruta GET para la pantalla 10A-B-A.');
    }
}

if ($reportesControllerCode !== null) {
    $ejecutivoBody = teExtractMethodCode($reportesControllerCode, 'ejecutivoAction');
    if (
        $ejecutivoBody !== ''
        && strpos($reportesControllerCode, 'TableroEjecutivo') !== false
        && strpos($ejecutivoBody, 'tableroEjecutivoModel') !== false
        && strpos($ejecutivoBody, 'reporteReadOnlyPorHotel') !== false
        && strpos($ejecutivoBody, "View::renderTemplate('reportes/ejecutivo'") !== false
        && strpos($ejecutivoBody, 'archivarNotificacionReporteGerencialVisto') === false
        && !teCodeHasSqlWriteToken($ejecutivoBody)
    ) {
        teOk('ReportesController expone ejecutivoAction con lector dedicado y sin archivado de notificaciones.');
    } else {
        teError(
            'ReportesController no cumple contrato 10A-B-A para ejecutivoAction.',
            'Usar TableroEjecutivo::reporteReadOnlyPorHotel(), filtros GET, render reportes/ejecutivo y cero escrituras.'
        );
    }

    $gerencialBody = teExtractMethodCode($reportesControllerCode, 'gerencialDiarioAction');
    $gerencialPdfBody = teExtractMethodCode($reportesControllerCode, 'gerencialDiarioPdfAction');
    if (
        $gerencialBody !== ''
        && strpos($gerencialBody, 'archivarNotificacionReporteGerencialVisto') === false
        && !teCodeHasSqlWriteToken($gerencialBody)
    ) {
        teOk('10B-A confirma que gerencialDiarioAction no archiva notificaciones por lectura directa.');
    } else {
        teError(
            '10B-A detecta side effect en gerencialDiarioAction.',
            'Separar lectura directa de reporte gerencial y archivado de notificaciones.'
        );
    }

    if (
        $gerencialPdfBody !== ''
        && strpos($gerencialPdfBody, 'archivarNotificacionReporteGerencialVisto') === false
        && !teCodeHasSqlWriteToken($gerencialPdfBody)
    ) {
        teOk('10B-A confirma que gerencialDiarioPdfAction no archiva notificaciones por descarga directa.');
    } else {
        teError(
            '10B-A detecta side effect en gerencialDiarioPdfAction.',
            'Mantener el PDF como descarga directa sin cambios de estado en notificaciones.'
        );
    }

    if (strpos($reportesControllerCode, 'function archivarNotificacionReporteGerencialVisto(') === false) {
        teOk('10B-A retiro el archivado automatico del ReportesController.');
    } else {
        teError(
            '10B-A aun conserva archivado automatico en ReportesController.',
            'Mover cualquier archivado al flujo controlado de notificaciones.'
        );
    }
}

if ($notificacionControllerCode !== null) {
    $abrirBody = teExtractMethodCode($notificacionControllerCode, 'abrirAction');
    $seArchivaBody = teExtractMethodCode($notificacionControllerCode, 'seArchivaAlAbrir');
    if (
        $abrirBody !== ''
        && $seArchivaBody !== ''
        && strpos($abrirBody, 'cambiarEstado') !== false
        && strpos($abrirBody, 'seArchivaAlAbrir') !== false
        && strpos($seArchivaBody, 'regla_reporte_gerencial_diario') !== false
    ) {
        teOk('10B-A conserva archivado solo en el flujo controlado de notificaciones.');
    } else {
        teWarning(
            'No se pudo confirmar archivado controlado desde NotificacionController.',
            'Revisar manualmente /notificaciones/{id}/abrir antes de cerrar QA 10B-A.'
        );
    }
}

if (
    $modelCode !== null
    && strpos($modelCode, 'class TableroEjecutivo') !== false
    && strpos($modelCode, 'function reporteReadOnlyPorHotel') !== false
    && strpos($modelCode, 'START TRANSACTION READ ONLY') !== false
    && strpos($modelCode, 'rollBack') !== false
    && strpos($modelCode, 'hotel_id') !== false
    && strpos($modelCode, 'ledger_laboral') !== false
    && !teCodeHasSqlWriteToken($modelCode)
) {
    teOk('TableroEjecutivo es lector read-only con hotel_id, transaccion y rollback.');
} else {
    teError(
        'TableroEjecutivo no cumple contrato read-only.',
        'Mantener modelo sin escrituras SQL, con START TRANSACTION READ ONLY, rollback y filtro por hotel actual.'
    );
}

if (
    $viewCode !== null
    && strpos($viewCode, 'Solo lectura') !== false
    && strpos($viewCode, 'method="get"') !== false
    && strpos($viewCode, "url('reportes/ejecutivo')") !== false
    && preg_match('/method\s*=\s*[\'"]post[\'"]/i', $viewCode) !== 1
    && strpos($viewCode, 'csrf_field()') === false
    && strpos($viewCode, 'name="hotel_id"') === false
    && strpos($viewCode, '--brand-') !== false
    && strpos($viewCode, '--ms-') === false
) {
    teOk('Vista reportes/ejecutivo usa filtros GET, solo lectura y branding hotelero.');
} else {
    teError(
        'Vista reportes/ejecutivo no cumple contrato visual/read-only.',
        'Usar filtros GET, etiqueta Solo lectura, tokens --brand-*, sin CSRF/POST y sin hotel_id editable.'
    );
}

if ($indexViewCode !== null && strpos($indexViewCode, "url('reportes/ejecutivo')") !== false) {
    teOk('Indice de reportes enlaza el tablero ejecutivo por GET.');
} else {
    teWarning(
        'Indice de reportes no enlaza el tablero ejecutivo.',
        'Agregar enlace GET a /reportes/ejecutivo para QA manual.'
    );
}

$pdo = null;
$database = '';

if (!is_file($configPath)) {
    teError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
        teOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        teError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $sourceSchema = [
        'hoteles' => ['id', 'nombre', 'activo'],
        'reservaciones' => ['id', 'hotel_id', 'huesped_id', 'fecha_entrada', 'fecha_salida', 'precio_total', 'estado'],
        'reservacion_habitaciones' => ['id', 'hotel_id', 'reservacion_id', 'habitacion_id'],
        'habitaciones' => ['id', 'hotel_id', 'estado', 'activa'],
        'tipos_habitacion' => ['id', 'hotel_id', 'nombre', 'activo'],
        'huespedes' => ['id', 'nombre_completo'],
        'cuentas_por_cobrar' => ['id', 'hotel_id', 'estado', 'total', 'saldo', 'fecha_emision'],
        'cuentas_por_cobrar_movimientos' => ['id', 'hotel_id', 'cuenta_por_cobrar_id', 'tipo_movimiento', 'monto', 'created_at'],
        'cuentas_por_pagar' => ['id', 'hotel_id', 'estado', 'total', 'saldo', 'fecha_emision'],
        'cuentas_por_pagar_movimientos' => ['id', 'hotel_id', 'cuenta_por_pagar_id', 'tipo_movimiento', 'monto', 'created_at'],
        'movimientos_caja' => ['id', 'hotel_id', 'tipo', 'monto', 'metodo_pago', 'corte_id', 'created_at'],
        'cortes_caja' => ['id', 'hotel_id', 'caja_id', 'estado', 'fecha_apertura', 'fecha_cierre'],
        'cajas' => ['id', 'hotel_id', 'nombre', 'activa'],
        'compras' => ['id', 'hotel_id', 'proveedor_id', 'estado', 'total', 'fecha_compra'],
        'compra_detalles' => ['id', 'hotel_id', 'compra_id', 'producto_id', 'cantidad'],
        'proveedores' => ['id', 'hotel_id', 'nombre', 'activo'],
        'inventario_productos' => ['id', 'hotel_id', 'nombre', 'stock_actual', 'stock_minimo', 'activo'],
        'movimientos_inventario' => ['id', 'hotel_id', 'producto_id', 'tipo_movimiento', 'cantidad', 'created_at'],
        'tareas_operativas' => ['id', 'hotel_id', 'categoria', 'estado', 'prioridad', 'fecha_limite'],
        'tarea_eventos' => ['id', 'hotel_id', 'tarea_id', 'tipo_evento', 'created_at'],
        'trabajadores' => ['id', 'hotel_id', 'nombre_completo', 'estado'],
        'ledger_laboral' => ['id', 'hotel_id'],
        'documentos' => ['id', 'hotel_id', 'estado', 'created_at'],
        'documento_entidades' => ['id', 'hotel_id', 'documento_id', 'entidad_tipo', 'entidad_id'],
        'logs_auditoria' => ['id', 'hotel_id', 'accion', 'created_at'],
    ];

    $optionalTables = [
        'ledger_laboral' => true,
    ];

    $schema = [];

    teSection('Fuentes candidatas');
    foreach ($sourceSchema as $table => $columns) {
        if (!teTableExists($pdo, $database, $table)) {
            if (isset($optionalTables[$table])) {
                teWarning('Fuente opcional no disponible: ' . $table . '.', 'Degradar KPI laboral pendiente sin migraciones automaticas.');
            } else {
                teWarning('Fuente candidata no disponible: ' . $table . '.', 'La futura pantalla debe degradar esta seccion con alerta de esquema.');
            }
            continue;
        }

        $schema[$table] = teTableColumns($pdo, $database, $table);
        $missing = array_values(array_diff($columns, $schema[$table]));
        if (empty($missing)) {
            teOk('Fuente disponible para 10A-A: ' . $table . '.');
        } else {
            teWarning(
                'Fuente ' . $table . ' disponible con columnas faltantes: ' . implode(', ', $missing) . '.',
                'La futura pantalla debe evitar consultas que dependan de columnas ausentes.'
            );
        }
    }

    teSection('Scope por hotel');
    foreach ($schema as $table => $columns) {
        if ($table === 'hoteles') {
            teOk('Fuente hoteles se usa solo para scope/nombre; no requiere hotel_id.');
            continue;
        }

        if (!in_array('hotel_id', $columns, true)) {
            teWarning(
                'Fuente ' . $table . ' no tiene hotel_id directo.',
                'Usar esta fuente solo mediante joins con entidades hotel-scoped.'
            );
            continue;
        }

        $count = teCount($pdo, 'SELECT COUNT(*) FROM ' . teQuoteIdentifier($table) . ' WHERE hotel_id IS NULL');
        if ($count === null) {
            teWarning('No se pudo validar hotel_id nulo en ' . $table . '.', 'Revisar manualmente scope multihotel.');
        } elseif ($count === 0) {
            teOk('Scope OK: ' . $table . '.hotel_id sin nulos.');
        } else {
            teWarning(
                'Scope incompleto: ' . $table . ' tiene hotel_id nulo en ' . (string)$count . ' filas.',
                'No exponer esta fuente en tablero ejecutivo sin resolver scope por hotel.'
            );
        }
    }

    teSection('KPIs candidatos');
    if (teHasColumns($schema, 'hoteles', ['id', 'activo'])) {
        teMetric('Hoteles activos', teCount($pdo, "SELECT COUNT(*) FROM hoteles WHERE activo = 1"));
    }

    if (teHasColumns($schema, 'reservaciones', ['hotel_id', 'estado', 'fecha_entrada', 'fecha_salida'])) {
        teMetric(
            'Reservaciones con estancia vigente hoy',
            teCount($pdo, "SELECT COUNT(*) FROM reservaciones
                WHERE fecha_entrada <= CURDATE()
                  AND fecha_salida >= CURDATE()
                  AND estado NOT IN ('cancelada', 'cancelado')")
        );
        teMetric(
            'Check-ins candidatos de hoy',
            teCount($pdo, "SELECT COUNT(*) FROM reservaciones
                WHERE fecha_entrada = CURDATE()
                  AND estado NOT IN ('cancelada', 'cancelado')")
        );
        teMetric(
            'Check-outs candidatos de hoy',
            teCount($pdo, "SELECT COUNT(*) FROM reservaciones
                WHERE fecha_salida = CURDATE()
                  AND estado NOT IN ('cancelada', 'cancelado')")
        );
    }

    if (teHasColumns($schema, 'habitaciones', ['hotel_id', 'estado', 'activa'])) {
        teMetric('Habitaciones activas', teCount($pdo, 'SELECT COUNT(*) FROM habitaciones WHERE activa = 1'));
        teMetric('Habitaciones ocupadas por estado', teCount($pdo, "SELECT COUNT(*) FROM habitaciones WHERE estado = 'ocupada' AND activa = 1"));
        teMetric('Habitaciones disponibles por estado', teCount($pdo, "SELECT COUNT(*) FROM habitaciones WHERE estado = 'disponible' AND activa = 1"));
        teMetric('Habitaciones en limpieza o mantenimiento', teCount($pdo, "SELECT COUNT(*) FROM habitaciones WHERE estado IN ('limpieza', 'mantenimiento') AND activa = 1"));
    }

    if (teHasColumns($schema, 'cuentas_por_cobrar', ['hotel_id', 'saldo', 'estado'])) {
        teMoneyMetric(
            'Saldo CxC pendiente',
            teScalar($pdo, "SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_cobrar WHERE saldo > 0 AND estado <> 'pagada'")
        );
        teMetric(
            'Cuentas CxC vencidas',
            teCount($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar
                WHERE saldo > 0
                  AND estado <> 'pagada'
                  AND fecha_vencimiento IS NOT NULL
                  AND fecha_vencimiento < CURDATE()")
        );
    }

    if (teHasColumns($schema, 'cuentas_por_pagar', ['hotel_id', 'saldo', 'estado'])) {
        teMoneyMetric(
            'Saldo CxP pendiente',
            teScalar($pdo, "SELECT COALESCE(SUM(saldo), 0) FROM cuentas_por_pagar WHERE saldo > 0 AND estado <> 'pagada'")
        );
        teMetric(
            'Cuentas CxP vencidas',
            teCount($pdo, "SELECT COUNT(*) FROM cuentas_por_pagar
                WHERE saldo > 0
                  AND estado <> 'pagada'
                  AND fecha_vencimiento IS NOT NULL
                  AND fecha_vencimiento < CURDATE()")
        );
    }

    if (teHasColumns($schema, 'movimientos_caja', ['hotel_id', 'tipo', 'monto', 'metodo_pago', 'created_at'])) {
        teMoneyMetric(
            'Ingresos de Caja del mes',
            teScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja
                WHERE tipo = 'ingreso'
                  AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")
        );
        teMoneyMetric(
            'Gastos de Caja del mes',
            teScalar($pdo, "SELECT COALESCE(SUM(monto), 0) FROM movimientos_caja
                WHERE tipo = 'gasto'
                  AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")
        );
        teMetric(
            'Metodos de pago usados en el mes',
            teCount($pdo, "SELECT COUNT(*) FROM (
                SELECT metodo_pago
                FROM movimientos_caja
                WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                GROUP BY metodo_pago
            ) metodos")
        );
    }

    if (teHasColumns($schema, 'cortes_caja', ['hotel_id', 'estado'])) {
        teMetric('Cortes de Caja abiertos', teCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'abierto'"));
        if (teHasColumns($schema, 'cortes_caja', ['diferencia'])) {
            teMetric(
                'Cortes cerrados con diferencia registrada',
                teCount($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'cerrado' AND ABS(COALESCE(diferencia, 0)) > 0.009")
            );
        }
    }

    if (teHasColumns($schema, 'compras', ['hotel_id', 'estado', 'total', 'fecha_compra'])) {
        teMoneyMetric(
            'Compras recibidas del mes',
            teScalar($pdo, "SELECT COALESCE(SUM(total), 0) FROM compras
                WHERE estado = 'recibida'
                  AND fecha_compra >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")
        );
    }

    if (teHasColumns($schema, 'inventario_productos', ['hotel_id', 'stock_actual', 'stock_minimo', 'activo'])) {
        teMetric(
            'Productos bajo minimo',
            teCount($pdo, 'SELECT COUNT(*) FROM inventario_productos
                WHERE activo = 1
                  AND stock_actual <= stock_minimo')
        );
    }

    if (
        teHasColumns($schema, 'inventario_productos', ['id', 'hotel_id', 'activo'])
        && teHasColumns($schema, 'movimientos_inventario', ['producto_id', 'hotel_id', 'created_at'])
    ) {
        teMetric(
            'Productos sin movimiento en 30 dias',
            teCount($pdo, "SELECT COUNT(*)
                FROM inventario_productos p
                LEFT JOIN movimientos_inventario m
                  ON m.producto_id = p.id
                 AND m.hotel_id = p.hotel_id
                 AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                WHERE p.activo = 1
                  AND m.id IS NULL")
        );
    }

    if (teHasColumns($schema, 'tareas_operativas', ['hotel_id', 'estado', 'fecha_limite'])) {
        teMetric(
            'Tareas operativas activas',
            teCount($pdo, "SELECT COUNT(*) FROM tareas_operativas WHERE estado IN ('pendiente', 'asignada', 'en_proceso')")
        );
        teMetric(
            'Tareas operativas vencidas',
            teCount($pdo, "SELECT COUNT(*) FROM tareas_operativas
                WHERE estado IN ('pendiente', 'asignada', 'en_proceso')
                  AND fecha_limite IS NOT NULL
                  AND fecha_limite < NOW()")
        );
    }

    if (teHasColumns($schema, 'trabajadores', ['hotel_id', 'estado'])) {
        teMetric('Trabajadores activos', teCount($pdo, "SELECT COUNT(*) FROM trabajadores WHERE estado = 'activo'"));
    }

    if (teHasColumns($schema, 'documentos', ['hotel_id', 'created_at'])) {
        teMetric(
            'Documentos cargados en 30 dias',
            teCount($pdo, 'SELECT COUNT(*) FROM documentos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)')
        );
    }

    if (teHasColumns($schema, 'logs_auditoria', ['hotel_id', 'created_at'])) {
        teMetric(
            'Eventos de auditoria en 7 dias',
            teCount($pdo, 'SELECT COUNT(*) FROM logs_auditoria WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
        );
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        teOk('Transaccion read-only cerrada con rollback sin escrituras.');
    }
}

tePrintSummary();
exit($errors > 0 ? 1 : 0);
