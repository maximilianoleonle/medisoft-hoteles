<?php
/**
 * Preflight Fase 5E-D-A para pagos laborales con Caja.
 *
 * Herramienta solo lectura. Valida rutas, servicio, rollback y datos sin crear pagos
 * ni movimientos de Caja. Diagnostica si el terreno esta listo para pago laboral
 * controlado con entidad independiente.
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

function lpcLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function lpcOk(string $message): void
{
    global $ok;
    $ok++;
    lpcLine('OK', $message);
}

function lpcWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    lpcLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function lpcError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    lpcLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function lpcQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function lpcTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int)$stmt->fetchColumn() > 0;
}

function lpcColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int)$stmt->fetchColumn() > 0;
}

function lpcIndexExists(PDO $pdo, string $database, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND INDEX_NAME = :index'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'index' => $index]);

    return (int)$stmt->fetchColumn() > 0;
}

function lpcConstraintExists(PDO $pdo, string $database, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'constraint' => $constraint]);

    return (int)$stmt->fetchColumn() > 0;
}

function lpcCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int)$stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function lpcReportZero(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        lpcWarning(
            'No se pudo validar pagos laborales con Caja: ' . $label . '.',
            'Revisar manualmente Personal y Caja antes de habilitar pagos laborales reales.'
        );
        return;
    }

    if ($count === 0) {
        lpcOk('Consistencia 5E-D-A OK: ' . $label . ' = 0.');
        return;
    }

    lpcError('Consistencia 5E-D-A fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function lpcParseRoutes(string $path): array
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

function lpcRouteExists(array $routes, string $path, string $method): bool
{
    $expectedPath = trim($path, '/');
    $expectedMethod = strtolower($method);
    foreach ($routes as $route) {
        if (
            strtolower((string)$route['method']) === $expectedMethod
            && trim((string)$route['path'], '/') === $expectedPath
        ) {
            return true;
        }
    }

    return false;
}

function lpcFileContainsAny(string $path, array $needles): array
{
    if (!is_file($path)) {
        return [];
    }

    $code = (string) file_get_contents($path);
    $matches = [];
    foreach ($needles as $needle) {
        if (stripos($code, $needle) !== false) {
            $matches[] = $needle;
        }
    }

    return $matches;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    lpcError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$workerModelPath = $appRoot . '/app/models/Trabajador.php';
$workerControllerPath = $appRoot . '/app/controllers/TrabajadorController.php';
$workerIndexViewPath = $appRoot . '/app/views/trabajadores/index.php';
$workerDetailViewPath = $appRoot . '/app/views/trabajadores/ver.php';
$workerReportViewPath = $appRoot . '/app/views/trabajadores/reporte.php';
$workerCashSimulatorViewPath = $appRoot . '/app/views/trabajadores/simulador_pago_caja.php';
$workerCashReportViewPath = $appRoot . '/app/views/trabajadores/reporte_pagos_caja.php';
$workerPayrollPreviewViewPath = $appRoot . '/app/views/trabajadores/nomina_preview.php';
$workerPayrollPeriodsViewPath = $appRoot . '/app/views/trabajadores/nomina_periodos.php';
$workerLaborReceiptViewPath = $appRoot . '/app/views/trabajadores/recibo_laboral_informativo.php';
$workerLaborReceiptPdfServicePath = $appRoot . '/app/services/TrabajadorReciboLaboralPdfService.php';
$auditServicePath = $appRoot . '/app/services/AuditService.php';
$paymentServicePath = $appRoot . '/app/services/TrabajadorPagoCajaService.php';
$rollbackToolPath = $appRoot . '/tools/saas/probar_pago_laboral_caja.php';
$contractPath = '';
$contractCandidates = [
    $projectRoot . '/docs/fase_5E_0_contrato_pagos_laborales_caja.md',
    $appRoot . '/docs/fase_5E_0_contrato_pagos_laborales_caja.md',
    getcwd() . '/docs/fase_5E_0_contrato_pagos_laborales_caja.md',
    dirname(getcwd()) . '/docs/fase_5E_0_contrato_pagos_laborales_caja.md',
];
foreach ($contractCandidates as $candidate) {
    if (is_file($candidate)) {
        $contractPath = $candidate;
        break;
    }
}
$docsDirectoryMounted = is_dir($projectRoot . '/docs')
    || is_dir($appRoot . '/docs')
    || is_dir(getcwd() . '/docs')
    || is_dir(dirname(getcwd()) . '/docs');

echo "Preflight Fase 5E-D-A - Pagos laborales con Caja\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    lpcError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
} else {
    lpcOk('Configuracion de base detectada.');
}

if ($contractPath !== '') {
    $contractCode = (string) file_get_contents($contractPath);
    if (
        strpos($contractCode, 'CONTRATO_5E_0_PAGOS_LABORALES_CAJA_COMPLETADO') !== false
        && strpos($contractCode, 'trabajador_pagos_caja') !== false
        && strpos($contractCode, 'trabajador_pagos no representa pagos reales') !== false
    ) {
        lpcOk('Contrato 5E-0 detectado y coherente con entidad independiente futura.');
    } else {
        lpcWarning(
            'Contrato 5E-0 existe pero no declara todo el alcance esperado.',
            'Revisar que trabajador_pagos quede como conceptos y trabajador_pagos_caja como entidad futura.'
        );
    }
} elseif (!$docsDirectoryMounted) {
    lpcOk('Directorio docs no esta montado en runtime; validacion documental 5E-0 omitida sin bloquear preflight.');
} else {
    lpcWarning(
        'No se encontro contrato 5E-0 de pagos laborales con Caja.',
        'Crear contrato documental antes de implementar migraciones, rutas o pagos reales.'
    );
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
        lpcOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        lpcError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo instanceof PDO) {
    $schema = [
        'trabajadores' => ['id', 'hotel_id', 'nombre_completo', 'estado'],
        'trabajador_pagos' => ['id', 'hotel_id', 'trabajador_id', 'tipo', 'efecto', 'monto', 'fecha', 'estado'],
        'trabajador_anticipos' => ['id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente', 'estado'],
        'trabajador_prestamos' => ['id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente', 'estado'],
        'cajas' => ['id', 'hotel_id'],
        'cortes_caja' => ['id', 'hotel_id', 'caja_id', 'estado'],
        'movimientos_caja' => ['id', 'hotel_id', 'tipo', 'categoria', 'monto', 'metodo_pago', 'referencia', 'corte_id'],
    ];

    foreach ($schema as $table => $columns) {
        if (!lpcTableExists($pdo, $database, $table)) {
            lpcError('Tabla requerida faltante para diagnostico 5E-D-A: ' . $table . '.', 'No avanzar a pagos laborales con Caja hasta reconciliar el esquema.');
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!lpcColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            lpcError(
                'Tabla ' . $table . ' incompleta para 5E-D-A. Faltan columnas: ' . implode(', ', $missingColumns) . '.',
                'Corregir esquema antes de preparar pagos laborales con Caja.'
            );
        } else {
            lpcOk('Tabla ' . $table . ' disponible con columnas requeridas.');
        }
    }

    if (lpcTableExists($pdo, $database, 'trabajador_pagos_caja')) {
        $paymentColumns = [
            'id',
            'hotel_id',
            'trabajador_id',
            'movimiento_caja_id',
            'corte_id',
            'monto',
            'metodo_pago',
            'referencia',
            'periodo_inicio',
            'periodo_fin',
            'concepto',
            'fecha_pago',
            'estado',
            'notas',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];
        $missingPaymentColumns = [];
        foreach ($paymentColumns as $column) {
            if (!lpcColumnExists($pdo, $database, 'trabajador_pagos_caja', $column)) {
                $missingPaymentColumns[] = $column;
            }
        }

        $paymentIndexes = [
            'PRIMARY',
            'uk_trabajador_pagos_caja_hotel_referencia',
            'uk_trabajador_pagos_caja_movimiento',
            'idx_trabajador_pagos_caja_hotel_trabajador',
            'idx_trabajador_pagos_caja_hotel_fecha',
            'idx_trabajador_pagos_caja_hotel_corte',
            'idx_trabajador_pagos_caja_estado',
            'idx_trabajador_pagos_caja_created_by',
            'idx_trabajador_pagos_caja_updated_by',
        ];
        $missingPaymentIndexes = [];
        foreach ($paymentIndexes as $index) {
            if (!lpcIndexExists($pdo, $database, 'trabajador_pagos_caja', $index)) {
                $missingPaymentIndexes[] = $index;
            }
        }

        $paymentConstraints = [
            'fk_trabajador_pagos_caja_hotel',
            'fk_trabajador_pagos_caja_trabajador',
            'fk_trabajador_pagos_caja_movimiento',
            'fk_trabajador_pagos_caja_corte',
            'fk_trabajador_pagos_caja_created_by',
            'fk_trabajador_pagos_caja_updated_by',
            'chk_trabajador_pagos_caja_monto',
            'chk_trabajador_pagos_caja_periodo',
        ];
        $missingPaymentConstraints = [];
        foreach ($paymentConstraints as $constraint) {
            if (!lpcConstraintExists($pdo, $database, 'trabajador_pagos_caja', $constraint)) {
                $missingPaymentConstraints[] = $constraint;
            }
        }

        if ($missingPaymentColumns || $missingPaymentIndexes || $missingPaymentConstraints) {
            lpcError(
                'trabajador_pagos_caja no cumple contrato 5E-B-A. Columnas faltantes: ' . implode(', ', $missingPaymentColumns) . '. Indices faltantes: ' . implode(', ', $missingPaymentIndexes) . '. Constraints faltantes: ' . implode(', ', $missingPaymentConstraints) . '.',
                'No avanzar a servicio de pago laboral hasta reconciliar la migracion 5E-B-A.'
            );
        } else {
            lpcOk('Tabla trabajador_pagos_caja cumple contrato 5E-B-A de columnas, indices y constraints.');
        }
        $pagosCajaCount = lpcCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_pagos_caja');
        if ($pagosCajaCount === null) {
            lpcWarning('No se pudo contar trabajador_pagos_caja.', 'Revisar la tabla antes de QA de pago laboral con Caja.');
        } else {
            lpcOk('Conteo informativo trabajador_pagos_caja: ' . (string)$pagosCajaCount . ' registro(s).');
        }

        if (lpcTableExists($pdo, $database, 'migrations')) {
            $migrationCount = lpcCountScalar($pdo, "SELECT COUNT(*) FROM migrations WHERE nombre = '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql' AND estado = 'ejecutada'");
            if ($migrationCount === 1) {
                lpcOk('Migracion 5E-B-A registrada como ejecutada.');
            } else {
                lpcWarning(
                    'trabajador_pagos_caja existe pero la migracion 5E-B-A no figura como ejecutada.',
                    'Registrar solo si la migracion fue aplicada tras backup verificado.'
                );
            }
        }
    } else {
        lpcError(
            'Tabla trabajador_pagos_caja no existe y es requerida para 5E-D-A.',
            'Aplicar migracion 5E-B-A solo con backup y autorizacion antes de usar el pago laboral con Caja.'
        );
    }

    foreach (['movimiento_caja_id', 'corte_id', 'pago_caja_id'] as $column) {
        if (
            lpcTableExists($pdo, $database, 'trabajador_pagos')
            && lpcColumnExists($pdo, $database, 'trabajador_pagos', $column)
        ) {
            lpcError(
                'trabajador_pagos contiene columna financiera fuera de contrato: ' . $column . '.',
                'No reutilizar trabajador_pagos como pago real; migrar solo mediante entidad independiente.'
            );
        } else {
            lpcOk('trabajador_pagos sin columna financiera futura no autorizada: ' . $column . '.');
        }
    }

    if (lpcTableExists($pdo, $database, 'logs_auditoria')) {
        lpcOk('logs_auditoria disponible para futura trazabilidad.');
    } else {
        lpcWarning(
            'No se detecta logs_auditoria.',
            'Una implementacion real de pago laboral debe auditar pago y fallos con AuditService.'
        );
    }

    lpcReportZero(
        'trabajador_pagos con tipos que parecen pagos reales',
        lpcCountScalar($pdo, "SELECT COUNT(*) FROM trabajador_pagos WHERE LOWER(COALESCE(tipo, '')) IN ('pago', 'nomina', 'nÃƒÆ’Ã‚Â³mina', 'pago_laboral', 'pago_nomina')"),
        'Normalizar trabajador_pagos como conceptos laborales: comision, bono, descuento o ajuste.'
    );
    lpcReportZero(
        'trabajador_pagos con concepto textual de Caja/Nomina',
        lpcCountScalar($pdo, "SELECT COUNT(*) FROM trabajador_pagos
            WHERE LOWER(COALESCE(concepto, '')) LIKE '%nomina%'
               OR LOWER(COALESCE(concepto, '')) LIKE '%nÃƒÆ’Ã‚Â³mina%'
               OR LOWER(COALESCE(concepto, '')) LIKE '%pago laboral%'
               OR LOWER(COALESCE(referencia, '')) LIKE '%movimiento_caja%'"),
        'Revisar conceptos laborales para no usarlos como pagos reales ni referencias directas a Caja.'
    );
    lpcReportZero(
        'conceptos laborales con monto invalido',
        lpcCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_pagos WHERE monto <= 0'),
        'Corregir montos antes de calcular cualquier saldo elegible.'
    );
    lpcReportZero(
        'conceptos laborales con trabajador inexistente',
        lpcCountScalar($pdo, 'SELECT COUNT(*)
            FROM trabajador_pagos p
            LEFT JOIN trabajadores t ON t.id = p.trabajador_id
            WHERE t.id IS NULL'),
        'Reconciliar trabajador_id antes de preparar pagos reales.'
    );
    lpcReportZero(
        'conceptos laborales con trabajador de otro hotel',
        lpcCountScalar($pdo, 'SELECT COUNT(*)
            FROM trabajador_pagos p
            JOIN trabajadores t ON t.id = p.trabajador_id
            WHERE t.hotel_id <> p.hotel_id'),
        'Bloquear pagos laborales hasta alinear hotel_id del trabajador y sus conceptos.'
    );
    lpcReportZero(
        'anticipos con saldo mayor al monto o saldo negativo',
        lpcCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_anticipos WHERE saldo_pendiente < 0 OR saldo_pendiente > monto'),
        'Reconciliar anticipos antes de cualquier liquidacion o descuento futuro.'
    );
    lpcReportZero(
        'prestamos con saldo mayor al monto o saldo negativo',
        lpcCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_prestamos WHERE saldo_pendiente < 0 OR saldo_pendiente > monto'),
        'Reconciliar prestamos antes de cualquier liquidacion o descuento futuro.'
    );

    $movimientosLaboralesOrfanos = lpcCountScalar($pdo, "SELECT COUNT(*)
        FROM movimientos_caja m
        WHERE LOWER(COALESCE(m.categoria, '')) = 'pago laboral'
          AND NOT EXISTS (
              SELECT 1
              FROM trabajador_pagos_caja pc
              WHERE pc.movimiento_caja_id = m.id
                AND pc.hotel_id = m.hotel_id
          )");
    if ($movimientosLaboralesOrfanos === null) {
        lpcWarning('No se pudieron validar movimientos Pago laboral en Caja.', 'Revisar movimientos_caja antes de QA.');
    } elseif ($movimientosLaboralesOrfanos === 0) {
        lpcOk('Movimientos Caja categoria Pago laboral estan vinculados a trabajador_pagos_caja o no existen.');
    } else {
        lpcError(
            'Movimientos Caja Pago laboral sin vinculo trabajador_pagos_caja: ' . (string)$movimientosLaboralesOrfanos . '.',
            'Reconciliar Caja contra trabajador_pagos_caja antes de continuar.'
        );
    }

    $reversionesLaboralesOrfanas = lpcCountScalar($pdo, "SELECT COUNT(*)
        FROM movimientos_caja m
        WHERE LOWER(COALESCE(m.categoria, '')) = 'reversion pago laboral'
          AND NOT EXISTS (
              SELECT 1
              FROM trabajador_pagos_caja pc
              WHERE pc.hotel_id = m.hotel_id
                AND pc.estado = 'revertido'
                AND m.referencia = CONCAT('REV-NOM-TRAB-', pc.trabajador_id, '-PAGO-', pc.id)
          )");
    if ($reversionesLaboralesOrfanas === null) {
        lpcWarning('No se pudieron validar reversiones Pago laboral en Caja.', 'Revisar movimientos_caja antes de QA de reversion.');
    } elseif ($reversionesLaboralesOrfanas === 0) {
        lpcOk('Movimientos Caja Reversion Pago laboral estan trazados a pagos laborales revertidos o no existen.');
    } else {
        lpcError(
            'Movimientos Caja Reversion Pago laboral sin pago revertido trazable: ' . (string)$reversionesLaboralesOrfanas . '.',
            'Reconciliar referencias REV-NOM-TRAB antes de cerrar 14D/14E.'
        );
    }

    if (lpcTableExists($pdo, $database, 'categorias_movimientos')) {
        lpcReportZero(
            'categorias de Caja con nombre Nomina/Pago laboral',
            lpcCountScalar($pdo, "SELECT COUNT(*) FROM categorias_movimientos
                WHERE LOWER(COALESCE(nombre, '')) LIKE '%nomina%'
                   OR LOWER(COALESCE(nombre, '')) LIKE '%nÃƒÆ’Ã‚Â³mina%'
                   OR LOWER(COALESCE(nombre, '')) LIKE '%pago laboral%'"),
            'Crear categoria laboral de Caja solo dentro de una fase autorizada.'
        );
    } else {
        lpcWarning(
            'No se encontro categorias_movimientos.',
            'Si Caja usa categorias normalizadas, validar su tabla antes de registrar nomina.'
        );
    }

    $activeWorkers = lpcCountScalar($pdo, "SELECT COUNT(*) FROM trabajadores WHERE estado = 'activo'");
    if ($activeWorkers === null) {
        lpcWarning('No se pudo contar trabajadores activos.', 'Revisar tabla trabajadores antes de QA.');
    } elseif ($activeWorkers > 0) {
        lpcOk('Trabajadores activos disponibles para QA: ' . (string)$activeWorkers . '.');
    } else {
        lpcWarning(
            'No hay trabajadores activos para QA.',
            'Crear o activar un trabajador solo en una fase autorizada antes de probar pagos reales.'
        );
    }

    $openCuts = lpcCountScalar($pdo, "SELECT COUNT(*) FROM cortes_caja WHERE estado = 'abierto'");
    if ($openCuts === null) {
        lpcWarning('No se pudo contar cortes abiertos.', 'Revisar cortes_caja antes de QA.');
    } elseif ($openCuts > 0) {
        lpcOk('Cortes de Caja abiertos disponibles para QA: ' . (string)$openCuts . '.');
    } else {
        lpcWarning(
            'No hay cortes de Caja abiertos para QA.',
            'Abrir corte solo desde el flujo autorizado de Caja cuando se vaya a probar el pago real.'
        );
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

$routes = lpcParseRoutes($routesPath);
if ($routes === []) {
    lpcWarning('No se pudieron parsear rutas.', 'Ejecutar desde el arbol src completo.');
} else {
    $forbiddenRoutes = [];
    foreach ($routes as $route) {
        $isReadOnlySimulator = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/pagos-caja/simulador'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'simuladorpagocaja';
        $isReadOnlyReport = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/pagos-caja/reporte'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'reportepagoscaja';
        $isReadOnlyReportExport = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/pagos-caja/reporte/exportar'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'exportarreportepagoscaja';
        $isReadOnlyPayrollPreview = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/nomina/preview'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'nominapreview';
        $isReadOnlyPayrollPreviewExport = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/nomina/preview/exportar'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'exportarnominapreview';
        $isReadOnlyPayrollPeriods = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/nomina/periodos'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'nominaperiodos';
        $isReadOnlyPayrollPeriodPreview = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/nomina/periodos/preview'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'nominaperiodopreview';
        $isReadOnlyLaborReceipt = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/{id:[0-9]+}/recibo-laboral'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'recibolaboral';
        $isReadOnlyLaborReceiptPdf = strtolower((string)$route['method']) === 'get'
            && trim((string)$route['path'], '/') === 'trabajadores/{id:[0-9]+}/recibo-laboral/pdf'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'recibolaboralpdf';
        $isPaymentRoute = strtolower((string)$route['method']) === 'post'
            && trim((string)$route['path'], '/') === 'trabajadores/{id:[0-9]+}/registrar-pago-caja'
            && strtolower((string)$route['controller']) === 'trabajador'
            && strtolower((string)$route['action']) === 'registrarpagocaja';
        $signature = strtolower(
            trim((string)$route['path'], '/') . ' ' . (string)$route['controller'] . ' ' . (string)$route['action']
        );
        if (
            !$isReadOnlySimulator
            && !$isReadOnlyReport
            && !$isReadOnlyReportExport
            && !$isReadOnlyPayrollPreview
            && !$isReadOnlyPayrollPreviewExport
            && !$isReadOnlyPayrollPeriods
            && !$isReadOnlyPayrollPeriodPreview
            && !$isReadOnlyLaborReceipt
            && !$isReadOnlyLaborReceiptPdf
            && !$isPaymentRoute
            &&
            strpos($signature, 'trabajadores') !== false
            && (
                strpos($signature, 'registrar-pago-caja') !== false
                || strpos($signature, 'pago-caja') !== false
                || strpos($signature, 'pago-laboral') !== false
                || strpos($signature, 'nomina') !== false
                || strpos($signature, 'nÃƒÆ’Ã‚Â³mina') !== false
            )
        ) {
            $forbiddenRoutes[] = strtoupper($route['method']) . ' ' . $route['path'];
        }
    }

    if ($forbiddenRoutes === []) {
        lpcOk('Rutas laborales con Caja fuera de contrato no detectadas.');
    } else {
        lpcError(
            'Rutas laborales de pago/Caja detectadas fuera de contrato: ' . implode(', ', $forbiddenRoutes) . '.',
            'Mantener solo simulador/reporte GET y POST registrar-pago-caja delegado al servicio transaccional.'
        );
    }

    $reportRouteOk = lpcRouteExists($routes, 'trabajadores/pagos-caja/reporte', 'get');
    if ($reportRouteOk) {
        lpcOk('Ruta 14E GET /trabajadores/pagos-caja/reporte registrada como reporte read-only.');
    } else {
        lpcWarning(
            'Ruta 14E GET /trabajadores/pagos-caja/reporte no esta registrada.',
            'Registrar el GET read-only antes de QA del reporte laboral Caja.'
        );
    }

    $reportExportRouteOk = lpcRouteExists($routes, 'trabajadores/pagos-caja/reporte/exportar', 'get');
    if ($reportExportRouteOk) {
        lpcOk('Ruta 14F GET /trabajadores/pagos-caja/reporte/exportar registrada como export CSV read-only.');
    } else {
        lpcWarning(
            'Ruta 14F GET /trabajadores/pagos-caja/reporte/exportar no esta registrada.',
            'Registrar el GET read-only antes de QA del export CSV laboral Caja.'
        );
    }

    $payrollPreviewRouteOk = lpcRouteExists($routes, 'trabajadores/nomina/preview', 'get');
    if ($payrollPreviewRouteOk) {
        lpcOk('Ruta 5E-G-A GET /trabajadores/nomina/preview registrada como preview read-only.');
    } else {
        lpcWarning(
            'Ruta 5E-G-A GET /trabajadores/nomina/preview no esta registrada.',
            'Registrar el GET read-only antes de QA del preview de nomina por periodo.'
        );
    }

    $payrollPreviewExportRouteOk = lpcRouteExists($routes, 'trabajadores/nomina/preview/exportar', 'get');
    if ($payrollPreviewExportRouteOk) {
        lpcOk('Ruta 5E-H-A GET /trabajadores/nomina/preview/exportar registrada como export CSV read-only.');
    } else {
        lpcWarning(
            'Ruta 5E-H-A GET /trabajadores/nomina/preview/exportar no esta registrada.',
            'Registrar el GET read-only antes de QA del export CSV de pre-nomina.'
        );
    }

    $payrollPeriodsRouteOk = lpcRouteExists($routes, 'trabajadores/nomina/periodos', 'get');
    $payrollPeriodPreviewRouteOk = lpcRouteExists($routes, 'trabajadores/nomina/periodos/preview', 'get');
    if ($payrollPeriodsRouteOk && $payrollPeriodPreviewRouteOk) {
        lpcOk('Rutas 5E-K-A GET /trabajadores/nomina/periodos y /preview registradas como periodos read-only.');
    } else {
        lpcWarning(
            'Rutas 5E-K-A de periodos de pre-nomina no estan completas.',
            'Registrar solo GET /trabajadores/nomina/periodos y GET /trabajadores/nomina/periodos/preview.'
        );
    }

    $laborReceiptRouteOk = lpcRouteExists($routes, 'trabajadores/{id:[0-9]+}/recibo-laboral', 'get');
    if ($laborReceiptRouteOk) {
        lpcOk('Ruta 5E-I-A GET /trabajadores/{id}/recibo-laboral registrada como recibo laboral read-only.');
    } else {
        lpcWarning(
            'Ruta 5E-I-A GET /trabajadores/{id}/recibo-laboral no esta registrada.',
            'Registrar el GET read-only antes de QA del recibo laboral informativo.'
        );
    }

    $laborReceiptPdfRouteOk = lpcRouteExists($routes, 'trabajadores/{id:[0-9]+}/recibo-laboral/pdf', 'get');
    if ($laborReceiptPdfRouteOk) {
        lpcOk('Ruta 5E-J-A GET /trabajadores/{id}/recibo-laboral/pdf registrada como PDF informativo read-only.');
    } else {
        lpcWarning(
            'Ruta 5E-J-A GET /trabajadores/{id}/recibo-laboral/pdf no esta registrada.',
            'Registrar el GET read-only antes de QA del PDF informativo laboral.'
        );
    }

    $simulatorRouteOk = lpcRouteExists($routes, 'trabajadores/pagos-caja/simulador', 'get');
    if ($simulatorRouteOk) {
        lpcOk('Ruta 5E-C-A GET /trabajadores/pagos-caja/simulador registrada como simulador read-only.');
    } else {
        lpcWarning(
            'Ruta 5E-C-A GET /trabajadores/pagos-caja/simulador no esta registrada.',
            'Registrar el GET read-only antes de avanzar a QA del simulador.'
        );
    }

    $paymentRouteOk = lpcRouteExists($routes, 'trabajadores/{id:[0-9]+}/registrar-pago-caja', 'post');
    if ($paymentRouteOk) {
        lpcOk('Ruta 5E-D-A POST /trabajadores/{id}/registrar-pago-caja registrada.');
    } else {
        lpcError(
            'Ruta 5E-D-A POST /trabajadores/{id}/registrar-pago-caja no esta registrada.',
            'Registrar el POST delegado a Trabajador::registrarPagoCajaAction.'
        );
    }
}

if (is_file($workerModelPath) && is_file($workerControllerPath) && is_file($workerCashReportViewPath)) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerCashReportViewCode = (string) file_get_contents($workerCashReportViewPath);
    $workerCashReportCode = $workerModelCode . "\n" . $workerControllerCode . "\n" . $workerCashReportViewCode;
    $reportWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\s+(trabajador_pagos_caja|movimientos_caja|cortes_caja|cajas)\b/i',
        $workerCashReportCode
    );

    if (
        !$reportWriteForbidden
        && strpos($workerModelCode, 'function reportePagosCajaPorHotel') !== false
        && strpos($workerModelCode, 'function tablasReportePagosCajaDisponibles') !== false
        && strpos($workerControllerCode, 'function reportePagosCajaAction') !== false
        && strpos($workerControllerCode, 'function exportarReportePagosCajaAction') !== false
        && strpos($workerControllerCode, 'fputcsv') !== false
        && strpos($workerControllerCode, 'trabajadores/reporte_pagos_caja') !== false
        && strpos($workerCashReportViewCode, 'trabajadores/pagos-caja/reporte/exportar') !== false
        && strpos($workerCashReportViewCode, 'Exportar CSV') !== false
        && strpos($workerCashReportViewCode, "action=\"<?= url('trabajadores/pagos-caja/reporte') ?>\"") !== false
        && strpos($workerCashReportViewCode, 'method="GET"') !== false
        && strpos($workerCashReportViewCode, 'method="POST"') === false
        && strpos($workerCashReportViewCode, 'csrf_field()') === false
        && strpos($workerCashReportViewCode, 'Solo GET') !== false
    ) {
        lpcOk('Reporte 14E de pagos laborales con Caja es read-only con filtros GET y sin escrituras.');
    } else {
        lpcError(
            'Reporte 14E de pagos laborales con Caja incompleto o con riesgo de escritura.',
            'Mantener reporte_pagos_caja como GET/read-only, sin POST, sin CSRF y sin escrituras a Caja.'
        );
    }
} else {
    lpcWarning(
        'Archivos del reporte 14E no estan completos.',
        'Crear modelo/controlador/vista read-only antes de cerrar reporte laboral Caja.'
    );
}

if (is_file($workerModelPath) && is_file($workerControllerPath) && is_file($workerPayrollPreviewViewPath)) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerPayrollPreviewViewCode = (string) file_get_contents($workerPayrollPreviewViewPath);
    $workerPayrollPreviewSurfaceCode = $workerControllerCode . "\n" . $workerPayrollPreviewViewCode;
    $payrollPreviewWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\s+(trabajadores|trabajador_pagos|trabajador_anticipos|trabajador_prestamos|trabajador_pagos_caja|movimientos_caja|cortes_caja|cajas)\b/i',
        $workerPayrollPreviewSurfaceCode
    );

    if (
        !$payrollPreviewWriteForbidden
        && strpos($workerModelCode, 'function nominaPreviewPorHotel') !== false
        && strpos($workerModelCode, 'function tablasNominaPreviewDisponibles') !== false
        && strpos($workerModelCode, 'function normalizarFiltrosNominaPreview') !== false
        && strpos($workerModelCode, 'function pagosCajaNominaPreviewPorTrabajador') !== false
        && strpos($workerControllerCode, 'function nominaPreviewAction') !== false
        && strpos($workerControllerCode, 'function exportarNominaPreviewAction') !== false
        && strpos($workerControllerCode, 'function descargarNominaPreviewCsv') !== false
        && strpos($workerControllerCode, 'nominaPreviewPorHotel($hotelId, $filtros, 500)') !== false
        && strpos($workerControllerCode, 'X-Content-Type-Options: nosniff') !== false
        && strpos($workerControllerCode, 'trabajadores/nomina_preview') !== false
        && strpos($workerPayrollPreviewViewCode, "action=\"<?= url('trabajadores/nomina/preview') ?>\"") !== false
        && strpos($workerPayrollPreviewViewCode, 'trabajadores/nomina/preview/exportar') !== false
        && strpos($workerPayrollPreviewViewCode, 'Exportar CSV') !== false
        && strpos($workerPayrollPreviewViewCode, 'method="GET"') !== false
        && strpos($workerPayrollPreviewViewCode, 'method="POST"') === false
        && strpos($workerPayrollPreviewViewCode, 'csrf_field()') === false
        && strpos($workerPayrollPreviewViewCode, 'Solo GET') !== false
        && strpos($workerPayrollPreviewViewCode, 'registrar-pago-caja') === false
        && strpos($workerPayrollPreviewViewCode, 'Pagar') === false
    ) {
        lpcOk('Preview 5E-G-A de nomina por periodo es GET/read-only y no expone pagos masivos.');
    } else {
        lpcError(
            'Preview 5E-G-A de nomina por periodo incompleto o con riesgo de escritura.',
            'Mantener nomina_preview como GET/read-only, sin POST, sin CSRF, sin pago masivo y sin escrituras laborales/Caja.'
        );
    }
} else {
    lpcWarning(
        'Archivos del preview 5E-G-A no estan completos.',
        'Crear modelo/controlador/vista read-only antes de QA de pre-nomina.'
    );
}

if (is_file($workerModelPath) && is_file($workerControllerPath) && is_file($workerPayrollPeriodsViewPath)) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerPayrollPeriodsViewCode = (string) file_get_contents($workerPayrollPeriodsViewPath);
    $workerPayrollPeriodsSurfaceCode = $workerControllerCode . "\n" . $workerPayrollPeriodsViewCode;
    $payrollPeriodsWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\s+(trabajadores|trabajador_pagos|trabajador_anticipos|trabajador_prestamos|trabajador_pagos_caja|movimientos_caja|cortes_caja|cajas)\b/i',
        $workerPayrollPeriodsSurfaceCode
    );

    if (
        !$payrollPeriodsWriteForbidden
        && strpos($workerModelCode, 'function nominaPeriodosReadOnlyPorHotel') !== false
        && strpos($workerModelCode, 'nominaPreviewPorHotel($hotelId, $previewFiltros, 250)') !== false
        && strpos($workerModelCode, 'function evaluarEstadoNominaPeriodoReadOnly') !== false
        && strpos($workerControllerCode, 'function nominaPeriodosAction') !== false
        && strpos($workerControllerCode, 'function nominaPeriodoPreviewAction') !== false
        && strpos($workerControllerCode, 'function filtrosNominaPeriodosDesdeQuery') !== false
        && strpos($workerControllerCode, 'trabajadores/nomina_periodos') !== false
        && strpos($workerPayrollPeriodsViewCode, "action=\"<?= url('trabajadores/nomina/periodos') ?>\"") !== false
        && strpos($workerPayrollPeriodsViewCode, 'trabajadores/nomina/periodos/preview') !== false
        && strpos($workerPayrollPeriodsViewCode, 'trabajadores/nomina/preview') !== false
        && strpos($workerPayrollPeriodsViewCode, 'method="GET"') !== false
        && strpos($workerPayrollPeriodsViewCode, 'method="POST"') === false
        && strpos($workerPayrollPeriodsViewCode, 'csrf_field()') === false
        && strpos($workerPayrollPeriodsViewCode, 'Solo GET') !== false
        && strpos($workerPayrollPeriodsViewCode, 'Read-only') !== false
        && strpos($workerPayrollPeriodsViewCode, 'Sin cierre') !== false
        && strpos($workerPayrollPeriodsViewCode, 'registrar-pago-caja') === false
        && strpos($workerPayrollPeriodsViewCode, 'timbrar') === false
        && strpos($workerPayrollPeriodsViewCode, 'dispersion') === false
    ) {
        lpcOk('Periodos 5E-K-A de pre-nomina son GET/read-only, reutilizan preview y no exponen cierre ni pagos.');
    } else {
        lpcError(
            'Periodos 5E-K-A de pre-nomina incompletos o con riesgo de escritura.',
            'Mantener nomina_periodos como GET/read-only, sin POST, sin CSRF, sin cierre real, sin Caja y reutilizando preview de nomina.'
        );
    }
} else {
    lpcWarning(
        'Archivos de periodos 5E-K-A no estan completos.',
        'Crear modelo/controlador/vista read-only antes de QA de periodos de pre-nomina.'
    );
}

if (is_file($workerModelPath) && is_file($workerControllerPath) && is_file($workerLaborReceiptViewPath)) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerLaborReceiptViewCode = (string) file_get_contents($workerLaborReceiptViewPath);
    $workerLaborReceiptSurfaceCode = $workerControllerCode . "\n" . $workerLaborReceiptViewCode;
    $laborReceiptWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\s+(trabajadores|trabajador_pagos|trabajador_anticipos|trabajador_prestamos|trabajador_pagos_caja|movimientos_caja|cortes_caja|cajas)\b/i',
        $workerLaborReceiptSurfaceCode
    );

    if (
        !$laborReceiptWriteForbidden
        && strpos($workerModelCode, 'function reciboLaboralInformativoPorHotel') !== false
        && strpos($workerModelCode, 'nominaPreviewPorHotel($hotelId, $filtros, 1)') !== false
        && strpos($workerControllerCode, 'function reciboLaboralAction') !== false
        && strpos($workerControllerCode, 'function filtrosReciboLaboralDesdeQuery') !== false
        && strpos($workerControllerCode, 'trabajadores/recibo_laboral_informativo') !== false
        && strpos($workerLaborReceiptViewCode, 'Recibo laboral informativo') !== false
        && strpos($workerLaborReceiptViewCode, 'No fiscal') !== false
        && strpos($workerLaborReceiptViewCode, 'No genera pago') !== false
        && strpos($workerLaborReceiptViewCode, 'recibo-laboral') !== false
        && strpos($workerLaborReceiptViewCode, 'method="GET"') !== false
        && strpos($workerLaborReceiptViewCode, 'method="POST"') === false
        && strpos($workerLaborReceiptViewCode, 'csrf_field()') === false
        && strpos($workerLaborReceiptViewCode, 'registrar-pago-caja') === false
        && strpos($workerLaborReceiptViewCode, 'timbrar') === false
        && strpos($workerLaborReceiptViewCode, 'dispersion') === false
    ) {
        lpcOk('Recibo 5E-I-A laboral informativo es GET/read-only, reutiliza preview y no expone pago ni timbrado.');
    } else {
        lpcError(
            'Recibo 5E-I-A laboral informativo incompleto o con riesgo de escritura.',
            'Mantener recibo_laboral_informativo como GET/read-only, sin POST, sin CSRF, sin pago, sin timbrado y reutilizando preview de nomina.'
        );
    }
} else {
    lpcWarning(
        'Archivos del recibo 5E-I-A no estan completos.',
        'Crear modelo/controlador/vista read-only antes de QA del recibo laboral informativo.'
    );
}

if (
    is_file($workerModelPath)
    && is_file($workerControllerPath)
    && is_file($workerLaborReceiptViewPath)
    && is_file($workerLaborReceiptPdfServicePath)
) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerLaborReceiptViewCode = (string) file_get_contents($workerLaborReceiptViewPath);
    $workerLaborReceiptPdfServiceCode = (string) file_get_contents($workerLaborReceiptPdfServicePath);
    $workerLaborReceiptPdfSurfaceCode = $workerControllerCode . "\n" . $workerLaborReceiptViewCode . "\n" . $workerLaborReceiptPdfServiceCode;
    $laborReceiptPdfWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE|file_put_contents)\b/i',
        $workerLaborReceiptPdfSurfaceCode
    );

    if (
        !$laborReceiptPdfWriteForbidden
        && strpos($workerModelCode, 'function reciboLaboralInformativoPorHotel') !== false
        && strpos($workerControllerCode, 'function reciboLaboralPdfAction') !== false
        && strpos($workerControllerCode, 'TrabajadorReciboLaboralPdfService') !== false
        && strpos($workerControllerCode, 'reciboLaboralInformativoPorHotel($hotelId, $id, $filtros)') !== false
        && strpos($workerLaborReceiptViewCode, 'PDF informativo') !== false
        && strpos($workerLaborReceiptViewCode, '/recibo-laboral/pdf') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'class TrabajadorReciboLaboralPdfService') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'ROOT_PATH . \'/app/helpers/tcpdf/tcpdf.php\'') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'Content-Type: application/pdf') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'Content-Disposition: attachment') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'X-Content-Type-Options: nosniff') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'Output($this->nombreArchivo($recibo), \'S\')') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'PDF informativo / No fiscal / No genera pago') !== false
        && strpos($workerLaborReceiptPdfServiceCode, 'ReporteEntregaService') === false
        && strpos($workerLaborReceiptPdfServiceCode, 'reporte_links') === false
        && strpos($workerLaborReceiptPdfServiceCode, 'storage') === false
        && strpos($workerLaborReceiptPdfServiceCode, 'registrarYEnviar') === false
        && strpos($workerLaborReceiptPdfServiceCode, 'timbrar') === false
        && strpos($workerLaborReceiptPdfServiceCode, 'dispersion') === false
    ) {
        lpcOk('PDF 5E-J-A de recibo laboral es GET/read-only, en memoria y sin storage, Caja ni timbrado.');
    } else {
        lpcError(
            'PDF 5E-J-A de recibo laboral incompleto o con riesgo de escritura.',
            'Mantener PDF informativo como GET/read-only, con TCPDF en memoria, sin storage, sin reporte_links, sin pago, sin timbrado y reutilizando recibo laboral.'
        );
    }
} else {
    lpcWarning(
        'Archivos del PDF 5E-J-A no estan completos.',
        'Crear ruta/controlador/vista/servicio PDF read-only antes de QA del PDF informativo laboral.'
    );
}

if (is_file($workerModelPath) && is_file($workerControllerPath) && is_file($workerCashSimulatorViewPath)) {
    $workerModelCode = (string) file_get_contents($workerModelPath);
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    $workerSimulatorViewCode = (string) file_get_contents($workerCashSimulatorViewPath);
    $workerSimulatorCode = $workerModelCode . "\n" . $workerControllerCode . "\n" . $workerSimulatorViewCode;
    $simulatorWriteForbidden = preg_match(
        '/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\s+(trabajador_pagos_caja|movimientos_caja|cortes_caja|cajas)\b/i',
        $workerSimulatorCode
    );

    if (
        !$simulatorWriteForbidden
        && strpos($workerModelCode, 'function simuladorPagoCajaPorHotel') !== false
        && strpos($workerModelCode, 'function tablasSimuladorPagoCajaDisponibles') !== false
        && strpos($workerModelCode, 'function corteAbiertoSimuladorPagoCaja') !== false
        && strpos($workerModelCode, 'function pagosCajaResumenPorTrabajador') !== false
        && strpos($workerControllerCode, 'function simuladorPagoCajaAction') !== false
        && strpos($workerControllerCode, 'trabajadores/simulador_pago_caja') !== false
        && strpos($workerSimulatorViewCode, "action=\"<?= url('trabajadores/pagos-caja/simulador') ?>\"") !== false
        && strpos($workerSimulatorViewCode, 'method="GET"') !== false
        && strpos($workerSimulatorViewCode, 'method="POST"') === false
        && strpos($workerSimulatorViewCode, 'csrf_field()') === false
        && strpos($workerSimulatorViewCode, 'Solo GET') !== false
        && strpos($workerSimulatorViewCode, 'registrar-pago-caja') === false
    ) {
        lpcOk('Simulador 5E-C-A de pagos laborales con Caja es read-only y no expone pagos reales.');
    } else {
        lpcWarning(
            'Simulador 5E-C-A incompleto o con riesgo de escritura.',
            'Mantener solo GET/read-only, sin POST, sin CSRF, sin servicio de pago y sin escrituras a Caja.'
        );
    }
} else {
    lpcWarning(
        'Archivos del simulador 5E-C-A no estan completos.',
        'Crear modelo/controlador/vista read-only antes de probar pagos laborales con Caja.'
    );
}

if (is_file($auditServicePath)) {
    $auditCode = (string) file_get_contents($auditServicePath);
    if (strpos($auditCode, 'class AuditService') !== false) {
        lpcOk('AuditService disponible para futura auditoria de pagos laborales.');
    } else {
        lpcWarning('AuditService existe pero no declara class AuditService.', 'Revisar trazabilidad antes de pagos reales.');
    }
} else {
    lpcWarning('No se encontro AuditService.', 'Una fase real debe auditar pago laboral, errores y reversion futura.');
}

if (is_file($paymentServicePath)) {
    $paymentServiceCode = (string) file_get_contents($paymentServicePath);
    if (
        strpos($paymentServiceCode, 'class TrabajadorPagoCajaService') !== false
        && strpos($paymentServiceCode, 'function evaluarPago') !== false
        && strpos($paymentServiceCode, 'function registrarPago') !== false
        && strpos($paymentServiceCode, 'manage_transaction') !== false
        && strpos($paymentServiceCode, 'FOR UPDATE') !== false
        && strpos($paymentServiceCode, 'INSERT INTO movimientos_caja') !== false
        && strpos($paymentServiceCode, 'INSERT INTO trabajador_pagos_caja') !== false
        && strpos($paymentServiceCode, "'Pago laboral'") !== false
        && strpos($paymentServiceCode, 'AuditService::record') !== false
        && strpos($paymentServiceCode, 'assertReferenciaNoDuplicada') !== false
        && strpos($paymentServiceCode, 'rollBack') !== false
    ) {
        lpcOk('Servicio TrabajadorPagoCajaService 5E-D-A existe con transaccion, locks, Caja, entidad laboral y auditoria.');
    } else {
        lpcError(
            'Servicio TrabajadorPagoCajaService incompleto para 5E-D-A.',
            'Revisar transaccion, FOR UPDATE, duplicados, INSERT Caja, INSERT trabajador_pagos_caja y AuditService.'
        );
    }
} else {
    lpcError(
        'Falta servicio TrabajadorPagoCajaService para 5E-D-A.',
        'Crear servicio transaccional antes de exponer el POST de pago laboral.'
    );
}

if (is_file($rollbackToolPath)) {
    $rollbackCode = (string) file_get_contents($rollbackToolPath);
    if (
        strpos($rollbackCode, 'manage_transaction') !== false
        && strpos($rollbackCode, 'false') !== false
        && strpos($rollbackCode, 'rollBack') !== false
        && strpos($rollbackCode, 'trabajador_pagos_caja') !== false
        && strpos($rollbackCode, 'movimientos_caja') !== false
        && strpos($rollbackCode, 'TEST-ROLLBACK-5E-D-A') !== false
    ) {
        lpcOk('Prueba rollback probar_pago_laboral_caja.php disponible y reversible.');
    } else {
        lpcError(
            'Prueba rollback probar_pago_laboral_caja.php incompleta.',
            'Debe usar transaccion externa, manage_transaction false y confirmar rollback sin persistencia.'
        );
    }
} else {
    lpcError(
        'Falta prueba rollback probar_pago_laboral_caja.php.',
        'Crear herramienta reversible antes de cerrar 5E-D-A.'
    );
}

$unexpectedNeedles = [
    'pago-laboral-caja',
    'movimiento_caja_id',
];
$scanPaths = [
    $workerIndexViewPath,
    $workerReportViewPath,
    $workerCashSimulatorViewPath,
];

foreach ($scanPaths as $path) {
    if (!is_file($path)) {
        lpcWarning('No se encontro archivo esperado para escaneo: ' . $path, 'Revisar instalacion del modulo Personal.');
        continue;
    }

    $matches = lpcFileContainsAny($path, $unexpectedNeedles);
    if ($matches === []) {
        lpcOk('Archivo Personal sin acciones reales de pago laboral fuera del detalle: ' . basename($path) . '.');
    } else {
        lpcError(
            'Archivo Personal contiene referencias financieras fuera del detalle autorizado: ' . basename($path) . ' -> ' . implode(', ', $matches) . '.',
            'Mantener pago laboral real solo en detalle trabajador, ruta POST, servicio y prueba rollback.'
        );
    }
}

if (is_file($workerControllerPath)) {
    $workerControllerCode = (string) file_get_contents($workerControllerPath);
    if (
        strpos($workerControllerCode, 'function registrarPagoCajaAction') !== false
        && strpos($workerControllerCode, "require_hotel_module('caja')") !== false
        && strpos($workerControllerCode, "requireWritePermission('usuarios.edit')") !== false
        && strpos($workerControllerCode, 'validateCSRF()') !== false
        && strpos($workerControllerCode, 'consumirPagoCajaToken') !== false
        && strpos($workerControllerCode, 'pagoCajaService->registrarPago') !== false
    ) {
        lpcOk('Controlador Trabajador delega POST 5E-D-A con permiso, modulo Caja, CSRF y token.');
    } else {
        lpcError(
            'Controlador Trabajador no cumple guardas 5E-D-A.',
            'Verificar registrarPagoCajaAction con usuarios.edit, Caja, CSRF, token y servicio.'
        );
    }
}
if (is_file($workerDetailViewPath)) {
    $viewCode = (string) file_get_contents($workerDetailViewPath);
    if (
        strpos($viewCode, 'Pago laboral con Caja') !== false
        && strpos($viewCode, 'registrar-pago-caja') !== false
        && strpos($viewCode, 'method="POST"') !== false
        && strpos($viewCode, 'csrf_field()') !== false
        && strpos($viewCode, 'name="pago_token"') !== false
        && strpos($viewCode, 'name="monto"') !== false
        && strpos($viewCode, 'name="metodo_pago"') !== false
        && strpos($viewCode, 'name="referencia"') !== false
    ) {
        lpcOk('Vista de trabajador expone pago laboral 5E-D-A con form POST, CSRF, token, monto, metodo y referencia.');
    } else {
        lpcError(
            'Vista de trabajador no expone correctamente el panel 5E-D-A.',
            'Agregar panel de pago laboral con Caja en detalle, sin tocar formularios existentes.'
        );
    }

    if (strpos($viewCode, 'No genera Caja') !== false || strpos($viewCode, 'seguimiento interno de Personal') !== false) {
        lpcOk('Vista de trabajador conserva aviso de ledger informativo separado del pago real.');
    } else {
        lpcWarning(
            'Vista de trabajador no separa claramente ledger informativo y pago real.',
            'Mantener copy visible para evitar confundir conceptos laborales con nomina pagada.'
        );
    }
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
