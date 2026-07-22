<?php
/**
 * Compras y proveedores: CRUD de proveedor, edicion/cancelacion de borradores,
 * recepcion e invariantes de inventario y tenant.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "ComprasProveedoresTest\n";

t_reset_db();
$base = t_seed_base('compras-proveedores');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];
$db = Database::getInstance();
$pdo = $db->getConnection();

$proveedorModel = new Proveedor();
$proveedorId = $proveedorModel->crearParaHotel($hotelId, [
    'nombre' => 'Suministros Test',
    'razon_social' => 'Suministros Test SA de CV',
    'rfc' => 'STE010101AA1',
    'telefono' => '5555555555',
    'email' => 'COMPRAS@TEST.EXAMPLE',
], $usuarioId);
t_ok($proveedorId > 0, 'proveedor creado');

$proveedor = $proveedorModel->buscarPorIdHotel($proveedorId, $hotelId);
t_eq('compras@test.example', $proveedor['email'] ?? null, 'correo de proveedor normalizado');

t_ok($proveedorModel->actualizarParaHotel($proveedorId, $hotelId, [
    'nombre' => 'Suministros Test Editado',
    'razon_social' => 'Suministros Test SA de CV',
    'rfc' => 'STE010101AA1',
    'telefono' => '5555550000',
    'email' => 'compras@test.example',
], $usuarioId), 'proveedor actualizado');
t_eq('Suministros Test Editado', $proveedorModel->buscarPorIdHotel($proveedorId, $hotelId)['nombre'] ?? null, 'edicion de proveedor persistida');

t_throws(function () use ($proveedorModel, $hotelId, $usuarioId): void {
    $proveedorModel->crearParaHotel($hotelId, [
        'nombre' => 'Otro proveedor',
        'rfc' => 'STE010101AA1',
    ], $usuarioId);
}, 'RFC', 'RFC duplicado rechazado dentro del hotel');

t_ok($proveedorModel->cambiarActivo($proveedorId, $hotelId, false, $usuarioId), 'proveedor desactivado');
t_eq(0, (int)($proveedorModel->buscarPorIdHotel($proveedorId, $hotelId)['activo'] ?? 1), 'estado inactivo persistido');
t_ok($proveedorModel->cambiarActivo($proveedorId, $hotelId, true, $usuarioId), 'proveedor reactivado');

$db->query("INSERT INTO inventario_categorias (nombre, descripcion, activo, created_at) VALUES ('Amenidades test', 'Pruebas', 1, NOW())");
$categoriaId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO inventario_productos
        (hotel_id, codigo, nombre, categoria_id, unidad_medida, stock_actual, stock_minimo, costo_unitario, activo, created_at, updated_at)
     VALUES (?, 'TEST-SHAMP', 'Shampoo test', ?, 'pieza', 10.00, 2.00, 5.00, 1, NOW(), NOW())",
    [$hotelId, $categoriaId]
);
$productoId = (int)$db->lastInsertId();

$service = new CompraService();
$compraId = $service->crearBorrador($hotelId, [
    'proveedor_id' => $proveedorId,
    'folio' => 'TEST-COMPRA-001',
    'fecha_compra' => '2026-07-21',
    'notas' => 'Borrador inicial',
], [[
    'producto_id' => $productoId,
    'cantidad' => '2.00',
    'costo_unitario' => '5.00',
]], $usuarioId);
t_ok($compraId > 0, 'compra creada como borrador');

$compra = $service->obtenerCompra($hotelId, $compraId);
t_eq('borrador', $compra['estado'] ?? null, 'compra inicia en borrador');
t_eq('10.00', $compra['total'] ?? null, 'total inicial calculado');
t_eq('10.00', $pdo->query("SELECT stock_actual FROM inventario_productos WHERE id = {$productoId}")->fetchColumn(), 'crear borrador no altera stock');

$resultadoEdicion = $service->actualizarBorrador($hotelId, $compraId, [
    'proveedor_id' => $proveedorId,
    'folio' => 'TEST-COMPRA-001',
    'fecha_compra' => '2026-07-22',
    'notas' => 'Borrador editado',
], [[
    'producto_id' => $productoId,
    'cantidad' => '3.00',
    'costo_unitario' => '6.50',
]], $usuarioId);
t_eq('19.50', $resultadoEdicion['total'] ?? null, 'edicion recalcula el total');

$compraEditada = $service->obtenerCompra($hotelId, $compraId);
t_eq('2026-07-22', $compraEditada['fecha_compra'] ?? null, 'fecha editada persistida');
t_eq('Borrador editado', $compraEditada['notas'] ?? null, 'notas editadas persistidas');
t_eq(1, count($compraEditada['detalles'] ?? []), 'edicion reemplaza detalles sin duplicarlos');
t_eq('3.00', $compraEditada['detalles'][0]['cantidad'] ?? null, 'cantidad editada persistida');
t_eq('10.00', $pdo->query("SELECT stock_actual FROM inventario_productos WHERE id = {$productoId}")->fetchColumn(), 'editar borrador no altera stock');

$compraCanceladaId = $service->crearBorrador($hotelId, [
    'proveedor_id' => $proveedorId,
    'folio' => 'TEST-COMPRA-CANCELAR',
    'fecha_compra' => '2026-07-21',
], [[
    'producto_id' => $productoId,
    'cantidad' => '1.00',
    'costo_unitario' => '5.00',
]], $usuarioId);
$service->cancelarBorrador($hotelId, $compraCanceladaId, $usuarioId);
$cancelada = $service->obtenerCompra($hotelId, $compraCanceladaId);
t_eq('cancelada', $cancelada['estado'] ?? null, 'cancelacion cambia estado logicamente');
t_eq($usuarioId, (int)($cancelada['cancelada_por'] ?? 0), 'cancelacion registra usuario');
t_eq(1, count($cancelada['detalles'] ?? []), 'cancelacion conserva detalles');
t_eq('10.00', $pdo->query("SELECT stock_actual FROM inventario_productos WHERE id = {$productoId}")->fetchColumn(), 'cancelar borrador no altera stock');
t_throws(fn() => $service->cancelarBorrador($hotelId, $compraCanceladaId, $usuarioId), 'borrador', 'doble cancelacion rechazada');
t_throws(fn() => $service->actualizarBorrador($hotelId, $compraCanceladaId, [
    'proveedor_id' => $proveedorId,
    'fecha_compra' => '2026-07-21',
], [['producto_id' => $productoId, 'cantidad' => 1]], $usuarioId), 'borrador', 'compra cancelada no puede editarse');

$service->recibirCompra($hotelId, $compraId, $usuarioId);
$recibida = $service->obtenerCompra($hotelId, $compraId);
t_eq('recibida', $recibida['estado'] ?? null, 'recepcion cambia estado');
t_eq('13.00', $pdo->query("SELECT stock_actual FROM inventario_productos WHERE id = {$productoId}")->fetchColumn(), 'recepcion incrementa stock una sola vez');
t_ok(!empty($recibida['detalles'][0]['movimiento_inventario_id']), 'recepcion vincula movimiento de inventario');
t_throws(fn() => $service->actualizarBorrador($hotelId, $compraId, [
    'proveedor_id' => $proveedorId,
    'fecha_compra' => '2026-07-21',
], [['producto_id' => $productoId, 'cantidad' => 1]], $usuarioId), 'borrador', 'compra recibida no puede editarse');
t_throws(fn() => $service->cancelarBorrador($hotelId, $compraId, $usuarioId), 'borrador', 'compra recibida no puede cancelarse');
t_throws(fn() => $service->recibirCompra($hotelId, $compraId, $usuarioId), 'recibida', 'doble recepcion rechazada');
t_eq('13.00', $pdo->query("SELECT stock_actual FROM inventario_productos WHERE id = {$productoId}")->fetchColumn(), 'doble recepcion rechazada no duplica stock');

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Ajeno', 'hotel-ajeno-compras', 1, NOW())");
$hotelAjenoId = (int)$db->lastInsertId();
t_eq(null, $service->obtenerCompra($hotelAjenoId, $compraId), 'compra aislada por hotel');
t_throws(fn() => $service->cancelarBorrador($hotelAjenoId, $compraCanceladaId, $usuarioId), 'no encontrada', 'cancelacion desde otro hotel rechazada');

$auditorias = $db->query(
    "SELECT accion, COUNT(*) total FROM logs_auditoria WHERE hotel_id = ? AND accion IN ('compras.actualizada', 'compras.cancelada') GROUP BY accion",
    [$hotelId]
)->fetchAll(PDO::FETCH_KEY_PAIR);
t_eq(1, (int)($auditorias['compras.actualizada'] ?? 0), 'edicion auditada');
t_eq(1, (int)($auditorias['compras.cancelada'] ?? 0), 'cancelacion auditada');

t_fin();
