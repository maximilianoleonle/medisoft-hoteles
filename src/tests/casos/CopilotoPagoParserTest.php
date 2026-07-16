<?php
/**
 * Parser puro del pago a proveedor dictado al copiloto
 * (CopilotoService::parsearPagoProveedor). No toca la base: valida monto
 * (null = saldar la cuenta) y metodo sobre texto ya normalizado.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoPagoParserTest\n";

// ── Tipico: monto + efectivo default ──
$r = CopilotoService::parsearPagoProveedor('pagale 500 al proveedor garcia');
t_eq(500.0, $r['monto'], 'tipico: monto 500');
t_eq('efectivo', $r['metodo'], 'tipico: metodo default efectivo');

// ── Sin monto: saldar la cuenta ──
$r = CopilotoService::parsearPagoProveedor('pagale al proveedor garcia');
t_eq(null, $r['monto'], 'sin monto: null (saldar)');

// ── Formato dinero ──
$r = CopilotoService::parsearPagoProveedor('pagale $1,250.50 a garcia');
t_eq(1250.5, $r['monto'], 'formato dinero: 1,250.50');

// ── Varios numeros: gana el que trae $ ──
$r = CopilotoService::parsearPagoProveedor('paga 2 facturas de $800 a garcia');
t_eq(800.0, $r['monto'], 'con $: gana el numero con signo');

// ── Varios numeros sin $: gana el mayor ──
$r = CopilotoService::parsearPagoProveedor('paga 2 facturas de 800 a garcia');
t_eq(800.0, $r['monto'], 'sin $: gana el mayor');

// ── Metodos dictados (aqui si se aceptan: la referencia la genera el motor) ──
$r = CopilotoService::parsearPagoProveedor('pagale 500 a garcia por transferencia');
t_eq('transferencia', $r['metodo'], 'metodo transferencia');
$r = CopilotoService::parsearPagoProveedor('pagale 500 a garcia con tarjeta');
t_eq('tarjeta', $r['metodo'], 'metodo tarjeta');

// ── Monto cero no vale ──
$r = CopilotoService::parsearPagoProveedor('pagale 0 al proveedor garcia');
t_eq(null, $r['monto'], 'monto 0: null');

t_fin();
