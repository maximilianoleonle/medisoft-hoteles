<?php
/**
 * Acciones ejecutables del copiloto: registrar gasto en caja, bloquear
 * habitacion (iniciar mantenimiento) y liberarla (finalizar). Ciclo completo
 * contra la BD de prueba: la frase produce la PROPUESTA correcta y ejecutarla
 * escribe con los mismos candados que las pantallas (corte abierto, categoria
 * del hotel, estado de la habitacion).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoAccionesTest\n";

t_reset_db();
$base = t_seed_base('copiloto-acciones');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();

$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado, created_at)
            VALUES (?, '204', 2, 'doble', 900, 'disponible', NOW())", [$hotelId]);
$habitacionId = (int) $db->lastInsertId();

$db->query("INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, activa, orden)
            VALUES (?, 'Mantenimiento', 'gasto', 1, 0)", [$hotelId]);
$categoriaId = (int) $db->lastInsertId();
$db->query("INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, activa, orden)
            VALUES (?, 'Papeleria', 'gasto', 1, 1)", [$hotelId]);

// Bloques motor_reservas + promociones activos (gate de la accion de cupon).
foreach (['motor_reservas', 'promociones'] as $clave) {
    $db->query("INSERT INTO modulos (clave, nombre) VALUES (?, ?)", [$clave, ucfirst($clave)]);
    $db->query("INSERT INTO hotel_modulos (hotel_id, modulo_id, activo) VALUES (?, ?, 1)", [$hotelId, (int) $db->lastInsertId()]);
}

$servicio = new CopilotoService();

// ── Gasto sin caja abierta: se frena en la propuesta ──
$r = $servicio->responder($hotelId, 'registra un gasto de 450 de plomeria en mantenimiento', $usuarioId);
t_eq('accion:gasto_caja_cerrada', $r['intent'] ?? null, 'gasto sin caja abierta: avisa y no propone');
t_ok(empty($r['accion']), 'gasto sin caja abierta: sin payload de accion');

// ── Abrir caja (mismo motor que la pantalla) ──
$cajaModel = new Caja();
$cajaModel->ensureDefaultCajaForHotel($hotelId);
$caja = $cajaModel->obtenerCajaPrincipal();
$apertura = $cajaModel->abrirCaja((int) $caja['id'], 1000, $usuarioId);
t_ok(!empty($apertura['success']), 'caja abierta para el resto del ciclo');

// ── Gasto sin mencionar categoria: pide una del catalogo ──
$r = $servicio->responder($hotelId, 'registra un gasto de 450 de plomeria', $usuarioId);
t_eq('accion:gasto_sin_categoria', $r['intent'] ?? null, 'gasto sin categoria: pide elegir');

// ── Gasto completo: propuesta con monto/categoria/concepto ──
$r = $servicio->responder($hotelId, 'registra un gasto de 450 de plomeria en mantenimiento', $usuarioId);
t_eq('accion:gasto', $r['intent'] ?? null, 'gasto completo: propone');
t_eq('registrar_gasto', $r['accion']['tipo'] ?? null, 'gasto completo: tipo de accion');
t_eq(450.0, (float) ($r['accion']['monto'] ?? 0), 'gasto completo: monto 450');
t_eq($categoriaId, (int) ($r['accion']['categoria_id'] ?? 0), 'gasto completo: categoria Mantenimiento');

// ── Ejecutar el gasto confirmado: cae en el corte abierto ──
$e = $servicio->ejecutarAccion($hotelId, 'registrar_gasto', [
    'monto' => 450.0,
    'categoria_id' => $categoriaId,
    'descripcion' => 'Plomeria',
], $usuarioId);
t_ok(!empty($e['success']), 'ejecutar gasto: success');

$mov = $db->query(
    "SELECT tipo, monto, metodo_pago, categoria_id, corte_id, hotel_id
     FROM movimientos_caja WHERE hotel_id = ? ORDER BY id DESC LIMIT 1",
    [$hotelId]
)->fetch();
t_eq('gasto', $mov['tipo'] ?? null, 'movimiento: tipo gasto');
t_eq(450.0, (float) ($mov['monto'] ?? 0), 'movimiento: monto 450');
t_eq('efectivo', $mov['metodo_pago'] ?? null, 'movimiento: efectivo forzado por el servidor');
t_ok((int) ($mov['corte_id'] ?? 0) > 0, 'movimiento: ligado al corte abierto');

// ── Categoria ajena/inexistente: rechazo sin insertar ──
$antes = (int) $db->query("SELECT COUNT(*) c FROM movimientos_caja WHERE hotel_id = ?", [$hotelId])->fetch()['c'];
$e = $servicio->ejecutarAccion($hotelId, 'registrar_gasto', [
    'monto' => 100.0,
    'categoria_id' => 999999,
    'descripcion' => 'Categoria falsa',
], $usuarioId);
t_ok(empty($e['success']), 'categoria inexistente: rechazado');
$despues = (int) $db->query("SELECT COUNT(*) c FROM movimientos_caja WHERE hotel_id = ?", [$hotelId])->fetch()['c'];
t_eq($antes, $despues, 'categoria inexistente: no inserto nada');

// ── Monto invalido: rechazo ──
$e = $servicio->ejecutarAccion($hotelId, 'registrar_gasto', [
    'monto' => 0,
    'categoria_id' => $categoriaId,
    'descripcion' => 'Monto cero',
], $usuarioId);
t_ok(empty($e['success']), 'monto 0: rechazado');

// ── Bloquear habitacion: propuesta + ejecucion ──
$r = $servicio->responder($hotelId, 'bloquea la 204 por pintura de paredes', $usuarioId);
t_eq('accion:bloqueo', $r['intent'] ?? null, 'bloqueo: propone');
t_eq('iniciar_mantenimiento', $r['accion']['tipo'] ?? null, 'bloqueo: usa el motor de mantenimiento');
t_eq($habitacionId, (int) ($r['accion']['habitacion_id'] ?? 0), 'bloqueo: habitacion 204');

$e = $servicio->ejecutarAccion($hotelId, 'iniciar_mantenimiento', [
    'habitacion_id' => $habitacionId,
    'motivo' => 'pintura de paredes',
], $usuarioId);
t_ok(!empty($e['success']), 'bloqueo ejecutado: success');
$estado = $db->query("SELECT estado FROM habitaciones WHERE id = ?", [$habitacionId])->fetch()['estado'];
t_eq('mantenimiento', $estado, 'bloqueo ejecutado: habitacion en mantenimiento');

// ── Bloqueo sin motivo: se exige ──
$r = $servicio->responder($hotelId, 'bloquea la 204', $usuarioId);
t_eq('accion:mant_sin_motivo', $r['intent'] ?? null, 'bloqueo sin motivo: lo pide');

// ── Liberar: propuesta + ejecucion ──
$r = $servicio->responder($hotelId, 'desbloquea la 204', $usuarioId);
t_eq('accion:desbloqueo', $r['intent'] ?? null, 'desbloqueo: propone');
t_eq('finalizar_mantenimiento', $r['accion']['tipo'] ?? null, 'desbloqueo: tipo de accion');

$e = $servicio->ejecutarAccion($hotelId, 'finalizar_mantenimiento', [
    'habitacion_id' => $habitacionId,
], $usuarioId);
t_ok(!empty($e['success']), 'desbloqueo ejecutado: success');
$estado = $db->query("SELECT estado FROM habitaciones WHERE id = ?", [$habitacionId])->fetch()['estado'];
t_eq('disponible', $estado, 'desbloqueo ejecutado: habitacion disponible');
$mant = $db->query(
    "SELECT estado FROM mantenimientos_habitaciones WHERE habitacion_id = ? ORDER BY id DESC LIMIT 1",
    [$habitacionId]
)->fetch();
t_eq('completado', $mant['estado'] ?? null, 'desbloqueo ejecutado: mantenimiento completado');

// ── Liberar una habitacion que no esta bloqueada: aviso sin accion ──
$r = $servicio->responder($hotelId, 'desbloquea la 204', $usuarioId);
t_eq('accion:desbloqueo_no_aplica', $r['intent'] ?? null, 'desbloqueo no aplica: avisa');
t_ok(empty($r['accion']), 'desbloqueo no aplica: sin payload de accion');

// ── Cupon: propuesta con codigo generado y mes resuelto ──
$r = $servicio->responder($hotelId, 'crea un cupon de 10% para agosto', $usuarioId);
t_eq('accion:cupon', $r['intent'] ?? null, 'cupon: propone');
$acc = $r['accion'] ?? [];
t_eq('crear_cupon', $acc['tipo'] ?? null, 'cupon: tipo de accion');
t_eq('porcentaje', $acc['cupon_tipo'] ?? null, 'cupon: porcentaje');
t_eq(10.0, (float) ($acc['valor'] ?? 0), 'cupon: valor 10');
t_eq('AGOSTO10', $acc['codigo'] ?? null, 'cupon: codigo generado legible');
t_eq('08-01', substr((string) ($acc['vigente_desde'] ?? ''), 5), 'cupon: desde el 1 de agosto');
t_eq('08-31', substr((string) ($acc['vigente_hasta'] ?? ''), 5), 'cupon: hasta el 31 de agosto');
t_ok(($acc['vigente_hasta'] ?? '') >= date('Y-m-d'), 'cupon: la vigencia no nace vencida');

// ── Numero sin % ni $: pregunta, no adivina ──
$r2 = $servicio->responder($hotelId, 'crea un cupon de 10 para agosto', $usuarioId);
t_eq('accion:cupon_valor_ambiguo', $r2['intent'] ?? null, 'cupon ambiguo: pregunta % o $');
t_ok(empty($r2['accion']), 'cupon ambiguo: sin payload de accion');

// ── Ejecutar el cupon confirmado (payload real de la propuesta) ──
$e = $servicio->ejecutarAccion($hotelId, 'crear_cupon', $acc, $usuarioId);
t_ok(!empty($e['success']), 'ejecutar cupon: success');
$cup = $db->query(
    "SELECT codigo, tipo, valor, activo, vigente_desde, vigente_hasta, limite_usos
     FROM motor_cupones WHERE hotel_id = ? ORDER BY id DESC LIMIT 1",
    [$hotelId]
)->fetch();
t_eq('AGOSTO10', $cup['codigo'] ?? null, 'cupon en BD: codigo');
t_eq('porcentaje', $cup['tipo'] ?? null, 'cupon en BD: tipo');
t_eq(10.0, (float) ($cup['valor'] ?? 0), 'cupon en BD: valor');
t_eq(1, (int) ($cup['activo'] ?? 0), 'cupon en BD: activo');
t_eq(null, $cup['limite_usos'], 'cupon en BD: sin limite');

// ── El generador no repite codigos: la siguiente propuesta sufija ──
$r3 = $servicio->responder($hotelId, 'crea un cupon de 10% para agosto', $usuarioId);
t_eq('AGOSTO10-2', $r3['accion']['codigo'] ?? null, 'cupon repetido: codigo sufijado');

// ── Duplicado forzado: el UNIQUE de la tabla lo frena con mensaje humano ──
$e = $servicio->ejecutarAccion($hotelId, 'crear_cupon', $acc, $usuarioId);
t_ok(empty($e['success']), 'cupon duplicado: rechazado');

// ── Hotel sin el bloque promociones: la accion ni se propone ──
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Sin Promos', 'sin-promos', 1, NOW())");
$hotelB = (int) $db->lastInsertId();
$r = $servicio->responder($hotelB, 'crea un cupon de 10% para agosto', $usuarioId);
t_eq('accion:cupon_sin_bloque', $r['intent'] ?? null, 'sin bloque: avisa que no esta contratado');
t_ok(empty($r['accion']), 'sin bloque: sin payload de accion');

t_fin();
