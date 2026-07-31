<?php
/**
 * Conceptos de caja (categorias_movimientos): alta, validacion, baja y tenancy.
 *
 * REGRESION del 500 en /caja/categorias (prod 2026-07-31, ref #c0b81680):
 * la tabla NO tiene columna updated_at (solo created_at con DEFAULT), pero el
 * Model base la agregaba al INSERT y al UPDATE -> SQLSTATE[42S22] "Unknown
 * column 'updated_at'". Reventaban DOS caminos, no uno: crear un concepto
 * nuevo y desactivar uno que ya tiene movimientos.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CajaConceptosTest\n";

t_reset_db();
$base = t_seed_base('conceptos-test');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();
$modelo = new CategoriaMovimiento();

// ── 1. Alta de un concepto nuevo (el caso que daba 500) ──────────────────
$alta = $modelo->crearCategoria([
    'nombre' => 'Lavado de autos',
    'tipo' => 'ingreso',
    'descripcion' => 'Servicio extra del hotel',
    'icono' => 'fas fa-car',
    'color' => '#10B981',
]);
t_ok(!empty($alta['success']), 'crear concepto nuevo NO truena (tabla sin updated_at)');

$fila = $db->query(
    'SELECT * FROM categorias_movimientos WHERE hotel_id = ? AND nombre = ?',
    [$hotelId, 'Lavado de autos']
)->fetch();
t_ok(!empty($fila), 'el concepto quedo guardado en la BD');
t_eq('ingreso', $fila['tipo'], 'tipo guardado');
t_eq($hotelId, (int) $fila['hotel_id'], 'el concepto nace en el hotel actual');
t_eq(1, (int) $fila['activa'], 'nace activo');
t_ok(!empty($fila['created_at']), 'created_at lo pone el DEFAULT de la columna');
t_eq(1, (int) $fila['orden'], 'orden = ultimo + 1');

$catId = (int) $fila['id'];

// ── 2. Nombre duplicado: rechazo CON motivo ──────────────────────────────
// Antes viajaba solo 'errores' y la pantalla mostraba un generico
// "Error al guardar la categoria" que no decia que estaba repetido.
$dup = $modelo->crearCategoria([
    'nombre' => 'Lavado de autos',
    'tipo' => 'ingreso',
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'color' => '#6B7280',
]);
t_ok(empty($dup['success']), 'concepto con nombre repetido RECHAZADO');
t_ok(
    strpos($dup['message'] ?? '', 'Ya existe') !== false,
    'el rechazo viaja con el motivo real, no un generico'
);

// ── 3. Validaciones basicas ──────────────────────────────────────────────
$corto = $modelo->crearCategoria([
    'nombre' => 'ab',
    'tipo' => 'ingreso',
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'color' => '#6B7280',
]);
t_ok(empty($corto['success']), 'nombre de menos de 3 caracteres rechazado');
t_ok(!empty($corto['message']), 'el rechazo por nombre corto tambien lleva mensaje');

$tipoMalo = $modelo->crearCategoria([
    'nombre' => 'Concepto con tipo invalido',
    'tipo' => 'egreso', // no existe en el enum: 'egreso' fue el bug historico
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'color' => '#6B7280',
]);
t_ok(empty($tipoMalo['success']), "tipo fuera del enum ('egreso') rechazado en PHP");

// ── 4. Editar un concepto ────────────────────────────────────────────────
$edicion = $modelo->actualizarCategoria($catId, [
    'nombre' => 'Lavado de autos premium',
    'tipo' => 'ingreso',
    'descripcion' => 'Con encerado',
    'icono' => 'fas fa-car',
    'color' => '#059669',
]);
t_ok(!empty($edicion['success']), 'editar concepto NO truena');
$trasEdicion = $db->query('SELECT nombre FROM categorias_movimientos WHERE id = ?', [$catId])->fetch();
t_eq('Lavado de autos premium', $trasEdicion['nombre'], 'el nombre quedo actualizado');

// ── 5. Baja de un concepto CON movimientos: se desactiva, no se borra ────
// Este camino pasa por update() del Model base: era la SEGUNDA mina del
// mismo bug de updated_at.
$db->query(
    "INSERT INTO movimientos_caja
        (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago, usuario_id, created_at)
     VALUES (?, 'ingreso', 'Lavado de autos premium', ?, 'Cobro de prueba', 150.00, 'efectivo', ?, NOW())",
    [$hotelId, $catId, $usuarioId]
);

$baja = $modelo->desactivar($catId);
t_ok(!empty($baja['success']), 'desactivar concepto CON movimientos NO truena');
$trasBaja = $db->query('SELECT activa FROM categorias_movimientos WHERE id = ?', [$catId])->fetch();
t_ok(!empty($trasBaja), 'el concepto con movimientos SIGUE existiendo (conserva historial)');
t_eq(0, (int) $trasBaja['activa'], 'quedo desactivado, no borrado');

// ── 6. Baja de un concepto SIN movimientos: se elimina ───────────────────
$modelo->crearCategoria([
    'nombre' => 'Concepto sin uso',
    'tipo' => 'gasto',
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'color' => '#6B7280',
]);
$sinUso = $db->query(
    'SELECT id FROM categorias_movimientos WHERE hotel_id = ? AND nombre = ?',
    [$hotelId, 'Concepto sin uso']
)->fetch();
t_ok(!empty($sinUso), 'segundo concepto creado');

$bajaLimpia = $modelo->desactivar((int) $sinUso['id']);
t_ok(!empty($bajaLimpia['success']), 'desactivar concepto SIN movimientos NO truena');
$quedan = $db->query(
    'SELECT COUNT(*) AS total FROM categorias_movimientos WHERE id = ?',
    [(int) $sinUso['id']]
)->fetch();
t_eq(0, (int) $quedan['total'], 'sin movimientos, la fila se elimina');

// ── 7. Conceptos protegidos del sistema ──────────────────────────────────
$modelo->crearCategoria([
    'nombre' => 'Hospedaje',
    'tipo' => 'ingreso',
    'descripcion' => '',
    'icono' => 'fas fa-bed',
    'color' => '#6B7280',
]);
$hospedaje = $db->query(
    'SELECT id FROM categorias_movimientos WHERE hotel_id = ? AND nombre = ?',
    [$hotelId, 'Hospedaje']
)->fetch();
$bajaProtegida = $modelo->desactivar((int) $hospedaje['id']);
t_ok(empty($bajaProtegida['success']), 'concepto del sistema (Hospedaje) NO se puede dar de baja');

// ── 8. Tenancy: los conceptos de otro hotel son intocables ───────────────
// El hotel B se siembra con SQL crudo A PROPOSITO: `obtenerHotelIdActualCompat()`
// cachea el hotel en un `static` para todo el proceso (en web da igual, cada
// request es un proceso nuevo), asi que cambiar de hotel a media corrida NO
// mueve lo que ve el modelo -> las altas del "hotel B" caian en el A y los
// asserts de aislamiento pasaban por la razon equivocada. La sesion se queda
// en el hotel A de principio a fin, que es justo el escenario a probar.
$db->query(
    "INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B', 'conceptos-otro-hotel', 1, NOW())"
);
$hotelB = (int) $db->lastInsertId();
t_ok($hotelB > 0 && $hotelB !== $hotelId, 'sembrado un segundo hotel');

$db->query(
    "INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, descripcion, icono, color, orden)
     VALUES (?, 'Concepto del hotel B', 'gasto', '', 'fas fa-tag', '#6B7280', 1)",
    [$hotelB]
);
$catB = $db->query(
    'SELECT id FROM categorias_movimientos WHERE hotel_id = ? AND nombre = ?',
    [$hotelB, 'Concepto del hotel B']
)->fetch();
t_ok(!empty($catB), 'el hotel B tiene su propio concepto');

// Seguimos en la sesion del hotel A: intentar tocar el concepto del B.
$modeloA = new CategoriaMovimiento();
$cruzada = $modeloA->actualizarCategoria((int) $catB['id'], [
    'nombre' => 'Secuestrado',
    'tipo' => 'gasto',
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'color' => '#6B7280',
]);
t_ok(empty($cruzada['success']), 'editar un concepto de otro hotel RECHAZADO');

$cruzadaBaja = $modeloA->desactivar((int) $catB['id']);
t_ok(empty($cruzadaBaja['success']), 'dar de baja un concepto de otro hotel RECHAZADO');

$intactoB = $db->query(
    'SELECT nombre FROM categorias_movimientos WHERE id = ?',
    [(int) $catB['id']]
)->fetch();
t_eq('Concepto del hotel B', $intactoB['nombre'], 'el concepto del hotel B quedo intacto');

$listaA = $modeloA->listarTodasDelHotel();
$ajenos = 0;
foreach ($listaA as $c) {
    if ((int) $c['hotel_id'] !== $hotelId) {
        $ajenos++;
    }
}
t_ok(!empty($listaA), 'la lista del hotel A no viene vacia (si no, el assert de abajo no prueba nada)');
t_eq(0, $ajenos, 'la lista del hotel A no trae ni un concepto de otro hotel');

t_fin();
