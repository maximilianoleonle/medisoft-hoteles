<?php
/**
 * Feedback 👍/👎 del copiloto: cada respuesta terminal viaja con el id de su
 * registro (mid), marcarFeedback lo guarda con scope de hotel (re-votar
 * actualiza, otro hotel jamas puede votar filas ajenas) y resumenUso lo
 * agrega para el panel de valor.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoFeedbackTest\n";

t_reset_db();
$base = t_seed_base('copiloto-feedback');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Ajeno', 'ajeno-fb', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

$servicio = new CopilotoService();

// ── La respuesta terminal trae el ancla del feedback ──
$r = $servicio->responder($hotelId, 'como voy de caja', $usuarioId);
$mid = (int) ($r['mid'] ?? 0);
t_ok($mid > 0, 'mid: la respuesta trae el id de su registro');

// Una propuesta de accion NO trae mid (no se vota lo que aun no responde).
$r2 = $servicio->responder($hotelId, 'bloquea la 204 por pintura', $usuarioId);
t_ok(!isset($r2['mid']), 'mid: una propuesta de accion no trae ancla');

// ── Votar 👎 y luego corregir a 👍 (re-votar actualiza) ──
t_ok($servicio->marcarFeedback($hotelId, $mid, false, $usuarioId), 'voto: 👎 aceptado');
$fila = $db->query("SELECT util, feedback_at FROM copiloto_mensajes WHERE id = ?", [$mid])->fetch();
t_eq(0, (int) $fila['util'], 'voto: util=0 guardado');
t_ok(!empty($fila['feedback_at']), 'voto: feedback_at con fecha');

t_ok($servicio->marcarFeedback($hotelId, $mid, true, $usuarioId), 're-voto: 👍 aceptado');
$fila = $db->query("SELECT util FROM copiloto_mensajes WHERE id = ?", [$mid])->fetch();
t_eq(1, (int) $fila['util'], 're-voto: util=1 actualizado');

// Mismo voto repetido: sigue siendo exito (idempotente para el widget).
t_ok($servicio->marcarFeedback($hotelId, $mid, true, $usuarioId), 'voto repetido: exito idempotente');

// ── Tenancy: otro hotel no puede votar esta fila ──
t_ok(!$servicio->marcarFeedback($hotelB, $mid, false, $usuarioId), 'tenancy: otro hotel rechazado');
$fila = $db->query("SELECT util FROM copiloto_mensajes WHERE id = ?", [$mid])->fetch();
t_eq(1, (int) $fila['util'], 'tenancy: el voto no cambio');

// ── Ids invalidos ──
t_ok(!$servicio->marcarFeedback($hotelId, 0, true, $usuarioId), 'mid 0: rechazado');
t_ok(!$servicio->marcarFeedback($hotelId, 999999, true, $usuarioId), 'mid inexistente: rechazado');

// ── resumenUso agrega el feedback para el panel ──
$r3 = $servicio->responder($hotelId, 'quien llega hoy', $usuarioId);
$servicio->marcarFeedback($hotelId, (int) $r3['mid'], false, $usuarioId);
$uso = $servicio->resumenUso($hotelId, 30);
t_eq(1, (int) ($uso['feedback']['si'] ?? -1), 'resumen: un 👍');
t_eq(1, (int) ($uso['feedback']['no'] ?? -1), 'resumen: un 👎');

t_fin();
