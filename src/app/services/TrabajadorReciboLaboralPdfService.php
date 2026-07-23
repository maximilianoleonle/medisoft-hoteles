<?php

require_once ROOT_PATH . '/app/helpers/tcpdf/tcpdf.php';

class TrabajadorReciboLaboralPdfService
{
    public function descargar(array $recibo): void
    {
        $contenido = $this->generarContenido($recibo);
        $nombreArchivo = $this->nombreArchivo($recibo);

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

    private function generarContenido(array $recibo): string
    {
        $trabajador = is_array($recibo['trabajador'] ?? null) ? $recibo['trabajador'] : [];
        $calculo = is_array($recibo['calculo'] ?? null) ? $recibo['calculo'] : [];
        $periodo = is_array($recibo['periodo'] ?? null) ? $recibo['periodo'] : [];
        $resumen = is_array($recibo['resumen'] ?? null) ? $recibo['resumen'] : [];

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $hotel = function_exists('current_hotel_display_name')
            ? current_hotel_display_name('Medisoft Hoteles')
            : 'Medisoft Hoteles';

        $pdf->SetCreator('Medisoft Hoteles');
        $pdf->SetAuthor($hotel);
        $pdf->SetTitle('Recibo laboral informativo');
        $pdf->SetSubject('PDF informativo laboral');
        $pdf->SetKeywords('recibo, laboral, informativo');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 14);
        $pdf->AddPage();

        $this->header($pdf, $hotel, $trabajador, $periodo, $recibo);
        $this->resumen($pdf, $calculo);
        $this->detalleConceptos($pdf, $calculo);
        $this->detalleCaja($pdf, $calculo, $periodo);
        $this->aviso($pdf, $resumen);

        return (string)$pdf->Output($this->nombreArchivo($recibo), 'S');
    }

    private function header(TCPDF $pdf, string $hotel, array $trabajador, array $periodo, array $recibo): void
    {
        $calculo = is_array($recibo['calculo'] ?? null) ? $recibo['calculo'] : [];
        $pdf->SetFillColor(31, 63, 70);
        $pdf->Rect(0, 0, 216, 34, 'F');
        $pdf->SetFillColor(181, 138, 60);
        $pdf->Rect(0, 34, 216, 2, 'F');

        $pdf->SetXY(12, 9);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(128, 7, 'RECIBO LABORAL INFORMATIVO', 0, 1, 'L');

        $pdf->SetX(12);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(128, 5, 'PDF informativo / No fiscal / No genera pago', 0, 0, 'L');

        $pdf->SetXY(146, 9);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->MultiCell(58, 5, $this->texto($hotel), 0, 'R');
        $pdf->SetXY(146, 22);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(58, 4, 'Generado: ' . $this->fechaHora($recibo['generado_en'] ?? null), 0, 'R');

        $pdf->SetY(44);
        $pdf->SetTextColor(36, 49, 66);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Trabajador', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 9);
        $this->dato($pdf, 'Nombre', $trabajador['nombre_completo'] ?? null, 12, 54, 92);
        $this->dato($pdf, 'Identificación', $trabajador['identificacion'] ?? null, 108, 54, 46);
        $this->dato($pdf, 'Rol', $trabajador['rol_laboral'] ?? null, 158, 54, 46);
        $this->dato($pdf, 'Periodo', $this->fecha($periodo['fecha_inicio'] ?? null) . ' - ' . $this->fecha($periodo['fecha_fin'] ?? null), 12, 70, 70);
        $this->dato($pdf, 'Pagos Caja', !empty($periodo['incluir_pagos_caja']) ? 'Incluidos' : 'Excluidos', 86, 70, 46);
        $this->dato($pdf, 'Estado', str_replace('_', ' ', (string)($calculo['estado_preview_nomina'] ?? $trabajador['estado'] ?? '')), 136, 70, 68);
    }

    private function resumen(TCPDF $pdf, array $calculo): void
    {
        $pdf->SetY(92);
        $this->sectionTitle($pdf, 'Resumen del periodo');

        $x = 12;
        $y = 104;
        $w = 37;
        $gap = 4;
        $this->metric($pdf, $x, $y, $w, 'Bruto laboral', $this->money($calculo['bruto_periodo'] ?? 0), [15, 118, 110]);
        $this->metric($pdf, $x + ($w + $gap), $y, $w, 'Deducciones', $this->money($calculo['deducciones_informativas'] ?? 0), [154, 92, 38]);
        $this->metric($pdf, $x + 2 * ($w + $gap), $y, $w, 'Pagos Caja', $this->money($calculo['pagos_caja_aplicados'] ?? 0), [154, 92, 38]);
        $this->metric($pdf, $x + 3 * ($w + $gap), $y, $w, 'Neto sugerido', $this->money($calculo['neto_sugerido'] ?? 0), [31, 63, 70]);
        $this->metric($pdf, $x + 4 * ($w + $gap), $y, $w, 'Pendiente', $this->money($calculo['pendiente_pago_sugerido'] ?? 0), [15, 118, 110]);
    }

    private function detalleConceptos(TCPDF $pdf, array $calculo): void
    {
        $pdf->SetY(137);
        $this->sectionTitle($pdf, 'Conceptos y deducciones informativas');
        $pdf->SetFont('helvetica', '', 9);

        $rows = [
            ['Conceptos a favor', $this->money($calculo['conceptos_a_favor'] ?? 0), 'Suman al bruto laboral.'],
            ['Conceptos en contra', $this->money($calculo['conceptos_en_contra'] ?? 0), 'Restan al bruto laboral.'],
            ['Anticipos pendientes', $this->money($calculo['anticipos_saldo'] ?? 0), $this->num($calculo['anticipos_count'] ?? 0) . ' registro(s).'],
            ['Prestamos vigentes', $this->money($calculo['prestamos_saldo'] ?? 0), $this->num($calculo['prestamos_count'] ?? 0) . ' registro(s).'],
        ];

        $this->tabla($pdf, $rows, 149);
    }

    private function detalleCaja(TCPDF $pdf, array $calculo, array $periodo): void
    {
        $pdf->SetY(196);
        $this->sectionTitle($pdf, 'Lectura contra Caja');
        $pdf->SetFont('helvetica', '', 9);

        $rows = [
            ['Pagos Caja vigentes', $this->num($calculo['pagos_caja_pagados'] ?? 0), !empty($periodo['incluir_pagos_caja']) ? 'Incluidos en el pendiente sugerido.' : 'Excluidos del calculo visible.'],
            ['Monto aplicado', $this->money($calculo['pagos_caja_aplicados'] ?? 0), 'Solo lectura; no crea movimiento de Caja.'],
            ['Pagos revertidos', $this->num($calculo['pagos_caja_revertidos'] ?? 0), 'Lectura historica informativa.'],
            ['Ultimo pago Caja', $this->fechaHora($calculo['ultimo_pago_caja'] ?? null), 'Referencia informativa.'],
        ];

        $this->tabla($pdf, $rows, 208);
    }

    private function aviso(TCPDF $pdf, array $resumen): void
    {
        $pdf->SetY(252);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(211, 218, 226);
        $pdf->Rect(12, 252, 192, 22, 'DF');
        $pdf->SetXY(15, 256);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetTextColor(36, 49, 66);
        $pdf->Cell(0, 5, 'Aviso interno', 0, 1, 'L');
        $pdf->SetX(15);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->MultiCell(
            186,
            4,
            'Este PDF es informativo. No representa CFDI, nomina oficial, comprobante fiscal, pago ni movimiento de Caja. '
            . 'Resumen visible: bruto ' . $this->money($resumen['bruto_total'] ?? 0)
            . ', pendiente ' . $this->money($resumen['pendiente_pago_total'] ?? 0) . '.',
            0,
            'L'
        );
    }

    private function sectionTitle(TCPDF $pdf, string $title): void
    {
        $pdf->SetFillColor(31, 63, 70);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $pdf->SetTextColor(36, 49, 66);
    }

    private function metric(TCPDF $pdf, float $x, float $y, float $w, string $label, string $value, array $color): void
    {
        $pdf->SetDrawColor(211, 218, 226);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect($x, $y, $w, 22, 2, '1111', 'DF');
        $pdf->SetXY($x + 2, $y + 3);
        $pdf->SetFont('helvetica', 'B', 6.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell($w - 4, 4, strtoupper($label), 0, 1, 'L');
        $pdf->SetX($x + 2);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->Cell($w - 4, 7, $value, 0, 1, 'L');
        $pdf->SetTextColor(36, 49, 66);
    }

    private function tabla(TCPDF $pdf, array $rows, float $startY): void
    {
        $pdf->SetY($startY);
        foreach ($rows as $row) {
            $pdf->SetX(12);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(229, 231, 235);
            $pdf->Cell(62, 10, $this->texto((string)$row[0]), 1, 0, 'L', true);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(36, 10, $this->texto((string)$row[1]), 1, 0, 'R', true);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(94, 10, $this->texto((string)$row[2]), 1, 1, 'L', true);
            $pdf->SetFont('helvetica', '', 9);
        }
    }

    private function dato(TCPDF $pdf, string $label, $value, float $x, float $y, float $w): void
    {
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell($w, 4, strtoupper($label), 0, 1, 'L');
        $pdf->SetX($x);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(36, 49, 66);
        $pdf->MultiCell($w, 5, $this->texto($value), 0, 'L');
    }

    private function nombreArchivo(array $recibo): string
    {
        $trabajador = is_array($recibo['trabajador'] ?? null) ? $recibo['trabajador'] : [];
        $periodo = is_array($recibo['periodo'] ?? null) ? $recibo['periodo'] : [];
        $id = max(0, (int)($trabajador['id'] ?? 0));
        $fecha = preg_replace('/[^0-9-]/', '', (string)($periodo['fecha_fin'] ?? date('Y-m-d')));

        return 'recibo_laboral_informativo_trab_' . $id . '_' . ($fecha !== '' ? $fecha : date('Y-m-d')) . '.pdf';
    }

    private function texto($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return $fallback;
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/', ' ', $text) ?: $fallback;
    }

    private function money($value): string
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }

    private function num($value): string
    {
        return number_format((int)($value ?? 0));
    }

    private function fecha($value): string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || $text === '0000-00-00') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y');
        } catch (Throwable $e) {
            return $this->texto($text);
        }
    }

    private function fechaHora($value): string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return $this->texto($text);
        }
    }
}
