<?php
/**
 * Preflight Fase MANT-B/MANT-C/MANT-D/MANT-E/MANT-G para mantenimiento operativo existente.
 *
 * Solo lectura. No crea rutas, migraciones ni datos.
 * Valida guardas del POST existente de mantenimiento de habitaciones y
 * programacion/cancelacion de mantenimiento programado.
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

function mantOpLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function mantOpOk(string $message): void
{
    global $ok;
    $ok++;
    mantOpLine('OK', $message);
}

function mantOpWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    mantOpLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function mantOpError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    mantOpLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function mantOpQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function mantOpTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function mantOpCountRows(PDO $pdo, string $table): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . mantOpQuote($table));

    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

function mantOpCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function mantOpParseRoutes(string $path): array
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
            'path' => trim($match[2], '/'),
            'controller' => $match[3],
            'action' => $match[4],
        ];
    }

    return $routes;
}

function mantOpRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && (string)$route['path'] === $path) {
            return true;
        }
    }

    return false;
}

function mantOpRoutePatternExists(array $routes, string $pattern, string $method): bool
{
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && preg_match($pattern, (string)$route['path'])) {
            return true;
        }
    }

    return false;
}

function mantOpMethodBody(string $code, string $method): string
{
    $needle = 'function ' . $method . '(';
    $start = strpos($code, $needle);
    if ($start === false) {
        return '';
    }

    $next = strpos($code, "\n    public function ", $start + strlen($needle));
    if ($next === false) {
        $next = strpos($code, "\n}", $start + strlen($needle));
    }

    if ($next === false) {
        return substr($code, $start);
    }

    return substr($code, $start, $next - $start);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    mantOpError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/HabitacionController.php';
$reportesControllerPath = $appRoot . '/app/controllers/ReportesController.php';
$taskControllerPath = $appRoot . '/app/controllers/TareaController.php';
$servicePath = $appRoot . '/app/services/MantenimientoService.php';
$modelPath = $appRoot . '/app/models/Mantenimiento.php';
$taskModelPath = $appRoot . '/app/models/TareaOperativa.php';
$viewPath = $appRoot . '/app/views/habitaciones/ver.php';
$previewViewPath = $appRoot . '/app/views/reportes/mantenimiento-programado.php';

echo "Preflight Fase MANT-B/MANT-C-A/MANT-D-A/MANT-E-A/MANT-G-B-A - Mantenimiento operativo existente\n";
echo "=====================================================\n";

if (is_file($configPath)) {
    mantOpOk('Configuracion de base detectada.');
} else {
    mantOpError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
            getenv('DB_PORT') ?: ($dbConfig['port'] ?? '3306'),
            $database,
            $dbConfig['charset'] ?? 'utf8mb4'
        );
        $pdo = new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->exec('START TRANSACTION READ ONLY');
        mantOpOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        mantOpError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    foreach (['mantenimientos_habitaciones', 'habitaciones'] as $table) {
        if (mantOpTableExists($pdo, $database, $table)) {
            mantOpOk('Fuente MANT-B disponible: ' . $table . ' (' . mantOpCountRows($pdo, $table) . ' registros).');
        } else {
            mantOpError('Fuente MANT-B faltante: ' . $table . '.', 'No operar mantenimiento hasta reconciliar la tabla fuente.');
        }
    }

    $duplicateActive = mantOpCountScalar(
        $pdo,
        "SELECT COUNT(*) FROM (
            SELECT hotel_id, habitacion_id
            FROM mantenimientos_habitaciones
            WHERE estado = 'en_proceso'
            GROUP BY hotel_id, habitacion_id
            HAVING COUNT(*) > 1
        ) duplicados"
    );
    if ($duplicateActive === 0) {
        mantOpOk('Mantenimientos en proceso duplicados por habitacion/hotel = 0.');
    } else {
        mantOpError('Mantenimientos en proceso duplicados por habitacion/hotel = ' . (string)$duplicateActive . '.', 'No permitir nuevas altas hasta reconciliar duplicados.');
    }

    $activeWrongRoom = mantOpCountScalar(
        $pdo,
        "SELECT COUNT(*)
         FROM mantenimientos_habitaciones m
         JOIN habitaciones h ON h.id = m.habitacion_id AND h.hotel_id = m.hotel_id
         WHERE m.estado = 'en_proceso'
           AND h.estado <> 'mantenimiento'"
    );
    if ($activeWrongRoom === 0) {
        mantOpOk('Mantenimientos en proceso con habitacion fuera de mantenimiento = 0.');
    } else {
        mantOpError('Mantenimientos en proceso con habitacion fuera de mantenimiento = ' . (string)$activeWrongRoom . '.', 'Reconciliar estado de habitacion/mantenimiento antes de automatizar.');
    }

    $roomWithoutActive = mantOpCountScalar(
        $pdo,
        "SELECT COUNT(*)
         FROM habitaciones h
         LEFT JOIN mantenimientos_habitaciones m
           ON m.habitacion_id = h.id
          AND m.hotel_id = h.hotel_id
          AND m.estado = 'en_proceso'
         WHERE h.estado = 'mantenimiento'
           AND m.id IS NULL"
    );
    if ($roomWithoutActive === 0) {
        mantOpOk('Habitaciones en mantenimiento sin registro en proceso = 0.');
    } else {
        mantOpWarning('Habitaciones en mantenimiento sin registro en proceso = ' . (string)$roomWithoutActive . '.', 'Revisar historico antes de automatizar cierres masivos.');
    }

    $scheduledOverlaps = mantOpCountScalar(
        $pdo,
        "SELECT COUNT(*) FROM (
            SELECT a.id
            FROM mantenimientos_habitaciones a
            INNER JOIN mantenimientos_habitaciones b
              ON b.hotel_id = a.hotel_id
             AND b.habitacion_id = a.habitacion_id
             AND b.id > a.id
             AND b.programado = 1
             AND b.estado = 'programado'
             AND b.fecha_programada IS NOT NULL
             AND b.fecha_programada <= COALESCE(a.fecha_programada_fin, a.fecha_programada)
             AND COALESCE(b.fecha_programada_fin, b.fecha_programada) >= a.fecha_programada
            WHERE a.programado = 1
              AND a.estado = 'programado'
              AND a.fecha_programada IS NOT NULL
        ) solapes"
    );
    if ($scheduledOverlaps === 0) {
        mantOpOk('Mantenimientos programados solapados por habitacion/hotel = 0.');
    } else {
        mantOpWarning('Mantenimientos programados solapados por habitacion/hotel = ' . (string)$scheduledOverlaps . '.', 'Revisar solapes historicos antes de automatizar disponibilidad.');
    }

    if (mantOpTableExists($pdo, $database, 'tareas_operativas')) {
        $tasksMissingMaintenance = mantOpCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM tareas_operativas t
             LEFT JOIN mantenimientos_habitaciones m
               ON m.id = t.mantenimiento_id
             WHERE t.mantenimiento_id IS NOT NULL
               AND m.id IS NULL"
        );
        if ($tasksMissingMaintenance === 0) {
            mantOpOk('Tareas vinculadas a mantenimiento inexistente = 0.');
        } else {
            mantOpError('Tareas vinculadas a mantenimiento inexistente = ' . (string)$tasksMissingMaintenance . '.', 'Reconciliar mantenimiento_id antes de mostrar enlaces contextuales.');
        }

        $tasksCrossHotelMaintenance = mantOpCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM tareas_operativas t
             JOIN mantenimientos_habitaciones m
               ON m.id = t.mantenimiento_id
             WHERE t.mantenimiento_id IS NOT NULL
               AND m.hotel_id <> t.hotel_id"
        );
        if ($tasksCrossHotelMaintenance === 0) {
            mantOpOk('Tareas vinculadas a mantenimiento de otro hotel = 0.');
        } else {
            mantOpError('Tareas vinculadas a mantenimiento de otro hotel = ' . (string)$tasksCrossHotelMaintenance . '.', 'Bloquear vistas contextuales hasta alinear hotel_id.');
        }

        $activeTaskDuplicates = mantOpCountScalar(
            $pdo,
            "SELECT COUNT(*) FROM (
                SELECT hotel_id, mantenimiento_id
                FROM tareas_operativas
                WHERE mantenimiento_id IS NOT NULL
                  AND estado IN ('pendiente', 'asignada', 'en_proceso')
                GROUP BY hotel_id, mantenimiento_id
                HAVING COUNT(*) > 1
            ) duplicados"
        );
        if ($activeTaskDuplicates === 0) {
            mantOpOk('Mantenimientos con mas de una tarea activa vinculada = 0.');
        } else {
            mantOpError('Mantenimientos con mas de una tarea activa vinculada = ' . (string)$activeTaskDuplicates . '.', 'Cancelar/cerrar duplicados antes de crear nuevas tareas desde mantenimiento.');
        }
    } else {
        mantOpWarning('No existe tareas_operativas; MANT-G-A contextual queda sin datos.', 'Aplicar TLM-A antes de usar tareas vinculadas a mantenimiento.');
    }
}

$routes = mantOpParseRoutes($routesPath);
$requiredRoutes = [
    ['label' => 'POST /habitaciones/{id}/mantenimiento', 'pattern' => '/^habitaciones\/\{id:[^}]+\}\/mantenimiento$/'],
    ['label' => 'POST /habitaciones/{id}/programar-mantenimiento', 'pattern' => '/^habitaciones\/\{id:[^}]+\}\/programar-mantenimiento$/'],
];

foreach ($requiredRoutes as $route) {
    if (mantOpRoutePatternExists($routes, $route['pattern'], 'post')) {
        mantOpOk('Ruta MANT-B registrada: ' . $route['label'] . '.');
    } else {
        mantOpError('Ruta MANT-B faltante: ' . $route['label'] . '.', 'Restaurar ruta POST existente con CSRF y permisos.');
    }
}

if (mantOpRoutePatternExists($routes, '/^habitaciones\/cancelar-mantenimiento-programado\/\{id:[^}]+\}$/', 'post')) {
    mantOpOk('Ruta MANT-B registrada: POST /habitaciones/cancelar-mantenimiento-programado/{id}.');
} else {
    mantOpError('Ruta MANT-B faltante: POST /habitaciones/cancelar-mantenimiento-programado/{id}.', 'Restaurar ruta POST existente con CSRF y permisos.');
}

if (mantOpRoutePatternExists($routes, '/^habitaciones\/activar-mantenimiento-programado\/\{id:[^}]+\}$/', 'post')) {
    mantOpOk('Ruta MANT-E-A registrada: POST /habitaciones/activar-mantenimiento-programado/{id}.');
} else {
    mantOpError('Ruta MANT-E-A faltante: POST /habitaciones/activar-mantenimiento-programado/{id}.', 'Restaurar activacion manual individual con CSRF y permisos.');
}

if (mantOpRoutePatternExists($routes, '/^reportes\/mantenimiento-programado$/', 'get')) {
    mantOpOk('Ruta MANT-D-A registrada: GET /reportes/mantenimiento-programado.');
} else {
    mantOpError('Ruta MANT-D-A faltante: GET /reportes/mantenimiento-programado.', 'Restaurar preview read-only bajo reportes.');
}

if (mantOpRoutePatternExists($routes, '/^tareas\/desde-mantenimiento\/\{id:[^}]+\}$/', 'post')) {
    mantOpOk('Ruta MANT-G-B-A registrada: POST /tareas/desde-mantenimiento/{id}.');
} else {
    mantOpError('Ruta MANT-G-B-A faltante: POST /tareas/desde-mantenimiento/{id}.', 'Registrar solo creacion manual individual con CSRF y permisos.');
}

$controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
$reportesControllerCode = is_file($reportesControllerPath) ? (string) file_get_contents($reportesControllerPath) : '';
$taskControllerCode = is_file($taskControllerPath) ? (string) file_get_contents($taskControllerPath) : '';
$serviceCode = is_file($servicePath) ? (string) file_get_contents($servicePath) : '';
$modelCode = is_file($modelPath) ? (string) file_get_contents($modelPath) : '';
$taskModelCode = is_file($taskModelPath) ? (string) file_get_contents($taskModelPath) : '';
$viewCode = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
$previewViewCode = is_file($previewViewPath) ? (string) file_get_contents($previewViewPath) : '';
$mantBody = mantOpMethodBody($controllerCode, 'mantenimientoAction');

if (
    $mantBody !== ''
    && strpos($mantBody, 'validateCSRF()') !== false
    && strpos($mantBody, "requirePermission('habitaciones.mantenimiento')") !== false
    && strpos($mantBody, "['iniciar', 'finalizar']") !== false
    && strpos($mantBody, 'MantenimientoService') !== false
    && strpos($mantBody, 'iniciarParaHotel') !== false
    && strpos($mantBody, 'finalizarParaHotel') !== false
    && strpos($serviceCode, 'Mantenimiento::getTipos()') !== false
    && strpos($serviceCode, 'Mantenimiento::getPrioridades()') !== false
    && strpos($serviceCode, "\$motivo === ''") !== false
    && strpos($serviceCode, 'SELECT COUNT(*) FROM mantenimientos_habitaciones') !== false
    && strpos($serviceCode, 'WHERE id = ? AND hotel_id = ?') !== false
    && strpos($mantBody, '$hotelId') !== false
    && strpos($serviceCode, 'movimientos_caja') === false
    && strpos($mantBody, 'movimientos_caja') === false
) {
    mantOpOk('HabitacionController delega a MantenimientoService con CSRF, permiso, validaciones, bloqueo de duplicados y hotel_id.');
} else {
    mantOpError('HabitacionController/MantenimientoService no muestran todas las guardas MANT-B.', 'Revisar CSRF, permiso, delegacion, tipo/prioridad/motivo, duplicados y hotel_id.');
}

$programarBody = mantOpMethodBody($controllerCode, 'programarMantenimientoAction');
if (
    $programarBody !== ''
    && strpos($programarBody, 'validateCSRF()') !== false
    && strpos($programarBody, "requirePermission('habitaciones.mantenimiento')") !== false
    && strpos($programarBody, "DateTimeImmutable::createFromFormat('!Y-m-d'") !== false
    && strpos($programarBody, 'Mantenimiento::getTipos()') !== false
    && strpos($programarBody, 'Mantenimiento::getPrioridades()') !== false
    && strpos($programarBody, '$motivo ===') !== false
    && strpos($programarBody, 'tieneProgramadoSolapado') !== false
    && strpos($programarBody, '$hotelId') !== false
    && strpos($programarBody, 'movimientos_caja') === false
) {
    mantOpOk('HabitacionController::programarMantenimientoAction conserva CSRF, permiso, fechas, catalogos, motivo, solapes y hotel_id.');
} else {
    mantOpError('HabitacionController::programarMantenimientoAction no muestra guardas MANT-C-A completas.', 'Revisar fechas, catalogos, motivo, solapes, hotel_id, CSRF y permiso.');
}

$cancelarBody = mantOpMethodBody($controllerCode, 'cancelarMantenimientoProgramadoAction');
if (
    $cancelarBody !== ''
    && strpos($cancelarBody, 'validateCSRF()') !== false
    && strpos($cancelarBody, "requirePermission('habitaciones.mantenimiento')") !== false
    && strpos($cancelarBody, 'habitacionModel->find') !== false
    && strpos($cancelarBody, 'cancelarProgramado') !== false
    && strpos($cancelarBody, '$motivo ===') !== false
    && strpos($cancelarBody, 'movimientos_caja') === false
) {
    mantOpOk('HabitacionController::cancelarMantenimientoProgramadoAction conserva CSRF, permiso, habitacion scoped y motivo normalizado.');
} else {
    mantOpError('HabitacionController::cancelarMantenimientoProgramadoAction no muestra guardas MANT-C-A completas.', 'Revisar CSRF, permiso, habitacion del hotel, motivo y ausencia de Caja.');
}

if (
    $modelCode !== ''
    && strpos($modelCode, 'class Mantenimiento') !== false
    && strpos($modelCode, 'function find') !== false
    && strpos($modelCode, 'WHERE {$this->primaryKey} = ? AND hotel_id = ?') !== false
    && strpos($modelCode, 'function update') !== false
    && strpos($modelCode, 'WHERE {$this->primaryKey} = ? AND hotel_id = ?') !== false
    && strpos($modelCode, 'function tieneProgramadoSolapado') !== false
    && strpos($modelCode, 'COALESCE(fecha_programada_fin, fecha_programada) >= ?') !== false
) {
    mantOpOk('Mantenimiento model mantiene find/update scoped y chequeo de solapes programados por hotel_id.');
} else {
    mantOpError('Mantenimiento model no muestra guardas tenant-safe completas.', 'Revisar find/update scoped y tieneProgramadoSolapado.');
}

$previewControllerBody = mantOpMethodBody($reportesControllerCode, 'mantenimientoProgramadoAction');
$previewModelBody = mantOpMethodBody($modelCode, 'previewProgramados');
$activateControllerBody = mantOpMethodBody($controllerCode, 'activarMantenimientoProgramadoAction');
$activateModelBody = mantOpMethodBody($modelCode, 'activarProgramadoManual');
if (
    $previewControllerBody !== ''
    && strpos($previewControllerBody, "require_hotel_module('mantenimiento')") !== false
    && strpos($previewControllerBody, 'previewProgramados') !== false
    && strpos($previewControllerBody, 'TareaOperativa') !== false
    && strpos($previewControllerBody, "listarPorEntidadHotel(\$hotelId, 'mantenimiento'") !== false
    && stripos($previewControllerBody, 'activarMantenimientosPendientes') === false
    && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\b/i', $previewControllerBody)
) {
    mantOpOk('ReportesController::mantenimientoProgramadoAction exige Mantenimiento, es GET/read-only, anexa tareas y no activa pendientes.');
} else {
    mantOpError('ReportesController::mantenimientoProgramadoAction no muestra contrato Mantenimiento/read-only MANT-D-A/MANT-G-A.', 'Revisar gate mantenimiento, previewProgramados, tareas vinculadas read-only y ausencia de escrituras.');
}

if (
    $taskModelCode !== ''
    && strpos($taskModelCode, "'mantenimiento' => 't.mantenimiento_id'") !== false
    && strpos($taskModelCode, 'function buscarTareaActivaPorMantenimientoHotel') !== false
    && strpos($taskModelCode, 'function crearDesdeMantenimientoParaHotel') !== false
    && strpos($taskModelCode, "'mantenimiento'") !== false
    && strpos($taskModelCode, 'function listarPorEntidadHotel') !== false
    && strpos($taskModelCode, 'WHERE t.hotel_id = ?') !== false
    && stripos($taskModelCode, 'movimientos_caja') === false
) {
    mantOpOk('TareaOperativa MANT-G-A/MANT-G-B-A permite lectura y creacion manual desde mantenimiento con hotel_id.');
} else {
    mantOpError('TareaOperativa MANT-G-A/MANT-G-B-A no muestra contrato de mantenimiento.', 'Agregar lectura/creacion por mantenimiento_id, scoped por hotel_id y sin Caja.');
}

if (
    $taskControllerCode !== ''
    && strpos($taskControllerCode, 'function crearDesdeMantenimientoAction') !== false
    && strpos($taskControllerCode, 'crearDesdeMantenimientoParaHotel') !== false
    && strpos($taskControllerCode, 'validateCSRF()') !== false
    && strpos($taskControllerCode, "require_permission('habitaciones.mantenimiento')") !== false
    && strpos($taskControllerCode, 'AuditService::record') !== false
) {
    mantOpOk('TareaController MANT-G-B-A crea tarea desde mantenimiento con CSRF, permiso y auditoria.');
} else {
    mantOpError('TareaController MANT-G-B-A no muestra guardas completas.', 'Revisar CSRF, permiso, auditoria y metodo central.');
}

if (
    $previewModelBody !== ''
    && strpos($previewModelBody, 'm.hotel_id = ?') !== false
    && strpos($previewModelBody, 'reservaciones_conflicto') !== false
    && stripos($previewModelBody, 'activarMantenimientosPendientes') === false
    && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\b/i', $previewModelBody)
) {
    mantOpOk('Mantenimiento::previewProgramados es read-only, scoped por hotel_id y detecta conflictos.');
} else {
    mantOpError('Mantenimiento::previewProgramados no muestra guardas read-only MANT-D-A.', 'Revisar hotel_id, conflictos y ausencia de escrituras.');
}

if (
    $viewCode !== ''
    && strpos($viewCode, "url('habitaciones/' . \$habitacion_id . '/mantenimiento')") !== false
    && strpos($viewCode, "url('habitaciones/' . \$habitacion_id . '/programar-mantenimiento')") !== false
    && strpos($viewCode, "url('habitaciones/cancelar-mantenimiento-programado/'") !== false
    && substr_count($viewCode, 'csrf_field()') >= 3
) {
    mantOpOk('habitaciones/ver.php conserva formularios MANT-B con CSRF.');
} else {
    mantOpError('habitaciones/ver.php no muestra formularios MANT-B esperados con CSRF.', 'Revisar action/method/csrf de mantenimiento.');
}

if (
    $previewViewCode !== ''
    && strpos($previewViewCode, "url('habitaciones/'") !== false
    && strpos($previewViewCode, "url('habitaciones/activar-mantenimiento-programado/'") !== false
    && strpos($previewViewCode, "can('habitaciones.mantenimiento')") !== false
    && strpos($previewViewCode, 'tareas_vinculadas') !== false
    && strpos($previewViewCode, 'tarea_activa_vinculada') !== false
    && strpos($previewViewCode, "url('tareas/'") !== false
    && strpos($previewViewCode, "url('tareas/desde-mantenimiento/'") !== false
    && strpos($previewViewCode, 'method="POST"') !== false
    && strpos($previewViewCode, 'csrf_field()') !== false
    && stripos($previewViewCode, 'activarMantenimientosPendientes') === false
) {
    mantOpOk('Vista MANT-D-A/MANT-E-A/MANT-G-B-A mantiene preview, accion manual existente y tareas vinculadas read-only.');
} else {
    mantOpError('Vista MANT-D-A/MANT-E-A/MANT-G-A no muestra contrato esperado.', 'Revisar preview GET, boton existente con permiso/CSRF, tareas vinculadas read-only y ausencia de activacion automatica.');
}

if (
    $activateControllerBody !== ''
    && strpos($activateControllerBody, 'validateCSRF()') !== false
    && strpos($activateControllerBody, "requirePermission('habitaciones.mantenimiento')") !== false
    && strpos($activateControllerBody, 'activarProgramadoManual') !== false
    && strpos($activateControllerBody, 'registrarAuditoriaMantenimientoProgramado') !== false
    && stripos($activateControllerBody, 'activarMantenimientosPendientes') === false
    && stripos($activateControllerBody, 'movimientos_caja') === false
) {
    mantOpOk('HabitacionController::activarMantenimientoProgramadoAction tiene CSRF, permiso, auditoria y no usa activacion masiva.');
} else {
    mantOpError('HabitacionController::activarMantenimientoProgramadoAction no muestra guardas MANT-E-A.', 'Revisar CSRF, permiso, auditoria, metodo central y ausencia de Caja/activacion masiva.');
}

if (
    $activateModelBody !== ''
    && strpos($activateModelBody, 'beginTransaction') !== false
    && strpos($activateModelBody, 'FOR UPDATE') !== false
    && strpos($activateModelBody, "estado = 'en_proceso'") !== false
    && strpos($activateModelBody, "estado = 'mantenimiento'") !== false
    && strpos($activateModelBody, "r.estado IN ('confirmada', 'checked_in')") !== false
    && strpos($activateModelBody, "m.hotel_id = ?") !== false
    && stripos($activateModelBody, 'activarMantenimientosPendientes') === false
    && stripos($activateModelBody, 'movimientos_caja') === false
) {
    mantOpOk('Mantenimiento::activarProgramadoManual es transaccional, scoped, valida conflictos y no toca Caja.');
} else {
    mantOpError('Mantenimiento::activarProgramadoManual no muestra guardas MANT-E-A completas.', 'Revisar transaccion, FOR UPDATE, hotel_id, conflictos, habitacion disponible y ausencia de Caja.');
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
