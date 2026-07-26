<?php
/**
 * Habitaciones rentables: noches reales dentro del periodo, ingreso asignado
 * proporcionalmente y aislamiento por hotel.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "RentabilidadHabitacionesTest\n";

t_reset_db();
$base = t_seed_base('rentabilidad-habitaciones');
$hotelId = $base['hotel_id'];
$db = Database::getInstance();

$db->query(
    "INSERT INTO huespedes (hotel_id, nombre_completo, created_at)
     VALUES (?, 'Huesped Rentabilidad', NOW())",
    [$hotelId]
);
$huespedId = (int)$db->lastInsertId();

$db->query(
    "INSERT INTO habitaciones
        (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, '101', 'sencilla', 1, 1000.00, 'disponible', 1, NOW()),
            (?, '102', 'sencilla', 1, 900.00, 'disponible', 1, NOW()),
            (?, '103', 'sencilla', 1, 1000.00, 'disponible', 1, NOW()),
            (?, '104', 'sencilla', 1, 800.00, 'disponible', 1, NOW())",
    [$hotelId, $hotelId, $hotelId, $hotelId]
);
$habitacion101 = (int)$db->lastInsertId();
$habitacion102 = $habitacion101 + 1;
$habitacion103 = $habitacion101 + 2;
$habitacion104 = $habitacion101 + 3;

$crearEstancia = function (
    int $habitacionId,
    string $entrada,
    string $salida,
    float $precio,
    string $estado = 'checked_out'
) use ($db, $hotelId, $huespedId): int {
    $db->query(
        "INSERT INTO reservaciones
            (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$hotelId, $huespedId, $entrada, $salida, $precio, $estado]
    );
    $reservacionId = (int)$db->lastInsertId();
    $db->query(
        "INSERT INTO reservacion_habitaciones
            (hotel_id, reservacion_id, habitacion_id, precio)
         VALUES (?, ?, ?, ?)",
        [$hotelId, $reservacionId, $habitacionId, $precio]
    );
    return $reservacionId;
};

// Cuatro noches completas dentro del periodo: 10, 11, 12 y 13 de julio.
$crearEstancia($habitacion101, '2026-07-10', '2026-07-14', 4000.00);

// Comienza antes del filtro; solo las noches del 1 y 2 de julio pertenecen al rango.
$crearEstancia($habitacion102, '2026-06-29', '2026-07-03', 3600.00);

// No debe entrar al reporte de rentabilidad realizada por no estar terminada.
$crearEstancia($habitacion101, '2026-07-20', '2026-07-22', 2000.00, 'confirmada');

// La noche del ultimo dia del periodo debe contarse completa.
$crearEstancia($habitacion103, '2026-07-31', '2026-08-01', 1000.00);

// Una cancelacion nunca aporta noches ni ingresos realizados.
$crearEstancia($habitacion104, '2026-07-15', '2026-07-18', 2400.00, 'cancelada');

// Un hotel ajeno no puede contaminar los resultados del tenant actual.
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Ajeno', 'hotel-ajeno-rentabilidad', 1, NOW())");
$hotelAjenoId = (int)$db->lastInsertId();
$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Ajeno', NOW())", [$hotelAjenoId]);
$huespedAjenoId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, 'AJ-1', 'doble', 1, 5000.00, 'disponible', 1, NOW())",
    [$hotelAjenoId]
);
$habitacionAjenaId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO reservaciones
        (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
     VALUES (?, ?, '2026-07-01', '2026-07-31', 150000.00, 'checked_out', NOW())",
    [$hotelAjenoId, $huespedAjenoId]
);
$reservacionAjenaId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
     VALUES (?, ?, ?, 150000.00)",
    [$hotelAjenoId, $reservacionAjenaId, $habitacionAjenaId]
);

$reporte = new Reporte();
$resultado = $reporte->obtenerRentabilidadHabitaciones('2026-07-01', '2026-07-31');
$porNumero = [];
foreach ($resultado as $fila) {
    $porNumero[$fila['numero']] = $fila;
}

t_eq(4, (int)($porNumero['101']['dias_ocupada'] ?? 0), 'estancia de cuatro noches cuenta cuatro dias ocupados');
t_eq('12.90', number_format((float)($porNumero['101']['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'ocupacion usa los 31 dias inclusivos de julio');
t_eq('4000.00', number_format((float)($porNumero['101']['ingresos_totales'] ?? 0), 2, '.', ''), 'estancia completa conserva su ingreso');
t_eq(1, (int)($porNumero['101']['total_reservaciones'] ?? 0), 'reservacion no finalizada queda excluida');

t_eq(2, (int)($porNumero['102']['dias_ocupada'] ?? 0), 'estancia iniciada antes aporta solo noches superpuestas');
t_eq('1800.00', number_format((float)($porNumero['102']['ingresos_totales'] ?? 0), 2, '.', ''), 'ingreso de estancia parcial se asigna proporcionalmente');
t_eq('900.00', number_format((float)($porNumero['102']['precio_promedio'] ?? 0), 2, '.', ''), 'precio promedio representa ingreso por noche');
t_eq(1, (int)($porNumero['103']['dias_ocupada'] ?? 0), 'noche del ultimo dia del periodo se incluye');
t_ok(!isset($porNumero['104']), 'reservacion cancelada no aparece en rentabilidad');
t_ok(!isset($porNumero['AJ-1']), 'habitacion de otro hotel queda aislada');

$ultimoDia = $reporte->obtenerRentabilidadHabitaciones('2026-07-31', '2026-07-31');
t_eq(1, (int)($ultimoDia[0]['dias_ocupada'] ?? 0), 'periodo de un solo dia conserva una noche');
t_eq('100.00', number_format((float)($ultimoDia[0]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'una noche en periodo de un dia equivale a cien por ciento');

$porTipo = $reporte->obtenerOcupacionPorTipo('2026-07-01', '2026-07-31');
t_eq(1, count($porTipo), 'resumen agrupa el tipo sin duplicarlo');
t_eq(7, (int)($porTipo[0]['dias_ocupadas'] ?? 0), 'resumen por tipo suma las siete noches reales');
t_eq('6800.00', number_format((float)($porTipo[0]['ingresos_totales'] ?? 0), 2, '.', ''), 'resumen por tipo usa ingresos del periodo');

t_fin();
