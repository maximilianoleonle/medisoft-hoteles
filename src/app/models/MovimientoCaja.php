<?php
/**
 * Modelo de Movimientos de Caja
 * Los Cedros
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class MovimientoCaja extends Model {
    protected $table = 'movimientos_caja';
    protected $fillable = [
        'hotel_id',
        'tipo',
        'categoria',
        'categoria_id',
        'descripcion',
        'monto',
        'metodo_pago',
        'referencia',
        'comprobante',
        'proveedor',
        'reservacion_id',
        'usuario_id',
        'corte_id'
    ];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function obtenerPorId($id, $hotel_id = null) {
        $hotel_id = $hotel_id ?: $this->hotelIdActual();

        $sql = "SELECT *
                FROM {$this->table}
                WHERE id = ?
                AND hotel_id = ?
                LIMIT 1";

        $stmt = $this->db->query($sql, [$id, $hotel_id]);
        return $stmt ? $stmt->fetch() : false;
    }
    
    /**
     * Tipos de movimiento
     */
    public static function getTipos() {
        return [
            'ingreso' => ['label' => 'Ingreso', 'color' => 'green', 'icon' => 'arrow-down'],
            'gasto' => ['label' => 'Gasto', 'color' => 'red', 'icon' => 'arrow-up']
        ];
    }
    
    /**
     * Métodos de pago
     */
    public static function getMetodosPago() {
        return [
            'efectivo' => ['label' => 'Efectivo', 'icon' => 'money-bill-wave', 'color' => 'green'],
            'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'credit-card', 'color' => 'blue'],
            'transferencia' => ['label' => 'Transferencia', 'icon' => 'exchange-alt', 'color' => 'purple']
        ];
    }
    
    /**
     * Registrar nuevo movimiento
     */
   /**
 * Registrar nuevo movimiento
 */
public function registrarMovimiento($data) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    try {
        // Obtener corte actual
        $cajaModel = new Caja();
        $corteActual = $cajaModel->obtenerCorteActual();
        
        if (!$corteActual) {
            return ['success' => false, 'message' => 'No hay una caja abierta'];
        }

        if ((int)($corteActual['hotel_id'] ?? 0) !== $hotel_id) {
            return ['success' => false, 'message' => 'El corte abierto no pertenece al hotel actual'];
        }
        
        // Agregar corte_id y usuario_id
        $data['corte_id'] = $corteActual['id'];
        $data['usuario_id'] = $_SESSION['user_id'] ?? null;
        $data['hotel_id'] = $hotel_id;
        
        // Si no hay usuario_id, intentar obtenerlo de otra forma
        if (!$data['usuario_id']) {
            $data['usuario_id'] = user_id(); // Usar la función helper
        }
        
        // Validar categoría si se proporciona
        if (!empty($data['categoria_id'])) {
            $sql = "SELECT nombre FROM categorias_movimientos WHERE id = ? AND activa = 1";
            $stmt = $db->query($sql, [$data['categoria_id']]);
            $categoria = $stmt->fetch();
            
            if ($categoria) {
                $data['categoria'] = $categoria['nombre'];
            }
        }
        
        // Limpiar datos innecesarios antes de crear
        unset($data['csrf_token']);
        
        // Asegurarse de que los campos requeridos estén presentes
        if (empty($data['descripcion'])) {
            return ['success' => false, 'message' => 'La descripción es requerida'];
        }
        
        if (empty($data['monto']) || $data['monto'] <= 0) {
            return ['success' => false, 'message' => 'El monto debe ser mayor a 0'];
        }

        if (!empty($data['reservacion_id'])) {
            $sql = "SELECT id
                    FROM reservaciones
                    WHERE id = ?
                    AND hotel_id = ?
                    LIMIT 1";
            $stmt = $db->query($sql, [$data['reservacion_id'], $hotel_id]);

            if (!$stmt || !$stmt->fetch()) {
                return ['success' => false, 'message' => 'Reservacion no encontrada para el hotel actual'];
            }
        }
        
        // Crear el movimiento
        $sql = "INSERT INTO movimientos_caja 
                (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                 referencia, comprobante, proveedor, reservacion_id, usuario_id, 
                 corte_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $data['hotel_id'],
            $data['tipo'],
            $data['categoria'] ?? null,
            $data['categoria_id'] ?? null,
            $data['descripcion'],
            $data['monto'],
            $data['metodo_pago'],
            $data['referencia'] ?? null,
            $data['comprobante'] ?? null,
            $data['proveedor'] ?? null,
            $data['reservacion_id'] ?? null,
            $data['usuario_id'],
            $data['corte_id']
        ];
        
        $result = $db->query($sql, $params);
        
        if ($result) {
            $movimiento_id = $db->lastInsertId();
            return [
                'success' => true,
                'movimiento_id' => $movimiento_id,
                'message' => 'Movimiento registrado exitosamente'
            ];
        } else {
            return ['success' => false, 'message' => 'Error al insertar el movimiento en la base de datos'];
        }
        
    } catch (Exception $e) {
        error_log("Error en registrarMovimiento: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}
    
    /**
     * Obtener categoría
     */
    private function obtenerCategoria($categoria_id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM categorias_movimientos WHERE id = ? AND activa = 1";
        $stmt = $db->query($sql, [$categoria_id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener movimientos con detalles
     */
    public function obtenerMovimientosDetallados($filtros = []) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
        mc.*,
        cm.nombre as categoria_nombre,
        cm.icono as categoria_icono,
        cm.color as categoria_color,
        u.nombre_completo as usuario_nombre,
        r.id as reservacion_numero,
        h.nombre_completo as huesped_nombre,
        GROUP_CONCAT(
            CONCAT('Hab. ', hab.numero, ' - ', hab.tipo)
            ORDER BY hab.numero
            SEPARATOR ', '
        ) as habitaciones_detalle
        FROM {$this->table} mc
        LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
        LEFT JOIN usuarios u ON mc.usuario_id = u.id
        LEFT JOIN reservaciones r ON mc.reservacion_id = r.id AND r.hotel_id = mc.hotel_id
        LEFT JOIN huespedes h ON r.huesped_id = h.id
        LEFT JOIN reservacion_habitaciones rh
            ON mc.reservacion_id = rh.reservacion_id AND rh.hotel_id = mc.hotel_id
        LEFT JOIN habitaciones hab
            ON rh.habitacion_id = hab.id AND hab.hotel_id = mc.hotel_id
        WHERE mc.hotel_id = ?";
        
        $params = [$hotel_id];
        
        // Aplicar filtros
        if (!empty($filtros['corte_id'])) {
            $sql .= " AND mc.corte_id = ?";
            $params[] = $filtros['corte_id'];
        }
        
        if (!empty($filtros['tipo'])) {
            $sql .= " AND mc.tipo = ?";
            $params[] = $filtros['tipo'];
        }
        
        if (!empty($filtros['metodo_pago'])) {
            $sql .= " AND mc.metodo_pago = ?";
            $params[] = $filtros['metodo_pago'];
        }
        
        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND mc.categoria_id = ?";
            $params[] = $filtros['categoria_id'];
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND DATE(mc.created_at) >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND DATE(mc.created_at) <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        if (!empty($filtros['buscar'])) {
            $sql .= " AND (mc.descripcion LIKE ? OR mc.referencia LIKE ? OR mc.proveedor LIKE ?)";
            $buscar = '%' . $filtros['buscar'] . '%';
            $params[] = $buscar;
            $params[] = $buscar;
            $params[] = $buscar;
        }
        
        // Ordenamiento
        // Agrupar por ID para el GROUP_CONCAT
$sql .= " GROUP BY mc.id";

// Ordenamiento
$orden = $filtros['orden'] ?? 'DESC';
$sql .= " ORDER BY mc.created_at $orden";
        
        // Límite
        if (!empty($filtros['limite'])) {
            $sql .= " LIMIT ?";
            $params[] = $filtros['limite'];
        }
        
        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Editar movimiento
     */
    public function editarMovimiento($id, $data, $motivo, $usuario_id) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        try {
            $db->beginTransaction();
            
            // Obtener movimiento original
            $movimientoOriginal = $this->obtenerPorId($id, $hotel_id);
            
            if (!$movimientoOriginal) {
                throw new Exception("Movimiento no encontrado");
            }
            
            // Verificar que el corte esté abierto
            $sql = "SELECT estado
                    FROM cortes_caja
                    WHERE id = ?
                    AND hotel_id = ?";
            $stmt = $db->query($sql, [$movimientoOriginal['corte_id'], $hotel_id]);
            $corte = $stmt->fetch();
            
            if (!$corte || $corte['estado'] != 'abierto') {
                throw new Exception("No se pueden editar movimientos de un corte cerrado");
            }
            
            // Actualizar movimiento
            $data['editado'] = 1;
            $data['motivo_edicion'] = $motivo;
            $data['usuario_edicion_id'] = $usuario_id;
            $data['fecha_edicion'] = date('Y-m-d H:i:s');
            
            $camposPermitidos = [
                'descripcion',
                'monto',
                'metodo_pago',
                'referencia',
                'editado',
                'motivo_edicion',
                'usuario_edicion_id',
                'fecha_edicion'
            ];
            $data = array_intersect_key($data, array_flip($camposPermitidos));

            $fields = [];
            $params = [];

            foreach ($data as $campo => $valor) {
                $fields[] = "{$campo} = ?";
                $params[] = $valor;
            }

            if (empty($fields)) {
                throw new Exception("No hay datos para actualizar");
            }

            $params[] = $id;
            $params[] = $hotel_id;

            $sql = "UPDATE {$this->table}
                    SET " . implode(', ', $fields) . "
                    WHERE id = ?
                    AND hotel_id = ?";

            $stmt = $db->query($sql, $params);
            $resultado = $stmt && $stmt->rowCount() > 0 ? $this->obtenerPorId($id, $hotel_id) : false;
            
            if ($resultado) {
                // Registrar en log
                $this->registrarLogEdicion($id, $movimientoOriginal, $data, $motivo, $usuario_id);
                
                $db->commit();
                return ['success' => true, 'message' => 'Movimiento actualizado'];
            }
            
            throw new Exception("Error al actualizar el movimiento");
            
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Registrar log de edición
     */
    private function registrarLogEdicion($movimiento_id, $original, $nuevo, $motivo, $usuario_id) {
        $db = Database::getInstance();
        
        $cambios = [];
        foreach ($nuevo as $campo => $valor) {
            if (isset($original[$campo]) && $original[$campo] != $valor) {
                $cambios[] = "$campo: '{$original[$campo]}' → '$valor'";
            }
        }
        
        $log = "Movimiento #$movimiento_id editado. Cambios: " . implode(', ', $cambios) . ". Motivo: $motivo";
        
        error_log($log);
        // Aquí podrías guardar en una tabla de auditoría si lo deseas
    }
    
    /**
     * Eliminar movimiento (soft delete)
     */
    public function eliminarMovimiento($id, $motivo, $usuario_id) {
        return $this->editarMovimiento($id, [
            'descripcion' => '[ELIMINADO] ' . $motivo
        ], "Eliminación: $motivo", $usuario_id);
    }
    
    /**
     * Obtener totales por período
     */
    public function obtenerTotalesPorPeriodo($fecha_inicio, $fecha_fin = null) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                tipo,
                metodo_pago,
                COUNT(*) as cantidad,
                SUM(monto) as total
                FROM {$this->table}
                WHERE hotel_id = ?
                AND DATE(created_at) >= ?";
        
        $params = [$hotel_id, $fecha_inicio];
        
        if ($fecha_fin) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $fecha_fin;
        }
        
        $sql .= " GROUP BY tipo, metodo_pago";
        
        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener movimientos para exportar
     */
    public function obtenerParaExportar($corte_id) {
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT 
                mc.created_at as 'Fecha y Hora',
                mc.tipo as 'Tipo',
                cm.nombre as 'Categoría',
                mc.descripcion as 'Descripción',
                mc.monto as 'Monto',
                mc.metodo_pago as 'Método de Pago',
                mc.referencia as 'Referencia',
                mc.comprobante as 'Comprobante',
                mc.proveedor as 'Proveedor',
                u.nombre_completo as 'Registrado por',
                CASE WHEN mc.reservacion_id IS NOT NULL THEN CONCAT('Reserva #', mc.reservacion_id) ELSE '' END as 'Reservación'
                FROM {$this->table} mc
                INNER JOIN cortes_caja cc ON mc.corte_id = cc.id AND cc.hotel_id = mc.hotel_id
                LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
                LEFT JOIN usuarios u ON mc.usuario_id = u.id
                WHERE cc.id = ?
                AND cc.hotel_id = ?
                AND mc.hotel_id = ?
                ORDER BY mc.created_at";
        
        $stmt = $db->query($sql, [$corte_id, $hotel_id, $hotel_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Validar movimiento
     */
    public function validarMovimiento($data) {
        $errores = [];
        
        // Validar tipo
        if (empty($data['tipo']) || !in_array($data['tipo'], ['ingreso', 'gasto'])) {
            $errores[] = 'El tipo de movimiento es inválido';
        }
        
        // Validar categoría
        if (empty($data['categoria_id'])) {
            $errores[] = 'Debe seleccionar una categoría';
        }
        
        // Validar descripción
        if (empty($data['descripcion'])) {
            $errores[] = 'La descripción es obligatoria';
        } elseif (strlen($data['descripcion']) < 5) {
            $errores[] = 'La descripción debe tener al menos 5 caracteres';
        }
        
        // Validar monto
        if (empty($data['monto']) || !is_numeric($data['monto'])) {
            $errores[] = 'El monto debe ser un número válido';
        } elseif ($data['monto'] <= 0) {
            $errores[] = 'El monto debe ser mayor a 0';
        }
        
        // Validar método de pago
        if (empty($data['metodo_pago']) || !in_array($data['metodo_pago'], ['efectivo', 'tarjeta', 'transferencia'])) {
            $errores[] = 'El método de pago es inválido';
        }
        
        // Validar referencia para tarjeta y transferencia
        if (in_array($data['metodo_pago'], ['tarjeta', 'transferencia']) && empty($data['referencia'])) {
            $errores[] = 'La referencia es obligatoria para pagos con tarjeta o transferencia';
        }
        
        return $errores;
    }
    
    /**
     * Obtener últimos movimientos
     */
    public function obtenerUltimosMovimientos($limite = 10) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
        mc.*,
        cm.nombre as categoria_nombre,
        cm.icono as categoria_icono,
        cm.color as categoria_color,
        u.nombre_completo as usuario_nombre,
        GROUP_CONCAT(
            CONCAT('Hab. ', hab.numero, ' - ', hab.tipo)
            ORDER BY hab.numero
            SEPARATOR ', '
        ) as habitaciones_detalle,
        tpc.trabajador_id as trabajador_id
        FROM movimientos_caja mc
        LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
        LEFT JOIN usuarios u ON mc.usuario_id = u.id
        LEFT JOIN reservacion_habitaciones rh
            ON mc.reservacion_id = rh.reservacion_id AND rh.hotel_id = mc.hotel_id
        LEFT JOIN habitaciones hab
            ON rh.habitacion_id = hab.id AND hab.hotel_id = mc.hotel_id
        LEFT JOIN trabajador_pagos_caja tpc
            ON tpc.movimiento_caja_id = mc.id AND tpc.hotel_id = mc.hotel_id
        WHERE mc.hotel_id = ?
        GROUP BY mc.id
        ORDER BY mc.created_at DESC
        LIMIT ?";
    
    $stmt = $db->query($sql, [$hotel_id, $limite]);
    $movimientos = $stmt->fetchAll();
    
    // Normalizar tipo para la vista
    foreach ($movimientos as &$mov) {
        // Para la vista, mostrar "egreso" como un tipo de gasto especial
        if ($mov['tipo'] == 'egreso') {
            $mov['tipo_display'] = 'egreso';
            $mov['tipo'] = 'egreso'; // Mantener como egreso para identificación
        }
    }
    
    return $movimientos;
}
}
