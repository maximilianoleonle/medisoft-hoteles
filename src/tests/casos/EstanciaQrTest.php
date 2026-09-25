<?php
/**
 * QR en la habitacion + peticiones del huesped (EstanciaService).
 *
 * Lo que se fija aqui:
 *  1. Token por habitacion: 32 hex, idempotente, rotable (el viejo muere) y
 *     JAMAS cruza de hotel (ni para leerlo ni para crearlo).
 *  2. El QR solo acepta peticiones con una reservacion en CHECK-IN: una
 *     confirmada sin llegar, o el cuarto vacio, no pueden pedir nada.
 *  3. Frenos: doble toque identico no duplica, tope por hora por habitacion,
 *     peticiones apagadas por el hotel, detalle obligatorio donde hace falta.
 *  4. La foto es opcional y nunca tumba la peticion; se guarda privada y solo
 *     se lee desde su hotel.
 *  5. La pagina publica solo ve las peticiones de la estancia ACTUAL (el
 *     huesped anterior del cuarto no se asoma).
 *  6. Bandeja: transiciones condicionales (dos personas no se pisan), cierre
 *     del aviso de la campana y aislamiento por hotel.
 *  7. Nada de esto toca el estado de la habitacion.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_PATH . '/services/EstanciaService.php';

echo "EstanciaQrTest\n";

t_reset_db();

$db = Database::getInstance();

$db->query(
    "INSERT INTO modulos (clave, nombre, es_core, activo_global, orden, created_at)
     VALUES ('reputacion', 'Reputacion', 1, 1, 1, NOW()),
            ('notificaciones', 'Notificaciones', 1, 1, 2, NOW())"
);

$baseA = t_seed_base('qr-a');
$hotelA = $baseA['hotel_id'];
$usuarioA = $baseA['usuario_id'];
$baseB = t_seed_base('qr-b');
$hotelB = $baseB['hotel_id'];

$habitacion = function (int $hotel, string $numero) use ($db): int {
    $db->query(
        "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
         VALUES (?, ?, 'sencilla', 1, 800, 'disponible', 1, NOW())",
        [$hotel, $numero]
    );
    return (int) $db->lastInsertId();
};

/** Reservacion ligada a la habitacion, en el estado pedido. */
$reservar = function (int $hotel, int $habitacionId, string $estado, string $nombre = 'Huesped Test') use ($db): int {
    $db->query(
        "INSERT INTO huespedes (hotel_id, nombre_completo, telefono, created_at) VALUES (?, ?, '5512345678', NOW())",
        [$hotel, $nombre]
    );
    $huespedId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
         VALUES (?, ?, CURDATE() - INTERVAL 1 DAY, CURDATE() + INTERVAL 2 DAY, 1600, ?, NOW())",
        [$hotel, $huespedId, $estado]
    );
    $reservacionId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio) VALUES (?, ?, ?, 800)",
        [$hotel, $reservacionId, $habitacionId]
    );
    return $reservacionId;
};

$setConfig = function (int $hotel, string $clave, string $valor, string $tipo = 'string') use ($db): void {
    $db->query(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'estancia', 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
        [$hotel, $clave, $valor, $tipo]
    );
    ConfiguracionHotelRegistry::limpiarCache();
};

$contar = function (string $sql, array $params = []) use ($db): int {
    $stmt = $db->query($sql, $params);
    return (int) $stmt->fetchColumn();
};

$h101 = $habitacion($hotelA, '101');
$h102 = $habitacion($hotelA, '102');
$h103 = $habitacion($hotelA, '103');
$h201 = $habitacion($hotelB, '201');

$storage = sys_get_temp_dir() . '/estancia_qr_test_' . bin2hex(random_bytes(4));
mkdir($storage, 0750, true);

/** Sustituye move_uploaded_file (solo existe con subidas HTTP reales). */
$servicio = new class ($db, $storage) extends EstanciaService {
    protected function moverArchivo(string $origen, string $destino): bool
    {
        return copy($origen, $destino);
    }
};

// ── 1. Tokens por habitacion ─────────────────────────────────────────────
$t101 = $servicio->tokenHabitacion($hotelA, $h101, true, $usuarioA);
t_ok(is_string($t101) && EstanciaService::tokenValido($t101), 'el token de la habitacion es de 32 hex');
t_eq($t101, $servicio->tokenHabitacion($hotelA, $h101), 'pedir el token otra vez devuelve el mismo (idempotente)');
t_eq(null, $servicio->tokenHabitacion($hotelA, $h201), 'hotel A NO puede crear el QR de una habitacion del hotel B');
t_eq(null, $servicio->tokenHabitacion($hotelA, $h102, false), 'sin $crear no se inventa un token');

$lista = $servicio->habitacionesConToken($hotelA, true, $usuarioA);
t_eq(3, count($lista), 'la hoja de impresion trae las 3 habitaciones activas del hotel A');
t_ok(count(array_filter($lista, static function ($f) { return !empty($f['token']); })) === 3, 'toda habitacion activa sale con token');
t_eq(0, $contar("SELECT COUNT(*) FROM estancia_qr WHERE hotel_id = ?", [$hotelB]), 'imprimir los QR del hotel A no crea nada en el hotel B');

$encontrada = $servicio->porToken($hotelA, $t101);
t_eq('101', (string) ($encontrada['numero'] ?? ''), 'el token lleva a su habitacion');
t_eq(null, $servicio->porToken($hotelB, $t101), 'el token del hotel A no se lee desde el hotel B');
t_eq(null, $servicio->porToken($hotelA, 'no-es-un-token'), 'un token mal formado no consulta nada');

$nuevo = $servicio->regenerarToken($hotelA, $h101, $usuarioA);
t_ok($nuevo !== null && $nuevo !== $t101, 'regenerar entrega un token distinto');
t_eq(null, $servicio->porToken($hotelA, $t101), 'el QR viejo deja de funcionar al regenerar');
t_eq('101', (string) ($servicio->porToken($hotelA, (string) $nuevo)['numero'] ?? ''), 'el QR nuevo si funciona');
t_eq(null, $servicio->regenerarToken($hotelB, $h101), 'el hotel B no puede regenerar el QR del hotel A');
$t101 = (string) $nuevo;

$db->query("UPDATE habitaciones SET activa = 0 WHERE id = ?", [$h103]);
$t103 = $servicio->tokenHabitacion($hotelA, $h103, false);
t_eq(null, $servicio->porToken($hotelA, (string) $t103), 'una habitacion dada de baja ya no responde a su QR');
$db->query("UPDATE habitaciones SET activa = 1 WHERE id = ?", [$h103]);

// ── 2. QR en SVG ─────────────────────────────────────────────────────────
$svg = EstanciaService::qrSvg('https://ejemplo.test/h/qr-a/estancia/' . $t101, 'QR 101');
t_ok(strpos($svg, '<svg') === 0 && strpos($svg, 'viewBox="0 0 ') !== false, 'qrSvg entrega un SVG con viewBox');
t_ok(preg_match('/<path d="M\d+ \d+h\d+v1h-\d+z/', $svg) === 1, 'los modulos van en un solo path de corridas');
t_eq($svg, EstanciaService::qrSvg('https://ejemplo.test/h/qr-a/estancia/' . $t101, 'QR 101'), 'el mismo link produce el mismo QR');
t_ok($svg !== EstanciaService::qrSvg('https://ejemplo.test/h/qr-a/estancia/' . str_repeat('0', 32), 'QR 101'), 'otro link produce otro QR');
t_ok(strlen($svg) < 12000, 'el SVG es ligero (' . strlen($svg) . ' bytes)');
t_ok(strpos(EstanciaService::qrSvg('x', '<b>"'), '&lt;b&gt;&quot;') !== false, 'la etiqueta del QR sale escapada');

// ── 3. Solo con huesped en check-in ──────────────────────────────────────
$r = $servicio->crearPeticion($hotelA, $h101, 'toallas', '');
t_eq('sin_estancia', $r['codigo'] ?? null, 'cuarto vacio: el QR no acepta peticiones');

$reservaConfirmada = $reservar($hotelA, $h101, 'confirmada');
$r = $servicio->crearPeticion($hotelA, $h101, 'toallas', '');
t_eq('sin_estancia', $r['codigo'] ?? null, 'una reservacion confirmada que no ha llegado no puede pedir');

$db->query("UPDATE reservaciones SET estado = 'checked_out' WHERE id = ?", [$reservaConfirmada]);
$reservaAnterior = $reservaConfirmada;
$db->query(
    "INSERT INTO estancia_peticiones (hotel_id, habitacion_id, reservacion_id, tipo, estado, created_at)
     VALUES (?, ?, ?, 'toallas', 'resuelta', NOW() - INTERVAL 3 DAY)",
    [$hotelA, $h101, $reservaAnterior]
);
$idPeticionAnterior = (int) $db->lastInsertId();

$reservaActual = $reservar($hotelA, $h101, 'checked_in', 'Ana Lopez');
$estancia = $servicio->estanciaActiva($hotelA, $h101);
t_eq($reservaActual, (int) ($estancia['reservacion_id'] ?? 0), 'la estancia activa es la reservacion en check-in');
t_eq(null, $servicio->estanciaActiva($hotelB, $h101), 'la estancia no se lee desde otro hotel');

// ── 4. Crear peticiones y sus frenos ─────────────────────────────────────
t_eq('tipo', $servicio->crearPeticion($hotelA, $h101, 'pizza', '')['codigo'] ?? null, 'un tipo desconocido se rechaza');
t_eq('detalle', $servicio->crearPeticion($hotelA, $h101, 'mantenimiento', ' ')['codigo'] ?? null, '"Algo no funciona" exige decir que');
t_eq('detalle', $servicio->crearPeticion($hotelA, $h101, 'otro', 'x')['codigo'] ?? null, '"Otra peticion" exige texto');

$estadoAntes = (string) $db->query("SELECT estado FROM habitaciones WHERE id = ?", [$h101])->fetchColumn();
$r = $servicio->crearPeticion($hotelA, $h101, 'toallas', "  2 toallas\x07 de baño  ", null, '10.0.0.7');
t_eq('ok', $r['codigo'] ?? null, 'con huesped en check-in la peticion entra');
$idToallas = (int) ($r['id'] ?? 0);
$fila = $db->query("SELECT * FROM estancia_peticiones WHERE id = ?", [$idToallas])->fetch();
t_eq('nueva', $fila['estado'] ?? null, 'nace como nueva');
t_eq($reservaActual, (int) ($fila['reservacion_id'] ?? 0), 'queda ligada a la reservacion en check-in');
t_ok((int) ($fila['huesped_id'] ?? 0) > 0, 'queda ligada al huesped (solo para el personal)');
t_eq('2 toallas de baño', $fila['detalle'] ?? null, 'el detalle se limpia de controles y espacios');
t_eq(hash('sha256', 'estancia|10.0.0.7'), $fila['origen_ip_hash'] ?? null, 'la IP se guarda solo como hash');
t_eq($estadoAntes, (string) $db->query("SELECT estado FROM habitaciones WHERE id = ?", [$h101])->fetchColumn(), 'pedir algo NO cambia el estado de la habitacion');

$aviso = $db->query(
    "SELECT rol_destino, severidad, estado, url FROM notificaciones WHERE hotel_id = ? AND dedupe_key = ?",
    [$hotelA, 'estancia_peticion_' . $idToallas]
)->fetch();
t_eq('limpieza,recepcionista,gerente', $aviso['rol_destino'] ?? null, 'toallas avisa a limpieza, recepcion y gerente');
t_eq('peticiones', $aviso['url'] ?? null, 'el aviso lleva a la bandeja de peticiones');

// Quien lo ve de verdad: la bandeja filtra rol_destino por TEXTO, asi que se
// pregunta al filtro real del modelo (lista SQL y apertura en PHP), no al campo.
$notifModel = new Notificacion();
$loVe = function (string $rol, string $dedupe) use ($notifModel, $hotelA): bool {
    foreach ($notifModel->listarPorHotel($hotelA, ['rol_usuario' => $rol], 200) as $n) {
        if (($n['dedupe_key'] ?? '') === $dedupe) {
            return $notifModel->visibleParaUsuario($n, $rol, null);
        }
    }
    return false;
};
$dedupeToallas = 'estancia_peticion_' . $idToallas;
t_ok($loVe('limpieza', $dedupeToallas), 'la camarista ve la peticion de toallas');
t_ok($loVe('recepcionista', $dedupeToallas), 'recepcion la ve (hotel sin cuentas de camarista)');
t_ok($loVe('gerente', $dedupeToallas), 'el gerente la ve');
t_ok(!$loVe('mantenimiento', $dedupeToallas), 'mantenimiento no recibe toallas');

$r = $servicio->crearPeticion($hotelA, $h101, 'toallas', '2 toallas de baño');
t_eq('duplicada', $r['codigo'] ?? null, 'el doble toque identico no duplica');
t_eq($idToallas, (int) ($r['id'] ?? 0), 'el doble toque devuelve la peticion original');
t_ok(!empty($r['success']), 'el doble toque se le muestra al huesped como exito');

$r = $servicio->crearPeticion($hotelA, $h101, 'toallas', 'y una almohada extra');
t_eq('ok', $r['codigo'] ?? null, 'la misma categoria con otro texto SI es otra peticion');

$r = $servicio->crearPeticion($hotelA, $h101, 'mantenimiento', 'No sale agua caliente');
t_eq('ok', $r['codigo'] ?? null, '"Algo no funciona" con detalle entra');
$aviso = $db->query(
    "SELECT rol_destino, severidad FROM notificaciones WHERE hotel_id = ? AND dedupe_key = ?",
    [$hotelA, 'estancia_peticion_' . (int) $r['id']]
)->fetch();
t_eq('mantenimiento,recepcionista,gerente', $aviso['rol_destino'] ?? null, 'algo que no funciona avisa a mantenimiento, recepcion y gerente');
$dedupeFalla = 'estancia_peticion_' . (int) $r['id'];
t_ok($loVe('mantenimiento', $dedupeFalla), 'mantenimiento ve la falla');
t_ok($loVe('gerente', $dedupeFalla), 'el gerente ve la falla');
t_ok(!$loVe('limpieza', $dedupeFalla), 'limpieza no recibe fallas');
t_eq('recepcionista,gerente', EstanciaService::rolesAviso('otro'), '"otra cosa" va a recepcion y gerente, sin repetir roles');
t_ok(strlen(EstanciaService::rolesAviso('mantenimiento')) <= 40, 'rol_destino cabe en los 40 caracteres que guarda el modelo');
t_eq('alta', $aviso['severidad'] ?? null, 'y con severidad alta (llega aunque el push sea solo prioritario)');

$setConfig($hotelA, 'estancia.peticiones_activas', '0', 'boolean');
t_eq('desactivadas', $servicio->crearPeticion($hotelA, $h101, 'limpieza', '')['codigo'] ?? null, 'con peticiones apagadas el QR solo informa');
$setConfig($hotelA, 'estancia.peticiones_activas', '1', 'boolean');

$reservaB = $reservar($hotelB, $h201, 'checked_in');
t_eq('sin_estancia', $servicio->crearPeticion($hotelA, $h201, 'toallas', '')['codigo'] ?? null, 'el hotel A no puede pedir para un cuarto del hotel B');

// Tope por hora: ya hay 3 en la hora; se llena hasta el tope.
$enLaHora = $contar(
    "SELECT COUNT(*) FROM estancia_peticiones WHERE hotel_id = ? AND habitacion_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR",
    [$hotelA, $h101]
);
for ($i = $enLaHora; $i < EstanciaService::MAX_POR_HORA_HABITACION; $i++) {
    $servicio->crearPeticion($hotelA, $h101, 'otro', 'peticion de relleno ' . $i);
}
$r = $servicio->crearPeticion($hotelA, $h101, 'otro', 'una mas');
t_eq('limite', $r['codigo'] ?? null, 'pasado el tope por hora la habitacion ya no manda mas');
t_eq(
    EstanciaService::MAX_POR_HORA_HABITACION,
    $contar("SELECT COUNT(*) FROM estancia_peticiones WHERE hotel_id = ? AND habitacion_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR", [$hotelA, $h101]),
    'el tope no deja pasar ni una de mas'
);

// ── 5. Foto opcional, privada y por hotel ────────────────────────────────
$reserva102 = $reservar($hotelA, $h102, 'checked_in');
$png = tempnam(sys_get_temp_dir(), 'qrfoto');
$im = imagecreatetruecolor(8, 8);
imagepng($im, $png);
imagedestroy($im);

$r = $servicio->crearPeticion($hotelA, $h102, 'mantenimiento', 'La TV no prende', [
    'name' => 'tv.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => filesize($png),
]);
t_eq('ok', $r['codigo'] ?? null, 'la peticion con foto entra');
$idTv = (int) ($r['id'] ?? 0);
$ruta = (string) $db->query("SELECT foto_path FROM estancia_peticiones WHERE id = ?", [$idTv])->fetchColumn();
t_ok(strpos($ruta, 'estancia_fotos/hotel_' . $hotelA . '/') === 0, 'la foto vive en el storage privado del hotel');
t_ok(is_file($storage . '/' . $ruta), 'el archivo quedo en disco');
$foto = $servicio->foto($hotelA, $idTv);
t_eq('image/png', $foto['mime'] ?? null, 'el personal la lee con su tipo real');
t_eq(null, $servicio->foto($hotelB, $idTv), 'la foto no se lee desde otro hotel');

$txt = tempnam(sys_get_temp_dir(), 'qrtxt');
file_put_contents($txt, "no soy imagen\n");
$r = $servicio->crearPeticion($hotelA, $h102, 'mantenimiento', 'El foco del baño', [
    'name' => 'x.jpg', 'type' => 'image/jpeg', 'tmp_name' => $txt, 'error' => UPLOAD_ERR_OK, 'size' => filesize($txt),
]);
t_eq('ok_sin_foto', $r['codigo'] ?? null, 'una foto que no es imagen NO tumba la peticion');
t_eq(null, $db->query("SELECT foto_path FROM estancia_peticiones WHERE id = ?", [(int) $r['id']])->fetchColumn() ?: null, 'y la peticion queda sin foto');

$r = $servicio->crearPeticion($hotelA, $h102, 'mantenimiento', 'Gotea la regadera', [
    'name' => 'grande.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0,
]);
t_eq('ok_sin_foto', $r['codigo'] ?? null, 'una foto demasiado pesada tampoco tumba la peticion');

$r = $servicio->crearPeticion($hotelA, $h102, 'toallas', 'con foto que no aplica', [
    'name' => 'tv.png', 'type' => 'image/png', 'tmp_name' => $png, 'error' => UPLOAD_ERR_OK, 'size' => filesize($png),
]);
t_eq('ok', $r['codigo'] ?? null, 'una peticion sin foto permitida ignora el archivo');
t_eq(null, $db->query("SELECT foto_path FROM estancia_peticiones WHERE id = ?", [(int) $r['id']])->fetchColumn() ?: null, 'y no lo guarda');

// ── 6. Lo que ve el huesped: solo SU estancia ────────────────────────────
$propias = $servicio->peticionesDeEstancia($hotelA, $h101, $reservaActual, 30);
t_ok(count($propias) >= 3, 'el huesped ve sus peticiones de esta estancia');
t_ok(!in_array($idPeticionAnterior, array_map(static function ($p) { return (int) $p['id']; }, $propias), true), 'no ve nada de la estancia anterior del cuarto');
t_ok(!array_key_exists('detalle', $propias[0] ?? []), 'la vista publica no trae textos');
t_eq([], $servicio->peticionesDeEstancia($hotelB, $h101, $reservaActual), 'ni se leen desde otro hotel');

// ── 7. Bandeja y estados ─────────────────────────────────────────────────
$servicio->crearPeticion($hotelB, $h201, 'limpieza', '');
$abiertasA = $servicio->bandeja($hotelA, 'abiertas');
t_ok(count($abiertasA) > 0, 'la bandeja lista las peticiones abiertas');
t_ok(count(array_filter($abiertasA, static function ($p) use ($h201) { return (int) $p['habitacion_id'] === $h201; })) === 0, 'la bandeja del hotel A no trae nada del hotel B');
t_eq('Ana Lopez', (string) ($abiertasA[0]['huesped_nombre'] ?? ''), 'el personal si ve quien lo pidio');

t_eq('en_atencion', EstanciaService::transicion('nueva', 'atender'), 'nueva -> atender = en atencion');
t_eq(null, EstanciaService::transicion('resuelta', 'atender'), 'una resuelta no se vuelve a atender');
t_eq('resuelta', EstanciaService::transicion('en_atencion', 'resolver'), 'en atencion -> resolver = resuelta');
t_eq(null, EstanciaService::transicion('nueva', 'borrar'), 'accion desconocida = null');

$r = $servicio->cambiarEstado($hotelA, $idToallas, 'atender', $usuarioA);
t_ok(!empty($r['success']), 'atender una nueva funciona');
$r = $servicio->cambiarEstado($hotelA, $idToallas, 'atender', $usuarioA);
t_ok(empty($r['success']) && strpos((string) $r['mensaje'], 'ya la tomó') !== false, 'si dos la atienden, la segunda se entera');
t_ok(empty($servicio->cambiarEstado($hotelB, $idToallas, 'resolver', null)['success']), 'el hotel B no puede cerrar peticiones del hotel A');

$r = $servicio->cambiarEstado($hotelA, $idToallas, 'resolver', $usuarioA, 'Se llevaron 2 toallas');
t_ok(!empty($r['success']), 'resolver funciona');
$fila = $db->query("SELECT estado, cerrada_por, nota_cierre, atendida_at FROM estancia_peticiones WHERE id = ?", [$idToallas])->fetch();
t_eq('resuelta', $fila['estado'] ?? null, 'queda resuelta');
t_eq($usuarioA, (int) ($fila['cerrada_por'] ?? 0), 'con quien la cerro');
t_eq('Se llevaron 2 toallas', $fila['nota_cierre'] ?? null, 'y su nota');
t_eq(
    'resuelta',
    $db->query("SELECT estado FROM notificaciones WHERE hotel_id = ? AND entidad_tipo = 'estancia_peticion' AND entidad_id = ?", [$hotelA, $idToallas])->fetchColumn(),
    'al resolverla, su aviso de la campana tambien se cierra'
);
t_ok(empty($servicio->cambiarEstado($hotelA, $idToallas, 'descartar', $usuarioA)['success']), 'una resuelta ya no se descarta');

$r = $servicio->cambiarEstado($hotelA, $idTv, 'resolver', $usuarioA);
$fila = $db->query("SELECT atendida_por, atendida_at FROM estancia_peticiones WHERE id = ?", [$idTv])->fetch();
t_ok(!empty($fila['atendida_at']) && (int) $fila['atendida_por'] === $usuarioA, 'resolver sin atender antes cuenta como primer contacto');

$cerradas = $servicio->bandeja($hotelA, 'cerradas');
t_ok(count(array_filter($cerradas, static function ($p) use ($idToallas) { return (int) $p['id'] === $idToallas; })) === 1, 'lo resuelto pasa a la vista de cerradas');

$resumen = $servicio->resumen($hotelA);
t_eq(2, $resumen['resueltas_hoy'], 'el resumen cuenta las resueltas de hoy');
t_eq($contar("SELECT COUNT(*) FROM estancia_peticiones WHERE hotel_id = ? AND estado IN ('nueva','en_atencion')", [$hotelA]), $servicio->contarAbiertas($hotelA), 'la burbuja cuenta nuevas + en atencion');
t_eq(1, $servicio->contarAbiertas($hotelB), 'y cada hotel cuenta solo las suyas');

// ── 8. Configuracion ─────────────────────────────────────────────────────
$norm = EstanciaService::normalizarConfiguracion(['contacto_whatsapp' => '12345']);
t_ok(!empty($norm['errores']), 'un WhatsApp de 5 digitos se rechaza');
$norm = EstanciaService::normalizarConfiguracion([
    'wifi_red' => " Hotel\tInvitados ",
    'wifi_clave' => 'clave con espacios',
    'contacto_whatsapp' => '(55) 1234-5678',
    'info' => "Desayuno 8 a 11\r\n\r\n\r\n\r\nAlberca 9 a 19",
    'peticiones_activas' => '1',
]);
t_eq([], $norm['errores'], 'datos validos no dan errores');
t_eq('HotelInvitados', $norm['valores']['estancia.wifi_red'][0], 'el nombre de la red pierde los controles');
t_eq('clave con espacios', $norm['valores']['estancia.wifi_clave'][0], 'la clave conserva sus espacios internos');
t_eq("Desayuno 8 a 11\n\nAlberca 9 a 19", $norm['valores']['estancia.info'][0], 'la info normaliza saltos de linea');
t_eq('0', EstanciaService::normalizarConfiguracion([])['valores']['estancia.peticiones_activas'][0], 'casilla ausente = peticiones apagadas');

$r = $servicio->guardarConfiguracion($hotelA, [
    'wifi_red' => 'Cedros Invitados',
    'wifi_clave' => 'bienvenido2026',
    'contacto_whatsapp' => '5598765432',
    'info' => 'Desayuno de 8 a 11',
    'reglamento' => 'No fumar',
    'peticiones_activas' => '1',
]);
t_ok(!empty($r['success']), 'la configuracion se guarda');
$cfg = $servicio->configuracion($hotelA);
t_eq('bienvenido2026', $cfg['wifi_clave'], 'se lee de vuelta en el mismo request');
t_eq('', $servicio->configuracion($hotelB)['wifi_clave'], 'la clave del Wi-Fi del hotel A no aparece en el B');
t_eq('525598765432', $servicio->contactoRecepcion($hotelA)['wa'], 'el WhatsApp de recepcion queda listo para wa.me');

t_eq('525512345678', EstanciaService::telefonoWa('55 1234 5678'), '10 digitos MX -> 52 + numero');
t_eq('525512345678', EstanciaService::telefonoWa('+52 1 55 1234 5678'), 'el 1 de movil viejo se quita');
t_eq(null, EstanciaService::telefonoWa('123'), 'un numero corto no sirve');
t_eq('hola', EstanciaService::normalizarDetalle("  hola\x00 "), 'normalizarDetalle quita nulos y recorta');

// Texto legible sobre el color de la marca (la pagina del huesped pinta su
// cabecera con el color primario del hotel).
t_eq('#FFFFFF', EstanciaService::colorTextoSobre('#1B2746'), 'sobre azul marino va texto blanco');
t_eq('#1F2937', EstanciaService::colorTextoSobre('#84B3D7'), 'sobre azul claro (Los Cedros) va tinta oscura');
t_eq('#1F2937', EstanciaService::colorTextoSobre('#F9FFF0'), 'sobre casi blanco va tinta oscura');
t_eq('#FFFFFF', EstanciaService::colorTextoSobre('#000'), 'acepta la forma corta #RGB');
t_eq('#FFFFFF', EstanciaService::colorTextoSobre('rojo'), 'un color invalido cae a blanco sin tronar');
t_eq('12:00 p. m.', EstanciaService::horaHumana('12:00'), 'mediodia = 12:00 p. m.');
t_eq('9:30 a. m.', EstanciaService::horaHumana('09:30:00'), 'acepta segundos');
t_eq('12:15 a. m.', EstanciaService::horaHumana('00:15'), 'medianoche = 12 a. m.');
t_eq('', EstanciaService::horaHumana('25:00'), 'una hora imposible no se pinta');
t_eq(EstanciaService::DETALLE_MAX, mb_strlen(EstanciaService::normalizarDetalle(str_repeat('ñ', 900))), 'el detalle se topa a su maximo');

// Limpieza del storage temporal del caso.
foreach (glob($storage . '/estancia_fotos/hotel_*/*') ?: [] as $archivo) {
    @unlink($archivo);
}
foreach (glob($storage . '/estancia_fotos/hotel_*') ?: [] as $dir) {
    @rmdir($dir);
}
@rmdir($storage . '/estancia_fotos');
@rmdir($storage);
@unlink($png);
@unlink($txt);

t_fin();
