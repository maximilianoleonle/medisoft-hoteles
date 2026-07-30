<?php

require_once __DIR__ . '/../bootstrap.php';

echo "GatesMotorTarifasTest\n";

// ─────────────────────────────────────────────────────────────────────────────
// Contrato: el MOTOR de tarifas respeta la contratación POR CLASE de regla.
//   clase 'incremento' → bloque `tarifas_dinamicas` ($149)
//   clase 'descuento'  → bloque `descuentos` ($99)
//
// Origen (2026-07-29, tanda 3): c74b407 blindó las PANTALLAS (TarifasController)
// y el override manual del operador, pero las reglas ya guardadas seguían
// aplicándose desde ~20 consumidores — modelo Reservacion, las 2 cotizaciones
// PDF, MotorReservaOnlineService (motor público), Copiloto IA, HabitacionController
// y la API de cálculo. Fuga real: el hotel que contrató y CANCELÓ seguía
// cobrando sus incrementos, y con /tarifas en 403 no tenía forma de apagarlos.
//
// El candado va en el ÚNICO punto por el que pasan las dos calculadoras
// (IncrementoTarifa::getIncrementosAplicables), no en cada consumidor.
// Lo YA cobrado se conserva: precio_total y descuento_total viven en la
// reservación y nadie los recalcula.
// ─────────────────────────────────────────────────────────────────────────────

$root = dirname(__DIR__, 2);
$motor   = (string) file_get_contents($root . '/app/models/IncrementoTarifa.php');
$apiCtrl = (string) file_get_contents($root . '/app/controllers/ApiController.php');

// ── 1) Un solo punto de estrangulamiento, con la clase como llave ──
t_ok(strpos($motor, 'private function claseTarifaContratada(') !== false,
    'IncrementoTarifa resuelve el bloque que gobierna cada clase de regla');
t_ok(strpos($motor, "\$bloque = (\$clase === 'descuento') ? 'descuentos' : 'tarifas_dinamicas';") !== false,
    'incremento → tarifas_dinamicas, descuento → descuentos');
t_ok(strpos($motor, 'if (!$this->claseTarifaContratada($clase, $hotelId)) {') !== false,
    'el candado vive en getIncrementosAplicables (cubre las 2 calculadoras y sus ~20 consumidores)');
t_eq(1, substr_count($motor, '$this->claseTarifaContratada('),
    'un solo llamado: si aparecieran dos, habria dos verdades del motor');

// ── 2) La API que alimenta pantallas del paquete base filtra por clase ──
t_ok(strpos($apiCtrl, "hotel_has_module('tarifas_dinamicas', \$hotelIdApi)") !== false,
    'incrementosTarifaActivos filtra los incrementos por su bloque');
t_ok(strpos($apiCtrl, "hotel_has_module('descuentos', \$hotelIdApi)") !== false,
    'y los descuentos por el suyo (el endpoint esta mapeado a reservaciones, que es base)');

// ─────────────────────────────────────────────────────────────────────────────
// 3) Comportamiento real: 4 hoteles, uno por combinación de bloques.
//    GOTCHA heredado: hotel_active_module_keys memoiza por proceso → un hotel
//    NUEVO por escenario, jamás togglear el mismo.
// ─────────────────────────────────────────────────────────────────────────────
t_reset_db();
$db = Database::getInstance();

$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('tarifas_dinamicas', 'Tarifas dinamicas', 'test', 0, 'opcional', 149.00, 1, 14, NOW())"
);
$idTarifas = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('descuentos', 'Descuentos', 'test', 0, 'opcional', 99.00, 1, 15, NOW())"
);
$idDescuentos = (int) $db->lastInsertId();

$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));
$manana = date('Y-m-d', strtotime('+30 days'));

// Cada hotel nace con una regla de incremento (20%) y una de descuento (10%)
// idénticas: lo único que cambia entre escenarios es qué bloques contrata.
$prepararHotel = function (string $slug, array $bloques) use ($db, $idTarifas, $idDescuentos, $ayer, $manana): int {
    $base = t_seed_base($slug);
    $hotelId = (int) $base['hotel_id'];

    foreach ($bloques as $moduloId) {
        $db->query(
            "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
             VALUES (?, ?, 1, 'manual', NOW(), NOW())",
            [$hotelId, $moduloId]
        );
    }

    foreach ([['Temporada alta', 'incremento', 20.00], ['Promo web', 'descuento', 10.00]] as $regla) {
        $db->query(
            "INSERT INTO incrementos_tarifas
                (hotel_id, nombre, tipo_incremento, clase, valor_incremento, alcance, tipos_habitacion, habitaciones,
                 es_permanente, fecha_inicio, fecha_fin, activo, prioridad, usuario_id)
             VALUES (?, ?, 'porcentaje', ?, ?, 'global', NULL, NULL, 0, ?, ?, 1, 0, ?)",
            [$hotelId, $regla[0], $regla[1], $regla[2], $ayer, $manana, (int) $base['usuario_id']]
        );
    }

    return $hotelId;
};

$conAmbos   = $prepararHotel('motor-ambos', [$idTarifas, $idDescuentos]);
$sinNada    = $prepararHotel('motor-sin', []);
$soloTarifa = $prepararHotel('motor-solo-tarifa', [$idTarifas]);
$soloDesc   = $prepararHotel('motor-solo-desc', [$idDescuentos]);

require_once dirname(__DIR__, 2) . '/app/models/IncrementoTarifa.php';
$motorModel = new IncrementoTarifa();

// 3a) Hotel con ambos bloques: el motor opera como siempre.
$calc = $motorModel->calcularPrecioConIncremento(0, 'sencilla', 1000.00, $hoy, $conAmbos);
t_eq(1200.0, round((float) $calc['precio_final'], 2),
    'con tarifas_dinamicas contratado el incremento del 20% se aplica');
$desc = $motorModel->calcularDescuentoPorTipo('sencilla', 1000.00, $hoy, $conAmbos);
t_eq(100.0, round((float) $desc['descuento_total'], 2),
    'con descuentos contratado el 10% se aplica');

// 3b) Hotel que canceló todo: las reglas guardadas dejan de cobrarse.
$calcSin = $motorModel->calcularPrecioConIncremento(0, 'sencilla', 1000.00, $hoy, $sinNada);
t_eq(1000.0, round((float) $calcSin['precio_final'], 2),
    'sin el bloque el precio vuelve al base: la regla guardada ya no cobra');
t_eq(0, count($calcSin['incrementos_aplicados']),
    'y no reporta incrementos aplicados (el consumidor ve cero reglas, no un error)');
$descSin = $motorModel->calcularDescuentoPorTipo('sencilla', 1000.00, $hoy, $sinNada);
t_eq(0.0, round((float) $descSin['descuento_total'], 2),
    'sin el bloque descuentos, el descuento guardado no se aplica');

// 3c) El gate es POR CLASE: contratar uno no arrastra al otro. Es el caso real
//     de Los Cedros en produccion (tarifas_dinamicas si, descuentos no).
$calcSolo = $motorModel->calcularPrecioConIncremento(0, 'sencilla', 1000.00, $hoy, $soloTarifa);
$descSolo = $motorModel->calcularDescuentoPorTipo('sencilla', 1000.00, $hoy, $soloTarifa);
t_eq(1200.0, round((float) $calcSolo['precio_final'], 2),
    'solo tarifas_dinamicas: el incremento SI se aplica');
t_eq(0.0, round((float) $descSolo['descuento_total'], 2),
    'solo tarifas_dinamicas: el descuento NO se aplica');

$calcSoloD = $motorModel->calcularPrecioConIncremento(0, 'sencilla', 1000.00, $hoy, $soloDesc);
$descSoloD = $motorModel->calcularDescuentoPorTipo('sencilla', 1000.00, $hoy, $soloDesc);
t_eq(1000.0, round((float) $calcSoloD['precio_final'], 2),
    'solo descuentos: el incremento NO se aplica');
t_eq(100.0, round((float) $descSoloD['descuento_total'], 2),
    'solo descuentos: el descuento SI se aplica');

// 3d) Bloqueo GLOBAL del catálogo corta aunque el hotel lo tenga contratado.
$globalOff = $prepararHotel('motor-global-off', [$idTarifas]);
$db->query("UPDATE modulos SET activo_global = 0 WHERE id = ?", [$idTarifas]);
$calcGlobal = $motorModel->calcularPrecioConIncremento(0, 'sencilla', 1000.00, $hoy, $globalOff);
t_eq(1000.0, round((float) $calcGlobal['precio_final'], 2),
    'tarifas_dinamicas bloqueada global no cobra aunque el hotel la tenga contratada');

// 3e) Las reglas siguen EXISTIENDO: cancelar un bloque no borra la configuración
//     del hotel (se congela, como hotel_modulos). Al recontratar, revive.
$reglas = $db->query(
    "SELECT COUNT(*) AS total FROM incrementos_tarifas WHERE hotel_id = ? AND activo = 1",
    [$sinNada]
)->fetch();
t_eq(2, (int) $reglas['total'],
    'apagar el bloque NO borra las reglas guardadas del hotel (historial congelado)');

t_fin();
