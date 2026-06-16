<?php
/**
 * Preflight Fase LIM-A/LIM-B-A - Limpieza operativa.
 *
 * Solo ejecuta consultas de lectura y validaciones estaticas.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$ok = 0;
$warnings = 0;
$errors = 0;

function limPfOk(string $message): void
{
    global $ok;
    $ok++;
    echo "[OK] {$message}\n";
}

function limPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings;
    $warnings++;
    echo "[WARNING] {$message}\n";
    if ($recommendation !== '') {
        echo "          Recomendacion: {$recommendation}\n";
    }
}

function limPfError(string $message, string $recommendation = ''): void
{
    global $errors;
    $errors++;
    echo "[ERROR] {$message}\n";
    if ($recommendation !== '') {
        echo "        Recomendacion: {$recommendation}\n";
    }
}

function limPfBasePath(string $path): string
{
    return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
}

function limPfTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?"
    );
    $stmt->execute([$table]);
    return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
}

function limPfColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?
           AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) > 0;
}

function limPfScalar(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)($stmt->fetchColumn() ?: 0);
}

function limPfRouteExists(string $routesCode, string $method, string $path, string $action): bool
{
    return strpos($routesCode, "\$router->{$method}('{$path}'") !== false
        && strpos($routesCode, "'action' => '{$action}'") !== false;
}

function limPfMethodBody(string $code, string $method): string
{
    $pos = strpos($code, 'function ' . $method);
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

    return '';
}

echo "Preflight Fase LIM-A/LIM-B-A - Limpieza operativa\n";
echo "====================================================\n";

$root = dirname(__DIR__, 2);
$configPath = $root . '/config/database.php';
$routesPath = $root . '/config/routes.php';
$controllerPath = $root . '/app/controllers/ReportesController.php';
$taskControllerPath = $root . '/app/controllers/TareaController.php';
$taskModelPath = $root . '/app/models/TareaOperativa.php';
$viewPath = $root . '/app/views/reportes/limpieza-operativa.php';
$indexViewPath = $root . '/app/views/reportes/index.php';

$pdo = null;
if (!is_file($configPath)) {
    limPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
    limPfOk('Configuracion de base detectada.');
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
        limPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        limPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    foreach (['hoteles', 'habitaciones', 'reservaciones', 'reservacion_habitaciones'] as $table) {
        if (limPfTableExists($pdo, $table)) {
            limPfOk("Tabla requerida disponible: {$table}");
        } else {
            limPfError("Tabla requerida faltante: {$table}");
        }
    }

    if (limPfTableExists($pdo, 'tareas_operativas')) {
        limPfOk('Tabla opcional tareas_operativas disponible para contexto de limpieza.');
        $crossHotelTasks = limPfScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM tareas_operativas t
             INNER JOIN habitaciones h
                ON h.id = t.habitacion_id
             WHERE t.categoria = 'limpieza'
               AND t.habitacion_id IS NOT NULL
               AND h.hotel_id <> t.hotel_id"
        );
        if ($crossHotelTasks === 0) {
            limPfOk('Tareas de limpieza vinculadas a habitacion de otro hotel = 0.');
        } else {
            limPfError('Tareas de limpieza cross-hotel detectadas: ' . $crossHotelTasks);
        }

        $duplicadas = limPfScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM (
                SELECT hotel_id, habitacion_id, COUNT(*) AS total
                FROM tareas_operativas
                WHERE categoria = 'limpieza'
                  AND habitacion_id IS NOT NULL
                  AND estado IN ('pendiente', 'asignada', 'en_proceso')
                GROUP BY hotel_id, habitacion_id
                HAVING COUNT(*) > 1
             ) duplicadas"
        );
        if ($duplicadas === 0) {
            limPfOk('Habitaciones con mas de una tarea activa de limpieza = 0.');
        } else {
            limPfError('Habitaciones con tareas activas de limpieza duplicadas: ' . $duplicadas);
        }
    } else {
        limPfWarning('Tabla tareas_operativas no existe.', 'LIM-A debe seguir funcionando con contexto de tareas vacio.');
    }

    $habitacionesLimpieza = limPfScalar(
        $pdo,
        "SELECT COUNT(*)
         FROM habitaciones
         WHERE estado = 'limpieza'
           AND activa = 1"
    );
    limPfOk('Habitaciones activas en limpieza detectadas: ' . $habitacionesLimpieza);

    if (limPfTableExists($pdo, 'movimientos_caja')) {
        $columns = [];
        foreach (['descripcion', 'concepto', 'referencia', 'observaciones'] as $column) {
            if (limPfColumnExists($pdo, 'movimientos_caja', $column)) {
                $columns[] = $column;
            }
        }

        if ($columns === []) {
            limPfWarning('No se pudo validar texto de movimientos_caja.', 'Confirmar manualmente ausencia de referencias LIM-A si aplica.');
        } else {
            $concat = "LOWER(CONCAT_WS(' ', " . implode(', ', $columns) . "))";
            $cajaLimpieza = limPfScalar(
                $pdo,
                "SELECT COUNT(*)
                 FROM movimientos_caja
                 WHERE {$concat} LIKE '%limpieza operativa%'"
            );
            if ($cajaLimpieza === 0) {
                limPfOk('Movimientos de Caja relacionados con LIM-A = 0.');
            } else {
                limPfError('Movimientos de Caja relacionados con LIM-A detectados: ' . $cajaLimpieza);
            }
        }
    }
}

$routesCode = is_file($routesPath) ? file_get_contents($routesPath) : '';
if ($routesCode !== '' && limPfRouteExists($routesCode, 'get', '/reportes/limpieza', 'limpieza')) {
    limPfOk('Ruta LIM-A registrada: GET /reportes/limpieza.');
} else {
    limPfError('Ruta LIM-A faltante o no GET.', 'Registrar solo GET /reportes/limpieza.');
}

if (strpos($routesCode, "\$router->post('/reportes/limpieza") === false) {
    limPfOk('No existe POST /reportes/limpieza.');
} else {
    limPfError('POST /reportes/limpieza detectado.', 'LIM-A debe ser solo GET.');
}

if ($routesCode !== '' && limPfRouteExists($routesCode, 'post', '/tareas/desde-limpieza/{id:[0-9]+}', 'crearDesdeLimpieza')) {
    limPfOk('Ruta LIM-B-A registrada: POST /tareas/desde-limpieza/{id}.');
} else {
    limPfError('Ruta LIM-B-A faltante o no controlada.', 'Registrar solo POST /tareas/desde-limpieza/{id} con TareaController.');
}

$controllerCode = is_file($controllerPath) ? file_get_contents($controllerPath) : '';
$limActionCode = limPfMethodBody($controllerCode, 'limpiezaAction');
$limReportCode = limPfMethodBody($controllerCode, 'reporteLimpiezaOperativa');
if (
    $limActionCode !== ''
    && $limReportCode !== ''
    && strpos($limReportCode, "h.estado = 'limpieza'") !== false
    && strpos($limReportCode, 't.categoria = \'limpieza\'') !== false
) {
    limPfOk('ReportesController LIM-A consulta limpieza con hotel_id y contexto de tareas.');
} else {
    limPfError('ReportesController LIM-A incompleto.', 'Revisar accion, consulta scoped y categoria limpieza.');
}

if (
    stripos($limActionCode . $limReportCode, 'UPDATE habitaciones') === false
    && stripos($limActionCode . $limReportCode, 'INSERT INTO tareas_operativas') === false
    && stripos($limActionCode . $limReportCode, 'movimientos_caja') === false
    && stripos($limActionCode . $limReportCode, 'api/sync') === false
) {
    limPfOk('ReportesController LIM-A no muestra escrituras de limpieza, Caja ni /api/sync.');
} else {
    limPfError('ReportesController LIM-A contiene patrones prohibidos.', 'LIM-A debe ser read-only.');
}

$viewCode = is_file($viewPath) ? file_get_contents($viewPath) : '';
if (
    strpos($viewCode, 'LIM-B-A agrega solo creacion manual') !== false
    && strpos($viewCode, "url('habitaciones/'") !== false
    && strpos($viewCode, "url('tareas/'") !== false
    && strpos($viewCode, "url('tareas/desde-limpieza/'") !== false
    && strpos($viewCode, 'csrf_field()') !== false
    && stripos($viewCode, 'storage_path') === false
) {
    limPfOk('Vista LIM-A/LIM-B-A muestra reporte y POST manual con CSRF sin exponer storage.');
} else {
    limPfError('Vista LIM-A/LIM-B-A no cumple contrato esperado.', 'Revisar enlaces GET, POST manual con CSRF y ausencia de storage.');
}

$taskControllerCode = is_file($taskControllerPath) ? file_get_contents($taskControllerPath) : '';
$limTaskActionCode = limPfMethodBody($taskControllerCode, 'crearDesdeLimpiezaAction');
if (
    $limTaskActionCode !== ''
    && strpos($limTaskActionCode, 'requireWritePermission') !== false
    && strpos($limTaskActionCode, 'validateCSRF') !== false
    && strpos($limTaskActionCode, 'crearDesdeLimpiezaHabitacionParaHotel') !== false
    && strpos($limTaskActionCode, 'tareas.creada_desde_limpieza') !== false
) {
    limPfOk('TareaController LIM-B-A crea tarea de limpieza con permiso, CSRF y auditoria.');
} else {
    limPfError('TareaController LIM-B-A incompleto.', 'Revisar permiso, CSRF, metodo central y auditoria.');
}

$taskModelCode = is_file($taskModelPath) ? file_get_contents($taskModelPath) : '';
$limTaskCreateCode = limPfMethodBody($taskModelCode, 'crearDesdeLimpiezaHabitacionParaHotel');
if (
    $limTaskCreateCode !== ''
    && strpos($limTaskCreateCode, "estado'] ?? '') !== 'limpieza'") !== false
    && strpos($limTaskCreateCode, "categoria = 'limpieza'") !== false
    && strpos($limTaskCreateCode, "'limpieza_manual'") !== false
    && stripos($limTaskCreateCode, 'UPDATE habitaciones') === false
    && stripos($limTaskCreateCode, 'movimientos_caja') === false
    && stripos($limTaskCreateCode, 'api/sync') === false
) {
    limPfOk('TareaOperativa LIM-B-A valida habitacion en limpieza, bloquea duplicado y no cambia disponibilidad.');
} else {
    limPfError('TareaOperativa LIM-B-A no muestra guardas completas.', 'Revisar estado limpieza, duplicado activo, origen y ausencia de cambios en habitaciones/Caja.');
}

$indexCode = is_file($indexViewPath) ? file_get_contents($indexViewPath) : '';
if (strpos($indexCode, "url('reportes/limpieza')") !== false) {
    limPfOk('Centro de reportes enlaza LIM-A.');
} else {
    limPfWarning('Centro de reportes no enlaza LIM-A.', 'Agregar enlace GET si se desea navegabilidad.');
}

echo "====================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";
echo $errors === 0 ? "Resultado general: PASS_WITH_WARNINGS_ALLOWED\n" : "Resultado general: FAIL\n";

exit($errors === 0 ? 0 : 1);
