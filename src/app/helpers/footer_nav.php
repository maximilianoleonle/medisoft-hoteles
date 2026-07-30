<?php
/**
 * Barra inferior de accesos rápidos (PWA móvil).
 *
 * Cada hotel elige hasta hotel_footer_nav_max() atajos desde Configuración.
 * Se guarda como JSON en hotel_configuracion (clave pwa.footer_shortcuts)
 * y al renderizar se filtra por módulos activos del hotel.
 */

if (!function_exists('hotel_footer_nav_max')) {
    function hotel_footer_nav_max()
    {
        return 4;
    }
}

if (!function_exists('hotel_footer_nav_min')) {
    function hotel_footer_nav_min()
    {
        return 2;
    }
}

if (!function_exists('hotel_footer_nav_catalog')) {
    function hotel_footer_nav_catalog()
    {
        // 'modules_any' vacío = disponible siempre que haya contexto de hotel.
        return [
            'dashboard' => [
                'label' => 'Inicio',
                'short' => 'Inicio',
                'icon' => 'fa-compass',
                'path' => 'dashboard',
                'modules_any' => ['dashboard'],
                'descripcion' => 'Resumen general del hotel.',
            ],
            'operacion_diaria' => [
                'label' => 'El hotel hoy',
                'short' => 'Hoy',
                'icon' => 'fa-clipboard-check',
                'path' => 'operacion/diaria',
                'modules_any' => ['dashboard'],
                'descripcion' => 'Llegadas, salidas y pendientes del día.',
            ],
            'habitaciones' => [
                'label' => 'Habitaciones',
                'short' => 'Habitaciones',
                'icon' => 'fa-bed',
                'path' => 'habitaciones',
                'modules_any' => ['habitaciones'],
                'descripcion' => 'Estado y gestión de habitaciones.',
            ],
            'reservaciones' => [
                'label' => 'Reservaciones',
                'short' => 'Reservas',
                'icon' => 'fa-calendar-check',
                'path' => 'reservaciones',
                'modules_any' => ['reservaciones'],
                'descripcion' => 'Listado y captura de reservaciones.',
            ],
            'calendario' => [
                'label' => 'Calendario de reservas',
                'short' => 'Calendario',
                'icon' => 'fa-calendar-days',
                'path' => 'reservaciones/calendario',
                'modules_any' => ['reservaciones'],
                'descripcion' => 'Ocupación por fechas en vista calendario.',
            ],
            'huespedes' => [
                'label' => 'Huéspedes',
                'short' => 'Huéspedes',
                'icon' => 'fa-users',
                'path' => 'huespedes',
                'modules_any' => ['huespedes'],
                'descripcion' => 'Expedientes de huéspedes.',
            ],
            'caja' => [
                'label' => 'Caja',
                'short' => 'Caja',
                'icon' => 'fa-wallet',
                'path' => 'caja',
                'modules_any' => ['caja'],
                'descripcion' => 'Ingresos, gastos y cortes de caja.',
            ],
            'facturacion' => [
                'label' => 'Facturación',
                'short' => 'Facturas',
                'icon' => 'fa-file-invoice',
                'path' => 'facturacion',
                'modules_any' => ['facturacion'],
                'descripcion' => 'Solicitudes y emisión de facturas.',
            ],
            'inventario' => [
                'label' => 'Inventarios',
                'short' => 'Inventario',
                'icon' => 'fa-boxes-stacked',
                'path' => 'inventario',
                'modules_any' => ['inventario'],
                'descripcion' => 'Existencias y movimientos de almacén.',
            ],
            'compras' => [
                'label' => 'Compras',
                'short' => 'Compras',
                'icon' => 'fa-clipboard-list',
                'path' => 'compras',
                // Compras es bloque vendible propio ($199): el atajo debe seguir
                // SU contratación, no la de Inventario (CompraController exige
                // require_hotel_module('compras') y el proxy mandaba a un 403).
                'modules_any' => ['compras'],
                'descripcion' => 'Órdenes y registro de compras.',
            ],
            'documentos' => [
                'label' => 'Documentos',
                'short' => 'Docs',
                'icon' => 'fa-folder-open',
                'path' => 'documentos',
                // huespedes/reservaciones son paquete base: con ese proxy el
                // atajo aparecía para TODOS los hoteles aunque no contrataran
                // el bloque, y DocumentoController::before() los rebotaba.
                'modules_any' => ['documentos'],
                'descripcion' => 'Archivos y documentos del hotel.',
            ],
            'reportes' => [
                'label' => 'Reportes',
                'short' => 'Reportes',
                'icon' => 'fa-chart-line',
                'path' => 'reportes',
                'modules_any' => ['reporte_ingresos_egresos', 'reporte_procedencia', 'reporte_habitaciones_rentables', 'reporte_ocupacion', 'reporte_promedio_estancia', 'mantenimiento', 'camarista', 'limpieza', 'tablero_ejecutivo'],
                'descripcion' => 'Indicadores e informes del hotel.',
            ],
            'mensajes' => [
                'label' => 'Mensajes a huéspedes',
                'short' => 'Mensajes',
                'icon' => 'fa-comment-dots',
                'path' => 'mensajes',
                'modules_any' => ['canal_whatsapp'],
                'descripcion' => 'WhatsApp del día listo para enviar.',
            ],
            // 'notificaciones' ya no se ofrece aquí: la campana vive fija en el
            // header móvil, así el hotel no gasta un atajo del footer en avisos.
        ];
    }
}

if (!function_exists('hotel_footer_nav_default_keys')) {
    function hotel_footer_nav_default_keys()
    {
        return ['dashboard', 'reservaciones', 'habitaciones', 'caja'];
    }
}

if (!function_exists('hotel_footer_nav_item_available')) {
    /**
     * ¿Está disponible este atajo para el hotel indicado?
     *
     * Con `$hotelId` explícito consulta los módulos de ESE hotel. Es obligatorio
     * desde el panel SaaS: `hotel_menu_module_enabled()` devuelve true para
     * cualquier ruta bajo /admin/saas (por diseño del panel) y además resuelve
     * por `current_hotel_id()`, que lee solo `$_SESSION` ⇒ el panel ofrecía —y
     * GUARDABA, porque `hotel_footer_nav_sanitize_keys` valida contra el catálogo
     * COMPLETO— atajos a pantallas que el hotel cliente rebota con 403.
     *
     * Sin `$hotelId` conserva la semántica de menú de siempre (el runtime del
     * hotel y la barra real no cambian).
     */
    function hotel_footer_nav_item_available(array $item, $hotelId = null)
    {
        $modules = $item['modules_any'] ?? [];

        if (empty($modules)) {
            return true;
        }

        $hotelId = (int) $hotelId;

        if ($hotelId > 0 && function_exists('hotel_active_module_keys')) {
            // Espejo EXACTO de hotel_menu_module_enabled, cambiando solo el hotel
            // al que se le pregunta: catálogo ilegible (null) ⇒ no se esconde
            // nada, y catálogo vacío ⇒ sobrevive dashboard. Con hotel_has_module
            // a secas el filtro seria fail-CLOSED y en un despliegue fresco —donde
            // la tabla `modulos` nace vacia— el panel se quedaria sin un solo
            // atajo que ofrecer.
            $clavesActivas = hotel_active_module_keys($hotelId);

            if ($clavesActivas === null) {
                return true;
            }

            if (empty($clavesActivas)) {
                return in_array('dashboard', $modules, true);
            }

            foreach ($modules as $module) {
                if (in_array((string) $module, $clavesActivas, true)) {
                    return true;
                }
            }

            return false;
        }

        if (!function_exists('hotel_menu_module_enabled')) {
            return true;
        }

        foreach ($modules as $module) {
            if (hotel_menu_module_enabled($module)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('hotel_footer_nav_available_catalog')) {
    function hotel_footer_nav_available_catalog($hotelId = null)
    {
        $available = [];

        foreach (hotel_footer_nav_catalog() as $key => $item) {
            if (hotel_footer_nav_item_available($item, $hotelId)) {
                $available[$key] = $item;
            }
        }

        return $available;
    }
}

if (!function_exists('hotel_footer_nav_sanitize_keys')) {
    function hotel_footer_nav_sanitize_keys($keys)
    {
        if (!is_array($keys)) {
            return [];
        }

        $catalog = hotel_footer_nav_catalog();
        $clean = [];

        foreach ($keys as $key) {
            $key = (string) $key;

            if (isset($catalog[$key]) && !in_array($key, $clean, true)) {
                $clean[] = $key;
            }

            if (count($clean) >= hotel_footer_nav_max()) {
                break;
            }
        }

        return $clean;
    }
}

if (!function_exists('hotel_footer_nav_saved_keys')) {
    function hotel_footer_nav_saved_keys($hotelId = null)
    {
        if (!function_exists('hotel_config_get')) {
            return [];
        }

        $stored = hotel_config_get('pwa.footer_shortcuts', [], $hotelId);

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            $stored = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($stored)) {
            return [];
        }

        return hotel_footer_nav_sanitize_keys($stored['items'] ?? []);
    }
}

if (!function_exists('hotel_footer_nav_items')) {
    /**
     * Atajos listos para render: elegidos por el hotel (o los predeterminados),
     * ya filtrados por módulos activos.
     */
    function hotel_footer_nav_items($hotelId = null)
    {
        $catalog = hotel_footer_nav_catalog();
        $keys = hotel_footer_nav_saved_keys($hotelId);

        if (empty($keys)) {
            $keys = hotel_footer_nav_default_keys();
        }

        $items = [];

        foreach ($keys as $key) {
            if (!isset($catalog[$key]) || !hotel_footer_nav_item_available($catalog[$key], $hotelId)) {
                continue;
            }

            $items[$key] = $catalog[$key];
        }

        // Si tras filtrar módulos la barra quedó por DEBAJO del mínimo, rellenar
        // con los predeterminados disponibles (los 4 son paquete base, así que
        // siempre hay de dónde). Antes solo rellenaba si quedaba VACÍA, y ese
        // hueco importa desde que el panel SaaS filtra de verdad: una barra que
        // baja a 1 atajo hace que hotel_footer_nav_normalize_payload aborte TODO
        // el guardado de /configuracion con "necesita al menos N atajos".
        if (count($items) < hotel_footer_nav_min()) {
            foreach (hotel_footer_nav_default_keys() as $key) {
                if (isset($catalog[$key]) && hotel_footer_nav_item_available($catalog[$key], $hotelId)) {
                    $items[$key] = $catalog[$key];
                }

                if (count($items) >= hotel_footer_nav_max()) {
                    break;
                }
            }
        }

        return array_slice($items, 0, hotel_footer_nav_max(), true);
    }
}

if (!function_exists('hotel_footer_nav_normalize_payload')) {
    /**
     * @return array{values: array, errors: array}
     */
    function hotel_footer_nav_normalize_payload($payload)
    {
        $values = hotel_footer_nav_sanitize_keys(is_array($payload) ? $payload : []);
        $errors = [];

        if (count($values) < hotel_footer_nav_min()) {
            $errors[] = 'La barra de accesos rápidos necesita al menos ' . hotel_footer_nav_min() . ' atajos seleccionados.';
        }

        return [
            'values' => $values,
            'errors' => $errors,
        ];
    }
}

if (!function_exists('hotel_footer_nav_save')) {
    function hotel_footer_nav_save(array $keys, $hotelId = null)
    {
        return hotel_config_save_value(
            'pwa.footer_shortcuts',
            [
                'version' => 1,
                'items' => hotel_footer_nav_sanitize_keys($keys),
            ],
            'json',
            'pwa',
            'Atajos de la barra inferior de la app móvil.',
            $hotelId
        );
    }
}
