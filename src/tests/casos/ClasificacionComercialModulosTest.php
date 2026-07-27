<?php

require_once __DIR__ . '/../bootstrap.php';

echo "ClasificacionComercialModulosTest\n";

// ─────────────────────────────────────────────────────────────────────────────
// 1) Asserts de código: blindajes presentes en modelo, controllers y candados.
// ─────────────────────────────────────────────────────────────────────────────
$root = dirname(__DIR__, 2);
$modeloSrc = (string) file_get_contents($root . '/app/models/Modulo.php');
$planSrc = (string) file_get_contents($root . '/app/models/Plan.php');
$configCtrl = (string) file_get_contents($root . '/app/controllers/ConfiguracionController.php');
$inventarioCtrl = (string) file_get_contents($root . '/app/controllers/InventarioController.php');
$reservacionCtrl = (string) file_get_contents($root . '/app/controllers/ReservacionController.php');
$apiCtrl = (string) file_get_contents($root . '/app/controllers/ApiController.php');

t_ok(strpos($modeloSrc, "AND m.tipo_comercial = 'opcional'") !== false,
    'cobro mensual solo suma modulos opcionales (internos/base excluidos)');
t_ok(strpos($modeloSrc, "AND tipo_comercial = 'opcional'") !== false,
    'precios de catalogo solo editables en opcionales');
t_ok(strpos($modeloSrc, 'setActivoGlobalPorClave') !== false
    && strpos($modeloSrc, 'ms_cache_forget') !== false,
    'bloqueo global invalida cache de modulos por hotel');
t_ok(strpos($planSrc, 'TIPO_INTERNO') !== false,
    'presets de plan no aplican modulos internos');
t_ok(strpos($configCtrl, 'isSaasAdmin()') !== false,
    'Configuracion exige equipo Medisoft en servidor (before)');
t_ok(strpos($inventarioCtrl, "require_hotel_module('compras')") === false,
    'Inventario no depende de Compras (funciona solo)');
t_ok(strpos($reservacionCtrl, "hotel_has_module('camarista'") === false
    && strpos($reservacionCtrl, "require_hotel_module('camarista')") === false,
    'check-out manda a limpieza sin consultar App Camarista');
t_ok(strpos($apiCtrl, 'sync_temporarily_disabled') !== false
    && strpos($apiCtrl, '423') !== false,
    '/api/sync conserva el candado HTTP 423 sync_temporarily_disabled');

// Los 3 origenes de datos de graficas filtran por hotel (tenancy). Ocupacion
// y procedencia son wrappers que DELEGAN la query: el filtro por hotel vive
// en el metodo delegado (verificado en codigo: obtenerOcupacionDiaria resuelve
// hotelIdActual() y obtenerProcedenciaPorEstado filtra r.hotel_id = ?).
$reporteSrc = (string) file_get_contents($root . '/app/models/Reporte.php');
$grafConQuery = [
    'obtenerDatosGraficaIngresosGastos' => 'obtenerDatosGraficaIngresosGastos',
    'obtenerDatosGraficaOcupacion' => 'obtenerOcupacionDiaria',
    'obtenerDatosGraficaProcedencia' => 'obtenerProcedenciaPorEstado',
];
foreach ($grafConQuery as $metodoGrafica => $metodoConQuery) {
    $pos = strpos($reporteSrc, 'function ' . $metodoConQuery . '(');
    $fragmento = $pos !== false ? substr($reporteSrc, $pos, 2600) : '';
    t_ok($pos !== false
            && (stripos($fragmento, 'hotel_id') !== false || stripos($fragmento, 'hotelIdActual') !== false),
        "grafica {$metodoGrafica} filtra por hotel (via {$metodoConQuery})");
    if ($metodoGrafica !== $metodoConQuery) {
        $posWrapper = strpos($reporteSrc, 'function ' . $metodoGrafica . '(');
        $fragWrapper = $posWrapper !== false ? substr($reporteSrc, $posWrapper, 700) : '';
        t_ok($fragWrapper !== '' && strpos($fragWrapper, $metodoConQuery . '(') !== false,
            "grafica {$metodoGrafica} delega en {$metodoConQuery}");
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 2) Logica con catalogo sintetico en BD de pruebas.
// ─────────────────────────────────────────────────────────────────────────────
t_reset_db();
$base = t_seed_base('clasif-mod');
$hotelId = $base['hotel_id'];
$db = Database::getInstance();

function cmt_sembrar_modulo($db, $clave, $tipo, $esCore, $activoGlobal, $precio, $motivo = null, $orden = 0)
{
    $db->query(
        "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, motivo_bloqueo, orden, created_at)
         VALUES (?, ?, 'test', ?, ?, ?, ?, ?, ?, NOW())",
        [$clave, 'Mod ' . $clave, $esCore, $tipo, $precio, $activoGlobal, $motivo, $orden]
    );
    return (int) $db->lastInsertId();
}

function cmt_activar_para_hotel($db, $hotelId, $moduloId, $override = null)
{
    $db->query(
        "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, precio_override, enabled_at, created_at)
         VALUES (?, ?, 1, 'manual', ?, NOW(), NOW())",
        [$hotelId, $moduloId, $override]
    );
}

// Catalogo sintetico con claves reales (la clasificacion REAL de la BD viva la
// verifica tools/saas/verificar_clasificacion_comercial.php).
$idDashboard = cmt_sembrar_modulo($db, 'dashboard', 'base', 1, 1, 0.00, null, 10);
$idMantenimiento = cmt_sembrar_modulo($db, 'mantenimiento', 'base', 1, 1, 0.00, null, 20);
$idRoles = cmt_sembrar_modulo($db, 'roles_avanzados', 'base', 1, 1, 0.00, null, 30);
// 2026-07-25: notificaciones y pwa dejaron de ser opcionales de $149.
$idNotificaciones = cmt_sembrar_modulo($db, 'notificaciones', 'base', 1, 1, 0.00, null, 32);
$idPwa = cmt_sembrar_modulo($db, 'pwa', 'base', 1, 1, 0.00, null, 34);
$idInventario = cmt_sembrar_modulo($db, 'inventario', 'opcional', 0, 1, 299.00, null, 40);
$idLlaves = cmt_sembrar_modulo($db, 'llaves_remotos', 'opcional', 0, 0, 79.00, 'Funcionalidad aun no lista para venta', 50);
$idCxc = cmt_sembrar_modulo($db, 'cuentas_cobrar', 'opcional', 0, 0, 199.00, 'Funcion comercial retirada; no es credito empresarial', 55);
$idCamarista = cmt_sembrar_modulo($db, 'camarista', 'opcional', 0, 0, 99.00, 'Pendiente de auditoria antes de venta', 58);
$idAnticipos = cmt_sembrar_modulo($db, 'anticipos', 'interno', 1, 1, 0.00, null, 60);
$idReportes = cmt_sembrar_modulo($db, 'reportes', 'interno', 0, 1, 0.00, null, 70);
$idTareas = cmt_sembrar_modulo($db, 'tareas', 'interno', 0, 0, 0.00, 'Infraestructura interna', 80);
$idRepProcedencia = cmt_sembrar_modulo($db, 'reporte_procedencia', 'opcional', 0, 1, 150.00, null, 90);
$idRepOcupacion = cmt_sembrar_modulo($db, 'reporte_ocupacion', 'opcional', 0, 1, 120.00, null, 91);

// Contrataciones del hotel: inventario (override 250), llaves (contratada ANTES
// del bloqueo global: historial), anticipos/reportes (internos historicos) y
// el reporte de procedencia. Ocupacion NO esta contratada.
cmt_activar_para_hotel($db, $hotelId, $idInventario, 250.00);
cmt_activar_para_hotel($db, $hotelId, $idLlaves);
cmt_activar_para_hotel($db, $hotelId, $idAnticipos);
cmt_activar_para_hotel($db, $hotelId, $idReportes);
cmt_activar_para_hotel($db, $hotelId, $idRepProcedencia);

// Historial: el hotel tenia 'pwa' APAGADA en hotel_modulos desde antes de que el
// bloque pasara al paquete base. es_core manda; esa fila es historia, no candado.
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, disabled_at, created_at)
     VALUES (?, ?, 0, 'manual', NOW(), NOW())",
    [$hotelId, $idPwa]
);

$moduloModel = new Modulo();

// ── Gates por modulo ──
t_ok($moduloModel->hotelTieneModulo($hotelId, 'dashboard'), 'base activo sin fila en hotel_modulos');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'mantenimiento'), 'mantenimiento (base) siempre activo');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'roles_avanzados'), 'roles y permisos (base) siempre activo');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'notificaciones'), 'notificaciones (base) activo sin fila en hotel_modulos');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'pwa'), 'pwa (base) activo aunque su fila historica siga en activo=0');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'llaves_remotos'),
    'llaves_remotos bloqueado global NO da acceso aunque el hotel lo tenga contratado');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'cuentas_cobrar'),
    'cuentas_cobrar bloqueado global NO da acceso');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'anticipos'), 'anticipos (interno es_core) sigue activo para los flujos');

// ── Gates del Centro de Reportes (helpers) ──
t_ok(hotel_report_screen_allowed('procedencia', $hotelId), 'hotel con Procedencia contratada abre procedencia');
t_ok(hotel_report_screen_allowed('ranking-estados', $hotelId), 'ranking pertenece a procedencia (mismo modulo)');
t_ok(!hotel_report_screen_allowed('ocupacion', $hotelId), 'hotel con Procedencia NO puede abrir Ocupacion');
t_ok(hotel_report_screen_allowed('mantenimiento', $hotelId), 'reportes de mantenimiento incluidos con el modulo base');
t_ok(!hotel_report_screen_allowed('limpieza', $hotelId), 'reporte de limpieza cerrado con camarista/limpieza bloqueados');
t_ok(!hotel_report_screen_allowed('pantalla-inexistente', $hotelId), 'pantalla desconocida queda cerrada (fail-closed)');
t_ok(hotel_reports_center_available($hotelId), 'centro de reportes disponible con >=1 permitido');

// ── Cobro mensual: solo opcionales activos globales contratados ──
$resumen = $moduloModel->resumenCobroMensual($hotelId, 999.00);
t_ok($resumen !== null, 'resumen de cobro calculado');
$clavesCobradas = array_column($resumen['modulos'], 'clave');
sort($clavesCobradas);
t_eq(['inventario', 'reporte_procedencia'], $clavesCobradas,
    'cobro incluye SOLO opcionales activos contratados (sin bloqueados, internos ni base)');
t_ok(!in_array('notificaciones', $clavesCobradas, true) && !in_array('pwa', $clavesCobradas, true),
    'notificaciones y pwa (paquete base) no suman al cobro mensual');
t_eq(400.00, (float) $resumen['total_modulos'], 'total usa precio_override (250) + catalogo (150)');
t_eq(1399.00, (float) $resumen['total'], 'total mensual = base + bloques');

// ── POST manipulado: bloqueados/internos no se contratan; core no se apaga ──
$ok = $moduloModel->actualizarModulosHotel($hotelId, [$idLlaves, $idTareas, $idCxc, $idCamarista], $base['usuario_id']);
t_ok($ok, 'actualizarModulosHotel procesa la seleccion');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'llaves_remotos'), 'POST manipulado no reactiva un modulo bloqueado');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'camarista'), 'POST manipulado no contrata un opcional bloqueado');
$filaTareas = $db->query("SELECT id FROM hotel_modulos WHERE hotel_id = ? AND modulo_id = ?", [$hotelId, $idTareas])->fetch();
t_ok(empty($filaTareas), 'POST manipulado no crea contratacion de un interno');
$filaLlaves = $db->query("SELECT activo FROM hotel_modulos WHERE hotel_id = ? AND modulo_id = ?", [$hotelId, $idLlaves])->fetch();
t_eq(1, (int) ($filaLlaves['activo'] ?? 0), 'la contratacion historica del bloqueado se CONSERVA congelada (no se borra)');
$filaAnticipos = $db->query("SELECT activo FROM hotel_modulos WHERE hotel_id = ? AND modulo_id = ?", [$hotelId, $idAnticipos])->fetch();
t_eq(1, (int) ($filaAnticipos['activo'] ?? 0), 'el interno anticipos no se desactiva aunque no viaje en el POST');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'dashboard'), 'core sigue activo tras seleccion vacia de opcionales');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'mantenimiento'), 'mantenimiento no puede desactivarse');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'roles_avanzados'), 'roles y permisos no puede desactivarse');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'notificaciones'), 'notificaciones no puede desactivarse por POST');
t_ok($moduloModel->hotelTieneModulo($hotelId, 'pwa'), 'pwa no puede desactivarse por POST');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'inventario'), 'opcional disponible SI se puede apagar (no venia en la seleccion)');

// El bloqueado congelado tampoco se cobra aunque su fila siga activa.
$resumen2 = $moduloModel->resumenCobroMensual($hotelId, 999.00);
t_eq([], array_column($resumen2['modulos'], 'clave'), 'sin opcionales contratados el cobro de bloques queda en cero');

// ── Reactivar inventario y bloquearlo globalmente: el gate cae y no se cobra ──
$moduloModel->actualizarModulosHotel($hotelId, [$idInventario], $base['usuario_id']);
t_ok($moduloModel->hotelTieneModulo($hotelId, 'inventario'), 'inventario reactivado para el hotel');
t_ok($moduloModel->setActivoGlobalPorClave('inventario', 0, 'Prueba de bloqueo global'), 'bloqueo global ejecutado');
t_ok(!$moduloModel->hotelTieneModulo($hotelId, 'inventario'), 'bloqueo global corta el acceso de inmediato');
$resumen3 = $moduloModel->resumenCobroMensual($hotelId, 999.00);
t_eq([], array_column($resumen3['modulos'], 'clave'), 'modulo bloqueado global no se cobra');
$moduloModel->setActivoGlobalPorClave('inventario', 1, null);

// ── Precios de catalogo: internos no editables ──
$moduloModel->actualizarPreciosCatalogo([$idAnticipos => '500.00', $idInventario => '310.00']);
$precioAnticipos = $db->query("SELECT precio_mensual FROM modulos WHERE id = ?", [$idAnticipos])->fetch();
$precioInventario = $db->query("SELECT precio_mensual FROM modulos WHERE id = ?", [$idInventario])->fetch();
t_eq(0.00, (float) $precioAnticipos['precio_mensual'], 'el precio de un interno no se puede editar (queda 0)');
t_eq(310.00, (float) $precioInventario['precio_mensual'], 'el precio de un opcional si es editable');

// ── Presets de plan: no reactivan bloqueados ni aplican internos ──
$db->query("INSERT INTO planes (clave, nombre, activo, orden, created_at) VALUES ('plan-test', 'Plan Test', 1, 99, NOW())");
$planId = (int) $db->lastInsertId();
foreach ([$idDashboard, $idInventario, $idLlaves, $idReportes] as $mid) {
    $db->query("INSERT INTO plan_modulos (plan_id, modulo_id, incluido, orden) VALUES (?, ?, 1, 0)", [$planId, $mid]);
}
$planModel = new Plan();
$idsPreset = $planModel->moduloIdsDelPlan($planId);
t_ok(in_array($idDashboard, $idsPreset, true), 'preset conserva modulos base');
t_ok(in_array($idInventario, $idsPreset, true), 'preset conserva opcionales disponibles');
t_ok(!in_array($idLlaves, $idsPreset, true), 'preset NO puede reactivar un modulo bloqueado');
t_ok(!in_array($idReportes, $idsPreset, true), 'preset NO aplica modulos internos');

// ── Segundo hotel: aislamiento por hotel_id y centro por defecto ──
$base2 = t_seed_base('clasif-mod-b');
$hotelB = $base2['hotel_id'];
t_ok(!$moduloModel->hotelTieneModulo($hotelB, 'reporte_procedencia'),
    'la contratacion de reportes de un hotel no se fuga a otro hotel');
t_ok(!hotel_report_screen_allowed('procedencia', $hotelB), 'hotel B sin Procedencia no la abre');
t_ok(hotel_reports_center_available($hotelB), 'hotel B conserva el centro por los reportes de mantenimiento (base)');
$resumenB = $moduloModel->resumenCobroMensual($hotelB, 999.00);
t_eq(0.0, (float) $resumenB['total_modulos'], 'hotel B no hereda cobros de otro hotel');

// ─────────────────────────────────────────────────────────────────────────────
// Inventario se vende SOLO: contratarlo no arrastra Compras
// ─────────────────────────────────────────────────────────────────────────────
// Pendiente 1 de la auditoria comercial (jul-26). La oferta vende Inventario
// como bloque independiente y deja fuera Compras/proveedores/cuentas por pagar.
// El catalogo NO modela dependencias entre bloques, asi que hoy nada las
// propaga — pero nada lo impedia tampoco. Esto lo fija por COMPORTAMIENTO
// (no por strpos): se contrata inventario y se comprueba que compras no quedo
// activo ni se colo al cobro. Ojo: la relacion real es la inversa (Compras si
// depende de Inventario por FK en compra_detalles.producto_id), y por eso el
// riesgo de que alguien "resuelva" la dependencia activando el par.
$idCompras = cmt_sembrar_modulo($db, 'compras', 'opcional', 0, 1, 199.00, null, 45);

$moduloModel->actualizarModulosHotel($hotelB, [$idInventario]);

t_ok($moduloModel->hotelTieneModulo($hotelB, 'inventario'),
    'contratar Inventario deja Inventario activo');
t_ok(!$moduloModel->hotelTieneModulo($hotelB, 'compras'),
    'contratar Inventario NO activa Compras');

$filaCompras = $db->query(
    "SELECT COUNT(*) AS n FROM hotel_modulos hm
     JOIN modulos m ON m.id = hm.modulo_id
     WHERE hm.hotel_id = ? AND m.clave = 'compras' AND hm.activo = 1",
    [$hotelB]
)->fetch();
t_eq(0, (int) $filaCompras['n'], 'no se crea fila activa de Compras al contratar Inventario');

$resumenSolo = $moduloModel->resumenCobroMensual($hotelB, 0.00);
$clavesCobradas = array_column($resumenSolo['modulos'], 'clave');
t_ok(in_array('inventario', $clavesCobradas, true), 'el cobro incluye Inventario');
t_ok(!in_array('compras', $clavesCobradas, true), 'el cobro NO incluye Compras');
t_eq(310.00, (float) $resumenSolo['total_modulos'],
    'contratar solo Inventario cobra solo Inventario (sin los $199 de Compras)');

// Y al reves: quien contrata ambos a proposito los conserva (los hoteles que
// ya venian con Compras contratado no deben perderla en silencio).
$moduloModel->actualizarModulosHotel($hotelB, [$idInventario, $idCompras]);
t_ok($moduloModel->hotelTieneModulo($hotelB, 'compras'),
    'contratar Compras explicitamente si la activa (hoteles heredados la conservan)');

t_fin();
