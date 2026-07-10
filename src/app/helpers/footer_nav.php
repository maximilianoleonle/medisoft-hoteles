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
                'label' => 'Dashboard',
                'short' => 'Inicio',
                'icon' => 'fa-th-large',
                'path' => 'dashboard',
                'modules_any' => ['dashboard'],
                'descripcion' => 'Resumen general del hotel.',
            ],
            'operacion_diaria' => [
                'label' => 'Operación diaria',
                'short' => 'Operación',
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
            'cuentas_por_cobrar' => [
                'label' => 'Cuentas por cobrar',
                'short' => 'Por cobrar',
                'icon' => 'fa-hand-holding-dollar',
                'path' => 'cuentas-por-cobrar',
                'modules_any' => ['reservaciones', 'facturacion'],
                'descripcion' => 'Saldos pendientes de huéspedes y clientes.',
            ],
            'facturacion' => [
                'label' => 'Facturación',
                'short' => 'Facturas',
                'icon' => 'fa-file-invoice',
                'path' => 'facturacion',
                'modules_any' => ['facturacion'],
                'descripcion' => 'Solicitudes y emisión de facturas.',
            ],
            'tareas' => [
                'label' => 'Tareas',
                'short' => 'Tareas',
                'icon' => 'fa-tasks',
                'path' => 'tareas',
                'modules_any' => ['habitaciones', 'limpieza', 'mantenimiento'],
                'descripcion' => 'Limpieza, mantenimiento y pendientes.',
            ],
            'inventario' => [
                'label' => 'Inventarios',
                'short' => 'Inventario',
                'icon' => 'fa-box',
                'path' => 'inventario',
                'modules_any' => ['inventario'],
                'descripcion' => 'Existencias y movimientos de almacén.',
            ],
            'compras' => [
                'label' => 'Compras',
                'short' => 'Compras',
                'icon' => 'fa-clipboard-list',
                'path' => 'compras',
                'modules_any' => ['inventario'],
                'descripcion' => 'Órdenes y registro de compras.',
            ],
            'documentos' => [
                'label' => 'Documentos',
                'short' => 'Docs',
                'icon' => 'fa-folder-open',
                'path' => 'documentos',
                'modules_any' => ['inventario', 'huespedes', 'reservaciones'],
                'descripcion' => 'Archivos y documentos del hotel.',
            ],
            'reportes' => [
                'label' => 'Reportes',
                'short' => 'Reportes',
                'icon' => 'fa-chart-line',
                'path' => 'reportes',
                'modules_any' => ['reportes'],
                'descripcion' => 'Indicadores e informes del hotel.',
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
    function hotel_footer_nav_item_available(array $item)
    {
        $modules = $item['modules_any'] ?? [];

        if (empty($modules)) {
            return true;
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
    function hotel_footer_nav_available_catalog()
    {
        $available = [];

        foreach (hotel_footer_nav_catalog() as $key => $item) {
            if (hotel_footer_nav_item_available($item)) {
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
            if (!isset($catalog[$key]) || !hotel_footer_nav_item_available($catalog[$key])) {
                continue;
            }

            $items[$key] = $catalog[$key];
        }

        // Si la configuración quedó vacía tras filtrar módulos, rellenar con
        // los predeterminados disponibles para no dejar la barra inútil.
        if (empty($items)) {
            foreach (hotel_footer_nav_default_keys() as $key) {
                if (isset($catalog[$key]) && hotel_footer_nav_item_available($catalog[$key])) {
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
