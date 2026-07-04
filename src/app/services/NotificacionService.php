<?php

require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/PwaPushService.php';

class NotificacionService {
    public static function crear(array $datos): ?int {
        try {
            $hotelId = (int)($datos['hotel_id'] ?? 0);
            if ($hotelId <= 0 && function_exists('obtenerHotelIdActualCompat')) {
                $hotelId = (int) obtenerHotelIdActualCompat();
            }

            if ($hotelId <= 0) {
                return null;
            }

            // Bloque comercial: sin 'notificaciones' contratado no se crean eventos ni push.
            if (function_exists('hotel_has_module') && !hotel_has_module('notificaciones', $hotelId)) {
                return null;
            }

            $datos['hotel_id'] = $hotelId;
            if (!isset($datos['estado']) || trim((string)$datos['estado']) === '') {
                $datos['estado'] = self::estadoInicialSugerido($datos);
            }

            $modelo = new Notificacion();
            $id = $modelo->crearEvento($datos);

            $estadoSolicitado = (string)($datos['estado'] ?? 'nueva');
            $debeEnviarPush = !in_array($estadoSolicitado, ['resuelta', 'descartada'], true);

            if ($id && !$modelo->ultimoEventoFueCreado() && !$debeEnviarPush) {
                self::cerrarEventoExistente((int)$id, $hotelId, $estadoSolicitado);
            }

            if ($id && $modelo->ultimoEventoFueCreado() && $debeEnviarPush) {
                try {
                    (new PwaPushService())->enviarNotificacion($datos, (int)$id);
                } catch (Throwable $e) {
                    error_log('No se pudo enviar push PWA de notificacion: ' . $e->getMessage());
                }
            }

            return $id;
        } catch (Throwable $e) {
            error_log('No se pudo crear notificacion: ' . $e->getMessage());
            return null;
        }
    }

    public static function sincronizarBandeja(int $hotelId): void {
        if ($hotelId <= 0) {
            return;
        }

        try {
            $db = Database::getInstance();

            self::resolverEventosInformativos($db, $hotelId);
            self::resolverEventosFacturacionInformativos($db, $hotelId);
            self::resolverEventosFacturacionCerrados($db, $hotelId);
            self::sincronizarReglaFacturasPendientes($db, $hotelId);
        } catch (Throwable $e) {
            error_log('No se pudo sincronizar la bandeja de notificaciones: ' . $e->getMessage());
        }
    }

    public static function sincronizarFacturacion(int $hotelId, ?int $solicitudId = null): void {
        if ($hotelId <= 0) {
            return;
        }

        try {
            $db = Database::getInstance();

            self::resolverEventosFacturacionInformativos($db, $hotelId, $solicitudId);
            self::resolverEventosFacturacionCerrados($db, $hotelId, $solicitudId);
            self::sincronizarReglaFacturasPendientes($db, $hotelId);
        } catch (Throwable $e) {
            error_log('No se pudieron sincronizar notificaciones de facturacion: ' . $e->getMessage());
        }
    }

    private static function cerrarEventoExistente(int $id, int $hotelId, string $estado): void {
        if ($id <= 0 || $hotelId <= 0 || !in_array($estado, ['resuelta', 'descartada'], true)) {
            return;
        }

        $campoFecha = $estado === 'descartada' ? 'descartada_en' : 'resuelta_en';

        try {
            $db = Database::getInstance();
            $db->query(
                "UPDATE notificaciones
                 SET estado = ?,
                     leida_en = COALESCE(leida_en, NOW()),
                     {$campoFecha} = COALESCE({$campoFecha}, NOW()),
                     dedupe_key = CASE
                        WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                        ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                     END,
                     updated_at = NOW()
                 WHERE id = ?
                   AND hotel_id = ?
                   AND estado IN ('nueva', 'leida')",
                [$estado, $id, $hotelId]
            );
        } catch (Throwable $e) {
            error_log('No se pudo cerrar notificacion existente: ' . $e->getMessage());
        }
    }

    private static function estadoInicialSugerido(array $datos): string {
        $tipo = strtolower(trim((string)($datos['tipo'] ?? '')));
        $severidad = strtolower(trim((string)($datos['severidad'] ?? 'info')));

        $informativas = [
            'factura_en_proceso',
            'factura_completada',
            'factura_cancelada',
            'mantenimiento_programado_cancelado',
        ];

        if (in_array($tipo, $informativas, true)) {
            return 'resuelta';
        }

        if ($tipo === 'corte_cerrado' && $severidad === 'info') {
            return 'resuelta';
        }

        return 'nueva';
    }

    private static function resolverEventosInformativos(Database $db, int $hotelId): void {
        $db->query(
            "UPDATE notificaciones
             SET estado = 'resuelta',
                 leida_en = COALESCE(leida_en, NOW()),
                 resuelta_en = COALESCE(resuelta_en, NOW()),
                 dedupe_key = CASE
                    WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                    ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                 END,
                 updated_at = NOW()
             WHERE hotel_id = ?
               AND estado IN ('nueva', 'leida')
               AND (
                    tipo IN ('mantenimiento_programado_cancelado')
                    OR (tipo = 'corte_cerrado' AND severidad = 'info')
               )",
            [$hotelId]
        );
    }

    private static function resolverEventosFacturacionInformativos(Database $db, int $hotelId, ?int $solicitudId = null): void {
        $whereSolicitud = '';
        $params = [$hotelId];

        if ($solicitudId !== null && $solicitudId > 0) {
            $whereSolicitud = ' AND entidad_id = ?';
            $params[] = $solicitudId;
        }

        $db->query(
            "UPDATE notificaciones
             SET estado = 'resuelta',
                 leida_en = COALESCE(leida_en, NOW()),
                 resuelta_en = COALESCE(resuelta_en, NOW()),
                 dedupe_key = CASE
                    WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                    ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                 END,
                 updated_at = NOW()
             WHERE hotel_id = ?
               AND modulo = 'facturacion'
               AND entidad_tipo = 'solicitud_factura'
               AND tipo IN ('factura_en_proceso', 'factura_completada', 'factura_cancelada')
               AND estado IN ('nueva', 'leida')" . $whereSolicitud,
            $params
        );
    }

    private static function resolverEventosFacturacionCerrados(Database $db, int $hotelId, ?int $solicitudId = null): void {
        $whereSolicitud = '';
        $params = [$hotelId];

        if ($solicitudId !== null && $solicitudId > 0) {
            $whereSolicitud = ' AND n.entidad_id = ?';
            $params[] = $solicitudId;
        }

        $db->query(
            "UPDATE notificaciones n
             INNER JOIN solicitudes_factura sf
                ON sf.id = n.entidad_id
               AND sf.hotel_id = n.hotel_id
             SET n.estado = 'resuelta',
                 n.leida_en = COALESCE(n.leida_en, NOW()),
                 n.resuelta_en = COALESCE(n.resuelta_en, NOW()),
                 n.dedupe_key = CASE
                    WHEN n.dedupe_key IS NULL OR n.dedupe_key LIKE '%.resuelta.%' THEN n.dedupe_key
                    ELSE LEFT(CONCAT(n.dedupe_key, '.resuelta.', n.id), 160)
                 END,
                 n.updated_at = NOW()
             WHERE n.hotel_id = ?
               AND n.modulo = 'facturacion'
               AND n.entidad_tipo = 'solicitud_factura'
               AND n.estado IN ('nueva', 'leida')
               AND sf.estatus IN ('completada', 'cancelada')" . $whereSolicitud,
            $params
        );
    }

    private static function sincronizarReglaFacturasPendientes(Database $db, int $hotelId): void {
        $stmt = $db->query(
            "SELECT COUNT(*) AS total
             FROM solicitudes_factura
             WHERE hotel_id = ?
               AND estatus = 'pendiente'",
            [$hotelId]
        );

        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        $pendientes = (int)($row['total'] ?? 0);

        if ($pendientes <= 0) {
            $db->query(
                "UPDATE notificaciones
                 SET estado = 'resuelta',
                     leida_en = COALESCE(leida_en, NOW()),
                     resuelta_en = COALESCE(resuelta_en, NOW()),
                     dedupe_key = CASE
                        WHEN dedupe_key IS NULL OR dedupe_key LIKE '%.resuelta.%' THEN dedupe_key
                        ELSE LEFT(CONCAT(dedupe_key, '.resuelta.', id), 160)
                     END,
                     updated_at = NOW()
                 WHERE hotel_id = ?
                   AND tipo = 'regla_facturas_pendientes'
                   AND estado IN ('nueva', 'leida')",
                [$hotelId]
            );
            return;
        }

        $umbralAlta = function_exists('hotel_notification_threshold')
            ? hotel_notification_threshold('umbral_facturas_alta', 5, $hotelId, 1, 100)
            : 5;
        $titulo = $pendientes === 1 ? 'Factura pendiente' : $pendientes . ' facturas pendientes';
        $severidad = $pendientes >= (int)$umbralAlta ? 'alta' : 'media';

        $db->query(
            "UPDATE notificaciones
             SET titulo = ?,
                 severidad = ?,
                 mensaje = 'Hay solicitudes de factura esperando captura o revision.',
                 url = 'facturacion?estatus=pendiente',
                 updated_at = NOW()
             WHERE hotel_id = ?
               AND tipo = 'regla_facturas_pendientes'
               AND estado IN ('nueva', 'leida')",
            [$titulo, $severidad, $hotelId]
        );
    }
}
