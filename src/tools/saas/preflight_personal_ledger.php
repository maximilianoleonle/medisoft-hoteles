<?php
/**
 * Preflight Fase NP-C-B-A para ledger laboral de Personal.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida consistencia de trabajador_* y ausencia de Caja/Nomina operativa antes de
 * habilitar escrituras futuras sobre conceptos laborales.
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

function npPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function npPfOk(string $message): void
{
    global $ok;
    $ok++;
    npPfLine('OK', $message);
}

function npPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    npPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function npPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    npPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function npPfQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function npPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function npPfColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function npPfCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function npPfReportZero(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        npPfWarning(
            'No se pudo validar ledger laboral: ' . $label . '.',
            'Revisar manualmente las tablas trabajador_* antes de habilitar escrituras.'
        );
        return;
    }

    if ($count === 0) {
        npPfOk('Consistencia NP-C OK: ' . $label . ' = 0.');
        return;
    }

    npPfError('Consistencia NP-C fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function npPfParseRoutes(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = (string) file_get_contents($path);
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

function npPfFileContainsForbiddenLedgerWrite(string $path): bool
{
    if (!is_file($path)) {
        return false;
    }

    $code = (string) file_get_contents($path);

    if (preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(trabajador_anticipos|trabajador_prestamos|trabajador_asistencias|trabajador_documentos|movimientos_caja|cajas|cortes_caja)\b/i', $code)) {
        return true;
    }

    if (preg_match('/\b(UPDATE|DELETE\s+FROM)\s+trabajador_pagos\b/i', $code)) {
        return true;
    }

    if (basename($path) !== 'Trabajador.php' && preg_match('/\bINSERT\s+INTO\s+trabajador_pagos\b/i', $code)) {
        return true;
    }

    return false;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    npPfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$workerModelPath = $appRoot . '/app/models/Trabajador.php';
$workerControllerPath = $appRoot . '/app/controllers/TrabajadorController.php';
$workerDetailViewPath = $appRoot . '/app/views/trabajadores/ver.php';

echo "Preflight Fase NP-C-B-A - Ledger laboral\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    npPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
    npPfOk('Configuracion de base detectada.');
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
        npPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        npPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $requiredTables = [
        'trabajadores',
        'trabajador_pagos',
        'trabajador_anticipos',
        'trabajador_prestamos',
        'trabajador_asistencias',
        'trabajador_documentos',
    ];

    foreach ($requiredTables as $table) {
        if (!npPfTableExists($pdo, $database, $table)) {
            npPfError('Falta tabla Personal base: ' . $table . '.', 'Aplicar NP-A con backup antes de usar ledger laboral.');
            continue;
        }

        if (!npPfColumnExists($pdo, $database, $table, 'hotel_id')) {
            npPfError('Tabla Personal sin hotel_id: ' . $table . '.', 'No usar Personal multi-hotel hasta corregir esquema.');
            continue;
        }

        npPfOk('Tabla Personal disponible con hotel_id: ' . $table . '. Registros=' . (string)npPfCountScalar($pdo, 'SELECT COUNT(*) FROM ' . npPfQuote($table)) . '.');
        npPfReportZero(
            $table . ' con hotel_id nulo',
            npPfCountScalar($pdo, 'SELECT COUNT(*) FROM ' . npPfQuote($table) . ' WHERE hotel_id IS NULL'),
            'Reconciliar hotel_id antes de habilitar movimientos laborales.'
        );
    }

    $childTables = [
        'trabajador_pagos' => 'conceptos laborales',
        'trabajador_anticipos' => 'anticipos',
        'trabajador_prestamos' => 'prestamos',
        'trabajador_asistencias' => 'asistencias',
        'trabajador_documentos' => 'documentos laborales',
    ];

    foreach ($childTables as $table => $label) {
        if (!npPfTableExists($pdo, $database, $table)) {
            continue;
        }

        npPfReportZero(
            $label . ' con trabajador inexistente',
            npPfCountScalar($pdo, "SELECT COUNT(*)
                FROM {$table} x
                LEFT JOIN trabajadores t ON t.id = x.trabajador_id
                WHERE t.id IS NULL"),
            'Reconciliar trabajador_id antes de exponer ledger laboral.'
        );
        npPfReportZero(
            $label . ' con trabajador de otro hotel',
            npPfCountScalar($pdo, "SELECT COUNT(*)
                FROM {$table} x
                JOIN trabajadores t ON t.id = x.trabajador_id
                WHERE t.hotel_id <> x.hotel_id"),
            'Bloquear escrituras hasta alinear hotel_id del trabajador y sus movimientos.'
        );
    }

    npPfReportZero(
        'trabajadores con salario base negativo',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajadores WHERE salario_base < 0'),
        'Corregir salario_base antes de usar reportes laborales.'
    );
    npPfReportZero(
        'conceptos laborales con monto invalido',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_pagos WHERE monto <= 0'),
        'No habilitar captura hasta validar montos mayores a cero.'
    );
    npPfReportZero(
        'conceptos laborales con tipo invalido',
        npPfCountScalar($pdo, "SELECT COUNT(*) FROM trabajador_pagos WHERE tipo NOT IN ('comision', 'bono', 'descuento', 'ajuste')"),
        'Normalizar tipos de concepto laboral.'
    );
    npPfReportZero(
        'conceptos laborales con efecto invalido',
        npPfCountScalar($pdo, "SELECT COUNT(*) FROM trabajador_pagos WHERE efecto NOT IN ('a_favor', 'en_contra')"),
        'Normalizar efecto de concepto laboral.'
    );
    npPfReportZero(
        'conceptos laborales con estado invalido',
        npPfCountScalar($pdo, "SELECT COUNT(*) FROM trabajador_pagos WHERE estado NOT IN ('activo', 'anulado')"),
        'Normalizar estado de concepto laboral.'
    );
    npPfReportZero(
        'conceptos laborales sin fecha',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_pagos WHERE fecha IS NULL'),
        'Completar fecha antes de reportes laborales.'
    );
    npPfReportZero(
        'anticipos con saldo mayor al monto o saldo negativo',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_anticipos WHERE saldo_pendiente < 0 OR saldo_pendiente > monto'),
        'Reconciliar anticipos antes de habilitar descuentos.'
    );
    npPfReportZero(
        'anticipos con monto invalido',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_anticipos WHERE monto <= 0'),
        'No habilitar captura hasta validar montos mayores a cero.'
    );
    npPfReportZero(
        'prestamos con saldo mayor al monto o saldo negativo',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_prestamos WHERE saldo_pendiente < 0 OR saldo_pendiente > monto'),
        'Reconciliar prestamos antes de habilitar descuentos.'
    );
    npPfReportZero(
        'prestamos con monto invalido',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_prestamos WHERE monto <= 0'),
        'No habilitar captura hasta validar montos mayores a cero.'
    );
    npPfReportZero(
        'asistencias con fecha nula',
        npPfCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_asistencias WHERE fecha IS NULL'),
        'Completar fecha antes de reportes de asistencia.'
    );
    npPfReportZero(
        'asistencias duplicadas por trabajador/dia',
        npPfCountScalar($pdo, "SELECT COUNT(*) FROM (
            SELECT hotel_id, trabajador_id, fecha
            FROM trabajador_asistencias
            GROUP BY hotel_id, trabajador_id, fecha
            HAVING COUNT(*) > 1
        ) duplicadas"),
        'Dejar una asistencia por trabajador/dia antes de operar asistencia.'
    );

    if (npPfTableExists($pdo, $database, 'categorias_movimientos')) {
        npPfReportZero(
            'categorias de Caja con nombre Nomina',
            npPfCountScalar($pdo, "SELECT COUNT(*) FROM categorias_movimientos WHERE LOWER(COALESCE(nombre, '')) LIKE '%nomina%'"),
            'No crear categoria Nomina en Caja dentro de NP-C.'
        );
    }

    if (npPfTableExists($pdo, $database, 'movimientos_caja')) {
        npPfReportZero(
            'movimientos de Caja con categoria Nomina',
            npPfCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE LOWER(COALESCE(categoria, '')) LIKE '%nomina%'"),
            'No mezclar ledger laboral con movimientos de Caja.'
        );
    }

    $pdo->rollBack();
}

$routes = npPfParseRoutes($routesPath);
if ($routes === []) {
    npPfWarning('No se pudieron parsear rutas.', 'Ejecutar desde el arbol src completo.');
} else {
    $forbiddenWorkerRoutes = [];
    foreach ($routes as $route) {
        $path = strtolower((string)$route['path']);
        if (strpos($path, '/trabajadores') !== false && preg_match('/pago|anticipo|prestamo|asistencia|nomina|caja/', $path)) {
            $forbiddenWorkerRoutes[] = strtoupper($route['method']) . ' ' . $route['path'];
        }
    }

    if ($forbiddenWorkerRoutes === []) {
        npPfOk('Rutas Personal NP-C-B-A no exponen pagos reales, anticipos, prestamos, asistencia, nomina ni Caja.');
    } else {
        npPfError(
            'Rutas Personal fuera de alcance: ' . implode(', ', $forbiddenWorkerRoutes) . '.',
            'Retirar rutas operativas hasta abrir contrato de anticipos, prestamos, asistencia o Caja.'
        );
    }
}

foreach ([$workerModelPath, $workerControllerPath] as $path) {
    if (!is_file($path)) {
        npPfWarning('No se encontro archivo esperado: ' . $path, 'Revisar instalacion del modulo Personal.');
        continue;
    }

    if (npPfFileContainsForbiddenLedgerWrite($path)) {
        npPfError(
            'Archivo contiene escritura fuera del alcance NP-C-B-A: ' . basename($path),
            'NP-C-B-A solo permite INSERT controlado en trabajador_pagos desde Trabajador.php; retirar otras escrituras de ledger o Caja.'
        );
    } else {
        npPfOk('Archivo sin escrituras de ledger/Caja fuera de alcance: ' . basename($path) . '.');
    }
}

if (is_file($workerDetailViewPath)) {
    $viewCode = (string) file_get_contents($workerDetailViewPath);
    if (
        strpos($viewCode, 'Ledger laboral') !== false
        && strpos($viewCode, 'Saldo informativo') !== false
        && strpos($viewCode, 'no representa movimiento de Caja') !== false
        && strpos($viewCode, 'movimientos_caja') === false
    ) {
        npPfOk('Vista de trabajador muestra ledger y concepto manual sin Caja con aclaracion de saldo informativo.');
    } else {
        npPfWarning(
            'Vista de trabajador no muestra claramente el contrato NP-C-B-A.',
            'Asegurar texto de saldo informativo y ausencia de rutas internas/Caja.'
        );
    }
} else {
    npPfWarning('No se encontro vista de trabajador.', 'Revisar modulo Personal antes de QA.');
}

echo "=====================================================\n";
echo "Resumen\n";
echo 'OK: ' . $ok . "\n";
echo 'WARNING: ' . $warnings . "\n";
echo 'ERROR: ' . $errors . "\n";

if (!empty($recommendations)) {
    echo "Recomendaciones concretas:\n";
    foreach (array_values(array_unique($recommendations)) as $recommendation) {
        echo '- ' . $recommendation . "\n";
    }
}

echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
exit($errors > 0 ? 1 : 0);
