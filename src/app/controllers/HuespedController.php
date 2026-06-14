<?php
/**
 * Controlador de Huéspedes
 * Los Cedros
 */

class HuespedController extends Controller {
    private $huespedModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->huespedModel = new Huesped();
    }

    private function estacionamientoDefaultVehiculo() {
        if (function_exists('hotel_general_catalog_parking_rows')) {
            foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
                $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
                if ($parkingCode !== '') {
                    return $parkingCode;
                }
            }
        }

        return 'coches';
    }

    private function normalizarEstacionamientoVehiculo($value) {
        $estacionamiento = trim((string)($value ?? ''));
        return $estacionamiento !== '' ? $estacionamiento : $this->estacionamientoDefaultVehiculo();
    }

    private function estacionamientoVehiculoDesdePost($key) {
        return $this->normalizarEstacionamientoVehiculo($this->getPost($key, ''));
    }

    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('huespedes');
        return true;
    }
    
    /**
     * Listado de huéspedes
     */
    
    
    public function indexAction() {
    // Obtener parámetros de búsqueda y filtros
    $buscar = $this->getQuery('buscar');
    $estado = $this->getQuery('estado');
    $pagina = intval($this->getQuery('page', 1));
    $por_pagina = 20;
    
    // Construir condiciones
    $conditions = [];
    if ($estado) {
        $conditions['procedencia_estado'] = $estado;
    }
    
    // Obtener huéspedes
    if ($buscar) {
        $huespedes = $this->huespedModel->buscar($buscar);
        $total = count($huespedes);
        // Paginar manualmente los resultados de búsqueda
        $offset = ($pagina - 1) * $por_pagina;
        $huespedes = array_slice($huespedes, $offset, $por_pagina);
    } else {
        $resultado = $this->huespedModel->paginate($por_pagina, $pagina, $conditions);
        $huespedes = $resultado['data'];
        $total = $resultado['total'];
    }
    
    // Agregar conteo de reservaciones a cada huésped
    foreach ($huespedes as &$huesped) {
        $huesped['total_reservaciones'] = $this->huespedModel->contarReservaciones($huesped['id']);
    }
    
    // Calcular paginación
    $total_paginas = ceil($total / $por_pagina);
    
    // Obtener estadísticas usando el nuevo método
    $estadisticas = $this->huespedModel->obtenerEstadisticas();
    
    View::renderTemplate('huespedes/index', [
        'title' => 'Huéspedes - ' . current_hotel_display_name(),
        'huespedes' => $huespedes,
        'buscar' => $buscar,
        'estado_filtro' => $estado,
        'estados' => Huesped::getEstados(),
        'pagina_actual' => $pagina,
        'total_paginas' => $total_paginas,
        'total_huespedes' => $estadisticas['total_huespedes'] ?? 0,
        'huespedes_con_vehiculo' => $estadisticas['con_vehiculo'] ?? 0,
        'estadisticas' => $estadisticas
    ]);
}

public function debugMovimientosAction() {
    $db = Database::getInstance();
    
    echo "<h3>Debug de Movimientos</h3>";
    
    // 1. Verificar total de movimientos
    $sql = "SELECT COUNT(*) as total FROM movimientos_inventario";
    $stmt = $db->query($sql);
    $total = $stmt->fetch();
    echo "<p><strong>Total de movimientos:</strong> " . $total['total'] . "</p>";
    
    // 2. Verificar tablas relacionadas
    echo "<h4>Verificando tablas relacionadas:</h4>";
    
    // Verificar inventario_productos
    $sql = "SELECT COUNT(*) as total FROM inventario_productos";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>inventario_productos: " . $result['total'] . " registros</p>";
    
    // Verificar usuarios
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>usuarios: " . $result['total'] . " registros</p>";
    
    // Verificar habitaciones
    $sql = "SELECT COUNT(*) as total FROM habitaciones";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>habitaciones: " . $result['total'] . " registros</p>";
    
    // 3. Probar JOIN simple con productos
    echo "<h4>Probando JOIN con productos:</h4>";
    $sql = "SELECT m.id, m.tipo_movimiento, m.cantidad, p.nombre as producto_nombre
            FROM movimientos_inventario m
            LEFT JOIN inventario_productos p ON m.producto_id = p.id
            LIMIT 3";
    
    try {
        $stmt = $db->query($sql);
        if ($stmt) {
            $results = $stmt->fetchAll();
            echo "<pre>";
            print_r($results);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>Error en JOIN con productos</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Excepción: " . $e->getMessage() . "</p>";
    }
    
    // 4. Llamar al método del modelo
    echo "<h4>Probando método getMovimientosDetallados():</h4>";
    try {
        $movimientos = $this->movimientoModel->getMovimientosDetallados(5);
        echo "<pre>";
        print_r($movimientos);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error en getMovimientosDetallados: " . $e->getMessage() . "</p>";
    }
    
    die();
}

/**
 * Actualizar vehículo (AJAX)
 */
/**
 * Actualizar vehículo (AJAX)
 */
/**
 * Actualizar vehículo (AJAX)
 */
public function actualizarVehiculoAction() {
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    $this->validateCSRF();
    
    $vehiculo_id = intval($this->getPost('vehiculo_id'));
    
    // Validar que el vehículo existe
    $vehiculoModel = new HuespedVehiculo();
    $vehiculoActual = $vehiculoModel->find($vehiculo_id);
    
    if (!$vehiculoActual) {
        View::renderJSON(['success' => false, 'message' => 'Vehículo no encontrado']);
        return;
    }
    
    // Recopilar datos
    $vehiculoData = [
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => strtoupper(trim($this->getPost('placas'))),
        'color' => trim($this->getPost('color')),
        'estacionamiento' => $this->estacionamientoVehiculoDesdePost('estacionamiento')
    ];
    
    // Validaciones
    if (empty($vehiculoData['marca'])) {
        View::renderJSON(['success' => false, 'message' => 'La marca es obligatoria']);
        return;
    }
    
    // Si cambió las placas, verificar que no existan
    if (!empty($vehiculoData['placas']) && $vehiculoData['placas'] !== $vehiculoActual['placas']) {
        if ($vehiculoModel->existenPlacas($vehiculoData['placas'], $vehiculo_id)) {
            View::renderJSON(['success' => false, 'message' => 'Las placas ya están registradas en otro vehículo']);
            return;
        }
    }
    
    // Actualizar
    $result = $vehiculoModel->update($vehiculo_id, $vehiculoData);
    
    if ($result) {
        // Obtener el vehículo actualizado
        $vehiculoActualizado = $vehiculoModel->find($vehiculo_id);
        
        View::renderJSON([
            'success' => true, 
            'message' => 'Vehículo actualizado exitosamente',
            'vehiculo' => $vehiculoActualizado
        ]);
    } else {
        View::renderJSON(['success' => false, 'message' => 'Error al actualizar vehículo']);
    }
}

/**
 * Actualizar huésped (sin vehículos - se gestionan por separado)
 */
public function actualizarAction() {
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }
    
    $this->validateCSRF();
    
    $id = $this->route_params['id'] ?? 0;
    
    // Verificar que existe
    $huesped = $this->huespedModel->find($id);
    if (!$huesped) {
        set_mensaje('Huésped no encontrado', 'error');
        $this->redirect('huespedes');
    }
    
    // Recopilar datos (sin campos de vehículo)
    $data = [
        'nombre_completo' => trim($this->getPost('nombre_completo')),
        'telefono' => trim($this->getPost('telefono')),
        'email' => trim($this->getPost('email')),
        'procedencia_estado' => $this->getPost('procedencia_estado'),
        'procedencia_ciudad' => trim($this->getPost('procedencia_ciudad')),
        'notas' => trim($this->getPost('notas'))
    ];
    
    // Validar datos
    $errores = $this->huespedModel->validar($data, $id);
    
    // Verificar teléfono único (excluyendo el actual)
    if (!empty($data['telefono']) && $data['telefono'] !== $huesped['telefono']) {
        if ($this->huespedModel->existeTelefono($data['telefono'], $id)) {
            $errores[] = 'Ya existe otro huésped con ese número de teléfono';
        }
    }
    
    if (!empty($errores)) {
        set_mensaje(implode('<br>', $errores), 'error');
        $this->redirect('huespedes/' . $id . '/edit');
    }
    
    // Actualizar
    if ($this->huespedModel->update($id, $data)) {
        set_mensaje('Huésped actualizado exitosamente', 'success');
        $this->redirect('huespedes/' . $id);
    } else {
        set_mensaje('Error al actualizar el huésped', 'error');
        $this->redirect('huespedes/' . $id . '/edit');
    }
}
    
    /**
     * Ver detalle de huésped
     */
    public function verAction() {
    $id = $this->route_params['id'] ?? 0;
    
    $huesped = $this->huespedModel->find($id);
    
    if (!$huesped) {
        set_mensaje('Huésped no encontrado', 'error');
        $this->redirect('huespedes');
    }
    
    // Obtener historial de reservaciones
    $reservaciones = $this->huespedModel->getReservaciones($id);
    
    // Obtener vehículos del huésped
    $vehiculos = $this->huespedModel->getVehiculos($id);
    
    // Calcular estadísticas
    // Calcular estadísticas del huésped (solo completadas)
$total_reservaciones = 0;
$total_gastado = 0;
$estados_validos = ['checked_in', 'checked_out', 'completada'];
$ultima_visita = null;

foreach ($reservaciones as $reservacion) {
    // Solo contar reservaciones que estén en estados válidos
    if (in_array($reservacion['estado'], $estados_validos)) {
        $total_reservaciones++;
        $total_gastado += $reservacion['precio_total'];
    }
}
    
    View::renderTemplate('huespedes/ver', [
        'title' => 'Huésped: ' . $huesped['nombre_completo'] . ' - ' . current_hotel_display_name(),
        'huesped' => $huesped,
        'vehiculos' => $vehiculos,
        'reservaciones' => $reservaciones,
        'total_reservaciones' => $total_reservaciones,
        'total_gastado' => $total_gastado,
        'ultima_visita' => $ultima_visita
    ]);
}
    
    /**
     * Mostrar formulario para crear huésped
     */
    public function crearAction() {
        error_log("=== DEBUG CREAR RESERVACIÓN ===");
    error_log("GET params: " . print_r($_GET, true));
    error_log("POST params: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));
        View::renderTemplate('huespedes/crear', [
            'title' => 'Nuevo Huésped - ' . current_hotel_display_name(),
            'estados' => Huesped::getEstados()
        ]);
    }
    
    public function agregarVehiculoAction() {
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
    }
    
    $this->validateCSRF();
    
    $huesped_id = intval($this->getPost('huesped_id'));
    $vehiculoData = [
        'huesped_id' => $huesped_id,
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => strtoupper(trim($this->getPost('placas'))),
        'color' => trim($this->getPost('color')),
        'estacionamiento' => $this->estacionamientoVehiculoDesdePost('estacionamiento')
    ];
    
    // Validaciones
    if (empty($vehiculoData['marca'])) {
        json_response(['success' => false, 'message' => 'La marca es obligatoria']);
    }
    
    $vehiculoModel = new HuespedVehiculo();
    
    if (!empty($vehiculoData['placas']) && $vehiculoModel->existenPlacas($vehiculoData['placas'])) {
        json_response(['success' => false, 'message' => 'Las placas ya están registradas']);
    }
    
    $result = $vehiculoModel->create($vehiculoData);
    
    if ($result) {
        json_response([
            'success' => true, 
            'message' => 'Vehículo agregado exitosamente',
            'vehiculo' => $result
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Error al agregar vehículo']);
    }
}

/**
 * Eliminar vehículo (soft delete - AJAX)
 */
/**
 * Eliminar vehículo (soft delete - AJAX)
 */
/**
 * Eliminar vehículo (soft delete - AJAX) - CON LOGGING PARA DEBUG
 */
public function eliminarVehiculoAction() {
    // LOG 1: Verificar que la función se está ejecutando
    error_log("=== ELIMINACIÓN VEHÍCULO DEBUG ===");
    error_log("1. Método eliminarVehiculoAction() ejecutándose");
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    
    // LOG 2: Verificar headers
    error_log("2. Headers importantes:");
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'No definido'));
    error_log("X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'No definido'));
    
    // LOG 3: Verificar datos POST
    error_log("3. Datos POST recibidos:");
    error_log("POST data: " . print_r($_POST, true));
    
    // LOG 4: Verificar verificaciones del controlador
    error_log("4. Verificaciones del controlador:");
    error_log("isAjax(): " . ($this->isAjax() ? 'true' : 'false'));
    error_log("isPost(): " . ($this->isPost() ? 'true' : 'false'));
    
    if (!$this->isAjax() || !$this->isPost()) {
        error_log("5. ERROR: Fallo en verificación AJAX o POST");
        error_log("Enviando respuesta de método no permitido");
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    error_log("5. Verificaciones AJAX y POST pasaron correctamente");
    
    try {
        // LOG 6: CSRF
        error_log("6. Validando CSRF...");
        $this->validateCSRF();
        error_log("CSRF validado correctamente");
        
        // LOG 7: Obtener ID
        $vehiculo_id = intval($this->getPost('vehiculo_id'));
        error_log("7. ID del vehículo: " . $vehiculo_id);
        
        if (!$vehiculo_id) {
            error_log("ERROR: ID de vehículo vacío o inválido");
            View::renderJSON(['success' => false, 'message' => 'ID de vehículo requerido']);
            return;
        }
        
        // LOG 8: Verificar modelo
        error_log("8. Creando instancia del modelo...");
        $vehiculoModel = new HuespedVehiculo();
        error_log("Modelo creado correctamente");
        
        // LOG 9: Buscar vehículo
        error_log("9. Buscando vehículo con ID: " . $vehiculo_id);
        $vehiculo = $vehiculoModel->find($vehiculo_id);
        error_log("Resultado de búsqueda: " . ($vehiculo ? 'Encontrado' : 'No encontrado'));
        
        if (!$vehiculo) {
            error_log("ERROR: Vehículo no encontrado en la base de datos");
            View::renderJSON(['success' => false, 'message' => 'Vehículo no encontrado']);
            return;
        }
        
        // LOG 10: Intentar desactivar
        error_log("10. Intentando desactivar vehículo...");
        $result = $vehiculoModel->desactivar($vehiculo_id);
        error_log("Resultado de desactivación: " . ($result ? 'Éxito' : 'Fallo'));
        
        if ($result) {
            error_log("11. ÉXITO: Vehículo eliminado correctamente");
            View::renderJSON(['success' => true, 'message' => 'Vehículo eliminado exitosamente']);
        } else {
            error_log("11. ERROR: Fallo al desactivar vehículo");
            View::renderJSON(['success' => false, 'message' => 'Error al eliminar vehículo']);
        }
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN CAPTURADA: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        View::renderJSON(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
    
    error_log("=== FIN DEBUG ELIMINACIÓN VEHÍCULO ===");
}
    
    /**
     * Guardar nuevo huésped
     */
    /**
 * Guardar nuevo huésped
 */
public function guardarAction() {
    // LOGGING PARA DEBUG
    error_log("=== DEBUG GUARDAR HUÉSPED ===");
    error_log("POST completo: " . print_r($_POST, true));
    
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }
    
    $this->validateCSRF();
    
    // Obtener return_to directamente
    $return_to = $_GET['return_to'] ?? null;
    
    // Recopilar datos del huésped
    $data = [
        'nombre_completo' => trim($this->getPost('nombre_completo')),
        'telefono' => trim($this->getPost('telefono')),
        'email' => trim($this->getPost('email')),
        'procedencia_estado' => $this->getPost('procedencia_estado'),
        'procedencia_ciudad' => trim($this->getPost('procedencia_ciudad')),
        'notas' => trim($this->getPost('notas'))
    ];
    
    // Validar datos
    $errores = $this->huespedModel->validar($data);
    
    // Verificar si ya existe un huésped con el mismo teléfono
    if (!empty($data['telefono']) && $this->huespedModel->existeTelefono($data['telefono'])) {
        $errores[] = 'Ya existe un huésped registrado con ese número de teléfono';
    }
    
    // Obtener vehículos del formulario
    $vehiculos = $this->getPost('vehiculos', []);
    $vehiculosValidos = [];
    
    // VALIDACIÓN MEJORADA DE VEHÍCULOS
    if (is_array($vehiculos)) {
        $vehiculoModel = new HuespedVehiculo();
        
        foreach ($vehiculos as $index => $vehiculo) {
            // Verificar que $vehiculo sea un array
            if (!is_array($vehiculo)) {
                error_log("Vehículo en índice $index no es array: " . print_r($vehiculo, true));
                continue;
            }
            
            // Obtener valores con validación segura
            $marca = isset($vehiculo['marca']) ? trim($vehiculo['marca']) : '';
            $placas = isset($vehiculo['placas']) ? trim($vehiculo['placas']) : '';
            $modelo = isset($vehiculo['modelo']) ? trim($vehiculo['modelo']) : '';
            $color = isset($vehiculo['color']) ? trim($vehiculo['color']) : '';
            $estacionamiento = $this->normalizarEstacionamientoVehiculo($vehiculo['estacionamiento'] ?? '');
            
            // Solo procesar si tiene al menos marca o placas
            if (!empty($marca) || !empty($placas)) {
                // Validar placas únicas
                if (!empty($placas)) {
                    $placas = strtoupper($placas);
                    if ($vehiculoModel->existenPlacas($placas)) {
                        $errores[] = "Las placas {$placas} ya están registradas";
                        continue;
                    }
                }
                
                // Validar que tenga al menos marca
                if (empty($marca)) {
                    $errores[] = "El vehículo " . ($index + 1) . " debe tener al menos la marca";
                    continue;
                }
                
                $vehiculosValidos[] = [
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'placas' => $placas,
                    'color' => $color,
                    'estacionamiento' => $estacionamiento
                ];
            }
        }
    }
    
    if (!empty($errores)) {
        set_mensaje(implode('<br>', $errores), 'error');
        save_old_input($_POST);
        $this->redirect('huespedes/create');
    }
    
    // Iniciar transacción
    $db = Database::getInstance();
    $db->beginTransaction();
    
    try {
        // Crear huésped
        $huesped_id = $this->huespedModel->create($data);
        
        if (!$huesped_id) {
            throw new Exception('Error al registrar el huésped');
        }
        
        // Guardar vehículos
        foreach ($vehiculosValidos as $vehiculo) {
            $vehiculoData = [
                'huesped_id' => $huesped_id,
                'marca' => $vehiculo['marca'],
                'modelo' => $vehiculo['modelo'],
                'placas' => $vehiculo['placas'],
                'color' => $vehiculo['color'],
                'estacionamiento' => $vehiculo['estacionamiento'],
                'activo' => 1
            ];
            
            $result = $vehiculoModel->create($vehiculoData);
            if (!$result) {
                throw new Exception('Error al registrar vehículo');
            }
        }
        
        // Confirmar transacción
        $db->commit();
        
        clear_old_input();
        set_mensaje('Huésped registrado exitosamente', 'success');
        
        // Redirección basada en return_to
if ($return_to == 'reservacion') {
    $this->redirect('reservaciones/crear?huesped_id=' . $huesped_id);
} else if ($return_to == 'reservacion_rapida') {
    // Recuperar datos de la reservación rápida
    $habitacion_id = $this->getQuery('habitacion_id');
    $fecha_entrada = $this->getQuery('fecha_entrada');
    $fecha_salida = $this->getQuery('fecha_salida');
    $hora_llegada = $this->getQuery('hora_llegada');
    
    $params = [
        'huesped_id' => $huesped_id,
        'from_rapid' => 'true',
        'preseleccion' => 'true'
    ];
    
    if ($habitacion_id) $params['habitacion_id'] = $habitacion_id;
    if ($fecha_entrada) $params['fecha_entrada'] = $fecha_entrada;
    if ($fecha_salida) $params['fecha_salida'] = $fecha_salida;
    if ($hora_llegada) $params['hora_llegada'] = $hora_llegada;
    
    $query_string = http_build_query($params);
    $this->redirect('reservaciones/crear?' . $query_string);
} else {
    $this->redirect('huespedes/' . $huesped_id);
}
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error en transacción: " . $e->getMessage());
        set_mensaje('Error al registrar: ' . $e->getMessage(), 'error');
        save_old_input($_POST);
        $this->redirect('huespedes/create');
    }
}
    
    /**
     * Mostrar formulario para editar huésped
     */
    public function editarAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $huesped = $this->huespedModel->find($id);
        
        if (!$huesped) {
            set_mensaje('Huésped no encontrado', 'error');
            $this->redirect('huespedes');
        }
        
        View::renderTemplate('huespedes/editar', [
            'title' => 'Editar Huésped - ' . current_hotel_display_name(),
            'huesped' => $huesped,
            'estados' => Huesped::getEstados()
        ]);
    }
    
   
    /**
     * Buscar huéspedes (AJAX)
     */
    public function buscarAction() {
        if (!$this->isAjax()) {
            $this->redirect('huespedes');
        }
        
        $termino = $this->getQuery('q', '');
        
        if (strlen($termino) < 2) {
            View::renderJSON([
                'success' => true,
                'data' => []
            ]);
            return;
        }
        
        $huespedes = $this->huespedModel->buscarAutocompletado($termino);
        
        View::renderJSON([
            'success' => true,
            'data' => $huespedes
        ]);
    }
}
