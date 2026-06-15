<?php
/**
 * Herramienta local de verificacion SaaS multi-hotel.
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

function reportar(string $nivel, string $mensaje): void
{
    echo "[{$nivel}] {$mensaje}\n";
}

function ok(string $mensaje): void
{
    global $ok;
    $ok++;
    reportar('OK', $mensaje);
}

function warn(string $mensaje): void
{
    global $warnings;
    $warnings++;
    reportar('WARN', $mensaje);
}

function errorCheck(string $mensaje): void
{
    global $errors;
    $errors++;
    reportar('ERROR', $mensaje);
}

function existeTabla(PDO $pdo, string $databaseName, string $tabla): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla]);

    return (int) $stmt->fetchColumn() > 0;
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function existeColumna(PDO $pdo, string $databaseName, string $tabla, string $columna): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla, 'columna' => $columna]);

    return (int) $stmt->fetchColumn() > 0;
}

function existeIndice(PDO $pdo, string $databaseName, string $tabla, string $indice): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :tabla AND INDEX_NAME = :indice'
    );
    $stmt->execute(['schema' => $databaseName, 'tabla' => $tabla, 'indice' => $indice]);

    return (int) $stmt->fetchColumn() > 0;
}

function existeForeignKey(PDO $pdo, string $databaseName, string $tabla, string $constraint): bool
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

function obtenerValorConfiguracion(PDO $pdo, string $clave): ?array
{
    $stmt = $pdo->prepare(
        "SELECT hc.valor, hc.tipo, hc.es_feature_flag
         FROM hotel_configuracion hc
         JOIN hoteles h ON h.id = hc.hotel_id
         WHERE h.slug = 'los-cedros'
           AND hc.clave = :clave
         LIMIT 1"
    );
    $stmt->execute(['clave' => $clave]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
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
    errorCheck('No fue posible conectar a la base de datos local: ' . $e->getMessage());
    echo "\nTotal OK: {$ok}\n";
    echo "Total WARN: {$warnings}\n";
    echo "Total ERROR: {$errors}\n";
    echo "Resultado general: FAIL\n";
    exit(1);
}

echo "Verificacion SaaS local - Base: {$databaseName}\n";
echo str_repeat('=', 56) . "\n";

$hotel = null;

$tablasSaas = [
    'migrations',
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
    'proveedores',
];

$tablasDisponibles = [];
foreach ($tablasSaas as $tabla) {
    if (existeTabla($pdo, $databaseName, $tabla)) {
        $tablasDisponibles[$tabla] = true;
        ok("Tabla {$tabla} existe");
    } else {
        $tablasDisponibles[$tabla] = false;
        errorCheck("Tabla {$tabla} no existe");
    }
}

if ($tablasDisponibles['migrations']) {
    $migracionesEsperadas = [
        '20260526_001_crear_base_saas_multihotel.sql',
        '20260526_002_seed_hotel_los_cedros.sql',
        '20260526_003_add_hotel_id_habitaciones.sql',
        '20260526_006_add_hotel_id_inventario_base.sql',
        '20260526_007_add_hotel_id_movimientos_inventario.sql',
        '20260526_008_add_hotel_id_reservaciones_base.sql',
        '20260526_009_add_hotel_id_caja_base.sql',
        '20260526_010_create_saas_admins.sql',
        '20260526_011_create_modulos_hotel_modulos.sql',
        '20260526_012_create_planes_plan_modulos.sql',
        '20260526_013_create_hotel_branding.sql',
        '20260526_014_add_pwa_icons_to_hotel_branding.sql',
        '20260611_001_create_reporte_links.sql',
        '20260611_002_create_reporte_link_envios.sql',
        '20260611_003_create_notificaciones.sql',
        '20260611_004_create_pwa_push_subscriptions.sql',
        '20260614_001_add_modulo_tarifas_dinamicas.sql',
        '20260614_002_fase_1b_modulos_los_cedros_auditoria.sql',
        '20260614_003_fix_vista_caja_actual_invoker.sql',
        '20260614_004_fase_2f_catalogo_proveedores.sql',
        '20260615_001_fase_2j_unique_proveedores.sql',
    ];

    $stmt = $pdo->prepare('SELECT estado FROM migrations WHERE nombre = :nombre LIMIT 1');
    foreach ($migracionesEsperadas as $migracion) {
        $stmt->execute(['nombre' => $migracion]);
        $estado = $stmt->fetchColumn();

        if ($estado === 'ejecutada') {
            ok("Migracion {$migracion} registrada como ejecutada");
        } elseif ($estado !== false) {
            warn("Migracion {$migracion} registrada con estado {$estado}");
        } else {
            errorCheck("Migracion {$migracion} no esta registrada");
        }
    }
}

if ($tablasDisponibles['hoteles']) {
    $stmt = $pdo->prepare(
        "SELECT id, nombre, slug, codigo, activo
         FROM hoteles
         WHERE slug = 'los-cedros'
         LIMIT 1"
    );
    $stmt->execute();
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hotel && $hotel['nombre'] === 'Los Cedros' && $hotel['codigo'] === 'LOS_CEDROS') {
        ok('Hotel Los Cedros encontrado con slug los-cedros y codigo LOS_CEDROS');
    } elseif ($hotel) {
        errorCheck('Hotel los-cedros existe, pero nombre o codigo no coinciden con lo esperado');
    } else {
        errorCheck('Hotel Los Cedros no encontrado');
    }
}

if ($tablasDisponibles['hotel_configuracion'] && $tablasDisponibles['hoteles']) {
    $configuracionesBase = [
        'hotel.nombre',
        'hotel.direccion',
        'hotel.zona_horaria',
        'hotel.moneda_codigo',
        'hotel.moneda_simbolo',
        'hotel.habitaciones_actuales',
        'sistema.modo_compatibilidad_mono_hotel',
    ];

    foreach ($configuracionesBase as $clave) {
        $config = obtenerValorConfiguracion($pdo, $clave);
        if ($config !== null) {
            ok("Configuracion {$clave} = {$config['valor']}");
        } else {
            errorCheck("Configuracion {$clave} no existe para Los Cedros");
        }
    }

    $featureFlags = [
        'feature.multi_hotel' => 'false',
        'feature.selector_hotel' => 'false',
        'feature.audit_logs' => 'false',
        'feature.hotel_configuracion' => 'true',
        'feature.saas_billing' => 'false',
        'feature.bitacora_inteligente' => 'false',
        'feature.whatsapp_ejecutivo' => 'false',
        'feature.ia_ejecutiva' => 'false',
    ];

    foreach ($featureFlags as $clave => $valorEsperado) {
        $config = obtenerValorConfiguracion($pdo, $clave);
        if ($config === null) {
            errorCheck("Feature flag {$clave} no existe para Los Cedros");
            continue;
        }

        if ((string) $config['valor'] !== $valorEsperado) {
            errorCheck("Feature flag {$clave} = {$config['valor']}; esperado {$valorEsperado}");
            continue;
        }

        if ((int) $config['es_feature_flag'] !== 1) {
            warn("Configuracion {$clave} existe, pero no esta marcada como feature flag");
            continue;
        }

        ok("Feature {$clave} = {$valorEsperado}");
    }
}

if ($tablasDisponibles['hotel_usuarios'] && $tablasDisponibles['hoteles']) {
    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM hotel_usuarios hu
         JOIN hoteles h ON h.id = hu.hotel_id
         WHERE h.slug = 'los-cedros'"
    );
    $totalUsuarios = (int) $stmt->fetchColumn();

    if ($totalUsuarios > 0) {
        ok("Relaciones hotel_usuarios para Los Cedros encontradas: {$totalUsuarios}");
    } else {
        errorCheck('No hay relaciones en hotel_usuarios para Los Cedros');
    }
}

$tablasPermitidasConHotelId = [
    'hotel_configuracion',
    'hotel_usuarios',
    'hotel_modulos',
    'hotel_branding',
    'logs_auditoria',
    'tipos_habitacion',
    'habitaciones',
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

$stmt = $pdo->prepare(
    "SELECT TABLE_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = :schema
       AND COLUMN_NAME = 'hotel_id'
     ORDER BY TABLE_NAME"
);
$stmt->execute(['schema' => $databaseName]);
$tablasConHotelId = $stmt->fetchAll(PDO::FETCH_COLUMN);
$tablasNoPermitidas = array_values(array_diff($tablasConHotelId, $tablasPermitidasConHotelId));
$tablasPermitidasFaltantes = array_values(array_diff($tablasPermitidasConHotelId, $tablasConHotelId));

if ($tablasNoPermitidas === []) {
    ok('Columnas hotel_id solo existen en tablas permitidas para el estado post Reservaciones 1-F-B');
} else {
    errorCheck('Columnas hotel_id encontradas fuera de tablas permitidas: ' . implode(', ', $tablasNoPermitidas));
}

foreach ($tablasPermitidasFaltantes as $tablaFaltante) {
    errorCheck("La tabla {$tablaFaltante} no tiene columna hotel_id esperada");
}

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

$tablasCajaBaseMigradas = [
    'cajas' => [
        'indice' => 'idx_cajas_hotel_id',
    ],
    'cortes_caja' => [
        'indice' => 'idx_cortes_caja_hotel_id',
    ],
    'movimientos_caja' => [
        'indice' => 'idx_movimientos_caja_hotel_id',
    ],
];

if ($hotel === null || !isset($hotel['id'])) {
    errorCheck('No se puede validar hotel_id en Habitaciones porque no se encontro Los Cedros');
} else {
    foreach ($tablasHabitacionesMigradas as $tabla => $metadata) {
        if (!existeTabla($pdo, $databaseName, $tabla)) {
            errorCheck("Tabla {$tabla} no existe para validar hotel_id post Fase 2A.1");
            continue;
        }

        if (!existeColumna($pdo, $databaseName, $tabla, 'hotel_id')) {
            errorCheck("Tabla {$tabla} no tiene hotel_id post Fase 2A.1");
            continue;
        }

        ok("Tabla {$tabla} tiene hotel_id post Fase 2A.1");

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM ' . quoteIdentifier($tabla)
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $conteo['total'];
        $conLosCedros = (int) $conteo['con_los_cedros'];
        $hotelIdNull = (int) $conteo['hotel_id_null'];

        if ($hotelIdNull === 0) {
            ok("Tabla {$tabla} no tiene hotel_id NULL en registros existentes");
        } else {
            errorCheck("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if ($total === $conLosCedros) {
            ok("Tabla {$tabla} tiene {$conLosCedros}/{$total} registros asignados a Los Cedros");
        } else {
            warn("Tabla {$tabla} tiene distribucion multihotel: {$conLosCedros}/{$total} registros asignados a Los Cedros");
        }

        if (existeIndice($pdo, $databaseName, $tabla, $metadata['indice'])) {
            ok("Indice {$metadata['indice']} existe");
        } else {
            errorCheck("Indice {$metadata['indice']} no existe");
        }

        if (existeForeignKey($pdo, $databaseName, $tabla, $metadata['foreign_key'])) {
            ok("Foreign key {$metadata['foreign_key']} existe hacia hoteles(id)");
        } else {
            errorCheck("Foreign key {$metadata['foreign_key']} no existe hacia hoteles(id)");
        }
    }
}

if ($hotel === null || !isset($hotel['id'])) {
    errorCheck('No se puede validar hotel_id en Inventario base porque no se encontro Los Cedros');
} else {
    foreach ($tablasInventarioBaseMigradas as $tabla => $metadata) {
        if (!existeTabla($pdo, $databaseName, $tabla)) {
            errorCheck("Tabla {$tabla} no existe para validar hotel_id post Inventario 1-C");
            continue;
        }

        if (!existeColumna($pdo, $databaseName, $tabla, 'hotel_id')) {
            errorCheck("Tabla {$tabla} no tiene hotel_id post Inventario 1-C");
            continue;
        }

        ok("Tabla {$tabla} tiene hotel_id post Inventario 1-C");

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM ' . quoteIdentifier($tabla)
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $conteo['total'];
        $conLosCedros = (int) $conteo['con_los_cedros'];
        $hotelIdNull = (int) $conteo['hotel_id_null'];

        if ($hotelIdNull === 0) {
            ok("Tabla {$tabla} no tiene hotel_id NULL en registros existentes");
        } else {
            errorCheck("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if ($total === $conLosCedros) {
            ok("Tabla {$tabla} tiene {$conLosCedros}/{$total} registros asignados a Los Cedros");
        } else {
            warn("Tabla {$tabla} tiene distribucion multihotel: {$conLosCedros}/{$total} registros asignados a Los Cedros");
        }

        if (existeIndice($pdo, $databaseName, $tabla, $metadata['indice'])) {
            ok("Indice {$metadata['indice']} existe");
        } else {
            errorCheck("Indice {$metadata['indice']} no existe");
        }

        if (existeForeignKey($pdo, $databaseName, $tabla, $metadata['foreign_key'])) {
            ok("Foreign key {$metadata['foreign_key']} existe hacia hoteles(id)");
        } else {
            errorCheck("Foreign key {$metadata['foreign_key']} no existe hacia hoteles(id)");
        }
    }
}

if ($hotel === null || !isset($hotel['id'])) {
    errorCheck('No se puede validar hotel_id en movimientos_inventario porque no se encontro Los Cedros');
} else {
    foreach ($tablasMovimientosInventarioMigradas as $tabla => $metadata) {
        if (!existeTabla($pdo, $databaseName, $tabla)) {
            errorCheck("Tabla {$tabla} no existe para validar hotel_id post Inventario 1-E-B");
            continue;
        }

        if (!existeColumna($pdo, $databaseName, $tabla, 'hotel_id')) {
            errorCheck("Tabla {$tabla} no tiene hotel_id post Inventario 1-E-B");
            continue;
        }

        ok("Tabla {$tabla} tiene hotel_id post Inventario 1-E-B");

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM ' . quoteIdentifier($tabla)
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $conteo['total'];
        $conLosCedros = (int) $conteo['con_los_cedros'];
        $hotelIdNull = (int) $conteo['hotel_id_null'];

        if ($hotelIdNull === 0) {
            ok("Tabla {$tabla} no tiene hotel_id NULL en registros existentes");
        } else {
            errorCheck("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if ($total === $conLosCedros) {
            ok("Tabla {$tabla} tiene {$conLosCedros}/{$total} registros asignados a Los Cedros");
        } else {
            warn("Tabla {$tabla} tiene distribucion multihotel: {$conLosCedros}/{$total} registros asignados a Los Cedros");
        }

        if (existeIndice($pdo, $databaseName, $tabla, $metadata['indice'])) {
            ok("Indice {$metadata['indice']} existe");
        } else {
            errorCheck("Indice {$metadata['indice']} no existe");
        }

        if (existeForeignKey($pdo, $databaseName, $tabla, $metadata['foreign_key'])) {
            ok("Foreign key {$metadata['foreign_key']} existe hacia hoteles(id)");
        } else {
            errorCheck("Foreign key {$metadata['foreign_key']} no existe hacia hoteles(id)");
        }
    }
}

if ($hotel === null || !isset($hotel['id'])) {
    errorCheck('No se puede validar hotel_id en Reservaciones base porque no se encontro Los Cedros');
} else {
    foreach ($tablasReservacionesBaseMigradas as $tabla => $metadata) {
        if (!existeTabla($pdo, $databaseName, $tabla)) {
            errorCheck("Tabla {$tabla} no existe para validar hotel_id post Reservaciones 1-B");
            continue;
        }

        if (!existeColumna($pdo, $databaseName, $tabla, 'hotel_id')) {
            errorCheck("Tabla {$tabla} no tiene hotel_id post Reservaciones 1-B");
            continue;
        }

        ok("Tabla {$tabla} tiene hotel_id post Reservaciones 1-B");

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM ' . quoteIdentifier($tabla)
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $conteo['total'];
        $conLosCedros = (int) $conteo['con_los_cedros'];
        $hotelIdNull = (int) $conteo['hotel_id_null'];

        if ($hotelIdNull === 0) {
            ok("Tabla {$tabla} no tiene hotel_id NULL en registros existentes");
        } else {
            errorCheck("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if ($total === $conLosCedros) {
            ok("Tabla {$tabla} tiene {$conLosCedros}/{$total} registros asignados a Los Cedros");
        } else {
            warn("Tabla {$tabla} tiene distribucion multihotel: {$conLosCedros}/{$total} registros asignados a Los Cedros");
        }

        if (existeIndice($pdo, $databaseName, $tabla, $metadata['indice'])) {
            ok("Indice {$metadata['indice']} existe");
        } else {
            errorCheck("Indice {$metadata['indice']} no existe");
        }
    }

    if (existeTabla($pdo, $databaseName, 'reservaciones')) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM reservaciones
             WHERE id IN (48, 49)
               AND hotel_id = :hotel_id'
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $reservacionesFallback = (int) $stmt->fetchColumn();

        if ($reservacionesFallback === 2) {
            ok('Reservaciones 48 y 49 tienen hotel_id de Los Cedros');
        } else {
            warn("Reservaciones historicas 48 y 49 no pertenecen ambas a Los Cedros en esta base multihotel: {$reservacionesFallback}/2");
        }
    }
}

if ($hotel === null || !isset($hotel['id'])) {
    errorCheck('No se puede validar hotel_id en Caja base porque no se encontro Los Cedros');
} else {
    foreach ($tablasCajaBaseMigradas as $tabla => $metadata) {
        if (!existeTabla($pdo, $databaseName, $tabla)) {
            errorCheck("Tabla {$tabla} no existe para validar hotel_id post Reservaciones 1-F-B");
            continue;
        }

        if (!existeColumna($pdo, $databaseName, $tabla, 'hotel_id')) {
            errorCheck("Tabla {$tabla} no tiene hotel_id post Reservaciones 1-F-B");
            continue;
        }

        ok("Tabla {$tabla} tiene hotel_id post Reservaciones 1-F-B");

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id = :hotel_id THEN 1 ELSE 0 END) AS con_los_cedros,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM ' . quoteIdentifier($tabla)
        );
        $stmt->execute(['hotel_id' => $hotel['id']]);
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = (int) $conteo['total'];
        $conLosCedros = (int) $conteo['con_los_cedros'];
        $hotelIdNull = (int) $conteo['hotel_id_null'];

        if ($hotelIdNull === 0) {
            ok("Tabla {$tabla} no tiene hotel_id NULL en registros existentes");
        } else {
            errorCheck("Tabla {$tabla} tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if ($total === $conLosCedros) {
            ok("Tabla {$tabla} tiene {$conLosCedros}/{$total} registros asignados a Los Cedros");
        } else {
            warn("Tabla {$tabla} tiene distribucion multihotel: {$conLosCedros}/{$total} registros asignados a Los Cedros");
        }

        if (existeIndice($pdo, $databaseName, $tabla, $metadata['indice'])) {
            ok("Indice {$metadata['indice']} existe");
        } else {
            errorCheck("Indice {$metadata['indice']} no existe");
        }
    }
}

if ($tablasDisponibles['proveedores'] ?? false) {
    if (existeColumna($pdo, $databaseName, 'proveedores', 'hotel_id')) {
        ok('Tabla proveedores tiene hotel_id post Fase 2F');

        $stmt = $pdo->query(
            'SELECT COUNT(*) AS total,
                    SUM(CASE WHEN hotel_id IS NULL THEN 1 ELSE 0 END) AS hotel_id_null
             FROM proveedores'
        );
        $conteo = $stmt->fetch(PDO::FETCH_ASSOC);
        $hotelIdNull = (int) ($conteo['hotel_id_null'] ?? 0);

        if ($hotelIdNull === 0) {
            ok('Tabla proveedores no tiene hotel_id NULL en registros existentes');
        } else {
            errorCheck("Tabla proveedores tiene {$hotelIdNull} registros con hotel_id NULL");
        }

        if (existeIndice($pdo, $databaseName, 'proveedores', 'idx_proveedores_hotel_activo')) {
            ok('Indice idx_proveedores_hotel_activo existe');
        } else {
            errorCheck('Indice idx_proveedores_hotel_activo no existe');
        }

        if (existeColumna($pdo, $databaseName, 'proveedores', 'nombre_activo_key')) {
            ok('Columna generada nombre_activo_key existe para proveedores');
        } else {
            errorCheck('Columna generada nombre_activo_key no existe para proveedores');
        }

        if (existeIndice($pdo, $databaseName, 'proveedores', 'uk_proveedores_hotel_rfc')) {
            ok('Indice unico uk_proveedores_hotel_rfc existe');
        } else {
            errorCheck('Indice unico uk_proveedores_hotel_rfc no existe');
        }

        if (existeIndice($pdo, $databaseName, 'proveedores', 'uk_proveedores_hotel_nombre_activo')) {
            ok('Indice unico uk_proveedores_hotel_nombre_activo existe');
        } else {
            errorCheck('Indice unico uk_proveedores_hotel_nombre_activo no existe');
        }

        if (existeForeignKey($pdo, $databaseName, 'proveedores', 'fk_proveedores_hotel')) {
            ok('Foreign key fk_proveedores_hotel existe hacia hoteles(id)');
        } else {
            errorCheck('Foreign key fk_proveedores_hotel no existe hacia hoteles(id)');
        }
    } else {
        errorCheck('Tabla proveedores no tiene hotel_id post Fase 2F');
    }
} else {
    errorCheck('Tabla proveedores no existe post Fase 2F');
}

$tablasOperativasSinHotelIdEsperadas = [
    'huespedes',
    'sync_queue',
    'push_subscriptions',
    'inventario_movimientos',
    'alertas_inventario',
    'productos',
    'categorias_producto',
    'tarifas_temporada',
];

foreach ($tablasOperativasSinHotelIdEsperadas as $tablaOperativa) {
    if (!existeTabla($pdo, $databaseName, $tablaOperativa)) {
        warn("Tabla operativa {$tablaOperativa} no existe con ese nombre en la base actual");
        continue;
    }

    if (in_array($tablaOperativa, $tablasConHotelId, true)) {
        errorCheck("Tabla operativa {$tablaOperativa} ya contiene hotel_id");
    } else {
        ok("Tabla operativa {$tablaOperativa} no contiene hotel_id");
    }
}

echo str_repeat('=', 56) . "\n";
echo "Total OK: {$ok}\n";
echo "Total WARN: {$warnings}\n";
echo "Total ERROR: {$errors}\n";
echo 'Resultado general: ' . ($errors === 0 ? 'PASS' : 'FAIL') . "\n";

exit($errors === 0 ? 0 : 1);
