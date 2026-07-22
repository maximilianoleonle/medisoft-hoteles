<?php
/**
 * Fichas del index de habitaciones: particion excluyente de llegadas de hoy.
 * HabitacionController::derivarEstadisticasLlegadas — PURO, sin BD.
 *
 * Contrato: 'por_llegar' de la ficha = llegadas con cuarto disponible (el grid
 * las pinta morado); las llegadas en limpieza se quedan en la ficha Limpieza
 * (sub-etiqueta); libres_hoy = disponibles fisicas - listas; el total de
 * cuartos que llegan y las reservas distintas viajan aparte para la franja.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "HabitacionLlegadasStatsTest\n";

$fila = function (int $habId, string $estado, int $resId) {
    return ['habitacion_id' => $habId, 'estado_habitacion' => $estado, 'reservacion_id' => $resId];
};

// ── Caso real Los Cedros (jul-22): 33 llegadas = 18 listas + 15 en limpieza ──
$pendientes = [];
for ($i = 1; $i <= 18; $i++) { $pendientes[$i] = $fila($i, 'disponible', 100 + $i); }
for ($i = 19; $i <= 33; $i++) { $pendientes[$i] = $fila($i, 'limpieza', 200); } // grupal: 1 reserva, 15 cuartos

$r = HabitacionController::derivarEstadisticasLlegadas($pendientes, 25);
t_eq(33, $r['por_llegar_total'], 'total de cuartos por llegar');
t_eq(18, $r['por_llegar_listas'], 'listas = llegadas con cuarto disponible');
t_eq(15, $r['por_llegar_en_limpieza'], 'en limpieza = llegadas con cuarto en limpieza');
t_eq(7, $r['libres_hoy'], 'libres hoy = 25 disponibles - 18 listas');
t_eq(19, $r['reservas_llegan_hoy'], 'reservas distintas (18 individuales + 1 grupal)');

// ── Particion suma el total del grid: libres + listas + resto de estados ──
// (25 disponibles fisicas = 7 libres + 18 moradas; nada se cuenta doble)
t_eq(25, $r['libres_hoy'] + $r['por_llegar_listas'], 'libres + listas = disponibles fisicas');

// ── Llegada con cuarto ocupado/mantenimiento: cuenta en total, no en fichas ──
$r = HabitacionController::derivarEstadisticasLlegadas([
    1 => $fila(1, 'disponible', 10),
    2 => $fila(2, 'ocupada', 11),      // back-to-back: huesped sigue adentro
    3 => $fila(3, 'mantenimiento', 12),
], 5);
t_eq(3, $r['por_llegar_total'], 'ocupada/mantenimiento cuentan en el total');
t_eq(1, $r['por_llegar_listas'], 'solo la disponible es "lista"');
t_eq(0, $r['por_llegar_en_limpieza'], 'ninguna en limpieza');
t_eq(4, $r['libres_hoy'], 'libres = 5 - 1');

// ── Sin llegadas: fichas = estados fisicos tal cual ──
$r = HabitacionController::derivarEstadisticasLlegadas([], 12);
t_eq(0, $r['por_llegar_total'], 'sin llegadas: total 0');
t_eq(0, $r['por_llegar_listas'], 'sin llegadas: listas 0');
t_eq(12, $r['libres_hoy'], 'sin llegadas: libres = disponibles');
t_eq(0, $r['reservas_llegan_hoy'], 'sin llegadas: 0 reservas');

// ── Clamp: mas llegadas listas que disponibles fisicas jamas da negativo ──
$r = HabitacionController::derivarEstadisticasLlegadas([
    1 => $fila(1, 'disponible', 1),
    2 => $fila(2, 'disponible', 2),
], 1);
t_eq(0, $r['libres_hoy'], 'libres_hoy con clamp en 0');

// ── Filas sin estado o sin reservacion no truenan ──
$r = HabitacionController::derivarEstadisticasLlegadas([
    1 => ['habitacion_id' => 1],
], 3);
t_eq(1, $r['por_llegar_total'], 'fila incompleta cuenta en el total');
t_eq(0, $r['por_llegar_listas'], 'fila sin estado no es lista');
t_eq(0, $r['reservas_llegan_hoy'], 'fila sin reservacion_id no suma reserva');

t_fin();
