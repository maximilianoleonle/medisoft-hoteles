<?php
/**
 * Festivos MX (helper puro, sin base): computus de Semana Santa con 3 anios
 * conocidos, corrimientos a lunes por ley (LFT art. 74) y rangos que cruzan
 * el fin de anio.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/festivos_mx.php';

echo "FestivosMxTest\n";

// ── Computus: Domingo de Pascua de 3 anios conocidos ──
t_eq('2025-04-20', festivos_mx_pascua(2025), 'Pascua 2025 = 20 de abril');
t_eq('2026-04-05', festivos_mx_pascua(2026), 'Pascua 2026 = 5 de abril');
t_eq('2027-03-28', festivos_mx_pascua(2027), 'Pascua 2027 = 28 de marzo');

// ── Corrimientos a lunes (2026) ──
$anio2026 = festivos_mx_del_anio(2026);
$porNombre = [];
foreach ($anio2026 as $f) {
    $porNombre[$f['nombre']] = $f;
}
t_eq('2026-02-02', $porNombre['Puente Dia de la Constitucion']['desde'] ?? null, '5 feb 2026 se corre al lunes 2 feb');
t_eq('2026-03-16', $porNombre['Puente Natalicio de Benito Juarez']['desde'] ?? null, '21 mar 2026 se corre al lunes 16 mar');
t_eq('2026-11-16', $porNombre['Puente Revolucion Mexicana']['desde'] ?? null, '20 nov 2026 se corre al lunes 16 nov');

// ── Semana Santa 2026: 2 semanas alrededor de Pascua (29 mar - 12 abr) ──
$ss = $porNombre['Semana Santa y Pascua (vacaciones)'] ?? null;
t_eq('2026-03-29', $ss['desde'] ?? null, 'Semana Santa 2026 inicia en Domingo de Ramos (29 mar)');
t_eq('2026-04-12', $ss['hasta'] ?? null, 'Semana Santa 2026 termina una semana despues de Pascua (12 abr)');

// ── Fijos sin corrimiento ──
t_eq('2026-09-16', $porNombre['Dia de la Independencia']['desde'] ?? null, '16 sep no se corre');
t_eq('2026-12-12', $porNombre['Dia de la Virgen de Guadalupe']['desde'] ?? null, '12 dic presente');
t_eq('2026-12-25', $porNombre['Navidad']['desde'] ?? null, '25 dic presente');

// ── Rango: 15 jul - 13 oct 2026 solo debe traer Independencia ──
$rango = festivos_mx_en_rango('2026-07-15', '2026-10-13');
t_eq(1, count($rango), 'jul-oct 2026: solo un festivo (Independencia)');
t_eq('Dia de la Independencia', $rango[0]['nombre'] ?? null, 'ese festivo es la Independencia');

// ── Rango que cruza fin de anio: trae dic del 2026 y ene-feb del 2027 ──
$rango = festivos_mx_en_rango('2026-12-01', '2027-02-28');
$nombres = array_map(static function ($f) { return $f['nombre']; }, $rango);
t_ok(in_array('Dia de la Virgen de Guadalupe', $nombres, true), 'cruce de anio: 12 dic 2026 presente');
t_ok(in_array('Navidad', $nombres, true), 'cruce de anio: Navidad presente');
t_ok(in_array('Ano Nuevo', $nombres, true), 'cruce de anio: 1 ene 2027 presente');
t_ok(in_array('Puente Dia de la Constitucion', $nombres, true), 'cruce de anio: puente de febrero 2027 presente');

// ── Semana Santa que toca el rango parcialmente ──
$rango = festivos_mx_en_rango('2026-04-10', '2026-05-05');
$nombres = array_map(static function ($f) { return $f['nombre']; }, $rango);
t_ok(in_array('Semana Santa y Pascua (vacaciones)', $nombres, true), 'rango parcial: Semana Santa se incluye si la toca');
t_ok(in_array('Dia del Trabajo', $nombres, true), 'rango parcial: 1 may presente');

// ── Rango invertido: vacio, sin explotar ──
t_eq(0, count(festivos_mx_en_rango('2026-05-01', '2026-04-01')), 'rango invertido devuelve vacio');

t_fin();
