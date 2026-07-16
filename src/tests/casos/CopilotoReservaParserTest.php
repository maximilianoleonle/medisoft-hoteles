<?php
/**
 * Parser puro de fechas de estancia dictadas al copiloto
 * (CopilotoService::parsearFechasReserva). No toca la base; $hoy inyectado
 * para que las reglas de "fecha pasada se corre" sean deterministas.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoReservaParserTest\n";

$hoy = '2026-07-16';

// ── Rango con mes nombrado futuro ──
$r = CopilotoService::parsearFechasReserva('reservale la 204 a juan del 20 al 22 de agosto', $hoy);
t_eq('2026-08-20', $r['entrada'] ?? null, 'rango con mes: entrada');
t_eq('2026-08-22', $r['salida'] ?? null, 'rango con mes: salida');

// ── Rango sin mes: usa el actual si aun no pasa ──
$r = CopilotoService::parsearFechasReserva('aparta la 204 del 20 al 22', $hoy);
t_eq('2026-07-20', $r['entrada'] ?? null, 'rango sin mes: mes actual');
t_eq('2026-07-22', $r['salida'] ?? null, 'rango sin mes: salida');

// ── Rango sin mes ya pasado: se corre al mes siguiente ──
$r = CopilotoService::parsearFechasReserva('aparta la 204 del 3 al 5', $hoy);
t_eq('2026-08-03', $r['entrada'] ?? null, 'rango pasado sin mes: mes siguiente');

// ── Mes nombrado ya pasado: se corre al año siguiente ──
$r = CopilotoService::parsearFechasReserva('reservale del 3 al 5 de febrero', $hoy);
t_eq('2027-02-03', $r['entrada'] ?? null, 'mes pasado: año siguiente');
t_eq('2027-02-05', $r['salida'] ?? null, 'mes pasado: salida año siguiente');

// ── Dia unico con mes + noches ──
$r = CopilotoService::parsearFechasReserva('reservale la 204 a juan el 15 de agosto por 3 noches', $hoy);
t_eq('2026-08-15', $r['entrada'] ?? null, 'dia unico: entrada');
t_eq('2026-08-18', $r['salida'] ?? null, 'dia unico: 3 noches');

// ── Dia unico sin noches: default 1 ──
$r = CopilotoService::parsearFechasReserva('reservale el 15 de agosto', $hoy);
t_eq('2026-08-16', $r['salida'] ?? null, 'dia unico: default 1 noche');

// ── Hoy / manana / pasado manana ──
$r = CopilotoService::parsearFechasReserva('apartale la 204 a maria hoy por 2 noches', $hoy);
t_eq('2026-07-16', $r['entrada'] ?? null, 'hoy: entrada');
t_eq('2026-07-18', $r['salida'] ?? null, 'hoy: 2 noches');
$r = CopilotoService::parsearFechasReserva('aparta la 204 manana', $hoy);
t_eq('2026-07-17', $r['entrada'] ?? null, 'manana: entrada');
t_eq('2026-07-18', $r['salida'] ?? null, 'manana: default 1 noche');
$r = CopilotoService::parsearFechasReserva('aparta la 204 pasado manana', $hoy);
t_eq('2026-07-18', $r['entrada'] ?? null, 'pasado manana: entrada');

// ── Rango invertido o igual: invalido ──
t_eq(null, CopilotoService::parsearFechasReserva('del 22 al 20 de agosto', $hoy), 'rango invertido: null');
t_eq(null, CopilotoService::parsearFechasReserva('del 20 al 20 de agosto', $hoy), 'rango de cero noches: null');

// ── Dia imposible ──
t_eq(null, CopilotoService::parsearFechasReserva('del 30 al 32 de agosto', $hoy), 'dia 32: null');

// ── Sin fechas ──
t_eq(null, CopilotoService::parsearFechasReserva('reservale la 204 a juan', $hoy), 'sin fechas: null');

// ── Tope de noches ──
$r = CopilotoService::parsearFechasReserva('aparta la 204 hoy por 99 noches', $hoy);
t_eq('2026-08-15', $r['salida'] ?? null, 'noches: tope de 30');

t_fin();
