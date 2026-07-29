<?php
/**
 * Helpers de lectura para modulos activos por hotel.
 */

function hotel_has_module($clave, $hotelId = null) {
    $hotelId = $hotelId ?: current_hotel_id();

    if (!$hotelId || !$clave) {
        return false;
    }

    $clavesActivas = hotel_active_module_keys((int) $hotelId);

    if ($clavesActivas === null) {
        return false;
    }

    return in_array((string) $clave, $clavesActivas, true);
}

function hotel_active_module_keys($hotelId = null) {
    $hotelId = $hotelId ?: current_hotel_id();

    if (!$hotelId) {
        return null;
    }

    static $cache = [];
    $cacheKey = (int) $hotelId;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    // Capa APCu (60s, compartida entre requests y usuarios) sobre el cache
    // estatico por request de arriba. Un toggle de modulo la invalida al
    // instante (ver Modulo.php); el TTL corto es la garantia de fondo.
    $consultar = static function () use ($hotelId) {
        try {
            $moduloModel = new Modulo();
            $modulos = $moduloModel->listarActivosDeHotel((int) $hotelId);
            return array_values(array_unique(array_filter(array_column($modulos, 'clave'))));
        } catch (Throwable $e) {
            error_log('Error al consultar modulos activos por hotel: ' . $e->getMessage());
            return null; // null no se cachea: el fallo no se pega 60s.
        }
    };

    $cache[$cacheKey] = function_exists('ms_cache_remember')
        ? ms_cache_remember('modulos_hotel_' . $cacheKey, 60, $consultar)
        : $consultar();

    return $cache[$cacheKey];
}

function current_hotel_has_module($clave) {
    return hotel_has_module($clave, current_hotel_id());
}

/**
 * ¿Debe mostrarse/cargarse el bloque documental de una entidad host
 * (huésped, reservación, proveedor, compra, cuenta por pagar, trabajador,
 * tarea)? Espeja EXACTAMENTE el gate del Centro Documental
 * (DocumentoController::before): módulo `documentos` contratado en el hotel
 * actual Y permiso `documentos.view`. Fail-closed si faltan los helpers.
 * Único punto de verdad para ocultar y NO cargar metadata documental fuera
 * del propio módulo cuando no está contratado o el usuario no tiene permiso.
 */
function documentos_entidad_visible(): bool {
    return function_exists('current_hotel_has_module')
        && current_hotel_has_module('documentos')
        && function_exists('can')
        && can('documentos.view');
}

/**
 * ¿Se muestra y opera el ESTACIONAMIENTO en este hotel (vehículos del huésped,
 * tarjeta de proyección del tablero, bloque de vehículos del PDF de cotización)?
 *
 * Punto ÚNICO de verdad: el bloque `vehiculos` ($99). Ya era el interruptor de
 * la mitad de la función —HuespedController 629/990/1061 y el mapa de
 * ApiController lo exigen para registrar placas— mientras la otra mitad (el
 * indicador del tablero, el POST del alta, el PDF, los chips del listado)
 * quedaba encendida para cualquier hotel: la incoherencia asimétrica que este
 * helper cierra. Con un solo predicado, contratar o cancelar el bloque prende y
 * apaga la función COMPLETA.
 *
 * Sin $hotelId responde por el hotel de la sesión con la semántica de menú
 * (fail-open si el catálogo no se pudo consultar, y `true` bajo /admin/saas
 * donde el panel muestra todo); con $hotelId explícito consulta ese hotel, que
 * es obligatorio en el panel SaaS porque `current_hotel_id()` lee solo
 * `$_SESSION` y devolvería el hotel del admin.
 *
 * Estacionamiento se GATEA, jamás se 403ea: vive dentro de pantallas del
 * paquete base (tablero, huéspedes, habitaciones, reservaciones), así que el
 * criterio es el de `descuentos` — el dato no se pinta y el POST se ignora.
 *
 * Si algún día el hotelero necesita apagarlo teniéndolo contratado (hotel sin
 * cajones), el ajuste por hotel se ANDea AQUÍ, en una línea, sin volver a
 * recorrer los ~15 consumidores.
 */
function hotel_parking_visible($hotelId = null): bool {
    if ($hotelId !== null) {
        return !function_exists('hotel_has_module')
            || hotel_has_module('vehiculos', (int) $hotelId);
    }

    return !function_exists('hotel_menu_module_enabled')
        || hotel_menu_module_enabled('vehiculos');
}

function require_hotel_module_api($clave) {
    if (!$clave || !has_hotel_context()) {
        return true;
    }

    if (current_hotel_has_module($clave)) {
        return true;
    }

    json_response([
        'success' => false,
        'message' => 'Modulo no disponible para este hotel.',
        'module' => (string) $clave
    ], 403);
}

function hotel_menu_should_filter_modules() {
    if (!has_hotel_context()) {
        return false;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    return strpos($path, '/admin/saas') !== 0;
}

function hotel_menu_module_enabled($clave) {
    if (!hotel_menu_should_filter_modules()) {
        return true;
    }

    $clavesActivas = hotel_active_module_keys(current_hotel_id());

    if ($clavesActivas === null) {
        return true;
    }

    if (empty($clavesActivas)) {
        return $clave === 'dashboard';
    }

    return in_array((string) $clave, $clavesActivas, true);
}

function hotel_menu_modules_unconfigured() {
    if (!hotel_menu_should_filter_modules()) {
        return false;
    }

    $clavesActivas = hotel_active_module_keys(current_hotel_id());
    return is_array($clavesActivas) && empty($clavesActivas);
}

/**
 * Centro de Reportes: cada pantalla analitica se contrata como modulo propio
 * (reporte_*) o viene incluida con un modulo operativo. El modulo `reportes`
 * es solo el contenedor interno: NO da acceso por si mismo.
 * Clave = pantalla (slug de la ruta /reportes/<slug>), valor = modulos que la
 * otorgan (any-of).
 */
function hotel_report_screen_modules() {
    return [
        'ingresos-gastos'          => ['reporte_ingresos_egresos'],
        'procedencia'              => ['reporte_procedencia'],
        // Ranking y comparativa de estados pertenece a Procedencia.
        'ranking-estados'          => ['reporte_procedencia'],
        'habitaciones-rentables'   => ['reporte_habitaciones_rentables'],
        'ocupacion'                => ['reporte_ocupacion'],
        'estancia'                 => ['reporte_promedio_estancia'],
        // Reportes incluidos con modulos operativos.
        'mantenimiento'            => ['mantenimiento'],
        'mantenimiento-programado' => ['mantenimiento'],
        'limpieza'                 => ['camarista', 'limpieza'],
        'ejecutivo'                => ['tablero_ejecutivo'],
        'gerencial-diario'         => ['tablero_ejecutivo'],
    ];
}

/** Una pantalla del Centro de Reportes esta permitida si el hotel tiene
 *  activo alguno de los modulos que la otorgan. Pantalla desconocida = cerrada. */
function hotel_report_screen_allowed($pantalla, $hotelId = null) {
    $mapa = hotel_report_screen_modules();

    if (!isset($mapa[$pantalla])) {
        return false;
    }

    foreach ($mapa[$pantalla] as $clave) {
        if (hotel_has_module($clave, $hotelId)) {
            return true;
        }
    }

    return false;
}

/** El Centro de Reportes aparece si existe al menos un reporte permitido. */
function hotel_reports_center_available($hotelId = null) {
    foreach (array_keys(hotel_report_screen_modules()) as $pantalla) {
        if (hotel_report_screen_allowed($pantalla, $hotelId)) {
            return true;
        }
    }

    return false;
}

/** Gate de servidor por pantalla de reporte (mismo contrato que
 *  require_hotel_module: bypass en /admin/saas, JSON en AJAX, redirect web). */
function require_hotel_report($pantalla) {
    if (!has_hotel_context()) {
        return true;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    if (strpos($path, '/admin/saas') === 0) {
        return true;
    }

    if (hotel_report_screen_allowed($pantalla, current_hotel_id())) {
        return true;
    }

    $mensaje = 'Este reporte no esta incluido en los modulos contratados por el hotel.';

    if (is_ajax()) {
        json_response([
            'success' => false,
            'message' => $mensaje,
            'report' => (string) $pantalla
        ], 403);
    }

    set_mensaje($mensaje, 'error');
    redirect(function_exists('home_route_for_current_user') ? home_route_for_current_user() : 'dashboard');
}

/** Gate del index del Centro de Reportes: al menos un reporte permitido. */
function require_hotel_reports_center() {
    if (!has_hotel_context()) {
        return true;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    if (strpos($path, '/admin/saas') === 0) {
        return true;
    }

    if (hotel_reports_center_available(current_hotel_id())) {
        return true;
    }

    $mensaje = 'El hotel no tiene reportes contratados.';

    if (is_ajax()) {
        json_response([
            'success' => false,
            'message' => $mensaje
        ], 403);
    }

    set_mensaje($mensaje, 'error');
    redirect(function_exists('home_route_for_current_user') ? home_route_for_current_user() : 'dashboard');
}

function require_hotel_module($clave) {
    if (!$clave || !has_hotel_context()) {
        return true;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    if (strpos($path, '/admin/saas') === 0) {
        return true;
    }

    if (strpos($path, '/api/') === 0) {
        return require_hotel_module_api($clave);
    }

    if (current_hotel_has_module($clave)) {
        return true;
    }

    $mensaje = 'Modulo no disponible para este hotel.';

    if (is_ajax()) {
        json_response([
            'success' => false,
            'message' => $mensaje,
            'module' => (string) $clave
        ], 403);
    }

    if ($clave === 'dashboard') {
        http_response_code(403);
        echo '<h1>Modulo no disponible</h1><p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>';
        exit;
    }

    set_mensaje($mensaje, 'error');
    redirect(function_exists('home_route_for_current_user') ? home_route_for_current_user() : 'dashboard');
}

/**
 * Gate del panel "Documentos vinculados" que otras fichas (huesped,
 * reservacion, trabajador, compra, proveedor, cuenta por pagar, tarea)
 * pintan con el partial documentos_entidad.
 *
 * Mismo contrato que el menu (views/layout/sidebar.php): el hotel debe tener
 * contratado el modulo 'documentos' y el usuario debe poder verlos. Sin esto
 * el titulo del archivo ("INE de Juan Perez") se filtraba aunque la descarga
 * si estuviera protegida por DocumentoController.
 */
function puede_ver_documentos_vinculados() {
    // Un solo punto de verdad (integracion 26 jul 2026): las dos ramas llegaron
    // con su propio helper para el MISMO gate. Se conserva este nombre porque lo
    // usan los 7 controllers host, pero delega en documentos_entidad_visible(),
    // que es fail-CLOSED (exige modulo contratado + permiso, y niega si falta
    // cualquiera de los dos helpers) en vez de fail-open. Para metadata con PII
    // la degradacion correcta es ocultar, no mostrar.
    return documentos_entidad_visible();
}

/**
 * Vincular un archivo es una escritura del centro documental: mismo contrato
 * que editar/archivar/eliminar en DocumentoController ('documentos.all').
 */
function puede_vincular_documentos() {
    if (!puede_ver_documentos_vinculados()) {
        return false;
    }

    return !function_exists('can') || can('documentos.all');
}
