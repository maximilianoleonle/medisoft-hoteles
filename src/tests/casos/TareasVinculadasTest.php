<?php
/**
 * Tareas manuales (/tareas) vinculadas a habitacion o area: crear una tarea
 * de limpieza "para ahora" refleja el estado en la unidad (disponible ->
 * limpieza), completar/cancelar desde /tareas la libera si no quedan otras
 * tareas activas, las programadas a futuro no tocan estado hasta iniciarse,
 * mantenimiento solo vincula (no bloquea) y habitacion/area son excluyentes.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "TareasVinculadasTest\n";

t_reset_db();
$base = t_seed_base('tareas-vinc-a');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa)
     VALUES (?, '101', 'sencilla', 1, 500.00, 'disponible', 1)",
    [$hotelId]
);
$habitacionId = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO areas_hotel (hotel_id, nombre, tipo, estado, activa)
     VALUES (?, 'Lobby Central', 'lobby', 'disponible', 1)",
    [$hotelId]
);
$areaId = (int) $db->lastInsertId();

// Hotel B: blanco del aislamiento.
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B Vinc', 'tareas-vinc-b', 1, NOW())");
$hotelBId = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO areas_hotel (hotel_id, nombre, tipo, estado, activa) VALUES (?, 'Alberca B', 'alberca', 'disponible', 1)",
    [$hotelBId]
);
$areaBId = (int) $db->lastInsertId();

$tareas = new TareaOperativa();

$estadoHabitacion = function () use ($db, $habitacionId): string {
    $fila = $db->query("SELECT estado FROM habitaciones WHERE id = ?", [$habitacionId])->fetch();
    return (string) ($fila['estado'] ?? '');
};
$estadoArea = function () use ($db, $areaId): string {
    $fila = $db->query("SELECT estado FROM areas_hotel WHERE id = ?", [$areaId])->fetch();
    return (string) ($fila['estado'] ?? '');
};

// ── Limpieza inmediata con habitacion: refleja y libera al completar ──
$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza profunda 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_ok($tareaId > 0, 'tarea limpieza con habitacion creada');
t_eq('limpieza', $estadoHabitacion(), 'habitacion disponible pasa a limpieza al crear');

$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'completar', $usuarioId);
t_eq('disponible', $estadoHabitacion(), 'completar desde /tareas libera la habitacion');

// ── Programada a futuro: no toca estado; iniciar la refleja; cancelar libera ──
$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza de manana 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
    'fecha_programada' => date('Y-m-d H:i:s', strtotime('+2 day')),
], $usuarioId);
t_eq('disponible', $estadoHabitacion(), 'tarea programada a futuro no cambia estado');

$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'iniciar', $usuarioId);
t_eq('limpieza', $estadoHabitacion(), 'iniciar la tarea manda la habitacion a limpieza');

$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'cancelar', $usuarioId, 'QA cancelacion');
t_eq('disponible', $estadoHabitacion(), 'cancelar libera la habitacion');

// ── Dos tareas activas sobre el mismo cuarto: la primera en cerrar NO libera ──
$tareaA = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza A 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
], $usuarioId);
$tareaB = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza B 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_eq('limpieza', $estadoHabitacion(), 'habitacion en limpieza con dos tareas activas');
$tareas->cambiarEstadoManualParaHotel($tareaA, $hotelId, 'completar', $usuarioId);
t_eq('limpieza', $estadoHabitacion(), 'cerrar una tarea no libera si queda otra activa');
$tareas->cambiarEstadoManualParaHotel($tareaB, $hotelId, 'completar', $usuarioId);
t_eq('disponible', $estadoHabitacion(), 'cerrar la ultima tarea activa libera');

// ── Limpieza inmediata con area: refleja y libera ──
$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza lobby',
    'categoria' => 'limpieza',
    'area_id' => $areaId,
], $usuarioId);
$fila = $db->query("SELECT area_id, habitacion_id FROM tareas_operativas WHERE id = ?", [$tareaId])->fetch();
t_eq($areaId, (int) ($fila['area_id'] ?? 0), 'tarea ligada al area');
t_ok($fila['habitacion_id'] === null, 'tarea de area sin habitacion');
t_eq('limpieza', $estadoArea(), 'area disponible pasa a limpieza al crear');

$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'completar', $usuarioId);
t_eq('disponible', $estadoArea(), 'completar desde /tareas libera el area');

// ── Mantenimiento vinculado: solo vincula, NO bloquea la unidad ──
$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Revisar clima 101',
    'categoria' => 'mantenimiento',
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_eq('disponible', $estadoHabitacion(), 'tarea de mantenimiento no cambia el estado del cuarto');
$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'completar', $usuarioId);
t_eq('disponible', $estadoHabitacion(), 'completar mantenimiento tampoco toca estado');

// ── Ocupada: la limpieza inmediata no pisa el estado ──
$db->query("UPDATE habitaciones SET estado = 'ocupada' WHERE id = ?", [$habitacionId]);
$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza con huesped 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_eq('ocupada', $estadoHabitacion(), 'habitacion ocupada no cambia a limpieza');
$tareas->cambiarEstadoManualParaHotel($tareaId, $hotelId, 'completar', $usuarioId);
t_eq('ocupada', $estadoHabitacion(), 'cerrar la tarea no toca una habitacion ocupada');

// ── Conexion inversa: "marcar limpia" de pantalla (camarista/habitaciones)
// cierra la tarea manual activa, y "programar" reutiliza esa tarea ──
$db->query("UPDATE habitaciones SET estado = 'disponible' WHERE id = ?", [$habitacionId]);
$db->query("INSERT INTO trabajadores (hotel_id, nombre_completo, estado) VALUES (?, 'Camarista QA', 'activo')", [$hotelId]);
$trabajadorId = (int) $db->lastInsertId();

$tareaId = $tareas->crearParaHotel($hotelId, [
    'titulo' => 'Limpieza manual 101',
    'categoria' => 'limpieza',
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_eq('limpieza', $estadoHabitacion(), 'tarea manual manda el cuarto a limpieza');

$reprogramada = $tareas->programarLimpiezaParaHotel($hotelId, $habitacionId, date('Y-m-d', strtotime('+1 day')), [$trabajadorId], $usuarioId);
t_eq($tareaId, $reprogramada, 'programar desde camarista reutiliza la tarea manual activa (no duplica)');

$tareas->completarLimpiezaConPersonalParaHotel($hotelId, $habitacionId, [$trabajadorId], $usuarioId);
$fila = $db->query("SELECT estado, notas_cierre FROM tareas_operativas WHERE id = ?", [$tareaId])->fetch();
t_eq('completada', $fila['estado'] ?? null, 'marcar limpia desde pantalla cierra la tarea manual');
t_ok(strpos((string) ($fila['notas_cierre'] ?? ''), 'Camarista QA') !== false, 'nota de cierre registra quien limpio');
$activas = $db->query(
    "SELECT COUNT(*) AS n FROM tareas_operativas WHERE hotel_id = ? AND habitacion_id = ? AND categoria = 'limpieza' AND estado IN ('pendiente','asignada','en_proceso')",
    [$hotelId, $habitacionId]
)->fetch();
t_eq(0, (int) ($activas['n'] ?? 99), 'sin tareas activas de limpieza tras el cierre de pantalla');
$db->query("UPDATE habitaciones SET estado = 'ocupada' WHERE id = ?", [$habitacionId]);

// ── Exclusividad habitacion/area y aislamiento multi-hotel ──
$rechazada = false;
try {
    $tareas->crearParaHotel($hotelId, [
        'titulo' => 'Tarea ambigua',
        'categoria' => 'limpieza',
        'habitacion_id' => $habitacionId,
        'area_id' => $areaId,
    ], $usuarioId);
} catch (Throwable $e) {
    $rechazada = true;
}
t_ok($rechazada, 'habitacion + area juntas rechazadas');

$cruzada = false;
try {
    $tareas->crearParaHotel($hotelId, [
        'titulo' => 'Tarea cruzada',
        'categoria' => 'limpieza',
        'area_id' => $areaBId,
    ], $usuarioId);
} catch (Throwable $e) {
    $cruzada = true;
}
t_ok($cruzada, 'area de otro hotel rechazada');

t_fin();
