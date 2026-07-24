<?php
/**
 * Aislamiento POR DISEÑO en el Model base (auditoria de accesos, 23 jul 2026).
 *
 * Con el contexto del hotel A, los metodos heredados find/update/delete/all
 * de un modelo cuya tabla tiene hotel_id NO deben alcanzar un registro del
 * hotel B, aunque el id llegue "de fuera". Y un modelo cuya tabla NO tiene
 * hotel_id (usuarios) debe seguir sin acotar (comportamiento previo).
 *
 * Cobaya: Proveedor (tabla proveedores, con hotel_id y updated_at, hereda
 * find/update/delete del base sin sobreescribirlos).
 */

require_once __DIR__ . '/../bootstrap.php';

echo "TenantScopeModelTest\n";

t_reset_db();

$db = Database::getInstance();

// ── Hotel B (la victima): sembrado por SQL directo ──────────────────────
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B', 'hotel-b', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

$db->query("INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
            VALUES ('user_scope_b', 'x', 'Usuario B', 'administrador', 1, NOW())");
$usuarioB = (int) $db->lastInsertId();

$db->query("INSERT INTO proveedores (hotel_id, nombre, activo, created_at, updated_at)
            VALUES (?, 'Proveedor B', 1, NOW(), NOW())", [$hotelB]);
$provB = (int) $db->lastInsertId();

// ── Hotel A (contexto activo del proceso) ───────────────────────────────
$base = t_seed_base('hotel-a');
$hotelA = (int) $base['hotel_id'];

$db->query("INSERT INTO proveedores (hotel_id, nombre, activo, created_at, updated_at)
            VALUES (?, 'Proveedor A', 1, NOW(), NOW())", [$hotelA]);
$provA = (int) $db->lastInsertId();

$modelo = new Proveedor();

// ── find(): solo alcanza lo del hotel A ─────────────────────────────────
$rowA = $modelo->find($provA);
t_ok(is_array($rowA) && (int) $rowA['id'] === $provA, 'find(proveedor propio) SÍ lo encuentra');

$rowB = $modelo->find($provB);
t_ok($rowB === false || $rowB === null, 'find(proveedor de otro hotel) NO lo encuentra');

// ── update(): no puede tocar lo del hotel B ─────────────────────────────
$modelo->update($provB, ['nombre' => 'HACKEADO']);
$revB = $db->query("SELECT nombre FROM proveedores WHERE id = ?", [$provB])->fetch();
t_ok(($revB['nombre'] ?? '') === 'Proveedor B', 'update(proveedor de otro hotel) NO lo modifica');

// ── update(): sí puede tocar lo propio ──────────────────────────────────
$modelo->update($provA, ['nombre' => 'Proveedor A editado']);
$revA = $db->query("SELECT nombre FROM proveedores WHERE id = ?", [$provA])->fetch();
t_ok(($revA['nombre'] ?? '') === 'Proveedor A editado', 'update(proveedor propio) SÍ lo modifica');

// ── delete(): no puede borrar lo del hotel B ────────────────────────────
$modelo->delete($provB);
$sigueB = $db->query("SELECT COUNT(*) AS n FROM proveedores WHERE id = ?", [$provB])->fetch();
t_ok((int) ($sigueB['n'] ?? 0) === 1, 'delete(proveedor de otro hotel) NO lo borra');

// ── all(): no lista nada del hotel B ────────────────────────────────────
$todas = $modelo->all();
$hayB = false;
foreach ($todas as $c) {
    if ((int) ($c['hotel_id'] ?? 0) === $hotelB) { $hayB = true; break; }
}
t_ok(!$hayB, 'all() no incluye proveedores de otro hotel');
t_ok(count($todas) >= 1, 'all() sí devuelve los del hotel propio');

// ── Modelo SIN hotel_id (usuarios): NO se acota ─────────────────────────
// El usuario B es de "otro hotel" conceptualmente, pero usuarios es una tabla
// global: el scope no debe activarse y find debe encontrarlo.
$usuarioModel = new Usuario();
$uB = $usuarioModel->find($usuarioB);
t_ok(is_array($uB) && (int) $uB['id'] === $usuarioB, 'find en tabla global (usuarios) NO se acota por hotel');

t_fin();
