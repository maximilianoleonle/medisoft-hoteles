<?php
/**
 * Modelo de Caja
 * Hotel San Nicolás
 */

require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/PropietarioDistribucionService.php';

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
     * Crear caja inicial segura para un hotel cuando no existe ninguna.
     */
    public function ensureDefaultCajaForHotel($hotelId) {
        $hotelId = (int) $hotelId;

        if ($hotelId <= 0) {
            return false;
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            $stmt = $this->db->query(
                "SELECT id
                 FROM cajas
                 WHERE hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$hotelId]
            );

            if ($stmt === false) {
                throw new Exception('No se pudo revisar si el hotel ya tiene caja.');
            }

            $cajaExistente = $stmt->fetch();

            if ($cajaExistente) {
                if ($ownTransaction) {
                    $this->db->safeCommit();
                }

                return true;
            }

            $stmt = $this->db->query(
                "INSERT INTO cajas
                    (hotel_id, nombre, ubicacion, monto_inicial, activa)
                 VALUES (?, ?, ?, ?, ?)",
                [$hotelId, 'Caja principal', 'Recepción', '0.00', 1]
            );

            if ($stmt === false) {
                throw new Exception('No se pudo crear la caja inicial del hotel.');
            }

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }

            error_log('Error al asegurar caja inicial para hotel ' . $hotelId . ': ' . $e->getMessage());
            return false;
        }
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
    private function grupoResumenMetodos(): array {
        return [
            'efectivo' => ['cantidad' => 0, 'total' => 0],
            'tarjeta' => ['cantidad' => 0, 'total' => 0],
            'transferencia' => ['cantidad' => 0, 'total' => 0],
            'total' => 0
        ];
    }

    private function condicionReversoIngresoSql(string $alias = 'mc'): string {
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
    
    // Obtener TODOS los movimientos del corte. La marca es_reverso usa la MISMA
    // condicion SQL que el listado por categorias (condicionReversoIngresoSql),
    // para que tarjetas de resumen y listado nunca clasifiquen distinto.
    $condicionReverso = $this->condicionReversoIngresoSql('');
    $sql = "SELECT
            tipo,
            metodo_pago,
            CASE WHEN {$condicionReverso} THEN 1 ELSE 0 END as es_reverso,
            SUM(monto) as total,
            COUNT(*) as cantidad
            FROM movimientos_caja
            WHERE corte_id = ?
            AND hotel_id = ?
            GROUP BY tipo, metodo_pago, es_reverso";
    
    $stmt = $db->query($sql, [$corte_id, $hotel_id]);
    $movimientos = $stmt->fetchAll();
    
    // Inicializar totales
    $resumen = [
        'ingresos' => $this->grupoResumenMetodos(),
        'reversos' => $this->grupoResumenMetodos(),
        'gastos' => $this->grupoResumenMetodos(),
        'gastos_reales' => $this->grupoResumenMetodos(),
        'ingreso_neto' => $this->grupoResumenMetodos()
    ];
    
    // Procesar movimientos
    foreach ($movimientos as $mov) {
        $tipo = $mov['tipo'];
        $metodo = $mov['metodo_pago'];
        $monto = floatval($mov['total']);
        $cantidad = intval($mov['cantidad']);

        if ($tipo == 'ingreso') {
            if (isset($resumen['ingresos'][$metodo])) {
                $resumen['ingresos'][$metodo]['cantidad'] += $cantidad;
                $resumen['ingresos'][$metodo]['total'] += $monto;
                $resumen['ingresos']['total'] += $monto;
            }
        } elseif (in_array($tipo, ['gasto', 'egreso'], true)) {
            if (isset($resumen['gastos'][$metodo])) {
                $resumen['gastos'][$metodo]['cantidad'] += $cantidad;
                $resumen['gastos'][$metodo]['total'] += $monto;
                $resumen['gastos']['total'] += $monto;
            }

            $grupoDestino = !empty($mov['es_reverso']) ? 'reversos' : 'gastos_reales';

            if (isset($resumen[$grupoDestino][$metodo])) {
                $resumen[$grupoDestino][$metodo]['cantidad'] += $cantidad;
                $resumen[$grupoDestino][$metodo]['total'] += $monto;
                $resumen[$grupoDestino]['total'] += $monto;
            }
        }
    }

    foreach (['efectivo', 'tarjeta', 'transferencia'] as $metodo) {
        $resumen['ingreso_neto'][$metodo]['cantidad'] =
            $resumen['ingresos'][$metodo]['cantidad'] + $resumen['reversos'][$metodo]['cantidad'];
        $resumen['ingreso_neto'][$metodo]['total'] =
            $resumen['ingresos'][$metodo]['total'] - $resumen['reversos'][$metodo]['total'];
        $resumen['ingreso_neto']['total'] += $resumen['ingreso_neto'][$metodo]['total'];
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
    $resumen['balance_operativo'] = $resumen['ingreso_neto']['total'] - $resumen['gastos_reales']['total'];
    
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
            COALESCE(cm.nombre, mc.categoria, 'Sin categoria') as categoria,
            COALESCE(cm.icono, 'circle') as icono,
            COALESCE(cm.color, '#6B7280') as color,
            COUNT(mc.id) as cantidad,
            SUM(mc.monto) as total
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.corte_id = ?
            AND mc.hotel_id = ?";
    
    $params = [$corte_id, $hotel_id];
    
    if ($tipo) {
        $condicionReverso = $this->condicionReversoIngresoSql('mc');
        // Si se especifica 'gasto', incluir también 'egreso'
        if ($tipo == 'gasto') {
            $sql .= " AND mc.tipo IN ('gasto', 'egreso')";
        } elseif ($tipo == 'gasto_real') {
            $sql .= " AND mc.tipo IN ('gasto', 'egreso') AND NOT {$condicionReverso}";
        } elseif ($tipo == 'reverso_ingreso') {
            $sql .= " AND {$condicionReverso}";
        } elseif ($tipo == 'ingreso') {
            $sql .= " AND mc.tipo = ?";
            $params[] = $tipo;
        }
    }
    
    $sql .= " GROUP BY COALESCE(cm.nombre, mc.categoria, 'Sin categoria'), COALESCE(cm.icono, 'circle'), COALESCE(cm.color, '#6B7280')
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
        
        $distribucionPropietarios = new PropietarioDistribucionService();
        $configPropietarios = $distribucionPropietarios->configuracionParaHotel($hotel_id);
        $resultado = $distribucionPropietarios->crearResultadoCaja($configPropietarios);
        
        $reservacionesContadas = [];
        
        foreach ($registros as $reg) {
            $resId = $reg['reservacion_id'];
            
            // Obtener habitaciones de esta reservación con su precio y tipo
            $sqlHabs = "SELECT hab.numero, hab.tipo, hab.precio_base
                        FROM reservacion_habitaciones rh
                        INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = rh.hotel_id
                        WHERE rh.reservacion_id = ?
                        AND rh.hotel_id = ?";
            $stmtHabs = $db->query($sqlHabs, [$resId, $hotel_id]);
            $habitaciones = $stmtHabs->fetchAll();
            
            $resultado = $distribucionPropietarios->aplicarMovimientoCaja(
                $resultado,
                $reg,
                $habitaciones,
                $reservacionesContadas,
                $configPropietarios
            );
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


