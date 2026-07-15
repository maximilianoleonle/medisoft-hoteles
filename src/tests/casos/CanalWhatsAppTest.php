<?php
/**
 * Invariantes del canal WhatsApp Nivel 1 (bloque canal_whatsapp):
 *  1. normalizarTelefono: 10 digitos MX -> 521XXXXXXXXXX; tolera formatos;
 *     lo no usable es null (y el envio lo registra como descartado con motivo).
 *  2. componer llena TODAS las variables (sin llaves residuales), habla a
 *     nombre del hotel (white-label: jamas 'Medisoft') y reporta faltantes
 *     de configuracion en vez de producir mensajes rotos.
 *  3. La cola del dia y su contador usan el MISMO criterio, y cada envio o
 *     descarte saca al candidato de la cola (timeline unico por tipo).
 *  4. Todo scopeado por hotel: un hotel jamas ve ni toca mensajes de otro.
 *  5. CERO escrituras de dinero: el servicio solo escribe en mensajes_whatsapp
 *     (y reusa el token de encuesta idempotente del bloque reputacion).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CanalWhatsAppTest\n";

t_reset_db();

// El gate de encuesta pide el bloque reputacion; sembrado como core queda
// activo para todos los hoteles del test sin fila en hotel_modulos.
Database::getInstance()->query(
    "INSERT INTO modulos (clave, nombre, es_core, activo_global, orden, created_at)
     VALUES ('reputacion', 'Reputacion', 1, 1, 1, NOW())"
);

$base = t_seed_base('wa-a');
$hotelA = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();
$servicio = new CanalWhatsAppService();

// ── 1. normalizarTelefono ────────────────────────────────────────────────
t_eq('5219511234567', $servicio->normalizarTelefono('9511234567'), '10 digitos MX -> 521...');
t_eq('5219511234567', $servicio->normalizarTelefono('951 123-4567'), 'tolera espacios y guiones');
t_eq('5219511234567', $servicio->normalizarTelefono('+52 951 123 4567'), '+52 y 10 digitos -> 521...');
t_eq('5219511234567', $servicio->normalizarTelefono('5219511234567'), '521 + 10 digitos pasa tal cual');
t_eq('15551234567', $servicio->normalizarTelefono('+1 555 123 4567'), 'lada internacional completa pasa tal cual');
t_eq(null, $servicio->normalizarTelefono('12345'), 'telefono corto no es usable');
t_eq(null, $servicio->normalizarTelefono('52951123456'), '52 + 9 digitos (incompleto) no es usable');
t_eq(null, $servicio->normalizarTelefono(''), 'vacio no es usable');
t_eq(null, $servicio->normalizarTelefono(null), 'null no es usable');
t_ok(stripos($servicio->motivoTelefono(''), 'Sin teléfono') !== false, 'motivo humano para telefono vacio');
t_ok(stripos($servicio->motivoTelefono('12345'), 'incompleto') !== false, 'motivo humano para telefono corto');

// ── 2. linkWa codifica UTF-8/acentos ─────────────────────────────────────
$link = $servicio->linkWa('5219511234567', "¡Hola María! ¿Cómo estás?");
t_ok(strpos($link, 'https://wa.me/5219511234567?text=') === 0, 'linkWa apunta a wa.me con el telefono');
t_ok(strpos($link, '%C2%A1Hola%20Mar%C3%ADa') !== false, 'acentos y signos van URL-encoded (UTF-8)');
t_eq("¡Hola María! ¿Cómo estás?", rawurldecode(substr($link, strpos($link, '=') + 1)), 'el texto decodificado regresa identico');

// ── 3. Cola del dia: confirmacion + composicion white-label ─────────────
$resA = t_seed_reservacion($hotelA, 1500.00); // entrada hoy, confirmada, creada hoy
$db->query("UPDATE huespedes SET nombre_completo = 'María Fernanda López', telefono = '951 123-4567' WHERE hotel_id = ?", [$hotelA]);

$items = $servicio->pendientesDeHoy($hotelA);
$confirmaciones = array_values(array_filter($items, fn ($i) => $i['tipo'] === 'confirmacion' && $i['reservacion_id'] === $resA));
t_eq(1, count($confirmaciones), 'la reserva recien creada espera su confirmacion en la cola');

$item = $confirmaciones[0] ?? ['texto' => '', 'telefono_wa' => null, 'faltantes' => ['x']];
t_ok(strpos($item['texto'], 'María') !== false, 'el mensaje saluda por el primer nombre');
t_ok(strpos($item['texto'], 'Hotel Wa-a') !== false, 'el mensaje habla a nombre del hotel');
t_ok(stripos($item['texto'], 'medisoft') === false, 'white-label: jamas menciona la plataforma');
t_ok(strpos($item['texto'], '$1,500.00') !== false, 'el total viaja formateado');
t_ok(strpos($item['texto'], '{') === false, 'ninguna variable queda sin resolver');
t_ok(strpos($item['texto'], '3:00 pm') !== false, 'hora de check-in legible (default 15:00)');
t_eq('5219511234567', $item['telefono_wa'], 'telefono normalizado listo para wa.me');
t_eq([], $item['faltantes'], 'la confirmacion no exige configuracion extra');
t_eq(count($items), $servicio->contarPendientesHoy($hotelA), 'el contador y la cola usan el mismo criterio');

// ── 4. marcarEnviado: registra, produce link y saca de la cola ───────────
$antes = $servicio->contarPendientesHoy($hotelA);
$envio = $servicio->marcarEnviado($hotelA, $resA, 'confirmacion', $usuarioId);
t_ok(!empty($envio['success']), 'envio de confirmacion aceptado');
t_ok(strpos((string) ($envio['link'] ?? ''), 'https://wa.me/5219511234567?text=') === 0, 'el envio devuelve el link wa.me listo');

$fila = $db->query("SELECT estado, telefono, contenido, canal, enviado_por, enviado_en FROM mensajes_whatsapp WHERE hotel_id = ? AND reservacion_id = ? AND tipo = 'confirmacion'", [$hotelA, $resA])->fetch();
t_ok($fila && $fila['estado'] === 'enviado', 'la fila queda como enviado');
t_eq('manual', $fila['canal'] ?? '', "canal registrado como 'manual' (listo para cloud_api futuro)");
t_eq($usuarioId, (int) ($fila['enviado_por'] ?? 0), 'quien envio queda registrado');
t_ok(!empty($fila['enviado_en']), 'cuando se envio queda registrado');
t_ok(!empty($fila['contenido']) && strpos($fila['contenido'], 'María') !== false, 'el texto exacto enviado queda en el timeline');
t_eq($antes - 1, $servicio->contarPendientesHoy($hotelA), 'el envio saca al candidato de la cola');

$mapa = $servicio->porReservacion($hotelA, $resA);
t_eq('enviado', $mapa['confirmacion']['estado'] ?? '', 'porReservacion refleja el timeline');

$reDescartar = $servicio->descartar($hotelA, $resA, 'confirmacion', $usuarioId);
t_ok(empty($reDescartar['success']), 'un mensaje ya enviado NO puede descartarse');

// ── 5. Telefono basura: el registro nace descartado con motivo ───────────
$resBasura = t_seed_reservacion($hotelA, 800.00);
$db->query("UPDATE huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id SET h.telefono = '12345' WHERE r.id = ?", [$resBasura]);

$envioBasura = $servicio->marcarEnviado($hotelA, $resBasura, 'confirmacion', $usuarioId);
t_ok(empty($envioBasura['success']) && !empty($envioBasura['descartado']), 'telefono basura no se envia: nace descartado');
$filaBasura = $db->query("SELECT estado, motivo FROM mensajes_whatsapp WHERE hotel_id = ? AND reservacion_id = ? AND tipo = 'confirmacion'", [$hotelA, $resBasura])->fetch();
t_eq('descartado', $filaBasura['estado'] ?? '', 'fila descartada en el timeline');
t_ok(!empty($filaBasura['motivo']), 'el motivo del descarte queda visible');

// ── 6. Sin telefono: aparece en cola con motivo y puede descartarse ──────
$resSinTel = t_seed_reservacion($hotelA, 900.00);
$itemsSinTel = array_values(array_filter($servicio->pendientesDeHoy($hotelA), fn ($i) => $i['reservacion_id'] === $resSinTel));
t_eq(1, count($itemsSinTel), 'la reserva sin telefono igual espera accion en la cola');
t_ok(count($itemsSinTel) === 1 && $itemsSinTel[0]['telefono_wa'] === null && stripos($itemsSinTel[0]['motivo_telefono'] ?? '', 'Sin teléfono') !== false, 'la cola explica por que no se puede enviar');

$descarte = $servicio->descartar($hotelA, $resSinTel, 'confirmacion', $usuarioId);
t_ok(!empty($descarte['success']), 'descarte manual aceptado');
$filaDescarte = $db->query("SELECT estado, motivo FROM mensajes_whatsapp WHERE hotel_id = ? AND reservacion_id = ? AND tipo = 'confirmacion'", [$hotelA, $resSinTel])->fetch();
t_ok(($filaDescarte['estado'] ?? '') === 'descartado' && !empty($filaDescarte['motivo']), 'descarte con motivo en el timeline');

// ── 7. Anticipo: configuracion incompleta bloquea, completa compone ──────
$resAnt = t_seed_reservacion($hotelA, 2000.00);
$db->query("UPDATE huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id SET h.telefono = '9515550001' WHERE r.id = ?", [$resAnt]);

$tiposAnt = $servicio->tiposParaReservacion($hotelA, ['id' => $resAnt, 'estado' => 'confirmada', 'precio_total' => 2000.00]);
t_ok(in_array('anticipo', $tiposAnt, true), 'reserva confirmada con saldo ofrece anticipo');

$envioAnt = $servicio->marcarEnviado($hotelA, $resAnt, 'anticipo', $usuarioId);
t_ok(empty($envioAnt['success']) && in_array('datos_deposito', $envioAnt['faltantes'] ?? [], true), 'sin datos de deposito el anticipo NO sale (configuracion incompleta)');
$sinFila = $db->query("SELECT COUNT(*) AS n FROM mensajes_whatsapp WHERE hotel_id = ? AND reservacion_id = ? AND tipo = 'anticipo'", [$hotelA, $resAnt])->fetch();
t_eq(0, (int) ($sinFila['n'] ?? -1), 'configuracion incompleta no escribe nada en el timeline');
t_ok(in_array('datos_deposito', $servicio->configuracionFaltante($hotelA), true), 'configuracionFaltante detecta datos_deposito');

// Hotel C con configuracion completa desde el inicio (el Registry cachea por hotel).
$baseC = t_seed_base('wa-c');
$hotelC = $baseC['hotel_id'];
foreach ([
    ['canal_whatsapp.datos_deposito', "BBVA 0123456789\nCLABE 012345678901234567", 'string'],
    ['canal_whatsapp.link_maps', 'https://maps.app.goo.gl/hotelwac', 'string'],
] as [$clave, $valor, $tipoCfg]) {
    $db->query(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'canal_whatsapp', 1, NOW(), NOW())",
        [$hotelC, $clave, $valor, $tipoCfg]
    );
}

$resC = t_seed_reservacion($hotelC, 1000.00);
$db->query("UPDATE huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id SET h.telefono = '9515550002' WHERE r.id = ?", [$resC]);

$envioAntC = $servicio->marcarEnviado($hotelC, $resC, 'anticipo', $usuarioId);
t_ok(!empty($envioAntC['success']), 'con datos de deposito capturados el anticipo sale');
t_ok(strpos((string) ($envioAntC['texto'] ?? ''), 'CLABE 012345678901234567') !== false, 'las instrucciones de deposito viajan en el mensaje');
t_ok(strpos((string) ($envioAntC['texto'] ?? ''), '$300.00') !== false, 'anticipo sugerido = 30% del total (regla del hotel), topado a saldo');
t_eq([], $servicio->configuracionFaltante($hotelC), 'hotel C no debe configuracion');

// ── 8. Recordatorio: llegada de manana ───────────────────────────────────
$resM = t_seed_reservacion($hotelC, 500.00);
$db->query("UPDATE reservaciones SET fecha_entrada = CURDATE() + INTERVAL 1 DAY, fecha_salida = CURDATE() + INTERVAL 3 DAY WHERE id = ?", [$resM]);
$db->query("UPDATE huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id SET h.telefono = '9515550003' WHERE r.id = ?", [$resM]);

$recordatorios = array_values(array_filter($servicio->pendientesDeHoy($hotelC), fn ($i) => $i['tipo'] === 'recordatorio' && $i['reservacion_id'] === $resM));
t_eq(1, count($recordatorios), 'la llegada de manana espera su recordatorio');

$envioRec = $servicio->marcarEnviado($hotelC, $resM, 'recordatorio', $usuarioId);
t_ok(!empty($envioRec['success']), 'recordatorio sale con link de Maps capturado');
t_ok(strpos((string) ($envioRec['texto'] ?? ''), 'https://maps.app.goo.gl/hotelwac') !== false, 'el recordatorio lleva el link de Maps');

// ── 9. Encuesta: salida de hoy con checkout, link del bloque reputacion ──
$resE = t_seed_reservacion($hotelC, 700.00);
$db->query("UPDATE reservaciones SET estado = 'checked_out', fecha_entrada = CURDATE() - INTERVAL 2 DAY, fecha_salida = CURDATE() WHERE id = ?", [$resE]);
$db->query("UPDATE huespedes h INNER JOIN reservaciones r ON r.huesped_id = h.id SET h.telefono = '9515550004' WHERE r.id = ?", [$resE]);

$tiposE = $servicio->tiposParaReservacion($hotelC, ['id' => $resE, 'estado' => 'checked_out', 'precio_total' => 700.00]);
t_eq(['encuesta'], $tiposE, 'tras el checkout solo aplica la encuesta');

$encuestas = array_values(array_filter($servicio->pendientesDeHoy($hotelC), fn ($i) => $i['tipo'] === 'encuesta' && $i['reservacion_id'] === $resE));
t_eq(1, count($encuestas), 'la salida de hoy espera su encuesta');

$envioEnc = $servicio->marcarEnviado($hotelC, $resE, 'encuesta', $usuarioId);
t_ok(!empty($envioEnc['success']), 'la encuesta sale');
t_ok(preg_match('#/encuesta/[a-f0-9]{32}#', (string) ($envioEnc['texto'] ?? '')) === 1, 'el mensaje lleva el link publico con token del bloque reputacion');
$tokenFila = $db->query("SELECT COUNT(*) AS n FROM reputacion_encuestas WHERE hotel_id = ? AND reservacion_id = ?", [$hotelC, $resE])->fetch();
t_eq(1, (int) ($tokenFila['n'] ?? 0), 'el token reusa el mecanismo idempotente de reputacion');

// ── 10. Tenancy: un hotel jamas ve ni toca lo del otro ───────────────────
$idsColaA = array_map(fn ($i) => $i['reservacion_id'], $servicio->pendientesDeHoy($hotelA));
t_ok(!in_array($resC, $idsColaA, true) && !in_array($resM, $idsColaA, true), 'la cola del hotel A no muestra reservas del hotel C');

$cruce = $servicio->marcarEnviado($hotelC, $resAnt, 'confirmacion', $usuarioId);
t_ok(empty($cruce['success']) && stripos((string) ($cruce['motivo'] ?? ''), 'no encontrada') !== false, 'enviar sobre una reserva de otro hotel se rechaza');

$historialC = $servicio->historial($hotelC);
$historialA = $servicio->historial($hotelA);
t_ok(count($historialC) > 0 && count($historialA) > 0, 'cada hotel tiene su historial');
t_ok(!in_array($resAnt, array_map(fn ($f) => (int) $f['reservacion_id'], $historialC), true), 'el historial del hotel C no incluye reservas del hotel A');

// ── 11. CERO dinero: el servicio solo escribio en mensajes_whatsapp ──────
// (se cuenta por hotel del test: medisoft_test es compartida y otra suite
//  corriendo en paralelo puede tener movimientos propios legitimos)
foreach (['movimientos_caja', 'reservacion_abonos', 'reservacion_pagos'] as $tablaDinero) {
    $n = $db->query("SELECT COUNT(*) AS n FROM {$tablaDinero} WHERE hotel_id IN (?, ?)", [$hotelA, $hotelC])->fetch();
    t_eq(0, (int) ($n['n'] ?? -1), "jamas escribe en {$tablaDinero}");
}

t_fin();
