<?php require_once __DIR__ . '/../models/IncrementoTarifa.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
/**
 * Controlador de Habitaciones
 * Sistema hotelero
 */

class HabitacionController extends Controller {
    private $habitacionModel;
    private $habitacionImagenModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->habitacionModel = new Habitacion();
        $this->habitacionImagenModel = new HabitacionImagen();
    }
    
    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('habitaciones');
        return true;
    }
    
    /**
     * Listado de habitaciones
     */
    /**
 * Listado de habitaciones - ACTUALIZADO CON DETECCIÓN DE "POR LLEGAR"
 */
/**
 * Listado de habitaciones - ACTUALIZADO CON DETECCIÓN DE "POR LLEGAR"
 */
 
 
/**
 * Listado de habitaciones - ACTUALIZADO CON TARIFAS DINÁMICAS
 */
public function indexAction() {
    // Obtener filtros
    $filtros = [
        'estado' => get('estado'),
        'tipo' => get('tipo'),
        'piso' => get('piso'),
        'buscar' => get('buscar'),
        'fecha_consulta' => get('fecha_consulta'),
        'mostrar_disponibilidad' => get('mostrar_disponibilidad')
    ];
    
    // Verificar si se está filtrando por fecha específica
    if (!empty($filtros['fecha_consulta']) && !empty($filtros['mostrar_disponibilidad'])) {
        $this->mostrarDisponibilidadPorFecha($filtros);
        return;
    }
    
    // Construir query base
    $conditions = ['activa' => 1];
    
    // Aplicar filtros (excepto estado que se maneja después)
    if ($filtros['tipo']) {
        $conditions['tipo'] = $filtros['tipo'];
    }
    if ($filtros['piso'] !== null && $filtros['piso'] !== '') {
        $conditions['piso'] = $filtros['piso'];
    }
    
    // Obtener habitaciones
    if ($filtros['buscar']) {
        $habitaciones = $this->habitacionModel->buscar($filtros['buscar']);
    } else {
        $habitaciones = $this->habitacionModel->where($conditions);
    }
    
    // Obtener reservaciones de hoy que no han hecho check-in
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    $sql = "SELECT 
            rh.habitacion_id,
            r.id as reservacion_id,
            r.hora_llegada_estimada,
            h.nombre_completo,
            h.telefono,
            COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id
            WHERE hab_scope.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'
            GROUP BY rh.habitacion_id, r.id";
    
    $stmt = $db->query($sql, [$hotelId]);
    $reservaciones_pendientes = [];
    
    while ($row = $stmt->fetch()) {
        $reservaciones_pendientes[$row['habitacion_id']] = $row;
    }
    
    // Cargar modelo de tarifas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    $fecha_consulta = date('Y-m-d');
    
    // Procesar cada habitación
    foreach ($habitaciones as &$habitacion) {
        // Calcular precio con incrementos
        $precio_info = $tarifaModel->calcularPrecioConIncremento(
            $habitacion['id'],
            $habitacion['tipo'],
            $habitacion['precio_base'],
            $fecha_consulta
        );
        
        // Agregar información de tarifas
        $habitacion['precio_base_original'] = $habitacion['precio_base'];
        $habitacion['precio_actual'] = $precio_info['precio_final'];
        $habitacion['incremento_total'] = $precio_info['incremento_total'];
        $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
        $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
        
        // Verificar si tiene reservación pendiente para hoy
        if (isset($reservaciones_pendientes[$habitacion['id']])) {
            $habitacion['estado_display'] = 'por_llegar';
            $habitacion['reservacion_pendiente'] = $reservaciones_pendientes[$habitacion['id']];
        } else {
            $habitacion['estado_display'] = $habitacion['estado'];
            
            // Información adicional según estado
            if ($habitacion['estado'] == 'ocupada') {
                $habitacion['ocupacion_actual'] = $this->habitacionModel->getOcupacionActual($habitacion['id']);
            }
            if (in_array($habitacion['estado'], ['ocupada', 'limpieza'])) {
                $habitacion['proxima_salida'] = $this->habitacionModel->getProximaSalida($habitacion['id']);
            }
        }
    }
    
    // Aplicar filtro de estado si existe
    if ($filtros['estado']) {
        if ($filtros['estado'] == 'por_llegar') {
            // Filtrar solo habitaciones con estado_display = por_llegar
            $habitaciones = array_filter($habitaciones, function($hab) {
                return $hab['estado_display'] == 'por_llegar';
            });
        } else {
            // Filtrar por estado normal
            $habitaciones = array_filter($habitaciones, function($hab) use ($filtros) {
                return $hab['estado'] == $filtros['estado'];
            });
        }
    }
    
    // Obtener estadísticas actualizadas
    $estadisticas = $this->habitacionModel->estadisticas();
    $estadisticas['por_llegar'] = count($reservaciones_pendientes);
    $estadisticas['disponibles_real'] = $estadisticas['disponibles'] - $estadisticas['por_llegar'];
    
    // Ordenar habitaciones por número
    usort($habitaciones, function($a, $b) {
        return strnatcmp($a['numero'], $b['numero']);
    });
    
    // Agregar estados para la vista
    $estados = Habitacion::getEstados();
    $estados['por_llegar'] = ['label' => 'Por llegar', 'color' => 'purple', 'icon' => 'clock'];
    
    View::renderTemplate('habitaciones/index', [
        'title' => 'Habitaciones - ' . current_hotel_display_name(),
        'habitaciones' => $habitaciones,
        'estadisticas' => $estadisticas,
        'filtros' => $filtros,
        'tipos' => $this->catalogoTiposHabitacion(),
        'estados' => $estados,
        'pisos' => $this->catalogoPisosHabitacion()
    ]);
}

private function mostrarDisponibilidadPorFecha($filtros) {
    $fecha_consulta = $filtros['fecha_consulta'];
    
    error_log("===== INICIO DISPONIBILIDAD POR FECHA =====");
    error_log("Fecha: $fecha_consulta");
    
    $db = Database::getInstance();
    
    // Obtener TODAS las habitaciones activas primero
    $conditions = ['activa' => 1];
    $todasHabitaciones = [];
    
    if (!empty($filtros['buscar'])) {
        $todasHabitaciones = $this->habitacionModel->buscar($filtros['buscar']);
    } else {
        if (!empty($filtros['tipo'])) {
            $conditions['tipo'] = $filtros['tipo'];
        }
        if ($filtros['piso'] !== null && $filtros['piso'] !== '') {
            $conditions['piso'] = $filtros['piso'];
        }
        
        $todasHabitaciones = $this->habitacionModel->where($conditions);
    }
    
    error_log("Total habitaciones obtenidas: " . count($todasHabitaciones));
    
    // LOG: Ver cuáles habitaciones tenemos
    foreach ($todasHabitaciones as $h) {
        if ($h['numero'] == '06' || $h['numero'] == '07') {
            error_log("Habitación {$h['numero']} encontrada - ID: {$h['id']}, Estado: {$h['estado']}");
        }
    }
    
    // Si hay filtro de estado para mantenimiento o limpieza, aplicarlo
if (!empty($filtros['estado']) && $filtros['estado'] === 'mantenimiento') {
        error_log("FILTRO DE ESTADO APLICADO: {$filtros['estado']}");
        $antes = count($todasHabitaciones);
        $todasHabitaciones = array_filter($todasHabitaciones, function($h) use ($filtros) {
            return $h['estado'] == $filtros['estado'];
        });
        $despues = count($todasHabitaciones);
        error_log("Habitaciones antes del filtro: $antes, después: $despues");
    }
    
    // Query para obtener ocupadas - Todas las habitaciones ocupadas EN la fecha específica
    // NO incluye las que hacen check-out ese día (porque ese día se desocupan)
    // Incluye checked_out para mostrar correctamente fechas pasadas
    $hotelId = $this->hotelIdActual();
    $sql_ocupadas = "SELECT DISTINCT 
                     rh.habitacion_id,
                     hab.numero as habitacion_numero,
                     r.id as reservacion_id,
                     r.fecha_entrada, 
                     r.fecha_salida, 
                     r.estado as estado_reservacion,
                     h.nombre_completo
            FROM reservacion_habitaciones rh
            INNER JOIN reservaciones r ON rh.reservacion_id = r.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
            WHERE r.estado IN ('confirmada', 'checked_in', 'checked_out')
            AND hab.hotel_id = ?
            AND DATE(r.fecha_entrada) <= ?
            AND DATE(r.fecha_salida) > ?
            ORDER BY hab.numero";
    
    error_log("=== EJECUTANDO QUERY DE OCUPADAS ===");
    $stmt = $db->query($sql_ocupadas, [$hotelId, $fecha_consulta, $fecha_consulta]);
    $ocupadas = [];
    
    while ($row = $stmt->fetch()) {
        if ($row['habitacion_numero'] == '06' || $row['habitacion_numero'] == '07') {
            error_log("*** ENCONTRADA EN QUERY: Hab {$row['habitacion_numero']} ***");
            error_log("  ID: {$row['habitacion_id']}");
            error_log("  Entrada: {$row['fecha_entrada']}");
            error_log("  Salida: {$row['fecha_salida']}");
            error_log("  Huésped: {$row['nombre_completo']}");
        }
        
        $ocupadas[$row['habitacion_id']] = [
            'reservacion_id' => $row['reservacion_id'],
            'fecha_entrada' => $row['fecha_entrada'],
            'fecha_salida' => $row['fecha_salida'],
            'huesped' => $row['nombre_completo']
        ];
    }
    
    error_log("Total ocupadas: " . count($ocupadas));
    
    // Cargar modelo de tarifas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    
    // Procesar habitaciones
    $habitaciones_procesadas = [];
    $disponibles = 0;
    $ocupadas_count = 0;
    
    foreach ($todasHabitaciones as $habitacion) {
        // Calcular precio
        $precio_info = $tarifaModel->calcularPrecioConIncremento(
            $habitacion['id'],
            $habitacion['tipo'],
            $habitacion['precio_base'],
            $fecha_consulta
        );
        
        $habitacion['precio_base_original'] = $habitacion['precio_base'];
        $habitacion['precio_actual'] = $precio_info['precio_final'];
        $habitacion['incremento_total'] = $precio_info['incremento_total'];
        $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
        $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
        
        // Determinar estado
        if ($habitacion['estado'] == 'mantenimiento') {
    $habitacion['estado_display'] = $habitacion['estado'];
            error_log("Hab {$habitacion['numero']}: {$habitacion['estado']}");
        } elseif (isset($ocupadas[$habitacion['id']])) {
            $habitacion['info_ocupacion'] = $ocupadas[$habitacion['id']];
            $habitacion['estado_display'] = 'ocupada_fecha';
            $ocupadas_count++;
            error_log("Hab {$habitacion['numero']}: OCUPADA - {$ocupadas[$habitacion['id']]['huesped']}");
        } else {
            $habitacion['estado_display'] = 'disponible_fecha';
            $disponibles++;
            error_log("Hab {$habitacion['numero']}: DISPONIBLE");
        }
        
        $habitaciones_procesadas[] = $habitacion;
    }
    
    error_log("=== ESTADÍSTICAS ===");
    error_log("Disponibles: $disponibles");
    error_log("Ocupadas: $ocupadas_count");
    
    // Aplicar filtro de estado si existe
    if (!empty($filtros['estado']) && !in_array($filtros['estado'], ['mantenimiento', 'limpieza'])) {
        if ($filtros['estado'] == 'disponible') {
            $habitaciones_procesadas = array_filter($habitaciones_procesadas, function($h) {
                return $h['estado_display'] == 'disponible_fecha';
            });
        } elseif ($filtros['estado'] == 'ocupada') {
            $habitaciones_procesadas = array_filter($habitaciones_procesadas, function($h) {
                return $h['estado_display'] == 'ocupada_fecha';
            });
        }
    }
    
    // Reindexar y ordenar
    $habitaciones_procesadas = array_values($habitaciones_procesadas);
    usort($habitaciones_procesadas, function($a, $b) {
        return strnatcmp($a['numero'], $b['numero']);
    });
    
    // Estadísticas
    $estadisticas = [
        'total' => count($habitaciones_procesadas),
        'disponibles' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'disponible_fecha'; 
        })),
        'ocupadas' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'ocupada_fecha'; 
        })),
        'por_llegar' => 0,
        'limpieza' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'limpieza'; 
        })),
        'mantenimiento' => count(array_filter($habitaciones_procesadas, function($h) { 
            return $h['estado_display'] == 'mantenimiento'; 
        }))
    ];
    
    error_log("=== FIN DISPONIBILIDAD ===");
    
    // Estados para la vista
    $estados = Habitacion::getEstados();
    $estados['disponible_fecha'] = ['label' => 'Disponible', 'color' => 'green', 'icon' => 'check-circle'];
    $estados['ocupada_fecha'] = ['label' => 'Ocupada', 'color' => 'red', 'icon' => 'user'];
    
    View::renderTemplate('habitaciones/index', [
        'title' => 'Disponibilidad ' . format_date($fecha_consulta) . ' - ' . current_hotel_display_name(),
        'habitaciones' => $habitaciones_procesadas,
        'estadisticas' => $estadisticas,
        'filtros' => $filtros,
        'tipos' => $this->catalogoTiposHabitacion(),
        'estados' => $estados,
        'pisos' => $this->catalogoPisosHabitacion(),
        'mostrar_disponibilidad_fecha' => true,
        'fecha_consultada' => $fecha_consulta
    ]);
}
    
    /**
     * Ver detalle de habitación
     */
    /**
 * Ver detalle de habitación - ACTUALIZADO CON DETECCIÓN DE LLEGADAS PENDIENTES
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON DETECCIÓN DE LLEGADAS PENDIENTES
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON TARIFAS DINÁMICAS
 */
/**
 * Ver detalle de habitación - ACTUALIZADO CON TARIFAS DINÁMICAS Y OCUPACIÓN ACTUAL
 */
public function verAction() {
    $id = $this->route_params['id'] ?? 0;
    
    $habitacion = $this->habitacionModel->find($id);
    
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
    }
    
    // Obtener información adicional
    $ocupacion_actual = null;
    // IMPORTANTE: Obtener ocupación actual SIEMPRE
$ocupacion_actual = $this->habitacionModel->getOcupacionActual($id);

// DEBUG TEMPORAL - AGREGAR ESTAS LÍNEAS
error_log("DEBUG ocupacion_actual para habitacion $id:");
error_log(print_r($ocupacion_actual, true));
    $proxima_salida = null;
    $historial_reciente = [];
    $mantenimiento_actual = null;
    $reservacion_pendiente = null;
    
    // NUEVO: Obtener información de tarifas dinámicas
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    $fecha_consulta = date('Y-m-d');
    
    // Calcular precio con incrementos
    $precio_info = $tarifaModel->calcularPrecioConIncremento(
        $habitacion['id'],
        $habitacion['tipo'],
        $habitacion['precio_base'],
        $fecha_consulta
    );
    
    // Agregar información de tarifas a la habitación
    $habitacion['precio_base_original'] = $habitacion['precio_base'];
    $habitacion['precio_actual'] = $precio_info['precio_final'];
    $habitacion['incremento_total'] = $precio_info['incremento_total'];
    $habitacion['incrementos_aplicados'] = $precio_info['incrementos_aplicados'];
    $habitacion['tiene_incremento'] = ($precio_info['incremento_total'] > 0);
    
    // Verificar si hay reservación pendiente para hoy
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    $sql = "SELECT 
            r.id as reservacion_id,
            r.hora_llegada_estimada,
            r.fecha_entrada,
            r.precio_total,
            h.nombre_completo,
            h.telefono,
            COUNT(DISTINCT rh2.habitacion_id) as total_habitaciones
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh2 ON r.id = rh2.reservacion_id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.fecha_entrada = CURDATE()
            AND r.estado = 'confirmada'
            GROUP BY r.id
            LIMIT 1";
    
    $stmt = $db->query($sql, [$id, $hotelId]);
    $reservacion_pendiente = $stmt->fetch();
    
    // Determinar el estado real de visualización
    if ($reservacion_pendiente) {
        $habitacion['estado_display'] = 'por_llegar';
    } else {
        $habitacion['estado_display'] = $habitacion['estado'];
    }
    
    // IMPORTANTE: Obtener ocupación actual SIEMPRE, no solo cuando estado es 'ocupada'
    // Esto permitirá mostrar el huésped actual incluso cuando hay doble movimiento
    $ocupacion_actual = $this->habitacionModel->getOcupacionActual($id);
    
    // Obtener próxima salida si aplica
    if (in_array($habitacion['estado'], ['ocupada', 'limpieza'])) {
        $proxima_salida = $this->habitacionModel->getProximaSalida($id);
    }
    
    // Obtener mantenimiento actual si aplica
    if ($habitacion['estado'] == 'mantenimiento') {
        $mantenimiento_actual = $this->obtenerMantenimientoActual($id);
    }
    
    // Obtener mantenimientos programados
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    $mantenimientos_programados = $mantenimientoModel->programadosPorHabitacion($id);
    
    // Obtener historial reciente
    $historial_reciente = [];

    try {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                r.id,
                r.fecha_entrada,
                r.fecha_salida,
                r.estado,
                r.notas as observaciones,
                r.created_at,
                r.precio_total,
                r.total_habitaciones,
                h.nombre_completo as nombre_huesped,
                h.telefono,
                h.procedencia_estado,
                h.procedencia_ciudad,
                h.vehiculo_marca,
                h.vehiculo_placas,
                CASE 
                    WHEN h.vehiculo_marca IS NOT NULL AND h.vehiculo_marca != '' THEN 1
                    ELSE 0
                END as vehiculos
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            WHERE 
                rh.habitacion_id = ?
                AND hab_scope.hotel_id = ?
                AND r.estado IN ('checked_out', 'checked_in', 'confirmada')
            GROUP BY r.id
            ORDER BY r.fecha_salida DESC, r.created_at DESC
            LIMIT 10";
        
        $stmt = $db->query($sql, [$id, $this->hotelIdActual()]);
        
        if ($stmt !== false) {
            $historial_reciente = $stmt->fetchAll();
            
            // Procesar los datos
            if ($historial_reciente && is_array($historial_reciente)) {
                foreach ($historial_reciente as &$reservacion) {
                    // Combinar ciudad y estado en procedencia
                    $procedencia_parts = [];
                    if (!empty($reservacion['procedencia_ciudad'])) {
                        $procedencia_parts[] = $reservacion['procedencia_ciudad'];
                    }
                    if (!empty($reservacion['procedencia_estado'])) {
                        $procedencia_parts[] = $reservacion['procedencia_estado'];
                    }
                    $reservacion['procedencia'] = implode(', ', $procedencia_parts);
                    
                    // Asegurar que precio_total tenga un valor
                    if (empty($reservacion['precio_total'])) {
                        $reservacion['precio_total'] = 0;
                    }
                    
                    // Asegurar que observaciones exista
                    if (!isset($reservacion['observaciones'])) {
                        $reservacion['observaciones'] = '';
                    }
                    
                    // Agregar campo folio vacío (ya que no existe en la BD)
                    $reservacion['folio'] = '';
                    
                    // Usar total_habitaciones que sí existe en la tabla
                    if (empty($reservacion['total_habitaciones'])) {
                        $reservacion['total_habitaciones'] = 1;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en historial: " . $e->getMessage());
        $historial_reciente = [];
    }

    // Asegurar que siempre sea un array
    if (!is_array($historial_reciente)) {
        $historial_reciente = [];
    }
    
    View::renderTemplate('habitaciones/ver', [
        'title' => 'Habitación ' . $habitacion['numero'] . ' - ' . current_hotel_display_name(),
        'habitacion' => $habitacion,
        'ocupacion_actual' => $ocupacion_actual,
        'proxima_salida' => $proxima_salida,
        'historial_reciente' => $historial_reciente,
        'mantenimiento_actual' => $mantenimiento_actual,
        'mantenimientos_programados' => $mantenimientos_programados ?? [],
        'reservacion_pendiente' => $reservacion_pendiente,
        'estados' => Habitacion::getEstados(),
        'tipos' => $this->catalogoTiposHabitacion()
    ]);
}
    
    /**
     * Mostrar formulario para crear habitación
     */
    public function crearAction() {
        $this->requirePermission('habitaciones.create');
        
        View::renderTemplate('habitaciones/crear', [
            'title' => 'Nueva Habitación - ' . current_hotel_display_name(),
            'tipos' => $this->catalogoTiposHabitacion(),
            'pisos' => $this->catalogoPisosHabitacion(),
            'amenidades' => $this->catalogoAmenidadesHabitacion()
        ]);
    }
    
    /**
     * Guardar nueva habitación CON MÚLTIPLES IMÁGENES
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        // Recopilar datos básicos
        $data = [
            'numero' => trim($this->getPost('numero')),
            'tipo' => $this->getPost('tipo'),
            'piso' => intval($this->getPost('piso')),
            'precio_base' => floatval($this->getPost('precio_base')),
            'estado' => 'disponible',
            'activa' => $this->getPost('activa') ? 1 : 0,
            'hotel_id' => $this->hotelIdActual()
        ];
        
        // Procesar características
        $caracteristicas_especiales = $this->getPost('caracteristicas_especiales', []);
        $caracteristicas_custom = trim($this->getPost('caracteristicas'));
        
        if (!empty($caracteristicas_custom)) {
            $data['caracteristicas'] = $caracteristicas_custom;
        } else {
            $data['caracteristicas'] = $this->generarDescripcionCaracteristicas($data['tipo'], $caracteristicas_especiales);
        }
        
        // Validar datos contra los catalogos configurables del hotel
        $errores = $this->validarHabitacionConCatalogos($data);
        
        // Verificar número único
        if ($this->habitacionModel->exists(['numero' => $data['numero']])) {
            $errores[] = 'Ya existe una habitación con ese número';
        }
        
        if (!empty($errores)) {
            save_old_input($_POST);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('habitaciones/create');
        }
        
        // Iniciar transacción
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Crear habitación
            $habitacion = $this->habitacionModel->create($data);
            
            if (!$habitacion) {
                throw new Exception('Error al crear la habitación');
            }
            
            // Procesar múltiples imágenes si se subieron
            if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
                $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $habitacion['id'], $data['numero']);
                
                if (!$imagenes_procesadas['success']) {
                    throw new Exception($imagenes_procesadas['error']);
                }
                
                // Si hay imágenes, establecer la primera como principal en habitaciones.foto_url
                if (!empty($imagenes_procesadas['imagenes'])) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $this->habitacionModel->update($habitacion['id'], ['foto_url' => $primera_imagen['url']]);
                }
            }
            
            // Crear entrada en control de llaves
            $this->crearControlLlave($habitacion['id']);
            
            $db->commit();
            
            clear_old_input();
            set_mensaje('Habitación creada exitosamente', 'success');
            $this->redirect('habitaciones/' . $habitacion['id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            save_old_input($_POST);
            $this->redirect('habitaciones/create');
        }
    }
    /**
 * Historial completo de reservaciones de una habitación
 */
public function historial() {
    $id = $this->route_params['id'] ?? 0;
    
    // Obtener habitación usando el mismo método que verAction()
    $habitacion = $this->habitacionModel->find($id);
    
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    // Obtener TODO el historial de reservaciones (sin límite)
    $historial = [];
    
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT 
                r.id,
                r.fecha_entrada,
                r.fecha_salida,
                r.estado,
                r.notas as observaciones,
                r.created_at,
                r.precio_total,
                r.total_habitaciones,
                h.nombre_completo as nombre_huesped,
                h.telefono,
                h.procedencia_estado,
                h.procedencia_ciudad,
                h.vehiculo_marca,
                h.vehiculo_placas,
                CASE 
                    WHEN h.vehiculo_marca IS NOT NULL AND h.vehiculo_marca != '' THEN 1
                    ELSE 0
                END as vehiculos,
                h.id as huesped_id
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            WHERE 
                rh.habitacion_id = ?
                AND hab_scope.hotel_id = ?
                AND r.estado IN ('checked_out', 'checked_in', 'confirmada')
            GROUP BY r.id
            ORDER BY r.fecha_salida DESC, r.created_at DESC";
        
        $stmt = $db->query($sql, [$id, $this->hotelIdActual()]);
        
        if ($stmt !== false) {
            $historial = $stmt->fetchAll();
            
            // Procesar los datos igual que en verAction
            if ($historial && is_array($historial)) {
                foreach ($historial as &$reservacion) {
                    // Combinar ciudad y estado en procedencia
                    $procedencia_parts = [];
                    if (!empty($reservacion['procedencia_ciudad'])) {
                        $procedencia_parts[] = $reservacion['procedencia_ciudad'];
                    }
                    if (!empty($reservacion['procedencia_estado'])) {
                        $procedencia_parts[] = $reservacion['procedencia_estado'];
                    }
                    $reservacion['procedencia'] = implode(', ', $procedencia_parts);
                    
                    // Asegurar que precio_total tenga un valor
                    if (empty($reservacion['precio_total'])) {
                        $reservacion['precio_total'] = 0;
                    }
                    
                    // Asegurar que observaciones exista
                    if (!isset($reservacion['observaciones'])) {
                        $reservacion['observaciones'] = '';
                    }
                    
                    // Agregar campo folio vacío (ya que no existe en la BD)
                    $reservacion['folio'] = '';
                    
                    // Usar total_habitaciones que sí existe en la tabla
                    if (empty($reservacion['total_habitaciones'])) {
                        $reservacion['total_habitaciones'] = 1;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error en historial completo: " . $e->getMessage());
        $historial = [];
    }
    
    // Asegurar que siempre sea un array
    if (!is_array($historial)) {
        $historial = [];
    }
    
    // Renderizar la vista de historial
    View::renderTemplate('habitaciones/historial', [
        'title' => 'Historial Completo - Habitación ' . $habitacion['numero'],
        'habitacion' => $habitacion,
        'historial' => $historial,
        'tipos' => $this->catalogoTiposHabitacion()
    ]);
}
    /**
     * Mostrar formulario para editar habitación
     */
    public function editarAction() {
        $this->requirePermission('habitaciones.edit');
        
        $id = $this->route_params['id'] ?? 0;
        $habitacion = $this->habitacionModel->find($id);
        
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        View::renderTemplate('habitaciones/editar', [
            'title' => 'Editar Habitación - ' . current_hotel_display_name(),
            'habitacion' => $habitacion,
            'tipos' => $this->catalogoTiposHabitacion(),
            'pisos' => $this->catalogoPisosHabitacion(),
            'amenidades' => $this->catalogoAmenidadesHabitacion()
        ]);
    }
    
    /**
     * Actualizar habitación CON MÚLTIPLES IMÁGENES
     */
    public function actualizarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Recopilar datos
        $data = [
            'numero' => trim($this->getPost('numero')),
            'tipo' => $this->getPost('tipo'),
            'piso' => intval($this->getPost('piso')),
            'precio_base' => floatval($this->getPost('precio_base')),
            'activa' => $this->getPost('activa') ? 1 : 0
        ];
        
        // Procesar características
        $caracteristicas_especiales = $this->getPost('caracteristicas_especiales', []);
        $caracteristicas_custom = trim($this->getPost('caracteristicas'));
        
        if (!empty($caracteristicas_custom)) {
            $data['caracteristicas'] = $caracteristicas_custom;
        } else {
            $data['caracteristicas'] = $this->generarDescripcionCaracteristicas($data['tipo'], $caracteristicas_especiales);
        }
        
        // Validar datos contra los catalogos configurables del hotel
        $errores = $this->validarHabitacionConCatalogos($data);
        
        // Verificar número único (excluyendo la habitación actual)
        $sql = "SELECT COUNT(*) as total FROM habitaciones WHERE numero = ? AND id != ? AND hotel_id = ?";
        $stmt = Database::getInstance()->query($sql, [$data['numero'], $id, $this->hotelIdActual()]);
        if ($stmt->fetch()['total'] > 0) {
            $errores[] = 'Ya existe otra habitación con ese número';
        }
        
        // Manejar múltiples imágenes si se subieron nuevas
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $id, $data['numero']);
            
            if (!$imagenes_procesadas['success']) {
                $errores[] = $imagenes_procesadas['error'];
            } else if (!empty($imagenes_procesadas['imagenes'])) {
                // Si no hay imagen principal actual, establecer la primera nueva como principal
                $tiene_principal = $this->habitacionImagenModel->obtenerPrincipal($id);
                if (!$tiene_principal) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $data['foto_url'] = $primera_imagen['url'];
                }
            }
        }
        
        if (!empty($errores)) {
            save_old_input($_POST);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('habitaciones/' . $id . '/edit');
        }
        
        // Actualizar
        if ($this->habitacionModel->update($id, $data)) {
            clear_old_input();
            set_mensaje('Habitación actualizada exitosamente', 'success');
            $this->redirect('habitaciones/' . $id);
        } else {
            set_mensaje('Error al actualizar la habitación', 'error');
            save_old_input($_POST);
            $this->redirect('habitaciones/' . $id . '/edit');
        }
    }
    
    /**
     * Eliminar habitación
     */
    public function eliminarAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        $this->requirePermission('habitaciones.delete');
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Solo se puede eliminar si está disponible
        if ($habitacion['estado'] != 'disponible') {
            set_mensaje('Solo se pueden eliminar habitaciones disponibles', 'error');
            $this->redirect('habitaciones/' . $id);
        }
        
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // Eliminar imágenes relacionadas
            $imagenes = $this->habitacionImagenModel->porHabitacion($id);
            foreach ($imagenes as $imagen) {
                $this->eliminarImagenHabitacion($imagen['url']);
            }
            $db->query(
                "DELETE FROM habitacion_imagenes WHERE habitacion_id = ? AND hotel_id = ?",
                [$id, $this->hotelIdActual()]
            );
            
            // Eliminar imagen principal si existe
            if (!empty($habitacion['foto_url'])) {
                $this->eliminarImagenHabitacion($habitacion['foto_url']);
            }
            
            // Eliminar control de llaves
            $db->query("DELETE FROM control_llaves WHERE habitacion_id = ?", [$id]);
            
            // Eliminar habitación
            $this->habitacionModel->delete($id);
            
            $db->commit();
            
            set_mensaje('Habitación eliminada exitosamente', 'success');
            $this->redirect('habitaciones');
            
        } catch (Exception $e) {
            $db->rollBack();
            set_mensaje('Error al eliminar la habitación', 'error');
            $this->redirect('habitaciones/' . $id);
        }
    }
    
    /**
     * Gestionar imágenes de habitación
     */
    public function gestionarImagenesAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Obtener todas las imágenes
        $imagenes = $this->habitacionImagenModel->porHabitacion($id);
        
        View::renderTemplate('habitaciones/gestionar-imagenes', [
            'title' => 'Gestionar Imágenes - Habitación ' . $habitacion['numero'],
            'habitacion' => $habitacion,
            'imagenes' => $imagenes
        ]);
    }
    
    /**
     * Agregar imágenes adicionales
     */
    public function agregarImagenesAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        $this->requirePermission('habitaciones.edit');
        
        $id = $this->route_params['id'] ?? 0;
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            set_mensaje('Habitación no encontrada', 'error');
            $this->redirect('habitaciones');
        }
        
        // Verificar límite de imágenes
        $imagenes_actuales = $this->habitacionImagenModel->contarPorHabitacion($id);
        if ($imagenes_actuales >= 10) {
            set_mensaje('Has alcanzado el límite máximo de 10 imágenes por habitación', 'error');
            $this->redirect('habitaciones/' . $id . '/imagenes');
        }
        
        // Procesar nuevas imágenes
        if (isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
            $db = Database::getInstance();
            
            try {
                $db->beginTransaction();
                
                $imagenes_procesadas = $this->procesarMultiplesImagenes($_FILES['fotos'], $id, $habitacion['numero']);
                
                if (!$imagenes_procesadas['success']) {
                    throw new Exception($imagenes_procesadas['error']);
                }
                
                // Si no hay imagen principal actual, establecer la primera nueva como principal
                $tiene_principal = $this->habitacionImagenModel->obtenerPrincipal($id);
                if (!$tiene_principal && !empty($imagenes_procesadas['imagenes'])) {
                    $primera_imagen = $imagenes_procesadas['imagenes'][0];
                    $this->habitacionModel->update($id, ['foto_url' => $primera_imagen['url']]);
                    $this->habitacionImagenModel->establecerPrincipal($primera_imagen['id'], $id);
                }
                
                $db->commit();
                
                $cantidad = count($imagenes_procesadas['imagenes']);
                set_mensaje("Se agregaron {$cantidad} imagen(es) correctamente", 'success');
                
            } catch (Exception $e) {
                $db->rollBack();
                set_mensaje('Error al agregar imágenes: ' . $e->getMessage(), 'error');
            }
        } else {
            set_mensaje('No se seleccionaron imágenes', 'error');
        }
        
        $this->redirect('habitaciones/' . $id . '/imagenes');
    }
    
    /**
     * Eliminar imagen individual
     */
    /**
 * Eliminar imagen individual
 */
/**
 * Eliminar imagen individual
 */
public function eliminarImagenAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    
    // Obtener parámetros
    $imagen_id = intval($this->getPost('imagen_id'));
    $habitacion_id = intval($this->getPost('habitacion_id'));
    
    // Verificar que la imagen existe y pertenece a la habitación
    $imagen = $this->habitacionImagenModel->find($imagen_id);
    
    if (!$imagen || $imagen['habitacion_id'] != $habitacion_id) {
        set_mensaje('Imagen no encontrada', 'error');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    // Verificar permisos
    if (!can('habitaciones.edit')) {
        set_mensaje('No tienes permisos para eliminar imágenes', 'error');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    $db = Database::getInstance();
    
    try {
        $db->beginTransaction();
        
        // Eliminar archivo físico
        if (!empty($imagen['url'])) {
            $rutaCompleta = PUBLIC_PATH . '/' . ltrim($imagen['url'], '/');
            if (file_exists($rutaCompleta)) {
                if (!@unlink($rutaCompleta)) {
                    error_log("No se pudo eliminar el archivo: " . $rutaCompleta);
                }
            }
        }
        
        // Si era la imagen principal, buscar otra para establecer como principal
        if ($imagen['es_principal']) {
            // Quitar foto_url de la habitación
            $this->habitacionModel->update($habitacion_id, ['foto_url' => null]);
            
            // Buscar otra imagen para establecer como principal
            $sql = "SELECT * FROM habitacion_imagenes 
                    WHERE habitacion_id = ? AND id != ? AND hotel_id = ?
                    ORDER BY orden ASC, id ASC 
                    LIMIT 1";
            $stmt = $db->query($sql, [$habitacion_id, $imagen_id, $this->hotelIdActual()]);
            $nueva_principal = $stmt->fetch();
            
            if ($nueva_principal) {
                // Establecer la nueva imagen como principal
                $sql_update = "UPDATE habitacion_imagenes SET es_principal = 1 WHERE id = ? AND hotel_id = ?";
                $db->query($sql_update, [$nueva_principal['id'], $this->hotelIdActual()]);
                
                // Actualizar foto_url en la tabla habitaciones
                $this->habitacionModel->update($habitacion_id, ['foto_url' => $nueva_principal['url']]);
            }
        }
        
        // Eliminar registro de la base de datos
        $sql_delete = "DELETE FROM habitacion_imagenes WHERE id = ? AND hotel_id = ?";
        $stmt = $db->query($sql_delete, [$imagen_id, $this->hotelIdActual()]);
        
        if ($stmt->rowCount() > 0) {
            $db->commit();
            set_mensaje('Imagen eliminada correctamente', 'success');
        } else {
            throw new Exception('No se pudo eliminar el registro de la imagen');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error al eliminar imagen: " . $e->getMessage());
        set_mensaje('Error al eliminar la imagen: ' . $e->getMessage(), 'error');
    }
    
    $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
}
    
    /**
     * Establecer imagen principal
     */
    public function establecerPrincipalAction() {
        if (!$this->isPost()) {
            $this->redirect('habitaciones');
        }
        
        $this->validateCSRF();
        
        $imagen_id = intval($this->getPost('imagen_id'));
        $habitacion_id = intval($this->getPost('habitacion_id'));
        
        $imagen = $this->habitacionImagenModel->find($imagen_id);
        
        if (!$imagen || $imagen['habitacion_id'] != $habitacion_id) {
            set_mensaje('Imagen no encontrada', 'error');
            $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
        }
        
        // Establecer como principal
        $this->habitacionImagenModel->establecerPrincipal($imagen_id, $habitacion_id);
        
        // Actualizar foto_url en la tabla habitaciones
        $this->habitacionModel->update($habitacion_id, ['foto_url' => $imagen['url']]);
        
        set_mensaje('Imagen principal actualizada', 'success');
        $this->redirect('habitaciones/' . $habitacion_id . '/imagenes');
    }
    
    /**
     * Cambiar estado de habitación (mantenimiento)
     */
    /**
 * Cambiar estado de habitación (mantenimiento)
 */
public function mantenimientoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $id = $this->route_params['id'] ?? 0;
    $accion = $this->getPost('accion');
    
    $db = Database::getInstance();
    $habitacion = $this->habitacionModel->find($id);

    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
    }
    
    try {
        $db->beginTransaction();
        
        if ($accion == 'iniciar') {
            // Actualizar estado de habitación
            $this->habitacionModel->update($id, ['estado' => 'mantenimiento']);
            
            // Registrar en tabla de mantenimientos - AGREGADO fecha_inicio y estado
            $sql = "INSERT INTO mantenimientos_habitaciones 
                    (hotel_id, habitacion_id, tipo_mantenimiento, prioridad, motivo, usuario_registro_id, fecha_inicio, estado)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'en_proceso')";
            
            $db->query($sql, [
                $this->hotelIdActual(),
                $id,
                $this->getPost('tipo_mantenimiento'),
                $this->getPost('prioridad', 'media'),
                $this->getPost('motivo'),
                current_user('id')
            ]);
            
            $db->commit();
            set_mensaje('Mantenimiento iniciado correctamente', 'success');
            
        } elseif ($accion == 'finalizar') {
            // Actualizar estado de habitación
            $this->habitacionModel->update($id, ['estado' => 'disponible']);
            
            // Actualizar registro de mantenimiento
            $sql = "UPDATE mantenimientos_habitaciones 
                    SET estado = 'completado', fecha_fin = NOW() 
                    WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'";
            
            $db->query($sql, [$id, $this->hotelIdActual()]);
            
            $db->commit();
            set_mensaje('Mantenimiento finalizado correctamente', 'success');
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        set_mensaje('Error al procesar mantenimiento: ' . $e->getMessage(), 'error');
    }
    
    $this->redirect('habitaciones/' . $id);
}

/**
 * Programar mantenimiento futuro
 * POST /habitaciones/{id}/programar-mantenimiento
 */
public function programarMantenimientoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $id = $this->route_params['id'] ?? 0;
    
    $habitacion = $this->habitacionModel->find($id);
    if (!$habitacion) {
        set_mensaje('Habitación no encontrada', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    
    $fecha_inicio = $this->getPost('fecha_programada');
    $fecha_fin = $this->getPost('fecha_programada_fin');
    
    // Validaciones
    if (empty($fecha_inicio)) {
        set_mensaje('Debe indicar la fecha de inicio del mantenimiento', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    if ($fecha_inicio < date('Y-m-d')) {
        set_mensaje('La fecha programada no puede ser en el pasado', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    if (!empty($fecha_fin) && $fecha_fin < $fecha_inicio) {
        set_mensaje('La fecha de fin no puede ser anterior a la fecha de inicio', 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    // Verificar que no haya reservaciones que conflicten con las fechas
    $fecha_fin_check = $fecha_fin ?: $fecha_inicio;
    // Agregar un día extra al fin para cubrir el día completo
    $fecha_fin_check_ext = date('Y-m-d', strtotime($fecha_fin_check . ' +1 day'));
    
    $db = Database::getInstance();
    $hotelId = $this->hotelIdActual();
    $sql = "SELECT r.id, h.nombre_completo, r.fecha_entrada, r.fecha_salida
            FROM reservaciones r
            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
            INNER JOIN huespedes h ON r.huesped_id = h.id
            WHERE rh.habitacion_id = ?
            AND hab_scope.hotel_id = ?
            AND r.estado IN ('confirmada', 'checked_in')
            AND r.fecha_entrada < ?
            AND r.fecha_salida > ?
            LIMIT 5";
    
    $stmt = $db->query($sql, [$id, $hotelId, $fecha_fin_check_ext, $fecha_inicio]);
    $reservaciones_conflicto = $stmt->fetchAll();
    
    if (!empty($reservaciones_conflicto)) {
        $conflictos = [];
        foreach ($reservaciones_conflicto as $res) {
            $conflictos[] = $res['nombre_completo'] . ' (' . date('d/m/Y', strtotime($res['fecha_entrada'])) . ' - ' . date('d/m/Y', strtotime($res['fecha_salida'])) . ')';
        }
        set_mensaje('No se puede programar: hay reservaciones en esas fechas: ' . implode(', ', $conflictos), 'error');
        $this->redirect('habitaciones/' . $id);
        return;
    }
    
    $data = [
        'tipo_mantenimiento' => $this->getPost('tipo_mantenimiento', 'preventivo'),
        'prioridad' => $this->getPost('prioridad', 'media'),
        'motivo' => $this->getPost('motivo', ''),
        'descripcion' => $this->getPost('descripcion', ''),
        'fecha_programada' => $fecha_inicio,
        'fecha_programada_fin' => $fecha_fin ?: null,
        'usuario_registro_id' => current_user('id')
    ];
    
    $resultado = $mantenimientoModel->programar($id, $data);
    
    if ($resultado) {
        $fecha_txt = date('d/m/Y', strtotime($fecha_inicio));
        $fin_txt = $fecha_fin ? ' al ' . date('d/m/Y', strtotime($fecha_fin)) : '';
        set_mensaje("Mantenimiento programado para el {$fecha_txt}{$fin_txt} en habitación {$habitacion['numero']}", 'success');
    } else {
        set_mensaje('Error al programar el mantenimiento', 'error');
    }
    
    $this->redirect('habitaciones/' . $id);
}

/**
 * Cancelar mantenimiento programado
 * POST /habitaciones/cancelar-mantenimiento-programado/{id}
 */
public function cancelarMantenimientoProgramadoAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    $this->requirePermission('habitaciones.mantenimiento');
    
    $mantenimiento_id = $this->route_params['id'] ?? 0;
    
    require_once __DIR__ . '/../models/Mantenimiento.php';
    $mantenimientoModel = new Mantenimiento();
    
    $mantenimiento = $mantenimientoModel->find($mantenimiento_id);
    if (!$mantenimiento) {
        set_mensaje('Mantenimiento no encontrado', 'error');
        $this->redirect('habitaciones');
        return;
    }
    
    $motivo = $this->getPost('motivo_cancelacion', 'Cancelado por el usuario');
    $resultado = $mantenimientoModel->cancelarProgramado($mantenimiento_id, $motivo);
    
    if ($resultado) {
        set_mensaje('Mantenimiento programado cancelado correctamente', 'success');
    } else {
        set_mensaje('Error al cancelar el mantenimiento programado', 'error');
    }
    
    $this->redirect('habitaciones/' . $mantenimiento['habitacion_id']);
}

    /**
     * Liberar habitación
     */
    public function liberarAction() {
    if (!$this->isPost()) {
        $this->redirect('habitaciones');
    }
    
    $this->validateCSRF();
    
    $id = $this->route_params['id'] ?? 0;
    
    try {
        // Obtener datos de la habitación
        $habitacion = $this->habitacionModel->find($id);
        
        if (!$habitacion) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Habitación no encontrada'
            ]);
            exit;
        }
        
        // Verificar que esté en estado limpieza
        if ($habitacion['estado'] !== 'limpieza') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Solo se pueden liberar habitaciones en limpieza'
            ]);
            exit;
        }
        
        // Actualizar estado a disponible
        $actualizado = $this->habitacionModel->update($id, ['estado' => 'disponible']);
        
        if ($actualizado) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'numero' => $habitacion['numero'],
                'message' => 'Habitación marcada como disponible'
            ]);
            exit;
        } else {
            throw new Exception('No se pudo actualizar el estado');
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}

    
    /**
     * Vista de disponibilidad
     */
    public function disponiblesAction() {
        $fecha_entrada = $this->getQuery('fecha_entrada', date('Y-m-d'));
        $fecha_salida = $this->getQuery('fecha_salida', date('Y-m-d', strtotime('+1 day')));
        $tipo_filtro = $this->getQuery('tipo');
        
        // Obtener habitaciones disponibles
        $habitaciones_disponibles = $this->habitacionModel->disponiblesEnFechas($fecha_entrada, $fecha_salida, $tipo_filtro);
        
        View::renderTemplate('habitaciones/disponibles', [
            'title' => 'Disponibilidad de Habitaciones - ' . current_hotel_display_name(),
            'fecha_entrada' => $fecha_entrada,
            'fecha_salida' => $fecha_salida,
            'tipo_filtro' => $tipo_filtro,
            'habitaciones_disponibles' => $habitaciones_disponibles,
            'tipos' => $this->catalogoTiposHabitacion()
        ]);
    }
    
    // ==================== MÉTODOS PRIVADOS ====================

    private function catalogoTiposHabitacion()
    {
        $tipos = function_exists('hotel_room_catalog_types')
            ? hotel_room_catalog_types($this->hotelIdActual())
            : [];

        return !empty($tipos) ? $tipos : Habitacion::getTipos();
    }

    private function catalogoPisosHabitacion()
    {
        $pisos = function_exists('hotel_room_catalog_floors')
            ? hotel_room_catalog_floors($this->hotelIdActual())
            : [];

        return !empty($pisos) ? $pisos : Habitacion::getPisos();
    }

    private function catalogoAmenidadesHabitacion()
    {
        $amenidades = function_exists('hotel_room_catalog_amenities')
            ? hotel_room_catalog_amenities($this->hotelIdActual())
            : [];

        return !empty($amenidades) ? $amenidades : [
            'pantalla' => 'Pantalla',
            'balcon' => 'Balcon',
            'jacuzzi' => 'Jacuzzi',
            'amplia' => 'Mas amplia',
        ];
    }

    private function validarHabitacionConCatalogos(array $data)
    {
        $errores = [];

        if (empty($data['numero'])) {
            $errores[] = 'El numero de habitacion es obligatorio';
        } elseif (strlen((string) $data['numero']) > 10) {
            $errores[] = 'El numero de habitacion no puede exceder 10 caracteres';
        }

        $tiposValidos = $this->catalogoTiposHabitacion();
        if (empty($data['tipo']) || !array_key_exists((string) $data['tipo'], $tiposValidos)) {
            $errores[] = 'Debe seleccionar un tipo de habitacion valido';
        }

        if (!is_numeric($data['precio_base']) || $data['precio_base'] <= 0) {
            $errores[] = 'El precio debe ser un numero mayor a cero';
        }

        $pisosValidos = $this->catalogoPisosHabitacion();
        $piso = (int) ($data['piso'] ?? 0);
        if (!is_numeric($data['piso'] ?? null) || !array_key_exists($piso, $pisosValidos)) {
            $errores[] = 'Debe seleccionar un piso valido del catalogo.';
        }

        return $errores;
    }

    private function hotelIdActual()
    {
        return obtenerHotelIdActualCompat();
    }
    
    /**
     * Procesar múltiples imágenes
     */
    private function procesarMultiplesImagenes($files, $habitacion_id, $numero_habitacion) {
        $imagenes_guardadas = [];
        $errores = [];
        $maxFiles = 10;
        
        // Verificar estructura de $_FILES para múltiples archivos
        if (!is_array($files['name'])) {
            return ['success' => false, 'error' => 'Formato de archivos incorrecto'];
        }
        
        $totalFiles = count($files['name']);
        
        if ($totalFiles > $maxFiles) {
            return ['success' => false, 'error' => "Solo se permiten máximo {$maxFiles} imágenes"];
        }
        
        // Procesar cada archivo
        for ($i = 0; $i < $totalFiles; $i++) {
            // Saltar si no hay archivo
            if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            // Verificar errores de upload
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errores[] = "Error al subir {$files['name'][$i]}";
                continue;
            }
            
            // Crear array temporal para procesarImagenHabitacion
            $archivo_temp = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            // Procesar imagen individual
            $resultado = $this->procesarImagenHabitacion($archivo_temp, $numero_habitacion);
            
            if ($resultado['success']) {
                // Guardar en la tabla habitacion_imagenes
                $imagen_data = [
                    'hotel_id' => $this->hotelIdActual(),
                    'habitacion_id' => $habitacion_id,
                    'url' => $resultado['path'],
                    'descripcion' => null,
                    'es_principal' => count($imagenes_guardadas) === 0 ? 1 : 0, // Primera imagen es principal
                    'orden' => count($imagenes_guardadas)
                ];
                
                $imagen_guardada = $this->habitacionImagenModel->create($imagen_data);
                
                if ($imagen_guardada) {
                    $imagenes_guardadas[] = [
                        'id' => is_array($imagen_guardada) ? $imagen_guardada['id'] : $imagen_guardada,
                        'url' => $resultado['path'],
                        'filename' => $resultado['filename']
                    ];
                }
            } else {
                $errores[] = $resultado['error'];
            }
        }
        
        if (!empty($errores) && empty($imagenes_guardadas)) {
            return ['success' => false, 'error' => implode(', ', $errores)];
        }
        
        return [
            'success' => true,
            'imagenes' => $imagenes_guardadas,
            'errores' => $errores
        ];
    }
    
    /**
     * Procesar imagen de habitación
     */
    private function procesarImagenHabitacion($archivo, $numeroHabitacion) {
        // Validaciones
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $minWidth = 200;
        $minHeight = 200;
        
        // Verificar tamaño
        if ($archivo['size'] > $maxSize) {
            return ['success' => false, 'error' => 'La imagen no puede exceder 5MB'];
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'error' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP'];
        }
        
        // Verificar dimensiones
        $imageInfo = getimagesize($archivo['tmp_name']);
        if (!$imageInfo || $imageInfo[0] < $minWidth || $imageInfo[1] < $minHeight) {
            return ['success' => false, 'error' => "La imagen debe tener al menos {$minWidth}x{$minHeight} píxeles"];
        }
        
        // Crear directorio si no existe
        $uploadDir = ensure_upload_directory('habitaciones');
        if (!$uploadDir) {
            return ['success' => false, 'error' => 'No se pudo crear el directorio de imágenes'];
        }
        
        // Generar nombre único
        $extension = get_extension_by_mime($mimeType);
        $nombreArchivo = generate_room_image_name($numeroHabitacion, $extension);
        $rutaCompleta = $uploadDir . '/' . $nombreArchivo;
        $rutaRelativa = 'uploads/habitaciones/' . $nombreArchivo;
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            // Optimizar imagen si es necesario (opcional)
            $this->optimizarImagen($rutaCompleta, $mimeType);
            
            return [
                'success' => true,
                'path' => $rutaRelativa,
                'filename' => $nombreArchivo
            ];
        } else {
            return ['success' => false, 'error' => 'Error al guardar la imagen'];
        }
    }
    
    /**
     * Optimizar imagen (redimensionar si es muy grande)
     */
    private function optimizarImagen($rutaArchivo, $mimeType) {
        $maxWidth = 1920;
        $maxHeight = 1080;
        $quality = 85;
        
        $imageInfo = getimagesize($rutaArchivo);
        if (!$imageInfo) return false;
        
        list($width, $height) = $imageInfo;
        
        // Si la imagen es pequeña, no hacer nada
        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);
        
        // Crear imagen desde el archivo
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($rutaArchivo);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($rutaArchivo);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($rutaArchivo);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($rutaArchivo);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) return false;
        
        // Crear nueva imagen redimensionada
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia para PNG y GIF
        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar imagen optimizada
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $rutaArchivo, $quality);
                break;
            case 'image/png':
                imagepng($resizedImage, $rutaArchivo, 6);
                break;
            case 'image/gif':
                imagegif($resizedImage, $rutaArchivo);
                break;
            case 'image/webp':
                imagewebp($resizedImage, $rutaArchivo, $quality);
                break;
        }
        
        // Limpiar memoria
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
        
        return true;
    }
    
    /**
     * Eliminar imagen de habitación
     */
    private function eliminarImagenHabitacion($rutaImagen) {
        if (empty($rutaImagen)) return true;
        
        $rutaCompleta = PUBLIC_PATH . '/' . ltrim($rutaImagen, '/');
        
        if (file_exists($rutaCompleta) && is_safe_image_path($rutaImagen)) {
            return unlink($rutaCompleta);
        }
        
        return true;
    }
    /**
 * Liberar múltiples habitaciones (marcar como disponibles)
 * Endpoint: POST /habitaciones/liberar-multiples
 */
/**
 * Liberar múltiples habitaciones (marcar como disponibles)
 * Endpoint: POST /habitaciones/liberar-multiples
 */
public function liberarMultiples() {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Establecer header JSON
    header('Content-Type: application/json; charset=utf-8');
    
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    // Verificar CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
    
    try {
        // Obtener IDs de habitaciones
        $habitaciones_ids = $_POST['habitaciones_ids'] ?? [];
        
        if (empty($habitaciones_ids) || !is_array($habitaciones_ids)) {
            throw new Exception('Debe seleccionar al menos una habitación');
        }
        
        // Validar que todos los IDs sean números
        $habitaciones_ids = array_map('intval', $habitaciones_ids);
        $habitaciones_ids = array_filter($habitaciones_ids, function($id) {
            return $id > 0;
        });
        
        if (empty($habitaciones_ids)) {
            throw new Exception('IDs de habitaciones inválidos');
        }
        
        $habitacion = new Habitacion();
        $actualizadas = 0;
        $numeros_habitaciones = [];
        
        // Procesar cada habitación
        foreach ($habitaciones_ids as $id) {
            // Obtener información de la habitación
            $hab = $habitacion->find($id);
            
            if (!$hab) {
                continue; // Saltar si no existe
            }
            
            // Solo actualizar si está en limpieza
            if ($hab['estado'] === 'limpieza') {
                $resultado = $habitacion->cambiarEstado($id, 'disponible');
                
                if ($resultado) {
                    $actualizadas++;
                    $numeros_habitaciones[] = $hab['numero'];
                }
            }
        }
        
        if ($actualizadas > 0) {
            echo json_encode([
                'success' => true,
                'message' => "Se marcaron {$actualizadas} habitación(es) como disponibles",
                'actualizadas' => $actualizadas,
                'habitaciones' => implode(', ', $numeros_habitaciones)
            ]);
        } else {
            throw new Exception('No se pudo actualizar ninguna habitación. Verifica que estén en estado de limpieza.');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}
    /**
     * Generar descripción automática de características
     */
    private function generarDescripcionCaracteristicas($tipo, $especiales = []) {
        $especiales = is_array($especiales) ? $especiales : [];

        // Informacion base por tipo conocido
        $camas_info = [
            'sencilla' => '1 cama matrimonial',
            'doble' => '2 camas matrimoniales',
            'triple' => '3 camas matrimoniales',
            'cuadruple' => '4 camas matrimoniales',
            'doble_jacuzzi' => '2 camas matrimoniales',
            'sencilla_jacuzzi' => '1 cama matrimonial'
        ];

        $baseTipo = $camas_info[$tipo] ?? '';

        if ($baseTipo === '' && function_exists('hotel_room_catalog_type_rows')) {
            foreach (hotel_room_catalog_type_rows($this->hotelIdActual(), true) as $row) {
                if (($row['codigo'] ?? '') !== $tipo) {
                    continue;
                }

                $baseTipo = trim((string) ($row['descripcion'] ?? ''));
                if ($baseTipo === '') {
                    $baseTipo = trim((string) ($row['nombre'] ?? ''));
                }
                break;
            }
        }

        $descripcion = [$baseTipo !== '' ? $baseTipo : 'Habitacion'];

        if (strpos((string) $tipo, 'jacuzzi') !== false && !in_array('jacuzzi', $especiales, true)) {
            $descripcion[] = 'jacuzzi';
        }

        $amenidades = $this->catalogoAmenidadesHabitacion();
        $orden_especiales = array_values(array_unique(array_merge(
            ['jacuzzi', 'pantalla', 'balcon', 'amplia'],
            array_keys($amenidades)
        )));

        foreach ($orden_especiales as $especial) {
            if (!in_array($especial, $especiales, true)) {
                continue;
            }

            if ($especial === 'jacuzzi' && strpos((string) $tipo, 'jacuzzi') !== false) {
                continue;
            }

            if (isset($amenidades[$especial])) {
                $descripcion[] = $amenidades[$especial];
            }
        }

        $base = ['bano', 'ventilador', 'agua caliente', 'Wifi', 'Cablevision', 'estacionamiento'];

        if (!in_array('pantalla', $especiales, true)) {
            array_unshift($base, 'TV normal');
        }

        $descripcion = array_merge($descripcion, $base);

        return implode(', ', $descripcion);
    }
    
    /**
     * Crear entrada en control de llaves
     */
    /**
 * Crear entrada en control de llaves
 */
private function crearControlLlave($habitacion_id) {
    $db = Database::getInstance();
    $sql = "INSERT INTO control_llaves (habitacion_id, estado, tiene_llave, created_at) 
            VALUES (?, 'disponible', 1, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()";
    return $db->query($sql, [$habitacion_id]);
}
    
    /**
     * Obtener mantenimiento actual
     */
    private function obtenerMantenimientoActual($habitacion_id) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM mantenimientos_habitaciones 
                WHERE habitacion_id = ? AND hotel_id = ? AND estado = 'en_proceso'
                ORDER BY fecha_inicio DESC LIMIT 1";
        $stmt = $db->query($sql, [$habitacion_id, $this->hotelIdActual()]);
        return $stmt->fetch();
    }
    
    /**
     * Cambiar estado de habitación
     */
    public function cambiarEstadoAction() {
        if (!$this->isPost()) {
            json_response(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        
        $this->validateCSRF();
        
        $id = $this->route_params['id'] ?? 0;
        $nuevo_estado = $this->getPost('estado');
        
        $habitacion = $this->habitacionModel->find($id);
        if (!$habitacion) {
            json_response(['success' => false, 'message' => 'Habitación no encontrada']);
        }
        
        // Validar transición de estado
        if (!puede_cambiar_estado_habitacion($habitacion['estado'], $nuevo_estado)) {
            json_response(['success' => false, 'message' => 'Cambio de estado no permitido']);
        }
        
        if ($this->habitacionModel->cambiarEstado($id, $nuevo_estado)) {
            json_response(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            json_response(['success' => false, 'message' => 'Error al actualizar estado']);
        }
    }
}
