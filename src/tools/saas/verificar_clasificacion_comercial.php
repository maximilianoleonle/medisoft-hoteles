<?php
/**
 * Verificacion de la clasificacion comercial del catalogo (BD real del .env).
 * Correr dentro del contenedor:
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/saas/verificar_clasificacion_comercial.php
 * Exit 0 = todo PASS; exit 1 = hay FAIL.
 */

$cfg = require __DIR__ . '/../../config/database.php';
$pdo = new PDO(
    "mysql:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}",
    $cfg['username'],
    $cfg['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$fallas = 0;
function v_ok($cond, $nombre)
{
    global $fallas;
    if ($cond) {
        echo "  PASS  {$nombre}\n";
    } else {
        $fallas++;
        echo "  FAIL  {$nombre}\n";
    }
}

echo "== Verificacion clasificacion comercial ({$cfg['database']}) ==\n";

// 1) Columnas nuevas.
$cols = $pdo->query("SHOW COLUMNS FROM modulos")->fetchAll(PDO::FETCH_COLUMN);
v_ok(in_array('tipo_comercial', $cols, true), 'columna tipo_comercial existe');
v_ok(in_array('motivo_bloqueo', $cols, true), 'columna motivo_bloqueo existe');

// 2) Paquete base exacto.
// 2026-07-25: notificaciones y pwa entraron al paquete base (10 modulos).
$BASE = ['dashboard', 'habitaciones', 'reservaciones', 'huespedes', 'caja', 'usuarios', 'roles_avanzados', 'mantenimiento',
    'notificaciones', 'pwa'];
$base = $pdo->query("SELECT clave, nombre, es_core, activo_global, precio_mensual FROM modulos WHERE tipo_comercial = 'base' ORDER BY clave")->fetchAll();
$clavesBase = array_column($base, 'clave');
sort($BASE);
v_ok($clavesBase === $BASE, 'paquete base = exactamente los ' . count($BASE) . ' modulos autorizados');
$baseSano = true;
foreach ($base as $m) {
    if ((int) $m['es_core'] !== 1 || (int) $m['activo_global'] !== 1 || (float) $m['precio_mensual'] != 0.0) {
        $baseSano = false;
    }
}
v_ok($baseSano, 'todo el paquete base: es_core=1, activo_global=1, precio 0');
$roles = $pdo->query("SELECT nombre FROM modulos WHERE clave = 'roles_avanzados'")->fetch();
v_ok(($roles['nombre'] ?? '') === 'Roles y permisos', 'roles_avanzados renombrado comercialmente a "Roles y permisos"');

// 3) Internos.
$INTERNOS_ACTIVOS = ['anticipos', 'configuracion', 'reportes'];
$INTERNOS_BLOQUEADOS = ['reportes_distribucion', 'tareas'];
$internos = $pdo->query("SELECT clave, activo_global, precio_mensual, motivo_bloqueo FROM modulos WHERE tipo_comercial = 'interno' ORDER BY clave")->fetchAll();
$clavesInternos = array_column($internos, 'clave');
sort($INTERNOS_ACTIVOS);
sort($INTERNOS_BLOQUEADOS);
v_ok($clavesInternos === array_merge(['anticipos', 'configuracion'], ['reportes', 'reportes_distribucion', 'tareas']),
    'internos = anticipos, configuracion, reportes + tareas/reportes_distribucion (trazables)');
$internosSanos = true;
foreach ($internos as $m) {
    if ((float) $m['precio_mensual'] != 0.0) {
        $internosSanos = false;
    }
    $esperaActivo = in_array($m['clave'], $INTERNOS_ACTIVOS, true) ? 1 : 0;
    if ((int) $m['activo_global'] !== $esperaActivo) {
        $internosSanos = false;
    }
    if ($esperaActivo === 0 && empty($m['motivo_bloqueo'])) {
        $internosSanos = false;
    }
}
v_ok($internosSanos, 'internos con precio 0; los retirados quedan bloqueados y con motivo');
$anticiposCore = $pdo->query("SELECT es_core FROM modulos WHERE clave = 'anticipos'")->fetch();
v_ok((int) ($anticiposCore['es_core'] ?? 0) === 1, 'anticipos es_core=1 (flujos de Reservaciones/Caja jamas dependen de hotel_modulos)');

// 4) Bloqueos obligatorios con su motivo.
$motivos = [
    'llaves_remotos' => 'Funcionalidad aun no lista para venta',
    'cuentas_cobrar' => 'Funcion comercial retirada; no es credito empresarial',
    'vehiculos' => 'Pendiente de integracion como complemento interno de Huespedes o Reservaciones',
];
foreach ($motivos as $clave => $motivo) {
    $m = $pdo->prepare("SELECT activo_global, motivo_bloqueo FROM modulos WHERE clave = ?");
    $m->execute([$clave]);
    $fila = $m->fetch();
    v_ok($fila && (int) $fila['activo_global'] === 0 && $fila['motivo_bloqueo'] === $motivo,
        "{$clave} bloqueado con su motivo exacto");
}

// 5) Reportes individuales.
$REPORTES = ['reporte_ingresos_egresos', 'reporte_procedencia', 'reporte_habitaciones_rentables', 'reporte_ocupacion', 'reporte_promedio_estancia'];
$stmt = $pdo->query("SELECT clave, tipo_comercial, activo_global, precio_mensual, motivo_bloqueo FROM modulos WHERE clave LIKE 'reporte\\_%' ORDER BY clave");
$reportes = $stmt->fetchAll();
$clavesReportes = array_column($reportes, 'clave');
sort($REPORTES);
v_ok($clavesReportes === $REPORTES, 'existen exactamente los 5 reportes individuales autorizados');
$reportesSanos = true;
foreach ($reportes as $m) {
    if ($m['tipo_comercial'] !== 'opcional' || (int) $m['activo_global'] !== 0
        || (float) $m['precio_mensual'] != 0.0 || empty($m['motivo_bloqueo'])) {
        $reportesSanos = false;
    }
}
v_ok($reportesSanos, 'reportes individuales: opcionales, precio provisional 0, bloqueados con motivo');

// 5-bis) Opcionales A LA VENTA: lista exacta autorizada por el owner (2026-07-25).
//        Espeja la proteccion de $BASE: si un bloque se desbloquea o se bloquea
//        sin decision comercial, este assert lo caza. Los reporte_* van aparte
//        (bloque 5) y quedan excluidos de esta comparacion.
$OPCIONALES_VENTA = ['inventario', 'facturacion', 'compras', 'documentos',
    'reputacion', 'descuentos', 'tarifas_dinamicas', 'lealtad'];
$venta = $pdo->query(
    "SELECT clave, es_core, precio_mensual, motivo_bloqueo
     FROM modulos
     WHERE tipo_comercial = 'opcional' AND activo_global = 1 AND clave NOT LIKE 'reporte\\_%'
     ORDER BY clave"
)->fetchAll();
$clavesVenta = array_column($venta, 'clave');
sort($OPCIONALES_VENTA);
v_ok($clavesVenta === $OPCIONALES_VENTA,
    'opcionales a la venta = exactamente los ' . count($OPCIONALES_VENTA) . ' autorizados');
$ventaSana = true;
foreach ($venta as $m) {
    // Un bloque vendible necesita precio > 0 (cobrar 0 es regalarlo) y no puede
    // arrastrar motivo_bloqueo (el panel lo mostraria como "no disponible aun").
    if ((int) $m['es_core'] !== 0 || (float) $m['precio_mensual'] <= 0.0 || !empty($m['motivo_bloqueo'])) {
        $ventaSana = false;
    }
}
v_ok($ventaSana, 'todo opcional a la venta: es_core=0, precio > 0, sin motivo de bloqueo');

// 6) Ningun opcional bloqueado sin motivo; ninguno con es_core.
$sinMotivo = (int) $pdo->query("SELECT COUNT(*) FROM modulos WHERE activo_global = 0 AND (motivo_bloqueo IS NULL OR motivo_bloqueo = '')")->fetchColumn();
v_ok($sinMotivo === 0, 'todo modulo bloqueado tiene motivo');
$coreRaros = (int) $pdo->query("SELECT COUNT(*) FROM modulos WHERE es_core = 1 AND tipo_comercial = 'opcional'")->fetchColumn();
v_ok($coreRaros === 0, 'ningun opcional quedo marcado es_core');
$tiposInvalidos = (int) $pdo->query("SELECT COUNT(*) FROM modulos WHERE tipo_comercial NOT IN ('base','opcional','interno')")->fetchColumn();
v_ok($tiposInvalidos === 0, 'tipo_comercial solo usa base/opcional/interno');

// 7) mantenimiento_plus no fue recreado.
$plus = (int) $pdo->query("SELECT COUNT(*) FROM modulos WHERE clave = 'mantenimiento_plus'")->fetchColumn();
v_ok($plus === 0, 'mantenimiento_plus no se recreo (fusionado en Mantenimiento)');

// 8) Nada se elimino: las claves del catalogo previo siguen presentes.
$PREVIAS = ['dashboard', 'habitaciones', 'nomina_avanzada', 'reservaciones', 'checkin_digital', 'canales_ical',
    'camarista', 'llaves_remotos', 'reputacion', 'night_audit', 'huespedes', 'vehiculos', 'caja', 'anticipos',
    'descuentos', 'facturacion', 'cuentas_cobrar', 'inventario', 'compras', 'reportes', 'tablero_ejecutivo',
    'exportaciones', 'forecast', 'documentos', 'personal', 'modo_dueno', 'configuracion', 'usuarios',
    'tarifas_dinamicas', 'roles_avanzados', 'auditoria', 'limpieza', 'mantenimiento', 'lavanderia',
    'notificaciones', 'canal_whatsapp', 'motor_reservas', 'promociones', 'pwa', 'upsells', 'lealtad',
    'motor_idiomas', 'whatsapp', 'ia_ejecutiva', 'copiloto', 'copiloto_ia', 'copiloto_briefing'];
$actuales = $pdo->query("SELECT clave FROM modulos")->fetchAll(PDO::FETCH_COLUMN);
$faltantes = array_diff($PREVIAS, $actuales);
v_ok(empty($faltantes), 'ningun modulo del catalogo previo fue eliminado' . (empty($faltantes) ? '' : ' (faltan: ' . implode(', ', $faltantes) . ')'));

// 9) Planes.
$planes = $pdo->query("SELECT clave, activo FROM planes")->fetchAll(PDO::FETCH_KEY_PAIR);
v_ok((int) ($planes['basico'] ?? 0) === 1, 'plan Basico activo');
v_ok((int) ($planes['personalizado'] ?? 0) === 1, 'plan Personalizado activo');
v_ok((int) ($planes['pro'] ?? 1) === 0, 'plan Pro desactivado (conservado)');
v_ok((int) ($planes['premium'] ?? 1) === 0, 'plan Premium desactivado (conservado)');
$presetPro = (int) $pdo->query("SELECT COUNT(*) FROM plan_modulos pm JOIN planes p ON p.id = pm.plan_id WHERE p.clave IN ('pro','premium')")->fetchColumn();
v_ok($presetPro > 0, 'plan_modulos de Pro/Premium conservados como historial');

// 10) Preset del Basico = paquete base exacto.
$presetBasico = $pdo->query(
    "SELECT m.clave FROM plan_modulos pm
     JOIN planes p ON p.id = pm.plan_id
     JOIN modulos m ON m.id = pm.modulo_id
     WHERE p.clave = 'basico' AND pm.incluido = 1
     ORDER BY m.clave"
)->fetchAll(PDO::FETCH_COLUMN);
v_ok($presetBasico === $BASE, 'preset del Basico incluye exactamente el paquete base autorizado');
$reportesEnBasico = (int) $pdo->query(
    "SELECT COUNT(*) FROM plan_modulos pm
     JOIN planes p ON p.id = pm.plan_id
     JOIN modulos m ON m.id = pm.modulo_id
     WHERE p.clave = 'basico' AND m.clave = 'reportes' AND pm.incluido = 0"
)->fetchColumn();
v_ok($reportesEnBasico === 1, 'el modulo general reportes quedo fuera del Basico (fila conservada, incluido=0)');

echo $fallas === 0 ? "== TODO PASS ==\n" : "== {$fallas} FALLA(S) ==\n";
exit($fallas === 0 ? 0 : 1);
