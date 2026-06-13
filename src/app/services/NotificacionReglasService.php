<?php

require_once __DIR__ . '/NotificacionService.php';
require_once __DIR__ . '/ReporteGerencialDiarioService.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class NotificacionReglasService {
    private $db;
    private $hotelId;
    private $fechaClave;
    private $tiposActivos = [];

    public function __construct(int $hotelId) {
        $this->db = Database::getInstance();
        $this->hotelId = $hotelId;
        $this->fechaClave = date('Ymd');
    }

    public static function evaluarDashboard(int $hotelId): array {
        if ($hotelId <= 0) {
            return [];
        }

        try {
            $servicio = new self($hotelId);
            return $servicio->evaluar();
        } catch (Throwable $e) {
            error_log('No se pudieron evaluar reglas de notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    public function evaluar(): array {
        if (function_exists('hotel_notifications_automatic_enabled')
            && !hotel_notifications_automatic_enabled($this->hotelId)) {
            $this->resolverAutomaticasInactivas([]);
            return [];
        }

        $creadas = [];

        $creadas[] = $this->reglaCheckInsPendientes();
        $creadas[] = $this->reglaCheckOutsPendientes();
        $creadas[] = $this->reglaFacturasPendientes();
        $creadas[] = $this->reglaMantenimientoActivo();
        $creadas[] = $this->reglaHabitacionesEnLimpieza();
        $creadas[] = $this->reglaCajaAbiertaProlongada();
        $creadas[] = $this->reglaInventarioBajo();
        $creadas[] = $this->reglaReporteGerencialDiario();

        $this->resolverAutomaticasInactivas($this->tiposActivos);

        return array_values(array_filter($creadas));
    }

    private function reglaCheckInsPendientes(): ?int {
        if (!$this->moduloActivo('reservaciones') || !$this->reglaActiva('checkins_pendientes')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(MAX(DATEDIFF(CURDATE(), fecha_entrada)), 0) AS dias_maximos
             FROM reservaciones
             WHERE hotel_id = ?
               AND estado = 'confirmada'
               AND fecha_entrada < CURDATE()",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $dias = (int)($row['dias_maximos'] ?? 0);
        $umbralAlta = $this->umbral('umbral_retraso_alta_dias', 2, 1, 30);
        return $this->crearRegla([
            'modulo' => 'reservaciones',
            'tipo' => 'regla_checkins_pendientes',
            'severidad' => $dias >= $umbralAlta ? 'alta' : 'media',
            'titulo' => $total === 1 ? 'Check-in pendiente' : $total . ' check-ins pendientes',
            'mensaje' => $dias > 0
                ? 'Hay reservaciones confirmadas con fecha de entrada vencida. Retraso maximo: ' . $dias . ' dia(s).'
                : 'Hay reservaciones confirmadas pendientes de check-in.',
            'url' => 'reservaciones',
            'dedupe_key' => 'regla.checkins_pendientes.' . $this->fechaClave,
        ]);
    }

    private function reglaCheckOutsPendientes(): ?int {
        if (!$this->moduloActivo('reservaciones') || !$this->reglaActiva('checkouts_pendientes')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(MAX(DATEDIFF(CURDATE(), fecha_salida)), 0) AS dias_maximos
             FROM reservaciones
             WHERE hotel_id = ?
               AND estado = 'checked_in'
               AND fecha_salida < CURDATE()",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $dias = (int)($row['dias_maximos'] ?? 0);
        $umbralAlta = $this->umbral('umbral_retraso_alta_dias', 2, 1, 30);
        return $this->crearRegla([
            'modulo' => 'reservaciones',
            'tipo' => 'regla_checkouts_pendientes',
            'severidad' => $dias >= $umbralAlta ? 'alta' : 'media',
            'titulo' => $total === 1 ? 'Check-out pendiente' : $total . ' check-outs pendientes',
            'mensaje' => $dias > 0
                ? 'Hay habitaciones con fecha de salida vencida. Retraso maximo: ' . $dias . ' dia(s).'
                : 'Hay salidas pendientes de revisar.',
            'url' => 'reservaciones',
            'dedupe_key' => 'regla.checkouts_pendientes.' . $this->fechaClave,
        ]);
    }

    private function reglaFacturasPendientes(): ?int {
        if (!$this->moduloActivo('facturacion') || !$this->reglaActiva('facturas_pendientes')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM solicitudes_factura
             WHERE hotel_id = ?
               AND estatus = 'pendiente'",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $umbralAlta = $this->umbral('umbral_facturas_alta', 5, 1, 100);
        return $this->crearRegla([
            'modulo' => 'facturacion',
            'tipo' => 'regla_facturas_pendientes',
            'severidad' => $total >= $umbralAlta ? 'alta' : 'media',
            'titulo' => $total === 1 ? 'Factura pendiente' : $total . ' facturas pendientes',
            'mensaje' => 'Hay solicitudes de factura esperando captura o revision.',
            'url' => 'facturacion?estatus=pendiente',
            'dedupe_key' => 'regla.facturas_pendientes.' . $this->fechaClave,
        ]);
    }

    private function reglaMantenimientoActivo(): ?int {
        if (!$this->moduloActivo('habitaciones') || !$this->reglaActiva('mantenimiento_activo')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN prioridad IN ('alta', 'urgente') THEN 1 ELSE 0 END) AS prioritarios
             FROM mantenimientos_habitaciones
             WHERE hotel_id = ?
               AND estado = 'en_proceso'",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $prioritarios = (int)($row['prioritarios'] ?? 0);
        return $this->crearRegla([
            'modulo' => 'habitaciones',
            'tipo' => 'regla_mantenimiento_activo',
            'severidad' => $prioritarios > 0 ? 'alta' : 'media',
            'titulo' => $total === 1 ? 'Habitacion en mantenimiento' : $total . ' habitaciones en mantenimiento',
            'mensaje' => $prioritarios > 0
                ? 'Hay mantenimientos activos con prioridad alta o urgente.'
                : 'Hay habitaciones fuera de operacion por mantenimiento.',
            'url' => 'habitaciones?estado=mantenimiento',
            'dedupe_key' => 'regla.mantenimiento_activo.' . $this->fechaClave,
        ]);
    }

    private function reglaHabitacionesEnLimpieza(): ?int {
        if (!$this->moduloActivo('habitaciones') || !$this->reglaActiva('habitaciones_limpieza')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total
             FROM habitaciones
             WHERE hotel_id = ?
               AND activa = 1
               AND estado = 'limpieza'",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $umbralMedia = $this->umbral('umbral_limpieza_media', 5, 1, 200);
        return $this->crearRegla([
            'modulo' => 'habitaciones',
            'tipo' => 'regla_habitaciones_limpieza',
            'severidad' => $total >= $umbralMedia ? 'media' : 'info',
            'titulo' => $total === 1 ? 'Habitacion en limpieza' : $total . ' habitaciones en limpieza',
            'mensaje' => 'Hay habitaciones pendientes de liberar despues de limpieza.',
            'url' => 'habitaciones?estado=limpieza',
            'dedupe_key' => 'regla.habitaciones_limpieza.' . $this->fechaClave,
        ]);
    }

    private function reglaCajaAbiertaProlongada(): ?int {
        if (!$this->moduloActivo('caja') || !$this->reglaActiva('caja_abierta_prolongada')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT id,
                    TIMESTAMPDIFF(HOUR, fecha_apertura, NOW()) AS horas_abierta
             FROM cortes_caja
             WHERE hotel_id = ?
               AND estado = 'abierto'
             ORDER BY fecha_apertura ASC
             LIMIT 1",
            [$this->hotelId]
        );

        $corteId = (int)($row['id'] ?? 0);
        $horas = (int)($row['horas_abierta'] ?? 0);
        $umbralMedia = $this->umbral('umbral_caja_horas_media', 12, 1, 168);
        $umbralAlta = max($umbralMedia, $this->umbral('umbral_caja_horas_alta', 24, 1, 336));

        if ($corteId <= 0 || $horas < $umbralMedia) {
            return null;
        }

        return $this->crearRegla([
            'modulo' => 'caja',
            'tipo' => 'regla_caja_abierta_prolongada',
            'severidad' => $horas >= $umbralAlta ? 'alta' : 'media',
            'titulo' => 'Caja abierta por ' . $horas . ' horas',
            'mensaje' => 'Hay un corte de caja abierto desde hace varias horas. Conviene revisar si debe cerrarse.',
            'entidad_tipo' => 'corte_caja',
            'entidad_id' => $corteId,
            'url' => 'caja/corte',
            'dedupe_key' => 'regla.caja_abierta_prolongada.' . $corteId . '.' . $this->fechaClave,
        ]);
    }

    private function reglaInventarioBajo(): ?int {
        if (!$this->moduloActivo('inventario') || !$this->reglaActiva('inventario_bajo')) {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) AS sin_stock
             FROM inventario_productos
             WHERE hotel_id = ?
               AND activo = 1
               AND stock_actual <= stock_minimo",
            [$this->hotelId]
        );

        $total = (int)($row['total'] ?? 0);
        if ($total <= 0) {
            return null;
        }

        $sinStock = (int)($row['sin_stock'] ?? 0);
        return $this->crearRegla([
            'modulo' => 'inventario',
            'tipo' => 'regla_inventario_bajo',
            'severidad' => $sinStock > 0 ? 'critica' : 'alta',
            'titulo' => $total === 1 ? 'Producto con stock bajo' : $total . ' productos con stock bajo',
            'mensaje' => $sinStock > 0
                ? $sinStock . ' producto(s) estan sin stock disponible.'
                : 'Hay productos por debajo de su minimo configurado.',
            'url' => 'inventario',
            'dedupe_key' => 'regla.inventario_bajo.' . $this->fechaClave,
        ]);
    }

    private function reglaReporteGerencialDiario(): ?int {
        if (!$this->moduloActivo('reportes') || !$this->reglaActiva('reporte_gerencial_diario')) {
            return null;
        }

        $servicio = new ReporteGerencialDiarioService();
        $reporte = $servicio->generar($this->hotelId, date('Y-m-d'));
        $fecha = (string)($reporte['fecha'] ?? date('Y-m-d'));

        return $this->crearRegla([
            'rol_destino' => 'gerente',
            'modulo' => 'reportes',
            'tipo' => 'regla_reporte_gerencial_diario',
            'severidad' => (string)($reporte['severidad'] ?? 'info'),
            'titulo' => 'Reporte gerencial diario listo',
            'mensaje' => $servicio->mensajeNotificacion($reporte),
            'url' => 'reportes/gerencial-diario?fecha=' . $fecha,
            'dedupe_key' => 'regla.reporte_gerencial_diario.' . str_replace('-', '', $fecha),
        ]);
    }

    private function crearRegla(array $datos): ?int {
        $datos['hotel_id'] = $this->hotelId;
        $datos['creada_por'] = function_exists('user_id') ? user_id() : null;

        $tipo = (string)($datos['tipo'] ?? '');
        if (strpos($tipo, 'regla_') === 0) {
            $this->tiposActivos[] = $tipo;
        }

        return NotificacionService::crear($datos);
    }

    private function fetchOne(string $sql, array $params = []): array {
        $stmt = $this->db->query($sql, $params);
        if (!$stmt) {
            return [];
        }

        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }

    private function moduloActivo(string $clave): bool {
        if (function_exists('hotel_menu_module_enabled')) {
            return hotel_menu_module_enabled($clave);
        }

        if (function_exists('current_hotel_has_module')) {
            return current_hotel_has_module($clave);
        }

        return true;
    }

    private function reglaActiva(string $regla): bool {
        if (function_exists('hotel_notification_rule_enabled')) {
            return hotel_notification_rule_enabled($regla, $this->hotelId);
        }

        return true;
    }

    private function umbral(string $clave, int $default, int $min, int $max): int {
        if (function_exists('hotel_notification_threshold')) {
            return hotel_notification_threshold($clave, $default, $this->hotelId, $min, $max);
        }

        return max($min, min($max, $default));
    }

    private function resolverAutomaticasInactivas(array $tiposActivos): void {
        try {
            $modelo = new Notificacion();
            $modelo->resolverAutomaticasInactivas($this->hotelId, $tiposActivos);
        } catch (Throwable $e) {
            error_log('No se pudieron resolver notificaciones automaticas inactivas: ' . $e->getMessage());
        }
    }
}
