<?php
/**
 * Preflight Fase 2T/2U/2V/2W/2X/2Y/2Z/3B/3C-A para recepcion minima de compras.
 *
 * Solo lectura. No ejecuta recepcion ni modifica DB.
 * Valida borradores pendientes, recepciones reales, productos_repetidos,
 * detalles_con_movimiento y guardas tecnicas sin modificar DB. Fase 2U
 * permite productos repetidos como un movimiento por linea de detalle.
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

function prcLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function prcOk(string $message): void
{
    global $ok;
    $ok++;
    prcLine('OK', $message);
}

function prcWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    prcLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function prcError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    prcLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function prcQuote(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function prcTableExists(PDO $pdo, string $database, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table'
    );
    $stmt->execute(['db' => $database, 'table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function prcColumnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['db' => $database, 'table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function prcCountRows(PDO $pdo, string $table, string $where = '1=1', array $params = []): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . prcQuote($table) . ' WHERE ' . $where);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function prcCountSql(PDO $pdo, string $fromSql, string $where = '1=1', array $params = []): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . $fromSql . ' WHERE ' . $where);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function prcFirstExistingPath(array $paths): string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return (string) ($paths[0] ?? '');
}

function prcFetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function prcFetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function prcParseRoutes(string $routesPath): array
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

function prcRouteExists(array $routes, string $path, ?string $method = null): bool
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

function prcDescribePurchase(array $compra): string
{
    $folio = trim((string) ($compra['folio'] ?? ''));
    $hotel = trim((string) ($compra['hotel_nombre'] ?? ''));
    $label = 'Compra #' . (int) $compra['id'];

    if ($folio !== '') {
        $label .= ' folio ' . $folio;
    }
    if ($hotel !== '') {
        $label .= ' (' . $hotel . ')';
    }

    return $label;
}

function prcProductLabel(array $detalle): string
{
    $codigo = trim((string) ($detalle['producto_codigo'] ?? ''));
    $nombre = trim((string) ($detalle['producto_nombre'] ?? ''));
    $id = (int) ($detalle['producto_id'] ?? 0);

    if ($codigo !== '' && $nombre !== '') {
        return $codigo . ' - ' . $nombre . ' (producto_id=' . $id . ')';
    }
    if ($nombre !== '') {
        return $nombre . ' (producto_id=' . $id . ')';
    }

    return 'producto_id=' . $id;
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    prcError(
        'APP_ENV debe ser local para ejecutar este preflight. Valor actual: ' . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv),
        'Ejecutar solo en entorno local o staging controlado.'
    );
}

$appRoot = dirname(__DIR__, 2);
$projectRoot = dirname($appRoot);
$configPath = $appRoot . '/config/database.php';
$routesPath = $appRoot . '/config/routes.php';
$purchaseServicePath = $appRoot . '/app/services/CompraService.php';
$purchaseControllerPath = $appRoot . '/app/controllers/CompraController.php';
$purchaseIndexViewPath = $appRoot . '/app/views/compras/index.php';
$purchaseFormViewPath = $appRoot . '/app/views/compras/form.php';
$purchaseDetailViewPath = $appRoot . '/app/views/compras/ver.php';
$purchaseReportViewPath = $appRoot . '/app/views/compras/reporte_recibidas.php';
$inventoryDocPath = prcFirstExistingPath([
    $projectRoot . '/docs/technical/inventory_reconciliation.md',
    $appRoot . '/docs/technical/inventory_reconciliation.md',
]);
$purchasingDocPath = prcFirstExistingPath([
    $projectRoot . '/docs/technical/purchasing_inventory_contract.md',
    $appRoot . '/docs/technical/purchasing_inventory_contract.md',
]);

echo "Preflight Fase 2T/2U/2V/2W/2X/2Y/2Z/3B/3C-A - Recepcion minima de compras y CxP read-only\n";
echo "=====================================================\n";

if (!is_file($configPath)) {
    prcError('No se encontro config/database.php.', 'Ejecutar desde el arbol src del proyecto.');
}

$pdo = null;
$database = '';
if (is_file($configPath)) {
    try {
        $dbConfig = require $configPath;
        $database = (string) ($dbConfig['database'] ?? '');
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
        prcOk('Conexion de solo lectura inicializada para ' . $database . '.');
    } catch (Throwable $e) {
        prcError('No se pudo abrir conexion de solo lectura: ' . $e->getMessage(), 'Revisar contenedores y credenciales.');
    }
}

if ($pdo) {
    $schema = [
        'hoteles' => ['id', 'nombre'],
        'proveedores' => ['id', 'hotel_id', 'nombre', 'activo'],
        'inventario_productos' => ['id', 'hotel_id', 'codigo', 'nombre', 'activo', 'stock_actual'],
        'movimientos_inventario' => ['id', 'hotel_id', 'producto_id', 'tipo_movimiento', 'cantidad', 'motivo', 'usuario_id', 'created_at'],
        'logs_auditoria' => ['id', 'hotel_id', 'usuario_id', 'accion', 'created_at'],
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

    $schemaOk = true;
    foreach ($schema as $table => $columns) {
        if (!prcTableExists($pdo, $database, $table)) {
            $schemaOk = false;
            prcError('Tabla requerida faltante: ' . $table, 'No preparar recepcion de compras hasta restaurar esta tabla.');
            continue;
        }

        $missingColumns = [];
        foreach ($columns as $column) {
            if (!prcColumnExists($pdo, $database, $table, $column)) {
                $missingColumns[] = $column;
            }
        }

        if ($missingColumns) {
            $schemaOk = false;
            prcError(
                'Tabla ' . $table . ' incompleta. Faltan columnas: ' . implode(', ', $missingColumns),
                'Reconciliar estructura antes de recibir compras.'
            );
        } else {
            prcOk('Tabla ' . $table . ' disponible con columnas requeridas para pre-recepcion.');
        }
    }

    if ($schemaOk) {
        $totalCompras = prcCountRows($pdo, 'compras');
        $totalDetalles = prcCountRows($pdo, 'compra_detalles');
        $comprasNoBorrador = prcCountRows($pdo, 'compras', "estado <> 'borrador'");
        $comprasRecibidas = prcCountRows($pdo, 'compras', "estado = 'recibida'");
        $comprasCanceladas = prcCountRows($pdo, 'compras', "estado = 'cancelada'");
        $detallesConMovimiento = prcCountRows($pdo, 'compra_detalles', 'movimiento_inventario_id IS NOT NULL');
        $movimientosRecepcion = prcCountRows($pdo, 'movimientos_inventario', "motivo LIKE 'Recepcion compra #%'");
        $auditoriasCompra = prcCountRows($pdo, 'logs_auditoria', "accion LIKE 'compras.%'");

        prcOk('Conteo compras: ' . $totalCompras . '.');
        prcOk('Conteo compra_detalles: ' . $totalDetalles . '.');

        if ($comprasNoBorrador === $comprasRecibidas && $comprasCanceladas === 0) {
            prcOk('compras_no_borrador = ' . $comprasNoBorrador . ', todas son recibidas.');
        } elseif ($comprasCanceladas > 0) {
            prcWarning(
                'compras_canceladas = ' . $comprasCanceladas . '.',
                'Auditar cancelaciones antes de habilitar flujos financieros o devoluciones.'
            );
        } else {
            prcWarning(
                'compras_no_borrador = ' . $comprasNoBorrador . ', recibidas = ' . $comprasRecibidas . '.',
                'Auditar compras no recibidas/no borrador antes de avanzar.'
            );
        }

        if ($comprasRecibidas === 0) {
            prcOk('No hay compras marcadas como recibidas; pre-recepcion pura.');
        } else {
            prcOk('Compras recibidas detectadas: ' . $comprasRecibidas . '.');
        }

        $detallesRecibidosSinMovimiento = prcCountSql(
            $pdo,
            'compra_detalles d INNER JOIN compras c ON c.id = d.compra_id AND c.hotel_id = d.hotel_id',
            "c.estado = 'recibida' AND d.movimiento_inventario_id IS NULL"
        );
        $detallesNoRecibidosConMovimiento = prcCountSql(
            $pdo,
            'compra_detalles d INNER JOIN compras c ON c.id = d.compra_id AND c.hotel_id = d.hotel_id',
            "c.estado <> 'recibida' AND d.movimiento_inventario_id IS NOT NULL"
        );
        $detallesConMovimientoInexistente = prcCountSql(
            $pdo,
            'compra_detalles d LEFT JOIN movimientos_inventario mi ON mi.id = d.movimiento_inventario_id',
            'd.movimiento_inventario_id IS NOT NULL AND mi.id IS NULL'
        );

        if ($detallesRecibidosSinMovimiento === 0 && $detallesNoRecibidosConMovimiento === 0 && $detallesConMovimientoInexistente === 0) {
            prcOk('detalles_con_movimiento = ' . $detallesConMovimiento . ', consistentes con estado de compra.');
        } else {
            if ($detallesRecibidosSinMovimiento > 0) {
                prcError(
                    'detalles_recibidos_sin_movimiento = ' . $detallesRecibidosSinMovimiento . '.',
                    'Reconciliar recepciones incompletas antes de avanzar.'
                );
            }
            if ($detallesNoRecibidosConMovimiento > 0) {
                prcError(
                    'detalles_no_recibidos_con_movimiento = ' . $detallesNoRecibidosConMovimiento . '.',
                    'Reconciliar movimientos vinculados a compras no recibidas.'
                );
            }
            if ($detallesConMovimientoInexistente > 0) {
                prcError(
                    'detalles_con_movimiento_inexistente = ' . $detallesConMovimientoInexistente . '.',
                    'Restaurar o corregir movimientos de inventario vinculados.'
                );
            }
        }

        if ($movimientosRecepcion === $detallesConMovimiento) {
            prcOk('movimientos_recepcion_compra = ' . $movimientosRecepcion . ', coincide con detalles vinculados.');
        } elseif ($movimientosRecepcion === 0 && $detallesConMovimiento === 0) {
            prcOk('movimientos_recepcion_compra = 0.');
        } else {
            prcWarning(
                'movimientos_recepcion_compra = ' . $movimientosRecepcion . ', detalles_con_movimiento = ' . $detallesConMovimiento . '.',
                'Auditar diferencias entre detalles y movimientos de recepcion.'
            );
        }

        if ($auditoriasCompra > 0) {
            $auditRows = prcFetchAll(
                $pdo,
                "SELECT accion, COUNT(*) AS total
                 FROM logs_auditoria
                 WHERE accion LIKE 'compras.%'
                 GROUP BY accion
                 ORDER BY accion"
            );
            $labels = [];
            foreach ($auditRows as $row) {
                $labels[] = $row['accion'] . '=' . (int) $row['total'];
            }
            prcOk('Auditorias de compras detectadas: ' . implode(', ', $labels) . '.');
        } else {
            prcWarning(
                'No hay auditorias de compras.',
                'La recepcion futura debe registrar compras.recibida con datos antes/despues.'
            );
        }

        $drafts = prcFetchAll(
            $pdo,
            "SELECT c.id, c.hotel_id, h.nombre AS hotel_nombre, c.proveedor_id, c.folio,
                    c.estado, c.total, c.fecha_compra, c.fecha_recepcion, c.created_at
             FROM compras c
             LEFT JOIN hoteles h ON h.id = c.hotel_id
             WHERE c.estado = 'borrador'
             ORDER BY c.hotel_id ASC, c.id ASC"
        );

        if (!$drafts) {
            prcOk('No hay borradores de compra pendientes.');
        }

        foreach ($drafts as $compra) {
            $compraLabel = prcDescribePurchase($compra);
            prcOk($compraLabel . ' permanece en borrador con total ' . number_format((float) $compra['total'], 2, '.', '') . '.');

            if (empty($compra['hotel_id'])) {
                prcError($compraLabel . ' no tiene hotel_id.', 'Corregir aislamiento antes de recepcion.');
            }

            if (!empty($compra['fecha_recepcion'])) {
                prcError($compraLabel . ' tiene fecha_recepcion aun estando en borrador.', 'Reconciliar estado antes de habilitar recepcion.');
            }

            $proveedor = prcFetchOne(
                $pdo,
                'SELECT id, hotel_id, nombre, activo FROM proveedores WHERE id = :id LIMIT 1',
                ['id' => (int) $compra['proveedor_id']]
            );

            if (!$proveedor) {
                prcError($compraLabel . ' referencia proveedor inexistente: ' . (int) $compra['proveedor_id'], 'Corregir proveedor antes de recepcion.');
            } elseif ((int) $proveedor['hotel_id'] !== (int) $compra['hotel_id']) {
                prcError(
                    $compraLabel . ' referencia proveedor de otro hotel: ' . $proveedor['nombre'],
                    'Bloquear recepcion hasta corregir proveedor_id.'
                );
            } elseif ((int) $proveedor['activo'] !== 1) {
                prcError(
                    $compraLabel . ' referencia proveedor inactivo: ' . $proveedor['nombre'],
                    'Reactivar o cambiar proveedor antes de recepcion.'
                );
            } else {
                prcOk($compraLabel . ' tiene proveedor activo del mismo hotel: ' . $proveedor['nombre'] . '.');
            }

            $detalles = prcFetchAll(
                $pdo,
                "SELECT d.id, d.compra_id, d.hotel_id, d.producto_id, d.cantidad,
                        d.costo_unitario, d.subtotal, d.movimiento_inventario_id,
                        ip.hotel_id AS producto_hotel_id, ip.codigo AS producto_codigo,
                        ip.nombre AS producto_nombre, ip.activo AS producto_activo
                 FROM compra_detalles d
                 LEFT JOIN inventario_productos ip ON ip.id = d.producto_id
                 WHERE d.compra_id = :compra_id
                 ORDER BY d.id ASC",
                ['compra_id' => (int) $compra['id']]
            );

            if (!$detalles) {
                prcError($compraLabel . ' no tiene detalles.', 'No puede recibirse una compra sin productos.');
                continue;
            }

            prcOk($compraLabel . ' tiene ' . count($detalles) . ' lineas de detalle.');

            $productosRepetidos = [];
            foreach ($detalles as $detalle) {
                $productoId = (int) $detalle['producto_id'];
                $productosRepetidos[$productoId] = ($productosRepetidos[$productoId] ?? 0) + 1;
                $detalleLabel = $compraLabel . ', detalle #' . (int) $detalle['id'] . ' (' . prcProductLabel($detalle) . ')';

                if ((int) $detalle['hotel_id'] !== (int) $compra['hotel_id']) {
                    prcError($detalleLabel . ' tiene hotel_id distinto al encabezado.', 'Corregir detalle antes de recepcion.');
                }

                if ($detalle['producto_hotel_id'] === null) {
                    prcError($detalleLabel . ' referencia producto inexistente.', 'Corregir producto_id antes de recepcion.');
                    continue;
                }

                if ((int) $detalle['producto_hotel_id'] !== (int) $compra['hotel_id']) {
                    prcError($detalleLabel . ' pertenece a otro hotel.', 'Bloquear recepcion hasta corregir producto.');
                }

                if ((int) $detalle['producto_activo'] !== 1) {
                    prcError($detalleLabel . ' esta inactivo.', 'Reactivar o sustituir producto antes de recepcion.');
                }

                if ((float) $detalle['cantidad'] <= 0) {
                    prcError($detalleLabel . ' tiene cantidad no positiva.', 'Corregir cantidad antes de recepcion.');
                }

                if ((float) $detalle['costo_unitario'] < 0 || (float) $detalle['subtotal'] < 0) {
                    prcError($detalleLabel . ' tiene importes negativos.', 'Corregir importes antes de recepcion.');
                }

                if (!empty($detalle['movimiento_inventario_id'])) {
                    prcError($detalleLabel . ' ya tiene movimiento_inventario_id.', 'No recibir una linea ya vinculada a movimiento.');
                }
            }

            $duplicados = [];
            foreach ($productosRepetidos as $productoId => $veces) {
                if ($veces <= 1) {
                    continue;
                }

                foreach ($detalles as $detalle) {
                    if ((int) $detalle['producto_id'] === (int) $productoId) {
                        $duplicados[] = prcProductLabel($detalle) . ' aparece ' . $veces . ' veces';
                        break;
                    }
                }
            }

            if ($duplicados) {
                prcOk(
                    $compraLabel . ' tiene productos_repetidos permitidos por Fase 2U: ' . implode('; ', $duplicados) . '. Regla: un movimiento por linea.'
                );
            } else {
                prcOk($compraLabel . ' no tiene productos repetidos.');
            }
        }

        $futureTables = [
            'compra_pagos',
            'documentos_proveedor',
            'proveedor_contactos',
        ];
        foreach ($futureTables as $table) {
            if (prcTableExists($pdo, $database, $table)) {
                prcWarning(
                    'Tabla futura detectada fuera del alcance Fase 2T: ' . $table,
                    'No usarla para recepcion minima hasta documentar su contrato.'
                );
            } else {
                prcOk('Tabla futura no existe en Fase 2T: ' . $table);
            }
        }

        if (prcTableExists($pdo, $database, 'cuentas_por_pagar') && prcTableExists($pdo, $database, 'cuentas_por_pagar_movimientos')) {
            prcOk('Tablas CxP Fase 3B existen para modo read-only y no forman parte de la recepcion minima.');
            prcOk('cuentas_por_pagar registros actuales: ' . (string)prcCountRows($pdo, 'cuentas_por_pagar'));
            prcOk('cuentas_por_pagar_movimientos registros actuales: ' . (string)prcCountRows($pdo, 'cuentas_por_pagar_movimientos'));
        } else {
            prcWarning(
                'Tablas CxP Fase 3B aun no existen.',
                'Esto no bloquea recepcion; aplicar CxP solo con backup si se requiere vista read-only.'
            );
        }
    }

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

if (is_file($routesPath)) {
    $routes = prcParseRoutes($routesPath);
    $expectedRoutes = [
        ['method' => 'get', 'path' => 'compras'],
        ['method' => 'get', 'path' => 'compras/crear'],
        ['method' => 'get', 'path' => 'compras/reportes/recibidas'],
        ['method' => 'get', 'path' => 'compras/{id:[0-9]+}'],
        ['method' => 'post', 'path' => 'compras'],
        ['method' => 'post', 'path' => 'compras/{id:[0-9]+}/recibir'],
    ];
    $missingRoutes = [];
    foreach ($expectedRoutes as $expectedRoute) {
        if (!prcRouteExists($routes, $expectedRoute['path'], $expectedRoute['method'])) {
            $missingRoutes[] = strtoupper($expectedRoute['method']) . ' /' . $expectedRoute['path'];
        }
    }

    if (!$missingRoutes) {
        prcOk('Rutas publicas de Compras incluyen reporte read-only, detalle, borradores y recepcion minima Fase 2V/2W/2X/2Y.');
    } else {
        prcError('Faltan rutas de borrador: ' . implode(', ', $missingRoutes), 'Restaurar rutas minimas antes de validar UI.');
    }

    $forbiddenRoutes = [];
    foreach ($routes as $route) {
        $method = strtoupper((string) $route['method']);
        $path = strtolower(trim((string) $route['path'], '/'));
        $controller = strtolower((string) $route['controller']);
        $action = strtolower((string) $route['action']);
        $signature = $method . ' /' . $path . ' -> ' . $controller . '::' . $action;

        $allowed = in_array($method . ' /' . $path, ['GET /compras', 'GET /compras/crear', 'GET /compras/reportes/recibidas', 'GET /compras/{id:[0-9]+}', 'POST /compras'], true)
            || $method . ' /' . $path === 'POST /compras/{id:[0-9]+}/recibir';
        $allowed = $allowed
            && $controller === 'compra'
            && in_array($action, ['index', 'crear', 'reporterecibidas', 'ver', 'guardar', 'recibir'], true);

        $allowedCxpReadOnly = in_array($method . ' /' . $path, [
            'GET /cuentas-por-pagar',
            'GET /cuentas-por-pagar/generacion-preview',
            'GET /cuentas-por-pagar/{id:[0-9]+}',
        ], true)
            && $controller === 'cuentaporpagar'
            && in_array($action, ['index', 'generacionpreview', 'ver'], true);

        if ($allowed || $allowedCxpReadOnly) {
            continue;
        }

        if (
            strpos($path, 'compras') !== false
            || strpos($path, 'cuentas-por-pagar') !== false
            || strpos($path, 'compra-pagos') !== false
            || strpos($path, 'proveedor-contactos') !== false
            || strpos($path, 'documentos-proveedor') !== false
            || strpos($controller, 'compra') !== false
            || strpos($controller, 'cuentaporpagar') !== false
        ) {
            $forbiddenRoutes[] = $signature;
        }
    }

    if (!$forbiddenRoutes) {
        prcOk('Solo hay reporte read-only, detalle read-only, recepcion minima y CxP/preview GET read-only; no hay pago ni documentos de compras.');
    } else {
        prcError(
            'Rutas fuera del alcance Fase 2V/2W/2X/2Y/3B: ' . implode(' | ', $forbiddenRoutes),
            'Retirar rutas que no sean reporte read-only, detalle, borrador, POST /compras/{id}/recibir o CxP GET/preview read-only.'
        );
    }
} else {
    prcWarning('No se pudo leer config/routes.php.', 'Ejecutar con el proyecto completo montado.');
}

if (is_file($purchaseControllerPath)) {
    $controller = (string) file_get_contents($purchaseControllerPath);
    $forbiddenControllerTokens = [
        'function pagarAction',
        'function cancelarAction',
        'cuentas_por_pagar',
        'compra_pagos',
        'documentos_proveedor',
        'movimientos_caja',
    ];
    $found = [];
    foreach ($forbiddenControllerTokens as $token) {
        if (strpos($controller, $token) !== false) {
            $found[] = $token;
        }
    }

    if (
        !$found
        && strpos($controller, 'function verAction') !== false
        && strpos($controller, 'function reporteRecibidasAction') !== false
        && strpos($controller, 'function recibirAction') !== false
        && strpos($controller, 'obtenerCompra') !== false
        && strpos($controller, 'reporteRecibidas') !== false
        && strpos($controller, 'catalogosReporteRecibidas') !== false
        && strpos($controller, 'compras/reporte_recibidas') !== false
        && strpos($controller, 'compras/ver') !== false
        && strpos($controller, 'recibirCompra') !== false
    ) {
        prcOk('CompraController expone reporte read-only, detalle read-only y recepcion minima; no expone pago, cancelacion, CxP, documentos ni caja.');
    } else {
        prcError(
            'CompraController no cumple Fase 2V/2W/2X/2Y o contiene tokens fuera de alcance: ' . (empty($found) ? 'sin detalle' : implode(', ', $found)),
            'Mantener solo reporte read-only, detalle read-only y recepcion minima sin pago, cancelacion, CxP, documentos ni caja.'
        );
    }
} else {
    prcError('No existe CompraController.', 'Restaurar controller minimo de borradores o retirar rutas /compras.');
}

if (is_file($purchaseServicePath)) {
    $service = (string) file_get_contents($purchaseServicePath);
    if (
        strpos($service, 'function recibirCompra') !== false
        && strpos($service, 'function obtenerCompra') !== false
        && strpos($service, 'function reporteRecibidas') !== false
        && strpos($service, 'function catalogosReporteRecibidas') !== false
        && strpos($service, 'function assertCompraPuedeRecibirse') !== false
        && strpos($service, 'function assertDetallesPuedenRecibirse') !== false
        && strpos($service, 'ya fue recibida') !== false
        && strpos($service, 'esta cancelada') !== false
        && strpos($service, 'otra sesion') !== false
        && strpos($service, 'GROUP BY p.id, p.nombre') !== false
        && strpos($service, 'GROUP BY ip.id, ip.codigo, ip.nombre, ip.unidad_medida') !== false
        && strpos($service, 'obtenerCompraBloqueada') !== false
        && strpos($service, 'LEFT JOIN movimientos_inventario') !== false
        && strpos($service, 'movimiento_stock_posterior') !== false
        && strpos($service, 'movimiento_inventario_id IS NULL') !== false
        && strpos($service, "estado = 'recibida'") !== false
        && strpos($service, 'AuditService::record') !== false
    ) {
        prcOk('CompraService conserva contrato interno para recepcion, reportes read-only e idempotencia explicita Fase 2Z.');
    } else {
        prcWarning(
            'CompraService no muestra todas las guardas esperadas para recepcion y reportes.',
            'Revisar bloqueo de compra/detalles/productos, estado borrador, movimiento_inventario_id, reporteRecibidas y guardas Fase 2Z.'
        );
    }
} else {
    prcError('No existe CompraService.', 'No preparar recepcion sin servicio transaccional revisado.');
}

if (is_file($purchaseIndexViewPath) && is_file($purchaseFormViewPath) && is_file($purchaseDetailViewPath) && is_file($purchaseReportViewPath)) {
    $views = (string) file_get_contents($purchaseIndexViewPath) . "\n"
        . (string) file_get_contents($purchaseFormViewPath) . "\n"
        . (string) file_get_contents($purchaseDetailViewPath) . "\n"
        . (string) file_get_contents($purchaseReportViewPath);
    if (
        strpos($views, "url('compras/recibir") === false
        && strpos($views, "url('compras/reportes/recibidas')") !== false
        && strpos($views, "url('compras/' . (int)") !== false
        && strpos($views, '/recibir') !== false
        && strpos($views, 'purchase-btn-receive') !== false
        && strpos($views, 'por_proveedor') !== false
        && strpos($views, 'por_producto') !== false
        && strpos($views, 'movimiento_inventario_id') !== false
        && strpos($views, 'movimiento_stock_posterior') !== false
        && strpos($views, "url('compras/pagar") === false
        && strpos($views, 'cuentas-por-pagar') === false
        && strpos($views, 'documentos-proveedor') === false
    ) {
        prcOk('Vistas de Compras muestran reporte read-only, detalle read-only y recepcion minima; no muestran pago/CxP/documentos.');
    } else {
        prcError(
            'Vistas de Compras contienen enlaces fuera de alcance.',
            'Mantener solo reporte read-only, detalle read-only, boton de recepcion minima y retirar pago, CxP o documentos.'
        );
    }
} else {
    prcError('Faltan vistas de Compras.', 'Restaurar vistas de listado, formulario, detalle y reporte antes de validar compras.');
}

if (is_file($purchasingDocPath)) {
    $doc = (string) file_get_contents($purchasingDocPath);
    if (
        strpos($doc, 'Actualizacion Fase 2T') !== false
        && strpos($doc, 'Actualizacion Fase 2U') !== false
        && strpos($doc, 'Actualizacion Fase 2W') !== false
        && strpos($doc, 'Actualizacion Fase 2X') !== false
        && strpos($doc, 'Actualizacion Fase 2Y') !== false
        && strpos($doc, 'Actualizacion Fase 2Z') !== false
        && strpos($doc, 'assertCompraPuedeRecibirse()') !== false
        && strpos($doc, 'GET /compras/reportes/recibidas') !== false
        && strpos($doc, 'preflight_recepcion_compras.php') !== false
        && strpos($doc, 'productos repetidos') !== false
        && strpos($doc, 'un movimiento por linea') !== false
    ) {
        prcOk('Contrato de compras documenta Fase 2T/2U/2W/2X/2Y/2Z.');
    } else {
        prcWarning('Contrato de compras no documenta completamente Fase 2T/2U/2W/2X/2Y/2Z.', 'Actualizar purchasing_inventory_contract.md.');
    }
} else {
    prcWarning('No se encontro purchasing_inventory_contract.md.', 'Documentar Fase 2T antes de recepcion real.');
}

if (is_file($inventoryDocPath)) {
    $doc = (string) file_get_contents($inventoryDocPath);
    if (
        strpos($doc, 'Fase 2T') !== false
        && strpos($doc, 'Fase 2U') !== false
        && strpos($doc, 'Fase 2W') !== false
        && strpos($doc, 'Fase 2X') !== false
        && strpos($doc, 'Fase 2Y') !== false
        && strpos($doc, 'Fase 2Z') !== false
        && strpos($doc, 'assertCompraPuedeRecibirse()') !== false
        && strpos($doc, 'GET /compras/reportes/recibidas') !== false
        && strpos($doc, 'preflight_recepcion_compras.php') !== false
        && strpos($doc, 'productos repetidos') !== false
        && strpos($doc, 'un movimiento por linea') !== false
    ) {
        prcOk('Documento de inventario registra Fase 2T/2U/2W/2X/2Y/2Z.');
    } else {
        prcWarning('Documento de inventario no registra completamente Fase 2T/2U/2W/2X/2Y/2Z.', 'Actualizar inventory_reconciliation.md.');
    }
} else {
    prcWarning('No se encontro inventory_reconciliation.md.', 'Documentar Fase 2T en inventario.');
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
