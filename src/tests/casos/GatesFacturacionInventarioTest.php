<?php

require_once __DIR__ . '/../bootstrap.php';

echo "GatesFacturacionInventarioTest\n";

// ─────────────────────────────────────────────────────────────────────────────
// Contrato: los bloques opcionales 'facturacion' ($249) e 'inventario' ($299)
// no deben producir efectos desde flujos del PAQUETE BASE cuando el hotel no
// los tiene contratados.
//
// Origen (2026-07-29, auditoría de fugas satélite — la deuda que c74b407 dejó
// anotada al blindar tarifas_dinamicas y descuentos):
//   - facturacion: la pregunta "¿requiere factura?" se pintaba SIEMPRE en los
//     4 flujos de reservaciones/ver.php (anticipos, check-in, tardío, cambio
//     de pago) y procesarSolicitudFactura escribía solicitudes —incluso de
//     'uso_interno' con "no"+tarjeta/transferencia— que el hotelero jamás
//     podía ver: /facturacion le rebotaba por el gate del controller.
//   - inventario: procesarDescuentoInventario descontaba stock en cada
//     check-in con solo mirar inventario_config_habitacion.activo=1; un hotel
//     que canceló el bloque seguía consumiendo inventario a ciegas.
//   - footer: los atajos Documentos y Compras seguían proxies de módulos
//     ajenos (huespedes/reservaciones son base ⇒ atajo visible para todos)
//     y mandaban a un 403.
//
// Igual que descuentos: el gate JAMÁS es un 403 en flujos del paquete base —
// el campo se ignora en silencio y la UI no pinta la pregunta.
// ─────────────────────────────────────────────────────────────────────────────

$root = dirname(__DIR__, 2);
$reservacionCtrl = (string) file_get_contents($root . '/app/controllers/ReservacionController.php');
$verView = (string) file_get_contents($root . '/app/views/reservaciones/ver.php');
$indexView = (string) file_get_contents($root . '/app/views/reservaciones/index.php');

// ── 1) Facturación: candado de servidor en el productor ──
t_ok(strpos($reservacionCtrl, 'private function puedeRegistrarFacturas()') !== false,
    'ReservacionController declara el gate puedeRegistrarFacturas()');
t_ok(strpos($reservacionCtrl, "current_hotel_has_module('facturacion')") !== false,
    'el gate de facturación consulta el módulo del hotel');
t_ok(strpos($reservacionCtrl, 'if (!$this->puedeRegistrarFacturas())') !== false,
    'procesarSolicitudFactura corta de entrada sin el bloque (cubre sus 4 llamadas)');
t_ok(strpos($reservacionCtrl, "\$this->puedeRegistrarFacturas() && \$this->getPost('requiere_factura')") !== false,
    'el anticipo deriva requiere_factura con el gate (y guarda no en el abono)');

// Reservaciones es paquete base: el bloque ausente NUNCA debe dar 403 aquí.
t_ok(strpos($reservacionCtrl, "require_hotel_module('facturacion')") === false,
    'facturación jamás gatea con 403 el flujo de Reservaciones (es paquete base)');

// La reversión de un anticipo facturado NO pasa por el gate a propósito: el
// ajuste negativo corrige una solicitud nacida con el módulo activo.
t_ok(strpos($reservacionCtrl, "(\$resultado['requiere_factura'] ?? 'no') === 'si'") !== false,
    'el ajuste por reversión sigue condicionado al abono, no al módulo (historial reparable)');

// ── 2) Facturación: la vista no pinta la pregunta sin el bloque ──
t_ok(strpos($verView, "hotel_menu_module_enabled('facturacion')") !== false,
    'ver.php define $rvModuloFacturacion contra el módulo facturacion');
t_eq(5, substr_count($verView, 'if ($rvModuloFacturacion):'),
    'los 5 bloques de factura van gateados (anticipos, resumen, check-in, cambio de pago, tardío)');
t_eq(3, substr_count($verView, 'if (!facturaSi || !facturaNo)'),
    'los 3 validadores JS tratan la pregunta ausente como "no aplica" (no bloquean el cobro)');
t_ok(strpos($verView, "document.querySelector('input[name=\"requiere_factura_cp\"]:checked').value") === false,
    'el fetch de cambio de pago ya no truena cuando la pregunta no existe');

// ── 2b) QA en navegador (2026-07-29): la etapa 3 no puede llamarse "Factura" ──
// Hallazgo real con el hotel Demo de produccion: el gate quitaba el contenido
// pero recepcion seguia llegando a un paso 3 llamado "Factura" y vacio, con el
// aria-labelledby apuntando a un <h4> que ya no se renderiza.
t_eq(2, substr_count($verView, "\$rvModuloFacturacion ? 'Factura' : 'Confirmar'"),
    'los DOS steppers (wizard de check-in y wizard de anticipos) renombran su paso');

// El marcado NO alcanzaba: setCheckInWizardStep PISA la etiqueta en cada cambio
// de etapa. Se vio en produccion — el paso seguia diciendo "Factura" con el
// ternario de PHP ya desplegado, porque el JS la reescribia encima.
t_ok(strpos($verView, '$rvModuloFacturacion ? "\'Factura\'" : "\'Confirmar\'"') !== false,
    'el JS que reescribe la etiqueta del paso 3 tambien consulta el bloque');
t_ok(strpos($verView, "sinCobroNuevo ? 'Sin cobro nuevo' : 'Factura'") === false,
    'ya no queda el "Factura" hardcodeado que pisaba lo que pinto PHP');
t_ok(strpos($verView, "'Sin cobro nuevo'") !== false,
    'y se conserva la etiqueta de reservacion ya pagada, que no depende del bloque');
t_ok(strpos($verView, 'aria-label="Confirmar check-in"') !== false,
    'la etapa 3 sin factura se etiqueta sola (no apunta a un titulo inexistente)');
t_ok(strpos($verView, 'is-solo-resumen') !== false,
    'la etapa queda marcada como solo-resumen para poder estilizarla aparte');

// Trampa PREEXISTENTE que este QA dejo al descubierto (no la causo el gate, se
// verifico A/B con el bloque activo): con el efectivo recibido vacio el boton se
// deshabilita a proposito, pero el aviso no decia que hacer.
t_ok(strpos($verView, 'Captura cuánto efectivo recibiste, o toca "Pagó exacto"') !== false,
    'el aviso del efectivo dice QUE hacer y menciona el atajo existente');
$appJs = (string) file_get_contents($root . '/public_html/js/app.js');
// Decision del owner (29-jul): el caso "todavia no lo capturaste" NO pinta
// mensaje. El campo vive en el paso Pago y el aviso salia en la cabecera del
// modal, o sea en OTRO paso y en rojo, hablando de algo que no esta a la vista.
t_ok(strpos($appJs, 'Captura cuanto efectivo recibiste') === false,
    'el aviso de "falta capturar" ya no se pinta en el modal');
t_ok(strpos($appJs, '!sinCapturar') !== false,
    'el showError del validador queda apagado para el caso vacio');
t_ok(strpos($appJs, 'El efectivo recibido no cubre el monto a cobrar.') !== false,
    'pero el mensaje de INSUFICIENTE se conserva: ahi si hay algo que corregir');
// El freno NO se relaja: setCustomValidity sigue marcando el campo invalido.
t_ok(strpos($appJs, "sinCapturar\n                        ? 'Falta capturar el efectivo recibido.'") !== false
    || strpos($appJs, "? 'Falta capturar el efectivo recibido.'") !== false,
    'el campo sigue invalido (nadie llama reportValidity, ese texto nunca se ve)');
t_ok(strpos($appJs, 'function hideFieldErrorMessage') !== false,
    'existe el helper que retira el texto sin declarar valido el campo (clearFieldError no puede)');

// El QUINTO productor (hallazgo de la revisión adversarial 2026-07-29): el
// modal de check-in del LISTADO /reservaciones postea a la misma acción y
// tenía su propia pregunta obligatoria sin gate — pregunta forzada + servidor
// que la traga en silencio, la peor combinación.
t_ok(strpos($indexView, "hotel_menu_module_enabled('facturacion')") !== false,
    'reservaciones/index.php define $resModuloFacturacion contra el módulo');
t_eq(1, substr_count($indexView, 'if ($resModuloFacturacion):'),
    'la sección Factura del modal de check-in del listado va gateada');
t_eq(1, substr_count($indexView, 'if (!facturaSi || !facturaNo)'),
    'el validador del listado trata la pregunta ausente como "no aplica"');

// ── 3) Inventario: el check-in no descuenta stock sin el bloque ──
t_ok(strpos($reservacionCtrl, "!hotel_has_module('inventario', \$hotel_id)") !== false,
    'procesarDescuentoInventario corta de entrada sin el bloque inventario');
t_ok(strpos($reservacionCtrl, "require_hotel_module('inventario')") === false,
    'inventario jamás gatea con 403 el flujo de Reservaciones (es paquete base)');

// ── 4) Footer: los atajos siguen la contratación REAL de su bloque ──
$footerCatalogo = hotel_footer_nav_catalog();
t_eq(['documentos'], $footerCatalogo['documentos']['modules_any'],
    'el atajo Documentos depende solo del bloque documentos (no de módulos base)');
t_eq(['compras'], $footerCatalogo['compras']['modules_any'],
    'el atajo Compras depende de su propio bloque, no de Inventario');

// ─────────────────────────────────────────────────────────────────────────────
// 5) Comportamiento real del gate, con catálogo sintético en BD de prueba.
//    newInstanceWithoutConstructor: puedeRegistrarFacturas() no toca estado de
//    instancia y así el test no arrastra el constructor completo del controller.
//    GOTCHA heredado de GatesBloquesVentaTest: hotel_active_module_keys memoiza
//    por proceso → un hotel NUEVO por cada escenario, jamás togglear el mismo.
// ─────────────────────────────────────────────────────────────────────────────
t_reset_db();
$db = Database::getInstance();

$sinBloque = t_seed_base('gates-fact-sin');
$conBloque = t_seed_base('gates-fact-con');

$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('facturacion', 'Control de facturacion', 'test', 0, 'opcional', 249.00, 1, 11, NOW())"
);
$idFacturacion = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$conBloque['hotel_id'], $idFacturacion]
);

require_once dirname(__DIR__, 2) . '/app/controllers/ReservacionController.php';
$refCtrl = new ReflectionClass(ReservacionController::class);
$reservaciones = $refCtrl->newInstanceWithoutConstructor();
$refGate = new ReflectionMethod(ReservacionController::class, 'puedeRegistrarFacturas');
$refGate->setAccessible(true);

// 5a) Hotel SIN el bloque: no se registran facturas (y no truena).
$_SESSION['hotel_id'] = $sinBloque['hotel_id'];
t_eq(false, $refGate->invoke($reservaciones),
    'sin el bloque facturacion, el productor queda apagado');

// 5b) Hotel CON el bloque contratado: el flujo sigue vivo.
$_SESSION['hotel_id'] = $conBloque['hotel_id'];
t_eq(true, $refGate->invoke($reservaciones),
    'con el bloque contratado, el flujo de factura opera normal');

// 5c) Bloqueo GLOBAL del catálogo corta aunque el hotel lo tenga contratado.
$otro = t_seed_base('gates-fact-global');
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$otro['hotel_id'], $idFacturacion]
);
$db->query("UPDATE modulos SET activo_global = 0 WHERE id = ?", [$idFacturacion]);
$_SESSION['hotel_id'] = $otro['hotel_id'];
t_eq(false, $refGate->invoke($reservaciones),
    'facturacion bloqueada global no opera aunque el hotel la tenga contratada');

// 5d) El gate de inventario usa hotel_has_module con hotel explícito: mismo
//     contrato para el hotel que contrata y el que no.
$invSin = t_seed_base('gates-inv-sin');
$invCon = t_seed_base('gates-inv-con');
$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('inventario', 'Inventario', 'test', 0, 'opcional', 299.00, 1, 12, NOW())"
);
$idInventario = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$invCon['hotel_id'], $idInventario]
);
t_eq(false, hotel_has_module('inventario', $invSin['hotel_id']),
    'sin el bloque inventario, el check-in no descuenta stock');
t_eq(true, hotel_has_module('inventario', $invCon['hotel_id']),
    'con el bloque contratado, el descuento automático sigue operando');

t_fin();
