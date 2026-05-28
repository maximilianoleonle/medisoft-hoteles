<?php
/**
 * Modelo de Caja
 * Hotel San Nicolás
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class Caja extends Model {
    protected $table = 'cajas';
    protected $fillable = [
        'hotel_id',
        'nombre',
        'ubicacion',
        'monto_inicial',
        'activa'
    ];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function obtenerCajaPorId($caja_id, $hotel_id = null) {
        $hotel_id = $hotel_id ?: $this->hotelIdActual();

        $sql = "SELECT *
                FROM cajas
                WHERE id = ?
                AND hotel_id = ?
                AND activa = 1
                LIMIT 1";

        $stmt = $this->db->query($sql, [$caja_id, $hotel_id]);
        return $stmt ? $stmt->fetch() : false;
    }

    public function obtenerCortePorId($corte_id, $hotel_id = null) {
        $hotel_id = $hotel_id ?: $this->hotelIdActual();

        $sql = "SELECT cc.*
                FROM cortes_caja cc
                INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
                WHERE cc.id = ?
                AND cc.hotel_id = ?
                LIMIT 1";

        $stmt = $this->db->query($sql, [$corte_id, $hotel_id]);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Obtener caja activa principal
     */
    public function obtenerCajaPrincipal() {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT *
                FROM cajas
                WHERE hotel_id = ?
                AND activa = 1
                LIMIT 1";
        $stmt = $db->query($sql, [$hotel_id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Verificar si hay un corte abierto
     */
    public function tieneCorteAbierto($caja_id = null) {
        $db = Database::getInstance();
        
        if (!$caja_id) {
            $cajaPrincipal = $this->obtenerCajaPrincipal();
            $caja_id = $cajaPrincipal ? $cajaPrincipal['id'] : null;
        }
        
        if (!$caja_id) return false;

        $hotel_id = $this->hotelIdActual();

        if (!$this->obtenerCajaPorId($caja_id, $hotel_id)) {
            return false;
        }
        
        $sql = "SELECT COUNT(*) as total FROM cortes_caja 
                WHERE caja_id = ?
                AND hotel_id = ?
                AND estado = 'abierto'";
        
        $stmt = $db->query($sql, [$caja_id, $hotel_id]);
        $result = $stmt->fetch();
        
        return $result['total'] > 0;
    }
    
    /**
     * Obtener corte actual abierto
     */
    public function obtenerCorteActual($caja_id = null) {
        $db = Database::getInstance();
        
        if (!$caja_id) {
            $cajaPrincipal = $this->obtenerCajaPrincipal();
            $caja_id = $cajaPrincipal ? $cajaPrincipal['id'] : null;
        }
        
        if (!$caja_id) return null;

        $hotel_id = $this->hotelIdActual();

        if (!$this->obtenerCajaPorId($caja_id, $hotel_id)) {
            return null;
        }
        
        $sql = "SELECT cc.*, u.nombre_completo as usuario_apertura
                FROM cortes_caja cc
                INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
                LEFT JOIN usuarios u ON cc.usuario_apertura_id = u.id
                WHERE cc.caja_id = ?
                AND cc.hotel_id = ?
                AND cc.estado = 'abierto'
                ORDER BY cc.id DESC LIMIT 1";
        
        $stmt = $db->query($sql, [$caja_id, $hotel_id]);
        return $stmt->fetch();
    }
    
    /**
     * Abrir caja
     */
    public function abrirCaja($caja_id, $monto_inicial, $usuario_id) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();

        if (!$this->obtenerCajaPorId($caja_id, $hotel_id)) {
            return ['success' => false, 'message' => 'Caja no encontrada para el hotel actual'];
        }
        
        // Verificar que no haya un corte abierto
        if ($this->tieneCorteAbierto($caja_id)) {
            return ['success' => false, 'message' => 'Ya existe un corte de caja abierto'];
        }
        
        // Crear nuevo corte
        $sql = "INSERT INTO cortes_caja 
                (hotel_id, caja_id, fecha_apertura, monto_inicial, usuario_apertura_id, estado)
                VALUES (?, ?, NOW(), ?, ?, 'abierto')";
        
        $stmt = $db->query($sql, [$hotel_id, $caja_id, $monto_inicial, $usuario_id]);
        
        if ($stmt) {
            $corte_id = $db->lastInsertId();
            return [
                'success' => true, 
                'corte_id' => $corte_id,
                'message' => 'Caja abierta exitosamente'
            ];
        }
        
        return ['success' => false, 'message' => 'Error al abrir la caja'];
    }
    
    /**
     * Obtener resumen de caja actual
     */
   public function obtenerResumenCaja($corte_id = null) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    if (!$corte_id) {
        $corteActual = $this->obtenerCorteActual();
        if (!$corteActual) return null;
        $corte_id = $corteActual['id'];
    }

    $corte = $this->obtenerCortePorId($corte_id, $hotel_id);
    if (!$corte) return null;
    
    // Obtener TODOS los movimientos del corte
    $sql = "SELECT 
            tipo,
            metodo_pago,
            categoria,
            SUM(monto) as total,
            COUNT(*) as cantidad
            FROM movimientos_caja 
            WHERE corte_id = ?
            AND hotel_id = ?
            GROUP BY tipo, metodo_pago, categoria";
    
    $stmt = $db->query($sql, [$corte_id, $hotel_id]);
    $movimientos = $stmt->fetchAll();
    
    // Inicializar totales
    $resumen = [
        'ingresos' => [
            'efectivo' => ['cantidad' => 0, 'total' => 0],
            'tarjeta' => ['cantidad' => 0, 'total' => 0],
            'transferencia' => ['cantidad' => 0, 'total' => 0],
            'total' => 0
        ],
        'gastos' => [
            'efectivo' => ['cantidad' => 0, 'total' => 0],
            'tarjeta' => ['cantidad' => 0, 'total' => 0],
            'transferencia' => ['cantidad' => 0, 'total' => 0],
            'total' => 0
        ]
    ];
    
    // Procesar movimientos
    foreach ($movimientos as $mov) {
        $tipo = $mov['tipo'];
        $metodo = $mov['metodo_pago'];
        $monto = floatval($mov['total']);
        $cantidad = intval($mov['cantidad']);
        
        if ($tipo == 'ingreso') {
            // Sumar ingresos
            if (isset($resumen['ingresos'][$metodo])) {
                $resumen['ingresos'][$metodo]['cantidad'] += $cantidad;
                $resumen['ingresos'][$metodo]['total'] += $monto;
                $resumen['ingresos']['total'] += $monto;
            }
        } elseif ($tipo == 'gasto') {
            // Sumar TODOS los gastos (incluyendo devoluciones)
            // Las devoluciones son gastos, NO se restan de ingresos
            if (isset($resumen['gastos'][$metodo])) {
                $resumen['gastos'][$metodo]['cantidad'] += $cantidad;
                $resumen['gastos'][$metodo]['total'] += $monto;
                $resumen['gastos']['total'] += $monto;
            }
        }
    }
    
    // Obtener monto inicial del corte
    $sql = "SELECT monto_inicial
            FROM cortes_caja
            WHERE id = ?
            AND hotel_id = ?";
    $stmt = $db->query($sql, [$corte_id, $hotel_id]);
    $corte = $stmt->fetch();
    
    $resumen['monto_inicial'] = floatval($corte['monto_inicial'] ?? 0);
    
    // Calcular efectivo en caja (solo efectivo físico)
    $resumen['efectivo_en_caja'] = $resumen['monto_inicial'] + 
                                   $resumen['ingresos']['efectivo']['total'] - 
                                   $resumen['gastos']['efectivo']['total'];
    
    // Balance general
    $resumen['balance_general'] = $resumen['ingresos']['total'] - $resumen['gastos']['total'];
    
    // Agregar información adicional
    $resumen['total_movimientos'] = array_sum([
        $resumen['ingresos']['efectivo']['cantidad'],
        $resumen['ingresos']['tarjeta']['cantidad'],
        $resumen['ingresos']['transferencia']['cantidad'],
        $resumen['gastos']['efectivo']['cantidad'],
        $resumen['gastos']['tarjeta']['cantidad'],
        $resumen['gastos']['transferencia']['cantidad']
    ]);
    
    return $resumen;
}
    
    /**
     * Obtener movimientos por categoría
     */
   public function obtenerMovimientosPorCategoria($corte_id, $tipo = null) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();

    if (!$this->obtenerCortePorId($corte_id, $hotel_id)) {
        return [];
    }
    
    $sql = "SELECT 
            cm.nombre as categoria,
            cm.icono,
            cm.color,
            COUNT(mc.id) as cantidad,
            SUM(mc.monto) as total
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.corte_id = ?
            AND mc.hotel_id = ?";
    
    $params = [$corte_id, $hotel_id];
    
    if ($tipo) {
        // Si se especifica 'gasto', incluir también 'egreso'
        if ($tipo == 'gasto') {
            $sql .= " AND mc.tipo IN ('gasto', 'egreso')";
        } elseif ($tipo == 'ingreso') {
            $sql .= " AND mc.tipo = ?";
            $params[] = $tipo;
        }
    }
    
    $sql .= " GROUP BY cm.id, cm.nombre, cm.icono, cm.color
              ORDER BY total DESC";
    
    $stmt = $db->query($sql, $params);
    return $stmt->fetchAll();
}
    
    /**
     * Cerrar caja con arqueo
     */
    public function cerrarCaja($corte_id, $efectivo_contado, $observaciones, $usuario_cierre_id) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        try {
            // Obtener resumen actual
            $resumen = $this->obtenerResumenCaja($corte_id);
            
            if (!$resumen) {
                return ['success' => false, 'message' => 'Corte no encontrado'];
            }
            
            // Calcular diferencia (solo sobre efectivo)
            $efectivo_esperado = $resumen['efectivo_en_caja'];
            $diferencia = $efectivo_contado - $efectivo_esperado;
            
            // Actualizar corte con totales por método
            $sql = "UPDATE cortes_caja SET 
                    fecha_cierre = NOW(),
                    total_ingresos_efectivo = ?,
                    total_ingresos_tarjeta = ?,
                    total_ingresos_transferencia = ?,
                    total_gastos_efectivo = ?,
                    total_gastos_tarjeta = ?,
                    total_gastos_transferencia = ?,
                    efectivo_esperado = ?,
                    efectivo_contado = ?,
                    diferencia = ?,
                    estado = 'cerrado',
                    observaciones = ?,
                    usuario_cierre_id = ?
                    WHERE id = ?
                    AND hotel_id = ?
                    AND estado = 'abierto'";
            
            $params = [
                $resumen['ingresos']['efectivo']['total'],
                $resumen['ingresos']['tarjeta']['total'],
                $resumen['ingresos']['transferencia']['total'],
                $resumen['gastos']['efectivo']['total'],
                $resumen['gastos']['tarjeta']['total'],
                $resumen['gastos']['transferencia']['total'],
                $efectivo_esperado,
                $efectivo_contado,
                $diferencia,
                $observaciones,
                $usuario_cierre_id,
                $corte_id,
                $hotel_id
            ];
            
            $result = $db->query($sql, $params);
            
            if ($result && $result->rowCount() > 0) {
                return [
                    'success' => true,
                    'diferencia' => $diferencia,
                    'resumen' => $resumen
                ];
            }
            
            return ['success' => false, 'message' => 'Error al cerrar el corte'];
            
        } catch (Exception $e) {
            error_log("Error en cerrarCaja: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener historial de cortes
     */
    public function obtenerHistorialCortes($caja_id = null, $limite = 30) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                cc.*,
                c.nombre as caja_nombre,
                ua.nombre_completo as usuario_apertura,
                uc.nombre_completo as usuario_cierre,
                (cc.total_ingresos_efectivo + cc.total_ingresos_tarjeta + cc.total_ingresos_transferencia) as total_ingresos,
                (cc.total_gastos_efectivo + cc.total_gastos_tarjeta + cc.total_gastos_transferencia) as total_gastos
                FROM cortes_caja cc
                INNER JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
                LEFT JOIN usuarios ua ON cc.usuario_apertura_id = ua.id
                LEFT JOIN usuarios uc ON cc.usuario_cierre_id = uc.id
                WHERE cc.hotel_id = ?";
        
        $params = [$hotel_id];
        
        if ($caja_id) {
            $sql .= " AND cc.caja_id = ?";
            $params[] = $caja_id;
        }
        
        $sql .= " ORDER BY cc.id DESC LIMIT ?";
        $params[] = $limite;
        
        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar si se puede registrar movimiento
     */
    public function puedeRegistrarMovimiento() {
        $corte = $this->obtenerCorteActual();
        return $corte && $corte['estado'] == 'abierto';
    }
    
    
    
    /**
     * Obtener estadísticas del mes
     */
    public function obtenerEstadisticasMes($mes = null, $año = null) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $mes = $mes ?: date('m');
        $año = $año ?: date('Y');
        
        $sql = "SELECT 
                COUNT(DISTINCT cc.id) as total_cortes,
                SUM(cc.total_ingresos_efectivo + cc.total_ingresos_tarjeta + cc.total_ingresos_transferencia) as total_ingresos,
                SUM(cc.total_gastos_efectivo + cc.total_gastos_tarjeta + cc.total_gastos_transferencia) as total_gastos,
                AVG(cc.diferencia) as promedio_diferencia,
                SUM(CASE WHEN cc.diferencia > 0 THEN cc.diferencia ELSE 0 END) as sobrantes,
                SUM(CASE WHEN cc.diferencia < 0 THEN ABS(cc.diferencia) ELSE 0 END) as faltantes
                FROM cortes_caja cc
                WHERE MONTH(cc.fecha_apertura) = ? 
                AND YEAR(cc.fecha_apertura) = ?
                AND cc.hotel_id = ?
                AND cc.estado = 'cerrado'";
        
        $stmt = $db->query($sql, [$mes, $año, $hotel_id]);
        $stats = $stmt->fetch();
        
        // Obtener días con más movimiento
        $sql = "SELECT 
                DATE(cc.fecha_apertura) as fecha,
                COUNT(mc.id) as total_movimientos,
                SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) as ingresos_dia
                FROM cortes_caja cc
                LEFT JOIN movimientos_caja mc ON cc.id = mc.corte_id AND mc.hotel_id = cc.hotel_id
                WHERE MONTH(cc.fecha_apertura) = ? 
                AND YEAR(cc.fecha_apertura) = ?
                AND cc.hotel_id = ?
                GROUP BY DATE(cc.fecha_apertura)
                ORDER BY ingresos_dia DESC
                LIMIT 5";
        
        $stmt = $db->query($sql, [$mes, $año, $hotel_id]);
        $stats['dias_top'] = $stmt->fetchAll();
        
        return $stats;
    }
    /**
 * Obtener ingresos del corte agrupados por tipo de habitación
 * Separa las cuentas de Sencilla Manolo, Doble Manolo, etc.
 */
/**
     * Obtener ingresos del corte agrupados por tipo de habitación
     * Separa: MANOLO (sencilla_manolo, doble_manolo) vs ELIA (sencilla, doble, cuádruple)
     * 
     * FIX: Usa mc.id para deduplicar - evita contar doble cuando
     * una reservación tiene múltiples habitaciones
     */
    /**
     * Habitaciones que pertenecen a Manolo (por nombre/número)
     * Si se agregan o quitan habitaciones, solo modificar este array
     */
    private $habitacionesManolo = [
        'TURQUESA', 'UVA', 'VINO', 'AMBAR', 'CHOCOLATE', 
        'CORAL', 'GRIS', 'MAGENTA', 'MENTA'
    ];

   /**
     * Obtener ingresos del corte separados: MANOLO vs ELIA
     * Si una reservación tiene habitaciones mixtas, reparte proporcionalmente
     * por precio base de las habitaciones
     */
    public function obtenerIngresosPorTipoHabitacion($corte_id) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        // Movimientos de ingreso con reservación
        $sql = "SELECT 
                    mc.id as movimiento_id,
                    mc.metodo_pago,
                    mc.monto,
                    mc.reservacion_id,
                    h.nombre_completo as huesped_nombre
                FROM movimientos_caja mc
                INNER JOIN cortes_caja cc ON mc.corte_id = cc.id AND cc.hotel_id = mc.hotel_id
                INNER JOIN reservaciones r ON mc.reservacion_id = r.id AND r.hotel_id = mc.hotel_id
                LEFT JOIN huespedes h ON r.huesped_id = h.id
                WHERE mc.corte_id = ?
                AND cc.hotel_id = ?
                AND mc.hotel_id = ?
                AND mc.tipo = 'ingreso'
                AND mc.reservacion_id IS NOT NULL
                ORDER BY mc.created_at";
        
        $stmt = $db->query($sql, [$corte_id, $hotel_id, $hotel_id]);
        $registros = $stmt->fetchAll();
        
        // Ingresos SIN reservación
        $sqlOtros = "SELECT mc.metodo_pago, SUM(mc.monto) as total
                     FROM movimientos_caja mc
                     INNER JOIN cortes_caja cc ON mc.corte_id = cc.id AND cc.hotel_id = mc.hotel_id
                     WHERE mc.corte_id = ?
                     AND cc.hotel_id = ?
                     AND mc.hotel_id = ?
                     AND mc.tipo = 'ingreso'
                     AND (mc.reservacion_id IS NULL OR mc.reservacion_id = 0)
                     GROUP BY mc.metodo_pago";
        $stmtOtros = $db->query($sqlOtros, [$corte_id, $hotel_id, $hotel_id]);
        $otros = $stmtOtros->fetchAll();
        
        $resultado = [
            'manolo' => [
                'efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0,
                'cantidad_reservas' => 0, 'detalle' => []
            ],
            'elia' => [
                'efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0,
                'cantidad_reservas' => 0, 'detalle' => []
            ]
        ];
        
        $reservacionesContadas = [];
        
        foreach ($registros as $reg) {
            $resId = $reg['reservacion_id'];
            $metodo = $reg['metodo_pago'];
            $monto = floatval($reg['monto']);
            
            // Obtener habitaciones de esta reservación con su precio y tipo
            $sqlHabs = "SELECT hab.numero, hab.tipo, hab.precio_base
                        FROM reservacion_habitaciones rh
                        INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = rh.hotel_id
                        WHERE rh.reservacion_id = ?
                        AND rh.hotel_id = ?";
            $stmtHabs = $db->query($sqlHabs, [$resId, $hotel_id]);
            $habitaciones = $stmtHabs->fetchAll();
            
            // Calcular precio total y separar por propiedad
            $precioManolo = 0;
            $precioElia = 0;
            $habsManolo = [];
            $habsElia = [];
            
            foreach ($habitaciones as $hab) {
                $precio = floatval($hab['precio_base']);
                if (strpos($hab['tipo'], 'manolo') !== false) {
                    $precioManolo += $precio;
                    $habsManolo[] = $hab['numero'];
                } else {
                    $precioElia += $precio;
                    $habsElia[] = $hab['numero'];
                }
            }
            
            $precioTotal = $precioManolo + $precioElia;
            
            if ($precioTotal > 0) {
                // Repartir proporcionalmente al precio de las habitaciones
                $montoManolo = round($monto * ($precioManolo / $precioTotal), 2);
                $montoElia = round($monto * ($precioElia / $precioTotal), 2);
                
                // Ajustar centavos si hay diferencia por redondeo
                $diff = $monto - ($montoManolo + $montoElia);
                if ($diff != 0) {
                    if ($montoElia > $montoManolo) $montoElia += $diff;
                    else $montoManolo += $diff;
                }
            } else {
                // Sin precios, todo a Elia por default
                $montoManolo = 0;
                $montoElia = $monto;
            }
            
            // Asignar a Manolo
            if ($montoManolo > 0) {
                if (isset($resultado['manolo'][$metodo])) {
                    $resultado['manolo'][$metodo] += $montoManolo;
                }
                $claveRes = $resId . '-manolo';
                if (!isset($reservacionesContadas[$claveRes])) {
                    $resultado['manolo']['cantidad_reservas']++;
                    $reservacionesContadas[$claveRes] = true;
                }
                $resultado['manolo']['detalle'][] = [
                    'huesped' => $reg['huesped_nombre'],
                    'habitacion' => implode(', ', $habsManolo),
                    'metodo_pago' => $metodo,
                    'monto' => $montoManolo
                ];
            }
            
            // Asignar a Elia
            if ($montoElia > 0) {
                if (isset($resultado['elia'][$metodo])) {
                    $resultado['elia'][$metodo] += $montoElia;
                }
                $claveRes = $resId . '-elia';
                if (!isset($reservacionesContadas[$claveRes])) {
                    $resultado['elia']['cantidad_reservas']++;
                    $reservacionesContadas[$claveRes] = true;
                }
                $resultado['elia']['detalle'][] = [
                    'huesped' => $reg['huesped_nombre'],
                    'habitacion' => implode(', ', $habsElia),
                    'metodo_pago' => $metodo,
                    'monto' => $montoElia
                ];
            }
        }
        
        // Otros ingresos
        $totalOtros = 0;
        foreach ($otros as $otro) {
            $totalOtros += floatval($otro['total']);
        }
        if ($totalOtros > 0) {
            $resultado['_otros'] = ['total' => $totalOtros];
        }
        
        return $resultado;
    }
}


