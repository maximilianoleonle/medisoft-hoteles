<?php
/**
 * Health check tecnico Fase 1A/1B/1C/2A/2B/2C/2D/2E/2F/2G/2H/2I/2J/2K/2L/2M/2N/2O/2P/2Q/2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3A/3B/3C-C/4D/NP-C-D-A/5E-B-A/5E-C-A/5E-D-A/TLM-G/OP-A/9A-B-A/9C-A/9C-B-A/10A-A/10A-B-A/10B-A/11A-A/MANT-A/MANT-B/MANT-C-A/MANT-D-A/MANT-E-A/MANT-G-B-A/LIM-B-A.
 *
 * Solo lectura. No ejecuta migraciones ni modifica datos.
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

function hcLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function hcOk(string $message): void
{
    global $ok;
    $ok++;
    hcLine('OK', $message);
}

function hcWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    hcLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function hcError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    hcLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function hcQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function hcFindFirstExistingPath(array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if ($candidate && file_exists($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    return null;
}

function hcParseEnvFile(string $path): array
{
    $values = [];
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

function hcTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcIndexExists(PDO $pdo, string $database, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND INDEX_NAME = :index'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'index' => $index]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcConstraintExists(PDO $pdo, string $database, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'constraint' => $constraint]);

    return (int) $stmt->fetchColumn() > 0;
}

function hcCountRows(PDO $pdo, string $table): ?int
{
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table));
        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcCountScalar(PDO $pdo, string $sql): ?int
{
    try {
        $stmt = $pdo->query($sql);

        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcMethodBody(string $code, string $method): string
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

function hcCodeBodyIsReadOnly(string $body): bool
{
    return !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|REPLACE\s+INTO|ALTER\s+TABLE|DROP\s+TABLE|TRUNCATE)\b/i', $body);
}

function hcReportZeroCount(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        hcWarning(
            'No se pudo validar consistencia CxP: ' . $label . '.',
            'Revisar manualmente el esquema antes de avanzar con CxP operativa.'
        );
        return;
    }

    if ($count === 0) {
        hcOk('Consistencia CxP OK: ' . $label . ' = 0.');
        return;
    }

    hcError('Consistencia CxP fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function hcCxpCajaTextPredicate(PDO $pdo, string $database): ?string
{
    $columns = [];
    foreach (['descripcion', 'referencia', 'proveedor', 'categoria', 'comprobante', 'motivo_edicion'] as $column) {
        if (hcColumnExists($pdo, $database, 'movimientos_caja', $column)) {
            $quoted = hcQuoteIdentifier($column);
            $columns[] = 'COALESCE(' . $quoted . ", '')";
        }
    }

    if (empty($columns)) {
        return null;
    }

    return "LOWER(CONCAT_WS(' ', " . implode(', ', $columns) . ")) REGEXP 'cxp|cuenta por pagar|cuentas por pagar'";
}

function hcReportCxpConsistency(PDO $pdo, string $database): void
{
    $cxpMovementCount = hcCountRows($pdo, 'cuentas_por_pagar_movimientos');
    hcOk('Consistencia CxP: movimientos referenciales actuales = ' . (string)$cxpMovementCount . '.');
    hcReportZeroCount(
        'movimientos CxP inconsistentes',
        hcCountScalar($pdo, "SELECT COUNT(*)
            FROM cuentas_por_pagar_movimientos m
            LEFT JOIN cuentas_por_pagar cxp
              ON cxp.id = m.cuenta_por_pagar_id
             AND cxp.hotel_id = m.hotel_id
            WHERE cxp.id IS NULL
               OR m.hotel_id IS NULL
               OR m.monto <= 0
               OR (
                   m.tipo_movimiento <> 'CANCELACION'
                   AND m.saldo_anterior < m.saldo_posterior
               )
               OR (
                   m.tipo_movimiento = 'CANCELACION'
                   AND m.saldo_anterior > m.saldo_posterior
               )
               OR m.saldo_posterior < 0"),
        'Revisar movimientos CxP antes de operar pagos proveedores.'
    );

    $checks = [
        [
            'label' => 'CxP duplicada por compra y hotel',
            'sql' => "SELECT COUNT(*) FROM (
                SELECT hotel_id, compra_id
                FROM cuentas_por_pagar
                WHERE compra_id IS NOT NULL
                GROUP BY hotel_id, compra_id
                HAVING COUNT(*) > 1
            ) duplicadas",
            'recommendation' => 'No generar nuevas CxP hasta dejar una sola cuenta por compra/hotel.',
        ],
        [
            'label' => 'CxP con compra inexistente',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                LEFT JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.id IS NULL",
            'recommendation' => 'Reconciliar compra_id antes de exponer generacion o reportes CxP.',
        ],
        [
            'label' => 'CxP con proveedor inexistente',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                LEFT JOIN proveedores p ON p.id = cxp.proveedor_id
                WHERE p.id IS NULL",
            'recommendation' => 'Reconciliar proveedor_id antes de permitir CxP operativa.',
        ],
        [
            'label' => 'CxP con hotel_id nulo',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE hotel_id IS NULL',
            'recommendation' => 'Asignar hotel_id por respaldo verificado antes de usar CxP.',
        ],
        [
            'label' => 'CxP con compra de otro hotel',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.hotel_id <> cxp.hotel_id",
            'recommendation' => 'Bloquear la cuenta y reconciliar hotel_id de compra/CxP.',
        ],
        [
            'label' => 'CxP con proveedor de otro hotel',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN proveedores p ON p.id = cxp.proveedor_id
                WHERE p.hotel_id <> cxp.hotel_id",
            'recommendation' => 'Bloquear la cuenta y reconciliar hotel_id de proveedor/CxP.',
        ],
        [
            'label' => 'CxP generada desde compra no recibida',
            'sql' => "SELECT COUNT(*)
                FROM cuentas_por_pagar cxp
                JOIN compras c ON c.id = cxp.compra_id
                WHERE cxp.compra_id IS NOT NULL
                  AND c.estado <> 'recibida'",
            'recommendation' => 'Anular avance CxP sobre compras no recibidas y revisar el origen manual.',
        ],
        [
            'label' => 'CxP con saldo mayor al total',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE saldo > total',
            'recommendation' => 'Corregir saldo/total antes de habilitar pagos o reportes financieros.',
        ],
        [
            'label' => 'CxP con total invalido o saldo negativo',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE total <= 0 OR saldo < 0',
            'recommendation' => 'Revisar totales de origen antes de permitir nuevas generaciones.',
        ],
        [
            'label' => 'CxP sin fecha de emision',
            'sql' => 'SELECT COUNT(*) FROM cuentas_por_pagar WHERE fecha_emision IS NULL',
            'recommendation' => 'Completar fecha_emision desde la compra validada antes de operar CxP.',
        ],
    ];

    foreach ($checks as $check) {
        hcReportZeroCount($check['label'], hcCountScalar($pdo, $check['sql']), $check['recommendation']);
    }

    if (hcTableExists($pdo, $database, 'movimientos_caja')) {
        $predicate = hcCxpCajaTextPredicate($pdo, $database);
        if ($predicate === null) {
            hcWarning(
                'No se pudo validar movimientos de Caja relacionados con CxP: faltan columnas textuales conocidas.',
                'Confirmar manualmente que los pagos CxP en Caja sean gastos con corte del mismo hotel.'
            );
        } else {
            $cxpCashCount = hcCountScalar($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE ' . $predicate);
            hcOk('Consistencia CxP: movimientos de Caja con referencia textual a CxP = ' . (string)$cxpCashCount . '.');
            hcReportZeroCount(
                'movimientos de Caja CxP inconsistentes',
                hcCountScalar($pdo, "SELECT COUNT(*)
                    FROM movimientos_caja
                    WHERE " . $predicate . "
                      AND (
                          hotel_id IS NULL
                          OR NOT (
                              (tipo = 'gasto' AND categoria = 'Pago proveedor')
                              OR (tipo = 'ingreso' AND categoria = 'Reversion Pago proveedor')
                          )
                          OR monto <= 0
                          OR corte_id IS NULL
                          OR NOT EXISTS (
                              SELECT 1
                              FROM cortes_caja cc
                              WHERE cc.id = movimientos_caja.corte_id
                                AND cc.hotel_id = movimientos_caja.hotel_id
                          )
                      )"),
                'Revisar movimientos_caja relacionados con CxP; deben ser gastos con corte del mismo hotel.'
            );
        }
    } else {
        hcWarning(
            'No se pudo validar movimientos de Caja relacionados con CxP porque falta movimientos_caja.',
            'Revisar esquema de Caja antes de habilitar pagos.'
        );
    }
}

function hcReportTlmZeroCount(string $label, ?int $count, string $recommendation): void
{
    if ($count === null) {
        hcWarning(
            'No se pudo validar consistencia TLM: ' . $label . '.',
            'Revisar manualmente tareas_operativas/tarea_eventos antes de avanzar con automatizaciones.'
        );
        return;
    }

    if ($count === 0) {
        hcOk('Consistencia TLM OK: ' . $label . ' = 0.');
        return;
    }

    hcError('Consistencia TLM fallo: ' . $label . ' = ' . (string)$count . '.', $recommendation);
}

function hcTlmCajaTextPredicate(PDO $pdo, string $database): ?string
{
    $columns = [];
    foreach (['descripcion', 'referencia', 'proveedor', 'categoria', 'comprobante', 'motivo_edicion'] as $column) {
        if (hcColumnExists($pdo, $database, 'movimientos_caja', $column)) {
            $quoted = hcQuoteIdentifier($column);
            $columns[] = 'COALESCE(' . $quoted . ", '')";
        }
    }

    if (empty($columns)) {
        return null;
    }

    return "LOWER(CONCAT_WS(' ', " . implode(', ', $columns) . ")) REGEXP 'tareas_operativas|tarea operativa|tarea #[0-9]+'";
}

function hcReportTlmConsistency(PDO $pdo, string $database): void
{
    if (!hcTableExists($pdo, $database, 'tareas_operativas') || !hcTableExists($pdo, $database, 'tarea_eventos')) {
        hcWarning(
            'No se pudo validar consistencia TLM-G porque faltan tablas base.',
            'Aplicar TLM-A con backup antes de ejecutar checks de tareas.'
        );
        return;
    }

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
            'recommendation' => 'Normalizar estados antes de exponer automatizaciones de limpieza/mantenimiento.',
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
            'recommendation' => 'Completar fecha_inicio o regresar la tarea a estado activo consistente.',
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
            'recommendation' => 'Corregir calendario de tareas antes de usar alertas operativas.',
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
        hcReportTlmZeroCount($check['label'], hcCountScalar($pdo, $check['sql']), $check['recommendation']);
    }

    $entityChecks = [
        [
            'table' => 'habitaciones',
            'column' => 'habitacion_id',
            'label' => 'tareas con habitacion inexistente',
            'hotel_label' => 'tareas con habitacion de otro hotel',
        ],
        [
            'table' => 'trabajadores',
            'column' => 'trabajador_id',
            'label' => 'tareas con trabajador inexistente',
            'hotel_label' => 'tareas con trabajador de otro hotel',
        ],
        [
            'table' => 'mantenimientos_habitaciones',
            'column' => 'mantenimiento_id',
            'label' => 'tareas con mantenimiento inexistente',
            'hotel_label' => 'tareas con mantenimiento de otro hotel',
        ],
        [
            'table' => 'reservaciones',
            'column' => 'reservacion_id',
            'label' => 'tareas con reservacion inexistente',
            'hotel_label' => 'tareas con reservacion de otro hotel',
        ],
        [
            'table' => 'huespedes',
            'column' => 'huesped_id',
            'label' => 'tareas con huesped inexistente',
            'hotel_label' => 'tareas con huesped de otro hotel',
        ],
    ];

    foreach ($entityChecks as $check) {
        if (!hcTableExists($pdo, $database, $check['table'])) {
            hcWarning(
                'No se pudo validar ' . $check['label'] . ' porque falta ' . $check['table'] . '.',
                'Revisar esquema antes de vincular tareas a ' . $check['table'] . '.'
            );
            continue;
        }

        if (!hcColumnExists($pdo, $database, $check['table'], 'hotel_id')) {
            $column = hcQuoteIdentifier($check['column']);
            hcReportTlmZeroCount(
                'tareas con ' . $check['column'] . ' directo sin scope hotel',
                hcCountScalar($pdo, "SELECT COUNT(*)
                    FROM tareas_operativas
                    WHERE {$column} IS NOT NULL"),
                'No vincular tareas a ' . $check['table'] . ' hasta tener una ruta de scope por hotel.'
            );
            continue;
        }

        $table = hcQuoteIdentifier($check['table']);
        $column = hcQuoteIdentifier($check['column']);
        hcReportTlmZeroCount(
            $check['label'],
            hcCountScalar($pdo, "SELECT COUNT(*)
                FROM tareas_operativas t
                LEFT JOIN {$table} e ON e.id = t.{$column}
                WHERE t.{$column} IS NOT NULL
                  AND e.id IS NULL"),
            'Reconciliar ' . $check['column'] . ' antes de usar vistas contextuales.'
        );
        hcReportTlmZeroCount(
            $check['hotel_label'],
            hcCountScalar($pdo, "SELECT COUNT(*)
                FROM tareas_operativas t
                JOIN {$table} e ON e.id = t.{$column}
                WHERE t.{$column} IS NOT NULL
                  AND e.hotel_id <> t.hotel_id"),
            'Bloquear automatizaciones hasta alinear hotel_id de tareas y entidad vinculada.'
        );
    }

    if (hcTableExists($pdo, $database, 'trabajadores')) {
        $inactiveAssigned = hcCountScalar($pdo, "SELECT COUNT(*)
            FROM tareas_operativas t
            JOIN trabajadores tr
              ON tr.id = t.trabajador_id
             AND tr.hotel_id = t.hotel_id
            WHERE t.trabajador_id IS NOT NULL
              AND tr.estado <> 'activo'
              AND t.estado IN ('pendiente', 'asignada', 'en_proceso')");
        if ($inactiveAssigned === 0) {
            hcOk('Consistencia TLM OK: tareas activas asignadas a trabajadores inactivos = 0.');
        } elseif ($inactiveAssigned === null) {
            hcWarning(
                'No se pudo validar trabajadores inactivos asignados a tareas activas.',
                'Revisar manualmente antes de planear turnos o asignaciones.'
            );
        } else {
            hcWarning(
                'Tareas activas asignadas a trabajadores inactivos: ' . (string)$inactiveAssigned . '.',
                'Reasignar o cancelar tareas antes de usar operacion diaria.'
            );
        }
    }

    if (hcTableExists($pdo, $database, 'movimientos_caja')) {
        $predicate = hcTlmCajaTextPredicate($pdo, $database);
        if ($predicate === null) {
            hcWarning(
                'No se pudo validar movimientos de Caja relacionados con TLM: faltan columnas textuales conocidas.',
                'Confirmar manualmente que TLM no haya escrito movimientos_caja.'
            );
        } else {
            hcReportTlmZeroCount(
                'movimientos de Caja con referencia textual a tarea operativa',
                hcCountScalar($pdo, 'SELECT COUNT(*) FROM movimientos_caja WHERE ' . $predicate),
                'Revisar movimientos_caja; TLM no debe crear pagos, abonos ni movimientos de Caja.'
            );
        }
    }
}

function hcNullHotelRows(PDO $pdo, string $table): ?int
{
    try {
        $stmt = $pdo->query(
            'SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table) . ' WHERE hotel_id IS NULL'
        );
        return $stmt ? (int) $stmt->fetchColumn() : null;
    } catch (Throwable $e) {
        return null;
    }
}

function hcRouteRegex(string $route): string
{
    $quoted = preg_quote(trim($route, '/'), '#');
    $quoted = preg_replace('#\\\\\{[^}]+\\\\\}#', '[^/]+', $quoted);
    return '#^' . $quoted . '$#';
}

function hcParseRoutes(string $routesPath): array
{
    $raw = file_get_contents($routesPath);
    if ($raw === false) {
        return [];
    }

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

function hcRouteExists(array $routes, string $path, ?string $method = null): bool
{
    $normalized = trim($path, '/');
    foreach ($routes as $route) {
        if ($method !== null && strtolower($route['method']) !== strtolower($method)) {
            continue;
        }
        if (trim($route['path'], '/') === $normalized) {
            return true;
        }
    }

    return false;
}

function hcRoutePatternExists(array $routes, string $samplePath, ?string $method = null): bool
{
    $sample = trim($samplePath, '/');
    foreach ($routes as $route) {
        if ($method !== null && strtolower($route['method']) !== strtolower($method)) {
            continue;
        }
        if (preg_match(hcRouteRegex($route['path']), $sample)) {
            return true;
        }
    }

    return false;
}

function hcExtractMethodCode(string $code, string $method): string
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

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$controllersDir = $appRoot . '/app/controllers';
$sidebarPath = $appRoot . '/app/views/layout/sidebar.php';
$providerReconciliationTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/reconciliar_proveedores_dry_run.php',
    $projectRoot . '/src/tools/saas/reconciliar_proveedores_dry_run.php',
    getcwd() . '/tools/saas/reconciliar_proveedores_dry_run.php',
    dirname(getcwd()) . '/src/tools/saas/reconciliar_proveedores_dry_run.php',
    '/workspace/src/tools/saas/reconciliar_proveedores_dry_run.php',
]);
$providerImportTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/importar_proveedores_los_cedros.php',
    $projectRoot . '/src/tools/saas/importar_proveedores_los_cedros.php',
    getcwd() . '/tools/saas/importar_proveedores_los_cedros.php',
    dirname(getcwd()) . '/src/tools/saas/importar_proveedores_los_cedros.php',
    '/workspace/src/tools/saas/importar_proveedores_los_cedros.php',
]);
$providerImportReviewFile = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/proveedores_los_cedros_reconciliation.json',
    $projectRoot . '/src/tools/saas/proveedores_los_cedros_reconciliation.json',
    getcwd() . '/tools/saas/proveedores_los_cedros_reconciliation.json',
    dirname(getcwd()) . '/src/tools/saas/proveedores_los_cedros_reconciliation.json',
    '/workspace/src/tools/saas/proveedores_los_cedros_reconciliation.json',
]);
$minimalPurchasingPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_compras_minimas.php',
    $projectRoot . '/src/tools/saas/preflight_compras_minimas.php',
    getcwd() . '/tools/saas/preflight_compras_minimas.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_compras_minimas.php',
    '/workspace/src/tools/saas/preflight_compras_minimas.php',
]);
$purchaseReceptionPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_recepcion_compras.php',
    $projectRoot . '/src/tools/saas/preflight_recepcion_compras.php',
    getcwd() . '/tools/saas/preflight_recepcion_compras.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_recepcion_compras.php',
    '/workspace/src/tools/saas/preflight_recepcion_compras.php',
]);
$providerCashPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_pagos_proveedores_caja.php',
    $projectRoot . '/src/tools/saas/preflight_pagos_proveedores_caja.php',
    getcwd() . '/tools/saas/preflight_pagos_proveedores_caja.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_pagos_proveedores_caja.php',
    '/workspace/src/tools/saas/preflight_pagos_proveedores_caja.php',
]);
$operationalTasksPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_tareas_operativas.php',
    $projectRoot . '/src/tools/saas/preflight_tareas_operativas.php',
    getcwd() . '/tools/saas/preflight_tareas_operativas.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_tareas_operativas.php',
    '/workspace/src/tools/saas/preflight_tareas_operativas.php',
]);
$workerLedgerPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_personal_ledger.php',
    $projectRoot . '/src/tools/saas/preflight_personal_ledger.php',
    getcwd() . '/tools/saas/preflight_personal_ledger.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_personal_ledger.php',
    '/workspace/src/tools/saas/preflight_personal_ledger.php',
]);
$workerCashPaymentPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_personal_pagos_caja.php',
    $projectRoot . '/src/tools/saas/preflight_personal_pagos_caja.php',
    getcwd() . '/tools/saas/preflight_personal_pagos_caja.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_personal_pagos_caja.php',
    '/workspace/src/tools/saas/preflight_personal_pagos_caja.php',
]);
$workerPayrollOfficialBoundaryPreflight = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/preflight_frontera_nomina_oficial.php',
    $projectRoot . '/src/tools/saas/preflight_frontera_nomina_oficial.php',
    getcwd() . '/tools/saas/preflight_frontera_nomina_oficial.php',
    dirname(getcwd()) . '/src/tools/saas/preflight_frontera_nomina_oficial.php',
    '/workspace/src/tools/saas/preflight_frontera_nomina_oficial.php',
]);
$workerCashPaymentServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/TrabajadorPagoCajaService.php',
    $projectRoot . '/src/app/services/TrabajadorPagoCajaService.php',
    getcwd() . '/app/services/TrabajadorPagoCajaService.php',
    dirname(getcwd()) . '/src/app/services/TrabajadorPagoCajaService.php',
    '/workspace/src/app/services/TrabajadorPagoCajaService.php',
]);
$workerPayrollSnapshotPaymentServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/TrabajadorNominaSnapshotPagoService.php',
    $projectRoot . '/src/app/services/TrabajadorNominaSnapshotPagoService.php',
    getcwd() . '/app/services/TrabajadorNominaSnapshotPagoService.php',
    dirname(getcwd()) . '/src/app/services/TrabajadorNominaSnapshotPagoService.php',
    '/workspace/src/app/services/TrabajadorNominaSnapshotPagoService.php',
]);
$workerCashPaymentRollbackTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_pago_laboral_caja.php',
    $projectRoot . '/src/tools/saas/probar_pago_laboral_caja.php',
    getcwd() . '/tools/saas/probar_pago_laboral_caja.php',
    dirname(getcwd()) . '/src/tools/saas/probar_pago_laboral_caja.php',
    '/workspace/src/tools/saas/probar_pago_laboral_caja.php',
]);
$workerPayrollPeriodRollbackTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_nomina_periodo_snapshot.php',
    $projectRoot . '/src/tools/saas/probar_nomina_periodo_snapshot.php',
    getcwd() . '/tools/saas/probar_nomina_periodo_snapshot.php',
    dirname(getcwd()) . '/src/tools/saas/probar_nomina_periodo_snapshot.php',
    '/workspace/src/tools/saas/probar_nomina_periodo_snapshot.php',
]);
$workerPayrollSnapshotPaymentRollbackTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_pago_snapshot_prenomina_caja.php',
    $projectRoot . '/src/tools/saas/probar_pago_snapshot_prenomina_caja.php',
    getcwd() . '/tools/saas/probar_pago_snapshot_prenomina_caja.php',
    dirname(getcwd()) . '/src/tools/saas/probar_pago_snapshot_prenomina_caja.php',
    '/workspace/src/tools/saas/probar_pago_snapshot_prenomina_caja.php',
]);
$purchaseServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/CompraService.php',
    $projectRoot . '/src/app/services/CompraService.php',
    getcwd() . '/app/services/CompraService.php',
    dirname(getcwd()) . '/src/app/services/CompraService.php',
    '/workspace/src/app/services/CompraService.php',
]);
$purchaseControllerFile = hcFindFirstExistingPath([
    $appRoot . '/app/controllers/CompraController.php',
    $projectRoot . '/src/app/controllers/CompraController.php',
    getcwd() . '/app/controllers/CompraController.php',
    dirname(getcwd()) . '/src/app/controllers/CompraController.php',
    '/workspace/src/app/controllers/CompraController.php',
]);
$purchaseIndexViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/index.php',
    $projectRoot . '/src/app/views/compras/index.php',
    getcwd() . '/app/views/compras/index.php',
    dirname(getcwd()) . '/src/app/views/compras/index.php',
    '/workspace/src/app/views/compras/index.php',
]);
$purchaseFormViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/form.php',
    $projectRoot . '/src/app/views/compras/form.php',
    getcwd() . '/app/views/compras/form.php',
    dirname(getcwd()) . '/src/app/views/compras/form.php',
    '/workspace/src/app/views/compras/form.php',
]);
$purchaseDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/ver.php',
    $projectRoot . '/src/app/views/compras/ver.php',
    getcwd() . '/app/views/compras/ver.php',
    dirname(getcwd()) . '/src/app/views/compras/ver.php',
    '/workspace/src/app/views/compras/ver.php',
]);
$purchaseReportViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/compras/reporte_recibidas.php',
    $projectRoot . '/src/app/views/compras/reporte_recibidas.php',
    getcwd() . '/app/views/compras/reporte_recibidas.php',
    dirname(getcwd()) . '/src/app/views/compras/reporte_recibidas.php',
    '/workspace/src/app/views/compras/reporte_recibidas.php',
]);
$providerDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/proveedores/ver.php',
    $projectRoot . '/src/app/views/proveedores/ver.php',
    getcwd() . '/app/views/proveedores/ver.php',
    dirname(getcwd()) . '/src/app/views/proveedores/ver.php',
    '/workspace/src/app/views/proveedores/ver.php',
]);
$cxpControllerFile = hcFindFirstExistingPath([
    $appRoot . '/app/controllers/CuentaPorPagarController.php',
    $projectRoot . '/src/app/controllers/CuentaPorPagarController.php',
    getcwd() . '/app/controllers/CuentaPorPagarController.php',
    dirname(getcwd()) . '/src/app/controllers/CuentaPorPagarController.php',
    '/workspace/src/app/controllers/CuentaPorPagarController.php',
]);
$cxpModelFile = hcFindFirstExistingPath([
    $appRoot . '/app/models/CuentaPorPagar.php',
    $projectRoot . '/src/app/models/CuentaPorPagar.php',
    getcwd() . '/app/models/CuentaPorPagar.php',
    dirname(getcwd()) . '/src/app/models/CuentaPorPagar.php',
    '/workspace/src/app/models/CuentaPorPagar.php',
]);
$cxpIndexViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/index.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/index.php',
    getcwd() . '/app/views/cuentas_por_pagar/index.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/index.php',
    '/workspace/src/app/views/cuentas_por_pagar/index.php',
]);
$cxpDetailViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/ver.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/ver.php',
    getcwd() . '/app/views/cuentas_por_pagar/ver.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/ver.php',
    '/workspace/src/app/views/cuentas_por_pagar/ver.php',
]);
$cxpPreviewViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/generacion_preview.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/generacion_preview.php',
    getcwd() . '/app/views/cuentas_por_pagar/generacion_preview.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/generacion_preview.php',
    '/workspace/src/app/views/cuentas_por_pagar/generacion_preview.php',
]);
$cxpCashSimulatorViewFile = hcFindFirstExistingPath([
    $appRoot . '/app/views/cuentas_por_pagar/simulador_caja.php',
    $projectRoot . '/src/app/views/cuentas_por_pagar/simulador_caja.php',
    getcwd() . '/app/views/cuentas_por_pagar/simulador_caja.php',
    dirname(getcwd()) . '/src/app/views/cuentas_por_pagar/simulador_caja.php',
    '/workspace/src/app/views/cuentas_por_pagar/simulador_caja.php',
]);
$cxpPaymentServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/CuentaPorPagarPagoService.php',
    $projectRoot . '/src/app/services/CuentaPorPagarPagoService.php',
    getcwd() . '/app/services/CuentaPorPagarPagoService.php',
    dirname(getcwd()) . '/src/app/services/CuentaPorPagarPagoService.php',
    '/workspace/src/app/services/CuentaPorPagarPagoService.php',
]);
$cxpPaymentReversalServiceFile = hcFindFirstExistingPath([
    $appRoot . '/app/services/CuentaPorPagarReversionPagoService.php',
    $projectRoot . '/src/app/services/CuentaPorPagarReversionPagoService.php',
    getcwd() . '/app/services/CuentaPorPagarReversionPagoService.php',
    dirname(getcwd()) . '/src/app/services/CuentaPorPagarReversionPagoService.php',
    '/workspace/src/app/services/CuentaPorPagarReversionPagoService.php',
]);
$cxpPaymentRollbackTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_pago_proveedor_caja.php',
    $projectRoot . '/src/tools/saas/probar_pago_proveedor_caja.php',
    getcwd() . '/tools/saas/probar_pago_proveedor_caja.php',
    dirname(getcwd()) . '/src/tools/saas/probar_pago_proveedor_caja.php',
    '/workspace/src/tools/saas/probar_pago_proveedor_caja.php',
]);
$cxpPaymentReversalRollbackTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_reversion_pago_proveedor_caja.php',
    $projectRoot . '/src/tools/saas/probar_reversion_pago_proveedor_caja.php',
    getcwd() . '/tools/saas/probar_reversion_pago_proveedor_caja.php',
    dirname(getcwd()) . '/src/tools/saas/probar_reversion_pago_proveedor_caja.php',
    '/workspace/src/tools/saas/probar_reversion_pago_proveedor_caja.php',
]);
$purchaseTestTool = hcFindFirstExistingPath([
    $appRoot . '/tools/saas/probar_compra_service.php',
    $projectRoot . '/src/tools/saas/probar_compra_service.php',
    getcwd() . '/tools/saas/probar_compra_service.php',
    dirname(getcwd()) . '/src/tools/saas/probar_compra_service.php',
    '/workspace/src/tools/saas/probar_compra_service.php',
]);
$minimalPurchasingDraft = hcFindFirstExistingPath([
    $projectRoot . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    getcwd() . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    dirname(getcwd()) . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    dirname(dirname(getcwd())) . '/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
    '/workspace/docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql',
]);
$cxpBaseDraft = hcFindFirstExistingPath([
    $projectRoot . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    getcwd() . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    dirname(getcwd()) . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    dirname(dirname(getcwd())) . '/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
    '/workspace/docs/technical/sql_drafts/20260615_003_fase_3b_cxp_base_draft.sql',
]);
$minimalPurchasingOfficialMigration = hcFindFirstExistingPath([
    $projectRoot . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    getcwd() . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    dirname(getcwd()) . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    dirname(dirname(getcwd())) . '/migrations/20260615_002_fase_2n_compras_minimas.sql',
    '/workspace/migrations/20260615_002_fase_2n_compras_minimas.sql',
]);
$cxpOfficialMigration = hcFindFirstExistingPath([
    $projectRoot . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    getcwd() . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    dirname(getcwd()) . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    dirname(dirname(getcwd())) . '/migrations/20260615_003_fase_3b_cxp_base.sql',
    '/workspace/migrations/20260615_003_fase_3b_cxp_base.sql',
]);
$docsTechnicalDir = hcFindFirstExistingPath([
    getenv('DOCS_TECHNICAL_DIR') ?: '',
    getenv('PROJECT_ROOT') ? rtrim((string) getenv('PROJECT_ROOT'), "/\\") . '/docs/technical' : '',
    $projectRoot . '/docs/technical',
    dirname($projectRoot) . '/docs/technical',
    getcwd() . '/docs/technical',
    dirname(getcwd()) . '/docs/technical',
    dirname(dirname(getcwd())) . '/docs/technical',
    '/workspace/docs/technical',
]);
$technicalClosureDoc = hcFindFirstExistingPath([
    getenv('PROJECT_ROOT') ? rtrim((string) getenv('PROJECT_ROOT'), "/\\") . '/docs/cierre-tecnico-bloque-cola.md' : '',
    $projectRoot . '/docs/cierre-tecnico-bloque-cola.md',
    dirname($projectRoot) . '/docs/cierre-tecnico-bloque-cola.md',
    getcwd() . '/docs/cierre-tecnico-bloque-cola.md',
    dirname(getcwd()) . '/docs/cierre-tecnico-bloque-cola.md',
    dirname(dirname(getcwd())) . '/docs/cierre-tecnico-bloque-cola.md',
    '/var/www/docs/cierre-tecnico-bloque-cola.md',
    '/workspace/docs/cierre-tecnico-bloque-cola.md',
]);
$inventoryDoc = $docsTechnicalDir ? $docsTechnicalDir . '/inventory_reconciliation.md' : null;
$duplicatedTablesDoc = $docsTechnicalDir ? $docsTechnicalDir . '/duplicated_tables.md' : null;
$purchasingInventoryDoc = $docsTechnicalDir ? $docsTechnicalDir . '/purchasing_inventory_contract.md' : null;

echo "Health check Fase 1A-4D/NP-C-D-A/5E-B-A/5E-C-A/5E-D-A/TLM-G/OP-A/9A-B-A/9C-A/9C-B-A/10A-A/10A-B-A/10B-A/11A-A/MANT-A/MANT-B/MANT-C-A/MANT-D-A/MANT-E-A/MANT-G-B-A/LIM-B-A - Medisoft Hoteles\n";
echo "============================================================\n";

if (!is_file($configPath)) {
    hcError('No se encontro config/database.php.', 'Verificar montaje de src antes de ejecutar el checker.');
    exit(1);
}

if ($docsTechnicalDir) {
    hcOk('Directorio docs/technical detectado: ' . $docsTechnicalDir);
} else {
    hcWarning(
        'Directorio docs/technical no esta accesible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar docs/technical para validar documentacion tecnica.'
    );
}

if ($providerReconciliationTool && is_file($providerReconciliationTool)) {
    $providerReconciliationCode = (string) file_get_contents($providerReconciliationTool);
    if (
        strpos($providerReconciliationCode, 'Solo lectura') !== false
        && strpos($providerReconciliationCode, 'movimientos_caja.proveedor') !== false
        && strpos($providerReconciliationCode, 'writes_db') !== false
    ) {
        hcOk('Herramienta dry-run de reconciliacion de proveedores existe y esta marcada como solo lectura.');
    } else {
        hcWarning(
            'Herramienta dry-run de proveedores existe pero no declara claramente su alcance.',
            'Asegurar que src/tools/saas/reconciliar_proveedores_dry_run.php documente solo lectura y no escritura.'
        );
    }
} else {
    hcWarning(
        'No existe herramienta dry-run de reconciliacion de proveedores.',
        'Crear src/tools/saas/reconciliar_proveedores_dry_run.php antes de preparar una importacion real.'
    );
}

if ($providerImportTool && is_file($providerImportTool) && $providerImportReviewFile && is_file($providerImportReviewFile)) {
    $providerImportCode = (string) file_get_contents($providerImportTool);
    $providerReviewCode = (string) file_get_contents($providerImportReviewFile);
    if (
        strpos($providerImportCode, '--apply') !== false
        && strpos($providerImportCode, '--backup-file') !== false
        && strpos($providerImportCode, 'beginTransaction') !== false
        && strpos($providerReviewCode, '"phase": "2L"') !== false
        && strpos($providerReviewCode, '"approved": false') !== false
    ) {
        hcOk('Importador revisable Fase 2L de proveedores existe y exige backup para aplicar.');
    } else {
        hcWarning(
            'Importador Fase 2L de proveedores existe pero no declara todas las guardas esperadas.',
            'Verificar --apply, --backup-file, transaccion y lista de revision con exclusiones.'
        );
    }
} else {
    hcWarning(
        'No existe importador revisable Fase 2L completo para proveedores de Los Cedros.',
        'Crear importar_proveedores_los_cedros.php y proveedores_los_cedros_reconciliation.json antes de importar.'
    );
}

if ($minimalPurchasingPreflight && is_file($minimalPurchasingPreflight)) {
    $minimalPurchasingPreflightCode = (string) file_get_contents($minimalPurchasingPreflight);
    if (
        strpos($minimalPurchasingPreflightCode, 'Solo lectura') !== false
        && strpos($minimalPurchasingPreflightCode, 'compras') !== false
        && strpos($minimalPurchasingPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($minimalPurchasingPreflightCode, 'proveedores') !== false
        && strpos($minimalPurchasingPreflightCode, 'CompraController') !== false
        && strpos($minimalPurchasingPreflightCode, 'movimientos_caja') !== false
        && strpos($minimalPurchasingPreflightCode, 'cuentas-por-pagar/generacion-preview') !== false
        && strpos($minimalPurchasingPreflightCode, 'function generacionPreviewAction') !== false
        && strpos($minimalPurchasingPreflightCode, 'function previewGeneracionDesdeCompras') !== false
        && strpos($minimalPurchasingPreflightCode, '3C-C') !== false
    ) {
        hcOk('Preflight de compras minimas existe, es solo lectura y conoce Fase 2R/2S/2T/2U/2V/2W/2X/2Y/2Z/3B/3C-C.');
    } else {
        hcWarning(
            'Preflight de compras minimas existe pero no declara guardas completas.',
            'Verificar que preflight_compras_minimas.php valide solo lectura, UI de borradores, proveedores, inventario moderno y no Caja.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 2M de compras minimas.',
        'Crear src/tools/saas/preflight_compras_minimas.php antes de preparar migraciones de compras.'
    );
}

if ($purchaseReceptionPreflight && is_file($purchaseReceptionPreflight)) {
    $purchaseReceptionPreflightCode = (string) file_get_contents($purchaseReceptionPreflight);
    if (
        strpos($purchaseReceptionPreflightCode, 'Preflight Fase 2T') !== false
        && strpos($purchaseReceptionPreflightCode, '3C-C') !== false
        && strpos($purchaseReceptionPreflightCode, 'Solo lectura') !== false
        && strpos($purchaseReceptionPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($purchaseReceptionPreflightCode, 'detalles_con_movimiento') !== false
        && strpos($purchaseReceptionPreflightCode, 'productos_repetidos') !== false
        && strpos($purchaseReceptionPreflightCode, 'un movimiento por linea') !== false
        && strpos($purchaseReceptionPreflightCode, 'cuentas-por-pagar/generacion-preview') !== false
        && strpos($purchaseReceptionPreflightCode, 'generacionpreview') !== false
        && strpos($purchaseReceptionPreflightCode, 'movimientos_caja') !== false
    ) {
        hcOk('Preflight Fase 2T/2U/2V/2W/2X/2Y/2Z/3B/3C-C de recepcion de compras existe y es solo lectura.');
    } else {
        hcWarning(
            'Preflight Fase 2T de recepcion existe pero no declara todas las guardas esperadas.',
            'Verificar solo lectura, productos repetidos, detalles con movimiento y ausencia de recepcion ejecutable.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 2T de recepcion de compras.',
        'Crear src/tools/saas/preflight_recepcion_compras.php antes de exponer recepcion.'
    );
}

if ($providerCashPreflight && is_file($providerCashPreflight)) {
    $providerCashPreflightCode = (string) file_get_contents($providerCashPreflight);
    if (
        strpos($providerCashPreflightCode, 'Preflight Fase 3D') !== false
        && strpos($providerCashPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($providerCashPreflightCode, 'cuentas-por-pagar/simulador-caja') !== false
        && strpos($providerCashPreflightCode, 'registrar-pago-caja') !== false
        && strpos($providerCashPreflightCode, 'revertir-pago-caja') !== false
        && strpos($providerCashPreflightCode, 'function simuladorCajaAction') !== false
        && strpos($providerCashPreflightCode, 'function simuladorCajaProveedor') !== false
        && strpos($providerCashPreflightCode, 'CuentaPorPagarPagoService') !== false
        && strpos($providerCashPreflightCode, 'CuentaPorPagarReversionPagoService') !== false
        && strpos($providerCashPreflightCode, 'probar_pago_proveedor_caja.php') !== false
        && strpos($providerCashPreflightCode, 'probar_reversion_pago_proveedor_caja.php') !== false
        && strpos($providerCashPreflightCode, 'movimientos_caja') !== false
    ) {
        hcOk('Preflight Fase 3D de pagos proveedores existe, es solo lectura y valida POST controlado/reversion.');
    } else {
        hcWarning(
            'Preflight Fase 3D existe pero no declara todas las guardas esperadas.',
            'Verificar read-only, ruta GET simulador, POST registrar-pago-caja, POST revertir-pago-caja, servicios y pruebas rollback.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 3D-A para simulador Caja proveedores.',
        'Crear src/tools/saas/preflight_pagos_proveedores_caja.php antes de avanzar a pagos reales.'
    );
}

if ($operationalTasksPreflight && is_file($operationalTasksPreflight)) {
    $operationalTasksPreflightCode = (string) file_get_contents($operationalTasksPreflight);
    if (
        strpos($operationalTasksPreflightCode, 'Preflight Fase TLM-G') !== false
        && strpos($operationalTasksPreflightCode, 'Solo lectura') !== false
        && strpos($operationalTasksPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($operationalTasksPreflightCode, 'tareas_operativas') !== false
        && strpos($operationalTasksPreflightCode, 'tarea_eventos') !== false
        && strpos($operationalTasksPreflightCode, 'movimientos_caja') !== false
    ) {
        hcOk('Preflight Fase TLM-G de tareas operativas existe y es solo lectura.');
    } else {
        hcWarning(
            'Preflight Fase TLM-G existe pero no declara todas las guardas esperadas.',
            'Verificar solo lectura, tareas_operativas, tarea_eventos, entidades por hotel y ausencia de Caja.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase TLM-G de tareas operativas.',
        'Crear src/tools/saas/preflight_tareas_operativas.php antes de automatizar tareas.'
    );
}

if ($workerLedgerPreflight && is_file($workerLedgerPreflight)) {
    $workerLedgerPreflightCode = (string) file_get_contents($workerLedgerPreflight);
    if (
        strpos($workerLedgerPreflightCode, 'Preflight Fase NP-C-D-A') !== false
        && strpos($workerLedgerPreflightCode, 'Solo lectura') !== false
        && strpos($workerLedgerPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($workerLedgerPreflightCode, 'trabajador_pagos') !== false
        && strpos($workerLedgerPreflightCode, 'trabajador_anticipos') !== false
        && strpos($workerLedgerPreflightCode, 'trabajador_prestamos') !== false
        && strpos($workerLedgerPreflightCode, 'trabajador_asistencias') !== false
        && strpos($workerLedgerPreflightCode, 'movimientos_caja') !== false
    ) {
        hcOk('Preflight Fase NP-C-D-A de ledger laboral existe y es solo lectura/controlado.');
    } else {
        hcWarning(
            'Preflight Fase NP-C-D-A existe pero no declara todas las guardas esperadas.',
            'Verificar solo lectura, tablas trabajador_*, aislamiento hotel_id y ausencia de Caja/Nomina.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase NP-C-D-A de ledger laboral.',
        'Crear src/tools/saas/preflight_personal_ledger.php antes de habilitar escrituras laborales.'
    );
}

if ($workerCashPaymentPreflight && is_file($workerCashPaymentPreflight)) {
    $workerCashPaymentPreflightCode = (string) file_get_contents($workerCashPaymentPreflight);
    if (
        strpos($workerCashPaymentPreflightCode, 'Preflight Fase 5E-D-A') !== false
        && strpos($workerCashPaymentPreflightCode, 'Herramienta solo lectura') !== false
        && strpos($workerCashPaymentPreflightCode, 'TrabajadorPagoCajaService') !== false
        && strpos($workerCashPaymentPreflightCode, 'registrar-pago-caja') !== false
        && strpos($workerCashPaymentPreflightCode, 'trabajadores/pagos-caja/simulador') !== false
        && strpos($workerCashPaymentPreflightCode, 'simuladorPagoCajaPorHotel') !== false
        && strpos($workerCashPaymentPreflightCode, 'trabajador_pagos_caja') !== false
        && strpos($workerCashPaymentPreflightCode, 'movimientos_caja') !== false
        && strpos($workerCashPaymentPreflightCode, 'cortes_caja') !== false
        && strpos($workerCashPaymentPreflightCode, 'probar_pago_laboral_caja.php') !== false
        && strpos($workerCashPaymentPreflightCode, 'Pago laboral') !== false
    ) {
        hcOk('Preflight Fase 5E-D-A de pagos laborales con Caja existe y valida servicio/ruta/rollback.');
    } else {
        hcWarning(
            'Preflight Fase 5E-D-A existe pero no declara todas las guardas esperadas.',
            'Verificar servicio, POST registrar-pago-caja, simulador GET, tabla trabajador_pagos_caja, Caja/cortes y rollback.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Fase 5E-D-A de pagos laborales con Caja.',
        'Crear src/tools/saas/preflight_personal_pagos_caja.php antes de cerrar el pago laboral real.'
    );
}

if ($workerPayrollOfficialBoundaryPreflight && is_file($workerPayrollOfficialBoundaryPreflight)) {
    $workerPayrollOfficialBoundaryPreflightCode = (string) file_get_contents($workerPayrollOfficialBoundaryPreflight);
    if (
        strpos($workerPayrollOfficialBoundaryPreflightCode, 'Preflight Fase 5E-T-A') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'Herramienta solo lectura') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'nomOfAssertNoForbiddenRoutes') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'nomOfScanCodeSymbols') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'nomOfScanDatabaseObjects') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'TrabajadorController.php') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'Trabajador.php') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'sync_temporarily_disabled') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'cfdi') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'timbrado') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'dispersion') !== false
        && strpos($workerPayrollOfficialBoundaryPreflightCode, 'pago masivo') !== false
    ) {
        hcOk('Preflight Personal 5E-T-A de frontera nomina oficial existe y valida rutas, simbolos, DB read-only y /api/sync.');
    } else {
        hcWarning(
            'Preflight Personal 5E-T-A de frontera nomina oficial existe pero no declara todas las guardas esperadas.',
            'Verificar rutas, simbolos Personal/Nomina, DB read-only, CFDI/timbrado/dispersion/pago masivo y /api/sync bloqueado.'
        );
    }
} else {
    hcWarning(
        'No existe preflight Personal 5E-T-A de frontera nomina oficial.',
        'Crear src/tools/saas/preflight_frontera_nomina_oficial.php antes de abrir fases de nomina oficial.'
    );
}

if ($workerCashPaymentServiceFile && is_file($workerCashPaymentServiceFile)) {
    $workerCashPaymentServiceCode = (string) file_get_contents($workerCashPaymentServiceFile);
    if (
        strpos($workerCashPaymentServiceCode, 'class TrabajadorPagoCajaService') !== false
        && strpos($workerCashPaymentServiceCode, 'function evaluarPago') !== false
        && strpos($workerCashPaymentServiceCode, 'function registrarPago') !== false
        && strpos($workerCashPaymentServiceCode, 'manage_transaction') !== false
        && strpos($workerCashPaymentServiceCode, 'FOR UPDATE') !== false
        && strpos($workerCashPaymentServiceCode, 'INSERT INTO movimientos_caja') !== false
        && strpos($workerCashPaymentServiceCode, 'INSERT INTO trabajador_pagos_caja') !== false
        && strpos($workerCashPaymentServiceCode, "'Pago laboral'") !== false
        && strpos($workerCashPaymentServiceCode, 'AuditService::record') !== false
        && strpos($workerCashPaymentServiceCode, 'assertReferenciaNoDuplicada') !== false
        && strpos($workerCashPaymentServiceCode, 'normalizarTrazabilidadSnapshot') !== false
        && strpos($workerCashPaymentServiceCode, 'validarTrazabilidadSnapshot') !== false
        && strpos($workerCashPaymentServiceCode, 'trazabilidadSnapshotDisponible') !== false
        && strpos($workerCashPaymentServiceCode, 'nomina_periodo_id') !== false
        && strpos($workerCashPaymentServiceCode, 'nomina_periodo_detalle_id') !== false
        && strpos($workerCashPaymentServiceCode, 'rollBack') !== false
    ) {
        hcOk('Servicio Personal 5E-D-A/5E-P-A TrabajadorPagoCajaService controla pago laboral con Caja, locks, auditoria, rollback y trazabilidad snapshot opcional.');
    } else {
        hcError(
            'Servicio Personal 5E-D-A/5E-P-A TrabajadorPagoCajaService incompleto.',
            'Revisar transaccion, FOR UPDATE, INSERT trabajador_pagos_caja, INSERT movimientos_caja, duplicados, AuditService y trazabilidad snapshot.'
        );
    }
} else {
    hcWarning(
        'No existe TrabajadorPagoCajaService para pago laboral con Caja.',
        'Crear el servicio antes de exponer el POST 5E-D-A.'
    );
}

if ($workerPayrollSnapshotPaymentServiceFile && is_file($workerPayrollSnapshotPaymentServiceFile)) {
    $workerPayrollSnapshotPaymentServiceCode = (string) file_get_contents($workerPayrollSnapshotPaymentServiceFile);
    if (
        strpos($workerPayrollSnapshotPaymentServiceCode, 'class TrabajadorNominaSnapshotPagoService') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'function evaluarPagoDesdeSnapshot') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'function registrarPagoDesdeSnapshot') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'TrabajadorPagoCajaService') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'manage_transaction') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'FOR UPDATE') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, "estado'] ?? '') !== 'aprobado") !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'pendiente_pago_sugerido') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'min($snapshotPendiente, $saldoVivo)') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'pagoCajaService->registrarPago') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'nomina_periodo_id') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'nomina_periodo_detalle_id') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'AuditService::record') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'snapshot_inmutable') !== false
        && strpos($workerPayrollSnapshotPaymentServiceCode, 'rollBack') !== false
    ) {
        hcOk('Servicio Personal 5E-N-A/5E-P-A controla pago individual desde snapshot aprobado con Caja, min(snapshot/saldo vivo), trazabilidad, auditoria y rollback.');
    } else {
        hcError(
            'Servicio Personal 5E-N-A/5E-P-A TrabajadorNominaSnapshotPagoService incompleto.',
            'Revisar snapshot aprobado, FOR UPDATE, maximo min(snapshot/saldo vivo), delegacion trazada a TrabajadorPagoCajaService, auditoria y rollback.'
        );
    }
} else {
    hcWarning(
        'No existe TrabajadorNominaSnapshotPagoService para pago desde snapshot aprobado.',
        'Crear el servicio antes de exponer el POST 5E-N-A.'
    );
}

if ($workerCashPaymentRollbackTool && is_file($workerCashPaymentRollbackTool)) {
    $workerCashPaymentRollbackCode = (string) file_get_contents($workerCashPaymentRollbackTool);
    if (
        strpos($workerCashPaymentRollbackCode, 'manage_transaction') !== false
        && strpos($workerCashPaymentRollbackCode, 'false') !== false
        && strpos($workerCashPaymentRollbackCode, 'rollBack') !== false
        && strpos($workerCashPaymentRollbackCode, 'trabajador_pagos_caja') !== false
        && strpos($workerCashPaymentRollbackCode, 'movimientos_caja') !== false
        && strpos($workerCashPaymentRollbackCode, 'TEST-ROLLBACK-5E-D-A') !== false
    ) {
        hcOk('Prueba rollback Personal 5E-D-A existe y valida pago laboral con Caja sin persistencia.');
    } else {
        hcError(
            'Prueba rollback Personal 5E-D-A incompleta.',
            'Debe abrir transaccion externa, usar manage_transaction false, insertar temporalmente y hacer rollBack.'
        );
    }
} else {
    hcWarning(
        'No existe prueba rollback probar_pago_laboral_caja.php.',
        'Crear herramienta reversible para QA de pago laboral con Caja.'
    );
}

if ($workerPayrollPeriodRollbackTool && is_file($workerPayrollPeriodRollbackTool)) {
    $workerPayrollPeriodRollbackCode = (string) file_get_contents($workerPayrollPeriodRollbackTool);
    if (
        strpos($workerPayrollPeriodRollbackCode, 'APP_ENV') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'local') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'manage_transaction') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'false') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'rollBack') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'trabajador_nomina_periodos') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'cerrarPeriodo') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'aprobarPeriodo') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'anularPeriodo') !== false
        && strpos($workerPayrollPeriodRollbackCode, 'QA-ROLLBACK-5E-L-A') !== false
    ) {
        hcOk('Prueba rollback Personal 5E-L-A existe y valida snapshot de pre-nomina sin persistencia.');
    } else {
        hcError(
            'Prueba rollback Personal 5E-L-A incompleta.',
            'Debe exigir APP_ENV local, abrir transaccion externa, usar manage_transaction false, cerrar/aprobar/anular y hacer rollBack.'
        );
    }
} else {
    hcWarning(
        'No existe prueba rollback probar_nomina_periodo_snapshot.php.',
        'Crear herramienta reversible para QA de cierre/aprobacion/anulacion 5E-L-A.'
    );
}

if ($workerPayrollSnapshotPaymentRollbackTool && is_file($workerPayrollSnapshotPaymentRollbackTool)) {
    $workerPayrollSnapshotPaymentRollbackCode = (string) file_get_contents($workerPayrollSnapshotPaymentRollbackTool);
    if (
        strpos($workerPayrollSnapshotPaymentRollbackCode, 'APP_ENV') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'local') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'TrabajadorNominaSnapshotPagoService') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'manage_transaction') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'false') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'rollBack') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'trabajador_nomina_periodos') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'trabajador_nomina_periodo_detalles') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'trabajador_pagos_caja') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'movimientos_caja') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'TEST-ROLLBACK-5E-N-A') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'nomina_periodo_id') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'nomina_periodo_detalle_id') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'Trazabilidad 5E-P-A temporal') !== false
        && strpos($workerPayrollSnapshotPaymentRollbackCode, 'Snapshot temporal permanecio inmutable') !== false
    ) {
        hcOk('Prueba rollback Personal 5E-N-A/5E-P-A existe y valida pago trazado desde snapshot aprobado sin persistencia.');
    } else {
        hcError(
            'Prueba rollback Personal 5E-N-A/5E-P-A incompleta.',
            'Debe exigir APP_ENV local, abrir transaccion externa, usar manage_transaction false, crear snapshot aprobado, registrar pago Caja trazado y hacer rollBack.'
        );
    }
} else {
    hcWarning(
        'No existe prueba rollback probar_pago_snapshot_prenomina_caja.php.',
        'Crear herramienta reversible para QA de pago individual desde snapshot aprobado 5E-N-A.'
    );
}

if ($minimalPurchasingOfficialMigration && is_file($minimalPurchasingOfficialMigration)) {
    hcOk('Migracion oficial Fase 2O de compras minimas existe en migrations/.');
} else {
    hcWarning(
        'Migracion oficial Fase 2O de compras minimas no existe en migrations/.',
        'Promover el borrador solo con backup completo y autorizacion explicita.'
    );
}

if ($cxpBaseDraft && is_file($cxpBaseDraft)) {
    $cxpDraftCode = (string) file_get_contents($cxpBaseDraft);
    if (
        strpos($cxpDraftCode, 'NO EJECUTAR EN ESTA SUBFASE') !== false
        && strpos($cxpDraftCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar') !== false
        && strpos($cxpDraftCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar_movimientos') !== false
        && strpos($cxpDraftCode, 'uk_cxp_hotel_compra') !== false
        && strpos($cxpDraftCode, 'fk_cxp_compra') !== false
        && strpos($cxpDraftCode, 'Rollback manual documentado') !== false
        && strpos($cxpDraftCode, 'movimientos_caja') === false
    ) {
        hcOk('Borrador tecnico Fase 3B de CxP base existe para trazabilidad.');
    } else {
        hcWarning(
            'Borrador Fase 3B de CxP base incompleto o riesgoso.',
            'Mantenerlo no ejecutado, no destructivo, sin Caja y con rollback manual documentado.'
        );
    }
} else {
    hcWarning(
        'Borrador Fase 3B de CxP base no visible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar docs/technical para validar el borrador historico.'
    );
}

if ($cxpOfficialMigration && is_file($cxpOfficialMigration)) {
    $cxpMigrationCode = (string) file_get_contents($cxpOfficialMigration);
    if (
        strpos($cxpMigrationCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar') !== false
        && strpos($cxpMigrationCode, 'CREATE TABLE IF NOT EXISTS cuentas_por_pagar_movimientos') !== false
        && strpos($cxpMigrationCode, '20260615_003_fase_3b_cxp_base.sql') !== false
        && strpos($cxpMigrationCode, 'phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql') !== false
        && strpos($cxpMigrationCode, 'movimientos_caja') === false
    ) {
        hcOk('Migracion oficial Fase 3B de CxP base existe y documenta backup previo.');
    } else {
        hcWarning(
            'Migracion oficial Fase 3B de CxP base incompleta.',
            'Validar tablas, backup documentado, ausencia de Caja y registro en migrations.'
        );
    }
} else {
    hcWarning(
        'Migracion oficial Fase 3B de CxP base no visible desde este contexto.',
        'Ejecutar el checker desde la raiz del proyecto o montar migrations/ para validar el archivo oficial.'
    );
}

if ($purchaseServiceFile && is_file($purchaseServiceFile)) {
    $purchaseServiceCode = (string) file_get_contents($purchaseServiceFile);
    if (
        strpos($purchaseServiceCode, 'class CompraService') !== false
        && strpos($purchaseServiceCode, 'function crearBorrador') !== false
        && strpos($purchaseServiceCode, 'function obtenerCompra') !== false
        && strpos($purchaseServiceCode, 'function recibirCompra') !== false
        && strpos($purchaseServiceCode, 'function listarCompras') !== false
        && strpos($purchaseServiceCode, 'function catalogosBorrador') !== false
        && strpos($purchaseServiceCode, 'function catalogosReporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function reporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function filtrosReporteRecibidas') !== false
        && strpos($purchaseServiceCode, 'function assertCompraPuedeRecibirse') !== false
        && strpos($purchaseServiceCode, 'function assertDetallesPuedenRecibirse') !== false
        && strpos($purchaseServiceCode, 'ya fue recibida') !== false
        && strpos($purchaseServiceCode, 'esta cancelada') !== false
        && strpos($purchaseServiceCode, 'otra sesion') !== false
        && strpos($purchaseServiceCode, 'COUNT(DISTINCT c.id)') !== false
        && strpos($purchaseServiceCode, 'GROUP BY p.id, p.nombre') !== false
        && strpos($purchaseServiceCode, 'GROUP BY ip.id, ip.codigo, ip.nombre, ip.unidad_medida') !== false
        && strpos($purchaseServiceCode, 'LEFT JOIN movimientos_inventario') !== false
        && strpos($purchaseServiceCode, 'movimiento_stock_posterior') !== false
        && strpos($purchaseServiceCode, 'beginTransaction') !== false
        && strpos($purchaseServiceCode, 'movimientos_inventario') !== false
        && strpos($purchaseServiceCode, "'ENTRADA'") !== false
        && strpos($purchaseServiceCode, 'AuditService::record') !== false
        && strpos($purchaseServiceCode, 'movimientos_caja') === false
        && strpos($purchaseServiceCode, 'cuentas_por_pagar') === false
    ) {
        hcOk('CompraService existe con contrato transaccional, reportes y guardas Fase 2Z.');
    } else {
        hcWarning(
            'CompraService Fase 2Z existe pero no declara todas las guardas esperadas.',
            'Revisar crearBorrador, recibirCompra, reporteRecibidas, assertCompraPuedeRecibirse, assertDetallesPuedenRecibirse y ausencia de Caja/CxP.'
        );
    }
} else {
    hcWarning(
        'CompraService Fase 2P no existe.',
        'Crear capa interna antes de exponer rutas o UI de Compras.'
    );
}

if ($purchaseTestTool && is_file($purchaseTestTool)) {
    $purchaseTestCode = (string) file_get_contents($purchaseTestTool);
    if (
        strpos($purchaseTestCode, 'ROLLBACK_TEST') !== false
        && strpos($purchaseTestCode, '--backup-file') !== false
        && strpos($purchaseTestCode, '--duplicate-line') !== false
        && strpos($purchaseTestCode, 'duplicate_product_rule') !== false
        && strpos($purchaseTestCode, 'runRollbackExercise') !== false
        && strpos($purchaseTestCode, "['manage_transaction' => false]") !== false
        && strpos($purchaseTestCode, 'rollBack') !== false
        && strpos($purchaseTestCode, 'commit()') === false
    ) {
        hcOk('Herramienta Fase 2Q/2U de prueba CompraService existe y fuerza rollback.');
    } else {
        hcWarning(
            'Herramienta Fase 2Q/2U de prueba CompraService existe pero no declara todas las guardas esperadas.',
            'Revisar backup obligatorio, confirmacion ROLLBACK_TEST, duplicate-line, transaccion externa y ausencia de commit.'
        );
    }
} else {
    hcWarning(
        'Herramienta Fase 2Q de prueba CompraService no existe.',
        'Crear script CLI de prueba con rollback antes de exponer UI o rutas.'
    );
}

if ($minimalPurchasingDraft && is_file($minimalPurchasingDraft)) {
    $minimalPurchasingDraftCode = (string) file_get_contents($minimalPurchasingDraft);
    if (
        strpos($minimalPurchasingDraftCode, 'BORRADOR NO EJECUTADO') !== false
        && strpos($minimalPurchasingDraftCode, 'CREATE TABLE IF NOT EXISTS compras') !== false
        && strpos($minimalPurchasingDraftCode, 'CREATE TABLE IF NOT EXISTS compra_detalles') !== false
        && strpos($minimalPurchasingDraftCode, 'fk_compras_proveedor') !== false
        && strpos($minimalPurchasingDraftCode, 'fk_compra_detalles_producto') !== false
        && strpos($minimalPurchasingDraftCode, 'No toca movimientos_caja') !== false
    ) {
        hcOk('Borrador Fase 2N de compras minimas existe con tablas y guardas principales.');
    } else {
        hcWarning(
            'Borrador Fase 2N de compras minimas existe pero no declara todas las guardas esperadas.',
            'Revisar compras, compra_detalles, FKs y exclusiones de Caja/CxP antes de promover.'
        );
    }
} else {
    hcWarning(
        'No existe borrador Fase 2N de compras minimas.',
        'Crear docs/technical/sql_drafts/20260615_002_fase_2n_compras_minimas_draft.sql antes de promover migracion.'
    );
}

$envPath = hcFindFirstExistingPath([
    getenv('ENV_FILE') ?: '',
    $projectRoot . '/.env',
    $appRoot . '/.env',
    getcwd() . '/.env',
    dirname(getcwd()) . '/.env',
]);

$envValues = [];
if ($envPath) {
    $envValues = hcParseEnvFile($envPath);
    hcOk('Archivo .env detectado: ' . $envPath);
} else {
    hcWarning('No se encontro archivo .env accesible desde este contexto.', 'Ejecutar el checker desde el host o montar el proyecto completo si se quiere validar .env por archivo.');
}

$dbConfig = require $configPath;
$database = (string) ($dbConfig['database'] ?? '');
if ($database === '') {
    hcError('La configuracion de DB no define database.', 'Revisar DB_NAME en .env/configuracion.');
} else {
    hcOk('Base configurada por la app: ' . $database);
}

if (isset($envValues['DB_NAME']) && $database !== '' && $envValues['DB_NAME'] !== $database) {
    hcWarning(
        'DB_NAME en .env (' . $envValues['DB_NAME'] . ') difiere de config/database.php (' . $database . ').',
        'Unificar la base activa antes de ejecutar nuevas fases.'
    );
} elseif (isset($envValues['DB_NAME'])) {
    hcOk('DB_NAME de .env coincide con la configuracion cargada.');
}

$pdo = null;
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $dbConfig['host'] ?? 'db',
        $dbConfig['port'] ?? 3306,
        $database,
        $dbConfig['charset'] ?? 'utf8mb4'
    );
    $pdo = new PDO($dsn, $dbConfig['username'] ?? '', $dbConfig['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    hcOk('Conexion PDO de solo lectura inicializada.');
} catch (Throwable $e) {
    hcError('No se pudo conectar a la base configurada: ' . $e->getMessage(), 'Revisar contenedores y credenciales antes de continuar.');
}

if ($pdo) {
    if (hcTableExists($pdo, $database, 'migrations')) {
        hcOk('Tabla migrations existe.');
    } else {
        hcError('Tabla migrations no existe.', 'No aplicar nuevas fases hasta crear/controlar la tabla migrations.');
    }

    $migrationsDir = hcFindFirstExistingPath([
        getenv('MIGRATIONS_DIR') ?: '',
        $projectRoot . '/migrations',
        dirname($projectRoot) . '/migrations',
        getcwd() . '/migrations',
        dirname(getcwd()) . '/migrations',
    ]);

    if ($migrationsDir && is_dir($migrationsDir)) {
        $migrationFiles = array_map('basename', glob($migrationsDir . '/*.sql') ?: []);
        sort($migrationFiles);
        hcOk('Directorio migrations detectado: ' . $migrationsDir . ' (' . count($migrationFiles) . ' archivos SQL).');

        if (hcTableExists($pdo, $database, 'migrations')) {
            $registered = $pdo->query('SELECT nombre FROM migrations ORDER BY nombre')->fetchAll(PDO::FETCH_COLUMN);
            $registered = array_map('strval', $registered);

            $missingRegistered = array_values(array_diff($migrationFiles, $registered));
            $registeredWithoutFile = array_values(array_diff($registered, $migrationFiles));

            if (empty($missingRegistered)) {
                hcOk('Todas las migraciones de migrations/ estan registradas.');
            } else {
                hcError(
                    'Migraciones de migrations/ no registradas: ' . implode(', ', $missingRegistered),
                    'No ejecutar codigo que dependa de esas migraciones sin reconciliar el historial.'
                );
            }

            if (empty($registeredWithoutFile)) {
                hcOk('Todas las migraciones registradas tienen archivo en migrations/.');
            } else {
                hcWarning(
                    'Migraciones registradas sin archivo local: ' . implode(', ', $registeredWithoutFile),
                    'Verificar si fueron aplicadas manualmente o si falta versionar archivos.'
                );
            }
        }
    } else {
        hcWarning(
            'No se encontro directorio migrations/ accesible.',
            'Ejecutar con el proyecto raiz montado o definir MIGRATIONS_DIR para comparar archivos vs DB.'
        );
    }

    $extraMigrationDir = $appRoot . '/database/migrations';
    if (is_dir($extraMigrationDir)) {
        $extraFiles = array_map('basename', glob($extraMigrationDir . '/*.sql') ?: []);
        foreach ($extraFiles as $file) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE nombre = :name');
            $stmt->execute(['name' => $file]);
            if ((int) $stmt->fetchColumn() === 0) {
                $documentedLegacySyncMigration = false;
                if (
                    $file === '2026_05_21_create_operaciones_sync.sql'
                    && $technicalClosureDoc
                    && is_file($technicalClosureDoc)
                ) {
                    $closureDocCode = (string) file_get_contents($technicalClosureDoc);
                    $documentedLegacySyncMigration = strpos($closureDocCode, 'Existe migracion historica fuera de `migrations/`: `database/migrations/2026_05_21_create_operaciones_sync.sql`') !== false
                        && strpos($closureDocCode, '`/api/sync` sigue fuera de alcance y bloqueado') !== false;
                }

                if ($documentedLegacySyncMigration) {
                    hcOk('Migracion legacy sync documentada fuera del flujo oficial: database/migrations/' . $file . '.');
                } else {
                    hcWarning(
                        'Migracion fuera de migrations/ no registrada: database/migrations/' . $file,
                        'Decidir si esta migracion pertenece al flujo oficial antes de aplicarla.'
                    );
                }
            }
        }
    }

    $requiredSaasTables = [
        'hoteles',
        'hotel_configuracion',
        'hotel_usuarios',
        'hotel_modulos',
        'hotel_branding',
        'logs_auditoria',
        'saas_admins',
        'modulos',
        'planes',
        'plan_modulos',
        'reporte_links',
        'reporte_link_envios',
        'pwa_push_subscriptions',
    ];

    foreach ($requiredSaasTables as $table) {
        if (hcTableExists($pdo, $database, $table)) {
            hcOk('Tabla SaaS requerida existe: ' . $table);
        } else {
            hcError('Tabla SaaS requerida faltante: ' . $table, 'No avanzar con modulos SaaS hasta reconciliar migraciones.');
        }
    }

    if (hcTableExists($pdo, $database, 'hoteles')) {
        $activeHotels = $pdo->query(
            'SELECT h.id, h.slug, h.nombre, h.plan_id, p.clave AS plan_clave
             FROM hoteles h
             LEFT JOIN planes p ON p.id = h.plan_id
             WHERE h.activo = 1
             ORDER BY h.id'
        )->fetchAll();

        if (empty($activeHotels)) {
            hcError('No hay hoteles activos.', 'Activar o crear al menos un hotel antes de operar multihotel.');
        } else {
            hcOk('Hoteles activos detectados: ' . count($activeHotels));
        }

        if (hcTableExists($pdo, $database, 'modulos')) {
            $activeModuleCount = (int) $pdo->query(
                'SELECT COUNT(*) FROM modulos WHERE activo_global = 1'
            )->fetchColumn();
            hcOk('Modulos globales activos: ' . $activeModuleCount);
        }

        if (hcTableExists($pdo, $database, 'hotel_modulos') && hcTableExists($pdo, $database, 'modulos')) {
            $coreModuleKeys = ['dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'reportes'];
            $losCedrosExpected = [
                'dashboard',
                'habitaciones',
                'reservaciones',
                'huespedes',
                'caja',
                'facturacion',
                'inventario',
                'reportes',
                'configuracion',
                'usuarios',
                'tarifas_dinamicas',
                'limpieza',
                'mantenimiento',
                'auditoria',
            ];

            $moduleStmt = $pdo->prepare(
                'SELECT m.clave
                 FROM hotel_modulos hm
                 INNER JOIN modulos m ON m.id = hm.modulo_id
                 WHERE hm.hotel_id = :hotel_id
                   AND hm.activo = 1
                   AND m.activo_global = 1
                 ORDER BY m.orden, m.clave'
            );

            $planStmt = $pdo->prepare(
                'SELECT m.clave
                 FROM plan_modulos pm
                 INNER JOIN modulos m ON m.id = pm.modulo_id
                 WHERE pm.plan_id = :plan_id
                   AND pm.incluido = 1
                   AND m.activo_global = 1
                 ORDER BY m.orden, m.clave'
            );

            foreach ($activeHotels as $hotel) {
                $moduleStmt->execute(['hotel_id' => (int) $hotel['id']]);
                $activeKeys = array_values(array_map('strval', $moduleStmt->fetchAll(PDO::FETCH_COLUMN)));

                if (empty($activeKeys)) {
                    hcError(
                        'Hotel activo sin modulos activos: ' . $hotel['slug'],
                        'Asignar modulos base antes de liberar flujos SaaS/multihotel para este hotel.'
                    );
                    continue;
                }

                hcOk('Hotel ' . $hotel['slug'] . ' tiene modulos activos: ' . count($activeKeys));

                $missingCore = array_values(array_diff($coreModuleKeys, $activeKeys));
                if (!empty($missingCore)) {
                    hcWarning(
                        'Hotel ' . $hotel['slug'] . ' no tiene modulos core activos: ' . implode(', ', $missingCore),
                        'Revisar hotel_modulos antes de operar ese hotel.'
                    );
                }

                if (($hotel['slug'] ?? '') === 'los-cedros') {
                    $missingLosCedros = array_values(array_diff($losCedrosExpected, $activeKeys));
                    if (empty($missingLosCedros)) {
                        hcOk('Los Cedros tiene los modulos Fase 1B minimos activos.');
                    } else {
                        hcError(
                            'Los Cedros no tiene modulos Fase 1B minimos: ' . implode(', ', $missingLosCedros),
                            'Ejecutar la migracion 20260614_002_fase_1b_modulos_los_cedros_auditoria.sql tras backup.'
                        );
                    }
                }

                $planId = (int) ($hotel['plan_id'] ?? 0);
                $planClave = (string) ($hotel['plan_clave'] ?? '');
                if ($planId > 0 && $planClave !== '' && $planClave !== 'personalizado' && hcTableExists($pdo, $database, 'plan_modulos')) {
                    $planStmt->execute(['plan_id' => $planId]);
                    $planKeys = array_values(array_map('strval', $planStmt->fetchAll(PDO::FETCH_COLUMN)));
                    $missingFromPlan = array_values(array_diff($planKeys, $activeKeys));
                    $outsidePlan = array_values(array_diff($activeKeys, $planKeys));

                    if (empty($missingFromPlan) && empty($outsidePlan)) {
                        hcOk('Hotel ' . $hotel['slug'] . ' coincide con su plan ' . $planClave . '.');
                    } else {
                        $outsidePlanSorted = $outsidePlan;
                        sort($outsidePlanSorted);
                        $documentedPlanException = false;

                        if (
                            (string) ($hotel['slug'] ?? '') === 'maximiliano'
                            && empty($missingFromPlan)
                            && $outsidePlanSorted === ['configuracion', 'usuarios']
                            && $technicalClosureDoc
                            && is_file($technicalClosureDoc)
                        ) {
                            $closureDocCode = (string) file_get_contents($technicalClosureDoc);
                            $documentedPlanException = strpos($closureDocCode, 'Maximiliano tiene excepciones de modulos respecto a su plan') !== false
                                && strpos($closureDocCode, '`configuracion`, `usuarios`') !== false;
                        }

                        if ($documentedPlanException) {
                            hcOk(
                                'Hotel ' . $hotel['slug'] . ' mantiene excepcion documentada sobre plan ' . $planClave . ': fuera de plan ' . implode(', ', $outsidePlan) . '.'
                            );
                        } else {
                            hcWarning(
                                'Hotel ' . $hotel['slug'] . ' difiere de su plan ' . $planClave . '. Faltan: ' . (empty($missingFromPlan) ? 'ninguno' : implode(', ', $missingFromPlan)) . '. Fuera de plan: ' . (empty($outsidePlan) ? 'ninguno' : implode(', ', $outsidePlan)) . '.',
                                'Mantener como advertencia si el hotel usa excepciones manuales; no corregir automaticamente.'
                            );
                        }
                    }
                }
            }
        }
    }

    if (hcTableExists($pdo, $database, 'logs_auditoria')) {
        $auditCount = hcCountRows($pdo, 'logs_auditoria');
        if ($auditCount === 0) {
            hcWarning(
                'logs_auditoria existe pero aun no tiene registros.',
                'Probar login/logout despues de desplegar AuditService para confirmar escritura gradual.'
            );
        } else {
            hcOk('logs_auditoria tiene registros: ' . $auditCount);

            $lastAudit = $pdo->query(
                'SELECT accion, created_at FROM logs_auditoria ORDER BY created_at DESC, id DESC LIMIT 1'
            )->fetch();
            if ($lastAudit) {
                hcOk('Ultima auditoria: ' . $lastAudit['accion'] . ' en ' . $lastAudit['created_at']);
            }
        }
    }

    $hotelIdTables = [
        'habitaciones',
        'tipos_habitacion',
        'habitacion_imagenes',
        'mantenimientos_habitaciones',
        'inventario_categorias',
        'inventario_productos',
        'inventario_config_habitacion',
        'movimientos_inventario',
        'reservaciones',
        'reservacion_habitaciones',
        'reservacion_pagos',
        'reservacion_abonos',
        'reservacion_notas',
        'solicitudes_factura',
        'cajas',
        'cortes_caja',
        'movimientos_caja',
        'notificaciones',
        'pwa_push_subscriptions',
        'reporte_links',
        'reporte_link_envios',
        'proveedores',
    ];

    foreach ($hotelIdTables as $table) {
        if (!hcTableExists($pdo, $database, $table)) {
            hcWarning('Tabla esperada para hotel_id no existe: ' . $table, 'Verificar si la migracion correspondiente debe permanecer pendiente.');
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            hcError('Tabla sin hotel_id esperado: ' . $table, 'No activar multihotel completo para esta tabla hasta migrarla.');
            continue;
        }

        $nullRows = hcNullHotelRows($pdo, $table);
        if ($nullRows === 0) {
            hcOk('Tabla con hotel_id completo: ' . $table);
        } else {
            hcError('Tabla ' . $table . ' tiene hotel_id NULL: ' . (string) $nullRows, 'Corregir backfill antes de usar aislamiento multihotel.');
        }
    }

    if (hcTableExists($pdo, $database, 'proveedores')) {
        $providerCount = hcCountRows($pdo, 'proveedores');
        hcOk('Catalogo proveedores Fase 2F existe. Registros: ' . (string) $providerCount);

        if (hcColumnExists($pdo, $database, 'proveedores', 'hotel_id')) {
            $providerNullHotels = hcNullHotelRows($pdo, 'proveedores');
            if ($providerNullHotels === 0) {
                hcOk('proveedores tiene hotel_id completo.');
            } else {
                hcError('proveedores tiene hotel_id NULL: ' . (string) $providerNullHotels, 'Corregir aislamiento antes de usar catalogo multihotel.');
            }
        } else {
            hcError('proveedores no tiene hotel_id.', 'No usar catalogo de proveedores sin alcance por hotel.');
        }

        if (hcTableExists($pdo, $database, 'migrations')) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE nombre = :name');
            $stmt->execute(['name' => '20260614_004_fase_2f_catalogo_proveedores.sql']);
            if ((int) $stmt->fetchColumn() > 0) {
                hcOk('Migracion Fase 2F de proveedores registrada.');
            } else {
                hcError(
                    'proveedores existe pero la migracion Fase 2F no esta registrada.',
                    'Registrar/reconciliar 20260614_004_fase_2f_catalogo_proveedores.sql antes de depender del catalogo.'
                );
            }

            $stmt->execute(['name' => '20260615_001_fase_2j_unique_proveedores.sql']);
            if ((int) $stmt->fetchColumn() > 0) {
                hcOk('Migracion Fase 2J de constraints proveedores registrada.');
            } else {
                hcError(
                    'Migracion Fase 2J de constraints proveedores no esta registrada.',
                    'Aplicar 20260615_001_fase_2j_unique_proveedores.sql tras backup o retirar dependencias de indices unicos.'
                );
            }
        }

        $stmt = $pdo->prepare(
            'SELECT COLUMN_NAME, EXTRA, GENERATION_EXPRESSION
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :db
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column'
        );
        $stmt->execute(['db' => $database, 'table' => 'proveedores', 'column' => 'nombre_activo_key']);
        $generatedColumn = $stmt->fetch();
        if (
            $generatedColumn
            && strpos((string) ($generatedColumn['EXTRA'] ?? ''), 'GENERATED') !== false
            && strpos((string) ($generatedColumn['GENERATION_EXPRESSION'] ?? ''), 'activo') !== false
        ) {
            hcOk('proveedores.nombre_activo_key existe como columna generada para nombres activos.');
        } else {
            hcError(
                'No existe columna generada proveedores.nombre_activo_key.',
                'Agregar la columna generada antes de depender del indice unico de nombre activo.'
            );
        }

        $expectedProviderUniqueIndexes = [
            'uk_proveedores_hotel_rfc' => ['hotel_id', 'rfc'],
            'uk_proveedores_hotel_nombre_activo' => ['hotel_id', 'nombre_activo_key'],
        ];
        $stmt = $pdo->prepare(
            'SELECT INDEX_NAME, NON_UNIQUE, COLUMN_NAME, SEQ_IN_INDEX
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = :db
               AND TABLE_NAME = :table
               AND INDEX_NAME IN ("uk_proveedores_hotel_rfc", "uk_proveedores_hotel_nombre_activo")
             ORDER BY INDEX_NAME, SEQ_IN_INDEX'
        );
        $stmt->execute(['db' => $database, 'table' => 'proveedores']);
        $providerIndexes = [];
        foreach ($stmt->fetchAll() as $row) {
            $providerIndexes[$row['INDEX_NAME']]['non_unique'] = (int) $row['NON_UNIQUE'];
            $providerIndexes[$row['INDEX_NAME']]['columns'][] = $row['COLUMN_NAME'];
        }

        foreach ($expectedProviderUniqueIndexes as $indexName => $columns) {
            if (
                isset($providerIndexes[$indexName])
                && ($providerIndexes[$indexName]['non_unique'] ?? 1) === 0
                && ($providerIndexes[$indexName]['columns'] ?? []) === $columns
            ) {
                hcOk('Indice unico proveedores OK: ' . $indexName . ' (' . implode(', ', $columns) . ').');
            } else {
                hcError(
                    'Indice unico proveedores faltante o incorrecto: ' . $indexName,
                    'Revisar migracion Fase 2J antes de confiar en constraints SQL de proveedores.'
                );
            }
        }

        if (hcTableExists($pdo, $database, 'hotel_modulos') && hcTableExists($pdo, $database, 'modulos')) {
            $stmt = $pdo->query(
                "SELECT COUNT(*)
                 FROM proveedores p
                 WHERE NOT EXISTS (
                     SELECT 1
                     FROM hotel_modulos hm
                     INNER JOIN modulos m ON m.id = hm.modulo_id
                     WHERE hm.hotel_id = p.hotel_id
                       AND hm.activo = 1
                       AND m.activo_global = 1
                       AND m.clave = 'inventario'
                 )"
            );
            $providersWithoutInventory = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($providersWithoutInventory === 0) {
                hcOk('No hay proveedores en hoteles sin modulo inventario activo.');
            } else {
                hcWarning(
                    'Hay proveedores en hoteles sin modulo inventario activo: ' . $providersWithoutInventory,
                    'Revisar hotel_modulos antes de ocultar o bloquear el catalogo para esos hoteles.'
                );
            }
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT hotel_id, rfc
                 FROM proveedores
                 WHERE rfc IS NOT NULL AND rfc <> ''
                 GROUP BY hotel_id, rfc
                 HAVING COUNT(*) > 1
             ) duplicados_rfc"
        );
        $duplicateRfcGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateRfcGroups === 0) {
            hcOk('No hay RFC duplicados por hotel en proveedores.');
        } else {
            hcWarning(
                'Hay grupos de RFC duplicado por hotel en proveedores: ' . $duplicateRfcGroups,
                'Revisar catalogo manualmente antes de usar proveedores como base de compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT hotel_id, nombre
                 FROM proveedores
                 WHERE activo = 1
                 GROUP BY hotel_id, nombre
                 HAVING COUNT(*) > 1
             ) duplicados_nombre"
        );
        $duplicateNameGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateNameGroups === 0) {
            hcOk('No hay nombres activos duplicados por hotel en proveedores.');
        } else {
            hcWarning(
                'Hay grupos de nombre activo duplicado por hotel en proveedores: ' . $duplicateNameGroups,
                'Fusionar o desactivar duplicados manualmente antes de construir compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND notas LIKE '%Importado desde movimientos_caja.proveedor en Fase 2L%'"
        );
        $phase2LProviders = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($phase2LProviders === 29) {
            hcOk('Importacion Fase 2L de proveedores Los Cedros aplicada: 29 proveedores.');
        } elseif ($phase2LProviders === 0) {
            hcWarning(
                'Importacion Fase 2L de proveedores Los Cedros aun no aplicada.',
                'Aplicar importar_proveedores_los_cedros.php solo despues de backup si el catalogo historico ya fue aprobado.'
            );
        } else {
            hcWarning(
                'Importacion Fase 2L de proveedores Los Cedros tiene conteo inesperado: ' . $phase2LProviders,
                'Revisar lista de revision y proveedores con notas Fase 2L antes de continuar.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND nombre IN ('huÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©sped', 'Huesped', 'no se', 'sin definir')"
        );
        $phase2LExcludedInserted = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($phase2LExcludedInserted === 0) {
            hcOk('Candidatos excluidos Fase 2L no fueron insertados como proveedores.');
        } else {
            hcWarning(
                'Candidatos excluidos Fase 2L aparecen insertados: ' . $phase2LExcludedInserted,
                'Revisar manualmente huÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©sped/no se/sin definir antes de construir compras.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM proveedores
             WHERE hotel_id = 1
               AND activo = 1"
        );
        $losCedrosActiveProviders = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($losCedrosActiveProviders >= 29) {
            hcOk('Los Cedros tiene proveedores activos disponibles para fases futuras: ' . $losCedrosActiveProviders . '.');
        } else {
            hcWarning(
                'Los Cedros tiene pocos proveedores activos: ' . $losCedrosActiveProviders,
                'Validar catalogo antes de habilitar Compras o recepciones.'
            );
        }
    } else {
        hcWarning(
            'Catalogo proveedores Fase 2F aun no existe.',
            'Ejecutar 20260614_004_fase_2f_catalogo_proveedores.sql tras backup antes de usar /proveedores.'
        );
    }

    $minimalPurchasingTables = [
        'compras' => [
            'id', 'hotel_id', 'proveedor_id', 'folio', 'fecha_compra',
            'fecha_recepcion', 'estado', 'subtotal', 'impuestos', 'total',
            'notas', 'recibida_por', 'cancelada_por', 'created_by',
            'updated_by', 'created_at', 'updated_at',
        ],
        'compra_detalles' => [
            'id', 'compra_id', 'hotel_id', 'producto_id', 'cantidad',
            'costo_unitario', 'subtotal', 'movimiento_inventario_id',
            'created_at', 'updated_at',
        ],
    ];
    foreach ($minimalPurchasingTables as $table => $columns) {
        if (!hcTableExists($pdo, $database, $table)) {
            hcError(
                'Tabla minima Fase 2O faltante: ' . $table,
                'Aplicar 20260615_002_fase_2n_compras_minimas.sql solo despues de backup confirmado.'
            );
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            hcError(
                'Tabla minima Fase 2O incompleta: ' . $table . '. Faltan: ' . implode(', ', $missingColumns),
                'Restaurar backup o reconciliar estructura antes de construir flujo de Compras.'
            );
        } else {
            hcOk('Tabla minima Fase 2O disponible: ' . $table);
        }

        $stmt = $pdo->query('SELECT COUNT(*) FROM ' . hcQuoteIdentifier($table) . ' WHERE hotel_id IS NULL');
        $nullHotelRows = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($nullHotelRows === 0) {
            hcOk($table . ' tiene hotel_id completo.');
        } else {
            hcError($table . ' tiene hotel_id NULL: ' . $nullHotelRows, 'Corregir aislamiento por hotel antes de exponer Compras.');
        }

        $rows = hcCountRows($pdo, $table);
        if ($rows === 0) {
            hcOk($table . ' esta vacia; no hay escrituras operativas de Compras.');
        } elseif ($table === 'compras') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM compras WHERE estado <> 'borrador'");
            $nonDraftRows = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($nonDraftRows === 0) {
                hcOk('compras contiene solo borradores Fase 2R: ' . $rows . '.');
            } else {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM compras
                     WHERE estado <> 'borrador'
                       AND NOT (estado = 'recibida' AND fecha_recepcion IS NOT NULL)"
                );
                $unexpectedNonDraftRows = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($unexpectedNonDraftRows === 0) {
                    hcOk('compras no borrador consistentes con recepcion Fase 2W: ' . $nonDraftRows . '.');
                } else {
                    hcWarning(
                        'compras no borrador inconsistentes: ' . $unexpectedNonDraftRows . ' de ' . $nonDraftRows,
                        'Auditar origen antes de habilitar pagos, CxP o cancelaciones.'
                    );
                }
            }
        } elseif ($table === 'compra_detalles') {
            $stmt = $pdo->query('SELECT COUNT(*) FROM compra_detalles WHERE movimiento_inventario_id IS NOT NULL');
            $linkedMovements = $stmt ? (int) $stmt->fetchColumn() : 0;
            if ($linkedMovements === 0) {
                hcOk('compra_detalles no tiene movimientos de inventario vinculados.');
            } else {
                $stmt = $pdo->query(
                    "SELECT COUNT(*)
                     FROM compra_detalles d
                     INNER JOIN compras c
                        ON c.id = d.compra_id
                       AND c.hotel_id = d.hotel_id
                     LEFT JOIN movimientos_inventario mi
                        ON mi.id = d.movimiento_inventario_id
                     WHERE d.movimiento_inventario_id IS NOT NULL
                       AND (c.estado <> 'recibida' OR mi.id IS NULL)"
                );
                $inconsistentLinkedMovements = $stmt ? (int) $stmt->fetchColumn() : 0;
                if ($inconsistentLinkedMovements === 0) {
                    hcOk('compra_detalles tiene movimientos vinculados consistentes con Fase 2W: ' . $linkedMovements . '.');
                } else {
                    hcWarning(
                        'compra_detalles tiene movimientos vinculados inconsistentes: ' . $inconsistentLinkedMovements . ' de ' . $linkedMovements,
                        'Auditar detalles antes de habilitar pagos, CxP o devoluciones.'
                    );
                }
            }
        }
    }

    if (hcTableExists($pdo, $database, 'compras') && hcTableExists($pdo, $database, 'compra_detalles')) {
        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM (
                 SELECT d.compra_id, d.producto_id
                 FROM compra_detalles d
                 INNER JOIN compras c ON c.id = d.compra_id
                 WHERE c.estado = 'borrador'
                 GROUP BY d.compra_id, d.producto_id
                 HAVING COUNT(*) > 1
             ) productos_repetidos"
        );
        $duplicateProductGroups = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($duplicateProductGroups === 0) {
            hcOk('Fase 2U: borradores de compras sin productos repetidos.');
        } else {
            hcOk(
                'Fase 2U: productos repetidos permitidos como un movimiento por linea. Grupos detectados: ' . $duplicateProductGroups . '.'
            );
        }

        $stmt = $pdo->query(
            "SELECT COUNT(*)
             FROM compra_detalles d
             INNER JOIN compras c ON c.id = d.compra_id
             LEFT JOIN proveedores p
                    ON p.id = c.proveedor_id
                   AND p.hotel_id = c.hotel_id
                   AND p.activo = 1
             LEFT JOIN inventario_productos ip
                    ON ip.id = d.producto_id
                   AND ip.hotel_id = d.hotel_id
                   AND ip.activo = 1
             WHERE c.estado = 'borrador'
               AND (p.id IS NULL OR ip.id IS NULL OR d.hotel_id <> c.hotel_id)"
        );
        $invalidDraftLines = $stmt ? (int) $stmt->fetchColumn() : 0;
        if ($invalidDraftLines === 0) {
            hcOk('Fase 2U: borradores tienen proveedor/productos activos y scoped por hotel.');
        } else {
            hcError(
                'Fase 2U: borradores con proveedor/producto invalido o hotel_id cruzado: ' . $invalidDraftLines,
                'Corregir borradores antes de habilitar recepcion.'
            );
        }
    }

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM migrations
         WHERE nombre = '20260615_002_fase_2n_compras_minimas.sql'
           AND estado = 'ejecutada'"
    );
    $stmt->execute();
    if ((int) $stmt->fetchColumn() === 1) {
        hcOk('Migracion Fase 2O de compras minimas registrada como ejecutada.');
    } else {
        hcError(
            'Migracion Fase 2O de compras minimas no esta registrada como ejecutada.',
            'Registrar la migracion solo si las tablas fueron creadas correctamente.'
        );
    }

    $futurePurchasingTables = [
        'proveedor_contactos',
        'compra_pagos',
        'documentos_proveedor',
    ];
    foreach ($futurePurchasingTables as $table) {
        if (hcTableExists($pdo, $database, $table)) {
            hcWarning(
                'Tabla futura de compras/CxP detectada fuera del alcance Fase 2O: ' . $table,
                'Validar origen antes de usarla; Fase 2O solo habilita compras minimas sin pagos, CxP ni documentos.'
            );
        } else {
            hcOk('Tabla futura no creada en Fase 2O: ' . $table);
        }
    }

    if (hcTableExists($pdo, $database, 'cuentas_por_pagar') && hcTableExists($pdo, $database, 'cuentas_por_pagar_movimientos')) {
        $cxpRequiredColumns = [
            'id', 'hotel_id', 'proveedor_id', 'compra_id', 'folio',
            'descripcion', 'fecha_emision', 'fecha_vencimiento', 'estado',
            'moneda', 'subtotal', 'impuestos', 'total', 'saldo', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ];
        $cxpMissingColumns = [];
        foreach ($cxpRequiredColumns as $column) {
            if (!hcColumnExists($pdo, $database, 'cuentas_por_pagar', $column)) {
                $cxpMissingColumns[] = $column;
            }
        }

        $cxpMovRequiredColumns = [
            'id', 'cuenta_por_pagar_id', 'hotel_id', 'tipo_movimiento',
            'monto', 'saldo_anterior', 'saldo_posterior', 'referencia',
            'notas', 'usuario_id', 'created_at',
        ];
        $cxpMovMissingColumns = [];
        foreach ($cxpMovRequiredColumns as $column) {
            if (!hcColumnExists($pdo, $database, 'cuentas_por_pagar_movimientos', $column)) {
                $cxpMovMissingColumns[] = $column;
            }
        }

        if (empty($cxpMissingColumns) && empty($cxpMovMissingColumns)) {
            hcOk('Tablas Fase 3B de CxP base existen con columnas requeridas.');
        } else {
            hcError(
                'Tablas Fase 3B de CxP incompletas. Faltan CxP: ' . implode(', ', $cxpMissingColumns) . '. Movimientos: ' . implode(', ', $cxpMovMissingColumns),
                'No exponer CxP hasta reconciliar la migracion 20260615_003_fase_3b_cxp_base.sql.'
            );
        }

        $cxpCount = hcCountRows($pdo, 'cuentas_por_pagar');
        $cxpMovCount = hcCountRows($pdo, 'cuentas_por_pagar_movimientos');
        hcOk('CxP Fase 3B/3C-C disponible. Registros: cuentas=' . (string)$cxpCount . ', movimientos=' . (string)$cxpMovCount . '.');
        hcReportCxpConsistency($pdo, $database);

        if (hcTableExists($pdo, $database, 'migrations')) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM migrations
                 WHERE nombre = '20260615_003_fase_3b_cxp_base.sql'
                   AND estado = 'ejecutada'"
            );
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 1) {
                hcOk('Migracion Fase 3B de CxP base registrada como ejecutada.');
            } else {
                hcError(
                    'Migracion Fase 3B de CxP base no esta registrada como ejecutada.',
                    'Registrar solo si las tablas fueron creadas correctamente tras backup.'
                );
            }
        }
    } else {
        hcWarning(
            'Tablas Fase 3B de CxP base aun no existen.',
            'Aplicar la migracion 20260615_003_fase_3b_cxp_base.sql solo despues de backup.'
        );
    }

    $npTables = [
        'trabajadores' => [
            'id', 'hotel_id', 'usuario_id', 'nombre_completo', 'identificacion',
            'rol_laboral', 'telefono', 'email', 'estado', 'fecha_alta',
            'fecha_baja', 'salario_base', 'periodicidad_pago', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_pagos' => [
            'id', 'hotel_id', 'trabajador_id', 'tipo', 'efecto', 'monto',
            'concepto', 'periodo_inicio', 'periodo_fin', 'fecha', 'referencia',
            'notas', 'estado', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_anticipos' => [
            'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
            'fecha', 'motivo', 'estado', 'referencia', 'notas',
            'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_prestamos' => [
            'id', 'hotel_id', 'trabajador_id', 'monto', 'saldo_pendiente',
            'fecha', 'plazo_meses', 'abono_periodico', 'motivo', 'estado',
            'referencia', 'notas', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_asistencias' => [
            'id', 'hotel_id', 'trabajador_id', 'fecha', 'tipo',
            'hora_entrada', 'hora_salida', 'horas', 'horas_extra',
            'observaciones', 'created_by', 'updated_by', 'created_at', 'updated_at',
        ],
        'trabajador_documentos' => [
            'id', 'hotel_id', 'trabajador_id', 'tipo', 'nombre_original',
            'ruta_archivo', 'mime', 'tamano', 'notas', 'estado',
            'subido_por', 'created_at', 'updated_at',
        ],
    ];

    $npMissingTables = [];
    $npMissingColumns = [];
    $npRows = 0;
    foreach ($npTables as $table => $columns) {
        if (!hcTableExists($pdo, $database, $table)) {
            $npMissingTables[] = $table;
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            $npMissingColumns[] = $table . '.hotel_id';
        }

        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $npMissingColumns[] = $table . '.' . $column;
            }
        }

        $npRows += (int)(hcCountRows($pdo, $table) ?? 0);
    }

    if (empty($npMissingTables) && empty($npMissingColumns)) {
        hcOk('Personal base NP-A tiene las seis tablas aditivas con hotel_id y columnas requeridas. Registros totales=' . (string)$npRows . '.');
    } else {
        hcWarning(
            'Personal base NP-A incompleto. Tablas faltantes: ' . implode(', ', $npMissingTables) . '. Columnas faltantes: ' . implode(', ', $npMissingColumns) . '.',
            'No exponer Personal hasta aplicar o reconciliar migrations/20260616_001_fase_np_a_personal_base.sql.'
        );
    }

    if (hcTableExists($pdo, $database, 'migrations')) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM migrations
             WHERE nombre = '20260616_001_fase_np_a_personal_base.sql'
               AND estado = 'ejecutada'"
        );
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 1) {
            hcOk('Migracion Personal base NP-A registrada como ejecutada.');
        } elseif (empty($npMissingTables)) {
            hcWarning(
                'Tablas Personal base NP-A existen pero la migracion no esta registrada.',
                'Registrar solo si la migracion fue aplicada tras backup verificado.'
            );
        }
    }

    if (hcTableExists($pdo, $database, 'trabajador_pagos_caja')) {
        $workerCashPaymentColumns = [
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
        $workerCashPaymentMissingColumns = [];
        foreach ($workerCashPaymentColumns as $column) {
            if (!hcColumnExists($pdo, $database, 'trabajador_pagos_caja', $column)) {
                $workerCashPaymentMissingColumns[] = $column;
            }
        }

        $workerCashPaymentIndexes = [
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
        $workerCashPaymentMissingIndexes = [];
        foreach ($workerCashPaymentIndexes as $index) {
            if (!hcIndexExists($pdo, $database, 'trabajador_pagos_caja', $index)) {
                $workerCashPaymentMissingIndexes[] = $index;
            }
        }

        $workerCashPaymentConstraints = [
            'fk_trabajador_pagos_caja_hotel',
            'fk_trabajador_pagos_caja_trabajador',
            'fk_trabajador_pagos_caja_movimiento',
            'fk_trabajador_pagos_caja_corte',
            'fk_trabajador_pagos_caja_created_by',
            'fk_trabajador_pagos_caja_updated_by',
            'chk_trabajador_pagos_caja_monto',
            'chk_trabajador_pagos_caja_periodo',
        ];
        $workerCashPaymentMissingConstraints = [];
        foreach ($workerCashPaymentConstraints as $constraint) {
            if (!hcConstraintExists($pdo, $database, 'trabajador_pagos_caja', $constraint)) {
                $workerCashPaymentMissingConstraints[] = $constraint;
            }
        }

        if (
            empty($workerCashPaymentMissingColumns)
            && empty($workerCashPaymentMissingIndexes)
            && empty($workerCashPaymentMissingConstraints)
        ) {
            hcOk('Tabla 5E-B-A trabajador_pagos_caja cumple columnas, indices y constraints requeridos.');
        } else {
            hcError(
                'Tabla 5E-B-A trabajador_pagos_caja incompleta. Columnas faltantes: ' . implode(', ', $workerCashPaymentMissingColumns) . '. Indices faltantes: ' . implode(', ', $workerCashPaymentMissingIndexes) . '. Constraints faltantes: ' . implode(', ', $workerCashPaymentMissingConstraints) . '.',
                'No avanzar a servicio de pago laboral hasta reconciliar la migracion 5E-B-A.'
            );
        }

        $workerCashPaymentRows = hcCountRows($pdo, 'trabajador_pagos_caja');
        hcOk('Tabla 5E-B-A trabajador_pagos_caja disponible; registros actuales: ' . (string)$workerCashPaymentRows . '.');

        $hasPayrollPeriodLink = hcColumnExists($pdo, $database, 'trabajador_pagos_caja', 'nomina_periodo_id');
        $hasPayrollDetailLink = hcColumnExists($pdo, $database, 'trabajador_pagos_caja', 'nomina_periodo_detalle_id');
        if (!$hasPayrollPeriodLink && !$hasPayrollDetailLink) {
            hcOk('5E-P-0: trazabilidad fuerte de snapshot aun no aplicada en trabajador_pagos_caja.');
        } elseif ($hasPayrollPeriodLink !== $hasPayrollDetailLink) {
            hcError(
                '5E-P-0: trazabilidad snapshot incompleta en trabajador_pagos_caja.',
                'Agregar nomina_periodo_id y nomina_periodo_detalle_id juntas, nullable y solo con migracion autorizada.'
            );
        } else {
            $snapshotPaymentIndexes = [];
            foreach ([
                'idx_trabajador_pagos_caja_nomina_periodo',
                'idx_trabajador_pagos_caja_nomina_detalle',
            ] as $index) {
                if (!hcIndexExists($pdo, $database, 'trabajador_pagos_caja', $index)) {
                    $snapshotPaymentIndexes[] = $index;
                }
            }

            if ($snapshotPaymentIndexes === []) {
                hcOk('5E-P-A: trazabilidad snapshot tiene columnas e indices esperados.');
            } else {
                hcError(
                    '5E-P-0: faltan indices de trazabilidad snapshot: ' . implode(', ', $snapshotPaymentIndexes) . '.',
                    'Crear indices por periodo y detalle en la migracion autorizada.'
                );
            }

            $snapshotPaymentConstraints = [];
            foreach ([
                'fk_trabajador_pagos_caja_nomina_periodo',
                'fk_trabajador_pagos_caja_nomina_detalle',
            ] as $constraint) {
                if (!hcConstraintExists($pdo, $database, 'trabajador_pagos_caja', $constraint)) {
                    $snapshotPaymentConstraints[] = $constraint;
                }
            }

            if ($snapshotPaymentConstraints === []) {
                hcOk('5E-P-A: trazabilidad snapshot tiene FKs restrictivas esperadas.');
            } else {
                hcError(
                    '5E-P-A: faltan FKs restrictivas de trazabilidad snapshot: ' . implode(', ', $snapshotPaymentConstraints) . '.',
                    'Aplicar o reconciliar migrations/20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql.'
                );
            }

            hcReportZeroCount(
                'pagos laborales snapshot con relacion parcial',
                hcCountScalar($pdo, 'SELECT COUNT(*) FROM trabajador_pagos_caja
                    WHERE (nomina_periodo_id IS NULL AND nomina_periodo_detalle_id IS NOT NULL)
                       OR (nomina_periodo_id IS NOT NULL AND nomina_periodo_detalle_id IS NULL)'),
                'Periodo y detalle de snapshot deben guardarse juntos o ambos NULL.'
            );
            hcReportZeroCount(
                'pagos laborales snapshot con periodo inexistente o de otro hotel',
                hcCountScalar($pdo, 'SELECT COUNT(*)
                    FROM trabajador_pagos_caja pc
                    LEFT JOIN trabajador_nomina_periodos p
                      ON p.id = pc.nomina_periodo_id
                     AND p.hotel_id = pc.hotel_id
                    WHERE pc.nomina_periodo_id IS NOT NULL
                      AND p.id IS NULL'),
                'Reconciliar nomina_periodo_id contra trabajador_nomina_periodos del mismo hotel.'
            );
            hcReportZeroCount(
                'pagos laborales snapshot con detalle inexistente o de otro hotel',
                hcCountScalar($pdo, 'SELECT COUNT(*)
                    FROM trabajador_pagos_caja pc
                    LEFT JOIN trabajador_nomina_periodo_detalles d
                      ON d.id = pc.nomina_periodo_detalle_id
                     AND d.hotel_id = pc.hotel_id
                    WHERE pc.nomina_periodo_detalle_id IS NOT NULL
                      AND d.id IS NULL'),
                'Reconciliar nomina_periodo_detalle_id contra detalles del mismo hotel.'
            );
            hcReportZeroCount(
                'pagos laborales snapshot con detalle fuera de periodo o trabajador',
                hcCountScalar($pdo, 'SELECT COUNT(*)
                    FROM trabajador_pagos_caja pc
                    INNER JOIN trabajador_nomina_periodos p
                      ON p.id = pc.nomina_periodo_id
                     AND p.hotel_id = pc.hotel_id
                    INNER JOIN trabajador_nomina_periodo_detalles d
                      ON d.id = pc.nomina_periodo_detalle_id
                     AND d.hotel_id = pc.hotel_id
                    WHERE pc.nomina_periodo_id IS NOT NULL
                      AND (d.periodo_id <> p.id OR d.trabajador_id <> pc.trabajador_id)'),
                'Reconciliar trazabilidad: detalle, periodo y trabajador pagado deben coincidir.'
            );

            if (hcTableExists($pdo, $database, 'migrations')) {
                $stmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM migrations
                     WHERE nombre = '20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql'
                       AND estado = 'ejecutada'"
                );
                $stmt->execute();
                if ((int)$stmt->fetchColumn() === 1) {
                    hcOk('Migracion 5E-P-A trazabilidad snapshot registrada como ejecutada.');
                } else {
                    hcError(
                        'Columnas 5E-P-A existen pero la migracion no esta registrada.',
                        'Reconciliar migrations para 20260622_001_fase_5e_p_a_trazabilidad_pago_snapshot.sql.'
                    );
                }
            }
        }

        $workerCashPaymentOrphans = hcCountScalar(
            $pdo,
            'SELECT COUNT(*)
             FROM trabajador_pagos_caja pc
             LEFT JOIN movimientos_caja m
               ON m.id = pc.movimiento_caja_id
              AND m.hotel_id = pc.hotel_id
             WHERE m.id IS NULL'
        );
        if ($workerCashPaymentOrphans === 0) {
            hcOk('Pagos laborales Caja mantienen vinculo con movimientos_caja.');
        } else {
            hcError(
                'Pagos laborales Caja sin movimiento_caja asociado: ' . (string)$workerCashPaymentOrphans . '.',
                'Reconciliar trabajador_pagos_caja.movimiento_caja_id antes de continuar.'
            );
        }

        if (hcTableExists($pdo, $database, 'migrations')) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM migrations
                 WHERE nombre = '20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql'
                   AND estado = 'ejecutada'"
            );
            $stmt->execute();
            if ((int)$stmt->fetchColumn() === 1) {
                hcOk('Migracion 5E-B-A trabajador_pagos_caja registrada como ejecutada.');
            } else {
                hcWarning(
                    'Tabla trabajador_pagos_caja existe pero la migracion 5E-B-A no esta registrada.',
                    'Registrar solo si la migracion fue aplicada tras backup verificado.'
                );
            }
        }
    } else {
        hcWarning(
            'Tabla 5E-B-A trabajador_pagos_caja aun no existe.',
            'Aplicar migrations/20260619_003_fase_5e_b_a_trabajador_pagos_caja.sql solo con backup y autorizacion explicita.'
        );
    }

    $payrollPeriodTables = [
        'trabajador_nomina_periodos' => [
            'id', 'hotel_id', 'tipo_periodo', 'etiqueta', 'fecha_inicio', 'fecha_fin',
            'estado', 'filtros_json', 'resumen_json', 'trabajadores_total',
            'bruto_total', 'deducciones_total', 'pagos_caja_aplicados_total',
            'reversiones_detectadas_total', 'neto_sugerido_total', 'pendiente_pago_total',
            'cerrado_por', 'cerrado_at', 'aprobado_por', 'aprobado_at',
            'anulado_por', 'anulado_at', 'motivo_anulacion', 'created_at', 'updated_at',
        ],
        'trabajador_nomina_periodo_detalles' => [
            'id', 'periodo_id', 'hotel_id', 'trabajador_id', 'trabajador_nombre',
            'trabajador_identificacion', 'trabajador_rol', 'trabajador_estado',
            'estado_preview_nomina', 'motivo_bloqueo_nomina', 'conceptos_count',
            'conceptos_a_favor', 'conceptos_en_contra', 'bruto_periodo',
            'anticipos_count', 'anticipos_saldo', 'prestamos_count', 'prestamos_saldo',
            'deducciones_informativas', 'pagos_caja_count', 'pagos_caja_pagados',
            'pagos_caja_revertidos', 'pagos_caja_aplicados',
            'pagos_caja_revertidos_total', 'reversiones_detectadas',
            'ultimo_pago_caja', 'neto_sugerido', 'pendiente_pago_sugerido',
            'snapshot_json', 'created_at',
        ],
        'trabajador_nomina_periodo_eventos' => [
            'id', 'periodo_id', 'hotel_id', 'tipo', 'estado_resultante',
            'descripcion', 'motivo', 'created_by', 'created_at',
        ],
    ];
    $payrollPeriodMissingTables = [];
    $payrollPeriodMissingColumns = [];
    foreach ($payrollPeriodTables as $table => $columns) {
        if (!hcTableExists($pdo, $database, $table)) {
            $payrollPeriodMissingTables[] = $table;
            continue;
        }
        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $payrollPeriodMissingColumns[] = $table . '.' . $column;
            }
        }
    }

    $payrollPeriodIndexes = [];
    if (hcTableExists($pdo, $database, 'trabajador_nomina_periodos')) {
        foreach ([
            'uk_trabajador_nomina_periodo_hotel_rango',
            'idx_trabajador_nomina_periodos_hotel_estado',
            'idx_trabajador_nomina_periodos_hotel_fecha',
        ] as $index) {
            if (!hcIndexExists($pdo, $database, 'trabajador_nomina_periodos', $index)) {
                $payrollPeriodIndexes[] = 'trabajador_nomina_periodos.' . $index;
            }
        }
    }
    if (hcTableExists($pdo, $database, 'trabajador_nomina_periodo_detalles')) {
        foreach ([
            'uk_trabajador_nomina_detalle_periodo_trabajador',
            'idx_trabajador_nomina_detalles_hotel_periodo',
            'idx_trabajador_nomina_detalles_trabajador',
        ] as $index) {
            if (!hcIndexExists($pdo, $database, 'trabajador_nomina_periodo_detalles', $index)) {
                $payrollPeriodIndexes[] = 'trabajador_nomina_periodo_detalles.' . $index;
            }
        }
    }
    if (hcTableExists($pdo, $database, 'trabajador_nomina_periodo_eventos')) {
        foreach ([
            'idx_trabajador_nomina_eventos_periodo',
            'idx_trabajador_nomina_eventos_hotel',
            'idx_trabajador_nomina_eventos_created_by',
        ] as $index) {
            if (!hcIndexExists($pdo, $database, 'trabajador_nomina_periodo_eventos', $index)) {
                $payrollPeriodIndexes[] = 'trabajador_nomina_periodo_eventos.' . $index;
            }
        }
    }

    if (empty($payrollPeriodMissingTables) && empty($payrollPeriodMissingColumns) && empty($payrollPeriodIndexes)) {
        $payrollPeriodRows = hcTableExists($pdo, $database, 'trabajador_nomina_periodos')
            ? hcCountRows($pdo, 'trabajador_nomina_periodos')
            : 0;
        hcOk('Tablas 5E-L-A de periodos de pre-nomina persistentes disponibles; snapshots actuales: ' . (string)$payrollPeriodRows . '.');
    } else {
        hcWarning(
            'Tablas 5E-L-A de periodos de pre-nomina pendientes o incompletas. Tablas: ' . implode(', ', $payrollPeriodMissingTables) . '. Columnas: ' . implode(', ', $payrollPeriodMissingColumns) . '. Indices: ' . implode(', ', $payrollPeriodIndexes) . '.',
            'Aplicar migrations/20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql solo con backup y autorizacion explicita.'
        );
    }

    if (hcTableExists($pdo, $database, 'migrations')) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM migrations
             WHERE nombre = '20260621_001_fase_5e_l_a_nomina_periodos_persistentes.sql'
               AND estado = 'ejecutada'"
        );
        $stmt->execute();
        if ((int)$stmt->fetchColumn() === 1) {
            hcOk('Migracion 5E-L-A de periodos persistentes registrada como ejecutada.');
        } else {
            hcWarning(
                'Migracion 5E-L-A de periodos persistentes no esta registrada como ejecutada.',
                'Registrar/aplicar solo despues de backup SQL valido.'
            );
        }
    }

    $cajaNomina = hcTableExists($pdo, $database, 'movimientos_caja')
        ? hcCountScalar($pdo, "SELECT COUNT(*) FROM movimientos_caja WHERE LOWER(COALESCE(categoria, '')) LIKE '%nomina%'")
        : null;
    $categoriasNomina = hcTableExists($pdo, $database, 'categorias_movimientos')
        ? hcCountScalar($pdo, "SELECT COUNT(*) FROM categorias_movimientos WHERE LOWER(COALESCE(nombre, '')) LIKE '%nomina%'")
        : null;
    if ($cajaNomina === 0 && $categoriasNomina === 0) {
        hcOk('Personal base NP-A no genero movimientos ni categorias de Caja/Nomina.');
    } else {
        hcError(
            'Personal base NP-A detecta referencias de Caja/Nomina. movimientos=' . (string)$cajaNomina . ', categorias=' . (string)$categoriasNomina . '.',
            'Revisar antes de continuar; NP-A no debe tocar Caja ni crear categoria Nomina.'
        );
    }

    $tlmTables = [
        'tareas_operativas' => [
            'id', 'hotel_id', 'categoria', 'titulo', 'descripcion', 'prioridad',
            'estado', 'habitacion_id', 'reservacion_id', 'huesped_id',
            'trabajador_id', 'mantenimiento_id', 'fecha_programada', 'fecha_limite',
            'fecha_inicio', 'fecha_cierre', 'creada_por_usuario_id',
            'asignada_por_usuario_id', 'cerrada_por_usuario_id',
            'cancelada_por_usuario_id', 'origen', 'notas_cierre',
            'created_at', 'updated_at',
        ],
        'tarea_eventos' => [
            'id', 'hotel_id', 'tarea_id', 'tipo_evento', 'estado_anterior',
            'estado_nuevo', 'comentario', 'usuario_id', 'created_at',
        ],
    ];

    $tlmMissingTables = [];
    $tlmMissingColumns = [];
    $tlmRows = 0;
    foreach ($tlmTables as $table => $columns) {
        if (!hcTableExists($pdo, $database, $table)) {
            $tlmMissingTables[] = $table;
            continue;
        }

        $tlmRows += (int)(hcCountRows($pdo, $table) ?? 0);

        foreach ($columns as $column) {
            if (!hcColumnExists($pdo, $database, $table, $column)) {
                $tlmMissingColumns[] = $table . '.' . $column;
            }
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            $tlmMissingColumns[] = $table . '.hotel_id';
        } else {
            $nullHotel = hcNullHotelRows($pdo, $table);
            if ($nullHotel === 0) {
                hcOk('TLM-A tabla ' . $table . ' tiene hotel_id completo.');
            } else {
                hcError(
                    'TLM-A tabla ' . $table . ' tiene hotel_id NULL: ' . (string)$nullHotel . '.',
                    'Reconciliar hotel_id antes de exponer tareas operativas.'
                );
            }
        }
    }

    if (empty($tlmMissingTables) && empty($tlmMissingColumns)) {
        hcOk('TLM-A base de tareas operativas disponible. Registros totales=' . (string)$tlmRows . '.');
        hcReportTlmConsistency($pdo, $database);
    } else {
        hcWarning(
            'TLM-A base de tareas operativas incompleta. Tablas faltantes: ' . implode(', ', $tlmMissingTables) . '. Columnas faltantes: ' . implode(', ', $tlmMissingColumns) . '.',
            'No avanzar a UI o POST de tareas hasta completar la migracion base.'
        );
    }

    if (hcTableExists($pdo, $database, 'migrations')) {
        $tlmMigration = hcCountScalar(
            $pdo,
            "SELECT COUNT(*) FROM migrations WHERE nombre = '20260616_002_fase_tlm_a_tareas_base.sql' AND estado = 'ejecutada'"
        );
        if ($tlmMigration === 1) {
            hcOk('Migracion TLM-A de tareas operativas registrada como ejecutada.');
        } else {
            hcWarning(
                'Tablas TLM-A pueden existir pero la migracion no esta registrada.',
                'Registrar solo si fue aplicada con backup verificado.'
            );
        }
    }

    $cajaTlmCategorias = hcTableExists($pdo, $database, 'categorias_movimientos')
        ? hcCountScalar($pdo, "SELECT COUNT(*) FROM categorias_movimientos WHERE LOWER(COALESCE(nombre, '')) IN ('tareas', 'tarea', 'limpieza', 'mantenimiento')")
        : null;
    if ($cajaTlmCategorias === 0) {
        hcOk('TLM-A no creo categorias operativas nuevas en Caja.');
    } else {
        hcWarning(
            'TLM-A detecta categorias de Caja que coinciden con tareas/limpieza/mantenimiento: ' . (string)$cajaTlmCategorias . '.',
            'Validar que sean historicas antes de cualquier integracion; TLM-A no debe tocar Caja.'
        );
    }

    $inventoryModernTables = [
        'inventario_productos' => 'productos operativos',
        'movimientos_inventario' => 'movimientos operativos',
        'inventario_config_habitacion' => 'configuracion por tipo de habitacion',
    ];

    foreach ($inventoryModernTables as $table => $purpose) {
        if (!hcTableExists($pdo, $database, $table)) {
            hcError('Contrato moderno de inventario incompleto: falta ' . $table . '.', 'No construir inventario avanzado hasta restaurar la tabla moderna.');
            continue;
        }

        if (!hcColumnExists($pdo, $database, $table, 'hotel_id')) {
            hcError('Contrato moderno de inventario sin hotel_id: ' . $table . '.', 'Agregar aislamiento por hotel antes de usar este flujo.');
            continue;
        }

        $nullRows = hcNullHotelRows($pdo, $table);
        if ($nullRows === 0) {
            hcOk('Contrato moderno de inventario OK: ' . $table . ' (' . $purpose . ') tiene hotel_id completo.');
        } else {
            hcError('Contrato moderno de inventario con hotel_id NULL en ' . $table . ': ' . (string) $nullRows, 'Corregir backfill antes de operar inventario multihotel.');
        }
    }

    $inventoryLegacyPairs = [
        ['legacy' => 'productos', 'modern' => 'inventario_productos', 'note' => 'catalogo legacy con datos semilla/antiguos'],
        ['legacy' => 'inventario_movimientos', 'modern' => 'movimientos_inventario', 'note' => 'movimientos legacy sin registros'],
        ['legacy' => 'inventario_habitacion_config', 'modern' => 'inventario_config_habitacion', 'note' => 'configuracion legacy por habitacion sin registros'],
    ];

    foreach ($inventoryLegacyPairs as $pair) {
        $legacyExists = hcTableExists($pdo, $database, $pair['legacy']);
        $modernExists = hcTableExists($pdo, $database, $pair['modern']);
        if ($legacyExists && $modernExists) {
            $legacyCount = hcCountRows($pdo, $pair['legacy']);
            $modernCount = hcCountRows($pdo, $pair['modern']);

            $isLegacyProductSeedCatalog = $pair['legacy'] === 'productos'
                && $pair['modern'] === 'inventario_productos';
            $legacyProductAlertRows = null;
            if ($isLegacyProductSeedCatalog && hcTableExists($pdo, $database, 'alertas_inventario')) {
                $legacyProductAlertRows = hcCountRows($pdo, 'alertas_inventario');
            }

            if ($legacyCount === 0) {
                hcOk(
                    'Inventario legacy controlado: ' . $pair['legacy'] . ' esta vacia y ' . $pair['modern'] . ' mantiene el contrato activo (' . $modernCount . ' registros).'
                );
            } elseif (
                $isLegacyProductSeedCatalog
                && $modernCount > 0
                && hcColumnExists($pdo, $database, 'inventario_productos', 'hotel_id')
                && !hcColumnExists($pdo, $database, 'productos', 'hotel_id')
                && $legacyProductAlertRows === 0
            ) {
                hcOk(
                    'Inventario legacy controlado: productos conserva ' . $legacyCount . ' registros semilla sin hotel_id, alertas_inventario esta vacia e inventario_productos mantiene el contrato activo por hotel (' . $modernCount . ' registros).'
                );
            } else {
                hcWarning(
                    'Inventario legacy congelado: ' . $pair['legacy'] . ' (' . $legacyCount . ') convive con ' . $pair['modern'] . ' (' . $modernCount . ') como ' . $pair['note'] . '.',
                    $inventoryDoc && is_file($inventoryDoc)
                        ? 'Mantener congelado; seguir docs/technical/inventory_reconciliation.md antes de cualquier fusion.'
                        : 'Montar o crear docs/technical/inventory_reconciliation.md antes de continuar con Inventario avanzado.'
                );
            }
        } elseif ($legacyExists || $modernExists) {
            hcWarning(
                'Inventario tiene solo una tabla del par legacy/moderno: ' . $pair['legacy'] . ' / ' . $pair['modern'] . '.',
                'Validar dependencias antes de limpiar nombres historicos.'
            );
        }
    }

    $duplicatePairs = [
        ['legacy' => 'push_subscriptions', 'modern' => 'pwa_push_subscriptions'],
        ['legacy' => 'huespedes_vehiculos', 'modern' => 'huesped_vehiculos'],
    ];

    foreach ($duplicatePairs as $pair) {
        $legacyExists = hcTableExists($pdo, $database, $pair['legacy']);
        $modernExists = hcTableExists($pdo, $database, $pair['modern']);
        if ($legacyExists && $modernExists) {
            $legacyCount = hcCountRows($pdo, $pair['legacy']);
            $modernCount = hcCountRows($pdo, $pair['modern']);

            if ($legacyCount === 0) {
                hcOk(
                    'Par duplicado controlado: ' . $pair['legacy'] . ' esta vacia y ' . $pair['modern'] . ' mantiene el contrato activo (' . $modernCount . ' registros).'
                );
            } else {
                hcWarning(
                    'Tablas duplicadas/solapadas detectadas: ' . $pair['legacy'] . ' (' . $legacyCount . ') vs ' . $pair['modern'] . ' (' . $modernCount . ').',
                    'No borrar ni fusionar; seguir docs/technical/duplicated_tables.md.'
                );
            }
        } elseif ($legacyExists || $modernExists) {
            hcWarning(
                'Solo existe una tabla del par duplicado: ' . $pair['legacy'] . ' / ' . $pair['modern'] . '.',
                'Validar dependencias antes de limpiar nombres historicos.'
            );
        }
    }

    $viewStmt = $pdo->prepare(
        'SELECT DEFINER, SECURITY_TYPE
         FROM information_schema.VIEWS
         WHERE TABLE_SCHEMA = :db
           AND TABLE_NAME = :view'
    );
    $viewStmt->execute(['db' => $database, 'view' => 'vista_caja_actual']);
    $viewInfo = $viewStmt->fetch();

    if (!$viewInfo) {
        hcWarning(
            'vista_caja_actual no existe.',
            'Confirmar si consumidores legacy la necesitan antes de limpiar referencias.'
        );
    } else {
        try {
            $pdo->query('SELECT * FROM vista_caja_actual LIMIT 1')->fetch();
            hcOk('vista_caja_actual responde SELECT simple.');
        } catch (Throwable $e) {
            hcError(
                'vista_caja_actual no responde SELECT simple: ' . $e->getMessage(),
                'Recrear la vista con SQL SECURITY INVOKER para evitar definer invalido.'
            );
        }

        if (($viewInfo['SECURITY_TYPE'] ?? '') === 'INVOKER') {
            hcOk('vista_caja_actual usa SQL SECURITY INVOKER.');
        } else {
            hcWarning(
                'vista_caja_actual usa SECURITY_TYPE=' . ($viewInfo['SECURITY_TYPE'] ?? 'desconocido') . ' y DEFINER=' . ($viewInfo['DEFINER'] ?? 'desconocido') . '.',
                'Preferir SQL SECURITY INVOKER en local para que mysqldump no dependa de un definer heredado.'
            );
        }
    }
}

if (!is_file($routesPath)) {
    hcError('No se encontro config/routes.php.', 'No se puede validar rutas sin archivo de rutas.');
    $routes = [];
} else {
    $routes = hcParseRoutes($routesPath);
    hcOk('Rutas activas parseadas: ' . count($routes));

    $missingRouteMethods = [];
    foreach ($routes as $route) {
        $controllerFile = $controllersDir . '/' . $route['controller'] . 'Controller.php';
        if (!is_file($controllerFile)) {
            $missingRouteMethods[] = $route['method'] . ' ' . $route['path'] . ' -> controller missing ' . $route['controller'];
            continue;
        }

        $controllerCode = file_get_contents($controllerFile) ?: '';
        $action = $route['action'];
        $hasActionSuffix = preg_match('/function\s+' . preg_quote($action . 'Action', '/') . '\s*\(/', $controllerCode);
        $hasDirectMethod = preg_match('/function\s+' . preg_quote($action, '/') . '\s*\(/', $controllerCode);
        if (!$hasActionSuffix && !$hasDirectMethod) {
            $missingRouteMethods[] = $route['method'] . ' ' . $route['path'] . ' -> ' . $route['controller'] . '::' . $action;
        }
    }

    if (empty($missingRouteMethods)) {
        hcOk('No hay rutas activas apuntando a metodos inexistentes.');
    } else {
        hcError(
            'Rutas activas con controlador/metodo inexistente: ' . implode(' | ', $missingRouteMethods),
            'Corregir aliases o desactivar rutas no implementadas antes de abrir esos flujos.'
        );
    }

    $inventarioControllerPath = $controllersDir . '/InventarioController.php';
    $inventarioControllerCode = is_file($inventarioControllerPath) ? (string) file_get_contents($inventarioControllerPath) : '';
    if ($inventarioControllerCode !== '') {
        if (
            strpos($inventarioControllerCode, 'protected $table = \'productos\'') === false
            && strpos($inventarioControllerCode, 'FROM productos') === false
            && strpos($inventarioControllerCode, 'JOIN productos') === false
        ) {
            hcOk('InventarioController no usa tabla legacy productos en SQL activo.');
        } else {
            hcWarning(
                'InventarioController contiene referencias SQL a productos legacy.',
                'Mover cualquier lectura activa al contrato moderno inventario_productos antes de Inventario avanzado.'
            );
        }

        if (
            hcRouteExists($routes, 'inventario/generarPdfMovimientos', 'post')
            && strpos($inventarioControllerCode, 'FROM movimientos_inventario m') !== false
            && strpos($inventarioControllerCode, 'WHERE m.hotel_id = ?') !== false
            && strpos($inventarioControllerCode, 'p.hotel_id = m.hotel_id') !== false
        ) {
            hcOk('PDF de movimientos de inventario filtra movimientos/productos por hotel_id.');
        } elseif (hcRouteExists($routes, 'inventario/generarPdfMovimientos', 'post')) {
            hcError(
                'PDF de movimientos de inventario no muestra aislamiento hotel_id completo.',
                'Filtrar movimientos_inventario por hotel_id y unir productos/habitaciones por el mismo hotel.'
            );
        }

        $guardarConfigCode = hcExtractMethodCode($inventarioControllerCode, 'guardarConfiguracionAction');
        if (
            $guardarConfigCode !== ''
            && strpos($guardarConfigCode, 'actualizarConfiguracion') !== false
            && strpos($guardarConfigCode, 'is_array($productos)') !== false
            && strpos($guardarConfigCode, 'intval($cantidad)') === false
        ) {
            hcOk('InventarioController delega guardado de configuracion al modelo sin silenciar validaciones.');
        } else {
            hcError(
                'InventarioController puede estar casteando o ignorando validaciones de configuracion.',
                'Delegar cantidades crudas a Inventario::actualizarConfiguracion y dejar que el modelo valide.'
            );
        }

        if (
            $guardarConfigCode !== ''
            && strpos($guardarConfigCode, 'registrarAuditoriaConfiguracionInventario') !== false
            && strpos($guardarConfigCode, '$cambiosAuditables') !== false
            && strpos($inventarioControllerCode, 'inventario.configuracion_actualizada') !== false
            && strpos($inventarioControllerCode, 'datos_antes') !== false
            && strpos($inventarioControllerCode, 'datos_despues') !== false
        ) {
            hcOk('Configuracion de inventario registra auditoria minima Fase 2E cuando hay cambios reales.');
        } else {
            hcWarning(
                'Auditoria de configuracion de inventario no esta implementada o no es detectable.',
                'Mantener documentado como pendiente o registrar inventario.configuracion_actualizada sin romper el guardado.'
            );
        }

        $officialInventoryRoutes = [
            ['get', 'inventario'],
            ['get', 'inventario/configuracion'],
            ['post', 'inventario/guardarConfiguracion'],
            ['get', 'inventario/movimientos'],
            ['get', 'inventario/exportar'],
            ['post', 'inventario/generarPdfMovimientos'],
            ['get', 'inventario/ajuste/1', true],
            ['post', 'inventario/ajuste/1', true],
            ['get', 'api/inventario/alertas'],
            ['get', 'api/inventario/verificar-stock/1', true],
            ['get', 'api/inventario/preview-checkin/1', true],
        ];

        foreach ($officialInventoryRoutes as $routeCheck) {
            [$method, $path] = $routeCheck;
            $isPattern = (bool)($routeCheck[2] ?? false);
            $exists = $isPattern
                ? hcRoutePatternExists($routes, $path, $method)
                : hcRouteExists($routes, $path, $method);

            if ($exists) {
                hcOk('Ruta oficial de inventario registrada: ' . strtoupper($method) . ' /' . $path);
            } else {
                hcError(
                    'Falta ruta oficial de inventario: ' . strtoupper($method) . ' /' . $path,
                    'Restaurar solo la ruta oficial existente del contrato moderno; no reactivar rutas legacy.'
                );
            }
        }
    }

    $inventoryModelPath = $appRoot . '/app/models/Inventario.php';
    $inventoryModelCode = is_file($inventoryModelPath) ? (string) file_get_contents($inventoryModelPath) : '';
    if ($inventoryModelCode !== '') {
        if (
            strpos($inventoryModelCode, "protected \$table = 'inventario_productos'") !== false
            && strpos($inventoryModelCode, 'FROM inventario_config_habitacion ich') !== false
            && strpos($inventoryModelCode, 'JOIN inventario_productos p') !== false
            && strpos($inventoryModelCode, 'p.hotel_id = ich.hotel_id') !== false
            && strpos($inventoryModelCode, 'AND ich.hotel_id = ?') !== false
        ) {
            hcOk('Inventario::getConfiguracionCompleta usa contrato moderno scoped por hotel.');
        } else {
            hcError(
                'Inventario::getConfiguracionCompleta no muestra contrato moderno completo.',
                'Debe leer inventario_config_habitacion + inventario_productos y unir por hotel_id.'
            );
        }

        if (
            strpos($inventoryModelCode, 'WHERE id = ? AND hotel_id = ?') !== false
            && strpos($inventoryModelCode, 'WHERE tipo_habitacion = ? AND producto_id = ? AND hotel_id = ?') !== false
            && strpos($inventoryModelCode, '(tipo_habitacion, producto_id, cantidad_descontar, activo, hotel_id, created_at)') !== false
        ) {
            hcOk('Inventario::actualizarConfiguracion valida producto y configuracion por hotel_id.');
        } else {
            hcError(
                'Inventario::actualizarConfiguracion no muestra aislamiento hotel_id completo.',
                'No guardar configuracion por habitacion sin validar producto y registro por hotel.'
            );
        }

        if (
            strpos($inventoryModelCode, 'tipoHabitacionPerteneceAlHotel') !== false
            && strpos($inventoryModelCode, 'FROM tipos_habitacion') !== false
            && strpos($inventoryModelCode, 'codigo = ? AND hotel_id = ? AND activo = 1') !== false
            && strpos($inventoryModelCode, 'descuento_automatico = 1') !== false
            && strpos($inventoryModelCode, 'La cantidad de configuracion no puede ser negativa') !== false
            && strpos($inventoryModelCode, 'throw $e') !== false
        ) {
            hcOk('Configuracion oficial Fase 2D valida tipo/producto/cantidad por hotel antes de guardar.');
        } else {
            hcError(
                'Configuracion oficial Fase 2D no muestra validaciones completas.',
                'Validar tipo_habitacion del hotel, producto activo/automatico del hotel y cantidad numerica no negativa.'
            );
        }

        if (
            strpos($inventoryModelCode, "'changed' =>") !== false
            && strpos($inventoryModelCode, "'cantidad_anterior' =>") !== false
            && strpos($inventoryModelCode, "'cantidad_nueva' =>") !== false
            && strpos($inventoryModelCode, "'descuento_automatico_cambio' => false") !== false
        ) {
            hcOk('Inventario::actualizarConfiguracion devuelve metadatos auditables Fase 2E.');
        } else {
            hcWarning(
                'Inventario::actualizarConfiguracion no expone metadatos auditables claros.',
                'Si se audita configuracion, capturar cantidad anterior/nueva y producto/tipo sin nueva migracion.'
            );
        }
    } else {
        hcError('No se encontro app/models/Inventario.php.', 'No se puede validar contrato moderno de inventario.');
    }

    $inventoryServicePath = $appRoot . '/app/services/InventarioService.php';
    $inventoryServiceCode = is_file($inventoryServicePath) ? (string) file_get_contents($inventoryServicePath) : '';
    if ($inventoryServiceCode !== '') {
        if (
            strpos($inventoryServiceCode, 'JOIN inventario_productos ip') !== false
            && strpos($inventoryServiceCode, 'ip.hotel_id = ich.hotel_id') !== false
            && strpos($inventoryServiceCode, 'AND ich.hotel_id = ?') !== false
            && strpos($inventoryServiceCode, 'INSERT INTO movimientos_inventario') !== false
            && strpos($inventoryServiceCode, 'WHERE id = ? AND hotel_id = ?') !== false
        ) {
            hcOk('InventarioService usa inventario_config_habitacion/inventario_productos/movimientos_inventario con hotel_id.');
        } else {
            hcError(
                'InventarioService no muestra contrato moderno scoped completo.',
                'No ejecutar descuento/preview de inventario si mezcla productos legacy o ignora hotel_id.'
            );
        }
    } else {
        hcError('No se encontro app/services/InventarioService.php.', 'No se puede validar consumo automatico de inventario.');
    }

    $movementModelPath = $appRoot . '/app/models/MovimientoInventario.php';
    $movementModelCode = is_file($movementModelPath) ? (string) file_get_contents($movementModelPath) : '';
    if ($movementModelCode !== '') {
        if (
            strpos($movementModelCode, "protected \$table = 'movimientos_inventario'") !== false
            && strpos($movementModelCode, 'productoPerteneceAlHotel') !== false
            && strpos($movementModelCode, 'habitacionPerteneceAlHotel') !== false
            && strpos($movementModelCode, 'p.hotel_id = m.hotel_id') !== false
        ) {
            hcOk('MovimientoInventario mantiene movimientos en tabla moderna y valida hotel.');
        } else {
            hcError(
                'MovimientoInventario no muestra validacion moderna de hotel completa.',
                'Los movimientos manuales deben validar producto/habitacion y usar movimientos_inventario.'
            );
        }
    } else {
        hcError('No se encontro app/models/MovimientoInventario.php.', 'No se puede validar trazabilidad moderna de inventario.');
    }

    $apiControllerPath = $controllersDir . '/ApiController.php';
    $apiCode = is_file($apiControllerPath) ? (string) file_get_contents($apiControllerPath) : '';
    if ($apiCode !== '') {
        $alertasCode = hcExtractMethodCode($apiCode, 'alertasInventarioAction');
        $stockCode = hcExtractMethodCode($apiCode, 'verificarStockHabitacionAction');
        $previewCode = hcExtractMethodCode($apiCode, 'previewCheckinInventarioAction');

        if (
            $alertasCode !== ''
            && strpos($alertasCode, 'FROM inventario_productos') !== false
            && strpos($alertasCode, 'WHERE hotel_id = ?') !== false
            && strpos($alertasCode, 'FROM productos') === false
        ) {
            hcOk('/api/inventario/alertas usa inventario_productos scoped y no productos legacy.');
        } else {
            hcError(
                '/api/inventario/alertas no muestra fuente moderna scoped.',
                'Debe derivar alertas desde inventario_productos.hotel_id, no desde productos legacy.'
            );
        }

        if ($stockCode !== '' && strpos($stockCode, 'new InventarioService') !== false) {
            hcOk('/api/inventario/verificar-stock delega en InventarioService moderno.');
        } else {
            hcWarning(
                '/api/inventario/verificar-stock no muestra delegacion clara a InventarioService.',
                'Mantener la verificacion de stock en el servicio moderno scoped.'
            );
        }

        if ($previewCode !== '' && strpos($previewCode, 'Preview de inventario aun no disponible con fuente scoped') !== false) {
            hcOk('/api/inventario/preview-checkin mantiene respuesta controlada sin mezclar fuentes legacy.');
        } else {
            hcWarning(
                '/api/inventario/preview-checkin no muestra respuesta controlada documentada.',
                'No habilitar preview real hasta definir fuente scoped completa.'
            );
        }

        if (
            $previewCode !== ''
            && stripos($previewCode, 'UPDATE ') === false
            && stripos($previewCode, 'INSERT ') === false
            && stripos($previewCode, 'DELETE ') === false
        ) {
            hcOk('/api/inventario/preview-checkin es lectura pura en Fase 2E.');
        } elseif ($previewCode !== '') {
            hcError(
                '/api/inventario/preview-checkin contiene escritura potencial.',
                'El preview por reservacion debe ser lectura pura y no modificar stock.'
            );
        }

        $legacyApiMethod = hcExtractMethodCode($apiCode, 'getProductosDescuentoReservacion');
        $legacyApiRoutes = array_filter($routes, static function (array $route): bool {
            return strtolower((string) $route['controller']) === 'api'
                && (string) $route['action'] === 'getProductosDescuentoReservacion';
        });

        if (!empty($legacyApiRoutes)) {
            hcError(
                'ApiController::getProductosDescuentoReservacion esta ruteado aunque es legacy.',
                'Retirar la ruta y usar /api/inventario/verificar-stock o InventarioService scoped.'
            );
        } elseif ($legacyApiMethod !== '' && strpos($legacyApiMethod, 'LEGACY INVENTARIO FASE 2D') !== false) {
            hcOk('ApiController::getProductosDescuentoReservacion queda congelado y sin ruta activa.');
        } elseif ($legacyApiMethod !== '') {
            hcWarning(
                'ApiController::getProductosDescuentoReservacion existe sin marca Fase 2D.',
                'Congelarlo o modernizarlo antes de exponer cualquier preview por reservacion.'
            );
        }

        if (
            $legacyApiMethod !== ''
            && (strpos($legacyApiMethod, 'ConfiguracionHabitacionService') !== false || strpos($legacyApiMethod, 'services/ConfiguracionHabitacionService.php') !== false)
        ) {
            hcWarning(
                'ApiController contiene metodo legacy no ruteado getProductosDescuentoReservacion con dependencia antigua.',
                'No registrar esta ruta; reemplazarla por InventarioService scoped si una fase futura necesita ese preview.'
            );
        }
    }

    $legacyInventoryFiles = [
        $appRoot . '/app/models/ConfiguracionInventario.php',
        $appRoot . '/app/services/ConfiguracionInventarioService.php',
        $appRoot . '/app/models/InventarioReportes.php',
        $appRoot . '/app/helpers/integracion_inventario.php',
        $appRoot . '/app/views/inventario/ConfiguracionHabitacionService.php',
    ];

    foreach ($legacyInventoryFiles as $legacyPath) {
        if (!is_file($legacyPath)) {
            continue;
        }

        $legacyCode = (string) file_get_contents($legacyPath);
        if (strpos($legacyCode, 'LEGACY INVENTARIO FASE 2C') !== false) {
            hcOk(
                'Archivo legacy de inventario congelado y marcado: ' . str_replace($appRoot . '/', '', $legacyPath)
            );
        } else {
            hcWarning(
                'Archivo legacy de inventario sin marca Fase 2C: ' . str_replace($appRoot . '/', '', $legacyPath),
                'Marcarlo como congelado o migrarlo explicitamente antes de conectarlo a rutas activas.'
            );
        }
    }

    $legacyViewConfigPath = $appRoot . '/app/views/inventario/configuracion-habitacion.php';
    if (is_file($legacyViewConfigPath) && !hcRouteExists($routes, 'inventario/configuracion-habitacion', 'get')) {
        hcOk(
            'Vista legacy inventario/configuracion-habitacion.php permanece desconectada; el flujo oficial es /inventario/configuracion.'
        );
    }

    $integracionInventarioPath = $appRoot . '/app/helpers/integracion_inventario.php';
    $integracionInventarioCode = is_file($integracionInventarioPath) ? (string) file_get_contents($integracionInventarioPath) : '';
    if ($integracionInventarioCode !== '') {
        $hasDestructiveSql = strpos($integracionInventarioCode, 'DELETE FROM movimientos_inventario') !== false
            || strpos($integracionInventarioCode, 'UPDATE productos') !== false;
        $hasLegacyWriteGuard = strpos($integracionInventarioCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
            && strpos($integracionInventarioCode, 'inventario_legacy_fase2d_escritura_permitida(__FUNCTION__)') !== false;

        if ($hasDestructiveSql && $hasLegacyWriteGuard) {
            hcOk('integracion_inventario.php conserva SQL legacy peligroso detras de guarda Fase 2D.');
        } elseif ($hasDestructiveSql) {
            hcError(
                'integracion_inventario.php contiene SQL destructivo sin guarda Fase 2D.',
                'Bloquear funciones legacy de escritura o mover esos ejemplos a documentacion no ejecutable.'
            );
        } elseif (strpos($integracionInventarioCode, 'LEGACY INVENTARIO FASE 2D') !== false) {
            hcOk('integracion_inventario.php esta marcado como legacy Fase 2D.');
        }
    }

    if ($duplicatedTablesDoc && is_file($duplicatedTablesDoc)) {
        $duplicatedDocCode = (string) file_get_contents($duplicatedTablesDoc);
        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2C') !== false
            && strpos($duplicatedDocCode, 'ConfiguracionInventario') !== false
            && strpos($duplicatedDocCode, 'ConfiguracionHabitacionService') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra congelamiento Fase 2C.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2C.',
                'Actualizar docs/technical/duplicated_tables.md con referencias legacy congeladas.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2D') !== false
            && strpos($duplicatedDocCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
            && strpos($duplicatedDocCode, 'getProductosDescuentoReservacion') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra neutralizacion Fase 2D.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2D.',
                'Actualizar docs/technical/duplicated_tables.md con decision de preview legacy y helper peligroso.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2E') !== false
            && strpos($duplicatedDocCode, 'compras') !== false
            && strpos($duplicatedDocCode, 'movimientos_inventario') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra decision Fase 2E para compras futuras.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2E.',
                'Actualizar docs/technical/duplicated_tables.md con el contrato de compras y tablas legacy que no deben usarse.'
            );
        }

        if (
            strpos($duplicatedDocCode, 'Actualizacion Fase 2F') !== false
            && strpos($duplicatedDocCode, 'movimientos_caja.proveedor') !== false
            && strpos($duplicatedDocCode, 'proveedores') !== false
        ) {
            hcOk('Documentacion de tablas duplicadas registra alcance Fase 2F de proveedores.');
        } else {
            hcWarning(
                'Documentacion de tablas duplicadas no registra aun Fase 2F.',
                'Actualizar docs/technical/duplicated_tables.md aclarando que proveedores no reconcilia movimientos_caja.proveedor.'
            );
        }
    } elseif ($docsTechnicalDir) {
        hcWarning(
            'No se encontro docs/technical/duplicated_tables.md.',
            'Documentar tablas duplicadas antes de limpiar productos/inventario_movimientos.'
        );
    }

    $productoRoutes = array_filter($routes, static function (array $route): bool {
        return strtolower((string) $route['controller']) === 'producto';
    });
    if (empty($productoRoutes)) {
        hcOk('No hay rutas activas hacia ProductoController legacy.');
    } else {
        hcError(
            'Hay rutas activas hacia ProductoController legacy.',
            'Retirar esas rutas o migrarlas al contrato inventario_productos antes de operar multihotel.'
        );
    }

    $providerExpectedRoutes = [
        ['method' => 'get', 'path' => 'proveedores'],
        ['method' => 'get', 'path' => 'proveedores/crear'],
        ['method' => 'post', 'path' => 'proveedores'],
        ['method' => 'get', 'path' => 'proveedores/1', 'pattern' => true],
        ['method' => 'get', 'path' => 'proveedores/1/editar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/actualizar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/desactivar', 'pattern' => true],
        ['method' => 'post', 'path' => 'proveedores/1/reactivar', 'pattern' => true],
    ];

    $missingProviderRoutes = [];
    foreach ($providerExpectedRoutes as $expectedRoute) {
        $exists = !empty($expectedRoute['pattern'])
            ? hcRoutePatternExists($routes, $expectedRoute['path'], $expectedRoute['method'])
            : hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method']);
        if (!$exists) {
            $missingProviderRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingProviderRoutes)) {
        hcOk('Rutas minimas Fase 3A de proveedores, incluida ficha read-only, estan registradas.');
    } else {
        hcError(
            'Rutas Fase 3A de proveedores faltantes: ' . implode(', ', $missingProviderRoutes),
            'Registrar CRUD minimo y GET /proveedores/{id} read-only antes de probar Proveedores v2.'
        );
    }

    $purchaseExpectedRoutes = [
        ['method' => 'get', 'path' => 'compras'],
        ['method' => 'get', 'path' => 'compras/crear'],
        ['method' => 'get', 'path' => 'compras/reportes/recibidas'],
        ['method' => 'get', 'path' => 'compras/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'compras'],
        ['method' => 'post', 'path' => 'compras/{id:[0-9]+}/recibir'],
    ];
    $missingPurchaseRoutes = [];
    foreach ($purchaseExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingPurchaseRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingPurchaseRoutes)) {
        hcOk('Rutas minimas Fase 2Y de Compras, reporte, detalle y recepcion estan registradas.');
    } else {
        hcError(
            'Rutas Fase 2Y de Compras faltantes: ' . implode(', ', $missingPurchaseRoutes),
            'Registrar solo GET /compras, GET /compras/reportes/recibidas, GET /compras/{id}, GET /compras/crear, POST /compras y POST /compras/{id}/recibir.'
        );
    }

    $cxpExpectedRoutes = [
        ['method' => 'get', 'path' => 'cuentas-por-pagar'],
        ['method' => 'get', 'path' => 'cuentas-por-pagar/generacion-preview'],
        ['method' => 'get', 'path' => 'cuentas-por-pagar/simulador-caja'],
        ['method' => 'post', 'path' => 'cuentas-por-pagar/generar-desde-compra/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'cuentas-por-pagar/{id:[0-9]+}/registrar-pago-caja'],
        ['method' => 'post', 'path' => 'cuentas-por-pagar/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-pago-caja'],
        ['method' => 'get', 'path' => 'cuentas-por-pagar/{id:[0-9]+}'],
    ];
    $missingCxpRoutes = [];
    foreach ($cxpExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingCxpRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingCxpRoutes)) {
        hcOk('Rutas Fase 3B/3C-C/3D/3D-D-A de CxP, preview, simulador, pago y reversion estan registradas.');
    } else {
        hcWarning(
            'Rutas Fase 3B/3C-C/3D/3D-D-A de CxP faltantes: ' . implode(', ', $missingCxpRoutes),
            'Registrar GET listado/preview/simulador-caja/detalle, POST generar, POST pago Caja y POST reversion pago Caja.'
        );
    }

    $workerExpectedRoutes = [
        ['method' => 'get', 'path' => 'trabajadores'],
        ['method' => 'get', 'path' => 'trabajadores/reporte'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/preview'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/reporte'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/pagos-snapshot'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/pagos-snapshot/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/auditoria'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/auditoria/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/expediente'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/expediente/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/periodos/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'trabajadores/nomina/periodos/cerrar'],
        ['method' => 'post', 'path' => 'trabajadores/nomina/periodos/{id:[0-9]+}/aprobar'],
        ['method' => 'post', 'path' => 'trabajadores/nomina/periodos/{id:[0-9]+}/anular'],
        ['method' => 'post', 'path' => 'trabajadores/nomina/periodos/{periodo:[0-9]+}/detalles/{detalle:[0-9]+}/registrar-pago-caja'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/preview'],
        ['method' => 'get', 'path' => 'trabajadores/nomina/preview/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/pagos-caja/reporte'],
        ['method' => 'get', 'path' => 'trabajadores/pagos-caja/reporte/exportar'],
        ['method' => 'get', 'path' => 'trabajadores/pagos-caja/simulador'],
        ['method' => 'get', 'path' => 'trabajadores/crear'],
        ['method' => 'post', 'path' => 'trabajadores'],
        ['method' => 'get', 'path' => 'trabajadores/{id:[0-9]+}/recibo-laboral/pdf'],
        ['method' => 'get', 'path' => 'trabajadores/{id:[0-9]+}/recibo-laboral'],
        ['method' => 'get', 'path' => 'trabajadores/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/registrar-pago-caja'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/pagos-caja/{pagoid:[0-9]+}/revertir'],
        ['method' => 'get', 'path' => 'trabajadores/{id:[0-9]+}/editar'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/actualizar'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/conceptos-laborales'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/anticipos'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/prestamos'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/asistencias'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/baja-logica'],
        ['method' => 'post', 'path' => 'trabajadores/{id:[0-9]+}/reactivar'],
    ];
    $missingWorkerRoutes = [];
    foreach ($workerExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingWorkerRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingWorkerRoutes)) {
        hcOk('Rutas Personal NP-F-A/5E-C/5E-D-A/5E-G-A/5E-H-A/5E-I-A/5E-J-A/5E-K-A/5E-L-A/5E-M-A/5E-N-A/14E/14F registradas: CRUD, reportes, periodos/preview nomina, snapshot persistente, export CSV, recibo/PDF read-only, ledger, simulador Caja, pago, reversion laboral y pago individual snapshot controlado.');
    } else {
        hcWarning(
            'Rutas Personal NP-F-A/5E-C/5E-D-A faltantes: ' . implode(', ', $missingWorkerRoutes),
            'Registrar CRUD basico, GET reportes/preview/export CSV, GET simulador Caja, POST ledger laboral, POST pago y POST reversion laboral controlada.'
        );
    }

    $forbiddenWorkerRoutes = [];
    $allowedWorkerRouteSignatures = [
        'GET /trabajadores -> trabajador::index',
        'GET /trabajadores/reporte -> trabajador::reporte',
        'GET /trabajadores/nomina/periodos -> trabajador::nominaperiodos',
        'GET /trabajadores/nomina/periodos/preview -> trabajador::nominaperiodopreview',
        'GET /trabajadores/nomina/periodos/reporte -> trabajador::reportenominaperiodos',
        'GET /trabajadores/nomina/periodos/exportar -> trabajador::exportarnominaperiodos',
        'GET /trabajadores/nomina/periodos/pagos-snapshot -> trabajador::reportenominapagossnapshot',
        'GET /trabajadores/nomina/periodos/pagos-snapshot/exportar -> trabajador::exportarnominapagossnapshot',
        'GET /trabajadores/nomina/auditoria -> trabajador::auditorianomina',
        'GET /trabajadores/nomina/auditoria/exportar -> trabajador::exportarauditorianomina',
        'GET /trabajadores/nomina/expediente -> trabajador::expedientenomina',
        'GET /trabajadores/nomina/expediente/exportar -> trabajador::exportarexpedientenomina',
        'GET /trabajadores/nomina/periodos/{id:[0-9]+} -> trabajador::nominaperiododetalle',
        'POST /trabajadores/nomina/periodos/cerrar -> trabajador::cerrarnominaperiodo',
        'POST /trabajadores/nomina/periodos/{id:[0-9]+}/aprobar -> trabajador::aprobarnominaperiodo',
        'POST /trabajadores/nomina/periodos/{id:[0-9]+}/anular -> trabajador::anularnominaperiodo',
        'POST /trabajadores/nomina/periodos/{periodo:[0-9]+}/detalles/{detalle:[0-9]+}/registrar-pago-caja -> trabajador::registrarpagosnapshotnomina',
        'GET /trabajadores/nomina/preview -> trabajador::nominapreview',
        'GET /trabajadores/nomina/preview/exportar -> trabajador::exportarnominapreview',
        'GET /trabajadores/pagos-caja/reporte -> trabajador::reportepagoscaja',
        'GET /trabajadores/pagos-caja/reporte/exportar -> trabajador::exportarreportepagoscaja',
        'GET /trabajadores/pagos-caja/simulador -> trabajador::simuladorpagocaja',
        'GET /trabajadores/crear -> trabajador::crear',
        'POST /trabajadores -> trabajador::guardar',
        'GET /trabajadores/{id:[0-9]+}/recibo-laboral/pdf -> trabajador::recibolaboralpdf',
        'GET /trabajadores/{id:[0-9]+}/recibo-laboral -> trabajador::recibolaboral',
        'GET /trabajadores/{id:[0-9]+} -> trabajador::ver',
        'POST /trabajadores/{id:[0-9]+}/registrar-pago-caja -> trabajador::registrarpagocaja',
        'POST /trabajadores/{id:[0-9]+}/pagos-caja/{pagoid:[0-9]+}/revertir -> trabajador::revertirpagocaja',
        'GET /trabajadores/{id:[0-9]+}/editar -> trabajador::editar',
        'POST /trabajadores/{id:[0-9]+}/actualizar -> trabajador::actualizar',
        'POST /trabajadores/{id:[0-9]+}/conceptos-laborales -> trabajador::registrarconceptolaboral',
        'POST /trabajadores/{id:[0-9]+}/anticipos -> trabajador::registraranticipolaboral',
        'POST /trabajadores/{id:[0-9]+}/prestamos -> trabajador::registrarprestamolaboral',
        'POST /trabajadores/{id:[0-9]+}/asistencias -> trabajador::registrarasistencialaboral',
        'POST /trabajadores/{id:[0-9]+}/baja-logica -> trabajador::bajalogica',
        'POST /trabajadores/{id:[0-9]+}/reactivar -> trabajador::reactivar',
    ];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;
        if ((strpos($path, 'trabajadores') !== false || $controller === 'trabajador') && !in_array($signature, $allowedWorkerRouteSignatures, true)) {
            $forbiddenWorkerRoutes[] = $signature;
        }
    }

    if (empty($forbiddenWorkerRoutes)) {
        hcOk('Personal NP-F-A/5E-C/5E-D-A/5E-G-A/5E-H-A/5E-I-A/5E-J-A/5E-K-A/5E-L-A/5E-M-A/14E/14F mantiene solo rutas autorizadas; reportes/periodos/preview/export/recibo/PDF/simulador GET y cierre/aprobacion/anulacion/pago/reversion POST controlados.');
    } else {
        hcError(
            'Personal NP-F-A tiene rutas fuera de alcance: ' . implode(' | ', $forbiddenWorkerRoutes),
            'Retirar rutas que no sean CRUD basico, reportes, simulador Caja, ledger laboral manual, pago o reversion laboral controlada.'
        );
    }

    $taskExpectedRoutes = [
        ['method' => 'get', 'path' => 'tareas'],
        ['method' => 'get', 'path' => 'tareas/reporte'],
        ['method' => 'get', 'path' => 'tareas/agenda'],
        ['method' => 'get', 'path' => 'tareas/crear'],
        ['method' => 'post', 'path' => 'tareas'],
        ['method' => 'post', 'path' => 'tareas/desde-mantenimiento/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'tareas/desde-limpieza/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/asignar'],
        ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/iniciar'],
        ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/completar'],
        ['method' => 'post', 'path' => 'tareas/{id:[0-9]+}/cancelar'],
        ['method' => 'get', 'path' => 'tareas/{id:[0-9]+}'],
    ];
    $missingTaskRoutes = [];
    foreach ($taskExpectedRoutes as $expectedRoute) {
        if (!hcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingTaskRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (empty($missingTaskRoutes)) {
        hcOk('Rutas TLM-I-A/TLM-J-A/MANT-G-B-A/LIM-B-A conservadas: listado, reporte/agenda read-only, formulario, alta, altas contextuales, asignacion, estados manuales y detalle.');
    } else {
        hcWarning(
            'Rutas TLM-I-A faltantes: ' . implode(', ', $missingTaskRoutes),
            'Registrar rutas de listado, reporte, formulario, alta, asignacion, iniciar, completar, cancelar y detalle.'
        );
    }

    $forbiddenTaskRoutes = [];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;
        $allowedTaskRoute = in_array($signature, [
            'GET /tareas -> tarea::index',
            'GET /tareas/reporte -> tarea::reporte',
            'GET /tareas/agenda -> tarea::agenda',
            'GET /tareas/crear -> tarea::crear',
            'POST /tareas -> tarea::guardar',
            'POST /tareas/desde-mantenimiento/{id:[0-9]+} -> tarea::creardesdemantenimiento',
            'POST /tareas/desde-limpieza/{id:[0-9]+} -> tarea::creardesdelimpieza',
            'POST /tareas/{id:[0-9]+}/asignar -> tarea::asignar',
            'POST /tareas/{id:[0-9]+}/iniciar -> tarea::iniciar',
            'POST /tareas/{id:[0-9]+}/completar -> tarea::completar',
            'POST /tareas/{id:[0-9]+}/cancelar -> tarea::cancelar',
            'GET /tareas/{id:[0-9]+} -> tarea::ver',
        ], true);

        if ((strpos($path, 'tareas') !== false || $controller === 'tarea') && !$allowedTaskRoute) {
            $forbiddenTaskRoutes[] = $signature;
        }
    }

    if (empty($forbiddenTaskRoutes)) {
        hcOk('TLM-I-A/TLM-J-A/MANT-G-B-A/LIM-B-A mantiene solo rutas autorizadas; reporte/agenda read-only y POST manual contextual controlado.');
    } else {
        hcError(
            'TLM-I-A tiene rutas fuera de alcance: ' . implode(' | ', $forbiddenTaskRoutes),
            'Retirar rutas de tareas fuera del contrato listado/reporte/form/alta/asignacion/estados/detalle.'
        );
    }

    $forbiddenPurchaseRoutes = [];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;

        $isAllowedPurchaseRoute = in_array($method . ' /' . $path, [
            'GET /compras',
            'GET /compras/crear',
            'GET /compras/reportes/recibidas',
            'GET /compras/{id:[0-9]+}',
            'POST /compras',
            'POST /compras/{id:[0-9]+}/recibir',
        ], true) && $controller === 'compra' && in_array($action, ['index', 'crear', 'reporterecibidas', 'ver', 'guardar', 'recibir'], true);

        $isAllowedCxpReadOnlyRoute = in_array($method . ' /' . $path, [
            'GET /cuentas-por-pagar',
            'GET /cuentas-por-pagar/generacion-preview',
            'GET /cuentas-por-pagar/simulador-caja',
            'POST /cuentas-por-pagar/generar-desde-compra/{id:[0-9]+}',
            'POST /cuentas-por-pagar/{id:[0-9]+}/registrar-pago-caja',
            'POST /cuentas-por-pagar/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-pago-caja',
            'GET /cuentas-por-pagar/{id:[0-9]+}',
        ], true) && $controller === 'cuentaporpagar' && in_array($action, ['index', 'generacionpreview', 'simuladorcaja', 'generardesdecompra', 'registrarpagocaja', 'revertirpagocaja', 'ver'], true);

        if ($isAllowedPurchaseRoute || $isAllowedCxpReadOnlyRoute) {
            continue;
        }

        if (
            strpos($path, 'compras') !== false
            || strpos($path, 'cuentas-por-pagar') !== false
            || strpos($path, 'proveedor-contactos') !== false
            || strpos($path, 'documentos-proveedor') !== false
            || strpos($controller, 'compra') !== false
            || strpos($controller, 'cuentaporpagar') !== false
        ) {
            $forbiddenPurchaseRoutes[] = $signature;
        }
    }

    if (empty($forbiddenPurchaseRoutes)) {
        hcOk('Solo hay compras minimas y CxP controlada; compras no expone pagos, contactos ni documentos.');
    } else {
        hcError(
            'Rutas fuera del alcance Fase 3C-C detectadas: ' . implode(' | ', $forbiddenPurchaseRoutes),
            'Retirar rutas que no sean Compras basicas, CxP listado/preview/detalle, POST generar CxP, POST pago Caja de CxP y POST /compras/{id}/recibir.'
        );
    }

    $providerModelPath = $appRoot . '/app/models/Proveedor.php';
    $providerControllerPath = $controllersDir . '/ProveedorController.php';
    if (is_file($providerModelPath) && is_file($providerControllerPath)) {
        $providerModelCode = (string) file_get_contents($providerModelPath);
        $providerControllerCode = (string) file_get_contents($providerControllerPath);

        if (
            strpos($providerModelCode, "protected \$table = 'proveedores'") !== false
            && strpos($providerModelCode, 'hotel_id') !== false
            && preg_match('/WHERE\s+id\s*=\s*\?\s+AND\s+hotel_id\s*=\s*\?/i', $providerModelCode)
            && preg_match('/UPDATE\s+proveedores.*WHERE\s+id\s*=\s*\?\s+AND\s+hotel_id\s*=\s*\?/is', $providerModelCode)
        ) {
            hcOk('Proveedor model usa proveedores con aislamiento hotel_id.');
        } else {
            hcError(
                'Proveedor model no muestra aislamiento hotel_id completo.',
                'No usar catalogo proveedores hasta filtrar lecturas/escrituras por hotel_id.'
            );
        }

        if (
            strpos($providerModelCode, 'validarDuplicadoPorHotel') !== false
            && strpos($providerModelCode, 'buscarDuplicado') !== false
            && strpos($providerModelCode, 'rfc') !== false
            && strpos($providerModelCode, 'nombre') !== false
            && strpos($providerModelCode, 'activo = 1') !== false
        ) {
            hcOk('Proveedor model valida duplicados por RFC y nombre activo en Fase 2I.');
        } else {
            hcWarning(
                'Proveedor model no muestra validacion de duplicados Fase 2I.',
                'Validar RFC por hotel y nombre activo por hotel antes de construir compras.'
            );
        }

        $providerForbiddenCode = $providerModelCode . "\n" . $providerControllerCode;
        $hasForbiddenProviderScope = strpos($providerForbiddenCode, 'movimientos_caja') !== false
            || strpos($providerForbiddenCode, 'cuentas_por_pagar') !== false
            || strpos($providerForbiddenCode, 'compra_pagos') !== false
            || strpos($providerForbiddenCode, 'documentos_proveedor') !== false
            || preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(compras|compra_detalles|inventario_productos|movimientos_inventario|movimientos_caja|cuentas_por_pagar)\b/i', $providerForbiddenCode);

        if (!$hasForbiddenProviderScope) {
            hcOk('Proveedor Fase 3A no toca Caja, CxP, pagos ni escrituras de compras/inventario.');
        } else {
            hcError(
                'Proveedor Fase 3A contiene referencias fuera de alcance o escrituras prohibidas.',
                'Mantener Proveedores v2 en lectura de compras; sin Caja, CxP, pagos ni escrituras de compras/inventario.'
            );
        }

        if (
            strpos($providerControllerCode, 'function verAction') !== false
            && strpos($providerControllerCode, 'proveedores/ver') !== false
            && strpos($providerControllerCode, 'comprasDisponibles') !== false
            && strpos($providerControllerCode, 'resumenComprasPorProveedor') !== false
            && strpos($providerControllerCode, 'comprasRecientesPorProveedor') !== false
            && strpos($providerModelCode, 'function comprasDisponibles') !== false
            && strpos($providerModelCode, 'function resumenComprasPorProveedor') !== false
            && strpos($providerModelCode, 'function comprasRecientesPorProveedor') !== false
            && strpos($providerModelCode, 'FROM compras') !== false
            && strpos($providerModelCode, 'JOIN compra_detalles') !== false
        ) {
            hcOk('Proveedor Fase 3A expone ficha read-only e historial de compras por hotel.');
        } else {
            hcWarning(
                'Proveedor Fase 3A no muestra ficha read-only completa.',
                'Agregar verAction, proveedores/ver.php y metodos read-only resumenComprasPorProveedor/comprasRecientesPorProveedor.'
            );
        }

        if (
            strpos($providerControllerCode, 'AuditService::record') !== false
            && strpos($providerControllerCode, 'proveedores.creado') !== false
            && strpos($providerControllerCode, 'proveedores.actualizado') !== false
            && strpos($providerControllerCode, 'proveedores.desactivado') !== false
            && strpos($providerControllerCode, 'proveedores.reactivado') !== false
        ) {
            hcOk('ProveedorController integra auditoria minima Fase 2F.');
        } else {
            hcWarning(
                'ProveedorController no muestra auditoria minima completa Fase 2F.',
                'Registrar altas, cambios, desactivaciones y reactivaciones en logs_auditoria si la tabla existe.'
            );
        }

        if (
            preg_match('/function\s+before\s*\([^)]*\).*?require_hotel_module\s*\(\s*[\'"]inventario[\'"]\s*\)/s', $providerControllerCode)
        ) {
            hcOk('ProveedorController requiere modulo inventario para acceso directo Fase 2H.');
        } else {
            hcError(
                'ProveedorController no valida modulo inventario.',
                'Proteger /proveedores con require_hotel_module("inventario") para alinear acceso directo con el sidebar.'
            );
        }

        $providerIndexViewPath = $appRoot . '/app/views/proveedores/index.php';
        if (is_file($providerIndexViewPath) && $providerDetailViewFile && is_file($providerDetailViewFile)) {
            $providerIndexViewCode = (string) file_get_contents($providerIndexViewPath);
            $providerDetailViewCode = (string) file_get_contents($providerDetailViewFile);
            $providerIndexHasDetailLink = strpos($providerIndexViewCode, "url('proveedores/' . (int)\$proveedor['id'])") !== false
                || strpos($providerIndexViewCode, "url('proveedores/' . \$provId)") !== false;
            $providerDetailHasReadonlyBadge = strpos($providerDetailViewCode, 'Solo lectura') !== false
                || strpos($providerDetailViewCode, 'Solo consulta') !== false;
            $providerDetailHasReportLink = strpos($providerDetailViewCode, "url('compras/reportes/recibidas?proveedor_id='") !== false;
            if (
                $providerIndexHasDetailLink
                && strpos($providerDetailViewCode, 'Compras recientes') !== false
                && $providerDetailHasReadonlyBadge
                && strpos($providerDetailViewCode, "url('compras/' . (int)") !== false
                && $providerDetailHasReportLink
                && strpos($providerDetailViewCode, '<form') === false
                && strpos($providerDetailViewCode, 'csrf_field()') === false
            ) {
                hcOk('Vistas de Proveedores Fase 3A enlazan ficha read-only e historial sin formularios nuevos.');
            } else {
                hcWarning(
                    'Vistas de Proveedores Fase 3A incompletas.',
                    'Asegurar enlace GET /proveedores/{id}, vista read-only sin forms y links a compras/reporte.'
                );
            }
        } else {
            hcWarning(
                'No se encontro vista de detalle de Proveedores Fase 3A.',
                'Crear app/views/proveedores/ver.php para la ficha read-only.'
            );
        }
    } else {
        hcError(
            'Faltan archivos de Proveedor Fase 2F.',
            'Crear src/app/models/Proveedor.php y src/app/controllers/ProveedorController.php para las rutas registradas.'
        );
    }

    $workerModelPath = $appRoot . '/app/models/Trabajador.php';
    $workerControllerPath = $controllersDir . '/TrabajadorController.php';
    $workerIndexViewPath = $appRoot . '/app/views/trabajadores/index.php';
    $workerDetailViewPath = $appRoot . '/app/views/trabajadores/ver.php';
    $workerCashSimulatorViewPath = $appRoot . '/app/views/trabajadores/simulador_pago_caja.php';
    $workerPayrollPeriodDetailViewPath = $appRoot . '/app/views/trabajadores/nomina_periodo_detalle.php';
    $workerPayrollPeriodsReportViewPath = $appRoot . '/app/views/trabajadores/nomina_periodos_reporte.php';
    $workerPayrollSnapshotPaymentsReportViewPath = $appRoot . '/app/views/trabajadores/nomina_pagos_snapshot_reporte.php';
    $workerPayrollAuditViewPath = $appRoot . '/app/views/trabajadores/nomina_auditoria_consolidada.php';
    $workerPayrollExpedientViewPath = $appRoot . '/app/views/trabajadores/nomina_expediente_administrativo.php';

    if (is_file($workerModelPath) && is_file($workerControllerPath)) {
        $workerModelCode = (string) file_get_contents($workerModelPath);
        $workerControllerCode = (string) file_get_contents($workerControllerPath);
        $workerCode = $workerModelCode . "\n" . $workerControllerCode;

        if (
            strpos($workerModelCode, "protected \$table = 'trabajadores'") !== false
            && strpos($workerModelCode, 'function listarPorHotel') !== false
            && strpos($workerModelCode, 'function reporteReadOnlyPorHotel') !== false
            && strpos($workerModelCode, 'function buscarPorIdHotel') !== false
            && strpos($workerModelCode, 'function resumenLedgerPorTrabajador') !== false
            && strpos($workerModelCode, 'function conceptosLaboralesPorTrabajador') !== false
            && strpos($workerModelCode, 'function anticiposPorTrabajador') !== false
            && strpos($workerModelCode, 'function prestamosPorTrabajador') !== false
            && strpos($workerModelCode, 'function registrarConceptoLaboralParaHotel') !== false
            && strpos($workerModelCode, 'function registrarAnticipoLaboralParaHotel') !== false
            && strpos($workerModelCode, 'function registrarPrestamoLaboralParaHotel') !== false
            && strpos($workerModelCode, 'function registrarAsistenciaLaboralParaHotel') !== false
            && strpos($workerModelCode, 'function asistenciaLaboralPorIdHotel') !== false
            && strpos($workerModelCode, 'function validarConceptoLaboral') !== false
            && strpos($workerModelCode, 'function validarAsistenciaLaboral') !== false
            && strpos($workerModelCode, 'saldo_informativo') !== false
            && strpos($workerModelCode, "INSERT INTO trabajador_pagos") !== false
            && strpos($workerModelCode, "INSERT INTO trabajador_anticipos") !== false
            && strpos($workerModelCode, "INSERT INTO trabajador_prestamos") !== false
            && strpos($workerModelCode, "INSERT INTO trabajador_asistencias") !== false
            && preg_match('/WHERE\s+t\.id\s*=\s*\?\s+AND\s+t\.hotel_id\s*=\s*\?/i', $workerModelCode)
            && strpos($workerModelCode, 'storage_path') === false
            && strpos($workerModelCode, 'ruta_archivo') === false
        ) {
            hcOk('Trabajador model NP-F-A consulta reporte read-only y ledger con aislamiento hotel_id.');
        } else {
            hcWarning(
                'Trabajador model NP-F-A no muestra contrato completo.',
                'Usar reporte/trabajadores/ledger con hotel_id, movimientos laborales manuales validados y no exponer storage_path/ruta_archivo.'
            );
        }

        $workerForbiddenWrite = preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(trabajador_documentos|movimientos_caja|cajas|cortes_caja)\b/i', $workerCode)
            || preg_match('/\b(UPDATE|DELETE\s+FROM)\s+(trabajador_pagos|trabajador_anticipos|trabajador_prestamos|trabajador_asistencias)\b/i', $workerCode)
            || preg_match('/\bDELETE\s+FROM\s+trabajadores\b/i', $workerCode);

        if (!$workerForbiddenWrite) {
            hcOk('Personal NP-F-A/5E-C/5E-D-A mantiene modelo/controlador sin escrituras Caja directas; pago real vive en servicio.');
        } else {
            hcError(
                'Personal NP-F-A contiene escrituras o referencias fuera de alcance.',
                'Permitir solo INSERT/UPDATE en trabajadores e INSERT controlado en ledger laboral dentro del modelo/controlador; escrituras Caja solo en TrabajadorPagoCajaService.'
            );
        }

        $workerSimulatorModelBody = hcMethodBody($workerModelCode, 'simuladorPagoCajaPorHotel');
        $workerSimulatorControllerBody = hcMethodBody($workerControllerCode, 'simuladorPagoCajaAction');
        if (
            $workerSimulatorModelBody !== ''
            && $workerSimulatorControllerBody !== ''
            && hcCodeBodyIsReadOnly($workerSimulatorModelBody)
            && hcCodeBodyIsReadOnly($workerSimulatorControllerBody)
            && strpos($workerModelCode, 'function tablasSimuladorPagoCajaDisponibles') !== false
            && strpos($workerModelCode, 'function corteAbiertoSimuladorPagoCaja') !== false
            && strpos($workerModelCode, 'function pagosCajaResumenPorTrabajador') !== false
            && strpos($workerModelCode, 'function referenciaDuplicadaPagoLaboralCaja') !== false
            && strpos($workerModelCode, 'function referenciaDuplicadaMovimientoCaja') !== false
            && strpos($workerControllerCode, 'trabajadores/simulador_pago_caja') !== false
            && strpos($workerControllerCode, 'validateCSRF') !== false
            && strpos($workerControllerCode, 'pagoCajaService->registrarPago') !== false
            && strpos($workerControllerCode, 'consumirPagoCajaToken') !== false
            && strpos($workerControllerCode, "require_hotel_module('caja')") !== false
        ) {
            hcOk('Personal 5E-C-A expone simulador de pago laboral con Caja en modo read-only.');
        } else {
            hcWarning(
                'Personal 5E-C-A no muestra simulador read-only completo.',
                'Validar ruta GET, controlador sin POST, modelo solo lectura y diagnostico con corte abierto/referencia.'
            );
        }

        $workerPayrollReportViewCode = is_file($workerPayrollPeriodsReportViewPath)
            ? (string) file_get_contents($workerPayrollPeriodsReportViewPath)
            : '';
        $workerPayrollReportModelBody = hcMethodBody($workerModelCode, 'reporteNominaPeriodosPersistentesPorHotel');
        $workerPayrollReportControllerBody = hcMethodBody($workerControllerCode, 'reporteNominaPeriodosAction');
        $workerPayrollExportControllerBody = hcMethodBody($workerControllerCode, 'exportarNominaPeriodosAction');
        $workerPayrollCsvControllerBody = hcMethodBody($workerControllerCode, 'descargarNominaPeriodosCsv');
        $workerPayrollReportSurface = $workerPayrollReportControllerBody
            . "\n" . $workerPayrollExportControllerBody
            . "\n" . $workerPayrollCsvControllerBody
            . "\n" . $workerPayrollReportViewCode;
        if (
            $workerPayrollReportViewCode !== ''
            && $workerPayrollReportModelBody !== ''
            && hcCodeBodyIsReadOnly($workerPayrollReportModelBody)
            && hcCodeBodyIsReadOnly($workerPayrollReportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollExportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollReportSurface)
            && strpos($workerModelCode, 'function tablasReporteNominaPeriodosDisponibles') !== false
            && strpos($workerModelCode, 'function normalizarFiltrosReporteNominaPeriodos') !== false
            && strpos($workerModelCode, 'trabajador_nomina_periodos') !== false
            && strpos($workerModelCode, 'trabajador_nomina_periodo_detalles') !== false
            && strpos($workerModelCode, 'trabajador_nomina_periodo_eventos') !== false
            && strpos($workerControllerCode, 'function reporteNominaPeriodosAction') !== false
            && strpos($workerControllerCode, 'function exportarNominaPeriodosAction') !== false
            && strpos($workerControllerCode, 'function descargarNominaPeriodosCsv') !== false
            && strpos($workerControllerCode, 'trabajadores/nomina_periodos_reporte') !== false
            && strpos($workerControllerCode, 'fputcsv') !== false
            && strpos($workerControllerCode, 'X-Content-Type-Options: nosniff') !== false
            && strpos($workerPayrollReportViewCode, "action=\"<?= url('trabajadores/nomina/periodos/reporte') ?>\"") !== false
            && strpos($workerPayrollReportViewCode, 'trabajadores/nomina/periodos/exportar') !== false
            && strpos($workerPayrollReportViewCode, 'Exportar CSV') !== false
            && strpos($workerPayrollReportViewCode, 'Snapshots persistentes') !== false
            && strpos($workerPayrollReportViewCode, 'method="GET"') !== false
            && strpos($workerPayrollReportViewCode, 'method="POST"') === false
            && strpos($workerPayrollReportViewCode, 'csrf_field()') === false
            && strpos($workerPayrollReportViewCode, 'Solo GET') !== false
            && strpos($workerPayrollReportViewCode, 'registrar-pago-caja') === false
            && strpos($workerPayrollReportViewCode, 'timbrar') === false
            && strpos($workerPayrollReportViewCode, 'dispersion') === false
        ) {
            hcOk('Personal 5E-M-A expone reporte/export CSV de snapshots de pre-nomina en modo GET/read-only, sin storage, pagos ni Caja.');
        } else {
            hcWarning(
                'Personal 5E-M-A no muestra reporte/export de snapshots completo.',
                'Validar rutas GET, controlador/modelo solo lectura, CSV en memoria y vista sin POST/CSRF/pagos.'
            );
        }

        $workerPayrollSnapshotPaymentsReportViewCode = is_file($workerPayrollSnapshotPaymentsReportViewPath)
            ? (string) file_get_contents($workerPayrollSnapshotPaymentsReportViewPath)
            : '';
        $workerPayrollSnapshotPaymentsReportModelBody = hcMethodBody($workerModelCode, 'reporteNominaPagosSnapshotPorHotel');
        $workerPayrollSnapshotPaymentsReportControllerBody = hcMethodBody($workerControllerCode, 'reporteNominaPagosSnapshotAction');
        $workerPayrollSnapshotPaymentsExportControllerBody = hcMethodBody($workerControllerCode, 'exportarNominaPagosSnapshotAction');
        $workerPayrollSnapshotPaymentsReportSurface = $workerPayrollSnapshotPaymentsReportControllerBody
            . "\n" . $workerPayrollSnapshotPaymentsExportControllerBody
            . "\n" . $workerPayrollSnapshotPaymentsReportViewCode;
        if (
            $workerPayrollSnapshotPaymentsReportViewCode !== ''
            && $workerPayrollSnapshotPaymentsReportModelBody !== ''
            && hcCodeBodyIsReadOnly($workerPayrollSnapshotPaymentsReportModelBody)
            && hcCodeBodyIsReadOnly($workerPayrollSnapshotPaymentsReportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollSnapshotPaymentsExportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollSnapshotPaymentsReportSurface)
            && strpos($workerModelCode, 'function tablasReporteNominaPagosSnapshotDisponibles') !== false
            && strpos($workerModelCode, 'function condicionInconsistenciaPagosSnapshot') !== false
            && strpos($workerModelCode, 'nomina_periodo_id IS NOT NULL') !== false
            && strpos($workerModelCode, 'nomina_periodo_detalle_id IS NOT NULL') !== false
            && strpos($workerControllerCode, 'function reporteNominaPagosSnapshotAction') !== false
            && strpos($workerControllerCode, 'function exportarNominaPagosSnapshotAction') !== false
            && strpos($workerControllerCode, 'function descargarNominaPagosSnapshotCsv') !== false
            && strpos($workerControllerCode, 'trabajadores/nomina_pagos_snapshot_reporte') !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, "action=\"<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>\"") !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'trabajadores/nomina/periodos/pagos-snapshot/exportar') !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'Exportar CSV') !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'Solo GET') !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'method="GET"') !== false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'method="POST"') === false
            && strpos($workerPayrollSnapshotPaymentsReportViewCode, 'csrf_field()') === false
        ) {
            hcOk('Personal 5E-Q-A expone conciliacion GET/read-only de pagos trazados a snapshot, detalle y Caja.');
        } else {
            hcWarning(
                'Personal 5E-Q-A no muestra conciliacion read-only completa.',
                'Validar rutas GET, controlador/modelo solo lectura, CSV en memoria y vista sin POST/CSRF.'
            );
        }

        $workerPayrollAuditViewCode = is_file($workerPayrollAuditViewPath)
            ? (string) file_get_contents($workerPayrollAuditViewPath)
            : '';
        $workerPayrollAuditModelBody = hcMethodBody($workerModelCode, 'auditoriaNominaConsolidadaPorHotel');
        $workerPayrollAuditControllerBody = hcMethodBody($workerControllerCode, 'auditoriaNominaAction');
        $workerPayrollAuditExportControllerBody = hcMethodBody($workerControllerCode, 'exportarAuditoriaNominaAction');
        $workerPayrollAuditSurface = $workerPayrollAuditControllerBody
            . "\n" . $workerPayrollAuditExportControllerBody
            . "\n" . $workerPayrollAuditViewCode;
        if (
            $workerPayrollAuditViewCode !== ''
            && $workerPayrollAuditModelBody !== ''
            && hcCodeBodyIsReadOnly($workerPayrollAuditModelBody)
            && hcCodeBodyIsReadOnly($workerPayrollAuditControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollAuditExportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollAuditSurface)
            && strpos($workerModelCode, 'function tablasAuditoriaNominaConsolidadaDisponibles') !== false
            && strpos($workerModelCode, 'function auditoriaNominaConsolidadaPorHotel') !== false
            && strpos($workerModelCode, 'function normalizarFiltrosAuditoriaNomina') !== false
            && strpos($workerModelCode, 'saldo_auditoria') !== false
            && strpos($workerModelCode, 'trabajador_nomina_periodo_detalles') !== false
            && strpos($workerModelCode, 'trabajador_pagos_caja') !== false
            && strpos($workerControllerCode, 'function auditoriaNominaAction') !== false
            && strpos($workerControllerCode, 'function exportarAuditoriaNominaAction') !== false
            && strpos($workerControllerCode, 'function descargarAuditoriaNominaCsv') !== false
            && strpos($workerControllerCode, 'trabajadores/nomina_auditoria_consolidada') !== false
            && strpos($workerPayrollAuditViewCode, "action=\"<?= url('trabajadores/nomina/auditoria') ?>\"") !== false
            && strpos($workerPayrollAuditViewCode, 'trabajadores/nomina/auditoria/exportar') !== false
            && strpos($workerPayrollAuditViewCode, 'Exportar CSV') !== false
            && strpos($workerPayrollAuditViewCode, 'GET / read-only') !== false
            && (strpos($workerPayrollAuditViewCode, 'method="GET"') !== false || strpos($workerPayrollAuditViewCode, 'method="get"') !== false)
            && strpos($workerPayrollAuditViewCode, 'method="POST"') === false
            && strpos($workerPayrollAuditViewCode, 'method="post"') === false
            && strpos($workerPayrollAuditViewCode, 'csrf_field()') === false
            && strpos($workerPayrollAuditViewCode, 'registrar-pago-caja') === false
            && strpos($workerPayrollAuditViewCode, 'Registrar pago') === false
        ) {
            hcOk('Personal 5E-R-A expone auditoria consolidada GET/read-only de snapshots, pagos Caja y saldo de auditoria.');
        } else {
            hcWarning(
                'Personal 5E-R-A no muestra auditoria consolidada read-only completa.',
                'Validar rutas GET, controlador/modelo solo lectura, CSV en memoria y vista sin POST/CSRF/acciones operativas.'
            );
        }

        $workerPayrollExpedientViewCode = is_file($workerPayrollExpedientViewPath)
            ? (string) file_get_contents($workerPayrollExpedientViewPath)
            : '';
        $workerPayrollExpedientModelBody = hcMethodBody($workerModelCode, 'expedienteNominaAdministrativoPorHotel');
        $workerPayrollExpedientControllerBody = hcMethodBody($workerControllerCode, 'expedienteNominaAction');
        $workerPayrollExpedientExportControllerBody = hcMethodBody($workerControllerCode, 'exportarExpedienteNominaAction');
        $workerPayrollExpedientSurface = $workerPayrollExpedientControllerBody
            . "\n" . $workerPayrollExpedientExportControllerBody
            . "\n" . $workerPayrollExpedientViewCode;
        if (
            $workerPayrollExpedientViewCode !== ''
            && $workerPayrollExpedientModelBody !== ''
            && hcCodeBodyIsReadOnly($workerPayrollExpedientModelBody)
            && hcCodeBodyIsReadOnly($workerPayrollExpedientControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollExpedientExportControllerBody)
            && hcCodeBodyIsReadOnly($workerPayrollExpedientSurface)
            && strpos($workerModelCode, 'function tablasExpedienteNominaAdministrativoDisponibles') !== false
            && strpos($workerModelCode, 'function expedienteNominaAdministrativoPorHotel') !== false
            && strpos($workerModelCode, 'function normalizarFiltrosExpedienteNomina') !== false
            && strpos($workerModelCode, 'function evaluarExpedienteNominaRegistro') !== false
            && strpos($workerModelCode, 'estado_expediente') !== false
            && strpos($workerModelCode, 'bloqueosExpedienteNomina') !== false
            && strpos($workerModelCode, 'auditoriaNominaConsolidadaPorHotel') !== false
            && strpos($workerControllerCode, 'function expedienteNominaAction') !== false
            && strpos($workerControllerCode, 'function exportarExpedienteNominaAction') !== false
            && strpos($workerControllerCode, 'function descargarExpedienteNominaCsv') !== false
            && strpos($workerControllerCode, 'trabajadores/nomina_expediente_administrativo') !== false
            && strpos($workerPayrollExpedientViewCode, "action=\"<?= url('trabajadores/nomina/expediente') ?>\"") !== false
            && strpos($workerPayrollExpedientViewCode, 'trabajadores/nomina/expediente/exportar') !== false
            && strpos($workerPayrollExpedientViewCode, 'Exportar CSV') !== false
            && strpos($workerPayrollExpedientViewCode, 'GET / read-only') !== false
            && strpos($workerPayrollExpedientViewCode, 'method="GET"') !== false
            && strpos($workerPayrollExpedientViewCode, 'method="POST"') === false
            && strpos($workerPayrollExpedientViewCode, 'method="post"') === false
            && strpos($workerPayrollExpedientViewCode, 'csrf_field()') === false
            && strpos($workerPayrollExpedientViewCode, 'registrar-pago-caja') === false
            && strpos($workerPayrollExpedientViewCode, 'Registrar pago') === false
        ) {
            hcOk('Personal 5E-S-A expone expediente administrativo GET/read-only de nomina con bloqueos y CSV en memoria.');
        } else {
            hcWarning(
                'Personal 5E-S-A no muestra expediente administrativo read-only completo.',
                'Validar rutas GET, controlador/modelo solo lectura, CSV en memoria y vista sin POST/CSRF/acciones operativas.'
            );
        }

        $workerPayrollPeriodDetailViewCode = is_file($workerPayrollPeriodDetailViewPath)
            ? (string) file_get_contents($workerPayrollPeriodDetailViewPath)
            : '';
        $workerSnapshotPaymentControllerBody = hcMethodBody($workerControllerCode, 'registrarPagoSnapshotNominaAction');
        if (
            $workerPayrollPeriodDetailViewCode !== ''
            && $workerSnapshotPaymentControllerBody !== ''
            && strpos($workerControllerCode, 'function registrarPagoSnapshotNominaAction') !== false
            && strpos($workerSnapshotPaymentControllerBody, "require_hotel_module('caja')") !== false
            && strpos($workerSnapshotPaymentControllerBody, "requireWritePermission('usuarios.edit')") !== false
            && strpos($workerSnapshotPaymentControllerBody, 'validateCSRF()') !== false
            && strpos($workerSnapshotPaymentControllerBody, "consumirNominaPeriodoToken('pago_snapshot_caja'") !== false
            && strpos($workerSnapshotPaymentControllerBody, 'registrarPagoDesdeSnapshot') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'pagoSnapshotTokens') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'name="pago_token"') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'csrf_field()') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'registrar-pago-caja') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'Registrar pago') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'Requiere aprobacion') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'Pago individual controlado') !== false
            && strpos($workerPayrollPeriodDetailViewCode, 'pago masivo') === false
        ) {
            hcOk('Personal 5E-N-A expone pago individual desde snapshot aprobado con CSRF, token y sin pago masivo/timbrado.');
        } else {
            hcWarning(
                'Personal 5E-N-A no muestra interfaz/controlador completo.',
                'Validar controlador POST con Caja, permiso, CSRF, token pago_snapshot_caja y vista con pago individual por detalle aprobado.'
            );
        }

        if (
            strpos($workerControllerCode, 'function indexAction') !== false
            && strpos($workerControllerCode, 'function reporteAction') !== false
            && strpos($workerControllerCode, 'function verAction') !== false
            && strpos($workerControllerCode, 'function crearAction') !== false
            && strpos($workerControllerCode, 'function guardarAction') !== false
            && strpos($workerControllerCode, 'function editarAction') !== false
            && strpos($workerControllerCode, 'function actualizarAction') !== false
            && strpos($workerControllerCode, 'function bajaLogicaAction') !== false
            && strpos($workerControllerCode, 'function reactivarAction') !== false
            && strpos($workerControllerCode, 'function registrarConceptoLaboralAction') !== false
            && strpos($workerControllerCode, 'function registrarAnticipoLaboralAction') !== false
            && strpos($workerControllerCode, 'function registrarPrestamoLaboralAction') !== false
            && strpos($workerControllerCode, 'function registrarAsistenciaLaboralAction') !== false
            && strpos($workerControllerCode, 'function registrarPagoCajaAction') !== false
            && strpos($workerControllerCode, 'conceptosLaboralesPorTrabajador') !== false
            && strpos($workerControllerCode, 'registrarConceptoLaboralParaHotel') !== false
            && strpos($workerControllerCode, 'registrarAnticipoLaboralParaHotel') !== false
            && strpos($workerControllerCode, 'registrarPrestamoLaboralParaHotel') !== false
            && strpos($workerControllerCode, 'registrarAsistenciaLaboralParaHotel') !== false
            && strpos($workerControllerCode, 'anticiposPorTrabajador') !== false
            && strpos($workerControllerCode, 'prestamosPorTrabajador') !== false
            && strpos($workerControllerCode, 'ultimosMovimientosPorTrabajador') !== false
            && strpos($workerControllerCode, "require_hotel_module('usuarios')") !== false
            && strpos($workerControllerCode, "require_permission('usuarios.view')") !== false
            && (
                strpos($workerControllerCode, "require_permission('usuarios.create')") !== false
                || strpos($workerControllerCode, "requireWritePermission('usuarios.create')") !== false
            )
            && (
                strpos($workerControllerCode, "require_permission('usuarios.edit')") !== false
                || strpos($workerControllerCode, "requireWritePermission('usuarios.edit')") !== false
            )
            && strpos($workerControllerCode, 'validateCSRF') !== false
            && strpos($workerControllerCode, 'pagoCajaService->registrarPago') !== false
            && strpos($workerControllerCode, 'consumirPagoCajaToken') !== false
            && strpos($workerControllerCode, "require_hotel_module('caja')") !== false
            && strpos($workerControllerCode, 'AuditService::record') !== false
            && strpos($workerControllerCode, 'trabajadores/index') !== false
            && strpos($workerControllerCode, 'trabajadores/reporte') !== false
            && strpos($workerControllerCode, 'trabajadores/ver') !== false
            && strpos($workerControllerCode, 'trabajadores/form') !== false
        ) {
            hcOk('TrabajadorController NP-F-A/5E-D-A expone CRUD, reporte, ledger y pago laboral Caja con permisos, CSRF, token y auditoria.');
        } else {
            hcWarning(
                'TrabajadorController NP-F-A/5E-D-A no muestra guardas o acciones completas.',
                'Validar requireAuth, hotel, permisos, Caja, CSRF, token, auditoria y acciones CRUD/reporte/ledger/pago laboral.'
            );
        }
    } else {
        hcWarning(
            'Faltan archivos Personal NP-A read-only.',
            'Crear Trabajador.php y TrabajadorController.php solo si la subfase UI read-only esta autorizada.'
        );
    }

    $workerFormViewPath = $appRoot . '/app/views/trabajadores/form.php';
    $workerReportViewPath = $appRoot . '/app/views/trabajadores/reporte.php';
    if (is_file($workerIndexViewPath) && is_file($workerDetailViewPath) && is_file($workerFormViewPath) && is_file($workerReportViewPath)) {
        $workerIndexViewCode = (string) file_get_contents($workerIndexViewPath);
        $workerDetailViewCode = (string) file_get_contents($workerDetailViewPath);
        $workerFormViewCode = (string) file_get_contents($workerFormViewPath);
        $workerReportViewCode = (string) file_get_contents($workerReportViewPath);
        $workerViewsCode = $workerIndexViewCode . "\n" . $workerDetailViewCode . "\n" . $workerFormViewCode . "\n" . $workerReportViewCode;
        $workerIndexHasDetailLink = strpos($workerIndexViewCode, "url('trabajadores/' . (int)") !== false
            || (
                strpos($workerIndexViewCode, "\$tUrl = url('trabajadores/' . \$tId)") !== false
                && strpos($workerIndexViewCode, 'href="<?= $tUrl ?>"') !== false
            );

        if (
            strpos($workerIndexViewCode, "action=\"<?= url('trabajadores') ?>\"") !== false
            && strpos($workerIndexViewCode, 'method="GET"') !== false
            && strpos($workerIndexViewCode, "url('trabajadores/reporte')") !== false
            && strpos($workerIndexViewCode, "url('trabajadores/pagos-caja/simulador')") !== false
            && $workerIndexHasDetailLink
            && strpos($workerIndexViewCode, '/baja-logica') !== false
            && strpos($workerIndexViewCode, '/reactivar') !== false
            && strpos($workerDetailViewCode, 'Captura manual') !== false
            && strpos($workerDetailViewCode, 'trabajadores/pagos-caja/simulador?trabajador_id=') !== false
            && strpos($workerDetailViewCode, '/baja-logica') !== false
            && strpos($workerDetailViewCode, '/reactivar') !== false
            && strpos($workerDetailViewCode, "url('trabajadores')") !== false
            && strpos($workerFormViewCode, 'method="POST"') !== false
            && strpos($workerFormViewCode, 'name="nombre_completo"') !== false
            && strpos($workerFormViewCode, 'csrf_field()') !== false
            && (
                strpos($workerDetailViewCode, 'Ledger laboral') !== false
                || (
                    strpos($workerDetailViewCode, 'Cuenta del trabajador') !== false
                    && strpos($workerDetailViewCode, 'wk-ledger-grid') !== false
                )
            )
            && (
                strpos($workerDetailViewCode, 'Saldo informativo') !== false
                || strpos($workerDetailViewCode, '$ledgerSaldoInformativo') !== false
            )
            && strpos($workerDetailViewCode, '/conceptos-laborales') !== false
            && strpos($workerDetailViewCode, '/anticipos') !== false
            && strpos($workerDetailViewCode, '/prestamos') !== false
            && strpos($workerDetailViewCode, '/asistencias') !== false
            && strpos($workerDetailViewCode, 'name="tipo"') !== false
            && strpos($workerDetailViewCode, 'name="monto"') !== false
            && strpos($workerDetailViewCode, 'name="hora_entrada"') !== false
            && (
                strpos($workerDetailViewCode, 'Pago laboral con Caja') !== false
                || strpos($workerDetailViewCode, 'Pagar al trabajador (desde Caja)') !== false
            )
            && strpos($workerDetailViewCode, 'registrar-pago-caja') !== false
            && strpos($workerDetailViewCode, 'name="pago_token"') !== false
            && strpos($workerReportViewCode, 'Reporte de Personal') !== false
            && strpos($workerReportViewCode, 'read-only') !== false
            && strpos($workerReportViewCode, 'No genera nomina') !== false
            && strpos($workerReportViewCode, 'method="POST"') === false
            && strpos($workerReportViewCode, 'csrf_field()') === false
            && substr_count($workerViewsCode, 'csrf_field()') >= 3
            && strpos($workerViewsCode, 'ruta_archivo') === false
            && strpos($workerViewsCode, 'movimientos_caja') === false
            && strpos($workerViewsCode, 'cuentas_por_pagar') === false
        ) {
            hcOk('Vistas Personal NP-F-A/5E-D-A muestran CRUD, reporte, ledger manual y panel de pago laboral con CSRF/token.');
        } else {
            hcWarning(
                'Vistas Personal NP-F-A/5E-D-A no muestran contrato completo.',
                'Asegurar filtros GET, reporte sin POST, formularios POST+CSRF, ledger laboral manual y pago laboral con token, sin rutas internas de archivos.'
            );
        }
    } else {
        hcWarning(
            'Faltan vistas Personal NP-F-A.',
            'Crear index.php, reporte.php, ver.php y form.php antes de habilitar Personal completo.'
        );
    }

    if (is_file($workerCashSimulatorViewPath)) {
        $workerCashSimulatorViewCode = (string) file_get_contents($workerCashSimulatorViewPath);
        if (
            strpos($workerCashSimulatorViewCode, "action=\"<?= url('trabajadores/pagos-caja/simulador') ?>\"") !== false
            && strpos($workerCashSimulatorViewCode, 'method="GET"') !== false
            && strpos($workerCashSimulatorViewCode, 'method="POST"') === false
            && strpos($workerCashSimulatorViewCode, 'csrf_field()') === false
            && strpos($workerCashSimulatorViewCode, "url('trabajadores/' . (int)") !== false
            && strpos($workerCashSimulatorViewCode, "url('caja')") !== false
            && strpos($workerCashSimulatorViewCode, 'Solo GET') !== false
            && strpos($workerCashSimulatorViewCode, 'registrar-pago-caja') === false
            && strpos($workerCashSimulatorViewCode, 'movimientos_caja') === false
        ) {
            hcOk('Vista Personal 5E-C-A simulador Caja es GET/read-only y no expone pago laboral real.');
        } else {
            hcError(
                'Vista Personal 5E-C-A incompleta o con acciones fuera de alcance.',
                'Mantener simulador_pago_caja.php como GET/read-only, sin POST, sin CSRF y sin escrituras.'
            );
        }
    } else {
        hcWarning(
            'Falta vista Personal 5E-C-A simulador Caja.',
            'Crear app/views/trabajadores/simulador_pago_caja.php solo para diagnostico read-only.'
        );
    }

    $sidebarPath = $appRoot . '/app/views/layout/sidebar.php';
    $sidebarCode = is_file($sidebarPath) ? (string) file_get_contents($sidebarPath) : '';
    if (
        $sidebarCode !== ''
        && strpos($sidebarCode, "url('trabajadores')") !== false
        && strpos($sidebarCode, '$mostrarPersonal') !== false
    ) {
        hcOk('Sidebar registra Personal NP-A bajo administracion existente.');
    } else {
        hcWarning(
            'Sidebar no muestra navegacion Personal NP-A.',
            'Agregar enlace GET /trabajadores solo bajo guardas administrativas existentes.'
        );
    }

    $taskModelPath = $appRoot . '/app/models/TareaOperativa.php';
    $taskControllerPath = $controllersDir . '/TareaController.php';
    $taskIndexViewPath = $appRoot . '/app/views/tareas/index.php';
    $taskReportViewPath = $appRoot . '/app/views/tareas/reporte.php';
    $taskAgendaViewPath = $appRoot . '/app/views/tareas/agenda.php';
    $taskDetailViewPath = $appRoot . '/app/views/tareas/ver.php';
    $taskFormViewPath = $appRoot . '/app/views/tareas/form.php';
    $taskPartialViewPath = $appRoot . '/app/views/tareas/_contextual_list.php';

    if (is_file($taskModelPath) && is_file($taskControllerPath)) {
        $taskModelCode = (string) file_get_contents($taskModelPath);
        $taskControllerCode = (string) file_get_contents($taskControllerPath);
        $taskCode = $taskModelCode . "\n" . $taskControllerCode;

        if (
            strpos($taskModelCode, "protected \$table = 'tareas_operativas'") !== false
            && strpos($taskModelCode, 'function listarPorHotel') !== false
            && strpos($taskModelCode, 'function reporteReadOnlyPorHotel') !== false
            && strpos($taskModelCode, 'function agendaReadOnlyPorHotel') !== false
            && strpos($taskModelCode, 'function buscarPorIdHotel') !== false
            && strpos($taskModelCode, 'function eventosPorTarea') !== false
            && preg_match('/WHERE\s+t\.id\s*=\s*\?\s+AND\s+t\.hotel_id\s*=\s*\?/i', $taskModelCode)
            && strpos($taskModelCode, 'h.hotel_id = t.hotel_id') !== false
            && strpos($taskModelCode, 'tr.hotel_id = t.hotel_id') !== false
            && strpos($taskModelCode, 'm.hotel_id = t.hotel_id') !== false
        ) {
            hcOk('TareaOperativa model TLM-I-A/TLM-J-A consulta tareas/reporte/agenda con aislamiento hotel_id y joins scoped.');
        } else {
            hcWarning(
                'TareaOperativa model TLM-I-A no muestra contrato de consulta completo.',
                'Usar tareas_operativas con hotel_id, reporte read-only, detalle por id+hotel y joins por el mismo hotel.'
            );
        }

        $taskHasManualCreate =
            strpos($taskModelCode, 'function crearParaHotel') !== false
            && preg_match('/INSERT\s+INTO\s+tareas_operativas/i', $taskModelCode)
            && preg_match('/INSERT\s+INTO\s+tarea_eventos/i', $taskModelCode)
            && strpos($taskModelCode, 'safeBeginTransaction') !== false
            && strpos($taskModelCode, 'safeCommit') !== false
            && strpos($taskModelCode, 'safeRollBack') !== false
            && strpos($taskModelCode, 'validarHabitacionHotel') !== false
            && strpos($taskModelCode, "'pendiente'") !== false
            && strpos($taskModelCode, "'manual'") !== false;

        if ($taskHasManualCreate) {
            hcOk('TLM-F modelo conserva alta manual atomica con evento inicial, hotel_id y origen manual.');
        } else {
            hcWarning(
                'TLM-F modelo no muestra alta manual segura completa.',
                'Validar INSERT controlado en tareas_operativas/tarea_eventos, transaccion y habitacion del mismo hotel.'
            );
        }

        $taskHasMaintenanceCreate =
            strpos($taskModelCode, 'function crearDesdeMantenimientoParaHotel') !== false
            && strpos($taskModelCode, 'function buscarTareaActivaPorMantenimientoHotel') !== false
            && strpos($taskModelCode, "'mantenimiento'") !== false
            && strpos($taskModelCode, 'mantenimiento_id') !== false
            && strpos($taskModelCode, "estado IN ('pendiente', 'asignada', 'en_proceso')") !== false
            && preg_match('/INSERT\s+INTO\s+tareas_operativas/i', $taskModelCode)
            && preg_match('/INSERT\s+INTO\s+tarea_eventos/i', $taskModelCode)
            && strpos($taskModelCode, 'safeBeginTransaction') !== false
            && strpos($taskModelCode, 'safeCommit') !== false
            && strpos($taskModelCode, 'safeRollBack') !== false
            && strpos($taskModelCode, 'FROM mantenimientos_habitaciones m') !== false
            && strpos($taskModelCode, 'FOR UPDATE') !== false;

        if ($taskHasMaintenanceCreate) {
            hcOk('MANT-G-B-A modelo crea tarea desde mantenimiento con transaccion, mantenimiento_id y bloqueo de duplicado activo.');
        } else {
            hcWarning(
                'MANT-G-B-A modelo no muestra creacion desde mantenimiento completa.',
                'Validar mantenimiento_id, bloqueo de duplicado activo, transaccion, evento inicial y ausencia de Caja.'
            );
        }

        $taskHasCleaningCreate =
            strpos($taskModelCode, 'function crearDesdeLimpiezaHabitacionParaHotel') !== false
            && strpos($taskModelCode, 'function buscarTareaActivaLimpiezaPorHabitacionHotel') !== false
            && strpos($taskModelCode, "'habitacion'") !== false
            && strpos($taskModelCode, "categoria = 'limpieza'") !== false
            && strpos($taskModelCode, "estado IN ('pendiente', 'asignada', 'en_proceso')") !== false
            && strpos($taskModelCode, "!== 'limpieza'") !== false
            && preg_match('/INSERT\s+INTO\s+tareas_operativas/i', $taskModelCode)
            && preg_match('/INSERT\s+INTO\s+tarea_eventos/i', $taskModelCode)
            && strpos($taskModelCode, 'safeBeginTransaction') !== false
            && strpos($taskModelCode, 'safeCommit') !== false
            && strpos($taskModelCode, 'safeRollBack') !== false
            && strpos($taskModelCode, 'FROM habitaciones') !== false
            && strpos($taskModelCode, 'FOR UPDATE') !== false;

        if ($taskHasCleaningCreate) {
            hcOk('LIM-B-A modelo crea tarea desde habitacion en limpieza con transaccion y bloqueo de duplicado activo.');
        } else {
            hcWarning(
                'LIM-B-A modelo no muestra creacion desde limpieza completa.',
                'Validar habitacion en limpieza, categoria limpieza, bloqueo de duplicado activo, transaccion, evento inicial y ausencia de Caja.'
            );
        }

        $taskHasAssignment =
            strpos($taskModelCode, 'function asignarTrabajadorParaHotel') !== false
            && strpos($taskModelCode, 'function trabajadoresActivosOpciones') !== false
            && preg_match('/UPDATE\s+tareas_operativas/i', $taskModelCode)
            && strpos($taskModelCode, "estado IN ('pendiente', 'asignada')") !== false
            && strpos($taskModelCode, 'trabajadorActivoEnHotel') !== false
            && strpos($taskModelCode, "'asignada'") !== false
            && preg_match('/INSERT\s+INTO\s+tarea_eventos/i', $taskModelCode)
            && strpos($taskModelCode, 'safeBeginTransaction') !== false
            && strpos($taskModelCode, 'safeCommit') !== false
            && strpos($taskModelCode, 'safeRollBack') !== false;

        if ($taskHasAssignment) {
            hcOk('TLM-F modelo conserva asignacion atomica a trabajador activo del mismo hotel.');
        } else {
            hcWarning(
                'TLM-F modelo no muestra asignacion segura completa.',
                'Validar UPDATE scoped por id+hotel, trabajador activo del mismo hotel, transaccion y evento asignada.'
            );
        }

        $taskHasStateChanges =
            strpos($taskModelCode, 'function cambiarEstadoManualParaHotel') !== false
            && strpos($taskModelCode, 'function transicionManual') !== false
            && strpos($taskModelCode, "'iniciada'") !== false
            && strpos($taskModelCode, "'completada'") !== false
            && strpos($taskModelCode, "'cancelada'") !== false
            && strpos($taskModelCode, 'fecha_inicio') !== false
            && strpos($taskModelCode, 'fecha_cierre') !== false
            && preg_match('/UPDATE\s+tareas_operativas/i', $taskModelCode)
            && preg_match('/INSERT\s+INTO\s+tarea_eventos/i', $taskModelCode);

        if ($taskHasStateChanges) {
            hcOk('TLM-F modelo conserva iniciar/completar/cancelar con transicion manual y evento.');
        } else {
            hcWarning(
                'TLM-F modelo no muestra transiciones manuales completas.',
                'Validar iniciar/completar/cancelar con UPDATE scoped, evento y sin cambios de habitacion.'
            );
        }

        if (
            strpos($taskModelCode, 'function listarPorEntidadHotel') !== false
            && strpos($taskModelCode, "'habitacion' => 't.habitacion_id'") !== false
            && strpos($taskModelCode, "'trabajador' => 't.trabajador_id'") !== false
            && strpos($taskModelCode, 'WHERE t.hotel_id = ?') !== false
        ) {
            hcOk('TLM-F modelo expone consulta contextual por habitacion/trabajador con hotel_id.');
        } else {
            hcWarning(
                'TLM-F modelo no muestra consulta contextual completa.',
                'Agregar lectura por entidad con hotel_id; sin nuevas escrituras.'
            );
        }

        $taskForbiddenWrite = preg_match('/\bDELETE\s+FROM\s+(tareas_operativas|tarea_eventos|habitaciones|mantenimientos_habitaciones|movimientos_caja|cajas|cortes_caja)\b/i', $taskCode)
            || preg_match('/UPDATE\s+(habitaciones|mantenimientos_habitaciones|movimientos_caja|cajas|cortes_caja)\b/i', $taskCode)
            || preg_match('/INSERT\s+INTO\s+(habitaciones|mantenimientos_habitaciones|movimientos_caja|cajas|cortes_caja)\b/i', $taskCode);

        if (!$taskForbiddenWrite) {
            hcOk('TLM-F no contiene escrituras fuera de tareas_operativas/tarea_eventos ni toca habitaciones, mantenimiento o Caja.');
        } else {
            hcError(
                'TLM-F contiene escrituras fuera de alcance.',
                'Permitir solo INSERT/UPDATE controlado en tareas_operativas y eventos; sin DELETE ni Caja.'
            );
        }

        if (
            strpos($taskControllerCode, 'function indexAction') !== false
            && strpos($taskControllerCode, 'function reporteAction') !== false
            && strpos($taskControllerCode, 'function agendaAction') !== false
            && strpos($taskControllerCode, 'function verAction') !== false
            && strpos($taskControllerCode, 'function crearAction') !== false
            && strpos($taskControllerCode, 'function guardarAction') !== false
            && strpos($taskControllerCode, 'function crearDesdeMantenimientoAction') !== false
            && strpos($taskControllerCode, 'function crearDesdeLimpiezaAction') !== false
            && strpos($taskControllerCode, 'function asignarAction') !== false
            && strpos($taskControllerCode, 'function iniciarAction') !== false
            && strpos($taskControllerCode, 'function completarAction') !== false
            && strpos($taskControllerCode, 'function cancelarAction') !== false
            && strpos($taskControllerCode, 'cambiarEstadoManualParaHotel') !== false
            && strpos($taskControllerCode, "require_hotel_module('habitaciones')") !== false
            && strpos($taskControllerCode, "require_permission('habitaciones.view')") !== false
            && strpos($taskControllerCode, "require_permission('habitaciones.mantenimiento')") !== false
            && strpos($taskControllerCode, 'validateCSRF') !== false
            && strpos($taskControllerCode, 'AuditService::record') !== false
            && strpos($taskControllerCode, 'crearDesdeMantenimientoParaHotel') !== false
            && strpos($taskControllerCode, 'crearDesdeLimpiezaHabitacionParaHotel') !== false
            && strpos($taskControllerCode, 'asignarTrabajadorParaHotel') !== false
            && strpos($taskControllerCode, 'tareas/index') !== false
            && strpos($taskControllerCode, 'tareas/reporte') !== false
            && strpos($taskControllerCode, 'tareas/agenda') !== false
            && strpos($taskControllerCode, 'tareas/form') !== false
            && strpos($taskControllerCode, 'tareas/ver') !== false
        ) {
            hcOk('TareaController TLM-I-A/TLM-J-A/MANT-G-B-A/LIM-B-A expone listado/reporte/agenda/form/alta/contextuales/asignacion/estados/detalle con guardas, CSRF y auditoria.');
        } else {
            hcWarning(
                'TareaController TLM-I-A/MANT-G-B-A/LIM-B-A no muestra guardas o acciones completas.',
                'Validar requireAuth, hotel, modulo habitaciones, permisos, CSRF y acciones index/reporte/crear/guardar/desde mantenimiento/desde limpieza/asignar/iniciar/completar/cancelar/ver.'
            );
        }
    } else {
        hcWarning(
            'Faltan archivos TLM-F.',
            'Crear TareaOperativa.php y TareaController.php solo si la subfase de estados manuales esta autorizada.'
        );
    }

    if (is_file($taskIndexViewPath) && is_file($taskReportViewPath) && is_file($taskAgendaViewPath) && is_file($taskDetailViewPath) && is_file($taskFormViewPath) && is_file($taskPartialViewPath)) {
        $taskIndexViewCode = (string) file_get_contents($taskIndexViewPath);
        $taskReportViewCode = (string) file_get_contents($taskReportViewPath);
        $taskAgendaViewCode = (string) file_get_contents($taskAgendaViewPath);
        $taskDetailViewCode = (string) file_get_contents($taskDetailViewPath);
        $taskFormViewCode = (string) file_get_contents($taskFormViewPath);
        $taskPartialViewCode = (string) file_get_contents($taskPartialViewPath);
        $taskViewsCode = $taskIndexViewCode . "\n" . $taskReportViewCode . "\n" . $taskAgendaViewCode . "\n" . $taskDetailViewCode . "\n" . $taskFormViewCode . "\n" . $taskPartialViewCode;
        $taskIndexHasDetailLink = strpos($taskIndexViewCode, "url('tareas/' . (int)") !== false
            || strpos($taskIndexViewCode, "url('tareas/' . \$tareaId)") !== false;
        $taskReportHasReadOnlyTitle = strpos($taskReportViewCode, 'Reporte operativo') !== false
            || strpos($taskReportViewCode, 'Reporte de tareas') !== false;
        $taskDetailHasEventsSection = strpos($taskDetailViewCode, 'Eventos') !== false
            || (
                strpos($taskDetailViewCode, 'Historial') !== false
                && strpos($taskDetailViewCode, '$eventos') !== false
            );

        if (
            strpos($taskIndexViewCode, "action=\"<?= url('tareas') ?>\"") !== false
            && strpos($taskIndexViewCode, 'method="GET"') !== false
            && strpos($taskIndexViewCode, "url('tareas/reporte')") !== false
            && strpos($taskIndexViewCode, "url('tareas/agenda')") !== false
            && strpos($taskIndexViewCode, "url('tareas/crear')") !== false
            && $taskIndexHasDetailLink
            && $taskReportHasReadOnlyTitle
            && strpos($taskReportViewCode, 'method="POST"') === false
            && strpos($taskReportViewCode, 'csrf_field()') === false
            && strpos($taskAgendaViewCode, 'Agenda de tareas') !== false
            && strpos($taskAgendaViewCode, 'method="GET"') !== false
            && strpos($taskAgendaViewCode, 'method="POST"') === false
            && strpos($taskAgendaViewCode, 'csrf_field()') === false
            && strpos($taskDetailViewCode, "url('tareas')") !== false
            && $taskDetailHasEventsSection
            && strpos($taskDetailViewCode, "/asignar')") !== false
            && strpos($taskDetailViewCode, "/iniciar')") !== false
            && strpos($taskDetailViewCode, "/completar')") !== false
            && strpos($taskDetailViewCode, "/cancelar')") !== false
            && strpos($taskDetailViewCode, 'name="trabajador_id"') !== false
            && strpos($taskDetailViewCode, 'name="comentario"') !== false
            && strpos($taskDetailViewCode, 'csrf_field()') !== false
            && strpos($taskFormViewCode, 'method="POST"') !== false
            && strpos($taskFormViewCode, "action=\"<?= url('tareas') ?>\"") !== false
            && strpos($taskFormViewCode, 'csrf_field()') !== false
            && strpos($taskFormViewCode, 'name="titulo"') !== false
            && strpos($taskViewsCode, 'movimientos_caja') === false
            && strpos($taskViewsCode, "action=\"<?= url('habitaciones") === false
        ) {
            hcOk('Vistas TLM-I-A/TLM-J-A muestran filtros GET, reporte/agenda read-only, alta/asignacion/estados manuales con CSRF sin Caja.');
        } else {
            hcWarning(
                'Vistas TLM-I-A/TLM-J-A no muestran contrato visual completo.',
                'Asegurar filtros GET, reporte/agenda sin POST, POST /tareas, asignacion y estados manuales con CSRF y sin acciones de habitacion/Caja.'
            );
        }

        if (
            strpos($taskPartialViewCode, 'tareasContextuales') !== false
            && strpos($taskPartialViewCode, "url('tareas/'") !== false
            && strpos($taskPartialViewCode, 'method="POST"') === false
            && strpos($taskPartialViewCode, 'csrf_field()') === false
            && strpos($taskPartialViewCode, 'storage_path') === false
        ) {
            hcOk('Partial TLM-F contextual es read-only y enlaza al detalle de tareas.');
        } else {
            hcWarning(
                'Partial TLM-F contextual no parece read-only completo.',
                'Mantenerlo sin formularios ni acciones; solo metadata y enlaces.'
            );
        }

        $habitacionControllerPath = $controllersDir . '/HabitacionController.php';
        $habitacionViewPathTlm = $appRoot . '/app/views/habitaciones/ver.php';
        $habitacionControllerCode = is_file($habitacionControllerPath) ? (string) file_get_contents($habitacionControllerPath) : '';
        $habitacionViewCodeTlm = is_file($habitacionViewPathTlm) ? (string) file_get_contents($habitacionViewPathTlm) : '';
        $workerControllerCodeForTasks = is_file($workerControllerPath) ? (string) file_get_contents($workerControllerPath) : '';
        $workerDetailViewCodeForTasks = is_file($workerDetailViewPath) ? (string) file_get_contents($workerDetailViewPath) : '';

        if (
            strpos($habitacionControllerCode, 'TareaOperativa') !== false
            && strpos($habitacionControllerCode, "listarPorEntidadHotel(\$hotelId, 'habitacion'") !== false
            && strpos($habitacionControllerCode, "'tareas_contextuales'") !== false
            && strpos($habitacionViewCodeTlm, '$tareas_contextuales') !== false
            && strpos($habitacionViewCodeTlm, "_contextual_list.php") !== false
        ) {
            hcOk('Ficha Habitacion TLM-F integra tareas contextuales read-only por hotel.');
        } else {
            hcWarning(
                'Ficha Habitacion TLM-F no muestra integracion contextual completa.',
                'Cargar TareaOperativa::listarPorEntidadHotel(habitacion) desde el controlador y renderizar el partial read-only.'
            );
        }

        if (
            strpos($workerControllerCodeForTasks, 'TareaOperativa') !== false
            && strpos($workerControllerCodeForTasks, "listarPorEntidadHotel(\$hotelId, 'trabajador'") !== false
            && strpos($workerControllerCodeForTasks, "'tareasContextuales'") !== false
            && strpos($workerDetailViewCodeForTasks, '$tareasContextuales') !== false
            && strpos($workerDetailViewCodeForTasks, "_contextual_list.php") !== false
        ) {
            hcOk('Ficha Trabajador TLM-F integra tareas asignadas read-only por hotel.');
        } else {
            hcWarning(
                'Ficha Trabajador TLM-F no muestra integracion contextual completa.',
                'Cargar TareaOperativa::listarPorEntidadHotel(trabajador) desde el controlador y renderizar el partial read-only.'
            );
        }
    } else {
        hcWarning(
            'Faltan vistas TLM-I-A/TLM-J-A.',
            'Crear app/views/tareas/index.php, reporte.php, agenda.php, form.php, ver.php y _contextual_list.php.'
        );
    }

    if (
        $sidebarCode !== ''
        && strpos($sidebarCode, "url('tareas')") !== false
        && strpos($sidebarCode, '$mostrarTareas') !== false
    ) {
        hcOk('Sidebar registra Tareas TLM-F bajo operaciones con gate visual de limpieza/mantenimiento/habitaciones.');
    } else {
        hcWarning(
            'Sidebar no muestra navegacion Tareas TLM-F.',
            'Agregar enlace GET /tareas bajo gate visual de limpieza/mantenimiento/habitaciones.'
        );
    }

    if ($purchaseControllerFile && is_file($purchaseControllerFile)) {
        $purchaseControllerCode = (string) file_get_contents($purchaseControllerFile);
        $forbiddenPurchaseControllerTokens = [
            'function pagarAction',
            'function cancelarAction',
            'movimientos_caja',
            'cuentas_por_pagar',
            'compra_pagos',
            'documentos_proveedor',
        ];
        $controllerForbidden = [];
        foreach ($forbiddenPurchaseControllerTokens as $token) {
            if (strpos($purchaseControllerCode, $token) !== false) {
                $controllerForbidden[] = $token;
            }
        }

        if (
            strpos($purchaseControllerCode, 'class CompraController') !== false
            && strpos($purchaseControllerCode, 'function indexAction') !== false
            && strpos($purchaseControllerCode, 'function crearAction') !== false
            && strpos($purchaseControllerCode, 'function verAction') !== false
            && strpos($purchaseControllerCode, 'function reporteRecibidasAction') !== false
            && strpos($purchaseControllerCode, 'function guardarAction') !== false
            && strpos($purchaseControllerCode, 'function recibirAction') !== false
            && strpos($purchaseControllerCode, "require_hotel_module('inventario')") !== false
            && strpos($purchaseControllerCode, 'compras/ver') !== false
            && strpos($purchaseControllerCode, 'compras/reporte_recibidas') !== false
            && strpos($purchaseControllerCode, 'obtenerCompra') !== false
            && strpos($purchaseControllerCode, 'reporteRecibidas') !== false
            && strpos($purchaseControllerCode, 'catalogosReporteRecibidas') !== false
            && strpos($purchaseControllerCode, 'crearBorrador') !== false
            && strpos($purchaseControllerCode, 'recibirCompra') !== false
            && strpos($purchaseControllerCode, 'validateCSRF') !== false
            && empty($controllerForbidden)
        ) {
            hcOk('CompraController Fase 2Y expone listado, detalle, reporte read-only, borrador y recepcion minima bajo modulo inventario.');
        } else {
            hcError(
                'CompraController Fase 2Y no cumple el alcance minimo o contiene tokens prohibidos: ' . (empty($controllerForbidden) ? 'sin detalle' : implode(', ', $controllerForbidden)),
                'Mantener solo indexAction, crearAction, verAction, reporteRecibidasAction, guardarAction y recibirAction; sin pagos, CxP, documentos ni caja.'
            );
        }
    } else {
        hcError(
            'No existe CompraController Fase 2R.',
            'Crear controlador minimo para borradores o retirar rutas /compras.'
        );
    }

    if ($purchaseIndexViewFile && is_file($purchaseIndexViewFile) && $purchaseFormViewFile && is_file($purchaseFormViewFile) && $purchaseDetailViewFile && is_file($purchaseDetailViewFile) && $purchaseReportViewFile && is_file($purchaseReportViewFile)) {
        $purchaseIndexViewCode = (string) file_get_contents($purchaseIndexViewFile);
        $purchaseFormViewCode = (string) file_get_contents($purchaseFormViewFile);
        $purchaseDetailViewCode = (string) file_get_contents($purchaseDetailViewFile);
        $purchaseReportViewCode = (string) file_get_contents($purchaseReportViewFile);
        $purchaseViewsCode = $purchaseIndexViewCode . "\n" . $purchaseFormViewCode . "\n" . $purchaseDetailViewCode . "\n" . $purchaseReportViewCode;

        $purchaseIndexHasDetailLink = strpos($purchaseIndexViewCode, "url('compras/' . (int)") !== false
            || strpos($purchaseIndexViewCode, "url('compras/' . \$compraId)") !== false;
        $purchaseViewsHaveReceiveAction = strpos($purchaseViewsCode, '/recibir') !== false
            && (
                strpos($purchaseViewsCode, 'purchase-btn-receive') !== false
                || strpos($purchaseViewsCode, 'cp-btn-receive') !== false
                || strpos($purchaseViewsCode, 'data-receive-form="1"') !== false
            );

        if (
            strpos($purchaseIndexViewCode, "url('compras/crear')") !== false
            && strpos($purchaseIndexViewCode, "url('compras/reportes/recibidas')") !== false
            && $purchaseIndexHasDetailLink
            && strpos($purchaseFormViewCode, "action=\"<?= url('compras') ?>\"") !== false
            && strpos($purchaseFormViewCode, 'csrf_field()') !== false
            && strpos($purchaseFormViewCode, 'name="producto_id[]"') !== false
            && strpos($purchaseFormViewCode, 'name="cantidad[]"') !== false
            && strpos($purchaseFormViewCode, 'name="costo_unitario[]"') !== false
            && strpos($purchaseDetailViewCode, 'movimiento_inventario_id') !== false
            && strpos($purchaseDetailViewCode, 'movimiento_stock_posterior') !== false
            && strpos($purchaseDetailViewCode, "url('compras?estado=") !== false
            && strpos($purchaseDetailViewCode, "url('compras/reportes/recibidas')") !== false
            && strpos($purchaseReportViewCode, "action=\"<?= url('compras/reportes/recibidas') ?>\"") !== false
            && strpos($purchaseReportViewCode, 'por_proveedor') !== false
            && strpos($purchaseReportViewCode, 'por_producto') !== false
            && strpos($purchaseReportViewCode, "url('compras/' . (int)") !== false
            && $purchaseViewsHaveReceiveAction
            && strpos($purchaseViewsCode, "url('compras/pagar") === false
            && strpos($purchaseViewsCode, 'cuentas-por-pagar') === false
        ) {
            hcOk('Vistas de Compras Fase 2Y permiten reporte read-only, detalle, borrador y recepcion minima con CSRF.');
        } else {
            hcError(
                'Vistas de Compras Fase 2Y incompletas o con enlaces fuera de alcance.',
                'Revisar reporte read-only, detalle, CSRF, action POST /compras, recepcion minima y ausencia de pagos/CxP.'
            );
        }
    } else {
        hcError(
            'Faltan vistas de Compras Fase 2Y.',
            'Crear app/views/compras/index.php, app/views/compras/form.php, app/views/compras/ver.php y app/views/compras/reporte_recibidas.php o retirar rutas /compras.'
        );
    }

    if ($cxpControllerFile && is_file($cxpControllerFile) && $cxpModelFile && is_file($cxpModelFile)) {
        $cxpControllerCode = (string) file_get_contents($cxpControllerFile);
        $cxpModelCode = (string) file_get_contents($cxpModelFile);
        $cxpCode = $cxpControllerCode . "\n" . $cxpModelCode;
        $cxpForbidden = preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+(cuentas_por_pagar_movimientos|movimientos_caja|cortes_caja|cajas)\b/i', $cxpCode)
            || strpos($cxpCode, 'function pagarAction') !== false
            || strpos($cxpCode, 'function abonarAction') !== false
            || strpos($cxpCode, 'ABONO') !== false
            || strpos($cxpCode, 'movimientos_caja') !== false;

        if (
            !$cxpForbidden
            && strpos($cxpControllerCode, 'class CuentaPorPagarController') !== false
            && strpos($cxpControllerCode, 'function indexAction') !== false
            && strpos($cxpControllerCode, 'function generacionPreviewAction') !== false
            && strpos($cxpControllerCode, 'function simuladorCajaAction') !== false
            && strpos($cxpControllerCode, 'function generarDesdeCompraAction') !== false
            && strpos($cxpControllerCode, 'function registrarPagoCajaAction') !== false
            && strpos($cxpControllerCode, 'function revertirPagoCajaAction') !== false
            && strpos($cxpControllerCode, 'validateCSRF') !== false
            && strpos($cxpControllerCode, 'consumirPagoToken') !== false
            && strpos($cxpControllerCode, 'consumirReversionPagoToken') !== false
            && strpos($cxpControllerCode, "require_hotel_module('caja')") !== false
            && strpos($cxpControllerCode, 'function verAction') !== false
            && strpos($cxpControllerCode, "require_hotel_module('inventario')") !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/index') !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/generacion_preview') !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/simulador_caja') !== false
            && strpos($cxpControllerCode, 'cuentas_por_pagar/ver') !== false
            && strpos($cxpModelCode, 'class CuentaPorPagar') !== false
            && strpos($cxpModelCode, "protected \$table = 'cuentas_por_pagar'") !== false
            && strpos($cxpModelCode, 'function resumenPorHotel') !== false
            && strpos($cxpModelCode, 'function listarPorHotel') !== false
            && strpos($cxpModelCode, 'function previewGeneracionDesdeCompras') !== false
            && strpos($cxpModelCode, 'function simuladorCajaProveedor') !== false
            && strpos($cxpModelCode, 'function corteAbiertoSimuladorCaja') !== false
            && strpos($cxpModelCode, 'function generarDesdeCompraRecibida') !== false
            && strpos($cxpModelCode, 'FOR UPDATE') !== false
            && strpos($cxpModelCode, 'INSERT INTO cuentas_por_pagar') !== false
            && strpos($cxpModelCode, 'AuditService::record') !== false
            && strpos($cxpModelCode, 'function buscarPorIdHotel') !== false
            && strpos($cxpModelCode, 'function movimientosPorCuenta') !== false
            && strpos($cxpModelCode, 'FROM cuentas_por_pagar') !== false
            && strpos($cxpModelCode, 'FROM cuentas_por_pagar_movimientos') !== false
        ) {
            hcOk('CxP Fase 3B/3C/3D/3D-D-A expone lectura, preview, simulador, pago y reversion Caja delegados.');
        } else {
            hcError(
                'CxP Fase 3D no cumple contrato o contiene tokens prohibidos.',
                'Mantener GET index/preview/simulador-caja/ver, POST generar, POST registrarPagoCaja y POST revertirPagoCaja delegados al servicio.'
            );
        }
    } else {
        hcWarning(
            'Faltan modelo/controlador de CxP Fase 3B.',
            'Crear CuentaPorPagar y CuentaPorPagarController solo para vistas read-only.'
        );
    }

    if (
        $cxpIndexViewFile && is_file($cxpIndexViewFile)
        && $cxpDetailViewFile && is_file($cxpDetailViewFile)
        && $cxpPreviewViewFile && is_file($cxpPreviewViewFile)
    ) {
        $cxpIndexViewCode = (string) file_get_contents($cxpIndexViewFile);
        $cxpDetailViewCode = (string) file_get_contents($cxpDetailViewFile);
        $cxpPreviewViewCode = (string) file_get_contents($cxpPreviewViewFile);
        $cxpViewsCode = $cxpIndexViewCode . "\n" . $cxpDetailViewCode . "\n" . $cxpPreviewViewCode;
        $cxpIndexHasDetailLink = strpos($cxpIndexViewCode, "url('cuentas-por-pagar/' . (int)") !== false
            || strpos($cxpIndexViewCode, "url('cuentas-por-pagar/' . \$cuentaId)") !== false;
        if (
            strpos($cxpIndexViewCode, "action=\"<?= url('cuentas-por-pagar') ?>\"") !== false
            && strpos($cxpIndexViewCode, "url('cuentas-por-pagar/generacion-preview')") !== false
            && $cxpIndexHasDetailLink
            && strpos($cxpDetailViewCode, "url('cuentas-por-pagar')") !== false
            && strpos($cxpDetailViewCode, "url('compras/' . (int)") !== false
            && strpos($cxpDetailViewCode, "url('proveedores/' . (int)") !== false
            && strpos($cxpPreviewViewCode, "action=\"<?= url('cuentas-por-pagar/generacion-preview') ?>\"") !== false
            && strpos($cxpPreviewViewCode, "action=\"<?= url('cuentas-por-pagar/generar-desde-compra/' . (int)") !== false
            && strpos($cxpPreviewViewCode, 'csrf_field()') !== false
            && strpos($cxpPreviewViewCode, "url('compras/' . (int)") !== false
            && strpos($cxpPreviewViewCode, "url('proveedores/' . (int)") !== false
            && strpos($cxpDetailViewCode, 'registrar-pago-caja') !== false
            && strpos($cxpDetailViewCode, 'revertir-pago-caja') !== false
            && strpos($cxpDetailViewCode, 'csrf_field()') !== false
            && strpos($cxpDetailViewCode, 'name="pago_token"') !== false
            && strpos($cxpDetailViewCode, 'name="reversion_token"') !== false
            && strpos($cxpViewsCode, 'movimientos_caja') === false
        ) {
            hcOk('Vistas CxP Fase 3D/3D-D-A incluyen detalle con pago/reversion Caja controlados y CSRF.');
        } else {
            hcError(
                'Vistas CxP Fase 3D incompletas o con acciones fuera de alcance.',
                'Mantener listado/preview y detalle con formularios pago/reversion Caja con CSRF/token; simulador debe seguir GET.'
            );
        }
    } else {
        hcWarning(
            'Faltan vistas CxP Fase 3B/3C-A.',
            'Crear app/views/cuentas_por_pagar/index.php, generacion_preview.php y ver.php en modo solo lectura.'
        );
    }

    if ($cxpPaymentServiceFile && is_file($cxpPaymentServiceFile) && $cxpPaymentRollbackTool && is_file($cxpPaymentRollbackTool)) {
        $cxpPaymentServiceCode = (string) file_get_contents($cxpPaymentServiceFile);
        $cxpPaymentRollbackCode = (string) file_get_contents($cxpPaymentRollbackTool);

        if (
            strpos($cxpPaymentServiceCode, 'class CuentaPorPagarPagoService') !== false
            && strpos($cxpPaymentServiceCode, 'manage_transaction') !== false
            && strpos($cxpPaymentServiceCode, 'FOR UPDATE') !== false
            && strpos($cxpPaymentServiceCode, 'INSERT INTO cuentas_por_pagar_movimientos') !== false
            && strpos($cxpPaymentServiceCode, 'INSERT INTO movimientos_caja') !== false
            && strpos($cxpPaymentServiceCode, 'UPDATE cuentas_por_pagar') !== false
            && strpos($cxpPaymentServiceCode, 'AuditService::record') !== false
            && strpos($cxpPaymentServiceCode, 'No hay corte de Caja abierto') !== false
            && strpos($cxpPaymentServiceCode, 'La compra vinculada no esta recibida') !== false
            && strpos($cxpPaymentRollbackCode, "['manage_transaction' => false]") !== false
            && strpos($cxpPaymentRollbackCode, 'rollBack()') !== false
            && strpos($cxpPaymentRollbackCode, 'movimientos_caja') !== false
        ) {
            hcOk('Servicio Fase 3D de pago proveedor concentra transaccion, locks, CxP, Caja, auditoria y prueba rollback.');
        } else {
            hcError(
                'Servicio Fase 3D de pago proveedor incompleto.',
                'Revisar transaccion, locks FOR UPDATE, escrituras CxP/Caja, auditoria y prueba rollback sin persistencia.'
            );
        }
    } else {
        hcError(
            'Falta servicio o herramienta rollback de pago proveedor Fase 3D.',
            'Crear CuentaPorPagarPagoService y tools/saas/probar_pago_proveedor_caja.php antes de QA manual.'
        );
    }

    if (
        $cxpPaymentReversalServiceFile && is_file($cxpPaymentReversalServiceFile)
        && $cxpPaymentReversalRollbackTool && is_file($cxpPaymentReversalRollbackTool)
    ) {
        $cxpPaymentReversalServiceCode = (string) file_get_contents($cxpPaymentReversalServiceFile);
        $cxpPaymentReversalRollbackCode = (string) file_get_contents($cxpPaymentReversalRollbackTool);

        if (
            strpos($cxpPaymentReversalServiceCode, 'class CuentaPorPagarReversionPagoService') !== false
            && strpos($cxpPaymentReversalServiceCode, 'manage_transaction') !== false
            && strpos($cxpPaymentReversalServiceCode, 'FOR UPDATE') !== false
            && strpos($cxpPaymentReversalServiceCode, 'PAGO_REFERENCIAL') !== false
            && strpos($cxpPaymentReversalServiceCode, 'CANCELACION') !== false
            && strpos($cxpPaymentReversalServiceCode, "tipo = 'ingreso'") !== false
            && strpos($cxpPaymentReversalServiceCode, 'Reversion Pago proveedor') !== false
            && strpos($cxpPaymentReversalServiceCode, 'INSERT INTO cuentas_por_pagar_movimientos') !== false
            && strpos($cxpPaymentReversalServiceCode, 'INSERT INTO movimientos_caja') !== false
            && strpos($cxpPaymentReversalServiceCode, 'UPDATE cuentas_por_pagar') !== false
            && strpos($cxpPaymentReversalServiceCode, 'AuditService::record') !== false
            && strpos($cxpPaymentReversalRollbackCode, "['manage_transaction' => false]") !== false
            && strpos($cxpPaymentReversalRollbackCode, 'CuentaPorPagarPagoService') !== false
            && strpos($cxpPaymentReversalRollbackCode, 'CuentaPorPagarReversionPagoService') !== false
            && strpos($cxpPaymentReversalRollbackCode, 'rollBack()') !== false
            && strpos($cxpPaymentReversalRollbackCode, "categoria = 'Reversion Pago proveedor'") !== false
        ) {
            hcOk('Servicio Fase 3D-D-A de reversion proveedor concentra transaccion, locks, CxP, Caja, auditoria y prueba rollback.');
        } else {
            hcError(
                'Servicio Fase 3D-D-A de reversion proveedor incompleto.',
                'Revisar transaccion, locks FOR UPDATE, CANCELACION CxP, ingreso Caja, auditoria y prueba rollback sin persistencia.'
            );
        }
    } else {
        hcError(
            'Falta servicio o herramienta rollback de reversion proveedor Fase 3D-D-A.',
            'Crear CuentaPorPagarReversionPagoService y tools/saas/probar_reversion_pago_proveedor_caja.php antes de QA manual.'
        );
    }

    if ($cxpCashSimulatorViewFile && is_file($cxpCashSimulatorViewFile)) {
        $cxpCashSimulatorViewCode = (string) file_get_contents($cxpCashSimulatorViewFile);
        if (
            strpos($cxpCashSimulatorViewCode, "action=\"<?= url('cuentas-por-pagar/simulador-caja') ?>\"") !== false
            && strpos($cxpCashSimulatorViewCode, 'method="GET"') !== false
            && strpos($cxpCashSimulatorViewCode, 'method="POST"') === false
            && strpos($cxpCashSimulatorViewCode, 'csrf_field()') === false
            && strpos($cxpCashSimulatorViewCode, "url('cuentas-por-pagar/' . (int)") !== false
            && strpos($cxpCashSimulatorViewCode, "url('proveedores/' . (int)") !== false
            && (
                strpos($cxpCashSimulatorViewCode, 'Solo GET') !== false
                || strpos($cxpCashSimulatorViewCode, 'Solo consulta') !== false
            )
            && strpos($cxpCashSimulatorViewCode, 'movimientos_caja') === false
        ) {
            hcOk('Vista CxP Fase 3D-A simulador Caja es GET/read-only y no expone acciones de egreso.');
        } else {
            hcError(
                'Vista CxP Fase 3D-A incompleta o con acciones fuera de alcance.',
                'Mantener simulador_caja.php como GET/read-only, sin POST, sin CSRF y sin escrituras.'
            );
        }
    } else {
        hcWarning(
            'Falta vista CxP Fase 3D-A simulador Caja.',
            'Crear app/views/cuentas_por_pagar/simulador_caja.php solo si se habilita el simulador read-only.'
        );
    }

    if (is_file($sidebarPath)) {
        $sidebarCode = (string) file_get_contents($sidebarPath);
        if (
            strpos($sidebarCode, '$mostrarProveedores = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('proveedores')") !== false
            && strpos($sidebarCode, 'Proveedores') !== false
        ) {
            hcOk('Sidebar expone Proveedores bajo el gate visual de Inventario en Fase 2G.');
        } else {
            hcWarning(
                'Sidebar no expone Proveedores bajo Inventario.',
                'Si el catalogo ya fue validado manualmente, enlazar /proveedores solo cuando inventario este visible.'
            );
        }

        if (
            strpos($sidebarCode, '$mostrarCompras = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('compras')") !== false
            && strpos($sidebarCode, 'Compras') !== false
        ) {
            hcOk('Sidebar expone Compras Fase 2R bajo el gate visual de Inventario.');
        } else {
            hcError(
                'Sidebar no expone Compras Fase 2R bajo el gate de Inventario.',
                'Enlazar /compras solo mediante $mostrarCompras = $mostrarInventario.'
            );
        }

        if (
            strpos($sidebarCode, '$mostrarCuentasPorPagar = $mostrarInventario') !== false
            && strpos($sidebarCode, "url('cuentas-por-pagar')") !== false
            && strpos($sidebarCode, 'Cuentas por pagar') !== false
        ) {
            hcOk('Sidebar expone CxP read-only Fase 3B bajo el gate visual de Inventario.');
        } else {
            hcError(
                'Sidebar no expone CxP read-only bajo el gate esperado.',
                'Enlazar /cuentas-por-pagar solo mediante $mostrarCuentasPorPagar = $mostrarInventario.'
            );
        }
    } else {
        hcWarning(
            'No se encontro sidebar.php para validar navegacion Fase 2G.',
            'Validar manualmente que Proveedores no se exponga como modulo independiente sin decision SaaS.'
        );
    }

    $auditServicePath = $appRoot . '/app/services/AuditService.php';
    $authControllerPath = $controllersDir . '/AuthController.php';
    if (is_file($auditServicePath)) {
        hcOk('AuditService existe para escritura gradual en logs_auditoria.');
    } else {
        hcError('AuditService no existe.', 'Crear una base reusable de auditoria antes de integrar acciones sensibles.');
    }

    if (is_file($authControllerPath)) {
        $authCode = (string) file_get_contents($authControllerPath);
        if (
            strpos($authCode, 'AuditService::loginSuccess') !== false
            && strpos($authCode, 'AuditService::loginFailed') !== false
            && strpos($authCode, 'AuditService::logout') !== false
        ) {
            hcOk('AuthController integra auditoria minima de login/logout.');
        } else {
            hcWarning(
                'AuthController no muestra integracion completa con AuditService para login/logout.',
                'Integrar gradualmente sin cambiar la logica de autenticacion.'
            );
        }
    }

    $habitacionView = $appRoot . '/app/views/habitaciones/ver.php';
    if (is_file($habitacionView) && strpos((string) file_get_contents($habitacionView), 'habitaciones/limpieza/') !== false) {
        hcError('habitaciones/ver.php sigue apuntando a habitaciones/limpieza/{id}.', 'Cambiar el form al endpoint seguro habitaciones/{id}/liberar.');
    } elseif (is_file($habitacionView) && hcRoutePatternExists($routes, 'habitaciones/1/liberar', 'post')) {
        hcOk('habitaciones/ver.php no usa habitaciones/limpieza/{id} y existe POST habitaciones/{id}/liberar.');
    }

    $backupView = $appRoot . '/app/views/configuracion/backup.php';
    if (is_file($backupView) && strpos((string) file_get_contents($backupView), 'configuracion/backup/descargar') !== false) {
        if (hcRouteExists($routes, 'configuracion/backup/descargar', 'get')) {
            hcOk('configuracion/backup.php apunta a una ruta de descarga registrada.');
        } else {
            hcError('configuracion/backup.php apunta a configuracion/backup/descargar sin ruta GET.', 'Registrar Configuracion::descargarBackup.');
        }
    }

    $inventarioAjusteView = $appRoot . '/app/views/inventario/ajuste.php';
    if (is_file($inventarioAjusteView) && strpos((string) file_get_contents($inventarioAjusteView), 'inventario/ajuste/') !== false) {
        $ajusteGet = hcRoutePatternExists($routes, 'inventario/ajuste/1', 'get');
        $ajustePost = hcRoutePatternExists($routes, 'inventario/ajuste/1', 'post');
        if (!$ajusteGet && !$ajustePost) {
            hcWarning(
                'inventario/ajuste/{id} queda aislado como vista legacy no registrada.',
                $inventoryDoc && is_file($inventoryDoc)
                    ? 'Mantener sin ruta hasta Fase 2B; entrada/salida actuales cubren movimientos manuales seguros.'
                    : 'Documentar decision antes de activar ajuste de stock por id.'
            );
        } elseif ($ajusteGet && $ajustePost) {
            hcOk('inventario/ajuste/{id} tiene rutas GET y POST registradas.');

            $ajusteViewCode = (string) file_get_contents($inventarioAjusteView);
            $ajusteActionCode = hcExtractMethodCode($inventarioControllerCode, 'ajusteAction');
            $procesarAjusteCode = hcExtractMethodCode($inventarioControllerCode, 'procesarAjusteAction');
            $movimientoModelPath = $appRoot . '/app/models/MovimientoInventario.php';
            $movimientoModelCode = is_file($movimientoModelPath) ? (string) file_get_contents($movimientoModelPath) : '';
            $inventarioDocCode = $inventoryDoc && is_file($inventoryDoc) ? (string) file_get_contents($inventoryDoc) : '';

            if ($ajusteActionCode !== '' && $procesarAjusteCode !== '') {
                hcOk('InventarioController expone ajusteAction y procesarAjusteAction.');
            } else {
                hcError(
                    'inventario/ajuste/{id} esta registrado pero faltan metodos de controller.',
                    'No exponer la ruta hasta que ambos metodos existan.'
                );
            }

            if (
                strpos($ajusteViewCode, '$producto') !== false
                && strpos($ajusteViewCode, '$productos') === false
                && strpos($ajusteActionCode, "'producto' => \$producto") !== false
            ) {
                hcOk('Vista de ajuste usa contrato singular $producto alineado con el controller.');
            } else {
                hcError(
                    'Vista/controller de ajuste no estan alineados en $producto.',
                    'El GET debe entregar producto singular y la vista no debe esperar $productos.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'getByIdWithCategory') !== false
                && strpos($procesarAjusteCode, 'actualizarProductoBase') !== false
            ) {
                hcOk('Ajuste valida producto y actualiza stock mediante el modelo moderno Inventario.');
            } else {
                hcError(
                    'Ajuste no muestra validacion/actualizacion por modelo moderno.',
                    'Usar Inventario::getByIdWithCategory y actualizarProductoBase para respetar hotel_id.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'crearMovimientoManual') !== false
                && strpos($movimientoModelCode, "protected \$table = 'movimientos_inventario'") !== false
                && strpos($movimientoModelCode, 'productoPerteneceAlHotel') !== false
            ) {
                hcOk('Ajuste registra movimiento en movimientos_inventario con validacion de hotel.');
            } else {
                hcError(
                    'Ajuste puede modificar stock sin movimiento moderno validado.',
                    'Registrar siempre en MovimientoInventario::crearMovimientoManual antes de confirmar.'
                );
            }

            if (strpos($procesarAjusteCode, 'AuditService::record') !== false && strpos($procesarAjusteCode, 'inventario.ajuste_stock') !== false) {
                hcOk('Ajuste registra auditoria minima inventario.ajuste_stock.');
            } else {
                hcError(
                    'Ajuste no registra auditoria minima.',
                    'Llamar AuditService::record con stock anterior/nuevo y motivo.'
                );
            }

            if (
                strpos($procesarAjusteCode, 'FROM productos') === false
                && strpos($procesarAjusteCode, 'JOIN productos') === false
                && strpos($procesarAjusteCode, 'inventario_movimientos') === false
            ) {
                hcOk('Ajuste moderno no usa tablas legacy productos ni inventario_movimientos.');
            } else {
                hcError(
                    'Ajuste moderno contiene referencias a tablas legacy.',
                    'No usar productos ni inventario_movimientos en Fase 2B.'
                );
            }

            if ($inventoryDoc === null || !is_file($inventoryDoc)) {
                hcWarning(
                    'No se pudo validar documentacion de inventario porque docs/technical no esta montado.',
                    'Ejecutar el checker desde la raiz del proyecto para validar docs/technical/inventory_reconciliation.md.'
                );
            } elseif (strpos($inventarioDocCode, 'Fase 2B') !== false && strpos($inventarioDocCode, 'inventario.ajuste_stock') !== false) {
                hcOk('Documentacion de inventario registra contrato Fase 2B.');
            } else {
                hcWarning(
                    'Documentacion de inventario no registra aun Fase 2B.',
                    'Actualizar docs/technical/inventory_reconciliation.md con el contrato final del ajuste.'
                );
            }

            if ($inventoryDoc && is_file($inventoryDoc)) {
                if (
                    strpos($inventarioDocCode, 'Fase 2C') !== false
                    && strpos($inventarioDocCode, 'legacy congelado') !== false
                    && strpos($inventarioDocCode, 'inventario_config_habitacion') !== false
                    && strpos($inventarioDocCode, 'inventario_productos') !== false
                ) {
                    hcOk('Documentacion de inventario registra contrato Fase 2C.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2C.',
                        'Actualizar docs/technical/inventory_reconciliation.md con congelamiento legacy y contrato moderno.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2D') !== false
                    && strpos($inventarioDocCode, 'rollback') !== false
                    && strpos($inventarioDocCode, 'getProductosDescuentoReservacion') !== false
                    && strpos($inventarioDocCode, 'MEDISOFT_ALLOW_LEGACY_INVENTORY_GUIDE_WRITES') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2D.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2D.',
                        'Actualizar docs/technical/inventory_reconciliation.md con prueba rollback, preview y guardas legacy.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2E') !== false
                    && strpos($inventarioDocCode, 'inventario.configuracion_actualizada') !== false
                    && strpos($inventarioDocCode, 'purchasing_inventory_contract.md') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2E.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2E.',
                        'Actualizar docs/technical/inventory_reconciliation.md con auditoria de configuracion y contrato de compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2F') !== false
                    && strpos($inventarioDocCode, 'proveedores') !== false
                    && strpos($inventarioDocCode, 'sin compras') !== false
                ) {
                    hcOk('Documentacion de inventario registra separacion Fase 2F de proveedores vs compras.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2F.',
                        'Actualizar docs/technical/inventory_reconciliation.md con alcance de proveedores sin compras ni caja.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2G') !== false
                    && strpos($inventarioDocCode, 'sidebar') !== false
                    && strpos($inventarioDocCode, 'gate visual de `inventario`') !== false
                ) {
                    hcOk('Documentacion de inventario registra exposicion controlada Fase 2G.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2G.',
                        'Actualizar docs/technical/inventory_reconciliation.md con enlace de sidebar bajo Inventario y sin compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2H') !== false
                    && strpos($inventarioDocCode, "require_hotel_module('inventario')") !== false
                    && strpos($inventarioDocCode, 'No se crea modulo `proveedores`') !== false
                ) {
                    hcOk('Documentacion de inventario registra gate Fase 2H de proveedores.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2H.',
                        'Actualizar docs/technical/inventory_reconciliation.md con gate de modulo inventario para Proveedores.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2I') !== false
                    && strpos($inventarioDocCode, 'RFC no debe repetirse') !== false
                    && strpos($inventarioDocCode, 'No se agrega indice unico') !== false
                ) {
                    hcOk('Documentacion de inventario registra validacion Fase 2I de duplicados.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2I.',
                        'Actualizar docs/technical/inventory_reconciliation.md con validacion de duplicados sin migracion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2J') !== false
                    && strpos($inventarioDocCode, 'uk_proveedores_hotel_rfc') !== false
                    && strpos($inventarioDocCode, 'nombre_activo_key') !== false
                ) {
                    hcOk('Documentacion de inventario registra constraints SQL Fase 2J.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2J.',
                        'Actualizar docs/technical/inventory_reconciliation.md con indices unicos SQL de proveedores.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2K') !== false
                    && strpos($inventarioDocCode, 'reconciliar_proveedores_dry_run.php') !== false
                    && strpos($inventarioDocCode, 'No inserta proveedores') !== false
                ) {
                    hcOk('Documentacion de inventario registra dry-run Fase 2K de proveedores historicos.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2K.',
                        'Actualizar docs/technical/inventory_reconciliation.md con dry-run de proveedores historicos sin importacion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2L') !== false
                    && strpos($inventarioDocCode, 'importar_proveedores_los_cedros.php') !== false
                    && strpos($inventarioDocCode, '--backup-file') !== false
                ) {
                    hcOk('Documentacion de inventario registra importador revisable Fase 2L.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2L.',
                        'Actualizar docs/technical/inventory_reconciliation.md con importador revisable y backup obligatorio.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2M') !== false
                    && strpos($inventarioDocCode, 'preflight_compras_minimas.php') !== false
                    && strpos($inventarioDocCode, 'No crea tablas `compras`') !== false
                ) {
                    hcOk('Documentacion de inventario registra preflight Fase 2M de compras minimas.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2M.',
                        'Actualizar docs/technical/inventory_reconciliation.md con preflight de compras minimas sin rutas ni tablas nuevas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2N') !== false
                    && strpos($inventarioDocCode, '20260615_002_fase_2n_compras_minimas_draft.sql') !== false
                    && strpos($inventarioDocCode, 'No se ejecuta contra ninguna base') !== false
                ) {
                    hcOk('Documentacion de inventario registra borrador SQL Fase 2N.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2N.',
                        'Actualizar docs/technical/inventory_reconciliation.md con borrador SQL no ejecutado de compras minimas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2O') !== false
                    && strpos($inventarioDocCode, '20260615_002_fase_2n_compras_minimas.sql') !== false
                    && strpos($inventarioDocCode, 'No se genero ningun `movimientos_inventario`') !== false
                ) {
                    hcOk('Documentacion de inventario registra migracion aplicada Fase 2O.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2O.',
                        'Actualizar docs/technical/inventory_reconciliation.md con migracion aplicada, tablas vacias y sin recepcion.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2P') !== false
                    && strpos($inventarioDocCode, 'CompraService.php') !== false
                    && strpos($inventarioDocCode, 'Sin rutas') !== false
                    && strpos($inventarioDocCode, 'No se genera ningun movimiento de inventario') !== false
                ) {
                    hcOk('Documentacion de inventario registra servicio interno Fase 2P.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2P.',
                        'Actualizar docs/technical/inventory_reconciliation.md con servicio interno sin rutas ni movimientos ejecutados.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Q') !== false
                    && strpos($inventarioDocCode, 'probar_compra_service.php') !== false
                    && strpos($inventarioDocCode, 'ROLLBACK_TEST') !== false
                    && strpos($inventarioDocCode, 'rollback obligatorio') !== false
                ) {
                    hcOk('Documentacion de inventario registra prueba CLI Fase 2Q.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Q.',
                        'Actualizar docs/technical/inventory_reconciliation.md con prueba CLI, rollback y restauracion de stock/conteos.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2R') !== false
                    && strpos($inventarioDocCode, 'CompraController.php') !== false
                    && strpos($inventarioDocCode, 'POST /compras') !== false
                    && strpos($inventarioDocCode, 'No modifica stock') !== false
                ) {
                    hcOk('Documentacion de inventario registra UI minima Fase 2R.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2R.',
                        'Actualizar docs/technical/inventory_reconciliation.md con UI de borradores sin recepcion ni stock.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2S') !== false
                    && strpos($inventarioDocCode, 'compras_no_borrador = 0') !== false
                    && strpos($inventarioDocCode, 'detalles_con_movimiento = 0') !== false
                    && strpos($inventarioDocCode, 'movimientos_recepcion_compra = 0') !== false
                ) {
                    hcOk('Documentacion de inventario registra cierre Fase 2S.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2S.',
                        'Actualizar docs/technical/inventory_reconciliation.md con cierre de borrador real sin stock ni movimientos.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2T') !== false
                    && strpos($inventarioDocCode, 'preflight_recepcion_compras.php') !== false
                    && strpos($inventarioDocCode, 'productos repetidos') !== false
                    && strpos($inventarioDocCode, 'sin recibir compras') !== false
                ) {
                    hcOk('Documentacion de inventario registra preflight Fase 2T.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2T.',
                        'Actualizar docs/technical/inventory_reconciliation.md con preflight de recepcion sin recibir compras.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2U') !== false
                    && strpos($inventarioDocCode, '--duplicate-line') !== false
                    && strpos($inventarioDocCode, 'un movimiento por linea') !== false
                    && strpos($inventarioDocCode, 'rollback') !== false
                ) {
                    hcOk('Documentacion de inventario registra regla Fase 2U para productos repetidos.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2U.',
                        'Actualizar docs/technical/inventory_reconciliation.md con regla de productos repetidos y prueba con rollback.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2V') !== false
                    && strpos($inventarioDocCode, 'POST /compras/{id}/recibir') !== false
                    && strpos($inventarioDocCode, 'CompraController::recibirAction()') !== false
                    && strpos($inventarioDocCode, 'CompraService::recibirCompra()') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra recepcion minima Fase 2V.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2V.',
                        'Actualizar docs/technical/inventory_reconciliation.md con recepcion minima sin caja, pagos ni CxP.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2W') !== false
                    && strpos($inventarioDocCode, 'compra #2') !== false
                    && strpos($inventarioDocCode, 'movimiento_inventario_id') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra recepcion real controlada Fase 2W.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2W.',
                        'Actualizar docs/technical/inventory_reconciliation.md con compra #2 recibida, movimientos vinculados y exclusiones.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2X') !== false
                    && strpos($inventarioDocCode, 'GET /compras/{id}') !== false
                    && strpos($inventarioDocCode, 'CompraController::verAction()') !== false
                    && strpos($inventarioDocCode, 'movimiento_inventario_id') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra detalle read-only Fase 2X.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2X.',
                        'Actualizar docs/technical/inventory_reconciliation.md con detalle read-only de compra y movimientos vinculados.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Y') !== false
                    && strpos($inventarioDocCode, 'GET /compras/reportes/recibidas') !== false
                    && strpos($inventarioDocCode, 'CompraController::reporteRecibidasAction()') !== false
                    && strpos($inventarioDocCode, 'CompraService::reporteRecibidas()') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra reportes read-only Fase 2Y.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Y.',
                        'Actualizar docs/technical/inventory_reconciliation.md con reporte read-only de compras recibidas.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 2Z') !== false
                    && strpos($inventarioDocCode, 'assertCompraPuedeRecibirse()') !== false
                    && strpos($inventarioDocCode, 'assertDetallesPuedenRecibirse()') !== false
                    && strpos($inventarioDocCode, 'un movimiento por linea') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra endurecimiento Fase 2Z.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 2Z.',
                        'Actualizar docs/technical/inventory_reconciliation.md con guardas de recepcion y navegacion read-only.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3A') !== false
                    && strpos($inventarioDocCode, 'ProveedorController::verAction()') !== false
                    && strpos($inventarioDocCode, 'Proveedor::resumenComprasPorProveedor()') !== false
                    && strpos($inventarioDocCode, 'Proveedor::comprasRecientesPorProveedor()') !== false
                    && strpos($inventarioDocCode, 'GET /proveedores/{id}') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Documentacion de inventario registra proveedores v2 read-only Fase 3A.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 3A.',
                        'Actualizar docs/technical/inventory_reconciliation.md con ficha read-only de proveedor e historial.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3B draft') !== false
                    && strpos($inventarioDocCode, '20260615_003_fase_3b_cxp_base_draft.sql') !== false
                    && strpos($inventarioDocCode, 'No se ejecuta SQL contra la base') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                    && strpos($inventarioDocCode, 'Sin rutas activas de CxP') !== false
                ) {
                    hcOk('Documentacion de inventario registra draft no ejecutado Fase 3B de CxP.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun draft Fase 3B.',
                        'Actualizar docs/technical/inventory_reconciliation.md con CxP base no ejecutada y exclusiones.'
                    );
                }

                if (
                    strpos($inventarioDocCode, 'Fase 3B aplicada') !== false
                    && strpos($inventarioDocCode, '20260615_003_fase_3b_cxp_base.sql') !== false
                    && strpos($inventarioDocCode, 'CuentaPorPagarController') !== false
                    && strpos($inventarioDocCode, 'GET /cuentas-por-pagar') !== false
                    && strpos($inventarioDocCode, 'Sin movimientos de caja') !== false
                    && strpos($inventarioDocCode, 'Sin formularios POST') !== false
                ) {
                    hcOk('Documentacion de inventario registra CxP read-only Fase 3B aplicada.');
                } else {
                    hcWarning(
                        'Documentacion de inventario no registra aun Fase 3B aplicada.',
                        'Actualizar docs/technical/inventory_reconciliation.md con migracion, rutas read-only y exclusiones.'
                    );
                }
            }

            if ($purchasingInventoryDoc === null || !is_file($purchasingInventoryDoc)) {
                hcWarning(
                    'No se pudo validar contrato de compras porque docs/technical no esta montado o falta el documento.',
                    'Crear docs/technical/purchasing_inventory_contract.md antes de implementar Proveedores/Compras.'
                );
            } else {
                $purchasingDocCode = (string) file_get_contents($purchasingInventoryDoc);
                if (
                    strpos($purchasingDocCode, 'Contrato de recepcion de compras') !== false
                    && strpos($purchasingDocCode, 'inventario_productos') !== false
                    && strpos($purchasingDocCode, 'movimientos_inventario') !== false
                    && strpos($purchasingDocCode, 'ENTRADA') !== false
                    && strpos($purchasingDocCode, 'No implementar todavia') !== false
                ) {
                    hcOk('Contrato Fase 2E de Compras/Inventario documentado.');
                } else {
                    hcWarning(
                        'Contrato de compras hacia inventario esta incompleto.',
                        'Documentar recepcion, tablas futuras, estados, relacion con caja/CxP/documentos y auditoria.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2F') !== false
                    && strpos($purchasingDocCode, '20260614_004_fase_2f_catalogo_proveedores.sql') !== false
                    && strpos($purchasingDocCode, 'Sin compras') !== false
                ) {
                    hcOk('Contrato de compras documenta implementacion minima Fase 2F de proveedores.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra implementacion Fase 2F.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con tabla proveedores, auditoria y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2G') !== false
                    && strpos($purchasingDocCode, 'sidebar') !== false
                    && strpos($purchasingDocCode, 'Sin compras') !== false
                ) {
                    hcOk('Contrato de compras documenta exposicion minima Fase 2G.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra exposicion Fase 2G.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con enlace de Proveedores y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2H') !== false
                    && strpos($purchasingDocCode, "require_hotel_module('inventario')") !== false
                    && strpos($purchasingDocCode, 'Sin modulo nuevo `proveedores`') !== false
                ) {
                    hcOk('Contrato de compras documenta gate minimo Fase 2H.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra gate Fase 2H.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con proteccion de Proveedores por modulo inventario.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2I') !== false
                    && strpos($purchasingDocCode, 'RFC repetido por hotel') !== false
                    && strpos($purchasingDocCode, 'Sin indice unico nuevo') !== false
                ) {
                    hcOk('Contrato de compras documenta validacion minima Fase 2I.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra validacion Fase 2I.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con reglas de duplicados en Proveedores.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2J') !== false
                    && strpos($purchasingDocCode, '20260615_001_fase_2j_unique_proveedores.sql') !== false
                    && strpos($purchasingDocCode, 'uk_proveedores_hotel_nombre_activo') !== false
                ) {
                    hcOk('Contrato de compras documenta constraints SQL Fase 2J.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra constraints Fase 2J.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con migracion e indices unicos de Proveedores.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2K') !== false
                    && strpos($purchasingDocCode, 'reconciliar_proveedores_dry_run.php') !== false
                    && strpos($purchasingDocCode, 'Sin inserts en `proveedores`') !== false
                ) {
                    hcOk('Contrato de compras documenta dry-run Fase 2K de proveedores.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra dry-run Fase 2K.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con preparacion de reconciliacion sin inserts.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2L') !== false
                    && strpos($purchasingDocCode, 'importar_proveedores_los_cedros.php') !== false
                    && strpos($purchasingDocCode, 'Sin actualizacion de `movimientos_caja`') !== false
                ) {
                    hcOk('Contrato de compras documenta importador revisable Fase 2L.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra importador Fase 2L.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con importador revisable sin Caja ni CxP.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2M') !== false
                    && strpos($purchasingDocCode, 'preflight_compras_minimas.php') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                    && strpos($purchasingDocCode, 'Sin cuentas por pagar') !== false
                ) {
                    hcOk('Contrato de compras documenta preflight Fase 2M.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra preflight Fase 2M.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con preparacion de compras minimas sin Caja, CxP ni documentos.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2N') !== false
                    && strpos($purchasingDocCode, '20260615_002_fase_2n_compras_minimas_draft.sql') !== false
                    && strpos($purchasingDocCode, 'No ejecutar desde `docs/technical/sql_drafts`') !== false
                ) {
                    hcOk('Contrato de compras documenta borrador SQL Fase 2N.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra borrador SQL Fase 2N.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con migracion borrador no ejecutada y rollback futuro.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2O') !== false
                    && strpos($purchasingDocCode, 'phase2o_20260615_013321_medisoft_hoteles_import.sql') !== false
                    && strpos($purchasingDocCode, 'Sin rutas nuevas') !== false
                ) {
                    hcOk('Contrato de compras documenta migracion aplicada Fase 2O.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra migracion aplicada Fase 2O.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con backup, migracion, tablas vacias y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2P') !== false
                    && strpos($purchasingDocCode, 'CompraService.php') !== false
                    && strpos($purchasingDocCode, 'Sin rutas publicas') !== false
                ) {
                    hcOk('Contrato de compras documenta servicio interno Fase 2P.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra servicio interno Fase 2P.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con CompraService y exclusiones de rutas/UI/Caja/CxP.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Q') !== false
                    && strpos($purchasingDocCode, 'probar_compra_service.php') !== false
                    && strpos($purchasingDocCode, 'ROLLBACK_TEST') !== false
                    && strpos($purchasingDocCode, 'no contiene `commit()`') !== false
                ) {
                    hcOk('Contrato de compras documenta prueba CLI Fase 2Q.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra prueba CLI Fase 2Q.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con script, backup obligatorio y rollback test.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2R') !== false
                    && strpos($purchasingDocCode, 'CompraController.php') !== false
                    && strpos($purchasingDocCode, 'POST /compras') !== false
                    && strpos($purchasingDocCode, 'Sin recepcion') !== false
                ) {
                    hcOk('Contrato de compras documenta UI minima Fase 2R.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra UI minima Fase 2R.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con rutas/vistas de borrador y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2S') !== false
                    && strpos($purchasingDocCode, 'compras_no_borrador = 0') !== false
                    && strpos($purchasingDocCode, 'detalles_con_movimiento = 0') !== false
                    && strpos($purchasingDocCode, 'movimientos_recepcion_compra = 0') !== false
                ) {
                    hcOk('Contrato de compras documenta cierre Fase 2S.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra cierre Fase 2S.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con la validacion del borrador real.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2T') !== false
                    && strpos($purchasingDocCode, 'preflight_recepcion_compras.php') !== false
                    && strpos($purchasingDocCode, 'productos repetidos') !== false
                    && strpos($purchasingDocCode, 'Sin recepcion') !== false
                ) {
                    hcOk('Contrato de compras documenta preflight Fase 2T.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra preflight Fase 2T.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con validacion previa a recepcion.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2U') !== false
                    && strpos($purchasingDocCode, '--duplicate-line') !== false
                    && strpos($purchasingDocCode, 'un movimiento por linea') !== false
                    && strpos($purchasingDocCode, 'ROLLBACK_TEST') !== false
                ) {
                    hcOk('Contrato de compras documenta regla Fase 2U para productos repetidos.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra regla Fase 2U.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con regla de productos repetidos y prueba rollback.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2V') !== false
                    && strpos($purchasingDocCode, 'POST /compras/{id}/recibir') !== false
                    && strpos($purchasingDocCode, 'CompraController::recibirAction()') !== false
                    && strpos($purchasingDocCode, 'CompraService::recibirCompra()') !== false
                    && strpos($purchasingDocCode, 'No registra pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta recepcion minima Fase 2V.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra recepcion minima Fase 2V.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta/action/servicio y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2W') !== false
                    && strpos($purchasingDocCode, 'compra #2') !== false
                    && strpos($purchasingDocCode, 'movimiento_inventario_id') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                    && strpos($purchasingDocCode, 'Sin cuentas por pagar') !== false
                ) {
                    hcOk('Contrato de compras documenta recepcion real controlada Fase 2W.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra recepcion real controlada Fase 2W.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con compra #2, movimientos, auditoria y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2X') !== false
                    && strpos($purchasingDocCode, 'GET /compras/{id}') !== false
                    && strpos($purchasingDocCode, 'CompraController::verAction()') !== false
                    && strpos($purchasingDocCode, 'compras/ver.php') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta detalle read-only Fase 2X.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra detalle read-only Fase 2X.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta, vista, movimientos mostrados y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Y') !== false
                    && strpos($purchasingDocCode, 'GET /compras/reportes/recibidas') !== false
                    && strpos($purchasingDocCode, 'CompraController::reporteRecibidasAction()') !== false
                    && strpos($purchasingDocCode, 'CompraService::reporteRecibidas()') !== false
                    && strpos($purchasingDocCode, 'compras/reporte_recibidas.php') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta reporte read-only Fase 2Y.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra reporte read-only Fase 2Y.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ruta, vista, filtros, agregados y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 2Z') !== false
                    && strpos($purchasingDocCode, 'assertCompraPuedeRecibirse()') !== false
                    && strpos($purchasingDocCode, 'assertDetallesPuedenRecibirse()') !== false
                    && strpos($purchasingDocCode, 'recepcion doble') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta endurecimiento Fase 2Z.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra endurecimiento Fase 2Z.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con guardas de recepcion doble, estados y detalles invalidos.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3A') !== false
                    && strpos($purchasingDocCode, 'GET /proveedores/{id}') !== false
                    && strpos($purchasingDocCode, 'ProveedorController::verAction()') !== false
                    && strpos($purchasingDocCode, 'Proveedor::resumenComprasPorProveedor()') !== false
                    && strpos($purchasingDocCode, 'Proveedor::comprasRecientesPorProveedor()') !== false
                    && strpos($purchasingDocCode, 'Sin pagos') !== false
                ) {
                    hcOk('Contrato de compras documenta proveedores v2 read-only Fase 3A.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra proveedores v2 Fase 3A.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con ficha read-only, ruta y exclusiones.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3B draft') !== false
                    && strpos($purchasingDocCode, '20260615_003_fase_3b_cxp_base_draft.sql') !== false
                    && strpos($purchasingDocCode, 'No se ejecuta contra ninguna base de datos') !== false
                    && strpos($purchasingDocCode, 'uk_cxp_hotel_compra') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Contrato de compras documenta draft no ejecutado Fase 3B de CxP.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra draft Fase 3B.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con draft CxP, no ejecucion y promocion futura.'
                    );
                }

                if (
                    strpos($purchasingDocCode, 'Actualizacion Fase 3B aplicada') !== false
                    && strpos($purchasingDocCode, 'phase3b_20260615_040742_before_cxp_medisoft_hoteles_import.sql') !== false
                    && strpos($purchasingDocCode, '20260615_003_fase_3b_cxp_base.sql') !== false
                    && strpos($purchasingDocCode, 'CuentaPorPagarController') !== false
                    && strpos($purchasingDocCode, 'GET /cuentas-por-pagar') !== false
                    && strpos($purchasingDocCode, 'Sin movimientos de caja') !== false
                ) {
                    hcOk('Contrato de compras documenta CxP read-only Fase 3B aplicada.');
                } else {
                    hcWarning(
                        'Contrato de compras no registra Fase 3B aplicada.',
                        'Actualizar docs/technical/purchasing_inventory_contract.md con backup, migracion, rutas read-only y exclusiones.'
                    );
                }
            }
        } else {
            hcError(
                'inventario/ajuste/{id} esta registrado parcialmente. GET=' . ($ajusteGet ? 'si' : 'no') . ', POST=' . ($ajustePost ? 'si' : 'no') . '.',
                'Registrar GET y POST juntos o mantener el flujo sin ruta.'
            );
        }
    }

    $documentoControllerPath = $controllersDir . '/DocumentoController.php';
    $documentoControllerCode = is_file($documentoControllerPath)
        ? (string) file_get_contents($documentoControllerPath)
        : '';

    if (hcRoutePatternExists($routes, 'documentos/1/descargar', 'get')) {
        hcOk('Ruta documental segura registrada: GET /documentos/{id}/descargar.');
    } else {
        hcWarning(
            'Ruta documental segura GET /documentos/{id}/descargar no esta registrada.',
            'Restaurar la ruta solo si Fase 4B-A sigue vigente.'
        );
    }

    if (
        $documentoControllerCode !== ''
        && strpos($documentoControllerCode, 'documentos.descargado') !== false
        && strpos($documentoControllerCode, 'documentos.descarga_bloqueada') !== false
        && strpos($documentoControllerCode, 'AuditService::record') !== false
    ) {
        hcOk('Centro Documental Fase 4B-B audita descargas exitosas y bloqueadas con AuditService.');
    } elseif ($documentoControllerCode !== '') {
        hcWarning(
            'Centro Documental no muestra auditoria completa Fase 4B-B.',
            'Registrar documentos.descargado y documentos.descarga_bloqueada con AuditService sin exponer storage_path.'
        );
    }

    $documentoModelPath = $appRoot . '/app/models/Documento.php';
    $documentoModelCode = is_file($documentoModelPath)
        ? (string) file_get_contents($documentoModelPath)
        : '';
    $documentoEditViewPath = $appRoot . '/app/views/documentos/editar.php';
    $documentoEditViewCode = is_file($documentoEditViewPath)
        ? (string) file_get_contents($documentoEditViewPath)
        : '';

    if (
        $documentoModelCode !== ''
        && strpos($documentoModelCode, "'trabajador'") !== false
        && strpos($documentoModelCode, "'trabajador' => 'trabajadores'") !== false
        && strpos($documentoControllerCode, "'trabajador' => 'Trabajador'") !== false
        && strpos($documentoControllerCode, "'usuarios'") !== false
    ) {
        hcOk('Centro Documental NP-D-A reconoce trabajador como entidad moderna validada por hotel_id.');
    } elseif ($documentoModelCode !== '') {
        hcWarning(
            'Centro Documental no reconoce trabajador como entidad moderna completa.',
            'Agregar trabajador en Documento::ENTIDAD_TIPOS, validar contra trabajadores y etiquetar la entidad sin usar trabajador_documentos como flujo nuevo.'
        );
    }

    if (
        hcRoutePatternExists($routes, 'documentos/1/editar', 'get')
        && hcRoutePatternExists($routes, 'documentos/1/actualizar', 'post')
    ) {
        hcOk('Rutas Fase 4B-C de metadata documental registradas: GET editar y POST actualizar.');
    } else {
        hcWarning(
            'Rutas Fase 4B-C de metadata documental incompletas.',
            'Registrar GET /documentos/{id}/editar y POST /documentos/{id}/actualizar solo si la fase esta autorizada.'
        );
    }

    if (
        $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, 'actualizarMetadata') !== false
        && strpos($documentoModelCode, 'documentos.metadata_actualizada') !== false
        && $documentoEditViewCode !== ''
        && strpos($documentoEditViewCode, 'storage_path') === false
        && strpos($documentoEditViewCode, 'nombre_archivo') === false
    ) {
        hcOk('Centro Documental Fase 4B-C actualiza metadata con CSRF y auditoria diferencial sin editar archivo/storage.');
    } elseif ($documentoModelCode !== '') {
        hcWarning(
            'Centro Documental no muestra contrato completo Fase 4B-C.',
            'Validar metadata en modelo, usar CSRF y auditar documentos.metadata_actualizada sin exponer storage_path.'
        );
    }

    $documentoDetailViewPath = $appRoot . '/app/views/documentos/ver.php';
    $documentoDetailViewCode = is_file($documentoDetailViewPath)
        ? (string) file_get_contents($documentoDetailViewPath)
        : '';

    if (
        hcRoutePatternExists($routes, 'documentos/1/archivar', 'post')
        && hcRoutePatternExists($routes, 'documentos/1/restaurar', 'post')
        && $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && $documentoDetailViewCode !== ''
        && strpos($documentoControllerCode, 'archivarAction') !== false
        && strpos($documentoControllerCode, 'restaurarAction') !== false
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, 'actualizarEstado') !== false
        && strpos($documentoModelCode, 'documentos.estado_actualizado') !== false
        && strpos($documentoModelCode, "'activo' => ['archivado") !== false
        && strpos($documentoModelCode, "'archivado' => ['activo") !== false
        && strpos($documentoDetailViewCode, "method=\"POST\"") !== false
        && strpos($documentoDetailViewCode, "csrf_field()") !== false
        && strpos($documentoDetailViewCode, "/archivar") !== false
        && strpos($documentoDetailViewCode, "/restaurar") !== false
        && strpos($documentoModelCode, 'DELETE FROM documentos') === false
    ) {
        hcOk('Centro Documental Fase 4D-A archiva/restaura con POST, CSRF, transiciones centrales y auditoria sin borrar archivos.');
    } else {
        hcWarning(
            'Centro Documental Fase 4D-A no muestra contrato completo de archivado reversible.',
            'Validar POST /documentos/{id}/archivar y /restaurar, CSRF, Documento::actualizarEstado(), auditoria documentos.estado_actualizado y ausencia de borrado fisico.'
        );
    }

    if (
        hcRoutePatternExists($routes, 'documentos/1/eliminar', 'post')
        && $documentoControllerCode !== ''
        && $documentoModelCode !== ''
        && $documentoDetailViewCode !== ''
        && strpos($documentoControllerCode, 'eliminarAction') !== false
        && strpos($documentoControllerCode, "cambiarEstadoAction('eliminado'") !== false
        && strpos($documentoControllerCode, 'validateCSRF') !== false
        && strpos($documentoModelCode, "'activo' => ['archivado', 'eliminado']") !== false
        && strpos($documentoModelCode, "'archivado' => ['activo', 'eliminado']") !== false
        && strpos($documentoModelCode, "'activo', 'archivado', 'eliminado'") !== false
        && strpos($documentoDetailViewCode, "/eliminar") !== false
        && strpos($documentoDetailViewCode, 'Baja logica') !== false
        && strpos($documentoDetailViewCode, "csrf_field()") !== false
        && strpos($documentoModelCode, 'DELETE FROM documentos') === false
    ) {
        hcOk('Centro Documental Fase 4D-B-A baja logicamente documentos con POST, CSRF, transiciones centrales y sin borrar archivos.');
    } elseif ($documentoModelCode !== '') {
        hcWarning(
            'Centro Documental Fase 4D-B-A no muestra contrato completo de baja logica.',
            'Validar POST /documentos/{id}/eliminar, CSRF, transiciones hacia eliminado, auditoria y ausencia de DELETE/borrado fisico.'
        );
    }

    $documentosEntidadPartialPath = $appRoot . '/app/views/partials/documentos_entidad.php';
    $documentosEntidadPartialCode = is_file($documentosEntidadPartialPath)
        ? (string) file_get_contents($documentosEntidadPartialPath)
        : '';

    if (
        $documentosEntidadPartialCode !== ''
        && strpos($documentosEntidadPartialCode, 'Lista segura') !== false
        && strpos($documentosEntidadPartialCode, 'Vincular documento') !== false
        && strpos($documentosEntidadPartialCode, "documentos/subir' . \$entityQuery") !== false
        && strpos($documentosEntidadPartialCode, 'storage_path') === false
        && strpos($documentosEntidadPartialCode, 'nombre_archivo') === false
        && strpos($documentosEntidadPartialCode, 'documentos/\' . $documentoId') !== false
        && strpos($documentosEntidadPartialCode, '<form') === false
        && strpos($documentosEntidadPartialCode, 'method=') === false
        && strpos($documentosEntidadPartialCode, '/editar') === false
        && strpos($documentosEntidadPartialCode, '/actualizar') === false
    ) {
        hcOk('Centro Documental Fase 4C-A tiene partial contextual por entidad sin storage_path, formularios, edicion ni POST nuevo.');
    } else {
        hcWarning(
            'Centro Documental Fase 4C-A no muestra partial contextual completo por entidad.',
            'Validar src/app/views/partials/documentos_entidad.php: sin storage_path/nombre_archivo, sin formularios, sin editar/actualizar y solo enlaces GET seguros.'
        );
    }

    $documentosEntidadViews = [
        'proveedor' => $appRoot . '/app/views/proveedores/ver.php',
        'compra' => $appRoot . '/app/views/compras/ver.php',
        'cuenta_por_pagar' => $appRoot . '/app/views/cuentas_por_pagar/ver.php',
        'huesped' => $appRoot . '/app/views/huespedes/ver.php',
        'reservacion' => $appRoot . '/app/views/reservaciones/ver.php',
        'trabajador' => $appRoot . '/app/views/trabajadores/ver.php',
    ];
    $viewsConPartial = [];
    foreach ($documentosEntidadViews as $entidadTipo => $viewPath) {
        $viewCode = is_file($viewPath) ? (string) file_get_contents($viewPath) : '';
        if (strpos($viewCode, "View::partial('documentos_entidad'") !== false) {
            $viewsConPartial[] = $entidadTipo;
        }
    }

    if (count($viewsConPartial) === count($documentosEntidadViews)) {
        hcOk('Fichas de proveedor, compra, CxP, huesped, reservacion y trabajador incluyen seccion documental contextual.');
    } else {
        $faltantes = array_diff(array_keys($documentosEntidadViews), $viewsConPartial);
        hcWarning(
            'Faltan fichas con seccion documental contextual 4C-A: ' . implode(', ', $faltantes),
            'Agregar View::partial("documentos_entidad") solo en fichas autorizadas y sin formularios nuevos.'
        );
    }

    $documentosEntidadControllers = [
        'proveedor' => $controllersDir . '/ProveedorController.php',
        'compra' => $controllersDir . '/CompraController.php',
        'cuenta_por_pagar' => $controllersDir . '/CuentaPorPagarController.php',
        'huesped' => $controllersDir . '/HuespedController.php',
        'reservacion' => $controllersDir . '/ReservacionController.php',
        'trabajador' => $controllersDir . '/TrabajadorController.php',
    ];
    $controllersConConsulta = [];
    foreach ($documentosEntidadControllers as $entidadTipo => $controllerPath) {
        $controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';
        if (
            strpos($controllerCode, 'Documento') !== false
            && strpos($controllerCode, 'documentosPorEntidad') !== false
            && strpos($controllerCode, "'" . $entidadTipo . "'") !== false
        ) {
            $controllersConConsulta[] = $entidadTipo;
        }
    }

    if (count($controllersConConsulta) === count($documentosEntidadControllers)) {
        hcOk('Controladores 4C-A consultan documentos por entidad con Documento::documentosPorEntidad().');
    } else {
        $faltantes = array_diff(array_keys($documentosEntidadControllers), $controllersConConsulta);
        hcWarning(
            'Faltan consultas documentales por entidad en controladores: ' . implode(', ', $faltantes),
            'Usar Documento::documentosPorEntidad($hotelId, $entidadTipo, $id) y pasar solo metadata segura a la vista.'
        );
    }

    $opControllerPath = $controllersDir . '/OperacionController.php';
    $opModelPath = $appRoot . '/app/models/OperacionDiaria.php';
    $opViewPath = $appRoot . '/app/views/operacion/diaria.php';
    $opPreflightPath = $appRoot . '/tools/saas/preflight_operacion_diaria.php';
    $cashMethodPreflightPath = $appRoot . '/tools/saas/preflight_arqueo_metodos_pago.php';
    $executivePreflightPath = $appRoot . '/tools/saas/preflight_tablero_ejecutivo.php';
    $executiveReportesControllerPath = $controllersDir . '/ReportesController.php';
    $executiveNotificacionControllerPath = $controllersDir . '/NotificacionController.php';
    $executiveModelPath = $appRoot . '/app/models/TableroEjecutivo.php';
    $executiveViewPath = $appRoot . '/app/views/reportes/ejecutivo.php';
    $reportesIndexViewPath = $appRoot . '/app/views/reportes/index.php';
    $guestControllerPath = $controllersDir . '/HuespedController.php';
    $guestModelPath = $appRoot . '/app/models/Huesped.php';
    $guestViewPath = $appRoot . '/app/views/huespedes/ver.php';
    $cashControllerPath = $controllersDir . '/CajaController.php';
    $cashArqueoModelPath = $appRoot . '/app/models/ArqueoMetodosPago.php';
    $cashArqueoViewPath = $appRoot . '/app/views/caja/arqueo_metodos.php';
    $cfinModelPath = $appRoot . '/app/models/ConciliacionFinanciera.php';
    $cfinViewPath = $appRoot . '/app/views/operacion/conciliacion_financiera.php';
    $opControllerCode = is_file($opControllerPath) ? (string) file_get_contents($opControllerPath) : '';
    $opModelCode = is_file($opModelPath) ? (string) file_get_contents($opModelPath) : '';
    $opViewCode = is_file($opViewPath) ? (string) file_get_contents($opViewPath) : '';
    $cashMethodPreflightCode = is_file($cashMethodPreflightPath) ? (string) file_get_contents($cashMethodPreflightPath) : '';
    $executivePreflightCode = is_file($executivePreflightPath) ? (string) file_get_contents($executivePreflightPath) : '';
    $executiveReportesControllerCode = is_file($executiveReportesControllerPath) ? (string) file_get_contents($executiveReportesControllerPath) : '';
    $executiveNotificacionControllerCode = is_file($executiveNotificacionControllerPath) ? (string) file_get_contents($executiveNotificacionControllerPath) : '';
    $executiveModelCode = is_file($executiveModelPath) ? (string) file_get_contents($executiveModelPath) : '';
    $executiveViewCode = is_file($executiveViewPath) ? (string) file_get_contents($executiveViewPath) : '';
    $reportesIndexViewCode = is_file($reportesIndexViewPath) ? (string) file_get_contents($reportesIndexViewPath) : '';
    $guestControllerCode = is_file($guestControllerPath) ? (string) file_get_contents($guestControllerPath) : '';
    $guestModelCode = is_file($guestModelPath) ? (string) file_get_contents($guestModelPath) : '';
    $guestViewCode = is_file($guestViewPath) ? (string) file_get_contents($guestViewPath) : '';
    $cashControllerCode = is_file($cashControllerPath) ? (string) file_get_contents($cashControllerPath) : '';
    $cashArqueoModelCode = is_file($cashArqueoModelPath) ? (string) file_get_contents($cashArqueoModelPath) : '';
    $cashArqueoViewCode = is_file($cashArqueoViewPath) ? (string) file_get_contents($cashArqueoViewPath) : '';
    $cashMethodPreflightSqlProbe = preg_replace(
        '/preg_match\s*\([^;]*(INSERT|UPDATE|DELETE|ALTER|DROP|TRUNCATE)[^;]*;/is',
        '',
        $cashMethodPreflightCode
    );
    $cfinModelCode = is_file($cfinModelPath) ? (string) file_get_contents($cfinModelPath) : '';
    $cfinViewCode = is_file($cfinViewPath) ? (string) file_get_contents($cfinViewPath) : '';
    $opSidebarCode = is_file($sidebarPath) ? (string) file_get_contents($sidebarPath) : '';

    if (hcRouteExists($routes, 'operacion/diaria', 'get')) {
        hcOk('Ruta OP-A registrada: GET /operacion/diaria.');
    } else {
        hcWarning(
            'Ruta OP-A GET /operacion/diaria no esta registrada.',
            'Registrar solo GET /operacion/diaria si el tablero operativo read-only sigue vigente.'
        );
    }

    if (hcRouteExists($routes, 'operacion/conciliacion-financiera', 'get')) {
        hcOk('Ruta 9A-B-A registrada: GET /operacion/conciliacion-financiera.');
    } else {
        hcError(
            'Ruta 9A-B-A GET /operacion/conciliacion-financiera no esta registrada.',
            'Restaurar la pantalla read-only de conciliacion financiera o retirar su documentacion de fase.'
        );
    }

    if (hcRouteExists($routes, 'caja/arqueo-metodos', 'get')) {
        hcOk('Ruta 9C-B-A registrada: GET /caja/arqueo-metodos.');
    } else {
        hcError(
            'Ruta 9C-B-A GET /caja/arqueo-metodos no esta registrada.',
            'Restaurar la pantalla read-only de arqueo por metodo o retirar su documentacion de fase.'
        );
    }

    if (!hcRouteExists($routes, 'caja/arqueo-metodos', 'post')) {
        hcOk('9C-B-A no expone POST /caja/arqueo-metodos.');
    } else {
        hcError(
            '9C-B-A expone POST /caja/arqueo-metodos fuera de contrato.',
            'Retirar POST; el arqueo por metodo debe seguir solo lectura.'
        );
    }

    $allowedOpRoutes = [
        'GET /operacion/diaria -> operacion::diaria' => true,
        'GET /operacion/conciliacion-financiera -> operacion::conciliacionfinanciera' => true,
    ];
    $forbiddenOpRoutes = [];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;

        if (($path === 'operacion/diaria' || strpos($path, 'operacion/') === 0 || $controller === 'operacion')
            && !isset($allowedOpRoutes[$signature])) {
            $forbiddenOpRoutes[] = $signature;
        }
    }

    if (empty($forbiddenOpRoutes)) {
        hcOk('OP-A/9A-B-A mantienen rutas GET/read-only y no exponen POST operativos.');
    } else {
        hcError(
            'OP-A/9A-B-A tienen rutas fuera de alcance: ' . implode(' | ', $forbiddenOpRoutes),
            'Retirar rutas OP que no sean GET /operacion/diaria o GET /operacion/conciliacion-financiera.'
        );
    }

    if (
        $opControllerCode !== ''
        && strpos($opControllerCode, 'class OperacionController') !== false
        && strpos($opControllerCode, 'function diariaAction') !== false
        && strpos($opControllerCode, 'require_hotel_context') !== false
        && strpos($opControllerCode, "require_hotel_module('dashboard')") !== false
        && strpos($opControllerCode, 'OperacionDiaria') !== false
        && strpos($opControllerCode, 'operacion/diaria') !== false
    ) {
        hcOk('OperacionController OP-A usa sesion, hotel actual, modulo dashboard y vista read-only.');
    } else {
        hcWarning(
            'OperacionController OP-A no muestra guardas completas.',
            'Validar requireAuth, require_hotel_context, require_hotel_module("dashboard") y render de operacion/diaria.'
        );
    }

    if (
        $opModelCode !== ''
        && strpos($opModelCode, 'class OperacionDiaria') !== false
        && strpos($opModelCode, 'function reporteReadOnlyPorHotel') !== false
        && strpos($opModelCode, 'hotel_id = ?') !== false
        && strpos($opModelCode, 'storage_path') === false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM)\b/i', $opModelCode)
        && strpos($opModelCode, 'movimientos_caja') === false
    ) {
        hcOk('OperacionDiaria OP-A solo lee fuentes modernas con hotel_id y no toca Caja/storage.');
    } else {
        hcError(
            'OperacionDiaria OP-A no cumple contrato read-only.',
            'Retirar escrituras, referencias a Caja/storage_path o consultas sin hotel_id.'
        );
    }

    if (
        $opViewCode !== ''
        && strpos($opViewCode, 'Tablero operativo diario') !== false
        && strpos($opViewCode, '<form') === false
        && strpos($opViewCode, 'method="POST"') === false
        && strpos($opViewCode, 'csrf_field()') === false
        && strpos($opViewCode, 'storage_path') === false
        && strpos($opViewCode, 'movimientos_caja') === false
        && strpos($opViewCode, "url('reservaciones/ver/'") !== false
        && strpos($opViewCode, "url('tareas/'") !== false
    ) {
        hcOk('Vista OP-A es read-only, sin formularios, sin storage_path y con enlaces GET.');
    } else {
        hcWarning(
            'Vista OP-A no muestra contrato visual completo.',
            'Asegurar vista sin formularios/POST/CSRF/storage_path/Caja y con enlaces GET seguros.'
        );
    }

    if (
        $cfinModelCode !== ''
        && strpos($cfinModelCode, 'class ConciliacionFinanciera') !== false
        && strpos($cfinModelCode, 'function reporteReadOnlyPorHotel') !== false
        && strpos($cfinModelCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($cfinModelCode, 'rollBack') !== false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $cfinModelCode)
    ) {
        hcOk('ConciliacionFinanciera 9A-B-A es lector read-only con transaccion y rollback.');
    } else {
        hcError(
            'ConciliacionFinanciera 9A-B-A no cumple contrato read-only.',
            'Mantener lector sin escrituras SQL, con START TRANSACTION READ ONLY y rollback final.'
        );
    }

    if (
        $cfinViewCode !== ''
        && strpos($cfinViewCode, 'Solo lectura') !== false
        && strpos($cfinViewCode, 'method="get"') !== false
        && preg_match('/method\s*=\s*[\'"]post[\'"]/i', $cfinViewCode) !== 1
        && strpos($cfinViewCode, '--brand-') !== false
        && strpos($cfinViewCode, '--ms-') === false
    ) {
        hcOk('Vista 9A-B-A usa filtros GET, solo lectura y branding hotelero.');
    } else {
        hcError(
            'Vista 9A-B-A no cumple contrato visual/read-only.',
            'Usar filtros GET, etiqueta Solo lectura, tokens --brand-* y cero formularios POST.'
        );
    }

    if (
        $opSidebarCode !== ''
        && strpos($opSidebarCode, '$mostrarOperacionDiaria') !== false
        && strpos($opSidebarCode, "url('operacion/diaria')") !== false
    ) {
        hcOk('Sidebar OP-A enlaza tablero operativo solo bajo guardas visuales del modulo dashboard.');
    } else {
        hcWarning(
            'Sidebar OP-A no muestra navegacion del tablero operativo.',
            'Agregar enlace visual solo cuando dashboard este activo.'
        );
    }

    if (is_file($opPreflightPath)) {
        hcOk('Preflight OP-A disponible: tools/saas/preflight_operacion_diaria.php.');
    } else {
        hcWarning(
            'Preflight OP-A no existe.',
            'Crear preflight read-only para validar tablero operativo diario.'
        );
    }

    if (
        $cashMethodPreflightCode !== ''
        && strpos($cashMethodPreflightCode, 'Preflight Fase 9C-A') !== false
        && strpos($cashMethodPreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($cashMethodPreflightCode, 'rollBack') !== false
        && strpos($cashMethodPreflightCode, 'movimientos_caja') !== false
        && strpos($cashMethodPreflightCode, 'cortes_caja') !== false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $cashMethodPreflightSqlProbe ?? $cashMethodPreflightCode)
    ) {
        hcOk('Preflight 9C-A de arqueo por metodo es CLI/read-only con transaccion y rollback.');
    } else {
        hcError(
            'Preflight 9C-A de arqueo por metodo no cumple contrato read-only.',
            'Mantener tools/saas/preflight_arqueo_metodos_pago.php sin escrituras SQL, con READ ONLY y rollback.'
        );
    }

    if (
        $cashControllerCode !== ''
        && strpos($cashControllerCode, 'function arqueoMetodosAction') !== false
        && strpos($cashControllerCode, 'ArqueoMetodosPago') !== false
        && strpos($cashControllerCode, 'reporteReadOnlyPorHotel') !== false
        && strpos($cashControllerCode, "View::renderTemplate('caja/arqueo_metodos'") !== false
        && strpos($cashControllerCode, 'getQuery') !== false
    ) {
        hcOk('CajaController 9C-B-A usa lector dedicado, filtros GET y vista read-only.');
    } else {
        hcError(
            'CajaController 9C-B-A no muestra contrato read-only completo.',
            'Usar ArqueoMetodosPago::reporteReadOnlyPorHotel(), getQuery y render caja/arqueo_metodos.'
        );
    }

    if (
        $cashArqueoModelCode !== ''
        && strpos($cashArqueoModelCode, 'class ArqueoMetodosPago') !== false
        && strpos($cashArqueoModelCode, 'function reporteReadOnlyPorHotel') !== false
        && strpos($cashArqueoModelCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($cashArqueoModelCode, 'rollBack') !== false
        && strpos($cashArqueoModelCode, 'hotel_id') !== false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $cashArqueoModelCode)
    ) {
        hcOk('ArqueoMetodosPago 9C-B-A es lector read-only con hotel_id, transaccion y rollback.');
    } else {
        hcError(
            'ArqueoMetodosPago 9C-B-A no cumple contrato read-only.',
            'Mantener modelo sin escrituras SQL, con START TRANSACTION READ ONLY, rollback y filtro por hotel actual.'
        );
    }

    if (
        $cashArqueoViewCode !== ''
        && strpos($cashArqueoViewCode, 'Solo lectura') !== false
        && strpos($cashArqueoViewCode, 'method="get"') !== false
        && preg_match('/method\s*=\s*[\'"]post[\'"]/i', $cashArqueoViewCode) !== 1
        && strpos($cashArqueoViewCode, 'csrf_field()') === false
        && strpos($cashArqueoViewCode, 'name="hotel_id"') === false
        && strpos($cashArqueoViewCode, '--brand-') !== false
        && strpos($cashArqueoViewCode, '--ms-') === false
    ) {
        hcOk('Vista 9C-B-A usa filtros GET, solo lectura y branding hotelero.');
    } else {
        hcError(
            'Vista 9C-B-A no cumple contrato visual/read-only.',
            'Usar filtros GET, etiqueta Solo lectura, tokens --brand-*, sin CSRF/POST y sin hotel_id editable.'
        );
    }

    if (hcRouteExists($routes, 'reportes/ejecutivo', 'get')) {
        hcOk('Ruta 10A-B-A registrada: GET /reportes/ejecutivo.');
    } else {
        hcError(
            'Ruta 10A-B-A GET /reportes/ejecutivo no esta registrada.',
            'Registrar una unica ruta GET para el tablero ejecutivo read-only.'
        );
    }

    if (!hcRouteExists($routes, 'reportes/ejecutivo', 'post')) {
        hcOk('10A-B-A no expone POST /reportes/ejecutivo.');
    } else {
        hcError(
            '10A-B-A expone POST /reportes/ejecutivo fuera de contrato.',
            'Retirar POST; el tablero ejecutivo debe seguir read-only.'
        );
    }

    if (
        $executivePreflightCode !== ''
        && strpos($executivePreflightCode, 'Preflight Fase 10A-A/10A-B-A') !== false
        && strpos($executivePreflightCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($executivePreflightCode, 'rollBack') !== false
        && strpos($executivePreflightCode, 'reservaciones') !== false
        && strpos($executivePreflightCode, 'cuentas_por_cobrar') !== false
        && strpos($executivePreflightCode, 'cuentas_por_pagar') !== false
        && strpos($executivePreflightCode, 'movimientos_caja') !== false
        && strpos($executivePreflightCode, 'inventario_productos') !== false
        && strpos($executivePreflightCode, 'tareas_operativas') !== false
        && strpos($executivePreflightCode, 'trabajadores') !== false
        && strpos($executivePreflightCode, 'documentos') !== false
        && strpos($executivePreflightCode, 'logs_auditoria') !== false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $executivePreflightCode)
    ) {
        hcOk('Preflight 10A-A/10A-B-A de tablero ejecutivo es CLI/read-only con fuentes transversales y rollback.');
    } else {
        hcError(
            'Preflight 10A-A/10A-B-A de tablero ejecutivo no cumple contrato read-only.',
            'Mantener tools/saas/preflight_tablero_ejecutivo.php sin escrituras SQL, con READ ONLY, fuentes transversales y rollback.'
        );
    }

    $executiveActionBody = $executiveReportesControllerCode !== ''
        ? hcExtractMethodCode($executiveReportesControllerCode, 'ejecutivoAction')
        : '';
    if (
        $executiveActionBody !== ''
        && strpos($executiveReportesControllerCode, 'TableroEjecutivo') !== false
        && strpos($executiveActionBody, 'tableroEjecutivoModel') !== false
        && strpos($executiveActionBody, 'reporteReadOnlyPorHotel') !== false
        && strpos($executiveActionBody, "View::renderTemplate('reportes/ejecutivo'") !== false
        && strpos($executiveActionBody, 'archivarNotificacionReporteGerencialVisto') === false
        && hcCodeBodyIsReadOnly($executiveActionBody)
    ) {
        hcOk('ReportesController 10A-B-A usa lector dedicado, filtros GET y vista read-only.');
    } else {
        hcError(
            'ReportesController 10A-B-A no muestra contrato read-only completo.',
            'Usar TableroEjecutivo::reporteReadOnlyPorHotel(), filtros GET, render reportes/ejecutivo y cero archivado de notificaciones.'
        );
    }

    if (
        $executiveModelCode !== ''
        && strpos($executiveModelCode, 'class TableroEjecutivo') !== false
        && strpos($executiveModelCode, 'function reporteReadOnlyPorHotel') !== false
        && strpos($executiveModelCode, 'START TRANSACTION READ ONLY') !== false
        && strpos($executiveModelCode, 'rollBack') !== false
        && strpos($executiveModelCode, 'hotel_id') !== false
        && strpos($executiveModelCode, 'ledger_laboral') !== false
        && !preg_match('/\b(INSERT\s+INTO|UPDATE|DELETE\s+FROM|ALTER|DROP|TRUNCATE)\b/i', $executiveModelCode)
    ) {
        hcOk('TableroEjecutivo 10A-B-A es lector read-only con hotel_id, transaccion y rollback.');
    } else {
        hcError(
            'TableroEjecutivo 10A-B-A no cumple contrato read-only.',
            'Mantener modelo sin escrituras SQL, con START TRANSACTION READ ONLY, rollback y filtro por hotel actual.'
        );
    }

    if (
        $executiveViewCode !== ''
        && strpos($executiveViewCode, 'Solo lectura') !== false
        && strpos($executiveViewCode, 'method="get"') !== false
        && strpos($executiveViewCode, "url('reportes/ejecutivo')") !== false
        && preg_match('/method\s*=\s*[\'"]post[\'"]/i', $executiveViewCode) !== 1
        && strpos($executiveViewCode, 'csrf_field()') === false
        && strpos($executiveViewCode, 'name="hotel_id"') === false
        && strpos($executiveViewCode, '--brand-') !== false
        && strpos($executiveViewCode, '--ms-') === false
    ) {
        hcOk('Vista 10A-B-A usa filtros GET, solo lectura y branding hotelero.');
    } else {
        hcError(
            'Vista 10A-B-A no cumple contrato visual/read-only.',
            'Usar filtros GET, etiqueta Solo lectura, tokens --brand-*, sin CSRF/POST y sin hotel_id editable.'
        );
    }

    if ($reportesIndexViewCode !== '' && strpos($reportesIndexViewCode, "url('reportes/ejecutivo')") !== false) {
        hcOk('Indice de reportes enlaza 10A-B-A por GET.');
    } else {
        hcWarning(
            'Indice de reportes no enlaza 10A-B-A.',
            'Agregar enlace GET al tablero ejecutivo para QA manual.'
        );
    }

    $executiveGerencialBody = $executiveReportesControllerCode !== ''
        ? hcExtractMethodCode($executiveReportesControllerCode, 'gerencialDiarioAction')
        : '';
    $executiveGerencialPdfBody = $executiveReportesControllerCode !== ''
        ? hcExtractMethodCode($executiveReportesControllerCode, 'gerencialDiarioPdfAction')
        : '';
    if (
        $executiveGerencialBody !== ''
        && strpos($executiveGerencialBody, 'archivarNotificacionReporteGerencialVisto') === false
        && hcCodeBodyIsReadOnly($executiveGerencialBody)
    ) {
        hcOk('10B-A: reporte gerencial HTML no archiva notificaciones por lectura directa.');
    } else {
        hcError(
            '10B-A: reporte gerencial HTML conserva side effect sobre notificaciones.',
            'Separar lectura directa de /reportes/gerencial-diario y archivado de notificaciones.'
        );
    }

    if (
        $executiveGerencialPdfBody !== ''
        && strpos($executiveGerencialPdfBody, 'archivarNotificacionReporteGerencialVisto') === false
        && hcCodeBodyIsReadOnly($executiveGerencialPdfBody)
    ) {
        hcOk('10B-A: reporte gerencial PDF no archiva notificaciones por descarga directa.');
    } else {
        hcError(
            '10B-A: reporte gerencial PDF conserva side effect sobre notificaciones.',
            'Mantener /reportes/gerencial-diario/pdf como descarga directa sin cambiar notificaciones.'
        );
    }

    if (strpos($executiveReportesControllerCode, 'function archivarNotificacionReporteGerencialVisto(') === false) {
        hcOk('10B-A: ReportesController no conserva archivado automatico de reporte gerencial.');
    } else {
        hcError(
            '10B-A: ReportesController aun conserva archivado automatico de reporte gerencial.',
            'Reservar cambios de estado de notificaciones para el flujo controlado de notificaciones.'
        );
    }

    $executiveNotificacionAbrirBody = $executiveNotificacionControllerCode !== ''
        ? hcExtractMethodCode($executiveNotificacionControllerCode, 'abrirAction')
        : '';
    $executiveNotificacionArchivaBody = $executiveNotificacionControllerCode !== ''
        ? hcExtractMethodCode($executiveNotificacionControllerCode, 'seArchivaAlAbrir')
        : '';
    if (
        $executiveNotificacionAbrirBody !== ''
        && $executiveNotificacionArchivaBody !== ''
        && strpos($executiveNotificacionAbrirBody, 'cambiarEstado') !== false
        && strpos($executiveNotificacionAbrirBody, 'seArchivaAlAbrir') !== false
        && strpos($executiveNotificacionArchivaBody, 'regla_reporte_gerencial_diario') !== false
    ) {
        hcOk('10B-A: archivado de reporte gerencial queda en flujo controlado de notificaciones.');
    } else {
        hcWarning(
            '10B-A: no se pudo confirmar archivado controlado de reporte gerencial desde notificaciones.',
            'Revisar manualmente /notificaciones/{id}/abrir para notificaciones de reporte gerencial.'
        );
    }

    if (hcRoutePatternExists($routes, 'huespedes/1', 'get')) {
        hcOk('Ruta 11A-A registrada: GET /huespedes/{id}.');
    } else {
        hcError(
            'Ruta 11A-A GET /huespedes/{id} no esta registrada.',
            'Restaurar la ficha operativa del huesped bajo la ruta GET existente.'
        );
    }

    if (!hcRoutePatternExists($routes, 'huespedes/1', 'post')) {
        hcOk('11A-A no expone POST directo /huespedes/{id}.');
    } else {
        hcError(
            '11A-A expone POST directo en /huespedes/{id} fuera de contrato.',
            'Mantener la ficha del huesped como lectura; las escrituras existentes deben seguir en sus rutas controladas.'
        );
    }

    $guestVerBody = $guestControllerCode !== ''
        ? hcExtractMethodCode($guestControllerCode, 'verAction')
        : '';
    if (
        $guestVerBody !== ''
        && strpos($guestVerBody, 'findForHotel') !== false
        && strpos($guestVerBody, 'getReservacionesPorHotel') !== false
        && strpos($guestVerBody, 'getVehiculosPorHotel') !== false
        && strpos($guestVerBody, 'perfilOperativoReadOnlyPorHotel') !== false
        && strpos($guestVerBody, "'perfilOperativo'") !== false
        && strpos($guestVerBody, 'documentosPorEntidad') !== false
        && hcCodeBodyIsReadOnly($guestVerBody)
    ) {
        hcOk('HuespedController 11A-A compone ficha con lector operativo scoped y sin escrituras.');
    } else {
        hcError(
            'HuespedController 11A-A no muestra contrato read-only completo.',
            'Usar findForHotel(), perfilOperativoReadOnlyPorHotel(), documentosPorEntidad() y render huespedes/ver sin escrituras.'
        );
    }

    $guestProfileMethods = [
        'perfilOperativoReadOnlyPorHotel',
        'huespedPerteneceAlHotel',
        'metricasReservacionesPerfil',
        'metricasVehiculosPerfil',
        'metricasCxcPerfil',
        'metricasDocumentosPerfil',
        'scoreRecurrenciaPerfil',
        'clasificacionPerfil',
        'alertasPerfil',
        'tablaPerfilExiste',
    ];
    $guestMissingMethods = [];
    $guestMutableMethods = [];
    foreach ($guestProfileMethods as $guestMethod) {
        $guestMethodBody = $guestModelCode !== '' ? hcExtractMethodCode($guestModelCode, $guestMethod) : '';
        if ($guestMethodBody === '') {
            $guestMissingMethods[] = $guestMethod;
            continue;
        }

        if (!hcCodeBodyIsReadOnly($guestMethodBody)) {
            $guestMutableMethods[] = $guestMethod;
        }
    }

    if (
        $guestModelCode !== ''
        && empty($guestMissingMethods)
        && empty($guestMutableMethods)
        && strpos($guestModelCode, 'function perfilOperativoReadOnlyPorHotel') !== false
        && strpos($guestModelCode, 'hotel_id = ?') !== false
        && strpos($guestModelCode, 'huesped_id = ?') !== false
        && strpos($guestModelCode, 'cuentas_por_cobrar') !== false
        && strpos($guestModelCode, 'documento_entidades') !== false
        && strpos($guestModelCode, 'information_schema.TABLES') !== false
    ) {
        hcOk('Huesped 11A-A calcula perfil operativo con consultas scoped por hotel_id y cuerpos read-only.');
    } else {
        $detalleGuest = [];
        if (!empty($guestMissingMethods)) {
            $detalleGuest[] = 'faltan metodos: ' . implode(', ', $guestMissingMethods);
        }
        if (!empty($guestMutableMethods)) {
            $detalleGuest[] = 'metodos con escrituras: ' . implode(', ', $guestMutableMethods);
        }
        hcError(
            'Huesped 11A-A no cumple contrato de lector operativo' . (!empty($detalleGuest) ? ' (' . implode(' | ', $detalleGuest) . ')' : '') . '.',
            'Mantener perfilOperativoReadOnlyPorHotel() y su familia sin INSERT/UPDATE/DELETE y con filtros hotel_id/huesped_id.'
        );
    }

    $guestProfileViewBlock = '';
    $guestBlockStart = strpos($guestViewCode, 'guest-readonly-profile');
    if ($guestBlockStart !== false) {
        $guestBlockEnd = strpos($guestViewCode, '<div class="guest-layout">', $guestBlockStart);
        $guestProfileViewBlock = $guestBlockEnd !== false
            ? substr($guestViewCode, $guestBlockStart, $guestBlockEnd - $guestBlockStart)
            : substr($guestViewCode, $guestBlockStart, 12000);
    }

    if (
        $guestProfileViewBlock !== ''
        && strpos($guestProfileViewBlock, 'Perfil operativo') !== false
        && strpos($guestProfileViewBlock, 'Solo lectura') !== false
        && strpos($guestProfileViewBlock, 'guest-operational-grid') !== false
        && strpos($guestProfileViewBlock, 'format_money($perfilCxcSaldo)') !== false
        && strpos($guestProfileViewBlock, '<form') === false
        && stripos($guestProfileViewBlock, 'method=') === false
        && strpos($guestProfileViewBlock, 'csrf_field') === false
        && strpos($guestProfileViewBlock, 'url(') === false
        && strpos($guestProfileViewBlock, '--ms-') === false
    ) {
        hcOk('Vista huespedes/ver 11A-A agrega perfil operativo sin formularios, acciones ni identidad Medisoft.');
    } else {
        hcError(
            'Vista huespedes/ver 11A-A no muestra bloque read-only completo.',
            'Mantener el bloque Perfil operativo con etiqueta Solo lectura, sin form/method/CSRF/url() y sin tokens --ms-*.'
        );
    }

    $mantControllerPath = $controllersDir . '/ReportesController.php';
    $mantModelPath = $appRoot . '/app/models/Reporte.php';
    $mantViewPath = $appRoot . '/app/views/reportes/mantenimiento.php';
    $mantPreflightPath = $appRoot . '/tools/saas/preflight_reporte_mantenimiento.php';
    $mantControllerCode = is_file($mantControllerPath) ? (string) file_get_contents($mantControllerPath) : '';
    $mantModelCode = is_file($mantModelPath) ? (string) file_get_contents($mantModelPath) : '';
    $mantViewCode = is_file($mantViewPath) ? (string) file_get_contents($mantViewPath) : '';

    if (hcRouteExists($routes, 'reportes/mantenimiento', 'get')) {
        hcOk('Ruta MANT-A registrada: GET /reportes/mantenimiento.');
    } else {
        hcWarning(
            'Ruta MANT-A GET /reportes/mantenimiento no esta registrada.',
            'Mantener o restaurar solo GET /reportes/mantenimiento si el reporte read-only sigue vigente.'
        );
    }

    if (!hcRouteExists($routes, 'reportes/mantenimiento', 'post')) {
        hcOk('MANT-A no expone POST /reportes/mantenimiento.');
    } else {
        hcError(
            'MANT-A expone POST /reportes/mantenimiento fuera de contrato.',
            'Retirar POST; el reporte de mantenimiento debe seguir solo lectura.'
        );
    }

    if (
        $mantControllerCode !== ''
        && strpos($mantControllerCode, 'class ReportesController') !== false
        && strpos($mantControllerCode, 'function mantenimientoAction') !== false
        && strpos($mantControllerCode, 'is_authenticated()') !== false
        && strpos($mantControllerCode, "require_hotel_module('reportes')") !== false
        && strpos($mantControllerCode, '../views/reportes/mantenimiento.php') !== false
    ) {
        hcOk('ReportesController MANT-A usa sesion, permisos, modulo reportes y vista read-only existente.');
    } else {
        hcWarning(
            'ReportesController MANT-A no muestra guardas completas.',
            'Validar sesion, permisos, modulo reportes y render de reportes/mantenimiento.'
        );
    }

    $mantActiveMethods = [
        'obtenerMantenimientosPorTipo' => 'hotel_id = ?',
        'obtenerMantenimientosPorPrioridad' => 'hotel_id = ?',
        'obtenerHabitacionesConMasMantenimientos' => 'h.hotel_id = ?',
        'obtenerRegistroMantenimientos' => 'm.hotel_id = ?',
        'obtenerTopResponsablesMantenimiento' => 'hotel_id = ?',
        'obtenerTendenciaMensualMantenimiento' => 'hotel_id = ?',
        'obtenerEstadisticasMantenimientoCompletas' => 'hotel_id = ?',
    ];
    foreach ($mantActiveMethods as $method => $scopeNeedle) {
        $methodBody = hcMethodBody($mantModelCode, $method);
        if ($methodBody === '') {
            hcError(
                'MANT-A no encuentra metodo activo: ' . $method . '.',
                'Restaurar el metodo usado por ReportesController::mantenimientoAction.'
            );
            continue;
        }

        if (strpos($methodBody, $scopeNeedle) !== false && hcCodeBodyIsReadOnly($methodBody)) {
            hcOk('Metodo MANT-A scoped/read-only: ' . $method . '.');
        } else {
            hcError(
                'Metodo MANT-A sin filtro hotel_id/read-only: ' . $method . '.',
                'Agregar filtro hotel_id y evitar escrituras en la familia activa del reporte.'
            );
        }
    }

    if (
        $mantViewCode !== ''
        && strpos($mantViewCode, 'Reporte de mantenimiento') !== false
        && strpos($mantViewCode, 'method="GET"') !== false
        && strpos($mantViewCode, 'method="POST"') === false
        && strpos($mantViewCode, 'csrf_field()') === false
        && strpos($mantViewCode, 'storage_path') === false
        && strpos($mantViewCode, "url('reportes/mantenimiento')") !== false
    ) {
        hcOk('Vista MANT-A es GET/read-only y no expone storage interno.');
    } else {
        hcWarning(
            'Vista MANT-A no muestra contrato visual completo.',
            'Revisar formulario GET unico, ausencia de POST/storage y ruta correcta.'
        );
    }

    if (is_file($mantPreflightPath)) {
        hcOk('Preflight MANT-A disponible: tools/saas/preflight_reporte_mantenimiento.php.');
    } else {
        hcWarning(
            'Preflight MANT-A no existe.',
            'Crear preflight read-only para validar reporte de mantenimiento.'
        );
    }

    if (hcTableExists($pdo, $database, 'mantenimientos_habitaciones')) {
        $mantenimientosSinHotel = hcCountScalar($pdo, 'SELECT COUNT(*) FROM mantenimientos_habitaciones WHERE hotel_id IS NULL');
        if ($mantenimientosSinHotel === 0) {
            hcOk('MANT-A datos: mantenimientos sin hotel_id = 0.');
        } else {
            hcError(
                'MANT-A datos: mantenimientos sin hotel_id = ' . (string)$mantenimientosSinHotel . '.',
                'Reconciliar hotel_id antes de automatizar mantenimiento o confiar en reportes multihotel.'
            );
        }

        if (hcTableExists($pdo, $database, 'habitaciones')) {
            $mantenimientosOtroHotel = hcCountScalar(
                $pdo,
                "SELECT COUNT(*)
                 FROM mantenimientos_habitaciones m
                 JOIN habitaciones h ON h.id = m.habitacion_id
                 WHERE m.hotel_id <> h.hotel_id"
            );
            if ($mantenimientosOtroHotel === 0) {
                hcOk('MANT-A datos: mantenimientos con habitacion de otro hotel = 0.');
            } else {
                hcError(
                    'MANT-A datos: mantenimientos con habitacion de otro hotel = ' . (string)$mantenimientosOtroHotel . '.',
                    'Corregir vinculos habitacion/hotel antes de operar mantenimiento avanzado.'
                );
            }
        }
    }

    $mantOpPreflightPath = $appRoot . '/tools/saas/preflight_mantenimiento_operativo.php';
    $mantOpControllerCode = is_file($controllersDir . '/HabitacionController.php')
        ? (string) file_get_contents($controllersDir . '/HabitacionController.php')
        : '';
    $mantOpModelCode = is_file($appRoot . '/app/models/Mantenimiento.php')
        ? (string) file_get_contents($appRoot . '/app/models/Mantenimiento.php')
        : '';
    $mantTaskModelCode = is_file($appRoot . '/app/models/TareaOperativa.php')
        ? (string) file_get_contents($appRoot . '/app/models/TareaOperativa.php')
        : '';
    $mantTaskControllerCode = is_file($controllersDir . '/TareaController.php')
        ? (string) file_get_contents($controllersDir . '/TareaController.php')
        : '';
    $mantOpViewCode = is_file($appRoot . '/app/views/habitaciones/ver.php')
        ? (string) file_get_contents($appRoot . '/app/views/habitaciones/ver.php')
        : '';
    $mantOpPreviewViewCode = is_file($appRoot . '/app/views/reportes/mantenimiento-programado.php')
        ? (string) file_get_contents($appRoot . '/app/views/reportes/mantenimiento-programado.php')
        : '';
    $limpiezaReportViewCode = is_file($appRoot . '/app/views/reportes/limpieza-operativa.php')
        ? (string) file_get_contents($appRoot . '/app/views/reportes/limpieza-operativa.php')
        : '';

    $mantOpRoutesOk = hcRoutePatternExists($routes, 'habitaciones/1/mantenimiento', 'post')
        && hcRoutePatternExists($routes, 'habitaciones/1/programar-mantenimiento', 'post')
        && hcRoutePatternExists($routes, 'habitaciones/cancelar-mantenimiento-programado/1', 'post');
    if ($mantOpRoutesOk) {
        hcOk('Rutas MANT-B existentes registradas con POST controlado.');
    } else {
        hcError(
            'Rutas MANT-B existentes incompletas.',
            'Restaurar POST mantenimiento/programar/cancelar solo con CSRF y permisos.'
        );
    }

    if (hcRouteExists($routes, 'reportes/mantenimiento-programado', 'get')) {
        hcOk('Ruta MANT-D-A registrada: GET /reportes/mantenimiento-programado.');
    } else {
        hcError(
            'Ruta MANT-D-A GET /reportes/mantenimiento-programado no esta registrada.',
            'Restaurar el preview read-only bajo ReportesController sin POST ni activaciones.'
        );
    }

    if (!hcRouteExists($routes, 'reportes/mantenimiento-programado', 'post')) {
        hcOk('MANT-D-A no expone POST /reportes/mantenimiento-programado.');
    } else {
        hcError(
            'MANT-D-A expone POST /reportes/mantenimiento-programado fuera de contrato.',
            'Retirar POST; el preview vencido/proximo debe seguir solo lectura.'
        );
    }

    if (hcRoutePatternExists($routes, 'habitaciones/activar-mantenimiento-programado/1', 'post')) {
        hcOk('Ruta MANT-E-A registrada: POST /habitaciones/activar-mantenimiento-programado/{id}.');
    } else {
        hcError(
            'Ruta MANT-E-A POST /habitaciones/activar-mantenimiento-programado/{id} no esta registrada.',
            'Registrar solo la activacion manual individual con CSRF, permiso y validacion central.'
        );
    }

    if (hcRoutePatternExists($routes, 'tareas/desde-mantenimiento/1', 'post')) {
        hcOk('Ruta MANT-G-B-A registrada: POST /tareas/desde-mantenimiento/{id}.');
    } else {
        hcError(
            'Ruta MANT-G-B-A POST /tareas/desde-mantenimiento/{id} no esta registrada.',
            'Registrar solo creacion manual individual con CSRF, permiso y validacion central.'
        );
    }

    if (hcRoutePatternExists($routes, 'tareas/desde-limpieza/1', 'post')) {
        hcOk('Ruta LIM-B-A registrada: POST /tareas/desde-limpieza/{id}.');
    } else {
        hcError(
            'Ruta LIM-B-A POST /tareas/desde-limpieza/{id} no esta registrada.',
            'Registrar solo creacion manual individual con CSRF, permiso y validacion central.'
        );
    }

    $mantOpBody = hcMethodBody($mantOpControllerCode, 'mantenimientoAction');
    if (
        $mantOpBody !== ''
        && strpos($mantOpBody, 'validateCSRF()') !== false
        && strpos($mantOpBody, "requirePermission('habitaciones.mantenimiento')") !== false
        && strpos($mantOpBody, "['iniciar', 'finalizar']") !== false
        && strpos($mantOpBody, 'Mantenimiento::getTipos()') !== false
        && strpos($mantOpBody, 'Mantenimiento::getPrioridades()') !== false
        && strpos($mantOpBody, '$motivo ===') !== false
        && strpos($mantOpBody, 'SELECT COUNT(*) FROM mantenimientos_habitaciones') !== false
        && strpos($mantOpBody, '$hotelId') !== false
        && strpos($mantOpBody, 'movimientos_caja') === false
    ) {
        hcOk('HabitacionController MANT-B valida accion, tipo, prioridad, motivo, duplicados y hotel_id.');
    } else {
        hcError(
            'HabitacionController MANT-B no muestra guardas completas.',
            'Revisar CSRF, permiso, whitelist de accion, tipo/prioridad/motivo, duplicados y hotel_id.'
        );
    }

    $mantProgramarBody = hcMethodBody($mantOpControllerCode, 'programarMantenimientoAction');
    if (
        $mantProgramarBody !== ''
        && strpos($mantProgramarBody, 'validateCSRF()') !== false
        && strpos($mantProgramarBody, "requirePermission('habitaciones.mantenimiento')") !== false
        && strpos($mantProgramarBody, "DateTimeImmutable::createFromFormat('!Y-m-d'") !== false
        && strpos($mantProgramarBody, 'Mantenimiento::getTipos()') !== false
        && strpos($mantProgramarBody, 'Mantenimiento::getPrioridades()') !== false
        && strpos($mantProgramarBody, '$motivo ===') !== false
        && strpos($mantProgramarBody, 'tieneProgramadoSolapado') !== false
        && strpos($mantProgramarBody, '$hotelId') !== false
        && strpos($mantProgramarBody, 'movimientos_caja') === false
    ) {
        hcOk('HabitacionController MANT-C-A valida programacion con CSRF, permiso, fechas, catalogos, motivo, solapes y hotel_id.');
    } else {
        hcError(
            'HabitacionController MANT-C-A sin guardas completas en programarMantenimientoAction.',
            'Revisar fechas, catalogos, motivo, solapes, hotel_id, CSRF, permiso y ausencia de Caja.'
        );
    }

    $mantCancelarBody = hcMethodBody($mantOpControllerCode, 'cancelarMantenimientoProgramadoAction');
    if (
        $mantCancelarBody !== ''
        && strpos($mantCancelarBody, 'validateCSRF()') !== false
        && strpos($mantCancelarBody, "requirePermission('habitaciones.mantenimiento')") !== false
        && strpos($mantCancelarBody, 'habitacionModel->find') !== false
        && strpos($mantCancelarBody, 'cancelarProgramado') !== false
        && strpos($mantCancelarBody, '$motivo ===') !== false
        && strpos($mantCancelarBody, 'movimientos_caja') === false
    ) {
        hcOk('HabitacionController MANT-C-A valida cancelacion con CSRF, permiso, habitacion scoped y motivo normalizado.');
    } else {
        hcError(
            'HabitacionController MANT-C-A sin guardas completas en cancelarMantenimientoProgramadoAction.',
            'Revisar CSRF, permiso, habitacion del hotel, motivo y ausencia de Caja.'
        );
    }

    if (
        $mantOpModelCode !== ''
        && strpos($mantOpModelCode, 'class Mantenimiento') !== false
        && strpos($mantOpModelCode, 'WHERE {$this->primaryKey} = ? AND hotel_id = ?') !== false
        && strpos($mantOpModelCode, 'AND m.hotel_id = ?') !== false
        && strpos($mantOpModelCode, 'function tieneProgramadoSolapado') !== false
        && strpos($mantOpModelCode, 'COALESCE(fecha_programada_fin, fecha_programada) >= ?') !== false
    ) {
        hcOk('Mantenimiento model MANT-B/MANT-C mantiene scope hotel_id y chequeo de solapes programados.');
    } else {
        hcError(
            'Mantenimiento model MANT-B/MANT-C no muestra scope hotel_id suficiente.',
            'No operar mantenimiento hasta restaurar find/update/consultas con hotel_id y solapes programados.'
        );
    }

    $mantPreviewControllerBody = hcMethodBody($mantControllerCode, 'mantenimientoProgramadoAction');
    if (
        $mantPreviewControllerBody !== ''
        && strpos($mantPreviewControllerBody, 'previewProgramados') !== false
        && strpos($mantPreviewControllerBody, 'TareaOperativa') !== false
        && strpos($mantPreviewControllerBody, "listarPorEntidadHotel(\$hotelId, 'mantenimiento'") !== false
        && strpos($mantPreviewControllerBody, 'View::renderTemplate') !== false
        && strpos($mantPreviewControllerBody, 'activarMantenimientosPendientes') === false
        && hcCodeBodyIsReadOnly($mantPreviewControllerBody)
    ) {
        hcOk('ReportesController MANT-D-A/MANT-G-A expone preview GET/read-only con tareas vinculadas sin activar pendientes.');
    } else {
        hcError(
            'ReportesController MANT-D-A/MANT-G-A no muestra contrato read-only completo.',
            'Mantener previewProgramados, tareas vinculadas read-only, render de vista y ausencia de activacion/escrituras.'
        );
    }

    if (
        $mantTaskModelCode !== ''
        && strpos($mantTaskModelCode, "'mantenimiento' => 't.mantenimiento_id'") !== false
        && strpos($mantTaskModelCode, 'function listarPorEntidadHotel') !== false
        && strpos($mantTaskModelCode, 'function buscarTareaActivaPorMantenimientoHotel') !== false
        && strpos($mantTaskModelCode, 'function crearDesdeMantenimientoParaHotel') !== false
        && strpos($mantTaskModelCode, "'mantenimiento'") !== false
        && strpos($mantTaskModelCode, 'WHERE t.hotel_id = ?') !== false
        && strpos($mantTaskModelCode, 'movimientos_caja') === false
    ) {
        hcOk('TareaOperativa MANT-G-B-A permite lectura y creacion manual desde mantenimiento con hotel_id.');
    } else {
        hcError(
            'TareaOperativa MANT-G-B-A no muestra contrato de mantenimiento.',
            'Agregar lectura/creacion por mantenimiento_id, scoped por hotel_id y sin Caja.'
        );
    }

    if (
        $mantTaskControllerCode !== ''
        && strpos($mantTaskControllerCode, 'function crearDesdeMantenimientoAction') !== false
        && strpos($mantTaskControllerCode, 'crearDesdeMantenimientoParaHotel') !== false
        && strpos($mantTaskControllerCode, 'validateCSRF()') !== false
        && strpos($mantTaskControllerCode, "require_permission('habitaciones.mantenimiento')") !== false
        && strpos($mantTaskControllerCode, 'AuditService::record') !== false
    ) {
        hcOk('TareaController MANT-G-B-A crea tarea desde mantenimiento con CSRF, permiso y auditoria.');
    } else {
        hcError(
            'TareaController MANT-G-B-A no muestra guardas completas.',
            'Revisar CSRF, permiso, auditoria y metodo central.'
        );
    }

    if (
        $mantTaskControllerCode !== ''
        && strpos($mantTaskControllerCode, 'function crearDesdeLimpiezaAction') !== false
        && strpos($mantTaskControllerCode, 'crearDesdeLimpiezaHabitacionParaHotel') !== false
        && strpos($mantTaskControllerCode, 'validateCSRF()') !== false
        && strpos($mantTaskControllerCode, "require_permission('habitaciones.mantenimiento')") !== false
        && strpos($mantTaskControllerCode, 'tareas.creada_desde_limpieza') !== false
        && strpos($mantTaskControllerCode, 'AuditService::record') !== false
    ) {
        hcOk('TareaController LIM-B-A crea tarea desde limpieza con CSRF, permiso y auditoria.');
    } else {
        hcError(
            'TareaController LIM-B-A no muestra guardas completas.',
            'Revisar CSRF, permiso, auditoria y metodo central de limpieza.'
        );
    }

    $mantPreviewModelBody = hcMethodBody($mantOpModelCode, 'previewProgramados');
    if (
        $mantPreviewModelBody !== ''
        && strpos($mantPreviewModelBody, 'm.hotel_id = ?') !== false
        && strpos($mantPreviewModelBody, 'm.programado = 1') !== false
        && strpos($mantPreviewModelBody, "m.estado = 'programado'") !== false
        && strpos($mantPreviewModelBody, 'reservaciones_conflicto') !== false
        && strpos($mantPreviewModelBody, 'preview_candidato') !== false
        && strpos($mantPreviewModelBody, 'activarMantenimientosPendientes') === false
        && hcCodeBodyIsReadOnly($mantPreviewModelBody)
    ) {
        hcOk('Mantenimiento::previewProgramados MANT-D-A es scoped por hotel_id, read-only y calcula candidatos/conflictos.');
    } else {
        hcError(
            'Mantenimiento::previewProgramados MANT-D-A no muestra guardas read-only completas.',
            'Revisar filtro hotel_id, estado programado, conflictos, candidatos y ausencia de escrituras.'
        );
    }

    $mantActivateControllerBody = hcMethodBody($mantOpControllerCode, 'activarMantenimientoProgramadoAction');
    if (
        $mantActivateControllerBody !== ''
        && strpos($mantActivateControllerBody, 'validateCSRF()') !== false
        && strpos($mantActivateControllerBody, "requirePermission('habitaciones.mantenimiento')") !== false
        && strpos($mantActivateControllerBody, 'activarProgramadoManual') !== false
        && strpos($mantActivateControllerBody, 'registrarAuditoriaMantenimientoProgramado') !== false
        && strpos($mantActivateControllerBody, 'activarMantenimientosPendientes') === false
        && strpos($mantActivateControllerBody, 'movimientos_caja') === false
    ) {
        hcOk('HabitacionController MANT-E-A activa manualmente con CSRF, permiso, auditoria y metodo central.');
    } else {
        hcError(
            'HabitacionController MANT-E-A no muestra guardas completas.',
            'Revisar CSRF, permiso, auditoria, metodo central y ausencia de Caja/activacion masiva.'
        );
    }

    $mantActivateModelBody = hcMethodBody($mantOpModelCode, 'activarProgramadoManual');
    if (
        $mantActivateModelBody !== ''
        && strpos($mantActivateModelBody, 'beginTransaction') !== false
        && strpos($mantActivateModelBody, 'FOR UPDATE') !== false
        && strpos($mantActivateModelBody, "estado = 'en_proceso'") !== false
        && strpos($mantActivateModelBody, "estado = 'mantenimiento'") !== false
        && strpos($mantActivateModelBody, "r.estado IN ('confirmada', 'checked_in')") !== false
        && strpos($mantActivateModelBody, 'm.hotel_id = ?') !== false
        && strpos($mantActivateModelBody, 'activarMantenimientosPendientes') === false
        && strpos($mantActivateModelBody, 'movimientos_caja') === false
    ) {
        hcOk('Mantenimiento::activarProgramadoManual MANT-E-A es transaccional, scoped y valida conflictos sin Caja.');
    } else {
        hcError(
            'Mantenimiento::activarProgramadoManual MANT-E-A no muestra guardas completas.',
            'Revisar transaccion, FOR UPDATE, hotel_id, conflictos, habitacion disponible y ausencia de Caja.'
        );
    }

    if (
        $mantOpViewCode !== ''
        && strpos($mantOpViewCode, "url('habitaciones/' . \$habitacion_id . '/mantenimiento')") !== false
        && strpos($mantOpViewCode, "url('habitaciones/' . \$habitacion_id . '/programar-mantenimiento')") !== false
        && strpos($mantOpViewCode, "url('habitaciones/cancelar-mantenimiento-programado/'") !== false
        && substr_count($mantOpViewCode, 'csrf_field()') >= 3
    ) {
        hcOk('habitaciones/ver.php conserva formularios MANT-B con CSRF.');
    } else {
        hcWarning(
            'habitaciones/ver.php no muestra todos los formularios MANT-B esperados.',
            'Revisar actions, method POST y csrf_field() de mantenimiento.'
        );
    }

    if (
        $mantOpPreviewViewCode !== ''
        && strpos($mantOpPreviewViewCode, 'Preview de mantenimiento programado') !== false
        && strpos($mantOpPreviewViewCode, "url('habitaciones/'") !== false
        && strpos($mantOpPreviewViewCode, "url('reportes/mantenimiento-programado") !== false
        && strpos($mantOpPreviewViewCode, "url('habitaciones/activar-mantenimiento-programado/'") !== false
        && strpos($mantOpPreviewViewCode, "can('habitaciones.mantenimiento')") !== false
        && strpos($mantOpPreviewViewCode, 'tareas_vinculadas') !== false
        && strpos($mantOpPreviewViewCode, "url('tareas/'") !== false
        && strpos($mantOpPreviewViewCode, 'tarea_activa_vinculada') !== false
        && strpos($mantOpPreviewViewCode, "url('tareas/desde-mantenimiento/'") !== false
        && strpos($mantOpPreviewViewCode, 'method="POST"') !== false
        && strpos($mantOpPreviewViewCode, 'csrf_field()') !== false
        && strpos($mantOpPreviewViewCode, 'activarMantenimientosPendientes') === false
        && strpos($mantOpPreviewViewCode, '/api/sync') !== false
    ) {
        hcOk('Vista MANT-D-A/MANT-E-A/MANT-G-A mantiene preview, accion manual existente y tareas vinculadas read-only sin /api/sync.');
    } else {
        hcError(
            'Vista MANT-D-A/MANT-E-A/MANT-G-A no muestra contrato visual completo.',
            'Revisar estado vacio, enlaces GET, boton existente con permiso/CSRF, tareas vinculadas y nota de /api/sync.'
        );
    }

    if (
        $limpiezaReportViewCode !== ''
        && strpos($limpiezaReportViewCode, 'Limpieza operativa') !== false
        && strpos($limpiezaReportViewCode, "url('reportes')") !== false
        && strpos($limpiezaReportViewCode, "url('tareas/desde-limpieza/'") !== false
        && strpos($limpiezaReportViewCode, 'method="POST"') !== false
        && strpos($limpiezaReportViewCode, 'csrf_field()') !== false
        && strpos($limpiezaReportViewCode, "can('habitaciones.mantenimiento')") !== false
        && strpos($limpiezaReportViewCode, 'tarea_activa') !== false
        && strpos($limpiezaReportViewCode, "url('tareas/'") !== false
        && strpos($limpiezaReportViewCode, 'movimientos_caja') === false
        && strpos($limpiezaReportViewCode, '/api/sync') !== false
    ) {
        hcOk('Vista LIM-A/LIM-B-A muestra reporte y creacion manual con permiso, CSRF y tareas vinculadas sin Caja ni /api/sync.');
    } else {
        hcError(
            'Vista LIM-B-A no muestra contrato visual completo.',
            'Revisar reporte read-only, boton manual con permiso/CSRF, tarea vinculada y ausencia de Caja/api sync.'
        );
    }

    if (is_file($mantOpPreflightPath)) {
        hcOk('Preflight MANT-B disponible: tools/saas/preflight_mantenimiento_operativo.php.');
    } else {
        hcWarning(
            'Preflight MANT-B no existe.',
            'Crear preflight para validar mantenimiento operativo existente.'
        );
    }

    if (hcTableExists($pdo, $database, 'mantenimientos_habitaciones') && hcTableExists($pdo, $database, 'habitaciones')) {
        $mantOpDuplicates = hcCountScalar(
            $pdo,
            "SELECT COUNT(*) FROM (
                SELECT hotel_id, habitacion_id
                FROM mantenimientos_habitaciones
                WHERE estado = 'en_proceso'
                GROUP BY hotel_id, habitacion_id
                HAVING COUNT(*) > 1
            ) duplicados"
        );
        if ($mantOpDuplicates === 0) {
            hcOk('MANT-B datos: mantenimientos en proceso duplicados = 0.');
        } else {
            hcError(
                'MANT-B datos: mantenimientos en proceso duplicados = ' . (string)$mantOpDuplicates . '.',
                'No permitir nuevas altas hasta reconciliar duplicados.'
            );
        }

        $mantOpActiveWrongRoom = hcCountScalar(
            $pdo,
            "SELECT COUNT(*)
             FROM mantenimientos_habitaciones m
             JOIN habitaciones h ON h.id = m.habitacion_id AND h.hotel_id = m.hotel_id
             WHERE m.estado = 'en_proceso'
               AND h.estado <> 'mantenimiento'"
        );
        if ($mantOpActiveWrongRoom === 0) {
            hcOk('MANT-B datos: mantenimientos en proceso con habitacion fuera de mantenimiento = 0.');
        } else {
            hcError(
                'MANT-B datos: mantenimientos en proceso con habitacion fuera de mantenimiento = ' . (string)$mantOpActiveWrongRoom . '.',
                'Reconciliar estado de habitacion/mantenimiento antes de automatizar.'
            );
        }

        $mantOpRoomWithoutActive = hcCountScalar(
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
        if ($mantOpRoomWithoutActive === 0) {
            hcOk('MANT-B datos: habitaciones en mantenimiento sin registro en proceso = 0.');
        } else {
            hcWarning(
                'MANT-B datos: habitaciones en mantenimiento sin registro en proceso = ' . (string)$mantOpRoomWithoutActive . '.',
                'Revisar historico antes de automatizar cierres masivos.'
            );
        }

        $mantScheduledOverlaps = hcCountScalar(
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
        if ($mantScheduledOverlaps === 0) {
            hcOk('MANT-C-A datos: mantenimientos programados solapados = 0.');
        } else {
            hcWarning(
                'MANT-C-A datos: mantenimientos programados solapados = ' . (string)$mantScheduledOverlaps . '.',
                'Revisar solapes historicos antes de automatizar disponibilidad.'
            );
        }

        if (hcTableExists($pdo, $database, 'tareas_operativas')) {
            $mantTasksMissingMaintenance = hcCountScalar(
                $pdo,
                "SELECT COUNT(*)
                 FROM tareas_operativas t
                 LEFT JOIN mantenimientos_habitaciones m
                   ON m.id = t.mantenimiento_id
                 WHERE t.mantenimiento_id IS NOT NULL
                   AND m.id IS NULL"
            );
            if ($mantTasksMissingMaintenance === 0) {
                hcOk('MANT-G-A datos: tareas con mantenimiento inexistente = 0.');
            } else {
                hcError(
                    'MANT-G-A datos: tareas con mantenimiento inexistente = ' . (string)$mantTasksMissingMaintenance . '.',
                    'Reconciliar mantenimiento_id antes de exponer enlaces contextuales.'
                );
            }

            $mantTasksCrossHotel = hcCountScalar(
                $pdo,
                "SELECT COUNT(*)
                 FROM tareas_operativas t
                 JOIN mantenimientos_habitaciones m
                   ON m.id = t.mantenimiento_id
                 WHERE t.mantenimiento_id IS NOT NULL
                   AND m.hotel_id <> t.hotel_id"
            );
            if ($mantTasksCrossHotel === 0) {
                hcOk('MANT-G-A datos: tareas con mantenimiento de otro hotel = 0.');
            } else {
                hcError(
                    'MANT-G-A datos: tareas con mantenimiento de otro hotel = ' . (string)$mantTasksCrossHotel . '.',
                    'Bloquear vistas contextuales hasta alinear hotel_id.'
                );
            }

            $mantTaskActiveDuplicates = hcCountScalar(
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
            if ($mantTaskActiveDuplicates === 0) {
                hcOk('MANT-G-B-A datos: mantenimientos con mas de una tarea activa vinculada = 0.');
            } else {
                hcError(
                    'MANT-G-B-A datos: mantenimientos con mas de una tarea activa vinculada = ' . (string)$mantTaskActiveDuplicates . '.',
                    'Cancelar/cerrar duplicados antes de crear nuevas tareas desde mantenimiento.'
                );
            }

            $cleaningTaskActiveDuplicates = hcCountScalar(
                $pdo,
                "SELECT COUNT(*) FROM (
                    SELECT hotel_id, habitacion_id
                    FROM tareas_operativas
                    WHERE categoria = 'limpieza'
                      AND habitacion_id IS NOT NULL
                      AND estado IN ('pendiente', 'asignada', 'en_proceso')
                    GROUP BY hotel_id, habitacion_id
                    HAVING COUNT(*) > 1
                ) duplicados"
            );
            if ($cleaningTaskActiveDuplicates === 0) {
                hcOk('LIM-B-A datos: habitaciones con mas de una tarea activa de limpieza = 0.');
            } else {
                hcError(
                    'LIM-B-A datos: habitaciones con mas de una tarea activa de limpieza = ' . (string)$cleaningTaskActiveDuplicates . '.',
                    'Cerrar o cancelar duplicados antes de crear nuevas tareas desde limpieza.'
                );
            }
        } else {
            hcWarning(
                'MANT-G-A no puede validar tareas vinculadas porque falta tareas_operativas.',
                'Aplicar TLM-A antes de usar tareas contextuales de mantenimiento.'
            );
        }
    }

    if (hcRouteExists($routes, 'api/sync', 'post')) {
        $apiController = $controllersDir . '/ApiController.php';
        $apiCode = is_file($apiController) ? (string) file_get_contents($apiController) : '';
        if (
            strpos($apiCode, 'sync_temporarily_disabled') !== false
            && preg_match('/function\s+syncAction\s*\([^)]*\).*?renderJSON\s*\(.*?423/s', $apiCode)
        ) {
            hcOk('/api/sync sigue registrado y bloqueado con sync_temporarily_disabled + HTTP 423 en codigo.');
        } else {
            hcError('/api/sync no muestra bloqueo estatico con HTTP 423.', 'No continuar hasta restaurar bloqueo temporal de sync.');
        }
    } else {
        hcError('No existe ruta POST /api/sync.', 'Restaurar ruta bloqueada antes de probar PWA/offline.');
    }
}

$recommendations = array_values(array_unique(array_filter($recommendations)));

echo "============================================================\n";
echo "Resumen\n";
echo "OK: {$ok}\n";
echo "WARNING: {$warnings}\n";
echo "ERROR: {$errors}\n";

if (!empty($recommendations)) {
    echo "Recomendaciones concretas:\n";
    foreach ($recommendations as $recommendation) {
        echo "- {$recommendation}\n";
    }
}

echo 'Resultado general: ' . ($errors > 0 ? 'FAIL' : 'PASS_WITH_WARNINGS_ALLOWED') . "\n";
exit($errors > 0 ? 1 : 0);
