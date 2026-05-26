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

$tablasSaas = [
    'migrations',
    'hoteles',
    'hotel_configuracion',
    'hotel_usuarios',
    'logs_auditoria',
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
    'logs_auditoria',
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
    ok('Columnas hotel_id solo existen en tablas SaaS permitidas');
} else {
    errorCheck('Columnas hotel_id encontradas fuera de tablas permitidas: ' . implode(', ', $tablasNoPermitidas));
}

foreach ($tablasPermitidasFaltantes as $tablaFaltante) {
    errorCheck("La tabla {$tablaFaltante} no tiene columna hotel_id esperada");
}

$tablasOperativasEsperadas = [
    'habitaciones',
    'reservaciones',
    'huespedes',
    'cajas',
    'cortes_caja',
    'movimientos_caja',
    'inventario_productos',
    'tarifas_temporada',
];

foreach ($tablasOperativasEsperadas as $tablaOperativa) {
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
