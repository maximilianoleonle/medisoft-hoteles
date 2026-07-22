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

// ── Semáforo ──
t_eq('sin_cupo', EstacionamientoProyeccionService::clasificarNivel(5, 0), 'cupo 0 = sin_cupo');
t_eq('ok', EstacionamientoProyeccionService::clasificarNivel(5, 40), '12% = ok');
t_eq('ok', EstacionamientoProyeccionService::clasificarNivel(27, 40), '67.5% = ok');
t_eq('ocupado', EstacionamientoProyeccionService::clasificarNivel(28, 40), '70% = ocupado');
t_eq('casi_lleno', EstacionamientoProyeccionService::clasificarNivel(36, 40), '90% = casi_lleno');
t_eq('casi_lleno', EstacionamientoProyeccionService::clasificarNivel(40, 40), '100% = casi_lleno (sin exceder)');
t_eq('sobrecupo', EstacionamientoProyeccionService::clasificarNivel(41, 40), '41/40 = sobrecupo');

t_fin();
