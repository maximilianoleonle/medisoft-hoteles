<?php
/**
 * Preflight Fase 7A-A/7B-B/7B-C-A/7B-D-A/7B-D-B-A/7B-D-C-A/7B-D-D-A para Cuentas por Cobrar.
 *
 * No crea rutas, migraciones ni datos.
 * Valida que CxC derivada siga protegida por hotel, que CxC operativa sea
 * consultable, y que 7B-C-A solo permita generar CxC manual desde reservacion
 * elegible. 7B-D-A debe ser simulador GET/read-only. 7B-D-B-A debe tener tipo
 * semantico COBRO disponible. 7B-D-C-A permite cobro CxC con Caja solo mediante
 * servicio transaccional, CSRF, token y prueba rollback. 7B-D-D-A permite
 * reversion de cobro CxC con Caja mediante CANCELACION + gasto controlado.
 * Sin escrituras en pagos, abonos, facturacion ni reservaciones.
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

function cxcPfLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function cxcPfOk(string $message): void
{
    global $ok;
    $ok++;
    cxcPfLine('OK', $message);
}

function cxcPfWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    cxcPfLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cxcPfError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    cxcPfLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function cxcPfTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function cxcPfCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int)$stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function cxcPfReportZero(string $label, ?int $count, string $recommendation, bool $warningOnly = false): void
{
    if ($count === null) {
        cxcPfWarning('No se pudo validar CxC: ' . $label . '.', 'Revisar manualmente antes de operar CxC.');
        return;
    }

    if ($count === 0) {
        cxcPfOk('Consistencia CxC OK: ' . $label . ' = 0.');
        return;
    }

    if ($warningOnly) {
        cxcPfWarning('Consistencia CxC pendiente de revisar: ' . $label . ' = ' . (string)$count . '.', $recommendation);
        return;
    }

    cxcPfError('Consistencia CxC fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function cxcPfParseRoutes(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $raw = (string)file_get_contents($path);
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

function cxcPfRouteExists(array $routes, string $path, string $method): bool
{
    $path = trim($path, '/');
    foreach ($routes as $route) {
        if (strtolower((string)$route['method']) === strtolower($method) && (string)$route['path'] === $path) {
            return true;
        }
    }

    return false;
}

function cxcPfContainsForbiddenWrites(string $code): bool
{
    if ($code === '') {
        return false;
    }

    return (bool)preg_match(
        '/\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(?:movimientos_caja|cortes_caja|cajas|reservaciones|reservacion_pagos|reservacion_abonos|solicitudes_factura)\b/i',
        $code
    );
}

function cxcPfContainsForbiddenCobroServiceWrites(string $code): bool
{
    if ($code === '') {
        return false;
    }

    return (bool)preg_match(
        '/\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(?:cortes_caja|cajas|reservaciones|reservacion_pagos|reservacion_abonos|solicitudes_factura)\b/i',
        $code
    );
}

function cxcPfContainsForbiddenReversionServiceWrites(string $code): bool
{
    if ($code === '') {
        return false;
    }

    return (bool)preg_match(
        '/\b(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(?:cortes_caja|cajas|reservaciones|reservacion_pagos|reservacion_abonos|solicitudes_factura)\b/i',
        $code
    );
}

$appRoot = dirname(__DIR__, 2);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllerPath = $appRoot . '/app/controllers/CuentaPorCobrarController.php';
$modelPath = $appRoot . '/app/models/CuentaPorCobrar.php';
$cobroServicePath = $appRoot . '/app/services/CuentaPorCobrarCobroService.php';
$reversionServicePath = $appRoot . '/app/services/CuentaPorCobrarReversionCobroService.php';
$viewPath = $appRoot . '/app/views/cuentas_por_cobrar/index.php';
$operativasViewPath = $appRoot . '/app/views/cuentas_por_cobrar/operativas.php';
$operativaDetailViewPath = $appRoot . '/app/views/cuentas_por_cobrar/ver_operativa.php';
$simuladorCajaViewPath = $appRoot . '/app/views/cuentas_por_cobrar/simulador_caja.php';
$cobroRollbackToolPath = $appRoot . '/tools/saas/probar_cobro_cxc_caja.php';
$reversionRollbackToolPath = $appRoot . '/tools/saas/probar_reversion_cobro_cxc_caja.php';
$sidebarPath = $appRoot . '/app/views/layout/sidebar.php';

echo "Preflight Fase 7A-A/7B-B/7B-C-A/7B-D-A/7B-D-B-A/7B-D-C-A/7B-D-D-A - Cuentas por cobrar\n";
echo "=====================================================\n";

if (is_file($configPath)) {
    cxcPfOk('Configuracion de base detectada.');
} else {
    cxcPfError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
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
        cxcPfOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        cxcPfError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    foreach (['reservaciones', 'huespedes', 'reservacion_pagos', 'reservacion_abonos', 'solicitudes_factura'] as $table) {
        if (cxcPfTableExists($pdo, $database, $table)) {
            cxcPfOk('Tabla fuente disponible: ' . $table . '.');
        } else {
            cxcPfError('Falta tabla fuente CxC: ' . $table . '.', 'No habilitar reporte CxC hasta reconciliar fuentes.');
        }
    }

    foreach (['cuentas_por_cobrar', 'cuentas_por_cobrar_movimientos'] as $table) {
        if (cxcPfTableExists($pdo, $database, $table)) {
            cxcPfOk('Tabla 7B-B disponible: ' . $table . '.');
        } else {
            cxcPfWarning('Tabla 7B-B no disponible: ' . $table . '.', 'Aplicar 7B-A antes de exponer CxC operativa.');
        }
    }

    foreach (['cajas', 'cortes_caja'] as $table) {
        if (cxcPfTableExists($pdo, $database, $table)) {
            cxcPfOk('Tabla Caja disponible para simulador 7B-D-A: ' . $table . '.');
        } else {
            cxcPfWarning('Tabla Caja no disponible para simulador 7B-D-A: ' . $table . '.', 'El simulador debe permanecer bloqueado si falta Caja.');
        }
    }

    cxcPfReportZero(
        'reservaciones con hotel_id nulo',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservaciones WHERE hotel_id IS NULL'),
        'Completar hotel_id antes de exponer CxC.'
    );
    cxcPfReportZero(
        'pagos con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservacion_pagos p LEFT JOIN reservaciones r ON r.id = p.reservacion_id AND r.hotel_id = p.hotel_id WHERE p.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar pagos historicos antes de convertir CxC en operativa; el reporte read-only los excluye por hotel/reservacion.',
        true
    );
    cxcPfReportZero(
        'abonos con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM reservacion_abonos a LEFT JOIN reservaciones r ON r.id = a.reservacion_id AND r.hotel_id = a.hotel_id WHERE a.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar abonos antes de usar CxC.'
    );
    cxcPfReportZero(
        'solicitudes_factura con reservacion inexistente o de otro hotel',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM solicitudes_factura sf LEFT JOIN reservaciones r ON r.id = sf.reservacion_id AND r.hotel_id = sf.hotel_id WHERE sf.hotel_id IS NOT NULL AND r.id IS NULL'),
        'Reconciliar facturacion historica antes de convertir CxC en operativa; el reporte read-only solo enlaza solicitudes scoped por hotel/reservacion.',
        true
    );
    cxcPfReportZero(
        'reservaciones con saldo estimado negativo',
        cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM (SELECT r.id, r.precio_total - COALESCE(p.total, 0) - COALESCE(a.total, 0) AS saldo FROM reservaciones r LEFT JOIN (SELECT hotel_id, reservacion_id, SUM(monto) AS total FROM reservacion_pagos GROUP BY hotel_id, reservacion_id) p ON p.hotel_id = r.hotel_id AND p.reservacion_id = r.id LEFT JOIN (SELECT hotel_id, reservacion_id, SUM(monto) AS total FROM reservacion_abonos GROUP BY hotel_id, reservacion_id) a ON a.hotel_id = r.hotel_id AND a.reservacion_id = r.id) x WHERE x.saldo < -0.009'),
        'Revisar excedentes antes de operar CxC real.',
        true
    );

    if (cxcPfTableExists($pdo, $database, 'cuentas_por_cobrar')) {
        cxcPfReportZero(
            'cuentas_por_cobrar sin hotel_id',
            cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar WHERE hotel_id IS NULL'),
            'No operar CxC sin hotel_id.'
        );
        cxcPfReportZero(
            'cuentas_por_cobrar con saldo mayor al total',
            cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar WHERE saldo > total'),
            'Revisar saldos CxC antes de permitir escrituras.'
        );
        cxcPfReportZero(
            'cuentas_por_cobrar con importes negativos',
            cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar WHERE saldo < 0 OR total < 0'),
            'No permitir importes negativos en CxC operativa.'
        );
        cxcPfReportZero(
            'CxC duplicadas por reservacion',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, origen_tipo, origen_id, COUNT(*) AS total FROM cuentas_por_cobrar WHERE origen_tipo = 'reservacion' AND origen_id IS NOT NULL GROUP BY hotel_id, origen_tipo, origen_id HAVING total > 1) x"),
            'La generacion manual debe ser idempotente por hotel/reservacion.'
        );
        cxcPfReportZero(
            'CxC de reservacion inexistente o de otro hotel',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar c LEFT JOIN reservaciones r ON r.id = c.reservacion_id AND r.hotel_id = c.hotel_id WHERE c.origen_tipo = 'reservacion' AND c.reservacion_id IS NOT NULL AND r.id IS NULL"),
            'Revisar CxC vinculadas antes de permitir cobros.'
        );
    }

    if (cxcPfTableExists($pdo, $database, 'cuentas_por_cobrar_movimientos')) {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_TYPE
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db
               AND TABLE_NAME = 'cuentas_por_cobrar_movimientos'
               AND COLUMN_NAME = 'tipo_movimiento'
             LIMIT 1"
        );
        $stmt->execute(['db' => $database]);
        $tipoMovimientoColumn = (string)($stmt->fetchColumn() ?: '');

        if (strpos($tipoMovimientoColumn, "'COBRO'") !== false) {
            cxcPfOk('Enum CxC 7B-D-B-A disponible: tipo_movimiento incluye COBRO.');
        } else {
            cxcPfError('Enum CxC 7B-D-B-A incompleto: falta tipo COBRO.', 'Aplicar migracion aditiva 7B-D-B-A antes de implementar cobros.');
        }

        if (cxcPfTableExists($pdo, $database, 'migrations')) {
            $migrationCount = cxcPfCountScalar(
                $pdo,
                "SELECT COUNT(*) FROM migrations WHERE nombre = '20260618_002_fase_7b_d_b_a_cxc_movimiento_cobro_enum.sql' AND estado = 'ejecutada'"
            );

            if ($migrationCount === 1) {
                cxcPfOk('Migracion 7B-D-B-A registrada como ejecutada.');
            } else {
                cxcPfError('Migracion 7B-D-B-A no registrada como ejecutada.', 'Revisar tabla migrations antes de continuar a cobro real.');
            }
        }

        cxcPfReportZero(
            'movimientos CxC tipo COBRO persistentes',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'COBRO'"),
            'Validar que todo COBRO persistente tenga traza Caja y auditoria esperada.',
            true
        );
        cxcPfReportZero(
            'movimientos CxC COBRO sin ingreso Caja asociado',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'ingreso' AND mc.categoria = 'Cobro CxC' AND mc.monto = m.monto AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE m.tipo_movimiento = 'COBRO' AND mc.id IS NULL"),
            'Cada COBRO CxC persistente debe tener un ingreso Caja creado por el servicio.'
        );
        cxcPfReportZero(
            'ingresos Caja CxC sin movimiento COBRO asociado',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_cobrar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'COBRO' AND m.monto = mc.monto AND (mc.referencia = CONCAT('CXC-', m.cuenta_por_cobrar_id, '-MOV-', m.id) OR (m.referencia IS NOT NULL AND m.referencia <> '' AND mc.referencia = m.referencia)) WHERE mc.tipo = 'ingreso' AND mc.categoria = 'Cobro CxC' AND m.id IS NULL"),
            'Todo ingreso Caja de Cobro CxC debe apuntar a un movimiento CxC COBRO.'
        );
        cxcPfReportZero(
            'movimientos CxC tipo CANCELACION persistentes',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%'"),
            'Validar que toda CANCELACION CxC persistente tenga gasto Caja y auditoria esperada.',
            true
        );
        cxcPfReportZero(
            'movimientos CxC CANCELACION sin gasto Caja asociado',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN movimientos_caja mc ON mc.hotel_id = m.hotel_id AND mc.tipo = 'gasto' AND mc.categoria = 'Reversion Cobro CxC' AND mc.monto = m.monto AND mc.referencia = m.referencia WHERE m.tipo_movimiento = 'CANCELACION' AND m.referencia LIKE 'REV-CXC-%' AND mc.id IS NULL"),
            'Cada CANCELACION CxC de reversion debe tener gasto Caja asociado.'
        );
        cxcPfReportZero(
            'gastos Caja Reversion Cobro CxC sin CANCELACION asociada',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja mc LEFT JOIN cuentas_por_cobrar_movimientos m ON m.hotel_id = mc.hotel_id AND m.tipo_movimiento = 'CANCELACION' AND m.monto = mc.monto AND m.referencia = mc.referencia WHERE mc.tipo = 'gasto' AND mc.categoria = 'Reversion Cobro CxC' AND m.id IS NULL"),
            'Todo gasto Caja de reversion debe apuntar a una CANCELACION CxC.'
        );
        cxcPfReportZero(
            'cobros CxC con doble reversion',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM (SELECT hotel_id, cuenta_por_cobrar_id, referencia, COUNT(*) AS total FROM cuentas_por_cobrar_movimientos WHERE tipo_movimiento = 'CANCELACION' AND referencia LIKE 'REV-CXC-%' GROUP BY hotel_id, cuenta_por_cobrar_id, referencia HAVING total > 1) x"),
            'No permitir doble reversion del mismo movimiento COBRO.'
        );

        cxcPfReportZero(
            'movimientos CxC sin cuenta existente',
            cxcPfCountScalar($pdo, 'SELECT COUNT(*) FROM cuentas_por_cobrar_movimientos m LEFT JOIN cuentas_por_cobrar c ON c.id = m.cuenta_por_cobrar_id AND c.hotel_id = m.hotel_id WHERE c.id IS NULL'),
            'Revisar trazabilidad CxC antes de permitir movimientos.'
        );
        cxcPfReportZero(
            'CxC de reservacion sin movimiento inicial',
            cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM cuentas_por_cobrar c LEFT JOIN cuentas_por_cobrar_movimientos m ON m.cuenta_por_cobrar_id = c.id AND m.hotel_id = c.hotel_id AND m.tipo_movimiento = 'CREACION' WHERE c.origen_tipo = 'reservacion' AND m.id IS NULL"),
            'Cada CxC generada manualmente debe tener movimiento CREACION.'
        );
    }

    cxcPfReportZero(
        'movimientos de Caja CxC persistentes',
        cxcPfCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE categoria LIKE '%CxC%' OR descripcion LIKE '%CxC%' OR referencia LIKE '%CxC%'"),
        'Validar que cada movimiento Caja CxC provenga del servicio 7B-D-C-A y tenga COBRO asociado.',
        true
    );
}

$routes = cxcPfParseRoutes($routesPath);
if (cxcPfRouteExists($routes, 'cuentas-por-cobrar', 'get')) {
    cxcPfOk('Ruta CxC registrada: GET /cuentas-por-cobrar.');
} else {
    cxcPfError('No esta registrada GET /cuentas-por-cobrar.', 'Registrar solo ruta GET para 7A-A.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/operativas', 'get')) {
    cxcPfOk('Ruta CxC operativa registrada: GET /cuentas-por-cobrar/operativas.');
} else {
    cxcPfError('No esta registrada GET /cuentas-por-cobrar/operativas.', 'Registrar solo ruta GET para 7B-B.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/operativas/{id:[0-9]+}', 'get')) {
    cxcPfOk('Ruta detalle CxC operativa registrada: GET /cuentas-por-cobrar/operativas/{id}.');
} else {
    cxcPfError('No esta registrada GET /cuentas-por-cobrar/operativas/{id}.', 'Registrar solo detalle GET para 7B-B.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/simulador-caja', 'get')) {
    cxcPfOk('Ruta simulador Caja CxC registrada: GET /cuentas-por-cobrar/simulador-caja.');
} else {
    cxcPfError('No esta registrada GET /cuentas-por-cobrar/simulador-caja.', 'Registrar solo ruta GET/read-only para 7B-D-A.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/generar-desde-reservacion/{id:[0-9]+}', 'post')) {
    cxcPfOk('Ruta generacion CxC registrada: POST /cuentas-por-cobrar/generar-desde-reservacion/{id}.');
} else {
    cxcPfError('No esta registrada POST /cuentas-por-cobrar/generar-desde-reservacion/{id}.', 'Registrar la ruta POST controlada para 7B-C-A.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/operativas/{id:[0-9]+}/registrar-cobro-caja', 'post')) {
    cxcPfOk('Ruta cobro CxC registrada: POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja.');
} else {
    cxcPfError('No esta registrada POST /cuentas-por-cobrar/operativas/{id}/registrar-cobro-caja.', 'Registrar la ruta POST controlada para 7B-D-C-A.');
}

if (cxcPfRouteExists($routes, 'cuentas-por-cobrar/operativas/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-cobro-caja', 'post')) {
    cxcPfOk('Ruta reversion cobro CxC registrada: POST /cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja.');
} else {
    cxcPfError('No esta registrada POST /cuentas-por-cobrar/operativas/{id}/movimientos/{movimientoid}/revertir-cobro-caja.', 'Registrar la ruta POST controlada para 7B-D-D-A con parametro compatible con Router.');
}

$allowedCxcPostRoutes = [
    'cuentas-por-cobrar/generar-desde-reservacion/{id:[0-9]+}',
    'cuentas-por-cobrar/operativas/{id:[0-9]+}/registrar-cobro-caja',
    'cuentas-por-cobrar/operativas/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-cobro-caja',
];
foreach ($routes as $route) {
    $method = strtolower((string)$route['method']);
    $path = (string)$route['path'];
    if (strpos($path, 'cuentas-por-cobrar') === 0 && $method !== 'get') {
        $isAllowedPost = $method === 'post' && in_array($path, $allowedCxcPostRoutes, true);
        if (!$isAllowedPost) {
            cxcPfError('Ruta CxC no permitida: ' . strtoupper($method) . ' /' . $path . '.', '7B-C-A solo permite el POST controlado de generacion manual.');
        }
    }
}

$modelCode = is_file($modelPath) ? (string)file_get_contents($modelPath) : '';
$controllerCode = is_file($controllerPath) ? (string)file_get_contents($controllerPath) : '';
$cobroServiceCode = is_file($cobroServicePath) ? (string)file_get_contents($cobroServicePath) : '';
$reversionServiceCode = is_file($reversionServicePath) ? (string)file_get_contents($reversionServicePath) : '';
$viewCode = is_file($viewPath) ? (string)file_get_contents($viewPath) : '';
$operativasViewCode = is_file($operativasViewPath) ? (string)file_get_contents($operativasViewPath) : '';
$operativaDetailViewCode = is_file($operativaDetailViewPath) ? (string)file_get_contents($operativaDetailViewPath) : '';
$simuladorCajaViewCode = is_file($simuladorCajaViewPath) ? (string)file_get_contents($simuladorCajaViewPath) : '';
$sidebarCode = is_file($sidebarPath) ? (string)file_get_contents($sidebarPath) : '';

if (
    $modelCode !== ''
    && strpos($modelCode, 'class CuentaPorCobrar') !== false
    && strpos($modelCode, 'listarDerivadasPorHotel') !== false
    && strpos($modelCode, 'r.hotel_id = ?') !== false
    && strpos($modelCode, 'reservacion_pagos') !== false
    && strpos($modelCode, 'reservacion_abonos') !== false
    && strpos($modelCode, 'solicitudes_factura') !== false
    && strpos($modelCode, 'listarOperativasPorHotel') !== false
    && strpos($modelCode, 'buscarOperativaPorIdHotel') !== false
    && strpos($modelCode, 'cuentas_por_cobrar') !== false
    && strpos($modelCode, 'cuentas_por_cobrar_movimientos') !== false
    && strpos($modelCode, 'generarDesdeReservacionElegible') !== false
    && strpos($modelCode, 'simuladorCajaCliente') !== false
    && strpos($modelCode, 'corteAbiertoSimuladorCaja') !== false
    && strpos($modelCode, 'tipoMovimientoCobroDisponible') !== false
    && strpos($modelCode, 'INSERT INTO cuentas_por_cobrar') !== false
    && strpos($modelCode, 'INSERT INTO cuentas_por_cobrar_movimientos') !== false
    && strpos($modelCode, 'FOR UPDATE') !== false
    && strpos($modelCode, 'AuditService::record') !== false
    && !cxcPfContainsForbiddenWrites($modelCode)
) {
    cxcPfOk('Modelo CxC deriva por hotel, lee base operativa y genera CxC manual sin escrituras sensibles.');
} else {
    cxcPfError('Modelo CxC no cumple contrato 7B-C-A.', 'Revisar CuentaPorCobrar.php: solo debe insertar CxC, movimiento CxC y auditoria.');
}

if (
    $controllerCode !== ''
    && strpos($controllerCode, 'require_hotel_context') !== false
    && strpos($controllerCode, "require_hotel_module('reservaciones')") !== false
    && strpos($controllerCode, 'indexAction') !== false
    && strpos($controllerCode, 'operativasAction') !== false
    && strpos($controllerCode, 'verOperativaAction') !== false
    && strpos($controllerCode, 'simuladorCajaAction') !== false
    && strpos($controllerCode, 'generarDesdeReservacionAction') !== false
    && strpos($controllerCode, 'registrarCobroCajaAction') !== false
    && strpos($controllerCode, 'revertirCobroCajaAction') !== false
    && strpos($controllerCode, 'validateCSRF') !== false
    && strpos($controllerCode, 'generarDesdeReservacionElegible') !== false
    && strpos($controllerCode, 'simuladorCajaCliente') !== false
    && strpos($controllerCode, 'registrarCobro') !== false
    && strpos($controllerCode, 'revertirCobro') !== false
    && strpos($controllerCode, 'generarCobroToken') !== false
    && strpos($controllerCode, 'consumirCobroToken') !== false
    && strpos($controllerCode, 'generarReversionCobroToken') !== false
    && strpos($controllerCode, 'consumirReversionCobroToken') !== false
    && strpos($controllerCode, "require_hotel_module('caja')") !== false
    && !cxcPfContainsForbiddenWrites($controllerCode)
) {
    cxcPfOk('Controller CxC expone GET protegidos, generacion manual, cobro y reversion Caja con CSRF/token.');
} else {
    cxcPfWarning('Controller CxC requiere revision manual.', 'Asegurar auth, hotel, modulo reservaciones, CSRF, tokens y Caja solo en POST controlados.');
}

if (
    $cobroServiceCode !== ''
    && strpos($cobroServiceCode, 'class CuentaPorCobrarCobroService') !== false
    && strpos($cobroServiceCode, 'registrarCobro') !== false
    && strpos($cobroServiceCode, 'evaluarCobro') !== false
    && strpos($cobroServiceCode, "tipo_movimiento, monto") !== false
    && strpos($cobroServiceCode, "'COBRO'") !== false
    && strpos($cobroServiceCode, 'INSERT INTO movimientos_caja') !== false
    && strpos($cobroServiceCode, "?, 'ingreso', 'Cobro CxC'") !== false
    && strpos($cobroServiceCode, 'UPDATE cuentas_por_cobrar') !== false
    && strpos($cobroServiceCode, 'FOR UPDATE') !== false
    && strpos($cobroServiceCode, 'AuditService::record') !== false
    && strpos($cobroServiceCode, 'assertReferenciaNoDuplicada') !== false
    && !cxcPfContainsForbiddenCobroServiceWrites($cobroServiceCode)
) {
    cxcPfOk('Servicio cobro CxC 7B-D-C-A concentra transaccion, CxC, Caja, auditoria y locks.');
} else {
    cxcPfError('Servicio cobro CxC 7B-D-C-A no cumple contrato.', 'Revisar servicio transaccional, locks, COBRO, Caja ingreso y ausencia de escrituras historicas.');
}

if (
    $reversionServiceCode !== ''
    && strpos($reversionServiceCode, 'class CuentaPorCobrarReversionCobroService') !== false
    && strpos($reversionServiceCode, 'revertirCobro') !== false
    && strpos($reversionServiceCode, 'evaluarReversion') !== false
    && strpos($reversionServiceCode, "'CANCELACION'") !== false
    && strpos($reversionServiceCode, 'INSERT INTO movimientos_caja') !== false
    && strpos($reversionServiceCode, "?, 'gasto', 'Reversion Cobro CxC'") !== false
    && strpos($reversionServiceCode, 'UPDATE cuentas_por_cobrar') !== false
    && strpos($reversionServiceCode, 'FOR UPDATE') !== false
    && strpos($reversionServiceCode, 'AuditService::record') !== false
    && strpos($reversionServiceCode, 'existeReversionPrevia') !== false
    && strpos($reversionServiceCode, 'REV-CXC-') !== false
    && !cxcPfContainsForbiddenReversionServiceWrites($reversionServiceCode)
) {
    cxcPfOk('Servicio reversion CxC 7B-D-D-A concentra transaccion, CANCELACION, gasto Caja, auditoria y locks.');
} else {
    cxcPfError('Servicio reversion CxC 7B-D-D-A no cumple contrato.', 'Revisar servicio transaccional, locks, CANCELACION, gasto Caja y ausencia de escrituras historicas.');
}

if (
    $viewCode !== ''
    && strpos($viewCode, 'method="GET"') !== false
    && strpos($viewCode, 'method="POST"') !== false
    && strpos($viewCode, 'csrf_field()') !== false
    && strpos($viewCode, 'generar-desde-reservacion') !== false
    && (
        stripos($viewCode, 'saldo estimado') !== false
        || stripos($viewCode, 'Saldo por cobrar') !== false
        || strpos($viewCode, 'saldo_estimado') !== false
    )
    && strpos($viewCode, 'registrar-cobro-caja') === false
    && strpos($viewCode, 'movimientos_caja') === false
) {
    cxcPfOk('Vista CxC conserva filtro GET y agrega POST con CSRF para generacion manual.');
} else {
    cxcPfWarning('Vista CxC no muestra contrato 7B-C-A completo.', 'Revisar texto, formularios y CSRF.');
}

if (
    $operativasViewCode !== ''
    && strpos($operativasViewCode, 'method="GET"') !== false
    && (
        stripos($operativasViewCode, 'Solo lectura') !== false
        || stripos($operativasViewCode, 'Los cobros se registran desde el detalle') !== false
    )
    && strpos($operativasViewCode, 'registrar-cobro-caja') === false
    && strpos($operativasViewCode, 'method="POST"') === false
) {
    cxcPfOk('Vista CxC operativa 7B-B es GET/read-only y sin acciones de cobro.');
} else {
    cxcPfWarning('Vista CxC operativa requiere revision manual.', 'Asegurar GET/read-only, sin POST, sin cobros ni Caja.');
}

if (
    $operativaDetailViewCode !== ''
    && strpos($operativaDetailViewCode, 'method="POST"') !== false
    && strpos($operativaDetailViewCode, 'csrf_field()') !== false
    && strpos($operativaDetailViewCode, 'registrar-cobro-caja') !== false
    && strpos($operativaDetailViewCode, 'cobro_token') !== false
    && (
        strpos($operativaDetailViewCode, 'Cobro con Caja') !== false
        || strpos($operativaDetailViewCode, 'Registrar cobro (en Caja)') !== false
    )
    && strpos($operativaDetailViewCode, 'revertir-cobro-caja') !== false
    && strpos($operativaDetailViewCode, 'reversion_token') !== false
    && (
        strpos($operativaDetailViewCode, 'Reversion de cobros') !== false
        || strpos($operativaDetailViewCode, 'Revertir un cobro') !== false
    )
) {
    cxcPfOk('Detalle CxC operativa integra cobro y reversion Caja con POST, CSRF y token.');
} else {
    cxcPfWarning('Detalle CxC operativa requiere revision manual.', 'Asegurar formularios de cobro/reversion con CSRF, token y visibilidad condicionada.');
}

if (
    $simuladorCajaViewCode !== ''
    && strpos($simuladorCajaViewCode, 'method="GET"') !== false
    && strpos($simuladorCajaViewCode, 'method="POST"') === false
    && stripos($simuladorCajaViewCode, 'no registra cobros') !== false
    && strpos($simuladorCajaViewCode, 'cuentas-por-cobrar/simulador-caja') !== false
    && (
        strpos($simuladorCajaViewCode, 'Tipo COBRO') !== false
        || strpos($simuladorCajaViewCode, 'Cobro en Caja') !== false
    )
) {
    cxcPfOk('Vista simulador Caja CxC 7B-D-A es GET/read-only y no registra cobros reales.');
} else {
    cxcPfWarning('Vista simulador Caja CxC requiere revision manual.', 'Asegurar GET/read-only, sin POST y sin cobros reales.');
}

if ($sidebarCode !== '' && strpos($sidebarCode, 'cuentas-por-cobrar') !== false) {
    cxcPfOk('Sidebar enlaza CxC read-only.');
} else {
    cxcPfWarning('Sidebar no enlaza CxC.', 'Agregar navegacion solo si es segura para el hotel.');
}

if (
    is_file($cobroRollbackToolPath)
    && strpos((string)file_get_contents($cobroRollbackToolPath), 'CuentaPorCobrarCobroService') !== false
    && strpos((string)file_get_contents($cobroRollbackToolPath), 'rollBack') !== false
    && strpos((string)file_get_contents($cobroRollbackToolPath), 'tipo_movimiento = \'COBRO\'') !== false
) {
    cxcPfOk('Prueba rollback cobro CxC disponible y marcada como reversible.');
} else {
    cxcPfError('No existe prueba rollback completa para cobro CxC.', 'Crear tools/saas/probar_cobro_cxc_caja.php antes de QA manual.');
}

if (
    is_file($reversionRollbackToolPath)
    && strpos((string)file_get_contents($reversionRollbackToolPath), 'CuentaPorCobrarReversionCobroService') !== false
    && strpos((string)file_get_contents($reversionRollbackToolPath), 'rollBack') !== false
    && strpos((string)file_get_contents($reversionRollbackToolPath), "tipo_movimiento = 'CANCELACION'") !== false
    && strpos((string)file_get_contents($reversionRollbackToolPath), "categoria = 'Reversion Cobro CxC'") !== false
) {
    cxcPfOk('Prueba rollback reversion cobro CxC disponible y marcada como reversible.');
} else {
    cxcPfError('No existe prueba rollback completa para reversion cobro CxC.', 'Crear tools/saas/probar_reversion_cobro_cxc_caja.php antes de QA manual.');
}

if ($pdo instanceof PDO) {
    try {
        $pdo->rollBack();
    } catch (Throwable $e) {
    }
}

echo "=====================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if (!empty($recommendations)) {
    echo "Recomendaciones concretas:\n";
    foreach (array_values(array_unique($recommendations)) as $recommendation) {
        echo "- {$recommendation}\n";
    }
}

echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
exit($errors > 0 ? 1 : 0);
