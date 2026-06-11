<?php
/**
 * Funciones Helper Generales
 * Los Cedros
 */

// Verificar si PUBLIC_PATH está definida
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', dirname(__DIR__) . '/public');
}

// Incluir modelos necesarios
if (file_exists(__DIR__ . '/../app/models/HuespedVehiculo.php')) {
    require_once __DIR__ . '/../app/models/HuespedVehiculo.php';
}

/**
 * Obtener la URL base de la aplicación
 */
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $base_dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $base = $protocol . '://' . $host . $base_dir;

    if ($path) {
        return $base . '/' . ltrim($path, '/');
    }

    return $base;
}

/**
 * Formatear cantidad como moneda
 */
function format_currency($amount, $decimals = 2) {
    return '$' . number_format($amount, $decimals, '.', ',');
}


/**
 * Verificar si el hotel tiene la llave de una habitación
 */
function hotel_tiene_llave($habitacion_id) {
    if (class_exists('ControlLlave')) {
        $controlLlave = new ControlLlave();
        return $controlLlave->hotelTieneLlave($habitacion_id);
    }
    return false;
}

/**
 * Obtener icono de llave según estado
 */
function get_icono_llave($tiene_llave) {
    if ($tiene_llave) {
        return '<i class="fas fa-key text-green-500" title="Hotel tiene la llave"></i>';
    } else {
        return '<i class="fas fa-key text-gray-300" title="Huésped tiene la llave"></i>';
    }
}

/**
 * Verificar si el hotel tiene el control remoto de una habitación
 */
function hotel_tiene_remoto($habitacion_id) {
    if (class_exists('ControlRemoto')) {
        $controlRemoto = new ControlRemoto();
        return $controlRemoto->hotelTieneRemoto($habitacion_id);
    }
    return true;
}

/**
 * Obtener icono de control remoto según estado
 * TV encendida = Huésped tiene el control (en uso)
 * TV apagada = Hotel tiene el control (no en uso)
 */
function get_icono_remoto($tiene_remoto) {
    if ($tiene_remoto) {
        // Hotel tiene el control = TV apagada
        return '<i class="fas fa-tv text-gray-300" title="Control en recepción"></i>';
    } else {
        // Huésped tiene el control = TV encendida
        return '<i class="fas fa-tv text-yellow-500" title="Control con huésped"></i>';
    }
}

/**
 * Obtener etiqueta para tipo de identificación
 */
function get_tipo_identificacion_label($tipo) {
    $tipos = [
        'ine' => 'INE',
        'licencia' => 'Licencia de Conducir',
        'otro' => 'Otro'
    ];
    
    return $tipos[$tipo] ?? 'No especificado';
}
/**
 * Helper Functions para Vehículos
 * Los Cedros
 */

/**
 * Obtener estadísticas de estacionamiento
 */
function get_estadisticas_estacionamiento() {
    if (class_exists('HuespedVehiculo')) {
        $vehiculoModel = new HuespedVehiculo();
        return $vehiculoModel->contarPorEstacionamiento();
    }
    return [];
}

/**
 * Agregar versión a los assets para evitar caché
 */
function asset_version($path) {
    $file = PUBLIC_PATH . '/' . ltrim($path, '/');
    $version = file_exists($file) ? filemtime($file) : time();
    return asset($path) . '?v=' . $version;
}

/**
 * Obtener clase CSS para ubicación de estacionamiento
 */
function get_estacionamiento_badge_class($estacionamiento) {
    $clases = [
        'primer_piso' => 'bg-blue-100 text-blue-800',
        'segundo_piso' => 'bg-green-100 text-green-800',
        'fuera_hotel' => 'bg-gray-100 text-gray-800'
    ];
    
    return $clases[$estacionamiento] ?? 'bg-gray-100 text-gray-800';
}

/**
 * Obtener icono para ubicación de estacionamiento
 */
function get_estacionamiento_icon($estacionamiento) {
    $iconos = [
        'primer_piso' => 'fa-parking',
        'segundo_piso' => 'fa-arrow-up',
        'fuera_hotel' => 'fa-road'
    ];
    
    return $iconos[$estacionamiento] ?? 'fa-question';
}

/**
 * Formatear información de vehículo
 */
function format_vehiculo_info($vehiculo) {
    $info = $vehiculo['marca'];
    
    if (!empty($vehiculo['modelo'])) {
        $info .= ' ' . $vehiculo['modelo'];
    }
    
    if (!empty($vehiculo['placas'])) {
        $info .= ' - ' . $vehiculo['placas'];
    }
    
    return $info;
}

/**
 * Verificar disponibilidad de espacio en estacionamiento
 */
function verificar_disponibilidad_estacionamiento($ubicacion) {
    $limites = [
        'primer_piso' => 30,
        'segundo_piso' => 20,
        'fuera_hotel' => null
    ];
    
    if (!isset($limites[$ubicacion]) || $limites[$ubicacion] === null) {
        return true;
    }
    
    $estadisticas = get_estadisticas_estacionamiento();
    $ocupados = $estadisticas[$ubicacion] ?? 0;
    
    return $ocupados < $limites[$ubicacion];
}

/**
 * Obtener resumen de vehículos por huésped
 */
function get_resumen_vehiculos_huesped($huesped_id) {
    if (class_exists('HuespedVehiculo')) {
        $vehiculoModel = new HuespedVehiculo();
        $vehiculos = $vehiculoModel->porHuesped($huesped_id);
        
        if (empty($vehiculos)) {
            return 'Sin vehículos';
        }
        
        $resumen = [];
        foreach ($vehiculos as $vehiculo) {
            $resumen[] = $vehiculo['marca'] . ' (' . $vehiculo['placas'] . ')';
        }
        
        return implode(', ', $resumen);
    }
    return 'Sin vehículos';
}

/**
 * Generar URL completa
 */
function url($path = '') {
    return base_url($path);
}

/**
 * Generar URL para assets
 */
function asset($path) {
    $path = ltrim($path, '/');
    
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    return base_url($path);
}

/**
 * Verificar si una imagen existe
 */
function image_exists($path) {
    if (empty($path)) {
        return false;
    }
    
    if (strpos($path, 'http') === 0) {
        $headers = @get_headers($path);
        return $headers && strpos($headers[0], '200') !== false;
    }
    
    $fullPath = PUBLIC_PATH . '/' . ltrim($path, '/');
    return file_exists($fullPath);
}

/**
 * Obtener URL de imagen con fallback
 */
function image_url($path, $fallback = '/img/default-room.jpg') {
    if (empty($path)) {
        return asset($fallback);
    }
    
    if (image_exists($path)) {
        return asset($path);
    }
    
    return asset($fallback);
}

/**
 * Obtener información de imagen
 */
function get_image_info($path) {
    if (empty($path) || !image_exists($path)) {
        return null;
    }
    
    $fullPath = PUBLIC_PATH . '/' . ltrim($path, '/');
    $imageInfo = getimagesize($fullPath);
    
    if ($imageInfo === false) {
        return null;
    }
    
    return [
        'width' => $imageInfo[0],
        'height' => $imageInfo[1],
        'type' => $imageInfo[2],
        'mime' => $imageInfo['mime'],
        'size' => filesize($fullPath),
        'url' => asset($path)
    ];
}

/**
 * Obtener thumbnail
 */
function get_thumbnail($imagePath, $width = 300, $height = 200) {
    if (empty($imagePath) || !image_exists($imagePath)) {
        return asset('/img/default-room-thumb.jpg');
    }
    
    return asset($imagePath);
}

/**
 * Redireccionar a otra página
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . url($url), true, $statusCode);
    exit();
}

/**
 * Generar HTML para imagen de habitación
 */
function habitacion_image_html($habitacion, $class = '', $alt = null) {
    $alt = $alt ?: 'Habitación ' . $habitacion['numero'];
    $src = !empty($habitacion['foto_url']) ? asset($habitacion['foto_url']) : asset('/img/default-room.jpg');
    
    return sprintf(
        '<img src="%s" alt="%s" class="%s" loading="lazy">',
        htmlspecialchars($src),
        htmlspecialchars($alt),
        htmlspecialchars($class)
    );
}

/**
 * Validar tipo de imagen
 */
function is_valid_image_type($mimeType) {
    $allowedTypes = [
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp'
    ];
    
    return in_array($mimeType, $allowedTypes);
}

/**
 * Obtener extensión por MIME type
 */
function get_extension_by_mime($mimeType) {
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif', 
        'image/webp' => 'webp'
    ];
    
    return $extensions[$mimeType] ?? 'jpg';
}

/**
 * Formatear tamaño de archivo
 */
function format_file_size($bytes) {
    if ($bytes == 0) return '0 Bytes';
    
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Crear directorio de uploads si no existe
 */
function ensure_upload_directory($subdir = '') {
    $baseDir = PUBLIC_PATH . '/uploads';
    $fullDir = $baseDir . ($subdir ? '/' . trim($subdir, '/') : '');
    
    if (!file_exists($fullDir)) {
        if (!mkdir($fullDir, 0755, true)) {
            error_log("No se pudo crear directorio: $fullDir");
            return false;
        }
    }
    
    return $fullDir;
}

/**
 * Limpiar nombre de archivo
 */
function sanitize_filename($filename) {
    $pathinfo = pathinfo($filename);
    $name = $pathinfo['filename'];
    $extension = $pathinfo['extension'] ?? '';
    
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    $name = trim($name, '_');
    
    if (empty($name)) {
        $name = 'imagen_' . uniqid();
    }
    
    return $name . ($extension ? '.' . $extension : '');
}

/**
 * Generar nombre único para imagen de habitación
 */
function generate_room_image_name($numeroHabitacion, $originalName = '') {
    $extension = 'jpg';
    
    if (!empty($originalName)) {
        $valor = strtolower((string) $originalName);
        $pathinfo = pathinfo($valor);
        $extension = strtolower($pathinfo['extension'] ?? $valor);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowedExtensions, true)) {
        $extension = 'jpg';
    }

    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }
    
    $prefix = 'san_nicolas_hab_' . $numeroHabitacion;
    $unique = uniqid();
    
    return $prefix . '_' . $unique . '.' . $extension;
}

/**
 * Verificar si una ruta de imagen es segura
 */
function is_safe_image_path($path) {
    if (strpos($path, '..') !== false) {
        return false;
    }
    
    $allowedPaths = ['/uploads/', '/img/', '/images/'];
    $isAllowed = false;
    
    foreach ($allowedPaths as $allowedPath) {
        if (strpos($path, $allowedPath) === 0) {
            $isAllowed = true;
            break;
        }
    }
    
    return $isAllowed;
}

/**
 * Obtener lista de imágenes en directorio
 */
function get_images_in_directory($directory) {
    $fullPath = PUBLIC_PATH . '/' . trim($directory, '/');
    
    if (!is_dir($fullPath)) {
        return [];
    }
    
    $images = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    $files = scandir($fullPath);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, $allowedExtensions)) {
            $images[] = [
                'filename' => $file,
                'path' => $directory . '/' . $file,
                'url' => asset($directory . '/' . $file),
                'size' => filesize($fullPath . '/' . $file)
            ];
        }
    }
    
    return $images;
}

/**
 * Limpiar archivos temporales de imágenes
 */
function cleanup_temp_images($olderThanHours = 24) {
    $tempDir = PUBLIC_PATH . '/uploads/temp';
    
    if (!is_dir($tempDir)) {
        return;
    }
    
    $cutoffTime = time() - ($olderThanHours * 3600);
    $files = scandir($tempDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $fullPath = $tempDir . '/' . $file;
        if (is_file($fullPath) && filemtime($fullPath) < $cutoffTime) {
            unlink($fullPath);
        }
    }
}

/**
 * Generar campo CSRF
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Generar token CSRF con tiempo de vida
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Renovar token si ha expirado (4 horas)
    $token_age = time() - $_SESSION['csrf_token_time'];
    if ($token_age > 14400) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verificar token CSRF con tiempo de vida
 */
function verify_csrf_token($token) {
    // ✅ Validar que el token no sea null o vacío
    if (empty($token)) {
        return false;
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Token expira después de 4 horas
    $token_age = time() - $_SESSION['csrf_token_time'];
    if ($token_age > 14400) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtener token CSRF desde formulario o headers AJAX/JSON.
 */
function csrf_token_from_request() {
    if (!empty($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }

    $headers = [
        'HTTP_X_CSRF_TOKEN',
        'HTTP_X_CSRFTOKEN',
        'HTTP_X_XSRF_TOKEN',
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            return $_SERVER[$header];
        }
    }

    return null;
}

/**
 * Detectar HTTPS sin confiar en cabeceras arbitrarias de cliente.
 */
function is_https_request() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

/**
 * Establecer mensaje flash
 */
function set_mensaje($texto, $tipo = 'info') {
    $_SESSION['flash_message'] = [
        'texto' => $texto,
        'tipo' => $tipo
    ];
}

/**
 * Obtener mensaje flash
 */
function get_mensaje() {
    if (isset($_SESSION['flash_message'])) {
        $mensaje = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $mensaje;
    }
    return null;
}

/**
 * Sanitizar entrada
 */
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Obtener valor de $_POST de forma segura
 */
function post($key, $default = null) {
    return isset($_POST[$key]) ? sanitize($_POST[$key]) : $default;
}

/**
 * Obtener valor de $_GET de forma segura
 */
function get($key, $default = null) {
    return isset($_GET[$key]) ? sanitize($_GET[$key]) : $default;
}

/**
 * Verificar si la petición es AJAX
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Respuesta JSON
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Formatear fecha con soporte mejorado para español
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    if ($timestamp === false) return '';
    
    // Para formatos especiales en español
    if (strpos($format, 'l') !== false || strpos($format, 'F') !== false) {
        $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 
                  'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        
        if ($format == 'l, d \d\e F \d\e Y') {
            $dia_semana = $dias[date('w', $timestamp)];
            $dia = date('d', $timestamp);
            $mes = $meses[date('n', $timestamp) - 1];
            $año = date('Y', $timestamp);
            
            return ucfirst($dia_semana) . ', ' . $dia . ' de ' . $mes . ' de ' . $año;
        }
    }
    
    return date($format, $timestamp);
}

/**
 * Formatear fecha y hora
 */
function format_datetime($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

/**
 * Formatear dinero
 */
function format_money($amount) {
    return '$' . number_format($amount, 2, '.', ',');
}

/**
 * Debug (solo en desarrollo)
 */
function dd($data) {
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        return;
    }
    
    echo '<pre style="background: #f4f4f4; padding: 10px; border: 1px solid #ddd;">';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Generar string aleatorio
 */
function generate_random_string($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validar email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Obtener tipo de habitación formateado
 */
function get_tipo_habitacion($tipo) {
    if (function_exists('hotel_room_catalog_types')) {
        try {
            $tiposCatalogo = hotel_room_catalog_types();
            if (isset($tiposCatalogo[$tipo])) {
                return $tiposCatalogo[$tipo];
            }
        } catch (Throwable $e) {
            error_log('Error consultando catalogo de tipos de habitacion: ' . $e->getMessage());
        }
    }

    $tipos = [
        'sencilla' => 'Sencilla',
        'doble' => 'Doble',
        'triple' => 'Triple',
        'cuadruple' => 'Cuádruple',
        'sencilla_manolo' => 'Sencilla Manolo',
        'doble_manolo' => 'Doble Manolo'
    ];
    
    return $tipos[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
}

/**
 * Limpiar todos los valores old del formulario
 */
function clear_old_input() {
    unset($_SESSION['old_input']);
}

/**
 * Obtener estados de habitación
 */
function get_estados_habitacion() {
    return [
        'disponible' => [
            'label' => 'Disponible', 
            'color' => 'green', 
            'icon' => 'check-circle',
            'badge_class' => 'bg-green-100 text-green-800'
        ],
        'por_llegar' => [
            'label' => 'Por llegar', 
            'color' => 'purple', 
            'icon' => 'clock',
            'badge_class' => 'bg-purple-100 text-purple-800'
        ],
        'ocupada' => [
            'label' => 'Ocupada', 
            'color' => 'red', 
            'icon' => 'user-check',
            'badge_class' => 'bg-red-100 text-red-800'
        ],
        'mantenimiento' => [
            'label' => 'Mantenimiento', 
            'color' => 'yellow', 
            'icon' => 'tools',
            'badge_class' => 'bg-yellow-100 text-yellow-800'
        ],
        'limpieza' => [
            'label' => 'Limpieza', 
            'color' => 'blue', 
            'icon' => 'broom',
            'badge_class' => 'bg-blue-100 text-blue-800'
        ]
    ];
}

/**
 * Obtener valor anterior del formulario
 */
function old($key, $default = '') {
    if (isset($_SESSION['old_input'][$key])) {
        return htmlspecialchars($_SESSION['old_input'][$key]);
    }
    return $default;
}

/**
 * Guardar input anterior en sesión
 */
function save_old_input($data) {
    $_SESSION['old_input'] = $data;
}

/**
 * Obtener IP del cliente
 */
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    if ($ip === 'UNKNOWN') {
        return $ip;
    }

    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
}
