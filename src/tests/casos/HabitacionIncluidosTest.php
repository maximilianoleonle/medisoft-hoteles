<?php
/**
 * Catalogo "servicios incluidos en todas las habitaciones" (configurable por hotel).
 * hotel_config.php — funciones puras + round-trip real contra hotel_configuracion.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "HabitacionIncluidosTest\n";

// ── Valores de fabrica ───────────────────────────────────────────────────
$defaults = hotel_room_catalog_default_included_rows();
t_eq(6, count($defaults), 'fabrica trae los 6 servicios historicos');
t_eq('wifi', $defaults[0]['codigo'], 'primer servicio de fabrica = wifi');

$catalogoIconos = hotel_room_included_icon_catalog();
foreach ($defaults as $fila) {
    t_ok(isset($catalogoIconos[$fila['icono']]), 'icono de fabrica en catalogo: ' . $fila['codigo']);
}

// ── El icono se adivina por el nombre (el hotelero no elige si no quiere) ──
t_eq('snowflake', hotel_room_included_icon_guess('Aire acondicionado'), 'aire acondicionado → snowflake');
t_eq('shower', hotel_room_included_icon_guess('Agua caliente las 24 horas'), 'agua caliente → shower');
t_eq('swimming-pool', hotel_room_included_icon_guess('Alberca climatizada'), 'alberca → swimming-pool');
t_eq('car', hotel_room_included_icon_guess('Estacionamiento techado'), 'estacionamiento → car');
t_eq('tv', hotel_room_included_icon_guess('TV'), 'tv → tv');
t_eq('check-circle', hotel_room_included_icon_guess('Servicio sin equivalente'), 'nombre desconocido → generico');
t_eq('check-circle', hotel_room_included_icon_guess(''), 'nombre vacio → generico');

// ── Iconos: solo los del catalogo (jamas una clase CSS libre) ────────────
t_eq('wifi', hotel_room_included_icon_normalize('fa-wifi'), 'acepta el icono con prefijo fa-');
t_eq('wifi', hotel_room_included_icon_normalize('FAS FA-WIFI'), 'acepta clase completa y mayusculas');
t_eq('', hotel_room_included_icon_normalize('rocket'), 'icono fuera del catalogo se rechaza');
t_eq('', hotel_room_included_icon_normalize('wifi" onload="alert(1)'), 'inyeccion en el icono se rechaza');
t_eq('#2563eb', hotel_room_included_icon_color('wifi'), 'color del catalogo');
t_eq('#64748b', hotel_room_included_icon_color('rocket'), 'color neutro para icono desconocido');

// ── Saneado de lo guardado ───────────────────────────────────────────────
$sane = hotel_room_catalog_sanitize_included_rows([
    ['label' => 'Aire acondicionado', 'activo' => 1, 'orden' => 1],
    ['label' => '   ', 'activo' => 1, 'orden' => 0],
    ['codigo' => 'wifi', 'label' => 'Wi-Fi', 'icono' => 'wifi', 'activo' => 0, 'orden' => 0],
    'basura',
]);
t_eq(2, count($sane), 'filas vacias y no-arreglo se descartan');
t_eq('wifi', $sane[0]['codigo'], 'ordena por el campo orden');
t_eq('aire_acondicionado', $sane[1]['codigo'], 'codigo derivado del nombre');
t_eq('snowflake', $sane[1]['icono'], 'icono ausente se adivina al sanear');
t_eq(0, $sane[0]['activo'], 'activo=0 se respeta');

$duplicados = hotel_room_catalog_sanitize_included_rows([
    ['label' => 'Agua caliente', 'activo' => 1],
    ['label' => 'AGUA  CALIENTE', 'activo' => 1],
]);
t_eq(1, count($duplicados), 'duplicado por nombre se descarta al sanear');

// ── Normalizado del formulario ───────────────────────────────────────────
$res = hotel_room_catalog_normalize_included_payload([
    ['label' => 'Wi-Fi', 'icono' => 'wifi', 'activo' => '1'],
    ['label' => '', 'icono' => 'tv', 'activo' => '1'],
    ['label' => 'Aire acondicionado', 'icono' => '', 'activo' => '0'],
    ['label' => 'Cochera', 'icono' => 'rocket', 'activo' => '1'],
]);
t_eq([], $res['errors'], 'formulario valido no arroja errores');
t_eq(3, count($res['values']), 'la fila sin nombre se ignora (equivale a borrarla)');
t_eq('snowflake', $res['values'][1]['icono'], 'icono automatico cuando no se elige');
t_eq('car', $res['values'][2]['icono'], 'icono invalido cae al adivinado, no se guarda crudo');
t_eq(0, $res['values'][1]['activo'], 'switch apagado viaja como 0');
t_eq([0, 1, 2], array_column($res['values'], 'orden'), 'el orden sigue al formulario');

$resDup = hotel_room_catalog_normalize_included_payload([
    ['label' => 'Estacionamiento', 'activo' => '1'],
    ['label' => 'estacionamiento', 'activo' => '1'],
]);
t_eq(1, count($resDup['values']), 'duplicado no se guarda');
t_eq(1, count($resDup['errors']), 'duplicado avisa al usuario');

$resLargo = hotel_room_catalog_normalize_included_payload([
    ['label' => str_repeat('a', 71), 'activo' => '1'],
]);
t_eq(1, count($resLargo['errors']), 'nombre de mas de 70 caracteres se rechaza');

$resVacio = hotel_room_catalog_normalize_included_payload([]);
t_eq([], $resVacio['errors'], 'lista vacia es valida (hay hoteles que no incluyen nada)');

// ── El payload general solo toca 'included' si el formulario la mando ────
$sinLlave = hotel_room_catalog_normalize_payload([
    'types' => [['codigo' => 'sencilla', 'nombre' => 'Sencilla', 'activo' => '1']],
    'floors' => [['valor' => '1', 'label' => 'Planta baja', 'activo' => '1']],
]);
t_ok(!array_key_exists('included', $sinLlave['values']), 'POST viejo sin la llave no borra los incluidos');

$conLlave = hotel_room_catalog_normalize_payload([
    'types' => [['codigo' => 'sencilla', 'nombre' => 'Sencilla', 'activo' => '1']],
    'floors' => [['valor' => '1', 'label' => 'Planta baja', 'activo' => '1']],
    'included' => [['label' => 'Wi-Fi', 'icono' => 'wifi', 'activo' => '1']],
]);
t_eq(1, count($conLlave['values']['included']), 'con la llave presente si viaja la lista');

// ── Round-trip real contra la BD (por hotel) ─────────────────────────────
t_reset_db();
$base = t_seed_base('incluidos');

t_eq(
    array_column(hotel_room_catalog_default_included_rows(), 'codigo'),
    array_column(hotel_room_catalog_included_rows($base['hotel_id'], true), 'codigo'),
    'hotel sin configurar hereda los valores de fabrica'
);

hotel_room_catalog_save_values([
    'types' => [],
    'floors' => [],
    'amenities' => [],
    'included' => hotel_room_catalog_normalize_included_payload([
        ['label' => 'Aire acondicionado', 'icono' => '', 'activo' => '1'],
        ['label' => 'Alberca', 'icono' => 'swimming-pool', 'activo' => '0'],
    ])['values'],
], $base['hotel_id']);

$guardados = hotel_room_catalog_included_rows($base['hotel_id'], true);
t_eq(2, count($guardados), 'se guardan los dos servicios del hotel');
t_eq('aire_acondicionado', $guardados[0]['codigo'], 'codigo persistido');
t_eq('snowflake', $guardados[0]['icono'], 'icono persistido');

$activos = hotel_room_catalog_included_rows($base['hotel_id'], false);
t_eq(1, count($activos), 'los inactivos no se muestran en la habitacion');
t_eq(['aire_acondicionado' => 'Aire acondicionado'], hotel_room_catalog_included($base['hotel_id']), 'mapa codigo => nombre');

// Lista vacia GUARDADA = el hotel no incluye nada (no vuelve a los defaults).
hotel_room_catalog_save_values([
    'types' => [],
    'floors' => [],
    'amenities' => [],
    'included' => [],
], $base['hotel_id']);
t_eq([], hotel_room_catalog_included_rows($base['hotel_id'], true), 'vaciar la lista se respeta, no revive la de fabrica');

t_fin();
