<?php
/**
 * Modelo de evidencia fotografica de mantenimientos (bloque mantenimiento_plus).
 *
 * Calca el patron de subida de uploads/habitaciones (mismas validaciones de
 * tipo/tamano y optimizacion GD), con nombres prefijados por hotel y limite
 * de fotos por momento (reporte|resuelto).
 */

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class MantenimientoFoto extends Model {
    protected $table = 'mantenimiento_fotos';
    protected $fillable = ['hotel_id', 'mantenimiento_id', 'ruta', 'momento', 'subido_por'];
    protected $timestamps = false;

    const MAX_FOTOS_POR_MOMENTO = 3;
    const MAX_BYTES = 5242880; // 5MB, igual que imagenes de habitacion
    const MOMENTOS = ['reporte', 'resuelto'];

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    /**
     * Fotos de un mantenimiento agrupadas por momento.
     * Retorna ['reporte' => [...], 'resuelto' => [...]].
     */
    public function porMantenimiento(int $mantenimientoId, ?int $hotelId = null): array {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $grupos = ['reporte' => [], 'resuelto' => []];

        if ($mantenimientoId <= 0 || $hotelId <= 0) {
            return $grupos;
        }

        $stmt = $this->db->query(
            "SELECT id, ruta, momento, subido_por, created_at
             FROM {$this->table}
             WHERE hotel_id = ? AND mantenimiento_id = ?
             ORDER BY momento ASC, id ASC",
            [$hotelId, $mantenimientoId]
        );

        foreach (($stmt ? $stmt->fetchAll() : []) as $foto) {
            $momento = in_array($foto['momento'], self::MOMENTOS, true) ? $foto['momento'] : 'reporte';
            $grupos[$momento][] = $foto;
        }

        return $grupos;
    }

    public function contarPorMomento(int $mantenimientoId, string $momento, ?int $hotelId = null): int {
        $hotelId = $hotelId ?: (int)$this->hotelIdActual();
        $stmt = $this->db->query(
            "SELECT COUNT(*) AS n FROM {$this->table}
             WHERE hotel_id = ? AND mantenimiento_id = ? AND momento = ?",
            [$hotelId, $mantenimientoId, $momento]
        );
        $row = $stmt ? $stmt->fetch() : null;
        return (int)($row['n'] ?? 0);
    }

    /**
     * Procesa y guarda un lote de archivos ($_FILES formato multiple) para un
     * mantenimiento. Respeta el tope por momento; devuelve rutas guardadas y
     * errores legibles (nunca lanza por un archivo invalido).
     */
    public function guardarLoteDesdeUpload(int $hotelId, int $mantenimientoId, string $momento, array $files, ?int $usuarioId = null): array {
        $guardadas = [];
        $errores = [];

        if ($hotelId <= 0 || $mantenimientoId <= 0 || !in_array($momento, self::MOMENTOS, true)) {
            return ['fotos' => $guardadas, 'errores' => ['Datos de evidencia no validos']];
        }

        if (empty($files['name']) || !is_array($files['name'])) {
            return ['fotos' => $guardadas, 'errores' => $errores];
        }

        $disponibles = self::MAX_FOTOS_POR_MOMENTO - $this->contarPorMomento($mantenimientoId, $momento, $hotelId);
        if ($disponibles <= 0) {
            return ['fotos' => $guardadas, 'errores' => ['Ya se alcanzo el maximo de ' . self::MAX_FOTOS_POR_MOMENTO . ' fotos para este momento']];
        }

        $total = count($files['name']);
        for ($i = 0; $i < $total; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (count($guardadas) >= $disponibles) {
                $errores[] = 'Solo se admiten ' . self::MAX_FOTOS_POR_MOMENTO . ' fotos por momento; se omitieron las extra';
                break;
            }

            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir ' . (string)($files['name'][$i] ?? 'archivo');
                continue;
            }

            $archivo = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
            ];

            $resultado = $this->procesarFoto($archivo, $hotelId, $mantenimientoId, $momento);
            if (!$resultado['success']) {
                $errores[] = $resultado['error'];
                continue;
            }

            $creada = $this->create([
                'hotel_id' => $hotelId,
                'mantenimiento_id' => $mantenimientoId,
                'ruta' => $resultado['path'],
                'momento' => $momento,
                'subido_por' => $usuarioId,
            ]);

            if ($creada) {
                $guardadas[] = $resultado['path'];
            } else {
                @unlink(PUBLIC_PATH . '/' . $resultado['path']);
                $errores[] = 'No se pudo registrar la foto ' . (string)($files['name'][$i] ?? '');
            }
        }

        return ['fotos' => $guardadas, 'errores' => $errores];
    }

    /**
     * Validaciones + guardado fisico, calcadas de procesarImagenHabitacion.
     */
    private function procesarFoto(array $archivo, int $hotelId, int $mantenimientoId, string $momento): array {
        if (($archivo['size'] ?? 0) > self::MAX_BYTES) {
            return ['success' => false, 'error' => 'La foto no puede exceder 5MB'];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes, true)) {
            return ['success' => false, 'error' => 'Solo se permiten imagenes JPG, PNG, GIF o WebP'];
        }

        if (!getimagesize($archivo['tmp_name'])) {
            return ['success' => false, 'error' => 'El archivo no es una imagen valida'];
        }

        $uploadDir = ensure_upload_directory('mantenimientos');
        if (!$uploadDir) {
            return ['success' => false, 'error' => 'No se pudo crear el directorio de evidencias'];
        }

        $extension = get_extension_by_mime($mimeType);
        $nombreArchivo = 'hotel' . $hotelId . '_mant' . $mantenimientoId . '_' . $momento . '_' . uniqid() . '.' . $extension;
        $rutaCompleta = $uploadDir . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            return ['success' => false, 'error' => 'Error al guardar la foto'];
        }

        $this->optimizarImagen($rutaCompleta, $mimeType);

        return ['success' => true, 'path' => 'uploads/mantenimientos/' . $nombreArchivo];
    }

    /**
     * Compresion/redimension identica a la de imagenes de habitacion.
     */
    private function optimizarImagen(string $rutaArchivo, string $mimeType): bool {
        $maxWidth = 1920;
        $maxHeight = 1080;
        $quality = 85;

        $imageInfo = getimagesize($rutaArchivo);
        if (!$imageInfo) return false;

        list($width, $height) = $imageInfo;

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return true;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);

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

        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType == 'image/png' || $mimeType == 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

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

        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return true;
    }
}
