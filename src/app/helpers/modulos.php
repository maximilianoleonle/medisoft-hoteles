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
    if (function_exists('hotel_menu_module_enabled') && !hotel_menu_module_enabled('documentos')) {
        return false;
    }

    return !function_exists('can') || can('documentos.view');
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
