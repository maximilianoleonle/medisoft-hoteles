<?php
/**
 * Modulo Lavanderia — invariantes:
 *  - El stock de blancos por estado (limpio/sucio/en lavado) nunca queda
 *    negativo y toda mutacion deja rastro en lavanderia_movimientos.
 *  - El ciclo de lote mueve sucio->proceso->limpio con merma contada; un
 *    lote recibido/cancelado no se reprocesa (candado optimista).
 *  - El cobro de pedido y el gasto de lote entran por MovimientoCaja con
 *    idempotencia (cobro/gasto_movimiento_id): sin caja abierta quedan
 *    pendientes SIN tronar; doble clic no duplica dinero.
 *  - Tenancy: entidades de otro hotel son invisibles/rechazadas.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "LavanderiaTest\n";

t_reset_db();
$base = t_seed_base('lavanderia-a');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();

require_once APP_PATH . '/controllers/LavanderiaController.php';

$blancoModel = new LavanderiaBlanco();
$loteModel = new LavanderiaLote();
$pedidoModel = new LavanderiaPedido();

/* ── 1. Blancos: alta con stock inicial + ledger ───────────────────────── */

$sabanaId = $blancoModel->guardar([
    'nombre' => 'Sabana king',
    'categoria' => 'cama',
    'stock_limpio' => 10,
    'stock_minimo' => 4,
    'usuario_id' => $usuarioId,
], null, $hotelId);
t_ok($sabanaId > 0, 'blanco creado con stock inicial');

$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(10, (int) $fila['stock_limpio'], 'stock limpio inicial = 10');
t_eq(0, (int) $fila['stock_sucio'], 'stock sucio inicial = 0');

$ledger = $db->query(
    "SELECT COUNT(*) c FROM lavanderia_movimientos WHERE hotel_id = ? AND blanco_id = ? AND tipo = 'compra'",
    [$hotelId, $sabanaId]
)->fetch();
t_eq(1, (int) $ledger['c'], 'alta con stock deja movimiento compra en el ledger');

$duplicado = false;
try {
    $blancoModel->guardar(['nombre' => 'Sabana king', 'categoria' => 'cama'], null, $hotelId);
} catch (Throwable $e) {
    $duplicado = true;
}
t_ok($duplicado, 'nombre duplicado de blanco RECHAZADO');

/* ── 2. Movimientos manuales: uso, exceso, baja ────────────────────────── */

$blancoModel->registrarMovimiento($hotelId, $sabanaId, 'uso', 6, $usuarioId, '');
$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(4, (int) $fila['stock_limpio'], 'uso 6: limpio 10 -> 4');
t_eq(6, (int) $fila['stock_sucio'], 'uso 6: sucio 0 -> 6');

$exceso = false;
try {
    $blancoModel->registrarMovimiento($hotelId, $sabanaId, 'uso', 99, $usuarioId, '');
} catch (Throwable $e) {
    $exceso = true;
}
t_ok($exceso, 'uso mayor al stock limpio RECHAZADO');

$blancoModel->registrarMovimiento($hotelId, $sabanaId, 'baja_sucio', 1, $usuarioId, 'rota');
$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(5, (int) $fila['stock_sucio'], 'baja_sucio 1: sucio 6 -> 5');

/* ── 3. Lote: crear mueve sucio -> en lavado ───────────────────────────── */

$loteId = $loteModel->crear($hotelId, 'externo', 'Lavanderia El Cisne', 'urgente', [$sabanaId => 4], $usuarioId);
t_ok($loteId > 0, 'lote externo creado');

$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(1, (int) $fila['stock_sucio'], 'crear lote: sucio 5 -> 1');
t_eq(4, (int) $fila['stock_proceso'], 'crear lote: en lavado 0 -> 4');

$sinStock = false;
try {
    $loteModel->crear($hotelId, 'interno', null, null, [$sabanaId => 50], $usuarioId);
} catch (Throwable $e) {
    $sinStock = true;
}
t_ok($sinStock, 'lote pidiendo mas piezas que las sucias RECHAZADO');
$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(1, (int) $fila['stock_sucio'], 'el rechazo no movio stock (rollback)');

$sinProveedor = false;
try {
    $loteModel->crear($hotelId, 'externo', '', null, [$sabanaId => 1], $usuarioId);
} catch (Throwable $e) {
    $sinProveedor = true;
}
t_ok($sinProveedor, 'lote externo sin proveedor RECHAZADO');

/* ── 4. Recibir lote con merma + costo ─────────────────────────────────── */

$items = $loteModel->items($loteId, $hotelId);
$itemId = (int) $items[0]['id'];
$resultado = $loteModel->recibir($hotelId, $loteId, [$itemId => 3], 250.00, $usuarioId);
t_eq(3, (int) $resultado['recibidas'], 'recibir: 3 piezas de vuelta');
t_eq(1, (int) $resultado['merma'], 'recibir: 1 pieza de merma');

$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(0, (int) $fila['stock_proceso'], 'recibir: en lavado 4 -> 0');
t_eq(7, (int) $fila['stock_limpio'], 'recibir: limpio 4 -> 7 (3 de vuelta)');

$lote = $loteModel->obtenerPorId($loteId, $hotelId);
t_eq('recibido', (string) $lote['estado'], 'lote queda recibido');
t_eq(1, (int) $lote['merma_total'], 'merma_total = 1');
t_eq(250.0, (float) $lote['costo'], 'costo real guardado');

$reproceso = false;
try {
    $loteModel->recibir($hotelId, $loteId, [$itemId => 4], null, $usuarioId);
} catch (Throwable $e) {
    $reproceso = true;
}
t_ok($reproceso, 'recibir un lote ya recibido RECHAZADO');

/* ── 5. Cancelar lote devuelve a sucio ─────────────────────────────────── */

$blancoModel->registrarMovimiento($hotelId, $sabanaId, 'uso', 3, $usuarioId, '');
$lote2Id = $loteModel->crear($hotelId, 'interno', null, null, [$sabanaId => 2], $usuarioId);
$loteModel->cancelar($hotelId, $lote2Id, $usuarioId);
$fila = $blancoModel->obtenerPorId($sabanaId, $hotelId);
t_eq(0, (int) $fila['stock_proceso'], 'cancelar lote: en lavado -> 0');
t_eq(4, (int) $fila['stock_sucio'], 'cancelar lote: las 2 piezas volvieron a sucio (2+2=4)');

/* ── 6. Pedidos: alta, total y estados ─────────────────────────────────── */

$pedidoId = $pedidoModel->crear($hotelId, [
    'cliente_nombre' => 'Cliente Mostrador',
    'reservacion_id' => 0,
], [
    ['descripcion' => 'Camisa lavada', 'cantidad' => 3, 'precio' => 45.50],
    ['descripcion' => 'Pantalon planchado', 'cantidad' => 2, 'precio' => 60.00],
], $usuarioId);
t_ok($pedidoId > 0, 'pedido walk-in creado');

$pedido = $pedidoModel->obtenerPorId($pedidoId, $hotelId);
t_eq(256.5, (float) $pedido['total'], 'total del pedido = 3*45.50 + 2*60.00 = 256.50');
t_eq('recibido', (string) $pedido['estado'], 'pedido nace recibido');

$sinItems = false;
try {
    $pedidoModel->crear($hotelId, ['cliente_nombre' => 'Nadie'], [], $usuarioId);
} catch (Throwable $e) {
    $sinItems = true;
}
t_ok($sinItems, 'pedido sin partidas RECHAZADO');

// Reservacion confirmada (sin check-in) NO es "huesped en casa".
$reservaConfirmadaId = t_seed_reservacion($hotelId, 1200.00);
$noEnCasa = false;
try {
    $pedidoModel->crear($hotelId, ['reservacion_id' => $reservaConfirmadaId], [
        ['descripcion' => 'Vestido', 'cantidad' => 1, 'precio' => 100],
    ], $usuarioId);
} catch (Throwable $e) {
    $noEnCasa = true;
}
t_ok($noEnCasa, 'pedido ligado a reservacion sin check-in RECHAZADO');

$pedidoModel->cambiarEstado($hotelId, $pedidoId, 'iniciar', $usuarioId);
$pedidoModel->cambiarEstado($hotelId, $pedidoId, 'listo', $usuarioId);
$pedido = $pedidoModel->obtenerPorId($pedidoId, $hotelId);
t_eq('listo', (string) $pedido['estado'], 'flujo recibido -> en_proceso -> listo');

/* ── 7. Cobro por Caja: pendiente sin corte, idempotente con corte ─────── */

$controller = new LavanderiaController([]);
$refCobro = new ReflectionMethod(LavanderiaController::class, 'registrarCobroDePedido');
$refCobro->setAccessible(true);
$refGasto = new ReflectionMethod(LavanderiaController::class, 'registrarGastoDeLote');
$refGasto->setAccessible(true);

$sinCaja = $refCobro->invoke($controller, $pedidoId, $hotelId, 'efectivo');
t_ok(empty($sinCaja['success']), 'cobro sin caja abierta NO truena: queda pendiente');
$pedido = $pedidoModel->obtenerPorId($pedidoId, $hotelId);
t_ok(empty($pedido['cobro_movimiento_id']), 'sin caja: el pedido sigue sin cobro ligado');

$caja = new Caja();
$caja->ensureDefaultCajaForHotel($hotelId);
$cajaPrincipal = $caja->obtenerCajaPrincipal();
$apertura = $caja->abrirCaja((int) $cajaPrincipal['id'], 1000.00, $usuarioId);
t_ok(!empty($apertura['success']), 'caja abierta para cobrar');

$cobro = $refCobro->invoke($controller, $pedidoId, $hotelId, 'efectivo');
t_ok(!empty($cobro['success']), 'cobro del pedido registrado con caja abierta');

$pedido = $pedidoModel->obtenerPorId($pedidoId, $hotelId);
t_ok(!empty($pedido['cobro_movimiento_id']), 'pedido liga cobro_movimiento_id');

$mov = $db->query(
    "SELECT m.tipo, m.monto, m.referencia, c.nombre AS categoria, c.tipo AS categoria_tipo
     FROM movimientos_caja m
     LEFT JOIN categorias_movimientos c ON c.id = m.categoria_id AND c.hotel_id = m.hotel_id
     WHERE m.hotel_id = ? AND m.referencia = ?",
    [$hotelId, 'LAV-' . $pedidoId]
)->fetchAll();
t_eq(1, count($mov), 'exactamente UN movimiento de caja LAV-#');
t_eq('ingreso', (string) $mov[0]['tipo'], 'el cobro es ingreso');
t_eq(256.5, (float) $mov[0]['monto'], 'monto del ingreso = total del pedido');
t_eq('Lavanderia', (string) $mov[0]['categoria'], 'categoria lazy Lavanderia creada y ligada');

$recobro = $refCobro->invoke($controller, $pedidoId, $hotelId, 'efectivo');
t_ok(!empty($recobro['success']), 'segundo cobro responde amable (ya cobrado)');
$movCount = $db->query(
    "SELECT COUNT(*) c FROM movimientos_caja WHERE hotel_id = ? AND referencia = ?",
    [$hotelId, 'LAV-' . $pedidoId]
)->fetch();
t_eq(1, (int) $movCount['c'], 'doble cobro NO duplica el ingreso (idempotente)');

$cancelarCobrado = false;
try {
    $pedidoModel->cambiarEstado($hotelId, $pedidoId, 'cancelar', $usuarioId);
} catch (Throwable $e) {
    $cancelarCobrado = true;
}
t_ok($cancelarCobrado, 'cancelar un pedido ya cobrado RECHAZADO');

$pedidoModel->cambiarEstado($hotelId, $pedidoId, 'entregar', $usuarioId);
$pedido = $pedidoModel->obtenerPorId($pedidoId, $hotelId);
t_eq('entregado', (string) $pedido['estado'], 'pedido entregado');
t_ok(!empty($pedido['entregado_en']), 'entregado_en queda sellado');

/* ── 8. Gasto del lote externo: idempotente ────────────────────────────── */

$gasto = $refGasto->invoke($controller, $loteId, $hotelId, 'efectivo');
t_ok(!empty($gasto['success']), 'gasto del lote externo registrado');

$lote = $loteModel->obtenerPorId($loteId, $hotelId);
t_ok(!empty($lote['gasto_movimiento_id']), 'lote liga gasto_movimiento_id');

$regasto = $refGasto->invoke($controller, $loteId, $hotelId, 'efectivo');
t_ok(!empty($regasto['success']), 'segundo intento de gasto responde amable');
$gastoCount = $db->query(
    "SELECT COUNT(*) c FROM movimientos_caja WHERE hotel_id = ? AND referencia = ? AND tipo = 'gasto'",
    [$hotelId, 'LAVLOTE-' . $loteId]
)->fetch();
t_eq(1, (int) $gastoCount['c'], 'doble gasto NO duplica el egreso (idempotente)');

/* ── 9. Tenancy: hotel B invisible y rechazado ─────────────────────────── */

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B', 'lavanderia-b', 1, NOW())");
$hotelBId = (int) $db->lastInsertId();

$toallaBId = $blancoModel->guardar([
    'nombre' => 'Toalla hotel B',
    'categoria' => 'bano',
    'stock_limpio' => 5,
], null, $hotelBId);
t_ok($toallaBId > 0, 'blanco del hotel B creado');

$listaA = $blancoModel->listar($hotelId);
$idsA = array_map(static fn($b) => (int) $b['id'], $listaA);
t_ok(!in_array($toallaBId, $idsA, true), 'el blanco del hotel B NO aparece en el listado del hotel A');

t_ok($blancoModel->obtenerPorId($toallaBId, $hotelId) === null, 'obtenerPorId cross-hotel devuelve null');

$cruzado = false;
try {
    $blancoModel->registrarMovimiento($hotelId, $toallaBId, 'uso', 1, $usuarioId, '');
} catch (Throwable $e) {
    $cruzado = true;
}
t_ok($cruzado, 'movimiento sobre blanco de otro hotel RECHAZADO');

$loteCruzado = false;
try {
    $db->query("UPDATE lavanderia_blancos SET stock_sucio = 3 WHERE id = ?", [$toallaBId]);
    $loteModel->crear($hotelId, 'interno', null, null, [$toallaBId => 1], $usuarioId);
} catch (Throwable $e) {
    $loteCruzado = true;
}
t_ok($loteCruzado, 'lote con blanco de otro hotel RECHAZADO');

t_ok($pedidoModel->obtenerPorId($pedidoId, $hotelBId) === null, 'pedido del hotel A invisible para hotel B');

t_fin();
