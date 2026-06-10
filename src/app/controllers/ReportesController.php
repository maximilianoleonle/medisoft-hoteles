<?php
/**
 * Controlador de Reportes
 * Sistema hotelero
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReportesController extends Controller {
    private $reporteModel;
    private $reservacionModel;
    private $cajaModel;
    private $huespedModel;
    private $habitacionModel;

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }
    
    public function __construct($router = null) {
        parent::__construct($router);
        
        // Verificar autenticación
        if (!is_authenticated()) {
            redirect('login');
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
    
    // Generar PDF
    $pdf = new ReportePDF('', '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Encabezado con fondo azul
    $pdf->SetFillColor(41, 128, 185);
    $pdf->Rect(0, 0, 210, 35, 'F');
    
    $pdf->SetTextColor(255, 255, 255);
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
                <div style="background-color:#E8F5E9; border:2px solid #4CAF50; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:#2E7D32; font-weight:bold; margin-bottom:5px;">INGRESOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:#1B5E20;">$' . number_format($totales['ingreso'], 2) . '</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:#FFEBEE; border:2px solid #F44336; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:#C62828; font-weight:bold; margin-bottom:5px;">GASTOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:#B71C1C;">$' . number_format($totales['gasto'], 2) . '</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:' . ($utilidad >= 0 ? '#E3F2FD' : '#FFF3E0') . '; border:2px solid ' . ($utilidad >= 0 ? '#2196F3' : '#FF9800') . '; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:' . ($utilidad >= 0 ? '#1565C0' : '#E65100') . '; font-weight:bold; margin-bottom:5px;">UTILIDAD</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . ($utilidad >= 0 ? '#0D47A1' : '#BF360C') . ';">$' . number_format($utilidad, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // Distribución por método de pago
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->Cell(0, 8, 'Distribución por Método de Pago', 0, 1);
    
    $totalEfectivo = $totalesPorMetodo['efectivo']['ingreso'] - $totalesPorMetodo['efectivo']['gasto'];
    $totalTarjeta = $totalesPorMetodo['tarjeta']['ingreso'] - $totalesPorMetodo['tarjeta']['gasto'];
    $totalTransferencia = $totalesPorMetodo['transferencia']['ingreso'] - $totalesPorMetodo['transferencia']['gasto'];
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:#E8F5E9; border:1px solid #4CAF50; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#2E7D32; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:#1B5E20;">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#E3F2FD; border:1px solid #2196F3; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#1565C0; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#0D47A1;">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#F3E5F5; border:1px solid #9C27B0; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#6A1B9A; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#4A148C;">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(8);
    // ═══ SECCIÓN MANOLO vs ELIA (ESTE USUARIO) ═══
    $propiedadesUsuario = $this->obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, $usuario_id, $hotel_id);
    $this->generarSeccionPropiedadesPDF($pdf, $propiedadesUsuario);
    $pdf->Ln(5);
    // DESGLOSE DE HOSPEDAJES POR FECHA
    if (!empty($habitacionesPorFecha)) {
        $pdf->SetFillColor(103, 58, 183);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'DESGLOSE DE HOSPEDAJES POR DÍA', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $numerodia = 1;
        foreach ($habitacionesPorFecha as $fecha => $hospedajes) {
            // Encabezado de fecha con número de día
            $pdf->SetFillColor(236, 239, 241);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 8, 'Día ' . $numerodia . ' - ' . date('d/m/Y', strtotime($fecha)) . ' - ' . $obtenerNombreDia($fecha), 0, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 9);
            
            $html = '<table border="1" cellpadding="4" cellspacing="0">
                <thead>
                    <tr style="background-color:#E1BEE7; color:#4A148C; font-weight:bold;">
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
                $bgColor = $fila % 2 == 0 ? '#FFFFFF' : '#F3E5F5';
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
                    $metodoColor = '#6A1B9A';
                    $metodoBg = '#F3E5F5';
                }
                
                // Si la habitación contiene comas (múltiples habitaciones)
                $habitacionDisplay = $hosp['habitacion'];
                if (strpos($habitacionDisplay, ',') !== false) {
                    $habitacionDisplay = str_replace(',', ', ', $habitacionDisplay);
                }
                
                $html .= '<tr style="background-color:' . $bgColor . ';">
                    <td style="text-align:center; font-weight:bold; font-size:11pt; color:#4A148C;">' . htmlspecialchars($habitacionDisplay) . '</td>
                    <td>' . htmlspecialchars(substr($hosp['descripcion'], 0, 50)) . '</td>
                    <td style="text-align:center;"><span style="background-color:' . $metodoBg . '; color:' . $metodoColor . '; padding:2px 8px; border-radius:10px; font-weight:bold;">' . ucfirst($hosp['metodo']) . '</span></td>
                    <td style="text-align:right; font-weight:bold; color:#2E7D32;">$' . number_format($hosp['monto'], 2) . '</td>
                </tr>';
                $fila++;
            }
            
            $html .= '</tbody>
                <tfoot>
                    <tr style="background-color:#E1BEE7;">
                        <td colspan="3" style="text-align:right; font-weight:bold;">Total del día ' . $numerodia . ':</td>
                        <td style="text-align:right; font-weight:bold; color:#4A148C;">$' . number_format($totalDia, 2) . '</td>
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
        
        $pdf->SetFillColor(103, 58, 183);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'TOTAL HOSPEDAJES: $' . number_format($totalGeneralHospedajes, 2), 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // OTROS MOVIMIENTOS
    if (!empty($otrosMovimientos)) {
        $pdf->Ln(10);
        $pdf->SetFillColor(255, 152, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'OTROS MOVIMIENTOS', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $pdf->SetFont('helvetica', '', 9);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr style="background-color:#FFCC80; color:#E65100; font-weight:bold;">
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
            $bgColor = $fila % 2 == 0 ? '#FFFFFF' : '#FFF3E0';
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
                <tr style="background-color:#FFE0B2;">
                    <td colspan="5" style="text-align:right; font-weight:bold; font-size:11pt;">Total Otros:</td>
                    <td style="text-align:right; font-weight:bold; font-size:12pt; color:#E65100;">$' . number_format($totalOtros, 2) . '</td>
                </tr>
            </tfoot>
        </table>';
        
        $pdf->writeHTML($html, true, false, false, false, '');
    }
    
    // Footer
    $pdf->SetY(-40);
    $pdf->SetFillColor(236, 240, 241);
    $pdf->Rect(0, $pdf->GetY() - 5, 210, 50, 'F');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(52, 73, 94);
    
    $html = '<table cellpadding="5">
        <tr>
            <td width="100%" style="text-align:center;">
                <div style="font-size:10pt; font-weight:bold; color:#2C3E50; margin-bottom:8px;">' . htmlspecialchars(current_hotel_display_name('Medisoft Hoteles'), ENT_QUOTES, 'UTF-8') . ' - Sistema de Gestión</div>
                <div style="font-size:9pt; color:#7F8C8D;">
                    Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs<br>
                    Por: ' . (function_exists('usuario_actual') ? usuario_actual('nombre_completo') : 'Sistema')  . '
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Salida del PDF
    $filename = 'Reporte_Movimientos_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
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

private function generarSeccionPropiedadesPDF($pdf, $propiedades) {
    $manolo = $propiedades['manolo'];
    $elia = $propiedades['elia'];
    $totalGeneral = $manolo['total'] + $elia['total'];
    
    // Si no hay ingresos de hospedaje, no mostrar esta sección
    if ($totalGeneral <= 0) return;
    
    $pctM = ($manolo['total'] / $totalGeneral) * 100;
    $pctE = ($elia['total'] / $totalGeneral) * 100;
    
    $pdf->Ln(5);
    
    // ── TÍTULO DE SECCIÓN ──
    $pdf->SetFillColor(139, 109, 66);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 11, '  INGRESOS POR PROPIEDAD', 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(4);
    
    // ── 3 CARDS: MANOLO | ELIA | TOTAL ──
    $html = '<table cellpadding="0" cellspacing="6">
        <tr>
            <td width="33%">
                <div style="background-color:#F5EFE6; border:2px solid #8B6D42; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:#654E2C; font-weight:bold; letter-spacing:1px;">MANOLO</div>
                    <div style="font-size:20pt; font-weight:bold; color:#8B6D42;">$' . number_format($manolo['total'], 2) . '</div>
                    <div style="font-size:9pt; color:#8B6D42; margin-top:4px;">' . $manolo['reservas'] . ' reservas · ' . number_format($pctM, 1) . '%</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:#EEF2E6; border:2px solid #7A8B5C; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:#576441; font-weight:bold; letter-spacing:1px;">ELIA</div>
                    <div style="font-size:20pt; font-weight:bold; color:#7A8B5C;">$' . number_format($elia['total'], 2) . '</div>
                    <div style="font-size:9pt; color:#7A8B5C; margin-top:4px;">' . $elia['reservas'] . ' reservas · ' . number_format($pctE, 1) . '%</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:#E8EDF3; border:2px solid #4A6FA5; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:9pt; color:#2C4A6E; font-weight:bold; letter-spacing:1px;">TOTAL HOSPEDAJE</div>
                    <div style="font-size:20pt; font-weight:bold; color:#4A6FA5;">$' . number_format($totalGeneral, 2) . '</div>
                    <div style="font-size:9pt; color:#4A6FA5; margin-top:4px;">' . ($manolo['reservas'] + $elia['reservas']) . ' reservas</div>
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
        'efectivo' => ['nombre' => 'Efectivo', 'color' => '#2E7D32', 'bg' => '#E8F5E9'],
        'tarjeta' => ['nombre' => 'Tarjeta', 'color' => '#1565C0', 'bg' => '#E3F2FD'],
        'transferencia' => ['nombre' => 'Transferencia', 'color' => '#6A1B9A', 'bg' => '#F3E5F5']
    ];
    
    $html = '<table border="1" cellpadding="6" cellspacing="0" style="font-size:10pt;">
        <thead>
            <tr style="background-color:#8B6D42; color:#FFFFFF; font-weight:bold;">
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
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : '#FAF8F5';
        
        $html .= '<tr style="background-color:' . $bgColor . ';">
            <td style="font-weight:bold; color:' . $info['color'] . ';">
                <span style="background-color:' . $info['bg'] . '; padding:3px 8px; border-radius:8px;">' . $info['nombre'] . '</span>
            </td>
            <td style="text-align:right; color:#8B6D42; font-weight:bold;">$' . number_format($m, 2) . '</td>
            <td style="text-align:right; color:#7A8B5C; font-weight:bold;">$' . number_format($e, 2) . '</td>
            <td style="text-align:right; font-weight:bold;">$' . number_format($t, 2) . '</td>
        </tr>';
        $fila++;
    }
    
    // Fila de TOTALES
    $html .= '<tr style="background-color:#F5EFE6; font-weight:bold;">
            <td style="font-size:11pt;">TOTAL</td>
            <td style="text-align:right; color:#8B6D42; font-size:11pt;">$' . number_format($manolo['total'], 2) . '</td>
            <td style="text-align:right; color:#7A8B5C; font-size:11pt;">$' . number_format($elia['total'], 2) . '</td>
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
            <td width="15%" style="font-size:9pt; color:#8B6D42; font-weight:bold; text-align:right; padding-right:5px;">Manolo ' . number_format($pctM, 0) . '%</td>
            <td width="70%">
                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr>
                        <td width="' . $manoloWidth . '%" style="background-color:#8B6D42; height:16px;">&nbsp;</td>
                        <td width="' . $eliaWidth . '%" style="background-color:#7A8B5C; height:16px;">&nbsp;</td>
                    </tr>
                </table>
            </td>
            <td width="15%" style="font-size:9pt; color:#7A8B5C; font-weight:bold; padding-left:5px;">Elia ' . number_format($pctE, 0) . '%</td>
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
    
    // Generar PDF
    $pdf = new ReportePDF('', '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // ── ENCABEZADO ──
    $pdf->SetFillColor(107, 68, 35); // hotel-brown
    $pdf->Rect(0, 0, 210, 35, 'F');
    
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetY(8);
    $pdf->Cell(0, 10, 'INGRESOS VS GASTOS', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, 'Hotel San Nicolás', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(45);
    
    // ── CARDS DE RESUMEN ──
    $html = '<table cellpadding="0" cellspacing="8">
        <tr>
            <td width="33%">
                <div style="background-color:#E8F5E9; border:2px solid #4CAF50; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:#2E7D32; font-weight:bold;">INGRESOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:#1B5E20;">$' . number_format($totalIngresos, 2) . '</div>
                </div>
            </td>
            <td width="33%">
                <div style="background-color:#FFEBEE; border:2px solid #F44336; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:#C62828; font-weight:bold;">GASTOS</div>
                    <div style="font-size:20pt; font-weight:bold; color:#B71C1C;">$' . number_format($totalGastos, 2) . '</div>
                </div>
            </td>
            <td width="34%">
                <div style="background-color:' . ($utilidad >= 0 ? '#E3F2FD' : '#FFF3E0') . '; border:2px solid ' . ($utilidad >= 0 ? '#2196F3' : '#FF9800') . '; border-radius:10px; padding:20px; text-align:center;">
                    <div style="font-size:11pt; color:' . ($utilidad >= 0 ? '#1565C0' : '#E65100') . '; font-weight:bold;">UTILIDAD</div>
                    <div style="font-size:20pt; font-weight:bold; color:' . ($utilidad >= 0 ? '#0D47A1' : '#BF360C') . ';">$' . number_format($utilidad, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // ── DISTRIBUCIÓN POR MÉTODO DE PAGO ──
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->Cell(0, 8, 'Distribución por Método de Pago', 0, 1);
    
    $totalEfectivo = ($metodosPago['efectivo']['ingresos'] ?? 0) - ($metodosPago['efectivo']['gastos'] ?? 0);
    $totalTarjeta = ($metodosPago['tarjeta']['ingresos'] ?? 0) - ($metodosPago['tarjeta']['gastos'] ?? 0);
    $totalTransferencia = ($metodosPago['transferencia']['ingresos'] ?? 0) - ($metodosPago['transferencia']['gastos'] ?? 0);
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:#E8F5E9; border:1px solid #4CAF50; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#2E7D32; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:#1B5E20;">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#E3F2FD; border:1px solid #2196F3; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#1565C0; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#0D47A1;">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#F3E5F5; border:1px solid #9C27B0; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#6A1B9A; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#4A148C;">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // ── DESGLOSE DE INGRESOS POR CATEGORÍA ──
    if (!empty($datos['ingresos'])) {
        $pdf->SetFillColor(76, 175, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 9, '  DESGLOSE DE INGRESOS', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="font-size:10pt;">
            <thead>
                <tr style="background-color:#A5D6A7; color:#1B5E20; font-weight:bold;">
                    <th width="50%">Categoría</th>
                    <th width="20%" style="text-align:center;">Movimientos</th>
                    <th width="30%" style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($datos['ingresos'] as $i => $cat) {
            $bg = $i % 2 == 0 ? '#FFFFFF' : '#F1F8E9';
            $html .= '<tr style="background-color:' . $bg . ';">
                <td>' . htmlspecialchars($cat['categoria'] ?? 'Sin categoría') . '</td>
                <td style="text-align:center;">' . ($cat['cantidad'] ?? 0) . '</td>
                <td style="text-align:right; font-weight:bold; color:#2E7D32;">$' . number_format($cat['total'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->Ln(3);
    }
    
    // ── DESGLOSE DE GASTOS POR CATEGORÍA ──
    if (!empty($datos['gastos'])) {
        $pdf->SetFillColor(244, 67, 54);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 9, '  DESGLOSE DE GASTOS', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="font-size:10pt;">
            <thead>
                <tr style="background-color:#EF9A9A; color:#B71C1C; font-weight:bold;">
                    <th width="50%">Categoría</th>
                    <th width="20%" style="text-align:center;">Movimientos</th>
                    <th width="30%" style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($datos['gastos'] as $i => $cat) {
            $bg = $i % 2 == 0 ? '#FFFFFF' : '#FFEBEE';
            $html .= '<tr style="background-color:' . $bg . ';">
                <td>' . htmlspecialchars($cat['categoria'] ?? 'Sin categoría') . '</td>
                <td style="text-align:center;">' . ($cat['cantidad'] ?? 0) . '</td>
                <td style="text-align:right; font-weight:bold; color:#C62828;">$' . number_format($cat['total'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, false, false, '');
    }
    
    // ═══════════════════════════════════════════
    // ═══ SECCIÓN MANOLO vs ELIA ═══
    // ═══════════════════════════════════════════
    $propiedades = $this->obtenerIngresosPorPropiedad($fecha_inicio, $fecha_fin, null, $hotel_id);
    $this->generarSeccionPropiedadesPDF($pdf, $propiedades);
    
    // ── FOOTER ──
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 9);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->Cell(0, 5, 'Hotel San Nicolás - Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs', 0, 1, 'C');
    
    $filename = 'Ingresos_Gastos_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
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
    
    // Generar PDF
    $pdf = new ReportePDF('', '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    
    // Encabezado
    $pdf->SetFillColor(41, 128, 185);
    $pdf->Rect(0, 0, 210, 35, 'F');
    
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetY(8);
    $pdf->Cell(0, 10, 'REPORTE DE INGRESOS TOTALES', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 6, current_hotel_display_name('Medisoft Hoteles'), 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(45);
    
    // Cards de resumen
    $html = '<table cellpadding="0" cellspacing="8">
        <tr>
            <td width="25%">
                <div style="background-color:#E8F5E9; border:2px solid #4CAF50; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:#2E7D32; font-weight:bold;">INGRESOS</div>
                    <div style="font-size:18pt; font-weight:bold; color:#1B5E20;">$' . number_format($totalesGenerales['ingreso'], 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:#FFEBEE; border:2px solid #F44336; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:#C62828; font-weight:bold;">GASTOS</div>
                    <div style="font-size:18pt; font-weight:bold; color:#B71C1C;">$' . number_format($totalesGenerales['gasto'], 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:' . ($utilidadTotal >= 0 ? '#E3F2FD' : '#FFF3E0') . '; border:2px solid ' . ($utilidadTotal >= 0 ? '#2196F3' : '#FF9800') . '; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:' . ($utilidadTotal >= 0 ? '#1565C0' : '#E65100') . '; font-weight:bold;">UTILIDAD</div>
                    <div style="font-size:18pt; font-weight:bold; color:' . ($utilidadTotal >= 0 ? '#0D47A1' : '#BF360C') . ';">$' . number_format($utilidadTotal, 2) . '</div>
                </div>
            </td>
            <td width="25%">
                <div style="background-color:#F3E5F5; border:2px solid #9C27B0; border-radius:10px; padding:15px; text-align:center;">
                    <div style="font-size:10pt; color:#6A1B9A; font-weight:bold;">DÍAS</div>
                    <div style="font-size:18pt; font-weight:bold; color:#4A148C;">' . count($movimientosPorDia) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(5);
    
    // Distribución por método
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(52, 73, 94);
    $pdf->Cell(0, 8, 'Distribución por Método de Pago', 0, 1);
    
    $totalEfectivo = $totalesPorMetodo['efectivo']['ingreso'] - $totalesPorMetodo['efectivo']['gasto'];
    $totalTarjeta = $totalesPorMetodo['tarjeta']['ingreso'] - $totalesPorMetodo['tarjeta']['gasto'];
    $totalTransferencia = $totalesPorMetodo['transferencia']['ingreso'] - $totalesPorMetodo['transferencia']['gasto'];
    
    $html = '<table cellpadding="5" cellspacing="2">
        <tr>
            <td width="30%">
                <div style="background-color:#E8F5E9; border:1px solid #4CAF50; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#2E7D32; font-weight:bold;">EFECTIVO</div>
                    <div style="font-size:14pt; font-weight:bold; color:#1B5E20;">$' . number_format($totalEfectivo, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#E3F2FD; border:1px solid #2196F3; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#1565C0; font-weight:bold;">TARJETA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#0D47A1;">$' . number_format($totalTarjeta, 2) . '</div>
                </div>
            </td>
            <td width="5%"></td>
            <td width="30%">
                <div style="background-color:#F3E5F5; border:1px solid #9C27B0; padding:10px; text-align:center; border-radius:5px;">
                    <div style="font-size:9pt; color:#6A1B9A; font-weight:bold;">TRANSFERENCIA</div>
                    <div style="font-size:14pt; font-weight:bold; color:#4A148C;">$' . number_format($totalTransferencia, 2) . '</div>
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    $pdf->Ln(8);
    
    
    
    // Resumen por usuario
    $pdf->SetFillColor(52, 152, 219);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'RESUMEN POR USUARIO', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 9);
    $html = '<table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr style="background-color:#AED6F1; color:#1B4F72; font-weight:bold;">
                <th width="40%">Usuario</th>
                <th width="20%" style="text-align:right;">Ingresos</th>
                <th width="20%" style="text-align:right;">Gastos</th>
                <th width="20%" style="text-align:right;">Utilidad</th>
            </tr>
        </thead>
        <tbody>';
    
    $fila = 0;
    foreach ($totalesPorUsuario as $usuario => $totales) {
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : '#EBF5FB';
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
    $pdf->SetFillColor(76, 175, 80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'RESUMEN POR CATEGORÍA', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 9);
    $html = '<table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr style="background-color:#A5D6A7; color:#1B5E20; font-weight:bold;">
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
        $bgColor = $fila % 2 == 0 ? '#FFFFFF' : '#E8F5E9';
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
    $this->generarSeccionPropiedadesPDF($pdf, $propiedades);
    
    // Nueva página para detalle diario
    $pdf->AddPage();
    
    // Detalle por día
    $pdf->SetFillColor(103, 58, 183);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'DETALLE DIARIO DE MOVIMIENTOS', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    $numeroDia = 1;
    foreach ($movimientosPorDia as $fecha => $dataDia) {
        $pdf->SetFillColor(236, 239, 241);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, 'Día ' . $numeroDia . ' - ' . date('d/m/Y', strtotime($fecha)) . ' - ' . $obtenerNombreDia($fecha), 0, 1, 'L', true);
        
        $pdf->SetFont('helvetica', '', 8);
        $html = '<table border="1" cellpadding="3" cellspacing="0">
            <thead>
                <tr style="background-color:#E1BEE7; color:#4A148C; font-weight:bold;">
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
                <tr style="background-color:#E1BEE7;">
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
    $pdf->SetFillColor(236, 240, 241);
    $pdf->Rect(0, $pdf->GetY() - 5, 210, 50, 'F');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(52, 73, 94);
    
    $html = '<table cellpadding="5">
        <tr>
            <td width="100%" style="text-align:center;">
                <div style="font-size:10pt; font-weight:bold; color:#2C3E50; margin-bottom:8px;">' . htmlspecialchars(current_hotel_display_name('Medisoft Hoteles'), ENT_QUOTES, 'UTF-8') . ' - Sistema de Gestión</div>
                <div style="font-size:9pt; color:#7F8C8D;">
                    Reporte generado el ' . date('d/m/Y') . ' a las ' . date('H:i') . ' hrs<br>
                    Por: ' . (function_exists('usuario_actual') ? usuario_actual('nombre_completo') : 'Sistema')  . '
                </div>
            </td>
        </tr>
    </table>';
    
    $pdf->writeHTML($html, true, false, false, false, '');
    
    // Salida del PDF
    $filename = 'Reporte_Ingresos_Totales_' . date('Y-m-d_His') . '.pdf';
    $pdf->Output($filename, 'D');
    exit;
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
$fecha_inicio = $this->getQuery('fecha_inicio', '2025-01-01'); // Desde enero 2025
    $fecha_fin = $this->getQuery('fecha_fin', '2025-12-31'); // Hasta diciembre 2025
        
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
        $fecha_inicio = $this->getQuery('fecha_inicio', date('Y-m-01'));
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
