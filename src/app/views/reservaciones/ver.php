<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista de Detalle de Reservación - Diseño Moderno y Colorido
 * Vista hotelera
 */

$estado_info = $estados[$reservacion['estado']] ?? ['label' => 'Desconocido', 'color' => 'gray'];
$pagos = $pagos ?? [];
$remotos_info = is_array($remotos_info ?? null) ? $remotos_info : [];
$ticketBranding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
if (!is_array($ticketBranding)) {
    $ticketBranding = [];
}

$nombreHotelVisible = function_exists('current_hotel_display_name')
    ? current_hotel_display_name('Medisoft Hoteles')
    : ((function_exists('current_hotel_nombre') && current_hotel_nombre()) ? current_hotel_nombre() : 'Medisoft Hoteles');
$nombreHotelTicket = function_exists('mb_strtoupper')
    ? mb_strtoupper($nombreHotelVisible, 'UTF-8')
    : strtoupper($nombreHotelVisible);

$parkingIconByCode = [
    'coches' => 'fa-car',
    'camionetas' => 'fa-truck',
    'discos' => 'fa-compact-disc',
    'nikkos' => 'fa-star',
];
$parkingClassByCode = [
    'coches' => 'ubicacion-coches',
    'camionetas' => 'ubicacion-camionetas',
    'discos' => 'ubicacion-discos',
    'nikkos' => 'ubicacion-nikkos',
];
$reservationParkingMap = [];
if (function_exists('hotel_general_catalog_parking_rows')) {
    foreach (hotel_general_catalog_parking_rows(null, true) as $parkingRow) {
        $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
        $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
        if ($parkingCode === '' || $parkingLabel === '') {
            continue;
        }

        $reservationParkingMap[$parkingCode] = [
            'label' => $parkingLabel,
            'icon' => $parkingIconByCode[$parkingCode] ?? 'fa-square-parking',
            'class' => $parkingClassByCode[$parkingCode] ?? 'ubicacion-catalogo',
        ];
    }
}
if (empty($reservationParkingMap)) {
    $reservationParkingMap = [
        'coches' => ['label' => 'Coches', 'icon' => 'fa-car', 'class' => 'ubicacion-coches'],
    ];
}

$ticketLogoDataUri = null;
$ticketLogoAssetUrl = null;
$ticketLogoPathToDataUri = function($path) {
    $path = trim((string) $path);
    if ($path === '' || preg_match('/[\x00-\x1F<>"\']/', $path)) {
        return null;
    }

    $urlPath = parse_url($path, PHP_URL_PATH);
    if (!$urlPath || !defined('PUBLIC_PATH')) {
        return null;
    }

    $cleanPath = ltrim($urlPath, '/');
    $allowedPrefixes = ['uploads/branding/', 'uploads/', 'img/'];
    $allowed = false;
    foreach ($allowedPrefixes as $prefix) {
        if (strpos($cleanPath, $prefix) === 0) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        return null;
    }

    $publicRoot = realpath(PUBLIC_PATH);
    $realPath = realpath(PUBLIC_PATH . '/' . $cleanPath);
    if (!$publicRoot || !$realPath || strpos($realPath, $publicRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($realPath)) {
        return null;
    }

    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    $mimeByExtension = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
    ];
    if (!isset($mimeByExtension[$extension])) {
        return null;
    }

    $contents = @file_get_contents($realPath);
    return $contents !== false
        ? 'data:' . $mimeByExtension[$extension] . ';base64,' . base64_encode($contents)
        : null;
};

$ticketLogoCandidates = [
    $ticketBranding['logo_url'] ?? null,
    function_exists('hotel_branding_default_logo_path') ? hotel_branding_default_logo_path() : 'img/logo.png',
];
foreach ($ticketLogoCandidates as $ticketLogoCandidate) {
    $ticketLogoDataUri = $ticketLogoPathToDataUri($ticketLogoCandidate);
    if ($ticketLogoDataUri) {
        break;
    }
}
if (function_exists('hotel_branding_asset_url')) {
    $ticketLogoAssetUrl = hotel_branding_asset_url($ticketBranding['logo_url'] ?? null)
        ?: (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : null);
}
$ticketLogoSrc = $ticketLogoDataUri ?: ($ticketLogoAssetUrl ?: '');

// Detectar si acaba de hacerse un check-in exitoso (pantalla de éxito + auto-descarga de ticket).
// El header ya consumió el flash para el toast; toast.php lo expone en $GLOBALS['ms_flash_consumido'].
$auto_imprimir_ticket = false;
$rdCheckinFlashTexto = null;
$rdFlashConsumido = $GLOBALS['ms_flash_consumido'] ?? ($_SESSION['flash_message'] ?? null);
if (is_array($rdFlashConsumido) &&
    ($rdFlashConsumido['tipo'] ?? '') === 'success' &&
    stripos((string)($rdFlashConsumido['texto'] ?? ''), 'Check-in') !== false) {
    $auto_imprimir_ticket = true;
    $rdCheckinFlashTexto = (string)$rdFlashConsumido['texto'];
}

$rdHabitacionesTexto = implode(', ', array_filter(array_map(function($habitacion) {
    return (string)($habitacion['numero'] ?? '');
}, $habitaciones ?? [])));
$rdHuespedNombre = trim((string)($huesped['nombre_completo'] ?? 'Huesped'));
$rdHuespedPartes = preg_split('/\s+/', $rdHuespedNombre);
$rdHuespedIniciales = '';
$rdHuespedInicialesCount = 0;
foreach ($rdHuespedPartes as $rdParteNombre) {
    if ($rdParteNombre === '') {
        continue;
    }
    $rdHuespedIniciales .= function_exists('mb_substr')
        ? mb_substr($rdParteNombre, 0, 1, 'UTF-8')
        : substr($rdParteNombre, 0, 1);
    $rdHuespedInicialesCount++;
    if ($rdHuespedInicialesCount >= 2) {
        break;
    }
}
$rdHuespedIniciales = $rdHuespedIniciales !== ''
    ? (function_exists('mb_strtoupper') ? mb_strtoupper($rdHuespedIniciales, 'UTF-8') : strtoupper($rdHuespedIniciales))
    : 'H';
$rdFechaHoy = date('Y-m-d');
$rdFechaEntrada = (string)($reservacion['fecha_entrada'] ?? '');
$rdFechaSalida = (string)($reservacion['fecha_salida'] ?? '');
$rdCheckinMode = null;
$rdCheckinDays = 0;
if (($reservacion['estado'] ?? '') === 'confirmada' && $rdFechaEntrada !== '' && $rdFechaSalida !== '') {
    if ($rdFechaHoy === $rdFechaEntrada) {
        $rdCheckinMode = 'normal';
    } elseif ($rdFechaHoy > $rdFechaEntrada && $rdFechaHoy < $rdFechaSalida) {
        $rdCheckinMode = 'late';
        $rdCheckinDays = (int)((strtotime($rdFechaHoy) - strtotime($rdFechaEntrada)) / 86400);
    } elseif ($rdFechaHoy >= $rdFechaSalida) {
        $rdCheckinMode = 'express';
        $rdCheckinDays = (int)((strtotime($rdFechaHoy) - strtotime($rdFechaSalida)) / 86400);
    }
}
$rdCheckinJsMode = 'normal_tardio';
$rdCheckinButtonLabel = $rdCheckinMode === 'normal' ? 'Check-in' : 'Check-in (Tardío)';
$rdCheckinActionLabel = $rdCheckinMode === 'normal' ? 'Registrar check-in' : 'Registrar check-in tardío';
$rdHuespedNombreJsonAttr = htmlspecialchars(json_encode($rdHuespedNombre, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdHabitacionesTextoJsonAttr = htmlspecialchars(json_encode($rdHabitacionesTexto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdFechaEntradaFormatoJsonAttr = htmlspecialchars(json_encode($rdFechaEntrada !== '' ? date('d/m/Y', strtotime($rdFechaEntrada)) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdFechaSalidaFormatoJsonAttr = htmlspecialchars(json_encode($rdFechaSalida !== '' ? date('d/m/Y', strtotime($rdFechaSalida)) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdMetodoPagoLabel = !empty($reservacion['metodo_pago'])
    ? ucfirst(str_replace('_', ' ', (string)$reservacion['metodo_pago']))
    : 'Pendiente';
$rdTipoTarjetaReservacion = strtolower((string)($tipoTarjetaReservacion ?? ''));
if (strtolower((string)($reservacion['metodo_pago'] ?? '')) === 'tarjeta' && in_array($rdTipoTarjetaReservacion, ['credito', 'debito'], true)) {
    $rdMetodoPagoLabel = 'Tarjeta de ' . ($rdTipoTarjetaReservacion === 'credito' ? 'crédito' : 'débito');
}
$rdNoches = 1;
if ($rdFechaEntrada !== '' && $rdFechaSalida !== '') {
    $rdEntradaDate = new DateTime($rdFechaEntrada);
    $rdSalidaDate = new DateTime($rdFechaSalida);
    $rdNoches = $rdEntradaDate->diff($rdSalidaDate)->days ?: 1;
}
$noches = $rdNoches;
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

/* Variables CSS */
:root {
    --hotel-brown: #8B4513;
    --hotel-brown-dark: #6B3410;
    --hotel-gold: #FFD700;
    --hotel-cream: #FFF8DC;
    --hotel-purple: #9333EA;
    --hotel-blue: #3B82F6;
    --hotel-green: #10B981;
    --hotel-red: #EF4444;
    --hotel-orange: #F97316;
}

/* Reset de espacios */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Animaciones */
.detail-view {
    animation: fadeIn 0.3s ease forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Header compacto con gradiente */
.detail-header {
    background: linear-gradient(135deg, var(--hotel-brown) 0%, var(--hotel-brown-dark) 100%);
    color: white;
    padding: 0.875rem 0;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
    position: sticky;
    top: 0;
    z-index: 40;
}

.detail-header a {
    color: var(--hotel-gold);
    transition: all 0.2s;
}

.detail-header a:hover {
    color: white;
    transform: translateX(-3px);
}

/* Cards mejoradas */
.info-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 2px solid transparent;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--hotel-gold), var(--hotel-brown));
    opacity: 0;
    transition: opacity 0.3s;
}

.info-card:hover::before {
    opacity: 1;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

.card-blue { border-color: #60A5FA; }
.card-purple { border-color: #A78BFA; }
.card-green { border-color: #34D399; }
.card-gold { border-color: var(--hotel-gold); }
.card-orange { border-color: var(--hotel-orange); }

/* Headers de cards más vibrantes */
.card-header {
    padding: 0.875rem 1rem;
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    border-bottom: 2px solid #F3F4F6;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
/* Agregar estos estilos en la sección <style> */
.ubicacion-coches {
    background: #DBEAFE;
    color: #1E40AF;
}

.ubicacion-camionetas {
    background: #D1FAE5;
    color: #065F46;
}

.ubicacion-discos {
    background: #E9D5FF;
    color: #7C3AED;
}

.ubicacion-nikkos {
    background: #FEF3C7;
    color: #92400E;
}

.ubicacion-catalogo {
    background: #E0F2FE;
    color: #075985;
}
.card-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.icon-blue {
    background: linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%);
    color: var(--hotel-blue);
}
.icon-purple {
    background: linear-gradient(135deg, #E9D5FF 0%, #DDD6FE 100%);
    color: var(--hotel-purple);
}
.icon-green {
    background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
    color: var(--hotel-green);
}
.icon-gold {
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    color: #D97706;
}
.icon-red {
    background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
    color: var(--hotel-red);
}
.icon-orange {
    background: linear-gradient(135deg, #FED7AA 0%, #FDBA74 100%);
    color: var(--hotel-orange);
}

/* Body de cards compacto */
.card-body {
    padding: 0.875rem 1rem;
}

/* Badges de estado vibrantes */
.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.875rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.estado-confirmada {
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    color: var(--hotel-blue);
    border: 1px solid var(--hotel-blue);
}

.estado-checked-in {
    background: linear-gradient(135deg, #F0FDF4 0%, #D1FAE5 100%);
    color: var(--hotel-green);
    border: 1px solid var(--hotel-green);
}

.estado-checked-out {
    background: #F9FAFB;
    color: #6B7280;
    border: 1px solid #D1D5DB;
}

.estado-cancelada {
    background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%);
    color: var(--hotel-red);
    border: 1px solid var(--hotel-red);
}

/* Agregar al estilo existente */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-in-out;
}

.nota-item {
    transition: all 0.2s ease;
}

.nota-item:hover {
    transform: translateX(2px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Timeline mejorado */
.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 0.75rem;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--hotel-gold), rgba(255, 215, 0, 0.2));
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-dot {
    position: absolute;
    left: 0.25rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.1);
}

/* Botones mejorados */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.625rem;
    font-weight: 600;
    font-size: 0.8125rem;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.btn-action::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-action:hover::before {
    left: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, var(--hotel-brown) 0%, var(--hotel-brown-dark) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(139, 69, 19, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, var(--hotel-green) 0%, #059669 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.btn-warning {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, var(--hotel-red) 0%, #DC2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

/* Info rows mejoradas */
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
    border-bottom: 1px dashed #E5E7EB;
    transition: all 0.2s;
}

.info-row:hover {
    background: #F9FAFB;
    margin: 0 -0.5rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.75rem;
    color: #6B7280;
    font-weight: 500;
}

.info-value {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1F2937;
}

/* Habitaciones grid mejorado */
.room-card {
    border: 2px solid #E5E7EB;
    border-radius: 0.75rem;
    padding: 0.875rem;
    background: white;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.room-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, var(--hotel-gold) 0%, transparent 70%);
    opacity: 0;
    transition: opacity 0.3s;
}

.room-card:hover::before {
    opacity: 0.1;
}

.room-card:hover {
    border-color: var(--hotel-gold);
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.2);
}

.room-card.cortesia {
    background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%);
    border-color: var(--hotel-green);
}

/* Vehículos estilizados */
.vehiculo-item {
    background: white;
    border: 2px solid #E5E7EB;
    border-radius: 0.75rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.vehiculo-item:hover {
    border-color: var(--hotel-purple);
    background: linear-gradient(135deg, #F5F3FF 0%, #EDE9FE 100%);
    transform: translateX(4px);
}

.vehiculo-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vehiculo-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(135deg, #E9D5FF 0%, #DDD6FE 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--hotel-purple);
    font-size: 1.125rem;
}

.vehiculo-details h4 {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1F2937;
    margin-bottom: 0.125rem;
}

.vehiculo-details p {
    font-size: 0.75rem;
    color: #6B7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ubicacion-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 600;
}

.ubicacion-primer-piso {
    background: #DBEAFE;
    color: #1E40AF;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease;
}

.modal-overlay.hidden {
    display: none;
}

.modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 2px solid #F3F4F6;
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.ubicacion-segundo-piso {
    background: #D1FAE5;
    color: #065F46;
}

.ubicacion-fuera {
    background: #F3F4F6;
    color: #374151;
}

/* Precio destacado animado */
.precio-total {
    background: linear-gradient(135deg, var(--hotel-gold) 0%, #FFC700 100%);
    color: var(--hotel-brown-dark);
    padding: 1rem;
    border-radius: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    position: relative;
    overflow: hidden;
}

.precio-total::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
    transform: rotate(45deg);
    animation: shine 3s infinite;
}

@keyframes shine {
    0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

/* Alertas mejoradas */
.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    border: 2px solid;
    font-size: 0.8125rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideDown 0.3s ease;
    position: relative;
    overflow: hidden;
}

.alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    background: currentColor;
    opacity: 0.3;
}

.alert-info {
    background: #EFF6FF;
    border-color: var(--hotel-blue);
    color: #1E40AF;
}

.alert-warning {
    background: #FEF3C7;
    border-color: #F59E0B;
    color: #92400E;
}

.alert-success {
    background: #F0FDF4;
    border-color: var(--hotel-green);
    color: #065F46;
}

/* Modal mejorado */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0,0,0,0.75);
    backdrop-filter: blur(4px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    max-width: 95%;
    position: relative;
    animation: modalShow 0.3s ease;
}

@keyframes modalShow {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive */
@media (max-width: 1024px) {
    .detail-header {
        position: relative;
    }

    .card-body {
        padding: 0.75rem;
    }
}

@media (max-width: 640px) {
    .info-card {
        border-radius: 0.75rem;
        margin-bottom: 0.5rem;
    }

    .btn-action {
        padding: 0.5rem 0.875rem;
        font-size: 0.75rem;
    }

    .precio-total {
        font-size: 1.25rem;
        padding: 0.75rem;
    }
}

@media print {
    .no-print {
        display: none !important;
    }

    .detail-header {
        position: relative !important;
        background: none !important;
        color: black !important;
    }

    .btn-action {
        display: none !important;
    }
}

/* Rediseño operativo - Detalle de reservacion */
.reservation-detail-v2 {
    --rd-brand: var(--brand-primary, #1B2746);
    --rd-brand-2: var(--brand-secondary, #0F172A);
    --rd-accent: var(--brand-accent, #BD9441);
    --rd-accent-dark: color-mix(in srgb, var(--rd-accent) 72%, #3E2E14);
    --rd-accent-soft: color-mix(in srgb, var(--rd-accent) 10%, #FDFBF7);
    --rd-ivory: color-mix(in srgb, var(--rd-accent) 7%, #F8F5ED);
    --rd-ivory-2: color-mix(in srgb, var(--rd-accent) 4%, #FFFCF7);
    --rd-surface: color-mix(in srgb, var(--rd-accent) 2%, #FDFBF7);
    --rd-surface-warm: color-mix(in srgb, var(--rd-accent) 5%, #FFFCF7);
    --rd-line: color-mix(in srgb, var(--rd-brand) 12%, #E7DDCA);
    --rd-line-soft: color-mix(in srgb, var(--rd-brand) 7%, #EFE8DA);
    --rd-text: #172033;
    --rd-muted: #6F7B8E;
    --rd-success: #16865A;
    --rd-warning: #B7791F;
    --rd-danger: #C24135;
    --rd-info: #2D6BB3;
    --rd-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rd-sans: "Manrope", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--rd-accent) 3%, transparent) 0 1px, transparent 1px 24px),
        linear-gradient(180deg, var(--rd-ivory-2), var(--rd-ivory) 58%, #F6F0E8) !important;
    color: var(--rd-text);
    font-family: var(--rd-sans);
}

.reservation-detail-v2 *,
.reservation-detail-v2 *::before,
.reservation-detail-v2 *::after {
    box-sizing: border-box;
}

.reservation-detail-v2 :where(p, span, a, button, input, select, textarea, label, td, th) {
    font-family: var(--rd-sans);
}

.reservation-detail-v2 .detail-header {
    position: sticky;
    top: 0;
    z-index: 40;
    padding: 14px 0;
    border-bottom: 1px solid color-mix(in srgb, var(--rd-accent) 22%, transparent);
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--rd-brand) 95%, #0A0F1C), var(--rd-brand-2)) !important;
    color: #FDFBF7;
    box-shadow: 0 18px 38px -30px rgba(15, 23, 42, .72) !important;
}

.reservation-detail-v2 .detail-header .container,
.reservation-detail-v2 > .container {
    width: min(100%, 1280px);
    max-width: none;
    margin-inline: auto;
}

.reservation-detail-v2 > .container {
    padding: 20px 18px 32px !important;
}

.reservation-detail-v2 .detail-header a {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 13px;
    background: rgba(255, 255, 255, .08);
    color: #F8EFE0 !important;
    text-decoration: none;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.reservation-detail-v2 .detail-header a:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rd-accent) 52%, transparent);
    background: rgba(255, 255, 255, .14);
}

.reservation-detail-v2 .detail-header h1 {
    color: #FDFBF7;
    font-family: var(--rd-serif);
    font-size: clamp(1.7rem, 3vw, 2.4rem);
    font-weight: 700;
    line-height: .96;
    letter-spacing: 0;
}

.reservation-detail-v2 .detail-header h1 + .estado-badge,
.reservation-detail-v2 .estado-badge {
    min-height: 28px;
    padding: 7px 11px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .28);
    box-shadow: none;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: none;
}

.reservation-detail-v2 .estado-confirmada {
    background: color-mix(in srgb, var(--rd-info) 15%, #FDFBF7);
    color: color-mix(in srgb, var(--rd-info) 78%, var(--rd-brand));
    border-color: color-mix(in srgb, var(--rd-info) 34%, transparent);
}

.reservation-detail-v2 .estado-checked-in {
    background: color-mix(in srgb, var(--rd-success) 16%, #FDFBF7);
    color: color-mix(in srgb, var(--rd-success) 82%, var(--rd-brand));
    border-color: color-mix(in srgb, var(--rd-success) 35%, transparent);
}

.reservation-detail-v2 .estado-checked-out {
    background: color-mix(in srgb, var(--rd-brand) 8%, #FDFBF7);
    color: var(--rd-muted);
    border-color: var(--rd-line);
}

.reservation-detail-v2 .estado-cancelada {
    background: color-mix(in srgb, var(--rd-danger) 13%, #FDFBF7);
    color: color-mix(in srgb, var(--rd-danger) 84%, #521412);
    border-color: color-mix(in srgb, var(--rd-danger) 34%, transparent);
}

.reservation-detail-v2 .detail-header .text-gold,
.reservation-detail-v2 .detail-header .text-green-300 {
    color: color-mix(in srgb, var(--rd-accent) 74%, #FDFBF7) !important;
}

.reservation-detail-v2 .detail-header .no-print {
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.reservation-detail-v2 .btn-action {
    min-height: 38px;
    padding: 9px 13px;
    border: 1px solid color-mix(in srgb, var(--rd-brand) 12%, transparent);
    border-radius: 12px;
    font-size: .78rem;
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0;
    box-shadow: 0 12px 24px -20px rgba(15, 23, 42, .42);
    transform: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.reservation-detail-v2 .btn-action::before {
    display: none;
}

.reservation-detail-v2 .btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px -22px rgba(15, 23, 42, .5);
}

.reservation-detail-v2 .btn-primary {
    background: linear-gradient(135deg, var(--rd-brand), var(--rd-brand-2)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-success {
    background: color-mix(in srgb, var(--rd-success) 88%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-warning {
    background: color-mix(in srgb, var(--rd-warning) 88%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-danger {
    background: color-mix(in srgb, var(--rd-danger) 88%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-cotizacion-ver {
    background: color-mix(in srgb, var(--rd-accent) 88%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-action.bg-yellow-500,
.reservation-detail-v2 .btn-action.bg-orange-600 {
    background: color-mix(in srgb, var(--rd-warning) 86%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-action.bg-purple-500,
.reservation-detail-v2 .btn-action.bg-indigo-500 {
    background: color-mix(in srgb, var(--rd-info) 72%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .btn-action[style*="background:#25D366"] {
    background: color-mix(in srgb, #25D366 76%, var(--rd-brand)) !important;
    color: #FDFBF7 !important;
}

.reservation-detail-v2 .grid.grid-cols-1.lg\:grid-cols-3 {
    gap: 18px !important;
    align-items: start;
}

.reservation-detail-v2 .lg\:col-span-2.space-y-3,
.reservation-detail-v2 .grid.grid-cols-1.lg\:grid-cols-3 > .space-y-3 {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.reservation-detail-v2 .info-card {
    overflow: hidden;
    border: 1px solid var(--rd-line) !important;
    border-radius: 18px !important;
    background: var(--rd-surface) !important;
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--rd-brand) 4%, transparent),
        0 18px 36px -30px color-mix(in srgb, var(--rd-brand) 36%, transparent) !important;
    transform: none !important;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.reservation-detail-v2 .info-card::before {
    display: none;
}

.reservation-detail-v2 .info-card:hover {
    transform: none !important;
    border-color: color-mix(in srgb, var(--rd-accent) 36%, var(--rd-line)) !important;
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--rd-brand) 5%, transparent),
        0 20px 42px -30px color-mix(in srgb, var(--rd-brand) 42%, transparent) !important;
}

.reservation-detail-v2 .card-blue,
.reservation-detail-v2 .card-purple,
.reservation-detail-v2 .card-green,
.reservation-detail-v2 .card-gold,
.reservation-detail-v2 .card-orange {
    border-color: var(--rd-line) !important;
}

.reservation-detail-v2 .card-header {
    min-height: 58px;
    padding: 15px 17px;
    border-bottom: 1px solid var(--rd-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--rd-accent) 6%, #FDFBF7), color-mix(in srgb, var(--rd-accent) 3%, #FDFBF7)) !important;
    gap: 11px;
}

.reservation-detail-v2 .card-header h2,
.reservation-detail-v2 .card-header h3 {
    color: #111827 !important;
    font-size: .95rem !important;
    font-weight: 900 !important;
    letter-spacing: 0;
}

.reservation-detail-v2 .card-icon {
    width: 38px;
    height: 38px;
    border-radius: 13px;
    color: var(--rd-accent-dark) !important;
    background: var(--rd-accent-soft) !important;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--rd-accent) 24%, transparent) !important;
}

.reservation-detail-v2 .card-body {
    padding: 16px 17px;
}

.reservation-detail-v2 .info-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, auto);
    gap: 12px;
    align-items: center;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid transparent;
    border-bottom: 1px solid var(--rd-line-soft);
    border-radius: 12px;
    background: transparent;
    transition: background .18s ease, border-color .18s ease;
}

.reservation-detail-v2 .info-row:hover {
    margin: 0;
    padding: 10px 12px;
    border-color: var(--rd-line-soft);
    background: var(--rd-surface-warm);
}

.reservation-detail-v2 .info-label {
    color: var(--rd-muted);
    font-size: .72rem;
    font-weight: 600;
}

.reservation-detail-v2 .info-value {
    min-width: 0;
    color: var(--rd-brand);
    font-size: .88rem;
    font-weight: 700;
    text-align: right;
    overflow-wrap: anywhere;
}

.reservation-detail-v2 .precio-total {
    padding: 18px 16px;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 34%, var(--rd-line));
    border-radius: 16px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--rd-accent) 16%, #FDFBF7), color-mix(in srgb, var(--rd-accent) 8%, #FFFCF7)) !important;
    color: var(--rd-brand);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .78), 0 14px 28px -24px color-mix(in srgb, var(--rd-accent) 48%, transparent);
    font-size: clamp(1.45rem, 3vw, 2rem);
    font-weight: 600;
    letter-spacing: 0;
}

.reservation-detail-v2 .precio-total::before {
    display: none;
}

.reservation-detail-v2 .room-card {
    min-height: 92px;
    padding: 14px;
    border: 1px solid var(--rd-line) !important;
    border-radius: 15px;
    background: color-mix(in srgb, var(--rd-accent) 2%, #FDFBF7);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--rd-brand) 4%, transparent);
}

.reservation-detail-v2 .room-card::before {
    display: none;
}

.reservation-detail-v2 .room-card:hover {
    transform: none;
    border-color: color-mix(in srgb, var(--rd-accent) 38%, var(--rd-line)) !important;
    box-shadow: 0 16px 30px -26px color-mix(in srgb, var(--rd-brand) 38%, transparent);
}

.reservation-detail-v2 .room-card h4 {
    color: var(--rd-brand) !important;
    font-size: .95rem;
    line-height: 1.15;
}

.reservation-detail-v2 .room-card p {
    color: var(--rd-muted) !important;
    font-weight: 650;
}

.reservation-detail-v2 .room-card.cortesia {
    border-color: color-mix(in srgb, var(--rd-success) 36%, var(--rd-line)) !important;
    background: color-mix(in srgb, var(--rd-success) 8%, #FDFBF7) !important;
}

.reservation-detail-v2 .room-paid-summary {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    margin-bottom: 14px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 26%, var(--rd-line));
    border-radius: 15px;
    background: color-mix(in srgb, var(--rd-accent) 7%, #FDFBF7);
}

.reservation-detail-v2 .room-paid-summary span {
    display: block;
    color: var(--rd-muted);
    font-size: .72rem;
    font-weight: 600;
}

.reservation-detail-v2 .room-paid-summary strong {
    display: block;
    color: var(--rd-brand);
    font-size: clamp(1.05rem, 2vw, 1.3rem);
    font-weight: 600;
    line-height: 1.1;
}

.reservation-detail-v2 .room-paid-summary > div:last-child {
    text-align: right;
}

.reservation-detail-v2 .room-price-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--rd-line-soft);
}

.reservation-detail-v2 .room-price-label {
    color: var(--rd-muted);
    font-size: .7rem;
    font-weight: 600;
}

.reservation-detail-v2 .room-price-value {
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 600;
    white-space: nowrap;
}

.reservation-detail-v2 .room-price-value.is-free {
    color: color-mix(in srgb, var(--rd-success) 82%, var(--rd-brand));
}

.reservation-detail-v2 .vehiculo-item {
    border: 1px solid var(--rd-line);
    border-radius: 14px;
    background: var(--rd-surface-warm);
    box-shadow: none;
}

.reservation-detail-v2 .vehiculo-item:hover {
    transform: none;
    border-color: color-mix(in srgb, var(--rd-accent) 36%, var(--rd-line));
    background: color-mix(in srgb, var(--rd-accent) 6%, #FDFBF7);
}

.reservation-detail-v2 .vehiculo-icon {
    background: var(--rd-accent-soft);
    color: var(--rd-accent-dark);
}

.reservation-detail-v2 .alert {
    padding: 13px 15px;
    border-width: 1px;
    border-radius: 15px;
    box-shadow: 0 12px 26px -24px color-mix(in srgb, var(--rd-brand) 32%, transparent);
}

.reservation-detail-v2 .alert::before {
    display: none;
}

.reservation-detail-v2 .alert-info {
    background: color-mix(in srgb, var(--rd-info) 8%, #FDFBF7);
    border-color: color-mix(in srgb, var(--rd-info) 28%, var(--rd-line));
    color: color-mix(in srgb, var(--rd-info) 78%, var(--rd-brand));
}

.reservation-detail-v2 .alert-warning {
    background: color-mix(in srgb, var(--rd-warning) 12%, #FDFBF7);
    border-color: color-mix(in srgb, var(--rd-warning) 34%, var(--rd-line));
    color: color-mix(in srgb, var(--rd-warning) 84%, #563308);
}

.reservation-detail-v2 .alert-success {
    background: color-mix(in srgb, var(--rd-success) 9%, #FDFBF7);
    border-color: color-mix(in srgb, var(--rd-success) 28%, var(--rd-line));
    color: color-mix(in srgb, var(--rd-success) 82%, var(--rd-brand));
}

.reservation-detail-v2 .timeline-item {
    padding-left: 30px;
    padding-bottom: 16px;
}

.reservation-detail-v2 .timeline-item::before {
    left: 8px;
    width: 1px;
    background: color-mix(in srgb, var(--rd-accent) 40%, var(--rd-line));
}

.reservation-detail-v2 .timeline-dot {
    left: 2px;
    top: 4px;
    width: 13px;
    height: 13px;
    border: 3px solid var(--rd-surface);
    box-shadow: 0 0 0 1px var(--rd-line);
}

.reservation-detail-v2 #wa-menu {
    border: 1px solid var(--rd-line) !important;
    border-radius: 16px !important;
    background: #FDFBF7 !important;
    box-shadow: 0 24px 48px -28px rgba(15, 23, 42, .48) !important;
}

.reservation-detail-v2 #wa-menu a:hover {
    background: color-mix(in srgb, #25D366 9%, #FDFBF7) !important;
}

.reservation-detail-v2 textarea,
.reservation-detail-v2 input:not([type="checkbox"]):not([type="radio"]),
.reservation-detail-v2 select {
    border-color: var(--rd-line) !important;
    border-radius: 12px !important;
    background: #FDFBF7 !important;
    color: var(--rd-brand) !important;
    box-shadow: none !important;
}

.reservation-detail-v2 textarea:focus,
.reservation-detail-v2 input:not([type="checkbox"]):not([type="radio"]):focus,
.reservation-detail-v2 select:focus {
    border-color: color-mix(in srgb, var(--rd-accent) 58%, var(--rd-line)) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rd-accent) 24%, transparent) !important;
    outline: none !important;
}

.reservation-detail-v2 .nota-item {
    border: 1px solid color-mix(in srgb, var(--rd-accent) 24%, var(--rd-line)) !important;
    border-radius: 13px !important;
    background: color-mix(in srgb, var(--rd-accent) 8%, #FDFBF7) !important;
}

.reservation-detail-v2 .nota-item:hover {
    transform: none;
    box-shadow: none;
}

.reservation-detail-v2 .info-card.no-print {
    background: var(--rd-surface) !important;
}

.reservation-detail-v2 .rd-reservation-hero-strip {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin: 0 0 16px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--rd-brand) 10%, #E5E7EB);
    border-radius: 22px;
    background:
        linear-gradient(135deg, rgba(255,255,255,.98), color-mix(in srgb, var(--rd-accent) 5%, #FFFFFF)),
        #FFFFFF;
    box-shadow: 0 20px 44px -34px rgba(15, 23, 42, .38);
}

.reservation-detail-v2 .rd-reservation-hero-strip::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--rd-brand) 62%, #1F2937),
        color-mix(in srgb, var(--rd-accent) 60%, #FFFFFF),
        color-mix(in srgb, var(--rd-brand) 18%, #FFFFFF));
    opacity: .78;
}

.reservation-detail-v2 .rd-strip-item {
    position: relative;
    overflow: hidden;
    min-height: 74px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--rd-brand) 9%, #E5E7EB);
    border-radius: 16px;
    background: rgba(255,255,255,.86);
    box-shadow: 0 1px 0 rgba(255,255,255,.9) inset;
}

.reservation-detail-v2 .rd-strip-item::before {
    content: "";
    position: absolute;
    inset: 12px auto 12px 0;
    width: 3px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rd-brand) 52%, #CBD5E1);
    opacity: .64;
}

.reservation-detail-v2 .rd-strip-item span {
    color: #647084;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.reservation-detail-v2 .rd-strip-item strong {
    color: #182033;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.05;
    font-variant-numeric: tabular-nums;
}

.reservation-detail-v2 .rd-strip-item.is-total {
    background: linear-gradient(135deg, color-mix(in srgb, var(--rd-accent) 9%, #FFFFFF), #FFFFFF);
    border-color: color-mix(in srgb, var(--rd-accent) 22%, #E5E7EB);
}

.reservation-detail-v2 .rd-strip-item.is-total::before {
    background: color-mix(in srgb, var(--rd-accent) 62%, #A16207);
    opacity: .78;
}

.reservation-detail-v2 .rd-strip-item.is-total strong {
    color: #182033;
    font-size: 1.22rem;
}

.reservation-detail-v2 .rd-inline-link,
.reservation-detail-v2 .rd-room-link,
.reservation-detail-v2 .rd-mini-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    color: var(--rd-brand);
    text-decoration: none;
    border-radius: 11px;
    transition: color .18s ease, background .18s ease, border-color .18s ease, transform .18s ease;
}

.reservation-detail-v2 .rd-inline-link:hover,
.reservation-detail-v2 .rd-inline-link:focus-visible,
.reservation-detail-v2 .rd-room-link:hover,
.reservation-detail-v2 .rd-room-link:focus-visible,
.reservation-detail-v2 .rd-mini-link:hover,
.reservation-detail-v2 .rd-mini-link:focus-visible {
    color: color-mix(in srgb, var(--rd-accent) 70%, var(--rd-brand));
    border-color: color-mix(in srgb, var(--rd-accent) 42%, var(--rd-line));
    background: color-mix(in srgb, var(--rd-accent) 8%, #FDFBF7);
    outline: none;
    transform: translateY(-1px);
}

.reservation-detail-v2 .rd-inline-link {
    justify-content: flex-end;
    max-width: 100%;
    padding: 4px 7px;
    margin: -4px -7px;
    font-weight: 600;
}

.reservation-detail-v2 .rd-room-link {
    padding: 4px 7px;
    margin: -4px -7px;
    color: var(--rd-brand) !important;
    font-weight: 600;
}

.reservation-detail-v2 .rd-mini-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 10px;
}

.reservation-detail-v2 .rd-mini-link {
    min-height: 30px;
    padding: 6px 9px;
    border: 1px solid var(--rd-line-soft);
    background: #FDFBF7;
    color: var(--rd-muted);
    font-size: .7rem;
    font-weight: 600;
}

.reservation-detail-v2 .rd-section-card {
    scroll-margin-top: 145px;
    position: relative;
}

.reservation-detail-v2 #resumen-reservacion {
    --rd-section-accent: var(--rd-info);
}

.reservation-detail-v2 #habitaciones-reservacion {
    --rd-section-accent: #6B5BA7;
}

.reservation-detail-v2 #huesped-reservacion {
    --rd-section-accent: var(--rd-success);
}

.reservation-detail-v2 .rd-section-card::after {
    content: '';
    position: absolute;
    inset: 0 0 auto;
    height: 5px;
    background: linear-gradient(90deg, var(--rd-section-accent, var(--rd-accent)), color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 24%, transparent));
    opacity: .9;
}

.reservation-detail-v2 .rd-section-card .card-header {
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 11%, #FDFBF7), #FDFBF7 74%) !important;
}

.reservation-detail-v2 .rd-section-card .card-header h2,
.reservation-detail-v2 .rd-section-card .card-header h3,
.reservation-detail-v2 .info-card .card-header h2,
.reservation-detail-v2 .info-card .card-header h3 {
    color: #111827 !important;
}

.reservation-detail-v2 .rd-section-card .card-icon {
    color: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 76%, var(--rd-brand)) !important;
    background: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 13%, #FDFBF7) !important;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 24%, transparent) !important;
}

.reservation-detail-v2 .rd-section-card:target {
    border-color: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 54%, var(--rd-line)) !important;
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 15%, transparent),
        0 20px 42px -30px color-mix(in srgb, var(--rd-brand) 42%, transparent) !important;
}

.reservation-detail-v2 .btn-action:focus-visible,
.reservation-detail-v2 button:focus-visible,
.reservation-detail-v2 a:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--rd-accent) 32%, transparent);
    outline-offset: 2px;
}

.reservation-detail-v2 .btn-action:active,
.reservation-detail-v2 .room-card:active,
.reservation-detail-v2 .vehiculo-item:active {
    transform: translateY(0) scale(.99);
}

.reservation-detail-v2 .btn-action i,
.reservation-detail-v2 .card-icon i {
    transition: transform .18s ease;
}

.reservation-detail-v2 .btn-action:hover i,
.reservation-detail-v2 .card-header:hover .card-icon i {
    transform: translateX(1px);
}

.reservation-detail-v2 .info-row {
    cursor: default;
}

.reservation-detail-v2 .info-row:hover .info-label,
.reservation-detail-v2 .info-row:hover .info-value {
    color: var(--rd-brand) !important;
}

.reservation-detail-v2 .info-row:hover {
    border-color: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 24%, var(--rd-line)) !important;
    background: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 6%, #FDFBF7) !important;
}

.reservation-detail-v2 .precio-total,
.reservation-detail-v2 .room-paid-summary,
.reservation-detail-v2 .room-card {
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease;
}

.reservation-detail-v2 .precio-total:hover,
.reservation-detail-v2 .room-paid-summary:hover {
    border-color: color-mix(in srgb, var(--rd-accent) 46%, var(--rd-line));
    box-shadow: 0 18px 34px -28px color-mix(in srgb, var(--rd-accent) 48%, transparent);
}

.reservation-detail-v2 .wa-menu-open {
    border-color: color-mix(in srgb, #25D366 30%, transparent) !important;
    background: color-mix(in srgb, #25D366 12%, #FDFBF7) !important;
}

/* Mantener textos principales en negro, sin usar el color primario del hotel. */
.reservation-detail-v2 .info-value,
.reservation-detail-v2 .precio-total,
.reservation-detail-v2 .room-card h4,
.reservation-detail-v2 .room-paid-summary strong,
.reservation-detail-v2 .room-price-value,
.reservation-detail-v2 textarea,
.reservation-detail-v2 input:not([type="checkbox"]):not([type="radio"]),
.reservation-detail-v2 select,
.reservation-detail-v2 .rd-inline-link,
.reservation-detail-v2 .rd-room-link,
.reservation-detail-v2 .rd-mini-link,
.reservation-detail-v2 .rd-inline-link:hover,
.reservation-detail-v2 .rd-inline-link:focus-visible,
.reservation-detail-v2 .rd-room-link:hover,
.reservation-detail-v2 .rd-room-link:focus-visible,
.reservation-detail-v2 .rd-mini-link:hover,
.reservation-detail-v2 .rd-mini-link:focus-visible,
.reservation-detail-v2 .info-row:hover .info-label,
.reservation-detail-v2 .info-row:hover .info-value {
    color: #111827 !important;
}

@media (prefers-reduced-motion: reduce) {
    .reservation-detail-v2 *,
    .reservation-detail-v2 *::before,
    .reservation-detail-v2 *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}

@media (min-width: 1024px) {
    .reservation-detail-v2 .grid.grid-cols-1.lg\:grid-cols-3 > .space-y-3 {
        position: sticky;
        top: 92px;
    }
}

@media (max-width: 1024px) {
    .reservation-detail-v2 .detail-header {
        position: relative;
    }

    .reservation-detail-v2 .rd-reservation-hero-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 640px) {
    .reservation-detail-v2 > .container {
        padding: 14px 12px 26px !important;
    }

    .reservation-detail-v2 .detail-header {
        padding: 12px 0;
    }

    .reservation-detail-v2 .detail-header .container {
        padding-inline: 12px !important;
    }

    .reservation-detail-v2 .detail-header h1 {
        font-size: 1.58rem;
    }

    .reservation-detail-v2 .estado-badge {
        width: fit-content;
    }

    .reservation-detail-v2 .rd-reservation-hero-strip {
        gap: 8px;
        padding: 9px;
        border-radius: 16px;
    }

    .reservation-detail-v2 .rd-strip-item {
        min-height: 66px;
        padding: 10px;
        border-radius: 13px;
    }

    .reservation-detail-v2 .rd-strip-item strong {
        font-size: .9rem;
    }

    .reservation-detail-v2 .rd-inline-link {
        justify-content: flex-start;
        margin-left: -7px;
    }

    .reservation-detail-v2 .card-header {
        padding: 13px 14px;
    }

    .reservation-detail-v2 .card-body {
        padding: 14px;
    }

    .reservation-detail-v2 .info-row {
        grid-template-columns: 1fr;
        gap: 4px;
        align-items: start;
    }

    .reservation-detail-v2 .info-value {
        text-align: left;
    }

    .reservation-detail-v2 .btn-action {
        width: 100%;
        justify-content: center;
        min-height: 42px;
        white-space: normal;
    }

    .reservation-detail-v2 .precio-total {
        font-size: 1.5rem;
    }

    .reservation-detail-v2 .room-paid-summary,
    .reservation-detail-v2 .room-price-panel {
        grid-template-columns: 1fr;
    }

    .reservation-detail-v2 .room-paid-summary {
        align-items: start;
    }

    .reservation-detail-v2 .room-paid-summary > div:last-child {
        text-align: left;
    }

    .reservation-detail-v2 .room-price-panel {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }
}
</style>

<!-- Vista de Detalle Moderna -->
<?php
$rdSafe = function($value, string $fallback = '-') {
    $text = trim((string)($value ?? ''));
    return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
};
$rdMoney = function($value) {
    $amount = (float)($value ?? 0);
    return function_exists('format_money') ? format_money($amount) : '$' . number_format($amount, 2);
};
$rdDate = function($value) use ($rdSafe) {
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        return '-';
    }
    $ts = strtotime($text);
    if (!$ts) {
        return $rdSafe($text);
    }
    $months = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];
    return date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
};
$rdDateTime = function($value) use ($rdSafe) {
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        return '-';
    }
    if (function_exists('format_datetime')) {
        return format_datetime($text);
    }
    $ts = strtotime($text);
    return $ts ? date('d/m/Y H:i', $ts) : $rdSafe($text);
};
$rdRoomTypeLabel = function(array $room) {
    if (!empty($room['tipo_label'])) {
        return (string)$room['tipo_label'];
    }

    return function_exists('get_tipo_habitacion_real')
        ? get_tipo_habitacion_real($room['tipo'] ?? '', $room['caracteristicas'] ?? '')
        : ucfirst(str_replace('_', ' ', (string)($room['tipo'] ?? 'Habitacion')));
};
$rdBytes = function($bytes) {
    $bytes = (int)($bytes ?? 0);
    if ($bytes <= 0) {
        return '-';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = (float)$bytes;
    $index = 0;
    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }
    return number_format($size, $index === 0 ? 0 : 1) . ' ' . $units[$index];
};
$rdDocIcon = function($documento) {
    $mime = strtolower((string)($documento['mime_type'] ?? ''));
    $name = strtolower((string)(($documento['nombre_original'] ?? '') ?: ($documento['titulo'] ?? '')));
    if (strpos($mime, 'pdf') !== false || substr($name, -4) === '.pdf') {
        return ['PDF', 'rdv3-docicon--pdf'];
    }
    if (strpos($mime, 'image') !== false || preg_match('/\.(jpg|jpeg|png|webp)$/', $name)) {
        return ['JPG', 'rdv3-docicon--image'];
    }
    return ['DOC', 'rdv3-docicon--doc'];
};

$rdReservationId = (int)($reservacion['id'] ?? 0);
$rdEstadoKey = (string)($reservacion['estado'] ?? '');
$rdEstadoLabel = trim((string)($estado_info['label'] ?? '')) ?: ucfirst(str_replace('_', ' ', $rdEstadoKey ?: 'pendiente'));
$rdEstadoClass = in_array($rdEstadoKey, ['confirmada', 'checked_in'], true) ? 'rdv3-status--ok' : (in_array($rdEstadoKey, ['cancelada', 'no_show'], true) ? 'rdv3-status--danger' : 'rdv3-status--wait');
$rdRooms = is_array($habitaciones ?? null) ? $habitaciones : [];
$rdRoomCount = count($rdRooms);
$rdRoomCountLabel = $rdRoomCount . ' habitacion' . ($rdRoomCount === 1 ? '' : 'es');
$rdPayments = is_array($pagos ?? null) ? $pagos : [];
$rdNotes = is_array($notas ?? null) ? $notas : [];
$rdDocuments = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];
$rdGuestDocuments = is_array($documentosHuesped ?? null) ? $documentosHuesped : [];
$rdDocsContext = is_array($documentosEntidadContexto ?? null) ? $documentosEntidadContexto : [];
$rdVehicles = is_array($vehiculos ?? null) ? $vehiculos : [];
$rdPaymentSummary = is_array($resumenPagos ?? null) ? $resumenPagos : null;
$rdTotal = (float)($rdPaymentSummary['total'] ?? ($reservacion['precio_total'] ?? 0));
$rdTotalPaid = 0.0;
foreach ($rdPayments as $rdPaymentRow) {
    $rdTotalPaid += (float)($rdPaymentRow['monto'] ?? $rdPaymentRow['cantidad'] ?? 0);
}
$rdHeaderBalance = max(0, round($rdTotal - $rdTotalPaid, 2));
if ($rdPaymentSummary !== null) {
    $rdTotalPaid = (float)($rdPaymentSummary['pagado'] ?? $rdTotalPaid);
    $rdHeaderBalance = (float)($rdPaymentSummary['saldo'] ?? $rdHeaderBalance);
} elseif ($rdTotalPaid <= 0 && !empty($reservacion['metodo_pago'])) {
    $rdTotalPaid = $rdTotal;
    $rdHeaderBalance = 0.0;
}
$rdPaymentIsPaid = $rdHeaderBalance <= 0.004 && ($rdTotalPaid > 0 || $rdTotal <= 0);
$rdHasCheckoutDebt = $rdHeaderBalance > 0.004;
if ($rdPaymentIsPaid) {
    $rdPaymentLabel = !empty($reservacion['metodo_pago']) ? 'Pagado - ' . $rdMetodoPagoLabel : 'Pagado';
} elseif ($rdHeaderBalance > 0.004) {
    $rdPaymentLabel = 'Saldo pendiente ' . $rdMoney($rdHeaderBalance);
} else {
    $rdPaymentLabel = 'Pago pendiente';
}
$rdCortesias = (int)($reservacion['habitaciones_cortesia'] ?? 0);
foreach ($rdRooms as $rdRoomCountRow) {
    if (!empty($rdRoomCountRow['es_cortesia']) || !empty($rdRoomCountRow['cortesia'])) {
        $rdCortesias++;
    }
}
$rdGuestName = trim((string)($huesped['nombre_completo'] ?? $rdHuespedNombre ?? 'Huesped'));
$rdGuestPhone = trim((string)($huesped['telefono'] ?? $huesped['celular'] ?? $huesped['telefono_principal'] ?? ''));
$rdGuestEmail = trim((string)($huesped['email'] ?? $huesped['correo'] ?? ''));

// Edición inline del huésped desde esta vista (solo campos atómicos y seguros).
$rdGuestEditId = (int)($huesped['id'] ?? 0);
$rdGuestEditable = $rdGuestEditId > 0;
$rdEditAttr = function (string $field, string $value, string $emptyLabel, string $type = 'text') use ($rdGuestEditable, $rdSafe) {
    if (!$rdGuestEditable) {
        return '';
    }
    return ' class="rd-editable" data-field="' . $rdSafe($field, '')
        . '" data-value="' . $rdSafe($value, '')
        . '" data-empty="' . $rdSafe($emptyLabel, '')
        . '" data-type="' . $rdSafe($type, 'text')
        . '" tabindex="0" role="button" title="Clic para editar"';
};
$rdGuestIdType = trim((string)($huesped['tipo_identificacion'] ?? $huesped['identificacion_tipo'] ?? ''));
$rdGuestIdNumber = trim((string)($huesped['numero_identificacion'] ?? $huesped['identificacion_numero'] ?? $huesped['identificacion'] ?? ''));
$rdGuestId = trim(($rdGuestIdType !== '' ? $rdGuestIdType . ' - ' : '') . $rdGuestIdNumber);
$rdGuestOriginParts = array_filter([
    trim((string)($huesped['procedencia_ciudad'] ?? $huesped['ciudad'] ?? '')),
    trim((string)($huesped['procedencia_estado'] ?? $huesped['estado'] ?? '')),
    trim((string)($huesped['pais'] ?? '')),
]);
$rdGuestOrigin = !empty($huesped['procedencia']) ? (string)$huesped['procedencia'] : implode(', ', $rdGuestOriginParts);
$rdGuestPhoneDial = preg_replace('/[^\d+]/', '', $rdGuestPhone);
$rdGuestPhoneDigits = preg_replace('/\D+/', '', $rdGuestPhone);
$rdGuestWhatsappPhone = $rdGuestPhoneDigits;
if (strlen($rdGuestWhatsappPhone) === 10) {
    $rdGuestWhatsappPhone = '52' . $rdGuestWhatsappPhone;
}
$rdGuestTelHref = $rdGuestPhoneDial !== '' ? 'tel:' . $rdGuestPhoneDial : '';
$rdGuestWhatsappHref = $rdGuestWhatsappPhone !== '' ? 'https://wa.me/' . $rdGuestWhatsappPhone : '';
$rdGuestIdDocScore = function($documento) {
    $haystack = strtolower(trim(implode(' ', [
        (string)($documento['titulo'] ?? ''),
        (string)($documento['nombre_original'] ?? ''),
        (string)($documento['descripcion'] ?? ''),
        (string)($documento['etiquetas'] ?? ''),
        (string)($documento['tipo_nombre'] ?? ''),
        (string)($documento['tipo_clave'] ?? ''),
        (string)($documento['relacion'] ?? ''),
    ])));

    foreach (['ine', 'identificacion', 'identificación', 'id oficial', 'oficial', 'licencia', 'pasaporte'] as $needle) {
        if (strpos($haystack, $needle) !== false) {
            return 0;
        }
    }

    return 10;
};
$rdGuestDocPreviewKind = function($documento) {
    $mime = strtolower((string)($documento['mime_type'] ?? ''));
    $name = strtolower((string)(($documento['nombre_original'] ?? '') ?: ($documento['titulo'] ?? '')));

    if (strpos($mime, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|webp)$/', $name)) {
        return 'image';
    }

    if (strpos($mime, 'pdf') !== false || substr($name, -4) === '.pdf') {
        return 'pdf';
    }

    return 'file';
};
usort($rdGuestDocuments, function($a, $b) use ($rdGuestIdDocScore, $rdGuestDocPreviewKind) {
    $scoreA = $rdGuestIdDocScore($a);
    $scoreB = $rdGuestIdDocScore($b);
    if ($scoreA !== $scoreB) {
        return $scoreA <=> $scoreB;
    }

    $previewRank = ['image' => 0, 'pdf' => 1, 'file' => 2];
    $rankA = $previewRank[$rdGuestDocPreviewKind($a)] ?? 2;
    $rankB = $previewRank[$rdGuestDocPreviewKind($b)] ?? 2;
    if ($rankA !== $rankB) {
        return $rankA <=> $rankB;
    }

    return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
});
$rdGuestPrimaryDoc = $rdGuestDocuments[0] ?? null;
$rdGuestDocCount = count($rdGuestDocuments);
$rdGuestDocPanelClass = 'rdv3-guest-docs ' . ($rdGuestDocCount > 1 ? 'is-gallery' : ($rdGuestDocCount === 1 ? 'is-single' : 'is-empty'));
$rdGuestDocEntityId = (int)($huesped['id'] ?? $reservacion['huesped_id'] ?? 0);
$rdGuestDocEntityQuery = $rdGuestDocEntityId > 0
    ? '?entidad_tipo=huesped&entidad_id=' . $rdGuestDocEntityId
    : '';
$rdEntryTime = trim((string)($reservacion['hora_entrada'] ?? '15:00'));
$rdExitTime = trim((string)($reservacion['hora_salida'] ?? '12:00'));
$rdCreatedAt = $reservacion['created_at'] ?? $reservacion['fecha_creacion'] ?? null;
$rdDocEntityTipo = trim((string)($rdDocsContext['tipo'] ?? ''));
$rdDocEntityId = (int)($rdDocsContext['id'] ?? 0);
$rdDocEntityQuery = ($rdDocEntityTipo !== '' && $rdDocEntityId > 0)
    ? '?entidad_tipo=' . rawurlencode($rdDocEntityTipo) . '&entidad_id=' . $rdDocEntityId
    : '';
$rdDocTotalBytes = 0;
foreach ($rdDocuments as $rdDocTotalRow) {
    $rdDocTotalBytes += (int)($rdDocTotalRow['size_bytes'] ?? 0);
}
?>

<style>
.rdv3 {
    --rdv3-primary: var(--brand-primary, #3A4656);
    --rdv3-primary-2: var(--brand-secondary, #2C3542);
    --rdv3-accent: var(--brand-accent, #9D8968);
    --rdv3-bg: #FAFAF8;
    --rdv3-ink: #3F3F3F;
    --rdv3-muted: #808080;
    --rdv3-muted-2: #686868;
    --rdv3-line: rgba(60, 70, 86, .06);
    --rdv3-card: rgba(255, 255, 255, .99);
    --rdv3-blue: #6B7F99;
    --rdv3-green: #608B70;
    --rdv3-violet: #8B8BA3;
    --rdv3-gold: #9D8968;
    --rdv3-cyan: #6B9FA0;
    min-height: 100dvh;
    margin: 0;
    background: var(--rdv3-bg);
    color: var(--rdv3-ink);
    font-family: Manrope, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    overflow-x: hidden;
}
.rdv3 *, .rdv3 *::before, .rdv3 *::after { box-sizing: border-box; }
.rdv3 a { color: inherit; text-decoration: none; }
.rdv3 button, .rdv3 a { transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease, color .18s ease; }
.rdv3 button:hover, .rdv3 a:hover { transform: translateY(-1px); }
.rdv3 button:active, .rdv3 a:active { transform: translateY(0); }
.rdv3-shell { width: 100%; min-height: 100dvh; }
.rdv3-main { min-width: 0; width: 100%; padding: 1rem !important; }
.rdv3-page { width: 100%; max-width: none !important; margin: 0 !important; padding: 0 !important; }
.rdv3-topbar { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.rdv3-back {
    width: 40px;
    height: 40px;
    border-radius: 13px;
    display: grid;
    place-items: center;
    border: 1px solid var(--rdv3-line);
    background: #fff;
    color: var(--rdv3-primary);
    box-shadow: 0 8px 18px rgba(36, 48, 74, .07);
}
.rdv3-crumbs { color: #8a93a7; font-size: .78rem; font-weight: 700; }
.rdv3-crumbs strong { color: var(--rdv3-primary); }
.rdv3-topbar-copy { min-width: 0; display: grid; gap: 3px; }
.rdv3-topbar-hotel { color: #8a93a7; font-size: .72rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rdv3-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 22px;
    align-items: center;
    min-height: 112px;
    padding: 26px 30px;
    border-radius: 24px;
    background: linear-gradient(135deg, var(--rdv3-primary), color-mix(in srgb, var(--rdv3-primary) 88%, #4A5A72));
    color: #fff;
    box-shadow: 0 18px 38px rgba(23, 35, 66, .25);
}
.rdv3-titleline { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
.rdv3-titleline h1 {
    margin: 0;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(2rem, 2vw, 2.75rem);
    line-height: .95;
    font-weight: 600;
    letter-spacing: 0;
}
.rdv3-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 30px;
    padding: 0 14px;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 700;
}
.rdv3-status::before { content: ""; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
.rdv3-status--ok { background: rgba(50, 185, 122, .19); color: #82e0ae; border: 1px solid rgba(130, 224, 174, .2); }
.rdv3-status--wait { background: rgba(245, 181, 64, .18); color: #f2cf8c; border: 1px solid rgba(242, 207, 140, .2); }
.rdv3-status--danger { background: rgba(239, 68, 68, .18); color: #fecaca; border: 1px solid rgba(254, 202, 202, .2); }
.rdv3-meta { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 16px; color: rgba(255, 255, 255, .72); font-size: .82rem; font-weight: 600; }
.rdv3-meta span { display: inline-flex; align-items: center; gap: 7px; }
.rdv3-hero-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; min-width: 0; }
.rdv3-btn {
    border: 0;
    min-height: 42px;
    padding: 0 16px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-size: .84rem;
    font-weight: 600;
    cursor: pointer;
}
.rdv3-btn-primary { background: #22b77a; color: #fff; box-shadow: 0 12px 24px rgba(34, 183, 122, .25); }
.rdv3-btn-primary:hover { background: #1fa66f; }
.rdv3-btn-checkout {
    background: linear-gradient(135deg, #D97706, #B45309);
    color: #fff;
    box-shadow: 0 12px 24px rgba(180, 83, 9, .28);
}
.rdv3-btn-checkout:hover { background: linear-gradient(135deg, #C7651D, #92400E); }
.rdv3-btn-ghost { background: rgba(255, 255, 255, .11); color: #fff; border: 1px solid rgba(255, 255, 255, .24); }
.rdv3-btn-calendar {
    background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-accent) 28%, #fff), color-mix(in srgb, var(--rdv3-accent) 16%, #fff));
    color: color-mix(in srgb, var(--rdv3-primary) 88%, #111827);
    border: 1px solid color-mix(in srgb, var(--rdv3-accent) 34%, rgba(255, 255, 255, .5));
    box-shadow: 0 12px 24px rgba(0, 0, 0, .12), inset 0 1px 0 rgba(255, 255, 255, .45);
}
.rdv3-btn-calendar i { color: color-mix(in srgb, var(--rdv3-accent) 84%, var(--rdv3-primary)); }
.rdv3-btn-calendar:hover {
    background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-accent) 38%, #fff), color-mix(in srgb, var(--rdv3-accent) 22%, #fff));
}
.rdv3-btn-rooms {
    background: linear-gradient(135deg, color-mix(in srgb, #6252d8 16%, #fff), color-mix(in srgb, #6252d8 9%, #fff));
    color: color-mix(in srgb, var(--rdv3-primary) 88%, #111827);
    border: 1px solid color-mix(in srgb, #6252d8 26%, rgba(255, 255, 255, .5));
    box-shadow: 0 12px 24px rgba(0, 0, 0, .12), inset 0 1px 0 rgba(255, 255, 255, .45);
}
.rdv3-btn-rooms i { color: #6252d8; }
.rdv3-btn-rooms:hover {
    background: linear-gradient(135deg, color-mix(in srgb, #6252d8 24%, #fff), color-mix(in srgb, #6252d8 14%, #fff));
}
.rdv3-btn-danger {
    background: linear-gradient(135deg, #B91C1C, #DC2626);
    color: #fff;
    border: 1px solid rgba(254, 202, 202, .45);
    box-shadow: 0 12px 24px rgba(185, 28, 28, .25);
}
.rdv3-btn-danger:hover { background: linear-gradient(135deg, #991B1B, #C81E1E); }
.rdv3-topbar-menu,
.rdv3-mobile-bottom { display: none; }
.rdv3-layout { display: grid; grid-template-columns: minmax(0, 1fr) clamp(318px, 24vw, 360px); gap: 26px; margin-top: 26px; align-items: start; }
.rdv3-left, .rdv3-right { display: grid; gap: 24px; min-width: 0; }
.rdv3-right { position: static; align-self: start; }
.rdv3-card {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 18%, rgba(255,255,255,.8));
    background: var(--rdv3-card);
    box-shadow: 0 18px 38px rgba(61, 47, 22, .09);
}
.rdv3-card::before {
    content: "";
    position: absolute;
    top: -58px;
    right: -44px;
    width: 184px;
    height: 138px;
    border-radius: 999px;
    background: transparent;
    opacity: 0;
    pointer-events: none;
}
.rdv3-card--stay { --rdv3-card-accent: var(--rdv3-blue); }
.rdv3-card--rooms { --rdv3-card-accent: var(--rdv3-violet); }
.rdv3-card--guest { --rdv3-card-accent: var(--rdv3-cyan); }
.rdv3-card--docs { --rdv3-card-accent: var(--rdv3-gold); }
.rdv3-side-card--actions { --rdv3-card-accent: var(--rdv3-blue); }
.rdv3-side-card--timeline { --rdv3-card-accent: var(--rdv3-gold); }
.rdv3-side-card--payment { --rdv3-card-accent: var(--rdv3-green); }
.rdv3-side-card--notes { --rdv3-card-accent: var(--rdv3-blue); }
.rdv3-card-header {
    position: relative;
    z-index: 1;
    min-height: 0;
    padding: 28px 42px 14px;
    border-bottom: 0;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.rdv3-heading { display: inline-flex; align-items: center; gap: 14px; min-width: 0; flex: 1 1 auto; }
.rdv3-icon { width: 40px; height: 40px; flex: 0 0 40px; display: grid; place-items: center; border-radius: 12px; color: var(--rdv3-card-accent, var(--rdv3-primary)); background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 12%, #fff); box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 18%, transparent); }
.rdv3-icon--blue { color: #3b82f6; background: #eaf2ff; }
.rdv3-icon--green { color: #2dbd79; background: #e7f8ef; }
.rdv3-icon--violet { color: #6252d8; background: #eeebff; }
.rdv3-card-title { margin: 0; color: var(--rdv3-primary); font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 1.22rem; line-height: 1.1; font-weight: 600; overflow-wrap: anywhere; }
.rdv3-card-body { position: relative; z-index: 1; padding: 12px 42px 32px; }
.rdv3-stay { display: grid; grid-template-columns: minmax(0, 1fr) 82px minmax(0, 1fr); border: 1px solid color-mix(in srgb, var(--rdv3-blue) 12%, transparent); border-radius: 16px; overflow: hidden; background: #FFFFFF; }
.rdv3-date { padding: 18px; }
.rdv3-date:first-child { background: transparent; }
.rdv3-date:last-child { background: transparent; }
.rdv3-date:last-child { text-align: right; }
.rdv3-label { display: block; color: var(--rdv3-muted-2); font-size: .69rem; font-weight: 950; text-transform: uppercase; letter-spacing: .03em; }
.rdv3-label--green { color: #26a96f; }
.rdv3-date strong { display: block; margin-top: 6px; color: var(--rdv3-blue); font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 1.35rem; line-height: 1; }
.rdv3-date span { display: block; margin-top: 6px; color: #8992a5; font-size: .78rem; font-weight: 600; }
.rdv3-nights { display: grid; place-items: center; text-align: center; border-left: 1px solid color-mix(in srgb, var(--rdv3-blue) 12%, transparent); border-right: 1px solid color-mix(in srgb, var(--rdv3-blue) 12%, transparent); color: var(--rdv3-muted); background: #FAFAF8; }
.rdv3-nights b { display: block; font-size: 1.35rem; line-height: 1; }
.rdv3-nights span { display: block; margin-top: 5px; color: var(--rdv3-muted-2); font-size: .62rem; font-weight: 950; text-transform: uppercase; }
.rdv3-total { margin-top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 86px; padding: 17px 18px; border-radius: 16px; border: 1px solid color-mix(in srgb, var(--rdv3-green) 16%, transparent); background: color-mix(in srgb, var(--rdv3-green) 5%, #fff); }
.rdv3-total .rdv3-amount { margin-top: 5px; font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 1.7rem; font-weight: 600; color: var(--rdv3-primary); }
.rdv3-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 28px; padding: 0 12px; border-radius: 999px; background: #fff; color: #37b77d; font-size: .74rem; font-weight: 950; }
.rdv3-pill.is-paid { color: #15835A; background: rgba(255,255,255,.86); }
.rdv3-pill.is-pending { color: color-mix(in srgb, var(--rdv3-gold) 86%, #5f481d); background: #fff8ec; }
.rdv3-res-note { margin-top: 16px; min-height: 42px; display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 24%, transparent); background: color-mix(in srgb, var(--rdv3-accent) 12%, #fff); color: color-mix(in srgb, var(--rdv3-accent) 72%, #5c4927); font-size: .84rem; font-weight: 600; }
.rdv3-badge { display: inline-flex; align-items: center; gap: 7px; min-height: 28px; padding: 0 11px; border-radius: 999px; background: color-mix(in srgb, var(--rdv3-accent) 14%, #fff); color: var(--rdv3-accent); font-size: .75rem; font-weight: 950; }
.rdv3-header-badges { display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
.rdv3-badge--count { color: var(--rdv3-gold); background: color-mix(in srgb, var(--rdv3-gold) 12%, #fff); }
a.rdv3-badge--edit { color: #6252d8; background: #eeebff; text-decoration: none; cursor: pointer; transition: background .14s; }
a.rdv3-badge--edit:hover { background: #e3defc; }
.rdv3-card--rooms .rdv3-card-header { padding-bottom: 14px; }
.rdv3-card--rooms .rdv3-card-body { padding-top: 10px; padding-bottom: 30px; }
.rdv3-rooms {
    display: grid;
    gap: 16px;
    overflow: visible;
    border: 0;
    border-radius: 0;
    background: transparent;
}
.rdv3-room,
.rdv3-room:nth-child(4n+1),
.rdv3-room:nth-child(4n+2),
.rdv3-room:nth-child(4n+3),
.rdv3-room:nth-child(4n+4) {
    --room-accent: var(--rdv3-accent);
}
.rdv3-room {
    position: relative;
    min-height: 0;
    border: 1px solid #E3D5BF;
    border-radius: 16px;
    background: linear-gradient(180deg, #FFFDF9 0%, #FBF6EE 100%);
    padding: 18px 20px 18px 24px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(156px, auto);
    grid-template-areas:
        "identity price"
        "metrics metrics";
    gap: 16px 22px;
    align-items: stretch;
}
.rdv3-room + .rdv3-room { border-top: 1px solid #E3D5BF; }
.rdv3-room:not(:last-child) { margin-bottom: 0; }
.rdv3-room::before {
    content: "";
    position: absolute;
    left: -1px;
    top: 14px;
    bottom: 14px;
    width: 4px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rdv3-accent) 54%, #CBB78E);
}
.rdv3-room.is-courtesy { --room-accent: var(--rdv3-gold); }
.rdv3-room-top { display: contents; }
.rdv3-room-top > .min-w-0 {
    grid-area: identity;
    display: grid;
    grid-template-columns: minmax(58px, auto) minmax(0, 1fr);
    grid-template-rows: auto auto;
    column-gap: 14px;
    align-items: center;
    min-width: 0;
}
.rdv3-room-number {
    grid-row: 1 / 3;
    box-sizing: border-box;
    min-width: 58px;
    max-width: 150px;
    min-height: 58px;
    padding: 10px 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border-radius: 15px;
    border: 1px solid #E6DBC8;
    background: linear-gradient(180deg, #FFFDF9, #FBF5EA);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
    color: var(--rdv3-primary);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(1rem, .55rem + 1vw, 1.5rem);
    line-height: 1.02;
    font-weight: 700;
    letter-spacing: .01em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: uppercase;
}
.rdv3-room-type { color: var(--rdv3-primary); font-size: .96rem; font-weight: 950; margin-top: 0; overflow-wrap: anywhere; }
.rdv3-room-sub { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 8px; }
.rdv3-room-price {
    grid-area: price;
    align-self: stretch;
    min-width: 156px;
    padding: 12px 0 12px 18px;
    border-left: 1px solid #E6DBC8;
    display: flex;
    flex-direction: column;
    justify-content: center;
    color: var(--rdv3-primary);
    font-size: 1.16rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    text-align: right;
}
.rdv3-room-price small { display: block; margin-top: 6px; color: #8a93a5; font-size: .63rem; font-weight: 900; text-transform: uppercase; letter-spacing: .03em; }
.rdv3-room-metrics {
    grid-area: metrics;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    overflow: visible;
    border: 0;
    border-radius: 0;
    background: transparent;
}
.rdv3-room-metric { min-height: 58px; padding: 11px 12px; border-radius: 12px; background: #F7F1E8; border: 1px solid #E7D9C4; }
.rdv3-room-metric + .rdv3-room-metric { border-left: 1px solid #E7D9C4; }
.rdv3-room-metric small { display: block; color: #8790a1; font-size: .62rem; font-weight: 950; text-transform: uppercase; letter-spacing: .025em; }
.rdv3-room-metric b { display: block; margin-top: 5px; color: var(--rdv3-primary); font-size: .86rem; font-weight: 950; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
.rdv3-room-discount,
.rdv3-room-net {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    min-height: 40px;
    padding: 10px 12px;
    border-radius: 12px;
    font-size: .78rem;
    font-weight: 900;
}
.rdv3-room-discount {
    grid-column: 1 / -1;
    background: color-mix(in srgb, #B4392B 8%, #fff);
    border: 1px solid color-mix(in srgb, #B4392B 18%, var(--rdv3-line));
    color: color-mix(in srgb, #B4392B 84%, var(--rdv3-primary));
}
.rdv3-room-discount span,
.rdv3-room-net span {
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    overflow-wrap: anywhere;
}
.rdv3-room-discount strong,
.rdv3-room-net strong {
    flex: 0 0 auto;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.rdv3-room-net {
    grid-column: 1 / -1;
    background: color-mix(in srgb, var(--rdv3-green) 8%, #fff);
    border: 1px solid color-mix(in srgb, var(--rdv3-green) 18%, var(--rdv3-line));
    color: var(--rdv3-primary);
}
.rdv3-room-net span { color: #6f7a8d; }
.rdv3-room-net strong { color: color-mix(in srgb, var(--rdv3-green) 82%, var(--rdv3-primary)); }
.rdv3-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.rdv3-tag { min-height: 22px; display: inline-flex; align-items: center; gap: 6px; padding: 0 8px; border-radius: 7px; background: rgba(255,255,255,.86); border: 1px solid color-mix(in srgb, var(--room-accent, var(--rdv3-accent)) 14%, var(--rdv3-line)); color: #6f7a91; font-size: .66rem; font-weight: 800; }
.rdv3-link { color: var(--rdv3-accent); font-size: .78rem; font-weight: 950; border: 0; background: transparent; cursor: pointer; }
.rdv3-card-header > .rdv3-link {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 12px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 9%, #fff);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 13%, transparent);
}
.rdv3-card--guest .rdv3-card-body { padding-bottom: 18px; }
.rdv3-guest-head { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; padding: 8px 10px 12px; border-radius: 15px; background: linear-gradient(90deg, rgba(19,168,198,.08), transparent); }
.rdv3-avatar { flex: 0 0 auto; width: 56px; height: 56px; display: grid; place-items: center; border-radius: 14px; background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-primary) 85%, #7258ff), #6252d8); color: #fff; font-size: 1.15rem; font-weight: 950; }
.rdv3-guest-name { margin: 0; color: var(--rdv3-primary); font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 1.55rem; font-weight: 600; line-height: 1; }
.rdv3-guest-sub { margin-top: 5px; color: #7e879b; font-size: .83rem; font-weight: 600; }
.rdv3-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.rdv3-info { --info-accent: var(--rdv3-blue); min-height: 62px; padding: 12px 13px; display: flex; align-items: center; gap: 12px; border: 1px solid color-mix(in srgb, var(--info-accent) 10%, transparent); border-radius: 12px; background: #FFFFFF; min-width: 0; }
.rdv3-info:nth-child(2) { --info-accent: var(--rdv3-cyan); }
.rdv3-info:nth-child(3) { --info-accent: var(--rdv3-violet); }
.rdv3-info:nth-child(4) { --info-accent: var(--rdv3-green); }
.rdv3-info i { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; color: var(--info-accent); background: #fff; border: 1px solid color-mix(in srgb, var(--info-accent) 15%, var(--rdv3-line)); }
.rdv3-info > div { min-width: 0; }
.rdv3-info small { display: block; color: var(--rdv3-muted-2); font-size: .64rem; font-weight: 950; text-transform: uppercase; }
.rdv3-info b { display: block; margin-top: 2px; color: #3b4660; font-size: .82rem; font-weight: 950; overflow-wrap: anywhere; }
.rdv3-info.is-empty b { color: #97A0B2; font-weight: 850; }
.rdv3-contact-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin: 13px 0 17px; }
.rdv3-contact-action {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 18%, transparent);
    background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-cyan) 9%, #fff), rgba(255,255,255,.88));
    color: var(--rdv3-primary);
    font-size: .82rem;
    font-weight: 950;
    white-space: nowrap;
}
.rdv3-contact-action i { color: var(--rdv3-cyan); }
.rdv3-contact-action.is-whatsapp { border-color: rgba(20,134,83,.18); background: linear-gradient(135deg, rgba(20,134,83,.1), rgba(255,255,255,.9)); }
.rdv3-contact-action.is-whatsapp i { color: #148653; }
.rdv3-contact-action.is-disabled { color: #9AA3B4; background: #F8FAFC; border-style: dashed; cursor: not-allowed; pointer-events: none; }
.rdv3-contact-action.is-disabled i { color: #AAB3C2; }
.rdv3-guest-docs {
    width: 100%;
    max-width: 100%;
    margin-top: 18px;
    padding: 16px;
    border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 18%, transparent);
    border-radius: 17px;
    background:
        radial-gradient(circle at 0 0, color-mix(in srgb, var(--rdv3-cyan) 13%, transparent), transparent 38%),
        linear-gradient(135deg, color-mix(in srgb, var(--rdv3-cyan) 7%, #fff), rgba(255,255,255,.82));
}
.rdv3-guest-docs.is-gallery { width: 100%; max-width: 100%; }
.rdv3-guest-docs.is-empty { width: 100%; max-width: 100%; }
.rdv3-guest-docs-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.rdv3-guest-docs-title { display: flex; align-items: center; gap: 10px; min-width: 0; color: var(--rdv3-primary); font-size: .85rem; font-weight: 950; }
.rdv3-guest-docs-title i { width: 31px; height: 31px; display: grid; place-items: center; border-radius: 10px; color: var(--rdv3-cyan); background: #fff; border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 18%, var(--rdv3-line)); }
.rdv3-guest-docs.is-single .rdv3-guest-docs-title span { max-width: 176px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rdv3-guest-docs-actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
.rdv3-guest-docs-grid { display: flex; flex-wrap: wrap; gap: 12px; align-items: stretch; }
.rdv3-guest-doc-feature { width: min(100%, 430px); min-height: 132px; border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 17%, transparent); border-radius: 15px; background: #fff; overflow: hidden; position: relative; display: grid; grid-template-columns: minmax(118px, 148px) minmax(0, 1fr); gap: 12px; padding: 10px; cursor: pointer; }
.rdv3-guest-doc-feature.is-revealable { cursor: default; }
.rdv3-guest-docs.is-gallery .rdv3-guest-doc-feature { width: min(100%, 460px); min-height: 142px; }
.rdv3-guest-doc-feature:hover { border-color: color-mix(in srgb, var(--rdv3-cyan) 42%, transparent); box-shadow: 0 18px 32px -26px var(--rdv3-primary); }
.rdv3-guest-doc-thumb {
    position: relative;
    min-height: 112px;
    overflow: hidden;
    display: grid;
    align-content: space-between;
    gap: 12px;
    padding: 14px;
    border-radius: 12px;
    color: #fff;
    background:
        linear-gradient(135deg, rgba(255,255,255,.12), transparent 38%),
        linear-gradient(145deg, var(--rdv3-primary), color-mix(in srgb, var(--rdv3-primary) 74%, #111827));
}
.rdv3-guest-doc-thumb::after {
    content: "";
    position: absolute;
    right: -26px;
    bottom: -34px;
    width: 96px;
    height: 96px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rdv3-accent) 34%, transparent);
}
.rdv3-guest-doc-brand { position: relative; z-index: 1; font-size: .68rem; font-weight: 950; letter-spacing: .1em; }
.rdv3-guest-doc-seal { position: relative; z-index: 1; width: 42px; height: 42px; display: grid; place-items: center; border-radius: 14px; color: #fff; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2); }
.rdv3-doc-reveal { appearance: none; -webkit-appearance: none; border: 0; width: 100%; margin: 0; text-align: left; font-family: inherit; line-height: inherit; cursor: pointer; text-decoration: none; }
.rdv3-doc-reveal:focus-visible { outline: 3px solid color-mix(in srgb, var(--rdv3-cyan) 44%, transparent); outline-offset: 3px; }
.rdv3-guest-doc-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 12px; opacity: 0; transition: opacity .22s ease; z-index: 2; }
.rdv3-guest-doc-img { -webkit-user-drag: none; -webkit-touch-callout: none; user-select: none; pointer-events: none; }
.rdv3-doc-reveal:hover .rdv3-guest-doc-img,
.rdv3-guest-doc-feature.is-revealed .rdv3-guest-doc-img { opacity: 1; }
.rdv3-doc-reveal-hint { position: absolute; top: 10px; right: 10px; z-index: 3; width: 32px; height: 32px; display: inline-grid; place-items: center; padding: 0; border-radius: 999px; background: rgba(18,28,42,.68); color: #fff; font-size: .78rem; font-weight: 900; backdrop-filter: blur(6px); transition: background .18s ease, transform .18s ease; }
.rdv3-doc-reveal:hover .rdv3-doc-reveal-hint,
.rdv3-doc-reveal:focus-visible .rdv3-doc-reveal-hint { transform: translateY(-1px); background: var(--rdv3-cyan); }
.rdv3-guest-doc-feature.is-revealed .rdv3-doc-reveal-hint { background: var(--rdv3-cyan); }
.rdv3-doc-open { margin-top: 5px; display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; font-weight: 900; color: var(--rdv3-cyan); text-decoration: none; width: max-content; }
.rdv3-doc-open:hover { text-decoration: underline; }
.rdv3-guest-doc-copy { min-width: 0; display: grid; align-content: center; gap: 7px; color: var(--rdv3-primary); }
.rdv3-guest-doc-copy b { display: block; font-size: .94rem; font-weight: 950; line-height: 1.2; overflow-wrap: anywhere; }
.rdv3-guest-doc-copy small { display: block; color: #8790a4; font-size: .72rem; font-weight: 760; line-height: 1.35; }
.rdv3-guest-doc-copy .rdv3-doc-mini-state { width: fit-content; min-height: 25px; display: inline-flex; align-items: center; gap: 6px; padding: 0 10px; border-radius: 999px; background: #e7f8ef; color: #15835A; font-size: .68rem; font-weight: 950; }
.rdv3-guest-doc-file {
    min-height: 122px;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 9px;
    padding: 18px;
    color: var(--rdv3-primary);
    background: linear-gradient(135deg, #fff, color-mix(in srgb, var(--rdv3-cyan) 7%, #fff));
    text-align: center;
}
.rdv3-guest-doc-file i { width: 50px; height: 50px; display: grid; place-items: center; border-radius: 15px; color: #fff; background: linear-gradient(135deg, var(--rdv3-cyan), var(--rdv3-primary)); box-shadow: 0 14px 24px -18px var(--rdv3-primary); }
.rdv3-guest-docs.is-gallery .rdv3-guest-doc-file { min-height: 136px; }
.rdv3-guest-doc-file b { font-size: .86rem; font-weight: 950; overflow-wrap: anywhere; }
.rdv3-guest-doc-caption { position: absolute; left: 10px; right: 10px; bottom: 10px; padding: 9px 10px; border-radius: 11px; background: rgba(18, 28, 42, .72); color: #fff; backdrop-filter: blur(12px); }
.rdv3-guest-doc-caption b { display: block; font-size: .8rem; line-height: 1.15; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rdv3-guest-doc-caption small { display: block; margin-top: 3px; color: rgba(255,255,255,.76); font-size: .66rem; font-weight: 800; }
.rdv3-guest-doc-list { flex: 1 1 260px; min-width: 220px; display: grid; gap: 8px; align-content: start; }
.rdv3-guest-doc-chip {
    min-height: 46px;
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    align-items: center;
    gap: 9px;
    padding: 8px 10px;
    border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 13%, transparent);
    border-radius: 12px;
    background: rgba(255,255,255,.86);
    color: var(--rdv3-primary);
}
.rdv3-guest-doc-chip:hover { border-color: color-mix(in srgb, var(--rdv3-cyan) 34%, transparent); box-shadow: 0 14px 28px -24px var(--rdv3-primary); }
.rdv3-guest-doc-chip-icon { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; color: var(--rdv3-cyan); background: color-mix(in srgb, var(--rdv3-cyan) 10%, #fff); border: 1px solid color-mix(in srgb, var(--rdv3-cyan) 17%, transparent); }
.rdv3-guest-doc-chip-title { display: block; font-size: .76rem; font-weight: 950; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rdv3-guest-doc-chip-meta { display: block; margin-top: 2px; color: #8790a4; font-size: .66rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rdv3-guest-doc-empty {
    min-height: 128px;
    display: grid;
    place-items: center;
    gap: 10px;
    padding: 20px;
    border: 1px dashed color-mix(in srgb, var(--rdv3-cyan) 25%, transparent);
    border-radius: 14px;
    background: rgba(255,255,255,.68);
    color: #7f8ba0;
    text-align: center;
    font-size: .84rem;
    font-weight: 760;
}
.rdv3-guest-doc-empty i { color: color-mix(in srgb, var(--rdv3-cyan) 64%, #aab4c3); font-size: 1.35rem; }
.rdv3-subhead { margin: 17px 0 9px; display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--rdv3-primary); font-size: .84rem; font-weight: 950; }
.rdv3-subhead span { display: inline-flex; align-items: center; gap: 8px; }
.rdv3-subhead--stack { align-items: flex-start; margin-top: 20px; }
.rdv3-subhead-copy { min-width: 0; display: grid; gap: 4px; }
.rdv3-subhead-note { margin: 0; color: #8790A4; font-size: .74rem; font-weight: 650; line-height: 1.35; }
.rdv3-car-accent { color: #1ca8c7; }
.rdv3-vehicle-list { display: grid; gap: 10px; }
.rdv3 #listaVehiculos > .text-center {
    min-height: 92px;
    padding: 18px;
    border: 1px dashed rgba(19,168,198,.28);
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(19,168,198,.08), rgba(255,255,255,.55));
    color: #7c879a;
    display: grid;
    place-items: center;
    align-content: center;
}
.rdv3 #listaVehiculos > .text-center i { color: rgba(19,168,198,.38) !important; }
.rdv3 #listaVehiculos > .text-center p { margin: 7px 0 0; font-weight: 850; }
.rdv3 #listaVehiculos > p,
.rdv3 #listaNotas > p {
    min-height: 74px;
    margin: 0;
    padding: 18px;
    border: 1px dashed color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-blue)) 20%, transparent);
    border-radius: 14px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-blue)) 7%, #fff), rgba(255,255,255,.62));
    color: #7f8ba0;
    font-weight: 600;
    display: grid;
    place-items: center;
}
.rdv3 #listaVehiculos .space-y-2 { display: grid; gap: 10px; }
.rdv3 #listaVehiculos .vehiculo-item {
    border: 1px solid rgba(19,168,198,.18);
    border-radius: 13px;
    background: linear-gradient(135deg, rgba(19,168,198,.08), rgba(255,255,255,.78));
    box-shadow: inset 3px 0 0 rgba(19,168,198,.55);
}
.rdv3 #listaVehiculos .vehiculo-icon {
    background: #e6f8fb;
    color: var(--rdv3-cyan);
}
.rdv3-vehicle { min-height: 56px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); border-radius: 12px; background: rgba(255, 252, 247, .72); padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.rdv3-vehicle-main { display: flex; align-items: center; gap: 12px; min-width: 0; }
.rdv3-vehicle-icon { width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center; color: #1ca8c7; background: #e6f8fb; }
.rdv3-vehicle-title { color: var(--rdv3-primary); font-size: .84rem; font-weight: 950; overflow-wrap: anywhere; }
.rdv3-vehicle-sub { color: #8790a4; font-size: .72rem; font-weight: 600; }
.rdv3-vehicle-status { display: none; margin-top: 10px; border-radius: 10px; border: 1px solid transparent; padding: 8px 10px; font-size: .74rem; font-weight: 850; line-height: 1.35; }
.rdv3-vehicle-status.is-visible { display: block; }
.rdv3-vehicle-status.is-info { border-color: #BFD4F5; background: #F0F6FF; color: #255AA7; }
.rdv3-vehicle-status.is-warning { border-color: #F4D38E; background: #FFF8E8; color: #9A5F10; }
.rdv3-vehicle-status.is-error { border-color: #FECACA; background: #FEF2F2; color: #B42318; }
.rdv3-vehicle-status.is-success { border-color: #BFE9D3; background: #F0FBF5; color: #15835A; }
.rdv3-plate { padding: 7px 10px; border-radius: 7px; background: var(--rdv3-primary); color: #fff; font-size: .73rem; font-weight: 950; white-space: nowrap; }
.rdv3-side-card .rdv3-card-header { min-height: 0; padding: 24px 32px 12px; }
.rdv3-side-card .rdv3-heading { gap: 12px; }
.rdv3-side-card .rdv3-icon { width: 34px; height: 34px; flex-basis: 34px; border-radius: 11px; }
.rdv3-side-card .rdv3-card-body { padding: 10px 32px 22px; }
.rdv3-actions { display: grid; gap: 10px; }
.rdv3-action { --action-accent: var(--rdv3-blue); width: 100%; min-height: 56px; border: 1px solid color-mix(in srgb, var(--action-accent) 16%, transparent); border-radius: 12px; background: linear-gradient(135deg, color-mix(in srgb, var(--action-accent) 7%, #fff), rgba(255,252,247,.78)); color: var(--rdv3-primary); display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 13px; cursor: pointer; font-size: .82rem; font-weight: 950; box-shadow: inset 3px 0 0 color-mix(in srgb, var(--action-accent) 72%, transparent); }
.rdv3-action:nth-child(2) { --action-accent: var(--rdv3-gold); }
.rdv3-action:nth-child(3) { --action-accent: var(--rdv3-blue); }
.rdv3-action:nth-child(4) { --action-accent: var(--rdv3-violet); }
.rdv3-action:nth-child(5) { --action-accent: #6d7689; }
.rdv3-action-left { display: inline-flex; align-items: center; gap: 12px; min-width: 0; }
.rdv3-action-icon { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; background: #fff; color: var(--rdv3-primary); }
.rdv3-action-icon.is-green { color: #24b777; background: #e7f8ef; }
.rdv3-action-icon.is-checkout { color: #C7651D; background: #FFF2DF; }
.rdv3-action-icon.is-gold { color: var(--rdv3-accent); background: color-mix(in srgb, var(--rdv3-accent) 15%, #fff); }
.rdv3-action-icon.is-blue { color: #3b82f6; background: #eaf2ff; }
.rdv3-action-icon.is-violet { color: #6252d8; background: #eeebff; }
.rdv3-action-icon.is-gray { color: #6d7689; background: #f4f5f7; }
.rdv3-action-icon.is-danger { color: #B91C1C; background: #FEE2E2; }
.rdv3-action--checkout {
    --action-accent: #C7651D;
    border-color: rgba(199, 101, 29, .24);
    background: linear-gradient(135deg, #FFF6EA, #FFFDF9);
    box-shadow: inset 3px 0 0 rgba(199, 101, 29, .68), 0 12px 22px -20px rgba(199, 101, 29, .55);
}
.rdv3-action--checkout i.fa-chevron-right { color: rgba(199, 101, 29, .62); }
.rdv3-action--danger {
    --action-accent: #B91C1C;
    margin-top: 4px;
    border-color: rgba(185, 28, 28, .28);
    background: linear-gradient(135deg, #FFF7F7, #FFFDF9);
    color: #991B1B;
}
.rdv3-action i.fa-chevron-right { color: var(--rdv3-muted-2); font-size: .72rem; }
.rdv3-action--danger i.fa-chevron-right { color: rgba(153, 27, 27, .55); }
.rdv3-timeline { position: relative; display: grid; gap: 15px; padding-left: 12px; }
.rdv3-timeline::before { content: ""; position: absolute; left: 5px; top: 7px; bottom: 7px; width: 2px; background: color-mix(in srgb, var(--rdv3-accent) 18%, transparent); }
.rdv3-step { position: relative; padding-left: 18px; }
.rdv3-step::before { content: ""; position: absolute; left: -12px; top: 4px; width: 14px; height: 14px; border-radius: 999px; background: #fff; border: 3px solid var(--step-color, #d4d8df); }
.rdv3-step.is-blue { --step-color: #3b82f6; }
.rdv3-step.is-green { --step-color: #24b777; }
.rdv3-step.is-gold { --step-color: var(--rdv3-accent); }
.rdv3-step-title { color: var(--rdv3-primary); font-size: .82rem; font-weight: 950; }
.rdv3-step-meta { margin-top: 2px; color: #8b94a8; font-size: .72rem; font-weight: 600; line-height: 1.3; }
.rdv3-payment-total { text-align: center; border-radius: 14px; border: 1px solid rgba(45, 189, 121, .26); background: radial-gradient(circle at 15% 0, rgba(45,189,121,.18), transparent 12rem), rgba(45, 189, 121, .13); padding: 18px; margin-bottom: 14px; }
.rdv3-payment-total small { display: block; color: #27a96e; font-size: .67rem; font-weight: 950; text-transform: uppercase; }
.rdv3-payment-total b { display: block; margin-top: 7px; color: var(--rdv3-primary); font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 1.65rem; }
.rdv3-payment-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; color: var(--rdv3-primary); }
.rdv3-payment-row + .rdv3-payment-row { border-top: 1px solid var(--rdv3-line); }
.rdv3-payment-method { display: flex; align-items: center; gap: 11px; min-width: 0; }
.rdv3-payment-method i { width: 31px; height: 31px; border-radius: 9px; display: grid; place-items: center; color: #2dbd79; background: #e7f8ef; }
.rdv3-payment-method b { display: block; font-size: .82rem; font-weight: 950; }
.rdv3-payment-method small { display: block; color: #8790a4; font-size: .7rem; font-weight: 600; }
.rdv3-payment-amount { font-size: .84rem; font-weight: 950; white-space: nowrap; }
.rdv3-note-composer { display: grid; gap: 10px; margin-bottom: 14px; padding: 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 20%, transparent); background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 7%, #fff), rgba(255,255,255,.74)); }
.rdv3-note-composer label { color: var(--rdv3-primary); font-size: .78rem; font-weight: 950; }
.rdv3-note-input { width: 100%; min-height: 86px; resize: vertical; padding: 11px 12px; border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 22%, var(--rdv3-line)); border-radius: 12px; background: #fff; color: var(--rdv3-primary); font: inherit; font-size: .82rem; line-height: 1.45; }
.rdv3-note-input::placeholder { color: #98a2b3; }
.rdv3-note-input:focus { outline: none; border-color: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 54%, var(--rdv3-line)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 16%, transparent); }
.rdv3-note-status { display: none; border-radius: 10px; border: 1px solid transparent; padding: 8px 10px; font-size: .74rem; font-weight: 850; line-height: 1.35; }
.rdv3-note-status.is-visible { display: block; }
.rdv3-note-status.is-error { border-color: #FECACA; background: #FEF2F2; color: #B42318; }
.rdv3-note-status.is-success { border-color: #BFE9D3; background: #F0FBF5; color: #15835A; }
.rdv3-inline-toast { position: fixed; right: 22px; bottom: 22px; z-index: 10080; max-width: min(360px, calc(100vw - 32px)); border-radius: 14px; border: 1px solid transparent; padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 850; line-height: 1.42; opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
.rdv3-inline-toast.is-visible { opacity: 1; transform: translateY(0); }
.rdv3-inline-toast.is-info { border-color: #BFD4F5; background: #F0F6FF; color: #255AA7; }
.rdv3-inline-toast.is-warning { border-color: #F4D38E; background: #FFF8E8; color: #9A5F10; }
.rdv3-inline-toast.is-error { border-color: #FECACA; background: #FEF2F2; color: #B42318; }
.rdv3-inline-toast.is-success { border-color: #BFE9D3; background: #F0FBF5; color: #15835A; }
.rdv3-note-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.rdv3-note-hint { color: #8b94a8; font-size: .72rem; font-weight: 600; }
.rdv3-note-submit { min-height: 34px; border: 0; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 12px; background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 82%, #22324a); color: #fff; font-size: .76rem; font-weight: 950; cursor: pointer; }
.rdv3-note-submit:hover { filter: brightness(.96); }
.rdv3-note-submit:disabled { opacity: .64; cursor: wait; }
.rdv3-note-list { display: grid; gap: 10px; max-height: 280px; overflow: auto; }
.rdv3-note { border-radius: 12px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 22%, transparent); background: color-mix(in srgb, var(--rdv3-accent) 12%, #fff); padding: 13px; color: #7a5f2b; font-size: .8rem; font-weight: 600; line-height: 1.45; }
.rdv3-note small { display: block; margin-bottom: 6px; color: color-mix(in srgb, var(--rdv3-accent) 84%, #6e5527); font-weight: 950; }
.rdv3-count-badge { min-width: 28px; height: 28px; padding: 0 8px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 90%, #22324a); color: #fff; font-size: .72rem; font-weight: 950; line-height: 1; box-shadow: 0 8px 16px -10px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 74%, transparent); }
.rdv3-side-card--notes .rdv3-count-badge { background: #dc2626; box-shadow: 0 8px 18px -10px rgba(220, 38, 38, .86); }
.rdv3-sr-only { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important; }
.rdv3-doc-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; margin-left: auto; min-width: min(100%, 300px); }
.rdv3-doc-btn { min-height: 36px; padding: 0 14px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: .76rem; font-weight: 950; white-space: nowrap; }
.rdv3-doc-btn.is-soft { background: #e7f8ef; color: #26a76f; }
.rdv3-doc-btn.is-gold { background: color-mix(in srgb, var(--rdv3-primary) 70%, var(--rdv3-accent)); color: #fff; }
.rdv3-doc-list { display: grid; gap: 11px; }
.rdv3-doc-row { min-height: 64px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); border-radius: 12px; background: rgba(255, 252, 247, .72); padding: 11px 13px; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.rdv3-doc-main { display: flex; align-items: center; gap: 13px; min-width: 0; }
.rdv3-docicon { width: 43px; height: 43px; border-radius: 11px; display: grid; place-items: center; color: #fff; font-size: .72rem; font-weight: 950; }
.rdv3-docicon--pdf { background: #d8493e; }
.rdv3-docicon--image { background: #4f46d8; }
.rdv3-docicon--doc { background: #2d74da; }
.rdv3-doc-title { color: var(--rdv3-primary); font-size: .86rem; font-weight: 950; overflow-wrap: anywhere; }
.rdv3-doc-meta { margin-top: 4px; color: #8790a4; font-size: .72rem; font-weight: 600; }
.rdv3-doc-right { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
.rdv3-doc-state { border-radius: 999px; min-height: 25px; padding: 0 10px; display: inline-flex; align-items: center; color: #27a96e; background: #e7f8ef; font-size: .7rem; font-weight: 950; }
.rdv3-doc-state.is-draft { color: var(--rdv3-accent); background: color-mix(in srgb, var(--rdv3-accent) 14%, #fff); }
.rdv3-iconbtn { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--rdv3-line); background: #fff; display: grid; place-items: center; color: #8790a4; }
.rdv3-empty { min-height: 86px; padding: 22px; text-align: center; color: #7f8ba0; font-size: .86rem; font-weight: 850; border: 1px dashed color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 22%, transparent); border-radius: 14px; background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 7%, #fff), rgba(255,255,255,.58)); display: grid; place-items: center; }
.rdv3-footer-line { display: flex; justify-content: space-between; gap: 12px; padding-top: 12px; color: #758096; font-size: .78rem; font-weight: 850; }
.rdv3 .documentos-entidad-panel {
    margin: 0 !important;
    overflow: hidden;
    border-radius: 20px;
    border-color: color-mix(in srgb, var(--rdv3-accent) 18%, rgba(255,255,255,.82));
    background: var(--rdv3-card);
    box-shadow: 0 18px 38px rgba(61, 47, 22, .09);
}
.rdv3 .documentos-entidad-panel > div:first-child {
    padding: 26px 42px 18px !important;
    border-color: color-mix(in srgb, var(--rdv3-accent) 12%, #E5E7EB) !important;
}
.rdv3 .documentos-entidad-panel > div:first-child > div:first-child { min-width: 0; }
.rdv3 .documentos-entidad-panel > div:first-child > div:first-child h2 {
    margin: 0;
    color: var(--rdv3-primary);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 1.22rem;
    font-weight: 600;
    line-height: 1.1;
}
.rdv3 .documentos-entidad-panel > div:first-child > div:first-child p { color: #8790A4 !important; font-weight: 650; }
.rdv3 .documentos-entidad-panel > div:first-child > .inline-flex {
    max-width: 100%;
    overflow-x: auto;
    overscroll-behavior-inline: contain;
    -webkit-overflow-scrolling: touch;
    flex-wrap: nowrap !important;
    justify-content: flex-start !important;
    padding-bottom: 4px;
    scrollbar-width: thin;
}
.rdv3 .documentos-entidad-panel .de-badge,
.rdv3 .documentos-entidad-panel .de-action {
    flex: 0 0 auto;
    min-height: 38px;
    border-radius: 999px;
    white-space: nowrap;
}
.rdv3 .documentos-entidad-panel .de-badge {
    background: #E7F8EF;
    color: #148653;
    border-color: rgba(20,134,83,.18);
}
.rdv3 .documentos-entidad-panel .de-action[href*="documentos/subir"] {
    background: color-mix(in srgb, var(--rdv3-accent) 76%, var(--rdv3-primary));
    color: #fff;
    border-color: transparent;
}
.rdv3 .documentos-entidad-panel .overflow-x-auto {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
@media (min-width: 781px) and (max-width: 1360px) {
    .rdv3-room {
        grid-template-columns: minmax(0, 1fr) minmax(148px, auto);
        gap: 15px 18px;
    }
    .rdv3-room-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 1360px) {
    .rdv3-layout { grid-template-columns: minmax(0, 1fr) 320px; gap: 22px; }
    .rdv3-card-header { padding-left: 36px; padding-right: 36px; }
    .rdv3-card-body { padding-left: 36px; padding-right: 36px; }
    .rdv3-side-card .rdv3-card-header { padding-left: 30px; padding-right: 30px; }
    .rdv3-side-card .rdv3-card-body { padding-left: 30px; padding-right: 30px; }
}
@media (max-width: 1120px) {
    .rdv3 { margin: 0; }
    .rdv3-layout { grid-template-columns: minmax(0, 1fr); }
    .rdv3-right { position: static; }
    .rdv3-right { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rdv3-side-card { align-self: start; }
}
@media (max-width: 780px) {
    .rdv3 { margin: 0; }
    .rdv3-hero { grid-template-columns: 1fr; padding: 22px; }
    .rdv3-layout { margin-top: 0; }
    .rdv3-card-header { padding: 22px 24px 12px; }
    .rdv3-card-body { padding: 12px 24px 22px; }
    .rdv3-side-card .rdv3-card-header { padding: 22px 24px 12px; }
    .rdv3-side-card .rdv3-card-body { padding: 12px 24px 22px; }
    .rdv3-doc-actions { width: 100%; justify-content: flex-start; padding-left: 0; }
    .rdv3-right { grid-template-columns: 1fr; }
    .rdv3-hero { align-items: flex-start; border-radius: 18px; }
    .rdv3-hero-actions { justify-content: flex-start; width: 100%; }
    .rdv3-btn { width: 100%; }
    .rdv3-stay, .rdv3-rooms, .rdv3-info-grid { grid-template-columns: 1fr; }
    .rdv3-guest-doc-feature { width: 100%; }
    .rdv3-guest-doc-list { width: 100%; min-width: 0; }
    .rdv3-guest-docs-head { align-items: flex-start; flex-direction: column; }
    .rdv3-guest-docs-actions { justify-content: flex-start; }
    .rdv3-nights { min-height: 62px; border-left: 0; border-right: 0; border-top: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); }
    .rdv3-date:last-child { text-align: left; }
    .rdv3-total, .rdv3-doc-row { align-items: flex-start; flex-direction: column; }
    .rdv3-doc-right { width: 100%; justify-content: space-between; }
    .rdv3 .rdv3-card--guest .rdv3-info-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rdv3-guest-doc-feature { grid-template-columns: 116px minmax(0, 1fr); width: 100%; }
    .rdv3-guest-doc-thumb { min-height: 106px; }
    .rdv3 .documentos-entidad-panel > div:first-child { padding: 22px 24px 16px !important; }
    .rdv3 .documentos-entidad-panel > div:first-child > .inline-flex { width: 100%; }
}
@media (max-width: 430px) {
    .rdv3-main { padding: .75rem !important; }
    .rdv3-card-header { padding-left: 18px !important; padding-right: 18px !important; }
    .rdv3-card-body { padding-left: 18px !important; padding-right: 18px !important; }
    .rdv3 .rdv3-card--guest .rdv3-info-grid,
    .rdv3-contact-actions { grid-template-columns: 1fr; }
    .rdv3-guest-head { align-items: flex-start; }
    .rdv3-guest-doc-feature { grid-template-columns: 1fr; }
    .rdv3-guest-doc-thumb { min-height: 118px; }
    .rdv3-subhead--stack { flex-direction: column; }
    .rdv3-subhead--stack .rdv3-link { min-height: 38px; align-self: flex-start; }
    .rdv3-vehicle { align-items: flex-start; flex-direction: column; }
    .rdv3-plate { margin-left: 48px; }
}
@media (min-width: 640px) {
    .rdv3-main { padding: 2rem !important; }
}

/* Header inset guardrail: keeps section icons away from card edges even if global styles load. */
.rdv3 {
    --rdv3-main-header-x: 42px;
    --rdv3-side-header-x: 32px;
}
.rdv3 .rdv3-left > .rdv3-card > .rdv3-card-header {
    padding-left: var(--rdv3-main-header-x) !important;
    padding-right: var(--rdv3-main-header-x) !important;
}
.rdv3 .rdv3-right > .rdv3-card > .rdv3-card-header {
    padding-left: var(--rdv3-side-header-x) !important;
    padding-right: var(--rdv3-side-header-x) !important;
}
.rdv3 .rdv3-card-header .rdv3-heading {
    margin-left: 0 !important;
    padding-left: 0 !important;
}
.rdv3 .rdv3-card-header .rdv3-icon {
    position: static !important;
    margin-left: 0 !important;
    transform: none !important;
}
@media (max-width: 1360px) {
    .rdv3 {
        --rdv3-main-header-x: 36px;
        --rdv3-side-header-x: 30px;
    }
}
@media (max-width: 780px) {
    .rdv3 {
        --rdv3-main-header-x: 24px;
        --rdv3-side-header-x: 24px;
    }
}
@media (max-width: 780px) {
    .main-content { overflow-x: hidden !important; }
    .rdv3 {
        --rdv3-mobile-gutter: 16px;
        --rdv3-mobile-surface: #F1EDE5;
        --rdv3-mobile-card: #FFFDF9;
        --rdv3-mobile-line: #E6DBC8;
        background: var(--rdv3-mobile-surface);
        overflow-x: hidden;
        max-width: 100%;
    }
    .rdv3-shell,
    .rdv3-page { background: var(--rdv3-mobile-surface); }
    .rdv3-main {
        padding: 0 !important;
        padding-bottom: calc(96px + env(safe-area-inset-bottom, 0px)) !important;
        overflow-x: hidden !important;
        max-width: 100%;
    }
    .rdv3-page {
        padding: 0 var(--rdv3-mobile-gutter) 112px !important;
        overflow-x: hidden;
        max-width: 100%;
    }
    .rdv3-topbar {
        position: sticky;
        top: 0;
        z-index: 60;
        margin: 0 calc(var(--rdv3-mobile-gutter) * -1) 14px;
        padding: calc(10px + env(safe-area-inset-top, 0px)) var(--rdv3-mobile-gutter) 12px;
        background: rgba(255, 253, 249, .96);
        border-bottom: 1px solid var(--rdv3-mobile-line);
        backdrop-filter: blur(14px);
    }
    .rdv3-back,
    .rdv3-topbar-menu {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 11px;
        border: 1px solid var(--rdv3-mobile-line);
        background: #fff;
        color: var(--rdv3-primary);
        box-shadow: none;
    }
    .rdv3-topbar-menu {
        margin-left: auto;
        display: grid;
        place-items: center;
    }
    .rdv3-crumb-prefix { display: none; }
    .rdv3-crumbs {
        color: var(--rdv3-primary);
        font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 1.13rem;
        line-height: 1;
        font-weight: 700;
    }
    .rdv3-crumbs strong { color: var(--rdv3-primary); }
    .rdv3-topbar-hotel {
        color: #6f7890;
        font-size: .73rem;
        font-weight: 700;
    }
    .rdv3-hero {
        margin: 0 0 18px;
        min-height: 106px;
        padding: 21px 18px;
        border-radius: 17px;
        grid-template-columns: 1fr;
        color: #fff;
        position: relative;
        overflow: hidden;
        background: linear-gradient(125deg, var(--rdv3-primary), color-mix(in srgb, var(--rdv3-primary) 86%, #243357));
        box-shadow: 0 15px 30px rgba(28, 37, 62, .18);
    }
    .rdv3-hero::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: rgba(176, 136, 63, .14);
        pointer-events: none;
    }
    .rdv3-title-prefix { display: none; }
    .rdv3-titleline {
        align-items: center;
        gap: 8px;
    }
    .rdv3-titleline h1 {
        font-size: 1.55rem;
        line-height: 1;
        letter-spacing: 0;
    }
    .rdv3-status {
        min-height: 26px;
        padding: 0 11px;
        font-size: .67rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .rdv3-status--ok {
        color: #B6B4F0;
        background: rgba(90, 87, 210, .26);
        border-color: rgba(150, 148, 230, .35);
    }
    .rdv3-meta {
        gap: 13px;
        margin-top: 11px;
        color: rgba(255,255,255,.68);
        font-size: .72rem;
        font-weight: 500;
        flex-wrap: wrap;
    }
    .rdv3-meta i { color: #D8BC83; }
    .rdv3-hero-actions { display: none; }
    .rdv3-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 14px;
        margin-top: 0;
    }
    .rdv3-left,
    .rdv3-right { display: contents; }
    .rdv3-card,
    .rdv3 .documentos-entidad-panel {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        overflow: visible;
    }
    .rdv3-card--stay { order: 10; }
    #rdv3-payment-section { order: 15; }
    .rdv3-card--rooms { order: 20; }
    .rdv3-card--guest { order: 30; }
    .rdv3-side-card--payment { order: 40; }
    .rdv3-side-card--timeline { order: 50; }
    .rdv3 .documentos-entidad-panel { order: 60; }
    .rdv3-side-card--notes { order: 70; }
    .rdv3-side-card--actions { order: 80; }
    .rdv3-card-header,
    .rdv3-side-card .rdv3-card-header {
        min-height: 0;
        padding: 0 3px 9px !important;
        background: transparent;
        border: 0;
        align-items: center;
    }
    .rdv3-card-header .rdv3-icon,
    .rdv3-side-card .rdv3-icon { display: none !important; }
    .rdv3-heading { gap: 0; }
    .rdv3-card-title,
    .rdv3 .documentos-entidad-panel > div:first-child > div:first-child h2 {
        font-family: Manrope, system-ui, sans-serif;
        color: #9BA3B6;
        font-size: .72rem;
        line-height: 1.15;
        font-weight: 950;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .rdv3-card-header .rdv3-link {
        min-height: 0;
        padding: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
        color: #B08034;
        font-size: .72rem;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .rdv3-card-body,
    .rdv3-side-card .rdv3-card-body {
        padding: 16px !important;
        border: 1px solid var(--rdv3-mobile-line);
        border-radius: 16px;
        background: var(--rdv3-mobile-card);
        box-shadow: 0 10px 22px rgba(39, 31, 18, .07);
    }
    .rdv3-stay {
        grid-template-columns: minmax(0, 1fr) 60px minmax(0, 1fr);
        border-color: var(--rdv3-mobile-line);
        border-radius: 13px;
        background: #F8F3EB;
    }
    .rdv3-date {
        min-width: 0;
        padding: 13px 14px;
    }
    .rdv3-date:last-child { text-align: right; }
    .rdv3-date strong {
        color: var(--rdv3-primary);
        font-size: 1.18rem;
    }
    .rdv3-date span {
        margin-top: 3px;
        font-size: .68rem;
    }
    .rdv3-nights {
        min-height: 80px;
        border-top: 0;
        border-bottom: 0;
        border-color: var(--rdv3-mobile-line);
        background: rgba(255,255,255,.55);
    }
    .rdv3-total {
        margin-top: 13px;
        min-height: 86px;
        padding: 15px;
        border-radius: 13px;
        border-color: rgba(30,158,99,.22);
        background: linear-gradient(120deg, #E8F7EF, #DFF2E8);
    }
    .rdv3-total .rdv3-amount { font-size: 1.55rem; }
    .rdv3-pill {
        min-height: 27px;
        padding: 0 11px;
        font-size: .69rem;
        white-space: nowrap;
    }
    .rdv3-res-note {
        margin-top: 13px;
        align-items: flex-start;
        border-color: #E4C98E;
        background: #FFF4D8;
        color: #78531C;
        font-size: .78rem;
        line-height: 1.45;
    }
    .rdv3-header-badges { display: none; }
    .rdv3-badge {
        min-height: 0;
        padding: 0;
        background: transparent;
        color: #B08034;
        font-size: .72rem;
        letter-spacing: .04em;
    }
    .rdv3-badge i { display: none; }
    .rdv3-card--rooms .rdv3-card-body { padding: 0 !important; }
    .rdv3-rooms {
        display: grid;
        gap: 12px;
        border: 0;
        border-radius: 0;
        background: transparent;
        overflow: visible;
    }
    .rdv3-room {
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
            "identity"
            "price"
            "metrics";
        border: 1px solid #E3D5BF;
        border-radius: 14px;
        background: #FFFDF9;
        box-shadow: none;
        padding: 14px;
        gap: 12px;
    }
    .rdv3-room + .rdv3-room { border-top: 1px solid #E3D5BF; }
    .rdv3-room:not(:last-child) {
        margin-bottom: 0;
        padding-bottom: 14px;
        border-bottom: 1px solid #E3D5BF;
    }
    .rdv3-room::before { display: none; }
    .rdv3-room-top { display: contents; }
    .rdv3-room-top > .min-w-0 {
        grid-area: identity;
        grid-template-columns: minmax(50px, auto) minmax(0, 1fr);
        column-gap: 12px;
        align-items: center;
    }
    .rdv3-room-number {
        box-sizing: border-box;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        min-width: 50px;
        max-width: 118px;
        min-height: 50px;
        height: auto;
        padding: 7px 12px;
        border-radius: 13px;
        border: 1px solid #E6DBC8;
        background: linear-gradient(180deg, #FFFDF9, #FBF5EA);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7);
        color: var(--rdv3-primary);
        overflow: hidden;
        text-align: center;
        white-space: nowrap;
        text-overflow: ellipsis;
        text-transform: uppercase;
        font-size: clamp(.8rem, 3.6vw, 1.12rem);
        line-height: 1.02;
        letter-spacing: .01em;
    }
    .rdv3-room-type {
        margin-top: 0;
        color: var(--rdv3-primary);
        font-size: .82rem;
        font-weight: 950;
        min-width: 0;
    }
    .rdv3-room-sub {
        margin-top: 2px;
        gap: 4px 8px;
    }
    .rdv3-tag {
        min-height: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: #7F8797;
        font-size: .72rem;
        font-weight: 650;
    }
    .rdv3-tag i { display: none; }
    .rdv3-room-price {
        grid-area: price;
        align-self: stretch;
        min-width: 0;
        padding: 11px 0 0;
        border-left: 0;
        border-top: 1px solid #E6DBC8;
        color: var(--rdv3-primary);
        font-size: .92rem;
        text-align: left;
    }
    .rdv3-room-price small {
        display: block;
        font-size: .58rem;
        color: #9BA3B6;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-top: 1px;
    }
    .rdv3-room.is-courtesy .rdv3-room-price {
        padding: 5px 9px;
        border-top: 0;
        justify-self: start;
        align-self: start;
        border-radius: 999px;
        background: #FFF2D9;
        color: #B08034;
        font-size: .68rem;
    }
    .rdv3-room.is-courtesy .rdv3-room-price small { display: none; }
    .rdv3-room-metrics {
        grid-area: metrics;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
        border: 0;
        background: transparent;
    }
    .rdv3-room-metric {
        min-height: 48px;
        padding: 9px 10px;
        border: 1px solid #E6DBC8;
        background: #F8F3EB;
    }
    .rdv3-room-metric + .rdv3-room-metric { border-left: 1px solid #E6DBC8; }
    .rdv3-room-metric small { font-size: .54rem; letter-spacing: .02em; }
    .rdv3-room-metric b { font-size: .68rem; }
    .rdv3-room-discount,
    .rdv3-room-net {
        min-height: 31px;
        padding: 7px 9px;
        font-size: .7rem;
    }
    .rdv3-guest-head {
        margin-bottom: 15px;
        padding: 0;
        background: transparent;
    }
    .rdv3-avatar {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        background: linear-gradient(145deg, #6657D7, #5143C6);
    }
    .rdv3-guest-name { font-size: 1.2rem; }
    .rdv3-guest-sub { font-size: .73rem; }
    .rdv3 .rdv3-card--guest .rdv3-info-grid,
    .rdv3-contact-actions {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 9px;
    }
    .rdv3-info {
        min-height: 54px;
        padding: 10px;
        border-color: var(--rdv3-mobile-line);
        background: #FFFBF5;
    }
    .rdv3-info i {
        width: 28px;
        height: 28px;
        background: #fff;
        color: #8D98AA;
    }
    .rdv3-info small { font-size: .58rem; }
    .rdv3-info b {
        font-size: .72rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .rdv3-contact-action {
        min-height: 42px;
        border-color: var(--rdv3-mobile-line);
        background: #FFFBF5;
        font-size: .78rem;
    }
    .rdv3-guest-docs {
        margin-top: 15px;
        padding: 0;
        border: 0;
        background: transparent;
    }
    .rdv3-guest-docs-head { margin-bottom: 9px; }
    .rdv3-guest-docs-title {
        color: var(--rdv3-primary);
        font-size: .82rem;
    }
    .rdv3-guest-docs-title i {
        width: auto;
        height: auto;
        border: 0;
        background: transparent;
        color: #62A7C2;
    }
    .rdv3-guest-doc-feature {
        min-height: 72px;
        grid-template-columns: 54px minmax(0, 1fr);
        padding: 11px;
        border-radius: 12px;
        border-color: var(--rdv3-mobile-line);
        background: #FFFBF5;
    }
    .rdv3-guest-doc-thumb {
        min-height: 48px;
        padding: 8px;
        border-radius: 8px;
    }
    .rdv3-guest-doc-seal { display: none; }
    .rdv3-guest-doc-brand {
        align-self: center;
        font-size: .48rem;
    }
    .rdv3-guest-doc-copy b { font-size: .82rem; }
    .rdv3-guest-doc-copy small { font-size: .68rem; }
    .rdv3-doc-mini-state { display: none !important; }
    .rdv3-subhead {
        margin: 15px 0 9px;
        color: var(--rdv3-primary);
        font-size: .82rem;
    }
    .rdv3-subhead-note { display: none; }
    .rdv3-vehicle {
        border-color: var(--rdv3-mobile-line);
        background: #FFFBF5;
    }
    .rdv3-payment-total {
        border-radius: 13px;
        background: linear-gradient(135deg, #E8F7EF, #DFF2E8);
        border-color: rgba(30, 158, 99, .2);
        padding: 14px 16px;
        text-align: center;
    }
    .rdv3-payment-total small {
        font-size: .67rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #1E9E63;
    }
    .rdv3-payment-total b { font-size: 1.55rem; }
    .rdv3-payment-row { padding: 8px 0; }
    .rdv3-payment-row + .rdv3-payment-row { border-top: 1px solid #F1EDE5; }
    .rdv3-payment-method i {
        width: 30px; height: 30px;
        border-radius: 8px;
        background: #E7F8EF;
        color: #22A66B;
    }
    .rdv3-timeline {
        gap: 14px;
        padding-left: 20px;
    }
    .rdv3-timeline::before {
        left: 6px;
        top: 5px;
        bottom: 5px;
        width: 2px;
        background: #E6DBC8;
    }
    .rdv3-step::before {
        left: -18px;
        top: 9px;
        width: 10px;
        height: 10px;
        border-width: 2.5px;
    }
    .rdv3-step-title { font-size: .84rem; font-weight: 750; }
    .rdv3-step-meta { font-size: .72rem; line-height: 1.3; }
    .rdv3 .documentos-entidad-panel {
        margin: 0 !important;
        padding: 0;
    }
    .rdv3 .documentos-entidad-panel > div:first-child {
        padding: 0 3px 9px !important;
        border: 0 !important;
        background: transparent !important;
    }
    .rdv3 .documentos-entidad-panel > div:first-child > div:first-child p { display: none; }
    .rdv3 .documentos-entidad-panel > div:first-child > .inline-flex {
        width: 100%;
        gap: 8px;
        padding-bottom: 3px;
    }
    .rdv3 .documentos-entidad-panel .de-badge,
    .rdv3 .documentos-entidad-panel .de-action {
        min-height: 34px;
        border-radius: 999px;
        font-size: .75rem;
        padding: 0 12px;
    }
    .rdv3 .documentos-entidad-panel .de-badge {
        color: #1E9E63;
        background: #E7F4EC;
        border-color: rgba(30, 158, 99, .22);
    }
    .rdv3 .documentos-entidad-panel .de-action:last-child {
        color: #fff;
        background: linear-gradient(150deg, #BE9A52, #9C7730);
        border-color: transparent;
    }
    .rdv3 .documentos-entidad-panel > div:not(:first-child) {
        border: 1px solid var(--rdv3-mobile-line);
        border-radius: 16px;
        background: var(--rdv3-mobile-card);
        box-shadow: 0 10px 22px rgba(39, 31, 18, .07);
        overflow: hidden;
    }
    /* Mobile-friendly document table */
    .rdv3 .documentos-entidad-panel .de-table { width: 100%; }
    .rdv3 .documentos-entidad-panel .de-table thead { display: none; }
    .rdv3 .documentos-entidad-panel .de-table tbody tr {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--rdv3-mobile-line);
    }
    .rdv3 .documentos-entidad-panel .de-table tbody tr:last-child { border-bottom: 0; }
    .rdv3 .documentos-entidad-panel .de-table td {
        border: 0 !important;
        padding: 0 !important;
    }
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(2),
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(3),
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(5),
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(6) { display: none; }
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(1) {
        flex: 1;
        min-width: 0;
        font-size: .82rem;
        font-weight: 700;
        color: var(--rdv3-primary);
    }
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(1) .text-xs {
        font-size: .68rem;
        color: #8b94a8;
        margin-top: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(4) {
        white-space: nowrap;
    }
    .rdv3 .documentos-entidad-panel .de-table td:nth-child(7) {
        white-space: nowrap;
    }
    /* Empty state inside documents panel */
    .rdv3 .documentos-entidad-panel .p-8 {
        padding: 20px 14px !important;
    }
    .rdv3-side-card--notes .rdv3-card-body {
        display: flex;
        flex-direction: column-reverse;
        gap: 12px;
    }
    .rdv3-note-list {
        max-height: none;
        overflow: visible;
    }
    .rdv3-note {
        background: linear-gradient(120deg, #F4EAD5, #FBF3E1);
        border-color: #E4D4B0;
        border-radius: 11px;
        padding: 11px 13px;
        color: #7A5C20;
        font-size: .78rem;
        line-height: 1.5;
    }
    .rdv3-note small {
        color: color-mix(in srgb, #9C7730 84%, #6e5527);
        font-weight: 750;
    }
    .rdv3-note-composer {
        margin: 0;
        padding: 12px;
        border-color: var(--rdv3-mobile-line);
        background: #FFFBF5;
        border-radius: 13px;
    }
    .rdv3-mobile-bottom {
        position: fixed;
        left: auto;
        right: 16px;
        bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        z-index: 90;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        width: auto;
        max-width: calc(100vw - 32px);
        padding: 6px;
        border: 1px solid rgba(230, 219, 200, .92);
        border-radius: 18px;
        background: rgba(255, 253, 249, .82);
        box-shadow: 0 12px 26px rgba(39, 31, 18, .12);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    .rdv3-mobile-bottom::before {
        content: '';
        position: absolute;
        inset: 1px;
        border-radius: 17px;
        background: rgba(255, 255, 255, .28);
        pointer-events: none;
    }
    .rdv3-mobile-bottom > a,
    .rdv3-mobile-bottom > button {
        position: relative;
        z-index: 1;
        width: 44px;
        min-width: 44px;
        height: 44px;
        min-height: 44px;
        padding: 0;
        flex: 0 0 44px;
    }
    .rdv3-mobile-primary {
        border: 0;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        background: #21A86A;
        color: #fff;
        font-size: .96rem;
        font-weight: 950;
        cursor: pointer;
        box-shadow: 0 10px 20px -14px rgba(33, 168, 106, .9);
    }
    .rdv3-mobile-checkout {
        background: linear-gradient(135deg, #D97706, #B45309);
        box-shadow: 0 10px 20px -14px rgba(180, 83, 9, .9);
    }
    .rdv3-mobile-more {
        border-radius: 14px;
        display: grid;
        place-items: center;
        border: 1px solid rgba(230, 219, 200, .88);
        background: rgba(255, 255, 255, .88);
        color: var(--rdv3-primary);
        font-size: .94rem;
    }
    .rdv3-mobile-danger {
        border-radius: 14px;
        border: 1px solid rgba(185, 28, 28, .24);
        background: rgba(255, 245, 245, .92);
        color: #991B1B;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        font-size: .92rem;
        font-weight: 950;
        box-shadow: 0 10px 20px -16px rgba(185, 28, 28, .72);
        cursor: pointer;
    }
    .rdv3-mobile-bottom a:focus-visible,
    .rdv3-mobile-bottom button:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--rdv3-accent) 72%, #fff);
        outline-offset: 2px;
    }
    .rdv3-mobile-bottom a:active,
    .rdv3-mobile-bottom button:active {
        transform: scale(.96);
    }
    .rdv3-mobile-label {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    body.has-hotel-bottom-nav .rdv3 .rdv3-main,
    body.has-hotel-bottom-nav .rdv3 .rdv3-page {
        padding-bottom: calc(var(--hbn-offset, calc(84px + env(safe-area-inset-bottom, 0px))) + 66px) !important;
    }
    body.has-hotel-bottom-nav .rdv3 .rdv3-mobile-bottom {
        bottom: calc(var(--hbn-offset, calc(84px + env(safe-area-inset-bottom, 0px))) + 10px);
        z-index: 970;
    }
}
@media (max-width: 370px) {
    .rdv3 {
        --rdv3-mobile-gutter: 12px;
    }
    .rdv3 .rdv3-card--guest .rdv3-info-grid,
    .rdv3-contact-actions { grid-template-columns: 1fr; }
    .rdv3-room-top > .min-w-0 { grid-template-columns: minmax(52px, auto) minmax(0, 1fr); }
    .rdv3-room-number {
        min-width: 52px;
        max-width: 68px;
        width: auto;
        height: 42px;
        font-size: clamp(.54rem, 2.8vw, .76rem);
    }
}

/* ════════════════════════════════════════════════════════════════════════
   ARMONÍA PC ↔ MÓVIL  ·  solo desktop/tablet (≥781px, jamás afecta ≤780px)
   Iguala la paleta fría del desktop al cálido cream de la vista móvil:
   fondo arena #F1EDE5, cards #FFFDF9, líneas/bordes en tono arena #E6DBC8.
   ════════════════════════════════════════════════════════════════════════ */
@media (min-width: 781px) {
    .rdv3 {
        --rdv3-bg: #F1EDE5;          /* = --rdv3-mobile-surface */
        --rdv3-card: #FFFDF9;        /* = --rdv3-mobile-card */
        --rdv3-line: rgba(157, 137, 104, .16);
        background: var(--rdv3-bg);
    }
    /* Cards y side-cards: fondo cálido + borde arena con un toque del acento */
    .rdv3-card,
    .rdv3-side-card,
    .rdv3 .documentos-entidad-panel {
        background: var(--rdv3-card);
        border-color: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 16%, #E6DBC8);
    }
    /* Superficies blancas/frías hardcodeadas → cálidas, como en móvil */
    .rdv3-stay { background: #FFFDF9; }
    .rdv3-stay { border-color: #E6DBC8; }
    .rdv3-nights {
        background: #F1EDE5;
        border-left-color: #E6DBC8;
        border-right-color: #E6DBC8;
    }

    /* ── Quitar el "rainbow" por tipo: un único acento dorado (como móvil) ── */
    .rdv3-card--stay,
    .rdv3-card--rooms,
    .rdv3-card--guest,
    .rdv3-card--docs,
    .rdv3-side-card--actions,
    .rdv3-side-card--timeline,
    .rdv3-side-card--payment,
    .rdv3-side-card--notes {
        --rdv3-card-accent: var(--rdv3-accent);
    }
    /* Habitaciones: el ciclo de colores por :nth-child gana en especificidad,
       así que lo igualamos rule-por-rule a un único acento dorado. */
    .rdv3-room,
    .rdv3-room:nth-child(4n+1),
    .rdv3-room:nth-child(4n+2),
    .rdv3-room:nth-child(4n+3),
    .rdv3-room:nth-child(4n+4),
    .rdv3-room.is-courtesy { --room-accent: var(--rdv3-accent); }

    .rdv3-card--rooms .rdv3-rooms { border: 0; }
    .rdv3-card--rooms .rdv3-room {
        box-shadow: none;
    }
    .rdv3-card--rooms .rdv3-room:hover {
        background: #FFFBF5;
        box-shadow: none;
    }

    /* Iconos de info del huésped: mismo ciclo de colores → uniforme dorado. */
    .rdv3-info,
    .rdv3-info:nth-child(2),
    .rdv3-info:nth-child(3),
    .rdv3-info:nth-child(4) { --info-accent: var(--rdv3-accent); }

    .rdv3-card--guest .rdv3-info-grid {
        gap: 12px;
    }
    .rdv3-card--guest .rdv3-info {
        border-color: color-mix(in srgb, var(--info-accent) 22%, #D8CCBA);
        background: #FFFDF9;
        box-shadow: 0 8px 18px rgba(47, 39, 25, .04);
    }
    .rdv3-card--guest .rdv3-info i {
        background: #FFFBF5;
        border-color: color-mix(in srgb, var(--info-accent) 20%, #D8CCBA);
    }

    /* Texto clave en navy (como móvil), no en colores de acento */
    .rdv3-date strong,
    .rdv3-room-number,
    .rdv3-room-price,
    .rdv3-room-type { color: var(--rdv3-primary); }

    /* Badge de estado del hero: visible en desktop, con el mismo tono morado del móvil */
    .rdv3-status {
        min-height: 34px;
        padding: 0 15px;
        border-radius: 999px;
        border-width: 1px;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .16),
            0 10px 22px rgba(24, 32, 72, .18);
        -webkit-backdrop-filter: blur(8px);
        backdrop-filter: blur(8px);
        white-space: nowrap;
    }
    .rdv3-status--ok {
        color: #F3F1FF;
        background:
            linear-gradient(135deg, rgba(111, 106, 222, .52), rgba(90, 87, 210, .32)),
            rgba(90, 87, 210, .36);
        border-color: rgba(194, 191, 255, .52);
    }
    .rdv3-status--ok::before {
        background: #C8C4FF;
        box-shadow: 0 0 0 3px rgba(200, 196, 255, .18);
    }
}
</style>

<div class="rdv3 detail-view">
    <div class="rdv3-shell">
        <main class="rdv3-main">
            <div class="rdv3-page">
                <div class="rdv3-topbar">
                    <?php $back_arrow_href = back_url('reservaciones'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a class="rdv3-back ms-back-legacy" href="<?= back_url('reservaciones') ?>" aria-label="Volver"><i class="fas fa-arrow-left"></i></a>
                    <div class="rdv3-topbar-copy">
                        <div class="rdv3-crumbs"><span class="rdv3-crumb-prefix">Reservaciones / </span><strong>Reservacion #<?= $rdReservationId ?></strong></div>
                        <div class="rdv3-topbar-hotel"><?= $rdSafe($nombreHotelVisible, 'Medisoft Hoteles') ?></div>
                    </div>
                    <a class="rdv3-topbar-menu" href="#rdv3-actions-panel" aria-label="Ver acciones"><i class="fas fa-ellipsis"></i></a>
                </div>

                <section class="rdv3-hero" aria-labelledby="rdv3-title">
                    <div>
                        <div class="rdv3-titleline">
                            <h1 id="rdv3-title"><span class="rdv3-title-prefix">Reservacion </span>#<?= $rdReservationId ?></h1>
                            <span class="rdv3-status <?= $rdEstadoClass ?>"><?= $rdSafe($rdEstadoLabel, 'Pendiente') ?></span>
                        </div>
                        <div class="rdv3-meta">
                            <span><i class="fas fa-bed"></i><?= $rdSafe($rdRoomCountLabel) ?></span>
                            <span><i class="fas fa-user"></i><?= $rdSafe($rdGuestName, 'Huesped') ?></span>
                            <span><i class="far fa-clock"></i><?= $rdSafe($rdDateTime($rdCreatedAt), '-') ?></span>
                        </div>
                    </div>
                    <div class="rdv3-hero-actions">
                        <?php if ($rdCheckinMode === 'normal' || $rdCheckinMode === 'express'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-primary" onclick="abrirModalCheckIn(<?= $rdReservationId ?>, <?= $rdTotal ?>)"><i class="fas fa-right-to-bracket"></i><?= $rdCheckinButtonLabel ?></button>
                        <?php elseif ($rdCheckinMode === 'late'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-primary" onclick='abrirModalCheckInTardio(<?= $rdReservationId ?>, <?= $rdHuespedNombreJsonAttr ?>, <?= $rdHabitacionesTextoJsonAttr ?>, <?= $rdFechaEntradaFormatoJsonAttr ?>, <?= $rdFechaSalidaFormatoJsonAttr ?>, <?= $rdTotal ?>, "<?= $rdCheckinJsMode ?>", <?= (int)$rdCheckinDays ?>)'><i class="fas fa-right-to-bracket"></i><?= $rdCheckinButtonLabel ?></button>
                        <?php elseif ($rdEstadoKey === 'checked_in' && !$rdHasCheckoutDebt): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-checkout" onclick="abrirModalCheckOut()"><i class="fas fa-right-from-bracket"></i>Check-out</button>
                        <?php endif; ?>
                        <?php if ($rdEstadoKey === 'confirmada'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-rooms" onclick="window.location.href='<?= url('reservaciones/editar-habitaciones/' . $reservacion['id']) ?>'"><i class="fas fa-bed"></i>Modificar habitaciones</button>
                        <?php endif; ?>
                        <?php if (in_array($rdEstadoKey, ['confirmada', 'checked_in'], true)): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-calendar" onclick="window.location.href='<?= url('reservaciones/editar-estancia/' . $reservacion['id']) ?>'"><i class="far fa-calendar"></i>Modificar estancia</button>
                            <button type="button" class="rdv3-btn rdv3-btn-danger" onclick="mostrarFormularioCancelacion()"><i class="fas fa-ban"></i>Cancelar reservacion</button>
                        <?php endif; ?>
                    </div>
                </section>

                <div class="rdv3-layout">
                    <div class="rdv3-left">
                        <section class="rdv3-card rdv3-card--stay">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon rdv3-icon--blue"><i class="far fa-calendar"></i></span>
                                    <h2 class="rdv3-card-title">Informacion de la reservacion</h2>
                                </div>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rdv3-stay">
                                    <div class="rdv3-date">
                                        <span class="rdv3-label">Check-in</span>
                                        <strong><?= $rdSafe($rdDate($reservacion['fecha_entrada'] ?? null)) ?></strong>
                                        <span>A partir de <?= $rdSafe($rdEntryTime, '15:00') ?></span>
                                    </div>
                                    <div class="rdv3-nights">
                                        <div><b><?= (int)$rdNoches ?></b><span>noches</span></div>
                                    </div>
                                    <div class="rdv3-date">
                                        <span class="rdv3-label">Check-out</span>
                                        <strong><?= $rdSafe($rdDate($reservacion['fecha_salida'] ?? null)) ?></strong>
                                        <span>Antes de <?= $rdSafe($rdExitTime, '12:00') ?></span>
                                    </div>
                                </div>
                                <?php $rdDescuentoTotal = (float)($reservacion['descuento_total'] ?? 0); ?>
                                <?php if ($rdDescuentoTotal > 0): ?>
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-top:1px dashed #E7E1D4;font-size:.9rem;">
                                        <span class="rdv3-label">Subtotal</span>
                                        <strong><?= $rdMoney($rdTotal + $rdDescuentoTotal) ?></strong>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;align-items:center;padding:0 0 7px;color:#B4392B;font-size:.9rem;">
                                        <span>Descuento aplicado</span>
                                        <strong>&minus;<?= $rdMoney($rdDescuentoTotal) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="rdv3-total">
                                    <div>
                                        <span class="rdv3-label rdv3-label--green">Precio total</span>
                                        <div class="rdv3-amount"><?= $rdMoney($rdTotal) ?></div>
                                    </div>
                                    <span class="rdv3-pill <?= $rdPaymentIsPaid ? 'is-paid' : 'is-pending' ?>"><i class="fas <?= $rdPaymentIsPaid ? 'fa-check' : 'fa-clock' ?>"></i><?= $rdSafe($rdPaymentLabel, 'Pago pendiente') ?></span>
                                </div>
                                <?php if (!empty($reservacion['notas'])): ?>
                                    <div class="rdv3-res-note"><i class="far fa-note-sticky"></i><?= $rdSafe($reservacion['notas']) ?></div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <?php
                            $rpResumen = $rdPaymentSummary ?? ['total' => $rdTotal, 'pagado' => 0, 'saldo' => $rdTotal];
                            $rpEval = $anticipoEval ?? ['elegible' => false, 'motivo' => '', 'metodos_pago' => []];
                            $rpAbonos = $abonos ?? [];
                            $rpSaldoPositivo = ($rpResumen['saldo'] ?? 0) > 0.004;
                            $rpEsPagoPendiente = in_array($rdEstadoKey, ['checked_in', 'checked_out'], true);
                            $rpConceptoCobro = $rpEsPagoPendiente ? 'Pago pendiente de reservacion' : 'Anticipo de reservacion';
                            $rpMontoLabel = $rpEsPagoPendiente ? 'Monto a cobrar' : 'Monto del anticipo';
                            $rpBotonCobro = $rpEsPagoPendiente ? 'Registrar pago pendiente' : 'Registrar anticipo';
                        ?>
                        <section class="rdv3-card" id="rdv3-payment-section" data-rdv3-payment-balance="<?= number_format((float)($rpResumen['saldo'] ?? 0), 2, '.', '') ?>">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon rdv3-icon--green"><i class="fas fa-hand-holding-dollar"></i></span>
                                    <h2 class="rdv3-card-title">Pagos y anticipos</h2>
                                </div>
                                <span class="rp-status <?= $rpSaldoPositivo ? 'is-pending' : 'is-done' ?>"><span class="rp-status-dot"></span><?= $rpSaldoPositivo ? 'Pendiente' : 'Liquidada' ?></span>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rp-stats">
                                    <div class="rp-stat"><small>Total</small><strong><?= $rdMoney($rpResumen['total']) ?></strong></div>
                                    <div class="rp-stat is-paid"><small>Pagado</small><strong><?= $rdMoney($rpResumen['pagado']) ?></strong></div>
                                    <div class="rp-stat <?= $rpSaldoPositivo ? 'is-balance' : 'is-paid' ?>"><small>Saldo</small><strong><?= $rdMoney($rpResumen['saldo']) ?></strong></div>
                                </div>

                                <?php
                                // El formulario de anticipo requiere el bloque 'anticipos';
                                // el cobro de saldo tras check-in/out es core y siempre se muestra.
                                $rpModuloAnticipos = !function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('anticipos');
                                $rpPuedeCobrar = !empty($rpEval['elegible']) && ($rpEsPagoPendiente || $rpModuloAnticipos);
                                $rpPct = ($rpResumen['total'] ?? 0) > 0
                                    ? min(100, max(0, (int)round(($rpResumen['pagado'] / $rpResumen['total']) * 100)))
                                    : ($rpSaldoPositivo ? 0 : 100);
                                ?>
                                <div class="rp-progress <?= $rpSaldoPositivo ? 'is-pending' : 'is-done' ?>"><i style="width:<?= $rpPct ?>%"></i></div>
                                <?php if ($rpPuedeCobrar): ?>
                                    <div class="rp-actionrow">
                                        <span class="rp-note">
                                            <i class="fas <?= $rpSaldoPositivo ? 'fa-clock' : 'fa-circle-check' ?>" aria-hidden="true"></i>
                                            <?= $rpSaldoPositivo ? ('Saldo pendiente ' . $rdMoney($rpResumen['saldo'])) : 'Reservación liquidada · sin saldo pendiente' ?>
                                        </span>
                                        <button type="button" class="rp-cta" data-rp-open aria-haspopup="dialog" aria-controls="rdaSheet">
                                            <i class="fas fa-plus" aria-hidden="true"></i> <?= $rdSafe($rpBotonCobro) ?>
                                        </button>
                                    </div>
                                <?php elseif ($rpSaldoPositivo): ?>
                                    <div class="rp-warn">
                                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                                        <span>
                                        <?php if (!empty($rpEval['elegible']) && !$rpModuloAnticipos): ?>
                                            El bloque de anticipos no está contratado; el saldo se cobra al hacer check-in.
                                        <?php else: ?>
                                            <?= $rdSafe($rpEval['motivo'] ?: 'No se puede registrar un anticipo ahora.') ?>
                                        <?php endif; ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="rp-note rp-note--block"><i class="fas fa-circle-check" aria-hidden="true"></i> Reservación liquidada · sin saldo pendiente</div>
                                <?php endif; ?>

                                <?php if (!empty($rpAbonos)): ?>
                                    <div style="margin-top:14px;">
                                        <small style="color:#667085;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Anticipos registrados</small>
                                        <?php foreach ($rpAbonos as $ab): ?>
                                            <?php
                                                $rpAbonoMetodo = strtolower((string)($ab['metodo_pago'] ?? ''));
                                                $rpAbonoTipoTarjeta = strtolower((string)($ab['tipo_tarjeta'] ?? ''));
                                                $rpAbonoMetodoLabel = ucfirst($rpAbonoMetodo);
                                                if ($rpAbonoMetodo === 'tarjeta' && in_array($rpAbonoTipoTarjeta, ['credito', 'debito'], true)) {
                                                    $rpAbonoMetodoLabel = 'Tarjeta ' . $rpAbonoTipoTarjeta;
                                                }
                                            ?>
                                            <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid #EEF1F4;padding:8px 0;">
                                                <div style="font-size:.85rem;">
                                                    <strong><?= $rdMoney($ab['monto']) ?></strong>
                                                    <span style="color:#667085;"> &middot; <?= $rdSafe($rpAbonoMetodoLabel) ?> &middot; <?= $rdSafe(date('d/m/Y H:i', strtotime((string)$ab['created_at']))) ?></span>
                                                </div>
                                                <form method="POST" action="<?= url('reservaciones/' . $rdReservationId . '/anticipo/revertir') ?>" data-ms-confirm data-ms-type="error" data-ms-icon="wallet" data-ms-title="¿Revertir anticipo?" data-ms-msg="Se generará un movimiento de caja de reverso por este anticipo." data-ms-ok="Sí, revertir" style="margin:0;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="abono_id" value="<?= (int)$ab['id'] ?>">
                                                    <button type="submit" style="background:none;border:none;color:#B4392B;cursor:pointer;font-size:.8rem;font-weight:600;"><i class="fas fa-rotate-left"></i> Revertir</button>
                                                </form>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <?php
                        // ── Bottom-sheet wizard de anticipo (SOLO móvil ≤780px) ──
                        // Reusa exactamente los mismos campos/acción/CSRF que el form de escritorio.
                        $rpSaldoNum  = (float)($rpResumen['saldo'] ?? 0);
                        $rpSaldoAttr = number_format($rpSaldoNum, 2, '.', '');
                        $rpMedioAttr = number_format($rpSaldoNum / 2, 2, '.', '');
                        $rpMetodoIcon = ['efectivo' => 'fa-money-bill-wave', 'tarjeta' => 'fa-credit-card', 'transferencia' => 'fa-building-columns'];
                        ?>
                        <?php if ($rpPuedeCobrar): ?>
                        <div class="rda-backdrop" id="rdaBackdrop" data-rp-close hidden></div>
                        <div class="rda-sheet" id="rdaSheet" role="dialog" aria-modal="true" aria-labelledby="rdaTitle" data-rda-saldo="<?= $rpSaldoAttr ?>" hidden>
                            <form method="POST" action="<?= url('reservaciones/' . $rdReservationId . '/anticipo') ?>" id="rdaForm" class="rda-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="concepto" value="<?= $rdSafe($rpConceptoCobro) ?>">
                                <header class="rda-head">
                                    <span class="rda-head-icon" aria-hidden="true"><i class="far fa-credit-card"></i></span>
                                    <div class="rda-head-txt">
                                        <h3 id="rdaTitle"><?= $rdSafe($rpBotonCobro) ?></h3>
                                        <p class="rda-sub"><?= $rdSafe($rdHuespedNombre) ?> · Reservación #<?= (int)$rdReservationId ?></p>
                                    </div>
                                    <button type="button" class="rda-close" data-rp-close aria-label="Cerrar"><i class="fas fa-xmark"></i></button>
                                </header>
                                <div class="rda-stepper" aria-hidden="true">
                                    <div class="rda-step is-current" data-rda-ind="1"><span>1</span> Anticipo</div>
                                    <div class="rda-line" data-rda-line></div>
                                    <div class="rda-step" data-rda-ind="2"><span>2</span> Factura</div>
                                </div>
                                <div class="rda-body">
                                    <section class="rda-stage is-active" data-rda-stage="1">
                                        <div class="rda-stats">
                                            <div class="rda-stat"><small>Total</small><strong><?= $rdMoney($rpResumen['total']) ?></strong></div>
                                            <div class="rda-stat is-paid"><small>Pagado</small><strong><?= $rdMoney($rpResumen['pagado']) ?></strong></div>
                                            <div class="rda-stat is-balance"><small>Saldo</small><strong><?= $rdMoney($rpResumen['saldo']) ?></strong></div>
                                        </div>
                                        <label class="rda-label" for="rdaMonto"><?= $rdSafe($rpMontoLabel) ?></label>
                                        <div class="rda-money">
                                            <span>$</span>
                                            <input type="text" id="rdaMonto" name="monto" value="<?= $rpSaldoNum > 0.004 ? $rpSaldoAttr : '' ?>" data-money-format="true" placeholder="0.00" inputmode="decimal" autocomplete="off">
                                        </div>
                                        <div class="rda-chips">
                                            <button type="button" class="rda-chip" data-rda-fill="<?= $rpMedioAttr ?>">50% del saldo · <?= $rdMoney($rpSaldoNum / 2) ?></button>
                                            <button type="button" class="rda-chip" data-rda-fill="<?= $rpSaldoAttr ?>">Saldo completo · <?= $rdMoney($rpSaldoNum) ?></button>
                                        </div>
                                        <label class="rda-label">Método de pago</label>
                                        <div class="rda-methods">
                                            <?php $rpFirst = true; foreach (($rpEval['metodos_pago'] ?? []) as $mk => $ml): ?>
                                                <label class="rda-method rda-m-<?= $rdSafe($mk) ?>">
                                                    <input type="radio" name="metodo_pago" value="<?= $rdSafe($mk) ?>" <?= $rpFirst ? 'checked' : '' ?> data-rda-metodo>
                                                    <i class="fas <?= $rpMetodoIcon[$mk] ?? 'fa-wallet' ?>" aria-hidden="true"></i>
                                                    <span><?= $rdSafe($ml) ?></span>
                                                </label>
                                            <?php $rpFirst = false; endforeach; ?>
                                        </div>
                                        <div class="rda-tarjeta" data-rda-tarjeta hidden>
                                            <label class="rda-label">Tipo de tarjeta</label>
                                            <div class="rda-tt">
                                                <label class="rda-ttopt"><input type="radio" name="tipo_tarjeta_anticipo" value="debito" disabled> <i class="fas fa-money-check-alt" aria-hidden="true"></i> Débito</label>
                                                <label class="rda-ttopt"><input type="radio" name="tipo_tarjeta_anticipo" value="credito" disabled> <i class="fas fa-credit-card" aria-hidden="true"></i> Crédito</label>
                                            </div>
                                            <p class="rda-inv-hint" data-rda-tt-hint hidden><i class="fas fa-circle-info" aria-hidden="true"></i> Selecciona débito o crédito para continuar.</p>
                                        </div>
                                        <div class="rda-after">
                                            <span>Saldo después del anticipo</span>
                                            <b data-rda-after>$0.00</b>
                                        </div>
                                    </section>
                                    <section class="rda-stage" data-rda-stage="2" hidden>
                                        <div class="rda-inv-q"><i class="fas fa-file-invoice" aria-hidden="true"></i> ¿El cliente requiere factura?</div>
                                        <label class="rda-radio-card">
                                            <input type="radio" name="requiere_factura" value="si" data-rda-factura>
                                            <span class="rc-dot"></span>
                                            <span class="rc-txt"><strong>Factura para cliente</strong><small>Se emitirá comprobante fiscal</small></span>
                                        </label>
                                        <label class="rda-radio-card">
                                            <input type="radio" name="requiere_factura" value="no" data-rda-factura>
                                            <span class="rc-dot"></span>
                                            <span class="rc-txt"><strong>Sin factura</strong><small>Solo recibo interno</small></span>
                                        </label>
                                        <p class="rda-inv-hint" data-rda-inv-hint><i class="fas fa-circle-info" aria-hidden="true"></i> Indica si el cliente requiere factura para continuar.</p>
                                        <?php if (!empty($anticipoFacturaSolicitud)): ?>
                                            <div class="rda-inv-pending">
                                                <div class="rda-inv-pending-h"><i class="fas fa-file-lines" aria-hidden="true"></i> Factura pendiente #<?= (int)($anticipoFacturaSolicitud['id'] ?? 0) ?> · <?= $rdMoney($anticipoFacturaSolicitud['monto_total'] ?? 0) ?></div>
                                                <label class="rda-radio-card sm"><input type="radio" name="factura_modo" value="acumular"><span class="rc-dot"></span><span class="rc-txt"><strong>Sumar a factura pendiente</strong></span></label>
                                                <label class="rda-radio-card sm"><input type="radio" name="factura_modo" value="separada" checked><span class="rc-dot"></span><span class="rc-txt"><strong>Crear factura separada</strong></span></label>
                                            </div>
                                        <?php endif; ?>
                                        <div class="rda-summary">
                                            <div class="rda-summary-h">Resumen del anticipo</div>
                                            <div class="rda-summary-row"><span>Monto</span><b data-rda-sum-monto>$0.00</b></div>
                                            <div class="rda-summary-row"><span>Método</span><b data-rda-sum-metodo>—</b></div>
                                            <div class="rda-summary-row"><span>Factura</span><b data-rda-sum-factura>Sin factura</b></div>
                                            <div class="rda-summary-row is-total"><span>Saldo restante</span><b data-rda-sum-saldo>$0.00</b></div>
                                        </div>
                                    </section>
                                </div>
                                <footer class="rda-foot">
                                    <button type="button" class="rda-btn rda-btn-ghost" data-rda-back hidden><i class="fas fa-arrow-left" aria-hidden="true"></i> Atrás</button>
                                    <button type="button" class="rda-btn rda-btn-navy" data-rda-next>Continuar <i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                                    <button type="submit" class="rda-btn rda-btn-green" data-rda-submit hidden><i class="fas fa-check" aria-hidden="true"></i> <?= $rdSafe($rpBotonCobro) ?></button>
                                </footer>
                            </form>
                        </div>
                        <?php endif; ?>

                        <style>
                        /* ── Tarjeta "Pagos y anticipos" ── */
                        #rdv3-payment-section .rdv3-card-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
                        .rp-status { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-size: .74rem; font-weight: 800; white-space: nowrap; }
                        .rp-status .rp-status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
                        .rp-status.is-done { background: #E7F5EC; color: #15835A; }
                        .rp-status.is-pending { background: #FDF1E3; color: #B4540F; }
                        .rp-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 14px; }
                        .rp-stat { border: 1px solid #ECE7DC; border-radius: 12px; padding: 12px 10px; text-align: center; background: #FDFCF9; }
                        .rp-stat small { display: block; margin-bottom: 3px; color: #94A3B8; font-size: .64rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
                        .rp-stat strong { font-size: 1.12rem; font-weight: 800; color: #1E293B; font-variant-numeric: tabular-nums; }
                        .rp-stat.is-paid small, .rp-stat.is-paid strong { color: #15835A; }
                        .rp-stat.is-balance small, .rp-stat.is-balance strong { color: #B4540F; }
                        .rp-progress { height: 8px; border-radius: 999px; background: #EEEAE0; overflow: hidden; margin: 2px 0 14px; }
                        .rp-progress i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #16A06A, #12805A); transition: width .5s cubic-bezier(.22,1,.36,1); }
                        .rp-progress.is-pending i { background: linear-gradient(90deg, #E7A24C, #C87E23); }
                        .rp-actionrow { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
                        .rp-note { display: inline-flex; align-items: center; gap: 8px; color: #475569; font-size: .86rem; font-weight: 600; }
                        .rp-note i { color: #15835A; }
                        .rp-note--block { margin-top: 2px; }
                        .rp-warn { display: flex; align-items: flex-start; gap: 8px; background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px; padding: 10px 12px; color: #9A3412; font-size: .84rem; }
                        .rp-cta { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 46px; padding: 0 22px; border: 0; border-radius: 12px; cursor: pointer; background: linear-gradient(135deg, #15835A, #0E6C49); color: #fff; font-size: .9rem; font-weight: 800; letter-spacing: .01em; box-shadow: 0 8px 18px -10px rgba(21,131,90,.6); }
                        .rp-cta:hover { filter: brightness(1.06); }
                        .rp-cta:active { transform: translateY(1px); }
                        @media (max-width: 780px) {
                            .rp-actionrow { flex-direction: column; align-items: stretch; }
                            .rp-cta { width: 100%; min-height: 48px; }
                        }
                        /* ── Header del modal: icono + título + subtítulo ── */
                        .rda-head-icon { flex: 0 0 auto; width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; background: #E7F5EC; color: #15835A; font-size: 1rem; }
                        .rda-head-txt { flex: 1 1 auto; min-width: 0; }
                        .rda-sub { margin: 2px 0 0; font-size: .78rem; font-weight: 600; color: #64748B; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                        .rda-backdrop { position: fixed; inset: 0; z-index: 10060; background: rgba(15,23,42,.5); opacity: 0; transition: opacity .25s ease; }
                        .rda-backdrop.is-open { opacity: 1; }
                        .rda-sheet[hidden], .rda-backdrop[hidden] { display: none !important; }
                        .rda-sheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10061; max-height: 92dvh; display: flex; flex-direction: column; background: #FBF9F5; border-radius: 20px 20px 0 0; box-shadow: 0 -18px 48px -20px rgba(15,23,42,.4); transform: translateY(100%); transition: transform .3s cubic-bezier(.22,1,.36,1); padding-bottom: env(safe-area-inset-bottom, 0px); }
                        .rda-sheet.is-open { transform: translateY(0); }
                        /* ── En escritorio el mismo wizard es un modal CENTRADO ── */
                        /* Debe ir DESPUÉS de las reglas base móviles: misma especificidad,
                           gana por orden de fuente y sobrescribe el bottom-sheet. */
                        @media (min-width: 781px) {
                            .rda-backdrop { background: rgba(15,23,42,.45); }
                            .rda-sheet {
                                left: 50%; right: auto; top: 50%; bottom: auto;
                                width: min(470px, calc(100vw - 40px));
                                max-height: 90vh;
                                border-radius: 20px;
                                padding-bottom: 0;
                                transform: translate(-50%, -47%) scale(.98);
                                opacity: 0;
                                transition: transform .24s cubic-bezier(.22,1,.36,1), opacity .18s ease;
                            }
                            .rda-sheet.is-open { transform: translate(-50%, -50%) scale(1); opacity: 1; }
                        }
                        .rda-form { display: flex; flex-direction: column; min-height: 0; }
                        .rda-head { display: flex; align-items: center; gap: 12px; padding: 20px 26px 14px !important; }
                        .rda-head h3 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 1.28rem; color: #1E293B; font-weight: 600; }
                        .rda-close { flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; border: 1px solid #E5E7EB; background: #fff; color: #64748B; cursor: pointer; }
                        .rda-stepper { display: flex; align-items: center; gap: 10px; padding: 0 26px 10px; }
                        .rda-step { display: flex; align-items: center; gap: 7px; font-size: .82rem; font-weight: 700; color: #94A3B8; }
                        .rda-step span { width: 20px; height: 20px; border-radius: 50%; display: grid; place-items: center; background: #E2E8F0; color: #64748B; font-size: .72rem; font-weight: 800; }
                        .rda-step.is-current { color: #1E293B; }
                        .rda-step.is-current span { background: #1E293B; color: #fff; }
                        .rda-step.is-done span { background: #15835A; color: #fff; }
                        .rda-line { flex: 1; height: 2px; background: #E2E8F0; border-radius: 2px; transition: background .25s ease; }
                        .rda-line.is-done { background: #15835A; }
                        .rda-body { overflow-y: auto; padding: 8px 26px 16px; -webkit-overflow-scrolling: touch; }
                        .rda-stage { display: none; }
                        .rda-stage.is-active { display: block; }
                        .rda-stats { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; margin-bottom: 14px; }
                        .rda-stat { border: 1px solid #EAE6DE; border-radius: 12px; padding: 9px 6px; text-align: center; background: #fff; }
                        .rda-stat small { display: block; color: #94A3B8; font-size: .6rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 2px; }
                        .rda-stat strong { font-size: 1.02rem; font-weight: 800; color: #1E293B; font-variant-numeric: tabular-nums; }
                        .rda-stat.is-paid small, .rda-stat.is-paid strong { color: #15835A; }
                        .rda-stat.is-balance small, .rda-stat.is-balance strong { color: #B4540F; }
                        .rda-label { display: block; font-size: .82rem; font-weight: 700; color: #334155; margin: 12px 0 7px; }
                        .rda-money { display: flex; align-items: center; gap: 8px; border: 1px solid #E5E7EB; border-radius: 12px; padding: 2px 14px; background: #fff; }
                        .rda-money span { color: #94A3B8; font-size: 1.15rem; font-weight: 800; }
                        .rda-money input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; font-size: 1.5rem; font-weight: 700; color: #1E293B; padding: 10px 0; font-variant-numeric: tabular-nums; -moz-appearance: textfield; }
                        .rda-money input::-webkit-outer-spin-button, .rda-money input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
                        .rda-chips { display: flex; gap: 8px; margin-top: 10px; }
                        .rda-chip { flex: 1; min-height: 40px; padding: 0 8px; border: 1px solid #E5E7EB; border-radius: 10px; background: #fff; color: #475569; font-size: .78rem; font-weight: 700; cursor: pointer; }
                        .rda-chip.is-active { border-color: #15835A; background: #ECFDF3; color: #0E6C49; }
                        .rda-methods { display: grid; grid-template-columns: repeat(3,1fr); gap: 8px; }
                        .rda-method { position: relative; display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 12px 6px; border: 1px solid #E5E7EB; border-radius: 12px; background: #fff; cursor: pointer; text-align: center; }
                        .rda-method input { position: absolute; opacity: 0; pointer-events: none; }
                        .rda-method i { width: 34px; height: 34px; border-radius: 9px; display: grid; place-items: center; background: #F1F5F9; color: #64748B; font-size: .95rem; }
                        .rda-method span { font-size: .78rem; font-weight: 700; color: #334155; }
                        .rda-method.is-sel.rda-m-efectivo { border-color: #15835A; background: #F0FBF5; }
                        .rda-method.is-sel.rda-m-efectivo i { background: #DCFCE7; color: #15835A; }
                        .rda-method.is-sel.rda-m-tarjeta { border-color: #2563EB; background: #EFF6FF; }
                        .rda-method.is-sel.rda-m-tarjeta i { background: #DBEAFE; color: #2563EB; }
                        .rda-method.is-sel.rda-m-transferencia { border-color: #7C3AED; background: #F5F3FF; }
                        .rda-method.is-sel.rda-m-transferencia i { background: #EDE9FE; color: #7C3AED; }
                        .rda-tarjeta { margin-top: 8px; }
                        .rda-tt { display: flex; gap: 8px; }
                        .rda-ttopt { position: relative; flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; min-height: 40px; border: 1px solid #BFDBFE; border-radius: 10px; background: #fff; font-size: .8rem; font-weight: 700; color: #1E3A8A; cursor: pointer; }
                        .rda-ttopt input { position: absolute; opacity: 0; }
                        .rda-ttopt.is-sel { border-color: #2563EB; background: #EFF6FF; }
                        .rda-after { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; padding: 12px 14px; border: 1px solid #EAE6DE; border-radius: 12px; background: #fff; }
                        .rda-after span { color: #475569; font-size: .84rem; font-weight: 600; }
                        .rda-after b { font-size: 1.05rem; font-weight: 800; color: #15835A; font-variant-numeric: tabular-nums; }
                        .rda-after.is-pending b { color: #B4540F; }
                        .rda-inv-q { display: flex; align-items: center; gap: 8px; font-size: .9rem; font-weight: 800; color: #1E293B; margin: 4px 0 12px; }
                        .rda-radio-card { position: relative; display: flex; align-items: center; gap: 12px; padding: 13px 14px; border: 1px solid #E5E7EB; border-radius: 12px; background: #fff; cursor: pointer; margin-bottom: 10px; }
                        .rda-radio-card input { position: absolute; opacity: 0; }
                        .rda-radio-card .rc-dot { flex: 0 0 auto; width: 20px; height: 20px; border-radius: 50%; border: 2px solid #CBD5E1; position: relative; }
                        .rda-radio-card.is-sel { border-color: #2563EB; background: #EFF6FF; }
                        .rda-radio-card.is-sel .rc-dot { border-color: #2563EB; }
                        .rda-radio-card.is-sel .rc-dot::after { content: ''; position: absolute; inset: 3px; border-radius: 50%; background: #2563EB; }
                        .rda-radio-card .rc-txt { display: flex; flex-direction: column; gap: 1px; }
                        .rda-radio-card .rc-txt strong { font-size: .88rem; font-weight: 700; color: #1E293B; }
                        .rda-radio-card .rc-txt small { font-size: .74rem; color: #64748B; }
                        .rda-radio-card.sm { padding: 10px 12px; margin-bottom: 8px; }
                        .rda-radio-card.sm .rc-txt strong { font-size: .82rem; }
                        .rda-inv-pending { margin: 6px 0 14px; padding: 12px; border: 1px solid #FDE8C8; border-radius: 12px; background: #FEF6E9; }
                        .rda-inv-pending-h { display: flex; align-items: center; gap: 7px; font-size: .78rem; font-weight: 800; color: #8A5A12; margin-bottom: 9px; }
                        .rda-summary { border: 1px solid #E5E7EB; border-radius: 12px; overflow: hidden; margin-top: 6px; }
                        .rda-summary-h { background: #1E293B; color: #fff; font-size: .86rem; font-weight: 700; padding: 11px 14px; }
                        .rda-summary-row { display: flex; align-items: center; justify-content: space-between; padding: 11px 14px; border-top: 1px solid #EEF1F4; font-size: .85rem; }
                        .rda-summary-row span { color: #64748B; }
                        .rda-summary-row b { color: #1E293B; font-weight: 800; font-variant-numeric: tabular-nums; }
                        .rda-summary-row.is-total { background: #F0FBF5; }
                        .rda-summary-row.is-total b { color: #15835A; }
                        .rda-foot { display: flex; gap: 10px; padding: 12px 26px calc(12px + env(safe-area-inset-bottom, 0px)); border-top: 1px solid #EEE9E0; background: #fff; }
                        .rda-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 50px; border-radius: 13px; border: 0; font-size: .92rem; font-weight: 800; cursor: pointer; }
                        .rda-btn[hidden] { display: none !important; }
                        .rda-btn-navy { background: #1E293B; color: #fff; }
                        .rda-btn-green { background: linear-gradient(135deg, #15835A, #0E6C49); color: #fff; }
                        .rda-btn-ghost { flex: 0 0 auto; padding: 0 18px; background: #fff; border: 1px solid #D7DBDF; color: #334155; }
                        .rda-btn:active { transform: translateY(1px); }
                        .rda-btn:disabled { cursor: not-allowed; }
                        .rda-btn.is-wait { background: #EEF1F4 !important; color: #94A3B8 !important; box-shadow: none !important; }
                        .rda-inv-hint { display: flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 600; color: #B4540F; margin: -2px 0 12px; }
                        .rda-inv-hint[hidden] { display: none; }
                        </style>
                        <script>
                        (function () {
                            var sheet = document.getElementById('rdaSheet');
                            if (!sheet) return;
                            var backdrop = document.getElementById('rdaBackdrop');
                            var form = document.getElementById('rdaForm');
                            var saldo = parseFloat(sheet.getAttribute('data-rda-saldo')) || 0;
                            var monto = document.getElementById('rdaMonto');
                            var afterBox = sheet.querySelector('.rda-after');
                            var afterVal = sheet.querySelector('[data-rda-after]');
                            var stage1 = sheet.querySelector('[data-rda-stage="1"]');
                            var stage2 = sheet.querySelector('[data-rda-stage="2"]');
                            var ind1 = sheet.querySelector('[data-rda-ind="1"]');
                            var ind2 = sheet.querySelector('[data-rda-ind="2"]');
                            var line = sheet.querySelector('[data-rda-line]');
                            var btnNext = sheet.querySelector('[data-rda-next]');
                            var btnBack = sheet.querySelector('[data-rda-back]');
                            var btnSubmit = sheet.querySelector('[data-rda-submit]');
                            var tarjetaBox = sheet.querySelector('[data-rda-tarjeta]');
                            var invHint = sheet.querySelector('[data-rda-inv-hint]');
                            var ttHint = sheet.querySelector('[data-rda-tt-hint]');
                            var submitHTML = btnSubmit ? btnSubmit.innerHTML : '';

                            function money(n) {
                                n = Math.round(n * 100) / 100;
                                return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                            function parseMonto() {
                                var v = (monto.value || '').toString().replace(/[^0-9.]/g, '');
                                var n = parseFloat(v);
                                return isNaN(n) ? 0 : n;
                            }
                            function syncSel() {
                                sheet.querySelectorAll('.rda-method').forEach(function (el) { el.classList.toggle('is-sel', !!el.querySelector('input:checked')); });
                                sheet.querySelectorAll('.rda-ttopt').forEach(function (el) { el.classList.toggle('is-sel', !!el.querySelector('input:checked')); });
                                sheet.querySelectorAll('.rda-radio-card').forEach(function (el) { el.classList.toggle('is-sel', !!el.querySelector('input:checked')); });
                            }
                            function recalc() {
                                var m = parseMonto();
                                var after = saldo - m; if (after < 0) after = 0;
                                afterVal.textContent = money(after);
                                afterBox.classList.toggle('is-pending', after > 0.004);
                                sheet.querySelectorAll('.rda-chip').forEach(function (c) {
                                    c.classList.toggle('is-active', Math.abs(parseFloat(c.getAttribute('data-rda-fill')) - m) < 0.005);
                                });
                            }
                            function metodoLabel() {
                                var r = sheet.querySelector('[data-rda-metodo]:checked');
                                if (!r) return '—';
                                var s = r.parentElement.querySelector('span');
                                var lbl = s ? s.textContent : r.value;
                                if (r.value === 'tarjeta') {
                                    var tt = sheet.querySelector('input[name="tipo_tarjeta_anticipo"]:checked');
                                    if (tt) lbl += ' · ' + (tt.value === 'credito' ? 'Crédito' : 'Débito');
                                }
                                return lbl;
                            }
                            function facturaLabel() {
                                var f = sheet.querySelector('input[name="requiere_factura"]:checked');
                                if (!f || f.value === 'no') return 'Sin factura';
                                var modo = sheet.querySelector('input[name="factura_modo"]:checked');
                                return 'Cliente' + (modo ? ' · ' + (modo.value === 'separada' ? 'Separada' : 'Sumada') : '');
                            }
                            function fillSummary() {
                                var m = parseMonto();
                                var after = saldo - m; if (after < 0) after = 0;
                                sheet.querySelector('[data-rda-sum-monto]').textContent = money(m);
                                sheet.querySelector('[data-rda-sum-metodo]').textContent = metodoLabel();
                                sheet.querySelector('[data-rda-sum-factura]').textContent = facturaLabel();
                                sheet.querySelector('[data-rda-sum-saldo]').textContent = money(after);
                            }
                            function updateGate() {
                                if (!btnSubmit) return;
                                var chosen = !!sheet.querySelector('input[name="requiere_factura"]:checked');
                                btnSubmit.disabled = !chosen;
                                btnSubmit.classList.toggle('is-wait', !chosen);
                                btnSubmit.innerHTML = chosen ? submitHTML : 'Continuar';
                                if (invHint) invHint.hidden = chosen;
                            }
                            function goStep(n) {
                                if (n === 1) {
                                    stage1.hidden = false; stage1.classList.add('is-active');
                                    stage2.hidden = true; stage2.classList.remove('is-active');
                                    ind1.className = 'rda-step is-current'; ind2.className = 'rda-step'; line.classList.remove('is-done');
                                    btnNext.hidden = false; btnBack.hidden = true; btnSubmit.hidden = true;
                                } else {
                                    stage1.hidden = true; stage1.classList.remove('is-active');
                                    stage2.hidden = false; stage2.classList.add('is-active');
                                    ind1.className = 'rda-step is-done'; ind2.className = 'rda-step is-current'; line.classList.add('is-done');
                                    btnNext.hidden = true; btnBack.hidden = false; btnSubmit.hidden = false;
                                    fillSummary();
                                    updateGate();
                                }
                            }
                            function open() {
                                sheet.hidden = false; backdrop.hidden = false;
                                void sheet.offsetHeight; // fuerza reflow para que el translateY anime desde abajo
                                sheet.classList.add('is-open'); backdrop.classList.add('is-open');
                                document.body.style.overflow = 'hidden';
                                goStep(1); syncSel(); recalc();
                            }
                            function close() {
                                sheet.classList.remove('is-open'); backdrop.classList.remove('is-open');
                                document.body.style.overflow = '';
                                setTimeout(function () { sheet.hidden = true; backdrop.hidden = true; }, 300);
                            }
                            document.querySelectorAll('[data-rp-open]').forEach(function (b) { b.addEventListener('click', open); });
                            document.querySelectorAll('[data-rp-close]').forEach(function (b) { b.addEventListener('click', close); });
                            btnNext.addEventListener('click', function () {
                                var m = parseMonto();
                                if (m <= 0) { monto.focus(); return; }
                                if (m > saldo + 0.01) { monto.value = saldo.toFixed(2); recalc(); }
                                var mm = sheet.querySelector('[data-rda-metodo]:checked');
                                if (mm && mm.value === 'tarjeta' && !sheet.querySelector('input[name="tipo_tarjeta_anticipo"]:checked')) {
                                    if (ttHint) ttHint.hidden = false;
                                    if (tarjetaBox) tarjetaBox.scrollIntoView({ block: 'nearest' });
                                    return;
                                }
                                goStep(2);
                            });
                            btnBack.addEventListener('click', function () { goStep(1); });
                            sheet.querySelectorAll('[data-rda-fill]').forEach(function (chip) {
                                chip.addEventListener('click', function () {
                                    monto.value = parseFloat(chip.getAttribute('data-rda-fill')).toFixed(2);
                                    monto.dispatchEvent(new Event('input', { bubbles: true }));
                                    recalc();
                                });
                            });
                            monto.addEventListener('input', recalc);
                            sheet.querySelectorAll('[data-rda-metodo]').forEach(function (r) {
                                r.addEventListener('change', function () {
                                    var isTarjeta = (r.value === 'tarjeta' && r.checked);
                                    if (tarjetaBox) {
                                        tarjetaBox.hidden = !isTarjeta;
                                        tarjetaBox.querySelectorAll('input[name="tipo_tarjeta_anticipo"]').forEach(function (t) { t.disabled = !isTarjeta; if (!isTarjeta) t.checked = false; });
                                    }
                                    if (ttHint) ttHint.hidden = true;
                                    syncSel();
                                });
                            });
                            sheet.querySelectorAll('input[name="requiere_factura"], input[name="factura_modo"], input[name="tipo_tarjeta_anticipo"]').forEach(function (i) {
                                i.addEventListener('change', function () { if (ttHint) ttHint.hidden = true; syncSel(); fillSummary(); updateGate(); });
                            });
                            form.addEventListener('submit', function () {
                                document.body.style.overflow = '';
                                if (btnSubmit) { btnSubmit.disabled = true; btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Registrando...'; }
                            });
                        })();
                        </script>
                        <script>
                            function rdv3ToggleTipoTarjetaAnticipo() {
                                const metodo = document.querySelector('[data-rdv3-anticipo-metodo]');
                                const panel = document.querySelector('[data-rdv3-anticipo-tarjeta]');
                                if (!metodo || !panel) {
                                    return;
                                }
                                const mostrar = metodo.value === 'tarjeta';
                                panel.style.display = mostrar ? 'block' : 'none';
                                panel.querySelectorAll('input[name="tipo_tarjeta_anticipo"]').forEach((input) => {
                                    input.disabled = !mostrar;
                                    input.required = mostrar;
                                    if (!mostrar) {
                                        input.checked = false;
                                    }
                                });
                            }
                            document.addEventListener('DOMContentLoaded', rdv3ToggleTipoTarjetaAnticipo);
                        </script>

                        <section class="rdv3-card rdv3-card--rooms">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon rdv3-icon--violet"><i class="fas fa-bed"></i></span>
                                    <h2 class="rdv3-card-title">Habitaciones reservadas</h2>
                                </div>
                                <div class="rdv3-header-badges">
                                    <span class="rdv3-badge rdv3-badge--count"><i class="fas fa-key"></i><?= $rdSafe($rdRoomCountLabel) ?></span>
                                    <?php if ($rdCortesias > 0): ?>
                                        <span class="rdv3-badge"><i class="fas fa-gift"></i><?= $rdCortesias ?> cortesia<?= $rdCortesias === 1 ? '' : 's' ?></span>
                                    <?php endif; ?>
                                    <?php if ($rdEstadoKey === 'confirmada'): ?>
                                        <a class="rdv3-badge rdv3-badge--edit" href="<?= url('reservaciones/editar-habitaciones/' . $reservacion['id']) ?>" title="Modificar habitaciones de la reservacion"><i class="fas fa-pen"></i>Modificar</a>
                                    <?php endif; ?>
                                </div>
                            </header>
                            <div class="rdv3-card-body">
                                <?php if (empty($rdRooms)): ?>
                                    <div class="rdv3-empty">No hay habitaciones asociadas a esta reservacion.</div>
                                <?php else: ?>
                                    <?php
                                    $rdRoomsDiscountTotal = max(0.0, (float)($reservacion['descuento_total'] ?? 0));
                                    $rdRoomsChargeableSubtotal = 0.0;
                                    $rdRoomsDiscountEligibleCount = 0;

                                    foreach ($rdRooms as $discountRoom) {
                                        $discountRoomIsCourtesy = !empty($discountRoom['es_cortesia']) || !empty($discountRoom['cortesia']);
                                        $discountRoomSubtotal = (float)($discountRoom['precio'] ?? $discountRoom['precio_total'] ?? 0);

                                        if (!$discountRoomIsCourtesy && $discountRoomSubtotal <= 0 && isset($discountRoom['precio_base'])) {
                                            $discountRoomSubtotal = (float)$discountRoom['precio_base'] * max(1, (int)$rdNoches);
                                        }

                                        if (!$discountRoomIsCourtesy && $discountRoomSubtotal > 0) {
                                            $rdRoomsChargeableSubtotal += $discountRoomSubtotal;
                                            $rdRoomsDiscountEligibleCount++;
                                        }
                                    }

                                    $rdRoomsDiscountPool = min($rdRoomsDiscountTotal, $rdRoomsChargeableSubtotal);
                                    $rdRoomsDiscountAllocated = 0.0;
                                    $rdRoomsDiscountIndex = 0;
                                    ?>
                                    <div class="rdv3-rooms">
                                        <?php foreach ($rdRooms as $room): ?>
                                            <?php
                                            $roomIsCourtesy = !empty($room['es_cortesia']) || !empty($room['cortesia']);
                                            $roomType = $rdRoomTypeLabel($room);
                                            $roomPeople = $room['capacidad_personas'] ?? $room['personas'] ?? $room['capacidad'] ?? null;
                                            $roomSubtotal = (float)($room['precio'] ?? $room['precio_total'] ?? 0);
                                            $roomNightly = $rdNoches > 0 ? ($roomSubtotal / $rdNoches) : $roomSubtotal;
                                            if (!$roomIsCourtesy && $roomSubtotal <= 0 && isset($room['precio_base'])) {
                                                $roomNightly = (float)$room['precio_base'];
                                                $roomSubtotal = $roomNightly * $rdNoches;
                                            }
                                            $roomDiscount = 0.0;
                                            if (!$roomIsCourtesy && $roomSubtotal > 0 && $rdRoomsDiscountPool > 0 && $rdRoomsChargeableSubtotal > 0 && $rdRoomsDiscountEligibleCount > 0) {
                                                $rdRoomsDiscountIndex++;

                                                if ($rdRoomsDiscountIndex >= $rdRoomsDiscountEligibleCount) {
                                                    $roomDiscount = max(0.0, $rdRoomsDiscountPool - $rdRoomsDiscountAllocated);
                                                } else {
                                                    $roomDiscount = round($rdRoomsDiscountPool * ($roomSubtotal / $rdRoomsChargeableSubtotal), 2);
                                                    $roomDiscount = min($roomDiscount, max(0.0, $rdRoomsDiscountPool - $rdRoomsDiscountAllocated));
                                                    $rdRoomsDiscountAllocated += $roomDiscount;
                                                }
                                            }
                                            $roomTotalAfterDiscount = max(0.0, $roomSubtotal - $roomDiscount);
                                            $roomLocation = !empty($room['piso']) ? 'Piso ' . $room['piso'] : 'Sin piso';
                                            ?>
                                            <article class="rdv3-room <?= $roomIsCourtesy ? 'is-courtesy' : '' ?>">
                                                <div class="rdv3-room-top">
                                                    <div class="min-w-0">
                                                        <div class="rdv3-room-number" title="<?= $rdSafe($room['numero'] ?? 'S/N') ?>"><?= $rdSafe($room['numero'] ?? 'S/N') ?></div>
                                                        <div class="rdv3-room-type"><?= $rdSafe($roomType) ?></div>
                                                        <div class="rdv3-room-sub">
                                                            <span class="rdv3-tag"><i class="fas fa-layer-group"></i><?= $rdSafe($roomLocation) ?></span>
                                                            <?php if ($roomPeople): ?><span class="rdv3-tag"><i class="fas fa-users"></i><?= (int)$roomPeople ?> persona<?= (int)$roomPeople === 1 ? '' : 's' ?></span><?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="rdv3-room-price">
                                                        <?= $roomIsCourtesy ? 'Cortesia' : $rdMoney($roomSubtotal) ?>
                                                        <small><?= $roomDiscount > 0 ? 'Antes de descuento' : 'Subtotal' ?></small>
                                                    </div>
                                                </div>
                                                <div class="rdv3-room-metrics">
                                                    <div class="rdv3-room-metric"><small>Precio/noche</small><b><?= $roomIsCourtesy ? 'Gratis' : $rdMoney($roomNightly) ?></b></div>
                                                    <div class="rdv3-room-metric"><small>Noches</small><b><?= (int)$rdNoches ?></b></div>
                                                    <div class="rdv3-room-metric"><small><?= $roomDiscount > 0 ? 'Subtotal base' : 'Subtotal' ?></small><b><?= $roomIsCourtesy ? $rdMoney(0) : $rdMoney($roomSubtotal) ?></b></div>
                                                </div>
                                                <?php if ($roomDiscount > 0): ?>
                                                    <div class="rdv3-room-discount">
                                                        <span><i class="fas fa-tag"></i>Descuento del hu&eacute;sped</span>
                                                        <strong>&minus;<?= $rdMoney($roomDiscount) ?></strong>
                                                    </div>
                                                    <div class="rdv3-room-net">
                                                        <span>Total despu&eacute;s de descuento</span>
                                                        <strong><?= $rdMoney($roomTotalAfterDiscount) ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-card--guest"<?= $rdGuestEditable ? ' data-huesped-id="' . $rdGuestEditId . '"' : '' ?>>
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon rdv3-icon--violet"><i class="far fa-user"></i></span>
                                    <h2 class="rdv3-card-title">Datos del huesped</h2>
                                </div>
                                <?php if (!empty($huesped['id'])): ?>
                                    <a class="rdv3-link" href="<?= url('huespedes/' . (int)$huesped['id']) ?>">Ver ficha &rarr;</a>
                                <?php endif; ?>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rdv3-guest-head">
                                    <div class="rdv3-avatar"><?= $rdSafe($rdHuespedIniciales, 'H') ?></div>
                                    <div>
                                        <h2 class="rdv3-guest-name<?= $rdGuestEditable ? ' rd-editable' : '' ?>"<?= $rdGuestEditable ? ' data-field="nombre_completo" data-value="' . $rdSafe($rdGuestName, '') . '" data-empty="Sin nombre" data-type="text" tabindex="0" role="button" title="Clic para editar"' : '' ?>><?= $rdSafe($rdGuestName, 'Huesped') ?></h2>
                                        <div class="rdv3-guest-sub"><?= $rdSafe($huesped['tipo_cliente'] ?? 'Cliente') ?><?= $rdGuestOrigin !== '' ? ' - ' . $rdSafe($rdGuestOrigin) : '' ?></div>
                                    </div>
                                </div>

                                <div class="rdv3-info-grid" aria-label="Contacto del huesped">
                                    <div class="rdv3-info <?= $rdGuestPhone === '' ? 'is-empty' : '' ?>"><i class="fas fa-phone"></i><div><small>Telefono</small><b<?= $rdEditAttr('telefono', $rdGuestPhone, 'No registrado', 'tel') ?>><?= $rdSafe($rdGuestPhone, 'No registrado') ?></b></div></div>
                                    <div class="rdv3-info <?= $rdGuestEmail === '' ? 'is-empty' : '' ?>"><i class="far fa-envelope"></i><div><small>Email</small><b<?= $rdEditAttr('email', $rdGuestEmail, 'No registrado', 'email') ?>><?= $rdSafe($rdGuestEmail, 'No registrado') ?></b></div></div>
                                    <div class="rdv3-info <?= $rdGuestId === '' ? 'is-empty' : '' ?>"><i class="far fa-address-card"></i><div><small>Identificacion</small><b><?= $rdSafe($rdGuestId, 'No registrado') ?></b></div></div>
                                    <div class="rdv3-info <?= $rdGuestOrigin === '' ? 'is-empty' : '' ?>"><i class="fas fa-location-dot"></i><div><small>Procedencia</small><b><?= $rdSafe($rdGuestOrigin, 'No registrado') ?></b></div></div>
                                </div>

                                <div class="rdv3-contact-actions" aria-label="Acciones rapidas de contacto">
                                    <?php if ($rdGuestTelHref !== ''): ?>
                                        <a class="rdv3-contact-action" href="<?= $rdSafe($rdGuestTelHref, '') ?>"><i class="fas fa-phone"></i>Llamar</a>
                                    <?php else: ?>
                                        <span class="rdv3-contact-action is-disabled" aria-disabled="true"><i class="fas fa-phone"></i>Llamar</span>
                                    <?php endif; ?>

                                    <?php if ($rdGuestWhatsappHref !== ''): ?>
                                        <a class="rdv3-contact-action is-whatsapp" href="<?= $rdSafe($rdGuestWhatsappHref, '') ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i>WhatsApp</a>
                                    <?php else: ?>
                                        <span class="rdv3-contact-action is-whatsapp is-disabled" aria-disabled="true"><i class="fab fa-whatsapp"></i>WhatsApp</span>
                                    <?php endif; ?>
                                </div>

                                <section class="<?= $rdSafe($rdGuestDocPanelClass) ?>" aria-label="Documentos del huesped">
                                    <div class="rdv3-guest-docs-head">
                                        <div class="rdv3-guest-docs-title">
                                            <i class="fas fa-id-card-clip"></i>
                                            <span>Vista documental del huesped</span>
                                        </div>
                                        <?php if ($rdGuestDocEntityId > 0): ?>
                                            <div class="rdv3-guest-docs-actions">
                                                <a class="rdv3-link" href="<?= url('documentos/subir' . $rdGuestDocEntityQuery) ?>"><i class="fas fa-paperclip"></i> Vincular</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (empty($rdGuestDocuments)): ?>
                                        <div class="rdv3-guest-doc-empty">
                                            <i class="fas fa-id-card"></i>
                                            <div>
                                                Sin documento vinculado.
                                                <?php if ($rdGuestDocEntityId > 0): ?>
                                                    <br><a class="rdv3-link" href="<?= url('documentos/subir' . $rdGuestDocEntityQuery) ?>">Agregar INE, licencia o archivo</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <?php
                                            $rdPrimaryDocId = (int)($rdGuestPrimaryDoc['id'] ?? 0);
                                            $rdPrimaryTitle = trim((string)(($rdGuestPrimaryDoc['titulo'] ?? '') ?: ($rdGuestPrimaryDoc['nombre_original'] ?? 'Documento del huesped')));
                                            $rdPrimaryMeta = trim(implode(' · ', array_filter([
                                                (string)($rdGuestPrimaryDoc['tipo_nombre'] ?? ''),
                                                $rdBytes($rdGuestPrimaryDoc['size_bytes'] ?? 0),
                                            ])));
                                            [$rdPrimaryDocLabel, $rdPrimaryDocClass] = $rdDocIcon($rdGuestPrimaryDoc);
                                        ?>
                                        <?php $rdPrimaryKind = $rdGuestDocPreviewKind($rdGuestPrimaryDoc); ?>
                                        <div class="rdv3-guest-docs-grid">
                                            <?php if ($rdPrimaryKind === 'image' && $rdPrimaryDocId > 0): ?>
                                                <?php $rdPrimaryPreviewUrl = url('documentos/' . $rdPrimaryDocId . '/descargar') . '?preview=1'; ?>
                                                <div class="rdv3-guest-doc-feature is-revealable is-revealed" role="group" aria-label="Documento del huesped <?= $rdSafe($rdPrimaryTitle, 'documento') ?>">
                                                    <button type="button" class="rdv3-guest-doc-thumb rdv3-doc-reveal" onclick="rdv3AbrirDocLightbox(this)" data-doc-src="<?= $rdSafe($rdPrimaryPreviewUrl) ?>" data-doc-title="<?= $rdSafe($rdPrimaryTitle, 'Documento del huesped') ?>" aria-label="Ver documento del huesped <?= $rdSafe($rdPrimaryTitle, 'documento') ?> en pantalla completa" aria-haspopup="dialog">
                                                        <span class="rdv3-guest-doc-brand">MEDISOFT</span>
                                                        <span class="rdv3-guest-doc-seal"><i class="fas fa-file-shield"></i></span>
                                                        <img class="rdv3-guest-doc-img" src="<?= $rdSafe($rdPrimaryPreviewUrl) ?>" alt="<?= $rdSafe($rdPrimaryTitle, 'Documento del huesped') ?>" loading="lazy" decoding="async" draggable="false" oncontextmenu="return false;">
                                                        <span class="rdv3-doc-reveal-hint"><i class="fas fa-eye" aria-hidden="true"></i></span>
                                                    </button>
                                                    <span class="rdv3-guest-doc-copy">
                                                        <span class="rdv3-doc-mini-state"><i class="fas fa-id-card"></i> <?= $rdSafe($rdPrimaryDocLabel, 'DOC') ?></span>
                                                        <b><?= $rdSafe($rdPrimaryTitle, 'Documento del huesped') ?></b>
                                                        <small><?= $rdSafe($rdPrimaryMeta, 'Archivo vinculado') ?></small>
                                                        <a class="rdv3-doc-open" href="<?= url('documentos/' . $rdPrimaryDocId) ?>">Abrir ficha <i class="fas fa-arrow-up-right-from-square"></i></a>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <a class="rdv3-guest-doc-feature" href="<?= url('documentos/' . $rdPrimaryDocId) ?>" title="Ver <?= $rdSafe($rdPrimaryTitle, 'documento') ?>">
                                                    <span class="rdv3-guest-doc-thumb" aria-hidden="true">
                                                        <span class="rdv3-guest-doc-brand">MEDISOFT</span>
                                                        <span class="rdv3-guest-doc-seal"><i class="fas fa-file-shield"></i></span>
                                                    </span>
                                                    <span class="rdv3-guest-doc-copy">
                                                        <span class="rdv3-doc-mini-state"><i class="fas fa-lock"></i> <?= $rdSafe($rdPrimaryDocLabel, 'DOC') ?></span>
                                                        <b><?= $rdSafe($rdPrimaryTitle, 'Documento del huesped') ?></b>
                                                        <small><?= $rdSafe($rdPrimaryMeta, 'Archivo vinculado') ?></small>
                                                    </span>
                                                </a>
                                            <?php endif; ?>

                                            <?php $rdGuestSecondaryDocs = array_slice($rdGuestDocuments, 1, 4); ?>
                                            <?php if (!empty($rdGuestSecondaryDocs)): ?>
                                                <div class="rdv3-guest-doc-list">
                                                <?php foreach ($rdGuestSecondaryDocs as $rdGuestDoc): ?>
                                                    <?php
                                                        $rdGuestDocId = (int)($rdGuestDoc['id'] ?? 0);
                                                        $rdGuestDocKind = $rdGuestDocPreviewKind($rdGuestDoc);
                                                        $rdGuestDocTitle = trim((string)(($rdGuestDoc['titulo'] ?? '') ?: ($rdGuestDoc['nombre_original'] ?? 'Documento')));
                                                        $rdGuestDocMeta = trim(implode(' · ', array_filter([
                                                            (string)($rdGuestDoc['tipo_nombre'] ?? ''),
                                                            (string)($rdGuestDoc['relacion'] ?? ''),
                                                            $rdBytes($rdGuestDoc['size_bytes'] ?? 0),
                                                        ])));
                                                        $rdGuestDocIcon = $rdGuestDocKind === 'image' ? 'fa-image' : ($rdGuestDocKind === 'pdf' ? 'fa-file-pdf' : 'fa-file-lines');
                                                    ?>
                                                    <?php if ($rdGuestDocId > 0): ?>
                                                        <a class="rdv3-guest-doc-chip" href="<?= url('documentos/' . $rdGuestDocId) ?>">
                                                            <span class="rdv3-guest-doc-chip-icon"><i class="fas <?= $rdGuestDocIcon ?>"></i></span>
                                                            <span>
                                                                <span class="rdv3-guest-doc-chip-title"><?= $rdSafe($rdGuestDocTitle, 'Documento') ?></span>
                                                                <span class="rdv3-guest-doc-chip-meta"><?= $rdSafe($rdGuestDocMeta, 'Archivo vinculado') ?></span>
                                                            </span>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </section>

                                <div class="rdv3-subhead rdv3-subhead--stack">
                                    <div class="rdv3-subhead-copy">
                                        <span><i class="fas fa-car-side rdv3-car-accent"></i>Vehiculos registrados</span>
                                        <p class="rdv3-subhead-note">Autos enlazados al expediente del huesped para control de estacionamiento.</p>
                                    </div>
                                    <button type="button" class="rdv3-link" onclick="abrirModalAgregarVehiculo()"><i class="fas fa-pen"></i> Agregar</button>
                                </div>
                                <div id="listaVehiculos" class="rdv3-vehicle-list">
                                    <?php if (empty($rdVehicles)): ?>
                                        <div class="rdv3-empty">Sin vehiculos registrados</div>
                                    <?php else: ?>
                                        <?php foreach ($rdVehicles as $vehiculo): ?>
                                            <div class="rdv3-vehicle">
                                                <div class="rdv3-vehicle-main">
                                                    <span class="rdv3-vehicle-icon"><i class="fas fa-car"></i></span>
                                                    <div>
                                                        <div class="rdv3-vehicle-title"><?= $rdSafe(trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? '') . ' ' . ($vehiculo['color'] ?? '')), 'Vehiculo') ?></div>
                                                        <div class="rdv3-vehicle-sub"><?= $rdSafe($vehiculo['ubicacion_estacionamiento'] ?? $vehiculo['observaciones'] ?? 'Registrado') ?></div>
                                                    </div>
                                                </div>
                                                <span class="rdv3-plate"><?= $rdSafe($vehiculo['placas'] ?? $vehiculo['placa'] ?? 'S/P') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div id="vehiculoStatus" class="rdv3-vehicle-status" aria-live="polite"></div>
                            </div>
                        </section>

                        <?php View::partial('documentos_entidad', [
                            'documentosEntidad' => $documentosEntidad ?? [],
                            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
                        ]); ?>
                    </div>

                    <aside class="rdv3-right" aria-label="Panel lateral de reservacion">
                        <section id="rdv3-actions-panel" class="rdv3-card rdv3-side-card rdv3-side-card--actions">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading"><span class="rdv3-icon"><i class="fas fa-grip"></i></span><h2 class="rdv3-card-title">Acciones rapidas</h2></div>
                            </header>
                            <div class="rdv3-card-body rdv3-actions">
                                <?php if ($rdCheckinMode === 'normal' || $rdCheckinMode === 'express'): ?>
                                    <button type="button" class="rdv3-action" onclick="abrirModalCheckIn(<?= $rdReservationId ?>, <?= $rdTotal ?>)"><span class="rdv3-action-left"><span class="rdv3-action-icon is-green"><i class="fas fa-right-to-bracket"></i></span><?= $rdCheckinActionLabel ?></span><i class="fas fa-chevron-right"></i></button>
                                <?php elseif ($rdCheckinMode === 'late'): ?>
                                    <button type="button" class="rdv3-action" onclick='abrirModalCheckInTardio(<?= $rdReservationId ?>, <?= $rdHuespedNombreJsonAttr ?>, <?= $rdHabitacionesTextoJsonAttr ?>, <?= $rdFechaEntradaFormatoJsonAttr ?>, <?= $rdFechaSalidaFormatoJsonAttr ?>, <?= $rdTotal ?>, "<?= $rdCheckinJsMode ?>", <?= (int)$rdCheckinDays ?>)'><span class="rdv3-action-left"><span class="rdv3-action-icon is-green"><i class="fas fa-right-to-bracket"></i></span><?= $rdCheckinActionLabel ?></span><i class="fas fa-chevron-right"></i></button>
                                <?php elseif ($rdEstadoKey === 'checked_in' && !$rdHasCheckoutDebt): ?>
                                    <button type="button" class="rdv3-action rdv3-action--checkout" onclick="abrirModalCheckOut()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-checkout"><i class="fas fa-right-from-bracket"></i></span>Registrar check-out</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <?php if ($rdEstadoKey === 'checked_in'): ?>
                                    <button type="button" class="rdv3-action" onclick="abrirModalCambiarPago()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-gold"><i class="fas fa-right-left"></i></span>Cambiar metodo de pago</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <button type="button" class="rdv3-action" onclick="abrirModalCotizacion()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-blue"><i class="fas fa-clipboard-list"></i></span>Generar cotizacion</span><i class="fas fa-chevron-right"></i></button>
                                <?php if ($rdEstadoKey === 'confirmada'): ?>
                                    <button type="button" class="rdv3-action" onclick="window.location.href='<?= url('reservaciones/editar-habitaciones/' . $reservacion['id']) ?>'"><span class="rdv3-action-left"><span class="rdv3-action-icon is-violet"><i class="fas fa-bed"></i></span>Modificar habitaciones</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <button type="button" class="rdv3-action" onclick="window.location.href='<?= url('reservaciones/editar-estancia/' . $reservacion['id']) ?>'"><span class="rdv3-action-left"><span class="rdv3-action-icon is-violet"><i class="far fa-calendar"></i></span>Modificar estancia</span><i class="fas fa-chevron-right"></i></button>
                                <?php if (!empty($reservacion['metodo_pago'])): ?>
                                    <button type="button" class="rdv3-action ms-print-hide-mobile" onclick="imprimirTicketTermico()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-gray"><i class="fas fa-print"></i></span>Imprimir ticket termico</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <?php if (in_array($rdEstadoKey, ['confirmada', 'checked_in'], true)): ?>
                                    <button type="button" class="rdv3-action rdv3-action--danger" onclick="mostrarFormularioCancelacion()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-danger"><i class="fas fa-ban"></i></span>Cancelar reservacion</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-side-card rdv3-side-card--timeline">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading"><span class="rdv3-icon"><i class="far fa-clock"></i></span><h2 class="rdv3-card-title">Timeline</h2></div>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rdv3-timeline">
                                    <div class="rdv3-step is-blue"><div class="rdv3-step-title">Reservacion creada</div><div class="rdv3-step-meta"><?= $rdSafe($rdDateTime($rdCreatedAt), '-') ?></div></div>
                                    <?php if (!empty($reservacion['metodo_pago'])): ?>
                                        <div class="rdv3-step is-green"><div class="rdv3-step-title">Pago registrado - <?= $rdMoney($rdTotalPaid) ?></div><div class="rdv3-step-meta"><?= $rdSafe($rdMetodoPagoLabel) ?></div></div>
                                    <?php endif; ?>
                                    <div class="rdv3-step is-gold"><div class="rdv3-step-title"><?= $rdSafe($rdEstadoLabel) ?></div><div class="rdv3-step-meta"><?= $rdSafe($rdDateTime($reservacion['updated_at'] ?? $rdCreatedAt), '-') ?></div></div>
                                    <div class="rdv3-step"><div class="rdv3-step-title"><?= $rdEstadoKey === 'checked_in' ? 'Check-in realizado' : 'Check-in pendiente' ?></div><div class="rdv3-step-meta"><?= $rdEstadoKey === 'checked_in' ? $rdSafe($rdDateTime($reservacion['fecha_checkin'] ?? $rdCreatedAt), '-') : 'Programado ' . $rdSafe($rdDate($reservacion['fecha_entrada'] ?? null)) . ' ' . $rdSafe($rdEntryTime, '15:00') ?></div></div>
                                </div>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-side-card rdv3-side-card--payment">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading"><span class="rdv3-icon rdv3-icon--green"><i class="far fa-credit-card"></i></span><h2 class="rdv3-card-title">Informacion de pago</h2></div>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rdv3-payment-total"><small>Total pagado</small><b><?= $rdMoney($rdTotalPaid) ?></b></div>
                                <?php if (empty($rdPayments)): ?>
                                    <div class="rdv3-payment-row">
                                        <div class="rdv3-payment-method"><i class="far fa-credit-card"></i><div><b><?= $rdSafe($rdMetodoPagoLabel) ?></b><small>Metodo registrado</small></div></div>
                                        <span class="rdv3-payment-amount"><?= $rdMoney($rdTotalPaid) ?></span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($rdPayments as $payment): ?>
                                        <?php
                                            $paymentMethod = $payment['metodo_pago'] ?? $payment['metodo'] ?? $rdMetodoPagoLabel;
                                            $paymentMethodKey = strtolower((string)$paymentMethod);
                                            $paymentTipoTarjeta = strtolower((string)($payment['tipo_tarjeta'] ?? $rdTipoTarjetaReservacion ?? ''));
                                            $paymentMethodLabel = ucfirst(str_replace('_', ' ', (string)$paymentMethod));
                                            if ($paymentMethodKey === 'tarjeta' && in_array($paymentTipoTarjeta, ['credito', 'debito'], true)) {
                                                $paymentMethodLabel = 'Tarjeta de ' . ($paymentTipoTarjeta === 'credito' ? 'crédito' : 'débito');
                                            }
                                        ?>
                                        <div class="rdv3-payment-row">
                                            <div class="rdv3-payment-method"><i class="far fa-credit-card"></i><div><b><?= $rdSafe($paymentMethodLabel) ?></b><small><?= $rdSafe($payment['referencia'] ?? $payment['notas'] ?? 'Recibido en caja') ?></small></div></div>
                                            <span class="rdv3-payment-amount"><?= $rdMoney($payment['monto'] ?? $payment['cantidad'] ?? 0) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-side-card rdv3-side-card--notes">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading"><span class="rdv3-icon"><i class="far fa-note-sticky"></i></span><h2 class="rdv3-card-title">Notas rapidas</h2><span class="rdv3-count-badge" aria-label="<?= (int)($total_notas ?? count($rdNotes)) ?> notas"><?= (int)($total_notas ?? count($rdNotes)) ?></span></div>
                            </header>
                            <div class="rdv3-card-body">
                                <div class="rdv3-note-composer">
                                    <label for="nuevaNota">Nueva nota interna</label>
                                    <textarea id="nuevaNota" class="rdv3-note-input" rows="3" maxlength="1000" placeholder="Escribe una observacion breve para el equipo..."></textarea>
                                    <div id="notaStatus" class="rdv3-note-status" aria-live="polite"></div>
                                    <div class="rdv3-note-actions">
                                        <span class="rdv3-note-hint">Ctrl + Enter tambien guarda</span>
                                        <button type="button" id="btnAgregarNota" class="rdv3-note-submit" onclick="agregarNota(this)">
                                            <i class="fas fa-paper-plane"></i>
                                            <span>Agregar nota</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="listaNotas" class="rdv3-note-list">
                                    <?php if (empty($rdNotes)): ?>
                                        <div class="rdv3-empty">No hay notas aun.</div>
                                    <?php else: ?>
                                        <?php foreach ($rdNotes as $nota): ?>
                                            <div class="rdv3-note nota-item">
                                                <small><?= $rdSafe($nota['usuario_nombre'] ?? 'Recepcion') ?> &middot; <?= $rdSafe($rdDateTime($nota['created_at'] ?? null), '-') ?></small>
                                                <?= nl2br($rdSafe($nota['nota'] ?? '')) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>

                <nav class="rdv3-mobile-bottom" aria-label="Acciones principales de reservacion">
                    <?php if ($rdCheckinMode === 'normal' || $rdCheckinMode === 'express'): ?>
                        <button type="button" class="rdv3-mobile-primary" onclick="abrirModalCheckIn(<?= $rdReservationId ?>, <?= $rdTotal ?>)" title="<?= $rdSafe($rdCheckinButtonLabel) ?>" aria-label="<?= $rdSafe($rdCheckinButtonLabel) ?>"><i class="fas fa-right-to-bracket" aria-hidden="true"></i><span class="rdv3-mobile-label"><?= $rdSafe($rdCheckinButtonLabel) ?></span></button>
                    <?php elseif ($rdCheckinMode === 'late'): ?>
                        <button type="button" class="rdv3-mobile-primary" onclick='abrirModalCheckInTardio(<?= $rdReservationId ?>, <?= $rdHuespedNombreJsonAttr ?>, <?= $rdHabitacionesTextoJsonAttr ?>, <?= $rdFechaEntradaFormatoJsonAttr ?>, <?= $rdFechaSalidaFormatoJsonAttr ?>, <?= $rdTotal ?>, "<?= $rdCheckinJsMode ?>", <?= (int)$rdCheckinDays ?>)' title="<?= $rdSafe($rdCheckinButtonLabel) ?>" aria-label="<?= $rdSafe($rdCheckinButtonLabel) ?>"><i class="fas fa-right-to-bracket" aria-hidden="true"></i><span class="rdv3-mobile-label"><?= $rdSafe($rdCheckinButtonLabel) ?></span></button>
                    <?php elseif ($rdEstadoKey === 'checked_in' && !$rdHasCheckoutDebt): ?>
                        <button type="button" class="rdv3-mobile-primary rdv3-mobile-checkout" onclick="abrirModalCheckOut()" title="Check-out" aria-label="Check-out"><i class="fas fa-right-from-bracket" aria-hidden="true"></i><span class="rdv3-mobile-label">Check-out</span></button>
                    <?php else: ?>
                        <a class="rdv3-mobile-primary" href="#rdv3-actions-panel" title="Ver acciones" aria-label="Ver acciones"><i class="fas fa-grip" aria-hidden="true"></i><span class="rdv3-mobile-label">Ver acciones</span></a>
                    <?php endif; ?>
                    <?php if (in_array($rdEstadoKey, ['confirmada', 'checked_in'], true)): ?>
                        <button type="button" class="rdv3-mobile-danger" onclick="mostrarFormularioCancelacion()" title="Cancelar reservacion" aria-label="Cancelar reservacion"><i class="fas fa-ban" aria-hidden="true"></i><span class="rdv3-mobile-label">Cancelar</span></button>
                    <?php endif; ?>
                    <a class="rdv3-mobile-more" href="#rdv3-actions-panel" title="Ver mas acciones" aria-label="Ver mas acciones"><i class="fas fa-ellipsis" aria-hidden="true"></i></a>
                </nav>
            </div>
        </main>
    </div>
</div>

<!-- Modal para Entregar Llave -->
<div id="modalEntregarLlave" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-hand-holding-key" style="color: #3B82F6; margin-right: 0.5rem;"></i>
                Entregar Llave al Huésped
            </h3>
            <button onclick="cerrarModalEntregarLlave()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formEntregarLlave" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="entregar_habitacion_id" name="habitacion_id">
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Habitación</label>
                    <p id="entregar_habitacion_numero" class="text-lg font-bold text-blue-600"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Entregada por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="usuario_actual" checked
                                   onchange="toggleEntregaManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="manual"
                                   onchange="toggleEntregaManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="entregaManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien entrega</label>
                    <input type="text" name="entregada_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalEntregarLlave()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        <i class="fas fa-check mr-2"></i>Entregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Recibir Llave -->
<div id="modalRecibirLlave" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-inbox" style="color: #10B981; margin-right: 0.5rem;"></i>
                Recibir Llave del Huésped
            </h3>
            <button onclick="cerrarModalRecibirLlave()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formRecibirLlave" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="recibir_habitacion_id" name="habitacion_id">
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Habitación</label>
                    <p id="recibir_habitacion_numero" class="text-lg font-bold text-green-600"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Recibida por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="usuario_actual" checked
                                   onchange="toggleRecepcionManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="manual"
                                   onchange="toggleRecepcionManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="recepcionManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien recibe</label>
                    <input type="text" name="recibida_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Notas (opcional)</label>
                    <textarea name="notas" rows="2" class="w-full p-2 border rounded-md"
                              placeholder="Ej: Huésped salió de paseo"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalRecibirLlave()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-check mr-2"></i>Recibir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="modalEntregarRemoto" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 450px;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-tv" style="color: #9333EA; margin-right: 0.5rem;"></i>
                Entregar Control Remoto al Huésped
            </h3>
            <button onclick="cerrarModalEntregarRemoto()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formEntregarRemoto" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="entregar_remoto_habitacion_id" name="habitacion_id">
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Habitación</label>
                    <p id="entregar_remoto_habitacion_numero" class="text-lg font-bold text-purple-600"></p>
                </div>

                <!-- NUEVO: Nombre del propietario de la INE -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user text-purple-600 mr-1"></i>
                        Nombre del propietario de la identificación *
                    </label>
                    <input type="text"
                           name="nombre_propietario_ine"
                           required
                           class="w-full p-2 border rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Ej: Juan Pérez García"
                           maxlength="200">
                    <p class="text-xs text-gray-500 mt-1">Ingrese el nombre completo del propietario</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Identificación *</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="ine" required class="mr-2">
                            <i class="fas fa-id-card mr-2 text-purple-600"></i>
                            <span>INE</span>
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="licencia" required class="mr-2">
                            <i class="fas fa-car mr-2 text-purple-600"></i>
                            <span>Licencia de Conducir</span>
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="otro" required class="mr-2">
                            <i class="fas fa-passport mr-2 text-purple-600"></i>
                            <span>Otro</span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Entregado por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="usuario_actual" checked
                                   onchange="toggleEntregaRemotoManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="manual"
                                   onchange="toggleEntregaRemotoManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="entregaRemotoManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien entrega</label>
                    <input type="text" name="entregada_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalEntregarRemoto()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">
                        <i class="fas fa-check mr-2"></i>Entregar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Recibir Control Remoto INDIVIDUAL (sin cambios) -->
<div id="modalRecibirRemoto" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-inbox" style="color: #10B981; margin-right: 0.5rem;"></i>
                Recibir Control Remoto del Huésped
            </h3>
            <button onclick="cerrarModalRecibirRemoto()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formRecibirRemoto" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" id="recibir_remoto_habitacion_id" name="habitacion_id">
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Habitación</label>
                    <p id="recibir_remoto_habitacion_numero" class="text-lg font-bold text-green-600"></p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Recibido por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="usuario_actual" checked
                                   onchange="toggleRecepcionRemotoManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="manual"
                                   onchange="toggleRecepcionRemotoManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="recepcionRemotoManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien recibe</label>
                    <input type="text" name="recibida_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Notas (opcional)</label>
                    <textarea name="notas" rows="2" class="w-full p-2 border rounded-md"
                              placeholder="Ej: Se devolvió identificación"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalRecibirRemoto()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-check mr-2"></i>Recibir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- NUEVOS MODALES MÚLTIPLES -->
<!-- ============================================================ -->
<!-- Modal para selección de habitaciones -->
<div id="modalCheckOut" class="modal-overlay hidden">
    <div class="modal-content checkout-modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="font-bold text-lg">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Check-out de Habitaciones
            </h3>
            <button onclick="cerrarModalCheckOut()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('reservaciones/check-out-parcial/' . $reservacion['id']) ?>" id="formCheckOut" class="checkout-modal-form">
            <?= csrf_field() ?>

            <div class="modal-body checkout-modal-body">
                <p class="text-sm text-gray-600 mb-4">
                    Selecciona las habitaciones que deseas liberar:
                </p>

                <!-- Lista de habitaciones con checkboxes -->
                <div class="space-y-2 mb-4 checkout-room-scroll" aria-label="Habitaciones para check-out">
                    <?php foreach ($habitaciones as $hab): ?>
                        <label class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg cursor-pointer border-2 border-transparent hover:border-brown-500 transition-all">
                            <input
                                type="checkbox"
                                name="habitaciones[]"
                                value="<?= $hab['habitacion_id'] ?>"
                                class="mr-3 w-5 h-5 text-brown-600 rounded focus:ring-brown-500"
                                onchange="actualizarSeleccion()"
                            >
                            <div class="flex-1">
                                <span class="font-bold text-gray-800">Habitación <?= $hab['numero'] ?></span>
                                <span class="text-xs text-gray-500 ml-2">(<?= $rdSafe($rdRoomTypeLabel($hab)) ?>)</span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Botón para seleccionar/deseleccionar todas -->
                <div class="flex gap-2 mb-4">
                    <button
                        type="button"
                        onclick="seleccionarTodas(true)"
                        class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        Seleccionar todas
                    </button>
                    <button
                        type="button"
                        onclick="seleccionarTodas(false)"
                        class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Deseleccionar todas
                    </button>
                </div>

                <!-- Hora de salida -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-clock mr-1"></i>
                        Hora de salida
                    </label>
                    <input
                        type="time"
                        name="hora_salida"
                        value="<?= date('H:i') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brown-500 focus:border-transparent"
                    >
                </div>

                <?php if (!empty($trabajadoresLimpieza)): ?>
                <!-- Asignacion opcional de responsable de limpieza (colapsado por defecto) -->
                <div class="mb-2">
                    <button type="button" id="toggleAsignarLimpieza" onclick="toggleAsignarLimpieza()"
                            class="flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-user-pen" style="font-size:.8rem;"></i>
                        <span>Asignar responsable de limpieza (opcional)</span>
                        <i class="fas fa-chevron-down" id="iconAsignarLimpieza" style="font-size:.65rem; transition:transform .15s ease;"></i>
                    </button>
                    <div id="asignarLimpiezaBox" class="hidden mt-3 space-y-2">
                        <p class="text-xs text-gray-500">Quién limpiará cada habitación. Puedes dejarlo en blanco.</p>
                        <?php foreach ($habitaciones as $hab): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700 shrink-0" style="width:5.5rem;">Hab. <?= htmlspecialchars($hab['numero']) ?></span>
                                <select name="limpieza_responsable[<?= (int)$hab['habitacion_id'] ?>]"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brown-500 focus:border-transparent">
                                    <option value="">— Sin asignar —</option>
                                    <?php foreach ($trabajadoresLimpieza as $tr): ?>
                                        <option value="<?= (int)$tr['id'] ?>"><?= htmlspecialchars($tr['nombre_completo']) ?><?= !empty($tr['rol_laboral']) ? ' · ' . htmlspecialchars($tr['rol_laboral']) : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mensaje de advertencia -->
                <div id="mensajeSeleccion" class="hidden p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="textoMensaje"></span>
                </div>
            </div>

            <div class="flex gap-3 p-4 bg-gray-50 border-t checkout-modal-actions">
                <button
                    type="button"
                    onclick="cerrarModalCheckOut()"
                    class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg font-medium transition-all">
                    Cancelar
                </button>
                <button
                    type="submit"
                    id="btnConfirmarCheckOut"
                    class="flex-1 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    <i class="fas fa-check mr-2"></i>
                    Confirmar Check-out
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal para Entregar Control Remoto MÚLTIPLE (NUEVO) -->
<div id="modalEntregarRemotosMultiples" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 500px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-tv" style="color: #9333EA; margin-right: 0.5rem;"></i>
                Entregar Controles Remotos (Múltiples)
            </h3>
            <button onclick="cerrarModalEntregarRemotosMultiples()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formEntregarRemotosMultiples" method="POST" action="<?= url('reservaciones/entregar-remotos-multiples') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-door-open text-purple-600 mr-1"></i>
                        Seleccione las habitaciones *
                    </label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border rounded p-2">
                        <label class="flex items-center p-2 hover:bg-purple-50 rounded cursor-pointer">
                            <input type="checkbox" id="selectAllRemotos" onchange="toggleSelectAllRemotos()" class="mr-2">
                            <span class="font-bold text-purple-600">Seleccionar Todas</span>
                        </label>
                        <hr>
                        <?php foreach ($remotos_info as $numero => $remoto): ?>
                            <?php if ($remoto['tiene_remoto']): // Solo mostrar habitaciones donde el hotel tiene el remoto ?>
                                <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                    <input type="checkbox"
                                           name="habitaciones_ids[]"
                                           value="<?= $remoto['habitacion_id'] ?>"
                                           class="remoto-checkbox mr-2">
                                    <i class="fas fa-door-open mr-2 text-purple-500"></i>
                                    <span>Habitación <?= htmlspecialchars($numero) ?></span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Seleccione las habitaciones para entregar con la misma identificación</p>
                </div>

                <!-- Nombre del propietario de la INE -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user text-purple-600 mr-1"></i>
                        Nombre del propietario de la identificación *
                    </label>
                    <input type="text"
                           name="nombre_propietario_ine"
                           required
                           class="w-full p-2 border rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                           placeholder="Ej: Juan Pérez García"
                           maxlength="200">
                </div>

                <!-- Tipo de identificación -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Identificación *</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="ine" required class="mr-2">
                            <i class="fas fa-id-card mr-2 text-purple-600"></i>
                            <span>INE</span>
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="licencia" required class="mr-2">
                            <i class="fas fa-car mr-2 text-purple-600"></i>
                            <span>Licencia de Conducir</span>
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_identificacion" value="otro" required class="mr-2">
                            <i class="fas fa-passport mr-2 text-purple-600"></i>
                            <span>Otro</span>
                        </label>
                    </div>
                </div>

                <!-- Entregado por -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Entregado por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="usuario_actual" checked
                                   onchange="toggleEntregaRemotosMultiplesManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_entrega" value="manual"
                                   onchange="toggleEntregaRemotosMultiplesManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="entregaRemotosMultiplesManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien entrega</label>
                    <input type="text" name="entregada_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalEntregarRemotosMultiples()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-purple-500 text-white rounded-md hover:bg-purple-600">
                        <i class="fas fa-check mr-2"></i>Entregar Seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Recibir Control Remoto MÚLTIPLE (NUEVO) -->
<div id="modalRecibirRemotosMultiples" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 500px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-inbox" style="color: #10B981; margin-right: 0.5rem;"></i>
                Recibir Controles Remotos (Múltiples)
            </h3>
            <button onclick="cerrarModalRecibirRemotosMultiples()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formRecibirRemotosMultiples" method="POST" action="<?= url('reservaciones/recibir-remotos-multiples') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">

                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-door-open text-green-600 mr-1"></i>
                        Seleccione las habitaciones *
                    </label>
                    <div class="space-y-2 max-h-40 overflow-y-auto border rounded p-2">
                        <label class="flex items-center p-2 hover:bg-green-50 rounded cursor-pointer">
                            <input type="checkbox" id="selectAllRemotosRecibir" onchange="toggleSelectAllRemotosRecibir()" class="mr-2">
                            <span class="font-bold text-green-600">Seleccionar Todas</span>
                        </label>
                        <hr>
                        <?php foreach ($remotos_info as $numero => $remoto): ?>
                            <?php if (!$remoto['tiene_remoto']): // Solo mostrar habitaciones donde el huésped tiene el remoto ?>
                                <label class="flex items-center p-2 border rounded hover:bg-green-50 cursor-pointer">
                                    <input type="checkbox"
                                           name="habitaciones_ids[]"
                                           value="<?= $remoto['habitacion_id'] ?>"
                                           class="remoto-recibir-checkbox mr-2">
                                    <i class="fas fa-door-open mr-2 text-green-500"></i>
                                    <span>Habitación <?= htmlspecialchars($numero) ?></span>
                                    <?php if ($remoto['nombre_propietario_ine']): ?>
                                        <span class="ml-2 text-xs text-gray-600">(<?= htmlspecialchars($remoto['nombre_propietario_ine']) ?>)</span>
                                    <?php endif; ?>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recibido por -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Recibido por:</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="usuario_actual" checked
                                   onchange="toggleRecepcionRemotosMultiplesManual(false)" class="mr-2">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuario actual') ?></span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_recepcion" value="manual"
                                   onchange="toggleRecepcionRemotosMultiplesManual(true)" class="mr-2">
                            <span>Otra persona</span>
                        </label>
                    </div>
                </div>

                <div id="recepcionRemotosMultiplesManualDiv" class="mb-4" style="display: none;">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre de quien recibe</label>
                    <input type="text" name="recibida_por_manual" class="w-full p-2 border rounded-md"
                           placeholder="Nombre completo">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Notas (opcional)</label>
                    <textarea name="notas" rows="2" class="w-full p-2 border rounded-md"
                              placeholder="Ej: Se devolvieron todas las identificaciones"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalRecibirRemotosMultiples()"
                            class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600">
                        <i class="fas fa-check mr-2"></i>Recibir Seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- ── Lightbox del documento del huésped (ver imagen sin salir de la vista) ── -->
<div id="rdv3DocLightbox" class="rdv3-doc-lightbox" hidden role="dialog" aria-modal="true" aria-label="Documento del huesped en pantalla completa">
    <figure class="rdv3-doc-lightbox-frame">
        <img id="rdv3DocLightboxImg" src="" alt="" draggable="false" oncontextmenu="return false;">
        <figcaption id="rdv3DocLightboxCaption"></figcaption>
    </figure>
    <button type="button" class="rdv3-doc-lightbox-close" aria-label="Cerrar vista de documento" onclick="rdv3CerrarDocLightbox()">
        <i class="fas fa-times" aria-hidden="true"></i>
    </button>
</div>
<style>
.rdv3-doc-lightbox {
    position: fixed; inset: 0; z-index: 10050;
    display: flex; align-items: center; justify-content: center;
    padding: 18px;
    background: rgba(10, 16, 26, .88);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    animation: rdv3DocLbIn .22s ease;
}
.rdv3-doc-lightbox[hidden] { display: none; }
@keyframes rdv3DocLbIn { from { opacity: 0; } to { opacity: 1; } }
.rdv3-doc-lightbox-frame { margin: 0; display: flex; flex-direction: column; align-items: center; gap: 10px; max-width: 100%; }
.rdv3-doc-lightbox-frame img {
    max-width: min(94vw, 1100px);
    max-height: 82vh;
    border-radius: 14px;
    box-shadow: 0 24px 70px rgba(0, 0, 0, .55);
    animation: rdv3DocLbImgIn .26s cubic-bezier(.22, 1, .36, 1);
    user-select: none;
}
@keyframes rdv3DocLbImgIn { from { opacity: 0; transform: scale(.96); } to { opacity: 1; transform: none; } }
.rdv3-doc-lightbox-frame figcaption { color: rgba(255, 255, 255, .82); font-size: .82rem; font-weight: 700; text-align: center; max-width: 92vw; overflow-wrap: anywhere; }
.rdv3-doc-lightbox-close {
    position: absolute;
    top: calc(env(safe-area-inset-top, 0px) + 14px);
    right: 14px;
    width: 42px; height: 42px;
    display: grid; place-items: center;
    border: 1px solid rgba(255, 255, 255, .28);
    border-radius: 999px;
    background: rgba(255, 255, 255, .12);
    color: #fff; font-size: 1rem; cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: background .16s ease, transform .16s ease;
}
.rdv3-doc-lightbox-close:hover, .rdv3-doc-lightbox-close:focus-visible { background: rgba(255, 255, 255, .24); }
.rdv3-doc-lightbox-close:active { transform: scale(.92); }
@media (prefers-reduced-motion: reduce) {
    .rdv3-doc-lightbox, .rdv3-doc-lightbox-frame img { animation: none; }
}
</style>
<script>
var rdv3DocLbScrollY = 0;
function rdv3AbrirDocLightbox(btn) {
    var lb = document.getElementById('rdv3DocLightbox');
    if (!lb) return;
    var img = document.getElementById('rdv3DocLightboxImg');
    var cap = document.getElementById('rdv3DocLightboxCaption');
    img.src = btn.getAttribute('data-doc-src') || '';
    img.alt = btn.getAttribute('data-doc-title') || 'Documento del huesped';
    cap.textContent = btn.getAttribute('data-doc-title') || '';
    rdv3DocLbScrollY = window.scrollY || document.documentElement.scrollTop || 0;
    lb.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    lb.querySelector('.rdv3-doc-lightbox-close').focus({ preventScroll: true });
}
function rdv3CerrarDocLightbox() {
    var lb = document.getElementById('rdv3DocLightbox');
    if (!lb || lb.hidden) return;
    lb.hidden = true;
    document.getElementById('rdv3DocLightboxImg').src = '';
    document.documentElement.style.overflow = '';
    window.scrollTo(0, rdv3DocLbScrollY);
}
document.getElementById('rdv3DocLightbox').addEventListener('click', function (ev) {
    if (ev.target === this) rdv3CerrarDocLightbox();
});
document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') rdv3CerrarDocLightbox();
});
</script>
<script>
function mostrarAvisoReservacion(mensaje, tipo = 'info', duracion = 5200) {
    let aviso = document.getElementById('rdv3InlineToast');
    if (!aviso) {
        aviso = document.createElement('div');
        aviso.id = 'rdv3InlineToast';
        aviso.setAttribute('role', 'status');
        aviso.setAttribute('aria-live', 'polite');
        document.body.appendChild(aviso);
    }

    const tipos = ['info', 'warning', 'error', 'success'];
    const tipoSeguro = tipos.includes(tipo) ? tipo : 'info';
    aviso.className = 'rdv3-inline-toast is-visible is-' + tipoSeguro;
    aviso.textContent = mensaje;

    window.clearTimeout(aviso._hideTimer);
    aviso._hideTimer = window.setTimeout(() => {
        aviso.classList.remove('is-visible');
    }, duracion);
}

// Funciones para control de llaves
function abrirModalEntregarLlave(habitacionId, numeroHabitacion) {
    document.getElementById('entregar_habitacion_id').value = habitacionId;
    document.getElementById('entregar_habitacion_numero').textContent = 'Habitación ' + numeroHabitacion;
    document.getElementById('modalEntregarLlave').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalEntregarLlave() {
    document.getElementById('modalEntregarLlave').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('formEntregarLlave').reset();
}

function abrirModalRecibirLlave(habitacionId, numeroHabitacion) {
    document.getElementById('recibir_habitacion_id').value = habitacionId;
    document.getElementById('recibir_habitacion_numero').textContent = 'Habitación ' + numeroHabitacion;
    document.getElementById('modalRecibirLlave').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalRecibirLlave() {
    document.getElementById('modalRecibirLlave').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('formRecibirLlave').reset();
}

function toggleEntregaManual(mostrar) {
    document.getElementById('entregaManualDiv').style.display = mostrar ? 'block' : 'none';
}

function toggleRecepcionManual(mostrar) {
    document.getElementById('recepcionManualDiv').style.display = mostrar ? 'block' : 'none';
}

// Función para entrega rápida desde el índice de habitaciones
function entregarLlaveRapida(habitacionId, reservacionId) {
    const form = document.getElementById('formEntregarLlave');
    const habitacionInput = document.getElementById('entregar_habitacion_id');
    const habitacionLabel = document.getElementById('entregar_habitacion_numero');
    const reservacionInput = form ? form.querySelector('input[name="reservacion_id"]') : null;

    if (!form || !habitacionInput || !habitacionLabel) {
        console.error('Modal de entrega de llave no disponible');
        return;
    }

    habitacionInput.value = habitacionId;
    if (reservacionInput && reservacionId) {
        reservacionInput.value = reservacionId;
    }
    habitacionLabel.textContent = 'Habitacion seleccionada';
    document.getElementById('modalEntregarLlave').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Manejadores de formularios
const formEntregarLlave = document.getElementById('formEntregarLlave');
if (formEntregarLlave) {
    formEntregarLlave.addEventListener('submit', function(e) {
        e.preventDefault();
        this.action = '<?= url("reservaciones/entregar-llave") ?>';
        this.submit();
    });
}

const formRecibirLlave = document.getElementById('formRecibirLlave');
if (formRecibirLlave) {
    formRecibirLlave.addEventListener('submit', function(e) {
        e.preventDefault();
        this.action = '<?= url("reservaciones/recibir-llave") ?>';
        this.submit();
    });
}
</script>

<!-- Modal de Check-in con Pagos Mixtos -->
<?php
$rvCheckinShortDate = function($value) use ($rdSafe) {
    $text = trim((string)($value ?? ''));
    if ($text === '') {
        return '';
    }
    $ts = strtotime($text);
    if (!$ts) {
        return $rdSafe($text, '');
    }
    $months = [
        1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
        7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic',
    ];
    return date('d', $ts) . ' ' . $months[(int)date('n', $ts)];
};
$rvCheckinStayLabel = $rdNoches . ' ' . ($rdNoches === 1 ? 'noche' : 'noches');
$rvCheckinEntradaCorta = $rvCheckinShortDate($rdFechaEntrada);
$rvCheckinSalidaCorta = $rvCheckinShortDate($rdFechaSalida);
if ($rvCheckinEntradaCorta !== '' || $rvCheckinSalidaCorta !== '') {
    $rvCheckinStayLabel .= ' - ' . trim($rvCheckinEntradaCorta . ' a ' . $rvCheckinSalidaCorta, ' a');
}
?>
<div id="modalCheckIn" class="modal-overlay rv-checkin-modal" style="display: none;" data-checkin-step="1">
    <div class="modal-content rv-checkin-shell" role="dialog" aria-modal="true" aria-labelledby="rvCheckinTitle">
        <form id="formCheckInModal" method="POST" action="" class="rv-checkin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="permitir_saldo_pendiente" id="permitir_saldo_pendiente" value="0">
            <input type="hidden" name="checkin_return_to" id="checkin_return_to" value="<?= htmlspecialchars((string)($_GET['checkin_return_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <div class="rv-checkin-topbar">
                <div class="rv-checkin-titleblock">
                    <span class="rv-checkin-icon" aria-hidden="true"><i class="fas fa-right-to-bracket"></i></span>
                    <div>
                        <h3 id="rvCheckinTitle">Registrar check-in</h3>
                        <p><?= $rdSafe($rdHuespedNombre) ?> - Reservacion #<?= (int)$rdReservationId ?> - Hab. <?= $rdSafe($rdHabitacionesTexto ?: 'Sin habitaciones') ?></p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalCheckIn()" class="rv-checkin-close" aria-label="Cerrar modal de check-in">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="rv-checkin-stepper" aria-label="Progreso de check-in">
                <div class="rv-checkin-step is-current" data-step-indicator="1"><span>1</span><strong>Llegada</strong></div>
                <div class="rv-checkin-line" data-step-line="1"></div>
                <div class="rv-checkin-step" data-step-indicator="2"><span>2</span><strong>Pago</strong></div>
                <div class="rv-checkin-line" data-step-line="2"></div>
                <div class="rv-checkin-step" data-step-indicator="3"><span>3</span><strong>Factura</strong></div>
            </div>

            <div class="rv-checkin-content">
                <section class="rv-checkin-stage rv-stage-arrival is-active" data-checkin-stage="1" aria-labelledby="rvLlegadaTitle">
                    <div class="rv-total-card">
                        <div>
                            <span class="rv-kicker">Total a cobrar</span>
                            <strong id="totalACobrar">$0.00</strong>
                        </div>
                        <span class="rv-date-pill"><i class="far fa-calendar"></i><?= $rdSafe($rvCheckinStayLabel) ?></span>
                    </div>
                    <div id="checkinAnticipoAviso" style="display:none;margin-top:8px;background:#F0FBF5;border:1px solid #BBF7D0;border-radius:10px;padding:9px 12px;color:#15803D;font-size:.82rem;font-weight:600;"></div>

                    <div class="form-group rv-arrival-field">
                        <label class="form-label" for="horaEntradaCheckIn" id="rvLlegadaTitle">Hora de llegada</label>
                        <div class="rv-time-input">
                            <i class="far fa-clock" aria-hidden="true"></i>
                            <input id="horaEntradaCheckIn" type="time" name="hora_entrada" class="form-input" value="<?= date('H:i') ?>" required>
                        </div>
                    </div>

                    <div class="rv-room-grid" aria-label="Habitaciones para check-in">
                        <?php if (empty($rdRooms)): ?>
                            <div class="rv-room-card rv-room-empty">
                                <strong>Sin habitaciones</strong>
                                <span>Revisa la reservacion antes de continuar.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rdRooms as $room): ?>
                                <?php
                                $roomIsCourtesy = !empty($room['es_cortesia']) || !empty($room['cortesia']);
                                $roomType = $rdRoomTypeLabel($room);
                                ?>
                                <article class="rv-room-card <?= $roomIsCourtesy ? 'is-courtesy' : '' ?>">
                                    <strong><?= $rdSafe($room['numero'] ?? 'S/N') ?></strong>
                                    <span><?= $rdSafe($roomType) ?></span>
                                    <em><i class="fas fa-check"></i><?= $roomIsCourtesy ? 'Cortesia' : 'Lista' ?></em>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="form-group rv-payment-section rv-checkin-stage" data-checkin-stage="2" aria-labelledby="rvPagoTitle" aria-hidden="true">
                    <h4 id="rvPagoTitle"><i class="fas fa-wallet"></i> Metodos de pago</h4>
                    <p class="rv-payment-hint">Registra lo que el huesped paga hoy. Si falta una parte, activa la opcion para dejarla como cuenta pendiente.</p>

                    <div class="rv-payment-shortcuts" aria-label="Atajos de pago">
                        <button type="button" class="rv-money-shortcut is-cash" onclick="aplicarPagoRapido('efectivo')">
                            <i class="fas fa-money-bill-wave"></i>
                            Efectivo exacto
                        </button>
                        <button type="button" class="rv-money-shortcut is-card" onclick="aplicarPagoRapido('tarjeta')">
                            <i class="fas fa-credit-card"></i>
                            Tarjeta exacta
                        </button>
                        <button type="button" class="rv-money-shortcut is-transfer" onclick="aplicarPagoRapido('transferencia')">
                            <i class="fas fa-university"></i>
                            Transferencia exacta
                        </button>
                        <button type="button" class="rv-money-shortcut is-split" onclick="dividirPagoRapido()">
                            <i class="fas fa-exchange-alt"></i>
                            Mitad y mitad
                        </button>
                        <button type="button" class="rv-money-shortcut is-cash-transfer" onclick="dividirPagoEfectivoTransferencia()">
                            <i class="fas fa-university"></i>
                            Efectivo + transferencia
                        </button>
                    </div>

                    <label class="rv-pending-option" for="check_saldo_pendiente">
                        <input type="checkbox" id="check_saldo_pendiente" onchange="toggleSaldoPendienteCheckIn()">
                        <span class="rv-pending-option__icon"><i class="fas fa-clock"></i></span>
                        <span>
                            <strong>Dejar saldo pendiente</strong>
                            <small>Permite continuar el check-in con pago parcial. Lo que falte aparecera en Cuentas por cobrar.</small>
                        </span>
                    </label>

                    <div id="checkinPendingPreview" class="rv-pending-preview" hidden aria-live="polite">
                        <div>
                            <span>Pago de hoy</span>
                            <strong id="checkinPagoHoy">$0.00</strong>
                        </div>
                        <div>
                            <span>Quedara pendiente</span>
                            <strong id="checkinQuedaPendiente">$0.00</strong>
                        </div>
                    </div>

                    <div id="metodosPagoContainer" class="rv-pay-methods">
                        <div class="metodo-pago-item rv-pay-option rv-pay-cash">
                            <label>
                                <input type="checkbox" id="check_efectivo" onchange="toggleMetodoPago('efectivo')">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Efectivo</span>
                            </label>
                            <div id="panel_efectivo" class="hidden rv-pay-panel">
                                <div class="rv-input-grid">
                                    <div>
                                        <label id="label_monto_efectivo">Monto a cobrar en efectivo</label>
                                        <input type="number" name="monto_efectivo" id="monto_efectivo" data-money-format="true" step="0.01" min="0" readonly oninput="calcularTotales()" onchange="calcularTotales()">
                                    </div>
                                    <div>
                                        <label id="label_recibido_efectivo">Dinero recibido</label>
                                        <input type="number" name="recibido_efectivo" id="recibido_efectivo" data-money-format="true" step="0.01" min="0" oninput="calcularCambio()" onchange="calcularCambio()" onkeyup="calcularCambio()" placeholder="0.00">
                                        <button type="button" class="rv-money-mini" onclick="marcarEfectivoExacto()">Recibi exacto</button>
                                    </div>
                                </div>
                                <div class="rv-change-pill">Cambio <strong id="cambio_efectivo">$0.00</strong></div>
                            </div>
                        </div>

                        <div class="metodo-pago-item rv-pay-option rv-pay-card">
                            <label>
                                <input type="checkbox" id="check_tarjeta" onchange="toggleMetodoPago('tarjeta')">
                                <i class="fas fa-credit-card"></i>
                                <span>Tarjeta</span>
                            </label>
                            <div id="panel_tarjeta" class="hidden rv-pay-panel">
                                <div class="rv-card-type">
                                    <label class="rv-radio-chip" id="label_credito" onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_debito').style.borderColor='#BFDBFE'; document.getElementById('label_debito').style.background='white';">
                                        <input type="radio" name="tipo_tarjeta" value="credito">
                                        <i class="fas fa-credit-card"></i> Credito
                                    </label>
                                    <label class="rv-radio-chip" id="label_debito" onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_credito').style.borderColor='#BFDBFE'; document.getElementById('label_credito').style.background='white';">
                                        <input type="radio" name="tipo_tarjeta" value="debito">
                                        <i class="fas fa-money-check-alt"></i> Debito
                                    </label>
                                </div>
                                <div class="rv-input-grid">
                                    <div>
                                        <label id="label_monto_tarjeta">Monto con tarjeta</label>
                                        <input type="number" name="monto_tarjeta" id="monto_tarjeta" data-money-format="true" step="0.01" min="0" oninput="calcularTotales()" onchange="calcularTotales()">
                                    </div>
                                    <div>
                                        <label>Referencia</label>
                                        <input type="text" name="referencia_tarjeta" placeholder="Ultimos 4 digitos">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="metodo-pago-item rv-pay-option rv-pay-transfer">
                            <label>
                                <input type="checkbox" id="check_transferencia" onchange="toggleMetodoPago('transferencia')">
                                <i class="fas fa-university"></i>
                                <span>Transferencia</span>
                            </label>
                            <div id="panel_transferencia" class="hidden rv-pay-panel">
                                <div class="rv-input-grid">
                                    <div>
                                        <label id="label_monto_transferencia">Monto por transferencia</label>
                                        <input type="number" name="monto_transferencia" id="monto_transferencia" data-money-format="true" step="0.01" min="0" oninput="calcularTotales()" onchange="calcularTotales()">
                                    </div>
                                    <div>
                                        <label>Referencia</label>
                                        <input type="text" name="referencia_transferencia" placeholder="Numero de operacion">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rv-invoice-section rv-checkin-stage" data-checkin-stage="3" aria-labelledby="rvFacturaTitle" aria-hidden="true">
                    <div class="rv-final-grid">
                        <div class="rv-final-invoice">
                            <h4 id="rvFacturaTitle"><i class="far fa-file-alt"></i> ¿El cliente requiere factura? <span>*</span></h4>

                            <?php if (!empty($anticipoFacturaSolicitud)): ?>
                                <div style="background:#EFF6FF;border:1px solid #BFDBFE;border-radius:10px;padding:10px 13px;margin-bottom:12px;color:#1E40AF;font-size:.83rem;font-weight:600;">
                                    <i class="fas fa-circle-info" style="margin-right:5px;"></i>
                                    Ya existe una solicitud de factura por un anticipo de
                                    <strong>$<?= number_format((float)($anticipoFacturaSolicitud['monto_total'] ?? 0), 2) ?></strong>
                                    (estatus: <?= htmlspecialchars($anticipoFacturaSolicitud['estatus'] ?? '') ?>).
                                    Por defecto el cobro del check-in se registra como <strong>factura separada</strong>; si prefieres sumarlo a la pendiente, usa las opciones de abajo.
                                </div>
                            <?php endif; ?>

                            <div id="facturaContainer" class="rv-invoice-grid">
                                <label class="rv-invoice-choice" id="label_factura_si" onmouseover="this.style.borderColor='#3B82F6'" onmouseout="if(!document.getElementById('factura_si').checked) this.style.borderColor='#E5E7EB'">
                                    <input type="radio" name="requiere_factura" id="factura_si" value="si" onchange="seleccionarFactura('si')">
                                    <div>
                                        <strong>Factura para cliente</strong>
                                        <p>Se registra solicitud de factura para el huesped.</p>
                                    </div>
                                </label>

                                <label class="rv-invoice-choice" id="label_factura_no" onmouseover="this.style.borderColor='#6B7280'" onmouseout="if(!document.getElementById('factura_no').checked) this.style.borderColor='#E5E7EB'">
                                    <input type="radio" name="requiere_factura" id="factura_no" value="no" onchange="seleccionarFactura('no')">
                                    <div>
                                        <strong>Sin factura del cliente</strong>
                                        <p>Si hay tarjeta o transferencia quedara como uso interno.</p>
                                    </div>
                                </label>
                            </div>

                            <?php if (!empty($anticipoFacturaSolicitud)): ?>
                                <div style="margin-top:12px;padding:11px 13px;border:1px solid #BFDBFE;border-radius:12px;background:#EFF6FF;">
                                    <div style="font-size:.74rem;color:#1E40AF;font-weight:700;margin-bottom:8px;">Factura pendiente #<?= (int)($anticipoFacturaSolicitud['id'] ?? 0) ?> por $<?= number_format((float)($anticipoFacturaSolicitud['monto_total'] ?? 0), 2) ?></div>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                        <label style="display:flex;align-items:center;gap:6px;font-size:.84rem;font-weight:700;color:#1E3A8A;cursor:pointer;">
                                            <input type="radio" name="factura_modo" value="acumular"> Sumar a factura pendiente
                                        </label>
                                        <label style="display:flex;align-items:center;gap:6px;font-size:.84rem;font-weight:700;color:#1E3A8A;cursor:pointer;">
                                            <input type="radio" name="factura_modo" value="separada" checked> Crear factura separada
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div id="facturaResultado" class="rv-invoice-result">
                                <p><i class="fas fa-circle-info"></i> Selecciona una opcion para ver como quedara registrada la facturacion.</p>
                            </div>

                            <div id="facturaValidacion" class="rv-checkin-message hidden">
                                <p><i class="fas fa-exclamation-circle"></i> Debe indicar si el cliente requiere factura</p>
                            </div>

                            <div id="facturaInfoInterna" class="rv-checkin-note hidden">
                                <p><i class="fas fa-info-circle"></i> El pago con tarjeta/transferencia se registrara en facturacion para uso interno</p>
                            </div>
                        </div>

                        <section class="rv-checkin-summary" aria-label="Resumen de pago">
                            <h5>Resumen final</h5>
                            <div class="rv-summary-row"><span>Total a cobrar</span><strong id="resumenTotal">$0.00</strong></div>
                            <div class="rv-summary-row is-paid"><span>Pagado</span><strong id="resumenPagado">$0.00</strong></div>
                            <div class="rv-summary-row"><span>Metodo elegido</span><strong id="resumenMetodoPago">Sin seleccionar</strong></div>
                            <div id="divRestante" class="rv-summary-row is-due" style="display: none;"><span>Cuenta pendiente</span><strong id="resumenRestante">$0.00</strong></div>
                            <div id="divCambio" class="rv-summary-row is-change" style="display: none;"><span>Cambio</span><strong id="resumenCambio">$0.00</strong></div>
                        </section>
                    </div>
                </section>

                <div id="mensajeValidacion" class="rv-checkin-message hidden" aria-live="polite"></div>
            </div>

            <div class="rv-checkin-actions">
                <button type="button" id="btnCheckInBack" onclick="retrocederOCerrarCheckInWizard()" class="rv-btn-cancel">
                    <i class="fas fa-arrow-left"></i>
                    <span>Cancelar</span>
                </button>
                <div class="rv-step-dots" aria-hidden="true">
                    <span class="is-active" data-step-dot="1"></span>
                    <span data-step-dot="2"></span>
                    <span data-step-dot="3"></span>
                </div>
                <button type="button" id="btnConfirmarCheckIn" onclick="avanzarCheckInWizard()" class="rv-btn-confirm">
                    <span>Continuar</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para cancelacion -->
<div id="modalCancelacion" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 360px;">
        <form action="/reservaciones/cancelar/<?= htmlspecialchars($reservacion['id'] ?? '') ?>" method="POST">
            <?= csrf_field() ?>


        <!-- Encabezado -->
        <div style="padding: 1rem; border-bottom: 2px solid #FEE2E2; background: linear-gradient(135deg, #FEF2F2 0%, #FEE2E2 100%); border-radius: 1rem 1rem 0 0;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #991B1B;">
                <i class="fas fa-times-circle" style="color: #EF4444; margin-right: 0.5rem;"></i>
                Cancelar Reservación
            </h3>
        </div>

        <!-- Cuerpo -->
        <div style="padding: 1rem;">
            <!-- Advertencia -->
            <div style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 1px solid #FCD34D; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem;">
                <p style="font-size: 0.875rem; color: #92400E; margin: 0; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle" style="margin-right: 0.375rem;"></i>
                    ¿Está seguro de cancelar esta reservación?
                </p>
            </div>

            <!-- Razón de cancelación -->
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 600; margin-bottom: 0.375rem; color: #374151;">
                    Razón de cancelación: <span style="color: #EF4444;">*</span>
                </label>
                <textarea name="razon_cancelacion" rows="3" required
                          style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem; resize: vertical; min-height: 80px;"
                          placeholder="Explique brevemente el motivo..."></textarea>
            </div>
        </div>

        <!-- Pie con botones -->
        <div style="padding: 1rem; background-color: #F9FAFB; border-top: 2px solid #E5E7EB; display: flex; justify-content: flex-end; gap: 0.5rem; border-radius: 0 0 1rem 1rem;">
            <button type="button" onclick="cerrarModalCancelacion()"
                    style="padding: 0.5rem 1rem; border: 2px solid #E5E7EB; background-color: white; color: #374151; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                Cerrar
            </button>
            <button type="submit"
                    style="padding: 0.5rem 1rem; background: linear-gradient(to right, #EF4444, #DC2626); color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                <i class="fas fa-times-circle" style="margin-right: 0.375rem;"></i>
                Confirmar Cancelación
            </button>
        </div>
    </form>
</div>


</div>

<!-- ========== MODAL CAMBIAR MÉTODO DE PAGO ========== -->
<div id="modalCambiarPago" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 400px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="padding: 1rem; border-bottom: 2px solid #F3F4F6; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: 1rem 1rem 0 0;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #92400E;">
                <i class="fas fa-exchange-alt" style="color: #F59E0B; margin-right: 0.5rem;"></i>
                Cambiar Método de Pago
            </h3>
            <button onclick="cerrarModalCambiarPago()" style="position: absolute; top: 0.75rem; right: 0.75rem; background: none; border: none; cursor: pointer; color: #92400E; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="padding: 1rem; overflow-y: auto; flex: 1;">
            <!-- Info de reservación -->
            <div style="background: #F9FAFB; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #E5E7EB;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.75rem; color: #6B7280;">Reservación #<?= htmlspecialchars($reservacion['id']) ?></span>
                    <span style="font-weight: 600; font-size: 1.125rem; color: #92400E;"><?= format_money($reservacion['precio_total'] ?? 0) ?></span>
                </div>
                <p style="font-size: 0.7rem; color: #9CA3AF; margin-top: 0.25rem;">
                    <?= htmlspecialchars($huesped['nombre_completo'] ?? '') ?>
                </p>
            </div>

            <!-- Métodos de pago actuales -->
            <div style="margin-bottom: 1rem;">
                <p style="font-size: 0.75rem; font-weight: 600; color: #6B7280; margin-bottom: 0.375rem;">
                    <i class="fas fa-history" style="margin-right: 0.25rem;"></i> Método actual:
                    <span style="color: #059669; font-weight: 700;">
                        <?php if (!empty($pagos) && count($pagos) > 1): ?>
                            Mixto
                        <?php else: ?>
                            <?= ucfirst(htmlspecialchars($reservacion['metodo_pago'] ?? 'No definido')) ?>
                        <?php endif; ?>
                    </span>
                </p>
            </div>

            <!-- Nuevo método de pago -->
            <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                <i class="fas fa-wallet" style="color: #9333EA; margin-right: 0.375rem;"></i>
                Nuevo Método de Pago
            </h4>

            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <!-- Efectivo -->
                <div style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 0.5rem; padding: 0.75rem;">
                    <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #065F46; cursor: pointer;">
                        <input type="checkbox" id="check_efectivo_cp" onchange="toggleMetodoPagoCP('efectivo')" style="margin-right: 0.5rem;">
                        <i class="fas fa-money-bill-wave" style="color: #10B981; margin-right: 0.5rem;"></i>
                        Efectivo
                    </label>
                    <div id="panel_efectivo_cp" class="hidden" style="margin-top: 0.5rem;">
                        <div>
                            <label style="font-size: 0.75rem; color: #065F46;">Monto</label>
                            <input type="number" id="monto_efectivo_cp" data-money-format="true" step="0.01" min="0"
                                   onchange="calcularTotalesCP()" onkeyup="calcularTotalesCP()"
                                   style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background: white;">
                        </div>
                    </div>
                </div>

                <!-- Tarjeta -->
                <div style="background: #EFF6FF; border: 2px solid #BFDBFE; border-radius: 0.5rem; padding: 0.75rem;">
                    <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #1E40AF; cursor: pointer;">
                        <input type="checkbox" id="check_tarjeta_cp" onchange="toggleMetodoPagoCP('tarjeta')" style="margin-right: 0.5rem;">
                        <i class="fas fa-credit-card" style="color: #3B82F6; margin-right: 0.5rem;"></i>
                        Tarjeta
                    </label>
                    <div id="panel_tarjeta_cp" class="hidden" style="margin-top: 0.5rem;">
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <label style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem; background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF;"
                                   id="label_credito_cp">
                                <input type="radio" name="tipo_tarjeta_cp" value="credito" style="accent-color: #3B82F6;">
                                Crédito
                            </label>
                            <label style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem; background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF;"
                                   id="label_debito_cp">
                                <input type="radio" name="tipo_tarjeta_cp" value="debito" style="accent-color: #3B82F6;">
                                Débito
                            </label>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <label style="font-size: 0.75rem; color: #1E40AF;">Monto</label>
                                <input type="number" id="monto_tarjeta_cp" data-money-format="true" step="0.01" min="0"
                                       onchange="calcularTotalesCP()" onkeyup="calcularTotalesCP()"
                                       style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: #1E40AF;">Referencia</label>
                                <input type="text" id="referencia_tarjeta_cp" placeholder="Últimos 4 dígitos"
                                       style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transferencia -->
                <div style="background: #F5F3FF; border: 2px solid #DDD6FE; border-radius: 0.5rem; padding: 0.75rem;">
                    <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #5B21B6; cursor: pointer;">
                        <input type="checkbox" id="check_transferencia_cp" onchange="toggleMetodoPagoCP('transferencia')" style="margin-right: 0.5rem;">
                        <i class="fas fa-exchange-alt" style="color: #9333EA; margin-right: 0.5rem;"></i>
                        Transferencia
                    </label>
                    <div id="panel_transferencia_cp" class="hidden" style="margin-top: 0.5rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <label style="font-size: 0.75rem; color: #5B21B6;">Monto</label>
                                <input type="number" id="monto_transferencia_cp" data-money-format="true" step="0.01" min="0"
                                       onchange="calcularTotalesCP()" onkeyup="calcularTotalesCP()"
                                       style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                            </div>
                            <div>
                                <label style="font-size: 0.75rem; color: #5B21B6;">Referencia</label>
                                <input type="text" id="referencia_transferencia_cp" placeholder="Número de operación"
                                       style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECCIÓN DE FACTURA CP ========== -->
            <div style="margin-top: 1rem; margin-bottom: 0.5rem;">
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                    <i class="fas fa-file-invoice" style="color: #2563EB; margin-right: 0.375rem;"></i>
                    ¿El cliente requiere factura?
                    <span style="color: #EF4444; font-size: 0.75rem;">*</span>
                </h4>

                <div id="facturaContainerCP" style="display: flex; gap: 0.5rem;">
                    <!-- Opción SÍ -->
                    <label style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                                   background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                                   cursor: pointer; transition: all 0.2s;"
                           id="label_factura_si_cp"
                           onmouseover="this.style.borderColor='#3B82F6'"
                           onmouseout="if(!document.getElementById('factura_si_cp').checked) this.style.borderColor='#E5E7EB'">
                        <input type="radio" name="requiere_factura_cp" id="factura_si_cp" value="si"
                               onchange="seleccionarFacturaCP('si')"
                               style="accent-color: #2563EB;">
                        <div>
                            <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">Sí</span>
                            <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Se registrará para facturación</p>
                        </div>
                    </label>

                    <!-- Opción NO -->
                    <label style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                                   background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                                   cursor: pointer; transition: all 0.2s;"
                           id="label_factura_no_cp"
                           onmouseover="this.style.borderColor='#6B7280'"
                           onmouseout="if(!document.getElementById('factura_no_cp').checked) this.style.borderColor='#E5E7EB'">
                        <input type="radio" name="requiere_factura_cp" id="factura_no_cp" value="no"
                               onchange="seleccionarFacturaCP('no')"
                               style="accent-color: #6B7280;">
                        <div>
                            <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">No</span>
                            <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Sin factura</p>
                        </div>
                    </label>
                </div>

                <!-- Mensaje de validación: campo obligatorio -->
                <div id="facturaValidacionCP" class="hidden"
                     style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #FEF2F2;
                            border-radius: 0.375rem; border: 1px solid #FECACA;">
                    <p style="font-size: 0.75rem; color: #DC2626; margin: 0;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i>
                        Debe indicar si el cliente requiere factura
                    </p>
                </div>

                <!-- Info: pago con tarjeta/transferencia se registrará internamente -->
                <div id="facturaInfoInternaCP" class="hidden"
                     style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #EFF6FF;
                            border-radius: 0.375rem; border: 1px solid #BFDBFE;">
                    <p style="font-size: 0.7rem; color: #1E40AF; margin: 0;">
                        <i class="fas fa-info-circle" style="margin-right: 0.25rem;"></i>
                        El pago con tarjeta/transferencia se registrará en facturación para uso interno
                    </p>
                </div>
            </div>
            <!-- ========== FIN SECCIÓN DE FACTURA CP ========== -->

            <!-- Resumen -->
            <div id="resumenCP" style="background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%); border-radius: 0.5rem; padding: 0.75rem; margin-top: 1rem; border: 1px solid #E5E7EB;">
                <h5 style="font-weight: 700; font-size: 0.75rem; color: #374151; margin-bottom: 0.375rem;">Resumen</h5>
                <div style="font-size: 0.75rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Total:</span>
                        <span id="totalCP" style="font-weight: 700;"><?= format_money($reservacion['precio_total'] ?? 0) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Asignado:</span>
                        <span id="asignadoCP" style="font-weight: 700; color: #10B981;">$0.00</span>
                    </div>
                    <div id="divPendienteCP" style="display: none; justify-content: space-between; margin-top: 0.25rem;">
                        <span>Pendiente:</span>
                        <span id="pendienteCP" style="font-weight: 700; color: #EF4444;">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Mensaje validación -->
            <div id="msgValidacionCP" class="hidden" style="margin-top: 0.75rem; padding: 0.5rem; border-radius: 0.375rem; font-size: 0.75rem;"></div>

            <!-- Botones -->
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                <button type="button" onclick="cerrarModalCambiarPago()"
                        style="flex: 1; padding: 0.625rem; border: 2px solid #E5E7EB; background: white; color: #374151; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    Cancelar
                </button>
                <button type="button" onclick="confirmarCambioPago()"
                        id="btnConfirmarCambio"
                        style="flex: 1; padding: 0.625rem; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check" style="margin-right: 0.25rem;"></i>
                    Confirmar Cambio
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ========== FIN MODAL CAMBIAR MÉTODO DE PAGO ========== -->

<!-- ── Modal Express / Tardío ─────────────────────────────── -->
<style>
/* Anular CSS viejo que convertía el modal en 2 columnas */
#modalCheckInTardio.rv-tardio-modal{all:unset;display:flex!important;position:fixed!important;inset:0!important;z-index:9990!important}
#modalCheckInTardio .rv-tardio-shell{all:unset!important;display:contents!important}
/* ── Overlay ── */
.xpm-ov{position:fixed;inset:0;z-index:9990;background:rgba(40,48,62,.55);display:flex;align-items:flex-end;justify-content:center}
@media(min-width:600px){.xpm-ov{align-items:center;backdrop-filter:blur(4px)}}
/* ── Shell — usa tokens rdv3 ── */
.xpm-shell{
    --xpm-bg: #FAFAF8;
    --xpm-card: #FFFFFF;
    --xpm-border: rgba(60,70,86,.08);
    --xpm-ink: #3F3F3F;
    --xpm-muted: #808080;
    --xpm-green: #22b77a;
    --xpm-green-2: #1fa66f;
    --xpm-green-soft: #e8f7f0;
    --xpm-amber: #B66A00;
    --xpm-amber-soft: #FFF3E0;
    --xpm-blue: #6B7F99;
    --xpm-line: rgba(60,70,86,.08);
    width:100%;height:100dvh;max-height:100dvh;
    background:var(--xpm-bg);
    display:flex;flex-direction:column;overflow:hidden;
    font-family:Manrope,system-ui,sans-serif;
    -webkit-font-smoothing:antialiased;
    color:var(--xpm-ink);
}
@media(min-width:600px){.xpm-shell{max-width:420px;height:auto;max-height:min(90dvh,760px);border-radius:20px;box-shadow:0 40px 80px -16px rgba(20,28,46,.28),0 0 0 1px rgba(255,255,255,.6)}}
.xpm-shell *,.xpm-shell *::before,.xpm-shell *::after{box-sizing:border-box}
/* ── Header ── */
.xpm-head{flex-shrink:0;display:flex;align-items:center;gap:11px;padding:15px 16px 14px;background:var(--xpm-card);border-bottom:1px solid var(--xpm-border)}
.xpm-head-ico{width:36px;height:36px;display:grid;place-items:center;border-radius:10px;background:var(--xpm-amber-soft);color:var(--xpm-amber);font-size:.85rem;flex-shrink:0}
.xpm-head-ico.is-late{background:var(--xpm-green-soft);color:var(--xpm-green-2)}
.xpm-head-text{flex:1;min-width:0}
.xpm-head-title{display:block;font-size:.94rem;font-weight:800;color:var(--xpm-ink);line-height:1.2}
.xpm-head-sub{display:block;font-size:.68rem;font-weight:600;color:var(--xpm-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.xpm-head-close{width:32px;height:32px;display:grid;place-items:center;border:1px solid var(--xpm-border);border-radius:9px;background:var(--xpm-bg);color:var(--xpm-muted);font-size:.8rem;cursor:pointer;flex-shrink:0;transition:background .14s,color .14s,border-color .14s}
.xpm-head-close:hover{background:#FEE2E2;color:#B91C1C;border-color:#FCA5A5}
/* Alert */
.xpm-alert{flex-shrink:0;padding:9px 14px;background:var(--xpm-amber-soft);border-bottom:1px solid rgba(182,106,0,.2);font-size:.75rem;font-weight:700;color:#78350F;display:none;line-height:1.4}
/* Form */
.xpm-form{flex:1;display:flex;flex-direction:column;min-height:0}
/* Body scrollable */
.xpm-body{flex:1;overflow-y:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;padding:13px 14px 8px;display:flex;flex-direction:column;gap:10px}
/* Validación */
.xpm-vmsg{padding:8px 12px;background:#FEF2F2;border:1px solid #FECACA;border-radius:9px;font-size:.76rem;font-weight:600;color:#B91C1C}
.xpm-vmsg.hidden{display:none}
/* Field */
.xpm-field{display:flex;flex-direction:column;gap:5px}
.xpm-lbl{font-size:.72rem;font-weight:700;color:var(--xpm-muted);display:flex;align-items:center;gap:5px;text-transform:uppercase;letter-spacing:.04em}
.xpm-lbl i{font-size:.65rem}
.xpm-inp{width:100%;height:40px;padding:0 11px;border:1px solid rgba(60,70,86,.16);border-radius:10px;font-family:inherit;font-size:.86rem;font-weight:600;color:var(--xpm-ink);background:var(--xpm-card);outline:none;-webkit-appearance:none;appearance:none;transition:border-color .14s,box-shadow .14s}
.xpm-inp:focus{border-color:var(--xpm-green);box-shadow:0 0 0 3px rgba(34,183,122,.12)}
textarea.xpm-inp{height:auto;padding:9px 11px;resize:none;line-height:1.45;font-size:.82rem}
/* Total */
.xpm-total{background:var(--xpm-card);border:1px solid var(--xpm-border);border-radius:13px;padding:12px 14px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 8px rgba(40,48,62,.04)}
.xpm-total-lbl{font-size:.7rem;font-weight:700;color:var(--xpm-muted);text-transform:uppercase;letter-spacing:.04em}
.xpm-total-val{font-size:1.55rem;font-weight:800;color:var(--xpm-ink);font-variant-numeric:tabular-nums}
/* Section label */
.xpm-sec{font-size:.64rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--xpm-muted);margin-bottom:1px}
/* Shortcuts */
.xpm-sc-grid{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.xpm-sc{display:flex;align-items:center;gap:7px;padding:9px 11px;background:var(--xpm-card);border:1px solid var(--xpm-border);border-radius:10px;font-family:inherit;font-size:.72rem;font-weight:700;color:var(--xpm-ink);cursor:pointer;-webkit-tap-highlight-color:transparent;transition:background .14s,border-color .14s;box-shadow:0 1px 4px rgba(40,48,62,.04)}
.xpm-sc i{font-size:.78rem;color:var(--xpm-muted);flex-shrink:0;width:14px;text-align:center}
.xpm-sc:hover{background:var(--xpm-green-soft);border-color:rgba(34,183,122,.28);color:var(--xpm-green-2)}
.xpm-sc:hover i{color:var(--xpm-green-2)}
.xpm-sc:active{transform:scale(.98)}
/* Payment opts */
.xpm-pay-list{display:flex;flex-direction:column;gap:5px}
.xpm-pay-opt{background:var(--xpm-card);border:1px solid var(--xpm-border);border-radius:11px;overflow:hidden;box-shadow:0 1px 4px rgba(40,48,62,.04);transition:border-color .14s}
.xpm-pay-opt.is-active{border-color:var(--xpm-green)}
.xpm-pay-toggle{display:flex;align-items:center;gap:10px;padding:10px 12px;cursor:pointer;-webkit-tap-highlight-color:transparent}
.xpm-pay-toggle input[type=checkbox]{width:16px;height:16px;accent-color:var(--xpm-green);flex-shrink:0;cursor:pointer}
.xpm-pay-ico{width:28px;height:28px;display:grid;place-items:center;border-radius:7px;font-size:.72rem;flex-shrink:0}
.xpm-pay-ico.cash{background:#E8F5E9;color:#2E7D32}
.xpm-pay-ico.card{background:#E3F2FD;color:#1565C0}
.xpm-pay-ico.xfer{background:#F3E5F5;color:#6A1B9A}
.xpm-pay-ico.pend{background:var(--xpm-bg);color:var(--xpm-muted)}
.xpm-pay-name{font-size:.84rem;font-weight:700;color:var(--xpm-ink);flex:1}
.xpm-pay-body{padding:0 12px 11px;display:flex;flex-direction:column;gap:8px}
.xpm-pay-body.hidden{display:none}
.xpm-2col{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.xpm-fld{display:flex;flex-direction:column;gap:3px}
.xpm-fld-lbl{font-size:.64rem;font-weight:700;color:var(--xpm-muted);text-transform:uppercase;letter-spacing:.03em}
.xpm-cambio{padding:7px 10px;background:#E8F5E9;border-radius:8px;display:flex;justify-content:space-between;align-items:center;font-size:.75rem;font-weight:700;color:#1B5E20}
.xpm-card-types{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.xpm-card-chip{display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;border:1px solid var(--xpm-border);border-radius:8px;background:var(--xpm-bg);color:var(--xpm-muted);font-family:inherit;font-size:.72rem;font-weight:700;cursor:pointer;-webkit-tap-highlight-color:transparent;transition:background .12s,border-color .12s,color .12s}
.xpm-card-chip input{display:none}
.xpm-card-chip.is-sel{background:#E3F2FD;border-color:#1565C0;color:#1565C0}
/* Resumen mixto */
.xpm-resumen{background:var(--xpm-bg);border:1px solid var(--xpm-border);border-radius:10px;padding:9px 12px;display:flex;flex-direction:column;gap:5px;margin-top:3px}
.xpm-resumen.hidden{display:none}
.xpm-rrow{display:flex;justify-content:space-between;align-items:center;font-size:.79rem}
.xpm-rrow span{color:var(--xpm-muted);font-weight:600}
.xpm-rrow strong{color:var(--xpm-ink);font-weight:800}
.xpm-rrow strong.red{color:#B91C1C}
/* Invoice — pill row */
.xpm-inv-row{display:flex;gap:6px}
.xpm-inv-opt{flex:1;display:flex;align-items:center;gap:7px;padding:9px 11px;background:var(--xpm-card);border:1px solid var(--xpm-border);border-radius:10px;cursor:pointer;-webkit-tap-highlight-color:transparent;transition:background .14s,border-color .14s;min-width:0;box-shadow:0 1px 4px rgba(40,48,62,.04)}
.xpm-inv-opt input{accent-color:var(--xpm-green);flex-shrink:0;width:15px;height:15px}
.xpm-inv-opt:has(input:checked){border-color:var(--xpm-green);background:var(--xpm-green-soft)}
.xpm-inv-t{font-size:.78rem;font-weight:700;color:var(--xpm-ink)}
.xpm-inv-note{padding:7px 10px;border-radius:8px;font-size:.73rem;font-weight:600;margin-top:6px}
.xpm-inv-note.is-err{background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C}
.xpm-inv-note.is-info{background:#EFF6FF;border:1px solid #BFDBFE;color:#1565C0}
.xpm-inv-note.is-ok{background:var(--xpm-green-soft);border:1px solid rgba(34,183,122,.2);color:var(--xpm-green-2)}
.xpm-inv-note.hidden{display:none}
/* Mini btn */
.xpm-mini{align-self:flex-start;padding:4px 10px;border:1px solid var(--xpm-border);border-radius:7px;background:var(--xpm-bg);color:var(--xpm-muted);font-family:inherit;font-size:.68rem;font-weight:700;cursor:pointer;margin-top:2px;-webkit-tap-highlight-color:transparent}
.xpm-mini:hover{background:var(--xpm-green-soft);color:var(--xpm-green-2)}
/* Footer */
.xpm-foot{flex-shrink:0;display:grid;grid-template-columns:auto 1fr;gap:8px;padding:11px 14px;padding-bottom:calc(11px + env(safe-area-inset-bottom));background:var(--xpm-card);border-top:1px solid var(--xpm-border)}
.xpm-fbtn{height:44px;display:flex;align-items:center;justify-content:center;gap:7px;border-radius:12px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;border:none;-webkit-tap-highlight-color:transparent;transition:background .14s,transform .13s,box-shadow .14s}
.xpm-fbtn:active{transform:scale(.97)}
.xpm-fbtn-cancel{background:var(--xpm-bg);color:var(--xpm-muted);border:1px solid var(--xpm-border);padding:0 18px}
.xpm-fbtn-cancel:hover{background:var(--xpm-border);color:var(--xpm-ink)}
.xpm-fbtn-ok{background:var(--xpm-green);color:#fff;box-shadow:0 12px 24px rgba(34,183,122,.28)}
.xpm-fbtn-ok:hover{background:var(--xpm-green-2);box-shadow:0 8px 16px rgba(34,183,122,.22)}
.xpm-fbtn-ok.is-express{background:var(--xpm-amber);box-shadow:0 12px 24px rgba(182,106,0,.22)}
.xpm-fbtn-ok.is-express:hover{background:#92400E}
</style>

<div id="modalCheckInTardio" class="xpm-ov" style="display:none;">
    <div class="xpm-shell">

        <div class="xpm-head">
            <div class="xpm-head-ico is-late" id="xpHeaderIcon"><i class="fas fa-clock"></i></div>
            <div class="xpm-head-text">
                <span class="xpm-head-title" id="tituloModalTardio">Check-in Tardío</span>
                <span class="xpm-head-sub">Confirma la llegada y registra el pago</span>
            </div>
            <button type="button" class="xpm-head-close" onclick="cerrarModalCheckInTardio()" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="alertaTardio" class="xpm-alert"></div>

        <form id="formCheckInTardio" method="POST" action="" class="xpm-form rv-checkin-form rv-tardio-form">
            <?= csrf_field() ?>
            <input type="hidden" name="tipo" id="tipo_tardio" value="">

            <div class="xpm-body">

                <div id="mensajeValidacionTardio" class="xpm-vmsg hidden rv-checkin-message" aria-live="polite"></div>

                <!-- Info compacta en una línea (ocultos para JS, los datos se muestran en el subtitle del header) -->
                <span id="huesped_tardio" style="display:none"></span>
                <span id="habitaciones_tardio" style="display:none"></span>
                <span id="fecha_entrada_tardio" style="display:none"></span>
                <span id="fecha_salida_tardio" style="display:none"></span>

                <div id="campo_hora_entrada" class="xpm-field rv-arrival-field">
                    <label class="xpm-lbl" for="hora_entrada_xpm"><i class="fas fa-clock"></i> Hora de check-in</label>
                    <input type="time" name="hora_entrada" id="hora_entrada_xpm" class="xpm-inp" value="<?= date('H:i') ?>">
                </div>

                <div class="xpm-total">
                    <span class="xpm-total-lbl"><i class="fas fa-receipt"></i> Total a cobrar</span>
                    <span class="xpm-total-val" id="totalACobrarTardio">$0.00</span>
                </div>

                <div>
                    <div class="xpm-sec" style="margin-bottom:7px;"><i class="fas fa-wallet"></i> Pago rápido</div>
                    <div class="xpm-sc-grid" style="margin-bottom:8px;">
                        <button type="button" class="xpm-sc rv-money-shortcut is-cash" onclick="aplicarPagoRapidoTardio('efectivo')">
                            <i class="fas fa-money-bill-wave"></i>Efectivo exacto
                        </button>
                        <button type="button" class="xpm-sc rv-money-shortcut is-card" onclick="aplicarPagoRapidoTardio('tarjeta')">
                            <i class="fas fa-credit-card"></i>Tarjeta exacta
                        </button>
                        <button type="button" class="xpm-sc rv-money-shortcut is-transfer" onclick="aplicarPagoRapidoTardio('transferencia')">
                            <i class="fas fa-exchange-alt"></i>Transferencia
                        </button>
                        <button type="button" class="xpm-sc rv-money-shortcut is-split" onclick="dividirPagoRapidoTardio()">
                            <i class="fas fa-code-branch"></i>Mitad ef./tarjeta
                        </button>
                        <button type="button" class="xpm-sc rv-money-shortcut is-cash-transfer" onclick="dividirPagoEfectivoTransferenciaTardio()" style="grid-column:span 2;">
                            <i class="fas fa-university"></i>Efectivo + transferencia
                        </button>
                    </div>

                    <div class="xpm-sec" style="margin-bottom:7px;"><i class="fas fa-wallet"></i> Método de pago</div>
                    <p id="nota_pago_opcional" style="font-size:.7rem;color:#9CA3AF;font-weight:500;margin:0 0 7px;">Opcional — puede dejarse pendiente.</p>

                    <div id="metodosPagoContainerTardio" class="xpm-pay-list rv-pay-methods">

                        <div class="xpm-pay-opt metodo-pago-item rv-pay-option rv-pay-pending">
                            <label class="xpm-pay-toggle">
                                <input type="checkbox" id="check_sin_pago" onchange="toggleMetodoPagoTardio('sin_pago')" checked>
                                <span class="xpm-pay-ico pend"><i class="fas fa-clock"></i></span>
                                <span class="xpm-pay-name">Sin pago ahora</span>
                            </label>
                        </div>

                        <div class="xpm-pay-opt metodo-pago-item rv-pay-option rv-pay-cash">
                            <label class="xpm-pay-toggle">
                                <input type="checkbox" id="check_efectivo_tardio" onchange="toggleMetodoPagoTardio('efectivo')">
                                <span class="xpm-pay-ico cash"><i class="fas fa-money-bill-wave"></i></span>
                                <span class="xpm-pay-name">Efectivo</span>
                            </label>
                            <div id="panel_efectivo_tardio" class="xpm-pay-body hidden">
                                <div class="xpm-2col">
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Total a cobrar</span>
                                        <input type="number" name="monto_efectivo" id="monto_efectivo_tardio" data-money-format="true" step="0.01" min="0" readonly class="xpm-inp" style="background:#F4F7F3">
                                    </div>
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Monto recibido</span>
                                        <input type="number" name="recibido_efectivo" id="recibido_efectivo_tardio" data-money-format="true" step="0.01" min="0" oninput="calcularCambioTardio()" onchange="calcularCambioTardio()" onkeyup="calcularCambioTardio()" placeholder="0.00" class="xpm-inp">
                                        <button type="button" class="xpm-mini rv-money-mini" onclick="marcarEfectivoExactoTardio()">Recibí exacto</button>
                                    </div>
                                </div>
                                <div class="xpm-cambio">
                                    <span>Cambio</span>
                                    <strong id="cambio_efectivo_tardio">$0.00</strong>
                                </div>
                            </div>
                        </div>

                        <div class="xpm-pay-opt metodo-pago-item rv-pay-option rv-pay-card">
                            <label class="xpm-pay-toggle">
                                <input type="checkbox" id="check_tarjeta_tardio" onchange="toggleMetodoPagoTardio('tarjeta')">
                                <span class="xpm-pay-ico card"><i class="fas fa-credit-card"></i></span>
                                <span class="xpm-pay-name">Tarjeta</span>
                            </label>
                            <div id="panel_tarjeta_tardio" class="xpm-pay-body hidden">
                                <div class="xpm-card-types">
                                    <label class="xpm-card-chip" id="label_credito_tardio" onclick="this.classList.add('is-sel');document.getElementById('label_debito_tardio').classList.remove('is-sel')">
                                        <input type="radio" name="tipo_tarjeta_tardio" value="credito">
                                        <i class="fas fa-credit-card"></i> Crédito
                                    </label>
                                    <label class="xpm-card-chip" id="label_debito_tardio" onclick="this.classList.add('is-sel');document.getElementById('label_credito_tardio').classList.remove('is-sel')">
                                        <input type="radio" name="tipo_tarjeta_tardio" value="debito">
                                        <i class="fas fa-money-check-alt"></i> Débito
                                    </label>
                                </div>
                                <div class="xpm-2col">
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Monto</span>
                                        <input type="number" name="monto_tarjeta" id="monto_tarjeta_tardio" data-money-format="true" step="0.01" min="0" oninput="calcularTotalesTardio()" onchange="calcularTotalesTardio()" class="xpm-inp">
                                    </div>
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Referencia</span>
                                        <input type="text" name="referencia_tarjeta" placeholder="Últ. 4 dígitos" class="xpm-inp">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="xpm-pay-opt metodo-pago-item rv-pay-option rv-pay-transfer">
                            <label class="xpm-pay-toggle">
                                <input type="checkbox" id="check_transferencia_tardio" onchange="toggleMetodoPagoTardio('transferencia')">
                                <span class="xpm-pay-ico xfer"><i class="fas fa-exchange-alt"></i></span>
                                <span class="xpm-pay-name">Transferencia</span>
                            </label>
                            <div id="panel_transferencia_tardio" class="xpm-pay-body hidden">
                                <div class="xpm-2col">
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Monto</span>
                                        <input type="number" name="monto_transferencia" id="monto_transferencia_tardio" data-money-format="true" step="0.01" min="0" oninput="calcularTotalesTardio()" onchange="calcularTotalesTardio()" class="xpm-inp">
                                    </div>
                                    <div class="xpm-fld">
                                        <span class="xpm-fld-lbl">Referencia</span>
                                        <input type="text" name="referencia_transferencia" placeholder="Nº de operación" class="xpm-inp">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div id="resumen_totales_tardio" class="xpm-resumen hidden">
                        <div class="xpm-rrow"><span>Total pagado</span><strong id="total_pagado_tardio">$0.00</strong></div>
                        <div class="xpm-rrow"><span>Pendiente</span><strong id="pendiente_tardio" class="red">$0.00</strong></div>
                    </div>
                </div>

                <div>
                    <div class="xpm-sec"><i class="fas fa-file-invoice"></i> ¿Requiere factura?</div>
                    <div id="facturaContainerTardio" class="xpm-inv-row rv-invoice-grid" style="margin-top:8px;">
                        <label class="xpm-inv-opt rv-invoice-choice" id="label_factura_si_tardio">
                            <input type="radio" name="requiere_factura" id="factura_si_tardio" value="si" onchange="seleccionarFacturaTardio('si')">
                            <span class="xpm-inv-t">Con factura</span>
                        </label>
                        <label class="xpm-inv-opt rv-invoice-choice" id="label_factura_no_tardio">
                            <input type="radio" name="requiere_factura" id="factura_no_tardio" value="no" onchange="seleccionarFacturaTardio('no')">
                            <span class="xpm-inv-t">Sin factura</span>
                        </label>
                    </div>
                    <?php if (!empty($anticipoFacturaSolicitud)): ?>
                        <div style="margin-top:10px;padding:10px;border:1px solid #BFDBFE;border-radius:10px;background:#EFF6FF;">
                            <div style="font-size:.72rem;color:#1E40AF;font-weight:700;margin-bottom:7px;">Factura pendiente #<?= (int)($anticipoFacturaSolicitud['id'] ?? 0) ?> por $<?= number_format((float)($anticipoFacturaSolicitud['monto_total'] ?? 0), 2) ?></div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#1E3A8A;cursor:pointer;">
                                    <input type="radio" name="factura_modo" value="acumular"> Sumar a factura pendiente
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:700;color:#1E3A8A;cursor:pointer;">
                                    <input type="radio" name="factura_modo" value="separada" checked> Crear factura separada
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div id="facturaResultadoTardio" class="xpm-inv-note is-ok rv-invoice-result hidden"></div>
                    <div id="facturaValidacionTardio" class="xpm-inv-note is-err rv-checkin-message hidden"><i class="fas fa-exclamation-circle"></i> Indica si el cliente requiere factura</div>
                    <div id="facturaInfoInternaTardio" class="xpm-inv-note is-info rv-checkin-note hidden"><i class="fas fa-info-circle"></i> Tarjeta/transferencia se registra en facturación interna</div>
                </div>

                <div class="xpm-field rv-notes-section">
                    <label class="xpm-lbl"><i class="fas fa-note-sticky"></i> Notas <span style="font-weight:400;color:#9CA3AF;font-size:.7rem">(opcional)</span></label>
                    <textarea name="notas_adicionales" rows="2" class="xpm-inp" placeholder="Ej: El personal olvidó registrar la llegada"></textarea>
                </div>

            </div>

            <div class="xpm-foot rv-checkin-actions">
                <button type="button" onclick="cerrarModalCheckInTardio()" class="xpm-fbtn xpm-fbtn-cancel rv-btn-cancel">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" id="btnConfirmarTardio" class="xpm-fbtn xpm-fbtn-ok rv-btn-confirm">
                    <i class="fas fa-check"></i> Confirmar Check-in
                </button>
            </div>

        </form>
    </div>
</div>

<div id="modalCotizacion" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 480px; border-radius: 16px; overflow: hidden;">
        <!-- Header -->
        <div class="modal-header" style="padding: 1.25rem; border-bottom: 2px solid #F3F4F6; background: linear-gradient(135deg, #78350F 0%, #92400E 50%, #A16207 100%); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: white; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-file-pdf" style="color: #FDE68A;"></i>
                Generar Cotización PDF
            </h3>
            <button onclick="cerrarModalCotizacion()" style="background: rgba(255,255,255,0.15); border: none; cursor: pointer; color: white; font-size: 1rem; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 1.25rem;">
            <!-- Resumen de la reservación -->
            <div style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); border: 2px solid #F59E0B; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; font-weight: 600; color: #92400E; text-transform: uppercase; letter-spacing: 0.5px;">
                        <i class="fas fa-calendar-check" style="margin-right: 4px;"></i>
                        Reservación #<?= $reservacion['id'] ?>
                    </span>
                    <span style="font-size: 0.75rem; color: #92400E;">
                        <?= $noches ?> noche<?= $noches > 1 ? 's' : '' ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <div>
                        <p style="font-size: 0.8rem; color: #78350F; font-weight: 600;">
                            <?= htmlspecialchars($huesped['nombre_completo']) ?>
                        </p>
                        <p style="font-size: 0.7rem; color: #92400E; margin-top: 2px;">
                            <?= count($habitaciones) ?> habitación<?= count($habitaciones) > 1 ? 'es' : '' ?>
                            · <?= date('d/m/Y', strtotime($reservacion['fecha_entrada'])) ?> al <?= date('d/m/Y', strtotime($reservacion['fecha_salida'])) ?>
                        </p>
                    </div>
                    <p style="font-weight: 600; font-size: 1.25rem; color: #78350F;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </p>
                </div>
            </div>

            <?php
                // El anticipo de la cotización se toma de los pagos ya registrados
                // (abonos/anticipos). El backend usa ese mismo valor real, así que
                // aquí solo lo mostramos; ya no se captura a mano.
                $cotTotal       = (float)($reservacion['precio_total'] ?? 0);
                $cotAnticipo    = (float)($rpResumen['pagado'] ?? 0);
                $cotSaldo       = (float)($rpResumen['saldo'] ?? max(0, $cotTotal - $cotAnticipo));
                $cotConAnticipo = $cotAnticipo > 0.004;
            ?>
            <!-- Resumen de saldo (anticipo real ya registrado; no editable) -->
            <div style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #065F46;">Total estancia:</span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #065F46;"><?= format_money($cotTotal) ?></span>
                </div>
                <?php if ($cotConAnticipo): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #059669;"><i class="fas fa-hand-holding-usd" style="margin-right: 5px;"></i>Anticipo registrado:</span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #059669;">-<?= format_money($cotAnticipo) ?></span>
                </div>
                <?php endif; ?>
                <div style="border-top: 1px dashed #BBF7D0; padding-top: 6px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.875rem; font-weight: 700; color: #065F46;">Saldo pendiente:</span>
                    <span style="font-size: 1.1rem; font-weight: 600; color: #065F46;"><?= format_money($cotConAnticipo ? $cotSaldo : $cotTotal) ?></span>
                </div>
            </div>
            <?php if (!$cotConAnticipo): ?>
            <p style="font-size: 0.72rem; color: #6B7280; margin: -6px 0 16px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-circle-info" style="color: #9CA3AF;"></i>
                El anticipo se toma de los pagos registrados. Esta reservación aún no tiene anticipos.
            </p>
            <?php endif; ?>

            <!-- Botones -->
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <button onclick="generarCotizacionPdf()"
                        style="width: 100%; padding: 12px; border-radius: 10px; border: none; cursor: pointer; font-weight: 700; font-size: 0.9rem; color: white; background: linear-gradient(135deg, #D97706, #B45309); display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(217,119,6,0.3); transition: transform 0.2s, box-shadow 0.2s;"
                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(217,119,6,0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(217,119,6,0.3)';">
                    <i class="fas fa-file-pdf"></i>
                    Generar Cotización PDF
                </button>
                <button onclick="cerrarModalCotizacion()"
                        style="width: 100%; padding: 9px; border-radius: 9px; border: 1.5px solid #E5E7EB; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: #6B7280; background: white; display: flex; align-items: center; justify-content: center; gap: 6px; transition: background 0.15s;"
                        onmouseover="this.style.background='#F9FAFB';"
                        onmouseout="this.style.background='white';">
                    <i class="fas fa-times" style="font-size: 0.75rem;"></i>
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Form oculto para enviar cotización -->
<form id="formCotizacionReservacion" method="POST" action="<?= url('reservaciones/cotizacion-reservacion-pdf') ?>" target="_blank" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">
    <input type="hidden" name="anticipo" id="hiddenAnticipoCotizacion" value="<?= (float)($cotAnticipo ?? 0) ?>">
</form>
<!-- Formularios ocultos para acciones rápidas -->

<form id="formCheckOut" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<!-- Scripts -->

<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>












<script>
// =============================================
// COTIZACIÓN PDF (anticipo tomado de los pagos ya registrados)
// =============================================
function abrirModalCotizacion() {
    const modalCotizacion = document.getElementById('modalCotizacion');
    if (modalCotizacion) modalCotizacion.style.display = 'flex';
}

function cerrarModalCotizacion() {
    const modalCotizacion = document.getElementById('modalCotizacion');
    if (modalCotizacion) modalCotizacion.style.display = 'none';
}
window.abrirModalCotizacion = abrirModalCotizacion;
window.cerrarModalCotizacion = cerrarModalCotizacion;

function generarCotizacionPdf() {
    // El anticipo real ya viaja en el form oculto (server-side) y el backend
    // lo recalcula desde los pagos registrados. Aquí solo enviamos.
    if (window.MedisoftMobileFiles && window.MedisoftMobileFiles.isMobile()) {
        window.MedisoftMobileFiles.showHint('cotizacion PDF', false);
    }
    document.getElementById('formCotizacionReservacion').submit();

    // Cerrar modal después de un momento
    setTimeout(() => cerrarModalCotizacion(), 500);
}

// Cerrar modal al hacer click fuera
const modalCotizacionClick = document.getElementById('modalCotizacion');
if (modalCotizacionClick) {
    modalCotizacionClick.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalCotizacion();
    });
}
</script>
<script>

// Variables globales
let totalReservacion = 0;
let checkInWizardStep = 1;
let vehiculosHuesped = [];
let vehiculoEliminacionPendiente = null;
let vehiculoEliminacionTimer = null;

// Cargar vehículos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarVehiculos();

    // Animación de entrada para las cards
    const cards = document.querySelectorAll('.info-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
    });
});
// =============================================
// FUNCIONES DE FACTURACIÓN - CHECK-IN NORMAL
// =============================================

function seleccionarFactura(valor) {
    const labelSi = document.getElementById('label_factura_si');
    const labelNo = document.getElementById('label_factura_no');
    const validacion = document.getElementById('facturaValidacion');
    const infoInterna = document.getElementById('facturaInfoInterna');

    // Ocultar validación
    if (validacion) validacion.classList.add('hidden');

    [labelSi, labelNo].forEach(label => {
        if (!label) return;
        label.classList.remove('is-selected');
        label.style.borderColor = '';
        label.style.background = '';
    });

    if (valor === 'si') {
        if (labelSi) labelSi.classList.add('is-selected');
        if (infoInterna) infoInterna.classList.add('hidden');
    } else {
        if (labelNo) labelNo.classList.add('is-selected');

        // Mostrar info si hay pago con tarjeta o transferencia
        mostrarInfoFacturaInterna();
    }

    actualizarResultadoFacturaCheckIn();
}

function mostrarInfoFacturaInterna() {
    const infoInterna = document.getElementById('facturaInfoInterna');
    if (!infoInterna) return;

    const checkTarjeta = document.getElementById('check_tarjeta');
    const checkTransferencia = document.getElementById('check_transferencia');

    const tieneTarjeta = checkTarjeta && checkTarjeta.checked;
    const tieneTransferencia = checkTransferencia && checkTransferencia.checked;

    if (tieneTarjeta || tieneTransferencia) {
        infoInterna.classList.remove('hidden');
    } else {
        infoInterna.classList.add('hidden');
    }

    actualizarResultadoFacturaCheckIn();
}

function actualizarResultadoFacturaCheckIn() {
    const resultado = document.getElementById('facturaResultado');
    if (!resultado) return;

    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const checkTarjeta = document.getElementById('check_tarjeta');
    const checkTransferencia = document.getElementById('check_transferencia');
    const usaElectronico = Boolean((checkTarjeta && checkTarjeta.checked) || (checkTransferencia && checkTransferencia.checked));

    resultado.className = 'rv-invoice-result';

    if (facturaSi && facturaSi.checked) {
        resultado.classList.add('is-client');
        resultado.innerHTML = '<p><i class="fas fa-file-invoice"></i> Factura para cliente: se creara solicitud para facturacion del huesped con el metodo de pago seleccionado.</p>';
        return;
    }

    if (facturaNo && facturaNo.checked && usaElectronico) {
        resultado.classList.add('is-internal');
        resultado.innerHTML = '<p><i class="fas fa-building"></i> Uso interno del hotel: como hay tarjeta o transferencia, se dejara registro interno para control administrativo.</p>';
        return;
    }

    if (facturaNo && facturaNo.checked) {
        resultado.classList.add('is-none');
        resultado.innerHTML = '<p><i class="fas fa-receipt"></i> Sin facturacion: no se generara solicitud de factura para esta reservacion.</p>';
        return;
    }

    resultado.innerHTML = '<p><i class="fas fa-circle-info"></i> Selecciona una opcion para ver como quedara registrada la facturacion.</p>';
}

function validarFacturaCheckIn() {
    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const validacion = document.getElementById('facturaValidacion');

    if (checkInSinCobroNuevo()) {
        if (validacion) validacion.classList.add('hidden');
        return true;
    }

    if (!facturaSi.checked && !facturaNo.checked) {
        if (validacion) validacion.classList.remove('hidden');
        return false;
    }
    if (validacion) validacion.classList.add('hidden');
    return true;
}

function resetearFacturaCheckIn() {
    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const labelSi = document.getElementById('label_factura_si');
    const labelNo = document.getElementById('label_factura_no');
    const validacion = document.getElementById('facturaValidacion');
    const infoInterna = document.getElementById('facturaInfoInterna');

    if (facturaSi) facturaSi.checked = false;
    if (facturaNo) facturaNo.checked = false;
    [labelSi, labelNo].forEach(label => {
        if (!label) return;
        label.classList.remove('is-selected');
        label.style.borderColor = '';
        label.style.background = '';
    });
    if (validacion) validacion.classList.add('hidden');
    if (infoInterna) infoInterna.classList.add('hidden');
    actualizarResultadoFacturaCheckIn();
}

// =============================================
// WIZARD DE CHECK-IN NORMAL
// =============================================

function setCheckInWizardStep(step) {
    const modal = document.getElementById('modalCheckIn');
    const safeStep = Math.max(1, Math.min(3, parseInt(step, 10) || 1));
    const sinCobroNuevo = checkInSinCobroNuevo();
    checkInWizardStep = safeStep;

    if (modal) {
        modal.dataset.checkinStep = String(safeStep);
        modal.classList.toggle('is-paid-in-advance', sinCobroNuevo);
    }

    const pagoStepLabel = document.querySelector('#modalCheckIn [data-step-indicator="2"] strong');
    const facturaStepLabel = document.querySelector('#modalCheckIn [data-step-indicator="3"] strong');
    if (pagoStepLabel) pagoStepLabel.textContent = sinCobroNuevo ? 'Pago cubierto' : 'Pago';
    if (facturaStepLabel) facturaStepLabel.textContent = sinCobroNuevo ? 'Sin cobro nuevo' : 'Factura';

    document.querySelectorAll('#modalCheckIn [data-checkin-stage]').forEach(stage => {
        const stageStep = parseInt(stage.dataset.checkinStage, 10);
        const active = stageStep === safeStep;
        stage.classList.toggle('is-active', active);
        stage.setAttribute('aria-hidden', active ? 'false' : 'true');
    });

    document.querySelectorAll('#modalCheckIn [data-step-indicator]').forEach(indicator => {
        const indicatorStep = parseInt(indicator.dataset.stepIndicator, 10);
        const skipped = sinCobroNuevo && indicatorStep > 1;
        indicator.classList.toggle('is-skipped', skipped);
        indicator.classList.toggle('is-current', !skipped && indicatorStep === safeStep);
        indicator.classList.toggle('is-complete', !skipped && indicatorStep < safeStep);
    });

    document.querySelectorAll('#modalCheckIn [data-step-line]').forEach(line => {
        const lineStep = parseInt(line.dataset.stepLine, 10);
        const skipped = sinCobroNuevo && lineStep >= 1;
        line.classList.toggle('is-complete', !skipped && lineStep < safeStep);
        line.classList.toggle('is-current', !skipped && lineStep === safeStep - 1);
    });

    document.querySelectorAll('#modalCheckIn [data-step-dot]').forEach(dot => {
        dot.classList.toggle('is-active', parseInt(dot.dataset.stepDot, 10) === safeStep);
    });

    const backButton = document.getElementById('btnCheckInBack');
    if (backButton) {
        const label = backButton.querySelector('span');
        const icon = backButton.querySelector('i');
        if (label) label.textContent = safeStep === 1 ? 'Cancelar' : 'Atras';
        if (icon) icon.className = safeStep === 1 ? 'fas fa-times' : 'fas fa-arrow-left';
    }

    const nextButton = document.getElementById('btnConfirmarCheckIn');
    if (nextButton) {
        const label = nextButton.querySelector('span');
        const icon = nextButton.querySelector('i');
        const isFinalStep = safeStep === 3 || (sinCobroNuevo && safeStep === 1);
        if (label) label.textContent = isFinalStep ? 'Confirmar check-in' : 'Continuar';
        if (icon) icon.className = isFinalStep ? 'fas fa-check' : 'fas fa-chevron-right';
        nextButton.disabled = false;
    }

    ocultarMensaje();
}

function retrocederOCerrarCheckInWizard() {
    if (checkInWizardStep > 1) {
        setCheckInWizardStep(checkInWizardStep - 1);
        return;
    }

    cerrarModalCheckIn();
}

function validarLlegadaCheckInWizard() {
    const horaEntrada = document.getElementById('horaEntradaCheckIn');
    if (!horaEntrada) {
        return true;
    }

    if (!horaEntrada.value) {
        mostrarMensaje('Indica la hora de llegada para continuar.', 'warning');
        horaEntrada.focus();
        return false;
    }

    return true;
}

function checkInSinCobroNuevo() {
    return Number(totalReservacion || 0) <= 0.01;
}

function validarPagoCheckInWizard() {
    if (checkInSinCobroNuevo()) {
        setSaldoPendienteCheckInHidden(false);
        ocultarMensaje();
        return true;
    }

    const metodosSeleccionados = getSelectedCheckInPaymentMethods();

    if (metodosSeleccionados.length === 0 && totalReservacion > 0.01) {
        mostrarMensaje('Debe seleccionar al menos un metodo de pago', 'warning');
        return false;
    }

    ajustarEfectivoPendienteDesdeRecibido();

    const totalPagado = calcularTotalPagado();
    const saldoRestante = totalReservacion - totalPagado;
    const permitirPendiente = isSaldoPendienteCheckInActivo();
    setSaldoPendienteCheckInHidden(permitirPendiente && saldoRestante > 0.01);

    if (saldoRestante > 0.01 && (!permitirPendiente || totalPagado <= 0.01)) {
        mostrarMensaje('Para hacer check-in con pago parcial, activa Dejar saldo pendiente y registra el monto recibido.', 'error');
        return false;
    }

    if (saldoRestante < -0.01) {
        mostrarMensaje('El monto total excede el precio de la reservacion', 'error');
        return false;
    }

    const checkEfectivo = document.getElementById('check_efectivo');
    if (checkEfectivo && checkEfectivo.checked) {
        const recibido = moneyValue(document.getElementById('recibido_efectivo'));
        const montoPagar = moneyValue(document.getElementById('monto_efectivo'));

        if (montoPagar > 0 && recibido < montoPagar) {
            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            return false;
        }
    }

    const checkTarjeta = document.getElementById('check_tarjeta');
    if (checkTarjeta && checkTarjeta.checked) {
        const tipoTarjetaSeleccionado = document.querySelector('input[name="tipo_tarjeta"]:checked');
        if (!tipoTarjetaSeleccionado) {
            mostrarMensaje('Debe seleccionar el tipo de tarjeta: Credito o Debito', 'error');
            const labelCredito = document.getElementById('label_credito');
            const labelDebito = document.getElementById('label_debito');
            if (labelCredito) labelCredito.style.borderColor = '#EF4444';
            if (labelDebito) labelDebito.style.borderColor = '#EF4444';
            setTimeout(() => {
                if (labelCredito) labelCredito.style.borderColor = '';
                if (labelDebito) labelDebito.style.borderColor = '';
            }, 2200);
            return false;
        }
    }

    return true;
}

function avanzarCheckInWizard() {
    const form = document.getElementById('formCheckInModal');

    if (checkInWizardStep === 1) {
        if (!validarLlegadaCheckInWizard()) return;
        if (checkInSinCobroNuevo()) {
            setSaldoPendienteCheckInHidden(false);
            if (form && typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else if (form) {
                sanitizeMoneyForm(form);
                form.submit();
            }
            return;
        }
        setCheckInWizardStep(2);
        return;
    }

    if (checkInWizardStep === 2) {
        if (!validarPagoCheckInWizard()) return;
        setCheckInWizardStep(3);
        return;
    }

    if (!validarFacturaCheckIn()) {
        mostrarMensaje('Debe indicar si el cliente requiere factura', 'warning');
        return;
    }

    if (!validarPagoCheckInWizard()) {
        setCheckInWizardStep(2);
        validarPagoCheckInWizard();
        return;
    }

    if (form && typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else if (form) {
        sanitizeMoneyForm(form);
        form.submit();
    }
}

// =============================================
// FUNCIONES DE FACTURACION - CHECK-IN TARDIO
// =============================================
function seleccionarFacturaTardio(valor) {
    const labelSi = document.getElementById('label_factura_si_tardio');
    const labelNo = document.getElementById('label_factura_no_tardio');
    const validacion = document.getElementById('facturaValidacionTardio');
    const infoInterna = document.getElementById('facturaInfoInternaTardio');

    if (validacion) validacion.classList.add('hidden');

    labelSi.style.borderColor = '#E5E7EB';
    labelSi.style.background = '#F9FAFB';
    labelNo.style.borderColor = '#E5E7EB';
    labelNo.style.background = '#F9FAFB';

    if (valor === 'si') {
        labelSi.style.borderColor = '#3B82F6';
        labelSi.style.background = '#EFF6FF';
        if (infoInterna) infoInterna.classList.add('hidden');
    } else {
        labelNo.style.borderColor = '#6B7280';
        labelNo.style.background = '#F3F4F6';

        // Mostrar info si hay pago con tarjeta o transferencia
        mostrarInfoFacturaInternaTardio();
    }

    actualizarResultadoFacturaTardio();
}

function mostrarInfoFacturaInternaTardio() {
    const infoInterna = document.getElementById('facturaInfoInternaTardio');
    if (!infoInterna) return;

    const checkTarjeta = document.getElementById('check_tarjeta_tardio');
    const checkTransferencia = document.getElementById('check_transferencia_tardio');

    const tieneTarjeta = checkTarjeta && checkTarjeta.checked;
    const tieneTransferencia = checkTransferencia && checkTransferencia.checked;

    if (tieneTarjeta || tieneTransferencia) {
        infoInterna.classList.remove('hidden');
    } else {
        infoInterna.classList.add('hidden');
    }

    actualizarResultadoFacturaTardio();
}

function actualizarResultadoFacturaTardio() {
    const resultado = document.getElementById('facturaResultadoTardio');
    if (!resultado) return;

    const facturaSi = document.getElementById('factura_si_tardio');
    const facturaNo = document.getElementById('factura_no_tardio');
    const checkTarjeta = document.getElementById('check_tarjeta_tardio');
    const checkTransferencia = document.getElementById('check_transferencia_tardio');
    const usaElectronico = Boolean((checkTarjeta && checkTarjeta.checked) || (checkTransferencia && checkTransferencia.checked));

    resultado.className = 'rv-invoice-result';

    if (facturaSi && facturaSi.checked) {
        resultado.classList.add('is-client');
        resultado.innerHTML = '<p><i class="fas fa-file-invoice"></i> Factura para cliente: se creara solicitud para facturacion del huesped con el metodo de pago seleccionado.</p>';
        return;
    }

    if (facturaNo && facturaNo.checked && usaElectronico) {
        resultado.classList.add('is-internal');
        resultado.innerHTML = '<p><i class="fas fa-building"></i> Uso interno del hotel: como hay tarjeta o transferencia, se dejara registro interno para control administrativo.</p>';
        return;
    }

    if (facturaNo && facturaNo.checked) {
        resultado.classList.add('is-none');
        resultado.innerHTML = '<p><i class="fas fa-receipt"></i> Sin facturacion: no se generara solicitud de factura para esta reservacion.</p>';
        return;
    }

    resultado.innerHTML = '<p><i class="fas fa-circle-info"></i> Selecciona una opcion para ver como quedara registrada la facturacion.</p>';
}

function validarFacturaTardio() {
    const facturaSi = document.getElementById('factura_si_tardio');
    const facturaNo = document.getElementById('factura_no_tardio');
    const validacion = document.getElementById('facturaValidacionTardio');

    if (!facturaSi.checked && !facturaNo.checked) {
        if (validacion) validacion.classList.remove('hidden');
        return false;
    }
    if (validacion) validacion.classList.add('hidden');
    return true;
}

function resetearFacturaTardio() {
    const facturaSi = document.getElementById('factura_si_tardio');
    const facturaNo = document.getElementById('factura_no_tardio');
    const labelSi = document.getElementById('label_factura_si_tardio');
    const labelNo = document.getElementById('label_factura_no_tardio');
    const validacion = document.getElementById('facturaValidacionTardio');
    const infoInterna = document.getElementById('facturaInfoInternaTardio');

    if (facturaSi) facturaSi.checked = false;
    if (facturaNo) facturaNo.checked = false;
    if (labelSi) { labelSi.style.borderColor = '#E5E7EB'; labelSi.style.background = '#F9FAFB'; }
    if (labelNo) { labelNo.style.borderColor = '#E5E7EB'; labelNo.style.background = '#F9FAFB'; }
    if (validacion) validacion.classList.add('hidden');
    if (infoInterna) infoInterna.classList.add('hidden');
    actualizarResultadoFacturaTardio();
}
// FUNCIONES DE CHECK-IN CON PAGOS MIXTOS
// Saldo pendiente (total menos anticipos ya pagados). El check-in cobra ESTO, no el total.
const SALDO_RESERVACION_CHECKIN = <?= json_encode(floatval($resumenPagos['saldo'] ?? ($reservacion['precio_total'] ?? 0))) ?>;
const TOTAL_RESERVACION_CHECKIN = <?= json_encode(floatval($reservacion['precio_total'] ?? 0)) ?>;

function abrirModalCheckIn(id, total) {
    // Cobrar el SALDO pendiente (descontando anticipos), no el total bruto.
    const saldoACobrar = (typeof SALDO_RESERVACION_CHECKIN === 'number' && SALDO_RESERVACION_CHECKIN >= 0)
        ? SALDO_RESERVACION_CHECKIN
        : parseFloat(total);
    totalReservacion = saldoACobrar;

    const modal = document.getElementById('modalCheckIn');
    if (!modal) {
        console.error('Modal de check-in no encontrado');
        return;
    }

    const form = document.getElementById('formCheckInModal');
    if (form) {
        form.action = '<?= url("reservaciones/check-in/") ?>' + id;
    }

    const totalACobrarElement = document.getElementById('totalACobrar');
    if (totalACobrarElement) {
        totalACobrarElement.textContent = formatMoney(saldoACobrar);
    }

    const resumenTotalElement = document.getElementById('resumenTotal');
    if (resumenTotalElement) {
        resumenTotalElement.textContent = formatMoney(saldoACobrar);
    }

    // Aviso de anticipo: si el saldo es menor que el total, mostrar lo ya pagado.
    const avisoAnticipo = document.getElementById('checkinAnticipoAviso');
    if (avisoAnticipo) {
        const anticipado = TOTAL_RESERVACION_CHECKIN - saldoACobrar;
        if (anticipado > 0.004) {
            const mensajeSaldo = saldoACobrar <= 0.004
                ? '. Saldo cubierto; el check-in no registrara otro cobro.'
                : '. Solo se cobra el saldo.';
            avisoAnticipo.style.display = '';
            avisoAnticipo.innerHTML = '<i class="fas fa-circle-info"></i> Anticipo ya pagado: <strong>' +
                formatMoney(anticipado) + '</strong> de ' + formatMoney(TOTAL_RESERVACION_CHECKIN) +
                mensajeSaldo;
        } else {
            avisoAnticipo.style.display = 'none';
        }
    }

    resetearFormularioPago();
    resetearFacturaCheckIn();
    modal.style.display = 'flex';
    setCheckInWizardStep(1);
    document.body.style.overflow = 'hidden';
}
window.abrirModalCheckIn = abrirModalCheckIn;

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#checkin') {
        abrirModalCheckIn(<?= (int)$rdReservationId ?>, <?= json_encode((float)$rdTotal) ?>);
    }
});

// Función de check-out
function confirmarCheckOut(id) {
    const modalCheckOut = document.getElementById('modalCheckOut');
    if (modalCheckOut) {
        abrirModalCheckOut();
        return;
    }

    console.error('Modal de check-out no encontrado para la reservacion', id);
}
window.confirmarCheckOut = confirmarCheckOut;

// Funciones de vehículos
function cargarVehiculos() {
    const huespedId = <?= json_encode($huesped['id'] ?? 0) ?>;
    const listaVehiculos = document.getElementById('listaVehiculos');
    if (!listaVehiculos) return;

    if (!huespedId) {
        listaVehiculos.innerHTML =
            '<p class="text-center text-gray-500 text-sm">No se pudo cargar la información</p>';
        return;
    }

    fetch('<?= url("api/huespedes/vehiculos/") ?>' + huespedId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar vehículos');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                vehiculosHuesped = data.vehiculos || [];
                mostrarVehiculos();
            } else {
                throw new Error(data.message || 'Error al cargar vehículos');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            listaVehiculos.innerHTML =
                '<p class="text-center text-gray-500 text-sm">Error al cargar vehículos</p>';
        });
}

// Mostrar vehículos con diseño mejorado
function mostrarVehiculos() {
    const container = document.getElementById('listaVehiculos');
    if (!container) return;

    if (vehiculosHuesped.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-car-side text-3xl text-gray-300"></i>
                <p class="text-sm text-gray-500 mt-2">Sin vehículos registrados</p>
            </div>
        `;
        return;
    }

    let html = '<div class="space-y-2">';
    vehiculosHuesped.forEach((vehiculo, index) => {
        const ubicaciones = <?= json_encode($reservationParkingMap, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const ubicacion = ubicaciones[vehiculo.estacionamiento] || { label: 'No especificado', icon: 'fa-question', class: 'ubicacion-fuera' };

        html += `
            <div class="vehiculo-item">
                <div class="vehiculo-info">
                    <div class="vehiculo-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="vehiculo-details">
                        <h4>
                            ${htmlspecialchars(vehiculo.marca || '')} ${htmlspecialchars(vehiculo.modelo || '')}
                            ${vehiculo.placas ? `<span style="font-family: monospace; color: #6B7280; margin-left: 0.5rem;">${htmlspecialchars(vehiculo.placas)}</span>` : ''}
                        </h4>
                        <p>
                            <span class="ubicacion-badge ${ubicacion.class}">
                                <i class="fas ${ubicacion.icon}"></i>
                                ${ubicacion.label}
                            </span>
                            ${vehiculo.color ? `<span style="color: #6B7280;">• ${htmlspecialchars(vehiculo.color)}</span>` : ''}
                        </p>
                    </div>
                </div>
                <div style="display: flex; gap: 0.25rem;">
                    <button onclick="editarVehiculoPorIndex(${index})"
                            class="text-blue-500 hover:text-blue-700 p-1.5 transition-colors"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="confirmarEliminarVehiculo(${vehiculo.id}, '${htmlspecialchars(vehiculo.marca || '')} - ${htmlspecialchars(vehiculo.placas || 'Sin placas')}')"
                            class="text-red-500 hover:text-red-700 p-1.5 transition-colors"
                            title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';

    container.innerHTML = html;
}

// Modal de cancelación
function mostrarFormularioCancelacion() {
    const modalCancelacion = document.getElementById('modalCancelacion');
    if (!modalCancelacion) {
        console.error('Modal de cancelacion no encontrado');
        return;
    }
    modalCancelacion.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalCancelacion() {
    const modalCancelacion = document.getElementById('modalCancelacion');
    if (modalCancelacion) modalCancelacion.style.display = 'none';
    document.body.style.overflow = 'auto';
}
window.mostrarFormularioCancelacion = mostrarFormularioCancelacion;
window.cerrarModalCancelacion = cerrarModalCancelacion;

// Funciones auxiliares
function formatMoney(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function moneyValue(inputOrValue) {
    const rawValue = inputOrValue && typeof inputOrValue === 'object' && 'value' in inputOrValue
        ? inputOrValue.value
        : inputOrValue;
    const parsedValue = parseFloat(String(rawValue || '').replace(/,/g, ''));

    if (Number.isFinite(parsedValue)) {
        return parsedValue;
    }

    if (window.MedisoftMoneyInput) {
        return window.MedisoftMoneyInput.read(inputOrValue);
    }

    return 0;
}
function setMoneyValue(inputOrId, value) {
    const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
    if (!input) {
        return;
    }

    const numericValue = typeof value === 'number'
        ? value
        : parseFloat(String(value || '').replace(/,/g, ''));
    const safeValue = Number.isFinite(numericValue) ? numericValue : 0;

    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.set(input, safeValue);
    } else {
        input.value = safeValue.toFixed(2);
    }
}

function splitMoneyParts(total, count) {
    const safeCount = Math.max(1, parseInt(count, 10) || 1);
    const totalCents = Math.round((parseFloat(total) || 0) * 100);
    const baseCents = Math.floor(totalCents / safeCount);
    const remainder = totalCents - (baseCents * safeCount);

    return Array.from({ length: safeCount }, function(_, index) {
        return (baseCents + (index < remainder ? 1 : 0)) / 100;
    });
}

function sanitizeMoneyForm(form) {
    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.sanitize(form);
    }
}

function cerrarModalCheckIn() {
    const modal = document.getElementById('modalCheckIn');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    setCheckInWizardStep(1);
    resetearFormularioPago();
}

// Funciones de pago
function isSaldoPendienteCheckInActivo() {
    return document.getElementById('check_saldo_pendiente')?.checked === true;
}

function setSaldoPendienteCheckInHidden(activo) {
    const hidden = document.getElementById('permitir_saldo_pendiente');
    if (hidden) hidden.value = activo ? '1' : '0';
}

function setCheckInText(id, text) {
    const element = document.getElementById(id);
    if (element) element.textContent = text;
}

function actualizarVistaSaldoPendienteCheckIn(totalPagado = null) {
    const activo = isSaldoPendienteCheckInActivo();
    const pagoHoy = totalPagado === null ? calcularTotalPagado() : totalPagado;
    const pendiente = Math.max(0, totalReservacion - pagoHoy);
    const preview = document.getElementById('checkinPendingPreview');

    setCheckInText('label_monto_efectivo', activo ? 'Pago de hoy en efectivo' : 'Monto a cobrar en efectivo');
    setCheckInText('label_recibido_efectivo', activo ? 'Dinero recibido hoy' : 'Dinero recibido');
    setCheckInText('label_monto_tarjeta', activo ? 'Pago de hoy con tarjeta' : 'Monto con tarjeta');
    setCheckInText('label_monto_transferencia', activo ? 'Pago de hoy por transferencia' : 'Monto por transferencia');

    setCheckInText('checkinPagoHoy', formatMoney(pagoHoy));
    setCheckInText('checkinQuedaPendiente', formatMoney(pendiente));

    if (preview) {
        preview.hidden = !activo;
        preview.style.display = activo ? 'grid' : 'none';
    }
}

function ajustarEfectivoPendienteDesdeRecibido() {
    if (!isSaldoPendienteCheckInActivo()) return false;

    const checkEfectivo = document.getElementById('check_efectivo');
    const montoInput = document.getElementById('monto_efectivo');
    const recibidoInput = document.getElementById('recibido_efectivo');

    if (!checkEfectivo || !checkEfectivo.checked || !montoInput || !recibidoInput) return false;

    if (document.activeElement === montoInput) {
        montoInput.dataset.saldoPendienteManual = '1';
        return false;
    }

    const recibido = moneyValue(recibidoInput);
    const totalPagadoOtros = calcularTotalPagadoSinEfectivo();
    const maximoEfectivo = Math.max(0, totalReservacion - totalPagadoOtros);
    const montoActual = moneyValue(montoInput);
    const montoNuevo = Math.min(recibido, maximoEfectivo);
    const montoManual = montoInput.dataset.saldoPendienteManual === '1';

    if (document.activeElement === recibidoInput && !montoManual) {
        if (Math.abs(montoActual - montoNuevo) > 0.01) {
            setMoneyValue(montoInput, montoNuevo);
            return true;
        }
        return false;
    }

    if (recibido <= 0 || montoManual) return false;

    const montoCompletoAutomatico = montoActual <= 0.01 || Math.abs(montoActual - maximoEfectivo) < 0.01;

    if (montoNuevo > 0 && recibido < montoActual - 0.01 && montoCompletoAutomatico) {
        setMoneyValue(montoInput, montoNuevo);
        return true;
    }

    if (montoActual <= 0.01 && montoNuevo > 0) {
        setMoneyValue(montoInput, montoNuevo);
        return true;
    }

    return false;
}

function toggleSaldoPendienteCheckIn() {
    const activo = isSaldoPendienteCheckInActivo();
    const montoEfectivo = document.getElementById('monto_efectivo');
    const checkEfectivo = document.getElementById('check_efectivo');
    const recibidoEfectivo = document.getElementById('recibido_efectivo');
    if (montoEfectivo) {
        montoEfectivo.readOnly = !activo;
        delete montoEfectivo.dataset.saldoPendienteManual;
        if (activo && checkEfectivo && checkEfectivo.checked) {
            const recibido = moneyValue(recibidoEfectivo);
            setMoneyValue(montoEfectivo, Math.max(0, Math.min(recibido, totalReservacion)));
        }
    }
    setSaldoPendienteCheckInHidden(activo);
    actualizarVistaSaldoPendienteCheckIn();
    calcularTotales({ preserveCash: activo });
}

function toggleMetodoPago(metodo) {
    const checkbox = document.getElementById('check_' + metodo);
    const panel = document.getElementById('panel_' + metodo);
    const montoInput = document.getElementById('monto_' + metodo);
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox || !panel || !montoInput) {
        console.error('Elementos no encontrados para método:', metodo);
        return;
    }

    if (checkbox.checked) {
        panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');

        if (metodo === 'efectivo') {
            const totalPagadoOtros = calcularTotalPagadoSinEfectivo();
            const montoRestante = Math.max(0, totalReservacion - totalPagadoOtros);
            const recibidoInput = document.getElementById('recibido_efectivo');

            if (isSaldoPendienteCheckInActivo()) {
                delete montoInput.dataset.saldoPendienteManual;
                const recibido = moneyValue(recibidoInput);
                setMoneyValue(montoInput, Math.min(recibido, montoRestante));
            } else if (moneyValue(montoInput) <= 0) {
                setMoneyValue(montoInput, montoRestante);
            }
            montoInput.readOnly = !isSaldoPendienteCheckInActivo();

            if (recibidoInput) {
                recibidoInput.value = '';
                if (montoRestante > 0) {
                    recibidoInput.focus();
                }
            }
        } else {
            montoInput.focus();
        }
    } else {
        panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
        montoInput.value = '';

        if (metodo === 'efectivo') {
            const recibidoInput = document.getElementById('recibido_efectivo');
            const cambioSpan = document.getElementById('cambio_efectivo');

            if (recibidoInput) recibidoInput.value = '';
            if (cambioSpan) {
                cambioSpan.textContent = '$0.00';
                cambioSpan.classList.remove('text-red-600');
            }
        }
    }

    calcularTotales();

    // Actualizar aviso de factura interna
    const facturaNo = document.getElementById('factura_no');
    if (facturaNo && facturaNo.checked) {
        mostrarInfoFacturaInterna();
    }
}

// Validación del formulario
function setCheckInPaymentChecked(metodo, checked, shouldRecalculate = true) {
    const checkbox = document.getElementById('check_' + metodo);
    const panel = document.getElementById('panel_' + metodo);
    const montoInput = document.getElementById('monto_' + metodo);
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox) {
        return false;
    }

    const changed = checkbox.checked !== checked;
    if (changed) {
        checkbox.checked = checked;

        if (shouldRecalculate) {
            toggleMetodoPago(metodo);
            return true;
        }
    }

    if (checked) {
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');
        if (metodo === 'efectivo' && montoInput) {
            montoInput.readOnly = !isSaldoPendienteCheckInActivo();
        }
    } else {
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
        if (montoInput) montoInput.value = '';

        if (metodo === 'efectivo') {
            const recibidoInput = document.getElementById('recibido_efectivo');
            const cambioSpan = document.getElementById('cambio_efectivo');
            if (recibidoInput) recibidoInput.value = '';
            if (cambioSpan) {
                cambioSpan.textContent = '$0.00';
                cambioSpan.classList.remove('text-red-600');
            }
        }
    }

    return true;
}

function getSelectedCheckInPaymentMethods() {
    return ['efectivo', 'tarjeta', 'transferencia'].filter(function(metodo) {
        const checkbox = document.getElementById('check_' + metodo);
        return checkbox && checkbox.checked;
    });
}

function aplicarPagoRapido(metodo) {
    if (!['efectivo', 'tarjeta', 'transferencia'].includes(metodo)) {
        return;
    }

    resetearFormularioPago();
    setCheckInPaymentChecked(metodo, true, false);
    setMoneyValue('monto_' + metodo, totalReservacion);

    if (metodo === 'efectivo') {
        setMoneyValue('recibido_efectivo', totalReservacion);
    }

    calcularTotales();

    if (metodo === 'efectivo') {
        calcularCambio();
    }

    mostrarMensaje('Pago exacto aplicado.', 'success');
}

function marcarEfectivoExacto() {
    setCheckInPaymentChecked('efectivo', true);
    calcularTotales();

    const montoEfectivo = moneyValue(document.getElementById('monto_efectivo'));
    setMoneyValue('recibido_efectivo', montoEfectivo);
    calcularCambio();
}

function dividirPagoRapido() {
    resetearFormularioPago();

    const metodos = ['efectivo', 'tarjeta'];
    const partes = splitMoneyParts(totalReservacion, 2);

    metodos.forEach(function(metodo) {
        setCheckInPaymentChecked(metodo, true, false);
    });

    const aplicarMitadYMitad = function() {
        setCheckInPaymentChecked('efectivo', true, false);
        setCheckInPaymentChecked('tarjeta', true, false);
        setMoneyValue('monto_efectivo', partes[0]);
        setMoneyValue('recibido_efectivo', partes[0]);
        setMoneyValue('monto_tarjeta', partes[1]);
        calcularTotales({ preserveCash: true });
        calcularCambio();
    };

    aplicarMitadYMitad();

    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(aplicarMitadYMitad);
    } else {
        setTimeout(aplicarMitadYMitad, 0);
    }

    mostrarMensaje('Pago dividido entre efectivo y tarjeta.', 'success');
}

function dividirPagoEfectivoTransferencia() {
    resetearFormularioPago();

    const partes = splitMoneyParts(totalReservacion, 2);

    setCheckInPaymentChecked('efectivo', true, false);
    setCheckInPaymentChecked('transferencia', true, false);
    setMoneyValue('monto_efectivo', partes[0]);
    setMoneyValue('recibido_efectivo', partes[0]);
    setMoneyValue('monto_transferencia', partes[1]);

    calcularTotales({ preserveCash: true });
    calcularCambio();
    mostrarMensaje('Pago dividido entre efectivo y transferencia.', 'success');
}

const formCheckInModal = document.getElementById('formCheckInModal');
if (formCheckInModal) {
    formCheckInModal.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validarFacturaCheckIn()) {
            setCheckInWizardStep(3);
            mostrarMensaje('Debe indicar si el cliente requiere factura', 'warning');
            return;
        }

        if (!validarPagoCheckInWizard()) {
            setCheckInWizardStep(2);
            validarPagoCheckInWizard();
            return;
        }

        sanitizeMoneyForm(this);
        this.submit();
    });
}

function calcularTotalPagadoSinEfectivo() {
    let total = 0;
    ['tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);

        if (checkbox && checkbox.checked && montoInput) {
            total += moneyValue(montoInput);
        }
    });
    return total;
}

function actualizarResumenMetodoPago() {
    const resumenMetodo = document.getElementById('resumenMetodoPago');
    if (!resumenMetodo) return;

    const labels = {
        efectivo: 'Efectivo',
        tarjeta: 'Tarjeta',
        transferencia: 'Transferencia'
    };
    const metodos = getSelectedCheckInPaymentMethods();
    resumenMetodo.textContent = metodos.length ? metodos.map(metodo => labels[metodo] || metodo).join(' + ') : 'Sin seleccionar';
}

function calcularTotales(options = {}) {
    const preserveCash = options && options.preserveCash === true;
    const skipCashChange = options && options.skipCashChange === true;
    const permitirPendiente = isSaldoPendienteCheckInActivo();
    ajustarEfectivoPendienteDesdeRecibido();
    let totalPagado = 0;

    const totalOtros = calcularTotalPagadoSinEfectivo();
    totalPagado = totalOtros;

    const checkEfectivo = document.getElementById('check_efectivo');
    const montoEfectivoInput = document.getElementById('monto_efectivo');

    if (checkEfectivo && checkEfectivo.checked && montoEfectivoInput) {
        montoEfectivoInput.readOnly = !permitirPendiente;
        if (permitirPendiente || preserveCash) {
            totalPagado += moneyValue(montoEfectivoInput);
        } else {
            const montoRestante = Math.max(0, totalReservacion - totalOtros);
            setMoneyValue(montoEfectivoInput, montoRestante);
            totalPagado += montoRestante;
        }
    }

    const resumenPagadoElement = document.getElementById('resumenPagado');
    if (resumenPagadoElement) {
        resumenPagadoElement.textContent = formatMoney(totalPagado);
    }

    const totalPagadoFinal = calcularTotalPagado();
    const diferencia = totalReservacion - totalPagadoFinal;
    actualizarVistaSaldoPendienteCheckIn(totalPagadoFinal);

    const divRestante = document.getElementById('divRestante');
    const divCambio = document.getElementById('divCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');
    const resumenRestante = document.getElementById('resumenRestante');
    const haySaldoPendiente = diferencia > 0.01;

    if (divRestante) divRestante.style.display = 'none';
    if (divCambio) divCambio.style.display = 'none';
    setSaldoPendienteCheckInHidden(permitirPendiente && haySaldoPendiente);

    if (checkEfectivo && checkEfectivo.checked && !skipCashChange) {
        calcularCambio();
    }

    const efectivoInsuficiente = checkEfectivo && checkEfectivo.checked
        && moneyValue(montoEfectivoInput) > 0
        && moneyValue(document.getElementById('recibido_efectivo')) < moneyValue(montoEfectivoInput);

    if (efectivoInsuficiente) {
        mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    } else if (Math.abs(diferencia) < 0.01) {
        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
    } else if (diferencia > 0.01) {
        if (divRestante) {
            divRestante.style.display = 'flex';
            if (resumenRestante) {
                resumenRestante.textContent = formatMoney(diferencia);
            }
        }

        if (permitirPendiente && totalPagadoFinal > 0) {
            mostrarMensaje('Se hara check-in y el saldo restante quedara en Cuentas por cobrar.', 'info');
            if (btnConfirmar) btnConfirmar.disabled = false;
        } else {
            mostrarMensaje('Falta completar el pago. Si el huesped pagara despues, activa Dejar saldo pendiente.', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    } else if (diferencia < -0.01) {
        mostrarMensaje('El monto total excede el precio de la reservacion', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    }

    actualizarResumenMetodoPago();
    actualizarResultadoFacturaCheckIn();
}

function calcularCambio() {
    const checkEfectivo = document.getElementById('check_efectivo');
    if (!checkEfectivo || !checkEfectivo.checked) return;

    const montoPagarInput = document.getElementById('monto_efectivo');
    const montoRecibidoInput = document.getElementById('recibido_efectivo');
    const cambioSpan = document.getElementById('cambio_efectivo');

    if (!montoPagarInput || !montoRecibidoInput || !cambioSpan) return;

    const montoAjustadoPorPendiente = ajustarEfectivoPendienteDesdeRecibido();
    const montoPagar = moneyValue(montoPagarInput);
    const montoRecibido = moneyValue(montoRecibidoInput);

    const divCambio = document.getElementById('divCambio');
    const resumenCambio = document.getElementById('resumenCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');

    if (montoPagar === 0) {
        cambioSpan.textContent = '$0.00';
        cambioSpan.classList.remove('text-red-600');
        if (divCambio) divCambio.style.display = 'none';

        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
        return;
    }

    if (montoRecibido > 0) {
        const cambio = montoRecibido - montoPagar;

        if (cambio >= 0) {
            cambioSpan.textContent = formatMoney(cambio);
            cambioSpan.classList.remove('text-red-600');

            if (divCambio && cambio > 0) {
                divCambio.style.display = 'flex';
                if (resumenCambio) {
                    resumenCambio.textContent = formatMoney(cambio);
                }
            } else if (divCambio) {
                divCambio.style.display = 'none';
            }

            ocultarMensaje();
            if (btnConfirmar) btnConfirmar.disabled = false;
        } else {
            cambioSpan.textContent = 'Monto insuficiente';
            cambioSpan.classList.add('text-red-600');
            if (divCambio) divCambio.style.display = 'none';

            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    } else {
        cambioSpan.textContent = '$0.00';
        cambioSpan.classList.remove('text-red-600');
        if (divCambio) divCambio.style.display = 'none';

        if (montoPagar > 0) {
            mostrarMensaje('Ingrese el monto recibido en efectivo', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    }

    if (montoAjustadoPorPendiente) {
        calcularTotales({ preserveCash: true, skipCashChange: true });
    }
}

function calcularTotalPagado() {
    let total = 0;
    ['efectivo', 'tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);

        if (checkbox && checkbox.checked && montoInput) {
            total += moneyValue(montoInput);
        }
    });
    return total;
}

function mostrarMensaje(mensaje, tipo) {
    // Avisos de validación → toast flotante (no empuja el layout del check-in).
    if (window.msToast) { window.msToast(tipo || 'info', null, mensaje); return; }

    // Fallback al aviso inline si el toast no está disponible.
    const div = document.getElementById('mensajeValidacion');
    if (!div) return;
    div.className = 'rv-checkin-message';
    if (tipo === 'warning') div.classList.add('is-warning');
    if (tipo === 'info') div.classList.add('is-info');
    if (tipo === 'success') div.classList.add('is-success');
    div.textContent = mensaje;
    div.classList.remove('hidden');
}

function ocultarMensaje() {
    const div = document.getElementById('mensajeValidacion');
    if (div) {
        div.className = 'rv-checkin-message hidden';
    }
}

function resetearFormularioPago() {
    ['efectivo', 'tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const panel = document.getElementById('panel_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);
        const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

        if (checkbox) checkbox.checked = false;
        if (panel) panel.classList.add('hidden');
        if (montoInput) {
            montoInput.value = '';
            delete montoInput.dataset.saldoPendienteManual;
        }
        if (metodo === 'efectivo' && montoInput) montoInput.readOnly = true;
        if (card) card.classList.remove('is-open');
    });

    const saldoPendienteCheck = document.getElementById('check_saldo_pendiente');
    if (saldoPendienteCheck) saldoPendienteCheck.checked = false;
    setSaldoPendienteCheckInHidden(false);
    actualizarVistaSaldoPendienteCheckIn(0);

    const recibidoEfectivo = document.getElementById('recibido_efectivo');
    if (recibidoEfectivo) recibidoEfectivo.value = '';

    const cambioEfectivo = document.getElementById('cambio_efectivo');
    if (cambioEfectivo) {
        cambioEfectivo.textContent = '$0.00';
        cambioEfectivo.classList.remove('text-red-600');
    }

    const refTarjeta = document.querySelector('input[name="referencia_tarjeta"]');
    if (refTarjeta) refTarjeta.value = '';

    const refTransferencia = document.querySelector('input[name="referencia_transferencia"]');
    if (refTransferencia) refTransferencia.value = '';

    document.querySelectorAll('input[name="tipo_tarjeta"]').forEach(radio => { radio.checked = false; });
    ['label_credito', 'label_debito'].forEach(id => {
        const label = document.getElementById(id);
        if (!label) return;
        label.style.borderColor = '';
        label.style.background = '';
    });

    const resumenPagado = document.getElementById('resumenPagado');
    if (resumenPagado) resumenPagado.textContent = '$0.00';

    const divRestante = document.getElementById('divRestante');
    if (divRestante) divRestante.style.display = 'none';

    const divCambio = document.getElementById('divCambio');
    if (divCambio) divCambio.style.display = 'none';

    ocultarMensaje();

    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');
    if (btnConfirmar) btnConfirmar.disabled = true;

    actualizarResumenMetodoPago();
    actualizarResultadoFacturaCheckIn();
}

// Cerrar modal al hacer clic fuera
const modalCheckInClick = document.getElementById('modalCheckIn');
if (modalCheckInClick) {
    modalCheckInClick.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalCheckIn();
        }
    });
}

const modalCancelacionClick = document.getElementById('modalCancelacion');
if (modalCancelacionClick) {
    modalCancelacionClick.addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModalCancelacion();
        }
    });
}

// Función helper para escapar HTML
function htmlspecialchars(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.toString().replace(/[&<>"']/g, m => map[m]) : '';
}

function setVehiculoStatus(message, type = 'info') {
    const status = document.getElementById('vehiculoStatus');
    if (!status) return;

    const allowedType = ['info', 'warning', 'error', 'success'].includes(type) ? type : 'info';
    status.className = 'rdv3-vehicle-status is-visible is-' + allowedType;
    status.textContent = message;
}

function clearVehiculoStatus() {
    const status = document.getElementById('vehiculoStatus');
    if (!status) return;

    status.className = 'rdv3-vehicle-status';
    status.textContent = '';
}

function resetVehiculoEliminarPendiente() {
    vehiculoEliminacionPendiente = null;
    if (vehiculoEliminacionTimer) {
        clearTimeout(vehiculoEliminacionTimer);
        vehiculoEliminacionTimer = null;
    }
}

// Función de agregar vehículo (implementar según tu sistema)
function abrirModalAgregarVehiculo() {
    // Redirigir a la página de huésped para agregar vehículo
    const huespedId = <?= json_encode($huesped['id'] ?? 0) ?>;
    if (huespedId) {
        window.location.href = '<?= url("huespedes/") ?>' + huespedId + '#vehiculos';
        return;
    }

    setVehiculoStatus('No se encontro el huesped de esta reservacion para vincular un vehiculo.', 'error');
}

// Función de editar vehículo
function editarVehiculoPorIndex(index) {
    const vehiculo = vehiculosHuesped[index];
    if (!vehiculo) return;

    // Similar a agregar, redirigir al perfil del huésped
    abrirModalAgregarVehiculo();
}

// Función de eliminar vehículo
function confirmarEliminarVehiculo(id, descripcion) {
    if (vehiculoEliminacionPendiente === id) {
        resetVehiculoEliminarPendiente();
        eliminarVehiculo(id);
        return;
    }

    resetVehiculoEliminarPendiente();
    vehiculoEliminacionPendiente = id;
    setVehiculoStatus('Para eliminar ' + descripcion + ', presiona eliminar otra vez.', 'warning');
    vehiculoEliminacionTimer = setTimeout(resetVehiculoEliminarPendiente, 7000);
}

function eliminarVehiculo(id) {
    fetch('<?= url("api/huespedes/vehiculos/") ?>' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recargar vehículos
            cargarVehiculos();
            setVehiculoStatus('Vehiculo eliminado correctamente.', 'success');
        } else {
            throw new Error(data.message || 'Error al eliminar vehículo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setVehiculoStatus(error.message || 'No se pudo eliminar el vehiculo.', 'error');
    });
}

// Prevenir envío accidental de formularios
document.addEventListener('keypress', function(e) {
    if (e.keyCode === 13 && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        return false;
    }
});

// Funciones para manejo de notas
function setNotaStatus(message, type = 'error') {
    const status = document.getElementById('notaStatus');
    if (!status) return;

    status.className = 'rdv3-note-status is-visible ' + (type === 'success' ? 'is-success' : 'is-error');
    status.textContent = message;
}

function clearNotaStatus() {
    const status = document.getElementById('notaStatus');
    if (!status) return;

    status.className = 'rdv3-note-status';
    status.textContent = '';
}

function agregarNota(trigger) {
    const textarea = document.getElementById('nuevaNota');
    if (!textarea) {
        return;
    }

    const nota = textarea.value.trim();

    if (!nota) {
        setNotaStatus('Escribe una nota antes de guardarla.');
        textarea.focus();
        return;
    }

    clearNotaStatus();

    const btn = trigger || document.getElementById('btnAgregarNota');
    if (btn) {
        btn.disabled = true;
    }

    const formData = new FormData();
    formData.append('reservacion_id', <?= $reservacion['id'] ?>);
    formData.append('nota', nota);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url("reservaciones/agregar-nota") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('No se pudo guardar la nota.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            textarea.value = '';
            actualizarListaNotas();
            setNotaStatus('Nota agregada correctamente.', 'success');
        } else {
            throw new Error(data.message || 'Error al agregar nota');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        setNotaStatus(error.message || 'No se pudo guardar la nota.');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
        }
    });
}

function actualizarListaNotas() {
    const container = document.getElementById('listaNotas');
    if (!container) {
        return;
    }

    fetch('<?= url("reservaciones/obtener-notas?reservacion_id=") ?><?= $reservacion['id'] ?>', {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notas = Array.isArray(data.notas) ? data.notas : [];

            if (notas.length === 0) {
                container.innerHTML = '<div class="rdv3-empty">No hay notas aun.</div>';
            } else {
                let html = '';
                notas.forEach(nota => {
                    const fechaFormateada = formatearFechaNota(nota.created_at);
                    const usuario = nota.usuario_nombre || 'Recepcion';
                    const texto = nota.nota || '';

                    html += `
                        <div class="rdv3-note nota-item">
                            <small>${escapeHtml(usuario)} &middot; ${escapeHtml(fechaFormateada)}</small>
                            ${escapeHtml(texto).replace(/\n/g, '<br>')}
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            document.querySelectorAll('.rdv3-count-badge').forEach(badge => {
                badge.textContent = notas.length;
            });
        }
    })
    .catch(error => {
        console.error('Error al actualizar notas:', error);
    });
}

function formatearFechaNota(value) {
    if (!value) {
        return '-';
    }

    const fecha = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(fecha.getTime())) {
        return String(value);
    }

    return fecha.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    text = String(text ?? '');
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Auto-actualizar notas cada 30 segundos
setInterval(actualizarListaNotas, 30000);

// Permitir enviar nota con Ctrl+Enter
const nuevaNotaTextarea = document.getElementById('nuevaNota');
if (nuevaNotaTextarea) {
    nuevaNotaTextarea.addEventListener('input', clearNotaStatus);
    nuevaNotaTextarea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            agregarNota(document.getElementById('btnAgregarNota'));
        }
    });
}

let checkoutSubmitConfirmado = false;

function resetCheckoutConfirmacion() {
    checkoutSubmitConfirmado = false;
}

function abrirModalCheckOut() {
    resetCheckoutConfirmacion();
    const modalCheckOut = document.getElementById('modalCheckOut');
    if (!modalCheckOut) {
        console.error('Modal de check-out no encontrado');
        return;
    }
    modalCheckOut.classList.remove('hidden');
    actualizarSeleccion();
}

function cerrarModalCheckOut() {
    resetCheckoutConfirmacion();
    const modalCheckOut = document.getElementById('modalCheckOut');
    if (modalCheckOut) modalCheckOut.classList.add('hidden');
}
window.abrirModalCheckOut = abrirModalCheckOut;
window.cerrarModalCheckOut = cerrarModalCheckOut;

function toggleAsignarLimpieza() {
    const box = document.getElementById('asignarLimpiezaBox');
    const icon = document.getElementById('iconAsignarLimpieza');
    if (!box) return;
    box.classList.toggle('hidden');
    if (icon) icon.style.transform = box.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

function seleccionarTodas(seleccionar) {
    resetCheckoutConfirmacion();
    const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]');
    checkboxes.forEach(cb => cb.checked = seleccionar);
    actualizarSeleccion();
}

function actualizarSeleccion() {
    resetCheckoutConfirmacion();
    const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]');
    const seleccionadas = Array.from(checkboxes).filter(cb => cb.checked);
    const total = checkboxes.length;
    const btnConfirmar = document.getElementById('btnConfirmarCheckOut');
    const mensaje = document.getElementById('mensajeSeleccion');
    const textoMensaje = document.getElementById('textoMensaje');

    // Habilitar/deshabilitar botón
    if (btnConfirmar) btnConfirmar.disabled = seleccionadas.length === 0;

    // Mostrar mensaje informativo
    if (seleccionadas.length > 0) {
        if (!mensaje || !textoMensaje) return;
        mensaje.classList.remove('hidden');

        if (seleccionadas.length === total) {
            mensaje.className = 'p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700';
            textoMensaje.innerHTML = '<strong>Check-out completo:</strong> Se liberarán todas las habitaciones y la reservación se marcará como finalizada.';
        } else {
            mensaje.className = 'p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700';
            textoMensaje.innerHTML = `<strong>Check-out parcial:</strong> Se liberarán ${seleccionadas.length} de ${total} habitaciones. La reservación permanecerá activa con las habitaciones restantes.`;
        }
    } else {
        if (mensaje) mensaje.classList.add('hidden');
    }
}

// Confirmar antes de enviar sin obligar a pulsar el mismo boton dos veces.
const formCheckOut = document.getElementById('formCheckOut');
if (formCheckOut) {
    formCheckOut.addEventListener('submit', function(e) {
        if (checkoutSubmitConfirmado) {
            return;
        }

        e.preventDefault();

        const form = this;
        const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]:checked');
        const total = document.querySelectorAll('input[name="habitaciones[]"]').length;
        const mensaje = document.getElementById('mensajeSeleccion');
        const textoMensaje = document.getElementById('textoMensaje');
        const btnConfirmar = document.getElementById('btnConfirmarCheckOut');
        const modalCheckOut = document.getElementById('modalCheckOut');

        if (checkboxes.length === 0) {
            resetCheckoutConfirmacion();
            if (mensaje && textoMensaje) {
                mensaje.className = 'p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700';
                mensaje.classList.remove('hidden');
                textoMensaje.innerHTML = '<strong>Seleccion requerida:</strong> elige al menos una habitacion para hacer check-out.';
            }
            return;
        }

        const esCompleto = checkboxes.length === total;
        const titulo = esCompleto ? 'Confirmar check-out completo' : 'Confirmar check-out parcial';
        const resumen = esCompleto
            ? 'Se liberaran todas las habitaciones y la reservacion se marcara como finalizada.'
            : `Se liberaran ${checkboxes.length} de ${total} habitaciones. La reservacion seguira activa con las habitaciones restantes.`;
        const confirmarEnvio = function() {
            checkoutSubmitConfirmado = true;
            if (btnConfirmar) {
                btnConfirmar.disabled = true;
                btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Procesando...';
            }
            form.submit();
        };

        if (typeof Swal === 'undefined') {
            if (window.confirm(titulo + '. ' + resumen)) {
                confirmarEnvio();
            }
            return;
        }

        if (modalCheckOut) {
            modalCheckOut.classList.add('hidden');
        }

        Swal.fire({
            title: titulo,
            html: `
                <div class="rv-checkout-confirm">
                    <p class="rv-checkout-confirm__lead">${resumen}</p>
                    <div class="rv-checkout-confirm__panel">
                        <strong>Antes de confirmar</strong>
                        <span>Verifica que el huesped ya desocupo y que las habitaciones seleccionadas son correctas.</span>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-right-from-bracket"></i> Registrar check-out',
            cancelButtonText: 'Volver',
            confirmButtonColor: '#EA580C',
            cancelButtonColor: '#6B7280',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                container: 'rv-checkout-confirm-container',
                popup: 'rv-checkout-confirm-swal',
                confirmButton: 'rv-checkout-confirm-action',
                cancelButton: 'rv-checkout-confirm-cancel'
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                confirmarEnvio();
                return;
            }

            if (modalCheckOut) {
                modalCheckOut.classList.remove('hidden');
                actualizarSeleccion();
            }
        });
    });
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (typeof Swal !== 'undefined' && typeof Swal.isVisible === 'function' && Swal.isVisible()) {
            return;
        }
        cerrarModalCheckOut();
    }
});


let reservacionTardioData = {};
let metodosSeleccionadosTardio = new Set(['sin_pago']);
let confirmacionExpressTardioLista = false;

function abrirModalCheckInTardio(id, huesped, habitaciones, fechaEntrada, fechaSalida, total, tipo, diasRetraso) {
    const modalTardio = document.getElementById('modalCheckInTardio');
    if (!modalTardio) {
        console.error('Modal de check-in tardio no encontrado');
        return;
    }

    if (tipo === 'express') {
        tipo = 'normal_tardio';
    }

    reservacionTardioData = {
        id: id,
        huesped: huesped,
        habitaciones: habitaciones,
        fechaEntrada: fechaEntrada,
        fechaSalida: fechaSalida,
        total: parseFloat(total),
        tipo: tipo, // 'normal_tardio' o 'express'
        diasRetraso: diasRetraso
    };

    if (modalTardio) {
        modalTardio.dataset.mode = tipo;
    }

    // Configurar el formulario
    const form = document.getElementById('formCheckInTardio');
    if (form) {
        form.action = '<?= url("reservaciones/check-in/") ?>' + id;
    }

    const tipoTardio = document.getElementById('tipo_tardio');
    if (tipoTardio) tipoTardio.value = tipo;

    // Llenar información
    const huespedTardio = document.getElementById('huesped_tardio');
    const habitacionesTardio = document.getElementById('habitaciones_tardio');
    const entradaTardio = document.getElementById('fecha_entrada_tardio');
    const salidaTardio = document.getElementById('fecha_salida_tardio');
    const totalTardio = document.getElementById('totalACobrarTardio');
    if (huespedTardio) huespedTardio.textContent = huesped;
    if (habitacionesTardio) habitacionesTardio.textContent = habitaciones;
    if (entradaTardio) entradaTardio.textContent = fechaEntrada;
    if (salidaTardio) salidaTardio.textContent = fechaSalida;
    if (totalTardio) totalTardio.textContent = formatMoney(total);

    // Configurar según tipo
    const titulo = document.getElementById('tituloModalTardio');
    const alerta = document.getElementById('alertaTardio');
    const btnConfirmar = document.getElementById('btnConfirmarTardio');
    const campoHora = document.getElementById('campo_hora_entrada');
    const notaPagoOpcional = document.getElementById('nota_pago_opcional');
    const checkSinPago = document.getElementById('check_sin_pago');
    if (!titulo || !alerta || !btnConfirmar || !campoHora || !notaPagoOpcional || !checkSinPago) {
        console.error('Elementos del modal de check-in tardio no disponibles');
        return;
    }

    // Mostrar info compacta en el subtitle del header
    const headerSub = document.querySelector('#modalCheckInTardio .xpm-head-sub');
    if (headerSub) {
        headerSub.textContent = huesped + ' · Hab. ' + habitaciones + ' · ' + fechaEntrada + ' → ' + fechaSalida;
    }

    titulo.textContent = 'Check-in Tardío';
    const ico = document.getElementById('xpHeaderIcon');
    if (ico) { ico.className = 'xpm-head-ico is-late'; ico.innerHTML = '<i class="fas fa-clock"></i>'; }
    btnConfirmar.innerHTML = '<i class="fas fa-check"></i> Confirmar Check-in';
    btnConfirmar.className = 'xpm-fbtn xpm-fbtn-ok rv-btn-confirm';
    campoHora.style.display = '';
    notaPagoOpcional.style.display = '';
    checkSinPago.closest('.xpm-pay-opt').style.display = '';
    alerta.style.display = 'block';
    alerta.innerHTML = `<i class="fas fa-clock"></i> Llegada con <strong>${diasRetraso} día(s) de retraso</strong>. Entrada programada: ${fechaEntrada}.`;

    // Resetear pagos
    metodosSeleccionadosTardio = new Set(['sin_pago']);
    resetearPagosTardio();
    resetearFacturaTardio();
    resetearConfirmacionTardio();
    ocultarMensajeTardio();

    // Mostrar modal
    if (modalTardio) {
        modalTardio.style.display = 'flex';
    }
    document.body.style.overflow = 'hidden';
}

function cerrarModalCheckInTardio() {
    const modalTardio = document.getElementById('modalCheckInTardio');
    if (modalTardio) {
        modalTardio.style.display = 'none';
        modalTardio.removeAttribute('data-mode');
    }
    document.body.style.overflow = 'auto';
    resetearPagosTardio();
    resetearConfirmacionTardio();
    ocultarMensajeTardio();
}
window.abrirModalCheckInTardio = abrirModalCheckInTardio;
window.cerrarModalCheckInTardio = cerrarModalCheckInTardio;

function mostrarMensajeTardio(mensaje, tipo = 'error') {
    const div = document.getElementById('mensajeValidacionTardio');
    if (!div) {
        mostrarMensaje(mensaje, tipo);
        return;
    }

    div.className = 'rv-checkin-message';
    if (tipo === 'warning') div.classList.add('is-warning');
    if (tipo === 'info') div.classList.add('is-info');
    if (tipo === 'success') div.classList.add('is-success');
    div.textContent = mensaje;
    div.classList.remove('hidden');
    div.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function ocultarMensajeTardio() {
    const div = document.getElementById('mensajeValidacionTardio');
    if (div) {
        div.className = 'rv-checkin-message hidden';
        div.textContent = '';
    }
}

function resetearConfirmacionTardio() {
    confirmacionExpressTardioLista = false;
}

function toggleMetodoPagoTardio(metodo) {
    resetearConfirmacionTardio();
    ocultarMensajeTardio();

    const checkbox = document.getElementById('check_' + metodo + (metodo === 'sin_pago' ? '' : '_tardio'));
    const panel = document.getElementById('panel_' + metodo + '_tardio');
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox) {
        return;
    }

    if (checkbox.checked) {
        // Si se selecciona un método de pago, desmarcar "sin pago"
        if (metodo !== 'sin_pago') {
            const sinPagoCheck = document.getElementById('check_sin_pago');
            if (sinPagoCheck) {
                sinPagoCheck.checked = false;
                metodosSeleccionadosTardio.delete('sin_pago');
                const sinPagoCard = sinPagoCheck.closest('.rv-pay-option, .metodo-pago-item');
                if (sinPagoCard) sinPagoCard.classList.remove('is-open');
            }
        } else {
            // Si se selecciona "sin pago", desmarcar todos los demás
            ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
                const check = document.getElementById('check_' + m + '_tardio');
                if (check) check.checked = false;
                const methodCard = check?.closest('.rv-pay-option, .metodo-pago-item');
                if (methodCard) methodCard.classList.remove('is-open');
                const p = document.getElementById('panel_' + m + '_tardio');
                if (p) p.classList.add('hidden');
            });
            metodosSeleccionadosTardio = new Set(['sin_pago']);
        }

        metodosSeleccionadosTardio.add(metodo);
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');

        if (metodo === 'efectivo') {
            calcularMontoEfectivoTardio();
        }
    } else {
        metodosSeleccionadosTardio.delete(metodo);
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
    }

    mostrarResumenTardio();
    calcularTotalesTardio();
    // Actualizar aviso de factura interna
    const facturaNoTardio = document.getElementById('factura_no_tardio');
    if (facturaNoTardio && facturaNoTardio.checked) {
        mostrarInfoFacturaInternaTardio();
    }
}

function setTardioPaymentChecked(metodo, checked, shouldRecalculate = true) {
    const checkbox = document.getElementById('check_' + metodo + (metodo === 'sin_pago' ? '' : '_tardio'));
    const panel = document.getElementById('panel_' + metodo + '_tardio');
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox) {
        return false;
    }

    const changed = checkbox.checked !== checked;
    if (changed) {
        checkbox.checked = checked;

        if (shouldRecalculate) {
            toggleMetodoPagoTardio(metodo);
            return true;
        }
    }

    if (checked) {
        if (metodo !== 'sin_pago') {
            const sinPagoCheck = document.getElementById('check_sin_pago');
            if (sinPagoCheck) {
                sinPagoCheck.checked = false;
                const sinPagoCard = sinPagoCheck.closest('.rv-pay-option, .metodo-pago-item');
                if (sinPagoCard) sinPagoCard.classList.remove('is-open');
            }
            metodosSeleccionadosTardio.delete('sin_pago');
        } else {
            ['efectivo', 'tarjeta', 'transferencia'].forEach(function(m) {
                const methodCheck = document.getElementById('check_' + m + '_tardio');
                const methodPanel = document.getElementById('panel_' + m + '_tardio');
                const methodCard = methodCheck?.closest('.rv-pay-option, .metodo-pago-item');
                const methodAmount = document.getElementById('monto_' + m + '_tardio');

                if (methodCheck) methodCheck.checked = false;
                if (methodPanel) methodPanel.classList.add('hidden');
                if (methodCard) methodCard.classList.remove('is-open');
                if (methodAmount) methodAmount.value = '';
                metodosSeleccionadosTardio.delete(m);
            });
        }

        metodosSeleccionadosTardio.add(metodo);
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');
    } else {
        metodosSeleccionadosTardio.delete(metodo);
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');

        if (metodo !== 'sin_pago') {
            const montoInput = document.getElementById('monto_' + metodo + '_tardio');
            if (montoInput) montoInput.value = '';
            if (metodo === 'efectivo') {
                const recibidoInput = document.getElementById('recibido_efectivo_tardio');
                const cambioSpan = document.getElementById('cambio_efectivo_tardio');
                if (recibidoInput) recibidoInput.value = '';
                if (cambioSpan) cambioSpan.textContent = '$0.00';
            }
        }
    }

    return true;
}

function getSelectedTardioPaymentMethods() {
    return ['efectivo', 'tarjeta', 'transferencia'].filter(function(metodo) {
        return metodosSeleccionadosTardio.has(metodo);
    });
}

function aplicarPagoRapidoTardio(metodo) {
    if (!['efectivo', 'tarjeta', 'transferencia'].includes(metodo)) {
        return;
    }

    resetearPagosTardio();
    setTardioPaymentChecked(metodo, true, false);
    setMoneyValue('monto_' + metodo + '_tardio', reservacionTardioData.total);

    if (metodo === 'efectivo') {
        setMoneyValue('recibido_efectivo_tardio', reservacionTardioData.total);
    }

    mostrarResumenTardio();
    calcularTotalesTardio();

    if (metodo === 'efectivo') {
        calcularCambioTardio();
    }
}

function marcarEfectivoExactoTardio() {
    setTardioPaymentChecked('efectivo', true);
    calcularTotalesTardio();

    const montoEfectivo = moneyValue(document.getElementById('monto_efectivo_tardio'));
    setMoneyValue('recibido_efectivo_tardio', montoEfectivo);
    calcularCambioTardio();
}

function dividirPagoRapidoTardio() {
    resetearPagosTardio();

    const metodos = ['efectivo', 'tarjeta'];
    const partes = splitMoneyParts(reservacionTardioData.total, 2);

    metodos.forEach(function(metodo) {
        setTardioPaymentChecked(metodo, true, false);
    });

    const aplicarMitadYMitadTardio = function() {
        setTardioPaymentChecked('efectivo', true, false);
        setTardioPaymentChecked('tarjeta', true, false);
        setMoneyValue('monto_efectivo_tardio', partes[0]);
        setMoneyValue('recibido_efectivo_tardio', partes[0]);
        setMoneyValue('monto_tarjeta_tardio', partes[1]);
        mostrarResumenTardio();
        calcularTotalesTardio({ preserveCash: true });
        calcularCambioTardio({ preserveCash: true });
    };

    aplicarMitadYMitadTardio();

    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(aplicarMitadYMitadTardio);
    } else {
        setTimeout(aplicarMitadYMitadTardio, 0);
    }
}

function dividirPagoEfectivoTransferenciaTardio() {
    resetearPagosTardio();

    const partes = splitMoneyParts(reservacionTardioData.total, 2);

    setTardioPaymentChecked('efectivo', true, false);
    setTardioPaymentChecked('transferencia', true, false);
    setMoneyValue('monto_efectivo_tardio', partes[0]);
    setMoneyValue('recibido_efectivo_tardio', partes[0]);
    setMoneyValue('monto_transferencia_tardio', partes[1]);

    mostrarResumenTardio();
    calcularTotalesTardio({ preserveCash: true });
    calcularCambioTardio({ preserveCash: true });
    mostrarMensajeTardio('Pago dividido entre efectivo y transferencia.', 'success');
}

function calcularMontoEfectivoTardio() {
    let totalOtros = 0;
    if (metodosSeleccionadosTardio.has('tarjeta')) {
        totalOtros += moneyValue(document.getElementById('monto_tarjeta_tardio'));
    }
    if (metodosSeleccionadosTardio.has('transferencia')) {
        totalOtros += moneyValue(document.getElementById('monto_transferencia_tardio'));
    }

    const montoEfectivo = Math.max(0, reservacionTardioData.total - totalOtros);
    setMoneyValue('monto_efectivo_tardio', montoEfectivo);
}

function calcularCambioTardio(options = {}) {
    const monto = moneyValue(document.getElementById('monto_efectivo_tardio'));
    const recibido = moneyValue(document.getElementById('recibido_efectivo_tardio'));
    const cambio = recibido - monto;

    document.getElementById('cambio_efectivo_tardio').textContent = formatMoney(cambio);
    calcularTotalesTardio(options);
}

function calcularTotalesTardio(options = {}) {
    resetearConfirmacionTardio();
    ocultarMensajeTardio();

    const preserveCash = options && options.preserveCash === true;

    if (metodosSeleccionadosTardio.has('efectivo') && !preserveCash) {
        calcularMontoEfectivoTardio();
    }

    let totalPagado = 0;

    if (metodosSeleccionadosTardio.has('efectivo')) {
        totalPagado += moneyValue(document.getElementById('monto_efectivo_tardio'));
    }
    if (metodosSeleccionadosTardio.has('tarjeta')) {
        totalPagado += moneyValue(document.getElementById('monto_tarjeta_tardio'));
    }
    if (metodosSeleccionadosTardio.has('transferencia')) {
        totalPagado += moneyValue(document.getElementById('monto_transferencia_tardio'));
    }

    const pendiente = reservacionTardioData.total - totalPagado;

    document.getElementById('total_pagado_tardio').textContent = formatMoney(totalPagado);
    document.getElementById('pendiente_tardio').textContent = formatMoney(pendiente);

    const pendienteEl = document.getElementById('pendiente_tardio');
    if (pendiente > 0) {
        pendienteEl.style.color = '#DC2626';
    } else if (pendiente < 0) {
        pendienteEl.style.color = '#16A34A';
    } else {
        pendienteEl.style.color = '#6B7280';
    }

    actualizarResultadoFacturaTardio();
}

function mostrarResumenTardio() {
    const resumen = document.getElementById('resumen_totales_tardio');
    const metodosActivos = Array.from(metodosSeleccionadosTardio).filter(m => m !== 'sin_pago');

    if (metodosActivos.length > 1) {
        resumen.classList.remove('hidden');
    } else {
        resumen.classList.add('hidden');
    }
}

function resetearPagosTardio() {
    metodosSeleccionadosTardio = new Set(['sin_pago']);

    // Desmarcar todos los checkboxes
    ['sin_pago', 'efectivo_tardio', 'tarjeta_tardio', 'transferencia_tardio'].forEach(id => {
        const check = document.getElementById('check_' + id);
        if (check) {
            check.checked = false;
            const card = check.closest('.rv-pay-option, .metodo-pago-item');
            if (card) card.classList.remove('is-open');
        }
    });

    // Ocultar todos los paneles
    ['efectivo_tardio', 'tarjeta_tardio', 'transferencia_tardio'].forEach(id => {
        const panel = document.getElementById('panel_' + id);
        if (panel) panel.classList.add('hidden');
    });

    // Limpiar campos
    ['monto_efectivo_tardio', 'recibido_efectivo_tardio', 'monto_tarjeta_tardio', 'monto_transferencia_tardio'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.value = '';
    });

    document.querySelectorAll('input[name="tipo_tarjeta_tardio"]').forEach(radio => { radio.checked = false; });
    ['label_credito_tardio', 'label_debito_tardio'].forEach(id => {
        const label = document.getElementById(id);
        if (!label) return;
        label.style.borderColor = '';
        label.style.background = '';
    });

    document.getElementById('cambio_efectivo_tardio').textContent = '$0.00';
    document.getElementById('total_pagado_tardio').textContent = '$0.00';
    document.getElementById('pendiente_tardio').textContent = '$0.00';
    document.getElementById('resumen_totales_tardio').classList.add('hidden');

    const sinPago = document.getElementById('check_sin_pago');
    if (sinPago) {
        sinPago.checked = true;
        const sinPagoCard = sinPago.closest('.rv-pay-option, .metodo-pago-item');
        if (sinPagoCard) sinPagoCard.classList.add('is-open');
    }
}

// Validar formulario antes de enviar
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCheckInTardio');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            ocultarMensajeTardio();

            // NUEVA VALIDACIÓN: Verificar factura
            if (!validarFacturaTardio()) {
                mostrarMensajeTardio('Debe indicar si el cliente requiere factura.', 'warning');
                document.getElementById('facturaContainerTardio').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            // Validación para proceso express
            if (reservacionTardioData.tipo === 'express') {
                if (metodosSeleccionadosTardio.size === 0) {
                    mostrarMensajeTardio('Selecciona al menos un método de pago para el proceso express.', 'warning');
                    document.getElementById('metodosPagoContainerTardio')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                // Confirmación única con Swal — sin doble clic
                const formRef = this;
                Swal.fire({
                    title: 'Confirmar proceso express',
                    html: 'Se registrará <strong>check-in y check-out</strong> en un solo paso. Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, procesar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#B66A00',
                    reverseButtons: true,
                }).then(result => {
                    if (result.isConfirmed) {
                        sanitizeMoneyForm(formRef);
                        formRef.submit();
                    }
                });
                return false;
            }

            // Validar tipo de tarjeta tardío
            if (metodosSeleccionadosTardio.has('tarjeta')) {
                const tipoTarjetaTardio = document.querySelector('#panel_tarjeta_tardio input[name="tipo_tarjeta_tardio"]:checked');
                if (!tipoTarjetaTardio) {
                    mostrarMensajeTardio('Selecciona el tipo de tarjeta: credito o debito.', 'warning');
                    document.getElementById('panel_tarjeta_tardio').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const labelCreditoT = document.getElementById('label_credito_tardio');
                    const labelDebitoT = document.getElementById('label_debito_tardio');
                    if (labelCreditoT) { labelCreditoT.style.borderColor = '#EF4444'; }
                    if (labelDebitoT) { labelDebitoT.style.borderColor = '#EF4444'; }
                    setTimeout(() => {
                        if (labelCreditoT) { labelCreditoT.style.borderColor = '#BFDBFE'; }
                        if (labelDebitoT) { labelDebitoT.style.borderColor = '#BFDBFE'; }
                    }, 3000);
                    return false;
                }
            }

            // Enviar formulario
            sanitizeMoneyForm(this);
            this.submit();
        });
    }
});

// ========== DATOS DE RESERVACIÓN PARA TICKET ==========
const ticketLogoSrc = <?= json_encode($ticketLogoSrc) ?>;
const ticketDownloadFileName = <?= json_encode('ticket-reservacion-' . (int) $reservacion['id'] . '.html') ?>;
const ticketData = {
    id: <?= json_encode($reservacion['id']) ?>,
    huesped: <?= json_encode($huesped['nombre_completo'] ?? 'N/A') ?>,
    telefono: <?= json_encode($huesped['telefono'] ?? '') ?>,
    fechaEntrada: <?= json_encode(!empty($reservacion['fecha_entrada']) ? date('d/m/Y', strtotime($reservacion['fecha_entrada'])) : '-') ?>,
    fechaSalida: <?= json_encode(!empty($reservacion['fecha_salida']) ? date('d/m/Y', strtotime($reservacion['fecha_salida'])) : '-') ?>,
    horaEntrada: <?= json_encode($reservacion['hora_entrada'] ?? '') ?>,
    precioTotal: <?= json_encode(floatval($reservacion['precio_total'] ?? 0)) ?>,
    metodoPago: <?= json_encode($reservacion['metodo_pago'] ?? '') ?>,
    estado: <?= json_encode($reservacion['estado'] ?? '') ?>,
    habitaciones: <?= json_encode(array_map(function($h) use ($rdRoomTypeLabel) { return ['numero' => $h['numero'], 'tipo' => $rdRoomTypeLabel($h)]; }, $habitaciones)) ?>,
    pagos: <?= json_encode($pagos ?? []) ?>,
    noches: <?= json_encode((!empty($reservacion['fecha_entrada']) && !empty($reservacion['fecha_salida'])) ? max(1, (new DateTime($reservacion['fecha_salida']))->diff(new DateTime($reservacion['fecha_entrada']))->days) : 1) ?>
};

// ========== FUNCIÓN IMPRIMIR TICKET TÉRMICO ==========
function descargarTicketHtml(ticketHtml) {
    const blob = new Blob([ticketHtml], { type: 'text/html;charset=utf-8' });
    const blobUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = blobUrl;
    link.download = ticketDownloadFileName;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(function() {
        URL.revokeObjectURL(blobUrl);
    }, 1000);
}

function imprimirTicketTermico(modo = 'imprimir') {
    const ahora = new Date();
    const fechaImpresion = ahora.toLocaleDateString('es-MX', { day:'2-digit', month:'2-digit', year:'numeric' });
    const horaImpresion = ahora.toLocaleTimeString('es-MX', { hour:'2-digit', minute:'2-digit' });

    // Construir detalle de habitaciones
    let habsHtml = '';
    ticketData.habitaciones.forEach(h => {
        habsHtml += `<tr><td style="text-align:left;">Hab. ${h.numero} </td><td style="text-align:right;">1</td></tr>`;
    });

    // Construir detalle de pagos
    let pagosHtml = '';
    if (ticketData.pagos && ticketData.pagos.length > 0) {
        ticketData.pagos.forEach(p => {
            const metodo = p.metodo_pago ? p.metodo_pago.charAt(0).toUpperCase() + p.metodo_pago.slice(1) : 'N/A';
            const monto = parseFloat(p.monto).toFixed(2);
            pagosHtml += `<tr><td style="text-align:left;">${metodo}</td><td style="text-align:right;">$${monto}</td></tr>`;
        });
    } else if (ticketData.metodoPago) {
        pagosHtml = `<tr><td style="text-align:left;">${ticketData.metodoPago.charAt(0).toUpperCase() + ticketData.metodoPago.slice(1)}</td><td style="text-align:right;">$${ticketData.precioTotal.toFixed(2)}</td></tr>`;
    }

    if (!pagosHtml) {
        pagosHtml = `<tr><td style="text-align:left;">Sin pago registrado</td><td style="text-align:right;">$0.00</td></tr>`;
    }

        // Intentar cargar logo desde múltiples ubicaciones
   const logoSrc = ticketLogoSrc || '';
   const logoHtml = logoSrc
        ? `<div class="logo-container"><img src="${logoSrc}" alt="<?= htmlspecialchars($nombreHotelVisible, ENT_QUOTES, 'UTF-8') ?>" onerror="this.style.display='none'"></div>`
        : '';

    const ticketHtml = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<title>Ticket #${ticketData.id}</title>
<style>
    @page { margin: 0; size: 80mm auto; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html {
        touch-action: pan-x pan-y;
        -webkit-text-size-adjust: 100%;
        text-size-adjust: 100%;
    }
    body {
        font-family: 'Courier New', monospace;
        width: 80mm;
        padding: 4mm;
        font-size: 13px;
        color: #000;
        background: #fff;
        font-weight: 600;
    }
    .logo-container {
        text-align: center;
        margin-bottom: 6px;
    }
    .logo-container img {
        width: 65px;
        height: 65px;
        object-fit: contain;
    }
    .hotel-name {
        text-align: center;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 2px;
        margin-bottom: 3px;
    }
    .hotel-sub {
        text-align: center;
        font-size: 11px;
        color: #333;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .divider {
        border-top: 1.5px dashed #000;
        margin: 6px 0;
    }
    .divider-double {
        border-top: 3px solid #000;
        margin: 6px 0;
    }
    .section-title {
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
        text-decoration: underline;
    }
    .row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 3px;
        font-size: 12px;
    }
    .row .label { color: #333; font-weight: 700; }
    .row .value { font-weight: 700; text-align: right; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    table td { padding: 2px 0; font-weight: 700; }
    .total-row {
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        padding: 6px 0;
        border-top: 2px dashed #000;
        border-bottom: 2px dashed #000;
        margin: 6px 0;
        letter-spacing: 1px;
    }
    .footer {
        text-align: center;
        font-size: 10px;
        color: #444;
        font-weight: 600;
        margin-top: 8px;
    }
    .footer-msg {
        text-align: center;
        font-size: 13px;
        font-weight: 700;
        margin-top: 5px;
        padding: 4px;
        letter-spacing: 0.5px;
    }
    .ticket-actions {
        position: fixed;
        top: 5px;
        left: 5px;
        right: 5px;
        z-index: 999;
        display: flex;
        gap: 6px;
    }
    .ticket-actions button {
        flex: 1;
        border: none;
        border-radius: 7px;
        padding: 9px 8px;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        box-shadow: 0 8px 18px rgba(0,0,0,.14);
    }
    .ticket-actions .ticket-back { background: #1F2937; }
    .ticket-actions .ticket-print { background: #374151; }
    .ticket-actions + button.no-print[onclick="window.print()"] { display: none; }
    @media screen {
        body { padding-top: calc(4mm + 42px); }
    }
    @media print {
        body { width: 80mm; }
        .no-print, .ticket-actions { display: none !important; }
    }
</style>
<script>
    (function() {
        var lastTouchEnd = 0;
        function blockZoom(event) {
            if (event.cancelable) {
                event.preventDefault();
            }
        }

        document.addEventListener('gesturestart', blockZoom, { passive: false });
        document.addEventListener('gesturechange', blockZoom, { passive: false });
        document.addEventListener('gestureend', blockZoom, { passive: false });
        document.addEventListener('touchmove', function(event) {
            if (event.touches && event.touches.length > 1) {
                blockZoom(event);
            }
        }, { passive: false });
        document.addEventListener('touchend', function(event) {
            var now = Date.now();
            if (now - lastTouchEnd <= 300) {
                blockZoom(event);
            }
            lastTouchEnd = now;
        }, { passive: false });
    })();
<\/script>
</head>
<body>
    <div class="ticket-actions no-print">
        <button class="ticket-back" onclick="if (window.opener && !window.opener.closed) { window.close(); } else if (history.length > 1) { history.back(); }">
            Volver al sistema
        </button>
        <button class="ticket-print" onclick="window.print()">
            Imprimir
        </button>
    </div>
    <button class="no-print" onclick="window.print()"
            style="position:fixed;top:5px;right:5px;background:#374151;color:white;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-weight:bold;z-index:999;">
        🖨️ Imprimir
    </button>

    ${logoHtml}
    <div class="hotel-name"><?= htmlspecialchars($nombreHotelTicket, ENT_QUOTES, 'UTF-8') ?></div>
    <div class="hotel-sub">Sistema de gestión hotelera</div>

    <div class="divider-double"></div>

    <div style="text-align:center; font-weight:900; font-size:15px; margin:4px 0; letter-spacing:1px;">
        COMPROBANTE DE PAGO
    </div>
    <div style="text-align:center; font-size:11px; color:#333; font-weight:700;">
        Reservación #${ticketData.id}
    </div>

    <div class="divider"></div>

    <div class="section-title">DATOS DEL HUÉSPED</div>
    <div class="row"><span class="label">Nombre:</span><span class="value">${ticketData.huesped}</span></div>
    ${ticketData.telefono ? `<div class="row"><span class="label">Tel:</span><span class="value">${ticketData.telefono}</span></div>` : ''}

    <div class="divider"></div>

    <div class="section-title">ESTANCIA</div>
    <div class="row"><span class="label">Entrada:</span><span class="value">${ticketData.fechaEntrada}</span></div>
    <div class="row"><span class="label">Salida:</span><span class="value">${ticketData.fechaSalida}</span></div>
    <div class="row"><span class="label">Noches:</span><span class="value">${ticketData.noches}</span></div>
    ${ticketData.horaEntrada ? `<div class="row"><span class="label">Hora entrada:</span><span class="value">${ticketData.horaEntrada.substring(0,5)}</span></div>` : ''}

    <div class="divider"></div>

    <div class="section-title">HABITACIONES</div>
    <table>${habsHtml}</table>

    <div class="divider"></div>

    <div class="total-row">
        TOTAL: $${ticketData.precioTotal.toFixed(2)} MXN
    </div>

    <div class="section-title">FORMA DE PAGO</div>
    <table>${pagosHtml}</table>

    <div class="divider-double"></div>

    <div class="footer">
        Impreso: ${fechaImpresion} ${horaImpresion}<br>
        Este documento no es un comprobante fiscal
    </div>
    <div class="footer-msg">
        ¡Gracias por su preferencia!<br>
        Esperamos verle pronto
    </div>

    ${modo === 'descargar' ? '' : `<script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    <\/script>`}
        <div class="row"><span class="label"> </span><span class="value"></span></div>
        <div class="row"><span class="label"> </span><span class="value"></span></div>
        <div class="row"><span class="label"> </span><span class="value"></span></div>
        <div class="row"><span class="label"> :</span><span class="value"></span></div>



</body>
</html>`;

    if (modo === 'descargar') {
        descargarTicketHtml(ticketHtml);
        return;
    }

    const ventana = window.open('', '_blank', 'width=350,height=600,scrollbars=yes');
    if (ventana) {
        ventana.document.write(ticketHtml);
        ventana.document.close();
        if (window.MedisoftMobileFiles && window.MedisoftMobileFiles.isMobile()) {
            window.MedisoftMobileFiles.showHint('ticket', false);
        }
    } else {
        descargarTicketHtml(ticketHtml);
        mostrarAvisoReservacion('El navegador bloqueo la ventana de impresion. Descargue el ticket para que puedas abrirlo o imprimirlo.', 'warning', 7200);
    }
}

// ========== FUNCIONES MODAL CAMBIAR MÉTODO DE PAGO ==========
let metodosSeleccionadosCP = new Set();
const totalReservacionCP = <?= json_encode(floatval($reservacion['precio_total'] ?? 0)) ?>;
let cambioPagoConfirmacionLista = false;

function resetConfirmacionCambioPago() {
    cambioPagoConfirmacionLista = false;
}

function abrirModalCambiarPago() {
    resetConfirmacionCambioPago();
    // Resetear todo
    metodosSeleccionadosCP.clear();
    ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
        const check = document.getElementById('check_' + m + '_cp');
        if (check) check.checked = false;
        const panel = document.getElementById('panel_' + m + '_cp');
        if (panel) panel.classList.add('hidden');
        const monto = document.getElementById('monto_' + m + '_cp');
        if (monto) monto.value = '';
    });
    const refTarjeta = document.getElementById('referencia_tarjeta_cp');
    if (refTarjeta) refTarjeta.value = '';
    const refTransf = document.getElementById('referencia_transferencia_cp');
    if (refTransf) refTransf.value = '';

    document.querySelectorAll('input[name="tipo_tarjeta_cp"]').forEach(r => r.checked = false);

    const asignadoCP = document.getElementById('asignadoCP');
    const pendienteCP = document.getElementById('divPendienteCP');
    const msgValidacionCP = document.getElementById('msgValidacionCP');
    if (asignadoCP) asignadoCP.textContent = '$0.00';
    if (pendienteCP) pendienteCP.style.display = 'none';
    if (msgValidacionCP) msgValidacionCP.classList.add('hidden');

    // Resetear sección de factura
    resetearFacturaCP();

    const modalCambiarPago = document.getElementById('modalCambiarPago');
    if (!modalCambiarPago) {
        console.error('Modal de cambio de pago no encontrado');
        return;
    }
    modalCambiarPago.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalCambiarPago() {
    resetConfirmacionCambioPago();
    const modalCambiarPago = document.getElementById('modalCambiarPago');
    if (modalCambiarPago) modalCambiarPago.style.display = 'none';
    document.body.style.overflow = 'auto';
}
window.abrirModalCambiarPago = abrirModalCambiarPago;
window.cerrarModalCambiarPago = cerrarModalCambiarPago;

function toggleMetodoPagoCP(metodo) {
    resetConfirmacionCambioPago();
    const check = document.getElementById('check_' + metodo + '_cp');
    const panel = document.getElementById('panel_' + metodo + '_cp');
    const montoInput = document.getElementById('monto_' + metodo + '_cp');

    if (check.checked) {
        metodosSeleccionadosCP.add(metodo);
        if (panel) panel.classList.remove('hidden');

        // Si es el único método, asignar el total
        if (metodosSeleccionadosCP.size === 1 && montoInput) {
            setMoneyValue(montoInput, totalReservacionCP);
        }
    } else {
        metodosSeleccionadosCP.delete(metodo);
        if (panel) panel.classList.add('hidden');
        if (montoInput) montoInput.value = '';
    }

    calcularTotalesCP();

    // Actualizar aviso de factura interna si ya se seleccionó "No"
    const facturaNoCP = document.getElementById('factura_no_cp');
    if (facturaNoCP && facturaNoCP.checked) {
        mostrarInfoFacturaInternaCP();
    }
}

function calcularTotalesCP() {
    resetConfirmacionCambioPago();
    if (metodosSeleccionadosCP.has('efectivo') && metodosSeleccionadosCP.size > 1) {
        let otrosMontos = 0;
        if (metodosSeleccionadosCP.has('tarjeta')) {
            otrosMontos += moneyValue(document.getElementById('monto_tarjeta_cp'));
        }
        if (metodosSeleccionadosCP.has('transferencia')) {
            otrosMontos += moneyValue(document.getElementById('monto_transferencia_cp'));
        }
        setMoneyValue('monto_efectivo_cp', Math.max(0, totalReservacionCP - otrosMontos));
    }

    let totalAsignado = 0;

    ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
        if (metodosSeleccionadosCP.has(m)) {
            totalAsignado += moneyValue(document.getElementById('monto_' + m + '_cp'));
        }
    });

    const pendiente = totalReservacionCP - totalAsignado;

    document.getElementById('asignadoCP').textContent = formatMoney(totalAsignado);

    if (Math.abs(pendiente) > 0.01) {
        document.getElementById('divPendienteCP').style.display = 'flex';
        document.getElementById('pendienteCP').textContent = formatMoney(pendiente);
        document.getElementById('pendienteCP').style.color = pendiente > 0 ? '#EF4444' : '#10B981';
    } else {
        document.getElementById('divPendienteCP').style.display = 'none';
    }
}

function confirmarCambioPago() {
    if (metodosSeleccionadosCP.size === 0) {
        mostrarMsgCP('Debe seleccionar al menos un método de pago', 'error');
        return;
    }

    // Validar factura
    if (!validarFacturaCP()) {
        document.getElementById('facturaContainerCP').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    // Validar que los montos cuadren
    let totalAsignado = 0;
    const pagosNuevos = [];
    let validacionPagoOk = true;

    ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
        if (metodosSeleccionadosCP.has(m)) {
            const monto = moneyValue(document.getElementById('monto_' + m + '_cp'));
            if (monto <= 0) {
                mostrarMsgCP('Todos los métodos seleccionados deben tener un monto mayor a 0', 'error');
                validacionPagoOk = false;
                return;
            }
            totalAsignado += monto;

            const pago = { metodo: m, monto: monto };

            if (m === 'tarjeta') {
                const tipoTarjeta = document.querySelector('input[name="tipo_tarjeta_cp"]:checked');
                if (!tipoTarjeta) {
                    mostrarMsgCP('Debe seleccionar tipo de tarjeta: Crédito o Débito', 'error');
                    validacionPagoOk = false;
                    return;
                }
                pago.tipo_tarjeta = tipoTarjeta.value;
                pago.referencia = document.getElementById('referencia_tarjeta_cp').value || null;
            }
            if (m === 'transferencia') {
                pago.referencia = document.getElementById('referencia_transferencia_cp').value || null;
            }

            pagosNuevos.push(pago);
        }
    });

    if (!validacionPagoOk) return;

    if (pagosNuevos.length === 0) return;

    if (Math.abs(totalAsignado - totalReservacionCP) > 0.01) {
        mostrarMsgCP(`El total asignado (${formatMoney(totalAsignado)}) no coincide con el total (${formatMoney(totalReservacionCP)})`, 'error');
        return;
    }

    if (!cambioPagoConfirmacionLista) {
        msConfirm({
            type: 'warning',
            icon: 'wallet',
            title: '¿Cambiar método de pago?',
            msg: 'Se actualizará el método de pago de esta reservación y sus movimientos de caja.',
            confirmLabel: 'Confirmar cambio'
        }).then(ok => {
            if (!ok) return;
            cambioPagoConfirmacionLista = true;
            confirmarCambioPago();
        });
        return;
    }
    resetConfirmacionCambioPago();

    const requiereFactura = document.querySelector('input[name="requiere_factura_cp"]:checked').value;

    // Enviar AJAX
    const btnConfirmar = document.getElementById('btnConfirmarCambio');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    fetch('<?= url("reservaciones/cambiar-metodo-pago/" . $reservacion["id"]) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: '<?= csrf_token() ?>',
            pagos: pagosNuevos,
            total: totalReservacionCP,
            requiere_factura: requiereFactura,
            tipo_tarjeta: (pagosNuevos.find(p => p.metodo === 'tarjeta') || {}).tipo_tarjeta || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarMsgCP(data.message || 'Metodo de pago actualizado correctamente.', 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            mostrarMsgCP(data.message || 'Error al cambiar el método de pago', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarMsgCP('Error de conexión. Intente nuevamente.', 'error');
    })
    .finally(() => {
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-check" style="margin-right:0.25rem;"></i> Confirmar Cambio';
    });
}

function mostrarMsgCP(msg, tipo) {
    const div = document.getElementById('msgValidacionCP');
    div.classList.remove('hidden');
    const isError = tipo === 'error';
    const isWarning = tipo === 'warning';
    div.style.background = isError ? '#FEF2F2' : (isWarning ? '#FFF8E8' : '#F0FDF4');
    div.style.color = isError ? '#DC2626' : (isWarning ? '#9A5F10' : '#059669');
    div.style.border = '1px solid ' + (isError ? '#FECACA' : (isWarning ? '#F4D38E' : '#BBF7D0'));
    div.innerHTML = '<i class="fas fa-' + (isError ? 'exclamation-circle' : (isWarning ? 'exclamation-triangle' : 'check-circle')) + '" style="margin-right:0.25rem;"></i>' + msg;

    setTimeout(() => div.classList.add('hidden'), 5000);
}

// =============================================
// FUNCIONES DE FACTURACIÓN - CAMBIAR PAGO (CP)
// =============================================

function seleccionarFacturaCP(valor) {
    resetConfirmacionCambioPago();
    const labelSi = document.getElementById('label_factura_si_cp');
    const labelNo = document.getElementById('label_factura_no_cp');
    const validacion = document.getElementById('facturaValidacionCP');
    const infoInterna = document.getElementById('facturaInfoInternaCP');

    if (validacion) validacion.classList.add('hidden');

    // Resetear estilos
    labelSi.style.borderColor = '#E5E7EB';
    labelSi.style.background = '#F9FAFB';
    labelNo.style.borderColor = '#E5E7EB';
    labelNo.style.background = '#F9FAFB';

    if (valor === 'si') {
        labelSi.style.borderColor = '#3B82F6';
        labelSi.style.background = '#EFF6FF';
        if (infoInterna) infoInterna.classList.add('hidden');
    } else {
        labelNo.style.borderColor = '#6B7280';
        labelNo.style.background = '#F3F4F6';
        mostrarInfoFacturaInternaCP();
    }
}

function mostrarInfoFacturaInternaCP() {
    const infoInterna = document.getElementById('facturaInfoInternaCP');
    if (!infoInterna) return;

    const tieneTarjeta = metodosSeleccionadosCP.has('tarjeta');
    const tieneTransferencia = metodosSeleccionadosCP.has('transferencia');

    if (tieneTarjeta || tieneTransferencia) {
        infoInterna.classList.remove('hidden');
    } else {
        infoInterna.classList.add('hidden');
    }
}

function validarFacturaCP() {
    const facturaSi = document.getElementById('factura_si_cp');
    const facturaNo = document.getElementById('factura_no_cp');
    const validacion = document.getElementById('facturaValidacionCP');

    if (!facturaSi.checked && !facturaNo.checked) {
        if (validacion) validacion.classList.remove('hidden');
        return false;
    }
    if (validacion) validacion.classList.add('hidden');
    return true;
}

function resetearFacturaCP() {
    const facturaSi = document.getElementById('factura_si_cp');
    const facturaNo = document.getElementById('factura_no_cp');
    const labelSi = document.getElementById('label_factura_si_cp');
    const labelNo = document.getElementById('label_factura_no_cp');
    const validacion = document.getElementById('facturaValidacionCP');
    const infoInterna = document.getElementById('facturaInfoInternaCP');

    if (facturaSi) facturaSi.checked = false;
    if (facturaNo) facturaNo.checked = false;
    if (labelSi) { labelSi.style.borderColor = '#E5E7EB'; labelSi.style.background = '#F9FAFB'; }
    if (labelNo) { labelNo.style.borderColor = '#E5E7EB'; labelNo.style.background = '#F9FAFB'; }
    if (validacion) validacion.classList.add('hidden');
    if (infoInterna) infoInterna.classList.add('hidden');
}

// Cerrar modal con clic fuera
const modalCambiarPagoClick = document.getElementById('modalCambiarPago');
if (modalCambiarPagoClick) {
    modalCambiarPagoClick.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalCambiarPago();
    });
}

document.querySelectorAll('input[name="tipo_tarjeta_cp"], #referencia_tarjeta_cp, #referencia_transferencia_cp').forEach(control => {
    control.addEventListener('input', resetConfirmacionCambioPago);
    control.addEventListener('change', resetConfirmacionCambioPago);
});

// Check-in exitoso: pantalla de éxito + oferta de imprimir ticket (solo PC)
<?php if ($auto_imprimir_ticket): ?>
<?php
$rdCheckinHabsCount = count($habitaciones ?? []);
$rdCheckinPageMsg = $rdHabitacionesTexto !== ''
    ? ($rdCheckinHabsCount > 1
        ? "Las habitaciones {$rdHabitacionesTexto} ya están ocupadas."
        : "La habitación {$rdHabitacionesTexto} ya está ocupada.")
    : 'La llegada del huésped quedó registrada.';
$rdCheckinExtra = '';
if ($rdCheckinFlashTexto !== null && ($rdCheckinPos = stripos($rdCheckinFlashTexto, 'exitosamente')) !== false) {
    $rdCheckinExtra = trim(substr($rdCheckinFlashTexto, $rdCheckinPos + strlen('exitosamente')), " .\t\n");
}
if ($rdCheckinExtra !== '') {
    $rdCheckinPageMsg .= ' ' . $rdCheckinExtra . '.';
}
?>
document.addEventListener('DOMContentLoaded', function() {
    // El estado de página sustituye al toast para este flujo.
    document.querySelectorAll('#ms-toast-stack .ms-toast').forEach(function(t){ t.remove(); });

    // Pantalla de éxito con las acciones (ver reservación / volver al inicio).
    function mostrarEstadoCheckin() {
        if (typeof msPageState === 'function') {
            msPageState({
                type: 'success',
                icon: 'check',
                title: '¡Check-in completado!',
                msg: <?= json_encode($rdCheckinPageMsg, JSON_UNESCAPED_UNICODE) ?>,
                primary: { label: 'Ver reservación' },
                secondary: { label: 'Volver al inicio', href: '<?= url('dashboard') ?>' }
            });
        }
    }

    // Ticket: solo en PC. En móvil se omite para no saturar la vista.
    var __esMovilTicket = (window.MedisoftMobileFiles && typeof window.MedisoftMobileFiles.isMobile === 'function')
        ? window.MedisoftMobileFiles.isMobile()
        : window.matchMedia('(max-width: 820px)').matches;

    if (__esMovilTicket || typeof msConfirm !== 'function') {
        mostrarEstadoCheckin();
        return;
    }

    // Primero preguntar por el ticket; al decidir, mostrar la pantalla de éxito.
    msConfirm({
        type: 'info',
        icon: 'check',
        title: '¿Imprimir ticket?',
        msg: 'El check-in quedó registrado. ¿Deseas imprimir el ticket del huésped?',
        confirmLabel: 'Imprimir ticket',
        cancelLabel: 'Ahora no'
    }).then(function(ok) {
        if (ok) imprimirTicketTermico('imprimir');
        mostrarEstadoCheckin();
    });
});
<?php endif; ?>

// ============================================
// PAGO PENDIENTE TRAS MODIFICAR ESTANCIA
// (La edición de estancia vive ahora en reservaciones/editar-estancia)
// ============================================
const RDV3_RESERVACION_ID = <?= $reservacion['id'] ?>;

document.addEventListener('DOMContentLoaded', function () {
    const pendingKey = 'rdv3PendingPayment:' + RDV3_RESERVACION_ID;
    const rawPending = sessionStorage.getItem(pendingKey);
    if (!rawPending) {
        return;
    }

    sessionStorage.removeItem(pendingKey);
    let pending = {};
    try {
        pending = JSON.parse(rawPending) || {};
    } catch (e) {
        pending = {};
    }

    const section = document.getElementById('rdv3-payment-section');
    if (!section) {
        return;
    }

    const saldo = Number(section.dataset.rdv3PaymentBalance || pending.monto || 0);
    const monto = Math.max(0, saldo);
    const input = section.querySelector('input[name="monto"]');
    if (input && monto > 0.004) {
        input.value = monto.toFixed(2);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    section.style.boxShadow = '0 0 0 3px rgba(180, 84, 15, .18), 0 18px 48px -38px rgba(15,23,42,.45)';
    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(function () {
        if (input && typeof input.focus === 'function') {
            input.focus({ preventScroll: true });
            input.select();
        }
    }, 450);
    setTimeout(function () {
        section.style.boxShadow = '';
    }, 4200);

    const montoTexto = '$' + monto.toLocaleString('es-MX', { minimumFractionDigits: 2 });
    mostrarAvisoReservacion('Captura el pago pendiente de ' + montoTexto + ' para que caja y la reservacion queden conciliadas.', 'warning', 7200);
});

</script>

<style>
/* Estilos adicionales para mejorar la experiencia */
.hidden {
    display: none !important;
}

/* Animación suave para modales */
.modal-overlay {
    animation: fadeIn 0.2s ease;
}

.modal-content {
    animation: slideUp 0.3s ease;
}

.modal-header {
    padding: 1rem;
    border-bottom: 2px solid #F3F4F6;
    background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);
    position: relative; /* importante para que la X se posicione dentro */
}

.modal-header button {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #6B7280;
    font-size: 1.25rem;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.modal-content {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    width: 380px;
    max-height: 90vh;       /* límite de altura */
    display: flex;
    flex-direction: column; /* header arriba y body scroll */
}

.modal-body {
    padding: 1rem;
    overflow-y: auto;       /* scroll vertical */
    flex: 1;                /* ocupa todo el espacio sobrante */
}

#modalCheckOut {
    padding: clamp(10px, 2vw, 24px);
}

#modalCheckOut .checkout-modal-content {
    width: min(500px, calc(100vw - 24px)) !important;
    max-width: min(500px, calc(100vw - 24px)) !important;
    max-height: min(92dvh, 760px) !important;
    overflow: hidden !important;
}

#modalCheckOut .modal-header {
    flex: 0 0 auto;
}

#modalCheckOut .checkout-modal-form {
    min-height: 0;
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}

#modalCheckOut .checkout-modal-body {
    min-height: 0;
    flex: 1 1 auto;
    overflow-y: auto;
}

#modalCheckOut .checkout-room-scroll {
    max-height: min(38dvh, 260px);
    overflow-y: auto;
    padding-right: 4px;
    scrollbar-width: thin;
    scrollbar-color: #F97316 #FFEDD5;
}

#modalCheckOut .checkout-room-scroll::-webkit-scrollbar {
    width: 8px;
}

#modalCheckOut .checkout-room-scroll::-webkit-scrollbar-track {
    background: #FFEDD5;
    border-radius: 999px;
}

#modalCheckOut .checkout-room-scroll::-webkit-scrollbar-thumb {
    background: #F97316;
    border-radius: 999px;
}

#modalCheckOut .checkout-modal-actions {
    flex: 0 0 auto;
}

.swal2-popup.rv-checkout-confirm-swal {
    border-radius: 20px !important;
    border: 1px solid #FED7AA !important;
    box-shadow: 0 34px 82px -44px rgba(15, 23, 42, .72) !important;
}
.swal2-container.rv-checkout-confirm-container {
    z-index: 100200 !important;
}
.rv-checkout-confirm {
    display: grid;
    gap: 12px;
    text-align: left;
}
.rv-checkout-confirm__lead {
    margin: 0;
    color: #475467;
    font-size: .92rem;
    line-height: 1.45;
}
.rv-checkout-confirm__panel {
    display: grid;
    gap: 6px;
    padding: 12px;
    border: 1px solid #FED7AA;
    border-radius: 14px;
    background: #FFF7ED;
    color: #9A3412;
    font-size: .82rem;
    font-weight: 750;
    line-height: 1.4;
}
.rv-checkout-confirm__panel strong {
    color: #7C2D12;
}
.rv-checkout-confirm-action,
.rv-checkout-confirm-cancel {
    min-height: 42px !important;
    border-radius: 12px !important;
    font-weight: 900 !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
}

@media (max-width: 640px) {
    #modalCheckOut {
        align-items: flex-end;
        padding: 8px 8px 0;
    }

    #modalCheckOut .checkout-modal-content {
        width: 100% !important;
        max-width: 100% !important;
        max-height: 92dvh !important;
        border-radius: 1.25rem 1.25rem 0 0;
    }

    #modalCheckOut .checkout-modal-actions {
        display: grid !important;
        grid-template-columns: 1fr;
        gap: .75rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px)) !important;
    }
}

/* Check-in confirm modal: step-by-step wizard, keeps IDs and form contract intact. */
#modalCheckIn.rv-checkin-modal {
    --rv-checkin-ink: var(--brand-secondary, #14213D);
    --rv-checkin-brand: var(--brand-primary, #1B2746);
    --rv-checkin-accent: var(--brand-accent, #BD9441);
    --rv-checkin-line: color-mix(in srgb, var(--rv-checkin-accent) 24%, #E7DDCB);
    --rv-checkin-soft: color-mix(in srgb, var(--rv-checkin-accent) 9%, #FBF8F1);
    --rv-checkin-muted: #6E7890;
    align-items: center;
    justify-content: center;
    padding: clamp(12px, 3vw, 28px);
    background: rgba(18, 23, 31, .58);
    z-index: 12000;
}

#modalCheckIn .rv-checkin-shell {
    width: min(640px, calc(100vw - 26px)) !important;
    max-width: min(640px, calc(100vw - 26px)) !important;
    height: min(94dvh, 740px);
    max-height: min(94dvh, 740px);
    overflow: hidden;
    border-radius: 26px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 3%, #FFFEFB);
    border: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 18%, rgba(255,255,255,.9));
    box-shadow: 0 32px 78px -40px rgba(4, 8, 18, .82), 0 0 0 1px rgba(255,255,255,.7) inset;
    animation: rvCheckInShow .28s cubic-bezier(.22, 1, .36, 1);
}

@keyframes rvCheckInShow {
    from { opacity: 0; transform: translateY(18px) scale(.985); }
    to { opacity: 1; transform: none; }
}

@keyframes rvCheckInStageIn {
    from { opacity: 0; transform: translateX(16px); }
    to { opacity: 1; transform: none; }
}

#modalCheckIn .rv-checkin-form {
    height: 100%;
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr) auto;
    overflow: hidden;
    padding: 0;
    background: transparent;
}

#modalCheckIn .rv-checkin-topbar {
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 26px 16px;
    background: #FFFEFB;
    border-bottom: 1px solid var(--rv-checkin-line);
}

#modalCheckIn .rv-checkin-titleblock {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

#modalCheckIn .rv-checkin-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, #21A36A 13%, #F1FBF5);
    color: #17945C;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, #21A36A 13%, transparent);
}

#modalCheckIn .rv-checkin-titleblock h3 {
    margin: 0;
    color: var(--rv-checkin-ink);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(1.35rem, 3vw, 1.75rem);
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0;
}

#modalCheckIn .rv-checkin-titleblock p {
    max-width: 48ch;
    margin: 5px 0 0;
    color: var(--rv-checkin-muted);
    font-size: .82rem;
    font-weight: 760;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

#modalCheckIn .rv-checkin-close {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 20%, #E4D9C6);
    border-radius: 10px;
    background: #FFFEFB;
    color: #778197;
    cursor: pointer;
    transition: transform .18s ease, border-color .18s ease, color .18s ease, background .18s ease;
}

#modalCheckIn .rv-checkin-close:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rv-checkin-accent) 44%, #E4D9C6);
    color: var(--rv-checkin-ink);
    background: var(--rv-checkin-soft);
}

#modalCheckIn .rv-checkin-stepper {
    min-height: 66px;
    display: grid;
    grid-template-columns: auto minmax(34px, 1fr) auto minmax(34px, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 0 30px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 3%, #FBF8F1);
    border-bottom: 1px solid var(--rv-checkin-line);
}

#modalCheckIn .rv-checkin-step {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    color: #97A0B3;
    font-size: .84rem;
    font-weight: 700;
    white-space: nowrap;
    transition: color .18s ease;
}

#modalCheckIn .rv-checkin-step span {
    width: 29px;
    height: 29px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 20%, #DFD6C8);
    background: #FFFEFB;
    color: #9AA3B6;
    font-variant-numeric: tabular-nums;
    transition: background .18s ease, border-color .18s ease, color .18s ease;
}

#modalCheckIn .rv-checkin-step.is-current {
    color: var(--rv-checkin-ink);
}

#modalCheckIn .rv-checkin-step.is-current span {
    border-color: var(--rv-checkin-brand);
    background: var(--rv-checkin-brand);
    color: #fff;
}

#modalCheckIn .rv-checkin-step.is-complete {
    color: #17945C;
}

#modalCheckIn .rv-checkin-step.is-complete span {
    border-color: #1BA56B;
    background: #1BA56B;
    color: #fff;
}

#modalCheckIn .rv-checkin-step.is-skipped {
    color: #64748B;
}

#modalCheckIn .rv-checkin-step.is-skipped span {
    border-color: #BBF7D0;
    background: #ECFDF5;
    color: #15803D;
}

#modalCheckIn .rv-checkin-line {
    height: 2px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 16%, #E8E1D5);
    transition: background .18s ease;
}

#modalCheckIn .rv-checkin-line.is-complete,
#modalCheckIn .rv-checkin-line.is-current {
    background: linear-gradient(90deg, #1BA56B, color-mix(in srgb, var(--rv-checkin-accent) 72%, #1BA56B));
}

#modalCheckIn .rv-checkin-content {
    min-height: 0;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: 24px 26px 30px;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
    -webkit-overflow-scrolling: touch;
}

#modalCheckIn .rv-checkin-stage {
    width: 100%;
    min-height: min-content;
    flex: 0 0 auto;
    display: none;
    align-content: start;
    align-self: stretch;
    gap: 16px;
    animation: rvCheckInStageIn .22s ease;
}

#modalCheckIn .rv-checkin-stage.is-active {
    display: grid;
}

#modalCheckIn .rv-checkin-stage,
#modalCheckIn .rv-checkin-summary {
    border: 1px solid var(--rv-checkin-line);
    border-radius: 18px;
    background: rgba(255,255,255,.78);
    box-shadow: 0 1px 0 rgba(255,255,255,.86) inset;
}

#modalCheckIn .rv-stage-arrival {
    border: 0;
    background: transparent;
    box-shadow: none;
    align-content: center;
}

#modalCheckIn .rv-total-card {
    min-height: 98px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 38%, #E6D2AD);
    border-radius: 18px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--rv-checkin-accent) 16%, #FFF9EC), color-mix(in srgb, var(--rv-checkin-accent) 9%, #FFFDF7));
}

#modalCheckIn .rv-kicker {
    display: block;
    margin-bottom: 7px;
    color: color-mix(in srgb, var(--rv-checkin-accent) 74%, #806037);
    font-size: .78rem;
    font-weight: 600;
}

#modalCheckIn #totalACobrar {
    display: block;
    color: var(--rv-checkin-ink) !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(2rem, 6vw, 2.65rem) !important;
    font-weight: 800 !important;
    line-height: .92 !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-date-pill {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 14px;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 38%, #E6D2AD);
    border-radius: 999px;
    background: rgba(255,255,255,.74);
    color: color-mix(in srgb, var(--rv-checkin-accent) 76%, #6C532F);
    font-size: .82rem;
    font-weight: 600;
    white-space: nowrap;
}

#modalCheckIn .rv-arrival-field {
    margin: 0 !important;
    display: grid;
    gap: 8px;
}

#modalCheckIn .form-label,
#modalCheckIn .rv-pay-panel label {
    margin: 0;
    color: #6F7890 !important;
    font-size: .78rem !important;
    font-weight: 900 !important;
    letter-spacing: 0 !important;
    text-transform: none !important;
}

#modalCheckIn .rv-time-input {
    min-height: 54px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    border: 1px solid var(--rv-checkin-line);
    border-radius: 14px;
    background: #FFFEFB;
}

#modalCheckIn .rv-time-input i {
    color: #AAB3C4;
}

#modalCheckIn input[type="time"],
#modalCheckIn input[type="number"],
#modalCheckIn input[type="text"] {
    width: 100% !important;
    min-height: 38px !important;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-brand) 14%, #DEE3EA) !important;
    border-radius: 12px !important;
    padding: 8px 11px !important;
    background: #FFFEFB !important;
    color: var(--rv-checkin-ink) !important;
    font-size: .9rem !important;
    font-weight: 850 !important;
    outline: none !important;
    font-variant-numeric: tabular-nums;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

#modalCheckIn .rv-time-input input[type="time"] {
    min-height: 50px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    font-size: 1.12rem !important;
    font-weight: 950 !important;
}

#modalCheckIn input[type="time"]:focus,
#modalCheckIn input[type="number"]:focus,
#modalCheckIn input[type="text"]:focus {
    border-color: var(--rv-checkin-brand) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--rv-checkin-brand) 12%, transparent) !important;
}

#modalCheckIn .rv-room-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

#modalCheckIn .rv-room-card {
    min-height: 96px;
    display: grid;
    place-items: center;
    gap: 4px;
    padding: 14px;
    border: 1px solid var(--rv-checkin-line);
    border-radius: 14px;
    background: #FFFEFB;
    text-align: center;
}

#modalCheckIn .rv-room-card strong {
    color: var(--rv-checkin-ink);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 1.48rem;
    font-weight: 600;
    line-height: 1;
}

#modalCheckIn .rv-room-card span {
    color: #63708A;
    font-size: .78rem;
    font-weight: 760;
}

#modalCheckIn .rv-room-card em {
    min-height: 25px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, #20A36A 12%, #F0FBF5);
    color: #15915B;
    font-size: .75rem;
    font-style: normal;
    font-weight: 600;
}

#modalCheckIn .rv-room-card.is-courtesy em {
    background: color-mix(in srgb, var(--rv-checkin-accent) 15%, #FFF8E8);
    color: color-mix(in srgb, var(--rv-checkin-accent) 82%, #765621);
}

#modalCheckIn .rv-payment-section,
#modalCheckIn .rv-invoice-section {
    margin: 0 !important;
    padding: 16px !important;
    align-content: start;
}

#modalCheckIn .rv-payment-section > h4,
#modalCheckIn .rv-invoice-section h4 {
    margin: 0 0 12px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    color: var(--rv-checkin-ink) !important;
    font-size: .92rem !important;
    font-weight: 950 !important;
}

#modalCheckIn .rv-payment-section > h4 i,
#modalCheckIn .rv-invoice-section h4 i {
    color: color-mix(in srgb, var(--rv-checkin-accent) 74%, var(--rv-checkin-brand)) !important;
    margin-right: 0 !important;
}

#modalCheckIn .rv-invoice-section h4 span {
    color: #D34B4B;
}

#modalCheckIn .rv-payment-hint {
    margin: -3px 0 13px;
    color: #6E7890;
    font-size: .78rem;
    font-weight: 760;
    line-height: 1.42;
}

#modalCheckIn .rv-payment-shortcuts {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0 0 16px;
}

#modalCheckIn .rv-money-shortcut,
#modalCheckIn .rv-money-mini {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 12px;
    border: 1px solid color-mix(in srgb, var(--rv-shortcut-color, var(--rv-checkin-accent)) 24%, #E2D8C9);
    border-radius: 999px;
    background: rgba(255,255,255,.7);
    color: color-mix(in srgb, var(--rv-shortcut-color, var(--rv-checkin-brand)) 78%, #27324A);
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

#modalCheckIn .rv-money-shortcut:hover,
#modalCheckIn .rv-money-mini:hover {
    transform: translateY(-1px);
    background: #FFFEFB;
    border-color: color-mix(in srgb, var(--rv-shortcut-color, var(--rv-checkin-accent)) 44%, #E2D8C9);
    box-shadow: 0 12px 22px -20px color-mix(in srgb, var(--rv-shortcut-color, var(--rv-checkin-brand)) 72%, transparent);
}

#modalCheckIn .rv-money-shortcut.is-cash { --rv-shortcut-color: #19A367; }
#modalCheckIn .rv-money-shortcut.is-card { --rv-shortcut-color: #2C70E8; }
#modalCheckIn .rv-money-shortcut.is-transfer { --rv-shortcut-color: #7A52E1; }
#modalCheckIn .rv-money-shortcut.is-split { --rv-shortcut-color: var(--rv-checkin-accent); }
#modalCheckIn .rv-money-shortcut.is-cash-transfer { --rv-shortcut-color: #0F9F8F; }

#modalCheckIn .rv-pending-option {
    display: grid;
    grid-template-columns: auto auto minmax(0, 1fr);
    align-items: center;
    gap: 11px;
    margin: 0 0 13px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, #D97706 28%, var(--rv-checkin-line));
    border-radius: 14px;
    background: #FFF8EA;
    color: #7C4A12;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

#modalCheckIn .rv-pending-option:hover,
#modalCheckIn .rv-pending-option:focus-within {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, #D97706 50%, var(--rv-checkin-line));
    background: #FFF5DC;
    box-shadow: 0 14px 28px -26px rgba(217,119,6,.75);
}

#modalCheckIn .rv-pending-option input {
    width: 18px !important;
    height: 18px !important;
    margin: 0 !important;
    accent-color: #D97706;
}

#modalCheckIn .rv-pending-option__icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: rgba(217,119,6,.12);
    color: #B45309;
}

#modalCheckIn .rv-pending-option strong {
    display: block;
    color: #6B3B08;
    font-size: .84rem;
    font-weight: 950;
}

#modalCheckIn .rv-pending-option small {
    display: block;
    margin-top: 2px;
    color: #8A5A18;
    font-size: .73rem;
    font-weight: 720;
    line-height: 1.35;
}

#modalCheckIn .rv-pending-preview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin: -4px 0 13px;
}

#modalCheckIn .rv-pending-preview[hidden] {
    display: none !important;
}

#modalCheckIn .rv-pending-preview > div {
    min-height: 58px;
    padding: 10px 12px;
    border: 1px solid color-mix(in srgb, #D97706 24%, var(--rv-checkin-line));
    border-radius: 12px;
    background: #FFFDF7;
}

#modalCheckIn .rv-pending-preview span {
    display: block;
    margin-bottom: 4px;
    color: #7C4A12;
    font-size: .72rem;
    font-weight: 820;
}

#modalCheckIn .rv-pending-preview strong {
    color: #3E4656;
    font-size: 1rem;
    font-weight: 950;
}

#modalCheckInTardio .rv-payment-shortcuts {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 9px;
    margin: 0 0 12px;
}

#modalCheckInTardio .rv-money-shortcut {
    --rv-shortcut-color: var(--brand-primary, #1B2746);
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid color-mix(in srgb, var(--rv-shortcut-color) 22%, #E2D8C9);
    border-radius: 13px;
    background: color-mix(in srgb, var(--rv-shortcut-color) 6%, #FFFFFF);
    color: color-mix(in srgb, var(--rv-shortcut-color) 86%, #263247);
    font-size: .75rem;
    font-weight: 850;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

#modalCheckInTardio .rv-money-shortcut:hover,
#modalCheckInTardio .rv-money-shortcut:focus-visible {
    transform: translateY(-1px);
    background: #FFFEFB;
    border-color: color-mix(in srgb, var(--rv-shortcut-color) 44%, #E2D8C9);
    box-shadow: 0 12px 22px -20px color-mix(in srgb, var(--rv-shortcut-color) 72%, transparent);
    outline: none;
}

#modalCheckInTardio .rv-money-shortcut.is-cash { --rv-shortcut-color: #19A367; }
#modalCheckInTardio .rv-money-shortcut.is-card { --rv-shortcut-color: #2C70E8; }
#modalCheckInTardio .rv-money-shortcut.is-transfer { --rv-shortcut-color: #7A52E1; }
#modalCheckInTardio .rv-money-shortcut.is-split { --rv-shortcut-color: var(--brand-accent, #BD9441); }
#modalCheckInTardio .rv-money-shortcut.is-cash-transfer { --rv-shortcut-color: #0F9F8F; }

#modalCheckIn .rv-pay-methods {
    display: grid !important;
    align-items: start;
    gap: 10px !important;
}

#modalCheckIn .rv-pay-option {
    --rv-pay-color: var(--rv-checkin-brand);
    overflow: hidden;
    scroll-margin: 18px 0 104px;
    border: 1px solid color-mix(in srgb, var(--rv-pay-color) 16%, #E1E7EE) !important;
    border-radius: 14px !important;
    background: #FFFEFB !important;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

#modalCheckIn .rv-pay-option.is-open,
#modalCheckIn .rv-pay-option:has(input[type="checkbox"]:checked) {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rv-pay-color) 56%, #DDE4EC) !important;
    background: color-mix(in srgb, var(--rv-pay-color) 6%, #FFFEFB) !important;
    box-shadow: 0 16px 30px -26px color-mix(in srgb, var(--rv-pay-color) 74%, transparent);
}

#modalCheckIn .rv-pay-cash { --rv-pay-color: #1BA56B; }
#modalCheckIn .rv-pay-card { --rv-pay-color: #2C70E8; }
#modalCheckIn .rv-pay-transfer { --rv-pay-color: #824CE6; }

#modalCheckIn .rv-pay-option > label {
    width: 100% !important;
    min-height: 48px;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 11px 14px !important;
    color: var(--rv-checkin-ink) !important;
    cursor: pointer !important;
    font-size: .88rem !important;
    font-weight: 950 !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
}

#modalCheckIn .rv-pay-option > label input {
    width: 18px !important;
    height: 18px !important;
    margin: 0 !important;
    accent-color: var(--rv-pay-color) !important;
}

#modalCheckIn .rv-pay-option > label i {
    width: 24px;
    height: 24px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    background: color-mix(in srgb, var(--rv-pay-color) 11%, #F7FAFC);
    color: var(--rv-pay-color) !important;
    margin-right: 0 !important;
    font-size: .75rem;
}

#modalCheckIn .rv-pay-panel {
    margin-top: 0 !important;
    padding: 10px 14px 12px;
    border-top: 1px solid color-mix(in srgb, var(--rv-pay-color) 16%, #E1E7EE);
    background: color-mix(in srgb, var(--rv-pay-color) 5%, #FFFEFB);
}

#modalCheckIn .rv-input-grid,
#modalCheckIn .rv-card-type,
#modalCheckIn .rv-invoice-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 9px !important;
}

#modalCheckIn .rv-card-type {
    margin-bottom: 9px;
}

#modalCheckIn .rv-radio-chip,
#modalCheckIn .rv-invoice-choice {
    border: 1px solid color-mix(in srgb, var(--rv-checkin-brand) 13%, #DCE3EA) !important;
    border-radius: 13px !important;
    background: #FFFEFB !important;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

#modalCheckIn .rv-radio-chip {
    min-height: 38px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 10px !important;
    color: #2C70E8 !important;
    font-size: .8rem !important;
    font-weight: 900 !important;
}

#modalCheckIn .rv-radio-chip input,
#modalCheckIn .rv-invoice-choice input {
    accent-color: var(--rv-checkin-brand);
}

#modalCheckIn .rv-radio-chip:has(input:checked) {
    border-color: #2C70E8 !important;
    background: #EEF5FF !important;
}

#modalCheckIn .rv-change-pill {
    min-height: 34px;
    margin-top: 9px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 0 12px;
    border-radius: 12px;
    background: rgba(255,255,255,.72);
    color: #63708A;
    font-size: .8rem;
    font-weight: 600;
}

#modalCheckIn .rv-change-pill strong {
    color: #15915B;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-money-mini {
    width: 100%;
    margin-top: 7px;
    --rv-shortcut-color: #19A367;
}

#modalCheckIn .rv-final-grid {
    display: grid;
    gap: 14px;
}

#modalCheckIn .rv-invoice-choice {
    min-height: 78px;
    display: flex !important;
    align-items: flex-start !important;
    gap: 11px !important;
    padding: 14px !important;
}

#modalCheckIn .rv-invoice-choice:hover {
    transform: translateY(-1px);
}

#modalCheckIn .rv-invoice-choice strong {
    display: block;
    color: var(--rv-checkin-ink) !important;
    font-size: .91rem !important;
    font-weight: 950 !important;
}

#modalCheckIn .rv-invoice-choice p {
    margin: 4px 0 0 !important;
    color: #7A8498 !important;
    font-size: .76rem !important;
    font-weight: 720 !important;
    line-height: 1.3 !important;
}

#modalCheckIn #label_factura_si.is-selected,
#modalCheckIn #label_factura_si:has(input:checked) {
    border-color: color-mix(in srgb, #2C70E8 62%, #DCE3EA) !important;
    background: #EEF5FF !important;
    box-shadow: 0 13px 24px -22px rgba(44,112,232,.82);
}

#modalCheckIn #label_factura_no.is-selected,
#modalCheckIn #label_factura_no:has(input:checked) {
    border-color: color-mix(in srgb, var(--rv-checkin-accent) 62%, #DCE3EA) !important;
    background: color-mix(in srgb, var(--rv-checkin-accent) 14%, #FFFEFB) !important;
    box-shadow: 0 13px 24px -22px color-mix(in srgb, var(--rv-checkin-accent) 75%, transparent);
}

#modalCheckIn .rv-invoice-result {
    margin-top: 10px;
    border: 1px solid var(--rv-checkin-line);
    border-radius: 14px;
    background: color-mix(in srgb, var(--rv-checkin-brand) 4%, #FFFFFF);
    padding: 10px 12px;
}

#modalCheckIn .rv-invoice-result p {
    margin: 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: var(--rv-checkin-muted);
    font-size: .76rem;
    font-weight: 750;
    line-height: 1.4;
}

#modalCheckIn .rv-invoice-result i {
    margin-top: 2px;
    color: var(--rv-checkin-brand);
}

#modalCheckIn .rv-invoice-result.is-client {
    border-color: #BFDBFE;
    background: #EFF6FF;
}

#modalCheckIn .rv-invoice-result.is-client p,
#modalCheckIn .rv-invoice-result.is-client i {
    color: #1D4ED8;
}

#modalCheckIn .rv-invoice-result.is-internal {
    border-color: #FED7AA;
    background: #FFF7ED;
}

#modalCheckIn .rv-invoice-result.is-internal p,
#modalCheckIn .rv-invoice-result.is-internal i {
    color: #9A3412;
}

#modalCheckIn .rv-invoice-result.is-none {
    border-color: #D1D5DB;
    background: #F9FAFB;
}

#modalCheckIn .rv-invoice-result.is-none p,
#modalCheckIn .rv-invoice-result.is-none i {
    color: #4B5563;
}

#modalCheckIn .rv-checkin-note,
#modalCheckIn .rv-checkin-message {
    margin-top: 10px !important;
    border-radius: 12px !important;
    padding: 10px 12px !important;
    font-size: .8rem !important;
    font-weight: 780 !important;
    line-height: 1.35 !important;
}

#modalCheckIn .rv-checkin-note p,
#modalCheckIn .rv-checkin-message p {
    margin: 0;
}

#modalCheckIn .rv-checkin-note {
    border: 1px solid #BFD4F5 !important;
    background: #F0F6FF !important;
    color: #255AA7 !important;
}

#modalCheckIn .rv-checkin-message {
    scroll-margin: 18px 0 104px;
    border: 1px solid #FECACA !important;
    background: #FEF2F2 !important;
    color: #B42318 !important;
}

#modalCheckIn .rv-checkin-message.is-warning {
    border-color: #F5D894 !important;
    background: #FFF8EA !important;
    color: #926118 !important;
}

#modalCheckIn .rv-checkin-message.is-info {
    border-color: #BFD4F5 !important;
    background: #F0F6FF !important;
    color: #255AA7 !important;
}

#modalCheckIn .rv-checkin-message.is-success {
    border-color: #BFE9D3 !important;
    background: #F0FBF5 !important;
    color: #15835A !important;
}

#modalCheckIn #mensajeValidacion:not(.hidden) {
    width: 100%;
    flex: 0 0 auto;
    align-self: stretch;
    margin-top: 12px !important;
    position: static;
}

#modalCheckIn .rv-checkin-summary {
    padding: 0 !important;
    overflow: hidden;
}

#modalCheckIn .rv-checkin-summary h5 {
    margin: 0 !important;
    padding: 13px 16px;
    background: linear-gradient(135deg, var(--rv-checkin-brand), var(--rv-checkin-ink));
    color: #fff !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 1.05rem !important;
    font-weight: 800 !important;
}

#modalCheckIn .rv-summary-row {
    min-height: 38px;
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 0 16px;
    border-top: 1px solid color-mix(in srgb, var(--rv-checkin-accent) 15%, #E8E0D4);
    color: #6E7890 !important;
    font-size: .84rem !important;
    font-weight: 780 !important;
    margin: 0 !important;
}

#modalCheckIn .rv-summary-row strong {
    color: var(--rv-checkin-ink) !important;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-summary-row.is-paid strong { color: #15915B !important; }
#modalCheckIn .rv-summary-row.is-due strong { color: #DC3E3E !important; }
#modalCheckIn .rv-summary-row.is-change strong { color: #2C70E8 !important; }

#modalCheckIn .rv-checkin-actions {
    display: grid !important;
    grid-template-columns: minmax(120px, auto) 1fr minmax(190px, auto);
    align-items: center;
    gap: 14px !important;
    padding: 16px 24px !important;
    border-top: 1px solid var(--rv-checkin-line);
    background: #FFFEFB !important;
}

#modalCheckIn .rv-btn-cancel,
#modalCheckIn .rv-btn-confirm {
    min-height: 45px !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 13px !important;
    padding: 0 16px !important;
    font-size: .9rem !important;
    font-weight: 950 !important;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

#modalCheckIn .rv-btn-cancel {
    border: 1px solid var(--rv-checkin-line) !important;
    color: #4E5A70 !important;
    background: #FFFEFB !important;
}

#modalCheckIn .rv-btn-confirm {
    border: 0 !important;
    color: #fff !important;
    background: linear-gradient(135deg, color-mix(in srgb, #20A36A 76%, var(--rv-checkin-brand)), #15835A) !important;
    box-shadow: 0 17px 32px -20px rgba(20,131,88,.9) !important;
}

#modalCheckIn .rv-btn-confirm:disabled {
    opacity: .58;
    cursor: not-allowed;
    box-shadow: none !important;
}

#modalCheckIn .rv-btn-cancel:hover,
#modalCheckIn .rv-btn-confirm:not(:disabled):hover {
    transform: translateY(-1px);
}

#modalCheckIn .rv-step-dots {
    display: inline-flex;
    justify-content: center;
    gap: 7px;
}

#modalCheckIn .rv-step-dots span {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 25%, #E4DED2);
    transition: width .18s ease, background .18s ease;
}

#modalCheckIn .rv-step-dots span.is-active {
    width: 24px;
    background: var(--rv-checkin-accent);
}

@media (max-width: 680px) {
    #modalCheckIn.rv-checkin-modal {
        align-items: flex-end;
        padding: 8px 8px 0;
    }

    #modalCheckIn .rv-checkin-shell {
        width: 100% !important;
        max-width: 100% !important;
        height: 96dvh;
        max-height: 96dvh;
        border-radius: 24px 24px 0 0;
    }

    #modalCheckIn .rv-checkin-topbar {
        min-height: 82px;
        padding: 16px 16px 14px;
    }

    #modalCheckIn .rv-checkin-titleblock h3 {
        font-family: 'Manrope', system-ui, sans-serif;
        font-size: 1.05rem;
        line-height: 1.15;
    }

    #modalCheckIn .rv-checkin-titleblock p {
        font-size: .75rem;
    }

    #modalCheckIn .rv-checkin-stepper {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        padding: 0 16px;
    }

    #modalCheckIn .rv-checkin-line {
        display: none;
    }

    #modalCheckIn .rv-checkin-step {
        justify-content: center;
        gap: 6px;
        font-size: .76rem;
    }

    #modalCheckIn .rv-checkin-content {
        padding: 16px 16px 92px;
    }

    #modalCheckIn .rv-total-card {
        align-items: flex-start;
        flex-direction: column;
        min-height: 92px;
    }

    #modalCheckIn .rv-room-grid,
    #modalCheckIn .rv-input-grid,
    #modalCheckIn .rv-card-type,
    #modalCheckIn .rv-invoice-grid {
        grid-template-columns: 1fr !important;
    }

    #modalCheckIn .rv-checkin-actions {
        grid-template-columns: 1fr;
        padding: 12px 16px 14px !important;
    }

    #modalCheckIn .rv-step-dots {
        order: -1;
    }
}

@media (max-height: 720px) and (min-width: 681px) {
    #modalCheckIn .rv-checkin-topbar { min-height: 76px; padding-block: 14px; }
    #modalCheckIn .rv-checkin-stepper { min-height: 56px; }
    #modalCheckIn .rv-checkin-content { padding-block: 18px 82px; }
    #modalCheckIn .rv-total-card { min-height: 84px; padding: 16px; }
    #modalCheckIn .rv-room-card { min-height: 84px; }
    #modalCheckIn .rv-payment-section,
    #modalCheckIn .rv-invoice-section { padding: 14px !important; }
    #modalCheckIn .rv-pay-option > label { min-height: 44px; }
    #modalCheckIn .rv-checkin-actions { padding-block: 12px !important; }
}

/* Check-in tardio / proceso express: same operating surface as check-in. */
#modalCheckInTardio.rv-tardio-modal {
    padding: clamp(10px, 2vw, 24px);
    background:
        radial-gradient(circle at 18% 20%, color-mix(in srgb, var(--brand-accent, #BD9441) 24%, transparent), transparent 34%),
        linear-gradient(145deg, rgba(7, 12, 22, .86), rgba(23, 29, 43, .74));
    -webkit-backdrop-filter: blur(10px) saturate(1.05);
    backdrop-filter: blur(10px) saturate(1.05);
    z-index: 12000;
}

#modalCheckInTardio .rv-tardio-shell {
    width: min(960px, calc(100vw - 24px)) !important;
    max-width: min(960px, calc(100vw - 24px)) !important;
    height: min(92dvh, 900px) !important;
    max-height: min(92dvh, 900px) !important;
    display: grid !important;
    grid-template-columns: minmax(250px, .9fr) minmax(0, 1.7fr);
    grid-template-rows: auto minmax(0, 1fr);
    overflow: hidden !important;
    border-radius: 28px !important;
    background: #F8F4EC !important;
    border: 1px solid rgba(255,255,255,.55);
    box-shadow: 0 38px 92px -34px rgba(4, 8, 18, .78), 0 0 0 1px rgba(255,255,255,.22) inset;
    animation: rvCheckInShow .32s cubic-bezier(.22, 1, .36, 1);
}

#modalCheckInTardio .rv-tardio-hero {
    position: relative;
    isolation: isolate;
    grid-column: 1;
    grid-row: 1 / 3;
    min-height: 100%;
    padding: 28px !important;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    gap: 28px;
    color: #fff;
    background:
        linear-gradient(150deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 86%, #111827), color-mix(in srgb, var(--brand-primary, #1B2746) 74%, #1F2937)),
        var(--brand-secondary, #0F172A) !important;
    border-bottom: 0 !important;
}

#modalCheckInTardio[data-mode="express"] .rv-tardio-hero {
    background:
        linear-gradient(150deg, color-mix(in srgb, #9A3412 78%, #111827), color-mix(in srgb, var(--brand-secondary, #0F172A) 78%, #431407)),
        #431407 !important;
}

#modalCheckInTardio .rv-tardio-hero::before {
    content: '';
    position: absolute;
    inset: 14px;
    z-index: -1;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 22px;
}

#modalCheckInTardio .rv-tardio-hero::after {
    content: '';
    position: absolute;
    right: -48px;
    bottom: -48px;
    z-index: -1;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--brand-accent, #BD9441) 34%, transparent);
    filter: blur(4px);
}

#modalCheckInTardio .rv-tardio-hero > button {
    position: absolute !important;
    top: 18px !important;
    right: 18px !important;
    width: 38px !important;
    height: 38px !important;
    display: grid !important;
    place-items: center !important;
    border: 1px solid rgba(255,255,255,.24) !important;
    border-radius: 14px !important;
    background: rgba(255,255,255,.13) !important;
    color: #fff !important;
    cursor: pointer !important;
    transition: transform .18s ease, background .18s ease !important;
}

#modalCheckInTardio .rv-tardio-hero > button:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.24) !important;
}

#modalCheckInTardio .rv-tardio-hero h3 {
    margin: 0 !important;
    padding-top: 34px;
    max-width: 9ch;
    display: grid;
    gap: 14px;
    color: #fff !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(2rem, 4vw, 2.8rem) !important;
    font-weight: 700 !important;
    line-height: .9 !important;
    letter-spacing: 0 !important;
    text-wrap: balance;
}

#modalCheckInTardio .rv-tardio-hero h3::after {
    content: 'Regulariza la llegada y deja el pago listo sin pasos extra.';
    max-width: 25ch;
    color: rgba(255,255,255,.72);
    font-family: 'Manrope', system-ui, sans-serif;
    font-size: .86rem;
    font-weight: 700;
    line-height: 1.45;
}

#modalCheckInTardio[data-mode="express"] .rv-tardio-hero h3::after {
    content: 'Cobra y cierra check-in/check-out en un solo flujo.';
}

#modalCheckInTardio .rv-tardio-hero h3 i {
    width: 54px;
    height: 54px;
    margin: 0 !important;
    display: grid;
    place-items: center;
    border-radius: 18px;
    color: #fff !important;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.16);
    font-size: 1.15rem;
}

#modalCheckInTardio #alertaTardio {
    grid-column: 2;
    padding: 20px 20px 0 !important;
}

#modalCheckInTardio #alertaTardio > div {
    border: 1px solid color-mix(in srgb, #EA580C 25%, #F5D0A9) !important;
    border-radius: 17px !important;
    background: #FFF7ED !important;
    padding: 13px 14px !important;
}

#modalCheckInTardio .rv-tardio-body {
    grid-column: 2;
    min-height: 0;
    padding: 0 !important;
    overflow: hidden !important;
}

#modalCheckInTardio .rv-tardio-form {
    min-height: 0;
    height: 100%;
    max-height: 100%;
    display: grid;
    grid-template-rows: minmax(0, 1fr) auto;
    padding: 0;
    overflow: hidden;
}

#modalCheckInTardio .rv-tardio-fields {
    min-height: 0;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(175px, .58fr);
    gap: 14px;
    padding: 20px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--brand-primary, #1B2746) 28%, #D7DCE3) transparent;
}

#modalCheckInTardio .rv-tardio-info,
#modalCheckInTardio .rv-arrival-field,
#modalCheckInTardio .rv-total-field,
#modalCheckInTardio .rv-payment-section,
#modalCheckInTardio .rv-invoice-section,
#modalCheckInTardio .rv-notes-section,
#modalCheckInTardio #resumen_totales_tardio {
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E7DDD1) !important;
    border-radius: 20px !important;
    background: rgba(255,255,255,.92) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
    margin: 0 !important;
    padding: 16px !important;
}

#modalCheckInTardio .rv-tardio-info,
#modalCheckInTardio .rv-payment-section,
#modalCheckInTardio .rv-invoice-section,
#modalCheckInTardio .rv-notes-section,
#modalCheckInTardio #resumen_totales_tardio,
#modalCheckInTardio .rv-checkin-actions {
    grid-column: 1 / -1;
}

#modalCheckInTardio .rv-total-field {
    background: linear-gradient(145deg, #fff, color-mix(in srgb, var(--brand-accent, #BD9441) 9%, #fff)) !important;
}

#modalCheckInTardio .rv-total-field > div {
    padding: 0 !important;
    background: transparent !important;
    text-align: left !important;
    border-radius: 0 !important;
}

#modalCheckInTardio #totalACobrarTardio {
    display: block;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: clamp(1.55rem, 4vw, 2.2rem) !important;
    font-weight: 950 !important;
    line-height: 1 !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckInTardio .form-label,
#modalCheckInTardio .rv-total-field > label,
#modalCheckInTardio .rv-arrival-field > label,
#modalCheckInTardio .rv-pay-option label[style*="font-size: 0.75rem"] {
    display: block !important;
    margin-bottom: 7px !important;
    color: #111827 !important;
    font-size: .72rem !important;
    font-weight: 900 !important;
    letter-spacing: 0 !important;
    text-transform: uppercase;
}

#modalCheckInTardio input[type="time"],
#modalCheckInTardio input[type="number"],
#modalCheckInTardio input[type="text"],
#modalCheckInTardio textarea {
    width: 100% !important;
    min-height: 43px !important;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 16%, #DFE4EA) !important;
    border-radius: 14px !important;
    padding: 10px 12px !important;
    background: #fff !important;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .9rem !important;
    font-weight: 760 !important;
    outline: none !important;
    transition: border-color .18s ease, box-shadow .18s ease !important;
}

#modalCheckInTardio textarea {
    min-height: 74px !important;
    resize: vertical;
}

#modalCheckInTardio input[type="time"]:focus,
#modalCheckInTardio input[type="number"]:focus,
#modalCheckInTardio input[type="text"]:focus,
#modalCheckInTardio textarea:focus {
    border-color: var(--brand-primary, #1B2746) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-primary, #1B2746) 12%, transparent) !important;
}

#modalCheckInTardio .rv-payment-section > h4,
#modalCheckInTardio .rv-invoice-section > h4 {
    margin: 0 0 10px !important;
    color: var(--brand-secondary, #0F172A) !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: .94rem !important;
    font-weight: 950 !important;
}

#modalCheckInTardio .rv-payment-section > h4 i,
#modalCheckInTardio .rv-invoice-section > h4 i {
    color: var(--brand-primary, #1B2746) !important;
    margin-right: 0 !important;
}

#modalCheckInTardio #nota_pago_opcional {
    margin: 0 0 10px !important;
    color: #667085 !important;
    font-size: .78rem !important;
    font-weight: 760 !important;
}

#modalCheckInTardio .rv-pay-methods {
    display: grid !important;
    gap: 10px !important;
}

#modalCheckInTardio .rv-pay-option {
    --rv-pay-color: var(--brand-primary, #1B2746);
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--rv-pay-color) 16%, #E5E7EB) !important;
    border-radius: 17px !important;
    background: #fff !important;
    padding: 0 !important;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease;
}

#modalCheckInTardio .rv-pay-option::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: color-mix(in srgb, var(--rv-pay-color) 76%, #fff);
    opacity: .68;
}

#modalCheckInTardio .rv-pay-option.is-open,
#modalCheckInTardio .rv-pay-option:has(input[type="checkbox"]:checked) {
    border-color: color-mix(in srgb, var(--rv-pay-color) 46%, #D7DCE3);
    background: color-mix(in srgb, var(--rv-pay-color) 4%, #fff) !important;
    box-shadow: 0 18px 36px -28px color-mix(in srgb, var(--rv-pay-color) 70%, transparent);
    transform: translateY(-1px);
}

#modalCheckInTardio .rv-pay-pending { --rv-pay-color: #667085; }
#modalCheckInTardio .rv-pay-cash { --rv-pay-color: #148653; }
#modalCheckInTardio .rv-pay-card { --rv-pay-color: #2563EB; }
#modalCheckInTardio .rv-pay-transfer { --rv-pay-color: #7C3AED; }

#modalCheckInTardio .rv-pay-option > label {
    width: 100% !important;
    margin: 0 !important;
    padding: 13px 14px 13px 17px !important;
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .9rem !important;
    font-weight: 920 !important;
    cursor: pointer !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
}

#modalCheckInTardio .rv-pay-option > label i {
    color: var(--rv-pay-color) !important;
    margin-right: 0 !important;
}

#modalCheckInTardio .rv-pay-option > label input {
    width: 18px !important;
    height: 18px !important;
    margin-right: 0 !important;
    accent-color: var(--rv-pay-color) !important;
}

#modalCheckInTardio .rv-pay-option > div[id^="panel_"] {
    margin-top: 0 !important;
    border-top: 1px solid color-mix(in srgb, var(--rv-pay-color) 18%, #E5E7EB);
    padding: 13px 14px 15px 17px;
    background: color-mix(in srgb, var(--rv-pay-color) 6%, #fff);
}

#modalCheckInTardio #panel_efectivo_tardio > div:first-child,
#modalCheckInTardio #panel_tarjeta_tardio > div:last-child,
#modalCheckInTardio #panel_transferencia_tardio > div:first-child,
#modalCheckInTardio #panel_tarjeta_tardio > div:first-child,
#modalCheckInTardio .rv-invoice-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 10px !important;
}

#modalCheckInTardio #panel_tarjeta_tardio > div:first-child {
    margin-bottom: 10px !important;
}

#modalCheckInTardio #panel_efectivo_tardio > div:last-child {
    margin-top: 9px !important;
    padding: 9px 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 10px !important;
    border-radius: 14px !important;
    background: rgba(255,255,255,.78) !important;
    color: #475467 !important;
    font-size: .82rem !important;
    font-weight: 850 !important;
}

#modalCheckInTardio #cambio_efectivo_tardio {
    color: #148653 !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckInTardio .rv-radio-chip,
#modalCheckInTardio .rv-invoice-choice {
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #DCE2EA) !important;
    border-radius: 15px !important;
    background: #fff !important;
    cursor: pointer;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}

#modalCheckInTardio .rv-radio-chip {
    min-height: 42px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px !important;
    color: #1E40AF !important;
    font-size: .82rem !important;
    font-weight: 880 !important;
}

#modalCheckInTardio .rv-radio-chip input,
#modalCheckInTardio .rv-invoice-choice input {
    accent-color: var(--brand-primary, #1B2746);
}

#modalCheckInTardio .rv-radio-chip:has(input:checked) {
    border-color: #2563EB !important;
    background: #EFF6FF !important;
    box-shadow: 0 12px 24px -22px rgba(37,99,235,.82);
}

#modalCheckInTardio .rv-invoice-choice {
    min-height: 70px;
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px !important;
    padding: 13px !important;
}

#modalCheckInTardio .rv-invoice-choice > div > span {
    display: block;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .88rem !important;
    font-weight: 920 !important;
}

#modalCheckInTardio .rv-invoice-choice p {
    display: block;
    margin-top: 2px;
    color: #667085 !important;
    font-size: .72rem !important;
    font-weight: 700 !important;
    line-height: 1.35 !important;
}

#modalCheckInTardio #label_factura_si_tardio.is-selected {
    border-color: color-mix(in srgb, #2563EB 62%, #DCE2EA) !important;
    background: #EFF6FF !important;
    box-shadow: 0 12px 24px -22px rgba(37,99,235,.82);
}

#modalCheckInTardio #label_factura_no_tardio.is-selected {
    border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 38%, #DCE2EA) !important;
    background: color-mix(in srgb, var(--brand-primary, #1B2746) 5%, #fff) !important;
}

#modalCheckInTardio .rv-invoice-result {
    margin-top: 10px;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #DCE2EA);
    border-radius: 14px;
    background: color-mix(in srgb, var(--brand-primary, #1B2746) 4%, #FFFFFF);
    padding: 10px 12px;
}

#modalCheckInTardio .rv-invoice-result p {
    margin: 0;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    color: #667085;
    font-size: .76rem;
    font-weight: 750;
    line-height: 1.4;
}

#modalCheckInTardio .rv-invoice-result i {
    margin-top: 2px;
    color: var(--brand-primary, #1B2746);
}

#modalCheckInTardio .rv-invoice-result.is-client {
    border-color: #BFDBFE;
    background: #EFF6FF;
}

#modalCheckInTardio .rv-invoice-result.is-client p,
#modalCheckInTardio .rv-invoice-result.is-client i {
    color: #1D4ED8;
}

#modalCheckInTardio .rv-invoice-result.is-internal {
    border-color: #FED7AA;
    background: #FFF7ED;
}

#modalCheckInTardio .rv-invoice-result.is-internal p,
#modalCheckInTardio .rv-invoice-result.is-internal i {
    color: #9A3412;
}

#modalCheckInTardio .rv-invoice-result.is-none {
    border-color: #D1D5DB;
    background: #F9FAFB;
}

#modalCheckInTardio .rv-invoice-result.is-none p,
#modalCheckInTardio .rv-invoice-result.is-none i {
    color: #4B5563;
}

#modalCheckInTardio .rv-checkin-note,
#modalCheckInTardio .rv-checkin-message {
    margin-top: 9px !important;
    border-radius: 13px !important;
    padding: 10px 11px !important;
    font-size: .78rem !important;
    font-weight: 780 !important;
    line-height: 1.35 !important;
}

#modalCheckInTardio .rv-checkin-note {
    border: 1px solid #BFDBFE !important;
    background: #EFF6FF !important;
    color: #1E40AF !important;
}

#modalCheckInTardio .rv-checkin-message {
    border: 1px solid #FECACA !important;
    background: #FEF2F2 !important;
    color: #B42318 !important;
}

#modalCheckInTardio .rv-checkin-message.is-warning {
    border-color: #F4D38E !important;
    background: #FFF8E8 !important;
    color: #9A5F10 !important;
}

#modalCheckInTardio #resumen_totales_tardio {
    display: block;
    background: linear-gradient(135deg, #fff, #F6F2EA) !important;
}

#modalCheckInTardio #resumen_totales_tardio.hidden {
    display: none !important;
}

#modalCheckInTardio #resumen_totales_tardio > div {
    margin: 0 !important;
    padding: 4px 0;
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: #667085 !important;
    font-size: .82rem !important;
    font-weight: 780 !important;
}

#modalCheckInTardio .rv-checkin-actions {
    position: sticky;
    bottom: 0;
    display: flex !important;
    gap: 10px !important;
    padding: 15px 20px 20px !important;
    margin: 0 !important;
    border-top: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E7DDD1);
    background: color-mix(in srgb, #F8F4EC 90%, transparent) !important;
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}

#modalCheckInTardio .rv-btn-cancel,
#modalCheckInTardio .rv-btn-confirm {
    min-height: 45px !important;
    border-radius: 14px !important;
    padding: 0 16px !important;
    font-size: .9rem !important;
    font-weight: 930 !important;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

#modalCheckInTardio .rv-btn-cancel {
    flex: .9;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #DCE2EA) !important;
    color: var(--brand-secondary, #0F172A) !important;
    background: #fff !important;
}

#modalCheckInTardio .rv-btn-confirm {
    flex: 1.25;
    border: 0 !important;
    color: #fff !important;
    background: linear-gradient(135deg, #B45309, #92400E) !important;
    box-shadow: 0 16px 32px -19px rgba(180,83,9,.92) !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

#modalCheckInTardio[data-mode="express"] .rv-btn-confirm {
    background: linear-gradient(135deg, #EA580C, #C2410C) !important;
    box-shadow: 0 16px 32px -19px rgba(234,88,12,.9) !important;
}

#modalCheckInTardio .rv-btn-cancel:hover,
#modalCheckInTardio .rv-btn-confirm:hover {
    transform: translateY(-1px);
}

@media (max-width: 840px) {
    #modalCheckIn .rv-checkin-shell { grid-template-columns: 1fr; }
    #modalCheckInTardio .rv-tardio-shell {
        grid-template-columns: 1fr !important;
        grid-template-rows: auto auto minmax(0, 1fr) !important;
    }
    #modalCheckIn .rv-checkin-hero {
        min-height: auto;
        padding: 18px 64px 18px 18px;
        gap: 12px;
    }
    #modalCheckInTardio .rv-tardio-hero {
        grid-column: 1;
        grid-row: auto;
        min-height: auto;
        padding: 18px 64px 18px 18px !important;
        gap: 12px;
    }
    #modalCheckIn .rv-checkin-hero::before,
    #modalCheckIn .rv-checkin-hero::after,
    #modalCheckInTardio .rv-tardio-hero::before,
    #modalCheckInTardio .rv-tardio-hero::after { display: none; }
    #modalCheckIn .rv-checkin-hero h3 {
        max-width: none;
        padding-top: 0;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        font-family: 'Manrope', system-ui, sans-serif;
        font-size: 1.08rem !important;
        line-height: 1.15 !important;
    }
    #modalCheckInTardio .rv-tardio-hero h3 {
        max-width: none;
        padding-top: 0;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        font-family: 'Manrope', system-ui, sans-serif;
        font-size: 1.08rem !important;
        line-height: 1.15 !important;
    }
    #modalCheckIn .rv-checkin-hero h3 i {
        width: 44px;
        height: 44px;
        border-radius: 15px;
    }
    #modalCheckInTardio .rv-tardio-hero h3 i {
        width: 44px;
        height: 44px;
        border-radius: 15px;
    }
    #modalCheckIn .rv-checkin-hero h3::after {
        grid-column: 2;
        font-size: .76rem;
    }
    #modalCheckInTardio .rv-tardio-hero h3::after {
        grid-column: 2;
        font-size: .76rem;
    }
    #modalCheckInTardio #alertaTardio,
    #modalCheckInTardio .rv-tardio-body {
        grid-column: 1;
    }
    #modalCheckIn .rv-checkin-heading {
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        padding-top: 0;
    }
    #modalCheckIn .rv-checkin-icon { width: 44px; height: 44px; border-radius: 15px; }
    #modalCheckIn .rv-checkin-heading h3 {
        max-width: none;
        font-family: 'Manrope', system-ui, sans-serif;
        font-size: 1.08rem;
        line-height: 1.15;
    }
    #modalCheckIn .rv-checkin-heading p {
        max-width: none;
        grid-column: 2;
        font-size: .76rem;
    }
    #modalCheckIn .rv-checkin-total {
        grid-template-columns: 1fr auto;
        align-items: end;
        padding: 12px 14px;
    }
    #modalCheckIn .rv-checkin-total strong { font-size: 1.45rem; }
}

@media (max-width: 540px) {
    #modalCheckIn.rv-checkin-modal {
        align-items: flex-end;
        padding: 8px 8px 0;
    }
    #modalCheckInTardio.rv-tardio-modal {
        align-items: flex-end;
        padding: 8px 8px 0;
    }
    #modalCheckIn .rv-checkin-shell {
        width: 100%;
        max-height: 96dvh;
        border-radius: 24px 24px 0 0;
    }
    #modalCheckInTardio .rv-tardio-shell {
        width: 100% !important;
        height: 96dvh !important;
        max-height: 96dvh !important;
        border-radius: 24px 24px 0 0 !important;
    }
    #modalCheckIn .rv-checkin-form {
        grid-template-columns: 1fr;
        padding: 0;
    }
    #modalCheckInTardio .rv-tardio-fields {
        grid-template-columns: 1fr;
        padding: 14px;
    }
    #modalCheckIn .rv-payment-shortcuts,
    #modalCheckInTardio .rv-payment-shortcuts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    #modalCheckIn .rv-checkin-arrival,
    #modalCheckIn .rv-input-grid,
    #modalCheckIn .rv-card-type,
    #modalCheckIn .rv-invoice-grid,
    #modalCheckIn #panel_efectivo > div:first-child,
    #modalCheckIn #panel_tarjeta > div:first-child,
    #modalCheckIn #panel_tarjeta > div:last-child,
    #modalCheckIn #panel_transferencia > div:first-child {
        grid-template-columns: 1fr !important;
    }
    #modalCheckInTardio #panel_efectivo_tardio > div:first-child,
    #modalCheckInTardio #panel_tarjeta_tardio > div:first-child,
    #modalCheckInTardio #panel_tarjeta_tardio > div:last-child,
    #modalCheckInTardio #panel_transferencia_tardio > div:first-child,
    #modalCheckInTardio .rv-invoice-grid {
        grid-template-columns: 1fr !important;
    }
    #modalCheckIn .rv-checkin-actions {
        flex-direction: column;
        padding: 12px 14px 14px !important;
    }
    #modalCheckInTardio .rv-checkin-actions {
        flex-direction: column;
        padding: 12px 14px 14px !important;
    }
    #modalCheckIn .rv-btn-cancel,
    #modalCheckIn .rv-btn-confirm,
    #modalCheckInTardio .rv-btn-cancel,
    #modalCheckInTardio .rv-btn-confirm { width: 100%; }
}

/* Reservation index-aligned check-in modal shell. */
#modalCheckIn.rv-checkin-modal {
    --rv-checkin-ink: var(--brand-secondary, #0F172A);
    --rv-checkin-brand: var(--brand-primary, #1B2746);
    --rv-checkin-accent: var(--brand-accent, #BD9441);
    --rv-checkin-line: color-mix(in srgb, var(--rv-checkin-brand) 10%, #E7DDD1);
    --rv-checkin-soft: color-mix(in srgb, var(--rv-checkin-accent) 7%, #F8F4EC);
    --rv-checkin-muted: #667085;
    padding: clamp(12px, 2.5vw, 28px);
    background:
        radial-gradient(circle at 18% 18%, color-mix(in srgb, var(--rv-checkin-brand) 22%, transparent), transparent 34%),
        linear-gradient(135deg, rgba(8, 13, 24, .82), rgba(26, 32, 45, .74));
    -webkit-backdrop-filter: blur(10px) saturate(1.1);
    backdrop-filter: blur(10px) saturate(1.1);
}

#modalCheckIn .rv-checkin-shell {
    width: min(920px, 100%) !important;
    max-width: min(920px, calc(100vw - 24px)) !important;
    height: min(91dvh, 860px) !important;
    max-height: min(91dvh, 860px) !important;
    display: block !important;
    border-radius: 28px;
    background: #F8F4EC;
    border: 1px solid rgba(255, 255, 255, .54);
    box-shadow: 0 36px 90px -36px rgba(7, 10, 18, .78), 0 0 0 1px rgba(255,255,255,.28) inset;
}

#modalCheckIn .rv-checkin-form {
    height: 100%;
    max-height: min(91dvh, 860px);
    display: grid;
    grid-template-columns: minmax(230px, .82fr) minmax(0, 1.75fr);
    grid-template-rows: auto minmax(0, 1fr) auto;
    padding: 0;
    overflow: hidden;
}

#modalCheckIn .rv-checkin-topbar {
    grid-column: 1;
    grid-row: 1 / 4;
    min-height: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    padding: 26px;
    color: #FFFEFB;
    position: relative;
    isolation: isolate;
    border: 0;
    background:
        linear-gradient(155deg, color-mix(in srgb, var(--rv-checkin-ink) 86%, #111827), color-mix(in srgb, var(--rv-checkin-brand) 74%, #1F2937)),
        var(--rv-checkin-ink);
}

#modalCheckIn .rv-checkin-topbar::before {
    content: '';
    position: absolute;
    inset: 14px;
    z-index: -1;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 22px;
}

#modalCheckIn .rv-checkin-topbar::after {
    content: '';
    position: absolute;
    right: -42px;
    bottom: -46px;
    z-index: -1;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--rv-checkin-accent) 32%, transparent);
    filter: blur(4px);
    opacity: .65;
}

#modalCheckIn .rv-checkin-titleblock {
    display: grid;
    align-items: start;
    gap: 14px;
    min-width: 0;
    padding-top: 28px;
}

#modalCheckIn .rv-checkin-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: inline-grid;
    place-items: center;
    color: #FFFEFB;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
}

#modalCheckIn .rv-checkin-titleblock h3 {
    max-width: 9ch;
    margin: 0;
    color: #FFFEFB;
    font-family: inherit;
    font-size: clamp(1.55rem, 3.8vw, 2.3rem);
    line-height: .96;
    font-weight: 600;
    text-wrap: balance;
}

#modalCheckIn .rv-checkin-titleblock p {
    max-width: 23ch;
    margin: 0;
    color: rgba(255,255,255,.72);
    font-size: .84rem;
    font-weight: 700;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

#modalCheckIn .rv-checkin-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    border: 1px solid rgba(255,255,255,.24);
    border-radius: 14px;
    color: #FFFEFB;
    background: rgba(255,255,255,.13);
}

#modalCheckIn .rv-checkin-close:hover {
    color: #FFFEFB;
    background: rgba(255,255,255,.25);
    border-color: rgba(255,255,255,.34);
}

#modalCheckIn .rv-checkin-stepper {
    grid-column: 2;
    grid-row: 1;
    min-height: 66px;
    padding: 0 22px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 4%, #F8F4EC);
    border-bottom: 1px solid var(--rv-checkin-line);
}

#modalCheckIn .rv-checkin-content {
    grid-column: 2;
    grid-row: 2;
    min-height: 0;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 0;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--rv-checkin-brand) 28%, #D7DCE3) transparent;
}

#modalCheckIn .rv-checkin-actions {
    grid-column: 2;
    grid-row: 3;
    display: grid !important;
    grid-template-columns: minmax(120px, auto) 1fr minmax(190px, auto);
    gap: 10px !important;
    padding: 15px 20px 20px !important;
    border-top: 1px solid var(--rv-checkin-line);
    background: color-mix(in srgb, #F8F4EC 90%, transparent) !important;
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}

#modalCheckIn .rv-checkin-stage,
#modalCheckIn .rv-checkin-summary {
    border: 1px solid var(--rv-checkin-line);
    border-radius: 20px;
    background: rgba(255,255,255,.92);
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
}

#modalCheckIn .rv-stage-arrival {
    padding: 16px;
    border: 1px solid var(--rv-checkin-line);
    background: rgba(255,255,255,.92);
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
}

#modalCheckIn .rv-total-card {
    min-height: 98px;
    padding: 18px;
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--rv-checkin-brand) 10%, #E7DDD1);
    border-radius: 20px;
    background:
        linear-gradient(145deg, #FFFEFB, color-mix(in srgb, var(--rv-checkin-accent) 9%, #FFFEFB));
}

#modalCheckIn .rv-total-card::after {
    content: '';
    position: absolute;
    right: 14px;
    top: 14px;
    width: 28px;
    height: 3px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rv-checkin-accent) 70%, #FFFEFB);
}

#modalCheckIn .rv-kicker,
#modalCheckIn .form-label,
#modalCheckIn .rv-pay-panel label {
    color: var(--rv-checkin-brand) !important;
    font-size: .72rem !important;
    font-weight: 900 !important;
    letter-spacing: .05em !important;
    text-transform: uppercase !important;
}

#modalCheckIn #totalACobrar {
    margin-top: 4px;
    color: var(--rv-checkin-ink) !important;
    font-family: inherit;
    font-size: clamp(1.55rem, 4vw, 2.2rem) !important;
    font-weight: 950 !important;
    line-height: 1 !important;
}

#modalCheckIn .rv-time-input,
#modalCheckIn .rv-room-card,
#modalCheckIn .rv-pay-option,
#modalCheckIn .rv-radio-chip,
#modalCheckIn .rv-invoice-choice {
    border-radius: 17px !important;
    background: #FFFEFB !important;
}

#modalCheckIn .rv-payment-section,
#modalCheckIn .rv-invoice-section {
    padding: 16px !important;
}

#modalCheckIn .rv-payment-section > h4,
#modalCheckIn .rv-invoice-section h4 {
    color: var(--rv-checkin-ink) !important;
    font-size: .95rem !important;
    font-weight: 900 !important;
}

#modalCheckIn .rv-pay-option.is-open,
#modalCheckIn .rv-pay-option:has(input[type="checkbox"]:checked) {
    border-color: color-mix(in srgb, var(--rv-pay-color) 46%, #D7DCE3) !important;
    background: color-mix(in srgb, var(--rv-pay-color) 4%, #FFFEFB) !important;
    box-shadow: 0 18px 36px -28px color-mix(in srgb, var(--rv-pay-color) 70%, transparent);
}

#modalCheckIn .rv-pay-panel {
    padding: 13px 14px 15px 17px;
    border-top: 1px solid color-mix(in srgb, var(--rv-pay-color) 18%, #E5E7EB);
    background: color-mix(in srgb, var(--rv-pay-color) 6%, #FFFEFB);
}

#modalCheckIn .rv-checkin-summary {
    padding: 15px 16px !important;
    background: linear-gradient(135deg, #FFFEFB, #F6F2EA);
}

#modalCheckIn .rv-checkin-summary h5 {
    margin: 0 0 8px !important;
    padding: 0;
    background: transparent;
    color: var(--rv-checkin-ink) !important;
    font-family: inherit;
    font-size: .82rem !important;
    font-weight: 950 !important;
}

#modalCheckIn .rv-summary-row {
    min-height: auto;
    padding: 4px 0;
    border-top: 0;
    color: #667085 !important;
    font-size: .82rem !important;
}

#modalCheckIn .rv-btn-cancel,
#modalCheckIn .rv-btn-confirm {
    min-height: 44px !important;
    border-radius: 13px !important;
    font-weight: 900 !important;
}

#modalCheckIn .rv-btn-cancel {
    border: 1px solid color-mix(in srgb, var(--rv-checkin-brand) 13%, #DCE2EA) !important;
    color: var(--rv-checkin-ink) !important;
    background: #FFFEFB !important;
}

#modalCheckIn .rv-btn-confirm {
    background: linear-gradient(135deg, #148653, #0F6F49) !important;
    box-shadow: 0 15px 30px -18px rgba(20,134,83,.9) !important;
}

@media (max-width: 860px) {
    #modalCheckIn .rv-checkin-shell {
        height: min(94dvh, 860px) !important;
        max-height: min(94dvh, 860px) !important;
        border-radius: 20px;
    }

    #modalCheckIn .rv-checkin-form {
        height: 100%;
        grid-template-columns: 1fr;
        grid-template-rows: auto auto minmax(0, 1fr) auto;
    }

    #modalCheckIn .rv-checkin-topbar {
        grid-column: 1;
        grid-row: 1;
        min-height: auto;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        padding: 18px 64px 18px 18px;
    }

    #modalCheckIn .rv-checkin-topbar::before,
    #modalCheckIn .rv-checkin-topbar::after {
        display: none;
    }

    #modalCheckIn .rv-checkin-titleblock {
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        padding-top: 0;
    }

    #modalCheckIn .rv-checkin-icon {
        width: 44px;
        height: 44px;
        border-radius: 15px;
    }

    #modalCheckIn .rv-checkin-titleblock h3 {
        max-width: none;
        font-size: 1.08rem;
        line-height: 1.15;
    }

    #modalCheckIn .rv-checkin-titleblock p {
        max-width: none;
        grid-column: 2;
        font-size: .76rem;
    }

    #modalCheckIn .rv-checkin-stepper {
        grid-column: 1;
        grid-row: 2;
    }

    #modalCheckIn .rv-checkin-content {
        grid-column: 1;
        grid-row: 3;
        padding: 14px;
    }

    #modalCheckIn .rv-checkin-actions {
        grid-column: 1;
        grid-row: 4;
        padding: 12px 14px 14px !important;
    }
}

@media (max-width: 540px) {
    #modalCheckIn.rv-checkin-modal {
        align-items: flex-end;
        padding: 8px 8px 0;
    }

    #modalCheckIn .rv-checkin-shell {
        width: 100% !important;
        max-width: 100% !important;
        height: 96dvh !important;
        max-height: 96dvh !important;
        border-radius: 20px 20px 0 0;
    }

    #modalCheckIn .rv-checkin-stepper {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        padding: 0 16px;
    }

    #modalCheckIn .rv-checkin-line {
        display: none;
    }

    #modalCheckIn .rv-checkin-actions {
        grid-template-columns: 1fr;
    }

    #modalCheckIn .rv-step-dots {
        order: -1;
    }

    #modalCheckIn .rv-btn-cancel,
    #modalCheckIn .rv-btn-confirm {
        width: 100%;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Estilos de impresión mejorados */
@media print {
    body {
        background: white !important;
    }

    .detail-view {
        background: white !important;
    }

    .info-card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }

    .card-header {
        background: #f5f5f5 !important;
        border-bottom: 1px solid #ddd !important;
    }

    .btn-action,
    .no-print,
    button {
        display: none !important;
    }
}
</style>

<style>
/* Edición inline del huésped (nombre / teléfono / email) en el detalle de reservación */
.rdv3-card--guest .rd-editable { cursor: text; border-radius: 6px; padding: 1px 4px; margin: -1px -4px; transition: background .15s ease, box-shadow .15s ease; outline: none; }
.rdv3-card--guest .rd-editable:hover { background: color-mix(in srgb, var(--brand-accent, #BD9441) 13%, transparent); box-shadow: inset 0 -1px 0 color-mix(in srgb, var(--brand-accent, #BD9441) 55%, transparent); }
.rdv3-card--guest .rd-editable:focus-visible { box-shadow: 0 0 0 2px color-mix(in srgb, var(--brand-accent, #BD9441) 45%, transparent); }
.rdv3-card--guest .rd-editable.rd-editing { background: #fff; box-shadow: none; cursor: text; }
.rdv3-card--guest .rd-editable.rd-edit-saving { opacity: .6; pointer-events: none; }
.rdv3-card--guest .rd-edit-input { font: inherit; color: inherit; width: 100%; min-width: 120px; box-sizing: border-box; border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 60%, #ccc); border-radius: 6px; padding: 2px 6px; background: #fff; box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-accent, #BD9441) 18%, transparent); outline: none; }
.rdv3-card--guest h2.rd-editable .rd-edit-input { font-size: inherit; font-weight: inherit; }
.rd-flash-ok { animation: rdFlashOk .9s ease; }
.rd-flash-error { animation: rdFlashErr .9s ease; }
@keyframes rdFlashOk { 0% { background: color-mix(in srgb, #1E9E63 26%, transparent); } 100% { background: transparent; } }
@keyframes rdFlashErr { 0% { background: color-mix(in srgb, #B4392B 24%, transparent); } 100% { background: transparent; } }
</style>

<script>
(function () {
    const card = document.querySelector('.rdv3-card--guest[data-huesped-id]');
    if (!card) return;
    const huespedId = parseInt(card.getAttribute('data-huesped-id'), 10);
    if (!huespedId) return;

    const endpoint = '<?= url("huespedes/") ?>' + huespedId + '/actualizar-inline';
    const csrf = '<?= csrf_token() ?>';
    let activo = null;

    function soloDigitos(v) { return (v || '').replace(/\D/g, '').slice(0, 15); }

    function avisar(msg, ok) {
        if (typeof mostrarAvisoReservacion === 'function') {
            mostrarAvisoReservacion(msg, ok ? 'success' : 'error', ok ? 2200 : 4200);
        }
    }

    function pintarValor(el, valor) {
        el.dataset.value = valor;
        el.textContent = valor !== '' ? valor : (el.dataset.empty || '');
        const info = el.closest('.rdv3-info');
        if (info) info.classList.toggle('is-empty', valor === '');
    }

    function flash(el, ok) {
        el.classList.remove('rd-flash-ok', 'rd-flash-error');
        void el.offsetWidth;
        el.classList.add(ok ? 'rd-flash-ok' : 'rd-flash-error');
        setTimeout(function () { el.classList.remove('rd-flash-ok', 'rd-flash-error'); }, 1000);
    }

    function guardar(el, field, nuevo, anterior) {
        activo = null;
        el.classList.remove('rd-editing');
        el.classList.add('rd-edit-saving');
        el.textContent = nuevo !== '' ? nuevo : (el.dataset.empty || '');

        const body = new URLSearchParams();
        body.append('campo', field);
        body.append('valor', nuevo);
        body.append('csrf_token', csrf);

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf,
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                el.classList.remove('rd-edit-saving');
                if (data && data.success) {
                    pintarValor(el, nuevo);
                    flash(el, true);
                    avisar('Cambio guardado', true);
                } else {
                    pintarValor(el, anterior);
                    flash(el, false);
                    avisar((data && data.message) || 'No se pudo guardar', false);
                }
            })
            .catch(function () {
                el.classList.remove('rd-edit-saving');
                pintarValor(el, anterior);
                flash(el, false);
                avisar('Error de conexión. Intenta de nuevo.', false);
            });
    }

    function iniciar(el) {
        if (activo) return;
        activo = el;
        const field = el.dataset.field;
        const type = el.dataset.type || 'text';
        const valor = el.dataset.value || '';

        el.classList.add('rd-editing');
        el.textContent = '';

        const input = document.createElement('input');
        input.type = (type === 'tel') ? 'tel' : (type === 'email' ? 'email' : 'text');
        input.className = 'rd-edit-input';
        input.value = valor;
        input.setAttribute('aria-label', 'Editar ' + field);
        if (type === 'tel') { input.inputMode = 'numeric'; input.maxLength = 15; }
        el.appendChild(input);
        input.focus();
        input.select();

        let cerrado = false;
        function cerrar(guardarCambio) {
            if (cerrado) return;
            cerrado = true;
            let nuevo = input.value.trim();
            if (type === 'tel') nuevo = soloDigitos(nuevo);
            if (!guardarCambio || nuevo === valor) {
                activo = null;
                el.classList.remove('rd-editing');
                pintarValor(el, valor);
                return;
            }
            guardar(el, field, nuevo, valor);
        }

        if (type === 'tel') {
            input.addEventListener('input', function () { input.value = soloDigitos(input.value); });
        }
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); cerrar(true); }
            else if (e.key === 'Escape') { e.preventDefault(); cerrar(false); }
        });
        input.addEventListener('blur', function () { cerrar(true); });
    }

    card.querySelectorAll('.rd-editable').forEach(function (el) {
        el.addEventListener('click', function () { iniciar(el); });
        el.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ' ') && activo !== el) { e.preventDefault(); iniciar(el); }
        });
    });
})();
</script>
