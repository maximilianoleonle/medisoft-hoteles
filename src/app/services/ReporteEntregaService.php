<?php

require_once __DIR__ . '/../models/ReporteLink.php';
require_once __DIR__ . '/ReporteEmailService.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReporteEntregaService {
    private $reporteLinkModel;
    private $reporteEmailService;

    public function __construct() {
        $this->reporteLinkModel = new ReporteLink();
        $this->reporteEmailService = new ReporteEmailService();
    }

    public function guardarTcpdfYDescargar($pdf, array $datos): void {
        $nombreArchivo = $this->nombreArchivo((string)($datos['archivo_nombre'] ?? 'reporte.pdf'));
        $contenido = (string) $pdf->Output($nombreArchivo, 'S');

        if ($contenido !== '') {
            $this->registrarYEnviar($contenido, $nombreArchivo, $datos);
        }

        $this->descargarContenido($contenido, $nombreArchivo);
    }

    public function registrarArchivoExistenteYEnviar(string $rutaPDF, array $datos): ?array {
        if (!is_file($rutaPDF) || strtolower(pathinfo($rutaPDF, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $contenido = file_get_contents($rutaPDF);
        if ($contenido === false || $contenido === '') {
            return null;
        }

        $nombreArchivo = $this->nombreArchivo((string)($datos['archivo_nombre'] ?? basename($rutaPDF)));
        return $this->registrarYEnviar($contenido, $nombreArchivo, $datos);
    }

    private function registrarYEnviar(string $contenido, string $nombreArchivo, array $datos): ?array {
        try {
            if (!$this->reporteLinkModel->tablaDisponible()) {
                return null;
            }

            $rutaRelativa = $this->guardarArchivo($contenido, $nombreArchivo);
            if ($rutaRelativa === null) {
                return null;
            }

            $hotelId = (int)($datos['hotel_id'] ?? obtenerHotelIdActualCompat());
            $registro = $this->reporteLinkModel->crearDesdeArchivo([
                'hotel_id' => $hotelId,
                'tipo_reporte' => (string)($datos['tipo_reporte'] ?? 'general'),
                'titulo' => (string)($datos['titulo'] ?? 'Reporte PDF'),
                'descripcion' => $datos['descripcion'] ?? null,
                'archivo_path' => $rutaRelativa,
                'archivo_nombre' => $nombreArchivo,
                'mime_type' => 'application/pdf',
                'tamano_bytes' => strlen($contenido),
                'parametros' => $datos['parametros'] ?? [],
                'creado_por' => function_exists('user_id') ? user_id() : null,
            ]);

            if (function_exists('hotel_report_email_enabled') && hotel_report_email_enabled($hotelId)) {
                $this->enviarCorreo($registro, $hotelId);
            }

            return $registro;
        } catch (Throwable $e) {
            error_log('No se pudo registrar link seguro de reporte: ' . $e->getMessage());
        }

        return null;
    }

    private function enviarCorreo(array $registro, int $hotelId): void {
        try {
            $token = (string)($registro['token'] ?? '');
            if ($token === '') {
                return;
            }

            $resultado = $this->reporteEmailService->enviarLink($registro, url('reportes/link/' . $token));
            $this->reporteLinkModel->registrarEnvioCorreo([
                'reporte_link_id' => (int)($registro['id'] ?? 0),
                'hotel_id' => $hotelId,
                'destinatarios' => implode(', ', $resultado['destinatarios'] ?? []),
                'asunto' => (string)($resultado['asunto'] ?? ''),
                'estado' => !empty($resultado['ok']) ? 'enviado' : 'fallido',
                'error_mensaje' => $resultado['error'] ?? null,
                'enviado_por' => function_exists('user_id') ? user_id() : null,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo enviar correo de reporte seguro: ' . $e->getMessage());
        }
    }

    private function guardarArchivo(string $contenido, string $nombreArchivo): ?string {
        $subDir = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $nombreSeguro = pathinfo($nombreArchivo, PATHINFO_FILENAME);
        $nombreSeguro = preg_replace('/[^A-Za-z0-9._-]+/', '_', $nombreSeguro);
        $nombreSeguro = trim((string)$nombreSeguro, '._-');
        if ($nombreSeguro === '') {
            $nombreSeguro = 'reporte';
        }

        $archivo = $nombreSeguro . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
        $destinos = [
            [
                'base' => STORAGE_PATH . DIRECTORY_SEPARATOR . 'reportes',
                'rel' => 'storage/reportes',
            ],
            [
                'base' => APP_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reportes',
                'rel' => 'app/uploads/reportes',
            ],
        ];

        foreach ($destinos as $destino) {
            $dir = $destino['base'] . DIRECTORY_SEPARATOR . $subDir;

            if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                continue;
            }

            if (!is_writable($dir)) {
                continue;
            }

            $rutaAbsoluta = $dir . DIRECTORY_SEPARATOR . $archivo;
            if (@file_put_contents($rutaAbsoluta, $contenido, LOCK_EX) !== false) {
                return $destino['rel'] . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $subDir) . '/' . $archivo;
            }
        }

        return null;
    }

    private function descargarContenido(string $contenido, string $nombreArchivo): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($contenido));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $contenido;
        exit;
    }

    private function nombreArchivo(string $nombre): string {
        $nombre = basename($nombre);
        $nombre = preg_replace('/[^A-Za-z0-9._ -]/', '_', $nombre);

        if ($nombre === '' || strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== 'pdf') {
            return 'reporte.pdf';
        }

        return $nombre;
    }
}
