<?php
/**
 * Controlador de Reportes
 * Sistema hotelero
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../services/ReporteEntregaService.php';
require_once __DIR__ . '/../services/ReporteGerencialDiarioService.php';

class ReportesController extends Controller {
    private $reporteModel;
    private $reservacionModel;
    private $cajaModel;
    private $huespedModel;
    private $habitacionModel;

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    private function entregarReportePdf($pdf, array $datos): void {
        $service = new ReporteEntregaService();
        $service->guardarTcpdfYDescargar($pdf, $datos);
    }

    private function reportePdfBranding(): array {
        $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
        if (!is_array($branding)) {
            $branding = [];
        }

        $primary = $this->reportePdfHex($branding['color_primary'] ?? null, '#1B2746');
        $secondary = $this->reportePdfHex($branding['color_secondary'] ?? null, '#0F172A');
        $accent = $this->reportePdfHex($branding['color_accent'] ?? null, '#BD9441');

        return [
            'hotel' => function_exists('current_hotel_display_name')
                ? current_hotel_display_name('Medisoft Hoteles')
                : 'Medisoft Hoteles',
            'logo' => $this->reportePdfLogoPath($branding),
            'primary' => $primary,
            'secondary' => $secondary,
            'accent' => $accent,
            'primary_dark' => $this->reportePdfMix($primary, '#020617', 0.84),
            'secondary_dark' => $this->reportePdfMix($secondary, '#020617', 0.84),
            'accent_dark' => $this->reportePdfMix($accent, '#020617', 0.84),
            'primary_soft' => $this->reportePdfMix($primary, '#FFFFFF', 0.10),
            'secondary_soft' => $this->reportePdfMix($secondary, '#FFFFFF', 0.08),
            'accent_soft' => $this->reportePdfMix($accent, '#FFFFFF', 0.16),
            'line' => $this->reportePdfMix($accent, '#E7DEC9', 0.30),
            'text' => '#172033',
            'muted' => '#667085',
        ];
    }

    private function reportePdfHex($color, string $fallback): string {
        if (function_exists('hotel_branding_hex')) {
            return hotel_branding_hex($color, $fallback);
        }

        $color = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtoupper($color);
        }

        if (preg_match('/^#[0-9a-fA-F]{3}$/', $color)) {
            return strtoupper('#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3]);
        }

        return strtoupper($fallback);
    }

    private function reportePdfRgb(string $hex): array {
        $hex = ltrim($this->reportePdfHex($hex, '#000000'), '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function reportePdfMix(string $hex, string $target, float $ratio): string {
        $ratio = max(0, min(1, $ratio));
        $a = $this->reportePdfRgb($hex);
        $b = $this->reportePdfRgb($target);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($a[0] * $ratio + $b[0] * (1 - $ratio)),
            (int) round($a[1] * $ratio + $b[1] * (1 - $ratio)),
            (int) round($a[2] * $ratio + $b[2] * (1 - $ratio))
        );
    }

    private function reportePdfTextColor(string $hex): string {
        $rgb = $this->reportePdfRgb($hex);
        $luminance = (($rgb[0] * 299) + ($rgb[1] * 587) + ($rgb[2] * 114)) / 1000;
        return $luminance > 155 ? '#111827' : '#FFFFFF';
    }

    private function reportePdfLogoPath(array $branding) {
        $candidates = [
            $branding['logo_url'] ?? null,
            function_exists('hotel_branding_default_logo_path') ? hotel_branding_default_logo_path() : 'img/logo.png',
            'images/logo-hotel.png',
        ];

        $publicRoot = defined('PUBLIC_PATH') ? realpath(PUBLIC_PATH) : null;
        if (!$publicRoot) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $path = trim((string) $candidate);
            if ($path === '' || preg_match('/[\x00-\x1F<>"\']/', $path)) {
                continue;
            }

            $urlPath = parse_url($path, PHP_URL_PATH);
            if (!$urlPath) {
                continue;
            }

            $cleanPath = str_replace('\\', '/', ltrim($urlPath, '/'));
            $allowed = false;
            foreach (['uploads/branding/', 'uploads/', 'img/', 'images/'] as $prefix) {
                if (strpos($cleanPath, $prefix) === 0) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                continue;
            }

            $realPath = realpath(PUBLIC_PATH . '/' . $cleanPath);
            $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
            if (!$realPath || strpos($realPath, $publicRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
                continue;
            }

            if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
                continue;
            }

            return $realPath;
        }

        return null;
    }
    
    public function __construct($router = null) {
        parent::__construct($router);
        
        // Verificar autenticación
        if (!is_authenticated()) {
            redirect(function_exists('login_path_for_current_context')
                ? login_path_for_current_context($_SERVER['REQUEST_URI'] ?? null)
                : 'login');
            exit;
        }
        
        // Solo gerentes y administradores tienen acceso
        if (!can('reportes.view') && !can('reportes.all')) {
            set_mensaje('No tiene permisos para acceder a los reportes', 'error');
            redirect('dashboard');
            exit;
        }

        require_hotel_module('reportes');
        
        // Incluir modelos necesarios
        require_once __DIR__ . '/../models/Reporte.php';
        require_once __DIR__ . '/../models/Reservacion.php';
        require_once __DIR__ . '/../models/Caja.php';
        require_once __DIR__ . '/../models/Huesped.php';
        require_once __DIR__ . '/../models/Habitacion.php';
        
        $this->reporteModel = new Reporte();
        $this->reservacionModel = new Reservacion();
        $this->cajaModel = new Caja();
        $this->huespedModel = new Huesped();
        $this->habitacionModel = new Habitacion();
    }
    
    /**
     * Vista principal de reportes
     */
    public function indexAction() {
        View::renderTemplate('reportes/index', [
            'title' => 'Reportes - ' . current_hotel_display_name()
        ]);
    }
    
    // Agregar esta acción al ReportesController.php
    public function gerencialDiarioAction() {
        $this->requireAuth();

        $fecha = trim((string)$this->getQuery('fecha', date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $servicio = new ReporteGerencialDiarioService();
        $reporte = $servicio->generar((int)$this->hotelIdActual(), $fecha);
        $fecha = (string)($reporte['fecha'] ?? $fecha);
        $this->archivarNotificacionReporteGerencialVisto($fecha);

        View::renderTemplate('reportes/gerencial-diario', [
            'title' => 'Reporte gerencial diario - ' . current_hotel_display_name(),
            'reporte' => $reporte,
            'fecha' => $fecha,
        ]);
    }

    public function gerencialDiarioPdfAction() {
        $this->requireAuth();

        $fecha = trim((string)$this->getQuery('fecha', date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $servicio = new ReporteGerencialDiarioService();
        $reporte = $servicio->generar((int)$this->hotelIdActual(), $fecha);
        $fecha = (string)($reporte['fecha'] ?? $fecha);
        $this->archivarNotificacionReporteGerencialVisto($fecha);

        $pdf = $this->crearReporteGerencialDiarioPdf($reporte);
        $this->entregarReportePdf($pdf, [
            'hotel_id' => (int)$this->hotelIdActual(),
            'tipo_reporte' => 'gerencial-diario',
            'titulo' => 'Reporte gerencial diario ' . date('d/m/Y', strtotime($fecha)),
            'descripcion' => 'Resumen ejecutivo diario de finanzas, ocupacion, agenda, caja y pendientes.',
            'archivo_nombre' => function_exists('hotel_export_filename')
                ? hotel_export_filename('Reporte_Gerencial_Diario_' . str_replace('-', '', $fecha), 'pdf', false)
                : 'Reporte_Gerencial_Diario_' . str_replace('-', '', $fecha) . '.pdf',
            'parametros' => [
                'fecha' => $fecha,
            ],
        ]);
    }

    private function archivarNotificacionReporteGerencialVisto(string $fecha): void {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return;
        }

        $hotelId = (int)$this->hotelIdActual();
        if ($hotelId <= 0) {
            return;
        }

        try {
            $dedupeKey = 'regla.reporte_gerencial_diario.' . str_replace('-', '', $fecha);
            $db = Database::getInstance();
            $db->query(
                "UPDATE notificaciones
                 SET estado = 'descartada',
                     leida_en = COALESCE(leida_en, NOW()),
                     descartada_en = COALESCE(descartada_en, NOW()),
                     updated_at = NOW()
                 WHERE hotel_id = ?
                   AND tipo = 'regla_reporte_gerencial_diario'
                   AND dedupe_key = ?
                   AND estado IN ('nueva', 'leida')",
                [$hotelId, $dedupeKey]
            );
        } catch (Throwable $e) {
            error_log('No se pudo archivar notificacion de reporte gerencial visto: ' . $e->getMessage());
        }
    }

    private function crearReporteGerencialDiarioPdf(array $reporte) {
        require_once __DIR__ . '/../views/reportes/ReportePDF.php';

        $fecha = (string)($reporte['fecha'] ?? date('Y-m-d'));
        $brand = $this->reportePdfBranding();
        $primaryRgb = $this->reportePdfRgb($brand['primary']);
        $accentRgb = $this->reportePdfRgb($brand['accent']);
        $primaryText = $this->reportePdfRgb($this->reportePdfTextColor($brand['primary']));

        $pdf = new ReportePDF('Reporte gerencial diario', date('d/m/Y', strtotime($fecha)));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->Rect(0, 0, 210, 34, 'F');
        $pdf->SetTextColor($primaryText[0], $primaryText[1], $primaryText[2]);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->SetY(9);
        $pdf->Cell(0, 8, $brand['hotel'], 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 6, 'Reporte gerencial diario - ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'C');
        $pdf->SetTextColor(23, 32, 51);
        $pdf->SetY(42);

        $finanzas = is_array($reporte['finanzas'] ?? null) ? $reporte['finanzas'] : [];
        $habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
        $agenda = is_array($reporte['agenda'] ?? null) ? $reporte['agenda'] : [];
        $caja = is_array($reporte['caja'] ?? null) ? $reporte['caja'] : [];
        $facturacion = is_array($reporte['facturacion'] ?? null) ? $reporte['facturacion'] : [];
        $inventario = is_array($reporte['inventario'] ?? null) ? $reporte['inventario'] : [];
        $riesgos = is_array($reporte['riesgos'] ?? null) ? $reporte['riesgos'] : [];
        $entradas = is_array($agenda['entradas'] ?? null) ? $agenda['entradas'] : [];
        $salidas = is_array($agenda['salidas'] ?? null) ? $agenda['salidas'] : [];
        $metodos = is_array($finanzas['metodos'] ?? null) ? $finanzas['metodos'] : [];

        $ingresos = (float)($finanzas['ingresos'] ?? 0);
        $gastos = (float)($finanzas['gastos'] ?? 0);
        $balance = (float)($finanzas['balance'] ?? 0);
        $ocupacion = (float)($habitaciones['ocupacion_pct'] ?? 0);
        $riesgosTotal = (int)($riesgos['total'] ?? 0);

        $html = '
        <table cellpadding="6" cellspacing="4" border="0">
            <tr>
                ' . $this->reporteGerencialPdfKpi('Ingresos', $this->reporteGerencialPdfMoney($ingresos), (int)($finanzas['movimientos'] ?? 0) . ' movimientos', $brand['primary_soft'], $brand['primary_dark']) . '
                ' . $this->reporteGerencialPdfKpi('Balance', $this->reporteGerencialPdfMoney($balance), 'Gastos ' . $this->reporteGerencialPdfMoney($gastos), $brand['accent_soft'], $brand['accent_dark']) . '
                ' . $this->reporteGerencialPdfKpi('Ocupacion', number_format($ocupacion, 1) . '%', (int)($habitaciones['ocupadas'] ?? 0) . ' de ' . (int)($habitaciones['total'] ?? 0) . ' habitaciones', '#EEF2F7', '#111827') . '
                ' . $this->reporteGerencialPdfKpi('Pendientes', (string)$riesgosTotal, (int)($facturacion['pendientes'] ?? 0) . ' facturacion / ' . (int)($inventario['bajo_minimo'] ?? 0) . ' inventario', '#FEF3C7', '#92400E') . '
            </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, '  Finanzas del dia', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);

        $html = '
        <table border="1" cellpadding="5" cellspacing="0" style="font-size:9.5pt;">
            <thead>
                <tr style="background-color:#F3F4F6;font-weight:bold;color:#111827;">
                    <th width="25%">Metodo</th>
                    <th width="25%" align="right">Ingresos</th>
                    <th width="25%" align="right">Gastos</th>
                    <th width="25%" align="center">Movimientos</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($metodos as $metodo => $valores) {
            $html .= '
                <tr>
                    <td>' . $this->reporteGerencialPdfText(ucfirst((string)$metodo)) . '</td>
                    <td align="right">' . $this->reporteGerencialPdfMoney($valores['ingresos'] ?? 0) . '</td>
                    <td align="right">' . $this->reporteGerencialPdfMoney($valores['gastos'] ?? 0) . '</td>
                    <td align="center">' . (int)($valores['movimientos'] ?? 0) . '</td>
                </tr>';
        }

        $html .= '
                <tr style="background-color:#F9FAFB;font-weight:bold;">
                    <td>Total</td>
                    <td align="right">' . $this->reporteGerencialPdfMoney($ingresos) . '</td>
                    <td align="right">' . $this->reporteGerencialPdfMoney($gastos) . '</td>
                    <td align="center">' . (int)($finanzas['movimientos'] ?? 0) . '</td>
                </tr>
            </tbody>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');

        $html = '
        <table cellpadding="5" cellspacing="6" border="0">
            <tr>
                <td width="50%">
                    <h3 style="color:' . $brand['primary_dark'] . ';font-size:12pt;">Habitaciones</h3>
                    ' . $this->reporteGerencialPdfMiniTable([
                        ['Ocupadas', (int)($habitaciones['ocupadas'] ?? 0)],
                        ['Disponibles reales', (int)($habitaciones['disponibles_reales'] ?? 0)],
                        ['Por llegar', (int)($habitaciones['por_llegar'] ?? 0)],
                        ['Limpieza', (int)($habitaciones['limpieza'] ?? 0)],
                        ['Mantenimiento', (int)($habitaciones['mantenimiento'] ?? 0)],
                    ]) . '
                </td>
                <td width="50%">
                    <h3 style="color:' . $brand['primary_dark'] . ';font-size:12pt;">Agenda y caja</h3>
                    ' . $this->reporteGerencialPdfMiniTable([
                        ['Entradas del dia', (int)($entradas['total'] ?? 0)],
                        ['Entradas pendientes', (int)($entradas['pendientes'] ?? 0)],
                        ['Salidas del dia', (int)($salidas['total'] ?? 0)],
                        ['Salidas pendientes', (int)($salidas['pendientes'] ?? 0)],
                        ['Cajas abiertas', (int)($caja['abiertas'] ?? 0)],
                        ['Cortes cerrados hoy', (int)($caja['cerradas'] ?? 0)],
                    ]) . '
                </td>
            </tr>
        </table>';
        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->SetTextColor($primaryText[0], $primaryText[1], $primaryText[2]);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, '  Pendientes gerenciales', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);

        $html = $this->reporteGerencialPdfMiniTable([
            ['Check-ins vencidos', (int)($riesgos['checkins_vencidos'] ?? 0)],
            ['Check-outs vencidos', (int)($riesgos['checkouts_vencidos'] ?? 0)],
            ['Facturas pendientes', (int)($riesgos['facturas_pendientes'] ?? 0)],
            ['Habitaciones en limpieza', (int)($riesgos['habitaciones_limpieza'] ?? 0)],
            ['Habitaciones en mantenimiento', (int)($riesgos['habitaciones_mantenimiento'] ?? 0)],
            ['Inventario bajo', (int)($riesgos['inventario_bajo'] ?? 0)],
        ]);
        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->Ln(6);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(110, 118, 137);
        $pdf->MultiCell(0, 5, 'Generado desde Medisoft Hoteles el ' . date('d/m/Y H:i') . '. Este PDF se guarda automaticamente como link seguro en el historial de reportes.', 0, 'C');

        return $pdf;
    }

    private function reporteGerencialPdfKpi(string $label, string $value, string $hint, string $bg, string $color): string {
        return '
            <td width="25%" style="background-color:' . $bg . ';border:1px solid #E5E7EB;text-align:center;">
                <div style="font-size:8pt;color:#667085;font-weight:bold;">' . $this->reporteGerencialPdfText($label) . '</div>
                <div style="font-size:17pt;color:' . $color . ';font-weight:bold;">' . $this->reporteGerencialPdfText($value) . '</div>
                <div style="font-size:8pt;color:#667085;">' . $this->reporteGerencialPdfText($hint) . '</div>
            </td>';
    }

    private function reporteGerencialPdfMiniTable(array $rows): string {
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="font-size:9.5pt;">';
        foreach ($rows as $i => $row) {
            $bg = $i % 2 === 0 ? '#FFFFFF' : '#F9FAFB';
            $html .= '
                <tr style="background-color:' . $bg . ';">
                    <td width="70%">' . $this->reporteGerencialPdfText((string)($row[0] ?? '')) . '</td>
                    <td width="30%" align="right"><b>' . $this->reporteGerencialPdfText((string)($row[1] ?? '0')) . '</b></td>
                </tr>';
        }
        return $html . '</table>';
    }

    private function reporteGerencialPdfMoney($value): string {
        return '$' . number_format((float)$value, 2);
    }

    private function reporteGerencialPdfText(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

public function ingresosGastosAction() {
    $this->requireAuth();
    
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    
    // Validar fechas
    if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
        set_mensaje('La fecha de inicio debe ser menor o igual a la fecha fin', 'error');
        $this->redirect('reportes');
        return;
    }
    
    $reporteModel = new Reporte();
    
    // Obtener datos principales
    $datos = $reporteModel->getIngresosVsGastos($fecha_inicio, $fecha_fin);
    $resumenDiario = $reporteModel->getResumenDiario($fecha_inicio, $fecha_fin);
    
    // Calcular totales
    $totales = [
        'ingresos' => array_sum(array_column($datos['ingresos'], 'total')),
        'gastos' => array_sum(array_column($datos['gastos'], 'total')),
        'utilidad' => 0
    ];
    
    $totales['utilidad'] = $totales['ingresos'] - $totales['gastos'];
    
    // Obtener datos por método de pago
    $metodosPago = $this->getMetodosPagoData($fecha_inicio, $fecha_fin);
    // Obtener lista de usuarios para el modal
    $usuarios = $this->getUsuariosActivos();
    
    $data = [
        'titulo' => 'Análisis de Ingresos vs Gastos',
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'datos' => $datos,
        'totales' => $totales,
        'resumenDiario' => $resumenDiario,
        'metodosPago' => $metodosPago,
        'usuarios' => $usuarios
    ];
    
    View::render('reportes/ingresos-gastos', $data);
}

// Método para obtener datos por método de pago
private function getMetodosPagoData($fecha_inicio, $fecha_fin) {
    $db = Database::getInstance();
    $hotel_id = $this->hotelIdActual();
    
    $sql = "SELECT 
                metodo_pago,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
                SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as total_gastos,
                SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE -monto END) as balance
            FROM movimientos_caja 
            WHERE hotel_id = ?
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY metodo_pago";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $results = $stmt->fetchAll();
    
    // Estructurar los datos por método de pago
    $metodosPago = [
        'efectivo' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
        'tarjeta' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
        'transferencia' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0]
    ];
    
    foreach ($results as $row) {
        $metodo = $row['metodo_pago'];
        if (isset($metodosPago[$metodo])) {
            $metodosPago[$metodo] = [
                'ingresos' => floatval($row['total_ingresos']),
                'gastos' => floatval($row['total_gastos']),
                'balance' => floatval($row['balance'])
            ];
        }
    }
    
    return $metodosPago;
}

// Método para obtener usuarios activos
private function getUsuariosActivos() {
    $db = Database::getInstance();
    $sql = "SELECT id, nombre_completo FROM usuarios WHERE activo = 1 ORDER BY nombre_completo";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

// Método modificado para exportarPdfAction - agregar nuevos tipos
public function exportarPdfAction() {
    $this->requireAuth();
    
    $tipo = $this->getQuery('tipo');
    
    switch ($tipo) {
        case 'ingresos-gastos':
            $this->exportarIngresosGastosPdf();
            break;
            
        case 'ingresos-gastos-usuario':
            $this->exportarIngresosGastosUsuarioPdf();
            break;
            
        case 'ingresos-totales':
            $this->exportarIngresosTotalesPdf();
            break;
            
        case 'procedencia':
            $this->exportarProcedenciaPdf();
            break;
            
        case 'habitaciones-rentables':
            $this->exportarHabitacionesRentablesPdf();
            break;
            
        default:
            set_mensaje('Tipo de reporte no válido', 'error');
            $this->redirect('reportes');
    }
}

// Nuevo método para exportar reporte de ingresos y gastos por usuario
private function exportarIngresosGastosUsuarioPdf() {
    require_once __DIR__ . '/../views/reportes/ReportePDF.php';
    
    // Función auxiliar para obtener el nombre del día
    $obtenerNombreDia = function($fecha) {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $dias[date('w', strtotime($fecha))];
    };
    
    $usuario_id = $this->getQuery('usuario_id');
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    $hotel_id = $this->hotelIdActual();
    
    if (!$usuario_id) {
        set_mensaje('Debe seleccionar un usuario', 'error');
        $this->redirect('reportes/ingresos-gastos');
        return;
    }
    
    $db = Database::getInstance();
    
    // Obtener información del usuario
    $sqlUsuario = "SELECT nombre_completo FROM usuarios WHERE id = ?";
    $stmtUsuario = $db->query($sqlUsuario, [$usuario_id]);
    $usuario = $stmtUsuario->fetch();
    
    if (!$usuario) {
        set_mensaje('Usuario no encontrado', 'error');
        $this->redirect('reportes/ingresos-gastos');
        return;
    }
    
    // Obtener todos los movimientos CON información de habitaciones
    $sql = "SELECT 
                mc.*,
                mc.categoria as categoria_nombre,
                r.id as reservacion_id_real,
                GROUP_CONCAT(DISTINCT h.numero ORDER BY h.numero) as habitaciones_numeros
            FROM movimientos_caja mc
            LEFT JOIN reservaciones r ON mc.reservacion_id = r.id
                AND r.hotel_id = mc.hotel_id
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                AND rh.hotel_id = r.hotel_id
            LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
                AND h.hotel_id = rh.hotel_id
            WHERE mc.usuario_id = ? 
            AND mc.hotel_id = ?
            AND DATE(mc.created_at) BETWEEN ? AND ?
            GROUP BY mc.id
            ORDER BY mc.created_at DESC";
    
    $stmt = $db->query($sql, [$usuario_id, $hotel_id, $fecha_inicio, $fecha_fin]);
    $movimientos = $stmt->fetchAll();
    
    // Procesar movimientos
    $habitacionesPorFecha = [];
    $movimientosHospedaje = [];
    $otrosMovimientos = [];
    $totales = ['ingreso' => 0, 'gasto' => 0];
    $totalesPorMetodo = [
        'efectivo' => ['ingreso' => 0, 'gasto' => 0],
        'tarjeta' => ['ingreso' => 0, 'gasto' => 0],
        'transferencia' => ['ingreso' => 0, 'gasto' => 0]
    ];
    
    foreach ($movimientos as $mov) {
        $tipo = $mov['tipo'];
        $monto = floatval($mov['monto']);
        $metodo = $mov['metodo_pago'];
        
        // Limpiar descripción para quitar referencias a IDs
        if (isset($mov['descripcion'])) {
            $mov['descripcion'] = preg_replace('/Reservación\s*#\d+\s*-?\s*/', '', $mov['descripcion']);
            $mov['descripcion'] = trim($mov['descripcion']);
        }
        
        // Sumar a totales
        $totales[$tipo] += $monto;
        if (isset($totalesPorMetodo[$metodo])) {
            $totalesPorMetodo[$metodo][$tipo] += $monto;
        }
        
        // Separar por categoría
        if ($mov['categoria_nombre'] == 'Hospedaje' || $mov['categoria'] == 'Hospedaje') {
            $movimientosHospedaje[] = $mov;
            
            // Si es un ingreso de hospedaje, extraer información
            if ($tipo == 'ingreso') {
                $fecha = date('Y-m-d', strtotime($mov['created_at']));
                
                // Obtener habitación
                $habitacion = 'Sin especificar';
                
                // Primero intentar con las habitaciones de la consulta
                if (!empty($mov['habitaciones_numeros'])) {
                    $habitacion = $mov['habitaciones_numeros'];
                } else {
                    // Si no hay habitaciones en la BD, buscar en la descripción
                    $descripcion = $mov['descripcion'] ?? '';
                    
                    // Patrones más amplios para buscar habitaciones
                    if (preg_match('/(?:Hab(?:itación)?\.?\s*|#\s*|N(?:o|°)\.?\s*)(\d{1,3})/i', $descripcion, $matches)) {
                        $habitacion = $matches[1];
                    } elseif (preg_match('/\b(\d{3})\b/', $descripcion, $matches)) {
                        $habitacion = $matches[1];
                    } elseif (preg_match('/(\d{1,3})\s*(?:-|,)/', $descripcion, $matches)) {
                        $habitacion = $matches[1];
                    }
                }
                
                if (!isset($habitacionesPorFecha[$fecha])) {
                    $habitacionesPorFecha[$fecha] = [];
                }
                
                $habitacionesPorFecha[$fecha][] = [
                    'habitacion' => $habitacion,
                    'descripcion' => $mov['descripcion'] ?? 'Hospedaje',
                    'monto' => $monto,
                    'metodo' => $metodo
                ];
            }
        } else {
            $otrosMovimientos[] = $mov;
        }
    }
    
    // IMPORTANTE: Ordenar las fechas de más antigua a más reciente
    ksort($habitacionesPorFecha);
    
    $utilidad = $totales['ingreso'] - $totales['gasto'];
    $brand = $this->reportePdfBranding();
    $primaryRgb = $this->reportePdfRgb($brand['primary']);
    $secondaryRgb = $this->reportePdfRgb($brand['secondary']);
    $accentRgb = $this->reportePdfRgb($brand['accent']);
    $primaryTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['primary']));
    $accentTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['accent']));
    $utilidadColor = $utilidad >= 0 ? $brand['accent'] : '#B93A32';
    $utilidadSoft = $utilidad >= 0 ? $brand['accent_soft'] : '#FBE9E7';
    
    // Generar PDF
    $pdf = new ReportePDF('', '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Encabezado con color de marca
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Rect(0, 0, 210, 35, 'F');
    
    $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetY(8);
    $pdf->Cell(0, 10, 'REPORTE DE MOVIMIENTOS', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, $usuario['nombre_completo'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(45);
    
    // Cards de resumen
    $html = '<table cellpadding="0" cellspacing="8">
        <tr>
            <td width="33%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:2px solid ' . $brand['primary'] . '; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:' . $brand['primary_dark'] . '; font-weight:bold; margin-bottom:5px;">INGRESOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $brand['primary'] . ';">$' . number_format($totales['ingreso'], 2) . '</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:2px solid ' . $brand['secondary'] . '; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:' . $brand['secondary_dark'] . '; font-weight:bold; margin-bottom:5px;">GASTOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $brand['secondary'] . ';">$' . number_format($totales['gasto'], 2) . '</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:' . $utilidadSoft . '; border:2px solid ' . $utilidadColor . '; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:' . $utilidadColor . '; font-weight:bold; margin-bottom:5px;">UTILIDAD</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $utilidadColor . ';">$' . number_format($utilidad, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // Distribución por método de pago
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Cell(0, 8, 'Distribución por Método de Pago', 0, 1);
    
    $totalEfectivo = $totalesPorMetodo['efectivo']['ingreso'] - $totalesPorMetodo['efectivo']['gasto'];
    $totalTarjeta = $totalesPorMetodo['tarjeta']['ingreso'] - $totalesPorMetodo['tarjeta']['gasto'];
    $totalTransferencia = $totalesPorMetodo['transferencia']['ingreso'] - $totalesPorMetodo['transferencia']['gasto'];
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:1px solid ' . $brand['primary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['primary'] . '; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['primary_dark'] . ';">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:1px solid ' . $brand['secondary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['secondary'] . '; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['secondary_dark'] . ';">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['accent_soft'] . '; border:1px solid ' . $brand['accent'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['accent'] . '; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['accent_dark'] . ';">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(8);
    // ═══ SECCIÓN MANOLO vs ELIA (ESTE USUARIO) ═══
    $propiedadesUsuario = $this->obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, $usuario_id, $hotel_id);
    $this->generarSeccionPropiedadesPDF($pdf, $propiedadesUsuario, $brand);
    $pdf->Ln(5);
    // DESGLOSE DE HOSPEDAJES POR FECHA
    if (!empty($habitacionesPorFecha)) {
        $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DESGLOSE DE HOSPEDAJES POR DÍA', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $numerodia = 1;
        foreach ($habitacionesPorFecha as $fecha => $hospedajes) {
            // Encabezado de fecha con número de día
            $dayHeaderRgb = $this->reportePdfRgb($brand['primary_soft']);
            $pdf->SetFillColor($dayHeaderRgb[0], $dayHeaderRgb[1], $dayHeaderRgb[2]);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 8, 'Día ' . $numerodia . ' - ' . date('d/m/Y', strtotime($fecha)) . ' - ' . $obtenerNombreDia($fecha), 0, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 9);
            
            $html = '<table border="1" cellpadding="4" cellspacing="0">
                <thead>
                    <tr style="background-color:' . $brand['primary_soft'] . '; color:' . $brand['primary_dark'] . '; font-weight:bold;">
                        <th width="15%" style="text-align:center;">Habitación</th>
                        <th width="45%">Descripción</th>
                        <th width="20%" style="text-align:center;">Método Pago</th>
                        <th width="20%" style="text-align:right;">Ingreso</th>
                    </tr>
                </thead>
                <tbody>';
            
            $totalDia = 0;
            $fila = 0;
            
            // Ordenar hospedajes por número de habitación
            usort($hospedajes, function($a, $b) {
                return intval($a['habitacion']) - intval($b['habitacion']);
            });
            
            foreach ($hospedajes as $hosp) {
                $bgColor = $fila % 2 == 0 ? '#FFFFFF' : $brand['primary_soft'];
                $totalDia += $hosp['monto'];
                
                // Color del método
                $metodoColor = '#757575';
                $metodoBg = '#F5F5F5';
                if ($hosp['metodo'] == 'efectivo') {
                    $metodoColor = '#2E7D32';
                    $metodoBg = '#E8F5E9';
                } elseif ($hosp['metodo'] == 'tarjeta') {
                    $metodoColor = '#1565C0';
                    $metodoBg = '#E3F2FD';
                } elseif ($hosp['metodo'] == 'transferencia') {
                    $metodoColor = $brand['accent'];
                    $metodoBg = $brand['accent_soft'];
                }
                
                // Si la habitación contiene comas (múltiples habitaciones)
                $habitacionDisplay = $hosp['habitacion'];
                if (strpos($habitacionDisplay, ',') !== false) {
                    $habitacionDisplay = str_replace(',', ', ', $habitacionDisplay);
                }
                
                $html .= '<tr style="background-color:' . $bgColor . ';">
                    <td style="text-align:center; font-weight:bold; font-size:11pt; color:' . $brand['primary'] . ';">' . htmlspecialchars($habitacionDisplay) . '</td>
                    <td>' . htmlspecialchars(substr($hosp['descripcion'], 0, 50)) . '</td>
                    <td style="text-align:center;"><span style="background-color:' . $metodoBg . '; color:' . $metodoColor . '; padding:2px 8px; border-radius:10px; font-weight:bold;">' . ucfirst($hosp['metodo']) . '</span></td>
                    <td style="text-align:right; font-weight:bold; color:#2E7D32;">$' . number_format($hosp['monto'], 2) . '</td>
                </tr>';
                $fila++;
            }
            
            $html .= '</tbody>
                <tfoot>
                    <tr style="background-color:' . $brand['primary_soft'] . ';">
                        <td colspan="3" style="text-align:right; font-weight:bold;">Total del día ' . $numerodia . ':</td>
                        <td style="text-align:right; font-weight:bold; color:' . $brand['primary'] . ';">$' . number_format($totalDia, 2) . '</td>
                    </tr>
                </tfoot>
            </table>';
            
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);
            $numerodia++;
        }
        
        // Total general de hospedajes
        $totalGeneralHospedajes = array_sum(array_map(function($m) { 
            return $m['tipo'] == 'ingreso' ? $m['monto'] : -$m['monto']; 
        }, $movimientosHospedaje));
        
        $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'TOTAL HOSPEDAJES: $' . number_format($totalGeneralHospedajes, 2), 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // OTROS MOVIMIENTOS
    if (!empty($otrosMovimientos)) {
        $pdf->Ln(10);
        $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
        $pdf->SetTextColor($accentTextRgb[0], $accentTextRgb[1], $accentTextRgb[2]);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'OTROS MOVIMIENTOS', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 9);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr style="background-color:' . $brand['accent_soft'] . '; color:' . $brand['accent_dark'] . '; font-weight:bold;">
                    <th width="12%" style="text-align:center;">Fecha</th>
                    <th width="10%" style="text-align:center;">Tipo</th>
                    <th width="20%">Categoría</th>
                    <th width="33%">Descripción</th>
                    <th width="13%" style="text-align:center;">Método</th>
                    <th width="12%" style="text-align:right;">Monto</th>
                </tr>
            </thead>
            <tbody>';
        
        $fila = 0;
        foreach ($otrosMovimientos as $mov) {
            $bgColor = $fila % 2 == 0 ? '#FFFFFF' : $brand['accent_soft'];
            $fecha = date('d/m/Y', strtotime($mov['created_at']));
            $tipo = ucfirst($mov['tipo']);
            $categoria = $mov['categoria_nombre'] ?: $mov['categoria'];
            $descripcion = $mov['descripcion'] ?: '';
            $metodo = ucfirst($mov['metodo_pago']);
            $monto = number_format($mov['monto'], 2);
            
            $tipoColor = $mov['tipo'] == 'ingreso' ? '#2E7D32' : '#C62828';
            $tipoTexto = $mov['tipo'] == 'ingreso' ? '[+] ' . $tipo : '[-] ' . $tipo;
            
            $catColor = '#757575';
            if (stripos($categoria, 'devol') !== false) {
                $catColor = '#D32F2F';
            } elseif (stripos($categoria, 'manten') !== false) {
                $catColor = '#F57C00';
            }
            
            $html .= '<tr style="background-color:' . $bgColor . ';">
                <td style="text-align:center;">' . $fecha . '</td>
                <td style="text-align:center; color:' . $tipoColor . '; font-weight:bold;">' . $tipoTexto . '</td>
                <td style="color:' . $catColor . '; font-weight:bold;">' . htmlspecialchars($categoria) . '</td>
                <td>' . htmlspecialchars(substr($descripcion, 0, 50)) . '</td>
                <td style="text-align:center;">' . $metodo . '</td>
                <td style="text-align:right; font-weight:bold; color:' . $tipoColor . ';">$' . $monto . '</td>
            </tr>';
            $fila++;
        }
        
        $totalOtros = array_sum(array_map(function($m) { 
            return $m['tipo'] == 'ingreso' ? $m['monto'] : -$m['monto']; 
        }, $otrosMovimientos));
        
        $html .= '</tbody>
            <tfoot>
                <tr style="background-color:' . $brand['accent_soft'] . ';">
                    <td colspan="5" style="text-align:right; font-weight:bold; font-size:11pt;">Total Otros:</td>
                    <td style="text-align:right; font-weight:bold; font-size:12pt; color:' . $brand['accent'] . ';">$' . number_format($totalOtros, 2) . '</td>
                </tr>
            </tfoot>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
    }
    
    // Footer
    $pdf->SetY(-40);
    $footerBgRgb = $this->reportePdfRgb($brand['primary_soft']);
    $pdf->SetFillColor($footerBgRgb[0], $footerBgRgb[1], $footerBgRgb[2]);
    $pdf->Rect(0, $pdf->GetY() - 5, 210, 50, 'F');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    
    $html = '<table cellpadding="5">
        <tr>
            <td width="100%" style="text-align:center;">
                <div style="font-size:10pt; font-weight:bold; color:' . $brand['primary_dark'] . '; margin-bottom:8px;">' . htmlspecialchars($brand['hotel'], ENT_QUOTES, 'UTF-8') . ' - Sistema de Gestión</div>
                <div style="font-size:9pt; color:' . $brand['muted'] . ';">
                    Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs<br>
                    Por: ' . (function_exists('usuario_actual') ? usuario_actual('nombre_completo') : 'Sistema')  . '
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Salida del PDF
    $filename = function_exists('hotel_export_filename')
        ? hotel_export_filename('Reporte_Movimientos', 'pdf')
        : 'Reporte_Movimientos_' . date('Y-m-d_His') . '.pdf';
    $this->entregarReportePdf($pdf, [
        'tipo_reporte' => 'ingresos-gastos-usuario',
        'titulo' => 'Reporte de movimientos por usuario',
        'descripcion' => 'Reporte PDF de ingresos y gastos filtrado por usuario.',
        'archivo_nombre' => $filename,
        'parametros' => [
            'usuario_id' => (int)$usuario_id,
            'usuario_nombre' => $usuario['nombre_completo'] ?? '',
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ],
    ]);
}
// Método adicional para exportar reporte de ingresos totales


public function testDatosAction() {
    // Solo permitir acceso en desarrollo o al gerente
    if (!can('reportes.all')) {
        $this->redirect('dashboard');
        return;
    }
    
    $db = Database::getInstance();
    $datos = [];
    
    try {
        // 1. Verificar estructura de la tabla
        $sql = "DESCRIBE movimientos_caja";
        $stmt = $db->query($sql);
        $datos['estructura'] = $stmt->fetchAll();
        
        // 2. Resumen general de datos
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo = 'ingreso' THEN 1 ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN tipo = 'gasto' THEN 1 ELSE 0 END) as total_gastos,
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as suma_ingresos,
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as suma_gastos,
                    MIN(created_at) as fecha_mas_antigua,
                    MAX(created_at) as fecha_mas_reciente
                FROM movimientos_caja";
        
        $stmt = $db->query($sql);
        $datos['resumen'] = $stmt->fetch();
        
        // 3. Usuarios con movimientos
        $sql = "SELECT 
                    u.id,
                    u.nombre_completo,
                    COUNT(mc.id) as total_movimientos,
                    SUM(CASE WHEN mc.tipo = 'ingreso' THEN mc.monto ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN mc.tipo = 'gasto' THEN mc.monto ELSE 0 END) as total_gastos
                FROM usuarios u
                LEFT JOIN movimientos_caja mc ON u.id = mc.usuario_id
                WHERE u.activo = 1
                GROUP BY u.id, u.nombre_completo
                ORDER BY total_movimientos DESC";
        
        $stmt = $db->query($sql);
        $datos['usuarios'] = $stmt->fetchAll();
        
        // 4. Últimos 20 movimientos
        $sql = "SELECT 
                    mc.*,
                    u.nombre_completo as usuario
                FROM movimientos_caja mc
                LEFT JOIN usuarios u ON mc.usuario_id = u.id
                ORDER BY mc.created_at DESC
                LIMIT 20";
        
        $stmt = $db->query($sql);
        $datos['movimientos_recientes'] = $stmt->fetchAll();
        
        // 5. Datos del mes actual
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-d');
        
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                    SUM(CASE WHEN tipo = 'gasto' THEN monto ELSE 0 END) as gastos
                FROM movimientos_caja 
                WHERE DATE(created_at) BETWEEN ? AND ?";
        
        $stmt = $db->query($sql, [$fecha_inicio, $fecha_fin]);
        $datos['mes_actual'] = $stmt->fetch();
        $datos['mes_actual']['fecha_inicio'] = $fecha_inicio;
        $datos['mes_actual']['fecha_fin'] = $fecha_fin;
        
        // 6. Verificar categorías
        $sql = "SELECT DISTINCT categoria FROM movimientos_caja WHERE categoria IS NOT NULL";
        $stmt = $db->query($sql);
        $datos['categorias'] = $stmt->fetchAll();
        
        // 7. Verificar métodos de pago
        $sql = "SELECT DISTINCT metodo_pago FROM movimientos_caja WHERE metodo_pago IS NOT NULL";
        $stmt = $db->query($sql);
        $datos['metodos_pago'] = $stmt->fetchAll();
        
        // 8. Movimientos sin usuario
        $sql = "SELECT COUNT(*) as sin_usuario FROM movimientos_caja WHERE usuario_id IS NULL OR usuario_id = 0";
        $stmt = $db->query($sql);
        $datos['sin_usuario'] = $stmt->fetch();
        
    } catch (Exception $e) {
        $datos['error'] = $e->getMessage();
    }
    
    // Renderizar vista de prueba
    View::render('reportes/test-datos', $datos);
}

/**
     * Obtener ingresos por propiedad (MANOLO vs ELIA) para un rango de fechas
     * Reparte proporcionalmente cuando hay habitaciones mixtas
     */
public function obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, $usuario_id = null, $hotel_id = null) {
    $db = Database::getInstance();
    $hotel_id = $hotel_id ?? $this->hotelIdActual();
    
    $sql = "SELECT 
                mc.id as movimiento_id,
                mc.metodo_pago,
                mc.monto,
                mc.reservacion_id,
                mc.created_at
            FROM movimientos_caja mc
            INNER JOIN reservaciones r ON mc.reservacion_id = r.id
                AND r.hotel_id = mc.hotel_id
            WHERE mc.hotel_id = ?
            AND mc.tipo = 'ingreso'
            AND mc.reservacion_id IS NOT NULL
            AND DATE(mc.created_at) BETWEEN ? AND ?";
    
    $params = [$hotel_id, $fecha_inicio, $fecha_fin];
    
    if ($usuario_id) {
        $sql .= " AND mc.usuario_id = ?";
        $params[] = $usuario_id;
    }
    
    $sql .= " ORDER BY mc.created_at";
    
    $stmt = $db->query($sql, $params);
    $registros = $stmt->fetchAll();
    
    $resultado = [
        'manolo' => ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'total' => 0, 'reservas' => 0],
        'elia'   => ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'total' => 0, 'reservas' => 0]
    ];
    
    $reservacionesContadas = [];
    
    foreach ($registros as $reg) {
        $resId = $reg['reservacion_id'];
        $metodo = $reg['metodo_pago'];
        $monto = floatval($reg['monto']);
        
        // Obtener habitaciones de la reservación
        $sqlHabs = "SELECT hab.tipo, hab.precio_base
                    FROM reservacion_habitaciones rh
                    INNER JOIN reservaciones r ON rh.reservacion_id = r.id
                        AND rh.hotel_id = r.hotel_id
                    INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id
                        AND hab.hotel_id = rh.hotel_id
                    WHERE rh.reservacion_id = ?
                    AND r.hotel_id = ?";
        $stmtHabs = $db->query($sqlHabs, [$resId, $hotel_id]);
        $habitaciones = $stmtHabs->fetchAll();
        
        $precioManolo = 0;
        $precioElia = 0;
        
        foreach ($habitaciones as $hab) {
            $precio = floatval($hab['precio_base']);
            if (strpos($hab['tipo'], 'manolo') !== false) {
                $precioManolo += $precio;
            } else {
                $precioElia += $precio;
            }
        }
        
        $precioTotal = $precioManolo + $precioElia;
        
        // Repartir proporcionalmente según precio de habitaciones
        if ($precioTotal > 0) {
            $montoManolo = round($monto * ($precioManolo / $precioTotal), 2);
            $montoElia = round($monto * ($precioElia / $precioTotal), 2);
            // Ajustar centavos por redondeo
            $diff = $monto - ($montoManolo + $montoElia);
            if ($diff != 0) {
                if ($montoElia > $montoManolo) $montoElia += $diff;
                else $montoManolo += $diff;
            }
        } else {
            $montoManolo = 0;
            $montoElia = $monto;
        }
        
        if ($montoManolo > 0) {
            $resultado['manolo'][$metodo] += $montoManolo;
            $resultado['manolo']['total'] += $montoManolo;
            if (!isset($reservacionesContadas[$resId . '-m'])) {
                $resultado['manolo']['reservas']++;
                $reservacionesContadas[$resId . '-m'] = true;
            }
        }
        
        if ($montoElia > 0) {
            $resultado['elia'][$metodo] += $montoElia;
            $resultado['elia']['total'] += $montoElia;
            if (!isset($reservacionesContadas[$resId . '-e'])) {
                $resultado['elia']['reservas']++;
                $reservacionesContadas[$resId . '-e'] = true;
            }
        }
    }
    
    return $resultado;
}

private function generarSeccionPropiedadesPDF($pdf, $propiedades, array $brand = []) {
    $manolo = $propiedades['manolo'];
    $elia = $propiedades['elia'];
    $totalGeneral = $manolo['total'] + $elia['total'];
    
    // Si no hay ingresos de hospedaje, no mostrar esta sección
    if ($totalGeneral <= 0) return;
    
    $pctM = ($manolo['total'] / $totalGeneral) * 100;
    $pctE = ($elia['total'] / $totalGeneral) * 100;
    $primary = $brand['primary'] ?? '#8B6D42';
    $secondary = $brand['secondary'] ?? '#7A8B5C';
    $accent = $brand['accent'] ?? '#4A6FA5';
    $primaryDark = $brand['primary_dark'] ?? '#654E2C';
    $secondaryDark = $brand['secondary_dark'] ?? '#576441';
    $accentDark = $brand['accent_dark'] ?? '#2C4A6E';
    $primarySoft = $brand['primary_soft'] ?? '#F5EFE6';
    $secondarySoft = $brand['secondary_soft'] ?? '#EEF2E6';
    $accentSoft = $brand['accent_soft'] ?? '#E8EDF3';
    $line = $brand['line'] ?? '#E7DEC9';
    $primaryRgb = $this->reportePdfRgb($primary);
    $primaryTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($primary));
    
    $pdf->Ln(5);
    
    // ── TÍTULO DE SECCIÓN ──
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 11, '  INGRESOS POR PROPIEDAD', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);
    
    // ── 3 CARDS: MANOLO | ELIA | TOTAL ──
    $html = '<table cellpadding="0" cellspacing="6">
        <tr>
            <td width="33%">
                <div style="background-color:' . $primarySoft . '; border:2px solid ' . $primary . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:' . $primaryDark . '; font-weight:bold; letter-spacing:1px;">MANOLO</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $primary . ';">$' . number_format($manolo['total'], 2) . '</div>
                    <div style="font-size:9pt; color:' . $primary . '; margin-top:4px;">' . $manolo['reservas'] . ' reservas · ' . number_format($pctM, 1) . '%</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:' . $secondarySoft . '; border:2px solid ' . $secondary . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:' . $secondaryDark . '; font-weight:bold; letter-spacing:1px;">ELIA</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $secondary . ';">$' . number_format($elia['total'], 2) . '</div>
                    <div style="font-size:9pt; color:' . $secondary . '; margin-top:4px;">' . $elia['reservas'] . ' reservas · ' . number_format($pctE, 1) . '%</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:' . $accentSoft . '; border:2px solid ' . $accent . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:' . $accentDark . '; font-weight:bold; letter-spacing:1px;">TOTAL HOSPEDAJE</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $accent . ';">$' . number_format($totalGeneral, 2) . '</div>
                    <div style="font-size:9pt; color:' . $accent . '; margin-top:4px;">' . ($manolo['reservas'] + $elia['reservas']) . ' reservas</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(3);
    
    // ── TABLA COMPARATIVA POR MÉTODO DE PAGO ──
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell(0, 8, '¿Cómo se cobró en cada propiedad?', 0, 1);
    
    $metodos = [
        'efectivo' => ['nombre' => 'Efectivo', 'color' => $primary, 'bg' => $primarySoft],
        'tarjeta' => ['nombre' => 'Tarjeta', 'color' => $secondary, 'bg' => $secondarySoft],
        'transferencia' => ['nombre' => 'Transferencia', 'color' => $accent, 'bg' => $accentSoft]
    ];
    
    $html = '<table border="1" cellpadding="6" cellspacing="0" style="font-size:10pt;">
        <thead>
            <tr style="background-color:' . $primary . '; color:' . $this->reportePdfTextColor($primary) . '; font-weight:bold;">
                <th width="28%">Método de Pago</th>
                <th width="24%" style="text-align:right;">Manolo</th>
                <th width="24%" style="text-align:right;">Elia</th>
                <th width="24%" style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    $fila = 0;
    foreach ($metodos as $key => $info) {
        $m = $manolo[$key] ?? 0;
        $e = $elia[$key] ?? 0;
        $t = $m + $e;
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : $line;
        
        $html .= '<tr style="background-color:' . $bgColor . ';">
            <td style="font-weight:bold; color:' . $info['color'] . ';">
                <span style="background-color:' . $info['bg'] . '; padding:3px 8px; border-radius:8px;">' . $info['nombre'] . '</span>
            </td>
            <td style="text-align:right; color:' . $primary . '; font-weight:bold;">$' . number_format($m, 2) . '</td>
            <td style="text-align:right; color:' . $secondary . '; font-weight:bold;">$' . number_format($e, 2) . '</td>
            <td style="text-align:right; font-weight:bold;">$' . number_format($t, 2) . '</td>
        </tr>';
        $fila++;
    }
    
    // Fila de TOTALES
    $html .= '<tr style="background-color:' . $primarySoft . '; font-weight:bold;">
            <td style="font-size:11pt;">TOTAL</td>
            <td style="text-align:right; color:' . $primary . '; font-size:11pt;">$' . number_format($manolo['total'], 2) . '</td>
            <td style="text-align:right; color:' . $secondary . '; font-size:11pt;">$' . number_format($elia['total'], 2) . '</td>
            <td style="text-align:right; font-size:11pt;">$' . number_format($totalGeneral, 2) . '</td>
        </tr>';
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // ── BARRA VISUAL DE PROPORCIÓN ──
    $pdf->Ln(3);
    
    $manoloWidth = round($pctM);
    $eliaWidth = 100 - $manoloWidth;
    if ($manoloWidth < 1 && $manolo['total'] > 0) $manoloWidth = 1;
    if ($eliaWidth < 1 && $elia['total'] > 0) $eliaWidth = 1;
    
    $html = '<table cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td width="15%" style="font-size:9pt; color:' . $primary . '; font-weight:bold; text-align:right; padding-right:5px;">Manolo ' . number_format($pctM, 0) . '%</td>
            <td width="70%">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="' . $manoloWidth . '%" style="background-color:' . $primary . '; height:16px;">&nbsp;</td>
                        <td width="' . $eliaWidth . '%" style="background-color:' . $secondary . '; height:16px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
            <td width="15%" style="font-size:9pt; color:' . $secondary . '; font-weight:bold; padding-left:5px;">Elia ' . number_format($pctE, 0) . '%</td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
}


// ╔════════════════════════════════════════════════════════════╗
// ║  MÉTODO 3: exportarIngresosGastosPdf (NUEVO - NO EXISTÍA) ║
// ║  El botón "Exportar PDF" principal de ingresos-gastos     ║
// ╚════════════════════════════════════════════════════════════╝

private function exportarIngresosGastosPdf() {
    require_once __DIR__ . '/../views/reportes/ReportePDF.php';
    
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    $hotel_id = $this->hotelIdActual();
    
    $reporteModel = new Reporte();
    $datos = $reporteModel->getIngresosVsGastos($fecha_inicio, $fecha_fin);
    
    $totalIngresos = array_sum(array_column($datos['ingresos'], 'total'));
    $totalGastos = array_sum(array_column($datos['gastos'], 'total'));
    $utilidad = $totalIngresos - $totalGastos;
    
    // Datos por método de pago
    $metodosPago = $this->getMetodosPagoData($fecha_inicio, $fecha_fin);
    $brand = $this->reportePdfBranding();
    $periodoLabel = date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin));
    $primaryRgb = $this->reportePdfRgb($brand['primary']);
    $primaryDarkRgb = $this->reportePdfRgb($brand['primary_dark']);
    $secondaryRgb = $this->reportePdfRgb($brand['secondary']);
    $accentRgb = $this->reportePdfRgb($brand['accent']);
    $headerTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['primary_dark']));
    $sectionTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['primary']));
    $secondaryTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['secondary']));
    $accentTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['accent']));
    $numIngresos = array_sum(array_map(function($row) {
        return (int) ($row['cantidad'] ?? 0);
    }, $datos['ingresos'] ?? []));
    $numGastos = array_sum(array_map(function($row) {
        return (int) ($row['cantidad'] ?? 0);
    }, $datos['gastos'] ?? []));
    $utilidadColor = $utilidad >= 0 ? $brand['accent'] : '#B93A32';
    $utilidadSoft = $utilidad >= 0 ? $brand['accent_soft'] : '#FBE9E7';
    $utilidadLabel = $utilidad >= 0 ? 'UTILIDAD' : 'PERDIDA';
    $hotelWords = preg_split('/\s+/', trim($brand['hotel']));
    $hotelInitials = '';
    foreach ($hotelWords as $word) {
        if ($word === '') {
            continue;
        }
        $hotelInitials .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
        if (strlen($hotelInitials) >= 2) {
            break;
        }
    }
    $hotelInitials = strtoupper($hotelInitials ?: 'H');
    
    // Generar PDF
    $pdf = new ReportePDF('Ingresos vs Gastos', $periodoLabel);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(14, 14, 14);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AddPage();
    
    // ── ENCABEZADO ──
    $pdf->SetFillColor($primaryDarkRgb[0], $primaryDarkRgb[1], $primaryDarkRgb[2]);
    $pdf->Rect(0, 0, 210, 42, 'F');
    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->Rect(0, 39, 210, 3, 'F');

    $pdf->SetFillColor(255, 255, 255);
    $pdf->RoundedRect(14, 8, 24, 24, 4, '1111', 'F');
    if (!empty($brand['logo'])) {
        $pdf->Image($brand['logo'], 17, 11, 18, 18, '', '', '', false, 300, '', false, false, 0, false, false, true);
    } else {
        $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(14, 15);
        $pdf->Cell(24, 7, $hotelInitials, 0, 0, 'C');
    }

    $pdf->SetTextColor($headerTextRgb[0], $headerTextRgb[1], $headerTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 19);
    $pdf->SetXY(45, 8);
    $pdf->Cell(110, 9, 'INGRESOS VS GASTOS', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetX(45);
    $pdf->Cell(110, 5, $brand['hotel'], 0, 1, 'L');
    $pdf->SetX(45);
    $pdf->Cell(110, 5, 'Periodo: ' . $periodoLabel, 0, 1, 'L');

    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->SetTextColor($accentTextRgb[0], $accentTextRgb[1], $accentTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(160, 12);
    $pdf->Cell(34, 7, 'REPORTE PDF', 0, 1, 'C', true);

    $pdf->SetTextColor(23, 32, 51);
    $pdf->SetY(50);
    
    // ── CARDS DE RESUMEN ──
    $html = '<table cellpadding="0" cellspacing="7">
        <tr>
            <td width="33%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:1.5px solid ' . $brand['primary'] . '; border-radius:10px; padding:18px; text-align:left;">
                    <div style="font-size:8pt; color:' . $brand['muted'] . '; font-weight:bold;">INGRESOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $brand['primary'] . ';">$' . number_format($totalIngresos, 2) . '</div>
                    <div style="font-size:8pt; color:' . $brand['muted'] . ';">' . $numIngresos . ' movimientos registrados</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:1.5px solid ' . $brand['secondary'] . '; border-radius:10px; padding:18px; text-align:left;">
                    <div style="font-size:8pt; color:' . $brand['muted'] . '; font-weight:bold;">GASTOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $brand['secondary'] . ';">$' . number_format($totalGastos, 2) . '</div>
                    <div style="font-size:8pt; color:' . $brand['muted'] . ';">' . $numGastos . ' movimientos registrados</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:' . $utilidadSoft . '; border:1.5px solid ' . $utilidadColor . '; border-radius:10px; padding:18px; text-align:left;">
                    <div style="font-size:8pt; color:' . $brand['muted'] . '; font-weight:bold;">' . $utilidadLabel . '</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . $utilidadColor . ';">$' . number_format($utilidad, 2) . '</div>
                    <div style="font-size:8pt; color:' . $brand['muted'] . ';">Balance del periodo</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // ── DISTRIBUCIÓN POR MÉTODO DE PAGO ──
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->SetTextColor($sectionTextRgb[0], $sectionTextRgb[1], $sectionTextRgb[2]);
    $pdf->Cell(0, 8, '  DISTRIBUCION POR METODO DE PAGO', 0, 1, 'L', true);
    $pdf->SetTextColor(23, 32, 51);
    
    $totalEfectivo = ($metodosPago['efectivo']['ingresos'] ?? 0) - ($metodosPago['efectivo']['gastos'] ?? 0);
    $totalTarjeta = ($metodosPago['tarjeta']['ingresos'] ?? 0) - ($metodosPago['tarjeta']['gastos'] ?? 0);
    $totalTransferencia = ($metodosPago['transferencia']['ingresos'] ?? 0) - ($metodosPago['transferencia']['gastos'] ?? 0);
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:1px solid ' . $brand['primary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['primary'] . '; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['primary_dark'] . ';">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:1px solid ' . $brand['secondary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['secondary'] . '; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['secondary_dark'] . ';">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['accent_soft'] . '; border:1px solid ' . $brand['accent'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['accent'] . '; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['accent'] . ';">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // ── DESGLOSE DE INGRESOS POR CATEGORÍA ──
    if (!empty($datos['ingresos'])) {
        $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
        $pdf->SetTextColor($sectionTextRgb[0], $sectionTextRgb[1], $sectionTextRgb[2]);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 9, '  DESGLOSE DE INGRESOS', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="font-size:10pt;">
            <thead>
                <tr style="background-color:' . $brand['primary_soft'] . '; color:' . $brand['primary_dark'] . '; font-weight:bold;">
                    <th width="50%">Categoría</th>
                    <th width="20%" style="text-align:center;">Movimientos</th>
                    <th width="30%" style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($datos['ingresos'] as $i => $cat) {
            $bg = $i % 2 == 0 ? '#FFFFFF' : $brand['primary_soft'];
            $html .= '<tr style="background-color:' . $bg . ';">
                <td>' . htmlspecialchars($cat['categoria'] ?? 'Sin categoría') . '</td>
                <td style="text-align:center;">' . ($cat['cantidad'] ?? 0) . '</td>
                <td style="text-align:right; font-weight:bold; color:' . $brand['primary'] . ';">$' . number_format($cat['total'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Ln(3);
    }
    
    // ── DESGLOSE DE GASTOS POR CATEGORÍA ──
    if (!empty($datos['gastos'])) {
        $pdf->SetFillColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
        $pdf->SetTextColor($secondaryTextRgb[0], $secondaryTextRgb[1], $secondaryTextRgb[2]);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 9, '  DESGLOSE DE GASTOS', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="font-size:10pt;">
            <thead>
                <tr style="background-color:' . $brand['secondary_soft'] . '; color:' . $brand['secondary_dark'] . '; font-weight:bold;">
                    <th width="50%">Categoría</th>
                    <th width="20%" style="text-align:center;">Movimientos</th>
                    <th width="30%" style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($datos['gastos'] as $i => $cat) {
            $bg = $i % 2 == 0 ? '#FFFFFF' : $brand['secondary_soft'];
            $html .= '<tr style="background-color:' . $bg . ';">
                <td>' . htmlspecialchars($cat['categoria'] ?? 'Sin categoría') . '</td>
                <td style="text-align:center;">' . ($cat['cantidad'] ?? 0) . '</td>
                <td style="text-align:right; font-weight:bold; color:' . $brand['secondary'] . ';">$' . number_format($cat['total'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
    }
    
    // ═══════════════════════════════════════════
    // ═══ SECCIÓN MANOLO vs ELIA ═══
    // ═══════════════════════════════════════════
    $propiedades = $this->obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, null, $hotel_id);
    $this->generarSeccionPropiedadesPDF($pdf, $propiedades, $brand);
    
    // ── FOOTER ──
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 5, $brand['hotel'] . ' - Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs', 0, 1, 'C');
    
    $filename = function_exists('hotel_export_filename')
        ? hotel_export_filename('Ingresos_Gastos', 'pdf')
        : 'Ingresos_Gastos_' . date('Y-m-d_His') . '.pdf';
    $this->entregarReportePdf($pdf, [
        'tipo_reporte' => 'ingresos-gastos',
        'titulo' => 'Reporte de ingresos vs gastos',
        'descripcion' => 'Reporte PDF general de ingresos, gastos y utilidad.',
        'archivo_nombre' => $filename,
        'parametros' => [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ],
    ]);
}

// Agregar también este método para prueba específica de usuario
public function testUsuarioAction() {
    if (!can('reportes.all')) {
        $this->redirect('dashboard');
        return;
    }
    
    $usuario_id = $this->getQuery('usuario_id', 1);
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    
    $db = Database::getInstance();
    
    // Consulta exacta que usa el PDF
    $sql = "SELECT 
                mc.*,
                COALESCE(cm.nombre, mc.categoria) as categoria_nombre,
                cm.icono,
                cm.color
            FROM movimientos_caja mc
            LEFT JOIN categorias_movimientos cm ON mc.categoria_id = cm.id
            WHERE mc.usuario_id = ? 
            AND DATE(mc.created_at) BETWEEN ? AND ?
            ORDER BY mc.created_at DESC";
    
    $stmt = $db->query($sql, [$usuario_id, $fecha_inicio, $fecha_fin]);
    $movimientos = $stmt->fetchAll();
    
    // Calcular totales como en el PDF
    $totales = [
        'ingresos' => [
            'efectivo' => 0, 
            'tarjeta' => 0, 
            'transferencia' => 0, 
            'total' => 0
        ],
        'gastos' => [
            'efectivo' => 0, 
            'tarjeta' => 0, 
            'transferencia' => 0, 
            'total' => 0
        ]
    ];
    $debug = [
        'tipos_encontrados' => [],
        'metodos_encontrados' => [],
        'calculos_detallados' => []
    ];
    
    foreach ($movimientos as $index => $mov) {
        $tipo_original = $mov['tipo'];
        $tipo_procesado = strtolower(trim($mov['tipo']));
        $metodo_original = $mov['metodo_pago'];
        $metodo_procesado = strtolower(trim($mov['metodo_pago']));
        $monto = floatval($mov['monto']);
        
        // Guardar tipos y métodos únicos
        $debug['tipos_encontrados'][$tipo_original] = $tipo_procesado;
        $debug['metodos_encontrados'][$metodo_original] = $metodo_procesado;
        
        // Guardar detalles del cálculo
        $debug['calculos_detallados'][] = [
            'index' => $index,
            'id' => $mov['id'],
            'tipo_original' => $tipo_original,
            'tipo_procesado' => $tipo_procesado,
            'tipo_valido' => in_array($tipo_procesado, ['ingreso', 'gasto']),
            'metodo_original' => $metodo_original,
            'metodo_procesado' => $metodo_procesado,
            'metodo_valido' => in_array($metodo_procesado, ['efectivo', 'tarjeta', 'transferencia']),
            'monto' => $monto,
            'agregado_a_total' => (in_array($tipo_procesado, ['ingreso', 'gasto']) && in_array($metodo_procesado, ['efectivo', 'tarjeta', 'transferencia']))
        ];
    }
    
    // Agregar al array de datos que se pasa a la vista
    $datos['debug'] = $debug;
    
    foreach ($movimientos as $mov) {
        $tipo = $mov['tipo'];
        $metodo = $mov['metodo_pago'];
        $monto = floatval($mov['monto']);
        
        if (isset($totales[$tipo]) && isset($totales[$tipo][$metodo])) {
            $totales[$tipo][$metodo] += $monto;
            $totales[$tipo]['total'] += $monto;
        }
    }
    
    $datos = [
        'usuario_id' => $usuario_id,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'movimientos' => $movimientos,
        'totales' => $totales,
        'cantidad_movimientos' => count($movimientos)
    ];
    
    View::render('reportes/test-usuario', $datos);
}


// Nuevo método para exportar reporte de ingresos totales
private function exportarIngresosTotalesPdf() {
    require_once __DIR__ . '/../views/reportes/ReportePDF.php';
    
    // Función auxiliar para obtener el nombre del día
    $obtenerNombreDia = function($fecha) {
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return $dias[date('w', strtotime($fecha))];
    };
    
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
    $hotel_id = $this->hotelIdActual();
    
    $db = Database::getInstance();
    
    // Obtener todos los movimientos del período
    $sql = "SELECT 
                mc.*,
                mc.categoria as categoria_nombre,
                u.nombre_completo as usuario_nombre,
                r.id as reservacion_id_real,
                GROUP_CONCAT(DISTINCT h.numero ORDER BY h.numero) as habitaciones_numeros
            FROM movimientos_caja mc
            LEFT JOIN usuarios u ON mc.usuario_id = u.id
            LEFT JOIN reservaciones r ON mc.reservacion_id = r.id
                AND r.hotel_id = mc.hotel_id
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                AND rh.hotel_id = r.hotel_id
            LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
                AND h.hotel_id = rh.hotel_id
            WHERE mc.hotel_id = ?
            AND DATE(mc.created_at) BETWEEN ? AND ?
            GROUP BY mc.id
            ORDER BY mc.created_at ASC";
    
    $stmt = $db->query($sql, [$hotel_id, $fecha_inicio, $fecha_fin]);
    $movimientos = $stmt->fetchAll();
    
    // Procesar datos
    $movimientosPorDia = [];
    $totalesGenerales = ['ingreso' => 0, 'gasto' => 0];
    $totalesPorUsuario = [];
    $totalesPorMetodo = [
        'efectivo' => ['ingreso' => 0, 'gasto' => 0],
        'tarjeta' => ['ingreso' => 0, 'gasto' => 0],
        'transferencia' => ['ingreso' => 0, 'gasto' => 0]
    ];
    $totalesPorCategoria = [];
    
    foreach ($movimientos as $mov) {
        $fecha = date('Y-m-d', strtotime($mov['created_at']));
        $tipo = $mov['tipo'];
        $monto = floatval($mov['monto']);
        $metodo = $mov['metodo_pago'];
        $usuario = $mov['usuario_nombre'] ?? 'Sistema';
        $categoria = $mov['categoria_nombre'] ?? $mov['categoria'];
        
        // Limpiar descripción
        if (isset($mov['descripcion'])) {
            $mov['descripcion'] = preg_replace('/Reservación\s*#\d+\s*-?\s*/', '', $mov['descripcion']);
            $mov['descripcion'] = trim($mov['descripcion']);
        }
        
        // Totales generales
        $totalesGenerales[$tipo] += $monto;
        
        // Totales por método
        if (isset($totalesPorMetodo[$metodo])) {
            $totalesPorMetodo[$metodo][$tipo] += $monto;
        }
        
        // Totales por usuario
        if (!isset($totalesPorUsuario[$usuario])) {
            $totalesPorUsuario[$usuario] = ['ingreso' => 0, 'gasto' => 0];
        }
        $totalesPorUsuario[$usuario][$tipo] += $monto;
        
        // Totales por categoría
        if (!isset($totalesPorCategoria[$categoria])) {
            $totalesPorCategoria[$categoria] = ['ingreso' => 0, 'gasto' => 0, 'cantidad' => 0];
        }
        $totalesPorCategoria[$categoria][$tipo] += $monto;
        $totalesPorCategoria[$categoria]['cantidad']++;
        
        // Agrupar por día
        if (!isset($movimientosPorDia[$fecha])) {
            $movimientosPorDia[$fecha] = [
                'movimientos' => [],
                'totales' => ['ingreso' => 0, 'gasto' => 0]
            ];
        }
        
        // Si es hospedaje, intentar obtener habitación
        if ($categoria == 'Hospedaje') {
            $habitacion = 'Sin especificar';
            
            if (!empty($mov['habitaciones_numeros'])) {
                $habitacion = $mov['habitaciones_numeros'];
            } else {
                $descripcion = $mov['descripcion'] ?? '';
                if (preg_match('/(?:Hab(?:itación)?\.?\s*|#\s*|N(?:o|°)\.?\s*)(\d{1,3})/i', $descripcion, $matches)) {
                    $habitacion = $matches[1];
                }
            }
            $mov['habitacion_display'] = $habitacion;
        }
        
        $movimientosPorDia[$fecha]['movimientos'][] = $mov;
        $movimientosPorDia[$fecha]['totales'][$tipo] += $monto;
    }
    
    $utilidadTotal = $totalesGenerales['ingreso'] - $totalesGenerales['gasto'];
    $brand = $this->reportePdfBranding();
    $primaryRgb = $this->reportePdfRgb($brand['primary']);
    $secondaryRgb = $this->reportePdfRgb($brand['secondary']);
    $accentRgb = $this->reportePdfRgb($brand['accent']);
    $primaryTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['primary']));
    $secondaryTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['secondary']));
    $accentTextRgb = $this->reportePdfRgb($this->reportePdfTextColor($brand['accent']));
    $utilidadColor = $utilidadTotal >= 0 ? $brand['accent'] : '#B93A32';
    $utilidadSoft = $utilidadTotal >= 0 ? $brand['accent_soft'] : '#FBE9E7';
    
    // Generar PDF
    $pdf = new ReportePDF('', '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Encabezado
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Rect(0, 0, 210, 35, 'F');
    
    $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetY(8);
    $pdf->Cell(0, 10, 'REPORTE DE INGRESOS TOTALES', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, $brand['hotel'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(45);
    
    // Cards de resumen
    $html = '<table cellpadding="0" cellspacing="8">
        <tr>
            <td width="25%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:2px solid ' . $brand['primary'] . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:' . $brand['primary_dark'] . '; font-weight:bold;">INGRESOS</div>
                    <div style="font-size:18pt; font-weight:bold; color:' . $brand['primary'] . ';">$' . number_format($totalesGenerales['ingreso'], 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:2px solid ' . $brand['secondary'] . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:' . $brand['secondary_dark'] . '; font-weight:bold;">GASTOS</div>
                    <div style="font-size:18pt; font-weight:bold; color:' . $brand['secondary'] . ';">$' . number_format($totalesGenerales['gasto'], 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:' . $utilidadSoft . '; border:2px solid ' . $utilidadColor . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:' . $utilidadColor . '; font-weight:bold;">UTILIDAD</div>
                    <div style="font-size:18pt; font-weight:bold; color:' . $utilidadColor . ';">$' . number_format($utilidadTotal, 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:' . $brand['accent_soft'] . '; border:2px solid ' . $brand['accent'] . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:' . $brand['accent_dark'] . '; font-weight:bold;">DÍAS</div>
                    <div style="font-size:18pt; font-weight:bold; color:' . $brand['accent'] . ';">' . count($movimientosPorDia) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // Distribución por método
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->Cell(0, 8, 'Distribución por Método de Pago', 0, 1);
    
    $totalEfectivo = $totalesPorMetodo['efectivo']['ingreso'] - $totalesPorMetodo['efectivo']['gasto'];
    $totalTarjeta = $totalesPorMetodo['tarjeta']['ingreso'] - $totalesPorMetodo['tarjeta']['gasto'];
    $totalTransferencia = $totalesPorMetodo['transferencia']['ingreso'] - $totalesPorMetodo['transferencia']['gasto'];
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:' . $brand['primary_soft'] . '; border:1px solid ' . $brand['primary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['primary'] . '; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['primary_dark'] . ';">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['secondary_soft'] . '; border:1px solid ' . $brand['secondary'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['secondary'] . '; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['secondary_dark'] . ';">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:' . $brand['accent_soft'] . '; border:1px solid ' . $brand['accent'] . '; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:' . $brand['accent'] . '; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:' . $brand['accent_dark'] . ';">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(8);
    
    
    
    // Resumen por usuario
    $pdf->SetFillColor($secondaryRgb[0], $secondaryRgb[1], $secondaryRgb[2]);
    $pdf->SetTextColor($secondaryTextRgb[0], $secondaryTextRgb[1], $secondaryTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'RESUMEN POR USUARIO', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 9);
    $html = '<table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr style="background-color:' . $brand['secondary_soft'] . '; color:' . $brand['secondary_dark'] . '; font-weight:bold;">
                <th width="40%">Usuario</th>
                <th width="20%" style="text-align:right;">Ingresos</th>
                <th width="20%" style="text-align:right;">Gastos</th>
                <th width="20%" style="text-align:right;">Utilidad</th>
            </tr>
        </thead>
        <tbody>';
    
    $fila = 0;
    foreach ($totalesPorUsuario as $usuario => $totales) {
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : $brand['secondary_soft'];
        $utilidad = $totales['ingreso'] - $totales['gasto'];
        
        $html .= '<tr style="background-color:' . $bgColor . ';">
            <td>' . htmlspecialchars($usuario) . '</td>
            <td style="text-align:right; color:#2E7D32;">$' . number_format($totales['ingreso'], 2) . '</td>
            <td style="text-align:right; color:#C62828;">$' . number_format($totales['gasto'], 2) . '</td>
            <td style="text-align:right; font-weight:bold; color:' . ($utilidad >= 0 ? '#1B5E20' : '#B71C1C') . ';">$' . number_format($utilidad, 2) . '</td>
        </tr>';
        $fila++;
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(10);
    
    // Resumen por categoría
    $pdf->SetFillColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    $pdf->SetTextColor($primaryTextRgb[0], $primaryTextRgb[1], $primaryTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'RESUMEN POR CATEGORÍA', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 9);
    $html = '<table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr style="background-color:' . $brand['primary_soft'] . '; color:' . $brand['primary_dark'] . '; font-weight:bold;">
                <th width="30%">Categoría</th>
                <th width="15%" style="text-align:center;">Cantidad</th>
                <th width="20%" style="text-align:right;">Ingresos</th>
                <th width="20%" style="text-align:right;">Gastos</th>
                <th width="15%" style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    $fila = 0;
    foreach ($totalesPorCategoria as $categoria => $datos) {
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : $brand['primary_soft'];
        $total = $datos['ingreso'] - $datos['gasto'];
        
        $html .= '<tr style="background-color:' . $bgColor . ';">
            <td>' . htmlspecialchars($categoria) . '</td>
            <td style="text-align:center;">' . $datos['cantidad'] . '</td>
            <td style="text-align:right; color:#2E7D32;">$' . number_format($datos['ingreso'], 2) . '</td>
            <td style="text-align:right; color:#C62828;">$' . number_format($datos['gasto'], 2) . '</td>
            <td style="text-align:right; font-weight:bold;">$' . number_format($total, 2) . '</td>
        </tr>';
        $fila++;
    }
    
    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // ═══ SECCIÓN MANOLO vs ELIA ═══
    $propiedades = $this->obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, null, $hotel_id);
    $this->generarSeccionPropiedadesPDF($pdf, $propiedades, $brand);
    
    // Nueva página para detalle diario
    $pdf->AddPage();
    
    // Detalle por día
    $pdf->SetFillColor($accentRgb[0], $accentRgb[1], $accentRgb[2]);
    $pdf->SetTextColor($accentTextRgb[0], $accentTextRgb[1], $accentTextRgb[2]);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'DETALLE DIARIO DE MOVIMIENTOS', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $numeroDia = 1;
    foreach ($movimientosPorDia as $fecha => $dataDia) {
        $dayHeaderRgb = $this->reportePdfRgb($brand['accent_soft']);
        $pdf->SetFillColor($dayHeaderRgb[0], $dayHeaderRgb[1], $dayHeaderRgb[2]);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Día ' . $numeroDia . ' - ' . date('d/m/Y', strtotime($fecha)) . ' - ' . $obtenerNombreDia($fecha), 0, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 8);
        $html = '<table border="1" cellpadding="3" cellspacing="0">
            <thead>
                <tr style="background-color:' . $brand['accent_soft'] . '; color:' . $brand['accent_dark'] . '; font-weight:bold;">
                    <th width="8%">Hora</th>
                    <th width="12%">Usuario</th>
                    <th width="12%">Categoría</th>
                    <th width="28%">Descripción</th>
                    <th width="10%">Habitación</th>
                    <th width="10%">Método</th>
                    <th width="8%">Tipo</th>
                    <th width="12%" style="text-align:right;">Monto</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($dataDia['movimientos'] as $mov) {
            $hora = date('H:i', strtotime($mov['created_at']));
            $usuario = substr($mov['usuario_nombre'] ?? 'Sistema', 0, 15);
            $categoria = substr($mov['categoria_nombre'], 0, 15);
            $descripcion = substr($mov['descripcion'] ?? '', 0, 35);
            $habitacion = isset($mov['habitacion_display']) ? $mov['habitacion_display'] : '-';
            $metodo = ucfirst($mov['metodo_pago']);
            $tipo = $mov['tipo'];
            $monto = number_format($mov['monto'], 2);
            
            $tipoColor = $tipo == 'ingreso' ? '#2E7D32' : '#C62828';
            
            $html .= '<tr>
                <td>' . $hora . '</td>
                <td>' . htmlspecialchars($usuario) . '</td>
                <td>' . htmlspecialchars($categoria) . '</td>
                <td>' . htmlspecialchars($descripcion) . '</td>
                <td style="text-align:center; font-weight:bold;">' . htmlspecialchars($habitacion) . '</td>
                <td>' . $metodo . '</td>
                <td style="color:' . $tipoColor . '; font-weight:bold;">' . ucfirst($tipo) . '</td>
                <td style="text-align:right; color:' . $tipoColor . '; font-weight:bold;">$' . $monto . '</td>
            </tr>';
        }
        
        $utilidadDia = $dataDia['totales']['ingreso'] - $dataDia['totales']['gasto'];
        
        $html .= '</tbody>
            <tfoot>
                <tr style="background-color:' . $brand['accent_soft'] . ';">
                    <td colspan="7" style="text-align:right; font-weight:bold;">Totales del día:</td>
                    <td style="text-align:right; font-weight:bold;">
                        I: $' . number_format($dataDia['totales']['ingreso'], 2) . '<br>
                        G: $' . number_format($dataDia['totales']['gasto'], 2) . '<br>
                        U: $' . number_format($utilidadDia, 2) . '
                    </td>
                </tr>
            </tfoot>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Ln(5);
        $numeroDia++;
    }
    
    // Footer
    $pdf->SetY(-40);
    $footerBgRgb = $this->reportePdfRgb($brand['primary_soft']);
    $pdf->SetFillColor($footerBgRgb[0], $footerBgRgb[1], $footerBgRgb[2]);
    $pdf->Rect(0, $pdf->GetY() - 5, 210, 50, 'F');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor($primaryRgb[0], $primaryRgb[1], $primaryRgb[2]);
    
    $html = '<table cellpadding="5">
        <tr>
            <td width="100%" style="text-align:center;">
                <div style="font-size:10pt; font-weight:bold; color:' . $brand['primary_dark'] . '; margin-bottom:8px;">' . htmlspecialchars($brand['hotel'], ENT_QUOTES, 'UTF-8') . ' - Sistema de Gestión</div>
                <div style="font-size:9pt; color:' . $brand['muted'] . ';">
                    Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs<br>
                    Por: ' . (function_exists('usuario_actual') ? usuario_actual('nombre_completo') : 'Sistema')  . '
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Salida del PDF
    $filename = function_exists('hotel_export_filename')
        ? hotel_export_filename('Reporte_Ingresos_Totales', 'pdf')
        : 'Reporte_Ingresos_Totales_' . date('Y-m-d_His') . '.pdf';
    $this->entregarReportePdf($pdf, [
        'tipo_reporte' => 'ingresos-totales',
        'titulo' => 'Reporte de ingresos totales',
        'descripcion' => 'Reporte PDF de ingresos totales por periodo.',
        'archivo_nombre' => $filename,
        'parametros' => [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
        ],
    ]);
}
/**
 * Preview read-only de mantenimiento programado vencido/proximo.
 */
public function mantenimientoProgramadoAction() {
    $dias = (int)$this->getQuery('dias', 30);
    $dias = max(0, min(90, $dias));

    require_once __DIR__ . '/../models/Mantenimiento.php';
    require_once __DIR__ . '/../models/TareaOperativa.php';
    $mantenimientoModel = new Mantenimiento();
    $tareaModel = new TareaOperativa();
    $preview = $mantenimientoModel->previewProgramados($dias);
    $hotelId = (int)$this->hotelIdActual();

    if ($hotelId > 0 && isset($preview['registros']) && is_array($preview['registros'])) {
        foreach ($preview['registros'] as $index => $registro) {
            $mantenimientoId = (int)($registro['id'] ?? 0);
            $preview['registros'][$index]['tareas_vinculadas'] = $mantenimientoId > 0
                ? $tareaModel->listarPorEntidadHotel($hotelId, 'mantenimiento', $mantenimientoId, 3)
                : [];
        }
    }

    View::renderTemplate('reportes/mantenimiento-programado', [
        'title' => 'Mantenimiento programado - ' . current_hotel_display_name(),
        'preview' => $preview,
        'dias' => $dias,
    ]);
}

/**
 * Reporte de Mantenimiento de Habitaciones
 */
public function mantenimientoAction() {
    $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
    $fecha_fin    = $this->getQuery('fecha_fin',    date('Y-m-d'));
    $tipo_filtro  = $this->getQuery('tipo', '');

    // Usar métodos del modelo (mismo patrón que funciona en el resto del controller)
    $estadisticas             = $this->reporteModel->obtenerEstadisticasMantenimientoCompletas($fecha_inicio, $fecha_fin);
    $porTipo                  = $this->reporteModel->obtenerMantenimientosPorTipo($fecha_inicio, $fecha_fin);
    $porPrioridad             = $this->reporteModel->obtenerMantenimientosPorPrioridad($fecha_inicio, $fecha_fin);
    $habitacionesMasMantenimiento = $this->reporteModel->obtenerHabitacionesConMasMantenimientos($fecha_inicio, $fecha_fin);
    $tendenciaMensual         = $this->reporteModel->obtenerTendenciaMensualMantenimiento($fecha_inicio, $fecha_fin);
    $realizadoPorTop          = $this->reporteModel->obtenerTopResponsablesMantenimiento($fecha_inicio, $fecha_fin);
    $ultimosMantenimientos    = $this->reporteModel->obtenerRegistroMantenimientos($tipo_filtro);

    include __DIR__ . '/../views/reportes/mantenimiento.php';
    exit;
}
    /**
     * Reporte de Ingresos vs Gastos
     */
    
    
    /**
     * Reporte de Procedencia de Huéspedes
     */
    public function procedenciaAction() {
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-d', strtotime('-1 month')));
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        
        // Obtener datos
        $porEstado = $this->reporteModel->obtenerProcedenciaPorEstado($fecha_inicio, $fecha_fin);
        $porCiudad = $this->reporteModel->obtenerProcedenciaPorCiudad($fecha_inicio, $fecha_fin);
        $evolucionMensual = $this->reporteModel->obtenerEvolucionProcedencia($fecha_inicio, $fecha_fin);
        
        // Top 10 estados
        $topEstados = array_slice($porEstado, 0, 10);
        
        View::renderTemplate('reportes/procedencia', [
            'title' => 'Reporte de Procedencia de Huéspedes - ' . current_hotel_display_name(),
            'porEstado' => $porEstado,
            'topEstados' => $topEstados,
            'porCiudad' => $porCiudad,
            'evolucionMensual' => $evolucionMensual,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
    
    /**
     * Reporte de Habitaciones Rentables
     */
    public function habitacionesRentablesAction() {
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        
        // Obtener datos
        $rentabilidad = $this->reporteModel->obtenerRentabilidadHabitaciones($fecha_inicio, $fecha_fin);
        $ocupacionPorTipo = $this->reporteModel->obtenerOcupacionPorTipo($fecha_inicio, $fecha_fin);
        $ingresosPromedio = $this->reporteModel->obtenerIngresoPromedioPorHabitacion($fecha_inicio, $fecha_fin);
        
        View::renderTemplate('reportes/habitaciones-rentables', [
            'title' => 'Reporte de Habitaciones más Rentables - ' . current_hotel_display_name(),
            'rentabilidad' => $rentabilidad,
            'ocupacionPorTipo' => $ocupacionPorTipo,
            'ingresosPromedio' => $ingresosPromedio,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
    
    /**
     * Reporte de Tasa de Ocupación
     */
    public function ocupacionAction() {
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        $tipo = $this->getQuery('tipo', 'diario'); // diario, semanal, mensual
        
        // Obtener datos según el tipo
        $datos = [];
        switch($tipo) {
            case 'diario':
                $datos = $this->reporteModel->obtenerOcupacionDiaria($fecha_inicio, $fecha_fin);
                break;
            case 'semanal':
                $datos = $this->reporteModel->obtenerOcupacionSemanal($fecha_inicio, $fecha_fin);
                break;
            case 'mensual':
                $datos = $this->reporteModel->obtenerOcupacionMensual($fecha_inicio, $fecha_fin);
                break;
        }
        
        // Estadísticas generales
        $estadisticas = $this->reporteModel->obtenerEstadisticasOcupacion($fecha_inicio, $fecha_fin);
        $ocupacionPorDia = $this->reporteModel->obtenerOcupacionPorDiaSemana($fecha_inicio, $fecha_fin);
        
        View::renderTemplate('reportes/ocupacion', [
            'title' => 'Reporte de Tasa de Ocupación - ' . current_hotel_display_name(),
            'datos' => $datos,
            'estadisticas' => $estadisticas,
            'ocupacionPorDia' => $ocupacionPorDia,
            'tipo' => $tipo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
    
    /**
     * Reporte de Promedio de Estancia
     */
    public function estanciaAction() {
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        
        // Obtener datos
        $promedioGeneral = $this->reporteModel->obtenerPromedioEstancia($fecha_inicio, $fecha_fin);
        $porTipoHabitacion = $this->reporteModel->obtenerEstanciaPorTipo($fecha_inicio, $fecha_fin);
        $porProcedencia = $this->reporteModel->obtenerEstanciaPorProcedencia($fecha_inicio, $fecha_fin);
        $distribucion = $this->reporteModel->obtenerDistribucionEstancia($fecha_inicio, $fecha_fin);
        $tendenciaMensual = $this->reporteModel->obtenerTendenciaEstancia($fecha_inicio, $fecha_fin);
        
        View::renderTemplate('reportes/estancia', [
            'title' => 'Reporte de Promedio de Estancia - ' . current_hotel_display_name(),
            'promedioGeneral' => $promedioGeneral,
            'porTipoHabitacion' => $porTipoHabitacion,
            'porProcedencia' => $porProcedencia,
            'distribucion' => $distribucion,
            'tendenciaMensual' => $tendenciaMensual,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
    
    /**
     * Reporte de Ranking de Estados
     */
    public function rankingEstadosAction() {
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        
        // Obtener datos
        $ranking = $this->reporteModel->obtenerRankingEstados($fecha_inicio, $fecha_fin);
        $evolucion = $this->reporteModel->obtenerEvolucionEstados($fecha_inicio, $fecha_fin);
        $comparativa = $this->reporteModel->obtenerComparativaEstados($fecha_inicio, $fecha_fin);
        
        View::renderTemplate('reportes/ranking-estados', [
            'title' => 'Ranking de Estados Visitantes - ' . current_hotel_display_name(),
            'ranking' => $ranking,
            'evolucion' => $evolucion,
            'comparativa' => $comparativa,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin
        ]);
    }
    
    /**
     * Exportar reporte a PDF
     */
    
    
    /**
     * Obtener datos para gráficas (AJAX)
     */
    public function datosGraficaAction() {
        if (!$this->isAjax()) {
            $this->redirect('reportes');
            return;
        }
        
        $tipo = $this->getQuery('tipo');
        $default_fecha_inicio = $tipo === 'procedencia' ? date('Y-m-d', strtotime('-1 month')) : date('Y-m-01');
        $fecha_inicio = $this->getQuery('fecha_inicio', $default_fecha_inicio);
        $fecha_fin = $this->getQuery('fecha_fin', date('Y-m-d'));
        
        $datos = [];
        
        switch($tipo) {
            case 'ingresos-gastos':
                $datos = $this->reporteModel->obtenerDatosGraficaIngresosGastos($fecha_inicio, $fecha_fin);
                break;
            case 'ocupacion':
                $datos = $this->reporteModel->obtenerDatosGraficaOcupacion($fecha_inicio, $fecha_fin);
                break;
            case 'procedencia':
                $datos = $this->reporteModel->obtenerDatosGraficaProcedencia($fecha_inicio, $fecha_fin);
                break;
        }
        
        json_response(['success' => true, 'datos' => $datos]);
    }
}
