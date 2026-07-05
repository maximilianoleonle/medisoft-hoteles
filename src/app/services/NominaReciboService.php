<?php
/**
 * Recibos internos de nomina v2 (Fase 4 nomina core).
 *
 * Emite recibos NO FISCALES desde el snapshot de un periodo v2 APROBADO:
 * folio consecutivo por negocio, totales y lineas congeladas. El PDF se
 * genera bajo demanda desde lo congelado (nunca recalcula). Documento
 * operativo: NO se gatea bajo el bloque exportaciones (regla AGENTS.md).
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';
require_once ROOT_PATH . '/app/helpers/tcpdf/tcpdf.php';

class NominaReciboService {

    private $db;
    private $pdo;

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function listarPorPeriodo(int $hotelId, int $periodoId): array {
        $st = $this->db->query(
            "SELECT * FROM nomina_recibos WHERE hotel_id = ? AND periodo_id = ? ORDER BY folio_numero ASC",
            [$hotelId, $periodoId]
        );
        return $st !== false ? $st->fetchAll() : [];
    }

    public function obtener(int $hotelId, int $reciboId): ?array {
        $st = $this->db->query(
            "SELECT * FROM nomina_recibos WHERE id = ? AND hotel_id = ?",
            [$reciboId, $hotelId]
        );
        $recibo = $st !== false ? $st->fetch() : null;
        return $recibo ?: null;
    }

    /**
     * Emite recibos para todos los detalles del periodo que no tengan recibo
     * vigente. Solo periodos v2 APROBADOS. Devuelve cuantos emitio.
     */
    public function emitirPorPeriodo(int $hotelId, int $periodoId, ?int $usuarioId = null): int {
        $st = $this->pdo->prepare(
            "SELECT * FROM trabajador_nomina_periodos
             WHERE id = ? AND hotel_id = ? AND motor = 'v2'"
        );
        $st->execute([$periodoId, $hotelId]);
        $periodo = $st->fetch();
        if (!$periodo) {
            throw new Exception('El periodo v2 no existe en este negocio.');
        }
        if ($periodo['estado'] !== 'aprobado') {
            throw new Exception('Solo un periodo APROBADO puede emitir recibos (estado: ' . $periodo['estado'] . ').');
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            // Detalles sin recibo vigente.
            $st = $this->pdo->prepare(
                "SELECT d.* FROM trabajador_nomina_periodo_detalles d
                 WHERE d.periodo_id = ? AND d.hotel_id = ?
                   AND NOT EXISTS (
                       SELECT 1 FROM nomina_recibos r
                       WHERE r.detalle_id = d.id AND r.estado = 'emitido'
                   )
                 ORDER BY d.trabajador_nombre ASC"
            );
            $st->execute([$periodoId, $hotelId]);
            $detalles = $st->fetchAll();

            // Folio consecutivo por negocio (la UNIQUE respalda la secuencia).
            $st = $this->pdo->prepare(
                "SELECT COALESCE(MAX(folio_numero), 0) AS ultimo FROM nomina_recibos WHERE hotel_id = ? FOR UPDATE"
            );
            $st->execute([$hotelId]);
            $folioNumero = (int) ($st->fetch()['ultimo'] ?? 0);

            $stLineas = $this->pdo->prepare(
                "SELECT concepto_nombre, tipo, clasificacion, origen, cantidad, base, monto
                 FROM nomina_periodo_conceptos
                 WHERE detalle_id = ? AND hotel_id = ?
                 ORDER BY tipo ASC, id ASC"
            );

            $stInsert = $this->pdo->prepare(
                "INSERT INTO nomina_recibos
                    (hotel_id, periodo_id, detalle_id, trabajador_id, folio_numero, folio,
                     trabajador_nombre, periodo_etiqueta, fecha_inicio, fecha_fin,
                     percepciones, deducciones, deducciones_informativas, neto,
                     lineas_json, estado, emitido_por, emitido_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitido', ?, NOW())"
            );

            $emitidos = 0;
            foreach ($detalles as $d) {
                $folioNumero++;
                $folio = 'NOM-' . str_pad((string) $folioNumero, 6, '0', STR_PAD_LEFT);

                $stLineas->execute([(int) $d['id'], $hotelId]);
                $lineas = $stLineas->fetchAll();

                $stInsert->execute([
                    $hotelId,
                    $periodoId,
                    (int) $d['id'],
                    (int) $d['trabajador_id'],
                    $folioNumero,
                    $folio,
                    $d['trabajador_nombre'],
                    $periodo['etiqueta'],
                    $periodo['fecha_inicio'],
                    $periodo['fecha_fin'],
                    $d['conceptos_a_favor'],
                    $d['conceptos_en_contra'],
                    $d['deducciones_informativas'],
                    $d['neto_sugerido'],
                    json_encode($lineas, JSON_UNESCAPED_UNICODE),
                    $usuarioId,
                ]);
                $emitidos++;
            }

            if ($emitidos > 0) {
                AuditService::record('nomina.recibos_emitidos', [
                    'hotel_id' => $hotelId,
                    'usuario_id' => $usuarioId,
                    'entidad_tipo' => 'nomina_recibos',
                    'entidad_id' => (string) $periodoId,
                    'descripcion' => 'Emitio ' . $emitidos . ' recibo(s) interno(s) del periodo ' . $periodo['etiqueta'],
                ]);
            }

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return $emitidos;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    public function cancelar(int $hotelId, int $reciboId, string $motivo, ?int $usuarioId = null): bool {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new Exception('El motivo de cancelacion es obligatorio.');
        }

        $recibo = $this->obtener($hotelId, $reciboId);
        if (!$recibo) {
            throw new Exception('El recibo no existe en este negocio.');
        }
        if ($recibo['estado'] === 'cancelado') {
            return true;
        }

        $st = $this->db->query(
            "UPDATE nomina_recibos
             SET estado = 'cancelado', cancelacion_uk = id, cancelado_por = ?, cancelado_at = NOW(), motivo_cancelacion = ?
             WHERE id = ? AND hotel_id = ? AND estado = 'emitido'",
            [$usuarioId, mb_substr($motivo, 0, 255), $reciboId, $hotelId]
        );
        if ($st === false) {
            throw new Exception('No se pudo cancelar el recibo.');
        }

        AuditService::record('nomina.recibo_cancelado', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'nomina_recibos',
            'entidad_id' => (string) $reciboId,
            'descripcion' => 'Cancelo recibo ' . $recibo['folio'] . ': ' . $motivo,
        ]);

        return true;
    }

    /** Cancela todos los recibos vigentes de un periodo (reapertura/anulacion). */
    public function cancelarPorPeriodo(int $hotelId, int $periodoId, string $motivo, ?int $usuarioId = null): int {
        $st = $this->pdo->prepare(
            "UPDATE nomina_recibos
             SET estado = 'cancelado', cancelacion_uk = id, cancelado_por = ?, cancelado_at = NOW(), motivo_cancelacion = ?
             WHERE hotel_id = ? AND periodo_id = ? AND estado = 'emitido'"
        );
        $st->execute([$usuarioId, mb_substr($motivo, 0, 255), $hotelId, $periodoId]);
        return $st->rowCount();
    }

    /** Genera y descarga el PDF del recibo (desde lo congelado; termina la peticion). */
    public function descargarPdf(int $hotelId, int $reciboId): void {
        $recibo = $this->obtener($hotelId, $reciboId);
        if (!$recibo) {
            throw new Exception('El recibo no existe en este negocio.');
        }

        $contenido = $this->generarPdf($recibo);
        $nombre = 'recibo-' . strtolower($recibo['folio']) . '.pdf';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($contenido));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $contenido;
        exit;
    }

    private function generarPdf(array $recibo): string {
        $negocio = function_exists('current_hotel_display_name')
            ? current_hotel_display_name('Medisoft')
            : 'Medisoft';

        $lineas = json_decode((string) ($recibo['lineas_json'] ?? '[]'), true) ?: [];

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator('Medisoft');
        $pdf->SetAuthor($negocio);
        $pdf->SetTitle('Recibo interno de nomina ' . $recibo['folio']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(14, 14, 14);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        // Encabezado.
        $pdf->SetFillColor(27, 39, 70);
        $pdf->Rect(0, 0, 216, 30, 'F');
        $pdf->SetFillColor(189, 148, 65);
        $pdf->Rect(0, 30, 216, 1.6, 'F');
        $pdf->SetXY(14, 8);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(120, 6, 'RECIBO INTERNO DE NOMINA', 0, 1, 'L');
        $pdf->SetX(14);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(120, 5, $negocio, 0, 1, 'L');
        $pdf->SetXY(150, 8);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(52, 6, $recibo['folio'], 0, 1, 'R');
        $pdf->SetXY(150, 14);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(52, 5, 'Emitido: ' . substr((string) $recibo['emitido_at'], 0, 10), 0, 1, 'R');

        if ($recibo['estado'] === 'cancelado') {
            $pdf->SetXY(150, 19);
            $pdf->SetTextColor(255, 120, 120);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(52, 5, 'CANCELADO', 0, 1, 'R');
        }

        // Datos.
        $pdf->SetTextColor(35, 35, 35);
        $pdf->SetY(38);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, $recibo['trabajador_nombre'], 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9.5);
        $pdf->Cell(0, 5, 'Periodo: ' . $recibo['periodo_etiqueta'] . '  (' . $recibo['fecha_inicio'] . ' al ' . $recibo['fecha_fin'] . ')', 0, 1, 'L');
        $pdf->Ln(3);

        // Tabla de conceptos.
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(246, 242, 234);
        $pdf->Cell(96, 7, 'Concepto', 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Origen', 1, 0, 'L', true);
        $pdf->Cell(20, 7, 'Cant.', 1, 0, 'R', true);
        $pdf->Cell(40, 7, 'Monto', 1, 1, 'R', true);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($lineas as $l) {
            $esDeduccion = ($l['tipo'] ?? '') === 'deduccion';
            $pdf->Cell(96, 6.5, ($esDeduccion ? '(-) ' : '(+) ') . (string) ($l['concepto_nombre'] ?? ''), 1, 0, 'L');
            $pdf->Cell(30, 6.5, (string) ($l['origen'] ?? ''), 1, 0, 'L');
            $pdf->Cell(20, 6.5, isset($l['cantidad']) && $l['cantidad'] !== null ? number_format((float) $l['cantidad'], 2) : '-', 1, 0, 'R');
            $pdf->Cell(40, 6.5, '$' . number_format((float) ($l['monto'] ?? 0), 2), 1, 1, 'R');
        }

        // Totales.
        $pdf->Ln(3);
        $tot = function (string $etq, $monto, bool $negrita = false) use ($pdf) {
            $pdf->SetFont('helvetica', $negrita ? 'B' : '', $negrita ? 11 : 9.5);
            $pdf->Cell(146, 6, $etq, 0, 0, 'R');
            $pdf->Cell(40, 6, '$' . number_format((float) $monto, 2), 0, 1, 'R');
        };
        $tot('Percepciones:', $recibo['percepciones']);
        $tot('Deducciones del periodo:', $recibo['deducciones']);
        $tot('Anticipos y prestamos (informativo):', $recibo['deducciones_informativas']);
        $tot('NETO:', $recibo['neto'], true);

        // Firmas y leyenda.
        $pdf->Ln(14);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(88, 5, '_________________________', 0, 0, 'C');
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(88, 5, '_________________________', 0, 1, 'C');
        $pdf->Cell(88, 5, 'Recibi de conformidad', 0, 0, 'C');
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(88, 5, 'Autorizo', 0, 1, 'C');

        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(120, 116, 108);
        $pdf->MultiCell(0, 4,
            'Documento interno de control operativo. NO es un comprobante fiscal (CFDI) ni un recibo de nomina timbrado. '
            . 'Generado desde el snapshot inmutable del periodo; los montos no se recalculan.',
            0, 'L');

        return (string) $pdf->Output('recibo.pdf', 'S');
    }
}
