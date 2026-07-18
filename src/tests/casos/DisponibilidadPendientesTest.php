<?php
/**
 * Candado de disponibilidad por pendientes: un check-out vencido (checked_in
 * con salida pasada) o una reservacion confirmada pasada sin resolver siguen
 * ocupando la habitacion HOY (salida efectiva se extiende a manana) en los
 * tres verificadores de disponibilidad. Resolver el pendiente libera; las
 * fechas futuras nunca se bloquean por pendientes de hoy. La limpieza NO es
 * candado de servidor (solo aviso en UI).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "DisponibilidadPendientesTest\n";

t_reset_db();
$base = t_seed_base('disp-pend-a');
$hotelId = $base['hotel_id'];

$db = Database::getInstance();

// Fechas ancladas al reloj de la BD (CURDATE), no al de PHP: los predicados
// del candado usan CURDATE() y los relojes difieren entre PHP y MySQL.
$hoy = (string) $db->query('SELECT CURDATE() AS d')->fetch()['d'];
$manana = (new DateTime($hoy))->modify('+1 day')->format('Y-m-d');
$pasado = (new DateTime($hoy))->modify('+2 day')->format('Y-m-d');

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa)
     VALUES (?, '101', 'sencilla', 1, 500.00, 'disponible', 1)",
    [$hotelId]
);
$habitacionId = (int) $db->lastInsertId();

$crearReserva = function (int $hId, int $habId, int $entradaOffset, int $salidaOffset, string $estado) use ($db): int {
    $db->query(
        "INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Pendiente', NOW())",
        [$hId]
    );
    $huespedId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
         VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), 500, ?, NOW())",
        [$hId, $huespedId, $entradaOffset, $salidaOffset, $estado]
    );
    $resId = (int) $db->lastInsertId();
    $db->query(
        "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
         VALUES (?, ?, ?, 500)",
        [$hId, $resId, $habId]
    );
    return $resId;
};

$habitacionModel = new Habitacion();
$reservacionModel = new Reservacion();

$en101Disponibles = function (array $disponibles) use ($habitacionId): bool {
    foreach ($disponibles as $hab) {
        if ((int) $hab['id'] === $habitacionId) {
            return true;
        }
    }
    return false;
};

// ── Sin pendientes: la 101 esta libre hoy ────────────────────────────────
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'sin pendientes la 101 aparece disponible hoy');
t_ok($reservacionModel->verificarDisponibilidadMultiple([$habitacionId], $hoy, $manana), 'verificador multiple da true sin pendientes');

// ── Check-out vencido: checked_in con salida AYER bloquea HOY ────────────
$resVencida = $crearReserva($hotelId, $habitacionId, -3, -1, 'checked_in');

t_ok(!$en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'checkout vencido saca la 101 de disponibles hoy');
t_ok(!$reservacionModel->verificarDisponibilidadMultiple([$habitacionId], $hoy, $manana), 'verificador multiple bloquea hoy con checkout vencido');
t_ok(!$reservacionModel->verificarDisponibilidadMultipleExcluyendo([$habitacionId], $hoy, $manana, 999999), 'verificador excluyendo (otra reserva) tambien bloquea hoy');

// Fechas futuras NO se bloquean por el pendiente de hoy.
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($manana, $pasado)), 'el vencido NO bloquea una reserva de manana');
t_ok($reservacionModel->verificarDisponibilidadMultiple([$habitacionId], $manana, $pasado), 'verificador multiple permite manana con vencido hoy');

// Mapa de pendientes: la 101 aparece como checkout_vencido.
$pendientes = $reservacionModel->pendientesPorHabitacion($hotelId);
t_ok(isset($pendientes[$habitacionId]), 'pendientesPorHabitacion incluye la 101');
t_eq('checkout_vencido', $pendientes[$habitacionId]['tipo'] ?? '', 'tipo de pendiente es checkout_vencido');
t_eq($resVencida, (int) ($pendientes[$habitacionId]['reservacion_id'] ?? 0), 'el pendiente apunta a la reservacion vencida');

// Resolver (check-out => checked_out) libera la habitacion HOY.
$db->query("UPDATE reservaciones SET estado = 'checked_out' WHERE id = ?", [$resVencida]);
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'resolver el check-out libera la 101 hoy');
t_ok($reservacionModel->verificarDisponibilidadMultiple([$habitacionId], $hoy, $manana), 'verificador multiple libera tras resolver');
t_ok(empty($reservacionModel->pendientesPorHabitacion($hotelId)), 'mapa de pendientes queda vacio tras resolver');

// ── Check-in pendiente: confirmada pasada sin resolver bloquea HOY ───────
$resSinCheckin = $crearReserva($hotelId, $habitacionId, -2, -1, 'confirmada');

t_ok(!$en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'confirmada pasada sin resolver bloquea la 101 hoy');
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($manana, $pasado)), 'confirmada pasada NO bloquea manana');

$pendientes = $reservacionModel->pendientesPorHabitacion($hotelId);
t_eq('checkin_pendiente', $pendientes[$habitacionId]['tipo'] ?? '', 'tipo de pendiente es checkin_pendiente');

// Si ademas hay checkout vencido en el mismo cuarto, gana el vencido (huesped adentro).
$resVencida2 = $crearReserva($hotelId, $habitacionId, -4, -2, 'checked_in');
$pendientes = $reservacionModel->pendientesPorHabitacion($hotelId);
t_eq('checkout_vencido', $pendientes[$habitacionId]['tipo'] ?? '', 'checkout_vencido pisa a checkin_pendiente en el mapa');
$db->query("UPDATE reservaciones SET estado = 'checked_out' WHERE id = ?", [$resVencida2]);

// Resolver como no-show (cancelada) libera.
$db->query("UPDATE reservaciones SET estado = 'cancelada' WHERE id = ?", [$resSinCheckin]);
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'cancelar/no-show libera la 101 hoy');

// ── Reserva normal en curso sigue funcionando igual (sin regresion) ──────
$resHoy = $crearReserva($hotelId, $habitacionId, 0, 1, 'confirmada');
t_ok(!$en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'reserva normal de hoy sigue bloqueando hoy');
t_ok($reservacionModel->verificarDisponibilidadMultipleExcluyendo([$habitacionId], $hoy, $manana, $resHoy), 'excluir la propia reservacion (edicion) sigue dando true');
$db->query("UPDATE reservaciones SET estado = 'cancelada' WHERE id = ?", [$resHoy]);

// ── Limpieza NO es candado de servidor (solo aviso en UI) ────────────────
$db->query("UPDATE habitaciones SET estado = 'limpieza' WHERE id = ?", [$habitacionId]);
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'habitacion en limpieza sigue disponible en servidor (aviso, no candado)');
$db->query("UPDATE habitaciones SET estado = 'disponible' WHERE id = ?", [$habitacionId]);

// ── Tenancy: pendientes de otro hotel no contaminan ──────────────────────
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B Pend', 'disp-pend-b', 1, NOW())");
$hotelBId = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa)
     VALUES (?, '101', 'sencilla', 1, 500.00, 'disponible', 1)",
    [$hotelBId]
);
$habitacionBId = (int) $db->lastInsertId();
$crearReserva($hotelBId, $habitacionBId, -3, -1, 'checked_in');

t_ok(empty($reservacionModel->pendientesPorHabitacion($hotelId)), 'el vencido del hotel B no aparece en el mapa del hotel A');
$pendientesB = $reservacionModel->pendientesPorHabitacion($hotelBId);
t_ok(isset($pendientesB[$habitacionBId]), 'el mapa del hotel B si trae su vencido');
t_ok($en101Disponibles($habitacionModel->disponiblesEntreFechas($hoy, $manana)), 'la 101 del hotel A sigue disponible pese al vencido de B');

// ── Fuente unica de alertas (misma data que los index) ───────────────────
$resVencida3 = $crearReserva($hotelId, $habitacionId, -3, -1, 'checked_in');
$alertas = $reservacionModel->alertasPendientesOperativas($hotelId);
t_eq(1, count($alertas['checkouts']), 'alertasPendientesOperativas reporta el checkout vencido');
t_eq(0, count($alertas['checkins']), 'sin checkins pendientes en hotel A');
t_ok(array_key_exists('llegadas_tardias', $alertas), 'contrato de llaves intacto (llegadas_tardias)');

t_fin();
