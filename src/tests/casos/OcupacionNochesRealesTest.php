<?php
/**
 * Ocupación: cada fecha entre entrada inclusiva y salida exclusiva cuenta
 * como una noche, con agrupaciones y denominadores recortados al periodo.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "OcupacionNochesRealesTest\n";

t_reset_db();
$base = t_seed_base('ocupacion-noches');
$hotelId = $base['hotel_id'];
$db = Database::getInstance();

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Ocupacion', NOW())", [$hotelId]);
$huespedId = (int)$db->lastInsertId();

$db->query(
    "INSERT INTO habitaciones
        (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, '101', 'sencilla', 1, 1000.00, 'disponible', 1, NOW()),
            (?, '102', 'sencilla', 1, 800.00, 'disponible', 1, NOW())",
    [$hotelId, $hotelId]
);
$habitacion101 = (int)$db->lastInsertId();
$habitacion102 = $habitacion101 + 1;

$crearEstancia = function (
    int $habitacionId,
    string $entrada,
    string $salida,
    float $precio,
    string $estado
) use ($db, $hotelId, $huespedId): void {
    $db->query(
        "INSERT INTO reservaciones
            (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$hotelId, $huespedId, $entrada, $salida, $precio, $estado]
    );
    $reservacionId = (int)$db->lastInsertId();
    $db->query(
        "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
         VALUES (?, ?, ?, ?)",
        [$hotelId, $reservacionId, $habitacionId, $precio]
    );
};

// Lunes 6 a viernes 10: noches de lunes, martes, miércoles y jueves.
$crearEstancia($habitacion101, '2026-07-06', '2026-07-10', 4000.00, 'confirmada');

// Cruza de julio a agosto: 30 y 31 de julio, 1 y 2 de agosto.
$crearEstancia($habitacion102, '2026-07-30', '2026-08-03', 3200.00, 'checked_out');

// No debe aportar ocupación.
$crearEstancia($habitacion102, '2026-07-15', '2026-07-18', 2400.00, 'cancelada');

// Datos ajenos con alta ocupación para comprobar tenant scope.
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Ajeno', 'hotel-ajeno-ocupacion', 1, NOW())");
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
     VALUES (?, ?, '2026-07-01', '2026-08-01', 155000.00, 'checked_out', NOW())",
    [$hotelAjenoId, $huespedAjenoId]
);
$reservacionAjenaId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
     VALUES (?, ?, ?, 155000.00)",
    [$hotelAjenoId, $reservacionAjenaId, $habitacionAjenaId]
);

$reporte = new Reporte();

$diaria = $reporte->obtenerOcupacionDiaria('2026-07-06', '2026-07-10');
t_eq(4, count($diaria), 'lunes a viernes genera cuatro noches, no una entrada');
t_eq('2026-07-06', $diaria[0]['fecha'] ?? null, 'primera noche corresponde al check-in');
t_eq('2026-07-09', $diaria[3]['fecha'] ?? null, 'ultima noche es el dia anterior al check-out');
t_eq('50.00', number_format((float)($diaria[0]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'una de dos habitaciones equivale a cincuenta por ciento diario');

$semanal = $reporte->obtenerOcupacionSemanal('2026-07-06', '2026-07-12');
t_eq(1, count($semanal), 'estancia queda agrupada en una semana ISO');
t_eq(4, (int)($semanal[0]['habitaciones_ocupadas_dias'] ?? 0), 'semana suma cuatro noches ocupadas');
t_eq('28.57', number_format((float)($semanal[0]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'semana usa capacidad de dos habitaciones por siete dias');

$cruceSemanal = $reporte->obtenerOcupacionSemanal('2026-07-30', '2026-08-02');
t_eq(4, (int)($cruceSemanal[0]['habitaciones_ocupadas_dias'] ?? 0), 'semana parcial conserva las cuatro noches del cruce');
t_eq('50.00', number_format((float)($cruceSemanal[0]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'semana parcial usa solo cuatro dias de capacidad');

$mensual = $reporte->obtenerOcupacionMensual('2026-07-30', '2026-08-02');
t_eq(2, count($mensual), 'cruce se distribuye en los dos meses reales');
t_eq(2, (int)($mensual[0]['habitaciones_ocupadas_dias'] ?? 0), 'julio recibe dos noches');
t_eq(2, (int)($mensual[1]['habitaciones_ocupadas_dias'] ?? 0), 'agosto recibe dos noches');
t_eq('50.00', number_format((float)($mensual[0]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'mes parcial de julio usa dos dias filtrados');
t_eq('50.00', number_format((float)($mensual[1]['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'mes parcial de agosto usa dos dias filtrados');

$estadisticas = $reporte->obtenerEstadisticasOcupacion('2026-07-01', '2026-07-31');
t_eq(31, (int)($estadisticas['dias_periodo'] ?? 0), 'julio tiene 31 dias inclusivos');
t_eq(62, (int)($estadisticas['habitaciones_disponibles'] ?? 0), 'capacidad multiplica habitaciones activas por dias');
t_eq(6, (int)($estadisticas['habitaciones_ocupadas'] ?? 0), 'resumen suma seis noches reales de julio');
t_eq('9.68', number_format((float)($estadisticas['porcentaje_ocupacion'] ?? 0), 2, '.', ''), 'porcentaje general usa noches reales sobre capacidad');

$porDia = $reporte->obtenerOcupacionPorDiaSemana('2026-07-06', '2026-07-09');
t_eq(4, count($porDia), 'estancia aparece en sus cuatro dias de la semana');
t_eq(['Lunes', 'Martes', 'Miércoles', 'Jueves'], array_column($porDia, 'nombre_dia'), 'dias de semana corresponden a cada noche');
t_eq('1000.00', number_format((float)($porDia[0]['precio_promedio'] ?? 0), 2, '.', ''), 'tarifa promedio se expresa por noche');

$centro = file_get_contents(APP_PATH . '/views/reportes/index.php');
t_ok(strpos($centro, "url('reportes/ocupacion')") !== false, 'ocupacion aparece en el Centro de Reportes');

t_fin();
