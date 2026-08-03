<?php
/**
 * Paridad entre el reporte del dia del SERVIDOR y el que arma el navegador
 * sin internet (js/reservaciones-reporte-offline.js).
 *
 * La plantilla vive duplicada en dos lenguajes a proposito — sin internet no hay
 * PHP que ejecutar — y una duplicacion sin prueba se desincroniza en silencio: el
 * hotelero acabaria viendo dos reportes distintos del MISMO dia segun tenga o no
 * red, que es exactamente lo que se prometio que no pasaria.
 *
 * Aqui se fija lo que TIENE que coincidir:
 *   1. las 8 columnas, en el mismo orden;
 *   2. la logica de costo (costoReporteDia), incluido el bug de ago-01;
 *   3. que la API del snapshot siga mandando los campos que el generador necesita.
 *
 * El COMPORTAMIENTO del lado JS lo prueba `node tools/tests_js/reporte_offline.test.js`
 * (29 casos). Este archivo prueba el lado PHP y el contrato entre ambos.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "ReporteOfflineParidadTest\n";

$rutaJs  = __DIR__ . '/../../public_html/js/reservaciones-reporte-offline.js';
$rutaPhp = __DIR__ . '/../../app/controllers/ReservacionController.php';
$rutaApi = __DIR__ . '/../../app/controllers/ApiController.php';

t_ok(is_file($rutaJs), 'el generador del cliente existe');
$js  = (string) file_get_contents($rutaJs);
$php = (string) file_get_contents($rutaPhp);
$api = (string) file_get_contents($rutaApi);

// ── 1. Las 8 columnas, en el mismo orden ────────────────────────────────────
// El servidor las declara en exportarExcelAction; el cliente en COLUMNAS.

$columnasServidor = [];
if (preg_match('/\$columnas\s*=\s*\[(.*?)\];/s', $php, $m)) {
    preg_match_all("/'([^']+)'/", $m[1], $mm);
    $columnasServidor = $mm[1];
}

$columnasCliente = [];
if (preg_match('/const COLUMNAS\s*=\s*\[(.*?)\];/s', $js, $m)) {
    preg_match_all("/'([^']+)'/", $m[1], $mm);
    $columnasCliente = $mm[1];
}

t_eq(count($columnasServidor), 8, 'el servidor declara 8 columnas');
t_eq(count($columnasCliente), 8, 'el cliente declara 8 columnas');
t_eq(
    implode('|', $columnasCliente),
    implode('|', $columnasServidor),
    'las columnas del cliente son EXACTAMENTE las del servidor, en el mismo orden'
);

// ── 2. Logica de costo: los mismos casos que corre el arnes JS ──────────────

$hab = ['id' => 2, 'precio_base' => 1000.0, 'tipo' => 'sencilla'];

// (a) Habitacion LIBRE con temporada vigente: el bug de ago-01. Con precio base
//     el reporte subcotiza y contradice a la vendida de al lado.
$conTemporada = ReservacionController::costoReporteDia($hab, null, [2 => 1200.0]);
t_eq($conTemporada['texto'], '$1,200', 'libre con temporada: sale con el incremento, no en base');
t_ok($conTemporada['con_temporada'] === true, 'libre con temporada: queda marcada como tal');

// (b) Habitacion LIBRE sin reglas: precio base.
$sinTemporada = ReservacionController::costoReporteDia($hab, null, []);
t_eq($sinTemporada['texto'], '$1,000', 'libre sin temporada: precio base');
t_ok($sinTemporada['con_temporada'] === false, 'libre sin temporada: no se marca');

// (c) Habitacion VENDIDA: manda el precio CONGELADO, aunque haya temporada.
$vendida = ReservacionController::costoReporteDia(
    $hab,
    ['precio_hab' => 1200.0, 'es_cortesia_hab' => 0],
    [2 => 9999.0]
);
t_eq($vendida['texto'], '$1,200', 'vendida: precio congelado, la temporada no la recotiza');
t_ok($vendida['con_temporada'] === false, 'vendida: nunca se marca como temporada');

// (d) Cortesia: gana sobre cualquier precio.
$cortesia = ReservacionController::costoReporteDia(
    $hab,
    ['precio_hab' => 1200.0, 'es_cortesia_hab' => 1],
    [2 => 1200.0]
);
t_eq($cortesia['texto'], 'Cortesía', 'cortesia: gana sobre el precio congelado');
t_eq((float) $cortesia['monto'], 0.0, 'cortesia: monto cero');

// (e) Vendida sin precio en la fila: cae al base (no rompe ni deja el costo vacio).
$sinPrecio = ReservacionController::costoReporteDia($hab, ['es_cortesia_hab' => 0], []);
t_eq($sinPrecio['texto'], '$1,000', 'vendida sin precio guardado: cae al base');

// ── 3. Formato de moneda: mismos valores que el arnes JS ───────────────────

$montos = [999, 1000, 1200, 12500, 1000000];
$formateados = array_map([ReservacionController::class, 'formatoMonedaReporteDia'], $montos);
t_eq(
    implode(' ', $formateados),
    '$999 $1,000 $1,200 $12,500 $1,000,000',
    'formato de moneda identico al del cliente'
);
// El separador de miles es COMA, nunca punto: con punto Excel lee $1.200 como
// $1.20 en locale es-MX (bug real corregido en jul-31).
t_ok(strpos($formateados[2], ',') !== false, 'el separador de miles es coma, no punto');

// ── 4. La API del snapshot manda lo que el generador necesita ──────────────
// Sin estos campos el reporte offline sale con columnas vacias.

foreach ([
    'procedencia_ciudad'  => "'procedencia_ciudad' => \$r['procedencia_ciudad']",
    'habitaciones'        => "'habitaciones' => \$habitacionesPorReservacion",
    'vehiculo'            => "'vehiculo' => \$vehiculoPorHuesped",
    'tiene_factura'       => "'tiene_factura' => isset(\$facturaPorReservacion",
] as $campo => $fragmento) {
    t_ok(strpos($api, $fragmento) !== false, "la API del snapshot manda '$campo'");
}

// El precio por habitacion es lo que hace posible el punto 2(c) sin internet.
t_ok(
    strpos($api, 'rh.precio') !== false && strpos($api, 'rh.es_cortesia') !== false,
    'la API manda el precio congelado y la cortesia de cada habitacion'
);

// Las placas internas JAMAS viajan al navegador (contrato de placas internas).
t_ok(
    strpos($api, 'HuespedVehiculo::placasVisibles') !== false,
    'las placas del snapshot van enmascaradas'
);

// El bloque 'vehiculos' se respeta tambien en el snapshot: si el hotel no lo
// contrato, no se filtran datos de vehiculos a IndexedDB.
t_ok(
    strpos($api, 'hotel_parking_visible') !== false,
    'el snapshot respeta el bloque de estacionamiento'
);

// ── 5. El snapshot de tarifas cubre un RANGO ───────────────────────────────
// Con solo las reglas de hoy, una fecha futura saldria en precio base: el mismo
// defecto del punto 2(a), pero desplazado en el calendario.

t_ok(
    method_exists('IncrementoTarifa', 'getActivosParaRango'),
    'el modelo de tarifas sabe responder por un rango'
);

$offlineData = (string) file_get_contents(__DIR__ . '/../../public_html/js/offline-data.js');
t_ok(
    strpos($offlineData, '/api/tarifas/incrementos?dias=') !== false,
    'el cliente pide las tarifas del rango, no solo las de hoy'
);
t_ok(
    strpos($offlineData, 'obtenerDatosReporteDia') !== false,
    'el cliente expone el lector que consume el generador'
);

// ── 6. El generador viaja en el precache del service worker ────────────────
// Si no, no existe justo cuando se necesita: cuando no hay red para ir por el.

$sw = (string) file_get_contents(__DIR__ . '/../../public_html/service-worker.js');
t_ok(
    strpos($sw, 'js/reservaciones-reporte-offline.js') !== false,
    'el service worker precachea el generador'
);

// ── 7. Con red sigue mandando el servidor ──────────────────────────────────
// El offline es un respaldo, no un reemplazo: el servidor ve estados y fechas
// que el snapshot no guarda.

$vista = (string) file_get_contents(__DIR__ . '/../../app/views/reservaciones/index.php');
t_ok(
    strpos($vista, 'resHayConexion') !== false
        && strpos($vista, "reservaciones/exportar-pdf?fecha=") !== false,
    'la vista conserva el camino al servidor y solo desvia sin conexion'
);
t_ok(
    strpos($vista, 'hayConexionAhora') !== false,
    'mide la red con hayConexionAhora (medicion real), no con la foto de isOnline'
);

t_fin();
