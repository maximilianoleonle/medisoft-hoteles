<?php
/**
 * Controlador API para llamadas AJAX - Actualizado para reservaciones múltiples
 * Los Cedros
 */

class ApiController extends Controller {
    
    /**
     * Verificar que sea una petición AJAX
     */
    protected function before() {
        if (!$this->isAjax()) {
            View::renderJSON([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ], 403);
            return false;
        }
        
        // Verificar autenticación
        if (!is_authenticated()) {
            View::renderJSON([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
            return false;
        }
        
        return true;
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }
    
    
public function validarImagenAction() {
    if (!$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido'], 405);
        return;
    }
    
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        View::renderJSON(['success' => false, 'message' => 'No se recibió ninguna imagen']);
        return;
    }
    
    $archivo = $_FILES['imagen'];
    
    // Configuración de validación
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $minWidth = 200;
    $minHeight = 200;
    
    // Validar tamaño
    if ($archivo['size'] > $maxSize) {
        View::renderJSON([
            'success' => false, 
            'message' => 'La imagen no puede exceder 5MB',
            'details' => [
                'size' => $archivo['size'],
                'maxSize' => $maxSize,
                'sizeFormatted' => format_file_size($archivo['size'])
            ]
        ]);
        return;
    }
    
    // Validar tipo MIME real
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        View::renderJSON([
            'success' => false, 
            'message' => 'Solo se permiten imágenes JPG, PNG, GIF o WebP',
            'details' => ['mimeType' => $mimeType]
        ]);
        return;
    }
    
    // Validar dimensiones
    $imageInfo = getimagesize($archivo['tmp_name']);
    if (!$imageInfo) {
        View::renderJSON(['success' => false, 'message' => 'El archivo no es una imagen válida']);
        return;
    }
    
    if ($imageInfo[0] < $minWidth || $imageInfo[1] < $minHeight) {
        View::renderJSON([
            'success' => false, 
            'message' => "La imagen debe tener al menos {$minWidth}x{$minHeight} píxeles",
            'details' => [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'minWidth' => $minWidth,
                'minHeight' => $minHeight
            ]
        ]);
        return;
    }
    
    // Si llegamos aquí, la imagen es válida
    View::renderJSON([
        'success' => true,
        'message' => 'Imagen válida',
        'details' => [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'size' => $archivo['size'],
            'sizeFormatted' => format_file_size($archivo['size']),
            'mimeType' => $mimeType,
            'extension' => get_extension_by_mime($mimeType)
        ]
    ]);
}

public function todasConOcupacionAction() {
    try {
        $fecha_entrada = $this->getQuery('fecha_entrada');
        $fecha_salida = $this->getQuery('fecha_salida');
        
        if (!$fecha_entrada) {
            $fecha_entrada = date('Y-m-d');
        }
        if (!$fecha_salida) {
            $fecha_salida = date('Y-m-d', strtotime($fecha_entrada . ' +1 day'));
        }
        
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        $sql = "SELECT h.* 
                FROM habitaciones h 
                WHERE h.hotel_id = ?
                AND h.activa = 1
                ORDER BY h.piso, CAST(h.numero AS UNSIGNED)";
        
        $stmt = $db->query($sql, [$hotel_id]);
        $todas_habitaciones = $stmt->fetchAll();
        
        // CONSULTA CORREGIDA: Obtener todas las reservaciones que se traslapen
        $sql_ocupacion = "SELECT DISTINCT 
                          rh.habitacion_id,
                          r.id as reservacion_id,
                          r.estado,
                          r.fecha_entrada,
                          r.fecha_salida,
                          r.hora_entrada,
                          h.nombre_completo as huesped_nombre,
                          h.telefono as huesped_telefono,
                          h.procedencia_estado
                          FROM reservacion_habitaciones rh
                          INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                            AND rh.hotel_id = r.hotel_id
                          INNER JOIN habitaciones hab_scope ON rh.habitacion_id = hab_scope.id
                            AND hab_scope.hotel_id = rh.hotel_id
                          INNER JOIN huespedes h ON r.huesped_id = h.id
                          WHERE r.hotel_id = ?
                          AND rh.hotel_id = ?
                          AND hab_scope.hotel_id = ?
                          AND r.estado IN ('confirmada', 'checked_in')
                          AND NOT (
                              r.fecha_salida <= ? OR r.fecha_entrada >= ?
                          )";
        
        $params = [$hotel_id, $hotel_id, $hotel_id, $fecha_entrada, $fecha_salida];
        
        $stmt = $db->query($sql_ocupacion, $params);
        $ocupaciones = $stmt->fetchAll();
        
        // Crear un mapa de ocupaciones por habitación_id
        $ocupacion_map = [];
        foreach ($ocupaciones as $ocupacion) {
            $ocupacion_map[$ocupacion['habitacion_id']] = $ocupacion;
        }
        
        // Cargar modelo de tarifas para precios dinámicos
        $tarifaModel = new IncrementoTarifa();
        
        // Calcular las noches del rango solicitado
        $fecha_inicio = new DateTime($fecha_entrada);
        $fecha_fin = new DateTime($fecha_salida);
        $noches = $fecha_inicio->diff($fecha_fin)->days;
        if ($noches == 0) $noches = 1;
        
        // Combinar información
        $habitaciones_con_info = [];
        
        foreach ($todas_habitaciones as $habitacion) {
            $hab_id = $habitacion['id'];
            
            // Calcular precio dinámico
            $precio_total = 0;
            $fecha_actual = clone $fecha_inicio;
            
            for ($i = 0; $i < $noches; $i++) {
                $fecha_str = $fecha_actual->format('Y-m-d');
                
                $precio_info = $tarifaModel->calcularPrecioConIncremento(
                    $habitacion['id'],
                    $habitacion['tipo'],
                    $habitacion['precio_base'],
                    $fecha_str
                );
                
                $precio_total += $precio_info['precio_final'];
                $fecha_actual->modify('+1 day');
            }
            
            $precio_promedio = $precio_total / $noches;
            
            // Detectar qué noches específicas están ocupadas
            if (isset($ocupacion_map[$hab_id])) {
                $ocupacion = $ocupacion_map[$hab_id];
                
                // Determinar qué noches del rango solicitado se traslapan
                $reserva_entrada = new DateTime($ocupacion['fecha_entrada']);
                $reserva_salida = new DateTime($ocupacion['fecha_salida']);
                
                $noches_ocupadas = [];
                $fechas_ocupadas = [];
                $fecha_actual = clone $fecha_inicio;
                
                for ($i = 0; $i < $noches; $i++) {
                    $noche_num = $i + 1;
                    $noche_fecha = $fecha_actual->format('Y-m-d');
                    
                    // Una noche está ocupada si está dentro del rango de la reserva existente
                    if ($fecha_actual >= $reserva_entrada && $fecha_actual < $reserva_salida) {
                        $noches_ocupadas[] = $noche_num;
                        $fechas_ocupadas[] = [
                            'noche' => $noche_num,
                            'fecha' => $noche_fecha,
                            'fecha_formateada' => $fecha_actual->format('d/m')
                        ];
                    }
                    
                    $fecha_actual->modify('+1 day');
                }
                
                $habitacion['ocupada'] = true;
                $habitacion['info_ocupacion'] = [
                    'reservacion_id' => $ocupacion['reservacion_id'],
                    'estado' => $ocupacion['estado'],
                    'huesped_nombre' => $ocupacion['huesped_nombre'],
                    'huesped_telefono' => $ocupacion['huesped_telefono'],
                    'procedencia' => $ocupacion['procedencia_estado'],
                    'fecha_entrada' => $ocupacion['fecha_entrada'],
                    'fecha_salida' => $ocupacion['fecha_salida'],
                    'hora_entrada' => $ocupacion['hora_entrada'],
                    'noches_ocupadas' => $noches_ocupadas,
                    'fechas_ocupadas' => $fechas_ocupadas,
                    'total_noches_solicitadas' => $noches
                ];
            } else {
                $habitacion['ocupada'] = false;
                $habitacion['info_ocupacion'] = null;
            }
            
            $habitacion['precio_base_original'] = $habitacion['precio_base'];
            $habitacion['precio_base'] = $precio_promedio;
            $habitacion['precio_periodo'] = $precio_total;
            
            $habitaciones_con_info[] = $habitacion;
        }
        
        usort($habitaciones_con_info, function($a, $b) {
            return strnatcmp($a['numero'], $b['numero']);
        });
        
        View::renderJSON([
            'success' => true,
            'data' => $habitaciones_con_info,
            'total' => count($habitaciones_con_info),
            'fecha_entrada' => $fecha_entrada,
            'fecha_salida' => $fecha_salida,
            'ocupadas' => count(array_filter($habitaciones_con_info, function($h) { return $h['ocupada']; })),
            'disponibles' => count(array_filter($habitaciones_con_info, function($h) { return !$h['ocupada']; }))
        ]);
        
    } catch (Exception $e) {
        error_log("Error en todasConOcupacionAction: " . $e->getMessage());
        
        View::renderJSON([
            'success' => false,
            'message' => 'Error al obtener habitaciones: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ]);
    }
}
public function informacionImagenAction() {
    $habitacion_id = $this->route_params['id'] ?? 0;
    
    if (!$habitacion_id) {
        View::renderJSON(['success' => false, 'message' => 'ID de habitación no válido']);
        return;
    }
    
    $habitacionModel = new Habitacion();
    $habitacion = $habitacionModel->find($habitacion_id);
    
    if (!$habitacion) {
        View::renderJSON(['success' => false, 'message' => 'Habitación no encontrada']);
        return;
    }
    
    if (empty($habitacion['foto_url'])) {
        View::renderJSON([
            'success' => true,
            'hasImage' => false,
            'message' => 'Esta habitación no tiene imagen'
        ]);
        return;
    }
    
    // Obtener información de la imagen
    $imageInfo = get_image_info($habitacion['foto_url']);
    
    if (!$imageInfo) {
        View::renderJSON([
            'success' => false,
            'message' => 'No se pudo obtener información de la imagen'
        ]);
        return;
    }
    
    View::renderJSON([
        'success' => true,
        'hasImage' => true,
        'image' => [
            'url' => $imageInfo['url'],
            'path' => $habitacion['foto_url'],
            'width' => $imageInfo['width'],
            'height' => $imageInfo['height'],
            'size' => $imageInfo['size'],
            'sizeFormatted' => format_file_size($imageInfo['size']),
            'mimeType' => $imageInfo['mime'],
            'aspectRatio' => round($imageInfo['width'] / $imageInfo['height'], 2)
        ],
        'habitacion' => [
            'id' => $habitacion['id'],
            'numero' => $habitacion['numero'],
            'tipo' => $habitacion['tipo']
        ]
    ]);
}

/**
 * Obtener estadísticas de imágenes del hotel
 */
public function estadisticasImagenesAction() {
    $db = Database::getInstance();
    
    // Contar habitaciones con y sin imagen
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN foto_url IS NOT NULL AND foto_url != '' THEN 1 ELSE 0 END) as con_imagen,
                SUM(CASE WHEN foto_url IS NULL OR foto_url = '' THEN 1 ELSE 0 END) as sin_imagen
            FROM habitaciones 
            WHERE activa = 1";
    
    $stmt = $db->query($sql);
    $stats = $stmt->fetch();
    
    // Obtener tamaños de archivos
    $imageSizes = [];
    $totalSize = 0;
    
    $sql = "SELECT foto_url FROM habitaciones WHERE foto_url IS NOT NULL AND foto_url != '' AND activa = 1";
    $stmt = $db->query($sql);
    $imagenes = $stmt->fetchAll();
    
    foreach ($imagenes as $imagen) {
        $imageInfo = get_image_info($imagen['foto_url']);
        if ($imageInfo) {
            $imageSizes[] = $imageInfo['size'];
            $totalSize += $imageInfo['size'];
        }
    }
    
    $averageSize = count($imageSizes) > 0 ? $totalSize / count($imageSizes) : 0;
    
    View::renderJSON([
        'success' => true,
        'estadisticas' => [
            'total_habitaciones' => $stats['total'],
            'con_imagen' => $stats['con_imagen'],
            'sin_imagen' => $stats['sin_imagen'],
            'porcentaje_con_imagen' => $stats['total'] > 0 ? round(($stats['con_imagen'] / $stats['total']) * 100, 1) : 0,
            'total_size' => $totalSize,
            'total_size_formatted' => format_file_size($totalSize),
            'average_size' => $averageSize,
            'average_size_formatted' => format_file_size($averageSize),
            'total_images' => count($imageSizes)
        ]
    ]);
}

/**
 * Limpiar imágenes huérfanas (que no están referenciadas en BD)
 */
 public function getProductosDescuentoReservacion($id)
{
    header('Content-Type: application/json');
    
    try {
        // Obtener habitaciones de la reservación
        $sql = "SELECT rh.habitacion_id 
                FROM reservacion_habitaciones rh
                WHERE rh.reservacion_id = :reservacion_id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reservacion_id' => $id]);
        $habitaciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($habitaciones)) {
            echo json_encode(['success' => false, 'message' => 'No se encontraron habitaciones']);
            return;
        }
        
        require_once __DIR__ . '/../services/ConfiguracionHabitacionService.php';
        $configService = new \App\Services\ConfiguracionHabitacionService();
        
        $todosProductos = [];
        
        // Obtener productos para cada habitación
        foreach ($habitaciones as $habitacionId) {
            $productos = $configService->getProductosDescuentoHabitacion($habitacionId);
            
            // Agrupar productos iguales sumando cantidades
            foreach ($productos as $producto) {
                $key = $producto['producto'];
                if (isset($todosProductos[$key])) {
                    $todosProductos[$key]['cantidad_checkin'] += $producto['cantidad_checkin'];
                } else {
                    $todosProductos[$key] = $producto;
                }
            }
        }
        
        // Recalcular disponibilidad con las cantidades totales
        foreach ($todosProductos as &$producto) {
            $producto['disponible'] = $producto['stock_actual'] >= $producto['cantidad_checkin'];
        }
        
        echo json_encode([
            'success' => true,
            'productos' => array_values($todosProductos),
            'total_habitaciones' => count($habitaciones)
        ]);
        
    } catch (\Exception $e) {
        error_log("Error obteniendo productos descuento: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al obtener productos']);
    }
}
 
public function limpiarImagenesHuerfanasAction() {
    if (!$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido'], 405);
        return;
    }
    
    $this->requirePermission('habitaciones.administrar');
    
    $uploadDir = PUBLIC_PATH . '/uploads/habitaciones';
    
    if (!is_dir($uploadDir)) {
        View::renderJSON(['success' => false, 'message' => 'Directorio de imágenes no encontrado']);
        return;
    }
    
    // Obtener todas las imágenes referenciadas en la BD
    $db = Database::getInstance();
    $sql = "SELECT foto_url FROM habitaciones WHERE foto_url IS NOT NULL AND foto_url != ''";
    $stmt = $db->query($sql);
    $imagenesEnBD = [];
    
    while ($row = $stmt->fetch()) {
        $filename = basename($row['foto_url']);
        $imagenesEnBD[] = $filename;
    }
    
    // Escanear directorio de imágenes
    $archivosEncontrados = [];
    $archivosEliminados = [];
    $errorArchivos = [];
    
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || $file === '.htaccess') {
            continue;
        }
        
        $filePath = $uploadDir . '/' . $file;
        
        if (is_file($filePath)) {
            $archivosEncontrados[] = $file;
            
            // Si no está referenciado en BD, es huérfano
            if (!in_array($file, $imagenesEnBD)) {
                if (unlink($filePath)) {
                    $archivosEliminados[] = $file;
                } else {
                    $errorArchivos[] = $file;
                }
            }
        }
    }
    
    View::renderJSON([
        'success' => true,
        'message' => 'Limpieza completada',
        'resultado' => [
            'archivos_encontrados' => count($archivosEncontrados),
            'archivos_eliminados' => count($archivosEliminados),
            'archivos_con_error' => count($errorArchivos),
            'imagenes_en_bd' => count($imagenesEnBD),
            'eliminados' => $archivosEliminados,
            'errores' => $errorArchivos
        ]
    ]);
}

/**
 * Optimizar todas las imágenes de habitaciones
 */
public function optimizarImagenesAction() {
    if (!$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido'], 405);
        return;
    }
    
    $this->requirePermission('habitaciones.administrar');
    
    $db = Database::getInstance();
    $sql = "SELECT id, numero, foto_url FROM habitaciones WHERE foto_url IS NOT NULL AND foto_url != '' AND activa = 1";
    $stmt = $db->query($sql);
    $habitaciones = $stmt->fetchAll();
    
    $optimizadas = [];
    $errores = [];
    $totalSizeAntes = 0;
    $totalSizeDespues = 0;
    
    foreach ($habitaciones as $habitacion) {
        $imagePath = PUBLIC_PATH . '/' . ltrim($habitacion['foto_url'], '/');
        
        if (!file_exists($imagePath)) {
            $errores[] = "Imagen no encontrada para habitación {$habitacion['numero']}";
            continue;
        }
        
        $sizeAntes = filesize($imagePath);
        $totalSizeAntes += $sizeAntes;
        
        if (optimize_image($imagePath, 1920, 1080, 85)) {
            $sizeDespues = filesize($imagePath);
            $totalSizeDespues += $sizeDespues;
            
            $optimizadas[] = [
                'habitacion' => $habitacion['numero'],
                'size_antes' => $sizeAntes,
                'size_despues' => $sizeDespues,
                'ahorro' => $sizeAntes - $sizeDespues,
                'ahorro_porcentaje' => $sizeAntes > 0 ? round((($sizeAntes - $sizeDespues) / $sizeAntes) * 100, 1) : 0
            ];
        } else {
            $errores[] = "Error optimizando imagen de habitación {$habitacion['numero']}";
            $totalSizeDespues += $sizeAntes; // Sin cambio
        }
    }
    
    View::renderJSON([
        'success' => true,
        'message' => 'Optimización completada',
        'resultado' => [
            'imagenes_procesadas' => count($optimizadas),
            'errores' => count($errores),
            'size_antes_total' => $totalSizeAntes,
            'size_despues_total' => $totalSizeDespues,
            'ahorro_total' => $totalSizeAntes - $totalSizeDespues,
            'ahorro_porcentaje' => $totalSizeAntes > 0 ? round((($totalSizeAntes - $totalSizeDespues) / $totalSizeAntes) * 100, 1) : 0,
            'size_antes_formatted' => format_file_size($totalSizeAntes),
            'size_despues_formatted' => format_file_size($totalSizeDespues),
            'ahorro_formatted' => format_file_size($totalSizeAntes - $totalSizeDespues),
            'detalles' => $optimizadas,
            'errores_detalle' => $errores
        ]
    ]);
}

    /**
     * Obtener habitaciones disponibles
     */
     
     /**
 * Obtener vehículos de un huésped (AJAX)
 */
public function vehiculosHuespedAction() {
    $huesped_id = $this->route_params['id'] ?? 0;
    
    if (!$huesped_id) {
        View::renderJSON([
            'success' => false,
            'message' => 'ID de huésped no válido'
        ]);
        return;
    }
    
    $vehiculoModel = new HuespedVehiculo();
    $vehiculos = $vehiculoModel->porHuesped($huesped_id);
    
    View::renderJSON([
        'success' => true,
        'vehiculos' => $vehiculos
    ]);
}
    public function disponiblesAction() {
    // Redirigir al método correcto
    $this->habitacionesDisponiblesAction();
}
    public function habitacionesDisponiblesAction() {
    try {
        $fecha_entrada = $this->getQuery('fecha_entrada');
        $fecha_salida = $this->getQuery('fecha_salida');
        $tipo = $this->getQuery('tipo');
        $excluir_reservacion = $this->getQuery('excluir_reservacion');
        
        if (!$fecha_entrada || !$fecha_salida) {
            View::renderJSON([
                'success' => false,
                'message' => 'Fechas de entrada y salida son requeridas'
            ]);
            return;
        }
        
        // Validar que la fecha de salida sea posterior a la de entrada
        if (strtotime($fecha_salida) <= strtotime($fecha_entrada)) {
            View::renderJSON([
                'success' => false,
                'message' => 'La fecha de salida debe ser posterior a la fecha de entrada'
            ]);
            return;
        }
        
        // Obtener habitaciones disponibles
        $habitacionModel = new Habitacion();
        $habitaciones = $habitacionModel->disponiblesEntreFechas(
            $fecha_entrada, 
            $fecha_salida, 
            $excluir_reservacion
        );
        
        // Si se especifica un tipo, filtrar por tipo
        if ($tipo) {
            $habitaciones = array_filter($habitaciones, function($hab) use ($tipo) {
                return $hab['tipo'] == $tipo;
            });
        }
        
        // Cargar el modelo de tarifas dinámicas
        $tarifaModel = new IncrementoTarifa();
        
        // Actualizar precios según tarifas dinámicas para cada habitación
        foreach ($habitaciones as &$habitacion) {
            // Calcular el precio promedio para el período
            $fecha_inicio = new DateTime($fecha_entrada);
            $fecha_fin = new DateTime($fecha_salida);
            $noches = $fecha_inicio->diff($fecha_fin)->days;
            
            if ($noches == 0) $noches = 1;
            
            $precio_total = 0;
            $fecha_actual = clone $fecha_inicio;
            
            // Calcular precio para cada noche con las tarifas dinámicas
            for ($i = 0; $i < $noches; $i++) {
                $fecha_str = $fecha_actual->format('Y-m-d');
                
                $precio_info = $tarifaModel->calcularPrecioConIncremento(
                    $habitacion['id'],
                    $habitacion['tipo'],
                    $habitacion['precio_base'],
                    $fecha_str
                );
                
                $precio_total += $precio_info['precio_final'];
                $fecha_actual->modify('+1 day');
            }
            
            // Agregar información de precio al array de habitación
            $habitacion['precio_periodo'] = $precio_total;
            $habitacion['precio_promedio_noche'] = $precio_total / $noches;
            
            // Mantener el precio base para referencia
            $habitacion['precio_base_original'] = $habitacion['precio_base'];
            
            // Actualizar el precio base mostrado con el precio promedio
            $habitacion['precio_base'] = $habitacion['precio_promedio_noche'];
        }
        
        // Reindexar el array
        $habitaciones = array_values($habitaciones);
        
        // Ordenar por número de habitación
        usort($habitaciones, function($a, $b) {
            return strnatcmp($a['numero'], $b['numero']);
        });
        
        View::renderJSON([
            'success' => true,
            'data' => $habitaciones,
            'total' => count($habitaciones),
            'fecha_entrada' => $fecha_entrada,
            'fecha_salida' => $fecha_salida,
            'message' => count($habitaciones) > 0 ? 
                'Habitaciones disponibles encontradas' : 
                'No hay habitaciones disponibles para las fechas seleccionadas'
        ]);
        
    } catch (Exception $e) {
        error_log("Error en habitacionesDisponiblesAction: " . $e->getMessage());
        
        View::renderJSON([
            'success' => false,
            'message' => 'Error al obtener habitaciones disponibles: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ]);
    }
}
    /**
     * Buscar huéspedes
     */
    public function buscarGlobalAction() {
        $q = trim($this->getQuery('q', ''));

        if ($q === '__offline_cache__') {
            try {
                $db = Database::getInstance();
                $hotel_id = $this->hotelIdActual();

                $stmt = $db->query(
                    "SELECT id, nombre_completo, telefono, procedencia_estado
                     FROM huespedes h
                     WHERE EXISTS (
                         SELECT 1
                         FROM reservaciones r
                         WHERE r.huesped_id = h.id
                           AND r.hotel_id = ?
                     )
                     ORDER BY nombre_completo ASC
                     LIMIT 1000",
                    [$hotel_id]
                );
                $huespedes = $stmt ? $stmt->fetchAll() : [];

                $stmt = $db->query(
                    "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida,
                            r.hora_llegada_estimada, r.hora_entrada, r.hora_salida,
                            r.precio_total, r.metodo_pago, r.notas, r.total_habitaciones,
                            h.id AS huesped_id,
                            h.nombre_completo AS huesped_nombre,
                            h.telefono AS huesped_telefono,
                            h.procedencia_estado,
                            GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones,
                            GROUP_CONCAT(DISTINCT hab.id SEPARATOR ',') AS habitaciones_ids,
                            GROUP_CONCAT(DISTINCT hab.tipo SEPARATOR '||') AS habitaciones_tipos
                     FROM reservaciones r
                     INNER JOIN huespedes h ON r.huesped_id = h.id
                     LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                        AND rh.hotel_id = r.hotel_id
                     LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
                        AND hab.hotel_id = rh.hotel_id
                     WHERE r.hotel_id = ?
                     GROUP BY r.id
                     ORDER BY r.updated_at DESC, r.id DESC
                     LIMIT 3000",
                    [$hotel_id]
                );
                $reservaciones = $stmt ? $stmt->fetchAll() : [];

                $stmt = $db->query(
                    "SELECT id, numero, tipo, estado, precio_base
                     FROM habitaciones
                     WHERE hotel_id = ?
                       AND activa = 1
                     ORDER BY numero ASC",
                    [$hotel_id]
                );
                $habitaciones = $stmt ? $stmt->fetchAll() : [];

                $resultados = [];

                foreach ($huespedes as $h) {
                    $resultados[] = [
                        'tipo' => 'huesped',
                        'icono' => 'fa-user',
                        'color' => '#5C7A4E',
                        'titulo' => $h['nombre_completo'],
                        'subtitulo' => $h['telefono'] ? 'Tel. ' . $h['telefono'] : ($h['procedencia_estado'] ?? ''),
                        'url' => 'huespedes/' . $h['id'],
                    ];
                }

                $estados_label = [
                    'confirmada' => 'Confirmada',
                    'checked_in' => 'Check-in',
                    'completada' => 'Check-out',
                    'cancelada' => 'Cancelada',
                ];

                foreach ($reservaciones as $r) {
                    $fecha = !empty($r['fecha_entrada']) ? date('d/m/Y', strtotime($r['fecha_entrada'])) : '-';
                    $resultados[] = [
                        'tipo' => 'reservacion',
                        'icono' => 'fa-calendar-check',
                        'color' => '#2563EB',
                        'titulo' => '#' . $r['id'] . ' - ' . $r['huesped_nombre'],
                        'subtitulo' => ($r['habitaciones'] ?? '-') . ' | ' . $fecha . ' | ' . ($estados_label[$r['estado']] ?? $r['estado']),
                        'url' => 'reservaciones/ver/' . $r['id'],
                    ];
                }

                $estado_es = [
                    'disponible' => 'Disponible',
                    'ocupada' => 'Ocupada',
                    'mantenimiento' => 'Mantenimiento',
                    'limpieza' => 'Limpieza',
                ];

                foreach ($habitaciones as $hab) {
                    $resultados[] = [
                        'tipo' => 'habitacion',
                        'icono' => 'fa-bed',
                        'color' => '#7C3AED',
                        'titulo' => 'Habitacion ' . $hab['numero'],
                        'subtitulo' => ucfirst(str_replace('_', ' ', $hab['tipo'])) . ' | ' . ($estado_es[$hab['estado']] ?? $hab['estado']),
                        'url' => 'habitaciones',
                    ];
                }

                View::renderJSON([
                    'success' => true,
                    'query' => $q,
                    'resultados' => $resultados,
                    'huespedes' => $huespedes,
                    'reservaciones' => array_map(function (array $r): array {
                        return [
                            'id' => (int) $r['id'],
                            'estado' => $r['estado'],
                            'fecha_entrada' => $r['fecha_entrada'],
                            'fecha_salida' => $r['fecha_salida'],
                            'hora_llegada_estimada' => $r['hora_llegada_estimada'],
                            'hora_entrada' => $r['hora_entrada'],
                            'hora_salida' => $r['hora_salida'],
                            'precio_total' => (float) $r['precio_total'],
                            'metodo_pago' => $r['metodo_pago'],
                            'notas' => $r['notas'],
                            'total_habitaciones' => (int) ($r['total_habitaciones'] ?: 1),
                            'huesped_id' => (int) $r['huesped_id'],
                            'huesped_nombre' => $r['huesped_nombre'],
                            'huesped_telefono' => $r['huesped_telefono'],
                            'procedencia_estado' => $r['procedencia_estado'],
                            'habitaciones_numeros' => $r['habitaciones'],
                            'habitaciones_ids' => $r['habitaciones_ids'],
                            'habitaciones_tipos' => $r['habitaciones_tipos'],
                        ];
                    }, $reservaciones),
                    'habitaciones' => $habitaciones,
                    'total' => count($resultados),
                ]);
            } catch (Throwable $e) {
                error_log('[API buscarGlobal offline cache] ' . $e->getMessage());
                View::renderJSON(['success' => false, 'message' => 'Error interno'], 500);
            }
            return;
        }

        $q_id_rapido = preg_replace('/\D+/', '', $q);
        if (strlen($q) < 2 && $q_id_rapido === '') {
            View::renderJSON(['success' => true, 'resultados' => []]);
            return;
        }

        try {
            $db = Database::getInstance();
            $hotel_id = $this->hotelIdActual();
            $buscar = '%' . $q . '%';
            $q_id = preg_replace('/\D+/', '', $q);
            $buscar_id = $q_id !== '' ? '%' . $q_id . '%' : $buscar;
            $id_exacto = $q_id !== '' ? $q_id : '__sin_id__';
            $limite = 5;

            $stmt = $db->query(
                "SELECT id, nombre_completo, telefono, procedencia_estado
                 FROM huespedes h
                 WHERE (nombre_completo LIKE ? OR telefono LIKE ?)
                   AND EXISTS (
                       SELECT 1
                       FROM reservaciones r
                       WHERE r.huesped_id = h.id
                         AND r.hotel_id = ?
                   )
                 ORDER BY nombre_completo ASC
                 LIMIT ?",
                [$buscar, $buscar, $hotel_id, $limite]
            );
            $huespedes = $stmt ? $stmt->fetchAll() : [];

            $stmt = $db->query(
                "SELECT r.id, r.estado, r.fecha_entrada, r.fecha_salida, r.precio_total,
                        h.nombre_completo AS huesped_nombre,
                        GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones
                 FROM reservaciones r
                 INNER JOIN huespedes h ON r.huesped_id = h.id
                 LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                    AND rh.hotel_id = r.hotel_id
                 LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
                    AND hab.hotel_id = rh.hotel_id
                 WHERE r.hotel_id = ?
                   AND (
                    CAST(r.id AS CHAR) LIKE ?
                    OR h.nombre_completo LIKE ?
                    OR hab.numero LIKE ?
                   )
                 GROUP BY r.id
                 ORDER BY
                    CASE
                        WHEN CAST(r.id AS CHAR) = ? THEN 0
                        WHEN CAST(r.id AS CHAR) LIKE ? THEN 1
                        ELSE 2
                    END,
                    r.fecha_entrada DESC
                 LIMIT ?",
                [$hotel_id, $buscar_id, $buscar, $buscar, $id_exacto, $buscar_id, $limite]
            );
            $reservaciones = $stmt ? $stmt->fetchAll() : [];

            $stmt = $db->query(
                "SELECT id, numero, tipo, estado, precio_base
                 FROM habitaciones
                 WHERE hotel_id = ?
                   AND (numero LIKE ? OR tipo LIKE ?)
                   AND activa = 1
                 ORDER BY numero ASC
                 LIMIT ?",
                [$hotel_id, $buscar, $buscar, $limite]
            );
            $habitaciones = $stmt ? $stmt->fetchAll() : [];

            $resultados = [];

            foreach ($huespedes as $h) {
                $resultados[] = [
                    'tipo' => 'huesped',
                    'icono' => 'fa-user',
                    'color' => '#5C7A4E',
                    'titulo' => $h['nombre_completo'],
                    'subtitulo' => $h['telefono'] ? 'Tel. ' . $h['telefono'] : ($h['procedencia_estado'] ?? ''),
                    'url' => 'huespedes/' . $h['id'],
                ];
            }

            $estados_label = [
                'confirmada' => 'Confirmada',
                'checked_in' => 'Check-in',
                'completada' => 'Check-out',
                'cancelada' => 'Cancelada',
            ];

            foreach ($reservaciones as $r) {
                $fecha = !empty($r['fecha_entrada']) ? date('d/m/Y', strtotime($r['fecha_entrada'])) : '-';
                $resultados[] = [
                    'tipo' => 'reservacion',
                    'icono' => 'fa-calendar-check',
                    'color' => '#2563EB',
                    'titulo' => '#' . $r['id'] . ' - ' . $r['huesped_nombre'],
                    'subtitulo' => ($r['habitaciones'] ?? '-') . ' | ' . $fecha . ' | ' . ($estados_label[$r['estado']] ?? $r['estado']),
                    'url' => 'reservaciones/ver/' . $r['id'],
                ];
            }

            $estado_es = [
                'disponible' => 'Disponible',
                'ocupada' => 'Ocupada',
                'mantenimiento' => 'Mantenimiento',
                'limpieza' => 'Limpieza',
            ];

            foreach ($habitaciones as $hab) {
                $resultados[] = [
                    'tipo' => 'habitacion',
                    'icono' => 'fa-bed',
                    'color' => '#7C3AED',
                    'titulo' => 'Habitacion ' . $hab['numero'],
                    'subtitulo' => ucfirst(str_replace('_', ' ', $hab['tipo'])) . ' | ' . ($estado_es[$hab['estado']] ?? $hab['estado']),
                    'url' => 'habitaciones',
                ];
            }

            View::renderJSON([
                'success' => true,
                'query' => $q,
                'resultados' => $resultados,
                'total' => count($resultados),
            ]);
        } catch (Throwable $e) {
            error_log('[API buscarGlobal] ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    public function reservacionesHoyAction() {
        try {
            $db = Database::getInstance();
            $hotel_id = $this->hotelIdActual();
            $hoy = date('Y-m-d');

            $sql = "SELECT
                        r.id,
                        r.estado,
                        r.fecha_entrada,
                        r.fecha_salida,
                        r.hora_llegada_estimada,
                        r.hora_entrada,
                        r.hora_salida,
                        r.precio_total,
                        r.metodo_pago,
                        r.notas,
                        r.total_habitaciones,
                        h.id AS huesped_id,
                        h.nombre_completo AS huesped_nombre,
                        h.telefono AS huesped_telefono,
                        h.procedencia_estado,
                        GROUP_CONCAT(DISTINCT hab.numero ORDER BY CAST(hab.numero AS UNSIGNED), hab.numero SEPARATOR ', ') AS habitaciones_numeros,
                        GROUP_CONCAT(DISTINCT hab.id SEPARATOR ',') AS habitaciones_ids,
                        GROUP_CONCAT(DISTINCT hab.tipo SEPARATOR '||') AS habitaciones_tipos
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                        AND rh.hotel_id = r.hotel_id
                    LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
                        AND hab.hotel_id = rh.hotel_id
                    WHERE r.hotel_id = ?
                      AND r.estado IN ('confirmada', 'checked_in')
                      AND r.fecha_entrada <= DATE_ADD(?, INTERVAL 1 DAY)
                      AND r.fecha_salida >= ?
                    GROUP BY r.id
                    ORDER BY r.fecha_entrada ASC, r.hora_llegada_estimada ASC";

            $stmt = $db->query($sql, [$hotel_id, $hoy, $hoy]);
            $filas = $stmt ? $stmt->fetchAll() : [];

            $reservaciones = array_map(function (array $r): array {
                return [
                    'id' => (int) $r['id'],
                    'estado' => $r['estado'],
                    'fecha_entrada' => $r['fecha_entrada'],
                    'fecha_salida' => $r['fecha_salida'],
                    'hora_llegada_estimada' => $r['hora_llegada_estimada'],
                    'hora_entrada' => $r['hora_entrada'],
                    'hora_salida' => $r['hora_salida'],
                    'precio_total' => (float) $r['precio_total'],
                    'metodo_pago' => $r['metodo_pago'],
                    'notas' => $r['notas'],
                    'total_habitaciones' => (int) $r['total_habitaciones'],
                    'huesped_id' => (int) $r['huesped_id'],
                    'huesped_nombre' => $r['huesped_nombre'],
                    'huesped_telefono' => $r['huesped_telefono'],
                    'procedencia_estado' => $r['procedencia_estado'],
                    'habitaciones_numeros' => $r['habitaciones_numeros'],
                    'habitaciones_ids' => $r['habitaciones_ids'] ? array_map('intval', explode(',', $r['habitaciones_ids'])) : [],
                    'habitaciones_tipos' => $r['habitaciones_tipos'],
                ];
            }, $filas);

            View::renderJSON([
                'success' => true,
                'fecha' => $hoy,
                'total' => count($reservaciones),
                'reservaciones' => $reservaciones,
                'generado_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            error_log('[API reservacionesHoy] ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    public function syncAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Metodo no permitido. Usa POST.'], 405);
            return;
        }

        $this->validateCSRF();

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            View::renderJSON(['success' => false, 'message' => 'JSON invalido: ' . json_last_error_msg()], 400);
            return;
        }

        $operaciones = $data['operaciones'] ?? [];

        if (!is_array($operaciones)) {
            View::renderJSON(['success' => false, 'message' => 'El campo operaciones debe ser un array.'], 400);
            return;
        }

        if (empty($operaciones)) {
            View::renderJSON([
                'success' => true,
                'exitosas' => [],
                'fallidas' => [],
                'total' => 0,
                'procesadas' => 0,
                'mensaje' => 'Sin operaciones pendientes',
            ]);
            return;
        }

        if (count($operaciones) > 100) {
            View::renderJSON(['success' => false, 'message' => 'Demasiadas operaciones en un lote (max 100).'], 400);
            return;
        }

        $usuario_id = user_id();
        $operaciones_limpias = [];

        foreach ($operaciones as $op) {
            if (!is_array($op)) {
                continue;
            }

            $operaciones_limpias[] = [
                'uuid' => trim((string) ($op['uuid'] ?? '')),
                'tipo' => trim((string) ($op['tipo'] ?? '')),
                'payload' => is_array($op['payload'] ?? null) ? $op['payload'] : [],
                'timestamp' => (int) ($op['timestamp'] ?? 0),
                'usuario_id' => $usuario_id,
            ];
        }

        try {
            $syncModel = new Sync();
            $resultado = $syncModel->procesarLote($operaciones_limpias, $usuario_id);
            $total = count($operaciones_limpias);
            $procesadas = count($resultado['exitosas']);

            View::renderJSON([
                'success' => true,
                'exitosas' => $resultado['exitosas'],
                'fallidas' => $resultado['fallidas'],
                'total' => $total,
                'procesadas' => $procesadas,
                'mensaje' => "$procesadas de $total operaciones sincronizadas correctamente.",
            ]);
        } catch (Throwable $e) {
            error_log('[API sync] ' . $e->getMessage());
            View::renderJSON(['success' => false, 'message' => 'Error interno del servidor. Intenta de nuevo.'], 500);
        }
    }

    public function buscarHuespedesAction() {
        $termino = $this->getQuery('q', '');

        if ($termino === '__offline_cache__') {
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT id, nombre_completo, telefono, procedencia_estado
                 FROM huespedes
                 ORDER BY nombre_completo
                 LIMIT 500"
            );

            View::renderJSON([
                'success' => true,
                'data' => $stmt ? $stmt->fetchAll() : []
            ]);
            return;
        }
        
        if (strlen($termino) < 2) {
            View::renderJSON([
                'success' => true,
                'data' => []
            ]);
            return;
        }
        
        $db = Database::getInstance();
        
        $sql = "SELECT id, nombre_completo, telefono, procedencia_estado 
                FROM huespedes 
                WHERE nombre_completo LIKE ? OR telefono LIKE ?
                ORDER BY nombre_completo 
                LIMIT 10";
        
        $termino_busqueda = '%' . $termino . '%';
        $stmt = $db->query($sql, [$termino_busqueda, $termino_busqueda]);
        $huespedes = $stmt->fetchAll();
        
        View::renderJSON([
            'success' => true,
            'data' => $huespedes
        ]);
    }
    
    /**
     * Estadísticas del dashboard
     */
    public function estadisticasDashboardAction() {
        $dashboardController = new DashboardController($this->route_params);
        
        // Usar reflexión para acceder al método privado
        $reflection = new ReflectionMethod($dashboardController, 'getEstadisticasCompletas');
        $reflection->setAccessible(true);
        $stats = $reflection->invoke($dashboardController);
        
        View::renderJSON([
            'success' => true,
            'data' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Ocupacion actual del dashboard
     */
    public function ocupacionActualAction() {
        try {
            $this->hotelIdActual();
            $dashboardController = new DashboardController($this->route_params);

            $statsReflection = new ReflectionMethod($dashboardController, 'getEstadisticasCompletas');
            $statsReflection->setAccessible(true);
            $stats = $statsReflection->invoke($dashboardController);

            $graficosReflection = new ReflectionMethod($dashboardController, 'getDatosGraficos');
            $graficosReflection->setAccessible(true);
            $graficos = $graficosReflection->invoke($dashboardController);

            $habitaciones = $stats['habitaciones'] ?? [];

            View::renderJSON([
                'success' => true,
                'data' => [
                    'habitaciones' => $habitaciones,
                    'porcentaje_ocupacion' => (float)($habitaciones['porcentaje_ocupacion'] ?? 0),
                    'ocupadas' => (int)($habitaciones['ocupadas'] ?? 0),
                    'disponibles' => (int)($habitaciones['disponibles'] ?? 0),
                    'total' => (int)($habitaciones['total'] ?? 0),
                    'ocupacion_semanal' => $graficos['ocupacion_semanal'] ?? []
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Error obteniendo ocupacion dashboard: " . $e->getMessage());
            View::renderJSON([
                'success' => false,
                'message' => 'Error al obtener ocupacion del dashboard',
                'data' => [
                    'habitaciones' => [],
                    'porcentaje_ocupacion' => 0,
                    'ocupadas' => 0,
                    'disponibles' => 0,
                    'total' => 0,
                    'ocupacion_semanal' => []
                ]
            ], 500);
        }
    }
    
    /**
     * Alertas del dashboard
     */
    public function alertasDashboardAction() {
        try {
            $db = Database::getInstance();
            $hotel_id = $this->hotelIdActual();
            $alertas = [];

            $sqlCheckIns = "
                SELECT
                    r.id,
                    r.fecha_entrada,
                    h.nombre_completo,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = rh.hotel_id
                WHERE r.hotel_id = ?
                AND r.estado = 'confirmada'
                AND r.fecha_entrada < CURDATE()
                GROUP BY r.id, r.fecha_entrada, h.nombre_completo
                ORDER BY r.fecha_entrada
                LIMIT 5
            ";

            $stmt = $db->query($sqlCheckIns, [$hotel_id]);
            foreach ($stmt->fetchAll() as $reserva) {
                $dias = (int)($reserva['dias_retraso'] ?? 0);
                $alertas[] = [
                    'type' => 'warning',
                    'title' => 'Check-in pendiente',
                    'message' => sprintf(
                        'Reservacion #%d de %s, habitacion(es) %s, con %d dia(s) de retraso.',
                        (int)$reserva['id'],
                        $reserva['nombre_completo'] ?? 'Huesped',
                        $reserva['habitaciones'] ?: 'sin habitaciones',
                        $dias
                    )
                ];
            }

            $sqlCheckOuts = "
                SELECT
                    r.id,
                    r.fecha_salida,
                    h.nombre_completo,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                INNER JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                INNER JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = rh.hotel_id
                WHERE r.hotel_id = ?
                AND r.estado = 'checked_in'
                AND r.fecha_salida < CURDATE()
                GROUP BY r.id, r.fecha_salida, h.nombre_completo
                ORDER BY r.fecha_salida
                LIMIT 5
            ";

            $stmt = $db->query($sqlCheckOuts, [$hotel_id]);
            foreach ($stmt->fetchAll() as $reserva) {
                $dias = (int)($reserva['dias_retraso'] ?? 0);
                $alertas[] = [
                    'type' => 'error',
                    'title' => 'Check-out vencido',
                    'message' => sprintf(
                        'Reservacion #%d de %s, habitacion(es) %s, con %d dia(s) de retraso.',
                        (int)$reserva['id'],
                        $reserva['nombre_completo'] ?? 'Huesped',
                        $reserva['habitaciones'] ?: 'sin habitaciones',
                        $dias
                    )
                ];
            }

            View::renderJSON([
                'success' => true,
                'data' => $alertas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Error obteniendo alertas dashboard: " . $e->getMessage());
            View::renderJSON([
                'success' => false,
                'message' => 'Error al obtener alertas del dashboard',
                'data' => []
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de habitaciones múltiples
     */
    public function verificarDisponibilidadAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $habitaciones_ids = $this->getPost('habitaciones', []);
        $fecha_entrada = $this->getPost('fecha_entrada');
        $fecha_salida = $this->getPost('fecha_salida');
        $excluir_reservacion_id = $this->getPost('excluir_id');
        
        if (empty($habitaciones_ids) || !is_array($habitaciones_ids)) {
            View::renderJSON([
                'success' => false,
                'message' => 'Debe seleccionar al menos una habitación'
            ]);
            return;
        }
        
        $db = Database::getInstance();
        $hotel_id = $this->hotelIdActual();
        
        // Verificar cada habitación individualmente
        $habitaciones_no_disponibles = [];
        
        foreach ($habitaciones_ids as $hab_id) {
            $hab_id = (int) $hab_id;

            $stmt_hab = $db->query(
                "SELECT numero FROM habitaciones WHERE id = ? AND hotel_id = ?",
                [$hab_id, $hotel_id]
            );
            $hab_info = $stmt_hab ? $stmt_hab->fetch() : null;

            if (!$hab_info) {
                $habitaciones_no_disponibles[] = 'Habitacion no encontrada';
                continue;
            }

            $sql = "SELECT COUNT(*) as conflictos 
                    FROM reservacion_habitaciones rh
                    INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                        AND rh.hotel_id = r.hotel_id
                    INNER JOIN habitaciones h ON rh.habitacion_id = h.id
                        AND h.hotel_id = rh.hotel_id
                    WHERE rh.habitacion_id = ? 
                    AND rh.hotel_id = ?
                    AND h.hotel_id = ?
                    AND r.hotel_id = ?
                    AND r.estado IN ('confirmada', 'checked_in')
                    AND ((r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada <= ? AND r.fecha_salida >= ?)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?))";
            
            $params = [
                $hab_id,
                $hotel_id,
                $hotel_id,
                $hotel_id,
                $fecha_entrada, $fecha_entrada,
                $fecha_salida, $fecha_salida,
                $fecha_entrada, $fecha_salida
            ];
            
            if ($excluir_reservacion_id) {
                $sql .= " AND r.id != ?";
                $params[] = $excluir_reservacion_id;
            }
            
            $stmt = $db->query($sql, $params);
            $result = $stmt->fetch();
            
            if ($result['conflictos'] > 0) {
                // Obtener info de la habitación
                $habitaciones_no_disponibles[] = $hab_info['numero'];
            }
        }
        
        $todas_disponibles = count($habitaciones_no_disponibles) == 0;
        
        View::renderJSON([
            'success' => true,
            'disponible' => $todas_disponibles,
            'habitaciones_no_disponibles' => $habitaciones_no_disponibles,
            'mensaje' => $todas_disponibles 
                ? 'Todas las habitaciones están disponibles' 
                : 'Las siguientes habitaciones no están disponibles: ' . implode(', ', $habitaciones_no_disponibles)
        ]);
    }
    
    public function previewCheckinInventarioAction() {
    try {
        $reservacion_id = $this->route_params['id'] ?? 0;
        
        if (!$reservacion_id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'ID de reservación no válido'
            ]);
            return;
        }
        
        // Obtener habitaciones de la reservación
        $sql = "SELECT 
                    rh.habitacion_id,
                    h.numero,
                    h.tipo_habitacion_id,
                    th.nombre as tipo_nombre
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id
                LEFT JOIN tipos_habitacion th ON h.tipo_habitacion_id = th.id
                WHERE rh.reservacion_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reservacion_id]);
        $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($habitaciones)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se encontraron habitaciones para esta reservación'
            ]);
            return;
        }
        
        // Incluir el servicio de inventario
        require_once __DIR__ . '/../services/InventarioService.php';
        $inventarioService = new InventarioService($this->db);
        
        $todos_los_productos = [];
        $habitaciones_info = [];
        
        // Obtener productos para cada habitación
        foreach ($habitaciones as $hab) {
            $productos = $inventarioService->obtenerProductosCheckIn($hab['habitacion_id']);
            
            if (!empty($productos)) {
                foreach ($productos as $producto) {
                    // Agregar número de habitación al producto
                    $producto['habitacion'] = $hab['numero'];
                    $producto['tipo_habitacion'] = $hab['tipo_nombre'] ?? 'Sin tipo';
                    $todos_los_productos[] = $producto;
                }
                
                $habitaciones_info[] = [
                    'numero' => $hab['numero'],
                    'tipo' => $hab['tipo_nombre'] ?? 'Sin tipo',
                    'productos_count' => count($productos)
                ];
            }
        }
        
        // Agrupar productos por nombre para mostrar totales
        $productos_agrupados = [];
        foreach ($todos_los_productos as $producto) {
            $key = $producto['nombre'];
            if (!isset($productos_agrupados[$key])) {
                $productos_agrupados[$key] = [
                    'nombre' => $producto['nombre'],
                    'cantidad_total' => 0,
                    'unidad_medida' => $producto['unidad_medida'],
                    'stock_actual' => $producto['stock_actual'],
                    'disponible' => true,
                    'habitaciones' => []
                ];
            }
            
            $productos_agrupados[$key]['cantidad_total'] += $producto['cantidad'];
            $productos_agrupados[$key]['habitaciones'][] = $producto['habitacion'];
            
            // Verificar disponibilidad total
            if ($productos_agrupados[$key]['stock_actual'] < $productos_agrupados[$key]['cantidad_total']) {
                $productos_agrupados[$key]['disponible'] = false;
            }
        }
        
        // Convertir a array indexado
        $productos_finales = array_values($productos_agrupados);
        
        // Preparar respuesta
        $this->jsonResponse([
            'success' => true,
            'productos' => $productos_finales,
            'habitaciones' => $habitaciones_info,
            'total_productos' => count($productos_finales),
            'mensaje' => count($productos_finales) > 0 
                ? 'Se encontraron productos configurados para descuento automático'
                : 'No hay productos configurados para estas habitaciones'
        ]);
        
    } catch (Exception $e) {
        error_log("Error en previewCheckinInventario: " . $e->getMessage());
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error al obtener preview de inventario'
        ]);
    }
}

/**
 * Obtener alertas actuales de inventario
 * Endpoint: GET /api/inventario/alertas
 */
public function alertasInventarioAction() {
    try {
        // Obtener alertas activas
        $sql = "SELECT 
                    ai.*,
                    p.nombre as producto_nombre,
                    p.stock_actual,
                    p.stock_minimo
                FROM alertas_inventario ai
                INNER JOIN productos p ON ai.producto_id = p.id
                WHERE ai.estado = 'ACTIVA'
                AND DATE(ai.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                ORDER BY 
                    FIELD(ai.nivel, 'CRITICO', 'ADVERTENCIA', 'INFO'),
                    ai.created_at DESC
                LIMIT 10";
        
        $stmt = $this->db->query($sql);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->jsonResponse([
            'success' => true,
            'alertas' => $alertas,
            'total' => count($alertas)
        ]);
        
    } catch (Exception $e) {
        error_log("Error obteniendo alertas: " . $e->getMessage());
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error al obtener alertas de inventario'
        ]);
    }
}

/**
 * Verificar stock disponible para una habitación
 * Endpoint: GET /api/inventario/verificar-stock/{habitacion_id}
 */
public function verificarStockHabitacionAction() {
    try {
        $habitacion_id = $this->route_params['id'] ?? 0;
        
        if (!$habitacion_id) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'ID de habitación no válido'
            ]);
            return;
        }
        
        require_once __DIR__ . '/../services/InventarioService.php';
        $inventarioService = new InventarioService($this->db);
        
        $verificacion = $inventarioService->verificarDisponibilidadInventario($habitacion_id);
        
        $this->jsonResponse([
            'success' => true,
            'disponible' => $verificacion['disponible'],
            'productos_faltantes' => $verificacion['productos_faltantes'],
            'total_productos' => $verificacion['total_productos']
        ]);
        
    } catch (Exception $e) {
        error_log("Error verificando stock: " . $e->getMessage());
        $this->jsonResponse([
            'success' => false,
            'error' => 'Error al verificar disponibilidad de inventario'
        ]);
    }
}
    
    /**
     * Calcular precio de estancia para múltiples habitaciones
     */
    public function calcularPrecioAction() {
    $habitaciones_ids = $this->getQuery('habitaciones', []);
    $fecha_entrada = $this->getQuery('fecha_entrada');
    $fecha_salida = $this->getQuery('fecha_salida');
    $hora_llegada = $this->getQuery('hora_llegada');
    
    // Validar que habitaciones_ids sea un array
    if (is_string($habitaciones_ids)) {
        $habitaciones_ids = explode(',', $habitaciones_ids);
    }
    $habitaciones_ids = array_values(array_unique(array_filter(array_map('intval', $habitaciones_ids))));
    
    if (empty($habitaciones_ids) || !$fecha_entrada || !$fecha_salida) {
        View::renderJSON([
            'success' => false,
            'message' => 'Faltan parámetros'
        ]);
        return;
    }
    
    $db = Database::getInstance();
    
    // Obtener información de las habitaciones
    $hotel_id = $this->hotelIdActual();
    $placeholders = str_repeat('?,', count($habitaciones_ids) - 1) . '?';
    $sql = "SELECT id, numero, tipo, precio_base FROM habitaciones WHERE hotel_id = ? AND id IN ($placeholders)";
    $stmt = $db->query($sql, array_merge([$hotel_id], $habitaciones_ids));
    $habitaciones = $stmt->fetchAll();
    
    if (count($habitaciones) !== count($habitaciones_ids)) {
        View::renderJSON([
            'success' => false,
            'message' => 'Habitaciones no encontradas para el hotel actual'
        ]);
        return;
    }
    
    // Incluir modelos necesarios
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    require_once __DIR__ . '/../models/Reservacion.php';
    
    $tarifaModel = new IncrementoTarifa();
    $reservacionModel = new Reservacion();
    
    // Calcular precio de cada habitación con incrementos de tarifa
    $habitaciones_con_precio = [];
    foreach ($habitaciones as $hab) {
        // Calcular días de estancia
        $fecha_inicio = new DateTime($fecha_entrada);
        $fecha_fin = new DateTime($fecha_salida);
        $noches = $fecha_inicio->diff($fecha_fin)->days;
        
        if ($noches == 0) $noches = 1;
        
        // Calcular precio para cada noche
        $precio_total_habitacion = 0;
        $fecha_actual = clone $fecha_inicio;
        
        for ($i = 0; $i < $noches; $i++) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            $precio_info = $tarifaModel->calcularPrecioConIncremento(
                $hab['id'],
                $hab['tipo'],
                $hab['precio_base'],
                $fecha_str
            );
            
            $precio_total_habitacion += $precio_info['precio_final'];
            $fecha_actual->modify('+1 day');
        }
        
        $hab['precio_calculado'] = $precio_total_habitacion;
        $hab['precio_por_noche'] = $precio_total_habitacion / $noches;
        $habitaciones_con_precio[] = $hab;
    }
    
    // Usar el método del modelo Reservacion para calcular con descuentos
    $calculo = $reservacionModel->calcularPrecioMultiple(
        $habitaciones_con_precio,
        $fecha_entrada,
        $fecha_salida,
        $hora_llegada
    );
    
    View::renderJSON([
        'success' => true,
        'data' => [
            'precio_por_noche' => $calculo['precio_sin_descuento'] / $calculo['noches'],
            'noches' => $calculo['noches'],
            'total_habitaciones' => count($habitaciones),
            'habitaciones_cortesia' => $calculo['habitaciones_cortesia'],
            'precio_sin_descuento' => $calculo['precio_sin_descuento'],
            'precio_total' => $calculo['precio_total'],
            'ahorro' => $calculo['precio_sin_descuento'] - $calculo['precio_total'],
            'precio_formateado' => format_money($calculo['precio_total']),
            'es_madrugada' => $calculo['es_madrugada'],
            'habitaciones' => $habitaciones_con_precio
        ]
    ]);
}

public function incrementosTarifaActivosAction() {
    $fecha = $this->getQuery('fecha', date('Y-m-d'));
    
    require_once __DIR__ . '/../models/IncrementoTarifa.php';
    $tarifaModel = new IncrementoTarifa();
    
    $incrementos = $tarifaModel->getActivosParaFecha($fecha);
    
    View::renderJSON([
        'success' => true,
        'fecha' => $fecha,
        'incrementos_activos' => $incrementos,
        'total' => count($incrementos)
    ]);
}
    
    /**
     * Obtener detalle de habitaciones para resumen
     */
    public function habitacionesAction() {
    $action = $this->route_params['action'] ?? '';
    
    if ($action === 'disponibles') {
        $this->habitacionesDisponiblesAction();
    } else {
        View::renderJSON([
            'success' => false,
            'message' => 'Acción no válida'
        ], 404);
    }
}
    
    /**
     * Obtener reservaciones activas por habitación
     */
    public function reservacionesActivasHabitacionAction() {
        $habitacion_id = $this->getQuery('habitacion_id');
        
        if (!$habitacion_id) {
            View::renderJSON([
                'success' => false,
                'message' => 'Falta ID de habitación'
            ]);
            return;
        }
        
        $db = Database::getInstance();
        
        $sql = "SELECT r.*, h.nombre_completo as huesped_nombre, h.telefono
                FROM reservaciones r
                INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE rh.habitacion_id = ?
                AND r.estado IN ('confirmada', 'checked_in')
                AND r.fecha_salida >= CURDATE()
                ORDER BY r.fecha_entrada";
        
        $stmt = $db->query($sql, [$habitacion_id]);
        $reservaciones = $stmt->fetchAll();
        
        View::renderJSON([
            'success' => true,
            'data' => $reservaciones
        ]);
    }
}
