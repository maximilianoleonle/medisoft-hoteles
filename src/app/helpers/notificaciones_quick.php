<?php
/**
 * Pendientes de notificaciones para la campana del shell (sidebar).
 *
 * El dashboard arma su campana con datos que le pasa DashboardController; la
 * sidebar vive en TODAS las pantallas, asi que necesita su propia fuente. Este
 * helper devuelve lo mismo que usa el dashboard:
 *
 *   ['pendientes' => int, 'recientes' => array]  (notificaciones en estado 'nueva')
 *
 * Mismo diseno defensivo que sidebar_novedades(): si la tabla no existe o la
 * consulta falla, devuelve vacio y la campana simplemente no muestra burbuja.
 * Nunca rompe el layout. El resultado se cachea ~60s en sesion (por
 * hotel+usuario) para no consultar en cada navegacion.
 */

if (!function_exists('notificaciones_quick')) {

    function notificaciones_quick(int $limite = 8): array
    {
        static $memo = null;

        if ($memo !== null) {
            return $memo;
        }

        $memo = ['pendientes' => 0, 'recientes' => []];

        if (function_exists('has_hotel_context') && !has_hotel_context()) {
            return $memo;
        }

        if (!function_exists('current_hotel_id')) {
            return $memo;
        }

        $hotelId = (int) current_hotel_id();
        if ($hotelId <= 0) {
            return $memo;
        }

        $usuarioId = function_exists('user_id') ? (int) user_id() : (int) ($_SESSION['user_id'] ?? 0);

        // Cache corto en sesion, igual que las burbujas del menu.
        $cacheKey = '__notificaciones_quick_' . $hotelId . '_' . $usuarioId;
        $ttl = 60;
        if (
            isset($_SESSION[$cacheKey]['at'], $_SESSION[$cacheKey]['data'])
            && is_array($_SESSION[$cacheKey]['data'])
            && (time() - (int) $_SESSION[$cacheKey]['at']) < $ttl
        ) {
            return $memo = $_SESSION[$cacheKey]['data'];
        }

        try {
            require_once APP_PATH . '/models/Notificacion.php';

            $modelo = new Notificacion();
            if (!$modelo->tablaDisponible()) {
                return $memo;
            }

            $rol = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
            $resumen = $modelo->resumenPorHotel($hotelId, $rol, $usuarioId ?: null);

            $data = [
                // 'nuevas' es el mismo criterio de la burbuja del menu y del
                // contador del dashboard: sin leer todavia.
                'pendientes' => (int) ($resumen['nuevas'] ?? 0),
                'recientes' => $modelo->listarPorHotel($hotelId, [
                    'estado' => 'nueva',
                    'rol_usuario' => $rol,
                    'usuario_id' => $usuarioId ?: null,
                ], max(1, $limite)),
            ];
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('warning', 'notificaciones_quick fallo: ' . $e->getMessage());
            }
            // Sin cachear el fallo: el siguiente request lo vuelve a intentar.
            return $memo;
        }

        $_SESSION[$cacheKey] = ['at' => time(), 'data' => $data];

        return $memo = $data;
    }

    /**
     * Icono por modulo. Espejo de dashboard_notif_icon(), que vive dentro de la
     * vista del dashboard y no esta disponible en el resto de pantallas.
     */
    function notificaciones_quick_icono($modulo): string
    {
        $iconos = [
            'caja' => 'fa-wallet',
            'habitaciones' => 'fa-bed',
            'facturacion' => 'fa-file-invoice',
            'inventario' => 'fa-boxes-stacked',
            'reservaciones' => 'fa-calendar-check',
        ];

        return $iconos[(string) ($modulo ?? '')] ?? 'fa-bell';
    }

    /**
     * Etiqueta legible del modulo. Espejo de dashboard_notif_label().
     */
    function notificaciones_quick_etiqueta($modulo): string
    {
        $etiquetas = [
            'caja' => 'Caja',
            'habitaciones' => 'Habitaciones',
            'facturacion' => 'Facturación',
            'inventario' => 'Inventario',
            'reservaciones' => 'Reservaciones',
            'sistema' => 'Sistema',
        ];

        $clave = (string) ($modulo ?? '');
        if ($clave === '') {
            return 'Sistema';
        }

        return $etiquetas[$clave] ?? ucfirst(str_replace('_', ' ', $clave));
    }

    /**
     * Fecha corta 'd/m H:i' a prueba de valores nulos o basura.
     */
    function notificaciones_quick_fecha($valor): string
    {
        $valor = trim((string) ($valor ?? ''));
        if ($valor === '') {
            return '';
        }

        $ts = strtotime($valor);

        return $ts === false ? '' : date('d/m H:i', $ts);
    }
}
