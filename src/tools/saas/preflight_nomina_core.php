<?php
/**
 * Preflight Nomina Core - Fase 1 (base tecnica del bloque nomina_avanzada).
 *
 * Herramienta solo lectura. Verifica que la base tecnica del modulo de
 * nomina este completa y coherente: bloque comercial registrado, permisos
 * nomina.* en catalogo/presets/roles, claves de configuracion en el registry,
 * rutas y archivos presentes, y que el subsistema laboral existente
 * (tablas trabajador_*) siga intacto. No modifica nada.
 *
 * Uso: php src/tools/saas/preflight_nomina_core.php
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

function nomCoreLine(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . "\n";
}

function nomCoreOk(string $message): void
{
    global $ok;
    $ok++;
    nomCoreLine('OK', $message);
}

function nomCoreWarning(string $message, string $recommendation = ''): void
{
    global $warnings, $recommendations;
    $warnings++;
    nomCoreLine('WARNING', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

function nomCoreError(string $message, string $recommendation = ''): void
{
    global $errors, $recommendations;
    $errors++;
    nomCoreLine('ERROR', $message);
    if ($recommendation !== '') {
        $recommendations[] = $recommendation;
    }
}

$srcRoot = dirname(__DIR__, 2);          // .../src
$projectRoot = dirname($srcRoot);        // raiz del proyecto

echo "== Preflight Nomina Core (Fase 1) ==\n\n";

/* ---------------------------------------------------------------------
 * 1) Archivos de la fase presentes.
 * ------------------------------------------------------------------- */
$archivosEsperados = [
    'controlador NominaController' => $srcRoot . '/app/controllers/NominaController.php',
    'vista nomina/index' => $srcRoot . '/app/views/nomina/index.php',
    'vista nomina/configuracion' => $srcRoot . '/app/views/nomina/configuracion.php',
    'migracion del bloque' => $projectRoot . '/migrations/20260704_001_nomina_core_bloque_permisos.sql',
];

foreach ($archivosEsperados as $etiqueta => $ruta) {
    if (is_file($ruta)) {
        nomCoreOk('Existe ' . $etiqueta . '.');
    } else {
        nomCoreError('Falta ' . $etiqueta . ' (' . $ruta . ').', 'Completar los archivos de la Fase 1 antes de continuar.');
    }
}

/* ---------------------------------------------------------------------
 * 2) Rutas registradas.
 * ------------------------------------------------------------------- */
$routesPath = $srcRoot . '/config/routes.php';
$routesCode = is_file($routesPath) ? (string) file_get_contents($routesPath) : '';

$rutasEsperadas = [
    "GET /nomina" => "/\\\$router->get\\('\\/nomina'/",
    "GET /nomina/configuracion" => "/\\\$router->get\\('\\/nomina\\/configuracion'/",
    "POST /nomina/configuracion" => "/\\\$router->post\\('\\/nomina\\/configuracion'/",
];

foreach ($rutasEsperadas as $etiqueta => $patron) {
    if ($routesCode !== '' && preg_match($patron, $routesCode) === 1) {
        nomCoreOk('Ruta registrada: ' . $etiqueta . '.');
    } else {
        nomCoreError('Ruta no registrada: ' . $etiqueta . '.', 'Registrar las rutas /nomina en src/config/routes.php.');
    }
}

/* ---------------------------------------------------------------------
 * 3) Gate del controlador (validacion estatica).
 * ------------------------------------------------------------------- */
$controllerPath = $srcRoot . '/app/controllers/NominaController.php';
$controllerCode = is_file($controllerPath) ? (string) file_get_contents($controllerPath) : '';

if ($controllerCode !== '') {
    if (strpos($controllerCode, "require_hotel_module('nomina_avanzada')") !== false) {
        nomCoreOk('NominaController gatea el bloque nomina_avanzada en before().');
    } else {
        nomCoreError('NominaController NO gatea el bloque nomina_avanzada.', 'Agregar require_hotel_module(\'nomina_avanzada\') al before().');
    }

    if (strpos($controllerCode, "require_permission('nomina.view')") !== false) {
        nomCoreOk('NominaController exige el permiso nomina.view.');
    } else {
        nomCoreError('NominaController NO exige permiso nomina.view.', 'Agregar require_permission(\'nomina.view\') al before().');
    }

    if (strpos($controllerCode, "require_permission('nomina.configurar')") !== false) {
        nomCoreOk('La configuracion de nomina exige el permiso nomina.configurar.');
    } else {
        nomCoreWarning('No se detecto require_permission(\'nomina.configurar\') en el controlador.');
    }

    if (strpos($controllerCode, 'validateCSRF') !== false) {
        nomCoreOk('El POST de configuracion valida CSRF.');
    } else {
        nomCoreError('El POST de configuracion NO valida CSRF.', 'Llamar $this->validateCSRF() en guardarConfiguracionAction.');
    }
}

/* ---------------------------------------------------------------------
 * 4) Catalogo de permisos y presets.
 * ------------------------------------------------------------------- */
$permisosPath = $srcRoot . '/config/permisos.php';
$permisosNomina = [
    'nomina.view', 'nomina.empleados', 'nomina.salarios', 'nomina.incidencias',
    'nomina.calcular', 'nomina.cerrar', 'nomina.aprobar', 'nomina.reabrir',
    'nomina.pagar', 'nomina.exportar', 'nomina.configurar', 'nomina.all',
];

if (is_file($permisosPath)) {
    $permisosCfg = require $permisosPath;
    $grupoNomina = $permisosCfg['catalogo']['nomina'] ?? null;

    if (is_array($grupoNomina)) {
        $faltantes = array_diff($permisosNomina, array_keys($grupoNomina['permisos'] ?? []));
        if ($faltantes === []) {
            nomCoreOk('Catalogo de permisos: grupo nomina completo (' . count($permisosNomina) . ' permisos).');
        } else {
            nomCoreError('Catalogo de permisos incompleto; faltan: ' . implode(', ', $faltantes) . '.', 'Completar el grupo nomina en src/config/permisos.php.');
        }

        if (($grupoNomina['modulo'] ?? null) === 'nomina_avanzada') {
            nomCoreOk('El grupo nomina cruza con el bloque comercial nomina_avanzada.');
        } else {
            nomCoreWarning('El grupo nomina no declara modulo => nomina_avanzada; la UI de roles no bloqueara permisos sin contrato.');
        }
    } else {
        nomCoreError('config/permisos.php no tiene el grupo nomina en el catalogo.', 'Sin catalogo, Rol::sanitizarPermisos() descarta los permisos nomina.* en silencio.');
    }

    $presetGerente = $permisosCfg['presets']['gerente']['permisos'] ?? [];
    if (in_array('nomina.all', $presetGerente, true)) {
        nomCoreOk('Preset gerente incluye nomina.all (hoteles nuevos).');
    } else {
        nomCoreError('Preset gerente NO incluye nomina.all.', 'Agregar nomina.all al preset gerente en config/permisos.php.');
    }

    $presetAdmin = $permisosCfg['presets']['administrador']['permisos'] ?? [];
    $adminEsperados = ['nomina.view', 'nomina.incidencias', 'nomina.calcular'];
    $adminFaltantes = array_diff($adminEsperados, $presetAdmin);
    if ($adminFaltantes === []) {
        nomCoreOk('Preset administrador incluye nomina.view / incidencias / calcular.');
    } else {
        nomCoreError('Preset administrador incompleto; faltan: ' . implode(', ', $adminFaltantes) . '.');
    }
} else {
    nomCoreError('No se encontro src/config/permisos.php.');
}

/* ---------------------------------------------------------------------
 * 5) Claves nomina.* en ConfiguracionHotelRegistry.
 * ------------------------------------------------------------------- */
$registryPath = $srcRoot . '/app/models/ConfiguracionHotelRegistry.php';
$clavesNomina = [
    'nomina.modo', 'nomina.pais', 'nomina.redondeo',
    'nomina.permitir_horas_extra', 'nomina.permitir_descuentos_manuales',
    'nomina.requiere_aprobacion_cierre', 'nomina.permitir_reapertura',
];

if (is_file($registryPath)) {
    require_once $registryPath;

    if (class_exists('ConfiguracionHotelRegistry')) {
        $clavesFaltantes = [];
        foreach ($clavesNomina as $clave) {
            if (!ConfiguracionHotelRegistry::hasKey($clave)) {
                $clavesFaltantes[] = $clave;
            }
        }

        if ($clavesFaltantes === []) {
            nomCoreOk('Registry de configuracion: ' . count($clavesNomina) . ' claves nomina.* registradas.');
        } else {
            nomCoreError('Registry sin claves: ' . implode(', ', $clavesFaltantes) . '.', 'Registrar las claves nomina.* en ConfiguracionHotelRegistry::$definitions.');
        }
    } else {
        nomCoreError('No se pudo cargar la clase ConfiguracionHotelRegistry.');
    }
} else {
    nomCoreError('No se encontro ConfiguracionHotelRegistry.php.');
}

/* ---------------------------------------------------------------------
 * 6) Navegacion y sidebar.
 * ------------------------------------------------------------------- */
$navPath = $srcRoot . '/config/navegacion.php';
$navCode = is_file($navPath) ? (string) file_get_contents($navPath) : '';
if ($navCode !== '' && strpos($navCode, "'nomina_avanzada'") !== false) {
    nomCoreOk('Catalogo de navegacion incluye la pantalla de nomina.');
} else {
    nomCoreWarning('La pantalla de nomina no esta en config/navegacion.php (buscador/favoritos no la veran).');
}

$sidebarPath = $srcRoot . '/app/views/layout/sidebar.php';
$sidebarCode = is_file($sidebarPath) ? (string) file_get_contents($sidebarPath) : '';
if ($sidebarCode !== '') {
    if (strpos($sidebarCode, '$mostrarNomina') !== false && strpos($sidebarCode, "menuModuloActivo('nomina_avanzada')") !== false) {
        nomCoreOk('Sidebar: flag $mostrarNomina gateado por el bloque nomina_avanzada.');
    } else {
        nomCoreError('Sidebar sin flag de nomina gateado por modulo.');
    }

    if (strpos($sidebarCode, '$mostrarNomina || ') !== false || strpos($sidebarCode, '|| $mostrarNomina') !== false) {
        nomCoreOk('Sidebar: nomina participa en el flag del grupo ADMINISTRACION.');
    } else {
        nomCoreWarning('Sidebar: nomina no participa en ningun flag de grupo; el titulo de seccion podria no mostrarse.');
    }
}

/* ---------------------------------------------------------------------
 * 7) Checks dinamicos contra la base de datos (opcionales).
 * ------------------------------------------------------------------- */
$envCandidates = [
    $projectRoot . '/.env',
    '/var/www/.env',
];

$env = [];
foreach ($envCandidates as $envPath) {
    if (!is_file($envPath)) {
        continue;
    }
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
        $linea = trim($linea);
        if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $linea, 2);
        $env[trim($k)] = trim(trim($v), "\"'");
    }
    break;
}

$dbHost = $env['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$dbName = $env['DB_NAME'] ?? getenv('DB_NAME') ?: 'medisoft_hoteles';
$dbUser = $env['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$dbPass = $env['DB_PASS'] ?? getenv('DB_PASS') ?: '';

$pdo = null;
try {
    // En host local el contenedor 'db' no resuelve: intentar 127.0.0.1 como fallback.
    foreach (array_unique([$dbHost, '127.0.0.1']) as $hostIntento) {
        try {
            $pdo = new PDO(
                'mysql:host=' . $hostIntento . ';dbname=' . $dbName . ';charset=utf8mb4',
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 3]
            );
            break;
        } catch (Throwable $e) {
            $pdo = null;
        }
    }
} catch (Throwable $e) {
    $pdo = null;
}

if (!$pdo instanceof PDO) {
    nomCoreWarning(
        'Sin conexion a BD (' . $dbHost . '/' . $dbName . '): checks dinamicos omitidos.',
        'Ejecutar este preflight dentro del contenedor o con la BD local levantada para validar catalogo, roles y tablas.'
    );
} else {
    nomCoreOk('Conexion a BD establecida (' . $dbName . ').');

    // 7a) Bloque en catalogo modulos.
    try {
        $st = $pdo->prepare("SELECT es_core, precio_mensual, activo_global, ruta_base FROM modulos WHERE clave = 'nomina_avanzada'");
        $st->execute();
        $modulo = $st->fetch();

        if ($modulo) {
            nomCoreOk('Bloque nomina_avanzada registrado en modulos (precio $' . number_format((float) $modulo['precio_mensual'], 2) . ', ruta ' . $modulo['ruta_base'] . ').');
            if ((int) $modulo['es_core'] === 1) {
                nomCoreError('nomina_avanzada esta marcado es_core = 1; debe ser opcional (es_core = 0).');
            }
        } else {
            nomCoreError('El bloque nomina_avanzada NO esta en el catalogo modulos.', 'Aplicar la migracion 20260704_001 antes de desplegar el codigo.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudo verificar el catalogo modulos: ' . $e->getMessage());
    }

    // 7b) Preset del plan premium.
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total
             FROM plan_modulos pm
             INNER JOIN planes p ON p.id = pm.plan_id AND p.clave = 'premium'
             INNER JOIN modulos m ON m.id = pm.modulo_id AND m.clave = 'nomina_avanzada'
             WHERE pm.incluido = 1"
        );
        $fila = $st->fetch();
        if ((int) ($fila['total'] ?? 0) > 0) {
            nomCoreOk('El plan premium incluye nomina_avanzada.');
        } else {
            nomCoreWarning('El plan premium no incluye nomina_avanzada.', 'Revisar el preset de plan_modulos de la migracion 20260704_001.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudo verificar plan_modulos: ' . $e->getMessage());
    }

    // 7c) Migracion registrada.
    try {
        $st = $pdo->prepare("SELECT estado FROM migrations WHERE nombre = ?");
        $st->execute(['20260704_001_nomina_core_bloque_permisos.sql']);
        $fila = $st->fetch();
        if ($fila && $fila['estado'] === 'ejecutada') {
            nomCoreOk('Migracion 20260704_001 registrada como ejecutada.');
        } else {
            nomCoreError('Migracion 20260704_001 sin registrar en la tabla migrations.', 'Aplicarla con mysql CLI antes del deploy del codigo.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudo verificar la tabla migrations: ' . $e->getMessage());
    }

    // 7d) Roles base con permisos nomina.* (hoteles existentes).
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total
             FROM roles
             WHERE clave = 'gerente' AND es_sistema = 1
               AND (permisos_json IS NULL OR (
                     NOT JSON_CONTAINS(permisos_json, '\"nomina.all\"')
                 AND NOT JSON_CONTAINS(permisos_json, '\"*\"')))"
        );
        $sinPermiso = (int) ($st->fetch()['total'] ?? 0);
        if ($sinPermiso === 0) {
            nomCoreOk('Todos los roles gerente (es_sistema) tienen nomina.all o comodin.');
        } else {
            nomCoreError($sinPermiso . ' rol(es) gerente sin nomina.all.', 'Re-ejecutar la seccion de permisos de la migracion 20260704_001.');
        }

        $st = $pdo->query(
            "SELECT COUNT(*) AS total
             FROM roles
             WHERE clave = 'administrador' AND es_sistema = 1
               AND (permisos_json IS NULL OR (
                     NOT JSON_CONTAINS(permisos_json, '\"nomina.view\"')
                 AND NOT JSON_CONTAINS(permisos_json, '\"nomina.all\"')
                 AND NOT JSON_CONTAINS(permisos_json, '\"*\"')))"
        );
        $sinPermiso = (int) ($st->fetch()['total'] ?? 0);
        if ($sinPermiso === 0) {
            nomCoreOk('Todos los roles administrador (es_sistema) tienen nomina.view.');
        } else {
            nomCoreError($sinPermiso . ' rol(es) administrador sin nomina.view.', 'Re-ejecutar la seccion de permisos de la migracion 20260704_001.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudieron verificar los roles: ' . $e->getMessage());
    }

    // 7e) Usuarios sin role_id: can() cae a can_legacy, que NUNCA conoce nomina.*.
    try {
        $st = $pdo->query("SELECT COUNT(*) AS total FROM hotel_usuarios WHERE activo = 1 AND role_id IS NULL");
        $sinRol = (int) ($st->fetch()['total'] ?? 0);
        if ($sinRol === 0) {
            nomCoreOk('Todos los hotel_usuarios activos tienen role_id (permisos configurables).');
        } else {
            nomCoreWarning(
                $sinRol . ' hotel_usuario(s) activos sin role_id: seran DENEGADOS en todo permiso nomina.*.',
                'Backfillear role_id (patron de 20260628_001) antes de dar acceso a nomina a esos usuarios.'
            );
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudo verificar hotel_usuarios.role_id: ' . $e->getMessage());
    }

    // 7f) No-regresion: subsistema laboral existente intacto.
    $tablasLaborales = [
        'trabajadores', 'trabajador_pagos', 'trabajador_anticipos', 'trabajador_prestamos',
        'trabajador_asistencias', 'trabajador_documentos', 'trabajador_pagos_caja',
        'trabajador_nomina_periodos', 'trabajador_nomina_periodo_detalles', 'trabajador_nomina_periodo_eventos',
    ];

    try {
        $marcadores = implode(',', array_fill(0, count($tablasLaborales), '?'));
        $st = $pdo->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($marcadores)"
        );
        $st->execute($tablasLaborales);
        $presentes = array_column($st->fetchAll(), 'TABLE_NAME');
        $faltantes = array_diff($tablasLaborales, $presentes);

        if ($faltantes === []) {
            nomCoreOk('Las 10 tablas del subsistema laboral existente siguen presentes.');
        } else {
            nomCoreWarning('Tablas laborales ausentes en esta BD: ' . implode(', ', $faltantes) . '.', 'En un entorno productivo esto seria regresion; en uno limpio, aplicar las migraciones de personal.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('No se pudo verificar el subsistema laboral: ' . $e->getMessage());
    }
}

/* ---------------------------------------------------------------------
 * 8) Modo de errores de BD para el futuro motor de calculo.
 * ------------------------------------------------------------------- */
$strict = $env['DB_STRICT_ERRORS'] ?? getenv('DB_STRICT_ERRORS');
if (filter_var((string) $strict, FILTER_VALIDATE_BOOLEAN)) {
    nomCoreOk('DB_STRICT_ERRORS activo: los errores SQL no seran silenciosos.');
} else {
    nomCoreWarning(
        'DB_STRICT_ERRORS no esta activo: Database::query() traga errores y devuelve false.',
        'El motor de calculo de nomina (Fase 3+) debe usar PDO en modo estricto o exigir DB_STRICT_ERRORS=true; un SELECT roto NO debe producir nominas en ceros.'
    );
}

/* ---------------------------------------------------------------------
 * Resumen.
 * ------------------------------------------------------------------- */
echo "\n== Resumen ==\n";
echo 'OK: ' . $ok . ' | WARNING: ' . $warnings . ' | ERROR: ' . $errors . "\n";

if ($recommendations !== []) {
    echo "\nRecomendaciones:\n";
    foreach (array_unique($recommendations) as $recomendacion) {
        echo ' - ' . $recomendacion . "\n";
    }
}

exit($errors > 0 ? 1 : 0);
