<?php
/**
 * Preflight Fase TLM-G para tareas operativas.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida consistencia de tareas_operativas/tarea_eventos, entidades vinculadas,
 * ausencia de Caja/Nomina y guardas tecnicas antes de automatizar tareas.
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

function tlmPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function tlmPfOk(string $message): void
{
    global $ok;
    $ok++;
    tlmPfLine('OK', $message);
}

function tlmPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    tlmPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function tlmPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    tlmPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function tlmPfQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function tlmPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function tlmPfColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function tlmPfCountRows(PDO $pdo, string $table): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . tlmPfQuote($table));

    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

function tlmPfCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function tlmPfReportZeroCount(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        tlmPfWarning(
            'No se pudo validar consistencia TLM: ' . $label . '.',
            'Revisar manualmente tareas_operativas/tarea_eventos antes de automatizar tareas.'
        );
        return;
    }

    if ($count === 0) {
        tlmPfOk('Consistencia TLM OK: ' . $label . ' = 0.');
        return;
    }

    tlmPfError('Consistencia TLM fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function tlmPfCajaTextPredicate(PDO $pdo, string $database): ?string
{
    $columns = [];
    foreach (['descripcion', 'referencia', 'proveedor', 'categoria', 'comprobante', 'motivo_edicion'] as $column) {
        if (tlmPfColumnExists($pdo, $database, 'movimientos_caja', $column)) {
            $columns[] = 'COALESCE(' . tlmPfQuote($column) . ", '')";
        }
    }

    if (empty($columns)) {
        return null;
    }

    return "LOWER(CONCAT_WS(' ', " . implode(', ', $columns) . ")) REGEXP 'tareas_operativas|tarea operativa|tarea #[0-9]+'";
}

function tlmPfParseRoutes(string $path): array
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

function tlmPfRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower($route['method']) === strtolower($method) && trim($route['path'], '/') === $path) {
            return true;
        }
    }

    return false;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    tlmPfError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$taskModelPath = $appRoot . '/app/models/TareaOperativa.php';
$taskControllerPath = $appRoot . '/app/controllers/TareaController.php';
$taskPartialPath = $appRoot . '/app/views/tareas/_contextual_list.php';
$habitacionControllerPath = $appRoot . '/app/controllers/HabitacionController.php';
$trabajadorControllerPath = $appRoot . '/app/controllers/TrabajadorController.php';

echo "Preflight Fase TLM-G - Tareas operativas\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    tlmPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
    tlmPfOk('Configuracion de base detectada.');
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
        tlmPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        tlmPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo) {
    $requiredTables = [
        'hoteles',
        'habitaciones',
        'mantenimientos_habitaciones',
        'tareas_operativas',
        'tarea_eventos',
        'movimientos_caja',
    ];

    foreach ($requiredTables as $table) {
        if (tlmPfTableExists($pdo, $database, $table)) {
            tlmPfOk('Tabla requerida disponible: ' . $table);
        } else {
            tlmPfError('Falta tabla requerida: ' . $table, 'No continuar con TLM hasta reconciliar la migracion base.');
        }
    }

    if (tlmPfTableExists($pdo, $database, 'tareas_operativas') && tlmPfTableExists($pdo, $database, 'tarea_eventos')) {
        $taskCount = tlmPfCountRows($pdo, 'tareas_operativas');
        $eventCount = tlmPfCountRows($pdo, 'tarea_eventos');
        tlmPfOk('Conteo TLM: tareas_operativas=' . (string)$taskCount . ', tarea_eventos=' . (string)$eventCount . '.');

        $checks = [
            [
                'label' => 'tareas con hotel_id nulo',
                'sql' => 'SELECT COUNT(*) FROM tareas_operativas WHERE hotel_id IS NULL',
                'recommendation' => 'Asignar hotel_id desde respaldo verificado antes de usar tareas.',
            ],
            [
                'label' => 'eventos con hotel_id nulo',
                'sql' => 'SELECT COUNT(*) FROM tarea_eventos WHERE hotel_id IS NULL',
                'recommendation' => 'Reconciliar eventos sin hotel antes de usar historial de tareas.',
            ],
            [
                'label' => 'eventos sin tarea existente',
                'sql' => "SELECT COUNT(*)
                    FROM tarea_eventos e
                    LEFT JOIN tareas_operativas t ON t.id = e.tarea_id
                    WHERE t.id IS NULL",
                'recommendation' => 'No borrar tareas con eventos; reconciliar tarea_id antes de continuar.',
            ],
            [
                'label' => 'eventos con hotel distinto a la tarea',
                'sql' => "SELECT COUNT(*)
                    FROM tarea_eventos e
                    JOIN tareas_operativas t ON t.id = e.tarea_id
                    WHERE e.hotel_id <> t.hotel_id",
                'recommendation' => 'Corregir hotel_id de eventos desde la tarea validada.',
            ],
            [
                'label' => 'tareas con estado invalido',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE estado NOT IN ('pendiente', 'asignada', 'en_proceso', 'completada', 'cancelada')",
                'recommendation' => 'Normalizar estados antes de exponer automatizaciones.',
            ],
            [
                'label' => 'tareas con categoria invalida',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE categoria NOT IN ('limpieza', 'mantenimiento', 'general')",
                'recommendation' => 'Normalizar categoria de tareas antes de reportes operativos.',
            ],
            [
                'label' => 'tareas con prioridad invalida',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE prioridad NOT IN ('baja', 'media', 'alta', 'urgente')",
                'recommendation' => 'Normalizar prioridad de tareas antes de reportes operativos.',
            ],
            [
                'label' => 'tareas cerradas sin fecha_cierre',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE estado IN ('completada', 'cancelada')
                      AND fecha_cierre IS NULL",
                'recommendation' => 'Completar fecha_cierre antes de usar metricas de cumplimiento.',
            ],
            [
                'label' => 'tareas en proceso sin fecha_inicio',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE estado = 'en_proceso'
                      AND fecha_inicio IS NULL",
                'recommendation' => 'Completar fecha_inicio o regresar la tarea a estado consistente.',
            ],
            [
                'label' => 'tareas activas con fecha_cierre',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE estado IN ('pendiente', 'asignada', 'en_proceso')
                      AND fecha_cierre IS NOT NULL",
                'recommendation' => 'Quitar fecha_cierre o cerrar formalmente la tarea con evento.',
            ],
            [
                'label' => 'tareas con fecha_limite anterior a fecha_programada',
                'sql' => "SELECT COUNT(*) FROM tareas_operativas
                    WHERE fecha_programada IS NOT NULL
                      AND fecha_limite IS NOT NULL
                      AND fecha_limite < fecha_programada",
                'recommendation' => 'Corregir calendario de tareas antes de usar alertas.',
            ],
            [
                'label' => 'tareas sin evento inicial creada',
                'sql' => "SELECT COUNT(*)
                    FROM tareas_operativas t
                    LEFT JOIN tarea_eventos e
                      ON e.tarea_id = t.id
                     AND e.hotel_id = t.hotel_id
                     AND e.tipo_evento = 'creada'
                    WHERE e.id IS NULL",
                'recommendation' => 'Reconstruir evento inicial desde auditoria antes de usar trazabilidad.',
            ],
        ];

        foreach ($checks as $check) {
            tlmPfReportZeroCount($check['label'], tlmPfCountScalar($pdo, $check['sql']), $check['recommendation']);
        }

        $entityChecks = [
            ['table' => 'habitaciones', 'column' => 'habitacion_id', 'label' => 'habitacion'],
            ['table' => 'trabajadores', 'column' => 'trabajador_id', 'label' => 'trabajador'],
            ['table' => 'mantenimientos_habitaciones', 'column' => 'mantenimiento_id', 'label' => 'mantenimiento'],
            ['table' => 'reservaciones', 'column' => 'reservacion_id', 'label' => 'reservacion'],
            ['table' => 'huespedes', 'column' => 'huesped_id', 'label' => 'huesped'],
        ];

        foreach ($entityChecks as $check) {
            if (!tlmPfTableExists($pdo, $database, $check['table'])) {
                tlmPfWarning(
                    'No se pudo validar tareas con ' . $check['label'] . ' porque falta ' . $check['table'] . '.',
                    'Revisar esquema antes de vincular tareas a ' . $check['label'] . '.'
                );
                continue;
            }

            if (!tlmPfColumnExists($pdo, $database, $check['table'], 'hotel_id')) {
                $column = tlmPfQuote($check['column']);
                tlmPfReportZeroCount(
                    'tareas con ' . $check['column'] . ' directo sin scope hotel',
                    tlmPfCountScalar($pdo, "SELECT COUNT(*)
                        FROM tareas_operativas
                        WHERE {$column} IS NOT NULL"),
                    'No vincular tareas a ' . $check['table'] . ' hasta tener una ruta de scope por hotel.'
                );
                continue;
            }

            $table = tlmPfQuote($check['table']);
            $column = tlmPfQuote($check['column']);
            tlmPfReportZeroCount(
                'tareas con ' . $check['label'] . ' inexistente',
                tlmPfCountScalar($pdo, "SELECT COUNT(*)
                    FROM tareas_operativas t
                    LEFT JOIN {$table} e ON e.id = t.{$column}
                    WHERE t.{$column} IS NOT NULL
                      AND e.id IS NULL"),
                'Reconciliar ' . $check['column'] . ' antes de usar vistas contextuales.'
            );
            tlmPfReportZeroCount(
                'tareas con ' . $check['label'] . ' de otro hotel',
                tlmPfCountScalar($pdo, "SELECT COUNT(*)
                    FROM tareas_operativas t
                    JOIN {$table} e ON e.id = t.{$column}
                    WHERE t.{$column} IS NOT NULL
                      AND e.hotel_id <> t.hotel_id"),
                'Bloquear automatizaciones hasta alinear hotel_id de tareas y entidad vinculada.'
            );
        }

        if (tlmPfTableExists($pdo, $database, 'trabajadores')) {
            $inactiveAssigned = tlmPfCountScalar($pdo, "SELECT COUNT(*)
                FROM tareas_operativas t
                JOIN trabajadores tr
                  ON tr.id = t.trabajador_id
                 AND tr.hotel_id = t.hotel_id
                WHERE t.trabajador_id IS NOT NULL
                  AND tr.estado <> 'activo'
                  AND t.estado IN ('pendiente', 'asignada', 'en_proceso')");
            if ($inactiveAssigned === 0) {
                tlmPfOk('Consistencia TLM OK: tareas activas asignadas a trabajadores inactivos = 0.');
            } elseif ($inactiveAssigned === null) {
                tlmPfWarning('No se pudo validar trabajadores inactivos asignados.', 'Revisar manualmente antes de planear turnos.');
            } else {
                tlmPfWarning(
                    'Tareas activas asignadas a trabajadores inactivos: ' . (string)$inactiveAssigned . '.',
                    'Reasignar o cancelar tareas antes de usar operacion diaria.'
                );
            }
        }
    }

    if (tlmPfTableExists($pdo, $database, 'movimientos_caja')) {
        $predicate = tlmPfCajaTextPredicate($pdo, $database);
        if ($predicate === null) {
            tlmPfWarning('No se pudo validar Caja relacionada con TLM.', 'Confirmar manualmente ausencia de movimientos_caja de tareas.');
        } else {
            tlmPfReportZeroCount(
                'movimientos de Caja con referencia textual a tarea operativa',
                tlmPfCountScalar($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE ' . $predicate),
                'TLM no debe crear pagos, abonos ni movimientos de Caja.'
            );
        }
    }
}

$routes = tlmPfParseRoutes($routesPath);
$expectedRoutes = [
    ['method' => 'get', 'path' => 'tareas'],
    ['method' => 'get', 'path' => 'tareas/reporte'],
    ['method' => 'get', 'path' => 'tareas/crear'],
    ['method' => 'post', 'path' => 'tareas'],
    ['method' => 'get', 'path' => 'tareas/{id:[0-9]+}'],
    ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/asignar'],
    ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/iniciar'],
    ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/completar'],
    ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/cancelar'],
];

foreach ($expectedRoutes as $route) {
    if (tlmPfRouteExists($routes, $route['path'], $route['method'])) {
        tlmPfOk('Ruta TLM registrada: ' . strtoupper($route['method']) . ' /' . $route['path']);
    } else {
        tlmPfError(
            'Ruta TLM faltante: ' . strtoupper($route['method']) . ' /' . $route['path'],
            'No cerrar TLM hasta registrar rutas autorizadas.'
        );
    }
}

$forbiddenRoutes = [];
foreach ($routes as $route) {
    $path = trim((string)$route['path'], '/');
    $method = strtolower((string)$route['method']);
    $signature = strtoupper($method) . ' /' . $path;
    $allowed = false;
    foreach ($expectedRoutes as $expected) {
        if ($method === $expected['method'] && $path === trim($expected['path'], '/')) {
            $allowed = true;
            break;
        }
    }

    if ((strpos($path, 'tareas') !== false || (string)$route['controller'] === 'tarea') && !$allowed) {
        $forbiddenRoutes[] = $signature;
    }
}

if (empty($forbiddenRoutes)) {
    tlmPfOk('No hay rutas TLM fuera del contrato autorizado.');
} else {
    tlmPfError(
        'Rutas TLM fuera de alcance: ' . implode(', ', $forbiddenRoutes),
        'Retirar rutas no autorizadas antes de continuar.'
    );
}

$taskModelCode = is_file($taskModelPath) ? (string) file_get_contents($taskModelPath) : '';
$taskControllerCode = is_file($taskControllerPath) ? (string) file_get_contents($taskControllerPath) : '';
$taskPartialCode = is_file($taskPartialPath) ? (string) file_get_contents($taskPartialPath) : '';
$habitacionControllerCode = is_file($habitacionControllerPath) ? (string) file_get_contents($habitacionControllerPath) : '';
$trabajadorControllerCode = is_file($trabajadorControllerPath) ? (string) file_get_contents($trabajadorControllerPath) : '';

if (
    $taskModelCode !== ''
    && strpos($taskModelCode, 'function reporteReadOnlyPorHotel') !== false
    && strpos($taskModelCode, 'function listarPorEntidadHotel') !== false
    && strpos($taskModelCode, 'WHERE t.hotel_id = ?') !== false
    && strpos($taskModelCode, 'function crearParaHotel') !== false
    && strpos($taskModelCode, 'function cambiarEstadoManualParaHotel') !== false
) {
    tlmPfOk('TareaOperativa conserva reporte read-only, lecturas, alta manual y estados con hotel_id.');
} else {
    tlmPfError('TareaOperativa no muestra contrato TLM esperado.', 'Revisar modelo antes de continuar.');
}

if (
    $taskControllerCode !== ''
    && strpos($taskControllerCode, 'function reporteAction') !== false
    && strpos($taskControllerCode, 'validateCSRF') !== false
    && strpos($taskControllerCode, "require_permission('habitaciones.mantenimiento')") !== false
    && strpos($taskControllerCode, 'AuditService::record') !== false
) {
    tlmPfOk('TareaController conserva reporte read-only, CSRF, permiso conservador y auditoria.');
} else {
    tlmPfError('TareaController no muestra guardas TLM completas.', 'Validar CSRF, permisos y auditoria antes de continuar.');
}

if (
    $taskPartialCode !== ''
    && strpos($taskPartialCode, 'method="POST"') === false
    && strpos($taskPartialCode, 'csrf_field()') === false
    && strpos($taskPartialCode, 'storage_path') === false
    && strpos($taskPartialCode, "url('tareas/'") !== false
) {
    tlmPfOk('Partial contextual TLM-F es read-only.');
} else {
    tlmPfError('Partial contextual TLM-F no parece read-only.', 'Retirar formularios o rutas internas del partial contextual.');
}

$taskReportViewPath = $appRoot . '/app/views/tareas/reporte.php';
$taskReportCode = is_file($taskReportViewPath) ? (string) file_get_contents($taskReportViewPath) : '';
if (
    $taskReportCode !== ''
    && strpos($taskReportCode, 'Reporte operativo') !== false
    && strpos($taskReportCode, 'method="POST"') === false
    && strpos($taskReportCode, 'csrf_field()') === false
    && strpos($taskReportCode, 'movimientos_caja') === false
    && strpos($taskReportCode, "url('tareas/'") !== false
) {
    tlmPfOk('Vista de reporte TLM-I-A es read-only y no expone Caja.');
} else {
    tlmPfWarning(
        'Vista de reporte TLM-I-A no muestra contrato read-only completo.',
        'Asegurar reporte sin POST, sin Caja y con enlaces GET al detalle de tareas.'
    );
}

if (
    strpos($habitacionControllerCode, "listarPorEntidadHotel(\$hotelId, 'habitacion'") !== false
    && strpos($trabajadorControllerCode, "listarPorEntidadHotel(\$hotelId, 'trabajador'") !== false
) {
    tlmPfOk('Fichas de habitacion y trabajador consumen tareas contextuales por hotel.');
} else {
    tlmPfWarning(
        'No se detecto consumo contextual completo en habitacion/trabajador.',
        'Validar que las fichas llamen listarPorEntidadHotel() con entidad correcta.'
    );
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
