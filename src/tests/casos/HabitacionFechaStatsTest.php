<?php
/**
 * Fichas del index de habitaciones cuando se consulta OTRA FECHA (proyeccion).
 * HabitacionController::estadoDisplayCompromisoEnFecha + derivarEstadisticasParaFecha
 * — PUROS, sin BD.
 *
 * Contrato (queja del dueno jul-27): una reservacion que ENTRA el dia consultado
 * ya no se pinta "Ocupada" junto al huesped que lleva 3 noches adentro. La que
 * entra ese dia es 'por_llegar' (violeta, mismo estado que la vista de hoy) y
 * solo la estancia que viene de antes queda 'ocupada_fecha'. Antes la ficha
 * "Por llegar" iba forzada a 0 y "Limpieza" contaba un estado inexistente.
 *
 * Hermano de HabitacionLlegadasStatsTest (ese cubre la particion de HOY).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "HabitacionFechaStatsTest\n";

// ── Clasificacion: entra ese dia vs viene de antes ─────────────────────────
t_eq(
    'por_llegar',
    HabitacionController::estadoDisplayCompromisoEnFecha('2026-07-28', '2026-07-28'),
    'entra el dia consultado = por llegar'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoEnFecha('2026-07-26', '2026-07-28'),
    'entro antes = ocupada (ya esta adentro esa noche)'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoEnFecha('2026-07-29', '2026-07-28'),
    'entra despues: no deberia llegar aqui, cae en ocupada (nunca infla lo vendible)'
);

// ── DATETIME y basura no rompen la comparacion ─────────────────────────────
t_eq(
    'por_llegar',
    HabitacionController::estadoDisplayCompromisoEnFecha('2026-07-28 15:00:00', '2026-07-28'),
    'fecha_entrada con hora se compara por los 10 primeros chars'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoEnFecha(null, '2026-07-28'),
    'sin fecha_entrada cae en ocupada, no en disponible'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoEnFecha('', '2026-07-28'),
    'fecha vacia cae en ocupada'
);

// ── Caso de la captura: 49 cuartos, 7 llegan el 27, 1 en mantenimiento ─────
$estados = [];
for ($i = 0; $i < 17; $i++) { $estados[] = 'disponible_fecha'; }
for ($i = 0; $i < 24; $i++) { $estados[] = 'ocupada_fecha'; }
for ($i = 0; $i < 7; $i++)  { $estados[] = 'por_llegar'; }
$estados[] = 'mantenimiento';

$r = HabitacionController::derivarEstadisticasParaFecha($estados);
t_eq(49, $r['total'], 'total = cuartos procesados');
t_eq(17, $r['disponibles'], 'disponibles = vendibles esa noche');
t_eq(24, $r['ocupadas'], 'ocupadas = estancias que vienen de antes');
t_eq(7, $r['por_llegar'], 'por llegar YA NO va forzado a 0');
t_eq(1, $r['mantenimiento'], 'mantenimiento = estado fisico');
t_eq(0, $r['limpieza'], 'limpieza no se proyecta a otra fecha');

// ── Invariante: la particion suma el total (ficha = chip = grid) ───────────
t_eq(
    $r['total'],
    $r['disponibles'] + $r['ocupadas'] + $r['por_llegar'] + $r['limpieza'] + $r['mantenimiento'],
    'particion excluyente: las fichas suman el total'
);

// ── Un estado desconocido se cuenta como ocupada, jamas como disponible ────
$r = HabitacionController::derivarEstadisticasParaFecha(['disponible_fecha', 'cualquier_cosa', '']);
t_eq(3, $r['total'], 'total cuenta todo');
t_eq(1, $r['disponibles'], 'solo el disponible_fecha explicito es vendible');
t_eq(2, $r['ocupadas'], 'desconocido cae en ocupada (nunca provoca sobreventa)');
t_eq(
    $r['total'],
    $r['disponibles'] + $r['ocupadas'] + $r['por_llegar'] + $r['limpieza'] + $r['mantenimiento'],
    'particion se sostiene con estados basura'
);

// ── PASADO: el eje deja de ser "llega o ya esta dentro" y pasa a ser el
//    RESULTADO de la noche. Una reserva que sobre una fecha ya ocurrida sigue en
//    'confirmada' es una que NUNCA registro check-in: contarla como rentada
//    inventa una venta, y llamarla "Entro" contradice al panel, que la marca
//    "Sin check-in". ────────────────────────────────────────────────────────
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoPasado('checked_out'),
    'checked_out => se rento esa noche'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoPasado('checked_in'),
    'checked_in => se rento esa noche'
);
t_eq(
    'por_llegar',
    HabitacionController::estadoDisplayCompromisoPasado('confirmada'),
    'confirmada sobre fecha pasada => sin check-in, NUNCA rentada'
);
t_eq(
    'por_llegar',
    HabitacionController::estadoDisplayCompromisoPasado('CONFIRMADA'),
    'el estado se compara sin importar mayusculas'
);
t_eq(
    'ocupada_fecha',
    HabitacionController::estadoDisplayCompromisoPasado(''),
    'estado desconocido cuenta como rentada, no infla el "sin ocupar"'
);

// ── La particion del pasado tambien suma el total ──────────────────────────
$r = HabitacionController::derivarEstadisticasParaFecha(array_merge(
    array_fill(0, 29, 'ocupada_fecha'),
    array_fill(0, 2, 'por_llegar'),
    array_fill(0, 18, 'disponible_fecha')
));
t_eq(49, $r['total'], 'noche pasada: total');
t_eq(29, $r['ocupadas'], 'se rentaron');
t_eq(2, $r['por_llegar'], 'sin check-in');
t_eq(18, $r['disponibles'], 'sin ocupar');
t_eq(
    $r['total'],
    $r['disponibles'] + $r['ocupadas'] + $r['por_llegar'] + $r['limpieza'] + $r['mantenimiento'],
    'particion del pasado suma el total'
);

// ── Hotel vacio no truena ──────────────────────────────────────────────────
$r = HabitacionController::derivarEstadisticasParaFecha([]);
t_eq(0, $r['total'], 'sin cuartos: total 0');
t_eq(0, $r['por_llegar'], 'sin cuartos: por llegar 0');

t_fin();
