<?php

require_once __DIR__ . '/../bootstrap.php';

echo "RetiroCuentasPorCobrarTest\n";

$root = dirname(__DIR__, 2);
$routes = (string)file_get_contents($root . '/config/routes.php');
$controller = (string)file_get_contents($root . '/app/controllers/CuentaPorCobrarController.php');
$detalle = (string)file_get_contents($root . '/app/views/cuentas_por_cobrar/ver_operativa.php');
$index = (string)file_get_contents($root . '/app/views/cuentas_por_cobrar/index.php');
$sidebar = (string)file_get_contents($root . '/app/views/layout/sidebar.php');
$footer = (string)file_get_contents($root . '/app/helpers/footer_nav.php');
$reservaciones = (string)file_get_contents($root . '/app/views/reservaciones/index.php');
$parcialSaldos = (string)file_get_contents($root . '/app/views/partials/reservaciones_saldos_pendientes.php');

t_ok(strpos($routes, 'generar-desde-reservacion') === false, 'no existe ruta para generar CxC nuevas');
t_ok(strpos($routes, "'/cuentas-por-cobrar/operativas/{id:[0-9]+}'") !== false, 'ruta de detalle historico permanece registrada');
t_ok(strpos($routes, "'/cuentas-por-cobrar/operativas/{id:[0-9]+}/registrar-cobro-caja'") !== false, 'ruta de liquidacion historica permanece registrada');
t_ok(strpos($routes, "'/cuentas-por-cobrar/operativas/{id:[0-9]+}/movimientos/{movimientoid:[0-9]+}/revertir-cobro-caja'") !== false, 'ruta de reversion historica permanece registrada');
t_ok(strpos($controller, 'generarDesdeReservacionAction') === false, 'controller no expone generacion nueva');
t_ok(strpos($controller, "redirect('cuentas-por-cobrar/operativas')") !== false, 'URL antigua redirige al historial');
t_ok(strpos($index, 'generar-desde-reservacion') === false, 'vista historica no ofrece generar cuenta');
t_ok(strpos($sidebar, '$mostrarCuentasPorCobrar = false') !== false, 'CxC queda oculta del sidebar comercial');
t_ok(strpos($footer, "'cuentas_por_cobrar' => [") === false, 'CxC queda fuera de atajos moviles');
t_ok(strpos($reservaciones, 'reservaciones_saldos_pendientes.php') !== false, 'Reservaciones integra saldos pendientes');
t_ok(strpos($parcialSaldos, 'id="saldos-pendientes"') !== false, 'seccion de saldos tiene ancla estable');

t_ok(strpos($controller, "can('cuentas_por_cobrar.cobrar')") !== false, 'detalle calcula permiso monetario');
t_ok(strpos($controller, '$puedeGestionarCobros ? $movimientos : []') !== false, 'solo usuarios autorizados reciben evaluacion de reversiones');
t_ok(strpos($detalle, 'if ($puedeGestionarCobros)') !== false, 'formularios monetarios se ocultan a solo lectura');
t_ok(substr_count($detalle, 'csrf_field()') >= 2, 'cobro y reversion conservan CSRF');
t_ok(strpos($controller, "require_permission('cuentas_por_cobrar.cobrar')") !== false, 'POST monetarios conservan gate de servidor');
t_ok(strpos($controller, "require_hotel_module('caja')") !== false, 'POST monetarios requieren Caja activa');

$saldos_pendientes = [[
    'reservacion_id' => 123,
    'huesped_nombre' => 'Huesped <QA>',
    'saldo_estimado' => '321.50',
]];
$resumen_saldos_pendientes = ['total' => 1, 'saldo_estimado' => '321.50'];
ob_start();
include $root . '/app/views/partials/reservaciones_saldos_pendientes.php';
$htmlSaldos = (string)ob_get_clean();
t_ok(strpos($htmlSaldos, 'reservaciones/ver/123') !== false, 'tarjeta de saldo enlaza al detalle correcto');
t_ok(strpos($htmlSaldos, 'Huesped &lt;QA&gt;') !== false, 'nombre de huesped queda escapado contra XSS');
t_ok(strpos($htmlSaldos, '<form') === false, 'seccion integrada es solo lectura y no agrega formularios');

t_reset_db();
$base = t_seed_base('retiro-cxc');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];
$reservacionId = t_seed_reservacion($hotelId, 1000.00);

$modelo = new CuentaPorCobrar();
$pendientes = $modelo->listarDerivadasPorHotel($hotelId, [
    'estado_reservacion' => 'todas',
    'estado_saldo' => 'pendiente',
    'vigencia' => 'todas',
], 50);
t_eq(1, count($pendientes), 'reservacion sin pagos aparece pendiente');
t_eq(1000.00, (float)$pendientes[0]['saldo_estimado'], 'saldo sin pagos coincide con total');

$db = Database::getInstance();
$db->query(
    "INSERT INTO reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto, referencia, created_at)
     VALUES (?, ?, 'transferencia', 250.00, 'TEST-PARCIAL', NOW())",
    [$hotelId, $reservacionId]
);
$db->query(
    "INSERT INTO reservacion_abonos
        (hotel_id, reservacion_id, monto, metodo_pago, concepto, usuario_id, created_at)
     VALUES (?, ?, 150.00, 'efectivo', 'TEST ABONO', ?, NOW())",
    [$hotelId, $reservacionId, $usuarioId]
);
$pendientes = $modelo->listarDerivadasPorHotel($hotelId, [
    'estado_reservacion' => 'todas', 'estado_saldo' => 'pendiente', 'vigencia' => 'todas',
], 50);
t_eq(600.00, (float)$pendientes[0]['saldo_estimado'], 'saldo parcial resta pagos y anticipos una sola vez');

$db->query(
    "INSERT INTO cuentas_por_cobrar
        (hotel_id, origen_tipo, origen_id, huesped_id, reservacion_id, concepto,
         fecha_emision, estado, total, saldo, creado_por_usuario_id, created_at)
     SELECT hotel_id, 'reservacion', id, huesped_id, id, 'Cuenta historica test',
            CURDATE(), 'parcial', 600.00, 400.00, ?, NOW()
     FROM reservaciones WHERE id = ? AND hotel_id = ?",
    [$usuarioId, $reservacionId, $hotelId]
);
$cuentaHistoricaId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO cuentas_por_cobrar_movimientos
        (hotel_id, cuenta_por_cobrar_id, tipo_movimiento, monto, saldo_anterior,
         saldo_posterior, referencia, usuario_id, created_at)
     VALUES (?, ?, 'COBRO', 200.00, 600.00, 400.00, 'TEST-CXC-COBRO', ?, NOW())",
    [$hotelId, $cuentaHistoricaId, $usuarioId]
);
$pendientes = $modelo->listarDerivadasPorHotel($hotelId, [
    'estado_reservacion' => 'todas', 'estado_saldo' => 'pendiente', 'vigencia' => 'todas',
], 50);
t_eq(400.00, (float)$pendientes[0]['saldo_estimado'], 'Reservaciones descuenta cobro historico CxC');

$copiloto = new CopilotoService();
$metodoPorCobrar = new ReflectionMethod(CopilotoService::class, 'porCobrar');
$saldoCopiloto = $metodoPorCobrar->invoke($copiloto, $hotelId);
t_eq('400.00', (string)($saldoCopiloto['saldo'] ?? ''), 'Copiloto usa el mismo saldo neto con cobro historico');

$tablero = (new TableroEjecutivo())->reporteReadOnlyPorHotel($hotelId);
t_eq(400.00, (float)($tablero['resumen']['saldo_cxc'] ?? -1), 'Tablero Ejecutivo usa el mismo saldo neto');

$db->query(
    "INSERT INTO reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto, referencia, created_at)
     VALUES (?, ?, 'tarjeta', 400.00, 'TEST-LIQUIDA', NOW())",
    [$hotelId, $reservacionId]
);
$pendientes = $modelo->listarDerivadasPorHotel($hotelId, [
    'estado_reservacion' => 'todas', 'estado_saldo' => 'pendiente', 'vigencia' => 'todas',
], 50);
t_eq(0, count($pendientes), 'reservacion liquidada desaparece de saldos pendientes');

$otra = t_seed_base('retiro-cxc-otro');
t_seed_reservacion($otra['hotel_id'], 777.00);
$ajenas = $modelo->listarDerivadasPorHotel($hotelId, [
    'estado_reservacion' => 'todas', 'estado_saldo' => 'pendiente', 'vigencia' => 'todas',
], 50);
t_eq(0, count($ajenas), 'saldos de otro hotel no se filtran al hotel actual');

$db->query(
    "INSERT INTO modulos
        (clave, nombre, descripcion, categoria, es_core, precio_mensual, activo_global, orden)
     VALUES ('cuentas_cobrar', 'CxC', 'Historico', 'finanzas', 0, 499.00, 1, 90)"
);
$moduloId = (int)$db->lastInsertId();
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, precio_override, fuente, enabled_at)
     VALUES (?, ?, 1, 699.00, 'manual', NOW())",
    [$hotelId, $moduloId]
);
$modulos = new Modulo();
// Dictamen 2026-07-24: bloquear no es esconder. La CxC retirada SI aparece en
// las consultas internas del Panel Medisoft (ahi se muestra como bloqueada);
// su exclusion del cobro por clave y su bloqueo operativo se conservan.
t_eq(1, count($modulos->listarGlobales()), 'CxC retirada sigue visible en el catalogo interno del panel');
$cobro = $modulos->resumenCobroMensual($hotelId, 1000.00);
t_eq(1000.00, (float)($cobro['total'] ?? 0), 'CxC retirada no se suma al cobro mensual');

t_fin();
