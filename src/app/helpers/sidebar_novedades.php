<?php
/**
 * Novedades del sidebar: burbujas por seccion del menu.
 *
 * Devuelve un mapa  ruta => ['count' => int]  o  ruta => ['dot' => true].
 *   - 'count': pendientes reales (numero) para secciones de accion
 *              (reservaciones por atender, tareas activas, CxP, etc).
 *   - 'dot'  : hay algo nuevo desde la ultima visita (punto) para secciones
 *              informativas (motor de reservas, reputacion, huespedes...).
 *
 * La "ultima visita por seccion" sale de usuario_preferencias_nav (la misma
 * tabla que alimenta 'vistas recientes' via nav_registrar_visita), asi que el
 * punto se limpia solo cuando el usuario entra a esa seccion.
 *
 * Diseno defensivo: cada seccion va aislada; si su tabla no existe o su query
 * falla, simplemente no muestra burbuja. Nunca rompe el sidebar. El resultado
 * se cachea ~60s en sesion (por hotel+usuario) para no recomputar en cada
 * navegacion.
 */

if (!function_exists('sidebar_novedades')) {

    function sidebar_novedades(): array
    {
        static $memo = null;

        if ($memo !== null) {
            return $memo;
        }

        $memo = [];

        if (function_exists('has_hotel_context') && !has_hotel_context()) {
            return $memo;
        }

        if (!class_exists('Database') || !function_exists('current_hotel_id')) {
            return $memo;
        }

        $hotelId = (int) current_hotel_id();
        if ($hotelId <= 0) {
            return $memo;
        }

        $usuarioId = function_exists('user_id') ? (int) user_id() : (int) ($_SESSION['user_id'] ?? 0);

        // Cache corto en sesion para no recomputar en cada pagina.
        $cacheKey = '__sidebar_novedades_' . $hotelId . '_' . $usuarioId;
        $ttl = 60;
        if (
            isset($_SESSION[$cacheKey]['at'], $_SESSION[$cacheKey]['map'])
            && is_array($_SESSION[$cacheKey]['map'])
            && (time() - (int) $_SESSION[$cacheKey]['at']) < $ttl
        ) {
            return $memo = $_SESSION[$cacheKey]['map'];
        }

        try {
            $map = _sidebar_novedades_calcular(Database::getInstance(), $hotelId, $usuarioId);
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('warning', 'sidebar_novedades fallo: ' . $e->getMessage());
            }
            $map = [];
        }

        $_SESSION[$cacheKey] = ['at' => time(), 'map' => $map];

        return $memo = $map;
    }

    /**
     * Existencia de tabla cacheada por request (misma idea que OperacionDiaria).
     */
    function _sidebar_novedades_tabla_existe($db, string $tabla): bool
    {
        static $cache = [];

        if (array_key_exists($tabla, $cache)) {
            return $cache[$tabla];
        }

        try {
            $stmt = $db->query(
                "SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
                [$tabla]
            );
            return $cache[$tabla] = (bool) ($stmt && $stmt->fetch());
        } catch (Throwable $e) {
            return $cache[$tabla] = false;
        }
    }

    /**
     * Ultima visita por ruta del catalogo (tipo 'reciente'), del usuario+hotel.
     */
    function _sidebar_novedades_ultima_visita($db, int $hotelId, int $usuarioId): array
    {
        $out = [];

        if ($usuarioId <= 0 || !_sidebar_novedades_tabla_existe($db, 'usuario_preferencias_nav')) {
            return $out;
        }

        try {
            $stmt = $db->query(
                "SELECT ruta, ultima_visita
                 FROM usuario_preferencias_nav
                 WHERE hotel_id = ? AND usuario_id = ? AND tipo = 'reciente'",
                [$hotelId, $usuarioId]
            );

            foreach (($stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : []) as $row) {
                $out[(string) $row['ruta']] = (string) $row['ultima_visita'];
            }
        } catch (Throwable $e) {
            // Sin historial: todo se resuelve con el fallback de 14 dias.
        }

        return $out;
    }

    function _sidebar_novedades_calcular($db, int $hotelId, int $usuarioId): array
    {
        $map = [];
        $ultimaVisita = _sidebar_novedades_ultima_visita($db, $hotelId, $usuarioId);

        // ── Conteo de pendientes (numero) ────────────────────────────────
        $count = static function (string $ruta, string $tabla, string $where, array $params, string $tono = 'count')
            use ($db, $hotelId, &$map): void {
            if (!_sidebar_novedades_tabla_existe($db, $tabla)) {
                return;
            }
            try {
                $stmt = $db->query(
                    "SELECT COUNT(*) AS n FROM {$tabla} WHERE hotel_id = ? AND ({$where})",
                    array_merge([$hotelId], $params)
                );
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                $n = (int) ($row['n'] ?? 0);
                if ($n > 0) {
                    $map[$ruta] = ['count' => $n, 'tono' => $tono];
                }
            } catch (Throwable $e) {
                // La seccion simplemente no muestra burbuja.
            }
        };

        // ── Punto de novedad: algo nuevo desde la ultima visita ──────────
        // Si nunca visito la seccion, se considera "nuevo" lo de los ultimos
        // 14 dias para no marcar un punto permanente por datos antiguos.
        $dot = static function (string $ruta, string $tabla, string $fechaCol, string $extraWhere, array $params)
            use ($db, $hotelId, $ultimaVisita, &$map): void {
            if (!_sidebar_novedades_tabla_existe($db, $tabla)) {
                return;
            }
            $visto = $ultimaVisita[$ruta] ?? null;
            try {
                $sql = "SELECT (MAX({$fechaCol}) > COALESCE(?, NOW() - INTERVAL 14 DAY)) AS nuevo
                        FROM {$tabla}
                        WHERE hotel_id = ?"
                    . ($extraWhere !== '' ? " AND ({$extraWhere})" : '');
                $stmt = $db->query($sql, array_merge([$visto, $hotelId], $params));
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                if (!empty($row['nuevo'])) {
                    $map[$ruta] = ['dot' => true];
                }
            } catch (Throwable $e) {
                // Sin novedad si la columna/tabla no calza.
            }
        };

        // ── Secciones de accion (numero) ─────────────────────────────────
        // Reservaciones por atender: llegadas de hoy + check-ins vencidos.
        $count('reservaciones', 'reservaciones', "estado = 'confirmada' AND fecha_entrada <= CURDATE()", []);
        // Habitaciones fuera de servicio (mantenimiento).
        $count('habitaciones', 'habitaciones', "estado = 'mantenimiento' AND COALESCE(activa, 1) = 1", []);
        // Areas que piden accion (limpieza o mantenimiento) suman a la misma
        // burbuja: la seccion del sidebar es "Habitaciones y areas".
        if (_sidebar_novedades_tabla_existe($db, 'areas_hotel')) {
            try {
                $stmt = $db->query(
                    "SELECT COUNT(*) AS n FROM areas_hotel
                     WHERE hotel_id = ? AND activa = 1 AND estado IN ('limpieza', 'mantenimiento')",
                    [$hotelId]
                );
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                $n = (int) ($row['n'] ?? 0);
                if ($n > 0) {
                    $map['habitaciones'] = [
                        'count' => $n + (int) ($map['habitaciones']['count'] ?? 0),
                        'tono' => 'count',
                    ];
                }
            } catch (Throwable $e) {
                // La burbuja simplemente no suma areas.
            }
        }
        // Limpieza: habitaciones por asear.
        $count('camarista', 'habitaciones', "estado = 'limpieza' AND COALESCE(activa, 1) = 1", []);
        // Tareas operativas activas.
        $count('tareas', 'tareas_operativas', "estado IN ('pendiente', 'asignada', 'en_proceso')", []);
        // Mantenimiento preventivo por atender: activos vencidos o por vencer
        // en 7 dias (bloque mantenimiento_plus; sin tabla no hay burbuja).
        $count('mantenimientos/activos', 'activos_hotel', "activo = 1 AND proximo_servicio IS NOT NULL AND proximo_servicio <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)", []);
        // Cuentas por pagar pendientes.
        $count('cuentas-por-pagar', 'cuentas_por_pagar', "estado IN ('pendiente', 'parcial', 'vencida')", []);
        // Facturacion: solicitudes por atender (columna es 'estatus', no 'estado').
        $count('facturacion', 'solicitudes_factura', "estatus IN ('pendiente', 'datos_capturados')", [], 'billing');

        // Cuentas por cobrar: reservaciones con saldo pendiente (derivado).
        if (
            _sidebar_novedades_tabla_existe($db, 'reservaciones')
            && _sidebar_novedades_tabla_existe($db, 'reservacion_pagos')
            && _sidebar_novedades_tabla_existe($db, 'reservacion_abonos')
        ) {
            try {
                $stmt = $db->query(
                    "SELECT COUNT(*) AS n FROM (
                        SELECT r.precio_total - COALESCE(p.total, 0) - COALESCE(a.total, 0) AS saldo
                        FROM reservaciones r
                        LEFT JOIN (
                            SELECT reservacion_id, SUM(monto) AS total
                            FROM reservacion_pagos WHERE hotel_id = ? GROUP BY reservacion_id
                        ) p ON p.reservacion_id = r.id
                        LEFT JOIN (
                            SELECT reservacion_id, SUM(monto) AS total
                            FROM reservacion_abonos WHERE hotel_id = ? GROUP BY reservacion_id
                        ) a ON a.reservacion_id = r.id
                        WHERE r.hotel_id = ?
                    ) cxc
                    WHERE saldo > 0.009",
                    [$hotelId, $hotelId, $hotelId]
                );
                $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
                $n = (int) ($row['n'] ?? 0);
                if ($n > 0) {
                    $map['cuentas-por-cobrar'] = ['count' => $n, 'tono' => 'count'];
                }
            } catch (Throwable $e) {
                // Sin burbuja de CxC si el esquema difiere.
            }
        }

        // Mensajes WhatsApp por enviar hoy (bloque canal_whatsapp). La cuenta
        // es derivada (candidatos del dia menos ya atendidos), asi que usa el
        // MISMO criterio del servicio en lugar de un COUNT de tabla.
        if (
            _sidebar_novedades_tabla_existe($db, 'mensajes_whatsapp')
            && (!function_exists('hotel_has_module') || hotel_has_module('canal_whatsapp', $hotelId))
        ) {
            try {
                require_once APP_PATH . '/services/CanalWhatsAppService.php';
                $n = (new CanalWhatsAppService($db))->contarPendientesHoy($hotelId);
                if ($n > 0) {
                    $map['mensajes'] = ['count' => $n, 'tono' => 'count'];
                }
            } catch (Throwable $e) {
                // Sin burbuja de mensajes si el bloque aun no migra.
            }
        }

        // Notificaciones sin leer (respeta rol/usuario via el modelo).
        if (_sidebar_novedades_tabla_existe($db, 'notificaciones')) {
            try {
                require_once APP_PATH . '/models/Notificacion.php';
                $resumen = (new Notificacion())->resumenPorHotel(
                    $hotelId,
                    function_exists('current_hotel_user_role') ? current_hotel_user_role() : null,
                    $usuarioId ?: null
                );
                $n = (int) ($resumen['nuevas'] ?? 0);
                if ($n > 0) {
                    $map['notificaciones'] = ['count' => $n, 'tono' => 'notify'];
                }
            } catch (Throwable $e) {
                // Sin burbuja de notificaciones si falla el modelo.
            }
        }

        // ── Secciones informativas (punto de novedad) ────────────────────
        // Motor de reservas: nueva reserva online pagada.
        $dot('motor-reservas', 'motor_pagos_online', 'created_at', "estado = 'pagado'", []);
        // Reputacion: nueva encuesta respondida por el huesped.
        $dot('reputacion', 'reputacion_encuestas', 'respondida_at', "estado = 'respondida'", []);
        // Check-in digital: huesped completo o avanzo su pre-registro.
        $dot('checkin-digital', 'checkin_digital_links', 'updated_at', "estado <> 'pendiente'", []);
        // Huespedes: alta de huesped nuevo.
        $dot('huespedes', 'huespedes', 'created_at', '', []);
        // Documentos: documento nuevo cargado.
        $dot('documentos', 'documentos', 'created_at', "estado = 'activo'", []);

        return $map;
    }
}
