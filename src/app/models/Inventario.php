<?php
/**
 * Modelo de Inventario
 * Los Cedros
 */

require_once __DIR__ . '/../helpers/hotel_config.php';

class Inventario extends Model {
    protected $table = 'inventario_productos'; // CORREGIDO
    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_id',
        'stock_actual',
        'stock_minimo',
        'descuento_automatico',
        'costo_unitario', // Cambiado de precio_unitario
        'unidad_medida',
        'activo',
        'hotel_id'
    ];

    public function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function create($data) {
        if (!isset($data['hotel_id'])) {
            $data['hotel_id'] = $this->hotelIdActual();
        }

        return parent::create($data);
    }

    public function codigoExisteEnHotel($codigo, $excluirId = null) {
        $hotelId = $this->hotelIdActual();
        $params = [$codigo, $hotelId];
        $sql = "SELECT COUNT(*) AS total
                FROM inventario_productos
                WHERE codigo = ? AND hotel_id = ?";

        if ($excluirId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excluirId;
        }

        $result = $this->query($sql, $params);
        return (int)($result[0]['total'] ?? 0) > 0;
    }

    public function categoriaPerteneceAlHotel($categoriaId) {
        $hotelId = $this->hotelIdActual();
        $result = $this->query(
            "SELECT COUNT(*) AS total
             FROM inventario_categorias
             WHERE id = ? AND hotel_id = ?",
            [$categoriaId, $hotelId]
        );

        return (int)($result[0]['total'] ?? 0) > 0;
    }

    public function tipoHabitacionPerteneceAlHotel($tipoHabitacion) {
        $hotelId = $this->hotelIdActual();
        $tipoHabitacion = trim((string)$tipoHabitacion);

        if ($tipoHabitacion === '') {
            return false;
        }

        $result = $this->query(
            "SELECT COUNT(*) AS total
             FROM tipos_habitacion
             WHERE codigo = ? AND hotel_id = ? AND activo = 1",
            [$tipoHabitacion, $hotelId]
        );

        return (int)($result[0]['total'] ?? 0) > 0;
    }

    public function actualizarProductoBase($id, array $data) {
        $hotelId = $this->hotelIdActual();
        $data = $this->filterFillable($data);

        unset($data['hotel_id']);

        if (empty($data)) {
            return $this->getByIdWithCategory($id);
        }

        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $fields = [];
        $values = [];
        foreach ($data as $field => $value) {
            $fields[] = "{$field} = ?";
            $values[] = $value;
        }

        $values[] = $id;
        $values[] = $hotelId;

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $fields) . "
                WHERE id = ? AND hotel_id = ?";

        $this->query($sql, $values);

        return $this->getByIdWithCategory($id);
    }

    public function desactivarProductoBase($id) {
        return $this->actualizarProductoBase($id, ['activo' => 0]);
    }

    /**
     * Obtener todos los productos con su categoria
     */
    public function getAllWithCategory() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT p.*,
                COALESCE(c.nombre, 'Sin categoria') as categoria_nombre
                FROM inventario_productos p
                LEFT JOIN inventario_categorias c
                    ON p.categoria_id = c.id AND c.hotel_id = p.hotel_id
                WHERE p.activo = 1
                    AND p.hotel_id = ?
                ORDER BY p.nombre";
        return $this->query($sql, [$hotelId]);
    }

    /**
     * Obtener un producto con su categoria
     */
    public function getByIdWithCategory($id) {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT p.*, c.nombre as categoria_nombre
                FROM inventario_productos p
                LEFT JOIN inventario_categorias c
                    ON p.categoria_id = c.id AND c.hotel_id = p.hotel_id
                WHERE p.id = ? AND p.hotel_id = ?";
        $results = $this->query($sql, [$id, $hotelId]);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Actualizar stock
     *
     * Fuera de alcance de Inventario 1-D-B: este metodo esta conectado con
     * movimientos/check-in/check-out y se conserva sin scope por ahora.
     */
    public function actualizarStock($producto_id, $cantidad, $tipo = 'SALIDA') {
        $db = Database::getInstance();

        if ($tipo == 'SALIDA') {
            $cantidad = -abs($cantidad);
        } else {
            $cantidad = abs($cantidad);
        }

        $sql = "UPDATE inventario_productos SET stock_actual = stock_actual + ? WHERE id = ?";
        return $db->query($sql, [$cantidad, $producto_id]);
    }

    /**
     * Obtener stock actual
     */
    public function getStock($producto_id) {
        $producto = $this->find($producto_id);
        return $producto ? $producto['stock_actual'] : 0;
    }

    /**
     * Obtener productos para descuento automatico
     */
    public function getProductosDescuentoAutomatico() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT *
                FROM inventario_productos
                WHERE descuento_automatico = 1
                    AND activo = 1
                    AND hotel_id = ?
                ORDER BY nombre";
        return $this->query($sql, [$hotelId]);
    }

    /**
     * Obtener categorias
     */
    public function getCategorias() {
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT *
                FROM inventario_categorias
                WHERE activo = 1
                    AND hotel_id = ?
                ORDER BY orden";
        return $this->query($sql, [$hotelId]);
    }

    /**
     * Obtener habitaciones activas
     */
    public function getHabitacionesActivas() {
        $db = Database::getInstance();
        $hotelId = $this->hotelIdActual();
        $sql = "SELECT id, numero
                FROM habitaciones
                WHERE activa = 1 AND hotel_id = ?
                ORDER BY numero";
        $stmt = $db->query($sql, [$hotelId]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Obtener configuracion completa
     */
    public function getConfiguracionCompleta() {
        $hotelId = $this->hotelIdActual();

        try {
            // Usar directamente inventario_config_habitacion
            $sql = "SELECT
                        ich.tipo_habitacion,
                        ich.producto_id,
                        ich.cantidad_descontar,
                        ich.activo,
                        p.nombre as producto_nombre,
                        p.codigo
                    FROM inventario_config_habitacion ich
                    JOIN inventario_productos p
                        ON ich.producto_id = p.id AND p.hotel_id = ich.hotel_id
                    WHERE ich.activo = 1
                        AND p.activo = 1
                        AND ich.hotel_id = ?
                    ORDER BY ich.tipo_habitacion, p.nombre";

            $results = $this->query($sql, [$hotelId]);

            // Organizar por tipo de habitacion
            $configuracion = [];
            foreach ($results as $row) {
                if (!isset($configuracion[$row['tipo_habitacion']])) {
                    $configuracion[$row['tipo_habitacion']] = [];
                }
                $configuracion[$row['tipo_habitacion']][] = $row;
            }

            return $configuracion;

        } catch (Exception $e) {
            error_log("Error en getConfiguracionCompleta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar configuracion de habitacion
     */
    public function actualizarConfiguracion($tipo_habitacion, $producto_id, $cantidad) {
        $hotelId = $this->hotelIdActual();
        $db = Database::getInstance();

        try {
            $tipo_habitacion = trim((string)$tipo_habitacion);
            $producto_id = (int)$producto_id;

            if ($tipo_habitacion === '' || !$this->tipoHabitacionPerteneceAlHotel($tipo_habitacion)) {
                throw new Exception('Tipo de habitacion no encontrado para el hotel actual');
            }

            if ($producto_id <= 0) {
                throw new Exception('Producto de inventario no valido');
            }

            if ($cantidad === '' || !is_numeric($cantidad)) {
                throw new Exception('La cantidad de configuracion debe ser numerica');
            }

            $cantidad = (int)$cantidad;
            if ($cantidad < 0) {
                throw new Exception('La cantidad de configuracion no puede ser negativa');
            }

            $producto = $this->query(
                "SELECT id, nombre, descuento_automatico
                 FROM inventario_productos
                 WHERE id = ?
                    AND hotel_id = ?
                    AND activo = 1
                    AND descuento_automatico = 1",
                [$producto_id, $hotelId]
            );

            if (empty($producto)) {
                throw new Exception('Producto de inventario no encontrado para el hotel actual');
            }

            // Primero verificar si existe el registro
            $sql = "SELECT id, cantidad_descontar, activo
                    FROM inventario_config_habitacion
                    WHERE tipo_habitacion = ? AND producto_id = ? AND hotel_id = ?";
            $stmt = $db->query($sql, [$tipo_habitacion, $producto_id, $hotelId]);
            $existe = $stmt ? $stmt->fetch() : null;

            $cantidadAnterior = $existe ? (float)$existe['cantidad_descontar'] : null;
            $activoAnterior = $existe ? (int)$existe['activo'] : null;
            $activoNuevo = $cantidad > 0 ? 1 : 0;

            if ($existe) {
                // Si existe, actualizar
                $sql = "UPDATE inventario_config_habitacion
                        SET cantidad_descontar = ?,
                            activo = ?,
                            created_at = NOW()
                        WHERE tipo_habitacion = ? AND producto_id = ? AND hotel_id = ?";
                $db->query($sql, [
                    $cantidad,
                    $cantidad > 0 ? 1 : 0,
                    $tipo_habitacion,
                    $producto_id,
                    $hotelId
                ]);
            } else {
                // Si no existe y la cantidad es mayor a 0, insertar
                if ($cantidad > 0) {
                    $sql = "INSERT INTO inventario_config_habitacion
                            (tipo_habitacion, producto_id, cantidad_descontar, activo, hotel_id, created_at)
                            VALUES (?, ?, ?, ?, ?, NOW())";
                    $db->query($sql, [
                        $tipo_habitacion,
                        $producto_id,
                        $cantidad,
                        1,
                        $hotelId
                    ]);
                } else {
                    // Si la cantidad es 0 y no existe, no hacer nada
                    return [
                        'changed' => false,
                        'hotel_id' => $hotelId,
                        'tipo_habitacion' => $tipo_habitacion,
                        'producto_id' => $producto_id,
                        'producto_nombre' => $producto[0]['nombre'] ?? null,
                        'cantidad_anterior' => null,
                        'cantidad_nueva' => 0,
                        'activo_anterior' => null,
                        'activo_nuevo' => 0,
                        'descuento_automatico_cambio' => false,
                    ];
                }
            }

            $changed = !$existe
                || (float)$cantidadAnterior !== (float)$cantidad
                || (int)$activoAnterior !== (int)$activoNuevo;

            return [
                'changed' => $changed,
                'hotel_id' => $hotelId,
                'tipo_habitacion' => $tipo_habitacion,
                'producto_id' => $producto_id,
                'producto_nombre' => $producto[0]['nombre'] ?? null,
                'cantidad_anterior' => $cantidadAnterior,
                'cantidad_nueva' => $cantidad,
                'activo_anterior' => $activoAnterior,
                'activo_nuevo' => $activoNuevo,
                'descuento_automatico_cambio' => false,
            ];

        } catch (Exception $e) {
            error_log("Error en actualizarConfiguracion: " . $e->getMessage());
            throw $e;
        }
    }
}
