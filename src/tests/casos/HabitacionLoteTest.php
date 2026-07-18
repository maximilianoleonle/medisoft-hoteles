<?php
/**
 * Generador de numeros del alta masiva de habitaciones (piso + rango).
 * HabitacionController::generarNumerosLote — PURO, sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "HabitacionLoteTest\n";

// ── Rango simple (piso 1: 101–105) ──
$r = HabitacionController::generarNumerosLote('', 101, 105);
t_eq(['101', '102', '103', '104', '105'], $r, 'rango simple 101-105');

// ── Rango de un solo numero ──
$r = HabitacionController::generarNumerosLote('', 200, 200);
t_eq(['200'], $r, 'rango de uno');

// ── Prefijo de letra (A101…) ──
$r = HabitacionController::generarNumerosLote('A', 1, 3);
t_eq(['A1', 'A2', 'A3'], $r, 'prefijo de letra');

// ── Relleno con ceros (ancho 3) ──
$r = HabitacionController::generarNumerosLote('', 1, 3, 3);
t_eq(['001', '002', '003', ], $r, 'relleno de 3 digitos');

// ── Prefijo + relleno combinados ──
$r = HabitacionController::generarNumerosLote('PB-', 1, 2, 2);
t_eq(['PB-01', 'PB-02'], $r, 'prefijo + relleno');

// ── Conteo correcto de un lote grande ──
$r = HabitacionController::generarNumerosLote('', 201, 300);
t_eq(100, count($r), 'lote de 100 = 100 numeros');
t_eq('201', $r[0], 'primer numero del lote');
t_eq('300', $r[99], 'ultimo numero del lote');

// ── Invalido: final menor que inicial ──
t_throws(function () {
    HabitacionController::generarNumerosLote('', 120, 101);
}, 'mayor o igual', 'rango invertido lanza');

// ── Invalido: excede el maximo por lote ──
t_throws(function () {
    HabitacionController::generarNumerosLote('', 1, 501);
}, 'exceder', 'lote sobre el maximo lanza');

// ── Invalido: numero mas largo que 10 caracteres ──
t_throws(function () {
    HabitacionController::generarNumerosLote('HABITACION', 100, 100);
}, '10 caracteres', 'numero > 10 chars lanza');

// ── Invalido: negativo ──
t_throws(function () {
    HabitacionController::generarNumerosLote('', -1, 5);
}, 'negativo', 'rango negativo lanza');

t_fin();
