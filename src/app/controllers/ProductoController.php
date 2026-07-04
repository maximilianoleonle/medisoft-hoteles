<?php
// controllers/ProductoController.php

class ProductoController {
    private $productoModel;
    private $inventarioService;
    
    public function __construct($database) {
        $this->productoModel = new Producto($database);
        $this->inventarioService = new InventarioService($database);
    }
    
    /**
     * Mostrar lista de productos
     */
    public function index() {
        try {
            // Obtener filtros de la solicitud
            $filtros = [
                'categoria_id' => $_GET['categoria'] ?? null,
                'busqueda' => $_GET['busqueda'] ?? null,
                'solo_alertas' => isset($_GET['alertas'])
            ];
            
            $productos = $this->productoModel->obtenerTodos($filtros);
            
            // Agrupar por categoría para mejor visualización
            $productosPorCategoria = [];
            foreach ($productos as $producto) {
                $categoria = $producto['categoria_nombre'];
                if (!isset($productosPorCategoria[$categoria])) {
                    $productosPorCategoria[$categoria] = [
                        'icono' => $producto['categoria_icono'],
                        'productos' => []
                    ];
                }
                $productosPorCategoria[$categoria]['productos'][] = $producto;
            }
            
            // Cargar vista
            require_once 'views/inventario/productos/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al cargar productos: " . $e->getMessage();
            header('Location: /inventario/dashboard');
        }
    }
    
    /**
     * Mostrar formulario de nuevo producto
     */
    public function crear() {
        try {
            // Obtener datos necesarios para el formulario
            $categorias = $this->obtenerCategorias();
            $unidades = $this->obtenerUnidadesMedida();
            $tiposHabitacion = $this->obtenerTiposHabitacion();
            
            require_once 'views/inventario/productos/crear.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al cargar formulario: " . $e->getMessage();
            header('Location: /inventario/productos');
        }
    }
    
    /**
     * Guardar nuevo producto
     */
    public function guardar() {
        try {
            // Validar datos
            $this->validarDatosProducto($_POST);
            
            // Crear producto
            $producto_id = $this->productoModel->crear($_POST);
            
            if (!$producto_id) {
                throw new Exception("Error al crear el producto");
            }
            
            // Configurar cantidades por tipo de habitación si se enviaron
            if (!empty($_POST['config_habitaciones'])) {
                $configService = new ConfiguracionHabitacionService($this->db);
                
                foreach ($_POST['config_habitaciones'] as $tipo_id => $config) {
                    if ($config['cantidad_checkin'] > 0 || $config['cantidad_limpieza'] > 0) {
                        $configService->agregarConfiguracion([
                            'tipo_habitacion_id' => $tipo_id,
                            'producto_id' => $producto_id,
                            'cantidad_checkin' => $config['cantidad_checkin'] ?? 0,
                            'cantidad_limpieza' => $config['cantidad_limpieza'] ?? 0
                        ]);
                    }
                }
            }
            
            $_SESSION['success'] = "Producto creado exitosamente";
            header('Location: /inventario/productos');
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /inventario/productos/crear');
        }
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function editar($id) {
        try {
            $producto = $this->productoModel->obtenerPorId($id);
            
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }
            
            $categorias = $this->obtenerCategorias();
            $unidades = $this->obtenerUnidadesMedida();
            $tiposHabitacion = $this->obtenerTiposHabitacion();
            
            // Obtener configuración actual por tipo de habitación
            $configService = new ConfiguracionHabitacionService($this->db);
            $configuraciones = $configService->obtenerConfiguracion();
            
            require_once 'views/inventario/productos/editar.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /inventario/productos');
        }
    }
    
    /**
     * Validar datos del producto
     */
    private function validarDatosProducto($datos) {
        $errores = [];
        
        if (empty($datos['codigo'])) {
            $errores[] = "El código es requerido";
        }
        
        if (empty($datos['nombre'])) {
            $errores[] = "El nombre es requerido";
        }
        
        if (empty($datos['categoria_id'])) {
            $errores[] = "La categoría es requerida";
        }
        
        if (empty($datos['unidad_medida_id'])) {
            $errores[] = "La unidad de medida es requerida";
        }
        
        if (!is_numeric($datos['stock_minimo']) || $datos['stock_minimo'] < 0) {
            $errores[] = "El stock mínimo debe ser un número positivo";
        }
        
        if (!empty($errores)) {
            throw new Exception(implode(", ", $errores));
        }
    }
    
    // Métodos auxiliares para obtener datos
    private function obtenerCategorias() {
        $sql = "SELECT * FROM categorias_producto WHERE activo = 1 ORDER BY orden, nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenerUnidadesMedida() {
        $sql = "SELECT * FROM unidades_medida WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function obtenerTiposHabitacion() {
        $sql = "SELECT * FROM tipos_habitacion WHERE activo = 1 ORDER BY id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}