<?php
/**
 * Saneador del estado del flujo campo-por-campo del copiloto
 * (CopilotoService::sanearFlujo). Puro: no toca la base. El estado viaja
 * por el cliente, asi que cualquier campo fuera de contrato invalida el
 * flujo completo (null) en vez de dejar pasar datos raros.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoFlujoSaneadorTest\n";

$valido = ['t' => 'reserva', 'paso' => 'habitacion', 'fe' => '2026-12-20', 'fs' => '2026-12-22',
    'hab_id' => 5, 'hab_num' => '204', 'nombre' => 'juan perez', 'nuevo' => 1, 'tel' => '5512345678'];

// ── Roundtrip valido (como JSON del widget) ──
$f = CopilotoService::sanearFlujo(json_encode($valido));
t_ok(is_array($f), 'valido: pasa');
t_eq('habitacion', $f['paso'], 'valido: paso');
t_eq('2026-12-20', $f['fe'], 'valido: fecha entrada');
t_eq(5, $f['hab_id'], 'valido: habitacion id');
t_eq(1, $f['nuevo'], 'valido: flag nuevo');

// ── Basura de tipo ──
t_eq(null, CopilotoService::sanearFlujo(null), 'null: invalido');
t_eq(null, CopilotoService::sanearFlujo(''), 'vacio: invalido');
t_eq(null, CopilotoService::sanearFlujo('no json'), 'string roto: invalido');

// ── Tipo o paso fuera de contrato ──
t_eq(null, CopilotoService::sanearFlujo(['t' => 'otra_cosa', 'paso' => 'fechas']), 'tipo desconocido: invalido');
t_eq(null, CopilotoService::sanearFlujo(['t' => 'reserva', 'paso' => 'hackear']), 'paso desconocido: invalido');
t_eq(null, CopilotoService::sanearFlujo(['t' => 'reserva']), 'sin paso: invalido');

// ── Fechas fuera de formato o incoherentes ──
t_eq(null, CopilotoService::sanearFlujo(['t' => 'reserva', 'paso' => 'nombre', 'fe' => '20/12/2026']), 'fecha con otro formato: invalido');
t_eq(null, CopilotoService::sanearFlujo(['t' => 'reserva', 'paso' => 'nombre', 'fe' => '2026-12-22', 'fs' => '2026-12-20']), 'salida antes de entrada: invalido');

// ── Numeros negativos se normalizan a 0 y flags a 0/1 ──
$f = CopilotoService::sanearFlujo(['t' => 'reserva', 'paso' => 'fechas', 'hab_id' => -7, 'nuevo' => 5, 'nom_skip' => 'si']);
t_eq(0, $f['hab_id'], 'hab_id negativo: 0');
t_eq(0, $f['nuevo'], 'flag no-1: 0');
t_eq(0, $f['nom_skip'], 'flag string: 0');

// ── Topes de texto y telefono solo con caracteres de telefono ──
$f = CopilotoService::sanearFlujo(['t' => 'reserva', 'paso' => 'telefono',
    'nombre' => str_repeat('a', 200), 'tel' => 'abc55-12x34']);
t_eq(60, mb_strlen($f['nombre']), 'nombre: tope 60');
t_eq('55-1234', $f['tel'], 'telefono: solo digitos/guiones');

t_fin();
