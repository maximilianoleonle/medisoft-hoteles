<?php
/**
 * Guion de datos DEMO para Nomina Core.
 *
 * Siembra un negocio de ejemplo listo para recorrer el modulo en vivo:
 * configuracion, catalogos, empleados con puesto/grupo/salario e incidencias
 * del periodo actual. NO cierra periodos (eso se muestra en vivo).
 *
 * Idempotente: re-ejecutar no duplica (los empleados llevan prefijo [DEMO]).
 * Usa los SERVICIOS reales del modulo, no SQL suelto.
 *
 * Uso (dentro del contenedor):
 *   php /var/www/html/tools/saas/seed_nomina_demo.php [slug_hotel]
 * Por defecto usa el hotel 'hotel-demo-saas'. Para limpiar:
 *   php /var/www/html/tools/saas/seed_nomina_demo.php [slug] --limpiar
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Solo CLI.\n";
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');

require_once CORE_PATH . '/Database.php';
require_once APP_PATH . '/models/ConfiguracionHotelRegistry.php';
require_once APP_PATH . '/services/NominaCatalogoService.php';
require_once APP_PATH . '/services/NominaSalarioService.php';
require_once APP_PATH . '/services/NominaIncidenciaService.php';

$slug = $argv[1] ?? 'hotel-demo-saas';
$limpiar = in_array('--limpiar', $argv, true);

$db = Database::getInstance();
$pdo = $db->getConnection();

$st = $pdo->prepare("SELECT id, nombre FROM hoteles WHERE slug = ? AND activo = 1");
$st->execute([$slug]);
$hotel = $st->fetch();
if (!$hotel) {
    echo "ERROR: no existe un hotel activo con slug '{$slug}'.\n";
    echo "Hoteles activos disponibles:\n";
    foreach ($pdo->query("SELECT slug, nombre FROM hoteles WHERE activo = 1")->fetchAll() as $h) {
        echo "  - {$h['slug']} ({$h['nombre']})\n";
    }
    exit(1);
}
$hotelId = (int) $hotel['id'];

echo "== Guion DEMO Nomina Core -> {$hotel['nombre']} (id {$hotelId}) ==\n\n";

/* --------------------------------------------------------------------- */
/* Limpieza opcional                                                      */
/* --------------------------------------------------------------------- */
if ($limpiar) {
    echo "Limpiando datos DEMO...\n";
    // Orden por FKs RESTRICT: incidencias, periodos v2 y sus hijos, salarios, trabajadores demo.
    $pdo->prepare("DELETE i FROM nomina_incidencias i INNER JOIN trabajadores t ON t.id = i.trabajador_id WHERE t.hotel_id = ? AND t.nombre_completo LIKE '[DEMO]%'")->execute([$hotelId]);
    $ids = $pdo->prepare("SELECT id FROM trabajadores WHERE hotel_id = ? AND nombre_completo LIKE '[DEMO]%'");
    $ids->execute([$hotelId]);
    foreach ($ids->fetchAll() as $row) {
        $tid = (int) $row['id'];
        $pdo->prepare("DELETE FROM trabajador_salarios WHERE trabajador_id = ?")->execute([$tid]);
        $pdo->prepare("DELETE FROM trabajador_pagos WHERE trabajador_id = ?")->execute([$tid]);
        $pdo->prepare("DELETE FROM trabajadores WHERE id = ?")->execute([$tid]);
    }
    echo "  Empleados [DEMO] y sus datos eliminados.\n";
    echo "  (Catalogos y configuracion se conservan; borralos a mano si quieres.)\n";
    exit(0);
}

$catalogo = new NominaCatalogoService($db);
$salarios = new NominaSalarioService($db);
$incidencias = new NominaIncidenciaService($db);

/* --------------------------------------------------------------------- */
/* 1) Configuracion del negocio                                           */
/* --------------------------------------------------------------------- */
echo "1) Configuracion de nomina...\n";
$config = [
    'negocio.giro' => ['hotel', 'string'],
    'nomina.modo' => ['hibrida', 'string'],
    'nomina.pais' => ['MX', 'string'],
    'nomina.redondeo' => ['centavos', 'string'],
    'nomina.permitir_horas_extra' => ['1', 'boolean'],
    'nomina.permitir_descuentos_manuales' => ['1', 'boolean'],
    'nomina.requiere_aprobacion_cierre' => ['1', 'boolean'],
    'nomina.permitir_reapertura' => ['0', 'boolean'],
];
foreach ($config as $clave => [$valor, $tipo]) {
    $pdo->prepare(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'nomina', 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()"
    )->execute([$hotelId, $clave, $valor, $tipo]);
}
if (function_exists('hotel_config_cache_invalidar')) {
    hotel_config_cache_invalidar($hotelId);
}
echo "   modo=hibrida, giro=hotel, MX, redondeo a centavos.\n";

/* --------------------------------------------------------------------- */
/* 2) Catalogos                                                           */
/* --------------------------------------------------------------------- */
echo "2) Catalogos internos...\n";

$idPorNombre = function (string $tipo, string $nombre) use ($catalogo, $hotelId): ?int {
    foreach ($catalogo->listar($hotelId, $tipo) as $r) {
        if ($r['nombre'] === $nombre) {
            return (int) $r['id'];
        }
    }
    return null;
};
$crearSiFalta = function (string $tipo, array $datos) use ($catalogo, $hotelId, $idPorNombre): int {
    $existente = $idPorNombre($tipo, $datos['nombre']);
    if ($existente !== null) {
        return $existente;
    }
    return $catalogo->crear($hotelId, $tipo, $datos, null);
};

$deptos = [];
foreach (['Recepcion', 'Ama de llaves', 'Mantenimiento', 'Seguridad'] as $i => $nombre) {
    $deptos[$nombre] = $crearSiFalta('departamentos', ['nombre' => $nombre, 'orden' => $i + 1]);
}

$puestos = [];
$defPuestos = [
    ['Recepcionista', 'Recepcion', 4500.00],
    ['Jefe de recepcion', 'Recepcion', 7000.00],
    ['Camarista', 'Ama de llaves', 3600.00],
    ['Mantenimiento', 'Mantenimiento', 4200.00],
    ['Velador', 'Seguridad', 3900.00],
];
foreach ($defPuestos as $i => [$nombre, $depto, $sugerido]) {
    $puestos[$nombre] = $crearSiFalta('puestos', [
        'nombre' => $nombre,
        'departamento_id' => $deptos[$depto] ?? null,
        'salario_sugerido' => $sugerido,
        'orden' => $i + 1,
    ]);
}

$contratos = [];
foreach (['Indeterminado', 'Por temporada'] as $i => $nombre) {
    $contratos[$nombre] = $crearSiFalta('tipos_contrato', ['nombre' => $nombre, 'orden' => $i + 1]);
}

$grupoQuincenal = $crearSiFalta('grupos', [
    'nombre' => 'Quincenal operativo',
    'periodicidad' => 'quincenal',
    'dia_corte' => 15,
    'dia_pago' => 16,
    'orden' => 1,
]);
$grupoSemanal = $crearSiFalta('grupos', [
    'nombre' => 'Semanal limpieza',
    'periodicidad' => 'semanal',
    'dia_corte' => 7,
    'dia_pago' => 1,
    'orden' => 2,
]);

$catalogo->sembrarConceptosBase($hotelId, null);
echo "   4 departamentos, 5 puestos, 2 contratos, 2 grupos, conceptos base.\n";

// Referencias a conceptos para las incidencias.
$conceptoPorClasif = [];
foreach ($catalogo->listar($hotelId, 'conceptos', true) as $c) {
    $conceptoPorClasif[$c['clasificacion']] = (int) $c['id'];
}

/* --------------------------------------------------------------------- */
/* 3) Empleados con puesto, grupo y salario                               */
/* --------------------------------------------------------------------- */
echo "3) Empleados DEMO...\n";

// [nombre, puesto, grupo_key, contrato, salario, esquema]
$plantilla = [
    ['Ana Torres Reyes',        'Jefe de recepcion', 'Q', 'Indeterminado', 7200.00, 'quincenal'],
    ['Carlos Mendoza Lira',     'Recepcionista',     'Q', 'Indeterminado', 4600.00, 'quincenal'],
    ['Diana Salas Cruz',        'Recepcionista',     'Q', 'Por temporada', 4500.00, 'quincenal'],
    ['Miguel Angel Rios',       'Mantenimiento',     'Q', 'Indeterminado', 4300.00, 'quincenal'],
    ['Rosa Elena Vega',         'Camarista',         'S', 'Indeterminado', 1850.00, 'semanal'],
    ['Lucia Fernandez Mora',    'Camarista',         'S', 'Por temporada', 1800.00, 'semanal'],
    ['Jorge Ramirez Soto',      'Velador',           'Q', 'Indeterminado', 4000.00, 'quincenal'],
];

$grupoDe = ['Q' => $grupoQuincenal, 'S' => $grupoSemanal];
$empleados = [];
$altaBase = date('Y-m-d', strtotime('-8 months'));

foreach ($plantilla as $idx => [$nombre, $puestoNom, $grupoKey, $contratoNom, $salario, $esquema]) {
    $nombreDemo = '[DEMO] ' . $nombre;

    $st = $pdo->prepare("SELECT id FROM trabajadores WHERE hotel_id = ? AND nombre_completo = ?");
    $st->execute([$hotelId, $nombreDemo]);
    $existente = $st->fetch();

    if ($existente) {
        $trabajadorId = (int) $existente['id'];
    } else {
        $pdo->prepare(
            "INSERT INTO trabajadores
                (hotel_id, nombre_completo, identificacion, rol_laboral, telefono, email,
                 estado, fecha_alta, salario_base, periodicidad_pago, notas, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'activo', ?, ?, ?, 'Empleado de demostracion', NOW())"
        )->execute([
            $hotelId,
            $nombreDemo,
            'DEMO' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
            $puestoNom,
            '55' . str_pad((string) rand(10000000, 99999999), 8, '0'),
            'demo' . ($idx + 1) . '@ejemplo.mx',
            $altaBase,
            number_format($salario, 2, '.', ''),
            $esquema === 'semanal' ? 'semanal' : 'quincenal',
        ]);
        $trabajadorId = (int) $pdo->lastInsertId();
    }

    // Asignaciones (idempotente: sobrescribe).
    $catalogo->asignarATrabajador($hotelId, $trabajadorId, [
        'puesto_id' => $puestos[$puestoNom] ?? null,
        'departamento_id' => null, // el servicio no exige depto; el puesto ya lo lleva
        'tipo_contrato_id' => $contratos[$contratoNom] ?? null,
        'grupo_nomina_id' => $grupoDe[$grupoKey],
    ], null);

    // Salario con vigencia (solo si no tiene historial aun).
    if ($salarios->vigente($hotelId, $trabajadorId) === null) {
        $salarios->registrarCambio($hotelId, $trabajadorId, [
            'salario' => $salario,
            'esquema' => $esquema,
            'vigente_desde' => $altaBase,
            'motivo' => 'Alta demo',
        ], null);
    }

    $empleados[$nombre] = $trabajadorId;
}
echo "   " . count($empleados) . " empleados con puesto, grupo y salario vigente.\n";

/* --------------------------------------------------------------------- */
/* 4) Incidencias del periodo actual                                      */
/* --------------------------------------------------------------------- */
echo "4) Incidencias del periodo...\n";

$hoy = date('Y-m-d');
$haceUnos = date('Y-m-d', strtotime('-3 days'));

$registrarInc = function (int $trabajadorId, string $clasif, array $extra) use ($incidencias, $hotelId, $conceptoPorClasif, $pdo) {
    $conceptoId = $conceptoPorClasif[$clasif] ?? null;
    if (!$conceptoId) {
        return;
    }
    // Evitar duplicar por corrida: misma persona, concepto y fecha.
    $st = $pdo->prepare("SELECT COUNT(*) AS n FROM nomina_incidencias WHERE hotel_id = ? AND trabajador_id = ? AND concepto_id = ? AND fecha = ?");
    $st->execute([$hotelId, $trabajadorId, $conceptoId, $extra['fecha']]);
    if ((int) $st->fetch()['n'] > 0) {
        return;
    }
    try {
        $incidencias->registrar($hotelId, array_merge(['trabajador_id' => $trabajadorId, 'concepto_id' => $conceptoId], $extra), null);
    } catch (Throwable $e) {
        echo "   (aviso: incidencia omitida - " . $e->getMessage() . ")\n";
    }
};

if (isset($empleados['Ana Torres Reyes'])) {
    $registrarInc($empleados['Ana Torres Reyes'], 'bono', ['fecha' => $haceUnos, 'monto' => 800.00, 'descripcion' => 'Bono por metas de ocupacion']);
}
if (isset($empleados['Carlos Mendoza Lira'])) {
    $registrarInc($empleados['Carlos Mendoza Lira'], 'horas_extra', ['fecha' => $haceUnos, 'cantidad' => 6, 'monto' => 60.00, 'descripcion' => '6 horas extra fin de semana']);
}
if (isset($empleados['Diana Salas Cruz'])) {
    $registrarInc($empleados['Diana Salas Cruz'], 'comision', ['fecha' => $hoy, 'monto' => 350.00, 'descripcion' => 'Comision por upsells']);
}
if (isset($empleados['Miguel Angel Rios'])) {
    $registrarInc($empleados['Miguel Angel Rios'], 'descuento', ['fecha' => $hoy, 'monto' => 200.00, 'descripcion' => 'Descuento por herramienta extraviada']);
}
if (isset($empleados['Jorge Ramirez Soto'])) {
    $registrarInc($empleados['Jorge Ramirez Soto'], 'bono', ['fecha' => $haceUnos, 'monto' => 300.00, 'descripcion' => 'Bono de puntualidad']);
}
echo "   Incidencias de bono, horas extra, comision y descuento cargadas.\n";

/* --------------------------------------------------------------------- */
/* Resumen                                                                */
/* --------------------------------------------------------------------- */
echo "\n== Listo. Para el recorrido en vivo: ==\n";
echo "  1. Entra al hotel '{$slug}' y activa el bloque 'Nomina avanzada' si no lo esta.\n";
echo "  2. Menu Administracion -> Nomina.\n";
echo "  3. Empleados: veras 7 empleados [DEMO] con puesto, grupo y salario.\n";
echo "  4. Incidencias: bonos, horas extra, comision y descuento del periodo.\n";
echo "  5. Periodos -> elige 'Quincenal operativo' -> Previsualizar -> Cerrar -> Aprobar -> Emitir recibos.\n";
echo "  6. Tambien hay grupo 'Semanal limpieza' con 2 camaristas.\n";
echo "\nPara limpiar todo: php tools/saas/seed_nomina_demo.php {$slug} --limpiar\n";
exit(0);
