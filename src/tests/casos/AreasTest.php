<?php
/**
 * Bloque habitaciones y areas: entidad Area (alta y estados), limpieza de
 * area con personal (mismo contrato que habitaciones), mantenimiento de area
 * via MantenimientoService (habitacion NULL + area_id), accion del copiloto
 * cerrar/reabrir y aislamiento multi-hotel.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/services/MantenimientoService.php';

echo "AreasTest\n";

t_reset_db();
$base = t_seed_base('areas-hotel-a');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

$db = Database::getInstance();

// Hotel B con su propia area: el blanco del aislamiento.
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B Areas', 'areas-hotel-b', 1, NOW())");
$hotelBId = (int) $db->lastInsertId();
$db->query("INSERT INTO areas_hotel (hotel_id, nombre, tipo, estado, activa) VALUES (?, 'Alberca Secreta B', 'alberca', 'disponible', 1)", [$hotelBId]);
$areaBId = (int) $db->lastInsertId();

// Personal de limpieza activo en A.
$db->query("INSERT INTO trabajadores (hotel_id, nombre_completo, estado) VALUES (?, 'Rosa Limpieza', 'activo')", [$hotelId]);
$trabajadorId = (int) $db->lastInsertId();

$areaModel = new Area();
$tareas = new TareaOperativa();
$mantenimiento = new MantenimientoService();

// ── Alta via modelo (hotel de la sesion simulada = A) ──
$creada = $areaModel->guardar([
    'nombre' => 'Alberca Principal',
    'tipo' => 'alberca',
    'piso' => null,
    'descripcion' => 'Area QA',
    'estado' => 'disponible',
    'activa' => 1,
]);
$areaId = is_array($creada) ? (int) ($creada['id'] ?? 0) : (int) $creada;
t_ok($areaId > 0, 'alta de area devuelve id');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('Alberca Principal', $area['nombre'] ?? null, 'area creada legible por id + hotel');
t_eq('disponible', $area['estado'] ?? null, 'area nace disponible');

// ── Validaciones de catalogo en PHP (gotcha enum MySQL) ──
t_ok(Area::tipoValido('alberca') && !Area::tipoValido('spa_marciano'), 'catalogo de tipos valida en PHP');
t_ok(!$areaModel->cambiarEstado($areaId, 'ocupada', $hotelId), 'estado invalido (ocupada) rechazado');

// ── Limpieza: iniciar manda a limpieza y crea la tarea con area_id ──
$tareaId = $tareas->iniciarLimpiezaAreaParaHotel($hotelId, $areaId, $usuarioId);
t_ok($tareaId > 0, 'iniciar limpieza crea tarea');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('limpieza', $area['estado'] ?? null, 'area queda en limpieza');
$fila = $db->query("SELECT estado, area_id, habitacion_id FROM tareas_operativas WHERE id = ?", [$tareaId])->fetch();
t_eq('pendiente', $fila['estado'] ?? null, 'tarea de limpieza pendiente');
t_eq($areaId, (int) ($fila['area_id'] ?? 0), 'tarea ligada al area');
t_ok($fila['habitacion_id'] === null, 'tarea sin habitacion (area pura)');

// Doble envio a limpieza: rechazado (el area ya no esta disponible).
$duplicada = false;
try {
    $tareas->iniciarLimpiezaAreaParaHotel($hotelId, $areaId, $usuarioId);
} catch (Throwable $e) {
    $duplicada = true;
}
t_ok($duplicada, 'segundo envio a limpieza rechazado');

// ── Completar con personal: contrato de habitaciones sobre el area ──
t_ok($tareas->completarLimpiezaConPersonalAreaParaHotel($hotelId, $areaId, [$trabajadorId], $usuarioId), 'completar limpieza con personal');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('disponible', $area['estado'] ?? null, 'area regresa a disponible');
$fila = $db->query("SELECT estado, notas_cierre FROM tareas_operativas WHERE id = ?", [$tareaId])->fetch();
t_eq('completada', $fila['estado'] ?? null, 'tarea completada');
t_ok(strpos((string) ($fila['notas_cierre'] ?? ''), 'Rosa Limpieza') !== false, 'nota de cierre registra quien limpio');
$pivote = $db->query("SELECT COUNT(*) AS n FROM tarea_trabajadores WHERE tarea_id = ?", [$tareaId])->fetch();
t_eq(1, (int) ($pivote['n'] ?? 0), 'pivote tarea_trabajadores con 1 fila');

// ── Mantenimiento de area: mismo motor, habitacion NULL ──
$r = $mantenimiento->iniciarParaAreaHotel($hotelId, $areaId, 'correctivo', 'alta', 'Bomba con fuga', $usuarioId);
t_ok(!empty($r['ok']), 'mantenimiento de area iniciado');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('mantenimiento', $area['estado'] ?? null, 'area queda en mantenimiento');
$mant = $db->query("SELECT area_id, habitacion_id, estado FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq($areaId, (int) ($mant['area_id'] ?? 0), 'mantenimiento ligado al area');
t_ok($mant['habitacion_id'] === null, 'mantenimiento sin habitacion');

$doble = false;
try {
    $mantenimiento->iniciarParaAreaHotel($hotelId, $areaId, 'correctivo', 'media', 'Otro motivo mas', $usuarioId);
} catch (Throwable $e) {
    $doble = true;
}
t_ok($doble, 'doble mantenimiento de area rechazado');

$r = $mantenimiento->finalizarParaAreaHotel($hotelId, $areaId, $usuarioId);
t_ok(!empty($r['ok']), 'mantenimiento de area finalizado');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('disponible', $area['estado'] ?? null, 'area disponible tras finalizar');

// ── Copiloto: "cierra la alberca" propone, ejecutar cierra, reabrir revierte ──
$copiloto = new CopilotoService();
$r = $copiloto->responder($hotelId, 'cierra la alberca', $usuarioId);
t_eq('accion:cerrar_area', $r['intent'] ?? null, 'copiloto: cierra la alberca propone');
t_eq('cerrar_area', $r['accion']['tipo'] ?? null, 'copiloto: tipo cerrar_area');
t_eq($areaId, (int) ($r['accion']['area_id'] ?? 0), 'copiloto: area correcta');

$e = $copiloto->ejecutarAccion($hotelId, 'cerrar_area', ['area_id' => $areaId], $usuarioId);
t_ok(!empty($e['success']), 'copiloto: cerrar ejecutado');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('cerrada', $area['estado'] ?? null, 'area cerrada desde el chat');

$r = $copiloto->responder($hotelId, 'cierra la alberca', $usuarioId);
t_eq('accion:cerrar_area_ya', $r['intent'] ?? null, 'copiloto: cerrar dos veces avisa que ya esta cerrada');

$r = $copiloto->responder($hotelId, 'reabre la alberca', $usuarioId);
t_eq('accion:reabrir_area', $r['intent'] ?? null, 'copiloto: reabre propone');
$e = $copiloto->ejecutarAccion($hotelId, 'reabrir_area', ['area_id' => $areaId], $usuarioId);
t_ok(!empty($e['success']), 'copiloto: reabrir ejecutado');
$area = $areaModel->obtenerPorId($areaId, $hotelId);
t_eq('disponible', $area['estado'] ?? null, 'area disponible tras reabrir');

// ── Aislamiento multi-hotel ──
t_ok($areaModel->obtenerPorId($areaBId, $hotelId) === null, 'area de B invisible desde A');
$e = $copiloto->ejecutarAccion($hotelId, 'cerrar_area', ['area_id' => $areaBId], $usuarioId);
t_ok(empty($e['success']), 'cerrar area de B desde A rechazado');
$estadoB = $db->query("SELECT estado FROM areas_hotel WHERE id = ?", [$areaBId])->fetch();
t_eq('disponible', $estadoB['estado'] ?? null, 'area de B intacta');
$cerrarB = false;
try {
    $cerrarB = $areaModel->cambiarEstado($areaBId, 'cerrada', $hotelId) && false;
} catch (Throwable $e2) {
    $cerrarB = false;
}
$estadoB = $db->query("SELECT estado FROM areas_hotel WHERE id = ?", [$areaBId])->fetch();
t_eq('disponible', $estadoB['estado'] ?? null, 'cambiarEstado cruzado no toca el area de B');

t_fin();
