<?php
/**
 * Navegacion rapida: vistas recientes/frecuentes y favoritos por usuario+hotel.
 *
 * Todo el estado vive en usuario_preferencias_nav, SIEMPRE filtrado por
 * (hotel_id, usuario_id). La visibilidad de cada pantalla se revalida contra
 * modulos activos y permisos en el momento de leer: si un modulo se desactiva
 * o el rol pierde el permiso, el acceso desaparece sin romper nada.
 *
 * Diseno defensivo: ninguna funcion de este helper debe romper la pagina.
 * Cualquier error queda en ms_log y se devuelve un resultado vacio.
 */

/**
 * Catalogo completo de pantallas (config/navegacion.php), cacheado por request.
 */
function nav_pantallas_catalogo() {
    static $catalogo = null;

    if ($catalogo === null) {
        $archivo = CONFIG_PATH . '/navegacion.php';
        $catalogo = is_readable($archivo) ? (require $archivo) : [];

        if (!is_array($catalogo)) {
            $catalogo = [];
        }
    }

    return $catalogo;
}

/**
 * Normalizar una ruta a la forma del catalogo: sin slash inicial/final,
 * sin query string. 'dashboard', 'caja/movimientos', etc.
 */
function nav_normalizar_ruta($ruta) {
    $ruta = parse_url((string) $ruta, PHP_URL_PATH) ?: (string) $ruta;
    return trim($ruta, '/');
}

/**
 * Visibilidad de una pantalla para el usuario/hotel actual.
 * Replica la logica del sidebar: modulo activo Y permiso.
 *
 * El gate por rol-string se retiro el 23 jul 2026 (auditoria de accesos): no
 * distinguia los roles personalizados y ademas abria el menu a pantallas que
 * el servidor luego rechazaba. Ver la cabecera de config/navegacion.php.
 */
function nav_pantalla_visible(array $pantalla) {
    // Requisito de modulo (string o lista en OR)
    $modulos = $pantalla['modulo'] ?? null;

    if ($modulos !== null) {
        $modulos = is_array($modulos) ? $modulos : [$modulos];
        $moduloOk = false;

        foreach ($modulos as $clave) {
            if (hotel_menu_module_enabled($clave)) {
                $moduloOk = true;
                break;
            }
        }

        if (!$moduloOk) {
            return false;
        }
    }

    $permiso = $pantalla['permiso'] ?? null;

    // Sin permiso declarado: basta el requisito de modulo.
    if ($permiso === null) {
        return true;
    }

    if (!function_exists('can')) {
        return false;
    }

    // 'permiso' puede ser un string o una lista any-of (basta cumplir uno),
    // p. ej. Limpieza: ['camarista.view', 'tareas.view'].
    foreach ((is_array($permiso) ? $permiso : [$permiso]) as $permisoUno) {
        if (can($permisoUno)) {
            return true;
        }
    }

    return false;
}

/**
 * Pantallas visibles para el usuario actual (para busqueda y accesos).
 */
function nav_pantallas_visibles() {
    static $visibles = null;

    if ($visibles !== null) {
        return $visibles;
    }

    $visibles = [];

    if (!function_exists('has_hotel_context') || !has_hotel_context()) {
        return $visibles;
    }

    try {
        foreach (nav_pantallas_catalogo() as $pantalla) {
            if (nav_pantalla_visible($pantalla)) {
                $visibles[] = $pantalla;
            }
        }
    } catch (Throwable $e) {
        ms_log('warning', 'nav_pantallas_visibles fallo: ' . $e->getMessage());
        $visibles = [];
    }

    return $visibles;
}

/**
 * Resolver la pantalla del catalogo que corresponde a una ruta visitada.
 * Match exacto primero; si no, el prefijo mas largo del catalogo
 * (ej. reservaciones/ver/12 -> reservaciones).
 */
function nav_pantalla_por_ruta($ruta) {
    $ruta = nav_normalizar_ruta($ruta);

    if ($ruta === '') {
        return null;
    }

    $mejor = null;
    $mejorLen = 0;

    foreach (nav_pantallas_catalogo() as $pantalla) {
        $candidata = $pantalla['ruta'];

        if ($ruta === $candidata) {
            return $pantalla;
        }

        $len = strlen($candidata);
        if ($len > $mejorLen && strpos($ruta, $candidata . '/') === 0) {
            $mejor = $pantalla;
            $mejorLen = $len;
        }
    }

    return $mejor;
}

/**
 * ¿La peticion actual es especulativa (prefetch/prerender del navegador)?
 * Chrome/Edge marcan las peticiones de Speculation Rules y <link rel=prefetch>
 * con "Sec-Purpose: prefetch" (o "prefetch;prerender"); versiones previas y
 * otros navegadores usan Purpose/X-Purpose/X-moz. El usuario AUN NO visito la
 * pagina: no deben ejecutarse efectos secundarios (registrar visita, marcar
 * notificaciones leidas, etc.). Ver instant-nav.js (quien origina la precarga).
 */
function is_speculative_request(): bool {
    $marcas = strtolower(implode(' ', [
        $_SERVER['HTTP_SEC_PURPOSE'] ?? '',
        $_SERVER['HTTP_PURPOSE'] ?? '',
        $_SERVER['HTTP_X_PURPOSE'] ?? '',
        $_SERVER['HTTP_X_MOZ'] ?? '',
    ]));

    return strpos($marcas, 'prefetch') !== false || strpos($marcas, 'prerender') !== false;
}

/** ¿La peticion es especificamente un prerender (pagina completa anticipada)? */
function is_prerender_request(): bool {
    return strpos(strtolower($_SERVER['HTTP_SEC_PURPOSE'] ?? ''), 'prerender') !== false;
}

/**
 * Registrar la visita a la vista actual como 'reciente' (fire-and-forget).
 * Se invoca desde View::renderTemplate en cada pagina principal GET.
 * Un solo upsert por vista; nunca rompe la pagina.
 */
function nav_registrar_visita() {
    try {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
            return;
        }

        // Prefetch (hover/touch): el usuario aun no visito la pantalla — no
        // contaminar 'recientes' ni ultima_visita de las burbujas de novedad.
        // El prerender SI se registra: nace de intencion fuerte y casi siempre
        // termina en visita real; ademas, al activarse un prerender NO hay una
        // segunda peticion al servidor, asi que saltarlo perderia la visita.
        if (is_speculative_request() && !is_prerender_request()) {
            return;
        }

        if (!function_exists('has_hotel_context') || !has_hotel_context()) {
            return;
        }

        $hotelId = (int) current_hotel_id();
        $usuarioId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$hotelId || !$usuarioId) {
            return;
        }

        $rutaActual = $_SERVER['REQUEST_URI'] ?? '';

        if (strpos($rutaActual, '/admin/saas') !== false) {
            return; // El panel SaaS no participa en accesos del hotel
        }

        $pantalla = nav_pantalla_por_ruta($rutaActual);

        if (!$pantalla) {
            return; // Solo se registran pantallas del catalogo
        }

        $db = Database::getInstance();
        $db->query(
            "INSERT INTO usuario_preferencias_nav
                (hotel_id, usuario_id, tipo, ruta, contador, ultima_visita, created_at, updated_at)
             VALUES (?, ?, 'reciente', ?, 1, NOW(), NOW(), NOW())
             ON DUPLICATE KEY UPDATE contador = contador + 1, ultima_visita = NOW()",
            [$hotelId, $usuarioId, $pantalla['ruta']]
        );

        // Poda ocasional (~4% de las visitas): conservar solo los 20 recientes
        // mas frescos por usuario+hotel para que la tabla no crezca sin limite.
        if (mt_rand(1, 25) === 1) {
            $db->query(
                "DELETE FROM usuario_preferencias_nav
                 WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'reciente'
                   AND id NOT IN (
                       SELECT id FROM (
                           SELECT id FROM usuario_preferencias_nav
                           WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'reciente'
                           ORDER BY ultima_visita DESC LIMIT 20
                       ) t
                   )",
                [$hotelId, $usuarioId, $hotelId, $usuarioId]
            );
        }
    } catch (Throwable $e) {
        ms_log('warning', 'nav_registrar_visita fallo: ' . $e->getMessage());
    }
}

/**
 * Rutas favoritas del usuario actual en el hotel actual.
 */
function nav_favoritos_rutas() {
    try {
        $hotelId = (int) current_hotel_id();
        $usuarioId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$hotelId || !$usuarioId) {
            return [];
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT ruta FROM usuario_preferencias_nav
             WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'favorito'
             ORDER BY updated_at ASC",
            [$hotelId, $usuarioId]
        );

        return $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (Throwable $e) {
        ms_log('warning', 'nav_favoritos_rutas fallo: ' . $e->getMessage());
        return [];
    }
}

/**
 * Accesos rapidos para el sidebar: favoritos primero, luego las pantallas
 * mas frecuentes/recientes. Deduplicado, revalidado y limitado a $max.
 * Sin historial todavia: defaults por rol para que la seccion nunca este vacia.
 */
function nav_accesos_rapidos($max = 6) {
    try {
        if (!function_exists('has_hotel_context') || !has_hotel_context()) {
            return [];
        }

        $hotelId = (int) current_hotel_id();
        $usuarioId = (int) ($_SESSION['user_id'] ?? 0);

        if (!$hotelId || !$usuarioId) {
            return [];
        }

        $favoritas = nav_favoritos_rutas();

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT ruta FROM usuario_preferencias_nav
             WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'reciente'
             ORDER BY contador DESC, ultima_visita DESC
             LIMIT 12",
            [$hotelId, $usuarioId]
        );
        $frecuentes = $stmt !== false ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        // Defaults por rol cuando el usuario aun no tiene historial
        if (empty($favoritas) && empty($frecuentes)) {
            $rol = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
            $frecuentes = in_array($rol, ['gerente', 'administrador'], true)
                ? ['dashboard', 'reservaciones', 'caja', 'reportes', 'habitaciones', 'configuracion']
                : ['reservaciones', 'habitaciones', 'caja', 'huespedes', 'dashboard'];
        }

        $accesos = [];
        $vistas = [];

        foreach (array_merge($favoritas, $frecuentes) as $ruta) {
            if (isset($vistas[$ruta]) || count($accesos) >= $max) {
                continue;
            }

            $pantalla = nav_pantalla_por_ruta($ruta);

            if (!$pantalla || $pantalla['ruta'] !== $ruta) {
                continue; // Ruta que ya no existe en el catalogo
            }

            if (!nav_pantalla_visible($pantalla)) {
                continue; // Modulo desactivado o permiso perdido: se oculta
            }

            $pantalla['es_favorito'] = in_array($ruta, $favoritas, true);
            $accesos[] = $pantalla;
            $vistas[$ruta] = true;
        }

        return $accesos;
    } catch (Throwable $e) {
        ms_log('warning', 'nav_accesos_rapidos fallo: ' . $e->getMessage());
        return [];
    }
}

/**
 * Alternar favorito para el usuario/hotel actual. Devuelve el estado final
 * ('favorito' => true/false) o null si la ruta no es valida/visible.
 */
function nav_toggle_favorito($ruta) {
    $ruta = nav_normalizar_ruta($ruta);
    $pantalla = nav_pantalla_por_ruta($ruta);

    // Solo rutas exactas del catalogo, y solo si el usuario puede verlas
    if (!$pantalla || $pantalla['ruta'] !== $ruta || !nav_pantalla_visible($pantalla)) {
        return null;
    }

    $hotelId = (int) current_hotel_id();
    $usuarioId = (int) ($_SESSION['user_id'] ?? 0);

    if (!$hotelId || !$usuarioId) {
        return null;
    }

    $db = Database::getInstance();
    $stmt = $db->query(
        "SELECT id FROM usuario_preferencias_nav
         WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'favorito' AND ruta = ?",
        [$hotelId, $usuarioId, $ruta]
    );
    $existente = $stmt !== false ? $stmt->fetch() : null;

    if ($existente) {
        $db->query(
            "DELETE FROM usuario_preferencias_nav WHERE id = ? AND hotel_id = ? AND usuario_id = ?",
            [(int) $existente['id'], $hotelId, $usuarioId]
        );
        return ['favorito' => false];
    }

    $db->query(
        "INSERT INTO usuario_preferencias_nav
            (hotel_id, usuario_id, tipo, ruta, contador, ultima_visita, created_at, updated_at)
         VALUES (?, ?, 'favorito', ?, 0, NOW(), NOW(), NOW())
         ON DUPLICATE KEY UPDATE updated_at = NOW()",
        [$hotelId, $usuarioId, $ruta]
    );

    return ['favorito' => true];
}
