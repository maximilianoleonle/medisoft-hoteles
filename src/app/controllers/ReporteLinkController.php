<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/ReporteLink.php';
require_once __DIR__ . '/../services/ReporteEmailService.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReporteLinkController extends Controller {
    private $reporteLinkModel;
    private $reporteEmailService;

    public function __construct($router = null) {
        parent::__construct($router);
        $this->reporteLinkModel = new ReporteLink();
        $this->reporteEmailService = new ReporteEmailService();
    }

    private function hotelIdActual(): int {
        return (int) obtenerHotelIdActualCompat();
    }

    private function requireReportesAccess(): void {
        $this->requireAuth();

        if (!can('reportes.view') && !can('reportes.all')) {
            set_mensaje('No tiene permisos para acceder a los links de reportes', 'error');
            $this->redirect('dashboard');
        }

        require_hotel_module('reportes');
    }

    public function historialAction(): void {
        $this->requireReportesAccess();

        $tablaDisponible = $this->reporteLinkModel->tablaDisponible();
        $filtros = [
            'estado' => $this->getQuery('estado', ''),
            'tipo_reporte' => $this->getQuery('tipo_reporte', ''),
        ];

        View::renderTemplate('reportes/links', [
            'title' => 'Links seguros de reportes - ' . current_hotel_display_name(),
            'links' => $tablaDisponible ? $this->reporteLinkModel->listarPorHotel($this->hotelIdActual(), $filtros) : [],
            'resumen' => $tablaDisponible ? $this->reporteLinkModel->resumenPorHotel($this->hotelIdActual()) : [],
            'tipos' => $tablaDisponible ? $this->reporteLinkModel->tiposPorHotel($this->hotelIdActual()) : [],
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
            'emailEnvioActivo' => function_exists('hotel_report_email_enabled') ? hotel_report_email_enabled($this->hotelIdActual()) : false,
            'emailDestinatariosConfigurados' => function_exists('hotel_report_email_recipients') ? count(hotel_report_email_recipients($this->hotelIdActual())) > 0 : false,
        ]);
    }

    public function descargarInternoAction($id): void {
        $this->requireReportesAccess();

        $registro = $this->reporteLinkModel->buscarPorIdHotel((int)$id, $this->hotelIdActual());
        if (!$registro) {
            http_response_code(404);
            set_mensaje('El reporte solicitado no existe para este hotel.', 'error');
            $this->redirect('reportes/links');
        }

        $this->servirPdf($registro, false);
    }

    public function revocarAction($id): void {
        $this->requireReportesAccess();
        $this->validateCSRF();

        $ok = $this->reporteLinkModel->revocar((int)$id, $this->hotelIdActual());
        set_mensaje($ok ? 'Link seguro revocado correctamente.' : 'No se pudo revocar el link seguro.', $ok ? 'success' : 'error');
        $this->redirect('reportes/links');
    }

    public function enviarCorreoAction($id): void {
        $this->requireReportesAccess();
        $this->validateCSRF();

        $hotelId = $this->hotelIdActual();
        $registro = $this->reporteLinkModel->renovarTokenParaEnvio((int)$id, $hotelId);

        if (!$registro) {
            set_mensaje('No se pudo preparar el link para correo. Verifica que no este revocado.', 'error');
            $this->redirect('reportes/links');
        }

        $token = (string)($registro['token'] ?? '');
        $linkPublico = url('reportes/link/' . $token);
        $resultado = $this->reporteEmailService->enviarLink($registro, $linkPublico);
        $this->reporteLinkModel->registrarEnvioCorreo([
            'reporte_link_id' => (int)$registro['id'],
            'hotel_id' => $hotelId,
            'destinatarios' => implode(', ', $resultado['destinatarios'] ?? []),
            'asunto' => (string)($resultado['asunto'] ?? ''),
            'estado' => !empty($resultado['ok']) ? 'enviado' : 'fallido',
            'error_mensaje' => $resultado['error'] ?? null,
            'enviado_por' => function_exists('user_id') ? user_id() : null,
        ]);

        if (!empty($resultado['ok'])) {
            set_mensaje('Correo enviado con link seguro. El PDF no se adjunto.', 'success');
        } else {
            set_mensaje('No se pudo enviar el correo: ' . (string)($resultado['error'] ?? 'error desconocido'), 'error');
        }

        $this->redirect('reportes/links');
    }

    public function descargarPublicoAction($token): void {
        $registro = $this->reporteLinkModel->buscarActivoPorToken((string)$token);
        if (!$registro) {
            $this->renderPublicMessage(410, 'Link no disponible', 'Este link ya expiro, fue revocado o no existe.');
        }

        if (function_exists('hotel_report_links_public_enabled') && !hotel_report_links_public_enabled((int)($registro['hotel_id'] ?? 0))) {
            $this->renderPublicMessage(410, 'Links desactivados', 'El hotel desactivo temporalmente el acceso publico a reportes.');
        }

        $this->reporteLinkModel->registrarAcceso((int)$registro['id']);
        $this->servirPdf($registro, true);
    }

    private function servirPdf(array $registro, bool $publico): void {
        $ruta = $this->resolverRutaReporte((string)($registro['archivo_path'] ?? ''));

        if (!$ruta) {
            if ($publico) {
                $this->renderPublicMessage(404, 'PDF no disponible', 'El archivo del reporte ya no esta disponible en el servidor.');
            }

            set_mensaje('El archivo del reporte no existe o esta fuera del directorio permitido.', 'error');
            $this->redirect('reportes/links');
        }

        $nombre = $this->nombreDescarga((string)($registro['archivo_nombre'] ?? 'reporte.pdf'));

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($ruta);
        exit;
    }

    private function resolverRutaReporte(string $archivoPath): ?string {
        $archivoPath = trim($archivoPath);
        if ($archivoPath === '' || strpos($archivoPath, '..') !== false) {
            return null;
        }

        $normalizado = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $archivoPath);
        $esAbsoluto = preg_match('/^[A-Za-z]:\\\\/', $normalizado) || strpos($normalizado, DIRECTORY_SEPARATOR) === 0;
        $ruta = $esAbsoluto ? $normalizado : ROOT_PATH . DIRECTORY_SEPARATOR . ltrim($normalizado, DIRECTORY_SEPARATOR);
        $real = realpath($ruta);

        if (!$real || !is_file($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $raicesPermitidas = array_filter([
            realpath(STORAGE_PATH . DIRECTORY_SEPARATOR . 'reportes'),
            realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'reportes'),
            realpath(APP_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reportes'),
            realpath(ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reportes'),
        ]);

        foreach ($raicesPermitidas as $raiz) {
            if (strpos($real, rtrim($raiz, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) === 0) {
                return $real;
            }
        }

        return null;
    }

    private function nombreDescarga(string $nombre): string {
        $nombre = basename($nombre);
        $nombre = preg_replace('/[^A-Za-z0-9._ -]/', '_', $nombre);

        if ($nombre === '' || strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== 'pdf') {
            return 'reporte.pdf';
        }

        return $nombre;
    }

    private function renderPublicMessage(int $status, string $titulo, string $mensaje): void {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        $tituloSeguro = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
        $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . $tituloSeguro . '</title>';
        echo '<style>body{margin:0;font-family:Inter,Arial,sans-serif;background:#f6f7fb;color:#172033;display:grid;place-items:center;min-height:100vh}main{width:min(520px,calc(100% - 32px));background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:28px;box-shadow:0 24px 70px rgba(15,23,42,.12)}h1{font-size:24px;margin:0 0 10px}p{margin:0;color:#667085;line-height:1.55}</style>';
        echo '</head><body><main><h1>' . $tituloSeguro . '</h1><p>' . $mensajeSeguro . '</p></main></body></html>';
        exit;
    }
}
