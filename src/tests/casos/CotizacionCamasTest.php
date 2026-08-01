<?php
/**
 * Columna CAMAS de la tabla de cotizacion (los dos PDFs).
 * ReservacionController::cotizacionPdfCamas — PURO, sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CotizacionCamasTest\n";

// ── Solo matrimoniales: nombre completo (lo lee el huesped) ──
t_eq('1 matrimonial', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 1,
    'camas_individuales' => 0,
]), 'una matrimonial en singular');

t_eq('2 matrimoniales', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 2,
    'camas_individuales' => 0,
]), 'dos matrimoniales en plural');

// ── Solo individuales ──
t_eq('1 individual', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 0,
    'camas_individuales' => 1,
]), 'una individual en singular');

t_eq('3 individuales', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 0,
    'camas_individuales' => 3,
]), 'tres individuales en plural');

// ── Mixta: se abrevia para no desbordar la celda de 30mm ──
t_eq('1 mat. / 2 ind.', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 1,
    'camas_individuales' => 2,
]), 'mixta abreviada como en habitaciones/index');

// ── Habitacion legacy sin camas registradas: guion, nunca cadena vacia ──
t_eq('-', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 0,
    'camas_individuales' => 0,
]), 'sin camas devuelve guion');

t_eq('-', ReservacionController::cotizacionPdfCamas([]), 'fila sin las columnas devuelve guion');

// ── Valores sucios: strings de PDO y negativos ──
t_eq('2 matrimoniales', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => '2',
    'camas_individuales' => '0',
]), 'strings de PDO se normalizan a entero');

t_eq('1 matrimonial', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => 1,
    'camas_individuales' => -4,
]), 'negativo no se pinta ni convierte la fila en mixta');

t_eq('-', ReservacionController::cotizacionPdfCamas([
    'camas_matrimoniales' => null,
    'camas_individuales' => null,
]), 'nulls devuelven guion');

// ── Sin acentos: el PDF va en Helvetica latin1 y la celda no debe traer basura ──
$textos = [
    ReservacionController::cotizacionPdfCamas(['camas_matrimoniales' => 1]),
    ReservacionController::cotizacionPdfCamas(['camas_individuales' => 2]),
    ReservacionController::cotizacionPdfCamas(['camas_matrimoniales' => 1, 'camas_individuales' => 1]),
];
foreach ($textos as $texto) {
    t_ok(preg_match('/^[\x20-\x7E]+$/', $texto) === 1, 'texto ASCII para el PDF: ' . $texto);
}

t_fin();
