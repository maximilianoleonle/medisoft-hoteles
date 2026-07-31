<?php
/**
 * Multi-dueño: apagado de fabrica y borrable.
 *
 * Contrato: un hotel NO reparte ingresos entre socios salvo que alguien de alta
 * a los dueños. Nadie hereda dueños de otro hotel (antes venian Manolo/Elia
 * cableados) y quitar los que ya existen debe poder guardarse sin errores.
 *
 * PURO: helpers hotel_owner_distribution_* y PropietarioDistribucionService, sin BD.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "PropietariosMultiDuenoTest\n";

// ── El default de fabrica no trae dueños ──
$default = hotel_owner_distribution_default();
t_eq([], $default['propietarios'], 'default de fabrica sin dueños');
t_eq('', $default['propietario_default'], 'default de fabrica sin predeterminado');
t_eq([], $default['reglas_tipo_contiene'], 'default de fabrica sin reglas');
t_eq([], $default['habitaciones'], 'default de fabrica sin asignaciones');

// ── Normalizar vacio NO reinyecta una semilla ──
$normalizado = hotel_owner_distribution_normalize([]);
t_eq([], $normalizado['propietarios'], 'normalizar vacio deja vacio');
t_eq('', $normalizado['propietario_default'], 'normalizar vacio deja sin predeterminado');

$normalizado = hotel_owner_distribution_normalize(null);
t_eq([], $normalizado['propietarios'], 'normalizar null deja vacio');

// ── Todos inactivos = apagado, no fallback ──
$normalizado = hotel_owner_distribution_normalize([
    'propietario_default' => 'socio_1',
    'propietarios' => [
        'socio_1' => ['key' => 'socio_1', 'nombre' => 'Socio 1', 'activo' => false, 'participacion_pct' => 100],
    ],
]);
t_eq('', $normalizado['propietario_default'], 'sin dueños activos no hay predeterminado');

// ── Guardar cero dueños es valido (asi se elimina el multi-dueño) ──
$payload = hotel_owner_distribution_normalize_payload([
    'propietario_default' => '',
    'propietarios' => [
        ['key' => '', 'nombre' => '', 'participacion_pct' => '', 'activo' => '1'],
        ['key' => '', 'nombre' => '', 'participacion_pct' => '', 'activo' => '1'],
    ],
    'reglas_tipo_contiene' => [['texto' => '', 'propietario_key' => '']],
    'habitaciones' => [['numero' => '', 'tipo' => '', 'habitacion_id' => '', 'propietario_key' => '']],
]);
t_eq([], $payload['errors'], 'quitar todos los dueños no da error');
t_eq([], $payload['values']['propietarios'], 'quitar todos deja la lista vacia');
t_eq('', $payload['values']['propietario_default'], 'quitar todos deja sin predeterminado');

// ── Al quitar los dueños se van reglas y asignaciones huerfanas ──
$payload = hotel_owner_distribution_normalize_payload([
    'propietario_default' => 'socio_1',
    'propietarios' => [],
    'reglas_tipo_contiene' => [['texto' => 'suite', 'propietario_key' => 'socio_1']],
    'habitaciones' => [['numero' => '101', 'propietario_key' => 'socio_1']],
]);
t_eq([], $payload['errors'], 'reglas huerfanas no bloquean la baja');
t_eq([], $payload['values']['reglas_tipo_contiene'], 'la regla se va con su dueño');
t_eq([], $payload['values']['habitaciones'], 'la asignacion se va con su dueño');

// ── Un hotel que SI reparte sigue funcionando igual ──
$payload = hotel_owner_distribution_normalize_payload([
    'propietario_default' => 'socio_1',
    'propietarios' => [
        ['key' => 'socio_1', 'nombre' => 'Socio 1', 'participacion_pct' => '100', 'activo' => '1'],
        ['key' => 'socio_2', 'nombre' => 'Socio 2', 'participacion_pct' => '60', 'activo' => '1'],
    ],
    'reglas_tipo_contiene' => [['texto' => 'suite', 'propietario_key' => 'socio_2']],
    'habitaciones' => [['numero' => '101', 'propietario_key' => 'socio_2']],
]);
t_eq([], $payload['errors'], 'alta de dos dueños sin errores');
t_eq(2, count($payload['values']['propietarios']), 'los dos dueños se guardan');
t_eq('socio_1', $payload['values']['propietario_default'], 'predeterminado respetado');
t_eq(['suite' => 'socio_2'], $payload['values']['reglas_tipo_contiene'], 'regla guardada');
t_eq(1, count($payload['values']['habitaciones']), 'asignacion guardada');

// ── Sin predeterminado explicito se toma el primer dueño activo ──
$payload = hotel_owner_distribution_normalize_payload([
    'propietario_default' => '',
    'propietarios' => [
        ['key' => 'socio_1', 'nombre' => 'Socio 1', 'participacion_pct' => '100', 'activo' => '1'],
    ],
]);
t_eq([], $payload['errors'], 'sin predeterminado no truena');
t_eq('socio_1', $payload['values']['propietario_default'], 'predeterminado derivado del primer activo');

// ── Predeterminado inexistente sigue siendo error ──
$payload = hotel_owner_distribution_normalize_payload([
    'propietario_default' => 'fantasma',
    'propietarios' => [
        ['key' => 'socio_1', 'nombre' => 'Socio 1', 'participacion_pct' => '100', 'activo' => '1'],
    ],
]);
t_ok(!empty($payload['errors']), 'predeterminado inexistente avisa');

// ══ Servicio de distribucion ══
$servicio = new PropietarioDistribucionService();

$vacia = $servicio->configuracionVacia();
t_eq([], $vacia['propietarios'], 'servicio: configuracion vacia sin dueños');
t_ok(!$servicio->hayPropietarios($vacia), 'servicio: vacia = sin multi-dueño');

$habitaciones = [
    ['id' => 1, 'numero' => '101', 'tipo' => 'sencilla', 'precio_base' => 800],
    ['id' => 2, 'numero' => '102', 'tipo' => 'suite', 'precio_base' => 1200],
];

// Sin dueños no se inventa ninguna cubeta de reparto.
t_eq([], $servicio->distribuirPorPrecio(2000.0, $habitaciones, $vacia), 'sin dueños no hay reparto');
t_eq([], $servicio->crearResultadoCaja($vacia), 'sin dueños no hay cubetas de caja');
t_eq([], $servicio->crearResultadoReporte($vacia), 'sin dueños no hay cubetas de reporte');
t_eq('', $servicio->propietarioParaHabitacion($habitaciones[0], $vacia), 'sin dueños la habitacion no tiene propietario');

$contadas = [];
t_eq(
    [],
    $servicio->aplicarMovimientoCaja([], ['monto' => 500, 'metodo_pago' => 'efectivo', 'reservacion_id' => 7], $habitaciones, $contadas, $vacia),
    'sin dueños el movimiento de caja no se reparte'
);
t_eq(
    [],
    $servicio->aplicarMovimientoReporte([], ['monto' => 500, 'metodo_pago' => 'efectivo', 'reservacion_id' => 7], $habitaciones, $contadas, $vacia),
    'sin dueños el movimiento de reporte no se reparte'
);

// Con dueños configurados el reparto por precio sigue cuadrando al total.
$config = $servicio->normalizarConfiguracion([
    'propietario_default' => 'socio_1',
    'propietarios' => [
        'socio_1' => ['key' => 'socio_1', 'nombre' => 'Socio 1', 'activo' => true, 'participacion_pct' => 100],
        'socio_2' => ['key' => 'socio_2', 'nombre' => 'Socio 2', 'activo' => true, 'participacion_pct' => 100],
    ],
    'reglas_tipo_contiene' => ['suite' => 'socio_2'],
    'habitaciones' => [],
]);
t_ok($servicio->hayPropietarios($config), 'servicio: con dueños el reparto esta activo');
t_eq('socio_2', $servicio->propietarioParaHabitacion($habitaciones[1], $config), 'regla por tipo asigna al socio 2');
t_eq('socio_1', $servicio->propietarioParaHabitacion($habitaciones[0], $config), 'sin coincidencia cae al predeterminado');

$partes = $servicio->distribuirPorPrecio(2000.0, $habitaciones, $config);
$suma = 0.0;
foreach ($partes as $parte) {
    $suma += (float) $parte['monto'];
}
t_eq(2000.0, round($suma, 2), 'el reparto suma el monto completo');
t_eq(800.0, round((float) $partes['socio_1']['monto'], 2), 'socio 1 recibe lo suyo');
t_eq(1200.0, round((float) $partes['socio_2']['monto'], 2), 'socio 2 recibe lo suyo');

// Un dueño inactivo no participa aunque este en la configuracion.
$configInactivo = $servicio->normalizarConfiguracion([
    'propietario_default' => 'socio_1',
    'propietarios' => [
        'socio_1' => ['key' => 'socio_1', 'nombre' => 'Socio 1', 'activo' => true, 'participacion_pct' => 100],
        'socio_2' => ['key' => 'socio_2', 'nombre' => 'Socio 2', 'activo' => false, 'participacion_pct' => 100],
    ],
]);
t_eq(1, count($configInactivo['propietarios']), 'el dueño inactivo no reparte');
t_eq('socio_1', $servicio->propietarioParaHabitacion($habitaciones[1], $configInactivo), 'todo cae en el unico activo');

t_fin();
