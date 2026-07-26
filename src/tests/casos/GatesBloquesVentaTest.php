<?php

require_once __DIR__ . '/../bootstrap.php';

echo "GatesBloquesVentaTest\n";

// ─────────────────────────────────────────────────────────────────────────────
// Contrato: un bloque OPCIONAL a la venta debe tener candado de SERVIDOR.
//
// Origen (2026-07-25): al abrir los 8 opcionales a la venta, la auditoria de
// gates encontro dos bloques que se cobraban sin estar restringidos -- el mismo
// agujero que tenia 'pwa', que cobraba $149/mes sin un solo
// require_hotel_module('pwa') en todo el repo:
//   - tarifas_dinamicas ($149): TarifasController::before() solo pedia el
//     permiso 'tarifas.view'. Las 12 rutas de /configuracion/tarifas cargaban
//     para cualquier hotel con el permiso, contratado o no. Solo el MENU lo
//     escondia (sidebar.php, navegacion.php), y el menu no es enforcement.
//   - descuentos ($99): sin ruta propia, es un feature-flag incrustado en
//     pantallas del PAQUETE BASE, asi que su gate no puede ser un 403 en un
//     before() (tumbaria Reservaciones). El candado va en los puntos de entrada.
//
// Esta suite caracteriza ambos candados para que no se pierdan en un refactor.
// ─────────────────────────────────────────────────────────────────────────────

$root = dirname(__DIR__, 2);
$tarifasCtrl = (string) file_get_contents($root . '/app/controllers/TarifasController.php');
$reservacionCtrl = (string) file_get_contents($root . '/app/controllers/ReservacionController.php');
$huespedCtrl = (string) file_get_contents($root . '/app/controllers/HuespedController.php');

// ── 1) Tarifas dinamicas: candado duro en el before() ──
t_ok(strpos($tarifasCtrl, "require_hotel_module('tarifas_dinamicas')") !== false,
    'TarifasController exige el modulo tarifas_dinamicas en su before()');

// El gate de modulo debe ir ANTES del de permiso: un hotel que no contrato el
// bloque no debe siquiera llegar a la evaluacion de RBAC.
$posModulo = strpos($tarifasCtrl, "require_hotel_module('tarifas_dinamicas')");
$posPermiso = strpos($tarifasCtrl, "require_permission_or_403('tarifas.view')");
t_ok($posModulo !== false && $posPermiso !== false && $posModulo < $posPermiso,
    'el gate de modulo precede al de permiso (el permiso no suple la contratacion)');

// ── 2) Descuentos: clase de tarifa gateada ──
t_ok(strpos($tarifasCtrl, 'claseTarifaPermitida') !== false,
    'la clase de tarifa pasa por claseTarifaPermitida()');
t_ok(substr_count($tarifasCtrl, '$this->claseTarifaPermitida($this->getPost(\'clase\'))') === 2,
    'crear Y editar tarifa usan el filtro de clase (ambos puntos de entrada)');
t_ok(strpos($tarifasCtrl, "!current_hotel_has_module('descuentos')") !== false,
    'claseTarifaPermitida consulta el modulo descuentos');

// La whitelist cruda ya no debe vivir fuera del helper: si reaparece en el
// arreglo de datos, alguien salto el gate.
t_ok(substr_count($tarifasCtrl, "in_array(\$this->getPost('clase'), ['incremento', 'descuento'], true)") === 0,
    'no queda whitelist de clase sin gatear fuera del helper');

// ── 3) Descuentos: override del operador en Reservaciones ──
t_ok(strpos($reservacionCtrl, 'puedeAplicarDescuentos') !== false,
    'ReservacionController declara el gate puedeAplicarDescuentos()');
t_ok(substr_count($reservacionCtrl, '$this->puedeAplicarDescuentos()') === 2,
    'los DOS puntos que leen descuento_aplicado del POST pasan por el gate');
t_ok(strpos($reservacionCtrl, "current_hotel_has_module('descuentos')") !== false,
    'el gate de descuentos consulta el modulo del hotel');

// Reservaciones es paquete base: el bloque ausente NUNCA debe dar 403 aqui.
t_ok(strpos($reservacionCtrl, "require_hotel_module('descuentos')") === false,
    'descuentos jamas gatea con 403 el flujo de Reservaciones (es paquete base)');

// ── 4) El precedente que ya existia sigue en pie ──
t_ok(strpos($huespedCtrl, "!current_hotel_has_module('descuentos')") !== false,
    'la captura de descuento del huesped sigue gateada (politica original)');

// ─────────────────────────────────────────────────────────────────────────────
// 5) Comportamiento real de los helpers, con catalogo sintetico en BD de prueba.
//    Se ejercita el hotel que SI contrata y el que NO, via Reflection sobre los
//    metodos privados (mismo patron que LavanderiaTest).
// ─────────────────────────────────────────────────────────────────────────────
t_reset_db();
$db = Database::getInstance();

// GOTCHA: hotel_active_module_keys memoiza en un `static $cache` POR PROCESO
// (helpers/modulos.php:29), que ms_cache_forget NO alcanza. Togglear el modulo
// del mismo hotel a mitad del test daria el valor viejo. Por eso van DOS hoteles:
// claves de cache distintas, sin colision.
$sinBloque = t_seed_base('gates-sin-descuentos');
$conBloque = t_seed_base('gates-con-descuentos');

$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('descuentos', 'Descuentos', 'test', 0, 'opcional', 99.00, 1, 10, NOW())"
);
$idDescuentos = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$conBloque['hotel_id'], $idDescuentos]
);

require_once dirname(__DIR__, 2) . '/app/controllers/TarifasController.php';
$tarifas = new TarifasController([]);
$refClase = new ReflectionMethod(TarifasController::class, 'claseTarifaPermitida');
$refClase->setAccessible(true);

// 5a) Hotel SIN el bloque: 'descuento' degrada a 'incremento', no truena.
$_SESSION['hotel_id'] = $sinBloque['hotel_id'];
t_eq('incremento', $refClase->invoke($tarifas, 'descuento'),
    'sin el bloque descuentos, una clase descuento degrada a incremento');
t_eq('incremento', $refClase->invoke($tarifas, 'incremento'),
    'sin el bloque, la clase incremento pasa intacta');
t_eq('incremento', $refClase->invoke($tarifas, 'basura'),
    'una clase invalida sigue cayendo a incremento (fail-closed)');

// 5b) Hotel CON el bloque contratado: 'descuento' se respeta.
$_SESSION['hotel_id'] = $conBloque['hotel_id'];
t_eq('descuento', $refClase->invoke($tarifas, 'descuento'),
    'con el bloque contratado, la clase descuento se respeta');

// 5c) El bloqueo GLOBAL del catalogo corta aunque el hotel lo tenga contratado
//     (mismo contrato que el resto de opcionales: activo_global manda).
$otro = t_seed_base('gates-bloqueo-global');
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$otro['hotel_id'], $idDescuentos]
);
$db->query("UPDATE modulos SET activo_global = 0 WHERE id = ?", [$idDescuentos]);
$_SESSION['hotel_id'] = $otro['hotel_id'];
t_eq('incremento', $refClase->invoke($tarifas, 'descuento'),
    'descuentos bloqueado global no aplica aunque el hotel lo tenga contratado');

t_fin();
