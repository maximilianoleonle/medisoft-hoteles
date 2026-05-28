<?php
/**
 * Modelo de Reportes
 * Los Cedros
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class Reporte extends Model {
    protected $table = ''; // No usa tabla específica
    protected $timestamps = false;
    
    public function __construct() {
        // No llamar a parent::__construct() porque no necesitamos una tabla específica
        $this->db = Database::getInstance();
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function totalHabitacionesActivasHotel($hotel_id = null): int {
        $db = Database::getInstance();
        $hotel_id = $hotel_id ?? $this->hotelIdActual();

        $stmt = $db->query(
            "SELECT COUNT(*) as total FROM habitaciones WHERE hotel_id = ? AND activa = 1",
            [$hotel_id]
        );
        $resultado = $stmt->fetch();

        return (int)($resultado['total'] ?? 0);
    }
    
    /**
     * Obtener Ingresos vs Gastos
     */
    public function obtenerIngresosGastos($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        // Ingresos
        $sqlIngresos = "SELECT 
                        cm.nombre as categoria,
                        COUNT(mc.id) as cantidad,
                        SUM(mc.monto) as total
                        FROM movimientos_caja mc
                        LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
                        WHERE mc.tipo = 'ingreso'
                        AND DATE(mc.created_at) BETWEEN ? AND ?
                        GROUP BY cm.id, cm.nombre
                        ORDER BY total DESC";
        
        $stmtIngresos = $db->query($sqlIngresos, [$fecha_inicio, $fecha_fin]);
        $ingresos = $stmtIngresos->fetchAll();
        
        // Gastos
        $sqlGastos = "SELECT 
                      cm.nombre as categoria,
                      COUNT(mc.id) as cantidad,
                      SUM(mc.monto) as total
                      FROM movimientos_caja mc
                      LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
                      WHERE mc.tipo = 'gasto'
                      AND DATE(mc.created_at) BETWEEN ? AND ?
                      GROUP BY cm.id, cm.nombre
                      ORDER BY total DESC";
        
        $stmtGastos = $db->query($sqlGastos, [$fecha_inicio, $fecha_fin]);
        $gastos = $stmtGastos->fetchAll();
        
        return [
            'ingresos' => $ingresos,
            'gastos' => $gastos
        ];
    }
public function getIngresosVsGastos($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    // Obtener ingresos por categoría
    $sqlIngresos = "SELECT 
                        categoria,
                        COUNT(*) as cantidad,
                        SUM(monto) as total
                    FROM movimientos_caja 
                    WHERE hotel_id = ?
                    AND tipo = 'ingreso'
                    AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY categoria 
                    ORDER BY total DESC";
    
    $stmtIngresos = $db->query($sqlIngresos, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $ingresos = $stmtIngresos->fetchAll();
    
    // Obtener gastos por categoría
    $sqlGastos = "SELECT 
                      categoria,
                      COUNT(*) as cantidad,
                      SUM(monto) as total
                  FROM movimientos_caja 
                  WHERE hotel_id = ?
                  AND tipo = 'gasto'
                  AND DATE(created_at) BETWEEN ? AND ?
                  GROUP BY categoria 
                  ORDER BY total DESC";
    
    $stmtGastos = $db->query($sqlGastos, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $gastos = $stmtGastos->fetchAll();
    
    // Asegurar que siempre devolvamos arrays aunque estén vacíos
    return [
        'ingresos' => $ingresos ?: [],
        'gastos' => $gastos ?: []
    ];
}

// Método modificado para getResumenDiario con manejo de datos vacíos
public function getResumenDiario($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                DATE(created_at) as fecha,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as utilidad
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY fecha";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $resumen = $stmt->fetchAll();
    
    // Si no hay datos, crear un array con las fechas del período con valores en 0
    if (empty($resumen)) {
        $resumen = [];
        $fecha_actual = new DateTime($fecha_inicio);
        $fecha_final = new DateTime($fecha_fin);
        
        while ($fecha_actual <= $fecha_final) {
            $resumen[] = [
                'fecha' => $fecha_actual->format('Y-m-d'),
                'ingresos' => 0,
                'gastos' => 0,
                'utilidad' => 0
            ];
            $fecha_actual->modify('+1 day');
        }
    } else {
        // Llenar días faltantes con valores en 0
        $resumenCompleto = [];
        $fecha_actual = new DateTime($fecha_inicio);
        $fecha_final = new DateTime($fecha_fin);
        
        // Convertir el array a un mapa indexado por fecha
        $resumenMap = [];
        foreach ($resumen as $dia) {
            $resumenMap[$dia['fecha']] = $dia;
        }
        
        while ($fecha_actual <= $fecha_final) {
            $fechaStr = $fecha_actual->format('Y-m-d');
            if (isset($resumenMap[$fechaStr])) {
                $resumenCompleto[] = $resumenMap[$fechaStr];
            } else {
                $resumenCompleto[] = [
                    'fecha' => $fechaStr,
                    'ingresos' => 0,
                    'gastos' => 0,
                    'utilidad' => 0
                ];
            }
            $fecha_actual->modify('+1 day');
        }
        
        $resumen = $resumenCompleto;
    }
    
    return $resumen;
}

// Nuevo método para obtener datos agrupados por método de pago
public function getIngresosPorMetodoPago($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                metodo_pago,
                tipo,
                COUNT(*) as cantidad,
                SUM(monto) as total
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY metodo_pago, tipo
            ORDER BY metodo_pago, tipo";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $results = $stmt->fetchAll();
    
    // Estructurar los datos
    $metodosPago = [
        'efectivo' => ['ingresos' => 0, 'gastos' => 0, 'cantidad_ingresos' => 0, 'cantidad_gastos' => 0],
        'tarjeta' => ['ingresos' => 0, 'gastos' => 0, 'cantidad_ingresos' => 0, 'cantidad_gastos' => 0],
        'transferencia' => ['ingresos' => 0, 'gastos' => 0, 'cantidad_ingresos' => 0, 'cantidad_gastos' => 0]
    ];
    
    foreach ($results as $row) {
        $metodo = $row['metodo_pago'];
        $tipo = $row['tipo'];
        
        if (isset($metodosPago[$metodo])) {
            if ($tipo == 'ingreso') {
                $metodosPago[$metodo]['ingresos'] = floatval($row['total']);
                $metodosPago[$metodo]['cantidad_ingresos'] = intval($row['cantidad']);
            } else {
                $metodosPago[$metodo]['gastos'] = floatval($row['total']);
                $metodosPago[$metodo]['cantidad_gastos'] = intval($row['cantidad']);
            }
        }
    }
    
    // Calcular balances
    foreach ($metodosPago as &$metodo) {
        $metodo['balance'] = $metodo['ingresos'] - $metodo['gastos'];
        $metodo['cantidad_total'] = $metodo['cantidad_ingresos'] + $metodo['cantidad_gastos'];
    }
    
    return $metodosPago;
}

// Método para obtener movimientos de un usuario específico
public function getMovimientosPorUsuario($usuario_id, $fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                mc.*,
                cm.nombre as categoria_nombre,
                cm.icono,
                cm.color,
                u.nombre_completo as usuario_nombre
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            LEFT JOIN usuarios u ON mc.usuario_id = u.id
            WHERE mc.usuario_id = ? 
            AND mc.hotel_id = ?
            AND DATE(mc.created_at) BETWEEN ? AND ?
            ORDER BY mc.created_at DESC";
    
    $stmt = $db->query($sql, [$usuario_id, $hotel_id, $fecha_inicio, $fecha_fin]);
    return $stmt->fetchAll();
}

// Método para obtener resumen por usuario
public function getResumenPorUsuario($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                u.id,
                u.nombre_completo,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN mc.tipo = 'gasto' THEN mc.monto ELSE 0 END) as total_gastos,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE -mc.monto END) as balance,
                COUNT(CASE WHEN mc.tipo = 'ingreso' THEN 1 END) as cantidad_ingresos,
                COUNT(CASE WHEN mc.tipo = 'gasto' THEN 1 END) as cantidad_gastos,
                COUNT(*) as total_movimientos
            FROM usuarios u
            LEFT JOIN movimientos_caja mc ON u.id = mc.usuario_id 
                AND DATE(mc.created_at) BETWEEN ? AND ?
                AND mc.hotel_id = ?
            WHERE u.activo = 1
            GROUP BY u.id, u.nombre_completo
            HAVING total_movimientos > 0
            ORDER BY balance DESC";
    
    $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin, $hotel_id]);
    return $stmt->fetchAll();
}

// Método para obtener estadísticas avanzadas
public function getEstadisticasAvanzadas($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    // Hora pico de ingresos
    $sqlHoraPico = "SELECT 
                        HOUR(created_at) as hora,
                        COUNT(*) as cantidad,
                        SUM(monto) as total
                    FROM movimientos_caja
                    WHERE hotel_id = ?
                    AND tipo = 'ingreso'
                    AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY HOUR(created_at)
                    ORDER BY total DESC
                    LIMIT 1";
    
    $stmtHora = $db->query($sqlHoraPico, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $horaPico = $stmtHora->fetch();
    
    // Día de la semana más rentable
    $sqlDiaSemana = "SELECT 
                        DAYOFWEEK(created_at) as dia_semana,
                        COUNT(*) as cantidad,
                        SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                        SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos
                    FROM movimientos_caja
                    WHERE hotel_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DAYOFWEEK(created_at)
                    ORDER BY ingresos DESC";
    
    $stmtDia = $db->query($sqlDiaSemana, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $diasSemana = $stmtDia->fetchAll();
    
    // Tendencia de crecimiento
    $dias_periodo = ceil((strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400) + 1;
    $mitad = ceil($dias_periodo / 2);
    $fecha_mitad = date('Y-m-d', strtotime($fecha_inicio . ' + ' . $mitad . ' days'));
    
    $sqlTendencia = "SELECT 
                        SUM(CASE WHEN DATE(created_at) <= ? AND tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos_primera_mitad,
                        SUM(CASE WHEN DATE(created_at) > ? AND tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos_segunda_mitad
                    FROM movimientos_caja
                    WHERE hotel_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?";
    
    $stmtTendencia = $db->query($sqlTendencia, [$fecha_mitad, $fecha_mitad, $hotel_id, $fecha_inicio, $fecha_fin]);
    $tendencia = $stmtTendencia->fetch();
    
    return [
        'hora_pico' => $horaPico,
        'dias_semana' => $diasSemana,
        'tendencia' => $tendencia,
        'crecimiento_porcentaje' => $tendencia['ingresos_primera_mitad'] > 0 
            ? (($tendencia['ingresos_segunda_mitad'] - $tendencia['ingresos_primera_mitad']) / $tendencia['ingresos_primera_mitad']) * 100 
            : 0
    ];
}

// Método para obtener transacciones más grandes
public function getTransaccionesMayores($fecha_inicio, $fecha_fin, $limit = 10) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                mc.*,
                u.nombre_completo as usuario_nombre,
                cm.nombre as categoria_nombre
            FROM movimientos_caja mc
            LEFT JOIN usuarios u ON mc.usuario_id = u.id
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.hotel_id = ?
            AND DATE(mc.created_at) BETWEEN ? AND ?
            ORDER BY mc.monto DESC
            LIMIT ?";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin, $limit]);
    return $stmt->fetchAll();
}

// Método para obtener comparación entre períodos
public function getComparacionPeriodos($fecha_inicio_actual, $fecha_fin_actual, $fecha_inicio_anterior, $fecha_fin_anterior) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                'actual' as periodo,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos,
                COUNT(CASE WHEN tipo = 'ingreso' THEN 1 END) as cantidad_ingresos,
                COUNT(CASE WHEN tipo = 'gasto' THEN 1 END) as cantidad_gastos
            FROM movimientos_caja
            WHERE hotel_id = ?
            AND DATE(created_at) BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'anterior' as periodo,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos,
                COUNT(CASE WHEN tipo = 'ingreso' THEN 1 END) as cantidad_ingresos,
                COUNT(CASE WHEN tipo = 'gasto' THEN 1 END) as cantidad_gastos
            FROM movimientos_caja
            WHERE hotel_id = ?
            AND DATE(created_at) BETWEEN ? AND ?";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio_actual, $fecha_fin_actual, $hotel_id, $fecha_inicio_anterior, $fecha_fin_anterior]);
    $results = $stmt->fetchAll();
    
    $comparacion = [];
    foreach ($results as $row) {
        $comparacion[$row['periodo']] = $row;
    }
    
    // Calcular variaciones
    if (isset($comparacion['actual']) && isset($comparacion['anterior'])) {
        $comparacion['variacion'] = [
            'ingresos' => $comparacion['anterior']['ingresos'] > 0 
                ? (($comparacion['actual']['ingresos'] - $comparacion['anterior']['ingresos']) / $comparacion['anterior']['ingresos']) * 100 
                : 0,
            'gastos' => $comparacion['anterior']['gastos'] > 0 
                ? (($comparacion['actual']['gastos'] - $comparacion['anterior']['gastos']) / $comparacion['anterior']['gastos']) * 100 
                : 0,
            'cantidad_ingresos' => $comparacion['anterior']['cantidad_ingresos'] > 0 
                ? (($comparacion['actual']['cantidad_ingresos'] - $comparacion['anterior']['cantidad_ingresos']) / $comparacion['anterior']['cantidad_ingresos']) * 100 
                : 0,
            'cantidad_gastos' => $comparacion['anterior']['cantidad_gastos'] > 0 
                ? (($comparacion['actual']['cantidad_gastos'] - $comparacion['anterior']['cantidad_gastos']) / $comparacion['anterior']['cantidad_gastos']) * 100 
                : 0
        ];
    }
    
    return $comparacion;
}
    /**
     * Obtener estadísticas generales de mantenimiento
     */
    public function obtenerEstadisticasMantenimiento($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                COUNT(*) as total_mantenimientos,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                SUM(COALESCE(costo, 0)) as costo_total,
                AVG(CASE WHEN costo IS NOT NULL THEN costo END) as costo_promedio,
                AVG(CASE 
                    WHEN estado = 'completado' AND fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as duracion_promedio_horas,
                MIN(fecha_inicio) as primer_mantenimiento,
                MAX(fecha_inicio) as ultimo_mantenimiento
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetch() ?: [
            'total_mantenimientos' => 0,
            'en_proceso' => 0,
            'completados' => 0,
            'cancelados' => 0,
            'costo_total' => 0,
            'costo_promedio' => 0,
            'duracion_promedio_horas' => 0
        ];
    }

    /**
     * Obtener mantenimientos por tipo
     */
    public function obtenerMantenimientosPorTipo($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                tipo_mantenimiento,
                COUNT(*) as cantidad,
                SUM(COALESCE(costo, 0)) as costo_total,
                AVG(CASE WHEN costo IS NOT NULL THEN costo END) as costo_promedio,
                AVG(CASE WHEN estado = 'completado' AND fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as duracion_promedio,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY tipo_mantenimiento
                ORDER BY cantidad DESC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener mantenimientos por prioridad
     */
    public function obtenerMantenimientosPorPrioridad($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                prioridad,
                COUNT(*) as cantidad,
                AVG(CASE WHEN estado = 'completado' AND fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as duracion_promedio,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                ROUND(SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as tasa_completitud
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY prioridad
                ORDER BY FIELD(prioridad, 'urgente', 'alta', 'media', 'baja')";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener habitaciones con más mantenimientos
     */
    public function obtenerHabitacionesConMasMantenimientos($fecha_inicio, $fecha_fin, $limite = 10) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                h.id,
                h.numero,
                h.tipo,
                h.piso,
                COUNT(m.id) as total_mantenimientos,
                SUM(COALESCE(m.costo, 0)) as costo_total,
                SUM(CASE WHEN m.tipo_mantenimiento = 'emergencia' THEN 1 ELSE 0 END) as emergencias,
                SUM(CASE WHEN m.tipo_mantenimiento = 'correctivo' THEN 1 ELSE 0 END) as correctivos,
                SUM(CASE WHEN m.tipo_mantenimiento = 'preventivo' THEN 1 ELSE 0 END) as preventivos,
                MAX(m.fecha_inicio) as ultimo_mantenimiento
                FROM habitaciones h
                INNER JOIN mantenimientos_habitaciones m ON h.id = m.habitacion_id
                WHERE DATE(m.fecha_inicio) BETWEEN ? AND ?
                GROUP BY h.id, h.numero, h.tipo, h.piso
                ORDER BY total_mantenimientos DESC, costo_total DESC
                LIMIT ?";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin, $limite]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener tendencia mensual de mantenimientos
     */
    public function obtenerTendenciaMantenimientos($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                DATE_FORMAT(fecha_inicio, '%Y-%m') as mes,
                COUNT(*) as total,
                SUM(CASE WHEN tipo_mantenimiento = 'preventivo' THEN 1 ELSE 0 END) as preventivos,
                SUM(CASE WHEN tipo_mantenimiento = 'correctivo' THEN 1 ELSE 0 END) as correctivos,
                SUM(CASE WHEN tipo_mantenimiento = 'emergencia' THEN 1 ELSE 0 END) as emergencias,
                SUM(CASE WHEN tipo_mantenimiento = 'limpieza_profunda' THEN 1 ELSE 0 END) as limpiezas,
                SUM(COALESCE(costo, 0)) as costo_total
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(fecha_inicio, '%Y-%m')
                ORDER BY mes";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener distribución de costos
     */
    public function obtenerDistribucionCostos($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                CASE 
                    WHEN costo IS NULL OR costo = 0 THEN 'Sin costo'
                    WHEN costo < 100 THEN '$0 - $99'
                    WHEN costo < 500 THEN '$100 - $499'
                    WHEN costo < 1000 THEN '$500 - $999'
                    WHEN costo < 5000 THEN '$1,000 - $4,999'
                    ELSE '$5,000+'
                END as rango_costo,
                COUNT(*) as cantidad,
                SUM(costo) as costo_total
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND estado = 'completado'
                GROUP BY rango_costo
                ORDER BY 
                    CASE rango_costo
                        WHEN 'Sin costo' THEN 1
                        WHEN '$0 - $99' THEN 2
                        WHEN '$100 - $499' THEN 3
                        WHEN '$500 - $999' THEN 4
                        WHEN '$1,000 - $4,999' THEN 5
                        ELSE 6
                    END";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener tiempo de respuesta por prioridad
     */
    public function obtenerTiempoRespuestaPorPrioridad($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                prioridad,
                COUNT(*) as total,
                AVG(CASE WHEN fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as horas_promedio,
                MIN(CASE WHEN fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as horas_minimo,
                MAX(CASE WHEN fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as horas_maximo
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                AND estado = 'completado'
                GROUP BY prioridad
                ORDER BY FIELD(prioridad, 'urgente', 'alta', 'media', 'baja')";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener mantenimientos por personal
     */
    public function obtenerMantenimientosPorPersonal($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                COALESCE(realizado_por, 'No especificado') as personal,
                COUNT(*) as total_trabajos,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                AVG(CASE WHEN estado = 'completado' AND fecha_fin IS NOT NULL 
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) END) as horas_promedio,
                SUM(COALESCE(costo, 0)) as costo_total
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY realizado_por
                ORDER BY total_trabajos DESC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener análisis por tipo de habitación
     */
    public function obtenerMantenimientosPorTipoHabitacion($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                h.tipo as tipo_habitacion,
                COUNT(m.id) as total_mantenimientos,
                COUNT(DISTINCT h.id) as habitaciones_afectadas,
                SUM(COALESCE(m.costo, 0)) as costo_total,
                AVG(CASE WHEN m.costo IS NOT NULL THEN m.costo END) as costo_promedio,
                SUM(CASE WHEN m.tipo_mantenimiento = 'emergencia' THEN 1 ELSE 0 END) as emergencias,
                ROUND(COUNT(m.id) * 100.0 / (
                    SELECT COUNT(*) 
                    FROM mantenimientos_habitaciones 
                    WHERE DATE(created_at) BETWEEN ? AND ?
                ), 1) as porcentaje_del_total
                FROM habitaciones h
                INNER JOIN mantenimientos_habitaciones m ON h.id = m.habitacion_id
                WHERE DATE(m.fecha_inicio) BETWEEN ? AND ?
                GROUP BY h.tipo
                ORDER BY total_mantenimientos DESC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener eficiencia de mantenimiento
     */
    public function obtenerEficienciaMantenimiento($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                COUNT(*) as total_mantenimientos,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados,
                ROUND(SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as tasa_completitud,
                AVG(CASE 
                    WHEN estado = 'completado' AND fecha_fin IS NOT NULL AND prioridad = 'urgente'
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) 
                END) as tiempo_resolucion_urgentes,
                AVG(CASE 
                    WHEN estado = 'completado' AND fecha_fin IS NOT NULL AND prioridad = 'alta'
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin) 
                END) as tiempo_resolucion_altas
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetch() ?: [
            'total_mantenimientos' => 0,
            'completados' => 0,
            'en_proceso' => 0,
            'cancelados' => 0,
            'tasa_completitud' => 0,
            'tiempo_resolucion_urgentes' => 0,
            'tiempo_resolucion_altas' => 0
        ];
    }
    
    /**
     * Obtener resumen diario
     */
    public function obtenerResumenDiario($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DATE(created_at) as fecha,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as utilidad
                FROM movimientos_caja
                WHERE hotel_id = ?
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY fecha";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener resumen por categoría
     */
    public function obtenerResumenPorCategoria($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                cm.nombre as categoria,
                cm.tipo,
                COUNT(mc.id) as movimientos,
                SUM(mc.monto) as total,
                AVG(mc.monto) as promedio
                FROM movimientos_caja mc
                LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
                WHERE mc.hotel_id = ?
                AND DATE(mc.created_at) BETWEEN ? AND ?
                GROUP BY cm.id, cm.nombre, cm.tipo
                ORDER BY cm.tipo, total DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener procedencia por estado
     */
    public function obtenerProcedenciaPorEstado($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.procedencia_estado as estado,
                COUNT(DISTINCT h.id) as total_huespedes,
                COUNT(DISTINCT r.id) as total_reservaciones,
                COUNT(DISTINCT rh.habitacion_id) as habitaciones_ocupadas,
                SUM(r.precio_total) as ingresos_totales
                FROM huespedes h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY h.procedencia_estado
                ORDER BY total_huespedes DESC";
        
        try {
            $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
            if ($stmt === false) {
                // Mostrar el error SQL
                $errorInfo = $db->errorInfo();
                die("Error SQL: " . print_r($errorInfo, true));
            }
            return $stmt->fetchAll();
        } catch (Exception $e) {
            die("Error en la consulta: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener procedencia por ciudad
     */
    public function obtenerProcedenciaPorCiudad($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.procedencia_estado as estado,
                h.procedencia_ciudad as ciudad,
                COUNT(DISTINCT h.id) as total_huespedes,
                SUM(r.precio_total) as ingresos_totales
                FROM huespedes h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                AND h.procedencia_ciudad IS NOT NULL
                GROUP BY h.procedencia_estado, h.procedencia_ciudad
                ORDER BY total_huespedes DESC
                LIMIT 20";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener evolución de procedencia
     */
    public function obtenerEvolucionProcedencia($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%Y-%m') as mes,
                h.procedencia_estado as estado,
                COUNT(DISTINCT h.id) as total_huespedes
                FROM huespedes h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY mes, h.procedencia_estado
                ORDER BY mes, total_huespedes DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener rentabilidad de habitaciones
     */
    public function obtenerRentabilidadHabitaciones($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.id,
                h.numero,
                h.tipo,
                h.piso,
                COUNT(DISTINCT r.id) as total_reservaciones,
                COUNT(DISTINCT DATE(r.fecha_entrada)) as dias_ocupada,
                SUM(rh.precio) as ingresos_totales,
                AVG(rh.precio) as precio_promedio,
                ROUND(COUNT(DISTINCT DATE(r.fecha_entrada)) * 100.0 / DATEDIFF(?, ?), 2) as porcentaje_ocupacion
                FROM habitaciones h
                LEFT JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                    AND rh.hotel_id = h.hotel_id
                LEFT JOIN reservaciones r ON rh.reservacion_id = r.id
                    AND r.hotel_id = h.hotel_id
                WHERE h.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado = 'checked_out'
                GROUP BY h.id, h.numero, h.tipo, h.piso
                ORDER BY ingresos_totales DESC";
        
        $stmt = $db->query($sql, [$fecha_fin, $fecha_inicio, $hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ocupación por tipo de habitación
     */
    public function obtenerOcupacionPorTipo($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.tipo,
                COUNT(DISTINCT h.id) as total_habitaciones,
                COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) as dias_ocupadas,
                SUM(rh.precio) as ingresos_totales,
                AVG(rh.precio) as precio_promedio
                FROM habitaciones h
                LEFT JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                    AND rh.hotel_id = h.hotel_id
                LEFT JOIN reservaciones r ON rh.reservacion_id = r.id
                    AND r.hotel_id = h.hotel_id
                WHERE h.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY h.tipo
                ORDER BY ingresos_totales DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ingreso promedio por habitación
     */
    public function obtenerIngresoPromedioPorHabitacion($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.tipo,
                AVG(rh.precio) as precio_promedio,
                MIN(rh.precio) as precio_minimo,
                MAX(rh.precio) as precio_maximo,
                COUNT(*) as total_reservaciones
                FROM habitaciones h
                INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                    AND rh.hotel_id = h.hotel_id
                INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                    AND r.hotel_id = h.hotel_id
                WHERE h.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado = 'checked_out'
                GROUP BY h.tipo";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ocupación diaria
     */
    public function obtenerOcupacionDiaria($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        $total_habitaciones_activas = $this->totalHabitacionesActivasHotel($hotel_id);
        
        $sql = "SELECT 
                fecha,
                COUNT(DISTINCT habitacion_id) as habitaciones_ocupadas,
                CASE
                    WHEN ? > 0 THEN ROUND(COUNT(DISTINCT habitacion_id) * 100.0 / ?, 2)
                    ELSE 0
                END as porcentaje_ocupacion
                FROM (
                    SELECT 
                        DATE(r.fecha_entrada) as fecha,
                        rh.habitacion_id
                    FROM reservaciones r
                    INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                        AND rh.hotel_id = r.hotel_id
                    WHERE r.hotel_id = ?
                    AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                    AND r.estado NOT IN ('cancelada', 'no_show')
                ) as ocupacion
                GROUP BY fecha
                ORDER BY fecha";
        
        $stmt = $db->query($sql, [
            $total_habitaciones_activas,
            $total_habitaciones_activas,
            $hotel_id,
            $fecha_inicio,
            $fecha_fin
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ocupación semanal
     */
    public function obtenerOcupacionSemanal($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        $total_habitaciones_activas = $this->totalHabitacionesActivasHotel($hotel_id);
        
        $sql = "SELECT 
                YEARWEEK(r.fecha_entrada, 1) as semana,
                MIN(DATE(r.fecha_entrada)) as fecha_inicio_semana,
                MAX(DATE(r.fecha_entrada)) as fecha_fin_semana,
                COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) as habitaciones_ocupadas_dias,
                CASE
                    WHEN ? > 0 THEN ROUND(COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) * 100.0 / (? * 7), 2)
                    ELSE 0
                END as porcentaje_ocupacion
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY YEARWEEK(r.fecha_entrada, 1)
                ORDER BY semana";
        
        $stmt = $db->query($sql, [
            $total_habitaciones_activas,
            $total_habitaciones_activas,
            $hotel_id,
            $fecha_inicio,
            $fecha_fin
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ocupación mensual
     */
    public function obtenerOcupacionMensual($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        $total_habitaciones_activas = $this->totalHabitacionesActivasHotel($hotel_id);
        
        $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%Y-%m') as mes,
                COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) as habitaciones_ocupadas_dias,
                CASE
                    WHEN ? > 0 THEN ROUND(COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) * 100.0 /
                        (? * DAY(LAST_DAY(r.fecha_entrada))), 2)
                    ELSE 0
                END as porcentaje_ocupacion
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY DATE_FORMAT(r.fecha_entrada, '%Y-%m')
                ORDER BY mes";
        
        $stmt = $db->query($sql, [
            $total_habitaciones_activas,
            $total_habitaciones_activas,
            $hotel_id,
            $fecha_inicio,
            $fecha_fin
        ]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas de ocupación
     */
    public function obtenerEstadisticasOcupacion($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        $total_habitaciones_activas = $this->totalHabitacionesActivasHotel($hotel_id);
        
        // Total de días en el período
        $dias_periodo = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400 + 1;
        $habitaciones_disponibles = $total_habitaciones_activas > 0
            ? $total_habitaciones_activas * $dias_periodo
            : 0;
        
        // Habitaciones ocupadas
        $sql = "SELECT 
                COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) as habitaciones_ocupadas_dias
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        $resultado = $stmt->fetch();
        
        $habitaciones_ocupadas = (int)($resultado['habitaciones_ocupadas_dias'] ?? 0);
        $porcentaje_ocupacion = $habitaciones_disponibles > 0
            ? round(($habitaciones_ocupadas / $habitaciones_disponibles) * 100, 2)
            : 0;
        
        return [
            'dias_periodo' => $dias_periodo,
            'habitaciones_disponibles' => $habitaciones_disponibles,
            'habitaciones_ocupadas' => $habitaciones_ocupadas,
            'porcentaje_ocupacion' => $porcentaje_ocupacion
        ];
    }
    
    /**
     * Obtener ocupación por día de la semana
     */
    public function obtenerOcupacionPorDiaSemana($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DAYOFWEEK(fecha_entrada) as dia_semana,
                CASE DAYOFWEEK(fecha_entrada)
                    WHEN 1 THEN 'Domingo'
                    WHEN 2 THEN 'Lunes'
                    WHEN 3 THEN 'Martes'
                    WHEN 4 THEN 'Miércoles'
                    WHEN 5 THEN 'Jueves'
                    WHEN 6 THEN 'Viernes'
                    WHEN 7 THEN 'Sábado'
                END as nombre_dia,
                COUNT(DISTINCT CONCAT(rh.habitacion_id, DATE(r.fecha_entrada))) as habitaciones_ocupadas,
                ROUND(AVG(rh.precio), 2) as precio_promedio
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY DAYOFWEEK(fecha_entrada)
                ORDER BY dia_semana";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener promedio de estancia general
     */
    public function obtenerPromedioEstancia($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                AVG(DATEDIFF(fecha_salida, fecha_entrada)) as promedio_dias,
                MIN(DATEDIFF(fecha_salida, fecha_entrada)) as estancia_minima,
                MAX(DATEDIFF(fecha_salida, fecha_entrada)) as estancia_maxima,
                COUNT(*) as total_reservaciones
                FROM reservaciones
                WHERE hotel_id = ?
                AND fecha_entrada BETWEEN ? AND ?
                AND estado = 'checked_out'
                AND fecha_salida IS NOT NULL";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener estancia por tipo de habitación
     */
    public function obtenerEstanciaPorTipo($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.tipo,
                AVG(DATEDIFF(r.fecha_salida, r.fecha_entrada)) as promedio_dias,
                COUNT(DISTINCT r.id) as total_reservaciones
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id
                    AND h.hotel_id = rh.hotel_id
                WHERE r.hotel_id = ?
                AND r.fecha_entrada BETWEEN ? AND ?
                AND r.estado = 'checked_out'
                AND r.fecha_salida IS NOT NULL
                GROUP BY h.tipo
                ORDER BY promedio_dias DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estancia por procedencia
     */
    public function obtenerEstanciaPorProcedencia($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.procedencia_estado as estado,
                AVG(DATEDIFF(r.fecha_salida, r.fecha_entrada)) as promedio_dias,
                COUNT(DISTINCT r.id) as total_reservaciones
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE r.hotel_id = ?
                AND r.fecha_entrada BETWEEN ? AND ?
                AND r.estado = 'checked_out'
                AND r.fecha_salida IS NOT NULL
                GROUP BY h.procedencia_estado
                HAVING total_reservaciones >= 5
                ORDER BY promedio_dias DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener distribución de estancia
     */
    public function obtenerDistribucionEstancia($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DATEDIFF(fecha_salida, fecha_entrada) as dias_estancia,
                COUNT(*) as cantidad
                FROM reservaciones
                WHERE hotel_id = ?
                AND fecha_entrada BETWEEN ? AND ?
                AND estado = 'checked_out'
                AND fecha_salida IS NOT NULL
                GROUP BY dias_estancia
                ORDER BY dias_estancia";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener tendencia de estancia mensual
     */
    public function obtenerTendenciaEstancia($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DATE_FORMAT(fecha_entrada, '%Y-%m') as mes,
                AVG(DATEDIFF(fecha_salida, fecha_entrada)) as promedio_dias,
                COUNT(*) as total_reservaciones
                FROM reservaciones
                WHERE hotel_id = ?
                AND fecha_entrada BETWEEN ? AND ?
                AND estado = 'checked_out'
                AND fecha_salida IS NOT NULL
                GROUP BY DATE_FORMAT(fecha_entrada, '%Y-%m')
                ORDER BY mes";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener ranking de estados
     */
    public function obtenerRankingEstados($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                h.procedencia_estado as estado,
                COUNT(DISTINCT h.id) as total_huespedes,
                COUNT(DISTINCT r.id) as total_reservaciones,
                SUM(r.precio_total) as ingresos_totales,
                AVG(r.precio_total) as ticket_promedio,
                AVG(DATEDIFF(r.fecha_salida, r.fecha_entrada)) as estancia_promedio,
                ROUND(COUNT(DISTINCT r.id) * 100.0 / 
                    (SELECT COUNT(*) FROM reservaciones 
                     WHERE hotel_id = ?
                     AND fecha_entrada BETWEEN ? AND ?
                     AND estado NOT IN ('cancelada', 'no_show')), 2) as porcentaje_del_total
                FROM huespedes h
                INNER JOIN reservaciones r ON h.id = r.huesped_id
                WHERE r.hotel_id = ?
                AND DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY h.procedencia_estado
                ORDER BY total_reservaciones DESC";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin, $hotel_id, $fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener evolución de estados
     */
    public function obtenerEvolucionEstados($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                DATE_FORMAT(r.fecha_entrada, '%Y-%m') as mes,
                h.procedencia_estado as estado,
                COUNT(DISTINCT r.id) as total_reservaciones
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE DATE(r.fecha_entrada) BETWEEN ? AND ?
                AND r.estado NOT IN ('cancelada', 'no_show')
                GROUP BY mes, h.procedencia_estado
                ORDER BY mes, total_reservaciones DESC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener comparativa de estados
     */
    public function obtenerComparativaEstados($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        // Obtener período anterior
        $dias_diferencia = (strtotime($fecha_fin) - strtotime($fecha_inicio)) / 86400;
        $fecha_inicio_anterior = date('Y-m-d', strtotime($fecha_inicio . " -$dias_diferencia days"));
        $fecha_fin_anterior = date('Y-m-d', strtotime($fecha_fin . " -$dias_diferencia days"));
        
        $sql = "SELECT 
                estados.estado,
                COALESCE(actual.total, 0) as total_actual,
                COALESCE(anterior.total, 0) as total_anterior,
                CASE 
                    WHEN COALESCE(anterior.total, 0) = 0 THEN 100
                    ELSE ROUND((COALESCE(actual.total, 0) - COALESCE(anterior.total, 0)) * 100.0 / COALESCE(anterior.total, 1), 2)
                END as variacion_porcentaje
                FROM (
                    SELECT DISTINCT procedencia_estado as estado 
                    FROM huespedes 
                    WHERE procedencia_estado IS NOT NULL
                ) estados
                LEFT JOIN (
                    SELECT h.procedencia_estado as estado, COUNT(DISTINCT r.id) as total
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    WHERE DATE(r.fecha_entrada) BETWEEN ? AND ?
                    AND r.estado NOT IN ('cancelada', 'no_show')
                    GROUP BY h.procedencia_estado
                ) actual ON estados.estado = actual.estado
                LEFT JOIN (
                    SELECT h.procedencia_estado as estado, COUNT(DISTINCT r.id) as total
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    WHERE DATE(r.fecha_entrada) BETWEEN ? AND ?
                    AND r.estado NOT IN ('cancelada', 'no_show')
                    GROUP BY h.procedencia_estado
                ) anterior ON estados.estado = anterior.estado
                WHERE COALESCE(actual.total, 0) > 0 OR COALESCE(anterior.total, 0) > 0
                ORDER BY total_actual DESC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin, $fecha_inicio_anterior, $fecha_fin_anterior]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener datos para gráfica de ingresos vs gastos
     */
    public function obtenerDatosGraficaIngresosGastos($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                DATE(created_at) as fecha,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos
                FROM movimientos_caja
                WHERE hotel_id = ?
                AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY fecha";
        
        $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
        $datos = $stmt->fetchAll();
        
        return [
            'labels' => array_column($datos, 'fecha'),
            'ingresos' => array_column($datos, 'ingresos'),
            'gastos' => array_column($datos, 'gastos')
        ];
    }
    
    /**
     * Obtener datos para gráfica de ocupación
     */
    public function obtenerDatosGraficaOcupacion($fecha_inicio, $fecha_fin) {
        $datos = $this->obtenerOcupacionDiaria($fecha_inicio, $fecha_fin);
        
        return [
            'labels' => array_column($datos, 'fecha'),
            'ocupacion' => array_column($datos, 'porcentaje_ocupacion')
        ];
    }
    
    /**
     * Obtener datos para gráfica de procedencia
     */
    public function obtenerDatosGraficaProcedencia($fecha_inicio, $fecha_fin) {
        $datos = $this->obtenerProcedenciaPorEstado($fecha_inicio, $fecha_fin);
        $top10 = array_slice($datos, 0, 10);
        
        return [
            'labels' => array_column($top10, 'estado'),
            'valores' => array_column($top10, 'total_huespedes')
        ];
    }
    
    /**
     * Generar PDF del reporte
     */
    public function generarPDF($tipo, $fecha_inicio, $fecha_fin) {
        require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Los Cedros');
        $pdf->SetAuthor('Sistema de Gestión Hotelera');
        $pdf->SetTitle('Reporte ' . ucfirst(str_replace('-', ' ', $tipo)));
        
        // Remover encabezado y pie de página por defecto
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Añadir página
        $pdf->AddPage();
        
        // Logo y encabezado
        $pdf->Image(__DIR__ . '/../../public/img/logo.png', 15, 10, 30, 30);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetXY(50, 15);
        $pdf->Cell(0, 10, 'Los Cedros', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetX(50);
        $pdf->Cell(0, 7, 'Santa Catarina Juquila, Oaxaca', 0, 1, 'L');
        
        // Título del reporte
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Reporte de ' . ucfirst(str_replace('-', ' ', $tipo)), 0, 1, 'C');
        
        // Período
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, 'Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
        
        $pdf->Ln(5);
        
        // Contenido según el tipo de reporte
        $this->generarContenidoPDF($pdf, $tipo, $fecha_inicio, $fecha_fin);
        
        // Pie de página
        $pdf->SetY(-20);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 5, 'Generado el ' . date('d/m/Y H:i:s'), 0, 0, 'L');
        $pdf->Cell(0, 5, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'R');
        
        // Salida del PDF
        $pdf->Output('reporte_' . $tipo . '_' . date('Y-m-d') . '.pdf', 'D');
    }
    
    /**
     * Generar contenido específico del PDF según el tipo
     */
    private function generarContenidoPDF($pdf, $tipo, $fecha_inicio, $fecha_fin) {
        switch($tipo) {
            case 'ingresos-gastos':
                $this->generarPDFIngresosGastos($pdf, $fecha_inicio, $fecha_fin);
                break;
            case 'procedencia':
                $this->generarPDFProcedencia($pdf, $fecha_inicio, $fecha_fin);
                break;
            case 'ocupacion':
                $this->generarPDFOcupacion($pdf, $fecha_inicio, $fecha_fin);
                break;
            case 'habitaciones-rentables':
                $this->generarPDFHabitacionesRentables($pdf, $fecha_inicio, $fecha_fin);
                break;
            case 'estancia':
                $this->generarPDFEstancia($pdf, $fecha_inicio, $fecha_fin);
                break;
            case 'ranking-estados':
                $this->generarPDFRankingEstados($pdf, $fecha_inicio, $fecha_fin);
                break;
        }
    }
    
    /**
     * Generar PDF de Ingresos vs Gastos
     */
    private function generarPDFIngresosGastos($pdf, $fecha_inicio, $fecha_fin) {
        $datos = $this->obtenerIngresosGastos($fecha_inicio, $fecha_fin);
        $totales = [
            'ingresos' => array_sum(array_column($datos['ingresos'], 'total')),
            'gastos' => array_sum(array_column($datos['gastos'], 'total')),
            'utilidad' => 0
        ];
        $totales['utilidad'] = $totales['ingresos'] - $totales['gastos'];
        
        // Resumen
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'RESUMEN GENERAL', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        
        $pdf->Cell(60, 6, 'Total Ingresos:', 1, 0, 'L');
        $pdf->Cell(0, 6, '$' . number_format($totales['ingresos'], 2), 1, 1, 'R');
        
        $pdf->Cell(60, 6, 'Total Gastos:', 1, 0, 'L');
        $pdf->Cell(0, 6, '$' . number_format($totales['gastos'], 2), 1, 1, 'R');
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 6, 'Utilidad:', 1, 0, 'L');
        $pdf->Cell(0, 6, '$' . number_format($totales['utilidad'], 2), 1, 1, 'R');
        
        $pdf->Ln(5);
        
        // Detalle de ingresos
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'DETALLE DE INGRESOS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($datos['ingresos'] as $ingreso) {
            $pdf->Cell(100, 5, $ingreso['categoria'], 0, 0, 'L');
            $pdf->Cell(30, 5, $ingreso['cantidad'] . ' movimientos', 0, 0, 'C');
            $pdf->Cell(0, 5, '$' . number_format($ingreso['total'], 2), 0, 1, 'R');
        }
        
        $pdf->Ln(3);
        
        // Detalle de gastos
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'DETALLE DE GASTOS', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($datos['gastos'] as $gasto) {
            $pdf->Cell(100, 5, $gasto['categoria'], 0, 0, 'L');
            $pdf->Cell(30, 5, $gasto['cantidad'] . ' movimientos', 0, 0, 'C');
            $pdf->Cell(0, 5, '$' . number_format($gasto['total'], 2), 0, 1, 'R');
        }
    }
    

    /**
     * Obtener registro completo de mantenimientos (para la tabla del reporte)
     * Sin filtro de fecha para mostrar siempre los últimos registros
     */
    public function obtenerRegistroMantenimientos($tipo_filtro = '') {
        $db = Database::getInstance();
        
        if ($tipo_filtro) {
            $sql = "SELECT m.*,
                    h.numero             AS habitacion_numero,
                    h.tipo               AS habitacion_tipo,
                    u.nombre_completo    AS usuario_nombre
                    FROM mantenimientos_habitaciones m
                    LEFT JOIN habitaciones h ON m.habitacion_id = h.id
                    LEFT JOIN usuarios    u ON m.usuario_registro_id = u.id
                    WHERE m.tipo_mantenimiento = ?
                    ORDER BY m.created_at DESC
                    LIMIT 50";
            $stmt = $db->query($sql, [$tipo_filtro]);
        } else {
            $sql = "SELECT m.*,
                    h.numero             AS habitacion_numero,
                    h.tipo               AS habitacion_tipo,
                    u.nombre_completo    AS usuario_nombre
                    FROM mantenimientos_habitaciones m
                    LEFT JOIN habitaciones h ON m.habitacion_id = h.id
                    LEFT JOIN usuarios    u ON m.usuario_registro_id = u.id
                    ORDER BY m.created_at DESC
                    LIMIT 50";
            $stmt = $db->query($sql, []);
        }
        
        if ($stmt === false) return [];
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener top responsables de mantenimientos
     */
    public function obtenerTopResponsablesMantenimiento($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                COALESCE(realizado_por, 'Sin asignar') AS realizado_por,
                COUNT(*) AS cantidad
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                  AND realizado_por IS NOT NULL
                  AND realizado_por <> ''
                GROUP BY realizado_por
                ORDER BY cantidad DESC
                LIMIT 8";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        if ($stmt === false) return [];
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Obtener tendencia mensual de mantenimientos (con label legible)
     */
    public function obtenerTendenciaMensualMantenimiento($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT
                DATE_FORMAT(fecha_inicio, '%Y-%m')  AS mes,
                DATE_FORMAT(fecha_inicio, '%b %Y')  AS mes_label,
                COUNT(*)                            AS cantidad,
                COALESCE(SUM(costo), 0)             AS costo_mes
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY mes, mes_label
                ORDER BY mes ASC";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        if ($stmt === false) return [];
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Estadísticas completas incluyendo programados
     */
    public function obtenerEstadisticasMantenimientoCompletas($fecha_inicio, $fecha_fin) {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                COUNT(*) AS total_mantenimientos,
                SUM(CASE WHEN estado = 'completado'  THEN 1 ELSE 0 END) AS completados,
                SUM(CASE WHEN estado = 'en_proceso'  THEN 1 ELSE 0 END) AS en_proceso,
                SUM(CASE WHEN estado = 'cancelado'   THEN 1 ELSE 0 END) AS cancelados,
                SUM(CASE WHEN estado = 'programado'  THEN 1 ELSE 0 END) AS programados,
                COALESCE(SUM(costo), 0)                                  AS costo_total,
                COALESCE(AVG(CASE WHEN costo IS NOT NULL THEN costo END), 0) AS costo_promedio,
                COALESCE(AVG(
                    CASE WHEN estado = 'completado' AND fecha_fin IS NOT NULL
                    THEN TIMESTAMPDIFF(HOUR, fecha_inicio, fecha_fin)
                    END
                ), 0) AS duracion_promedio_horas
                FROM mantenimientos_habitaciones
                WHERE DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        if ($stmt === false) return ['total_mantenimientos'=>0,'completados'=>0,'en_proceso'=>0,'cancelados'=>0,'programados'=>0,'costo_total'=>0,'costo_promedio'=>0,'duracion_promedio_horas'=>0];
        return $stmt->fetch() ?: ['total_mantenimientos'=>0,'completados'=>0,'en_proceso'=>0,'cancelados'=>0,'programados'=>0,'costo_total'=>0,'costo_promedio'=>0,'duracion_promedio_horas'=>0];
    }

    // Los métodos generarPDFProcedencia, generarPDFOcupacion, etc. permanecen igual
    // ya que no contienen referencias a las tablas problemáticas
}
?>
