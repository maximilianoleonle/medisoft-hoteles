<?php
/**
 * Modelo de Categorías de Movimientos
 * Los Cedros
 */

class CategoriaMovimiento extends Model {
    protected $table = 'categorias_movimientos';
    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'icono',
        'color',
        'activa',
        'orden'
    ];
    
    /**
     * Obtener categorías activas por tipo
     */
    public function obtenerPorTipo($tipo = null) {
        $sql = "SELECT * FROM {$this->table} WHERE activa = 1";
        $params = [];
        
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
            return ['success' => false, 'errores' => $errores];
        }
        
        // Obtener el último orden
        $sql = "SELECT MAX(orden) as max_orden FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        $data['orden'] = ($result['max_orden'] ?? 0) + 1;
        
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
            // Verificar que no exista otra con el mismo nombre
            $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE nombre = ?";
            $params = [$data['nombre']];
            
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
            $db->beginTransaction();
            
            foreach ($ordenamiento as $orden => $categoria_id) {
                $sql = "UPDATE {$this->table} SET orden = ? WHERE id = ?";
                $db->query($sql, [$orden, $categoria_id]);
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
        
        $sql = "SELECT 
                cm.id,
                cm.nombre,
                cm.tipo,
                cm.icono,
                cm.color,
                COUNT(mc.id) as total_movimientos,
                SUM(mc.monto) as monto_total
                FROM {$this->table} cm
                LEFT JOIN movimientos_caja mc ON cm.id = mc.categoria_id";
        
        $params = [];
        
        if ($mes && $año) {
            $sql .= " AND MONTH(mc.created_at) = ? AND YEAR(mc.created_at) = ?";
            $params[] = $mes;
            $params[] = $año;
        }
        
        $sql .= " WHERE cm.activa = 1
                  GROUP BY cm.id
                  ORDER BY total_movimientos DESC";
        
        $stmt = $db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Desactivar categoría
     */
    public function desactivar($id) {
        // Verificar que no sea una categoría del sistema
        $categoria = $this->find($id);
        
        if (!$categoria) {
            return ['success' => false, 'message' => 'Categoría no encontrada'];
        }
        
        // Categorías del sistema que no se pueden desactivar
        $categoriasProtegidas = ['Hospedaje', 'Anticipo'];
        
        if (in_array($categoria['nombre'], $categoriasProtegidas)) {
            return ['success' => false, 'message' => 'Esta categoría del sistema no se puede desactivar'];
        }
        
        // Verificar si tiene movimientos
        $sql = "SELECT COUNT(*) as total FROM movimientos_caja WHERE categoria_id = ?";
        $stmt = $this->db->query($sql, [$id]);
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