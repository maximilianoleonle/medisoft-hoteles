<?php
/**
 * TarifaImpactoService: recalculo opcional de reservaciones existentes al
 * crear/editar un incremento de tarifa.
 *
 * Cubre: candidatas (estado/ventana/alcance), cortesias intactas, descuento
 * conservado, guard de detalle desincronizado (caso prod #1867), saldo con
 * pagos registrados, aplicar() transaccional con re-verificacion de estado,
 * y tarifas no aplicables (descuento / inactiva).
 *
 * Fechas: TODAS relativas al reloj de PHP (el motor de tarifas compara
 * strings de fecha generados en PHP; no se usa CURDATE en este servicio).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "TarifaImpactoTest\n";

t_reset_db();
$base = t_seed_base('tarifa-impacto');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();

// El MOTOR de tarifas exige el bloque contratado desde jul-29 (candado en
// IncrementoTarifa::getIncrementosAplicables, ver GatesMotorTarifasTest): sin
// esta contratación el motor devuelve cero reglas aplicables y todo el recálculo
// de este caso mediría precios base. La dependencia se declara aquí a propósito.
$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('tarifas_dinamicas', 'Tarifas dinamicas', 'test', 0, 'opcional', 149.00, 1, 14, NOW())"
);
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$hotelId, (int) $db->lastInsertId()]
);

$d = function (int $offset): string {
    return date('Y-m-d', strtotime(($offset >= 0 ? '+' : '') . $offset . ' days'));
};

// ── Semillas: habitaciones ───────────────────────────────────────────────
$crearHab = function (string $numero, string $tipo, float $precioBase) use ($db, $hotelId): int {
    $db->query(
        "INSERT INTO habitaciones (hotel_id, numero, tipo, piso, precio_base, estado, activa)
         VALUES (?, ?, ?, 1, ?, 'disponible', 1)",
        [$hotelId, $numero, $tipo, $precioBase]
    );
    return (int) $db->lastInsertId();
};

$hab101 = $crearHab('101', 'sencilla', 500.00); // sencilla base 500
$hab102 = $crearHab('102', 'sencilla', 500.00);
$hab201 = $crearHab('201', 'doble', 800.00);    // doble base 800

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped Impacto', NOW())", [$hotelId]);
$huespedId = (int) $db->lastInsertId();

/**
 * Reservacion con detalle: $habs = [[habitacion_id, precio_detalle, es_cortesia]].
 */
$crearRes = function (int $entradaOff, int $salidaOff, string $estado, array $habs, float $precioTotal, float $descuento = 0) use ($db, $hotelId, $huespedId, $d): int {
    $db->query(
        "INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, descuento_total, estado, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [$hotelId, $huespedId, $d($entradaOff), $d($salidaOff), $precioTotal, $descuento, $estado]
    );
    $resId = (int) $db->lastInsertId();
    foreach ($habs as $h) {
        $db->query(
            "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio, es_cortesia)
             VALUES (?, ?, ?, ?, ?)",
            [$hotelId, $resId, $h[0], $h[1], $h[2] ?? 0]
        );
    }
    return $resId;
};

$crearTarifa = function (string $nombre, string $tipoInc, float $valor, string $alcance, ?string $inicio, ?string $fin, array $extra = []) use ($db, $hotelId, $usuarioId): int {
    $db->query(
        "INSERT INTO incrementos_tarifas
            (hotel_id, nombre, tipo_incremento, clase, valor_incremento, alcance, tipos_habitacion, habitaciones,
             es_permanente, fecha_inicio, fecha_fin, activo, prioridad, usuario_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)",
        [
            $hotelId, $nombre, $tipoInc,
            $extra['clase'] ?? 'incremento',
            $valor, $alcance,
            $extra['tipos'] ?? null,
            $extra['habs'] ?? null,
            $extra['permanente'] ?? 0,
            $inicio, $fin,
            $extra['activo'] ?? 1,
            $usuarioId,
        ]
    );
    return (int) $db->lastInsertId();
};

$precioTotalDe = function (int $resId) use ($db, $hotelId): array {
    $r = $db->query("SELECT precio_total, descuento_total FROM reservaciones WHERE id = ? AND hotel_id = ?", [$resId, $hotelId])->fetch();
    return [(float) $r['precio_total'], (float) $r['descuento_total']];
};
$detalleDe = function (int $resId) use ($db, $hotelId): array {
    $filas = $db->query(
        "SELECT precio, es_cortesia FROM reservacion_habitaciones WHERE reservacion_id = ? AND hotel_id = ? ORDER BY id",
        [$resId, $hotelId]
    )->fetchAll();
    return array_map(function ($f) { return [(float) $f['precio'], (int) $f['es_cortesia']]; }, $filas);
};

$servicio = new TarifaImpactoService();

// ═════════════════════ Fase A: tarifa GLOBAL +10% [d1, d3] ═══════════════
// Noches con tarifa: d1 y d2 (una reservacion d1→d3 son las noches d1,d2).
$t1 = $crearTarifa('Temporada QA', 'porcentaje', 10.0, 'global', $d(1), $d(3));

$resA = $crearRes(1, 3, 'confirmada', [[$hab101, 1000.00]], 1000.00);           // 2 noches, cuadra
$resB = $crearRes(10, 12, 'confirmada', [[$hab101, 1000.00]], 1000.00);         // fuera de ventana
$resC = $crearRes(1, 3, 'cancelada', [[$hab101, 1000.00]], 1000.00);            // cancelada: intocable
$resC2 = $crearRes(1, 3, 'checked_out', [[$hab101, 1000.00]], 1000.00);         // checked_out: intocable
$resD = $crearRes(1, 3, 'confirmada', [[$hab102, 0.00, 1], [$hab201, 1600.00]], 1600.00); // cortesia + doble
$resE = $crearRes(1, 3, 'confirmada', [[$hab101, 1000.00]], 900.00, 100.00);    // descuento 100 conservado
$resF = $crearRes(1, 3, 'confirmada', [[$hab101, 500.00]], 1000.00);            // #1867: detalle 1 noche vs 2 reales
$resCort = $crearRes(1, 3, 'confirmada', [[$hab102, 0.00, 1]], 0.00);           // solo cortesia: fuera
$resG = $crearRes(1, 3, 'confirmada', [[$hab101, 1000.00]], 1000.00);           // carrera de estado

// Pago registrado sobre A: saldo resultante debe reflejarlo.
$db->query(
    "INSERT INTO reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto) VALUES (?, ?, 'efectivo', 400.00)",
    [$hotelId, $resA]
);

$prev = $servicio->previsualizar($t1, $hotelId);
t_ok($prev['aplicable'], 'tarifa incremento activa es aplicable');

$porId = [];
foreach ($prev['afectadas'] as $af) {
    $porId[$af['id']] = $af;
}

t_ok(isset($porId[$resA]), 'A (confirmada en ventana) aparece como afectada');
t_ok(!isset($porId[$resB]), 'B fuera de la vigencia NO aparece');
t_ok(!isset($porId[$resC]), 'cancelada NO aparece');
t_ok(!isset($porId[$resC2]), 'checked_out NO aparece');
t_ok(!isset($porId[$resCort]), 'reservacion solo-cortesia NO aparece');

// A: 2 noches x (500 +10%) = 1100
t_eq(1000.00, $porId[$resA]['precio_actual'], 'A precio actual 1000');
t_eq(1100.00, $porId[$resA]['precio_nuevo'], 'A precio nuevo 1100 (2 noches +10%)');
t_eq(100.00, $porId[$resA]['delta'], 'A delta +100');
t_ok($porId[$resA]['cuadra_detalle'], 'A detalle cuadra con su total');
t_eq(400.00, $porId[$resA]['pagado'], 'A pagado 400 (reservacion_pagos)');
t_eq(600.00, $porId[$resA]['saldo_actual'], 'A saldo actual 600');
t_eq(700.00, $porId[$resA]['saldo_nuevo'], 'A saldo nuevo 700');

// D: cortesia intacta en 0; doble 2 noches x (800 +10%) = 1760
t_ok(isset($porId[$resD]), 'D (cortesia + doble) aparece');
t_eq(1760.00, $porId[$resD]['precio_nuevo'], 'D nuevo total = solo la doble (1760)');
$cortesiaD = null;
foreach ($porId[$resD]['habitaciones'] as $h) {
    if ($h['es_cortesia']) { $cortesiaD = $h; }
}
t_ok($cortesiaD !== null && $cortesiaD['precio_nuevo'] == 0.0, 'la cortesia de D sigue en 0 en el calculo');

// E: descuento 100 conservado → 1100 - 100 = 1000
t_eq(1000.00, $porId[$resE]['precio_nuevo'], 'E nuevo total conserva descuento (1100-100)');
t_eq(100.00, $porId[$resE]['descuento_aplicado'], 'E descuento aplicado se conserva en 100');

// F (#1867): el total actual NO cuadra con su detalle → bandera
t_ok(isset($porId[$resF]), 'F desincronizada aparece como afectada');
t_ok(!$porId[$resF]['cuadra_detalle'], 'F: guard #1867 marca detalle desincronizado');
t_eq(1100.00, $porId[$resF]['precio_nuevo'], 'F nuevo total se re-deriva de las noches reales (1100)');

// ── aplicar() con SUBSET: solo A; E y F intactas ─────────────────────────
$resultado = $servicio->aplicar($t1, [$resA], $hotelId);
t_eq(1, count($resultado['actualizadas']), 'aplicar subset: 1 actualizada');
t_eq(100.00, $resultado['delta_total'], 'aplicar subset: delta total +100');

list($totA) = $precioTotalDe($resA);
t_eq(1100.00, $totA, 'A: precio_total actualizado a 1100');
t_eq([[1100.00, 0]], $detalleDe($resA), 'A: detalle actualizado a 1100');
list($totE) = $precioTotalDe($resE);
t_eq(900.00, $totE, 'E NO seleccionada: intacta');
list($totF) = $precioTotalDe($resF);
t_eq(1000.00, $totF, 'F NO seleccionada: intacta');

// Re-aplicar A: ya sin cambio → omitida, cero actualizadas
$resultado = $servicio->aplicar($t1, [$resA], $hotelId);
t_eq(0, count($resultado['actualizadas']), 're-aplicar A sin cambio: 0 actualizadas');
t_eq('Sin cambio de precio', $resultado['omitidas'][0]['motivo'] ?? '', 're-aplicar A: motivo sin cambio');

// ── aplicar E (descuento) y F (#1867) ────────────────────────────────────
$resultado = $servicio->aplicar($t1, [$resE, $resF], $hotelId);
t_eq(2, count($resultado['actualizadas']), 'aplicar E y F: 2 actualizadas');

list($totE, $descE) = $precioTotalDe($resE);
t_eq(1000.00, $totE, 'E: nuevo total 1000 (descuento restado)');
t_eq(100.00, $descE, 'E: descuento_total INTACTO en 100');

list($totF) = $precioTotalDe($resF);
t_eq(1100.00, $totF, 'F: header sincronizado a 1100');
t_eq([[1100.00, 0]], $detalleDe($resF), 'F: detalle sincronizado a 1100 (noches reales)');

// D: cortesia jamas se toca al aplicar
$servicio->aplicar($t1, [$resD], $hotelId);
t_eq([[0.00, 1], [1760.00, 0]], $detalleDe($resD), 'D: cortesia sigue en 0 y doble en 1760 tras aplicar');

// ── Carrera de estado: cancelada entre previsualizar y aplicar ───────────
$db->query("UPDATE reservaciones SET estado = 'cancelada' WHERE id = ? AND hotel_id = ?", [$resG, $hotelId]);
$resultado = $servicio->aplicar($t1, [$resG], $hotelId);
t_eq(0, count($resultado['actualizadas']), 'G cancelada tras previsualizar: 0 actualizadas');
t_eq(1, count($resultado['omitidas']), 'G cancelada: reportada como omitida');
list($totG) = $precioTotalDe($resG);
t_eq(1000.00, $totG, 'G cancelada: precio intacto');

// ═════════════════ Fase B: alcance TIPO (monto fijo +50) ═════════════════
$db->query("UPDATE incrementos_tarifas SET activo = 0 WHERE id = ?", [$t1]);
$t2 = $crearTarifa('Solo dobles QA', 'monto_fijo', 50.0, 'tipo_habitacion', $d(1), $d(3), ['tipos' => json_encode(['doble'])]);

$resSen = $crearRes(1, 3, 'confirmada', [[$hab101, 1000.00]], 1000.00);
$resDob = $crearRes(1, 3, 'confirmada', [[$hab201, 1600.00]], 1600.00);

$prev = $servicio->previsualizar($t2, $hotelId);
$porId = [];
foreach ($prev['afectadas'] as $af) {
    $porId[$af['id']] = $af;
}
t_ok(!isset($porId[$resSen]), 'alcance tipo: la sencilla NO aparece');
t_ok(isset($porId[$resDob]), 'alcance tipo: la doble aparece');
t_eq(1700.00, $porId[$resDob]['precio_nuevo'], 'doble: 2 noches x (800+50) = 1700');

// ═══════════ Fase C: checked_in en curso (noche pasada intacta) ══════════
$db->query("UPDATE incrementos_tarifas SET activo = 0 WHERE id = ?", [$t2]);
$t3 = $crearTarifa('Desde hoy QA', 'porcentaje', 10.0, 'global', $d(0), $d(3));

// Entro ayer, sale manana: noches = ayer (sin tarifa, 500) + hoy (550) = 1050
$resH = $crearRes(-1, 1, 'checked_in', [[$hab101, 1000.00]], 1000.00);
$db->query(
    "INSERT INTO reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto) VALUES (?, ?, 'efectivo', 2000.00)",
    [$hotelId, $resH]
);

$prev = $servicio->previsualizar($t3, $hotelId);
$porId = [];
foreach ($prev['afectadas'] as $af) {
    $porId[$af['id']] = $af;
}
t_ok(isset($porId[$resH]), 'checked_in en curso aparece como afectada');
t_eq(1050.00, $porId[$resH]['precio_nuevo'], 'checked_in: noche pasada sin tarifa + noche de hoy con tarifa');
t_ok($porId[$resH]['sobrepago'], 'checked_in: pagado 2000 > nuevo 1050 marca sobrepago');
t_eq(0.00, $porId[$resH]['saldo_nuevo'], 'checked_in: saldo nuevo topado en 0');

// ═════════════════════ No aplicables ═════════════════════════════════════
$tDesc = $crearTarifa('Descuento QA', 'porcentaje', 10.0, 'global', $d(1), $d(3), ['clase' => 'descuento']);
$prev = $servicio->previsualizar($tDesc, $hotelId);
t_ok(!$prev['aplicable'], 'tarifa clase descuento NO es aplicable al recalculo');

$tInactiva = $crearTarifa('Inactiva QA', 'porcentaje', 10.0, 'global', $d(1), $d(3), ['activo' => 0]);
$prev = $servicio->previsualizar($tInactiva, $hotelId);
t_ok(!$prev['aplicable'], 'tarifa inactiva NO es aplicable al recalculo');

t_throws(function () use ($servicio, $tDesc, $hotelId) {
    $servicio->aplicar($tDesc, [1], $hotelId);
}, 'descuento', 'aplicar() con tarifa descuento lanza');

t_fin();
