<?php
/**
 * Controlador de Reservaciones - Versión Completa Corregida
 * Sistema hotelero
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Reservacion.php';
require_once __DIR__ . '/../models/Habitacion.php';
require_once __DIR__ . '/../models/Huesped.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../models/Caja.php';
require_once __DIR__ . '/../models/CuentaPorCobrar.php';
require_once __DIR__ . '/../helpers/hotel_config.php';

class ReservacionController extends Controller {
    
    private $reservacionModel;
    private $huespedModel;
    private $habitacionModel;
    private $cajaModel;
    private $db;
    
    private function normalizarRetornoCheckIn($valor): ?string {
        $valor = trim((string)$valor);
        if ($valor === '' || preg_match('/^[a-z][a-z0-9+.-]*:/i', $valor)) {
            return null;
        }

        $valor = str_replace('\\', '/', $valor);
        $valor = explode('#', $valor, 2)[0];
        $valor = ltrim($valor, '/');
        if ($valor === '') {
            return null;
        }

        $partes = explode('?', $valor, 2);
        $ruta = trim($partes[0], '/');
        if (!in_array($ruta, ['habitaciones', 'reservaciones'], true)) {
            return null;
        }

        $query = $partes[1] ?? '';
        return $ruta . ($query !== '' ? '?' . $query : '');
    }

    private function agregarCheckInOkRetorno(string $retorno, int $reservacionId): string {
        $partes = explode('?', $retorno, 2);
        $ruta = $partes[0];
        $query = [];
        if (!empty($partes[1])) {
            parse_str($partes[1], $query);
        }

        unset($query['checkin_ok']);
        $query['checkin_ok'] = $reservacionId;

        return $ruta . '?' . http_build_query($query);
    }

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

    private function cotizacionPdfBranding(): array {
        $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
        if (!is_array($branding)) {
            $branding = [];
        }

        // Intercambio primario<->secundario (pedido jul-22): el cromado que antes
        // usaba el color secundario del hotel ahora usa el primario y viceversa.
        $primary = $this->cotizacionPdfHex($branding['color_secondary'] ?? null, '#0F172A');
        $secondary = $this->cotizacionPdfHex($branding['color_primary'] ?? null, '#1B2746');
        $accent = $this->cotizacionPdfHex($branding['color_accent'] ?? null, '#BD9441');
        $headerText = $this->cotizacionPdfTextColor($secondary);
        $marcaClara = $headerText !== '#FFFFFF'; // fondo de marca claro -> texto oscuro

        // Acento LEGIBLE sobre el fondo de marca (header/tabla/cajas de total):
        // dorado solo cuando el fondo es oscuro; en fondos claros, el texto oscuro.
        $headerAccent = $marcaClara ? $headerText : $accent;

        // Acento para TEXTO sobre superficies claras del cuerpo (montos, noches,
        // términos): el primario si contrasta; si ambos colores de marca son
        // claros, gris tinta neutro para no perder legibilidad de dinero.
        if ($this->cotizacionPdfTextColor($primary) === '#FFFFFF') {
            $accentInk = $primary;
        } elseif ($this->cotizacionPdfTextColor($secondary) === '#FFFFFF') {
            $accentInk = $secondary;
        } else {
            $accentInk = '#374151';
        }

        return [
            'hotel' => function_exists('current_hotel_display_name')
                ? current_hotel_display_name('Medisoft Hoteles')
                : 'Medisoft Hoteles',
            'logo' => $this->cotizacionPdfLogoPath($branding),
            'primary_rgb' => $this->cotizacionPdfRgb($primary),
            'secondary_rgb' => $this->cotizacionPdfRgb($secondary),
            'accent_rgb' => $this->cotizacionPdfRgb($accent),
            'surface_rgb' => $this->cotizacionPdfRgb($this->cotizacionPdfMix($primary, '#FFFFFF', 0.07)),
            'line_rgb' => $this->cotizacionPdfRgb($this->cotizacionPdfMix($accent, '#E5E7EB', 0.25)),
            'header_text_rgb' => $this->cotizacionPdfRgb($headerText),
            'header_muted_rgb' => $this->cotizacionPdfRgb($this->cotizacionPdfMix($accent, $headerText, $marcaClara ? 0.25 : 0.65)),
            'header_accent_rgb' => $this->cotizacionPdfRgb($headerAccent),
            'accent_ink_rgb' => $this->cotizacionPdfRgb($accentInk),
        ];
    }

    private function cotizacionPdfHex($color, string $fallback): string {
        if (function_exists('hotel_branding_hex')) {
            return hotel_branding_hex($color, $fallback);
        }

        $color = trim((string) $color);
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : strtoupper($fallback);
    }

    private function cotizacionPdfRgb(string $hex): array {
        $hex = ltrim($this->cotizacionPdfHex($hex, '#000000'), '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private function cotizacionPdfMix(string $hex, string $target, float $ratio): string {
        $ratio = max(0, min(1, $ratio));
        $a = $this->cotizacionPdfRgb($hex);
        $b = $this->cotizacionPdfRgb($target);

        return sprintf(
            '#%02X%02X%02X',
            (int) round($a[0] * $ratio + $b[0] * (1 - $ratio)),
            (int) round($a[1] * $ratio + $b[1] * (1 - $ratio)),
            (int) round($a[2] * $ratio + $b[2] * (1 - $ratio))
        );
    }

    private function cotizacionPdfTextColor(string $hex): string {
        $rgb = $this->cotizacionPdfRgb($hex);
        $luminance = (($rgb[0] * 299) + ($rgb[1] * 587) + ($rgb[2] * 114)) / 1000;
        return $luminance > 155 ? '#111827' : '#FFFFFF';
    }

    private function cotizacionPdfLogoPath(array $branding) {
        $candidate = trim((string) ($branding['logo_url'] ?? ''));
        $defaultLogo = function_exists('hotel_branding_default_logo_path') ? hotel_branding_default_logo_path() : 'img/logo.png';
        if ($candidate === '' || $candidate === $defaultLogo) {
            return null;
        }

        if (preg_match('/[\x00-\x1F<>"\']/', $candidate)) {
            return null;
        }

        $publicRoot = defined('PUBLIC_PATH') ? realpath(PUBLIC_PATH) : null;
        $urlPath = parse_url($candidate, PHP_URL_PATH);
        if (!$publicRoot || !$urlPath) {
            return null;
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
            return null;
        }

        $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
        $realPath = realpath(PUBLIC_PATH . '/' . $cleanPath);
        if (!$realPath || strpos($realPath, $publicRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
            return null;
        }

        return in_array($extension, ['png', 'jpg', 'jpeg'], true) ? $realPath : null;
    }

    private function cotizacionPdfHoraConfig($value, string $fallback): string {
        $value = trim((string) $value);
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $value, $matches)) {
            return $matches[1] . ':' . $matches[2];
        }

        return $fallback;
    }

    private function cotizacionPdfHoraTexto(string $hora): string {
        $parts = explode(':', $hora);
        $hour = isset($parts[0]) ? (int) $parts[0] : 0;
        $minute = isset($parts[1]) ? (int) $parts[1] : 0;
        $period = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;
        if ($displayHour === 0) {
            $displayHour = 12;
        }

        return sprintf('%s hrs (%d:%02d %s)', $hora, $displayHour, $minute, $period);
    }

    private function cotizacionPdfHotelConfig(?int $hotelId = null): array {
        $checkinHora = $this->cotizacionPdfHoraConfig(
            function_exists('hotel_config_get') ? hotel_config_get('operacion.checkin_hora', '15:00', $hotelId) : '15:00',
            '15:00'
        );
        $checkoutHora = $this->cotizacionPdfHoraConfig(
            function_exists('hotel_config_get') ? hotel_config_get('operacion.checkout_hora', '12:00', $hotelId) : '12:00',
            '12:00'
        );
        $terminos = function_exists('hotel_config_get')
            ? trim((string) hotel_config_get('reservaciones.terminos_cotizacion', '', $hotelId))
            : '';

        return [
            'checkin_hora' => $checkinHora,
            'checkout_hora' => $checkoutHora,
            'checkin_texto' => $this->cotizacionPdfHoraTexto($checkinHora),
            'checkout_texto' => $this->cotizacionPdfHoraTexto($checkoutHora),
            'terminos' => $terminos,
        ];
    }

    /**
     * Líneas de texto (una por vehículo) con solo los campos que el huésped
     * realmente llenó en el formulario de vehículo (marca/modelo/placas/color/tipo).
     */
    private function cotizacionPdfVehiculos(int $huespedId, int $hotelId): array {
        if ($huespedId <= 0 || $hotelId <= 0) {
            return [];
        }
        // Un solo punto cubre las DOS acciones de cotización: sin el bloque
        // 'vehiculos' el PDF que se entrega al huésped no lleva placas y la caja
        // "DATOS DEL HUÉSPED" encoge sola (su alto es 18 + 5*N).
        if (function_exists('hotel_parking_visible') && !hotel_parking_visible($hotelId)) {
            return [];
        }
        if (!class_exists('HuespedVehiculo')) {
            require_once __DIR__ . '/../models/HuespedVehiculo.php';
        }

        $vehiculos = (new HuespedVehiculo())->porHuespedHotel($huespedId, $hotelId);
        if (empty($vehiculos)) {
            return [];
        }

        $tipoOpciones = [
            'auto' => 'Auto',
            'camioneta' => 'Camioneta',
            'motocicleta' => 'Motocicleta',
            'van' => 'Van',
            'otro' => 'Otro',
        ];

        $lineas = [];
        foreach ($vehiculos as $vehiculo) {
            $extras = function_exists('hotel_guest_decode_extra_json')
                ? hotel_guest_decode_extra_json($vehiculo['datos_extra_json'] ?? null)
                : [];
            $tipoCodigo = trim((string) ($extras['vehiculo_tipo'] ?? ''));
            $tipoLabel = $tipoCodigo !== '' ? ($tipoOpciones[$tipoCodigo] ?? $tipoCodigo) : '';

            $partes = [];
            if ($tipoLabel !== '') {
                $partes[] = $tipoLabel;
            }
            $marcaModelo = trim(trim((string) ($vehiculo['marca'] ?? '')) . ' ' . trim((string) ($vehiculo['modelo'] ?? '')));
            if ($marcaModelo !== '') {
                $partes[] = $marcaModelo;
            }
            $placas = trim((string) ($vehiculo['placas'] ?? ''));
            if ($placas !== '') {
                $partes[] = 'Placas ' . $placas;
            }
            $color = trim((string) ($vehiculo['color'] ?? ''));
            if ($color !== '') {
                $partes[] = 'Color ' . $color;
            }

            if (empty($partes)) {
                continue;
            }
            $linea = implode(' · ', $partes);
            if (mb_strlen($linea) > 95) {
                $linea = mb_substr($linea, 0, 94) . '…';
            }
            $lineas[] = $linea;
        }

        return $lineas;
    }

    private function cotizacionPdfTerminosDefault(string $fechaEntradaTexto, string $checkinTexto, string $checkoutTexto): array {
        return [
            'El alojamiento es por la noche del ' . $fechaEntradaTexto . ' con salida conforme al horario de check-out configurado.',
            'El numero de personas se encuentra senalado en la tabla. En caso de ingresar mas personas se cobrara un excedente.',
            'CHECK IN: La hora de ingreso a las habitaciones es a las ' . $checkinTexto . '.',
            'CHECK OUT: La hora para desocupar las habitaciones y salida del hotel es a las ' . $checkoutTexto . '.',
            'Esta cotizacion tiene una vigencia de 7 dias a partir de la fecha de elaboracion.',
            'Los precios pueden variar segun la temporada y disponibilidad al momento de confirmar.',
        ];
    }

    private function cotizacionPdfTerminos(array $config, string $fechaEntradaTexto, string $hotelNombre): array {
        $texto = trim((string) ($config['terminos'] ?? ''));
        if ($texto === '') {
            return $this->cotizacionPdfTerminosDefault(
                $fechaEntradaTexto,
                $config['checkin_texto'] ?? '15:00 hrs (3:00 PM)',
                $config['checkout_texto'] ?? '12:00 hrs (12:00 PM)'
            );
        }

        $replacements = [
            '{hotel}' => $hotelNombre,
            '{fecha_entrada}' => $fechaEntradaTexto,
            '{checkin}' => $config['checkin_texto'] ?? '',
            '{checkout}' => $config['checkout_texto'] ?? '',
        ];
        $texto = strtr($texto, $replacements);
        $lineas = preg_split('/\r\n|\r|\n/', $texto);
        $terminos = [];

        foreach ($lineas as $linea) {
            $linea = trim((string) $linea);
            $linea = preg_replace('/^(?:[-*]|\d+[.)])\s*/', '', $linea);
            if ($linea !== '') {
                $terminos[] = $linea;
            }
        }

        return $terminos ?: $this->cotizacionPdfTerminosDefault(
            $fechaEntradaTexto,
            $config['checkin_texto'] ?? '15:00 hrs (3:00 PM)',
            $config['checkout_texto'] ?? '12:00 hrs (12:00 PM)'
        );
    }

    private function cotizacionPdfRenderTerminos($pdf, array $terminos, callable $u, int $margin, int $contentW, array $cream, array $creamMid, array $olivo, array $negro): void {
        $terminos = array_values(array_filter(array_map('trim', $terminos), static function ($termino) {
            return $termino !== '';
        }));

        if (empty($terminos)) {
            return;
        }

        $textW = $contentW - 23;
        $lineHeights = [];
        foreach ($terminos as $termino) {
            $safeText = (string) $u($termino);
            $estimatedLines = max(1, (int) ceil($pdf->GetStringWidth($safeText) / max(1, $textW)));
            $lineHeights[] = max(5.2, $estimatedLines * 4.2);
        }

        $termH = 7 + array_sum($lineHeights) + count($terminos) + 4;
        if ($pdf->GetY() + $termH > 258) {
            $pdf->AddPage();
        }

        $termBoxY = $pdf->GetY();
        $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
        $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'F');
        $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
        $pdf->Rect($margin, $termBoxY, $contentW, $termH, 'D');
        $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
        $pdf->Rect($margin, $termBoxY, 2.5, $termH, 'F');

        $currentY = $termBoxY + 3.5;
        foreach ($terminos as $i => $termino) {
            $pdf->SetXY($margin + 5, $currentY);
            $pdf->SetFont('Helvetica', 'B', 7);
            $pdf->SetTextColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Cell(7, 4.4, ($i + 1) . '.', 0, 0, 'R');

            $pdf->SetXY($margin + 15, $currentY);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->MultiCell($textW, 4.2, (string) $u($termino), 0, 'L');
            $currentY = max($pdf->GetY(), $currentY + $lineHeights[$i]) + 1;
        }

        $pdf->SetY($termBoxY + $termH + 2);
    }

    public function agregarNotaAction() {
    require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

    if (!$this->isPost()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se pudo procesar la acción. Recarga la página e intenta de nuevo.']);
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
        require_permission_or_403('reservaciones.view');

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
            $hotel_id = obtenerHotelIdActualCompat();
            $reservacion = $this->reservacionModel->obtenerPorId($reservacion_id);
            if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
                die('Reservación no encontrada.');
            }

            // Anticipos REALES ya registrados (abonos + pagos). Si existen, mandan sobre el simbólico.
            $resumenCobro = $this->reservacionModel->resumenPagos((int)$reservacion_id, (int)$hotel_id);
            $anticipoReal = (float)($resumenCobro['pagado'] ?? 0);
            if ($anticipoReal > 0) {
                $anticipo = $anticipoReal;
            }

            // Obtener huésped
            $huesped = $this->huespedModel->findForHotel($reservacion['huesped_id'], $hotel_id);
            if (!$huesped) {
                die('Huésped no encontrado.');
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
            require_once PUBLIC_PATH . '/fdpdf/fpdf.php';
 
            $pdf = new FPDF('P', 'mm', 'Letter');
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->AddPage();
 
            // ─── Colores del hotel ───────────────────────────────
            $brand     = $this->cotizacionPdfBranding();
            $olivo     = $brand['primary_rgb'];
            $olivoOsc  = $brand['secondary_rgb'];
            $gold      = $brand['accent_rgb'];
            $cream     = $brand['surface_rgb'];
            $creamMid  = $brand['line_rgb'];
            $headerText = $brand['header_text_rgb'];
            $headerMuted = $brand['header_muted_rgb'];
            $goldMarca = $brand['header_accent_rgb']; // acento legible SOBRE el fondo de marca
            $tinta     = $brand['accent_ink_rgb'];    // acento legible sobre superficies claras
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
            $cotizacionConfig = $this->cotizacionPdfHotelConfig((int) $hotel_id);

            // Hora de llegada registrada en la reservación → columna propia en la caja de estancia
            $horaLlegadaRes = $this->cotizacionPdfHoraConfig($reservacion['hora_llegada_estimada'] ?? '', '');
            $llegadaTexto = $horaLlegadaRes !== ''
                ? $this->cotizacionPdfHoraTexto($horaLlegadaRes)
                : 'Por confirmar';
            $llegadaSub = $horaLlegadaRes !== '' ? 'Hora indicada por el huésped' : '';

            // ═══════════════════════════════════════════════════════
            // HEADER
            // ═══════════════════════════════════════════════════════
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->Rect(0, 0, $pageW, 42, 'F');
            $pdf->SetFillColor($olivo[0], $olivo[1], $olivo[2]);
            $pdf->Rect(0, 38, $pageW, 4, 'F');
 
            $logoPath = $brand['logo'];
            if ($logoPath) {
                // Tarjeta blanca detrás del logo (estética sobre el fondo de marca)
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect($margin - 1.5, 1.5, 35, 35, 'F');
                $pdf->Image($logoPath, $margin, 3, 32, 32);
            }
            $headerTitleX = $logoPath ? $margin + 36 : $margin;
            $headerTitleW = $logoPath ? 100 : 136;
 
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
            $pdf->SetXY($headerTitleX, 8);
            $pdf->Cell($headerTitleW, 8, $u($brand['hotel']), 0, 2, 'L');
 
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
            $pdf->SetX($headerTitleX);
            $pdf->Cell($headerTitleW, 5, $u('Sistema de gestión hotelera'), 0, 2, 'L');

            $pdf->SetFont('Helvetica', 'B', 22);
            $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
            $pdf->SetXY($pageW - $margin - 70, 9);
            $pdf->Cell(70, 10, $u('COTIZACIÓN'), 0, 0, 'R');
 
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor($headerMuted[0], $headerMuted[1], $headerMuted[2]);
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
 
            // Alto de la caja del huésped: crece si el huésped tiene vehículo(s) registrado(s)
            $vehiculoLineas = $this->cotizacionPdfVehiculos((int) ($huesped['id'] ?? 0), (int) $hotel_id);
            $huespedBoxH = 18 + (count($vehiculoLineas) * 5);

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

            // Filas siguientes: un vehículo por línea (solo campos que sí se llenaron)
            foreach ($vehiculoLineas as $i => $vehiculoLinea) {
                $pdf->SetXY($margin + 4, $boxY + 8 + (($i + 1) * 5));
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $etiqueta = count($vehiculoLineas) > 1 ? ('Vehículo ' . ($i + 1) . ':') : 'Vehículo:';
                $pdf->Cell(26, 5, $u($etiqueta), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->Cell($contentW - 34, 5, $u($vehiculoLinea), 0, 1, 'L');
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
            $pdf->Rect($margin, $boxY2, $contentW, 18, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY2, $contentW, 18, 'D');
 
            $colW = $contentW / 4;
            $pdf->SetXY($margin + 4, $boxY2 + 2);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, 'CHECK-IN', 0, 0, 'L');
            $pdf->Cell($colW, 4, 'CHECK-OUT', 0, 0, 'L');
            $pdf->Cell($colW, 4, $u('LLEGADA ESTIMADA'), 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, 'NOCHES', 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell($colW, 5, $u($formatFecha($reservacion['fecha_entrada'])), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($formatFecha($reservacion['fecha_salida'])), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($llegadaTexto), 0, 0, 'L');
            $pdf->SetTextColor($tinta[0], $tinta[1], $tinta[2]);
            $pdf->Cell($colW - 8, 5, $noches . ' noche' . ($noches > 1 ? 's' : ''), 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, $u('Desde ' . $cotizacionConfig['checkin_texto']), 0, 0, 'L');
            $pdf->Cell($colW, 4, $u('Hasta ' . $cotizacionConfig['checkout_texto']), 0, 0, 'L');
            $pdf->Cell($colW, 4, $u($llegadaSub), 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, '', 0, 1, 'L');

            // ═══════════════════════════════════════════════════════
            // TABLA DE HABITACIONES
            // ═══════════════════════════════════════════════════════
            $y = $boxY2 + 26;
            $pdf->SetY($y);
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->SetTextColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetX($margin);
            $pdf->Cell($contentW, 7, $u('DETALLE DE HABITACIONES'), 0, 1, 'L');
 
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->Line($margin, $pdf->GetY(), $margin + 50, $pdf->GetY());
            $pdf->Ln(3);
 
            $formatPisoCotizacion = function($piso) {
                $map = [
                    '-4' => '4 niveles abajo',
                    '-2' => '2 niveles abajo',
                    '-1' => '1 nivel abajo',
                    '1'  => 'Nivel de piso',
                    '2'  => '2do Nivel',
                    '3'  => '3er Nivel'
                ];
                return $map[(string)$piso] ?? 'Piso ' . $piso;
            };

            $tipoRealCotizacion = function(array $habitacion) {
                if (!empty($habitacion['tipo_label'])) {
                    return (string)$habitacion['tipo_label'];
                }

                return function_exists('get_tipo_habitacion_real')
                    ? get_tipo_habitacion_real($habitacion['tipo'] ?? '', $habitacion['caracteristicas'] ?? '')
                    : ucwords(str_replace('_', ' ', (string)($habitacion['tipo'] ?? 'Habitacion')));
            };

            // Capacidad de personas: dato real de la habitación o estimada por tipo
            $capacidadCotizacion = function(array $habitacion) {
                $cap = (int)($habitacion['capacidad_personas'] ?? 0);
                if ($cap > 0) {
                    return $cap;
                }
                $tipo = strtolower((string)($habitacion['tipo'] ?? ''));
                if (strpos($tipo, 'sencilla') !== false) return 1;
                if (strpos($tipo, 'doble') !== false)    return 2;
                if (strpos($tipo, 'triple') !== false)   return 3;
                if (strpos($tipo, 'cuad') !== false)     return 4;
                if (strpos($tipo, 'suite') !== false)    return 4;
                return 2;
            };

            // Columnas: HAB | TIPO | PISO | PERSONAS | PRECIO/NOCHE | NOCHES | TOTAL
            $colHab    = 18;
            $colTipo   = 36;
            $colPiso   = 28;
            $colPers   = 22;
            $colPrecio = 30;
            $colNoches = 20;
            $colTotal  = $contentW - $colHab - $colTipo - $colPiso - $colPers - $colPrecio - $colNoches;

            // Header tabla (texto adaptativo: el fondo es el color de marca)
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
            $pdf->SetFont('Helvetica', 'B', 7.5);
            $pdf->SetX($margin);
            $pdf->Cell($colHab, 8, 'HAB.', 0, 0, 'C', true);
            $pdf->Cell($colTipo, 8, 'TIPO', 0, 0, 'C', true);
            $pdf->Cell($colPiso, 8, 'PISO', 0, 0, 'C', true);
            $pdf->Cell($colPers, 8, 'PERS.', 0, 0, 'C', true);
            $pdf->Cell($colPrecio, 8, 'PRECIO/NOCHE', 0, 0, 'C', true);
            $pdf->Cell($colNoches, 8, 'NOCHES', 0, 0, 'C', true);
            $pdf->Cell($colTotal, 8, 'TOTAL', 0, 1, 'C', true);
 
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
 
                // Precio por noche YA con incremento (promedio, consistente con el total)
                $precioPorNoche = $noches > 0 ? ($precioTotalHab / $noches) : floatval($hab['precio_base']);
 
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
                $pdf->Cell($colTipo, 7, $u($tipoRealCotizacion($hab)), 0, 0, 'C', true);
                $pdf->Cell($colPiso, 7, $u($formatPisoCotizacion($hab['piso'] ?? '')), 0, 0, 'C', true);
                $pdf->Cell($colPers, 7, $capacidadCotizacion($hab), 0, 0, 'C', true);

                // Precio y noches
                if ($esCortesia) {
                    $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->Cell($colTotal, 7, $u('CORTESÍA'), 0, 1, 'C', true);
                } else {
                    $pdf->Cell($colPrecio, 7, '$' . number_format($precioPorNoche, 0, '.', ','), 0, 0, 'C', true);
                    $pdf->Cell($colNoches, 7, $noches, 0, 0, 'C', true);
                    $pdf->SetFont('Helvetica', 'B', 8);
                    $pdf->SetTextColor($tinta[0], $tinta[1], $tinta[2]);
                    $pdf->Cell($colTotal, 7, '$' . number_format($precioTotalHab, 0, '.', ','), 0, 1, 'C', true);
                }

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
 
            // Descuento de precio guardado en la reservación (por tipo + huésped)
            $descuento_precio = (float)($reservacion['descuento_total'] ?? 0);
            $totalReservacion = $subtotal - $descuento_cortesia - $descuento_precio;

            // Subtotal (cuando hay cortesía y/o descuento de precio)
            if ($descuento_cortesia > 0 || $descuento_precio > 0) {
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
                $pdf->SetX($totalesX);
                $pdf->Cell(40, 6, 'Subtotal:', 0, 0, 'R');
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $pdf->Cell(40, 6, '$' . number_format($subtotal, 0, '.', ',') . ' MXN', 0, 1, 'R');

                if ($descuento_cortesia > 0) {
                    $pdf->SetFont('Helvetica', 'B', 9);
                    $pdf->SetTextColor($ambar[0], $ambar[1], $ambar[2]);
                    $pdf->SetX($totalesX);
                    $cortCount = count(array_filter($habitaciones, fn($h) => $h['es_cortesia'] ?? false));
                    $pdf->Cell(40, 6, $u('Cortesía (' . $cortCount . ' hab.):'), 0, 0, 'R');
                    $pdf->Cell(40, 6, '-$' . number_format($descuento_cortesia, 0, '.', ',') . ' MXN', 0, 1, 'R');
                }

                if ($descuento_precio > 0) {
                    $pdf->SetFont('Helvetica', 'B', 9);
                    $pdf->SetTextColor(180, 57, 43);
                    $pdf->SetX($totalesX);
                    $pdf->Cell(40, 6, $u('Descuento:'), 0, 0, 'R');
                    $pdf->Cell(40, 6, '-$' . number_format($descuento_precio, 0, '.', ',') . ' MXN', 0, 1, 'R');
                }
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
                $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
                $pdf->SetXY($totalesX, $totalBoxY + 1.5);
                $pdf->Cell(40, 7, 'SALDO PENDIENTE:', 0, 0, 'R');
                $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
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
                $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
                $pdf->SetXY($totalesX, $totalBoxY + 1.5);
                $pdf->Cell(40, 7, 'TOTAL:', 0, 0, 'R');
                $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
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
            $terminos = $this->cotizacionPdfTerminos($cotizacionConfig, $fechaEntradaTexto, $brand['hotel']);
            $this->cotizacionPdfRenderTerminos($pdf, $terminos, $u, $margin, $contentW, $cream, $creamMid, $tinta, $negro);

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
            $pdf->Cell($contentW / 2, 4, $u($brand['hotel']), 0, 0, 'L');
            $pdf->Cell($contentW / 2, 4, $u('Documento generado el ' . $hoy), 0, 1, 'R');

            // Output
            $nombreArchivo = function_exists('hotel_export_filename')
                ? hotel_export_filename('Cotizacion_Res' . $reservacion_id, 'pdf')
                : 'Cotizacion_Res' . $reservacion_id . '_' . date('Ymd_His') . '.pdf';
            $pdf->Output('I', $nombreArchivo);
            exit;
 
        } catch (Exception $e) {
            error_log('ERROR en cotizacionReservacionPdfAction: ' . $e->getMessage());
            die('Error al generar la cotizacion: ' . $e->getMessage());
        }
    }


public function habitacionesApiAction() {
    // Gate RBAC no monetario (cerrado por defecto): consulta de habitaciones de
    // una reservacion (JSON). AJAX -> 403 JSON. Cross-hotel ya validado abajo.
    require_permission_or_403('reservaciones.view');

    header('Content-Type: application/json');

    $id = (int)($this->route_params['id'] ?? 0);
    
    try {
        // Verificar que la reservación exista
        $hotel_id = $this->hotelIdActual();
        $reservacion = $this->reservacionModel->obtenerPorId($id);
        
        if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
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
    // Gate RBAC de escritura: mismo permiso que el check-out completo.
    require_permission_or_403('habitaciones.checkout', 'No tiene permiso para registrar check-out');

    $id = $this->route_params['id'] ?? 0;

    if (!$this->isPost()) {
        // Si es AJAX, devolver JSON
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo procesar la acción. Recarga la página e intenta de nuevo.'
            ]);
            exit;
        }

        $_SESSION['flash_message'] = [
            'tipo' => 'error',
            'texto' => 'No se pudo procesar la acción. Recarga la página e intenta de nuevo.'
        ];
        return $this->redirect('/reservaciones');
    }
    
    try {
        // Obtener habitaciones seleccionadas
        $habitaciones_ids = $this->getPost('habitaciones', []);
        
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
        
        // Verificar nuevamente después del filtrado
        if (empty($habitaciones_ids)) {
            // Si es AJAX, devolver JSON
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Selecciona al menos una habitación para registrar la salida.'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'Selecciona al menos una habitación para registrar la salida.'
            ];
            return $this->redirect('/reservaciones/ver/' . $id);
        }
        
        $reservacion = $this->reservacionModel->obtenerPorId($id);
        if (!$reservacion) {
            throw new Exception('Reservación no encontrada para el hotel actual');
        }

        // Guard de saldo ANTES del check-out: si hay saldo pendiente devolvemos una
        // respuesta estructurada para mostrar el modal "Cuenta pendiente" con acción
        // de cobro, en vez de dejar que el modelo lance una excepción cruda (que se
        // veía como un error genérico). El guard del modelo se mantiene como red de
        // seguridad.
        $resumenPagos = $this->reservacionModel->resumenPagos($id, $this->hotelIdActual());
        $saldoPendiente = (float)($resumenPagos['saldo'] ?? 0);
        if ($saldoPendiente > 0.004) {
            $mensajeSaldo = 'Hay un saldo pendiente de $' . number_format($saldoPendiente, 2) . '. Cobra el saldo antes de registrar la salida.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'saldo_pendiente' => true,
                    'saldo' => $saldoPendiente,
                    'message' => $mensajeSaldo,
                    'url_reservacion' => function_exists('url') ? url('reservaciones/ver/' . $id) : null,
                ]);
                exit;
            }
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => $mensajeSaldo
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

            // Generar tareas de limpieza SOLO para los cuartos realmente liberados
            // (en check-out parcial algunos siguen ocupados por otra reservacion).
            $habitaciones_limpieza_ids = array_map('intval', $resultado['habitaciones_liberadas_ids'] ?? []);
            if (!empty($habitaciones_limpieza_ids)) {
                $this->generarTareasLimpiezaCheckOut($id, $habitaciones_limpieza_ids, $this->obtenerAsignacionesLimpiezaPost($habitaciones_limpieza_ids));
            }

            $mensaje = 'Check-out realizado correctamente. ';
            if (!empty($resultado['numeros_liberados'])) {
                $mensaje .= 'Se enviaron a limpieza ' . $resultado['habitaciones_liberadas'] . ' habitación(es): ';
                $mensaje .= implode(', ', $resultado['numeros_liberados']);
            } else {
                $mensaje .= 'No se cambiaron habitaciones a limpieza';
            }

            if (!empty($resultado['numeros_ocupadas_otro_flujo'])) {
                $mensaje .= '. Siguen ocupadas por otra reservacion activa: '
                    . implode(', ', $resultado['numeros_ocupadas_otro_flujo']) . '.';
            }
            
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
                    'habitaciones_liberadas_ids' => $resultado['habitaciones_liberadas_ids'] ?? [],
                    'habitaciones_restantes' => $resultado['habitaciones_restantes'],
                    'numeros_liberados' => $resultado['numeros_liberados'],
                    'numeros_ocupadas_otro_flujo' => $resultado['numeros_ocupadas_otro_flujo'] ?? []
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
                    'message' => $resultado['error'] ?? 'No se pudo registrar la salida. Intenta de nuevo.'
                ]);
                exit;
            }

            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => $resultado['error'] ?? 'No se pudo registrar la salida. Intenta de nuevo.'
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
    // Gate RBAC no monetario (cerrado por defecto): leer notas de una reservacion.
    require_permission_or_403('reservaciones.view');

    $reservacion_id = intval($this->getQuery('reservacion_id'));

    if (!$reservacion_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No encontramos esa reservación.']);
        exit;
    }

    // Proteccion cross-hotel: obtenerPorId ya filtra por hotel_id de la sesion,
    // asi que una reservacion de otro hotel no revela sus notas (404 limpio).
    $duenaDelHotel = $this->reservacionModel->obtenerPorId($reservacion_id);
    if (!$duenaDelHotel) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
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
    require_hotel_module('exportaciones');
    require_permission_or_403('reservaciones.view');
    try {
        $fecha = $this->getQuery('fecha', date('Y-m-d'));
        
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            throw new Exception('Formato de fecha inválido');
        }
        
        $hotel_id = obtenerHotelIdActualCompat();

        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        
        // Fecha bonita en español
        $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
        $dias_semana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
        $ts = strtotime($fecha);
        $fecha_bonita = $dias_semana[date('w', $ts)] . ', ' . date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);
        
        // Obtener todas las habitaciones — numéricas primero, luego colores
        $sql = "SELECT id, numero, tipo, precio_base 
                FROM habitaciones 
                WHERE hotel_id = ?
                AND activa = 1
                ORDER BY 
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN 0 ELSE 1 END,
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED) ELSE 0 END,
                    numero";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hotel_id]);
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
            LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
            LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = rh.hotel_id
            WHERE r.hotel_id = ?
            AND ? >= r.fecha_entrada AND ? < r.fecha_salida
            AND r.estado NOT IN ('cancelada', 'completada')
            GROUP BY r.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hotel_id, $fecha, $fecha]);
        $reservaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Vehículos (bloque 'vehiculos': sin contratar, el PDF exportado no
        // lleva marca/modelo/color del huésped — la columna queda "Sin vehiculo",
        // que además es cierto porque no pueden registrarlos).
        $vehiculos_por_huesped = [];
        if (!empty($reservaciones)
            && (!function_exists('hotel_parking_visible') || hotel_parking_visible($hotel_id))) {
            $huespedes_ids = array_unique(array_column($reservaciones, 'huesped_id'));
            if (!empty($huespedes_ids)) {
                $placeholders = str_repeat('?,', count($huespedes_ids) - 1) . '?';
                $stmt = $this->db->prepare("
                    SELECT v.huesped_id, v.marca, v.modelo, v.placas, v.color
                    FROM huesped_vehiculos v
                    INNER JOIN huespedes h
                        ON h.id = v.huesped_id
                       AND h.hotel_id = v.hotel_id
                    WHERE v.huesped_id IN ($placeholders)
                      AND v.hotel_id = ?
                      AND v.activo = 1
                ");
                $stmt->execute(array_merge(array_values($huespedes_ids), [$hotel_id]));
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
                    $stmt = $this->db->prepare(
                        "SELECT sf.reservacion_id
                         FROM solicitudes_factura sf
                         INNER JOIN reservaciones r
                            ON sf.reservacion_id = r.id
                            AND sf.hotel_id = r.hotel_id
                         WHERE sf.reservacion_id IN ($placeholders)
                           AND sf.hotel_id = ?
                           AND r.hotel_id = ?
                           AND sf.requiere_factura = 'si'
                           AND sf.created_at >= r.created_at"
                    );
                    $stmt->execute(array_merge(array_values($res_ids), [$hotel_id, $hotel_id]));
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
                $stmt2 = $this->db->prepare("SELECT rh.habitacion_id, rh.precio, rh.es_cortesia FROM reservacion_habitaciones rh WHERE rh.reservacion_id = ? AND rh.hotel_id = ?");
                $stmt2->execute([$r['id'], $hotel_id]);
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
        set_mensaje_error_op($e, 'generar el PDF');
        $this->redirect('reservaciones');
    }
}

    public function exportarExcelAction() {
        require_hotel_module('exportaciones');
        require_permission_or_403('reservaciones.view');
        try {
            $fecha = $this->getQuery('fecha', date('Y-m-d'));

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                throw new Exception('Formato de fecha inválido');
            }

            // Fecha bonita en español
            $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
            $dias_semana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
            $ts = strtotime($fecha);
            $hotel_id = obtenerHotelIdActualCompat();
            $fecha_bonita = $dias_semana[date('w', $ts)] . ', ' . date('j', $ts) . ' de ' . $meses[date('n', $ts) - 1] . ' de ' . date('Y', $ts);

            // Obtener todas las habitaciones activas ordenadas
            $stmt = $this->db->prepare("SELECT id, numero, tipo, precio_base 
                FROM habitaciones WHERE hotel_id = ? AND activa = 1
                ORDER BY 
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN 0 ELSE 1 END,
                    CASE WHEN numero REGEXP '^[0-9]+$' THEN CAST(numero AS UNSIGNED) ELSE 0 END,
                    numero");
            $stmt->execute([$hotel_id]);
            $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener reservaciones activas en esa fecha (NO incluir día de check-out)
            $stmt = $this->db->prepare("SELECT r.id, r.huesped_id, r.estado, r.fecha_entrada, r.fecha_salida,
                r.precio_total, r.metodo_pago, r.notas,
                h.nombre_completo, h.telefono, h.procedencia_estado, h.procedencia_ciudad,
                GROUP_CONCAT(rh.habitacion_id) as habitaciones_ids,
                GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                ANY_VALUE(rh.precio) as precio_habitacion,
                ANY_VALUE(rh.es_cortesia) as es_cortesia
                FROM reservaciones r
                INNER JOIN huespedes h ON r.huesped_id = h.id
                LEFT JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                LEFT JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = rh.hotel_id
                WHERE r.hotel_id = ?
                AND ? >= r.fecha_entrada AND ? < r.fecha_salida
                AND r.estado NOT IN ('cancelada', 'completada')
                GROUP BY r.id");
            $stmt->execute([$hotel_id, $fecha, $fecha]);
            $reservaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener vehículos (mismo gate que el export a PDF de arriba)
            $vehiculos_por_huesped = [];
            if (!empty($reservaciones)
                && (!function_exists('hotel_parking_visible') || hotel_parking_visible($hotel_id))) {
                $huespedes_ids = array_unique(array_column($reservaciones, 'huesped_id'));
                if (!empty($huespedes_ids)) {
                    $placeholders = str_repeat('?,', count($huespedes_ids) - 1) . '?';
                    $stmt = $this->db->prepare("
                        SELECT v.huesped_id, v.marca, v.modelo, v.placas, v.color
                        FROM huesped_vehiculos v
                        INNER JOIN huespedes h
                            ON h.id = v.huesped_id
                           AND h.hotel_id = v.hotel_id
                        WHERE v.huesped_id IN ($placeholders)
                          AND v.hotel_id = ?
                          AND v.activo = 1
                    ");
                    $stmt->execute(array_merge(array_values($huespedes_ids), [$hotel_id]));
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
                    $stmt = $this->db->prepare(
                        "SELECT sf.reservacion_id, sf.requiere_factura
                         FROM solicitudes_factura sf
                         INNER JOIN reservaciones r
                            ON sf.reservacion_id = r.id
                            AND sf.hotel_id = r.hotel_id
                         WHERE sf.reservacion_id IN ($placeholders)
                           AND sf.hotel_id = ?
                           AND r.hotel_id = ?
                           AND sf.requiere_factura = 'si'
                           AND sf.created_at >= r.created_at"
                    );
                    try {
                        $stmt->execute(array_merge(array_values($res_ids), [$hotel_id, $hotel_id]));
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
                        WHERE rh.reservacion_id = ? AND rh.hotel_id = ?");
                    $stmt2->execute([$r['id'], $hotel_id]);
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
            $nombre_archivo = function_exists('hotel_export_filename')
                ? hotel_export_filename('Reservaciones_' . str_replace('-', '', $fecha), 'xls', false)
                : 'Reservaciones_' . str_replace('-', '', $fecha) . '.xls';

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
    $branding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
    $hotelNombre = function_exists('hotel_branding_public_name')
        ? hotel_branding_public_name($branding, current_hotel_display_name('Medisoft Hoteles'))
        : (function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles');
    $hotelNombreMayus = function_exists('mb_strtoupper') ? mb_strtoupper($hotelNombre, 'UTF-8') : strtoupper($hotelNombre);
    $primary = $this->cotizacionPdfHex($branding['color_primary'] ?? null, '#1B2746');
    $secondary = $this->cotizacionPdfHex($branding['color_secondary'] ?? null, '#0F172A');
    $accent = $this->cotizacionPdfHex($branding['color_accent'] ?? null, '#BD9441');
    $primarySoft = $this->cotizacionPdfMix($primary, '#FFFFFF', 0.08);
    $accentSoft = $this->cotizacionPdfMix($accent, '#FFFFFF', 0.14);
    $lineColor = $this->cotizacionPdfMix($primary, '#E5E7EB', 0.16);
    $mutedText = $this->cotizacionPdfMix($secondary, '#FFFFFF', 0.62);
    $headerText = $this->cotizacionPdfTextColor($secondary);
    $accentText = $this->cotizacionPdfTextColor($accent);
    $ocupadas = $habitaciones_con_checkin + $habitaciones_reservadas;
    $porcentajeOcupacion = $total_habitaciones > 0 ? round(($ocupadas / $total_habitaciones) * 100) : 0;
    $fechaGeneracion = date('d/m/Y H:i') . ' hrs';
    $logoUrl = null;
    $candidateLogo = trim((string) ($branding['logo_url'] ?? ''));
    $defaultLogo = function_exists('hotel_branding_default_logo_path') ? hotel_branding_default_logo_path() : 'img/logo.png';
    $candidateLogoPath = parse_url($candidateLogo, PHP_URL_PATH);
    $candidateLogoNormalized = ltrim((string) ($candidateLogoPath ?: $candidateLogo), '/');
    if ($candidateLogo !== '' && $candidateLogoNormalized !== $defaultLogo && function_exists('hotel_branding_asset_url')) {
        $logoUrl = hotel_branding_asset_url($candidateLogo);
    }
    $brandInitials = '';
    foreach (preg_split('/\s+/', trim($hotelNombre)) as $word) {
        if ($word !== '') {
            $brandInitials .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
        }
        if (strlen($brandInitials) >= 3) {
            break;
        }
    }
    $brandInitials = $brandInitials !== '' ? (function_exists('mb_strtoupper') ? mb_strtoupper($brandInitials, 'UTF-8') : strtoupper($brandInitials)) : 'H';
    $esc = static function($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
        <title>Reservaciones - <?= htmlspecialchars($fecha_bonita) ?></title>
        <style>
            :root {
                --report-primary: <?= $esc($primary) ?>;
                --report-secondary: <?= $esc($secondary) ?>;
                --report-accent: <?= $esc($accent) ?>;
                --report-primary-soft: <?= $esc($primarySoft) ?>;
                --report-accent-soft: <?= $esc($accentSoft) ?>;
                --report-line: <?= $esc($lineColor) ?>;
                --report-muted: <?= $esc($mutedText) ?>;
                --report-header-text: <?= $esc($headerText) ?>;
                --report-accent-text: <?= $esc($accentText) ?>;
                --paper: #fffefb;
                --ink: #172033;
                --soft-gray: #f5f3ee;
                --free-bg: #edf8ef;
                --free-line: #8bbd95;
                --reserved-bg: #f3eefb;
                --reserved-line: #a891c8;
                --checkin-bg: #fcebea;
                --checkin-line: #d98781;
            }
            @page { size: A4 landscape; margin: 0.42cm 0.5cm; }
            @media print {
                body { margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; color-adjust: exact; }
                .no-print { display: none !important; }
                .page-break { break-before: page; page-break-before: always; }
                thead { display: table-header-group; }
            }
            * { box-sizing: border-box; }
            /* Documento de lectura con fuente muy chica: el zoom debe quedar libre. */
            html {
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 7.4pt;
                line-height: 1.18;
                margin: 0;
                padding: 5px;
                background: var(--paper);
                color: var(--ink);
            }
            .report-page { min-height: 188mm; }
            .brand-header {
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 10px;
                min-height: 25mm;
                padding: 8px 11px;
                margin-bottom: 5px;
                border: 1px solid color-mix(in srgb, var(--report-primary) 22%, #ffffff);
                border-radius: 8px;
                background:
                    linear-gradient(135deg, color-mix(in srgb, var(--report-secondary) 94%, #000000), color-mix(in srgb, var(--report-primary) 88%, #111827)),
                    var(--report-secondary);
                color: var(--report-header-text);
                box-shadow: 0 5px 16px rgba(17, 24, 39, .10);
            }
            .brand-mark {
                width: 18mm;
                height: 18mm;
                border-radius: 7px;
                background: rgba(255,255,255,.92);
                border: 1px solid rgba(255,255,255,.55);
                display: grid;
                place-items: center;
                overflow: hidden;
                color: var(--report-secondary);
                font-size: 10pt;
                font-weight: 800;
                letter-spacing: .4px;
            }
            .brand-mark img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                padding: 2.5mm;
            }
            .brand-eyebrow {
                color: var(--report-accent);
                font-size: 6.6pt;
                font-weight: 800;
                letter-spacing: .9px;
                text-transform: uppercase;
                margin-bottom: 1px;
            }
            .brand-title {
                font-size: 16pt;
                line-height: 1;
                font-weight: 800;
                letter-spacing: .2px;
                margin: 0;
            }
            .brand-subtitle {
                margin-top: 3px;
                color: rgba(255,255,255,.78);
                font-size: 8pt;
            }
            .date-card {
                min-width: 49mm;
                padding: 6px 8px;
                border-radius: 7px;
                background: rgba(255,255,255,.10);
                border: 1px solid rgba(255,255,255,.16);
                text-align: right;
            }
            .date-card .label {
                display: block;
                color: var(--report-accent);
                font-size: 6.2pt;
                font-weight: 800;
                letter-spacing: .7px;
                text-transform: uppercase;
            }
            .date-card .value {
                display: block;
                margin-top: 2px;
                font-size: 8.2pt;
                font-weight: 700;
            }
            .summary-grid {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 5px;
                margin: 0 0 5px;
            }
            .summary-card {
                padding: 5px 7px;
                border: 1px solid var(--report-line);
                border-radius: 7px;
                background: #ffffff;
                min-height: 12mm;
            }
            .summary-card span {
                display: block;
                color: var(--report-muted);
                font-size: 6.2pt;
                font-weight: 800;
                letter-spacing: .55px;
                text-transform: uppercase;
            }
            .summary-card strong {
                display: block;
                margin-top: 1px;
                color: var(--report-secondary);
                font-size: 13pt;
                line-height: 1;
            }
            .summary-card.is-accent {
                background: var(--report-accent-soft);
                border-color: color-mix(in srgb, var(--report-accent) 42%, #ffffff);
            }
            .section-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 5px 8px;
                margin-top: 3px;
                border-radius: 7px 7px 0 0;
                background: var(--report-primary);
                color: #fffefb;
                font-size: 8pt;
                font-weight: 800;
                letter-spacing: .45px;
                text-transform: uppercase;
            }
            .section-title small {
                color: rgba(255,255,255,.78);
                font-size: 6.4pt;
                font-weight: 700;
                letter-spacing: .2px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                font-size: 7.15pt;
                background: #ffffff;
                border: 1px solid var(--report-line);
                border-top: 0;
            }
            th {
                padding: 4px 4px;
                text-align: left;
                background: color-mix(in srgb, var(--report-secondary) 92%, #ffffff);
                color: #fffefb;
                border: 1px solid color-mix(in srgb, var(--report-secondary) 74%, #ffffff);
                font-size: 6.35pt;
                text-transform: uppercase;
                letter-spacing: .35px;
                white-space: nowrap;
            }
            th.center, td.center { text-align: center; }
            td {
                padding: 3px 4px;
                border: 1px solid var(--report-line);
                vertical-align: middle;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            tbody tr:nth-child(even) td { background-image: linear-gradient(rgba(17,24,39,.018), rgba(17,24,39,.018)); }
            .is-checkin td { background-color: var(--checkin-bg); border-color: color-mix(in srgb, var(--checkin-line) 54%, #ffffff); }
            .is-reserved td { background-color: var(--reserved-bg); border-color: color-mix(in srgb, var(--reserved-line) 54%, #ffffff); }
            .is-free td { background-color: var(--free-bg); border-color: color-mix(in srgb, var(--free-line) 52%, #ffffff); color: color-mix(in srgb, var(--report-secondary) 70%, #31533b); }
            .room-cell {
                font-weight: 900;
                text-align: center;
                font-size: 8.8pt;
                color: var(--report-secondary);
            }
            .guest-cell { font-weight: 800; white-space: nowrap; }
            .money-cell { text-align: right; font-weight: 800; white-space: nowrap; color: var(--report-secondary); }
            .mono-cell { font-family: "Courier New", monospace; font-size: 6.7pt; text-align: center; white-space: nowrap; }
            .small-cell { font-size: 6.55pt; }
            .status-pill {
                display: inline-block;
                min-width: 22mm;
                padding: 2px 5px;
                border-radius: 999px;
                font-size: 6.1pt;
                font-weight: 800;
                text-align: center;
                white-space: nowrap;
            }
            .status-pill.checkin { background: #ffffff; color: #9b2f2b; border: 1px solid var(--checkin-line); }
            .status-pill.reserved { background: #ffffff; color: #60438e; border: 1px solid var(--reserved-line); }
            .status-pill.free { background: #ffffff; color: #237047; border: 1px solid var(--free-line); }
            .invoice-mark {
                font-weight: 900;
                color: var(--report-secondary);
            }
            .footer-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
                margin-top: 5px;
                padding-top: 4px;
                border-top: 1px solid var(--report-line);
                color: var(--report-muted);
                font-size: 6.2pt;
            }
            .legend {
                display: flex;
                gap: 8px;
                align-items: center;
                white-space: nowrap;
            }
            .legend span::before {
                content: "";
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 2px;
                margin-right: 3px;
                vertical-align: -1px;
            }
            .legend .l-checkin::before { background: var(--checkin-bg); border: 1px solid var(--checkin-line); }
            .legend .l-reserved::before { background: var(--reserved-bg); border: 1px solid var(--reserved-line); }
            .legend .l-free::before { background: var(--free-bg); border: 1px solid var(--free-line); }
            .print-button {
                position: fixed;
                top: 10px;
                right: 10px;
                background: var(--report-primary);
                color: #fffefb;
                border: 0;
                padding: 10px 18px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 800;
                font-size: 10pt;
                z-index: 1000;
                box-shadow: 0 8px 20px rgba(17,24,39,.24);
            }
            .header {
                display: grid;
                grid-template-columns: auto 1fr auto;
                align-items: center;
                gap: 10px;
                min-height: 25mm;
                padding: 8px 11px;
                margin-bottom: 5px;
                border: 1px solid color-mix(in srgb, var(--report-primary) 22%, #ffffff);
                border-radius: 8px;
                background:
                    linear-gradient(135deg, color-mix(in srgb, var(--report-secondary) 94%, #000000), color-mix(in srgb, var(--report-primary) 88%, #111827)),
                    var(--report-secondary);
                color: var(--report-header-text);
                box-shadow: 0 5px 16px rgba(17, 24, 39, .10);
            }
            .header-logo {
                width: 18mm;
                height: 18mm;
                border-radius: 7px;
                background: rgba(255,255,255,.92);
                border: 1px solid rgba(255,255,255,.55);
                display: grid;
                place-items: center;
                overflow: hidden;
                color: var(--report-secondary);
                font-size: 10pt;
                font-weight: 800;
                letter-spacing: .4px;
            }
            .header-logo img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                padding: 2.5mm;
            }
            .header-copy { text-align: left; }
            .header-eyebrow {
                color: var(--report-accent);
                font-size: 6.6pt;
                font-weight: 800;
                letter-spacing: .9px;
                text-transform: uppercase;
                margin-bottom: 1px;
            }
            .header-title {
                margin: 0;
                font-size: 16pt;
                line-height: 1;
                font-weight: 800;
                letter-spacing: .2px;
            }
            .header-subtitle {
                margin-top: 3px;
                color: rgba(255,255,255,.78);
                font-size: 8pt;
                font-weight: 500;
            }
            .header-date {
                min-width: 48mm;
                padding: 6px 8px;
                border-radius: 7px;
                background: rgba(255,255,255,.10);
                border: 1px solid rgba(255,255,255,.16);
                text-align: right;
            }
            .header-date span {
                display: block;
                color: var(--report-accent);
                font-size: 6.2pt;
                font-weight: 800;
                letter-spacing: .7px;
                text-transform: uppercase;
            }
            .header-date strong {
                display: block;
                margin-top: 2px;
                font-size: 8.2pt;
            }
            .stats-bar {
                display: grid;
                grid-template-columns: repeat(5, 1fr);
                gap: 5px;
                margin-bottom: 5px;
            }
            .stats-bar span {
                display: block;
                padding: 5px 7px;
                min-height: 12mm;
                border: 1px solid var(--report-line);
                border-radius: 7px;
                background: #ffffff;
                color: var(--report-secondary);
                font-size: 7.1pt;
                font-weight: 800;
            }
            .stat-total { background: var(--report-primary-soft) !important; }
            .stat-occ { background: var(--report-accent-soft) !important; }
            .stat-ci { background: var(--checkin-bg) !important; }
            .stat-res { background: var(--reserved-bg) !important; }
            .stat-disp { background: var(--free-bg) !important; }
            .seccion-titulo {
                padding: 5px 8px;
                margin-top: 3px;
                border-radius: 7px 7px 0 0;
                background: var(--report-primary);
                color: #fffefb;
                font-size: 8pt;
                font-weight: 800;
                letter-spacing: .45px;
                text-transform: uppercase;
            }
            .con-checkin td { background-color: var(--checkin-bg); border-color: color-mix(in srgb, var(--checkin-line) 54%, #ffffff); }
            .reservada td { background-color: var(--reserved-bg); border-color: color-mix(in srgb, var(--reserved-line) 54%, #ffffff); }
            .disponible td { background-color: var(--free-bg); border-color: color-mix(in srgb, var(--free-line) 52%, #ffffff); color: color-mix(in srgb, var(--report-secondary) 70%, #31533b); }
            .hab-num { font-weight: 900; text-align: center; font-size: 8.8pt; color: var(--report-secondary); }
            .huesped { font-weight: 800; white-space: nowrap; }
            .precio { text-align: right; font-weight: 800; white-space: nowrap; color: var(--report-secondary); }
            .tel { font-family: "Courier New", monospace; font-size: 6.7pt; text-align: center; white-space: nowrap; }
            .pago, .vehiculo, .procedencia { font-size: 6.55pt; }
            .pie {
                margin-top: 5px;
                padding-top: 4px;
                border-top: 1px solid var(--report-line);
                color: var(--report-muted);
                font-size: 6.2pt;
                text-align: right;
            }
        </style>
    </head>
    <body>
        <button class="print-button no-print" onclick="window.print()">Imprimir PDF</button>
        
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
                        if ($res['es_cortesia_hab'] ?? false) { $precio_fmt = 'Cortesia'; }
                        
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
                        
                        $metodo = ucfirst($res['metodo_pago'] ?? '');
                        $estado_pago = $es_checkin ? 'Pagado' : 'Pendiente';
                        $tipo_pago = $metodo ? $metodo . ' - ' . $estado_pago : '';
                        
                        $factura_txt = isset($res['tiene_factura']) ? 'Si' : '';
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
        <?php if (!empty($hab_numericas)): ?>
        <div class="header">
            <div class="header-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?= $esc($logoUrl) ?>" alt="<?= $esc($hotelNombre) ?>">
                <?php else: ?>
                    <?= $esc($brandInitials) ?>
                <?php endif; ?>
            </div>
            <div class="header-copy">
                <div class="header-eyebrow">Operacion hotelera</div>
                <h1 class="header-title"><?= $esc($hotelNombreMayus) ?></h1>
                <div class="header-subtitle">Control diario de habitaciones - Habitaciones numericas</div>
            </div>
            <div class="header-date">
                <span>Fecha del reporte</span>
                <strong><?= $esc($fecha_bonita) ?></strong>
                <span style="margin-top:5px;">Generado</span>
                <strong><?= $esc($fechaGeneracion) ?></strong>
            </div>
        </div>
        
        <div class="stats-bar">
            <span class="stat-total">Total: <?= $total_habitaciones ?></span>
            <span class="stat-occ">Ocupacion: <?= $porcentajeOcupacion ?>%</span>
            <span class="stat-ci">Check-in: <?= $habitaciones_con_checkin ?></span>
            <span class="stat-res">Reservadas: <?= $habitaciones_reservadas ?></span>
            <span class="stat-disp">Disponibles: <?= $habitaciones_disponibles ?></span>
        </div>
        
        <?php if (!empty($hab_numericas)): ?>
            <?php $renderTabla($hab_numericas, 'HABITACIONES NUMÉRICAS (' . count($hab_numericas) . ')'); ?>
        <?php endif; ?>
        
        <div class="pie">
            <?= $esc($hotelNombre) ?> - Exportacion generada <?= $esc($fechaGeneracion) ?>
        </div>
        
        <!-- ═══ PÁGINA 2: Habitaciones de Color ═══ -->
        <?php endif; ?>
        <?php if (!empty($hab_color)): ?>
        <?php if (!empty($hab_numericas)): ?>
            <div class="page-break"></div>
        <?php endif; ?>
        
        <div class="header">
            <div class="header-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?= $esc($logoUrl) ?>" alt="<?= $esc($hotelNombre) ?>">
                <?php else: ?>
                    <?= $esc($brandInitials) ?>
                <?php endif; ?>
            </div>
            <div class="header-copy">
                <div class="header-eyebrow">Operacion hotelera</div>
                <h1 class="header-title"><?= $esc($hotelNombreMayus) ?></h1>
                <div class="header-subtitle">Control diario de habitaciones - Habitaciones de color</div>
            </div>
            <div class="header-date">
                <span>Fecha del reporte</span>
                <strong><?= $esc($fecha_bonita) ?></strong>
                <span style="margin-top:5px;">Generado</span>
                <strong><?= $esc($fechaGeneracion) ?></strong>
            </div>
        </div>
        
        <div class="stats-bar">
            <span class="stat-total">Total: <?= $total_habitaciones ?></span>
            <span class="stat-occ">Ocupacion: <?= $porcentajeOcupacion ?>%</span>
            <span class="stat-ci">Check-in: <?= $habitaciones_con_checkin ?></span>
            <span class="stat-res">Reservadas: <?= $habitaciones_reservadas ?></span>
            <span class="stat-disp">Disponibles: <?= $habitaciones_disponibles ?></span>
        </div>
        
        <?php $renderTabla($hab_color, 'HABITACIONES DE COLOR (' . count($hab_color) . ')'); ?>
        
        <div class="pie">
            <?= $esc($hotelNombre) ?> - Exportacion generada <?= $esc($fechaGeneracion) ?>
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
        require_hotel_module('reservaciones');
        return true;
    }

    /**
     * ¿El hotel puede APLICAR descuentos en una reservación?
     *
     * 'descuentos' es un bloque opcional ($99) que vive DENTRO de pantallas del
     * paquete base, así que su gate no puede ser un 403 en el before(): eso
     * tumbaría Reservaciones entero. El candado va en los puntos de entrada —
     * aquí el override manual del operador — y sigue la política de
     * HuespedController::descuentoHuespedDesdePost: sin el módulo el campo se
     * IGNORA en silencio, no revienta el alta.
     *
     * Los descuentos YA aplicados a reservaciones existentes se conservan
     * (historial congelado, mismo contrato que hotel_modulos y que
     * TarifaImpactoService con descuento_total).
     */
    private function puedeAplicarDescuentos(): bool {
        return !function_exists('current_hotel_has_module')
            || current_hotel_has_module('descuentos');
    }

    /**
     * ¿El hotel puede REGISTRAR solicitudes de factura?
     *
     * 'facturacion' es un bloque opcional ($249) cuyo flujo productor vive en
     * pantallas del paquete base (anticipos, check-in, cambio de pago), así que
     * —igual que descuentos— su gate no puede ser un 403: sin el módulo, el
     * campo requiere_factura se IGNORA en silencio y no se crea solicitud
     * alguna (ni de uso interno). La vista tampoco pinta la pregunta
     * ($rvModuloFacturacion en reservaciones/ver.php).
     *
     * Las solicitudes YA creadas se conservan (historial congelado) y el
     * ajuste NEGATIVO al revertir un anticipo facturado sigue pasando: solo
     * corrige una solicitud que nació con el módulo activo.
     */
    private function puedeRegistrarFacturas(): bool {
        return !function_exists('current_hotel_has_module')
            || current_hotel_has_module('facturacion');
    }


    /**
     * Listado de reservaciones
     */
private function obtenerProximasReservacionesIndex($buscar = null, $desde = null, $limite = 24) {
    $desde = (is_string($desde) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) ? $desde : date('Y-m-d');
    $limite = max(1, min(60, (int) $limite));
    $hotel_id = $this->hotelIdActual();

    $where = [
        "r0.hotel_id = ?",
        "r0.estado = 'confirmada'",
        "DATE(r0.fecha_entrada) > ?"
    ];
    $params = [$hotel_id, $desde];

    $buscar = trim((string) ($buscar ?? ''));
    if ($buscar !== '') {
        $where[] = "(h0.nombre_completo LIKE ? OR h0.telefono LIKE ? OR hab0.numero LIKE ? OR r0.id = ?)";
        $termino = '%' . $buscar . '%';
        $params[] = $termino;
        $params[] = $termino;
        $params[] = $termino;
        $params[] = (int) $buscar;
    }

    $whereSql = implode(' AND ', $where);
    $sql = "
        SELECT 
            r.id,
            r.huesped_id,
            r.fecha_entrada,
            r.fecha_salida,
            r.hora_llegada_estimada,
            r.hora_entrada,
            r.hora_salida,
            r.precio_total,
            r.metodo_pago,
            r.estado,
            r.notas,
            r.usuario_registro_id,
            r.created_at,
            h.nombre_completo as huesped_nombre,
            h.telefono as huesped_telefono,
            hab.id as habitacion_id,
            hab.numero as habitacion_numero,
            hab.tipo as habitacion_tipo,
            hab.piso as habitacion_piso,
            u.nombre_completo as usuario_registro,
            (
                SELECT COUNT(*)
                FROM reservacion_habitaciones rh_count
                WHERE rh_count.reservacion_id = r.id
                  AND rh_count.hotel_id = r.hotel_id
            ) as total_habitaciones_reserva,
            (
                SELECT GROUP_CONCAT(hab2.numero ORDER BY CAST(hab2.numero AS UNSIGNED), hab2.numero SEPARATOR ', ')
                FROM reservacion_habitaciones rh2
                INNER JOIN habitaciones hab2 ON rh2.habitacion_id = hab2.id AND hab2.hotel_id = rh2.hotel_id
                WHERE rh2.reservacion_id = r.id
                  AND rh2.hotel_id = r.hotel_id
            ) as todas_habitaciones
        FROM reservaciones r
        INNER JOIN (
            SELECT r0.id
            FROM reservaciones r0
            INNER JOIN huespedes h0 ON r0.huesped_id = h0.id AND h0.hotel_id = r0.hotel_id
            LEFT JOIN reservacion_habitaciones rh0 ON r0.id = rh0.reservacion_id AND rh0.hotel_id = r0.hotel_id
            LEFT JOIN habitaciones hab0 ON rh0.habitacion_id = hab0.id AND hab0.hotel_id = r0.hotel_id
            WHERE {$whereSql}
            GROUP BY r0.id
            ORDER BY r0.fecha_entrada ASC, COALESCE(r0.hora_llegada_estimada, '23:59:59') ASC, r0.id ASC
            LIMIT {$limite}
        ) proximas ON proximas.id = r.id
        INNER JOIN huespedes h ON r.huesped_id = h.id AND h.hotel_id = r.hotel_id
        INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
        INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = rh.hotel_id
        LEFT JOIN usuarios u ON r.usuario_registro_id = u.id
        WHERE r.hotel_id = ?
        ORDER BY r.fecha_entrada ASC, COALESCE(r.hora_llegada_estimada, '23:59:59') ASC, r.id ASC, CAST(hab.numero AS UNSIGNED), hab.numero
    ";

    $params[] = $hotel_id;
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function indexAction() {
    // Gate RBAC no monetario (cerrado por defecto): ver la lista de reservaciones.
    require_permission_or_403('reservaciones.view');

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
    
    $proximas_reservaciones = $this->obtenerProximasReservacionesIndex($buscar, $fecha, 60);
    $estadisticas = $this->reservacionModel->obtenerEstadisticasDashboard();
    $entradas_hoy = $this->reservacionModel->obtenerEntradasHoy();
    $salidas_hoy = $this->reservacionModel->obtenerSalidasHoy();
    $alertasPendientes = $this->obtenerAlertasPendientesReservaciones($this->hotelIdActual());

    // Cuartos que llegan hoy (una reserva grupal trae varios): mismo idioma
    // que la ficha "Por llegar" del index de habitaciones.
    $dbIdx = Database::getInstance();
    $stmtCuartosHoy = $dbIdx->query(
        "SELECT COUNT(DISTINCT rh.habitacion_id) AS total
         FROM reservaciones r
         INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
         WHERE r.hotel_id = ? AND r.fecha_entrada = CURDATE() AND r.estado = 'confirmada'",
        [$this->hotelIdActual()]
    );
    $habitaciones_llegan_hoy = (int)(($stmtCuartosHoy ? $stmtCuartosHoy->fetch() : [])['total'] ?? 0);

    $saldosPendientes = [];
    $resumenSaldosPendientes = ['total' => 0, 'saldo_estimado' => 0.0];
    try {
        $cuentasLectura = new CuentaPorCobrar();
        $saldosPendientes = $cuentasLectura->listarDerivadasPorHotel($this->hotelIdActual(), [
            'estado_reservacion' => 'todas',
            'estado_saldo' => 'pendiente',
            'vigencia' => 'todas',
        ], 300);
        $resumenSaldosPendientes = $cuentasLectura->resumenPorCuentas($saldosPendientes);
    } catch (Throwable $errorSaldos) {
         error_log('No se pudieron cargar los saldos pendientes de reservaciones: ' . $errorSaldos->getMessage());
     }

     View::renderTemplate('reservaciones/index', [
        'title' => 'Reservaciones - ' . current_hotel_display_name(),
        'reservaciones' => $reservaciones,
        'proximas_reservaciones' => $proximas_reservaciones,
        'buscar' => $buscar,
        'fecha_filtro' => $fecha,
        'estados' => Reservacion::getEstados(),
        'total_reservaciones' => count($reservaciones),
        'estadisticas' => $estadisticas,
        'entradas_hoy' => $entradas_hoy,
        'salidas_hoy' => $salidas_hoy,
        'habitaciones_llegan_hoy' => $habitaciones_llegan_hoy,
        'checkins_pendientes' => $alertasPendientes['checkins'],
        'checkouts_vencidos' => $alertasPendientes['checkouts'],
        'llegadas_tardias' => $alertasPendientes['llegadas_tardias'],
        'saldos_pendientes' => $saldosPendientes,
        'resumen_saldos_pendientes' => $resumenSaldosPendientes
    ]);
}

private function obtenerAlertasPendientesReservaciones(int $hotelId): array {
    // Fuente única en el modelo (misma data que usa el index de habitaciones).
    return $this->reservacionModel->alertasPendientesOperativas($hotelId);
}
    // ========== AGREGAR ESTOS MÉTODOS EN ReservacionController.php DESPUÉS DEL MÉTODO guardarAction() ==========

    /**
     * Mostrar vista para editar habitaciones de una reservación
     */
    public function editarHabitacionesAction() {
    require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

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
    $huesped = $this->huespedModel->findForHotel($reservacion['huesped_id'], $hotel_id);
    
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
     * Página completa para modificar la estancia (check-in / check-out) de una
     * reservación. Sustituye al modal "Modificar días" y permite además
     * desplazar la fecha de llegada en reservaciones confirmadas.
     */
    public function editarEstanciaAction() {
        require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

        $id = $this->route_params['id'] ?? 0;

        $reservacion = $this->reservacionModel->obtenerPorId($id);

        if (!$reservacion) {
            set_mensaje('Reservación no encontrada', 'error');
            $this->redirect('reservaciones');
            return;
        }

        if (!in_array($reservacion['estado'], ['confirmada', 'checked_in'], true)) {
            set_mensaje('Solo se puede modificar la estancia de reservaciones confirmadas o con check-in activo', 'warning');
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }

        $fecha_inicio = new DateTime($reservacion['fecha_entrada']);
        $fecha_fin    = new DateTime($reservacion['fecha_salida']);
        $noches       = (int)$fecha_inicio->diff($fecha_fin)->days;
        if ($noches < 1) {
            $noches = 1;
        }

        View::renderTemplate('reservaciones/editar-estancia', [
            'title'       => 'Modificar Estancia - Reservación #' . $id,
            'reservacion' => $reservacion,
            'noches'      => $noches
        ]);
    }

    /**
     * Actualizar habitaciones de una reservación
     */
    public function actualizarHabitacionesAction() {
        // Gate RBAC de escritura: cambiar habitaciones/precio de la reservación.
        require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

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
            set_mensaje_error_op($e, 'completar la operación');
            $this->redirect('reservaciones/editar-habitaciones/' . $reservacion_id);
        }
    }
    /**
     * Ver detalle de reservación
     */
    public function verAction() {
        // Gate RBAC no monetario (cerrado por defecto): ver el detalle de una
        // reservacion. El cross-hotel ya lo cubre obtenerPorId (scoped por hotel_id).
        require_permission_or_403('reservaciones.view');

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

        // Obtener información del huésped
        $huesped = $this->huespedModel->findForHotel($reservacion['huesped_id'], $this->hotelIdActual());
        
        // Obtener vehículos (bloque 'vehiculos': sin contratar no se consultan
        // placas — son PII que la vista ya no pinta). El guard preguntaba por
        // getVehiculos e invocaba getVehiculosPorHotel; se corrige de paso.
        $vehiculos = [];
        $parkingVisible = !function_exists('hotel_parking_visible')
            || hotel_parking_visible($this->hotelIdActual());
        if ($parkingVisible && method_exists($this->huespedModel, 'getVehiculosPorHotel')) {
            $vehiculos = $this->huespedModel->getVehiculosPorHotel($reservacion['huesped_id'], $this->hotelIdActual());
        }
        
        // Obtener pagos registrados en cualquier estado: el ticket y la ficha
        // los necesitan también tras el check-out (cargarlos solo en checked_in
        // dejaba el ticket reimpreso sin desglose real de pagos).
        $pagos = [];
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
        
        // Obtener notas
require_once __DIR__ . '/../models/ReservacionNota.php';
$notaModel = new ReservacionNota();
$notas = $notaModel->obtenerPorReservacion($id);
$total_notas = count($notas);

$documentosEntidad = [];
$documentosHuesped = [];
try {
    if (puede_ver_documentos_vinculados()) {
        $hotelId = (int)$this->hotelIdActual();
        $documentoModel = new Documento();
        if ($documentoModel->entidadExisteEnHotel($hotelId, 'reservacion', (int)$id)) {
            $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'reservacion', (int)$id, 10);
        }
        $huespedId = (int)($huesped['id'] ?? $reservacion['huesped_id'] ?? 0);
        if ($huespedId > 0 && $documentoModel->entidadExisteEnHotel($hotelId, 'huesped', $huespedId)) {
            $documentosHuesped = $documentoModel->documentosPorEntidad($hotelId, 'huesped', $huespedId, 8);
        }
    }
} catch (Throwable $e) {
    $documentosEntidad = [];
    $documentosHuesped = [];
}

        // Resumen de cobro (total/pagado/saldo) + abonos (anticipos) para la ficha
        require_once __DIR__ . '/../services/AnticipoService.php';
        $hotelIdAnticipo = (int)$this->hotelIdActual();
        $resumenPagos = $this->reservacionModel->resumenPagos((int)$id, $hotelIdAnticipo);
        $anticipoEval = (new AnticipoService())->evaluar($hotelIdAnticipo, (int)$id);
        $abonos = [];
        $anticipoFacturaSolicitud = null;
        $tipoTarjetaReservacion = '';
        try {
            $dbAbonos = Database::getInstance();
            $chkAbonos = $dbAbonos->query("SHOW TABLES LIKE 'reservacion_abonos'");
            if ($chkAbonos && $chkAbonos->rowCount() > 0) {
                $stmtAbonos = $dbAbonos->query(
                    "SELECT id, monto, metodo_pago, tipo_tarjeta, concepto, referencia, corte_id, created_at
                     FROM reservacion_abonos WHERE reservacion_id = ? AND hotel_id = ? ORDER BY id DESC",
                    [(int)$id, $hotelIdAnticipo]
                );
                $abonos = $stmtAbonos ? $stmtAbonos->fetchAll(PDO::FETCH_ASSOC) : [];
            }
            // Solicitud de factura pendiente originada por un anticipo
            $chkSf = $dbAbonos->query("SHOW TABLES LIKE 'solicitudes_factura'");
            if ($chkSf && $chkSf->rowCount() > 0) {
                $stmtTipoTarjeta = $dbAbonos->query(
                    "SELECT notas
                     FROM solicitudes_factura
                     WHERE reservacion_id = ? AND hotel_id = ?
                       AND metodo_pago_principal = 'tarjeta'
                       AND notas LIKE '%Tarjeta de%'
                     ORDER BY id DESC LIMIT 1",
                    [(int)$id, $hotelIdAnticipo]
                );
                $tipoTarjetaRow = $stmtTipoTarjeta ? $stmtTipoTarjeta->fetch(PDO::FETCH_ASSOC) : null;
                $tipoTarjetaNotas = is_array($tipoTarjetaRow) ? (string)($tipoTarjetaRow['notas'] ?? '') : '';
                if ($tipoTarjetaNotas !== '') {
                    if (preg_match('/tarjeta\s+de\s+cr.?dito/iu', $tipoTarjetaNotas)) {
                        $tipoTarjetaReservacion = 'credito';
                    } elseif (preg_match('/tarjeta\s+de\s+d.?bito/iu', $tipoTarjetaNotas)) {
                        $tipoTarjetaReservacion = 'debito';
                    }
                }

                $stmtSf = $dbAbonos->query(
                    "SELECT id, monto_total, metodo_pago_principal, estatus, created_at
                     FROM solicitudes_factura
                     WHERE reservacion_id = ? AND hotel_id = ? AND tipo = 'cliente'
                       AND estatus IN ('pendiente', 'en_proceso')
                     ORDER BY id DESC LIMIT 1",
                    [(int)$id, $hotelIdAnticipo]
                );
                $anticipoFacturaSolicitud = $stmtSf ? $stmtSf->fetch(PDO::FETCH_ASSOC) : null;
            }
        } catch (Throwable $e) {
            $abonos = [];
        }

        if ($tipoTarjetaReservacion !== '') {
            foreach ($pagos as &$pagoReservacion) {
                if (($pagoReservacion['metodo_pago'] ?? '') === 'tarjeta' && empty($pagoReservacion['tipo_tarjeta'])) {
                    $pagoReservacion['tipo_tarjeta'] = $tipoTarjetaReservacion;
                }
            }
            unset($pagoReservacion);
        }

        // Trabajadores activos para la asignacion opcional de limpieza en el check-out.
        $trabajadoresLimpieza = [];
        try {
            if (!class_exists('TareaOperativa')) {
                require_once __DIR__ . '/../models/TareaOperativa.php';
            }
            $tareaModelTmp = new TareaOperativa();
            if ($tareaModelTmp->tablaDisponible()) {
                $trabajadoresLimpieza = $tareaModelTmp->trabajadoresActivosOpciones((int)$this->hotelIdActual());
            }
        } catch (Throwable $e) {
            $trabajadoresLimpieza = [];
        }

        // Canal WhatsApp (bloque canal_whatsapp): estado del timeline de mensajes
        // de ESTA reservacion para el panel lateral. Solo LECTURAS aqui; el envio
        // real va por POST /mensajes/enviar. Defensivo: jamas rompe la ficha.
        $canalWhatsApp = null;
        try {
            $cwHotelId = (int)$this->hotelIdActual();
            if (function_exists('hotel_has_module') && hotel_has_module('canal_whatsapp', $cwHotelId)) {
                require_once __DIR__ . '/../services/CanalWhatsAppService.php';
                $cwServicio = new CanalWhatsAppService();
                $cwReservacion = [
                    'id' => (int)$id,
                    'estado' => (string)($reservacion['estado'] ?? ''),
                    'precio_total' => (float)($reservacion['precio_total'] ?? 0),
                    'fecha_entrada' => (string)($reservacion['fecha_entrada'] ?? ''),
                    'fecha_salida' => (string)($reservacion['fecha_salida'] ?? ''),
                    'huesped_nombre' => (string)($reservacion['huesped_nombre'] ?? ''),
                    'huesped_telefono' => (string)($reservacion['huesped_telefono'] ?? ''),
                    'habitaciones' => implode(', ', array_filter(array_map(
                        static function ($h) { return (string)($h['numero'] ?? ''); },
                        is_array($reservacion['habitaciones'] ?? null) ? $reservacion['habitaciones'] : []
                    ))),
                ];

                $cwTelefonoWa = $cwServicio->normalizarTelefono($cwReservacion['huesped_telefono']);
                $cwAplicables = $cwServicio->tiposParaReservacion($cwHotelId, $cwReservacion);
                $cwFaltantes = [];
                foreach ($cwAplicables as $cwTipo) {
                    $cwFaltantes[$cwTipo] = $cwServicio->componer($cwTipo, $cwReservacion, $cwHotelId, false)['faltantes'];
                }

                $canalWhatsApp = [
                    'telefono' => $cwReservacion['huesped_telefono'],
                    'telefono_wa' => $cwTelefonoWa,
                    'motivo_telefono' => $cwTelefonoWa === null ? $cwServicio->motivoTelefono($cwReservacion['huesped_telefono']) : '',
                    'activos' => $cwServicio->tiposActivos($cwHotelId),
                    'aplicables' => $cwAplicables,
                    'faltantes' => $cwFaltantes,
                    'timeline' => $cwServicio->porReservacion($cwHotelId, (int)$id),
                ];
            }
        } catch (Throwable $e) {
            error_log('Reservaciones: bloque canal_whatsapp no disponible en la ficha: ' . $e->getMessage());
            $canalWhatsApp = null;
        }

        View::renderTemplate('reservaciones/ver', [
    'title' => 'Reservación #' . $id . ' - ' . current_hotel_display_name(),
    'reservacion' => $reservacion,
    'huesped' => $huesped,
    'vehiculos' => $vehiculos,
    'habitaciones' => $habitaciones,
    'trabajadoresLimpieza' => $trabajadoresLimpieza,
    'estados' => Reservacion::getEstados(),
    'pagos' => $pagos,
    'resumenPagos' => $resumenPagos,
    'abonos' => $abonos,
    'anticipoEval' => $anticipoEval,
    'anticipoFacturaSolicitud' => $anticipoFacturaSolicitud,
    'tipoTarjetaReservacion' => $tipoTarjetaReservacion,
    'notas' => $notas,
    'total_notas' => $total_notas,
    'documentosEntidad' => $documentosEntidad,
    'documentosHuesped' => $documentosHuesped,
    'documentosEntidadContexto' => [
        'tipo' => 'reservacion',
        'id' => (int)$id,
        'label' => 'Reservacion',
    ],
    'canalWhatsApp' => $canalWhatsApp,
]);
    }
    
    
    /**
 * Entregar llave al huésped
 */
public function entregarLlaveAction() {
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: control de llaves (recepcionista tiene
    // llaves.control; gerencia/admin entran por habitaciones.all→checkin).
    if (!can_any(['llaves.control', 'habitaciones.checkin'])) {
        deny_access_403('No tiene permiso para controlar llaves');
    }
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
    
    // Aislamiento multi-tenant: la habitación debe ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel([$habitacion_id])) {
        set_mensaje('Habitación no válida para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: control de llaves (par de entregarLlaveAction).
    if (!can_any(['llaves.control', 'habitaciones.checkout'])) {
        deny_access_403('No tiene permiso para controlar llaves');
    }
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
    
    // Aislamiento multi-tenant: la habitación debe ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel([$habitacion_id])) {
        set_mensaje('Habitación no válida para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: mismo criterio que entregarLlaveAction.
    if (!can_any(['llaves.control', 'habitaciones.checkin'])) {
        deny_access_403('No tiene permiso para controlar llaves y remotos');
    }
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
    
    // Aislamiento multi-tenant: la habitación debe ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel([$habitacion_id])) {
        set_mensaje('Habitación no válida para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: mismo criterio que entregarLlaveAction.
    if (!can_any(['llaves.control', 'habitaciones.checkin'])) {
        deny_access_403('No tiene permiso para controlar llaves y remotos');
    }
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
    
    // Aislamiento multi-tenant: todas las habitaciones deben ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel($habitaciones_ids)) {
        set_mensaje('Una o más habitaciones no son válidas para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: mismo criterio que recibirLlaveAction.
    if (!can_any(['llaves.control', 'habitaciones.checkout'])) {
        deny_access_403('No tiene permiso para controlar llaves y remotos');
    }
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
    
    // Aislamiento multi-tenant: todas las habitaciones deben ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel($habitaciones_ids)) {
        set_mensaje('Una o más habitaciones no son válidas para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
    require_hotel_module('llaves_remotos');
    // Gate RBAC de escritura: mismo criterio que recibirLlaveAction.
    if (!can_any(['llaves.control', 'habitaciones.checkout'])) {
        deny_access_403('No tiene permiso para controlar llaves y remotos');
    }
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
    
    // Aislamiento multi-tenant: la habitación debe ser de este hotel.
    if (!$this->habitacionesPertenecenAlHotel([$habitacion_id])) {
        set_mensaje('Habitación no válida para este hotel', 'error');
        $this->redirect('reservaciones/ver/' . $reservacion_id);
        return;
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
 * Verifica que TODAS las habitaciones indicadas pertenezcan al hotel activo.
 * Habitacion::find() confina por hotel_id, asi que un id de otro hotel devuelve
 * false. Cierra el IDOR de escritura en control de llaves/remotos: esas tablas
 * (control_llaves, control_remotos, historial_*) no tienen columna hotel_id, y
 * antes el habitacion_id del POST llegaba al UPDATE sin validar pertenencia.
 */
private function habitacionesPertenecenAlHotel(array $habitacionIds): bool {
    if (empty($habitacionIds)) {
        return false;
    }
    foreach ($habitacionIds as $hid) {
        $hid = (int) $hid;
        if ($hid <= 0 || !$this->habitacionModel->find($hid)) {
            return false;
        }
    }
    return true;
}

private function erroresCamposReservacionCrear(string $mensaje): array {
    $mensaje = trim($mensaje);
    if ($mensaje === '') {
        return [];
    }

    $lower = strtolower($mensaje);
    $campo = 'fecha_entrada';

    if (strpos($lower, 'hu') !== false || strpos($lower, 'huesped') !== false) {
        $campo = 'huesped_id';
    } elseif (strpos($lower, 'habitaci') !== false || strpos($lower, 'dispon') !== false) {
        $campo = 'habitaciones';
    } elseif (strpos($lower, 'salida') !== false) {
        $campo = 'fecha_salida';
    } elseif (strpos($lower, 'entrada') !== false || strpos($lower, 'fecha') !== false) {
        $campo = 'fecha_entrada';
    } elseif (strpos($lower, 'hora') !== false || strpos($lower, 'llegada') !== false) {
        $campo = 'hora_llegada';
    }

    return [$campo => [$mensaje]];
}

    /**
     * Proceso de check-in usando el método del modelo
     */
    public function checkInAction() {
        // Gate RBAC de escritura: check-in cobra dinero (permiso existente
        // habitaciones.checkin; gerencia/admin entran por habitaciones.all).
        require_permission_or_403('habitaciones.checkin', 'No tiene permiso para registrar check-in');

        $id = $this->route_params['id'] ?? 0;
        $checkInOk = false;
        $checkInReturnTo = $this->normalizarRetornoCheckIn($this->getPost('checkin_return_to', ''));
        
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
            $permitirSaldoPendiente = $this->getPost('permitir_saldo_pendiente', '0') === '1';
            
            // Obtener la reservación para saber el precio
            $reservacion = $this->reservacionModel->obtenerPorId($id);
            if (!$reservacion) {
                throw new Exception('Reservación no encontrada para el hotel actual');
            }

            // SALDO-AWARE: el check-in cobra el SALDO (total - anticipos ya pagados), no el total.
            // Evita el doble cobro cuando ya hubo anticipos registrados en reservacion_abonos.
            $resumenCobro = $this->reservacionModel->resumenPagos((int)$id, (int)($reservacion['hotel_id'] ?? $this->hotelIdActual()));
            $saldoCheckin = (float)$resumenCobro['saldo'];

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

                    if ($metodo === 'tarjeta') {
                        // Mismo campo que lee procesarSolicitudFactura; el modelo lo sanitiza.
                        $pago['tipo_tarjeta'] = $this->getPost('tipo_tarjeta', '');
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
            
            // Si no hay pagos específicos, cobrar el SALDO por efectivo (no el total).
            // Si el saldo ya es 0 (cubierto por anticipos), no se agrega ningún pago.
            if (empty($pagos)) {
                if ($saldoCheckin > 0.004) {
                    if ($permitirSaldoPendiente) {
                        throw new Exception('Para dejar saldo pendiente registra al menos un pago parcial.');
                    }
                    $pagos[] = [
                        'metodo' => 'efectivo',
                        'monto' => $saldoCheckin,
                        'referencia' => null
                    ];
                    $monto_recibido_total = $saldoCheckin;
                }
            }

            // GUARDA UNIVERSAL anti-doble-cobro: no se puede cobrar más que el saldo pendiente.
            // Protege cualquier punto de entrada (ficha, listado, etc.) cuando ya hubo anticipos.
            $totalCobrado = 0;
            foreach ($pagos as $p) {
                $totalCobrado += (float)$p['monto'];
            }
            if ($totalCobrado > $saldoCheckin + 0.01) {
                $yaPagado = (float)$resumenCobro['pagado'];
                throw new Exception(sprintf(
                    'El cobro ($%s) excede el saldo pendiente ($%s). Esta reservación ya tiene $%s pagado (anticipos). Registra el check-in desde la ficha para cobrar solo el saldo.',
                    number_format($totalCobrado, 2),
                    number_format($saldoCheckin, 2),
                    number_format($yaPagado, 2)
                ));
            }

            // Usar el método del modelo para check-in con pagos mixtos
            if ($totalCobrado < $saldoCheckin - 0.01 && !$permitirSaldoPendiente) {
                throw new Exception(sprintf(
                    'El cobro ($%s) no cubre el saldo pendiente ($%s). Para continuar con pago parcial, marca la opcion de dejar saldo pendiente.',
                    number_format($totalCobrado, 2),
                    number_format($saldoCheckin, 2)
                ));
            }

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
                $this->procesarDescuentoInventario($id);
                $this->procesarEntregaLlavesCheckIn($id);
                
                // ========== PROCESAR SOLICITUD DE FACTURA ==========
                $this->procesarSolicitudFactura($id, $pagos, $totalCobrado);
                // ========== FIN FACTURA ==========
                $mensaje = 'Check-in realizado exitosamente';
                if ($cambio_total > 0) {
                    $mensaje .= sprintf('. Cambio a devolver: $%s', number_format($cambio_total, 2));
                }
                $resumenPosterior = $this->reservacionModel->resumenPagos((int)$id, (int)($reservacion['hotel_id'] ?? $this->hotelIdActual()));
                $saldoPosterior = (float)($resumenPosterior['saldo'] ?? 0);
                if ($saldoPosterior > 0.004) {
                    $mensaje .= sprintf('. Saldo pendiente: $%s. Puedes consultarlo en Reservaciones.', number_format($saldoPosterior, 2));
                }
                set_mensaje($mensaje, 'success');
                $checkInOk = true;
            } else {
                throw new Exception('Error al procesar el check-in');
            }
            
        } catch (Exception $e) {
            error_log("Error en checkInAction: " . $e->getMessage());
            set_mensaje_error_op($e, 'completar la operación');
        }
        
        if ($checkInOk && $checkInReturnTo !== null) {
            $this->redirect($this->agregarCheckInOkRetorno($checkInReturnTo, (int)$id));
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
    // Gate RBAC de escritura: registrar check-out (permiso existente
    // habitaciones.checkout; gerencia/admin entran por habitaciones.all).
    require_permission_or_403('habitaciones.checkout', 'No tiene permiso para registrar check-out');

    $id = $this->route_params['id'] ?? 0;

    if (!$this->isPost()) {
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    $this->validateCSRF();
    
    $reservacion = $this->reservacionModel->obtenerPorId($id);
    if (!$reservacion) {
        set_mensaje('Reservación no encontrada para el hotel actual', 'error');
        $this->redirect('reservaciones');
        return;
    }

    $resumenPagos = $this->reservacionModel->resumenPagos($id, $this->hotelIdActual());
    $saldoPendiente = (float)($resumenPagos['saldo'] ?? 0);
    if ($saldoPendiente > 0.004) {
        set_mensaje('No se puede hacer check-out con saldo pendiente de $' . number_format($saldoPendiente, 2), 'error');
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }

    // Obtener hora de salida
    $hora_salida = $this->getPost('hora_salida', date('H:i:s'));

    // Habitaciones de la reservacion (antes del check-out) para poder asignar limpieza.
    $habitacionIds = $this->reservacionModel->obtenerHabitacionIds($id);

    // Usar el método checkOut del modelo
    $resultado = $this->reservacionModel->checkOut($id, $hora_salida);

    if ($resultado) {
        $this->procesarRecogidaLlavesCheckOut($id);
        $this->procesarRecogidaRemotosCheckOut($id);
        $this->generarTareasLimpiezaCheckOut($id, $habitacionIds, $this->obtenerAsignacionesLimpiezaPost($habitacionIds));
        set_mensaje('Check-out realizado exitosamente. Las habitaciones pasaron a limpieza.', 'success');
    } else {
        set_mensaje('Error al realizar check-out', 'error');
    }
    
    $this->redirect('reservaciones/ver/' . $id);
}

    /**
     * Lee del POST la asignacion opcional de responsables de limpieza.
     * Acepta:
     *  - limpieza_responsable[habitacion_id] = trabajador_id (por cuarto)
     *  - limpieza_responsable_todas = trabajador_id (mismo responsable para todos)
     * Devuelve [habitacion_id => trabajador_id] solo con valores validos.
     */
    private function obtenerAsignacionesLimpiezaPost(array $habitacionIds): array {
        $asignaciones = [];

        $rawAsign = $this->getPost('limpieza_responsable', []);
        if (is_array($rawAsign)) {
            foreach ($rawAsign as $hId => $tId) {
                $hId = (int)$hId;
                $tId = (int)$tId;
                if ($hId > 0 && $tId > 0) {
                    $asignaciones[$hId] = $tId;
                }
            }
        }

        $global = (int)$this->getPost('limpieza_responsable_todas', 0);
        if ($global > 0) {
            foreach ($habitacionIds as $hId) {
                $hId = (int)$hId;
                if ($hId > 0 && !isset($asignaciones[$hId])) {
                    $asignaciones[$hId] = $global;
                }
            }
        }

        return $asignaciones;
    }

    /**
     * API: personal activo y habitaciones de la reservacion para el selector
     * de responsable de limpieza que se muestra al hacer check-out.
     * GET /api/reservaciones/{id}/limpieza-personal
     */
    public function limpiezaPersonalApiAction() {
        require_permission_or_403('reservaciones.view');

        header('Content-Type: application/json');

        $id = (int)($this->route_params['id'] ?? 0);

        try {
            $reservacion = $this->reservacionModel->obtenerPorId($id);
            if (!$reservacion) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
                exit;
            }

            $personal = [];
            if (!class_exists('TareaOperativa')) {
                require_once __DIR__ . '/../models/TareaOperativa.php';
            }
            $tareaModel = new TareaOperativa();
            if ($tareaModel->tablaDisponible() && $tareaModel->eventosDisponibles()) {
                foreach ($tareaModel->trabajadoresActivosOpciones((int)$this->hotelIdActual()) as $t) {
                    $personal[] = [
                        'id' => (int)$t['id'],
                        'nombre' => $t['nombre_completo'],
                        'rol' => $t['rol_laboral'] ?? '',
                    ];
                }
            }

            $habitaciones = [];
            foreach ($this->reservacionModel->getHabitaciones($id) as $hab) {
                $habitaciones[] = [
                    'habitacion_id' => (int)$hab['habitacion_id'],
                    'numero' => $hab['numero'],
                ];
            }

            echo json_encode([
                'success' => true,
                'disponible' => !empty($personal),
                'personal' => $personal,
                'habitaciones' => $habitaciones,
            ]);
        } catch (Throwable $e) {
            // Fail-open: el selector es opcional, nunca debe bloquear el check-out.
            echo json_encode(['success' => true, 'disponible' => false, 'personal' => [], 'habitaciones' => []]);
        }
        exit;
    }

    /**
     * Genera automaticamente una tarea operativa de limpieza por cada habitacion
     * de la reservacion tras el check-out (las habitaciones quedaron en limpieza).
     * Idempotente: el modelo evita duplicados y exige estado 'limpieza'.
     * Nunca rompe el check-out si Tareas no esta disponible.
     */
    private function generarTareasLimpiezaCheckOut($reservacion_id, ?array $habitacionIds = null, array $asignaciones = []) {
        try {
            if (!class_exists('TareaOperativa')) {
                require_once __DIR__ . '/../models/TareaOperativa.php';
            }

            $tareaModel = new TareaOperativa();
            if (!$tareaModel->tablaDisponible() || !$tareaModel->eventosDisponibles()) {
                return;
            }

            $hotelId = (int)$this->hotelIdActual();
            if ($hotelId <= 0) {
                return;
            }

            $usuarioId = function_exists('user_id') ? user_id() : null;

            if ($habitacionIds === null) {
                $habitacionIds = $this->reservacionModel->obtenerHabitacionIds($reservacion_id);
            }

            foreach ((array)$habitacionIds as $habId) {
                $habId = (int)$habId;
                if ($habId <= 0) {
                    continue;
                }
                try {
                    $tareaModel->crearDesdeLimpiezaHabitacionParaHotel(
                        $hotelId,
                        $habId,
                        ['titulo' => 'Limpieza tras check-out'],
                        $usuarioId
                    );
                } catch (Throwable $e) {
                    // Duplicado o cuarto no en limpieza: se ignora sin romper el check-out.
                    error_log('generarTareasLimpiezaCheckOut hab #' . $habId . ': ' . $e->getMessage());
                }

                // Asignacion opcional de responsable de limpieza para este cuarto.
                $trabajadorId = (int)($asignaciones[$habId] ?? 0);
                if ($trabajadorId > 0) {
                    try {
                        $tarea = $tareaModel->buscarTareaActivaLimpiezaPorHabitacionHotel($hotelId, $habId);
                        if ($tarea && !empty($tarea['id'])) {
                            $tareaModel->asignarTrabajadorParaHotel((int)$tarea['id'], $hotelId, $trabajadorId, $usuarioId);
                        }
                    } catch (Throwable $e) {
                        // Trabajador invalido / inactivo: no romper el check-out.
                        error_log('asignar limpieza hab #' . $habId . ': ' . $e->getMessage());
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('generarTareasLimpiezaCheckOut reserva #' . $reservacion_id . ': ' . $e->getMessage());
        }
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
    /**
     * Registrar un anticipo/abono real de la reservación (movimiento de Caja + abono).
     */
    public function registrarAnticipoAction() {
        // Gate RBAC de escritura: cobro de dinero por caja (caja.cobros lo
        // tienen recepcionista, administrador y gerente en sus presets).
        require_permission_or_403('caja.cobros', 'No tiene permiso para registrar cobros');

        $id = (int)($this->route_params['id'] ?? 0);
        if (!$this->isPost()) {
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }
        $this->validateCSRF();

        // El bloque 'anticipos' gatea solo depositos previos a la llegada.
        // Cobrar saldo pendiente tras check-in/check-out es flujo core y no se bloquea.
        $reservacionGate = $this->reservacionModel->obtenerPorId($id);
        $estadoGate = strtolower((string)($reservacionGate['estado'] ?? ''));
        if (!in_array($estadoGate, ['checked_in', 'checked_out'], true)) {
            require_hotel_module('anticipos');
        }
        try {
            require_once __DIR__ . '/../services/AnticipoService.php';
            $hotelId = obtenerHotelIdActualCompat();
            // Sin el bloque 'facturacion' el campo se ignora Y se guarda 'no'
            // en el abono: una reversión futura no debe intentar ajustar una
            // factura que nunca existió.
            $requiereFactura = ($this->puedeRegistrarFacturas() && $this->getPost('requiere_factura') === 'si') ? 'si' : 'no';
            $modoSolicitudFactura = $this->modoSolicitudFactura($this->getPost('factura_modo', 'acumular'));
            $conceptoCobro = trim((string)$this->getPost('concepto'));
            $esPagoPendiente = stripos($conceptoCobro, 'pago pendiente') !== false;
            $etiquetaCobro = $esPagoPendiente ? 'Pago pendiente' : 'Anticipo';
            $svc = new AnticipoService();
            $resultado = $svc->registrar($hotelId, $id, [
                'monto'            => $this->getPost('monto'),
                'metodo_pago'      => $this->getPost('metodo_pago'),
                'tipo_tarjeta'     => $this->getPost('tipo_tarjeta_anticipo'),
                'referencia'       => $this->getPost('referencia'),
                'concepto'         => $conceptoCobro,
                'requiere_factura' => $requiereFactura,
            ], user_id());

            $sincronizacionCxc = null;
            $advertenciaCxc = '';
            try {
                $cuentaPorCobrar = new CuentaPorCobrar();
                $sincronizacionCxc = $cuentaPorCobrar->sincronizarPagoReservacion(
                    $hotelId,
                    $id,
                    (float)$resultado['monto'],
                    'RES-ABONO-' . $id . '-' . (int)$resultado['abono_id'],
                    user_id()
                );
            } catch (Throwable $syncError) {
                error_log('No se pudo sincronizar CxC con pago de reservacion #' . $id . ': ' . $syncError->getMessage());
                $advertenciaCxc = ' No se pudo actualizar el historial anterior de cobranza; revisa el detalle de la reservacion.';
            }

            if ($requiereFactura === 'si') {
                $notaTipoTarjeta = '';
                if (($resultado['metodo_pago'] ?? '') === 'tarjeta' && !empty($resultado['tipo_tarjeta'])) {
                    $notaTipoTarjeta = ' | Tarjeta de ' . (($resultado['tipo_tarjeta'] ?? '') === 'credito' ? 'CREDITO' : 'DEBITO');
                }
                $this->reservacionModel->crearSolicitudFactura([
                    'reservacion_id'      => $id,
                    'hotel_id'            => $hotelId,
                    'requiere_factura'    => 'si',
                    'tipo'                => 'cliente',
                    'metodo_pago_principal' => $resultado['metodo_pago'],
                    'monto_total'         => $resultado['monto'],
                    'usuario_registro_id' => user_id(),
                    'notas'               => $etiquetaCobro . ' registrado - Abono #' . $resultado['abono_id'] . $notaTipoTarjeta,
                    'modo_monto'          => 'acumular',
                    'modo_solicitud'      => $modoSolicitudFactura,
                ]);
            }

            $msg = $etiquetaCobro . ' de $' . number_format($resultado['monto'], 2) .
                   ' registrado. Saldo pendiente: $' . number_format($resultado['saldo_posterior'], 2);
            if (!empty($sincronizacionCxc['cuentas_actualizadas'])) {
                $msg .= '. CxC sincronizada: $' . number_format((float)$sincronizacionCxc['monto_aplicado'], 2);
            }
            if ($requiereFactura === 'si') {
                $msg .= '. Solicitud de factura creada.';
            }
            $msg .= $advertenciaCxc;
            set_mensaje($msg, 'success');
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'registrar el cobro');
        }
        $this->redirect('reservaciones/ver/' . $id);
    }

    /**
     * Revertir un anticipo (contramovimiento de Caja + eliminar abono).
     */
    // Sin gate de modulo: revertir un anticipo ya cobrado es correccion de dinero
    // y debe seguir disponible aunque el hotel apague el bloque 'anticipos'.
    public function revertirAnticipoAction() {
        // Gate RBAC de escritura: revertir un cobro es movimiento de dinero
        // (mismo criterio que CxC: quien cobra puede revertir; caja.cobros).
        require_permission_or_403('caja.cobros', 'No tiene permiso para revertir cobros');

        $id = (int)($this->route_params['id'] ?? 0);
        if (!$this->isPost()) {
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }
        $this->validateCSRF();
        try {
            require_once __DIR__ . '/../services/AnticipoService.php';
            $hotelId = obtenerHotelIdActualCompat();
            $abonoId = (int)$this->getPost('abono_id');
            $svc = new AnticipoService();
            $resultado = $svc->reversar($hotelId, $id, $abonoId, user_id());

            // Restaurar el saldo de CxC que este pago habia liquidado via
            // sincronizarPagoReservacion (misma referencia con la que se aplico).
            $restauracionCxc = null;
            $advertenciaCxc = '';
            try {
                $cuentaPorCobrar = new CuentaPorCobrar();
                $restauracionCxc = $cuentaPorCobrar->revertirSincronizacionPagoReservacion(
                    $hotelId,
                    $id,
                    'RES-ABONO-' . $id . '-' . $abonoId,
                    user_id()
                );
            } catch (Throwable $syncError) {
                error_log('No se pudo restaurar CxC al revertir abono #' . $abonoId . ' de reservacion #' . $id . ': ' . $syncError->getMessage());
                $advertenciaCxc = ' No se pudo restaurar el historial anterior de cobranza; revisa el detalle de la reservacion.';
            }

            $advertenciaFactura = '';
            if (($resultado['requiere_factura'] ?? 'no') === 'si') {
                // El ajuste de factura no debe convertir una reversion exitosa
                // en un error: se aisla y solo genera advertencia.
                $ajusteFactura = false;
                try {
                    $ajusteFactura = $this->reservacionModel->crearSolicitudFactura([
                        'reservacion_id'      => $id,
                        'hotel_id'            => $hotelId,
                        'requiere_factura'    => 'si',
                        'tipo'                => 'cliente',
                        'metodo_pago_principal' => $resultado['metodo_pago'] ?? 'efectivo',
                        'monto_total'         => -1 * (float)($resultado['monto'] ?? 0),
                        'usuario_registro_id' => user_id(),
                        'notas'               => 'Reversion de anticipo - Abono #' . $abonoId,
                        'modo_monto'          => 'acumular',
                        'referencia_nota'     => 'Abono #' . $abonoId,
                    ]);
                } catch (Throwable $facturaError) {
                    error_log('Error al ajustar factura por reversion de abono #' . $abonoId . ': ' . $facturaError->getMessage());
                }
                if (!$ajusteFactura) {
                    $advertenciaFactura = ' La solicitud de factura no se ajusto automaticamente (puede estar ya facturada); revisala en Facturacion.';
                }
            }
            $msg = 'Anticipo revertido correctamente.';
            if (!empty($restauracionCxc['cuentas_actualizadas'])) {
                $msg .= ' Saldo pendiente restaurado: $'
                    . number_format((float)$restauracionCxc['monto_restaurado'], 2) . '.';
            }
            $msg .= $advertenciaCxc . $advertenciaFactura;
            set_mensaje($msg, 'success');
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'revertir el anticipo');
        }
        $this->redirect('reservaciones/ver/' . $id);
    }

    public function cancelarAction() {
    $id = $this->route_params['id'] ?? 0;

    // Gate RBAC de escritura: cancelar. La clave fina es reservaciones.cancelar
    // (gerencia/admin la cubren vía reservaciones.all); se acepta también
    // reservaciones.edit porque el preset de recepcionista NO incluye .cancelar
    // y recepción opera las cancelaciones hoy. Endurecer a solo .cancelar es
    // decisión de política, no de este fix.
    if (!can_any(['reservaciones.cancelar', 'reservaciones.edit'])) {
        deny_access_403('No tiene permiso para cancelar reservaciones');
    }

    if (!$this->isPost()) {
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    $this->validateCSRF();
    
    try {
        $hotel_id = obtenerHotelIdActualCompat();

        // Obtener la razón de cancelación
        $razon = trim($this->getPost('razon_cancelacion', ''));
        
        if (empty($razon)) {
            throw new Exception("Debe especificar una razón para la cancelación");
        }
        
        // Obtener información de la reservación antes de cancelar
        $reservacion = $this->reservacionModel->obtenerPorId($id);
        
        if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
            set_mensaje('Reservación no encontrada', 'error');
            $this->redirect('reservaciones');
            return;
        }
        
        // Verificar que la reservación pueda ser cancelada
        if ($reservacion['estado'] == 'checked_out') {
            throw new Exception("No se puede cancelar una reservación con check-out realizado");
        }
        
        if ($reservacion['estado'] == 'cancelada') {
            throw new Exception("Esta reservación ya está cancelada");
        }
        
        // Si esta reservación tiene pagos/anticipos cobrados en caja, hay que devolverlos:
        // se requiere caja abierta (aplica también a reservas confirmadas con anticipo,
        // no solo a las que ya tenían check-in).
        $stmt_ingresos = $this->db->prepare(
            "SELECT COUNT(*) FROM movimientos_caja
             WHERE reservacion_id = ? AND hotel_id = ? AND tipo = 'ingreso'"
        );
        $stmt_ingresos->execute([$id, $hotel_id]);
        $tiene_ingresos_cobrados = (int) $stmt_ingresos->fetchColumn() > 0;

        if ($tiene_ingresos_cobrados) {
            $corteActual = $this->cajaModel->obtenerCorteActual();
            if (!$corteActual || (int)($corteActual['hotel_id'] ?? 0) !== (int)$hotel_id) {
                set_mensaje('No se puede cancelar la reservación. Debe abrir la caja primero para procesar la devolución del anticipo/pago.', 'error');
                $this->redirect('reservaciones/ver/' . $id);
                return;
            }

            // ⚠️ Advertir si el pago original está en un corte cerrado (de otro día).
            // En ese caso se recomienda usar "Modificar días" en lugar de cancelar+recrear,
            // para que la contabilidad quede correcta por fecha.
            $stmt_check = $this->db->prepare(
                "SELECT COUNT(*) FROM movimientos_caja
                 WHERE reservacion_id = ? AND hotel_id = ? AND tipo = 'ingreso' AND corte_id != ?"
            );
            $stmt_check->execute([$id, $hotel_id, $corteActual['id']]);
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

        // NUEVO: Información sobre productos devueltos
        if (is_array($resultado) && !empty($resultado['productos_devueltos'])) {
            $mensaje .= sprintf(' Se devolvieron %d productos al inventario.',
                              count($resultado['productos_devueltos']));
        }
    }

    // Información sobre la devolución del dinero realmente cobrado (anticipos o pagos).
    // Aplica tanto a reservas con check-in como a confirmadas con anticipo.
    $total_devuelto = is_array($resultado) ? (float)($resultado['total_devuelto'] ?? 0) : 0;
    if ($total_devuelto > 0) {
        $mensaje .= sprintf(' Se registró la devolución de %s en caja.', format_money($total_devuelto));
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
        set_mensaje_error_op($e, 'completar la operación');
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }
    
    // Redireccionar al listado de reservaciones
    $this->redirect('reservaciones');
}

/**
 * Marcar una reservación como NO-SHOW (el huésped no se presentó).
 * El anticipo se RETIENE como penalización (no se devuelve).
 */
public function noShowAction() {
    $id = $this->route_params['id'] ?? 0;

    // Gate RBAC de escritura: no-show retiene el anticipo (mismo criterio que
    // cancelarAction: reservaciones.cancelar, o reservaciones.edit para recepción).
    if (!can_any(['reservaciones.cancelar', 'reservaciones.edit'])) {
        deny_access_403('No tiene permiso para marcar no-show');
    }

    if (!$this->isPost()) {
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }

    $this->validateCSRF();

    try {
        $hotel_id = obtenerHotelIdActualCompat();
        $razon = trim($this->getPost('razon_no_show', ''));
        if ($razon === '') {
            $razon = 'El huésped no se presentó (no-show).';
        }

        $reservacion = $this->reservacionModel->obtenerPorId($id);
        if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
            set_mensaje('Reservación no encontrada', 'error');
            $this->redirect('reservaciones');
            return;
        }

        if ($reservacion['estado'] !== 'confirmada') {
            set_mensaje('Solo puedes marcar "no llegó" (no-show) en reservaciones confirmadas que aún no tienen check-in.', 'error');
            $this->redirect('reservaciones/ver/' . $id);
            return;
        }

        $resultado = $this->reservacionModel->marcarNoShow($id, $razon);

        $mensaje = 'Reservación marcada: el huésped no llegó (no-show).';
        $retenido = is_array($resultado) ? (float)($resultado['total_retenido'] ?? 0) : 0;
        if ($retenido > 0) {
            $mensaje .= sprintf(' Se retuvo el anticipo de %s como penalización.', format_money($retenido));
        }
        set_mensaje($mensaje, 'success');

        error_log(sprintf(
            "Reservación #%d marcada como NO-SHOW por %s. Anticipo retenido: $%s",
            $id,
            $_SESSION['user_name'] ?? 'Desconocido',
            number_format($retenido, 2)
        ));
    } catch (Exception $e) {
        error_log("Error en noShowAction: " . $e->getMessage());
        set_mensaje_error_op($e, 'completar la operación');
        $this->redirect('reservaciones/ver/' . $id);
        return;
    }

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
    private function resolverHoraLlegadaEstimada(string $horaLlegada, string $modo): ?string {
        $modo = in_array($modo, ['manual', 'ahora', 'despues'], true) ? $modo : 'manual';

        if ($modo === 'despues') {
            return null;
        }

        if ($modo === 'ahora' && $horaLlegada === '') {
            return date('H:i');
        }

        if ($horaLlegada === '') {
            return null;
        }

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $horaLlegada)) {
            throw new Exception('La hora de llegada debe tener formato HH:MM.');
        }

        return $horaLlegada;
    }

    public function crearAction() {
    // Mismo criterio que el menu (config/navegacion.php): abrir el formulario
    // pide ver reservaciones; guardarlo exige 'reservaciones.create'.
    require_permission_or_403('reservaciones.view');

    // Obtener parámetros de preselección
    $habitacion_id = $this->getQuery('habitacion_id');
    $fecha_entrada = $this->getQuery('fecha_entrada');
    $fecha_salida = $this->getQuery('fecha_salida');
    $hora_llegada = $this->getQuery('hora_llegada');
    $es_preseleccion = $this->getQuery('preseleccion');
    
    $huesped_id = $this->getQuery('huesped_id');
    if (!$huesped_id && !empty($_SESSION['old_input']['huesped_id'])) {
        $huesped_id = $_SESSION['old_input']['huesped_id'];
    }
    $huesped_preseleccionado = null;
    
    if ($huesped_id) {
        $huesped_preseleccionado = $this->huespedModel->findForHotel($huesped_id, $this->hotelIdActual());
    }
    
    // Obtener habitaciones activas
    $habitaciones = $this->habitacionModel->where(['activa' => 1]);
    
    View::renderTemplate('reservaciones/crear', [
        'title' => 'Nueva Reservación - ' . current_hotel_display_name(),
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
    // Gate RBAC de escritura: crear reservaciones (preset recepcionista lo
    // incluye; gerencia/admin entran por reservaciones.all).
    require_permission_or_403('reservaciones.create', 'No tiene permiso para crear reservaciones');

    if (!$this->isPost()) {
        $this->redirect('reservaciones');
        return;
    }
    
    $this->validateCSRF();
    
    try {
        $horaLlegadaModo = trim((string)$this->getPost('hora_llegada_modo', 'manual'));
        $horaLlegadaPost = trim((string)$this->getPost('hora_llegada', ''));
        $horaLlegadaEstimada = $this->resolverHoraLlegadaEstimada($horaLlegadaPost, $horaLlegadaModo);

        // Recopilar datos
        // vehiculos_estimados: '' = no se preguntó (NULL); 0..9 = lo declarado al reservar
        // (alimenta la proyección de estacionamiento del dashboard).
        $vehiculosEstimadosPost = $this->getPost('vehiculos_estimados', '');
        $vehiculosEstimados = ($vehiculosEstimadosPost === '' || $vehiculosEstimadosPost === null)
            ? null
            : max(0, min(9, intval($vehiculosEstimadosPost)));

        $data = [
            'huesped_id' => intval($this->getPost('huesped_id')),
            'fecha_entrada' => $this->getPost('fecha_entrada'),
            'fecha_salida' => $this->getPost('fecha_salida'),
            'hora_llegada_estimada' => $horaLlegadaEstimada,
            'hora_entrada' => null,
            'metodo_pago' => null,
            'notas' => trim($this->getPost('notas', '')),
            'estado' => 'confirmada',
            'usuario_registro_id' => user_id(),
            'vehiculos_estimados' => $vehiculosEstimados,
            'hotel_id' => $this->hotelIdActual()
        ];

        // Descuento ajustable: el operador pudo editar/quitar el descuento en el form.
        // Si viene vacio, el modelo aplica el descuento automatico (por tipo + huesped).
        // GATE 'descuentos' (2026-07-25): sin el bloque contratado el override del
        // operador se IGNORA (no truena: Reservaciones es paquete base y el alta debe
        // seguir funcionando). Misma politica que descuentoHuespedDesdePost en
        // HuespedController: el modulo ausente descarta el campo del POST.
        $descuentoAplicadoPost = $this->puedeAplicarDescuentos()
            ? $this->getPost('descuento_aplicado', null)
            : null;
        if ($descuentoAplicadoPost !== null && $descuentoAplicadoPost !== '') {
            $data['descuento_aplicado'] = (float) str_replace(',', '', (string) $descuentoAplicadoPost);
        }
        
        // Obtener habitaciones seleccionadas
        $habitaciones_ids = $this->getPost('habitaciones', []);
        $habitaciones_ids = array_values(array_unique(array_map('intval', (array) $habitaciones_ids)));
        // Obtener habitaciones marcadas como cortesía (NUEVO)
$cortesias_ids = $this->getPost('cortesias', []);
        
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
            }
        }
        
        if (count($habitaciones) !== count($habitaciones_ids)) {
            throw new Exception('Una o mas habitaciones no pertenecen al hotel actual');
        }

        // Verificar disponibilidad
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
        
        if ($reservacion && isset($reservacion['id'])) {
            $redirectReservacion = 'reservaciones/ver/' . $reservacion['id'];

            // Anticipo inicial opcional: si el operador capturó uno, se registra como abono real.
            // Best-effort: si no hay caja abierta u ocurre un error, la reservación NO se pierde.
            $anticipoInicial = (float) str_replace(',', '', (string) $this->getPost('anticipo_inicial', ''));
            if ($anticipoInicial > 0 && function_exists('current_hotel_has_module') && !current_hotel_has_module('anticipos')) {
                $anticipoInicial = 0;
                set_mensaje('Reservación creada. El anticipo no se registró porque tu plan no incluye anticipos.', 'warning');
            }
            if ($anticipoInicial > 0) {
                try {
                    require_once __DIR__ . '/../services/AnticipoService.php';
                    $svcAnticipo = new AnticipoService();
                    $resAnticipo = $svcAnticipo->registrar((int)$this->hotelIdActual(), (int)$reservacion['id'], [
                        'monto'       => $anticipoInicial,
                        'metodo_pago' => $this->getPost('anticipo_metodo', 'efectivo'),
                        'tipo_tarjeta' => $this->getPost('tipo_tarjeta_anticipo_inicial'),
                        'concepto'    => 'Anticipo inicial',
                    ], user_id());
                    set_mensaje(
                        'Reservación creada. Anticipo de $' . number_format($resAnticipo['monto'], 2) .
                        ' registrado (saldo: $' . number_format($resAnticipo['saldo_posterior'], 2) . ').',
                        'success'
                    );
                } catch (Throwable $eAnt) {
                    set_mensaje(
                        'Reservación creada, pero el anticipo no se registró: ' . $eAnt->getMessage() .
                        ' Puedes registrarlo desde la ficha.',
                        'warning'
                    );
                }
                if ($this->isAjax()) {
                    View::renderJSON([
                        'success' => true,
                        'redirect' => url($redirectReservacion),
                    ]);
                }
                $this->redirect($redirectReservacion);
                return;
            }

            set_mensaje('Reservación creada exitosamente', 'success');
            if ($this->isAjax()) {
                View::renderJSON([
                    'success' => true,
                    'redirect' => url($redirectReservacion),
                ]);
            }
            $this->redirect($redirectReservacion);
        } else {
            throw new Exception('Error al crear la reservación');
        }

    } catch (Exception $e) {
        error_log('guardarAction: ' . $e->getMessage());
        save_old_input($_POST);
        save_form_errors($this->erroresCamposReservacionCrear($e->getMessage()));
        set_mensaje_error_op($e, 'completar la operación');
        if ($this->isAjax()) {
            View::renderJSON([
                'success' => false,
                'message' => $e->getMessage(),
                'redirect' => url('reservaciones/crear'),
            ], 422);
        }
        $this->redirect('reservaciones/crear');
    }
}
    /**
     * Generar PDF de cotización SIN guardar reservación
     * Se llama via POST desde el formulario de crear reservación
     */
    public function cotizacionPdfAction() {
        require_permission_or_403('reservaciones.view');

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
            $hora_llegada   = $this->resolverHoraLlegadaEstimada(
                trim((string)$this->getPost('hora_llegada', '')),
                trim((string)$this->getPost('hora_llegada_modo', 'manual'))
            );
            $habitaciones_ids = $this->getPost('habitaciones', []);
            $cortesias_ids  = $this->getPost('cortesias', []);
            $notas          = trim($this->getPost('notas', ''));
            // Ver gate 'descuentos' en guardarAction: sin el bloque, el override
            // manual del operador se ignora y manda el descuento automatico.
            $descuentoAplicadoPost = $this->puedeAplicarDescuentos()
                ? $this->getPost('descuento_aplicado', null)
                : null;
            $descuentoAplicadoManual = null;
            if ($descuentoAplicadoPost !== null && trim((string)$descuentoAplicadoPost) !== '') {
                $descuentoAplicadoManual = max(0, (float) str_replace(',', '', (string) $descuentoAplicadoPost));
            }

            // Validaciones básicas
            if (empty($huesped_id) || empty($fecha_entrada) || empty($fecha_salida) || empty($habitaciones_ids)) {
                die('Datos incompletos para generar la cotización.');
            }

            // Obtener datos del huésped
            $hotel_id = obtenerHotelIdActualCompat();
            $huesped = $this->huespedModel->findForHotel($huesped_id, $hotel_id);
            if (!$huesped) {
                die('Huésped no encontrado.');
            }

            // Obtener habitaciones
            $habitaciones = [];
            foreach ($habitaciones_ids as $hab_id) {
                $hab_id = (int)$hab_id;
                $hab = $this->habitacionModel->find($hab_id);
                if (!$hab || (int)($hab['hotel_id'] ?? 0) !== (int)$hotel_id) {
                    die('Habitacion no encontrada para el hotel actual.');
                }
                $habitaciones[] = $hab;
            }

            if (empty($habitaciones)) {
                die('No se encontraron las habitaciones seleccionadas.');
            }

            // Calcular precios usando el mismo método que guardar (incluye descuentos por tipo + huésped)
            $calculo = $this->reservacionModel->calcularPrecioMultiple(
                $habitaciones,
                $fecha_entrada,
                $fecha_salida,
                $hora_llegada,
                $huesped_id ?: null
            );

            // ─── Generar PDF ─────────────────────────────────────
            require_once PUBLIC_PATH . '/fdpdf/fpdf.php';

            $pdf = new FPDF('P', 'mm', 'Letter'); // 216 x 279 mm
            $pdf->SetAutoPageBreak(true, 25);
            $pdf->AddPage();

            // ─── Colores del hotel ───────────────────────────────
            $brand     = $this->cotizacionPdfBranding();
            $olivo     = $brand['primary_rgb'];
            $olivoOsc  = $brand['secondary_rgb'];
            $gold      = $brand['accent_rgb'];
            $cream     = $brand['surface_rgb'];
            $creamMid  = $brand['line_rgb'];
            $headerText = $brand['header_text_rgb'];
            $headerMuted = $brand['header_muted_rgb'];
            $goldMarca = $brand['header_accent_rgb']; // acento legible SOBRE el fondo de marca
            $tinta     = $brand['accent_ink_rgb'];    // acento legible sobre superficies claras
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
            $logoPath = $brand['logo'];
            if ($logoPath) {
                // Tarjeta blanca detrás del logo (estética sobre el fondo de marca)
                $pdf->SetFillColor(255, 255, 255);
                $pdf->Rect($margin - 1.5, 1.5, 35, 35, 'F');
                $pdf->Image($logoPath, $margin, 3, 32, 32);
            }
            $headerTitleX = $logoPath ? $margin + 36 : $margin;
            $headerTitleW = $logoPath ? 100 : 136;

            // Nombre del hotel
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
            $pdf->SetXY($headerTitleX, 8);
            $pdf->Cell($headerTitleW, 8, $u($brand['hotel']), 0, 2, 'L');

            // Subtítulo
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
            $pdf->SetX($headerTitleX);
            $pdf->Cell($headerTitleW, 5, $u('Sistema de gestión hotelera'), 0, 2, 'L');

            // Título COTIZACIÓN a la derecha
            $pdf->SetFont('Helvetica', 'B', 22);
            $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
            $pdf->SetXY($pageW - $margin - 70, 9);
            $pdf->Cell(70, 10, $u('COTIZACIÓN'), 0, 0, 'R');

            // Fecha de elaboración
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor($headerMuted[0], $headerMuted[1], $headerMuted[2]);
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

            // Caja de datos del huésped (crece si el huésped tiene vehículo(s) registrado(s))
            $vehiculoLineas = $this->cotizacionPdfVehiculos((int) $huesped_id, (int) $hotel_id);
            $huespedBoxH = 18 + (count($vehiculoLineas) * 5);

            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY = $pdf->GetY();
            $pdf->Rect($margin, $boxY, $contentW, $huespedBoxH, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY, $contentW, $huespedBoxH, 'D');

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

            // Filas siguientes: un vehículo por línea (solo campos que sí se llenaron)
            foreach ($vehiculoLineas as $i => $vehiculoLinea) {
                $pdf->SetXY($margin + 4, $boxY + 8 + (($i + 1) * 5));
                $pdf->SetFont('Helvetica', 'B', 9);
                $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
                $etiqueta = count($vehiculoLineas) > 1 ? ('Vehículo ' . ($i + 1) . ':') : 'Vehículo:';
                $pdf->Cell(26, 5, $u($etiqueta), 0, 0, 'L');
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->Cell($contentW - 34, 5, $u($vehiculoLinea), 0, 1, 'L');
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

            // Formatear fechas
            $formatFecha = function($fecha) use ($meses, $u) {
                $d = new DateTime($fecha);
                return $d->format('d') . ' de ' . $meses[$d->format('n')-1] . ' de ' . $d->format('Y');
            };

            $cotizacionConfig = $this->cotizacionPdfHotelConfig((int) $hotel_id);
            $noches = $calculo['noches'];

            // Hora de llegada capturada en el form → columna propia en la caja de estancia
            $hayHoraLlegada = ($hora_llegada !== null && $hora_llegada !== '');
            $llegadaTexto = $hayHoraLlegada
                ? $this->cotizacionPdfHoraTexto($hora_llegada)
                : 'Por confirmar';
            $llegadaSub = $hayHoraLlegada ? 'Hora indicada por el huésped' : '';

            // Caja de fechas
            $pdf->SetFillColor($cream[0], $cream[1], $cream[2]);
            $boxY2 = $pdf->GetY();
            $pdf->Rect($margin, $boxY2, $contentW, 18, 'F');
            $pdf->SetDrawColor($creamMid[0], $creamMid[1], $creamMid[2]);
            $pdf->Rect($margin, $boxY2, $contentW, 18, 'D');

            $colW = $contentW / 4;

            $pdf->SetXY($margin + 4, $boxY2 + 2);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, 'CHECK-IN', 0, 0, 'L');
            $pdf->Cell($colW, 4, 'CHECK-OUT', 0, 0, 'L');
            $pdf->Cell($colW, 4, $u('LLEGADA ESTIMADA'), 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, 'NOCHES', 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor($negro[0], $negro[1], $negro[2]);
            $pdf->Cell($colW, 5, $u($formatFecha($fecha_entrada)), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($formatFecha($fecha_salida)), 0, 0, 'L');
            $pdf->Cell($colW, 5, $u($llegadaTexto), 0, 0, 'L');
            $pdf->SetTextColor($tinta[0], $tinta[1], $tinta[2]);
            $pdf->Cell($colW - 8, 5, $noches . ' noche' . ($noches > 1 ? 's' : ''), 0, 1, 'L');

            $pdf->SetX($margin + 4);
            $pdf->SetFont('Helvetica', '', 7.5);
            $pdf->SetTextColor($gris[0], $gris[1], $gris[2]);
            $pdf->Cell($colW, 4, $u('Desde ' . $cotizacionConfig['checkin_texto']), 0, 0, 'L');
            $pdf->Cell($colW, 4, $u('Hasta ' . $cotizacionConfig['checkout_texto']), 0, 0, 'L');
            $pdf->Cell($colW, 4, $u($llegadaSub), 0, 0, 'L');
            $pdf->Cell($colW - 8, 4, '', 0, 1, 'L');

            // ═══════════════════════════════════════════════════════
            // TABLA DE HABITACIONES
            // ═══════════════════════════════════════════════════════
            $y = $boxY2 + 26;
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
            $formatTipo = function($tipo, $caracteristicas = '') {
                return function_exists('get_tipo_habitacion_real')
                    ? get_tipo_habitacion_real($tipo, $caracteristicas)
                    : ucwords(str_replace('_', ' ', (string)$tipo));
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

            // Header de la tabla (texto adaptativo: el fondo es el color de marca)
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
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
                $pdf->Cell($colTipo, 7, $u($hab['tipo_label'] ?? $formatTipo($hab['tipo'], $hab['caracteristicas'] ?? '')), 0, 0, 'C', true);
                $pdf->Cell($colPiso, 7, $u($formatPiso($hab['piso'])), 0, 0, 'C', true);
                $pdf->Cell($colPers, 7, $hab['capacidad_personas'] ?? $capacidadPorTipo($hab['tipo']), 0, 0, 'C', true);

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
                    $pdf->SetTextColor($tinta[0], $tinta[1], $tinta[2]);
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

            // Descuentos de precio (por tipo de habitación + por huésped)
            $descuento_auto = (float)($calculo['descuento_total'] ?? 0);
            $descuento_precio = $descuentoAplicadoManual !== null ? $descuentoAplicadoManual : $descuento_auto;
            $max_desc_precio = $subtotal - $descuento_cortesia;
            if ($descuento_precio > $max_desc_precio) {
                $descuento_precio = max(0, $max_desc_precio);
            }
            $descuento_manual_difiere = $descuentoAplicadoManual !== null && abs($descuento_precio - $descuento_auto) > 0.005;
            if ($descuento_auto > 0 || $descuento_precio > 0) {
                $desc_tipo = (float)($calculo['descuento_tipo'] ?? 0);
                $desc_huesped = (float)($calculo['descuento_huesped'] ?? 0);
                $pdf->SetFont('Helvetica', '', 9);
                $pdf->SetTextColor(180, 57, 43);
                if ($desc_tipo > 0) {
                    $pdf->SetX($totalesX);
                    $pdf->Cell(40, 6, $u('Descuento por tipo:'), 0, 0, 'R');
                    $pdf->Cell(40, 6, '-$' . number_format($desc_tipo, 0, '.', ',') . ' MXN', 0, 1, 'R');
                }
                if ($desc_huesped > 0) {
                    $pdf->SetX($totalesX);
                    $pdf->Cell(40, 6, $u('Descuento del huésped:'), 0, 0, 'R');
                    $pdf->Cell(40, 6, '-$' . number_format($desc_huesped, 0, '.', ',') . ' MXN', 0, 1, 'R');
                }
                if ($descuento_manual_difiere) {
                    $pdf->SetFont('Helvetica', 'B', 9);
                    $pdf->SetX($totalesX);
                    $pdf->Cell(40, 6, $u('Descuento aplicado:'), 0, 0, 'R');
                    $pdf->Cell(40, 6, '-$' . number_format($descuento_precio, 0, '.', ',') . ' MXN', 0, 1, 'R');
                    $pdf->SetFont('Helvetica', '', 9);
                }
            }

            // Línea antes del total
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.5);
            $lineY = $pdf->GetY() + 1;
            $pdf->Line($totalesX, $lineY, $totalesX + $totalesW, $lineY);
            $pdf->Ln(4);

            // TOTAL FINAL
            $totalFinal = $subtotal - $descuento_cortesia - $descuento_precio;
            $pdf->SetFillColor($olivoOsc[0], $olivoOsc[1], $olivoOsc[2]);
            $totalBoxY = $pdf->GetY();
            $pdf->Rect($totalesX - 2, $totalBoxY, $totalesW + 4, 10, 'F');

            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetTextColor($headerText[0], $headerText[1], $headerText[2]);
            $pdf->SetXY($totalesX, $totalBoxY + 1.5);
            $pdf->Cell(40, 7, 'TOTAL:', 0, 0, 'R');
            $pdf->SetTextColor($goldMarca[0], $goldMarca[1], $goldMarca[2]);
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

            $fechaEntradaTexto = $formatFecha($fecha_entrada);
            $terminos = $this->cotizacionPdfTerminos($cotizacionConfig, $fechaEntradaTexto, $brand['hotel']);
            $this->cotizacionPdfRenderTerminos($pdf, $terminos, $u, $margin, $contentW, $cream, $creamMid, $tinta, $negro);

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
            $pdf->Cell($contentW / 2, 4, $u($brand['hotel']), 0, 0, 'L');
            $pdf->Cell($contentW / 2, 4, $u('Documento generado el ' . $hoy), 0, 1, 'R');

            // ─── Output PDF ──────────────────────────────────────
            $nombreArchivo = function_exists('hotel_export_filename')
                ? hotel_export_filename('Cotizacion_Hotel', 'pdf')
                : 'Cotizacion_Hotel_' . date('Ymd_His') . '.pdf';
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
        $hotel_id = $this->hotelIdActual();

        // Bloque 'inventario' opcional: apagado, el check-in JAMÁS descuenta
        // stock aunque sobrevivan filas activas de inventario_config_habitacion
        // de cuando estuvo contratado. La devolución por cancelación no pasa
        // por aquí (Reservacion::devolverInventarioCancelacion lee
        // movimientos_inventario directo), así que el consumo histórico sigue
        // siendo reversible con el bloque apagado.
        if (function_exists('hotel_has_module') && !hotel_has_module('inventario', $hotel_id)) {
            return;
        }

        // Verificar si hay configuración de inventario activa
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM inventario_config_habitacion WHERE hotel_id = ? AND activo = 1"
        );
        $stmt->execute([$hotel_id]);
        if ((int)$stmt->fetch()['total'] === 0) {
            return;
        }

        // Obtener habitaciones de la reservación
        $stmt = $this->db->prepare(
            "SELECT rh.habitacion_id, h.numero, h.tipo
             FROM reservacion_habitaciones rh
             INNER JOIN habitaciones h ON h.id = rh.habitacion_id AND h.hotel_id = rh.hotel_id
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             WHERE rh.reservacion_id = ? AND rh.hotel_id = ? AND h.hotel_id = ? AND r.hotel_id = ?"
        );
        $stmt->execute([$reservacion_id, $hotel_id, $hotel_id, $hotel_id]);
        $habitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($habitaciones)) {
            return;
        }

        $service_path = __DIR__ . '/../services/InventarioService.php';
        if (!file_exists($service_path)) {
            $service_path_alt = __DIR__ . '/../../services/InventarioService.php';
            if (file_exists($service_path_alt)) {
                $service_path = $service_path_alt;
            } else {
                return;
            }
        }

        require_once $service_path;

        if (!class_exists('InventarioService')) {
            return;
        }

        $inventarioService = new InventarioService($this->db);
        $total_productos_descontados = 0;
        $productos_sin_stock = [];
        $resumen_descuentos = [];

        foreach ($habitaciones as $hab) {
            $resultado = $inventarioService->descontarInventarioCheckIn(
                $hab['habitacion_id'],
                $reservacion_id
            );

            if ($resultado['success']) {
                if (!empty($resultado['productos_descontados'])) {
                    $total_productos_descontados += count($resultado['productos_descontados']);
                    $resumen_descuentos[$hab['numero']] = $resultado['productos_descontados'];
                }
                if (!empty($resultado['productos_sin_stock'])) {
                    foreach ($resultado['productos_sin_stock'] as $producto) {
                        $productos_sin_stock[] = $producto['nombre'] . " (Hab. {$hab['numero']})";
                    }
                }
            }
        }

        if ($total_productos_descontados > 0) {
            $detalles = [];
            foreach ($resumen_descuentos as $productos) {
                foreach ($productos as $prod) {
                    $detalles[] = $prod['cantidad'] . ' ' . $prod['unidad'] . ' de ' . $prod['nombre'];
                }
            }
            $_SESSION['mensaje_inventario'] = 'Se descontaron del inventario: ' . implode(', ', $detalles);
            $_SESSION['tipo_mensaje'] = 'success';
        }

        if (!empty($productos_sin_stock)) {
            $aviso = 'Sin stock suficiente: ' . implode(', ', $productos_sin_stock);
            $_SESSION['mensaje_inventario'] = (($_SESSION['mensaje_inventario'] ?? '') !== ''
                ? $_SESSION['mensaje_inventario'] . ' | '
                : '') . $aviso;
            $_SESSION['tipo_mensaje'] = 'warning';
        }

    } catch (Exception $e) {
        error_log('procesarDescuentoInventario: ' . $e->getMessage());
        $_SESSION['error_inventario'] = 'No se pudo procesar el descuento automatico de inventario';
    }
}
    /**
     * Vista de calendario
     */
     
     /**
 * Check-out rápido (para llamadas AJAX desde index de habitaciones)
 */
public function checkOutRapidoAction() {
    // Gate RBAC de escritura: mismo permiso que el check-out completo.
    require_permission_or_403('habitaciones.checkout', 'No tiene permiso para registrar check-out');

    $id = $this->route_params['id'] ?? 0;

    if (!$this->isPost()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No se pudo procesar la acción. Recarga la página e intenta de nuevo.']);
        exit;
    }
    
    try {
        // Obtener datos de la reservación
        $reservacion = $this->reservacionModel->obtenerPorId($id);
        
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

        $resumenPagos = $this->reservacionModel->resumenPagos($id, $this->hotelIdActual());
        $saldoPendiente = (float)($resumenPagos['saldo'] ?? 0);
        if ($saldoPendiente > 0.004) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'saldo_pendiente' => true,
                'saldo' => $saldoPendiente,
                'message' => 'Hay un saldo pendiente de $' . number_format($saldoPendiente, 2) . '. Cobra el saldo antes de registrar la salida.',
                'url_reservacion' => function_exists('url') ? url('reservaciones/ver/' . $id) : null,
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
        $huesped = $this->huespedModel->findForHotel($reservacion['huesped_id'], $this->hotelIdActual());
        
        // Obtener habitaciones para mostrar
        $hotel_id = $this->hotelIdActual();
        $sql = "SELECT GROUP_CONCAT(h.numero ORDER BY h.numero SEPARATOR ', ') as habitaciones
                FROM reservacion_habitaciones rh
                INNER JOIN habitaciones h
                    ON rh.habitacion_id = h.id
                    AND h.hotel_id = rh.hotel_id
                WHERE rh.reservacion_id = ?
                AND rh.hotel_id = ?
                AND h.hotel_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $hotel_id, $hotel_id]);
        $habitaciones_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener hora de salida
        $hora_salida = $this->getPost('hora_salida', date('H:i:s'));

        // IDs de habitaciones (antes del check-out) para asignar responsables de limpieza.
        $habitacionIds = array_map(function($hab) {
            return (int)$hab['habitacion_id'];
        }, $habitaciones);

        // Usar el método checkOut del modelo
        $resultado = $this->reservacionModel->checkOut($id, $hora_salida);

        if ($resultado) {
            $this->procesarRecogidaLlavesCheckOut($id);
            $this->procesarRecogidaRemotosCheckOut($id);
            $this->generarTareasLimpiezaCheckOut($id, $habitacionIds, $this->obtenerAsignacionesLimpiezaPost($habitacionIds));
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
        // Gate RBAC no monetario (cerrado por defecto): calendario de reservaciones.
        require_permission_or_403('reservaciones.view');

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
        require_permission_or_403('habitaciones.checkin', 'No tiene permiso para registrar check-in');

        $id = $this->route_params['id'] ?? 0;
        
        if (!$id) {
            $_SESSION['flash_message'] = [
                'tipo' => 'error',
                'texto' => 'No encontramos esa reservación.'
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
            $reservacion = $verificacion['reservacion'] ?? $this->reservacionModel->obtenerPorId($id);
            if (!$reservacion) {
                throw new Exception('Reservación no encontrada para el hotel actual');
            }
            $huesped = $this->huespedModel->findForHotel($reservacion['huesped_id'], $this->hotelIdActual());
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
        // Gate RBAC de escritura: check-in tardío/express también cobra
        // (mismo permiso que checkInAction).
        require_permission_or_403('habitaciones.checkin', 'No tiene permiso para registrar check-in');

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
                    $reservacion = $this->reservacionModel->obtenerPorId($id);
                    if (!$reservacion) {
                        throw new Exception('Reservación no encontrada para el hotel actual');
                    }
                    $pagos_tardio = $this->obtenerPagosDelPost();
                    $this->procesarSolicitudFactura($id, $pagos_tardio, $this->sumarMontoPagos($pagos_tardio));
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
                    $reservacion = $this->reservacionModel->obtenerPorId($id);
                    if (!$reservacion) {
                        throw new Exception('Reservación no encontrada para el hotel actual');
                    }
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
                    $reservacion_express = $this->reservacionModel->obtenerPorId($id);
                    if (!$reservacion_express) {
                        throw new Exception('Reservación no encontrada para el hotel actual');
                    }
                    $this->procesarSolicitudFactura($id, $pagos, $this->sumarMontoPagos($pagos));
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
        require_permission_or_403('habitaciones.checkin', 'No tiene permiso para registrar check-in');

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
    
    private function sumarMontoPagos(array $pagos): float {
        $total = 0.0;
        foreach ($pagos as $pago) {
            $total += (float)($pago['monto'] ?? 0);
        }
        return round($total, 2);
    }

    private function modoSolicitudFactura($valor): string {
        return trim((string)$valor) === 'separada' ? 'separada' : 'actualizar';
    }

    /**
     * Procesar solicitud de factura durante el check-in
     * 
     * Lógica:
     * - Cliente QUIERE factura → Siempre crear solicitud tipo 'cliente'
     * - Cliente NO quiere factura + SOLO efectivo → NO crear nada
     * - Cliente NO quiere factura + tarjeta/transferencia → Crear solicitud tipo 'uso_interno'
     */
    private function procesarSolicitudFactura($reservacion_id, $pagos, $monto_total, $requiere_factura = null, string $modoMonto = 'acumular', ?string $modoSolicitud = null) {
        try {
            // Sin el bloque 'facturacion' no se crea NINGUNA solicitud (ni de
            // uso interno), aunque un POST artesanal mande requiere_factura.
            if (!$this->puedeRegistrarFacturas()) {
                return;
            }

            $monto_total = round((float)$monto_total, 2);
            if ($monto_total <= 0.004) {
                return;
            }

            // Si no se pasó como parámetro, intentar leerlo del POST (check-in normal usa form POST)
            if ($requiere_factura === null) {
                $requiere_factura = $this->getPost('requiere_factura', '');
            }
            
            // Si no se indicó, no hacer nada (no debería pasar por la validación JS)
            if (empty($requiere_factura)) {
                return;
            }
            $modoSolicitud = $modoSolicitud !== null
                ? $this->modoSolicitudFactura($modoSolicitud)
                : $this->modoSolicitudFactura($this->getPost('factura_modo', 'acumular'));

            $hotel_id = obtenerHotelIdActualCompat();
            $reservacion = $this->reservacionModel->obtenerPorId($reservacion_id);

            if (!$reservacion || (int)$reservacion['hotel_id'] !== (int)$hotel_id) {
                throw new Exception('Reservación no encontrada para el hotel actual');
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
                    'hotel_id' => $hotel_id,
                    'requiere_factura' => 'si',
                    'tipo' => 'cliente',
                    'estatus' => 'pendiente',
                    'metodo_pago_principal' => $metodo_principal,
                    'monto_total' => $monto_total,
                    'usuario_registro_id' => $usuario_id,
                    'modo_monto' => $modoMonto,
                    'modo_solicitud' => $modoSolicitud,
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
                        'hotel_id' => $hotel_id,
                        'requiere_factura' => 'no',
                        'tipo' => 'uso_interno',
                        'estatus' => 'pendiente',
                        'metodo_pago_principal' => $metodo_principal,
                        'monto_total' => $monto_total,
                        'usuario_registro_id' => $usuario_id,
                        'modo_monto' => $modoMonto,
                        'modo_solicitud' => $modoSolicitud,
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
        // Gate RBAC de escritura: reescribe movimientos de caja (caja.cobros).
        require_permission_or_403('caja.cobros', 'No tiene permiso para modificar cobros');

        $id = $this->route_params['id'] ?? 0;

        header('Content-Type: application/json');
        
        if (!$this->isPost()) {
            echo json_encode(['success' => false, 'message' => 'No se pudo procesar la acción. Recarga la página e intenta de nuevo.']);
            exit;
        }
        
        try {
            $hotel_id = obtenerHotelIdActualCompat();

            // Leer JSON del body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['pagos'])) {
                echo json_encode(['success' => false, 'message' => 'Datos de pago no proporcionados']);
                exit;
            }
            
            // Verificar que la reservación existe y está en check-in
            $reservacion = $this->reservacionModel->obtenerPorId($id);
            if (!$reservacion || (int)$reservacion['hotel_id'] !== (int)$hotel_id) {
                echo json_encode(['success' => false, 'message' => 'Reservación no encontrada']);
                exit;
            }
            
            if ($reservacion['estado'] !== 'checked_in') {
                echo json_encode(['success' => false, 'message' => 'Solo se puede cambiar el método de pago en reservaciones con check-in']);
                exit;
            }
            
            $pagos = $input['pagos'];

            // Validar método contra el enum real (mismo whitelist que el loop
            // fijo de checkInAction y MovimientoCaja::validarMovimiento). En
            // MySQL no estricto un valor inválido quedaría '' en
            // movimientos_caja.metodo_pago y el arqueo lo descartaría en
            // silencio. Se RECHAZA, no se corrige.
            $metodos_validos = ['efectivo', 'tarjeta', 'transferencia'];
            foreach ($pagos as $pago) {
                if (!is_array($pago) || !in_array($pago['metodo'] ?? '', $metodos_validos, true)) {
                    echo json_encode(['success' => false, 'message' => 'Método de pago inválido']);
                    exit;
                }
            }

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

            if ((int)$corteActual['hotel_id'] !== (int)$hotel_id) {
                echo json_encode(['success' => false, 'message' => 'El corte activo no pertenece al hotel actual']);
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

            // Candado anti-TOCTOU (patrón MovimientoCaja::registrarMovimiento /
            // Caja::cerrarCaja): bloquear el corte FOR UPDATE y revalidar que
            // siga abierto antes de reescribir movimientos, para no insertar en
            // un corte que se está cerrando en paralelo (arqueo ya congelado).
            $stmt_lock = $this->db->prepare(
                "SELECT estado FROM cortes_caja WHERE id = ? AND hotel_id = ? FOR UPDATE"
            );
            $stmt_lock->execute([$corteActual['id'], $hotel_id]);
            $corte_lock = $stmt_lock->fetch(PDO::FETCH_ASSOC);
            if (!$corte_lock || ($corte_lock['estado'] ?? '') !== 'abierto') {
                throw new Exception('La caja se cerró: recarga antes de cambiar el método de pago');
            }

            // 1. Actualizar método de pago en reservaciones
            $sql = "UPDATE reservaciones SET metodo_pago = ? WHERE id = ? AND hotel_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$metodo_principal, $id, $hotel_id]);
            
            // 2. Eliminar pagos anteriores de reservacion_pagos
            $sql_check = "SHOW TABLES LIKE 'reservacion_pagos'";
            $result = $this->db->query($sql_check);
            
            if ($result && $result->fetch()) {
                $sql = "DELETE FROM reservacion_pagos WHERE reservacion_id = ? AND hotel_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id, $hotel_id]);
                
                // 3. Insertar nuevos pagos (tipo_tarjeta solo si la migracion ya agrego la columna)
                $chk_tipo = $this->db->query("SHOW COLUMNS FROM reservacion_pagos LIKE 'tipo_tarjeta'");
                $con_tipo_tarjeta = $chk_tipo && $chk_tipo->fetch();
                $sql = $con_tipo_tarjeta
                    ? "INSERT INTO reservacion_pagos (reservacion_id, hotel_id, metodo_pago, monto, referencia, tipo_tarjeta, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"
                    : "INSERT INTO reservacion_pagos (reservacion_id, hotel_id, metodo_pago, monto, referencia, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                foreach ($pagos as $pago) {
                    if (floatval($pago['monto']) > 0) {
                        $params = [
                            $id,
                            $hotel_id,
                            $pago['metodo'],
                            floatval($pago['monto']),
                            $pago['referencia'] ?? null
                        ];
                        if ($con_tipo_tarjeta) {
                            $tipo_tarjeta = strtolower(trim((string)($pago['tipo_tarjeta'] ?? '')));
                            if (($pago['metodo'] ?? '') !== 'tarjeta' || !in_array($tipo_tarjeta, ['credito', 'debito'], true)) {
                                $tipo_tarjeta = '';
                            }
                            $params[] = $tipo_tarjeta;
                        }
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute($params);
                    }
                }
            }
            
            // 4. Actualizar movimientos_caja - SOLO si pertenecen al corte actual (abierto)
            //    Si pertenecen a un corte cerrado, NO se tocan para no descuadrar cortes anteriores.
            
            // Obtener categoría de hospedaje (del hotel actual)
            $sql_cat = "SELECT id FROM categorias_movimientos WHERE nombre = 'Hospedaje' AND tipo = 'ingreso' AND activa = 1 AND hotel_id = ? LIMIT 1";
            $stmt_cat = $this->db->prepare($sql_cat);
            $stmt_cat->execute([$hotel_id]);
            $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
            $categoria_id = $categoria ? $categoria['id'] : null;
            
            // Verificar en qué corte están los movimientos actuales de esta reservación
            $sql_check_corte = "SELECT mc.id, mc.corte_id, cc.estado as corte_estado, c.id as caja_id
                                FROM movimientos_caja mc
                                INNER JOIN reservaciones r
                                    ON mc.reservacion_id = r.id
                                    AND mc.hotel_id = r.hotel_id
                                LEFT JOIN cortes_caja cc ON mc.corte_id = cc.id AND cc.hotel_id = mc.hotel_id
                                LEFT JOIN cajas c ON cc.caja_id = c.id AND c.hotel_id = cc.hotel_id
                                WHERE mc.reservacion_id = ?
                                AND mc.hotel_id = ?
                                AND r.hotel_id = ?
                                AND mc.created_at >= r.created_at
                                AND mc.categoria = 'Hospedaje'
                                AND mc.tipo = 'ingreso'";
            $stmt_check = $this->db->prepare($sql_check_corte);
            $stmt_check->execute([$id, $hotel_id, $hotel_id]);
            $movimientos_existentes = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
            
            $hay_movimientos_en_corte_cerrado = false;
            if (!empty($movimientos_existentes)) {
                foreach ($movimientos_existentes as $mov) {
                    if ($mov['corte_id'] != $corteActual['id'] || !$mov['corte_estado'] || !$mov['caja_id']) {
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
                $sql = "DELETE FROM movimientos_caja WHERE reservacion_id = ? AND hotel_id = ? AND categoria = 'Hospedaje' AND created_at >= ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id, $hotel_id, $reservacion['created_at'] ?? '1970-01-01 00:00:00']);
                
                // Insertar nuevos movimientos en el corte actual
                foreach ($pagos as $pago) {
                    if (floatval($pago['monto']) > 0) {
                        $sql = "INSERT INTO movimientos_caja 
                                (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                                 referencia, reservacion_id, usuario_id, corte_id, created_at) 
                                VALUES (?, 'ingreso', 'Hospedaje', ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $descripcion = "Hospedaje - Reservación #" . $id . " (Cambio de método)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            $hotel_id,
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
                
                $this->procesarSolicitudFactura($id, $pagos_para_factura, $total, $requiere_factura, 'reemplazar');
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
        require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

        header('Content-Type: application/json');

        $reservacion_id      = intval($this->getPost('reservacion_id'));
        $nueva_fecha_salida  = trim($this->getPost('nueva_fecha_salida', ''));
        $nueva_fecha_entrada = trim($this->getPost('nueva_fecha_entrada', ''));

        if (!$reservacion_id || !$nueva_fecha_salida) {
            echo json_encode(['disponible' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        try {
            $model = new Reservacion();
            $hotel_id = obtenerHotelIdActualCompat();

            $reservacion = $model->obtenerPorId($reservacion_id);

            if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
                echo json_encode(['disponible' => false, 'mensaje' => 'Reservación no encontrada.']);
                return;
            }

            if (!in_array($reservacion['estado'], ['confirmada', 'checked_in'])) {
                echo json_encode(['disponible' => false, 'mensaje' => 'La reservación no se puede modificar en su estado actual.']);
                return;
            }

            // El check-in solo se puede desplazar en reservaciones confirmadas (antes de la llegada).
            $entrada_efectiva = $reservacion['fecha_entrada'];
            if ($nueva_fecha_entrada !== '' && $nueva_fecha_entrada !== $reservacion['fecha_entrada']) {
                if ($reservacion['estado'] !== 'confirmada') {
                    echo json_encode(['disponible' => false, 'mensaje' => 'La fecha de llegada solo se puede cambiar antes del check-in.']);
                    return;
                }
                $entrada_efectiva = $nueva_fecha_entrada;
            }

            $fecha_entrada   = new DateTime($entrada_efectiva);
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
                $entrada_efectiva,
                $nueva_fecha_salida,
                $reservacion_id
            );

            if (!$disponible) {
                echo json_encode(['disponible' => false, 'mensaje' => 'Una o más habitaciones no están disponibles en las nuevas fechas.']);
                return;
            }

            $calculo = $model->calcularPrecioTotal(
                $habitacion_ids,
                $entrada_efectiva,
                $nueva_fecha_salida
            );

            echo json_encode([
                'disponible'    => true,
                'nuevo_precio'  => $calculo['precio_total'],
                'noches'        => $calculo['noches'],
                'fecha_entrada' => $entrada_efectiva,
                'fecha_salida'  => $nueva_fecha_salida
            ]);

        } catch (Exception $e) {
            error_log("Error verificarModificarDias: " . $e->getMessage());
            echo json_encode(['disponible' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
        }
    }

    /**
     * Calcula el tope de días al que se puede extender una reservación sin
     * pisar la siguiente reserva de cualquiera de sus habitaciones (AJAX).
     * Devuelve: max_noches, max_checkout, y datos de la reserva que limita.
     */
    public function topeModificarDiasAction() {
        require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

        header('Content-Type: application/json');

        $reservacion_id = intval($this->getPost('reservacion_id'));
        if (!$reservacion_id) {
            echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        try {
            $model    = new Reservacion();
            $db       = Database::getInstance();
            $hotel_id = obtenerHotelIdActualCompat();

            $reservacion = $model->obtenerPorId($reservacion_id);
            if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
                echo json_encode(['ok' => false, 'mensaje' => 'Reservación no encontrada.']);
                return;
            }

            $habitacion_ids = $model->obtenerHabitacionIds($reservacion_id);
            if (empty($habitacion_ids)) {
                echo json_encode(['ok' => false, 'mensaje' => 'Sin habitaciones.']);
                return;
            }

            $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
            $fecha_salida  = new DateTime($reservacion['fecha_salida']);
            // Tope por defecto: 365 noches desde la entrada (sin reservas que limiten).
            $cap_checkout  = (clone $fecha_entrada)->modify('+365 days');

            // Próxima reserva (confirmada / check-in) de cualquiera de estas
            // habitaciones que empiece en o después de la salida actual.
            $placeholders = implode(',', array_fill(0, count($habitacion_ids), '?'));
            $params = $habitacion_ids;
            $params[] = $hotel_id;
            $params[] = $reservacion_id;
            $params[] = $reservacion['fecha_salida'];

            $stmt = $db->query(
                "SELECT r.id, r.fecha_entrada, r.fecha_salida, r.estado,
                        h.numero AS habitacion_numero, hu.nombre_completo AS huesped_nombre
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh
                     ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 LEFT JOIN habitaciones h
                     ON h.id = rh.habitacion_id AND h.hotel_id = rh.hotel_id
                 LEFT JOIN huespedes hu
                     ON hu.id = r.huesped_id
                 WHERE rh.habitacion_id IN ($placeholders)
                   AND r.hotel_id = ?
                   AND r.id != ?
                   AND r.estado IN ('confirmada', 'checked_in')
                   AND r.fecha_entrada >= ?
                 ORDER BY r.fecha_entrada ASC, r.id ASC
                 LIMIT 1",
                $params
            );
            $proxima = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;

            if ($proxima && !empty($proxima['fecha_entrada'])) {
                $max_checkout = new DateTime($proxima['fecha_entrada']);
            } else {
                $max_checkout = $cap_checkout;
            }

            // Seguridad: el tope nunca puede ser menor a la salida actual.
            if ($max_checkout < $fecha_salida) {
                $max_checkout = clone $fecha_salida;
            }

            $max_noches = (int)$fecha_entrada->diff($max_checkout)->days;
            if ($max_noches < 1) {
                $max_noches = 1;
            }

            // Reserva previa (confirmada / check-in) de cualquiera de estas
            // habitaciones que termine en o antes de la entrada actual: marca el
            // día más temprano al que se puede adelantar el check-in.
            $params_prev = $habitacion_ids;
            $params_prev[] = $hotel_id;
            $params_prev[] = $reservacion_id;
            $params_prev[] = $reservacion['fecha_entrada'];

            $stmt_prev = $db->query(
                "SELECT r.id, r.fecha_salida, h.numero AS habitacion_numero
                 FROM reservaciones r
                 INNER JOIN reservacion_habitaciones rh
                     ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                 LEFT JOIN habitaciones h
                     ON h.id = rh.habitacion_id AND h.hotel_id = rh.hotel_id
                 WHERE rh.habitacion_id IN ($placeholders)
                   AND r.hotel_id = ?
                   AND r.id != ?
                   AND r.estado IN ('confirmada', 'checked_in')
                   AND r.fecha_salida <= ?
                 ORDER BY r.fecha_salida DESC, r.id DESC
                 LIMIT 1",
                $params_prev
            );
            $previa = $stmt_prev ? $stmt_prev->fetch(PDO::FETCH_ASSOC) : null;
            $min_entrada = ($previa && !empty($previa['fecha_salida'])) ? $previa['fecha_salida'] : null;

            echo json_encode([
                'ok'                 => true,
                'estado'             => $reservacion['estado'],
                'fecha_entrada'      => $reservacion['fecha_entrada'],
                'fecha_salida'       => $reservacion['fecha_salida'],
                'max_noches'         => $max_noches,
                'max_checkout'       => $max_checkout->format('Y-m-d'),
                'min_entrada'        => $min_entrada,
                'previa_habitacion'  => $previa['habitacion_numero'] ?? null,
                'tiene_limite'       => $proxima ? true : false,
                'proxima_reserva_id' => $proxima ? (int)$proxima['id'] : null,
                'proxima_entrada'    => $proxima['fecha_entrada'] ?? null,
                'proxima_salida'     => $proxima['fecha_salida'] ?? null,
                'proxima_habitacion' => $proxima['habitacion_numero'] ?? null,
                'proxima_huesped'    => $proxima['huesped_nombre'] ?? null,
                'proxima_estado'     => $proxima['estado'] ?? null,
            ]);

        } catch (Exception $e) {
            error_log("Error topeModificarDias: " . $e->getMessage());
            echo json_encode(['ok' => false, 'mensaje' => 'Error interno.']);
        }
    }

    /**
     * Aplicar el cambio de días de la reservación (AJAX)
     */
    public function modificarDiasAction() {
        // Gate RBAC de escritura: editar la estancia (reservaciones.edit, que
        // recepcionista tiene; gerencia/admin entran por reservaciones.all).
        require_permission_or_403('reservaciones.edit', 'No tiene permiso para editar reservaciones');

        header('Content-Type: application/json');

        $reservacion_id      = intval($this->getPost('reservacion_id'));
        $nueva_fecha_salida  = trim($this->getPost('nueva_fecha_salida', ''));
        $nueva_fecha_entrada = trim($this->getPost('nueva_fecha_entrada', ''));

        if (!$reservacion_id || !$nueva_fecha_salida) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos.']);
            return;
        }

        try {
            $model = new Reservacion();
            $db = Database::getInstance();
            $hotel_id = obtenerHotelIdActualCompat();

            $reservacion = $model->obtenerPorId($reservacion_id);

            if (!$reservacion || (int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
                echo json_encode(['success' => false, 'mensaje' => 'Reservación no encontrada.']);
                return;
            }

            if (!in_array($reservacion['estado'], ['confirmada', 'checked_in'])) {
                echo json_encode(['success' => false, 'mensaje' => 'Estado inválido para modificar días.']);
                return;
            }

            // El check-in solo se puede desplazar en reservaciones confirmadas.
            $entrada_efectiva = $reservacion['fecha_entrada'];
            $cambia_entrada   = false;
            if ($nueva_fecha_entrada !== '' && $nueva_fecha_entrada !== $reservacion['fecha_entrada']) {
                if ($reservacion['estado'] !== 'confirmada') {
                    echo json_encode(['success' => false, 'mensaje' => 'La fecha de llegada solo se puede cambiar antes del check-in.']);
                    return;
                }
                $entrada_efectiva = $nueva_fecha_entrada;
                $cambia_entrada   = true;
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

                if ((int)($corteActual['hotel_id'] ?? 0) !== (int)$hotel_id) {
                    echo json_encode(['success' => false, 'mensaje' => 'El corte abierto no pertenece al hotel actual.']);
                    return;
                }
            }

            $fecha_entrada   = new DateTime($entrada_efectiva);
            $nueva_salida_dt = new DateTime($nueva_fecha_salida);

            if ($nueva_salida_dt <= $fecha_entrada) {
                echo json_encode(['success' => false, 'mensaje' => 'La fecha de salida debe ser posterior a la entrada.']);
                return;
            }

            $habitacion_ids = $model->obtenerHabitacionIds($reservacion_id);

            $disponible = $model->verificarDisponibilidadMultipleExcluyendo(
                $habitacion_ids,
                $entrada_efectiva,
                $nueva_fecha_salida,
                $reservacion_id
            );

            if (!$disponible) {
                echo json_encode(['success' => false, 'mensaje' => 'Conflicto de disponibilidad al guardar.']);
                return;
            }

            $calculo = $model->calcularPrecioTotal(
                $habitacion_ids,
                $entrada_efectiva,
                $nueva_fecha_salida
            );

            $precio_anterior = floatval($reservacion['precio_total']);
            $precio_nuevo    = floatval($calculo['precio_total']);
            $diferencia      = round($precio_nuevo - $precio_anterior, 2);
            $resumenAntes = $model->resumenPagos($reservacion_id, $hotel_id);
            $pagado_antes = (float)($resumenAntes['pagado'] ?? 0);

            $db->beginTransaction();

            if ($cambia_entrada) {
                $resultado = $model->modificarFechas(
                    $reservacion_id,
                    $entrada_efectiva,
                    $nueva_fecha_salida,
                    $calculo['precio_total'],
                    $calculo['desglose'] ?? []
                );
            } else {
                $resultado = $model->modificarFechaSalida(
                    $reservacion_id,
                    $nueva_fecha_salida,
                    $calculo['precio_total'],
                    $calculo['desglose'] ?? []
                );
            }

            if (!$resultado) {
                if ($db->enTransaccion()) {
                    $db->rollBack();
                }
                echo json_encode(['success' => false, 'mensaje' => 'Error al guardar en la base de datos.']);
                return;
            }

            // ========== AJUSTE DE CAJA ==========
            // Solo si la reservación ya tiene check-in (ya se cobró)
            $ajuste_caja = null;
            $saldo_pendiente_actual = null;
            if ($reservacion['estado'] === 'checked_in' && $diferencia != 0 && $corteActual) {
                if ((int)($reservacion['hotel_id'] ?? 0) !== (int)$hotel_id) {
                    if ($db->enTransaccion()) {
                        $db->rollBack();
                    }
                    echo json_encode(['success' => false, 'mensaje' => 'Reservacion no encontrada.']);
                    return;
                }

                if ((int)($corteActual['hotel_id'] ?? 0) !== (int)$hotel_id) {
                    if ($db->enTransaccion()) {
                        $db->rollBack();
                    }
                    echo json_encode(['success' => false, 'mensaje' => 'El corte abierto no pertenece al hotel actual.']);
                    return;
                }

                // Candado anti-TOCTOU (patrón MovimientoCaja::registrarMovimiento):
                // bloquear el corte FOR UPDATE y revalidar que siga abierto antes
                // de registrar la devolución en movimientos_caja.
                $stmt_lock = $db->query(
                    "SELECT estado FROM cortes_caja WHERE id = ? AND hotel_id = ? FOR UPDATE",
                    [$corteActual['id'], $hotel_id]
                );
                $corte_lock = $stmt_lock ? $stmt_lock->fetch() : null;
                if (!$corte_lock || ($corte_lock['estado'] ?? '') !== 'abierto') {
                    if ($db->enTransaccion()) {
                        $db->rollBack();
                    }
                    echo json_encode(['success' => false, 'mensaje' => 'La caja se cerró: recarga antes de modificar los días.']);
                    return;
                }

                $usuario_id = user_id();

                // Obtener el método de pago principal de la reservación
                $stmt_mp = $db->query(
                    "SELECT metodo_pago FROM reservaciones WHERE id = ? AND hotel_id = ?",
                    [$reservacion_id, $hotel_id]
                );
                $row_mp = $stmt_mp->fetch();
                $metodo_pago = $row_mp ? $row_mp['metodo_pago'] : 'efectivo';

                if ($diferencia < 0) {
                    // ─── REDUCCIÓN DE DÍAS → DEVOLUCIÓN (gasto) ───
                    $monto_devolver = round(max(0, min(abs($diferencia), $pagado_antes - $precio_nuevo)), 2);

                    if ($monto_devolver <= 0.004) {
                        error_log("Reduccion de dias sin devolucion: pagado $" . $pagado_antes . ", nuevo total $" . $precio_nuevo . " - Reservacion #" . $reservacion_id);
                    } else {

                        // Obtener o crear categoría de Devoluciones (del hotel actual).
                        // 'gasto' es el valor válido del enum; 'egreso' insertaba '' y duplicaba.
                        $stmt_cat = $db->query(
                            "SELECT id FROM categorias_movimientos
                             WHERE nombre = 'Devoluciones' AND tipo = 'gasto' AND activa = 1
                               AND hotel_id = ?
                             LIMIT 1",
                            [$hotel_id]
                        );
                        $cat = $stmt_cat->fetch();

                        if (!$cat) {
                            $db->query(
                                "INSERT INTO categorias_movimientos
                                 (hotel_id, nombre, tipo, descripcion, icono, color, activa, created_at)
                                 VALUES (?, 'Devoluciones', 'gasto', 'Devoluciones por ajustes',
                                         'fas fa-undo', '#EF4444', 1, NOW())",
                                [$hotel_id]
                            );
                            $categoria_id = $db->lastInsertId();
                        } else {
                            $categoria_id = $cat['id'];
                        }

                        $descripcion = "Devolución por reducción de días - Reservación #" . $reservacion_id .
                                       " (" . $precio_anterior . " → " . $precio_nuevo . ")";

                        $stmtMov = $db->query(
                            "INSERT INTO movimientos_caja
                             (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago,
                               referencia, reservacion_id, usuario_id, corte_id, created_at)
                             VALUES (?, 'gasto', 'Devoluciones', ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                            [
                                $hotel_id,
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

                        if (!$stmtMov || !$model->registrarAjustePagoPorDevolucion($reservacion_id, $monto_devolver, $metodo_pago, $hotel_id)) {
                            throw new Exception('No se pudo registrar la devolucion de la reservacion.');
                        }

                        $ajuste_caja = [
                            'tipo' => 'devolucion',
                            'monto' => $monto_devolver,
                            'metodo_pago' => $metodo_pago
                        ];

                        error_log("Devolucion registrada: $" . $monto_devolver . " (" . $metodo_pago . ") - Reservacion #" . $reservacion_id);
                    }

                } else {
                    error_log("Extension de dias genero saldo pendiente: $" . $diferencia . " - Reservacion #" . $reservacion_id);
                }
            }
            // ========== FIN AJUSTE DE CAJA ==========

            try {
                $resumenActualizado = $model->resumenPagos($reservacion_id, $hotel_id);
                $saldo_pendiente_actual = (float)($resumenActualizado['saldo'] ?? 0);
            } catch (Throwable $e) {
                $saldo_pendiente_actual = max(0, round($precio_nuevo - $precio_anterior, 2));
            }

            if ($db->enTransaccion()) {
                $db->commit();
            }

            echo json_encode([
                'success'            => true,
                'nuevo_precio'       => $calculo['precio_total'],
                'nueva_fecha'        => $nueva_fecha_salida,
                'nueva_fecha_entrada'=> $entrada_efectiva,
                'noches'             => $calculo['noches'],
                'diferencia'         => $diferencia,
                'saldo_pendiente'    => $saldo_pendiente_actual,
                'requiere_cobro'     => ($diferencia > 0.004 && $saldo_pendiente_actual > 0.004),
                'ajuste_caja'        => $ajuste_caja
            ]);

        } catch (Exception $e) {
            if (isset($db) && $db instanceof Database && $db->enTransaccion()) {
                $db->rollBack();
            }
            error_log("Error modificarDias: " . $e->getMessage());
            echo json_encode(['success' => false, 'mensaje' => 'Error interno: ' . $e->getMessage()]);
        }
    }
}
