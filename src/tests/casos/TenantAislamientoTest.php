<?php
/**
 * Aislamiento multi-tenant: con el contexto del hotel A, NADA del hotel B
 * puede ser visible ni operable. Es la promesa central del SaaS.
 *
 * Nota: el proceso corre con TenantContext = hotel A (los caches estaticos
 * por-request impiden cambiar de hotel a mitad de proceso); los datos del
 * hotel B se siembran por SQL directo con hotel_id explicito.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "TenantAislamientoTest\n";

t_reset_db();

$db = Database::getInstance();

// ── Hotel B (LA VICTIMA): sembrado por SQL directo, sin contexto ─────────
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B', 'hotel-b', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

$db->query("INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
            VALUES ('user_b', 'x', 'Usuario B', 'administrador', 1, NOW())");
$usuarioB = (int) $db->lastInsertId();

$db->query("INSERT INTO cajas (hotel_id, nombre, activa, created_at) VALUES (?, 'Caja B', 1, NOW())", [$hotelB]);
$cajaB = (int) $db->lastInsertId();
$db->query("INSERT INTO cortes_caja (hotel_id, caja_id, fecha_apertura, monto_inicial, usuario_apertura_id, estado)
            VALUES (?, ?, NOW(), 5000, ?, 'abierto')", [$hotelB, $cajaB, $usuarioB]);
$corteB = (int) $db->lastInsertId();

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, telefono, created_at)
            VALUES (?, 'Huesped Secreto B', '5559998877', NOW())", [$hotelB]);
$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado, created_at)
            VALUES (?, '999B', 9, 'doble', 900, 'disponible', NOW())", [$hotelB]);

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Reserva B', NOW())", [$hotelB]);
$huespedRB = (int) $db->lastInsertId();
$db->query("INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
            VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2500, 'confirmada', NOW())", [$hotelB, $huespedRB]);
$reservacionB = (int) $db->lastInsertId();

// ── Hotel A (EL ATACANTE ACCIDENTAL): contexto activo del proceso ────────
$base = t_seed_base('hotel-a');
$hotelA = $base['hotel_id'];
$usuarioA = $base['usuario_id'];

$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelA);

// 1. La caja principal de A no es la de B
$principal = $caja->obtenerCajaPrincipal();
t_ok((int) $principal['hotel_id'] === $hotelA, 'caja principal pertenece al hotel A');

// 2. El corte abierto de B es invisible para A
$corteVisto = $caja->obtenerCorteActual();
t_ok(empty($corteVisto), 'el corte abierto de B NO es visible como corte actual de A');

// 3. Acceso directo por id al corte de B: bloqueado
$porId = $caja->obtenerCortePorId($corteB);
t_ok(empty($porId), 'obtenerCortePorId(corte de B) devuelve vacio');

// 4. Cerrar el corte de B desde el contexto de A: rechazado
$cierreAjeno = $caja->cerrarCaja($corteB, 5000, 'intento cruzado', $usuarioA);
t_ok(empty($cierreAjeno['success']), 'cerrar el corte de B desde A RECHAZADO');
$sigueAbierto = $db->query('SELECT estado FROM cortes_caja WHERE id = ?', [$corteB])->fetch();
t_eq('abierto', $sigueAbierto['estado'], 'el corte de B sigue abierto (intacto)');

// 5. Huespedes de B invisibles en busquedas de A
$huesped = new Huesped();
$encontrados = $huesped->buscarPorHotel('Secreto');
t_eq(0, is_array($encontrados) ? count($encontrados) : 0, 'buscar "Secreto" (huesped de B) desde A: 0 resultados');
$porTelefono = $huesped->buscarPorTelefonoPorHotel('5559998877');
t_ok(empty($porTelefono), 'buscar telefono del huesped de B desde A: vacio');

// 6. Habitaciones de B invisibles para A
$habitacion = new Habitacion();
$habs = $habitacion->buscar('999B');
t_eq(0, is_array($habs) ? count($habs) : 0, 'habitacion 999B de B invisible desde A');

// 7. Servicios de dinero: reservacion de B inoperable desde A
$anticipos = new AnticipoService();
$eval = $anticipos->evaluar($hotelA, $reservacionB);
t_ok(empty($eval['elegible']), 'evaluar anticipo de la reservacion de B con hotel A: NO elegible');

t_throws(
    fn () => $anticipos->registrar($hotelA, $reservacionB, ['monto' => 10, 'metodo_pago' => 'efectivo'], $usuarioA),
    'no encontrada',
    'registrar anticipo cruzado (hotel A -> reservacion B) RECHAZADO'
);

// 8. Reservaciones del dia de A no incluyen la de B
$reservacion = new Reservacion();
$delDia = $reservacion->obtenerReservacionesDelDia();
$idsVistos = array_map(fn ($r) => (int) ($r['id'] ?? 0), is_array($delDia) ? $delDia : []);
t_ok(!in_array($reservacionB, $idsVistos, true), 'la reservacion de B no aparece en el dia de A');

t_fin();
