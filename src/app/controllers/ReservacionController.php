<?php
/**
 * Controlador de Reservaciones - Versión Completa Corregida
 * Los Cedros
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Reservacion.php';
require_once __DIR__ . '/../models/Habitacion.php';
require_once __DIR__ . '/../models/Huesped.php';
require_once __DIR__ . '/../models/Caja.php';

class ReservacionController extends Controller {
    
    private $reservacionModel;
    private $huespedModel;
    private $habitacionModel;
    private $cajaModel;
    private $db;
    
    /**
     * Constructor
     */
    public function __construct($route_params = []) {
        parent::__construct($route_params);
        
        // Obtener instancia de la base de datos
        $this->db = Database::getInstance()->getConnection();
        
        // Inicializar modelos
        $this->reservacionModel = new Reservacion();
        $this->huespedModel = new Huesped();
        $this->habitacionModel = new Habitacion();
        $this->cajaModel = new Caja();
    }

    private function hotelIdActual() {
        return obtenerHotelIdActualCompat();
    }

    public function agregarNotaAction() {
    if (!$this->isPost()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    $this->validateCSRF();
    
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $nota = trim($this->getPost('nota'));
    
    if (empty($nota)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'La nota no puede estar vacía']);
        exit;
    }
    
    // Cargar modelo de notas
    require_once __DIR__ . '/../models/ReservacionNota.php';
    $notaModel = new ReservacionNota();
    
    $usuario_id = user_id();
    
    $resultado = $notaModel->agregarNota($reservacion_id, $usuario_id, $nota);
    
    if ($resultado['success']) {
        // Obtener la nota recién creada con información del usuario
        $notas = $notaModel->obtenerPorReservacion($reservacion_id);
        $nota_nueva = $notas[0] ?? null;
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'nota' => $nota_nueva,
            'message' => 'Nota agregada exitosamente'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la nota'
        ]);
    }
    exit;
}

    public function cotizacionReservacionPdfAction() {
        if (!$this->isPost()) {
            $this->redirect('reservaciones');
            return;
        }
 
        try {
            $reservacion_id = intval($this->getPost('reservacion_id'));
            $anticipo        = floatval($this->getPost('anticipo', 0));
 
            if (empty($reservacion_id)) {
                die('ID de reservación no proporcionado.');
            }
 
            // Obtener reservación
            $reservacion = $this->reservacionModel->find($reservacion_id);
            if (!$reservacion) {
                die('Reservación no encontrada.');
            }
 
            // Obtener huésped
            $huesped = $this->huespedModel->find($reservacion['huesped_id']);
            if (!$huesped) {
                die('Huésped no encontrado.');
            }
 
            // Obtener vehículos del huésped
            $vehiculos = [];
            try {
                $stmtV = $this->db->prepare("
                    SELECT marca, modelo, placas, color, estacionamiento 
                    FROM huesped_vehiculos 
                    WHERE huesped_id = :hid AND activo = 1
                    ORDER BY id ASC
                ");
                $stmtV->execute([':hid' => $reservacion['huesped_id']]);
                $vehiculos = $stmtV->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Error obteniendo vehículos: ' . $e->getMessage());
            }
 
            // Si no hay en la tabla nueva, intentar con campos legacy
            if (empty($vehiculos) && !empty($huesped['vehiculo_marca'])) {
                $vehiculos[] = [
                    'marca'  => $huesped['vehiculo_marca'],
                    'modelo' => '',
                    'placas' => $huesped['vehiculo_placas'] ?? '',
                    'color'  => '',
                    'estacionamiento' => ''
                ];
            }
 
            // Obtener habitaciones de la reservación
            $habitaciones = $this->reservacionModel->getHabitaciones($reservacion_id);
            if (empty($habitaciones)) {
                die('No se encontraron habitaciones para esta reservación.');
            }
 
            // Calcular noches
            $fecha_inicio = new DateTime($reservacion['fecha_entrada']);
            $fecha_fin    = new DateTime($reservacion['fecha_salida']);
            $noches       = $fecha_inicio->diff($fecha_fin)->days;
            if ($noches == 0) $noches = 1;
 
            // ─── Generar PDF ─────────────────────────────────────
            require_once __DIR__ . '/../../public_html/fdpdf/fpdf.php';
 
            $pdf = new FPDF('P', 'mm', 'Letter');
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->AddPage();
 
            // ─── Colores del hotel ───────────────────────────────
            $olivo     = [92, 122, 78];
            $olivoOsc  = [61, 82, 52];
            $gold      = [200, 169, 106];
            $cream     = [247, 244, 238];
            $creamMid  = [238, 233, 222];
            $gris      = [107, 114, 128];
            $grisCla   = [156, 163, 175];
            $blanco    = [255, 255, 255];
            $negro     = [55, 65, 81];
            $ambar     = [217, 119, 6];
 
            $u = function($text) {
                return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            };
 
            $pageW   = 216;
            $margin  = 15;
            $contentW = $pageW - ($margin * 2);
 
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $hoy = date('j') . ' de ' . $meses[date('n')-1] . ' de ' . date('Y');
 
            $formatFecha = function($fecha) use ($meses, $u) {
                $d = new DateTime($fecha);
                return $d->format('d') . ' de ' . $meses[$d->format('n')-1] . ' de ' . $d->format('Y');
            };
 
            // ═══════════════════════════════════════════════════════
            // HEADER
            // ═══════════════════════════════════════════════════════
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->Rect(0, 0, $pageW, 42, 'F');
            $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Rect(0, 38, $pageW, 4, 'F');
 
            $logoPath = __DIR__ . '/../../public_html/img/logo.png';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, $margin, 5, 32, 32);
            }
 
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
            $pdf->SetXY($margin + 36, 8);
            $pdf->Cell(100, 8, $u('Hotel Los Cedros'), 0, 2, 'L');
 
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetX($margin + 36);
            $pdf->Cell(100, 5, $u('Santa Catarina Juquila, Oaxaca'), 0, 2, 'L');
 
            $pdf->SetFont('Helvetica', 'B', 22);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetXY($pageW - $margin - 70, 9);
            $pdf->Cell(70, 10, $u('COTIZACIÓN'), 0, 0, 'R');
 
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(200, 210, 195);
            $pdf->SetXY($pageW - $margin - 70, 20);
            $pdf->Cell(70, 5, $u('Fecha: ' . $hoy), 0, 0, 'R');
 
            // Número de reservación
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetXY($pageW - $margin - 70, 26);
            $pdf->Cell(70, 5, $u('Reservación #' . $reservacion_id), 0, 0, 'R');
 
            $pdf->SetFillColor($gold[0], $gold[1], $gold[2]);
            $pdf->Rect(0, 42, $pageW, 1.5, 'F');
 
            // ═══════════════════════════════════════════════════════
            // DATOS DEL HUÉSPED
            // ═══════════════════════════════════════════════════════
            $y = 50;
            $pdf->SetY($y);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DATOS DEL HUÉSPED'), 0, 1, 'L');
 
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.5);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);
 
            // Calcular alto de la caja del huésped (base 18 + vehículos)
            $huespedBoxH = 18;
            if (!empty($vehiculos)) {
                $huespedBoxH += 2 + (count($vehiculos) * 5) + 5 + 4; // separador + filas + título + margen inferior
            }
 
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY = $pdf->GetY();
            $pdf->Rect($margin, $boxY, $contentW, $huespedBoxH, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY, $contentW, $huespedBoxH, 'D');
 
            // Fila 1: Nombre + Teléfono
            $pdf->SetXY($margin + 4, $boxY + 3);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell(18, 5, $u('Nombre:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(70, 5, $u($huesped['nombre_completo'] ?? 'N/A'), 0, 0, 'L');
 
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(22, 5, $u('Teléfono:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(50, 5, $u($huesped['telefono'] ?? 'No registrado'), 0, 1, 'L');
 
            // Fila 2: Procedencia
            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(26, 5, $u('Procedencia:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $procedencia = $huesped['procedencia_estado'] ?? ($huesped['procedencia'] ?? 'No especificada');
            $pdf->Cell(60, 5, $u($procedencia), 0, 0, 'L');
 
            // ── Vehículos ──────────────────────────────────────
            if (!empty($vehiculos)) {
                $vehY = $boxY + 20;
                
                // Línea separadora sutil
                $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
                $pdf->SetLineWidth(0.3);
                $pdf->Line($margin + 4, $vehY, $margin + $contentW - 4, $vehY);
 
                $pdf->SetXY($margin + 4, $vehY + 2);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
                $pdf->Cell(60, 4, $u('VEHÍCULOS'), 0, 1, 'L');
 
                foreach ($vehiculos as $veh) {
                    $pdf->SetX($margin + 6);
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
 
                    // Construir descripción del vehículo
                    $vehDesc = '';
                    $vehParts = [];
                    
                    if (!empty($veh['marca']))  $vehParts[] = $veh['marca'];
                    if (!empty($veh['modelo'])) $vehParts[] = $veh['modelo'];
                    $vehDesc = implode(' ', $vehParts);
                    
                    if (!empty($veh['color'])) {
                        $vehDesc .= ' · ' . $veh['color'];
                    }
 
                    // Icono bullet
                    $pdf->SetFont('Helvetica', 'B', 7);
                    $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
                    $pdf->Cell(3, 5, $u('•'), 0, 0, 'L');
 
                    // Descripción
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                    $pdf->Cell(70, 5, ' ' . $u($vehDesc), 0, 1, 'L');
                }
            }
 
            // ═══════════════════════════════════════════════════════
            // DATOS DE LA ESTANCIA
            // ═══════════════════════════════════════════════════════
            $y = $boxY + $huespedBoxH + 6;
            $pdf->SetY($y);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DATOS DE LA ESTANCIA'), 0, 1, 'L');
 
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);
 
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY2 = $pdf->GetY();
            $pdf->Rect($margin, $boxY2, $contentW, 14, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY2, $contentW, 14, 'D');
 
            $colW = $contentW / 3;
            $pdf->SetXY($margin + 4, $boxY2 + 2);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, 'CHECK-IN', 0, 0, 'L');
            $pdf->Cell($colW, 4, 'CHECK-OUT', 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, 'NOCHES', 0, 1, 'L');
 
            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell($colW, 5, $u($formatFecha($reservacion['fecha_entrada'])), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($formatFecha($reservacion['fecha_salida'])), 0, 0, 'L');
            $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Cell($colW - 8, 5, $noches . ' noche' . ($noches > 1 ? 's' : ''), 0, 1, 'L');
 
            // ═══════════════════════════════════════════════════════
            // TABLA DE HABITACIONES
            // ═══════════════════════════════════════════════════════
            $y = $boxY2 + 22;
            $pdf->SetY($y);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DETALLE DE HABITACIONES'), 0, 1, 'L');
 
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);
 
            // Columnas: HAB | PRECIO/NOCHE | NOCHES | TOTAL | OBSERVACIONES
            $colHab    = 20;
            $colPrecio = 28;
            $colNoches = 18;
            $colTotal  = 28;
            $colObs    = $contentW - $colHab - $colPrecio - $colNoches - $colTotal;
 
            // Header tabla
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
            $pdf->SetFont('Helvetica', 'B', 7.5);
            $pdf->SetX($margin);
            $pdf->Cell($colHab, 8, 'HAB.', 0, 0, 'C', true);
            $pdf->Cell($colPrecio, 8, 'PRECIO/NOCHE', 0, 0, 'C', true);
            $pdf->Cell($colNoches, 8, 'NOCHES', 0, 0, 'C', true);
            $pdf->Cell($colTotal, 8, 'TOTAL', 0, 0, 'C', true);
            $pdf->Cell($colObs, 8, 'OBSERVACIONES', 0, 1, 'C', true);
 
            // ── Cargar modelo de tarifas dinámicas ──────────────
            if (!class_exists('IncrementoTarifa')) {
                require_once __DIR__ . '/../models/IncrementoTarifa.php';
            }
            $tarifaModel = new IncrementoTarifa();
 
            // Filas
            $subtotal = 0;
            $descuento_cortesia = 0;
            $row = 0;
 
            foreach ($habitaciones as $hab) {
                $esCortesia = ($hab['es_cortesia'] ?? false) ? true : false;
 
                // Recalcular precio con tarifas dinámicas (noche por noche)
                $precioTotalHab = 0;
                $fecha_actual = clone $fecha_inicio;
                for ($i = 0; $i < $noches; $i++) {
                    $fecha_str = $fecha_actual->format('Y-m-d');
                    $calculo = $tarifaModel->calcularPrecioConIncremento(
                        $hab['habitacion_id'] ?? $hab['id'] ?? 0,
                        $hab['tipo'],
                        $hab['precio_base'],
                        $fecha_str
                    );
                    $precioTotalHab += $calculo['precio_final'];
                    $fecha_actual->modify('+1 day');
                }
 
                // Precio base por noche (sin incremento, para mostrar en columna)
                // Si hay incremento, el total será mayor que base * noches
                $precioPorNoche = floatval($hab['precio_base']);
 
                $subtotal += $precioTotalHab;
 
                if ($esCortesia) {
                    $descuento_cortesia += $precioTotalHab;
                }
 
                if ($esCortesia) {
                    $pdf->SetFillColor(254, 243, 199);
                } elseif ($row % 2 == 0) {
                    $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
                } else {
                    $pdf->SetFillColor($blanco[0], $blanco[1], $blanco[2]);
                }
 
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->SetFont('Helvetica', $esCortesia ? 'B' : '', 8);
                $pdf->SetX($margin);
 
                // Habitación
                $pdf->Cell($colHab, 7, $hab['numero'], 0, 0, 'C', true);
 
                // Precio y noches
                if ($esCortesia) {
                    $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->Cell($colTotal, 7, $u('CORTESÍA'), 0, 0, 'C', true);
                } else {
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
                    $pdf->Cell($colTotal, 7, '$' . number_format($precioTotalHab, 0, '.', ','), 0, 0, 'C', true);
                }
 
                // Observaciones
                $pdf->SetFont('Helvetica', '', 6.5);
                $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
                $obs = $hab['observaciones'] ?? '';
                $pdf->Cell($colObs, 7, $u($obs), 0, 1, 'L', true);
 
                $row++;
            }
 
            // Línea inferior tabla
            $pdf->SetDrawColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($margin, $pdf->GetY(), $margin + $contentW, $pdf->GetY());
 
            // ═══════════════════════════════════════════════════════
            // TOTALES
            // ═══════════════════════════════════════════════════════
            $pdf->Ln(5);
            $totalesX = $margin + $contentW - 80;
            $totalesW = 80;
 
            $totalReservacion = $subtotal - $descuento_cortesia;
 
            // Subtotal (sin cortesías)
            if ($descuento_cortesia > 0) {
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
                $pdf->SetX($totalesX);
                $pdf->Cell(40, 6, 'Subtotal:', 0, 0, 'R');
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->Cell(40, 6, '$' . number_format($subtotal, 0, '.', ',') . ' MXN', 0, 1, 'R');
 
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                $pdf->SetX($totalesX);
                $cortCount = count(array_filter($habitaciones, fn($h) => $h['es_cortesia'] ?? false));
                $pdf->Cell(40, 6, $u('Cortesía (' . $cortCount . ' hab.):'), 0, 0, 'R');
                $pdf->Cell(40, 6, '-$' . number_format($descuento_cortesia, 0, '.', ',') . ' MXN', 0, 1, 'R');
            }
 
            // Anticipo (si aplica)
            if ($anticipo > 0) {
                $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
                $pdf->SetLineWidth(0.3);
                $lineY = $pdf->GetY() + 1;
                $pdf->Line($totalesX, $lineY, $totalesX + $totalesW, $lineY);
                $pdf->Ln(3);
 
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->SetX($totalesX);
                $pdf->Cell(40, 6, $u('Total Estancia:'), 0, 0, 'R');
                $pdf->Cell(40, 6, '$' . number_format($totalReservacion, 0, '.', ',') . ' MXN', 0, 1, 'R');
 
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor(5, 150, 105);
                $pdf->SetX($totalesX);
                $pdf->Cell(40, 6, 'Anticipo:', 0, 0, 'R');
                $pdf->Cell(40, 6, '-$' . number_format($anticipo, 0, '.', ',') . ' MXN', 0, 1, 'R');
 
                $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
                $pdf->SetLineWidth(0.5);
                $lineY = $pdf->GetY() + 1;
                $pdf->Line($totalesX, $lineY, $totalesX + $totalesW, $lineY);
                $pdf->Ln(4);
 
                $saldo = $totalReservacion - $anticipo;
                $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
                $totalBoxY = $pdf->GetY();
                $pdf->Rect($totalesX - 2, $totalBoxY, $totalesW + 4, 10, 'F');
 
                $pdf->SetFont('Helvetica', 'B', 11);
                $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
                $pdf->SetXY($totalesX, $totalBoxY + 1.5);
                $pdf->Cell(40, 7, 'SALDO PENDIENTE:', 0, 0, 'R');
                $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
                $pdf->Cell(40, 7, '$' . number_format($saldo, 0, '.', ',') . ' MXN', 0, 1, 'R');
 
            } else {
                $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
                $pdf->SetLineWidth(0.5);
                $lineY = $pdf->GetY() + 1;
                $pdf->Line($totalesX, $lineY, $totalesX + $totalesW, $lineY);
                $pdf->Ln(4);
 
                $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
                $totalBoxY = $pdf->GetY();
                $pdf->Rect($totalesX - 2, $totalBoxY, $totalesW + 4, 10, 'F');
 
                $pdf->SetFont('Helvetica', 'B', 11);
                $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
                $pdf->SetXY($totalesX, $totalBoxY + 1.5);
                $pdf->Cell(40, 7, 'TOTAL:', 0, 0, 'R');
                $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
                $pdf->Cell(40, 7, '$' . number_format($totalReservacion, 0, '.', ',') . ' MXN', 0, 1, 'R');
            }
 
            // ═══════════════════════════════════════════════════════
            // TÉRMINOS Y CONDICIONES
            // ═══════════════════════════════════════════════════════
            $pdf->Ln(10);
            if ($pdf->GetY() > 220) {
                $pdf->AddPage();
            }
 
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('TÉRMINOS Y CONDICIONES'), 0, 1, 'L');
 
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);
 
            $fechaEntradaTexto = $formatFecha($reservacion['fecha_entrada']);
            $terminos = [
                'El alojamiento es por la noche del ' . $fechaEntradaTexto . ' con salida el dia siguiente a las doce del medio dia.',
                'El numero de personas se encuentra senalado en la tabla. En caso de ingresar mas personas se cobrara un excedente.',
                'CHECK IN: La hora de ingreso a las habitaciones es a las 15:00 hrs (3:00 PM).',
                'CHECK OUT: La hora para desocupar las habitaciones y salida del hotel es a las 12:00 hrs (12:00 PM).',
                'Esta cotizacion tiene una vigencia de 7 dias a partir de la fecha de elaboracion.',
                'Los precios pueden variar segun la temporada y disponibilidad al momento de confirmar.'
            ];
 
            $termH = 6 + (count($terminos) * 6) + 4;
            $termBoxY = $pdf->GetY();
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'D');
            $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Rect($margin, $termBoxY, 2.5, $termH, 'F');
 
            $pdf->SetXY($margin + 6, $termBoxY + 3);
            foreach ($terminos as $i => $termino) {
                $pdf->SetX($margin + 6);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
                $pdf->Cell(6, 5, ($i + 1) . '.', 0, 0, 'R');
                $pdf->SetFont('Helvetica', '', 7.5);
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->Cell($contentW - 14, 5, '  ' . $u($termino), 0, 1, 'L');
            }
 
            // ═══════════════════════════════════════════════════════
            // FOOTER
            // ═══════════════════════════════════════════════════════
            $footerY = 265;
            $pdf->SetY($footerY);
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($margin, $footerY, $margin + $contentW, $footerY);
            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor($grisCla[0], $grisCla[1], $grisCla[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW / 2, 4, $u('Hotel Los Cedros · Santa Catarina Juquila, Oaxaca'), 0, 0, 'L');
            $pdf->Cell($contentW / 2, 4, $u('Documento generado el ' . $hoy), 0, 1, 'R');
 
            // Output
            $nombreArchivo = 'Cotizacion_Res' . $reservacion_id . '_' . date('Ymd_His') . '.pdf';
            $pdf->Output('I', $nombreArchivo);
            exit;
 
        } catch (Exception $e) {
            error_log('ERROR en cotizacionReservacionPdfAction: ' . $e->getMessage());
            die('Error al generar la cotizacion: ' . $e->getMessage());
        }
    }


public function habitacionesApiAction() {
    header('Content-Type: application/json');
    
    $id = $this->route_params['id'] ?? 0;
    
    try {
        // Verificar que la reservación exista
        $reservacion = $this->reservacionModel->find($id);
        
        if (!$reservacion) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Reservación no encontrada'
            ]);
            exit;
        }
        
        // Obtener habitaciones con sus IDs reales
        $habitaciones = $this->reservacionModel->getHabitaciones($id);
        
        if (empty($habitaciones)) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No se encontraron habitaciones para esta reservación'
            ]);
            exit;
        }
        
        // Formatear respuesta
        $habitaciones_formateadas = array_map(function($hab) {
            return [
                'habitacion_id' => (int)$hab['habitacion_id'],  // ID REAL de la tabla habitaciones
                'numero' => $hab['numero'],
                'tipo' => $hab['tipo'],
                'piso' => $hab['piso'] ?? null,
                'precio' => $hab['precio'] ?? 0,
                'es_cortesia' => (bool)($hab['es_cortesia'] ?? false)
            ];
        }, $habitaciones);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'reservacion_id' => (int)$id,
            'total_habitaciones' => count($habitaciones_formateadas),
            'habitaciones' => $habitaciones_formateadas
        ]);
        
    } catch (Exception $e) {
        error_log("Error en habitacionesApiAction: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener habitaciones',
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

/**
 * Procesar recogida automática de controles remotos al hacer check-out
 */
private function procesarRecogidaRemotosCheckOut($reservacion_id) {
    try {
        // Cargar modelo de control de remotos
        require_once __DIR__ . '/../models/ControlRemoto.php';
        $controlRemoto = new ControlRemoto();
        
        // Obtener habitaciones de la reservación
        $habitaciones = $this->reservacionModel->getHabitaciones($reservacion_id);
        
        if (empty($habitaciones)) {
            error_log("No se encontraron habitaciones para la reservación: " . $reservacion_id);
            return;
        }
        
        $usuario_id = user_id();
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
        
        // Contador de controles recibidos
        $controles_recibidos = 0;
        
        foreach ($habitaciones as $hab) {
            // Verificar si el huésped tiene el control
            if (!$controlRemoto->hotelTieneRemoto($hab['habitacion_id'])) {
                // Recibir el control automáticamente
                $recibido = $controlRemoto->recibirRemoto(
                    $hab['habitacion_id'],
                    $reservacion_id,
                    $usuario_id,
                    'Check-out automático - ' . $usuario_nombre,
                    'Control recibido automáticamente durante check-out'
                );
                
                if ($recibido) {
                    $controles_recibidos++;
                    error_log("Control remoto recibido automáticamente - Habitación: " . $hab['numero']);
                } else {
                    error_log("Error al recibir control remoto - Habitación: " . $hab['numero']);
                }
            }
        }
        
        if ($controles_recibidos > 0) {
            // Modificar el mensaje de éxito del check-out
            $mensaje_actual = $_SESSION['flash_message']['texto'] ?? '';
            $_SESSION['flash_message']['texto'] = $mensaje_actual . 
                                                 ' Se recibieron ' . $controles_recibidos . ' control(es) remoto(s).';
        }
        
    } catch (Exception $e) {
        error_log("Error en procesarRecogidaRemotosCheckOut: " . $e->getMessage());
        // No detener el check-out por error en remotos
    }
}

/**
 * Check-out parcial - Liberar solo algunas habitaciones
 */
public function checkOutParcialAction() {
    $id = $this->route_params['id'] ?? 0;
    
    if (!$this->isPost()) {
        // Si es AJAX, devolver JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido'
            ]);
            exit;
        }
        
        $_SESSION['flash_message'] = [
            'tipo' => 'error',
            'texto' => 'Método no permitido'
        ];
        return $this->redirect('/reservaciones');
    }
    
    try {
        // Obtener habitaciones seleccionadas
        $habitaciones_ids = $this->getPost('habitaciones', []);
        
        // 🔍 DEBUG: Ver qué IDs se reciben
        error_log("=== DEBUG CHECK-OUT PARCIAL ===");
        error_log("ID Reservación: $id");
        error_log("IDs recibidos del POST: " . print_r($habitaciones_ids, true));
        
        if (empty($habitaciones_ids)) {
            // Si es AJAX, devolver JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Debe seleccionar al menos una habitación para liberar'
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'Debe seleccionar al menos una habitación para liberar'
            ];
            return $this->redirect('/reservaciones/ver/' . $id);
        }
        
        // Convertir a enteros
        $habitaciones_ids = array_map('intval', $habitaciones_ids);
        
        // Filtrar valores 0 o negativos
        $habitaciones_ids = array_filter($habitaciones_ids, function($id) {
            return $id > 0;
        });
        
        // Reindexar el array
        $habitaciones_ids = array_values($habitaciones_ids);
        
        error_log("IDs después de intval y filtrado: " . print_r($habitaciones_ids, true));
        
        // Verificar nuevamente después del filtrado
        if (empty($habitaciones_ids)) {
            // Si es AJAX, devolver JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'No se recibieron IDs válidos de habitaciones'
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'No se recibieron IDs válidos de habitaciones'
            ];
            return $this->redirect('/reservaciones/ver/' . $id);
        }
        
        // Obtener hora de salida
        $hora_salida = $this->getPost('hora_salida', date('H:i:s'));
        
        // Ejecutar check-out parcial
        $resultado = $this->reservacionModel->checkOutParcial($id, $habitaciones_ids, $hora_salida);
        
        if ($resultado['success']) {
            // Procesar recogida de llaves solo de las habitaciones liberadas
            $this->procesarRecogidaLlavesCheckOutParcial($id, $habitaciones_ids);
            
            // Procesar recogida de controles remotos
            $this->procesarRecogidaRemotosCheckOutParcial($id, $habitaciones_ids);
            
            $mensaje = 'Check-out realizado correctamente. ';
            $mensaje .= 'Se liberaron ' . $resultado['habitaciones_liberadas'] . ' habitación(es): ';
            $mensaje .= implode(', ', $resultado['numeros_liberados']);
            
            if (!$resultado['checkout_completo']) {
                $mensaje .= '. Quedan ' . $resultado['habitaciones_restantes'] . ' habitación(es) ocupada(s).';
            }
            
            // Si es AJAX, devolver JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $mensaje,
                    'checkout_completo' => $resultado['checkout_completo'],
                    'habitaciones_liberadas' => $resultado['habitaciones_liberadas'],
                    'habitaciones_restantes' => $resultado['habitaciones_restantes'],
                    'numeros_liberados' => $resultado['numeros_liberados']
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = [
                'tipo' => 'success',
                'texto' => $mensaje
            ];
            
            // Si es check-out completo, redirigir al índice, sino volver a ver
            if ($resultado['checkout_completo']) {
                return $this->redirect('/reservaciones');
            } else {
                return $this->redirect('/reservaciones/ver/' . $id);
            }
        } else {
            // Si es AJAX, devolver JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $resultado['error'] ?? 'Error desconocido'
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'Error: ' . ($resultado['error'] ?? 'Error desconocido')
            ];
            return $this->redirect('/reservaciones/ver/' . $id);
        }
        
    } catch (Exception $e) {
        error_log("Error en checkOutParcialAction: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Si es AJAX, devolver JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al realizar check-out: ' . $e->getMessage()
            ]);
            exit;
        }
        
        $_SESSION['flash_message'] = [
            'tipo' => 'error',
            'texto' => 'Error al realizar check-out: ' . $e->getMessage()
        ];
        return $this->redirect('/reservaciones/ver/' . $id);
    }
}


/**
 * Procesar recogida de llaves solo para habitaciones específicas
 */
private function procesarRecogidaLlavesCheckOutParcial($reservacion_id, $habitaciones_ids) {
    try {
        require_once __DIR__ . '/../models/ControlLlave.php';
        $controlLlave = new ControlLlave();
        
        $usuario_id = user_id();
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
        
        foreach ($habitaciones_ids as $hab_id) {
            if (!$controlLlave->hotelTieneLlave($hab_id)) {
                $controlLlave->recibirLlave(
                    $hab_id,
                    $reservacion_id,
                    $usuario_id,
                    'Check-out parcial - ' . $usuario_nombre,
                    'Llave recibida automáticamente durante check-out parcial'
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error en procesarRecogidaLlavesCheckOutParcial: " . $e->getMessage());
    }
}

/**
 * Procesar recogida de controles remotos solo para habitaciones específicas
 */
private function procesarRecogidaRemotosCheckOutParcial($reservacion_id, $habitaciones_ids) {
    try {
        require_once __DIR__ . '/../models/ControlRemoto.php';
        $controlRemoto = new ControlRemoto();
        
        $usuario_id = user_id();
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
        
        foreach ($habitaciones_ids as $hab_id) {
            if (!$controlRemoto->hotelTieneRemoto($hab_id)) {
                $controlRemoto->recibirRemoto(
                    $hab_id,
                    $reservacion_id,
                    $usuario_id,
                    'Check-out parcial - ' . $usuario_nombre,
                    'Control recibido automáticamente durante check-out parcial'
                );
            }
        }
    } catch (Exception $e) {
        error_log("Error en procesarRecogidaRemotosCheckOutParcial: " . $e->getMessage());
    }
}

/**
 * Obtener notas de una reservación (AJAX)
 */
public function obtenerNotasAction() {
    $reservacion_id = intval($this->getQuery('reservacion_id'));
    
    if (!$reservacion_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID de reservación inválido']);
        exit;
    }
    
    require_once __DIR__ . '/../models/ReservacionNota.php';
    $notaModel = new ReservacionNota();
    
    $notas = $notaModel->obtenerPorReservacion($reservacion_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notas' => $notas
    ]);
    exit;
}
    
    public function exportarPDFAction() {
    try {
        $fecha = $this->getQuery('fecha', date('Y-m-d'));
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('Formato de fecha inválido');
        }
        
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        
        // Fecha bonita en español
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $dias_semana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $ts = strtotime($fecha);
        $fecha_bonita = $dias_semana[date('w', $ts)] . ', ' . date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
        
        // Obtener todas las habitaciones — numéricas primero, luego colores
        $sql = "SELECT id, numero, tipo, precio_base 
                FROM habitaciones 
                WHERE activa = 1 
                ORDER BY 
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN 0 ELSE 1 END,
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED) ELSE 0 END,
                    numero";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener reservaciones activas (NO incluir día de check-out)
        $sql = "SELECT 
                r.id, r.huesped_id, r.estado, r.fecha_entrada, r.fecha_salida,
                r.precio_total, r.metodo_pago, r.hora_entrada, r.notas,
                h.nombre_completo, h.telefono, h.procedencia_estado, h.procedencia_ciudad,
                GROUP_CONCAT(rh.habitacion_id) as habitaciones_ids,
                GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros
            FROM reservaciones r
            INNER JOIN huespedes h ON r.huesped_id = h.id
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
            LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
            WHERE ? >= r.fecha_entrada AND ? < r.fecha_salida
            AND r.estado NOT IN ('cancelada', 'completada')
            GROUP BY r.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fecha, $fecha]);
        $reservaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vehículos
        $vehiculos_por_huesped = [];
        if (!empty($reservaciones)) {
            $huespedes_ids = array_unique(array_column($reservaciones, 'huesped_id'));
            if (!empty($huespedes_ids)) {
                $placeholders = str_repeat('?,', count($huespedes_ids) - 1) . '?';
                $stmt = $this->db->prepare("SELECT huesped_id, marca, modelo, placas, color FROM huesped_vehiculos WHERE huesped_id IN ($placeholders)");
                $stmt->execute(array_values($huespedes_ids));
                while ($v = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $vehiculos_por_huesped[$v['huesped_id']] = $v;
                }
            }
        }

        // Facturas
        $facturas_por_reservacion = [];
        if (!empty($reservaciones)) {
            $res_ids = array_column($reservaciones, 'id');
            if (!empty($res_ids)) {
                $placeholders = str_repeat('?,', count($res_ids) - 1) . '?';
                try {
                    $stmt = $this->db->prepare("SELECT reservacion_id FROM solicitudes_factura WHERE reservacion_id IN ($placeholders) AND requiere_factura = 'si'");
                    $stmt->execute(array_values($res_ids));
                    while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $facturas_por_reservacion[$f['reservacion_id']] = true;
                    }
                } catch (Exception $e) {}
            }
        }
        
        // Mapear ocupación por habitación
        $ocupacion_por_habitacion = [];
        foreach ($reservaciones as $r) {
            if (!empty($r['habitaciones_ids'])) {
                $stmt2 = $this->db->prepare("SELECT rh.habitacion_id, rh.precio, rh.es_cortesia FROM reservacion_habitaciones rh WHERE rh.reservacion_id = ?");
                $stmt2->execute([$r['id']]);
                $precios_hab = [];
                while ($ph = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                    $precios_hab[$ph['habitacion_id']] = $ph;
                }
                
                foreach (explode(',', $r['habitaciones_ids']) as $hid) {
                    $hid = trim($hid);
                    $ocupacion_por_habitacion[$hid] = $r;
                    $ocupacion_por_habitacion[$hid]['precio_hab'] = $precios_hab[$hid]['precio'] ?? null;
                    $ocupacion_por_habitacion[$hid]['es_cortesia_hab'] = $precios_hab[$hid]['es_cortesia'] ?? 0;
                    if (isset($vehiculos_por_huesped[$r['huesped_id']])) {
                        $ocupacion_por_habitacion[$hid]['vehiculo'] = $vehiculos_por_huesped[$r['huesped_id']];
                    }
                    if (isset($facturas_por_reservacion[$r['id']])) {
                        $ocupacion_por_habitacion[$hid]['tiene_factura'] = true;
                    }
                }
            }
        }
        
        // Separar habitaciones
        $hab_numericas = [];
        $hab_color = [];
        foreach ($habitaciones as $hab) {
            if (preg_match('/^\d+$/', trim($hab['numero']))) {
                $hab_numericas[] = $hab;
            } else {
                $hab_color[] = $hab;
            }
        }
        
        // Estadísticas
        $total_habitaciones = count($habitaciones);
        $habitaciones_con_checkin = 0;
        $habitaciones_reservadas = 0;
        $habitaciones_disponibles = 0;
        foreach ($habitaciones as $hab) {
            if (isset($ocupacion_por_habitacion[$hab['id']])) {
                $ocupacion_por_habitacion[$hab['id']]['estado'] == 'checked_in' ? $habitaciones_con_checkin++ : $habitaciones_reservadas++;
            } else {
                $habitaciones_disponibles++;
            }
        }
        
        // Generar HTML compacto para 2 páginas landscape
        $html = $this->generarHTMLReservacionesPersonalizado(
            $fecha,
            $fecha_bonita,
            $hab_numericas,
            $hab_color,
            $ocupacion_por_habitacion,
            $total_habitaciones,
            $habitaciones_con_checkin,
            $habitaciones_reservadas,
            $habitaciones_disponibles
        );
        
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        
    } catch (Exception $e) {
        error_log("Error en exportarPDFAction: " . $e->getMessage());
        set_mensaje('Error al generar el PDF: ' . $e->getMessage(), 'error');
        $this->redirect('reservaciones');
    }
}

    public function exportarExcelAction() {
        try {
            $fecha = $this->getQuery('fecha', date('Y-m-d'));

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                throw new Exception('Formato de fecha inválido');
            }

            // Fecha bonita en español
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $dias_semana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
            $ts = strtotime($fecha);
            $fecha_bonita = $dias_semana[date('w', $ts)] . ', ' . date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);

            // Obtener todas las habitaciones activas ordenadas
            $stmt = $this->db->prepare("SELECT id, numero, tipo, precio_base 
                FROM habitaciones WHERE activa = 1 
                ORDER BY 
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN 0 ELSE 1 END,
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED) ELSE 0 END,
                    numero");
            $stmt->execute();
            $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener reservaciones activas en esa fecha (NO incluir día de check-out)
            $stmt = $this->db->prepare("SELECT r.id, r.huesped_id, r.estado, r.fecha_entrada, r.fecha_salida,
                r.precio_total, r.metodo_pago, r.notas,
                h.nombre_completo, h.telefono, h.procedencia_estado, h.procedencia_ciudad,
                GROUP_CONCAT(rh.habitacion_id) as habitaciones_ids,
                GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                rh.precio as precio_habitacion,
                rh.es_cortesia
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id
                LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id
                WHERE ? >= r.fecha_entrada AND ? < r.fecha_salida
                AND r.estado NOT IN ('cancelada', 'completada')
                GROUP BY r.id");
            $stmt->execute([$fecha, $fecha]);
            $reservaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener vehículos
            $vehiculos_por_huesped = [];
            if (!empty($reservaciones)) {
                $huespedes_ids = array_unique(array_column($reservaciones, 'huesped_id'));
                if (!empty($huespedes_ids)) {
                    $placeholders = str_repeat('?,', count($huespedes_ids) - 1) . '?';
                    $stmt = $this->db->prepare("SELECT huesped_id, marca, modelo, placas, color FROM huesped_vehiculos WHERE huesped_id IN ($placeholders)");
                    $stmt->execute(array_values($huespedes_ids));
                    while ($v = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $vehiculos_por_huesped[$v['huesped_id']] = $v;
                    }
                }
            }

            // Obtener facturas
            $facturas_por_reservacion = [];
            if (!empty($reservaciones)) {
                $res_ids = array_column($reservaciones, 'id');
                if (!empty($res_ids)) {
                    $placeholders = str_repeat('?,', count($res_ids) - 1) . '?';
                    $stmt = $this->db->prepare("SELECT reservacion_id, requiere_factura FROM solicitudes_factura WHERE reservacion_id IN ($placeholders) AND requiere_factura = 'si'");
                    try {
                        $stmt->execute(array_values($res_ids));
                        while ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $facturas_por_reservacion[$f['reservacion_id']] = true;
                        }
                    } catch (Exception $e) {
                        // Tabla puede no existir, ignorar
                    }
                }
            }

            // Mapear ocupación por habitación (con precio individual)
            $ocupacion = [];
            foreach ($reservaciones as $r) {
                if (!empty($r['habitaciones_ids'])) {
                    // Obtener precios individuales por habitación
                    $stmt2 = $this->db->prepare("SELECT rh.habitacion_id, rh.precio, rh.es_cortesia 
                        FROM reservacion_habitaciones rh 
                        WHERE rh.reservacion_id = ?");
                    $stmt2->execute([$r['id']]);
                    $precios_hab = [];
                    while ($ph = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                        $precios_hab[$ph['habitacion_id']] = $ph;
                    }
                    
                    foreach (explode(',', $r['habitaciones_ids']) as $hid) {
                        $hid = trim($hid);
                        $ocupacion[$hid] = $r;
                        $ocupacion[$hid]['precio_hab'] = $precios_hab[$hid]['precio'] ?? null;
                        $ocupacion[$hid]['es_cortesia_hab'] = $precios_hab[$hid]['es_cortesia'] ?? 0;
                        if (isset($vehiculos_por_huesped[$r['huesped_id']])) {
                            $ocupacion[$hid]['vehiculo'] = $vehiculos_por_huesped[$r['huesped_id']];
                        }
                        if (isset($facturas_por_reservacion[$r['id']])) {
                            $ocupacion[$hid]['tiene_factura'] = true;
                        }
                    }
                }
            }

            // Separar habitaciones en numéricas y de color
            $hab_numericas = [];
            $hab_color = [];
            foreach ($habitaciones as $hab) {
                if (preg_match('/^\d+$/', trim($hab['numero']))) {
                    $hab_numericas[] = $hab;
                } else {
                    $hab_color[] = $hab;
                }
            }

            // Nombre de archivo
            $nombre_archivo = 'Reservaciones_' . str_replace('-', '', $fecha) . '.xls';

            // Headers para descarga de Excel
            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            // BOM para UTF-8
            echo "\xEF\xBB\xBF";

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
            echo '<x:Name>Reservaciones</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
            echo '</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head>';
            echo '<body>';

            // Helper para generar una sección
            $generarSeccion = function($lista_habs) use ($ocupacion, $fecha_bonita) {
                $estilo_th = 'background:#2E7D32;color:white;font-weight:bold;padding:6px 8px;text-align:center;font-size:10pt;';
                $columnas = ['HUÉSPED', 'TELÉFONO', 'PROCEDENCIA', 'HABITACIÓN', 'COSTO', 'FACTURA', 'VEHÍCULO', 'TIPO/ESTADO DE PAGO'];

                echo '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;font-family:Arial;font-size:10pt;margin-bottom:10px;">';
                
                // Fila de fecha
                echo '<tr><td colspan="8" style="font-weight:bold;padding:8px;font-size:11pt;">' . htmlspecialchars($fecha_bonita) . '</td></tr>';

                // Encabezados
                echo '<tr>';
                foreach ($columnas as $col) {
                    echo '<td style="' . $estilo_th . '">' . $col . '</td>';
                }
                echo '</tr>';

                // Filas
                foreach ($lista_habs as $hab) {
                    $ocupada = isset($ocupacion[$hab['id']]);
                    $res = $ocupada ? $ocupacion[$hab['id']] : null;

                    if ($ocupada) {
                        $es_checkin = ($res['estado'] == 'checked_in');
                        $bg = $es_checkin ? '#FFCDD2' : '#E1BEE7';

                        // Procedencia
                        $procedencia = trim(($res['procedencia_ciudad'] ?? '') . ' ' . ($res['procedencia_estado'] ?? ''));

                        // Precio de esta habitación
                        $precio_mostrar = $res['precio_hab'] ?? $hab['precio_base'];
                        $precio_fmt = '$' . number_format($precio_mostrar, 0, '.', '.');
                        if ($res['es_cortesia_hab'] ?? false) {
                            $precio_fmt = 'Cortesía';
                        }

                        // Vehículo
                        $vehiculo = '';
                        if (isset($res['vehiculo'])) {
                            $v = $res['vehiculo'];
                            $partes = [];
                            if (!empty($v['marca'])) $partes[] = $v['marca'];
                            if (!empty($v['modelo'])) $partes[] = $v['modelo'];
                            if (!empty($v['color'])) $partes[] = $v['color'];
                            $vehiculo = implode(' ', $partes);
                        } else {
                            $vehiculo = 'Sin vehiculo';
                        }

                        // Tipo/estado de pago
                        $metodo = ucfirst($res['metodo_pago'] ?? '');
                        $estado_pago = $es_checkin ? 'Pagado' : 'Pendiente';
                        $tipo_pago = $metodo ? $metodo . ' - ' . $estado_pago : '';

                        // Factura
                        $factura_txt = isset($res['tiene_factura']) ? 'Sí' : '';

                        echo '<tr>';
                        echo '<td style="background:' . $bg . ';padding:5px 8px;">' . htmlspecialchars($res['nombre_completo'] ?? '') . '</td>';
                        echo '<td style="background:' . $bg . ';text-align:center;">' . htmlspecialchars($res['telefono'] ?? '') . '</td>';
                        echo '<td style="background:' . $bg . ';">' . htmlspecialchars($procedencia) . '</td>';
                        echo '<td style="background:' . $bg . ';font-weight:bold;text-align:center;">' . htmlspecialchars($hab['numero']) . '</td>';
                        echo '<td style="background:' . $bg . ';text-align:right;">' . $precio_fmt . '</td>';
                        echo '<td style="background:' . $bg . ';text-align:center;">' . $factura_txt . '</td>';
                        echo '<td style="background:' . $bg . ';">' . htmlspecialchars($vehiculo) . '</td>';
                        echo '<td style="background:' . $bg . ';">' . htmlspecialchars($tipo_pago) . '</td>';
                        echo '</tr>';
                    } else {
                        // Habitación disponible — solo número y precio
                        $precio_fmt = '$' . number_format($hab['precio_base'], 0, '.', '.');
                        echo '<tr>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '<td style="background:#C8E6C9;font-weight:bold;text-align:center;">' . htmlspecialchars($hab['numero']) . '</td>';
                        echo '<td style="background:#C8E6C9;text-align:right;">' . $precio_fmt . '</td>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '<td style="background:#C8E6C9;"></td>';
                        echo '</tr>';
                    }
                }

                echo '</table>';
            };

            // Sección 1: Habitaciones numéricas
            if (!empty($hab_numericas)) {
                $generarSeccion($hab_numericas);
            }

            // Sección 2: Habitaciones de color
            if (!empty($hab_color)) {
                $generarSeccion($hab_color);
            }

            echo '</body></html>';
            exit;

        } catch (Exception $e) {
            error_log("Error en exportarExcelAction: " . $e->getMessage());
            $this->redirect('reservaciones');
        }
    }

/**
 * Generar HTML compacto para reporte de reservaciones — 2 páginas landscape
 * Página 1: Habitaciones numéricas | Página 2: Habitaciones de color
 * Columnas: HUÉSPED, TELÉFONO, PROCEDENCIA, HABITACIÓN, COSTO, FACTURA, VEHÍCULO, TIPO/ESTADO DE PAGO
 */
private function generarHTMLReservacionesPersonalizado(
    $fecha,
    $fecha_bonita,
    $hab_numericas,
    $hab_color,
    $ocupacion_por_habitacion,
    $total_habitaciones,
    $habitaciones_con_checkin,
    $habitaciones_reservadas,
    $habitaciones_disponibles
) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reservaciones - <?= htmlspecialchars($fecha_bonita) ?></title>
        <style>
            @page {
                size: A4 landscape;
                margin: 0.4cm 0.5cm;
            }
            @media print {
                body { margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
                .no-print { display: none !important; }
                .page-break { page-break-before: always; }
            }
            * { box-sizing: border-box; }
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 8pt;
                line-height: 1.15;
                margin: 0;
                padding: 6px;
                background: white;
            }
            .header {
                background: linear-gradient(135deg, #1565C0, #1976D2);
                color: white;
                text-align: center;
                padding: 6px 10px;
                border-radius: 4px;
                margin-bottom: 4px;
                font-size: 11pt;
                font-weight: bold;
            }
            .header small { font-size: 8pt; font-weight: normal; opacity: .85; }
            .stats-bar {
                display: flex;
                justify-content: center;
                gap: 8px;
                margin-bottom: 4px;
                font-size: 7.5pt;
                font-weight: bold;
            }
            .stats-bar span {
                padding: 2px 8px;
                border-radius: 3px;
                border: 1px solid;
            }
            .stat-total { background: #E3F2FD; border-color: #1565C0; color: #0D47A1; }
            .stat-ci { background: #FFCDD2; border-color: #D32F2F; color: #B71C1C; }
            .stat-res { background: #E1BEE7; border-color: #7B1FA2; color: #4A148C; }
            .stat-disp { background: #C8E6C9; border-color: #388E3C; color: #1B5E20; }
            
            .seccion-titulo {
                background: #263238;
                color: white;
                padding: 3px 10px;
                font-size: 8.5pt;
                font-weight: bold;
                border-radius: 3px 3px 0 0;
                margin-top: 2px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 7.5pt;
            }
            th {
                background: #37474F;
                color: white;
                padding: 3px 4px;
                text-align: center;
                border: 1px solid #263238;
                font-size: 7pt;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
            td {
                padding: 2px 4px;
                border: 1px solid #999;
                vertical-align: middle;
            }
            .con-checkin td { background: #FFCDD2; }
            .reservada td { background: #E1BEE7; }
            .disponible td { background: #C8E6C9; }
            .hab-num { font-weight: bold; text-align: center; font-size: 8.5pt; }
            .huesped { font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
            .precio { text-align: right; font-weight: bold; white-space: nowrap; }
            .tel { font-family: 'Courier New', monospace; font-size: 7pt; text-align: center; }
            .pago { font-size: 6.5pt; white-space: nowrap; }
            .vehiculo { font-size: 6.5pt; }
            .procedencia { font-size: 6.5pt; }
            
            .pie {
                text-align: center;
                font-size: 6.5pt;
                color: #777;
                margin-top: 3px;
            }
            .boton-imprimir {
                position: fixed; top: 8px; right: 8px;
                background: #1565C0; color: white; border: none;
                padding: 10px 20px; border-radius: 5px;
                cursor: pointer; font-weight: bold; font-size: 11pt;
                z-index: 1000; box-shadow: 0 3px 8px rgba(0,0,0,.3);
            }
            .boton-imprimir:hover { background: #0D47A1; }
        </style>
    </head>
    <body>
        <button class="boton-imprimir no-print" onclick="window.print()">🖨️ IMPRIMIR</button>
        
        <?php
        // Función interna para generar tabla de una sección
        $renderTabla = function($lista_habs, $titulo) use ($ocupacion_por_habitacion) {
        ?>
            <div class="seccion-titulo"><?= $titulo ?></div>
            <table>
                <thead>
                    <tr>
                        <th style="width:18%;">Huésped</th>
                        <th style="width:9%;">Teléfono</th>
                        <th style="width:10%;">Procedencia</th>
                        <th style="width:7%;">Hab.</th>
                        <th style="width:7%;">Costo</th>
                        <th style="width:5%;">Fact.</th>
                        <th style="width:14%;">Vehículo</th>
                        <th style="width:14%;">Tipo/Estado de Pago</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista_habs as $hab):
                    $ocupada = isset($ocupacion_por_habitacion[$hab['id']]);
                    $res = $ocupada ? $ocupacion_por_habitacion[$hab['id']] : null;
                    
                    if ($ocupada) {
                        $es_checkin = ($res['estado'] == 'checked_in');
                        $clase = $es_checkin ? 'con-checkin' : 'reservada';
                        
                        $procedencia = trim(($res['procedencia_ciudad'] ?? '') . ' ' . ($res['procedencia_estado'] ?? ''));
                        
                        $precio_mostrar = $res['precio_hab'] ?? $hab['precio_base'];
                        $precio_fmt = '$' . number_format($precio_mostrar, 0, '.', ',');
                        if ($res['es_cortesia_hab'] ?? false) { $precio_fmt = 'Cortesía'; }
                        
                        $vehiculo = '';
                        if (isset($res['vehiculo'])) {
                            $v = $res['vehiculo'];
                            $partes = [];
                            if (!empty($v['marca'])) $partes[] = $v['marca'];
                            if (!empty($v['modelo'])) $partes[] = $v['modelo'];
                            if (!empty($v['color'])) $partes[] = $v['color'];
                            $vehiculo = implode(' ', $partes);
                        } else {
                            $vehiculo = 'Sin vehículo';
                        }
                        
                        $metodo = ucfirst($res['metodo_pago'] ?? '');
                        $estado_pago = $es_checkin ? 'Pagado' : 'Pendiente';
                        $tipo_pago = $metodo ? $metodo . ' - ' . $estado_pago : '';
                        
                        $factura_txt = isset($res['tiene_factura']) ? 'Sí' : '';
                    ?>
                    <tr class="<?= $clase ?>">
                        <td class="huesped"><?= htmlspecialchars($res['nombre_completo'] ?? '') ?></td>
                        <td class="tel"><?= htmlspecialchars($res['telefono'] ?? '') ?></td>
                        <td class="procedencia"><?= htmlspecialchars($procedencia) ?></td>
                        <td class="hab-num"><?= htmlspecialchars($hab['numero']) ?></td>
                        <td class="precio"><?= $precio_fmt ?></td>
                        <td style="text-align:center;font-size:6.5pt;"><?= $factura_txt ?></td>
                        <td class="vehiculo"><?= htmlspecialchars($vehiculo) ?></td>
                        <td class="pago"><?= htmlspecialchars($tipo_pago) ?></td>
                    </tr>
                    <?php } else { ?>
                    <tr class="disponible">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="hab-num"><?= htmlspecialchars($hab['numero']) ?></td>
                        <td class="precio">$<?= number_format($hab['precio_base'], 0, '.', ',') ?></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <?php } ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        };
        ?>
        
        <!-- ═══ PÁGINA 1: Habitaciones Numéricas ═══ -->
        <div class="header">
            HOTEL LOS CEDROS — CONTROL DE HABITACIONES
            <br><small><?= htmlspecialchars($fecha_bonita) ?></small>
        </div>
        
        <div class="stats-bar">
            <span class="stat-total">Total: <?= $total_habitaciones ?></span>
            <span class="stat-ci">Check-in: <?= $habitaciones_con_checkin ?></span>
            <span class="stat-res">Reservadas: <?= $habitaciones_reservadas ?></span>
            <span class="stat-disp">Disponibles: <?= $habitaciones_disponibles ?></span>
        </div>
        
        <?php if (!empty($hab_numericas)): ?>
            <?php $renderTabla($hab_numericas, 'HABITACIONES NUMÉRICAS (' . count($hab_numericas) . ')'); ?>
        <?php endif; ?>
        
        <div class="pie">
            Los Cedros — Santa Catarina Juquila, Oaxaca · Impreso <?= date('d/m/Y H:i') ?> hrs
        </div>
        
        <!-- ═══ PÁGINA 2: Habitaciones de Color ═══ -->
        <?php if (!empty($hab_color)): ?>
        <div class="page-break"></div>
        
        <div class="header">
            HOTEL LOS CEDROS — CONTROL DE HABITACIONES
            <br><small><?= htmlspecialchars($fecha_bonita) ?></small>
        </div>
        
        <div class="stats-bar">
            <span class="stat-total">Total: <?= $total_habitaciones ?></span>
            <span class="stat-ci">Check-in: <?= $habitaciones_con_checkin ?></span>
            <span class="stat-res">Reservadas: <?= $habitaciones_reservadas ?></span>
            <span class="stat-disp">Disponibles: <?= $habitaciones_disponibles ?></span>
        </div>
        
        <?php $renderTabla($hab_color, 'HABITACIONES DE COLOR (' . count($hab_color) . ')'); ?>
        
        <div class="pie">
            Los Cedros — Santa Catarina Juquila, Oaxaca · Impreso <?= date('d/m/Y H:i') ?> hrs
        </div>
        <?php endif; ?>
        
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    if (!window.location.search.includes('no-auto-print')) {
                        window.print();
                    }
                }, 800);
            });
        </script>
    </body>
    </html>
    <?php
    return ob_get_clean();
}






/**
 * Generar HTML para el reporte de reservaciones
 */


    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        return true;
    }
    
    /**
     * Listado de reservaciones
     */
public function indexAction() {
    $buscar = $this->getQuery('buscar');
    $fecha = $this->getQuery('fecha', date('Y-m-d'));
    
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $fecha = date('Y-m-d');
    }
    
    // DEBUG: descomenta esta línea si sigue fallando para ver qué fecha llega
    // error_log("=== INDEX RESERVACIONES === Fecha recibida: " . $fecha);
    
    $reservaciones = $this->reservacionModel->obtenerHabitacionesReservadasPorFecha($fecha, $buscar);
    
    // DEBUG: descomenta para ver cuántos resultados
    // error_log("Resultados: " . count($reservaciones) . " habitaciones para fecha " . $fecha);
    
    $estadisticas = $this->reservacionModel->obtenerEstadisticasDashboard();
    $entradas_hoy = $this->reservacionModel->obtenerEntradasHoy();
    $salidas_hoy = $this->reservacionModel->obtenerSalidasHoy();
    
    View::renderTemplate('reservaciones/index', [
        'title' => 'Reservaciones - Los Cedros',
        'reservaciones' => $reservaciones,
        'buscar' => $buscar,
        'fecha_filtro' => $fecha,
        'estados' => Reservacion::getEstados(),
        'total_reservaciones' => count($reservaciones),
        'estadisticas' => $estadisticas,
        'entradas_hoy' => $entradas_hoy,
        'salidas_hoy' => $salidas_hoy
    ]);
}
    // ========== AGREGAR ESTOS MÉTODOS EN ReservacionController.php DESPUÉS DEL MÉTODO guardarAction() ==========

    /**
     * Mostrar vista para editar habitaciones de una reservación
     */
    public function editarHabitacionesAction() {
    $id = $this->route_params['id'] ?? 0;
    
    $reservacion = $this->reservacionModel->obtenerPorId($id);
    
    if (!$reservacion) {
        set_mensaje('Reservación no encontrada', 'error');
        $this->redirect('reservaciones');
        return;
    }
    
    $hotel_id = $this->hotelIdActual();

    // Solo permitir edición en estado confirmada
    if ($reservacion['estado'] !== 'confirmada') {
        set_mensaje('Solo se pueden modificar habitaciones en reservaciones confirmadas', 'warning');
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    // Obtener huésped
    $huesped = $this->huespedModel->find($reservacion['huesped_id']);
    
    // Obtener habitaciones seleccionadas de la reservación
    $habitaciones_seleccionadas = $this->reservacionModel->getHabitaciones($id);
    
    // Calcular noches
    $fecha_inicio = new DateTime($reservacion['fecha_entrada']);
    $fecha_fin = new DateTime($reservacion['fecha_salida']);
    $noches = $fecha_inicio->diff($fecha_fin)->days;
    
    if ($noches == 0) {
        $noches = 1;
    }
    
    // Obtener todas las habitaciones disponibles
    $habitaciones = $this->habitacionModel->where(['activa' => 1]);
    
    // ====== NUEVA LÓGICA: Calcular precios con incrementos ======
    // Cargar modelo de tarifas
    if (!class_exists('IncrementoTarifa')) {
        require_once __DIR__ . '/../models/IncrementoTarifa.php';
    }
    $tarifaModel = new IncrementoTarifa();
    
    // Para cada habitación, verificar ocupación y calcular precio con incrementos
    foreach ($habitaciones as &$hab) {
        // Verificar ocupación
        $sql = "SELECT r.id, r.fecha_entrada, r.fecha_salida, h.nombre_completo as huesped_nombre
                FROM reservacion_habitaciones rh
                INNER JOIN reservaciones r ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                INNER JOIN huespedes h ON r.huesped_id = h.id
                WHERE rh.habitacion_id = ?
                AND r.id != ?  -- Excluir la reservación actual
                AND r.hotel_id = ?
                AND rh.hotel_id = ?
                AND r.estado IN ('confirmada', 'checked_in')
                AND (
                    (? BETWEEN r.fecha_entrada AND DATE_SUB(r.fecha_salida, INTERVAL 1 DAY))
                    OR (? BETWEEN DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY) AND r.fecha_salida)
                    OR (r.fecha_entrada >= ? AND r.fecha_salida <= ?)
                )
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $hab['id'], 
            $id,
            $hotel_id,
            $hotel_id,
            $reservacion['fecha_entrada'],
            $reservacion['fecha_salida'],
            $reservacion['fecha_entrada'],
            $reservacion['fecha_salida']
        ]);
        
        $ocupacion = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ocupacion) {
            $hab['ocupacion'] = $ocupacion;
        }
        
        // ====== CALCULAR PRECIO CON INCREMENTOS ======
        $precio_total_habitacion = 0;
        $fecha_actual = clone $fecha_inicio;
        
        for ($i = 0; $i < $noches; $i++) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            $precio_info = $tarifaModel->calcularPrecioConIncremento(
                $hab['id'],
                $hab['tipo'],
                $hab['precio_base'],
                $fecha_str
            );
            
            $precio_total_habitacion += $precio_info['precio_final'];
            $fecha_actual->modify('+1 day');
        }
        
        // Agregar precio calculado con incrementos (para todas las noches)
        $hab['precio_con_incremento'] = $precio_total_habitacion;
        $hab['precio_por_noche'] = $precio_total_habitacion / $noches;
        $hab['tiene_incremento'] = ($precio_total_habitacion > ($hab['precio_base'] * $noches));
        
        error_log("Habitación {$hab['numero']}: Precio base total = " . ($hab['precio_base'] * $noches) . 
                  ", Precio con incrementos = {$precio_total_habitacion}");
        // ====== FIN CÁLCULO ======
    }
    
    // También calcular precio con incrementos para habitaciones ya seleccionadas
    foreach ($habitaciones_seleccionadas as &$hab_sel) {
        $precio_total_habitacion = 0;
        $fecha_actual = clone $fecha_inicio;
        
        for ($i = 0; $i < $noches; $i++) {
            $fecha_str = $fecha_actual->format('Y-m-d');
            
            $precio_info = $tarifaModel->calcularPrecioConIncremento(
                $hab_sel['habitacion_id'],
                $hab_sel['tipo'],
                $hab_sel['precio_base'],
                $fecha_str
            );
            
            $precio_total_habitacion += $precio_info['precio_final'];
            $fecha_actual->modify('+1 day');
        }
        
        // IMPORTANTE: Actualizar el precio que se pasa a la vista
        // Si ya tiene un precio guardado y es diferente, significa que antes no tenía incrementos
        $hab_sel['precio_calculado_nuevo'] = $precio_total_habitacion;
        $hab_sel['precio_por_noche'] = $precio_total_habitacion / $noches;
        
        error_log("Habitación seleccionada {$hab_sel['numero']}: " .
                  "Precio guardado en BD = {$hab_sel['precio']}, " .
                  "Precio que debería ser = {$precio_total_habitacion}");
    }
    
    View::renderTemplate('reservaciones/editar-habitaciones', [
        'title' => 'Modificar Habitaciones - Reservación #' . $id,
        'reservacion' => $reservacion,
        'huesped' => $huesped,
        'habitaciones' => $habitaciones,
        'habitaciones_seleccionadas' => $habitaciones_seleccionadas,
        'noches' => $noches
    ]);
}
    
    /**
     * Actualizar habitaciones de una reservación
     */
    public function actualizarHabitacionesAction() {
        if (!$this->isPost()) {
            $this->redirect('reservaciones');
            return;
        }
        
        $this->validateCSRF();
        
        try {
            // Obtener datos del POST
            $reservacion_id = intval($this->getPost('reservacion_id'));
            $habitaciones_ids = $this->getPost('habitaciones', []);
            $habitaciones_ids = array_values(array_unique(array_map('intval', (array) $habitaciones_ids)));
            $cortesias_ids = $this->getPost('cortesias', []);
            
            error_log("=== ACTUALIZAR HABITACIONES ===");
            error_log("Reservación ID: " . $reservacion_id);
            error_log("Habitaciones seleccionadas: " . json_encode($habitaciones_ids));
            error_log("Cortesías seleccionadas: " . json_encode($cortesias_ids));
            
            // Validar que existe la reservación
            $reservacion = $this->reservacionModel->obtenerPorId($reservacion_id);
            if (!$reservacion) {
                throw new Exception('Reservación no encontrada');
            }
            
           if ($reservacion['estado'] !== 'confirmada') {
    throw new Exception('Solo se pueden modificar habitaciones en reservaciones confirmadas');
}
            
            // Validar que hay habitaciones
            if (empty($habitaciones_ids)) {
                throw new Exception('Debe seleccionar al menos una habitación');
            }
            
            // Obtener información de las habitaciones
            $habitaciones = [];
            foreach ($habitaciones_ids as $hab_id) {
                $hab = $this->habitacionModel->find($hab_id);
                if ($hab) {
                    $habitaciones[] = $hab;
                }
            }

            if (count($habitaciones) !== count(array_unique(array_map('intval', $habitaciones_ids)))) {
                throw new Exception('Una o mas habitaciones no pertenecen al hotel actual');
            }
            
            // Verificar disponibilidad de las nuevas habitaciones
            $disponible = $this->reservacionModel->verificarDisponibilidadMultipleExcluyendo(
                $habitaciones_ids, 
                $reservacion['fecha_entrada'], 
                $reservacion['fecha_salida'],
                $reservacion_id
            );
            
            if (!$disponible) {
                throw new Exception('Una o más habitaciones no están disponibles');
            }
            
            // Actualizar habitaciones usando el modelo
            if (method_exists($this->reservacionModel, 'actualizarHabitaciones')) {
                $resultado = $this->reservacionModel->actualizarHabitaciones(
                    $reservacion_id,
                    $habitaciones,
                    $cortesias_ids,
                    $reservacion['fecha_entrada'],
                    $reservacion['fecha_salida'],
                    $reservacion['hora_llegada_estimada']
                );
            } else {
                throw new Exception('Método actualizarHabitaciones no implementado');
            }
            
            if ($resultado) {
                set_mensaje('Habitaciones actualizadas exitosamente', 'success');
                
                // Si el precio cambió, registrar nota
                if (isset($resultado['precio_diferencia']) && $resultado['precio_diferencia'] != 0) {
                    require_once __DIR__ . '/../models/ReservacionNota.php';
                    $notaModel = new ReservacionNota();
                    
                    $diff = $resultado['precio_diferencia'];
                    $nota = sprintf(
                        "Habitaciones modificadas. Precio %s en $%s (de $%s a $%s)",
                        $diff > 0 ? 'aumentó' : 'disminuyó',
                        number_format(abs($diff), 0),
                        number_format($resultado['precio_anterior'], 0),
                        number_format($resultado['precio_nuevo'], 0)
                    );
                    
                    $usuario_id = user_id();
                    $notaModel->agregarNota($reservacion_id, $usuario_id, $nota);
                }
            } else {
                throw new Exception('Error al actualizar las habitaciones');
            }
            
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            
        } catch (Exception $e) {
            error_log('ERROR en actualizarHabitacionesAction: ' . $e->getMessage());
            set_mensaje('Error: ' . $e->getMessage(), 'error');
            $this->redirect('reservaciones/editar-habitaciones/' . $reservacion_id);
        }
    }
    /**
     * Ver detalle de reservación
     */
    public function verAction() {
        $id = $this->route_params['id'] ?? 0;
        
        $reservacion = $this->reservacionModel->obtenerPorId($id);
        
        if (!$reservacion) {
            set_mensaje('Reservación no encontrada', 'error');
            $this->redirect('reservaciones');
            return;
        }
        
        if (!empty($reservacion['usuario_registro_id'])) {
        $sql = "SELECT nombre_completo FROM usuarios WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reservacion['usuario_registro_id']]);
        $usuario_registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario_registro) {
            $reservacion['usuario_registro'] = $usuario_registro['nombre_completo'];
        } else {
            $reservacion['usuario_registro'] = 'Usuario ID: ' . $reservacion['usuario_registro_id'];
        }
    } else {
        $reservacion['usuario_registro'] = 'Sistema';
    }
        
        // Obtener habitaciones
        $habitaciones = $this->reservacionModel->getHabitaciones($id);
        // DEBUG: Verificar que se están obteniendo los precios
error_log("=== DEBUG HABITACIONES ===");
foreach ($habitaciones as $hab) {
    error_log("Habitación {$hab['numero']}: Precio = {$hab['precio']}, Es cortesía = {$hab['es_cortesia']}");
}
error_log("=== FIN DEBUG ===");
        
        // Obtener información del huésped
        $huesped = $this->huespedModel->find($reservacion['huesped_id']);
        
        // Obtener vehículos
        $vehiculos = [];
        if (method_exists($this->huespedModel, 'getVehiculos')) {
            $vehiculos = $this->huespedModel->getVehiculos($reservacion['huesped_id']);
        }
        
        // Obtener pagos si está en check-in
        $pagos = [];
        if ($reservacion['estado'] == 'checked_in') {
            try {
                // Verificar si la tabla existe
                $sql_check = "SHOW TABLES LIKE 'reservacion_pagos'";
                $result = $this->db->query($sql_check);
                
                if ($result && $result->fetch()) {
                    // La tabla existe, obtener pagos
                    $sql = "SELECT * FROM reservacion_pagos 
                            WHERE reservacion_id = ?
                            AND hotel_id = ?
                            ORDER BY created_at DESC";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$id, $this->hotelIdActual()]);
                    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                error_log("Error al obtener pagos: " . $e->getMessage());
                $pagos = [];
            }
        }
        
        // Obtener notas
require_once __DIR__ . '/../models/ReservacionNota.php';
$notaModel = new ReservacionNota();
$notas = $notaModel->obtenerPorReservacion($id);
$total_notas = count($notas);
        
        View::renderTemplate('reservaciones/ver', [
    'title' => 'Reservación #' . $id . ' - Los Cedros',
    'reservacion' => $reservacion,
    'huesped' => $huesped,
    'vehiculos' => $vehiculos,
    'habitaciones' => $habitaciones,
    'estados' => Reservacion::getEstados(),
    'pagos' => $pagos,
    'notas' => $notas,
    'total_notas' => $total_notas
]);
    }
    
    
    /**
 * Entregar llave al huésped
 */
public function entregarLlaveAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitacion_id = intval($this->getPost('habitacion_id'));
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_entrega = $this->getPost('tipo_entrega');
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlLlave.php';
    $controlLlave = new ControlLlave();
    
    // Determinar quién entrega
    $entregada_por_id = $_SESSION['user_id'] ?? null;
    $entregada_por_manual = null;
    
    if ($tipo_entrega === 'manual') {
        $entregada_por_manual = trim($this->getPost('entregada_por_manual'));
        if (empty($entregada_por_manual)) {
            set_mensaje('Debe especificar quién entrega la llave', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar entrega
    if ($controlLlave->entregarLlave($habitacion_id, $reservacion_id, $entregada_por_id, $entregada_por_manual)) {
        set_mensaje('Llave entregada al huésped exitosamente', 'success');
    } else {
        set_mensaje('Error al registrar la entrega de la llave', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}

/**
 * Recibir llave del huésped
 */
public function recibirLlaveAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitacion_id = intval($this->getPost('habitacion_id'));
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_recepcion = $this->getPost('tipo_recepcion');
    $notas = trim($this->getPost('notas'));
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlLlave.php';
    $controlLlave = new ControlLlave();
    
    // Determinar quién recibe
    $recibida_por_id = $_SESSION['user_id'] ?? null;
    $recibida_por_manual = null;
    
    if ($tipo_recepcion === 'manual') {
        $recibida_por_manual = trim($this->getPost('recibida_por_manual'));
        if (empty($recibida_por_manual)) {
            set_mensaje('Debe especificar quién recibe la llave', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar recepción
    if ($controlLlave->recibirLlave($habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual, $notas)) {
        set_mensaje('Llave recibida del huésped exitosamente', 'success');
    } else {
        set_mensaje('Error al registrar la recepción de la llave', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}

/**
 * Entregar control remoto al huésped
 */
public function entregarRemotoAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitacion_id = intval($this->getPost('habitacion_id'));
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_entrega = $this->getPost('tipo_entrega');
    $tipo_identificacion = $this->getPost('tipo_identificacion');
    $nombre_propietario_ine = trim($this->getPost('nombre_propietario_ine')); // NUEVO
    
    // Validar tipo de identificación
    if (empty($tipo_identificacion) || !in_array($tipo_identificacion, ['ine', 'licencia', 'otro'])) {
        set_mensaje('Debe seleccionar el tipo de identificación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Validar nombre del propietario - NUEVO
    if (empty($nombre_propietario_ine)) {
        set_mensaje('Debe ingresar el nombre del propietario de la identificación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlRemoto.php';
    $controlRemoto = new ControlRemoto();
    
    // Determinar quién entrega
    $entregada_por_id = $_SESSION['user_id'] ?? null;
    $entregada_por_manual = null;
    
    if ($tipo_entrega === 'manual') {
        $entregada_por_manual = trim($this->getPost('entregada_por_manual'));
        if (empty($entregada_por_manual)) {
            set_mensaje('Debe especificar quién entrega el control remoto', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar entrega con nombre del propietario - MODIFICADO
    if ($controlRemoto->entregarRemoto($habitacion_id, $reservacion_id, $entregada_por_id, $entregada_por_manual, $tipo_identificacion, $nombre_propietario_ine)) {
        set_mensaje('Control remoto entregado exitosamente a ' . htmlspecialchars($nombre_propietario_ine) . ' con ' . get_tipo_identificacion_label($tipo_identificacion), 'success');
    } else {
        set_mensaje('Error al registrar la entrega del control remoto', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}

/**
 * Entregar controles remotos de MÚLTIPLES habitaciones
 * NUEVA FUNCIÓN
 */
public function entregarRemotosMultiplesAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitaciones_ids = $this->getPost('habitaciones_ids');
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_entrega = $this->getPost('tipo_entrega');
    $tipo_identificacion = $this->getPost('tipo_identificacion');
    $nombre_propietario_ine = trim($this->getPost('nombre_propietario_ine'));
    
    // Validar que se hayan seleccionado habitaciones
    if (empty($habitaciones_ids) || !is_array($habitaciones_ids)) {
        set_mensaje('Debe seleccionar al menos una habitación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Convertir IDs a enteros
    $habitaciones_ids = array_map('intval', $habitaciones_ids);
    
    // Validar tipo de identificación
    if (empty($tipo_identificacion) || !in_array($tipo_identificacion, ['ine', 'licencia', 'otro'])) {
        set_mensaje('Debe seleccionar el tipo de identificación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Validar nombre del propietario
    if (empty($nombre_propietario_ine)) {
        set_mensaje('Debe ingresar el nombre del propietario de la identificación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlRemoto.php';
    $controlRemoto = new ControlRemoto();
    
    // Determinar quién entrega
    $entregada_por_id = $_SESSION['user_id'] ?? null;
    $entregada_por_manual = null;
    
    if ($tipo_entrega === 'manual') {
        $entregada_por_manual = trim($this->getPost('entregada_por_manual'));
        if (empty($entregada_por_manual)) {
            set_mensaje('Debe especificar quién entrega los controles remotos', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar entrega múltiple
    $resultado = $controlRemoto->entregarRemotosMultiples(
        $habitaciones_ids, 
        $reservacion_id, 
        $entregada_por_id, 
        $entregada_por_manual, 
        $tipo_identificacion,
        $nombre_propietario_ine
    );
    
    if ($resultado['success']) {
        $mensaje = "Controles remotos entregados exitosamente: {$resultado['exitosas']} de {$resultado['total']} habitaciones";
        if ($resultado['fallidas'] > 0) {
            $mensaje .= " ({$resultado['fallidas']} fallidas)";
        }
        set_mensaje($mensaje, 'success');
    } else {
        set_mensaje('Error al entregar los controles remotos', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}

/**
 * Recibir controles remotos de MÚLTIPLES habitaciones
 * NUEVA FUNCIÓN
 */
public function recibirRemotosMultiplesAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitaciones_ids = $this->getPost('habitaciones_ids');
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_recepcion = $this->getPost('tipo_recepcion');
    $notas = trim($this->getPost('notas'));
    
    // Validar que se hayan seleccionado habitaciones
    if (empty($habitaciones_ids) || !is_array($habitaciones_ids)) {
        set_mensaje('Debe seleccionar al menos una habitación', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
    }
    
    // Convertir IDs a enteros
    $habitaciones_ids = array_map('intval', $habitaciones_ids);
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlRemoto.php';
    $controlRemoto = new ControlRemoto();
    
    // Determinar quién recibe
    $recibida_por_id = $_SESSION['user_id'] ?? null;
    $recibida_por_manual = null;
    
    if ($tipo_recepcion === 'manual') {
        $recibida_por_manual = trim($this->getPost('recibida_por_manual'));
        if (empty($recibida_por_manual)) {
            set_mensaje('Debe especificar quién recibe los controles remotos', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar recepción múltiple
    $resultado = $controlRemoto->recibirRemotosMultiples(
        $habitaciones_ids, 
        $reservacion_id, 
        $recibida_por_id, 
        $recibida_por_manual,
        $notas
    );
    
    if ($resultado['success']) {
        $mensaje = "Controles remotos recibidos exitosamente: {$resultado['exitosas']} de {$resultado['total']} habitaciones";
        if ($resultado['fallidas'] > 0) {
            $mensaje .= " ({$resultado['fallidas']} fallidas)";
        }
        set_mensaje($mensaje, 'success');
    } else {
        set_mensaje('Error al recibir los controles remotos', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}

/**
 * Recibir control remoto del huésped
 */
public function recibirRemotoAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    $habitacion_id = intval($this->getPost('habitacion_id'));
    $reservacion_id = intval($this->getPost('reservacion_id'));
    $tipo_recepcion = $this->getPost('tipo_recepcion');
    $notas = trim($this->getPost('notas'));
    
    // Cargar modelo
    require_once __DIR__ . '/../models/ControlRemoto.php';
    $controlRemoto = new ControlRemoto();
    
    // Determinar quién recibe
    $recibida_por_id = $_SESSION['user_id'] ?? null;
    $recibida_por_manual = null;
    
    if ($tipo_recepcion === 'manual') {
        $recibida_por_manual = trim($this->getPost('recibida_por_manual'));
        if (empty($recibida_por_manual)) {
            set_mensaje('Debe especificar quién recibe el control remoto', 'error');
            $this->redirect('reservaciones/ver/' . $reservacion_id);
            return;
        }
    }
    
    // Procesar recepción
    if ($controlRemoto->recibirRemoto($habitacion_id, $reservacion_id, $recibida_por_id, $recibida_por_manual, $notas)) {
        set_mensaje('Control remoto recibido del huésped exitosamente', 'success');
    } else {
        set_mensaje('Error al registrar la recepción del control remoto', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $reservacion_id);
}
    /**
     * Proceso de check-in usando el método del modelo
     */
    public function checkInAction() {
        $id = $this->route_params['id'] ?? 0;
        
        if (!$this->isPost()) {
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }
        
        $this->validateCSRF();
        
        // Verificar caja abierta
        $corteActual = $this->cajaModel->obtenerCorteActual();
        if (!$corteActual) {
            set_mensaje('No se puede realizar el check-in. Debe abrir la caja primero.', 'error');
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }
        
        try {
            // Obtener datos del formulario
            $hora_entrada = $this->getPost('hora_entrada', date('H:i:s'));
            
            // Procesar pagos
            $pagos = [];
            $monto_recibido_total = 0;
            $cambio_total = 0;
            
            // Obtener la reservación para saber el precio
            $reservacion = $this->reservacionModel->find($id);
            // En ReservacionController.php, método verAction()
// Después de obtener la reservación
$reservacion = $this->reservacionModel->find($id);

// Obtener el nombre del usuario que registró
if ($reservacion && $reservacion['usuario_registro_id']) {
    $sql = "SELECT nombre_completo FROM usuarios WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([$reservacion['usuario_registro_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario) {
        $reservacion['usuario_registro'] = $usuario['nombre_completo'];
    }
}
            // Verificar cada método de pago
            $metodos = ['efectivo', 'tarjeta', 'transferencia'];
            
            foreach ($metodos as $metodo) {
                $monto = floatval($this->getPost('monto_' . $metodo, 0));
                
                if ($monto > 0) {
                    $pago = [
                        'metodo' => $metodo,
                        'monto' => $monto,
                        'referencia' => null
                    ];
                    
                    if ($metodo === 'tarjeta' || $metodo === 'transferencia') {
                        $referencia = trim($this->getPost('referencia_' . $metodo, ''));
                        $pago['referencia'] = $referencia ?: null;
                    }
                    
                    if ($metodo === 'efectivo') {
                        $recibido = floatval($this->getPost('recibido_efectivo', $monto));
                        $monto_recibido_total += $recibido;
                        $cambio = $recibido - $monto;
                        $cambio_total += $cambio;
                    } else {
                        $monto_recibido_total += $monto;
                    }
                    
                    $pagos[] = $pago;
                }
            }
            
            // Si no hay pagos específicos, usar efectivo por defecto
            if (empty($pagos)) {
                $pagos[] = [
                    'metodo' => 'efectivo',
                    'monto' => $reservacion['precio_total'],
                    'referencia' => null
                ];
                $monto_recibido_total = $reservacion['precio_total'];
            }
            
            // Usar el método del modelo para check-in con pagos mixtos
            if (method_exists($this->reservacionModel, 'checkInConPagosMixtos')) {
                $resultado = $this->reservacionModel->checkInConPagosMixtos(
                    $id, 
                    $hora_entrada, 
                    $pagos, 
                    $monto_recibido_total,
                    $cambio_total
                );
            } else {
                // Fallback al método simple si no existe checkInConPagosMixtos
                $resultado = $this->reservacionModel->checkIn($id, $hora_entrada);
            }
            
            if ($resultado) {
                
                // Justo después de: if ($resultado) {
error_log("=== DEBUG CHECK-IN ===");
error_log("Check-in exitoso, iniciando descuento de inventario...");

// Llamar al procesamiento de inventario
$this->procesarDescuentoInventario($id);

// Verificar si se guardó algún mensaje
error_log("Mensaje inventario: " . ($_SESSION['mensaje_inventario'] ?? 'No hay mensaje'));
error_log("=== FIN DEBUG CHECK-IN ===");
                // Procesar descuento de inventario
                // Procesar entrega automática de llaves
$this->procesarEntregaLlavesCheckIn($id);
                
                // ========== PROCESAR SOLICITUD DE FACTURA ==========
                $this->procesarSolicitudFactura($id, $pagos, $reservacion['precio_total']);
                // ========== FIN FACTURA ==========
                $mensaje = 'Check-in realizado exitosamente';
                if ($cambio_total > 0) {
                    $mensaje .= sprintf('. Cambio a devolver: $%s', number_format($cambio_total, 2));
                }
                set_mensaje($mensaje, 'success');
            } else {
                throw new Exception('Error al procesar el check-in');
            }
            
        } catch (Exception $e) {
            error_log("Error en checkInAction: " . $e->getMessage());
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('reservaciones/ver/' . $id);
    }
    /**
 * Procesar entrega automática de llaves al hacer check-in
 */
private function procesarEntregaLlavesCheckIn($reservacion_id) {
    try {
        // Cargar modelo de control de llaves
        require_once __DIR__ . '/../models/ControlLlave.php';
        $controlLlave = new ControlLlave();
        
        // Obtener habitaciones de la reservación
        $habitaciones = $this->reservacionModel->getHabitaciones($reservacion_id);
        
        if (empty($habitaciones)) {
            error_log("No se encontraron habitaciones para la reservación: " . $reservacion_id);
            return;
        }
        
        $usuario_id = user_id();
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
        
        // Contador de llaves entregadas
        $llaves_entregadas = 0;
        
        foreach ($habitaciones as $hab) {
            // Verificar si el hotel tiene la llave
            if ($controlLlave->hotelTieneLlave($hab['habitacion_id'])) {
                // Entregar la llave automáticamente
                $entregada = $controlLlave->entregarLlave(
                    $hab['habitacion_id'],
                    $reservacion_id,
                    $usuario_id,
                    'Check-in automático - ' . $usuario_nombre
                );
                
                if ($entregada) {
                    $llaves_entregadas++;
                    error_log("Llave entregada automáticamente - Habitación: " . $hab['numero']);
                } else {
                    error_log("Error al entregar llave - Habitación: " . $hab['numero']);
                }
            }
        }
        
        if ($llaves_entregadas > 0) {
            // Agregar mensaje informativo
            if (isset($_SESSION['mensaje_inventario'])) {
                $_SESSION['mensaje_inventario'] .= " | ";
            } else {
                $_SESSION['mensaje_inventario'] = "";
            }
            
            $_SESSION['mensaje_inventario'] .= "✓ Se entregaron " . $llaves_entregadas . 
                                               " llave(s) al huésped automáticamente";
        }
        
    } catch (Exception $e) {
        error_log("Error en procesarEntregaLlavesCheckIn: " . $e->getMessage());
        // No detener el check-in por error en llaves
    }
}
    /**
     * Proceso de check-out usando el método del modelo
     */
    public function checkOutAction() {
    $id = $this->route_params['id'] ?? 0;
    
    if (!$this->isPost()) {
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    $this->validateCSRF();
    
    // Obtener hora de salida
    $hora_salida = $this->getPost('hora_salida', date('H:i:s'));
    
    // Usar el método checkOut del modelo
    $resultado = $this->reservacionModel->checkOut($id, $hora_salida);
    
    // Procesar recogida automática de llaves
    $this->procesarRecogidaLlavesCheckOut($id);
    
    // Procesar recogida automática de controles remotos
    $this->procesarRecogidaRemotosCheckOut($id);
    
    if ($resultado) {
        set_mensaje('Check-out realizado exitosamente. Las habitaciones pasaron a limpieza.', 'success');
    } else {
        set_mensaje('Error al realizar check-out', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $id);
}
    /**
 * Procesar recogida automática de llaves al hacer check-out
 */
private function procesarRecogidaLlavesCheckOut($reservacion_id) {
    try {
        // Cargar modelo de control de llaves
        require_once __DIR__ . '/../models/ControlLlave.php';
        $controlLlave = new ControlLlave();
        
        // Obtener habitaciones de la reservación
        $habitaciones = $this->reservacionModel->getHabitaciones($reservacion_id);
        
        if (empty($habitaciones)) {
            error_log("No se encontraron habitaciones para la reservación: " . $reservacion_id);
            return;
        }
        
        $usuario_id = user_id();
        $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
        
        // Contador de llaves recibidas
        $llaves_recibidas = 0;
        
        foreach ($habitaciones as $hab) {
            // Verificar si el huésped tiene la llave
            if (!$controlLlave->hotelTieneLlave($hab['habitacion_id'])) {
                // Recibir la llave automáticamente
                $recibida = $controlLlave->recibirLlave(
                    $hab['habitacion_id'],
                    $reservacion_id,
                    $usuario_id,
                    'Check-out automático - ' . $usuario_nombre,
                    'Llave recibida durante check-out'
                );
                
                if ($recibida) {
                    $llaves_recibidas++;
                    error_log("Llave recibida automáticamente - Habitación: " . $hab['numero']);
                } else {
                    error_log("Error al recibir llave - Habitación: " . $hab['numero']);
                }
            }
        }
        
        if ($llaves_recibidas > 0) {
            // Modificar el mensaje de éxito del check-out
            $mensaje_actual = $_SESSION['flash_message']['texto'] ?? '';
            $_SESSION['flash_message']['texto'] = $mensaje_actual . 
                                                 ' Se recibieron ' . $llaves_recibidas . ' llave(s).';
        }
        
    } catch (Exception $e) {
        error_log("Error en procesarRecogidaLlavesCheckOut: " . $e->getMessage());
        // No detener el check-out por error en llaves
    }
}
    /**
     * Cancelar reservación
     */
    public function cancelarAction() {
    $id = $this->route_params['id'] ?? 0;
    
    if (!$this->isPost()) {
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    $this->validateCSRF();
    
    try {
        // Obtener la razón de cancelación
        $razon = trim($this->getPost('razon_cancelacion', ''));
        
        if (empty($razon)) {
            throw new Exception("Debe especificar una razón para la cancelación");
        }
        
        // Obtener información de la reservación antes de cancelar
        $reservacion = $this->reservacionModel->find($id);
        
        if (!$reservacion) {
            throw new Exception("Reservación no encontrada");
        }
        
        // Verificar que la reservación pueda ser cancelada
        if ($reservacion['estado'] == 'checked_out') {
            throw new Exception("No se puede cancelar una reservación con check-out realizado");
        }
        
        if ($reservacion['estado'] == 'cancelada') {
            throw new Exception("Esta reservación ya está cancelada");
        }
        
        // Si hay un pago registrado, verificar que haya caja abierta
        if ($reservacion['estado'] == 'checked_in' && !empty($reservacion['metodo_pago'])) {
            $corteActual = $this->cajaModel->obtenerCorteActual();
            if (!$corteActual) {
                set_mensaje('No se puede cancelar la reservación. Debe abrir la caja primero para procesar la devolución.', 'error');
                $this->redirect('reservaciones/ver/' . $id);
                return;
            }

            // ⚠️ Advertir si el pago original está en un corte cerrado (de otro día).
            // En ese caso se recomienda usar "Modificar días" en lugar de cancelar+recrear,
            // para que la contabilidad quede correcta por fecha.
            $stmt_check = $this->db->prepare(
                "SELECT COUNT(*) FROM movimientos_caja
                 WHERE reservacion_id = ? AND tipo = 'ingreso' AND corte_id != ?"
            );
            $stmt_check->execute([$id, $corteActual['id']]);
            $pagos_en_corte_cerrado = (int) $stmt_check->fetchColumn();

            if ($pagos_en_corte_cerrado > 0) {
                // Guardar alerta en sesión para mostrarla junto al mensaje de éxito
                $_SESSION['alerta_corte_cerrado'] = true;
            }
        }

        // Usar el método cancelar mejorado del modelo
        $resultado = $this->reservacionModel->cancelar($id, $razon);
        
        if ($resultado) {
    // Construir mensaje de éxito
    $mensaje = 'Reservación cancelada exitosamente.';

    // Si había check-in, informar sobre la liberación de habitaciones
    if ($reservacion['estado'] == 'checked_in') {
        $mensaje .= ' Las habitaciones han sido liberadas.';

        // Información sobre devolución de dinero
        if (!empty($reservacion['metodo_pago'])) {
            $mensaje .= sprintf(' Se registró la devolución de %s en caja.',
                              format_money($reservacion['precio_total']));
        }

        // ⚠️ Advertencia si el pago era de un corte cerrado
        if (!empty($_SESSION['alerta_corte_cerrado'])) {
            unset($_SESSION['alerta_corte_cerrado']);
            set_mensaje(
                '⚠️ Aviso contable: el pago original de esta reservación pertenecía a un corte de caja ya cerrado (de un día anterior). ' .
                'Se registró la devolución en el corte actual. ' .
                'Para evitar esto en el futuro, usa "Modificar días" en vez de cancelar y volver a crear la reservación.',
                'warning'
            );
        }
        
        // NUEVO: Información sobre productos devueltos
        if (is_array($resultado) && !empty($resultado['productos_devueltos'])) {
            $mensaje .= sprintf(' Se devolvieron %d productos al inventario.', 
                              count($resultado['productos_devueltos']));
        }
    }
    
    set_mensaje($mensaje, 'success');
    
    // Log adicional para auditoría (actualizado)
    error_log(sprintf(
        "Reservación #%d cancelada por usuario %s. Estado anterior: %s, Monto: $%s, Productos devueltos: %d",
        $id,
        $_SESSION['user_name'] ?? 'Desconocido',
        $reservacion['estado'],
        $reservacion['precio_total'] ?? 0,
        is_array($resultado) ? count($resultado['productos_devueltos'] ?? []) : 0
    ));
} else {
            throw new Exception('Error al procesar la cancelación');
        }
        
    } catch (Exception $e) {
        error_log("Error en cancelarAction: " . $e->getMessage());
        set_mensaje('Error: ' . $e->getMessage(), 'error');
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    // Redireccionar al listado de reservaciones
    $this->redirect('reservaciones');
}

/**
 * Método adicional para verificar si se puede cancelar una reservación
 * Agregar este método al controlador para validaciones adicionales
 */
private function validarCancelacion($reservacion) {
    // Verificar estado
    if ($reservacion['estado'] == 'cancelada') {
        return ['valido' => false, 'mensaje' => 'La reservación ya está cancelada'];
    }
    
    if ($reservacion['estado'] == 'checked_out') {
        return ['valido' => false, 'mensaje' => 'No se puede cancelar después del check-out'];
    }
    
    // Verificar permisos del usuario (opcional)
    $usuario_rol = $_SESSION['user_role'] ?? '';
    if ($usuario_rol == 'recepcionista' && $reservacion['estado'] == 'checked_in') {
        // Los recepcionistas no pueden cancelar reservaciones con check-in
        return ['valido' => false, 'mensaje' => 'No tiene permisos para cancelar reservaciones con check-in'];
    }
    
    // Verificar antigüedad (opcional - por ejemplo, no cancelar reservaciones muy antiguas)
    $fecha_creacion = new DateTime($reservacion['created_at']);
    $ahora = new DateTime();
    $diferencia = $fecha_creacion->diff($ahora);
    
    if ($diferencia->days > 30) {
        return ['valido' => false, 'mensaje' => 'No se pueden cancelar reservaciones con más de 30 días'];
    }
    
    return ['valido' => true];
}
    
    /**
     * Crear nueva reservación
     */
    public function crearAction() {
    // Obtener parámetros de preselección
    $habitacion_id = $this->getQuery('habitacion_id');
    $fecha_entrada = $this->getQuery('fecha_entrada');
    $fecha_salida = $this->getQuery('fecha_salida');
    $hora_llegada = $this->getQuery('hora_llegada');
    $es_preseleccion = $this->getQuery('preseleccion');
    
    $huesped_id = $this->getQuery('huesped_id');
    $huesped_preseleccionado = null;
    
    if ($huesped_id) {
        $huesped_preseleccionado = $this->huespedModel->find($huesped_id);
    }
    
    // Obtener habitaciones activas
    $habitaciones = $this->habitacionModel->where(['activa' => 1]);
    
    View::renderTemplate('reservaciones/crear', [
        'title' => 'Nueva Reservación - Los Cedros',
        'habitaciones' => $habitaciones,
        'huesped_preseleccionado' => $huesped_preseleccionado,
        'metodos_pago' => Reservacion::getMetodosPago(),
        // Pasar los parámetros de preselección a la vista
        'habitacion_preseleccionada' => $habitacion_id,
        'fecha_entrada_pre' => $fecha_entrada,
        'fecha_salida_pre' => $fecha_salida,
        'hora_llegada_pre' => $hora_llegada,
        'es_preseleccion' => $es_preseleccion
    ]);
}
    
    /**
     * Guardar nueva reservación
     */
    public function guardarAction() {
    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    // DEBUG: Log todos los datos POST
    error_log("=== DEBUG GUARDAR RESERVACIÓN ===");
    error_log("POST completo: " . json_encode($_POST));
    
    try {
        // Recopilar datos
        $data = [
            'huesped_id' => intval($this->getPost('huesped_id')),
            'fecha_entrada' => $this->getPost('fecha_entrada'),
            'fecha_salida' => $this->getPost('fecha_salida'),
            'hora_llegada_estimada' => $this->getPost('hora_llegada', '14:00'),
            'hora_entrada' => null,
            'metodo_pago' => null,
            'notas' => trim($this->getPost('notas', '')),
            'estado' => 'confirmada',
            'usuario_registro_id' => user_id(),
            'hotel_id' => $this->hotelIdActual()
        ];
        
        // DEBUG: Log datos procesados
        error_log("Datos procesados: " . json_encode($data));
        
        // Obtener habitaciones seleccionadas
        $habitaciones_ids = $this->getPost('habitaciones', []);
        $habitaciones_ids = array_values(array_unique(array_map('intval', (array) $habitaciones_ids)));
        // Obtener habitaciones marcadas como cortesía (NUEVO)
$cortesias_ids = $this->getPost('cortesias', []);
error_log("Cortesías seleccionadas por el usuario: " . json_encode($cortesias_ids));
        
        // DEBUG: Log habitaciones
        error_log("Habitaciones seleccionadas: " . json_encode($habitaciones_ids));
        
        // Validaciones
        if (empty($data['huesped_id'])) {
            error_log("ERROR: No se seleccionó huésped");
            throw new Exception('Debe seleccionar un huésped');
        }
        
        if (empty($habitaciones_ids)) {
            error_log("ERROR: No se seleccionaron habitaciones");
            throw new Exception('Debe seleccionar al menos una habitación');
        }
        
        // Obtener información de las habitaciones
        $habitaciones = [];
        foreach ($habitaciones_ids as $hab_id) {
            $hab = $this->habitacionModel->find($hab_id);
            if ($hab) {
                $habitaciones[] = $hab;
                error_log("Habitación encontrada: " . json_encode($hab));
            } else {
                error_log("ERROR: Habitación no encontrada con ID: " . $hab_id);
            }
        }
        
        if (count($habitaciones) !== count($habitaciones_ids)) {
            throw new Exception('Una o mas habitaciones no pertenecen al hotel actual');
        }

        // Verificar disponibilidad
        error_log("Verificando disponibilidad...");
        $disponible = $this->reservacionModel->verificarDisponibilidadMultiple(
            $habitaciones_ids, 
            $data['fecha_entrada'], 
            $data['fecha_salida']
        );
        
        error_log("Disponibilidad: " . ($disponible ? "SÍ" : "NO"));
        
        if (!$disponible) {
            throw new Exception('Una o más habitaciones no están disponibles');
        }
        
        // Calcular precio
        error_log("Calculando precio...");
        $calculo = $this->reservacionModel->calcularPrecioMultiple(
            $habitaciones,
            $data['fecha_entrada'],
            $data['fecha_salida'],
            $data['hora_llegada_estimada']
        );
        
        error_log("Cálculo de precio: " . json_encode($calculo));
        
        $data['precio_total'] = $calculo['precio_total'];
        
        // Ajustar notas para estadías de madrugada
        if ($calculo['es_madrugada'] && $data['fecha_entrada'] == $data['fecha_salida']) {
            $nota_horario = "[LLEGADA EN MADRUGADA] Check-in estimado a las " . $data['hora_llegada_estimada'] . 
                           ". Check-out el mismo día a las 12:00 PM.";
            $data['notas'] = empty($data['notas']) ? $nota_horario : $data['notas'] . "\n\n" . $nota_horario;
        }
        
        // Usar el método crearConHabitaciones del modelo
        error_log("Creando reservación...");
        if (method_exists($this->reservacionModel, 'crearConHabitaciones')) {
            // CORRECCIÓN: Usar habitaciones_detalle que contiene precio_calculado con incrementos
            $habitaciones_con_precio = $calculo['habitaciones_detalle'] ?? $habitaciones;
            $reservacion = $this->reservacionModel->crearConHabitaciones($data, $habitaciones_con_precio, $cortesias_ids);
        } else {
            error_log("ADVERTENCIA: método crearConHabitaciones no existe, usando create");
            $reservacion = $this->reservacionModel->create($data);
        }
        
        error_log("Resultado de creación: " . json_encode($reservacion));
        
        if ($reservacion && isset($reservacion['id'])) {
            error_log("ÉXITO: Reservación creada con ID: " . $reservacion['id']);
            set_mensaje('Reservación creada exitosamente', 'success');
            $this->redirect('reservaciones/ver/' . $reservacion['id']);
        } else {
            error_log("ERROR: No se pudo crear la reservación");
            throw new Exception('Error al crear la reservación');
        }
        
    } catch (Exception $e) {
        error_log('ERROR en guardarAction: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        error_log("=== FIN DEBUG ===");
        set_mensaje('Error: ' . $e->getMessage(), 'error');
        $this->redirect('reservaciones/crear');
    }
}
    /**
     * Generar PDF de cotización SIN guardar reservación
     * Se llama via POST desde el formulario de crear reservación
     */
    public function cotizacionPdfAction() {
        if (!$this->isPost()) {
            $this->redirect('reservaciones/crear');
            return;
        }

        // No validamos CSRF aquí porque abrimos en nueva pestaña con form target _blank
        // Si quieres validarlo, necesitarás un approach diferente

        try {
            // Recopilar datos del POST
            $huesped_id     = intval($this->getPost('huesped_id'));
            $fecha_entrada  = $this->getPost('fecha_entrada');
            $fecha_salida   = $this->getPost('fecha_salida');
            $hora_llegada   = $this->getPost('hora_llegada', '15:00');
            $habitaciones_ids = $this->getPost('habitaciones', []);
            $cortesias_ids  = $this->getPost('cortesias', []);
            $notas          = trim($this->getPost('notas', ''));

            // Validaciones básicas
            if (empty($huesped_id) || empty($fecha_entrada) || empty($fecha_salida) || empty($habitaciones_ids)) {
                die('Datos incompletos para generar la cotización.');
            }

            // Obtener datos del huésped
            $huesped = $this->huespedModel->find($huesped_id);
            if (!$huesped) {
                die('Huésped no encontrado.');
            }

            // Obtener habitaciones
            $habitaciones = [];
            foreach ($habitaciones_ids as $hab_id) {
                $hab = $this->habitacionModel->find($hab_id);
                if ($hab) {
                    $habitaciones[] = $hab;
                }
            }

            if (empty($habitaciones)) {
                die('No se encontraron las habitaciones seleccionadas.');
            }

            // Calcular precios usando el mismo método que guardar
            $calculo = $this->reservacionModel->calcularPrecioMultiple(
                $habitaciones,
                $fecha_entrada,
                $fecha_salida,
                $hora_llegada
            );

            // ─── Generar PDF ─────────────────────────────────────
            require_once __DIR__ . '/../../public_html/fdpdf/fpdf.php';

            $pdf = new FPDF('P', 'mm', 'Letter'); // 216 x 279 mm
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->AddPage();

            // ─── Colores del hotel ───────────────────────────────
            $olivo     = [92, 122, 78];    // #5C7A4E
            $olivoOsc  = [61, 82, 52];     // #3D5234
            $gold      = [200, 169, 106];  // #C8A96A
            $cream     = [247, 244, 238];  // #F7F4EE
            $creamMid  = [238, 233, 222];  // #EEE9DE
            $gris      = [107, 114, 128];  // #6B7280
            $grisCla   = [156, 163, 175];  // #9CA3AF
            $blanco    = [255, 255, 255];
            $negro     = [55, 65, 81];     // #374151
            $ambar     = [217, 119, 6];    // #D97706

            // Helper para texto con caracteres especiales
            // FPDF usa ISO-8859-1, hay que convertir desde UTF-8
            $u = function($text) {
                return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
            };

            $pageW = 216;
            $margin = 15;
            $contentW = $pageW - ($margin * 2);

            // ═══════════════════════════════════════════════════════
            // HEADER - Barra olivo con logo
            // ═══════════════════════════════════════════════════════
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->Rect(0, 0, $pageW, 42, 'F');

            // Degradado decorativo
            $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Rect(0, 38, $pageW, 4, 'F');

            // Logo
            $logoPath = __DIR__ . '/../../public_html/img/logo-hotel-san-nicolas2.png';
            if (file_exists($logoPath)) {
                $pdf->Image($logoPath, $margin, 5, 32, 32);
            }

            // Nombre del hotel
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
            $pdf->SetXY($margin + 36, 8);
            $pdf->Cell(100, 8, $u('Hotel Los Cedros'), 0, 2, 'L');

            // Subtítulo
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetX($margin + 36);
            $pdf->Cell(100, 5, $u('Santa Catarina Juquila, Oaxaca'), 0, 2, 'L');

            // Título COTIZACIÓN a la derecha
            $pdf->SetFont('Helvetica', 'B', 22);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetXY($pageW - $margin - 70, 9);
            $pdf->Cell(70, 10, $u('COTIZACIÓN'), 0, 0, 'R');

            // Fecha de elaboración
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(200, 210, 195);
            $pdf->SetXY($pageW - $margin - 70, 20);
            setlocale(LC_TIME, 'es_MX.UTF-8', 'es_ES.UTF-8', 'es_MX', 'es');
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $hoy = date('j') . ' de ' . $meses[date('n')-1] . ' de ' . date('Y');
            $pdf->Cell(70, 5, $u('Fecha: ' . $hoy), 0, 0, 'R');

            // Línea gold debajo del header
            $pdf->SetFillColor($gold[0], $gold[1], $gold[2]);
            $pdf->Rect(0, 42, $pageW, 1.5, 'F');

            // ═══════════════════════════════════════════════════════
            // DATOS DEL HUÉSPED
            // ═══════════════════════════════════════════════════════
            $y = 50;
            $pdf->SetY($y);

            // Título sección
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DATOS DEL HUÉSPED'), 0, 1, 'L');

            // Línea decorativa
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.5);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);

            // Caja de datos del huésped
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY = $pdf->GetY();
            $pdf->Rect($margin, $boxY, $contentW, 18, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY, $contentW, 18, 'D');

            $pdf->SetXY($margin + 4, $boxY + 3);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell(18, 5, $u('Nombre:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(70, 5, $u($huesped['nombre_completo'] ?? 'N/A'), 0, 0, 'L');

            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(22, 5, $u('Teléfono:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(50, 5, $u($huesped['telefono'] ?? 'No registrado'), 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(26, 5, $u('Procedencia:'), 0, 0, 'L');
            $pdf->SetFont('Helvetica', '', 9);
            $procedencia = $huesped['procedencia_estado'] ?? ($huesped['procedencia'] ?? 'No especificada');
            $pdf->Cell(60, 5, $u($procedencia), 0, 0, 'L');

            // ═══════════════════════════════════════════════════════
            // DATOS DE LA ESTANCIA
            // ═══════════════════════════════════════════════════════
            $y = $boxY + 24;
            $pdf->SetY($y);

            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DATOS DE LA ESTANCIA'), 0, 1, 'L');

            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);

            // Formatear fechas
            $formatFecha = function($fecha) use ($meses, $u) {
                $d = new DateTime($fecha);
                return $d->format('d') . ' de ' . $meses[$d->format('n')-1] . ' de ' . $d->format('Y');
            };

            $noches = $calculo['noches'];

            // Caja de fechas
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY2 = $pdf->GetY();
            $pdf->Rect($margin, $boxY2, $contentW, 14, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY2, $contentW, 14, 'D');

            $colW = $contentW / 3;

            $pdf->SetXY($margin + 4, $boxY2 + 2);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, 'CHECK-IN', 0, 0, 'L');
            $pdf->Cell($colW, 4, 'CHECK-OUT', 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, 'NOCHES', 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell($colW, 5, $u($formatFecha($fecha_entrada)), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($formatFecha($fecha_salida)), 0, 0, 'L');
            $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Cell($colW - 8, 5, $noches . ' noche' . ($noches > 1 ? 's' : ''), 0, 1, 'L');

            // ═══════════════════════════════════════════════════════
            // TABLA DE HABITACIONES
            // ═══════════════════════════════════════════════════════
            $y = $boxY2 + 22;
            $pdf->SetY($y);

            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DETALLE DE HABITACIONES'), 0, 1, 'L');

            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);

            // Mapeo de tipo a capacidad estimada
            $capacidadPorTipo = function($tipo) {
                $tipo = strtolower($tipo);
                if (strpos($tipo, 'sencilla') !== false) return 1;
                if (strpos($tipo, 'doble') !== false)    return 2;
                if (strpos($tipo, 'triple') !== false)   return 3;
                if (strpos($tipo, 'cuad') !== false)     return 4;
                if (strpos($tipo, 'suite') !== false)    return 4;
                if (strpos($tipo, 'premium') !== false)  return 3;
                return 2; // Default
            };

            // Formato de tipo legible
            $formatTipo = function($tipo) {
                return ucwords(str_replace('_', ' ', $tipo));
            };

            // Piso legible
            $formatPiso = function($piso) {
                $map = [
                    '-4' => '4 niveles abajo',
                    '-2' => '2 niveles abajo',
                    '-1' => '1 nivel abajo',
                    '1'  => 'Nivel de piso',
                    '2'  => '2do Nivel',
                    '3'  => '3er Nivel'
                ];
                return $map[$piso] ?? 'Piso ' . $piso;
            };

            // Columnas: Hab | Tipo | Piso | Personas | Precio/Noche | Noches | Total
            $colHab      = 18;
            $colTipo     = 36;
            $colPiso     = 28;
            $colPers     = 22;
            $colPrecio   = 30;
            $colNoches   = 20;
            $colTotal    = $contentW - $colHab - $colTipo - $colPiso - $colPers - $colPrecio - $colNoches;

            // Header de la tabla
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetX($margin);
            $pdf->Cell($colHab, 8, 'HAB.', 0, 0, 'C', true);
            $pdf->Cell($colTipo, 8, 'TIPO', 0, 0, 'C', true);
            $pdf->Cell($colPiso, 8, 'PISO', 0, 0, 'C', true);
            $pdf->Cell($colPers, 8, $u('PERS.'), 0, 0, 'C', true);
            $pdf->Cell($colPrecio, 8, 'PRECIO/NOCHE', 0, 0, 'C', true);
            $pdf->Cell($colNoches, 8, 'NOCHES', 0, 0, 'C', true);
            $pdf->Cell($colTotal, 8, 'TOTAL', 0, 1, 'C', true);

            // Filas de habitaciones
            $habitaciones_detalle = $calculo['habitaciones_detalle'] ?? [];
            $subtotal = 0;
            $descuento_cortesia = 0;
            $row = 0;

            foreach ($habitaciones_detalle as $hab) {
                $esCortesia = in_array($hab['id'], $cortesias_ids);
                $precioTotal = $hab['precio_calculado'] ?? ($hab['precio_base'] * $noches);
                $precioPorNoche = $hab['precio_por_noche'] ?? $hab['precio_base'];
                $subtotal += $precioTotal;

                if ($esCortesia) {
                    $descuento_cortesia += $precioTotal;
                }

                // Alternar color de fondo
                if ($row % 2 == 0) {
                    $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
                } else {
                    $pdf->SetFillColor($blanco[0], $blanco[1], $blanco[2]);
                }

                // Si es cortesía, fondo ámbar claro
                if ($esCortesia) {
                    $pdf->SetFillColor(254, 243, 199); // #FEF3C7
                }

                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->SetFont('Helvetica', $esCortesia ? 'B' : '', 8);
                $pdf->SetX($margin);

                $pdf->Cell($colHab, 7, $hab['numero'], 0, 0, 'C', true);
                $pdf->Cell($colTipo, 7, $u($formatTipo($hab['tipo'])), 0, 0, 'C', true);
                $pdf->Cell($colPiso, 7, $u($formatPiso($hab['piso'])), 0, 0, 'C', true);
                $pdf->Cell($colPers, 7, $capacidadPorTipo($hab['tipo']), 0, 0, 'C', true);

                if ($esCortesia) {
                    $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->Cell($colTotal, 7, $u('CORTESÍA'), 0, 1, 'C', true);
                } else {
                    $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
                    $pdf->Cell($colTotal, 7, '$' . number_format($precioTotal, 0, '.', ','), 0, 1, 'C', true);
                }

                $row++;
            }

            // Línea inferior de tabla
            $pdf->SetDrawColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($margin, $pdf->GetY(), $margin + $contentW, $pdf->GetY());

            // ═══════════════════════════════════════════════════════
            // TOTALES
            // ═══════════════════════════════════════════════════════
            $pdf->Ln(5);
            $totalesX = $margin + $contentW - 80;
            $totalesW = 80;

            // Subtotal
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->SetX($totalesX);
            $pdf->Cell(40, 6, 'Subtotal:', 0, 0, 'R');
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell(40, 6, '$' . number_format($subtotal, 0, '.', ',') . ' MXN', 0, 1, 'R');

            // Si hay cortesías
            if ($descuento_cortesia > 0) {
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                $pdf->SetX($totalesX);
                $habsCort = count($cortesias_ids);
                $pdf->Cell(40, 6, $u('Cortesía (' . $habsCort . ' hab.):'), 0, 0, 'R');
                $pdf->Cell(40, 6, '-$' . number_format($descuento_cortesia, 0, '.', ',') . ' MXN', 0, 1, 'R');
            }

            // Línea antes del total
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.5);
            $lineY = $pdf->GetY() + 1;
            $pdf->Line($totalesX, $lineY, $totalesX + $totalesW, $lineY);
            $pdf->Ln(4);

            // TOTAL FINAL
            $totalFinal = $subtotal - $descuento_cortesia;
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $totalBoxY = $pdf->GetY();
            $pdf->Rect($totalesX - 2, $totalBoxY, $totalesW + 4, 10, 'F');

            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor($blanco[0], $blanco[1], $blanco[2]);
            $pdf->SetXY($totalesX, $totalBoxY + 1.5);
            $pdf->Cell(40, 7, 'TOTAL:', 0, 0, 'R');
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->Cell(40, 7, '$' . number_format($totalFinal, 0, '.', ',') . ' MXN', 0, 1, 'R');

            // ═══════════════════════════════════════════════════════
            // TÉRMINOS Y CONDICIONES
            // ═══════════════════════════════════════════════════════
            $pdf->Ln(10);

            // Verificar si necesitamos nueva página
            if ($pdf->GetY() > 220) {
                $pdf->AddPage();
            }

            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('TÉRMINOS Y CONDICIONES'), 0, 1, 'L');

            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);

            // Caja de términos
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $termBoxY = $pdf->GetY();

            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);

            // Término 1 - con fechas dinámicas
            $fechaEntradaTexto = $formatFecha($fecha_entrada);
            $terminos = [
                'El alojamiento es por la noche del ' . $fechaEntradaTexto . ' con salida el dia siguiente a las doce del medio dia.',
                'El numero de personas se encuentra senalado en la tabla. En caso de ingresar mas personas se cobrara un excedente.',
                'CHECK IN: La hora de ingreso a las habitaciones es a las 15:00 hrs (3:00 PM).',
                'CHECK OUT: La hora para desocupar las habitaciones y salida del hotel es a las 12:00 hrs (12:00 PM).',
                'Esta cotizacion tiene una vigencia de 7 dias a partir de la fecha de elaboracion.',
                'Los precios pueden variar segun la temporada y disponibilidad al momento de confirmar.'
            ];

            // Calcular alto del recuadro
            $termH = 6 + (count($terminos) * 6) + 4;
            $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'D');

            // Acento olivo izquierdo
            $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Rect($margin, $termBoxY, 2.5, $termH, 'F');

            $pdf->SetXY($margin + 6, $termBoxY + 3);

            foreach ($terminos as $i => $termino) {
                $pdf->SetX($margin + 6);
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
                $bullet = ($i + 1) . '.';
                $pdf->Cell(6, 5, $bullet, 0, 0, 'R');
                $pdf->SetFont('Helvetica', '', 7.5);
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->Cell($contentW - 14, 5, '  ' . $u($termino), 0, 1, 'L');
            }

            // ═══════════════════════════════════════════════════════
            // FOOTER
            // ═══════════════════════════════════════════════════════
            $footerY = 265;
            $pdf->SetY($footerY);

            // Línea gold
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($margin, $footerY, $margin + $contentW, $footerY);

            $pdf->Ln(2);
            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor($grisCla[0], $grisCla[1], $grisCla[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW / 2, 4, $u('Hotel Los Cedros · Santa Catarina Juquila, Oaxaca'), 0, 0, 'L');
            $pdf->Cell($contentW / 2, 4, $u('Documento generado el ' . $hoy), 0, 1, 'R');

            // ─── Output PDF ──────────────────────────────────────
            $nombreArchivo = 'Cotizacion_LosCedros_' . date('Ymd_His') . '.pdf';
            $pdf->Output('I', $nombreArchivo);
            exit;

        } catch (Exception $e) {
            error_log('ERROR en cotizacionPdfAction: ' . $e->getMessage());
            die('Error al generar la cotizacion: ' . $e->getMessage());
        }
    }
    /**
     * Procesar descuento automático de inventario
     */
    private function procesarDescuentoInventario($reservacion_id) {
    try {
        error_log("=== INICIO procesarDescuentoInventario ===");
        error_log("Reservacion ID: " . $reservacion_id);
        
        // 1. Verificar configuración de inventario
        $sql = "SELECT COUNT(*) as total FROM inventario_config_habitacion WHERE activo = 1";
        $stmt = $this->db->query($sql);
        $config_count = $stmt->fetch()['total'];
        error_log("Configuraciones activas de inventario: " . $config_count);
        
        if ($config_count == 0) {
            error_log("ADVERTENCIA: No hay configuraciones de inventario activas");
            $_SESSION['mensaje_inventario'] = "No hay productos configurados para descuento automático";
            $_SESSION['tipo_mensaje'] = 'warning';
            return;
        }
        
        // 2. Obtener todas las habitaciones de la reservación
        $sql = "SELECT rh.habitacion_id, h.numero, h.tipo
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id
                WHERE rh.reservacion_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reservacion_id]);
        $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Habitaciones encontradas: " . count($habitaciones));
        foreach ($habitaciones as $hab) {
            error_log("  - Habitación {$hab['numero']} (ID: {$hab['habitacion_id']}, Tipo: {$hab['tipo']})");
        }
        
        if (empty($habitaciones)) {
            error_log("ERROR: No se encontraron habitaciones para la reservación");
            return;
        }
        
        // 3. Verificar configuración para cada tipo de habitación
        foreach ($habitaciones as $hab) {
            $sql = "SELECT COUNT(*) as total 
                    FROM inventario_config_habitacion 
                    WHERE tipo_habitacion = ? AND activo = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hab['tipo']]);
            $config_tipo = $stmt->fetch()['total'];
            error_log("  Configuraciones para tipo '{$hab['tipo']}': " . $config_tipo);
        }
        
        // 4. Cargar servicio de inventario
        $service_path = __DIR__ . '/../services/InventarioService.php';
        error_log("Buscando InventarioService en: " . $service_path);
        
        if (!file_exists($service_path)) {
            // Intentar ruta alternativa
            $service_path_alt = __DIR__ . '/../../services/InventarioService.php';
            if (file_exists($service_path_alt)) {
                $service_path = $service_path_alt;
                error_log("Encontrado en ruta alternativa: " . $service_path);
            } else {
                error_log("ERROR: No se encontró el archivo InventarioService.php");
                $_SESSION['error_inventario'] = "Sistema de inventario no configurado";
                return;
            }
        }
        
        require_once $service_path;
        
        if (!class_exists('InventarioService')) {
            error_log("ERROR: La clase InventarioService no existe");
            $_SESSION['error_inventario'] = "Error de configuración del inventario";
            return;
        }
        
        $inventarioService = new InventarioService($this->db);
        error_log("InventarioService creado correctamente");
        
        $total_productos_descontados = 0;
        $productos_sin_stock = [];
        $resumen_descuentos = [];
        
        // 5. Procesar descuento para cada habitación
        foreach ($habitaciones as $hab) {
            error_log("Procesando habitación {$hab['numero']} (Tipo: {$hab['tipo']})...");
            
            // Verificar disponibilidad primero
            $disponibilidad = $inventarioService->verificarDisponibilidad($hab['habitacion_id']);
            error_log("  Disponibilidad: " . json_encode($disponibilidad));
            
            // Procesar descuento
            $resultado = $inventarioService->descontarInventarioCheckIn(
                $hab['habitacion_id'], 
                $reservacion_id
            );
            
            error_log("  Resultado: " . json_encode($resultado));
            
            if ($resultado['success']) {
                if (!empty($resultado['productos_descontados'])) {
                    $total_productos_descontados += count($resultado['productos_descontados']);
                    $resumen_descuentos[$hab['numero']] = $resultado['productos_descontados'];
                    
                    foreach ($resultado['productos_descontados'] as $prod) {
                        error_log("    ✓ Descontado: {$prod['cantidad']} {$prod['unidad']} de {$prod['nombre']}");
                    }
                }
                
                if (!empty($resultado['productos_sin_stock'])) {
                    foreach ($resultado['productos_sin_stock'] as $producto) {
                        $productos_sin_stock[] = $producto['nombre'] . " (Hab. {$hab['numero']})";
                        error_log("    ⚠️ Sin stock: {$producto['nombre']}");
                    }
                }
            } else {
                error_log("  ERROR: " . ($resultado['error'] ?? 'Error desconocido'));
            }
        }
        
        // 6. Preparar mensaje de retroalimentación
        if ($total_productos_descontados > 0) {
            $mensaje = "✔ Se descontaron automáticamente del inventario: ";
            $detalles = [];
            
            foreach ($resumen_descuentos as $habitacion => $productos) {
                foreach ($productos as $prod) {
                    $detalles[] = $prod['cantidad'] . " " . $prod['unidad'] . " de " . $prod['nombre'];
                }
            }
            
            $mensaje .= implode(', ', $detalles);
            
            $_SESSION['mensaje_inventario'] = $mensaje;
            $_SESSION['tipo_mensaje'] = 'success';
            
            error_log("ÉXITO: " . $mensaje);
        } else {
            $mensaje = "No se encontraron productos configurados para descuento automático";
            $_SESSION['mensaje_inventario'] = $mensaje;
            $_SESSION['tipo_mensaje'] = 'warning';
            
            error_log("ADVERTENCIA: " . $mensaje);
        }
        
        // Agregar mensaje sobre productos sin stock
        if (!empty($productos_sin_stock)) {
            $mensaje_stock = "ATENCIÓN: Los siguientes productos no tenían stock suficiente: " . 
                           implode(', ', $productos_sin_stock);
            
            $_SESSION['mensaje_inventario'] = ($_SESSION['mensaje_inventario'] ?? '') . " | " . $mensaje_stock;
            $_SESSION['tipo_mensaje'] = 'warning';
            
            error_log($mensaje_stock);
        }
        
        error_log("=== FIN procesarDescuentoInventario ===");
        
    } catch (Exception $e) {
        error_log("ERROR CRÍTICO en procesarDescuentoInventario: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $_SESSION['error_inventario'] = "Advertencia: No se pudo procesar el descuento automático de inventario";
    }
}
    /**
     * Vista de calendario
     */
     
     /**
 * Check-out rápido (para llamadas AJAX desde index de habitaciones)
 */
public function checkOutRapidoAction() {
    $id = $this->route_params['id'] ?? 0;
    
    if (!$this->isPost()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }
    
    try {
        // Obtener datos de la reservación
        $reservacion = $this->reservacionModel->find($id);
        
        if (!$reservacion) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Reservación no encontrada'
            ]);
            exit;
        }
        
        // Verificar que esté en check-in
        if ($reservacion['estado'] != 'checked_in') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Solo se puede hacer check-out de reservaciones activas'
            ]);
            exit;
        }
        
        // NUEVO: Verificar controles remotos ANTES del check-out
        require_once __DIR__ . '/../models/ControlRemoto.php';
        $controlRemoto = new ControlRemoto();
        
        $habitaciones_con_control = [];
        $habitaciones = $this->reservacionModel->getHabitaciones($id);
        
        foreach ($habitaciones as $hab) {
            if (!$controlRemoto->hotelTieneRemoto($hab['habitacion_id'])) {
                $habitaciones_con_control[] = $hab['numero'];
            }
        }
        
        // Obtener datos del huésped
        $huesped = $this->huespedModel->find($reservacion['huesped_id']);
        
        // Obtener habitaciones para mostrar
        $sql = "SELECT GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') as habitaciones
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h ON rh.habitacion_id = h.id
                WHERE rh.reservacion_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $habitaciones_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener hora de salida
        $hora_salida = $this->getPost('hora_salida', date('H:i:s'));
        
        // Usar el método checkOut del modelo
        $resultado = $this->reservacionModel->checkOut($id, $hora_salida);
        
        // Procesar recogida automática de llaves
        $this->procesarRecogidaLlavesCheckOut($id);
        
        // Procesar recogida automática de controles remotos
        $this->procesarRecogidaRemotosCheckOut($id);
        
        if ($resultado) {
            $response = [
                'success' => true,
                'huesped' => $huesped['nombre_completo'],
                'habitaciones' => $habitaciones_info['habitaciones'],
                'hora_salida' => date('H:i', strtotime($hora_salida)),
                'total' => format_money($reservacion['precio_total']),
                'habitaciones_con_control' => $habitaciones_con_control,
                'message' => 'Check-out realizado correctamente. Llaves y controles remotos devueltos automáticamente.'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            throw new Exception('Error al realizar check-out');
        }
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
}
    public function calendarioAction() {
        $mes = intval($this->getQuery('mes', date('m')));
        $año = intval($this->getQuery('año', date('Y')));
        
        // Validar mes y año
        if ($mes < 1 || $mes > 12) {
            $mes = date('m');
        }
        if ($año < 2020 || $año > 2030) {
            $año = date('Y');
        }
        
        // Obtener reservaciones del mes
        $reservaciones = $this->reservacionModel->paraCalendario($mes, $año);
        
        // Obtener habitaciones
        $habitaciones = $this->habitacionModel->where(['activa' => 1]);
        
        // Organizar por día
        $calendario = [];
        foreach ($reservaciones as $reservacion) {
            $fecha_inicio = new DateTime($reservacion['fecha_entrada']);
            $fecha_fin = new DateTime($reservacion['fecha_salida']);
            
            $habitaciones_ids = explode(',', $reservacion['habitaciones_ids']);
            
            while ($fecha_inicio <= $fecha_fin) {
                $dia = $fecha_inicio->format('j');
                
                foreach ($habitaciones_ids as $hab_id) {
                    $calendario[$dia][$hab_id] = $reservacion;
                }
                
                $fecha_inicio->modify('+1 day');
            }
        }
        
        View::renderTemplate('reservaciones/calendario', [
            'title' => 'Calendario de Reservaciones',
            'mes' => $mes,
            'año' => $año,
            'calendario' => $calendario,
            'habitaciones' => $habitaciones,
            'reservaciones' => $reservaciones
        ]);
    }

    /**
     * ============================================================================
     * SISTEMA DE CHECK-IN TARDÍO - MÉTODOS DEL CONTROLADOR
     * ============================================================================
     */

    /**
     * Mostrar interfaz para check-in tardío con opciones
     */
    public function checkInTardioAction() {
        $id = $this->route_params['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'ID de reservación inválido'
            ];
            $this->redirect('/reservaciones');
        }
        
        try {
            // Verificar estado de la reservación
            $verificacion = $this->reservacionModel->verificarEstadoCheckIn($id);
            
            if (!$verificacion['puede_checkin']) {
                $_SESSION['flash_message'] = [
                    'tipo' => 'error',
                    'texto' => $verificacion['motivo']
                ];
                $this->redirect('/reservaciones/ver/' . $id);
            }
            
            // Obtener datos completos de la reservación
            $reservacion = $this->reservacionModel->find($id);
            $huesped = $this->huespedModel->find($reservacion['huesped_id']);
            $habitaciones = $this->reservacionModel->getHabitaciones($id);
            
            // Preparar vista según el tipo
            View::renderTemplate('reservaciones/check_in_tardio', [
                'title' => $verificacion['tipo'] == 'express' ? 'Check-in/Check-out Express' : 'Check-in Tardío',
                'reservacion' => $reservacion,
                'huesped' => $huesped,
                'habitaciones' => $habitaciones,
                'verificacion' => $verificacion
            ]);
            
        } catch (Exception $e) {
            error_log("Error en checkInTardioAction: " . $e->getMessage());
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'Error al procesar check-in tardío: ' . $e->getMessage()
            ];
            $this->redirect('/reservaciones');
        }
    }

    /**
     * Procesar check-in tardío (POST)
     */
    public function procesarCheckInTardioAction() {
        if (!$this->isPost()) {
            $this->redirect('/reservaciones');
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('reservacion_id');
        $tipo = $this->getPost('tipo'); // 'normal_tardio' o 'express'
        $notas = $this->getPost('notas_adicionales');
        
        try {
            // Verificar estado
            $verificacion = $this->reservacionModel->verificarEstadoCheckIn($id);
            
            if (!$verificacion['puede_checkin']) {
                throw new Exception($verificacion['motivo']);
            }
            
            // CASO 1: CHECK-IN TARDÍO NORMAL
            if ($tipo == 'normal_tardio') {
                $hora_entrada = $this->getPost('hora_entrada', date('H:i:s'));
                
                $resultado = $this->reservacionModel->checkInTardio($id, $hora_entrada, $notas);
                
                if ($resultado) {
                    // Procesar entrega de llaves
                    $this->procesarEntregaLlavesCheckIn($id);
                    
                    // Procesar entrega de controles remotos
                    $this->procesarEntregaRemotosCheckIn($id);
                    
                    // ========== PROCESAR SOLICITUD DE FACTURA ==========
                    $reservacion = $this->reservacionModel->find($id);
                    $pagos_tardio = $this->obtenerPagosDelPost();
                    $this->procesarSolicitudFactura($id, $pagos_tardio, $reservacion['precio_total']);
                    // ========== FIN FACTURA ==========
                    
                    $mensaje = "✓ Check-in tardío realizado exitosamente";
                    
                    if ($verificacion['dias_retraso'] > 0) {
                        $mensaje .= " (con " . $verificacion['dias_retraso'] . " día(s) de retraso)";
                    }
                    
                    $_SESSION['flash_message'] = [
                        'tipo' => 'success',
                        'texto' => $mensaje
                    ];
                } else {
                    throw new Exception("No se pudo realizar el check-in tardío");
                }
            }
            
            // CASO 2: CHECK-IN/CHECK-OUT EXPRESS
            elseif ($tipo == 'express') {
                
                // Obtener pagos si se proporcionaron
                $pagos = [];
                
                // Verificar si hay pago mixto
                $metodo_pago = $this->getPost('metodo_pago');
                
                if ($metodo_pago == 'mixto') {
                    // Pagos múltiples
                    $efectivo = floatval($this->getPost('efectivo', 0));
                    $tarjeta = floatval($this->getPost('tarjeta', 0));
                    $transferencia = floatval($this->getPost('transferencia', 0));
                    
                    if ($efectivo > 0) {
                        $pagos[] = [
                            'metodo' => 'efectivo',
                            'monto' => $efectivo,
                            'referencia' => null
                        ];
                    }
                    
                    if ($tarjeta > 0) {
                        $pagos[] = [
                            'metodo' => 'tarjeta',
                            'monto' => $tarjeta,
                            'referencia' => $this->getPost('referencia_tarjeta')
                        ];
                    }
                    
                    if ($transferencia > 0) {
                        $pagos[] = [
                            'metodo' => 'transferencia',
                            'monto' => $transferencia,
                            'referencia' => $this->getPost('referencia_transferencia')
                        ];
                    }
                } elseif ($metodo_pago && $metodo_pago != 'pendiente') {
                    // Pago único
                    $reservacion = $this->reservacionModel->find($id);
                    $pagos[] = [
                        'metodo' => $metodo_pago,
                        'monto' => $reservacion['precio_total'],
                        'referencia' => $this->getPost('referencia')
                    ];
                }
                
                // Ejecutar proceso express
                $resultado = $this->reservacionModel->checkInCheckOutExpress($id, $pagos, $notas);
                
                if ($resultado['success']) {
                    // Procesar llaves y controles (entrega y recogida automática)
                    $this->procesarEntregaLlavesCheckIn($id);
                    $this->procesarRecogidaLlavesCheckOut($id);
                    
                    $this->procesarEntregaRemotosCheckIn($id);
                    $this->procesarRecogidaRemotosCheckOut($id);
                    
                    // Procesar descuento de inventario si aplica
                    $this->procesarDescuentoInventario($id);
                    // ========== PROCESAR SOLICITUD DE FACTURA ==========
                    $reservacion_express = $this->reservacionModel->find($id);
                    $this->procesarSolicitudFactura($id, $pagos, $reservacion_express['precio_total']);
                    // ========== FIN FACTURA ==========
                    $mensaje = "✓ Proceso EXPRESS completado: Check-in y Check-out registrados automáticamente";
                    
                    if ($resultado['pagos_registrados']) {
                        $mensaje .= " | Pagos registrados en caja";
                    }
                    
                    $mensaje .= " | La reservación estaba vencida hace " . $verificacion['dias_pasados'] . " día(s)";
                    
                    $_SESSION['flash_message'] = [
                        'tipo' => 'success',
                        'texto' => $mensaje
                    ];
                } else {
                    throw new Exception("Error en proceso express");
                }
            }
            
            $this->redirect('/reservaciones/ver/' . $id);
            
        } catch (Exception $e) {
            error_log("Error en procesarCheckInTardioAction: " . $e->getMessage());
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'Error: ' . $e->getMessage()
            ];
            $this->redirect('/reservaciones/ver/' . $id);
        }
    }

    /**
     * Procesar entrega automática de controles remotos en check-in
     */
    private function procesarEntregaRemotosCheckIn($reservacion_id) {
        try {
            require_once __DIR__ . '/../models/ControlRemoto.php';
            $controlRemoto = new ControlRemoto();
            
            $habitaciones = $this->reservacionModel->getHabitaciones($reservacion_id);
            
            if (empty($habitaciones)) {
                error_log("No se encontraron habitaciones para la reservación: " . $reservacion_id);
                return;
            }
            
            $usuario_id = user_id();
            $usuario_nombre = $_SESSION['user_name'] ?? $_SESSION['usuario_nombre'] ?? 'Sistema';
            
            $controles_entregados = 0;
            
            foreach ($habitaciones as $hab) {
                // Verificar si el hotel tiene el control
                if ($controlRemoto->hotelTieneRemoto($hab['habitacion_id'])) {
                    // Entregar el control automáticamente
                    $entregado = $controlRemoto->entregarRemoto(
                        $hab['habitacion_id'],
                        $reservacion_id,
                        $usuario_id,
                        'Check-in automático - ' . $usuario_nombre,
                        'Control entregado automáticamente durante check-in'
                    );
                    
                    if ($entregado) {
                        $controles_entregados++;
                        error_log("Control remoto entregado automáticamente - Habitación: " . $hab['numero']);
                    }
                }
            }
            
            if ($controles_entregados > 0) {
                error_log("✓ Se entregaron $controles_entregados control(es) remoto(s) automáticamente");
            }
            
        } catch (Exception $e) {
            error_log("Error en procesarEntregaRemotosCheckIn: " . $e->getMessage());
        }
    }

    /**
     * API AJAX para verificar estado antes de check-in
     */
    public function verificarCheckInAction() {
        header('Content-Type: application/json');
        
        $id = $this->route_params['id'] ?? 0;
        
        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => 'ID inválido'
            ]);
            exit;
        }
        
        try {
            $verificacion = $this->reservacionModel->verificarEstadoCheckIn($id);
            
            echo json_encode([
                'success' => true,
                'verificacion' => $verificacion
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        
        exit;
    }
    
    /**
     * Procesar solicitud de factura durante el check-in
     * 
     * Lógica:
     * - Cliente QUIERE factura → Siempre crear solicitud tipo 'cliente'
     * - Cliente NO quiere factura + SOLO efectivo → NO crear nada
     * - Cliente NO quiere factura + tarjeta/transferencia → Crear solicitud tipo 'uso_interno'
     */
    private function procesarSolicitudFactura($reservacion_id, $pagos, $monto_total, $requiere_factura = null) {
        try {
            // Si no se pasó como parámetro, intentar leerlo del POST (check-in normal usa form POST)
            if ($requiere_factura === null) {
                $requiere_factura = $this->getPost('requiere_factura', '');
            }
            
            // Si no se indicó, no hacer nada (no debería pasar por la validación JS)
            if (empty($requiere_factura)) {
                return;
            }
            
            $usuario_id = $_SESSION['user_id'] ?? null;
            
            // Determinar método de pago principal
            $metodo_principal = 'efectivo';
            $monto_mayor = 0;
            $tiene_tarjeta = false;
            $tiene_transferencia = false;
            $solo_efectivo = true;
            
            foreach ($pagos as $pago) {
                if ($pago['monto'] > $monto_mayor) {
                    $monto_mayor = $pago['monto'];
                    $metodo_principal = $pago['metodo'];
                }
                if ($pago['metodo'] === 'tarjeta') {
                    $tiene_tarjeta = true;
                    $solo_efectivo = false;
                }
                if ($pago['metodo'] === 'transferencia') {
                    $tiene_transferencia = true;
                    $solo_efectivo = false;
                }
            }
            // Obtener tipo de tarjeta (crédito/débito) si aplica
$tipo_tarjeta = $this->getPost('tipo_tarjeta', '');
$nota_tipo_tarjeta = '';
if ($tiene_tarjeta && !empty($tipo_tarjeta)) {
    $nota_tipo_tarjeta = ' | Tarjeta de ' . ($tipo_tarjeta === 'credito' ? 'CRÉDITO' : 'DÉBITO');
}
            if ($requiere_factura === 'si') {
                // CASO 1: Cliente SÍ quiere factura → Siempre crear
                $this->reservacionModel->crearSolicitudFactura([
                    'reservacion_id' => $reservacion_id,
                    'requiere_factura' => 'si',
                    'tipo' => 'cliente',
                    'estatus' => 'pendiente',
                    'metodo_pago_principal' => $metodo_principal,
                    'monto_total' => $monto_total,
                    'usuario_registro_id' => $usuario_id,
                    'notas' => 'Cliente solicitó factura al momento del check-in' . $nota_tipo_tarjeta
                ]);
                
            } elseif ($requiere_factura === 'no') {
                if ($solo_efectivo && !empty($pagos)) {
                    // CASO 2: No quiere factura + solo efectivo → NO crear nada
                    return;
                }
                
                if ($tiene_tarjeta || $tiene_transferencia) {
                    // CASO 3: No quiere factura + tarjeta/transferencia → Crear como uso_interno
                    $metodos_usados = [];
                    if ($tiene_tarjeta) $metodos_usados[] = 'tarjeta';
                    if ($tiene_transferencia) $metodos_usados[] = 'transferencia';
                    
                    $this->reservacionModel->crearSolicitudFactura([
                        'reservacion_id' => $reservacion_id,
                        'requiere_factura' => 'no',
                        'tipo' => 'uso_interno',
                        'estatus' => 'pendiente',
                        'metodo_pago_principal' => $metodo_principal,
                        'monto_total' => $monto_total,
                        'usuario_registro_id' => $usuario_id,
'notas' => 'Factura de uso interno - Pago con ' . implode(' y ', $metodos_usados) . $nota_tipo_tarjeta . '. Cliente no requiere factura.'
                    ]);
                }
                
                // Si no hay pagos (sin_pago en tardío), no crear nada
            }
            
        } catch (Exception $e) {
            // No detener el check-in por error en factura
            error_log("Error al procesar solicitud de factura: " . $e->getMessage());
        }
    }
    
    /**
     * Cambiar método de pago de una reservación (AJAX)
     */
    public function cambiarMetodoPagoAction() {
        $id = $this->route_params['id'] ?? 0;
        
        header('Content-Type: application/json');
        
        if (!$this->isPost()) {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        try {
            // Leer JSON del body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['pagos'])) {
                echo json_encode(['success' => false, 'message' => 'Datos de pago no proporcionados']);
                exit;
            }
            
            // Verificar que la reservación existe y está en check-in
            $reservacion = $this->reservacionModel->find($id);
            if (!$reservacion) {
                echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
                exit;
            }
            
            if ($reservacion['estado'] !== 'checked_in') {
                echo json_encode(['success' => false, 'message' => 'Solo se puede cambiar el método de pago en reservaciones con check-in']);
                exit;
            }
            
            $pagos = $input['pagos'];
            $total = floatval($input['total'] ?? $reservacion['precio_total']);
            
            // Validar que los montos cuadren
            $total_pagos = 0;
            foreach ($pagos as $pago) {
                $total_pagos += floatval($pago['monto']);
            }
            
            if (abs($total_pagos - $total) > 0.01) {
                echo json_encode(['success' => false, 'message' => 'El total de los pagos no coincide con el total de la reservación']);
                exit;
            }
            
            // Verificar caja abierta
            $corteActual = $this->cajaModel->obtenerCorteActual();
            if (!$corteActual) {
                echo json_encode(['success' => false, 'message' => 'No hay caja abierta. Abra la caja primero.']);
                exit;
            }
            
            $usuario_id = user_id();
            
            // Determinar método principal (el de mayor monto)
            $metodo_principal = 'efectivo';
            $monto_mayor = 0;
            foreach ($pagos as $pago) {
                if (floatval($pago['monto']) > $monto_mayor) {
                    $monto_mayor = floatval($pago['monto']);
                    $metodo_principal = $pago['metodo'];
                }
            }
            
            // Si hay más de un método, marcar como mixto
            if (count($pagos) > 1) {
                $metodo_principal = $metodo_principal; // se guarda el principal, los detalles van en reservacion_pagos
            }
            
            $this->db->beginTransaction();
            
            // 1. Actualizar método de pago en reservaciones
            $sql = "UPDATE reservaciones SET metodo_pago = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$metodo_principal, $id]);
            
            // 2. Eliminar pagos anteriores de reservacion_pagos
            $sql_check = "SHOW TABLES LIKE 'reservacion_pagos'";
            $result = $this->db->query($sql_check);
            
            if ($result && $result->fetch()) {
                $sql = "DELETE FROM reservacion_pagos WHERE reservacion_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                
                // 3. Insertar nuevos pagos
                $sql = "INSERT INTO reservacion_pagos (reservacion_id, metodo_pago, monto, referencia, created_at) VALUES (?, ?, ?, ?, NOW())";
                foreach ($pagos as $pago) {
                    if (floatval($pago['monto']) > 0) {
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            $id,
                            $pago['metodo'],
                            floatval($pago['monto']),
                            $pago['referencia'] ?? null
                        ]);
                    }
                }
            }
            
            // 4. Actualizar movimientos_caja - SOLO si pertenecen al corte actual (abierto)
            //    Si pertenecen a un corte cerrado, NO se tocan para no descuadrar cortes anteriores.
            
            // Obtener categoría de hospedaje
            $sql_cat = "SELECT id FROM categorias_movimientos WHERE nombre = 'Hospedaje' AND tipo = 'ingreso' AND activa = 1 LIMIT 1";
            $stmt_cat = $this->db->prepare($sql_cat);
            $stmt_cat->execute();
            $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
            $categoria_id = $categoria ? $categoria['id'] : null;
            
            // Verificar en qué corte están los movimientos actuales de esta reservación
            $sql_check_corte = "SELECT id, corte_id FROM movimientos_caja 
                                WHERE reservacion_id = ? AND categoria = 'Hospedaje' AND tipo = 'ingreso'";
            $stmt_check = $this->db->prepare($sql_check_corte);
            $stmt_check->execute([$id]);
            $movimientos_existentes = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
            
            $hay_movimientos_en_corte_cerrado = false;
            if (!empty($movimientos_existentes)) {
                foreach ($movimientos_existentes as $mov) {
                    if ($mov['corte_id'] != $corteActual['id']) {
                        $hay_movimientos_en_corte_cerrado = true;
                        break;
                    }
                }
            }
            
            if ($hay_movimientos_en_corte_cerrado) {
                // Los movimientos pertenecen a un corte ya cerrado.
                // NO eliminamos ni creamos movimientos en caja para no descuadrar.
                // Solo se actualizaron reservaciones y reservacion_pagos (pasos 1-3).
                error_log("Cambio de método Res #$id: movimientos en corte cerrado, solo se actualizó reservacion_pagos. No se tocó movimientos_caja.");
            } else {
                // Los movimientos están en el corte actual o no existen → seguro eliminar y recrear
                $sql = "DELETE FROM movimientos_caja WHERE reservacion_id = ? AND categoria = 'Hospedaje'";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id]);
                
                // Insertar nuevos movimientos en el corte actual
                foreach ($pagos as $pago) {
                    if (floatval($pago['monto']) > 0) {
                        $sql = "INSERT INTO movimientos_caja 
                                (tipo, categoria, categoria_id, descripcion, monto, metodo_pago, 
                                 referencia, reservacion_id, usuario_id, corte_id, created_at) 
                                VALUES ('ingreso', 'Hospedaje', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $descripcion = "Hospedaje - Reservación #" . $id . " (Cambio de método)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            $categoria_id,
                            $descripcion,
                            floatval($pago['monto']),
                            $pago['metodo'],
                            $pago['referencia'] ?? null,
                            $id,
                            $usuario_id,
                            $corteActual['id']
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            
            // 5. Procesar solicitud de factura (misma lógica que el check-in)
            $requiere_factura = $input['requiere_factura'] ?? '';
            if (!empty($requiere_factura)) {
                // Adaptar estructura de pagos para procesarSolicitudFactura
                // (espera array con clave 'metodo' y 'monto')
                $pagos_para_factura = array_map(function($p) {
                    return [
                        'metodo' => $p['metodo'],
                        'monto'  => floatval($p['monto'])
                    ];
                }, $pagos);
                
                $this->procesarSolicitudFactura($id, $pagos_para_factura, $total, $requiere_factura);
            }
            
            // Log
            $metodos_str = implode(', ', array_map(function($p) { 
                return ucfirst($p['metodo']) . ': $' . number_format(floatval($p['monto']), 2); 
            }, $pagos));
            error_log("Cambio de método de pago - Reservación #$id: $metodos_str (Usuario: $usuario_id)");
            
            echo json_encode([
                'success' => true, 
                'message' => $hay_movimientos_en_corte_cerrado 
                    ? 'Método de pago actualizado en la reservación. Los movimientos de caja no se modificaron porque pertenecen a un corte ya cerrado.'
                    : 'Método de pago actualizado correctamente'
            ]);
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en cambiarMetodoPagoAction: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Obtener pagos del POST para check-in tardío
     */
    private function obtenerPagosDelPost() {
        $pagos = [];
        $metodos = ['efectivo', 'tarjeta', 'transferencia'];
        
        foreach ($metodos as $metodo) {
            $monto = floatval($this->getPost('monto_' . $metodo, 0));
            if ($monto > 0) {
                $pagos[] = [
                    'metodo' => $metodo,
                    'monto' => $monto,
                    'referencia' => $this->getPost('referencia_' . $metodo, null)
                ];
            }
        }
        
        return $pagos;
    }

    /**
     * Verificar disponibilidad para modificar días (AJAX)
     */
    public function verificarModificarDiasAction() {
        header('Content-Type: application/json');

        $reservacion_id     = intval($this->getPost('reservacion_id'));
        $nueva_fecha_salida = trim($this->getPost('nueva_fecha_salida', ''));

        if (!$reservacion_id || !$nueva_fecha_salida) {
            echo json_encode(['disponible' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        try {
            $model = new Reservacion();

            $reservacion = $model->obtenerDatosBasicos($reservacion_id);

            if (!$reservacion) {
                echo json_encode(['disponible' => false, 'mensaje' => 'Reservación no encontrada.']);
                return;
            }

            if (!in_array($reservacion['estado'], ['confirmada', 'checked_in'])) {
                echo json_encode(['disponible' => false, 'mensaje' => 'La reservación no se puede modificar en su estado actual.']);
                return;
            }

            $fecha_entrada   = new DateTime($reservacion['fecha_entrada']);
            $nueva_salida_dt = new DateTime($nueva_fecha_salida);

            if ($nueva_salida_dt <= $fecha_entrada) {
                echo json_encode(['disponible' => false, 'mensaje' => 'La nueva fecha de salida debe ser posterior a la entrada.']);
                return;
            }

            $habitacion_ids = $model->obtenerHabitacionIds($reservacion_id);

            if (empty($habitacion_ids)) {
                echo json_encode(['disponible' => false, 'mensaje' => 'No se encontraron habitaciones en esta reservación.']);
                return;
            }

            $disponible = $model->verificarDisponibilidadMultipleExcluyendo(
                $habitacion_ids,
                $reservacion['fecha_entrada'],
                $nueva_fecha_salida,
                $reservacion_id
            );

            if (!$disponible) {
                echo json_encode(['disponible' => false, 'mensaje' => 'Una o más habitaciones no están disponibles en las nuevas fechas.']);
                return;
            }

            $calculo = $model->calcularPrecioTotal(
                $habitacion_ids,
                $reservacion['fecha_entrada'],
                $nueva_fecha_salida
            );

            echo json_encode([
                'disponible'   => true,
                'nuevo_precio' => $calculo['precio_total'],
                'noches'       => $calculo['noches']
            ]);

        } catch (Exception $e) {
            error_log("Error verificarModificarDias: " . $e->getMessage());
            echo json_encode(['disponible' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Aplicar el cambio de días de la reservación (AJAX)
     */
    public function modificarDiasAction() {
        header('Content-Type: application/json');

        $reservacion_id     = intval($this->getPost('reservacion_id'));
        $nueva_fecha_salida = trim($this->getPost('nueva_fecha_salida', ''));

        if (!$reservacion_id || !$nueva_fecha_salida) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        try {
            $model = new Reservacion();
            $db = Database::getInstance();

            $reservacion = $model->obtenerDatosBasicos($reservacion_id);

            if (!$reservacion) {
                echo json_encode(['success' => false, 'mensaje' => 'Reservación no encontrada.']);
                return;
            }

            if (!in_array($reservacion['estado'], ['confirmada', 'checked_in'])) {
                echo json_encode(['success' => false, 'mensaje' => 'Estado inválido para modificar días.']);
                return;
            }

            // Si está en checked_in, verificar que haya caja abierta para ajustar
            $corteActual = null;
            if ($reservacion['estado'] === 'checked_in') {
                $cajaModel = new Caja();
                $corteActual = $cajaModel->obtenerCorteActual();
                if (!$corteActual) {
                    echo json_encode(['success' => false, 'mensaje' => 'Debe abrir la caja antes de modificar días en una reservación con check-in activo.']);
                    return;
                }
            }

            $fecha_entrada   = new DateTime($reservacion['fecha_entrada']);
            $nueva_salida_dt = new DateTime($nueva_fecha_salida);

            if ($nueva_salida_dt <= $fecha_entrada) {
                echo json_encode(['success' => false, 'mensaje' => 'La fecha de salida debe ser posterior a la entrada.']);
                return;
            }

            $habitacion_ids = $model->obtenerHabitacionIds($reservacion_id);

            $disponible = $model->verificarDisponibilidadMultipleExcluyendo(
                $habitacion_ids,
                $reservacion['fecha_entrada'],
                $nueva_fecha_salida,
                $reservacion_id
            );

            if (!$disponible) {
                echo json_encode(['success' => false, 'mensaje' => 'Conflicto de disponibilidad al guardar.']);
                return;
            }

            $calculo = $model->calcularPrecioTotal(
                $habitacion_ids,
                $reservacion['fecha_entrada'],
                $nueva_fecha_salida
            );

            $precio_anterior = floatval($reservacion['precio_total']);
            $precio_nuevo    = floatval($calculo['precio_total']);
            $diferencia      = round($precio_nuevo - $precio_anterior, 2);

            $resultado = $model->modificarFechaSalida(
                $reservacion_id,
                $nueva_fecha_salida,
                $calculo['precio_total']
            );

            if (!$resultado) {
                echo json_encode(['success' => false, 'mensaje' => 'Error al guardar en la base de datos.']);
                return;
            }

            // ========== AJUSTE DE CAJA ==========
            // Solo si la reservación ya tiene check-in (ya se cobró)
            $ajuste_caja = null;
            if ($reservacion['estado'] === 'checked_in' && $diferencia != 0 && $corteActual) {
                $usuario_id = user_id();

                // Obtener el método de pago principal de la reservación
                $stmt_mp = $db->query(
                    "SELECT metodo_pago FROM reservaciones WHERE id = ?",
                    [$reservacion_id]
                );
                $row_mp = $stmt_mp->fetch();
                $metodo_pago = $row_mp ? $row_mp['metodo_pago'] : 'efectivo';

                if ($diferencia < 0) {
                    // ─── REDUCCIÓN DE DÍAS → DEVOLUCIÓN (gasto) ───
                    $monto_devolver = abs($diferencia);

                    // Obtener o crear categoría de Devoluciones
                    $stmt_cat = $db->query(
                        "SELECT id FROM categorias_movimientos 
                         WHERE nombre = 'Devoluciones' AND tipo = 'egreso' AND activa = 1 
                         LIMIT 1"
                    );
                    $cat = $stmt_cat->fetch();

                    if (!$cat) {
                        $db->query(
                            "INSERT INTO categorias_movimientos 
                             (nombre, tipo, descripcion, icono, color, activa, created_at) 
                             VALUES ('Devoluciones', 'egreso', 'Devoluciones por ajustes', 
                                     'fas fa-undo', '#EF4444', 1, NOW())"
                        );
                        $categoria_id = $db->lastInsertId();
                    } else {
                        $categoria_id = $cat['id'];
                    }

                    $descripcion = "Devolución por reducción de días - Reservación #" . $reservacion_id .
                                   " (" . $precio_anterior . " → " . $precio_nuevo . ")";

                    $db->query(
                        "INSERT INTO movimientos_caja 
                         (tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                          referencia, reservacion_id, usuario_id, corte_id, created_at) 
                         VALUES ('gasto', 'Devoluciones', ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                        [
                            $categoria_id,
                            $descripcion,
                            $monto_devolver,
                            $metodo_pago,
                            'Ajuste por modificación de días',
                            $reservacion_id,
                            $usuario_id,
                            $corteActual['id']
                        ]
                    );

                    $ajuste_caja = [
                        'tipo' => 'devolucion',
                        'monto' => $monto_devolver,
                        'metodo_pago' => $metodo_pago
                    ];

                    error_log("Devolución registrada: $" . $monto_devolver . " (" . $metodo_pago . ") - Reservación #" . $reservacion_id);

                } else {
                    // ─── EXTENSIÓN DE DÍAS → COBRO ADICIONAL (ingreso) ───
                    $monto_cobrar = $diferencia;

                    // Obtener categoría de Hospedaje
                    $stmt_cat = $db->query(
                        "SELECT id FROM categorias_movimientos 
                         WHERE nombre = 'Hospedaje' AND tipo = 'ingreso' AND activa = 1 
                         LIMIT 1"
                    );
                    $cat = $stmt_cat->fetch();

                    if (!$cat) {
                        $db->query(
                            "INSERT INTO categorias_movimientos 
                             (nombre, tipo, descripcion, icono, color, activa, created_at) 
                             VALUES ('Hospedaje', 'ingreso', 'Ingresos por hospedaje', 
                                     'fas fa-bed', '#10B981', 1, NOW())"
                        );
                        $categoria_id = $db->lastInsertId();
                    } else {
                        $categoria_id = $cat['id'];
                    }

                    $descripcion = "Cobro adicional por extensión de días - Reservación #" . $reservacion_id .
                                   " (" . $precio_anterior . " → " . $precio_nuevo . ")";

                    $db->query(
                        "INSERT INTO movimientos_caja 
                         (tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                          referencia, reservacion_id, usuario_id, corte_id, created_at) 
                         VALUES ('ingreso', 'Hospedaje', ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                        [
                            $categoria_id,
                            $descripcion,
                            $monto_cobrar,
                            $metodo_pago,
                            'Ajuste por modificación de días',
                            $reservacion_id,
                            $usuario_id,
                            $corteActual['id']
                        ]
                    );

                    $ajuste_caja = [
                        'tipo' => 'cobro_adicional',
                        'monto' => $monto_cobrar,
                        'metodo_pago' => $metodo_pago
                    ];

                    error_log("Cobro adicional registrado: $" . $monto_cobrar . " (" . $metodo_pago . ") - Reservación #" . $reservacion_id);
                }
            }
            // ========== FIN AJUSTE DE CAJA ==========

            echo json_encode([
                'success'      => true,
                'nuevo_precio' => $calculo['precio_total'],
                'nueva_fecha'  => $nueva_fecha_salida,
                'noches'       => $calculo['noches'],
                'ajuste_caja'  => $ajuste_caja
            ]);

        } catch (Exception $e) {
            error_log("Error modificarDias: " . $e->getMessage());
            echo json_encode(['success' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
        }
    }
}
