<?php
/**
 * Compatibilidad RBAC legacy (Fase 3, paso 1 del plan de autorizacion).
 *
 * Los hotel_usuarios SIN role_id (rol-string legacy) deben resolver permisos
 * contra el PRESET homonimo de config/permisos.php — incluidos los modulos
 * que el arreglo historico no conocia (reservaciones.*, documentos.*,
 * tareas.*) — y todo lo desconocido se niega (matriz cerrada por defecto).
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';
require_once __DIR__ . '/../../app/helpers/auth.php';

echo "LegacyRbacCompatTest\n";

/** Sesion legacy simulada: usuario con rol-string y SIN role_id. */
function t_sesion_legacy(string $rol): void
{
    $_SESSION = [
        'user_id' => 999999, // no existe en hotel_usuarios: fuerza fallback legacy
        'user' => ['id' => 999999, 'rol' => $rol, 'nombre_usuario' => 'qa_legacy'],
        'hotel_id' => 424242,
        'hotel_slug' => 'hotel-fantasma-qa',
        'hotel_usuario' => ['id' => 1, 'rol' => $rol], // sin role_id (legacy)
    ];
}

// ── El preset existe para los tres roles legacy detectados ──
t_ok(is_array(legacy_preset_permissions('administrador')), 'preset administrador resoluble');
t_ok(is_array(legacy_preset_permissions('gerente')), 'preset gerente resoluble');
t_ok(is_array(legacy_preset_permissions('recepcionista')), 'preset recepcionista resoluble');
t_eq(null, legacy_preset_permissions('rol_inventado'), 'rol desconocido no tiene preset');

// ── Administrador legacy: reservaciones/documentos/tareas ya no bloquean ──
t_sesion_legacy('administrador');
t_ok(can('reservaciones.view'), 'admin legacy ve reservaciones');
t_ok(can('reservaciones.create'), 'admin legacy crea reservaciones');
t_ok(can('reservaciones.cancelar'), 'admin legacy cancela (reservaciones.all)');
t_ok(can('documentos.view'), 'admin legacy ve documentos');
t_ok(can('tareas.view'), 'admin legacy ve tareas');
t_ok(can('habitaciones.checkin'), 'admin legacy conserva check-in');
t_ok(can('caja.cobros'), 'admin legacy conserva cobros');
t_ok(!can('caja.ajustes'), 'admin legacy SIN caja.ajustes (igual que preset)');
t_ok(!can('roles.manage'), 'admin legacy no gestiona roles');
t_ok(!can('permiso.inexistente'), 'permiso desconocido se niega (cerrado por defecto)');

// ── Gerente legacy: paridad con preset gerente ──
t_sesion_legacy('gerente');
t_ok(can('reservaciones.cancelar'), 'gerente legacy cancela reservaciones');
t_ok(can('caja.ajustes'), 'gerente legacy conserva caja.ajustes');
t_ok(can('roles.manage'), 'gerente legacy gestiona roles (paridad preset)');
t_ok(can('nomina.calcular'), 'gerente legacy opera nomina (nomina.all)');
t_ok(!can('saas.admin'), 'gerente legacy no es admin saas');

// ── Recepcionista legacy: opera sin poder cancelar ni ajustar ──
t_sesion_legacy('recepcionista');
t_ok(can('reservaciones.view'), 'recepcionista legacy ve reservaciones');
t_ok(can('reservaciones.create'), 'recepcionista legacy crea reservaciones');
t_ok(can('reservaciones.edit'), 'recepcionista legacy edita reservaciones');
t_ok(!can('reservaciones.cancelar'), 'recepcionista legacy NO cancela');
t_ok(!can('caja.ajustes'), 'recepcionista legacy NO ajusta caja');
t_ok(!can('caja.corte'), 'recepcionista legacy NO corta caja');
t_ok(can('huespedes.create'), 'recepcionista legacy registra huespedes');
t_ok(!can('usuarios.view'), 'recepcionista legacy no administra usuarios');

// ── Rol desconocido: nada ──
t_sesion_legacy('espia');
t_ok(!can('reservaciones.view'), 'rol desconocido: negado view');
t_ok(!can('caja.view'), 'rol desconocido: negado caja');

// ── Sin sesion: nada ──
$_SESSION = [];
t_ok(!can('reservaciones.view'), 'sin sesion: negado');

t_fin();
