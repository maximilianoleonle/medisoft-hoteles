<?php
/**
 * Controlador del Dashboard - VERSIÓN MEJORADA
 */

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../models/Notificacion.php';
require_once __DIR__ . '/../services/NotificacionReglasService.php';
require_once __DIR__ . '/../services/NotificacionService.php';

class DashboardController extends Controller {
    
    private $cajaModel;
    private $habitacionModel;
    private $reservacionModel;
    private $movimientoModel;
    private $dashboardNombreVisual;
    
    /**
     * Constructor - Inicializar modelos
     */
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->cajaModel = new Caja();
        $this->habitacionModel = new Habitacion();
        $this->reservacionModel = new Reservacion();
        $this->movimientoModel = new MovimientoCaja();
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function dashboardTitle() {
        return 'Dashboard - ' . $this->dashboardNombreVisual();
    }

    private function dashboardNombreVisual() {
        if ($this->dashboardNombreVisual !== null) {
            return $this->dashboardNombreVisual;
        }

        $fallback = 'Medisoft Hoteles';
        $nombreHotel = function_exists('current_hotel_nombre') ? current_hotel_nombre() : ($_SESSION['hotel_nombre'] ?? null);
        $nombreHotel = trim((string) $nombreHotel);
        $fallbackHotel = $nombreHotel !== '' ? $nombreHotel : $fallback;

        if (function_exists('has_hotel_context') && has_hotel_context()
            && function_exists('current_hotel_branding')
            && function_exists('hotel_branding_public_name')) {
            try {
                $branding = current_hotel_branding();
                $this->dashboardNombreVisual = hotel_branding_public_name(is_array($branding) ? $branding : [], $fallbackHotel);
                return $this->dashboardNombreVisual;
            } catch (Throwable $e) {
                error_log('Error al resolver branding para dashboard: ' . $e->getMessage());
            }
        }

        $this->dashboardNombreVisual = $fallbackHotel;
        return $this->dashboardNombreVisual;
    }
    
    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('dashboard');
        return true;
    }
    
    /**
     * Página principal del dashboard mejorada
     */
    public function indexAction() {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        try {
            // Obtener estadísticas completas
            $stats = $this->getEstadisticasCompletas();
            
            // Obtener información de la caja
            $caja_info = $this->getCajaInfo();
            
            // Obtener corte actual
            $corte_actual = $this->cajaModel->obtenerCorteActual();
            
            // Obtener reservaciones de hoy
            $reservacionesHoy = $this->getReservacionesHoy();
            
            // Obtener próximas llegadas y salidas
            $proximasLlegadas = $this->getProximasLlegadas();
            $proximasSalidas = $this->getProximasSalidas();
            
            // Obtener check-ins y check-outs pendientes (pasados de fecha)
            $alertasPendientes = $this->getAlertasPendientesReservaciones();
            $checkInsPendientes = $alertasPendientes['checkins'];
            $checkOutsPendientes = $alertasPendientes['checkouts'];
            
            // Obtener datos para gráficos
            $datosGraficos = $this->getDatosGraficos();
            // Generacion diferida: el motor de reglas + sincronizacion corren a
            // lo sumo una vez por intervalo por hotel (la vista Notificaciones
            // sigue sincronizando fresco al abrirse).
            if (NotificacionService::debeSincronizar($this->hotelIdActual())) {
                NotificacionReglasService::evaluarDashboard($this->hotelIdActual());
                NotificacionService::sincronizarBandeja($this->hotelIdActual());
            }
            $notificacionesDashboard = $this->getNotificacionesDashboard();

            // Mensajes WhatsApp por enviar hoy (bloque canal_whatsapp): ficha
            // discreta. Solo lectura y a prueba de fallos: sin bloque -> null.
            $mensajesWhatsApp = null;
            try {
                if (function_exists('hotel_has_module') && hotel_has_module('canal_whatsapp', (int) $this->hotelIdActual())) {
                    require_once __DIR__ . '/../services/CanalWhatsAppService.php';
                    $cwServicio = new CanalWhatsAppService();
                    $mensajesWhatsApp = [
                        'pendientes' => $cwServicio->contarPendientesHoy((int) $this->hotelIdActual()),
                        'faltantes' => $cwServicio->configuracionFaltante((int) $this->hotelIdActual()),
                    ];
                }
            } catch (Throwable $e) {
                $mensajesWhatsApp = null;
            }

            // Preparar datos para la vista
            $data = [
                'title' => $this->dashboardTitle(),
                'stats' => $stats,
                'caja_info' => $caja_info,
                'corte_actual' => $corte_actual,
                'reservaciones_hoy' => $reservacionesHoy,
                'proximas_llegadas' => $proximasLlegadas,
                'proximas_salidas' => $proximasSalidas,
                'checkins_pendientes' => $checkInsPendientes,
                'checkouts_vencidos' => $checkOutsPendientes,
                'llegadas_tardias' => [], // Puedes implementar esta funcionalidad después
                'graficos' => $datosGraficos,
                'notificaciones_resumen' => $notificacionesDashboard['resumen'],
                'notificaciones_recientes' => $notificacionesDashboard['recientes'],
                'mensajes_whatsapp' => $mensajesWhatsApp
            ];
            
            // Renderizar vista
            View::renderTemplate('dashboard/index', $data);
            
        } catch (Exception $e) {
            error_log("Error en Dashboard: " . $e->getMessage());
            
            // Renderizar con datos vacíos en caso de error
            View::renderTemplate('dashboard/index', [
                'title' => $this->dashboardTitle(),
                'stats' => $this->getEstadisticasVacias(),
                'caja_info' => null,
                'corte_actual' => null,
                'reservaciones_hoy' => [],
                'proximas_llegadas' => [],
                'proximas_salidas' => [],
                'checkins_pendientes' => [],
                'checkouts_vencidos' => [],
                'llegadas_tardias' => [],
                'graficos' => [],
                'notificaciones_resumen' => $this->getResumenNotificacionesVacio(),
                'notificaciones_recientes' => [],
                'error' => 'Error al cargar los datos del dashboard'
            ]);
        }
    }
    
    /**
     * Obtener estadísticas completas mejoradas
     */
    private function condicionReversoIngresoCaja(string $alias = 'mc'): string {
        $prefix = $alias !== '' ? $alias . '.' : '';

        return "(
            {$prefix}tipo = 'gasto'
            AND (
                LOWER(COALESCE({$prefix}categoria, '')) IN ('devolucion', 'devoluciones', 'reverso anticipo', 'reverso de anticipo', 'reversion cobro cxc')
                OR LOWER(COALESCE({$prefix}categoria, '')) LIKE 'devoluc%'
                OR LOWER(COALESCE({$prefix}categoria, '')) LIKE 'reverso anticipo%'
                OR LOWER(COALESCE({$prefix}categoria, '')) LIKE 'reverso de anticipo%'
                OR LOWER(COALESCE({$prefix}categoria, '')) LIKE 'reversion cobro cxc%'
                OR LOWER(COALESCE({$prefix}descripcion, '')) LIKE 'reverso de anticipo%'
                OR LOWER(COALESCE({$prefix}descripcion, '')) LIKE 'reversion cobro cxc%'
            )
        )";
    }

    private function getEstadisticasCompletas() {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        // 1. HABITACIONES - Estado actual detallado
        $stmt = $db->query("
            SELECT 
                estado,
                COUNT(*) as total 
            FROM habitaciones 
            WHERE hotel_id = ?
            AND activa = 1
            GROUP BY estado
        ", [$hotel_id]);
        
        $habitacionesPorEstado = [];
        while ($row = $stmt->fetch()) {
            $habitacionesPorEstado[$row['estado']] = $row['total'];
        }
        
        // Total de habitaciones activas
        $stmt = $db->query(
            "SELECT COUNT(*) as total FROM habitaciones WHERE hotel_id = ? AND activa = 1",
            [$hotel_id]
        );
        $totalHabitaciones = $stmt->fetch()['total'] ?? 0;
        
        $ocupadas = $habitacionesPorEstado['ocupada'] ?? 0;
        $disponibles = $habitacionesPorEstado['disponible'] ?? 0;
        $limpieza = $habitacionesPorEstado['limpieza'] ?? 0;
        $mantenimiento = $habitacionesPorEstado['mantenimiento'] ?? 0;
        
        // Contar habitaciones con check-in pendiente para hoy
        $stmt = $db->query("
            SELECT COUNT(DISTINCT rh.habitacion_id) as total
            FROM reservacion_habitaciones rh
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND r.hotel_id = rh.hotel_id
            INNER JOIN habitaciones h ON rh.habitacion_id = h.id AND h.hotel_id = rh.hotel_id
            WHERE rh.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'
            AND h.estado = 'disponible'
            AND h.activa = 1
        ", [$hotel_id]);
        $porLlegar = $stmt->fetch()['total'] ?? 0;
        
        // Ajustar disponibles reales
        $disponiblesReales = $disponibles - $porLlegar;
        
        // Porcentaje de ocupación
        $porcentajeOcupacion = $totalHabitaciones > 0 ? round(($ocupadas / $totalHabitaciones) * 100, 1) : 0;
        
        // 2. INGRESOS Y EGRESOS DEL DÍA - VERSIÓN CORREGIDA
        
        // Inicializar arrays
        $ingresosPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];
        
        $egresosPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];
        
        // Obtener TODOS los ingresos del día
        $stmt = $db->query("
            SELECT 
                metodo_pago,
                SUM(monto) as total
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) = CURDATE()
            AND tipo = 'ingreso'
            GROUP BY metodo_pago
        ", [$hotel_id]);
        
        while ($row = $stmt->fetch()) {
            $metodo = $row['metodo_pago'];
            if (isset($ingresosPorMetodo[$metodo])) {
                $ingresosPorMetodo[$metodo] = floatval($row['total']);
            }
        }
        
        // Obtener TODOS los gastos del día
        $stmt = $db->query("
            SELECT 
                metodo_pago,
                SUM(monto) as total
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) = CURDATE()
            AND tipo = 'gasto'
            GROUP BY metodo_pago
        ", [$hotel_id]);
        
        while ($row = $stmt->fetch()) {
            $metodo = $row['metodo_pago'];
            if (isset($egresosPorMetodo[$metodo])) {
                $egresosPorMetodo[$metodo] = floatval($row['total']);
            }
        }
        
        // Obtener específicamente las devoluciones para procesamiento especial
        $stmt = $db->query("
            SELECT 
                metodo_pago,
                SUM(monto) as total
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) = CURDATE()
            AND tipo = 'gasto'
            AND categoria = 'Devoluciones'
            GROUP BY metodo_pago
        ", [$hotel_id]);
        
        $devolucionesPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];
        
        while ($row = $stmt->fetch()) {
            $metodo = $row['metodo_pago'];
            if (isset($devolucionesPorMetodo[$metodo])) {
                $devolucionesPorMetodo[$metodo] = floatval($row['total']);
            }
        }
        
        // Las devoluciones se restan de los ingresos porque son realmente una reducción del ingreso
        // Las devoluciones afectan los ingresos (reducción) pero siguen siendo egresos
foreach ($devolucionesPorMetodo as $metodo => $monto) {
    if ($monto > 0) {
        // Restar de los ingresos SOLAMENTE
        $ingresosPorMetodo[$metodo] = max(0, $ingresosPorMetodo[$metodo] - $monto);
        // NO restar de los egresos - las devoluciones deben mostrarse como gastos
    }
}
        
        // Calcular totales
        $totalIngresosHoy = array_sum($ingresosPorMetodo);
        $totalEgresosHoy = array_sum($egresosPorMetodo);

        $ingresosBrutosPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];

        $reversosPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];

        $gastosRealesPorMetodo = [
            'efectivo' => 0,
            'tarjeta' => 0,
            'transferencia' => 0
        ];

        $condicionReverso = $this->condicionReversoIngresoCaja('mc');

        $stmt = $db->query("
            SELECT
                mc.metodo_pago,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) as ingresos_brutos,
                SUM(CASE WHEN {$condicionReverso} THEN mc.monto ELSE 0 END) as reversos,
                SUM(CASE WHEN mc.tipo IN ('gasto', 'egreso') AND NOT {$condicionReverso} THEN mc.monto ELSE 0 END) as gastos_reales
            FROM movimientos_caja mc
            WHERE mc.hotel_id = ?
            AND DATE(mc.created_at) = CURDATE()
            GROUP BY mc.metodo_pago
        ", [$hotel_id]);

        while ($row = $stmt->fetch()) {
            $metodo = $row['metodo_pago'];
            if (isset($ingresosBrutosPorMetodo[$metodo])) {
                $ingresosBrutosPorMetodo[$metodo] = floatval($row['ingresos_brutos']);
                $reversosPorMetodo[$metodo] = floatval($row['reversos']);
                $gastosRealesPorMetodo[$metodo] = floatval($row['gastos_reales']);
            }
        }

        foreach ($ingresosBrutosPorMetodo as $metodo => $monto) {
            $ingresosPorMetodo[$metodo] = $monto - ($reversosPorMetodo[$metodo] ?? 0);
            $egresosPorMetodo[$metodo] = $gastosRealesPorMetodo[$metodo] ?? 0;
        }

        $totalIngresosBrutosHoy = array_sum($ingresosBrutosPorMetodo);
        $totalReversosHoy = array_sum($reversosPorMetodo);
        $totalIngresosHoy = array_sum($ingresosPorMetodo);
        $totalEgresosHoy = array_sum($egresosPorMetodo);
        $balanceFinancieroHoy = $totalIngresosHoy - $totalEgresosHoy;
        
        // 3. ENTRADAS Y SALIDAS DE HOY
        // Entradas (Check-ins) de hoy
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN hora_entrada IS NULL THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN hora_entrada IS NOT NULL THEN 1 ELSE 0 END) as completadas
            FROM reservaciones 
            WHERE hotel_id = ?
            AND fecha_entrada = CURDATE()
            AND estado IN ('confirmada', 'checked_in')
        ", [$hotel_id]);
        $entradasHoy = $stmt->fetch();
        
        // Salidas (Check-outs) de hoy
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN hora_salida IS NULL THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN hora_salida IS NOT NULL THEN 1 ELSE 0 END) as completadas
            FROM reservaciones 
            WHERE hotel_id = ?
            AND fecha_salida = CURDATE()
            AND estado IN ('checked_in', 'checked_out')
        ", [$hotel_id]);
        $salidasHoy = $stmt->fetch();
        
        // 4. HUÉSPEDES ACTUALES
        $stmt = $db->query("
            SELECT 
                COUNT(DISTINCT r.huesped_id) as total_huespedes,
                COUNT(DISTINCT rh.habitacion_id) as habitaciones_ocupadas
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
            AND r.estado = 'checked_in'
            AND r.fecha_entrada <= CURDATE()
            AND r.fecha_salida >= CURDATE()
        ", [$hotel_id]);
        $huespedesActuales = $stmt->fetch();
        
        return [
            'habitaciones' => [
                'total' => $totalHabitaciones,
                'ocupadas' => $ocupadas,
                'disponibles' => $disponibles,
                'disponibles_reales' => $disponiblesReales,
                'por_llegar' => $porLlegar,
                'limpieza' => $limpieza,
                'mantenimiento' => $mantenimiento,
                'porcentaje_ocupacion' => $porcentajeOcupacion
            ],
            'ingresos' => [
                'total_dia' => $totalIngresosHoy,
                'brutos_total_dia' => $totalIngresosBrutosHoy,
                'reversos_total_dia' => $totalReversosHoy,
                'efectivo_dia' => $ingresosPorMetodo['efectivo'],
                'tarjeta_dia' => $ingresosPorMetodo['tarjeta'],
                'transferencia_dia' => $ingresosPorMetodo['transferencia'],
                'brutos_por_metodo' => $ingresosBrutosPorMetodo,
                'reversos_por_metodo' => $reversosPorMetodo
            ],
            'reversos' => [
                'total_dia' => $totalReversosHoy,
                'efectivo_dia' => $reversosPorMetodo['efectivo'],
                'tarjeta_dia' => $reversosPorMetodo['tarjeta'],
                'transferencia_dia' => $reversosPorMetodo['transferencia']
            ],
            'egresos' => [
                'total_dia' => $totalEgresosHoy,
                'efectivo_dia' => $egresosPorMetodo['efectivo'],
                'tarjeta_dia' => $egresosPorMetodo['tarjeta'],
                'transferencia_dia' => $egresosPorMetodo['transferencia'],
                'gastos_reales_total_dia' => $totalEgresosHoy
            ],
            'finanzas' => [
                'entradas_brutas' => $totalIngresosBrutosHoy,
                'reversos' => $totalReversosHoy,
                'ingreso_neto' => $totalIngresosHoy,
                'gastos_reales' => $totalEgresosHoy,
                'balance' => $balanceFinancieroHoy
            ],
            'entradas' => [
                'total' => $entradasHoy['total'] ?? 0,
                'pendientes' => $entradasHoy['pendientes'] ?? 0,
                'completadas' => $entradasHoy['completadas'] ?? 0
            ],
            'salidas' => [
                'total' => $salidasHoy['total'] ?? 0,
                'pendientes' => $salidasHoy['pendientes'] ?? 0,
                'completadas' => $salidasHoy['completadas'] ?? 0
            ],
            'huespedes' => [
                'total' => $huespedesActuales['total_huespedes'] ?? 0,
                'habitaciones_ocupadas' => $huespedesActuales['habitaciones_ocupadas'] ?? 0
            ]
        ];
    }

    private function getNotificacionesDashboard() {
        $vacio = [
            'resumen' => $this->getResumenNotificacionesVacio(),
            'recientes' => []
        ];

        try {
            $modelo = new Notificacion();
            if (!$modelo->tablaDisponible()) {
                return $vacio;
            }

            $hotelId = $this->hotelIdActual();
            $rolUsuario = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
            $usuarioId = function_exists('user_id') ? user_id() : null;

            $resumen = $modelo->resumenPorHotel($hotelId, $rolUsuario, $usuarioId);
            $resumen['pendientes'] = (int)($resumen['nuevas'] ?? 0);

            return [
                'resumen' => $resumen,
                'recientes' => $modelo->listarPorHotel($hotelId, [
                    'estado' => 'nueva',
                    'rol_usuario' => $rolUsuario,
                    'usuario_id' => $usuarioId,
                ], 8)
            ];
        } catch (Throwable $e) {
            error_log('Error obteniendo notificaciones del dashboard: ' . $e->getMessage());
            return $vacio;
        }
    }

    private function getResumenNotificacionesVacio() {
        return [
            'total' => 0,
            'pendientes' => 0,
            'nuevas' => 0,
            'prioritarias' => 0,
            'resueltas' => 0,
            'historial' => 0,
            'hoy' => 0,
        ];
    }

    private function getAlertasPendientesReservaciones() {
        $hotelId = $this->hotelIdActual();
        if ($hotelId <= 0) {
            return ['checkins' => [], 'checkouts' => []];
        }

        $db = Database::getInstance();

        $sqlCheckins = "SELECT
                    r.id,
                    r.hotel_id,
                    r.huesped_id,
                    r.fecha_entrada,
                    r.hora_llegada_estimada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado = 'confirmada'
                  AND r.fecha_entrada < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_entrada
                LIMIT 10";

        $sqlCheckouts = "SELECT
                    r.id,
                    r.hotel_id,
                    r.huesped_id,
                    r.fecha_salida,
                    r.hora_entrada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado = 'checked_in'
                  AND r.fecha_salida < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_salida
                LIMIT 10";

        $stmtCheckins = $db->query($sqlCheckins, [$hotelId]);
        $stmtCheckouts = $db->query($sqlCheckouts, [$hotelId]);

        return [
            'checkins' => $stmtCheckins ? ($stmtCheckins->fetchAll() ?: []) : [],
            'checkouts' => $stmtCheckouts ? ($stmtCheckouts->fetchAll() ?: []) : [],
        ];
    }
    
    /**
     * Obtener información de la caja actual
     */
    private function getCajaInfo() {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        $condicionReverso = $this->condicionReversoIngresoCaja('mc');
        
        $stmt = $db->query("
            SELECT 
                cc.id,
                cc.fecha_apertura,
                cc.monto_inicial,
                COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END), 0) as total_ingresos,
                COALESCE(SUM(CASE WHEN mc.tipo IN ('gasto', 'egreso') THEN mc.monto ELSE 0 END), 0) as total_gastos,
                COALESCE(SUM(CASE WHEN {$condicionReverso} THEN mc.monto ELSE 0 END), 0) as total_reversos,
                COALESCE(SUM(CASE WHEN mc.tipo IN ('gasto', 'egreso') AND NOT {$condicionReverso} THEN mc.monto ELSE 0 END), 0) as total_gastos_reales,
                COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN {$condicionReverso} THEN mc.monto ELSE 0 END), 0) as total_ingresos_netos,
                cc.monto_inicial +
                    COALESCE(SUM(CASE WHEN mc.tipo = 'ingreso' AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN mc.tipo IN ('gasto', 'egreso') AND mc.metodo_pago = 'efectivo' THEN mc.monto ELSE 0 END), 0) as efectivo_esperado
            FROM cortes_caja cc
            LEFT JOIN movimientos_caja mc ON cc.id = mc.corte_id AND mc.hotel_id = cc.hotel_id
            WHERE cc.hotel_id = ?
            AND cc.estado = 'abierto'
            GROUP BY cc.id
            LIMIT 1
        ", [$hotel_id]);
        
        return $stmt->fetch() ?: null;
    }
    
    /**
     * Obtener reservaciones de hoy
     */
    private function getReservacionesHoy() {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "
            SELECT 
                r.*,
                h.nombre_completo as huesped_nombre,
                h.telefono,
                GROUP_CONCAT(hab.numero ORDER BY hab.numero) as habitaciones
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
            AND (r.fecha_entrada = CURDATE() OR r.fecha_salida = CURDATE())
            AND r.estado IN ('confirmada', 'checked_in', 'checked_out')
            GROUP BY r.id
            ORDER BY r.fecha_entrada, r.hora_llegada_estimada
        ";
        
        $stmt = $db->query($sql, [$hotel_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener reservaciones de hoy con detalles (alias para compatibilidad)
     */
    private function getReservacionesHoyDetalladas() {
        return $this->getReservacionesHoy();
    }
    
    /**
     * Obtener próximas llegadas (check-ins pendientes de hoy)
     */
    private function getProximasLlegadas() {
        try {
            $db = Database::getInstance();
            $hotel_id = $this->hotelIdActual();
            
            $sql = "
                SELECT 
                    r.id,
                    r.huesped_id,
                    r.hora_llegada_estimada,
                    r.hora_entrada,
                    h.nombre_completo as huesped_nombre,
                    COALESCE(h.procedencia_ciudad, '') as procedencia_ciudad,
                    COALESCE(h.procedencia_estado, '') as procedencia_estado,
                    CONCAT_WS(', ', NULLIF(h.procedencia_ciudad, ''), NULLIF(h.procedencia_estado, '')) as procedencia,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    r.precio_total
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND r.fecha_entrada = CURDATE()
                AND r.estado = 'confirmada'
                GROUP BY r.id
                ORDER BY r.hora_llegada_estimada
                LIMIT 10
            ";
            
            $stmt = $db->query($sql, [$hotel_id]);
            
            if (!$stmt) {
                error_log("Error en getProximasLlegadas: consulta falló");
                return [];
            }
            
            return $stmt->fetchAll() ?: [];
            
        } catch (Exception $e) {
            error_log("Error en getProximasLlegadas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener próximas salidas (check-outs pendientes de hoy)
     */
    private function getProximasSalidas() {
        try {
            $db = Database::getInstance();
            $hotel_id = $this->hotelIdActual();
            
            $sql = "
                SELECT 
                    r.id,
                    r.huesped_id,
                    r.hora_entrada,
                    r.hora_salida,
                    h.nombre_completo as huesped_nombre,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    r.precio_total
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND r.fecha_salida = CURDATE()
                AND r.estado IN ('checked_in', 'checked_out')
                GROUP BY r.id
                ORDER BY CASE 
                    WHEN r.hora_salida IS NULL THEN 0 
                    ELSE 1 
                END, r.hora_salida DESC
                LIMIT 10
            ";
            
            $stmt = $db->query($sql, [$hotel_id]);
            
            if (!$stmt) {
                error_log("Error en getProximasSalidas: consulta falló");
                return [];
            }
            
            return $stmt->fetchAll() ?: [];
            
        } catch (Exception $e) {
            error_log("Error en getProximasSalidas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener datos para los gráficos
     */
    private function getDatosGraficos() {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        // Total de habitaciones activas
        $stmt = $db->query(
            "SELECT COUNT(*) as total FROM habitaciones WHERE hotel_id = ? AND activa = 1",
            [$hotel_id]
        );
        $totalHabitaciones = $stmt->fetch()['total'] ?? 0;
        
        // Ocupación semanal: para cada día cuenta habitaciones ACTIVAS ese día
        // (fecha_entrada <= día Y fecha_salida > día), no solo las que entraron.
        $ocupacionSemanal = [];
        $diasSemana = ['Sunday' => 'Dom', 'Monday' => 'Lun', 'Tuesday' => 'Mar',
                       'Wednesday' => 'Mié', 'Thursday' => 'Jue', 'Friday' => 'Vie', 'Saturday' => 'Sáb'];
        
        for ($i = 6; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-$i days"));
            $diaNombre = date('l', strtotime($fecha));
            
            $stmtDia = $db->query("
                SELECT COUNT(DISTINCT rh.habitacion_id) as ocupadas
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id AND h.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND h.activa = 1
                  AND r.estado IN ('checked_in', 'checked_out', 'reservada')
                  AND DATE(r.fecha_entrada) <= ?
                  AND DATE(r.fecha_salida)  >  ?
            ", [$hotel_id, $fecha, $fecha]);
            
            $ocupadas = $stmtDia ? (int)($stmtDia->fetch()['ocupadas'] ?? 0) : 0;
            
            $ocupacionSemanal[] = [
                'fecha'       => $fecha,
                'dia'         => $diasSemana[$diaNombre],
                'ocupadas'    => $ocupadas,
                'disponibles' => $totalHabitaciones - $ocupadas
            ];
        }
        
        // Ingresos por tipo de habitación este mes
        $stmt = $db->query("
            SELECT 
                h.tipo,
                COUNT(DISTINCT r.id) as reservaciones,
                SUM(rh.precio) as ingresos
            FROM reservacion_habitaciones rh
            INNER JOIN habitaciones h ON rh.habitacion_id = h.id AND h.hotel_id = rh.hotel_id
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND r.hotel_id = rh.hotel_id
            WHERE rh.hotel_id = ?
            AND MONTH(r.created_at) = MONTH(CURDATE())
            AND YEAR(r.created_at) = YEAR(CURDATE())
            AND r.estado != 'cancelada'
            GROUP BY h.tipo
            ORDER BY ingresos DESC
        ", [$hotel_id]);
        
        $ingresosPorTipo = [];
        while ($row = $stmt->fetch()) {
            $ingresosPorTipo[] = [
                'tipo' => $row['tipo'],
                'reservaciones' => $row['reservaciones'],
                'ingresos' => floatval($row['ingresos'])
            ];
        }
        
        return [
            'ocupacion_semanal' => $ocupacionSemanal,
            'ingresos_por_tipo' => $ingresosPorTipo
        ];
    }
    
    /**
     * Obtener estructura de estadísticas vacías
     */
    private function getEstadisticasVacias() {
        return [
            'habitaciones' => [
                'total' => 0,
                'ocupadas' => 0,
                'disponibles' => 0,
                'disponibles_reales' => 0,
                'por_llegar' => 0,
                'limpieza' => 0,
                'mantenimiento' => 0,
                'porcentaje_ocupacion' => 0
            ],
            'ingresos' => [
                'total_dia' => 0,
                'efectivo_dia' => 0,
                'tarjeta_dia' => 0,
                'transferencia_dia' => 0
            ],
            'egresos' => [
                'total_dia' => 0,
                'efectivo_dia' => 0,
                'tarjeta_dia' => 0,
                'transferencia_dia' => 0
            ],
            'entradas' => [
                'total' => 0,
                'pendientes' => 0,
                'completadas' => 0
            ],
            'salidas' => [
                'total' => 0,
                'pendientes' => 0,
                'completadas' => 0
            ],
            'huespedes' => [
                'total' => 0,
                'habitaciones_ocupadas' => 0
            ]
        ];
    }
    
    /**
     * API para actualizar estadísticas (AJAX)
     */
    public function statsAction() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
        }
        
        $stats = $this->getEstadisticasCompletas();
        $caja_info = $this->getCajaInfo();
        
        View::renderJSON([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'caja' => $caja_info,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    /**
     * API para obtener datos de gráficos actualizados
     */
    public function chartsAction() {
        if (!$this->isAjax()) {
            $this->redirect('dashboard');
        }
        
        $datos = $this->getDatosGraficos();
        
        View::renderJSON([
            'success' => true,
            'data' => $datos,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
