<?php
/**
 * "¿Todo bien?" durante la estancia (tipo 'seguimiento' del canal WhatsApp).
 *
 * Lo que se fija aqui:
 *  1. Entra a la cola SOLO la manana siguiente a la llegada y mientras el
 *     huesped aun se queda otra noche (checked_in, entrada < hoy < salida).
 *     Llegar hoy, salir hoy o no haber llegado NO califica.
 *  2. Con el QR en la habitacion (bloque reputacion) el mensaje lleva el link
 *     de SU habitacion; sin el bloque, la linea del enlace desaparece entera
 *     (no queda "{link_estancia}" ni una frase colgando).
 *  3. Se apaga desde la configuracion como los demas tipos.
 *  4. Enviarlo solo se permite con el huesped hospedado; un hotel no toca
 *     reservas de otro.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_PATH . '/services/CanalWhatsAppService.php';

echo "SeguimientoWhatsAppTest\n";

t_reset_db();

$db = Database::getInstance();

// reputacion NO es core aqui: el hotel A lo contrata y el B no, para ver las
// dos caras del mensaje (con y sin el QR de la habitacion).
$db->query(
    "INSERT INTO modulos (clave, nombre, es_core, tipo_comercial, activo_global, orden, created_at)
     VALUES ('reputacion', 'Reputacion', 0, 'opcional', 1, 1, NOW())"
);
$moduloReputacion = (int) $db->lastInsertId();

$baseA = t_seed_base('seg-a');
$hotelA = $baseA['hotel_id'];
$usuarioA = $baseA['usuario_id'];
$db->query("INSERT INTO hotel_modulos (hotel_id, modulo_id, activo) VALUES (?, ?, 1)", [$hotelA, $moduloReputacion]);

$baseB = t_seed_base('seg-b');
$hotelB = $baseB['hotel_id'];

$habitacion = function (int $hotel, string $numero) use ($db): int {
    $db->query(
        "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
         VALUES (?, ?, 'doble', 2, 900, 'ocupada', 1, NOW())",
        [$hotel, $numero]
    );
    return (int) $db->lastInsertId();
};

/** Reservacion con fechas relativas a HOY (del reloj de la misma conexion). */
$reservar = function (int $hotel, int $habitacionId, string $estado, int $entradaDias, int $salidaDias, string $nombre) use ($db): int {
    $db->query(
        "INSERT INTO huespedes (hotel_id, nombre_completo, telefono, created_at) VALUES (?, ?, '9511234567', NOW())",
        [$hotel, $nombre]
    );
    $huespedId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
         VALUES (?, ?, CURDATE() + INTERVAL ? DAY, CURDATE() + INTERVAL ? DAY, 1800, ?, NOW() - INTERVAL 10 DAY)",
        [$hotel, $huespedId, $entradaDias, $salidaDias, $estado]
    );
    $reservacionId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio) VALUES (?, ?, ?, 900)",
        [$hotel, $reservacionId, $habitacionId]
    );
    return $reservacionId;
};

$servicio = new CanalWhatsAppService();

$seguimientoDe = function (int $hotel, int $reservacionId) use ($servicio): ?array {
    foreach ($servicio->pendientesDeHoy($hotel) as $item) {
        if ($item['tipo'] === 'seguimiento' && $item['reservacion_id'] === $reservacionId) {
            return $item;
        }
    }
    return null;
};

$h201 = $habitacion($hotelA, '201');
$h202 = $habitacion($hotelA, '202');
$h203 = $habitacion($hotelA, '203');
$h204 = $habitacion($hotelA, '204');
$h301 = $habitacion($hotelB, '301');

$enCurso = $reservar($hotelA, $h201, 'checked_in', -1, 2, 'Rosa Martinez');
$llegoHoy = $reservar($hotelA, $h202, 'checked_in', 0, 3, 'Luis Perez');
$saleHoy = $reservar($hotelA, $h203, 'checked_in', -2, 0, 'Ana Ruiz');
$noLlego = $reservar($hotelA, $h204, 'confirmada', -1, 2, 'Carlos Diaz');
$enCursoB = $reservar($hotelB, $h301, 'checked_in', -1, 2, 'Eva Soto');

// ── 1. Quien entra a la cola ─────────────────────────────────────────────
t_ok(in_array('seguimiento', CanalWhatsAppService::TIPOS_COLA, true), 'seguimiento es un tipo de la cola del dia');
t_ok($seguimientoDe($hotelA, $enCurso) !== null, 'llego ayer y se queda: recibe el "¿todo bien?"');
t_eq(null, $seguimientoDe($hotelA, $llegoHoy), 'quien llego hoy todavia no');
t_eq(null, $seguimientoDe($hotelA, $saleHoy), 'quien sale hoy ya no (para eso esta la encuesta)');
t_eq(null, $seguimientoDe($hotelA, $noLlego), 'una reservacion que no hizo check-in no');
t_eq(null, $seguimientoDe($hotelA, $enCursoB), 'la cola del hotel A no trae huespedes del B');

// ── 2. Con el QR: lleva el link de SU habitacion ─────────────────────────
$item = $seguimientoDe($hotelA, $enCurso) ?? ['texto' => ''];
t_ok(strpos($item['texto'], 'Rosa') !== false, 'saluda por el primer nombre');
t_ok(strpos($item['texto'], '201') !== false, 'menciona su habitacion');
$token201 = (string) $db->query("SELECT token FROM estancia_qr WHERE hotel_id = ? AND habitacion_id = ?", [$hotelA, $h201])->fetchColumn();
t_ok($token201 !== '' && strpos($item['texto'], '/h/seg-a/estancia/' . $token201) !== false, 'el link apunta al QR de la habitacion 201 de este hotel');
t_ok(strpos($item['texto'], '{') === false, 'ninguna variable queda sin resolver');
t_eq([], $item['faltantes'] ?? ['x'], 'no exige configuracion extra');

// ── 3. Sin el bloque: la linea del enlace se cae entera ──────────────────
$itemB = $seguimientoDe($hotelB, $enCursoB) ?? ['texto' => ''];
t_ok($itemB['texto'] !== '', 'sin el QR el mensaje igual sale');
t_ok(strpos($itemB['texto'], 'estancia/') === false, 'sin el bloque no hay link del QR');
t_ok(strpos($itemB['texto'], 'desde este enlace') === false, 'y no queda la frase del enlace colgando');
t_ok(strpos($itemB['texto'], '{') === false, 'ni variables sin resolver');
t_ok(strpos($itemB['texto'], 'respóndenos por aquí') !== false, 'conserva la invitacion a responder por WhatsApp');
t_eq(0, (int) $db->query("SELECT COUNT(*) FROM estancia_qr WHERE hotel_id = ?", [$hotelB])->fetchColumn(), 'sin el bloque no se crean QR');

t_eq("Hola\nAdios", CanalWhatsAppService::quitarLineasCon("Hola\nquita {x} esto\nAdios", '{x}'), 'quitarLineasCon quita solo las lineas con el marcador');

// ── 4. Enviar: solo con el huesped hospedado ─────────────────────────────
$envio = $servicio->marcarEnviado($hotelA, $enCurso, 'seguimiento', $usuarioA);
t_ok(!empty($envio['success']), 'el "¿todo bien?" se envia');
t_ok(strpos((string) ($envio['link'] ?? ''), 'https://wa.me/5219511234567?text=') === 0, 'con su link wa.me');
t_eq(null, $seguimientoDe($hotelA, $enCurso), 'enviado, sale de la cola');
$fila = $db->query("SELECT estado FROM mensajes_whatsapp WHERE hotel_id = ? AND reservacion_id = ? AND tipo = 'seguimiento'", [$hotelA, $enCurso])->fetch();
t_eq('enviado', $fila['estado'] ?? '', 'el timeline guarda el tipo seguimiento (el enum lo acepta)');

$noAplica = $servicio->marcarEnviado($hotelA, $noLlego, 'seguimiento', $usuarioA);
t_ok(empty($noAplica['success']), 'a quien no ha llegado no se le puede mandar');
$cruzado = $servicio->marcarEnviado($hotelA, $enCursoB, 'seguimiento', $usuarioA);
t_ok(empty($cruzado['success']), 'el hotel A no manda sobre una reservacion del B');

$tipos = $servicio->tiposParaReservacion($hotelA, ['id' => $enCurso, 'estado' => 'checked_in', 'precio_total' => 1800]);
t_eq(['seguimiento'], $tipos, 'con el huesped hospedado, la ficha solo ofrece el "¿todo bien?"');

// ── 5. Se apaga desde la configuracion ───────────────────────────────────
$db->query(
    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
     VALUES (?, 'canal_whatsapp.seguimiento_activo', '0', 'boolean', 'canal_whatsapp', 1, NOW(), NOW())",
    [$hotelB]
);
ConfiguracionHotelRegistry::limpiarCache();
t_eq(null, $seguimientoDe($hotelB, $enCursoB), 'apagado, desaparece de la cola');
t_ok(empty($servicio->tiposActivos($hotelB)['seguimiento']), 'y de los tipos activos del hotel');

t_fin();
