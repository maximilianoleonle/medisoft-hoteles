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
 * 7g) Fase 2: catalogos internos e historial salarial.
 * ------------------------------------------------------------------- */
if ($pdo instanceof PDO) {
    $tablasFase2 = [
        'nomina_departamentos', 'nomina_puestos', 'nomina_tipos_contrato',
        'nomina_grupos', 'nomina_conceptos', 'trabajador_salarios',
    ];

    try {
        $marcadores = implode(',', array_fill(0, count($tablasFase2), '?'));
        $st = $pdo->prepare(
            "SELECT TABLE_NAME FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($marcadores)"
        );
        $st->execute($tablasFase2);
        $presentes = array_column($st->fetchAll(), 'TABLE_NAME');
        $faltantes = array_diff($tablasFase2, $presentes);

        if ($faltantes === []) {
            nomCoreOk('Fase 2: las 6 tablas de catalogos e historial salarial existen.');
        } else {
            nomCoreError('Fase 2: faltan tablas: ' . implode(', ', $faltantes) . '.', 'Aplicar la migracion 20260704_002.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 2: no se pudieron verificar tablas: ' . $e->getMessage());
    }

    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajadores'
               AND COLUMN_NAME IN ('puesto_id','departamento_id','tipo_contrato_id','grupo_nomina_id')"
        );
        $cols = (int) ($st->fetch()['total'] ?? 0);
        if ($cols === 4) {
            nomCoreOk('Fase 2: trabajadores tiene las 4 columnas de asignacion (NULL-ables).');
        } else {
            nomCoreError('Fase 2: trabajadores tiene ' . $cols . '/4 columnas de asignacion.', 'Aplicar la migracion 20260704_002.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 2: no se pudieron verificar columnas de trabajadores: ' . $e->getMessage());
    }

    // Backfill: todo trabajador con salario_base > 0 debe tener historial.
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM trabajadores t
             WHERE t.salario_base IS NOT NULL AND t.salario_base > 0
               AND NOT EXISTS (SELECT 1 FROM trabajador_salarios ts WHERE ts.trabajador_id = t.id)"
        );
        $sinHistorial = (int) ($st->fetch()['total'] ?? 0);
        if ($sinHistorial === 0) {
            nomCoreOk('Fase 2: backfill salarial completo (0 trabajadores con salario sin historial).');
        } else {
            nomCoreError('Fase 2: ' . $sinHistorial . ' trabajador(es) con salario_base sin vigencia en trabajador_salarios.', 'Re-ejecutar el backfill de 20260704_002.');
        }

        // Consistencia: maximo UNA vigencia abierta por trabajador.
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM (
                SELECT trabajador_id FROM trabajador_salarios
                WHERE vigente_hasta IS NULL
                GROUP BY trabajador_id HAVING COUNT(*) > 1
             ) x"
        );
        $duplicadas = (int) ($st->fetch()['total'] ?? 0);
        if ($duplicadas === 0) {
            nomCoreOk('Fase 2: ninguna doble vigencia salarial abierta.');
        } else {
            nomCoreError('Fase 2: ' . $duplicadas . ' trabajador(es) con MAS de una vigencia salarial abierta.', 'Cerrar manualmente las vigencias duplicadas antes de calcular nomina.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 2: no se pudo verificar el historial salarial: ' . $e->getMessage());
    }

    // Aislamiento: asignaciones de trabajadores deben apuntar a catalogos del MISMO hotel.
    try {
        $st = $pdo->query(
            "SELECT
                (SELECT COUNT(*) FROM trabajadores t INNER JOIN nomina_puestos p ON p.id = t.puesto_id AND p.hotel_id != t.hotel_id)
              + (SELECT COUNT(*) FROM trabajadores t INNER JOIN nomina_departamentos d ON d.id = t.departamento_id AND d.hotel_id != t.hotel_id)
              + (SELECT COUNT(*) FROM trabajadores t INNER JOIN nomina_tipos_contrato c ON c.id = t.tipo_contrato_id AND c.hotel_id != t.hotel_id)
              + (SELECT COUNT(*) FROM trabajadores t INNER JOIN nomina_grupos g ON g.id = t.grupo_nomina_id AND g.hotel_id != t.hotel_id)
              AS cruzadas"
        );
        $cruzadas = (int) ($st->fetch()['cruzadas'] ?? 0);
        if ($cruzadas === 0) {
            nomCoreOk('Fase 2: cero asignaciones cruzadas entre hoteles (aislamiento tenant).');
        } else {
            nomCoreError('Fase 2: ' . $cruzadas . ' asignacion(es) apuntan a catalogos de OTRO hotel.', 'Fuga de tenant: corregir de inmediato y revisar NominaCatalogoService::asignarATrabajador.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 2: no se pudo verificar aislamiento de asignaciones: ' . $e->getMessage());
    }
}

/* ---------------------------------------------------------------------
 * 7h) Fase 3: motor v2 (incidencias, lineas, periodos por grupo).
 * ------------------------------------------------------------------- */
if ($pdo instanceof PDO) {
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('nomina_incidencias', 'nomina_periodo_conceptos')"
        );
        $tablas = (int) ($st->fetch()['total'] ?? 0);
        if ($tablas === 2) {
            nomCoreOk('Fase 3: tablas nomina_incidencias y nomina_periodo_conceptos existen.');
        } else {
            nomCoreError('Fase 3: faltan tablas del motor v2 (' . $tablas . '/2).', 'Aplicar la migracion 20260704_003.');
        }

        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
               AND COLUMN_NAME IN ('grupo_nomina_id', 'motor', 'reglas_snapshot_json')"
        );
        $cols = (int) ($st->fetch()['total'] ?? 0);
        if ($cols === 3) {
            nomCoreOk('Fase 3: trabajador_nomina_periodos extendida (grupo, motor, reglas_snapshot).');
        } else {
            nomCoreError('Fase 3: trabajador_nomina_periodos tiene ' . $cols . '/3 columnas nuevas.', 'Aplicar la migracion 20260704_003.');
        }

        $st = $pdo->query(
            "SELECT COUNT(DISTINCT INDEX_NAME) AS total FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodos'
               AND INDEX_NAME = 'uk_trabajador_nomina_periodo_grupo_rango_vigente'"
        );
        if ((int) ($st->fetch()['total'] ?? 0) === 1) {
            nomCoreOk('Fase 3: unicidad por grupo activa solo para periodos vigentes.');
        } else {
            nomCoreError('Fase 3: falta la UNIQUE por grupo (vigentes) en periodos.', 'Aplicar la migracion 20260704_003.');
        }

        // Consistencia: todo periodo anulado debe liberar su rango (anulacion_uk = id).
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM trabajador_nomina_periodos
             WHERE estado = 'anulado' AND anulacion_uk = 0"
        );
        $sinLiberar = (int) ($st->fetch()['total'] ?? 0);
        if ($sinLiberar === 0) {
            nomCoreOk('Fase 3: todos los periodos anulados liberaron su rango (anulacion_uk).');
        } else {
            nomCoreError('Fase 3: ' . $sinLiberar . ' periodo(s) anulados con anulacion_uk = 0.', 'Ejecutar: UPDATE trabajador_nomina_periodos SET anulacion_uk = id WHERE estado = \'anulado\' AND anulacion_uk = 0;');
        }

        // Integridad: los creditos NOMV2 activos SOLO pueden pertenecer a
        // periodos APROBADOS (se emiten al aprobar; anular/reabrir los anula).
        $st = $pdo->query(
            "SELECT COUNT(*) AS total
             FROM trabajador_pagos tp
             INNER JOIN trabajador_nomina_periodos p
                ON p.id = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(tp.referencia, '-', 2), '-', -1) AS UNSIGNED)
             WHERE tp.referencia LIKE 'NOMV2-%' AND tp.estado = 'activo' AND p.estado != 'aprobado'"
        );
        $huerfanos = (int) ($st->fetch()['total'] ?? 0);
        if ($huerfanos === 0) {
            nomCoreOk('Fase 3: creditos NOMV2 activos solo en periodos aprobados (invariante de pago).');
        } else {
            nomCoreError('Fase 3: ' . $huerfanos . ' credito(s) NOMV2 activos de periodos NO aprobados.', 'Anular esos creditos de trabajador_pagos: permiten pagar nomina sin aprobacion.');
        }

        // Integridad: todo periodo v2 no anulado debe tener lineas congeladas.
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM trabajador_nomina_periodos p
             WHERE p.motor = 'v2' AND p.estado != 'anulado'
               AND NOT EXISTS (SELECT 1 FROM nomina_periodo_conceptos l WHERE l.periodo_id = p.id)"
        );
        $sinLineas = (int) ($st->fetch()['total'] ?? 0);
        if ($sinLineas === 0) {
            nomCoreOk('Fase 3: todos los periodos v2 vigentes tienen lineas congeladas.');
        } else {
            nomCoreWarning('Fase 3: ' . $sinLineas . ' periodo(s) v2 sin lineas congeladas.', 'Revisar cierres interrumpidos.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 3: no se pudieron verificar estructuras del motor v2: ' . $e->getMessage());
    }

    // Fase 4: recibos internos.
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nomina_recibos'"
        );
        if ((int) ($st->fetch()['total'] ?? 0) === 1) {
            nomCoreOk('Fase 4: tabla nomina_recibos existe.');

            $st = $pdo->query(
                "SELECT COUNT(*) AS total FROM (
                    SELECT hotel_id, folio_numero FROM nomina_recibos GROUP BY hotel_id, folio_numero HAVING COUNT(*) > 1
                 ) x"
            );
            $dupFolios = (int) ($st->fetch()['total'] ?? 0);
            if ($dupFolios === 0) {
                nomCoreOk('Fase 4: folios de recibo unicos por negocio.');
            } else {
                nomCoreError('Fase 4: ' . $dupFolios . ' folio(s) duplicados en nomina_recibos.');
            }

            $st = $pdo->query(
                "SELECT COUNT(*) AS total FROM nomina_recibos r
                 INNER JOIN trabajador_nomina_periodos p ON p.id = r.periodo_id
                 WHERE r.estado = 'emitido' AND p.estado != 'aprobado'"
            );
            $recibosInvalidos = (int) ($st->fetch()['total'] ?? 0);
            if ($recibosInvalidos === 0) {
                nomCoreOk('Fase 4: cero recibos vigentes de periodos no aprobados.');
            } else {
                nomCoreError('Fase 4: ' . $recibosInvalidos . ' recibo(s) vigentes de periodos anulados/reabiertos.', 'Cancelar esos recibos: la reapertura/anulacion debe cancelarlos.');
            }
        } else {
            nomCoreError('Fase 4: falta la tabla nomina_recibos.', 'Aplicar la migracion 20260704_004.');
        }

        $st = $pdo->query(
            "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'trabajador_nomina_periodo_eventos' AND COLUMN_NAME = 'tipo'"
        );
        $tipoEnum = (string) ($st->fetch()['t'] ?? '');
        if (strpos($tipoEnum, 'reapertura') !== false) {
            nomCoreOk('Fase 4: eventos de periodo soportan reapertura.');
        } else {
            nomCoreError('Fase 4: el ENUM de eventos no incluye reapertura.', 'Aplicar la migracion 20260704_004.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 4: no se pudieron verificar recibos: ' . $e->getMessage());
    }

    // Fase 5: reglas legales versionadas.
    try {
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME IN ('nomina_reglas_legales', 'nomina_reglas_legales_eventos')"
        );
        if ((int) ($st->fetch()['total'] ?? 0) === 2) {
            nomCoreOk('Fase 5: tablas de reglas legales y su historial existen.');
        } else {
            nomCoreError('Fase 5: faltan tablas de reglas legales.', 'Aplicar la migracion 20260704_005.');
        }

        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM nomina_reglas_legales
             WHERE pais = 'MX' AND estado = 'activo'
               AND tipo_regla IN ('aguinaldo_dias_minimo', 'prima_vacacional_pct', 'vacaciones_tabla')"
        );
        if ((int) ($st->fetch()['total'] ?? 0) >= 3) {
            nomCoreOk('Fase 5: reglas estatutarias LFT sembradas.');
        } else {
            nomCoreWarning('Fase 5: faltan reglas estatutarias LFT.', 'Re-aplicar semillas de 20260704_005.');
        }

        // Ejercicio vigente sin UMA/salario minimo resolubles HOY: aviso operativo.
        $hoy = date('Y-m-d');
        $st = $pdo->prepare(
            "SELECT COUNT(*) AS total FROM nomina_reglas_legales
             WHERE pais = 'MX' AND tipo_regla = ? AND estado = 'activo'
               AND vigente_desde <= ? AND (vigente_hasta IS NULL OR vigente_hasta >= ?)"
        );
        foreach (['uma_diaria', 'salario_minimo_general'] as $tipoCritico) {
            $st->execute([$tipoCritico, $hoy, $hoy]);
            if ((int) ($st->fetch()['total'] ?? 0) > 0) {
                nomCoreOk('Fase 5: ' . $tipoCritico . ' resoluble a hoy.');
            } else {
                nomCoreWarning('Fase 5: ' . $tipoCritico . ' NO resoluble a hoy (' . $hoy . ').', 'Capturar el ejercicio vigente en /admin/saas/nomina/reglas con fuente DOF antes de activar el modo legal.');
            }
        }

        // Solape de vigencias activas del mismo tipo: error de captura.
        $st = $pdo->query(
            "SELECT COUNT(*) AS total FROM nomina_reglas_legales a
             INNER JOIN nomina_reglas_legales b
                ON b.pais = a.pais AND b.tipo_regla = a.tipo_regla AND b.id > a.id
               AND a.estado = 'activo' AND b.estado = 'activo'
               AND a.vigente_desde <= COALESCE(b.vigente_hasta, '9999-12-31')
               AND b.vigente_desde <= COALESCE(a.vigente_hasta, '9999-12-31')"
        );
        $solapes = (int) ($st->fetch()['total'] ?? 0);
        if ($solapes === 0) {
            nomCoreOk('Fase 5: cero solapes de vigencia entre reglas activas del mismo tipo.');
        } else {
            nomCoreError('Fase 5: ' . $solapes . ' solape(s) de vigencia en reglas activas.', 'Corregir vigencias en /admin/saas/nomina/reglas (la resolucion seria ambigua).');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 5: no se pudieron verificar reglas legales: ' . $e->getMessage());
    }

    // Fase 6: motor legal (origen fiscal en lineas congeladas).
    try {
        $st = $pdo->query(
            "SELECT COLUMN_TYPE AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'nomina_periodo_conceptos' AND COLUMN_NAME = 'origen'"
        );
        $origenEnum = (string) ($st->fetch()['t'] ?? '');
        if (strpos($origenEnum, 'fiscal') !== false) {
            nomCoreOk('Fase 6: lineas congeladas soportan origen fiscal (ISR/IMSS).');
        } else {
            nomCoreError('Fase 6: el ENUM origen no incluye fiscal.', 'Aplicar la migracion 20260704_006.');
        }

        // Negocios en modo legal sin tabla ISR resoluble: aviso critico.
        $st = $pdo->query(
            "SELECT COUNT(DISTINCT hc.hotel_id) AS total
             FROM hotel_configuracion hc
             WHERE hc.clave = 'nomina.modo' AND hc.valor = 'legal' AND hc.activo = 1
               AND NOT EXISTS (
                   SELECT 1 FROM nomina_reglas_legales r
                   WHERE r.pais = 'MX' AND r.estado = 'activo'
                     AND r.tipo_regla LIKE 'isr_tabla_%'
                     AND r.vigente_desde <= CURDATE()
                     AND (r.vigente_hasta IS NULL OR r.vigente_hasta >= CURDATE())
               )"
        );
        $legalSinIsr = (int) ($st->fetch()['total'] ?? 0);
        if ($legalSinIsr === 0) {
            nomCoreOk('Fase 6: ningun negocio en modo legal sin tabla ISR resoluble.');
        } else {
            nomCoreWarning('Fase 6: ' . $legalSinIsr . ' negocio(s) en modo legal SIN tabla ISR vigente.', 'Sus cierres legales quedaran bloqueados hasta capturar las tablas del ejercicio en /admin/saas/nomina/reglas.');
        }
    } catch (Throwable $e) {
        nomCoreWarning('Fase 6: no se pudo verificar el motor legal: ' . $e->getMessage());
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
