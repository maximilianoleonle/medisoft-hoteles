<?php
/**
 * Personal = registro de gente + tareas; la nomina vive en el modulo Nomina.
 *
 * Cuida el contrato completo del interruptor personal_nomina_legacy_visible():
 * que la superficie de Personal quede apagada, que el modulo Nomina SIGA PUDIENDO
 * PAGAR (su unico boton de pago postea a una ruta de este controlador) y que
 * ningun rol pierda el pago por el camino.
 *
 * Todo es logica pura + catalogos: sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_PATH . '/controllers/TrabajadorController.php';

echo "PersonalSinNominaTest\n";

// ── El interruptor ──
t_ok(function_exists('personal_nomina_legacy_visible'), 'existe el interruptor');
t_eq(false, personal_nomina_legacy_visible(), 'la nomina NO vive en Personal');
t_ok(function_exists('require_personal_nomina_legacy'), 'existe el gate de servidor');

// ── Clasificacion de acciones (decision pura del gate) ──
foreach (['index', 'ver', 'crear', 'guardar', 'editar', 'actualizar', 'bajaLogica', 'reactivar', 'reporte', 'informes'] as $accion) {
    t_eq('registro', TrabajadorController::clasificarAccionPersonal($accion), "'$accion' se queda en Personal");
}

// Lo que se fue: nomina por periodos, ledger laboral, pagos libres y recibos.
foreach ([
    'nominaPeriodos', 'nominaPreview', 'exportarNominaPreview', 'cerrarNominaPeriodo',
    'reporteNominaPeriodos', 'auditoriaNomina', 'expedienteNomina',
    'registrarConceptoLaboral', 'registrarAnticipoLaboral', 'registrarPrestamoLaboral',
    'registrarAsistenciaLaboral', 'registrarPagoCaja', 'revertirPagoCaja',
    'reciboLaboral', 'reciboLaboralPdf', 'reportePagosCaja', 'simuladorPagoCaja',
] as $accion) {
    t_eq('nomina_legacy', TrabajadorController::clasificarAccionPersonal($accion), "'$accion' queda apagada");
}

// Una accion inventada cae apagada: la lista de registro es ALLOWLIST.
t_eq('nomina_legacy', TrabajadorController::clasificarAccionPersonal('accionQueNoExiste'), 'lo desconocido se apaga (allowlist)');
t_eq('nomina_legacy', TrabajadorController::clasificarAccionPersonal(''), 'accion vacia se apaga');

// ── El motor que /nomina consume NO se puede apagar ──
// Estas rutas son a las que postea/enlaza el modulo Nomina para los periodos del
// motor v1. Si alguna cae en 'nomina_legacy', el hotel se queda sin poder pagar.
$motor = [
    'registrarPagoSnapshotNomina' => 'el UNICO boton "Registrar pago" del producto postea aqui',
    'nominaPeriodoDetalle'        => '/nomina enlaza aqui los periodos v1',
    'aprobarNominaPeriodo'        => 'aprobar un periodo v1',
    'anularNominaPeriodo'         => 'anular un periodo v1',
    'reporteNominaPagosSnapshot'  => 'conciliacion de pagos del periodo',
    'exportarNominaPagosSnapshot' => 'exportar esa conciliacion',
];
foreach ($motor as $accion => $porque) {
    t_eq('motor_nomina', TrabajadorController::clasificarAccionPersonal($accion), "'$accion' sigue viva: $porque");
}

// Ninguna accion puede estar en las dos listas a la vez.
$cruce = array_intersect(
    TrabajadorController::ACCIONES_REGISTRO_PERSONAL,
    TrabajadorController::ACCIONES_MOTOR_NOMINA
);
t_eq([], array_values($cruce), 'las dos listas no se traslapan');

// ── Catalogo de permisos ──
$permisos = require CONFIG_PATH . '/permisos.php';
$catalogo = $permisos['catalogo'] ?? [];

$claves = [];
foreach ($catalogo as $grupo) {
    foreach (array_keys($grupo['permisos'] ?? []) as $clave) {
        $claves[] = $clave;
    }
}
t_eq(count($claves), count(array_unique($claves)), 'ninguna clave se dibuja dos veces en la matriz');

$personal = $catalogo['personal'] ?? [];
t_eq('Personal', $personal['label'] ?? '', 'el area ya no se llama "Personal y nomina"');
t_eq(false, isset($personal['permisos']['personal.pagar']), 'Personal ya no ofrece la casilla de pagar');
t_ok(isset($personal['permisos']['personal.view']), 'Personal conserva su permiso de acceso');
t_ok(isset($personal['permisos']['personal.all']), 'Personal conserva su comodin (contrato de encabezado de area)');
t_eq(false, (bool) preg_match('/n[oó]mina/i', (string) ($personal['permisos']['personal.view']['label'] ?? '')), 'la etiqueta de acceso ya no habla de nomina');

// 'nomina.pagar' es la casilla grantable de ahora en adelante, y vive en el grupo
// de Nomina, cubierta por su propio comodin.
t_ok(isset($catalogo['nomina']['permisos']['nomina.pagar']), 'Nomina ofrece la casilla de pagar');
t_ok(isset($catalogo['nomina']['permisos']['nomina.all']), 'Nomina conserva su comodin');

// La clave vieja NO se borro de los presets: quien ya podia pagar sigue pudiendo.
$presets = $permisos['presets'] ?? [];
t_ok(
    in_array('personal.pagar', $presets['administrador']['permisos'] ?? [], true),
    'el preset administrador CONSERVA personal.pagar (el gate del pago lo acepta por any-of)'
);

// ── Navegacion ──
$nav = require CONFIG_PATH . '/navegacion.php';
$pantallas = is_array($nav['pantallas'] ?? null) ? $nav['pantallas'] : $nav;
$personalNav = null;
foreach ($pantallas as $pantalla) {
    if (($pantalla['ruta'] ?? '') === 'trabajadores') {
        $personalNav = $pantalla;
        break;
    }
}
t_ok(is_array($personalNav), 'Personal sigue en el catalogo de navegacion');
t_eq(false, (bool) preg_match('/nomina/i', (string) ($personalNav['buscar'] ?? '')), 'buscar "nomina" ya no cae en Personal');
t_ok((bool) preg_match('/tareas/i', (string) ($personalNav['buscar'] ?? '')), 'Personal se puede buscar por "tareas"');

t_fin();
