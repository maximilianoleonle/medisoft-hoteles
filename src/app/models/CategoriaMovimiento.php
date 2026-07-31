<?php
/**
 * Modelo de Categorías de Movimientos
 * Los Cedros
 */

class CategoriaMovimiento extends Model {
    protected $table = 'categorias_movimientos';
    // La tabla NO tiene updated_at (solo created_at, con DEFAULT CURRENT_TIMESTAMP).
    // Con los timestamps del Model base, create() y update() agregaban esa columna
    // al INSERT/UPDATE y MySQL respondia 1054 -> 500 al crear o desactivar un
    // concepto. created_at lo sigue poniendo el DEFAULT de la columna.
    protected $timestamps = false;
    protected $fillable = [
        'hotel_id',
        'nombre',
        'tipo',
        'descripcion',
        'icono',
        'color',
        'activa',
        'orden'
    ];

    /**
     * Hotel activo. El catalogo de categorias es por hotel (aislamiento
     * multi-tenant): cada consulta se confina con AND hotel_id = ?.
     */
    protected function hotelIdActual() {
        return function_exists('obtenerHotelIdActualCompat')
            ? obtenerHotelIdActualCompat()
            : (function_exists('current_hotel_id') ? current_hotel_id() : null);
    }

    /**
     * Obtener categorías activas por tipo
     */
    public function obtenerPorTipo($tipo = null) {
        $sql = "SELECT * FROM {$this->table} WHERE hotel_id = ? AND activa = 1";
        $params = [$this->hotelIdActual()];

        if ($tipo && in_array($tipo, ['ingreso', 'gasto', 'ambos'])) {
            $sql .= " AND (tipo = ? OR tipo = 'ambos')";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY orden ASC, nombre ASC";

        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener categorías para select
     */
    public function obtenerParaSelect($tipo = null) {
        $categorias = $this->obtenerPorTipo($tipo);
        $resultado = [];
        
        foreach ($categorias as $cat) {
            $resultado[$cat['id']] = [
                'nombre' => $cat['nombre'],
                'icono' => $cat['icono'],
                'color' => $cat['color']
            ];
        }
        
        return $resultado;
    }
    
    /**
     * Crear categoría personalizada
     */
    public function crearCategoria($data) {
        // Validar datos
        $errores = $this->validarCategoria($data);
        
        if (!empty($errores)) {
            // 'message' ademas de 'errores': la pantalla muestra el motivo real
            // ("Ya existe una categoria con ese nombre") en vez de un generico.
            return ['success' => false, 'errores' => $errores, 'message' => implode(' ', $errores)];
        }

        // Obtener el último orden (dentro del hotel actual)
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT MAX(orden) as max_orden FROM {$this->table} WHERE hotel_id = ?";
        $stmt = $this->db->query($sql, [$hotelId]);
        $result = $stmt->fetch();

        $data['orden'] = ($result['max_orden'] ?? 0) + 1;
        $data['hotel_id'] = $hotelId; // la categoría nace en el hotel actual

        // Crear categoría
        $categoria = $this->create($data);
        
        if ($categoria) {
            return [
                'success' => true,
                'categoria' => $categoria,
                'message' => 'Categoría creada exitosamente'
            ];
        }
        
        return ['success' => false, 'message' => 'Error al crear la categoría'];
    }

    /**
     * Actualizar una categoría del HOTEL ACTUAL. El update() base va por id sin
     * scope (seria un IDOR de escritura entre hoteles): aqui confinamos por
     * hotel_id y revalidamos unicidad de nombre dentro del hotel.
     */
    public function actualizarCategoria($id, $data) {
        $hotelId = $this->hotelIdActual();

        $stmt = $this->db->query(
            "SELECT id FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$id, $hotelId]
        );
        if (!$stmt || !$stmt->fetch()) {
            return ['success' => false, 'message' => 'Categoría no encontrada'];
        }

        $errores = $this->validarCategoria($data, $id);
        if (!empty($errores)) {
            return ['success' => false, 'errores' => $errores, 'message' => implode(' ', $errores)];
        }

        $this->db->query(
            "UPDATE {$this->table}
             SET nombre = ?, tipo = ?, descripcion = ?, icono = ?, color = ?
             WHERE id = ? AND hotel_id = ?",
            [
                $data['nombre'],
                $data['tipo'],
                $data['descripcion'] ?? null,
                $data['icono'] ?? 'fas fa-tag',
                $data['color'] ?? '#6B7280',
                $id,
                $hotelId,
            ]
        );

        return ['success' => true, 'message' => 'Categoría actualizada'];
    }

    /**
     * Todas las categorías del hotel actual (activas e inactivas), para la vista
     * de configuración. Scoped por hotel_id.
     */
    public function listarTodasDelHotel() {
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table}
             WHERE hotel_id = ?
             ORDER BY tipo ASC, orden ASC, nombre ASC",
            [$this->hotelIdActual()]
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Validar datos de categoría
     */
    private function validarCategoria($data, $id = null) {
        $errores = [];
        
        // Validar nombre
        if (empty($data['nombre'])) {
            $errores[] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres';
        } else {
            // Verificar que no exista otra con el mismo nombre EN ESTE HOTEL
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE hotel_id = ? AND nombre = ?";
            $params = [$this->hotelIdActual(), $data['nombre']];

            if ($id) {
                $sql .= " AND id != ?";
                $params[] = $id;
            }
            
            $stmt = $this->db->query($sql, $params);
            $result = $stmt->fetch();
            
            if ($result['total'] > 0) {
                $errores[] = 'Ya existe una categoría con ese nombre';
            }
        }
        
        // Validar tipo
        if (empty($data['tipo']) || !in_array($data['tipo'], ['ingreso', 'gasto', 'ambos'])) {
            $errores[] = 'El tipo de categoría es inválido';
        }
        
        return $errores;
    }
    
    /**
     * Actualizar orden de categorías
     */
    public function actualizarOrden($ordenamiento) {
        $db = Database::getInstance();
        
        try {
            $hotelId = $this->hotelIdActual();
            $db->beginTransaction();

            // El WHERE incluye hotel_id: no se puede reordenar categorías de otro hotel.
            foreach ($ordenamiento as $orden => $categoria_id) {
                $sql = "UPDATE {$this->table} SET orden = ? WHERE id = ? AND hotel_id = ?";
                $db->query($sql, [$orden, $categoria_id, $hotelId]);
            }

            $db->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener estadísticas de uso
     */
    public function obtenerEstadisticasUso($mes = null, $año = null) {
        $db = Database::getInstance();
        
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT
                cm.id,
                cm.nombre,
                cm.tipo,
                cm.icono,
                cm.color,
                COUNT(mc.id) as total_movimientos,
                SUM(mc.monto) as monto_total
                FROM {$this->table} cm
                LEFT JOIN movimientos_caja mc ON cm.id = mc.categoria_id AND mc.hotel_id = cm.hotel_id";

        $params = [];

        if ($mes && $año) {
            $sql .= " AND MONTH(mc.created_at) = ? AND YEAR(mc.created_at) = ?";
            $params[] = $mes;
            $params[] = $año;
        }

        $sql .= " WHERE cm.hotel_id = ? AND cm.activa = 1
                  GROUP BY cm.id
                  ORDER BY total_movimientos DESC";
        $params[] = $hotelId;

        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Desactivar categoría
     */
    public function desactivar($id) {
        // Confinar al hotel actual: el find() base no filtra por hotel, así que
        // resolvemos con scope para no tocar categorías de otro hotel.
        $hotelId = $this->hotelIdActual();
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$id, $hotelId]
        );
        $categoria = $stmt ? $stmt->fetch() : null;

        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoría no encontrada'];
        }

        // Categorías del sistema que no se pueden desactivar
        $categoriasProtegidas = ['Hospedaje', 'Anticipo'];

        if (in_array($categoria['nombre'], $categoriasProtegidas)) {
            return ['success' => false, 'message' => 'Esta categoría del sistema no se puede desactivar'];
        }

        // Verificar si tiene movimientos (de este hotel)
        $sql = "SELECT COUNT(*) as total FROM movimientos_caja WHERE categoria_id = ? AND hotel_id = ?";
        $stmt = $this->db->query($sql, [$id, $hotelId]);
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            // Solo desactivar, no eliminar
            $this->update($id, ['activa' => 0]);
            return ['success' => true, 'message' => 'Categoría desactivada'];
        } else {
            // Si no tiene movimientos, se puede eliminar
            $this->delete($id);
            return ['success' => true, 'message' => 'Categoría eliminada'];
        }
    }
    
    /**
     * Obtener iconos disponibles
     */
    public static function getIconosDisponibles() {
        return [
            // Ingresos
            'fas fa-bed' => 'Cama/Hospedaje',
            'fas fa-hand-holding-usd' => 'Recibir dinero',
            'fas fa-concierge-bell' => 'Servicios',
            'fas fa-shopping-cart' => 'Ventas',
            'fas fa-coins' => 'Monedas',
            'fas fa-dollar-sign' => 'Dólar',
            'fas fa-cash-register' => 'Caja registradora',
            'fas fa-piggy-bank' => 'Alcancía',
            
            // Gastos
            'fas fa-users' => 'Personal',
            'fas fa-bolt' => 'Servicios/Luz',
            'fas fa-tools' => 'Herramientas',
            'fas fa-broom' => 'Limpieza',
            'fas fa-box' => 'Productos',
            'fas fa-percentage' => 'Porcentaje',
            'fas fa-bullhorn' => 'Publicidad',
            'fas fa-file-invoice-dollar' => 'Facturas',
            'fas fa-truck' => 'Transporte',
            'fas fa-utensils' => 'Alimentos',
            
            // Generales
            'fas fa-tag' => 'Etiqueta',
            'fas fa-plus-circle' => 'Agregar',
            'fas fa-minus-circle' => 'Quitar',
            'fas fa-info-circle' => 'Información',
            'fas fa-exclamation-circle' => 'Importante',
            'fas fa-question-circle' => 'Pregunta',
            'fas fa-check-circle' => 'Verificado',
            'fas fa-times-circle' => 'Cancelado'
        ];
    }
    
    /**
     * Obtener colores disponibles
     */
    public static function getColoresDisponibles() {
        return [
            '#10B981' => 'Verde',
            '#3B82F6' => 'Azul',
            '#8B5CF6' => 'Púrpura',
            '#F59E0B' => 'Ámbar',
            '#EF4444' => 'Rojo',
            '#EC4899' => 'Rosa',
            '#06B6D4' => 'Cian',
            '#84CC16' => 'Lima',
            '#6366F1' => 'Índigo',
            '#F97316' => 'Naranja',
            '#6B7280' => 'Gris',
            '#78716C' => 'Piedra'
        ];
    }
}