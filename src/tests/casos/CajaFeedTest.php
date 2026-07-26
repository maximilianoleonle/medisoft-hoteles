<?php
/**
 * Feed de Caja: traduccion al idioma de recepcion y emparejado de cancelaciones.
 * CajaMovimientosFeed — PURO, sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CajaFeedTest\n";

/** Fabrica de filas: solo se sobrescribe lo que importa a cada caso. */
function cf_mov(array $campos = []): array
{
    return array_merge([
        'id' => 1,
        'tipo' => 'ingreso',
        'categoria' => 'Hospedaje',
        'categoria_nombre' => null,
        'categoria_icono' => null,
        'descripcion' => 'Hospedaje - Reservación #2005',
        'monto' => '800.00',
        'metodo_pago' => 'efectivo',
        'referencia' => null,
        'proveedor' => null,
        'reservacion_id' => 2005,
        'corte_id' => 389,
        'created_at' => '2026-07-25 20:42:00',
        'usuario_nombre' => 'Ana Ruiz',
        'habitaciones_detalle' => null,
        'trabajador_id' => null,
    ], $campos);
}

// ─────────────────── Traduccion de una fila ───────────────────

$f = CajaMovimientosFeed::clasificar(cf_mov());
t_eq('Pago de hospedaje', $f['titulo'], 'hospedaje se lee como pago de hospedaje');
t_eq('Reservación #2005', $f['contexto'], 'el contexto trae la reservacion, no la descripcion cruda');
t_eq('Entrada', $f['etiqueta'], 'un ingreso normal es Entrada');
t_eq(false, $f['es_cancelacion'], 'un cobro normal no es cancelacion');
t_eq('+', $f['signo'], 'el ingreso suma');

$f = CajaMovimientosFeed::clasificar(cf_mov([
    'habitaciones_detalle' => 'Hab. 12 - suite, Hab. 13 - doble',
]));
t_eq('Reservación #2005 · Hab. 12 y 13', $f['contexto'], 'las habitaciones se resumen legibles');

// Concepto propio del hotel: manda lo que escribio la persona.
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'categoria' => 'Otros ingresos',
    'categoria_nombre' => 'Renta de salón',
    'descripcion' => 'Renta del salón para XV años',
    'reservacion_id' => 0,
]));
t_eq('Renta del salón para XV años', $f['titulo'], 'concepto libre: el titulo es lo que escribio el usuario');
t_eq('Renta de salón', $f['contexto'], 'el concepto del hotel viaja como contexto');

// Un concepto propio que EMPIEZA como uno del sistema no hereda su nombre.
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'categoria' => 'Anticipos de eventos',
    'descripcion' => 'Anticipo del salón',
    'reservacion_id' => 0,
]));
t_eq('Anticipo del salón', $f['titulo'], 'Anticipos de eventos no se confunde con anticipo de reservacion');

// El titulo del sistema no repite lo que ya dice el contexto.
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'categoria' => 'Anticipo reservacion',
    'descripcion' => 'Anticipo de reservacion - Reserva #1717',
    'reservacion_id' => 1717,
]));
t_eq('Anticipo de reservación', $f['titulo'], 'anticipo con nombre humano');
t_eq('Reservación #1717', $f['contexto'], 'no se repite la reserva en el contexto');

// El texto original solo se conserva cuando aporta algo (libro de movimientos).
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'categoria' => 'Anticipo reservacion',
    'descripcion' => 'Anticipo de reservacion - Reserva #1717',
    'reservacion_id' => 1717,
]));
t_eq('', $f['detalle_extra'], 'la descripcion cruda se calla si repite el titulo (aun sin acentos)');

$f = CajaMovimientosFeed::clasificar(cf_mov([
    'tipo' => 'gasto',
    'categoria' => 'Devoluciones',
    'descripcion' => 'Devolución por cancelación (pago corte #388) - Reservación #44',
    'reservacion_id' => 44,
]));
t_eq('Devolución por cancelación (pago corte #388) - Reservación #44', $f['detalle_extra'], 'el matiz del corte cerrado no se pierde');

// Pago al personal: el nombre sale del campo proveedor, sin el prefijo tecnico.
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'tipo' => 'gasto',
    'categoria' => 'Pago laboral',
    'descripcion' => 'Pago laboral trabajador #5 - Juan Pérez',
    'proveedor' => 'Trabajador: Juan Pérez',
    'reservacion_id' => 0,
]));
t_eq('Pago al personal', $f['titulo'], 'pago laboral en cristiano');
t_eq('Juan Pérez', $f['contexto'], 'el contexto es la persona, sin "Trabajador:"');
t_eq('Salida', $f['etiqueta'], 'un gasto normal es Salida');
t_eq('-', $f['signo'], 'el gasto resta');

// Cobro de cuenta pendiente sin reservacion: se rescata el nombre del cliente.
$f = CajaMovimientosFeed::clasificar(cf_mov([
    'categoria' => 'Cobro CxC',
    'descripcion' => 'Cobro CxC #12 - Laura Díaz',
    'reservacion_id' => 0,
]));
t_eq('Cobro de cuenta pendiente', $f['titulo'], 'CxC se traduce');
t_eq('Laura Díaz', $f['contexto'], 'queda el cliente, no el identificador interno');

// ─────────────────── Que cuenta como cancelacion ───────────────────

$cancelaciones = [
    ['gasto',   'Devoluciones',             'Devolución por cancelación - Reservación #1717 (Efectivo)'],
    ['gasto',   'Reverso anticipo',         'Reverso de anticipo - Reserva #1717'],
    ['gasto',   'Reversion Cobro CxC',      'Reversion Cobro CxC #12 mov #40'],
    ['ingreso', 'Reversion Pago laboral',   'Reversion pago laboral trabajador #5 pago #9'],
    ['ingreso', 'Reversion Pago proveedor', 'Reversion pago proveedor #3'],
];
foreach ($cancelaciones as [$tipo, $categoria, $descripcion]) {
    $mov = cf_mov(['tipo' => $tipo, 'categoria' => $categoria, 'descripcion' => $descripcion]);
    t_eq(true, CajaMovimientosFeed::esCancelacion($mov), "«{$categoria}» es cancelacion");
    t_eq('Cancelación', CajaMovimientosFeed::clasificar($mov)['etiqueta'], "«{$categoria}» se etiqueta Cancelación");
}

// Red de seguridad: sin categoria reconocible pero con la descripcion del modelo.
t_eq(true, CajaMovimientosFeed::esCancelacion(cf_mov([
    'tipo' => 'gasto',
    'categoria' => 'Ajuste',
    'descripcion' => 'Reverso de anticipo - Reserva #10',
])), 'la descripcion de reverso tambien cuenta (paridad con el modelo)');

t_eq(false, CajaMovimientosFeed::esCancelacion(cf_mov([
    'tipo' => 'gasto',
    'categoria' => 'Mantenimiento',
    'descripcion' => 'Plomería habitación 204',
])), 'un gasto normal no es cancelacion');

// ─────────────────── Emparejado cancelacion ↔ original ───────────────────

$pago = cf_mov(['id' => 10, 'monto' => '7000.00', 'reservacion_id' => 1717, 'created_at' => '2026-07-25 20:40:00']);
$devolucion = cf_mov([
    'id' => 11,
    'tipo' => 'gasto',
    'categoria' => 'Devoluciones',
    'descripcion' => 'Devolución por cancelación - Reservación #1717 (Efectivo)',
    'monto' => '7000.00',
    'reservacion_id' => 1717,
    'created_at' => '2026-07-25 21:05:00',
]);

$par = CajaMovimientosFeed::emparejar([$devolucion, $pago]);
$dev = $par[0];
$org = $par[1];
t_eq(11, $org['anulado_por']['id'] ?? 0, 'el pago queda marcado como anulado por su devolucion');
t_eq('21:05', $org['anulado_por']['hora'] ?? '', 'la hora de la cancelacion viaja al original');
t_eq(10, $dev['anula']['id'] ?? 0, 'la devolucion apunta al pago que deshace');
t_eq('Pago de hospedaje', $dev['anula']['titulo'] ?? '', 'la devolucion dice QUE deshizo');

// Distinto monto: no son pareja.
$otra = $devolucion;
$otra['monto'] = '500.00';
$par = CajaMovimientosFeed::emparejar([$otra, $pago]);
t_eq(null, $par[1]['anulado_por'] ?? null, 'montos distintos no se emparejan');

// El original nunca puede ser posterior a su cancelacion.
$posterior = $pago;
$posterior['created_at'] = '2026-07-25 22:00:00';
$par = CajaMovimientosFeed::emparejar([$devolucion, $posterior]);
t_eq(null, $par[1]['anulado_por'] ?? null, 'un cobro posterior no lo anula una devolucion vieja');

// Dos cobros iguales y UNA devolucion: solo uno queda anulado (el mas cercano).
$cobroViejo = cf_mov(['id' => 20, 'monto' => '7000.00', 'reservacion_id' => 1717, 'created_at' => '2026-07-25 18:00:00']);
$cobroNuevo = cf_mov(['id' => 21, 'monto' => '7000.00', 'reservacion_id' => 1717, 'created_at' => '2026-07-25 20:40:00']);
$par = CajaMovimientosFeed::emparejar([$devolucion, $cobroNuevo, $cobroViejo]);
$anulados = 0;
foreach ($par as $fila) {
    if (!empty($fila['anulado_por'])) {
        $anulados++;
    }
}
t_eq(1, $anulados, 'una devolucion anula un solo cobro');
t_eq(true, !empty($par[1]['anulado_por']), 'anula al cobro mas cercano en el tiempo');

// Referencia canonica REV-*: gana aunque el metodo cambie.
$cobroCxc = cf_mov([
    'id' => 30,
    'categoria' => 'Cobro CxC',
    'descripcion' => 'Cobro CxC #12 - Laura Díaz',
    'monto' => '1500.00',
    'metodo_pago' => 'tarjeta',
    'referencia' => 'CXC-12-MOV-40',
    'reservacion_id' => 0,
    'created_at' => '2026-07-25 10:00:00',
]);
$revCxc = cf_mov([
    'id' => 31,
    'tipo' => 'gasto',
    'categoria' => 'Reversion Cobro CxC',
    'descripcion' => 'Reversion Cobro CxC #12 mov #40',
    'monto' => '1500.00',
    'metodo_pago' => 'tarjeta',
    'referencia' => 'REV-CXC-12-MOV-40',
    'reservacion_id' => 0,
    'created_at' => '2026-07-25 11:00:00',
]);
$par = CajaMovimientosFeed::emparejar([$revCxc, $cobroCxc]);
t_eq(31, $par[1]['anulado_por']['id'] ?? 0, 'la referencia REV- empareja el cobro CxC');

// Cancelacion que REGRESA dinero (ingreso que deshace un gasto).
$pagoNomina = cf_mov([
    'id' => 40,
    'tipo' => 'gasto',
    'categoria' => 'Pago laboral',
    'descripcion' => 'Pago laboral trabajador #5 - Juan Pérez',
    'monto' => '2000.00',
    'proveedor' => 'Trabajador: Juan Pérez',
    'reservacion_id' => 0,
    'created_at' => '2026-07-25 09:00:00',
]);
$revNomina = cf_mov([
    'id' => 41,
    'tipo' => 'ingreso',
    'categoria' => 'Reversion Pago laboral',
    'descripcion' => 'Reversion pago laboral trabajador #5 pago #9',
    'monto' => '2000.00',
    'proveedor' => 'Trabajador: Juan Pérez',
    'reservacion_id' => 0,
    'created_at' => '2026-07-25 12:00:00',
]);
$par = CajaMovimientosFeed::emparejar([$revNomina, $pagoNomina]);
t_eq(41, $par[1]['anulado_por']['id'] ?? 0, 'la reversion de nomina empareja por trabajador');
t_eq(40, $par[0]['anula']['id'] ?? 0, 'y sabe que pago deshizo');

// Una cancelacion no cancela a otra cancelacion.
$dev2 = $devolucion;
$dev2['id'] = 12;
$dev2['created_at'] = '2026-07-25 21:30:00';
$par = CajaMovimientosFeed::emparejar([$dev2, $devolucion]);
t_eq(null, $par[1]['anulado_por'] ?? null, 'dos devoluciones no se emparejan entre si');

// ─────────────────── Agrupado por turno ───────────────────

$grupos = CajaMovimientosFeed::agrupar([
    $devolucion,                                                    // corte 389, 21:05
    $pago,                                                          // corte 389, 20:40
    cf_mov(['id' => 50, 'corte_id' => 388, 'monto' => '3000.00', 'created_at' => '2026-07-24 23:10:00']),
], 389, '2026-07-25');

t_eq(2, count($grupos), 'un grupo por turno');
t_eq(389, $grupos[0]['corte_id'], 'el turno abierto va primero (lo mas reciente)');
t_eq(true, $grupos[0]['actual'], 'el turno abierto se marca como actual');
t_eq(false, $grupos[0]['colapsado'], 'el turno abierto arranca expandido');
t_eq(true, $grupos[1]['colapsado'], 'los turnos anteriores arrancan plegados');
t_eq('En curso', $grupos[0]['chip'], 'chip del turno abierto');
t_eq('Cerrado', $grupos[1]['chip'], 'chip del turno cerrado');
t_eq('Hoy', $grupos[0]['fecha_texto'], 'la fecha se dice en humano');
t_eq('Ayer', $grupos[1]['fecha_texto'], 'ayer tambien');

t_eq(2, count($grupos[0]['movimientos']), 'el turno abierto trae sus dos movimientos');
t_eq(11, $grupos[0]['movimientos'][0]['id'], 'ordenado del mas nuevo al mas viejo');

// Los contadores separan cancelaciones de gastos reales.
t_eq(1, $grupos[0]['entradas']['cantidad'], 'una entrada en el turno');
t_eq(7000.0, $grupos[0]['entradas']['total'], 'total de entradas');
t_eq(0, $grupos[0]['salidas']['cantidad'], 'la devolucion NO se cuenta como gasto del hotel');
t_eq(1, $grupos[0]['cancelaciones']['cantidad'], 'la devolucion se cuenta como cancelacion');

// Las notas explican el par en lenguaje de recepcion.
$filaDevolucion = $grupos[0]['movimientos'][0];
$filaPago = $grupos[0]['movimientos'][1];
t_eq(true, $filaPago['anulado'], 'el pago se pinta como anulado');
t_ok(strpos($filaPago['nota'], '21:05') !== false, 'la nota del pago dice a que hora se cancelo');
t_ok(strpos($filaDevolucion['nota'], '20:40') !== false, 'la nota de la devolucion dice que movimiento deshace');
t_eq('', $grupos[1]['movimientos'][0]['nota'], 'un movimiento sin pareja no lleva nota');

// Cancelacion sin pareja visible: igual se explica sola.
$grupos = CajaMovimientosFeed::agrupar([$devolucion], 389, '2026-07-25');
$sola = $grupos[0]['movimientos'][0];
t_eq(false, $sola['anulado'], 'la devolucion no se tacha a si misma');
t_ok(strpos($sola['nota'], 'canceló un cobro') !== false, 'sin pareja, la nota explica el porque');

// ─────────────────── Detalles de presentacion ───────────────────

$f = CajaMovimientosFeed::clasificar(cf_mov(['categoria' => 'Otros', 'categoria_icono' => 'fas fa-undo', 'reservacion_id' => 0]));
t_eq('fas fa-undo', $f['icono'], 'un icono ya completo se respeta');
$f = CajaMovimientosFeed::clasificar(cf_mov(['categoria' => 'Otros', 'categoria_icono' => 'star', 'reservacion_id' => 0]));
t_eq('fas fa-star', $f['icono'], 'un icono a secas se completa');

// ── Concepto: la cadena de respaldo que arregla el "Sin categoria" ──
// Los servicios (anticipos, cobros CxC, pagos, reversos) guardan categoria_id
// NULL: sin respaldo al texto, el libro los pintaba sin concepto.
t_eq('Anticipo de reservación', CajaMovimientosFeed::concepto([
    'categoria' => 'Anticipo reservacion',
    'categoria_nombre' => null,
]), 'un anticipo sin fila de catalogo SI tiene concepto');
t_eq('Devolución al huésped', CajaMovimientosFeed::concepto([
    'categoria' => 'Devoluciones',
    'categoria_nombre' => null,
]), 'una devolucion sin catalogo tambien');
t_eq('Cobro de cuenta pendiente', CajaMovimientosFeed::concepto([
    'categoria' => 'Cobro CxC',
    'categoria_nombre' => '',
]), 'CxC nunca se le muestra al usuario');
t_eq('Mantenimiento', CajaMovimientosFeed::concepto([
    'categoria' => 'Gasto',
    'categoria_nombre' => 'Mantenimiento',
]), 'si hay concepto del hotel, ese manda');
t_eq('Sin concepto', CajaMovimientosFeed::concepto([
    'categoria' => '',
    'categoria_nombre' => null,
]), 'sin nada, se dice sin concepto (no vacio)');

// ── Nota del par para filas sueltas (libro de movimientos) ──
$parLibro = CajaMovimientosFeed::emparejar([$devolucion, $pago]);
t_ok(strpos(CajaMovimientosFeed::notaDePar($parLibro[1]), '21:05') !== false, 'el libro sabe decir cuando se cancelo la fila');
t_ok(strpos(CajaMovimientosFeed::notaDePar($parLibro[0]), '20:40') !== false, 'y que movimiento deshace la cancelacion');
t_eq('', CajaMovimientosFeed::notaDePar(cf_mov()), 'un movimiento suelto no inventa nota');

t_eq('Sin concepto', CajaMovimientosFeed::etiquetaConcepto(''), 'concepto vacio tiene nombre');
t_eq('Devolución al huésped', CajaMovimientosFeed::etiquetaConcepto('Devoluciones'), 'el panel de conceptos habla igual que el feed');
t_eq('Cobro de cuenta pendiente', CajaMovimientosFeed::etiquetaConcepto('Cobro CxC'), 'CxC fuera de la vista del usuario');

t_eq([], CajaMovimientosFeed::agrupar([], 389, '2026-07-25'), 'sin movimientos no hay grupos');

t_fin();
