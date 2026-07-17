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

// Bloques activos: cupones (motor_reservas + promociones) y pago CxP (compras).
foreach (['motor_reservas', 'promociones', 'compras'] as $clave) {
    $db->query("INSERT INTO modulos (clave, nombre) VALUES (?, ?)", [$clave, ucfirst($clave)]);
    $db->query("INSERT INTO hotel_modulos (hotel_id, modulo_id, activo) VALUES (?, ?, 1)", [$hotelId, (int) $db->lastInsertId()]);
}

// Proveedor con dos cuentas pendientes (la vieja debe ganar) y un trabajador
// que comparte nombre con otro proveedor (guard anti-nomina).
$db->query("INSERT INTO proveedores (hotel_id, nombre, activo) VALUES (?, 'Garcia Distribuciones', 1)", [$hotelId]);
$proveedorId = (int) $db->lastInsertId();
$db->query("INSERT INTO cuentas_por_pagar (hotel_id, proveedor_id, folio, fecha_emision, fecha_vencimiento, estado, total, saldo)
            VALUES (?, ?, 'F-VIEJA', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'vencida', 500, 500)", [$hotelId, $proveedorId]);
$cuentaViejaId = (int) $db->lastInsertId();
$db->query("INSERT INTO cuentas_por_pagar (hotel_id, proveedor_id, folio, fecha_emision, fecha_vencimiento, estado, total, saldo)
            VALUES (?, ?, 'F-NUEVA', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'pendiente', 800, 800)", [$hotelId, $proveedorId]);
$db->query("INSERT INTO proveedores (hotel_id, nombre, activo) VALUES (?, 'Maria Materiales', 1)", [$hotelId]);
$db->query("INSERT INTO trabajadores (hotel_id, nombre_completo, estado) VALUES (?, 'Maria Lopez', 'activo')", [$hotelId]);

// Huesped conocido para el prellenado de reservacion.
$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Juan Perez', NOW())", [$hotelId]);
$huespedJuanId = (int) $db->lastInsertId();

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

// ── Pago a proveedor: la cuenta MAS ANTIGUA gana y el monto se respeta ──
$r = $servicio->responder($hotelId, 'pagale 300 al proveedor garcia', $usuarioId);
t_eq('accion:pago', $r['intent'] ?? null, 'pago: propone');
$accPago = $r['accion'] ?? [];
t_eq('pagar_proveedor', $accPago['tipo'] ?? null, 'pago: tipo de accion');
t_eq($cuentaViejaId, (int) ($accPago['cuenta_id'] ?? 0), 'pago: elige la cuenta mas antigua');
t_eq(300.0, (float) ($accPago['monto'] ?? 0), 'pago: monto 300');
t_eq('efectivo', $accPago['metodo'] ?? null, 'pago: efectivo default');

// ── Sin monto: propone saldar la cuenta ──
$r = $servicio->responder($hotelId, 'pagale al proveedor garcia', $usuarioId);
t_eq(500.0, (float) ($r['accion']['monto'] ?? 0), 'pago sin monto: propone el saldo completo');

// ── Monto mayor al saldo: se frena en la propuesta ──
$r = $servicio->responder($hotelId, 'pagale 2000 al proveedor garcia', $usuarioId);
t_eq('accion:pago_excede', $r['intent'] ?? null, 'pago excede: avisa el saldo');
t_ok(empty($r['accion']), 'pago excede: sin payload de accion');

// ── Ejecutar el pago confirmado: cuenta + caja + trazabilidad ──
$e = $servicio->ejecutarAccion($hotelId, 'pagar_proveedor', $accPago, $usuarioId);
t_ok(!empty($e['success']), 'ejecutar pago: success');
$cxp = $db->query("SELECT saldo, estado FROM cuentas_por_pagar WHERE id = ?", [$cuentaViejaId])->fetch();
t_eq(200.0, (float) ($cxp['saldo'] ?? 0), 'pago ejecutado: saldo 500 - 300 = 200');
t_eq('parcial', $cxp['estado'] ?? null, 'pago ejecutado: estado parcial');
$movPago = $db->query(
    "SELECT tipo, categoria, monto, metodo_pago, corte_id FROM movimientos_caja
     WHERE hotel_id = ? ORDER BY id DESC LIMIT 1",
    [$hotelId]
)->fetch();
t_eq('gasto', $movPago['tipo'] ?? null, 'pago en caja: tipo gasto');
t_eq('Pago proveedor', $movPago['categoria'] ?? null, 'pago en caja: categoria Pago proveedor');
t_eq(300.0, (float) ($movPago['monto'] ?? 0), 'pago en caja: monto 300');
t_ok((int) ($movPago['corte_id'] ?? 0) > 0, 'pago en caja: ligado al corte abierto');
$movCxp = $db->query(
    "SELECT tipo_movimiento, monto, saldo_posterior FROM cuentas_por_pagar_movimientos
     WHERE hotel_id = ? AND cuenta_por_pagar_id = ? ORDER BY id DESC LIMIT 1",
    [$hotelId, $cuentaViejaId]
)->fetch();
t_eq('PAGO_REFERENCIAL', $movCxp['tipo_movimiento'] ?? null, 'trazabilidad CxP: movimiento referencial');
t_eq(200.0, (float) ($movCxp['saldo_posterior'] ?? -1), 'trazabilidad CxP: saldo posterior 200');

// ── Proveedor no identificado: lista a quienes se les debe ──
$r = $servicio->responder($hotelId, 'pagale al proveedor fantasma', $usuarioId);
t_eq('accion:pago_sin_proveedor', $r['intent'] ?? null, 'pago sin proveedor: pregunta a cual');

// ── Nombre que tambien es de personal y sin la palabra "proveedor" ──
$r = $servicio->responder($hotelId, 'pagale 200 a maria', $usuarioId);
t_eq('accion:pago_quiza_nomina', $r['intent'] ?? null, 'nombre compartido: manda a desambiguar');
t_ok(empty($r['accion']), 'nombre compartido: sin payload de accion');

// ── "paga la nomina" no es un pago a proveedor ──
$r = $servicio->responder($hotelId, 'paga la nomina', $usuarioId);
t_ok(strpos((string) ($r['intent'] ?? ''), 'accion:pago') !== 0, 'paga la nomina: no se confunde con proveedor');

// ── Reservacion por chat: formulario prellenado (no crea nada) ──
$fr = CopilotoService::parsearFechasReserva('del 20 al 22 de diciembre');
$r = $servicio->responder($hotelId, 'reservale la 204 a juan perez del 20 al 22 de diciembre', $usuarioId);
t_eq('accion:reserva_link', $r['intent'] ?? null, 'reserva: arma el enlace');
t_ok(empty($r['accion']), 'reserva: NO es accion POST (no crea nada)');
$url = (string) ($r['acciones'][0]['url'] ?? '');
t_ok(strpos($url, 'reservaciones/crear?') === 0, 'reserva: apunta al formulario de crear');
t_ok(strpos($url, 'fecha_entrada=' . $fr['entrada']) !== false, 'reserva: entrada prellenada');
t_ok(strpos($url, 'fecha_salida=' . $fr['salida']) !== false, 'reserva: salida prellenada');
t_ok(strpos($url, 'habitacion_id=' . $habitacionId) !== false, 'reserva: habitacion libre prellenada');
t_ok(strpos($url, 'huesped_id=' . $huespedJuanId) !== false, 'reserva: huesped encontrado y prellenado');
$reservasAntes = (int) $db->query("SELECT COUNT(*) c FROM reservaciones WHERE hotel_id = ?", [$hotelId])->fetch()['c'];
t_eq(0, $reservasAntes, 'reserva: cero reservaciones creadas por el chat');

// ── Habitacion ocupada esas noches: fechas si, habitacion no ──
$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Ocupante Previo', NOW())", [$hotelId]);
$otroHuesped = (int) $db->lastInsertId();
$db->query("INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
            VALUES (?, ?, ?, ?, 1800, 'confirmada', NOW())", [$hotelId, $otroHuesped, $fr['entrada'], $fr['salida']]);
$reservaOcupante = (int) $db->lastInsertId();
$db->query("INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
            VALUES (?, ?, ?, 900)", [$hotelId, $reservaOcupante, $habitacionId]);
$r = $servicio->responder($hotelId, 'reservale la 204 a juan perez del 20 al 22 de diciembre', $usuarioId);
t_eq('accion:reserva_ocupada', $r['intent'] ?? null, 'reserva ocupada: avisa el cruce');
$url = (string) ($r['acciones'][0]['url'] ?? '');
t_ok(strpos($url, 'fecha_entrada=') !== false, 'reserva ocupada: conserva las fechas');
t_ok(strpos($url, 'habitacion_id=') === false, 'reserva ocupada: sin habitacion preseleccionada');

// ── Sin fechas: arranca el flujo pidiendolas (con lo demas ya capturado) ──
$r = $servicio->responder($hotelId, 'reservale la 204 a juan perez', $usuarioId);
t_eq('flujo:reserva_fechas', $r['intent'] ?? null, 'reserva sin fechas: arranca el flujo');
t_eq($huespedJuanId, (int) ($r['flujo']['huesped_id'] ?? 0), 'reserva sin fechas: huesped ya capturado en el estado');

// ── "¿tiene reserva juan?" sigue siendo busqueda, no accion ──
$r = $servicio->responder($hotelId, 'tiene reserva juan perez?', $usuarioId);
t_eq('busca:huesped', $r['intent'] ?? null, 'pregunta de reserva: sigue cayendo a busqueda de huesped');

// ── "como hago una reservacion" sigue siendo FAQ ──
$r = $servicio->responder($hotelId, 'como hago una reservacion', $usuarioId);
t_ok(strpos((string) ($r['intent'] ?? ''), 'accion:reserva') !== 0, 'como hago: no se confunde con la accion');

// ── Flujo campo por campo: arranque generico pide fechas ──
$r = $servicio->responder($hotelId, 'quiero hacer una reservacion', $usuarioId);
t_eq('flujo:reserva_fechas', $r['intent'] ?? null, 'flujo: arranca pidiendo fechas');
$fl = $r['flujo'] ?? null;
t_eq('fechas', $fl['paso'] ?? null, 'flujo: estado con paso fechas');
$qs = $r['sugerencias'] ?? [];
t_ok(count($qs) >= 3, 'flujo fechas: trae respuestas posibles como chips');
t_eq('hoy', $qs[0][1] ?? null, 'flujo fechas: la primera opcion es hoy');
t_eq('cancelar', $qs[count($qs) - 1][1] ?? null, 'flujo fechas: cancelar siempre es la ultima opcion');

// ── Responde fechas -> pide habitacion ──
$r = $servicio->responder($hotelId, 'del 20 al 22 de diciembre', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_habitacion', $r['intent'] ?? null, 'flujo: tras fechas pide habitacion');
$fl = $r['flujo'];
t_eq($fr['entrada'], $fl['fe'] ?? null, 'flujo: fechas guardadas en el estado');

// ── La 204 esta ocupada esas noches: re-pregunta ──
$r = $servicio->responder($hotelId, 'la 204', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_hab_ocupada', $r['intent'] ?? null, 'flujo: habitacion ocupada re-pregunta');
$fl = $r['flujo'];

// ── Por TIPO: la unica "doble" (204) esta ocupada -> avisa sin libre de ese tipo ──
$r = $servicio->responder($hotelId, 'una doble', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_hab_tipo_ocupado', $r['intent'] ?? null, 'flujo: tipo sin habitacion libre re-pregunta');
$fl = $r['flujo'];

// ── "cualquiera" salta la habitacion -> pregunta ¿registrado o nuevo? ──
$r = $servicio->responder($hotelId, 'cualquiera', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_tipo_huesped', $r['intent'] ?? null, 'flujo: pregunta si es registrado o nuevo');
$fl = $r['flujo'];
$labels = array_column($r['sugerencias'] ?? [], 0);
t_ok(in_array('✅ Ya registrado', $labels, true), 'tipo huesped: chip de registrado');
t_ok(in_array('🆕 Huesped nuevo', $labels, true), 'tipo huesped: chip de nuevo');

// ── Rama REGISTRADO + el nombre existe: busca y cierra con su id ──
$r2 = $servicio->responder($hotelId, 'ya esta registrado', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_nombre', $r2['intent'] ?? null, 'registrado: pasa a pedir el nombre');
t_eq(1, $r2['flujo']['reg'] ?? 0, 'registrado: flag activo y sobrevive el saneador');
$r2 = $servicio->responder($hotelId, 'juan perez', $usuarioId, '', '', null, json_encode($r2['flujo']));
t_eq('accion:reserva_link', $r2['intent'] ?? null, 'registrado existente: cierra directo');
t_ok(strpos((string) ($r2['acciones'][0]['url'] ?? ''), 'huesped_id=' . $huespedJuanId) !== false, 'registrado existente: enlace con su id');

// ── Rama REGISTRADO pero NO aparece: ofrece darlo de alta con ese nombre ──
$r3 = $servicio->responder($hotelId, 'ya esta registrado', $usuarioId, '', '', null, json_encode($fl));
$r3 = $servicio->responder($hotelId, 'rodrigo montano', $usuarioId, '', '', null, json_encode($r3['flujo']));
t_eq('flujo:reserva_nombre_noesta', $r3['intent'] ?? null, 'registrado inexistente: avisa que no esta');
t_ok(in_array('🆕 Registrarlo como nuevo', array_column($r3['sugerencias'] ?? [], 0), true), 'registrado inexistente: chip de darlo de alta');
$r3 = $servicio->responder($hotelId, 'registralo como nuevo', $usuarioId, '', '', null, json_encode($r3['flujo']));
t_eq('flujo:reserva_telefono', $r3['intent'] ?? null, 'registralo como nuevo: pasa al telefono');
t_eq('rodrigo montano', $r3['flujo']['nombre'] ?? null, 'registralo como nuevo: conserva el nombre pendiente');
t_eq(1, $r3['flujo']['nuevo'] ?? 0, 'registralo como nuevo: marcado nuevo');

// ── Rama NUEVO desde la pregunta: el nombre va DIRECTO al alta ──
$r4 = $servicio->responder($hotelId, 'es un huesped nuevo', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_nombre', $r4['intent'] ?? null, 'nvo_dir: pasa a pedir el nombre en tono de alta');
t_eq(1, $r4['flujo']['nvo_dir'] ?? 0, 'nvo_dir: flag activo y sobrevive el saneador');
$r4 = $servicio->responder($hotelId, 'juan perez', $usuarioId, '', '', null, json_encode($r4['flujo']));
t_eq('flujo:reserva_telefono', $r4['intent'] ?? null, 'nvo_dir: aunque el nombre exista, va directo al alta');
t_eq(1, $r4['flujo']['nuevo'] ?? 0, 'nvo_dir: marcado nuevo sin buscar el catalogo');

// ── Pregunta de datos a MEDIA captura: se responde y el flujo sigue vivo ──
$r = $servicio->responder($hotelId, 'cuanto tengo en caja?', $usuarioId, '', '', null, json_encode($fl));
t_eq('caja', $r['intent'] ?? null, 'flujo: una pregunta de caja se responde normal');
t_ok(empty($r['flujo_fin']), 'flujo: la pregunta no mata la captura');

// ── Nombre escrito DIRECTO en la pregunta registrado/nuevo: se acepta ──
// (delegacion al paso nombre: no esta en el catalogo -> nuevo -> telefono)
$r = $servicio->responder($hotelId, 'pedro ramirez', $usuarioId, '', '', null, json_encode($fl));
t_eq('flujo:reserva_telefono', $r['intent'] ?? null, 'flujo: huesped nuevo pide telefono');
$fl = $r['flujo'];
t_eq(1, $fl['nuevo'] ?? 0, 'flujo: marcado como nuevo');

// ── Telefono -> propuesta final con accion confirmable ──
$r = $servicio->responder($hotelId, '55 1234 5678', $usuarioId, '', '', null, json_encode($fl));
t_eq('accion:reserva_huesped', $r['intent'] ?? null, 'flujo: cierre con propuesta');
t_eq('finalizar_reserva', $r['accion']['tipo'] ?? null, 'flujo: accion finalizar_reserva');
t_ok(!empty($r['flujo_fin']), 'flujo: el estado se cierra al proponer');
$accReserva = $r['accion'];

// ── Ejecutar: registra al huesped y entrega el enlace prellenado ──
$e = $servicio->ejecutarAccion($hotelId, 'finalizar_reserva', $accReserva, $usuarioId);
t_ok(!empty($e['success']), 'finalizar: success');
$nuevoHuesped = $db->query(
    "SELECT id, nombre_completo, telefono FROM huespedes WHERE hotel_id = ? AND nombre_completo = 'Pedro Ramirez' LIMIT 1",
    [$hotelId]
)->fetch();
t_ok(!empty($nuevoHuesped['id']), 'finalizar: huesped Pedro Ramirez registrado');
t_eq('5512345678', $nuevoHuesped['telefono'] ?? null, 'finalizar: telefono guardado');
$url = (string) ($e['acciones'][0]['url'] ?? '');
t_ok(strpos($url, 'huesped_id=' . (int) $nuevoHuesped['id']) !== false, 'finalizar: enlace con el huesped nuevo');
t_ok(strpos($url, 'fecha_entrada=' . $fr['entrada']) !== false, 'finalizar: enlace con las fechas');
t_ok(strpos($url, 'habitacion_id=') === false, 'finalizar: sin habitacion (se salto con "cualquiera")');

// ── Doble clic / repeticion: reusa al huesped, no duplica ──
$e = $servicio->ejecutarAccion($hotelId, 'finalizar_reserva', $accReserva, $usuarioId);
t_ok(!empty($e['success']), 'finalizar repetido: success');
$nPedros = (int) $db->query(
    "SELECT COUNT(*) c FROM huespedes WHERE hotel_id = ? AND nombre_completo = 'Pedro Ramirez'",
    [$hotelId]
)->fetch()['c'];
t_eq(1, $nPedros, 'finalizar repetido: sin duplicados');

// ── Cancelar a media captura ──
$r = $servicio->responder($hotelId, 'quiero hacer una reservacion', $usuarioId);
$r = $servicio->responder($hotelId, 'mejor cancelalo', $usuarioId, '', '', null, json_encode($r['flujo']));
t_eq('flujo:reserva_cancel', $r['intent'] ?? null, 'flujo: cancelar funciona');
t_ok(!empty($r['flujo_fin']), 'flujo: cancelar cierra el estado');

// ── One-shot con nombre dictado NO registrado: pide telefono directo ──
$r = $servicio->responder($hotelId, 'reservale la 204 a carlos gomez del 10 al 12 de noviembre', $usuarioId);
t_eq('flujo:reserva_telefono', $r['intent'] ?? null, 'one-shot nuevo: salta directo al telefono');
t_eq(1, $r['flujo']['nuevo'] ?? 0, 'one-shot nuevo: marcado nuevo');
t_eq($habitacionId, (int) ($r['flujo']['hab_id'] ?? 0), 'one-shot nuevo: habitacion ya capturada');

// ── Hotel sin el bloque promociones: la accion ni se propone ──
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Sin Promos', 'sin-promos', 1, NOW())");
$hotelB = (int) $db->lastInsertId();
$r = $servicio->responder($hotelB, 'crea un cupon de 10% para agosto', $usuarioId);
t_eq('accion:cupon_sin_bloque', $r['intent'] ?? null, 'sin bloque: avisa que no esta contratado');
t_ok(empty($r['accion']), 'sin bloque: sin payload de accion');
$r = $servicio->responder($hotelB, 'pagale 100 al proveedor garcia', $usuarioId);
t_eq('accion:pago_sin_bloque', $r['intent'] ?? null, 'sin bloque compras: el pago ni se propone');

// ── Sugerencias contextuales: siempre en respuestas terminales ──
$r = $servicio->responder($hotelId, 'como voy de caja', $usuarioId);
$sug = $r['sugerencias'] ?? [];
t_ok(count($sug) === 3, 'sugerencias: 3 chips tras una respuesta de datos');
t_ok(strpos((string) ($sug[0][1] ?? ''), 'gasto') !== false, 'sugerencias: tema dinero sugiere registrar gasto');
t_eq(1, (int) ($sug[0][2] ?? 0), 'sugerencias: la orden de gasto es plantilla (rellena, no envia)');

// En una PROPUESTA de accion o un paso del flujo no hay sugerencias (la
// siguiente jugada ya esta clara: confirmar o contestar).
$r = $servicio->responder($hotelId, 'registra un gasto de 100 de garrafones en mantenimiento', $usuarioId);
t_ok(!isset($r['sugerencias']), 'sugerencias: una propuesta de accion no trae chips');
$r = $servicio->responder($hotelId, 'quiero hacer una reservacion', $usuarioId);
t_ok(!empty($r['sugerencias']), 'sugerencias: un paso del wizard trae sus RESPUESTAS posibles (no el catalogo)');
t_eq('cancelar', $r['sugerencias'][count($r['sugerencias']) - 1][1] ?? null, 'sugerencias wizard: cierran con cancelar');

// ── Catalogo de capacidades: paginas core + bloques del hotel ──
$paginas = $servicio->catalogoCapacidades($hotelId);
$titulos = array_column($paginas, 'titulo');
t_ok(in_array('Acciones rápidas', $titulos, true), 'catalogo: pagina de acciones');
t_ok(in_array('Dinero', $titulos, true), 'catalogo: pagina de dinero');
t_ok(in_array('Tus bloques', $titulos, true), 'catalogo: pagina de bloques contratados');
$paginasB = $servicio->catalogoCapacidades($hotelB);
t_ok(!in_array('Tus bloques', array_column($paginasB, 'titulo'), true), 'catalogo: hotel sin bloques no ve esa pagina');

t_fin();
