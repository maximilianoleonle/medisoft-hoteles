<?php
/**
 * Modelo de Reservación - COMPLETO CON TODOS LOS MÉTODOS
 * Los Cedros
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class Reservacion extends Model {
    protected $table = 'reservaciones';
    protected $fillable = [
        'hotel_id',
        'huesped_id',
        'total_habitaciones',
        'habitaciones_cortesia',
        'fecha_entrada',
        'hora_llegada_estimada',
        'hora_entrada',
        'fecha_salida',
        'hora_salida',
        'precio_total',
        'metodo_pago',
        'estado',
        'notas',
        'usuario_registro_id'
    ];

    protected function hotelIdActual()
    {
        return obtenerHotelIdActualCompat();
    }

    private function normalizarHabitacionIds($habitacion_ids)
    {
        $ids = array_map('intval', (array) $habitacion_ids);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    private function validarHabitacionesDelHotel($habitacion_ids, $hotel_id = null)
    {
        $ids = $this->normalizarHabitacionIds($habitacion_ids);

        if (empty($ids)) {
            return false;
        }

        $hotel_id = $hotel_id ?: $this->hotelIdActual();
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $params = array_merge($ids, [$hotel_id]);

        $sql = "SELECT COUNT(*) as total
                FROM habitaciones
                WHERE id IN ($placeholders)
                AND hotel_id = ?
                AND activa = 1";

        $stmt = $this->db->query($sql, $params);
        $row = $stmt ? $stmt->fetch() : null;

        return (int) ($row['total'] ?? 0) === count($ids);
    }
    public function obtenerReservacionesDelDia($buscar = null) {
    $sql = "SELECT r.*, 
            h.nombre_completo as huesped_nombre,
            h.telefono as huesped_telefono,
            h.procedencia_estado,
            GROUP_CONCAT(DISTINCT hab.numero ORDER BY CAST(hab.numero AS UNSIGNED), hab.numero SEPARATOR ', ') as habitaciones_numeros,
            GROUP_CONCAT(DISTINCT hab.tipo SEPARATOR '||') as habitaciones_tipos,
            COUNT(DISTINCT rh.habitacion_id) as total_habitaciones,
            u.nombre_completo as usuario_registro
            FROM {$this->table} r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
            LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
            WHERE r.hotel_id = ?
            AND r.estado IN ('confirmada', 'checked_in', 'checked_out')
            AND CURDATE() >= r.fecha_entrada 
            AND CURDATE() <= r.fecha_salida";
    
    $params = [$this->hotelIdActual()];
    
    if ($buscar) {
        $sql .= " AND (h.nombre_completo LIKE ? OR h.telefono LIKE ? OR hab.numero LIKE ? OR r.id = ?)";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
        $params[] = intval($buscar);
    }
    
    $sql .= " GROUP BY r.id ORDER BY r.created_at DESC";
    
    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
}
/**
     * Generar PDF de cotización desde una reservación EXISTENTE
     * Acepta un anticipo simbólico (no se guarda)
     * Se llama via POST desde el modal en ver.php
     */
/**
 * Obtener reservaciones activas para una fecha específica
 * Incluye tipo de habitación para mostrar en las cards
 * @param string $fecha - Fecha en formato Y-m-d (default: hoy)
 * @param string|null $buscar - Término de búsqueda opcional
 */
public function obtenerHabitacionesReservadasPorFecha($fecha = null, $buscar = null) {
    if (!$fecha) {
        $fecha = date('Y-m-d');
    }
    
    $sql = "SELECT 
                r.id,
                r.huesped_id,
                r.fecha_entrada,
                r.fecha_salida,
                r.hora_llegada_estimada,
                r.hora_entrada,
                r.hora_salida,
                r.precio_total,
                r.metodo_pago,
                r.estado,
                r.notas,
                r.usuario_registro_id,
                r.created_at,
                h.nombre_completo AS huesped_nombre,
                h.telefono AS huesped_telefono,
                hab.id AS habitacion_id,
                hab.numero AS habitacion_numero,
                hab.tipo AS habitacion_tipo,
                hab.piso AS habitacion_piso,
                u.nombre_completo AS usuario_registro,
                (SELECT COUNT(*) 
                 FROM reservacion_habitaciones rh2 
                 WHERE rh2.reservacion_id = r.id
                 AND rh2.hotel_id = r.hotel_id
                ) AS total_habitaciones_reserva,
                (SELECT GROUP_CONCAT(h2.numero ORDER BY CAST(h2.numero AS UNSIGNED), h2.numero SEPARATOR ', ')
                 FROM reservacion_habitaciones rh2 
                 INNER JOIN habitaciones h2 ON rh2.habitacion_id = h2.id AND h2.hotel_id = rh2.hotel_id
                 WHERE rh2.reservacion_id = r.id
                 AND rh2.hotel_id = r.hotel_id
                ) AS todas_habitaciones
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
            LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
WHERE r.hotel_id = ?
AND r.estado IN ('confirmada', 'checked_in', 'checked_out')
AND ? >= DATE(r.fecha_entrada) AND ? < DATE(r.fecha_salida)";
    
    $params = [$this->hotelIdActual(), $fecha, $fecha];
    
    if ($buscar) {
        $sql .= " AND (h.nombre_completo LIKE ? OR h.telefono LIKE ? OR hab.numero LIKE ? OR r.id = ?)";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
        $params[] = "%{$buscar}%";
        $params[] = intval($buscar);
    }
    
    $sql .= " ORDER BY CAST(hab.numero AS UNSIGNED), hab.numero";
    
    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
}

// ═══════════════════════════════════════════════════════════════
// PASO 2: REEMPLAZAR indexAction EN ReservacionController.php
// ═══════════════════════════════════════════════════════════════

public function indexAction() {
    $buscar = $this->getQuery('buscar');
    $fecha = $this->getQuery('fecha', date('Y-m-d')); // ← NUEVO: recibe ?fecha=YYYY-MM-DD
    
    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = date('Y-m-d');
    }
    
    // Reservaciones activas para la fecha seleccionada
    $reservaciones = $this->reservacionModel->obtenerReservacionesPorFecha($fecha, $buscar);
    
    // Estadísticas del dashboard (siempre del día actual)
    $estadisticas = $this->reservacionModel->obtenerEstadisticasDashboard();
    $entradas_hoy = $this->reservacionModel->obtenerEntradasHoy();
    $salidas_hoy = $this->reservacionModel->obtenerSalidasHoy();
    
    View::renderTemplate('reservaciones/index', [
        'title' => 'Reservaciones - Los Cedros',
        'reservaciones' => $reservaciones,
        'buscar' => $buscar,
        'fecha_filtro' => $fecha,           // ← NUEVO: se pasa la fecha a la vista
        'estados' => Reservacion::getEstados(),
        'total_reservaciones' => count($reservaciones),
        'estadisticas' => $estadisticas,
        'entradas_hoy' => $entradas_hoy,
        'salidas_hoy' => $salidas_hoy
    ]);
}

    /**
     * Estados posibles de la reservación
     */
    public static function getEstados() {
        return [
            'confirmada' => ['label' => 'Confirmada', 'color' => 'blue', 'icon' => 'calendar-check'],
            'checked_in' => ['label' => 'Check-in', 'color' => 'green', 'icon' => 'user-check'],
            'checked_out' => ['label' => 'Check-out', 'color' => 'gray', 'icon' => 'user-x'],
            'cancelada' => ['label' => 'Cancelada', 'color' => 'red', 'icon' => 'x-circle']
        ];
    }
    /**
 * Calcular precio total con incrementos aplicables
 */
public function calcularPrecioTotal($habitacion_ids, $fecha_entrada, $fecha_salida) {
    $habitacionModel = new Habitacion();
    $tarifaModel = new IncrementoTarifa();
    
    $precio_total = 0;
    $desglose = [];
    
    // Calcular días de estancia
    $fecha_inicio = new DateTime($fecha_entrada);
    $fecha_fin = new DateTime($fecha_salida);
    $noches = $fecha_inicio->diff($fecha_fin)->days;
    
    foreach ($habitacion_ids as $hab_id) {
        $habitacion = $habitacionModel->find($hab_id);
        if (!$habitacion) continue;
        
        $precio_habitacion = 0;
        $desglose_habitacion = [
            'habitacion_id' => $hab_id,
            'numero' => $habitacion['numero'],
            'tipo' => $habitacion['tipo'],
            'precio_base' => $habitacion['precio_base'],
            'noches' => []
        ];
        
        // Calcular precio por cada noche
        $fecha_actual = clone $fecha_inicio;
        
        for ($i = 0; $i < $noches; $i++) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            $calculo = $tarifaModel->calcularPrecioConIncremento(
                $habitacion['id'],
                $habitacion['tipo'],
                $habitacion['precio_base'],
                $fecha_str
            );
            
            $precio_habitacion += $calculo['precio_final'];
            
            $desglose_habitacion['noches'][] = [
                'fecha' => $fecha_str,
                'precio_base' => $calculo['precio_base'],
                'precio_final' => $calculo['precio_final'],
                'incrementos' => $calculo['incrementos_aplicados']
            ];
            
            $fecha_actual->modify('+1 day');
        }
        
        $desglose_habitacion['precio_total'] = $precio_habitacion;
        $precio_total += $precio_habitacion;
        $desglose[] = $desglose_habitacion;
    }
    
    return [
        'precio_total' => $precio_total,
        'desglose' => $desglose,
        'noches' => $noches
    ];
}
    /**
     * Métodos de pago disponibles
     */
    public static function getMetodosPago() {
        return [
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia'
        ];
    }
    
    /**
 * Check-out parcial - Liberar solo algunas habitaciones de una reservación
 * @param int $reservacion_id ID de la reservación
 * @param array $habitaciones_ids IDs de las habitaciones a liberar
 * @param string $hora_salida Hora de salida (opcional)
 * @return array Resultado de la operación
 */
public function checkOutParcial($reservacion_id, $habitaciones_ids, $hora_salida = null) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    try {
        error_log("=== INICIO checkOutParcial ===");
        error_log("Reservación ID: $reservacion_id");
        error_log("IDs recibidos: " . implode(', ', $habitaciones_ids));
        
        $db->beginTransaction();
        
        // Validar que la reservación exista y esté en checked_in
        $stmt_reservacion = $db->query(
            "SELECT * FROM reservaciones WHERE id = ? AND hotel_id = ?",
            [$reservacion_id, $hotel_id]
        );
        $reservacion = $stmt_reservacion ? $stmt_reservacion->fetch() : null;
        if (!$reservacion) {
            throw new Exception('La reservación no existe');
        }
        
        if ($reservacion['estado'] !== 'checked_in') {
            throw new Exception('La reservación no está activa (estado: ' . $reservacion['estado'] . ')');
        }
        
        // Validar que haya habitaciones a liberar
        if (empty($habitaciones_ids)) {
            throw new Exception('Debe seleccionar al menos una habitación');
        }
        
        // Obtener todas las habitaciones de la reservación
        $sql = "SELECT rh.habitacion_id, h.numero 
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h
                    ON rh.habitacion_id = h.id
                    AND h.hotel_id = rh.hotel_id
                WHERE rh.reservacion_id = ?
                AND rh.hotel_id = ?
                AND h.hotel_id = ?";
        $stmt = $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id]);
        $todas_habitaciones = $stmt->fetchAll();
        
        error_log("Habitaciones de la reservación:");
        foreach ($todas_habitaciones as $hab) {
            error_log("  - ID: {$hab['habitacion_id']}, Número: {$hab['numero']}");
        }
        
        // Validar que las habitaciones pertenezcan a la reservación
        $habitaciones_validas = array_column($todas_habitaciones, 'habitacion_id');
        error_log("IDs válidos: " . implode(', ', $habitaciones_validas));
        
        foreach ($habitaciones_ids as $hab_id) {
            if (!in_array($hab_id, $habitaciones_validas)) {
                error_log("ERROR: ID $hab_id NO es válido");
                error_log("IDs válidos: " . implode(', ', $habitaciones_validas));
                error_log("IDs recibidos: " . implode(', ', $habitaciones_ids));
                throw new Exception("La habitación ID $hab_id no pertenece a esta reservación");
            }
        }
        
        error_log("✓ Todas las habitaciones son válidas");
        
        // Liberar las habitaciones seleccionadas (cambiar estado a limpieza)
        // PROTECCIÓN: No cambiar si otra reservación activa ocupa la habitación
        $placeholders = implode(',', array_fill(0, count($habitaciones_ids), '?'));
        $sql = "UPDATE habitaciones 
                SET estado = 'limpieza' 
                WHERE id IN ($placeholders) 
                AND hotel_id = ?
                AND estado = 'ocupada'
                AND NOT EXISTS (
                    SELECT 1 FROM reservacion_habitaciones rh2
                    INNER JOIN reservaciones r2
                        ON rh2.reservacion_id = r2.id
                        AND r2.hotel_id = rh2.hotel_id
                    WHERE rh2.habitacion_id = habitaciones.id
                    AND rh2.hotel_id = ?
                    AND r2.hotel_id = ?
                    AND r2.id != ?
                    AND r2.estado = 'checked_in'
                )";
        $params_update = array_merge($habitaciones_ids, [$hotel_id, $hotel_id, $hotel_id, $reservacion_id]);
        $result = $db->query($sql, $params_update);
        
        error_log("Habitaciones actualizadas: " . $result->rowCount());
        
        // Obtener números de habitaciones liberadas para la nota
        $sql = "SELECT numero FROM habitaciones WHERE id IN ($placeholders) AND hotel_id = ? ORDER BY numero";
        $stmt = $db->query($sql, array_merge($habitaciones_ids, [$hotel_id]));
        $habitaciones_liberadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Números liberados: " . implode(', ', $habitaciones_liberadas));
        
        // Verificar cuántas habitaciones quedan ocupadas
        $sql = "SELECT COUNT(*) as ocupadas 
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h
                    ON rh.habitacion_id = h.id
                    AND h.hotel_id = rh.hotel_id
                WHERE rh.reservacion_id = ? 
                AND rh.hotel_id = ?
                AND h.hotel_id = ?
                AND h.estado = 'ocupada'";
        $stmt = $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id]);
        $resultado = $stmt->fetch();
        $habitaciones_ocupadas = $resultado['ocupadas'];
        
        error_log("Habitaciones que quedan ocupadas: $habitaciones_ocupadas");
        
        // Determinar si es check-out completo o parcial
        $es_checkout_completo = ($habitaciones_ocupadas == 0);
        
        // Preparar nota
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario';
        $nota = "\n\n[CHECK-OUT " . ($es_checkout_completo ? 'COMPLETO' : 'PARCIAL') . "] ";
        $nota .= date('Y-m-d H:i:s') . " - Por: " . $usuario_nombre;
        $nota .= "\nHabitaciones liberadas: " . implode(', ', $habitaciones_liberadas);
        
        if (!$es_checkout_completo) {
            $nota .= "\nHabitaciones que permanecen ocupadas: " . $habitaciones_ocupadas;
        }
        
        // Actualizar reservación
        if ($es_checkout_completo) {
            // Si se liberaron todas, marcar como checked_out
            $sql = "UPDATE reservaciones 
                    SET estado = 'checked_out',
                        hora_salida = ?,
                        notas = CONCAT(IFNULL(notas, ''), ?)
                    WHERE id = ?
                    AND hotel_id = ?";
            $db->query($sql, [
                $hora_salida ?? date('H:i:s'),
                $nota,
                $reservacion_id,
                $hotel_id
            ]);
            
            error_log("✓ Check-out COMPLETO - Reservación marcada como checked_out");
        } else {
            // Si quedan habitaciones, solo agregar nota
            $sql = "UPDATE reservaciones 
                    SET notas = CONCAT(IFNULL(notas, ''), ?)
                    WHERE id = ?
                    AND hotel_id = ?";
            $db->query($sql, [$nota, $reservacion_id, $hotel_id]);
            
            error_log("✓ Check-out PARCIAL - Reservación sigue activa");
        }
        
        $db->commit();
        
        error_log("=== FIN checkOutParcial EXITOSO ===");
        
        return [
            'success' => true,
            'habitaciones_liberadas' => count($habitaciones_liberadas),
            'numeros_liberados' => $habitaciones_liberadas,
            'habitaciones_restantes' => $habitaciones_ocupadas,
            'checkout_completo' => $es_checkout_completo
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("ERROR CRÍTICO en checkOutParcial: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
    
 private function devolverInventarioCancelacion($reservacion_id) {
    $db = Database::getInstance();

    try {
        $hotel_id = obtenerHotelIdActualCompat();
        $motivo_devolucion = "Devolucion por cancelacion - Reservacion #" . $reservacion_id;

        $sql_validacion = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN m.hotel_id IS NULL THEN 1 ELSE 0 END) AS sin_hotel,
                    SUM(CASE WHEN m.hotel_id IS NOT NULL AND m.hotel_id != ? THEN 1 ELSE 0 END) AS hotel_distinto,
                    SUM(CASE WHEN ip.id IS NULL OR ip.hotel_id != m.hotel_id THEN 1 ELSE 0 END) AS producto_invalido,
                    SUM(CASE
                        WHEN m.habitacion_id IS NOT NULL
                            AND (h.id IS NULL OR h.hotel_id != m.hotel_id)
                        THEN 1 ELSE 0
                    END) AS habitacion_invalida
                FROM movimientos_inventario m
                LEFT JOIN inventario_productos ip ON m.producto_id = ip.id
                LEFT JOIN habitaciones h ON m.habitacion_id = h.id
                WHERE m.reservacion_id = ?
                    AND m.tipo_movimiento = 'SALIDA'";

        $stmt_validacion = $db->query($sql_validacion, [$hotel_id, $reservacion_id]);
        if (!$stmt_validacion) {
            throw new Exception("No se pudieron validar los movimientos de inventario de la reservacion");
        }

        $validacion = $stmt_validacion->fetch();
        $total_salidas = (int)($validacion['total'] ?? 0);

        if ($total_salidas === 0) {
            error_log("No se encontraron movimientos de inventario para devolver");
            return ['success' => true, 'productos_devueltos' => 0, 'detalles' => []];
        }

        if ((int)($validacion['sin_hotel'] ?? 0) > 0) {
            throw new Exception("Hay movimientos de inventario sin hotel_id para esta reservacion");
        }

        if ((int)($validacion['hotel_distinto'] ?? 0) > 0) {
            throw new Exception("Hay movimientos de inventario de otro hotel para esta reservacion");
        }

        if ((int)($validacion['producto_invalido'] ?? 0) > 0) {
            throw new Exception("Hay movimientos con producto de inventario fuera del hotel actual");
        }

        if ((int)($validacion['habitacion_invalida'] ?? 0) > 0) {
            throw new Exception("Hay movimientos con habitacion fuera del hotel actual");
        }

        $sql_movimientos = "SELECT
                    m.producto_id,
                    m.habitacion_id,
                    m.hotel_id,
                    ip.nombre AS producto_nombre,
                    ip.codigo,
                    SUM(m.cantidad) AS cantidad_salida,
                    COALESCE(dev.cantidad_devuelta, 0) AS cantidad_devuelta,
                    SUM(m.cantidad) - COALESCE(dev.cantidad_devuelta, 0) AS cantidad_devolver
                FROM movimientos_inventario m
                INNER JOIN inventario_productos ip
                    ON m.producto_id = ip.id
                    AND ip.hotel_id = m.hotel_id
                LEFT JOIN (
                    SELECT
                        producto_id,
                        habitacion_id,
                        hotel_id,
                        reservacion_id,
                        SUM(cantidad) AS cantidad_devuelta
                    FROM movimientos_inventario
                    WHERE reservacion_id = ?
                        AND tipo_movimiento = 'ENTRADA'
                        AND motivo = ?
                    GROUP BY producto_id, habitacion_id, hotel_id, reservacion_id
                ) dev
                    ON dev.producto_id = m.producto_id
                    AND dev.habitacion_id <=> m.habitacion_id
                    AND dev.hotel_id = m.hotel_id
                    AND dev.reservacion_id = m.reservacion_id
                WHERE m.reservacion_id = ?
                    AND m.tipo_movimiento = 'SALIDA'
                    AND m.hotel_id = ?
                GROUP BY
                    m.producto_id,
                    m.habitacion_id,
                    m.hotel_id,
                    ip.nombre,
                    ip.codigo,
                    dev.cantidad_devuelta
                HAVING cantidad_devolver > 0
                ORDER BY m.producto_id, m.habitacion_id";

        $stmt_movimientos = $db->query($sql_movimientos, [
            $reservacion_id,
            $motivo_devolucion,
            $reservacion_id,
            $hotel_id
        ]);

        if (!$stmt_movimientos) {
            throw new Exception("No se pudieron consultar los movimientos a devolver");
        }

        $movimientos = $stmt_movimientos->fetchAll();

        if (empty($movimientos)) {
            error_log("La reservacion #{$reservacion_id} ya tiene devolucion de inventario registrada");
            return ['success' => true, 'productos_devueltos' => 0, 'detalles' => []];
        }

        $productos_devueltos = [];
        $usuario_id = function_exists('user_id') ? user_id() : null;

        foreach ($movimientos as $mov) {
            $cantidad_devolver = (int)$mov['cantidad_devolver'];

            if ($cantidad_devolver <= 0) {
                continue;
            }

            $stmt_stock = $db->query(
                "SELECT stock_actual
                 FROM inventario_productos
                 WHERE id = ? AND hotel_id = ?
                 FOR UPDATE",
                [$mov['producto_id'], $hotel_id]
            );

            if (!$stmt_stock) {
                throw new Exception("No se pudo bloquear el producto para devolver inventario");
            }

            $producto_actual = $stmt_stock->fetch();
            if (!$producto_actual) {
                throw new Exception("Producto no encontrado para el hotel actual durante la devolucion");
            }

            $stock_anterior = (int)$producto_actual['stock_actual'];
            $stock_posterior = $stock_anterior + $cantidad_devolver;

            $stmt_update = $db->query(
                "UPDATE inventario_productos
                 SET stock_actual = ?
                 WHERE id = ? AND hotel_id = ?",
                [$stock_posterior, $mov['producto_id'], $hotel_id]
            );

            if (!$stmt_update || $stmt_update->rowCount() === 0) {
                throw new Exception("No se pudo restaurar el stock del producto {$mov['producto_id']}");
            }

            $stmt_insert = $db->query(
                "INSERT INTO movimientos_inventario
                    (hotel_id, producto_id, tipo_movimiento, cantidad, stock_anterior,
                     stock_posterior, motivo, habitacion_id, reservacion_id, usuario_id, created_at)
                 VALUES (?, ?, 'ENTRADA', ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $hotel_id,
                    $mov['producto_id'],
                    $cantidad_devolver,
                    $stock_anterior,
                    $stock_posterior,
                    $motivo_devolucion,
                    $mov['habitacion_id'] ?: null,
                    $reservacion_id,
                    $usuario_id
                ]
            );

            if (!$stmt_insert) {
                throw new Exception("No se pudo registrar el movimiento de devolucion");
            }

            $productos_devueltos[] = [
                'producto' => $mov['producto_nombre'],
                'cantidad' => $cantidad_devolver
            ];

            error_log("Devuelto al inventario: {$cantidad_devolver} de {$mov['producto_nombre']}");
        }

        return [
            'success' => true,
            'productos_devueltos' => count($productos_devueltos),
            'detalles' => $productos_devueltos
        ];

    } catch (Exception $e) {
        error_log("Error al devolver inventario: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage(), 'productos_devueltos' => 0, 'detalles' => []];
    }
}
/**
 * Método registrarPagosMixtos corregido
 * Agregar este método a la clase Reservacion si no existe
 */

private function registrarPagosMixtos($reservacion_id, $pagos, $monto_recibido = null, $cambio = null) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();

    $stmt_reservacion = $db->query(
        "SELECT id FROM reservaciones WHERE id = ? AND hotel_id = ?",
        [$reservacion_id, $hotel_id]
    );

    if (!$stmt_reservacion || !$stmt_reservacion->fetch()) {
        throw new Exception("Reservacion no encontrada para el hotel actual");
    }
    
    // Verificar si la tabla existe
    $sql = "SHOW TABLES LIKE 'reservacion_pagos'";
    $stmt = $db->query($sql);
    
    if ($stmt->rowCount() == 0) {
        return; // La tabla no existe, salir sin error
    }
    
    // Eliminar pagos anteriores si existen
    $sql = "DELETE FROM reservacion_pagos WHERE reservacion_id = ? AND hotel_id = ?";
    $db->query($sql, [$reservacion_id, $hotel_id]);
    
    // Insertar nuevos pagos
    $sql = "INSERT INTO reservacion_pagos 
            (reservacion_id, hotel_id, metodo_pago, monto, referencia, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    foreach ($pagos as $pago) {
        if ($pago['monto'] > 0) {
            $db->query($sql, [
                $reservacion_id,
                $hotel_id,
                $pago['metodo'],
                $pago['monto'],
                $pago['referencia'] ?? null
            ]);
        }
    }
}

/**
 * Obtener los pagos de una reservación
 * @param int $reservacion_id
 * @return array
 */
public function obtenerPagos($reservacion_id) {
    $db = Database::getInstance();
    $sql = "SELECT * FROM reservacion_pagos 
            WHERE reservacion_id = ? 
            ORDER BY metodo_pago";
    
    $stmt = $db->query($sql, [$reservacion_id]);
    return $stmt->fetchAll();
}

/**
 * Obtener resumen de pagos por método
 * @param int $reservacion_id
 * @return array
 */
public function resumenPagosPorMetodo($reservacion_id) {
    $db = Database::getInstance();
    $sql = "SELECT 
                metodo_pago,
                SUM(monto) as total,
                COUNT(*) as cantidad
            FROM reservacion_pagos 
            WHERE reservacion_id = ?
            GROUP BY metodo_pago";
    
    $stmt = $db->query($sql, [$reservacion_id]);
    $resultado = [];
    
    while ($row = $stmt->fetch()) {
        $resultado[$row['metodo_pago']] = [
            'total' => $row['total'],
            'cantidad' => $row['cantidad']
        ];
    }
    
    return $resultado;
}

/**
 * Hacer check-in con pagos mixtos
 * SOLO agregar este método si NO existe en tu modelo
 */


/**
 * Intentar registrar pagos mixtos si la tabla existe
 */


/**
 * Intentar registrar pagos mixtos si la tabla existe
 */
/**
 * Hacer check-in con pagos mixtos - Versión final sin duplicados
 */
public function checkInConPagosMixtos($id, $hora_entrada, $pagos, $monto_recibido = null, $cambio = null) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    try {
        $db->beginTransaction();
        
        // VALIDACIÓN: Verificar que haya una caja abierta
        $cajaModel = new Caja();
        $corteActual = $cajaModel->obtenerCorteActual();
        
        if (!$corteActual) {
            throw new Exception("No se puede registrar el pago. Debe abrir la caja primero.");
        }

        if ((int)($corteActual['hotel_id'] ?? 0) !== (int)$hotel_id) {
            throw new Exception("El corte abierto no pertenece al hotel actual");
        }
        
        // Obtener datos de la reservación
        $stmt_reservacion = $db->query(
            "SELECT * FROM reservaciones WHERE id = ? AND hotel_id = ?",
            [$id, $hotel_id]
        );
        $reservacion = $stmt_reservacion ? $stmt_reservacion->fetch() : null;
        if (!$reservacion) {
            throw new Exception("Reservación no encontrada");
        }
        
        // Verificar estado actual
        if ($reservacion['estado'] != 'confirmada') {
            throw new Exception("La reservación debe estar en estado 'confirmada' para hacer check-in");
        }
        
        // Obtener el ID del usuario actual
        $usuario_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        if (!$usuario_id) {
            throw new Exception("No se pudo identificar al usuario actual");
        }
        
        // Determinar el método de pago principal (el de mayor monto)
        $metodo_principal = 'efectivo';
        $monto_mayor = 0;
        
        foreach ($pagos as $pago) {
            if ($pago['monto'] > $monto_mayor) {
                $monto_mayor = $pago['monto'];
                $metodo_principal = $pago['metodo'];
            }
        }
        
        // 1. Actualizar estado de la reservación
        $sql = "UPDATE reservaciones 
                SET estado = 'checked_in', 
                    hora_entrada = ?,
                    metodo_pago = ?,
                    monto_recibido = ?,
                    cambio = ?
                WHERE id = ? AND hotel_id = ? AND estado = 'confirmada'";
                
        $stmt = $db->query($sql, [$hora_entrada, $metodo_principal, $monto_recibido, $cambio, $id, $hotel_id]);
        
        if (!$stmt || $stmt->rowCount() == 0) {
            throw new Exception("No se pudo actualizar el estado de la reservación");
        }
        
        // ====== NUEVO: VALIDAR ESTADO DE HABITACIONES ANTES DEL CHECK-IN ======
        // Verificar que ninguna habitación esté en un estado no válido (mantenimiento, ocupada, etc.)
        $sql_validar = "SELECT h.numero, h.estado 
                        FROM habitaciones h
                        INNER JOIN reservacion_habitaciones rh
                            ON h.id = rh.habitacion_id
                            AND h.hotel_id = rh.hotel_id
                        WHERE rh.reservacion_id = ?
                        AND rh.hotel_id = ?
                        AND h.hotel_id = ?
                        AND h.estado NOT IN ('disponible', 'limpieza')";
        
        $stmt_validar = $db->query($sql_validar, [$id, $hotel_id, $hotel_id]);
        $habitaciones_problema = $stmt_validar->fetchAll();
        
        if (!empty($habitaciones_problema)) {
            $habitaciones_lista = [];
            $estados_texto = [
                'mantenimiento' => 'en mantenimiento',
                'ocupada' => 'ocupada por otra reservación'
            ];
            
            foreach ($habitaciones_problema as $hab) {
                $estado_desc = $estados_texto[$hab['estado']] ?? $hab['estado'];
                $habitaciones_lista[] = "Habitación {$hab['numero']} ({$estado_desc})";
            }
            
            throw new Exception(
                "No se puede hacer check-in. Las siguientes habitaciones no están disponibles: " . 
                implode(', ', $habitaciones_lista) . 
                ". Por favor, finalice el mantenimiento o libere las habitaciones antes de continuar."
            );
        }
        
        error_log("✅ Validación de estados OK - Todas las habitaciones disponibles para check-in");
        // ====== FIN VALIDACIÓN ======
        
        // 2. Actualizar habitaciones a ocupadas
        $sql = "UPDATE habitaciones h
                INNER JOIN reservacion_habitaciones rh
                    ON h.id = rh.habitacion_id
                    AND h.hotel_id = rh.hotel_id
                SET h.estado = 'ocupada'
                WHERE rh.reservacion_id = ?
                AND rh.hotel_id = ?
                AND h.hotel_id = ?
                AND h.estado IN ('disponible', 'limpieza')";
                
        $stmt = $db->query($sql, [$id, $hotel_id, $hotel_id]);
        
        // Verificar que se actualizaron habitaciones
        if ($stmt->rowCount() == 0) {
            // Verificar el estado actual de las habitaciones
            $sql_check = "SELECT h.numero, h.estado 
                          FROM habitaciones h
                          INNER JOIN reservacion_habitaciones rh
                            ON h.id = rh.habitacion_id
                            AND h.hotel_id = rh.hotel_id
                          WHERE rh.reservacion_id = ?
                          AND rh.hotel_id = ?
                          AND h.hotel_id = ?";
            $stmt_check = $db->query($sql_check, [$id, $hotel_id, $hotel_id]);
            $habitaciones_estados = $stmt_check->fetchAll();
            
            $estados_info = [];
            foreach ($habitaciones_estados as $hab) {
                $estados_info[] = "Habitación {$hab['numero']}: {$hab['estado']}";
            }
            
            throw new Exception("No se pudieron actualizar las habitaciones. Estados actuales: " . implode(', ', $estados_info));
        }
        
        error_log("✅ Check-in: {$stmt->rowCount()} habitaciones marcadas como ocupadas");
        
        // 3. Obtener categoría de hospedaje para movimientos de caja
        $sql_cat = "SELECT id FROM categorias_movimientos 
                    WHERE nombre = 'Hospedaje' AND tipo = 'ingreso' AND activa = 1 
                    LIMIT 1";
        $stmt_cat = $db->query($sql_cat);
        $categoria = $stmt_cat->fetch();
        
        if (!$categoria) {
            // Si no existe, crear la categoría
            $sql_crear = "INSERT INTO categorias_movimientos 
                         (nombre, tipo, descripcion, icono, color, activa, created_at) 
                         VALUES ('Hospedaje', 'ingreso', 'Ingresos por hospedaje', 
                                 'fas fa-bed', '#10B981', 1, NOW())";
            $db->query($sql_crear);
            $categoria_id = $db->lastInsertId();
            error_log("Categoría Hospedaje creada con ID: $categoria_id");
        } else {
            $categoria_id = $categoria['id'];
        }
        
        // 4. Registrar cada pago en movimientos_caja
        foreach ($pagos as $pago) {
            if ($pago['monto'] > 0) {
                $sql = "INSERT INTO movimientos_caja 
                        (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                         referencia, reservacion_id, usuario_id, corte_id, created_at) 
                        VALUES (?, 'ingreso', 'Hospedaje', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $descripcion = "Hospedaje - Reservación #" . $id;
                $params = [
                    $hotel_id,
                    $categoria_id,
                    $descripcion,
                    $pago['monto'],
                    $pago['metodo'],
                    $pago['referencia'] ?? null,
                    $id,
                    $usuario_id,
                    $corteActual['id']
                ];
                
                $stmt = $db->query($sql, $params);
                
                if (!$stmt) {
                    throw new Exception("Error al registrar pago en movimientos_caja");
                }
            }
        }
        
        // 5. Intentar registrar en la tabla de pagos mixtos si existe
        try {
            $this->registrarPagosMixtos($id, $pagos, $monto_recibido, $cambio);
        } catch (Exception $e) {
            // Si falla el registro de pagos mixtos, no es crítico
            error_log("Advertencia: No se pudieron registrar pagos mixtos: " . $e->getMessage());
        }
        
        // 6. IMPORTANTE: Confirmar la transacción
        $db->commit();
        
        // 7. Registrar en logs
        error_log("Check-in exitoso - Reservación: $id, Usuario: $usuario_id, Total: $" . $reservacion['precio_total']);
        
        return true;
        
    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios
        $db->rollBack();
        error_log("ERROR en checkInConPagosMixtos: " . $e->getMessage());
        throw $e; // Re-lanzar la excepción para que el controlador pueda manejarla
    }
}

// ========== AGREGAR ESTOS MÉTODOS EN Reservacion.php (modelo) ==========

    /**
     * Verificar disponibilidad de múltiples habitaciones excluyendo una reservación específica
     * Se usa al editar habitaciones de una reservación existente
     */
    public function verificarDisponibilidadMultipleExcluyendo($habitacion_ids, $fecha_entrada, $fecha_salida, $excluir_reservacion_id = null) {
        if (empty($habitacion_ids)) {
            return false;
        }
        
        $habitacion_ids = $this->normalizarHabitacionIds($habitacion_ids);
        $hotel_id = $this->hotelIdActual();

        if (!$this->validarHabitacionesDelHotel($habitacion_ids, $hotel_id)) {
            return false;
        }

        $placeholders = str_repeat('?,', count($habitacion_ids) - 1) . '?';
        $sql = "SELECT COUNT(DISTINCT rh.habitacion_id) as ocupadas
                FROM reservacion_habitaciones rh
                INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                WHERE rh.habitacion_id IN ($placeholders)
                AND rh.hotel_id = ?
                AND r.hotel_id = ?
                AND r.estado IN ('confirmada', 'checked_in')";
        
        // Agregar exclusión si se proporciona
        $params = $habitacion_ids;
        $params[] = $hotel_id;
        $params[] = $hotel_id;
        
        if ($excluir_reservacion_id) {
            $sql .= " AND r.id != ?";
            $params[] = $excluir_reservacion_id;
        }
        
        $sql .= " AND (
                    (? BETWEEN r.fecha_entrada AND DATE_SUB(r.fecha_salida, INTERVAL 1 DAY))
                    OR (? BETWEEN DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY) AND r.fecha_salida)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                )";
        
        // Agregar parámetros de fechas
        $params[] = $fecha_entrada;
        $params[] = $fecha_salida;
        $params[] = $fecha_entrada;
        $params[] = $fecha_salida;
        
        try {
            $stmt = $this->db->query($sql, $params);
            $result = $stmt->fetch();
            
            // Si no hay habitaciones ocupadas, todas están disponibles
            return $result['ocupadas'] == 0;
        } catch (Exception $e) {
            error_log("Error en verificarDisponibilidadMultipleExcluyendo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar habitaciones de una reservación existente
     * Maneja cambios de habitaciones, cortesías y recalcula precios
     */
public function actualizarHabitaciones($reservacion_id, $habitaciones, $cortesias_ids, $fecha_entrada, $fecha_salida, $hora_llegada_estimada = null) {
    try {
        error_log("=== INICIO actualizarHabitaciones (CON TARIFAS DINÁMICAS) ===");
        error_log("Reservación ID: $reservacion_id");
        error_log("Total habitaciones: " . count($habitaciones));
        error_log("Cortesías recibidas: " . json_encode($cortesias_ids));
        
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        $hotel_id = $this->hotelIdActual();
        
        // Obtener reservación actual
        $stmt_reservacion = $pdo->prepare("SELECT * FROM reservaciones WHERE id = ? AND hotel_id = ? LIMIT 1");
        $stmt_reservacion->execute([$reservacion_id, $hotel_id]);
        $reservacion_actual = $stmt_reservacion->fetch(PDO::FETCH_ASSOC);
        if (!$reservacion_actual) {
            throw new Exception("Reservación no encontrada");
        }
        
        $precio_anterior = $reservacion_actual['precio_total'];
        
        // ====== IMPORTANTE: Guardar el estado de la reservación ======
        $estado_reservacion = $reservacion_actual['estado'];
        error_log("Estado de la reservación: $estado_reservacion");

        if ($estado_reservacion !== 'confirmada') {
            throw new Exception("La modificacion de habitaciones con check-in activo debe esperar una fase de check-in/check-out");
        }

        $habitacion_ids = array_column($habitaciones, 'id');
        if (!$this->validarHabitacionesDelHotel($habitacion_ids, $hotel_id)) {
            throw new Exception("Una o mas habitaciones no pertenecen al hotel actual");
        }
        
        // ====== CALCULAR PRECIO CON TARIFAS DINÁMICAS ======
        // Cargar modelo de tarifas
        if (!class_exists('IncrementoTarifa')) {
            require_once __DIR__ . '/IncrementoTarifa.php';
        }
        $tarifaModel = new IncrementoTarifa();
        
        // Calcular días de estancia
        $fecha_ini = new DateTime($fecha_entrada);
        $fecha_fin = new DateTime($fecha_salida);
        $noches = $fecha_ini->diff($fecha_fin)->days;
        
        // Ajuste para llegadas en madrugada
        if ($hora_llegada_estimada) {
            $hora = intval(substr($hora_llegada_estimada, 0, 2));
            if ($hora >= 0 && $hora < 12 && $noches == 0) {
                $noches = 1;
            }
        }
        
        if ($noches == 0) {
            $noches = 1;
        }
        
        error_log("Noches calculadas: $noches");
        
        // Convertir cortesías a array de strings para comparación
        $ids_cortesia = array_map('strval', $cortesias_ids);
        
        // Calcular precio total y preparar datos de habitaciones
        $precio_total = 0;
        $habitaciones_con_precio = [];
        
        foreach ($habitaciones as $habitacion) {
            $es_cortesia = in_array(strval($habitacion['id']), $ids_cortesia);
            
            if ($es_cortesia) {
                $precio_habitacion = 0;
                error_log("Habitación {$habitacion['numero']} - CORTESÍA");
            } else {
                // Calcular precio con incrementos para todas las noches
                $precio_habitacion = 0;
                $fecha_actual = clone $fecha_ini;
                
                for ($i = 0; $i < $noches; $i++) {
                    $fecha_str = $fecha_actual->format('Y-m-d');
                    
                    $calculo = $tarifaModel->calcularPrecioConIncremento(
                        $habitacion['id'],
                        $habitacion['tipo'],
                        $habitacion['precio_base'],
                        $fecha_str
                    );
                    
                    $precio_habitacion += $calculo['precio_final'];
                    $fecha_actual->modify('+1 day');
                }
                
                error_log("Habitación {$habitacion['numero']} - Precio calculado con incrementos: \${$precio_habitacion}");
            }
            
            $habitaciones_con_precio[] = [
                'id' => $habitacion['id'],
                'numero' => $habitacion['numero'],
                'precio' => $precio_habitacion,
                'es_cortesia' => $es_cortesia
            ];
            
            $precio_total += $precio_habitacion;
        }
        
        error_log("Precio total calculado: \${$precio_total}");
        // ====== FIN CÁLCULO DE PRECIO ======
        
        // ====== NUEVO: Obtener habitaciones antiguas antes de eliminarlas ======
        $sql_old = "SELECT habitacion_id FROM reservacion_habitaciones WHERE reservacion_id = ? AND hotel_id = ?";
        $stmt_old = $pdo->prepare($sql_old);
        $stmt_old->execute([$reservacion_id, $hotel_id]);
        $habitaciones_antiguas = $stmt_old->fetchAll(PDO::FETCH_COLUMN);
        
        error_log("Habitaciones antiguas: " . json_encode($habitaciones_antiguas));
        
        // Eliminar habitaciones anteriores
        $sql = "DELETE FROM reservacion_habitaciones WHERE reservacion_id = ? AND hotel_id = ?";
        $pdo->prepare($sql)->execute([$reservacion_id, $hotel_id]);
        
        error_log("Habitaciones anteriores eliminadas");
        
        // ====== NUEVO: Si la reservación está en check-in, liberar habitaciones antiguas ======
        // que ya no están en la nueva selección
        if ($estado_reservacion == 'checked_in' && !empty($habitaciones_antiguas)) {
            $nuevas_ids = array_column($habitaciones_con_precio, 'id');
            $habitaciones_a_liberar = array_diff($habitaciones_antiguas, $nuevas_ids);
            
            if (!empty($habitaciones_a_liberar)) {
                $placeholders = str_repeat('?,', count($habitaciones_a_liberar) - 1) . '?';
                $sql_liberar = "UPDATE habitaciones 
                               SET estado = 'limpieza' 
                               WHERE id IN ($placeholders)";
                $pdo->prepare($sql_liberar)->execute($habitaciones_a_liberar);
                
                error_log("Habitaciones liberadas (marcadas en limpieza): " . json_encode($habitaciones_a_liberar));
            }
        }
        
        // Insertar nuevas habitaciones CON PRECIOS CALCULADOS
        $sql = "INSERT INTO reservacion_habitaciones 
                (hotel_id, reservacion_id, habitacion_id, precio, es_cortesia)
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($habitaciones_con_precio as $hab) {
            $stmt->execute([
                $hotel_id,
                $reservacion_id,
                $hab['id'],
                $hab['precio'],
                $hab['es_cortesia'] ? 1 : 0
            ]);
            
            error_log("Habitación {$hab['numero']} guardada - Precio: \${$hab['precio']}, Cortesía: " . ($hab['es_cortesia'] ? 'Sí' : 'No'));
        }
        
        // ====== NUEVO: Si la reservación está en check-in, marcar TODAS las habitaciones como ocupadas ======
        if ($estado_reservacion == 'checked_in') {
            $habitaciones_ids = array_column($habitaciones_con_precio, 'id');
            
            if (!empty($habitaciones_ids)) {
                $placeholders = str_repeat('?,', count($habitaciones_ids) - 1) . '?';
                $sql_ocupar = "UPDATE habitaciones 
                              SET estado = 'ocupada' 
                              WHERE id IN ($placeholders)
                              AND estado IN ('disponible', 'limpieza')";
                
                $stmt_ocupar = $pdo->prepare($sql_ocupar);
                $stmt_ocupar->execute($habitaciones_ids);
                
                $habitaciones_actualizadas = $stmt_ocupar->rowCount();
                error_log("✅ FIX APLICADO: $habitaciones_actualizadas habitaciones marcadas como 'ocupada'");
            }
        }
        
        // ====== RECALCULAR precio_total desde precios reales ======
        $precio_real_actualizado = 0;
        foreach ($habitaciones_con_precio as $hpc) {
            if (!$hpc['es_cortesia']) {
                $precio_real_actualizado += $hpc['precio'];
            }
        }
        if ($precio_real_actualizado > 0) {
            $precio_total = $precio_real_actualizado;
            error_log("precio_total recalculado en actualizarHabitaciones: \${$precio_total}");
        }
        // ====== FIN RECALCULO ======

        // Actualizar reservacion
        $total_habitaciones = count($habitaciones);
        $habitaciones_cortesia = count($cortesias_ids);
        
        $sql = "UPDATE reservaciones 
                SET precio_total = ?,
                    total_habitaciones = ?,
                    habitaciones_cortesia = ?,
                    updated_at = NOW()
                WHERE id = ?
                AND hotel_id = ?";
        
        $pdo->prepare($sql)->execute([
            $precio_total,
            $total_habitaciones,
            $habitaciones_cortesia,
            $reservacion_id,
            $hotel_id
        ]);
        
        error_log("Reservación actualizada - Nuevo precio total: \${$precio_total}");
        
        $pdo->commit();
        
        error_log("=== FIN actualizarHabitaciones EXITOSO ===");
        
        return [
            'success' => true,
            'precio_anterior' => $precio_anterior,
            'precio_nuevo' => $precio_total,
            'precio_diferencia' => $precio_total - $precio_anterior
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("ERROR en actualizarHabitaciones: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}
/**
 * Intentar registrar pagos mixtos si la tabla existe
 * MÉTODO ÚNICO - NO DUPLICAR
 */
private function intentarRegistrarPagosMixtos($reservacion_id, $pagos, $monto_recibido = null, $cambio = null) {
    $db = Database::getInstance();
    
    try {
        // Verificar si la tabla existe
        $sql = "SHOW TABLES LIKE 'reservacion_pagos'";
        $stmt = $db->query($sql);
        
        if (!$stmt || $stmt->rowCount() == 0) {
            // La tabla no existe
            error_log("Tabla reservacion_pagos no existe");
            return;
        }
        
        // Primero intentar actualizar las columnas de monto_recibido y cambio
        try {
            $sql = "UPDATE reservaciones 
                    SET monto_recibido = ?, cambio = ? 
                    WHERE id = ?";
            $db->query($sql, [$monto_recibido, $cambio, $reservacion_id]);
        } catch (Exception $e) {
            // Si falla, las columnas no existen, continuar
            error_log("Columnas monto_recibido/cambio no existen en reservaciones");
        }
        
        // Eliminar pagos anteriores
        $sql = "DELETE FROM reservacion_pagos WHERE reservacion_id = ?";
        $db->query($sql, [$reservacion_id]);
        
        // Insertar nuevos pagos
        $sql = "INSERT INTO reservacion_pagos (reservacion_id, metodo_pago, monto, referencia, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        foreach ($pagos as $pago) {
            if ($pago['monto'] > 0) {
                $result = $db->query($sql, [
                    $reservacion_id,
                    $pago['metodo'],
                    $pago['monto'],
                    $pago['referencia'] ?? null
                ]);
                
                if (!$result) {
                    error_log("Error al insertar en reservacion_pagos: " . print_r($db->getConnection()->errorInfo(), true));
                }
            }
        }
        
    } catch (Exception $e) {
        // Si hay algún error, registrarlo pero no fallar el check-in
        error_log("Error al registrar pagos mixtos (no crítico): " . $e->getMessage());
    }
}
   public function obtenerPorId($id) {
    $sql = "SELECT r.*, 
            h.nombre_completo as huesped_nombre,
            h.telefono as huesped_telefono,
            h.email as huesped_correo,
            h.procedencia_estado,
            h.procedencia_ciudad,
            u.nombre_completo as usuario_registro
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
            WHERE r.id = ?
            AND r.hotel_id = ?";
    
    $hotel_id = $this->hotelIdActual();
    $stmt = $this->db->query($sql, [$id, $hotel_id]);
    $reservacion = $stmt->fetch();
    
    if ($reservacion) {
        // Obtener habitaciones con sus precios
        $sql_habitaciones = "SELECT 
                            rh.id as rh_id,
                            rh.habitacion_id,
                            rh.precio,
                            rh.es_cortesia,
                            h.numero,
                            h.tipo,
                            h.precio_base
                            FROM reservacion_habitaciones rh
                            INNER JOIN habitaciones h ON rh.habitacion_id = h.id AND h.hotel_id = rh.hotel_id
                            WHERE rh.reservacion_id = ?
                            AND rh.hotel_id = ?
                            ORDER BY h.numero";
        
        $stmt_hab = $this->db->query($sql_habitaciones, [$id, $hotel_id]);
        $reservacion['habitaciones'] = $stmt_hab->fetchAll();
        
        // Calcular total de habitaciones de cortesía
        $cortesias = 0;
        foreach ($reservacion['habitaciones'] as $hab) {
            if ($hab['es_cortesia']) {
                $cortesias++;
            }
        }
        $reservacion['habitaciones_cortesia_calculadas'] = $cortesias;
    }
    
    return $reservacion;
} 
    /**
     * Buscar reservaciones con joins (método que faltaba)
     */
    public function buscarConDetalles($termino = null, $filtros = []) {
        $sql = "SELECT r.*, 
                h.nombre_completo as huesped_nombre,
                h.telefono as huesped_telefono,
                h.procedencia_estado,
                GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                u.nombre_completo as usuario_registro
                FROM {$this->table} r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
                WHERE r.hotel_id = ?";
        
        $params = [$this->hotelIdActual()];
        
        // Aplicar búsqueda
        if ($termino) {
            $sql .= " AND (h.nombre_completo LIKE ? OR h.telefono LIKE ? OR hab.numero LIKE ?)";
            $params[] = "%{$termino}%";
            $params[] = "%{$termino}%";
            $params[] = "%{$termino}%";
        }
        
        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $sql .= " AND r.estado = ?";
            $params[] = $filtros['estado'];
        }
        
        if (!empty($filtros['fecha_entrada'])) {
            $sql .= " AND r.fecha_entrada = ?";
            $params[] = $filtros['fecha_entrada'];
        }
        
        if (!empty($filtros['fecha_salida'])) {
            $sql .= " AND r.fecha_salida = ?";
            $params[] = $filtros['fecha_salida'];
        }
        
        $sql .= " GROUP BY r.id ORDER BY r.created_at DESC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas mejoradas para el dashboard
     */
   /**
 * Obtener estadísticas para el dashboard - Compatible con pagos mixtos
 * Agregar o reemplazar este método en el modelo Reservacion
 */
public function obtenerEstadisticasDashboard() {
    $db = Database::getInstance();
    
    // Configurar zona horaria para MySQL
    $db->query("SET time_zone = '-06:00'"); // Mexico City UTC-6
    
    // 1. Entradas de hoy (confirmadas que entran hoy)
    $sql = "SELECT COUNT(*) as total 
            FROM reservaciones 
            WHERE fecha_entrada = CURDATE() 
            AND estado = 'confirmada'";
    $stmt = $db->query($sql);
    $entradas_hoy = $stmt->fetch()['total'] ?? 0;
    
    // 2. Salidas de hoy (checked_in que salen hoy)
    $sql = "SELECT COUNT(*) as total 
            FROM reservaciones 
            WHERE fecha_salida = CURDATE() 
            AND estado = 'checked_in'";
    $stmt = $db->query($sql);
    $salidas_hoy = $stmt->fetch()['total'] ?? 0;
    
    // 3. Habitaciones ocupadas actualmente
    $sql = "SELECT COUNT(DISTINCT h.id) as total 
            FROM habitaciones h
            WHERE h.estado = 'ocupada'";
    $stmt = $db->query($sql);
    $habitaciones_ocupadas = $stmt->fetch()['total'] ?? 0;
    
    // Total de habitaciones activas
    $sql = "SELECT COUNT(*) as total FROM habitaciones WHERE activa = 1";
    $stmt = $db->query($sql);
    $total_habitaciones = $stmt->fetch()['total'] ?? 1;
    
    // Porcentaje de ocupación
    $porcentaje_ocupacion = $total_habitaciones > 0 ? round(($habitaciones_ocupadas / $total_habitaciones) * 100, 1) : 0;
    
    // 4. INGRESOS DEL DÍA - Verificar primero si hay pagos mixtos
    $ingresos_dia = [
        'efectivo' => 0,
        'tarjeta' => 0,
        'transferencia' => 0,
        'total' => 0
    ];
    
    // Primero intentar con movimientos_caja (más preciso)
    $sql = "SELECT 
            metodo_pago,
            SUM(monto) as total
            FROM movimientos_caja 
            WHERE DATE(created_at) = CURDATE()
            AND tipo = 'ingreso'
            AND categoria = 'hospedaje'
            GROUP BY metodo_pago";
    
    $stmt = $db->query($sql);
    $hay_movimientos = false;
    
    while ($row = $stmt->fetch()) {
        $hay_movimientos = true;
        $metodo = $row['metodo_pago'];
        if (in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'])) {
            $ingresos_dia[$metodo] = floatval($row['total']);
            $ingresos_dia['total'] += floatval($row['total']);
        }
    }
    
    // Si no hay movimientos en caja, usar el método antiguo
    if (!$hay_movimientos) {
        $sql = "SELECT 
                COALESCE(SUM(CASE WHEN metodo_pago = 'efectivo' THEN precio_total ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta' THEN precio_total ELSE 0 END), 0) as tarjeta,
                COALESCE(SUM(CASE WHEN metodo_pago = 'transferencia' THEN precio_total ELSE 0 END), 0) as transferencia,
                COALESCE(SUM(precio_total), 0) as total
                FROM reservaciones 
                WHERE DATE(updated_at) = CURDATE()
                AND estado IN ('checked_in', 'checked_out')
                AND metodo_pago IS NOT NULL";
        
        $stmt = $db->query($sql);
        $ingresos_dia = $stmt->fetch();
    }
    
    // 5. Ingresos del mes
    $sql = "SELECT COALESCE(SUM(monto), 0) as total 
            FROM movimientos_caja 
            WHERE MONTH(created_at) = MONTH(CURDATE()) 
            AND YEAR(created_at) = YEAR(CURDATE())
            AND tipo = 'ingreso'
            AND categoria = 'hospedaje'";
    $stmt = $db->query($sql);
    $ingresos_mes_caja = $stmt->fetch()['total'] ?? 0;
    
    // Si no hay datos en movimientos_caja, usar reservaciones
    if ($ingresos_mes_caja == 0) {
        $sql = "SELECT SUM(precio_total) as total 
                FROM reservaciones 
                WHERE MONTH(created_at) = MONTH(CURDATE()) 
                AND YEAR(created_at) = YEAR(CURDATE())
                AND estado != 'cancelada'";
        $stmt = $db->query($sql);
        $ingresos_mes = $stmt->fetch()['total'] ?? 0;
    } else {
        $ingresos_mes = $ingresos_mes_caja;
    }
    
    return [
        'entradas_hoy' => $entradas_hoy,
        'salidas_hoy' => $salidas_hoy,
        'habitaciones_ocupadas' => $habitaciones_ocupadas,
        'total_habitaciones' => $total_habitaciones,
        'porcentaje_ocupacion' => $porcentaje_ocupacion,
        'ingresos_dia' => $ingresos_dia,
        'ingresos_mes' => $ingresos_mes
    ];
}
    
    /**
     * Obtener lista detallada de entradas de hoy
     */
    public function obtenerEntradasHoy() {
        $sql = "SELECT r.*, h.nombre_completo as huesped_nombre, h.telefono as huesped_telefono
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE r.fecha_entrada = CURDATE() 
                AND r.estado = 'confirmada'
                ORDER BY r.hora_llegada_estimada";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener lista detallada de salidas de hoy
     */
    public function obtenerSalidasHoy() {
        $sql = "SELECT r.*, h.nombre_completo as huesped_nombre, h.telefono as huesped_telefono
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE r.fecha_salida = CURDATE() 
                AND r.estado = 'checked_in'
                ORDER BY r.id";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Crear reservación con múltiples habitaciones
     */
    /**
     * Crear reservación con múltiples habitaciones
     */
    public function crearConHabitaciones($data, $habitaciones, $cortesias_seleccionadas = []) {
    try {
        error_log("=== INICIO crearConHabitaciones (CORRECCIÓN TARIFAS DINÁMICAS) ===");
        error_log("Datos recibidos: " . json_encode($data));
        error_log("Habitaciones: " . count($habitaciones));
        error_log("Cortesías recibidas: " . json_encode($cortesias_seleccionadas));
        
        // Verificar que las habitaciones tienen precio_calculado
        foreach ($habitaciones as $index => $hab) {
            if (!isset($hab['precio_calculado'])) {
                error_log("⚠️ ADVERTENCIA: Habitación ID {$hab['id']} no tiene precio_calculado, tiene: " . json_encode(array_keys($hab)));
            } else {
                error_log("✓ Habitación ID {$hab['id']} tiene precio_calculado: \${$hab['precio_calculado']}");
            }
        }
        
        // CRÍTICO: Obtener la conexión PDO correctamente
        $pdo = $this->db->getConnection();
        
        if (!$pdo) {
            throw new Exception("No se pudo obtener la conexión a la base de datos");
        }
        
        $pdo->beginTransaction();
        $hotel_id = $this->hotelIdActual();
        
        // Verificar campos requeridos
        $campos_requeridos = ['huesped_id', 'fecha_entrada', 'fecha_salida', 'precio_total'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                error_log("ERROR: Campo requerido faltante: " . $campo);
                throw new Exception("Campo requerido faltante: " . $campo);
            }
        }
        
        // NUEVA LÓGICA: Usar cortesías seleccionadas por el usuario
        $total_habitaciones = count($habitaciones);
        $habitaciones_cortesia = count($cortesias_seleccionadas);

        $habitacion_ids = array_column($habitaciones, 'id');
        if (!$this->validarHabitacionesDelHotel($habitacion_ids, $hotel_id)) {
            throw new Exception("Una o mas habitaciones no pertenecen al hotel actual");
        }
        
        error_log("Total habitaciones: " . $total_habitaciones);
        error_log("Habitaciones de cortesía seleccionadas por usuario: " . $habitaciones_cortesia);
        error_log("IDs de cortesías: " . json_encode($cortesias_seleccionadas));
        
        // Sin limite de cortesias: el usuario puede marcar cualquier habitacion
        $ids_cortesia = array_map('strval', $cortesias_seleccionadas);
        
        // ====== RECALCULAR precio_total desde precios reales ======
        // Evita desajuste entre calcularPrecioMultiple y precios reales por habitacion
        $precio_real_total = 0;
        foreach ($habitaciones as $habitacion) {
            $es_cort_check = in_array(strval($habitacion['id']), $ids_cortesia);
            if (!$es_cort_check) {
                $precio_real_total += $habitacion['precio_calculado'] ?? 0;
            }
        }
        if ($precio_real_total >= 0) {
            $data['precio_total'] = $precio_real_total;
            error_log("precio_total recalculado desde habitaciones: \${$precio_real_total}");
        }
        // ====== FIN RECALCULO ======

        // CRÍTICO: Crear la reservación primero
        error_log("Intentando insertar en tabla reservaciones...");
        
        $sql = "INSERT INTO reservaciones (
                    huesped_id, 
                    fecha_entrada, 
                    fecha_salida, 
                    hora_llegada_estimada, 
                    precio_total, 
                    estado, 
                    notas, 
                    usuario_registro_id, 
                    total_habitaciones, 
                    habitaciones_cortesia,
                    hotel_id,
                    created_at, 
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $params = [
            $data['huesped_id'],
            $data['fecha_entrada'],
            $data['fecha_salida'],
            $data['hora_llegada_estimada'] ?? '14:00',
            $data['precio_total'],
            $data['estado'] ?? 'confirmada',
            $data['notas'] ?? '',
            $data['usuario_registro_id'] ?? 1,
            $total_habitaciones,
            $habitaciones_cortesia,
            $hotel_id
        ];
        
        error_log("SQL: " . $sql);
        error_log("Params: " . json_encode($params));
        
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            $error = $pdo->errorInfo();
            error_log("ERROR al preparar statement: " . json_encode($error));
            throw new Exception("Error al preparar INSERT: " . $error[2]);
        }
        
        $result = $stmt->execute($params);
        
        if (!$result) {
            $error = $stmt->errorInfo();
            error_log("ERROR al ejecutar INSERT: " . json_encode($error));
            throw new Exception("Error al insertar reservación: " . $error[2]);
        }
        
        $reservacion_id = $pdo->lastInsertId();
        
        if (!$reservacion_id || $reservacion_id == 0) {
            error_log("ERROR CRÍTICO: lastInsertId() retornó: " . var_export($reservacion_id, true));
            
            // Intentar obtener el ID de otra manera
            $stmt_check = $pdo->query("SELECT LAST_INSERT_ID() as id");
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
            $reservacion_id = $row['id'] ?? null;
            
            if (!$reservacion_id) {
                throw new Exception("No se pudo obtener el ID de la reservación insertada");
            }
            
            error_log("ID obtenido mediante LAST_INSERT_ID(): " . $reservacion_id);
        }
        
        error_log("✓ Reservación creada con ID: " . $reservacion_id);
        
        // Ahora insertar las habitaciones
        error_log("Insertando habitaciones con precios calculados...");
        
        $sql_hab = "INSERT INTO reservacion_habitaciones 
                    (hotel_id, reservacion_id, habitacion_id, precio, es_cortesia)
                    VALUES (?, ?, ?, ?, ?)";
        
        $stmt_hab = $pdo->prepare($sql_hab);
        
        if (!$stmt_hab) {
            $error = $pdo->errorInfo();
            throw new Exception("Error al preparar INSERT de habitaciones: " . $error[2]);
        }
        
        foreach ($habitaciones as $habitacion) {
            // Determinar si es cortesía (comparar como string)
            $es_cortesia = in_array(strval($habitacion['id']), $ids_cortesia) ? 1 : 0;
            
            // ====== CORRECCIÓN PRINCIPAL ======
            // SIEMPRE usar precio_calculado si existe, si no, calcular en el momento
            if ($es_cortesia) {
                $precio = 0;
                error_log("Habitación {$habitacion['numero']} marcada como CORTESÍA - Precio: \$0");
            } elseif (isset($habitacion['precio_calculado']) && $habitacion['precio_calculado'] !== null) {
                // Usar el precio calculado que ya incluye incrementos
                $precio = $habitacion['precio_calculado'];
                error_log("Habitación {$habitacion['numero']} - Usando precio_calculado: \${$precio}");
            } else {
                // FALLBACK: Si por alguna razón no tiene precio_calculado, calcularlo ahora
                error_log("⚠️ ADVERTENCIA: Habitación {$habitacion['numero']} no tiene precio_calculado, calculando ahora...");
                
                // Cargar modelo de tarifas
                if (!class_exists('IncrementoTarifa')) {
                    require_once __DIR__ . '/IncrementoTarifa.php';
                }
                $tarifaModel = new IncrementoTarifa();
                
                // Calcular precio con incrementos para el periodo completo
                $fecha_inicio = new DateTime($data['fecha_entrada']);
                $fecha_fin = new DateTime($data['fecha_salida']);
                $noches = $fecha_inicio->diff($fecha_fin)->days;
                if ($noches == 0) $noches = 1;
                
                $precio_total_habitacion = 0;
                $fecha_actual = clone $fecha_inicio;
                
                for ($i = 0; $i < $noches; $i++) {
                    $fecha_str = $fecha_actual->format('Y-m-d');
                    $calculo = $tarifaModel->calcularPrecioConIncremento(
                        $habitacion['id'],
                        $habitacion['tipo'],
                        $habitacion['precio_base'],
                        $fecha_str
                    );
                    $precio_total_habitacion += $calculo['precio_final'];
                    $fecha_actual->modify('+1 day');
                }
                
                $precio = $precio_total_habitacion;
                error_log("Habitación {$habitacion['numero']} - Precio calculado en FALLBACK: \${$precio}");
            }
            // ====== FIN CORRECCIÓN ======
            
            $params_hab = [
                $hotel_id,
                $reservacion_id,
                $habitacion['id'],
                $precio,
                $es_cortesia
            ];
            
            error_log("Insertando habitación {$habitacion['numero']} (ID: {$habitacion['id']}): Precio=\${$precio}, Cortesía=" . ($es_cortesia ? 'Sí' : 'No'));
            
            $result_hab = $stmt_hab->execute($params_hab);
            
            if (!$result_hab) {
                $error = $stmt_hab->errorInfo();
                error_log("ERROR al insertar habitación: " . json_encode($error));
                throw new Exception("Error al asociar habitación: " . $error[2]);
            }
        }
        
        error_log("✓ Todas las habitaciones insertadas correctamente");
        
        $pdo->commit();
        
        error_log("✓ TRANSACCIÓN COMPLETADA - Reservación ID: " . $reservacion_id);
        error_log("=== FIN crearConHabitaciones EXITOSO ===");
        
        return [
            'success' => true,
            'id' => $reservacion_id
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("ERROR CRÍTICO en crearConHabitaciones: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        error_log("=== FIN crearConHabitaciones CON ERROR ===");
        
        throw $e;
    }
}
    
    /**
     * Verificar disponibilidad de múltiples habitaciones
     */
    public function verificarDisponibilidadMultiple($habitaciones_ids, $fecha_entrada, $fecha_salida, $excluir_reservacion_id = null) {
    if (empty($habitaciones_ids)) {
        return false;
    }

    $habitaciones_ids = $this->normalizarHabitacionIds($habitaciones_ids);
    $hotel_id = $this->hotelIdActual();

    if (!$this->validarHabitacionesDelHotel($habitaciones_ids, $hotel_id)) {
        return false;
    }

    $placeholders = str_repeat('?,', count($habitaciones_ids) - 1) . '?';
    
    // 1. Verificar conflictos con reservaciones existentes
    $sql = "SELECT habitacion_id, COUNT(*) as conflictos 
            FROM reservacion_habitaciones rh
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
            WHERE rh.habitacion_id IN ($placeholders)
            AND rh.hotel_id = ?
            AND r.hotel_id = ?
            AND r.estado IN ('confirmada', 'checked_in')
            AND ? < r.fecha_salida
            AND ? > r.fecha_entrada";
    
    $params = $habitaciones_ids;
    $params[] = $hotel_id;
    $params[] = $hotel_id;
    array_push($params, $fecha_entrada, $fecha_salida);
    
    if ($excluir_reservacion_id) {
        $sql .= " AND r.id != ?";
        $params[] = $excluir_reservacion_id;
    }
    
    $sql .= " GROUP BY habitacion_id";
    
    $stmt = $this->db->query($sql, $params);
    $conflictos = $stmt->fetchAll();
    
    if (count($conflictos) > 0) {
        return false;
    }
    
    // 2. Verificar conflictos con mantenimientos programados o en proceso
    $placeholders2 = str_repeat('?,', count($habitaciones_ids) - 1) . '?';
    $sql_mant = "SELECT DISTINCT m.habitacion_id 
                 FROM mantenimientos_habitaciones m
                 WHERE m.habitacion_id IN ($placeholders2)
                 AND m.hotel_id = ?
                 AND m.estado IN ('programado', 'en_proceso')
                 AND (
                     (m.programado = 1 AND m.fecha_programada IS NOT NULL AND (
                         (m.fecha_programada < ? AND (m.fecha_programada_fin IS NULL OR m.fecha_programada_fin > ?))
                         OR (m.fecha_programada >= ? AND m.fecha_programada < ?)
                         OR (m.fecha_programada_fin IS NOT NULL AND m.fecha_programada_fin > ? AND m.fecha_programada < ?)
                     ))
                     OR (m.estado = 'en_proceso' AND m.programado = 0)
                 )";
    
    $params_mant = $habitaciones_ids;
    $params_mant[] = $hotel_id;
    array_push($params_mant, 
        $fecha_salida, $fecha_entrada,
        $fecha_entrada, $fecha_salida,
        $fecha_entrada, $fecha_salida
    );
    
    $stmt_mant = $this->db->query($sql_mant, $params_mant);
    $conflictos_mant = $stmt_mant->fetchAll();
    
    return count($conflictos_mant) == 0;
}
    
    /**
     * Calcular precio total (simplificado - sin variaciones)
     */
    /**
 * Calcular precio múltiple con consideración de horarios especiales
 */
public function calcularPrecioMultiple($habitaciones, $fecha_entrada, $fecha_salida, $hora_llegada = null) {
    // Incluir el modelo de tarifas si no está incluido
    if (!class_exists('IncrementoTarifa')) {
        require_once __DIR__ . '/IncrementoTarifa.php';
    }
    
    $tarifaModel = new IncrementoTarifa();
    
    $fecha_inicio = new DateTime($fecha_entrada);
    $fecha_fin = new DateTime($fecha_salida);
    $noches = $fecha_inicio->diff($fecha_fin)->days;
    
    // Ajuste especial para llegadas en madrugada
    if ($hora_llegada) {
        $hora = intval(substr($hora_llegada, 0, 2));
        
        // Si llega entre 00:00 y 12:00 y las fechas son iguales
        if ($hora >= 0 && $hora < 12 && $noches == 0) {
            // Es una estadía de madrugada, se cobra como una noche
            $noches = 1;
        }
    }
    
    if ($noches == 0) {
        $noches = 1; // Mínimo una noche
    }
    
    // Calcular precio total sin descuentos pero CON incrementos de tarifas
    $precio_total = 0;
    $habitaciones_con_precio_calculado = [];
    
    foreach ($habitaciones as $key => $habitacion) {
        $precio_por_noche = 0;
        
        // Calcular precio para cada noche considerando incrementos de tarifa
        $fecha_actual = clone $fecha_inicio;
        for ($i = 0; $i < $noches; $i++) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            // Calcular precio con incrementos para esta fecha específica
            $precio_info = $tarifaModel->calcularPrecioConIncremento(
                $habitacion['id'],
                $habitacion['tipo'],
                $habitacion['precio_base'],
                $fecha_str
            );
            
            $precio_por_noche += $precio_info['precio_final'];
            $fecha_actual->modify('+1 day');
        }
        
        // Guardar el precio calculado para esta habitación
        $habitaciones[$key]['precio_calculado'] = $precio_por_noche;
        $habitaciones[$key]['precio_por_noche'] = $precio_por_noche / $noches;
        $habitaciones_con_precio_calculado[] = $habitaciones[$key];
        
        $precio_total += $precio_por_noche;
    }
    
    // Precio sin descuento (pero con incrementos de tarifa aplicados)
    $precio_sin_descuento = $precio_total;
    
    // Las cortesias las selecciona el usuario manualmente en el formulario.
    // calcularPrecioMultiple solo devuelve el precio bruto (suma de todas las habitaciones).
    // El descuento se aplica en crearConHabitaciones / actualizarHabitaciones segun seleccion.
    $habitaciones_cortesia = floor(count($habitaciones) / 11); // solo para mostrar en UI
    
    return [
        'precio_total' => $precio_total,
        'noches' => $noches,
        'habitaciones_cortesia' => $habitaciones_cortesia,
        'precio_sin_descuento' => $precio_sin_descuento,
        'es_madrugada' => ($hora_llegada && $hora >= 0 && $hora < 12),
        'descuento_cortesia' => $precio_sin_descuento - $precio_total,
        'habitaciones_detalle' => $habitaciones_con_precio_calculado,
        'habitaciones_necesarias_proxima_cortesia' => $this->calcularHabitacionesParaProximaCortesia(count($habitaciones))
    ];
}
  private function calcularHabitacionesParaProximaCortesia($total_habitaciones) {
    $siguiente_cortesia = (floor($total_habitaciones / 11) + 1) * 11;
    return $siguiente_cortesia - $total_habitaciones;
}  
    /**
     * Obtener habitaciones de una reservación
     */
    public function getHabitaciones($reservacion_id) {
        $sql = "SELECT 
                rh.id,
                rh.habitacion_id,
                rh.precio,
                rh.es_cortesia,
                h.numero,
                h.tipo,
                h.piso,
                h.precio_base,
                h.caracteristicas,
h.observaciones
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id AND h.hotel_id = rh.hotel_id
                INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND r.hotel_id = rh.hotel_id
                WHERE rh.reservacion_id = ?
                AND rh.hotel_id = ?
                ORDER BY CAST(h.numero AS UNSIGNED)";
        
        try {
            $stmt = $this->db->query($sql, [$reservacion_id, $this->hotelIdActual()]);
            $result = $stmt->fetchAll();
            return $result;
        } catch (Exception $e) {
            error_log("ERROR en getHabitaciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Hacer check-in
     */
    public function checkIn($reservacion_id, $hora_entrada = null) {
        $hora = $hora_entrada ?? date('H:i:s');
        $hotel_id = $this->hotelIdActual();
        
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Actualizar reservación
            $sql = "UPDATE reservaciones
                    SET estado = 'checked_in',
                        hora_entrada = ?,
                        updated_at = NOW()
                    WHERE id = ?
                    AND hotel_id = ?
                    AND estado = 'confirmada'";
            $stmt = $db->query($sql, [$hora, $reservacion_id, $hotel_id]);
            $result = $stmt && $stmt->rowCount() > 0;
            
            if ($result) {
                // Actualizar estado de todas las habitaciones
                $sql = "UPDATE habitaciones h
        INNER JOIN reservacion_habitaciones rh
            ON h.id = rh.habitacion_id
            AND h.hotel_id = rh.hotel_id
        SET h.estado = 'ocupada'
        WHERE rh.reservacion_id = ?
        AND rh.hotel_id = ?
        AND h.hotel_id = ?
        AND h.estado IN ('disponible', 'limpieza')";
                
                $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id]);
            }
            
            $db->commit();
            return $result;
            
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
    
    /**
     * Hacer check-out
     */
   /**
 * Hacer check-out - Agregar o reemplazar este método en el modelo Reservacion
 */
public function checkOut($reservacion_id, $hora_salida = null) {
    $hora = $hora_salida ?? date('H:i:s');
    $hotel_id = $this->hotelIdActual();
    
    $db = Database::getInstance();
    
    try {
        // NO iniciar transacción aquí para evitar conflictos
        
        // 1. Actualizar reservación
        $sql = "UPDATE reservaciones 
                SET estado = 'checked_out', 
                    hora_salida = ?
                WHERE id = ?
                AND hotel_id = ?
                AND estado = 'checked_in'"; // Solo si está en check-in
        
        $stmt = $db->query($sql, [$hora, $reservacion_id, $hotel_id]);
        
        // Verificar si se actualizó algún registro
        if ($stmt && $stmt->rowCount() > 0) {
            // 2. Liberar habitaciones — SOLO si no están ocupadas por OTRA reservación activa
            $sql = "UPDATE habitaciones h
                    INNER JOIN reservacion_habitaciones rh
                        ON h.id = rh.habitacion_id
                        AND h.hotel_id = rh.hotel_id
                    SET h.estado = 'limpieza'
                    WHERE rh.reservacion_id = ?
                    AND rh.hotel_id = ?
                    AND h.hotel_id = ?
                    AND h.estado = 'ocupada'
                    AND NOT EXISTS (
                        SELECT 1 FROM reservacion_habitaciones rh2
                        INNER JOIN reservaciones r2
                            ON rh2.reservacion_id = r2.id
                            AND r2.hotel_id = rh2.hotel_id
                        WHERE rh2.habitacion_id = h.id
                        AND rh2.hotel_id = ?
                        AND r2.hotel_id = ?
                        AND r2.id != ?
                        AND r2.estado = 'checked_in'
                    )";
            
            $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id, $hotel_id, $hotel_id, $reservacion_id]);
            
            return true;
        } else {
            error_log("No se pudo actualizar la reservación $reservacion_id. Puede que no esté en estado checked_in");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error en checkOut del modelo: " . $e->getMessage());
        return false;
    }
}
    
    /**
     * Cancelar reservación
     */
    public function cancelar($id, $razon) {
    $db = Database::getInstance();
    
    try {
        $db->beginTransaction();
        
        // 1. Obtener información de la reservación antes de cancelar
        $reservacion = $this->find($id);
        if (!$reservacion) {
            throw new Exception("Reservación no encontrada");
        }
        
        error_log("=== INICIO CANCELACIÓN RESERVACIÓN #$id ===");
        error_log("Estado actual: " . $reservacion['estado']);
        
        // 2. Si la reservación estaba en check-in, revertir TODOS los pagos de caja
        if ($reservacion['estado'] == 'checked_in') {
            
            // Verificar que haya una caja abierta
            $cajaModel = new Caja();
            $corteActual = $cajaModel->obtenerCorteActual();
            
            if (!$corteActual) {
                throw new Exception("No se puede cancelar. Debe abrir la caja primero para procesar la devolución.");
            }
            
            // Buscar TODOS los movimientos de ingreso de esta reservación
            $sql = "SELECT id, monto, metodo_pago, categoria_id, descripcion, corte_id 
                    FROM movimientos_caja 
                    WHERE reservacion_id = ? 
                    AND tipo = 'ingreso'
                    ORDER BY metodo_pago";
            
            $stmt = $db->query($sql, [$id]);
            $movimientos = $stmt->fetchAll();
            
            error_log("Movimientos de ingreso encontrados: " . count($movimientos));
            foreach ($movimientos as $mov) {
                error_log("- " . $mov['metodo_pago'] . ": $" . $mov['monto'] . " (corte: " . $mov['corte_id'] . ")");
            }
            
            // Verificar si los movimientos están en el corte actual o en cortes cerrados
            $hay_movimientos_en_corte_cerrado = false;
            $hay_movimientos_en_corte_actual = false;
            foreach ($movimientos as $mov) {
                if ($mov['corte_id'] != $corteActual['id']) {
                    $hay_movimientos_en_corte_cerrado = true;
                } else {
                    $hay_movimientos_en_corte_actual = true;
                }
            }
            
            // Obtener o crear categoría de devolución
            $sql = "SELECT id FROM categorias_movimientos 
                    WHERE nombre = 'Devoluciones' 
                    AND tipo = 'egreso' 
                    AND activa = 1 
                    LIMIT 1";
            $stmt = $db->query($sql);
            $categoria = $stmt->fetch();
            
            if (!$categoria) {
                // Si no existe, intentar crearla
                try {
                    $sql = "INSERT INTO categorias_movimientos 
                            (nombre, tipo, descripcion, icono, color, activa, created_at) 
                            VALUES ('Devoluciones', 'egreso', 'Devoluciones por cancelaciones', 
                                    'fas fa-undo', '#EF4444', 1, NOW())";
                    $db->query($sql);
                    $categoria_id = $db->lastInsertId();
                    error_log("Categoría Devoluciones creada con ID: $categoria_id");
                } catch (Exception $e) {
                    // Si falla, usar la primera categoría de egreso disponible
                    $sql = "SELECT id FROM categorias_movimientos 
                            WHERE tipo = 'egreso' AND activa = 1 
                            ORDER BY id LIMIT 1";
                    $stmt = $db->query($sql);
                    $cat_temp = $stmt->fetch();
                    $categoria_id = $cat_temp ? $cat_temp['id'] : 1;
                    error_log("Usando categoría de egreso alternativa: $categoria_id");
                }
            } else {
                $categoria_id = $categoria['id'];
            }
            
            // Revertir pagos con egresos
            $total_devuelto = 0;
            $metodos_devueltos = [];
            $usuario_id = user_id();
            
            foreach ($movimientos as $mov) {

                if ($mov['corte_id'] != $corteActual['id']) {
                    // El movimiento está en un corte CERRADO (ya fue auditado/cerrado).
                    // NO se borra: eso alteraría retroactivamente un corte histórico.
                    // Se registra una devolución en el corte ACTUAL para que quede
                    // el rastro contable correcto: ingreso en sábado + egreso hoy.
                    $descripcion_dev = "Devolución por cancelación (pago corte #" . $mov['corte_id'] .
                                       ") - Reservación #" . $id .
                                       " (" . ucfirst($mov['metodo_pago']) . ")";

                    $sql_dev = "INSERT INTO movimientos_caja
                                (tipo, categoria, categoria_id, descripcion, monto,
                                 metodo_pago, referencia, reservacion_id, usuario_id,
                                 corte_id, created_at)
                                VALUES ('gasto', 'Devoluciones', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                    $db->query($sql_dev, [
                        $categoria_id,
                        $descripcion_dev,
                        $mov['monto'],
                        $mov['metodo_pago'],
                        'Cancelación - pago en corte cerrado #' . $mov['corte_id'],
                        $id,
                        $usuario_id,
                        $corteActual['id']
                    ]);

                    $total_devuelto += $mov['monto'];
                    $metodos_devueltos[$mov['metodo_pago']] =
                        ($metodos_devueltos[$mov['metodo_pago']] ?? 0) + $mov['monto'];

                    error_log("Devolución creada en corte actual #{$corteActual['id']} por pago en corte cerrado #{$mov['corte_id']} - " .
                              $mov['metodo_pago'] . " $" . $mov['monto']);
                    continue;
                }
                
                // El movimiento está en el corte ACTUAL → devolución normal
                $sql = "INSERT INTO movimientos_caja 
                        (tipo, categoria, categoria_id, descripcion, monto, 
                         metodo_pago, referencia, reservacion_id, usuario_id, 
                         corte_id, created_at) 
                        VALUES ('gasto', 'Devoluciones', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $descripcion = "Devolución por cancelación - Reservación #" . $id . 
                              " (" . ucfirst($mov['metodo_pago']) . ")";
                
                $params = [
                    $categoria_id,
                    $descripcion,
                    $mov['monto'],
                    $mov['metodo_pago'],
                    null,
                    $id,
                    $usuario_id,
                    $corteActual['id']
                ];
                
                $result = $db->query($sql, $params);
                
                if (!$result) {
                    $error = $db->getConnection()->errorInfo();
                    throw new Exception("Error al registrar devolución para " . $mov['metodo_pago'] . ": " . $error[2]);
                }
                
                $total_devuelto += $mov['monto'];
                $metodos_devueltos[$mov['metodo_pago']] = 
                    ($metodos_devueltos[$mov['metodo_pago']] ?? 0) + $mov['monto'];
                
                error_log("Egreso registrado exitosamente: " . $mov['metodo_pago'] . " - $" . $mov['monto']);
            }
            
            // Verificar que se hayan procesado devoluciones
            if ($total_devuelto == 0 && $reservacion['precio_total'] > 0) {
                error_log("ADVERTENCIA: No se procesaron devoluciones pero había un precio total de $" . $reservacion['precio_total']);
            }
        }
        
        // 3. Liberar las habitaciones si estaban ocupadas
        // PROTECCIÓN: No cambiar si otra reservación activa ocupa la habitación
        if ($reservacion['estado'] == 'checked_in') {
            $sql = "UPDATE habitaciones h
                    INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
                    SET h.estado = 'disponible'
                    WHERE rh.reservacion_id = ? 
                    AND h.estado = 'ocupada'
                    AND NOT EXISTS (
                        SELECT 1 FROM reservacion_habitaciones rh2
                        INNER JOIN reservaciones r2 ON rh2.reservacion_id = r2.id
                        WHERE rh2.habitacion_id = h.id
                        AND r2.id != ?
                        AND r2.estado = 'checked_in'
                    )";
            
            $stmt = $db->query($sql, [$id, $id]);
            $habitaciones_liberadas = $stmt->rowCount();
            
            error_log("Habitaciones liberadas: " . $habitaciones_liberadas);
        }
        
        if ($reservacion['estado'] == 'checked_in') {
            error_log("Procesando devolución de inventario para reservación cancelada #$id");
            
            $resultado_inventario = $this->devolverInventarioCancelacion($id);

            if (!$resultado_inventario['success']) {
                throw new Exception("Error al devolver inventario: " . ($resultado_inventario['error'] ?? 'Error desconocido'));
            }

            if ($resultado_inventario['success'] && $resultado_inventario['productos_devueltos'] > 0) {
                error_log("Se devolvieron {$resultado_inventario['productos_devueltos']} productos al inventario");
                
                // Agregar información a la nota de cancelación
                $nota_inventario = "\n\nINVENTARIO DEVUELTO:";
                foreach ($resultado_inventario['detalles'] as $det) {
                    $nota_inventario .= "\n- {$det['cantidad']} {$det['producto']}";
                }
                
                // Actualizar la nota de cancelación con la información de inventario
                $nota_cancelacion .= $nota_inventario;
            }
        }
        
        // 4. Actualizar el estado de la reservación
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Usuario';
        $nota_cancelacion = "\n\n[CANCELADA] " . date('Y-m-d H:i:s') . 
                           " - Por: " . $usuario_nombre . 
                           "\nRazón: " . $razon;
        
        if (isset($total_devuelto) && $total_devuelto > 0) {
            $nota_cancelacion .= "\n\nDEVOLUCIÓN PROCESADA:";
            $nota_cancelacion .= "\nTotal devuelto: $" . number_format($total_devuelto, 2);
            foreach ($metodos_devueltos as $metodo => $monto) {
                $nota_cancelacion .= "\n- " . ucfirst($metodo) . ": $" . number_format($monto, 2);
            }
            if (isset($hay_movimientos_en_corte_cerrado) && $hay_movimientos_en_corte_cerrado) {
                $nota_cancelacion .= "\nNota: El pago original fue de un turno anterior. No se afectó la caja actual.";
            }
        }
        
        $sql = "UPDATE reservaciones 
                SET estado = 'cancelada',
                    notas = CONCAT(IFNULL(notas, ''), ?)
                WHERE id = ?";
        
        $stmt = $db->query($sql, [$nota_cancelacion, $id]);
        
        if (!$stmt || $stmt->rowCount() == 0) {
            throw new Exception("No se pudo actualizar el estado de la reservación");
        }
        
        // 5. Cancelar solicitudes de factura pendientes de esta reservación
        $sql = "UPDATE solicitudes_factura 
                SET estatus = 'cancelada',
                    notas = CONCAT(IFNULL(notas, ''), ?)
                WHERE reservacion_id = ? 
                AND estatus IN ('pendiente', 'en_proceso')";
        
        $nota_factura = "\n[CANCELADA AUTOMÁTICAMENTE] " . date('d/m/Y H:i') . 
                       " - Reservación #" . $id . " fue cancelada. Razón: " . $razon;
        
        $stmt_factura = $db->query($sql, [$nota_factura, $id]);
        $facturas_canceladas = $stmt_factura ? $stmt_factura->rowCount() : 0;
        
        if ($facturas_canceladas > 0) {
            error_log("Solicitudes de factura canceladas automáticamente: " . $facturas_canceladas);
        }
        
        // 6. Confirmar todos los cambios
        $db->commit();
        
        error_log("=== CANCELACIÓN COMPLETADA EXITOSAMENTE ===");
        error_log("Total devuelto: $" . ($total_devuelto ?? 0));
        
        // Retornar información detallada
        return [
            'success' => true,
            'total_devuelto' => $total_devuelto ?? 0,
            'metodos_devueltos' => $metodos_devueltos ?? [],
            'habitaciones_liberadas' => $habitaciones_liberadas ?? 0
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("ERROR CRÍTICO en cancelar: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Método auxiliar para verificar si existe la categoría de devoluciones
 * Agregar este método al modelo Reservacion
 */
private function obtenerCategoriaDevolucion() {
    $db = Database::getInstance();
    
    // Buscar categoría de devoluciones
    $sql = "SELECT id FROM categorias_movimientos 
            WHERE nombre = 'Devoluciones' 
            AND tipo = 'egreso' 
            AND activa = 1 
            LIMIT 1";
    
    $stmt = $db->query($sql);
    $categoria = $stmt->fetch();
    
    if ($categoria) {
        return $categoria['id'];
    }
    
    // Si no existe, intentar crearla
    $sql = "INSERT INTO categorias_movimientos 
            (nombre, tipo, activa, created_at) 
            VALUES ('Devoluciones', 'egreso', 1, NOW())";
    
    try {
        $db->query($sql);
        return $db->lastInsertId();
    } catch (Exception $e) {
        // Si falla la creación, usar una categoría genérica
        // Buscar cualquier categoría de egreso activa
        $sql = "SELECT id FROM categorias_movimientos 
                WHERE tipo = 'egreso' 
                AND activa = 1 
                LIMIT 1";
        $stmt = $db->query($sql);
        $categoria = $stmt->fetch();
        
        return $categoria['id'] ?? 1; // Default si no hay ninguna
    }
}
    
    /**
     * Obtener reservaciones para calendario
     */
    /**
 * Obtener reservaciones para calendario - VERSIÓN CORREGIDA
 * Incluye TODAS las reservaciones del mes, sin importar su estado
 */
 
 
 // En el modelo Reservacion.php, agregar un método específico
public function findConUsuario($id) {
    $sql = "SELECT r.*, u.nombre_completo as usuario_registro
            FROM {$this->table} r
            LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
            WHERE r.id = ?";
    
    $stmt = $this->db->query($sql, [$id]);
    return $stmt->fetch();
}
public function paraCalendario($mes = null, $año = null) {
    $mes = $mes ?? date('m');
    $año = $año ?? date('Y');
    
    // Query modificada para incluir TODOS los estados
    $sql = "SELECT r.*, 
            h.nombre_completo as huesped_nombre,
            GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
            GROUP_CONCAT(DISTINCT hab.id) as habitaciones_ids
            FROM {$this->table} r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
            WHERE r.hotel_id = ?
            AND (
                -- Reservaciones que COMIENZAN en el mes
                (MONTH(r.fecha_entrada) = ? AND YEAR(r.fecha_entrada) = ?)
                OR
                -- Reservaciones que TERMINAN en el mes
                (MONTH(r.fecha_salida) = ? AND YEAR(r.fecha_salida) = ?)
                OR
                -- Reservaciones que ABARCAN todo el mes
                (r.fecha_entrada < CONCAT(?, '-', LPAD(?, 2, '0'), '-01') 
                 AND r.fecha_salida > LAST_DAY(CONCAT(?, '-', LPAD(?, 2, '0'), '-01')))
            )
            -- REMOVIDO: AND r.estado IN ('confirmada', 'checked_in')
            -- Ahora incluye TODOS los estados
            GROUP BY r.id
            ORDER BY r.fecha_entrada, habitaciones_numeros";
    
    // Parámetros actualizados para cubrir todos los casos
    $params = [$this->hotelIdActual(), $mes, $año, $mes, $año, $año, $mes, $año, $mes];
    
    $stmt = $this->db->query($sql, $params);
    return $stmt->fetchAll();
}

    /**
     * Obtener reservaciones con check-in pendiente (pasadas de fecha)
     * Estas son reservaciones confirmadas cuya fecha de entrada ya pasó pero aún no se hizo el check-in
     */
    public function getCheckInsPendientes() {
        $sql = "SELECT 
                    r.id,
                    r.huesped_id,
                    r.fecha_entrada,
                    r.hora_llegada_estimada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                FROM {$this->table} r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                WHERE r.estado = 'confirmada'
                AND r.fecha_entrada < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_entrada
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Obtener reservaciones con check-out pendiente (pasadas de fecha)
     * Estas son reservaciones activas (checked_in) cuya fecha de salida ya pasó pero aún no se hizo el check-out
     */
    public function getCheckOutsPendientes() {
        $sql = "SELECT 
                    r.id,
                    r.huesped_id,
                    r.fecha_salida,
                    r.hora_entrada,
                    r.precio_total,
                    h.nombre_completo,
                    h.telefono,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                FROM {$this->table} r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                WHERE r.estado = 'checked_in'
                AND r.fecha_salida < CURDATE()
                GROUP BY r.id
                ORDER BY r.fecha_salida
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * ============================================================================
     * SISTEMA DE CHECK-IN TARDÍO
     * ============================================================================
     */

    /**
     * Verificar si una reservación puede hacer check-in tardío
     * @param int $reservacion_id
     * @return array con información del estado
     */
    public function verificarEstadoCheckIn($reservacion_id) {
        $hotel_id = $this->hotelIdActual();
        $stmt_reservacion = $this->db->query(
            "SELECT * FROM reservaciones WHERE id = ? AND hotel_id = ?",
            [$reservacion_id, $hotel_id]
        );
        $reservacion = $stmt_reservacion ? $stmt_reservacion->fetch() : null;
        
        if (!$reservacion) {
            return [
                'puede_checkin' => false,
                'tipo' => null,
                'motivo' => 'Reservación no encontrada'
            ];
        }
        
        // Ya tiene check-in
        if ($reservacion['estado'] == 'checked_in') {
            return [
                'puede_checkin' => false,
                'tipo' => null,
                'motivo' => 'Ya tiene check-in registrado'
            ];
        }
        
        // Cancelada o con check-out
        if (in_array($reservacion['estado'], ['cancelada', 'checked_out'])) {
            return [
                'puede_checkin' => false,
                'tipo' => null,
                'motivo' => 'Reservación ' . $reservacion['estado']
            ];
        }
        
        $hoy = date('Y-m-d');
        $fecha_entrada = $reservacion['fecha_entrada'];
        $fecha_salida = $reservacion['fecha_salida'];
        
        // CASO 1: Check-in normal (en fecha o tardío dentro del periodo)
        if ($hoy >= $fecha_entrada && $hoy < $fecha_salida) {
            $dias_retraso = (strtotime($hoy) - strtotime($fecha_entrada)) / (60 * 60 * 24);
            
            return [
                'puede_checkin' => true,
                'tipo' => 'normal_tardio',
                'motivo' => $dias_retraso > 0 ? "Check-in tardío ($dias_retraso día(s) de retraso)" : 'Check-in en fecha',
                'dias_retraso' => $dias_retraso,
                'requiere_confirmacion' => $dias_retraso > 0,
                'reservacion' => $reservacion
            ];
        }
        
        // CASO 2: Ya pasó la fecha de salida - Check-in/Check-out Express
        if ($hoy >= $fecha_salida) {
            $dias_pasados = (strtotime($hoy) - strtotime($fecha_salida)) / (60 * 60 * 24);
            
            return [
                'puede_checkin' => true,
                'tipo' => 'express',
                'motivo' => "La fecha de salida ya pasó ($dias_pasados día(s)). Se hará check-in y check-out automático.",
                'dias_pasados' => $dias_pasados,
                'requiere_confirmacion' => true,
                'es_express' => true,
                'reservacion' => $reservacion
            ];
        }
        
        // CASO 3: Antes de la fecha de entrada
        return [
            'puede_checkin' => false,
            'tipo' => null,
            'motivo' => 'La fecha de entrada aún no ha llegado',
            'fecha_entrada' => $fecha_entrada
        ];
    }

    /**
     * Realizar check-in tardío normal (dentro del periodo de reservación)
     */
    public function checkInTardio($reservacion_id, $hora_entrada = null, $notas_adicionales = null) {
        $verificacion = $this->verificarEstadoCheckIn($reservacion_id);
        $hotel_id = $this->hotelIdActual();
        
        if (!$verificacion['puede_checkin']) {
            throw new Exception($verificacion['motivo']);
        }
        
        if ($verificacion['tipo'] != 'normal_tardio') {
            throw new Exception('Use checkInCheckOutExpress para reservaciones fuera de fecha');
        }
        
        $hora = $hora_entrada ?? date('H:i:s');
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Preparar nota de check-in tardío
            $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
            $nota = "\n\n[CHECK-IN TARDÍO] " . date('Y-m-d H:i:s') . " - Por: " . $usuario_nombre;
            
            if ($verificacion['dias_retraso'] > 0) {
                $nota .= "\n⚠️ Registrado con " . $verificacion['dias_retraso'] . " día(s) de retraso";
            }
            
            if ($notas_adicionales) {
                $nota .= "\nMotivo: " . $notas_adicionales;
            }
            
            // Actualizar reservación
            $sql = "UPDATE reservaciones 
                    SET estado = 'checked_in',
                        hora_entrada = ?,
                        notas = CONCAT(IFNULL(notas, ''), ?)
                    WHERE id = ?
                    AND hotel_id = ?";
            
            $db->query($sql, [$hora, $nota, $reservacion_id, $hotel_id]);
            
            // Actualizar habitaciones a ocupadas
            $sql = "UPDATE habitaciones h
                    INNER JOIN reservacion_habitaciones rh
                        ON h.id = rh.habitacion_id
                        AND h.hotel_id = rh.hotel_id
                    SET h.estado = 'ocupada'
                    WHERE rh.reservacion_id = ?
                    AND rh.hotel_id = ?
                    AND h.hotel_id = ?";
            
            $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id]);
            
            $db->commit();
            
            error_log("✓ Check-in tardío exitoso - Reservación: $reservacion_id");
            
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("ERROR en checkInTardio: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Realizar check-in y check-out EXPRESS cuando ya pasó la fecha
     */
    public function checkInCheckOutExpress($reservacion_id, $pagos = [], $notas_adicionales = null) {
        $verificacion = $this->verificarEstadoCheckIn($reservacion_id);
        $hotel_id = $this->hotelIdActual();
        
        if (!$verificacion['puede_checkin']) {
            throw new Exception($verificacion['motivo']);
        }
        
        if ($verificacion['tipo'] != 'express') {
            throw new Exception('Esta reservación no requiere proceso express');
        }
        
        $reservacion = $verificacion['reservacion'];
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
            $hora_actual = date('H:i:s');
            
            // FASE 1: CHECK-IN EXPRESS
            $nota_checkin = "\n\n[CHECK-IN EXPRESS] " . date('Y-m-d H:i:s') . " - Por: " . $usuario_nombre;
            $nota_checkin .= "\n⚠️ Proceso automático - Reservación vencida hace " . $verificacion['dias_pasados'] . " día(s)";
            
            if ($notas_adicionales) {
                $nota_checkin .= "\nMotivo: " . $notas_adicionales;
            }
            
            // Marcar como checked_in temporalmente
            $sql = "UPDATE reservaciones 
                    SET estado = 'checked_in',
                        hora_entrada = ?,
                        notas = CONCAT(IFNULL(notas, ''), ?)
                    WHERE id = ?
                    AND hotel_id = ?";
            
            $db->query($sql, [
                $reservacion['hora_llegada_estimada'] ?? '14:00:00',
                $nota_checkin,
                $reservacion_id,
                $hotel_id
            ]);
            
            // FASE 2: REGISTRAR PAGOS (SI HAY)
            if (!empty($pagos) && $reservacion['precio_total'] > 0) {
                $cajaModel = new Caja();
                $corteActual = $cajaModel->obtenerCorteActual();
                
                if (!$corteActual) {
                    throw new Exception("Debe abrir la caja para registrar los pagos");
                }
                
                $usuario_id = user_id();
                
                // Obtener categoria_id de Hospedaje
                $sql = "SELECT id FROM categorias WHERE nombre = 'Hospedaje' AND tipo = 'ingreso' LIMIT 1";
                $stmt = $db->query($sql);
                $categoria = $stmt->fetch();
                $categoria_id = $categoria ? $categoria['id'] : null;
                
                // Registrar cada pago
                foreach ($pagos as $pago) {
                    if ($pago['monto'] > 0) {
                        $sql = "INSERT INTO movimientos_caja 
                                (tipo, categoria, categoria_id, descripcion, monto, metodo_pago, 
                                 referencia, reservacion_id, usuario_id, corte_id, created_at) 
                                VALUES ('ingreso', 'Hospedaje', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $descripcion = "Hospedaje EXPRESS - Reservación #" . $reservacion_id;
                        
                        $db->query($sql, [
                            $categoria_id,
                            $descripcion,
                            $pago['monto'],
                            $pago['metodo'],
                            $pago['referencia'] ?? null,
                            $reservacion_id,
                            $usuario_id,
                            $corteActual['id']
                        ]);
                    }
                }
                
                // Registrar pagos mixtos si existe la tabla
                try {
                    $this->registrarPagosMixtos($reservacion_id, $pagos);
                } catch (Exception $e) {
                    error_log("Advertencia pagos mixtos: " . $e->getMessage());
                }
            }
            
            // FASE 3: CHECK-OUT EXPRESS INMEDIATO
            $nota_checkout = "\n\n[CHECK-OUT EXPRESS] " . date('Y-m-d H:i:s') . " - Por: " . $usuario_nombre;
            $nota_checkout .= "\n✓ Proceso completado automáticamente (check-in + check-out)";
            
            // Actualizar a checked_out
            $sql = "UPDATE reservaciones 
                    SET estado = 'checked_out',
                        hora_salida = ?,
                        notas = CONCAT(IFNULL(notas, ''), ?)
                    WHERE id = ?
                    AND hotel_id = ?";
            
            $db->query($sql, [$hora_actual, $nota_checkout, $reservacion_id, $hotel_id]);
            
            // Liberar habitaciones
            $sql = "UPDATE habitaciones h
                    INNER JOIN reservacion_habitaciones rh
                        ON h.id = rh.habitacion_id
                        AND h.hotel_id = rh.hotel_id
                    SET h.estado = 'limpieza'
                    WHERE rh.reservacion_id = ?
                    AND rh.hotel_id = ?
                    AND h.hotel_id = ?";
            
            $db->query($sql, [$reservacion_id, $hotel_id, $hotel_id]);
            
            $db->commit();
            
            error_log("✓ CHECK-IN/CHECK-OUT EXPRESS EXITOSO - Reservación: $reservacion_id");
            
            return [
                'success' => true,
                'tipo' => 'express',
                'mensaje' => 'Check-in y check-out completados automáticamente',
                'dias_pasados' => $verificacion['dias_pasados'],
                'pagos_registrados' => !empty($pagos)
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("ERROR en checkInCheckOutExpress: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener reservaciones que necesitan check-in tardío
     */
    public function obtenerReservacionesSinCheckIn() {
        $hoy = date('Y-m-d');
        
        $sql = "SELECT 
                    r.id,
                    r.fecha_entrada,
                    r.fecha_salida,
                    r.precio_total,
                    h.nombre_completo as huesped,
                    GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(?, r.fecha_entrada) as dias_retraso,
                    CASE 
                        WHEN ? >= r.fecha_salida THEN 'express'
                        ELSE 'tardio'
                    END as tipo_pendiente
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                WHERE r.estado = 'confirmada'
                AND r.fecha_entrada <= ?
                GROUP BY r.id
                ORDER BY r.fecha_entrada ASC";
        
        $db = Database::getInstance();
        $stmt = $db->query($sql, [$hoy, $hoy, $hoy]);
        
        return $stmt->fetchAll();
    }
    /**
     * Crear solicitud de factura
     */
    public function crearSolicitudFactura($datos) {
        $db = Database::getInstance();
        $hotel_id = isset($datos['hotel_id']) ? (int)$datos['hotel_id'] : (int)$this->hotelIdActual();
        
        try {
            $stmt_reservacion = $db->query(
                "SELECT id, hotel_id FROM reservaciones WHERE id = ? AND hotel_id = ? LIMIT 1",
                [$datos['reservacion_id'], $hotel_id]
            );
            $reservacion = $stmt_reservacion ? $stmt_reservacion->fetch(PDO::FETCH_ASSOC) : null;

            if (!$reservacion) {
                throw new Exception("Reservación no encontrada para el hotel actual");
            }

            $sql = "INSERT INTO solicitudes_factura 
                    (reservacion_id, hotel_id, requiere_factura, tipo, estatus,
                     metodo_pago_principal, monto_total, usuario_registro_id, notas, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $datos['reservacion_id'],
                $hotel_id,
                $datos['requiere_factura'],
                $datos['tipo'],
                $datos['estatus'],
                $datos['metodo_pago_principal'] ?? 'efectivo',
                $datos['monto_total'] ?? 0,
                $datos['usuario_registro_id'] ?? null,
                $datos['notas'] ?? null
            ];
            
            $stmt = $db->query($sql, $params);
            
            if ($stmt) {
                $id = $db->lastInsertId();
                error_log("✅ Solicitud de factura creada - ID: $id, Reservación: {$datos['reservacion_id']}, Tipo: {$datos['tipo']}");
                return $id;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error al crear solicitud de factura: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener solicitud de factura por reservación
     */
    public function obtenerSolicitudFactura($reservacion_id) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM solicitudes_factura WHERE reservacion_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->query($sql, [$reservacion_id]);
        
        return $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    }
    
    /**
     * Obtener todas las solicitudes de factura pendientes
     */
    public function obtenerSolicitudesPendientes($tipo = null) {
        $db = Database::getInstance();
        
        $sql = "SELECT sf.*, r.precio_total, r.fecha_entrada, r.fecha_salida,
                       h.nombre as huesped_nombre, h.apellido as huesped_apellido,
                       h.telefono as huesped_telefono
                FROM solicitudes_factura sf
                INNER JOIN reservaciones r ON sf.reservacion_id = r.id
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE sf.estatus = 'pendiente'";
        
        $params = [];
        
        if ($tipo) {
            $sql .= " AND sf.tipo = ?";
            $params[] = $tipo;
        }
        
        $sql .= " ORDER BY sf.created_at DESC";
        
        $stmt = $db->query($sql, $params);
        
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
    
    /**
     * Actualizar solicitud de factura con datos fiscales
     */
    public function actualizarSolicitudFactura($id, $datos) {
        $db = Database::getInstance();
        
        $campos = [];
        $params = [];
        
        $permitidos = ['rfc', 'razon_social', 'regimen_fiscal', 'uso_cfdi', 
                       'codigo_postal_fiscal', 'email_factura', 'estatus', 
                       'notas', 'fecha_facturada', 'numero_factura'];
        
        foreach ($permitidos as $campo) {
            if (isset($datos[$campo])) {
                $campos[] = "$campo = ?";
                $params[] = $datos[$campo];
            }
        }
        
        if (empty($campos)) return false;
        
        $params[] = $id;
        $sql = "UPDATE solicitudes_factura SET " . implode(', ', $campos) . " WHERE id = ?";
        
        $stmt = $db->query($sql, $params);
        return $stmt && $stmt->rowCount() > 0;
    }

    /**
     * Modificar la fecha de salida y recalcular el precio total
     */
    public function modificarFechaSalida($reservacion_id, $nueva_fecha_salida, $nuevo_precio_total) {
        try {
            $sql = "UPDATE reservaciones
                    SET fecha_salida = ?,
                        precio_total = ?,
                        updated_at   = NOW()
                    WHERE id = ?";

            $stmt = $this->db->query($sql, [
                $nueva_fecha_salida,
                $nuevo_precio_total,
                $reservacion_id
            ]);

            return $stmt && $stmt->rowCount() > 0;

        } catch (Exception $e) {
            error_log("Error en modificarFechaSalida: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener datos básicos de una reservación (sin JOINs complejos)
     */
    public function obtenerDatosBasicos($reservacion_id) {
        try {
            $stmt = $this->db->query(
                "SELECT id, estado, fecha_entrada, fecha_salida, precio_total FROM reservaciones WHERE id = ?",
                [$reservacion_id]
            );
            return $stmt ? $stmt->fetch() : null;
        } catch (Exception $e) {
            error_log("Error en obtenerDatosBasicos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener solo los IDs de habitaciones de una reservación
     */
    public function obtenerHabitacionIds($reservacion_id) {
        try {
            $stmt = $this->db->query(
                "SELECT habitacion_id FROM reservacion_habitaciones WHERE reservacion_id = ?",
                [$reservacion_id]
            );
            if (!$stmt) return [];
            return array_column($stmt->fetchAll(), 'habitacion_id');
        } catch (Exception $e) {
            error_log("Error en obtenerHabitacionIds: " . $e->getMessage());
            return [];
        }
    }
    
    
/**
 * Obtener habitaciones con reservación activa para una fecha
 * Retorna UNA FILA POR HABITACIÓN (no por reservación)
 * 
 * @param string $fecha Fecha Y-m-d (default: hoy)
 * @param string|null $buscar Término de búsqueda
 * @return array Una fila por cada habitación reservada
 */
}
