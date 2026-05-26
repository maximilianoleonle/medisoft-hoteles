<?php
/**
 * Controlador de Setup - Configuración inicial del sistema
 * Los Cedros
 */

class SetupController extends Controller {
    
    /**
     * Verificar autenticación y permisos
     */
    protected function before() {
        $this->requireAuth();
        $this->requireRole('gerente'); // Solo gerente puede hacer setup
        return true;
    }
    
    /**
     * Crear estructura de directorios necesaria
     */
    public function createDirectoriesAction() {
        $baseDir = PUBLIC_PATH;
        $directories = [
            'uploads',
            'uploads/habitaciones',
            'uploads/habitaciones/defaults',
            'uploads/temp',
            'img'
        ];
        
        $resultados = [];
        $errores = [];
        
        foreach ($directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            
            if (!file_exists($fullPath)) {
                if (mkdir($fullPath, 0755, true)) {
                    $resultados[] = "✓ Creado: /$dir";
                } else {
                    $errores[] = "✗ Error creando: /$dir";
                }
            } else {
                $resultados[] = "→ Ya existe: /$dir";
            }
        }
        
        // Crear archivo .htaccess para uploads (seguridad)
        $htaccessContent = "# Protección para directorio uploads - Los Cedros
Options -Indexes
<FilesMatch \"\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Permitir solo imágenes
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>";

        $htaccessPath = $baseDir . '/uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            if (file_put_contents($htaccessPath, $htaccessContent)) {
                $resultados[] = "✓ Creado archivo de seguridad: /uploads/.htaccess";
            } else {
                $errores[] = "✗ Error creando .htaccess de seguridad";
            }
        } else {
            $resultados[] = "→ Ya existe archivo de seguridad: /uploads/.htaccess";
        }
        
        // Crear imagen por defecto si no existe
        $this->crearImagenPorDefecto($baseDir, $resultados, $errores);
        
        // Verificar permisos
        $this->verificarPermisos($baseDir, $directories, $resultados, $errores);
        
        // Crear algunas imágenes por defecto para tipos de habitación
        $this->crearImagenesTipos($baseDir, $resultados, $errores);
        
        // Respuesta
        if ($this->isAjax()) {
            View::renderJSON([
                'success' => count($errores) == 0,
                'resultados' => $resultados,
                'errores' => $errores,
                'total_operaciones' => count($resultados) + count($errores)
            ]);
        } else {
            View::renderTemplate('setup/directories', [
                'title' => 'Configuración de Directorios - Los Cedros',
                'resultados' => $resultados,
                'errores' => $errores,
                'success' => count($errores) == 0
            ]);
        }
    }
    
    /**
     * Crear imagen por defecto
     */
    private function crearImagenPorDefecto($baseDir, &$resultados, &$errores) {
        $defaultImagePath = $baseDir . '/img/default-room.jpg';
        
        if (!file_exists($defaultImagePath)) {
            try {
                // Crear imagen por defecto
                $width = 600;
                $height = 400;
                $image = imagecreatetruecolor($width, $height);
                
                // Colores del Los Cedros
                $background = imagecolorallocate($image, 245, 240, 230); // Cream
                $border = imagecolorallocate($image, 139, 69, 19); // Brown
                $text = imagecolorallocate($image, 101, 67, 33); // Dark brown
                $accent = imagecolorallocate($image, 218, 165, 32); // Gold
                
                // Fondo
                imagefill($image, 0, 0, $background);
                
                // Borde decorativo
                imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);
                imagerectangle($image, 10, 10, $width - 11, $height - 11, $border);
                
                // Textos
                $font = 5;
                $text1 = "HOTEL SAN NICOLAS";
                $text2 = "Santa Catarina Juquila, Oaxaca";
                $text3 = "Imagen no disponible";
                
                // Centrar textos
                $x1 = ($width - strlen($text1) * imagefontwidth($font)) / 2;
                $x2 = ($width - strlen($text2) * imagefontwidth($font)) / 2;
                $x3 = ($width - strlen($text3) * imagefontwidth($font)) / 2;
                
                imagestring($image, $font, $x1, $height / 2 - 40, $text1, $border);
                imagestring($image, 3, $x2, $height / 2 - 10, $text2, $text);
                imagestring($image, 4, $x3, $height / 2 + 20, $text3, $accent);
                
                // Agregar elementos decorativos
                $this->agregarDecoracion($image, $width, $height, $accent);
                
                // Guardar
                if (imagejpeg($image, $defaultImagePath, 90)) {
                    $resultados[] = "✓ Creada imagen por defecto: /img/default-room.jpg";
                } else {
                    $errores[] = "✗ Error guardando imagen por defecto";
                }
                
                imagedestroy($image);
            } catch (Exception $e) {
                $errores[] = "✗ Error creando imagen por defecto: " . $e->getMessage();
            }
        } else {
            $resultados[] = "→ Ya existe imagen por defecto: /img/default-room.jpg";
        }
    }
    
    /**
     * Agregar decoración a la imagen
     */
    private function agregarDecoracion($image, $width, $height, $color) {
        // Agregar algunas formas decorativas simples
        $centerX = $width / 2;
        $centerY = $height / 2;
        
        // Círculos decorativos en las esquinas
        imagefilledellipse($image, 50, 50, 20, 20, $color);
        imagefilledellipse($image, $width - 50, 50, 20, 20, $color);
        imagefilledellipse($image, 50, $height - 50, 20, 20, $color);
        imagefilledellipse($image, $width - 50, $height - 50, 20, 20, $color);
        
        // Líneas decorativas
        imageline($image, $centerX - 100, $centerY + 60, $centerX + 100, $centerY + 60, $color);
        imageline($image, $centerX - 80, $centerY + 65, $centerX + 80, $centerY + 65, $color);
    }
    
    /**
     * Crear imágenes por defecto para cada tipo de habitación
     */
    private function crearImagenesTipos($baseDir, &$resultados, &$errores) {
        $tiposHabitacion = [
            'sencilla' => 'Habitación Sencilla',
            'doble' => 'Habitación Doble',
            'triple' => 'Habitación Triple', 
            'suite' => 'Suite',
            'premium' => 'Habitación Premium'
        ];
        
        $defaultsDir = $baseDir . '/uploads/habitaciones/defaults';
        
        foreach ($tiposHabitacion as $tipo => $nombre) {
            $imagePath = $defaultsDir . "/default-{$tipo}.jpg";
            
            if (!file_exists($imagePath)) {
                try {
                    $width = 600;
                    $height = 400;
                    $image = imagecreatetruecolor($width, $height);
                    
                    // Colores diferentes para cada tipo
                    $colores = [
                        'sencilla' => [240, 248, 255], // Alice blue
                        'doble' => [240, 255, 240],    // Honeydew
                        'triple' => [255, 250, 240],   // Floral white
                        'suite' => [248, 248, 255],    // Ghost white
                        'premium' => [255, 248, 220]   // Cornsilk
                    ];
                    
                    $bgColor = $colores[$tipo];
                    $background = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
                    $border = imagecolorallocate($image, 139, 69, 19);
                    $text = imagecolorallocate($image, 101, 67, 33);
                    
                    imagefill($image, 0, 0, $background);
                    imagerectangle($image, 0, 0, $width - 1, $height - 1, $border);
                    
                    // Texto
                    $font = 4;
                    $text1 = "HOTEL SAN NICOLAS";
                    $text2 = strtoupper($nombre);
                    
                    $x1 = ($width - strlen($text1) * imagefontwidth($font)) / 2;
                    $x2 = ($width - strlen($text2) * imagefontwidth($font)) / 2;
                    
                    imagestring($image, $font, $x1, $height / 2 - 20, $text1, $border);
                    imagestring($image, 3, $x2, $height / 2 + 10, $text2, $text);
                    
                    if (imagejpeg($image, $imagePath, 85)) {
                        $resultados[] = "✓ Creada imagen por defecto para: $nombre";
                    } else {
                        $errores[] = "✗ Error creando imagen para: $nombre";
                    }
                    
                    imagedestroy($image);
                } catch (Exception $e) {
                    $errores[] = "✗ Error creando imagen para $nombre: " . $e->getMessage();
                }
            }
        }
    }
    
    /**
     * Verificar permisos de directorios
     */
    private function verificarPermisos($baseDir, $directories, &$resultados, &$errores) {
        foreach ($directories as $dir) {
            $fullPath = $baseDir . '/' . $dir;
            
            if (file_exists($fullPath)) {
                $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                
                if ($perms >= '0755') {
                    $resultados[] = "✓ Permisos OK para /$dir ($perms)";
                } else {
                    $errores[] = "⚠ Permisos insuficientes para /$dir ($perms) - Recomendado: 0755";
                    
                    // Intentar corregir permisos
                    if (chmod($fullPath, 0755)) {
                        $resultados[] = "✓ Permisos corregidos para /$dir";
                    } else {
                        $errores[] = "✗ No se pudieron corregir permisos para /$dir";
                    }
                }
                
                // Verificar que se pueda escribir
                if (!is_writable($fullPath)) {
                    $errores[] = "✗ Directorio /$dir no es escribible";
                }
            }
        }
    }
    
    /**
     * Verificar configuración del sistema
     */
    public function verificarConfiguracionAction() {
        $checks = [];
        
        // Verificar extensiones PHP necesarias
        $extensiones = ['gd', 'fileinfo', 'exif'];
        foreach ($extensiones as $ext) {
            $checks['php_' . $ext] = [
                'nombre' => "Extensión PHP: $ext",
                'status' => extension_loaded($ext),
                'mensaje' => extension_loaded($ext) ? 'Disponible' : 'No disponible - Requerida para manejo de imágenes'
            ];
        }
        
        // Verificar funciones GD
        $funcionesGD = ['imagecreatetruecolor', 'imagejpeg', 'imagepng', 'imagewebp'];
        foreach ($funcionesGD as $func) {
            $checks['gd_' . $func] = [
                'nombre' => "Función GD: $func",
                'status' => function_exists($func),
                'mensaje' => function_exists($func) ? 'Disponible' : 'No disponible'
            ];
        }
        
        // Verificar límites de PHP
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        $memoryLimit = ini_get('memory_limit');
        
        $checks['upload_max_filesize'] = [
            'nombre' => 'Tamaño máximo de archivo',
            'status' => $this->parseSize($uploadMax) >= 5 * 1024 * 1024,
            'mensaje' => "Actual: $uploadMax (Recomendado: 5M o mayor)"
        ];
        
        $checks['post_max_size'] = [
            'nombre' => 'Tamaño máximo de POST',
            'status' => $this->parseSize($postMax) >= 5 * 1024 * 1024,
            'mensaje' => "Actual: $postMax (Recomendado: 5M o mayor)"
        ];
        
        // Verificar directorios
        $directorios = ['uploads', 'uploads/habitaciones', 'img'];
        foreach ($directorios as $dir) {
            $path = PUBLIC_PATH . '/' . $dir;
            $checks['dir_' . str_replace('/', '_', $dir)] = [
                'nombre' => "Directorio: /$dir",
                'status' => file_exists($path) && is_writable($path),
                'mensaje' => file_exists($path) 
                    ? (is_writable($path) ? 'Existe y es escribible' : 'Existe pero no es escribible')
                    : 'No existe'
            ];
        }
        
        if ($this->isAjax()) {
            View::renderJSON([
                'success' => true,
                'checks' => $checks,
                'resumen' => [
                    'total' => count($checks),
                    'exitosos' => count(array_filter($checks, function($c) { return $c['status']; })),
                    'fallidos' => count(array_filter($checks, function($c) { return !$c['status']; }))
                ]
            ]);
        } else {
            View::renderTemplate('setup/verificar', [
                'title' => 'Verificar Configuración - Los Cedros',
                'checks' => $checks
            ]);
        }
    }
    
    /**
     * Convertir tamaño de PHP a bytes
     */
    private function parseSize($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }
    
    /**
     * Limpiar sistema (eliminar archivos temporales, etc.)
     */
    public function limpiarSistemaAction() {
        if (!$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $resultados = [];
        $errores = [];
        
        // Limpiar imágenes temporales
        try {
            cleanup_temp_images(24);
            $resultados[] = "✓ Limpieza de imágenes temporales completada";
        } catch (Exception $e) {
            $errores[] = "✗ Error limpiando imágenes temporales: " . $e->getMessage();
        }
        
        // Limpiar logs antiguos si existen
        $logsDir = STORAGE_PATH . '/logs';
        if (is_dir($logsDir)) {
            $files = glob($logsDir . '/*.log');
            $cleaned = 0;
            
            foreach ($files as $file) {
                if (filemtime($file) < strtotime('-30 days')) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }
            
            $resultados[] = "✓ Eliminados $cleaned archivos de log antiguos";
        }
        
        View::renderJSON([
            'success' => count($errores) == 0,
            'resultados' => $resultados,
            'errores' => $errores
        ]);
    }
}