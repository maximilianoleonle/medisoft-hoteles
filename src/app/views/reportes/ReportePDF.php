<?php
/**
 * Generador de Reportes en PDF
 * Los Cedros
 */

// Cargar TCPDF
require_once dirname(dirname(__DIR__)) . '/libs/TCPDF/tcpdf.php';
require_once dirname(dirname(__DIR__)) . '/services/PropietarioDistribucionService.php';

class ReportePDF extends TCPDF {
    
    private $titulo_reporte;
    private $periodo;
    private $nombre_hotel;
    
    /**
     * Constructor
     */
    public function __construct($titulo = '', $periodo = '') {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $this->titulo_reporte = $titulo;
        $this->periodo = $periodo;
        $this->nombre_hotel = function_exists('current_hotel_display_name')
            ? current_hotel_display_name('Medisoft Hoteles')
            : 'Medisoft Hoteles';
        
        // Configuración del documento
        $this->SetCreator($this->nombre_hotel);
        $this->SetAuthor('Sistema de Gestión Hotelera');
        $this->SetTitle($titulo);
        
        // Márgenes
        $this->SetMargins(15, 30, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);
        
        // Saltos de página automáticos
        $this->SetAutoPageBreak(TRUE, 15);
        
        // Factor de escala de imagen
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }
    
    /**
     * Encabezado del PDF
     */
    public function Header() {
        // Logo
        $logo = dirname(dirname(dirname(__DIR__))) . '/public_html/images/logo-hotel.png';
        if (file_exists($logo)) {
            $this->Image($logo, 15, 10, 30);
        }
        
        // Título
        $this->SetFont('helvetica', 'B', 16);
        $this->SetX(50);
        $this->Cell(0, 10, $this->nombre_hotel, 0, 1, 'L');
        
        $this->SetFont('helvetica', '', 12);
        $this->SetX(50);
        $this->Cell(0, 5, $this->titulo_reporte, 0, 1, 'L');
        
        if ($this->periodo) {
            $this->SetFont('helvetica', 'I', 10);
            $this->SetX(50);
            $this->Cell(0, 5, 'Período: ' . $this->periodo, 0, 1, 'L');
        }
        
        // Línea divisoria
        $this->Line(15, 30, $this->getPageWidth() - 15, 30);
    }
    
    /**
     * Pie de página
     */
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        
        // Número de página
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
        
        // Fecha y hora de generación
        $this->SetX(15);
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i'), 0, 0, 'L');
    }
    
    /**
     * Generar reporte de Ingresos vs Gastos
     */
    public function generarReporteIngresosGastos($datos) {
        $this->AddPage();
        
        // Resumen general
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Resumen General', 0, 1);
        
        $this->SetFont('helvetica', '', 11);
        
        // Verificar que los datos existan
        $totalIngresos = isset($datos['totales']['ingresos']) ? $datos['totales']['ingresos'] : 0;
        $totalGastos = isset($datos['totales']['gastos']) ? $datos['totales']['gastos'] : 0;
        $totalBalance = isset($datos['totales']['balance']) ? $datos['totales']['balance'] : ($totalIngresos - $totalGastos);
        $numIngresos = isset($datos['totales']['num_ingresos']) ? $datos['totales']['num_ingresos'] : 0;
        $numGastos = isset($datos['totales']['num_gastos']) ? $datos['totales']['num_gastos'] : 0;
        
        // Tabla de resumen
        $html = '
        <table cellpadding="5" cellspacing="0" border="0">
            <tr>
                <td width="33%" style="background-color:#d4edda; text-align:center;">
                    <b style="color:#155724;">TOTAL INGRESOS</b><br>
                    <span style="font-size:16pt;">' . $this->formatearMoneda($totalIngresos) . '</span><br>
                    <small>' . $numIngresos . ' transacciones</small>
                </td>
                <td width="33%" style="background-color:#f8d7da; text-align:center;">
                    <b style="color:#721c24;">TOTAL GASTOS</b><br>
                    <span style="font-size:16pt;">' . $this->formatearMoneda($totalGastos) . '</span><br>
                    <small>' . $numGastos . ' transacciones</small>
                </td>
                <td width="33%" style="background-color:#d1ecf1; text-align:center;">
                    <b style="color:#0c5460;">BALANCE</b><br>
                    <span style="font-size:16pt;">' . $this->formatearMoneda($totalBalance) . '</span><br>
                    <small>' . ($totalBalance >= 0 ? 'Ganancia' : 'Pérdida') . '</small>
                </td>
            </tr>
        </table>';
        
        $this->writeHTML($html, true, false, true, false, '');
        
        $this->Ln(10);
        
        // Desglose por categorías si existen
        if (isset($datos['categorias']) && !empty($datos['categorias'])) {
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Desglose por Categorías', 0, 1);
            
            // Ingresos
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(0, 8, 'Ingresos:', 0, 1);
            
            $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                    <thead>
                        <tr style="background-color:#f8f9fa;">
                            <th width="40%"><b>Categoría</b></th>
                            <th width="20%" align="center"><b>Cantidad</b></th>
                            <th width="25%" align="right"><b>Monto</b></th>
                            <th width="15%" align="center"><b>%</b></th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $hayIngresos = false;
            foreach ($datos['categorias'] as $cat) {
                if ($cat['tipo'] == 'ingreso' && $totalIngresos > 0) {
                    $hayIngresos = true;
                    $porcentaje = ($cat['total'] / $totalIngresos) * 100;
                    $html .= '<tr>
                        <td>' . htmlspecialchars($cat['categoria']) . '</td>
                        <td align="center">' . $cat['cantidad'] . '</td>
                        <td align="right">' . $this->formatearMoneda($cat['total']) . '</td>
                        <td align="center">' . number_format($porcentaje, 1) . '%</td>
                    </tr>';
                }
            }
            
            if (!$hayIngresos) {
                $html .= '<tr><td colspan="4" align="center">No hay ingresos registrados</td></tr>';
            }
            
            $html .= '</tbody></table>';
            $this->writeHTML($html, true, false, true, false, '');
            
            $this->Ln(5);
            
            // Gastos
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(0, 8, 'Gastos:', 0, 1);
            
            $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                    <thead>
                        <tr style="background-color:#f8f9fa;">
                            <th width="40%"><b>Categoría</b></th>
                            <th width="20%" align="center"><b>Cantidad</b></th>
                            <th width="25%" align="right"><b>Monto</b></th>
                            <th width="15%" align="center"><b>%</b></th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $hayGastos = false;
            foreach ($datos['categorias'] as $cat) {
                if ($cat['tipo'] == 'gasto' && $totalGastos > 0) {
                    $hayGastos = true;
                    $porcentaje = ($cat['total'] / $totalGastos) * 100;
                    $html .= '<tr>
                        <td>' . htmlspecialchars($cat['categoria']) . '</td>
                        <td align="center">' . $cat['cantidad'] . '</td>
                        <td align="right">' . $this->formatearMoneda($cat['total']) . '</td>
                        <td align="center">' . number_format($porcentaje, 1) . '%</td>
                    </tr>';
                }
            }
            
            if (!$hayGastos) {
                $html .= '<tr><td colspan="4" align="center">No hay gastos registrados</td></tr>';
            }
            
            $html .= '</tbody></table>';
            $this->writeHTML($html, true, false, true, false, '');
        }
        
        // Detalle por período si existe
        if (isset($datos['resultados']) && !empty($datos['resultados'])) {
            // Si hay más páginas de datos
            if (count($datos['resultados']) > 15) {
                $this->AddPage();
            } else {
                $this->Ln(10);
            }
            
            // Detalle por período
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Detalle por Período', 0, 1);
            
            $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:9pt;">
                    <thead>
                        <tr style="background-color:#f8f9fa;">
                            <th width="15%"><b>Período</b></th>
                            <th width="20%" align="right"><b>Ingresos</b></th>
                            <th width="10%" align="center"><b># Ing.</b></th>
                            <th width="20%" align="right"><b>Gastos</b></th>
                            <th width="10%" align="center"><b># Gas.</b></th>
                            <th width="20%" align="right"><b>Balance</b></th>
                            <th width="5%" align="center"><b>Estado</b></th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($datos['resultados'] as $row) {
                $balance = $row['ingresos'] - $row['gastos'];
                $estado = $balance >= 0 ? '✓' : '✗';
                $color_balance = $balance >= 0 ? 'color:#155724;' : 'color:#721c24;';
                
                $html .= '<tr>
                    <td>' . $row['periodo'] . '</td>
                    <td align="right" style="color:#155724;">' . $this->formatearMoneda($row['ingresos']) . '</td>
                    <td align="center">' . $row['num_ingresos'] . '</td>
                    <td align="right" style="color:#721c24;">' . $this->formatearMoneda($row['gastos']) . '</td>
                    <td align="center">' . $row['num_gastos'] . '</td>
                    <td align="right" style="' . $color_balance . '">' . $this->formatearMoneda($balance) . '</td>
                    <td align="center">' . $estado . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
            $this->writeHTML($html, true, false, true, false, '');
        }
    }
    
    /**
     * Generar reporte de Procedencia Geográfica
     */
    public function generarReporteProcedencia($datos) {
        $this->AddPage();
        
        // Resumen
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Resumen de Procedencia', 0, 1);
        
        $this->SetFont('helvetica', '', 11);
        
        // Verificar que los datos existan
        $totalEstados = isset($datos['totales']['estados']) ? $datos['totales']['estados'] : 0;
        $totalReservaciones = isset($datos['totales']['reservaciones']) ? $datos['totales']['reservaciones'] : 0;
        $totalHuespedes = isset($datos['totales']['huespedes']) ? $datos['totales']['huespedes'] : 0;
        $totalIngresos = isset($datos['totales']['ingresos']) ? $datos['totales']['ingresos'] : 0;
        
        $html = '
        <table cellpadding="5" cellspacing="0" border="0">
            <tr>
                <td width="25%" style="background-color:#e8f4f8; text-align:center;">
                    <b>Estados</b><br>
                    <span style="font-size:18pt;">' . $totalEstados . '</span>
                </td>
                <td width="25%" style="background-color:#e8f4f8; text-align:center;">
                    <b>Reservaciones</b><br>
                    <span style="font-size:18pt;">' . number_format($totalReservaciones) . '</span>
                </td>
                <td width="25%" style="background-color:#e8f4f8; text-align:center;">
                    <b>Huéspedes</b><br>
                    <span style="font-size:18pt;">' . number_format($totalHuespedes) . '</span>
                </td>
                <td width="25%" style="background-color:#e8f4f8; text-align:center;">
                    <b>Ingresos</b><br>
                    <span style="font-size:14pt;">' . $this->formatearMoneda($totalIngresos) . '</span>
                </td>
            </tr>
        </table>';
        
        $this->writeHTML($html, true, false, true, false, '');
        
        // Ranking por estados si existe
        if (isset($datos['estados']) && !empty($datos['estados'])) {
            $this->Ln(10);
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Ranking por Estados', 0, 1);
            
            $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                    <thead>
                        <tr style="background-color:#f8f9fa;">
                            <th width="5%">#</th>
                            <th width="25%"><b>Estado</b></th>
                            <th width="15%" align="center"><b>Reservaciones</b></th>
                            <th width="15%" align="center"><b>Huéspedes</b></th>
                            <th width="20%" align="right"><b>Ingresos</b></th>
                            <th width="20%" align="center"><b>Estancia Prom.</b></th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $posicion = 1;
            foreach ($datos['estados'] as $estado) {
                $html .= '<tr>
                    <td align="center">' . $posicion++ . '</td>
                    <td>' . $estado['estado'] . '</td>
                    <td align="center">' . $estado['num_reservaciones'] . '</td>
                    <td align="center">' . $estado['num_huespedes'] . '</td>
                    <td align="right">' . $this->formatearMoneda($estado['total_ingresos']) . '</td>
                    <td align="center">' . number_format($estado['promedio_estancia'], 1) . ' días</td>
                </tr>';
                
                // Nueva página si es necesario
                if ($posicion > 10 && count($datos['estados']) > 15) {
                    $html .= '</tbody></table>';
                    $this->writeHTML($html, true, false, true, false, '');
                    $this->AddPage();
                    $this->SetFont('helvetica', 'B', 12);
                    $this->Cell(0, 10, 'Ranking por Estados (continuación)', 0, 1);
                    $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                            <thead>
                                <tr style="background-color:#f8f9fa;">
                                    <th width="5%">#</th>
                                    <th width="25%"><b>Estado</b></th>
                                    <th width="15%" align="center"><b>Reservaciones</b></th>
                                    <th width="15%" align="center"><b>Huéspedes</b></th>
                                    <th width="20%" align="right"><b>Ingresos</b></th>
                                    <th width="20%" align="center"><b>Estancia Prom.</b></th>
                                </tr>
                            </thead>
                            <tbody>';
                }
            }
            
            $html .= '</tbody></table>';
            $this->writeHTML($html, true, false, true, false, '');
        }
        
        // Ciudades principales
        if (!empty($datos['ciudades'])) {
            $this->Ln(10);
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'Ciudades más Frecuentes', 0, 1);
            
            $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                    <thead>
                        <tr style="background-color:#f8f9fa;">
                            <th width="10%">#</th>
                            <th width="35%"><b>Ciudad</b></th>
                            <th width="35%"><b>Estado</b></th>
                            <th width="20%" align="center"><b>Reservaciones</b></th>
                        </tr>
                    </thead>
                    <tbody>';
            
            $pos = 1;
            foreach (array_slice($datos['ciudades'], 0, 10) as $ciudad) {
                $html .= '<tr>
                    <td align="center">' . $pos++ . '</td>
                    <td>' . $ciudad['ciudad'] . '</td>
                    <td>' . $ciudad['estado'] . '</td>
                    <td align="center">' . $ciudad['num_reservaciones'] . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
            $this->writeHTML($html, true, false, true, false, '');
        }
    }
    
    /**
     * Método genérico para generar tabla
     */
    public function generarTabla($titulo, $columnas, $datos, $anchos = null) {
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, $titulo, 0, 1);
        
        // Si no se especifican anchos, dividir equitativamente
        if (!$anchos) {
            $num_columnas = count($columnas);
            $ancho_columna = 100 / $num_columnas;
            $anchos = array_fill(0, $num_columnas, $ancho_columna . '%');
        }
        
        $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                <thead>
                    <tr style="background-color:#f8f9fa;">';
        
        foreach ($columnas as $i => $columna) {
            $html .= '<th width="' . $anchos[$i] . '"><b>' . $columna . '</b></th>';
        }
        
        $html .= '</tr></thead><tbody>';
        
        foreach ($datos as $fila) {
            $html .= '<tr>';
            foreach ($fila as $celda) {
                $html .= '<td>' . $celda . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $this->writeHTML($html, true, false, true, false, '');
    }
    
    /**
     * Método helper para formatear moneda
     */
    private function formatearMoneda($cantidad) {
        return '$' . number_format($cantidad, 2);
    }
    
    /**
     * Generar sección de Ingresos por Propiedad (MANOLO vs ELIA)
     */
    public function generarSeccionPropiedades($datos, array $configPropietarios = null) {
        $this->AddPage();

        $service = new PropietarioDistribucionService();
        $configPropietarios = $configPropietarios ?: $service->configuracionParaHotel();
        $propietarios = $service->resumenPropietarios(is_array($datos) ? $datos : [], $configPropietarios);
        $totalGeneral = array_sum(array_map(static function ($propietario) {
            return (float) ($propietario['total'] ?? 0);
        }, $propietarios));
        $totalReservas = array_sum(array_map(static function ($propietario) {
            return (int) ($propietario['cantidad'] ?? 0);
        }, $propietarios));

        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Ingresos por Propiedad', 0, 1);

        if ($totalGeneral <= 0 || empty($propietarios)) {
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 8, 'No hay ingresos de hospedaje para mostrar en este periodo.', 0, 1);
            return;
        }

        $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                <thead>
                    <tr style="background-color:#f8f9fa;">
                        <th width="34%"><b>Propietario</b></th>
                        <th width="22%" align="right"><b>Ingresos</b></th>
                        <th width="18%" align="center"><b>Reservas</b></th>
                        <th width="26%" align="right"><b>Participacion</b></th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($propietarios as $propietario) {
            $pct = $totalGeneral > 0 ? ((float) $propietario['total'] / $totalGeneral * 100) : 0;
            $html .= '<tr>
                <td><b>' . htmlspecialchars($propietario['nombre'], ENT_QUOTES, 'UTF-8') . '</b></td>
                <td align="right">' . $this->formatearMoneda($propietario['total']) . '</td>
                <td align="center">' . (int) $propietario['cantidad'] . '</td>
                <td align="right">' . number_format($pct, 1) . '%</td>
            </tr>';
        }

        $html .= '<tr style="background-color:#f8f9fa;">
                <td><b>TOTAL</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalGeneral) . '</b></td>
                <td align="center"><b>' . $totalReservas . '</b></td>
                <td align="right"><b>100%</b></td>
            </tr>';

        $html .= '</tbody></table>';
        $this->writeHTML($html, true, false, true, false, '');
        $this->Ln(8);

        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Desglose por Metodo de Pago', 0, 1);

        $totalEfectivo = array_sum(array_map(static function ($propietario) {
            return (float) ($propietario['datos']['efectivo'] ?? 0);
        }, $propietarios));
        $totalTarjeta = array_sum(array_map(static function ($propietario) {
            return (float) ($propietario['datos']['tarjeta'] ?? 0);
        }, $propietarios));
        $totalTransferencia = array_sum(array_map(static function ($propietario) {
            return (float) ($propietario['datos']['transferencia'] ?? 0);
        }, $propietarios));

        $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                <thead>
                    <tr style="background-color:#f8f9fa;">
                        <th width="34%"><b>Propietario</b></th>
                        <th width="16%" align="right"><b>Efectivo</b></th>
                        <th width="16%" align="right"><b>Tarjeta</b></th>
                        <th width="18%" align="right"><b>Transferencia</b></th>
                        <th width="16%" align="right"><b>Total</b></th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($propietarios as $propietario) {
            $row = $propietario['datos'];
            $html .= '<tr>
                <td><b>' . htmlspecialchars($propietario['nombre'], ENT_QUOTES, 'UTF-8') . '</b></td>
                <td align="right">' . $this->formatearMoneda($row['efectivo'] ?? 0) . '</td>
                <td align="right">' . $this->formatearMoneda($row['tarjeta'] ?? 0) . '</td>
                <td align="right">' . $this->formatearMoneda($row['transferencia'] ?? 0) . '</td>
                <td align="right"><b>' . $this->formatearMoneda($propietario['total']) . '</b></td>
            </tr>';
        }

        $html .= '<tr style="background-color:#f8f9fa;">
                <td><b>TOTAL</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalEfectivo) . '</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalTarjeta) . '</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalTransferencia) . '</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalGeneral) . '</b></td>
            </tr>';

        $html .= '</tbody></table>';
        $this->writeHTML($html, true, false, true, false, '');
    }

    public function generarSeccionPropiedadesLegacy($datos) {
        $this->AddPage();
        
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'Ingresos por Propiedad', 0, 1);
        
        $manolo = $datos['manolo'];
        $elia = $datos['elia'];
        $totalGeneral = $manolo['total'] + $elia['total'];
        $pctM = $totalGeneral > 0 ? ($manolo['total'] / $totalGeneral * 100) : 0;
        $pctE = $totalGeneral > 0 ? ($elia['total'] / $totalGeneral * 100) : 0;
        
        // Cards resumen
        $html = '
        <table cellpadding="5" cellspacing="0" border="0">
            <tr>
                <td width="33%" style="background-color:#f0e6d3; text-align:center;">
                    <b style="color:#654E2C;">MANOLO</b><br>
                    <span style="font-size:16pt; color:#8B6D42;">' . $this->formatearMoneda($manolo['total']) . '</span><br>
                    <small>' . $manolo['reservas'] . ' reservas · ' . number_format($pctM, 1) . '%</small>
                </td>
                <td width="33%" style="background-color:#e8eed8; text-align:center;">
                    <b style="color:#576441;">ELIA</b><br>
                    <span style="font-size:16pt; color:#7A8B5C;">' . $this->formatearMoneda($elia['total']) . '</span><br>
                    <small>' . $elia['reservas'] . ' reservas · ' . number_format($pctE, 1) . '%</small>
                </td>
                <td width="33%" style="background-color:#d1ecf1; text-align:center;">
                    <b style="color:#0c5460;">TOTAL</b><br>
                    <span style="font-size:16pt;">' . $this->formatearMoneda($totalGeneral) . '</span><br>
                    <small>' . ($manolo['reservas'] + $elia['reservas']) . ' reservas</small>
                </td>
            </tr>
        </table>';
        
        $this->writeHTML($html, true, false, true, false, '');
        $this->Ln(8);
        
        // Tabla comparativa por método de pago
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 10, 'Desglose por Método de Pago', 0, 1);
        
        $html = '<table cellpadding="4" cellspacing="0" border="1" style="font-size:10pt;">
                <thead>
                    <tr style="background-color:#f8f9fa;">
                        <th width="25%"><b>Método</b></th>
                        <th width="25%" align="right"><b>Manolo</b></th>
                        <th width="25%" align="right"><b>Elia</b></th>
                        <th width="25%" align="right"><b>Total</b></th>
                    </tr>
                </thead>
                <tbody>';
        
        $metodos = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
        
        foreach ($metodos as $key => $nombre) {
            $m = $manolo[$key] ?? 0;
            $e = $elia[$key] ?? 0;
            $t = $m + $e;
            $html .= '<tr>
                <td>' . $nombre . '</td>
                <td align="right" style="color:#8B6D42;">' . $this->formatearMoneda($m) . '</td>
                <td align="right" style="color:#7A8B5C;">' . $this->formatearMoneda($e) . '</td>
                <td align="right"><b>' . $this->formatearMoneda($t) . '</b></td>
            </tr>';
        }
        
        $html .= '<tr style="background-color:#f8f9fa;">
                <td><b>TOTAL</b></td>
                <td align="right"><b style="color:#8B6D42;">' . $this->formatearMoneda($manolo['total']) . '</b></td>
                <td align="right"><b style="color:#7A8B5C;">' . $this->formatearMoneda($elia['total']) . '</b></td>
                <td align="right"><b>' . $this->formatearMoneda($totalGeneral) . '</b></td>
            </tr>';
        
        $html .= '</tbody></table>';
        $this->writeHTML($html, true, false, true, false, '');
    }
    
    /**
     * Generar y descargar el PDF
     */
    public function descargar($nombre_archivo = 'reporte.pdf') {
        $this->Output($nombre_archivo, 'D');
    }
    
}
