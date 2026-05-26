<?php
/**
 * Preflight local para planear la migracion de hotel_id.
 *
 * Solo lectura. No ejecuta migraciones ni modifica datos.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$appEnv = getenv('APP_ENV');
if ($appEnv !== 'local') {
    echo "[ERROR] APP_ENV debe ser local para ejecutar esta herramienta. Valor actual: " . ($appEnv === false || $appEnv === '' ? '(sin definir)' : $appEnv) . "\n";
    echo "Resultado general: FAIL\n";
    exit(1);
}

$configPath = dirname(__DIR__, 2) . '/config/database.php';
if (!is_file($configPath)) {
    echo "[ERROR] No se encontro el archivo de configuracion de base de datos: {$configPath}\n";
    echo "Resultado general: FAIL\n";
    exit(1);
}

$dbConfig = require $configPath;
$databaseName = $dbConfig['database'] ?? '';
$ok = 0;
$warnings = 0;
$errors = 0;

function reportarPreflight(string $nivel, string $mensaje): void
{
    echo "[{$nivel}] {$mensaje}\n";
}

function preflightOk(string $mensaje): void
{
    global $ok;
    $ok++;
    reportarPreflight('OK', $mensaje);
}

function preflightWarn(string $mensaje): void
{
    global $warnings;
    $warnings++;
    reportarPreflight('WARN', $mensaje);
}

function preflightError(string $mensaje): void
{
    global $errors;
    $errors++;
    reportarPreflight('ERROR', $mensaje);
}

function preflightInfo(string $mensaje): void
{
    reportarPreflight('INFO', $mensaje);
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function existeTablaPreflight(PDO $pdo, string $databaseName, string $tabla): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    return (int) $stmt->fetchColumn() > 0;
}

function contarRegistros(PDO $pdo, string $tabla): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM ' . quoteIdentifier($tabla));

    return (int) $stmt->fetchColumn();
}

function obtenerColumnas(PDO $pdo, string $databaseName, string $tabla): array
{
    $stmt = $pdo->prepare(
        'SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabla
         ORDER BY ORDINAL_POSITION'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function obtenerPrimaryKey(PDO $pdo, string $databaseName, string $tabla): array
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabla
           AND CONSTRAINT_NAME = 'PRIMARY'
         ORDER BY ORDINAL_POSITION"
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function obtenerForaneas(PDO $pdo, string $databaseName, string $tabla): array
{
    $stmt = $pdo->prepare(
        'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabla
           AND REFERENCED_TABLE_NAME IS NOT NULL
         ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    $foraneas = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nombre = $row['CONSTRAINT_NAME'];
        $foraneas[$nombre][] = sprintf(
            '%s -> %s(%s)',
            $row['COLUMN_NAME'],
            $row['REFERENCED_TABLE_NAME'],
            $row['REFERENCED_COLUMN_NAME']
        );
    }

    return $foraneas;
}

function obtenerIndicesUnicos(PDO $pdo, string $databaseName, string $tabla): array
{
    $stmt = $pdo->prepare(
        "SELECT INDEX_NAME, COLUMN_NAME
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabla
           AND NON_UNIQUE = 0
           AND INDEX_NAME <> 'PRIMARY'
         ORDER BY INDEX_NAME, SEQ_IN_INDEX"
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    $indices = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $indices[$row['INDEX_NAME']][] = $row['COLUMN_NAME'];
    }

    return $indices;
}

function existeIndicePreflight(PDO $pdo, string $databaseName, string $tabla, string $indice): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla AND INDEX_NAME = :indice'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla, 'indice' => $indice]);

    return (int) $stmt->fetchColumn() > 0;
}

function existeForeignKeyPreflight(PDO $pdo, string $databaseName, string $tabla, string $constraint): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = :tabla
           AND CONSTRAINT_NAME = :constraint
           AND COLUMN_NAME = "hotel_id"
           AND REFERENCED_TABLE_NAME = "hoteles"
           AND REFERENCED_COLUMN_NAME = "id"'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla, 'constraint' => $constraint]);

    return (int) $stmt->fetchColumn() > 0;
}

function obtenerEstadoMigracion(PDO $pdo, string $nombre): ?string
{
    $stmt = $pdo->prepare('SELECT estado FROM migrations WHERE nombre = :nombre LIMIT 1');
    $stmt->execute(['nombre' => $nombre]);
    $estado = $stmt->fetchColumn();

    return $estado === false ? null : (string) $estado;
}

function obtenerIdHotelLosCedros(PDO $pdo): ?int
{
    $stmt = $pdo->query("SELECT id FROM hoteles WHERE slug = 'los-cedros' LIMIT 1");
    $id = $stmt->fetchColumn();

    return $id === false ? null : (int) $id;
}

function formatearLista(array $valores): string
{
    return $valores === [] ? 'ninguno' : implode(', ', $valores);
}

function formatearForaneas(array $foraneas): string
{
    if ($foraneas === []) {
        return 'ninguna';
    }

    $partes = [];
    foreach ($foraneas as $nombre => $columnas) {
        $partes[] = $nombre . ': ' . implode('; ', $columnas);
    }

    return implode(' | ', $partes);
}

function formatearIndicesUnicos(array $indices): string
{
    if ($indices === []) {
        return 'ninguno';
    }

    $partes = [];
    foreach ($indices as $nombre => $columnas) {
        $partes[] = $nombre . '(' . implode(', ', $columnas) . ')';
    }

    return implode(' | ', $partes);
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['database'],
        $dbConfig['charset'] ?? 'utf8mb4'
    );

    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    preflightError('No fue posible conectar a la base de datos local: ' . $e->getMessage());
    echo "Resultado general: FAIL\n";
    exit(1);
}

$altoRiesgo = [
    'reservaciones',
    'reservacion_habitaciones',
    'reservacion_pagos',
    'reservacion_abonos',
    'cajas',
    'cortes_caja',
    'movimientos_caja',
    'sync_queue',
    'push_subscriptions',
];

$primerasCandidatas = [
    'tipos_habitacion',
    'habitaciones',
    'habitacion_imagenes',
    'mantenimientos_habitaciones',
];

$tablasHabitacionesMigradas = [
    'tipos_habitacion' => [
        'indice' => 'idx_tipos_habitacion_hotel_id',
        'foreign_key' => 'fk_tipos_habitacion_hotel',
    ],
    'habitaciones' => [
        'indice' => 'idx_habitaciones_hotel_id',
        'foreign_key' => 'fk_habitaciones_hotel',
    ],
    'habitacion_imagenes' => [
        'indice' => 'idx_habitacion_imagenes_hotel_id',
        'foreign_key' => 'fk_habitacion_imagenes_hotel',
    ],
    'mantenimientos_habitaciones' => [
        'indice' => 'idx_mantenimientos_habitaciones_hotel_id',
        'foreign_key' => 'fk_mantenimientos_habitaciones_hotel',
    ],
];

$tablasInventarioBaseMigradas = [
    'inventario_categorias' => [
        'indice' => 'idx_inventario_categorias_hotel_id',
        'foreign_key' => 'fk_inventario_categorias_hotel',
    ],
    'inventario_productos' => [
        'indice' => 'idx_inventario_productos_hotel_id',
        'foreign_key' => 'fk_inventario_productos_hotel',
    ],
    'inventario_config_habitacion' => [
        'indice' => 'idx_inventario_config_habitacion_hotel_id',
        'foreign_key' => 'fk_inventario_config_habitacion_hotel',
    ],
];

$tablasMovimientosInventarioMigradas = [
    'movimientos_inventario' => [
        'indice' => 'idx_movimientos_inventario_hotel_id',
        'foreign_key' => 'fk_movimientos_inventario_hotel',
    ],
];

$tablasReservacionesBaseMigradas = [
    'reservaciones' => [
        'indice' => 'idx_reservaciones_hotel_id',
    ],
    'reservacion_habitaciones' => [
        'indice' => 'idx_reservacion_habitaciones_hotel_id',
    ],
    'reservacion_pagos' => [
        'indice' => 'idx_reservacion_pagos_hotel_id',
    ],
    'reservacion_abonos' => [
        'indice' => 'idx_reservacion_abonos_hotel_id',
    ],
    'reservacion_notas' => [
        'indice' => 'idx_reservacion_notas_hotel_id',
    ],
    'solicitudes_factura' => [
        'indice' => 'idx_solicitudes_factura_hotel_id',
    ],
];

$tablasMigradasConHotelId = array_merge(
    $tablasHabitacionesMigradas,
    $tablasInventarioBaseMigradas,
    $tablasMovimientosInventarioMigradas,
    $tablasReservacionesBaseMigradas
);

$tablasPermitidasConHotelIdPostReservaciones1B = array_merge(
    ['hotel_configuracion', 'hotel_usuarios', 'logs_auditoria'],
    array_keys($tablasHabitacionesMigradas),
    array_keys($tablasInventarioBaseMigradas),
    array_keys($tablasMovimientosInventarioMigradas),
    array_keys($tablasReservacionesBaseMigradas)
);

$tablas = [
    'SaaS/base' => [
        'hoteles' => ['necesita_hotel_id' => false, 'orden' => 0, 'riesgo' => 'bajo', 'motivo' => 'Tabla raiz de hoteles; no debe tener hotel_id propio.'],
        'hotel_configuracion' => ['necesita_hotel_id' => true, 'orden' => 0, 'riesgo' => 'bajo', 'motivo' => 'Tabla SaaS ya creada para configuracion por hotel.'],
        'hotel_usuarios' => ['necesita_hotel_id' => true, 'orden' => 0, 'riesgo' => 'bajo', 'motivo' => 'Tabla pivote SaaS ya creada para relacionar usuarios y hoteles.'],
        'logs_auditoria' => ['necesita_hotel_id' => true, 'orden' => 0, 'riesgo' => 'medio', 'motivo' => 'Auditoria multi-hotel futura; todavia no debe activarse en modulos.'],
        'migrations' => ['necesita_hotel_id' => false, 'orden' => 0, 'riesgo' => 'bajo', 'motivo' => 'Registro global de migraciones; no es informacion operativa por hotel.'],
    ],
    'habitaciones' => [
        'habitaciones' => ['necesita_hotel_id' => true, 'orden' => 2, 'riesgo' => 'medio', 'motivo' => 'Entidad operativa central; primera candidata, pero conecta con reservaciones y ocupacion.'],
        'tipos_habitacion' => ['necesita_hotel_id' => true, 'orden' => 1, 'riesgo' => 'bajo', 'motivo' => 'Catalogo acotado por hotel; buena primera migracion.'],
        'habitacion_imagenes' => ['necesita_hotel_id' => true, 'orden' => 3, 'riesgo' => 'bajo', 'motivo' => 'Depende de habitaciones; migrar despues de habitaciones.'],
        'mantenimientos_habitaciones' => ['necesita_hotel_id' => true, 'orden' => 4, 'riesgo' => 'medio', 'motivo' => 'Operacion ligada a habitaciones; validar nombres reales y relaciones.'],
    ],
    'huespedes/reservaciones' => [
        'huespedes' => ['necesita_hotel_id' => true, 'orden' => 20, 'riesgo' => 'medio', 'motivo' => 'Datos compartibles por visitas; definir si huesped sera global o por hotel.'],
        'reservaciones' => ['necesita_hotel_id' => true, 'orden' => 30, 'riesgo' => 'alto', 'motivo' => 'Flujo critico con fechas, habitaciones, pagos y estados.'],
        'reservacion_habitaciones' => ['necesita_hotel_id' => true, 'orden' => 31, 'riesgo' => 'alto', 'motivo' => 'Tabla puente critica entre reservaciones y habitaciones.'],
        'reservacion_pagos' => ['necesita_hotel_id' => true, 'orden' => 32, 'riesgo' => 'alto', 'motivo' => 'Pagos de reservaciones; requiere conciliacion con caja.'],
        'reservacion_abonos' => ['necesita_hotel_id' => true, 'orden' => 33, 'riesgo' => 'alto', 'motivo' => 'Abonos ligados a reservaciones y cortes.'],
        'reservacion_notas' => ['necesita_hotel_id' => true, 'orden' => 34, 'riesgo' => 'medio', 'motivo' => 'Notas dependientes de reservaciones; migrar despues del nucleo.'],
        'solicitudes_factura' => ['necesita_hotel_id' => true, 'orden' => 35, 'riesgo' => 'medio', 'motivo' => 'Facturacion depende del contexto fiscal por hotel.'],
    ],
    'caja' => [
        'cajas' => ['necesita_hotel_id' => true, 'orden' => 40, 'riesgo' => 'alto', 'motivo' => 'Caja es critica; requiere aislamiento y pruebas de corte.'],
        'cortes_caja' => ['necesita_hotel_id' => true, 'orden' => 41, 'riesgo' => 'alto', 'motivo' => 'Cortes no pueden mezclarse entre hoteles.'],
        'movimientos_caja' => ['necesita_hotel_id' => true, 'orden' => 42, 'riesgo' => 'alto', 'motivo' => 'Movimientos financieros; requiere indices compuestos y auditoria.'],
        'denominaciones_efectivo' => ['necesita_hotel_id' => true, 'orden' => 43, 'riesgo' => 'medio', 'motivo' => 'Denominaciones/cortes pueden necesitar contexto de caja por hotel.'],
        'categorias_movimientos' => ['necesita_hotel_id' => true, 'orden' => 44, 'riesgo' => 'medio', 'motivo' => 'Catalogo configurable por hotel; migrar antes de movimientos si aplica.'],
    ],
    'inventario' => [
        'inventario_productos' => ['necesita_hotel_id' => true, 'orden' => 50, 'riesgo' => 'medio', 'motivo' => 'Stock por hotel; revisar codigos y unicidad.'],
        'inventario_categorias' => ['necesita_hotel_id' => true, 'orden' => 49, 'riesgo' => 'bajo', 'motivo' => 'Catalogo de inventario por hotel.'],
        'inventario_config_habitacion' => ['necesita_hotel_id' => true, 'orden' => 51, 'riesgo' => 'medio', 'motivo' => 'Configuracion por habitacion; depende de habitaciones e inventario.'],
        'inventario_habitacion_config' => ['necesita_hotel_id' => true, 'orden' => 51, 'riesgo' => 'medio', 'motivo' => 'Posible variante de configuracion por habitacion.'],
        'inventario_movimientos' => ['necesita_hotel_id' => true, 'orden' => 52, 'riesgo' => 'medio', 'motivo' => 'Movimientos de inventario; validar trazabilidad por hotel.'],
        'movimientos_inventario' => ['necesita_hotel_id' => true, 'orden' => 52, 'riesgo' => 'medio', 'motivo' => 'Movimientos de inventario; validar nombre real usado por el sistema.'],
        'productos' => ['necesita_hotel_id' => true, 'orden' => 48, 'riesgo' => 'medio', 'motivo' => 'Catalogo o stock, segun uso actual; requiere revisar unicidad.'],
        'categorias_producto' => ['necesita_hotel_id' => true, 'orden' => 47, 'riesgo' => 'bajo', 'motivo' => 'Catalogo por hotel si los productos son locales.'],
        'alertas_inventario' => ['necesita_hotel_id' => true, 'orden' => 53, 'riesgo' => 'medio', 'motivo' => 'Alertas deben aislarse por hotel y stock.'],
    ],
    'tarifas/configuracion' => [
        'tarifas_temporada' => ['necesita_hotel_id' => true, 'orden' => 10, 'riesgo' => 'medio', 'motivo' => 'Tarifas por hotel; revisar solapes de fechas y tipos.'],
        'incrementos_tarifas' => ['necesita_hotel_id' => true, 'orden' => 11, 'riesgo' => 'medio', 'motivo' => 'Reglas de tarifa por hotel; revisar indices de fechas.'],
        'configuracion' => ['necesita_hotel_id' => true, 'orden' => 12, 'riesgo' => 'medio', 'motivo' => 'Configuracion historica mono-hotel; evaluar migracion hacia hotel_configuracion.'],
    ],
    'operacion' => [
        'control_llaves' => ['necesita_hotel_id' => true, 'orden' => 14, 'riesgo' => 'medio', 'motivo' => 'Operacion por habitacion; migrar despues de habitaciones.'],
        'historial_llaves' => ['necesita_hotel_id' => true, 'orden' => 15, 'riesgo' => 'medio', 'motivo' => 'Historial operativo; preservar trazabilidad por hotel.'],
        'control_remotos' => ['necesita_hotel_id' => true, 'orden' => 14, 'riesgo' => 'medio', 'motivo' => 'Operacion por habitacion; migrar despues de habitaciones.'],
        'historial_remotos' => ['necesita_hotel_id' => true, 'orden' => 15, 'riesgo' => 'medio', 'motivo' => 'Historial operativo; preservar trazabilidad por hotel.'],
    ],
    'PWA/sync/acceso' => [
        'sync_queue' => ['necesita_hotel_id' => true, 'orden' => 90, 'riesgo' => 'alto', 'motivo' => 'Offline/sync es alto riesgo; no tocar hasta tener aislamiento probado.'],
        'push_subscriptions' => ['necesita_hotel_id' => true, 'orden' => 91, 'riesgo' => 'alto', 'motivo' => 'Notificaciones pueden cruzar hoteles si no hay contexto seguro.'],
        'remember_tokens' => ['necesita_hotel_id' => true, 'orden' => 92, 'riesgo' => 'medio', 'motivo' => 'Login/sesion no se toca todavia; evaluar contexto activo por usuario en fase posterior.'],
        'logs_acceso' => ['necesita_hotel_id' => true, 'orden' => 93, 'riesgo' => 'medio', 'motivo' => 'Logs de acceso pueden requerir contexto de hotel activo.'],
    ],
];

$riesgoPeso = ['alto' => 3, 'medio' => 2, 'bajo' => 1];
$tablasRevisadas = 0;
$tablasExistentes = 0;
$tablasFaltantes = 0;
$tablasConHotelId = [];
$tablasSinHotelIdNecesarias = [];
$riesgosDetectados = [];
$hotelLosCedrosId = null;

echo "Preflight hotel_id SaaS local - Base: {$databaseName}\n";
echo str_repeat('=', 72) . "\n";
echo "[INFO] Analisis de solo lectura. No ejecuta ALTER/INSERT/UPDATE/DELETE.\n";

if (existeTablaPreflight($pdo, $databaseName, 'hoteles')) {
    $hotelLosCedrosId = obtenerIdHotelLosCedros($pdo);
    if ($hotelLosCedrosId !== null) {
        preflightOk("Hotel Los Cedros encontrado con id {$hotelLosCedrosId}");
    } else {
        preflightError('Hotel Los Cedros no existe; no se puede validar Fase 2A.1');
    }
}

if (existeTablaPreflight($pdo, $databaseName, 'migrations')) {
    $estadoMigracionHabitaciones = obtenerEstadoMigracion($pdo, '20260526_003_add_hotel_id_habitaciones.sql');
    if ($estadoMigracionHabitaciones === 'ejecutada') {
        preflightOk('Migracion 20260526_003_add_hotel_id_habitaciones.sql registrada como ejecutada');
    } elseif ($estadoMigracionHabitaciones !== null) {
        preflightWarn("Migracion 20260526_003_add_hotel_id_habitaciones.sql registrada con estado {$estadoMigracionHabitaciones}");
    } else {
        preflightError('Migracion 20260526_003_add_hotel_id_habitaciones.sql no esta registrada');
    }

    $estadoMigracionInventarioBase = obtenerEstadoMigracion($pdo, '20260526_006_add_hotel_id_inventario_base.sql');
    if ($estadoMigracionInventarioBase === 'ejecutada') {
        preflightOk('Migracion 20260526_006_add_hotel_id_inventario_base.sql registrada como ejecutada');
    } elseif ($estadoMigracionInventarioBase !== null) {
        preflightWarn("Migracion 20260526_006_add_hotel_id_inventario_base.sql registrada con estado {$estadoMigracionInventarioBase}");
    } else {
        preflightError('Migracion 20260526_006_add_hotel_id_inventario_base.sql no esta registrada');
    }

    $estadoMigracionMovimientosInventario = obtenerEstadoMigracion($pdo, '20260526_007_add_hotel_id_movimientos_inventario.sql');
    if ($estadoMigracionMovimientosInventario === 'ejecutada') {
        preflightOk('Migracion 20260526_007_add_hotel_id_movimientos_inventario.sql registrada como ejecutada');
    } elseif ($estadoMigracionMovimientosInventario !== null) {
        preflightWarn("Migracion 20260526_007_add_hotel_id_movimientos_inventario.sql registrada con estado {$estadoMigracionMovimientosInventario}");
    } else {
        preflightError('Migracion 20260526_007_add_hotel_id_movimientos_inventario.sql no esta registrada');
    }

    $estadoMigracionReservacionesBase = obtenerEstadoMigracion($pdo, '20260526_008_add_hotel_id_reservaciones_base.sql');
    if ($estadoMigracionReservacionesBase === 'ejecutada') {
        preflightOk('Migracion 20260526_008_add_hotel_id_reservaciones_base.sql registrada como ejecutada');
    } elseif ($estadoMigracionReservacionesBase !== null) {
        preflightWarn("Migracion 20260526_008_add_hotel_id_reservaciones_base.sql registrada con estado {$estadoMigracionReservacionesBase}");
    } else {
        preflightError('Migracion 20260526_008_add_hotel_id_reservaciones_base.sql no esta registrada');
    }
}

foreach ($tablas as $grupo => $grupoTablas) {
    echo "\n";
    preflightInfo("Grupo {$grupo}");

    foreach ($grupoTablas as $tabla => $metadata) {
        $tablasRevisadas++;
        echo str_repeat('-', 72) . "\n";

        if (!existeTablaPreflight($pdo, $databaseName, $tabla)) {
            $tablasFaltantes++;
            preflightWarn("Tabla {$tabla} no existe; se omite analisis estructural");
            preflightInfo("Necesitara hotel_id: " . ($metadata['necesita_hotel_id'] ? 'si, en fase posterior' : 'no'));
            preflightInfo("Riesgo estimado: {$metadata['riesgo']} - {$metadata['motivo']}");
            preflightInfo("Orden recomendado de migracion: {$metadata['orden']}");
            continue;
        }

        $tablasExistentes++;
        $columnas = obtenerColumnas($pdo, $databaseName, $tabla);
        $primaryKey = obtenerPrimaryKey($pdo, $databaseName, $tabla);
        $foraneas = obtenerForaneas($pdo, $databaseName, $tabla);
        $indicesUnicos = obtenerIndicesUnicos($pdo, $databaseName, $tabla);
        $tieneHotelId = in_array('hotel_id', $columnas, true);
        $totalRegistros = contarRegistros($pdo, $tabla);

        preflightOk("Tabla {$tabla} existe (registros: {$totalRegistros})");
        preflightInfo("Llave primaria: " . formatearLista($primaryKey));
        preflightInfo("Llaves foraneas: " . formatearForaneas($foraneas));
        preflightInfo("Indices unicos: " . formatearIndicesUnicos($indicesUnicos));

        if ($tieneHotelId) {
            $tablasConHotelId[] = $tabla;
            preflightOk("Tabla {$tabla} ya tiene hotel_id");
        } elseif (array_key_exists($tabla, $tablasMigradasConHotelId)) {
            preflightError("Tabla {$tabla} deberia tener hotel_id despues de su migracion tenant");
            $tablasSinHotelIdNecesarias[] = $tabla;
        } elseif ($metadata['necesita_hotel_id']) {
            $tablasSinHotelIdNecesarias[] = $tabla;
            preflightOk("Tabla {$tabla} todavia no tiene hotel_id");
        } else {
            preflightOk("Tabla {$tabla} no requiere hotel_id directo");
        }

        if ($tieneHotelId && !in_array($tabla, $tablasPermitidasConHotelIdPostReservaciones1B, true)) {
            preflightError("Tabla {$tabla} tiene hotel_id antes de la fase autorizada");
        }

        if (array_key_exists($tabla, $tablasMigradasConHotelId) && $tieneHotelId) {
            if ($hotelLosCedrosId === null) {
                preflightError("No se puede validar backfill de {$tabla} porque no existe Los Cedros");
            } else {
                $stmt = $pdo->prepare(
                    'SELECT COUNT(*) AS total,
                            SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                            SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
                     FROM ' . quoteIdentifier($tabla)
                );
                $stmt->execute(['hotel_id' => $hotelLosCedrosId]);
                $conteoHotel = $stmt->fetch(PDO::FETCH_ASSOC);
                $totalHotel = (int) $conteoHotel['total'];
                $conLosCedros = (int) $conteoHotel['con_los_cedros'];
                $hotelIdNull = (int) $conteoHotel['hotel_id_null'];

                if ($hotelIdNull === 0) {
                    preflightOk("Tabla {$tabla} no tiene hotel_id NULL");
                } else {
                    preflightError("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
                }

                if ($totalHotel === $conLosCedros) {
                    preflightOk("Tabla {$tabla} tiene {$conLosCedros}/{$totalHotel} registros asignados a Los Cedros");
                } else {
                    preflightError("Tabla {$tabla} tiene {$conLosCedros}/{$totalHotel} registros asignados a Los Cedros");
                }
            }

            if (existeIndicePreflight($pdo, $databaseName, $tabla, $tablasMigradasConHotelId[$tabla]['indice'])) {
                preflightOk("Indice {$tablasMigradasConHotelId[$tabla]['indice']} existe");
            } else {
                preflightError("Indice {$tablasMigradasConHotelId[$tabla]['indice']} no existe");
            }

            if (isset($tablasMigradasConHotelId[$tabla]['foreign_key'])) {
                if (existeForeignKeyPreflight($pdo, $databaseName, $tabla, $tablasMigradasConHotelId[$tabla]['foreign_key'])) {
                    preflightOk("Foreign key {$tablasMigradasConHotelId[$tabla]['foreign_key']} existe hacia hoteles(id)");
                } else {
                    preflightError("Foreign key {$tablasMigradasConHotelId[$tabla]['foreign_key']} no existe hacia hoteles(id)");
                }
            } else {
                preflightOk("Tabla {$tabla} no requiere foreign key estricta de hotel_id en esta fase");
            }
        }

        $necesitaTexto = $metadata['necesita_hotel_id'] ? 'si, en Fase 2 o posterior' : 'no';
        if (array_key_exists($tabla, $tablasHabitacionesMigradas)) {
            $necesitaTexto .= '; migrada en Fase 2A.1, con codigo de Habitaciones ya scoped';
        } elseif (array_key_exists($tabla, $tablasInventarioBaseMigradas)) {
            $necesitaTexto .= '; migrada en Inventario 1-C, con codigo base de Inventario ya scoped';
        } elseif (array_key_exists($tabla, $tablasMovimientosInventarioMigradas)) {
            $necesitaTexto .= '; migrada en Inventario 1-E-B, pendiente integracion funcional de movimientos';
        } elseif (array_key_exists($tabla, $tablasReservacionesBaseMigradas)) {
            $necesitaTexto .= '; migrada en Reservaciones 1-B, pendiente integracion funcional de Reservaciones';
        } elseif (in_array($tabla, $primerasCandidatas, true)) {
            $necesitaTexto .= '; primera candidata';
        }
        preflightInfo("Necesitara hotel_id: {$necesitaTexto}");

        $riesgo = in_array($tabla, $altoRiesgo, true) ? 'alto' : $metadata['riesgo'];
        $motivo = in_array($tabla, $altoRiesgo, true) ? 'Tabla marcada explicitamente como alto riesgo para multi-hotel.' : $metadata['motivo'];
        preflightInfo("Riesgo estimado: {$riesgo} - {$motivo}");
        preflightInfo("Orden recomendado de migracion: {$metadata['orden']}");

        $indicesCompuestos = [];
        if ($metadata['necesita_hotel_id']) {
            foreach ($indicesUnicos as $nombreIndice => $columnasIndice) {
                if (!in_array('hotel_id', $columnasIndice, true)) {
                    $indicesCompuestos[] = $nombreIndice . '(hotel_id, ' . implode(', ', $columnasIndice) . ')';
                }
            }
        }

        if ($indicesCompuestos !== []) {
            preflightInfo("Posibles indices compuestos con hotel_id: " . implode(' | ', $indicesCompuestos));
        } else {
            preflightInfo('Posibles indices compuestos con hotel_id: ninguno detectado por indices unicos actuales');
        }

        $riesgosDetectados[] = [
            'tabla' => $tabla,
            'riesgo' => $riesgo,
            'motivo' => $motivo,
            'orden' => $metadata['orden'],
            'registros' => $totalRegistros,
            'peso' => $riesgoPeso[$riesgo] ?? 0,
        ];
    }
}

usort(
    $riesgosDetectados,
    static function (array $a, array $b): int {
        if ($a['peso'] !== $b['peso']) {
            return $b['peso'] <=> $a['peso'];
        }

        if ($a['registros'] !== $b['registros']) {
            return $b['registros'] <=> $a['registros'];
        }

        return $a['orden'] <=> $b['orden'];
    }
);

$topRiesgos = array_slice($riesgosDetectados, 0, 5);

echo "\n" . str_repeat('=', 72) . "\n";
echo "Resumen preflight hotel_id\n";
echo str_repeat('=', 72) . "\n";
echo "Total de tablas revisadas: {$tablasRevisadas}\n";
echo "Total de tablas existentes: {$tablasExistentes}\n";
echo "Total de tablas faltantes: {$tablasFaltantes}\n";
echo "Tablas con hotel_id: " . formatearLista($tablasConHotelId) . "\n";
echo "Tablas sin hotel_id que lo necesitaran: " . formatearLista($tablasSinHotelIdNecesarias) . "\n";
echo "Top 5 riesgos:\n";

if ($topRiesgos === []) {
    echo "- ninguno\n";
} else {
    foreach ($topRiesgos as $riesgo) {
        echo sprintf(
            "- %s: %s, %s registros, orden %s. %s\n",
            $riesgo['tabla'],
            $riesgo['riesgo'],
            $riesgo['registros'],
            $riesgo['orden'],
            $riesgo['motivo']
        );
    }
}

echo "Recomendacion de siguiente fase: actualizar codigo base de Reservaciones solo en fase aprobada; no tocar Caja, PWA/sync ni check-in/check-out todavia.\n";
echo "Total OK: {$ok}\n";
echo "Total WARN: {$warnings}\n";
echo "Total ERROR: {$errors}\n";
echo 'Resultado general: ' . ($errors === 0 ? 'PASS' : 'FAIL') . "\n";

exit($errors === 0 ? 0 : 1);
