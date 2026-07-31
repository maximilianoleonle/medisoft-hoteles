<?php
/**
 * Generador de PDF para Corte de Caja
 * 
 * Diseño simple con letras grandes - Paleta Olivo + Café
 * Bloques por propietario SOLO si el hotel configuró dueños (multi-dueño)
 * 
 * Usa TCPDF (app/helpers/tcpdf/)
 */

require_once ROOT_PATH . '/app/helpers/tcpdf/tcpdf.php';
require_once ROOT_PATH . '/app/services/PropietarioDistribucionService.php';

class ReporteCortePDF extends TCPDF {
    
    // === COLORES ===
    private $olivo      = [107, 122, 80];
    private $olivoClaro = [142, 158, 111];
    private $cafe       = [139, 109, 66];
    private $cafeClaro  = [178, 153, 110];
    private $crema      = [252, 250, 245];
    private $cremaOsc   = [235, 228, 215];
    private $oscuro     = [51, 51, 51];
    private $gris       = [120, 120, 120];
    private $verdeOk    = [34, 139, 34];
    private $rojoGasto  = [180, 40, 40];
    private $azulTj     = [37, 99, 180];
    private $moradoTr   = [110, 60, 190];
    
    private $corteId = '';
    private $fechaCorta = '';
    private $nombreHotel = 'Medisoft Hoteles';
    
    // =================================================================
    // HEADER
    // =================================================================
    public function Header() {
        $this->c_fill($this->olivo);
        $this->Rect(0, 0, 216, 25, 'F');
        $this->c_fill($this->cafe);
        $this->Rect(0, 25, 216, 2, 'F');
        
        $this->SetY(4);
        $this->SetFont('helvetica', 'B', 22);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'CORTE DE CAJA', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(230, 230, 220);
        $this->Cell(0, 5, $this->fechaCorta . '   -   ' . $this->nombreHotel . '   -   Corte #' . $this->corteId, 0, 1, 'C');
        
        $this->SetY(30);
    }
    
    // =================================================================
    // FOOTER
    // =================================================================
    public function Footer() {
        $this->SetY(-12);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(160, 160, 160);
        $this->Cell(95, 5, $this->nombreHotel, 0, 0, 'L');
        $this->Cell(95, 5, 'Pág. ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
    
    // =================================================================
    // HELPERS (nombres que no chocan con TCPDF)
    // =================================================================
    private function c_text($c) { $this->SetTextColor($c[0], $c[1], $c[2]); }
    private function c_fill($c) { $this->SetFillColor($c[0], $c[1], $c[2]); }
    private function c_draw($c) { $this->SetDrawColor($c[0], $c[1], $c[2]); }

    private function aplicarBrandingHotel() {
        $this->nombreHotel = function_exists('current_hotel_display_name')
            ? current_hotel_display_name('Medisoft Hoteles')
            : 'Medisoft Hoteles';

        $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
        if (!is_array($branding)) {
            $branding = [];
        }

        $primary = function_exists('hotel_branding_hex') ? hotel_branding_hex($branding['color_primary'] ?? null, '#1B2746') : '#1B2746';
        $secondary = function_exists('hotel_branding_hex') ? hotel_branding_hex($branding['color_secondary'] ?? null, '#0F172A') : '#0F172A';
        $accent = function_exists('hotel_branding_hex') ? hotel_branding_hex($branding['color_accent'] ?? null, '#BD9441') : '#BD9441';
        $palette = function_exists('hotel_branding_safe_palette')
            ? hotel_branding_safe_palette($primary, $secondary, $accent)
            : [
                '--brand-primary' => $primary,
                '--brand-secondary' => $secondary,
                '--brand-accent' => $accent,
                '--brand-surface-soft' => '#FCFAF5',
                '--brand-line' => '#EBE4D7',
                '--brand-muted' => '#667085',
            ];

        $this->olivo = $this->hexToRgb($palette['--brand-primary'] ?? $primary);
        $this->olivoClaro = $this->hexToRgb($this->mixHex($palette['--brand-primary'] ?? $primary, '#FFFFFF', 0.72));
        $this->cafe = $this->hexToRgb($palette['--brand-accent'] ?? $accent);
        $this->cafeClaro = $this->hexToRgb($this->mixHex($palette['--brand-accent'] ?? $accent, '#FFFFFF', 0.68));
        $this->crema = $this->hexToRgb($palette['--brand-surface-soft'] ?? '#FCFAF5');
        $this->cremaOsc = $this->hexToRgb($palette['--brand-line'] ?? '#EBE4D7');
        $this->oscuro = $this->hexToRgb($palette['--brand-secondary'] ?? $secondary);
        $this->gris = $this->hexToRgb($palette['--brand-muted'] ?? '#667085');
    }

    private function hexToRgb($hex) {
        $hex = trim((string) $hex);
        if (function_exists('hotel_branding_hex')) {
            $hex = hotel_branding_hex($hex, '#111827');
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            $hex = '#111827';
        }

        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function mixHex($hex, $target, $ratio) {
        $ratio = max(0, min(1, (float) $ratio));
        $a = $this->hexToRgb($hex);
        $b = $this->hexToRgb($target);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($a[0] * $ratio + $b[0] * (1 - $ratio)),
            (int) round($a[1] * $ratio + $b[1] * (1 - $ratio)),
            (int) round($a[2] * $ratio + $b[2] * (1 - $ratio))
        );
    }
    
    private function titulo($texto, $color = null) {
        if (!$color) $color = $this->olivo;
        if ($this->GetY() > $this->getPageHeight() - 40) $this->AddPage();
        
        $this->Ln(5);
        $y = $this->GetY();
        $this->c_fill($color);
        $this->Rect(10, $y, 190, 9, 'F');
        $this->SetXY(10, $y + 1);
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(190, 7, '   ' . $texto, 0, 1, 'L');
        $this->Ln(3);
    }
    
    private function lineaDivisora() {
        $this->c_draw($this->cremaOsc);
        $this->SetLineWidth(0.3);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
    }
    
    private function cardGrande($x, $y, $w, $valor, $label, $borderColor, $valorColor) {
        $this->c_draw($borderColor);
        $this->SetLineWidth(1.2);
        $this->RoundedRect($x, $y, $w, 26, 3, '1111', 'D');
        $this->SetLineWidth(0.2);
        
        $this->SetXY($x, $y + 3);
        $this->SetFont('helvetica', 'B', 16);
        $this->c_text($valorColor);
        $this->Cell($w, 10, $valor, 0, 0, 'C');
        
        $this->SetXY($x, $y + 15);
        $this->SetFont('helvetica', 'B', 8);
        $this->c_text($this->gris);
        $this->Cell($w, 5, strtoupper($label), 0, 0, 'C');
    }
    
    // =================================================================
    // MÉTODO PRINCIPAL
    // =================================================================
    public function generar($corte, $resumen, $movimientos, $ingresosPorTipo, $efectivoContado, $observaciones = '') {
        
        $this->corteId = $corte['id'] ?? '0';
        $this->fechaCorta = date('d/m/Y', strtotime($corte['fecha_apertura'] ?? 'now'));
        $this->aplicarBrandingHotel();
        
        $fecha_apertura = date('d/m/Y H:i', strtotime($corte['fecha_apertura'] ?? 'now'));
        $fecha_cierre = date('d/m/Y H:i', strtotime($corte['fecha_cierre'] ?? 'now'));
        
        $monto_inicial = $resumen['monto_inicial'] ?? 0;
        $total_ingresos = $resumen['ingresos']['total'] ?? 0;
        $total_gastos = $resumen['gastos']['total'] ?? 0;
        $efectivo_esperado = $resumen['efectivo_en_caja'] ?? 0;
        $ganancia_neta = $total_ingresos - $total_gastos;
        
        $usuario_apertura = $corte['usuario_apertura'] ?? 'N/A';
        $usuario_cierre = $corte['usuario_cierre'] ?? $corte['usuario_apertura'] ?? 'N/A';
        
        $this->SetCreator($this->nombreHotel);
        $this->SetTitle('Corte de Caja #' . $this->corteId);
        $this->SetMargins(10, 32, 10);
        $this->SetAutoPageBreak(true, 16);
        $this->AddPage();
        
        // =============================================================
        // QUIÉN ABRIÓ / QUIÉN CERRÓ
        // =============================================================
        $y = $this->GetY();
        $this->c_fill($this->crema);
        $this->c_draw($this->cremaOsc);
        $this->SetLineWidth(0.5);
        $this->RoundedRect(10, $y, 190, 16, 3, '1111', 'DF');
        $this->SetLineWidth(0.2);
        
        $this->SetXY(15, $y + 2);
        $this->SetFont('helvetica', '', 8);
        $this->c_text($this->gris);
        $this->Cell(90, 4, 'ABRIÓ', 0, 0, 'L');
        $this->Cell(90, 4, 'CERRÓ', 0, 1, 'L');
        
        $this->SetX(15);
        $this->SetFont('helvetica', 'B', 11);
        $this->c_text($this->oscuro);
        $this->Cell(90, 6, $usuario_apertura . '  (' . $fecha_apertura . ')', 0, 0, 'L');
        $this->Cell(90, 6, $usuario_cierre . '  (' . $fecha_cierre . ')', 0, 1, 'L');
        
        $this->SetY($y + 20);
        
        // =============================================================
        // RESUMEN PRINCIPAL - 5 Cards
        // =============================================================
        $y = $this->GetY();
        $cw = 37; // card width
        $g = 2.5; // gap
        
        $this->cardGrande(10, $y, $cw,
            '$' . number_format($total_ingresos, 2), 'Total Cobrado',
            $this->verdeOk, $this->verdeOk);
        
        $this->cardGrande(10 + ($cw + $g), $y, $cw,
            '$' . number_format($total_gastos, 2), 'Total Gastos',
            $this->rojoGasto, $this->rojoGasto);
        
        $gColor = $ganancia_neta >= 0 ? $this->verdeOk : $this->rojoGasto;
        $this->cardGrande(10 + ($cw + $g) * 2, $y, $cw,
            '$' . number_format($ganancia_neta, 2), 'Ganancia Neta',
            $this->olivo, $gColor);
        
        $this->cardGrande(10 + ($cw + $g) * 3, $y, $cw,
            '$' . number_format($efectivo_esperado, 2), 'Efectivo Esperado',
            $this->cafe, $this->cafe);
        
        $this->cardGrande(10 + ($cw + $g) * 4, $y, $cw - 2,
            '$' . number_format($monto_inicial, 2), 'Fondo Inicial',
            $this->gris, $this->oscuro);
        
        $this->SetY($y + 30);
        
        // =============================================================
        // ¿CÓMO SE COBRÓ?
        // =============================================================
        $this->titulo('¿CÓMO SE COBRÓ?');
        
        $this->c_fill([70, 70, 70]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(80, 9, '  MÉTODO DE PAGO', 0, 0, 'L', true);
        $this->Cell(55, 9, 'COBROS', 0, 0, 'R', true);
        $this->Cell(55, 9, 'GASTOS', 0, 1, 'R', true);
        
        $metodos = [
            'efectivo'      => ['Efectivo', $this->verdeOk],
            'tarjeta'       => ['Tarjeta', $this->azulTj],
            'transferencia' => ['Transferencia', $this->moradoTr],
        ];
        
        $fill = false;
        foreach ($metodos as $key => $info) {
            $ing = $resumen['ingresos'][$key]['total'] ?? 0;
            $gas = $resumen['gastos'][$key]['total'] ?? 0;
            
            $this->SetFillColor($fill ? $this->crema[0] : 255, $fill ? $this->crema[1] : 255, $fill ? $this->crema[2] : 255);
            
            $this->SetFont('helvetica', 'B', 11);
            $this->c_text($info[1]);
            $this->Cell(80, 9, '     ' . $info[0], 0, 0, 'L', true);
            
            $this->c_text($this->verdeOk);
            $this->Cell(55, 9, '$' . number_format($ing, 2) . '  ', 0, 0, 'R', true);
            
            $this->c_text($this->rojoGasto);
            $this->Cell(55, 9, '$' . number_format($gas, 2) . '  ', 0, 1, 'R', true);
            
            $this->lineaDivisora();
            $fill = !$fill;
        }
        
        // TOTALES
        $this->c_fill($this->crema);
        $this->c_text($this->oscuro);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(80, 10, '  TOTAL', 0, 0, 'L', true);
        $this->c_text($this->verdeOk);
        $this->Cell(55, 10, '$' . number_format($total_ingresos, 2) . '  ', 0, 0, 'R', true);
        $this->c_text($this->rojoGasto);
        $this->Cell(55, 10, '$' . number_format($total_gastos, 2) . '  ', 0, 1, 'R', true);
        
        $this->Ln(2);
        
        $this->renderPropietariosDinamicos($ingresosPorTipo, true);
        
        // =============================================================
        // OTROS INGRESOS
        // =============================================================
        if (isset($ingresosPorTipo['_otros']) && ($ingresosPorTipo['_otros']['total'] ?? 0) > 0) {
            $this->Ln(2);
            $y = $this->GetY();
            $this->c_fill($this->crema);
            $this->c_draw($this->cremaOsc);
            $this->SetLineWidth(0.8);
            $this->RoundedRect(10, $y, 190, 14, 3, '1111', 'DF');
            $this->SetLineWidth(0.2);
            $this->SetXY(10, $y + 2);
            $this->SetFont('helvetica', 'B', 11);
            $this->c_text($this->oscuro);
            $this->Cell(120, 10, '   Otros cobros (sin habitación)', 0, 0, 'L');
            $this->c_text($this->verdeOk);
            $this->SetFont('helvetica', 'B', 14);
            $this->Cell(70, 10, '$' . number_format($ingresosPorTipo['_otros']['total'], 2), 0, 1, 'R');
            $this->Ln(2);
        }
        
        // =============================================================
        // EFECTIVO ESPERADO + GANANCIA NETA (recuadro destacado)
        // =============================================================
        $this->Ln(3);
        $y = $this->GetY();
        if ($y > $this->getPageHeight() - 35) $this->AddPage();
        $y = $this->GetY();
        
        // Card izquierda: Efectivo esperado
        $this->c_fill($this->crema);
        $this->c_draw($this->cafe);
        $this->SetLineWidth(1);
        $this->RoundedRect(10, $y, 93, 22, 3, '1111', 'DF');
        $this->SetLineWidth(0.2);
        $this->SetXY(10, $y + 2);
        $this->SetFont('helvetica', 'B', 8);
        $this->c_text($this->gris);
        $this->Cell(93, 5, 'EFECTIVO ESPERADO EN CAJA', 0, 0, 'C');
        $this->SetXY(10, $y + 9);
        $this->SetFont('helvetica', 'B', 18);
        $this->c_text($this->cafe);
        $this->Cell(93, 10, '$' . number_format($efectivo_esperado, 2), 0, 0, 'C');
        
        // Card derecha: Ganancia neta
        $gNetaColor = $ganancia_neta >= 0 ? $this->verdeOk : $this->rojoGasto;
        $gBgColor = $ganancia_neta >= 0 ? [240, 253, 240] : [254, 242, 242];
        $this->c_fill($gBgColor);
        $this->c_draw($gNetaColor);
        $this->SetLineWidth(1);
        $this->RoundedRect(107, $y, 93, 22, 3, '1111', 'DF');
        $this->SetLineWidth(0.2);
        $this->SetXY(107, $y + 2);
        $this->SetFont('helvetica', 'B', 8);
        $this->c_text($this->gris);
        $this->Cell(93, 5, 'GANANCIA NETA (COBROS - GASTOS)', 0, 0, 'C');
        $this->SetXY(107, $y + 9);
        $this->SetFont('helvetica', 'B', 18);
        $this->c_text($gNetaColor);
        $this->Cell(93, 10, '$' . number_format($ganancia_neta, 2), 0, 0, 'C');
        
        $this->SetY($y + 27);
        
        // =============================================================
        // OBSERVACIONES
        // =============================================================
        if (!empty($observaciones)) {
            $this->Ln(2);
            $y = $this->GetY();
            $this->SetFillColor(255, 250, 230);
            $this->SetDrawColor(240, 200, 100);
            $this->SetLineWidth(0.8);
            $this->RoundedRect(10, $y, 190, 14, 3, '1111', 'DF');
            $this->SetLineWidth(0.2);
            $this->SetXY(12, $y + 2);
            $this->SetFont('helvetica', 'B', 10);
            $this->c_text($this->oscuro);
            $this->Cell(35, 4, 'Nota:', 0, 0, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->Cell(148, 4, mb_substr($observaciones, 0, 70), 0, 1, 'L');
            $this->Ln(2);
        }
        
        // =============================================================
        // DETALLE DE MOVIMIENTOS
        // =============================================================
        if (!empty($movimientos)) {
            $this->titulo('TODOS LOS MOVIMIENTOS DEL DÍA');
            $this->headerMovimientos();
            
            $fill = false;
            foreach ($movimientos as $mov) {
                if ($this->GetY() > $this->getPageHeight() - 22) {
                    $this->AddPage();
                    $this->headerMovimientos();
                    $fill = false;
                }
                
                $esIngreso = ($mov['tipo'] == 'ingreso');
                $signo = $esIngreso ? '+' : '-';
                $colorTipo = $esIngreso ? $this->verdeOk : $this->rojoGasto;
                
                // Verificar si es devolución para mostrar razón
                $esDevolucion = (!$esIngreso && stripos($mov['categoria'] ?? '', 'Devolucion') !== false);
                $razonCancelacion = '';
                if ($esDevolucion && !empty($mov['referencia'])) {
                    $razonCancelacion = $mov['referencia'];
                }
                
                $this->SetFillColor($fill ? $this->crema[0] : 255, $fill ? $this->crema[1] : 255, $fill ? $this->crema[2] : 255);
                
                $this->SetFont('helvetica', '', 9);
                $this->c_text($this->oscuro);
                $this->Cell(18, 7, date('H:i', strtotime($mov['created_at'])), 0, 0, 'C', true);
                
                $this->SetFont('helvetica', 'B', 9);
                $this->c_text($colorTipo);
                $etiqueta = $esIngreso ? 'COBRO' : 'GASTO';
                $this->Cell(20, 7, $etiqueta, 0, 0, 'C', true);
                
                $this->SetFont('helvetica', '', 9);
                $this->c_text($this->oscuro);
                $this->Cell(62, 7, mb_substr($mov['descripcion'] ?? '', 0, 30), 0, 0, 'L', true);
                
                $met = $mov['metodo_pago'] ?? '';
                if ($met == 'efectivo') { $this->c_text($this->verdeOk); }
                elseif ($met == 'tarjeta') { $this->c_text($this->azulTj); }
                else { $this->c_text($this->moradoTr); }
                $this->SetFont('helvetica', 'B', 9);
                $this->Cell(30, 7, strtoupper($met), 0, 0, 'C', true);
                
                $this->c_text($colorTipo);
                $this->SetFont('helvetica', 'B', 10);
                $this->Cell(60, 7, $signo . ' $' . number_format($mov['monto'] ?? 0, 2), 0, 1, 'R', true);
                
                // Si es devolución, mostrar razón en línea adicional
                if ($esDevolucion && !empty($razonCancelacion)) {
                    $this->SetFillColor($fill ? $this->crema[0] : 255, $fill ? $this->crema[1] : 255, $fill ? $this->crema[2] : 255);
                    $this->SetFont('helvetica', 'I', 8);
                    $this->c_text($this->rojoGasto);
                    $this->Cell(18, 5, '', 0, 0, 'C', true);
                    $this->Cell(172, 5, '   Razón: ' . mb_substr($razonCancelacion, 0, 80), 0, 1, 'L', true);
                }
                
                $fill = !$fill;
            }
        }
        
        // =============================================================
        // GUARDAR PDF
        // =============================================================
        $directorio = ROOT_PATH . '/app/uploads/reportes';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        $nombreArchivo = function_exists('hotel_export_filename')
            ? hotel_export_filename('corte_caja_' . date('Y-m-d', strtotime($corte['fecha_apertura'])), 'pdf')
            : sprintf('corte_caja_%s_%s.pdf',
                date('Y-m-d', strtotime($corte['fecha_apertura'])),
                date('His'));
        
        $rutaCompleta = $directorio . '/' . $nombreArchivo;
        $this->Output($rutaCompleta, 'F');
        
        error_log("PDF de corte generado: $rutaCompleta");
        return $rutaCompleta;
    }
    
    private function renderPropietariosDinamicos($ingresosPorTipo, $contarHabitaciones = false) {
        if (!class_exists('PropietarioDistribucionService') || !is_array($ingresosPorTipo)) {
            return false;
        }

        $service = new PropietarioDistribucionService();
        $propietarios = $service->resumenPropietarios($ingresosPorTipo, $service->configuracionParaHotel());

        if (empty($propietarios)) {
            return false;
        }

        $colores = [
            [$this->cafe, $this->cafeClaro],
            [$this->olivo, $this->olivoClaro],
            [$this->azulTj, [205, 220, 245]],
            [$this->moradoTr, [225, 215, 245]],
        ];

        foreach (array_values($propietarios) as $index => $propietario) {
            [$color, $colorClaro] = $colores[$index % count($colores)];
            $cantidad = $contarHabitaciones ? count($propietario['habitaciones']) : (int) $propietario['cantidad'];
            $this->bloquePropiedad(
                'HABITACIONES ' . strtoupper($propietario['nombre']),
                $color,
                $colorClaro,
                (float) $propietario['total'],
                $cantidad,
                $propietario['datos'],
                $propietario['habitaciones']
            );
        }

        if (count($propietarios) > 1) {
            $this->renderResumenPropietarios($propietarios, $contarHabitaciones);
        }

        return true;
    }

    private function renderResumenPropietarios(array $propietarios, $contarHabitaciones = false) {
        $this->titulo('RESUMEN POR PROPIETARIO', [80, 80, 80]);

        $total = array_sum(array_map(static function ($propietario) {
            return (float) ($propietario['total'] ?? 0);
        }, $propietarios));
        $cantidadTotal = 0;
        foreach ($propietarios as $propietario) {
            $cantidadTotal += $contarHabitaciones ? count($propietario['habitaciones']) : (int) $propietario['cantidad'];
        }

        $this->c_fill([70, 70, 70]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(70, 8, 'PROPIETARIO', 0, 0, 'L', true);
        $this->Cell(45, 8, 'INGRESOS', 0, 0, 'C', true);
        $this->Cell(35, 8, $contarHabitaciones ? 'HABS.' : 'RESERVAS', 0, 0, 'C', true);
        $this->Cell(40, 8, '%', 0, 1, 'C', true);

        $fila = 0;
        foreach ($propietarios as $propietario) {
            $cantidad = $contarHabitaciones ? count($propietario['habitaciones']) : (int) $propietario['cantidad'];
            $pct = $total > 0 ? ((float) $propietario['total'] / $total * 100) : 0;
            $this->c_fill($fila % 2 === 0 ? [255, 255, 255] : $this->crema);
            $this->c_text($this->oscuro);
            $this->SetFont('helvetica', '', 9);
            $this->Cell(70, 8, '  ' . $propietario['nombre'], 'B', 0, 'L', true);
            $this->Cell(45, 8, '$' . number_format((float) $propietario['total'], 2), 'B', 0, 'C', true);
            $this->Cell(35, 8, $cantidad, 'B', 0, 'C', true);
            $this->Cell(40, 8, number_format($pct, 1) . '%', 'B', 1, 'C', true);
            $fila++;
        }

        $this->c_fill($this->crema);
        $this->SetFont('helvetica', 'B', 10);
        $this->Cell(70, 8, '  TOTAL', 'B', 0, 'L', true);
        $this->Cell(45, 8, '$' . number_format($total, 2), 'B', 0, 'C', true);
        $this->Cell(35, 8, $cantidadTotal, 'B', 0, 'C', true);
        $this->Cell(40, 8, '100%', 'B', 1, 'C', true);
        $this->Ln(2);
    }

    // =================================================================
    // BLOQUE DE PROPIEDAD (DINAMICO / LEGACY)
    // =================================================================
    private function bloquePropiedad($titulo, $color, $colorClaro, $total, $cantidadHabs, $datos, $habitaciones) {
        if ($this->GetY() > $this->getPageHeight() - 70) $this->AddPage();
        
        $this->Ln(4);
        $y = $this->GetY();
        
        // Header
        $this->c_fill($color);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 14);
        $this->RoundedRect(10, $y, 190, 12, 3, '1111', 'F');
        $this->SetXY(10, $y + 1.5);
        $this->Cell(190, 9, $titulo, 0, 1, 'C');
        
        $this->Ln(3);
        $y = $this->GetY();
        
        // Cards: Total + Habitaciones Ocupadas
        $this->cardGrande(10, $y, 60,
            '$' . number_format($total, 2), 'Total Cobrado',
            $color, $this->verdeOk);
        
        $this->cardGrande(75, $y, 60,
            $cantidadHabs, 'Habs. Ocupadas',
            $this->gris, $this->oscuro);
        
        // Mini desglose
        $boxX = 140;
        $this->c_draw($colorClaro);
        $this->SetLineWidth(0.8);
        $this->RoundedRect($boxX, $y, 60, 26, 3, '1111', 'D');
        $this->SetLineWidth(0.2);
        
        $this->SetXY($boxX + 3, $y + 2);
        $this->SetFont('helvetica', '', 8);
        $this->c_text($this->gris);
        $this->Cell(25, 4, 'Efectivo', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 9);
        $this->c_text($this->verdeOk);
        $this->Cell(27, 4, '$' . number_format($datos['efectivo'] ?? 0, 2), 0, 1, 'R');
        
        $this->SetXY($boxX + 3, $y + 9);
        $this->SetFont('helvetica', '', 8);
        $this->c_text($this->gris);
        $this->Cell(25, 4, 'Tarjeta', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 9);
        $this->c_text($this->azulTj);
        $this->Cell(27, 4, '$' . number_format($datos['tarjeta'] ?? 0, 2), 0, 1, 'R');
        
        $this->SetXY($boxX + 3, $y + 16);
        $this->SetFont('helvetica', '', 8);
        $this->c_text($this->gris);
        $this->Cell(25, 4, 'Transfer.', 0, 0, 'L');
        $this->SetFont('helvetica', 'B', 9);
        $this->c_text($this->moradoTr);
        $this->Cell(27, 4, '$' . number_format($datos['transferencia'] ?? 0, 2), 0, 1, 'R');
        
        $this->SetY($y + 30);
        
        // Habitaciones usadas - AHORA EN MÚLTIPLES LÍNEAS
        if (!empty($habitaciones)) {
            sort($habitaciones);
            $habsTexto = implode(', ', $habitaciones);
            
            // Calcular cuántas líneas necesitamos
            $this->SetFont('helvetica', '', 8);
            $anchoDisponible = 180; // ancho disponible para texto
            
            // Dividir en líneas si es muy largo
            $lineas = [];
            $lineaActual = '';
            foreach ($habitaciones as $hab) {
                $textoTentativo = $lineaActual ? $lineaActual . ', ' . $hab : $hab;
                $anchoTexto = $this->GetStringWidth($textoTentativo);
                
                if ($anchoTexto > $anchoDisponible && $lineaActual !== '') {
                    $lineas[] = $lineaActual;
                    $lineaActual = $hab;
                } else {
                    $lineaActual = $textoTentativo;
                }
            }
            if ($lineaActual !== '') {
                $lineas[] = $lineaActual;
            }
            
            // Imprimir cada línea
            $this->c_text($this->gris);
            foreach ($lineas as $i => $linea) {
                $prefijo = ($i === 0) ? 'Habitaciones: ' : '                      ';
                $this->Cell(190, 4, $prefijo . $linea, 0, 1, 'C');
            }
        }
        
        $this->Ln(1);
    }
    
    // =================================================================
    // HEADER TABLA MOVIMIENTOS
    // =================================================================
    private function headerMovimientos() {
        $this->c_fill([70, 70, 70]);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(18, 8, 'HORA', 0, 0, 'C', true);
        $this->Cell(20, 8, 'TIPO', 0, 0, 'C', true);
        $this->Cell(62, 8, '  DESCRIPCIÓN', 0, 0, 'L', true);
        $this->Cell(30, 8, 'MÉTODO', 0, 0, 'C', true);
        $this->Cell(60, 8, 'MONTO', 0, 1, 'R', true);
    }
}
