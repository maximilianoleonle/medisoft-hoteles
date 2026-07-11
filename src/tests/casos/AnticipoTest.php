<?php
/**
 * Invariantes de anticipos (memoria: anticipos-reservacion):
 *  1. El anticipo exige corte de Caja abierto y crea el par
 *     movimiento_caja + reservacion_abonos (dinero real, no nota).
 *  2. Un anticipo jamas puede exceder el saldo pendiente (anti doble cobro).
 *  3. Con saldo en cero no se aceptan mas anticipos.
 *  4. Un abono solo puede reversarse una vez.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "AnticipoTest\n";

t_reset_db();
$base = t_seed_base('anticipo-test');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$reservacionId = t_seed_reservacion($hotelId, 1500.00);

$servicio = new AnticipoService();

// ── 1a. Sin corte abierto: rechazado ─────────────────────────────────────
t_throws(
    fn () => $servicio->registrar($hotelId, $reservacionId, ['monto' => 100, 'metodo_pago' => 'efectivo'], $usuarioId),
    'corte de Caja abierto',
    'anticipo sin caja abierta RECHAZADO'
);

// Abrir caja
$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelId);
$cajaId = (int) $caja->obtenerCajaPrincipal()['id'];
$apertura = $caja->abrirCaja($cajaId, 500.00, $usuarioId);
t_ok(!empty($apertura['success']), 'caja abierta para anticipos');

// ── 1b. Anticipo valido crea dinero real ─────────────────────────────────
$detalle = $servicio->registrar($hotelId, $reservacionId, ['monto' => 600, 'metodo_pago' => 'efectivo'], $usuarioId);
t_eq(600.00, $detalle['monto'], 'anticipo de 600 registrado');
t_eq(900.00, $detalle['saldo_posterior'], 'saldo pasa de 1500 a 900');

$db = Database::getInstance();
$movil = $db->query('SELECT tipo, monto, corte_id FROM movimientos_caja WHERE id = ?', [$detalle['movimiento_caja_id']])->fetch();
t_ok($movil && $movil['tipo'] === 'ingreso' && round((float) $movil['monto'], 2) === 600.00, 'existe movimiento de caja ligado (ingreso 600)');
$abono = $db->query('SELECT monto, movimiento_caja_id FROM reservacion_abonos WHERE id = ?', [$detalle['abono_id']])->fetch();
t_ok($abono && (int) $abono['movimiento_caja_id'] === (int) $detalle['movimiento_caja_id'], 'abono ligado al movimiento de caja');

// ── 2. Anticipo mayor al saldo: rechazado ────────────────────────────────
t_throws(
    fn () => $servicio->registrar($hotelId, $reservacionId, ['monto' => 901, 'metodo_pago' => 'efectivo'], $usuarioId),
    'no puede ser mayor al saldo',
    'anticipo mayor al saldo (901 > 900) RECHAZADO'
);

// ── 3. Saldo a cero y anti sobrecobro ────────────────────────────────────
$resto = $servicio->registrar($hotelId, $reservacionId, ['monto' => 900, 'metodo_pago' => 'tarjeta', 'tipo_tarjeta' => 'debito'], $usuarioId);
t_eq(0.00, $resto['saldo_posterior'], 'saldo liquidado en 0');

t_throws(
    fn () => $servicio->registrar($hotelId, $reservacionId, ['monto' => 1, 'metodo_pago' => 'efectivo'], $usuarioId),
    'no tiene saldo pendiente',
    'anticipo extra con saldo 0 RECHAZADO (sin doble cobro)'
);

// ── 4. Reverso unico ─────────────────────────────────────────────────────
$reverso = $servicio->reversar($hotelId, $reservacionId, (int) $detalle['abono_id'], $usuarioId);
t_ok(is_array($reverso), 'reverso del abono de 600 aplicado');

$saldoTras = $servicio->evaluar($hotelId, $reservacionId);
t_eq(600.00, $saldoTras['saldo'], 'reverso restaura saldo pendiente a 600');

t_throws(
    fn () => $servicio->reversar($hotelId, $reservacionId, (int) $detalle['abono_id'], $usuarioId),
    '',
    'segundo reverso del MISMO abono RECHAZADO'
);

t_fin();
