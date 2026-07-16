<?php
/**
 * Parser puro del gasto dictado al copiloto (CopilotoService::parsearGasto).
 * No toca la base: valida monto, concepto y metodo sobre texto ya normalizado
 * (minusculas, sin acentos), que es como se lo pasa detectarAccionGasto.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoGastoParserTest\n";

// ── Frase tipica: monto despues de "gasto de" ──
$r = CopilotoService::parsearGasto('registra un gasto de 450 de plomeria');
t_eq(450.0, $r['monto'], 'tipica: monto 450');
t_eq('plomeria', $r['concepto'], 'tipica: concepto plomeria');
t_eq('efectivo', $r['metodo'], 'tipica: metodo default efectivo');

// ── Concepto antes del monto ──
$r = CopilotoService::parsearGasto('anota un gasto de plomeria de 450');
t_eq(450.0, $r['monto'], 'concepto primero: monto 450');
t_eq('plomeria', $r['concepto'], 'concepto primero: concepto plomeria');

// ── Monto con formato de dinero ($ y comas) ──
$r = CopilotoService::parsearGasto('registra un gasto de $1,250.50 por compra de focos');
t_eq(1250.5, $r['monto'], 'formato dinero: 1,250.50');
t_eq('compra de focos', $r['concepto'], 'formato dinero: concepto limpio');

// ── Cantidad + precio: gana el numero con $ ──
$r = CopilotoService::parsearGasto('registra un gasto de 2 cubetas de pintura por $800');
t_eq(800.0, $r['monto'], 'con $: gana el numero con signo');

// ── Cantidad + precio sin $: gana el mayor ──
$r = CopilotoService::parsearGasto('registra un gasto de 2 focos por 100');
t_eq(100.0, $r['monto'], 'sin $: gana el mayor (dinero vs cantidad)');

// ── Mencion del metodo no ensucia el concepto ──
$r = CopilotoService::parsearGasto('apunta un gasto de 300 de garrafones en efectivo');
t_eq(300.0, $r['monto'], 'efectivo explicito: monto');
t_eq('garrafones', $r['concepto'], 'efectivo explicito: concepto sin metodo');
t_eq('efectivo', $r['metodo'], 'efectivo explicito: metodo');

// ── Tarjeta y transferencia se detectan (el chat las rechaza) ──
$r = CopilotoService::parsearGasto('registra un gasto de 500 de despensa con tarjeta');
t_eq('tarjeta', $r['metodo'], 'metodo tarjeta detectado');
$r = CopilotoService::parsearGasto('registra un gasto de 500 de renta por transferencia');
t_eq('transferencia', $r['metodo'], 'metodo transferencia detectado');

// ── Sin monto ──
$r = CopilotoService::parsearGasto('registra un gasto de plomeria');
t_eq(null, $r['monto'], 'sin numero: monto null');

// ── Sin concepto ──
$r = CopilotoService::parsearGasto('registra un gasto de 450');
t_eq(450.0, $r['monto'], 'sin concepto: monto si');
t_eq(null, $r['concepto'], 'sin concepto: concepto null');

// ── Monto cero no vale ──
$r = CopilotoService::parsearGasto('registra un gasto de 0 de propinas');
t_eq(null, $r['monto'], 'monto 0: null');

// ── Signos de pregunta/exclamacion al final no ensucian ──
$r = CopilotoService::parsearGasto('registra un gasto de 450 de plomeria!');
t_eq('plomeria', $r['concepto'], 'signo final: concepto limpio');
