<?php
/**
 * Fase RBAC con autorizacion monetaria (aprobada 2026-07-19): gates de servidor
 * en las LECTURAS y en las ESCRITURAS monetarias/combinadas de reservaciones,
 * cerrados por defecto.
 *
 * Dos capas de verificacion, ambas sin BD:
 *  1) Matriz de permisos: quien pasa el gate `reservaciones.view` (resuelto por
 *     preset) y que ese permiso NO concede por si solo acciones monetarias.
 *  2) Caracterizacion estatica: los gates estan presentes tanto en las acciones
 *     de LECTURA (reservaciones.view) como en las de ESCRITURA, cada una con su
 *     permiso (habitaciones.checkin/checkout, caja.cobros, reservaciones.create/
 *     edit, o el any-of cancelar/edit para cancelaciones y no-show).
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

echo "ReservacionGatesRbacTest\n";

// ── Infra: helper y vista 403 existen ──
t_ok(function_exists('require_permission_or_403'), 'helper require_permission_or_403 existe');
t_ok(function_exists('deny_access_403'), 'helper deny_access_403 existe');
t_ok(is_file(__DIR__ . '/../../app/views/errors/403.php'), 'vista errors/403 existe');

// ── Presets del catalogo ──
$cfg = require __DIR__ . '/../../config/permisos.php';
$presets = $cfg['presets'] ?? [];

/** Permisos efectivos de un preset. */
function t_perms_preset(array $presets, string $rol): array
{
    return $presets[$rol]['permisos'] ?? [];
}

// ── Matriz: quien pasa el gate de lectura (reservaciones.view) ──
$rolesQuePasan = ['superadmin', 'propietario', 'gerente', 'administrador', 'recepcionista', 'dueno_remoto'];
foreach ($rolesQuePasan as $rol) {
    $perms = t_perms_preset($presets, $rol);
    t_ok(permission_in_list('reservaciones.view', $perms), "preset '$rol' pasa reservaciones.view");
}

// Rol personalizado tipo "camarista/solo limpieza": NO pasa (cerrado por defecto).
$camaristaCustom = ['camarista.view', 'habitaciones.view', 'tareas.view'];
t_ok(!permission_in_list('reservaciones.view', $camaristaCustom), 'rol limpieza-only NO pasa reservaciones.view');

// Rol vacio (sin permisos): NO pasa.
t_ok(!permission_in_list('reservaciones.view', []), 'rol sin permisos NO pasa (cerrado por defecto)');

// ── Frontera monetaria: reservaciones.view NUNCA concede dinero ──
// Recepcionista tiene reservaciones.view pero no debe poder mover dinero.
$recep = t_perms_preset($presets, 'recepcionista');
t_ok(permission_in_list('reservaciones.view', $recep), 'recepcionista ve reservaciones');
foreach (['caja.ajustes', 'caja.corte', 'reservaciones.cancelar'] as $mon) {
    t_ok(!permission_in_list($mon, $recep), "recepcionista NO tiene $mon (frontera monetaria)");
}
// dueno_remoto: solo lectura, sin cobros.
$dueno = t_perms_preset($presets, 'dueno_remoto');
t_ok(!permission_in_list('caja.cobros', $dueno), 'dueno_remoto NO cobra');
t_ok(!permission_in_list('reservaciones.create', $dueno), 'dueno_remoto NO crea reservaciones');

// ── Caracterizacion estatica del controlador ──
$src = file_get_contents(__DIR__ . '/../../app/controllers/ReservacionController.php');
t_ok($src !== false && $src !== '', 'ReservacionController legible');

/** Cuerpo aproximado de una accion: de `function NAME(` a la siguiente `function `/EOF. */
function t_cuerpo_accion(string $src, string $accion): string
{
    $ini = strpos($src, 'function ' . $accion . '(');
    if ($ini === false) {
        return '';
    }
    $sig = strpos($src, 'function ', $ini + 1);
    return substr($src, $ini, $sig === false ? null : $sig - $ini);
}

// Acciones de LECTURA gateadas en esta fase.
$gateadas = ['indexAction', 'verAction', 'calendarioAction', 'habitacionesApiAction', 'obtenerNotasAction'];
foreach ($gateadas as $accion) {
    $cuerpo = t_cuerpo_accion($src, $accion);
    t_ok($cuerpo !== '', "accion $accion existe");
    t_ok(strpos($cuerpo, "require_permission_or_403('reservaciones.view')") !== false,
        "$accion tiene el gate reservaciones.view");
}

// Acciones de ESCRITURA monetarias/combinadas: ahora gateadas (autorizacion
// monetaria aprobada 2026-07-19), cada una con su permiso especifico.
$gatesEscritura = [
    'checkInAction'           => "require_permission_or_403('habitaciones.checkin'",
    'checkOutAction'          => "require_permission_or_403('habitaciones.checkout'",
    'checkOutRapidoAction'    => "require_permission_or_403('habitaciones.checkout'",
    'checkOutParcialAction'   => "require_permission_or_403('habitaciones.checkout'",
    'registrarAnticipoAction' => "require_permission_or_403('caja.cobros'",
    'revertirAnticipoAction'  => "require_permission_or_403('caja.cobros'",
    'cambiarMetodoPagoAction' => "require_permission_or_403('caja.cobros'",
    'guardarAction'           => "require_permission_or_403('reservaciones.create'",
    'modificarDiasAction'     => "require_permission_or_403('reservaciones.edit'",
];
foreach ($gatesEscritura as $accion => $gate) {
    $cuerpo = t_cuerpo_accion($src, $accion);
    t_ok($cuerpo !== '', "accion $accion existe");
    t_ok(strpos($cuerpo, $gate) !== false, "$accion gateada ({$gate}')");
}

// Cancelacion / no-show: gate any-of (reservaciones.cancelar ∨ .edit) + 403,
// porque el preset de recepcionista trae .edit pero no .cancelar.
foreach (['cancelarAction', 'noShowAction'] as $accion) {
    $cuerpo = t_cuerpo_accion($src, $accion);
    t_ok($cuerpo !== '', "accion $accion existe");
    t_ok(strpos($cuerpo, "can_any(['reservaciones.cancelar', 'reservaciones.edit'])") !== false
        && strpos($cuerpo, 'deny_access_403') !== false,
        "$accion gateada con any-of cancelar/edit + 403");
}

t_fin();
