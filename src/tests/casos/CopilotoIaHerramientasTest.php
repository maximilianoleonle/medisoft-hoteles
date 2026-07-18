<?php
/**
 * Herramientas de SOLO LECTURA del fallback IA del copiloto
 * (CopilotoService::ejecutarHerramientaIa). Contra la BD de prueba: cifras
 * correctas, fechas validadas, tenancy (los datos de otro hotel jamas se
 * cuelan) y salidas compactas para el modelo. No toca el API.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoIaHerramientasTest\n";

t_reset_db();
$base = t_seed_base('copiloto-ia-tools');
$hotelId = $base['hotel_id'];

$db = Database::getInstance();

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Ajeno', 'ajeno-tools', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

// Dinero: movimientos en un rango fijo de mayo (created_at directo; el
// motor de caja ya se prueba aparte) + un movimiento del hotel B que NO
// debe contar.
$db->query("INSERT INTO movimientos_caja (hotel_id, usuario_id, tipo, categoria, descripcion, monto, metodo_pago, created_at)
            VALUES (?, ?, 'ingreso', 'Hospedaje', 'Estancia', 3000, 'efectivo', '2026-05-05 12:00:00')", [$hotelId, $base['usuario_id']]);
$db->query("INSERT INTO movimientos_caja (hotel_id, usuario_id, tipo, categoria, descripcion, monto, metodo_pago, created_at)
            VALUES (?, ?, 'gasto', 'Limpieza', 'Insumos', 500, 'efectivo', '2026-05-07 10:00:00')", [$hotelId, $base['usuario_id']]);
$db->query("INSERT INTO movimientos_caja (hotel_id, usuario_id, tipo, categoria, descripcion, monto, metodo_pago, created_at)
            VALUES (?, ?, 'ingreso', 'Hospedaje', 'Ajena', 9999, 'efectivo', '2026-05-06 12:00:00')", [$hotelB, $base['usuario_id']]);

// Habitaciones: dos dobles y una sencilla; una doble ocupada en junio.
$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado, created_at)
            VALUES (?, '101', 1, 'doble', 900, 'disponible', NOW())", [$hotelId]);
$dobleOcupada = (int) $db->lastInsertId();
$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado, created_at)
            VALUES (?, '102', 1, 'doble', 900, 'disponible', NOW())", [$hotelId]);
$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado, created_at)
            VALUES (?, '103', 1, 'sencilla', 700, 'disponible', NOW())", [$hotelId]);

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, telefono, created_at) VALUES (?, 'Laura Campos', '5599887766', NOW())", [$hotelId]);
$huespedId = (int) $db->lastInsertId();
$db->query("INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
            VALUES (?, ?, '2026-06-10', '2026-06-12', 1800, 'confirmada', NOW())", [$hotelId, $huespedId]);
$reservaId = (int) $db->lastInsertId();
$db->query("INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio)
            VALUES (?, ?, ?, 900)", [$hotelId, $reservaId, $dobleOcupada]);

// Reserva FUTURA de Laura (la "vigente" que la herramienta debe listar).
$futIni = date('Y-m-d', strtotime('+10 days'));
$futFin = date('Y-m-d', strtotime('+12 days'));
$db->query("INSERT INTO reservaciones (hotel_id, huesped_id, fecha_entrada, fecha_salida, precio_total, estado, created_at)
            VALUES (?, ?, ?, ?, 1400, 'confirmada', NOW())", [$hotelId, $huespedId, $futIni, $futFin]);

$servicio = new CopilotoService();

// ── Dinero por rango: cifras del hotel, sin el movimiento ajeno ──
$out = $servicio->ejecutarHerramientaIa($hotelId, 'dinero_entre_fechas', ['desde' => '2026-05-01', 'hasta' => '2026-05-31']);
t_ok(strpos($out, '$3,000.00') !== false, 'dinero: ingresos del rango');
t_ok(strpos($out, '$2,500.00') !== false, 'dinero: neto 3000-500');
t_ok(strpos($out, '9,999') === false, 'dinero: el movimiento del hotel ajeno NO se cuela');
t_ok(strpos($out, 'Limpieza $500.00') !== false, 'dinero: desglose de gastos por categoria');

// El limite "hasta" es INCLUSIVO: un rango que termina justo el dia del
// movimiento lo cuenta.
$out = $servicio->ejecutarHerramientaIa($hotelId, 'dinero_entre_fechas', ['desde' => '2026-05-05', 'hasta' => '2026-05-05']);
t_ok(strpos($out, '$3,000.00') !== false, 'dinero: hasta inclusivo cuenta el dia final');

// ── Validacion de fechas ──
t_ok(strpos($servicio->ejecutarHerramientaIa($hotelId, 'dinero_entre_fechas', ['desde' => '05/01/2026', 'hasta' => '2026-05-31']), 'invalidas') !== false, 'fechas: formato raro rechazado');
t_ok(strpos($servicio->ejecutarHerramientaIa($hotelId, 'dinero_entre_fechas', ['desde' => '2026-06-01', 'hasta' => '2026-05-01']), 'anterior') !== false, 'fechas: rango invertido rechazado');
t_ok(strpos($servicio->ejecutarHerramientaIa($hotelId, 'dinero_entre_fechas', ['desde' => '2024-01-01', 'hasta' => '2026-05-01']), 'grande') !== false, 'fechas: rango de años rechazado');

// ── Reservaciones por rango ──
$out = $servicio->ejecutarHerramientaIa($hotelId, 'reservaciones_entre_fechas', ['desde' => '2026-06-01', 'hasta' => '2026-06-30']);
t_ok(strpos($out, '1 reservacion') !== false, 'reservas: cuenta la llegada de junio');
t_ok(strpos($out, '2 noche') !== false, 'reservas: noches vendidas');

// ── Disponibilidad por tipo: la doble ocupada no cuenta ──
$out = $servicio->ejecutarHerramientaIa($hotelId, 'disponibilidad_entre_fechas', ['desde' => '2026-06-10', 'hasta' => '2026-06-12']);
t_ok(strpos($out, '2 en total') !== false, 'disponibilidad: 2 libres (1 doble ocupada)');
// Fuera del rango de la reserva, las 3 estan libres.
$out = $servicio->ejecutarHerramientaIa($hotelId, 'disponibilidad_entre_fechas', ['desde' => '2026-06-20', 'hasta' => '2026-06-22']);
t_ok(strpos($out, '3 en total') !== false, 'disponibilidad: 3 libres fuera del rango ocupado');
// Colindante: llegada el dia que sale el otro huesped NO choca (hasta exclusivo del checkout).
$out = $servicio->ejecutarHerramientaIa($hotelId, 'disponibilidad_entre_fechas', ['desde' => '2026-06-12', 'hasta' => '2026-06-14']);
t_ok(strpos($out, '3 en total') !== false, 'disponibilidad: el dia del checkout no bloquea');

// ── Buscar huesped ──
$out = $servicio->ejecutarHerramientaIa($hotelId, 'buscar_huesped', ['nombre' => 'laura']);
t_ok(strpos($out, 'Laura Campos') !== false, 'huesped: encontrado');
t_ok(strpos($out, '2 estancia') !== false, 'huesped: cuenta estancias (pasada + futura)');
t_ok(strpos($out, $futIni) !== false, 'huesped: reserva vigente/proxima listada');
t_ok(strpos($servicio->ejecutarHerramientaIa($hotelId, 'buscar_huesped', ['nombre' => 'fantasma']), 'ningun huesped') !== false, 'huesped: no encontrado avisa');
t_ok(strpos($servicio->ejecutarHerramientaIa($hotelB, 'buscar_huesped', ['nombre' => 'laura']), 'ningun huesped') !== false, 'huesped: invisible desde otro hotel');

// ── Estado de lavanderia ──
// Sin blancos, avisa donde empezar (el modulo no esta activo en la BD de
// prueba: hotel_has_module sin filas activas de hotel_modulos deja pasar
// solo si el helper no gatea; se siembra el modulo activo para el hotel A).
$db->query("INSERT INTO modulos (clave, nombre, categoria, activo_global, orden) VALUES ('lavanderia', 'Lavanderia', 'operacion', 1, 110)
            ON DUPLICATE KEY UPDATE activo_global = 1");
$moduloLavId = (int) $db->query("SELECT id FROM modulos WHERE clave = 'lavanderia'")->fetch()['id'];
$db->query("INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, created_at) VALUES (?, ?, 1, 'manual', NOW())
            ON DUPLICATE KEY UPDATE activo = 1", [$hotelId, $moduloLavId]);
if (function_exists('ms_cache_forget')) {
    ms_cache_forget('modulos_hotel_' . $hotelId);
}

$out = $servicio->ejecutarHerramientaIa($hotelId, 'estado_lavanderia', []);
t_ok(strpos($out, 'no hay blancos') !== false, 'lavanderia: sin blancos invita a registrarlos');

$db->query("INSERT INTO lavanderia_blancos (hotel_id, nombre, categoria, stock_limpio, stock_sucio, stock_proceso, stock_minimo, activo, created_at)
            VALUES (?, 'Toalla de baño', 'bano', 2, 5, 3, 6, 1, NOW())", [$hotelId]);
$db->query("INSERT INTO lavanderia_lotes (hotel_id, tipo, estado, piezas_enviadas, created_at)
            VALUES (?, 'externo', 'en_proceso', 3, NOW())", [$hotelId]);
$db->query("INSERT INTO lavanderia_pedidos (hotel_id, cliente_nombre, estado, total, created_at)
            VALUES (?, 'Cliente Tools', 'listo', 180.50, NOW())", [$hotelId]);
// Ruido del hotel B que NO debe contarse.
$db->query("INSERT INTO lavanderia_blancos (hotel_id, nombre, categoria, stock_limpio, activo, created_at)
            VALUES (?, 'Sabana ajena', 'cama', 99, 1, NOW())", [$hotelB]);

$out = $servicio->ejecutarHerramientaIa($hotelId, 'estado_lavanderia', []);
t_ok(strpos($out, '2 piezas limpias') !== false, 'lavanderia: stock limpio del hotel (sin el ajeno)');
t_ok(strpos($out, '5 por lavar') !== false, 'lavanderia: stock sucio');
t_ok(strpos($out, 'BAJO MINIMO') !== false && strpos($out, 'Toalla de baño') !== false, 'lavanderia: alerta bajo minimo con nombre');
t_ok(strpos($out, 'Lotes en proceso: 1') !== false, 'lavanderia: lote en proceso contado');
t_ok(strpos($out, '1 listo(s) por entregar') !== false, 'lavanderia: pedido listo por entregar');
t_ok(strpos($out, '$180.50 por cobrar') !== false, 'lavanderia: monto por cobrar');
t_ok(strpos($out, '99') === false, 'lavanderia: el stock del hotel ajeno NO se cuela');

// ── Herramienta desconocida ──
t_eq('Herramienta desconocida.', $servicio->ejecutarHerramientaIa($hotelId, 'borrar_todo', []), 'herramienta desconocida: rechazada');

t_fin();
