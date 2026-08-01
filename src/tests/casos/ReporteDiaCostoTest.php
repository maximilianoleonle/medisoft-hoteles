<?php
/**
 * Columna COSTO del reporte del dia (Excel .xls y PDF de /reservaciones).
 * ReservacionController::costoReporteDia / ::formatoMonedaReporteDia — PUROS, sin BD.
 *
 * Dos contratos que se rompieron de verdad, por eso viven aqui:
 *  (1) FORMATO: el separador de miles JAMAS va en punto. El .xls es HTML y Excel lee
 *      el punto como separador DECIMAL en es-MX -> "$1.200" se pinta $1.20. Sintoma
 *      real reportado con captura: cuartos de $1,200 mostrados como $1.20.
 *  (2) TEMPORADA: la habitacion LIBRE se cotiza con el incremento vigente de ESA
 *      fecha; la VENDIDA manda su precio congelado. Antes la libre salia con
 *      precio_base crudo, asi que CORAL ($1,000 + $200 de temporada) aparecia en
 *      $1,000 justo al lado de sus gemelas vendidas en $1,200.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "ReporteDiaCostoTest\n";

/* ── (1) Formato: el punto como separador de miles esta PROHIBIDO ── */

t_eq('$600', ReservacionController::formatoMonedaReporteDia(600), 'monto de 3 digitos sin separador');
t_eq('$1,000', ReservacionController::formatoMonedaReporteDia(1000), '1000 lleva COMA, no punto');
t_eq('$1,200', ReservacionController::formatoMonedaReporteDia(1200), '1200 lleva COMA (el bug daba $1.200 -> Excel $1.20)');
t_eq('$1,300', ReservacionController::formatoMonedaReporteDia(1300), '1300 lleva COMA');
t_eq('$12,500', ReservacionController::formatoMonedaReporteDia(12500), 'cinco digitos');
t_eq('$0', ReservacionController::formatoMonedaReporteDia(0), 'cero');

// El aserto que impide la regresion exacta: ni un solo punto en la salida.
foreach ([1000, 1200, 1300, 2500, 10000, 1000000] as $monto) {
    $texto = ReservacionController::formatoMonedaReporteDia($monto);
    t_ok(strpos($texto, '.') === false, "sin punto en la salida de $monto ($texto)");
}

t_eq('$1,200', ReservacionController::formatoMonedaReporteDia('1200'), 'acepta string numerico');
t_eq('$1,200', ReservacionController::formatoMonedaReporteDia(1200.00), 'acepta float');
t_eq('$1,200', ReservacionController::formatoMonedaReporteDia(1200.4), 'redondea a entero');

/* ── (2) Habitacion LIBRE: se cotiza CON la temporada de esa fecha ── */

$coral  = ['id' => 501, 'numero' => 'CORAL',  'precio_base' => 1000.00];
$marfil = ['id' => 502, 'numero' => 'MARFIL', 'precio_base' => 1200.00];
$menta  = ['id' => 503, 'numero' => 'MENTA',  'precio_base' => 600.00];

// Mapa tal como lo arma preciosTemporadaReporteDia(): CORAL +200, MARFIL +100, MENTA sin temporada.
$temporada = [501 => 1200.00, 502 => 1300.00, 503 => 600.00];

$r = ReservacionController::costoReporteDia($coral, null, $temporada);
t_eq('$1,200', $r['texto'], 'CORAL libre se cotiza con su +$200 de temporada');
t_ok(abs($r['monto'] - 1200.0) < 0.001, 'monto de CORAL con temporada');
t_ok($r['con_temporada'] === true, 'CORAL queda marcada como con temporada');

$r = ReservacionController::costoReporteDia($marfil, null, $temporada);
t_eq('$1,300', $r['texto'], 'MARFIL libre se cotiza con su +$100');
t_ok($r['con_temporada'] === true, 'MARFIL marcada como con temporada');

$r = ReservacionController::costoReporteDia($menta, null, $temporada);
t_eq('$600', $r['texto'], 'MENTA sin temporada se queda en su base');
t_ok($r['con_temporada'] === false, 'MENTA no se marca como con temporada');

// Sin mapa (hotel sin tarifas, o el motor trono) cae al precio base y NO se cae.
$r = ReservacionController::costoReporteDia($coral, null, []);
t_eq('$1,000', $r['texto'], 'sin mapa de temporada cae al precio base');
t_ok($r['con_temporada'] === false, 'sin temporada no se marca');

/* ── (3) Habitacion VENDIDA: manda el precio CONGELADO ── */

// Aunque la temporada de esa fecha diga otra cosa, la venta no se reescribe.
$vendida = ['precio_hab' => 1200.00, 'es_cortesia_hab' => 0];
$r = ReservacionController::costoReporteDia($coral, $vendida, $temporada);
t_eq('$1,200', $r['texto'], 'vendida usa rh.precio, no el mapa de temporada');
t_ok($r['con_temporada'] === false, 'una venta jamas se marca como cotizacion de temporada');

// El caso que prueba que NO se re-cotiza: venta barata con temporada alta encima.
$vendidaBarata = ['precio_hab' => 800.00, 'es_cortesia_hab' => 0];
t_eq(
    '$800',
    ReservacionController::costoReporteDia($coral, $vendidaBarata, $temporada)['texto'],
    'la venta de $800 se respeta aunque la temporada pida $1,200'
);

// Sin precio congelado (dato viejo) cae al base de la habitacion, NO al de temporada.
$sinPrecio = ['precio_hab' => null, 'es_cortesia_hab' => 0];
t_eq(
    '$1,000',
    ReservacionController::costoReporteDia($coral, $sinPrecio, $temporada)['texto'],
    'vendida sin rh.precio cae al precio_base'
);

/* ── (4) Cortesia: gana sobre cualquier precio ── */

$cortesia = ['precio_hab' => 1200.00, 'es_cortesia_hab' => 1];
$r = ReservacionController::costoReporteDia($coral, $cortesia, $temporada);
t_eq('Cortesía', $r['texto'], 'la cortesia se dice con palabras, no con monto');
t_ok(abs($r['monto']) < 0.001, 'una cortesia no cobra');

$cortesiaStr = ['precio_hab' => null, 'es_cortesia_hab' => '1'];
t_eq(
    'Cortesía',
    ReservacionController::costoReporteDia($coral, $cortesiaStr, $temporada)['texto'],
    'es_cortesia_hab como string "1" tambien cuenta'
);

/* ── (5) La foto del reporte que el hotelero reporto ── */

// Sabado 28-nov-2026, Los Cedros: temporada +200 en confortables y +100 en MAGENTA/MARFIL.
// AMARILLO estaba VENDIDA en $1,200 y CORAL LIBRE: las dos deben decir $1,200.
$amarillo = ['id' => 504, 'numero' => 'AMARILLO', 'precio_base' => 1000.00];
$mapaFoto = [501 => 1200.00, 502 => 1300.00];
$ventaAmarillo = ['precio_hab' => 1200.00, 'es_cortesia_hab' => 0];

t_eq(
    ReservacionController::costoReporteDia($amarillo, $ventaAmarillo, $mapaFoto)['texto'],
    ReservacionController::costoReporteDia($coral, null, $mapaFoto)['texto'],
    'CORAL libre y AMARILLO vendida (mismo grupo de temporada) dicen el MISMO precio'
);
t_eq('$1,300', ReservacionController::costoReporteDia($marfil, null, $mapaFoto)['texto'], 'MARFIL libre dice $1,300');

/* ── (6) E2E con BD: el mapa sale de verdad del motor de tarifas ── */

// Esto es lo que faltaba por completo: el export nunca consultaba el motor.
// Se prueba el cable real (wrapper -> IncrementoTarifa -> incrementos_tarifas).
t_reset_db();
$base = t_seed_base('reporte-dia');
$hotelId = (int) $base['hotel_id'];
$db = Database::getInstance();

// El MOTOR de tarifas exige el bloque contratado desde jul-29 (candado en
// IncrementoTarifa::getIncrementosAplicables, ver GatesMotorTarifasTest): sin
// esta contratacion devuelve cero reglas y el reporte mediria precios base.
// Los Cedros SI lo trae activo en produccion, por eso su reporte debe cobrarlo.
$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('tarifas_dinamicas', 'Tarifas dinamicas', 'test', 0, 'opcional', 149.00, 1, 14, NOW())"
);
$idModuloTarifas = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$hotelId, $idModuloTarifas]
);

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, 'CORAL', 'doble', 1, 1000.00, 'disponible', 1, NOW())",
    [$hotelId]
);
$idCoral = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, 'MENTA', 'sencilla', 1, 600.00, 'disponible', 1, NOW())",
    [$hotelId]
);
$idMenta = (int) $db->lastInsertId();

// Temporada por HABITACION (+$200), vigente en noviembre — el caso real de Los Cedros.
$db->query(
    "INSERT INTO incrementos_tarifas
        (hotel_id, nombre, tipo_incremento, clase, valor_incremento, alcance, habitaciones,
         fecha_inicio, fecha_fin, es_permanente, prioridad, activo, usuario_id, created_at)
     VALUES (?, 'Temporada noviembre', 'monto_fijo', 'incremento', 200.00, 'habitacion', ?,
         '2026-11-01', '2027-02-01', 0, 0, 1, ?, NOW())",
    [$hotelId, json_encode([$idCoral]), (int) $base['usuario_id']]
);

$habs = [
    ['id' => $idCoral, 'numero' => 'CORAL', 'tipo' => 'doble', 'precio_base' => 1000.00],
    ['id' => $idMenta, 'numero' => 'MENTA', 'tipo' => 'sencilla', 'precio_base' => 600.00],
];

$ctrl = new ReservacionController();
$metodo = new ReflectionMethod('ReservacionController', 'preciosTemporadaReporteDia');
$metodo->setAccessible(true);

// Dentro de la vigencia: CORAL sube, MENTA no (no esta en la tarifa).
$mapa = $metodo->invoke($ctrl, $habs, [], '2026-11-28', $hotelId);
t_ok(abs(($mapa[$idCoral] ?? 0) - 1200.0) < 0.001, 'motor: CORAL el 28-nov vale 1200 (1000 + 200)');
t_ok(abs(($mapa[$idMenta] ?? 0) - 600.0) < 0.001, 'motor: MENTA sin temporada se queda en 600');
t_eq('$1,200', ReservacionController::costoReporteDia($habs[0], null, $mapa)['texto'], 'E2E: el reporte dice $1,200 para CORAL libre');

// FUERA de la vigencia (octubre) no debe cobrar temporada: la fecha manda.
$mapaOct = $metodo->invoke($ctrl, $habs, [], '2026-10-15', $hotelId);
t_ok(abs(($mapaOct[$idCoral] ?? 0) - 1000.0) < 0.001, 'motor: en octubre CORAL vuelve a 1000 (fuera de vigencia)');
t_eq('$1,000', ReservacionController::costoReporteDia($habs[0], null, $mapaOct)['texto'], 'E2E: fuera de temporada el reporte dice $1,000');

// Una habitacion VENDIDA no se consulta al motor (su precio ya esta congelado).
$mapaConVenta = $metodo->invoke($ctrl, $habs, [$idCoral => ['precio_hab' => 900.00]], '2026-11-28', $hotelId);
t_ok(!array_key_exists($idCoral, $mapaConVenta), 'una habitacion vendida NO entra al mapa de temporada');
t_ok(array_key_exists($idMenta, $mapaConVenta), 'la libre si entra al mapa');

/* ── (7) El reporte HEREDA el candado de modulo del motor ── */

// Un hotel SIN `tarifas_dinamicas` no cobra sus reglas guardadas en NINGUNA
// pantalla (candado jul-29), y el reporte debe decir lo mismo que la pantalla de
// reservar: precio base. Sale gratis por reusar el motor en vez de re-implementarlo.
//
// Se usa un hotel NUEVO en vez de dar de baja el modulo del anterior a proposito:
// hotel_has_module cachea en estatico + APCu y no expone reset, asi que un toggle
// a media corrida no se observa (da un falso PASS/FAIL segun el orden).
$baseB = t_seed_base('reporte-dia-sin-bloque');
$hotelB = (int) $baseB['hotel_id'];

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa, created_at)
     VALUES (?, 'CORAL', 'doble', 1, 1000.00, 'disponible', 1, NOW())",
    [$hotelB]
);
$idCoralB = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO incrementos_tarifas
        (hotel_id, nombre, tipo_incremento, clase, valor_incremento, alcance, habitaciones,
         fecha_inicio, fecha_fin, es_permanente, prioridad, activo, usuario_id, created_at)
     VALUES (?, 'Temporada noviembre', 'monto_fijo', 'incremento', 200.00, 'habitacion', ?,
         '2026-11-01', '2027-02-01', 0, 0, 1, ?, NOW())",
    [$hotelB, json_encode([$idCoralB]), (int) $baseB['usuario_id']]
);

$habsB = [['id' => $idCoralB, 'numero' => 'CORAL', 'tipo' => 'doble', 'precio_base' => 1000.00]];
$mapaSinBloque = $metodo->invoke($ctrl, $habsB, [], '2026-11-28', $hotelB);

t_ok(abs(($mapaSinBloque[$idCoralB] ?? 0) - 1000.0) < 0.001, 'sin el bloque contratado, CORAL se queda en su precio base');
t_eq(
    '$1,000',
    ReservacionController::costoReporteDia($habsB[0], null, $mapaSinBloque)['texto'],
    'E2E: hotel sin tarifas_dinamicas ve precio base (el reporte no cobra lo que el sistema no cobra)'
);

t_fin();
