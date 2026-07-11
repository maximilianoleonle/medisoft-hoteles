<?php
/**
 * Invariantes de dinero de Caja (memoria: caja-cortes-concurrencia, 875c51bd):
 *  1. Solo puede existir UN corte abierto por caja.
 *  2. Ningun movimiento puede caer en un corte cerrado.
 *  3. El cierre es atomico y sus totales cuadran con los movimientos.
 *  4. Un corte cerrado no puede volver a cerrarse.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CajaCortesTest\n";

t_reset_db();
$base = t_seed_base('caja-test');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelId);
$cajaPrincipal = $caja->obtenerCajaPrincipal();
t_ok(!empty($cajaPrincipal['id']), 'existe caja principal del hotel');
$cajaId = (int) $cajaPrincipal['id'];

// ── 1. Un solo corte abierto por caja ────────────────────────────────────
$apertura = $caja->abrirCaja($cajaId, 1000.00, $usuarioId);
t_ok(!empty($apertura['success']), 'abrir caja con fondo inicial 1000');
$corteId = (int) $apertura['corte_id'];

$reapertura = $caja->abrirCaja($cajaId, 500.00, $usuarioId);
t_ok(empty($reapertura['success']), 'segundo corte en la misma caja RECHAZADO');

// ── 3a. Movimientos dentro del corte abierto ─────────────────────────────
$mov = new MovimientoCaja();
$r1 = $mov->registrarMovimiento(['tipo' => 'ingreso', 'monto' => 100.00, 'metodo_pago' => 'efectivo', 'descripcion' => 'Ingreso efectivo test', 'categoria' => 'Otros ingresos']);
$r2 = $mov->registrarMovimiento(['tipo' => 'ingreso', 'monto' => 200.00, 'metodo_pago' => 'tarjeta', 'descripcion' => 'Ingreso tarjeta test', 'categoria' => 'Otros ingresos']);
$r3 = $mov->registrarMovimiento(['tipo' => 'gasto', 'monto' => 30.00, 'metodo_pago' => 'efectivo', 'descripcion' => 'Gasto efectivo test', 'categoria' => 'Insumos']);
t_ok(!empty($r1['success']) && !empty($r2['success']) && !empty($r3['success']), 'tres movimientos registrados en corte abierto');

// ── 3b. Cierre atomico con totales que cuadran ──────────────────────────
// Efectivo esperado = 1000 inicial + 100 ingreso - 30 gasto = 1070
$cierre = $caja->cerrarCaja($corteId, 1070.00, 'Cierre de prueba', $usuarioId);
t_ok(!empty($cierre['success']), 'cierre de corte exitoso');
t_eq(0.0, round((float) ($cierre['diferencia'] ?? -1), 2), 'diferencia 0 al contar el efectivo esperado (1070)');

$db = Database::getInstance();
$fila = $db->query('SELECT * FROM cortes_caja WHERE id = ?', [$corteId])->fetch();
t_eq('cerrado', $fila['estado'], 'estado del corte = cerrado');
t_eq(100.00, round((float) $fila['total_ingresos_efectivo'], 2), 'total ingresos efectivo = 100');
t_eq(200.00, round((float) $fila['total_ingresos_tarjeta'], 2), 'total ingresos tarjeta = 200');
t_eq(30.00, round((float) $fila['total_gastos_efectivo'], 2), 'total gastos efectivo = 30');
t_eq(1070.00, round((float) $fila['efectivo_esperado'], 2), 'efectivo esperado = 1070');

// ── 2. Ningun movimiento cae en corte cerrado ────────────────────────────
$tardio = $mov->registrarMovimiento(['tipo' => 'ingreso', 'monto' => 999.00, 'metodo_pago' => 'efectivo', 'descripcion' => 'Movimiento tardio', 'categoria' => 'Otros ingresos']);
t_ok(empty($tardio['success']), 'movimiento tras el cierre RECHAZADO');

$colados = $db->query('SELECT COUNT(*) c FROM movimientos_caja WHERE corte_id = ?', [$corteId])->fetch();
t_eq(3, (int) $colados['c'], 'el corte cerrado conserva exactamente 3 movimientos');

// ── 4. Doble cierre rechazado ────────────────────────────────────────────
$recierre = $caja->cerrarCaja($corteId, 1070.00, 'Doble cierre', $usuarioId);
t_ok(empty($recierre['success']), 'segundo cierre del mismo corte RECHAZADO');

// ── Reapertura legitima despues del cierre ───────────────────────────────
$apertura2 = $caja->abrirCaja($cajaId, 1070.00, $usuarioId);
t_ok(!empty($apertura2['success']), 'nuevo corte se abre bien tras cerrar el anterior');

t_fin();
