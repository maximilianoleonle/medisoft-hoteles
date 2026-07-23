<?php
/**
 * Proyección de estacionamiento a días futuros.
 * EstacionamientoProyeccionService::proyectarDia / clasificarNivel — PUROS, sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/services/EstacionamientoProyeccionService.php';

echo "EstacionamientoProyeccionTest\n";

// ── Día sin reservas ──
$r = EstacionamientoProyeccionService::proyectarDia([], []);
t_eq(0, $r['total'], 'sin reservas = 0 vehiculos');
t_eq([], $r['por_area'], 'sin reservas = sin areas');

// ── Reserva legacy (estimado NULL) usa los registrados del huésped ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 10, 'vehiculos_estimados' => null]],
    [10 => ['coches' => 2, 'camionetas' => 1]]
);
t_eq(3, $r['total'], 'NULL cuenta todos los registrados');
t_eq(2, $r['por_area']['coches'], 'coches del registro');
t_eq(1, $r['por_area']['camionetas'], 'camioneta del registro');

// ── Estimado 0 declara "no trae" y silencia los registrados ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 10, 'vehiculos_estimados' => 0]],
    [10 => ['coches' => 2]]
);
t_eq(0, $r['total'], 'estimado 0 pisa a los registrados');

// ── Estimado menor que registrados: recorta ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 10, 'vehiculos_estimados' => 1]],
    [10 => ['coches' => 2]]
);
t_eq(1, $r['total'], 'estimado 1 con 2 registrados = 1');
t_eq(1, $r['por_area']['coches'], 'recorta al area registrada');

// ── Estimado mayor que registrados: excedente a por_confirmar ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 10, 'vehiculos_estimados' => 3]],
    [10 => ['coches' => 1]]
);
t_eq(3, $r['total'], 'estimado 3 con 1 registrado = 3');
t_eq(1, $r['por_area']['coches'], 'registrado en su area');
t_eq(2, $r['por_area'][EstacionamientoProyeccionService::AREA_POR_CONFIRMAR], 'excedente a por_confirmar');

// ── Estimado sin ningún registrado: todo a por_confirmar ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 11, 'vehiculos_estimados' => 2]],
    []
);
t_eq(2, $r['total'], 'estimado sin registrados cuenta completo');
t_eq(2, $r['por_area'][EstacionamientoProyeccionService::AREA_POR_CONFIRMAR], 'sin registro = por_confirmar');

// ── Dedup por huésped: entre varias reservas del día gana la declarada ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [
        ['huesped_id' => 10, 'vehiculos_estimados' => null],
        ['huesped_id' => 10, 'vehiculos_estimados' => 2],
    ],
    [10 => ['coches' => 5]]
);
t_eq(2, $r['total'], 'huesped duplicado cuenta una vez (gana declarado)');

// ── Varios huéspedes suman ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [
        ['huesped_id' => 10, 'vehiculos_estimados' => null],
        ['huesped_id' => 20, 'vehiculos_estimados' => 1],
    ],
    [10 => ['coches' => 1], 20 => ['camionetas' => 1]]
);
t_eq(2, $r['total'], 'dos huespedes suman');
t_eq(1, $r['por_area']['coches'], 'area huesped 1');
t_eq(1, $r['por_area']['camionetas'], 'area huesped 2');

// ── huesped_id inválido se ignora ──
$r = EstacionamientoProyeccionService::proyectarDia(
    [['huesped_id' => 0, 'vehiculos_estimados' => 4]],
    []
);
t_eq(0, $r['total'], 'huesped_id 0 se ignora');

// ── Desglose (detalleDia): filas enlazables por vehículo ──
$reservasDet = [
    ['reservacion_id' => 501, 'huesped_id' => 10, 'vehiculos_estimados' => null, 'huesped' => 'Ana', 'habitaciones' => '3', 'estado' => 'checked_in'],
    ['reservacion_id' => 502, 'huesped_id' => 20, 'vehiculos_estimados' => 3, 'huesped' => 'Beto', 'habitaciones' => '7', 'estado' => 'confirmada'],
    ['reservacion_id' => 503, 'huesped_id' => 30, 'vehiculos_estimados' => 0, 'huesped' => 'Caro', 'habitaciones' => '', 'estado' => 'confirmada'],
];
$vehiculosDet = [
    10 => [
        ['area' => 'coches', 'vehiculo' => 'Nissan Versa', 'placas' => 'AAA-111'],
        ['area' => 'camionetas', 'vehiculo' => 'Ford Ranger', 'placas' => 'BBB-222'],
    ],
    20 => [
        ['area' => 'coches', 'vehiculo' => 'Kia Rio', 'placas' => 'CCC-333'],
    ],
    30 => [
        ['area' => 'coches', 'vehiculo' => 'VW Jetta', 'placas' => 'DDD-444'],
    ],
];
$filas = EstacionamientoProyeccionService::detalleDia($reservasDet, $vehiculosDet);
t_eq(4, count($filas), 'detalle: 2 de Ana + 1 de Beto + 1 por confirmar (Caro declaró 0)');
t_eq('Ana', $filas[0]['huesped'], 'hospedados (checked_in) van primero');
t_eq(501, $filas[0]['reservacion_id'], 'fila enlaza a la reservacion ganadora');
t_eq('Nissan Versa', $filas[0]['vehiculo'], 'trae el vehiculo registrado');
t_eq(false, $filas[0]['por_confirmar'], 'registrado no es por_confirmar');
$ultima = $filas[count($filas) - 1];
t_eq(true, $ultima['por_confirmar'], 'excedente declarado queda como por_confirmar');
t_eq(2, $ultima['cantidad'], 'Beto declaro 3 con 1 registrado = 2 por confirmar');
t_eq(EstacionamientoProyeccionService::AREA_POR_CONFIRMAR, $ultima['area'], 'excedente en area por_confirmar');

// La suma de cantidades del desglose SIEMPRE cuadra con proyectarDia (misma fuente de la barra).
$agregados = [];
foreach ($vehiculosDet as $hid => $lista) {
    foreach ($lista as $v) {
        $agregados[$hid][$v['area']] = ($agregados[$hid][$v['area']] ?? 0) + 1;
    }
}
$conteo = EstacionamientoProyeccionService::proyectarDia($reservasDet, $agregados);
$sumaDetalle = array_sum(array_map(function ($f) { return $f['cantidad']; }, $filas));
t_eq($conteo['total'], $sumaDetalle, 'detalle y proyeccion cuentan lo mismo');

// Dedup en detalle: gana la reserva con estimado declarado, y se enlaza ESA reserva.
$filas = EstacionamientoProyeccionService::detalleDia(
    [
        ['reservacion_id' => 601, 'huesped_id' => 10, 'vehiculos_estimados' => null, 'huesped' => 'Ana', 'habitaciones' => '', 'estado' => 'confirmada'],
        ['reservacion_id' => 602, 'huesped_id' => 10, 'vehiculos_estimados' => 1, 'huesped' => 'Ana', 'habitaciones' => '', 'estado' => 'confirmada'],
    ],
    [10 => [
        ['area' => 'coches', 'vehiculo' => 'Nissan Versa', 'placas' => 'AAA-111'],
        ['area' => 'coches', 'vehiculo' => 'Mazda 3', 'placas' => 'EEE-555'],
    ]]
);
t_eq(1, count($filas), 'dedup: estimado 1 recorta a un vehiculo');
t_eq(602, $filas[0]['reservacion_id'], 'enlaza la reserva declarada, no la legacy');

// ── Nombre presentable del vehículo (limpia rellenos legacy "sin definir") ──
t_eq('Toyota Tacoma GRIS', EstacionamientoProyeccionService::nombreVehiculo('Toyota', 'Tacoma', 'GRIS'), 'nombre completo normal');
t_eq('AUTOBUS', EstacionamientoProyeccionService::nombreVehiculo('AUTOBUS', 'SIN DEFINIR', 'sin definir'), 'filtra sin definir en cualquier caja');
t_eq('TRAX', EstacionamientoProyeccionService::nombreVehiculo('TRAX', 'n/a', '-'), 'filtra n/a y guion');
t_eq('Vehículo registrado', EstacionamientoProyeccionService::nombreVehiculo('', 'sin definir', ''), 'todo relleno = etiqueta generica');
t_eq(true, EstacionamientoProyeccionService::esRellenoSinDato('  SIN DEFINIR '), 'relleno detectado con espacios/mayusculas');
t_eq(false, EstacionamientoProyeccionService::esRellenoSinDato('GRIS'), 'dato real no es relleno');

// ── Semáforo ──
t_eq('sin_cupo', EstacionamientoProyeccionService::clasificarNivel(5, 0), 'cupo 0 = sin_cupo');
t_eq('ok', EstacionamientoProyeccionService::clasificarNivel(5, 40), '12% = ok');
t_eq('ok', EstacionamientoProyeccionService::clasificarNivel(27, 40), '67.5% = ok');
t_eq('ocupado', EstacionamientoProyeccionService::clasificarNivel(28, 40), '70% = ocupado');
t_eq('casi_lleno', EstacionamientoProyeccionService::clasificarNivel(36, 40), '90% = casi_lleno');
t_eq('casi_lleno', EstacionamientoProyeccionService::clasificarNivel(40, 40), '100% = casi_lleno (sin exceder)');
t_eq('sobrecupo', EstacionamientoProyeccionService::clasificarNivel(41, 40), '41/40 = sobrecupo');

t_fin();
