<?php
/**
 * Placas internas de vehiculo (helpers puros, sin base).
 *
 * Cuando el hotel no exige placas, HuespedController genera 'SINPLACA'+12 hex
 * para no romper el indice unico por hotel. Es relleno de BD y NUNCA debe
 * leerse en pantalla ni en el PDF de cotizacion que recibe el huesped.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "PlacasInternasTest\n";

// ── El token generado se reconoce ──
t_ok(HuespedVehiculo::esPlacaInterna('SINPLACA1AFE360EDC81'), 'reconoce el token generado (caso real de produccion)');
t_ok(HuespedVehiculo::esPlacaInterna('SINPLACA08E5B9135551'), 'reconoce otro token real de produccion');
t_ok(!HuespedVehiculo::esPlacaInterna('SINPLACA08E5B913555'), 'exige los 12 hex exactos: con 11 no marca');
t_ok(!HuespedVehiculo::esPlacaInterna('SINPLACA08E5B91355512'), 'exige los 12 hex exactos: con 13 no marca');
t_ok(HuespedVehiculo::esPlacaInterna('sinplaca08e5b9135551'), 'lo reconoce en minusculas');
t_ok(HuespedVehiculo::esPlacaInterna('  SINPLACA08E5B9135551  '), 'lo reconoce con espacios alrededor');

// ── Lo que escribio una persona se RESPETA (hay 2 asi en produccion) ──
t_ok(!HuespedVehiculo::esPlacaInterna('SINPLACAS'), 'respeta "SINPLACAS" escrito a mano');
t_ok(!HuespedVehiculo::esPlacaInterna('SINPLACAS1'), 'respeta "SINPLACAS1" escrito a mano');
t_ok(!HuespedVehiculo::esPlacaInterna('SINPLACAZZZZZZZZZZZZ'), 'no confunde 12 caracteres no hexadecimales');

// ── Placas de verdad ──
t_ok(!HuespedVehiculo::esPlacaInterna('ABC-1234'), 'una placa normal no es interna');
t_ok(!HuespedVehiculo::esPlacaInterna(''), 'vacio no es token interno');
t_ok(!HuespedVehiculo::esPlacaInterna(null), 'null no truena ni es token interno');

// ── Texto listo para pintar ──
t_eq('', HuespedVehiculo::placasVisibles('SINPLACA1AFE360EDC81'), 'el token se vacia para la vista');
t_eq('Sin placas', HuespedVehiculo::placasVisibles('SINPLACA1AFE360EDC81', 'Sin placas'), 'el token cae al texto de respaldo');
t_eq('Sin placas', HuespedVehiculo::placasVisibles('', 'Sin placas'), 'el campo vacio cae al mismo respaldo');
t_eq('ABC-1234', HuespedVehiculo::placasVisibles('ABC-1234', 'Sin placas'), 'una placa real se muestra tal cual');
t_eq('SINPLACAS', HuespedVehiculo::placasVisibles('SINPLACAS', 'Sin placas'), 'el texto de la persona se muestra tal cual');
t_eq('ABC-1234', HuespedVehiculo::placasVisibles('  ABC-1234 '), 'recorta espacios');

// ── Copia para el navegador: limpia SOLO las placas ──
$fila = [
    'id' => 7,
    'marca' => 'Toyota',
    'modelo' => 'Yaris',
    'placas' => 'SINPLACA1AFE360EDC81',
    'color' => 'Rojo',
    'estacionamiento' => 'coches',
];
$limpio = HuespedVehiculo::paraMostrar($fila);
t_eq('', $limpio['placas'], 'paraMostrar vacia el token');
t_eq('Toyota', $limpio['marca'], 'paraMostrar no toca la marca');
t_eq('Rojo', $limpio['color'], 'paraMostrar no toca el color');
t_eq(7, $limpio['id'], 'paraMostrar no toca el id');
t_eq(6, count($limpio), 'paraMostrar no agrega ni quita llaves');
t_eq('SINPLACA1AFE360EDC81', $fila['placas'], 'el arreglo original queda intacto (la BD conserva el token)');

$fila['placas'] = 'XYZ-987';
t_eq('XYZ-987', HuespedVehiculo::paraMostrar($fila)['placas'], 'paraMostrar deja pasar una placa real');

// Sin la llave 'placas' devuelve lo mismo (respuestas que no traen vehiculo).
t_eq(['id' => 3], HuespedVehiculo::paraMostrar(['id' => 3]), 'sin la llave placas devuelve el arreglo igual');
t_eq(9, HuespedVehiculo::paraMostrar(9), 'un valor que no es arreglo pasa sin tocarse');

$lista = HuespedVehiculo::listaParaMostrar([
    ['placas' => 'SINPLACA08E5B9135551'],
    ['placas' => 'ABC-1234'],
]);
t_eq('', $lista[0]['placas'], 'listaParaMostrar limpia el token de la lista');
t_eq('ABC-1234', $lista[1]['placas'], 'listaParaMostrar respeta las placas reales');
t_eq([], HuespedVehiculo::listaParaMostrar(null), 'listaParaMostrar tolera null');

t_fin();
