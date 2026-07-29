<?php

require_once __DIR__ . '/../bootstrap.php';

echo "GatesEstacionamientoTest\n";

// ─────────────────────────────────────────────────────────────────────────────
// Contrato: el ESTACIONAMIENTO completo (registro de vehículos, indicador del
// tablero, proyección, chips del listado, bloque del PDF de cotización) cuelga
// de UN solo predicado — hotel_parking_visible(), que consulta el bloque
// 'vehiculos' ($99).
//
// Origen (2026-07-29, tanda 2 de las fugas satélite): el bloque ya gateaba la
// MITAD de la función (los 3 endpoints AJAX de HuespedController y el mapa de
// ApiController para vehiculosHuesped), mientras la otra mitad seguía encendida
// para cualquier hotel: la tarjeta del tablero, la API de proyección —mapeada
// al módulo 'dashboard', que es paquete base—, el POST del alta de huésped, el
// PDF que se entrega al huésped, los chips del listado y las fichas contador.
// Incoherencia asimétrica: no podías capturar placas y sí verlas proyectadas.
//
// Estacionamiento vive DENTRO de pantallas del paquete base (tablero, huéspedes,
// habitaciones, reservaciones) ⇒ se GATEA, jamás se 403ea (mismo criterio que
// descuentos y facturación). Los 3 endpoints AJAX preexistentes sí cortan con
// require_hotel_module porque son AJAX y responden JSON.
// ─────────────────────────────────────────────────────────────────────────────

$root = dirname(__DIR__, 2);
$modulosHelper   = (string) file_get_contents($root . '/app/helpers/modulos.php');
$apiCtrl         = (string) file_get_contents($root . '/app/controllers/ApiController.php');
$reservacionCtrl = (string) file_get_contents($root . '/app/controllers/ReservacionController.php');
$huespedCtrl     = (string) file_get_contents($root . '/app/controllers/HuespedController.php');
$dashboardView   = (string) file_get_contents($root . '/app/views/dashboard/index.php');
$reservacionView = (string) file_get_contents($root . '/app/views/reservaciones/ver.php');
$huespedesIndex  = (string) file_get_contents($root . '/app/views/huespedes/index.php');
$huespedVerView  = (string) file_get_contents($root . '/app/views/huespedes/ver.php');
$habitacionVer   = (string) file_get_contents($root . '/app/views/habitaciones/ver.php');
$habitacionHist  = (string) file_get_contents($root . '/app/views/habitaciones/historial.php');

// ── 1) El predicado único ──
t_ok(strpos($modulosHelper, 'function hotel_parking_visible(') !== false,
    'helpers/modulos.php declara el predicado hotel_parking_visible()');
t_ok(strpos($modulosHelper, "hotel_has_module('vehiculos', (int) \$hotelId)") !== false,
    'con hotel explicito consulta el bloque de ESE hotel (obligatorio en el panel SaaS)');
t_ok(strpos($modulosHelper, "hotel_menu_module_enabled('vehiculos')") !== false,
    'sin hotel usa la semantica de menu (fail-open y todo visible bajo /admin/saas)');

// ── 2) La API de proyección dejó de colgar del paquete base ──
t_ok(strpos($apiCtrl, "'estacionamientoProyeccion' => 'vehiculos'") !== false,
    'el mapa de modulos apunta la proyeccion al bloque vehiculos');
t_ok(strpos($apiCtrl, "'estacionamientoProyeccion' => 'dashboard'") === false,
    'ya no la alcanza cualquier hotel por ser dashboard (paquete base)');
t_ok(strpos($apiCtrl, "'vehiculosHuesped' => 'vehiculos'") !== false,
    'el endpoint de placas conserva su gate historico (los dos coinciden ahora)');

// ── 3) Tablero: se apaga el PRODUCTOR, no solo la pintura ──
t_ok(strpos($dashboardView, '$parking_activo_dashboard = !function_exists(\'hotel_parking_visible\')') !== false,
    'el tablero resuelve el predicado una vez');
t_ok(strpos($dashboardView, '? get_estado_estacionamiento_dashboard()') !== false,
    'sin el bloque el tablero NO consulta estacionamiento (ahorra 2 consultas + placas)');
t_eq(2, substr_count($dashboardView, 'if ($parking_activo_dashboard):'),
    'las DOS vistas (escritorio y movil) se apagan juntas o la tira movil queda vacia sin error');

// ── 4) Ficha de reservación: bloque gateado y el catch que borraba la lista ──
t_ok(strpos($reservacionView, '$rdParkingActivo = !function_exists(\'hotel_parking_visible\')') !== false,
    'ver.php define $rdParkingActivo junto a $rdDocumentosActivo');
t_eq(1, substr_count($reservacionView, 'if ($rdParkingActivo):'),
    'el subencabezado, la lista y el status de vehiculos van en un solo bloque gateado');
t_ok(strpos($reservacionView, "listaVehiculos.innerHTML =\n                '<p class=\"text-center text-gray-500 text-sm\">Error al cargar vehículos</p>'") === false,
    'BUG VIVO CERRADO: el .catch ya no reemplaza la lista del servidor por "Error al cargar vehiculos"');
t_ok(strpos($reservacionView, 'JAMÁS pisar la lista que el servidor ya pintó') !== false,
    'queda escrito por que el catch no debe tocar #listaVehiculos');

// ── 5) Productores del controlador de reservaciones (4 puntos) ──
t_ok(strpos($reservacionCtrl, "!hotel_parking_visible(\$hotelId)") !== false,
    'cotizacionPdfVehiculos corta de entrada (un punto cubre las 2 acciones de cotizacion)');
t_ok(strpos($reservacionCtrl, '$parkingVisible = !function_exists(\'hotel_parking_visible\')') !== false,
    'la ficha de reservacion no consulta placas sin el bloque');
t_ok(strpos($reservacionCtrl, "method_exists(\$this->huespedModel, 'getVehiculosPorHotel')") !== false,
    'el guard pregunta por el metodo que realmente invoca (antes preguntaba por getVehiculos)');
t_eq(2, substr_count($reservacionCtrl, "|| hotel_parking_visible(\$hotel_id))"),
    'los exports a PDF y Excel dejan de imprimir marca/modelo/color del huesped');
t_ok(strpos($reservacionCtrl, "require_hotel_module('vehiculos')") === false,
    'Reservaciones es paquete base: estacionamiento jamas gatea con 403 aqui');

// ── 6) Huéspedes: el cuarto punto de ESCRITURA y las lecturas de PII ──
t_eq(3, substr_count($huespedCtrl, "require_hotel_module('vehiculos')"),
    'los 3 endpoints AJAX (agregar/actualizar/eliminar) conservan su 403 JSON');
t_ok(strpos($huespedCtrl, "? \$this->getPost('vehiculos', [])") !== false,
    'guardarAction IGNORA en silencio el arreglo vehiculos[] sin el bloque (4o punto de escritura)');
t_ok(strpos($huespedCtrl, "? \$this->huespedModel->getVehiculosPorHotel(\$id, \$hotelId)") !== false,
    'verAction no carga placas sin el bloque');

// ── 7) Vistas que sobrevivían a todo el gate ──
t_ok(strpos($huespedesIndex, '$guestParkingActivo = !function_exists') !== false,
    'el listado de huespedes resuelve el predicado');
t_ok(strpos($huespedesIndex, '$vehiculoModel = (!empty($huespedes) && $guestParkingActivo)') !== false,
    'sin el bloque no instancia HuespedVehiculo (mata de paso el N+1 del listado)');
t_eq(2, substr_count($huespedesIndex, 'if ($guestParkingActivo): ?>'),
    'los chips de escritorio y de tarjeta movil se apagan (incluida la rama "Sin vehiculo")');
t_ok(strpos($huespedVerView, '$guestVerParkingActivo = !function_exists') !== false,
    'la ficha del huesped resuelve el predicado');
t_eq(4, substr_count($huespedVerView, '$guestVerParkingActivo'),
    'la definicion + los 3 consumidores: 2 fichas contador y la tarjeta "Vehiculo anterior" (columnas legacy)');
t_ok(strpos($habitacionVer, "hotel_parking_visible()): ?>") !== false,
    'el tile Vehiculos del detalle de habitacion va gateado');
t_ok(strpos($habitacionHist, 'hotel_parking_visible())') !== false,
    'el chip "N veh." del historial de la habitacion va gateado');

// ─────────────────────────────────────────────────────────────────────────────
// 8) Comportamiento real del predicado con catálogo sintético.
//    GOTCHA heredado: hotel_active_module_keys memoiza por proceso → un hotel
//    NUEVO por escenario, jamás togglear el mismo.
// ─────────────────────────────────────────────────────────────────────────────
t_reset_db();
$db = Database::getInstance();

$sinBloque = t_seed_base('parking-sin');
$conBloque = t_seed_base('parking-con');
$global    = t_seed_base('parking-global');

$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('vehiculos', 'Vehiculos y estacionamiento', 'test', 0, 'opcional', 99.00, 1, 13, NOW())"
);
$idVehiculos = (int) $db->lastInsertId();

foreach ([$conBloque, $global] as $hotelConContrato) {
    $db->query(
        "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
         VALUES (?, ?, 1, 'manual', NOW(), NOW())",
        [$hotelConContrato['hotel_id'], $idVehiculos]
    );
}

// 8a) Hotel sin contratar: estacionamiento apagado (y no truena).
t_eq(false, hotel_parking_visible($sinBloque['hotel_id']),
    'sin el bloque vehiculos el estacionamiento queda apagado para ese hotel');

// 8b) Hotel con el bloque: todo sigue vivo.
t_eq(true, hotel_parking_visible($conBloque['hotel_id']),
    'con el bloque contratado el estacionamiento opera normal');

// 8c) Bloqueo GLOBAL del catálogo corta aunque el hotel lo tenga contratado.
$db->query("UPDATE modulos SET activo_global = 0 WHERE id = ?", [$idVehiculos]);
t_eq(false, hotel_parking_visible($global['hotel_id']),
    'vehiculos bloqueado global no opera aunque el hotel lo tenga contratado');

// 8d) La variante SIN hotel explícito responde por la sesión, no por el hotel
//     del admin: es la que usan las vistas del hotelero.
$_SESSION['hotel_id'] = $sinBloque['hotel_id'];
$_SESSION['hotel_slug'] = 'parking-sin';
$_SERVER['REQUEST_URI'] = '/dashboard';
t_eq(false, hotel_parking_visible(),
    'la variante de sesion tambien apaga al hotel sin el bloque');

// 8e) Bajo /admin/saas el helper de menú muestra todo a propósito (el panel
//     configura hoteles ajenos): ahí SIEMPRE hay que pasar el hotel explícito.
$_SERVER['REQUEST_URI'] = '/admin/saas/hoteles/1';
t_eq(true, hotel_parking_visible(),
    'en el panel SaaS la variante sin hotel responde true (por eso se pasa $hotelId)');
t_eq(false, hotel_parking_visible($sinBloque['hotel_id']),
    'y con hotel explicito sigue contestando por el hotel objetivo dentro del panel');
$_SERVER['REQUEST_URI'] = '/dashboard';

t_fin();
