<?php
/**
 * GuardianPatrones (bloque vigilancia_financiera): reglas deterministas de
 * comportamiento por usuario. Casos sembrados:
 *  - usuario normal            -> NO aparece en ninguna alerta
 *  - descuentos atipicos       -> hallazgo vs la mediana del hotel
 *  - cancelacion post check-in con efectivo -> hallazgo alta
 *  - movimientos a las 3 AM    -> hallazgo fuera de horario
 *  - cortes descuadrados x3    -> hallazgo de recurrencia
 *  - reversiones concentradas  -> hallazgo de concentracion
 *  - hotel con poco volumen    -> NO debe alertar (falsos positivos)
 *  - tenancy: hotel vecino no ve nada de esto
 */

require_once __DIR__ . '/../bootstrap.php';

echo "GuardianPatronesTest\n";

t_reset_db();
$base = t_seed_base('guardian-test');
$hotelId = $base['hotel_id'];

$db = Database::getInstance();

function g_usuario(string $slug): int
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
         VALUES (?, 'x', ?, 'recepcionista', 1, NOW())",
        ['g_' . $slug, 'Usuario ' . ucfirst($slug)]
    );
    return (int) $db->lastInsertId();
}

function g_reserva(int $hotelId, int $usuarioId, float $precioFinal, float $descuento, array $extra = []): int
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped G', NOW())",
        [$hotelId]
    );
    $huespedId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO reservaciones
            (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, descuento_total,
             estado, usuario_registro_id, hora_entrada, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $hotelId,
            $huespedId,
            $extra['fecha_entrada'] ?? date('Y-m-d', strtotime('-5 days')),
            $extra['fecha_salida'] ?? date('Y-m-d', strtotime('-3 days')),
            $precioFinal,
            $descuento,
            $extra['estado'] ?? 'confirmada',
            $usuarioId,
            $extra['hora_entrada'] ?? null,
            $extra['created_at'] ?? date('Y-m-d H:i:s', strtotime('-5 days')),
            $extra['updated_at'] ?? date('Y-m-d H:i:s', strtotime('-5 days')),
        ]
    );
    return (int) $db->lastInsertId();
}

function g_mov(int $hotelId, int $usuarioId, string $tipo, string $categoria, float $monto, array $extra = []): int
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO movimientos_caja
            (hotel_id, tipo, categoria, descripcion, monto, metodo_pago, referencia,
             reservacion_id, usuario_id, corte_id, editado, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $hotelId,
            $tipo,
            $categoria,
            $extra['descripcion'] ?? 'Mov guardian test',
            $monto,
            $extra['metodo_pago'] ?? 'efectivo',
            $extra['referencia'] ?? null,
            $extra['reservacion_id'] ?? null,
            $usuarioId,
            $extra['corte_id'] ?? null,
            $extra['editado'] ?? 0,
            $extra['created_at'] ?? date('Y-m-d H:i:s', strtotime('-4 days 12:00')),
        ]
    );
    return (int) $db->lastInsertId();
}

function g_corte(int $hotelId, int $cajaId, int $usuarioId, float $diferencia, string $fechaCierre): int
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO cortes_caja
            (hotel_id, caja_id, fecha_apertura, fecha_cierre, monto_inicial,
             efectivo_esperado, efectivo_contado, diferencia, estado,
             usuario_apertura_id, usuario_cierre_id, created_at)
         VALUES (?, ?, ?, ?, 1000, 1000, ?, ?, 'cerrado', ?, ?, NOW())",
        [$hotelId, $cajaId, $fechaCierre, $fechaCierre, 1000 + $diferencia, $diferencia, $usuarioId, $usuarioId]
    );
    return (int) $db->lastInsertId();
}

function g_alerta(array $reporte, string $codigo): array
{
    foreach ($reporte['alertas'] as $a) {
        if ($a['codigo'] === $codigo) {
            return $a;
        }
    }
    return [];
}

function g_usuario_en_alerta(array $alerta, int $usuarioId): ?array
{
    foreach ($alerta['usuarios'] ?? [] as $u) {
        if ((int) $u['usuario_id'] === $usuarioId) {
            return $u;
        }
    }
    return null;
}

// ── Seed hotel principal (volumen suficiente) ────────────────────────────

$normal = g_usuario('normal');
$descuentero = g_usuario('descuentos');
$cancelador = g_usuario('cancela');
$nocturno = g_usuario('nocturno');
$descuadrado = g_usuario('descuadra');
$reversor = g_usuario('reversa');

// Usuario normal y un segundo usuario sano: reservas con descuento chico.
for ($i = 0; $i < 6; $i++) {
    g_reserva($hotelId, $normal, 1000.00, 0.00);
}
$sano2 = g_usuario('sano2');
for ($i = 0; $i < 6; $i++) {
    g_reserva($hotelId, $sano2, 980.00, 20.00); // 2% de descuento
}

// Descuentos atipicos: 6 reservas con 30% de descuento.
for ($i = 0; $i < 6; $i++) {
    g_reserva($hotelId, $descuentero, 700.00, 300.00);
}

// Cancelaciones post check-in con cobro en efectivo previo (x2).
for ($i = 0; $i < 2; $i++) {
    $rid = g_reserva($hotelId, $normal, 800.00, 0.00, [
        'estado' => 'cancelada',
        'hora_entrada' => '14:00:00',
        'fecha_entrada' => date('Y-m-d', strtotime('-6 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days 18:00')),
    ]);
    g_mov($hotelId, $cancelador, 'ingreso', 'Hospedaje', 800.00, [
        'metodo_pago' => 'efectivo',
        'reservacion_id' => $rid,
        'created_at' => date('Y-m-d H:i:s', strtotime('-6 days 15:00')),
    ]);
    // La devolucion (gasto) atribuye la cancelacion a quien la proceso.
    g_mov($hotelId, $cancelador, 'gasto', 'Devolución', 800.00, [
        'metodo_pago' => 'efectivo',
        'reservacion_id' => $rid,
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days 18:05')),
    ]);
}

// Movimientos a las 3 AM (fuera del horario default 06:00-23:59) x3.
for ($i = 1; $i <= 3; $i++) {
    g_mov($hotelId, $nocturno, 'ingreso', 'Hospedaje', 350.00, [
        'created_at' => date('Y-m-d', strtotime("-{$i} days")) . ' 03:00:00',
    ]);
}

// Cortes con diferencia recurrente: 3 cierres con faltante de $60.
$db->query("INSERT INTO cajas (hotel_id, nombre, activa, created_at) VALUES (?, 'Caja G', 1, NOW())", [$hotelId]);
$cajaId = (int) $db->lastInsertId();
for ($i = 1; $i <= 3; $i++) {
    g_corte($hotelId, $cajaId, $descuadrado, -60.00, date('Y-m-d', strtotime("-{$i} days")) . ' 22:00:00');
}

// Reversiones concentradas: 5 de 6 reversiones del hotel son del mismo usuario.
for ($i = 0; $i < 5; $i++) {
    g_mov($hotelId, $reversor, 'gasto', 'Reversion Cobro CxC', 120.00, [
        'referencia' => 'REV-CXC-9-MOV-' . (100 + $i),
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days 13:00')),
    ]);
}
g_mov($hotelId, $normal, 'gasto', 'Reversion Cobro CxC', 90.00, [
    'referencia' => 'REV-CXC-9-MOV-200',
    'created_at' => date('Y-m-d H:i:s', strtotime('-3 days 14:00')),
]);

// ── Reporte del hotel principal ──────────────────────────────────────────

$modelo = new GuardianPatrones();
$reporte = $modelo->reporteReadOnlyPorHotel($hotelId);

t_ok($reporte['volumen']['suficiente'], 'volumen del hotel principal es suficiente');

// Regla 1: descuentos atipicos.
$a1 = g_alerta($reporte, 'GD_DESCUENTOS_ATIPICOS');
t_ok((int) $a1['conteo'] >= 1, 'descuentos atipicos: hay hallazgo');
t_ok(g_usuario_en_alerta($a1, $descuentero) !== null, 'descuentos atipicos: señala al usuario del 30%');
t_ok(g_usuario_en_alerta($a1, $normal) === null, 'descuentos atipicos: el usuario normal NO aparece');
t_ok(g_usuario_en_alerta($a1, $sano2) === null, 'descuentos atipicos: el usuario con 2% NO aparece');

// Regla 2: cancelaciones post check-in con efectivo.
$a2 = g_alerta($reporte, 'GD_CANCELACIONES_SOSPECHOSAS');
$u2 = g_usuario_en_alerta($a2, $cancelador);
t_ok($u2 !== null, 'cancelaciones: señala a quien proceso las devoluciones');
t_eq('alta', (string) ($u2['severidad'] ?? ''), 'cancelaciones: severidad alta con 2 casos de efectivo');
t_eq(2, (int) ($u2['metrica']['con_efectivo'] ?? 0), 'cancelaciones: 2 casos con cobro en efectivo previo');

// Regla 3: fuera de horario.
$a3 = g_alerta($reporte, 'GD_FUERA_HORARIO');
$u3 = g_usuario_en_alerta($a3, $nocturno);
t_ok($u3 !== null, 'fuera de horario: señala al usuario de las 3 AM');
t_eq(3, (int) ($u3['metrica']['casos'] ?? 0), 'fuera de horario: 3 movimientos detectados');
t_ok(g_usuario_en_alerta($a3, $normal) === null, 'fuera de horario: el usuario normal NO aparece');

// Regla 4: cortes con diferencia recurrente.
$a4 = g_alerta($reporte, 'GD_CORTES_DIFERENCIA');
$u4 = g_usuario_en_alerta($a4, $descuadrado);
t_ok($u4 !== null, 'cortes descuadrados: señala la recurrencia');
t_eq('alta', (string) ($u4['severidad'] ?? ''), 'cortes descuadrados: 3 faltantes = severidad alta');

// Regla 5: reversiones concentradas.
$a5 = g_alerta($reporte, 'GD_REVERSIONES_CONCENTRADAS');
t_ok(g_usuario_en_alerta($a5, $reversor) !== null, 'reversiones: señala la concentracion (5 de 6)');
t_ok(g_usuario_en_alerta($a5, $normal) === null, 'reversiones: el usuario normal (1 de 6) NO aparece');

// Por persona: radiografia con contexto del hotel.
t_ok(!empty($reporte['por_persona']['usuarios']), 'por persona: hay tarjetas de usuario');
t_ok(isset($reporte['por_persona']['promedio_hotel']['descuento_pct']), 'por persona: trae el promedio del hotel');

// ── Hotel chico: poco volumen => NO debe alertar ─────────────────────────

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Chico', 'hotel-chico-g', 1, NOW())");
$hotelChico = (int) $db->lastInsertId();
$chicoUser = g_usuario('chico');

// 1 reserva con descuentazo + 2 movimientos a las 3 AM: sin volumen minimo.
g_reserva($hotelChico, $chicoUser, 500.00, 400.00);
for ($i = 1; $i <= 2; $i++) {
    g_mov($hotelChico, $chicoUser, 'ingreso', 'Hospedaje', 200.00, [
        'created_at' => date('Y-m-d', strtotime("-{$i} days")) . ' 03:10:00',
    ]);
}

$reporteChico = $modelo->reporteReadOnlyPorHotel($hotelChico);
t_ok(!$reporteChico['volumen']['suficiente'], 'hotel chico: volumen insuficiente detectado');
t_eq(0, (int) $reporteChico['totales']['hallazgos'], 'hotel chico: CERO hallazgos (sin falsas alarmas)');
foreach ($reporteChico['alertas'] as $a) {
    t_eq('ok', (string) $a['severidad'], 'hotel chico: regla ' . $a['codigo'] . ' queda en ok');
}

// ── Tenancy: el hotel vecino no ve nada del principal ────────────────────

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Vecino', 'hotel-vecino-g', 1, NOW())");
$hotelVecino = (int) $db->lastInsertId();
$reporteVecino = $modelo->reporteReadOnlyPorHotel($hotelVecino);
t_eq(0, (int) $reporteVecino['totales']['hallazgos'], 'tenancy: hotel vecino sin hallazgos ajenos');
t_eq(0, (int) $reporteVecino['volumen']['movimientos'], 'tenancy: hotel vecino sin movimientos ajenos');

// ── Read-only: el reporte no escribio en tablas de dinero ────────────────

$antes = [
    'mov' => (int) $db->query('SELECT COUNT(*) c FROM movimientos_caja')->fetch()['c'],
    'cortes' => (int) $db->query('SELECT COUNT(*) c FROM cortes_caja')->fetch()['c'],
    'res' => (int) $db->query('SELECT COUNT(*) c FROM reservaciones')->fetch()['c'],
];
$modelo->reporteReadOnlyPorHotel($hotelId);
$despues = [
    'mov' => (int) $db->query('SELECT COUNT(*) c FROM movimientos_caja')->fetch()['c'],
    'cortes' => (int) $db->query('SELECT COUNT(*) c FROM cortes_caja')->fetch()['c'],
    'res' => (int) $db->query('SELECT COUNT(*) c FROM reservaciones')->fetch()['c'],
];
t_ok($antes === $despues, 'read-only: cero escrituras en caja/cortes/reservaciones');

t_fin();
