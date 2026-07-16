<?php
/**
 * Parser puro del cupon dictado al copiloto (CopilotoService::parsearCupon).
 * No toca la base: valida tipo/valor (con ambiguedad explicita), codigo y
 * limite de usos sobre texto ya normalizado.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoCuponParserTest\n";

// ── Porcentaje con % ──
$r = CopilotoService::parsearCupon('crea un cupon de 10% para agosto');
t_eq('porcentaje', $r['tipo'], 'porcentaje %: tipo');
t_eq(10.0, $r['valor'], 'porcentaje %: valor 10');
t_ok(!$r['valor_ambiguo'], 'porcentaje %: no ambiguo');
t_eq(null, $r['codigo'], 'porcentaje %: sin codigo dictado');
t_eq(null, $r['limite'], 'porcentaje %: sin limite');

// ── Porcentaje en palabras y con decimal ──
$r = CopilotoService::parsearCupon('haz un cupon de 15 por ciento');
t_eq('porcentaje', $r['tipo'], 'por ciento: tipo');
t_eq(15.0, $r['valor'], 'por ciento: valor');
$r = CopilotoService::parsearCupon('crea un cupon de 12.5%');
t_eq(12.5, $r['valor'], 'decimal: 12.5');

// ── Monto con $ y con "pesos" ──
$r = CopilotoService::parsearCupon('crea un cupon de $100');
t_eq('monto', $r['tipo'], 'monto $: tipo');
t_eq(100.0, $r['valor'], 'monto $: valor');
$r = CopilotoService::parsearCupon('genera un cupon de 1,500 pesos');
t_eq('monto', $r['tipo'], 'pesos: tipo');
t_eq(1500.0, $r['valor'], 'pesos: valor con coma');

// ── Numero pelon = ambiguo (se pregunta, no se adivina) ──
$r = CopilotoService::parsearCupon('crea un cupon de 10 para agosto');
t_eq(null, $r['tipo'], 'pelon: tipo null');
t_eq(10.0, $r['valor'], 'pelon: valor capturado');
t_ok($r['valor_ambiguo'], 'pelon: marcado ambiguo');

// ── Codigo dictado ──
$r = CopilotoService::parsearCupon('crea un cupon de 15% con codigo verano10');
t_eq('VERANO10', $r['codigo'], 'codigo dictado: en mayusculas');
t_eq(15.0, $r['valor'], 'codigo dictado: el 10 del codigo no ensucia el valor');
t_ok(!$r['valor_ambiguo'], 'codigo dictado: no ambiguo');

// ── Limite de usos (las dos formas) ──
$r = CopilotoService::parsearCupon('crea un cupon de 10% para agosto con 20 usos');
t_eq(20, $r['limite'], 'limite "20 usos"');
t_eq(10.0, $r['valor'], 'limite: no ensucia el valor');
$r = CopilotoService::parsearCupon('crea un cupon de $100 limite de 50');
t_eq(50, $r['limite'], 'limite "limite de 50"');
t_eq(100.0, $r['valor'], 'limite de 50: valor intacto');

// ── Sin numero ──
$r = CopilotoService::parsearCupon('crea un cupon para agosto');
t_eq(null, $r['valor'], 'sin numero: valor null');

t_fin();
